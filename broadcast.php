<?php
/**
* @file
* Tools to send a mass text, and import, remove and list numbers.
* 
*/

require_once 'config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$action = $_REQUEST['action'];
$text = trim($_REQUEST['text']);
$numbers = $_POST['numbers'];

// *** ACTIONS ***

// send a text message?
if ($action == 'broadcast') {
	if (sendBroadcastText($text, $error, $success)) {
		$text = '';
	}
// import?
} else if ($action == 'import') {
	importNumbers($numbers, $error, $success);
// remove?
} else if ($action == 'remove') {
	removeNumbers($numbers, $error, $success);
// list?
} else if ($action == 'list') {
	loadNumbers($numbers_active, $numbers_disabled, $error);
}

// get the count of the active numbers
$sql = "SELECT COUNT(*) FROM broadcast WHERE status='active'";
pp_db_getone($sql, $broadcast_count, $error);

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

// display the broadcast information unless a list is requested
if ($action != 'list') {
?>
          <h2 class="sub-header">Broadcast</h2>
          <form id="text-controls" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="broadcast">
		   <div class="form-group">
			<label for="text-message">Send a broadcast text message to <?php echo $broadcast_count ?> numbers</label>
			<input type="text" class="form-control" name="text"
			       placeholder="Text message" value="<?php echo $text ?>">
 		   </div>		  
		   <button class="btn btn-success" id="button-text">Broadcast</button>
		  </form>
          
          <h3 class="sub-header">Import</h3>
          <form id="text-controls" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="import">
		   <div class="form-group">
			<label for="text-message">Import numbers, one per line, or comma separated</label>
			<textarea class="form-control" name="numbers" rows="3" cols="30"><?php echo $numbers ?></textarea>
 		   </div>		 
		   <button class="btn btn-success" id="button-text">Import</button>
		  </form>
          
          <h3 class="sub-header">Remove</h3>
          <form id="text-controls" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="remove">
		   <div class="form-group">
			<label for="text-message">Numbers to remove, one per line, or comma separated</label>
			<textarea class="form-control" name="numbers" rows="2" cols="30"><?php echo $numbers ?></textarea>
 		   </div>		 
		   <button class="btn btn-warning" id="button-text">Remove</button>
		  </form>
		  
		  <h2 class="sub-header">List</h2>
		  <form id="text-controls" action="broadcast.php" method="GET">
		   <input type="hidden" name="action" value="list">
		   <button class="btn btn-success" id="button-text">Show</button>
		  </form>
<?php
} else {
	// display the list of numbers
?>
          <h2 class="sub-header">List</h2>
          <form id="text-controls" action="broadcast.php" method="GET">
		   <input type="hidden" name="action" value="">
		   <button class="btn btn-success" id="button-text">Hide</button>
		  </form>
          <h3 class="sub-header">Active</h3>
          <p><?php echo implode(', ', $numbers_active) ?></p>
          <h3 class="sub-header">Disabled</h3>
          <p><?php echo implode(', ', $numbers_disabled) ?></p>          
<?php
}

// display the footer
include 'footer.php';

/**
* Send a broadcast text.
*
* Sends a text to all active numbers in the broadcast list.
* 
* @param string $text
*   The text to send.
* @param string &$error
*   An error if one occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if sent.
*/

function sendBroadcastText($text, &$error, &$message)
{
	global $BROADCAST_CALLER_ID;
	
	$error = '';
	$message = '';

	$text = trim($text);
	if (!$text) {
		$error = "No text was provided.";
		return false;
	}
	
	// load the broadcast numbers
	$sql = "SELECT phone FROM broadcast WHERE status='active'";
	if (!pp_db_getcol($sql, $numbers, $error)) {
		return false;
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to send to.";
		return false;
	}
	
	// send the texts
	if (!send_sms($numbers, $text, $error, $BROADCAST_CALLER_ID)) {
		return false;
	}
	
	// store the text
	$data = array(
		'From' => $BROADCAST_CALLER_ID,
		'To' => 'BROADCAST',
		'Body' => $text,
		'MessageSid' => 'text'
	);
	storeCallData($data, $error);

	$message = "Text sent to " . count($numbers) . " numbers.";
	return true;
}

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
*   
* @return bool
*   True if any numbers were provided.
*/

