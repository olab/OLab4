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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_OBJECTIVES"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('objective', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	echo "<h1>Curriculum Tag Sets</h1>";
	if ($ENTRADA_ACL->amIAllowed("objective", "create", false)) {
		?>
		<div class="row-fluid">
			<span class="pull-right">
				<a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/objectives?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> Add Curriculum Tag Set</a>
			</span>
		</div>
		<br />
		<?php
	}

	$query = "SELECT a.*
				FROM `global_lu_objectives` AS a
				JOIN `objective_organisation` AS b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`objective_parent` = '0'
				AND a.`objective_active` = '1'
				AND b.`organisation_id` = ?
				ORDER BY a.`objective_order` ASC";
	$results = $db->GetAll($query, array($ORGANISATION_ID));
	if ($results) {
		?>
		<form action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives?section=delete&amp;org=".$ORGANISATION_ID; ?>" method="post">
			<table class="table table-striped" summary="Curriculum Tag Sets">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tbody>
					<?php
					foreach ($results as $result) {
						echo "<tr>";
						echo "	<td><input type=\"checkbox\" name=\"deactivate[]\" value=\"".$result["objective_id"]."\"/></td>";
						echo"	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/objectives?section=edit&amp;org=$ORGANISATION_ID&amp;id=".$result["objective_id"]."\">".$result["objective_name"]."</a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
			<input type="submit" class="btn btn-danger" value="Deactivate Selected" />
		</form>
		<?php
	} else {
		add_notice("There are currently no Curriculum Tag Sets assigned to this organization.");
		echo display_notice();
	}
}