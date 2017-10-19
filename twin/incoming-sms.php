<?php
/**
* @file
* Handle an incoming text message
*
* All texts are stored, and administrative messages are intercepted and
* responded to as appropriate.
* 
* For hotline texts, looks up who is available to be texted now and sends
* texts to them.
* 
* For broadcast texts, just store the response in the database.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// store the text
sms_storeCallData($_REQUEST, $error);

// the text data
$from = $_REQUEST['From'];
$to = $_REQUEST['To'];
$body = $_REQUEST['Body'];
$media = array();
if ((int)$_REQUEST['NumMedia']) {
	$count = (int)$_REQUEST['NumMedia'];
	for ($i = 0; $i < $count; $i++) {
		$media[$i] = array(
			"mime_type" => $_REQUEST['MediaContentType' . $i],
			"url" => $_REQUEST['MediaUrl' . $i]
		);
	}
}

// by default, send no response
$message = '';

// is this an administrative request?
if (sms_handleAdminText($from, $to, $body, $message, $error)) {
	// yes it was
	if ($error) {
		$message .= " Error: {$error}";
	}
} else {
	// no, process normally

	// is this a broadcast text?
	if ($to == $BROADCAST_CALLER_ID) {
		processBroadcastText($from, $body, $message, $error);
	}
	
	// is this a hotline text?
	if (array_key_exists($to, $HOTLINES)) {
		processHotlineText($from, $to, $body, $media, $message, $error);
	}
}

$response = new Twilio\Twiml();

// is there a reply?
if (trim($message)) {
	$response->message($message);
}

echo $response;

db_databaseDisconnect();


/**
* Process a text received from the hotline
*
* Look up the active contacts that support texting and forward the message
* to them.
* 
* @param string $from
*   The sending phone number
* @param string $hotline_number
*   The hotline number receiving the text
* @param string $body
*   The body of the text
* @param array $media
*   The media received with this text
* @param string &$message
*   The message to respond with, if any
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless a fatal error occurred
*/

function processHotlineText($from, $hotline_number, $body, $media, &$message, &$error)
{
	global $HOTLINES;
	
	// clear variables
	$message = '';
	$error = '';
	$from_descriptive = $from;
	
    // look up who is on duty and receive texts
    $receives = array('calls' => false, 'texts' => true, 'answered_alerts' => false);
	sms_getActiveContacts($contacts, 0 /* no language restriction */, $receives, $error);

	// format the numbers
	$numbers = array();
	foreach ($contacts as $contact) {
		if ($contact['phone'] == $from) {
			// don't text the person who sent the text
			continue;
		}
		$numbers[] = $contact['phone'];
	}

	// identify the texter
	if (sms_whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from_descriptive = " ({$contact_name})";
	}

	// is this number blocked?
	if (sms_isNumberBlocked($from, $error)) {
		// number is blocked, don't forward it or reply
		return true;
	}

	// was media received? Let them know, but don't forward the media
	if (count($media)) {
		$body = trim($body . " [Media received]");
	}

	// was anything sent?
	if ($from && $body) {
		// yes
		$forwarded = "{$HOTLINE[$hotline_number]['name']} hotline text from {$from_descriptive}: {$body}";

		// attempt to forward
		if (!sms_send($numbers, $forwarded, $error, $hotline_number)) {
			$error = "Unable to forward your text.";
		}
	} else {
		$error = "Nothing was received.";
	}

	if ($error) {
		$message = "There was a problem: {$error}";
	} else {
		// have we received a text from this number recently?
		if (!hasTextedRecently($from, $hotline_number, $error)) {
			// no, send an automated response
			$message = "Your message has been received.  Someone will respond shortly.";
		}
	}
	
	return true;
}

/**
* Process a text received from the broadcast number
*
* If the text is 'yes', add the sender to the broadcast update list for the
* last broadcast that requested an update
* 
* @param string $from
*   The sending phone number
* @param string $body
*   The body of the text
* @param string &$message
*   The message to respond with, if any
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless a fatal error occurred
*/

function processBroadcastText($from, $body, &$message, &$error)
{
	// clear variables
	$message = '';
	$error = '';
	
	// did they send 'yes' or 'y'?
	$body_lower = trim(strtolower($body));
	if ($body_lower == 'yes' || $body_lower == 'y') {
		// load the latest broadcast that requested a response
		if (sms_getBroadcastResponse($broadcast_response, $error) && $broadcast_response) {
			// add them to the list and update them with messages they missed
			sms_addToBroadcastResponse($broadcast_response, $from, $error);
		}
	} else {
		// do nothing - it is added to the history for the coordinator to view
	}
	
	return true;
}

/**
* Have we received a text recently from this number?
*
* If we've received a text from this number (to a hotline) in the past
* week, return true.  Exclude the current text we are replying to.
* 
* @param string $from
*   The phone number to check
* @param string $hotline_number
*   The hotline number to check
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if a text was recently received, false if not or if an error 
*   occurred.
*/

function hasTextedRecently($from, $hotline_number, &$error)
{
	$sql = "SELECT COUNT(*) FROM communications ".
		"WHERE phone_to='".addslashes($hotline_number)."' AND ".
		" phone_from='".addslashes($from)."' AND status='text' AND ".
		" twilio_sid != '".addslashes($_REQUEST['MessageSid'])."' AND ".
		" communication_time > DATE_SUB(NOW(), INTERVAL 7 DAY)";
	if (!db_db_getone($sql, $recent_texts, $error)) {
		return false;
	}
	
	if ($recent_texts > 0) {
		// at least one text in the last week
		return true;
	}
	
	return false;
}

?>
