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
 * Primary controller file for the Exam module.
 * /admin/exam
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [" . $MODULE . "]");
} else {
    $MODULE_TEXT = $translate->_($MODULE);
    if ($ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
        $sidebar_html  = "<ul class=\"menu\">\n";
        if ($ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
            $sidebar_html .= "	<li class=\"" . (!$SUBMODULE ? "on" : "off") . "\"><a href=\"" . ENTRADA_URL . "/admin/" . $MODULE . "\">" . $translate->_("Dashboard") . "</a></li>";
        }
        if ($ENTRADA_ACL->amIAllowed("exam", "create", false)) {
            $sidebar_html .= "	<li class=\"" . ($SUBMODULE == "exams" ? "on" : "off") . "\"><a href=\"" . ENTRADA_URL . "/admin/" . $MODULE . "/exams\">" . $translate->_("Exams") . "</a></li>";
        }
        if ($ENTRADA_ACL->amIAllowed("examquestionindex", "read", false)) {
            $sidebar_html .= "	<li class=\"" . (in_array($SUBMODULE, array("questions", "groups", "import", "migrate", "migrateimages", "migrateresponses", "flagged")) ? "on" : "off") . "\"><a href=\"" . ENTRADA_URL . "/admin/" . $MODULE . "/questions\">" . $translate->_("Questions") . "</a></li>";
        }
        $sidebar_html .= "</ul>\n";

        new_sidebar_item($translate->_("Manage Exams"), $sidebar_html, "page-exam", "open", 2);
    }

    define("IN_EXAMS", true);

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE, "title" => $translate->_("Manage Exams"));
    if (($router) && ($router->initRoute())) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    }

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/exams/exams.css?release=". html_encode(APPLICATION_VERSION) ."\" />";

}