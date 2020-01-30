<?php
/**
 * One-Off Report
 *
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_RELATIVE);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Course Summary");
	
	$HEAD[]		= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
	$ONLOAD[]	= "$('courses_list').style.display = 'none'";

	$organisation_id_changed = false;
	
	/**
	 * Fetch the organisation_id that has been selected.
	 */
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = -1;
	} elseif ((isset($_GET["org_id"])) && ($tmp_input = clean_input($_GET["org_id"], "int"))) {
		$organisation_id_changed = true;
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = $tmp_input;
	} else {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = (int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"];
	}
		
	/**
	 * Fetch all courses into an array that will be used.
	 */
	$query = "SELECT * FROM `courses`".(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] > 0) ? " WHERE `organisation_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]) : "")." ORDER BY `course_code` ASC";
	$courses = $db->GetAll($query);
	if ($courses) {
		foreach ($courses as $course) {
			$course_list[$course["course_id"]] = array("code" => $course["course_code"], "name" => $course["course_name"]);
		}
	}

	/**
	 * Fetch selected course_ids.
	 */
	if ((isset($_POST["course_ids"])) && (is_array($_POST["course_ids"]))) {
		$course_ids = array();
		
		foreach ($_POST["course_ids"] as $course_id) {
			if ($course_id = (int) $course_id) {
				$course_ids[] = $course_id;
			}
		}
		
		if (count($course_ids)) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = $course_ids;
		} else {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
		}
	} elseif (($organisation_id_changed) || (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"]))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
	}
	
	if (isset($_POST["event_title_search"]) && $_POST["event_title_search"]) {
		$event_title_search = clean_input($_POST["event_title_search"], "notags");
	}
	
	$PROCESSED["cohort"] = (int) $_POST["cohort"];
	?>
	<style type="text/css">
	h1 {
		page-break-before:	always;
		border-bottom:		2px #CCCCCC solid;
		font-size:			24px;
	}
	
	h2 {
		font-weight:		normal;
		border:				0px;
		font-size:			18px;
	}
	
	div.top-link {
		float: right;
	}
	</style>	
	<div class="no-printing">
		<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" onsubmit="selIt()">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 20%" />
					<col style="width: 77%" />
				</colgroup>
				<tbody>
					<tr>
						<td style="vertical-align: top;"><input id="cohort_checkbox" type="checkbox" disabled="disabled" checked="checked"></td>
						<td style="vertical-align: top;"><label class="form-required" for="cohort">Cohort</label></td>
						<td style="vertical-align: top;">
							<select name="cohort" id="cohort">
								<?php
									$active_cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
									if (isset($active_cohorts) && !empty($active_cohorts)) {
										foreach ($active_cohorts as $cohort) {
											echo "<option value=\"" . $cohort["group_id"] . "\"" . (($PROCESSED["cohort"] == $cohort["group_id"]) ? " selected=\"selected\"" : "") . ">" . html_encode($cohort["group_name"]) . "</option>\n";
										}
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>					
					<tr>
						<td></td>
						<td style="vertical-align: top;"><label class="form-required">Courses Included</label></td>
						<td style="vertical-align: top;">
							<?php
							echo "<select class=\"multi-picklist\" id=\"PickList\" name=\"course_ids[]\" multiple=\"multiple\" size=\"5\" style=\"width: 100%; margin-bottom: 5px\">\n";
									if ((is_array($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) && (count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"]))) {
										foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
											echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
										}
									}
							echo "</select>\n";
							echo "<div style=\"float: left; display: inline\">\n";
							echo "	<input type=\"button\" id=\"courses_list_state_btn\" class=\"btn\" value=\"Show List\" onclick=\"toggle_list('courses_list')\" />\n";
							echo "</div>\n";
							echo "<div style=\"float: right; display: inline\">\n";
							echo "	<input type=\"button\" id=\"courses_list_remove_btn\" class=\"btn btn-danger\" onclick=\"delIt()\" value=\"Remove\" />\n";
							echo "	<input type=\"button\" id=\"courses_list_add_btn\" class=\"btn btn-primary\" onclick=\"addIt()\" style=\"display: none\" value=\"Add\" />\n";
							echo "</div>\n";
							echo "<div id=\"courses_list\" style=\"clear: both; padding-top: 3px; display: none\">\n";
							echo "	<h2>Courses List</h2>\n";
							echo "	<select class=\"multi-picklist\" id=\"SelectList\" name=\"other_courses_list\" multiple=\"multiple\" size=\"15\" style=\"width: 100%\">\n";
									if ((is_array($course_list)) && (count($course_list))) {
										foreach ($course_list as $course_id => $course) {
											if (!in_array($course_id, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) {
												echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
											}
										}
									}
							echo "	</select>\n";
							echo "	</div>\n";
							echo "	<script type=\"text/javascript\">\n";
							echo "	\$('PickList').observe('keypress', function(event) {\n";
							echo "		if (event.keyCode == Event.KEY_DELETE) {\n";
							echo "			delIt();\n";
							echo "		}\n";
							echo "	});\n";
							echo "	\$('SelectList').observe('keypress', function(event) {\n";
							echo "	    if (event.keyCode == Event.KEY_RETURN) {\n";
							echo "			addIt();\n";
							echo "		}\n";
							echo "	});\n";
							echo "	</script>\n";
							?>
						</td>
					</tr>

					<tr>
						<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
	<?php
	if ($STEP == 2) {
		$output		= array();
		$appendix	= array();
		
		$courses_included	= array();
		$eventtype_legend	= array();
		
		echo "<h1 style=\"page-break-before: avoid\">Assessment Objective Summary</h1>";

		$organisation_where = " AND (b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation()).")";
		
		$eventtype_legend[$event_type["eventtype_id"]] = $event_type["eventtype_title"];
		
		$presentation_ids = array();
		$mcc_presentations = fetch_clinical_presentations(309);
		if ($mcc_presentations) {
			foreach ($mcc_presentations as $mcc_presentation) {
				$presentation_ids[] = $mcc_presentation["objective_id"];
			}
		}
		
		foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
			$query =  "	SELECT a.`assessment_id`, a.`name`, a.`description`
						FROM `assessments` AS a
						JOIN `assessments_lu_meta` AS b
						ON a.`characteristic_id` = b.`id`
						WHERE `cohort` = " . $db->qstr($PROCESSED["cohort"])."
						AND `course_id` = ". $db->qstr($course_id)."
						AND a.`active` = 1
						ORDER BY a.`order` ASC";
					
			$results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
			
			$courses_included[$course_id] = $course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"];
			
			if ($results) {
				
				foreach ($results as $result_id => $result) {
					
					$query = "	SELECT a.`objective_type`, b.`objective_name`, a.`objective_id`
								FROM `assessment_objectives` AS a
								JOIN `global_lu_objectives` AS b
								ON a.`objective_id` = b.`objective_id`
								WHERE a.`assessment_id` = ".$db->qstr($result["assessment_id"])."
								ORDER BY a.`objective_type`";
					$objectives = $db->GetAll($query);
					foreach ($objectives as $objective) {
						if ($objective["objective_type"] == "clinical_presentation") {
							$assessment_objectives["clinical_presentations"][] =  $objective["objective_name"];
						} else {
							$assessment_objectives["objectives"][] = $objective["objective_name"];
						}
					}
					if ($assessment_objectives) {
							$results[$result_id]["assessment_objectives"] = $assessment_objectives;
					}
					unset($assessment_objectives);
					
				}
			}
			
			$output[$course_id] = $results;
			
		}
		?>
		<style type="text/css">
		table.grid thead tr td {
			font-weight: 700;
			border-bottom: 2px #CCC solid;
			font-size: 11px;
		}
		
		table.grid tbody tr td {
			vertical-align:top;
			padding:3px 2px 10px 4px;
			font-size: 11px;
			border-bottom: 1px #EEE solid;
		}
		
		table.grid tbody tr td a {
			font-size: 11px;
		}
		
		table.grid tbody td.border-r {
			border-right: 1px #EEE solid;
		}
		</style>
		<?php
		if (count($output)) {
			foreach ($output as $course_id => $result) {
				?>
				<h1><?php echo html_encode($courses_included[$course_id]); ?></h1>
				<?php
				if ($output[$course_id]) {
					?>
					<table class="grid" style="width: 900px" cellspacing="0" summary="<?php echo html_encode($courses_included[$course_id]); ?>">
					<colgroup>
						<col style="width: 25%" />
						<col style="width: 30%" />
						<col style="width: 15%" />
						<col style="width: 20%" />
					</colgroup>
					<thead>
						<tr>
							<td>Assessment Title</td>
							<td>Description</td>
							<td class="border-r"><?php echo $translate->_("Objectives"); ?></td>
							<td class="border-r">Presentations</td>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($output[$course_id] as $assessment) {
						/*echo "<pre>";
						var_dump($assessment);
						echo "</pre>";*/
						
						$objectives = array();
						if ($assessment["assessment_objectives"]["objectives"]) {
							foreach ($assessment["assessment_objectives"]["objectives"] as $value) {
								$firstpart = substr($value, 0, (strlen($value) - 1));
								$letter = substr($value, -1);
								
								if (!isset($objectives[$firstpart])) {
									$objectives[$firstpart] = $firstpart . $letter;
								} else {
									$objectives[$firstpart] .= ", " . $letter;
								}
							}
						}
						
						echo "<tr>\n";
						echo "\t<td><a href=\"".ENTRADA_URL."/admin/gradebook/assessments/?section=edit&id=".$course_id."&assessment_id=".$assessment["assessment_id"]."\" target=\"_blank\">".$assessment["name"]."</a></td>\n";
						echo "\t<td>".$assessment["description"]."</td>\n";
						echo "\t<td>".(!empty($assessment["assessment_objectives"]["objectives"]) ? "&rsaquo; ".implode("<br />&rsaquo; ", $objectives) : "&nbsp;")."</td>\n";
						echo "\t<td>".(!empty($assessment["assessment_objectives"]["clinical_presentations"]) ? "&rsaquo; ".implode("<br />&rsaquo; ", $assessment["assessment_objectives"]["clinical_presentations"]) : "")."</td>\n";
						echo "</tr>\n";
					}
					?>
					</tbody>
					</table>
					<?php
				} else {
					echo display_notice(array("There are no assessments for this course for the selected cohort."));
				}
			}
		}
	}
}
?>
