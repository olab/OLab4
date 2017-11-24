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
} elseif (!$ENTRADA_ACL->amIAllowed("community", "create")) {

    Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have the permissions required to use this module."), "error", $MODULE);

	$url = ENTRADA_URL . "/admin/" . $MODULE;
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
    header("Location: " . $url);
    exit;
} else {

	if ($MAILING_LISTS["active"]) {
		require_once("Entrada/mail-list/mail-list.class.php");
	}

	$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities?".replace_query(array("section" => "create")), "title" => "Creating a Community");
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$CATEGORY_ID		= 0;
	$COMMUNITY_PARENT	= 0;

	/**
	 * Check for a community category to proceed.
	 */
	if ((isset($_GET["category"])) && ((int) trim($_GET["category"]))) {
		$CATEGORY_ID	= (int) trim($_GET["category"]);
	} elseif ((isset($_POST["category_id"])) && ((int) trim($_POST["category_id"]))) {
		$CATEGORY_ID	= (int) trim($_POST["category_id"]);
	}

	/**
	 * Ensure the selected category is feasible or send them to the first step.
	 */
	if ($CATEGORY_ID) {
		$query	= "	SELECT *
				FROM `communities_categories`
				WHERE `category_id` = ".$db->qstr($CATEGORY_ID)."
				AND `category_visible` = '1'";
		$result	= $db->GetRow($query);
		if ($result) {
			$query		= "
					SELECT COUNT(*) AS `total_categories`
					FROM `communities_categories`
					WHERE `category_parent` = ".$db->qstr($CATEGORY_ID)."
					AND `category_visible` = '1'";
			$sresult	= $db->GetRow($query);
			if (($sresult) && ((int) $sresult["total_categories"])) {
				$ERROR++;
				$ERRORSTR[] = "The community category that you have chosen can not accept new communities because it has categories underneath it. Please choose a new child category to place your new community under.";
			} else {
				if ($result["category_status"] == 1) {
					$NOTICE++;
					$NOTICESTR[] = "You have chosen a community category which requires administrator approval before your community will be accessible. An administrator will be notified once you have finished creating this community and they will review your request as soon as possible. Please continue creating this community.";
				}

				if ($STEP < 2) {
					$STEP = 2;
				}
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "The category that you have selected no longer exists in the system. Please choose a new category.";
		}
	} else {
		$NOTICE++;
		$NOTICESTR[]	= "Please begin by selecting a community category to place your new community under.";
		$STEP			= 1;
	}

	/**
	 * Check for a selected community parent for this category.
	 */
	if ((isset($_GET["parent"])) && ((int) trim($_GET["parent"]))) {
		$COMMUNITY_PARENT	= (int) trim($_GET["parent"]);
	} elseif ((isset($_POST["community_parent"])) && ((int) trim($_POST["community_parent"]))) {
		$COMMUNITY_PARENT	= (int) trim($_POST["community_parent"]);
	}

	/**
	 * If there is a selected community parent, make sure they have permissions to do this.
	 */
	if ($COMMUNITY_PARENT) {
		$query	= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_PARENT);
		$result	= $db->GetRow($query);
		if ($result) {
			if (!$result["sub_communities"]) {
				$ERROR++;
				$ERRORSTR[] = "The parent community that you have chosen does not allow sub-communities to be created under it.<br /><br />If you would like to create a community here, please contact a community administrator who will need to update the community profile to allow sub-communities.";
				$COMMUNITY_PARENT = 0;
			} elseif (!$result["community_active"]) {
				$ERROR++;
				$ERRORSTR[] = "The parent community that you have chosen is not currently activated; therefore a sub-communit cannot be created at this time.<br /><br />If you would like to create a community here, please contact a community administrator who will need to re-activate the community in their community profile.";
				$COMMUNITY_PARENT = 0;
			} elseif ($result["community_members"] != "") {
				if ((is_array($community_members = @unserialize($result["community_members"]))) && (isset($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]))) {
					if (!in_array($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"], $community_members)) {
						$ERROR++;
						$ERRORSTR[] = "The parent community that you have chosen only allows certain MEdTech groups (".html_encode(implode(", ", $community_members)).") to become members, and your group is ".html_encode($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]).".<br /><br />If you would like to create a community here, please contact a community administrator who will need to adjust the groups requirements option in their community profile.";
						$COMMUNITY_PARENT = 0;
					}
				}
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "The parent community that you have provided does not exist.";
			$COMMUNITY_PARENT = 0;
		}
	}

	if ($ERROR) {
		$STEP = 1;
	}

	echo "<h1>Creating a Community</h1>\n";

	// Error Checking
	switch($STEP) {
		case 3 :
			$PROCESSED["community_parent"]	= $COMMUNITY_PARENT;
			$PROCESSED["category_id"]		= $CATEGORY_ID;
			$PROCESSED["community_active"]	= 1;
			$PROCESSED["community_members"]	= "";

			$query	= "SELECT `category_status` FROM `communities_categories` WHERE `category_id` = ".$db->qstr($PROCESSED["category_id"])." AND `category_visible` = '1'";
			$result	= $db->GetRow($query);
			if ($result) {
				if ($result["category_status"] == 1) {
					$PROCESSED["community_active"] = 0;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The category that you have selected no longer exists in the system. Please choose a new category.";
			}


			if ((isset($_POST["community_title"])) && ($community_title = clean_input($_POST["community_title"], array("notags", "trim")))) {
				$PROCESSED["community_title"] = substr($community_title, 0, 64);
			} else {
				$ERROR++;
				$ERRORSTR[] = "Please provide a title for your new community. Example: Medicine Club";
			}

			if ((isset($_POST["community_keywords"])) && ($community_keywords = clean_input($_POST["community_keywords"], array("notags", "trim")))) {
				$PROCESSED["community_keywords"] = substr($community_keywords, 0, 255);
			} else {
				$PROCESSED["community_keywords"] = "";
			}

			if ((isset($_POST["community_description"])) && ($community_description = clean_input($_POST["community_description"], array("notags", "trim")))) {
				$PROCESSED["community_description"] = $community_description;
			} else {
				$PROCESSED["community_description"] = "";
			}

			if ((isset($_POST["page_ids"])) && (is_array($_POST["page_ids"])) ) {
				$PROCESSED["page_ids"] = $_POST["page_ids"];
			} else {
				$PROCESSED["page_ids"] = array();
			}

			if ((isset($_POST["course_ids"])) && (is_array($_POST["course_ids"])) ) {
				$PROCESSED["course_ids"] = $_POST["course_ids"];
			} else {
				$PROCESSED["course_ids"] = array();
			}

			if ((isset($_POST["community_shortname"])) && ($community_shortname = clean_input($_POST["community_shortname"], array("notags", "lower", "trim")))) {
			/**
			 * Ensure that this community name is less than 32 characters in length.
			 */
				$community_shortname = substr($community_shortname, 0, 32);

				$query	= "SELECT `community_id`, `community_url`, `community_shortname`, `community_title` FROM `communities` WHERE `community_shortname` = ".$db->qstr($community_shortname)." AND `community_parent` = ".$db->qstr($COMMUNITY_PARENT)." LIMIT 1";
				$result	= $db->GetRow($query);
				if ($result) {
					$ERROR++;
					$ERRORSTR[] = "The Community Shortname <em>(".html_encode($community_shortname).")</em> that you have chosen is already in use by another community in the system.<br /><br />Please choose and enter a new shortname to use for your community.";
				} else {
					if ($parent_details = communities_fetch_parent($COMMUNITY_PARENT)) {
						$PROCESSED["community_url"] = $parent_details["community_url"]."/".$community_shortname;
					} else {
						$PROCESSED["community_url"] = "/".$community_shortname;
					}

					$PROCESSED["community_shortname"] = $community_shortname;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a shortname for your new community to use. Example: medicine_club";
			}

			/**
			 * Required: Mailing List Mode
			 */
			if (($MAILING_LISTS["active"]) && isset($_POST["community_list_mode"])) {
				if (($list_mode = clean_input($_POST["community_list_mode"], array("nows", "lower"))) && $list_mode != $mail_list->type) {
					$PROCESSED["community_list_mode"] = $list_mode;
				}
			} elseif ($MAILING_LISTS["active"]) {
				$ERROR++;
				$ERRORSTR[] = "You must specify which mode the mailing list for this community is in.";
			}

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

			if (isset($_POST["template_selection"])) {
				if ($template_selection = clean_input($_POST["template_selection"], array("trim", "int"))) {
					$template_query = "SELECT * FROM `community_templates` WHERE `template_id` = ". $db->qstr($template_selection);
					$community_template = $db->GetRow($template_query);
					if ($community_template) {
						$PROCESSED["community_template"] = $community_template["template_name"];
					}
				}
			}

			/**
			 * Check for a community category to proceed.
			 */
			if ((isset($_GET["type_id"])) && ((int) trim($_GET["type_id"]))) {
				$PROCESSED["octype_id"]	= (int) trim($_GET["type_id"]);
			} elseif ((isset($_POST["type_id"])) && ((int) trim($_POST["type_id"]))) {
				$PROCESSED["octype_id"]	= (int) trim($_POST["type_id"]);
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must specify a Community Type for this new community.";
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
				$PROCESSED["community_twitter_hashtags"] = array();
				foreach ($_POST["community_twitter_hashtags"] as $index => $tmp_input) {
					if ($community_twitter_hashtags = clean_input($tmp_input, array("trim", "notags"))) {
						$PROCESSED["community_twitter_hashtags"][] = $community_twitter_hashtags;
					}
				}
				$PROCESSED["community_twitter_hashtags"] = implode(" ", $PROCESSED["community_twitter_hashtags"]);
			} else {
				$PROCESSED["community_twitter_hashtags"] = "";
			}

			if (isset($_POST["community_registration"])) {
				switch(clean_input($_POST["community_registration"], array("trim", "int"))) {
					case 0 :
						$PROCESSED["community_registration"]	= 0;
					break;
					case 2 :
						$PROCESSED["community_registration"]	= 2;

						// Group Registration
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
					case 3 :
						$PROCESSED["community_registration"]	= 3;

						// Community Registration
						if ((isset($_POST["community_registration_communities"])) && (is_array($_POST["community_registration_communities"])) && (count($_POST["community_registration_communities"]))) {
							$community_communities = array();

							foreach ($_POST["community_registration_communities"] as $community_id) {
								if ($community_id = (int) trim($community_id)) {
									$query	= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($community_id)." AND `community_active` = '1'";
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
					case 4 :
						$PROCESSED["community_registration"]	= 4;
					break;
					case 1 :
					default :
						$PROCESSED["community_registration"]	= 1;
					break;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must specify the Registration Options for this new community.";
			}

            $query = "SELECT * FROM `communities_modules` WHERE `module_active` = '1'";
            $results = $db->GetAll($query);
            foreach ($results as $module) {
                $community_modules[] = $module["module_id"];
            }

			if (!$ERROR) {
				$PROCESSED["community_opened"]	= time();
				$PROCESSED["updated_date"]		= time();
				$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

				if (($db->AutoExecute("communities", $PROCESSED, "INSERT")) && ($community_id = $db->Insert_Id())) {
					if ($db->AutoExecute("community_members", array("community_id" => $community_id, "proxy_id" => $ENTRADA_USER->getActiveId(), "member_active" => 1, "member_joined" => time(), "member_acl" => 1), "INSERT")) {

						foreach ($community_modules as $module_id) {
							if (!communities_module_activate($community_id, $module_id)) {
								$NOTICE++;
								$NOTICESTR[] = "We were unable to activate module ".(int) $module_id." when creating your community.<br /><br />Your community will still function without this module. The MEdTech Unit has been informed of this problem and will resolve it shortly.";

								application_log("error", "Unable to active module ".(int) $module_id." for new community id ".(int) $community_id.". Database said: ".$db->ErrorMsg());
							}
						}
                        if (isset($PROCESSED["page_ids"]) && $PROCESSED["page_ids"]) {
                            foreach ($PROCESSED["page_ids"] as $page_id) {
                                $query = "SELECT * FROM `community_type_pages` WHERE `ctpage_id` = ".$db->qstr($page_id);
                                $page = $db->GetRow($query);
                                if ($page) {
                                    $query = "SELECT * FROM `community_type_page_options` WHERE `ctpage_id` = ".$db->qstr($page_id);
                                    $page_options = $db->GetAll($query);
                                    $page["community_id"] = $community_id;
                                    $page["updated_date"] = time();
                                    $page["updated_by"] = $ENTRADA_USER->getActiveId();
                                    if ($db->AutoExecute("community_pages", $page, "INSERT") && ($cpage_id = $db->Insert_Id())) {
                                        communities_log_history($community_id, $cpage_id, 0, "community_history_add_page", 1);
                                        foreach ($page_options as $page_option) {
                                            if ($page_option["option_title"] == "community_title") {
                                                $query = "UPDATE `community_pages` 
                                                            SET `menu_title` = ".$db->qstr($PROCESSED["community_title"]).", 
                                                                `page_title` = ".$db->qstr($PROCESSED["community_title"])." 
                                                            WHERE `cpage_id` = ".$db->qstr($cpage_id);
                                                if (!$db->Execute($query)) {
                                                    application_log("error", "Unable to set `page_title` and `menu_title` for a community page [".$cpage_id."] to that of the `community_title`.");
                                                }
                                            } else {
                                                $page_option["cpage_id"] = $cpage_id;
                                                if (!$db->AutoExecute("community_page_options", $page_option, "INSERT")) {
                                                    $ERROR++;
                                                    $ERRORSTR[] = "An issue was encountered while attempting to insert page options for a newly created page.";
                                                    application_log("error", "Could not create a page option record in community page [".$cpage_id."]. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }
                                    } else {
                                        $ERROR++;
                                        $ERRORSTR[] = "An issue was encountered while attempting to insert a page in this newly created community.";
                                        application_log("error", "Could not create a new page in community [".$community_id."]. Database said: ".$db->ErrorMsg());
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
                                if ($course) {
                                    if (!$db->AutoExecute("community_courses", array("community_id" => $community_id, "course_id" => $course["course_id"]), "INSERT")) {
                                        $ERROR++;
                                        $ERRORSTR[] = "An issue was encountered while attempting to attach a course to this newly created community.";
                                        application_log("error", "Could not connect a course [".$course["course_id"]."] to a newly created community [".$community_id."]. Database said: ".$db->ErrorMsg());
                                    }
                                }
                            }
                        }

						if (!$PROCESSED["community_active"]) {
							if ($MAILING_LISTS["active"]) {
								$mail_list = new MailingList($community_id, $PROCESSED["community_list_mode"]);
							}

							if (communities_approval_notice($community_id)) {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully created <strong>%s</strong>, however because you are not an administrator your event must be reviewed before appearing to all users."), $PROCESSED["community_title"]), "success", $MODULE);
								communities_log_history($community_id, 0, $community_id, "community_history_create_moderated_community", 1);

								$url = ENTRADA_URL . "/communities";
                                header("Location: " . $url);
                                exit;
							} else {
								$ERROR++;
								$ERRORSTR[] = "Your new community has been successfully created; however, this community requires an administrator's approval before it is activated and there was an error when trying to send an administrator this notification.<br /><br />The MEdTech Unit has been informed of this error and will contact you shortly.";

								application_log("error", "Community ID ".$community_id." was successfully created, but an admin approval notification could not be sent.");
							}

						} else {
							if ($MAILING_LISTS["active"]) {
								$mail_list = new MailingList($community_id, $PROCESSED["community_list_mode"]);
							}
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully created <strong>%s</strong>"), $PROCESSED["community_title"]), "success", "login");
							communities_log_history($community_id, 0, $community_id, "community_history_create_active_community", 1);

							$url = ENTRADA_URL . "/community" . $PROCESSED["community_url"];
                            header("Location: " . $url);
                            exit;
						}
					} else {
						$ERROR++;

						$ERRORSTR[] = "Your community was successfully created; however, administrative permissions for your account could not be set to the new community.<br /><br />The system administrator has been informed of this problem, and they will resolve it shortly.";

						application_log("error", "Community was created, but admin permissions for proxy id ".$ENTRADA_USER->getActiveId()." could not be set. Database said: ".$db->ErrorMsg());
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "We encountered a problem while creating your new community.<br /><br />The system administrator has been informed of this problem, please try again later.";

					application_log("error", "Failed to create new community. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP		= 2;
			} else {
				application_log("success", "Community ID ".$community_id." was successfully created.");
			}
		break;
		case 2 :
            /**
             * This error checking is actually done above because it's a requirement for any page (including 3).
             */
			$template_selection = 0;
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content
	switch($STEP) {
		case 2 :
            if (isset($PROCESSED["community_shortname"]) && $PROCESSED["community_shortname"]) {
				$ONLOAD[] = "validateShortname('".html_encode($PROCESSED["community_shortname"])."')";
			}
			if ((!isset($PROCESSED["community_registration"])) || (!(int) $PROCESSED["community_registration"])) {
				$ONLOAD[] = "selectRegistrationOption('0')";
			} else {
				$ONLOAD[] = "selectRegistrationOption('".(int) $PROCESSED["community_registration"]."')";
			}
            
            $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
            $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\"></script>\n";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/Twitter.js\"></script>";
			$ONLOAD[] = "jQuery('.chosen-select').chosen({no_results_text: 'No courses found matching'})";

			if ($COMMUNITY_PARENT) {
				$fetched	= array();
				communities_fetch_parents($COMMUNITY_PARENT, $fetched);

				if ((is_array($fetched)) && (@count($fetched))) {
					$community_parents	= array_reverse($fetched);
				} else {
					$community_parents	= false;
				}
				unset($fetched);
			}
			?>
            <h2>Step 2: Community Details</h2>
			<?php
			if ($ERROR) {
				echo display_error();
			}
			if ($NOTICE) {
				echo display_notice();
			}
			?>
            <script type="text/javascript">
                function loadCommunityType(community_type_id) {
                    new Ajax.Updater('type_options', '<?php echo ENTRADA_URL; ?>/api/community-type-options.api.php', {
                        parameters: {
                                        community_type_id: community_type_id,
                                        'page_ids[]' : $$('input[name="page_ids[]"]:checked').pluck('value'),
                                        'course_ids[]' : jQuery('select#course_ids').val(),
                                        category_id: <?php echo $CATEGORY_ID; ?>,
                                        group: '<?php echo $GROUP; ?>'
                                    },
                        onComplete: function () {
                            $('type_options').show();
                            jQuery('.chosen-select').chosen({no_results_text: 'No courses found matching'});
                        }
                    });
                }
                
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
            <form action="<?php echo ENTRADA_URL."/".$MODULE."?".replace_query(array("action" => "create", "step" => 3)); ?>" method="post" class="form-horizontal">
                <input type="hidden" name="category_id" value="<?php echo html_encode($CATEGORY_ID); ?>" />
                <input type="hidden" name="community_parent" value="<?php echo html_encode($COMMUNITY_PARENT); ?>" />
                <h3>Community Details</h3>
                <div class="control-group">
                    <?php
                    if (isset($community_parents) && @count($community_parents)) {
                        ?>
                        <label class="control-label">Community Path:</label>
                        <div class="controls">
                            <?php
                            foreach ($community_parents as $result) {
                                echo html_encode($result["community_title"])." / ";
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="community_title">Community Name</label>
                    <div class="controls">
                        <input type="text" id="community_title" name="community_title" value="<?php echo html_encode((isset($PROCESSED["community_title"]) && $PROCESSED["community_title"] ? $PROCESSED["community_title"] : "")); ?>" onkeyup="validateShortname(this.value)" />
                        <span class="content-small">(<strong>Example:</strong> Medicine Club)</span>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="community_keywords">Community Keywords</label>
                    <div class="controls">
                        <input type="text" id="community_keywords" name="community_keywords" value="<?php echo html_encode((isset($PROCESSED["community_keywords"]) && $PROCESSED["community_keywords"] ? $PROCESSED["community_keywords"] : "")); ?>"  />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="community_description">Community Description</label>
                    <div class="controls">
                        <textarea id="community_description" name="community_description"><?php echo html_encode((isset($PROCESSED["community_description"]) && $PROCESSED["community_description"] ? $PROCESSED["community_description"] : "")); ?></textarea>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="community_shortname">Community Shortname</label>
                    <div class="controls">
                        <input type="text" id="community_shortname" name="community_shortname" onkeyup="validateShortname(this.value)" value="<?php echo (isset($PROCESSED["community_shortname"]) && $PROCESSED["community_shortname"] ? $PROCESSED["community_shortname"] : "") ?>" />
                        <span class="content-small">(<strong>Example:</strong> medicine_club)</span>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <p class="content-small"><strong>Help Note:</strong> The community shortname is a name used to uniquely identify your new community in the URL; a username for the community of sorts. It must lower-case, less than 20 characters, and can only contain letters, numbers, underscore or period.
                            <br /><br />
                            <?php
                            echo ENTRADA_URL."/community/";
                            if (isset($community_parents) && is_array($community_parents)) {
                                foreach ($community_parents as $result) {
                                    echo html_encode($result["community_shortname"])."/";
                                }
                            }
                            ?><span id="displayed_shortname"></span>
                        </p>
                    </div>
                </div>
                <?php
                $query = "SELECT * FROM `org_community_types`
                            WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->GetActiveOrganisation())."
                            AND `community_type_active` = 1";
                $community_types = $db->GetAll($query);
                if ($community_types) {
                    /**
                     * @todo jellis: can you please modify this so that if there is only one
                     * type for the user to "choose" from it simply shows the options for that
                     * type. I couldn't figure it out on the air plane.
                     */
                    if (false && count($community_types) == 1) {
                        ?>
                        <script type="text/javascript">
                        jQuery(function() {
                            loadCommunityType('<?php echo $community_type["octype_id"]; ?>');
                        });
                        </script>
                        <input type="hidden" name="type_id" id="type_id" value="<?php echo $community_type["octype_id"]; ?>" />
                        <?php
                    } else {
                        ?>
                        <div class="control-group">
                            <label class="control-label form-required" for="type_id">Community Type</label>
                            <div class="controls">
                                 <?php
                                 echo "<select name=\"type_id\" id=\"type_id\">";
                                 foreach ($community_types as $community_type) {
                                     echo "<option value = \"".$community_type["octype_id"]."\"".(isset($PROCESSED["octype_id"]) && $PROCESSED["octype_id"] == $community_type["octype_id"] ? " selected=\"selected\"" : "").">".$community_type["community_type_name"]."</option>";
                                 }
                                 echo "</select>";
                                 ?>
                                <script type="text/javascript">
                                    jQuery(function($) {
                                        loadCommunityType($('#type_id').val());

                                        $('#type_id').change(function() {
                                            loadCommunityType(this.value);
                                        });
                                    });
                                </script>
                             </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <div id="type_options"<?php echo (!isset($PROCESSED["octype_id"]) || !$PROCESSED["octype_id"] ? " style=\"display: none;\"" : ""); ?>>
                    <div class="control-group">
                        <label class="control-label form-nrequired">Community Template</label>
                        <div class="controls">
                            <?php
                            if (isset($PROCESSED["octype_id"]) && $PROCESSED["octype_id"]) {
                                $query = "SELECT a.* FROM `community_templates` AS a
                                            JOIN `community_type_templates` AS b
                                            ON a.`template_id` = b.`template_id`
                                            WHERE b.`type_id` = ".$db->qstr($PROCESSED["octype_id"])."
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
                                        foreach ($results as $community_template) {
                                            $permissions_query = "SELECT * FROM `communities_template_permissions` WHERE `template`=". $db->qstr($community_template["template_name"]);
                                            $template_permissions = $db->GetAll($permissions_query);
                                            if ($template_permissions) {
                                                foreach ($template_permissions as $template_permission) {
                                                    if ($template_permission["permission_type"] == "group") {
                                                        $groups = explode(",",$template_permission["permission_value"]);
                                                    }
                                                    if (($template_permission["permission_type"] == null && $template_permission["permission_value"] == null)) {
                                                        $default_templates[] = $template_permission["template"];
                                                    }
                                                    if (($template_permission["permission_type"] == "category_id" && $template_permission["permission_value"] != null)) {
                                                        $category = explode(",",$template_permission["permission_value"]);
                                                    }
                                                    if (($template_permission["permission_type"] == "category_id" && $template_permission["permission_value"] == null)) {
                                                        $category_permissions_query = " SELECT * FROM `communities_template_permissions`
                                                                                        WHERE `permission_type`= 'group'
                                                                                        AND `template`=". $db->qstr($template_permission["template"]);
                                                        $category_permissions = $db->GetAll($category_permissions_query);
                                                        if ($category_permissions) {
                                                            foreach ($category_permissions as $category_permission) {
                                                                $default_categories = explode(",", $category_permission["permission_value"]);
                                                                if (in_array($GROUP, $default_categories)) {
                                                                    $default_categories[] = $category_permission["template"];
                                                                }

                                                            }
                                                        }
                                                    }
                                                }
                                                if ((in_array($GROUP, $groups) && in_array($CATEGORY_ID, $category)) || (in_array($community_template["template_name"], $default_templates)) || (in_array($community_template["template_name"], $default_categories))) {
                                                ?>
                                                    <li id="<?php echo $community_template["template_name"]."-template"; ?>" style="background: url('images/<?php echo $community_template["template_name"]; ?>-thumb.jpg')">
                                                        <div class="template-rdo">
                                                            <input type="radio" id="<?php echo "template_option_".$community_template["template_id"] ?>" name="template_selection" value="<?php echo $community_template["template_id"]; ?>"<?php echo (((!isset($template_selection) || $template_selection == 0) && ($key == 0) || (isset($template_selection) && $template_selection == $community_template["template_id"])) ? " checked=\"checked\"" : ""); ?> />
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
                                            }
                                        }
                                        ?>
                                    </ul>
                                    <?php
                                    echo (isset($large_template_images) && $large_template_images ? $large_template_images : "");
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <h3>Community Pages</h3>
                    <div class="control-group">
                        <label class="control-label form-required">Default Pages</label>
                        <div class="controls">
                            <?php
                            $pages_output = community_type_pages_inlists($PROCESSED["octype_id"], 0, 0, array(), $PROCESSED["page_ids"]);
                            if ($pages_output != "<ul class=\"community-page-list empty\"></ul>") {
                                echo $pages_output;
                            } else {
                                add_notice("No default pages found for this community type.");
                                echo display_notice();
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    $query = "SELECT `community_type_options` FROM `org_community_types`
                                WHERE `octype_id` = ".$db->qstr($PROCESSED["octype_id"]);
                    $type_options_serialized = $db->GetOne($query);
                    if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
                        foreach ($type_options as $type_option => $active) {
                            if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                                ?>
                                <h3>Community Courses</h3>
                                <div class="control-group">
                                    <label class="control-label form-nrequired">Select course(s)</label>
                                    <div class="controls">
                                        <?php
                                            $query = "SELECT `course_id`, `course_code`, `course_name` FROM `courses` 
                                                        WHERE `course_active` = 1
                                                        AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                        AND `course_id` NOT IN (
                                                            SELECT `course_id` FROM `community_courses`
                                                        )";
                                            $courses = $db->GetAll($query);
                                            if ($courses) {
                                                echo "<select multiple=\"multiple\" name=\"course_ids[]\" id=\"course_ids\" class=\"chosen-select\">";
                                                foreach ($courses as $course) {
                                                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), 'update')) {
                                                        echo "<option value=\"".((int)$course["course_id"])."\"".(in_array($course["course_id"], $PROCESSED["course_ids"]) ? " selected=\"selected\"" : "").">".html_encode(($course["course_code"] ? $course["course_code"]." - " : "").$course["course_name"])."</option>";
                                                    }
                                                }
                                                echo "</select>";
                                            }
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                	?>
                </div>
				<?php if ( Entrada_Twitter::widgetIsActive() ) { ?>
					<h3>Community Twitter</h3>
					<div class="control-group">
						<label class="control-label" for="community_twitter_handle">Community Twitter Handle</label>
						<div class="controls">
							<input type="text" id="community_twitter_handle" name="community_twitter_handle" value="<?php echo html_encode((isset($PROCESSED["community_twitter_handle"]) && $PROCESSED["community_twitter_handle"] ? $PROCESSED["community_twitter_handle"] : "")); ?>"  />
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="community_twitter_hashtags">Community Twitter Hashtags</label>
						<div class="controls">
							<select class="chosen-select" multiple id="twitter_hashtags" name="community_twitter_hashtags[]">
								<?php
								$select_options_array = explode(" ", $PROCESSED["community_twitter_hashtags"]);
								foreach ($select_options_array as $select_option) {
									echo "<option selected value=\"" . $select_option . "\">".$select_option."</option>";
								}
								?>
							</select>

						</div>
					</div>
					<div class="clearfix"></div>
				<?php } ?>


                <h3><?php echo $translate->_("Community Permissions"); ?></h3>
                <div class="control-group">
                    <label class="control-label form-nrequired">Access Permissions</label>
                    <div class="controls">
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
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-nrequired">Registration Options</label>
                    <div class="controls">
                        <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Setting Registration Options">
                            <colgroup>
                                <col style="width: 3%" />
                                <col style="width: 97%" />
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_0" value="0" onclick="selectRegistrationOption('0')" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["community_registration"])) || (isset($PROCESSED["community_registration"])) && ((int) $PROCESSED["community_registration"] === 0)) ? " checked=\"checked\"" : ""); ?> /></td>
                                    <td>
                                        <label for="community_registration_0" class="normal-green">Open Community</label>
                                        <div class="content-small">Any authenticated user can access this community without registering.</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_1" value="1" onclick="selectRegistrationOption('1')" style="vertical-align: middle"<?php echo ((((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
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
                                                $count = 0;
                                                $column = 0;
                                                $max_columns = 2;

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
                                    <td style="vertical-align: top"><input type="radio" name="community_registration" id="community_registration_4" value="4" onclick="selectRegistrationOption('4')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["community_registration"])) && ($PROCESSED["community_registration"] == 4)) ? " checked=\"checked\"" : ""); ?> /></td>
                                    <td>
                                        <label for="community_registration_4" class="normal-green">Private Community</label>
                                        <div class="content-small">People cannot register, members are invited only by community administrators.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                if ($MAILING_LISTS["active"]) {
                    ?>
                    <h3>Community Mailing List</h3>
                    <div class="control-group">
                        <label class="control-label form-nrequired">Community Mailing List</label>
                        <div class="controls">
                            <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Setting Mailing List Mode">
                                <colgroup>
                                    <col style="width: 3%" />
                                    <col style="width: 97%" />
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_announcement" style="vertical-align: middle" value="announcements" <?php echo (((!isset($PROCESSED["community_list_mode"]) && isset($mail_list) && $mail_list->type == "announcements") || (isset($PROCESSED["community_list_mode"])) && ($PROCESSED["community_list_mode"] == "announcements")) ? " checked=\"checked\"" : ""); ?>/></td>
                                        <td style="padding-bottom: 5px; vertical-align: top">
                                            <label for="community_list_announcement" class="normal-green">Announcement Mode</label>
                                            <div class="content-small">Allow administrators of this community to send out email announcements to all the members of the community through the mailing list.</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_discussion" style="vertical-align: middle" value="discussion" <?php echo (((!isset($PROCESSED["community_list_mode"]) && isset($mail_list) && $mail_list->type == "discussion") || (isset($PROCESSED["community_list_mode"])) && ($PROCESSED["community_list_mode"] == "discussion")) ? " checked=\"checked\"" : ""); ?>/></td>
                                        <td style="padding-bottom: 5px; vertical-align: top">
                                            <label for="community_list_discussion" class="normal-green">Discussion Mode</label>
                                            <div class="content-small">Allow all members of this community to send out email to the community through the mailing list.</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 5px; vertical-align: top"><input type="radio" name="community_list_mode" id="community_list_deactivate" style="vertical-align: middle" value="inactive" <?php echo (((!isset($PROCESSED["community_list_mode"]) || !isset($mail_list) || $mail_list->type == "inactive") || (isset($PROCESSED["community_list_mode"])) && ($PROCESSED["community_list_mode"] == "inactive")) ? " checked=\"checked\"" : ""); ?>/></td>
                                        <td>
                                            <label for="community_list_deactivates" class="normal-green">Deactivate List</label>
                                            <div class="content-small">Disable the mailing list for this community so members cannot be contacted through the list.</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="form-actions">
                    <input type="submit" class="btn btn-primary" value="Create" /> <span class="or">or</span>
                    <a class="btn" onclick="window.location='<?php echo ENTRADA_URL."/".$MODULE; ?>'" />Cancel</a>
                </div>
            </form>
            <br /><br />
            <?php
		break;
        case 1 :
		default :
			?>
            <h2>Step 1: Choosing Your Category</h2>
			<?php
			if ($ERROR) {
				echo display_error();
			}
			if ($NOTICE) {
				echo display_notice();
			}

			$query = "SELECT *
                        FROM `communities_categories`
                        WHERE `category_parent` = '0'
                        AND `category_visible` = '1'
                        ORDER BY `category_title` ASC";
			$results = $db->GetAll($query);
			if ($results) {
				?>
                <table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
                    <colgroup>
                        <col style="width: 50%" />
                        <col style="width: 50%" />
                    </colgroup>
                    <tbody>
						<?php
						foreach ($results as $result) {
							echo "<tr>\n";
							echo "	<td colspan=\"2\"><h4 class=\"categ-title\"> ".html_encode($result["category_title"])."</h4></td>\n";
							echo "</tr>\n";
							$query	= "SELECT *
                                        FROM `communities_categories`
                                        WHERE `category_parent` = ".$db->qstr($result["category_id"])."
                                        AND `category_visible` = '1'
                                        ORDER BY `category_title` ASC";
							$sresults = $db->GetAll($query);
							if ($sresults) {
								$total_sresults	= @count($sresults);
								$count			= 0;
								$column			= 0;
								$max_columns		= 2;
								foreach ($sresults as $sresult) {
									$count++;
									$column++;
									$communities = communities_count($sresult["category_id"]);
									echo "\t<td style=\"padding: 2px 2px 2px 19px\">";
									echo "	<div style=\"position: relative; vertical-align: middle;\">\n";
									echo "		<img src=\"".ENTRADA_URL."/images/btn_folder_go.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"margin-right: 2px\" />";
									echo "		<a href=\"".ENTRADA_URL."/communities?".replace_query(array("section" => "create", "step" => 2, "category" => $sresult["category_id"], "parent" => (($COMMUNITY_PARENT) ? $COMMUNITY_PARENT : false)))."\" style=\"font-size: 13px; color: #006699\">".html_encode($sresult["category_title"])."</a>";
									if ($communities) {
										echo "<span style=\"position: absolute; right: 0px; display: inline; vertical-align: middle\" class=\"content-small\">(".$communities." communit".(($communities != 1) ? "ies" : "y").")</span>";
									}
									echo "	</div>\n";
									echo "</td>\n";

									if (($count == $total_sresults) && ($column < $max_columns)) {
										for ($i = 0; $i < ($max_columns - $column); $i++) {
											echo "<td>&nbsp;</td>\n";
										}
									}

									if (($count == $total_sresults) || ($column == $max_columns)) {
										$column = 0;
										echo "</tr>\n";

										if ($count < $total_sresults) {
											echo "<tr>\n";
										}
									}
								}
								echo "<tr>\n";
								echo "	<td colspan=\"2\">&nbsp;</td>\n";
								echo "</tr>\n";
							}
						}
						?>
                    </tbody>
                </table>
                <?php
			} else {
				$ERROR++;
				$ERRORSTR[] = "There does no seem to be any Community Categories in the database right now.<br /><br />The MEdTech Unit has been notified of this problem, please try again later. We apologize for any inconvenience this has caused.";

				echo display_error();

				application_log("error", "No community categories in the database. Database said: ".$db->ErrorMsg());
			}
		break;
	}
}
