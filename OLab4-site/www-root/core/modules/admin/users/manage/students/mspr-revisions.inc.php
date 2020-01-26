<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_MANAGE_USER_STUDENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", true)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {
	
	require_once("Classes/mspr/MSPRs.class.php");
	$PROXY_ID					= $user_record["id"];
	$user = User::fetchRowByID($user_record["id"]);
	
	$PAGE_META["title"]			= "MSPR Revisions";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID, "title" => "MSPR");
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr-revisions&id=".$PROXY_ID, "title" => "MSPR Revisions");

	$PROCESSED		= array();
	
	if ((is_array($_SESSION["permissions"])) && ($total_permissions = count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "The following individual".((($total_permissions - 1) != 1) ? "s have" : " has")." given you access to their ".APPLICATION_NAME." permission levels:";
		$sidebar_html .= "<ul class=\"menu\">\n";
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($access_id != $ENTRADA_USER->getDefaultAccessId()) {
				$sidebar_html .= "<li class=\"checkmark\"><strong>".html_encode($result["fullname"])."</strong><br /><span class=\"content-small\">Exp: ".(($result["expires"]) ? date("D M d/y", $result["expires"]) : "Unknown")."</span></li>\n";
			}
		}
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Delegated Permissions", $sidebar_html, "delegated-permissions", "open");
	}

	
	$mspr = MSPR::get($user);
	$revisions = $mspr->getMSPRRevisions();
	$name = $user->getFirstname() . " " . $user->getLastname();
	$number = $user->getNumber();
	if ($revision = $_GET['revision']) {
		if ($type = $_GET['get']) {
			switch($type) {
				case 'html':
					header('Content-type: text/html');
					header('Content-Disposition: filename="MSPR - '.$name.'('.$number.').html"');
					
					break;
				case 'pdf':
					header('Content-type: application/pdf');
					header('Content-Disposition: attachment; filename="MSPR - '.$name.'('.$number.').pdf"');
					break;
				default:
					add_error("Unknown file type: " . $type);
			}
			if (!has_error()) {
				ob_clear_open_buffers();
				flush();
				echo $mspr->getMSPRFile($type,$revision);
				exit();	
			}
			
		}
	}
	
	add_mspr_management_sidebar();
	?>
	<h1>Revisions of MSPR documents for <?php echo $user->getFullname(); ?></h1>
	<?php display_status_messages(); 
	
	if ($revisions) {
	?>
	<ul class="revision-list">
	<?php
		foreach ($revisions as $revision) {
	?>
		<li>
			<?php echo date("F j, Y \a\\t g:i a",$revision); ?>: <a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $PROXY_ID; ?>&get=html&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=html" /> HTML</a> <a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $PROXY_ID; ?>&get=pdf&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=pdf" /> PDF</a>	
		</li>
	<?php
		}
	?>
	</ul>
	
	<?php 
	} else{
	?>
	<p>None</p>
	<?php
	}
}
