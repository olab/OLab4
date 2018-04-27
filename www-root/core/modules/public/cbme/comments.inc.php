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
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
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

    $qstr = http_build_query($_GET);
    if($qstr) {
        $qstr = "?" . $qstr;
    }
    $navigation_urls = array(
        "stages" => ENTRADA_URL,
        "assessments" => ENTRADA_URL . "/cbme/assessments/completed" . $qstr,
        "items" => ENTRADA_URL . "/cbme/items" . $qstr,
        "trends" => ENTRADA_URL . "/cbme/trends" . $qstr,
        "comments" => ENTRADA_URL . "/cbme/comments" . $qstr,
        "assessment_pins" => ENTRADA_URL . "/cbme/pins/assessments". $qstr,
        "item_pins" => ENTRADA_URL . "/cbme/pins/items". $qstr,
        "comment_pins" => ENTRADA_URL . "/cbme/pins/comments". $qstr,
        "unread_assessments" => ENTRADA_URL . "/cbme/assessments/unread"
    );

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
     * Instantiate the CBME visualization abstraction layer
     */
    $cbme_progress_api = new Entrada_CBME_Visualization(array(
        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
        "datasource_type" => "progress",
        "filters" => $_GET,
        "query_limit" => 12,
        "limit_dataset" => array("assessment_comments", "total_assessment_comment_count", "filtered_assessment_comment_count", "course_epas", "roles", "course_milestones", "course_assessment_tools", "rating_scales", "filter_list_data", "course_stages", "unread_assessment_count"),
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
     * Instantiate and render the CBME comments view
     */
    $cbme_assessment_comments_view = new Views_CBME_Comments();
    $cbme_assessment_comments_view->render(array(
        "assessments" => $dataset["assessment_comments"],
        "total_count" => $dataset["total_assessment_comment_count"],
        "filtered_count" => $dataset["filtered_assessment_comment_count"],
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
        "form_action_url" => ENTRADA_URL . "/cbme/comments",
        "form_reset_url" => ENTRADA_URL . "/cbme/comments",
        "navigation_urls" => $navigation_urls,
        "learner_picker" => false,
        "proxy_id" => $ENTRADA_USER->getActiveId(),
        "pinned_view" => 0,
        "rotation_schedule" => $cbme_progress_api->getRotationScheduleAdvancedSearch(),
        "unread_assessment_count" => $dataset["unread_assessment_count"],
        "triggered_by" => $cbme_progress_api->getTriggeredByFilter()
    ));

    /**
     * Check if preferences need to be updated on the server at this point.
     */
    preferences_update("cbme_assessments", $PREFERENCES);
}