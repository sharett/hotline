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
$language_id = (int)$_REQUEST['Digits'];
$from = $_REQUEST['From'];
$call_status = $_REQUEST['CallStatus'];

$response = new Twilio\Twiml();

// is the call still active?
if ($call_status != 'completed') {
	// load the language data
	sms_loadLanguage($language_id, $language, $error);
	$language_id = $language['id'];

	// get the staff's phone numbers to call
	getNumbersToCall($from, $language_id, $numbers, $error);
	
	// anyone to call?
	if (count($numbers)) {
		// initiate calls to each of these staff
		sms_placeCalls($numbers, $TWILIO_INTERFACE_WEBROOT . 'screen-call.php?language_id=' . $language_id, 
			$HOTLINE_CALLER_ID, $error);

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
* @param array &$numbers
*   Set to an array of numbers to call
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred
*/

function getNumbersToCall($from, $language_id, &$numbers, &$error)
{
	global $HOTLINE_CALLER_ID;
	
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
	
	// check the currently active calls, and don't call people who are already
	// on a call
	if (!sms_getActiveCalls($HOTLINE_CALLER_ID, $active_calls, $error)) {
		return false;
	}
	
	foreach ($contacts as $contact) {
		// don't call the person who is calling
		if ($from == $contact['phone']) {
			continue;
		}
		
		// don't call people already on a call
		foreach ($active_calls as $call) {
			if ($call['From'] == $HOTLINE_CALLER_ID &&
				$call['To'] == $contact['phone']) {
				// an active call is in progress
				continue;
			}
		}
		
		$numbers[] = $contact['phone'];
	}

	// randomize the numbers to call so different people get called first
	// (there is a 1 second delay between each call)
	shuffle($numbers);
	
	return true;
}

?>
