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
 * This is the primary controller file for public Exam module in Entrada.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif(!$ENTRADA_ACL->amIAllowed('exam', 'read')) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_PUBLIC_EXAMS", true);

    if (($router) && ($router->initRoute())) {
        $PREFERENCES = preferences_load($MODULE);

        /**
         * Allows you to specify which record id your particular component is
         * dealing with (i.e. http:// ... /admin/events?section=edit&id=1562).
         */
        if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
            $RECORD_ID = $tmp_input;
        } elseif ((isset($_POST["id"])) && ($tmp_input = clean_input($_POST["id"], array("trim", "int")))) {
            $RECORD_ID = $tmp_input;
        } else {
            $RECORD_ID = 0;
        }

        if (isset($_REQUEST["community"]) && $_REQUEST["community"]) {
            $EXAM_TYPE = "community_page";
        } else {
            $EXAM_TYPE = "event";
        }

        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update($MODULE, $PREFERENCES);
    } else {
        $url = ENTRADA_URL."/".$MODULE;
        application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

        header("Location: ".$url);
        exit;
    }
}