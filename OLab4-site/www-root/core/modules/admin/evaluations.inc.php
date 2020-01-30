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
 * The default file that is loaded when /admin/evaluations is accessed.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	define("IN_EVALUATIONS", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations", "title" => $translate->_("Manage Clerkship Evaluations"));

	if (($router) && ($router->initRoute())) {
		$modules = $router->getModules();
		if (!$ENTRADA_ACL->amIAllowed("evaluation", "update", false) && count($modules) >= 2 && $modules[1] != "reports") {
			$ERROR++;
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			echo display_error();
		
			application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
		} else {
			if ($ENTRADA_ACL->amIAllowed("evaluation", "update", false)) {
				$sidebar_html  = "<ul class=\"menu\">";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/evaluations\">Manage Evaluations</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/evaluations/forms\">Manage Forms</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/evaluations/questions\">Manage Questions</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/evaluations/reports\">Evaluation Reports</a></li>\n";
				$sidebar_html .= "</ul>";
				new_sidebar_item($translate->_("Clerkship Evaluations"), $sidebar_html, "evaluation-nav", "open");
			}
			$PREFERENCES = preferences_load($MODULE);			
			$module_file = $router->getRoute();
			if ($module_file) {
				require_once($module_file);
			}
	
			/**
			 * Check if preferences need to be updated on the server at this point.
			 */
			preferences_update($MODULE, $PREFERENCES);
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}