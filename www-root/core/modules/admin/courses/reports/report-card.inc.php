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
 * This report provides the ability to see all the procedures that a student who
 * belongs to the active course has completed.  The procedures are based on the
 * objectives found under Report Card Objectives for the active course.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_COURSE_REPORTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseContentResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation(), true), "update")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["STEP"]) && $tmp_input = clean_input($_GET["STEP"], array("trim", "int"))) {
		$STEP = $tmp_input;
	} else {
		$STEP = 1;
	}
	$query = "	SELECT * FROM `courses`
				WHERE `course_id` = ".$db->qstr($COURSE_ID)."
				AND `course_active` = '1'";
	$course_details	= $db->GetRow($query);
	courses_subnavigation($course_details,"reports");

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "report", "id" => $COURSE_ID, "step" => false)), "title" => "Report Card");

	$curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course_details["curriculum_type_id"], $course_details["course_id"]);
	foreach ($curriculum_periods as $curriculum_period) {
		if ($curriculum_period->getStartDate() <= time() && $curriculum_period->getFinishDate() >= time()) {
			$audience_value = $curriculum_period->getAudienceValue();
			$query = "	SELECT *, concat(c.`lastname`, ', ', c.`firstname`) as `fullname`, c.`id` as `proxy_id`
				FROM `course_audience` a
				JOIN `group_members` b
				ON a.`audience_value` = b.`group_id`
				JOIN `" . AUTH_DATABASE . "`.`user_data` c
				ON b.`proxy_id` = c.`id`
				WHERE a.`audience_type` = 'group_id'
				AND a.`course_id` = " . $db->qstr($course_details["course_id"]) . "
				AND b.`member_active` = 1
				AND a.`audience_value` = ".$db->qstr($audience_value)."
				ORDER BY `fullname` ASC";

			$student_list = $db->getAll($query);

			//Add individuals to the student list if they exist.
			$results = array();
			$query = "	SELECT *, concat(c.`lastname`, ', ', c.`firstname`) as `fullname`, c.`id` as `proxy_id`
				FROM `course_audience` a
				JOIN `" . AUTH_DATABASE . "`.`user_data` c
				ON a.`audience_value` = c.`id`
				WHERE a.`audience_type` = 'proxy_id'
				AND a.`course_id` = " . $db->qstr($course_details["course_id"]) . "
				AND a.`audience_value` = ".$db->qstr($audience_value)."
				AND a.`audience_active` = 1
				ORDER BY `fullname` ASC";
			$results = $db->getAll($query);

			if ($results && count($results) > 0) {
				$student_list = array_merge($student_list, $results);

				// Create the fullname column to sort by
				foreach ($student_list as $key => $row) {
					$fullname[$key]  = $row['fullname'];
				}

				// Sort the data with fullname ascending
				// Add $student_list as the last parameter, to sort by the common key
				array_multisort($fullname, SORT_ASC, $student_list);
			}

			$query = "	SELECT *
				FROM `course_objectives` a
				JOIN `global_lu_objectives` b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`course_id` = " . $db->qstr($course_details["course_id"]) . "
                AND a.`active` = '1'
				AND b.`objective_loggable` = 1";

			$course_objectives = $db->getAll($query);

		}
	}

	//Error Checking
	switch($STEP) {
		case "2":
			if (isset($_POST["student_proxy_id"]) && $tmp_input	= clean_input($_POST["student_proxy_id"], array("trim", "int"))) {
				$PROCESSED["student_proxy_id"] = $tmp_input;
				$query = "	SELECT concat(`lastname`, ', ', `firstname`) as fullname
							FROM `" . AUTH_DATABASE . "`.`user_data`
							WHERE `id` = " . $db->qstr($PROCESSED["student_proxy_id"]);
				$PROCESSED["fullname"] = $db->getOne($query);
			} else {
				$PROCESSED["student_proxy_id"] = 0;
				$ERROR++;
				$ERRORSTR[] = "A student must be specified.";
			}

			if ($ERROR) {
				echo display_error();
				$STEP = 1;
			}
			break;
		case "1":
		default:

			break;
	}

	//Display page
	switch($STEP) {
		case "2":
			$leaf_objs = array();

			foreach ($course_objectives as $co) {
				list($report_objectives, $top_level_id) = courses_fetch_objectives($course_details["organisation_id"], $course_details["course_id"], $co["objective_parent"], $co["objective_id"]);

				if (isset($report_objectives) && isset($report_objectives["objectives"]) && $report_objectives["objectives"]) {
					//Fetch the active curriculum period start and finish dates
					$query = "	SELECT *
								FROM `curriculum_periods` a
								JOIN `curriculum_type_organisation` b
								ON a.`curriculum_type_id` = b.`curriculum_type_id`
								WHERE a.`active` = 1
								AND b.`organisation_id` = " . $db->qstr($course_details["organisation_id"]) . "
								AND " . $db->qstr(time()) . " BETWEEN a.start_date AND a.finish_date;";
					$curr_period = $db->getRow($query);

					foreach ($report_objectives["objectives"] as $obj_id => $obj_details) {
						$is_parent = true;
						foreach($report_objectives["objectives"] as $id => $details) {
							if (array_search($obj_id, $details["parent_ids"]) === false) {
								$is_parent = false;
							} else {
								$is_parent = true;
								break;
							}
						}
						if (!$is_parent) {
							sort($COURSE_REPORT_EVENT_TYPES);
							foreach ($COURSE_REPORT_EVENT_TYPES as $etype_id) {
								$query = "	SELECT *
											FROM `events` a
											JOIN `event_objectives` b
											ON a.`event_id` = b.`event_id`
											JOIN `event_eventtypes` c
											ON a.`event_id` = c.`event_id`
											JOIN `event_audience` d
											ON d.`event_id` = a.`event_id`
											WHERE a.`course_id` = " . $db->qstr($course_details["course_id"]) . "
											AND b.`objective_id` = " . $db->qstr($obj_id) . "
											AND a.`event_finish` <= " . $db->qstr(time()) . "
											AND a.`event_finish` BETWEEN '" . $curr_period["start_date"] . "' AND '" . $curr_period["finish_date"] . "'
											AND c.`eventtype_id` = " . $db->qstr($etype_id) . "
											GROUP BY a.`event_id`, d.`audience_type`";
								$events = $db->getAll($query);
								$procedure_total = 0;

								foreach ($events as $event) {
									switch($event["audience_type"]) {
										case "proxy_id":
											$query = "	SELECT count(*)
														FROM `event_audience` a
														WHERE a.`audience_value` = " . $db->qstr($PROCESSED["student_proxy_id"]) . "
														AND a.`audience_type` = 'proxy_id'
														AND a.`event_id` = " . $db->qstr($event["event_id"]);
											$procedure_total += $db->getOne($query);
											break;
										case "course_id":
											$query = "	SELECT *
														FROM `course_audience` a
														WHERE a.`course_id` = " . $db->qstr($event["audience_value"]);
											$course_audiences = $db->getAll($query);
											foreach($course_audiences as $ca) {
												switch($ca["audience_type"]) {
													case "group_id":
														$query = "	SELECT count(*)
																	FROM `group_members` a
																	WHERE a.`proxy_id` = " .$db->qstr($PROCESSED["student_proxy_id"]) . "
																	AND a.`group_id` = " . $db->qstr($ca["audience_value"]);
														$procedure_total += $db->getOne($query);
														break;
													case "proxy_id":
														$query = "	SELECT count(*)
																	FROM `course_audience` a
																	WHERE a.`audience_value` = " . $db->qstr($PROCESSED["student_proxy_id"]) . "
																	AND a.`course_id` = " . $db->qstr($ca["course_id"]);
														$procedure_total += $db->getOne($query);
														break;
													default:

														break;
												}
											}
											break;
										case "cohort":
											$query = "	SELECT count(*)
														FROM `group_members` a
														WHERE a.`proxy_id` = " .$db->qstr($PROCESSED["student_proxy_id"]) . "
														AND a.`group_id` = " . $db->qstr($event["audience_value"]);
											$procedure_total += $db->getOne($query);
											break;
										case "group_id":
											$query = "	SELECT count(*)
														FROM `course_group_audience` a
														WHERE a.`proxy_id` = " .$db->qstr($PROCESSED["student_proxy_id"]) . "
														AND a.`cgroup_id` = " . $db->qstr($event["audience_value"]);
											$procedure_total += $db->getOne($query);
											break;
										default:

											break;
									}
								}

								$query = "	SELECT count(*)
											FROM `events` a
											JOIN `event_objectives` b
											ON a.`event_id` = b.`event_id`
											JOIN `event_attendance` c
											ON a.`event_id` = c.`event_id`
											JOIN `event_eventtypes` d
											ON a.`event_id` = d.`event_id`
											WHERE a.`course_id` = " . $db->qstr($course_details["course_id"]) . "
											AND b.`objective_id` = " . $db->qstr($obj_id) . "
											AND c.`proxy_id` = " . $db->qstr($PROCESSED["student_proxy_id"]) . "
											AND a.`event_finish` <= " . $db->qstr(time()) . "
											AND a.`event_finish` BETWEEN " . $curr_period["start_date"] . " AND " . $curr_period["finish_date"] . "
											AND d.`eventtype_id` = " . $db->qstr($etype_id);
								$procedure_completed = $db->getOne($query);

								$query = "	SELECT b.`participation_level`, count(*) as `completed`
											FROM `logbook_entries` a
											JOIN `logbook_entry_objectives` b
											ON a.`lentry_id` = b.`lentry_id`
											WHERE b.`objective_id` = " . $db->qstr($obj_id) . "
											AND a.`updated_date` BETWEEN " . $curr_period["start_date"] . " AND " . $curr_period["finish_date"] . "
											AND a.`proxy_id` =  " . $db->qstr($PROCESSED["student_proxy_id"]) . "
											GROUP BY b.`participation_level`
											ORDER BY b.`participation_level`";
								$clinical_encounters = $db->getAll($query);

								if ($procedure_total > 0) {
									$leaf_objs[$obj_id]["event_types"][$etype_id]["procedure_total"] = $procedure_total;
									$leaf_objs[$obj_id]["event_types"][$etype_id]["procedure_completed"] = $procedure_completed;
									$temp_leaf_objs[$obj_id]["breadcrumb"] = "";
									foreach($obj_details["parent_ids"] as $parent_id) {
										if (isset($report_objectives["objectives"][$parent_id])) {
											$temp_leaf_objs[$obj_id]["breadcrumb"] = $temp_leaf_objs[$obj_id]["breadcrumb"] . $report_objectives["objectives"][$parent_id]["name"];
											$temp_leaf_objs[$obj_id]["breadcrumb"] = $temp_leaf_objs[$obj_id]["breadcrumb"] . " / ";
										}
									}
									$temp_leaf_objs[$obj_id]["breadcrumb"] = $temp_leaf_objs[$obj_id]["breadcrumb"] . $obj_details["name"];

									if (array_key_exists($obj_id, $leaf_objs) && strlen($leaf_objs[$obj_id]["breadcrumb"]) < strlen($temp_leaf_objs[$obj_id]["breadcrumb"])) {
										$leaf_objs[$obj_id]["breadcrumb"] = $temp_leaf_objs[$obj_id]["breadcrumb"];
									}

									if ($clinical_encounters) {
										foreach ($clinical_encounters as $c) {
											$leaf_objs[$obj_id]["clinical_encounters"][$c["participation_level"]] = $c["completed"];
										}
									}
								}
							}
						}
					}
				}
			}
		case "1":

		default:

?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#student_proxy_id").on("change", function() {
			$("#student_procedure_form").submit();
		});
	});
