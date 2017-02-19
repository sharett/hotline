<?php
/**
* @file
* Handle the end of a successful call.
*
*/

require_once '../config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

pp_databaseConnect();

// save a record of the call ending
$_REQUEST['status'] = 'call ended';
storeCallData($_REQUEST, $error);

pp_databaseDisconnect();

?>
<?xml version="1.0" encoding="UTF-8"?>
<Response>
 <Say voice="alice"><?php echo $HOTLINE_GOODBYE ?></Say>
 <Hangup/>
</Response>
