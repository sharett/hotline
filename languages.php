<?php
/**
* @file
* List of languages supported
*
* 
*/

require_once 'config.php';

include 'header.php';

if (!db_db_query("SELECT * FROM languages", $languages, $error)) {
    echo $error;
}

?>
          <h2 class="sub-header">Languages</h2>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
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
