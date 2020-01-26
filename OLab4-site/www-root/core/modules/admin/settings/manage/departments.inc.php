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
 * This file is part of the department management feature.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	
	function departments_nav($dep_id, $current = NULL) {
		global $ENTRADA_USER;
		
		$tabs = "";
		$tabs .= "<ul class=\"nav nav-tabs\">";
		$tabs .= "<li class=\"".($current == "edit" || $current == NULL ? "active" : "")."\"><a href=\"" . ENTRADA_URL . "/admin/settings/manage/departments" . "?section=edit&org=" . $ENTRADA_USER->getActiveOrganisation() . "&department_id=" . $dep_id . "\">Edit Department</a></li>";
		$tabs .= "<li class=\"".($current == "profile-fields" ? "active" : "")."\"><a href=\"" . ENTRADA_URL . "/admin/settings/manage/departments" . "?section=profile-fields&org=" . $ENTRADA_USER->getActiveOrganisation() . "&department_id=" . $dep_id . "\">Department Profile Fields</a></li>";
		$tabs .= "</ul>";
		
		return $tabs;
	}

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);


		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/departments?org=".$ORGANISATION['organisation_id'], "title" => "Departments");

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
