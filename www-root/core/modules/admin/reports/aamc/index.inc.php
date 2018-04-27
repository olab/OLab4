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
    $aamc = new Models_Reports_Aamc($ACTIVE_ORG->getID());

    $missing_eventtypes = $aamc->missingEventTypeMapping();
    if ($missing_eventtypes) {
        $message  = "The following <strong>" . $translate->_("Event Types") . "</strong> do not have mapped <strong>MedBiquitous Instructional Methods</strong>. This means that any Learning Events in your report that contain these " . $translate->_("Event Types") . " will be excluded. Click the links to below to map these types.";
        $message .= "<ol>\n";
        foreach ($missing_eventtypes as $eventtype) {
            $message .= "<li><a href=\"".ENTRADA_RELATIVE."/admin/settings/manage/eventtypes?section=edit&org=".$ACTIVE_ORG->getID()."&type_id=".(int) $eventtype["eventtype_id"]."\" target=\"_blank\">".html_encode($eventtype["eventtype_title"])."</a></li>\n";
        }
        $message .= "</ol>\n";

        echo display_error($message);
    }

    $missing_assessment_methods = $aamc->missingAssessmentMethodMapping();
    if ($missing_assessment_methods) {
        $message  = "The following <strong>Assessment Methods</strong> do not have mapped <strong>MedBiquitous Assessment Methods</strong>. This means that any Assessments in your report that contain these Characteristics will classified as &quot;Exam - Institutionally Developed, Written/Computer-based&quot;.";
        $message .= "<ol>\n";
        foreach ($missing_assessment_methods as $assessment_method) {
            $message .= "<li><a href=\"".ENTRADA_RELATIVE."/admin/settings/manage/characteristics?section=edit&org=".$ACTIVE_ORG->getID()."&id=".$assessment_method["id"]."\" target=\"_blank\">".html_encode($assessment_method["title"])."</a></li>\n";
        }
        $message .= "</ol>\n";

        echo display_notice($message);
    }

	?>

	<h1>AAMC Curriculum Inventory Reports</h1>

    <div class="row-fluid space-below medium">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_URL; ?>/admin/reports/aamc?section=add"><i class="icon-plus-sign icon-white"></i> Create New Report</a>
        </span>
    </div>


	<?php
	$query = "SELECT * FROM `reports_aamc_ci` WHERE `report_active` = '1' AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())." ORDER BY `report_date` DESC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/reports/aamc?section=delete" method="post">
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
						<input type="submit" class="btn btn-danger" value="Delete Selected" />
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			foreach ($results as $result) {
				$url = ENTRADA_RELATIVE . "/admin/reports/aamc/manage?id=".$result["raci_id"];

				echo "<tr>\n";
				echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["raci_id"]."\" /></td>\n";
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
	} else {
		?>
		<div class="display-notice">
			<h3>No Available AAMC Curriculum Inventory Reports</h3>
			<p>There are currently no AAMC Curriculum Inventory reports available for <strong><?php echo html_encode($ACTIVE_ORG->getTitle()); ?></strong>. To create a new report click the <strong>Create New Report</strong> link above.</p>
		</div>
		<?php
	}
}
