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

    if (!$ERROR) {
        $course = Models_Course::get($COURSE_ID);
        if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
            /**
             * Instantiate the Assessment Plan API
             */
            $assessment_plan_api = new Entrada_CBME_AssessmentPlan(array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "course_id" => $course_id,
                "limit_dataset" => array("assessment_plan_containers", "curriculum_periods", "curriculum_period")
            ));

            switch ($STEP) {
                case 2:
                    if (isset($_POST["assessment_plan_ids"]) && is_array($_POST["assessment_plan_ids"]) && !empty($_POST["assessment_plan_ids"])) {
                        $assessment_plan_api->delete($_POST["assessment_plan_ids"]);
                        $errors = $assessment_plan_api->getErrors();
                        if ($errors) {
                            foreach ($errors as $error) {
                                add_error($error);
                            }
                        }
                    } else {
                        add_error($translate->_("No Assessment Plans were selected for removal."));
                    }

                    if (!$ERROR) {
                        $url = ENTRADA_URL . "/admin/courses/cbme/plans?id=" . $course_id;
                        add_success(sprintf($translate->_("<br />You will now be redirected to the assessment plans index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\"><strong>click here</strong></a> to continue."), $url));
                        $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                        echo display_success();
                    } else {
                        $STEP = 1;
                    }
                    break;
            }

            switch ($STEP) {
                case 1:
                    /**
                     * Display relevant errors
                     */
                    echo display_error();

                    /**
                     * Instantiate the assessment plan list view
                     */
                    $assessment_plan_list_view = new Views_Course_Cbme_Containers();

                    /**
                     * Render the assessment plan list view
                     */
                    $assessment_plan_list_view->render(array(
                        "course_id" => $course_id,
                        "assessment_plan_containers" => $assessment_plan_api->getAssessmentPlanContainers(),
                    ));

                    /**
                     * Render the delete modal
                     */
                    $delete_view = new Views_Course_Cbme_Plans_Modals_Delete();
                    $delete_view->render(array(
                        "action_url" => ENTRADA_URL . "/admin/courses/cbme/plans?id=" . $course_id . "&step=2",
                        "heading_text" => $translate->_("Delete Assessment Plans"),
                        "delete_confirmation_text" => $translate->_("Please confirm you would like to delete the selected assessment plan(s)")
                    ));
                    break;
            }
        } else {
            add_error($translate->_("You do not have the required permissions to edit this course resource."));
            echo display_error();
        }
    } else {
        echo display_error();
    }
}