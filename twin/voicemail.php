<?php
/**
* @file
* Announce and start the voicemail process
*
* 
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

$response = new Twilio\Twiml();

// URL parameters
$language_id = $_REQUEST['language_id'];

// load language data
sms_loadLanguageById($language_id, $language, $error);

// no one available to answer
if ($language_id != 0) {
    sms_playOrSay($response, $language['voicemail'], $language['twilio_code']);
}

// record for up to 5 minutes
$response->record(
	array('timeout' => 5,
		'maxLength' => 300,
		'action' => 'voicemail-record.php?language_id=' . $language_id
	)
);

// if we reach here, then the recording did not succeed
sms_playOrSay($response, 'An error occurred - your message was not received.');

echo $response;

db_databaseDisconnect();
