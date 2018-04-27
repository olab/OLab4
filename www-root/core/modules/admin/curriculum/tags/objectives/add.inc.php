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
} elseif (!$ENTRADA_ACL->amIAllowed("curriculum", "create", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $ajax = (isset($_GET["ajax"]) && $_GET["ajax"] ? true : false);

    if ($ajax) {
        ob_clear_open_buffers();
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/curriculum/tags?".replace_query(array("section" => "add")), "title" => "Add Curriculum Tag");
    }

    if ((isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], array("nows", "int"))) || (isset($_POST["parent_id"]) && $tmp_input = clean_input($_POST["parent_id"], array("nows", "int")))) {
        $objective_parent = Models_Objective::fetchRow($tmp_input, 1, $ENTRADA_USER->getActiveOrganisation());
        $objective_set = Models_ObjectiveSet::fetchRowByID($objective_parent->getObjectiveSetID());
        $PARENT_ID = $tmp_input;

        $level = Models_Objective::getLevel($PARENT_ID);
        $objectives_order = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $tmp_input);

        $attributes = Models_Objective_TagAttribute::fetchAllByObjectiveSetID($objective_set->getID());
        $map_versions = Models_Curriculum_Map_Versions::fetchAllRecords($ENTRADA_USER->getActiveOrganisation());

        $mode = "add";

        if ($level <= $objective_set->getMaximumLevels()) {
            $objectiveModel = new Models_Objective();
            $status = $objectiveModel->fetchStatus();
            $translationStatus = $objectiveModel->fetchTranslationStatus();
            if ($objective_set->getLanguages() != null) {
                $languages = json_decode($objective_set->getLanguages(), true);
                $num_languages = count($languages);
            }

            // Error Checking
            switch ($STEP) {
                case 2 :
                    $requirements = [];
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
                     * Non-required field "objective_translation_status_id" / Translation
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
                     * Non-required field "map_version_id" / Version Map
                     */
                    if (isset($_POST["map_version_id"]) && $tmp_input = clean_input($_POST["map_version_id"], array("trim", "int"))) {
                        $map_version = $tmp_input;
                    } else {
                        $map_version = null;
                    }

                    /**
                     * Non-required field "linked_tags" / Tags mapped
                     */
                    if (isset($_POST["linked_tags"]) && is_array($_POST["linked_tags"])) {
                        foreach ($_POST["linked_tags"] as $tag) {
                            if ($tmp_input = clean_input($tag, array("trim", "int"))) {
                                $PROCESSED["linked_tags"][] = $tmp_input;
                            }
                        }
                    }
                    if (!has_error()) {
                        $objectives = Models_Objective::fetchAllAfterOrderByParentIdOrganisation($PROCESSED["objective_order"], $PARENT_ID, $ENTRADA_USER->getActiveOrganisation());
                        if ($objectives) {
                            $count = $PROCESSED["objective_order"];
                            foreach ($objectives as $objective) {
                                $count++;
                                if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = ".$db->qstr($objective->getID()))) {
                                    add_error($translate->_("There was a problem adding this objective to the system. The system administrator was informed of this error; please try again later."));
                                    application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
                                }
                            }
                        }

                        $objective = array(
                            "objective_code" => $PROCESSED["objective_code"],
                            "objective_name" => (isset($PROCESSED["objective_title"]) && isset($PROCESSED["objective_title"][DEFAULT_LANGUAGE]) ? $PROCESSED["objective_title"][DEFAULT_LANGUAGE] : ""),
                            "objective_description" => (isset($PROCESSED["objective_description"]) && isset($PROCESSED["objective_description"][DEFAULT_LANGUAGE]) ? $PROCESSED["objective_description"][DEFAULT_LANGUAGE] : ""),
                            "objective_parent" => $PARENT_ID,
                            "objective_set_id" => $objective_set->getID(),
                            "objective_order" => $PROCESSED["objective_order"],
                            "objective_loggable" => $PROCESSED["objective_loggable"],
                            "non_examinable" => $PROCESSED["non_examinable"],
                            "objective_status_id" => $PROCESSED["objective_status_id"],
                            "objective_translation_status_id" => $PROCESSED["objective_translation_status_id"],
                            "admin_notes" => $PROCESSED["admin_notes"],
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getActiveId()
                        );
                        $objective_model = new Models_Objective();
                        $objective_model->fromArray($objective);
                        if ($objective_model->insert()) {
                            $objective_id = $objective_model->getID();

                            //Objective History
                            $history = array();
                            $history["objective_id"] = $objective_id;
                            $history["proxy_id"] = $ENTRADA_USER->getID();
                            $history["history_message"] = "created this tag";
                            $history["history_display"] = 1;
                            $history["history_timestamp"] = time();
                            Models_ObjectiveHistory::insertHistory("objective_history", $history);

                            //Objective Languages
                            if ($num_languages > 1) {
                                foreach ($languages as $language) {
                                    $query = "SELECT `language_id` from `language` WHERE `iso_6391_code` = " .$db->qstr($language);
                                    $result = $db->GetRow($query);
                                    if ($result) {
                                        $objective_model->setTranslation($result["language_id"], $PROCESSED["objective_description"][$language], $PROCESSED["objective_title"][$language]);
                                    }
                                }
                            }
                            //Objective Organisation
                            $objective_organisation = array(
                                "objective_id" => $objective_id,
                                "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            );
                            $objective_org_model = new Models_Objective_Organisation($objective_organisation);
                            if (!$objective_org_model->insert()) {
                                add_error($translate->_("There was a problem adding the organisation to the objective. The system administrator was informed of this error; please try again later."));
                                application_log("error", "There was an error associating an objective set with objective audience. Database said: " . $db->ErrorMsg());
                            }

                            //Linked Objectives
                            if (isset($PROCESSED["linked_tags"]) && !empty($PROCESSED["linked_tags"])) {
                                $linked_objective = array(
                                    "version_id" => $map_version,
                                    "objective_id" => $objective_id,
                                    "active" => 1,
                                );
                                foreach ($PROCESSED["linked_tags"] as $tag_id) {
                                    $linked_objective["target_objective_id"] = $tag_id;
                                    $linked_objective_model = new Models_Objective_LinkedObjective($linked_objective);
                                    if (!$linked_objective_model->insert()) {
                                        add_error($translate->_("There was a problem adding the tag attributes to the system. The system administrator was informed of this error; please try again later."));
                                        application_log("error", "There was an error associating an objective set with tag set attributes. Database said: " . $db->ErrorMsg());
                                    } else {
                                        $target_obj_model = Models_Objective::fetchRow($tag_id);
                                        $target_root = $target_obj_model->getRoot();
                                        $history["history_message"] = "mapped [" . $target_root->getShortMethod() . "] " . $target_obj_model->getShortMethod();
                                        Models_ObjectiveHistory::insertHistory("objective_history", $history);
                                    }
                                }
                            }
                        } else {
                            application_log("error", "There was an error associating an objective with an organisation. Database said: " . $db->ErrorMsg());
                        }
                    }
                    if (!has_error()) {
                        if ($ajax) {
                            add_success($translate->_("You have successfully added <strong>" . html_encode($objective_model->getShortMethod()) . "</strong> to the system."));
                            echo json_encode(array("status" => "success", "data" => $SUCCESSSTR, "parent_id" => $PARENT_ID));
                            exit;
                        } else {
                            $url = ENTRADA_URL . "/admin/curriculum/tags/objectives?set_id=" . $objective_set->getID();

                            add_success(sprintf($translate->_("You have successfully added <strong>%s</strong> to the system.<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), html_encode($objective_model->getShortMethod()),$url));

                            $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";

                            application_log("success", "New Objective [" . $OBJECTIVE_ID . "] added to the system.");
                        }

                    } else {
                        if ($ajax) {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            exit;
                        } else {
                            $STEP = 1;
                        }
                    }
                    break;
                case 1 :
                default :
                    $PROCESSED["objective_parent"] = 0;
                    break;
            }

            // Display Content
            switch ($STEP) {
                case 2 :
                    if (has_success()) {
                        echo display_success();
                    }

                    if (has_notice()) {
                        echo display_notice();
                    }

                    if (has_error()) {
                        echo display_error();
                    }
                    break;
                case 1 :
                default:
                    if (has_error()) {
                        echo display_error();
                    }
                    $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/objectives.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
                    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
                    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/objectives/tag_sets.js\"></script>";

                    include("templates/add-form.inc.php");

                    break;
            }
            if ($ajax) {
                exit;
            }
        } else {
            add_error("You cannot add more objectives to this level");
            echo display_error();
            exit;
        }
    } else {
        add_error("The Parent ID was not provided");
        echo display_error();
        exit;
    }
}
