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

define("IN_QUIZZES", true);

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => $MENU_TITLE);

/**
 * Allows you to specify which record id your particular component is
 * dealing with (i.e. http:// ... /admin/events?section=edit&id=1562).
 */
if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
	$RECORD_ID = $tmp_input;
} elseif ((isset($_POST["id"])) && ($tmp_input = clean_input($_POST["id"], array("trim", "int")))) {
	$RECORD_ID = $tmp_input;
} else {
	$RECORD_ID = 0;
}

$QUIZ_TYPE = "community_page";

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