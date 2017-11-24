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
 * Tertiary controller file used by the manage module, within the apartments module within the regionaled module.
 * /admin/regionaled/apartments/manage
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_APARTMENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$APARTMENT_ID = 0;

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
		$APARTMENT_ID = $tmp_input;
	}

	if ($APARTMENT_ID) {
		$allowed_apartment = 0;
		$query = "	SELECT *
					FROM `".CLERKSHIP_DATABASE."`.`apartment_contacts` a
					JOIN `".AUTH_DATABASE."`.`departments` b
					ON a.`department_id` = b.`department_id`
					WHERE a.`apartment_id` = " . $db->qstr($APARTMENT_ID) . "
					AND a.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
		$result = $db->getRow($query);
		if ($result) {
			$allowed_apartment = 1;
			$department_id = $result["department_id"];
			$department_title = $result["department_title"];
		}
	} else {
		application_log("notice", "Someone attempted to manage an apartment without providing an apartment id.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
		exit;
	}
	
	if ($allowed_apartment) {
		$query = "	SELECT a.*, b.`region_name`, c.`province`, d.`country`
					FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
					ON a.`region_id` = b.`region_id`
					LEFT JOIN `global_lu_provinces` AS c
					ON c.`province_id` = b.`province_id`
					LEFT JOIN `global_lu_countries` AS d
					ON d.`countries_id` = b.`countries_id`
					WHERE a.`apartment_id` = ".$db->qstr($APARTMENT_ID);
		$APARTMENT_INFO = $db->GetRow($query);
		if ($APARTMENT_INFO) {
			define("IN_MANAGE", true);
			$APARTMENT_INFO["department_id"] = $department_id;
			$APARTMENT_INFO["department_title"] = $department_title;

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled/apartments/manage?id=".$APARTMENT_ID, "title" => limit_chars($APARTMENT_INFO["apartment_title"], 32));

			if (($router) && ($router->initRoute())) {
				/**
				 * Add the secondary Management navigation.
				 */
				$sidebar_html  = "<ul class=\"menu\">";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/regionaled/apartments/manage?id=".$APARTMENT_ID."\">Apartment Schedule</a></li>\n";
				//$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/regionaled/apartments/manage/accounts?id=".$APARTMENT_ID."\">Apartment Accounts</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/regionaled/apartments/manage/edit?id=".$APARTMENT_ID."\">Edit Apartment</a></li>\n";
				$sidebar_html .= "</ul>";

				new_sidebar_item("Apartment Options", $sidebar_html, "regionaled-apt-nav", "open");

				/**
				 * Add current apartment address to sidebar.
				 */
				$sidebar_html  = "<div style=\"font-size: 10px; padding: 6px 2px 6px 6px\">\n";
				$sidebar_html .= limit_chars((($APARTMENT_INFO["apartment_number"] != "") ? html_encode($APARTMENT_INFO["apartment_number"])."-" : "").html_encode($APARTMENT_INFO["apartment_address"]), 25, true)."<br />";
				$sidebar_html .= html_encode($APARTMENT_INFO["region_name"]).($APARTMENT_INFO["province"] ? ", ".html_encode($APARTMENT_INFO["province"]) : "")."<br />";
				$sidebar_html .= html_encode($APARTMENT_INFO["apartment_postcode"]).", ".html_encode($APARTMENT_INFO["country"])."<br /><br />";
				if ($APARTMENT_INFO["apartment_phone"]) {
					$sidebar_html .= html_encode($APARTMENT_INFO["apartment_phone"])."<br />";
				}
				if ($APARTMENT_INFO["apartment_email"]) {
					$sidebar_html .= "<a href=\"mailto:".html_encode($APARTMENT_INFO["apartment_email"])."\" style=\"font-size: 10px\">".html_encode(limit_chars($APARTMENT_INFO["apartment_email"], 30))."</a><br />";
				}
				$sidebar_html .= "	<br />\n";
				$sidebar_html .= "	<strong>Max</strong> Occupants: ".$APARTMENT_INFO["max_occupants"]."\n";

				$sidebar_html .= "	<br /><br />\n";
				$sidebar_html .= "	<strong>Current</strong> Occupants:\n";
				$sidebar_html .= "	<ul class=\"menu\">\n";
				$timestamp = fetch_timestamps("day", time());
				$query = "	SELECT a.*, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`, c.`gender`
							FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
							ON c.`id` = a.`proxy_id`
							WHERE a.`apartment_id` = ".$db->qstr($APARTMENT_INFO["apartment_id"])."
							AND (
							".$db->qstr($timestamp["start"])." BETWEEN a.`inhabiting_start` AND a.`inhabiting_finish` OR
							".$db->qstr($timestamp["end"])." BETWEEN a.`inhabiting_start` AND a.`inhabiting_finish` OR
							a.`inhabiting_start` BETWEEN ".$db->qstr($timestamp["start"])." AND ".$db->qstr($timestamp["end"])." OR
							a.`inhabiting_finish` BETWEEN ".$db->qstr($timestamp["start"])." AND ".$db->qstr($timestamp["end"])."
							)";
				$results = $db->GetAll($query);
				if ($results) {
					foreach ($results as $result) {
						$sidebar_html .= "<li class=\"".$result["occupant_type"]."\">".html_encode(limit_chars((($result["occupant_type"] == "other") ? $result["occupant_title"] : $result["fullname"]), 23))."</li>\n";
					}
				} else {
					$sidebar_html .= "<li class=\"status-offline\">Not currently occupied</li>";
				}
				$sidebar_html .= "	</ul>\n";
				$sidebar_html .= "</div>\n";

				new_sidebar_item("Apartment Information", $sidebar_html, "regionaled-apt-nav", "open");

				$APARTMENT_EXPIRED = true;
				if ((!(int) $APARTMENT_INFO["available_finish"]) || ($APARTMENT_INFO["available_finish"] > time())) {
					$APARTMENT_EXPIRED = false;
				}

				/**
				 * Show a list of other apartments in this region so it's quick to switch between them.
				 */
				$query = "	SELECT * 
							FROM `".CLERKSHIP_DATABASE."`.`apartments` a
							JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` b
							ON b.`apartment_id` = a.`apartment_id`
							WHERE a.`region_id` = ".$db->qstr($APARTMENT_INFO["region_id"]) . "
							AND b.`proxy_id` = " . $db->qstr($ENTRADA_USER->getActiveId());
				$apartments = $db->GetAll($query);
				if ($apartments) {
					echo "<div class=\"container\">\n";
					echo "	<div class=\"col-right right\">\n";
					echo "		<form id=\"changeApartment\">\n";
					echo "			<label for=\"change_apartment_id\" class=\"form-nrequired\">Other Apartments</label>\n";
					echo "			<select id=\"change_apartment_id\" style=\"width: 250px;\" onchange=\"window.location = '".ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(array("id" => false))."&id=' + \$F('change_apartment_id')\">\n";
					foreach ($apartments as $apartment) {
						echo "			<option value=\"".(int) $apartment["apartment_id"]."\"".(($apartment["apartment_id"] == $APARTMENT_INFO["apartment_id"]) ? " selected=\"selected\"" : "").">".html_encode($apartment["apartment_title"])."</option>\n";
					}
					echo "			</select>\n";
					echo "		</form>\n";
					echo "	</div>\n";
					echo "</div>\n";
				}

				echo "<h1 style=\"margin-bottom: 0px\">".($APARTMENT_EXPIRED ? "Expired: " : "").html_encode($APARTMENT_INFO["apartment_title"])."</h1>\n";

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
			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/regionaled/apartments\\'', 5000)";

			application_log("notice", "Someone attempted to manage an apartment that does not exist [".$APARTMENT_ID."]. Database said: ".$db->ErrorMsg());

			$ERROR++;
			$ERRORSTR[] = "The apartment you are trying to manage does not exist in the system.";

			echo display_error();
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You are not authorized to manage this apartment.";
		
		application_log("notice", "Someone attempted to manage an apartment without having authorization.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
		exit;
	}
}