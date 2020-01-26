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
 * The default file that is loaded when /admin/evaluations is accessed.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {

	?>
	<h1><?php echo $translate->_("Manage Clerkship Evaluations"); ?></h1>
	<?php

	if ($ENTRADA_ACL->amIAllowed("evaluation", "create", false)) {
		?>
		<div style="float: right">
            <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add"  class="btn btn-small btn-success pull-right cursor-pointer space-below"><i class="icon-plus-sign icon-white"></i> Add New Evaluation</a>
		</div>
		<div style="clear: both"></div>
		<?php
	}

    $evaluations = Classes_Evaluation::getAuthorEvaluations();

	if (count($evaluations)) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#evaluations').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'bInfo': false,
					'aoColumns': [
						null,
						null,
						{'sType': 'alt-string'},
						{'sType': 'alt-string'},
						null,
						null
					],
                    'bAutoWidth': false
				}
			);
		});
		</script>";
		if ($ENTRADA_ACL->amIAllowed("evaluation", "delete", false)) { 
            ?>
            <form action="<?php echo ENTRADA_URL; ?>/admin/evaluations?section=delete" method="post">
            <?php 
        }
        ?>
		<table id="evaluations" class="tableList" cellspacing="0" cellpadding="1" summary="List of Evaluations">
			<colgroup>
				<col class="modified" />
				<col class="title" />
				<col class="date" />
				<col class="date" />
				<col class="date-smallest" />
				<col class="attachment" />
			</colgroup>
			<thead>
				<tr>
					<td class="modified">&nbsp;</td>
					<td class="title">Title</td>
					<td class="date-small">Evaluation Start</td>
					<td class="date-small">Evaluation Finish</td>
					<td class="date-smallest">Type</td>
					<td class="attachment">&nbsp;</td>
				</tr>
			</thead>
			<?php if ($ENTRADA_ACL->amIAllowed("evaluation", "delete", false)) : ?>
			<tfoot>
				<tr>
					<td></td>
					<td colspan="5" style="padding-top: 10px">
						<input type="submit" class="btn btn-danger" value="Delete Selected" />
					</td>
				</tr>
			</tfoot>
			<?php endif; ?>
			<tbody>
			<?php
			foreach ($evaluations as $result) {
				$url = ENTRADA_URL."/admin/evaluations?section=progress&evaluation=".$result["evaluation_id"];

				echo "<tr id=\"evaluation-".$result["evaluation_id"]."\" class=\"evaluation\">\n";
				echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["evaluation_id"]."\" /></td>\n";
				echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($result["evaluation_title"])."</a></td>\n";
				echo "	<td class=\"date-small\"><a href=\"".$url."\" alt=\"".$result["evaluation_start"]."\">".date("M d/y g:ia", $result["evaluation_start"])."</a></td>\n";
				echo "	<td class=\"date-small\"><a href=\"".$url."\" alt=\"".$result["evaluation_finish"]."\">".date("M d/y g:ia", $result["evaluation_finish"])."</a></td>\n";
				echo "	<td class=\"date-smallest\"><a href=\"".$url."\">".ucwords($result["evaluation_type"])."</a></td>\n";
				echo "	<td class=\"attachment\">\n";
                if ($ENTRADA_ACL->amIAllowed(new EvaluationResource($result["evaluation_id"], $result["organisation_id"], true), 'update')) {
                echo "  <div class=\"btn-group\">\n";
                echo "      <button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
                echo "          <i class=\"fa fa-cog\" aria-hidden=\"true\"></i>\n";
                echo "      </button>";
                echo "      <ul class=\"dropdown-menu toggle-left\">\n";
                echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/evaluations?section=edit&id=".$result["evaluation_id"]."\">Edit Evaluation</a></li>";
                echo "          <li><a href=\"".ENTRADA_RELATIVE . "/evaluations?section=review&id=".$result["evaluation_id"]."\">View Evaluation Report</a></li>";
                echo "      </ul>\n";
                echo "  </div>\n";
                } else {
                    echo "&nbsp;";
                }
                echo "  </td>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
		</table>
		<?php if ($ENTRADA_ACL->amIAllowed("evaluation", "delete", false)) : ?>
		</form>
		<?php
		endif;
	} else {
		?>
		<div class="display-notice">
			<h3>No Available Evaluations</h3>
			There are currently no available evaluations in the system. To begin click the <strong>Add New Evaluation</strong> link above.
		</div>
		<?php
	}
}