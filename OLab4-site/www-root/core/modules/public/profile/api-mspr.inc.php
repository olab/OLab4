<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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
	if ($ENTRADA_ACL->amIAllowed("mspr", "update", false)) {

		
		ob_clear_open_buffers();
		require_once(dirname(__FILE__)."/includes/functions.inc.php");
		
		$user = User::fetchRowByID($ENTRADA_USER->getID());
		$controller = new MSPRPublicController($translate, $user);
		$controller->process();	
	}
	exit;
}
?>
