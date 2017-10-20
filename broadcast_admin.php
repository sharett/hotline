<?php
/**
* @file
* Tools to import, remove and list numbers in the broadcast list.
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

// required to avoid output buffering problems when sending progress marks
// as texts are sent
header('Content-type: text/html; charset=utf-8');

include 'header.php';

// URL parameters
$action = $_REQUEST['action'];
$numbers = $_POST['numbers'];
$send_welcome = ($_POST['send_welcome'] == 'on');

// *** ACTIONS ***

// import?
if ($action == 'import') {
	importNumbers($numbers, $error, $success, $send_welcome);
// remove?
} else if ($action == 'remove') {
	removeNumbers($numbers, $error, $success);
// list?
} else if ($action == 'list') {
	loadNumbers($numbers_active, $numbers_disabled, $error);
}

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

?>
		  <h2 class="sub-header">Broadcast</h2>
		  <ul class="nav nav-pills">
			<li role="presentation"><a href="broadcast.php">Send</a></li>
			<li role="presentation"<?php if ($action != 'list') echo ' class="active"'?>><a href="broadcast_admin.php">Import &amp; Remove</a></li>
			<li role="presentation"<?php if ($action == 'list') echo ' class="active"'?>><a href="broadcast_admin.php?action=list">List</a></li>
			<li role="presentation"><a href="log.php?ph=<?php echo urlencode($BROADCAST_CALLER_ID) ?>">Log</a></li>
		  </ul>
<?php
// display the import/remove information unless a list is requested
if ($action != 'list') {
?>          
          <h3 class="sub-header">Import</h3>
          <form id="text-controls" action="broadcast_admin.php" method="POST">
		   <input type="hidden" name="action" value="import">
		   <div class="form-group">
			<label for="import-numbers">Import numbers, one per line, or comma separated</label>
			<textarea class="form-control" name="numbers" id="import-numbers" rows="3" cols="30"><?php echo $numbers ?></textarea>
 		   </div>		 
 		   <div class="checkbox">
			 <label>
			   <input type="checkbox" name="send_welcome" <?php if ($send_welcome) { echo 'checked'; } ?>> Send a welcome message to each number
			 </label>
		   </div> 
		   <button class="btn btn-success" id="button-text">Import</button>
		  </form>
          
          <h3 class="sub-header">Remove</h3>
          <form id="text-controls" action="broadcast_admin.php" method="POST">
		   <input type="hidden" name="action" value="remove">
		   <div class="form-group">
			<label for="remove-numbers">Numbers to remove, one per line, or comma separated</label>
			<textarea class="form-control" name="numbers" id="remove-numbers" rows="2" cols="30"><?php echo $numbers ?></textarea>
 		   </div>
		   <button class="btn btn-warning" id="button-text">Remove</button>
		  </form>
<?php
} else {
	// display the list of numbers
?>
          <h3 class="sub-header">Active</h3>
          <p><?php echo implode(', ', $numbers_active) ?></p>
          <h3 class="sub-header">Disabled</h3>
          <p><?php echo implode(', ', $numbers_disabled) ?></p>          
<?php
}

// display the footer
include 'footer.php';

/**
* Import a list of numbers into the database
*
* Each number is separated by a newline, or by commas.  Converts the numbers to E.164 format, and if
* valid, adds to the database, and send a welcome message.
* 
* @param array $numbers
*   List of phone numbers, one on each line, or comma separated.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
* @param bool $send_welcome = true
*   If true, sends a welcome message to each number added.
*   
* @return bool
*   True if any numbers were provided.
*/

