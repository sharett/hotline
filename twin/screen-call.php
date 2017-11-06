<?php
/**
* @file
* Sent to the answering side of a hotline call - the volunteer must press 1 to
* accept the call.
*
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$language_id = (int)$_REQUEST['language_id'];
$from = $_REQUEST['From'];

// load the language data
sms_loadLanguageById($language_id, $language, $error);

$response = new Twilio\Twiml();

// wait for a digit to be pressed
$gather = $response->gather(array(
	'action' => 'handle-screen.php',
	'numDigits' => 1,
	'timeout' => 15,
	)
);

// say the hotline staff prompts
sms_playOrSay($gather, $HOTLINES[$from]['staff_prompt_1']);
sms_playOrSay($gather, $language['language']);
sms_playOrSay($gather, $HOTLINES[$from]['staff_prompt_2']);

// hang up if it times out
$response->hangup();

echo $response;

db_databaseDisconnect();
