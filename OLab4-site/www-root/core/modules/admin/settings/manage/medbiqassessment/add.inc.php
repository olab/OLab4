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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MEDBIQASSESSMENT"))) {
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
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/medbiqassessment?".replace_query(array("section" => "add"))."&amp;org=".$ORGANISATION_ID, "title" => "Add Method");
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "assessment_method" / Assessment Method
			 */
			if (isset($_POST["assessment_method"]) && ($assessment_method = clean_input($_POST["assessment_method"], array("htmlbrackets", "trim")))) {
				$PROCESSED["assessment_method"] = $assessment_method;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Medbiquitous Assessment Method</strong> is a required field.";
			}

			/**
			 * Non-required field "assessment_method_description" / Description
			 */
			if (isset($_POST["assessment_method_description"]) && ($assessment_method_description = clean_input($_POST["assessment_method_description"], array("htmlbrackets", "trim")))) {
				$PROCESSED["assessment_method_description"] = $assessment_method_description;
			} else {
				$PROCESSED["assessment_method_description"] = "";
			}
			
			/**
			 * Non-required field "fk_assessments_meta_id" / Mapped Assessment Types
			 */
			if (isset($_POST["fk_assessments_meta_id"]) && is_array($_POST["fk_assessments_meta_id"])) {
				$SEMI_PROCESSED["fk_assessments_meta_id"] = $_POST["fk_assessments_meta_id"];
			}
			
			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute("medbiq_assessment_methods", $PROCESSED, "INSERT")) {
					if(isset($SEMI_PROCESSED)) {
						// Insert keys into mapped table
						$MAPPED_PROCESSED = array();
						$MAPPED_PROCESSED["fk_assessment_method_id"] = $db->Insert_Id();
						$MAPPED_PROCESSED["updated_date"] = time();
						$MAPPED_PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						
						foreach($SEMI_PROCESSED["fk_assessments_meta_id"] as $fk_assessments_meta_id) {
							$MAPPED_PROCESSED["fk_assessments_meta_id"] = $fk_assessments_meta_id;
							if(!$db->AutoExecute("map_assessments_meta", $MAPPED_PROCESSED, "INSERT")) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting this assessment method into the system. The system administrator was informed of this error; please try again later.";
		
								application_log("error", "There was an error inserting an assessment method. Database said: ".$db->ErrorMsg());
							}
						}
					}
					
					if (!$ERROR) {
						$url = ENTRADA_URL . "/admin/settings/manage/medbiqassessment?org=".$ORGANISATION_ID;
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["assessment_method"])."</strong> to the system.<br /><br />You will now be redirected to the Medbiquitous Assessment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
						
						application_log("success", "New Medbiquitous Assessment Method [".$PROCESSED["assessment_method"]."] added to the system.");
					}
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this assessment method into the system. The system administrator was informed of this error; please try again later.";

				application_log("error", "There was an error inserting an assessment method. Database said: ".$db->ErrorMsg());
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
            <h1>Add Medbiquitous Assessment Method</h1>

			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/medbiqassessment"."?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post" class="form-horizontal">
                <div class="control-group">
                    <label for="assessment_method" class="form-required control-label">Assessment Method</label>
                    <div class="controls">
                        <input type="text" id="assessment_method" name="assessment_method" value="<?php echo ((isset($PROCESSED["assessment_method"])) ? html_encode($PROCESSED["assessment_method"]) : ""); ?>" class="span11" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="assessment_method_description" class="form-nrequired control-label">Description</label>
                    <div class="controls">
                        <textarea id="assessment_method_description" name="assessment_method_description" class="span11 expandable"><?php echo ((isset($PROCESSED["assessment_method_description"])) ? html_encode($PROCESSED["assessment_method_description"]) : ""); ?></textarea>
                    </div>
                </div>
                <div class="control-group">
                    <label for="assessment_method" class="form-nrequired control-label">Mapped Assessment Types</label>
                    <div class="controls">
                    <?php
                    $title_list = array();

                    $query = "	SELECT * FROM `assessments_lu_meta` 
                    WHERE `organisation_id` = ".$db->qstr($ORGANISATION_ID)."
                    AND `active` = '1' 
                    ORDER BY `title` ASC";

                    if ($results = $db->GetAll($query)) {
                        foreach($results as $result) {
                            $title_list[] = array("id"=>$result['id'], "title" => $result["title"]);
                        }
                    }
                    if (isset($title_list) && is_array($title_list) && !empty($title_list)) {
                        foreach($title_list as $title) {
                            if(isset($SEMI_PROCESSED["fk_assessments_meta_id"])) {
                                if(in_array($title["id"], $SEMI_PROCESSED["fk_assessments_meta_id"])) {
                                    $checked = "CHECKED";
                                } else {
                                    $checked = "";
                                }
                            } else {
                                $checked = "";
                            }
                            echo "<div class=\"checkbox\">";
                            echo "<input type=\"checkbox\" id=\"assessment_type_". $title["id"] ."\" name=\"fk_assessments_meta_id[]\" value=\"".$title["id"]."\" ".$checked.">";
                            echo "<label for=\"assessment_type_". $title["id"] ."\">";
                            echo $title["title"];
                            echo "</label>";
                            echo "</div>";
                        }
                    }
					?>
                    </div>
                </div>
                <div class="control-group">
                    <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/medbiqassessment?org=<?php echo $ORGANISATION_ID;?>'" />
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />                           
                </div>
			</form>
			<?php
		break;
	}
}
