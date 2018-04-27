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
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
	add_error($translate->_("module_no_permission") . str_ireplace(array("%admin_email%","%admin_name%"), array(html_encode($AGENT_CONTACTS["administrator"]["email"]),html_encode($AGENT_CONTACTS["administrator"]["name"])), $translate->_("module_assistance")));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	require_once("Entrada/metadata/functions.inc.php");


	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/metadata?".replace_query(array("section" => "add"))."&amp;org=".$ORGANISATION_ID, "title" => $translate->_("metadata_add"));

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "objective_name" / Objective Name
			 */
			if (isset($_POST["metadata_title"]) && ($metadata_title = clean_input($_POST["metadata_title"], array("notags", "trim")))) {
				$PROCESSED["label"] = $metadata_title;
			} else {
				add_error($translate->_("metadata_error_missing_name"));
			}
			/**
			 * Required field "objective_name" / Objective Name
			 */
			if (isset($_POST["metadata_desc"]) && ($metadata_desc = clean_input($_POST["metadata_desc"], array("notags", "trim")))) {
				$PROCESSED["description"] = $metadata_desc;
			} else {
				$PROCESSED["description"] = '';
			}


			if(isset($_POST["associated_parent"]) && $parent = clean_input($_POST["associated_parent"],"int")){
				if($parent>0){
					$PROCESSED["parent_type_id"] = $parent;
					$results = MetaDataRelations::getRelationsByID($parent);
					if($results){
						$groups = array();
						foreach($results as $result){
							$entity_data = explode(":",$result->getEntityValue());
							$PROCESSED["groups"][] = $entity_data[1];
						}

					}

				}
			}
			if(!isset($PROCESSED["parent_type_id"])){
				if (isset($_POST["group_order"]) && strlen($_POST["group_order"])>0) {

						$groups = explode(",", trim($_POST["group_order"]));
						if ((is_array($groups)) && (count($groups))) {
							foreach($groups as $order => $group_id) {
								if ($group_id = clean_input($group_id, array("trim", "notags"))) {
									$PROCESSED["groups"][] = $group_id;
								} else {
									add_error($translate->_("metadata_error_groups"));
								}
							}
						}

				}
				else{
					add_error($translate->_("metadata_error_nogroups"));
				}
			}
			/**
			 * Required field "restricted" / Objective Name
			 */
			if (isset($_POST["metadata_restrict"]) && ($restricted = clean_input($_POST["metadata_restrict"], "int"))) {
				$PROCESSED["restricted"] = $restricted;
			} else {
				$PROCESSED["restricted"] = 0;
			}


			if (!$ERROR) {

				if (MetaDataType::insert($PROCESSED)) {

					$url = ENTRADA_URL . "/admin/settings/manage/metadata?org=".$ORGANISATION_ID;

					add_success(str_ireplace("%type%", html_encode(ucwords($PROCESSED["label"])),$translate->_("metadata_notice_added")).str_ireplace(array("%time%","%url%"), array(5,$url), $translate->_("metadata_redirect")));

								$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
				} else {
					add_error(metadata_error_edit);
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :

		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}

			if ($NOTICE) {
				echo display_notice();
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default:
			if ($ERROR) {
				echo display_error();
			}

			$HEAD[]	= "	<script type=\"text/javascript\">
						var organisation_id = ".$ORGANISATION_ID.";
						function selectObjective(parent_id, objective_id) {
							new Ajax.Updater('selectObjectiveField', '".ENTRADA_URL."/api/objectives-list.api.php', {parameters: {'pid': parent_id, 'organisation_id': ".$ORGANISATION_ID."}});
							return;
						}
						function selectOrder(parent_id) {
							new Ajax.Updater('selectOrderField', '".ENTRADA_URL."/api/objectives-list.api.php', {parameters: {'type': 'order', 'pid': parent_id, 'organisation_id': ".$ORGANISATION_ID."}});
							return;
						}
						</script>";
			$HEAD[] = "<script type=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/typelist-select.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/typelist-text.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$ONLOAD[] = "selectOrder(".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";

			?>
			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/metadata?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Page">
			<colgroup>
				<col style="width: 30%" />
				<col style="width: 70%" />
			</colgroup>
			<thead>
				<tr>
					<td colspan="2"><h1>Add Meta Data</h1></td>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2" style="padding-top: 15px; text-align: right">
						<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/metadata?org=<?php echo $ORGANISATION_ID;?>'" />
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td><label for="metadata_title" class="form-required">Meta Data Name:</label></td>
					<td><input type="text" id="metadata_title" name="metadata_title" value="<?php echo ((isset($PROCESSED["label"])) ? html_encode($PROCESSED["label"]) : ""); ?>" maxlength="60" style="width: 300px" /></td>
				</tr>
				<tr>
					<td><label for="metadata_desc" class="form-optional">Meta Data Description:</label></td>
					<td><input type="text" id="metadata_desc" name="metadata_desc" value="<?php echo ((isset($PROCESSED["description"])) ? html_encode($PROCESSED["description"]) : ""); ?>" maxlength="60" style="width: 300px" /></td>
				</tr>
				<tr>
					<td><label for="associated_parent" class="form-required"><?php echo $translate->translate("Parent"); ?></label></td>
					<td><select id="associated_parent" name="associated_parent">
							<option value="-1">-- No Parent --</option>
					<?php
					$metadata_types = MetaDataTypes::getSelectionOption();
					foreach ($metadata_types as $type) {
						echo "<option value=\"".$type["meta_type_id"]."\">".$type["label"]."</option>";
					}
					?>
					</select>
					</td>
				</tr>
				<tr>
					<td><label for="metadata_restrict" class="form-required"><?php echo $translate->translate("Restricted to Public"); ?></label></td>
					<td><select id="metadata_restrict" name="metadata_restrict">
							<option value="0">-- Public Viewable --</option>
							<option value="1">-- Restricted --</option>
							<option value="-1" disabled="disabled"><?php echo $translate->_("metadata_select_group_parent"); ?></option>
					</select>
					</td>
				</tr>
				<tr>
					<td><label for="associated_group" class="form-required"><?php echo $translate->translate("Group"); ?></label></td>
					<td><select id="associated_group" name="associated_group">
							<option value="-1"><?php echo $translate->_("metadata_select_group"); ?></option>
							<option value="0" disabled="disabled"><?php echo $translate->_("metadata_select_group_parent"); ?></option>
					<?php
					$ONLOAD[] = "grouplist = new SelectTypeList('group','associated_group');";
					foreach (array_keys($SYSTEM_GROUPS) as $group) {
						echo build_option($group, ucwords($group));
					}
					?>
					</select>
					<div id="group_notice" class="content-small" ><?php echo $translate->_("metadata_select_group_notice"); ?></div>
					<ol id="group_container" class="sortableList" style="display: none;">
						<?php
						if (isset($PROCESSED["groups"]) && is_array($PROCESSED["groups"]) && !empty($PROCESSED["groups"])) {
							foreach($PROCESSED["groups"] as $group) {
								echo "<li id=\"type_group_".$group."\" class=\"\">".ucwords($group)."
									<a href=\"#\" onclick=\"$(this).up().remove(); cleanupList(); return false;\" class=\"remove\">
										<img src=\"".ENTRADA_URL."/images/action-delete.gif\">
									</a>
								</li>";
							}
						}
						?>
					</ol>
					<input id="group_order" name="group_order" style="display: none;"/>

					</td>
				</tr>
				<script type="text/javascript">
					$('associated_parent').observe('change',function(parent){
						var value = $F(parent.target);
						if(value != -1){
							$('associated_group').selectedIndex = 1;
							$('associated_group').disabled=true;
							$('group_container').innerHTML = '';
							$('group_order').innerHTML = '';
							$('metadata_restrict').selectedIndex = 2;
							$('metadata_restrict').disabled=true;
						}
						else{
							$('associated_group').selectedIndex = 0;
							$('associated_group').disabled=false;
							$('metadata_restrict').selectedIndex = 0;
							$('metadata_restrict').disabled=false;
						}


					});

				</script>
			</tbody>
			</table>
			</form>
			<?php
		break;
	}

}
