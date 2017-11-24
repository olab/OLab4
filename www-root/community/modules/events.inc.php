<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Controller file for the events / calendar module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if (!defined("COMMUNITY_INCLUDED")) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

define("IN_EVENTS", true);

communities_build_parent_breadcrumbs();

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);
$ALLOWED_HTML_TAGS = "<span><a><ol><ul><li><strike><br><p><div><em><h1><h2><h3><small>";

$RECORD_AUTHOR = $db->GetOne("SELECT `proxy_id` FROM `community_events` WHERE `cevent_id` = ".$db->qstr($RECORD_ID));

if (communities_module_access($COMMUNITY_ID, $MODULE_ID, $SECTION)) {
	if ((@file_exists($section_to_load = COMMUNITY_ABSOLUTE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$COMMUNITY_MODULE.DIRECTORY_SEPARATOR.$SECTION.".inc.php")) && (@is_readable($section_to_load))) {
		/**
		 * Add the RSS feed version of the page to the <head></head> tags.
		 */
		$PRIVATE_HASH = (isset($_SESSION["details"]["private_hash"]) ? "private-".html_encode($_SESSION["details"]["private_hash"]) : "");
		$HEAD[] = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%TITLE% ".$MENU_TITLE." RSS 2.0\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss20:".$PRIVATE_HASH."\" />";
		$HEAD[] = "<link rel=\"alternate\" type=\"text/xml\" title=\"%TITLE% ".$MENU_TITLE." RSS 0.91\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss:".$PRIVATE_HASH."\" />";

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