</script>
<style type="text/css">
	#report-card td, #report-card th {
		text-align: center!important;
	}
	#report-card td.first-column, #report-card th.first-column {
		text-align: left!important;
	}
</style>
<h2>Report Card</h2>
<?php if ($course_objectives) { ?>
	<div class="row-fluid">
		<form id="student_procedure_form" class="form-horizontal no-printing" action="<?php echo ENTRADA_URL . "/admin/courses/reports?section=report-card&STEP=2&id=".$course_details["course_id"]; ?>" method="post">
			<div class="control-group">
				<label class="control-label form-required" for="student_proxy_id">Student:</label>
				<div class="controls">
					<select id="student_proxy_id" name="student_proxy_id">
						<option value="0">-- Select a Student --</option>
						<?php
							if ($student_list) {
								foreach ($student_list as $student) {
									$selected = false;
									if ($student["proxy_id"] == $PROCESSED["student_proxy_id"]) {
										$selected = true;
									}
									echo build_option($student["proxy_id"], $student["fullname"], $selected);
								}
							}
						?>
					</select>
				</div>
				<div class="space-above"></div>
			</div>
		</form>
	</div>
	<?php
		if ($STEP == 2 && $PROCESSED["student_proxy_id"]) {
			?>
			<div class="row-fluid">
				<h3><?php echo $PROCESSED["fullname"]; ?></h3>
			</div>
			<div class="row-fluid">
				<?php
					if (isset($leaf_objs) && count($leaf_objs) > 0) {
				?>
						<table id="report-card" class="table table-striped">
							<thead>
								<tr>
									<th class="first-column">Procedure</th>
									<?php
											foreach ($COURSE_REPORT_EVENT_TYPES as $etype_id) {
											$query = "	SELECT `eventtype_title`
														FROM `events_lu_eventtypes`
														WHERE `eventtype_id` = " . $db->qstr($etype_id) . "
														ORDER BY `eventtype_id` ASC";
											$event_title = $db->getOne($query);
											?>
											<th><?php echo $event_title ?></th>
											<?php
										}
									?>
									<th colspan="3">Clinical Encounters</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$count = 1;
								foreach($leaf_objs as $obj_id => $details) {
									if ($count == 1) {
									?>
									<tr><td></td><td class="text-center">Attended / Opportunities</td></td><td class="text-center">Observed</td><td class="text-center">Performed With Help</td><td class="text-center">Performed Independently</td></tr>
								<?php
									}
									echo "<tr>";
									echo "<td class=\"first-column\">" . $details["breadcrumb"] . "</td>";
									foreach ($COURSE_REPORT_EVENT_TYPES as $etype_id) {
										if (isset($details["event_types"][$etype_id])) {
											echo "<td>" . $details["event_types"][$etype_id]["procedure_completed"] . " / " . $details["event_types"][$etype_id]["procedure_total"] . "</td>";
										} else {
											echo "<td>-</td>";
										}
									}
									if ($details["clinical_encounters"]) {
										for ($i = 1; $i <= 3; $i++) {
											if ($details["clinical_encounters"][$i]) {
												echo "<td>" . $details["clinical_encounters"][$i] . "</td>";
											} else {
												echo "<td>-</td>";
											}
										}
									} else {
										echo "<td>-</td><td>-</td><td>-</td>";
									}
									echo "</tr>";
									$count++;
								}
								?>
							</tbody>
						</table>
				<?php
					} else {
						if ($procedure_total > 0) {
							add_notice("No objectives have been mapped to events.");
							echo display_notice();
						} else {
							add_notice("No events to report on for this student.");
							echo display_notice();
						}
					}
				?>
			</div>
			<?php
		}
	} else {
		$NOTICE++;
		$NOTICESTR[] = "No objectives to report on.  Please make sure you have mapped objectives on the details tab.";
		echo display_notice();
	}
	?>
<div class="row-fluid">
	<a class="btn pull-left no-printing" href="<?php echo ENTRADA_URL . '/admin/courses/reports?id=' . $course_details["course_id"]; ?>">Cancel</a>
</div>
<?php
	}
}
?>