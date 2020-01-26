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
 * This API file returns a reponse code of 0 or 1 depeneding whether or
 * not the apartment(s) are available during the selected date.
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
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	/**
	 * Clears all open buffers so we can return a plain response for the Javascript.
	 */
	ob_clear_open_buffers();

	if ((isset($_GET["apartment_id"])) && ($tmp_input = clean_input($_GET["apartment_id"], array("nows", "int")))) {
		$apartment_id = $tmp_input;
	} elseif ((isset($_POST["apartment_id"])) && ($tmp_input = clean_input($_POST["apartment_id"], array("nows", "int")))) {
		$apartment_id = $tmp_input;
	} else {
		$apartment_id = 0;
	}

	if ((isset($_GET["start_date"])) && ($tmp_input = clean_input($_GET["start_date"], array("nows", "notags")))) {
		$start_date = strtotime($tmp_input." 00:00:00");
	} elseif ((isset($_POST["start_date"])) && ($tmp_input = clean_input($_POST["start_date"], array("nows", "notags")))) {
		$start_date = strtotime($tmp_input." 00:00:00");
	} else {
		$start_date = 0;
	}

	if ((isset($_GET["finish_date"])) && ($tmp_input = clean_input($_GET["finish_date"], array("nows", "notags")))) {
		$finish_date = strtotime($tmp_input." 23:59:59");
	} elseif ((isset($_POST["finish_date"])) && ($tmp_input = clean_input($_POST["finish_date"], array("nows", "notags")))) {
		$finish_date = strtotime($tmp_input." 23:59:59");
	} else {
		$finish_date = 0;
	}

	if ($apartment_id && $start_date && $finish_date) {
		$availability = regionaled_apartment_availability($apartment_id, $start_date, $finish_date);
		if ($availability["openings"] > 0) {
			echo 1;
		} else {
			echo 0;
		}
	} else {
		echo 0;
	}
	exit;
}