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
 * A controller for an assessment plan
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"%s\">%s</a> for assistance."), "mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $form_model = new Models_Assessments_Form();
    $objective_model = new Models_Objective();
    $objective_set_model = new Models_ObjectiveSet();
    $item_response_model = new Models_Assessments_Item_Response();
    $assessment_pan_continer_model = new Models_Assessments_PlanContainer();

    $assessment_plan_form_model = new Models_Assessments_Plan_Form();
    $assessment_plan_form_objectives_model = new Models_Assessments_Plan_Objective();
    $objective_set = $objective_set_model->fetchRowByShortname("contextual_variable");
    $objective_cv_response_set = $objective_set_model->fetchRowByShortname("contextual_variable_responses");

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], array("trim", "int"))) {
        $course_id = $tmp_input;
    } else {
        add_error($translate->_("In order to create an assessment plan a valid course identifier must be supplied."));
    }

    if (isset($_GET["objective_id"]) && $tmp_input = clean_input($_GET["objective_id"], array("trim", "int"))) {
        $objective_id = $tmp_input;
    } else {
        add_error($translate->_("In order to create an assessment plan a valid EPA identifier must be supplied."));
    }

    if (isset($_GET["cbme_objective_tree_id"]) && $tmp_input = clean_input($_GET["cbme_objective_tree_id"], array("trim", "int"))) {
        $objective_tree_id = $tmp_input;
    } else {
        add_error($translate->_("In order to create an assessment plan a valid EPA identifier must be supplied."));
    }

    if (isset($_GET["assessment_plan_container_id"]) && $tmp_input = clean_input($_GET["assessment_plan_container_id"], array("trim", "int"))) {
        $assessment_plan_container_id = $tmp_input;
        $container = $assessment_pan_continer_model->fetchRowByID($assessment_plan_container_id);
        if (!$container) {
            add_error($translate->_("No assessment plan found matching the provided identifier."));
        }
    } else {
        add_error($translate->_("In order to create an assessment plan a valid container identifier must be supplied."));
    }

    if (!$ERROR) {
        $assessment_plan_api = new Entrada_CBME_AssessmentPlan(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "course_id" => $course_id
        ));

        $course = Models_Course::get($COURSE_ID);
        if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
            if ($assessment_plan_api->canAccessPlanRequirements($objective_id, $objective_tree_id, $course_id, $ENTRADA_USER->getActiveOrganisation())) {
                if ($objective_set && $objective_cv_response_set) {
                    $assessment_plan_model = new Models_Assessments_Plan();
                    $assessment_plan = $assessment_plan_model->fetchRowByObjectiveID($objective_id);
                    if ($assessment_plan) {
                        $PROCESSED["title"] = $assessment_plan->getTitle();
                        $PROCESSED["description"] = $assessment_plan->getDescription();
                        $PROCESSED["published"] = $assessment_plan->getPublished();

                        $assessment_plan_form_model = new Models_Assessments_Plan_Form();
                        $assessment_plan_forms = $assessment_plan_form_model->fetchAllByAssessmentPlanID($assessment_plan->getID());
                        if ($assessment_plan_forms) {
                            foreach ($assessment_plan_forms as $assessment_plan_form) {
                                $form = $form_model->fetchRowByID($assessment_plan_form->getFormID());
                                if ($form) {
                                    $form = $form->toArray();
                                    $form_minimum_key = "form_" . $form["form_id"] . "_minimum";
                                    $minimum_assessors_key = "form_" . $form["form_id"] . "_minimum_assessors";
                                    $rating_scale_response_key = "form_" . $form["form_id"] . "_rating_scale_response";
                                    $rating_scale_responses_key = "form_" . $form["form_id"] . "_rating_scale_responses";
                                    $PROCESSED["assessment_forms"][] = $form;
                                    $PROCESSED[$form_minimum_key] = $assessment_plan_form->getMinimumAssessments();
                                    $PROCESSED[$minimum_assessors_key] = $assessment_plan_form->getMinimumAssessors();

                                    if ($assessment_plan_form->getIresponseID()) {
                                        $response = $item_response_model->fetchRowByID($assessment_plan_form->getIresponseID());
                                        if ($response) {
                                            $PROCESSED[$rating_scale_response_key] = $response->toArray();
                                        }
                                    }

                                    /**
                                     * Get the rating scale from the form
                                     */
                                    $form_rating_scale = $assessment_plan_api->fetchFormEntrustmentItem($form["form_id"]);
                                    if ($form_rating_scale) {
                                        $form_rating_scale_responses = $assessment_plan_api->fetchFormEntrustmentItemResponses($form_rating_scale["item_id"]);
                                        if ($form_rating_scale_responses) {
                                            $PROCESSED[$rating_scale_responses_key] = $form_rating_scale_responses;
                                        }
                                    }

                                    $assessment_plan_form_objective_model = new Models_Assessments_Plan_FormObjective();
                                    $assessment_plan_form_objectives = $assessment_plan_form_objective_model->fetchAllByAssessmentPlanFormIDObjectiveSetID($assessment_plan_form->getID(), $objective_set->getID());
                                    if ($assessment_plan_form_objectives) {
                                        foreach ($assessment_plan_form_objectives as $assessment_plan_form_objective) {
                                            $contextual_variable_objective = $objective_model->fetchRow($assessment_plan_form_objective->getObjectiveID());
                                            if ($contextual_variable_objective) {
                                                $contextual_variable_objective = $contextual_variable_objective->toArray();
                                                $contextual_variable_key = "form_" . $form["form_id"] . "_contextual_variables";
                                                $PROCESSED[$contextual_variable_key][] = $contextual_variable_objective;

                                                $assessment_plan_form_cv_response_objectives = $assessment_plan_form_objective_model->fetchAllByAssessmentPlanFormIDObjectiveParentObjectiveSetID($assessment_plan_form->getID(), $contextual_variable_objective["objective_id"], $objective_cv_response_set->getID());
                                                if ($assessment_plan_form_cv_response_objectives) {
                                                    foreach ($assessment_plan_form_cv_response_objectives as $assessment_plan_form_cv_response_objective) {
                                                        $contextual_variable_response_objective = $objective_model->fetchRow($assessment_plan_form_cv_response_objective->getObjectiveID());
                                                        if ($contextual_variable_response_objective) {
                                                            $contextual_variable_response_objective = $contextual_variable_response_objective->toArray();
                                                            $contextual_variable_response_key = "form_" . $form["form_id"] . "_cv_" . $contextual_variable_objective["objective_id"] . "_responses";
                                                            $contextual_variable_response_minimum_key = "form_" . $form["form_id"] . "_contextual_variable_response_" . $contextual_variable_response_objective["objective_id"];
                                                            $PROCESSED[$contextual_variable_response_key][] = $contextual_variable_response_objective;
                                                            $PROCESSED[$contextual_variable_response_minimum_key] = $assessment_plan_form_cv_response_objective->getMinimum();
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                switch ($STEP) {
                    case 2 :
                        $PROCESSED = array();
                        if (isset($_POST["remove_plan"]) && $tmp_input = clean_input($_POST["remove_plan"], array("trim", "striptags"))) {
                            $assessment_plan_model = new Models_Assessments_Plan();
                            $assessment_plan = $assessment_plan_model->fetchRowByObjectiveID($objective_id);
                            if ($assessment_plan) {
                                if (!$assessment_plan->fromArray(array("deleted_date" => time()))->update()) {
                                    add_error($translate->_("An error has occurred while attempting to remove this assessment plan, please try again later."));
                                    application_log("error", "Unable to update assessment plan for primary key = '{$assessment_plan->getID()}' DB said: " . $db->ErrorMsg());
                                } else {
                                    $url = ENTRADA_URL . "/admin/courses/cbme/plans?id=" . $course_id;
                                    add_success(sprintf($translate->_("You have successfully removed  this <strong>Assessment Plan</strong>.<br /><br />You will now be redirected to the assessment plans index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\"><strong>click here</strong></a> to continue."), $url));
                                    $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                                    echo display_success();
                                }
                            }
                        } else {
                            if (isset($_POST["save_as_draft"]) && $tmp_input = clean_input($_POST["save_as_draft"], array("trim", "striptags"))) {
                                $PROCESSED["published"] = 0;
                            } else {
                                $PROCESSED["published"] = 1;
                            }

                            if (isset($_POST["title"]) && $tmp_input = clean_input($_POST["title"], array("trim", "striptags"))) {
                                $PROCESSED["title"] = $tmp_input;
                            } else {
                                add_error($translate->_("Please provide a <strong>Title</strong> for this assessment plan."));
                            }

                            if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                                $PROCESSED["description"] = $tmp_input;
                            } else {
                                $PROCESSED["description"] = null;
                            }

                            if (isset($_POST["assessment_forms"]) && is_array($_POST["assessment_forms"])) {
                                foreach ($_POST["assessment_forms"] as $assessment_form) {
                                    if ($tmp_input = clean_input($assessment_form, array("trim", "int"))) {
                                        $form = $form_model->fetchRowByID($tmp_input);
                                        if ($form) {
                                            $form = $form->toArray();
                                            $form_minimum_key = "form_" . $tmp_input . "_minimum";
                                            $form_contextual_variables_key = "form_" . $tmp_input . "_contextual_variables[]";
                                            if (isset($_POST[$form_minimum_key]) && $minimum_tmp_input = clean_input($_POST[$form_minimum_key], array("trim", "int"))) {
                                                $PROCESSED[$form_minimum_key] = $minimum_tmp_input;
                                            } else {
                                                if ($PROCESSED["published"]) {
                                                    add_error(sprintf($translate->_("Please provide a <strong>Minimum Number of Assessments</strong> for the <strong>%s tool</strong>."), html_encode($form["title"])));
                                                }
                                            }

                                            $form_minimum_assessors_key = "form_" . $tmp_input . "_minimum_assessors";
                                            if (isset($_POST[$form_minimum_assessors_key]) && $minimum_assessors_tmp_input = clean_input($_POST[$form_minimum_assessors_key], array("trim", "int"))) {
                                                $PROCESSED[$form_minimum_assessors_key] = $minimum_assessors_tmp_input;
                                            } else {
                                                if ($PROCESSED["published"]) {
                                                    add_error(sprintf($translate->_("Please provide a <strong>Minimum Number of Assessors</strong> for the <strong>%s tool</strong>."), html_encode($form["title"])));
                                                }
                                            }

                                            $form_rating_scale_response_key = "form_" . $tmp_input . "_rating_scale_response";
                                            if (isset($_POST[$form_rating_scale_response_key]) && $form_rating_scale_response_tmp_input = clean_input($_POST[$form_rating_scale_response_key], array("trim", "int"))) {
                                                $response = $item_response_model->fetchRowByID($form_rating_scale_response_tmp_input);
                                                if ($response) {
                                                    $PROCESSED[$form_rating_scale_response_key] = $response->toArray();
                                                }
                                            } else {
                                                if ($PROCESSED["published"]) {
                                                    add_error(sprintf($translate->_("Please provide a <strong>rating scale response</strong> for the <strong>%s tool</strong>."), html_encode($form["title"])));
                                                }
                                            }

                                            $PROCESSED["assessment_forms"][] = $form;

                                            $contextual_variable_key = "form_" . $tmp_input . "_contextual_variables";
                                            if (array_key_exists($contextual_variable_key, $_POST) && is_array($_POST[$contextual_variable_key])) {
                                                foreach ($_POST[$contextual_variable_key] as $contextual_variable) {
                                                    if ($tmp_input = clean_input($contextual_variable, array("trim", "int"))) {
                                                        $contextual_variable_objective = $objective_model->fetchRow($tmp_input);
                                                        if ($contextual_variable_objective) {
                                                            $contextual_variable_objective = $contextual_variable_objective->toArray();
                                                            $PROCESSED[$contextual_variable_key][] = $contextual_variable_objective;

                                                            $contextual_variable_response_key = "form_" . $form["form_id"] . "_cv_" . $tmp_input . "_responses";
                                                            if (array_key_exists($contextual_variable_response_key, $_POST) && is_array($_POST[$contextual_variable_response_key])) {
                                                                foreach ($_POST[$contextual_variable_response_key] as $contextual_variable_response) {
                                                                    if ($tmp_input = clean_input($contextual_variable_response, array("trim", "int"))) {
                                                                        $objective = $objective_model->fetchRow($tmp_input);
                                                                        if ($objective) {
                                                                            $objective = $objective->toArray();
                                                                            $PROCESSED[$contextual_variable_response_key][] = $objective;
                                                                            $contextual_variable_responses_minimum_key = "form_" . $form["form_id"] . "_contextual_variable_response_" . $tmp_input;
                                                                            if (isset($_POST[$contextual_variable_responses_minimum_key]) && $response_tmp_input = clean_input($_POST[$contextual_variable_responses_minimum_key], array("trim", "int"))) {
                                                                                $PROCESSED[$contextual_variable_responses_minimum_key] = $response_tmp_input;
                                                                            } else {
                                                                                if ($PROCESSED["published"]) {
                                                                                    add_error(sprintf($translate->_("Please provide a <strong>Minimum Number of Responses</strong> for the <strong>%s response</strong>."), html_encode($objective["objective_name"])));
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                if ($PROCESSED["published"]) {
                                                                    add_error(sprintf($translate->_("Please select at least one <strong>Contextual Variable Response</strong> for the <strong>%s Contextual Variable</strong>."), html_encode($contextual_variable_objective["objective_name"])));
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($PROCESSED["published"]) {
                                                    add_error(sprintf($translate->_("Please select at least one <strong>Contextual Variable</strong> for the <strong>%s tool</strong>."), html_encode($form["title"])));
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($PROCESSED["published"]) {
                                    add_error($translate->_("Please select at least one <strong>Assessment Tool</strong> for this assessment plan."));
                                }
                            }

                            if (!$ERROR) {
                                $assessment_plan_model = new Models_Assessments_Plan();
                                $assessment_plan = array();
                                $method = "insert";
                                $assessment_plan_record = $assessment_plan_model->fetchRowByObjectiveID($objective_id);
                                if ($assessment_plan_record) {
                                    $method = "update";
                                    $assessment_plan = $assessment_plan_record->toArray();
                                }

                                $assessment_plan["title"] = $PROCESSED["title"];
                                $assessment_plan["description"] = $PROCESSED["description"];
                                $assessment_plan["assessment_plan_container_id"] = $assessment_plan_container_id;
                                $assessment_plan["objective_id"] = $objective_id;
                                $assessment_plan["published"] = 0;
                                $assessment_plan["valid_from"] = time();
                                $assessment_plan["valid_until"] = time();
                                $assessment_plan["published"] = $PROCESSED["published"];

                                if ($method == "insert") {
                                    $assessment_plan["created_date"] = time();
                                    $assessment_plan["created_by"] = $ENTRADA_USER->getActiveID();
                                } else {
                                    $assessment_plan["assessment_plan_id"] = $assessment_plan_record->getID();
                                    $assessment_plan["updated_date"] = time();
                                    $assessment_plan["updated_by"] = $ENTRADA_USER->getActiveID();
                                }

                                if ($assessment_plan_model->fromArray($assessment_plan)->{$method}()) {
                                    if (isset($PROCESSED["assessment_forms"]) && is_array($PROCESSED["assessment_forms"])) {
                                        $assessment_plan_forms = $assessment_plan_form_model->fetchAllByAssessmentPlanID($assessment_plan_model->getID());
                                        if ($assessment_plan_forms) {
                                            foreach ($assessment_plan_forms as $assessment_plan_form_record) {
                                                $assessment_plan_form_update = array();
                                                $assessment_plan_form_update["updated_date"] = time();
                                                $assessment_plan_form_update["updated_by"] = $ENTRADA_USER->getActiveId();
                                                $assessment_plan_form_update["deleted_date"] = time();
                                                if (!$assessment_plan_form_record->fromArray($assessment_plan_form_update)->update()) {
                                                    add_error($translate->_("An error has occurred while attempting to save assessment plan form data, please try again later."));
                                                    application_log("error", "Unable to update assessment plan form for primary key = '{$assessment_plan_form_record->getID()}'");
                                                } else {
                                                    $assessment_plan_form_model = new Models_Assessments_Plan_Form();
                                                    if ($objective_set) {
                                                        $assessment_plan_form_objectives_model = new Models_Assessments_Plan_FormObjective();
                                                        $assessment_plan_form_cv_objectives = $assessment_plan_form_objectives_model->fetchAllByAssessmentPlanFormIDObjectiveSetID($assessment_plan_form_record->getID(), $objective_set->getID());
                                                        if ($assessment_plan_form_cv_objectives) {
                                                            foreach ($assessment_plan_form_cv_objectives as $assessment_plan_form_objective) {
                                                                $assessment_plan_form_objective_update = array();
                                                                $assessment_plan_form_objective_update["updated_date"] = time();
                                                                $assessment_plan_form_objective_update["updated_by"] = $ENTRADA_USER->getActiveId();
                                                                $assessment_plan_form_objective_update["deleted_date"] = time();
                                                                if (!$assessment_plan_form_objective->fromArray($assessment_plan_form_objective_update)->update()) {
                                                                    add_error($translate->_("An error has occurred while attempting to save assessment plan form data, please try again later."));
                                                                    application_log("error", "Unable to update assessment plan form objective for primary key = '{$assessment_plan_form_objective->getID()}'");
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($objective_cv_response_set) {
                                                        $assessment_plan_form_cv_response_objectives = $assessment_plan_form_objectives_model->fetchAllByAssessmentPlanFormIDObjectiveSetID($assessment_plan_form_record->getID(), $objective_cv_response_set->getID());
                                                        if ($assessment_plan_form_cv_response_objectives) {
                                                            foreach ($assessment_plan_form_cv_response_objectives as $assessment_plan_form_cv_response_objective) {
                                                                $assessment_plan_form_cv_response_objective_update = array();
                                                                $assessment_plan_form_cv_response_objective_update["updated_date"] = time();
                                                                $assessment_plan_form_cv_response_objective_update["updated_by"] = $ENTRADA_USER->getActiveId();
                                                                $assessment_plan_form_cv_response_objective_update["deleted_date"] = time();
                                                                if (!$assessment_plan_form_cv_response_objective->fromArray($assessment_plan_form_cv_response_objective_update)->update()) {
                                                                    add_error($translate->_("An error has occurred while attempting to save assessment plan form data, please try again later."));
                                                                    application_log("error", "Unable to update assessment plan form objective for primary key = '{$assessment_plan_form_cv_response_objective->getID()}'");
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        foreach ($PROCESSED["assessment_forms"] as $form) {
                                            $assessment_plan_form_model = new Models_Assessments_Plan_Form();
                                            $form_mimimum_assessments_key = "form_" . $form["form_id"] . "_minimum";
                                            $form_mimimum_assessors_key = "form_" . $form["form_id"] . "_minimum_assessors";
                                            $form_rating_scale_response_key = "form_" . $form["form_id"] . "_rating_scale_response";
                                            if (isset($PROCESSED[$form_mimimum_assessments_key]) && isset($PROCESSED[$form_mimimum_assessors_key])) {
                                                $assessment_plan_form = array(
                                                    "assessment_plan_id" => $assessment_plan_model->getID(),
                                                    "form_id" => $form["form_id"],
                                                    "minimum_assessments" => $PROCESSED[$form_mimimum_assessments_key],
                                                    "iresponse_id" => $PROCESSED[$form_rating_scale_response_key]["iresponse_id"],
                                                    "minimum_assessors" => $PROCESSED[$form_mimimum_assessors_key],
                                                    "created_date" => time(),
                                                    "created_by" => $ENTRADA_USER->getActiveId(),
                                                );

                                                if ($assessment_plan_form_model->fromArray($assessment_plan_form)->insert()) {
                                                    $assessment_plan_form_objectives_model = new Models_Assessments_Plan_FormObjective();
                                                    $contextual_variable_key = "form_" . $form["form_id"] . "_contextual_variables";
                                                    if (isset($PROCESSED[$contextual_variable_key]) && is_array($PROCESSED[$contextual_variable_key])) {
                                                        if ($objective_set && $objective_cv_response_set) {
                                                            foreach ($PROCESSED[$contextual_variable_key] as $contextual_variable) {
                                                                $assessment_plan_form_objectives_model = new Models_Assessments_Plan_FormObjective();
                                                                $assessment_plan_contextual_variables = array();
                                                                $assessment_plan_contextual_variables["assessment_plan_id"] = $assessment_plan_model->getID();
                                                                $assessment_plan_contextual_variables["assessment_plan_form_id"] = $assessment_plan_form_model->getID();
                                                                $assessment_plan_contextual_variables["objective_id"] = $contextual_variable["objective_id"];
                                                                $assessment_plan_contextual_variables["objective_parent"] = 0;
                                                                $assessment_plan_contextual_variables["objective_set_id"] = $objective_set->getID();
                                                                $assessment_plan_contextual_variables["minimum"] = null;
                                                                $assessment_plan_contextual_variables["created_date"] = time();
                                                                $assessment_plan_contextual_variables["created_by"] = $ENTRADA_USER->getActiveId();
                                                                if (!$assessment_plan_form_objectives_model->fromArray($assessment_plan_contextual_variables)->insert()) {
                                                                    add_error($translate->_("An error has occurred while attempting to save assessment plan form data, please try again later."));
                                                                    application_log("error", "Unable to update assessment plan form objective for primary key = '{$assessment_plan_form_objectives_model->getID()}'");
                                                                } else {
                                                                    $contextual_variable_response_key = "form_" . $form["form_id"] . "_cv_" . $contextual_variable["objective_id"] . "_responses";
                                                                    if (isset($PROCESSED[$contextual_variable_response_key]) && is_array($PROCESSED[$contextual_variable_response_key])) {
                                                                        foreach ($PROCESSED[$contextual_variable_response_key] as $contextual_variable_response) {
                                                                            $contextual_variable_response_minimum_key = "form_" . $form["form_id"] . "_contextual_variable_response_" . $contextual_variable_response["objective_id"];
                                                                            if (isset($PROCESSED[$contextual_variable_response_minimum_key])) {
                                                                                $assessment_plan_form_objectives_model = new Models_Assessments_Plan_FormObjective();
                                                                                $assessment_plan_contextual_variable_responses = array();
                                                                                $assessment_plan_contextual_variable_responses["assessment_plan_id"] = $assessment_plan_model->getID();
                                                                                $assessment_plan_contextual_variable_responses["assessment_plan_form_id"] = $assessment_plan_form_model->getID();
                                                                                $assessment_plan_contextual_variable_responses["objective_id"] = $contextual_variable_response["objective_id"];
                                                                                $assessment_plan_contextual_variable_responses["objective_parent"] = $contextual_variable["objective_id"];
                                                                                $assessment_plan_contextual_variable_responses["objective_set_id"] = $objective_cv_response_set->getID();
                                                                                $assessment_plan_contextual_variable_responses["minimum"] = $PROCESSED[$contextual_variable_response_minimum_key];
                                                                                $assessment_plan_contextual_variable_responses["created_date"] = time();
                                                                                $assessment_plan_contextual_variable_responses["created_by"] = $ENTRADA_USER->getActiveId();
                                                                                if (!$assessment_plan_form_objectives_model->fromArray($assessment_plan_contextual_variable_responses)->insert()) {
                                                                                    add_error($translate->_("An error has occurred while attempting to save assessment plan form data, please try again later."));
                                                                                    application_log("error", "Unable to update assessment plan form objective for primary key = '{$assessment_plan_form_objectives_model->getID()}'");
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    add_error($translate->_("An error has occurred while attempting to save this assessment plan, please try again later."));
                                                    application_log("error", "Unable to save assessment plan form form_id = '{$form["form_id"]}', method = insert");
                                                }
                                            }
                                        }
                                    } else {
                                        $error = false;
                                        $assessment_plan_forms = $assessment_plan_form_model->fetchAllByAssessmentPlanID($assessment_plan_model->getID());
                                        if ($assessment_plan_forms) {
                                            foreach ($assessment_plan_forms as $assessment_plan_form) {
                                                if (!$assessment_plan_form->fromArray(array("updated_by" => $ENTRADA_USER->getActiveID(), "deleted_date" => time()))->update()) {
                                                    $error = true;
                                                }
                                            }
                                        }

                                        if ($error) {
                                            add_error($translate->_("An error occurred while attempting to save assessment as a draft. Please try again later"));
                                        }
                                    }
                                } else {
                                    add_error($translate->_("An error has occurred while attempting to save this assessment plan, please try again later."));
                                    application_log("error", "Unable to save assessment plan for EPA objective_id = '{$objective_id}', method = '$method'");
                                }
                            } else {
                                $PROCESSED["published"] = 0;
                            }

                            if (!$ERROR) {
                                $url = ENTRADA_URL . "/admin/courses/cbme/plans?section=container&id=" . $course_id . "&assessment_plan_container_id=" . $assessment_plan_container_id;
                                add_success(sprintf($translate->_("You have successfully saved  the assessment plan requirements for this EPA.<br /><br />You will now be redirected to the assessment plan section; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\"><strong>click here</strong></a> to continue."), $url));
                                $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                                echo display_success();
                            } else {
                                $STEP = 1;
                            }
                        }
                        break;
                }

                switch ($STEP) {
                    case 1 :
                        echo display_error();

                        $objective_model = new Models_Objective();
                        $objective_array = array();
                        $objective = $objective_model->fetchRow($objective_id);
                        if ($objective) {
                            $objective_array = $objective->toArray();
                        }

                        $plan_view = new Views_Course_Cbme_Plans_Plan();
                        $plan_view->render(array(
                            "course_id" => $course_id,
                            "objective_id" => $objective_id,
                            "objective" => $objective_array,
                            "assessment_tools" => $assessment_plan_api->getAssessmentPlanTools($objective_tree_id, $course_id),
                            "cbme_objective_tree_id" => $objective_tree_id,
                            "form_data" => $PROCESSED,
                            "assessment_plan_container_id" => $assessment_plan_container_id
                        ));

                        /**
                         * Instantiate and render the assessment tool template
                         */
                        $assessment_tool_template = new Views_Course_Cbme_Plans_Templates_AssessmentTool();
                        $assessment_tool_template->render();

                        /**
                         * Instantiate and render the contextual variable template
                         */
                        $contextual_variable_template = new Views_Course_Cbme_Plans_Templates_ContextualVariable();
                        $contextual_variable_template->render();

                        /**
                         * Instantiate and render the contextual variable response template
                         */
                        $contextual_variable_response_template = new Views_Course_Cbme_Plans_Templates_ContextualVariableResponse();
                        $contextual_variable_response_template->render();

                        /**
                         * Instantiate and render the rating scale response template
                         */
                        $rating_scale_response_option_view = new Views_Course_Cbme_Plans_Templates_RatingScaleOption();
                        $rating_scale_response_option_view->render();

                        /**
                         * Render the delete modal
                         */
                        $delete_view = new Views_Course_Cbme_Plans_Modals_Delete();
                        $delete_view->render(array(
                            "action_url" => ENTRADA_URL . "/admin/courses/cbme/plans?section=plan&id=" . $course_id . "&objective_id=" . $objective_id . "&cbme_objective_tree_id=" . $objective_tree_id . "&step=2&assessment_plan_container_id=" . $assessment_plan_container_id,
                            "heading_text" => $translate->_("Delete Plan Requirements"),
                            "delete_confirmation_text" => sprintf($translate->_("Please confirm that you would like to delete the requirements for %s"), html_encode($objective_array["objective_code"]))
                        ));
                        break;
                }
            } else {
                add_error($translate->_("You do not have the required permissions to edit this course resource."));
                echo display_error();
            }
        } else {
            add_error($translate->_("You do not have the required permissions to edit this course resource."));
            echo display_error();
        }
    } else {
        echo display_error();
    }
}