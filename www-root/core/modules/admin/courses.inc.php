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
 * Primary controller file for the Courses module.
 * /admin/courses
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_COURSES", true);
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
    $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\" />\n";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE, "title" => $translate->_($MODULE));

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$COURSE_ID = $tmp_input;
		} else {
			$COURSE_ID = 0;
		}

		if(isset($_GET["org_id"])){
			$ORGANISATION_ID = $_GET["org_id"];
		}
		else{
			$ORAGNISATION_ID = false;
		}

		/**
		 * Check for groups which have access to the administrative side of this module
		 * and add the appropriate toggle sidebar item.
		 */
		if ($ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
			switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
				case "admin" :
					$admin_wording = "Administrator View";
				break;
				case "pcoordinator" :
					$admin_wording = "Coordinator View";
				break;
				case "director" :
					$admin_wording = "Director View";
				break;
				default :
					$admin_wording = "";
				break;
			}

			$sidebar_html  = "<ul class=\"menu\">\n";
			$sidebar_html .= "	<li class=\"off\"><a href=\"".ENTRADA_URL."/courses".(($COURSE_ID) ? "?".replace_query(array("id" => $COURSE_ID, "action" => false, "section" => false)) : "")."\">Learner View</a></li>\n";
			if($admin_wording) {
				$sidebar_html .= "<li class=\"on\"><a href=\"".ENTRADA_URL."/admin/courses".(($COURSE_ID) ? "?".replace_query(array("id" => $COURSE_ID, "action" => "edit")) : "")."\">".html_encode($admin_wording)."</a></li>\n";
			}
			$sidebar_html .= "</ul>\n";

			new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
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