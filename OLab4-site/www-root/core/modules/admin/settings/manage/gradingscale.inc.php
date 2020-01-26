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
 * Primary controller file for the Gradingscale module.
 * /admin/settings/manage/gradingscale
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
*/

$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID, "title" => $translate->_("Grading Scale"));

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    echo "<input type=\"hidden\" id=\"organisation_id\" value=\"".$ORGANISATION_ID."\" />\n";
    
    if (($router) && ($router->initRoute())) {
        $PREFERENCES = preferences_load($MODULE);

        /**
         * See if we are working with an existing Grading Scale
         */
        if (isset($_GET["scale"]) && ($scale = clean_input($_GET["scale"], array("notags", "trim")))) {
            $SCALE_ID = $scale;
        }
        
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update($MODULE, $PREFERENCES);
    }
}
