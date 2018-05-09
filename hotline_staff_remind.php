<?php
/**
* @file
* Staff Remind
*
* Script to be run every fifteen minutes that reminds hotline staff of upcoming
* shift changes. It should be run at 10 minutes, 25 minutes, 40 minutes, and
* 55 minutes past each hour, using a crontab entry like this:
*
* 10-59/15 * * * * php -f <path-to-hotline>/hotline/hotline_staff_remind.php 2>&1 | /usr/bin/logger -t hotline-cron
*
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

// Ensure this script is being run from the command line.
if (stripos(php_sapi_name(), "cli") === false) {
    exit;
}

db_databaseConnect();

// Get the first hotline number.
if (sms_getFirstHotline($hotlineNumber, $hotlinePrompts, $error)) {

    // Get the current time, and from that derive the lower and upper
    // bounds for the time range in which to look for shift changes.
    $timestamp = time();
    $currentTime = date("H:i", $timestamp);
    $day = date("D", $timestamp);
    $startTime = date("H:i", strtotime("+5 minutes", strtotime($currentTime)));
    $endTime = date("H:i", strtotime("+20 minutes", strtotime($currentTime)));
    if ($startTime == "00:00") {
        $day = date("D", strtotime("+1 day", $timestamp));
    }

    // Get a list of staff members that are scheduled to start their
    // shifts in the above-figured time range, and another list of those
    // that are scheduled to end their shifts during that same time
    // period. For each list, notify the staff members of the upcoming
    // shift changes.
    $contacts = array();
    foreach (array("earliest", "latest") as $boundary) {

        // Get the shift change information for this change type.
        $valid = sms_getShiftChangeContacts(
            $contacts,
            $day,
            $startTime,
            $endTime,
            $boundary,
            $error
        );
        if ($valid) {

            // Prune through the results, finding for each staff member
            // in the results the earliest entry (if going on shift) or
            // the latest entry (if going off shift).
            $shiftChangesForContacts = array();
            foreach ($contacts as $contact) {
                if (isset($shiftChangesForContacts[$contact['contact_name']])) {
                    $recordedTime = strtotime($shiftChangesForContacts[$contact['contact_name']][$boundary]);
                    $newTime = strtotime($contact[$boundary]);
                    if ((($boundary == "earliest") &&
                            ($recordedTime > $newTime)) ||
                            (($boundary == "latest") &&
                            ($recordedTime < $newTime))) {
                        $shiftChangesForContacts[$contact['contact_name']] = $contact;
                    }
                } else {
                    $shiftChangesForContacts[$contact['contact_name']] = $contact;
                }
            }

            // Iterate through the pruned results, sending a text to each
            // staff member for whom an entry was found.
            foreach ($shiftChangesForContacts as $contactName => $contact) {
                $numbers = array($contact['phone']);
                $text = ($boundary == "earliest" ? $STAFF_REMINDER_START_SHIFT :
                        $STAFF_REMINDER_END_SHIFT);
                $text .= date("g:i a", strtotime($contact[$boundary]));
                if (sms_send($numbers, $text, $error, $hotlineNumber) == false) {
                    db_error("Error: Could not send shift change text to ".
                            $contact['contact_name']." at ".$contact['phone'].": ".
                            $error);
                }
            }
        } else {
            db_error("Error: Could not get shift change contacts: ".$error);
        }
    }
} else {
    db_error("Fatal error: no hotline number found.");
}

db_databaseDisconnect();
