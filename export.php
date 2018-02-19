<?php
/**
* @file
* Export a communicatons log to a CSV file
*
* Show the latest calls and texts.
*
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

db_databaseConnect();

$type = $_REQUEST['type'];
$export = $_REQUEST['export'];

if ($type == 'communications') {
    $result = exportCommunicationsToCsv($export, $error);
} elseif ($type == 'calltimes') {
    $result = exportCallTimesToCsv($export, $error);
} else {
    $result = false;
    $error = "Unrecognized export type \"".$type."\".";
}
if ($result == false) {
    include 'header.php'; ?>
<div class="alert alert-danger" role="alert"><?php echo $error ?></div>
<?php
    include 'footer.php';
}

db_databaseDisconnect();

/**
* Load the communications data and output it as a CSV file
*
* Export fields:
* 	Date/time
* 	From
* 	To
* 	Content
* 	Type
* 	Responded
*
* @param array $export
*	'earliest' => Earliest date/time to include
* 	'latest' => Latest date/time to include
* 	'phone' => If set, limits the export to records to or from this number
* 	'type' => limit only to this type of record
* @param string &$error
*   An error if one occurred.
*
* @return bool
*   True unless an error occurred
*/
function exportCommunicationsToCsv($export, &$error)
{
    $error = '';

    // determine what to include
    $earliest = $export['earliest'] ? strtotime($export['earliest']) : '';
    $latest = $export['latest'] ? strtotime($export['latest']) : '';
    $phone = trim($export['phone']);
    if ($phone) {
        if (!sms_normalizePhoneNumber($phone, $error)) {
            return false;
        }
    }
    $type = trim($export['type']);

    // load the matching records
    $sql = "SELECT communication_time,phone_from,phone_to,body,status,responded FROM communications WHERE ".
        ($earliest ? ("communication_time > '".addslashes(date("Y-m-d H:i:s", $earliest))."' AND ") : '') .
        ($latest ? ("communication_time < '".addslashes(date("Y-m-d H:i:s", $latest))."' AND ") : '') .
        ($phone ? ("(phone_from = '".addslashes($phone)."' OR phone_to = '".addslashes($phone)."') AND ") : '') .
        ($type ? ("status = '".addslashes($type)."' AND ") : '') .
        " 1 ".
        "ORDER BY communication_time DESC";
    if (!db_db_query($sql, $comms, $error)) {
        return false;
    }

    // output the data
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=export-log.csv");

    $fp = fopen("php://output", "w");
    fwrite($fp, "time,from,to,content,type,responded\n");
    foreach ($comms as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    return true;
}

/**
* Load the call times data and output it as a CSV file
*
* Export fields:
* 	Date/time
* 	From
* 	To
* 	Content
* 	Type
* 	Responded
*
* @param array $export
*	'contact_id' => Identifier of the contact for which to export; if empty,
*                   all contacts' call times are to be exported.
* @param string &$error
*   An error if one occurred.
*
* @return bool
*   True unless an error occurred
*/
function exportCallTimesToCsv($export, &$error)
{
    $error = '';

    // Determine what to include.
    $contact_id = trim($export['contact_id']);
    if ($contact_id) {
        $sql = "SELECT * FROM contacts WHERE id = '".$contact_id."'";
    } else {
        $sql = "SELECT * FROM contacts ORDER BY contact_name";
    }

    // Write the headers and start the output file.
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=export-log.csv");
    $fp = fopen("php://output", "w");

    // Get all the call times for the contact(s), and write them to
    // the CSV file.
    if (db_db_query($sql, $contacts, $error)) {
        $call_times = array();
        foreach ($contacts as $contact) {
            $call_times = array();
            $sql = "SELECT '".$contact['contact_name']."' AS name,' ".$contact['phone'].
                    "' AS number, day, earliest, latest, language_id, ".
                    "IF(receive_texts = 'y', 1, 0), IF(receive_calls = 'y', 1, 0), ".
                    "IF(receive_call_answered_alerts = 'y', 1, 0) ".
                    "FROM call_times WHERE contact_id='{$contact['id']}' AND enabled='y' ".
                    "ORDER BY day, earliest, latest, language_id";
            if (!db_db_query($sql, $call_times, $error)) {
                echo $error;
                break;
            }
            fwrite($fp, "name,number,day,earliest,latest,language_id,receive_texts,".
                        "receive_calls,receive_call_answered_alerts\n");
            foreach ($call_times as $row) {
                fputcsv($fp, $row);
            }
        }
    } else {
        echo $error;
    }

    fclose($fp);

    return ($error == '');
}

?>
