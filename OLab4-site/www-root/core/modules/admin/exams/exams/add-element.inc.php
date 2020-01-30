<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The file that loads the form permissions add page when /admin/assessments/forms?section=add-permission is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_FORMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    
    if (isset($_GET["element_type"]) && $tmp_input = clean_input($_GET["element_type"], array("trim", "striptags"))) {
        $PROCESSED["element_type"] = $tmp_input;
    }
    
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }
    
    $form = Models_Assessments_Form::fetchRowByID($PROCESSED["id"]);
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-form&id=" . $PROCESSED["id"], "title" => $form->getTitle());
    
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["breadcrumb"]["title"]);
    
    ?>
    <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
    <?php
    
    switch ($STEP) {
        case 2 :
            if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], "int")) {
                $PROCESSED["id"] = $tmp_input;
            } else {
                add_error($translate->_("Invalid form ID."));
            }

            if (isset($_POST["element_text"]) && $tmp_input = clean_input($_POST["element_text"], array("trim", "allowedtags"))) {
                $PROCESSED["element_text"] = $tmp_input;
            } else {
                add_error($translate->_("Invalid element text."));
            }

            if (!$ERROR) {
                $element_data = array(
                    "form_id"           => $PROCESSED["id"],
                    "element_type"      => "text",
                    "element_text"      => $PROCESSED["element_text"],
                    "order"             => Models_Assessments_Form_Element::fetchNextOrder($PROCESSED["id"]),
                    "allow_comments"    => "1",
                    "enable_flagging"   => "0",
                    "updated_date"      => time(),
                    "updated_by"        => $ENTRADA_USER->getActiveId()
                );

                $element = new Models_Assessments_Form_Element($element_data);
                if ($element->insert()) {
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully added text element to form."), "success", $MODULE);
                    header("Location: ".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=edit-form&id=".$PROCESSED["id"]);
                } else {
                    $STEP = 1;
                    add_error($translate->_("An error occurred while adding a text element to a form."));
                }
            }

        break;
    }
    
    switch ($STEP) {
        case 1 :
        default :
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }

            /**
             * Load the rich text editor.
             */
            load_rte();
            ?>
            <?php echo display_generic($SECTION_TEXT["add_element_notice"]); ?>
            <form class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&id=" . $PROCESSED["id"] . "&element_type=" . $PROCESSED["element_type"]; ?>" method="POST">
                <input type="hidden" name="step" value="2" />
                <input type="hidden" name="id" value="<?php echo $PROCESSED["id"]; ?>" />
                <div class="control-group">
                    <label class="control-label" for="element-text"><?php echo $translate->_("Element Text"); ?></label>
                    <div class="controls">
                        <textarea id="element-text" name="element_text"></textarea>
                    </div>
                </div>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-form&id=".$PROCESSED["id"]; ?>" class="btn btn-default"><?php echo $DEFAULT_TEXT_LABELS["btn_back"]; ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $DEFAULT_TEXT_LABELS["btn_add_elements"]; ?>" />
                </div>
            </form>
            <?php
        break;
    }
}