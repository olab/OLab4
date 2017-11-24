<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list all available polls within this page of a community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_POLLS"))) {
    exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

/**
 * Add the javascript for deleting polls.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-poll")) {
	?>
	<script type="text/javascript">
		function pollDelete(id) {
			Dialog.confirm('Do you really wish to remove the '+ $('poll-' + id + '-title').innerHTML +' poll from this community?<br /><br />If you confirm this action, you will be deactivating this poll and all votes within it.',
				{
					id:				'requestDialog',
					width:			350,
					height:			125,
					title:			'Delete Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					ok:				function(win) {
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-poll&id='+id;
										return true;
									}
				}
			);
		}
	</script>
	<?php
}
?>
<div id="module-header">
	<?php 
	if (COMMUNITY_NOTIFICATIONS_ACTIVE && $LOGGED_IN && $_SESSION["details"]["notifications"]) { ?>
		<div id="notifications-toggle"></div>
		<script type="text/javascript">
		function promptNotifications(enabled) {
			Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications for new polls on this page?',
				{
					id:				'requestDialog',
					width:			350,
					height:			100,
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
														height:			100,
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
														url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$PAGE_ID; ?>&type=poll&action=edit&active='+(enabled == 1 ? '0' : '1'),
														onClose:			function () {
																			new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$PAGE_ID; ?>&type=poll&action=view');
																		}
													}
										).showCenter();
										return true;
									}
				}
			);
		}
		
		</script>
		<?php
		$ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$PAGE_ID."&type=poll&action=view')";
	}
	if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-poll")) {
		?>
		<div style="float: right">
			<ul class="page-action">
				<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-poll" class="btn btn-success">Add Poll</a></li>
				<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-poll&term=vote" class="btn btn-success">Add Vote</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
		<?php
	}

	$query		= "
				SELECT a.*
				FROM `community_polls` AS a
				WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND a.`poll_active` = '1'
				".((!$LOGGED_IN) ? " AND a.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND a.`allow_member_read` = '1'" : "") : " AND a.`allow_troll_read` = '1'"))."
				".((!$COMMUNITY_ADMIN) ? " AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")" : "")."
				AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
				ORDER BY a.`poll_order` ASC, a.`poll_title` ASC";
	$results	= $db->GetAll($query);
	if ($results) {
		?>
		<table class="table table-striped table-bordered">
		<colgroup>
			<col style="width: 50%" />
			<col style="width: 10%" />
			<col style="width: 10%" />
			<col style="width: 30%" />
		</colgroup>
		<thead>
			<tr>
				<td>Title</td>
				<td>Voters</td>
				<td>Votes</td>
				<td>Available Until</td>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($results as $result) {
				$accessible	= TRUE;
				$allowVote  = FALSE;
				$voteInfo	= communities_polls_latest($result["cpolls_id"]);
				$specificMembers = communities_polls_specific_access($result['cpolls_id']);
				if ($LOGGED_IN) {
					$vote_record = communities_polls_votes_cast_by_member($result["cpolls_id"], $ENTRADA_USER->getActiveId());
				} else {
					$vote_record = array("votes" => 0);
				}
				$allow_main_load = false;

				if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
					$accessible = FALSE;
				}				
				
				// Check to see if this is a poll that this user can vote in.
				if ($COMMUNITY_ADMIN || (($COMMUNITY_MEMBER && (int)$result['allow_member_vote'] == 1)
				|| (!(int) $community_details["community_protected"] && (int)$result['allow_public_vote'] == 1)
				|| (!(int) $community_details["community_registration"] && (int)$result['allow_troll_vote'] == 1)))
				{
					// Check to see if only specific members can vote before checking if they've voted
					if ((count($specificMembers) == 0) || (is_array($specificMembers) && in_array($ENTRADA_USER->getActiveId(), $specificMembers)))
					{
						// Check for multiple votes
						if ($result["allow_multiple"] == 0)
						{
							$query	= "SELECT `proxy_id` FROM `community_polls_responses`, `community_polls_results`
							WHERE `cpolls_id` = ".$result['cpolls_id']."
							AND `community_polls_responses`.`cpresponses_id` = `community_polls_results`.`cpresponses_id`
							AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."";
							
							if ($multiResults = $db->GetAll($query))
							{
								$allowVote = FALSE;
							}
							else 
							{
								$allowVote = TRUE;
							}
						}
						else if ($result["allow_multiple"] == 1)
						{	
							if ($result["number_of_votes"] == 0)
							{
								$allowVote = TRUE;
							}
							else 
							{					
								$query	= "SELECT count(`proxy_id`) as `times_voted`
								FROM `community_polls_responses`, `community_polls_results`
								WHERE `cpolls_id` = ".$result['cpolls_id']."
								AND `community_polls_responses`.`cpresponses_id` = `community_polls_results`.`cpresponses_id`
								AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."";
								
								$multiResults = $db->GetRow($query);
								
								if ($multiResults['times_voted'] >= $result["number_of_votes"])
								{
									$allowVote = FALSE;
								}
								else 
								{
									$allowVote = TRUE;
								}
							}
						}
					}
				}
				
				$show_results = false;
				if ($COMMUNITY_ADMIN) {
					if ((isset($voteInfo["votes_cast"]) && (int)$voteInfo["votes_cast"] > 0) || (isset($vote_record["votes"]) && (int)$vote_record["votes"] > 0)) {
						$show_results = true;
					}
					$allow_main_load = true;
				} elseif (($COMMUNITY_MEMBER && (int)$result["allow_member_results"] == 1) || (!(int) $community_details["community_protected"] && (int)$result["allow_public_results"] == 1) || (!(int) $community_details["community_registration"] && (int)$result["allow_troll_results"] == 1)) {
					if ((count($specificMembers) == 0) || (is_array($specificMembers) && in_array($ENTRADA_USER->getActiveId(), $specificMembers)))
					{
						$allow_main_load = true;
						if ((int)$voteInfo["votes_cast"] > 0) {
							$show_results = true;
						}
					} 
				} elseif (($COMMUNITY_MEMBER && (int)$result["allow_member_results_after"] == 1) || (!(int) $community_details["community_protected"] && (int)$result["allow_public_results_after"] == 1) || (!(int) $community_details["community_registration"] && (int)$result["allow_troll_results_after"] == 1)) {
					if (((count($specificMembers) == 0) || (is_array($specificMembers) && in_array($ENTRADA_USER->getActiveId(), $specificMembers)))) {
						$allow_main_load = true;
						if (isset($vote_record["votes"]) && (int) $vote_record["votes"] > 0) {
							$show_results = true;
						}
					}
				} elseif (($COMMUNITY_MEMBER && (int)$result["allow_member_read"] == 1) || (!(int) $community_details["community_protected"] && (int) $result["allow_public_read"] == 1) || (!(int) $community_details["community_registration"] && (int) $result["allow_troll_read"] == 1)) {
					if ((count($specificMembers) == 0) || (is_array($specificMembers) && in_array($ENTRADA_USER->getActiveId(), $specificMembers))) {
						$allow_main_load = true;
					}
				}
				
				if ($allow_main_load) {
					echo "<tr".((!$accessible) ? " class=\"na\"" : "").">\n";
					echo "	<td>\n";
					echo 		"<span id=\"poll-".$result["cpolls_id"]."-title\">".html_encode($result["poll_title"])."</span>\n";
					if ($show_results) {
						echo	((communities_module_access($COMMUNITY_ID, $MODULE_ID, "view-poll")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-poll&amp;id=".$result["cpolls_id"]."\">results</a>)" : "");
					}
					echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-poll")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-poll&amp;id=".$result["cpolls_id"]."\">edit</a>)" : "");
					echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "vote-poll")) ? ($allowVote && ((int)$result["release_date"] < time() && ((int)$result["release_until"] > time() || (int)$result["release_until"] == 0)) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=vote-poll&amp;id=".$result["cpolls_id"]."\">vote</a>)" : "") : "");
					echo 		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-poll")) ? " (<a class=\"action\" href=\"javascript:pollDelete('".$result["cpolls_id"]."')\">delete</a>)" : "");
					echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "my-votes")) ? (isset($vote_record["votes"]) && (int)$vote_record["votes"] > 0 ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=my-votes&amp;id=".$result["cpolls_id"]."\">history</a>)" : "") : "");
					echo "		<div class=\"content-small\">".html_encode(limit_chars($result["poll_description"], 125))."</div>\n";
					echo "	</td>\n";
					echo "	<td style=\"text-align: center\">".$voteInfo["voters"]."</td>\n";
					echo "	<td style=\"text-align: center\">".$voteInfo["votes_cast"]."</td>\n";
					echo "	<td class=\"small\">\n";
					echo 	($result["release_until"] != "0" && isset($result["release_until"]) ? date("Y-m-d", $result["release_until"]) : "No End Date Set");
					echo "	</td>\n";
					echo "</tr>\n";
				}
			}
			?>
		</tbody>
		</table>
		<?php
	} else {
		$NOTICE++;
		$NOTICESTR[] = "There are currently no polls available on this page.<br /><br />".(($COMMUNITY_ADMIN) ? "As a community adminstrator you can add polls by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-poll\">Add Poll</a>." : "");

		echo display_notice();
	}
	?>
</div>