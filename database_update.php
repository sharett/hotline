<?php
/**
* @file
* Database update file. This should be updated as the application
* evolved to know how to perform updates on an existing database to
* bring it up to current spec.
*
* For example, if a new column is added to a table, this file's
* algorithm should look for the new column in the table, and if not
* found, should add it and do whatever is necessary to properly
* populate it. The idea is to ensure that it never corrupts an
* existing already up-to-date database, of course.
*/


require_once 'config.php';

include 'header.php';

/**
* Display the specified error message.
*
* @param string $error
*   Error message to be displayed.
*/
function displayError($error)
{
    echo "<div class=\"alert alert-danger\" role=\"alert\">".$error."</div>";
}

/**
* Display the specified success message.
*
* @param string $message
*   Success message to be displayed.
*/
function displaySuccess($message)
{
    echo "<div class=\"alert alert-success\" role=\"alert\">".$message."</div>";
}

/**
* Unlock any locked tables, displaying the specified error message if any.
*
* @param string $previousError
*   Error message to be displayed if the unlock succeeds, or to be displayed
*   along with the unlock error message if the unlock fails.
* @return bool
*   True if the table was unlocked, false otherwise.
*/
function unlockTablesForDatabaseUpdate($previousError)
{
    if (!db_db_command("UNLOCK TABLES", $error)) {
        if (empty($previousError)) {
            displayError("The call times table update could not be completed ".
                    "because it could not be unlocked in the database.");
        } else {
            displayError($previousError."<br><br>Additionally, The call times ".
                    "table could not be unlocked in the database.");
        }
        return false;
    } elseif (!empty($previousError)) {
        displayError($previousError);
    }
    return true;
}

// Lock the table.
if (!db_db_command("LOCK TABLES call_times WRITE", $error)) {
    displayError("The call times table could not updated because it ".
            "could not be locked in the database.");
    return;
}

// Query the database for any duplicate sets of entries. Such a set is
// a set of entries that share the same contact, day, earliest, latest,
// received texts/calls/call answered alerts, languages, and enabled
// values.
$query = "SELECT GROUP_CONCAT(id) AS ids, contact_id, day, earliest, latest, ".
        "receive_texts, receive_calls, receive_call_answered_alerts, ".
        "language_id, enabled, count(*) FROM call_times GROUP BY ".
        "contact_id, day, earliest, latest, receive_texts, receive_calls, ".
        "receive_call_answered_alerts, language_id, enabled ".
        "HAVING count(*) > 1";
if (!db_db_query($query, $duplicateResults, $error)) {
    unlockTablesForDatabaseUpdate("The call times table could not be checked ".
            "for duplicate entries because a database error occurred: ".$error);
    return;
}

// If any duplicate entries are found, iterate through the sets of
// their identifiers, compiling a list of all such identifiers in each
// set besides the first one, and then delete all the entries associated
// with the compiled identifiers.
if (!empty($duplicateResults)) {
    $duplicateIds = array();
    foreach ($duplicateResults as $duplicateResult) {
        $ids = explode(",", $duplicateResult["ids"]);
        array_shift($ids);
        $duplicateIds = array_merge($duplicateIds, $ids);
    }
    $sql = "DELETE FROM call_times WHERE id IN (".implode(",", $duplicateIds).")";
    if (!db_db_command($sql, $error)) {
        unlockTablesForDatabaseUpdate("The call times table could not have ".
                "duplicate entries pruned because a database error occurred: ".
                $error);
        return;
    }
    $numDuplicates = count($duplicateIds);
    if ($numDuplicates == 1) {
        displaySuccess("The call times table had a single duplicate entry ".
                "pruned.");
    } else {
        displaySuccess("The call times table had ".$numDuplicates." duplicate ".
                "entries pruned.");
    }
}

// Determine whether or not the entry_id column exists in the table, and
// if it does not, create it and populate it.
$query = "SHOW COLUMNS FROM call_times LIKE 'entry_id'";
if (!db_db_getone($query, $results, $error)) {
    unlockTablesForDatabaseUpdate("The call times table could not be checked ".
            "for the entry_id column's existence because a database error ".
            "occurred: ".$error);
    return;
}
if (!$results) {

    // Add the column to the table.
    $sql = "ALTER TABLE call_times ADD entry_id int(11) UNSIGNED NOT NULL ".
            "DEFAULT '0' AFTER id";
    if (!db_db_command($sql, $error)) {
        unlockTablesForDatabaseUpdate("The call times table could not have ".
                "the entry_id column added because a database error occurred: ".
                $error);
        return;
    }

    // Find all the entries, grouped so that all entries that need the
    // same entry identifier are together.
    $query = "SELECT GROUP_CONCAT(id) AS ids, contact_id, day, earliest, latest, ".
            "receive_texts, receive_calls, receive_call_answered_alerts, ".
            "enabled, count(*) FROM call_times GROUP BY contact_id, day, ".
            "earliest, latest, receive_texts, receive_calls, ".
            "receive_call_answered_alerts, enabled";
    if (!db_db_query($query, $entryResults, $error)) {
        unlockTablesForDatabaseUpdate("The call times table could not have ".
                "the new entry identifier column populated because a ".
                "database error occurred: ".$error);
        return;
    }

    // Iterate through the results, setting each group of records to use
    // a common entry identifier.
    if (!empty($entryResults)) {
        $entryIdentifier = 1;
        foreach ($entryResults as $entryResult) {
            $sql = "UPDATE call_times SET entry_id = ".$entryIdentifier.
                    " WHERE id IN (".$entryResult["ids"].")";
            if (!db_db_command($sql, $error)) {
                unlockTablesForDatabaseUpdate("The call times table could not ".
                        "have the new entry identifier column populated ".
                        "because a database error occurred: ".$error);
                return;
            }
            $entryIdentifier++;
        }
    }

    displaySuccess("The call times table had the entry identifier column ".
            "added and populated for all existing entries.");
}

// Unlock the table.
if (!unlockTablesForDatabaseUpdate(null)) {
    return;
}

displaySuccess("Update completed successfully.");
