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
 * This page renders the dashboard for a specific learner
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_ASSESSMENTS_LEARNERS_CBME")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";

    add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.animated-notices.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.animated-notices.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    $JAVASCRIPT_TRANSLATIONS[] = "var cbme_learner_progress_dashboard = {};";
    $JAVASCRIPT_TRANSLATIONS[] = "cbme_learner_progress_dashboard.in_progress = '" . addslashes($translate->_("In Progress")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "cbme_learner_progress_dashboard.completed = '" . addslashes($translate->_("Complete")) . "';";

    /**
     * Load dashboard preferences
     */
    $PREFERENCES = preferences_load("dashboard");

    /**
     * Sanitize the provided proxy_id
     */
    if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], array("trim", "int"))) {
        $PROCESSED["proxy_id"] = $tmp_input;
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
                add_error(sprintf($translate->_("Your account does not have the permissions required to view this learner's dashboard. Click <a href='%s'>here</a> to return to your dashboard"), ENTRADA_URL . "/dashboard"));
            }
        }
    } else {
        add_error(sprintf($translate->_("There was no learner found. Click <a href='%s'>here</a> to return to your dashboard"), ENTRADA_URL . "/dashboard"));
    }

    if (!$ERROR) {
        $navigation_urls = array(
            "stages" => ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "items" => ENTRADA_URL . "/assessments/learner/cbme/items?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "trends" => ENTRADA_URL . "/assessments/learner/cbme/trends?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "comments" => ENTRADA_URL . "/assessments/learner/cbme/comments?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "assessment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/assessments?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "item_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/items" . html_encode($PROCESSED["proxy_id"]),
            "comment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/comments" . html_encode($PROCESSED["proxy_id"]),
            "unread_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/unread?proxy_id=" . html_encode($PROCESSED["proxy_id"])
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
            "limit_dataset" => array("epa_assessments", "unread_assessment_count"),
            "courses" => $courses,
            "secondary_proxy_id" => $ENTRADA_USER->getActiveID()
        ));

        /**
         * Fetch EPA progress dataset
         */
        $dataset = $cbme_progress_api->fetchData();

        /**
         * Check if current user is a competencies committee member
         */
        $is_ccmember = in_array($ENTRADA_USER->getActiveID(), $cbme_progress_api->getCourseCCMembers());
        if ($is_ccmember) {
            $toggle_objective_modal = new Views_CBME_Modals_ObjectiveStatusToggle();
            $toggle_objective_modal->render(array("proxy_id" => $PROCESSED["proxy_id"]));
        }

        /**
         * Check for epa assessment view preferences
         */
        if (isset($PREFERENCES["epa_assessments_view_preference"])) {
            $epa_assessment_view_preferences = $PREFERENCES["epa_assessments_view_preference"];
        } else {
            $epa_assessment_view_preferences = array();
        }

        /**
         * Check for epa learner preferences
         */
        if (isset($PREFERENCES["learner_preference"])) {
            $learner_preferences = $PREFERENCES["learner_preference"];
        } else {
            $learner_preferences = array();
        }

        /**
         * Instantiate CBME progress visualization view
         */
        $progress_view = new Views_CBME_Progress();
        $learner = Models_User::fetchRowByID($PROCESSED["proxy_id"]);
        $learner_name = $learner->getFullname();
        $learner_firstname = $learner->getFirstname();
        $learner_lastname = $learner->getLastname();
        $learner_number = $learner->getNumber();
        $learner_email = $learner->getEmail();

        /**
         * Render the progress view
         */
        $progress_view->render(array(
            "stage_data" => $dataset["stage_data"],
            "number_of_items_displayed" => 5,
            "epa_assessments_view_preferences" => $epa_assessment_view_preferences,
            "courses" => $cbme_progress_api->getCourses(),
            "course_id" => $cbme_progress_api->getCourseID(),
            "course_name" => $cbme_progress_api->getCourseName(),
            "admin_flag" => true,

            "learner_picker" => true,
            "proxy_id" => $PROCESSED["proxy_id"],
            "learner_name" => $learner_name,
            "learner_number" => $learner_number,
            "learner_preference" => $learner_preferences,
            "learner_firstname" => $learner_firstname,
            "learner_lastname" => $learner_lastname,
            "learner_email" => $learner_email,

            "navigation_urls" => $navigation_urls,
            "course_settings" => $cbme_progress_api->getCourseSettings(),
            "is_ccmember" => $is_ccmember,
            "hide_trigger_assessment" => true,
            "hide_meetings_log" => false,
            "unread_assessment_count" => $dataset["unread_assessment_count"]

        ));

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update("dashboard", $PREFERENCES);
    } else {
        echo display_error();
    }
}