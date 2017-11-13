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
$tags = $_POST['tags'];
$send_welcome = ($_POST['send_welcome'] == 'on');
$id = $_REQUEST['id'];
$tag = $_REQUEST['tag'];

// *** ACTIONS ***

// import?
if ($action == 'import') {
	importNumbers($numbers, $tags, $error, $success, $send_welcome);
// remove?
} else if ($action == 'remove') {
	removeNumbers($numbers, $error, $success);
// remove a tag?
} else if ($action == 'removetag') {
	removeTag($id, $tag, $error);
	$action = 'list';
}

// list?
if (substr($action, 0, 4) == 'list') {
	loadNumbers($numbers_active, $numbers_disabled, $numbers_by_tag, $error);
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
			<li role="presentation"<?php if (substr($action, 0, 4) != 'list') echo ' class="active"'?>><a href="broadcast_admin.php">Import &amp; Remove</a></li>
			<li role="presentation"<?php if (substr($action, 0, 4) == 'list') echo ' class="active"'?>><a href="broadcast_admin.php?action=list">List</a></li>
			<li role="presentation"><a href="log.php?ph=<?php echo urlencode($BROADCAST_CALLER_ID) ?>">Log</a></li>
		  </ul>
<?php
// display the import/remove information unless a list is requested
if (substr($action, 0, 4) != 'list') {
?>          
          <h3 class="sub-header">Import</h3>
          <form id="text-controls" action="broadcast_admin.php" method="POST">
		   <input type="hidden" name="action" value="import">
		   <div class="form-group">
			<label for="import-numbers">Import numbers, one per line, or comma separated</label>
			<textarea class="form-control" name="numbers" id="import-numbers" rows="3" cols="30"><?php echo $numbers ?></textarea>
 		   </div>
 		   <div class="form-group">
			<label for="add-tags">Add tags to these numbers</label>
			<input type="text" class="form-control" name="tags" id="add-tags" size="50" 
			       placeholder="actions, alerts" value="<?php echo addslashes($tags) ?>">
			<p class="help-block">Optional. Separate each tag with a comma.</p>
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
	// display the lists of numbers
	if ($action == 'list') {
?>
            <h3><a href="broadcast_admin.php?action=listtags" class="btn btn-success btn-sm" 
               role="button">Show tags</a></h3>
<?php
	} else {
		// tags are already shown
?>
            <h3><a href="broadcast_admin.php?action=list" class="btn btn-success btn-sm" 
               role="button">Hide tags</a></h3>
<?php
	}
?>
          <h3 class="sub-header">Active</h3> 
          <p>
<?php
		displayNumbers($numbers_active, ($action == 'listtags'), $error);
?>
		  </p>
          <h3 class="sub-header">Disabled</h3>
          <p>
<?php
		displayNumbers($numbers_disabled, ($action == 'listtags'), $error);
?>
		  </p>
          <h3 class="sub-header">Tags</h3><a name="tags"></a>
          <p class="help-block">Numbers with a line drawn through them are disabled.</p>
<?php
	foreach ($numbers_by_tag as $tag => $numbers) {
?>
          <h4>
		    <span class="label label-primary"><?php echo $tag ?></span><a name="<?php echo urlencode($tag) ?>"></a>
		    <a href="broadcast_admin.php?action=removetag&tag=<?php echo urlencode($tag) ?>#tags"
		       onClick="return confirm('Are you sure you want to remove this entire tag?');">
		      <span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span></a>
          </h4>
          <p>
<?php
		foreach ($numbers as $number) {
			if ($number['status'] == 'disabled') {
?>
			<s><?php echo $number['phone'] ?></s>
<?php
			} else {
?>
		    <?php echo $number['phone'] ?>
<?php
			}
?>
		    <a href="broadcast_admin.php?action=removetag&id=<?php echo $number['id'] ?>#<?php echo urlencode($tag) ?>"
		       onClick="return confirm('Are you sure you want to remove this tag from this number?');">
		      <span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span></a> &nbsp;
<?php		 
		}
?>
		  </p>
<?php
	}
}

// display the footer
include 'footer.php';

/**
* Import a list of numbers into the database
*
* Each number is separated by a newline, or by commas.  Converts the numbers to E.164 format, and if
* valid, adds to the database, and send a welcome message.
* 
* @param string $numbers
*   List of phone numbers, one on each line, or comma separated.
* @param string $tags
*   List of tags to add to the imported numbers, comma separated.
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

function importNumbers($numbers, $tags, &$error, &$message, $send_welcome = true)
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
	
	// break apart the tags into an array
	$tags_array = explode(",", trim(strtolower($tags)));
	$tags = array();
	foreach ($tags_array as $tag) {
		$tag = trim($tag);
		if ($tag) {
			$tags[] = $tag;
		}
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
			if (!addTagsToNumber($number, $tags, $add_error)) {
				$error .= "{$number}: {$add_error}<br />\n";
			}
			continue;
		}
		
		// add the number and tags to the database
		$sql = "INSERT INTO broadcast SET phone='".addslashes($number)."', status='active'";
		if (!db_db_command($sql, $db_error)) {
			$error .= "{$number}: {$db_error}<br />\n";
			continue;
		}		
		if (!addTagsToNumber($number, $tags, $add_error)) {
			$error .= "{$number}: {$add_error}<br />\n";
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
* Add tags to a broadcast number
*
* Create a broadcast_tag record for each one.
* 
* @param string $number
*   Broadcast number to add to.
* @param array $tags
*   Array of tags to add to the imported numbers.
* @param string &$error
*   Errors if any occurred.
*   
* @return bool
*   True unless an error occured.
*/

function addTagsToNumber($number, $tags, &$error)
{
	// get the broadcast id for this number
	$sql = "SELECT id FROM broadcast WHERE phone='".addslashes($number)."'";
	if (!db_db_getone($sql, $id, $error)) {
		return false;
	}
	
	foreach ($tags as $tag) {
		// does the tag already exist?
		$sql = "SELECT COUNT(*) FROM broadcast_tags WHERE ".
			"broadcast_id='".addslashes($id)."' AND ".
			"tag='".addslashes($tag)."'";
		if (!db_db_getone($sql, $count, $error)) {
			return false;
		}
		if ($count > 0) {
			continue;
		}
		
		// insert the new tag
		$sql = "INSERT INTO broadcast_tags SET ".
			"broadcast_id='".addslashes($id)."', ".
			"tag='".addslashes($tag)."'";
		if (!db_db_command($sql, $error)) {
			return false;
		}
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
		
		// remove tags associated with this number
		$sql = "DELETE FROM broadcast_tags WHERE broadcast_id='".addslashes($broadcast_id)."'";
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
* Remove a tag, entirely or for a specific number
*
* ...
* 
* @param int $id
*   Broadcast tag id to remove.
* @param string $tag
*   Tag to remove all broadcast tag records for.
* @param string &$error
*   Errors if any occurred.
*   
* @return bool
*   True unless an error occured.
*/

function removeTag($id, $tag, &$error)
{
	// was a specific broadcast tag specified?
	if ($id) {
		$sql = "DELETE FROM broadcast_tags WHERE id='".addslashes($id)."'";
		if (!db_db_command($sql, $error)) {
			return false;
		}
	}
	// was an entire tag specified?
	if ($tag) {
		$sql = "DELETE FROM broadcast_tags WHERE tag='".addslashes($tag)."'";
		if (!db_db_command($sql, $error)) {
			return false;
		}
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
* @param array &$numbers_by_tag
*   Set to the list of tags, with a subarray of numbers.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if valid.
*/

function loadNumbers(&$numbers_active, &$numbers_disabled, &$numbers_by_tag, &$error)
{
	// load active numbers
	$sql = "SELECT broadcast.phone, broadcast_tags.tag, broadcast_tags.id FROM broadcast ".
		"LEFT JOIN broadcast_tags ON broadcast.id = broadcast_tags.broadcast_id ".
		"WHERE status='active' ORDER BY phone";
	if (!db_db_query($sql, $numbers_active, $error)) {
		return false;
	}

	// load disabled numbers
	$sql = "SELECT broadcast.phone, broadcast_tags.tag, broadcast_tags.id FROM broadcast ".
		"LEFT JOIN broadcast_tags ON broadcast.id = broadcast_tags.broadcast_id ".
		"WHERE status='disabled' ORDER BY phone";
	if (!db_db_query($sql, $numbers_disabled, $error)) {
		return false;
	}
	
	// load by tags
	$sql = "SELECT DISTINCT tag FROM broadcast_tags ORDER BY tag";
	if (!db_db_getcol($sql, $tags, $error)) {
		return false;
	}
	$numbers_by_tag = array();
	foreach ($tags as $tag) {
		$sql = "SELECT broadcast_tags.id, broadcast.phone, broadcast.status FROM broadcast_tags ".
			"LEFT JOIN broadcast ON broadcast.id = broadcast_tags.broadcast_id ".
			"WHERE broadcast_tags.tag = '".addslashes($tag)."' ".
			"ORDER BY broadcast.phone";
		if (!db_db_query($sql, $numbers_by_tag[$tag], $error)) {
			return false;
		} 
	}
	
	return true;
}

/**
* Display a list of numbers, optionally displaying tags
*
* ...
* 
* @param array $numbers
*   Each number contains an array:
* 		'phone' => The phone number
* 		'tag' => The tag, if any
* @param bool $show_tags
*   If true, tags are displayed after each number.
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function displayNumbers($numbers, $show_tags, &$error)
{
	// show each phone number once only, and then list all tags associated if requested
	$previous_phone = '';
	foreach ($numbers as $number) {
		if ($previous_phone != $number['phone']) {
			if ($previous_phone != '') {
				echo ", ";
			}
?>
		    <?php echo $number['phone'] ?>
<?php
		}
		$previous_phone = $number['phone'];
		
		if ($show_tags) {
?>
		    <a href="#<?php echo urlencode($number['tag']) ?>"><span class="label label-primary"><?php echo $number['tag'] ?></span></a>
<?php			
		}
	}
	
	return true;
}

?>
