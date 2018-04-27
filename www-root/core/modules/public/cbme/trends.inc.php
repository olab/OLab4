<?php
/*
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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
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
        "limit_dataset" => array(
            "mapped_course_epas",
            "cbme_assessment_items",
            "total_assessment_count",
            "filtered_assessment_count",
            "assessments",
            "items",
            "rating_scales",
            "roles",
            "course_milestones",
            "filter_list_data",
            "course_assessment_tools",
            "course_assessment_tools_charts",
            "rating_scales_charts",
            "course_stages",
            "unread_assessment_count"
        ),
        "courses"  => $courses,
        "secondary_proxy_id" => $ENTRADA_USER->getActiveId(),
        "query_limit" => 35
    ));

    /**
     * Fetch the dataset that will be used by the view
     */
    $dataset = $cbme_progress_api->fetchData();

    /**
     * Iterate the course EPAs and store them in a format that the advancedSearch widget can consume
     */
    $advanced_search_epas = array();
    foreach ($dataset["course_epas"] as $epa) {
        $advanced_search_epas[] = array("target_id" => $epa["objective_id"], "target_label" => html_encode($epa["objective_code"] . ": " . substr($epa["objective_name"], 0,  37) . "..."), "target_title" => html_encode($epa["objective_code"] . " " . $epa["objective_name"]));
    }

    /**
     * Iterate the roles and store them in a format that the advancedSearch widget can consume
     */
    $advanced_search_roles = array();
    foreach ($dataset["roles"] as $role) {
        $advanced_search_roles[] = array("target_id" => $role->getID(), "target_label" => $role->getCode() . ": " . $role->getName(), "target_title" => $role->getCode() . " " . $role->getName());
    }

    /**
     * Iterate the course EPAs and store them in a format that the advancedSearch widget can consume
     */
    $advanced_search_milestones = array();
    foreach ($dataset["course_milestones"] as $milestone) {
        $advanced_search_milestones[] = array("target_id" => $milestone["objective_id"], "target_label" => html_encode($milestone["objective_code"] . ": " . substr($milestone["objective_name"], 0,  25) . "..."), "target_title" => html_encode($milestone["objective_code"] . " " . $milestone["objective_name"]));
    }

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

    // Get the rating scales charts to render
    $charts = array();
    $charts = $dataset["rating_scales_charts"];

    // Get the assessment tools charts to render
/*
    $charts["assessment_tools"] = array(
        "title" => $translate->_("Assessment Tools"),
        "charts" => $dataset["assessment_tools_charts"]
    );
*/
    $cbme_trends_view = new Views_CBME_Trends();
    $cbme_trends_view->render(array(
        "charts" => $charts,
        "assessments" => $dataset["assessments"],
        "total_count" => $dataset["total_assessment_count"],
        "filtered_count" => $dataset["filtered_assessment_count"],
        "advanced_search_epas" => $advanced_search_epas,
        "advanced_search_roles" => $advanced_search_roles,
        "advanced_search_milestones" => $advanced_search_milestones,
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
        "trends_query_limit" => $cbme_progress_api->getScaleTrendsAssessmentsLimit(),
        "form_action_url" => ENTRADA_URL . "/cbme/trends",
        "form_reset_url" => ENTRADA_URL . "/cbme/trends",
        "proxy_id" => $ENTRADA_USER->getActiveId(),
        "navigation_urls" => $navigation_urls,
        "rotation_schedule" => $cbme_progress_api->getRotationScheduleAdvancedSearch(),
        "unread_assessment_count" => $dataset["unread_assessment_count"],
        "secondary_proxy_id" => $ENTRADA_USER->getActiveId(),
        "triggered_by" => $cbme_progress_api->getTriggeredByFilter()
    ));

    /**
     * Check if preferences need to be updated on the server at this point.
     */
    preferences_update("cbme_assessments", $PREFERENCES);
}

