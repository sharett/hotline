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
* @param string $from = ''
*   The number to send from.  Defaults to the $HOTLINE_CALLER_ID constant.
* @param int $progress_every = 0
*   If nonzero, displays a progress mark after this many messages are sent.
*   
* @return bool
*   True unless no messages were sent successfully.
*/

function sms_send($numbers, $text, &$error, $from = '', $progress_every = 0)
{
	global $TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN, $HOTLINE_CALLER_ID;
	
	if (!count($numbers)) {
		// there are no messages to send
		return true;
	}
	
	if ($progress_every) {
		echo '<p>Sending ';
	}
	
	// default from address
	if (!$from) {
		$from = $HOTLINE_CALLER_ID;
	}
	
	// create a Twilio client
	$client = new Client($TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN);

	// send the messages
	$error = '';
	$count = 0;
	$error_count = 0;
	foreach ($numbers as $number) {
		// reset the execution time limit to insure we have time to send all the messages
		set_time_limit(30);
		
        try {
            $client->messages->create($number,
				array('from' => $from,
                      'body' => $text)
			);
        } catch (Exception $e) {
            $error .= $number . ": " . $e->getMessage() . "<br>\n";
            $error_count++;
        }
        
        $count++;
        // display progress?
        if ($progress_every) {
			if ($count % $progress_every == 0) {
				echo "." . str_repeat(' ', 1024);
				flush();
				ob_flush();
			}
		}
	}
	
	if ($progress_every) {
		echo '</p>';
	}
	
	// return true unless all messages were not sent
	if ($error_count == $count) {
		return false;
	} else {
		return true;
	}
}

/**
* Place calls to a list of numbers
*
* ...
* 
* @param array $numbers
*   Array of phone numbers to call
* @param string $url
*   The URL for Twilio to request when connected
* @param string $from
*   The number to place the call from.  Defaults to the $HOTLINE_CALLER_ID constant.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True - errors reported in $error parameter.
*/

function sms_placeCalls($numbers, $url, $from, &$error)
{
	global $TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN, $HOTLINE_CALLER_ID;
	
	// default from number
	if (!$from) {
		$from = $HOTLINE_CALLER_ID;
	}
	
	// create a Twilio client
	$client = new Client($TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN);

	// place the calls
	$error = '';
	foreach ($numbers as $number) {
        try {
			// create the call		
			$call = $client->calls->create($number, $from,
				array(
					"url" => $url,
					"method" => "POST",
					//"statusCallbackMethod" => "POST",
					//"statusCallback" => "https://www.myapp.com/events",
					//"statusCallbackEvent" => array(
					//	"initiated", "ringing", "answered", "completed"
					//)
				)
			);
        } catch (Exception $e) {
            $error .= $number . ": " . $e->getMessage() . "\n";
        }
	}

	return true;
}

/**
* Get an array of active calls
*
* Calls with the statuses queued, ringing or in-progress are considered
* active.
* 
* @param string $from
*   The phone number that must be the from number in the calls
* @param string $to
*   The phone number that must be the to number in the calls
* @param array &$calls
*   Filled with the array of active calls
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True - errors reported in $error parameter.
*/

function sms_getActiveCalls($from, $to, &$calls, &$error)
{
	global $TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN;
	
	// create a Twilio client
	$client = new Client($TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN);

	// retrieve the calls
	$error = '';
	$calls = array();
	
	$statuses = array("queued", "ringing", "in-progress");
	foreach ($statuses as $status) {
		// for each status type, build an array that requests a from or to number, or both
		$read_array = array("status" => $status);
		if ($from) {
			$read_array['from'] = $from;
		}
		if ($to) {
			$read_array['to'] = $to;
		}
		_sms_getCallInfo($read_array, $client, $calls, $error);
	}
	
	return true;
}

/**
* Helper function to get the search for specific call information
*
* ...
* 
* @param array $read_array
*   The parameters to search for
* @param object &$client
*   The Twilio client to search with
* @param array &$calls
*   The array of active calls to be added to
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred
*/

function _sms_getCallInfo($read_array, &$client, &$calls, &$error)
{
	try {
		// load the calls
		$calls_from = $client->calls->read($read_array);
		
		// add each to the $calls array, formatting relevant information
		foreach ($calls_from as $call) {
			$calls[] = array("From" => $call->from,
				"To" => $call->to,
				"Status" => $call->status,
				"StartTime" => $call->startTime->format("Y-m-d H:i:s O"),
				"EndTime" => $call->endTime->format("Y-m-d H:i:s O"),
			);
		}
	} catch (Exception $e) {
		// catch errors
		$error .= $e->getMessage() . "\n";
		return false;
	}
	
	return true;
}

