<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Controller file for the discussions module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 * 
*/

if (!defined("COMMUNITY_INCLUDED")) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

define("IN_DISCUSSIONS", true);

communities_build_parent_breadcrumbs();
$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);
$VALID_MIME_TYPES		= array();
$VALID_MAX_FILESIZE		= 31457280; // 30MB
$DOWNLOAD				= false;

/**
 * If the download variable exists in the URL on view-file and it's a valid integer
 * it will download the specified version of the file (i.e. ...action=view-file&id=123&download=6
 */
if (isset($_GET["download"])) {
	if ($tmp_download = clean_input($_GET["download"], "alphanumeric")) {
		if ((int) $tmp_download) {
			$DOWNLOAD = (int) $tmp_download;
		} elseif ($tmp_download == "latest") {
			$DOWNLOAD = "latest";
		}
	}
}
/**
 * This function handles granular permissions levels (where as communities_module_access handles higher level permissions)
 * for the actual discussion forum.
 *
 * @param int $cdiscussion_id
 * @param string $section
 * @return bool
 */
function discussions_module_access($cdiscussion_id = 0, $section = "") {
	global $db, $COMMUNITY_ACL, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $PAGE_ID, $ENTRADA_USER;

	$is_community_course = Models_Community_Course::is_community_course($COMMUNITY_ID);
	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cdiscussion_id = (int) $cdiscussion_id) {
			$query	= "SELECT * FROM `community_discussions` WHERE `cdiscussion_id` = ".$db->qstr($cdiscussion_id)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				switch($section) {
					case "add-forum" :
					case "delete-forum" :
					case "edit-forum" :
						/**
						 * This is false, because this should have been covered by the statement
						 * above as creating new forums is an administrative only function.
						 */
						$allow_to_load = false;
					break;
					case "add-post" :
					case "add-file" :
						if ($is_community_course) {
							$allow_to_load = $COMMUNITY_ACL->amIAllowed("communitydiscussion", $cdiscussion_id, "create");
						} else {
							if ($LOGGED_IN) {
								if ($COMMUNITY_MEMBER) {
									if ((int) $result["allow_member_post"]) {
										$allow_to_load = true;
									}
								} else if ((int) $result["allow_troll_post"]) {
									$allow_to_load = true;
								}
							} else if ((int) $result["allow_public_post"]) {
								$allow_to_load = true;
							}
						}
					break;
					case "delete-post" :
					case "delete-file" :
					case "edit-post" :
					case "edit-file" :
                        //Users can edit and delete posts if they are an admin
                        //or they are the original poster. This will be checked
                        //in discussion_topic_module_access()
                        $allow_to_load = true;
					break;
					case "reply-post" :
						if ($is_community_course) {
							$allow_to_load = $COMMUNITY_ACL->amIAllowed("communitydiscussion", $cdiscussion_id, "create");
						} else {
							if ($LOGGED_IN) {
								if ($COMMUNITY_MEMBER) {
									if ((int) $result["allow_member_reply"]) {
										$allow_to_load = true;
									}
								} elseif ((int) $result["allow_troll_reply"]) {
									$allow_to_load = true;
								}
							} elseif ((int) $result["allow_public_reply"]) {
								$allow_to_load = true;
							}
						}
					break;
					case "view-forum" :
					case "view-post" :
					case "view-file" :
						if ($is_community_course) {
							$allow_to_load = $COMMUNITY_ACL->amIAllowed("communitydiscussion", $cdiscussion_id, "read");
						} else {
							if ($LOGGED_IN) {
								if ($COMMUNITY_MEMBER) {
									if ((int) $result["allow_member_read"]) {
										$allow_to_load = true;
									}
								} elseif ((int) $result["allow_troll_read"]) {
									$allow_to_load = true;
								}
							} elseif ((int) $result["allow_public_read"]) {
								$allow_to_load = true;
							}
						}
					break;
					case "index" :
						$allow_to_load = true;
					break;
					default :
						continue;
					break;
				}
			}
		}
		
		if ($allow_to_load) {
			if ((int) $result["forum_active"]) {
				if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
					if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
						/**
						 * You're good to go, no further checks at this time.
						 * If you need to add more checks, this is there they would go.
						 */
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "This discussion forum was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

						$allow_to_load	= false;
					}
				} else {
					$NOTICE++;
					$NOTICESTR[]	= "This discussion forum will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

					$allow_to_load	= false;
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This discussion forum was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You do not have access to this discussion forum.<br /><br />".(($LOGGED_IN) ? "If you believe there has been a mistake, please contact a community administrator for assistance." : "You are not currently authenticated, please log in by clicking the login link to the right.");
		}
	}

	return $allow_to_load;
}

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual discussion forum topics.
 *
 * @param int $cdiscussion_id
 * @param string $section
 * @return bool
 */
