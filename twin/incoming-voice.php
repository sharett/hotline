<?php
/**
* @file
* Handle an incoming voice call - part 1
*
* Welcome message, prompts for language, sends to incoming-voice-dial.php
* when a digit is pressed or after a 15 second timeout.
* 
*/

require_once '../config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

pp_databaseConnect();

// store call info
storeCallData($_REQUEST, $error);

// load the list of languages
if (!pp_db_query("SELECT * FROM languages", $languages, $error)) {
    // error!
}

pp_databaseDisconnect();

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
 <Gather action="incoming-voice-dial.php" numDigits="1" timeout="15">
  <Say voice="alice"><?php echo $HOTLINE_INTRO ?></Say>
<?php
foreach ($languages as $language) {
?>
  <Say voice="alice" language="<?php echo $language['twilio_code'] ?>"><?php echo $language['prompt'] ?></Say>
<?php
}
?>
 </Gather>
 <Redirect method="GET">
  incoming-voice-dial.php?Digits=TIMEOUT
 </Redirect>
</Response>
