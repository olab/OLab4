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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('unitcontent', 'read', false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[] = "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_COURSE_UNITS", true);

    unset($PROCESSED);

    $PREFERENCES = preferences_load($MODULE);

    /**
     * Unit ID
     */
    if ((isset($_GET["cunit_id"])) && ($tmp_input = clean_input($_GET["cunit_id"], array("nows", "int")))) {
        $CUNIT_ID = $tmp_input;
    } else {
        $CUNIT_ID = 0;
    }

    if ($COURSE_ID) {
        $COURSE = Models_Course::get($COURSE_ID);
        courses_subnavigation($COURSE->toArray(), "units");
        $BREADCRUMB[] = array("url" => "", "title" => $COURSE->getCourseCode());

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/courses/units?id=".$COURSE_ID, "title" => $translate->_("Course Units"));
    }

    if (($router) && ($router->initRoute())) {
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
/* vim: set expandtab: */
