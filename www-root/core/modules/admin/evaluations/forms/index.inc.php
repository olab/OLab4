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
 * This file displays the list of all evaluation forms available in the system.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	?>
	<h1>Manage Evaluation Forms</h1>

    <div style="float: right">
        <a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=add"  class="btn btn-small btn-success pull-right cursor-pointer space-below"><i class="icon-plus-sign icon-white"></i> Create New Evaluation Form</a>
    </div>
	<div class="clear"></div>
	<?php
	$results = Classes_Evaluation::getAuthorEvaluationForms();
	if ($results) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#evaluationforms').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'bInfo': false,
                    'bAutoWidth': false
				}
			);
		});
		</script>";
		?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=delete" method="post">
		<table class="tableList" id="evaluationforms" cellspacing="0" summary="List of Evaluation Forms">
		<colgroup>
			<col class="modified" />
			<col class="general" />
			<col class="title" />
		</colgroup>
		<thead>
			<tr>
				<td class="modified">&nbsp;</td>
				<td class="general">Form Type</td>
				<td class="title">Evaluation Form Title</td>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td></td>
				<td style="padding-top: 10px" colspan="2">
					<input type="submit" class="btn btn-danger" value="Disable Selected" />
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			foreach ($results as $result) {
				echo "<tr id=\"eform-".$result["eform_id"]."\">\n";
				echo "	<td><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["eform_id"]."\" /></td>\n";
				echo "	<td>".html_encode($result["target_title"])."</td>\n";
				echo "	<td><a href=\"".ENTRADA_URL."/admin/evaluations/forms?section=edit&amp;id=".$result["eform_id"]."\">".html_encode($result["form_title"])."</a></td>\n";
				echo "</tr>\n";
			}
			?>
		</tbody>
		</table>
		</form>
		<?php
	} else {
		?>
		<div class="display-generic">
			The Manage Forms tool allows you to create and manage forms that can be electronically distributed to groups of people.
			<br /><br />
			Creating evaluation forms is easy; to begin simply click the <strong>Create New Evaluation Form</strong> link above and follow the on-screen instructions.
		</div>
		<?php
	}
}