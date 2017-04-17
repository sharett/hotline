<?php
/**
* @file
* List of languages supported
*
* 
*/

require_once 'config.php';

include 'header.php';

if (!db_db_query("SELECT * FROM languages ORDER BY keypress", $languages, $error)) {
    echo $error;
}

?>
		  <h2 class="sub-header">Hotline</h2>
   		    <ul class="nav nav-pills">
			  <li role="presentation"><a href="hotline_staff.php">Staff</a></li>
			  <li role="presentation"><a href="hotline_blocks.php">Blocks</a></li>
			  <li role="presentation" class="active"><a href="hotline_languages.php">Languages</a></li>
			  <li role="presentation"><a href="contact.php?ph=<?php echo $HOTLINE_CALLER_ID ?>&hide=1">Log</a></li>
		    </ul>
		    <br />
		    
          <h2 class="sub-header">Languages</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
				  <th>keypress</th>
                  <th>language</th>
                  <th>prompt</th>
                  <th>twilio code</th>
                </tr>
              </thead>
              <tbody>
<?php
foreach ($languages as $language) {
?>
                <tr>
				  <td><?php echo $language['keypress']?></td>
                  <td><?php echo $language['language']?></td>
                  <td><?php echo $language['prompt']?></td>
                  <td><?php echo $language['twilio_code']?></td>
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
