<?php
/**
* @file
* Handle an incoming voice call - part 1
*
* Welcome message, prompts for language, sends to incoming-voice-dial.php
* when a digit is pressed or after a 15 second timeout.
* 
* Calls to the broadcast number will play recorded messages in each
* language and hangup, if messages are set.  Otherwise, calls will be 
* routed to the hotline.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// store call info
sms_storeCallData($_REQUEST, $error);

// the call data
$to = $_REQUEST['To'];

// load the list of languages
$sql = "SELECT * FROM languages ORDER BY keypress";
if (!db_db_query($sql, $languages, $error)) {
    // error!
}

$response = new Twilio\Twiml();
// is this to a broadcast number, and is a message set?
if (in_array($to, $BROADCAST_CALLER_IDS) && count($BROADCAST_VOICE_MESSAGES) > 0) {
	// play broadcast messages in each language and hangup
	foreach ($BROADCAST_VOICE_MESSAGES as $language_code => $message) {
		sms_playOrSay($response, $message, $language_code);
	}	
} elseif (array_key_exists($to, $HOTLINES) || $BROADCAST_SEND_TO_HOTLINE) {
	// calling from a non-hotline number?
	if (!array_key_exists($to, $HOTLINES)) {
		// yes, switch to the appropriate hotline
		$to = $BROADCAST_SEND_TO_HOTLINE;
	}
	
	// use hotline functionality
	$hotline = $HOTLINES[$to];
	
	// wait for a key to be pressed
	$gather = $response->gather(array(
		'action' => 'incoming-voice-dial.php',
		'numDigits' => 1,
		'timeout' => 15,
		)
	);

	// say the hotline intro
	sms_playOrSay($gather, $hotline['intro']);

	// and each of the language options
	foreach ($languages as $language) {
		sms_parseLanguagePrompt($to, $language['prompt'], $error);
		sms_playOrSay($gather, $language['prompt'], $language['twilio_code']);
	}

	sms_playOrSay($gather, $hotline['voicemail']);

	// and handle a timeout
	$response->redirect('incoming-voice-dial.php?Digits=TIMEOUT',
		array('method' => 'GET')
	);
} else {
	// nothing defined, say goodbye
	sms_playOrSay($response, $HOTLINE_GOODBYE);
}

echo $response;

db_databaseDisconnect();
