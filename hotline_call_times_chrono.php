<?php
/**
* @file
* Call times chronologically ordered
*
* Display and edit call times that are scheduled, ordered chronologically.
* This file is to be included in a page; it does not work as a standalone
* page.
*/

require_once 'hotline_call_times_utils.php';

// Empty call time constant.
$EMPTY_CALL_TIME = array(
        'original_day' => "",
        'original_day_ordinal' => -1,
        'day_of_week' => "",
        'day_ordinal' => -1,
        'earliest_time' => -1,
        'latest_time' => -1,
        'takes_calls' => "",
        'takes_texts' => "",
        'takes_alerts' => "",
        'types_string' => "",
        'enabled_choice' => "y",
        'language_choice' => "",
        'staff_name' => "",
        'phone' => ""
   );

// Query mode dicates how the query is performed. It has two possible
// values: "pureSql" or "hybrid". The former uses a large multipart,
// UNIONed SELECT statement to perform the query. The latter uses four
// separate SELECT statements to query the single- and multi-day rows,
// and then merges them together using PHP.
$QUERY_MODE = "hybrid";

// Test mode, if true, indicates whether testing should be performed
// instead of displaying query results.
$TEST_MODE = false;

//  If testing, run each query many times and output the time taken.
// Otherwise, run the appropriate query and display the results.
if ($TEST_MODE) {

    // Time the hybrid approach.
    $startTimeMicros = microtime(true);
    for ($j = 0; $j < 1000; $j++) {
        $call_times = getResultsUsingHybridQuery();
    }
    $call_times_count = count($call_times);
    $timeHybridApproach = round((microtime(true) - $startTimeMicros) * 1000);

    // Time the pure SQL approach.
    $startTimeMicros = microtime(true);
    for ($j = 0; $j < 1000; $j++) {
        $call_times = getResultsUsingPureSqlQuery();
    }
    $timePureSqlApproach = round((microtime(true) - $startTimeMicros) * 1000);

    // Show the test results.
    echo '<div class="alert alert-success" role="alert">'.
        'Time taken for pure SQL approach was '. $timePureSqlApproach.
        ' milliseconds, yielding '. $call_times_count. ' records.<br>'.
        'Time taken for hybrid SQL/PHP approach was '. $timeHybridApproach.
        ' milliseconds, yielding '. count($call_times). ' records.'.
        '</div>';
} else {

    // Get the call times.
    $call_times = ($QUERY_MODE == "hybrid" ? getResultsUsingHybridQuery() :
            getResultsUsingPureSqlQuery());

    // Show the header.?>

    <h3 class="sub-header">Call Times</h3>

    <?php

    // Show the display type selector.
    createCallTimesDisplayTypeSelector($display_type);

    // Provide a message if there are no call times; otherwise, show a table
    // with the call times.
    if (count($call_times) == 0) {
        ?>
        <div><br>There are no call times on record.<br><br></div>
        <?php
    } else {

        // Start the table.?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>day</th>
                <th>from</th>
                <th>to</th>
                <th>name</th>
                <th>phone</th>
                <th>language</th>
                <th>types</th>
                <th>actions</th>
              </tr>
            </thead>
            <tbody>
              <?php

        // Iterate through the call times, displaying each in turn.
        $last_call_time = $EMPTY_CALL_TIME;
        foreach ($call_times as $call_time) {

            // If the previous call time entry and this one do not
            // have the same enabled state, ensure that this one is
            // fully displayed, with no empty cells for its days,
            // times, etc. If the two have the same enabled state,
            // see if this one is simply a repeat of the previous
            // one, and if so, skip it.
            if ($last_call_time['enabled_choice'] != $call_time['enabled_choice']) {
                $last_day = $last_call_time['day_of_week'];
                $last_call_time = $EMPTY_CALL_TIME;
                if ($call_time['day_of_week'] == $last_day) {
                    $last_call_time['day_of_week'] = $last_day;
                }
            } elseif (areArrayValuesEqual($call_time, $last_call_time, array(
                'day_of_week', 'earliest_time', 'latest_time', 'takes_calls',
                'takes_texts', 'takes_alerts', 'language_choice', 'staff_name'))) {
                continue;
            }

            // Add the start of a table row.
            echo "<tr>";

            // Get cell decorations for the contents of any table
            // cell for this call time entry.
            $cell_decorations = getCellDecorations(
                $call_time,
                'enabled_choice',
                'takes_calls',
                'takes_texts'
            );

            // Day and times.
            $force_display = createCallTimeTableCell(
                $call_time,
                $last_call_time,
                'day_of_week',
                $cell_decorations,
                false
            );
            $force_display = createCallTimeRangeTableCells(
                $call_time,
                $last_call_time,
                'earliest_time',
                'latest_time',
                $cell_decorations,
                $force_display
            );

            // Name, number, and language.
            $force_display = createCallTimeTableCell(
                $call_time,
                $last_call_time,
                'staff_name',
                $cell_decorations,
                $force_display
            );
            $force_display = createCallTimeTablePhoneCell(
                $call_time,
                $last_call_time,
                'phone',
                $cell_decorations,
                $force_display
            );
            $force_display = createCallTimeTableCell(
                $call_time,
                $last_call_time,
                'language_choice',
                $cell_decorations,
                $force_display
            );

            // Types of calls/messages that can be received.
            createCallTimeTypesTableCell($call_time, $last_call_time, array(
                'takes_texts' => "texts",
                'takes_calls' => "calls",
                'takes_alerts' => "answer alerts"
            ), $cell_decorations, $force_display);

            // Remember this call time for the next one, so that values
            // that have not changed can be output as blank table cells.
            $last_call_time = $call_time;

            // Create the removal confirmation message.
            $confirmation_message = "Are you sure you want to remove this call time?";
            if ($call_time['original_day'] == "all") {
                $confirmation_message .=
                    " It is scheduled for every day, so deleting it will ".
                    "remove it from all seven days of the week, not just ".
                    "this day.";
            } elseif ($call_time['original_day'] == "weekends") {
                $confirmation_message .=
                    " It is scheduled for both weekend days, so deleting it ".
                    "will remove it from both such days, not just this day.";
            } elseif ($call_time['original_day'] == "weekdays") {
                $confirmation_message .=
                    " It is scheduled for all weekdays, so deleting it will ".
                    "remove it from all such days, not just this day.";
            }

            // Action buttons.?>
            <td>
              <a href="hotline_staff.php?display_type=chronological&action=editcalltimemodal&id=<?php
                    echo $call_time['id'] ?>"
                    title="Edit this call time">
                  <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
              <a href="hotline_staff.php?display_type=chronological&action=removecalltime&id=<?php
                    echo $call_time['id'] ?>"
                  onClick="return confirm('<?php echo $confirmation_message; ?>');"
                  title="Remove this call time">
               <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>
            </td>
            <?php
            echo "</tr>";
        }

        // Finish the table.?>
            </tbody>
          </table>
        </div>
        <?php
    }
}

