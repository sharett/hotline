<?php
/**
* @file
* Staff
*
* Display and edit all hotline staff and their call times
*
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

require_once 'hotline_call_times_utils.php';

include 'header.php';

// URL parameters
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$staff = isset($_POST['staff']) ? $_POST['staff'] : '';
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
$call_time = isset($_REQUEST['call_time']) ? $_REQUEST['call_time'] : '';
$call_time_languages = isset($_REQUEST['call_time_languages']) ? $_REQUEST['call_time_languages'] : array();
$display_type = isset($_REQUEST['display_type']) ? $_REQUEST['display_type'] : 'chronological';

// Authorized user?
$authorized = empty($HOTLINE_AUTHORIZED_USERS) ||
    in_array($_SERVER['PHP_AUTH_USER'], $HOTLINE_AUTHORIZED_USERS);
if (!$authorized) {
    // no
    $error = "You are not authorized to update staff information.";
}

// *** ACTIONS ***

// Add or remove a staff member, or add, edit, or remove a call time, or put up
// a modal dialog to allow the user to add a new call time or edit an existing
// one, if any of these actions were requested.
if ($action == 'add' && $authorized) {
    addStaff($staff, $error, $success);
} elseif ($action == 'removestaff' && $authorized) {
    removeStaff($id, $error, $success);
} elseif ($action == 'addcalltime' && $authorized) {
    addCallTime($call_time, $call_time_languages, $error, $success);
} elseif ($action == 'editcalltime' && $authorized) {
    editCallTime($call_time, $call_time_languages, $error, $success);
} elseif ($action == 'removecalltime' && $authorized) {
    removeCallTime($id, $error, $success);
} elseif ($action == 'addcalltimemodal' && $authorized) {
    $modal_action = "Add";
    require 'hotline_call_times_modal.php';
} elseif ($action == 'editcalltimemodal' && $authorized) {
    $modal_action = "Edit";
    require 'hotline_call_times_modal.php';
}

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
            <li role="presentation"><a href="hotline_active_calls.php">Active Calls</a></li>
			<li role="presentation" class="active"><a href="hotline_staff.php">Staff</a></li>
			<li role="presentation"><a href="hotline_blocks.php">Blocks</a></li>
			<li role="presentation"><a href="hotline_languages.php">Languages</a></li>
			<li role="presentation"><a href="log.php?ph=<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? urlencode($hotline_number) : '' ?>">Log</a></li>
		  </ul>
		  <br />
<?php


// On duty now
?>
        <h3 class="sub-header">On duty now</h3>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>name</th>
                  <th>phone</th>
                  <th>receives</th>
                </tr>
              </thead>
              <tbody>
                <?php

$receives = array('calls' => false, 'texts' => false, 'answered_alerts' => false);
sms_getActiveContacts($contacts, 0 /* any language */, $receives, $error);
foreach ($contacts as $contact) {
    $types = array();
    // receive texts?
    if ($contact['receive_texts'] == 'y') {
        $types[] = "texts";
    }
    // receive calls?
    if ($contact['receive_calls'] == 'y') {
        $types[] = "calls";
    }
    // receive call answered alerts?
    if ($contact['receive_call_answered_alerts'] == 'y') {
        $types[] = "answer alerts";
    } ?>
                <tr>
                  <td><?php echo $contact['contact_name']?></td>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($contact['phone']) . '">' . $contact['phone'] . '</a>'; ?></td>
                  <td><?php echo implode(', ', $types) ?></td>
                </tr>
<?php

// End of foreach loop; iteration through contacts is done.
}

?>
              </tbody>
            </table>
          </div>

          <?php

// Get the call times in chronological or alphabetical order and display them
// as appropriate.
if ($display_type == "chronological") {
    require 'hotline_call_times_chrono.php';
} else {
    require 'hotline_call_times_alpha.php';
}

          ?>
          <form id="text-controls" action="hotline_staff.php" method="POST">
		    <input type="hidden" name="action" value="add">
            <input type="hidden" name="display_type" value="<?php echo $display_type ?>">
		    <div class="form-group">
			  <label for="text-message">Add staff</label>
			  <textarea class="form-control" name="staff" rows="3" cols="30"></textarea>
			  <p class="help-block">
			    <b>Format:</b> name, phone number, day, earliest time, latest time, language
				    keypress, texts (0 or 1), calls (0 or 1), call answered alerts (0 or 1).
			        Only name and phone number required.
			  </p>
 		    </div>
		    <button class="btn btn-success" id="button-text">Add</button>
		  </form>
          <?php
include 'footer.php';

/**
* Lock the call_times table and any associated tables, modifying the specified
* error message to hold an error if a problem occurs.
*
* @param string $staffLine
*   Line that was being added as new staff and/or new time.
* @param string &$error
*   Message to be modified to include any error that occurred, if an error
*   occurs while attempting the lock.
* @return bool
*   True if the tables were locked, false otherwise.
*/
function lockCallTimesTablesForStaffAddition($staffLine, &$error)
{
    if (!db_db_command("LOCK TABLES call_times WRITE, call_times AS t2 WRITE", $thisError)) {
        $error .= "{$staff_line}: ".$thisError."<br />\n";
        return false;
    }
    return true;
}

