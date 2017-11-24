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
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall<hall@ucalgary.ca>
 * @copyright Copyright 2011 University of Calgary. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_ENROLMENT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('course', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 1000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Manage Groups");

	$GROUP_IDS = array();
	$MEMBERS = 0;


		if ((isset($_GET["group_id"])) && (int) $_GET["group_id"])  { 
			$GROUP_ID = (int)$_GET["group_id"];
		}
			foreach($_POST["checked"] as $user_id) {
				$user_id = (int) trim($user_id);
				if($user_id) {
					$USER_IDS[] = $user_id;
				}
			}
			if(!@count($USER_IDS)) {
				add_error("There were no users to delete.");
			} elseif(isset($_POST["members"])) { 
				$MEMBERS = count($USER_IDS);
			}

		if ($ERROR) {
			$STEP = 1;
		} else {
			$STEP = 2;
		}

	// Display Page
	switch($STEP) {
		case 2 :
			if ($MEMBERS)  {  // Delete members
				foreach($USER_IDS as $proxy_id) {
					if ($GROUP_ID) {
						$db->Execute("DELETE FROM `group_members` WHERE `proxy_id` = ".$db->qstr($proxy_id)." AND `group_id` = ".$db->qstr($GROUP_ID));
					} else {
						$db->Execute("DELETE FROM `course_audience` WHERE `audience_type` = 'proxy_id' AND `course_id` = ".$db->qstr($COURSE_ID)." AND `audience_value` = ".$db->qstr($proxy_id));
					}
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/enrolment?id=".$COURSE_ID."\\'', 5000)";
				add_success("Successfully updated course enrolment. You will be returned to the enrolment page in 5 seconds. To go there now <a href=\"".ENTRADA_URL."/admin/courses/enrolment?id=".$COURSE_ID."\"> click here</a>.");
				echo display_success();

			}
			if ($ERROR) {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/enrolment?id=".$COURSE_ID."\\'', 5000)";
				echo display_error();
			}

		break;
		case 1 :
		default :

			
			if($ERROR) {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/enrolment?id=".$COURSE_ID."\\'', 5000)";
				echo display_error();
			}   
		break;
	}
}
