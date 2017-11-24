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
 * Allows students to delete an elective in the system if it has not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('group', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	
	ob_clear_open_buffers();
	
	if(isset($_POST["checked"])) {
		$group_ids = $_POST["checked"];
	} else {
		header("Location: ".ENTRADA_URL."/admin/groups");
		exit;
	}
	$result = $db->GetOne("	SELECT TRIM(`group_name`) FROM `groups` WHERE `group_id` = ".$group_ids[0]);
	list($filename, $result) = preg_split('/ /', $result, 1);
	$filename = "Group-$filename-".date("dmhi",time())."csv";
	header("Content-Type:  application/vnd.ms-excel");
	header("Content-Disposition: \"".$filename."\"; filename=\"".$filename."\"");

	echo "\"Group Name\",\"Firstname\",\"Lastname\",\"Active\",\"Osler ID\"\n";

	$query = "	SELECT * FROM `groups`
				WHERE `group_id` IN (".implode(", ", $group_ids).")
				ORDER By `group_name`";
	$groups	= $db->GetAll($query);
	
	foreach ($groups as $group) {
		$query	= "	SELECT a.`firstname`, a.`lastname`, b.`member_active`, a.`id`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					INNER JOIN `group_members` b ON a.`id` = b.`proxy_id`
					WHERE b.`group_id` = ".$db->qstr($group["group_id"])."
					ORDER BY a.`lastname` ASC, a.`firstname` ASC";
		$results = $db->GetAll($query);

		if ($results) {
			foreach ($results as $result) {
				echo html_encode($group["group_name"]).",";
				$result["firstname"] = "\"".html_encode($result["firstname"])."\"";
				$result["lastname"] = "\"".html_encode($result["lastname"])."\"";
				echo implode(",", $result)."\n";
			}
		}
	}
}
exit();