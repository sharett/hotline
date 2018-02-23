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
$start = isset($_REQUEST['s']) ? (int)$_REQUEST['s'] : 0;
$ph = isset($_REQUEST['ph']) ? trim($_REQUEST['ph']) : '';
$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : '';
$text = isset($_REQUEST['text']) ? trim($_REQUEST['text']) : '';
$mark = isset($_REQUEST['mark']) ? (int)$_REQUEST['mark'] : 0;
$unmark = isset($_REQUEST['unmark']) ? (int)$_REQUEST['unmark'] : 0;

// Normalize the phone number
if ($ph) {
	$ph_valid = sms_normalizePhoneNumber($ph, $error);
}

// ensure that the "from" number is hotline or broadcast.  Default to first hotline.
if (!in_array($from, $BROADCAST_CALLER_IDS) && !array_key_exists($from, $HOTLINES)) {
	sms_getFirstHotline($from, $hotline, $error);
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
if (!empty($error)) {
?>
	      <div class="alert alert-danger" role="alert"><?php echo $error ?></div>
<?php
}

// any success message?
if (!empty($success)) {
?>
	      <div class="alert alert-success" role="alert"><?php echo $success ?></div>
<?php
}

// display the contact's information
if ($ph && $ph_valid) {
	// display the call & text options
?>
		  <h2 class="sub-header">Contact <?php echo $ph ?><?php if ($name) echo " ({$name})" ?></h2>
		  <form action="contact.php" method="GET" class="form-inline">
		    <input type="hidden" name="s" value="<?php echo $start ?>">
		    <input type="hidden" name="ph" value="<?php echo $ph ?>">
		    <div class="form-group">
			<label for="text-message">From:</label>
			<select class="form-control" name="from" onChange="this.form.submit()">
<?php
	displayPhoneOptions($from);
?>				
			</select>
 		   </div>
           <button class="btn btn-success" id="button-text">Go</button>
		  </form>
		  
		  <br />
          <form id="text-controls" action="contact.php?ph=<?php echo urlencode($ph) ?>&from=<?php echo urlencode($from) ?>"
                method="POST">
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
		  
		  <h3 class="sub-header">Log</h3>
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
	// form for exporting of data
	$export['phone'] = $ph;
	include 'communications_export.php';
} else {
	// no phone number provided - prompt for one
?>
		  <h2 class="sub-header">Contact</h2>
		  <form action="contact.php">
		   <div class="form-group">
			<label for="text-message">Call / text from</label>
			<select class="form-control" name="from">
<?php
	displayPhoneOptions($from);
?>
			</select>
 		   </div>
		   <div class="form-group">
			<label for="text-message">Phone number</label>
			<input type="text" class="form-control" name="ph" 
			       placeholder="<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? $hotline_number : reset($BROADCAST_CALLER_IDS) ?>"
			       value="<?php echo $ph ?>">
 		   </div>		  
		   <button class="btn btn-success" id="button-text">Lookup</button>
		  </form>
<?php
}

// display the footer
include 'footer.php';

/**
* Display the options for phone numbers inside a select tag
*
* Options are each hotline, and the broadcast number.
* 
* @param string $selected
*   The number that should be selected.
*/

function displayPhoneOptions($selected)
{
	global $HOTLINES, $BROADCAST_CALLER_IDS;
	
	foreach ($HOTLINES as $hotline_number => $hotline) {
?>
			 <option value="<?php echo $hotline_number ?>" 
				<?php if ($selected == $hotline_number) { echo "selected"; } ?>><?php echo $hotline_number ?> (<?php echo $hotline['name'] ?>)</option>
<?php
	}
	if (isset($BROADCAST_CALLER_IDS)) {
		foreach ($BROADCAST_CALLER_IDS as $broadcast_caller_id) {
?>
			 <option value="<?php echo $broadcast_caller_id ?>" 
				<?php if ($selected == $broadcast_caller_id) { echo "selected"; } ?>><?php echo $broadcast_caller_id ?> (Broadcast)</option>
<?php
		}
	}
}

?>
