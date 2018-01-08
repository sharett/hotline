<?php
/**
* @file
* Call times utilities.
*
* Utility functions for call times display.
*/

/**
* Lock the call_times table and any associated tables, modifying the specified
* error message to hold an error if a problem occurs.
*
* @param string &$error
*   Message to be modified to include any error that occurred, if an error
*   occurs while attempting the unlock.
* @param string $errorPrefix
*   Prefix to be prepended any error that occurs; should be something like
*   "The operation failed" with no terminating punctuation.
* @return bool
*   True if the tables were locked, false otherwise.
*/
function lockCallTimesTables(&$error, $errorPrefix)
{
    if (!db_db_command("LOCK TABLES call_times WRITE, call_times AS t2 WRITE", $error)) {
        $error = $errorPrefix." because the table could not be locked in the database.";
        return false;
    }
    return true;
}

/**
* Unlock any locked tables, modifying the specified error message if any.
*
* @param string &$error
*   Message to be modified to include any error that occurred, if an error
*   occurs while attempting the unlock.
* @return bool
*   True if the tables were unlocked, false otherwise.
*/
function unlockTables(&$error)
{
    if (!db_db_command("UNLOCK TABLES", $error)) {
        if (empty($error)) {
            $error = "The tables could not be unlocked in the database.";
        } else {
            $error = $error."<br><br>Additionally, The tables could not ".
                    "be unlocked in the database.";
        }
        return false;
    }
    return true;
}

/**
* Determine whether or not the values in the two specified arrays found
* under the specified indices are equal.
*
* @param array &$array1
*   First array to be compared.
* @param array &$array2
*   Second array to be compared.
* @param array $indices
*   Indices for which to compare values in the two arrays.
* @return bool
*   True if the two arrays are equal at the indices, false otherwise.
*/
function areArrayValuesEqual(&$array1, &$array2, $indices)
{
    foreach ($indices as $index) {
        if ($array1[$index] != $array2[$index]) {
            return false;
        }
    }
    return true;
}

/**
* Create the call times display type selector.
*
* @param string $display_type
*   Type of display currently being shown; either 'chronological' or
*   'alphabetical'.
*/
function createCallTimesDisplayTypeSelector($display_type)
{
    ?>
    <form action="hotline_staff.php" method="GET" class="form-inline">
      <div class="form-group">
        <label for="display_type">Sort order:</label>
        <select class="form-control" name="display_type" onChange="this.form.submit()">
          <option value="chronological"
            <?php

    if ($display_type == "chronological") {
        echo "selected";
    } ?>>by day</option>
          <option value="alphabetical"
            <?php

    if ($display_type == "alphabetical") {
        echo "selected";
    } ?>>by name</option>
        </select>
      </div>
    </form>
    <?php
}

/**
* Given the specified call time, get the cell decorations for all its
* table cells. The cell decorations are an array of two elements, the
* first holding a string to be prepended to any cell's contents, the
* second holding a string to be appended to any cell's contents.
*
* @param array &$call_time
*   Call time information.
* @param string $enabled_index
*   Index into $call_time at which to find the is-enabled value.
* @param string $receive_calls_index
*   Index into $call_time at which to find the can-receive-calls value.
* @param string $receive_texts_index
*   Index into $call_time at which to find the can-receive-texts value.
* @return array
*   Array of two strings, one the cell prefix, the other the suffix.
*/
function getCellDecorations(
    &$call_time,
    $enabled_index,
    $receive_calls_index,
    $receive_texts_index
) {
    if ($call_time[$enabled_index] == 'n') {
        return array(
            '<span style="text-decoration: line-through; color: gray;">',
            '</span>'
        );
    } elseif (($call_time[$receive_calls_index] == 'n') &&
            ($call_time[$receive_texts_index] == 'n')) {
        return array('<span style="color: darkred;">', '</span>');
    } else {
        return array('', '');
    }
}

/**
* Get a link to the specified phone number.
*
* @param string $phone
*   Phone number.
* @return string Link to the phone number.
*/
function getContactNumber($phone)
{
    return '<a href="contact.php?ph='. urlencode($phone). '">'. $phone. '</a>';
}

/**
* Get displayable time from the specified time string fetched from a
* TIME-type column in a database table.
*
* @param string time
*   Time string.
* @return string
*   Time string suitable for display.
*/
function getDisplayableTime($time)
{
    return date("h:i a", strtotime($time));
}

