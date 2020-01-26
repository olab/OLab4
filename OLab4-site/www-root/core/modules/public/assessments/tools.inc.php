<?php
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('dashboard', 'read')) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    /**
     * Javascript Translations
     */
    Entrada_Utilities::addJavascriptTranslation("N/A", "not_available", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("No information found", "no_information", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("User Information", "user_information", "cbme_translations");

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

        $course_utility = new Models_CBME_Course();
        $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());
        $courses = $course_utility->getActorCourses(
            "student",
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
                "actor_proxy_id"            => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id"     => $ENTRADA_USER->getActiveOrganisation(),
                "datasource_type"           => "progress",
                "limit_dataset"             => array("epa_assessments"),
                "courses"  => $courses
            )
        );

        $PROCESSED["course_id"] = null;
        if (isset($_GET["course_id"]) && $tmp_input = clean_input($_GET["course_id"], array("trim", "striptags"))) {
            $PROCESSED["course_id"] = $tmp_input;
        }

        $assessment_method_array = $cbme_progress_api->fetchCourseAssessmentMethods(($course_id_preference ? $course_id_preference :  ($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null)), $ENTRADA_USER->getActiveGroup());

        $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
        $objective_model = new Models_Objective();

        if ($ENTRADA_USER->getActiveGroup() == "faculty") {

            /**
             * Faculty triggering assessment on learner. We can't prepopulate anything until a learner is selected.
             */
            $can_request_preceptor_access = false;
            $user_courses = array();
            $course_epas = array();
            $epas_tagged_to_forms = array();
            $epa_advanced_search_data = array();
            $default_method_id = $assessment_method_model->fetchMethodIDByShortname("faculty_triggered_assessment"); // The attending will complete this, and no second step will be taken

        } else {

            /**
             * Learner triggering assessment.
             *
             * Fetch the current learner's courses for this cperiod.
             */
            $course_model = new Models_Course();
            $user_courses = array();

            if (empty($PROCESSED["course_id"])) {
                $PROCESSED["course_id"] = $cbme_progress_api->getCourseID();
            }

            $can_request_preceptor_access = $cbme_progress_api->courseCanRequestPreceptorAccess($PROCESSED["course_id"]);

            $user_courses = $course_model->getCurrentUserCourseList(true);
            if ($user_courses) {
                foreach ($user_courses as &$course) {
                    $has_tool_objectives = $cbme_progress_api->courseHasAssessmentToolObjectives($course["course_id"]);
                    $course["has_tool_objectives"] = $has_tool_objectives;
                }
            }

            if (!has_error()) {

                /**
                 * When there is only one course, fetch course specific EPAs and then populate an array for the EPA instance of advancedSearch.
                 */
                if (count($user_courses) == 1) {
                    // Get the specific course info.
                    $the_only_course = end($user_courses);
                    $course_epas = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("epa", $the_only_course["course_id"]);
                    $epas_tagged_to_forms = $forms_api->fetchEPANodesTaggedToForms($the_only_course["course_id"]);
                    if (empty($epas_tagged_to_forms)) {
                        if ($cbme_progress_api->courseRequiresEPAs($the_only_course["course_id"])) {
                            add_error($translate->_("No EPAs have been defined."));
                        } else {
                            $epa_advanced_search_data = array();
                        }
                    } else {
                        $epa_advanced_search_data = array();
                        foreach ($epas_tagged_to_forms as $epa) {
                            $epa_advanced_search_data[] = array(
                                "target_id" => $epa["cbme_objective_tree_id"],
                                "target_label" => $epa["objective_code"] . ": " . substr($epa["objective_name"], 0, 65) . "...",
                                "target_title" => $epa["objective_code"] . " " . $epa["objective_name"]
                            );
                        }
                    }

                } else {

                    // More than one course, leave it blank until they pick a relevant course.
                    $course_epas = array();
                    $epas_tagged_to_forms = array();
                    $epa_advanced_search_data = array();
                }
            }
        }

        if (!has_error()) {
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/assessment-form.css\" />";
            
            /**
             * Render the assessment tool interface
             */
            $assessment_tools_view = new Views_Assessments_Tools_Tool();
            $assessment_tools_view->render(array(
                "proxy_id" => $ENTRADA_USER->getActiveId(),
                "course_epas" => $epa_advanced_search_data,
                "assessment_methods" => $assessment_method_array,
                "user_courses" => $user_courses,
                "default_course_id" => ($course_id_preference ? $course_id_preference :  ($PROCESSED["course_id"] ? $PROCESSED["course_id"] : null)),
                "module" => $MODULE,
                "mode" => $ENTRADA_USER->getActiveGroup(),
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