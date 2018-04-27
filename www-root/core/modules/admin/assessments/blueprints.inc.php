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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES["assessments"]["resource"], $MODULES["assessments"]["permission"], false)) {
    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_BLUEPRINTS", true);

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/blueprints", "title" => $translate->_("Form Templates"));

    $JAVASCRIPT_TRANSLATIONS[] = "var blueprints_index = {};";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.No_Blueprint_Found = '" . addslashes($translate->_("No template found")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Milestone_Selected = '" . addslashes($translate->_("%1 %2 have been selected, this is the maximum allowed for this template type.")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Cannot_Remove_EPA = '" . addslashes($translate->_("You cannot remove this EPA.")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Cannot_Remove_Last_EPA = '" . addslashes($translate->_("You cannot remove the last selected EPA.")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Error_Posting_Data = '" . addslashes($translate->_("There was an error posting the data to the server: ")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Showing_Of_Forms = '" . addslashes($translate->_("Showing %1 of %2 total forms")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Showing_Of_Blueprints = '" . addslashes($translate->_("Showing %1 of %2 total form templates")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "blueprints_index.Max_Milestones_Allowed = '" . addslashes($translate->_("A maximum of %1 %2 is allowed for this template type.")) . "';";

    if (($router) && ($router->initRoute())) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    }
}