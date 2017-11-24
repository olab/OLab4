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
 * This file displays the list of categories pulled
 * from the entrada_clerkship.categories table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CATEGORIES"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed("categories", "update", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/scriptaculous/sortable_tree.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	?>
	<h1>Clinical Rotation Categories</h1>
	<?php
	if ($ENTRADA_ACL->amIAllowed("categories", "create", false)) {
		?>
		<div class="row-fluid">
			<span class="pull-right">
				<a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/categories?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> Add Category</a>
			</span>
		</div>
		<br />
		<?php
	}

	$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
				WHERE `category_parent` = '0'
				AND `category_status` != 'trash'
				AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
				ORDER BY `category_order` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<form action="<?php echo ENTRADA_URL."/admin/settings/manage/categories?".replace_query(array("section" => "delete", "step" => 1)); ?>" method="post">
			<table class="table table-striped" summary="Clinical Rotation Categories">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tfoot>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" class="btn btn-danger" value="Delete Selected" /></td>
					</tr>
				</tfoot>
				<tbody>
					<?php
					foreach ($results as $result) {
						echo "<tr>";
						echo "	<td><input type=\"checkbox\" name=\"delete[".$result["category_id"]."][category_id]\" value=\"".$result["category_id"]."\"/></td>";
						echo"	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/categories?section=edit&amp;org=".$ORGANISATION_ID."&amp;id=".$result["category_id"]."\">".$result["category_name"]."</a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
		</form>
		<?php
	} else {
		add_notice("There are currently no Clerkship categories created in this organisation.");
		echo display_notice();
	}
}