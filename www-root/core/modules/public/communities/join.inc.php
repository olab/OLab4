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
			if($result["member_active"] == 1) {
				header("Location: ".ENTRADA_URL."/community".$community_details["community_url"]);
				exit;
			} else {
				/**
				 * User is currently an inactive member of this community. Perhaps they have been disabled by an
				 * administrator. Display an error message.
				 */

				$ERROR++;
				$ERRORSTR[] = "You are already a member of this community; however, your account has been deactivated by an administrator.<br /><br />If you have questions, please contact one of the community administrators.";

				echo display_error();
			}
		} else {
			/**
			 * Check registration requirements for this community.
			 */
			switch($community_details["community_registration"]) {
				case 0 :	// Open Community
				case 1 :	// Open Registration
					continue;
				break;
				case 2 :	// Selected Group Registration
					$ALLOW_MEMBERSHIP = false;

					if(($community_details["community_members"] != "") && ($community_members = @unserialize($community_details["community_members"])) && (is_array($community_members)) && (count($community_members))) {
						if(in_array($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"], $community_members)) {
							$ALLOW_MEMBERSHIP = true;
						} else {
							foreach($community_members as $member_group) {
								if($member_group) {
									$pieces = explode("_", $member_group);

									if((isset($pieces[0])) && ($group = trim($pieces[0]))) {
										if($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] == $group) {
											if((isset($pieces[1])) && ($role = trim($pieces[1]))) {
												if($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] == $role) {
													$ALLOW_MEMBERSHIP = true;
													break;
												}
											} else {
												$ALLOW_MEMBERSHIP = true;
												break;
											}
										}
									}
								}
							}
						}
					}

					if(!$ALLOW_MEMBERSHIP) {
						$ERROR++;
						$ERRORSTR[] = "Your account (".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." &rarr; ".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"].") does not meet the group requirements setup by the community administrators.";
						$display_admin_list = true;
						application_log("notice", "User id ".$ENTRADA_USER->getID()." was not have the proper group requirements to join community id ".$COMMUNITY_ID);
					}
				break;
				case 3 :	// Selected Community Registration
					$ALLOW_MEMBERSHIP = false;

					if(($community_details["community_members"] != "") && ($community_members = @unserialize($community_details["community_members"])) && (is_array($community_members)) && (count($community_members))) {
						$query	= "SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `member_active` = '1' AND `community_id` IN ('".implode("', '", $community_members)."')";
						$result	= $db->GetRow($query);
						if($result) {
							$ALLOW_MEMBERSHIP = true;
						}
					}

					if(!$ALLOW_MEMBERSHIP) {
						$ERROR++;
						$ERRORSTR[] = "Your account does not meet the community membership requirements setup by the community administrators.";
						$display_admin_list = true;
						application_log("notice", "User id ".$ENTRADA_USER->getID()." was not have the proper community membership requirements to join community id ".$COMMUNITY_ID);
					}
				break;
				case 4 :	// Private Community
					$ERROR++;
					$ERRORSTR[] = "This community does not allow new registrations, in order to join this community a community administrator must add you.";
					$display_admin_list = true;
				break;
				default :
					$ERROR++;
					$ERRORSTR[] = "There has been an error when trying to join this community. The MEdTech Unit has been informed of the error, please try again later.";
					$display_admin_list = false;
					application_log("error", "Unknown community_registration value [".$community_details["community_registration"]."] for community id ".$COMMUNITY_ID);
				break;
			}

			echo "<h1>".html_encode($community_details["community_title"])."</h1>\n";
			if($community_details["community_description"]) {
				echo "<div class=\"content-small\" style=\"margin-bottom: 15px\"><strong>Community Description:</strong> ".nl2br(html_encode($community_details["community_description"]))."</div>";
			}
			if($ERROR) {
				echo display_error();
				$community_administrators = $db->GetAll("	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `fullname`, a.`proxy_id` 
															FROM `community_members` AS a
															LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
															ON a.`proxy_id` = b.`id`
															WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
															AND a.`member_acl` = '1'");
				if ($display_admin_list && $community_administrators) {
					echo "<div style=\"margin-left: 30px;\">If you have any questions, please contact one of the community administrators:\n";
					echo "\t<ul class=\"menu\" style=\"margin: 10px 0px 0px 20px;\">\n";
					foreach ($community_administrators as $community_administrator) {
						echo "\t\t<li class=\"community\"><a href=\"".ENTRADA_URL."/people?id=".$community_administrator["proxy_id"]."\">".$community_administrator["fullname"]."</a></li>\n";
					}
					echo "\t</ul>\n";
					echo "</div>\n";
				}
			} else {
				// Error Checking
				switch($STEP) {
					case 2 :
						$PROCESSED["community_id"]	= $COMMUNITY_ID;
						$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
						$PROCESSED["member_active"]	= 1;
						$PROCESSED["member_joined"]	= time();
						$PROCESSED["member_acl"]	= 0;

						if($db->AutoExecute("community_members", $PROCESSED, "INSERT")) {
							
							$notifys_type = array('announcement','event','poll');
							foreach ($notifys_type as $notify_type) {
									
								$current_notify = $db->GetOne("SELECT `proxy_id` FROM `community_notify_members` WHERE `proxy_id` = " . $db->qstr($ENTRADA_USER->getID()) . " AND `community_id` = " . $db->qstr($COMMUNITY_ID) . " AND `record_id` = " . $db->qstr($COMMUNITY_ID) . " AND `notify_type` = " . $db->qstr($notify_type));
								if ($current_notify) {
									if (!$db->Execute("UPDATE `community_notify_members` SET `notify_active` =  1 WHERE `proxy_id` = " . $db->qstr($ENTRADA_USER->getID()) . " AND `community_id` = " . $db->qstr($COMMUNITY_ID) . " AND `record_id` = " . $db->qstr($COMMUNITY_ID) . " AND `notify_type` = " . $db->qstr($notify_type))) {
										application_log("error", "can update community_notify_members for : ".$COMMUNITY_ID ."and Proxy id : ".$ENTRADA_USER->getID());
									} 
								} else {

									if (!$db->Execute("INSERT INTO `community_notify_members` (`notify_active`, `proxy_id`, `community_id`, `record_id`, `notify_type`) VALUES ( 1 , " . $db->qstr($ENTRADA_USER->getID()) . ", " . $db->qstr($COMMUNITY_ID) . ", " . $db->qstr($COMMUNITY_ID) . ", " . $db->qstr($notify_type) . ")")) {
										application_log("error", "can insert into community_notify_members for : ".$COMMUNITY_ID ."and Proxy id : ".$ENTRADA_USER->getID());
									} 
								}		
							}
							$member_id		= $db->Insert_Id();
							if ($MAILING_LISTS["active"]) {
								$mail_list = new MailingList($COMMUNITY_ID);
								$mail_list->add_member($PROCESSED["proxy_id"]);
							}
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully joined <strong>%s</strong>"), $community_details["community_title"]), "success", "login");
							communities_log_history($COMMUNITY_ID, 0, $member_id, "community_history_add_member", 1);
                            if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                community_notify($COMMUNITY_ID, $ENTRADA_USER->getActiveId(), "join", ENTRADA_URL."/people?id=".$ENTRADA_USER->getActiveId(), $COMMUNITY_ID);
                            }
                            $community_url = ENTRADA_URL . "/community" . $community_details["community_url"];
                            header("Location: " . $community_url);
                            exit;
						} else {
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Failed to join <strong>%s</strong><br /><br />We were unable to register you in this community. The MEdTech Unit has been informed of this error, please try again later."), $community_details["community_title"]), "error", $MODULE);
							application_log("error", "Unable to register ".$ENTRADA_USER->getActiveId()." in community id ".$COMMUNITY_ID.". Database said: ".$db->ErrorMsg());

							$community_url	= ENTRADA_URL . "/communities";
                            header("Location: " . $community_url);
                            exit;
						}
					break;
					default :
						continue;
					break;
				}

				// Display Content
				switch($STEP) {
					case 1 :	// Choose to join.
					default :
						/**
						 * Check registration requirements for this community.
						 */
						switch($community_details["community_registration"]) {
							case 0 :	// Open Community
								$NOTICE++;
								$NOTICESTR[] = "In order to post content in this community you must join it: <a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"color: #669900; font-size: 14px; font-weight: bold\">Click Here to Join Now!</a>";
							break;
							case 1 :	// Open Registration
							case 2 :	// Selected Group Registration
							case 3 :	// Selected Community Registration
								$NOTICE++;
								$NOTICESTR[] = "Before you can access the <strong>".html_encode($community_details["community_title"])."</strong> community you must join it. Do you want to join?<div style=\"margin-top: 10px; text-align: right\"><a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"color: #669900; font-size: 14px; font-weight: bold\">Yes, join now</a> <span style=\"font-size: 14px\">|</span> <a href=\"".ENTRADA_URL."/communities\" style=\"color: #669900; font-size: 14px; font-weight: bold\">Oops, no... cancel</a></div>";
							break;
							default :
								$ERROR++;
								$ERRORSTR[] = "There has been an error when trying to join this community. The MEdTech Unit has been informed of the error, please try again later.";

								application_log("error", "Unknown community_registration value [".$community_details["community_registration"]."] for community id ".$COMMUNITY_ID);
							break;
						}

						if($NOTICE) {
							echo display_notice();
						}
						if($ERROR) {
							echo display_error();
						}
					break;
				}
			}
		}
	} else {
		application_log("error", "User tried to join community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

		$ERROR++;
		$ERRORSTR[] = "The community you are trying to join either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

		echo display_error();
	}
} else {
	application_log("error", "User tried to join a community without providing a community_id.");

	header("Location: ".ENTRADA_URL."/communities");
	exit;
}
?>