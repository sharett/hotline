<?php
/**
* @file
* Write a record of an outgoing call to the database
*
* ...
* 
*/

require_once '../config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

pp_databaseConnect();

// store call info
$_REQUEST['From'] = "+14133204300";
$_REQUEST['Body'] = "(call from website)";
storeCallData($_REQUEST, $error);

pp_databaseDisconnect();

header('Content-Type: application/json');
echo json_encode(array(
    'error' => $error,
));
