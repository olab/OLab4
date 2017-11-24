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
 * API to handle interaction with learning object repository.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

ini_set('post_max_size', '64M');
ini_set('upload_max_filesize', '64M');

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    switch ($request_method) {
        case "POST" :
            $PROCESSED = array();
            if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                $PROCESSED["rubric_id"] = $tmp_input;
            } else {
                add_error($translate->_("A Grouped Item is required."));
            }

            switch ($request["method"]) {
                case "add-element" :
                    if (isset($request["item_id"]) && $tmp_input = clean_input($request["item_id"], array("trim", "int"))) {
                        $PROCESSED["item_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No item to add to this rubric."));
                        $PROCESSED["item_id"] = 0;
                    }
                    if (isset($request["add_rubric_item_checked"]) && $tmp_input = clean_input($request["add_rubric_item_checked"], array("trim", "int"))) {
                        $PROCESSED["add_rubric_item_checked"] = $tmp_input;
                    } else {
                        $PROCESSED["add_rubric_item_checked"] = 0;
                        $PROCESSED["deleted_date"] = time();
                    }
                    if (!$ERROR) {
                        $method = "insert";
                        $my_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($PROCESSED["rubric_id"], "AND", "order", "DESC");
                        $my_items = array();
                        if ($my_rubric_items) {
                            foreach($my_rubric_items as $item) {
                                $my_items[] = $item->getItemID();
                                if ($PROCESSED["item_id"] == $item->getItemID()) {
                                    $method = "update";
                                    $rubric_item = Models_Assessments_Rubric_Item::fetchRowByID($item->getID());
                                }
                            }
                        }
                        $count = 1;
                        if ($my_rubric_items) {
                            $count = $my_rubric_items[0]->getOrder() + 1;
                        }

                        $PROCESSED["order"] = $count;
                        $PROCESSED["enable_flagging"] = 0;

                        if ($method == "insert" && !$rubric_item) {
                            $rubric_item = new Models_Assessments_Rubric_Item($PROCESSED);
                        } else {
                            $rubric_item = $rubric_item->fromArray($PROCESSED);
                        }

                        if (!$rubric_item->$method()) {
                            application_log("error", "Unable to add item to rubric. Database said: " . $db->ErrorMsg());
                        }

                        $total_rubric_items = count(Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($PROCESSED["rubric_id"], "AND", "order", "DESC"));

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully modified Grouped Item items."), "rubric_item_count" => $total_rubric_items));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error while trying to add items to this Grouped Item. The system administrator was informed of this error; please try again later.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to add elements to Grouped Item."), "data" => $ERRORSTR));
                    }
                    break;
                case "update-rubric" :

                    if (!$ERROR) {
                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveId();

                        $form = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($PROCESSED["rubric_id"]);
                        $form = $form->fromArray($PROCESSED);

                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to update Grouped Item."), "data" => $ERRORSTR));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;
            }
            break;
    }
    exit;
}