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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COMMUNITIES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($MAILING_LISTS["active"]) {
	require_once("Entrada/mail-list/mail-list.class.php");
}

$COMMUNITY_ID		= 0;
$PREFERENCES		= preferences_load($MODULE);
$PROXY_IDS			= array();
$current_members_ids = array();

/**
 * Check for a community category to proceed.
 */
if ((isset($_GET["community"])) && ((int) trim($_GET["community"]))) {
	$COMMUNITY_ID	= (int) trim($_GET["community"]);
} elseif ((isset($_POST["community_id"])) && ((int) trim($_POST["community_id"]))) {
	$COMMUNITY_ID	= (int) trim($_POST["community_id"]);
}

if ((isset($_GET["type"])) && ($tmp_action_type = clean_input(trim($_GET["type"]), "alphanumeric"))) {
	$ACTION_TYPE	= $tmp_action_type;
} elseif ((isset($_POST["type"])) && ($tmp_action_type = clean_input(trim($_POST["type"]), "alphanumeric"))) {
	$ACTION_TYPE	= $tmp_action_type;
}
unset($tmp_action_type);

/**
 * Ensure that the selected community is editable by you.
 */
if ($COMMUNITY_ID) {
	$query				= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `community_active` = '1'";
	$community_details	= $db->GetRow($query);
	if ($community_details) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";

		$BREADCRUMB[]		= array("url" => ENTRADA_URL."/community".$community_details["community_url"], "title" => limit_chars($community_details["community_title"], 50));
		$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities?".replace_query(), "title" => "Manage Members");
		if ($ENTRADA_ACL->amIAllowed(new CommunityResource($COMMUNITY_ID), 'update')) {
			?>
            <a class="btn space-below" href="<?php echo html_encode(ENTRADA_URL."/community".$community_details["community_url"]); ?>">
                <i class="icon-chevron-left" style="margin: 0"></i> Back To Community
            </a>
            <?php
            Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

            echo "<h1>".html_encode($community_details["community_title"])."</h1>\n";

			// Error Checking
			switch ($STEP) {
				case 3 :
				case 2 :
					switch ($ACTION_TYPE) {
						case "addguest":
							$PROCESSED = array();
							$GUEST_PROXY_ID = 0;
							$GUEST_ACCESS = false;
							$GUEST_NEW_ACCESS = false;
							$member_add_success	= 0;
							$member_add_failure	= 0;
							/**
							 * Required field "firstname" / Firstname.
							 */
							if ((isset($_POST["firstname"])) && ($firstname = clean_input($_POST["firstname"], "trim"))) {
								$PROCESSED["firstname"] = $firstname;
							} else {
								add_error("The firstname of the user is a required field.");
							}

							/**
							 * Required field "lastname" / Lastname.
							 */
							if ((isset($_POST["lastname"])) && ($lastname = clean_input($_POST["lastname"], "trim"))) {
								$PROCESSED["lastname"] = $lastname;
							} else {
								add_error("The lastname of the user is a required field.");
							}

							/**
							 * Required field "email" / Primary E-Mail.
							 */
							if ((isset($_POST["email"])) && ($email = clean_input($_POST["email"], "trim", "lower"))) {
								if (@valid_address($email)) {
									$query	= "SELECT * FROM `".AUTH_DATABASE."`.`user_data`
												LEFT JOIN `".AUTH_DATABASE."`.`user_access` ON `user_access`.`user_id` = `user_data`.`id`
												WHERE `user_data`.`email` = ".$db->qstr($email)."
												AND (`user_access`.`group` != 'guest' && `user_access`.`role` != 'communityinvite');";
									$result	= $db->GetRow($query);
									if ($result) {
										add_error("The e-mail address <strong>".html_encode($email)."</strong> already exists in the system for username <strong>".html_encode($result["username"])."</strong>. Please provide a unique e-mail address for this user or select the existing user on the <strong>Add Members</strong> tab.");
									} else {
										$PROCESSED["email"] = $email;
									}
								} else {
									add_error("The primary e-mail address you have provided is invalid. Please make sure that you provide a properly formatted e-mail address.");
								}
							} else {
								add_error("The primary e-mail address is a required field.");
							}

							if (!$ERROR) {
							//Check to see if this user is already in the system.

								$query = "	SELECT `user_data`.`id` as `proxy_id`, `user_data`.`username`
											FROM `".AUTH_DATABASE."`.`user_data`
											LEFT JOIN `".AUTH_DATABASE."`.`user_access`
											ON `user_access`.`user_id` = `user_data`.`id`
											WHERE `user_data`.`email` = ".$db->qstr($PROCESSED["email"]);
								$result	= $db->GetRow($query);
								if ($result) {
									//User exists already, just grant them access to the community
									$GUEST_PROXY_ID = $result["proxy_id"];
									$username = $result["username"];
								} else {
									$pieces = explode("@", $PROCESSED["email"]);

									$original = "guest.".substr(clean_input($pieces[0], "credentials"), 0, 16);
									$username = $original;
									$i = "";
									do {
										$username = $original.$i;
										$i++;
									} while($db->GetRow("SELECT `username` FROM `".AUTH_DATABASE."`.`user_data` WHERE `username` = ".$db->qstr($username)));


									$PROCESSED["username"]			= $username;
									$PROCESSED["number"]			= 0;
									$PROCESSED["password"]			= "";
									$PROCESSED["organisation_id"]	= $_SESSION["details"]["organisation_id"];
									$PROCESSED["prefix"]			= "";
									$PROCESSED["email_alt"]			= "";
									$PROCESSED["telephone"]			= "";
									$PROCESSED["fax"]				= "";
									$PROCESSED["address"]			= "";
									$PROCESSED["city"]				= "";
									$PROCESSED["province"]			= "";
									$PROCESSED["postcode"]			= "";
									$PROCESSED["country"]			= "";
									$PROCESSED["notes"]				= "Guest created by proxy id ".$ENTRADA_USER->getID();

									if (($db->AutoExecute(AUTH_DATABASE.".user_data", $PROCESSED, "INSERT")) && ($PROCESSED_ACCESS["user_id"] = $db->Insert_Id())) {
										$GUEST_PROXY_ID = $PROCESSED_ACCESS["user_id"];
									} else {
										add_error("Unable to create a new user account at this time. An administrator has been informed of this error, so please try again later.");

										application_log("error", "Unable to create new user account. Database said: ".$db->ErrorMsg());
									}
								}
								if ($GUEST_PROXY_ID > 0) {
									$query = "SELECT `account_active`, `access_starts`, `access_expires` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($GUEST_PROXY_ID)." AND `app_id` IN (".AUTH_APP_IDS_STRING.")";
									$result = $db->GetRow($query);
									if ($result) {
										if ($result["account_active"] == "false") {
											add_error("Unable to create a new user account at this time because the account you are trying to create already exists and is deactivated. Contact an administrator for more information.");
										} else if ($result["access_starts"] > time()) {
											add_error("Unable to create a new user account at this time because the account you are trying to create already exists but it's access hasn't started yet. Contact an administrator for more information.");
										} else if ($result["access_expires"] != 0 && $result["access_expires"] < time()) {
											add_error("Unable to create a new user account at this time because the account you are trying to create already exists but it's access has expired. Contact an administrator for more information.");
										} else {
											$GUEST_ACCESS = true;
										}
									} else {
										//User needs access
										$GUEST_NEW_ACCESS = true;
										$PROCESSED_ACCESS["app_id"]				= AUTH_APP_ID;
										$PROCESSED_ACCESS["organisation_id"]    = $ENTRADA_USER->getActiveOrganisation();
										$PROCESSED_ACCESS["acount_active"]		= "true";
										$PROCESSED_ACCESS["access_starts"]		= time();
										$PROCESSED_ACCESS["access_expires"]		= 0;
										$PROCESSED_ACCESS["last_login"]			= 0;
										$PROCESSED_ACCESS["last_ip"]			= 0;
										$PROCESSED_ACCESS["role"]				= "communityinvite";
										$PROCESSED_ACCESS["group"]				= "guest";
										$PROCESSED_ACCESS["extras"]				= "";
										$PROCESSED_ACCESS["notes"]				= "Guest created by proxy id ".$ENTRADA_USER->getID();

										if ($db->AutoExecute(AUTH_DATABASE.".user_access", $PROCESSED_ACCESS, "INSERT")) {
											$GUEST_ACCESS = true;
										} else {
											add_error("Unable to create a new user account at this time. An administrator has been informed of this error, so please try again later.");
											application_log("error", "Unable to enter new user access record. User data already exists. Database said: ".$db->ErrorMsg());
										}
									}

									require_once("Classes/utility/Template.class.php");
									require_once("Classes/utility/TemplateMailer.class.php");

									if ($GUEST_ACCESS) {
										$hash = get_hash();

										if ($GUEST_NEW_ACCESS) {
											$PROCESSED_EMAIL = array();
											$PROCESSED_EMAIL["ip"] = $_SERVER["REMOTE_ADDR"];
											$PROCESSED_EMAIL["date"] = time();
											$PROCESSED_EMAIL["user_id"] = $GUEST_PROXY_ID;
											$PROCESSED_EMAIL["hash"] = $hash;
											$PROCESSED_EMAIL["complete"] = 0;

											if ($db->AutoExecute("`".AUTH_DATABASE."`.`password_reset`", $PROCESSED_EMAIL, "INSERT")) {
												$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/community-new-user.xml";
											} else {
												$xml_file = "";

												application_log("error", "Error inserting new password_reset into database from Communities > Manage Members. Database said: ".$db->ErrorMsg());
											}
										} else {
											$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/community-new-access.xml";
										}

										try {
											$template = new Template($xml_file);
											$mail = new TemplateMailer(new Zend_Mail());
											$mail->addHeader("X-Section", "Communities / Manage Members", true);
											$from = array("email" => $_SESSION["details"]["email"], "firstname" => $_SESSION["details"]["firstname"], "lastname" => $_SESSION["details"]["lastname"]);
											$to = array("email" => $PROCESSED["email"], "firstname" => $PROCESSED["firstname"], "lastname" => $PROCESSED["lastname"]);
											$keywords = array(
												"application_name" => APPLICATION_NAME,
												"from_firstname" => $_SESSION["details"]["firstname"],
												"from_lastname" => $_SESSION["details"]["lastname"],
												"to_fullname" => ($PROCESSED["firstname"]." ".$PROCESSED["lastname"]),
												"community_title" => $community_details["community_title"],
												"community_url" => ENTRADA_URL."/community".$community_details["community_url"],
												"username" => $username,
												"password_url" => PASSWORD_RESET_URL."?hash=".rawurlencode($PROCESSED_ACCESS["user_id"].":".$hash)
											);

											if ($mail->send($template, $to, $from, DEFAULT_LANGUAGE, $keywords)) {
												if ($MAILING_LISTS["active"]) {
                                                    try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
												}
											} else {
												add_error("We were unable to send an invitation e-mail to the guest at this time.<br /><br />A system administrator was notified of this issue, but you may wish to contact this individual manually and let them know they have been added.");

												application_log("error", "Unable to send community guest notification to [".$to["email"]."]. Zend_Mail said: ".$mail->ErrorInfo);
											}
										} catch (Exception $e) {
											add_error("We were unable to send an invitation e-mail to the guest at this time.<br /><br />A system administrator was notified of this issue, but you may wish to contact this individual manually and let them know they have been added.");

											application_log("error", "Unable to load the XML file [".$xml_file."] or the XML file did not contain the language requested [".DEFAULT_LANGUAGE."], when attempting to send a community guest notification.");
										}

										$query = "	SELECT a.`id` AS `proxy_id`, c.`cmember_id`, c.`member_acl`
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON b.`user_id` = ".$db->qstr($GUEST_PROXY_ID)."
													AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
													LEFT JOIN `community_members` AS c
													ON c.`proxy_id` = ".$db->qstr($GUEST_PROXY_ID)."
													AND c.`community_id` = ".$db->qstr($COMMUNITY_ID)."
													WHERE a.`id` = ".$db->qstr($GUEST_PROXY_ID);
										$result	= $db->GetRow($query);
										if ($result) {
											if ((int) $result["cmember_id"]) {
												if (@$db->AutoExecute("community_members", array("member_active" => 1), "UPDATE", "`cmember_id` = ".$db->qstr((int) $result["cmember_id"]))) {
													if ($MAILING_LISTS["active"]) {
														$mail_list->add_member($GUEST_PROXY_ID, ((bool)$result["member_acl"]));
													}
													$member_add_success++;
												} else {
													$member_add_failure++;
													application_error("error", "Unable to activate a deactivated member. Database said: ".$db->ErrorMsg());
												}
											} else {
												$PROCESSED = array();
												$PROCESSED["community_id"]	= $COMMUNITY_ID;
												$PROCESSED["proxy_id"]		= $GUEST_PROXY_ID;
												$PROCESSED["member_active"]	= 1;
												$PROCESSED["member_joined"]	= time();
												$PROCESSED["member_acl"]	= 0;

												if (@$db->AutoExecute("community_members", $PROCESSED, "INSERT")) {
													if ($MAILING_LISTS["active"]) {
														$mail_list->add_member($GUEST_PROXY_ID);
													}
													$member_add_success++;
												} else {
													$member_add_failure++;
													application_error("error", "Unable to insert a new community member. Database said: ".$db->ErrorMsg());
												}
											}
										}


										if ($member_add_success) {
											$url = ENTRADA_URL . "/communities?section=members&community=" . $COMMUNITY_ID;

											if ($GUEST_NEW_ACCESS) {
                                                Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully created a new guest user in system, and have given them access to this community."), "success", $MODULE);
											} else {
                                                Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully given a guest access to this community."), "success", $MODULE);
											}
											communities_log_history($COMMUNITY_ID, 0, $member_add_success, "community_history_add_members", 1);
										} else {
											add_error("The guest user has been added to the system however they have not joined your community. An administrator has been informed of this issue, please try again later.");

											application_log("error", "Unable to make the user a community member. Database said: ".$db->ErrorMsg());
										}
									}
								} else {
									add_error("The guest user could not be added to your community. An administrator has been informed of this issue, please try again later.");

									application_log("error", "Unable to make the user a community member. Database said: ".$db->ErrorMsg());

								}
							}
						break;
						case "add" :
							$member_add_success	= 0;
							$member_add_failure	= 0;

							if (isset($_POST["members"]) && (is_array($_POST["members"]))) {
								$proxy_ids = array();
								foreach ($_POST["members"] as $proxy_id) {
									if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
										$proxy_ids[] = $tmp_input;
									}
								}

								if ($MAILING_LISTS["active"]) {
									try {
									    $mail_list = new MailingList($COMMUNITY_ID);
                                    } catch (Zend_Exception $e) {
                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                    }
								}

								foreach ($proxy_ids as $proxy_id) {
									if (($proxy_id = (int) trim($proxy_id))) {
										$query	= "
												SELECT a.`id` AS `proxy_id`, c.`cmember_id`, c.`member_acl`
												FROM `".AUTH_DATABASE."`.`user_data` AS a
												LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
												ON b.`user_id` = ".$db->qstr($proxy_id)."
												AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
												LEFT JOIN `community_members` AS c
												ON c.`proxy_id` = ".$db->qstr($proxy_id)."
												AND c.`community_id` = ".$db->qstr($COMMUNITY_ID)."
												WHERE a.`id` = ".$db->qstr($proxy_id);
										$result	= $db->GetRow($query);
										if ($result) {
											$notifys_type = array('announcement','event','poll');
											foreach ($notifys_type as $notify_type) {
													
												$current_notify = $db->GetOne("SELECT `proxy_id` FROM `community_notify_members` WHERE `proxy_id` = " . $db->qstr($proxy_id) . " AND `community_id` = " . $db->qstr($COMMUNITY_ID) . " AND `record_id` = " . $db->qstr($COMMUNITY_ID) . " AND `notify_type` = " . $db->qstr($notify_type));
												if ($current_notify) {
													if (!$db->Execute("UPDATE `community_notify_members` SET `notify_active` =  1 WHERE `proxy_id` = " . $db->qstr($proxy_id) . " AND `community_id` = " . $db->qstr($COMMUNITY_ID) . " AND `record_id` = " . $db->qstr($COMMUNITY_ID) . " AND `notify_type` = " . $db->qstr($notify_type))) {
														application_log("error", "can update community_notify_members for : ".$COMMUNITY_ID ."and Proxy id : ".$proxy_id);
													} 
												} else {

													if (!$db->Execute("INSERT INTO `community_notify_members` (`notify_active`, `proxy_id`, `community_id`, `record_id`, `notify_type`) VALUES ( 1 , " . $db->qstr($proxy_id) . ", " . $db->qstr($COMMUNITY_ID) . ", " . $db->qstr($COMMUNITY_ID) . ", " . $db->qstr($notify_type) . ")")) {
														application_log("error", "can insert into community_notify_members for : ".$COMMUNITY_ID ."and Proxy id : ".$proxy_id);
													} 
												}		
											}
											if ((int) $result["cmember_id"]) {
												if (@$db->AutoExecute("community_members", array("member_active" => 1), "UPDATE", "`cmember_id` = ".$db->qstr((int) $result["cmember_id"]))) {
													if ($MAILING_LISTS["active"]) {
														$mail_list->add_member($proxy_id, ((bool)$result["member_acl"]));
													}
													$member_add_success++;
												} else {
													$member_add_failure++;
													application_error("error", "Unable to activate a deactivated member. Database said: ".$db->ErrorMsg());
												}
											} else {
												$PROCESSED = array();
												$PROCESSED["community_id"]	= $COMMUNITY_ID;
												$PROCESSED["proxy_id"]		= (int) $result["proxy_id"];
												$PROCESSED["member_active"]	= 1;
												$PROCESSED["member_joined"]	= time();
												$PROCESSED["member_acl"]	= 0;

												if (@$db->AutoExecute("community_members", $PROCESSED, "INSERT")) {
													if ($MAILING_LISTS["active"]) {
														$mail_list->add_member($proxy_id);
													}
													$member_add_success++;
												} else {
													$member_add_failure++;
													application_error("error", "Unable to insert a new community member. Database said: ".$db->ErrorMsg());
												}

											}
										}
									}
								}
							} else {
                                add_error("You must select a user(s) to add to this group. Please be sure that you select at least one user to add to this group from the interface.");

                                $STEP = 1;
                            }


							if ($member_add_success) {
							    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> new member%s to this community."), $member_add_success,($member_add_success != 1) ? "s" : ""), "success", $MODULE);
                                communities_log_history($COMMUNITY_ID, 0, $member_add_success, "community_history_add_members", 1);
                            }
							if ($member_add_failure) {
								add_error("Failed to add or update".$member_add_failure." member".(($member_add_failure != 1) ? "s" : "")." during this process. The MEdTech Unit has been informed of this error, please try again later.");
							}
                        break;
						case "admins" :
							$url = ENTRADA_URL."/communities?section=members&community=".$COMMUNITY_ID;
							if ((isset($_POST["admin_action"])) && (@in_array(strtolower($_POST["admin_action"]), array("delete", "deactivate", "demote")))) {
								if ((isset($_POST["admin_proxy_ids"])) && (is_array($_POST["admin_proxy_ids"])) && (count($_POST["admin_proxy_ids"]))) {
									foreach ($_POST["admin_proxy_ids"] as $proxy_id) {
										if ($proxy_id = (int) trim($proxy_id)) {
											$query	= "
													SELECT a.*
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON b.`user_id` = ".$db->qstr($proxy_id)."
													AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
													WHERE a.`id` = ".$db->qstr($proxy_id);
											$result	= $db->GetRow($query);
											if ($result) {
												$PROXY_IDS[] = $proxy_id;
											}
										}
									}
								}

								if ((is_array($PROXY_IDS)) && (count($PROXY_IDS))) {									
									switch (strtolower($_POST["admin_action"])) {
										case "delete" :
											$query	= "DELETE FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_acl` = '1'";
											$result	= $db->Execute($query);
											if (($result) && ($total_deleted = $db->Affected_Rows())) {
												if ($MAILING_LISTS["active"]) {
													try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
													foreach ($PROXY_IDS as $proxy_id) {
														$mail_list->deactivate_member($proxy_id);
													}
												}
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully removed <strong>%s</strong> administrator%s from the <strong>%s</strong> community."), $total_deleted ,($total_deleted != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
											} else {
												add_error("There was a problem removing these community administrators from the system; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to remove admins from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										case "deactivate" :
											if (($db->AutoExecute("community_members", array("member_active" => 0, "member_acl" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_active` = '1' AND `member_acl` = '1'")) && ($total_updated = $db->Affected_Rows())) {
												if ($MAILING_LISTS["active"]) {
                                                    try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
                                                    
													foreach ($PROXY_IDS as $proxy_id) {
														$mail_list->deactivate_member($proxy_id);
													}
												}
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully deactivated <strong>%s</strong> administrator%s in the <strong>%s</strong> community."), $total_updated ,($total_updated != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
											} else {
												add_error("There was a problem deactivating these community administrators in the system; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to deactivate admins from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										case "demote" :
											if (($db->AutoExecute("community_members", array("member_acl" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_active` = '1' AND `member_acl` = '1'")) && ($total_updated = $db->Affected_Rows())) {
												if ($MAILING_LISTS["active"]) {
													try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
                                                    
													foreach ($PROXY_IDS as $proxy_id) {
														$mail_list->demote_administrator($proxy_id);
													}
												}
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully demoted <strong>%s</strong> administrator%s to regular member status in the <strong>%s</strong> community."), $total_updated ,($total_updated != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
											} else {
												add_error("There was a problem demoting these community administrators; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to demote admins from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										default :
										/**
										 * This should never happen, as I'm checking the member_action above.
										 */
											continue;
                                        break;
									}
								} else {
									add_error("In order to complete this action, you will need to select at least 1 administrator from the list.");
								}
							} else {
								add_error("Unrecognized Admin Action error; the MEdTech Unit has been informed of this error. Please try again later.");

								application_log("error", "The provided action_type [".$ACTION_TYPE."] is invalid.");
							}
                        break;
						case "members" :
							$url = ENTRADA_URL . "/communities?section=members&community=" . $COMMUNITY_ID;
							if ((isset($_POST["member_action"])) && (@in_array(strtolower($_POST["member_action"]), array("delete", "deactivate", "promote")))) {
								if ((isset($_POST["member_proxy_ids"])) && (is_array($_POST["member_proxy_ids"])) && (count($_POST["member_proxy_ids"]))) {
									foreach ($_POST["member_proxy_ids"] as $proxy_id) {
										if ($proxy_id = (int) trim($proxy_id)) {
											$query	= "
													SELECT a.*
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON b.`user_id` = ".$db->qstr($proxy_id)."
													AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
													WHERE a.`id` = ".$db->qstr($proxy_id);
											$result	= $db->GetRow($query);
											if ($result) {
												$PROXY_IDS[] = $proxy_id;
											}
										}
									}
								}

								if ((is_array($PROXY_IDS)) && (count($PROXY_IDS))) {
									switch (strtolower($_POST["member_action"])) {
										case "delete" :
											$query	= "DELETE FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_acl` = '0'";
											$result	= $db->Execute($query);
											if (($result) && ($total_deleted = $db->Affected_Rows())) {
												if ($MAILING_LISTS["active"]) {
													try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
                                                    
													foreach ($PROXY_IDS as $proxy_id) {
														$mail_list->deactivate_member($proxy_id);
													}
												}
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully removed <strong>%s</strong> member%s from the <strong>%s</strong> community."), $total_updated ,($total_deleted != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
											} else {
												add_error("There was a problem removing these community members from the system; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to remove members from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										case "deactivate" :
											if (($db->AutoExecute("community_members", array("member_active" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_active` = '1' AND `member_acl` = '0'")) && ($total_updated = $db->Affected_Rows())) {
												if ($MAILING_LISTS["active"]) {
													try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
                                                    
													foreach ($PROXY_IDS as $proxy_id) {
														$mail_list->deactivate_member($proxy_id);
													}
												}
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully deactivated <strong>%s</strong> member%s in the <strong>%s</strong> community."), $total_updated ,($total_deleted != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
											} else {
												add_error("There was a problem deactivating these community members in the system; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to deactivate members from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										case "promote" :
											if ($db->AutoExecute("community_members", array("member_acl" => 1), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` IN ('".implode("', '", $PROXY_IDS)."') AND `member_active` = '1' AND `member_acl` = '0'")) {
												if ($MAILING_LISTS["active"]) {
													try {
													$mail_list = new MailingList($COMMUNITY_ID);
                                                    } catch (Zend_Exception $e) {
                                                        application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                                    }
                                                    
													if ($mail_list) {
														foreach ($PROXY_IDS as $proxy_id) {
															$mail_list->promote_administrator($proxy_id);
														}
													}
												}
                                                $total_updated = ($db->Affected_Rows() ? $db->Affected_Rows() : 1);
                                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully promoted <strong>%s</strong> member%s in the <strong>%s</strong> community."), $total_updated ,($total_deleted != 1) ? "s" : "", $community_details["community_title"]), "success", $MODULE);
                                            } else {
												add_error("There was a problem promoting these community members; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "Unable to promote members from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
											}
                                        break;
										default :
										/**
										 * This should never happen, as I'm checking the member_action above.
										 */
											continue;
                                        break;
									}
								} else {
									add_error("In order to complete this action, you will need to select at least 1 user from the list.");
								}
							} else {
								add_error("Unrecognized Member Action error; the MEdTech Unit has been informed of this error. Please try again later.");

								application_log("error", "The provided action_type [".$ACTION_TYPE."] is invalid.");
							}
                        break;
						default :
							add_error("Unrecognized Action Type selection; the MEdTech Unit has been informed of this error. Please try again later.");

							application_log("error", "The provided action_type [".$ACTION_TYPE."] is invalid.");
                        break;
					}

					if ($ERROR) {
						$STEP = 1;
					}
                break;
				case 1 :
				default :
					continue;
                break;
			}

			// Display Content
			switch ($STEP) {
				case 3 :
                break;
				case 2 :
				    $url = ENTRADA_URL . "/" . $MODULE . "?section=members&community=" . $COMMUNITY_ID;
                    if (!$ERROR) {
                        header("Location: " . $url);
                        exit;
                    }
                break;
				case 1 :
				default :
					/**
					 * Update requested sort column.
					 * Valid: date, title
					 */
					$HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>";
					$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
					$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
					$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
					if (isset($_GET["sb"])) {
						if (@in_array(trim($_GET["sb"]), array("date", "name", "type"))) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = trim($_GET["sb"]);
						}

						$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
					} else {
						if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "date";
						}
					}

					/**
					 * Update requested order to sort by.
					 * Valid: asc, desc
					 */
					if (isset($_GET["so"])) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

						$_SERVER["QUERY_STRING"]	= replace_query(array("so" => false));
					} else {
						if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "asc";
						}
					}

					/**
					 * Update requsted number of rows per page.
					 * Valid: any integer really.
					 */
					if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
						$integer = (int) trim($_GET["pp"]);

						if (($integer > 0) && ($integer <= 250)) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $integer;
						}

						$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
					} else {
						if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"])) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = 15;
						}
					}

					/**
					 * Provide the queries with the columns to order by.
					 */
					switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
						case "name" :
							$SORT_BY	= "CONCAT_WS(', ', b.`lastname`, b.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
                        break;
						case "type" :
							$SORT_BY	= "CASE c.`group` WHEN 'guest' THEN 1 WHEN '%' THEN 2 END ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
                        break;
						case "date" :
						default :
							$SORT_BY	= "a.`member_joined` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
                        break;
					}

					if ($NOTICE) {
						echo display_notice();
					}
					if ($ERROR) {
						echo display_error();
					}

                    $current_members_ids = array();
                    ?>

					<div class="tab-pane" id="community_members_div">
						<div class="tab-page members">
							<h3 class="tab">Members</h3>
							<h2 style="margin-top: 0px">Community Members</h2>
							<?php
							if ($MAILING_LISTS["active"]) {
                                try {
                                    $mail_list = new MailingList($COMMUNITY_ID);
                                } catch (Zend_Exception $e) {
                                    application_log("error", "Instantiating mailing list failed. Exception message: ".$e->getMessage());
                                }
							}

							/**
							 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
							 */
							$query		= "
										SELECT a.*, b.`username`, b.`firstname`, b.`lastname`, b.`email`, c.`group`
										FROM `community_members` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
										ON a.`proxy_id` = b.`id`
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
										ON c.`user_id` = b.`id`
										AND c.`app_id` IN (".AUTH_APP_IDS_STRING.")
										WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
										AND a.`member_active` = '1'
										AND a.`member_acl` = '0'
										GROUP BY b.`id`
										ORDER BY %s";

							$query		= sprintf($query, $SORT_BY);
							$results	= $db->GetAll($query);

							if ($results) {
								$HEAD[] = "
                                    <script type=\"text/javascript\">
                                        jQuery(document).ready(function() {
                                            jQuery('#membersTable').dataTable(
                                                {
                                                    'sPaginationType': 'full_numbers',
                                                    'aoColumns': [
                                                        null,
                                                        null,
                                                        null,
                                                        null,
                                                        {'sType': 'alt-string'}
                                                    ],
                                                    'bAutoWidth': false
                                                }
                                            );
                                        });
                                    </script>";
								?>
								<form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("section" => "members", "type" => "members", "step" => 2)); ?>" method="post">
									<table class="tableList membersTableList" id="membersTable" style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Community Members">
										<colgroup>
											<col class="modified" />
											<col class="date" />
											<col class="title" />
											<col class="type" />
											<col class="list-status" />
										</colgroup>
										<thead>
											<tr>
												<td class="modified">&nbsp;</td>
												<td class="date"><div class="noLink">Member Since</div></td>
												<td class="title"><div class="noLink">Member Name</div></td>
												<td class="type"><div class="noLink">Member Type</div></td>
												<td class="list-status"><div class="noLink">Mail List Status</div></td>
											</tr>
										</thead>
										<tfoot>
											<tr>
												<td colspan="2" style="padding-top: 15px">&nbsp;</td>
												<td style="padding-top: 15px; text-align: right" colspan="3">
													<select id="member_action" name="member_action" style="vertical-align: middle; width: 200px">
														<option value="">-- Select Member Action --</option>
														<option value="delete">1. Remove members</option>
														<option value="deactivate">2. Deactivate / ban members</option>
														<option value="promote">3. Promote to administrator</option>
													</select>
													<input type="submit" class="btn btn-primary" value="Proceed" style="vertical-align: middle" />
												</td>
											</tr>
										</tfoot>
										<tbody>
										<?php
										foreach ($results as $result) {
											echo "<tr>\n";
											echo "	<td><input type=\"checkbox\" name=\"member_proxy_ids[]\" value=\"".(int) $result["proxy_id"]."\" /></td>\n";
											echo "	<td>".date(DEFAULT_DATE_FORMAT, $result["member_joined"])."</td>\n";
											echo "	<td><a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["username"])."\"".(($result["proxy_id"] == $ENTRADA_USER->getActiveId()) ? " style=\"font-weight: bold" : "")."\">".html_encode($result["firstname"]." ".$result["lastname"])."</a></td>\n";
											echo "	<td>".($result["group"] == "guest" ? "Guest" : "Member" )."</td>\n";
											echo "	<td class=\"list-status\"><img src=\"images/".(($MAILING_LISTS["active"]) && $mail_list->users[($result["proxy_id"])]["member_active"] ? "list-status-online.gif\" alt=\"Enabled\"" : "list-status-offline.gif\" alt=\"Disabled\"")." /></td>\n";
											echo "</tr>\n";
                                            $current_members_ids[] = $result["proxy_id"];
										}
										?>
										</tbody>
									</table>
								</form>
								<?php
							} else {
								echo display_notice(array("Your community has no members at this time, you should add some people by clicking the &quot;<strong>Add Members</strong>&quot; tab."));
							}
							?>
						</div>
						<div class="tab-page members">
							<h3 class="tab">Administrators</h3>
							<h2 style="margin-top: 0px">Community Administrators</h2>
							<?php

							/**
							 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
							 */
							$query		= "
										SELECT a.*, b.`username`, b.`firstname`, b.`lastname`, b.`email`
										FROM `community_members` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
										ON a.`proxy_id` = b.`id`
										WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
										AND a.`member_active` = '1'
										AND a.`member_acl` = '1'
										ORDER BY %s";

							// If the user has sorted on Member Type in the members tab and has moved to the administrators tab
							// the member type sort is removed and the results are sorted on name as member type is not shown in the results since all
							// are of the administrator type.
							if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "type") {
								$SORT_BY	= "CONCAT_WS(', ', b.`lastname`, b.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
							}

							$SORT_BY = str_replace("CASE c.`group` WHEN 'guest' THEN 1 WHEN '%' THEN 2 END ASC", "a.`member_joined`", $SORT_BY);

							$query		= sprintf($query, $SORT_BY);
							$results	= $db->GetAll($query);
							if ($results) {
								$HEAD[] = "
										<script type=\"text/javascript\">
											jQuery(document).ready(function() {
												jQuery('#adminsTable').dataTable(
													{
														'sPaginationType': 'full_numbers',
														'aoColumns': [
															null,
															null,
															null,
															{'sType': 'alt-string'}
														],
                                                        'bAutoWidth': false
													}
												);
											});
										</script>";
								?>
                                <div class="row-fluid">
                                    <form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("section" => "members", "type" => "admins", "step" => 2)); ?>" method="post" class="form-horizontal">
                                        <table class="table tableList membersTableList" id="adminsTable" style="width:95%" cellspacing="0" cellpadding="2" border="0" summary="Community Administrators">
                                            <colgroup>
                                                <col class="modified" />
                                                <col class="date" />
                                                <col class="title" />
                                                <col class="list-status" />
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <td class="modified">&nbsp;</td>
                                                    <td class="date"><div class="noLink">Member Since</div></td>
                                                    <td class="title"><div class="noLink">Member Name</div></td>
                                                    <td class="list-status"><div class="noLink">Mail List Status</div></td>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2" style="padding-top: 15px">&nbsp;</td>
                                                    <td style="padding-top: 15px; text-align: right" colspan="2">
                                                        <select id="admin_action" name="admin_action" style="vertical-align: middle; width: 200px">
                                                            <option value="">-- Select Admin Action --</option>
                                                            <option value="delete">1. Remove administrators</option>
                                                            <option value="deactivate">2. Deactivate / ban administrators</option>
                                                            <option value="demote">3. Demote to members</option>
                                                        </select>
                                                        <input type="submit" class="btn btn-primary" value="Proceed" style="vertical-align: middle" />
                                                    </td>
                                                </tr>
                                            </tfoot>
                                            <tbody>
                                            <?php
                                            foreach ($results as $result) {
                                                echo "<tr>\n";
                                                echo "	<td><input type=\"checkbox\" name=\"admin_proxy_ids[]\" value=\"".(int) $result["proxy_id"]."\"".(($result["proxy_id"] == $ENTRADA_USER->getActiveId()) ? " onclick=\"this.checked = false\" disabled=\"disabled\"" : "")." /></td>\n";
                                                echo "	<td>".date(DEFAULT_DATE_FORMAT, $result["member_joined"])."</td>\n";
                                                echo "	<td><a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["username"])."\"".(($result["proxy_id"] == $ENTRADA_USER->getActiveId()) ? " style=\"font-weight: bold" : "")."\">".html_encode($result["firstname"]." ".$result["lastname"])."</a></td>\n";
                                                echo "	<td class=\"list-status\"><img src=\"images/".(($MAILING_LISTS["active"]) && $mail_list->users[($result["proxy_id"])]["member_active"] ? "list-status-online.gif\" alt=\"Active\"" : "list-status-offline.gif\" alt=\"Disabled\"")." /></td>\n";
                                                echo "</tr>\n";
                                                $current_members_ids[] = $result["proxy_id"];
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </form>
                                </div><!--/row-fluid-->
							<?php
							} else {
								echo display_notice(array("Your community has no administrators at this time; the MEdTech Unit has been informed of this error, please try again later."));

								application_log("error", "Someone [".$ENTRADA_USER->getID()."] accessed the Manage Members page in a community [".$COMMUNITY_ID."] with no administrators present.");
							}
							?>
	                    </div>
                        <div class="tab-page members">
                            <h3 class="tab">Add Members</h3>
                            <h2 style="margin-top: 0px">Add Members</h2>
                            <form id="add-members-form" action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("section" => "members", "type" => "add", "step" => 2)); ?>" method="post" class="form-horizontal">
                                <div class="row-fluid">
                                    <p>If you would like to add users that already exist in the system to this community yourself, you can do so by clicking the checkbox beside their name from the list below.
                                                            Once you have reviewed the list at the bottom and are ready, click the <strong>Add Members</strong> button at the bottom to complete the process.</p>
                                </div>
                                <div class="row-fluid">
                                    <label for="choose-members-btn" class="control-label form-required"><?php echo $translate->_("Select Members"); ?></label>
                                    <div class="controls">
                                        <button id="choose-members-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Members"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                                    </div>
                                    <script>
                                        var filters = {};
                                        var excluded_target_ids = <?php echo isset($current_members_ids) && $current_members_ids ?  json_encode(array_values(array_unique($current_members_ids))) : 0; ?>;
                                    </script>
                                    <?php
                                    /**
                                     * Check registration requirements for this community.
                                     */
                                    switch ($community_details["community_registration"]) {
                                        case 2 :	// Selected Group Registration
                                            /**
                                             * Generate the filters for the advancedSearch plugin using the groups in $community_members
                                             */
                                            if (($community_details["community_members"] != "") && ($community_members = @unserialize($community_details["community_members"])) && (is_array($community_members)) && (count($community_members))) {
                                                foreach ($community_members as $member_group) {
                                                    if ($member_group != "") {
                                                        $pieces = explode("_", $member_group);
                                                        ?>

                                                        <script>
                                                            var filter = "<?php echo $pieces[0]; ?>";

                                                            if (filter == "student") {
                                                                if (filters[filter]) {
                                                                    filters[filter].api_params.included_group_ids += ",<?php echo $pieces[1]; ?>";
                                                                } else {
                                                                    filters[filter] = {
                                                                        api_params: {
                                                                            group: filter,
                                                                            included_group_ids: "<?php echo $pieces[1]; ?>",
                                                                            excluded_target_ids: excluded_target_ids
                                                                        },
                                                                        label: "<?php echo $translate->_("Students"); ?>",
                                                                        data_source: "group-get-cohorts",
                                                                        secondary_data_source: "group-get-students"
                                                                    }
                                                                }
                                                            } else if (filter == "resident") {
                                                                filters[filter] = {
                                                                    api_params: {
                                                                        group: filter,
                                                                        excluded_target_ids: excluded_target_ids
                                                                    },
                                                                    label: "<?php echo $translate->_("Residents"); ?>",
                                                                    data_source: "get-residents",
                                                                }
                                                            } else {
                                                                filters[filter] = {
                                                                    api_params: {
                                                                        group: filter,
                                                                        excluded_target_ids: excluded_target_ids
                                                                    },
                                                                    label: "<?php echo $translate->_(ucfirst($pieces[0])); ?>",
                                                                    data_source: "get-users-by-group",
                                                                }
                                                            }
                                                        </script>

                                                        <?php
                                                    }
                                                }
                                            }
                                        break;
                                        case 0 :	// Open Community
                                        case 1 :	// Open Registration
                                        case 4 :	// Private Community
                                        default :
                                            /**
                                             * Generate the filters for the advancedSearch plugin to find anyone in the system
                                             */
                                            ?>

                                            <script>
                                                filters = {
                                                    faculty: {
                                                        api_params: {
                                                            group: "faculty",
                                                            excluded_target_ids: excluded_target_ids
                                                        },
                                                        label: "<?php echo $translate->_("Faculty"); ?>",
                                                        data_source: "get-users-by-group"
                                                    },
                                                    medtech: {
                                                        api_params: {
                                                            group: "medtech",
                                                            excluded_target_ids: excluded_target_ids
                                                        },
                                                        label: "<?php echo $translate->_("MEdTech"); ?>",
                                                        data_source: "get-users-by-group"
                                                    },
                                                    resident: {
                                                        api_params: {
                                                            excluded_target_ids: excluded_target_ids
                                                        },
                                                        label: "<?php echo $translate->_("Residents"); ?>",
                                                        data_source: "get-residents"
                                                    },
                                                    staff: {
                                                        api_params: {
                                                            group: "staff",
                                                            excluded_target_ids: excluded_target_ids
                                                        },
                                                        label: "<?php echo $translate->_("Staff"); ?>",
                                                        data_source: "get-users-by-group"
                                                    },
                                                    student: {
                                                        select_all_enabled: true,
                                                        api_params: {
															organisation_id: "<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>",
                                                            excluded_target_ids: excluded_target_ids
                                                        },
                                                        label: "<?php echo $translate->_("Students"); ?>",
                                                        data_source: "get-students",
                                                    }
                                                }
                                            </script>

                                            <?php
                                        break;
                                    }
                                    ?>
                                </div>
                                <div class="row-fluid" style="margin-top: 10px;">
                                    <div class="pull-right" style="position: absolute; right: 10px; bottom: 10px;">
                                        <input type="submit" class="btn btn-primary" value="Add Members"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-page members">
                            <h3 class="tab">Add Guest Members</h3>
                            <h2 style="margin-top: 0px">Add Guest Members</h2>
                            <form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("section" => "members", "type" => "addguest", "step" => 2)); ?>" method="post">
                                <div class="member-add-type" id="guest-member-add-type">
                                    <p>If you aren't able to find the user you wish to add on the Add Members tab, or you would like to add a new user to the system and register them in this community, you can do so by entering their e-mail, first name, and last name below.
                                        Click the <strong>Add Guest</strong> button when you are done and the system will email the new user with their account information.</p>
                                    <table cellspacing="0" cellpadding="2" border="0" summary="Adding Event" style="width: 100%;">
                                        <colgroup>
                                            <col style="width: 20%;"/>
                                            <col style="width: 80%;"/>
                                        </colgroup>
                                        <div id="validation-message"></div>
                                        <tr>
                                            <td><label id="guest_first_text" class="form-required" for="guest_member_first">First Name:</label></td>
                                            <td><input id="guest_member_first" type="text" style="width: 203px;" maxlength="255" value="<?php echo $PROCESSED['firstname'];?>" name="firstname"/></td>
                                        </tr>
                                        <tr>
                                            <td><label id="guest_last_text" class="form-required" for="guest_member_last">Last Name:</label></td>
                                            <td><input id="guest_member_last" type="text" style="width: 203px;" maxlength="255" value="<?php echo $PROCESSED['lastname'];?>" name="lastname"/></td>
                                        </tr>
                                        <tr>
                                            <td><label id="guest_email_text" class="form-required" for="guest_member_email">E-mail Address:</label></td>
                                            <td><input id="guest_member_email" type="text" style="width: 203px;" maxlength="255" value="<?php echo $PROCESSED['email'];?>" name="email"/></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><input type="submit" class="btn btn-primary" value="Add Guest">
                                        </tr>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        jQuery(document).ready(function () {
                            jQuery("#choose-members-btn").advancedSearch({
								api_url: "<?php echo ENTRADA_URL . "/" . $MODULE . "?section=api-members"; ?>",
								build_selected_filters: false,
								reset_api_params: true,
								resource_url: ENTRADA_URL,
								filter_component_label: "Users",
								filters: filters,
                                select_all_enabled : true,
                                target_name : "members",
                                no_results_text: "<?php echo $translate->_("No Users found matching the search criteria"); ?>",
                                parent_form: jQuery("#add-members-form"),
                                list_data: {
                                    selector: "#group_members_list",
                                    background_value: "url(images/list-community.gif) no-repeat scroll 0 4px transparent"
                                },
                                width: 300,
                                async: false
                            });
                        });

                        setupAllTabs(true);
                    </script>
					<?php
                break;
			}
		} else {
			application_log("error", "User tried to update members of a community, but they aren't an administrator of this community.");

			add_error("You do not appear to be an administrator of the community that you are trying to manage the members.<br /><br />If you feel you are getting this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.");

			echo display_error();
		}
	} else {
		application_log("error", "User tried to manage members of a community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

		add_error("The community you are trying to manage members either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.");

		echo display_error();
	}
} else {
	application_log("error", "User tried to manage members a community without providing a community_id.");

	header("Location: ".ENTRADA_URL."/communities");
	exit;
}
?>