<?php
/**
* @file
* Staff
*
* Display and edit hotline staff on duty now, and all staff
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$action = $_REQUEST['action'];
$staff = $_POST['staff'];
$id = $_REQUEST['id'];

// Authorized user?
$authorized = empty($HOTLINE_AUTHORIZED_USERS) || 
	in_array($_SERVER['PHP_AUTH_USER'], $HOTLINE_AUTHORIZED_USERS);
if (!$authorized) {
	// no
	$error = "You are not authorized to update staff information.";
}

// *** ACTIONS ***

// quick add?
if ($action == 'add' && $authorized) {
	addStaff($staff, $error, $success);
// remove staff?
} else if ($action == 'removestaff' && $authorized) {
	removeStaff($id, $error, $success);
// remove call time?
} else if ($action == 'removecalltime' && $authorized) {
	removeCallTime($id, $error, $success);
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
		<h2 class="sub-header">Hotline</h2>
   		  <ul class="nav nav-pills">
			<li role="presentation" class="active"><a href="hotline_staff.php">Staff</a></li>
			<li role="presentation"><a href="hotline_blocks.php">Blocks</a></li>
			<li role="presentation"><a href="hotline_languages.php">Languages</a></li>
			<li role="presentation"><a href="contact.php?ph=<?php echo $HOTLINE_CALLER_ID ?>&hide=1">Log</a></li>
		  </ul>
		  <br />
<?php

// Active calls from and to the hotline
sms_getActiveCalls($HOTLINE_CALLER_ID, '', $calls_from, $error);
sms_getActiveCalls('', $HOTLINE_CALLER_ID, $calls_to, $error);
$calls = array_merge($calls_from, $calls_to);

if (count($calls)) {
?>          
        <h3 class="sub-header">Active calls</h3>
		  <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>from</th>
                  <th>to</th>
                  <th>status</th>
                  <th>timing</th>
                </tr>
              </thead>
              <tbody>
<?php
	foreach ($calls as $call) {
		$duration = strtotime($call['EndTime']) - strtotime($call['StartTime']);
?>
                <tr>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($call['From']) . '">' . $call['From'] . '</a>';
                  ?></td>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($call['To']) . '">' . $call['To'] . '</a>';
                  ?></td>
                  <td><?php echo $call['Status']?></td>
                  <td><?php echo date("m/d/y h:i a", strtotime($call['StartTime'])) . 
								 ' (' . $duration . ' seconds)' ?></td>
                </tr>
<?php
	}
?>
              </tbody>
            </table>
          </div>
<?php
}

// On duty now
?>
        <h3 class="sub-header">On duty now</h3>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>name</th>
                  <th>phone</th>
                </tr>
              </thead>
              <tbody>
<?php
sms_getActiveContacts($contacts, 0 /* any language */, false /*texting doesn't matter*/, $error);
foreach ($contacts as $contact) {
?>
                <tr>
                  <td><?php echo $contact['contact_name']?></td>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($contact['phone']) . '">' . $contact['phone'] . '</a>';
                  ?></td>
                </tr>
<?php
}
?>
              </tbody>
            </table>
          </div>

<?php

// All hotline staff
if (!db_db_query("SELECT * FROM contacts ORDER BY contact_name", $contacts, $error)) {
    echo $error;
}

?>
          <h3 class="sub-header">Staff</h3>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>name</th>
                  <th>phone</th>
                  <th>call times</th>
                </tr>
              </thead>
              <tbody>
<?php
foreach ($contacts as $contact) {
    $sql = "SELECT call_times.*,languages.language FROM call_times ".
	"LEFT JOIN languages ON languages.id = call_times.language_id ".
        "WHERE contact_id='{$contact['id']}'";
    if (!db_db_query($sql, $call_times, $error)) {
        echo $error;
    }
?>
                <tr>
                  <td><?php echo $contact['contact_name']?></td>
                  <td><?php 
                  echo '<a href="contact.php?ph=' . urlencode($contact['phone']) . '">' . $contact['phone'] . '</a>';
                  ?></td>
                  <td><?php
    // allow removal of staff entry if all call time records are removed
    if (count($call_times) == 0) {
?>
		<a href="hotline_staff.php?action=removestaff&id=<?php echo $contact['id'] ?>" 
		   onClick="return confirm('Are you sure you want to remove this staff entry?');">
		 <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
<?php
	} else {
		// display each call_times records, crossed out if disabled
		foreach ($call_times as $call_time) {
			if ($call_time['enabled'] == 'n') {
				echo "<s>";
			}
			echo "day: {$call_time['day']}; time: ".
				date("h:i a", strtotime($call_time['earliest'])) . " to ".
				date("h:i a", strtotime($call_time['latest'])) .", {$call_time['language']}, ";
			if ($call_time['receive_texts'] == 'y') {
				echo "texts";
			} else {
				echo "no texts";
			}
			if ($call_time['enabled'] == 'n') {
				echo "</s>";
			}
?>
		<a href="hotline_staff.php?action=removecalltime&id=<?php echo $call_time['id'] ?>" 
		   onClick="return confirm('Are you sure you want to remove this record?');">
		 <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
<?php
			echo "<br />\n";
		}
	}
?>

                  </td>
                </tr>
<?php
}
?>
              </tbody>
            </table>
          </div>
          <form id="text-controls" action="hotline_staff.php" method="POST">
		   <input type="hidden" name="action" value="add">
		   <div class="form-group">
			<label for="text-message">Add staff</label>
			<textarea class="form-control" name="staff" rows="3" cols="30"></textarea>
			<p class="help-block">
			  <b>Format:</b> name,phone number,day,earliest time,latest time,language digit,texts (0 or 1).
			  Only name and phone number required.
			</p>
 		   </div>		 
		   <button class="btn btn-success" id="button-text">Add</button>
		  </form>