function discussion_topic_module_access($cdtopic_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $ENTRADA_USER;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cdtopic_id = (int) $cdtopic_id) {

			$query	= "SELECT * FROM `community_discussion_topics` WHERE `cdtopic_id` = ".$db->qstr($cdtopic_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				if ($allow_to_load = discussions_module_access($result["cdiscussion_id"], $section)) {
					switch($section) {
						case "delete-post" :
						case "edit-post" :
							if (($LOGGED_IN) && ($ENTRADA_USER->getActiveId() != (int) $result["proxy_id"])) {
								$allow_to_load = false;
							} else {
                                $allow_to_load = true;
							}
						break;
						default :
							continue;
						break;
					}
				}
			}
		}
		if ($allow_to_load) {
			if ((int) $result["topic_active"]) {
				/**
				 * Don't worry about checking the release dates if the person viewing
				 * the post is the post author.
				 */
				if (!$LOGGED_IN || $ENTRADA_USER->getActiveId() != (int) $result["proxy_id"]) {
					if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
						if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
							/**
							 * You're good to go, no further checks at this time.
							 * If you need to add more checks, this is there they would go.
							 */
						} else {
							$NOTICE++;
							$NOTICESTR[]	= "This discussion post was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

							$allow_to_load	= false;
						}
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "This discussion post will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

						$allow_to_load	= false;
					}
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This discussion post was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			if (!$ERROR) {
				$ERROR++;
				$ERRORSTR[] = "You do not have access to this discussion post.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.";
			}
		}
	}

	return $allow_to_load;
}

if (communities_module_access($COMMUNITY_ID, $MODULE_ID, $SECTION)) {
	if ((@file_exists($section_to_load = COMMUNITY_ABSOLUTE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$COMMUNITY_MODULE.DIRECTORY_SEPARATOR.$SECTION.".inc.php")) && (@is_readable($section_to_load))) {
		/**
		 * Add the RSS feed version of the page to the <head></head> tags.
		 */
		$PRIVATE_HASH = (isset($_SESSION["details"]["private_hash"]) ? "private-".html_encode($_SESSION["details"]["private_hash"]) : "");
		$HEAD[] = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%TITLE% ".$MENU_TITLE." RSS 2.0\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss20:".$PRIVATE_HASH.(($RECORD_ID) ? "?id=".$RECORD_ID : "")."\" />";
		$HEAD[] = "<link rel=\"alternate\" type=\"text/xml\" title=\"%TITLE% ".$MENU_TITLE." RSS 0.91\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss:".$PRIVATE_HASH.(($RECORD_ID) ? "?id=".$RECORD_ID : "")."\" />";

		require_once($section_to_load);
	} else {
        Entrada_Utilities_Flashmessenger::addMessage($translate->_("The action you are looking for does not exist for this module."), "error", $MODULE);

        application_log("error", "Communities system tried to load ".$section_to_load." which does not exist or is not readable by PHP.");

        $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
        header("Location: " . $url);
        exit;
	}
} else {
    Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have access to this section of this module. Please contact a community administrator for assistance."), "error", $MODULE);

    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
    header("Location: " . $url);
    exit;
}
?>