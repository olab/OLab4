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
 * This page is for recording previously completed assessments that are not currently in the system.
 * Assessments are submitted on behalf of the selected attending.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    $PREFERENCES = preferences_load("cbme_assessments");
    $course_id_preference = null;
    if ($PREFERENCES && is_array($PREFERENCES) && array_key_exists("course_preference", $PREFERENCES)) {
        if (is_array($PREFERENCES["course_preference"]) && array_key_exists("course_id", $PREFERENCES["course_preference"])) {
            $course_id_preference = $PREFERENCES["course_preference"]["course_id"];
        }
    }

    $render_page = true;
    $default_method_id = null;
    $assessment_method_model = new Models_Assessments_Method();

    if (array_key_exists("success", $_GET)) {
        $render_page = false; // Display this success message and quit.
        $success_message = new Views_Message_Redirect();
        $success_message->render(
            array(
                "message_type" => "success",
                "message_text" => $translate->_("Successfully created the assessment."),
                "redirection_url" => ENTRADA_URL . "/dashboard",
                "redirect_name" => $translate->_("your dashboard"),
                "add_sidebar_begone" => true,
            )
        );
    }

    if ($render_page) {

        /**
         * Fetch the actors courses
         */
        $course_utility = new Models_CBME_Course();
        $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());
        $courses = $course_utility->getActorCourses(
            "faculty",
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            null,
            $cperiods
        );

        /**
         * Instantiate the visualization API
         */
        $cbme_progress_api = new Entrada_CBME_Visualization(
            array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "datasource_type" => "progress",
                "limit_dataset" => array("epa_assessments"),
                "courses" => $courses
            )
        );

        $PROCESSED["course_id"] = null;
        if (isset($_GET["course_id"]) && $tmp_input = clean_input($_GET["course_id"], array("trim", "striptags"))) {
            $PROCESSED["course_id"] = $tmp_input;
        } else {
            $PROCESSED["course_id"] = $courses["course_id"];
        }

        /**
         * Fetch the assessment methods
         */
        $assessment_method_array = $cbme_progress_api->fetchCourseAssessmentMethods(($course_id_preference ? $course_id_preference : ($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null)), $ENTRADA_USER->getActiveGroup());

        /**
         * Instantiate the forms API
         */
        $forms_api = new Entrada_Assessments_Forms(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
        ));

        /**
         * Faculty triggering assessment on learner. We can't pre-populate anything until a learner is selected.
         */
        $can_request_preceptor_access = false;
        $user_courses = array();
        $course_epas = array();
        $epas_tagged_to_forms = array();
        $epa_advanced_search_data = array();
        $default_method_id = $assessment_method_model->fetchMethodIDByShortname("faculty_triggered_assessment"); // The attending will complete this, and no second step will be taken

        if (!has_error()) {

            /**
             * Render the assessment tool interface
             */
            $assessment_tools_view = new Views_Assessments_Tools_Tool();
            $assessment_tools_view->render(array(
                "proxy_id" => $ENTRADA_USER->getActiveId(),
                "course_epas" => $epa_advanced_search_data,
                "assessment_methods" => $assessment_method_array,
                "user_courses" => $user_courses,
                "default_course_id" => ($course_id_preference ? $course_id_preference : ($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null)),
                "module" => $MODULE,
                "mode" => "assessment-backfill",
                "assessment_method_id" => $default_method_id, // required for faculty tool view
                "course_requires_epas" => $cbme_progress_api->courseRequiresEPAs($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null),
                "can_request_preceptor_access" => $can_request_preceptor_access,
                "course_requires_date_of_encounter" => $cbme_progress_api->courseRequiresDateOfEncounter($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null),
                "preset_filters" => $cbme_progress_api->getLearnerEpaFilterPresets($translate->_("Current Stage EPAs"), $ENTRADA_USER->getActiveId(), $PROCESSED["course_id"])
            ));

            /**
             * Render the assessment tool template interface
             */
            $assessment_tool_template = new Views_Assessments_Tools_Templates_ToolCard();
            $assessment_tool_template->render();

            /**
             * Render a procedure (tool) picker modal
             */
            $assessment_tool_picker = new Views_Assessments_Modals_AssessmentToolPicker();

            // Render the default markup for a tool picker modal, empty content
            $assessment_tool_picker->render(array("mode" => "markup"));
            // Render a template version of the contents of the modal; this template will be used to fill the modal on demand
            $assessment_tool_picker->render(array("mode" => "template"));

            /**
             * Render the preceptor access request modal if the course has the cbme_request_preceptor_access course setting enabled
             */
            if ($can_request_preceptor_access) {
                $preceptor_access_modal = new Views_Assessments_Modals_PreceptorAccessRequest();
                $preceptor_access_modal->render(array("course_id" => $PROCESSED["course_id"]));
            }

            /**
             * Check if preferences need to be updated on the server at this point.
             */
            preferences_update("cbme_assessments", $PREFERENCES);
        } else {
            echo display_error();
        }
    }
}