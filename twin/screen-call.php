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

// load the language data
sms_loadLanguage($language_id, $language, $error);

$response = new Twilio\Twiml();

// wait for a digit to be pressed
$gather = $response->gather(array(
	'action' => 'handle-screen.php',
	'numDigits' => 1,
	'timeout' => 15,
	)
);

// say the hotline staff prompt
$gather->say($HOTLINE_STAFF_PROMPT_1 . $language['language'] . $HOTLINE_STAFF_PROMPT_2,
	array('voice' => 'alice')
);

// hang up if it times out
$response->hangup();

echo $response;

db_databaseDisconnect();
