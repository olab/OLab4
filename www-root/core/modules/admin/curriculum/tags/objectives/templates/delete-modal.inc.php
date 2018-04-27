<div id="deleteConfirmationModal" class="modal hide fade">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3><?php echo $translate->_("Delete Tag(s)"); ?> </h3>
   </div>
    <div class="modal-body">
        <div id="courses-selected" >
            <p><?php echo $translate->_("Please confirm you would like to delete the selected Tag(s):"); ?></p>
            <div id="delete-courses-container">
                <ul class="delete-courses-list" id="delete-courses-list"></ul>
            </div>
            
        </div>
    </div>
    <div class="modal-footer">
        <div class="row-fluid">
            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
            <input id="submitdeleteconfirmation" data-dismiss="modal" name="submitdeleteconfirmation" type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete"); ?>" />
        </div>
    </div>
</div>