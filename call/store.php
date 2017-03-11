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

// store call info
$_REQUEST['From'] = $HOTLINE_CALLER_ID;
$_REQUEST['Body'] = "(call from website)";
sms_storeCallData($_REQUEST, $error);

db_databaseDisconnect();

header('Content-Type: application/json');
echo json_encode(array(
    'error' => $error,
));