/**
* Create a call time table cell, filling it with the value found at the
* specified index of the specified call time array.
*
* @param array &$call_time
*   Information about the call time from which to extract the relevant
*   value.
* @param array &$last_call_time
*   Information about the previous call time; this is used to determine
*   whether or not the value has changed from the last one, and if it
*   has not, to create an empty table cell.
* @param string $index
*   Index into $call_time at which to find the relevant value.
* @param string &$cell_decorations
*   Array of two strings, the first being the prefix for any contents
*   of the table cell, the second being the suffix.
* @param bool $force_display
*   Flag indicating whether or not the cell created should not be empty.
*   If true, the cell will have the appropriate contents; if false, then
*   it will only be non-empty if its value differs from the previous
*   call time's corresponding value.
* @return bool
*   True if the created cell is not empty, false if it is empty.
*/
function createCallTimeTableCell(
    &$call_time,
    &$last_call_time,
    $index,
    &$cell_decorations,
    $force_display
) {
    if ($force_display || ($last_call_time[$index] != $call_time[$index])) {
        echo "<td>". $cell_decorations[0]. $call_time[$index]. $cell_decorations[1]. "</td>";
        return true;
    } else {
        echo "<td></td>";
        return false;
    }
}

/**
* Create a call time table phone number cell, filling it with the value
* found at the specified index of the specified call time array, with
* said value being a link to interact with the phone number.
*
* @param array &$call_time
*   Information about the call time from which to extract the relevant
*   value.
* @param array &$last_call_time
*   Information about the previous call time; this is used to determine
*   whether or not the value has changed from the last one, and if it
*   has not, to create an empty table cell.
* @param string $index
*   Index into $call_time at which to find the relevant value.
* @param string $cell_decorations
*   Array of two strings, the first being the prefix for any contents
*   of the table cell, the second being the suffix.
* @param bool $force_display
*   Flag indicating whether or not the cell created should not be empty.
*   If true, the cell will have the appropriate contents; if false, then
*   it will only be non-empty if its value differs from the previous
*   call time's corresponding value.
* @return bool
*   True if the created cell is not empty, false if it is empty.
*/
function createCallTimeTablePhoneCell(
    &$call_time,
    &$last_call_time,
    $index,
    &$cell_decorations,
    $force_display
) {
    if ($force_display || ($last_call_time[$index] != $call_time[$index])) {
        echo "<td>". $cell_decorations[0]. getContactNumber($call_time[$index]).
                $cell_decorations[1]. "</td>";
        return true;
    } else {
        echo "<td></td>";
        return false;
    }
}

/**
* Create the two (start and end) call time range table cells, placing within
* them the values found at the appropriate indices of the specified call time
* array.
*
* @param array &$call_time
*   Information about the call time from which to extract the relevant
*   values.
* @param array &$last_call_time
*   Information about the previous call time; this is used to determine
*   whether or not the values changed from the last one, and if they have
*   not, to create empty table cells.
* @param string $start_index
*   Index into $call_time at which to find the relevant start time value.
* @param string $end_index
*   Index into $call_time at which to find the relevant end time value.
* @param string &$cell_decorations
*   Array of two strings, the first being the prefix for any contents
*   of the table cells, the second being the suffix.
* @param bool $force_display
*   Flag indicating whether or not the cells created should not be empty.
*   If true, the cells will have the appropriate contents; if false, then
*   they will only be non-empty if their values differ from the previous
*   call time's corresponding values.
* @return bool
*   True if the created cell is not empty, false if it is empty.
*/
function createCallTimeRangeTableCells(
    &$call_time,
    &$last_call_time,
    $start_index,
    $end_index,
    &$cell_decorations,
    $force_display
) {
    if ($force_display || (($last_call_time[$start_index] != $call_time[$start_index]) ||
         ($last_call_time[$end_index] != $call_time[$end_index]))) {
        foreach (array($start_index, $end_index) as $index) {
            echo "<td>". $cell_decorations[0]. getDisplayableTime($call_time[$index]).
                    $cell_decorations[1]. "</td>";
        }
        return true;
    } else {
        echo '<td colspan="2"></td>';
        return false;
    }
}

