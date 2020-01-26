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
 * Primary controller file for the Users module.
 * /admin/users
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_USERS", true);

	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.multiselect.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.multiselect.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users", "title" => $MODULES[strtolower($MODULE)]["title"]);

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
			$PROXY_ID = $tmp_input;
		} else {
			$PROXY_ID = 0;
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

function add_manage_user_sidebar(){
	global $ENTRADA_ACL, $PROXY_ID;
	$baseurl = ENTRADA_URL."/admin/users";
	$sidebar_html  = "<ul class=\"menu\">";
	$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/users/metadata\">Manage User Meta Data</a></li>\n";
	$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/mspr\">Manage MSPRs</a></li>\n";
	$sidebar_html .= "</ul>";

	new_sidebar_item("User Management", $sidebar_html, "user-management-nav", "open");
}
