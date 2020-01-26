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
 * This file is used when a learner is being assigned to an available apartment.
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
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_REGIONALED_VIEW", true);

	if (($router) && ($router->initRoute())) {

		$ASCHEDULE_ID = 0;

		if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], "int"))) {
			$ASCHEDULE_ID = $tmp_input;
		}

		if ($ASCHEDULE_ID) {
	 		$query = "	SELECT a.*, b.*, c.`region_name`, d.`province`, e.`country`, g.`department_id`, g.`department_title`
						FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS b
						ON b.`apartment_id` = a.`apartment_id`
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
						ON c.`region_id` = a.`region_id`
						LEFT JOIN `global_lu_provinces` AS d
						ON d.`province_id` = a.`province_id`
						LEFT JOIN `global_lu_countries` AS e
						ON e.`countries_id` = a.`countries_id`
						LEFT JOIN `".AUTH_DATABASE."`.`departments` AS g
						ON a.`department_id` = g.`department_id`
						WHERE b.`aschedule_id` = ".$db->qstr($ASCHEDULE_ID)."
						AND b.`aschedule_status` = 'published'";
			$APARTMENT_INFO = $db->GetRow($query);
			if ($APARTMENT_INFO) {
				/**
				 * Check the owner of this event to ensure it's the correct proxy_id.
				 */
				if ($APARTMENT_INFO["proxy_id"] == $ENTRADA_USER->getID()) {
					if ((int) $APARTMENT_INFO["event_id"] && ($APARTMENT_INFO["occupant_type"] == "undergrad")) {
						$query = "	SELECT a.*, b.`etype_id` AS `proxy_id`, c.`rotation_title`
									FROM `".CLERKSHIP_DATABASE."`.`events` AS a
									LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
									ON b.`event_id` = a.`event_id`
									LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
									ON c.`rotation_id` = a.`rotation_id`
									WHERE a.`event_id` = ".$db->qstr($APARTMENT_INFO["event_id"]);
						$EVENT_INFO = $db->GetRow($query);
						if ($EVENT_INFO) {
							$BREADCRUMB[] = array("url" => "", "title" => limit_chars($EVENT_INFO["rotation_title"]." Rotation", 32));
						}
					} else {
						$EVENT_INFO = false;
					}

					/**
					 * All is good, load the requested view section.
					 */
					$module_file = $router->getRoute();
					if ($module_file) {
						require_once($module_file);
					}
				} else {
					application_log("error", "Proxy_id [".$ENTRADA_USER->getID()."] attempted to view aschedule_id [".$ASCHEDULE_ID."] which did not belong to them.");

					header("Location: ".ENTRADA_URL."/regionaled");
					exit;
				}
			} else {
				$NOTICE++;
				$NOTICESTR[] = "The accommodation schedule that you are attempting to view no longer exists.<br /><br />If you have further inquiries please contact the <a href=\"mailto:".$AGENT_CONTACTS["agent-regionaled"][$APARTMENT_INFO["department_id"]]["email"]."\">" . $APARTMENT_INFO["department_title"] . " Office</a> directly.";

				echo display_notice();

				application_log("notice", "Proxy_id [".$ENTRADA_USER->getID()."] attempted to view aschedule_id [".$ASCHEDULE_ID."] which does not exist in the database.");
			}
		} else {
			application_log("notice", "Someone attempted to view an apartment schedule without providing an aschedule_id.");

			header("Location: ".ENTRADA_URL."/regionaled");
			exit;
		}
	} else {
		$url = ENTRADA_URL."/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}