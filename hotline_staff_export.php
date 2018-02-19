<?php
/**
* @file
* Form to export staff call times to CSV file
*
* The export.php file performs the export.
*
* Variables:
*   $export[]
* 		'contact_id' => Identifier of the contact for which to export call times.
*                       If empty, all contacts' call times should be exported.
*/
$export = array("contact_id"=>"");

// Query the database for the contacts.
if (!db_db_query("SELECT * FROM contacts ORDER BY contact_name", $contacts, $error)) {
    echo $error;
}
?>
	      <h3>Export to CSV</h2>
		  <form class="form-inline" action="export.php?type=calltimes" method="POST">
			<div class="form-group">
			  <label for="export_type">Limit to staff member: </label>
			  <select class="form-control" name="export[contact_id]" id="export_contact_id">
				<option value="">(all staff)</option>
                <?php
foreach ($contacts as $contact) {
    ?>
                <option value="<?php echo $contact['id'] ?>"><?php echo $contact['contact_name'] ?></option>
                <?php
}
                ?>
			  </select>
			</div>
			<button type="submit" class="btn btn-default">Export</button>
		  </form>
