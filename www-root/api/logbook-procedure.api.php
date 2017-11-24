<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Outputs a table row with the appropriate clerkship procedure's data.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"] && isset($_POST["id"])) {
    $procedure_id = clean_input($_POST["id"], array("int"));

    if (isset($_POST["level"]) && ((int)$_POST["level"])) {
        $level = (int)$_POST["level"];
    } else {
        $level = 0;
    }

    if ($procedure_id) {
        $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` WHERE `lprocedure_id` = ".$db->qstr($procedure_id);
        $procedure = $db->GetRow($query);
        if ($procedure) {
            ?>
            <div class="row-fluid" id="procedure_<?php echo $procedure_id; ?>_row">
                <div class="span5">
                    <label class="checkbox space-above">
                        <input type="checkbox" class="procedure_delete" value="<?php echo $procedure_id; ?>" />
                        <?php echo html_encode($procedure["procedure"]); ?>
                    </label>
                </div>
                <div class="span7 space-below">
                    <select name="proc_participation_level[<?php echo $procedure_id; ?>]" id="proc_<?php echo $procedure_id; ?>_participation_level" class="input-large">
                        <option value="1" <?php echo ($level == 1 || (!$level) ? "selected=\"selected\"" : ""); ?>>Observed</option>
                        <option value="2" <?php echo ($level == 2 ? "selected=\"selected\"" : ""); ?>>Performed with help</option>
                        <option value="3" <?php echo ($level == 3 ? "selected=\"selected\"" : ""); ?>>Performed independently</option>
                    </select>
                </div>
                <input type="hidden" name="procedures[<?php echo $procedure_id; ?>]" value="<?php echo $procedure_id; ?>" />
            </div>
            <?php 
        }
    }
}
