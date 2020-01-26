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
 * The form that allows users to add and edit formbank forms.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_ITEM") && !defined("EDIT_ITEM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
	echo display_error();
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte();
    $render_form_page = true;
    $disabled = false;
    $lock_itemtype = false;
    $lock_rating_scale = false;
    $force_descriptors_readonly = false;
    $clone_on_edit = false;
    $show_grid_controls = false;
    $show_copy_and_attach_button = false;
    $attempt_save_item = false;
    $itemtype_shortname = "";
    $item_data = array();
    $item_in_use = false;
    $notice_message = $alert_message = "";
    $usage_delivered = $usage_rubrics = array();
    $used_in_rubrics = $used_in_assessments = 0;
    $objective_name = false;
    $session_referrer_type = Entrada_Utilities_FormStorageSessionHelper::determineReferrerType($PROCESSED["fref"], $PROCESSED["rref"]);
    $session_referrer_url = Entrada_Utilities_FormStorageSessionHelper::determineReferrerURI($PROCESSED["fref"], $PROCESSED["rref"]);

    // Objective course id processing.
    $disable_course_select = false;
    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
    $formatted_courses_datasource = array();

    $lock_course_selector = false;
    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
        $PROCESSED["course_id"] = $tmp_input;
    } else if ($PROCESSED["fref"] && $form_session_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"])) {
        $PROCESSED["course_id"] = isset($form_session_data["course_id"]) ? $form_session_data["course_id"] : null;
        $lock_course_selector = true;
    } else {
        $PROCESSED["course_id"] = null;
        $PROCESSED["course_name"] = null;
    }

    if ($user_courses) {
        foreach ($user_courses as $user_course) {
            $formatted_courses_datasource[] = array("target_id" => $user_course->getID(), "target_label" => $user_course->getCourseName());
            if ($PROCESSED["course_id"] && $user_course->getID() == $PROCESSED["course_id"]) {
                $PROCESSED["course_name"] = $user_course->getCourseName();

            }
        }

        if (!$PROCESSED["course_id"] && count($formatted_courses_datasource) == 1) {
            $disable_course_select = true;
            $PROCESSED["course_name"] = $formatted_courses_datasource[0]["target_label"];
            $PROCESSED["course_id"] = $formatted_courses_datasource[0]["target_id"];
        }
    }

    if ($PROCESSED["course_id"]) { ?>
        <input id="objective_course_<?php echo $PROCESSED["course_id"]; ?>" name="course_id" type="hidden" class="choose-objective-course-selector" value="<?php echo $PROCESSED["course_id"]; ?>" data-label="<?php echo $PROCESSED["course_name"]; ?>"/>
        <input id="course-name" name="objective_course_name" type="hidden" value="<?php echo html_encode($PROCESSED["course_name"]); ?>"/>
    <?php
    }

    // Get session information about associated rubric, if applicable.
    $PROCESSED["rubric_descriptors"] = array();
    if (!empty($rubric_referrer_data)) {
        $PROCESSED["rubric_descriptors"] = $rubric_referrer_data["descriptors"];
    }

    if (isset($PROCESSED["rating_scale_id"]) && $PROCESSED["rating_scale_id"]) {
        $rating_scale_model = Models_Assessments_RatingScale::fetchRowByID($PROCESSED["rating_scale_id"]);
        if ($rating_scale_model) {
            $PROCESSED["rating_scale_type_id"] = $rating_scale_model->getRatingScaleType();
            $PROCESSED["rating_scale_title"] = $rating_scale_model->getRatingScaleTitle();
        } else {
            $PROCESSED["rating_scale_type_id"] = 0;
        }
    } else {
        $PROCESSED["rating_scale_type_id"] = 0;
    }

    if ($PROCESSED["rating_scale_id"]) {
        $scale_data = $forms_api->fetchScaleData($PROCESSED["rating_scale_id"]);
    }

    // We have an item ID, so set relevant flags
    if ($PROCESSED["item_id"]) {
        $forms_api->setItemID($PROCESSED["item_id"]);

        $usage_delivered = $forms_api->getItemUsageInAssessments();
        $usage_rubrics = $forms_api->getItemUsageInRubrics();
        $used_in_rubrics = count($usage_rubrics);
        $used_in_assessments = count($usage_delivered);

        $item_in_use = $forms_api->isItemInUse($PROCESSED["item_id"]);
        $item_data = $forms_api->fetchItemData();
        $itemtype_shortname = $item_data["meta"]["itemtype_shortname"];

        // Mark any flagged responses (this is overridden if data is posted)
        $position = 0;
        foreach ($item_data["responses"] as $response) {
            $position++;
            $PROCESSED["flag_response"][$position] = $response["flag_response"];
        }

        // Gather field note response data
        if ($item && $item->getID() == $PROCESSED["item_id"]) {
            // Gather field note responses
            if (!empty($item_data["responses"])) {
                $PROCESSED["responses"] = array();
                $count = 0;
                foreach ($item_data["responses"] as $response) {
                    if ($itemtype_shortname == "fieldnote") {
                        $PROCESSED["field_note_responses"][$count] = $response["text"];
                        $PROCESSED["field_note_flag_response"][$count] = $response["flag_response"];
                        $PROCESSED["field_note_ardescriptor_id"][$count++] = $response["ardescriptor_id"];
                    }
                }
            }

            // Fetch objective name
            $objective_name = reset($item_data["objective_names"]);
            if (!empty($item_data["objectives"])) {
                foreach ($item_data["objectives"] as $objective) {
                    $PROCESSED["field_note_objective_ids"][] = $objective["objective_id"];
                }
            }
        }

        $rubrics_using_this_item_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?item_id={$PROCESSED["item_id"]}", $PROCESSED["fref"], $PROCESSED["rref"]);
        $forms_using_this_item_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/forms?item_id={$PROCESSED["item_id"]}", $PROCESSED["fref"], $PROCESSED["rref"]);

        // Set the messaging on the form, and what controls appear, based on the item's usage
        $item_editability = $forms_api->getItemEditabilityState(@$form_referrer_data["form_id"], @$rubric_referrer_data["rubric_id"]);

        switch ($item_editability) {
            case "editable":
                // Not used anywhere, so allow full access.
                $lock_itemtype = false;
                $force_descriptors_readonly = false;
                $disabled = false;
                $lock_rating_scale = false;
                $show_grid_controls = true;
                break;
            case "editable-by-attached-form":
                // The referrer form is the only thing that uses this item
                $lock_itemtype = false;
                $force_descriptors_readonly = false;
                $disabled = false;
                $lock_rating_scale = false;
                $show_grid_controls = true;
                break;
            case "editable-by-attached-rubric":
                // The referrer rubric is the only thing that uses this item
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = false;
                $lock_rating_scale = true;
                $show_grid_controls = false;
                break;
            case "editable-by-attached-form-and-rubric-unlocked-descriptors":
            case "editable-by-attached-rubric-unlocked-descriptors":
                // The referrer rubric is the only thing that uses this item, and there's only 1 item in the rubric, making the rubric descriptors editable
                $lock_itemtype = true;
                $force_descriptors_readonly = false;
                $show_grid_controls = true;
                $disabled = false;
                $lock_rating_scale = false;
                break;
            case "editable-by-attached-form-and-rubric-has-scale":
            case "editable-by-attached-rubric-has-scale":
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = false;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $notice_message = $translate->_("This item uses a pre-defined <strong>Rating Scale</strong>. Response descriptors cannot be individually modified.");
                break;
            case "editable-attached-form-multiple":
                // This item is attached to multiple forms, but no rubrics
                $lock_itemtype = false;
                $disabled = false;
                $force_descriptors_readonly = false;
                $show_grid_controls = true;
                $lock_rating_scale = false;
                $notice_message = sprintf($translate->_("This item is attached to one or more forms. <strong>Making changes to this item will affect all of the associated forms</strong>. <a href='%s'>Click here</a> to see which forms use this item."), $forms_using_this_item_url);
                break;
            case "editable-attached-rubric-multiple":
                // This item is attached to multiple rubrics, but not forms
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = false;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $notice_message = sprintf($translate->_("This item is in use by one or more Grouped Items. <strong>Making changes to this item will affect all of the associated Grouped Items</strong>. <a href='%s'>Click here</a> to see which Grouped Items use this item."), $rubrics_using_this_item_url);
                break;
            case "editable-attached-multiple":
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = false;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $notice_message = $translate->_("This item is in use by multiple forms and Grouped Items. <strong>Making changes to this item will affect all of the associated forms and Grouped Items</strong>.");
                $notice_message .= "<br/><br/>";
                $notice_message .= sprintf($translate->_("<a href='%s'>Click here</a> to see which forms use this item."), $forms_using_this_item_url);
                $notice_message .= "<br/>";
                $notice_message .= sprintf($translate->_("<a href='%s'>Click here</a> to see which Grouped Items use this item."), $rubrics_using_this_item_url);
                break;
            case "readonly":
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = true;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $alert_message = $translate->_("This <strong>item is in use</strong> by a <strong>form</strong> that has been delivered. Only <strong>permissions</strong> can be edited when an item is used in tasks that have been delivered. Please make a copy if you wish to make changes.");
                break;
            case "readonly-clone-to-rubric":
                $clone_on_edit = true;
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = true;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $alert_message = $translate->_("This <strong>item is in use</strong> by a <strong>form</strong> that has been delivered. Only <strong>permissions</strong> can be edited when an item is used in tasks that have been delivered. If you wish to make changes, please use the <strong>\"Copy & Attach\"</strong> button to replace this item in the rubric with a new version.");
                $show_copy_and_attach_button = true;
                break;
            case "readonly-clone-to-form":
                $clone_on_edit = true;
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = true;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                $alert_message = $translate->_("This <strong>item is in use</strong> by a <strong>form</strong> that has been delivered. Only <strong>permissions</strong> can be edited when an item is used in tasks that have been delivered. If you wish to make changes, please use the <strong>\"Copy & Attach\"</strong> button to replace this item on the form with a new version.");
                $show_copy_and_attach_button = true;
                break;
            default: // unknown type, so default as read-only
                $lock_itemtype = true;
                $force_descriptors_readonly = true;
                $disabled = true;
                $show_grid_controls = false;
                $lock_rating_scale = true;
                break;
        }

    } else {
        // This is a new item
        // If we're referred by a rubric, lock the scale selector
        if (!empty($rubric_referrer_data)) {
            if ($rubric_referrer_data["rating_scale_id"] || !empty($rubric_referrer_data["descriptors"])) {
                $lock_rating_scale = true;
                $force_descriptors_readonly = true;
                $PROCESSED["rating_scale_id"] = (int)$rubric_referrer_data["rating_scale_id"]; // defaulting to 0 is valid
            }
        }
    }

    $PROCESSED["mapped_objectives"] = array();

    if ($notice_message) {
        add_notice($notice_message);
    }
    if ($alert_message) {
        add_generic($alert_message);
    }

    switch ($STEP) {
        case 2 : // Process the POST and put it in PROCESSED

            $attempt_save_item = true;

            if (isset($_POST["itemtype_id"]) && $tmp_input = clean_input($_POST["itemtype_id"], array("trim", "int"))) {
                $PROCESSED["itemtype_id"] = $tmp_input;
            } else {
                if (!$PROCESSED["rref"]) {
                    add_error($translate->_("You must select an <strong>Item Type</strong> for this item."));
                }
            }
            if ($PROCESSED["itemtype_id"]) {
                if ($itemtype_record = Models_Assessments_Itemtype::fetchRowByID($PROCESSED["itemtype_id"])) {
                    $itemtype_shortname = $itemtype_record->getShortname();
                } else {
                    add_error($translate->_("Couldn't determine what item type was selected."));
                }
            }

            if (array_key_exists("item_text", $_POST)) {
                $PROCESSED["item_text"] = $_POST["item_text"];
                $tmp_input = $forms_api->cleanInputString($_POST["item_text"]);
                if ($tmp_input === false) {
                    add_error($translate->_("You must provide <strong>Item Text</strong> for this item."));
                } else {
                    $PROCESSED["item_text"] = $tmp_input;
                }
            }

            if (isset($_POST["item_code"]) && $tmp_input = clean_input($_POST["item_code"], array("trim", "striptags"))) {
                $PROCESSED["item_code"] = $tmp_input;
            } else {
                $PROCESSED["item_code"] = "";
            }

            if (isset($_POST["attributes"]) && $tmp_input = clean_input($_POST["attributes"], array("trim", "striptags"))) {
                $PROCESSED["attributes"] = urldecode($tmp_input);
            } else {
                $PROCESSED["attributes"] = "";
            }

            $PROCESSED["rating_scale_id"] = "";
            if (isset($_POST["rating_scale_id"]) && $tmp_input = clean_input($_POST["rating_scale_id"], array("trim", "int"))) {
                $PROCESSED["rating_scale_id"] = $tmp_input;
            }

            if (isset($_POST["allow_comments"]) && $tmp_input = clean_input($_POST["allow_comments"], array("trim", "int"))) {
                if (isset($_POST["comment_type"]) && $tmp_input = clean_input($_POST["comment_type"], array("trim", "striptags"))) {
                    $PROCESSED["comment_type"] = $tmp_input;
                } else {
                    $PROCESSED["comment_type"] = false;
                    add_error($translate->_("You must select what types of <strong>Comments</strong> to allow."));
                }
            } else {
                $PROCESSED["comment_type"] = "disabled";
            }

            if (isset($_POST["flag_response"]) && is_array($_POST["flag_response"])) {
                $PROCESSED["flag_response"] = array_filter($_POST["flag_response"], function ($response_flag) {
                    return (int)$response_flag;
                });
            } else {
                $PROCESSED["flag_response"] = false;
            }

            if (isset($_POST["item_mandatory"]) && $tmp_input = clean_input($_POST["item_mandatory"], array("trim", "int"))) {
                $PROCESSED["mandatory"] = $tmp_input;
            } else {
                $PROCESSED["mandatory"] = false;
            }

            if (isset($_POST["allow_default"]) && $tmp_input = clean_input($_POST["allow_default"], array("trim", "int"))) {
                $PROCESSED["allow_default"] = $tmp_input;
                if (isset($_POST["default_response"]) && $tmp_input = clean_input($_POST["default_response"], array("trim", "int"))) {
                    $PROCESSED["default_response"] = $tmp_input;
                } else {
                    $PROCESSED["default_response"] = 1;
                }
            } else {
                $PROCESSED["allow_default"] = false;
                $PROCESSED["default_response"] = null;
            }

            if (isset($_POST["ardescriptor_id"]) && is_array($_POST["ardescriptor_id"])) {
                $PROCESSED["ardescriptor_id"] = array_filter($_POST["ardescriptor_id"], function ($ardescriptor_id) {
                    return (int)$ardescriptor_id;
                });
            }

            if (isset($_POST["selected_ardescriptor_ids"]) && is_array($_POST["selected_ardescriptor_ids"])) {
                $PROCESSED["selected_ardescriptor_ids"] = array_filter($_POST["selected_ardescriptor_ids"], function ($selected_ardescriptor_ids) {
                    return (int)$selected_ardescriptor_ids;
                });
            }

            // Process item responses.

            if ($itemtype_shortname == "fieldnote") {

                // Field note specific responses

                $PROCESSED["field_note_responses"]          = array();
                $PROCESSED["field_note_flag_response"]      = array();
                $PROCESSED["field_note_ardescriptor_id"]    = array();
                $PROCESSED["field_note_objective_ids"]      = array();
                $PROCESSED["comment_type"]                  = "disabled";

                if (isset($_POST["field_note_item_responses"]) && is_array($_POST["field_note_item_responses"])) {
                    foreach ($_POST["field_note_item_responses"] as $key => $response) {
                        if ($tmp_input = !clean_input($response, array("trim", "allowedtags"))) {
                            add_error(sprintf($translate->_("You must provide text for <strong>Response %s</strong>."), $key));
                        }
                        $PROCESSED["field_note_responses"][$key] = clean_input($response, array("allowedtags"));
                    }
                }

                if (isset($_POST["field_note_flag_response"]) && is_array($_POST["field_note_flag_response"])) {
                    foreach ($_POST["field_note_flag_response"] as $key => $field_note_flag_response) {
                        if ($tmp_input = clean_input($field_note_flag_response, array("trim", "int"))) {
                            $PROCESSED["field_note_flag_response"][$key] = (int) $tmp_input;
                        }
                    }
                }

                if (isset($_POST["field_note_ardescriptor_id"]) && is_array($_POST["field_note_ardescriptor_id"])) {
                    foreach ($_POST["field_note_ardescriptor_id"] as $key => $field_note_ardescriptor_id) {
                        if ($tmp_input = clean_input($field_note_ardescriptor_id, array("trim", "int"))) {
                            $PROCESSED["field_note_ardescriptor_id"][$key] = $tmp_input;
                        }
                    }
                }

                $PROCESSED["field_note_objective_ids"] = array();
                if (isset($_POST["field_note_objective_ids"]) && is_array($_POST["field_note_objective_ids"])) {
                    foreach ($_POST["field_note_objective_ids"] as $objective_id) {
                        if ($tmp_input = clean_input($objective_id, array("trim", "int"))) {
                            $PROCESSED["field_note_objective_ids"][] = $objective_id;
                            $objective = Models_Objective::fetchRow($objective_id);
                            if ($objective) {
                                $objective_name = $objective->getName();
                            }
                        }
                    }
                } else {
                    add_error($translate->_("Please select a <strong>Curriculum Tag</strong> for this field note."));
                }

            } else {
                // Standard (Non-field note) item responses
                $item_response_types = array(
                    "horizontal_multiple_choice_single",
                    "vertical_multiple_choice_single",
                    "selectbox_single",
                    "horizontal_multiple_choice_multiple",
                    "vertical_multiple_choice_multiple",
                    "selectbox_multiple",
                    "rubric_line",
                    "scale"
                );

                if (in_array($itemtype_shortname, $item_response_types)) {
                    // We require a minimum of 2 item responses for these types.
                    if (!isset($_POST["item_responses"]) || count($_POST["item_responses"]) < 2) {
                        add_error($translate->_("Please add at least two item responses."));
                    } else {

                        if (isset($_POST["item_responses"]) && is_array($_POST["item_responses"])) {
                            $item_ordinal = 0;
                            foreach ($_POST["item_responses"] as $key => $response) {
                                $PROCESSED["responses"][$key] = $response;
                                $item_ordinal++;
                                if (($itemtype_shortname != "rubric_line" && $itemtype_shortname != "scale") &&
                                    $forms_api->cleanInputString($response) === false
                                ) {
                                    add_error(sprintf($translate->_("You must provide text for <strong>Response %s</strong>."), $item_ordinal));
                                } else {
                                    $PROCESSED["responses"][$key] = $forms_api->cleanInputString($response);
                                }
                            }
                        }

                        // scales and rubric_lines must have response descriptors
                        if ($itemtype_shortname == "rubric_line" || $itemtype_shortname == "scale") {
                            if (!isset($PROCESSED["ardescriptor_id"])) {
                                add_error($translate->_("Please select item response descriptors."));
                            } else {
                                if (count($PROCESSED["ardescriptor_id"]) != count($PROCESSED["responses"])) {
                                    $response_position = 0;
                                    foreach ($PROCESSED["responses"] as $response_ordinal => $response_text) {
                                        $response_position++;
                                        if (!isset($PROCESSED["ardescriptor_id"][$response_ordinal])) {
                                            add_error(sprintf($translate->_("Please select a response descriptor for <strong>Response %s</strong>."), $response_position));
                                        }
                                    }
                                }
                                if (count($PROCESSED["ardescriptor_id"]) != count(array_unique($PROCESSED["ardescriptor_id"]))) {
                                    add_error($translate->_("Unable to add responses with duplicate response descriptor(s). Please ensure you have selected unique response descriptors."));
                                }
                            }
                        }
                    }
                    if (!$force_descriptors_readonly) {
                        $show_grid_controls = true;
                    }
                }

                $PROCESSED["objective_ids"] = array();
                if ((isset($_POST["mapped_objective_ids"])) && (is_array($_POST["mapped_objective_ids"]))) {
                    if (isset($_POST["mapped_objective_breadcrumbs"]) && is_array($_POST["mapped_objective_breadcrumbs"])) {
                        $PROCESSED["objectives_breadcrumbs"] = array_map(function($breadcrumb) {
                            return clean_input($breadcrumb, array("trim", "striptags"));
                        }, $_POST["mapped_objective_breadcrumbs"]);
                    } else {
                        $PROCESSED["objectives_breadcrumbs"] = array();
                    }

                    foreach ($_POST["mapped_objective_ids"] as $objective_id) {
                        $metadata = array();
                        if ((isset($_POST["mapped_objective_tree_ids_{$objective_id}"])) && (is_array($_POST["mapped_objective_tree_ids_{$objective_id}"]))) {
                            $PROCESSED["objective_tree_ids"][$objective_id] = array_map("intval", $_POST["mapped_objective_tree_ids_{$objective_id}"]);
                            $objective_info = Models_Objective::fetchRow($objective_id);
                            if ($objective_info) {
                                foreach ($PROCESSED["objective_tree_ids"][$objective_id] as $tree_id) {
                                    $metadata = array("tree_node_id" => $tree_id);
                                    if (array_key_exists($tree_id, $PROCESSED["objectives_breadcrumbs"])) {
                                        $metadata["breadcrumb"] = $PROCESSED["objectives_breadcrumbs"][$tree_id];
                                    }

                                    $metadata = count($metadata) ? json_encode($metadata) : null;
                                    $PROCESSED["mapped_objectives"][] = array_merge($objective_info->toArray(), array("objective_metadata" => $metadata));
                                }
                            }
                        } else {
                            $objective_info = Models_Objective::fetchRow($objective_id);
                            if ($objective_info) {
                                $PROCESSED["mapped_objectives"][] = array_merge($objective_info->toArray(), array("objective_metadata" => null));
                            }
                        }

                        $objective_id = clean_input($objective_id, array("trim", "int"));
                        if ($objective_id && isset($PROCESSED["objective_ids"]) && @count($PROCESSED["objective_ids"])) {
                            foreach ($PROCESSED["objective_ids"] as $temp_objective_id) {
                                if ($temp_objective_id == $objective_id) {
                                    add_error($translate->_("You cannot have more than one identical <strong>objective</strong> associated with an item."));
                                }
                            }
                        }

                        $PROCESSED["objective_ids"][] = $objective_id;
                    }
                }
            }

            // PROCESSED array built, let's load it into our forms api for saving later.

            // Build the save-able data from the processed array
            $save_item_data = array(
                "item" => array(
                    "item_id" => $PROCESSED["item_id"],
                    "organisation_id" => $PROCESSED["organisation_id"],
                    "itemtype_id" => $PROCESSED["itemtype_id"],
                    "item_code" => $PROCESSED["item_code"],
                    "item_text" => $PROCESSED["item_text"],
                    "item_description" => $PROCESSED["item_description"],
                    "comment_type" => $PROCESSED["comment_type"],
                    "rating_scale_id" => $PROCESSED["rating_scale_id"],
                    "mandatory" => $PROCESSED["mandatory"],
                    "allow_default" => $PROCESSED["allow_default"],
                    "default_response" => $PROCESSED["default_response"],
                    "attributes" => $PROCESSED["attributes"] // encode the (presumably) json string
                ),
                "flag_response" => $PROCESSED["flag_response"],
                "responses" => @$PROCESSED["responses"],
                "descriptors" => @$PROCESSED["ardescriptor_id"],
                "objectives" => @$PROCESSED["objective_ids"],
                "objectives_tree_ids" => @$PROCESSED["objective_tree_ids"],
                "objectives_breadcrumbs" => @$PROCESSED["objectives_breadcrumbs"]
            );

            $temp_item_id = $PROCESSED["item_id"] ? $PROCESSED["item_id"] : 0; // 0 means new item (this can't be null due to the storage mechanism in the base class)
            $forms_api->loadItemData($save_item_data, $temp_item_id);
            $item_data = $forms_api->fetchItemData($temp_item_id, false); // re-fetch the item data since we've processed some new data.
        break;
        case 1 :
            $PROCESSED["objective_ids"] = array();
            $PROCESSED["mapped_objectives"] = array();
            if ($item) {
                $objectives = $item->getItemObjectives();
                if ($objectives) {
                    foreach ($objectives as $objective) {
                        $PROCESSED["objective_ids"][] = $objective->getObjectiveID();
                        $objective_info = Models_Objective::fetchRow($objective->getObjectiveID());
                        if ($objective_info) {
                            $PROCESSED["mapped_objectives"][] = array_merge($objective_info->toArray(), array("objective_metadata" => $objective->getObjectiveMetadata()));
                        }
                    }
                }
            }

        break;
    }

    $itemtypes = array();
    if ($PROCESSED["rref"]) {
        // Limit the selectable itemtypes if we're in a rubric
        $itemtypes = $forms_api->getAllowableRubricItemTypes();

        // It could be the case that the item type of this item that is added to this rubric is actually invalid (e.g. a
        // horizontal multiple choice instead of rubric_line/scale item). This is possible in some legacy data.
        // Instead of forcing an overwrite, we just load the previous non-rubric approved version
        if (!array_key_exists($PROCESSED["itemtype_id"], $itemtypes)) {
            if ($record = Models_Assessments_Itemtype::fetchRowByID($PROCESSED["itemtype_id"])) {
                // Add the item type
                $itemtypes[$record->getItemtypeID()] = $record;
            }
        }
    } else {
        // Otherwise, all itemtypes
        $itemtypes = $forms_api->getAllowableItemTypes(); // everything except for "Individuals" TODO: this should be derived from data stored in the database.
    }

    // Attempt to save this item.
    if ($attempt_save_item && !$ERROR) {

        $item_saved = false;
        if ($clone_on_edit) {
            if ($forms_api->copyItem($save_item_data["item"]["item_id"], $PROCESSED["item_text"], true)) {
                // Item was successfully cloned. So update it with the new data.
                $item_saved = $forms_api->saveItem($save_item_data);
            } else {
                add_error($translate->_("Unable to copy the item."));
            }
        } else {
            $item_saved = $forms_api->saveItem($save_item_data);
        }

        // Save the item, when it fails, this returns false, but the API retains the invalid/incomplete posted data inside it.
        if ($item_saved) {

            if ($itemtype_shortname == "fieldnote") {
                // Field notes must have their responses and objectives saved separately
                $forms_api->saveItemFieldNoteProperties(
                    $PROCESSED["field_note_responses"],
                    $PROCESSED["field_note_flag_response"],
                    $PROCESSED["field_note_ardescriptor_id"],
                    $PROCESSED["field_note_objective_ids"]
                );
            }

            // Successfully saved, grab the updated item ID (new one if adding)
            $new_item_id = $forms_api->getItemID();

            if ($REQUEST_MODE == "add") {
                // If we're creating a new item, check if we have to attach it to a form or rubric

                if ($PROCESSED["rref"] && $PROCESSED["fref"]) {
                    // Our referrer was specified as a rubric. So let's attach this item to that rubric.
                    if (!$forms_api->attachItemsToRubric($rubric_referrer_data["rubric_id"], array($new_item_id))) {
                        foreach ($forms_api->getErrorMessages() as $message) {
                            add_error($message);
                        }
                    }
                    // Our referrer was also a form, so attach this item to that.
                    if (!$forms_api->attachItemsToForm($form_referrer_data["form_id"], array($new_item_id), $rubric_referrer_data["rubric_id"])) {
                        foreach ($forms_api->getErrorMessages() as $message) {
                            add_error($message);
                        }
                    }

                } else if ($PROCESSED["fref"]) {
                    // Our referrer was a form, so attach this item to that.
                    if (!$forms_api->attachItemsToForm($form_referrer_data["form_id"], array($new_item_id))) {
                        foreach ($forms_api->getErrorMessages() as $message) {
                            add_error($message);
                        }
                    }

                } else if ($PROCESSED["rref"]) {
                    // Our referrer was specified as a rubric. So let's replaced the cloned item
                    if (!$forms_api->attachItemsToRubric($rubric_referrer_data["rubric_id"], array($new_item_id))) {
                        foreach ($forms_api->getErrorMessages() as $message) {
                            add_error($message);
                        }
                    }
                }

            } else if ($clone_on_edit) {
                // CLONING/EDITING

                if ($PROCESSED["rref"]) {
                    // Our referrer was specified as a rubric. So let's replaced the cloned item
                    if (!$forms_api->replaceItemInRubric($rubric_referrer_data["rubric_id"], $PROCESSED["item_id"], $new_item_id)) {
                        foreach ($forms_api->getErrorMessages() as $message) {
                            add_error($message);
                        }
                    }
                }
            }

            $referrer_type = $translate->_("item");
            if ($PROCESSED["rref"]) {
                $referrer_type = $translate->_("Grouped Item");
            } else if ($PROCESSED["fref"]) {
                $referrer_type = $translate->_("form");
            }
            $action_taken = $REQUEST_MODE == "edit" ? $translate->_("edited") : $translate->_("added");

            // Default redirect is to go back to the newly created item page.
            $url = ENTRADA_URL."/admin/assessments/items/?section=edit-item&item_id=$new_item_id";
            if ($new_url = Entrada_Utilities_FormStorageSessionHelper::determineReferrerURI($PROCESSED["fref"], $PROCESSED["rref"])) {
                // Change URL if referral data is supplied
                $url = $new_url;
            }

            if (!$ERROR) {
                $success_msg = sprintf($translate->_("The item has been successfully %s. You will be redirected back to the %s. Please <a href=\"%s\">click here</a> if you do not wish to wait."), $action_taken, $referrer_type, $url);
                add_success($success_msg);
                $ONLOAD[] = "setTimeout(\"window.location='$url'\", 5000);";
            } else {
                $STEP = 1;
            }

        } else {
            // Failed
            add_error($translate->_("Unable to save item."));
            foreach ($forms_api->getErrorMessages() as $error_message) {
                add_error($translate->_($error_message));
            }
        }
    }

    $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
    if ($flash_messages) {
        foreach ($flash_messages as $message_type => $messages) {
            switch ($message_type) {
                case "error" :
                    echo display_error($messages);
                    break;
                case "success" :
                    echo display_success($messages);
                    break;
                case "notice" :
                default :
                    echo display_notice($messages);
                    break;
            }
        }
    }

    // Fetch response descriptors relevant to the item
    if ($item && $item->getOne45ElementID()) {
        $all_response_descriptors = Models_Assessments_Response_Descriptor::fetchDistinctByOrganisationID($ENTRADA_USER->getActiveOrganisation());
    } else {
        $all_response_descriptors = Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada");
    }

    // Fetch all descriptors and format them to be used as a datasource for the AdvancedSearch widget.
    // Include any descriptors that may have been given to us that are attached to the rubric, even if they are out of org scope.
    $formatted_all_response_descriptors = array();
    if (!empty($all_response_descriptors)) {
        foreach ($all_response_descriptors as $response_descriptor) {
            $formatted_all_response_descriptors[$response_descriptor->getID()] = array("target_id" => $response_descriptor->getID(), "target_label" => $response_descriptor->getDescriptor());
        }
        // Add the ones from the bounding rubric
        if (isset($PROCESSED["rubric_descriptors"]) && is_array($PROCESSED["rubric_descriptors"])) {
            foreach ($PROCESSED["rubric_descriptors"] as $selected_rubric_descriptors) {
                if ($specific_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($selected_rubric_descriptors)) {
                    $formatted_all_response_descriptors[$specific_descriptor->getID()] = array("target_id" => $specific_descriptor->getID(), "target_label" => $specific_descriptor->getDescriptor());
                }
            }
        }
        if (isset($item_data["descriptors"]) && !empty($item_data["descriptors"])) {
            foreach ($item_data["descriptors"] as $descriptor_id => $descriptor_data) {
                $formatted_all_response_descriptors[$descriptor_id] = array("target_id" => $descriptor_id, "target_label" => $descriptor_data["descriptor"]);
            }
        }
    }

    // Sort descriptors by name. We can't uasort since advanced search javascript will not honour the keys and ignore the sort.
    usort($formatted_all_response_descriptors, function($a, $b) {
        return ($a["target_label"] > $b["target_label"]);
    });

    // Determine what flags to show on the editor.
    // We only show non 1 or 0 values when the current organisation uses specially defined flag values (via system settings->flag severity levels).
    // 0 is a hardcoded default for "Not Flagged", and 1 is a global (organisation agnostic) default flag value ("Flagged").
    // The flag value of 1 corresponds to the primary key of 1 in the flags table.
    // Other flags are set with arbitrary values and any primary key that is greater than 1.
    $custom_flags = Models_Assessments_Flag::organisationUsesCustomFlags($ENTRADA_USER->getActiveOrganisation());
    $flags = array();
    $flags_datasource = array();

    if ($custom_flags) {
        $flags_datasource = array();
        $flags = Models_Assessments_Flag::fetchAllByOrganisation($ENTRADA_USER->getActiveOrganisation());
        $flag_keys = array_keys($flags);
        if (count($flag_keys) == 1 && $flag_keys[0] == 1) {
            // There's only one flag, and it's the default, non-organisation specific flag of 1
            // So disable the picker, and use the checkbox instead.
            $flags = array();
            $custom_flags = 0;
        } else {
            $flags_datasource = Entrada_Utilities_AdvancedSearchHelper::buildSearchSource($flags, "flag_id", "title", array("flag_value", "organisation_id", "color") );
        }
        if (!empty($flags_datasource)) {
            // Prepend the "Not Flagged" response
            array_unshift($flags_datasource, array("target_id" => 0, "target_label" => $translate->_("Not Flagged"), "flag_value" => 0, "organisation_id" => null, "color" => "#000000"));
        }
        // Fetch the admin-only flags; these are only ever displayed if an admin-only flag is set on an item.
        $admin_flag_records = Models_Assessments_Flag::fetchAllByOrganisation($ENTRADA_USER->getActiveOrganisation(), "", false, false, false, true);
        $admin_flags_append = array();
        // Check if any of the responses use any admin flags, and add those flags to the datasource.
        if (array_key_exists("flag_response", $PROCESSED) && is_array($PROCESSED["flag_response"])) {
            foreach ($PROCESSED["flag_response"] as $flag) {
                foreach ($admin_flag_records as $admin_flag) {
                    if ($admin_flag->getID() == $flag) {
                        $admin_flags_append[$flag] = $flag;
                    }
                }
            }
        }
        if (!empty($admin_flags_append)) {
            // For the admin flags we found, add them to the datasource
            foreach ($admin_flags_append as $admin_append) {
                $flags_datasource = array_merge(
                    $flags_datasource,
                    Entrada_Utilities_AdvancedSearchHelper::buildSearchSource(
                        array($admin_flag_records[$admin_append]),
                        "flag_id",
                        "title",
                        array("flag_value", "organisation_id", "color")
                    )
                );
            }
        }
    }

    // If we have item data, then we use it to render with, otherwise, set a default
    if ($item_data) {
        $item_responses_render_options = array(
            "item_in_use" => $item_in_use,
            "show_grid_controls" => $show_grid_controls,
            "item_responses" => $item_data["responses"],
            "itemtype_shortname" => $itemtype_shortname,
            "flag_response" => @$PROCESSED["flag_response"],
            "rubric_descriptors" => $PROCESSED["rubric_descriptors"],
            "selected_descriptors" => @$PROCESSED["ardescriptor_id"],
            "advanced_search_descriptor_datasource" => $formatted_all_response_descriptors,
            "readonly_override" => $force_descriptors_readonly,
            "disabled" => $disabled,
            "default_response" => $PROCESSED["default_response"],
            "use_custom_flags" => $custom_flags,
            "flags_datasource" => $flags_datasource
        );
    } else {
        // This is a new item
        $item_responses_render_options = array(
            "item_in_use" => $item_in_use,
            "item_responses" => array(),
            "itemtype_shortname" => $itemtype_shortname,
            "flag_response" => array(),
            "rubric_descriptors" => $PROCESSED["rubric_descriptors"],
            "selected_descriptors" => array(),
            "advanced_search_descriptor_datasource" => $formatted_all_response_descriptors,
            "show_grid_controls" => intval($PROCESSED["rating_scale_id"]) ? false : true,
            "readonly_override" => $force_descriptors_readonly,
            "disabled" => $disabled,
            "use_custom_flags" => $custom_flags,
            "flags_datasource" => $flags_datasource
        );

        // If we're creating and attaching to a rubric, we have to take the rubric response descriptors into account.
        if (!empty($rubric_referrer_data)) {
            if (count($rubric_referrer_data["items"]) > 0) {
                // There are existing items in this rubric, so we limit editability
                $item_responses_render_options["readonly_override"] = true;
                $item_responses_render_options["show_grid_controls"] = false;
            }
        }
    }
    // If we're dealing with a scale item, we ALWAYS disable descriptor editing
    if (isset($rubric_referrer_data["rating_scale_id"]) || $PROCESSED["rating_scale_id"]) {
        $item_responses_render_options["readonly_override"] = true;
        $item_responses_render_options["show_grid_controls"] = false;
    }

    if ($STEP == 2) {
        if ($SUCCESS) {
            $render_form_page = false;
            echo display_success();
        }
        if ($ERROR) {
            echo display_error();
        }
    } else {
        if ($GENERIC) {
            ?>
            <div class="alert alert-info">
                <ul>
                    <?php foreach ($GENERICSTR as $str): ?>
                        <li><?php echo $str ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
        if ($SUCCESS) {
            echo display_success();
        }
        if ($NOTICE) {
            echo display_notice();
        }
        if ($ERROR) {
            echo display_error();
        }
    }

    if ($render_form_page) {

        // Start page render

        $HEAD[] = "<script>var API_URL = \"" . ENTRADA_URL . "/admin/assessments/items?section=api-items" . "\";</script>";
        $HEAD[] = "<script>var SITE_URL = '" . ENTRADA_URL . "';</script>";
        $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
        ?>
        <script type="text/javascript">
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var SCALE_API_URL = '<?php echo ENTRADA_URL . "/admin/assessments/scales?section=api-scales" ?>';
            var submodule_text = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
            var assessment_item_localization = {};
            assessment_item_localization.response_item_template = "<?php echo $translate->_("Response <span>%s</span>"); ?>";
            assessment_item_localization.error_default_json = "<?php echo $translate->_("An unknown error occurred."); ?>"; // json parse error
            assessment_item_localization.error_unable_to_copy = "<?php echo $translate->_("The action could not be completed. Please try again later."); ?>";
            assessment_item_localization.success_removed_item = "<?php echo $translate->_("Response removed."); ?>";

            jQuery(document).ready(function ($) {
                $("#field-note-objective-btn").advancedSearch({
                    api_url: "<?php echo ENTRADA_URL . "/admin/assessments/items?section=api-items"; ?>",
                    resource_url: ENTRADA_URL,
                    filters: {
                        curriculum_tag: {
                            label: "<?php echo $translate->_("Curriculum Tag"); ?>",
                            data_source: "get-fieldnote-objectives",
                            mode: "radio",
                            secondary_data_source: "get-fieldnote-child-objectives",
                            selector_control_name: "field_note_objective_ids[]"
                        }
                    },
                    control_class: "field-note-objective-control",
                    no_results_text: "<?php echo $translate->_(""); ?>",
                    parent_form: $("#item-form"),
                    width: 350
                });
                jQuery(document).ready(function ($) {
                    $("#choose-objective-course-btn").advancedSearch({
                        filters: {
                            course: {
                                label: "<?php echo $translate->_("Course"); ?>",
                                data_source: <?php echo json_encode($formatted_courses_datasource); ?>,
                                mode: "radio",
                                selector_control_name: "course_id",
                                search_mode: false
                            }
                        },
                        control_class: "choose-objective-course-selector",
                        no_results_text: "<?php echo $translate->_(""); ?>",
                        parent_form: $("#item-form"),
                        width: 300,
                        modal: false
                    });
                });
            });
        </script>
        <script src="<?php echo ENTRADA_URL; ?>/javascript/assessments/items/assessments-items-admin.js?release=<?php echo APPLICATION_VERSION; ?>"></script>
        <?php
        $item_id_url = "";
        if (array_key_exists("item_id", $PROCESSED) && $PROCESSED["item_id"]) {
            $item_id_url = "&item_id={$PROCESSED["item_id"]}";
        }
        $item_form_action_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?step=2{$item_id_url}&section={$SECTION}", $PROCESSED["fref"], $PROCESSED["rref"]); ?>
        <form id="item-form" action="<?php echo $item_form_action_url;?>" class="form-horizontal" method="POST">
            <input type="hidden" name="rref" value="<?php echo(isset($PROCESSED["rref"]) ? $PROCESSED["rref"] : ""); ?>"/>
            <input type="hidden" name="fref" value="<?php echo(isset($PROCESSED["fref"]) ? $PROCESSED["fref"] : ""); ?>"/>
            <input type="hidden" name="item_id" value="<?php echo(isset($PROCESSED["item_id"]) ? $PROCESSED["item_id"] : ""); ?>"/>
            <input type="hidden" name="rubric_id" value="<?php echo(isset($PROCESSED["rubric_id"]) ? $PROCESSED["rubric_id"] : ""); ?>"/>
            <input type="hidden" name="attributes" value="<?php echo(isset($PROCESSED["attributes"]) ? urlencode($PROCESSED["attributes"]) : ""); ?>"/>
            <input type="hidden" id="rating_scale_disable" value="<?php echo $lock_rating_scale; ?>"/>

            <h2><?php echo $translate->_("Item Information"); ?></h2>

            <?php
            $authors = array();
            if ($REQUEST_MODE == "edit") {
                $authors = Models_Assessments_Item_Author::fetchAllByItemID($PROCESSED["item_id"], $ENTRADA_USER->getActiveOrganisation());
            }

            $scale_type_datasource = Entrada_Utilities_AdvancedSearchHelper::buildSearchSource(
                $forms_api->getScaleTypesInUse($ENTRADA_USER->getActiveOrganisation(), true),
                "rating_scale_type_id",
                "title",
                array("shortname") // List of additional properties to include in the data source (from the source records)
            );

            // If we're adding a new item, the item_data array will be empty, but we still need to know about the given rating scale (if given to us via a referrer)
            // So we have to fetch the specific item and type.

            if ($PROCESSED["rating_scale_id"] && empty($item_data)) {
                if ($rating_scale_record = Models_Assessments_RatingScale::fetchRowByID($PROCESSED["rating_scale_id"])) {
                    $item_data["rating_scale"]["rating_scale_id"] = $PROCESSED["rating_scale_id"];
                    $item_data["rating_scale"]["rating_scale_title"] = $rating_scale_record->getRatingScaleTitle();
                    if ($rating_scale_record->getRatingScaleID()) {
                        if ($rating_scale_type_record = Models_Assessments_RatingScale_Type::fetchRowByID($rating_scale_record->getRatingScaleType())) {
                            $item_data["rating_scale_type"]["rating_scale_type_id"] = $rating_scale_type_record->getID();
                            $item_data["rating_scale_type"]["shortname"] = $rating_scale_type_record->getShortname();
                            $item_data["rating_scale_type"]["title"] = $rating_scale_type_record->getTitle();
                        }
                    }
                }
            }

            /* TODO: In the future, refactor this so that the item_data array always exists and can be generated with defaults,
             * including any inferred properties (such as rating scale data). */

            // Render basic form controls; item type/text/code
            $item_information_view = new Views_Assessments_Forms_Sections_ItemInformation();
            $item_information_view->render(
                array(
                    "form_mode" => $REQUEST_MODE,
                    "item_id" => $PROCESSED["item_id"],
                    "authors" => $authors,
                    "item_types" => $itemtypes,
                    "item_in_use" => $lock_itemtype,
                    "itemtype_id" => $PROCESSED["itemtype_id"],
                    "itemtype_shortname" => $itemtype_shortname,
                    "mandatory" => $PROCESSED["mandatory"],
                    "comment_type" => @$PROCESSED["comment_type"],
                    "allow_default" => $PROCESSED["allow_default"],
                    "item_code" => $PROCESSED["item_code"],
                    "item_text" => $PROCESSED["item_text"],
                    "disabled" => $disabled,

                    // Scale related
                    "lock_rating_scale" => $lock_rating_scale,
                    "scale_type_datasource" => $scale_type_datasource, // For advanced search
                    "rating_scale_id" => @$item_data["rating_scale"]["rating_scale_id"],
                    "rating_scale_title" => @$item_data["rating_scale"]["rating_scale_title"],
                    "rating_scale_type_id" => @$item_data["rating_scale_type"]["rating_scale_type_id"],
                    "rating_scale_type_shortname" => @$item_data["rating_scale_type"]["shortname"],
                    "rating_scale_type_title" => @$item_data["rating_scale_type"]["title"],
                    "rating_scale_deleted" => @$item_data["rating_scale"]["deleted_date"]
                )
            );
            ?>

            <div class="row-fluid space-above">
                <?php
                    $back_button = new Views_Assessments_Forms_Controls_BackToReferrerButton();
                    $back_button->render(array("referrer_url" => $session_referrer_url, "referrer_type" => $session_referrer_type));
                ?>
                <?php if (!$disabled): ?>
                <input type="submit" class="btn btn-primary space-left pull-right" value="<?php echo $translate->_("Save"); ?>"/>
                <?php endif; ?>
                <a id="copy-item-link" href="#copy-item-modal" data-toggle="modal" class="btn btn-default pull-right"><i class="icon-share"></i> <?php echo $translate->_("Copy Item"); ?></a>
                <?php if ($show_copy_and_attach_button): ?>
                    <a id="copy-item-link" href="#copy-attach-item-modal" data-toggle="modal" class="btn btn-default pull-right space-right"><i class="icon-share"></i> <?php echo $translate->_("Copy & Attach Item"); ?></a>
                <?php endif; ?>
            </div>
            <?php

                // Render item responses section
                $response_section_view = new Views_Assessments_Forms_Sections_ItemResponses(array("id" => "response-section", "class" => Entrada_Assessments_Forms::canHaveResponses($itemtype_shortname) ? "" : "hide"));
                $response_section_view->render($item_responses_render_options);

                // Render fieldnote edit controls
                $field_note_edit_view = new Views_Assessments_Forms_Sections_FieldNoteResponses();
                $field_note_edit_view->render(
                    array(
                        "response_descriptors" => Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada"),
                        "field_note_responses" => @$PROCESSED["field_note_responses"],
                        "field_note_ardescriptor_id" => @$PROCESSED["field_note_ardescriptor_id"],
                        "field_note_flag_response" => @$PROCESSED["field_note_flag_response"],
                        "objective_name" => $objective_name
                    )
                );

            // Render objective sets selector and mapped objective list.
            ?>
            <div id="objective-options" class="row-fluid <?php echo Entrada_Assessments_Forms::canHaveObjectives($itemtype_shortname) ? "" : "hide"; ?>">
                <div class="span12">
                    <?php if (!$disabled): ?>
                        <div id="objectives-selector" class="pull-left">
                            <h2><?php echo $translate->_("Curriculum Tag Sets"); ?></h2>
                            <div id="objectives-selector-inner">
                                <?php
                                // There is no point in showing the course select if the user only has one course to choose from.
                                if (!$disable_course_select) : ?>
                                    <div class="course-select control-group">
                                        <label for="choose-objective-course-btn" class="control-label form-required"><?php echo $translate->_("Select a Course"); ?></label>
                                        <div class="controls">
                                            <button id="choose-objective-course-btn" class="btn btn-search-filter"<?php echo $lock_course_selector ? " disabled='disabled'" : ""; ?>>
                                                <?php echo (isset($PROCESSED["course_name"])) ? html_encode($PROCESSED["course_name"]) : $translate->_("Browse Courses"); ?>
                                                <i class="icon-chevron-down btn-icon pull-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div id="objectives-selector-error" class="hide"></div>
                                <div id="objectives-selector-controls">
                                    <?php
                                    $objective_model = new Entrada_CBME_CourseObjective();
                                    $objective_sets = $objective_model->fetchObjectiveSetsByCourse($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["course_id"], $ENTRADA_USER->getActiveId());
                                    $objective_set_view = new Views_Assessments_Forms_Objectives_ObjectiveSetSelector();
                                    $objective_set_view->render(array("objective_sets" => $objective_sets));
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif;
                    $mapped_objective_set_view = new Views_Assessments_Forms_Objectives_MappedObjectiveList();
                    $mapped_objective_set_view->render(array("objectives" => $PROCESSED["mapped_objectives"]));
                    ?>
                </div>
            </div>

            <?php if (isset($PROCESSED["itemtype_id"]) && $PROCESSED["itemtype_id"] == "13"): ?>
                <?php if (isset($PROCESSED["field_note_objective_ids"]) && is_array($PROCESSED["field_note_objective_ids"])): ?>
                    <?php foreach ($PROCESSED["field_note_objective_ids"] as $key => $objective_id): ?>
                        <input id="curriculum_tag_<?php echo $objective_id; ?>"
                               class="search-target-control curriculum_tag_search_target_control field-note-objective-control"
                               type="hidden"
                               name="field_note_objective_ids[]"
                               value="<?php echo html_encode($objective_id); ?>"/>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

        </form>

        <?php
        // Render templates for loadTemplate functionality
        $response_row_template = new Views_Assessments_Forms_Templates_ItemResponseRow();
        $response_row_template->render(array(
            "disabled" => $disabled,
            "custom_flags" => $custom_flags
        ));

        $scale_response_row_template = new Views_Assessments_Forms_Templates_ItemScaleResponseRow();
        $scale_response_row_template->render(array(
            "custom_flags" => $custom_flags
        ));

        $objective_set_selector_template = new Views_Assessments_Forms_Templates_ObjectiveSetSelector();
        $objective_set_selector_template->render(array());
        $objective_selector_template = new Views_Assessments_Forms_Templates_ObjectiveSelector();
        $objective_selector_template->render(array());
        $mapped_objective_template = new Views_Assessments_Forms_Templates_MappedObjective();
        $mapped_objective_template->render(array());

        // Helpful modals
        $delete_response_modal = new Views_Assessments_Forms_Modals_DeleteItemResponse();
        $delete_response_modal->render();

        if ($REQUEST_MODE == "edit") {

            // Render copy modal
            $copy_modal = new Views_Assessments_Forms_Modals_CopyItem();
            $copy_modal->render(
                array(
                    "item_id" => $PROCESSED["item_id"],
                    "action_url" => ENTRADA_URL."/admin/assessments/items",
                    "prepopulate_text" => $item_data["item"]["item_text"]
                )
            );

            // Render copy & attach modal
            $copy_modal = new Views_Assessments_Forms_Modals_CopyAttachItem();
            $copy_modal->render(
                array(
                    "item_id" => $PROCESSED["item_id"],
                    "action_url" => ENTRADA_URL."/admin/assessments/items",
                    "prepopulate_text" => $item_data["item"]["item_text"]
                )
            );

        }
    }
}
