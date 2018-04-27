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

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_CBME", true);

    /*
     * Unfortunately, not all files with the CBME folder are including $_GET["id"]. They should be, always, but
     * they're not. So we have to do this for now.
     */
    if ($COURSE_ID) {
        $course = Models_Course::get($COURSE_ID);
        if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
            $BREADCRUMB[] = array("title" => $course->getCourseCode());
            echo "<h1 id=\"page-top\">" . $course->getFullCourseTitle() . "</h1>";
        } else {
            application_log("error", "The provided course_id [" . $COURSE_ID . "] was not found, and it really should have been since it was provided.");

            header("Location: " . ENTRADA_URL . "/admin/courses");
            exit;
        }
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."/cbme?".replace_query(array("section" => false, "id" => $COURSE_ID, "step" => false)), "title" => $translate->_("CBME"));

    if ($router && $router->initRoute()) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    } else {
        $url = ENTRADA_URL."/admin/".$MODULE;
        application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

        header("Location: ".$url);
        exit;
    }
    /**
     * Check if preferences need to be updated on the server at this point.
     */
    preferences_update($MODULE, $PREFERENCES);
}
