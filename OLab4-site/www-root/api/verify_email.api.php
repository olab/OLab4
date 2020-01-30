<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

/**
 * This is called via index.php when the user has verified we have their correct email address
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

if((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) { 	
	$proxy_id 	= $ENTRADA_USER->getActiveId();
	$_SESSION["details"]["email_updated"] = true;
	
	$PROCESSED["email_updated"] = time();
	$PROCESSED["updated_by"] = $proxy_id;
	if(!$db->AutoExecute(AUTH_DATABASE.".user_data", $PROCESSED, "UPDATE", "`id`=".$db->qstr($proxy_id))) {
		echo $db->ErrorMsg();
		exit;
	}
} else {
	application_log("error", "Verify Email API accessed without valid session_id.");
}
?>