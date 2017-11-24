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
	require_once("Entrada/mspr/functions.inc.php");
	require_once("Classes/mspr/MSPRs.class.php");
	$PROXY_ID = $user_record["id"];
	$user = User::fetchRowByID($PROXY_ID);

	if (!(isset($_GET['from']) && ($from = $_GET['from']) && (in_array($from,array("attention", "class","user"))))) {
		$from = 'user';
	}
	switch($from) {
		case 'attention':
			$success_url = ENTRADA_URL."/admin/mspr?mode=all";
			$success_title = "Manage All MSPRs Requiring Attention";
			break;
		case 'class':
			$year = $user->getGradYear();
			$success_url = ENTRADA_URL."/admin/mspr?mode=year&year=".$year;
			$success_title = "Manage Class of ".$year." MSPRs";
			break;
		case 'user':
		default:
			$success_url = ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID;
			$success_title = $user->getFirstName() . " " . $user->getLastName() . "'s MSPR";
	}

	if (isset($_GET['revision']) && $REVISION = clean_input($_POST['revision'], array("int)"))) {
		$rev_append = "&revision=".$REVISION;
	}

	if ($user) {
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr-edit&id=".$PROXY_ID."&from=".$from.$rev_append, "title" => "Edit MSPR" );

		$mspr = MSPR::get($user);

		if ($mspr) {

			$is_closed = $mspr->isClosed();
			$rev = $mspr->getMSPRRevisions("html");
			if ($is_closed) {

				if (!empty($rev)) {

					if (isset($_POST['action']) && ($_POST['action']=='save')) {
						if (isset($_POST['edit-html']) && ($edit_html = trim($_POST['edit-html']))) {
							$ts = time();
							$pdf = $mspr->generatePDF($edit_html);

							$wrote_html = $mspr->saveMSPRFile("html",$edit_html,$ts);
							$wrote_pdf = $mspr->saveMSPRFile("pdf",$pdf,$ts);
							if ($wrote_html && $wrote_pdf) {
								$mspr->setGeneratedTimestamp($ts);
								success_redirect($success_url, $success_title,"Successfully edited HTML and generated PDF.");
							}

						} else {
							error_redirect(ENTRADA_URL."/admin/users/manage/students?section=mspr-edit&id=".$PROXY_ID."&from=".$from.$rev_append, "Edit MSPR", "No content provided. Cannot create empty MSPR.");
						}
					} else {

						if ($REVISION) {
							$html_file = $mspr->getMSPRFile("html", $REVISION);
						} else {
							$html_file = $mspr->getMSPRFile("html");
						}
						load_rte("mspr");
						?>
						<h1>Edit MSPR: <?php echo $user->getFullName(); ?></h1>
						<form method="post">
						<input type="hidden" name="action" value="save" />
						<textarea id="edit-html" name="edit-html" style="width: 100%; height: 300px;" cols="65" rows="20"><?php echo html_encode(trim($html_file)); ?></textarea>
						<input class="btn btn-primary" type="submit" value="Save Changes" />
						</form>
						<?php
					}
				} else {
					echo display_error("MSPR has not been generated. Cannot Edit until at least one revision has been generated.");
				}
			} else {
				echo display_error("MSPR not closed. MSPRs must be closed to student input when editing.");
			}
		} else {
			//no mspr yet. can't have been generated, then, either.
			echo display_error("No MSPR data available to edit");
		}
	} else {
		echo display_error("Invlid user identifier provided or user not found.");
	}
}