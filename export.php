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

$export = $_REQUEST['export'];

if (!exportToCsv($export, $error)) {
	include 'header.php';
?>
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

function exportToCsv($export, &$error)
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

?>
