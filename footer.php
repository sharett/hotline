<?php
/**
* @file
* Footer
*
* Displays the bottom of the HTML page.
* 
* config.php must be included before this file
* 
*/

?>
        </div>
      </div>
    </div>


<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<?php
if (!empty($include_calling)) {
?>
<!-- Twilio client interface -->
  <script type="text/javascript" src="//media.twiliocdn.com/sdk/js/client/v1.3/twilio.min.js"></script>
  <script src="call/call.js"></script>
<?php
}
?>

</body>
</html>
<?php

db_databaseDisconnect();
?>
