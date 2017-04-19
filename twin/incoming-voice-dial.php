<?php
/**
* @file
* Handle an incoming voice call - part 2
*
* Look up who is available to be called at a given date and time and language.  Puts the
* caller in a queue, and initiates the calls to the available staff.  If no staff are
* available, the caller is sent directly to voicemail.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$language_keypress = $_REQUEST['Digits'];
$from = $_REQUEST['From'];
$call_status = $_REQUEST['CallStatus'];

$response = new Twilio\Twiml();

// is the call still active?
if ($call_status != 'completed') {
    // keypress 0 indicates that the caller wants to go straight to voicemail
    // also send to voicemail if this number is blocked
    if ($language_keypress == '0' || sms_isNumberBlocked($from)) {
        $response->redirect('voicemail.php?language_id=0');
    } else {
		// load the language data
		sms_loadLanguageByKeypress($language_keypress, $language, $error);
		$language_id = (int)$language['id'];

		// get the staff's phone numbers to call
		getNumbersToCall($from, $language_id, $enqueue_anyway, $numbers, $error);

		// anyone to call?
		if (count($numbers) || $enqueue_anyway) {
			// initiate calls to each of these staff
			if (count($numbers)) {
				sms_placeCalls($numbers, $TWILIO_INTERFACE_WEBROOT . 'screen-call.php?language_id=' . $language_id,
					$HOTLINE_CALLER_ID, $error);
			}
			
			// enqueue the caller
			$response->enqueue('hotline',
				array('waitUrl' => $TWILIO_INTERFACE_WEBROOT . 'incoming-voice-queue.php?language_id=' . $language_id)
			);

			// fall through to voicemail when leaving the queue
			$response->redirect('voicemail.php?language_id=' . $language_id);
		} else {
			// no one to call, redirect to voicemail now
			$response->redirect('voicemail.php?language_id=' . $language_id);
		}
    }
}

echo $response;

db_databaseDisconnect();

/**
* Gets the numbers of on-duty staff
*
* Removes anyone who is already on a call, and prevents people from
* calling themselves.
* 
* @param string $from
*   The person who is calling the hotline
* @param int $language_id
*   The language they are requesting
* @param bool &$enqueue_anyway
*   Set to true if the caller should be enqueued even if there are no
*   numbers to call.
* @param array &$numbers
*   Set to an array of numbers to call
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred
*/

function getNumbersToCall($from, $language_id, &$enqueue_anyway, &$numbers, &$error)
{
	global $HOTLINE_CALLER_ID;
	
	$call_anyway = false;
	$numbers = array();
	
	// who should we call given the current day, time and language?
	if (!sms_getActiveContacts($contacts, $language_id, false /* not texting */, $error)) {
		return false;
	}

	// is there anyone to call?
	if (!count($contacts)) {
		// no
		return true;
	}
	
	// yes, pull out the phone numbers
	
	// check the currently active calls coming from this number, and 
	// don't call people who are already on a call
	if (!sms_getActiveCalls($HOTLINE_CALLER_ID, '', $active_calls, $error)) {
		return false;
	}
	
	foreach ($contacts as $contact) {
		// don't call the person who is calling
		if ($from == $contact['phone']) {
			continue;
		}
			
		// don't call people already on a call
		$in_progress = false;
		foreach ($active_calls as $call) {
			if ($call['From'] == $HOTLINE_CALLER_ID &&
				$call['To'] == $contact['phone']) {
				// an active call is in progress
				$in_progress = true;
				
				// which means that there are calls in progress that might
				// be ready to answer - call anyway even if all numbers
				// are eliminated
				$call_anyway = true;
				break;
			}
		}
		if ($in_progress) {
			continue;
		}
		
		$numbers[] = $contact['phone'];
	}

	// randomize the numbers to call so different people get called first
	// (there is a 1 second delay between each call)
	shuffle($numbers);
		
	return true;
}

?>
