<?php
/**
* @file
* Process a signup from another server
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';
require_once $LIB_BASE . 'lib_map.php';

db_databaseConnect();

// URL parameters
$ph = isset($_REQUEST['ph']) ? trim($_REQUEST['ph']) : '';
$zipcode = isset($_REQUEST['zip']) ? trim($_REQUEST['zip']) : '';

// process the signup
if (!processSignup($ph, $zipcode, $error)) {
	header("Location: {$BROADCAST_SIGNUP_FAILURE_URL}?error=".urlencode($error));
} else {
	header("Location: {$BROADCAST_SIGNUP_SUCCESS_URL}");
}

db_databaseDisconnect();

/**
* Process a signup request
*
* Sends a confirmation text to the number provided.
* 
* @param string $ph
*   The phone number.
* @param string $zipcode
*   The optional zip code to limit notifications to.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if unless an error occurred.
*/

function processSignup($ph, $zipcode, &$error)
{
	global $BROADCAST_CALLER_ID, $BROADCAST_SIGNUP_CONFIRMATION;
	
	$error = '';
	
	// is there a phone number?
	if (empty(trim($ph))) {
		$error = "A phone number is required.";
		return false;
	}
	
	// valid?
	if (!sms_normalizePhoneNumber($ph, $error)) {
		return false;
	}
	
	// our own number?
	if ($ph == $BROADCAST_CALLER_ID) {
		$error = "That number is not allowed.";
		return false;
	}
	
	// is a zip code set?
	if (!empty($zipcode) && !sms_isZipcode($zipcode)) {
		$error = "Please provide a valid 5 digit zip code.";
		return false;
	}
	
	// is this phone number already in the database?
	$sql = "SELECT COUNT(*) FROM broadcast WHERE phone = '".addslashes($ph)."'";
	if (!db_db_getone($sql, $broadcast_count, $error)) {
		return false;
	}
	
	if ($broadcast_count != 0) {
		// already in database!
		$error = "This phone number is already signed up.";
		return false;
	}
	
	// add an entry
	$sql = "INSERT INTO broadcast SET status='disabled', phone='".addslashes($ph)."', ".
			"zipcode='".addslashes($zipcode)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	// send a confirmation text
	$numbers = array($ph);
	$text = $BROADCAST_SIGNUP_CONFIRMATION;
	if (!sms_send($numbers, $text, $error, $BROADCAST_CALLER_ID)) {
		return false;
	}
	
	// store the text
	$data = array(
		'From' => $BROADCAST_CALLER_ID,
		'To' => $ph,
		'Body' => $text,
		'MessageSid' => 'text'
	);
	sms_storeCallData($data, $error);

	return true;
}

?>
