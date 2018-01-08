<?php
/**
* @file
* Communications log
*
* Show the latest calls and texts.
*
*
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$start = isset($_REQUEST['s']) ? (int)$_REQUEST['s'] : 0;
$mark = isset($_REQUEST['mark']) ? (int)$_REQUEST['mark'] : 0;
$unmark = isset($_REQUEST['unmark']) ? (int)$_REQUEST['unmark'] : 0;
$ph = isset($_REQUEST['ph']) ? trim($_REQUEST['ph']) : '';

// Settings
$page = 50;

// Mark an item as responded, or not responded
if ($mark) {
    sms_markCommunication($mark, true /* mark responded */, $error);
} elseif ($unmark) {
    sms_markCommunication($unmark, false /* mark not responded */, $error);
}

// Communications

// build the query to specify which numbers to select
$where_sql_array = array();
if (isset($BROADCAST_CALLER_IDS) && $ph == 'all_broadcast') {
	// show logs for all broadcast numbers
	foreach ($BROADCAST_CALLER_IDS as $number) {
		$where_sql_array[] =
			"communications.phone_from = '".addslashes($number)."' OR ".
            "communications.phone_to = '".addslashes($number)."'";
	}
} else {
	// show logs just for a single number
	$where_sql_array[] =
		"communications.phone_from = '".addslashes($ph)."' OR ".
		"communications.phone_to = '".addslashes($ph)."'";
}
$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
    "FROM communications ".
    "LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
    "LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
    ($ph ? ("WHERE " . implode(" OR ", $where_sql_array) . " ") : '') .
    "ORDER BY communication_time DESC LIMIT ".addslashes($start).",{$page}";
if (!db_db_query($sql, $comms, $error)) {
    ?><div class="alert alert-danger" role="alert"><?php echo $error ?></div><?php
}

?>
        <h2 class="sub-header">Hotline</h2>
          <ul class="nav nav-pills">
            <li role="presentation"><a href="hotline_active_calls.php">Active Calls</a></li>
            <li role="presentation"><a href="hotline_staff.php">Staff</a></li>
            <li role="presentation"><a href="hotline_blocks.php">Blocks</a></li>
            <li role="presentation"><a href="hotline_languages.php">Languages</a></li>
            <li role="presentation" class="active"><a href="log.php?ph=<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? urlencode($hotline_number) : '' ?>">Log</a></li>
          </ul>
          <br />

          <h3 class="sub-header">Log</h3>
          <p>Click a phone number to view all communications with that number.  Click the response button or link to mark or
          unmark an item as responded to.</p>
          <form id="choose_number" action="log.php" method="GET" class="form-inline">
		    <input type="hidden" name="s" value="<?php echo $start ?>">
		    <div class="form-group">
			<label for="text-message">View log for:</label>
			<select class="form-control" name="ph" onChange="this.form.submit()">
			 <option value="">(all numbers)</option>
<?php
    foreach ($HOTLINES as $hotline_number => $hotline) {
        ?>
			 <option value="<?php echo $hotline_number ?>"
				<?php if ($ph == $hotline_number) {
            echo "selected";
        } ?>><?php echo $hotline_number ?> (<?php echo $hotline['name'] ?>)</option>
<?php
    }
    if (isset($BROADCAST_CALLER_IDS)) {
?>
			 <option value="all_broadcast" <?php if ($ph == 'all_broadcast') {
            echo "selected";
    } ?>>(all broadcast numbers)</option>
<?php
	foreach ($BROADCAST_CALLER_IDS as $broadcast_caller_id) {
        ?>
			 <option value="<?php echo $broadcast_caller_id ?>"
				<?php if ($ph == $broadcast_caller_id) {
            echo "selected";
        } ?>><?php echo $broadcast_caller_id ?> (Broadcast)</option>
<?php
		}
    }
?>
			</select>
 		   </div>
           <button class="btn btn-success" id="button-text">View</button>
		  </form>
<?php
include 'communications.php';

?>
<p>
<?php
// show the previous button if we are not at the beginning
if ($start > 0) {
    ?>
 <a class="btn btn-success" href="log.php?s=<?php echo $start - $page ?>&ph=<?php echo urlencode($ph) ?>" role="button">&lt;&lt; Prev</a>
<?php
}
// show the next button if there are more to show
if (count($comms) >= $page) {
    ?>
 <a class="btn btn-success" href="log.php?s=<?php echo $start + $page ?>&ph=<?php echo urlencode($ph) ?>" role="button">Next &gt;&gt;</a>
<?php
}
?>
</p>
<?php
// form for exporting of data
$export['phone'] = $ph;
include 'communications_export.php';

include 'footer.php';

?>