/**
* Create the call time types to be received table cell, placing within it
* a concatenation of the values found at the appropriate indices of the
* specified call time array.
*
* @param array &$call_time
*   Information about the call time from which to extract the relevant
*   values. Note that this array is modified to include an entry for the
*   string that would be displayed in this cell (regardless of whether
*   said string is actually displayed, since if it is the same as the one
*   in $last_call_time, it obviously will not be). The entry is under the
*   index 'types_string'.
* @param array &$last_call_time
*   Information about the previous call time; this is used to determine
*   whether or not the values changed from the last one, and if they have
*   not, to create empty table cells. This array must have an entry for
*   'types_string'.
* @param array $type_strings_for_indices
*   Mapping of the indices of types to be received, all of which should
*   be found within $call_time, to the text strings used to describe the
*   types.
* @param string &$cell_decorations
*   Array of two strings, the first being the prefix for any contents
*   of the table cell, the second being the suffix.
* @param bool $force_display
*   Flag indicating whether or not the cell created should not be empty.
*   If true, the cell will have the appropriate contents; if false, then
*   it will only be non-empty if its value differs from the previous
*   call time's corresponding value.
* @return bool
*   True if the created cell is not empty, false if it is empty.
*/
function createCallTimeTypesTableCell(
    &$call_time,
    &$last_call_time,
    $type_strings_for_indices,
    &$cell_decorations,
    $force_display
) {
    $types = array();
    foreach ($type_strings_for_indices as $index => $string) {
        if ($call_time[$index] == 'y') {
            $types[] = $string;
        }
    }
    $types_string = implode(', ', $types);
    if ($force_display || ($last_call_time['types_string'] != $types_string)) {
        echo "<td>". $cell_decorations[0]. $types_string. $cell_decorations[1]. "</td>";
        $result = true;
    } else {
        echo "<td></td>";
        $result = false;
    }
    $call_time['types_string'] = $types_string;
    return $result;
}

/**
* Get the next available (free) entry identifier.
*
* @param string &$entryIdentifier
*   Variable to be modified to hold the next available entry identifier.
* @param string &$error
*   Variable to be modified to hold an error description, if the attempt
*   to get the entry identifier fails.
* @return bool
*   True if the attempt succeeds, false otherwise.
*/
function getNextAvailableEntryIdentifier(&$entryIdentifier, &$error)
{
    $query = "SELECT MIN(t1.entry_id) AS next_entry_id FROM (SELECT 1 AS entry_id ".
            "UNION ALL SELECT entry_id + 1 FROM call_times) t1 LEFT OUTER JOIN ".
            "call_times t2 ON t1.entry_id = t2.entry_id WHERE t2.entry_id IS NULL";
    if (!db_db_getone($query, $entryIdentifier, $error)) {
        return false;
    }
    return true;
}