/**
* Unlock any locked tables, modifying the specified error message if any.
*
* @param string $staffLine
*   Line that was being added as new staff and/or new time.
* @param string &$error
*   Message to be modified to include any error that occurred, if an error
*   occurs while attempting the unlock.
* @return bool
*   True if the tables were unlocked, false otherwise.
*/
function unlockTablesForStaffAddition($staffLine, &$error)
{
    if (!db_db_command("UNLOCK TABLES", $thisError)) {
        $error .= "{$staff_line}: ".$thisError."<br />\n";
        return false;
    }
    return true;
}

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
        if (!$staff_line = trim($staff_line)) {
            continue;
        }

        $staff_array = explode(",", trim($staff_line));

        // 0 = name
        // 1 = phone number
        // 2 = day
        // 3 = earliest time
        // 4 = latest time
        // 5 = language keypress
        // 6 = texts (0 or 1).
        // 7 = calls (0 or 1).
        // 8 = call answered alerts (0 or 1).

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
        if (isset($staff_array[2]) && trim($staff_array[2])) {
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
                // default language keypress
                $staff_array[5] = 2;
            }
            if ($staff_array[6] == '') {
                // default to sending texts
                $staff_array[6] = 1;
            }
            if ($staff_array[7] == '') {
                // default to receiving calls
                $staff_array[7] = 1;
            }
            if ($staff_array[8] == '') {
                // default to not sending call answered alerts
                $staff_array[8] = 0;
            }

            // get language_id from the language_keypress
            $language_id = "1";
            $sql = "SELECT id FROM languages WHERE keypress='". addslashes($staff_array[5]) . "'";
            if (!db_db_getone($sql, $language_id, $db_error)) {
                $error .= "{$staff_line}: {$db_error}<br />\n";
                continue;
            }

            // Lock the tables.
            if (!lockCallTimesTablesForStaffAddition($staff_line, $error)) {
                continue;
            }

            // Ensure that an identical entry does not already exist.
            $earliest = addslashes(date("H:i:s", strtotime($staff_array[3])));
            $latest = addslashes(date("H:i:s", strtotime($staff_array[4])));
            $receive_texts = ($staff_array[6] ? 'y' : 'n');
            $receive_calls = ($staff_array[7] ? 'y' : 'n');
            $receive_call_answered_alerts = ($staff_array[8] ? 'y' : 'n');
            $query = "SELECT EXISTS(SELECT 1 FROM call_times WHERE ".
                "contact_id = ".addslashes($contact['id'])." AND ".
                "day = '".trim(addslashes($staff_array[2]))."' AND earliest = '".
                $earliest."' AND latest = '".$latest."' AND receive_texts = '".
                $receive_texts."' AND receive_calls = '".$receive_calls.
                "' AND receive_call_answered_alerts = '".
                $receive_call_answered_alerts."' AND language_id = '".
                addslashes($language_id)."')";
            if (!db_db_getone($query, $results, $thisError)) {
                $error .= "{$staff_line}: {$thisError}<br />\n";
                unlockTablesForStaffAddition($staff_line, $error);
                continue;
            }
            if ($results) {
                $thisError = "The call time record could not be added because it ".
                        "is a duplicate of an existing entry.";
                $error .= "{$staff_line}: {$thisError}<br />\n";
                unlockTablesForStaffAddition($staff_line, $error);
                continue;
            }

            // See if an entry that should be grouped with this one exists,
            // and if so, use its entry identifier; otherwise, use a new
            // entry identifier.
            $query = "SELECT entry_id FROM call_times WHERE ".
                "contact_id = ".addslashes($contact['id'])." AND ".
                "day = '".trim(addslashes($staff_array[2]))."' AND earliest = '".
                $earliest."' AND latest = '".$latest."' AND receive_texts = '".
                $receive_texts."' AND receive_calls = '".$receive_calls.
                "' AND receive_call_answered_alerts = '".
                $receive_call_answered_alerts."' LIMIT 1";
            if (!db_db_getone($query, $entryIdentifier, $thisError)) {
                $error .= "{$staff_line}: {$thisError}<br />\n";
                unlockTablesForStaffAddition($staff_line, $error);
                continue;
            }
            if (!$entryIdentifier) {
                if (!getNextAvailableEntryIdentifier($entryIdentifier, $thisError)) {
                    $error .= "{$staff_line}: {$thisError}<br />\n";
                    unlockTablesForStaffAddition($staff_line, $error);
                    continue;
                }
            }

            // Add a call_times record.
            $sql = "INSERT INTO call_times SET ".
                "entry_id='".$entryIdentifier."',".
                "contact_id='".addslashes($contact['id'])."',".
                "day='".trim(addslashes($staff_array[2]))."',".
                "earliest='".$earliest."',".
                "latest='".$latest."',".
                "language_id='".addslashes($language_id)."',".
                "receive_texts='".$receive_texts."',".
                "receive_calls='".$receive_calls."',".
                "receive_call_answered_alerts='".$receive_call_answered_alerts. "'";
            if (!db_db_command($sql, $db_error)) {
                $error .= "{$staff_line}: {$db_error}<br />\n";
            }

            // Unlock the tables.
            unlockTablesForStaffAddition($staff_line, $error);
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

          ?>
