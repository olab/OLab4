<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Controller file for the polling module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if (!defined("COMMUNITY_INCLUDED")) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

define("IN_POLLS", true);

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual poll.
 *
 * @param int $cpolls_id
 * @param string $section
 * @return bool
 */
function polls_module_access($cpolls_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $PAGE_ID;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cpolls_id = (int) $cpolls_id) {
			$query	= "SELECT * FROM `community_polls` WHERE `cpolls_id` = ".$db->qstr($cpolls_id)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				switch($section) {
					case "add-poll" :
					case "delete-poll" :
					case "edit-poll" :
						/**
						 * This is false, because this should have been covered by the statement
						 * above as creating new polls is an administrative only function.
						 */
						$allow_to_load = false;
					break;
					case "vote-poll" :
						if ($LOGGED_IN) {
							if ($COMMUNITY_MEMBER) {
								if ((int) $result["allow_member_vote"]) {
									$allow_to_load = true;
								}
							} elseif ((int) $result["allow_troll_vote"]) {
								$allow_to_load = true;
							}
						} elseif ((int) $result["allow_public_vote"]) {
							$allow_to_load = true;
						}
					break;
					case "delete-vote" :
					case "edit-vote" :
						if ($LOGGED_IN) {
							if ($COMMUNITY_MEMBER) {
								if ((int) $result["allow_member_edit"]) {
									$allow_to_load = true;
								}
    						} else {
    							$allow_to_load = false;
    						}
						}
					break;
					case "view-poll" :
					case "view-poll-results" :
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
			if ((int) $result["poll_active"]) {
				if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
					if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
						/**
						 * You're good to go, no further checks at this time.
						 * If you need to add more checks, this is where they would go.
						 */
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "This poll was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

						$allow_to_load	= false;
					}
				} else {
					$NOTICE++;
					$NOTICESTR[]	= "This poll will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

					$allow_to_load	= false;
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This poll was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You do not have access to this poll.<br /><br />".(($LOGGED_IN) ? "If you believe there has been a mistake, please contact a community administrator for assistance." : "You are not currently authenticated, please log in by clicking the login link to the right.");
		}
	}    

	return $allow_to_load;
}

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual polls.
 *
 * @param int $cpresults_id
 * @param string $section
 * @return bool
 */
function results_module_access($cpresults_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $ENTRADA_USER;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cpresults_id = (int) $cpresults_id) {

			$query	= "SELECT * FROM `community_polls_results` WHERE `cpresults_id` = ".$db->qstr($cpresults_id);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				if ($allow_to_load = polls_module_access($result["cpolls_id"], $section)) {
					switch($section) {
						case "delete-post" :
						case "edit-post" :
							if ($ENTRADA_USER->getActiveId() != (int) $result["proxy_id"]) {
								$allow_to_load = false;
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
			if ((int) $result["poll_active"]) {
				/**
				 * Don't worry about checking the release dates if the person viewing
				 * the post is the post author.
				 */
				if ($ENTRADA_USER->getActiveId() != (int) $result["proxy_id"]) {
					if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
						if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
							/**
							 * You're good to go, no further checks at this time.
							 * If you need to add more checks, this is there they would go.
							 */
						} else {
							$NOTICE++;
							$NOTICESTR[]	= "These results are only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

							$allow_to_load	= false;
						}
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "These results will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

						$allow_to_load	= false;
					}
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "These results were deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			if (!$ERROR) {
				$ERROR++;
				$ERRORSTR[] = "You do not have access to view these results.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.";
			}
		}
	}

	return $allow_to_load;
}

if (communities_module_access($COMMUNITY_ID, $MODULE_ID, $SECTION)) {
	if ((@file_exists($section_to_load = COMMUNITY_ABSOLUTE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$COMMUNITY_MODULE.DIRECTORY_SEPARATOR.$SECTION.".inc.php")) && (@is_readable($section_to_load))) {
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