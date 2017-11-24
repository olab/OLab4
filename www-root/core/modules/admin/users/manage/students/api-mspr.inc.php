<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	if ($ENTRADA_ACL->amIAllowed("mspr", "create", false)) {

		ob_clear_open_buffers();
	
		require_once(dirname(__FILE__)."/includes/functions.inc.php");
		
		$user = User::fetchRowByID($user_record["id"]);
		$controller = new MSPRAdminController($translate, $user);
		$controller->process();		
	}
	exit;
}
?>
