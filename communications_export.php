<?php
/**
* @file
* Form to export communications to CSV file
*
* The export.php file performs the export.
* 
* Variables:
*   $export[]
* 		'earliest' => Earliest date/time to include
* 		'latest' => Latest date/time to include
* 		'phone' => If set, limits the export to records to or from this number
*/

?>
	      <h3>Export to CSV</h2>
	      <p class="help-block">Use <b>all_broadcast</b> as the phone number to include all broadcast numbers.</p>
		  <form class="form-inline" action="export.php" method="POST">
			<div class="form-group">
			  <label for="export_earliest">Earliest: </label>
			  <input type="text" class="form-control" name="export[earliest]" id="export_earliest" 
			         placeholder="<?php echo date("m/d/y") ?>"
			         value="<?php echo $export['earliest'] ?>">
			</div>
			<div class="form-group">
			  <label for="export_latest">Latest: </label>
			  <input type="text" class="form-control" name="export[latest]" id="export_latest" 
			         placeholder="<?php echo date("m/d/y"); ?>"
			         value="<?php echo $export['latest'] ?>">
			</div>
			<div class="form-group">
			  <label for="export_phone">Limit to phone: </label>
			  <input type="text" class="form-control" name="export[phone]" id="export_phone" 
			         placeholder="<?php echo sms_getFirstHotline($hotline_number, $hotline, $error) ? $hotline_number : reset($BROADCAST_CALLER_IDS) ?>"
			         value="<?php echo $export['phone'] ?>">			  
			</div>
			<div class="form-group">
			  <label for="export_type">Type: </label>
			  <select class="form-control" name="export[type]" id="export_type">
				<option value="">(all types)</option>
				<option value="text">text</option>
				<option value="call in progress">call in progress</option>
				<option value="call answered">call answered</option>
				<option value="voicemail">voicemail</option>
				<option value="call ended">call ended</option>
			  </select>
			</div>
			<button type="submit" class="btn btn-default">Export</button>
		  </form>
