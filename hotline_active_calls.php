<?php
/**
* @file
* Active
*
* Display currently active calls.
*
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

?>
		<h2 class="sub-header">Hotline</h2>
   		  <ul class="nav nav-pills">
            <li role="presentation" class="active"><a href="hotline_active_calls.php">Active Calls</a></li>
			<li role="presentation"><a href="hotline_staff.php">Staff</a></li>
			<li role="presentation"><a href="hotline_blocks.php">Blocks</a></li>
			<li role="presentation"><a href="hotline_languages.php">Languages</a></li>
			<li role="presentation"><a href="log.php?ph=<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? urlencode($hotline_number) : '' ?>">Log</a></li>
		  </ul>
		  <br />
<?php

// Active calls from and to the hotlines
$calls = array();
foreach ($HOTLINES as $hotline_number => $hotline) {
    sms_getActiveCalls($hotline_number, '', $calls_from, $error);
    sms_getActiveCalls('', $hotline_number, $calls_to, $error);
    $calls = array_merge($calls_from, $calls_to, $calls);
}

if (count($calls)) {
    ?>
        <h3 class="sub-header">Active calls</h3>
		  <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>from</th>
                  <th>to</th>
                  <th>status</th>
                  <th>timing</th>
                </tr>
              </thead>
              <tbody>
<?php
    foreach ($calls as $call) {
        $duration = strtotime($call['EndTime']) - strtotime($call['StartTime']); ?>
                <tr>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($call['From']) . '">' . $call['From'] . '</a>'; ?></td>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($call['To']) . '">' . $call['To'] . '</a>'; ?></td>
                  <td><?php echo $call['Status']?></td>
                  <td><?php echo date("m/d/y h:i a", strtotime($call['StartTime'])) .
                                 ' (' . $duration . ' seconds)' ?></td>
                </tr>
<?php
    } ?>
              </tbody>
            </table>
          </div>
<?php
} else {
        ?>
        <p>There are currently no active calls.</p>
<?php
    }
?>
