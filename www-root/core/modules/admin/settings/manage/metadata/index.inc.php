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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
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
	<h1>User Meta Data</h1>

	<div class="row-fluid">
		<span class="pull-right">
			<a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/metadata?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> Add Meta Data</a>
		</span>
	</div>
	<br />
	<?php
	$query = "	SELECT DISTINCT a.`meta_type_id`, a.`label`,a.`parent_type_id`
				FROM `meta_types` AS a 
				JOIN `meta_type_relations` AS b 
				ON a.`meta_type_id` = b.`meta_type_id` 
				AND b.`entity_value` LIKE '".$ORGANISATION_ID.":%'";
	$metadata_types = $db->GetAll($query);
	if ($metadata_types) {
		?>
		<form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/metadata?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
			<table class="table table-striped" summary="User Meta Data">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tbody>
					<?php
					foreach ($metadata_types as $type) {
						if ($type["parent_type_id"] == null){
							echo "<tr>";
							echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$type["meta_type_id"]."\" id=\"parent-".$type["meta_type_id"]."\" onclick=\"selectChildren(".$type["meta_type_id"].")\"/></td>";
							echo"	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/metadata?section=edit&amp;org=".$ORGANISATION_ID."&amp;meta=".$type["meta_type_id"]."\">".$type["label"]."</a></td>";
							echo "</tr>";
							foreach ($metadata_types as $child) {
								if ($child["parent_type_id"] == $type["meta_type_id"]) {
									echo "<tr>";
									echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$child["meta_type_id"]."\" class=\"child-".$type["meta_type_id"]."\"/></td>";
									echo "	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/metadata?section=edit&amp;org=".$ORGANISATION_ID."&amp;meta=".$child["meta_type_id"]."\">".$type["label"]." → ".$child["label"]."</a></td>";
									echo "</tr>";
								}
							}
						}
					}
					?>
				</tbody>
				<script type="text/javascript">
					function selectChildren(id){
						$$('.child-'+id).each(function(checkbox){
							checkbox.checked = $('parent-'+id).checked;
							checkbox.disabled = checkbox.checked;
						});
					}
				</script>
			</table>
			<input type="submit" class="btn btn-danger" value="Delete Selected" />
		</form>
		<?php
	} else {
		add_notice("There are currently no User Meta Data types assigned to this organization.");
		echo display_notice();
	}
}
