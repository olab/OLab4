<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Controller file for the galleries / photo module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if (!defined("COMMUNITY_INCLUDED")) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

define("IN_GALLERIES", true);

communities_build_parent_breadcrumbs();
$BREADCRUMB[]			= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);
$VALID_MIME_TYPES		= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
$VALID_MAX_FILESIZE		= MAX_UPLOAD_FILESIZE;
$VALID_MAX_DIMENSIONS	= array("photo" => 500, "thumb" => 150);
$RENDER					= false;

/**
 * Name of the action that is to be loaded by a module. Defaults to "index". It is up to the
 * individual module to ensure that this action is valid and exists.
 */
if (isset($_GET["render"])) {
	if (trim($_GET["render"]) != "") {
		$RENDER = clean_input($_GET["render"], "alphanumeric");
	}
}

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual gallery.
 *
 * @param int $cgallery_id
 * @param string $section
 * @return bool
 */
function galleries_module_access($cgallery_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $PAGE_ID;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cgallery_id = (int) $cgallery_id) {
			$query	= "SELECT * FROM `community_galleries` WHERE `cgallery_id` = ".$db->qstr($cgallery_id)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				switch($section) {
					case "add-gallery" :
					case "delete-gallery" :
					case "edit-gallery" :
						/**
						 * This is false, because this should have been covered by the statement
						 * above as creating new galleries is an administrative only function.
						 */
						$allow_to_load = false;
					break;
					case "add-photo" :
					case "delete-photo" :
					case "edit-photo" :
						if ($LOGGED_IN) {
							if ($COMMUNITY_MEMBER) {
								if ((int) $result["allow_member_upload"]) {
									$allow_to_load = true;
								}
							} elseif ((int) $result["allow_troll_upload"]) {
								$allow_to_load = true;
							}
						} else {
							$allow_to_load = false;
						}
					break;
					case "add-comment" :
					case "edit-comment" :
					case "delete-comment" :
						if ($LOGGED_IN) {
							if ($COMMUNITY_MEMBER) {
								if ((int) $result["allow_member_comment"]) {
									$allow_to_load = true;
								}
							} elseif ((int) $result["allow_troll_comment"]) {
								$allow_to_load = true;
							}
						} else {
							$allow_to_load = false;
						}
					break;
					case "view-gallery" :
					case "view-photo" :
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
			if ((int) $result["gallery_active"]) {
				if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
					if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
						/**
						 * You're good to go, no further checks at this time.
						 * If you need to add more checks, this is there they would go.
						 */
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "This photo gallery was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

						$allow_to_load	= false;
					}
				} else {
					$NOTICE++;
					$NOTICESTR[]	= "This photo gallery will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

					$allow_to_load	= false;
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This photo gallery was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You do not have access to this photo gallery.<br /><br />".(($LOGGED_IN) ? "If you believe there has been a mistake, please contact a community administrator for assistance." : "You are not currently authenticated, please log in by clicking the login link to the right.");
		}
	}

	return $allow_to_load;
}

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual gallery photos.
 *
 * @param int $cgallery_id
 * @param string $section
 * @return bool
 */
