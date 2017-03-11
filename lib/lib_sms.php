<?php
/**
* @file
* Library of voice and texting related functions
* 
* ../config.php is required before including this file
*
*/


// Require the bundled autoload file - the path may need to change
// based on where you downloaded and unzipped the SDK
require $HTML_BASE . '/vendor/autoload.php';

// Use the REST API Client to make requests to the Twilio REST API
use Twilio\Rest\Client;
use Twilio\Twiml;

/**
* Retrieve the contacts that are accepting calls.
*
* Based on the day, time and language choice.
* 
* @param array &$contacts
*   Array of contacts to be loaded.
* @param int $language_id
*   The language the contact must speak.  If zero, all languages will be returned.
* @param bool $texting
*   If true, the contact must support texting.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if retrieved successfully.
*/

function sms_getActiveContacts(&$contacts, $language_id, $texting, &$error)
{
    $day = date('D');
    $weekend = ($day == 'Sun' || $day == 'Sat');

    // Follow me numbers
    $sql = "SELECT DISTINCT contacts.* FROM call_times ".
        "LEFT JOIN contacts ON contacts.id = call_times.contact_id ".
        "WHERE enabled='y' AND ".
        ($language_id ? "language_id = '".addslashes($language_id)."' AND " : "") .
        ($texting ? "receive_texts = 'y' AND " : "") .
        "((day='all' OR day='{$day}' OR ".
        ($weekend ? "day='weekends'" : "day='weekdays'") .
        ") AND ".
        "((earliest < CURTIME() AND latest > CURTIME()) OR ".
        " (earliest > latest AND (earliest < CURTIME() OR latest > CURTIME()))))";
    if (!db_db_query($sql, $contacts, $error)) {
        return false;
    }

    return true;
}

/**
* Send a text message to a list of numbers
*
* ...
* 
* @param array $numbers
*   Array of phone numbers to send the text to.
* @param string $text
*   The body of the text message.
* @param string &$error
*   An error if one occurred.
* @param string $from = ''
*   The number to send from.  Defaults to the $HOTLINE_CALLER_ID constant.
*   
* @return bool
*   True if sent.
*/

function sms_send($numbers, $text, &$error, $from = '')
{
	global $TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN, $HOTLINE_CALLER_ID;
	
	// default from address
	if (!$from) {
		$from = $HOTLINE_CALLER_ID;
	}
	
	// create a Twilio client
	$client = new Client($TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN);

	// send the messages
	$error = '';
	foreach ($numbers as $number) {
        try {
            $client->messages->create($number,
				array('from' => $from,
                      'body' => $text)
			);
        } catch (Services_Twilio_RestException $e) {
            $error .= $phone . ": " . $e->getMessage() . "\n";
        }
	}

	return true;
}

/**
* Record the particulars of the call or text.
*
* ...
* 
* @param array $data
*   Data, usually copied from the $_REQUEST
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if sent.
*/

function sms_storeCallData($data, &$error)
{
	// pull out the call data
	$from = $data['From'];
	$to = $data['To'];
	$body = $data['Body'];
	if ($data['MessageSid']) {
		$twilio_sid = $data['MessageSid'];
		$status = 'text';
	} else {
		$twilio_sid = $data['CallSid'];
		if ($data['status']) {
			$status = $data['status'];
		} else {
			$status = 'call in progress';
		}
	}
	$media = array();
	if ((int)$data['NumMedia']) {
		$count = (int)$data['NumMedia'];
		for ($i = 0; $i < $count; $i++) {
			$media[$i] = array(
				"mime_type" => $data['MediaContentType' . $i],
				"url" => $data['MediaUrl' . $i]
			);
		}
	}

	$media_urls = '';
	// any media urls?
	if (count($media)) {
		foreach ($media as $file) {
			$media_urls .= $file['url'] . "\t" . $file['mime_type'] . "\n";
		}
	}
	
	// record the communication
	$sql = "INSERT INTO communications SET ".
		"phone_from='".addslashes($from)."', ".
		"phone_to='".addslashes($to)."', ".
		"body='".addslashes($body)."', communication_time=NOW(), ".
		"twilio_sid='".addslashes($twilio_sid)."', ".
		"status='".addslashes($status)."', ".
		"media_urls='{$media_urls}'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	return true;
}

