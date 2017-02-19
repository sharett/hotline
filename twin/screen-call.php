<?php
/**
* @file
* Sent to the answering side of a hotline call - the volunteer must press 1 to
* accept the call.
*
*/

require_once '../config.php';

header("content-type: text/xml");
?>
<?xml version="1.0" encoding="UTF-8"?>
<Response>
 <Gather action="handle-screen.php" numDigits="1" timeout="15">
  <Say voice="alice"><?php echo $HOTLINE_VOLUNTEER_PROMPT ?></Say>
 </Gather>
 <Hangup/>
</Response>
