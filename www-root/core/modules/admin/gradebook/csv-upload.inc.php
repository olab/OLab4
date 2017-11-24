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
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ini_set("auto_detect_line_endings", 1);
	if (isset($_GET["assessment_id"]) && $tmp_input = (int)$_GET["assessment_id"]) {
		$ASSESSMENT_ID = $tmp_input;
	}

	if ($ASSESSMENT_ID) {
        if (isset($_POST["enable_grade_threshold"]) && $_POST["enable_grade_threshold"] && isset($_POST["grade_threshold"]) && ((int)$_POST["grade_threshold"])) {
            $grade_threshold = ((int)$_POST["grade_threshold"]);
        } else {
            $grade_threshold = false;
        }

        $url = ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("step" => false, "section" => "grade", "assessment_id" => $ASSESSMENT_ID));
        if ($_FILES["file"]["error"] > 0) {
            add_error("Error occurred while uploading file.");
        } elseif(!in_array($_FILES["file"]["type"], array("text/csv", "application/vnd.ms-excel","text/comma-separated-values","application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel","application/octet-stream"))) {
            add_error("Invalid <strong>file type</strong> uploaded. Must be a CSV file in the proper format.");
        } else {
            if (!DEMO_MODE) {
                $lines = file($_FILES["file"]["tmp_name"]);
            } else {
                $lines = file(DEMO_GRADEBOOK);
            }
            $PROCESSED["assessment_id"] = $ASSESSMENT_ID;
            $query = "SELECT *, a.`name` as `assessment_name` FROM `assessments` AS a
                        JOIN `assessment_marking_schemes` AS b
                        ON a.`marking_scheme_id` = b.`id`
                        WHERE a.`assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                        AND a.`active` = 1";
            $assessment = $db->GetRow($query);
            $GROUP = $assessment["cohort"];
            $ASSESSMENT_NAME = $assessment["assessment_name"];
            $clean_parameters = array("trim");
            switch ($assessment["handler"]) {
                case "Boolean" :
                case "IncompleteComplete" :
                    $clean_parameters[] = "alphanumeric";
                break;
                case "Numeric" :
                case "Percentage" :
                default :
                    $clean_parameters[] = "float";
                break;
            }
            if ($GROUP) {
                echo "<form id=\"errorForm\" action=\"".ENTRADA_URL."/admin/".$MODULE."/assessments?".replace_query(array("section" => "grade", "assessment_id" => $ASSESSMENT_ID))."\" method=\"POST\">";
                foreach ($lines as $key => $line) {
                    $member_found = false;
                    $line_data = explode(",",$line);
                    $stud_num = ((int) $line_data[0]);
                    $temp_value = preg_replace("/\|(.*)/s", "", $line_data[1]);
                    $preserved_input = $temp_value;
                    $temp_value = clean_input($temp_value, $clean_parameters);
                    $valid_value = false;
                    if (in_array($assessment["handler"], array("Boolean", "CompleteIncomplete"))) {
                        if (in_array(strtolower($temp_value), array("p", "pass", "c", "complete", "true", "t")) !== false || (($assessment["handler"] != "Boolean" || !$grade_threshold) && (is_numeric($temp_value)))) {
                            if ((int)$temp_value) {
                                $temp_value = 100;
                            } else {
                                $temp_value = 0;
                            }
                            $valid_value = true;
                        } elseif (is_numeric($temp_value) && $assessment["handler"] == "Boolean" && $grade_threshold) {
                            $valid_value = true;
                            if (((int)$temp_value) >= $grade_threshold) {
                                $temp_value = 100;
                            } else {
                                $temp_value = 0;
                            }
                        }
                        $assessment["numeric_grade_points_total"] = 100;
                    } elseif ($assessment["handler"] == "Percentage") {
                        $assessment["numeric_grade_points_total"] = 100;
                    }
                    if ((!in_array($assessment["handler"], array("Boolean", "CompleteIncomplete")) || ((int)$temp_value)|| $temp_value === 0) && $temp_value <= $assessment["numeric_grade_points_total"] && (((string)trim($preserved_input)) == ((string)$temp_value) || $temp_value || $temp_value === 0)) {
                        $PROCESSED["value"] = get_storage_grade($temp_value, $assessment);
                        $valid_value = true;
                    }
                    if ($stud_num && isset($temp_value) && ($temp_value || $temp_value === false || $temp_value === 0. || $temp_value === 0)) {
                        $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `number` = ".$db->qstr($stud_num);

                        $user = $db->GetRow($query);
                        if ($user) {
                            $query = "SELECT * FROM `group_members` WHERE `group_id` = ".$db->qstr($GROUP)." AND `proxy_id` = ".$db->qstr($user["id"])." AND `member_active` = '1'";
                            $member = $db->GetRow($query);
                            if ($member) {
                                $PROCESSED["proxy_id"] = $member["proxy_id"];
                                $member_found = true;
                            }
                            if ($member_found) {
                                if ($valid_value) {
                                    $query = "SELECT * FROM `assessment_grades` WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)." AND `proxy_id` = ".$db->qstr($member["proxy_id"]);
                                    $grade = $db->GetRow($query);

                                    if (isset($assessment["grade_threshold"]) && $PROCESSED["value"] < $assessment["grade_threshold"]) {
                                        $PROCESSED["threshold_notified"] = 0;
                                    }

                                    if ($grade) {
                                        $db->AutoExecute("assessment_grades",$PROCESSED, "UPDATE", "`grade_id`=".$db->qstr($grade["grade_id"]));
                                    } else {
                                        $db->AutoExecute("assessment_grades",$PROCESSED,"INSERT");
                                    }
                                } else {
                                    echo "<input type=\"hidden\" value=\"".html_encode($preserved_input)."\" name=\"error_grades[".$PROCESSED["proxy_id"]."]\" />\n";
                                    if (!has_error()) {
                                        add_error("Not all of the provided grade values match the requirements for this assessment.");
                                    }
                                }
                            } else {
                                add_error("Student on line ".$key." is not registered in the class.");
                            }
                        } else {
                            add_error("Student on line ".$key." is not registered in the system.");
                        }
                    } else {
                        add_error("Invalid data on line ".$key.":".$line.".");
                    }
                }
                echo "</form>";
                if (!DEMO_MODE) {
                    add_success("Successfully updated <strong>Gradebook</strong>. You will now be redirected to the <strong>Grade Assessment</strong> page for <strong>".$ASSESSMENT_NAME. "</strong>. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"#\" onclick=\"$('errorForm').submit()\" style=\"font-weight: bold\">click here</a> to continue now.");
                } else {
                    add_success("Entrada is in demo mode therefore the Entrada demo grade file was used for this import instead of the file you attempted to import. You will now be redirected to the <strong>Grade Assessment</strong> page for <strong>".$ASSESSMENT_NAME. "</strong>. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"#\" onclick=\"$('errorForm').submit()\" style=\"font-weight: bold\">click here</a> to continue now.");
                }
                $COURSE_ID = (int)$_GET["id"];
                if (!has_error()) {
                    if (isset($_GET["assignment_id"]) && $ASSIGNMENT_ID = (int)$_GET["assignment_id"]) {
                        $url = ENTRADA_URL."/admin/gradebook/assignments?section=grade&id=".$COURSE_ID."&assignment_id=".$ASSIGNMENT_ID;
                    } else {
                        $url = ENTRADA_URL."/admin/gradebook/assessments?section=grade&id=".$COURSE_ID."&assessment_id=".$ASSESSMENT_ID;
                    }
                    if (!DEMO_MODE) {
                        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                    } else {
                        // Longer success message explaining demo mode so allow the user the time to read it before redirecting
                        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 10000)";
                    }
                } else {
                    $ONLOAD[] = "setTimeout(\"$('errorForm').submit()\", 5000)";
                }
            } else {
                add_error("Invalid <strong>Assessment ID</strong> provided. You will now be redirected to the <strong>Gradebook Index</strong>. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue now.");
            }
        }

    } else {
        $url = ENTRADA_URL."/admin/gradebook";
        add_error("<strong>Assessment ID</strong> is required. You will now be redirected to the <strong>Gradebook Index</strong>. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue now.");
	}
	if($ERROR){
		echo display_error();
	}
	if($SUCCESS){
		echo display_success();
	}
}