/**
* Add a single call time entry to the database.
*
* Data is passed from a modal form.
*
* @param array $call_time
*   Call time form data.  Contains the following keys:
*		'contact_id' => The contact id to add a call time to
* 		'day' => 'all','weekdays','weekends','Sun','Mon','Tue','Wed','Thu','Fri','Sat'
* 		'earliest' => The earliest time the contact should be called.
*		'latest' => The latest time the contact should be called.
*		'receive_texts' => Whether the contact should receive texts.
*		'receive_calls' => Whether the contact should receive calls.
*		'receive_call_answered_alerts' => Whether the contact should receive call answered alerts.
* @param array $call_time_languages
*   Call time languages array; each key is a language identifier, and the corresponding
*   value is 'on' if that language is included.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
* @return bool
*   True if successful, false if an error occurred.
*/
function addCallTime($call_time, $call_time_languages, &$error, &$message)
{
    $error = '';
    $message = '';

    // call_time[]:


    // contact id
    $call_time['contact_id'] = (int)$call_time['contact_id'];
    if (!$call_time['contact_id']) {
        $error = "No contact was specified.";
        return false;
    }
    // default day
    if (!$call_time['day']) {
        $call_time['day'] = 'all';
    }
    // default earliest time
    if (!$call_time['earliest']) {
        $call_time['earliest'] = '12:00 am';
    }
    // default latest time
    if (!$call_time['latest']) {
        $call_time['latest'] = '11:59 pm';
    }
    // list of languages
    $languages = array();
    foreach ($call_time_languages as $language => $include) {
        if ($include == 'on') {
            $languages[] = $language;
        }
    }
    if (count($languages) == 0) {
        $error = "The call time entry could not be added because no ".
                "language was specified.";
        return false;
    }

    // Ensure at least one checkbox is selected (for texts, calls, or
    // answered alerts.
    $texts = (isset($call_time['receive_texts']) &&
            ($call_time['receive_texts'] == 'on'));
    $calls = (isset($call_time['receive_calls']) &&
            ($call_time['receive_calls'] == 'on'));
    $alerts = (isset($call_time['receive_call_answered_alerts']) &&
            ($call_time['receive_call_answered_alerts'] == 'on'));
    if (!$texts && !$calls && !$alerts) {
        $error = "The call time entry could not be added because no ".
                "'received type' checkbox (texts, calls, or call ".
                "answered alerts) was specified.";
        return false;
    }

    // Lock the tables.
    if (!lockCallTimesTables($error, "The call time entry could not be added")) {
        return false;
    }

    // Ensure that an identical entry does not already exist.
    $earliest = addslashes(date("H:i:s", strtotime($call_time['earliest'])));
    $latest = addslashes(date("H:i:s", strtotime($call_time['latest'])));
    $receive_texts = ($texts ? "y" : "n");
    $receive_calls = ($calls ? "y" : "n");
    $receive_call_answered_alerts = ($alerts ? "y" : "n");
    $query = "SELECT EXISTS(SELECT 1 FROM call_times WHERE ".
        "contact_id = ".addslashes($call_time['contact_id'])." AND ".
        "day = '".addslashes($call_time['day'])."' AND earliest = '".
        $earliest."' AND latest = '".$latest."' AND receive_texts = '".
        $receive_texts."' AND receive_calls = '".$receive_calls.
        "' AND receive_call_answered_alerts = '".$receive_call_answered_alerts.
        "')";
    if (!db_db_getone($query, $results, $error)) {
        $error = "The call time entry could not be added because an error ".
                "occurred while checking for duplicate entries: ".$error;
        unlockTables($error);
        return false;
    }
    if ($results) {
        $error = "The call time entry could not be added because it ".
                "is a duplicate of an existing entry.";
        unlockTables($error);
        return false;
    }

    // Get the next available entry identifier.
    if (!getNextAvailableEntryIdentifier($entryIdentifier, $error)) {
        $error = "The call time entry could not be added because the next ".
                "available entry identifier could not be determined: ".$error;
        unlockTables($error);
        return false;
    }

    // Add one call_times record for each language, with all records having
    // the same entry identifier.
    foreach ($languages as $language) {
        $sql = "INSERT INTO call_times SET ".
            "entry_id='".addslashes($entryIdentifier)."',".
            "contact_id='".addslashes($call_time['contact_id'])."',".
            "day='".addslashes($call_time['day'])."',".
            "earliest='".$earliest."',".
            "latest='".$latest."',".
            "language_id='".addslashes($language)."',".
            "receive_texts='".$receive_texts."',".
            "receive_calls='".$receive_calls."',".
            "receive_call_answered_alerts='".$receive_call_answered_alerts."'";
        if (!db_db_command($sql, $error)) {
            $error = "The call time entry could not be fully added due to ".
                    "a database error: ".$error;
            unlockTables($error);
            return false;
        }
    }

    // Unlock the tables.
    $message = "The call time entry was added.";
    $error = null;
    return unlockTables($error);
}
/**
* Edit a single call time entry in the database.
*
* Data is passed from a modal form.
*
* @param array $call_time
*   Call time form data.  Contains the following keys:
*       'id' => Original identifier of the call time entry being edited.
* 		'day' => 'all','weekdays','weekends','Sun','Mon','Tue','Wed','Thu','Fri','Sat'.
* 		'earliest' => The earliest time the contact should be called.
*		'latest' => The latest time the contact should be called.
*		'receive_texts' => Whether the contact should receive texts.
*		'receive_calls' => Whether the contact should receive calls.
*		'receive_call_answered_alerts' => Whether the contact should receive call
*           answered alerts.
* @param array $call_time_languages
*   Call time languages array; each key is a language identifier, and the corresponding
*   value is 'on' if that language is included.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
* @return bool
*   True if successful, false if an error occurred.
*/
function editCallTime($call_time, $call_time_languages, &$error, &$message)
{
    $error = '';
    $message = '';

    // Update the day, earliest time, and latest time if each is provided.
    $updates = array();
    if ($call_time['day']) {
        $updates[] = "day='". addslashes($call_time['day']). "'";
    }
    if ($call_time['earliest']) {
        $updates[] = "earliest='". addslashes(date("H:i:s", strtotime($call_time['earliest']))). "'";
    }
    if ($call_time['latest']) {
        $updates[] = "latest='". addslashes(date("H:i:s", strtotime($call_time['latest']))). "'";
    }

    // Get the list of languages for which to create rows.
    $languages = array();
    foreach ($call_time_languages as $language => $include) {
        if ($include == 'on') {
            $languages[] = $language;
        }
    }
    if (count($languages) == 0) {
        $error = "The call time entry could not be updated because no ".
                "language was specified.";
        return false;
    }

    // Ensure at least one checkbox is selected (for texts, calls, or
    // answered alerts.
    $texts = (isset($call_time['receive_texts']) && ($call_time['receive_texts'] == 'on'));
    $calls = (isset($call_time['receive_calls']) && ($call_time['receive_calls'] == 'on'));
    $alerts = (isset($call_time['receive_call_answered_alerts']) &&
            ($call_time['receive_call_answered_alerts'] == 'on'));
    if (!$texts && !$calls && !$alerts) {
        $error = "The call time entry could not be updated because no ".
                "'received type' checkbox (texts, calls, or call ".
                "answered alerts) was specified.";
        return false;
    }
    $updates[] = "receive_texts='". ($texts ? "y" : "n")."', ".
            "receive_calls='". ($calls ? "y" : "n")."', ".
            "receive_call_answered_alerts='". ($alerts ? "y" : "n")."'";

    // Do nothing if nothing has been found to update.
    if (count($updates) == 0) {
        $message = "The call time entry was not updated since no changes ".
                "were specified.";
        return true;
    }

    // Lock the tables.
    if (!lockCallTimesTables(
        $error,
            "The call time entry could not be updated"
    )) {
        return false;
    }

    // Ensure that an identical entry does not already exist.
    $earliest = addslashes(date("H:i:s", strtotime($call_time['earliest'])));
    $latest = addslashes(date("H:i:s", strtotime($call_time['latest'])));
    $receive_texts = ($texts ? "y" : "n");
    $receive_calls = ($calls ? "y" : "n");
    $receive_call_answered_alerts = ($alerts ? "y" : "n");
    $query = "SELECT EXISTS(SELECT 1 FROM call_times WHERE ".
        "contact_id = ".addslashes($call_time['contact_id'])." AND ".
        "day = '".addslashes($call_time['day'])."' AND earliest = '".
        $earliest."' AND latest = '".$latest."' AND receive_texts = '".
        $receive_texts."' AND receive_calls = '".$receive_calls.
        "' AND receive_call_answered_alerts = '".$receive_call_answered_alerts.
        "' AND entry_id != ".$call_time['entry_id'].")";
    if (!db_db_getone($query, $results, $error)) {
        $error = "The call time entry could not be updated because an error ".
                "occurred while checking for duplicate entries: ".$error;
        unlockTables($error);
        return false;
    }
    if ($results) {
        $error = "The call time entry could not be updated because the ".
                "changes would have resulted in it being a duplicate ".
                "of an existing entry.";
        unlockTables($error);
        return false;
    }

    // Since the updated entry may require a different number of records
    // from the original version, delete the old records.
    //
    // TODO: The algorithm could be made smarter, and reuse existing rows
    // in the table where possible. This is somewhat less elegant.
    $sql = "DELETE FROM call_times WHERE entry_id='".
            addslashes($call_time['entry_id'])."'";
    if (!db_db_command($sql, $error)) {
        $error = "The call time entry could not be updated because an error ".
                "occurred while deleting the old entry: ".$error;
        unlockTables($error);
        return false;
    }

    // Add one call_times record for each language, with all records having
    // the same entry identifier.
    foreach ($languages as $language) {
        $sql = "INSERT INTO call_times SET ".
            "entry_id='".addslashes($call_time['entry_id'])."',".
            "contact_id='".addslashes($call_time['contact_id'])."',".
            "day='".addslashes($call_time['day'])."',".
            "earliest='".$earliest."',".
            "latest='".$latest."',".
            "language_id='".addslashes($language)."',".
            "receive_texts='".$receive_texts."',".
            "receive_calls='".$receive_calls."',".
            "receive_call_answered_alerts='".$receive_call_answered_alerts."'";
        if (!db_db_command($sql, $error)) {
            $error = "The call time entry could not be fully updated due to ".
                    "a database error: ".$error;
            unlockTables($error);
            return false;
        }
    }

    // Unlock the tables.
    $message = "The call time entry was updated.";
    $error = null;
    return unlockTables($error);
}

/**
* Remove a call time entry from the database
*
* ...
*
* @param int $id
*   Call time entry identifier to remove.
* @param string &$error
*   Errors if any occurred.
* @param string &$message
*   An informational message if appropriate.
* @return bool
*   True unless an error occurred.
*/
function removeCallTime($id, &$error, &$message)
{
    $sql = "DELETE FROM call_times WHERE entry_id='".addslashes($id)."'";
    if (!db_db_command($sql, $error)) {
        return false;
    }

    $message = "The entry was removed.";
    return true;
}
