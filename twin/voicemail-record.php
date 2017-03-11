<?php
/**
* @file
* Record a voicemail
*
* Store the voicemail in the database, and send a text alerting volunteers
* 
*/

require_once '../config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

// store the voicemail
$_REQUEST['status'] = 'voicemail';
$_REQUEST['Body'] = $_REQUEST['RecordingUrl'];
sms_storeCallData($_REQUEST, $error);

// URL parameters
$url = $_REQUEST['RecordingUrl'];
$duration = $_REQUEST['RecordingDuration'];
$from = $_REQUEST['From'];

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php
if (!$url) {
	?>
	<Say voice="alice">An error occurred - your voicemail was not received. Goodbye.</Say>
	<?php
} else {
	// send an text alerting the volunteers of a voicemail
	if (!alertVolunteersOfVoicemail($from, $url, $duration, $error)) {
		?>
		<Say voice="alice">An error occurred - your voicemail was not received. Goodbye.</Say>
		<?php
	} else {
		?>
		<Say voice="alice"><?php echo $HOTLINE_VOICEMAIL_RECEIVED ?></Say>
		<?php
	}
}	
?>
</Response>

<?php
db_databaseDisconnect();


/**
* Send a text notifying volunteers of a voicemail received
*
* ...
* 
* @param string $from
*   The phone number the voicemail is from.
* @param string $url
*   The URL to listen to the voicemail.
* @param int $duration
*   The length, in seconds, of the voicemail.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if sent successfully.
*/

function alertVolunteersOfVoicemail($from, $url, $duration, &$error)
{
	global $HOTLINE_NAME;
	
	// compost the text
    $body = "{$HOTLINE_NAME} hotline has received a {$duration} second message from {$from}. ".
		"Log in to the website to listen to this voicemail.";
		
	// look up who is on duty
	if (!sms_getActiveContacts($contacts, 0 /* no language restriction */, true /* texting */, $error)) {
		return false;
	}
	
	if (count($contacts) == 0) {
		// no one on duty, nothing to send
		return true;
	}
	
	// format the numbers
	$numbers = array();
	foreach ($contacts as $contact) {
		$numbers[] = $contact['phone'];
	}
	
	// identify the caller
	if (sms_whoIsCaller($contact_name, $from, $error) && $contact_name) {
		$from .= " ({$contact_name})";
	}

	// send the texts
	if (!sms_send($numbers, $body, $error)) {
		return false;
	}
	
	return true;
}

?>
