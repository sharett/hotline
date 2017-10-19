<?php
/**
* @file
* Blocks
*
* Display and edit blocked phone numbers
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

// *** ACTIONS ***

// add?
if ($action == 'add') {
	addBlockedNumber($phone, $error, $success);
// remove?
} else if ($action == 'remove') {
	removeBlockedNumber($id, $error, $success);
}

// load blocks
$sql = "SELECT * FROM blocked_numbers ORDER by phone";
db_db_query($sql, $blocks, $error);

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

?>
		<h2 class="sub-header">Hotline</h2>
   		  <ul class="nav nav-pills">
			<li role="presentation"><a href="hotline_staff.php">Staff</a></li>
			<li role="presentation" class="active"><a href="hotline_blocks.php">Blocks</a></li>
			<li role="presentation"><a href="hotline_languages.php">Languages</a></li>
			<li role="presentation"><a href="contact.php?ph=<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? $hotline_number : '' ?>&hide=1">Log</a></li>
		  </ul>
		  <br />
<?php

// Add, list and remove blocks
?>
		<h3 class="sub-header">Add</h3>
          <form id="text-controls" action="hotline_blocks.php" method="POST">
		   <input type="hidden" name="action" value="add">
		   <div class="form-group">
			<label for="add-phone">Number to block:</label>
			<input type="text" class="form-control" name="phone" id="add-phone" size="20" />
 		   </div>		 
		   <button class="btn btn-success" id="button-text">Add</button>
		  </form>
        <h3 class="sub-header">Blocks</h3>
        <p>
<?php 
foreach ($blocks as $block) {
	echo '<b>' . $block['phone'] . '</b> (blocked ' . date("m/d/y h:i a", strtotime($block['added'])) . ') ';
?>
		 <a href="hotline_blocks.php?action=remove&id=<?php echo $block['id'] ?>" 
		   onClick="return confirm('Are you sure you want to remove this block?');">
		 <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a><br />
<?php
}

?>
		</p>
<?php
include 'footer.php';

/**
* Add a blocked phone number to the database
*
* ...
* 
* @param string $phone
*   The number to block.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if added successfully.
*/

function addBlockedNumber($phone, &$error, &$message)
{
	$error = '';
	$message = '';
	
	if (!trim($phone)) {
		$error = "No number provided.";
		return false;
	}
	
	// make sure the number is in E164 format
	if (!sms_normalizePhoneNumber($phone, $error)) {
		return false;
	}

	// is this number in the database already?
	$sql = "SELECT COUNT(*) FROM blocked_numbers WHERE phone='".addslashes($phone)."'";
	if (!db_db_getone($sql, $number_exists, $error)) {
		return false;
	}
	if ($number_exists > 0) {
		$error = "{$number} is already blocked.";
		return false;
	}
		
	// add the number to the database
	$sql = "INSERT INTO blocked_numbers SET phone='".addslashes($phone)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	$message = "Added {$phone} to the block list.";
	return true;
}

/**
* Remove a phone number block from the database
*
* ...
* 
* @param int $id
*   Block id to remove.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True unless an error occurred.
*/

function removeBlockedNumber($id, &$error, &$message)
{
	$sql = "DELETE FROM blocked_numbers WHERE id='".addslashes($id)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	$message = "The record was removed.";
	return true;
}

?>