/**
* Get the call times using a hybrid query.
*
* @return array List of call times ordered chronologically.
*/
function getResultsUsingHybridQuery()
{

    // Get the multi-day call times.
    $multiDayResults = array();
    foreach (array("all", "weekends", "weekdays") as $dayType) {

        // Query the database for the call times with this particular
        // multi-day specifier.
        $query =
            "SELECT DISTINCT ".
                "call_times.id, ".
                "call_times.day AS original_day, ".
                "call_times.day + 0 AS original_day_ordinal, ".
                "call_times.earliest AS earliest_time, ".
                "call_times.latest AS latest_time, ".
                "call_times.receive_calls AS takes_calls, ".
                "call_times.receive_texts AS takes_texts, ".
                "call_times.receive_call_answered_alerts AS takes_alerts, ".
                "call_times.enabled AS enabled_choice, ".
                "languages.language AS language_choice, ".
                "contacts.contact_name AS staff_name, ".
                "contacts.phone ".
            "FROM ".
                "call_times ".
            "LEFT JOIN ".
                "languages ON languages.id = call_times.language_id ".
            "LEFT JOIN ".
                "contacts ON contacts.id = call_times.contact_id ".
            "WHERE ".
                "day = '".$dayType."' ";
        if (!db_db_query($query, $multiDayResults[$dayType], $error)) {
            echo $error;
        }
    }

    // Query the database for the call times with single-day specifiers.
    $query =
        "SELECT DISTINCT ".
            "call_times.id, ".
            "call_times.day AS original_day, ".
            "call_times.day + 0 AS original_day_ordinal, ".
            "call_times.day AS day_of_week, ".
            "call_times.day - 4 AS day_ordinal, ".
            "call_times.earliest AS earliest_time, ".
            "call_times.latest AS latest_time, ".
            "call_times.receive_calls AS takes_calls, ".
            "call_times.receive_texts AS takes_texts, ".
            "call_times.receive_call_answered_alerts AS takes_alerts, ".
            "call_times.enabled AS enabled_choice, ".
            "languages.language AS language_choice, ".
            "contacts.contact_name AS staff_name, ".
            "contacts.phone ".
        "FROM ".
            "call_times ".
        "LEFT JOIN ".
            "languages ON languages.id = call_times.language_id ".
        "LEFT JOIN ".
            "contacts ON contacts.id = call_times.contact_id ".
        "WHERE ".
            "day NOT IN ('all','weekdays', 'weekends') ".
        "ORDER BY ".
            "day_ordinal, ".
            "earliest_time, ".
            "latest_time, ".
            "staff_name, ".
            "takes_calls, ".
            "takes_texts, ".
            "takes_alerts, ".
            "language_choice";
    if (!db_db_query($query, $call_times, $error)) {
        echo $error;
    }

    // Iterate through the multi-day results, adding each in turn
    // to the single-day results list once for every day it
    // represents. Thus, an 'all' result is added seven times, once
    // for each day of the week; a 'weekends' result is added two
    // times, one per weekend day; and so on.
    foreach (array(
        "all" => array("Sun" => 0, "Mon" => 1, "Tue" => 2, "Wed" => 3,
                        "Thu" => 4, "Fri" => 5, "Sat" => 6),
        "weekends" => array("Sun" => 0, "Sat" => 6),
        "weekdays" => array("Mon" => 1, "Tue" => 2, "Wed" => 3,
                        "Thu" => 4, "Fri" => 5)
    ) as $grouping => $days) {
        foreach ($multiDayResults[$grouping] as $multiDayResult) {
            foreach ($days as $day => $ordinal) {

                // Copy he multi-day result, then insert into it
                // valuesfor the 'day_of_week' and 'day_ordinal'
                // indices. Then add it to the list of single-day
                // results.
                $result = $multiDayResult;
                $result["day_of_week"] = $day;
                $result["day_ordinal"] = $ordinal;
                $call_times[] = $result;
            }
        }
    }

    // Sort the results to complete the merge.
    usort($call_times, "compareCallTimesChronologically");

    return $call_times;
}

