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
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_MEDBIQRESOURCES")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	echo "<script language=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/medbiqresources?".replace_query(array("section" => "add"))."&amp;org=".$ORGANISATION_ID, "title" => "Add Resource");
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "resource" / Resource
			 */
			if (isset($_POST["resource"]) && ($resource = clean_input($_POST["resource"], array("htmlbrackets", "trim")))) {
				$PROCESSED["resource"] = $resource;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Medbiquitous Resource</strong> is a required field.";
			}

			/**
			 * Non-required field "resource_description" / Description
			 */
			if (isset($_POST["resource_description"]) && ($resource_description = clean_input($_POST["resource_description"], array("htmlbrackets", "trim")))) {
				$PROCESSED["resource_description"] = $resource_description;
			} else {
				$PROCESSED["resource_description"] = "";
			}
			
			/**
			 * Non-required field "resource_id" / Mapped Resources
			 */
			if (isset($_POST["resource_id"]) && is_array($_POST["resource_id"])) {
				$SEMI_PROCESSED["resource_id"] = $_POST["resource_id"];
			}
			
			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute("medbiq_resources", $PROCESSED, "INSERT")) {
					if(isset($SEMI_PROCESSED)) {
						// Insert keys into mapped table
						$MAPPED_PROCESSED = array();
						$MAPPED_PROCESSED["fk_medbiq_resource_id"] = $db->Insert_Id();
						$MAPPED_PROCESSED["updated_date"] = time();
						$MAPPED_PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						
						foreach($SEMI_PROCESSED["resource_id"] as $resource_id) {
							$MAPPED_PROCESSED["fk_resource_id"] = $resource_id;
							if(!$db->AutoExecute("map_event_resources", $MAPPED_PROCESSED, "INSERT")) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting this resource into the system. The system administrator was informed of this error; please try again later.";
		
								application_log("error", "There was an error inserting a resource. Database said: ".$db->ErrorMsg());
							}
						}
					}
					
					if (!$ERROR) {
						$url = ENTRADA_URL . "/admin/settings/manage/medbiqresources?org=".$ORGANISATION_ID;
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["resource"])."</strong> to the system.<br /><br />You will now be redirected to the Medbiquitous Resources index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
						
						application_log("success", "New Medbiquitous Resource [".$PROCESSED["resource"]."] added to the system.");
					}
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this resource into the system. The system administrator was informed of this error; please try again later.";

				application_log("error", "There was an error inserting a resource. Database said: ".$db->ErrorMsg());
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
			?>
			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/medbiqresources"."?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Page">
			<colgroup>
				<col style="width: 30%" />
				<col style="width: 70%" />
			</colgroup>
			<thead>
				<tr>
					<td colspan="2"><h1>Add Medbiquitous Resource</h1></td>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2" style="padding-top: 15px; text-align: right">
						<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/medbiqresources?org=<?php echo $ORGANISATION_ID;?>'" />
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />                           
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td><label for="resource" class="form-required">Medbiquitous Resource:</label></td>
					<td><input type="text" id="resource" name="resource" value="<?php echo ((isset($PROCESSED["resource"])) ? html_encode($PROCESSED["resource"]) : ""); ?>" maxlength="60" style="width: 300px" /></td>
				</tr>
				<tr>
					<td style="vertical-align: top;"><label for="resource_description" class="form-nrequired">Description:</label></td>
					<td>
						<textarea id="resource_description" name="resource_description" style="width: 98%; height: 200px" rows="20" cols="70"><?php echo ((isset($PROCESSED["resource_description"])) ? html_encode($PROCESSED["resource_description"]) : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td><label for="resource" class="form-nrequired">Mapped Resources:</label></td>
					<?php
						$resource_list = array();
				
						$query = "	SELECT * FROM `events_lu_resources` 
						WHERE `organisation_id` = ".$db->qstr($ORGANISATION_ID)."
						AND `active` = '1' 
						ORDER BY `resource` ASC";

						if ($results = $db->GetAll($query)) {
							foreach($results as $result) {
								$resource_list[] = array("resource_id"=>$result['resource_id'], "resource" => $result["resource"]);
							}
						}
						if (isset($resource_list) && is_array($resource_list) && !empty($resource_list)) {
							echo "<td>";
							foreach($resource_list as $resource) {
								if(isset($SEMI_PROCESSED["resource_id"])) {
									if(in_array($resource["resource_id"], $SEMI_PROCESSED["resource_id"])) {
										$checked = "CHECKED";
									} else {
										$checked = "";
									}
								} else {
									$checked = "";
								}
								echo "<input type=\"checkbox\" name=\"resource_id[]\" value=\"".$resource["resource_id"]."\" ".$checked.">".$resource["resource"]."<br>";
							}
						}
					?>
					</td>
				</tr>
			</tbody>
			</table>
			</form>
			<?php
		break;
	}
}