/**
* Gets the details of a particular queue
*
* ...
* 
* @param string $name
*   The queue's to retrieve's "friendlyName"
* @param object &$queue
*   Set to the queue object
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred
*/

function sms_getQueueInfo($name, &$queue, &$error)
{
	global $TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN;
	
	// create a Twilio client
	$client = new Twilio\Rest\Client($TWILIO_ACCOUNT_SID, $TWILIO_AUTH_TOKEN);
	
	// loop over the list of queues and find the matching one
	$queue = '';
	foreach ($client->queues->read() as $queue_query) {
		if ($queue_query->friendlyName == $name) {
			// the name matches
			$queue = $queue_query;
			break;
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
		case 'UNSTOP':
		case 'ON':
		case 'ALERT':
		case 'ALERTS':
			// enable sending to this number, and respond.
			sms_updateNumber(true /* enable */, $from, $to, $response, $error);
			return true;
		case 'YES':
			// because we use this keyword for responding to broadcasts, only 
			// respond and stop regular processing if they are not already 
			// enabled
			$sql = "SELECT COUNT(*) FROM broadcast WHERE ".
				"phone = '".addslashes($from)."' AND status='active'";
			if (db_db_getone($sql, $enabled, $error) && $enabled) {
				// they are already active - don't treat this as an admin request
				return false;
			} else {
				// they are not active or not in the list - enable them
				sms_updateNumber(true /* enable */, $from, $to, $response, $error);
				return true;
			}
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

/**
* Mark a communication as responded to, or not
*
* Set to the current time if marked as responded, otherwise set to NULL.
* 
* @param int $id
*   The communication to mark
* @param bool $responded
*   True to mark as responded, false to mark as not responded
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_markCommunication($id, $responded, &$error)
{
	$sql = "UPDATE communications ".
		"SET responded=" . ($responded ? "NOW() " : "NULL ") .
		"WHERE id='".addslashes($id)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	return true;
}

/**
* Ensure that a phone number is in E.164 format
*
* Must begin with a +.  Convert 10 digit US/Canada/etc. numbers to this format.
* 
* @param string &$number
*   Phone number to normalize.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if valid.
*/

function sms_normalizePhoneNumber(&$number, &$error)
{
	$original_number = $number;
	$number = '';
	
	// remove everything but numbers and the plus sign as the first digit
	for ($i = 0; $i < strlen($original_number); $i++) {
		$ch = substr($original_number, $i, 1);
		if (($ch == '+' && $i == 0) ||
			($ch >= '0' && $ch <= '9')) {
			$number .= $ch;
		}
	}
	
	// must begin with a plus, or be at least ten digits
	if (substr($number, 0, 1) != '+') {
		if (strlen($number) < 10) {
			// invalid number
			$error = "{$original_number} is not a valid number.";
			return false;
		} else if (strlen($number) == 10) {
			$number = "+1{$number}";
		} else {
			$number = "+{$number}";
		}
	}
	
	return true;
}

/**
* Load the last broadcast text that requested a response
*
* ...
* 
* @param string &$broadcast_response
*   Set to the latest broadcast text that requested a response
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_getBroadcastResponse(&$broadcast_response, &$error)
{
	$sql = "SELECT * FROM communications WHERE phone_to='BROADCAST_RESPONSE' ".
		"ORDER BY communication_time DESC LIMIT 1";
	return db_db_getrow($sql, $broadcast_response, $error);
}

/**
* Add a phone number to a broadcast response list
*
* If added successfully, catches the user up on all the messages they missed.
* 
* @param array $broadcast_response
*   The broadcast response they are responding to
* @param string $from
*   The phone number to add
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_addToBroadcastResponse($broadcast_response, $from, &$error)
{
	global $BROADCAST_CALLER_ID;
	
	// look up the broadcast id for this number
	$sql = "SELECT id FROM broadcast WHERE phone='".addslashes($from)."' AND status='active'";
	if (!db_db_getone($sql, $broadcast_id, $error)) {
		return false;
	}
	
	// does the broadcast id exist? (are they subscribed?)
	if (!$broadcast_id) {
		// no, we shouldn't have gotten here
		return false;
	}
	
	// have they already sent yes for this broadcast?
	$sql = "SELECT COUNT(*) FROM broadcast_responses WHERE ".
		"communications_id='".addslashes($broadcast_response['id'])."' AND ".
		"broadcast_id='".addslashes($broadcast_id)."'";
	if (!db_db_getone($sql, $broadcast_response_id, $error)) {
		return false;
	}
	
	if ($broadcast_response_id) {
		// yes, they are already subscribed
		return true;
	}
	
	// no, add them to the list
	$sql = "INSERT INTO broadcast_responses SET ".
		"communications_id='".addslashes($broadcast_response['id'])."',".
		"broadcast_id='".addslashes($broadcast_id)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	// catch them up on the messages they missed, if any
	$sql = "SELECT * FROM communications WHERE phone_to='BROADCAST_RESPONSE_UPDATE' ".
		"AND communication_time > '".addslashes($broadcast_response['communication_time'])."' ".
		"ORDER BY communication_time";
	if (!db_db_query($sql, $messages, $error)) {
		return false;
	}
	
	if (count($messages)) {
		// there are messages, catch them up
		$update_message = "Catching you up on messages you missed: ";
		
		foreach ($messages as $message) {
			$message_timestamp = strtotime($message['communication_time']);
			// was this message sent today?
			if (date("Y-m-d") == substr($message['communication_time'], 0, 10)) {
				// yes, just provide the time it was sent
				$message_time = date("h:i a", $message_timestamp);
			} else {
				// no, provide the date and time
				$message_time = date("m/d/y h:i a", $message_timestamp);
			}
			
			$update_message .= $message_time . ": " . $message['body'] . ' ';
		}
		
		// send them the messages
		$numbers = array($from);
		if (!sms_send($numbers, $update_message, $error, $BROADCAST_CALLER_ID)) {
			return false;
		}
	}
	
	return true;
}

/**
* Load a language table entry by language key press
*
* ...
* 
* @param int $keypress
*   The language keypress to load
* @param array &$language
*   Set to the loaded language.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function sms_loadLanguageByKeypress($keypress, &$language, &$error)
{	
	// first, does the keypress exist?
	if ($keypress) {
		$sql = "SELECT COUNT(*) FROM languages WHERE keypress='". addslashes($keypress) . "'";
		if (!db_db_getone($sql, $exists, $error)) {
			return false;
		}
		
		if (!$exists) {
			$keypress = 1;  // default to the first language
		}
	} else {
		$keypress= 1;
	}
	
	// now load the record
	$sql = "SELECT * FROM languages WHERE keypress='". addslashes($keypress) . "'";
	if (!db_db_getrow($sql, $language, $error)) {
		return false;
	}
	
	return true;
}

/**
* Load a language table entry by language id
*
* ...
*
* @param int $id
*   The language id to load
* @param array &$language
*   Set to the loaded language.
* @param string &$error
*   An error if one occurred.
*
* @return bool
*   True unless an error occurred.
*/

function sms_loadLanguageById($id, &$language, &$error)
{
	// first, does the id exist?
	if ($id) {
		$sql = "SELECT COUNT(*) FROM languages WHERE id='". addslashes($id) . "'";
		if (!db_db_getone($sql, $exists, $error)) {
			return false;
		}

		if (!$exists) {
			$id = 1;  // default to the first language
		}
	} else {
		$id = 1;
	}

	// now load the record
	$sql = "SELECT * FROM languages WHERE id='". addslashes($id) . "'";
	if (!db_db_getrow($sql, $language, $error)) {
		return false;
	}

	return true;
}

/**
* Is a phone number blocked?
*
* ...
* 
* @param string $phone
*   The phone number to check
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if the phone number was blocked.
*/

function sms_isNumberBlocked($phone, &$error)
{
	$sql = "SELECT COUNT(*) FROM blocked_numbers WHERE phone='".addslashes($phone)."'";
	if (!db_db_getone($sql, $count, $error)) {
		return false;
	}
	
	return ($count > 0);
}

/**
* Based on the media string either says the string in a robot voice or plays the url in the string
*
* ...
* 
* @param string $gather
*   The twilio object to call say or play from
* @param string $string
*   A string of text which could be a URL which either makes a robot voice say the string or twilio play the URL 
* @param string $voice_code
*   The String for the twilio voice code which indicates which language the text is 
*
*/

function sms_playOrSay(&$gather, $string, $voice_code = null)
{
    $sub_string = substr($string, 0, 4);
    if ($sub_string == 'http') {
        $gather->play($string);
    } else {
        if (!$voice_code) $voice_code = 'en-US';
        $gather->say($string,
		array('voice' => 'alice', 'language' => $voice_code));
    }
}

?>
