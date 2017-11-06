<?php
/**
* @file
* Call times alphabetically ordered
*
* Display and edit call times that are scheduled, ordered alphabetically
* by staff member name. This file is to be included in a page; it does not
* work as a standalone page.
*/

require_once 'hotline_call_times_utils.php';

// Empty call time constant.
$EMPTY_CALL_TIME = array(
       'day' => "",
       'earliest' => -1,
       'latest' => -1,
       'language' => "",
       'receive_texts' => "",
       'receive_calls' => "",
       'receive_call_answered_alerts' => "",
       'types_string' => "",
       'enabled' => "y"
   );

// Query the database for the contacts.
if (!db_db_query("SELECT * FROM contacts ORDER BY contact_name", $contacts, $error)) {
    echo $error;
}
?>

<h3 class="sub-header">Staff</h3>
<?php createCallTimesDisplayTypeSelector($display_type); ?>
<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>name</th>
        <th>phone</th>
        <th>day</th>
        <th>from</th>
        <th>to</th>
        <th>language</th>
        <th>types</th>
        <th>actions</th>
      </tr>
    </thead>
    <tbody>
      <?php

// Iterate over all the contacts, placing each one's call times in
// the table.
foreach ($contacts as $contact) {
    $sql = "SELECT call_times.*,languages.language FROM call_times ".
            "LEFT JOIN languages ON languages.id = call_times.language_id ".
            "WHERE contact_id='{$contact['id']}' ".
            "ORDER BY call_times.day, call_times.earliest, call_times.latest, ".
            "languages.language, call_times.enabled";
    if (!db_db_query($sql, $call_times, $error)) {
        echo $error;
    } ?>
      <tr>
        <td><?php echo $contact['contact_name']?></td>
        <td><?php echo getContactNumber($contact['phone']); ?></td>
        <?php

    // Allow removal of staff entry if all call time records are
    // removed.
    if (count($call_times) == 0) {
        ?>
        <td></td><td></td><td></td><td></td><td></td><td>
          <a href="hotline_staff.php?display_type=alphabetical&action=removestaff&id=<?php
                echo $contact['id'] ?>"
              onClick="return confirm('Are you sure you want to remove this staff entry?');"
              title="Remove this staff entry">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
        <?php
    } else {

        // Display each call time record that is not an exact
        // repeat of the previous one, crossed out if disabled.
        $first = true;
        $last_call_time = $EMPTY_CALL_TIME;
        foreach ($call_times as $call_time) {

            // If this is not the first call time for this contact,
            // ensure that it should be shown, and if so, pad it
            // with empty table cells since the leftmost ones do
            // not need repeating.
            if ($first) {
                $first = false;
            } else {

                // If the previous call time entry and this one
                // do not have the same enabled state, ensure
                // that this one is fully displayed, with no
                // empty cells for its days, times, etc. If the
                // two have the same enabled state, see if this
                // one is simply a repeat of the previous one,
                // and if so, skip it.
                if ($last_call_time['enabled'] != $call_time['enabled']) {
                    $last_call_time = $EMPTY_CALL_TIME;
                } elseif (areArrayValuesEqual($call_time, $last_call_time, array(
                    'day', 'earliest', 'latest', 'receive_texts', 'receive_calls',
                    'receive_call_answered_alerts', 'language'))) {
                    continue;
                }

                // Add the start of a table row, and a couple of
                // empty cells to pad it to the left, since the
                // contact info cells do not need to be repeated.
                echo "<tr><td></td><td></td>";
            }

            // Get cell decorations for the contents of any table
            // cell for this call time entry.
            $cell_decorations = getCellDecorations(
                $call_time,
                'enabled',
                'receive_calls',
                'receive_texts'
            );

            // Days and times.
            $force_display = createCallTimeTableCell(
                $call_time,
                $last_call_time,
                'day',
                $cell_decorations,
                false
            );
            $force_display = createCallTimeRangeTableCells(
                $call_time,
                $last_call_time,
                'earliest',
                'latest',
                $cell_decorations,
                $force_display
            );

            // Language.
            $force_display = createCallTimeTableCell(
                $call_time,
                $last_call_time,
                'language',
                $cell_decorations,
                $force_display
            );

            // Types of calls/messages that can be received.
            createCallTimeTypesTableCell($call_time, $last_call_time, array(
                'receive_texts' => "texts",
                'receive_calls' => "calls",
                'receive_call_answered_alerts' => "answer alerts"
            ), $cell_decorations, $force_display);

            // Remember this call time for the next one, so that values
            // that have not changed can be output as blank table cells.
            $last_call_time = $call_time; ?>
        <td>
          <a href="hotline_staff.php?display_type=alphabetical&action=editcalltimemodal&id=<?php
                echo $call_time['id'] ?>"
                title="Edit this call time">
            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
          <a href="hotline_staff.php?display_type=alphabetical&action=removecalltime&id=<?php
                echo $call_time['id'] ?>"
                onClick="return confirm('Are you sure you want to remove this call time?');"
                title="Remove this call time">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
        </td>
      </tr>
      <?php

        // Close the foreach loop; iteration over the call times is
        // done.
        } ?>
      <tr>
        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td>
          <?php

    // Close the else block; call times have all been processed for
    // this contact.
    } ?>
          <a href="hotline_staff.php?display_type=alphabetical&action=addcalltimemodal&id=<?php
                echo $contact['id'] ?>"
              title="Add a call time">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
        </td>
      </tr>
      <?php

// Close the foreach loop; iteration over all the contacts is done.
}
      ?>
    </tbody>
  </table>
</div>
