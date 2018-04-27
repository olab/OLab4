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
 * Primary controller file for the Objectives module.
 * /admin/objectives
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_OBJECTIVES",	true);
	Entrada_Utilities_Flashmessenger::displayMessages("Objectives");

    $URL_ROOT = "/admin";
    $module_title = "Manage Objectives";
    if ($LEARNER_VIEW || !$ENTRADA_ACL->amIAllowed('objectiveattributes', 'update', false)) {
        $URL_ROOT = "";
        $module_title = "Objectives";
    }
	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);
		$tmp_id = clean_input($_GET["id"], array("nows", "int"));

		if ( isset($_GET["id"]) && $tmp_id ) {
            $objectiveId = $tmp_id;
        } else {
            $objectiveId = 0;
        }

        $BREADCRUMB[] = array("url" => ENTRADA_URL.$URL_ROOT."/curriculum/objectives", "title" => $translate->_($module_title));
        
        if ($ENTRADA_ACL->amIAllowed("curriculum", "read", false)) {
            $admin_url = ENTRADA_URL . "/admin/curriculum/objectives".(($objectiveId) ? "?".replace_query(array("id" => $objectiveId, "action" => false, "section" => "edit", "step" => false)): "");
            $learner_url = ENTRADA_URL . "/curriculum/objectives".(($objectiveId) ? "?".replace_query(array("id" => $objectiveId, "action" => false, "section" => "edit", "step" => false)): "");
            add_display_style_sidebar($admin_url, $learner_url, ($LEARNER_VIEW ? "learner" : "admin"));
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
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");
		header("Location: ".$url);
		exit;
	}
}
