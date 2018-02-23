<?php
/**
* @file
* Home page
*
* Display an overview of the broadcast text, hotline and logging information.
* Display a list of communications that need a response. 
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$mark = isset($_REQUEST['mark']) ? (int)$_REQUEST['mark'] : 0;

// Mark an item as responded to
if ($mark) {
	sms_markCommunication($mark, true /* mark responded */, $error);
}

// load the number of active broadcast numbers
$sql = "SELECT COUNT(*) FROM broadcast WHERE status='active'";
db_db_getone($sql, $broadcast_count, $error);

// load all communications that need to be responded to
$hotline_phones_to = array();
foreach ($HOTLINES as $hotline_number => $hotline) {
	$hotline_phones_to[] = "phone_to = '".addslashes($hotline_number) . "'";
}
$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
	"FROM communications ".
	"LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
	"LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
	"WHERE responded IS NULL AND (status = 'text' OR status = 'voicemail') AND ".
	(count($hotline_phones_to) ? ("(" . implode(" OR ", $hotline_phones_to) . ") AND ") : '') .
	"   LOWER(body) != 'off' AND LOWER(body) != 'on' ".
	"ORDER BY communication_time";
if (!db_db_query($sql, $comms, $error)) {
?><div class="alert alert-danger" role="alert"><?php echo $error ?></div><?php
}

// Home page
?>
          <h2 class="sub-header"><?php echo $WEBSITE_NAME ?></h2>
          <div class="container">
		   <div class="row">
<?php
if (isset($BROADCAST_CALLER_IDS)) {
?>
			<div class="col-md-4">
			  <h3>Broadcast texts</h3>
			  <p>Administer the list and send texts to the <b><?php echo $broadcast_count ?></b> numbers in the database from the
			  broadcast numbers: <b><?php echo implode(', ', $BROADCAST_CALLER_IDS) ?></b>.</p>
			  <p><a class="btn btn-success" href="broadcast.php" role="button">Broadcast</a></p>
			</div>
<?php
}
?>			
			<div class="col-md-4">
			  <h3>Hotline 
<?php
if (count($comms)) {
?>
			  <span class="badge" style="background-color: #f89406;"><?php echo count($comms) ?></span>
<?php
}
?>
			  </h3>
			  <p>View active hotline staff and make calls and texts from the hotline<?php echo (count($HOTLINES) == 1) ? '' : 's' ?>: <b>
<?php
$count = 0;
foreach ($HOTLINES as $hotline_number => $hotline) {
	echo $hotline_number . ' ('. $hotline['name'] . ')';
	echo (++$count == count($HOTLINES)) ? '. ' : ', ';
}
?>
			  </b>
<?php
if (count($comms)) {
	if (count($comms) == 1) {
		echo "There is <b>1</b> communication waiting for a response.";
	} else {
		echo "There are <b>". count($comms) . "</b> communications waiting for a response.";
	}	
}
?>
			  </p>
			  <p>
				<a class="btn btn-success" href="contact.php" role="button">Call / Text</a>
				<a class="btn btn-success" href="hotline_staff.php" role="button">Staff</a>
				<a class="btn btn-success" href="hotline_blocks.php" role="button">Blocks</a>
				<a class="btn btn-success" href="hotline_languages.php" role="button">Languages</a>
			  </p>
		   </div>
			<div class="col-md-4">
			  <h3>Log</h3>
			  <p>View a log of all texts and calls.</p>
			  <p><a class="btn btn-success" href="log.php" role="button">Log</a></p>
			</div>
		  </div>
		</div> <!-- /container -->
<?php

// display the communications table if needed
if (count($comms)) {
?>
	<h3 class="sub-header">Response needed</h3>
	<p>The following communications need a response.  Once you've responded, check the orange checkbox to mark 
	the item as responded to.</p>
<?php
	include 'communications.php';
}

?>
<br />
<footer><a href="https://github.com/sharett/hotline">View source code on Github</a></footer>
<?php

include 'footer.php';

?>
