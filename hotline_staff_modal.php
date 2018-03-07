<?php
/**
* @file
* Staff modal dialog, for adding or editing staff entries.
*
* This file is to be included in a page; it does not work as a standalone
* page.
*/

// Set up the initial values to be displayed by the dialog differently
// depending upon whether a new staff entry is being added, or an existing
// one is being edited.
if ($modal_action == "Add") {

    // Initialize a new staff entry array.
    $staff = array(
        'contact_name' => "",
        'phone' => ""
    );
} else {

    // Get the staff entry as a record.
    $sql = "SELECT id, contact_name, phone FROM contacts ".
            "WHERE id='".addslashes($id)."'";
    db_db_getrow($sql, $staff, $error);
}

// Create the modal dialog.
?>

<div class="modal show" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="modal-staff" action="hotline_staff.php" method="POST">
        <input type="hidden" name="action" value="<?php
                echo strtolower($modal_action) ?>staff">
        <input type="hidden" name="display_type" value="<?php echo $display_type ?>">
        <?php

if ($modal_action == "Edit") {
    ?>

        <input type="hidden" name="staff[id]"
                value="<?php echo $staff['id'] ?>">

        <?php
}

        ?>
        <div class="modal-header">
          <a class="btn close"
                href="hotline_staff.php?display_type=<?php echo $display_type ?>"
                role="button" aria-label="Close"><span aria-hidden="true">&times;</span></a>
          <h4 class="modal-title">
            <strong>
              <?php echo $modal_action ?> staff entry
            </strong>
          </h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="staff_name">Name</label>
            <input type="text" class="form-control" id="staff_name"
                    name="staff[contact_name]"
                    value="<?php echo $staff['contact_name'] ?>">
          </div>
          <div class="form-group">
            <label for="staff_phone">Phone</label>
            <input type="text" class="form-control" id="staff_phone"
                    name="staff[phone]"
                    value="<?php echo $staff['phone'] ?>">
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default"
                href="hotline_staff.php?display_type=<?php echo $display_type ?>"
                role="button">Close</a>
          <button type="submit" class="btn btn-primary"><?php echo $modal_action ?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
