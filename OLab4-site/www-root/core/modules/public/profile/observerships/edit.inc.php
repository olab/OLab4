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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
require_once 'core/library/Classes/mspr/Observership.class.php';
if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if (isset($_GET["id"]) && $tmp = clean_input($_GET["id"],array("int"))) {
	$OBSERVERSHIP_ID = $tmp;
} else {
	echo display_error("Invalid observership ID provided. Returning to your Observerships index.");
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
	return;
}

$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=edit&id=".$OBSERVERSHIP_ID, "title" => "Update Observership");

$OBSERVERSHIP = Observership::get($OBSERVERSHIP_ID);

if (!$OBSERVERSHIP) {
	echo display_error("Invalid observership ID provided. Returning to your Observerships index.");
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
	return;
}

if ($OBSERVERSHIP->getStudentId() !== $ENTRADA_USER->getActiveId() || !in_array(strtolower($OBSERVERSHIP->getStatus()),array('pending','rejected'))) {
	echo display_error("You are not authorized to update the selected Observership. Returning to your Observerships index.");
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
	return;
}

switch($STEP){
	case 2:
		
		echo "<h1>Update Observership</h1>";
		
		$observership_array = $_POST;
		if ($OBSERVERSHIP->getStatus() == "rejected") {
			$observership_array["status"] = "pending";
		}
		$OBSERVERSHIP->mapArray($observership_array,"update");

		if (!$OBSERVERSHIP->isValid()){
			add_error("Sorry, there was a problem with the data you have entered. Please verify the information and try again.");
		} else {
			if ($OBSERVERSHIP->update($OBSERVERSHIP_ID)) {
				$url = ENTRADA_URL."/profile/observerships";
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


define('PUBLIC_OBSERVERSHIP_FORM',true);

$ACTION = "Update";

require_once 'form.inc.php';