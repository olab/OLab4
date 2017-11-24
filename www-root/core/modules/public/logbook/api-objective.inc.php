<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Outputs a table row with the appropriate clerkship objective's data.
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
ob_clear_open_buffers();

if (isset($_POST["id"]) && $_SESSION["isAuthorized"]) {
	$objective_id = clean_input($_POST["id"], array("int"));
	if (isset($_POST["level"]) && ((int)$_POST["level"])) {
		$level = (int)$_POST["level"];
	} else {
		$level = 0;
	}
	if ($objective_id) {
        $objective = Models_Objective::fetchRow($objective_id);
        if ($objective && $objective->getLoggable()) {
			?>
            <div class="row-fluid" id="objective_<?php echo $objective_id; ?>_row">
                <span class="span1">
                    <input type="checkbox" class="objective_delete" value="<?php echo $objective_id; ?>" />
                </span>
                <label class="offset1 span5" for="delete_objective_<?php echo $objective_id; ?>">
                    <?php echo $objective->getName(); ?>
                </label>
                <span class="span5 align-right">
                    <input type="hidden" class="objective_id" name="objectives[<?php echo $objective_id; ?>]" value="<?php echo $objective_id; ?>" />
                    <select name="obj_participation_level[<?php echo $objective_id; ?>]" id="obj_<?php echo $objective_id; ?>_participation_level" style="width: 150px" class="pull-right">
                        <option value="1" <?php echo ($level == 1 || (!$level) ? "selected=\"selected\"" : ""); ?>>Observed</option>
                        <option value="2" <?php echo ($level == 2 ? "selected=\"selected\"" : ""); ?>>Performed with help</option>
                        <option value="3" <?php echo ($level == 3 ? "selected=\"selected\"" : ""); ?>>Performed independently</option>
                    </select>
                </span>
            </div>
			<?php
		}
	}
}
exit;
?>