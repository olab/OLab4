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

	$student_id = clean_input($_GET["id"], "numeric");
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/observerships?section=add", "title" => "Add Observership");
	switch($STEP){
		case 2:

		$observership_array = $_POST;
		$observership_array["student_id"] = $student_id;
		/*
		 * Admins adding observerships are approved automatically. 
		 */
		
		$OBSERVERSHIP = Observership::fromArray($observership_array, "add", $student_id);
		if (!$OBSERVERSHIP->isValid()){
			add_error("<strong>Invalid data entered</strong>. Please confirm everything and try again.");
		} else {
			if ($OBSERVERSHIP->create()) {
				$url = ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student_id;
				echo display_success("Successfully created Observership. You will be redirected to your Observership index in <strong>5 seconds</strong> or <a href=\"".$url."\">click here</a> to go there now.");
				$ONLOAD[]	= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				return;
			} else {
				add_error("<strong>Error occurred creating Observership</strong>. Please confirm everything and try again.");
			}
		}

		break;
		case 1:
		default:
			$OBSERVERSHIP = new Observership();	
			break;
	}


	define('ADMIN_OBSERVERSHIP_FORM',true);

	$ACTION = "Create";

	require_once 'form.inc.php';

}