function importNumbers($numbers, &$error, &$message, $send_welcome = true)
{
	global $BROADCAST_CALLER_ID, $BROADCAST_WELCOME, $BROADCAST_PROGRESS_MARK_EVERY; 
	
	$error = '';
	$message = '';
	
	// break apart the numbers into an array
	$numbers_lines = explode("\n", trim($numbers));
	$numbers = array();
	foreach ($numbers_lines as $numbers_line) {
		$numbers_each = explode(",", trim($numbers_line));
		$numbers = array_merge($numbers, $numbers_each);
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to import.";
		return false;
	}
	
	$success_numbers = array();
	foreach ($numbers as $number) {
		$number = trim($number);
		
		// make sure the number is in E164 format
		if (!sms_normalizePhoneNumber($number, $n_error)) {
			$error .= $n_error . "<br />\n";
			continue;
		}

		// is this number in the database already?
		$sql = "SELECT COUNT(*) FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!db_db_getone($sql, $number_exists, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		if ($number_exists > 0) {
			$error .= "{$number}: Already in the database.<br />\n";
			continue;
		}
		
		// add the number to the database
		$sql = "INSERT INTO broadcast SET phone='".addslashes($number)."', status='active'";
		if (!db_db_command($sql, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		
		// import successful
		$success_numbers[] = $number;
	}
	
	// send the welcome messages if set
	if ($send_welcome && $BROADCAST_WELCOME) {
		sms_send($success_numbers, $BROADCAST_WELCOME, $send_error, 
				 $BROADCAST_CALLER_ID, $BROADCAST_PROGRESS_MARK_EVERY);
		$error .= $send_error;
	}
	
	// report on the status of the import
	$error_count = count($numbers) - count($success_numbers);
	$message = "Imported {$success_count} numbers successfully. ";
	if ($error_count) {
		$message .= "{$error_count} numbers were invalid or already in the database.";
	}
	
	return true;
}

/**
* Remove a list of numbers from the database
*
* Each number is separated by a newline, or by commas.  Converts the numbers to E.164 format, and if
* valid, removes it from the database.
* 
* @param array $numbers
*   List of phone numbers, one on each line, or comma separated.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if any numbers were provided.
*/

function removeNumbers($numbers, &$error, &$message)
{
	$error = '';
	$message = '';
	
	// break apart the numbers into an array
	$numbers_lines = explode("\n", trim($numbers));
	$numbers = array();
	foreach ($numbers_lines as $numbers_line) {
		$numbers_each = explode(",", trim($numbers_line));
		$numbers = array_merge($numbers, $numbers_each);
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to remove.";
		return false;
	}
	
	$success_count = 0;
	foreach ($numbers as $number) {
		$number = trim($number);
		
		// make sure the number is in E164 format
		if (!sms_normalizePhoneNumber($number, $n_error)) {
			$error .= $n_error . "<br />\n";
			continue;
		}

		// is this number in the database?
		$sql = "SELECT id FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!db_db_getone($sql, $broadcast_id, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		if (!$broadcast_id) {
			$error .= "{$number}: Not found in the database.<br />\n";
			continue;
		}

		// remove the number from the database
		$sql = "DELETE FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!db_db_command($sql, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		
		// remove any entries in the broadcast_responses table
		$sql = "DELETE FROM broadcast_responses WHERE broadcast_id='".addslashes($broadcast_id)."'";
		if (!db_db_command($sql, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		
		// remove successful
		$success_count++;
	}
	
	// report on the status of the removal
	$error_count = count($numbers) - $success_count;
	$message = "Removed {$success_count} numbers successfully. ";
	if ($error_count) {
		$message .= "{$error_count} numbers were invalid or not in the database.";
	}
	
	return true;
}

/**
* Load lists of active and disabled numbers
*
* ...
* 
* @param array &$numbers_active
*   Set to the list of active phone numbers.
* @param array &$numbers_disabled
*   Set to the list of disabled phone numbers.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if valid.
*/

function loadNumbers(&$numbers_active, &$numbers_disabled, &$error)
{
	// load active numbers
	$sql = "SELECT phone FROM broadcast WHERE status='active' ORDER BY phone";
	if (!db_db_getcol($sql, $numbers_active, $error)) {
		return false;
	}

	// load disabled numbers
	$sql = "SELECT phone FROM broadcast WHERE status='disabled' ORDER BY phone";
	if (!db_db_getcol($sql, $numbers_disabled, $error)) {
		return false;
	}
	
	return true;
}

?>
