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
 * Used in observerships.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
require_once 'core/library/Classes/mspr/Observership.class.php';
if((!defined("PARENT_INCLUDED")) || (!defined("IN_OBSERVERSHIPS_ADMIN"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("observerships", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	if (isset($_GET["id"]) && $tmp = clean_input($_GET["id"],array("int"))) {
		$OBSERVERSHIP_ID = $tmp;
	} else {
		echo display_error("Invalid observership ID provided. Returning to your Observerships index.");
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/observerships\\'', 5000)";
		return;
	}
	$OBSERVERSHIP = Observership::get($OBSERVERSHIP_ID);
	$student_id = $OBSERVERSHIP->getStudentID();
	if (!$OBSERVERSHIP) {
		echo display_error("Invalid observership ID provided. Returning to your Observerships index.");
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/observerships\\'', 5000)";
		return;
	}
    
	$student = User::fetchRowByID($OBSERVERSHIP->getStudentID());

	$BREADCRUMB = array();
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users", "title" => "Manage Users");
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage?id=".$student->getID(), "title" => $student->getFullname(false));
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student->getID(), "title" => "Observerships");
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/observerships?section=edit&id=".$OBSERVERSHIP->getID(), "title" => "Edit Observership");

	switch($STEP){
		case 2:

		$_POST["start"] = strtotime($_POST["observership_start_date"]);
		$_POST["end"] = strtotime($_POST["observership_finish_date"]);

		$OBSERVERSHIP->mapArray($_POST,"update");

		if (!$OBSERVERSHIP->isValid()){
			add_error("Invalid data entered. Please confirm everything and try again.");
		} else {
			if ($OBSERVERSHIP->update($OBSERVERSHIP_ID)) {
				$url = ENTRADA_URL."/admin/observerships";
				echo display_success("Successfully updated Observership. You will be redirected to your Observership index in <strong>5 seconds</strong> or <a href=\"".$url."\">click here</a> to go there now.");
				$ONLOAD[]	= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				return;
			} else {
				add_error("<strong>Error occurred updating Observership</strong>. Please confirm everything and try again.");
			}
		}

		break;
		case 1:
		default:
			break;
	}


	define('ADMIN_OBSERVERSHIP_FORM',true);

	$ACTION = "Update";

	require_once 'form.inc.php';

}