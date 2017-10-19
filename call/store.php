<?php
/**
* @file
* Write a record of an outgoing call to the database
*
* ...
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$from = $_REQUEST['From'];

// ensure that the "from" number is hotline or broadcast.  Default to first hotline.
if ($from != $BROADCAST_CALLER_ID && !array_key_exists($from, $HOTLINES)) {
	sms_getFirstHotline($from, $hotline, $error);
}

// store call info
$_REQUEST['From'] = $from;
$_REQUEST['Body'] = "(call from website)";
sms_storeCallData($_REQUEST, $error);

db_databaseDisconnect();

header('Content-Type: application/json');
echo json_encode(array(
    'error' => $error,
));
