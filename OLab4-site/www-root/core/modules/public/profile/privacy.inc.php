<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_PROFILE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$PAGE_META["title"]			= "Privacy Preferences";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$PROXY_ID					= $ENTRADA_USER->getID();
	$VALID_MIME_TYPES			= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
	$VALID_MAX_FILESIZE			= 2097512; // 2MB
	$VALID_MAX_DIMENSIONS		= array("photo-width" => 216, "photo-height" => 300, "thumb-width" => 75, "thumb-height" => 104);
	$RENDER						= false;

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/profile?section=privacy", "title" => "Privacy Preferences");

	$PROCESSED		= array();

	if (isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && (count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "<form id=\"masquerade-form\" action=\"".ENTRADA_URL."\" method=\"get\">\n";
		$sidebar_html .= "<label for=\"permission-mask\">Available permission masks:</label><br />";
		$sidebar_html .= "<select id=\"permission-mask\" name=\"mask\" style=\"width: 100%\" onchange=\"window.location='".ENTRADA_URL."/".$MODULE."/?".str_replace("&#039;", "'", replace_query(array("mask" => "'+this.options[this.selectedIndex].value")))."\">\n";
		$display_masks = true;
		$added_users = array();
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($result["organisation_id"] == $ENTRADA_USER->getActiveOrganisation() && is_int($access_id) && ((isset($result["mask"]) && $result["mask"]) || $access_id == $ENTRADA_USER->getDefaultAccessId() || ($result["id"] == $ENTRADA_USER->getID() && $ENTRADA_USER->getDefaultAccessId() != $access_id)) && array_search($result["id"], $added_users) === false) {
				if (isset($result["mask"]) && $result["mask"]) {
					$display_masks = true;
				}
				$added_users[] = $result["id"];
				$sidebar_html .= "<option value=\"".(($access_id == $ENTRADA_USER->getDefaultAccessId()) || !isset($result["permission_id"]) ? "close" : $result["permission_id"])."\"".(($result["id"] == $ENTRADA_USER->getActiveId()) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"]) . "</option>\n";
			}
		}
		$sidebar_html .= "</select>\n";
		$sidebar_html .= "</form>\n";
		if ($display_masks) {
			new_sidebar_item("Permission Masks", $sidebar_html, "permission-masks", "open");
		}
	}

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/profile.js\"></script>";
	$HEAD[] = "<script>var PROV_STATE = \"". $prov_state ."\";</script>";
	$HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";

	if ($ERROR) {
		fade_element("out", "display-error-box");
		echo display_error();
	}

	if ($SUCCESS) {
		fade_element("out", "display-success-box");
		echo display_success();
	}

	if ($NOTICE) {
		fade_element("out", "display-notice-box");
		echo display_notice();
	}
	
	$user_object = Models_User::fetchRowByID($ENTRADA_USER->getID());
	if ($user_object) {
		$result = $user_object->toArray();
			
?>
	<h1>Privacy Preferences</h1>
	<form action="<?php echo ENTRADA_URL; ?>/profile?section=privacy" method="post" enctype="multipart/form-data" accept="<?php echo ((@is_array($VALID_MIME_TYPES)) ? implode(",", array_keys($VALID_MIME_TYPES)) : ""); ?>">
		<input type="hidden" name="action" value="privacy-update" />
		<input type="hidden" name="tab" value="privacy-level" />
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0" summary="My <?php echo APPLICATION_NAME;?> Profile Privacy">
			<colgroup>
				<col style="width: 25%" />
				<col style="width: 75%" />
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="2" style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
						<input type="submit" class="btn btn-primary" value="Update Privacy" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td colspan="2">
						<table style="width: 100%" cellspacing="4" cellpadding="2" border="0">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 97%" />
							</colgroup>
							<tbody>
								<tr>
									<td style="vertical-align: top"><input type="radio" id="privacy_level_3" name="privacy_level" value="3"<?php echo (($result["privacy_level"] == "3") ? " checked=\"checked\"" : ""); ?> /></td>
									<td style="vertical-align: top">
										<label for="privacy_level_3"><strong>Complete Profile</strong>: show the information I choose to provide.</label><br />
										<span class="content-small">This means that normal logged in users will be able to view any information you provide in the <strong>My Profile</strong> section. You can provide as much or as little information as you would like; however, whatever you provide will be displayed.</span>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top"><input type="radio" id="privacy_level_2" name="privacy_level" value="2"<?php echo (($result["privacy_level"] == "2") ? " checked=\"checked\"" : ""); ?> /></td>
									<td style="vertical-align: top">
										<label for="privacy_level_2"><strong>Typical Profile</strong>: show only basic information about me.</label><br />
										<span class="content-small">This means that normal logged in users will only be able to view your name, email address, role, official photo and uploaded photo if you have added one, regardless of how much information you provide in the <strong>My Profile</strong> section.</span>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top"><input type="radio" id="privacy_level_1" name="privacy_level" value="1"<?php echo (($result["privacy_level"] == "1") ? " checked=\"checked\"" : ""); ?> /></td>
									<td style="vertical-align: top">
										<label for="privacy_level_1"><strong>Minimal Profile</strong>: show minimal information about me.</label><br />
										<span class="content-small">This means that normal logged in users will only be able to view your name and role. In other words, people will not be able to get your e-mail address or other contact information.</span>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
			</tbody>
		</table>
	</form>
			
		<?php
	} else {
		add_notice("Unfortunately your ".APPLICATION_NAME." profile is not accessible at this time, please try again later.");

		echo display_notice();

		application_log("error", "A user profile was not available in the database");
	}
}
?>