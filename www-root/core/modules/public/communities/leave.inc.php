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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COMMUNITIES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($MAILING_LISTS["active"]) {
	require_once("Entrada/mail-list/mail-list.class.php");
}

$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities", "title" => "Community Registration");

$COMMUNITY_ID		= 0;

/**
 * Check for a community category to proceed.
 */
if((isset($_GET["community"])) && ((int) trim($_GET["community"]))) {
	$COMMUNITY_ID	= (int) trim($_GET["community"]);
} elseif((isset($_POST["community_id"])) && ((int) trim($_POST["community_id"]))) {
	$COMMUNITY_ID	= (int) trim($_POST["community_id"]);
}

/**
 * Ensure that the selected community is editable by you.
 */
if($COMMUNITY_ID) {
	$query				= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `community_active` = '1'";
	$community_details	= $db->GetRow($query);
	if($community_details) {
		$query	= "
				SELECT * FROM `community_members`
				WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			if((int) $result["member_active"]) {
				switch($STEP) {
					case 2 :
						$query = "DELETE FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `member_active` = '1' LIMIT 1";
						if($db->Execute($query)) {
							if ($MAILING_LISTS["active"]) {
								$mail_list = new MailingList($COMMUNITY_ID);
								$mail_list->remove_member($result["proxy_id"]);
							}

							if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
								community_notify($COMMUNITY_ID, $ENTRADA_USER->getActiveId(), "leave", ENTRADA_URL."/people?id=".$ENTRADA_USER->getActiveId(), $COMMUNITY_ID);
							}
							application_log("success", "Removed proxy_id [".$ENTRADA_USER->getActiveId()."] from community_id [".$COMMUNITY_ID."].");
						} else {
							application_log("error", "Unable to remove proxy_id [".$ENTRADA_USER->getActiveId()."] from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
						}
						
						if($_SESSION['details']['group'] == 'guest') {
							$query = "SELECT COUNT(*) AS total FROM `community_members`
									WHERE `community_members`.`proxy_id` = {$result['ID']} AND `community_members`.`member_active` = 1";
							$community_result = $db->GetRow($query);
							if(!isset($community_result) || $community_result['total'] == 0) {
								header("Location: ".ENTRADA_URL."?action=logout");
							}
						} else {
							header("Location: ".ENTRADA_URL."/communities");
						}
						
						exit;
					break;
					case 1 :
					default :
						echo "<h1>".html_encode($community_details["community_title"])."</h1>\n";
						if($community_details["community_description"]) {
							echo "<div class=\"content-small\" style=\"margin-bottom: 15px\"><strong>Community Description:</strong> ".nl2br(html_encode($community_details["community_description"]))."</div>";
						}

						//Be sure guests are informed if they are leaving the community
						$guest_notice = '';
						if($_SESSION['details']['group'] == 'guest') {
							 $query = "	SELECT COUNT(*) as total FROM `community_members`
										WHERE `community_members`.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." 
										AND `community_members`.`member_active` = 1 
										ORDER BY `community_members`.`member_joined`";
							$result = $db->GetRow($query);
							if(($result) && $result['total'] == 1) {
								$guest_notice = " As a guest you cannot log in if you are not a member of any communities. This is the last community you are a member of so leaving it will <strong>deactivate</strong> your account.";
							}
						}
						$NOTICE++;
						$NOTICESTR[] = "Are you sure you want to be removed from the <strong>".html_encode($community_details["community_title"])."</strong> community?".$guest_notice."<div style=\"margin-top: 10px; text-align: right\"><a href=\"".ENTRADA_URL."/communities?section=leave&community=".$COMMUNITY_ID."&step=2\" style=\"color: #669900; font-size: 14px; font-weight: bold\">Yes, leave now</a> <span style=\"font-size: 14px\">|</span> <a href=\"".ENTRADA_URL."/community".html_encode($community_details["community_url"])."\" style=\"color: #669900; font-size: 14px; font-weight: bold\">Oops, no... cancel</a></div>";

						echo display_notice();
					break;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "You are not an active member of the community that you are trying to leave.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate.";

				echo display_error();
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You are not a member of the community that you are trying to leave.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate.";

			echo display_error();
		}
	} else {
		application_log("error", "User tried to leave community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

		$ERROR++;
		$ERRORSTR[] = "The community you are trying to leave either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

		echo display_error();
	}
} else {
	application_log("error", "User tried to leave a community without providing a community_id.");

	header("Location: ".ENTRADA_URL."/communities");
	exit;
}
?>