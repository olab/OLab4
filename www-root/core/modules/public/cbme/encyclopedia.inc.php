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
 * This page displays extra EPA information for learners.
 * A resource they can use to get a better understanding of each EPA
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "\\'', 15000)";

    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE, "title" => "EPA Encyclopedia");

    if (isset($_GET["objective_id"]) && $tmp_input = clean_input($_GET["objective_id"], array("trim", "striptags"))) {
        $objective_code = $tmp_input;
    } else {
        $objective_code = "";
    }

    if (isset($_GET["course_id"]) && $tmp_input = clean_input($_GET["course_id"], array("trim", "striptags"))) {
        $course_id = $tmp_input;
    } else {
        $course_id = null;
    }

    $PREFERENCES = preferences_load("cbme_assessments");

    if ($course_id) {
        $course_model = new Models_Course();
        $all_courses = $course_model->getRowByID($course_id);
        $courses = array(
            "course_id"     => $all_courses["course_id"],
            "course_name"   => $all_courses["course_name"],
            "courses"       => array($all_courses["course_id"] => $all_courses)
        );
    } else {
        $course_utility = new Models_CBME_Course();
        $courses = $course_utility->getActorCourses(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId()
        );
    }
    
    /**
     * Instantiate the CBME visualization abstraction layer
     */
    $cbme_progress_api = new Entrada_CBME_Visualization(array(
        "actor_proxy_id"        => $ENTRADA_USER->getActiveId(),
        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
        "datasource_type"       => "progress",
        "limit_dataset"         => array("epa_assessments"),
        "courses"               => $courses
    ));

    $tree_object = new Entrada_CBME_ObjectiveTree(array(
        "actor_proxy_id"        => $ENTRADA_USER->getActiveId(),
        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
        "course_id"             => $cbme_progress_api->getCourseID()
    ));


    if (!$tree_object || !isset($tree_object)) {
        add_error($translate->_("There was no objective tree found"));
    }

    if (!$ERROR) {

        /**
         * Get the json representation of this course's tree
         */
        $tree_json = $tree_object->fetchEpaBranchesParentChild();

        /**
         * Check for epa assessment view preferences
         */
        if (isset($PREFERENCES["epa_assessments_view_preference"])) {
            $epa_assessment_view_preferences = $PREFERENCES["epa_assessments_view_preference"];
        } else {
            $epa_assessment_view_preferences = array();
        }

        /**
         * Instantiate CBME progress visualization view
         */
        $progress_view = new Views_CBME_Encyclopedia();

        /**
         * Render the progress view
         */
        $view_options = array(
            "stage_data" => $cbme_progress_api->fetchData(),
            "number_of_items_displayed" => 5,
            "epa_assessments_view_preferences" => $epa_assessment_view_preferences,
            "objective_id" => $objective_code,
            "tree_json" => $tree_json,
            "courses" => $cbme_progress_api->getCourses(),
            "course_id" => $cbme_progress_api->getCourseID(),
            "course_name" => $cbme_progress_api->getCourseName()
        );
        $progress_view->render($view_options);

    } else {
        echo display_error();
    }
}