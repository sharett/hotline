<?php
/**
* @file
* Handle an incoming voice call - part 2
*
* Look up who is available to be called at a given date and time and language, attempts
* to connect, and sends to voicemail if no answer.
* 
* TODO: Log errors, investigate problem where call falls over to "no one" available after hangup.
* 
*/

require_once '../config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

$DEFAULT_LANGUAGE = 1; // English

pp_databaseConnect();

// what language did they choose?
$language_id = (int)$_REQUEST['Digits'];
$sql = "SELECT id FROM languages WHERE id='". addslashes($language_id) . "'";
if (!pp_db_getrow($sql, $language, $error)) {
    // error, use default language
    $language_id = $DEFAULT_LANGUAGE;
}

if (!$language['id']) {
	// language does not exist, use default
	$language_id = $DEFAULT_LANGUAGE;
}

// who should we call given the current day, time and language?
getActiveContacts($contacts, $language_id, false /* not texting */, $error);

pp_databaseDisconnect();

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php
// is there anyone to dial?
if (count($contacts)) {
?>
 <Dial timeout="40" timeLimit="3600" callerId="<?php echo $HOTLINE_CALLER_ID ?>" action="call-ended.php">
<?php
	foreach ($contacts as $contact) {
?>
  <Number url="<?php echo $TWILIO_INTERFACE_WEBROOT ?>screen-call.php"><?php echo $contact['phone'] ?></Number>
<?php
	}
?>
 </Dial>
<?php
}
?>
 <Say voice="alice"><?php echo $HOTLINE_NO_ANSWER ?></Say>
 <Record timeout="5" maxLength="300" action="voicemail-record.php" />
 <Say voice="alice">An error occurred - your message was not received.</Say>
</Response>
<?php

?>
