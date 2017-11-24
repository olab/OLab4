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
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_AAMC_CI"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Delete Reports");

	echo "<h1>Delete AAMC Curriculum Inventory Reports</h1>";

	$report_ids = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (empty($_POST["delete"]))) {
				$ERROR++;
				$ERRORSTR[] = "You must select at least 1 notice to delete by checking the checkbox to the left the notice.";

				application_log("notice", "Notice delete page accessed without providing any notice id's to delete.");
			} else {
				foreach ($_POST["delete"] as $raci_id) {
					$raci_id = (int) trim($raci_id);
					if ($raci_id) {
						$query = "SELECT `organisation_id` FROM `reports_aamc_ci` WHERE `raci_id` = ".$db->qstr($raci_id). " AND `report_active` = '1'";
						$organisation_id = $db->GetOne($query);
						if ($organisation_id && ($organisation_id == $ENTRADA_USER->getActiveOrganisation())) {
							$report_ids[] = $raci_id;
						}
					}
				}

				if(empty($report_ids)) {
					add_error("You must select at least one report to delete.");
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
			$query = "UPDATE `reports_aamc_ci` SET `report_active` = '0' WHERE `raci_id` IN (".implode(", ", $report_ids).")";
			if ($db->Execute($query)) {
				$url = ENTRADA_URL."/admin/reports/aamc";

				$ONLOAD[] = "setTimeout('window.location=\\'".$url."', 5000)";

				if ($total_removed = $db->Affected_Rows()) {
					add_success("You have successfully deactived ".$total_removed." AAMC Curriculum Inventory Report".(($total_removed != 1) ? "s" : "").".<br /><br />You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".$url."\">click here</a> if you do not wish to wait.");

					echo display_success();

					application_log("success", "Successfully deactived raci_ids: ".implode(", ", $report_ids));
				} else {
					add_error("We were unable to deactive the requested reports from the system. The system administrator has been informed of this issue and will address it shortly; please try again later.");

					echo display_error();

					application_log("error", "Failed to deactivate any raci_ids: ".implode(", ", $report_ids).". Database said: ".$db->ErrorMsg());
				}
			} else {
				add_error("We were unable to deactive the requested reports from the system. The system administrator has been informed of this issue and will address it shortly; please try again later.");

				echo display_error();

				application_log("error", "Failed to execute deactivate query for raci_ids: ".implode(", ", $report_ids).". Database said: ".$db->ErrorMsg());
			}
		break;
		case 1 :
		default :
			if ($ERROR) {
				echo display_error();
			}

			$query = "SELECT * FROM `reports_aamc_ci` WHERE `raci_id` IN (".implode(", ", $report_ids).") AND `report_active` = '1' ORDER BY `report_date` DESC";
			$results = $db->GetAll($query);
			if ($results) {
				echo display_notice(array("Please review the following AAMC Curriculum Inventory reports to ensure that you wish to deactivate them. A system administrator can recover removed reports at a later time if required."));
				?>
				<form action="<?php echo ENTRADA_URL; ?>/admin/reports/aamc?section=delete&amp;step=2" method="post">
				<table class="tableList" cellspacing="0" summary="List of AAMC Curriculum Inventory Reports">
					<colgroup>
						<col class="modified" />
						<col class="date" />
						<col class="title" />
						<col class="general" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="date sortedDESC">Report Date</td>
							<td class="title">Report Title</td>
							<td class="general">Status</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Confirm Removal" />
							</td>
						</tr>
					</tfoot>
					<tbody>
					<?php
					foreach ($results as $result) {
						$url = ENTRADA_RELATIVE . "/admin/reports/aamc/manage?id=".$result["raci_id"];

						echo "<tr>\n";
						echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["raci_id"]."\" checked=\"checked\" /></td>\n";
						echo "	<td class=\"date\"><a href=\"".$url."\">".date("Y-m-d", $result["report_date"])."</a></td>\n";
						echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($result["report_title"])."</a></td>\n";
						echo "	<td class=\"general\"><a href=\"".$url."\">".ucwords(strtolower($result["report_status"]))."</a></td>\n";
						echo "</tr>\n";
					}
					?>
					</tbody>
				</table>
				</form>
				<?php
			}
		break;
	}
}