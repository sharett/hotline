<?php
/**
* @file
* Handle the call screening on the answering side.
*
* If the volunteer has pushed 1, connect them to the caller, otherwise hangup.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$user_pushed = (int)$_REQUEST['Digits'];
$to = $_REQUEST['To']; // the staff answering the call

$response = new Twilio\Twiml();

// did the user push 1 to accept?
if ($user_pushed == 1) {
	// yes, does the hotline queue still have someone in it?
	if (sms_getQueueInfo('hotline', $queue, $error) && $queue && $queue->currentSize > 0) {
		// yes, connect them
		
		// announce the connection
		$response->say($HOTLINE_CONNECTING_TO_CALLER, array('voice' => 'alice'));
		
		// connect them to the caller at the front of the queue and log it
		$dial = $response->dial();
		$dial->queue('hotline', 
			array('url' => $TWILIO_INTERFACE_WEBROOT . 'log-queue.php?connected_to='. urlencode($to))
		);
	} else {
		// did someone else answer?
		// TODO
		
		// no, let them know the caller hung up
		$response->say($HOTLINE_CALLER_HUNG_UP, array('voice' => 'alice'));
	}
} else {
	// no, say goodbye
    $response->say($HOTLINE_GOODBYE, array('voice' => 'alice'));
}
$response->hangup();

echo $response;

db_databaseDisconnect();

?>
