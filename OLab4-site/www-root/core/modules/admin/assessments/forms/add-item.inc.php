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
    <h1><?php echo $translate->_("Create New Item"); ?></h1>
    <?php
    
    switch ($STEP) {
        case 2 :
            
            if (isset($_POST["items"]) && is_array($_POST["items"])) {
                foreach ($_POST["items"] as $item) {
                    $tmp_item = clean_input($item, "int");
                    if ($tmp_item) {
                        $PROCESSED["items"][] = $tmp_item;
                    }
                }
            }

            if ($PROCESSED["id"] && (isset($PROCESSED["items"]) && $PROCESSED["items"])) {
                
                foreach ($PROCESSED["items"] as $item) {
                    $assessments_item = Models_Assessments_Item::fetchRowByID($item);
                    if (!Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item, $PROCESSED["id"])) {
                        $form_element_data = array(
                            "form_id"           => $PROCESSED["id"],
                            "element_type"      => $PROCESSED["element_type"],
                            "element_id"        => $assessments_item->getID(),
                            "one45_element_id"  => $assessments_item->getOne45ElementID(),
                            "order"             => Models_Assessments_Form_Element::fetchNextOrder($PROCESSED["id"]),
                            "allow_comments"    => 1,
                            "enable_flagging"   => 0,
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->GetID()
                        );
                        $form_element = new Models_Assessments_Form_Element($form_element_data);

                        if ($form_element->insert()) {
                            $SUCCESS++;
                        } else {
                            add_error($SECTION_TEXT["failed_to_create"]);
                        }
                    } else {
                        add_error($SECTION_TEXT["already_attached"]);
                    }
                }
                if ($SUCCESS) {
                    Entrada_Utilities_Flashmessenger::addMessage("Successfully added <strong>".$SUCCESS."</strong> items to the form.", "success", $MODULE);
                    header("Location: ".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=edit-form&id=".$PROCESSED["id"]);
                } else {
                    $STEP = 1;
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
            <style type="text/css">
                .item-container {
                    cursor:pointer;
                }
                .item-container.added .item-table {
                    border-color:green;
                }
            </style>
            <script type="text/javascript">
                jQuery(function($) {
                    $(".item-selector").on("change", function(e) {
                        var self = $(this);
                        if (self.is(":checked")) {
                            self.closest(".item-container").addClass("added");
                        } else {
                            self.closest(".item-container").removeClass("added");
                        }
                    });
                });
            </script>
            <?php echo display_generic($SECTION_TEXT["add_element_notice"]); ?>
            <form action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&id=" . $PROCESSED["id"] . "&element_type=" . $PROCESSED["element_type"]; ?>" method="POST">
                <input type="hidden" name="step" value="2" />
                <input type="hidden" name="id" value="<?php echo $PROCESSED["id"]; ?>" />
                <?php

                switch ($PROCESSED["element_type"]) {
                    case "item" :
                        $items = new Views_Deprecated_Item();
                        $i = $items->fetchItemsByAuthor($ENTRADA_USER->getActiveID(), $PROCESSED["id"]);
                        if ($i) {
                            echo implode("\n", $i->render());
                        }
                    break;
                    case "rubric" :
                    default :
                        header("Location: " . ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE);
                    break;
                }

                ?>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-form&id=".$PROCESSED["id"]; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Add Elements"); ?>" />
                </div>
            </form>
            <?php
        break;
    }
}