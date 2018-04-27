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
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!((new Entrada_Settings)->read("cbme_enabled") && $ENTRADA_ACL->amIAllowed(new CourseContentResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), "update"))) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"%s\">%s</a> for assistance."), "mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $course = Models_Course::get($COURSE_ID);
    if ($course) {
        courses_subnavigation($course->toArray(), "cbme");

        echo "<h1 class=\"muted\">" . $translate->_("Competency-Based Medical Education") . "</h1>";

        include("cbme-setup.inc.php");

        if ($cbme_checked) :
            /**
             * Render the Course CBME subnavigation
             */
            $navigation_view = new Views_Course_Cbme_Navigation();
            $navigation_view->render(array(
                "course_id" => $COURSE_ID,
                "active_tab" => "getting_started"
            )); ?>

            <iframe src="https://docs.entrada.org/v/1.11/modules/admin-cbme/" style="width:100%; border:0; margin-top:15px; height:850px"></iframe>
        <?php endif;
    } else {
        add_error($translate->_("You do not have the required permissions to edit this course resource."));

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifer when attempting to edit a course resource.");
    }
}