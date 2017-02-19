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
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

pp_databaseConnect();

// store the text
storeCallData($_REQUEST, $error);

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

// is this an administrative request?
if (sms_handleAdminText($from, $to, $body, $message, $error)) {
	// yes it was
	if ($error) {
		$message .= " Error: {$error}";
	}
} else {
	// no, process normally

	// is the sender a volunteer?
	$is_volunteer = isVolunteer($from, $error);

	if ($is_volunteer) {
		// yes, text all other volunteers and don't respond
		$sql = "SELECT phone FROM contacts ".
			"LEFT JOIN call_times ON call_times.contact_id = contacts.id ".
			"WHERE call_times.id IS NOT NULL AND call_times.receive_texts = 'y'";
		pp_db_query($sql, $contacts, $error);
	} else {
		// no, look up who is on duty
		getActiveContacts($contacts, 0 /* no language restriction */, true /* texting */, $error);
	}

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
	if (whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from .= " ({$contact_name})";
	}

	// was anything sent?
	if ($from && $body) {
		// yes
		if ($is_volunteer) {
			$forwarded = "{$from}: {$body}";
		} else {
			$forwarded = "Hotline text from {$from}: {$body}";
		}
		// attempt to forward
		if (!send_sms($numbers, $forwarded, $error)) {
			$error = "Unable to forward your text.";
		}
	} else {
		$error = "Nothing was received.";
	}

	if ($error) {
		$message = "There was a problem: {$error}";
	} else if (!$is_volunteer) {
		$message = "Your message has been received.  Someone will respond shortly.";
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
* Does a phone number belong to a volunteer?
*
* ...
* 
* @param string $from
*   The phone number to check
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if the phone number belongs to a volunteer, false if not or if an
*   error occurred.
*/

function isVolunteer($from, &$error)
{
	$sql = "SELECT call_times.id AS call_time_id FROM `contacts` ".
		"LEFT JOIN call_times ON call_times.contact_id = contacts.id ".
		"WHERE contacts.phone = '".addslashes($from)."'";
	if (!pp_db_getrow($sql, $sender, $error)) {
		return false;
	}
	
	if ($sender['call_time_id']) {
		// at least one call time exists, so they are a volunteer
		return true;
	}
	
	return false;
}

?>
