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

if (!defined("IN_NOTICES")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("notice", "create", false)) {
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
		if (isset($_POST["org_id"]) && ($tmp_input = clean_input($_POST["org_id"], "int"))) {
			$PROCESSED["organisation_id"] = $tmp_input;
		}
	}

	if ($PROCESSED["organisation_id"]) {
		?>
		<div class="control-group">
			<label for="faculty_name" class="form-required">Notice Recipients:</label>

			<div id="event_audience_type_custom_options" style="<?php echo ($course_list && !$custom_audience ? "display: none; " : ""); ?>position: relative">
				<?php
				if (!$use_ajax) {
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
								AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation_id"])."
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
					$query = "	SELECT a.*
								FROM `groups` AS a
								JOIN `group_organisations` AS b
								ON a.`group_id` = b.`group_id`
								WHERE a.`group_active` = '1'
								AND a.`group_type` = 'course_list'
								AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation_id"])."
								ORDER BY LENGTH(a.`group_name`), a.`group_name` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						foreach($results as $result) {
							$GROUP_LIST[$result["group_id"]] = $result;
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
					 * Compiles the list of faculty.
					 */
					$FACULTY_LIST = array();
					$query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`organisation_id`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
								AND b.`group` = 'faculty'
								ORDER BY a.`grad_year` ASC, a.`lastname` ASC, a.`firstname` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						foreach($results as $result) {
							$FACULTY_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
						}
					}

					/**
					 * Compiles the list of staff.
					 */
					$STAFF_LIST = array();
					$query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`organisation_id`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
								AND (b.`group` = 'medtech' || b.`group` = 'staff')
								ORDER BY a.`grad_year` ASC, a.`lastname` ASC, a.`firstname` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$STAFF_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
						}
					}
				}

				/**
				 * Process cohorts.
				 */
				if ((isset($_POST["associated_cohort"]) && $use_ajax)) {
					$associated_audience = explode(',', $_POST["event_audience_cohorts"]);
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
												AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation_id"]);
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_cohort"][] = $group_id;
									}
								}
							}
						}
					}
				}

				/**
				 * Process students.
				 */
				if ((isset($_POST["associated_student"]) && $use_ajax)) {
					$associated_audience = explode(',', $_POST["associated_student"]);
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
										$PROCESSED["associated_student"][] = $proxy_id;
									}
								}
							}
						}
					}
				}

				$target_audience = false;
				if (!isset($PROCESSED["associated_cohort"]) && !isset($PROCESSED["associated_student"]) && !isset($_POST["associated_cohort"]) && !isset($_POST["associated_student"]) && isset($PROCESSED["organisation_id"])) {

					$query = "SELECT * FROM `notice_audience` WHERE `notice_id` = ".$db->qstr($NOTICE_ID);
					$audience = $db->GetAll($query);
					if($audience){
						foreach($audience as $amember){
							if (strpos($amember["audience_type"], "all:") !== false || $amember["audience_type"] == "public") {
								$target_audience = $amember["audience_type"];
							}
							$PROCESSED["associated_".$amember["audience_type"]][] = (int)$amember["audience_value"];
						}
					}
				}

				$cohort_ids_string = "";
				$clist_ids_string = "";
				$student_ids_string = "";
				$faculty_ids_string = "";
				$staff_ids_string = "";

				if (isset($PROCESSED["associated_course_ids"]) && $PROCESSED["associated_course_ids"]) {
					$course_audience_included = true;
				} else {
					$course_audience_included = false;
				}

				if (isset($PROCESSED["associated_cohort"]) && is_array($PROCESSED["associated_cohort"])) {
					foreach ($PROCESSED["associated_cohort"] as $group_id) {
						if ($cohort_ids_string) {
							$cohort_ids_string .= ",group_".$group_id;
						} else {
							$cohort_ids_string = "group_".$group_id;
						}
					}
				}

				if (isset($PROCESSED["associated_course_list"]) && is_array($PROCESSED["associated_course_list"])) {
					foreach ($PROCESSED["associated_course_list"] as $group_id) {
						if ($clist_ids_string) {
							$clist_ids_string .= ",cgroup_".$group_id;
						} else {
							$clist_ids_string = "cgroup_".$group_id;
						}
					}
				}

				if (isset($PROCESSED["associated_student"]) && is_array($PROCESSED["associated_student"])) {
					foreach ($PROCESSED["associated_student"] as $proxy_id) {
						if(array_key_exists($proxy_id, $STUDENT_LIST)){

							if ($student_ids_string) {
								$student_ids_string .= ",student_".$proxy_id;
							} else {
								$student_ids_string = "student_".$proxy_id;
							}
						}
					}
				}

				if (isset($PROCESSED["associated_faculty"]) && is_array($PROCESSED["associated_faculty"])) {
					foreach ($PROCESSED["associated_faculty"] as $proxy_id) {
						if(array_key_exists($proxy_id, $FACULTY_LIST)){
							if ($faculty_ids_string) {
								$faculty_ids_string .= ",faculty_".$proxy_id;
							} else {
								$faculty_ids_string = "faculty_".$proxy_id;
							}
						}
					}
				}

				if (isset($PROCESSED["associated_staff"]) && is_array($PROCESSED["associated_staff"])) {
					foreach ($PROCESSED["associated_staff"] as $proxy_id) {
						if(array_key_exists($proxy_id, $STAFF_LIST)){
							if ($staff_ids_string) {
								$staff_ids_string .= ",staff_".$proxy_id;
							} else {
								$staff_ids_string = "staff_".$proxy_id;
							}
						}
					}
				}
				?>
				<select id="audience_type" name="target_audience" onchange="showMultiSelect();" style="width: 275px;">
					<option value="">-- Select an audience type --</option>
					<option value="public"<?php echo ($target_audience == "public" ? "selected=\"selected\"" : ""); ?>>Public announcement visible on login page.</option>
					<option value="all:all"<?php echo ($target_audience=="all:users"?"selected=\"selected\"":"");?>>Everyone should receive this notice.</option>
					<option value="all:student"<?php echo ($target_audience=="all:student"?"selected=\"selected\"":"");?>>All students should receive this notice.</option>
					<option value="all:faculty"<?php echo ($target_audience=="all:faculty"?"selected=\"selected\"":"");?>>All faculty should receive this notice.</option>
					<option value="all:staff"<?php echo ($target_audience=="all:staff"?"selected=\"selected\"":"");?>>All staff should receive this notice.</option>
					<option value="cohort">Selected cohorts of students should receive this notice.</option>
					<option value="course_list">Students in selected course lists should receive this notice.</option>
					<option value="student">Selected individual students should receive this notice.</option>
					<option value="faculty">Selected individual faculty should receive this notice.</option>
					<option value="staff">Selected individual staff should receive this notice.</option>
				</select>

				<span id="options_loading" style="display:none; vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Loading ... </span>
				<span id="options_container"></span>

				<input type="hidden" id="associated_cohort" name="associated_cohort" value="<?php echo $cohort_ids_string; ?>" />
				<input type="hidden" id="associated_course_list" name="associated_course_list" value="<?php echo $clist_ids_string; ?>" />
				<input type="hidden" id="associated_student" name="associated_student" value="<?php echo $student_ids_string; ?>" />
				<input type="hidden" id="associated_faculty" name="associated_faculty" value="<?php echo $faculty_ids_string; ?>" />
				<input type="hidden" id="associated_staff" name="associated_staff" value="<?php echo $staff_ids_string; ?>" />
				<input type="hidden" id="associated_course" name="associated_course" value="<?php echo $course_audience_included ? "1" : "0"; ?>" />

				<ul class="menu multiselect" id="audience_list" style="margin-top: 5px<?php echo ($target_audience ? "; display: none;" : "");?>">
					<?php
					if (isset($PROCESSED["associated_cohort"]) && is_array($PROCESSED["associated_cohort"]) && count($PROCESSED["associated_cohort"])) {
						foreach ($PROCESSED["associated_cohort"] as $group) {
							if ((array_key_exists($group, $COHORT_LIST)) && is_array($COHORT_LIST[$group])) {
								?>
								<li class="group" id="audience_group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>" style="cursor: move;"><?php echo $COHORT_LIST[$group]["group_name"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('group_<?php echo $COHORT_LIST[$group]["group_id"]; ?>', 'cohort');" class="list-cancel-image" /></li>
								<?php
							}
						}
					}

					if (isset($PROCESSED["associated_course_list"]) && is_array($PROCESSED["associated_course_list"]) && count($PROCESSED["associated_course_list"])) {
						foreach ($PROCESSED["associated_course_list"] as $group) {
							if ((array_key_exists($group, $GROUP_LIST)) && is_array($GROUP_LIST[$group])) {
								?>
								<li class="group" id="audience_cgroup_<?php echo $GROUP_LIST[$group]["group_id"]; ?>" style="cursor: move;"><?php echo $GROUP_LIST[$group]["group_name"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('cgroup_<?php echo $GROUP_LIST[$group]["group_id"]; ?>', 'course_list');" class="list-cancel-image" /></li>
								<?php
							}
						}
					}

					if (isset($PROCESSED["associated_student"]) && is_array($PROCESSED["associated_student"]) && count($PROCESSED["associated_student"])) {
						foreach ($PROCESSED["associated_student"] as $student) {
							if ((array_key_exists($student, $STUDENT_LIST)) && is_array($STUDENT_LIST[$student])) {
								?>
								<li class="user" id="audience_student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>" style="cursor: move;"><?php echo $STUDENT_LIST[$student]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>', 'student');" class="list-cancel-image" /></li>
								<?php
							}
						}
					}

					if (isset($PROCESSED["associated_faculty"]) && is_array($PROCESSED["associated_faculty"]) && count($PROCESSED["associated_faculty"])) {
						foreach ($PROCESSED["associated_faculty"] as $faculty) {
							if ((array_key_exists($faculty, $FACULTY_LIST)) && is_array($FACULTY_LIST[$faculty])) {
								?>
								<li class="user" id="audience_faculty_<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>" style="cursor: move;"><?php echo $FACULTY_LIST[$faculty]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('faculty_<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>', 'faculty');" class="list-cancel-image" /></li>
								<?php
							}
						}
					}

					if (isset($PROCESSED["associated_staff"]) && is_array($PROCESSED["associated_staff"]) && count($PROCESSED["associated_staff"])) {
						foreach ($PROCESSED["associated_staff"] as $staff) {
							if ((array_key_exists($staff, $STAFF_LIST)) && is_array($STAFF_LIST[$staff])) {
								?>
								<li class="user" id="audience_staff_<?php echo $STAFF_LIST[$staff]["proxy_id"]; ?>" style="cursor: move;"><?php echo $STAFF_LIST[$staff]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeAudience('staff_<?php echo $STAFF_LIST[$staff]["proxy_id"]; ?>', 'staff');" class="list-cancel-image" /></li>
								<?php
							}
						}
					}
					?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * If we are return this via Javascript,
	 * exit now so we don't get the entire page.
	 */
	if ($use_ajax) {
		exit;
	}
}