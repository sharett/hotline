<?php
/**
* @file
* Communications table
*
* Show the latest calls and texts in the $comms variable.
* 
*/
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
foreach ($comms as $comm) {
	$not_responded = !$comm['responded'] && $comm['phone_to'] == $HOTLINE_CALLER_ID && 
	    ($comm['status'] == 'text' || $comm['status'] == 'voicemail');
?>
                <tr <?php if ($not_responded) echo 'class="warning"' ?>>
                  <td><?php echo date("m/d/y h:i a", strtotime($comm['communication_time'])); ?></td>
                  <td><?php 
    // the "from" number
    echo '<a href="contact.php?ph=' . urlencode($comm['phone_from']) . '">' . $comm['phone_from'] . '</a>';
    if ($comm['from_contact']) {
		echo " ({$comm['from_contact']})";
	}
                  ?></td>
                  <td><?php 
    // the "to" number
	echo '<a href="contact.php?ph=' . urlencode($comm['phone_to']) . '">' . $comm['phone_to'] . '</a>';
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