/**
* Get the call times using a pure SQL query.
*
* @return array List of call times ordered chronologically.
*/
function getResultsUsingPureSqlQuery()
{

    // Build the query string.
    $dayOrdinal = 0;
    $query = "";
    foreach (array(
        "Sun" => "weekends",
        "Mon" => "weekdays",
        "Tue" => "weekdays",
        "Wed" => "weekdays",
        "Thu" => "weekdays",
        "Fri" => "weekdays",
        "Sat" => "weekends"
    ) as $day => $grouping) {
        if ($dayOrdinal > 0) {
            $query = $query." UNION ";
        }
        $query .=
            "(SELECT DISTINCT ".
                "call_times.id, ".
                "call_times.day AS original_day, ".
                "call_times.day + 0 AS original_day_ordinal, ".
                "'". $day. "' AS day_of_week, ".
                $dayOrdinal. " AS day_ordinal, ".
                "call_times.earliest AS earliest_time, ".
                "call_times.latest AS latest_time, ".
                "call_times.receive_calls AS takes_calls, ".
                "call_times.receive_texts AS takes_texts, ".
                "call_times.receive_call_answered_alerts AS takes_alerts, ".
                "call_times.enabled AS enabled_choice, ".
                "languages.language AS language_choice, ".
                "contacts.contact_name AS staff_name, ".
                "contacts.phone ".
            "FROM ".
                "call_times ".
            "LEFT JOIN ".
                "languages ON languages.id = call_times.language_id ".
            "LEFT JOIN ".
                "contacts ON contacts.id = call_times.contact_id ".
            "WHERE ".
                "day IN ('all', '". $grouping. "', '". $day. "')".
            ")";
        $dayOrdinal++;
    }
    $query .=
        " ORDER BY ".
            "day_ordinal, ".
            "earliest_time, ".
            "latest_time, ".
            "staff_name, ".
            "takes_calls, ".
            "takes_texts, ".
            "takes_alerts, ".
            "language_choice, ".
            "enabled_choice, ".
            "original_day_ordinal";

    // Execute the query.
    if (!db_db_query($query, $call_times, $error)) {
        echo "$error";
    }

    return $call_times;
}

/**
* Compare the two specified call times chronologically.
*
* @param array &$call_time1
*   First call time to be compared.
* @param array &$call_time2
*   Second call time to be compared.
* @return int Number that is less than, equal to, or greater than 0
*   if the first call time is considered to be respectively less than,
*   equal to, or greater than the second.
*/
function compareCallTimesChronologically(&$call_time1, &$call_time2)
{

    // Compare the day of the week and the start and end times first.
    foreach (array("day_ordinal", "earliest_time", "latest_time") as $index) {
        if ($call_time1[$index] != $call_time2[$index]) {
            return $call_time1[$index] - $call_time2[$index];
        }
    }

    // Next, compare the staff member's name.
    if (($value = strcmp(
        $call_time1["staff_name"],
            $call_time2["staff_name"]
    )) != 0) {
        return $value;
    }

    // Then compare the flags indicating what types of messages the
    // staff member takes.
    foreach (array("takes_calls", "takes_texts", "takes_alerts") as $index) {
        if ($call_time1[$index] != $call_time2[$index]) {
            return ($call_time1[$index] == "y" ? -1 : 1);
        }
    }

    // Next, compare the language used by the staff member.
    if (($value = strcmp(
        $call_time1["language_choice"],
            $call_time2["language_choice"]
    )) != 0) {
        return $value;
    }

    // Then compare the enabled flag.
    if ($call_time1["enabled_choice"] != $call_time2["enabled_choice"]) {
        return ($call_time1["enabled_choice"] == "y" ? -1 : 1);
    }

    // Finally, compare the original day ordinal, so that 'all' comes
    // before 'weekends', and so on.
    return $call_time1["original_day_ordinal"] - $call_time2["original_day_ordinal"];
}

?>
