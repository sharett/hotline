<?php
/**
* @file
* Handle the call screening on the answering side.
*
* If the volunteer has pushed 1, connect them to the caller, otherwise hangup.
* 
*/

require_once '../config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

pp_databaseConnect();

$response = new Twilio\Twiml();

$user_pushed = (int)$_REQUEST['Digits'];
if ($user_pushed == 1) {
    $response->say($HOTLINE_CONNECTING_TO_CALLER, array('voice' => 'alice'));
    $_REQUEST['status'] = 'call answered';
    storeCallData($_REQUEST, $error);
} else {
    $response->say($HOTLINE_GOODBYE, array('voice' => 'alice'));
    $response->hangup();
}

print $response;

pp_databaseDisconnect();


?>
