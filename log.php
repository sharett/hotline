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

// Settings
$page = 50;

// Mark an item as responded, or not responded
if ($mark) {
	sms_markCommunication($mark, true /* mark responded */, $error);
} else if ($unmark) {
	sms_markCommunication($unmark, false /* mark not responded */, $error);
}

// Communications
$sql = "SELECT communications.*,contacts_from.contact_name AS from_contact, contacts_to.contact_name AS to_contact ".
	"FROM communications ".
	"LEFT JOIN contacts AS contacts_from ON contacts_from.phone = communications.phone_from ".
	"LEFT JOIN contacts AS contacts_to ON contacts_to.phone = communications.phone_to ".
	"ORDER BY communication_time DESC LIMIT ".addslashes($start).",{$page}";
if (!db_db_query($sql, $comms, $error)) {
?><div class="alert alert-danger" role="alert"><?php echo $error ?></div><?php
}

?>
          <h2 class="sub-header">Log</h2>
          <p>Click a phone number to view all communications with that number.  Click the response button or link to mark or 
          unmark an item as responded to.</p>
<!-- ?php
include 'communications.php';

? -->
<div id="auto_refresh_div"></div>
<p>
<?php
// show the previous button if we are not at the beginning
if ($start > 0) {
?>
 <a class="btn btn-success" href="log.php?s=<?php echo $start - $page ?>" role="button">&lt;&lt; Prev</a>
<?php
}
// show the next button if there are more to show
if (count($comms) >= $page) {
?>
 <a class="btn btn-success" href="log.php?s=<?php echo $start + $page ?>" role="button">Next &gt;&gt;</a>
<?php
}
?>
</p>
<?php
// form for exporting of data
include 'communications_export.php';

include 'footer.php';

?>
<!-- Autorefresh logs -->
<script>
  function auto_refresh(){
    $.ajax({
      url: "communications.php",
      cache: false,
      success: function(data){
         $("#auto_refresh_div").html(data);
      }
    });
  }

  $(document).ready(function(){

    auto_refresh(); //Call auto_load() function when DOM is Ready

  });

  //Refresh auto_refresh() function after 10000 milliseconds
  setInterval(auto_refresh,10000);
</script>
