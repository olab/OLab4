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
    $course = Models_Course::get($COURSE_ID);
    if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
        courses_subnavigation($course->toArray(), "cbme");

        /**
         * Fetch the curriculum tags that will be passed to the view
         */
        $curriculum_tag_set_model = new Models_ObjectiveSet();
        $curriculum_tag_sets = $curriculum_tag_set_model->fetchAllByStandardOrganisationID(0, $ENTRADA_USER->getActiveOrganisation());

        /**
         * Instantiate the view for the contextual variable response import interface
         */
        $import_tag_view = new Views_Course_Cbme_ImportContextualVariableResponses_Page();

        /**
         * Render the contextual variable response import interface
         */
        $import_tag_view->render(array("entrada_url" => ENTRADA_URL, "course_id" => $COURSE_ID, "module" => $MODULE));
    } else {
        add_error($translate->_("You do not have the required permissions to edit this course resource."));

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifer when attempting to edit a course.");
    }
}