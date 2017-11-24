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
 * Secondary controller file used by the forms module within the evaluations module.
 * /admin/evaluations/forms
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_CLERKSHIP")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("logbook", "read")) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$USER_ID = (isset($_GET["id"]) && (int) $_GET["id"] ? $_GET["id"] : 0);
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/clerkship/logbook".($USER_ID ? "?id=".$USER_ID : ""), "title" => "Logbook");

	if ($USER_ID && $ENTRADA_USER->getActiveGroup() != "student") {
		$sidebar_html = "";
		$query = "SELECT a.*, b.`rotation_title` FROM `".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans` AS a
					JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS b
					ON a.`rotation_id` = b.`rotation_id`
					WHERE a.`proxy_id` = ".$db->qstr($USER_ID);
		$deficiency_plans = $db->GetAll($query);
		if ($deficiency_plans) {
			$plans_accepted = false;
			$plans_pending = false;
			$plans_rejected = false;
			
			foreach ($deficiency_plans as $plan) {
				if ($plan["clerk_accepted"] && $plan["administrator_accepted"]) {
					$plans_accepted = true;
				} elseif ($plan["clerk_accepted"]) {
					$plans_pending = true;
				} elseif ($plan["administrator_comments"]) {
					$plans_rejected = true;
				}
			}
			if ($plans_accepted) {
				$sidebar_html .= "Accepted Plans:\n";
				$sidebar_html .= "<ul class=\"menu\">";
				foreach ($deficiency_plans as $plan) {
					if ($plan["clerk_accepted"] && $plan["administrator_accepted"]) {
						$sidebar_html .= "	<li class=\"checkmark\"><a href=\"".ENTRADA_URL."/clerkship/logbook?section=deficiency-plan&rotation=".$plan["rotation_id"]."&id=".$USER_ID."\"><strong>".$plan["rotation_title"]."</strong></a></li>\n";
					}
				}
				$sidebar_html .= "</ul>";
			}
			if ($plans_pending) {
				$sidebar_html .= "Plans Pending Approval:\n";
				$sidebar_html .= "<ul class=\"menu\">";
				foreach ($deficiency_plans as $plan) {
					if ($plan["clerk_accepted"] && !$plan["administrator_accepted"]) {
						$sidebar_html .= "	<li><a href=\"".ENTRADA_URL."/clerkship/logbook?section=deficiency-plan&rotation=".$plan["rotation_id"]."&id=".$USER_ID."\"><strong>".$plan["rotation_title"]."</strong></a></li>\n";
					}
				}
				$sidebar_html .= "</ul>";
			}
			if ($plans_rejected) {
				$sidebar_html .= "Rejected Plans:\n";
				$sidebar_html .= "<ul class=\"menu\">";
				foreach ($deficiency_plans as $plan) {
					if ($plan["administrator_comments"] && !$plan["clerk_accepted"]) {
						$sidebar_html .= "	<li class=\"incorrect\"><a href=\"".ENTRADA_URL."/clerkship/logbook?section=deficiency-plan&rotation=".$plan["rotation_id"]."&id=".$USER_ID."\"><strong>".$plan["rotation_title"]."</strong></a></li>\n";
					}
				}
				$sidebar_html .= "</ul>";
			}
			new_sidebar_item("Deficiency Plans", $sidebar_html, "page-clerkship", "open");
		}
	}
    
	if (($router) && ($router->initRoute())) {
		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}
	} else {
		$url = ENTRADA_URL."/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}