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
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_NOTICES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif($ENTRADA_ACL->amIAllowed('notices', 'delete')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Delete Notices");

	echo "<h1>Delete Notices</h1>";

	$NOTICE_IDS	= array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
				$ERROR++;
				$ERRORSTR[] = "You must select at least 1 notice to delete by checking the checkbox to the left the notice.";

				application_log("notice", "Notice delete page accessed without providing any notice id's to delete.");
			} else {
				foreach($_POST["delete"] as $notice_id) {
					$notice_id = (int) trim($notice_id);
					if($notice_id) {
						$query = "SELECT `organisation_id` FROM `notices` WHERE `notice_id` = ".$db->qstr($notice_id);
						$organisation_id = $db->GetOne($query);
						if ($ENTRADA_ACL->amIAllowed(new NoticeResource($organisation_id), "create")) {
							$NOTICE_IDS[] = $notice_id;
						}
					}
				}
				
				if(!@count($NOTICE_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid notice identifiers provided to delete. Please ensure that you access this section through the notice index.";
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$query = "DELETE FROM `notices` WHERE `notice_id` IN (".implode(", ", $NOTICE_IDS).")";
			if($db->Execute($query)) {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/notices\\'', 5000)";

				if($total_removed = $db->Affected_Rows()) {
					$query = "DELETE FROM `notice_audience` WHERE `notice_id` IN (".implode(", ", $NOTICE_IDS).")";
					$db->Execute($query);
					
					$SUCCESS++;
					$SUCCESSSTR[]  = "You have successfully removed ".$total_removed." notice".(($total_removed != 1) ? "s" : "")." from the system.<br /><br />You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/notices\">click here</a> if you do not wish to wait.";

					echo display_success();

					application_log("success", "Successfully removed notice ids: ".implode(", ", $NOTICE_IDS));
				} else {
					$ERROR++;
					$ERRORSTR[] = "We were unable to remove the requested notices from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

					echo display_error();

					application_log("error", "Failed to remove any notice ids: ".implode(", ", $NOTICE_IDS).". Database said: ".$db->ErrorMsg());
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "We were unable to remove the requested notices from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

				echo display_error();

				application_log("error", "Failed to execute remove query for notice ids: ".implode(", ", $NOTICE_IDS).". Database said: ".$db->ErrorMsg());
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			}
			
			$query	= "  SELECT a.*, CONCAT(b.`firstname`, ' ', b.`lastname`) AS notice_author 
						 FROM `notices` AS a
						 JOIN `".AUTH_DATABASE."`.`user_data` AS b
						 ON a.`created_by` = b.`id`
						 WHERE `notice_id` IN (".implode(", ", $NOTICE_IDS).") ORDER BY `display_until` ASC";
			$results	= $db->GetAll($query);
			if($results) {
				echo display_notice(array("Please review the following notices to ensure that you wish to permanently delete them. This action cannot be undone."));
				?>
				<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery("#delete-notices").on("click", function (event) {
							var checked = document.querySelectorAll("input.delete-control:checked").length === 0 ? false : true;
							if (!checked) {
								event.preventDefault();
								var errors = new Array();
								errors[0] = "You must select at least 1 notice to delete by checking the checkbox to the left the notice.";
								display_error(errors, "#msg");
							}
						});
					});
				</script>
				<div id="msg"></div>
				<form action="<?php echo ENTRADA_URL; ?>/admin/notices?section=delete&amp;step=2" method="post">
					<table id="notice-list" class="table table-striped table-bordered" cellspacing="0" summary="List of Notices">
						<thead>
							<tr>
								<th width="5%"></th>
								<th width="25%">Display Until</th>
								<th width="25%">Notice Author</th>
								<th width="45%">Notice Summary</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($results as $result) {
								$url			= ENTRADA_URL."/admin/notices?section=edit&amp;id=".$result["notice_id"];
								$expired		= false;

								if(($display_until = (int) $result["display_until"]) && ($display_until < time())) {
									$expired	= true;
								}

								$result["notice_summary"] = strip_tags($result["notice_summary"]);
								$result["notice_summary"] = (strlen($result["notice_summary"]) > 45 ? substr($result["notice_summary"], 0, 45)."..." : $result["notice_summary"]);

								echo "<tr id=\"notice-".$result["notice_id"]."\" class=\"notice".(($expired) ? " na" : "")."\">\n";
								echo "	<td class=\"modified\"><input class=\"delete-control\" type=\"checkbox\" name=\"delete[]\" value=\"".$result["notice_id"]."\" checked=\"checked\" /></td>\n";
								echo "	<td class=\"date\">".(($url) ? "<a href=\"".$url."\">" : "").date(DEFAULT_DATE_FORMAT, $result["display_until"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"notice_author\">".(($url) ? "<a href=\"".$url."\">" : "").html_encode($result["notice_author"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\">" : "").html_encode($result["notice_summary"]).(($url) ? "</a>" : "")."</td>\n";
								echo "</tr>\n";
							}
							?>
						</tbody>
					</table>
					<div class="row-fluid space-above">
						<a href="<?php echo ENTRADA_RELATIVE; ?>/admin/notices" class="btn">Cancel</a>
						<input id="delete-notices" type="submit" class="btn btn-danger pull-right" value="Confirm Removal" />
					</div>	
				</form>
				<?php
			}
		break;
	}
}