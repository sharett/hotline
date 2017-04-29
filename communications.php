<?php
/**
* @file
* Communications table
*
* Show the latest calls and texts in the $comms variable.
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

// URL parameters
$start = (int)$_REQUEST['s'];
$mark = (int)$_REQUEST['mark'];
$unmark = (int)$_REQUEST['unmark'];

// Settings
$page = 50;

// Mark an item as responded, or not responded
if ($mark) {
	sms_markCommunication($mark, true /* mark responded */, $error);
} else if ($unmark) {
	sms_markCommunication($unmark, false /* mark not responded */, $error);
}

db_databaseConnect();
// Communications
$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
	"FROM communications ".
	"LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
	"LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
	"ORDER BY communication_time DESC LIMIT ".addslashes($start).",{$page}";
if (!db_db_query($sql, $comms, $error)) {
    echo $error;
}


?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>when</th>
                  <th>from</th>
                  <th>to</th>
                  <th>content</th>
                  <th>type</th>
                  <th>response</th>
                </tr>
              </thead>
              <tbody>
<?php
// is the number to dial from specified?
$from_url = $from ? ("&from=" . urlencode($from)) : '';

foreach ($comms as $comm) {
	$not_responded = !$comm['responded'] && $comm['phone_to'] == $HOTLINE_CALLER_ID && 
	    ($comm['status'] == 'text' || $comm['status'] == 'voicemail') && 
	    strtolower($comm['body']) != 'off' && strtolower($comm['body']) != 'on';
?>
                <tr <?php if ($not_responded) echo 'class="warning"' ?>>
                  <td><?php echo date("m/d/y h:i a", strtotime($comm['communication_time'])); ?></td>
                  <td><?php 
    // the "from" number
    echo '<a href="contact.php?ph=' . urlencode($comm['phone_from']) . $from_url . '">' . $comm['phone_from'] . '</a>';
    if ($comm['from_contact']) {
		echo " ({$comm['from_contact']})";
	}
                  ?></td>
                  <td><?php 
    // the "to" number
	echo '<a href="contact.php?ph=' . urlencode($comm['phone_to']) . $from_url . '">' . $comm['phone_to'] . '</a>';
	if ($comm['to_contact']) {
		echo " ({$comm['to_contact']})";
	}
	              ?></td>
                  <td><?php 
    // the text body, voicemail link or other information
	if (substr($comm['body'], 0, 22) == 'https://api.twilio.com') {
		echo 'Voicemail: <a href="' . $comm['body'] . '">[listen]</a>';
	} else {
		echo $comm['body'];
	}
	// media
	if ($comm['media_urls']) {
		// display media received
		echo "<br />Media: ";
		$items = explode("\n", $comm['media_urls']);
		foreach ($items as $item) {
			if (!trim($item)) {
				// skip blank lines
				continue;
			}
			$detail = explode("\t", $item);
			echo '<a href="'.$detail[0].'">['.$detail[1].']</a> ';
		}
	}
                  ?></td>
                  <td><?php echo $comm['status']; ?></td>
                  <td>
<?php
    if ($comm['responded']) {
		$unmark_url = $_SERVER['PHP_SELF'] . "?ph=".urlencode($ph)."&s={$start}&unmark={$comm['id']}";
		$responded_time = date("m/d/y h:i a", strtotime($comm['responded'])); 
?>
	               <a href="<?php echo $unmark_url ?>" title="Click to mark as not responded"><?php echo $responded_time ?></a>
<?php
	} else if ($not_responded) {
		$mark_url = $_SERVER['PHP_SELF'] . "?ph=".urlencode($ph)."&s={$start}&mark={$comm['id']}";
?>
                   <a class="btn btn-warning" title="Click to mark as responded" href="<?php echo $mark_url ?>" role="button">
					<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
					<span class="sr-only">Mark responded</span>
				   </a>
<?php
	}
?>
                  </td>
                </tr>
<?php
}
?>
              </tbody>
            </table>
          </div>
<?php

db_databaseDisconnect();
?>