/**
* Return the contact name for a particular caller
*
* ...
* 
* @param string &$name
*   The contact name, if found
* @param string &$number
*   The phone number to search by
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_whoIsCaller(&$name, $number, &$error)
{
	$sql = "SELECT contact_name FROM contacts WHERE phone='".addslashes($number)."'";
	if (!db_db_getone($sql, $name, $error)) {
		return false;
	}
	
	return true;
}

/**
* Handle administrative text requests
*
* Process administrative text requests, such as STOP, START, ON or OFF.
* 
* @param string $from
*   The phone number that sent the text.
* @param string $to
*   The phone number that is receiving the text.
* @param string $body
*   The body of the text.
* @param string &$response
*   Our response, if any.  Only set if function returns true.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if this was an administrative request.
*/

function sms_handleAdminText($from, $to, $body, &$response, &$error)
{
	// is this a one-word admin request?
	$body = trim(strtoupper($body));
	switch($body) {
		case 'STOP':
		case 'STOPALL':
		case 'UNSUBSCRIBE':
		case 'CANCEL':
		case 'END':
		case 'QUIT':
			// disable sending to this number, and don't respond - we can't.
			sms_updateNumber(false /* disable */, $from, $to, $response, $error);
			$response = '';
			return true;
		case 'START':
		case 'YES':
		case 'UNSTOP':
		case 'ON':
		case 'ALERT':
		case 'ALERTS':
			// enable sending to this number, and respond.
			sms_updateNumber(true /* enable */, $from, $to, $response, $error);
			return true;
		case 'OFF':
			// disable sending to this number, and respond.
			sms_updateNumber(false /* disable */, $from, $to, $response, $error);
			return true;
		default:
			// do nothing, not an administrative request.
			break;
	}
	
	// not an administrative request
	return false;
}

/**
* Enable or disable sending to a number.
*
* ...
* 
* @param bool $enable
*   Enables sending if true, disables if false.
* @param string $from
*   The phone number that sent the text.
* @param string $to
*   The phone number that is receiving the text.
* @param string &$response
*   Response to the enabling/disabling.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_updateNumber($enable, $from, $to, &$response, &$error)
{
	global $BROADCAST_CALLER_ID, $HOTLINE_CALLER_ID, $BROADCAST_WELCOME, $BROADCAST_GOODBYE;
	
	$enabled = $enable ? 'y' : 'n';
	
	// disable/enable call times for volunteers
	if ($to == $HOTLINE_CALLER_ID) {
		$sql = "UPDATE call_times ".
			"LEFT JOIN contacts ON call_times.contact_id = contacts.id ".
			"SET enabled='".addslashes($enabled)."' ".
			"WHERE contacts.phone = '".addslashes($from)."'";
		if (!db_db_command($sql, $error)) {
			return false;
		}
		
		// only respond if they are listed in the call_times table
		if (db_db_affected_rows()) {
			if ($enable) {
				$response .= "Hotline calls are now enabled. ";
			} else {
				$response .= "Hotline calls are now disabled. ";
			}
		}
	}
	
	// disable/enable broadcast texts
	if ($to == $BROADCAST_CALLER_ID) {
		// is this phone number in the database?
		$sql = "SELECT COUNT(*) FROM broadcast WHERE phone = '".addslashes($from)."'";
		if (!db_db_getone($sql, $broadcast_count, $error)) {
			return false;
		}
		if ($broadcast_count == 0 && $enable) {
			// not in database, but they want to enable
			$sql = "INSERT INTO broadcast SET status='active', phone='".addslashes($from)."'";
		} else {
			// in database, enable or disable
			$sql = "UPDATE broadcast SET status='" . ($enable ? 'active' : 'disabled') . "' ".
				"WHERE phone='".addslashes($from)."'";
		}
		if (!db_db_command($sql, $error)) {
			return false;
		}
		
		// respond with a welcome or a goodbye
		$response .= $enable ? $BROADCAST_WELCOME : $BROADCAST_GOODBYE;
	}
	
	return true;
}

?>
