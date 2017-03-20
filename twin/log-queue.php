<?php
/**
* @file
* Log the bridging of a caller to staff
*
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$connected_to = $_REQUEST['connected_to'];

// record this call as answered, and as connected to the staff who took the call
$_REQUEST['status'] = 'call answered';
$_REQUEST['To'] = $connected_to;
sms_storeCallData($_REQUEST, $error);

// return an empty response
$response = new Twilio\Twiml();
echo $response;

db_databaseDisconnect();

?>
