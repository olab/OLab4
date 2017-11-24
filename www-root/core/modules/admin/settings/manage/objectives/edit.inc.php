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
 * This file is used to edit objectives in the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_OBJECTIVES")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "update", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["id"]) && ($id = clean_input($_GET["id"], array("notags", "trim")))) {
		$OBJECTIVE_ID = $id;
	}
	
	if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
		$MODE = "ajax";
	}

	if ($OBJECTIVE_ID) {
		$query = "	SELECT a.*, GROUP_CONCAT(c.`audience_value`) AS `audience`, d.`standard` FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					LEFT JOIN `objective_audience` AS c
					ON a.`objective_id` = c.`objective_id`
					AND b.`organisation_id` = c.`organisation_id`
					LEFT JOIN `global_lu_objective_sets` AS d
					ON a.`objective_set_id` = d.`objective_set_id`
					WHERE a.`objective_id` = ".$db->qstr($OBJECTIVE_ID)."
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					AND a.`objective_active` = '1'
					AND d.`deleted_date` IS NULL";
		$objective_details	= $db->GetRow($query);
		if ($MODE == "ajax") {
			ob_clear_open_buffers();
			$time = time();
			
			$method = clean_input($_POST["method"], array("trim", "striptags"));

			switch ($method) {
				case "link-objective" :
					$PROCESSED["objective_id"] = $OBJECTIVE_ID;
						
					if ($_GET["target_objective_id"] && $tmp_input = clean_input($_GET["target_objective_id"], "int")) {
						$PROCESSED["target_objective_id"] = $tmp_input;
					} else {
						add_error("Invalid target objective ID provided.");
					}
					
					if (!has_error()) {
						if ($_POST["action"] == "link") {
							if ($db->AutoExecute("linked_objectives", $PROCESSED, "INSERT")) {
								$query = "SELECT `objective_id` AS `target_objective_id`, `objective_name`, `objective_description` FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($PROCESSED["target_objective_id"]);
								$result = $db->GetRow($query);
								if ($result) {
									$parent = Entrada_Curriculum_Explorer::fetch_objective_parents($PROCESSED["target_objective_id"]);
									$result["action"] = "link";
									$result["parent_objective"] = $parent["parent"]["objective_name"];

									echo json_encode(array("status" => "success", "data" => $result));
								}
							}
						} else {
							$query = "DELETE FROM `linked_objectives` WHERE `objective_id` = " . $db->qstr($PROCESSED["objective_id"]) . " AND `target_objective_id` = ".$db->qstr($PROCESSED["target_objective_id"]);
							if ($db->Execute($query)) {
								echo json_encode(array("status" => "success", "data" => array("action" => "unlink", "target_objective_id" => $PROCESSED["target_objective_id"])));
							}
						}
					}
				break;
				case "fetch-linked-objectives" :
					echo "<h1>".$objective_details["objective_name"]."</h1>";
					echo (!empty($objective_details["objective_description"]) ? "<p>".$objective_details["objective_description"]."</p>" : "");

					if (isset($_POST["objective_set_id"]) && $tmp_input = clean_input($_POST["objective_set_id"], "int")) {
						$PROCESSED["objective_set_id"] = $tmp_input;
					}
					
					$query = "	SELECT a.`objective_id`, a.`objective_description`, a.`objective_name`, b.`linked_objective_id`
								FROM `global_lu_objectives` AS a
								JOIN `linked_objectives` AS b
								ON b.`target_objective_id` = a.`objective_id`
								WHERE b.`objective_id` = ".$db->qstr($OBJECTIVE_ID)."
								AND b.`active` = '1'";
					$linked_objectives = $db->GetAll($query);
					
					echo "<h2>Currently Linked Curriculum Tags</h2>\n";

					echo "<ul id=\"currently-linked-objectives\">";
					if ($linked_objectives) {
						foreach ($linked_objectives as $objective) {
							$parent = Entrada_Curriculum_Explorer::fetch_objective_parents($objective["objective_id"]);
							echo "<li data-id=\"" . $objective["objective_id"] . "\"><strong>".$objective["objective_name"]."</strong><a href=\"#\" class=\"unlink\"><i class=\"icon-trash\"></i></a>".($parent["parent"]["objective_name"] ? "<br /><small class=\"content-small\">From ".$parent["parent"]["objective_name"]."</small>" : "")."".(!empty($objective["objective_description"]) ? "<br />".$objective["objective_description"] : "")."</li>";
						}
					} else {
						echo "<li class=\"no-objectives\">This curriculum tag is not currently linked to any other tags.</li>";
					}
					echo "</ul>";

					$query = "	SELECT a.* FROM `global_lu_objectives` a
								JOIN `objective_audience` b
								ON a.`objective_id` = b.`objective_id`
								AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
								WHERE a.`objective_parent` = '0'
								AND a.`objective_active` = '1'
								AND a.`objective_id` != ".$db->qstr($PROCESSED["objective_set_id"])."
								GROUP BY a.`objective_id`";
					$objectives = $db->GetAll($query);
					if ($objectives) {
						$objective_name = $translate->_("events_filter_controls");
						$hierarchical_name = $objective_name["co"]["global_lu_objectives_name"];
						?>
						<h2>Tags Available to Link</h2>
						<ul id="linked-objective-list" class="objective-list">
							<?php
							foreach ($objectives as $objective) {
								?>
								<li>
									<a href="#" class="objective" data-id="<?php echo $objective["objective_id"];?>">
									<?php
									$title = ($objective["objective_code"] ? $objective["objective_code"] . ': ' . $objective["objective_name"] : $objective["objective_name"]);
									echo $title;
									?>
									</a><i class="icon-chevron-down"></i>
									<div class="children"></div>
								</li>
								<?php
							}
							?>
						</ul>
						<?php
					}
				break;
				default:
					if ($objective_details["objective_parent"] != 0) {
						switch ($STEP) {
							case 2 :
								/**
								 * Non-required field "objective_parent" / Objective Parent
								 */
								if (isset($_POST["objective_id"]) && ($objective_parent = clean_input($_POST["objective_id"], array("int")))) {
									$PROCESSED["objective_parent"] = $objective_parent;
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
								if (isset($_POST["objective_order"]) && ($objective_order = clean_input($_POST["objective_order"], array("int"))) && $objective_order != "-1") {
									$PROCESSED["objective_order"] = clean_input($_POST["objective_order"], array("int")) - 1;
								} else if($objective_order == "-1") {
									$PROCESSED["objective_order"] = $objective_details["objective_order"];
								} else {
									$PROCESSED["objective_order"] = 0;
								}

								if (!has_error()) {
									if ($objective_details["objective_order"] != $PROCESSED["objective_order"]) {
										$query = "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
													LEFT JOIN `objective_organisation` AS b
													ON a.`objective_id` = b.`objective_id`
													WHERE a.`objective_parent` = ".$db->qstr($PROCESSED["objective_parent"])."
													AND (b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR b.`organisation_id` IS NULL)
													AND a.`objective_id` != ".$db->qstr($OBJECTIVE_ID)./*"
													AND a.`objective_order` >= ".$db->qstr($PROCESSED["objective_order"]).*/"
													AND a.`objective_active` = '1'
													ORDER BY a.`objective_order` ASC";
										$objectives = $db->GetAll($query);
										if ($objectives) {
											$count = 0;
											foreach ($objectives as $objective) {
												if($count === $PROCESSED["objective_order"]) {
													$count++;
												}
												if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = ".$db->qstr($objective["objective_id"]))) {
													add_error("There was a problem updating this objective in the system. The system administrator was informed of this error; please try again later.");

													application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
												}
												$count++;
											}
										}
									}
								}

								if (!has_error()) {
									$PROCESSED["updated_date"] = time();
									$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

									if (!$db->AutoExecute("global_lu_objectives", $PROCESSED, "UPDATE", "`objective_id` = ".$db->qstr($OBJECTIVE_ID))) {
										echo json_encode(array("status" => "error", "msg" => "There was a problem updating this objective in the system. The system administrator was informed of this error; please try again later."));

										application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
									} else {
										$PROCESSED["objective_id"] = $OBJECTIVE_ID;

										echo json_encode(array("status" => "success", "updates" => $PROCESSED));
									}
								} else {
									echo json_encode(array("status" => "error", "msg" => "Name is required"));
								}
							break;
							case 1 :
							default :
								?>
                                <script type="text/javascript">
                                jQuery(function(){
                                    selectObjective('#m_selectObjectiveField_<?php echo $time; ?>', <?php echo (isset($objective_details["objective_parent"]) && $objective_details["objective_parent"] ? $objective_details["objective_parent"] : "0"); ?>, <?php echo $OBJECTIVE_ID; ?>);
                                    selectOrder('#m_selectOrderField_<?php echo $time; ?>', <?php echo $OBJECTIVE_ID; ?>, <?php echo (isset($objective_details["objective_parent"]) && $objective_details["objective_parent"] ? $objective_details["objective_parent"] : "0"); ?>);
                                });
                                </script>

								<div class="row-fluid">
									<h2>Curriculum Tag<?php echo ($objective_details["objective_parent"] == 0) ? " Set" : ""; ?> Details</h2>

									<form id="objective-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives"."?".replace_query(array("action" => "edit", "step" => 2, "mode" => "ajax")); ?>" method="post" class="form-horizontal">
										<div class="control-group">
											<label for="objective_id" class="form-required control-label">Tag Parent</label>
											<div class="controls">
												<div id="m_selectObjectiveField_<?php echo $time; ?>"></div>
											</div>
										</div>

										<div class="control-group">
											<label for="objective_code" class="form-nrequired control-label">Tag Code</label>
											<div class="controls">
												<input type="text" id="objective_code" name="objective_code" value="<?php echo ((isset($objective_details["objective_code"])) ? html_encode($objective_details["objective_code"]) : ""); ?>" class="span5" />
											</div>
										</div>

										<div class="control-group">
											<label for="objective_name" class="form-required control-label">Tag Name</label>
											<div class="controls">
												<input type="text" id="objective_name" name="objective_name" value="<?php echo ((isset($objective_details["objective_name"])) ? html_encode($objective_details["objective_name"]) : ""); ?>" class="span11" />
											</div>
										</div>

										<div class="control-group">
											<label for="objective_description" class="form-nrequired control-label">Tag Description</label>
											<div class="controls">
												<textarea id="objective_description" name="objective_description" class="span11 expandable"><?php echo ((isset($objective_details["objective_description"])) ? html_encode($objective_details["objective_description"]) : ""); ?></textarea>
											</div>
										</div>

										<?php
										if ((int) $objective_details["objective_parent"]) {
											?>
											<div class="control-group">
												<div class="controls">
													<label class="form-nrequired checkbox"><input type="checkbox" id="objective_loggable" name="objective_loggable" value="1"<?php echo(isset($objective_details["objective_loggable"]) && $objective_details["objective_loggable"] ? " checked=\"checked\"" : ""); ?> />This curriculum tag should be loggable in Experience Logbook.</label>
												</div>
											</div>
											<?php
										}
										?>

										<div class="control-group">
											<label for="objective_id" class="form-required control-label">Display Order</label>
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
					}
				break;
			}
			
			exit;
		} else {
			/**
			 * Fetch all courses into an array that will be used.
			 */
			$query = "SELECT * FROM `courses` WHERE `organisation_id` = ".$ORGANISATION_ID." ORDER BY `course_code` ASC";
			$courses = $db->GetAll($query);
			if ($courses) {
				foreach ($courses as $course) {
					$course_list[$course["course_id"]] = array("code" => $course["course_code"], "name" => $course["course_name"]);
				}
			}

			if ($objective_details) {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/objectives?".replace_query(array("section" => "edit")), "title" => "Curriculum Tag Set");

				// Error Checking
				switch ($STEP) {
					case 2:
						/**
						 * Non-required field "objective_parent" / Tag Set Parent
						 */
						if (isset($_POST["objective_id"]) && ($objective_parent = clean_input($_POST["objective_id"], array("int")))) {
							$PROCESSED["objective_parent"] = $objective_parent;
						} else {
							$PROCESSED["objective_parent"] = 0;
						}

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
							$ERROR++;
							$ERRORSTR[] = "The <strong>" . $translate->_("Objective") . " Name</strong> is a required field.";
						}

						/**
						 * Required field "shortname" / Tag Set Shortname
						 */
						if (isset($_POST["objective_shortname"]) && ($objective_shortname = clean_input($_POST["objective_shortname"], array("notags", "trim")))) {
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
						if (isset($_POST["objective_order"]) && ($objective_order = clean_input($_POST["objective_order"], array("int"))) && $objective_order != "-1") {
							$PROCESSED["objective_order"] = clean_input($_POST["objective_order"], array("int")) - 1;
						} else if($objective_order == "-1") {
							$PROCESSED["objective_order"] = $objective_details["objective_order"];
						} else {
							$PROCESSED["objective_order"] = 0;
						}

						if ($objective_details["objective_parent"] == 0 || $PROCESSED["objective_parent"] == 0) {
							/**
							 * Non-required field "objective_audience"
							 */
							if (isset($_POST["objective_audience"]) && $tmp_input = clean_input($_POST["objective_audience"], array("notags", "trim")) && ($tmp_input == "all" || $tmp_input == "none" || "selected")) {
								$PROCESSED["objective_audience"] = clean_input($_POST["objective_audience"], array("notags", "trim"));
							} else {
								$PROCESSED["objective_audience"] = "all";
							}

							/**
							 * Non-required field "course_ids"
							 */
							if (isset($_POST["course_ids"]) && isset($PROCESSED["objective_audience"]) == "selected") {
								foreach ($_POST["course_ids"] as $course_id) {
									if (array_key_exists($course_id, $course_list)) {
										$PROCESSED["course_ids"][] = clean_input($course_id, "numeric") ;
									}
								}
								if (empty($PROCESSED["course_ids"])) {
									$PROCESSED["objective_audience"] = "none";
								}
							}
						}

						if (!has_error()) {
							if ($objective_details["objective_order"] != $PROCESSED["objective_order"]) {
								$query = "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
											LEFT JOIN `objective_organisation` AS b
											ON a.`objective_id` = b.`objective_id`
											WHERE a.`objective_parent` = ".$db->qstr($PROCESSED["objective_parent"])."
											AND (b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR b.`organisation_id` IS NULL)
											AND a.`objective_id` != ".$db->qstr($OBJECTIVE_ID)./*"
											AND a.`objective_order` >= ".$db->qstr($PROCESSED["objective_order"]).*/"
											AND a.`objective_active` = '1'
											ORDER BY a.`objective_order` ASC";
								$objectives = $db->GetAll($query);
								if ($objectives) {
									$count = 0;
									foreach ($objectives as $objective) {
										if ($count === $PROCESSED["objective_order"]) {
											$count++;
										}
										if (!$db->AutoExecute("global_lu_objectives", array("objective_order" => $count), "UPDATE", "`objective_id` = ".$db->qstr($objective["objective_id"]))) {
											add_error("There was a problem updating this objective in the system. The system administrator was informed of this error; please try again later.");

											application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
										}
										$count++;
									}
								}
							}
						}

						if (!has_error()) {
							$PROCESSED["updated_date"] = time();
							$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

							$method = "update";
							$objective_set_array = array();
							$objective_set_model = new Models_ObjectiveSet();
							$objective_set = $objective_set_model->fetchRowByID($objective_details["objective_set_id"]);

							if (!$objective_set) {
								$objective_set = new Models_ObjectiveSet();
								$method = "insert";
								$objective_set_array["created_date"] = time();
								$objective_set_array["created_by"] = $ENTRADA_USER->getActiveId();
							} else {
								$objective_set_array["created_date"] = $objective_set->getCreatedDate();
								$objective_set_array["created_by"] = $objective_set->getCreatedBy();
							}

							$objective_set_array["title"] = $PROCESSED["objective_name"];
							$objective_set_array["description"] = $PROCESSED["objective_description"];
							$objective_set_array["shortname"] = $PROCESSED["objective_shortname"];
							$objective_set_array["start_date"] = null;
							$objective_set_array["end_date"] = null;
							$objective_set_array["standard"] = $PROCESSED["standard"];
							$objective_set_array["updated_date"] = $PROCESSED["updated_date"];
							$objective_set_array["updated_by"] = $PROCESSED["updated_by"];

							if ($objective_set->fromArray($objective_set_array)->$method()) {
								if ($method == "insert") {
									$PROCESSED["objective_set_id"] = $db->Insert_Id();
								}

								if ($db->AutoExecute("global_lu_objectives", $PROCESSED, "UPDATE", "`objective_id` = " . $db->qstr($OBJECTIVE_ID))) {
									$query = "DELETE FROM `objective_audience` WHERE `objective_id` = " . $db->qstr($OBJECTIVE_ID);
									if ($db->Execute($query)) {
										if ($objective_details["objective_parent"] == 0 || $PROCESSED["objective_parent"] == 0) {
											if ($PROCESSED["objective_audience"] == "all" || $PROCESSED["objective_audience"] == "none") {
												$query = "	INSERT INTO `objective_audience` (`objective_id`, `organisation_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
													VALUES(" . $db->qstr($OBJECTIVE_ID) . ", " . $db->qstr($ORGANISATION_ID) . ", " . $db->qstr("course") . ", " . $db->qstr($PROCESSED["objective_audience"]) . ", " . $db->qstr(time()) . ", " . $db->qstr($ENTRADA_USER->getID()) . ")";
												if (!$db->Execute($query)) {
													add_error("An error occurred while updating objective audience.");
												}
											} else if ($PROCESSED["objective_audience"] == "selected" && is_array($PROCESSED["course_ids"]) && !empty($PROCESSED["course_ids"])) {
												foreach ($PROCESSED["course_ids"] as $course => $course_id) {
													$query = "	INSERT INTO `objective_audience` (`objective_id`, `organisation_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
														VALUES(" . $db->qstr($OBJECTIVE_ID) . ", " . $db->qstr($ORGANISATION_ID) . ", " . $db->qstr("course") . ", " . $db->qstr($course_id) . ", " . $db->qstr(time()) . ", " . $db->qstr($ENTRADA_USER->getID()) . ")";
													if (!$db->Execute($query)) {
														add_error("An error occurred while updating objective audience.");
													}
												}
											} else {
												$query = "	INSERT INTO `objective_audience` (`objective_id`, `organisation_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
													VALUES(" . $db->qstr($OBJECTIVE_ID) . ", " . $db->qstr($ORGANISATION_ID) . ", " . $db->qstr("course") . ", " . $db->qstr("none") . ", " . $db->qstr(time()) . ", " . $db->qstr($ENTRADA_USER->getID()) . ")";
												if (!$db->Execute($query)) {
													add_error("An error occurred while updating objective audience.");
												}
											}
										}
									}

									if (!has_error()) {
										$url = ENTRADA_URL . "/admin/settings/manage/objectives?org=" . $ORGANISATION_ID;

										add_success("You have successfully updated <strong>" . html_encode($PROCESSED["objective_name"]) . "</strong> in the system.<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.");

										$ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";

										application_log("success", "Objective [" . $OBJECTIVE_ID . "] updated in the system.");
									}
								} else {
									add_error("There was a problem updating this objective in the system. The system administrator was informed of this error; please try again later.");

									application_log("error", "There was an error updating an objective. Database said: " . $db->ErrorMsg());
								}
							} else {
								add_error("There was a problem updating this objective set in the system. The system administrator was informed of this error; please try again later. ". $db->ErrorMsg());

								application_log("error", "There was an error updating an objective set. Database said: " . $db->ErrorMsg());
							}
						}

						if (has_error()) {
							$STEP = 1;
						}
					break;
					case 1:
					default:
						$PROCESSED = $objective_details;
					break;
				}

				//Display Content
				switch ($STEP) {
					case 2:
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
					case 1:
					default:
						if (has_error()) {
							echo display_error();
						}

						if ($objective_details["audience"] != "all" && $objective_details["audience"] != "none" && !empty($objective_details["audience"])) {
							$objetive_audience_courses = explode(",", $objective_details["audience"]);
							if (is_array($objetive_audience_courses)) {
								foreach ($objetive_audience_courses as $course_id) {
									$PROCESSED["course_ids"][] = clean_input($course_id, "numeric");
								}
							} else {
								$PROCESSED["course_ids"][] = clean_input($course_id, "numeric"); 
							}
							$PROCESSED["objective_audience"] = "selected";
						} else {
							$PROCESSED["objective_audience"] = "all";
						}

						$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
						$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
						$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                        
						$ONLOAD[] = "jQuery('#courses_list').css('display', 'none')";
						$ONLOAD[] = "selectObjective('#selectObjectiveField', ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").", ".$OBJECTIVE_ID.")";
						$ONLOAD[] = "selectOrder('#selectOrderField', ".$OBJECTIVE_ID.", ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
						?>
						<script type="text/javascript">
						var SITE_URL = "<?php echo ENTRADA_URL;?>";
						var EDITABLE = true;
						var org_id = "<?php echo $ORGANISATION_ID; ?>";
						var objective_set_id = "<?php echo $OBJECTIVE_ID; ?>";

                        jQuery(function($) {
                            $("#objective-form").submit(function() {
                                $("#PickList").each(function() {
                                    $("#PickList option").attr("selected", "selected");
                                });
                            });
                            $("input[name=objective_audience]").click(function() {
                                if ($(this).val() == "selected") {
                                    if (!$("#course-selector").is(":visible")) {
                                        $("#course-selector").show();
                                    }
                                } else if ($("#course-selector").is(":visible")) {
                                    $("#course-selector").hide();
                                }
                            });
                        });
						</script>

						<h1><?php echo html_encode($PROCESSED["objective_name"]); ?></h1>

						<form id="objective-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives"."?".replace_query(array("action" => "add", "step" => 2)); ?>" method="post" class="form-horizontal">
							<h2 class="collapsed" title="Curriculum Tag Set Options Section">Curriculum Tag<?php echo ($objective_details["objective_parent"] == 0) ? " Set" : ""; ?> Options</h2>
							<div id="curriculum-tag-set-options-section">
								<div class="control-group">
									<label for="objective_code" class="form-nrequired control-label">Tag Set Code</label>
									<div class="controls">
										<input type="text" id="objective_code" name="objective_code" value="<?php echo ((isset($PROCESSED["objective_code"])) ? html_encode($PROCESSED["objective_code"]) : ""); ?>" class="span3" />
									</div>
								</div>

								<div class="control-group">
									<label for="objective_name" class="form-required control-label">Tag Set Name</label>
									<div class="controls">
										<input type="text" id="objective_name" name="objective_name" value="<?php echo ((isset($PROCESSED["objective_name"])) ? html_encode($PROCESSED["objective_name"]) : ""); ?>" class="span11" />
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

								<?php
								if ($objective_details["objective_parent"] == 0) {
									?>
									<div class="control-group">
										<label for="objective_audience" class="form-required control-label">Tag Set Audience</label>
										<div class="controls">
											<label class="radio">
												<input type="radio" name="objective_audience" value="all" <?php echo ($PROCESSED["objective_audience"] == "all" || $objective_details["audience"] == "all") ? "checked=\"checked\"" : ""; ?> /> <?php echo $translate->_("all_courses"); ?>
											</label>
											<label class="radio">
												<input type="radio" name="objective_audience" value="none" <?php echo ($PROCESSED["objective_audience"] == "none" || $objective_details["audience"] == "none") ? "checked=\"checked\"" : ""; ?> /> <?php echo $translate->_("no_courses"); ?>
											</label>
											<label class="radio">
												<input type="radio" name="objective_audience" value="selected" <?php echo ($PROCESSED["objective_audience"] == "selected" || $objective_details["audience"] == "selected") ? "checked=\"checked\"" : ""; ?> /> <?php echo $translate->_("selected_courses"); ?>
											</label>
										</div>
									</div>

									<div id="course-selector" class="control-group<?php echo ($PROCESSED["objective_audience"] == "selected" || $objective_details["audience"] == "selected") ? "" : " hide"; ?>">
										<div class="controls">
											<?php
											echo "<h2 style=\"margin-top:0\">" . $translate->_("selected_courses") . "</h2>";

											echo "<select class=\"multi-picklist span12 space-below\" id=\"PickList\" name=\"course_ids[]\" multiple=\"multiple\" size=\"5\">\n";
													if ((is_array($PROCESSED["course_ids"])) && (count($PROCESSED["course_ids"]))) {
														foreach ($PROCESSED["course_ids"] as $key => $course_id) {
															echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
														}
													}
											echo "</select>\n";

											echo "<div class=\"pull-left space-above\">\n";
											echo "	<input type=\"button\" id=\"courses_list_state_btn\" class=\"btn\" value=\"Show List\" onclick=\"toggle_list('courses_list')\" />\n";
											echo "</div>\n";
											echo "<div class=\"pull-right space-above\">\n";
											echo "	<input type=\"button\" id=\"courses_list_remove_btn\" class=\"btn btn-danger\" onclick=\"delIt()\" value=\"Remove\" />\n";
											echo "	<input type=\"button\" id=\"courses_list_add_btn\" class=\"btn btn-primary\" onclick=\"addIt()\" style=\"display: none\" value=\"Add\" />\n";
											echo "</div>\n";

											echo "<div class=\"clearfix\"></div>";

											echo "<div id=\"courses_list\" style=\"display:none\">\n";
											echo "	<h2>" . $translate->_("available_courses") . "</h2>\n";
											echo "	<select class=\"multi-picklist span12\" id=\"SelectList\" name=\"other_courses_list\" multiple=\"multiple\" size=\"15\">\n";
													if ((is_array($course_list)) && (count($course_list))) {
														foreach ($course_list as $course_id => $course) {
															if (!is_array($PROCESSED["course_ids"])) {
																$PROCESSED["course_ids"] = array();
															}
															if (!in_array($course_id, $PROCESSED["course_ids"])) {
																echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
															}
														}
													}
											echo "	</select>\n";
											echo "</div>\n";
                                            ?>
                                            <script type="text/javascript">
                                                jQuery(function($) {
                                                    $('#PickList').on('keydown', function(event) {
                                                        if (event.which == $.ui.keyCode.DELETE) {
                                                            delIt();
                                                        }
                                                    });
                                                    $('#SelectList').on('keydown', function(event) {
                                                        if (event.which == $.ui.keyCode.ENTER) {
                                                            addIt();
                                                        }
                                                    });
                                                })
                                            </script>
										</div>
									</div>
									<?php
								}
								?>

								<div class="control-group">
									<label for="objective_id" class="form-required control-label">Display Order</label>
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
	                        </div>
						</form>

						<div>
							<style>
								#objective-link-modal {
									max-height:500px;
									overflow-x:hidden;
									overflow-y:scroll;
								}
								#objective-link-modal .objective-list > li {
									background-image:none;
								}
								.objective-title{
									cursor:pointer;
								}
								.objective-list{
									padding-left:5px;
								}
                                .objective-description{
                                    width: 90%;
                                }
								#mapped_objectives,#objective_list_0{
									margin-left:0px;
									padding-left: 0px;
								}
								.objectives{
									width:48%;
									float:left;
								}
								.mapped_objectives{
									float:right;
									height:100%;
									width:100%;
								}
								.remove{
									display:block;
									cursor:pointer;
									float:right;
								}
								.draggable{
									cursor:pointer;
								}
								.droppable.hover{
									background-color:#ddd;
								}
								.objective-title{
									font-weight:bold;
								}
								.objective-children{
									margin-top:5px;
								}
								.objective-container{
									position:relative;
									padding-right:0px!important;
									margin-right:0px!important;
								}
								.objective-controls{
									position:absolute;
									top:5px;
									right:0px;
								}
								li.display-notice{
									border:1px #FC0 solid!important;
									padding-top:10px!important;
									text-align:center;
								}
								.hide{
									display:none;
								}
								.objective-controls i {
									display:block;
									width:16px;
									height:16px;
									cursor:pointer;
									float:left;
								}
							</style>

							<script type="text/javascript">
								var mapped = [];
								jQuery(document).ready(function($){
									jQuery('.objectives').hide();
									jQuery('.draggable').draggable({
										revert:true
									});
									jQuery('.droppable').droppable({
										drop: function(event,ui){										
											var id = jQuery(ui.draggable[0]).attr('data-id');
											var ismapped = jQuery.inArray(id,mapped);
											if(ismapped == -1){
												var title = jQuery('#objective_title_'+id).attr('data-title');										
												mapObjective(id,title);
											}
											jQuery(this).removeClass('hover');											
										},
										over:function(event,ui){
											jQuery(this).addClass('hover');
										},
										out: function(event,ui){
											jQuery(this).removeClass('hover');	
										}
									});

									jQuery(document).on('click', '.remove', function(){
										var id = jQuery(this).attr('data-id');
										var key = jQuery.inArray(id,mapped);
										if(key != -1){
											mapped.splice(key,1);
										}
										jQuery('#check_objective_'+id).attr('checked','');

										jQuery('#mapped_objective_'+id).remove();																		
										jQuery("#mapped_objectives_select option[value='"+id+"']").remove();
										if(jQuery('#mapped_objectives').children('li').length == 0){
											var warning = jQuery(document.createElement('li'))
															.attr('class','display-notice')
															.html('No <strong>objectives</strong> have been mapped to this course.');
											jQuery('#mapped_objectives').append(warning);				
										}									
									});

									jQuery(document).on('change', '.checked-objective', function(){
										var id = jQuery(this).val();
										var title = jQuery('#objective_title_'+id).attr('data-title');
										if (jQuery(this).is(':checked')) {
											mapObjective(id,title);
										} else {
											jQuery('#objective_remove_'+id).trigger('click');
										}
									});

									jQuery('.mapping-toggle').click(function(){
										var state = $(this).attr('data-toggle');
										if(state == "show"){
											$(this).attr('data-toggle','hide');
											$(this).html('Hide All <?php echo $translate->_("Objectives"); ?>');
											jQuery('.mapped_objectives').animate({width:'45%'},400,'swing',function(){
												jQuery('.objectives').css({width:'48%'});
												jQuery('.objectives').show(400);
											});										
										}else{
											$(this).attr('data-toggle','show');
											$(this).html('Show All <?php echo $translate->_("Objectives"); ?>');
											jQuery('.objectives').animate({width:'0%'},400,'swing',function(){
												jQuery('.objectives').hide();
												jQuery('.mapped_objectives').animate({width:'100%'},400,'swing');
											});																				
										}
									});

								});

								function mapObjective(id, title) {

									var li = jQuery(document.createElement('li'))
													.attr('class','mapped-objective')
													.attr('id','mapped_objective_'+id)
													.html(title);
									var rm = jQuery(document.createElement('a'))
													.attr('data-id',id)
													.attr('class','remove')
													.attr('id','objective_remove_'+id)
													.html('x');			
									jQuery(li).append(rm);											
									var option = jQuery(document.createElement('option'))
													.val(id)
													.attr('selected','selected')
													.html(title);														
									jQuery('#mapped_objectives').append(li);
									jQuery('#mapped_objectives .display-notice').remove();
									jQuery('#mapped_objectives_select').append(option);
									jQuery('#check_objective_'+id).attr('checked','checked');
									mapped.push(id);								
								}
							</script>

                            <h2 title="Curriculum Tags Section">Curriculum Tags</h2>
							<div id="curriculum-tags-section">
								<div class="pull-right space-below">
									<a href="#" class="btn btn-success objective-add-control" data-id="<?php echo $OBJECTIVE_ID; ?>"><i class="icon-plus-sign icon-white"></i> Add Curriculum Tag</a>
								</div>

								<div class="clearfix"></div>

								<div data-description="" data-id="<?php echo $OBJECTIVE_ID; ?>" data-title="" id="objective_title_<?php echo $OBJECTIVE_ID; ?>" class="objective-title" style="display:none;"></div>
								<div class="half left" id="children_<?php echo $OBJECTIVE_ID; ?>">
									<ul class="objective-list" id="objective_list_<?php echo $OBJECTIVE_ID; ?>">
									<?php
									$query = "SELECT a.* FROM `global_lu_objectives` a
												LEFT JOIN `objective_organisation` b
												ON a.`objective_id` = b.`objective_id`
												AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
												WHERE a.`objective_parent` = ".$db->qstr($OBJECTIVE_ID)."
												AND a.`objective_active` = '1'
												ORDER BY a.`objective_order`";
									$objectives = $db->GetAll($query);
									if ($objectives) {
										foreach ($objectives as $objective) {
											?>
											<li class = "objective-container"
												id = "objective_<?php echo $objective["objective_id"]; ?>">
												<?php
												$title = ($objective["objective_code"] ? $objective["objective_code"] . ": " : "") . $objective["objective_name"];
												$description = $objective["objective_description"];
												?>
												<div class="objective-title"
														id="objective_title_<?php echo $objective["objective_id"]; ?>"
														data-title="<?php echo $title;?>"
														data-id="<?php echo $objective["objective_id"]; ?>"
														data-code="<?php echo $objective["objective_code"]; ?>"
														data-name="<?php echo $objective["objective_name"]; ?>"
														data-description="<?php echo $objective["objective_description"]; ?>">
													<?php echo $title; ?>
												</div>
												<div class="objective-controls">
													<i class="objective-edit-control icon-edit" data-id="<?php echo $objective["objective_id"]; ?>"></i>
													<i class="objective-add-control icon-plus-sign" data-id="<?php echo $objective["objective_id"]; ?>"></i>
													<i class="objective-delete-control icon-minus-sign" data-id="<?php echo $objective["objective_id"]; ?>"></i>
													<i class="objective-link-control icon-link" data-id="<?php echo $objective["objective_id"]; ?>"></i>
												</div>
												<div class="objective-description content-small" id="description_<?php echo $objective["objective_id"]; ?>">
													<?php echo $description; ?>
												</div>
												<div class="objective-children" id="children_<?php echo $objective["objective_id"]; ?>">
													<ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>"></ul>
												</div>
											</li>
											<?php
										}
									}
									?>
									</ul>
								</div>
								<div class="clearfix"></div>
							</div>
						</div>
						<div id="objective-link-modal" class="hide"></div>
						<?php
					break;
				}
			} else {
				$url = ENTRADA_URL."/admin/settings/manage/objectives?org=" . $ORGANISATION_ID;
				$ONLOAD[] = "setTimeout('window.location=\\'". $url . "\\'', 5000)";

				add_error("In order to update a curriculum tag a valid identifier must be supplied. You will be redirected to the System Settings page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

				echo display_error();

				application_log("notice", "Failed to provide objective identifier when attempting to edit a curriculum tag.");
			}
		}
	} else {
		$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

		add_error("In order to update a curriculum tag a valid identifier must be supplied.");

		echo display_error();

		application_log("notice", "Failed to provide objective identifer when attempting to edit an objective.");
	}
}
