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
 * This file is used to add objectives in the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_OBJECTIVES")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "create", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/objectives?".replace_query(array("section" => "add"))."&amp;id=".$ORGANISATION_ID, "title" => "Add Curriculum Tag");
	
	if (isset($_GET["parent_id"]) && ($id = clean_input($_GET["parent_id"], array("notags", "trim")))) {
		$PARENT_ID = $id;
	}
	
	if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
		$MODE = "ajax";
	}

	if ($MODE == "ajax" && isset($PARENT_ID) && $PARENT_ID != 0) {
		ob_clear_open_buffers();
		
		switch ($STEP) {
			case 2 :
				/**
				 * Non-required field "objective_parent" / Tag Parent
				 */
				if (isset($_POST["objective_id"]) && ($objective_parent = clean_input($_POST["objective_id"], array("int")))) {
					$PROCESSED["objective_parent"] = $objective_parent;

					$_SESSION[APPLICATION_IDENTIFIER]["objectives"]["objective_parent"] = $objective_parent;
				} else {
					$PROCESSED["objective_parent"] = 0;
				}

				/**
				 * Non-required field "objective_code" / Tag Code
				 */
				if (isset($_POST["objective_code"]) && ($objective_code = clean_input($_POST["objective_code"], array("notags", "trim")))) {
					$PROCESSED["objective_code"] = $objective_code;
				} else {
					$PROCESSED["objective_code"] = "";
				}

				/**
				 * Required field "objective_name" / Tag Name
				 */
				if (isset($_POST["objective_name"]) && ($objective_name = clean_input($_POST["objective_name"], array("notags", "trim")))) {
					$PROCESSED["objective_name"] = $objective_name;
				} else {
					add_error("The <strong>Tag Name</strong> is a required field.");
				}

				/**
				 * Non-required field "objective_description" / Tag Description
				 */
				if (isset($_POST["objective_description"]) && ($objective_description = clean_input($_POST["objective_description"], array("notags", "trim")))) {
					$PROCESSED["objective_description"] = $objective_description;
				} else {
					$PROCESSED["objective_description"] = "";
				}
				
				/**
				 * Non-required field "objective_loggable" / Tag loggable in Experience Logbook.
				 */
				if (isset($_POST["objective_loggable"]) && $_POST["objective_loggable"]) {
					$PROCESSED["objective_loggable"] = 1;
				} else {
					$PROCESSED["objective_loggable"] = 0;
				}

				/**
				 * Required field "objective_order" / Display Order
				 */
				if (isset($_POST["objective_order"]) && ($objective_order = clean_input($_POST["objective_order"], array("int"))) && $objective_order > 0) {
					$PROCESSED["objective_order"] = clean_input($_POST["objective_order"], array("int")) - 1;
				} else {
					$PROCESSED["objective_order"] = 0;
				}

				if (!has_error()) {
					$query = "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
								LEFT JOIN `objective_organisation` AS b
								ON a.`objective_id` = b.`objective_id`
								WHERE a.`objective_parent` = ".$db->qstr($PROCESSED["objective_parent"])."
								AND a.`objective_order` >= ".$db->qstr($PROCESSED["objective_order"])."
								AND a.`objective_active` = '1'
								AND (b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR b.`organisation_id` IS NULL)
								ORDER BY a.`objective_order` ASC";
					$objectives = $db->GetAll($query);
					if ($objectives) {
						$count = $PROCESSED["objective_order"];
						foreach ($objectives as $objective) {
							$count++;
							if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = ".$db->qstr($objective["objective_id"]))) {
								add_error("There was a problem adding this objective to the system. The system administrator was informed of this error; please try again later.");

								application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
							}
						}
					}
				}

				if (!has_error()) {
					$PROCESSED["updated_date"] = time();
					$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

					if ($db->AutoExecute("global_lu_objectives", $PROCESSED, "INSERT")) {
						if ($OBJECTIVE_ID = $db->Insert_Id()) {
							$PROCESSED["objective_id"] = $OBJECTIVE_ID;

							$objective = array(
									"objective_id" => $OBJECTIVE_ID,
									"organisation_id" => $ORGANISATION_ID,
							);

							$db->AutoExecute("objective_organisation", $objective, "INSERT");
						}
					} else {
						$ERROR++;
						echo json_encode(array("status" => "error", "msg" => "There was a problem inserting this objective into the system. The system administrator was informed of this error; please try again later."));
					}
				} else {
					$error_ajax = "";
					if (is_array($ERRORSTR) && !empty($ERRORSTR)) {
						foreach ($ERRORSTR as $error) {
							$error_ajax .= $error."<br/>";
						}
					}
					echo json_encode(array("status" => "error", "msg" => $error_ajax));
				}
				
				if (!has_error()) {
					echo json_encode(array("status" => "success", "updates" => $PROCESSED));
				}
			break;
			case 1 :
			default :
				$time = time();
				?>
                <script type="text/javascript">
                    jQuery(function(){
                        selectObjective('#m_selectObjectiveField_<?php echo $time; ?>',<?php echo $PARENT_ID; ?>);
                        selectOrder('#m_selectOrderField_<?php echo $time; ?>', <?php echo $PARENT_ID; ?>, <?php echo $PARENT_ID; ?>);
                    });
                </script>
				<div class="row-fluid">
					<h2>Tag Details</h2>

					<form id="add-curriculum-tag-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives"."?".replace_query(array("action" => "add", "step" => 2, "mode" => "ajax")); ?>" method="post" class="form-horizontal">
						<div class="control-group">
							<label for="objective_id" class="form-required control-label">Tag Parent</label>
							<div class="controls">
								<div id="m_selectObjectiveField_<?php echo $time; ?>"></div>
							</div>
						</div>

						<div class="control-group">
							<label for="objective_code" class="form-nrequired control-label">Tag Code</label>
							<div class="controls">
								<input type="text" id="objective_code" name="objective_code" value="<?php echo ((isset($PROCESSED["objective_code"])) ? html_encode($PROCESSED["objective_code"]) : ""); ?>" class="span5">
							</div>
						</div>

						<div class="control-group">
							<label for="objective_name" class="form-required control-label">Tag Name</label>
							<div class="controls">
								<input type="text" id="objective_name" name="objective_name" value="<?php echo ((isset($PROCESSED["objective_name"])) ? html_encode($PROCESSED["objective_name"]) : ""); ?>" class="span11">
							</div>
						</div>

						<div class="control-group">
							<label for="objective_description" class="form-nrequired control-label">Tag Description</label>
							<div class="controls">
								<textarea id="objective_description" name="objective_description" class="span11"><?php echo ((isset($PROCESSED["objective_description"])) ? html_encode($PROCESSED["objective_description"]) : ""); ?></textarea>
							</div>
						</div>

						<div class="control-group">
							<div class="controls">
								<label class="checkbox"><input type="checkbox" id="objective_loggable" name="objective_loggable" value="1"<?php echo (isset($PROCESSED["objective_loggable"]) && $PROCESSED["objective_loggable"] ? " checked=\"checked\"" : ""); ?> /> This curriculum tag should be loggable in Experience Logbook.</label>
							</div>
						</div>

						<div class="control-group">
							<label for="objective_order" class="form-required control-label">Display Order</label>
							<div class="controls">
								<div id="m_selectOrderField_<?php echo $time; ?>"></div>
							</div>
						</div>

						<div class="control-group">
							<div class="alert alert-block alert-error hide" id="objective_error" style="margin-top:10px!important;margin-bottom:0px!important;"></div>
						</div>

					</form>
				</div>
				<?php
			break;
		}
		exit;
	}
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Non-required field "objective_code" / Tag Set Code
			 */
			if (isset($_POST["objective_code"]) && ($objective_code = clean_input($_POST["objective_code"], array("notags", "trim")))) {
				$PROCESSED["objective_code"] = $objective_code;
			} else {
				$PROCESSED["objective_code"] = "";
			}

			/**
			 * Required field "objective_name" / Tag Set Name
			 */
			if (isset($_POST["objective_name"]) && ($objective_name = clean_input($_POST["objective_name"], array("notags", "trim")))) {
				$PROCESSED["objective_name"] = $objective_name;
			} else {
				add_error("The <strong>Tag Set Name</strong> is a required field.");
			}

			/**
			 * Required field "shortname" / Tag Set Shortname
			 */
			if (isset($_POST["objective_shortname"]) && $objective_shortname = clean_input($_POST["objective_shortname"], array("notags", "trim"))) {
				$PROCESSED["objective_shortname"] = $objective_shortname;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Tag Set Shortname</strong> is a required field.";
			}

			/**
			 * Non-required field "standard" / Tag Set Standard
			 */
			if (isset($_POST["standard"]) && ($tmp_input = clean_input($_POST["standard"], array("trim", "int")))) {
				$PROCESSED["standard"] = $tmp_input;
			} else {
				$PROCESSED["standard"] = 0;
			}

			/**
			 * Non-required field "objective_description" / Tag Set Description
			 */
			if (isset($_POST["objective_description"]) && ($objective_description = clean_input($_POST["objective_description"], array("notags", "trim")))) {
				$PROCESSED["objective_description"] = $objective_description;
			} else {
				$PROCESSED["objective_description"] = "";
			}

			/**
			 * Required field "objective_order" / Display Order
			 */
			if (isset($_POST["objective_order"]) && ($objective_order = clean_input($_POST["objective_order"], array("int")))) {
				$PROCESSED["objective_order"] = clean_input($_POST["objective_order"], array("int")) - 1;
			} else {
				$PROCESSED["objective_order"] = 0;
			}

			if (!has_error()) {
				$query = "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
							LEFT JOIN `objective_organisation` AS b
							ON a.`objective_id` = b.`objective_id`
							WHERE a.`objective_parent` = ".$db->qstr($PROCESSED["objective_parent"])."
							AND a.`objective_order` >= ".$db->qstr($PROCESSED["objective_order"])."
							AND a.`objective_active` = '1'
							AND (b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR b.`organisation_id` IS NULL)
							ORDER BY a.`objective_order` ASC";
				$objectives = $db->GetAll($query);
				if ($objectives) {
					$count = $PROCESSED["objective_order"];
					foreach ($objectives as $objective) {
						$count++;
						if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = ".$db->qstr($objective["objective_id"]))) {
							add_error("There was a problem adding this objective to the system. The system administrator was informed of this error; please try again later.");

							application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
						}
					}
				}
			}
			
			if (!has_error()) {
				$objective_set = array(
					"title" 		=> $PROCESSED["objective_name"],
					"description" 	=> $PROCESSED["objective_description"],
					"shortname" 	=> $PROCESSED["objective_shortname"],
					"start_date" 	=> null,
					"end_date" 		=> null,
					"standard" 		=> $PROCESSED["standard"],
					"created_date" 	=> time(),
					"created_by" 	=> $ENTRADA_USER->getActiveId()
				);

				$objective_set_model = new Models_ObjectiveSet($objective_set);
				if ($objective_set_model->insert() && $objective_set_id = $db->Insert_Id()) {
					$PROCESSED["objective_parent"] = 0;
					$PROCESSED["objective_shortname"] = $PROCESSED["objective_shortname"];
					$PROCESSED["objective_set_id"] = $objective_set_id;
					$PROCESSED["updated_date"] = time();
					$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

					if ($db->AutoExecute("global_lu_objectives", $PROCESSED, "INSERT")) {
						if ($OBJECTIVE_ID = $db->Insert_Id()) {

							$objective = array(
								"objective_id" => $OBJECTIVE_ID,
								"organisation_id" => $ORGANISATION_ID,
								"audience_type" => "COURSE",
								"audience_value" => "all",
								"updated_date" => time(),
								"updated_by" => $ENTRADA_USER->getID()
							);

							if ($db->AutoExecute("objective_audience", $objective, "INSERT") && $db->AutoExecute("objective_organisation", $objective, "INSERT")) {
								$url = ENTRADA_URL . "/admin/settings/manage/objectives?org=".$ORGANISATION_ID;

								add_success("You have successfully added <strong>".html_encode($PROCESSED["objective_name"])."</strong> to the system.<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

								$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

								application_log("success", "New Objective [".$OBJECTIVE_ID."] added to the system.");
							} else{
								add_error("There was a problem adding this Curriculum Tag to the system. The system administrator was informed of this error; please try again later.");

								application_log("error", "There was an error associating an objective with an organisation. Database said: ".$db->ErrorMsg());
							}
						}
					} else {
						add_error("There was a problem inserting this objective into the system. The system administrator was informed of this error; please try again later.");

						application_log("error", "There was an error inserting an objective. Database said: ".$db->ErrorMsg());
					}
				} else {
					add_error("There was a problem inserting this objective set into the system. The system administrator was informed of this error; please try again later.");

					application_log("error", "There was an error inserting an objective set. Database said: ".$db->ErrorMsg());
				}
			}

			if (has_error()) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			$PROCESSED["objective_parent"] = 0;
		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if (has_success()) {
				echo display_success();
			}

			if (has_notice()) {
				echo display_notice();
			}

			if (has_error()) {
				echo display_error();
			}
		break;
		case 1 :
		default:
			if (has_error()) {
				echo display_error();
			}
            $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$ONLOAD[] = "selectObjective('#selectObjectiveField', ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
			$ONLOAD[] = "selectOrder('#selectOrderField', ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
			?>
            <script type="text/javascript">
                var SITE_URL = "<?php echo ENTRADA_URL;?>";
                var org_id = "<?php echo $ORGANISATION_ID; ?>";
            </script>
			<h1>Add Curriculum Tag Set</h1>
			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post" class="form-horizontal">
				<div class="control-group">
					<label for="objective_code" class="form-nrequired control-label">Tag Set Code</label>
					<div class="controls">
						<input type="text" id="objective_code" name="objective_code" value="<?php echo ((isset($PROCESSED["objective_code"])) ? html_encode($PROCESSED["objective_code"]) : ""); ?>" class="span5">
					</div>
				</div>

				<div class="control-group">
					<label for="objective_name" class="form-required control-label">Tag Set Name</label>
					<div class="controls">
						<input type="text" id="objective_name" name="objective_name" value="<?php echo ((isset($PROCESSED["objective_name"])) ? html_encode($PROCESSED["objective_name"]) : ""); ?>" class="span11">
					</div>
				</div>

				<div class="control-group">
					<label for="objective_shortname" class="form-required control-label">Tag Set Shortname</label>
					<div class="controls">
						<input type="text" id="objective_shortname" name="objective_shortname" value="<?php echo ((isset($PROCESSED["objective_shortname"])) ? html_encode($PROCESSED["objective_shortname"]) : ""); ?>" class="span11">
					</div>
				</div>

				<div class="control-group">
					<div class="controls">
						<label class="checkbox">
							<input type="checkbox" id="standard" value="1" name="standard" <?php echo ((isset($PROCESSED["standard"]) && $PROCESSED["standard"] == 1) ? "checked=\"checked\"" : ""); ?> />
							<?php echo $translate->_("This is a standardized Curriculum Tag Set."); ?>
						</label>
					</div>
				</div>

				<div class="control-group">
					<label for="objective_description" class="form-nrequired control-label">Tag Set Description</label>
					<div class="controls">
						<textarea id="objective_description" name="objective_description" class="span11 expandable"><?php echo ((isset($PROCESSED["objective_description"])) ? html_encode($PROCESSED["objective_description"]) : ""); ?></textarea>
					</div>
				</div>

				<div class="control-group">
					<label for="objective_order" class="form-required control-label">Display Order</label>
					<div class="controls">
						<div id="selectOrderField"></div>
					</div>
				</div>

				<div class="control-group">
					<a href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/objectives?org=<?php echo $ORGANISATION_ID;?>" class="btn"><?php echo $translate->_("global_button_cancel"); ?></a>
					<div class="pull-right">
						<input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
					</div>
				</div>
			</form>
			<?php
		break;
	}
}