<?php
include 'footer.php';

/**
* Add staff to the database
*
* Each entry is separated by a newline.  Format: 
*    name,phone number,day,earliest time,latest time,language id,texts (0 or 1).
* 
* @param array $staff
*   List of staff to add, one on each line.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if any staff were provided.
*/

function addStaff($staff, &$error, &$message)
{
	$error = '';
	$message = '';
	
	// break apart the staff into an array
	$staff_lines = explode("\n", trim($staff));
	if (count($staff_lines) == 0) {
		$error = "No staff to import.";
		return false;
	}
	
	// iterate through each staff entry
	$success_count = 0;
	foreach ($staff_lines as $staff_line) {
		if (!trim($staff_line)) {
			continue;
		}
		
		$staff_array = explode(",", trim($staff_line));
		
		// 0 = name
		// 1 = phone number
		// 2 = day
		// 3 = earliest time
		// 4 = latest time
		// 5 = language digit
		// 6 = texts (0 or 1).
		
		// make sure the number is in E164 format
		if (!sms_normalizePhoneNumber($staff_array[1], $n_error)) {
			$error .= "{$staff_line}: " . $n_error . "<br />\n";
			continue;
		}
		
		// name?
		if (!trim($staff_array[0])) {
			$error .= "{$staff_line}: No name provided.<br />\n";
			continue;
		}
		
		// is this number in the database already?
		$sql = "SELECT * FROM contacts WHERE phone='".addslashes($staff_array[1])."'";
		if (!db_db_getrow($sql, $contact, $db_error)) {
			$error .= "{$staff_line}: {$db_error}<br />\n";
			continue;
		}
		if ($contact['id']) {
			// it's already in the database, update the name
			$sql = "UPDATE contacts SET contact_name='".addslashes(trim($staff_array[0]))."' ".
				"WHERE id='{$contact['id']}'";
			if (!db_db_command($sql, $db_error)) {
				$error .= "{$staff_line}: {$db_error}<br />\n";
				continue;
			}
		} else {
			// it's not in the database, add it
			$sql = "INSERT INTO contacts SET contact_name='".addslashes(trim($staff_array[0]))."',".
				"phone='".addslashes($staff_array[1])."'";
			if (!db_db_command($sql, $db_error)) {
				$error .= "{$staff_line}: {$db_error}<br />\n";
				continue;
			}
			// retrieve the newly added record
			$sql = "SELECT * FROM contacts WHERE phone='".addslashes($staff_array[1])."'";
			if (!db_db_getrow($sql, $contact, $db_error)) {
				$error .= "{$staff_line}: {$db_error}<br />\n";
				continue;
			}
		}

		// is a day provided?
		if (trim($staff_array[2])) {
			// provide defaults
			if (!$staff_array[3]) {
				// default earliest time
				$staff_array[3] = '12:00 am';
			}
			if (!$staff_array[4]) {
				// default latest time
				$staff_array[4] = '11:59 pm';
			}
			if (!$staff_array[5]) {
				// default language digit
				$staff_array[5] = 2;
			}
			if ($staff_array[6] == '') {
				// default to sending texts
				$staff_array[6] = 1;
			}
			
		    // get language_id from the language_digit
		    $language_id = "1";
		    $sql = "SELECT id FROM languages WHERE digit='". addslashes($staff_array[5]) . "'";
		    if (!db_db_getone($sql, $language_id, $db_error)){
		        $error = "{$staff_line}: {$db_error}<br />\n";
		        continue;
		    }

			// add a call_times record
			$sql = "INSERT INTO call_times SET ".
				"contact_id='".addslashes($contact['id'])."',".
				"day='".trim(addslashes($staff_array[2]))."',".
				"earliest='".addslashes(date("H:i:s", strtotime($staff_array[3])))."',".
				"latest='".addslashes(date("H:i:s", strtotime($staff_array[4])))."',".
				"language_id='".addslashes($language_id)."',".
				"receive_texts='". ($staff_array[6] ? 'y' : 'n') . "'";
			if (!db_db_command($sql, $db_error)) {
				$error .= "{$staff_line}: {$db_error}<br />\n";
				continue;
			}
		}
		
		// import successful
		$success_count++;
	}
	
	// report on the status of the import
	$error_count = count($staff_lines) - $success_count;
	$message = "Imported {$success_count} staff entries successfully. ";
	if ($error_count) {
		$message .= "{$error_count} staff entries had errors.";
	}
	
	return true;
}

/**
* Remove a staff entry from the database
*
* First make sure all call time entries have been removed.
* 
* @param int $id
*   Staff id to remove.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True unless an error occurred.
*/

function removeStaff($id, &$error, &$message)
{
	// are all call time entries removed?
	$sql = "SELECT COUNT(*) FROM call_times WHERE contact_id='".addslashes($id)."'";
	if (!db_db_getone($sql, $call_time_count, $error)) {
		return false;
	}
	if ($call_time_count) {
		$error = "Please remove the call time entries first.";
		return false;
	}
	
	// delete the staff entry
	$sql = "DELETE FROM contacts WHERE id='".addslashes($id)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	$message = "The staff entry was removed.";
	return true;
}

/**
* Remove a call time entry from the database
*
* ...
* 
* @param int $id
*   Call time id to remove.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True unless an error occurred.
*/

function removeCallTime($id, &$error, &$message)
{
	$sql = "DELETE FROM call_times WHERE id='".addslashes($id)."'";
	if (!db_db_command($sql, $error)) {
		return false;
	}
	
	$message = "The record was removed.";
	return true;
}

?>
