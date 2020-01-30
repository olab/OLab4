<?php
    load_rte();
    ?>
    <h2>Admin Notes</h2>
    <div class="">
        <label for="textarea_editor"><?php echo $translate->_("Note: These notes are visible only to Administrators, Translators, and Leadership."); ?></label>
        <br/>
        <div id="editor">
            <textarea <?php echo $disabled; ?> id="admin_notes" name="admin_notes" cols="70" rows="10">
                <?php if (!empty($PROCESSED)) {
                    echo $PROCESSED["admin_notes"];
                }?>
            </textarea>
        </div>
    </div>
<?php
