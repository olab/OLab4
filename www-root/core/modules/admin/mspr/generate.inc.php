<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MSPR_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	if (isset($_GET['id'])) {
		$user_id = clean_input($_GET['id'],array("int"));
		
		//single user generation
		$mode = "user_mode";
	} elseif (isset($_POST['year']) && isset($_POST['user_id'])) {
		$year = $_POST['year'];
		$mode = "group_mode";
		$user_ids = $_POST['user_id'];
	} else {
		add_error("Insufficient data provided to generate report(s)");
		display_status_messages();
	}
	if (!has_error()) {
		require_once("Classes/mspr/MSPRs.class.php");
		switch($mode) {
			case "user_mode":
				$user = User::fetchRowByID($user_id);
				$mspr = MSPR::get($user);
				$name = $user->getFirstname() . " " . $user->getLastname();
				
				$page_title = $name . "'s MSPR page";
				$url = ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$user_id;
				
				if ($mspr->saveMSPRFiles()) {
					success_redirect($url, $page_title, "<p>Report successfully generated.</p>"); 
				} else {
					error_redirect($url, $page_title, "<p>Error generating report for ".$name.".</p>"); 
				}
				break;
			case "group_mode";
				$timestamp = time();
				foreach ($user_ids as $user_id) {
					
					$user = User::fetchRowByID($user_id);
					$mspr = MSPR::get($user);
					$name = $user->getFirstname() . " " . $user->getLastname();
										
					if (!$mspr->saveMSPRFiles($timestamp)) {
						add_error("Error generating report for $name.");
					}
											
				}
				
				$page_title = "Class of ". $year . " MSPR page";
				$url = ENTRADA_URL."/admin/mspr?mode=year&year=".$year;
				
				if (!has_error()) {
					success_redirect($url, $page_title, "<p>Reports successfully generated.</p>"); 
				} else {
					error_redirect($url, $page_title, ""); 
				}
				break;
		}
	}
}