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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('poll', 'delete')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000);";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if(isset($_POST["expire_polls"])) {
		$PAGE_ACTION	= "expire";
	} else {
		$PAGE_ACTION	= "delete";
	}

	$BREADCRUMB[]	= array("url" => "", "title" => ucwords($PAGE_ACTION)." Polls");

	echo "<h1>".ucwords($PAGE_ACTION)." Polls</h1>";

	$POLL_IDS	= array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
				$ERROR++;
				$ERRORSTR[] = "You must select at least 1 notice to ".$PAGE_ACTION." by checking the checkbox to the left the poll.";

				application_log("notice", "Poll ".$PAGE_ACTION." page accessed without providing any poll id's to ".$PAGE_ACTION.".");
			} else {
				foreach($_POST["delete"] as $poll_id) {
					$poll_id = (int) trim($poll_id);
					if($poll_id) {
						$POLL_IDS[] = $poll_id;
					}
				}

				if(!@count($POLL_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid poll identifiers provided to ".$PAGE_ACTION.". Please ensure that you access this section through the poll index.";
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
			switch($PAGE_ACTION) {
				case "delete" :
					$total_removed	= 0;

					$query	= "SELECT `poll_id` FROM `poll_questions` WHERE `poll_id` IN (".implode(", ", $POLL_IDS).")";
					$results	= $db->GetAll($query);
					if($results) {
						foreach($results as $result) {
							/**
							 * Oh, if we were only using InnoDB...
							 */
							$query = "DELETE FROM `poll_results` WHERE `poll_id`=".$db->qstr($result["poll_id"]);
							$db->Execute($query);

							$query = "DELETE FROM `poll_answers` WHERE `poll_id`=".$db->qstr($result["poll_id"]);
							$db->Execute($query);

							$query = "DELETE FROM `poll_questions` WHERE `poll_id`=".$db->qstr($result["poll_id"]);
							$db->Execute($query);

							$total_removed += (int) $db->Affected_Rows();
						}
					}
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/polls\\'', 5000);";

					if($total_removed = $db->Affected_Rows()) {
						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully removed ".$total_removed." poll".(($total_removed != 1) ? "s" : "")." from the system.<br /><br />You will be automatically redirected to the poll index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/polls\">click here</a> if you do not wish to wait.";

						echo display_success();

						application_log("success", "Successfully removed poll ids: ".implode(", ", $POLL_IDS));
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to remove the requested polls from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

						echo display_error();

						application_log("error", "Failed to remove any poll ids: ".implode(", ", $POLL_IDS).". Database said: ".$db->ErrorMsg());
					}
				break;
				case "expire" :
					$query = "UPDATE `poll_questions` SET `poll_until`='".(time() - 1)."' WHERE `poll_id` IN (".implode(", ", $POLL_IDS).")";
					if($db->Execute($query)) {
						$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/polls\\'', 5000);";

						if($total_expired = $db->Affected_Rows()) {
							$SUCCESS++;
							$SUCCESSSTR[]  = "You have successfully expired ".$total_expired." poll".(($total_expired != 1) ? "s" : "")." in the system.<br /><br />You will be automatically redirected to the poll index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/polls\">click here</a> if you do not wish to wait.";

							echo display_success();

							application_log("success", "Successfully expired poll ids: ".implode(", ", $POLL_IDS));
						} else {
							$ERROR++;
							$ERRORSTR[] = "We were unable to expire the requested polls in the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

							echo display_error();

							application_log("error", "Failed to expire any poll ids: ".implode(", ", $POLL_IDS).". Database said: ".$db->ErrorMsg());
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to expire the requested polls in the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

						echo display_error();

						application_log("error", "Failed to execute update query for poll ids: ".implode(", ", $POLL_IDS).". Database said: ".$db->ErrorMsg());
					}
				break;
				default :
					$ERROR++;
					$ERRORSTR[] = "Unknown page action on poll delete page 2. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

					echo display_error();

					application_log("error", "Unknown page action [".$PAGE_ACTION."] on poll delete page 2.");
				break;
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			}

			$query	= "SELECT * FROM `poll_questions` WHERE `poll_id` IN (".implode(", ", $POLL_IDS).") ORDER BY `poll_target` ASC, `poll_until` ASC";
			$results	= $db->GetAll($query);
			if($results) {
				echo display_notice(array("Please review the following polls to ensure that you wish to permanently ".$PAGE_ACTION." them. This action cannot be undone."));
				if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
				<form action="<?php echo ENTRADA_URL; ?>/admin/polls?section=delete&amp;step=2" method="post">
				<?php endif; ?>
				<table class="tableList" cellspacing="0" summary="List of Polls">
				<colgroup>
					<col class="modified" />
					<col class="general" />
					<col class="title" />
					<col class="responses" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="general sortedASC" style="font-size: 12px"><div class="noLink">Poll Targets</div></td>
						<td class="title" style="font-size: 12px">Poll Question</td>
						<td class="responses" style="font-size: 12px">Responses</td>
					</tr>
				</thead>
				<?php if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
				<tfoot>
					<tr>
						<td></td>
						<td colspan="3" style="padding-top: 10px">
							<input type="submit" class="btn btn-danger" name="<?php echo $PAGE_ACTION; ?>_polls" value="Confirm <?php echo ucwords($PAGE_ACTION); ?>" />
						</td>
					</tr>
				</tfoot>
				<?php endif; ?>
				<tbody>
					<?php
					foreach($results as $result) {
						$expired	= false;
						$responses	= poll_responses($result["poll_id"]);

						if(!$responses) {
							$url	= ENTRADA_URL."/admin/polls?section=edit&amp;id=".$result["poll_id"];
						} else {
							$url	= "javascript: SeeResults('".$result["poll_id"]."')";
						}

						if(($poll_until = (int) $result["poll_until"]) && ($poll_until < time())) {
							$expired	= true;
						}

						echo "<tr id=\"poll-".$result["poll_id"]."\" class=\"poll".(($expired) ? " na" : "")."\">\n";
						echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["poll_id"]."\" checked=\"checked\" /></td>\n";
						echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Poll: ".((isset($POLL_TARGETS[$result["poll_target"]])) ? str_replace("&nbsp;", "", $POLL_TARGETS[$result["poll_target"]]) : $result["poll_target"])."\">" : "").((isset($POLL_TARGETS[$result["poll_target"]])) ? str_replace("&nbsp;", "", $POLL_TARGETS[$result["poll_target"]]) : $result["poll_target"]).(($url) ? "</a>" : "")."</td>\n";
						echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Poll: ".html_encode($result["poll_question"])."\">" : "").html_encode($result["poll_question"]).(($url) ? "</a>" : "")."</td>\n";
						echo "	<td class=\"responses\">".$responses."</td>\n";
						echo "</tr>\n";
					}
					?>
				</tbody>
				</table>
				<?php if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
				</form>
				<?php
				endif;
			}
		break;
	}
}