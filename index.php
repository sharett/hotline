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
$mark = (int)$_REQUEST['mark'];

// Mark an item as responded to
if ($mark) {
	sms_markCommunication($mark, true /* mark responded */, $error);
}

// load the number of active broadcast numbers
$sql = "SELECT COUNT(*) FROM broadcast WHERE status='active'";
db_db_getone($sql, $broadcast_count, $error);

// load all communications that need to be responded to
$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
	"FROM communications ".
	"LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
	"LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
	"WHERE responded IS NULL AND (status = 'text' OR status = 'voicemail') AND ".
	"	phone_to = '".addslashes($HOTLINE_CALLER_ID) . "' AND ".
	"   LOWER(body) != 'off' AND LOWER(body) != 'on' ".
	"ORDER BY communication_time";
if (!db_db_query($sql, $comms, $error)) {
	echo $error;
}

// Home page
?>
          <h2 class="sub-header"><?php echo $WEBSITE_NAME ?></h2>
          <div class="container">
		   <div class="row">
			<div class="col-md-4">
			  <h3>Broadcast texts</h3>
			  <p>Administer the list and send texts to the <b><?php echo $broadcast_count ?></b> numbers in the database from the
			  <b><?php echo $BROADCAST_CALLER_ID ?></b> number.</p>
			  <p><a class="btn btn-success" href="broadcast.php" role="button">Broadcast</a></p>
			</div>
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
			  <p>View active hotline staff and make calls and texts from the <b><?php echo $HOTLINE_CALLER_ID ?></b> number.
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
				<a class="btn btn-success" href="staff.php" role="button">Staff</a>
				<a class="btn btn-success" href="languages.php" role="button">Languages</a>
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
