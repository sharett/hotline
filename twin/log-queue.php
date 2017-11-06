<?php
/**
* @file
* Log the bridging of a caller to staff
*
* Send an alert to those who receive call answered alerts.
*  
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// URL parameters
$connected_to = $_REQUEST['connected_to'];

// record this call as answered, and as connected to the staff who took the call
$_REQUEST['status'] = 'call answered';
$hotline_number = $_REQUEST['To'];
$_REQUEST['To'] = $connected_to;
sms_storeCallData($_REQUEST, $error);

// send a text to those who receive answer alerts
if (!alertOfAnsweredCall($_REQUEST['From'], $connected_to, $hotline_number, $error)) {
	db_error($error);
}

// return an empty response
$response = new Twilio\Twiml();
echo $response;

db_databaseDisconnect();

/**
* Send a text notifying contacts of an answered call
*
* ...
* 
* @param string $from
*   The phone number of the caller.
* @param string $to
*   The phone number of the answerer.
* @param string $hotline_number
*   The hotline number for this hotline.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if sent successfully.
*/

function alertOfAnsweredCall($from, $to, $hotline_number, &$error)
{
	global $HOTLINES;
	
	// look up who is on duty who receives call answer alerts
	$receives = array('calls' => false, 'texts' => false, 'answered_alerts' => true);
	if (!sms_getActiveContacts($contacts, 0 /* no language restriction */, $receives, $error)) {
		return false;
	}
	
	if (count($contacts) == 0) {
		// no one on duty, nothing to send
		return true;
	}
	
	// format the numbers
	$numbers = array();
	foreach ($contacts as $contact) {
		if ($contact['phone'] == $to) {
			// don't send an alert to the person who just answered
			continue;
		}
		$numbers[] = $contact['phone'];
	}
	
	// remove duplicates
	$numbers = array_unique($numbers);
	
	// identify the caller
	if (sms_whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from .= " ({$contact_name})";
	}
	
	// identify the answerer
	if (sms_whoIsCaller($contact_name, $to, $error) && $contact_name) {
		$to .= " ({$contact_name})";
	}

	// compose the text
    $body = "{$HOTLINES[$hotline_number]['name']} hotline call from {$from} was answered by {$to}.";
		
	// send the texts
	if (!sms_send($numbers, $body, $error, $hotline_number)) {
		return false;
	}
	
	return true;
}

?>
