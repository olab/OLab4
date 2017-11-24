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
 * API to handle interaction with the Assessment Item Bank.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

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
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    
	$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	$request = ${"_" . $request_method};
    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }
    
    if (!$ERROR) { 
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-items" :
                        if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                            $PROCESSED["item_id"] = $tmp_input;
                        } else {
                            $PROCESSED["item_id"] = 0;
                        }

                        $PROCESSED["filters"] = array();
                        if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"])) {
                            $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"];
                        }
                        
                        if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = "%".$tmp_input."%";
                        } else {
                            $PROCESSED["search_term"] = "";
                        }
                        
                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 25;
                        }
                        
                        if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }
                        
                        if (isset($request["view"]) && $tmp_input = clean_input(strtolower($request["view"]), array("trim", "alpha"))) {
                            $PROCESSED["view"] = $tmp_input;
                        }

                        $PROCESSED["form_id"] = NULL;
                        if (isset($request["form_id"]) && $tmp_input = clean_input(strtolower($request["form_id"]), array("trim", "int"))) {
                            $PROCESSED["form_id"] = $tmp_input;
                        }

                        $PROCESSED["rubric_items"] = NULL;
                        $PROCESSED["rubric_descriptors"] = NULL;
                        $PROCESSED["exclude_item_ids"] = NULL;
                        if (isset($request["exclude_item_ids"])) {
                            foreach($request["exclude_item_ids"] as $descriptor) {
                                $descriptor = clean_input($descriptor, array("int"));
                                $PROCESSED["exclude_item_ids"][] = $descriptor;
                            }
                        }

                        $PROCESSED["rubric_width"] = NULL;
                        $PROCESSED["sort_direction"] = NULL;
                        $PROCESSED["sort_column"] = NULL;
                        $PROCESSED["search_itemtype_id"] = NULL;
                        $PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef();

                        $rubric_referrer_data = array();
                        if ($PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::getRubricRef()) {
                            $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);
                        }

                        if (!empty($rubric_referrer_data)) {
                            if (isset($rubric_referrer_data["types"]) && !empty($rubric_referrer_data["types"])) {
                                // Rubric data given to us, so use it to search with
                                // Find the item type IDs given
                                $PROCESSED["search_itemtype_id"] = array();
                                foreach ($rubric_referrer_data["types"] as $itemtype) {
                                    // Find each item type ID
                                    if ($itemtype_record = Models_Assessments_Itemtype::fetchRowByShortname($itemtype)) {
                                        $PROCESSED["search_itemtype_id"][] = $itemtype_record->getID();
                                    }
                                }
                            }
                            // Set the rubric width
                            if (isset($rubric_referrer_data["width"]) && $rubric_referrer_data["width"]) {
                                $PROCESSED["rubric_width"] = $rubric_referrer_data["width"];
                            }

                            // Set the existing rubric item IDs
                            $PROCESSED["rubric_items"] = $rubric_referrer_data["items"];

                            // Set the descriptors
                            $PROCESSED["rubric_descriptors"] = $rubric_referrer_data["descriptors"];
                        }

                        $PROCESSED["rubric_width"] = null;
                        if (isset($request["rubric_width"]) && $tmp_input = clean_input(strtolower($request["rubric_width"]), array("trim", "int"))) {
                            if ($tmp_input) {
                                $PROCESSED["rubric_width"] = $tmp_input;
                            }
                        }

                        $items = array();
                        if (isset($PROCESSED["item_id"]) && $PROCESSED["item_id"]) {
                            $item = Models_Assessments_Item::fetchRecordByID($PROCESSED["item_id"]);
                            if ($item) {
                                $items = array($item);
                            }
                        } else {
                            $items = Models_Assessments_Item::fetchAllRecordsBySearchTerm(
                                $PROCESSED["search_term"],
                                $PROCESSED["limit"],
                                $PROCESSED["offset"],
                                $PROCESSED["sort_direction"],
                                $PROCESSED["sort_column"],
                                $PROCESSED["rubric_width"],
                                $PROCESSED["search_itemtype_id"],
                                $PROCESSED["rubric_items"], // always null?
                                $PROCESSED["rubric_descriptors"],
                                $PROCESSED["exclude_item_ids"],
                                $PROCESSED["form_id"],
                                $PROCESSED["filters"]);
                        }

                        if (!empty($items)) {
                            $data = array();
                            foreach ($items as $item) {
                                $item_data = array(
                                    "item_id"           => $item["item_id"],
                                    "itemtype_id"       => $item["itemtype_id"],
                                    "item_code"         => ($item["item_code"] && !empty($item["item_code"]) ? $item["item_code"] : "N/A"),
                                    "item_text"         => $item["item_text"],
                                    "item_type"         => $item["name"],
                                    "item_responses"    => $item["responses"],
                                    "mandatory"         => $item["mandatory"],
                                    "comment_type"      => $item["comment_type"],
                                    "created_date"      => ($item["created_date"] != "0" ? date("Y-m-d", $item["created_date"]) : $translate->_("N/A"))
                                );

                                $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item["item_id"]);
                                if ($item_responses) {
                                    $response_data = array();

                                    foreach ($item_responses as $response) {
                                        $descriptor_text = "";
                                        //if rubric get response descriptors and response labels.
                                        if ($response->getARDescriptorID()) {
                                            $descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($response->getARDescriptorID());
                                            if ($descriptor) {
                                                $descriptor_text = $descriptor->getDescriptor();
                                            }
                                        }

                                        $item_data["responses"][] = array(
                                            "iresponse_id"      => $response->getID(),
                                            "text"              => $response->getText(),
                                            "item_id"           => $item["item_id"],
                                            "descriptor"        => $descriptor_text,
                                            "total_responses"   => count($item_responses)
                                        );
                                    }
                                }
                                $data[] = $item_data;
                            }
                            echo json_encode(array("results" => count($items), "data" => array("total_items" => Models_Assessments_Item::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["rubric_width"], $PROCESSED["search_itemtype_id"], $PROCESSED["rubric_items"], $PROCESSED["rubric_descriptors"], $PROCESSED["filters"]), "items" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Items Found."))));
                        }
                    break;
                    case "get-item-details" :
                        if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                            $PROCESSED["item_id"] = $tmp_input;
                        } else { 
                            add_error($translate->_("A problem occurred while attempting to fetch data for this item. Please try again later."));
                        }
                        
                        if (!$ERROR) {
                            $item = Models_Assessments_Item::fetchRowByID($PROCESSED["item_id"]);
                            if ($item) {
                                $item_tags = Models_Assessments_Item::fetchItemObjectives($item->getID());
                                $item_responses = $item->getItemResponses();
                                
                                    $data = array();
                                    $data["item_id"] = $item->getID();
                                    $data["item_code"] = ($item->getItemCode() ? $item->getItemCode() : "N/A");
                                    $data["mandatory"] = $item->getMandatory();
                                    $data["comment_type"] = $item->getCommentType();
                                    $data["total_responses"] = count($item_responses);
                                    $data["created_date"] = ($item->getCreatedDate() != "0" ? date("Y-m-d", $item->getCreatedDate()) : $translate->_("N/A"));
                                    if ($item_tags) {
                                        $deprecated_view_item = new Views_Deprecated_Item();
                                        $data["tags"] = $deprecated_view_item->renderCurriculumTags($item_tags);
                                    }
                                    echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to fetch data for this item. PLease try again later."))));
                            }
                        } else {
                           echo json_encode(array("status" => "error", "data" => array($ERRORSTR))); 
                        }
                        
                    break;
                    case "get-item-responses" :
                        if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                            $PROCESSED["item_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch data for this item. PLease try again later"));
                        }

                        if (!$ERROR) {
                            $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($PROCESSED["item_id"]);
                            if ($item_responses) {
                                $data = array();
                                $data["total_responses"] = count($item_responses);
                                
                                foreach ($item_responses as $response) {
                                    $descriptor_text = "";
                                    //if rubric get response descriptors and response labels.
                                    if ($response->getARDescriptorID()) {
                                        $descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($response->getARDescriptorID());
                                        if ($descriptor) {
                                            $descriptor_text = $descriptor->getDescriptor();
                                        }
                                    }
                                    $data["responses"][] = array("iresponse_id" => $response->getID(), "text" => $response->getText(), "item_id" => $PROCESSED["item_id"],
                                                                 "descriptor" => $descriptor_text);
                                }

                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("This item has no responses."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("This item has no responses."))));
                        }
                    break;
                    case "get-item-tags" :
                        if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                            $PROCESSED["item_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch data for this item. Please try again later"));
                        }
                        
                        if (!$ERROR) {
                            $tags = Models_Assessments_Tag::fetchAllRecordsByItemID($PROCESSED["item_id"]);
                            
                            if ($tags) {
                                $data = array();
                                foreach ($tags as $tag) {
                                    $data[] = array("tag" => $tag->getTag());
                                }
                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("This item has no tags."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to fetch data for this item. Please try again later."))));
                        }
                    break;
                    case "get-filtered-audience" :
                        if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                            $PROCESSED["item_id"] = $tmp_input;
                        }
                        
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = "%".$tmp_input."%";
                        }

                        if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                            $PROCESSED["filter_type"] = $tmp_input;
                        }

                        if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                            $PROCESSED["item_id"] = $tmp_input;
                        }

                        $results = Models_Assessments_Item_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["item_id"], $PROCESSED["search_value"]);
                        if ($results) {
                            echo json_encode(array("results" => count($results), "data" => $results));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                        }
                    break;
                    case "get-response-descriptors" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }

                        $descriptors = Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada", NULL, $PROCESSED["search_value"]);

                        if ($descriptors) {
                            $data = array();
                            foreach ($descriptors as $descriptor) {
                                $data[] = array("target_id" => $descriptor->getID(), "target_label" => $descriptor->getDescriptor());
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No descriptors found")));
                        }
                    break;
                    case "get-objectives" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            $PROCESSED["parent_id"] = 0;
                        }
                        
                        $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                        $objectives = Models_Objective::fetchByOrganisationSearchValue($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["parent_id"]);

                        if ($objectives) {
                            $data = array();
                            foreach ($objectives as $objective) {
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 0));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                        
                    break;
                    case "get-child-objectives" :
                        if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            $PROCESSED["parent_id"] = 0;
                        }
                        
                        $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                        $child_objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["parent_id"]);
                        
                        if ($child_objectives) {
                            $data = array();
                            foreach ($child_objectives as $objective) {
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 0));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                    break;
                    case "get-fieldnote-objectives" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            $PROCESSED["parent_id"] = 0;
                        }
                        
                        $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                        $objectives = Models_Objective::fetchByOrganisationSearchValue($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["parent_id"]);

                        if ($objectives) {
                            $data = array();
                            foreach ($objectives as $objective) {
                                $total_child_objectives = (int) Models_Objective::countObjectiveChildren($objective->getID());
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => $total_child_objectives, "level_selectable" =>  ($total_child_objectives > 0 ? 0 : 1));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                        
                    break;
                    case "get-fieldnote-child-objectives" :
                        if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            $PROCESSED["parent_id"] = 0;
                        }
                        
                        $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                        $child_objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["parent_id"]);
                        
                        if ($child_objectives) {
                            $data = array();
                            foreach ($child_objectives as $objective) {
                                $total_child_objectives = (int) Models_Objective::countObjectiveChildren($objective->getID());
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => $total_child_objectives, "level_selectable" =>  ($total_child_objectives > 0 ? 0 : 1));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                    break;
                    case "get-item-authors" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        $authors = Models_Assessments_Item_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                        if ($authors) {
                            $data = array();
                            foreach ($authors as $author) {
                                $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                                $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No authors were found.")));
                        }
                    break;
                    case "get-user-courses" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                        if ($user_courses) {
                            $data = array();
                            foreach ($user_courses as $course) {
                                $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                        }
                    break;    
                    case "get-user-organisations" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        $user_organisations = $ENTRADA_USER->getAllOrganisations();
                        if ($user_organisations) {
                            $data = array();
                            foreach ($user_organisations as $key => $organisation) {
                                $data[] = array("target_id" => $key, "target_label" => $organisation);
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No organisations were found.")));
                        }
                    break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
                }
            break;    
            case "POST" :
                switch ($method) {
                    case "view-preference" :
                        if (isset($request["selected_view"]) && $tmp_input = clean_input($request["selected_view"], array("trim", "striptags"))) {
                            $selected_view = $tmp_input;
                        } else {
                            add_error($translate->_("No Item Bank view was selected"));
                        }
                        
                        if (!$ERROR) {
                            $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_view"] = $selected_view;
                            echo json_encode(array("status" => "success", "data" => array($selected_view)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    break;
                    case "remove-permission" :
                        if (isset($request["aiauthor_id"]) && $tmp_input = clean_input($request["aiauthor_id"], "int")) {
                            $PROCESSED["aiauthor_id"] = $tmp_input;
                        }
                        if (isset($PROCESSED["aiauthor_id"])) {
                            $author = Models_Assessments_Item_Author::fetchRowByID($PROCESSED["aiauthor_id"]);
                            if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                                if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                    $ENTRADA_LOGGER->log("Item Bank", "remove-permission", "aiauthor_id", $PROCESSED["aiauthor_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                    echo json_encode(array("status" => "success", $translate->_("success.")));
                                } else {
                                    echo json_encode(array("status" => "error", "You can't delete yourself."));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                            }

                        } else {
                            echo json_encode(array("status" => "error"));
                        }
                    break;
                    case "add-permission" :
                        if (isset($request["member_id"]) && $tmp_input = clean_input($request["member_id"], "int")) {
                            $PROCESSED["member_id"] = $tmp_input;
                        }

                        if (isset($request["member_type"]) && $tmp_input = clean_input($request["member_type"], array("trim", "striptags"))) {
                            $PROCESSED["member_type"] = $tmp_input;
                        }

                        if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                            $PROCESSED["item_id"] = $tmp_input;
                        }

                        if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["item_id"]) {
                            $added = 0;
                            $a = Models_Assessments_Item_Author::fetchRowByItemIDAuthorIDAuthorType($PROCESSED["item_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                            if ($a) {
                                if ($a->getDeletedDate()) {
                                    if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                        $added++;
                                    }
                                } else {
                                    application_log("notice", "Item author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                                }
                            } else {
                                $a = new Models_Assessments_Item_Author(
                                    array(
                                        "item_id" => $PROCESSED["item_id"],
                                        "author_type" => $PROCESSED["member_type"],
                                        "author_id" => $PROCESSED["member_id"],
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveID(),
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getActiveID()
                                    )
                                );
                                if ($a->insert()) {
                                    $ENTRADA_LOGGER->log($translate->_("Item Bank"), "add-permission", "aiauthor_id", $a->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                    $added++;
                                }
                            }

                            if ($added >= 1) {
                                echo json_encode(array("status" => "success", "data" => array("author_id" => $a->getID())));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array("Failed to add author")));
                            }
                        }
                    break;
                    case "delete-items" :
                        $PROCESSED["delete_ids"] = array();
                        if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                            foreach ($request["delete_ids"] as $rubric_id) {
                                $tmp_input = clean_input($rubric_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["delete_ids"][] = $tmp_input;
                                }
                            }
                        }
                        if (!empty($PROCESSED["delete_ids"])) {
                            $deleted_items = $forms_api->deleteItems($PROCESSED["delete_ids"]);
                            $errors = $forms_api->getErrorMessages();
                            if (empty($errors) && !empty($deleted_items)) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Item(s)."), count($deleted_items)), "item_ids" => $deleted_items));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an Item.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No Items were selected for deletion.")));
                        }
                    break;
                    case "set-filter-preferences" :
                        if (isset($request["curriculum_tag"]) && is_array($request["curriculum_tag"])) {
                            $PROCESSED["filters"]["curriculum_tag"] = array_filter($request["curriculum_tag"], function ($curriculum_tag) {
                                return (int) $curriculum_tag;
                            });
                        }
                        
                        if (isset($request["author"]) && is_array($request["author"])) {
                            $PROCESSED["filters"]["author"] = array_filter($request["author"], function ($author) {
                                return (int) $author;
                            });
                        }
                        
                        if (isset($request["course"]) && is_array($request["course"])) {
                            $PROCESSED["filters"]["course"] = array_filter($request["course"], function ($course) {
                                return (int) $course;
                            });
                        }
                        
                        if (isset($request["organisation"]) && is_array($request["organisation"])) {
                            $PROCESSED["filters"]["organisation"] = array_filter($request["organisation"], function ($organisation) {
                                return (int) $organisation;
                            });
                        }

                        foreach (array("curriculum_tag", "author", "course", "organisation") as $filter_type) {
                            Entrada_Utilities_AdvancedSearchHelper::cleanupSessionFilters($request, $MODULE, $SUBMODULE, $filter_type);
                        }

                        if (isset($PROCESSED["filters"])) {
                            $assessments_base = new Entrada_Utilities_Assessments_Base();
                            Models_Assessments_Item::saveFilterPreferences($PROCESSED["filters"]);
                            $assessments_base->updateAssessmentPreferences("assessments");
                            echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                        }
                    break;
                    case "remove-filter" :
                        if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                            $PROCESSED["filter_type"] = $tmp_input;
                        } else {
                            add_error($translate->_("Invalid filter type provided."));
                        }
                        
                        if (isset($request["filter_target"]) && $tmp_input = clean_input($request["filter_target"], array("trim", "int"))) {
                            $PROCESSED["filter_target"] = $tmp_input;
                        } else {
                            add_error($translate->_("Invalid filter target provided."));
                        }

                        $assessments_base = new Entrada_Utilities_Assessments_Base();

                        if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"][$PROCESSED["filter_type"]])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"][$PROCESSED["filter_type"]]);
                                if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"])) {
                                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"]);
                                }
                            }

                            $assessments_base->updateAssessmentPreferences("assessments");
                            echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                        }
                    break;
                    case "remove-all-filters" :
                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"]);
                        $assessments_base->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                    case "copy-item" :

                        // Ensure that a new item title was entered
                        if (isset($_POST["new_item_title"]) && $tmp_input = clean_input($_POST["new_item_title"], array("trim", "striptags"))) {
                            $PROCESSED["title"] = $tmp_input;
                        } else {
                            add_error($translate->_("Sorry, a new item title is required."));
                        }

                        // Validate posted item identifier
                        if (isset($request["item_id"]) && $tmp_input = clean_input($request["item_id"], "int")) {
                            $old_item_id = $tmp_input;
                        } else {
                            add_error($translate->_("Invalid item identifier provided."));
                            $old_item_id = null;
                        }

                        if (!$ERROR) {
                            // Copy via Forms API
                            if (!$forms_api->copyItem($old_item_id, $PROCESSED["title"])) {
                                add_error($translate->_("Unable to copy item."));
                            }
                        }

                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        } else {
                            // Successfully copied item
                            Entrada_Utilities_Flashmessenger::addMessage("Successfully copied item.", "success", $MODULE);
                            $url = ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id={$forms_api->getItemID()}";
                            echo json_encode(array("status" => "success", "url" => $url));
                        }
                        break;

                    case "copy-attach-item":
                        $PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef();
                        $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
                        if (empty($form_referrer_data)) {
                            $form_id = null;
                        } else {
                            $form_id = $form_referrer_data["form_id"];
                        }

                        $PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::getRubricRef();
                        $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);
                        if (empty($rubric_referrer_data)) {
                            $rubric_id = null;
                        } else {
                            $rubric_id = $rubric_referrer_data["rubric_id"];
                        }

                        // Ensure that a new item title was entered
                        if (isset($_POST["new_item_title"]) && $tmp_input = clean_input($_POST["new_item_title"], array("trim", "striptags"))) {
                            $PROCESSED["title"] = $tmp_input;
                        } else {
                            add_error($translate->_("Sorry, a new item title is required."));
                        }

                        // Validate posted item identifier
                        if (isset($request["item_id"]) && $tmp_input = clean_input($request["item_id"], "int")) {
                            $old_item_id = $tmp_input;
                        } else {
                            add_error($translate->_("Invalid item identifier provided."));
                            $old_item_id = null;
                        }

                        if (!$form_id && !$rubric_id) {
                            add_error($translate->_("No form or rubric specified."));
                        }

                        if (!$ERROR) {
                            // Copy via Forms API
                            if (!$forms_api->copyItemAndReplaceOnFormOrRubric($old_item_id, $form_id, $rubric_id, $PROCESSED["title"])) {
                                add_error($translate->_("Unable to copy and replace item."));
                                foreach ($forms_api->getErrorMessages() as $err) {
                                    add_error($err);
                                }
                            }
                        }

                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        } else {
                            // Successfully copied item
                            Entrada_Utilities_Flashmessenger::addMessage("Successfully copied and attached item to form.", "success", $MODULE);
                            $url = ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id={$forms_api->getItemID()}";
                            $url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL($url, $PROCESSED["fref"], $PROCESSED["rref"]);
                            echo json_encode(array("status" => "success", "url" => $url));
                        }
                        break;
                }
            break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}