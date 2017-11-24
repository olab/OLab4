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
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$ASSESSMENT_IDS = array();

	if (isset($_GET["assessment_ids"]) && ($tmp_input = explode(",", $_GET["assessment_ids"]))) {
		foreach ($tmp_input as $assessment_id) {
			$assessment_id = clean_input($assessment_id, array("trim", "int"));

			if ($assessment_id) {
				$ASSESSMENT_IDS[] = $assessment_id;
			}
		}
	}

	if ((isset($_GET["cohort"])) && ($cohort = clean_input($_GET["cohort"], array("trim", "int")))) {
		$COHORT = $cohort;
	} elseif (isset($_GET["cohort-quick-select"]) && ($tmp_input = (int)$_GET["cohort-quick-select"])) {
		$COHORT = $tmp_input;
	}
	
	if ($COURSE_ID) {
		$query = "	SELECT * FROM `courses`
					WHERE `course_id` = ".$db->qstr($COURSE_ID)."
					AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);
		
		if ($course_details && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "read")) {
			if (!empty($ASSESSMENT_IDS)) {
				$query = "	SELECT a.*,b.`id` as `marking_scheme_id`, b.`handler`
							FROM `assessments` AS a
							LEFT JOIN `assessment_marking_schemes` AS b
                            ON b.`id` = a.`marking_scheme_id`
							WHERE a.`assessment_id` IN (".implode(",", $ASSESSMENT_IDS).")
							AND a.`active` = '1'
                            AND a.`course_id` = ".$db->qstr($COURSE_ID)."
                            ORDER BY a.`order`";
			} else {
				$query = "	SELECT a.*,b.`id` as `marking_scheme_id`, b.`handler`
							FROM `assessments` AS a
							LEFT JOIN `assessment_marking_schemes` AS b
                            ON b.`id` = a.`marking_scheme_id`
							WHERE a.`cohort` = ".$db->qstr($COHORT)."
							AND a.`active` = '1'
                            AND a.`course_id` = ".$db->qstr($COURSE_ID)."
                            ORDER BY a.`order`";
			}
			$assessments = $db->GetAll($query);
			if ($assessments) {
                $temp_setting = $ENTRADA_SETTINGS->fetchByShortname("export_weighted_grade", $ENTRADA_USER->getActiveOrganisation());
                $export_weighted_grades = ($temp_setting && $temp_setting->getValue() ? true : false);
                $export_calculated_grades = json_decode(($temp_setting && $temp_setting->getValue() ? $temp_setting->getValue() : false));
                $export_calculated_grades_enabled = ($export_calculated_grades && isset($export_calculated_grades->enabled) && $export_calculated_grades->enabled ? true : false);
				// CSV Download
				$groups = array();				
				$query = "SELECT b.`id` AS `proxy_id`, 
							CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, 
							b.`number`, 
							c.`group`, 
							c.`role`,
							d.`group_id`
							FROM `".AUTH_DATABASE."`.`user_data` AS b
							JOIN `".AUTH_DATABASE."`.`user_access` AS c
							ON c.`user_id` = b.`id`
							AND c.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND c.`account_active` = 'true'
							AND (c.`access_starts` = '0' OR c.`access_starts` <= ".$db->qstr(time()).")
							AND (c.`access_expires` = '0' OR c.`access_expires` >= ".$db->qstr(time()).")
							JOIN `group_members` AS d
							ON b.`id` = d.`proxy_id`";

				if (isset($COHORT)) {
					$query .= " WHERE c.`group` = 'student' AND d.`group_id` = ".$db->qstr($COHORT);
				} else {					
					$cquery = "SELECT DISTINCT(`cohort`) FROM `assessments`
                                WHERE `assessment_id` IN (".implode(",", $ASSESSMENT_IDS).")
                                AND `active` = '1'";
					$cohorts = $db->GetAll($cquery);
					if ($cohorts) {
						foreach($cohorts as $cohort){
							$groups[] = (int)$cohort["cohort"];
						}
					}
					$query .= " WHERE c.`group` = 'student' AND d.`group_id` IN (".implode(",", $groups).")";
				}
                $query .= " GROUP BY b.`id`";
                $query .= " ORDER BY `fullname`";
				$students = $db->GetAll($query);
				
				ob_start();
				echo "\"Number\",\"Fullname\"";
				$assessment_ids = array();
				$indexed_assessments = array();
				foreach ($assessments as $key => $assessment) {
					$assessment_ids[] = $assessment["assessment_id"];
					$indexed_assessments[$assessment["assessment_id"]] = $assessment;
					$weight_heading = "";
					if ($export_weighted_grades) {
						$weight_heading  = " [Weighting: ".$assessment["grade_weighting"]."%]";
					}					
					echo ",\"".trim($assessment["name"]).(" [".(assessment_suffix($assessment) == "%" ? "Out of 100%" : ($assessment["handler"] == "Numeric" ? "Out of ".$assessment["numeric_grade_points_total"]." marks": ($assessment["handler"] == "Boolean" ? "P for Pass, F for Fail" : "C for Complete, I for Incomplete")))."]").$weight_heading." (".trim($assessment["type"]).")\"";
				}
                $assessment_ids_string = "";
                foreach ($assessment_ids as $assessment_id) {
                    $assessment_ids_string .= ($assessment_ids_string ? "," : "").$db->qstr($assessment_id);
                }
				// foreach ($assessments as $key => $assessment) {
				// 	$query .= " LEFT JOIN `".DATABASE_NAME."`.`assessment_grades` AS assessment_$key ON b.`id` = assessment_$key.`proxy_id` AND assessment_$key.`assessment_id` IN (".$db->qstr($assessment["assessment_id"]).")";
				// }
				if ($export_weighted_grades) {
				    echo ",\"Weighted Total\"";
				}
				if ($export_calculated_grades_enabled) {
				    echo ",\"".(isset($export_calculated_grades->grade_long) && $export_calculated_grades->grade_long ? $export_calculated_grades->grade_long : "Rounded Total")."\"";
				}
				// foreach ($assessments as $key => $assessment) {
				// 	$groups[] = $assessment["cohort"];
				// 	$query .= ", assessment_$key.`grade_id` AS `".$key."_grade_id`, assessment_$key.`value` AS `".$key."_grade_value` ";
				// }				
				echo "\n";
				if (count($students) >= 1) {
					foreach ($students as $student) {
						$proxy_id	= $student["proxy_id"];

						$cols = array();
						$cols[]	= trim((($student["group"] == "student") ? $student["number"] : 0));
						$cols[]	= trim($student["fullname"]);
						$query = "SELECT *, a.`assessment_id` as `assessment_id` FROM `assessment_grades` a
									LEFT JOIN `assessment_exceptions` b
									ON a.`assessment_id` = b.`assessment_id`
									AND a.`proxy_id` = b.`proxy_id`
									WHERE a.`assessment_id` IN (".$assessment_ids_string.")
									AND a.`proxy_id` = ".$db->qstr($student["proxy_id"]);
						$student_assessments = $db->GetAll($query);
						$s_assessments = array();
						foreach($student_assessments as $assessment){
							$s_assessments[$assessment["assessment_id"]] = $indexed_assessments[$assessment["assessment_id"]];
							$s_assessments[$assessment["assessment_id"]]["grade"] = $assessment["value"];
							if($assessment["grade_weighting"]){
								$s_assessments[$assessment["assessment_id"]]["grade_weighting"] = $assessment["grade_weighting"];
							}
						}
						foreach($assessment_ids as $assessment_id){					 
							if(!isset($s_assessments[$assessment_id])){
								$cols[] = trim(format_retrieved_grade(0, $indexed_assessments[$assessment_id]));
								continue;
							}
							$cols[] = trim(format_retrieved_grade($s_assessments[$assessment_id]["grade"], $s_assessments[$assessment_id]));
						}
						if ($export_weighted_grades) {
                            $weight = gradebook_get_weighted_grades($COURSE_ID, $student["group_id"], $student["proxy_id"], false, $assessment_ids_string, false);
							$cols[] = round($weight["grade"], 2);
                            if ($export_calculated_grades_enabled) {
                                $export_grade = "";
                                $whole_number_grade = round($weight["grade"], 0);
                                switch ($export_calculated_grades->grade_short) {
                                    case "letter_grade" :
                                        foreach ($export_calculated_grades->grades as $letter => $range) {
                                            if ($whole_number_grade >= $range->min && $whole_number_grade <= $range->max) {
                                                $export_grade = $letter;
                                            }
                                        }
                                        break;
                                    case "rounded_percent" :
                                    default :
                                        $export_grade = $whole_number_grade;
                                        break;
                                }
                                $cols[] = $export_grade;
                            }
							if($weight["total"] != 100){							
								$cols[] = "[WARNING]: Your weighting totals do not equal 100% or this student is missing a weighted assessment. Current weighted total worth ".$weight["total"]."% of total course mark.";
							}
						}
						echo "\"".implode("\",\"", $cols)."\"", "\n";
					}
					if ($export_weighted_grades) {
						echo "\n\n\n";
						echo "\"[NOTE]: Weighted Total grades account for student specific weighting exceptions\"\n";
					}
				}
				$contents = ob_get_contents();

				ob_clear_open_buffers();
				
				if (isset($COHORT)) {
					$filename = date("Y-m-d")."_".useable_filename($course_details["course_code"]."_".clean_input(groups_get_name($COHORT), array("trim", "file"))."_gradebook").".csv\"";
				} else {
					$filename = date("Y-m-d")."_".useable_filename($course_details["course_code"]."_gradebook").".csv\"";
				}
				
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: text/csv");
				header("Content-Disposition: attachment; filename=\"".$filename);
				header("Content-Length: ".strlen($contents));
				header("Content-Transfer-Encoding: binary\n");

				echo $contents;
				exit;
			} else {
				$ERROR++;
				$ERRORSTR[] = "In order to view an assessment's grades you must provide some valid assessment identifiers.";

				echo display_error();

				application_log("notice", "Failed to provide a valid assessment identifiers when attempting to view an assessment's grades.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You don't have permission to view this gradebook.";

			echo display_error();

			application_log("error", "User tried to view gradebook without permission.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to export a gradebook you must provide a valid course identifier.";

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to export an gradebook's grades.");
	}
}