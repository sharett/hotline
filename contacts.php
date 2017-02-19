<?php
/**
* @file
* Contacts
*
* Display volunteers on duty now, and all contacts.
* 
*/

require_once 'config.php';
require_once $TWILIO_INTERFACE_BASE . 'lib_sms.php';

include 'header.php';

// On duty now
?>
        <h2 class="sub-header">On duty now</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>name</th>
                  <th>phone</th>
                </tr>
              </thead>
              <tbody>
<?php
getActiveContacts($contacts, 0 /* any language */, false /*texting doesn't matter*/, $error);
foreach ($contacts as $contact) {
?>
                <tr>
                  <td><?php echo $contact['contact_name']?></td>
                  <td><?php
                  echo '<a href="contact.php?ph=' . urlencode($contact['phone']) . '">' . $contact['phone'] . '</a>';
                  ?></td>
                </tr>
<?php
}
?>
              </tbody>
            </table>
          </div>


<?php
// Contact list
if (!pp_db_query("SELECT * FROM contacts ORDER BY contact_name", $contacts, $error)) {
    echo $error;
}

?>
          <h2 class="sub-header">Contacts</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>name</th>
                  <th>phone</th>
                  <th>e-mail</th>
                  <th>call times/notes</th>
                </tr>
              </thead>
              <tbody>
<?php
foreach ($contacts as $contact) {
    $sql = "SELECT call_times.*,languages.language FROM call_times ".
	"LEFT JOIN languages ON languages.id = call_times.language_id ".
        "WHERE contact_id='{$contact['id']}'";
    if (!pp_db_query($sql, $call_times, $error)) {
        echo $error;
    }
?>
                <tr>
                  <td><?php echo $contact['contact_name']?></td>
                  <td><?php 
                  echo '<a href="contact.php?ph=' . urlencode($contact['phone']) . '">' . $contact['phone'] . '</a>';
                  ?></td>
                  <td><?php echo $contact['email']?></td>
                  <td><?php
    // display each call_times records, crossed out if disabled
    foreach ($call_times as $call_time) {
		if ($call_time['enabled'] == 'n') {
			echo "<s>";
		}
        echo "day: {$call_time['day']}; time: ".
			date("h:i a", strtotime($call_time['earliest'])) . " to ".
			date("h:i a", strtotime($call_time['latest'])) .", {$call_time['language']}, ";
        if ($call_time['receive_texts'] == 'y') {
            echo "texts";
        } else {
            echo "no texts";
        }
		if ($call_time['enabled'] == 'n') {
			echo "</s>";
		}
        echo "<br />\n";
    }
?><?php echo $contact['notes']?>

                  </td>
                </tr>
<?php
}
?>
              </tbody>
            </table>
          </div>
<?php
include 'footer.php';

?>
