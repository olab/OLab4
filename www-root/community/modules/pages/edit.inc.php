<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Gives community administrators the ability to edit a page in their community.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_PAGES")) || !COMMUNITY_INCLUDED || !IN_PAGES) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if (($LOGGED_IN) && (!$COMMUNITY_MEMBER)) {
	$NOTICE++;
	$NOTICESTR[] = "You are not currently a member of this community, <a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"font-weight: bold\">want to join?</a>";

	echo display_notice();
} else {
	$PAGE_TYPES		= array();
	$STEP			= 1;
	$PAGE_ID		= 0;
	$page_options 	= array();
	$home_page		= false;

	if ((isset($_GET["step"])) && ($tmp_input = clean_input($_GET["step"], array("int")))) {
		$STEP = $tmp_input;
	}

	if ((isset($_GET["page"])) && ($tmp_input = clean_input($_GET["page"], array("int")))) {
		$PAGE_ID 		= $tmp_input;
		$query			= "SELECT `page_type`, `page_url` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($PAGE_ID);
		$page_record	= $db->GetRow($query);
		$page_type		= $page_record["page_type"];

		$home_page		= (((isset($page_record["page_url"])) && ($page_record["page_url"] != "")) ? false : true);
	} elseif ($_GET["page"] == "home") {
		$query			= "SELECT `cpage_id`,`page_type` FROM `community_pages` WHERE `page_url` = '' AND `community_id` = ".$db->qstr($COMMUNITY_ID);
		$page_record 	= $db->GetRow($query);
		$PAGE_ID 		= $page_record["cpage_id"];
		$page_type		= $page_record["page_type"];

		$home_page		= true;
	}

	$query				= "	SELECT `module_shortname`, `module_title`
							FROM `communities_modules`
							WHERE `module_id` IN (
								SELECT `module_id`
								FROM `community_modules`
								WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND `module_active` = '1'
								ORDER BY `module_title` ASC
							)";
	$module_pagetypes	= $db->GetAll($query);

	$PAGE_TYPES[]		= array("module_shortname" => "default", "module_title" => "Default Content");

	foreach ($module_pagetypes as $module_pagetype) {
		$PAGE_TYPES[]	= array("module_shortname" => $module_pagetype["module_shortname"], "module_title" => $module_pagetype["module_title"]);
	}

	if (!$home_page) {
		$PAGE_TYPES[]	= array("module_shortname" => "url", "module_title" => "External URL");
		$PAGE_TYPES[]   = array("module_shortname" => "lticonsumer", "module_title" => "BasicLTI Consumer");
	}

	foreach ($PAGE_TYPES as $PAGE) {
		if (isset($_GET["type"])) {
			if ((is_array($PAGE)) && (array_search(trim($_GET["type"]), $PAGE))) {
				$PAGE_TYPE = trim($_GET["type"]);
				break;
			}
		}
	}

	if (!isset($PAGE_TYPE) || !$PAGE_TYPE) {
		$PAGE_TYPE= $page_type;
	}
	
	if ($home_page || $PAGE_TYPE == "events" || $PAGE_TYPE == "announcements") {
		$query		= "SELECT * FROM `community_page_options` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID);
		$results	= $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
						$page_options[$result["option_title"]] = $result;
			}
		}
	}
	
	if ($home_page) {
		/**
		 * If these options are not already records in the database, insert them so they can be updated.
		 */
		if (!array_key_exists("show_announcements", $page_options)) {
			$db->Execute("INSERT INTO `community_page_options` 
				(`community_id`, `cpage_id`, `option_title`, `option_value`)
				VALUES (".$db->qstr($COMMUNITY_ID).", ".$db->qstr($PAGE_ID).", 'show_announcements', 0)");
			$page_options["show_announcements"] =	array ('cpoption_id' 	=> $db->insert_id(),
												   'community_id'	=> $COMMUNITY_ID,
												   'cpage_id'		=> $PAGE_ID,
												   'option_title'	=> "show_announcements",
												   'option_value'	=> 0,
												   'proxy_id'		=> 0,
												   'updated_date'	=> 0
												  );
		}
		if (!array_key_exists("show_events", $page_options)) {
			$db->Execute("INSERT INTO `community_page_options` 
				(`community_id`, `cpage_id`, `option_title`, `option_value`)
				VALUES (".$db->qstr($COMMUNITY_ID).", ".$db->qstr($PAGE_ID).", 'show_events', 0)");
			$page_options["show_events"] =	array ('cpoption_id' 	=> $db->insert_id(),
												   'community_id'	=> $COMMUNITY_ID,
												   'cpage_id'		=> $PAGE_ID,
												   'option_title'	=> "show_events",
												   'option_value'	=> 0,
												   'proxy_id'		=> 0,
												   'updated_date'	=> 0
												  );
		}
		if (!array_key_exists("show_history", $page_options)) {
			$db->Execute("INSERT INTO `community_page_options` 
				(`community_id`, `cpage_id`, `option_title`, `option_value`)
				VALUES (".$db->qstr($COMMUNITY_ID).", ".$db->qstr($PAGE_ID).", 'show_history', 0)");
			$page_options["show_history"] =	array ('cpoption_id' 	=> $db->insert_id(),
												   'community_id'	=> $COMMUNITY_ID,
												   'cpage_id'		=> $PAGE_ID,
												   'option_title'	=> "show_history",
												   'option_value'	=> 0,
												   'proxy_id'		=> 0,
												   'updated_date'	=> 0
												  );
		}
		
		$query		= "SELECT * FROM `community_page_options` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);//." AND `cpage_id` = '0'";
		$results	= $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				switch ($result["option_title"]) {
					case "show_announcements" :
						$page_options["show_announcements"] = $result;
					break;
					case "show_events" :
						$page_options["show_events"] = $result;
					break;
					case "show_history" :
						$page_options["show_history"] = $result;
					break;
					default :
						continue;
					break;
				}
			}
		}
	} else {
		if ($PAGE_TYPE == "announcements" || $PAGE_TYPE == "events") {
			if (!array_key_exists('allow_member_posts', $page_options)) {
			$db->Execute("INSERT INTO `community_page_options` SET `community_id` = ".$db->qstr($COMMUNITY_ID).", `cpage_id` = ".$db->qstr($PAGE_ID).", `option_title` = 'allow_member_posts', `option_value` = '0'");
			$page_options["allow_member_posts"] = Array ('cpoption_id' 	=> $db->insert_id(),
														 'community_id' => $COMMUNITY_ID,
														 'cpage_id' 	=> $PAGE_ID,
														 'option_title' => "allow_member_posts",
														 'option_value' => 0,
														 'proxy_id'		=> 0,
														 'updated_date' => 0
														);
			}

			if (!array_key_exists('allow_troll_posts', $page_options)) {
				$db->Execute("INSERT INTO `community_page_options` SET `community_id` = ".$db->qstr($COMMUNITY_ID).", `cpage_id` = ".$db->qstr($PAGE_ID).", `option_title` = 'allow_troll_posts', `option_value` = '0'");
				$page_options["allow_troll_posts"] = Array ('cpoption_id' 	=> $db->insert_id(),
															 'community_id' => $COMMUNITY_ID,
															 'cpage_id' 	=> $PAGE_ID,
															 'option_title' => "allow_troll_posts",
															 'option_value' => 0,
															 'proxy_id'		=> 0,
															 'updated_date' => 0
															);
			}

			if (!array_key_exists('moderate_posts', $page_options)) {
				$db->Execute("INSERT INTO `community_page_options` SET `community_id` = ".$db->qstr($COMMUNITY_ID).", `cpage_id` = ".$db->qstr($PAGE_ID).", `option_title` = 'moderate_posts', `option_value` = '0'");
				$page_options["moderate_posts"] = Array ('cpoption_id' 	=> $db->insert_id(),
															 'community_id' => $COMMUNITY_ID,
															 'cpage_id' 	=> $PAGE_ID,
															 'option_title' => "moderate_posts",
															 'option_value' => 0,
															 'proxy_id'		=> 0,
															 'updated_date' => 0
															);
			}
		} elseif ($PAGE_TYPE == "url") {
			$query		= "SELECT * FROM `community_page_options` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID);
			$results	= $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
							$page_options[$result["option_title"]] = $result;
				}
			}
			if (!array_key_exists('new_window', $page_options)) {
				$db->Execute("INSERT INTO `community_page_options` SET `community_id` = ".$db->qstr($COMMUNITY_ID).", `cpage_id` = ".$db->qstr($PAGE_ID).", `option_title` = 'new_window', `option_value` = '0'");
				$page_options["moderate_posts"] = Array ('cpoption_id' 	=> $db->insert_id(),
															 'community_id' => $COMMUNITY_ID,
															 'cpage_id' 	=> $PAGE_ID,
															 'option_title' => "new_window",
															 'option_value' => 0,
															 'proxy_id'		=> 0,
															 'updated_date' => 0
															);
			}
		}
	}


	if (($COMMUNITY_ID) && ($PAGE_ID)) {
		$query				= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `community_active` = '1'";
		$community_details	= $db->GetRow($query);
		if ($community_details) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/community".$community_details["community_url"], "title" => limit_chars($community_details["community_title"], 50));
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/community".$community_details["community_url"].":pages", "title" => "Manage Pages");

			$query	= "	SELECT * FROM `community_members`
						WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND `member_active` = '1'
						AND `member_acl` = '1'";
			$result	= $db->GetRow($query);
			if ($result && ($PAGE_ID != "home") && (isset($page_type) && $page_type != "home")) {
				$query			= "SELECT * FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_active` = '1'";
				$page_details	= $db->GetRow($query);
				if ($page_details) {
					load_rte(($PAGE_TYPE == "default" || $PAGE_TYPE == "course") ? "communityadvanced" : "communitybasic");

					$BREADCRUMB[]	= array("url" => "", "title" => "Edit Page");

					if (!isset($PAGE_TYPE) || $page_details["page_type"] == "course") {
						$PAGE_TYPE = $page_details["page_type"];
						$results = $db->GetAll("SELECT `course_id` FROM `community_courses` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID));
				        $course_ids = array();
				        $course_ids_string = "";
				        foreach($results as $course_id) {
				        	$course_ids[] = $course_id["course_id"];
				        	if ($course_ids_string) {
				        		$course_ids_string .= ",".$course_id["course_id"];
				        	} else {
				        		$course_ids_string .= $course_id["course_id"];
				        	}
				        }

						$query = "	SELECT `organisation_id` FROM `courses` WHERE `course_id` = ".$db->qstr($results[0]["course_id"]);
						$org_id = $db->GetOne($query);
                        if ($PAGE_TYPE == "course" && $page_details["page_url"] == "objectives") {
                            list($course_objectives,$top_level_id) = courses_fetch_objectives($org_id,$course_ids, -1,1, false, false, 0, true);
                        }
					}


					// Error Checking
					switch($STEP) {
						case 2 :
							/**
							 * The "course" page type is meant to have more static unchangeable poperties than
							 * a normal page, so the page_type, menu_title, permissions, and page_title
							 * will not be set when the page type is currently set to "course"
							 */
							if (array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) {
								/**
								 * Required field "page_type" / Page Type (Unchangeable for course content pages).
								 */
								foreach ($PAGE_TYPES as $PAGE) {
									if (isset($_POST["page_type"]) && $_POST["page_type"] == "course") {
										$PROCESSED["page_type"] = "course";
										break;
									} else if ((isset($_POST["page_type"])) && (is_array($PAGE)) && (array_search(trim($_POST["page_type"]), $PAGE))) {
										$PROCESSED["page_type"] = trim($_POST["page_type"]);
										break;
									}
								}
								if (!array_key_exists("page_type", $PROCESSED)) {
									$ERROR++;
									$ERRORSTR[] = "The <strong>Page Type</strong> field is required and is either empty or an invalid value.";
								}

								$PROCESSED["page_navigation"] = array();
								$nav_elements = array();
								if ((isset($_POST["show_right_nav"]))) {
									$show_right_nav = clean_input($_POST["show_right_nav"], array("trim", "notags"));
									if ((isset($_POST["selected_nav_next_page_id"]))) {
										$nav_next_page_id = $_POST["selected_nav_next_page_id"];
									} else {
										$nav_next_page_id = "";
									}
									if ($show_right_nav == 0) {
										$nav_elements[] = array("nav_type" => "next", "nav_title" => "Next", "show_nav" => "0", "cpage_id" => $nav_next_page_id);
									} else {
										$nav_elements[] = array("nav_type" => "next", "nav_title" => "Next", "show_nav" => "1", "cpage_id" => $nav_next_page_id);
									}
								} else {
									$nav_elements[] = array("nav_type" => "next", "nav_title" => "Next", "show_nav" => "0", "cpage_id" => "");
								}
								if ((isset($_POST["show_left_nav"]))) {
									$show_left_nav = clean_input($_POST["show_left_nav"], array("trim", "notags"));
									if ((isset($_POST["selected_nav_previous_page_id"]))) {
										$nav_previous_page_id = $_POST["selected_nav_previous_page_id"];
									} else {
										$nav_previous_page_id = "";
									}
									if ($show_left_nav == 0) {
										$nav_elements[] = array("nav_type" => "previous", "nav_title" => "Previous", "show_nav" => "0", "cpage_id" => $nav_previous_page_id);
									} else {
										$nav_elements[] = array("nav_type" => "previous", "nav_title" => "Previous", "show_nav" => "1", "cpage_id" => $nav_previous_page_id);
									}
								} else {
									$nav_elements[] = array("nav_type" => "previous", "nav_title" => "Previous", "show_nav" => "0", "cpage_id" => "");
								}

								/**
								 * Required field "parent_id" / Page Parent.
								 */
								if (isset($_POST["parent_id"]) && !$home_page) {
									if ($parent_id = clean_input($_POST["parent_id"], array("trim", "int"))) {
										$PROCESSED["parent_id"] = $parent_id;
									} else {
										$PROCESSED["parent_id"] = 0;
									}
								} else {
									$PROCESSED["parent_id"] = 0;
								}

								/**
								 * Required field "menu_title" / Menu Title.
								 *  note: page_url is not changed for home page
								 */
								if ((isset($_POST["menu_title"])) && ($menu_title = clean_input($_POST["menu_title"], array("trim", "notags")))) {
									$PROCESSED["menu_title"] = $menu_title;

									if (($PROCESSED["menu_title"] != $page_details["menu_title"]) && !$home_page) {
										$page_url = clean_input($PROCESSED["menu_title"], array("lower","underscores","page_url"));
										if ($page_details["parent_id"] != 0) {
											$query = "SELECT `page_url` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($page_details["parent_id"]);
											if ($parent_url = $db->GetOne($query)) {
												$page_url = $parent_url . "/" . $page_url;
											}
										}
										if (in_array($page_url, $COMMUNITY_RESERVED_PAGES)) {
											$ERROR++;
											$ERRORSTR[] = "The <strong>Menu Title</strong> you have chosen is reserved. Please enter a new menu title.";
										} else {
											$query	= "SELECT * FROM `community_pages` WHERE `page_url` = ".$db->qstr($page_url)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` != ".$db->qstr($PAGE_ID);
											$result	= $db->GetRow($query);
											if ($result) {
												$ERROR++;
												$ERRORSTR[] = "A similar <strong>Menu Title</strong> already exists in this community; menu titles must be unique.";
											} else {
												$PROCESSED["page_url"] = $page_url;
											}
										}
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>Menu Title</strong> field is required.";
								}

								/**
								 * Non-required fields view page access for members, non-members and public
								 *  - cannot be changed for home page
								 */
								if (!$home_page) {
									if ((isset($_POST["allow_member_view"])) && ((int) $_POST["allow_member_view"])) {
										$PROCESSED["allow_member_view"] = 1;
									} else {
										$PROCESSED["allow_member_view"] = 0;
									}
									if (!(int) $community_details["community_registration"]) {
										if ((isset($_POST["allow_troll_view"])) && ((int) $_POST["allow_troll_view"])) {
											$PROCESSED["allow_troll_view"] = 1;
										} else {
											$PROCESSED["allow_troll_view"] = 0;
										}
									}
									if (!(int) $community_details["community_protected"]) {
										if ((isset($_POST["allow_public_view"])) && ((int) $_POST["allow_public_view"])) {
											$PROCESSED["allow_public_view"] = 1;
										} else {
											$PROCESSED["allow_public_view"] = 0;
										}
									}
								}

								/**
								 * Non-required field "page_title" / Page Title.
								 */
								if ((isset($_POST["page_title"])) && ($page_title = clean_input($_POST["page_title"], array("trim", "notags")))) {
									$PROCESSED["page_title"] = $page_title;
								} else {
									$PROCESSED["page_title"] = "";
								}
							}

							/**
							 * If the page type is an external URL the data will come in from a different field than if it is
							 * another type of page that actually holds content.
							 */
							if ($PAGE_TYPE == "url") {
								/**
								 * Required "page_url" / Page URL.
								 */
								if ((isset($_POST["page_content"])) && ($page_content = clean_input($_POST["page_content"], array("nows", "notags")))) {
									if (preg_match("/[\w]{3,5}[\:]{1}[\/]{2}/", $page_content) == 1) {
										$PROCESSED["page_content"] = $page_content;
									} else {
										$PROCESSED["page_content"] = "http://" . $page_content;
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>External URL</strong> field is required, please enter a valid website address.";
								}
							}  elseif ($PAGE_TYPE == "lticonsumer") {
								$ltiJSONArray = array();
								if ((isset($_POST["lti_url"])) && ($lti_url = clean_input($_POST["lti_url"], array("trim", "notags")))) {
									$ltiJSONArray["lti_url"] = $lti_url;
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>LTI Launch URL</strong> field is required, please enter a valid URL address.";
								}

								if ((isset($_POST["lti_key"])) && ($lti_key = clean_input($_POST["lti_key"], array("trim", "notags")))) {
									$ltiJSONArray["lti_key"] = $lti_key;
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>LTI Key</strong> field is required, please enter a key.";
								}

								if ((isset($_POST["lti_secret"])) && ($lti_secret = clean_input($_POST["lti_secret"], array("trim", "notags")))) {
									$ltiJSONArray["lti_secret"] = $lti_secret;
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>LTI Secret</strong> field is required, please enter a secret.";
								}

								if (isset($_POST["lti_params"])) {
									$ltiJSONArray["lti_params"] = $_POST["lti_params"];
								}

								$PROCESSED["page_content"] = json_encode($ltiJSONArray);
							} elseif ($PAGE_TYPE == "default") {
								/**
								 * Non-Required "page_content" / Page Contents.
								 */
								if (isset($_POST["page_content"]) && (trim($_POST["page_content"]))) {
									$PROCESSED["page_content"]	= clean_input($_POST["page_content"], array("trim", "allowedtags"));
								} else {
									$PROCESSED["page_content"]	= "";

									$NOTICE++;
									$NOTICESTR[] = "The <strong>Page Content</strong> field is empty, which means that nothing will show up on this page.";
								}
							} else {
								/**
								 * Non-Required "page_content" / Page Contents.
								 */
								if (isset($_POST["page_content"])) {
									$PROCESSED["page_content"] = clean_input($_POST["page_content"], array("trim", "allowedtags"));
								} else {
									$PROCESSED["page_content"] = "";
								}
							}
							if (!$home_page && array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) {
								if ($_POST["page_visibile"] == '0') {
									$PROCESSED["page_visible"] = 0;
								} else {
									$PROCESSED["page_visible"] = 1;
								}
							}

							if (!$ERROR) {
								if (array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false && !$home_page) {
									/**
									 * Non-required "page_order" / Page Position.
									 * This special field will change the order which this page will appear under the parent.
									 * Don't get confused though, because page_order isn't the actual number, you've still got
									 * to do some math ;)
									 *
									 * note: this is never changed for the home page, it should always be 0.
									 */
									if ((isset($_POST["page_order"])) && ($_POST["page_order"] != "no") && !$home_page) {
										$page_order = clean_input($_POST["page_order"], array("trim", "int"));
										if ($page_order == 0) {
											$first_available = $db->GetOne("SELECT MAX(`page_order`) FROM `community_pages` WHERE `page_type` = 'course' AND `community_id` = ".$db->qstr($COMMUNITY_ID));
											if ($first_available) {
												$page_order = (int)$first_available + 1;
											}
										}

										if ($PROCESSED["parent_id"] == $page_details["parent_id"]) {

											$PROCESSED["page_order"] = $page_order;

											/**
											 * Go through this process the first time to put each page in the proper order.
											 */
											$query		= "SELECT `cpage_id`, `page_order` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `parent_id` = ".$db->qstr($PROCESSED["parent_id"])." AND `page_order` >= ".$PROCESSED["page_order"]." AND `page_url` != '' ORDER BY `page_order` ASC";
											$results	= $db->GetAll($query);
											if ($results) {
												foreach ($results as $result) {
													$query = "UPDATE `community_pages` SET `page_order` = ".$db->qstr(($result["page_order"] + 1))." WHERE `cpage_id` = ".$db->qstr($result["cpage_id"]);
													if (!$db->Execute($query)) {
														application_log("error", "Unable to update the page order of page_id ".$result["page_id"]);
													}
												}
											}
										} else {
											$NOTICE++;
											$NOTICESTR[] = "You cannot update the <strong>Page Position</strong> of this page if you have also changed the <strong>Page Parent</strong>. The new page position was disregarded.";
										}
									}

									if ($PROCESSED["parent_id"] != $page_details["parent_id"] && !$home_page) {
										$query	= "SELECT COUNT(*) AS `new_order` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `parent_id` = ".$db->qstr($PROCESSED["parent_id"])." AND `page_url` != ''";
										$result	= $db->GetRow($query);
										if ($result) {
											$PROCESSED["page_order"] = (int) $result["new_order"];
										} else {
											$PROCESSED["page_order"] = 0;
										}

										$query			= "SELECT `cpage_id`, `page_order` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `parent_id` = ".$db->qstr($page_details["parent_id"])." AND `page_order` > ".$db->qstr($page_details["page_order"])." AND `page_url` != ''";
										$moving_pages	= $db->GetAll($query);
										if ($moving_pages) {
											foreach ($moving_pages as $moving_page) {
												$query = "UPDATE `community_pages` SET `page_order` = ".$db->qstr($moving_page["page_order"] - 1)." WHERE `cpage_id` = ".$db->qstr($moving_page["cpage_id"]);
												$db->Execute($query);
											}
										}
									}
								}
								if ($home_page) {
									/**
									 * Non-required fields for various page options of what to display on default home pages
									 */
									if ((isset($_POST["show_announcements"])) && ((int) $_POST["show_announcements"])) {
										$page_options["show_announcements"]["option_value"] = 1;
									} else {
										$page_options["show_announcements"]["option_value"] = 0;
									}
									if ((isset($_POST["show_events"])) && ((int) $_POST["show_events"])) {
										$page_options["show_events"]["option_value"] = 1;
									} else {
										$page_options["show_events"]["option_value"] = 0;
									}
									if ((isset($_POST["show_history"])) && ((int) $_POST["show_history"])) {
										$page_options["show_history"]["option_value"] = 1;
									} else {
										$page_options["show_history"]["option_value"] = 0;
									}
								} elseif ($PAGE_TYPE ==  "announcements" || $PAGE_TYPE == "events") {
									/**
									 * Non-required fields for various page options of what to display on default home pages
									 */
									if ((isset($_POST["allow_member_posts"])) && ((int) $_POST["allow_member_posts"])) {
										$page_options["allow_member_posts"]["option_value"] = 1;
									} else {
										$page_options["allow_member_posts"]["option_value"] = 0;
									}
									if ((isset($_POST["allow_troll_posts"])) && ((int) $_POST["allow_troll_posts"])) {
										$page_options["allow_troll_posts"]["option_value"] = 1;
									} else {
										$page_options["allow_troll_posts"]["option_value"] = 0;
									}
									if ((isset($_POST["moderate_posts"])) && ((int) $_POST["moderate_posts"])) {
										$page_options["moderate_posts"]["option_value"] = 1;
									} else {
										$page_options["moderate_posts"]["option_value"] = 0;
									}
								} elseif ($PAGE_TYPE ==  "url") {
									/**
									 * Non-required fields for various page options of what to display on default home pages
									 */
									if ((isset($_POST["new_window"])) && ((int) $_POST["new_window"])) {
										$page_options["new_window"]["option_value"] = 1;
									} else {
										$page_options["new_window"]["option_value"] = 0;
									}
								}

								/**
								 * page_id
								 * community_id
								 * page_type
								 * page_order
								 * page_url
								 * menu_title
								 * page_title
								 * page_content
								 * page_visible
								 * allow_member_view
								 * allow_troll_view
								 * allow_public_view
								 * updated_by
								 * updated_date
								 */
								$PROCESSED["updated_date"]	= time();
								$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

								if ($db->AutoExecute("community_pages", $PROCESSED, "UPDATE", "cpage_id = ".$PAGE_ID)) {
									if ($home_page) {
										if ($PAGE_TYPE == "default" || $PAGE_TYPE == "course") {
											if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["show_announcements"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["show_announcements"]["cpoption_id"]))) {
												if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["show_events"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["show_events"]["cpoption_id"]))) {
													if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["show_history"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["show_history"]["cpoption_id"]))) {
														if (!$ERROR) {
															Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully updated the home page of the community."), "success", $MODULE);

                                                            communities_log_history($COMMUNITY_ID, 0, 0, "community_history_edit_home_page", 1);
                                                            application_log("success", "Home Page [".$PAGE_ID."] updated in the system.");

															$url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
															header("Location: " . $url);
															exit;
														}
													} else {
														$ERROR++;
														$ERRORSTR[] = "There was a problem updating the 'show history' option for the home page of the community. Please contact the application administrator and inform them of this error.";

														application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
													}
												} else {
													$ERROR++;
													$ERRORSTR[] = "There was a problem updating the 'show events' option for the home page of the community. Please contact the application administrator and inform them of this error.";

													application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating the 'show announcements' option for the home page of the community. Please contact the application administrator and inform them of this error.";

												application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
											}
										} elseif ($PAGE_TYPE == "announcements" || $PAGE_TYPE == "events") {
											if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["moderate_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["moderate_posts"]["cpoption_id"]))) {
												if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["allow_member_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["allow_member_posts"]["cpoption_id"]))) {
													if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["allow_troll_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["allow_troll_posts"]["cpoption_id"]))) {
														if (!$ERROR) {
                                                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully updated the home page of the community."), "success", $MODULE);

															application_log("success", "Home Page [".$PAGE_ID."] updated in the system.");

                                                            $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
                                                            header("Location: " . $url);
															exit;
														}
													} else {
														$ERROR++;
														$ERRORSTR[] = "There was a problem updating the 'moderate posts' option for the home page of the community. Please contact the application administrator and inform them of this error.";

														application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
													}
												} else {
													$ERROR++;
													$ERRORSTR[] = "There was a problem updating the 'allow member posts' option for the home page of the community. Please contact the application administrator and inform them of this error.";

													application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating the 'allow non-member posts' option for the home page of the community. Please contact the application administrator and inform them of this error.";

												application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
											}
										} else {
											if (!$ERROR) {
												Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully updated the home page of the community."), "success", $MODULE);

												application_log("success", "Home Page [".$PAGE_ID."] updated in the system.");

                                                $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
                                                header("Location: " . $url);
												exit;
											}
										}
									} else {
										communities_log_history($COMMUNITY_ID, $PAGE_ID, 0, "community_history_edit_page", 1);
										if ($PROCESSED["menu_title"] != $page_details["menu_title"]) {
											communities_set_children_urls($PAGE_ID, $PROCESSED["page_url"]);
										}
										if ((isset($PROCESSED["page_order"])) && ($PROCESSED["page_order"] != $page_details["page_order"])) {
											/**
											 * Go through this process the second time to ensure each page is in the correct order.
											 */
											$query		= "SELECT `cpage_id`, `page_order` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `parent_id` = ".$db->qstr($PROCESSED["parent_id"])." AND `page_url` != '' ORDER BY `page_order` ASC";
											$results	= $db->GetAll($query);
											if ($results) {
												foreach ($results as $key => $result) {
													$order = $key;
													if ((int) $order != (int) $result["page_order"]) {
														$query = "UPDATE `community_pages` SET `page_order` = ".$db->qstr($order)." WHERE `cpage_id` = ".$db->qstr($result["cpage_id"]);
														if (!$db->Execute($query)) {
															application_log("error", "Unable to update the page order of page_id ".$result["page_id"]);
														}
													}
												}
											}
										}
										if ($PAGE_TYPE == "announcements" || $PAGE_TYPE == "events") {
											if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["moderate_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["moderate_posts"]["cpoption_id"]))) {
												if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["allow_member_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["allow_member_posts"]["cpoption_id"]))) {
													if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["allow_troll_posts"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["allow_troll_posts"]["cpoption_id"]))) {
														if (!$ERROR) {
															Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["menu_title"]), "success", $MODULE);

															application_log("success", "Page [".$PAGE_ID."] updated in the system.");

															$url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
                                                            header("Location: " . $url);
															exit;
														}
													} else {
														$ERROR++;
														$ERRORSTR[] = "There was a problem updating the 'moderate posts' option for this page of the community. Please contact the application administrator and inform them of this error.";

														application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
													}
												} else {
													$ERROR++;
													$ERRORSTR[] = "There was a problem updating the 'allow member posts' option this page of the community. Please contact the application administrator and inform them of this error.";

													application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating the 'allow non-member posts' option for this page of the community. Please contact the application administrator and inform them of this error.";

												application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
											}
										} elseif ($PAGE_TYPE == "course" && $page_details["page_url"] == "objectives") {
											foreach ($course_ids as $course_id) {
												if (isset($_POST["course_objectives"]) && ($objectives = $_POST["course_objectives"]) && (is_array($objectives))) {
													foreach ($objectives as $objective => $status) {
														if ($objective) {
															if (isset($_POST["objective_text"][$objective]) && $_POST["objective_text"][$objective]) {
																$objective_text = $_POST["objective_text"][$objective];
															} else {
																$objective_text = false;
															}
															$PROCESSED_OBJECTIVES[$objective] = $objective_text;
														}
													}
												}
												if (is_array($PROCESSED_OBJECTIVES)) {
													foreach ($PROCESSED_OBJECTIVES as $objective_id => $objective) {
														$objective_found = $db->GetOne("SELECT `objective_id` FROM `course_objectives` WHERE `objective_id` = ".$db->qstr($objective_id)." AND `course_id` = ".$db->qstr($course_id));
														if ($objective_found) {
															$db->AutoExecute("course_objectives", array("objective_details" => $objective, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`objective_id` = ".$db->qstr($objective_id)." AND `course_id` = ".$db->qstr($course_id));
														} else {
															$db->AutoExecute("course_objectives", array("course_id" => $course_id, "objective_id" => $objective_id, "objective_details" => $objective, "importance" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT");
														}
													}
													foreach ($course_objectives["used_ids"] as $objective_id) {
														if (!array_key_exists($objective_id, $PROCESSED_OBJECTIVES)) {
															$db->AutoExecute("course_objectives", array("objective_details" => "", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`objective_id` = ".$db->qstr($objective_id)." AND `course_id` = ".$db->qstr($course_id));
														}
													}
												}
											}
											if (!$ERROR) {
												Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["menu_title"]), "success", $MODULE);

												application_log("success", "Page [".$PAGE_ID."] updated in the system.");

                                                $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
                                                header("Location: " . $url);
												exit;
											}
										} elseif ($PAGE_TYPE == "url") {
											if ($db->Execute("UPDATE `community_page_options` SET `option_value` = ".$db->qstr($page_options["new_window"]["option_value"])." WHERE `cpoption_id` = ".$db->qstr($page_options["new_window"]["cpoption_id"]))) {
												if (!$ERROR) {
													Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["menu_title"]), "success", $MODULE);

													application_log("success", "Page [".$PAGE_ID."] updated in the system.");

                                                    $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
													header("Location: " . $url);
													exit;
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating the 'open in new window' option for the current page in the community. The application administrator has been informed them of this error.";

												application_log("error", "There was an error updating this page option. Database said: ".$db->ErrorMsg());
											}
										} elseif (!$ERROR) {
											Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["menu_title"]), "success", $MODULE);

											application_log("success", "Page [".$PAGE_ID."] updated in the system.");

                                            $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
                                            header("Location: " . $url);
											exit;
										}
									}

									$default_next_page = get_next_community_page($COMMUNITY_ID, $PAGE_ID, $page_details["parent_id"], $page_details["page_order"]);
									$default_previous_page = get_prev_community_page($COMMUNITY_ID, $PAGE_ID, $page_details["parent_id"], $page_details["page_order"]);

                                    if (isset($nav_elements) && is_array($nav_elements)) {
                                        foreach($nav_elements as $n) {
                                            $PROCESSED["page_navigation"] = array();
                                            $PROCESSED["page_navigation"]["cpage_id"] = $PAGE_ID;
                                            $PROCESSED["page_navigation"]["nav_page_id"] = $n["cpage_id"];
                                            $PROCESSED["page_navigation"]["community_id"] = $COMMUNITY_ID;
                                            $PROCESSED["page_navigation"]["nav_type"] = $n["nav_type"];
                                            $PROCESSED["page_navigation"]["nav_title"] = $n["nav_title"];
                                            $PROCESSED["page_navigation"]["show_nav"] = $n["show_nav"];
                                            $PROCESSED["page_navigation"]["updated_date"] = time();
                                            $PROCESSED["page_navigation"]["updated_by"] = $ENTRADA_USER->getID();

                                            $query = "	SELECT *
                                                        FROM `community_page_navigation`
                                                        WHERE `cpage_id` = " . $db->qstr($PAGE_ID) . "
                                                        AND `nav_type` = " . $db->qstr($n["nav_type"]);

                                            $result = $db->GetRow($query);

                                            if (isset($COMMUNITY_TYPE_OPTIONS["sequential_navigation"])) {
                                                $update_sql = 0;
                                                $insert_sql = 0;
                                                if ($result) {
                                                    $update_sql = $db->AutoExecute("community_page_navigation", $PROCESSED["page_navigation"], "UPDATE", "cpage_id = " . $db->qstr($PAGE_ID) . " AND " . "nav_type = " . $db->qstr($n["nav_type"]));
                                                } else {
                                                    $insert_sql = $db->AutoExecute("community_page_navigation", $PROCESSED["page_navigation"], "INSERT");
                                                }
                                                if (!$update_sql && !$insert_sql) {
                                                    $ERROR++;
                                                    $ERRORSTR[] = "There was a problem updating the page navigation. The application administrator has been informed them of this error.";

                                                    application_log("error", "There was a problem updating the page navigation for cpage_id: " . $PAGE_ID . ". Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }
                                    }
								} else {
									$ERROR++;
									$ERRORSTR[] = "There was a problem updating this page of the community. The application administrator has been informed them of this error.";

									application_log("error", "There was an error updating this page. Database said: ".$db->ErrorMsg());
								}
							}

							if ($ERROR) {
								$STEP = 1;
							}
						break;
						case 1 :
						default :
							$PROCESSED = $page_details;

							$query			= "SELECT *
												FROM `community_page_navigation`
												WHERE `cpage_id` = ".$db->qstr($PAGE_ID)."
												AND `community_id` = ".$db->qstr($COMMUNITY_ID);
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									$PROCESSED["page_navigation"]["show_" . $result["nav_type"] . "_nav"] = $result["show_nav"];
									if ($result["nav_type"] == "next") {
										$nav_next_page_id = $result["nav_page_id"];
									} elseif ($result["nav_type"] == "previous") {
										$nav_previous_page_id = $result["nav_page_id"];
									}
								}
							}
							if ((isset($PAGE_TYPE)) && ($PAGE_TYPE != "")) {
								$PROCESSED["page_type"] = $PAGE_TYPE;
							}

							$default_next_page = get_next_community_page($COMMUNITY_ID, $PAGE_ID, $page_details["parent_id"], $page_details["page_order"]);
							$default_previous_page = get_prev_community_page($COMMUNITY_ID, $PAGE_ID, $page_details["parent_id"], $page_details["page_order"]);
						break;
					}

					//Display Page
					switch($STEP) {
						case 1:
						default:
							// echo print_r($PAGE_TYPES)."<br />";//[3]);//["module_title"])."\n";
							// echo $page_details["page_type"]."<br />";
							// $key = searchForId($page_details["page_type"], $PAGE_TYPES, "module_shortname");
							// echo $PAGE_TYPES[$key]['module_title'];
							// echo $_POST["page_type"];
							if ($NOTICE) {
								echo display_notice();
							}

							if ($ERROR) {
								echo display_error();
							}

							if ($page_type == "course" && $page_details["page_url"] == "objectives") {
								require_once("../javascript/courses.js.php");
							}

							$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/community/javascript/page_navigation.js\"></script>\n";
							?>
							<script type="text/javascript">
							var text = new Array();

							function objectiveClick(element, id, default_text) {
								if (element.checked) {
									var textarea = document.createElement('textarea');
									textarea.name = 'objective_text['+id+']';
									textarea.id = 'objective_text_'+id;
									if (text[id] != null) {
										textarea.innerHTML = text[id];
									} else {
										textarea.innerHTML = default_text;
									}
									textarea.className = "expandable objective";
									$('objective_'+id+'_append').insert({after: textarea});
									setTimeout('jQuery("#objective_text_'+id+'").textareaAutoSize();', 100);
								} else {
									if ($('objective_text_'+id)) {
										text[id] = $('objective_text_'+id).value;
										$('objective_text_'+id).remove();
									}
								}
							}

							function parentChange (parent_id) {
								var page_order = ($('page_order').value != 'no' ? $('page_order').value : <?php echo $page_details["page_order"]; ?>);
								if (parent_id == <?php echo $PROCESSED["parent_id"]?>) {
									$('page_order').disabled = false; } else { $('page_order').disabled = true;
								}
								new Ajax.Updater('modal_page_navigation','<?php echo ENTRADA_URL; ?>/api/community-page-navigation.api.php', {
									method: 'post',
									parameters: {cpage_id: <?php echo $PAGE_ID; ?>, parent_id: parent_id, page_order: page_order, nav_type: 'next'}
								});
								new Ajax.Updater('modal_previous_page_navigation','<?php echo ENTRADA_URL; ?>/api/community-page-navigation.api.php', {
									method: 'post',
									parameters: {cpage_id: <?php echo $PAGE_ID; ?>, parent_id: parent_id, page_order: page_order, nav_type: 'previous'}
								});
							}

							function orderChange (page_order) {
								var parent_id = $('parent_id').value;
								new Ajax.Request('<?php echo ENTRADA_URL; ?>/api/community-page-navigation.api.php', {
									method: 'post',
									parameters: {cpage_id: <?php echo $PAGE_ID; ?>, parent_id: parent_id, page_order: page_order, nav_type: 'next_id'},
									onSuccess: function (response) {
										if (response && response.responseText) {
											$$('input:checked[type="radio"][name="nav_next_page_id"]').each(function(radio){ radio.checked = false; });
											$('nav_next_page_id'+response.responseText).checked = true;
										}
									}
								});
								new Ajax.Request('<?php echo ENTRADA_URL; ?>/api/community-page-navigation.api.php', {
									method: 'post',
									parameters: {cpage_id: <?php echo $PAGE_ID; ?>, parent_id: parent_id, page_order: page_order, nav_type: 'previous_id'},
									onSuccess: function (response) {
										if (response && response.responseText) {
											$$('input:checked[type="radio"][name="nav_previous_page_id"]').each(function(radio){ radio.checked = false; });
											$('nav_previous_page_id'+response.responseText).checked = true;
											$('content_previous_page_list_'+response.responseText).up('li.parent_'+parent_id).insert($('content_previous_page_list_<?php echo $PAGE_ID; ?>'));
											$('content_next_page_list_'+response.responseText).up('li.parent_'+parent_id).insert($('content_next_page_list_<?php echo $PAGE_ID; ?>'));
										}
									}
								});
							}
							</script>
							<h1>Edit Community Page</h1>
							<form id="edit_page_form" action="<?php echo ENTRADA_URL."/community".$community_details["community_url"].":pages?".replace_query(array("action" => "edit", "step" => 2)); ?>" method="post" enctype="multipart/form-data" onsubmit="selIt()">
								<table summary="Editing Page">
									<colgroup>
										<col style="width: 20%" />
										<col style="width: 80%" />
									</colgroup>
									<tfoot>
										<tr>
											<td colspan="1" style="padding-top: 15px; text-align: left">
												<a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>" class="btn button-left"><?php echo $translate->_("global_button_cancel"); ?></a>
											</td>
											<td colspan="2" style="padding-top: 15px; text-align: right">
		                                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
											</td>
										</tr>
									</tfoot>
									<tbody>
										<tr>
											<td style="vertical-align: middle">
												<label for="page_type" class="form-required">Page Type:</label>
											</td>
											<td style="vertical-align: middle">
												<?php
												if ((is_array($PAGE_TYPES)) && (count($PAGE_TYPES))) {
													echo "<select id=\"page_type\" name=\"page_type\" style=\"width: 312px\" onchange=\"window.location = '".COMMUNITY_URL.$COMMUNITY_URL.":pages?section=edit&page=".($home_page ? "home" : $PAGE_ID)."&type='+this.options[this.selectedIndex].value\" ".(array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) !== false ? "disabled=\"disabled\" " : "").">\n";
													foreach ($PAGE_TYPES as $page_type_info) {
														echo "<option value=\"".html_encode($page_type_info["module_shortname"])."\"".(((isset($PAGE_TYPE)) && ($PAGE_TYPE == $page_type_info["module_shortname"])) || ((isset($page_details["page_type"])) && !isset($PAGE_TYPE) && ($page_details["page_type"] == $page_type_info["module_shortname"])) ? " selected=\"selected\"" : "").">".html_encode($page_type_info["module_title"])."</option>\n";
													}
													if ($PAGE_TYPE == "course") {
														echo "<option value=\"course\" selected=\"selected\">Course Content Page</option>\n";
													}
													echo "</select>";
													if (isset($PAGE_TYPES[$page_details["page_type"]]["description"])) {
														echo "<div class=\"content-small\" style=\"margin-top: 5px\">\n";
														echo "<strong>Page Type Description:</strong><br />".html_encode($PAGE_TYPES[$page_details["page_type"]]["description"]);
														echo "</div>\n";
													}
												} else {
													echo "<input type=\"hidden\" name=\"page_type\" value=\"default\" />\n";
													echo "<strong>Default Content Page</strong>\n";

													application_log("error", "No available page types during content page add or edit.");
												}
												?>
											</td>
										</tr>
										<?php
										if (!$home_page && array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) {
												?>
											<tr>
												<td style="vertical-align: middle">
													<label for="parent_id" class="form-required">Page Parent:</label>
												</td>
												<td style="vertical-align: middle">
													<select id="parent_id" name="parent_id" onchange="parentChange(this.value)" style="width: 312px">
													<?php
													echo "<option value=\"0\"".(!$page_details["parent_id"] ? " selected=\"selected\"" : "").">-- No Parent Page --</option>\n";

													$current_selected	= array($page_details["parent_id"]);
													$exclude			= array($PAGE_ID);

													echo communities_pages_inselect(0, $current_selected, 0, $exclude, $COMMUNITY_ID);
													?>
													</select>
												</td>
											</tr>
											<?php
										}
										?>
										<tr>
											<td style="vertical-align: middle">
												<label for="menu_title" class="form-required">Menu Title:</label>
											</td>
											<td style="vertical-align: middle">
												<input type="text" id="menu_title" name="menu_title" value="<?php echo ((isset($PROCESSED["menu_title"])) ? html_encode($PROCESSED["menu_title"]) : ((isset($page_details["menu_title"])) ? html_encode($page_details["menu_title"]) : "")); ?>" maxlength="32" style="width: 300px" onblur="fieldCopy('menu_title', 'page_title', 1)"<?php echo (array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) !== false ? " disabled=\"disabled\"" : ""); ?> />
											</td>
										</tr>
										<tr>
											<td style="vertical-align: middle">
												<label for="page_title" class="form-nrequired">Page Title:</label>
											</td>
											<td style="vertical-align: middle">
												<input type="text" id="page_title" name="page_title" value="<?php echo ((isset($PROCESSED["page_title"])) ? html_encode($PROCESSED["page_title"]) : ""); ?>" maxlength="100" style="width: 300px"<?php echo (array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) !== false ? " disabled=\"disabled\"" : ""); ?> />
											</td>
										</tr>
										<?php
										if ($PAGE_TYPE == "url") {
											?>
											<tr>
												<td style="vertical-align: middle">
													<label for="page_content" class="form-required">External URL:</label>
												</td>
												<td style="vertical-align: middle">
													<input type="text" id="page_content" name="page_content" style="width: 98%" value="<?php echo (((isset($PROCESSED["page_content"])) && ($page_details["page_type"] == "url")) ? html_encode($PROCESSED["page_content"]) : ""); ?>">
												</td>
											</tr>
											<?php
										} elseif($PAGE_TYPE == "lticonsumer") {
											$ltiSettings = null;
											if(isset($PROCESSED["page_content"]) && !empty($PROCESSED["page_content"])) {
												$ltiSettings = json_decode($PROCESSED["page_content"]);
											}
											?>
											<tr>
												<td style="vertical-align: middle">
													<label for="lti_url" class="form-required">LTI Launch URL:</label>
												</td>
												<td style="vertical-align: middle">
													<input type="text" id="lti_url" name="lti_url" style="width: 98%;" value="<?php echo ((isset($ltiSettings) && property_exists($ltiSettings, "lti_url")) ? html_encode($ltiSettings->lti_url) : ""); ?>" />
												</td>
											</tr>
											<tr>
												<td style="vertical-align: middle">
													<label for="lti_key" class="form-required">LTI Key:</label>
												</td>
												<td style="vertical-align: middle">
													<input type="text" id="lti_key" name="lti_key" style="width: 98%;" value="<?php echo ((isset($ltiSettings) && property_exists($ltiSettings, "lti_key")) ? html_encode($ltiSettings->lti_key) : ""); ?>" />
												</td>
											</tr>
											<tr>
												<td style="vertical-align: middle">
													<label for="lti_secret" class="form-required">LTI Secret:</label>
												</td>
												<td style="vertical-align: middle">
													<input type="text" id="lti_secret" name="lti_secret" style="width: 98%;" value="<?php echo ((isset($ltiSettings) && property_exists($ltiSettings, "lti_secret")) ? html_encode($ltiSettings->lti_secret) : ""); ?>" />
												</td>
											</tr>
											<tr>
												<td style="vertical-align: middle">
													<label for="lti_params">LTI Additional Parameters:</label>
												</td>
												<td style="vertical-align: middle">
													<textarea class="expandable" id="lti_params" name="lti_params" style="width: 98%;"><?php echo ((isset($ltiSettings)) ? html_encode($ltiSettings->lti_params) : ""); ?></textarea>
												</td>
											</tr>
											<?php
										} else {
											?>
											<tr>
												<td colspan="2">
													<label for="page_content" class="form-nrequired"><?php if ($PAGE_TYPE != "default"){ echo "Top of "; } ?>Page Content:</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">
		                                            <textarea id="page_content" name="page_content" style="margin-right: 10px;width: 95%; height: <?php echo (($PAGE_TYPE == "default") ? "400" : "200"); ?>px" rows="20" cols="70"><?php echo ((isset($PROCESSED["page_content"])) ? html_encode($PROCESSED["page_content"]) : ""); ?></textarea>
												</td>
											</tr>
											<?php
										}

										if (!$home_page && array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) {
											if ($PAGE_TYPE == "events" || $PAGE_TYPE == "announcements") {
												?>
												<tr>
													<td colspan="2">
														<h2>Page Permissions</h2>
													</td>
												</tr>
												<tr>
													<td colspan="2">
														<table class="table table-striped table-bordered">
															<colgroup>
																<col style="width: 50%" />
																<col style="width: 25%" />
																<col style="width: 25%" />
															</colgroup>
															<thead>
																<tr>
																	<td>Group</td>
																	<td>View Page</td>
																	<td>Post <?php echo ucfirst($PAGE_TYPE); ?></td>
																</tr>
															</thead>
															<tbody>
																<tr>
																	<td>
																		<strong>Community Administrators</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_admin_view" name="allow_admin_view" value="1" checked="checked" onclick="this.checked = true" />
																	</td>
																	<td>
																		<input type="checkbox" checked="checked" disabled="disabled"/>
																	</td>
																</tr>
																<tr>
																	<td>
																		<strong>Community Members</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_member_view" name="allow_member_view" value="1"<?php echo (((!isset($PROCESSED["allow_member_view"])) || ((isset($PROCESSED["allow_member_view"])) && ($PROCESSED["allow_member_view"] == 1))) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																	<td>
																		<input onclick="show_hide_moderation()" type="checkbox" id="allow_member_posts" name="allow_member_posts" value="1"<?php echo (((isset($page_options["allow_member_posts"]["option_value"])) && (((int)$page_options["allow_member_posts"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<?php if (!(int) $community_details["community_registration"]) : ?>
																<tr>
																	<td>
																		<strong>Browsing Non-Members</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_troll_view" name="allow_troll_view" value="1"<?php echo (((!isset($PROCESSED["allow_troll_view"])) || ((isset($PROCESSED["allow_troll_view"])) && ($PROCESSED["allow_troll_view"] == 1))) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																	<td>
																		<input onclick="show_hide_moderation()" type="checkbox" id="allow_troll_posts" name="allow_troll_posts" value="1"<?php echo (((isset($page_options["allow_troll_posts"]["option_value"])) && (((int)$page_options["allow_troll_posts"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<?php endif; ?>
																<?php if (!(int) $community_details["community_protected"]) :  ?>
																<tr>
																	<td class="left">
																		<strong>Non-Authenticated / Public Users</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_public_view" name="allow_public_view" value="1"<?php echo (((isset($PROCESSED["allow_public_view"])) && ($PROCESSED["allow_public_view"] == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																	<td>
																		<input type="checkbox" disabled="disabled"/>
																	</td>
																</tr>
																<?php endif; ?>
															</tbody>
														</table>
													</td>
												</tr>
												<script type="text/javascript">
													function show_hide_moderation() {
														if($('allow_member_posts').checked || ($('allow_troll_posts') && $('allow_troll_posts').checked)) {
															if (!$('moderate-posts-body').visible()) {
																Effect.BlindDown($('moderate-posts-body'), { duration: 0.3 });
															}
														} else {
															if ($('moderate-posts-body').visible()) {
																Effect.BlindUp($('moderate-posts-body'), { duration: 0.3 });
															}
														}
													}
												</script>
												<tr>
													<td colspan="2">
														<table class="table table-bordered no-thead" id="moderate-posts-body" <?php echo ((((isset($page_options["allow_troll_posts"]["option_value"])) && (((int)$page_options["allow_troll_posts"]["option_value"]) == 1)) && (!(int) $community_details["community_protected"])) || ((isset($page_options["allow_member_posts"]["option_value"])) && (((int)$page_options["allow_member_posts"]["option_value"]) == 1)) ? "" : " style=\"display: none;\""); ?>>
															<colgroup>
																<col style="width: 5%" />
																<col style="width: auto" />
															</colgroup>
															<tbody>
																<tr>
																	<td class="center">
																		<input type="checkbox" id="moderate_posts" name="moderate_posts" value="1"<?php echo (((isset($page_options["moderate_posts"]["option_value"])) && (((int)$page_options["moderate_posts"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																	<td>
																		<label class="form-nrequired">Require non-administrator <?php echo $PAGE_TYPE; ?> to be moderated</label>
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
												<?php
											} else {
												?>
												<tr>
													<td colspan="2">
														<h2>Page Permissions</h2>
													</td>
												</tr>
												<tr>
													<td colspan="2">
														<table class="table table-striped table-bordered">
															<colgroup>
																<col style="width: 50%" />
																<col style="width: 50%" />
															</colgroup>
															<thead>
																<tr>
																	<td>Group</td>
																	<td>View Page</td>
																</tr>
															</thead>
															<tbody>
																<tr>
																	<td>
																		<strong>Community Administrators</strong>
																	</td>
																	<td class="on"><input type="checkbox" id="allow_admin_view" name="allow_admin_view" value="1" checked="checked" onclick="this.checked = true" /></td>
																</tr>
																<tr>
																	<td>
																		<strong>Community Members</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_member_view" name="allow_member_view" value="1"<?php echo (((!isset($PROCESSED["allow_member_view"])) || ((isset($PROCESSED["allow_member_view"])) && ($PROCESSED["allow_member_view"] == 1))) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<?php if (!(int) $community_details["community_registration"]) : ?>
																<tr>
																	<td>
																		<strong>Browsing Non-Members</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_troll_view" name="allow_troll_view" value="1"<?php echo (((!isset($PROCESSED["allow_troll_view"])) || ((isset($PROCESSED["allow_troll_view"])) && ($PROCESSED["allow_troll_view"] == 1))) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<?php endif; ?>
																<?php if (!(int) $community_details["community_protected"]) :  ?>
																<tr>
																	<td>
																		<strong>Non-Authenticated / Public Users</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="allow_public_view" name="allow_public_view" value="1"<?php echo (((isset($PROCESSED["allow_public_view"])) && ($PROCESSED["allow_public_view"] == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<?php endif; ?>
															</tbody>
														</table>
													</td>
												</tr>
												<?php
											}
										} elseif ($PAGE_TYPE == "course" && strpos($page_record["page_url"], "objectives") !== false) {
										?>
												<tr>
													<td colspan="2">
														<br />
														<div class="display-notice">
															Please edit the Learning Objectives through the Manage Courses > Course Content tab found <a href="<?php echo ENTRADA_URL . "/admin/courses" ;?>">here</a>.
														</div>
													</td>
												</tr>
										<?php
										} elseif ($PAGE_TYPE == "course" && strpos($page_record["page_url"], "mcc_presentations") !== false) {
										?>
												<tr>
													<td colspan="2">
														<br />
														<div class="display-notice">
															Please edit the MCC Presentation Objectives through the Manage Courses > Course Content tab found <a href="<?php echo ENTRADA_URL . "/admin/courses" ;?>">here</a>.
														</div>
													</td>
												</tr>
									    <?php
										}
										?>
										<?php
										if (($PAGE_TYPE == "url") || (!$home_page && array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) || ($home_page && $PAGE_TYPE == "default")) {
											?>
												<tr>
													<td colspan="2"><h2>Page Options</h2></td>
												</tr>
											<?php
										}
										if ($PAGE_TYPE == "url") {
										?>
												<tr>
													<td colspan="2">
														<table class="table table-striped table-bordered">
															<colgroup>
																<col style="width: 50%" />
																<col style="width: 50%" />
															</colgroup>
															<thead>
																<tr>
																	<td>Additional Options</td>
																	<td>Status</td>
																</tr>
															</thead>
															<tbody>
																<tr>
																	<td><strong>Open page in new window.</strong></td>
																	<td class="on">
																		<input type="checkbox" id="new_window" name="new_window" value="1"<?php echo (((isset($page_options["new_window"]["option_value"])) && (((int)$page_options["new_window"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
										<?php
										} else if (isset($COMMUNITY_TYPE_OPTIONS["sequential_navigation"]) && $COMMUNITY_TYPE_OPTIONS["sequential_navigation"] == "1") { ?>
												<tr>
													<td>
														<label for="show_left_nav" class="form-nrequired">Show Left Navigation</label>
													</td>
													<td>
														<input id="show_left_nav" name="show_left_nav" type="checkbox" value="1"<?php echo (!isset($PROCESSED["page_navigation"]["show_previous_nav"]) || ((int) $PROCESSED["page_navigation"]["show_previous_nav"] == 1) ? " checked=\"checked\"" : ""); ?>/>
														<input class="btn" id="change_previous_nav_button" name="change_previous_nav_button" type="button" value="Previous Page" />
														<input type="hidden" name="selected_nav_previous_page_id" id="selected_nav_previous_page_id" <?php echo (isset($nav_previous_page_id) && $nav_previous_page_id ? "value = \"" . $nav_previous_page_id . "\"" : "value = \"" . (isset($default_previous_page["cpage_id"]) && $default_previous_page["cpage_id"] ? $default_previous_page["cpage_id"] : "")) . "\"" ?> />
													</td>
												</tr>
												<tr>
													<td>
														<label for="show_right_nav" class="form-nrequired">Show Right Navigation</label>
													</td>
													<td>
														<input id="show_right_nav" name="show_right_nav" type="checkbox" value="1"<?php echo (!isset($PROCESSED["page_navigation"]["show_next_nav"]) || ((int) $PROCESSED["page_navigation"]["show_next_nav"] == 1) ? " checked=\"checked\"" : ""); ?>/>
														<input class="btn" id="change_next_nav_button" name="change_next_nav_button" type="button" value="Next Page" />
														<input type="hidden" name="selected_nav_next_page_id" id="selected_nav_next_page_id" <?php echo (isset($nav_next_page_id) && $nav_next_page_id ? "value = \"" . $nav_next_page_id . "\"" : "value = \"" . $default_next_page["cpage_id"] . "\"") ?> />
													</td>
												</tr>
										<?php
										}

										if (!$home_page && array_search($PAGE_ID, (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS ? $COMMUNITY_LOCKED_PAGE_IDS : array())) === false) {
											$query		= "SELECT `cpage_id`, `page_order`, `menu_title` FROM `community_pages` WHERE `cpage_id` <> ".$db->qstr($PAGE_ID)." AND `parent_id` = ".$db->qstr($PROCESSED["parent_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` != '' AND `page_type` != 'course' ORDER BY `page_order` ASC";
											$results	= $db->GetAll($query);
											if ($results) {
												?>
												<tr>
													<td style="vertical-align: middle">
														<label for="page_order" class="form-nrequired">Page Position:</label>
													</td>
													<td style="vertical-align: middle">
														<select id="page_order" name="page_order" style="width: 312px" onchange="orderChange(this.value)">
															<option value="no">Do Not Move Page</option>
															<?php
															if ((int) $PROCESSED["parent_id"]) {
																?>
																<optgroup label="&rarr; /<?php echo html_encode($PROCESSED["menu_title"]); ?>">
																<?php
															} else {
																?>
																<optgroup label="/">
																<?php
															}
															?>
															<option value="0">Appear First</option>
															<?php
															foreach ($results as $result) {
																echo "<option value=\"".(((int) $result["page_order"]) + 1)."\">After &quot;".html_encode($result["menu_title"])."&quot;</option>\n";
															}
															?>
															</optgroup>
														</select>
													</td>
												</tr>
												<?php
											}
											?>
												<tr>
													<td><label for="page_visibile" class="form-nrequired">Page Visibility:</label></td>
													<td>
														<select id="page_visibile" name="page_visibile" style="width: 312px">
															<option value="1"<?php echo (((int)$PROCESSED["page_visible"]) == 1 ? " selected=\"true\"" : ""); ?>>Show this page on menu</option>
															<option value="0"<?php echo (((int)$PROCESSED["page_visible"]) == 0 ? " selected=\"true\"" : ""); ?>>Hide this page from menu</option>
														</select>
													</td>
												</tr>
											<?php
										} elseif ($home_page) {
										?>
												<tr>
													<td colspan="2">
														<table class="table table-striped table-bordered">
															<colgroup>
																<col style="width: 50%" />
																<col style="width: 50%" />
															</colgroup>
															<thead>
																<tr>
																	<td>Option</td>
																	<td>Additional Options</td>
																</tr>
															</thead>
															<tbody>
																<tr>
																	<td>
																		<strong>Show New Announcements</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="show_announcements" name="show_announcements" value="1"<?php echo (((isset($page_options["show_announcements"]["option_value"])) && (((int)$page_options["show_announcements"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
																<tr>
																	<td>
																		<strong>Show Upcoming Events</strong>
																	</td>
																	<td class="on"><input type="checkbox" id="show_events" name="show_events" value="1"<?php echo (((isset($page_options["show_events"]["option_value"])) && (((int)$page_options["show_events"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
																</tr>												<tr>
																	<td>
																		<strong>Show Community History</strong>
																	</td>
																	<td class="on">
																		<input type="checkbox" id="show_history" name="show_history" value="1"<?php echo (((isset($page_options["show_history"]["option_value"])) && (((int)$page_options["show_history"]["option_value"]) == 1)) ? " checked=\"checked\"" : ""); ?> />
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
											<?php
										}
										?>
									</tbody>
								</table>
								<div id="modal_page_navigation" style="display: none; text-align: left;">
									<?php echo communities_pages_inradio(0, 0, array('id'=>'next_page_list', "nav_type" => "next", "selected" => (isset($nav_next_page_id) && $nav_next_page_id ? $nav_next_page_id : $default_next_page["cpage_id"]))); ?>
								</div>
								<div id="modal_previous_page_navigation" style="display: none; text-align: left;">
									<?php echo communities_pages_inradio(0, 0, array('id'=>'previous_page_list', "nav_type" => "previous", "selected" => (isset($nav_previous_page_id) && $nav_previous_page_id ? $nav_previous_page_id : (isset($default_previous_page["cpage_id"]) && $default_previous_page["cpage_id"] ? $default_previous_page["cpage_id"] : "")))); ?>
								</div>
							</form>
							<?php
						break;
					}
				} else {
					$url		= COMMUNITY_URL.$COMMUNITY_URL.":pages";
					$ONLOAD[]	= "setTimeout('window.location=\\'".$url."?errmsg=pages404\\'', 0)";

					$ERROR++;
					// $ERRORSTR[]	= "The page you have requested does not currently exist within this community.<br /><br />You will now be redirected to the page management index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					// echo display_error();
				}
			} else {
				application_log("error", "Someone attempted to access this page who was not a community administrator. (Edit Page)");
				header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":pages");
				exit;
			}
		} else {
			application_log("error", "The provided pages page_id does not exist in the system. (Edit Page)");
			header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":pages");
			exit;
		}
	} else {
		application_log("error", "No pages page id was provided to edit. (Edit Page)");
		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":pages");
		exit;
	}
}
?>