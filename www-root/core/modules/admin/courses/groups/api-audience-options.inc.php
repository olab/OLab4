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
 * This API file returns an HTML table of the possible audience information
 * based on the selected course.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_COURSE_GROUPS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("course", "update", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["ajax"]) && ($_POST["ajax"] == 1)) {
		$use_ajax = true;
	} else {
		$use_ajax = false;
	}

	if ($use_ajax) {
		/**
		 * Clears all open buffers so we can return a plain response for the Javascript.
		 */
		ob_clear_open_buffers();

		$PROCESSED = array();
		$PROCESSED["course_id"] = 0;
		$group_ID = 0;

		if (isset($_POST["course_id"]) && ($tmp_input = clean_input($_POST["course_id"], "int"))) {
			$PROCESSED["course_id"] = $tmp_input;
		}
		
		if (isset($_POST["group_id"]) && ($tmp_input = clean_input($_POST["group_id"], "int"))) {
			$group_ID = $tmp_input;
		}
	}
	
	if ($PROCESSED["course_id"]) {
		$query = "SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"]);
		$course_info = $db->GetRow($query);
		if ($course_info) {
			$permission = $course_info["permission"];

			$curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course_info["curriculum_type_id"], $PROCESSED["course_id"]);

			//$query = "SELECT * FROM `groups` AS a JOIN `group_organisations` AS b ON a.`group_id` = b.`group_id` WHERE ((`group_type` = 'course_list' AND `group_value` = ".$db->qstr($PROCESSED["course_id"]).") OR (b.`organisation_id` = '".$course_info["organisation_id"]."'))".($use_ajax ? " AND `group_active` = '1'" : "");
			$query = "SELECT a.* FROM `course_audience` AS a JOIN `courses` AS b ON a.`course_id` = b.`course_id` AND a.`course_id` = ".$db->qstr($PROCESSED["course_id"])." WHERE a.`audience_active` = '1'";
			$course_list = $db->GetRow($query);
			
			$query = "SELECT * FROM `group_audience` WHERE `group_id` = ".$db->qstr($group_ID)." AND `audience_type` != 'course_id'";
			$custom_audience = $db->GetAll($query);

			if ($course_list && $curriculum_periods) { ?>
				<label class="control-label form-required">Populate from</label>

				<div class="control-group">
					<div class="controls">
						<label class="radio" for="group_audience_type_course">
							<input type="radio" name="group_audience_type" id="group_audience_type_course" value="course" onclick="selectGroupAudienceOption('course')" <?php echo ((($PROCESSED["group_audience_type"] == "course") || !isset($PROCESSED["group_audience_type"])) ? " checked=\"checked\"" : ""); ?>/>
							&nbsp;All Learners Enrolled in <?php echo html_encode($course_info["course_code"]); ?>
						</label>

						<div class="content-small">Use the existing course audience to populate the course groups.</div>

						<div id="group_audience_type_course_options" style="position: relative; margin-top: 10px;">
							<select id="cperiod_select" name="cperiod_id" onchange="showMultiSelect();" style="width: 100%;">
								<option value="">-- Select an enrolment period --</option>
								<?php
								foreach ($curriculum_periods as $period) { ?>
									<option value="<?php echo html_encode($period->getID());?>" <?php echo (isset($cperiod_id) && $cperiod_id == $period->getID() ? "selected=\"selected\"" : ""); ?>>
										<?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate())); ?>
									</option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>

				<?php
			}

			if (($permission == "open")) { ?>
				<div class="control-group">
					<div class="controls">
						<label class="radio" for="group_audience_type_custom">
							<input type="radio" name="group_audience_type" id="group_audience_type_custom" value="custom" onclick="selectGroupAudienceOption('custom')" <?php echo ((($PROCESSED["group_audience_type"] == "custom") || (!$course_list)) ? " checked=\"checked\"" : ""); ?>/>
							&nbsp;A Custom Course Audience
						</label>

						<div class="content-small">Use a custom audience to populate the course groups.</div>

						<div id="group_audience_type_custom_options" style="<?php echo ($PROCESSED["group_audience_type"] != "custom" && $course_list? "display: none; " : ""); ?>position: relative; margin-top: 10px;">
							<select id="audience_type" onchange="showMultiSelect();" style="width: 275px;">
								<option value="">-- Select an audience type --</option>
								<?php
								if ($permission == "open") {
									?>
									<option value="cohorts">Cohorts of learners</option>
									<?php
								}

								if (false && $permission == "open") {
									?>
									<option value="students">Individual learners</option>
									<?php
								}
								?>
							</select>

							<span id="options_loading" style="display:none; vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Loading ... </span>
							<span id="options_container"></span>
							<?php

							/**
							 * Compiles the list of groups from groups table (known as Cohorts).
							 */
							$COHORT_LIST = array();
							$query = "	SELECT a.*
										FROM `groups` AS a
										JOIN `group_organisations` AS b
										ON a.`group_id` = b.`group_id`
										WHERE a.`group_active` = '1'
										AND a.`group_type` = 'cohort'
										AND b.`organisation_id` = '".$course_info["organisation_id"]."'
										ORDER BY LENGTH(a.`group_name`), a.`group_name` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach($results as $result) {
									$COHORT_LIST[$result["group_id"]] = $result;
								}
							}

							/**
							 * Compiles the list of course small groups.
							 */
							$GROUP_LIST = array();
							$query = "	SELECT *
										FROM `course_groups`
										WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"])."
										AND `active` = '1'
										ORDER BY LENGTH(`group_name`), `group_name` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach($results as $result) {
									$GROUP_LIST[$result["cgroup_id"]] = $result;
								}
							}

							/**
							 * Compiles the list of students.
							 */
							$STUDENT_LIST = array();
							$query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`organisation_id`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON a.`id` = b.`user_id`
										WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
										AND b.`account_active` = 'true'
										AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
										AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
										AND b.`group` = 'student'
										AND a.`grad_year` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
										ORDER BY a.`grad_year` ASC, a.`lastname` ASC, a.`firstname` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach($results as $result) {
									$STUDENT_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
								}
							}


							/**
							 * Process cohorts.
							 */
							if ((isset($_POST["group_audience_cohorts"]) && $use_ajax)) {
								$associated_audience = explode(',', $_POST["group_audience_cohorts"]);
								if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
									foreach($associated_audience as $audience_id) {
										if (strpos($audience_id, "group") !== false) {
											if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
												$query = "	SELECT a.*
															FROM `groups` AS a
															JOIN `group_organisations` AS b
															ON a.`group_id` = b.`group_id`
															WHERE a.`group_id` = ".$db->qstr($group_id)."
															AND a.`group_type` = 'cohort'
															AND a.`group_active` = 1
															AND b.`organisation_id` = '".$course_info["organisation_id"]."'";
												$result	= $db->GetRow($query);
												if ($result) {
													$PROCESSED["associated_cohort_ids"][] = $group_id;
												}
											}
										}
									}
								}
							}

							/**
							 * Process course groups.
							 */
							if ((isset($_POST["group_audience_course_groups"]) && $use_ajax)) {
								$associated_audience = explode(',', $_POST["group_audience_course_groups"]);
								if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
									foreach($associated_audience as $audience_id) {
										if (strpos($audience_id, "cgroup") !== false) {

											if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
												$query = "	SELECT *
															FROM `course_groups`
															WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
															AND `course_id` = ".$db->qstr($PROCESSED["course_id"])."
															AND `active` = 1";
												$result	= $db->GetRow($query);
												if ($result) {
													$PROCESSED["associated_cgroup_ids"][] = $cgroup_id;
												}
											}
										}
									}
								}
							}

							/**
							 * Process students.
							 */
							if ((isset($_POST["group_audience_students"]) && $use_ajax)) {
								$associated_audience = explode(',', $_POST["group_audience_students"]);
								if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
									foreach($associated_audience as $audience_id) {
										if (strpos($audience_id, "student") !== false) {
											if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
												$query = "	SELECT a.*
															FROM `".AUTH_DATABASE."`.`user_data` AS a
															LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
															ON a.`id` = b.`user_id`
															WHERE a.`id` = ".$db->qstr($proxy_id)."
															AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
															AND b.`account_active` = 'true'
															AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
															AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
												$result	= $db->GetRow($query);
												if ($result) {
													$PROCESSED["associated_proxy_ids"][] = $proxy_id;
												}
											}
										}
									}
								}
							}

							if (!isset($PROCESSED["associated_cohort_ids"]) && !isset($PROCESSED["associated_cgroup_ids"]) && !isset($PROCESSED["associated_proxy_ids"]) && !isset($_POST["group_audience_cohorts"]) && !isset($_POST["group_audience_course_groups"]) && !isset($_POST["group_audience_students"]) && isset($group_ID)) {
								$query = "SELECT * FROM `group_audience` WHERE `group_id` = ".$db->qstr($group_ID);
								$results = false;//$db->GetAll($query);
								if ($results) {
									$PROCESSED["group_audience_type"] = "custom";

									foreach($results as $result) {
										switch($result["audience_type"]) {
											case "course_id" :
												$PROCESSED["group_audience_type"] = "course";

												$PROCESSED["associated_course_ids"] = (int) $result["audience_value"];
												break;
											case "cohort" :
												$PROCESSED["associated_cohort_ids"][] = (int) $result["audience_value"];
												break;
											case "group_id" :
												$PROCESSED["associated_cgroup_ids"][] = (int) $result["audience_value"];
												break;
											case "proxy_id" :
												$PROCESSED["associated_proxy_ids"][] = (int) $result["audience_value"];
												break;
										}
									}
								}
							}

							$cohort_ids_string = "";
							$cgroup_ids_string = "";
							$student_ids_string = "";

							if (isset($PROCESSED["associated_course_ids"]) && $PROCESSED["associated_course_ids"]) {
								$course_audience_included = true;
							} else {
								$course_audience_included = false;
							}

							if (isset($PROCESSED["associated_cohort_ids"]) && is_array($PROCESSED["associated_cohort_ids"])) {
								foreach ($PROCESSED["associated_cohort_ids"] as $group_id) {
									if ($cohort_ids_string) {
										$cohort_ids_string .= ",group_".$group_id;
									} else {
										$cohort_ids_string = "group_".$group_id;
									}
								}
							}

							if (isset($PROCESSED["associated_cgroup_ids"]) && is_array($PROCESSED["associated_cgroup_ids"])) {
								foreach ($PROCESSED["associated_cgroup_ids"] as $group_id) {
									if ($cgroup_ids_string) {
										$cgroup_ids_string .= ",cgroup_".$group_id;
									} else {
										$cgroup_ids_string = "cgroup_".$group_id;
									}
								}
							}

							if (isset($PROCESSED["associated_proxy_ids"]) && is_array($PROCESSED["associated_proxy_ids"])) {
								foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
									if ($student_ids_string) {
										$student_ids_string .= ",student_".$proxy_id;
									} else {
										$student_ids_string = "student_".$proxy_id;
									}
								}
							}
							?>
							<input type="hidden" id="group_audience_cohorts" name="group_audience_cohorts" value="<?php echo $cohort_ids_string; ?>" />
							<input type="hidden" id="group_audience_course_groups" name="group_audience_course_groups" value="<?php echo $cgroup_ids_string; ?>" />
							<input type="hidden" id="group_audience_students" name="group_audience_students" value="<?php echo $student_ids_string; ?>" />
							<input type="hidden" id="group_audience_course" name="group_audience_course" value="<?php echo $course_audience_included ? "1" : "0"; ?>" />

							<ul class="menu multiselect" id="audience_list" style="margin-top: 5px">
								<?php
								if (is_array($PROCESSED["associated_cohort_ids"]) && count($PROCESSED["associated_cohort_ids"])) {
									foreach ($PROCESSED["associated_cohort_ids"] as $group) {
										if ((array_key_exists($group, $COHORT_LIST)) && is_array($COHORT_LIST[$group])) {
											?>
											<li class="group" id="audience_group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>" style="cursor: move;"><?php echo $COHORT_LIST[$group]["group_name"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>', 'cohorts');" class="list-cancel-image" /></li>
											<?php
										}
									}
								}

								if (is_array($PROCESSED["associated_cgroup_ids"]) && count($PROCESSED["associated_cgroup_ids"])) {
									foreach ($PROCESSED["associated_cgroup_ids"] as $group) {
										if ((array_key_exists($group, $GROUP_LIST)) && is_array($GROUP_LIST[$group])) {
											?>
											<li class="group" id="audience_cgroup_<?php echo $GROUP_LIST[$group]["cgroup_id"]; ?>" style="cursor: move;"><?php echo $GROUP_LIST[$group]["group_name"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('group_<?php echo $GROUP_LIST[$group]["cgroup_id"]; ?>', 'course_groups');" class="list-cancel-image" /></li>
											<?php
										}
									}
								}

								if (is_array($PROCESSED["associated_proxy_ids"]) && count($PROCESSED["associated_proxy_ids"])) {
									foreach ($PROCESSED["associated_proxy_ids"] as $student) {
										if ((array_key_exists($student, $STUDENT_LIST)) && is_array($STUDENT_LIST[$student])) {
											?>
											<li class="user" id="audience_student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>" style="cursor: move;"><?php echo $STUDENT_LIST[$student]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>', 'students');" class="list-cancel-image" /></li>
											<?php
										}
									}
								}
								?>
							</ul>
						</div>
					</div>
				</div>

				<?php
			}
		}
	}

	/**
	 * If we are return this via Javascript,
	 * exit now so we don't get the entire page.
	 */
	if ($use_ajax) {
		exit;
	}
}