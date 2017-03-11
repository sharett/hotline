<?php
/**
* @file
* Handle the end of a successful call.
*
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// save a record of the call ending
$_REQUEST['status'] = 'call ended';
sms_storeCallData($_REQUEST, $error);

db_databaseDisconnect();

?>
<?xml version="1.0" encoding="UTF-8"?>
<Response>
 <Say voice="alice"><?php echo $HOTLINE_GOODBYE ?></Say>
 <Hangup/>
</Response>
