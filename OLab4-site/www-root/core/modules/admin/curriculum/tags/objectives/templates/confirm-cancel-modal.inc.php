<div id="confirmCancelModal" class="modal hide fade">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h1><?php echo $translate->_("Cancel"); ?> </h1>
   </div>
    <div class="modal-body">
        <div id="courses-selected" >
            <p><?php echo $translate->_("The changes you have made will not be saved."); ?></p>
        </div>
    </div>
    <div class="modal-footer">
        <div class="row-fluid">
            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
            <input id="btnCancelConfirmation" data-dismiss="modal" name="submitdeleteconfirmation" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Ok"); ?>" />
        </div>
    </div>
</div>
