
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
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete",false)) {
	add_error($translate->_("module_no_permission") . str_ireplace(array("%admin_email%","%admin_name%"), array(html_encode($AGENT_CONTACTS["administrator"]["email"]),html_encode($AGENT_CONTACTS["administrator"]["name"])), $translate->_("module_assistance")));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
?>
<h1>Delete Event Types</h1>
<?php
	require_once("Entrada/metadata/functions.inc.php");

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/metadata?section=delete&amp;org=".$ORGANISATION['organisation_id'], "title" => $translate->_("metadata_delete"));

	if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
		foreach ($_POST["remove_ids"] as $id){
			$PROCESSED["remove_ids"][] = (int) $id;
		}
	}

	if ($PROCESSED["remove_ids"]) {
		switch($STEP) {
			case 2:
			if(isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])){
				foreach($_POST["remove_ids"] as $id){

					if (MetaDataType::delete($id)) {
						add_success(str_ireplace("%id%",$id, $translate->_("metadata_notice_deleted")));
					} else {
						add_error(str_ireplace("%id%",$id, $translate->_("metadata_error_delete_type")));
					}
				}


				if($SUCCESS)
					echo display_success();
				if($NOTICE)
					echo display_notice();
				if($ERROR)
					echo display_error();
			}
			else{
				add_error($translate->_("metadata_error_notypes").str_ireplace(array("%time%","%url%"), array(5,html_encode(ENTRADA_URL."/admin/settings/manage/metadata/?org=".$ORGANISATION_ID)), $translate->_("metadata_redirect")));

				echo display_error();
			}
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/metadata/?org=".$ORGANISATION_ID."\\'', 5000)";
			break;
		case 1:
		default:
			add_notice($translate->_("metadata_notice_review"));
			echo display_notice();
			?>
			<form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/metadata?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>&step=2" method="post" id="delete_form">
				<table class="tableList" cellspacing="0" summary="List of Curriculum Layout">
					<colgroup>
						<col class="modified"/>
						<col class="title"/>
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="title"><?php echo $translate->_("metadata_topic");?></td>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($PROCESSED["remove_ids"] as $id) {
//						$query = "SELECT * FROM `meta_types` WHERE `meta_type_id` = ".$db->qstr($id);
//						$type = $db->GetRow($query);
						$type = MetaDataType::get($id);
						?>
						<tr>
							<td><input type="checkbox" value="<?php echo $id;?>" name ="remove_ids[]" class="checkboxes" checked="checked" disabled="disabled"/></td>
							<td><?php echo $type->getLabel();?></td>
						</tr>
						<?php
						$query = "SELECT * FROM `meta_types` WHERE `parent_type_id` = ".$db->qstr($id);
						$types = MetaDataTypes::getSelectionByParent($id);
						foreach ($types as $type){
							?>
							<tr>
								<td><input type="checkbox" value="<?php echo $type["meta_type_id"];?>" name ="remove_ids[]" class="checkboxes" checked="checked" disabled="disabled"/></td>
								<td><?php echo $type["label"];?></td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
				<br />
				<input type="button" value="<?php echo $translate->_("metadata_confirm");?>" class="btn btn-danger" id="delete_button"/>
				<script type="text/javascript">
					jQuery('#delete_button').click(function(){
						jQuery('.checkboxes').each(function(){
							jQuery(this).removeAttr('disabled');
						});

						jQuery('#delete_form').submit();
					});
				</script>
			</form>
			<?php
			break;
		}
	}
}
