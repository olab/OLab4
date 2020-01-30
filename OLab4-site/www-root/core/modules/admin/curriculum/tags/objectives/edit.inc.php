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
 * This file is used to add objectives in the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_OBJECTIVES")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], array("nows", "int"))) {
        $objective =  Models_Objective::fetchRow($tmp_input);
        $old_objective = Models_Objective::fetchRow($tmp_input);
        $objective_set = Models_ObjectiveSet::fetchRowByID($objective->getObjectiveSetID());
    }

    if ($objective) {
        //object
        $PROCESSED["objective_code"] = $objective->getCode();
        $PROCESSED["objective_parent"] = $objective->getParent();
        $PROCESSED["objective_loggable"] = $objective->getLoggable();
        $PROCESSED["non_examinable"] = $objective->getNonExaminable();
        $PROCESSED["objective_set_id"] = $objective->getObjectiveSetID();
        $PROCESSED["objective_status_id"] = $objective->getStatus();
        $PROCESSED["objective_translation_status_id"] = $objective->getTranslationStatusId();
        $PROCESSED["admin_notes"] = $objective->getAdminNotes();
        $PROCESSED["objective_order"] = $objective->getOrder();

        $mode = "edit";

        $objectiveId = $objective->getID();
        $objectiveModel = new Models_Objective();
        $status = $objectiveModel->fetchStatus();
        $translationStatus = $objectiveModel->fetchTranslationStatus();

        $attributes = Models_Objective_TagAttribute::fetchAllByObjectiveSetID($objective_set->getID());
        $linked_objectives = Models_Objective_LinkedObjective::fetchAllByTargetObjectiveID($objectiveId);
        if ($objective_set->getLanguages() != null) {
            $languages = json_decode($objective_set->getLanguages(), true);
            $num_languages = count($languages);
            if (!empty($languages)) {
                foreach ($languages as $key => $language) {
                    $query = "SELECT `language_id` from `language` WHERE `iso_6391_code` = " .$db->qstr($language);
                    $result = $db->GetRow($query);
                    if ($result) {
                        $translation = $objective->getTranslation($result["language_id"]);
                        if ($translation) {
                            $PROCESSED["objective_description"][$language] = $translation["objective_description"];
                            $PROCESSED["objective_title"][$language] = $translation["objective_name"];
                        }
                    }
                }
            }
        }
        if (!isset($PROCESSED["objective_description"])) {
            $PROCESSED["objective_description"][DEFAULT_LANGUAGE] = $objective->getDescription();
        }
        if (!isset($PROCESSED["objective_title"])) {
            $PROCESSED["objective_title"][DEFAULT_LANGUAGE] = $objective->getName();
        }
        $old_language_title = $PROCESSED["objective_title"];
        $old_language_description = $PROCESSED["objective_description"];

        $objectives_order = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $objective->getParent());
        $first_id = reset($objectives_order)->getID();
        $first = ($first_id != $objectiveId ? $first_id : false);
        $last_id = end($objectives_order)->getID();
        $last = ($last_id != $objectiveId ? $last_id : false);
        $ids = [];
        foreach ($objectives_order as $key => $obj) {
            $ids[$key] = $obj->getID();
        }

        $currentKey = array_search($objectiveId, $ids);
        $next = (array_key_exists($currentKey+1, $objectives_order) ? $objectives_order[$currentKey+1] : false);
        $previous = (array_key_exists($currentKey-1, $objectives_order) ? $objectives_order[$currentKey-1] : false);

        if (isset($_POST["map_version_id"]) && $tmp_input = clean_input($_POST["map_version_id"], array("trim", "int"))) {
            $map_version = $tmp_input;
        } else {
            $map_version = null;
        }
        $target_linked = Models_Objective_LinkedObjective::fetchAllByObjectiveID($objectiveId, $map_version);
        $objectives_linked = Models_Objective_LinkedObjective::fetchAllByTargetObjectiveID($objectiveId);
        $PROCESSED["linked_tags"] = [];
        if ($target_linked) {
            foreach ($target_linked as $target) {
                $PROCESSED["linked_tags"][] = $target->getTargetObjectiveId();
            }
        }

        $map_versions = Models_Curriculum_Map_Versions::fetchAllRecords($ENTRADA_USER->getActiveOrganisation());

        // Error Checking
        switch ($STEP) {
            case 2 :
                $requirements = [];

                if (!$ENTRADA_ACL->amIAllowed("objective", "update", false)) {
                    add_error($translate->_("You do not have the permissions required to update this tag."));

                    application_log("error", "User tried to edit an curriculum tag [" . $objectiveId . "] they did not have access to.");
                }

                if ($objective_set->getRequirements() != null) {
                    $requirements = json_decode($objective_set->getRequirements(), true);
                }

                /**
                 * Non-required field "objective_status_id" / Status
                 */
                if (isset($_POST["objective_status_id"]) && ($tmp_input = clean_input($_POST["objective_status_id"], array("trim", "int")))) {
                    $PROCESSED["objective_status_id"] = $tmp_input;
                } else {
                    add_error($translate->_("The <strong>Status</strong> is a required field."));
                }

                /**
                 * Non-required field "objective_translation_status_id" / Translations
                 */
                if (isset($_POST["objective_translation_status_id"]) && ($tmp_input = clean_input($_POST["objective_translation_status_id"], array("trim", "int")))) {
                    $PROCESSED["objective_translation_status_id"] = $tmp_input;
                } else {
                    $PROCESSED["objective_translation_status_id"] = 0;
                }

                /**
                 * Non-required field "objective_code" / Tag code
                 */
                if (isset($_POST["objective_code"]) && ($objective_code = clean_input($_POST["objective_code"], array("notags", "trim")))) {
                    $PROCESSED["objective_code"] = $objective_code;
                } else {
                    if (isset($requirements["code"]) && $requirements["code"]["required"]) {
                        add_error($translate->_("The <strong>Code</strong> is a required field."));
                    } else {
                        $PROCESSED["objective_code"] = "";
                    }
                }

                /**
                 * Non-required field "objective_name" / Tag title
                 */
                if (isset($_POST["objective_title"]) && is_array($_POST["objective_title"])) {
                    foreach ($_POST["objective_title"] as $key => $objective_title) {
                        if ($tmp_input = clean_input($objective_title, array("notags", "trim"))) {
                            $PROCESSED["objective_title"][$key] = $tmp_input;
                        } else {
                            if (isset($requirements["title"]) && $requirements["title"]["required"]) {
                                add_error(sprintf($translate->_("The <strong>%sTitle</strong> is a required field."), ($num_languages > 1 ? ucfirst($key) . " "  : "")));
                            } else {
                                $PROCESSED["objective_title"][$key] = "";
                            }
                        }
                    }
                }

                /**
                 * Non-required field "objective_description" / Tag description
                 */
                if (isset($_POST["objective_description"]) && is_array($_POST["objective_description"]) && !empty($_POST["objective_description"])) {
                    foreach ($_POST["objective_description"] as $key => $objective_description) {
                        if ($tmp_input = clean_input($objective_description, array("notags", "trim"))) {
                            $PROCESSED["objective_description"][$key] = $tmp_input;
                        } else {
                            if (isset($requirements["description"]) && $requirements["description"]["required"]) {
                                add_error(sprintf($translate->_("The <strong>%sDescription</strong> is a required field."), ($num_languages > 1 ? ucfirst($key) . " "  : "")));
                            } else {
                                $PROCESSED["objective_description"][$key] = "";
                            }
                        }
                    }
                }

                /**
                 * Non-required field "objective_order" / Display Order
                 */
                if (isset($_POST["objective_order"]) && ($objective_order = clean_input($_POST["objective_order"], ["int"]))) {
                    $PROCESSED["objective_order"] = $objective_order;
                } else {
                    $PROCESSED["objective_order"] = 0;
                }

                /**
                 * Non-required field "non_examinable" / Non-Examinable
                 */
                if (isset($_POST["non_examinable"]) && ($tmp_input = clean_input($_POST["non_examinable"], array("trim", "int")))) {
                    $PROCESSED["non_examinable"] = $tmp_input;
                } else {
                    $PROCESSED["non_examinable"] = 0;
                }

                /**
                 * Non-required field "objective_loggable" / loggable in the Experience Logbook
                 */
                if (isset($_POST["objective_loggable"]) && ($tmp_input = clean_input($_POST["objective_loggable"], array("trim", "int")))) {
                    $PROCESSED["objective_loggable"] = $tmp_input;
                } else {
                    $PROCESSED["objective_loggable"] = 0;
                }

                /**
                 * Non-required field "admin_notes" / Admin Notes
                 */
                if (isset($_POST["admin_notes"]) && ($objective_description = clean_input($_POST["admin_notes"], array("notags", "trim")))) {
                    $PROCESSED["admin_notes"] = $objective_description;
                } else {
                    $PROCESSED["admin_notes"] = "";
                }

                /**
                 * Non-required field "linked_tags" / Tags mapped
                 */
                if (isset($_POST["linked_tags"]) && is_array($_POST["linked_tags"])) {
                    foreach ($_POST["linked_tags"] as $tag) {
                        if ($tmp_input = clean_input($tag, array("trim", "int"))) {
                            $linked_tags[] = $tmp_input;
                        }
                    }
                } else {
                    $linked_tags = [];
                }

                if (!has_error()) {
                    $objective->fromArray(
                        array(
                            "objective_code" => $PROCESSED["objective_code"],
                            "objective_name" => $PROCESSED["objective_title"][DEFAULT_LANGUAGE],
                            "objective_description" => $PROCESSED["objective_description"][DEFAULT_LANGUAGE],
                            "objective_parent" => $PROCESSED["objective_parent"],
                            "objective_set_id" => $PROCESSED["objective_set_id"],
                            "objective_loggable" => $PROCESSED["objective_loggable"],
                            "non_examinable" => $PROCESSED["non_examinable"],
                            "objective_status_id" => $PROCESSED["objective_status_id"],
                            "objective_translation_status_id" => $PROCESSED["objective_translation_status_id"],
                            "objective_order" => $PROCESSED["objective_order"],
                            "admin_notes" => $PROCESSED["admin_notes"]
                        )
                    );

                    if ($objective->update()) {

                        // Build history array of data
                        $history = array();
                        $history["objective_id"] = $objective->getID();
                        $history["proxy_id"] = $ENTRADA_USER->getID();
                        $history["history_display"] = 1;
                        $history["history_timestamp"] = time();

                        //order
                        if ($old_objective->getOrder() != $PROCESSED["objective_order"]) {
                            if ($objectives_order) {
                                $count = 0;
                                foreach ($objectives_order as $objective_order) {
                                    if ($old_objective->getID() != $objective_order->getID()) {
                                        if ($count === $PROCESSED["objective_order"]) {
                                            $count++;
                                        }
                                        if ($objective_order->getOrder() != $count) {
                                            if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = " . $db->qstr($objective_order->getID()))) {
                                                add_error("There was a problem updating this objective in the system. The system administrator was informed of this error; please try again later.");
                                                application_log("error", "There was an error updating an objective. Database said: " . $db->ErrorMsg());
                                            }
                                        }
                                        $count++;
                                    }
                                }
                                $history["history_message"] = "updated [order] " . $old_objective->getOrder() . " to " . $PROCESSED["objective_order"];
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        //Objective Languages
                        if ($num_languages > 1) {
                            foreach ($languages as $language) {
                                $query = "SELECT `language_id` from `language` WHERE `iso_6391_code` = " .$db->qstr($language);
                                $result = $db->GetRow($query);
                                if ($result) {
                                    if ($PROCESSED["objective_description"][$language] != $old_language_description[$language]) {
                                        $history["history_message"] = "updated [ ". $language . " description] " . $old_language_description[$language] . " to " .$PROCESSED["objective_description"][$language];
                                        Models_ObjectiveHistory::insertHistory("objective_history", $history);
                                    }
                                    if ($PROCESSED["objective_title"][$language] != $old_language_title[$language]) {
                                        $history["history_message"] = "updated [ ". $language . " title] " . $old_language_title[$language] . " to " .$PROCESSED["objective_title"][$language];
                                        Models_ObjectiveHistory::insertHistory("objective_history", $history);
                                    }
                                    $objective->setTranslation($result["language_id"], $PROCESSED["objective_description"][$language], $PROCESSED["objective_title"][$language]);
                                }
                            }
                        } else {
                            if ($PROCESSED["objective_description"][DEFAULT_LANGUAGE] != $old_language_description[DEFAULT_LANGUAGE]) {
                                $history["history_message"] = "updated [description] " . $old_language_description[DEFAULT_LANGUAGE] . " to " .$PROCESSED["objective_description"][DEFAULT_LANGUAGE];
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                            if ($PROCESSED["objective_title"][DEFAULT_LANGUAGE] != $old_language_title[DEFAULT_LANGUAGE]) {
                                $history["history_message"] = "updated [title] " . $old_language_title[DEFAULT_LANGUAGE] . " to " .$PROCESSED["objective_title"][DEFAULT_LANGUAGE];
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        //Linked Objectives
                        if (!empty($linked_tags)) {
                            $linked_objective = array(
                                "version_id" => $map_version,
                                "objective_id" => $objective->getID(),
                                "active" => 1,
                            );
                            foreach ($linked_tags as $tag_id) {
                                if (!in_array($tag_id, $PROCESSED["linked_tags"])) {
                                    if ($tmp_input = clean_input($tag_id, array("trim", "int"))) {
                                        $linked_objective["target_objective_id"] = $tmp_input;
                                        $linked_objective_model = new Models_Objective_LinkedObjective($linked_objective);
                                        if (!$linked_objective_model->insert()) {
                                            add_error("There was a problem adding the tag attributes to the system. The system administrator was informed of this error; please try again later.");
                                            application_log("error", "There was an error associating an objective set with tag set attributes. Database said: " . $db->ErrorMsg());
                                        } else {
                                            $target_obj_model = Models_Objective::fetchRow($tag_id);
                                            $target_root = $target_obj_model->getRoot();
                                            $history["history_message"] = "mapped [" . $target_root->getShortMethod() . "] " . $target_obj_model->getShortMethod();
                                            Models_ObjectiveHistory::insertHistory("objective_history", $history);
                                        }
                                    }
                                }
                            }
                        }

                        $linked_tags_remove_set  = array_diff($PROCESSED["linked_tags"], $linked_tags);
                        if ($linked_tags_remove_set && is_array($linked_tags_remove_set) && !empty($linked_tags_remove_set)) {
                            foreach ($linked_tags_remove_set as $tag) {
                                $model_tag = Models_Objective_LinkedObjective::fetchRowByObjectiveIdTargetObjectiveID($objective->getID(), $tag);
                                if ($model_tag && is_object($model_tag)) {
                                    $model_tag->setActive(0);
                                    if (!$model_tag->update()) {
                                        add_error($translate->_("There was an error when trying to update a tag mapping into the system."));
                                        application_log("error", "Unable to update tag mapping to the database when editing tag. Database said: " . $db->ErrorMsg());
                                    } else {
                                        $target_obj_model = Models_Objective::fetchRow($tag);
                                        $target_root = $target_obj_model->getRoot();
                                        $history["history_message"] = "unmapped [" . $target_root->getShortMethod() . "] " . $target_obj_model->getShortMethod();
                                        Models_ObjectiveHistory::insertHistory("objective_history", $history);
                                    }
                                }
                            }
                        }
                        // History code
                        if ($PROCESSED["objective_code"] != $old_objective->getCode()) {
                            $history["history_message"] = "updated [ Code ] " . $old_objective->getCode() . " to " . $PROCESSED["objective_code"];
                            Models_ObjectiveHistory::insertHistory("objective_history", $history);
                        }

                        // History tracking
                        if ($PROCESSED["objective_status_id"] != $old_objective->getStatus()) {
                            $changed_from_status = Models_Objective_Status::fetchRowByID($old_objective->getStatus());
                            $changed_to_status = Models_Objective_Status::fetchRowByID($PROCESSED["objective_status_id"]);
                            if ($changed_from_status && $changed_to_status) {
                                $history["history_message"] = "updated [ Status ] " . $changed_from_status->getDescription() . " to " . $changed_to_status->getDescription();
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        // History for non-examinable
                        if ($PROCESSED["non_examinable"] != $old_objective->getNonExaminable()) {
                            if ($PROCESSED["non_examinable"] == 0) {
                                $history["history_message"] = "unchecked [ Non Examinable ]";
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            } else {
                                $history["history_message"] = "checked [ Non Examinable ]";
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        // History for translation status
                        if ($PROCESSED["objective_translation_status_id"] != $old_objective->getTranslationStatusId()) {
                            $changed_from_translation = Models_Objective_TranslationStatus::fetchRowByID($old_objective->getTranslationStatusId());
                            $changed_to_translation = Models_Objective_TranslationStatus::fetchRowByID($PROCESSED["objective_translation_status_id"]);
                            if ($changed_from_translation && $changed_to_translation) {
                                $history["history_message"] = "updated [ Translation ] " . $changed_from_translation->getDescription() . " to " . $changed_to_translation->getDescription();
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        // History for Loggable
                        if ( $PROCESSED["objective_loggable"] != $old_objective->getLoggable() ) {
                            if ($PROCESSED["objective_loggable"] == 0) {
                                $history["history_message"] = "unchecked [ Loggable ]";
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            } else {
                                $history["history_message"] = "checked [ Loggable ]";
                                Models_ObjectiveHistory::insertHistory("objective_history", $history);
                            }
                        }

                        // History for Admin notes
                        if ($PROCESSED["admin_notes"] != $old_objective->getAdminNotes()) {
                            $history["history_message"] = "updated [ Admin Notes ] " . $old_objective->getAdminNotes() . " to " . $PROCESSED["admin_notes"] ;
                            Models_ObjectiveHistory::insertHistory("objective_history", $history);
                        }
                    } else {
                        add_error($translate->_("There was a problem editing the objective. The system administrator was informed of this error; please try again later."));
                        application_log("error", "There was an error editing an objective. Database said: " . $db->ErrorMsg());
                    }
                }

                if (!has_error()) {
                    add_success($translate->_("You have successfully edited this curriculum tag."));
                    echo json_encode(array("status" => "success", "data" => $SUCCESSSTR, "parent_id" => $objective->getParent(), "edit" => true));
                } else {
                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                }
                break;
            case 1 :
            default :
                $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/objectives.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
                $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
                $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/objectives/tag_sets.js\"></script>";

                include("templates/edit-form.inc.php");

                break;
        }
        exit;
    }
}
