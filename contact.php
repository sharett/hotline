<?php
/**
* @file
* Place a phone call, send a text and view the log for a particular phone number
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

$include_calling = true; // include twilio client js
include 'header.php';

// URL parameters
$start = (int)$_REQUEST['s'];
$ph = trim($_REQUEST['ph']);
$from = $_REQUEST['from'];
$text = trim($_REQUEST['text']);
$mark = (int)$_REQUEST['mark'];
$unmark = (int)$_REQUEST['unmark'];
$hide = (int)$_REQUEST['hide'];  // if true, hide the text & call options

// Normalize the phone number
if ($ph) {
	$ph_valid = sms_normalizePhoneNumber($ph, $error);
}

// if the phone number is the broadcast number, make that the from number
if ($ph == $BROADCAST_CALLER_ID) {
	$from = $BROADCAST_CALLER_ID;
}

// ensure that the "from" number is hotline or broadcast.  Default to hotline.
if ($from != $BROADCAST_CALLER_ID) {
	$from = $HOTLINE_CALLER_ID;
}

// Settings
$page = 50; // show per page

// Mark an item as responded, or not responded
if ($mark) {
	$sql = "UPDATE communications SET responded=NOW() WHERE id='".addslashes($mark)."'";
	if (!db_db_command($sql, $error)) {
		echo $error;
	}
}
if ($unmark) {
	$sql = "UPDATE communications SET responded=NULL WHERE id='".addslashes($unmark)."'";
	if (!db_db_command($sql, $error)) {
		echo $error;
	}
}

// send a text message?
if ($ph && $text) {
	$numbers = array($ph);
	if (sms_send($numbers, $text, $error, $from)) {
		// store the text
		$data = array(
			'From' => $from,
			'To' => $ph,
			'Body' => $text,
			'MessageSid' => 'text'
		);
		sms_storeCallData($data, $error);

		$success = "The text was sent.";
		$text = '';
	}
}

// look up the contact's name
sms_whoIsCaller($name, $ph, $error);

// any error message?
if ($error) {
?>
	      <div class="alert alert-danger" role="alert"><?php echo $error ?></div>
<?php
}

// any success message?
if ($success) {
?>
	      <div class="alert alert-success" role="alert"><?php echo $success ?></div>
<?php
}

// display the contact's information
if ($ph && $ph_valid) {
	if (!$hide) {
		// display the call & text options
?>
		  <h2 class="sub-header">Contact <?php echo $ph ?><?php if ($name) echo " ({$name})" ?></h2>
          <form id="text-controls" action="contact.php?ph=<?php echo urlencode($ph) ?>&from=<?php echo urlencode($from) ?>"
                method="POST">
           <p>(from <?php echo $from ?>)</p>
		   <div class="form-group">
			<label for="text-message">Send a text message</label>
			<input type="text" class="form-control" name="text"
			       placeholder="Text message" value="<?php echo $text ?>">
 		   </div>		  
		   <button class="btn btn-success" id="button-text">Text</button>
		  </form>
          
          <br />
          <form id="call-controls" style="display: none;" onsubmit="return false;">
		   <input type="hidden" id="phone-number" value="<?php echo $ph ?>">
		   <input type="hidden" id="call-from" value="<?php echo $from ?>">
 		   <label>Place an in-browser call</label><br />
		   <button class="btn btn-success" id="button-call">Call</button>
		   <button class="btn btn-danger" id="button-hangup">Hangup</button>
		   <h5 id="log"></h5>
		  </form>
<?php
	}
	// display log
	if ($ph == $BROADCAST_CALLER_ID) {
?>
		  <h3 class="sub-header">Broadcast log (<?php echo $BROADCAST_CALLER_ID ?>)</h3>
<?php
	} else {
?>
          <h3 class="sub-header">Log</h3>
<?php
	}
?>
          <p>Click a phone number to view all communications with that number.  Click the response button or link to mark or 
          unmark an item as responded to.</p>
<?php
	// load all communications to or from this phone number
	$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
		"FROM communications ".
		"LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
		"LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
		"WHERE communications.phone_from = '".addslashes($ph)."' OR ".
		"      communications.phone_to = '".addslashes($ph)."' ".
		"ORDER BY communication_time DESC LIMIT ".addslashes($start).",{$page}";
	if (!db_db_query($sql, $comms, $error)) {
		echo $error;
	}

	// display the communications table
	include 'communications.php';
?>
<p>
<?php
// show the previous button if we are not at the beginning
if ($start > 0) {
?>
 <a class="btn btn-success" href="contact.php?ph=<?php echo urlencode($ph) ?>&s=<?php echo $start - $page ?>" role="button">&lt;&lt; Prev</a>
<?php
}
// show the next button if there are more to show
if (count($comms) >= $page) {
?>
 <a class="btn btn-success" href="contact.php?ph=<?php echo urlencode($ph) ?>&s=<?php echo $start + $page ?>" role="button">Next &gt;&gt;</a>
<?php
}
?>
</p>
<?php

} else {
	// no phone number provided - prompt for one
?>
		  <h2 class="sub-header">Contact</h2>
		  <form id="choose_number" action="contact.php">
		   <div class="form-group">
			<label for="text-message">Call/text from</label>
			<select class="form-control" name="from">
			 <option value="<?php echo $HOTLINE_CALLER_ID ?>" 
				<?php if ($from == $HOTLINE_CALLER_ID) { echo "selected"; } ?>>Hotline - <?php echo $HOTLINE_CALLER_ID ?></option>
			 <option value="<?php echo $BROADCAST_CALLER_ID ?>" 
				<?php if ($from == $BROADCAST_CALLER_ID) { echo "selected"; } ?>>Broadcast - <?php echo $BROADCAST_CALLER_ID ?></option>
			</select>
 		   </div>		  
		   <div class="form-group">
			<label for="text-message">Phone number</label>
			<input type="text" class="form-control" name="ph" 
			       placeholder="<?php echo $HOTLINE_CALLER_ID ?>"
			       value="<?php echo $ph ?>">
 		   </div>		  
		   <button class="btn btn-success" id="button-text">Lookup</button>
		  </form>
<?php
}

// display the footer
include 'footer.php';
?>
