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
 * This file is part of the department management feature and lists the departments
 * in the system for the currently active organisation.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	?>
	<h1>Departments</h1>
	<div class="row-fluid">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/departments?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> Add Department</a>
        </span>
	</div>
	<br />
	<?php
	$query = "	SELECT a.* FROM " . AUTH_DATABASE . ". `departments` AS a
				WHERE a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
				AND a.`department_active` = 1
				ORDER BY a.`department_title` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/departments?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
			<table class="table table-striped" summary="Departments">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tbody>
					<?php
						foreach($results as $result){
							echo "<tr><td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$result["department_id"]."\"/></td>";
							echo"<td><a href=\"".ENTRADA_URL."/admin/settings/manage/departments?section=edit&amp;org=".$ORGANISATION_ID."&amp;department_id=".$result["department_id"]."\">".$result["department_title"]."</a></td></tr>";
						}
					?>
				</tbody>
			</table>
			<input type="submit" class="btn btn-danger" value="Delete Selected" />
		</form>
		<?php
	} else {
		add_notice("There are currently no Departments in this organization.");
		echo display_notice();
	}
}
