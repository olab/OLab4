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
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if(!defined("COMMUNITY_ORGANISATION_WHERE_SQL")) {
	if(isset($COMMUNITY_ORGANISATIONS) && is_array($COMMUNITY_ORGANISATIONS) && count($COMMUNITY_ORGANISATIONS)) {
		define("COMMUNITY_ORGANISATION_WHERE_SQL", $db->qstr(AUTH_APP_ID));
	}
}
define("IN_COMMUNITIES", true);

	
$COMMUNITY_ID = 0;

/**
 * Check for a community category to proceed.
 */
if((isset($_GET["community"])) && ((int) trim($_GET["community"]))) {
	$COMMUNITY_ID	= (int) trim($_GET["community"]);
} elseif((isset($_POST["community_id"])) && ((int) trim($_POST["community_id"]))) {
	$COMMUNITY_ID	= (int) trim($_POST["community_id"]);
}

if($COMMUNITY_ID){

	$query = "	SELECT `community_title`,`community_url` FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
	$result = $db->GetRow($query);
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/community".$result["community_url"], "title" => $result["community_title"]);
	$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities/reports?community=".$COMMUNITY_ID, "title" => "Reports");

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);


		$GROUP_TARGETS = array();

		$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
		if (isset($active_cohorts) && !empty($active_cohorts)) {
			foreach ($active_cohorts as $cohort) {
				$GROUP_TARGETS["student_".$cohort["group_id"]] = "Students, ".$cohort["group_name"];
			}
		}

		$GROUP_TARGETS["alumni"] = "Student Alumni";
		$GROUP_TARGETS["faculty"] = "Faculty Members";
		$GROUP_TARGETS["resident"] = "Student Residents";
		$GROUP_TARGETS["staff"] = "Staff Members";

		asort($GROUP_TARGETS);

		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/communities.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
		$HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
		$HEAD[] = "<link href=\"".ENTRADA_URL."/css/communities.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";


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
} else {
	application_log("error", "User tried to access a community without providing a community_id.");

	header("Location: ".ENTRADA_URL."/communities");
	exit;
}