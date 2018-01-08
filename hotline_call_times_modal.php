<?php
/**
* @file
* Call times modal dialog, for adding or editing call times.
*
* This file is to be included in a page; it does not work as a standalone
* page.
*/

require_once 'hotline_call_times_utils.php';

// Set up the initial values to be displayed by the dialog differently
// depending upon whether a new call time is being added, or an existing
// one is being edited.
if ($modal_action == "Add") {

    // Get the name of the contact.
    $sql = "SELECT contact_name FROM contacts WHERE id='".addslashes($id)."'";
    db_db_getone($sql, $contact_name, $error);

    // Initialize a new call time array.
    $call_time = array(
        'contact_id' => $id,
        'day' => "all",
        'earliest' => "12:00 am",
        'latest' => "11:59 pm",
        'language_ids' => null,
        'receive_texts' => "y",
        'receive_calls' => "y",
        'receive_call_answered_alerts' => "n"
    );
} else {

    // Get the call time array as a single record, with languages
    // listed sequentially.
    $sql = "SELECT entry_id, contact_id, day, earliest, latest, ".
            "receive_texts, receive_calls, receive_call_answered_alerts, ".
            "GROUP_CONCAT(language_id) AS language_ids FROM call_times ".
            "WHERE entry_id='".addslashes($id)."' GROUP BY entry_id, ".
            "contact_id, day, earliest, latest, receive_texts, ".
            "receive_calls, receive_call_answered_alerts";
    db_db_getrow($sql, $call_time, $error);
    $call_time['earliest'] = getDisplayableTime($call_time['earliest']);
    $call_time['latest'] = getDisplayableTime($call_time['latest']);
    $call_time['language_ids'] = explode(",", $call_time['language_ids']);

    // Get the name of the contact.
    $sql = "SELECT contact_name FROM contacts WHERE id='".
            addslashes($call_time['contact_id']). "'";
    db_db_getone($sql, $contact_name, $error);
}

// Create the modal dialog.
?>

<div class="modal show" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="modal-calltime" action="hotline_staff.php" method="POST">
       <input type="hidden" name="action" value="<?php
            echo strtolower($modal_action) ?>calltime">
       <input type="hidden" name="display_type" value="<?php echo $display_type ?>">
       <?php

if ($modal_action == "Edit") {
    ?>

       <input type="hidden" name="call_time[entry_id]"
            value="<?php echo $call_time['entry_id'] ?>">

       <?php
}

       ?>
       <input type="hidden" name="call_time[contact_id]"
            value="<?php echo $call_time['contact_id'] ?>">
        <div class="modal-header">
          <a class="btn close"
                href="hotline_staff.php?display_type=<?php echo $display_type ?>"
                role="button" aria-label="Close"><span aria-hidden="true">&times;</span></a>
          <h4 class="modal-title">
            <strong>
            <?php echo $modal_action ?> call time for <?php echo $contact_name ?>
            </strong>
          </h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="calltime_day">Days</label>
            <select class="form-control" id="calltime_day" name="call_time[day]">
              <?php

foreach (array('all', 'weekdays', 'weekends', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $day) {
    echo "<option". ($day == $call_time['day'] ? " selected" : ""). ">". $day. "</option>";
}
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="calltime_earliest">Earliest time</label>
            <input type="text" class="form-control" id="calltime_earliest"
                    name="call_time[earliest]"
                    value="<?php echo $call_time['earliest'] ?>">
          </div>
          <div class="form-group">
            <label for="calltime_latest">Latest time</label>
            <input type="text" class="form-control" id="calltime_latest"
                    name="call_time[latest]"
                    value="<?php echo $call_time['latest'] ?>">
          </div>
          <div class="form-group">
            <label>Languages: </label>
            <?php

    // Look up the language options.
    $sql = "SELECT id,language FROM languages ORDER BY language";
    db_db_query($sql, $languages, $error);
    foreach ($languages as $language) {

            // Output the language checkboxes.?>
            <label class="checkbox-inline">
              <input type="checkbox" id="language_<?php echo $language['id'] ?>"
                    name="call_time_languages[<?php echo $language['id'] ?>]"
                    <?php

if (($call_time['language_ids'] == null) || in_array($language['id'], $call_time['language_ids'])) {
    echo " checked";
} ?>>
                <?php echo $language['language'] ?>
            </label>
<?php

    // End of the foreach loop creating language options.
    }
?>
          </div>
          <div class="form-group">
            <label for="calltime_texts">Receive: </label>
            <label class="checkbox-inline">
              <input type="checkbox" id="calltime_texts" name="call_time[receive_texts]"
                    <?php
                        echo($call_time['receive_texts'] == "y" ? " checked" : "")
                    ?>> texts
            </label>
            <label class="checkbox-inline">
              <input type="checkbox" id="calltime_calls" name="call_time[receive_calls]"
                    <?php
                        echo($call_time['receive_calls'] == "y" ? " checked" : "")
                    ?>> calls
            </label>
            <label class="checkbox-inline">
              <input type="checkbox" id="calltime_answered_alerts"
                    name="call_time[receive_call_answered_alerts]"
                    <?php
                        echo($call_time['receive_call_answered_alerts'] == "y" ? " checked" : "")
                    ?>> call answered alerts
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default"
                href="hotline_staff.php?display_type=<?php echo $display_type ?>"
                role="button">Close</a>
          <button type="submit" class="btn btn-primary"><?php echo $modal_action ?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
