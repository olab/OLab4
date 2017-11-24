<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} else if (!$ENTRADA_ACL->amIAllowed('event', 'create', false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	?>
	<style type="text/css">
		#draft-list_length {padding:5px 4px 0 0;}
		#draft-list_filter {-moz-border-radius:10px 10px 0px 0px; border: 1px solid #9D9D9D;border-bottom:none;background-color:#FAFAFA;font-size: 0.9em;padding:3px;}
		#draft-list_paginate a {margin:2px 5px;}
	</style>
	<h1>My Draft Learning Event Schedules</h1>
    <div  class="row-fluid"><a class="btn btn-primary pull-right" href="<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=create-draft">Create New Draft</a></div>
	<br />
	<?php
    $drafts = Models_Event_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID());
	if ($drafts) {
		add_notice("<p>You currently have <strong>".count($drafts)."</strong> draft".((count($drafts) > 1) ? "s" : "")." on the go.</p><p>When you are finished working on your draft event schedule you can click <strong>Publish Drafts</strong> to schedule them to be imported.</p>");
		echo display_notice();
		?>
			<form name="frmSelect" id="frmSelect" action="<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=delete" method="post">
			<table class="table table-striped table-bordered" id="draft-list" cellspacing="0" cellpadding="1" summary="List of Events">
				<thead>
					<tr>
						<th class="modified" width="5%">&nbsp;</th>
						<th class="title">Draft Name</th>
						<th class="date">Created</th>
						<th class="status">Status</th>
					</tr>
				</thead>
				<tbody>
				<?php

				$count_modified = 0;

				foreach ($drafts as $draft) {
					echo "<tr id=\"draft-".$draft->getDraftID()."\" rel=\"".$draft->getDraftID()."\" class=\"draft".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
					echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$draft->getDraftID()."\" /></td>\n";
					echo "	<td class=\"title\">".(($draft->getStatus() == "open") ? "<a href=\"".$url."?section=edit&draft_id=".$draft->getDraftID()."\" title=\"Draft Name\">" : "") .$draft->getName().(($draft->getStatus() == "open") ? "</a>" : "" )."</td>\n";
					echo "	<td class=\"date\">".(($draft->getStatus() == "open") ? "<a href=\"".$url."?section=edit&draft_id=".$draft->getDraftID()."\" title=\"Duration\">" : "").date("Y-m-d", $draft->getCreated()).(($draft->getStatus() == "open") ? "</a>" : "")."</td>\n";
					echo "	<td class=\"status\">".(($draft->getStatus() == "open") ? "<a href=\"".$url."?section=edit&draft_id=".$draft->getDraftID()."\" title=\"Draft Status\">" : "").$draft->getStatus().(($draft->getStatus() == "open") ? "</a>" : "")."</td>\n";
					echo "</tr>\n";
				}
				?>
				</tbody>
			</table>
			<?php if ($ENTRADA_ACL->amIAllowed("event", "delete", false) or $ENTRADA_ACL->amIAllowed("event", "create", false)) : ?>
			<table width="100%">
				<tfoot>
					<tr>
						<td></td>
						<td style="padding-top: 10px" colspan="2">
							<?php
							if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
								?>
								<input type="submit" class="btn btn-danger" value="Delete Selected" />
								<?php
							} ?>
						</td>
						<td style="padding-top: 10px; text-align: right" colspan="1">
							<?php
							if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
								?>
								<input type="submit" class="btn" value="Reopen Drafts" onclick="jQuery('#frmSelect').attr('action', '<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=status&action=reopen')" />
								<input type="submit" class="btn btn-primary" value="Publish Drafts" onclick="jQuery('#frmSelect').attr('action', '<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=status&action=approve')" />
								<?php
							}
							?>
						</td>
					</tr>
				</tfoot>
			</table>
			<?php endif; ?>
			</form>

		<?php
	} else {
		?>
        <div class="display-generic">
            <h3>No draft schedules</h3>
            <p>You are not currently working on any draft schedules. You can create a new draft schedule or have an administrator add you to an existing one.</p>
        </div>
        <?php
	}
}