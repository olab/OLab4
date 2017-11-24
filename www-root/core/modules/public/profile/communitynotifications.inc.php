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

	$PROXY_ID	    = $ENTRADA_USER->getID();
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/profile?section=communitynotifications", "title" => "Community Notifications");

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


	$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/profile.js\"></script>";
	$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE ."-community-notifications.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
	$HEAD[] = "<script>var PROV_STATE = \"". $prov_state ."\";</script>";
	$HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";


	echo Views_Profile_PageHeader::getCoursesSubnavigation("communitynotifications");
	?>

	<?php
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
	echo "<h1>Community Notifications</h1>";

	if ((defined("COMMUNITY_NOTIFICATIONS_ACTIVE")) && ((bool) COMMUNITY_NOTIFICATIONS_ACTIVE)) {
		?>
		<?php
		$result	= Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($result) {
			?>
			<form action="<?php echo ENTRADA_URL; ?>/profile?section=communitynotifications" method="post">
			<table style="width: 100%" cellspacing="4" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tbody>
				<tr>
					<td style="vertical-align: top"><input type="radio" id="enabled-notifications" name="enable-notifications" onclick="$('notifications-toggle').show()" value="1"<?php echo ($result->getNotifications() ? " checked=\"checked\"" : ""); ?> /></td>
					<td style="vertical-align: top">
						<label for="enabled-notifications"><strong>Enable</strong> Community Notifications</label><br />
						<span class="content-small">You will be able to receive notifications from communities and enable notifications for different types of content.</span>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top"><input type="radio" id="disabled-notifications" name="enable-notifications" onclick="$('notifications-toggle').hide()" value="0"<?php echo (!$result->getNotifications() ? " checked=\"checked\"" : ""); ?> /></td>
					<td style="vertical-align: top">
						<label for="disabled-notifications"><strong>Disable</strong> Community Notifications</label><br />
						<span class="content-small">You will no longer receive notifications from any communities and will not be able to enable notifications for any content.</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div class="pull-right">
							<input type="submit" class="btn btn-primary" value="Save Changes" />
						</div>
					</td>
				</tr>
				</tbody>
				<tfoot>
				<tr>
					<td colspan="2">
						<div class="alert alert-info">
							Please select the notifications you would like to receive for each community you are a member of. If you are a community administrator, then you will also have the option of being notified when members join or leave your community.
						</div>
					</td>
				</tr>
				</tfoot>
			</table>
			<input type="hidden" name="action" value="notifications-update" />
			<div id="msgs"></div>
			<div id="notification-notifications-container">
				<input id="form-search" class="form-search" type="hidden" name="url" value="<?php echo ENTRADA_URL."/admin/" . $MODULE  . "?step=2"; ?>">
					<input type="hidden" id="proxy_id" name="proxy_id" value="<?php echo (isset($PROXY_ID) ? $PROXY_ID : ""); ?>" />
					<div id="search-bar" class="search-bar space-below">
						<div class="row-fluid">
							<div class="pull-left">
								<div class="input-append space-right">
									<input type="text" id="notification-search" placeholder="<?php echo $MODULE_TEXT["placeholders"]["anotification_bank_search"]?>" class="input-large search-icon">
								</div>
							</div>
						</div>
						<div id="notification-summary"></div>
					</div>
					<div id="search-container" class="hide space-below medium"></div>
					<div id="notification-summary"></div>
					<div id="notification-msgs">
						<div id="notification-loading" class="hide">
							<p><?php echo $translate->_("Loading Notifications..."); ?></p>
							<img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
						</div>
					</div>
					<div id="notification-table-container">
						<table id="notifications-table" class="table table-bordered table-striped">
							<thead>
							<th width="60%" class="general">Title<i class="fa fa-sort notification-sort" aria-hidden="true" data-name="title" data-order=""></i></th>
							<th width="10%">Announcements</th>
							<th width="10%">Events</th>
							<th width="10%">Polls</th>
							<th width="10%">Members Joining/Leaving</th>
							</thead>
							<tbody>
							<tr id="no-notifications">
								<td colspan="6"><?php echo $translate->_("No Notifications to display"); ?></td>
							</tr>
							</tbody>
						</table>
					</div>
					<div id="notification-detail-container" class="hide"></div>
				<div id="delete-notifications-modal" class="modal hide fade">
					<form id="delete-notifications-modal-notification" class="form-horizontal" action="<?php echo ENTRADA_URL . "/" . $MODULE . "?section=api-notifications"; ?>" method="POST" style="margin:0px;">
						<input type="hidden" name="step" value="2" />
						<div class="modal-header"><h1><?php echo $MODULE_TEXT["index"]["title_modal_delete_anotification"]; ?></h1></div>
						<div class="modal-body">
							<div id="no-notifications-selected" class="hide">
								<p><?php echo $MODULE_TEXT["index"]["text_modal_no_anotifications_selected"] ?></p>
							</div>
							<div id="notifications-selected" class="hide">
								<p><?php echo $MODULE_TEXT["index"]["text_modal_delete_anotifications"] ?></p>
								<div id="delete-notifications-container"></div>
							</div>
						</div>
						<div class="modal-footer">
							<div class="row-fluid">
								<a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
								<input id="delete-notifications-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
							</div>
						</div>
					</form>
				</div>
				<div class="row-fluid">
					<a id="load-notifications" class="btn btn-block"><?php echo $translate->_("Load More Notifications"); ?> <span class="bleh"></span></a>
				</div>
			</div>
			</form>
			<?php
		} else {
			add_notice("Unfortunately your ".APPLICATION_NAME." profile is not accessible at this time, please try again later.");
	
			echo display_notice();
	
			application_log("error", "A user profile was not available in the database? Database said: ".$db->ErrorMsg());
		}
	}
}
?>