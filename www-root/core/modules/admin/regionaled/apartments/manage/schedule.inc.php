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
 * This file is used as a controller for the Regional Education module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_MANAGE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$ASCHEDULE_ID = 0;

	if (isset($_GET["sid"]) && ($tmp_input = clean_input($_GET["sid"], array("trim", "int")))) {
		$ASCHEDULE_ID = $tmp_input;
	}

	if ($ASCHEDULE_ID) {
		$query = "	SELECT a.*, b.`number`, b.`prefix`, b.`firstname`, b.`lastname`, b.`email`, b.`gender`, b.`privacy_level`, IF(c.`group` = 'student', 'Clerk', 'Resident') AS `learner_type`
					FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON b.`id` = a.`proxy_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON c.`user_id` = b.`id`
					AND c.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					WHERE a.`aschedule_id` = ".$db->qstr($ASCHEDULE_ID)."
					AND a.`apartment_id` = ".$db->qstr($APARTMENT_ID);
		$ASCHEDULE_INFO = $db->GetRow($query);
		if ($ASCHEDULE_INFO) {
			/**
			 * Fetch the event information associated with this apartment schedule.
			 */
			if ((int) $ASCHEDULE_INFO["event_id"]) {
				$query = "	SELECT a.*, b.`etype_id` AS `proxy_id`, c.`rotation_title`
							FROM `".CLERKSHIP_DATABASE."`.`events` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
							ON b.`event_id` = a.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
							ON c.`rotation_id` = a.`rotation_id`
							WHERE a.`event_id` = ".$db->qstr($ASCHEDULE_INFO["event_id"]);
				$EVENT_INFO = $db->GetRow($query);
				if (!$EVENT_INFO) {
					echo display_notice("<strong>Notice:</strong> The learning event originally associated with this accommodation has been removed.");
				}
			} else {
				$EVENT_INFO = false;
			}

			define("IN_SCHEDULE", true);

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled/apartments", "title" => "Manage Schedule");
			if (($router) && ($router->initRoute())) {

				$module_file = $router->getRoute();
				if ($module_file) {
					require_once($module_file);
				}
			} else {
				$url = ENTRADA_URL."/admin/".$MODULE;
				application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

				header("Location: ".$url);
				exit;
			}
		} else {
			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/regionaled/apartments/manage?id=".$APARTMENT_ID."\\'', 5000)";

			application_log("notice", "Someone attempted to access an apartment schedule that does not exist [".$ASCHEDULE_ID."]. Database said: ".$db->ErrorMsg());

			$ERROR++;
			$ERRORSTR[] = "The apartment schedule you are trying to access does not exist in the system.";

			echo display_error();
		}
	} else {
		application_log("notice", "Someone attempted to access an apartment schedule without providing an aschedule_id (sid) in the URL.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/apartments/manage?id=".$APARTMENT_ID);
		exit;
	}
}