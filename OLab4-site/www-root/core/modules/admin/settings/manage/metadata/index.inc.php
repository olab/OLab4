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
	add_error($translate->_("module_no_permission") . str_ireplace(array("%admin_email%","%admin_name%"), array(html_encode($AGENT_CONTACTS["administrator"]["email"]),html_encode($AGENT_CONTACTS["administrator"]["name"])), $translate->_("module_assistance")));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	require_once("Entrada/metadata/functions.inc.php");

	echo "<h1>".$translate->_("metadata_heading")."</h1>";
	?>

	<div class="row-fluid">
		<span class="pull-right">
			<a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/metadata?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("metadata_add") ?></a>
		</span>
	</div>
	<br />
	<?php
	$metadata_types = MetaDataTypes::get();

	$top_types = getCategories($metadata_types);
	if ($top_types) {
	?>
		<form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/metadata?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
			<table class="table table-striped" summary="User Meta Data">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 97%" />
				</colgroup>
				<tbody>
					<?php
					foreach ($top_types as $type) {
						if ($type->getParent() == null){
							$restrictedRow = ($type->getRestricted() ? " [Admin view only]":"");
							echo "<tr>";
							echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$type->getID()."\" id=\"parent-".$type->getID()."\" onclick=\"selectChildren(".$type->getID().")\"/></td>";
							echo"	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/metadata?section=edit&amp;org=".$ORGANISATION_ID."&amp;meta=".$type->getID()."\"><b> ".$type->getLabel()."</b></a>$restrictedRow</td>";
							echo "</tr>";
							$child_types = getChildTypes($metadata_types,$type);
							$children = array();
							foreach ($child_types as $child) {
								if (in_array($child->getID(), $children)) {
									continue;
								}
								$children[] = $child->getID();
								if ((int)$child->getParentID() == $type->getID()) {
									echo "<tr>";
									echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$child->getID()."\" class=\"child-".$type->getID()."\"/></td>";
									echo "	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/metadata?section=edit&amp;org=".$ORGANISATION_ID."&amp;meta=".$child->getID()."\">".$type->getLabel()." → <b>".$child->getLabel()."</b></a>$restrictedRow</td>";
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
			<input type="submit" class="btn btn-danger" value="<?php echo $translate->_("metadata_button_delete") ?>" />
		</form>
		<?php
	} else {
		add_notice($translate->_("metadata_notice_none"));
		echo display_notice();
	}
}
