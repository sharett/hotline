<?php
/**
* @file
* Handle an incoming text message
*
* Look up who is available to be texted at a given date and time and sends
* texts to them.  If a volunteer, the text is sent regardless of availability
* and no response is given.
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// store the text
sms_storeCallData($_REQUEST, $error);

// the text data
$from = $_REQUEST['From'];
$to = $_REQUEST['To'];
$body = $_REQUEST['Body'];
$media = array();
if ((int)$_REQUEST['NumMedia']) {
	$count = (int)$_REQUEST['NumMedia'];
	for ($i = 0; $i < $count; $i++) {
		$media[$i] = array(
			"mime_type" => $_REQUEST['MediaContentType' . $i],
			"url" => $_REQUEST['MediaUrl' . $i]
		);
	}
}

// by default, send no response
$message = '';

// is this an administrative request?
if (sms_handleAdminText($from, $to, $body, $message, $error)) {
	// yes it was
	if ($error) {
		$message .= " Error: {$error}";
	}
} else {
	// no, process normally

    // look up who is on duty
	sms_getActiveContacts($contacts, 0 /* no language restriction */, true /* texting */, $error);

	// format the numbers
	$numbers = array();
	foreach ($contacts as $contact) {
		if ($contact['phone'] == $from) {
			// don't text the person who sent the text
			continue;
		}
		$numbers[] = $contact['phone'];
	}

	// identify the texter
	if (sms_whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from .= " ({$contact_name})";
	}

	// was anything sent?
	if ($from && $body) {
		// yes
		$forwarded = "Hotline text from {$from}: {$body}";

		// attempt to forward
		if (!sms_send($numbers, $forwarded, $error)) {
			$error = "Unable to forward your text.";
		}
	} else {
		$error = "Nothing was received.";
	}

	if ($error) {
		$message = "There was a problem: {$error}";
	} else {
		// have we received a text from this number recently?
		if (!hasTextedRecently($from, $error)) {
			// no, send an automated response
			$message = "Your message has been received.  Someone will respond shortly.";
		}
	}
}

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php
if (trim($message)) {
?>
  <Message><?php echo $message; ?></Message>
<?php
}
?>
</Response>
<?php

/**
* Have we received a text recently from this number?
*
* If we've received a text from this number (to the hotline) in the past
* week, return true.
* 
* @param string $from
*   The phone number to check
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if a text was recently received, false if not or if an error 
*   occurred.
*/

function hasTextedRecently($from, &$error)
{
	global $HOTLINE_CALLER_ID;
	
	$sql = "SELECT COUNT(*) FROM communications ".
		"WHERE phone_to='".addslashes($HOTLINE_CALLER_ID)."' AND ".
		" phone_from='".addslashes($from)."' AND ".
		" status='text' AND communication_time > DATE_SUB(NOW(), INTERVAL 7 DAY)";
	if (!db_db_getone($sql, $recent_texts, $error)) {
		return false;
	}
	
	if ($recent_texts > 0) {
		// at least one text in the last week
		return true;
	}
	
	return false;
}

?>
