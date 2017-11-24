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
 * but WITHOUT ANY WARRANTY; without even the implied warranty ofF
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
} elseif (!$ENTRADA_ACL->amIAllowed('community', 'update', false)) {
    Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance."), "error", $MODULE);
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");

	$url = ENTRADA_URL . "/" . $MODULE;
    header("Location: " . $url);
    exit;
} else {

	if ($MAILING_LISTS["active"]) {
		require_once("Entrada/mail-list/mail-list.class.php");
	}

	$COMMUNITY_ID		= 0;

	/**
	 * Check for a community category to proceed.
	 */
	if ((isset($_GET["community"])) && ((int) trim($_GET["community"]))) {
		$COMMUNITY_ID	= (int) trim($_GET["community"]);
	} elseif ((isset($_POST["community_id"])) && ((int) trim($_POST["community_id"]))) {
		$COMMUNITY_ID	= (int) trim($_POST["community_id"]);
	}

	/**
	 * Ensure that the selected community is editable by you.
	 */
	if ($COMMUNITY_ID) {
		if ($MAILING_LISTS["active"]) {
			$mailing_list = new MailingList($COMMUNITY_ID);
			$list_mode = $mailing_list->type;

			$query = "  SELECT a.*, b.`list_type`
						FROM `communities` AS a
						JOIN `community_mailing_lists` AS b
						ON a.`community_id` = b.`community_id`
						WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND a.`community_active` = '1'";
		} else {
			$query = "  SELECT *
						FROM `communities`
						WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND `community_active` = '1'";
		}
		$community_details	= $db->GetRow($query);
		if ($community_details) {
			$BREADCRUMB[]		= array("url" => ENTRADA_URL."/community".$community_details["community_url"], "title" => limit_chars($community_details["community_title"], 50));
			$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities?".replace_query(array("section" => "modify")), "title" => "Manage Community");
			$community_resource = new CommunityResource($COMMUNITY_ID);
			if ($ENTRADA_ACL->amIAllowed($community_resource, 'update')) {
				$CATEGORY_ID = $community_details["category_id"];
			?>
				<a class="btn space-below" href="<?php echo html_encode(ENTRADA_URL."/community".$community_details["community_url"]); ?>">
						<i class="icon-chevron-left" style="margin: 0"></i> Back To Community
				</a>
			<?php
				echo "<h1>".html_encode($community_details["community_title"])."</h1>\n";

				// Error Checking
				switch ($STEP) {
					case 3 :
					case 2 :
						$PROCESSED["community_members"]	= "";
						$PROCESSED["sub_communities"]	= 0;

						/**
						 * Required: Community Name / community_title
						 */
						if ((isset($_POST["community_title"])) && ($community_title = clean_input($_POST["community_title"], array("notags", "trim")))) {
							$PROCESSED["community_title"] = substr($community_title, 0, 64);
						} else {
							$ERROR++;
							$ERRORSTR[] = "Please provide a title for your new community. Example: Medicine Club";
						}

						/**
						 * Not Required: Community Keywords / community_keywords
						 */
						if ((isset($_POST["community_keywords"])) && ($community_keywords = clean_input($_POST["community_keywords"], array("notags", "trim")))) {
							$PROCESSED["community_keywords"] = substr($community_keywords, 0, 255);
						} else {
							$PROCESSED["community_keywords"] = "";
						}

						/**
						 * Not Required: Community Description / community_description
						 */
						if ((isset($_POST["community_description"])) && ($community_description = clean_input($_POST["community_description"], array("notags", "trim")))) {
							$PROCESSED["community_description"] = $community_description;
						} else {
							$PROCESSED["community_description"] = "";
						}

                        if ((isset($_POST["course_ids"])) && (is_array($_POST["course_ids"])) ) {
                            $PROCESSED["course_ids"] = $_POST["course_ids"];
                        } else {
                            $PROCESSED["course_ids"] = array();
                        }
						
						/**
						 * Not Required: Contact E-Mail / community_email
						if ((isset($_POST["community_email"])) && ($community_email = clean_input($_POST["community_email"], array("trim", "lower")))) {
							if (valid_address($community_email)) {
								$PROCESSED["community_email"] = $community_email;
							} else {
								$ERROR++;
								$ERRORSTR[] = "The e-mail address you provided [".html_encode($community_email)."] is not a valid e-mail address.";
							}
						} else {
							$PROCESSED["community_email"] = "";
						}

						/**
						 * Not Required: External Website / community_website
						if ((isset($_POST["community_website"])) && ($community_website = clean_input($_POST["community_website"], array("trim", "notags", "lower")))) {
							$PROCESSED["community_website"] = $community_website;
						} else {
							$PROCESSED["community_website"] = "";
						}
						*/
						
						/**
						 * Required: Access Permissions / community_protected
						 */
						if (isset($_POST["community_protected"])) {
							if ($community_protected = clean_input($_POST["community_protected"], array("trim", "int")) === 0) {
								$PROCESSED["community_protected"] = 0;
							} else {
								$PROCESSED["community_protected"] = 1;
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must specify the Access Permissions for this new community.";
						}
						
						if (isset($_POST["community_template"])) {
							if ($template_selection = clean_input($_POST["community_template"], array("trim", "int"))) {
								$query = "SELECT * FROM `community_templates` WHERE `template_id` = ". $db->qstr($template_selection);
								$results = $db->GetRow($query);
								if ($results) {
									$PROCESSED["community_template"] = $results["template_name"];
								} else {
                                    $ERROR++;
                                    $ERRORSTR[] = "An invalid Template was provided for this community, please try again.";
                                }
							}		
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must specify the Template for this community.";
						}

						/**
						 * Twitter Settings
						 */
						if ((isset($_POST["community_twitter_handle"])) && ($community_twitter_handle = clean_input($_POST["community_twitter_handle"], array("notags", "trim")))) {
							$PROCESSED["community_twitter_handle"] = $community_twitter_handle;
						} else {
							$PROCESSED["community_twitter_handle"] = "";
						}
						if (isset($_POST["community_twitter_hashtags"])) {
							if ( is_array($_POST["community_twitter_hashtags"]) ) {
								$PROCESSED["community_twitter_hashtags"] = array();
								foreach ($_POST["community_twitter_hashtags"] as $index => $tmp_input) {
									if ($community_twitter_hashtags = clean_input($tmp_input, array("trim", "notags"))) {
										$PROCESSED["community_twitter_hashtags"][] = $community_twitter_hashtags;
									}
								}
								$PROCESSED["community_twitter_hashtags"] = implode(" ", $PROCESSED["community_twitter_hashtags"]);
							} else {
								$PROCESSED["community_twitter_hashtags"] = clean_input($_POST["community_twitter_hashtags"], array("notags", "trim"));	
							}
						} else {
							$PROCESSED["community_twitter_hashtags"] = "";
						}

						/**
						 * Not Required: Sub-Communities / sub_communities
						 */
						if (isset($_POST["sub_communities"])) {
							if ($community_protected = clean_input($_POST["sub_communities"], array("trim", "int")) == 1) {
								$PROCESSED["sub_communities"] = 1;
							}
						}

						/**
						 * Required: Mailing List Mode
						 */
						if ($MAILING_LISTS["active"] && isset($_POST["community_list_mode"])) {
							if (($list_mode = clean_input($_POST["community_list_mode"], array("nows", "lower"))) && $list_mode != $mailing_list->type) {
								$PROCESSED["community_list_mode"] = $list_mode;
							}
						} elseif ($MAILING_LISTS["active"] && !array_key_exists("list_type", $community_details)) {
							$ERROR++;
							$ERRORSTR[] = "You must specify which mode the mailing list for this community is in.";
						}

						/**
						 * Required: Registration Options / community_registration
						 */
						if (isset($_POST["community_registration"])) {
							switch (clean_input($_POST["community_registration"], array("trim", "int"))) {
								case 0 :	// Open Community
									$PROCESSED["community_registration"]	= 0;
									break;
								case 2 :	// Group Registration
									$PROCESSED["community_registration"]	= 2;


									if ((isset($_POST["community_registration_groups"])) && (is_array($_POST["community_registration_groups"])) && (count($_POST["community_registration_groups"]))) {
										$community_groups = array();

										foreach ($_POST["community_registration_groups"] as $community_group) {
											if (($community_group = clean_input($community_group, "credentials")) && (array_key_exists($community_group, $GROUP_TARGETS))) {
												$community_groups[] = $community_group;
											}
										}

										if (count($community_groups)) {
											$PROCESSED["community_members"] = serialize($community_groups);
										} else {
											$ERROR++;
											$ERRORSTR[] = "You have selected Group Registration under Registration Options, but have not chosen any Groups that are able to register. Please select at least one Group to continue.";

											application_log("error", "User selected Group Registration option, did provide groups, none of which could be validated.");
										}
									} else {
										$ERROR++;
										$ERRORSTR[] = "You have selected Group Registration under Registration Options, but have not chosen any Groups that are able to register. Please select at least one Group to continue.";
									}
									break;
								case 3 :	// Community Registration
									$PROCESSED["community_registration"]	= 3;

									if ((isset($_POST["community_registration_communities"])) && (is_array($_POST["community_registration_communities"])) && (count($_POST["community_registration_communities"]))) {

										$community_communities = array();

										foreach ($_POST["community_registration_communities"] as $community_id) {
											if ($community_id = (int) trim($community_id)) {
												$query = "  SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($community_id)." AND `community_active` = '1'";
												$result	= $db->GetRow($query);
												if ($result) {
													$community_communities[] = $community_id;
												}
											}
										}

										if (count($community_communities)) {
											$PROCESSED["community_members"] = serialize($community_communities);
										} else {
											$ERROR++;
											$ERRORSTR[] = "You have selected Community Registration under Registration Options, but have not chosen any Communites which can register. Please select at least one existing Community to continue.";

											application_log("error", "User selected Community Registration, did provide community_ids, none of which existed.");
										}
									} else {
										$ERROR++;
										$ERRORSTR[] = "You have selected Community Registration under Registration Options, but have not chosen any Communites which can register. Please select at least one existing Community to continue.";
									}
									break;
								case 4 :	// Private Community
									$PROCESSED["community_registration"]	= 4;
									break;
								case 1 :	// Open Registration
								default :
									$PROCESSED["community_registration"]	= 1;
									break;
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must specify the Registration Options for this new community.";
						}

						if ($ERROR) {
							$PROCESSED	= $_POST;
							$STEP		= 1;
						}
						break;
					case 1 :
					default :
						$PROCESSED						= $community_details;
                        $query = "SELECT `community_type_options` FROM `org_community_types`
                                    WHERE `octype_id` = ".$db->qstr($community_details["octype_id"]);
                        $type_options_serialized = $db->GetOne($query);
                        if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
                            foreach ($type_options as $type_option => $active) {
                                if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                                    $PROCESSED["course_ids"] = array();
                                    $query = "SELECT `course_id` FROM `community_courses` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
                                    $courses = $db->GetAll($query);
                                    foreach ($courses as $course) {
                                        $PROCESSED["course_ids"][] = $course["course_id"];
                                    }
                                }
                            }
                        }
						$COMMUNITY_PARENT				= $community_details["community_parent"];
						$community_groups				= array();
						$community_communities			= array();

						$community_template = $PROCESSED["community_template"];
						$query = "SELECT * FROM `community_templates` WHERE `template_name` =".$db->qstr($community_template);
						$results = $db->GetRow($query);
						if ($results) {
							$template_selection = $results["template_id"];
						}

						if (($community_details["community_registration"] == 2) && ($community_details["community_members"])) {
							$community_groups = @unserialize($community_details["community_members"]);
						}

						if (($community_details["community_registration"] == 3) && ($community_details["community_members"])) {
							$community_communities = @unserialize($community_details["community_members"]);
						}
					break;
				}

				// Display Content
				switch ($STEP) {
					case 3 :
						$community_url = ENTRADA_URL . "/community" . $community_details["community_url"];

						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

						if ($_POST["community_template"] && $tmp_input = clean_input($_POST["community_template"], array("trim", "striptags"))) {
							$PROCESSED["community_template"] = $tmp_input;
						}

						if ($db->AutoExecute("communities", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID))) {
                            
                            $query = "SELECT `community_type_options` FROM `org_community_types`
                                        WHERE `octype_id` = ".$db->qstr($community_details["octype_id"]);
                            $type_options_serialized = $db->GetOne($query);
                            if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
                                foreach ($type_options as $type_option => $active) {
                                    if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                                        $query = "SELECT b.`course_id`, b.`organisation_id` FROM `community_courses` AS a
                                                    JOIN `courses` AS b
                                                    ON a.`course_id` = b.`course_id`
                                                    WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID);
                                        $courses = $db->GetAll($query);
                                        if ($courses) {
                                            foreach ($courses as $course) {
                                                if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), "update")) {
                                                    $query = "DELETE FROM `community_courses` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `course_id` = ".$db->qstr($course["course_id"]);
                                                    if (!$db->Execute($query)) {
                                                        application_log("error", "Unable to remove `community_courses` record from the database. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }
                                        if (isset($PROCESSED["course_ids"]) && $PROCESSED["course_ids"]) {
                                            foreach ($PROCESSED["course_ids"] as $course_id) {
                                                $query = "SELECT * FROM `courses` 
                                                            WHERE `course_id` = ".$db->qstr($course_id)."
                                                            AND `course_active` = 1
                                                            AND `course_id` NOT IN (
                                                                SELECT `course_id` FROM `community_courses` WHERE `course_id` = ".$db->qstr($course_id)."
                                                            )";
                                                $course = $db->GetRow($query);
                                                if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), "update")) {
                                                    if (!$db->AutoExecute("community_courses", array("community_id" => $COMMUNITY_ID, "course_id" => $course["course_id"]), "INSERT")) {
                                                        $ERROR++;
                                                        $ERRORSTR[] = "An issue was encountered while attempting to attach a course to this community.";
                                                        application_log("error", "Could not connect a course [".$course["course_id"]."] to an existing community [".$community_id."]. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
							if ($MAILING_LISTS["active"] && array_key_exists("community_list_mode", $PROCESSED) && $PROCESSED["community_list_mode"] != $mailing_list->type) {
								$mailing_list->mode_change($PROCESSED["community_list_mode"]);
							}
							$SUCCESS++;
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["community_title"]), "success", "login");
							communities_log_history($COMMUNITY_ID, 0, $ENTRADA_USER->getID(), "community_history_edit_community", 1);

							if ($community_details["community_title"] != $PROCESSED["community_title"]) {
								communities_log_history($COMMUNITY_ID, 0, 0, "community_history_rename_community", 1);
							}

                            application_log("success", "Community ID ".$community_id." was successfully updated.");

							header("Location: " . $community_url);
                            exit;
						} else {
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("We encountered a problem while updating <strong>%s</strong>.<br /><br />The system administrator has been informed of this problem, please try again later."), $PROCESSED["community_title"]), "error", $MODULE);
                            application_log("error", "Failed to create new community. Database said: ".$db->ErrorMsg());

                            $url = ENTRADA_URL . "/" . "communities";
                            header("Location: " . $url);
                            exit;
                        }
						break;
					case 2 :
						?>
						<div class="display-notice" style="line-height: 175%">
							<strong>Please review</strong> the following changes that will be made to your community once you press the &quot;Save Changes&quot; button at the bottom of the screen. If you have made a mistake, please press the &quot;Cancel&quot; button, <strong>not</strong> your browsers back button.
						</div>
						<form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("action" => "modify", "step" => 3)); ?>" method="post">
							<?php

							if (isset($community_groups) && @count($community_groups)) {
								foreach ($community_groups as $key => $value) {
									echo "<input type=\"hidden\" name=\"community_registration_groups[]\" value=\"".html_encode($value)."\" />\n";
								}
							}

							if (isset($community_communities) && @count($community_communities)) {
								foreach ($community_communities as $key => $value) {
									echo "<input type=\"hidden\" name=\"community_registration_communities[]\" value=\"".html_encode($value)."\" />\n";
								}
							}

							if (is_array($PROCESSED)) {
								foreach ($PROCESSED as $key => $value) {
									if (is_array($value)) {
										foreach ($value as $skey => $svalue) {
											echo "<input type=\"hidden\" name=\"".html_encode($key."[".$skey."]")."\" value=\"".html_encode($svalue)."\" />\n";
										}
									} else {
										echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
									}
								}
							}
							?>
							<table style="width: 100%" cellspacing="0" cellpadding="4" border="0" summary="Review of pending community updates.">
								<colgroup>
									<col style="width: 3%" />
									<col style="width: 20%" />
									<col style="width: 77%" />
								</colgroup>
								<tfoot>
									<tr>
										<td colspan="3" style="padding-top: 15px">
											<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
												<tr>
													<td style="width: 25%; text-align: left">
														<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("action" => "modify", "step" => 1)); ?>'" />
													</td>
													<td style="width: 75%; text-align: right; vertical-align: middle">
														<input type="submit" class="btn btn-primary" value="Save Changes" />
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</tfoot>
								<tbody>
								<?php
								if ($PROCESSED["community_title"] != $community_details["community_title"]) {
									?>
									<tr>
										<td><?php echo help_create_button("Community Name", "communities-community_title"); ?></td>
										<td><span class="form-required">Community Name</span></td>
										<td><?php echo html_encode($PROCESSED["community_title"]); ?></td>
									</tr>
                                    <?php
								}
								if ($PROCESSED["community_keywords"] != $community_details["community_keywords"]) {
									?>
									<tr>
										<td><?php echo help_create_button("Community Keywords", "communities-community_keywords"); ?></td>
										<td><span class="form-nrequired">Community Keywords</span></td>
										<td><?php echo html_encode($PROCESSED["community_keywords"]); ?></td>
									</tr>
                                    <?php
								}
                                $historical_course_ids = array();
                                $query = "SELECT `community_type_options` FROM `org_community_types`
                                            WHERE `octype_id` = ".$db->qstr($community_details["octype_id"]);
                                $type_options_serialized = $db->GetOne($query);
                                if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
                                    foreach ($type_options as $type_option => $active) {
                                        if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                                            $query = "SELECT b.`course_id`, b.`organisation_id` FROM `community_courses` AS a
                                                        JOIN `courses` AS b
                                                        ON a.`course_id` = b.`course_id`
                                                        WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID);
                                            $courses = $db->GetAll($query);
                                            if ($courses) {
                                                foreach ($courses as $course) {
                                                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), "update")) {
                                                        $historical_course_ids[] = $course["course_id"];
                                                    }
                                                }
                                            }
                                            $added_course_ids = array_diff($PROCESSED["course_ids"], $historical_course_ids);
                                            $removed_course_ids = array_diff($historical_course_ids, $PROCESSED["course_ids"]);
                                            if (@count($added_course_ids) || @count($removed_course_ids)) {
                                                ?>
                                                <tr>
                                                    <td style="vertical-align: top">&nbsp;</td>
                                                    <td style="vertical-align: top"><span class="form-nrequired">Community Courses</span></td>
                                                    <td>
                                                    <?php
                                                        if (@count($added_course_ids)) {
                                                            echo "<div>\n";
                                                            echo "  <div>The following courses will be added: </div>\n";
                                                            echo "  <ul>\n";
                                                            foreach ($added_course_ids as $added_course_id) {
                                                                $query = "SELECT `course_name`, `course_code` FROM `courses` WHERE `course_id` = ".$db->qstr($added_course_id);
                                                                $course = $db->GetRow($query);
                                                                if ($course) {
                                                                    echo "<li>".html_encode((isset($course["course_code"]) && $course["course_code"] ? $course["course_code"]." - " : "").$course["course_name"])."</li>\n";
                                                                }
                                                            }
                                                            echo "  </ul>\n";
                                                            echo "</div>\n";
                                                        }
                                                        if (@count($removed_course_ids)) {
                                                            echo "<div>\n";
                                                            echo "  <div>The following courses will be removed: </div>\n";
                                                            echo "  <ul>\n";
                                                            foreach ($removed_course_ids as $removed_course_ids) {
                                                                $query = "SELECT `course_name`, `course_code` FROM `courses` WHERE `course_id` = ".$db->qstr($removed_course_ids);
                                                                $course = $db->GetRow($query);
                                                                if ($course) {
                                                                    echo "<li>".html_encode((isset($course["course_code"]) && $course["course_code"] ? $course["course_code"]." - " : "").$course["course_name"])."</li>\n";
                                                                }
                                                            }
                                                            echo "  </ul>\n";
                                                            echo "</div>\n";
                                                        }
                                                    ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }
                                }
								if ($PROCESSED["community_description"] != $community_details["community_description"]) {
									?>
									<tr>
										<td style="vertical-align: top"><?php echo help_create_button("Community Description", "communities-community_description"); ?></td>
										<td style="vertical-align: top"><span class="form-nrequired">Community Description</span></td>
										<td><?php echo nl2br(html_encode($PROCESSED["community_description"])); ?></td>
									</tr>
								<?php
								}
								/*
								if ($PROCESSED["community_email"] != $community_details["community_email"]) {
								?>
									<tr>
										<td><?php echo help_create_button("Contact E-Mail Address", "communities-community_email"); ?></td>
										<td><span class="form-nrequired">Contact E-Mail</span></td>
										<td><?php echo (($PROCESSED["community_email"]) ? "<a href=\"mailto:".html_encode($PROCESSED["community_email"])."\">".html_encode($PROCESSED["community_email"])."</a>" : ""); ?></td>
									</tr>
								<?php
								}
								if ($PROCESSED["community_website"] != $community_details["community_website"]) {
								?>
									<tr>
										<td><?php echo help_create_button("External Website", "communities-community_website"); ?></td>
										<td><span class="form-nrequired">External Website</span></td>
										<td><?php echo (($PROCESSED["community_website"]) ? "<a href=\"".html_encode($PROCESSED["community_website"])."\" target=\"_blank\">".html_encode($PROCESSED["community_website"])."</a>" : ""); ?></td>
									</tr>
								<?php
								}
								*/

								if ($PROCESSED["community_twitter_handle"] != $community_details["community_twitter_handle"]) {
									?>
									<tr>
										<td>&nbsp;</td>
										<td><span class="form-nrequired">Community Twitter Handle</span></td>
										<td><?php echo html_encode($PROCESSED["community_twitter_handle"]); ?></td>
									</tr>
									<?php
								}

								if ($PROCESSED["community_twitter_hashtags"] != $community_details["community_twitter_hashtags"]) {
									?>
									<tr>
										<td>&nbsp;</td>
										<td><span class="form-nrequired">Community Twitter Hashtags</span></td>
										<td><?php echo html_encode($PROCESSED["community_twitter_hashtags"]); ?></td>
									</tr>
									<?php
								}

								if ($PROCESSED["sub_communities"] != $community_details["sub_communities"]) {
									?>
									<tr>
										<td><?php echo help_create_button("Sub-Communities", "communities-sub_communities"); ?></td>
										<td><span class="form-nrequired">Sub-Communities</span></td>
										<td><?php echo (($PROCESSED["sub_communities"] == 1) ? " On" : " Off"); ?></td>
									</tr>
									<?php
									if (!(int) $PROCESSED["sub_communities"]) {
										$query	= "SELECT COUNT(*) AS `total_sub_communities` FROM `communities` WHERE `community_parent` = ".$db->qstr($COMMUNITY_ID)." AND `community_active` = '1'";
										$result	= $db->GetRow($query);
										if (($result) && ($total_sub_communities = (int) $result["total_sub_communities"])) {
											?>
											<tr>
												<td colspan="2">
												<td>
													<div class="display-notice" style="line-height: 175%">
														<strong>Please note</strong> that <?php echo (($total_sub_communities != 1) ? "there are ".$total_sub_communities." sub-communities / groups that exist" : "there is 1 sub-community / group that exists"); ?> under this community.<br /><br />If you proceed with turning off sub-community support, then <?php echo (($total_sub_communities != 1) ? "these communities" : "this community"); ?> will be deactivated and removed from the system completely.
													</div>
												</td>
											</tr>
											<?php
										}
									}
								}

								if ($MAILING_LISTS["active"] && isset($PROCESSED["community_list_mode"]) && $PROCESSED["community_list_mode"] != $mailing_list->type) {
									?>
									<tr>
										<td style="vertical-align: top"><?php echo help_create_button("Mailing List", "communities-community_mailing_list"); ?></td>
										<td style="vertical-align: top"><span class="form-nrequired">Mailing List</span></td>
										<td style="vertical-align: top">
										<?php
										if (($PROCESSED["community_list_mode"] == "announcements" || $PROCESSED["community_list_mode"] == "discussion") && ($mailing_list->type == "inactive")) {
											echo "<img src=\"" . ENTRADA_URL ."/images/list-success.gif\" />&nbsp Mailing List - will be activated.<br>";
											echo "<div style=\"margin-left: 20px;\"><img src=\"" . ENTRADA_URL ."/images/list-success.gif\" />&nbsp Members will be added within the next 30 minutes (Status can be viewed in the Manage Users section of the community).<br></div>";
										} elseif ($PROCESSED["community_list_mode"] == "inactive") {
											echo "<img src=\"" . ENTRADA_URL ."/images/list-success.gif\" />&nbsp Mailing List - will be deactivated.<br>";
										} else {
											echo "<img src=\"" . ENTRADA_URL ."/images/list-success.gif\" />&nbsp Mailing List - will be changed to '".$PROCESSED["community_list_mode"]."' mode.<br>";
										}
											?>
										</td>
									</tr>
									<?php
								}

								if ($PROCESSED["community_protected"] != $community_details["community_protected"]) {
										?>
									<tr>
										<td><?php echo help_create_button("Access Permissions", "communities-community_protected"); ?></td>
										<td><span class="form-nrequired">Access Permissions</span></td>
										<td><?php echo (($PROCESSED["community_protected"] == 1) ? "Protected" : "Public"); ?> Community</td>
									</tr>
									<?php
								}

								if ($PROCESSED["community_template"] != $community_details["community_template"]) {
										?>
									<tr>
										<td><?php echo help_create_button("Community Template", "community_template"); ?></td>
										<td><span class="form-nrequired">Community Template</span></td>
										<td><?php echo ucfirst($PROCESSED["community_template"]); ?> Template</td>
									</tr>
									<?php
								}

								if (($PROCESSED["community_registration"] != $community_details["community_registration"]) || ($PROCESSED["community_members"] != $community_details["community_members"])) {
										?>
									<tr>
										<td style="vertical-align: top"><?php echo help_create_button("Registration Options", "communities-community_registration"); ?></td>
										<td style="vertical-align: top"><span class="form-nrequired">Registration Options</span></td>
										<td style="vertical-align: top">
										<?php
										switch ($PROCESSED["community_registration"]) {
											case 0 :
												echo "Open Community";
												break;
											case 1 :
												echo "Open Registration";
												break;
											case 2 :
												echo "Group Registration";
												if ($PROCESSED["community_members"] != $community_details["community_members"]) {
													echo "<ol>\n";
													foreach ($community_groups as $community_group) {
														echo "<li>".html_encode($GROUP_TARGETS[$community_group])."</li>\n";
													}
													echo "</ol>\n";
												}
												break;
											case 3 :
												echo "Community Registration";
												if ($PROCESSED["community_members"] != $community_details["community_members"]) {
													echo "<ol>\n";
													foreach ($community_communities as $community_id) {
														echo "<li>".communities_title((int) $community_id)."</li>\n";
													}
													echo "</ol>\n";
												}

												break;
											case 4 :
												echo "Private Community";
												break;
											default :
												echo "Unknown Registration Pption";

												application_log("error", "An unknown community_registration option was encountered");
												break;
											}
											?>
										</td>
									</tr>
								<?php
									}
									?>
								</tbody>
							</table>
						</form>
						<?php
						break;
					case 1 :
					default :
						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/progressbar.js?release=".APPLICATION_VERSION."\"></script>";
                        $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
                        $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\"></script>\n";
						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/Twitter.js\"></script>";
                        $ONLOAD[] = "jQuery('.chosen-select').chosen({no_results_text: 'No courses found matching'})";

						/**
						 * Information used for the community statistics tab.
						 */
						$community_stats							= array();
						$community_stats["members_total"]			= 0;
						$community_stats["members_last_31_days"]	= 0;
						$community_stats["members_admins"]			= 0;
						$community_stats["history_last_31_days"]	= 0;
						$community_stats["total_sub_communities"]	= 0;

						$query = "SELECT COUNT(*) AS `members_total` FROM `community_members` WHERE `community_id` = ".$db->qstr($community_details["community_id"])." AND `member_active` = '1'";
						$result	= $db->GetRow($query);
						if ($result) {
							$community_stats["members_total"] = $result["members_total"];
						}

						$query = "SELECT COUNT(*) AS `members_last_31_days` FROM `community_members` WHERE `community_id` = ".$db->qstr($community_details["community_id"])." AND `member_active` = '1' AND `member_joined` >= ".$db->qstr(strtotime("-31 days"));
						$result	= $db->GetRow($query);
						if ($result) {
							$community_stats["members_last_31_days"] = $result["members_last_31_days"];
						}

						$query = "SELECT COUNT(*) AS `members_admins` FROM `community_members` WHERE `community_id` = ".$db->qstr($community_details["community_id"])." AND `member_active` = '1' AND `member_acl` = '1'";
						$result = $db->GetRow($query);
						if ($result) {
							$community_stats["members_admins"] = $result["members_admins"];
						}

						$query = "SELECT COUNT(*) AS `history_last_31_days` FROM `community_history` WHERE `community_id` = ".$db->qstr($community_details["community_id"])." AND `history_timestamp` >= ".$db->qstr(strtotime("-31 days"));
						$result = $db->GetRow($query);
						if ($result) {
							$community_stats["history_last_31_days"] = $result["history_last_31_days"];
						}

						$query = "SELECT COUNT(*) AS `total_sub_communities` FROM `communities` WHERE `community_parent` = ".$db->qstr($community_details["community_id"])." AND `community_active` = '1'";
						$result	= $db->GetRow($query);
						if ($result) {
							$community_stats["total_sub_communities"] = $result["total_sub_communities"];
						}

						/**
						 * Onload information for setting the registration options properly.
						 */
						if ((!isset($PROCESSED["community_registration"])) || (!(int) $PROCESSED["community_registration"])) {
							$ONLOAD[] = "selectRegistrationOption('0')";
						} else {
							$ONLOAD[] = "selectRegistrationOption('".(int) $PROCESSED["community_registration"]."')";
						}

						if ($COMMUNITY_PARENT) {
							$fetched = array();
							communities_fetch_parents($COMMUNITY_PARENT, $fetched);

							if ((is_array($fetched)) && (count($fetched))) {
								$community_parents	= array_reverse($fetched);
							} else {
								$community_parents	= false;
							}
							unset($fetched);
						}

						if ($NOTICE) {
							echo display_notice();
						}
						if ($ERROR) {
							echo display_error();
						}
                        ?>
						<form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("action" => "modify", "step" => 2)); ?>" method="post">
							<div class="tab-pane" id="community-modify-tabs">
								<div class="tab-page">
									<h3 class="tab">Statistics</h3>
									<h2 style="margin-top: 0px">Community Statistics</h2>
									<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Basic Community Statistics">
										<colgroup>
											<col style="width: 3%" />
											<col style="width: 30%" />
											<col style="width: 67%" />
										</colgroup>
										<?php
										if ((int) $community_details["storage_max"]) {
											$percent = ceil((($community_details["storage_usage"] / $community_details["storage_max"]) * 100));

											/**
											 * Interface display options.
											 */
											if ($percent >= 100) {
												$usage	= " over";
											} elseif ($percent >= 85) {
												$usage	= " warning";
											} else {
												$usage	= "";
											}
											?>
											<tr>
												<td><?php echo help_create_button("Community Quota Usage", "community-quota_usage"); ?></td>
												<td><span class="form-nrequired">Community Quota Usage</span></td>
												<td>
													<div id="community-usage" class="usage-container<?php echo $usage; ?>"></div>
													<script type="text/javascript">
														new Control.ProgressBar('community-usage').setProgress('<?php echo (($percent > 100) ? 100 : $percent); ?>');
													</script>
												</td>
											</tr>
											<tr>
												<td colspan="2">&nbsp;</td>
												<td><span class="content-small"><strong>Usage:</strong> <?php echo $percent; ?>% or <?php echo readable_size($community_details["storage_usage"])." of ".readable_size($community_details["storage_max"]); ?></span></td>
											</tr>
											<tr>
												<td colspan="3">&nbsp;</td>
											</tr>
											<?php
										}
										?>
										<tr>
											<td><?php echo help_create_button("Community Administrators", "community-community_admistrators"); ?></td>
											<td><span class="form-nrequired">Community Administrators</span></td>
											<td><?php echo $community_stats["members_admins"]." member".(($community_stats["members_admins"] != 1) ? "s" : ""); ?></td>
										</tr>
										<tr>
											<td><?php echo help_create_button("Total Members", "community-total_members"); ?></td>
											<td><span class="form-nrequired">Total Members</span></td>
											<td><?php echo $community_stats["members_total"]." member".(($community_stats["members_total"] != 1) ? "s" : ""); ?></td>
										</tr>
										<tr>
											<td><?php echo help_create_button("New Members", "community-new_members"); ?></td>
											<td><span class="form-nrequired">New Members <small>(previous 31 days)</small></span></td>
											<td><?php echo $community_stats["members_last_31_days"]." member".(($community_stats["members_last_31_days"] != 1) ? "s" : ""); ?></td>
										</tr>
										<tr>
											<td colspan="3">&nbsp;</td>
										</tr>
										<tr>
											<td><?php echo help_create_button("Updates Last Month", "community-total_updates"); ?></td>
											<td><span class="form-nrequired">Activity Points: <small>(previous 31 days)</small></span></td>
											<td><?php echo $community_stats["history_last_31_days"]." update".(($community_stats["history_last_31_days"] != 1) ? "s" : ""); ?></td>
										</tr>
										<tr>
											<td colspan="3">&nbsp;</td>
										</tr>
										<!--
										<tr>
											<td><?php echo help_create_button("Total Sub-Communities / Groups", "community-total_sub_communities"); ?></td>
											<td><span class="form-nrequired">Total Sub-Communities / Groups</span></td>
											<td><?php echo $community_stats["total_sub_communities"]." ".(($community_stats["total_sub_communities"] != 1) ? "sub-communities / groups" : "sub-community / group"); ?></td>
										</tr>
										<tr>
											<td colspan="3">&nbsp;</td>
										</tr>
										-->
									</table>
								</div>
								<div class="tab-page">
									<script type="text/javascript">
                                        function show_default_large() {
                                            jQuery(".default-large").dialog({ 
                                                 width: 792 , 
                                                 height: 720,
                                                 position: 'center',
                                                 draggable: false,
                                                 resizable: false,
                                                 modal : true, 
                                                 show: 'fade',
                                                 hide: 'fade',
                                                 title: 'Default Template',
                                                 buttons: {
                                                'Select': function() {
                                                   jQuery('#template_option_1').attr('checked', 'checked');
                                                   jQuery(this).dialog('close');
                                                },
                                                'Close': function() {
                                                   jQuery(this).dialog('close');
                                                }
                                              }
                                            });
                                        }

                                        function show_committee_large() {
                                            jQuery(".committee-large").dialog({
                                                 width: 792 , 
                                                 height: 720,
                                                 position: 'center',
                                                 draggable: false,
                                                 resizable: false,
                                                 modal : true,
                                                 show: 'fade',
                                                 hide: 'fade',
                                                 title: 'Committee Template',
                                                 buttons: {
                                                'Select': function() {
                                                   jQuery('#template_option_2').attr('checked', 'checked');
                                                   jQuery(this).dialog('close');
                                                },
                                                'Close': function() {
                                                   jQuery(this).dialog('close');
                                                }
                                              }
                                            });
                                        }

                                        function show_virtualpatient_large() {
                                            jQuery(".virtualpatient-large").dialog({
                                                 width: 792 , 
                                                 height: 720,
                                                 position: 'center',
                                                 draggable: false,
                                                 resizable: false,
                                                 modal : true,
                                                 show: 'fade',
                                                 hide: 'fade',
                                                 title: 'Virtual Patient Template',
                                                 buttons: {
                                                'Select': function() {
                                                   jQuery('#template_option_3').attr('checked', 'checked');
                                                   jQuery(this).dialog('close');
                                                },
                                                'Close': function() {
                                                   jQuery(this).dialog('close');
                                                }
                                              }
                                            });
                                        }

                                        function show_learningmodule_large() {
                                            jQuery(".learningmodule-large").dialog({
                                                 width: 792, 
                                                 height: 720,
                                                 position: 'center',
                                                 draggable: false,
                                                 resizable: false,
                                                 modal : true,
                                                 show: 'fade',
                                                 hide: 'fade',
                                                 title: 'Learning Module Template',
                                                 buttons: {
                                                'Select': function() {
                                                   jQuery('#template_option_4').attr('checked', 'checked');
                                                   jQuery(this).dialog('close');
                                                },
                                                'Close': function() {
                                                   jQuery(this).dialog('close');
                                                }
                                              }
                                            });
                                        }

                                        function show_course_large() {
                                            jQuery(".course-large").dialog({
                                                 width: 792, 
                                                 height: 908,
                                                 position: 'center',
                                                 draggable: false,
                                                 resizable: false,
                                                 modal : true,
                                                 show: 'fade',
                                                 hide: 'fade',
                                                 title: 'Course Template',
                                                 buttons: {
                                                'Select': function() {
                                                   jQuery('#template_option_5').attr('checked', 'checked');
                                                   jQuery(this).dialog('close');
                                                },
                                                'Close': function() {
                                                   jQuery(this).dialog('close');
                                                }
                                              }
                                            });
                                        }
										
									</script>
									<h3 class="tab">Details</h3>
									<h2 style="margin-top: 0px">Community Details</h2>
									<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Modifying Community Details">
										<colgroup>
											<col style="width: 3%" />
											<col style="width: 20%" />
											<col style="width: 77%" />
										</colgroup>
										<tbody>
											<tr>
												<td>&nbsp;</td>
												<td><span class="form-nrequired">Community Shortname</span></td>
												<td><?php echo html_encode($community_details["community_shortname"]); ?></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td><span class="form-nrequired">Community URL</span></td>
												<td><a href="<?php echo html_encode(ENTRADA_URL."/community".$community_details["community_url"]); ?>" style="font-size: 10px" target="_blank"><?php echo html_encode(ENTRADA_URL."/community".$community_details["community_url"]); ?></a></td>
											</tr>
											<tr>
												<td colspan="3">&nbsp;</td>
											</tr>
											<?php
											if (isset($community_parents) && (is_array($community_parents)) && ($total_parents = count($community_parents))) {
												?>
												<tr>
													<td></td>
													<td><span class="form-nrequired">Community Path</span></td>
													<td>
													<?php
													$i = 0;
													foreach ($community_parents as $result) {
														$i++;
														echo html_encode($result["community_title"])." / ";
													}
													echo "<em>".html_encode($PROCESSED["community_title"])."</em>";
													?>
													</td>
												</tr>
												<?php
												}
												?>
											<tr>
												<td><?php echo help_create_button("Community Name", ""); ?></td>
												<td><label for="community_title" class="form-required">Community Name</label></td>
												<td>
													<input type="text" id="community_title" name="community_title" value="<?php echo html_encode($PROCESSED["community_title"]); ?>" maxlength="64" style="width: 250px" />
													<span class="content-small">(<strong>Example:</strong> Medicine Club)</span>
												</td>
											</tr>
											<tr>
												<td><?php echo help_create_button("Community Keywords", ""); ?></td>
												<td><label for="community_keywords" class="form-nrequired">Community Keywords</label></td>
												<td>
													<input type="text" id="community_keywords" name="community_keywords" value="<?php echo html_encode($PROCESSED["community_keywords"]); ?>" maxlength="255" style="width: 500px" />
												</td>
											</tr>
                                            <?php
                                            $query = "SELECT `community_type_options` FROM `org_community_types`
                                                        WHERE `octype_id` = ".$db->qstr($community_details["octype_id"]);
                                            $type_options_serialized = $db->GetOne($query);
                                            if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
                                            ?>
                                            <tr>
												<td style="padding-top:6px; vertical-align: top">&nbsp;</td>
												<td style="padding-top:6px; vertical-align: top"><label for="community_template" class="form-nrequired">Select course(s)</label></td>
                                                <td>
                                                <?php
                                                    foreach ($type_options as $type_option => $active) {
                                                        if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                                                            $query = "SELECT `course_id`, `course_code`, `course_name` FROM `courses` 
                                                                        WHERE `course_active` = 1
                                                                        AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                                        AND `course_id` NOT IN (
                                                                            SELECT `course_id` FROM `community_courses` WHERE `community_id` != ".$db->qstr($COMMUNITY_ID)."
                                                                        )";
                                                            $courses = $db->GetAll($query);
                                                            if ($courses) {
                                                                echo "<select multiple=\"multiple\" name=\"course_ids[]\" id=\"course_ids\" class=\"chosen-select\">";
                                                                foreach ($courses as $course) {
                                                                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), "update")) {
                                                                        echo "<option value=\"".((int)$course["course_id"])."\"".(in_array($course["course_id"], $PROCESSED["course_ids"]) ? " selected=\"selected\"" : "").">".html_encode(($course["course_code"] ? $course["course_code"]." - " : "").$course["course_name"])."</option>";
                                                                    }
                                                                }
                                                                echo "</select>";
                                                            }
                                                        }
                                                    }
                                                ?>
                                                </td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
											<tr>
												<td style="vertical-align: top"><?php echo help_create_button("Community Description", ""); ?></td>
												<td style="vertical-align: top"><label for="community_description" class="form-nrequired">Community Description</label></td>
												<td><textarea id="community_description" name="community_description" style="width: 500px; height: 75px"><?php echo html_encode($PROCESSED["community_description"]); ?></textarea></td>
											</tr>
											<tr>
												<td style="padding-top:6px; vertical-align: top"><?php echo help_create_button("Community Template", ""); ?></td>
												<td style="padding-top:6px; vertical-align: top"><label for="community_template" class="form-nrequired">Community Template</label></td>
												<td style="vertical-align: top">
												<div>
												<?php
													$admin_query = "SELECT `cmember_id`, `community_id`, `proxy_id`, `member_active`, `member_acl` FROM `community_members` WHERE `community_id` = ".$db->qstr($community_details["community_id"])." AND `member_active` = '1' AND `member_acl` = '1' ORDER BY `cmember_id`";
													$admin = $db->GetRow($admin_query);
													if ($admin) {
														$query = "SELECT `user_id`, `group` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id`= ".$db->qstr($admin["proxy_id"]);
														$admin_group = $db->GetRow($query);
														if ($admin_group) {
															$creator_group = $admin_group["group"];
														}
													}
													$query = "SELECT a.* FROM `community_templates` AS a
																JOIN `community_type_templates` AS b
																ON a.`template_id` = b.`template_id`
																WHERE b.`type_id` = ".$db->qstr($community_details["octype_id"])."
																AND b.`type_scope` = 'organisation'"; 
													$results = $db->GetAll($query);
													if ($results) {
                                                        ?>
														<ul class="community-themes">
														<?php
														$default_templates = array();
														$groups = array();
														$category = array();
														$default_categories = array();
														$default_groups = array();
														$large_template_images = "";
														foreach($results as $community_template) {
                                                            ?>
                                                            <li id="<?php echo $community_template["template_name"]."-template"; ?>" style="background: url('images/<?php echo $community_template["template_name"]; ?>-thumb.jpg')">
                                                                <div class="template-rdo">
                                                                    <input type="radio" id="<?php echo "template_option_".$community_template["template_id"] ?>" name="community_template" value="<?php echo $community_template["template_id"]; ?>"<?php echo ((($template_selection == 0) && ($community_template["template_id"] == 1) || ($template_selection == $community_template["template_id"])) ? " checked=\"checked\"" : ""); ?> />
                                                                </div>
                                                                <div class="large-view">
                                                                    <a href="#" onclick="show_<?php echo $community_template["template_name"]; ?>_large()" class="<?php echo "large-view-".$community_template["template_id"]; ?>"><img src="<?php echo ENTRADA_URL. "/images/icon-magnify.gif"  ?>" /></a>
                                                                </div>
                                                                <label for="<?php echo "template_option_".$community_template["template_id"]; ?>"><?php echo ucfirst($community_template["template_name"]. " Template"); ?></label>
                                                            </li>
                                                            <?php
                                                            $large_template_images .= " <div class=\"".$community_template["template_name"]."-large\" style=\"display:none;\">\n";
                                                            $large_template_images .= "     <img src=\"".ENTRADA_URL."/images/template-".$community_template["template_name"]."-large.gif\" alt=\"".ucfirst($community_template["template_name"])." Template Screen shot\" />\n";
                                                            $large_template_images .= " </div>\n";
														}
														?>
														</ul>
                                                        <?php
                                                        echo (isset($large_template_images) && $large_template_images ? $large_template_images : "");
													}
													?>					
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="3">&nbsp;</td>
											</tr>
											<?php /*
											<tr>
												<td><?php echo help_create_button("Contact E-Mail Address", ""); ?></td>
												<td><label for="community_email" class="form-nrequired">Contact E-Mail</label></td>
												<td><input type="text" id="community_email" name="community_email" value="<?php echo html_encode($PROCESSED["community_email"]); ?>" maxlength="128" style="width: 250px" /></td>
											</tr>
											<tr>
												<td><?php echo help_create_button("External Website", ""); ?></td>
												<td><label for="community_website" class="form-nrequired">External Website</label></td>
												<td><input type="text" id="community_website" name="community_website" value="<?php echo html_encode($PROCESSED["community_website"]); ?>" maxlength="1055" style="width: 250px" /></td>
											</tr>
											*/ ?>
										</tbody>
									</table>
									<?php
									if (Entrada_Twitter::widgetIsActive()) {
										?>
										<h2 style="margin-top: 0px">Twitter Details</h2>
										<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Modifying Community Details">
											<colgroup>
												<col style="width: 3%" />
												<col style="width: 20%" />
												<col style="width: 77%" />
											</colgroup>
											<tbody>
												<tr>
													<td>&nbsp;</td>
													<td><span class="form-nrequired">Community Twitter Handle</span></td>
													<td><input type="text" id="community_twitter_handle" name="community_twitter_handle" value="<?php echo html_encode((isset($PROCESSED["community_twitter_handle"]) && $PROCESSED["community_twitter_handle"] ? $PROCESSED["community_twitter_handle"] : "")); ?>"  /></td>
												</tr>
												<tr>
													<td>&nbsp;</td>
													<td><span class="form-nrequired">Community Twitter Hashtags</span></td>
													<td>
														<style type="text/css">
															/**
															 *	Fixes the hashtag select box width that was 0px when the page is
															 *	loaded on a different tab then Details.
															 */
															#twitter_hashtags_chzn {
																width: 220px !important;
															}
														</style>
														<select class="chosen-select" multiple id="twitter_hashtags" name="community_twitter_hashtags[]">
															<?php
															if( $PROCESSED["community_twitter_hashtags"] ) {
																$select_options_array = explode(" ", $PROCESSED["community_twitter_hashtags"]);
																foreach ($select_options_array as $select_option) {
																	echo "<option selected value=\"" . $select_option . "\">".$select_option."</option>";
																}
															}
															?>
														</select>
													</td>
												</tr>
											</tbody>
										</table>
										<?php
									}
									?>
								</div>
								<div class="tab-page">
									<h3 class="tab">Permissions</h3>
									<h2 style="margin-top: 0px"><?php echo $translate->_("Community Permissions"); ?></h2>
									<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Modifying Community Permissions">
										<colgroup>
											<col style="width: 3%" />
											<col style="width: 20%" />
											<col style="width: 77%" />
										</colgroup>
										<!--
										<tr>
											<td><?php echo help_create_button("Sub-Communities", "communities-sub_communities"); ?></td>
											<td><span class="form-nrequired">Sub-Communities</span></td>
											<td><input type="checkbox" id="sub_communities" name="sub_communities" value="1"<?php echo (($PROCESSED["sub_communities"] == 1) ? " checked=\"checked\"" : ""); ?> style="vertical-align: middle" /> <label for="sub_communities" class="form-nrequired" style="vertical-align: middle">Allow members to create sub-communities / groups under this community.</label></td>
										</tr>
										<tr>
											<td colspan="3">&nbsp;</td>
										</tr>
										-->
										<tr>
											<td style="vertical-align: top"><?php echo help_create_button("Access Permissions", ""); ?></td>
											<td style="vertical-align: top"><span class="form-nrequired">Access Permissions</span></td>
											<td style="padding-bottom: 15px">
												<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Setting Access Permissions">
													<colgroup>
														<col style="width: 3%" />
														<col style="width: 97%" />
													</colgroup>
													<tbody>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_protected" id="community_protected_1" value="1" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["community_protected"])) || ($PROCESSED["community_protected"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_protected_1" class="normal-green">Protected Community</label>
																<div class="content-small">Only authenticated users can access this community after they log in.</div>
															</td>
														</tr>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_protected" id="community_protected_0" value="0" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_protected"])) && ($PROCESSED["community_protected"] == 0)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_protected_0" class="normal-green">Public Community</label>
																<div class="content-small">Anyone in the world can have read-only access to this community without logging in.</div>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
										<tr>
											<td style="vertical-align: top"><?php echo help_create_button("Registration Options", ""); ?></td>
											<td style="vertical-align: top"><span class="form-nrequired">Registration Options</span></td>
											<td style="padding-bottom: 15px">
												<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Setting Registration Options">
													<colgroup>
														<col style="width: 3%" />
														<col style="width: 97%" />
													</colgroup>
													<tbody>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_0" value="0" onclick="selectRegistrationOption('0')" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["community_registration"])) || ((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 0))) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_registration_0" class="normal-green">Open Community</label>
																<div class="content-small">Any authenticated user can access this community without registering in it.</div>
															</td>
														</tr>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_1" value="1" onclick="selectRegistrationOption('1')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_registration_1" class="normal-green">Open Registration</label>
																<div class="content-small">Any authenticated user can and must register to be part of this community.</div>
															</td>
														</tr>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_2" value="2" onclick="selectRegistrationOption('2')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 2)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_registration_2" class="normal-green">Group Registration</label>
																<div class="content-small">Only members of the selected Groups can register to be part of this community.</div>
																<div id="community_registration_show_groups" style="display: none; padding-left: 25px">
																<?php
																if ((is_array($GROUP_TARGETS)) && ($total_sresults = count($GROUP_TARGETS))) {
																	$count			= 0;
																	$column			= 0;
																	$max_columns	= 2;

																	echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\" summary=\"Available Groups\">\n";
																	echo "<colgroup>\n";
																	echo "	<col style=\"width: 50%\" />\n";
																	echo "	<col style=\"width: 50%\" />\n";
																	echo "</colgroup>\n";
																	echo "<tbody>\n";
																	echo "	<tr>\n";
																	foreach ($GROUP_TARGETS as $group => $result) {
																		$count++;
																		$column++;

																		echo "	<td>\n";
																		echo "		<input type=\"checkbox\" id=\"community_registration_groups_".$group."\" name=\"community_registration_groups[]\" value=\"".$group."\" style=\"vertical-align: middle\"".(((isset($community_groups)) && (is_array($community_groups)) && (in_array($group, $community_groups))) ? " checked=\"checked\"" : "")." /> <label for=\"community_registration_groups_".$group."\" class=\"content-small\">".html_encode($result)."</label>\n";
																		echo "	</td>\n";
																		if (($count == $total_sresults) && ($column < $max_columns)) {
																			for ($i = 0; $i < ($max_columns - $column); $i++) {
																				echo "	<td>&nbsp;</td>\n";
																			}
																		}

																		if (($count == $total_sresults) || ($column == $max_columns)) {
																			$column = 0;
																			echo "	</tr>\n";

																			if ($count < $total_sresults) {
																				echo "	<tr>\n";
																			}
																		}
																	}
																	echo "</tbody>\n";
																	echo "</table>\n";
																}
																?>
																</div>
															</td>
														</tr>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_3" value="3" onclick="selectRegistrationOption('3')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 3)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_registration_3" class="normal-green">Community Registration</label>
																<div class="content-small">Only members of the selected Communities can register to be part of this community.</div>
																<div id="community_registration_show_communities" style="display: none; padding: 5px 5px 0px 5px">
																	<select id="community_registration_communities" name="community_registration_communities[]" multiple="multiple" size="10" style="width: 85%; height: 150px">
																	<?php
																	$COMMUNITIES_FETCH_CHILDREN = ((isset($community_communities)) ? $community_communities : array());
																	echo communities_fetch_children(0, false, 0, false, "select");
																	?>
																	</select>
																</div>
															</td>
														</tr>
														<tr>
															<td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_4" value="4" onclick="selectRegistrationOption('4')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 4)) ? " checked=\"checked\"" : ""); ?> /></td>
															<td>
																<label for="community_registration_4" class="normal-green">Private Community</label>
																<div class="content-small">People cannot register, members are invited only by community administrators.</div>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</table>
								</div>
								<?php
								if ($MAILING_LISTS["active"]) {
									?>
									<div class="tab-page">
										<h3 class="tab">Mailing List</h3>
										<h2 style="margin-top: 0px">Community Mailing List</h2>
										<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Modifying Community Mailing List">
											<colgroup>
												<col style="width: 3%" />
												<col style="width: 20%" />
												<col style="width: 77%" />
											</colgroup>
											<tr>
												<td style="vertical-align: top"><?php echo help_create_button("Mailing List Mode", ""); ?></td>
												<td style="vertical-align: top"><span class="form-nrequired">Mailing List Mode</span></td>
												<td style="padding-bottom: 15px">
													<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Setting Mailing List Mode">
														<colgroup>
															<col style="width: 3%" />
															<col style="width: 97%" />
														</colgroup>
														<tbody>
															<tr>
																<td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_announcement"<?php echo ($list_mode == "announcements" ? "\" checked=\"checked\"" : "\""); ?> style="vertical-align: middle" value="announcements" /></td>
																<td style="padding-bottom: 5px; vertical-align: top">
																	<label for="community_list_announcement" class="normal-green">Announcement Mode</label>
																	<div class="content-small">Allow administrators of this community to send out email announcements to all the members of the community through the mailing list at <a style="font-size: 11px;" href=<?php echo "\"mailto:" .$mailing_list->list_name . "@" . $GOOGLE_APPS["domain"]. "\">" . $mailing_list->list_name . "@" . $GOOGLE_APPS["domain"]; ?></a>.</div>
																</td>
															</tr>
															<tr>
																<td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_discussion"<?php echo ($list_mode == "discussion" ? "\" checked=\"checked\"" : "\""); ?> style="vertical-align: middle" value="discussion" /></td>
																<td style="padding-bottom: 5px; vertical-align: top">
																	<label for="community_list_discussion" class="normal-green">Discussion Mode</label>
																	<div class="content-small">Allow all members of this community to send out email to the community through the mailing list at <a style="font-size: 11px;" href=<?php echo "\"mailto:" .$mailing_list->list_name . "@" . $GOOGLE_APPS["domain"]. "\">" . $mailing_list->list_name . "@" . $GOOGLE_APPS["domain"]; ?></a>.</div></td>
															</tr>
															<tr>
																<td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_deactivate"<?php echo ($list_mode == "inactive" ? "\" checked=\"checked\"" : "\""); ?> style="vertical-align: middle" value="inactive" /></td>
																<td>
																	<label for="community_list_deactivates" class="normal-green">Deactivate List</label>
																	<div class="content-small">Disable the mailing list for this community so members cannot be contacted through the list.</div>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</table>
									</div>
									<?php
								}
									?>
								<div class="tab-page">
									<h3 class="tab">Deactivate</h3>
									<h2 style="margin-top: 0px">Deactivate Community</h2>

									<script type="text/javascript">
										function validateDeactivate() {
											if ((document.getElementById('confirmed-deactivation')) && (document.getElementById('confirmed-deactivation').checked)) {
												if (document.getElementById('deactivate_community_form')) {
													document.getElementById('deactivate_community_form').submit();
												}
											} else {
												alert('Before being able to deactivate this community, you must check the box that asks if you understand what will happen once you deactivate the community.');
											}
											return;
										}
									</script>

									If you no longer wish to maintain this community or it is no longer being used, you can deactivate the community using the button below. If you have any questions before deactivating, please use the Page Feedback icon to the left of the page to ask us.
									<div class="display-notice" style="margin-top: 15px; line-height: 175%">
										<strong>Please note</strong> that once you deactivate this community all of the content (photos, calendar, etc) within the community will no longer be accessible to you or any other members of the community. Deactivating this community will also deactivate any Sub-Communities / Groups that have been created under this community.
									</div>

									<label class="checkbox form-required" for="confirmed-deactivation">
										<input type="checkbox" id="confirmed-deactivation" value="" />I understand that deactivating this community will render everything inside it inaccessible.
									</label>
									 
									<div style="margin-top: 15px; margin-bottom: 50px;">
										<input type="button" class="btn btn-danger" onclick="validateDeactivate()" value="Deactivate Now" />
									</div>
								</div>
							</div>
							<script type="text/javascript">setupAllTabs(true);
							</script>
							<?php
							if (isset($_GET["deactivate"])) {
								?>
								<script type="text/javascript">tabPaneObj.setSelectedIndex(4);</script>
								<?php
							}
							?>
							<div style="margin-top: 10px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
									<tr>

										<td style="width: 75%; text-align: right; vertical-align: middle">
											<input type="submit" class="btn btn-primary" value="Save Changes" />
										</td>
									</tr>
								</table>
							</div>
						</form>
						<form id="deactivate_community_form" action="<?php echo ENTRADA_URL."/".$MODULE; ?>?section=deactivate" method="post">
							<input type="hidden" id="deactivate_community_id" name="community_id" value="<?php echo (int) $community_details["community_id"]; ?>" />
						</form>
						<br /><br />
						<?php
					break;
				}
			} else {
				application_log("error", "User tried to modify a community, but they aren't an administrator of this community.");

				$ERROR++;
				$ERRORSTR[] = "You do not appear to be an administrator of the community that you are trying to modify.<br /><br />If you feel you are getting this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

				echo display_error();
			}
		} else {
			application_log("error", "User tried to modify a community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

			$ERROR++;
			$ERRORSTR[] = "The community you are trying to modify either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

			echo display_error();
		}
	} else {
		application_log("error", "User tried to modify a community without providing a community_id.");

		header("Location: ".ENTRADA_URL."/communities");
		exit;
	}
}