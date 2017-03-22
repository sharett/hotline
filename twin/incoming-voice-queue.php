<?php
/**
* @file
* Tell Twilio what to say or play to the caller while they are waiting
*
* Plays a ringing phone sound
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$queue_time = $_REQUEST['QueueTime'];
$language_id = $_REQUEST['language_id'];

$response = new Twilio\Twiml();

$response->play($TWILIO_INTERFACE_WEBROOT . 'ringing_phone.mp3');

/*$response->say("You have been waiting for {$queue_time} seconds.",
	array('voice' => 'alice')
);
$response->pause(1);
*/

// exit if longer than 30 seconds
if ($queue_time > 40) {
	$response->leave();
}

echo $response;

db_databaseDisconnect();

?>
