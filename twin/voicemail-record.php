<?php
/**
* @file
* Record a voicemail
*
* Store the voicemail in the database, and send a text alerting volunteers
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

$response = new Twilio\Twiml();

// store the voicemail
$_REQUEST['status'] = 'voicemail';
$_REQUEST['Body'] = $_REQUEST['RecordingUrl'];
sms_storeCallData($_REQUEST, $error);

// URL parameters
$url = $_REQUEST['RecordingUrl'];
$duration = $_REQUEST['RecordingDuration'];
$from = $_REQUEST['From'];
$hotline_number = $_REQUEST['To'];
$language_id = $_REQUEST['language_id'];

// load language data
sms_loadLanguageById($language_id, $language, $error);

if (!$url) {
	// no voicemail!
	sms_playOrSay($response, 'An error occurred - your voicemail was not received. Goodbye.');
} else {
	// send an text alerting the volunteers of a voicemail
	if (!alertVolunteersOfVoicemail($from, $hotline_number, $url, $duration, $error)) {
		sms_playOrSay($response, 'An error occurred - your voicemail was not received. Goodbye.');
	} else {
		// voicemail was received successfully
		sms_parseLanguagePrompt($hotline_number, $language['voicemail_received'], $error);
		sms_playOrSay($response, $language['voicemail_received'], $language['twilio_code']);
	}
}	

// send the response
echo $response;

db_databaseDisconnect();


/**
* Send a text notifying volunteers of a voicemail received
*
* ...
* 
* @param string $from
*   The phone number the voicemail is from.
* @param string $hotline_number
*   The phone number of the hotline.
* @param string $url
*   The URL to listen to the voicemail.
* @param int $duration
*   The length, in seconds, of the voicemail.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if sent successfully.
*/

function alertVolunteersOfVoicemail($from, $hotline_number, $url, $duration, &$error)
{
	global $HOTLINES;
	
	// look up who is on duty who receives texts or answered alerts
	$receives = array('calls' => false, 'texts' => true, 'answered_alerts' => true);
	if (!sms_getActiveContacts($contacts, 0 /* no language restriction */, $receives, $error)) {
		return false;
	}
	
	if (count($contacts) == 0) {
		// no one on duty, nothing to send
		return true;
	}
	
	// format the numbers
	$numbers = array();
	foreach ($contacts as $contact) {
		$numbers[] = $contact['phone'];
	}
	
	// identify the caller
	if (sms_whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from .= " ({$contact_name})";
	}

	// compose the text
    $body = "{$HOTLINES[$hotline_number]['name']} hotline has received a {$duration} second message from {$from}. ".
		"Log in to the website to listen to this voicemail.";
		
	// send the texts
	if (!sms_send($numbers, $body, $error, $hotline_number)) {
		return false;
	}
	
	return true;
}

?>
