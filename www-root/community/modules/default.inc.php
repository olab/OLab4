<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * @todo Is this file used actually used? If not, it needs to be removed. If it
 * is then it needs to be documented better.
 * 
 * @author Organisation: Queen's University
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * $Id: default.inc.php 1092 2010-04-04 17:19:49Z simpson $
*/

if (!defined("COMMUNITY_INCLUDED")) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

define("IN_DEFAULT", true);

communities_build_parent_breadcrumbs();

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);

if ((@file_exists($section_to_load = COMMUNITY_ABSOLUTE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$COMMUNITY_MODULE.DIRECTORY_SEPARATOR.$SECTION.".inc.php")) && (@is_readable($section_to_load))) {
	require_once($section_to_load);
} else {
    Entrada_Utilities_Flashmessenger::addMessage($translate->_("The action you are looking for does not exist for this module."), "error", $MODULE);

	application_log("error", "Communities system tried to load ".$section_to_load." which does not exist or is not readable by PHP.");

    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
    header("Location: " . $url);
    exit;
}
?>