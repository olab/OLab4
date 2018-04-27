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
 * A controller for assessment plans
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
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], array("trim", "int"))) {
        $course_id = $tmp_input;
    } else {
        add_error($translate->_("In order to create an assessment plan a valid course identifier must be supplied."));
    }

    if (isset($_GET["assessment_plan_container_id"]) && $tmp_input = clean_input($_GET["assessment_plan_container_id"], array("trim", "int"))) {
        $assessment_plan_container_id = $tmp_input;
    } else {
        $assessment_plan_container_id = null;
    }

    if (!$ERROR) {
        $course = Models_Course::get($COURSE_ID);
        if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
            /**
             * Instantiate the Assessment Plan API
             */
            $api_options = array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "course_id" => $course_id,
                "limit_dataset" => array("curriculum_periods", "assessment_plan_container", "course_epa_objectives", "assessment_plan_containers", "assessment_plan_cperiods"),
                "assessment_plan_container_id" => $assessment_plan_container_id
            );

            /**
             * Set the curriculum period data into the $api_options array
             */
            $plan_container_model = new Models_Assessments_PlanContainer();
            $plan = $plan_container_model->fetchRowByID($assessment_plan_container_id);
            if ($plan) {
                $PROCESSED = $plan->toArray();

                $cperiod_model = new Models_Curriculum_Period();
                $cperiod = $cperiod_model->fetchRowByID($PROCESSED["cperiod_id"]);
                if ($cperiod) {
                    $api_options["cperiod_id"] = $cperiod->getID();
                    $api_options["start_date"] = $cperiod->getStartDate();
                    $api_options["finish_date"] = $cperiod->getFinishDate();
                }
            }

            $assessment_plan_api = new Entrada_CBME_AssessmentPlan($api_options);

            if ($assessment_plan_container_id) {
                if (!$assessment_plan_api->assessmentPlanBelongsToCourse($assessment_plan_container_id, $course_id)) {
                    add_error($translate->_("You do not have the required permissions to edit this course resource."));
                }
            }

            if (!$ERROR) {
                switch ($STEP) {
                    case 2 :
                        $PROCESSED = array();
                        if (isset($_POST["title"]) && $tmp_input = clean_input($_POST["title"], array("trim", "striptags"))) {
                            $PROCESSED["title"] = $tmp_input;
                        } else {
                            add_error($translate->_("In order to create an assessment plan a <strong>Title</strong> must be supplied."));
                        }

                        if (isset($_POST["cperiod_id"]) && $tmp_input = clean_input($_POST["cperiod_id"], array("trim", "int"))) {
                            $PROCESSED["cperiod_id"] = $tmp_input;
                        } else {
                            if ($plan) {
                                $PROCESSED["cperiod_id"] = $plan->getCperiodID();
                            } else {
                                add_error($translate->_("In order to create an assessment plan a <strong>Curriculum Period</strong> must be supplied."));
                            }
                        }

                        if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                            $PROCESSED["description"] = $tmp_input;
                        } else {
                            $PROCESSED["description"] = null;
                        }

                        if (!$ERROR) {
                            /**
                             * Instantiate the Assessment Plan API
                             */
                            if (!$assessment_plan_container_id = $assessment_plan_api->saveAssessmentPlanContainer($PROCESSED["cperiod_id"], $PROCESSED["title"], $PROCESSED["description"], $course_id, $ENTRADA_USER->getActiveID())) {
                                $errors = $assessment_plan_api->getErrors();
                                if ($errors) {
                                    foreach ($errors as $error) {
                                        add_error($error);
                                    }
                                }
                            }
                        }

                        if (!$ERROR) {
                            $url = ENTRADA_URL . "/admin/courses/cbme/plans?section=container&id=" . $course_id . "&assessment_plan_container_id=" . $assessment_plan_container_id;
                            add_success(sprintf($translate->_("You have successfully saved  this <strong>Assessment Plan</strong>.<br /><br />You will now be redirected to the assessment plan section; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\"><strong>click here</strong></a> to continue."), $url));
                            $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                            echo display_success();
                        } else {
                            $STEP = 1;
                        }
                        break;
                }

                switch ($STEP) {
                    case 1 :
                        /**
                         *
                         */
                        echo display_error();

                        /**
                         * Instantiate the view for adding assessment plan containers
                         */
                        $add_assessment_plan_view = new Views_Course_Cbme_Plans_Container();


                        /**
                         * Render the add assessment plan view
                         */
                        $add_assessment_plan_view->render(array(
                            "course_id" => $course_id,
                            "curriculum_periods" => $assessment_plan_api->getCurriculumPeriods(),
                            "assessment_plan_container" => $PROCESSED,
                            "assessment_plan_container_id" => $assessment_plan_container_id,
                            "assessment_plan_cperiods" => $assessment_plan_api->getAssessmentPlanCperiods()
                        ));

                        if ($assessment_plan_container_id) {
                            /**
                             * Render the Assessment Plan interface
                             */
                            $assessment_plan_view = new Views_Course_Cbme_Plans_Objectives();
                            $assessment_plan_view->render(array(
                                "course_id" => $course_id,
                                "objectives" => $assessment_plan_api->getEpas(),
                                "assessment_plan_container_id" => $assessment_plan_container_id
                            ));
                        }
                        break;
                }
            } else {
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