function galleries_photo_module_access($cgphoto_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $ENTRADA_USER;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cgphoto_id = (int) $cgphoto_id) {

			$query	= "SELECT * FROM `community_gallery_photos` WHERE `cgphoto_id` = ".$db->qstr($cgphoto_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				if ($allow_to_load = galleries_module_access($result["cgallery_id"], $section)) {
					switch($section) {
						case "delete-photo" :
						case "edit-photo" :
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
			if ((int) $result["photo_active"]) {
				/**
				 * Don't worry about checking the release dates if the person viewing
				 * the photo is the photo uploader.
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
							$NOTICESTR[]	= "This photo was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until).".<br /><br />Please contact your community administrators for further assistance.";

							$allow_to_load	= false;
						}
					} else {
						$NOTICE++;
						$NOTICESTR[]	= "This photo will not be accessible until ".date(DEFAULT_DATE_FORMAT, $release_date).".<br /><br />Please check back at this time, thank-you.";

						$allow_to_load	= false;
					}
				}
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This photo was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			if (!$ERROR) {
				$ERROR++;
				$ERRORSTR[] = "You do not have access to this photo.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.";
			}
		}
	}

	return $allow_to_load;
}

/**
 * This function handles granular permissions levels (where as communities_module_access handles higer level permissions)
 * for the actual gallery comments.
 *
 * @param int $cgallery_id
 * @param string $section
 * @return bool
 */
function galleries_comment_module_access($cgcomment_id = 0, $section = "") {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $ENTRADA_USER;

	$allow_to_load = false;

	if (((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if ($cgcomment_id = (int) $cgcomment_id) {

			$query	= "SELECT * FROM `community_gallery_comments` WHERE `cgcomment_id` = ".$db->qstr($cgcomment_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if ($result) {
				if ($allow_to_load = galleries_module_access($result["cgallery_id"], $section)) {
					switch($section) {
						case "delete-comment" :
						case "edit-comment" :
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
			if ((int) $result["comment_active"]) {
				/**
				 * You're good to go, no further checks at this time.
				 * If you need to add more checks, this is there they would go.
				 */
			} else {
				$NOTICE++;
				$NOTICESTR[]	= "This comment was deactivated ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".html_encode(get_account_data("firstlast", $result["updated_by"])).".<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

				$allow_to_load	= false;
			}
		} else {
			if (!$ERROR) {
				$ERROR++;
				$ERRORSTR[] = "You do not have access to this comment.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.";
			}
		}
	}

	return $allow_to_load;
}

function galleries_photo_navigation($cgallery_id = 0, $cgphoto_id = 0) {
	global $db, $COMMUNITY_ID, $PAGE_URL, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $PAGE_ID, $ENTRADA_USER;

	$output = false;
	if (($cgallery_id = (int) $cgallery_id) && ($cgphoto_id = (int) $cgphoto_id)) {
		/**
		 * Provide the queries with the columns to order by.
		 */
		switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
			case "title" :
				$SORT_BY	= "a.`photo_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`updated_date` DESC";
			break;
			case "poster" :
				$SORT_BY	= "CONCAT_WS(', ', b.`lastname`, b.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`updated_date` DESC";
			break;
			case "date" :
			default :
				$SORT_BY	= "a.`updated_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
			break;
		}
		$query		= "	SELECT a.`cgphoto_id`
						FROM `community_gallery_photos` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						LEFT JOIN `community_galleries` AS c
						ON a.`cgallery_id` = c.`cgallery_id`
						WHERE a.`cgallery_id` = ".$db->qstr($cgallery_id)."
						AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND c.`cpage_id` = ".$db->qstr($PAGE_ID)."
						AND a.`photo_active` = '1'
						".((!$LOGGED_IN) ? " AND c.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND c.`allow_member_read` = '1'" : "") : " AND c.`allow_troll_read` = '1'"))."
						".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "")."
						ORDER BY ".$SORT_BY;
		$results	= $db->GetAll($query);
		if ($results) {
			$output = array("back" => false, "next" => false);

			if ($cgphoto_id_search = dimensional_array_search($cgphoto_id, $results)) {
				$back_key = ($cgphoto_id_search[0] - 1);
				$next_key = ($cgphoto_id_search[0] + 1);
				if ((isset($results[$back_key]["cgphoto_id"])) && ($cgphoto_id_back = (int) $results[$back_key]["cgphoto_id"])) {
					$output["back"] = $cgphoto_id_back;
				}
				if ((isset($results[$next_key]["cgphoto_id"])) && ($cgphoto_id_next = (int) $results[$next_key]["cgphoto_id"])) {
					$output["next"] = $cgphoto_id_next;
				}
			}
		}
	}

	return $output;
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