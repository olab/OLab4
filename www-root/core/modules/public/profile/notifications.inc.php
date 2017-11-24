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

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$PROXY_ID	    = $ENTRADA_USER->getID();
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/profile?section=notifications", "title" => "Notification Preferences");

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


	$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/profile.js\"></script>";

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
	require_once("Classes/notifications/NotificationUser.class.php");
	echo "<h1>Notification Preferences</h1>";
	$query = "SELECT `nuser_id` FROM `notification_users` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
	$notification_user_ids = $db->GetAll($query);
	if ($notification_user_ids) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "
		<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#notificationsTable').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'aoColumns': [
						null,
						null,
						null,
						{'sType': 'alt-string'},
						{'sType': 'alt-string'}
					],
                    'bAutoWidth': false
				}
			);
		});
		</script>";
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
		$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/default.css\" rel=\"stylesheet\" type=\"text/css\" />";
		$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/medtech.css\" rel=\"stylesheet\" type=\"text/css\" />";

		if (isset($_GET["action"]) && ($_GET["action"] == "unsubscribe")) {
			if (isset($_GET["id"]) && ($RECORD_ID = (int)$_GET["id"])) {
				$notification_user = NotificationUser::getByID($RECORD_ID);
				$HEAD[] = "<script type=\"text/javascript\">jQuery(document).ready(function() { promptNotifications(0, ".$RECORD_ID.", '".$notification_user->getContentTypeName()."'); });</script>";
			}
		}
		if (isset($_GET["action"]) && ($_GET["action"] == "digest-mode")) {
			if (isset($_GET["id"]) && ($RECORD_ID = (int)$_GET["id"])) {
				$notification_user = NotificationUser::getByID($RECORD_ID);
				$HEAD[] = "<script type=\"text/javascript\">jQuery(document).ready(function() { promptNotificationsDigest(1, ".$RECORD_ID.", '".$notification_user->getContentTypeName()."'); });</script>";
			}
		}
		?>
		<div id="notifications-toggle" style="display: inline; padding-top: 4px; width: 100%; text-align: right;"></div>
		<script type="text/javascript">
		function promptNotifications(enabled, nuser_id, content_type) {
			Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "begin" : "stop") +' receiving notifications when new comments or changes are made on this '+content_type+'?',
				{
					id:				'requestDialog',
					width:			350,
					height:			75,
					title:			'Notification Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					destroyOnClose:	true,
					ok:				function(win) {
										new Window(	{
														id:				'resultDialog',
														width:			350,
														height:			75,
														title:			'Notification Result',
														className:		'medtech',
														okLabel:		'close',
														buttonClass:	'btn',
														resizable:		false,
														draggable:		false,
														minimizable:	false,
														maximizable:	false,
														recenterAuto:	true,
														destroyOnClose:	true,
														url:			'<?php echo ENTRADA_URL; ?>/api/notifications.api.php?nuser_id='+nuser_id+'&action=edit&active='+(enabled == 1 ? '1' : '0'),
														onClose:			function () {
																			new Ajax.Updater('notification_user_'+nuser_id+'_active', '<?php echo ENTRADA_URL; ?>/api/notifications.api.php?nuser_id='+nuser_id+'&action=view');
																		}
													}
										).showCenter();
										return true;
									}
				}
			);
		}
		
		function promptNotificationsDigest(enabled, nuser_id, content_type) {
			Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "begin" : "stop") +' receiving notifications once per day at most when new comments or changes are made on this '+content_type+'?',
				{
					id:				'requestDigestDialog',
					width:			350,
					height:			75,
					title:			'Digest Mode Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					destroyOnClose:	true,
					ok:				function(win) {
										new Window(	{
														id:				'resultDigestDialog',
														width:			350,
														height:			75,
														title:			'Digest Mode Result',
														className:		'medtech',
														okLabel:		'close',
														buttonClass:	'btn',
														resizable:		false,
														draggable:		false,
														minimizable:	false,
														maximizable:	false,
														recenterAuto:	true,
														destroyOnClose:	true,
														url:			'<?php echo ENTRADA_URL; ?>/api/notifications.api.php?nuser_id='+nuser_id+'&action=edit-digest&active='+enabled,
														onClose:			function () {
																			new Ajax.Updater('notification_user_'+nuser_id+'_digest_mode', '<?php echo ENTRADA_URL; ?>/api/notifications.api.php?nuser_id='+nuser_id+'&action=view-digest');
																		}
													}
										).showCenter();
										return true;
									}
				}
			);
		}
		</script>
        <h2>Active Notifications</h2>
		<table id="notificationsTable" class="tableList" style="width: 100%; margin: 10px 0px 10px 0px" cellspacing="0" cellpadding="0" border="0">
			<thead>
				<tr>
					<td class="modified">&nbsp;</td>
					<td class="title"><div class="noLink">Content Title</div></td>
					<td class="date"><div class="noLink">Content Type</div></td>
					<td class="date-smallest"><div class="noLink">Digest Mode</div></td>
					<td class="date-smallest"><div class="noLink">Active</div></td>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($notification_user_ids as $nuser_id) {
					$nuser_id = $nuser_id["nuser_id"];
					$notification_user = NotificationUser::getByID($nuser_id);
					$url = $notification_user->getContentURL();
					echo "<tr id=\"notification-user-".$nuser_id."\">\n";
					echo "	<td class=\"modified\">&nbsp;</td>\n";
					echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($notification_user->getContentTitle())."</a></td>\n";
					echo "	<td class=\"date\">".ucwords($notification_user->getContentTypeName())."</td>\n";
					echo "	<td class=\"date-smallest\" id=\"notification_user_".$nuser_id."_digest_mode\">".($notification_user->getContentType() != "logbook_rotation" ? "<span style=\"cursor: pointer;\" onclick=\"promptNotificationsDigest(".($notification_user->getDigestMode() ? "'0'" : "'1'").", ".$nuser_id.", '".$notification_user->getContentTypeName()."')\"><img src=\"".ENTRADA_URL."/images/btn-".($notification_user->getDigestMode() ? "approve.gif\" alt=\"Active\" />" : "unapprove.gif\" alt=\"Disabled\" />")."</span>" : "<span alt=\"N/A\">N/A</span>")."</td>\n";
					echo "	<td class=\"date-smallest\" id=\"notification_user_".$nuser_id."_active\"><span style=\"cursor: pointer;\" onclick=\"promptNotifications(".($notification_user->getNotifyActive() ? "'0'" : "'1'").", ".$nuser_id.", '".$notification_user->getContentTypeName()."')\"><img src=\"".ENTRADA_URL."/images/btn-".($notification_user->getNotifyActive() ? "approve.gif\" alt=\"Active\" />" : "unapprove.gif\" alt=\"Disabled\" />")."</span>"."</td>\n";
					echo "</tr>\n";
				}
				?>
			</tbody>
		</table>
		<?php
	}
	
	
	if ((defined("COMMUNITY_NOTIFICATIONS_ACTIVE")) && ((bool) COMMUNITY_NOTIFICATIONS_ACTIVE)) {
		?>
		<h2>Community Notifications</h2>
		<?php
		$query	= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `".AUTH_DATABASE."`.`user_data`.`id`=".$db->qstr($ENTRADA_USER->getID());
		$result	= $db->GetRow($query);
		if ($result) {
			?>
			<form action="<?php echo ENTRADA_URL; ?>/profile?section=notifications" method="post">
			<input type="hidden" name="action" value="notifications-update" />
			<table style="width: 100%;" cellspacing="1" cellpadding="1" border="0" summary="My MEdTech Profile">
			<thead>
				<tr>
					<td>
						<table style="width: 100%" cellspacing="4" cellpadding="2" border="0">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 97%" />
							</colgroup>
							<tbody>
								<tr>
									<td style="vertical-align: top"><input type="radio" id="enabled-notifications" name="enable-notifications" onclick="$('notifications-toggle').show()" value="1"<?php echo ($result["notifications"] ? " checked=\"checked\"" : ""); ?> /></td>
									<td style="vertical-align: top">
										<label for="enabled-notifications"><strong>Enable</strong> Community Notifications</label><br />
										<span class="content-small">You will be able to receive notifications from communities and enable notifications for different types of content.</span>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top"><input type="radio" id="disabled-notifications" name="enable-notifications" onclick="$('notifications-toggle').hide()" value="0"<?php echo (!$result["notifications"] ? " checked=\"checked\"" : ""); ?> /></td>
									<td style="vertical-align: top">
										<label for="disabled-notifications"><strong>Disable</strong> Community Notifications</label><br />
										<span class="content-small">You will no longer receive notifications from any communities and will not be able to enable notifications for any content.</span>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
						<input type="submit" class="btn btn-primary" value="Save Changes" />
					</td>
				</tr>
			</tfoot>
			<tbody id="notifications-toggle"<?php echo (($result["notifications"]) ? "" : " style=\"display: none\""); ?>>
				<tr>
					<td>
                        <hr />

                        <div class="alert alert-info">
						    Please select the notifications you would like to receive for each community you are a member of. If you are a community administrator, then you will also have the option of being notified when members join or leave your community.
                        </div>
						<?php
						$query = "	SELECT DISTINCT(a.`community_id`), a.`member_acl`, e.`community_title`, b.`notify_active` AS `announcements`, c.`notify_active` AS `events`, d.`notify_active` AS `polls`, f.`notify_active` AS `members`
									FROM `community_members` AS a
									LEFT JOIN `community_notify_members` AS b
									ON a.`community_id` = b.`community_id`
									AND a.`proxy_id` = b.`proxy_id`
									AND b.`notify_type` = 'announcement'
									LEFT JOIN `community_notify_members` AS c
									ON a.`community_id` = c.`community_id`
									AND a.`proxy_id` = c.`proxy_id`
									AND c.`notify_type` = 'event'
									LEFT JOIN `community_notify_members` AS d
									ON a.`community_id` = d.`community_id`
									AND a.`proxy_id` = d.`proxy_id`
									AND d.`notify_type` = 'poll'
									LEFT JOIN `communities` AS e
									ON a.`community_id` = e.`community_id`
									LEFT JOIN `community_notify_members` AS f
									ON a.`community_id` = f.`community_id`
									AND a.`proxy_id` = f.`proxy_id`
									AND f.`notify_type` = 'members'
									WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)."
									AND a.`member_active` = '1'";
						$community_notifications = $db->GetAll($query);
						if ($community_notifications) {
							?>
							<table>
							<tbody>
								<tr>
									<td style="width: 50%; vertical-align: top;">
										<ul class="notify-communities">
										<?php
										$count = 0;
										foreach ($community_notifications as $key => $community) {
											$count++;
											if (($count != ((int)(round(count($community_notifications)/2))+1))) {
												?>
												<li>
													<strong><?php echo $community["community_title"]; ?></strong>
													<ul class="notifications">
														<li><label><input type="checkbox" name="notify_announcements[<?php echo $community["community_id"]; ?>]" value="1"<?php echo (!isset($community["announcements"]) || $community["announcements"] == 1 ? " checked=\"checked\"" : ""); ?> /> Announcements</label></li>
														<li><label><input type="checkbox" name="notify_events[<?php echo $community["community_id"]; ?>]" value="1" <?php echo (!isset($community["events"]) || $community["events"] == 1 ? " checked=\"checked\"" : ""); ?> /> Events</label></li>
														<li><label><input type="checkbox" name="notify_polls[<?php echo $community["community_id"]; ?>]" value="1" <?php echo (!isset($community["polls"]) || $community["polls"] == 1 ? " checked=\"checked\"" : ""); ?> /> Polls</label></li>
														<?php
														if ($community["member_acl"]) {
															?>
															<li><label><input type="checkbox" name="notify_members[<?php echo $community["community_id"]; ?>]" value="1" <?php echo ($community["members"] == 1 ? " checked=\"checked\"" : ""); ?> /> Members Joining / Leaving (Admin Only)</label></li>
															<?php
														}
														?>
													</ul>
												</li>
												<?php
											} else {
												?>
													</ul>
												</td>
												<td style="width: 50%; vertical-align: top">
													<ul class="notify-communities">
														<li>
															<strong><?php echo $community["community_title"]; ?></strong>
															<ul class="notifications">
																<li><label><input type="checkbox" name="notify_announcements[<?php echo $community["community_id"]; ?>]" value="1"<?php echo (!isset($community["announcements"]) || $community["announcements"] == 1 ? " checked=\"checked\"" : ""); ?> /> Announcements</label></li>
																<li><label><input type="checkbox" name="notify_events[<?php echo $community["community_id"]; ?>]" value="1" <?php echo (!isset($community["events"]) || $community["events"] == 1 ? " checked=\"checked\"" : ""); ?> /> Events</label></li>
																<li><label><input type="checkbox" name="notify_polls[<?php echo $community["community_id"]; ?>]" value="1" <?php echo (!isset($community["polls"]) || $community["polls"] == 1 ? " checked=\"checked\"" : ""); ?> /> Polls</label></li>
																<?php
																if ($community["member_acl"]) {
																	?>
																	<li><label><input type="checkbox" name="notify_members[<?php echo $community["community_id"]; ?>]" value="1" <?php echo ($community["members"] == 1 ? " checked=\"checked\"" : ""); ?> /> Members Joining / Leaving (Admin Only)</label></li>
																	<?php
																}
																?>
															</ul>
														</li>
														<?php
											}
										}
										?>
										</ul>
									</td>
								</tr>
							</tbody>
							</table>
							<?php
						} else {
							$NOTICE++;
							$NOTICESTR[] = "You are not currently a member of any communities, so community e-mail notifications will not be sent to you.";
	
							echo display_notice();
						}
						?>
					</td>
				</tr>
			</tbody>
			</table>
			</form>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[]	= "Unfortunately your ".APPLICATION_NAME." profile is not accessible at this time, please try again later.";
	
			echo display_notice();
	
			application_log("error", "A user profile was not available in the database? Database said: ".$db->ErrorMsg());
		}
	}
}
?>