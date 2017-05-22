<?php
/**
* @file
* Handle an incoming voice call - part 1
*
* Welcome message, prompts for language, sends to incoming-voice-dial.php
* when a digit is pressed or after a 15 second timeout.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

$to = $_REQUEST['To'];

db_databaseConnect();

// store call info
sms_storeCallData($_REQUEST, $error);

// load the list of languages
if (!db_db_query("SELECT * FROM languages ORDER BY keypress", $languages, $error)) {
    // error!
}

$response = new Twilio\Twiml();
if ($BROADCAST_FORWARD_TO) {
	// forward the call to the specified number
	$response->dial($BROADCAST_FORWARD_TO);
} else if ($BROADCAST_VOICE_MESSAGE) {
	// play broadcast message and hangup
	sms_playOrSay($response, $BROADCAST_VOICE_MESSAGE);	
} else {
	// use hotline functionality
	// wait for a key to be pressed
	$gather = $response->gather(array(
		'action' => 'incoming-voice-dial.php',
		'numDigits' => 1,
		'timeout' => 15,
		)
	);

	// say the hotline intro
	sms_playOrSay($gather, $HOTLINE_INTRO);

	// and each of the language options
	foreach ($languages as $language) {
		sms_playOrSay($gather, $language['prompt'], $language['twilio_code']);
	}

	sms_playOrSay($gather, $HOTLINE_STRAIGHT_TO_VOICEMAIL);

	// and handle a timeout
	$response->redirect('incoming-voice-dial.php?Digits=TIMEOUT',
		array('method' => 'GET')
	);
}

echo $response;

db_databaseDisconnect();
