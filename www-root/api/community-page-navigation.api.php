<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    if (isset($_POST["cpage_id"]) && ($cpage_id = ((int) $_POST["cpage_id"]))) {
		$query = "SELECT * FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($cpage_id);
		$page = $db->GetRow($query);
		if (!$page) {
			echo 402;
			exit;
		} else {
			$query = "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($page["community_id"]);
			$community = $db->GetRow($query);
			if (!$community) {
				echo 403;
				exit;
			} else {
				$COMMUNITY_ID = $community["community_id"];
				$COMMUNITY_URL = $community["community_url"];
			}
		}
	} elseif (isset($_POST["community_id"]) && ($COMMUNITY_ID = ((int) $_POST["community_id"]))) {
		$query = "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
		$community = $db->GetRow($query);
		if (!$community) {
			echo 403;
			exit;
		} else {
			$COMMUNITY_URL = $community["community_url"];
		}
		$cpage_id = 0;
	} else {
		echo 401;
		exit;
	}
    if (!isset($_POST["parent_id"]) || !($parent_id = ((int) $_POST["parent_id"]))) {
        $parent_id = 0;
    } else {
		$query = "SELECT * FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($parent_id);
		$parent_page = $db->GetRow($query);
		if (!$parent_page) {
			$parent_id = 0;
		}
	}
	if (!isset($_POST["nav_type"]) || !($nav_type = clean_input($_POST["nav_type"], "module"))) {
		echo 0;
		exit;
    }
	if (!isset($_POST["page_order"]) || !($page_order = ((int) $_POST["page_order"]))) {
		$query = "SELECT (MAX(`page_order`) + 1) FROM `community_pages`
					WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND `parent_id` = ".$db->qstr($parent_id);
        $page_order = $db->GetOne($query);
    }
									

	switch ($nav_type) {
		case "prev_id" :
		case "previous_id" :
			$default_previous_page = get_prev_community_page($COMMUNITY_ID, $cpage_id, $parent_id, $page_order);
			echo $default_previous_page["cpage_id"];
			exit;
		break;
		case "prev" :
		case "previous" :
			$default_previous_page = get_prev_community_page($COMMUNITY_ID, $cpage_id, $parent_id, $page_order);
			echo communities_pages_inradio(0, 0, array("selected" => $default_previous_page["cpage_id"], "id" => "previous_page_list", "nav_type" => "previous", "parent_swap" => array("parent_id" => $parent_id, "page_id" => $cpage_id)));
			exit;
		break;
		case "next_id" :
			$default_next_page = get_next_community_page($COMMUNITY_ID, $cpage_id, $parent_id, $page_order);
			echo $default_next_page["cpage_id"];
			exit;
		break;
		case "next" :
		default :
			$default_next_page = get_next_community_page($COMMUNITY_ID, $cpage_id, $parent_id, $page_order);
			echo communities_pages_inradio(0, 0, array("selected" => $default_next_page["cpage_id"], "id" => "next_page_list", "nav_type" => "next", "parent_swap" => array("parent_id" => $parent_id, "page_id" => $cpage_id)));
			exit;
		break;
	}
}