function importNumbers($numbers, &$error, &$message)
{
	global $BROADCAST_CALLER_ID, $BROADCAST_WELCOME; 
	
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
	
	$success_count = 0;
	foreach ($numbers as $number) {
		$number = trim($number);
		
		// make sure the number is in E164 format
		if (!normalizePhoneNumber($number, $n_error)) {
			$error .= $n_error . "<br />\n";
			continue;
		}

		// is this number in the database already?
		$sql = "SELECT COUNT(*) FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!pp_db_getone($sql, $number_exists, $error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		if ($number_exists > 0) {
			$error .= "{$number}: Already in the database.<br />\n";
			continue;
		}
		
		// add the number to the database
		$sql = "INSERT INTO broadcast SET phone='".addslashes($number)."', status='active'";
		if (!pp_db_command($sql, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		
		// send a welcome message if set
		if ($BROADCAST_WELCOME) {
			$welcome_numbers = array($number);
			if (!send_sms($welcome_numbers, $BROADCAST_WELCOME, $error, $BROADCAST_CALLER_ID)) {
				$error .= "{$number}: Failed to send welcome message.<br />\n";
				continue;
			}
		}
		
		// import successful
		$success_count++;
	}
	
	// report on the status of the import
	$error_count = count($numbers) - $success_count;
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
		if (!normalizePhoneNumber($number, $n_error)) {
			$error .= $n_error . "<br />\n";
			continue;
		}

		// is this number in the database?
		$sql = "SELECT COUNT(*) FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!pp_db_getone($sql, $number_exists, $error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}
		if ($number_exists < 1) {
			$error .= "{$number}: Not found in the database.<br />\n";
			continue;
		}

		// remove the number from the database
		$sql = "DELETE FROM broadcast WHERE phone='".addslashes($number)."'";
		if (!pp_db_command($sql, $db_error)) {
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
* Ensure that a phone number is in E.164 format
*
* Must begin with a +.  Convert 10 digit US/Canada/etc. numbers to this format.
* 
* @param string &$number
*   Phone number to normalize.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if valid.
*/

function loadNumbers(&$numbers_active, &$numbers_disabled, &$error)
{
	// load active numbers
	$sql = "SELECT phone FROM broadcast WHERE status='active'";
	if (!pp_db_getcol($sql, $numbers_active, $error)) {
		return false;
	}

	// load disabled numbers
	$sql = "SELECT phone FROM broadcast WHERE status='disabled'";
	if (!pp_db_getcol($sql, $numbers_disabled, $error)) {
		return false;
	}
	
	return true;
}

/**
* Ensure that a phone number is in E.164 format
*
* Must begin with a +.  Convert 10 digit US/Canada/etc. numbers to this format.
* 
* @param string &$number
*   Phone number to normalize.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if valid.
*/

function normalizePhoneNumber(&$number, &$error)
{
	// remove everything but numbers and the plus sign as the first digit
	$new_number = '';
	for ($i = 0; $i < strlen($number); $i++) {
		$ch = substr($number, $i, 1);
		if (($ch == '+' && $i == 0) ||
			($ch >= '0' && $ch <= '9')) {
			$new_number .= $ch;
		}
	}
	$number = $new_number;
	
	// must begin with a plus, or be at least ten digits
	if (substr($number, 0, 1) != '+') {
		if (strlen($number) < 10) {
			// invalid number
			$error = "{$number} is not valid.";
			return false;
		} else if (strlen($number) == 10) {
			$number = "+1{$number}";
		} else {
			$number = "+{$number}";
		}
	}
	
	return true;
}

?>
