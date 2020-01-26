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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    /**
     * Load module preferences
     */
    $PREFERENCES = preferences_load("cbme_assessments");

    echo display_error();
    if((isset($_GET["proxy_id"])) && ($proxy_id = clean_input($_GET["proxy_id"], array("int", "trim")))) {
        $PROCESSED["proxy_id"] = $proxy_id;
        if ($PROCESSED["proxy_id"]) {
            /**
             * Check to see if the proxy id that was passed in is a valid learner for the current user.
             */
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
            $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null);
            $valid_learner = false;
            foreach ($learners as $learner) {
                if ($learner["proxy_id"] == $PROCESSED["proxy_id"]) {
                    $valid_learner = true;
                }
            }
            if (!$valid_learner) {
                add_error("Your account does not have the permissions required to view this learners dashboard. Click <a href='" . ENTRADA_URL ."/dashboard'>here</a> to return to your dashboard");
            }
        }
    } else {
        add_error($translate->_("There was no learner found. Click <a href='" . ENTRADA_URL ."/dashboard'>here</a> to return to your dashboard"));
    }

    if (!$ERROR) {

        $qstr = http_build_query($_GET);
        if($qstr) {
            $qstr = "?" . $qstr;
        }
        $navigation_urls = array(
            "stages" => ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed" . $qstr,
            "items" => ENTRADA_URL . "/assessments/learner/cbme/items" . $qstr,
            "trends" => ENTRADA_URL . "/assessments/learner/cbme/trends" . $qstr,
            "comments" => ENTRADA_URL . "/assessments/learner/cbme/comments" . $qstr,
            "assessment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/assessments" . $qstr,
            "item_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/items" . $qstr,
            "comment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/comments" . $qstr,
            "completed_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed". $qstr,
            "inprogress_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/inprogress" . $qstr,
            "pending_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/pending". $qstr,
            "deleted_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/deleted". $qstr,
            "unread_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/unread". $qstr
        );

        $course_utility = new Models_CBME_Course();
        $courses = $course_utility->getActorCourses(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            $PROCESSED["proxy_id"]
        );

        /**
         * Instantiate the CBME visualization abstraction layer
         */
        $cbme_progress_api = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $PROCESSED["proxy_id"],
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress",
            "filters" => $_GET,
            "query_limit" => 12,
            "limit_dataset" => array("assessments", "total_assessment_count", "unread_assessment_count", "filtered_assessment_count", "course_epas", "roles", "course_milestones", "course_assessment_tools", "rating_scales", "filter_list_data", "course_stages"),
            "courses" => $courses,
            "secondary_proxy_id" => $ENTRADA_USER->getActiveId()
        ));

        /**
         * Fetch the dataset that will be used by the view
         */
        $dataset = $cbme_progress_api->fetchData();

        /**
         * Iterate the course stages and build an array that will be used as the filters for milestone advancedSearch widget
         */
        $course_stage_filters = array();
        if ($dataset["course_stages"]) {
            foreach ($dataset["course_stages"] as $stage) {
                $course_stage_filters["objective_" . $stage["objective_id"]] = array(
                    "label" => $stage["objective_name"],
                    "data_source" => "get-stage-milestones",
                    "selector_control_name" => "cbme_objective_tree_id_" . $stage["cbme_objective_tree_id"],
                    "search_mode" => false,
                    "api_params" => array(
                        "cbme_objective_tree_id" => $stage["cbme_objective_tree_id"],
                        "course_id" => $cbme_progress_api->getCourseID(),
                    ),
                );
            }
        }

        /**
         * Check for epa learner preferences
         */
        if (isset($PREFERENCES["learner_preference"])) {
            $learner_preferences = $PREFERENCES["learner_preference"];
        } else {
            $learner_preferences = array();
        }

        $learner = Models_User::fetchRowByID($PROCESSED["proxy_id"]);
        if ($learner) {
            $learner_name = $learner->getFullname();
            $learner_firstname = $learner->getFirstname();
            $learner_lastname = $learner->getLastname();
            $learner_number = $learner->getNumber();
            $learner_email = $learner->getEmail();

            /**
             * Instantiate and render the CBME assessments view
             */
            $cbme_assessments_view = new Views_CBME_Assessments();
            $cbme_assessments_view->render(array(
                "assessments" => $dataset["assessments"],
                "total_count" => $dataset["total_assessment_count"],
                "filtered_count" => $dataset["filtered_assessment_count"],
                "advanced_search_epas" => $cbme_progress_api->getAdvancedSearchEPAs(),
                "advanced_search_roles" => $cbme_progress_api->getAdvancedSearchRoles(),
                "advanced_search_milestones" => $cbme_progress_api->getAdvancedSearchMilestones(),
                "filters" => $cbme_progress_api->getFilters(),
                "filter_list_data" => $dataset["filter_list_data"],
                "course_assessment_tools" => $dataset["course_assessment_tools"],
                "rating_scales" => $dataset["rating_scales"],
                "preferences" => $PREFERENCES,
                "course_id" => $cbme_progress_api->getCourseID(),
                "course_name" => $cbme_progress_api->getCourseName(),
                "courses" => $cbme_progress_api->getCourses(),
                "course_stage_filters" => $course_stage_filters,
                "query_limit" => $cbme_progress_api->getQueryLimit(),
                "form_action_url" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
                "form_reset_url" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
                "navigation_urls" => $navigation_urls,
                "learner_picker" => true,
                "proxy_id" => $PROCESSED["proxy_id"],
                "learner_name" => $learner_name,
                "learner_number" => $learner_number,
                "learner_preference" => $learner_preferences,
                "learner_firstname" => $learner_firstname,
                "learner_lastname" => $learner_lastname,
                "learner_email" => $learner_email,
                "pinned_view" => 0,
                "rotation_schedule" => $cbme_progress_api->getRotationScheduleAdvancedSearch(),
                "card_type" => "completed",
                "active_tab" => "completed_assessments",
                "triggered_by" => $cbme_progress_api->getTriggeredByFilter(),
                "unread_assessment_count" => $dataset["unread_assessment_count"],
                "secondary_proxy_id" => $ENTRADA_USER->getActiveId(),
                "is_admin_view" => 1
            ));
        }
    } else {
        echo display_error();
    }
    /**
     * Check if preferences need to be updated on the server at this point.
     */
    preferences_update("cbme_assessments", $PREFERENCES);
}