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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("search", "read")) {
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$PAGE_META["title"]			= "Curriculum Explorer";
	$PAGE_META["description"]	= "Allowing you to browse the curriculum by objective set, course, and date.";
	$PAGE_META["keywords"]		= "";

	$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE."/curriculum/explorer", "title" => "Explorer");

	if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
		$MODE = "ajax";
	}
    
    if (isset($_GET["method"]) && $tmp_input = clean_input($_GET["method"], array("trim", "striptags"))) {
        $METHOD = $tmp_input;
    }

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("int"))) || (isset($_GET["objective_parent"]) && ($tmp_input = clean_input($_GET["objective_parent"], array("int"))))) {
		$PROCESSED["id"] = $tmp_input;
		$query = "SELECT * FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($PROCESSED["id"]);
		$objective_info = $db->GetRow($query);
		$objective_name = $objective_info["objective_name"];
		$objective_description = $objective_info["objective_description"];
	}

    $PROCESSED["count"] = array();
    
	if (isset($MODE) && $MODE == "ajax") {
        
        ob_clear_open_buffers();
        
        switch ($METHOD) {
            case "course_specific_objective_sets" :
                
                if (isset($_GET["course_id"]) && $tmp_input = clean_input($_GET["course_id"], "int")) {
                    $PROCESSED["course_id"] = $tmp_input;
                }
                
                $results = Entrada_Curriculum_Explorer::getCourseSpecificObjectiveSets($PROCESSED["course_id"]);
                if ($results) {
                    echo json_encode(array("status" => "success", "data" => $results));
                } else {
                    echo json_encode(array("status" => "error", "data" => array("No course specific objectives found.")));
                }
                
            break;
            default:
                
                if (!$PROCESSED["id"] && $_GET["objective_parent"] && ($tmp_input = clean_input($_GET["objective_parent"], array("int")))) {
                    $PROCESSED["objective_parent"] = $tmp_input;
                } else {
                    $PROCESSED["objective_parent"] = $PROCESSED["id"];
                }
                if ($_GET["year"] && ($tmp_input = clean_input($_GET["year"], array("int")))) {
                    $PROCESSED["year"] = $tmp_input;
                    $SEARCH_DURATION["start"]	= mktime(0, 0, 0, 9, 1, $PROCESSED["year"]);
                    $SEARCH_DURATION["end"]		= strtotime("+1 year", $SEARCH_DURATION["start"]);
                }
                if ($_GET["course_id"] && ($tmp_input = clean_input($_GET["course_id"], array("int")))) {
                    $PROCESSED["course_id"] = $tmp_input;
                }
                if ($_GET["group_id"] && ($tmp_input = clean_input($_GET["group_id"], array("int")))) {
                    $PROCESSED["group_id"] = $tmp_input;
                }
                
                if (isset($_GET["count"]) && is_array($_GET["count"])) {
                    foreach ($_GET["count"] as $count) {
                        if ($tmp_input = clean_input($count, array("trim", "striptags"))) {
                            $PROCESSED["count"][] = $tmp_input;
                        }
                    }
                }

                if (in_array("courses", $PROCESSED["count"])) {
                    $mapped_courses = Entrada_Curriculum_Explorer::getMappedCourses($PROCESSED["objective_parent"], $PROCESSED["course_id"]);
                }

                if (in_array("events", $PROCESSED["count"])) {
                    $event_objectives = Entrada_Curriculum_Explorer::getMappedEvents($PROCESSED["objective_parent"], $SEARCH_DURATION["start"], $SEARCH_DURATION["end"], $PROCESSED["course_id"], $PROCESSED["group_id"]);
                }
                
                if (in_array("assessments", $PROCESSED["count"]) && $ENTRADA_USER->getActiveGroup != "student") {
                    $assessment_objectives = Entrada_Curriculum_Explorer::getMappedAssessments($PROCESSED["objective_parent"], $SEARCH_DURATION["start"], $SEARCH_DURATION["end"], $PROCESSED["course_id"], $PROCESSED["group_id"]);
                    foreach ($assessment_objectives as $key => $assessment) {
                        $course = Models_Course::fetchRowByID($assessment["course_id"]);
                        $assessment_objectives[$key]["permission"] = $ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "read");
                    }
                }

                $child_objectives = Entrada_Curriculum_Explorer::getChildObjectives($PROCESSED["objective_parent"]);
                if (empty($child_objectives)) {
                   $child_objectives = Entrada_Curriculum_Explorer::getChildObjectives($objective_info["objective_parent"]);
                }
                if ($child_objectives) {
                    $i = 0;
                    foreach ($child_objectives as $child) {
                        if (in_array("courses", $PROCESSED["count"])) {
                            $course_count = array_sum(Entrada_Curriculum_Explorer::count_objective_child_courses($child["objective_id"]));
                        } else {
                            $course_count = 0;
                        }

                        if (in_array("events", $PROCESSED["count"])) {
                            $event_count = array_sum(Entrada_Curriculum_Explorer::count_objective_child_events($child["objective_id"], $SEARCH_DURATION["start"], $SEARCH_DURATION["end"], $PROCESSED["course_id"], $PROCESSED["group_id"]));
                        } else {
                            $event_count = 0;
                        }
                        
                        if (in_array("assessments", $PROCESSED["count"])) {
                            $assessment_count = array_sum(Entrada_Curriculum_Explorer::count_objective_child_assessments($child["objective_id"], $SEARCH_DURATION["start"], $SEARCH_DURATION["end"], $PROCESSED["course_id"], $PROCESSED["group_id"]));
                        } else {
                            $assessment_count = 0;
                        }
                        
                        if ($PROCESSED["course_id"]) {
                            if ($course_count == 0 && $event_count == 0) {
                                unset($child_objectives[$i]);
                            } else {
                                $child_objectives[$i]["course_count"]       = $course_count;
                                $child_objectives[$i]["event_count"]        = $event_count;
                                $child_objectives[$i]["assessment_count"]   = $assessment_count;
                            }
                        } else {
                            $child_objectives[$i]["course_count"]       = $course_count;
                            $child_objectives[$i]["event_count"]        = $event_count;
                            $child_objectives[$i]["assessment_count"]   = $assessment_count;
                        }

                        $i++;
                    }
                }

                $objective_parents = Entrada_Curriculum_Explorer::fetch_objective_parents($PROCESSED["objective_parent"]);
                if ($objective_parents) {
                    $flattened_objectives = flatten_array($objective_parents);
                    for ($i = 0; $i <= count($flattened_objectives); $i++) {
                        if ($i % 2 == 0 && (!empty($flattened_objectives[$i]) && ($flattened_objectives[$i] != $PROCESSED["objective_parent"] || count($objective_parents) == 2))) {
                            $o_breadcrumb[] = "<a class=\"objective-link\" href=\"".ENTRADA_RELATIVE. "/curriculum/explorer?objective_parent=".($flattened_objectives[$i+2] ? $flattened_objectives[$i+2] : 0)."&id=" . $flattened_objectives[$i]."&step=2\" data-id=\"".$flattened_objectives[$i]."\">".$flattened_objectives[$i+1]."</a>";
                        } else if ($i % 2 == 0) {
                            $o_breadcrumb[] = $flattened_objectives[$i+1];
                        }
                    }

                    if ($o_breadcrumb) {
                        $breadcrumb = implode(" / ", array_reverse($o_breadcrumb));
                    } else {
                        $breadcrumb = null;
                    }
                }

                if ($event_objectives) {
                    if (!$objective_name) {
                        $objective_name = $event_objectives[0]["objective_name"];
                        $objective_description = $event_objectives[0]["objective_description"];
                    }
                    foreach ($event_objectives as $objective) {
                        $events[$objective["course_code"] . ": " . $objective["course_name"]][] = $objective;
                    }
                }
                
                if ($assessment_objectives) {
                    foreach ($assessment_objectives as $objective) {
                        $assessments[$objective["course_code"] . ": " . $objective["course_name"]][] = $objective;
                    }
                }

                echo json_encode(array("status" => "success", "objective_parent" => $PROCESSED["objective_parent"], "events" => $events, "courses" => $mapped_courses, "assessments" => $assessments, "child_objectives" => $child_objectives, "objective_name" => $objective_name, "objective_description" => $objective_description, "breadcrumb" => $breadcrumb));

            break;
        }
        
        exit;
	}

	switch ($STEP) {
		case 2 :
			/*
			 * Objective Set ID
			 */
			if (isset($_GET["objective_parent"]) && ($tmp_input = clean_input($_GET["objective_parent"], array("int")))) {
				$PROCESSED["objective_parent"] = $tmp_input;
			}

			/*
			 * Course ID
			 */
			if (isset($_GET["course_id"]) && ($tmp_input = clean_input($_GET["course_id"], array("int")))) {
				$PROCESSED["course_id"] = $tmp_input;
			}

			/*
			 * Academic Year
			 */
			if (isset($_GET["year"]) && ($tmp_input = clean_input($_GET["year"], array("int")))) {
				$PROCESSED["year"] = $tmp_input;
				$SEARCH_DURATION["start"]	= mktime(0, 0, 0, 9, 1, $PROCESSED["year"]);
				$SEARCH_DURATION["end"]		= strtotime("+1 year", $SEARCH_DURATION["start"]);
			}

			/*
			 * Count
			 */
			if (isset($_GET["count"]) && is_array($_GET["count"])) {
                foreach ($_GET["count"] as $count) {
                    if ($tmp_input = clean_input($count, array("trim", "striptags"))) {
                        $PROCESSED["count"][] = $tmp_input;
                    }
                }
            }
			
			if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], "int")) {
				$PROCESSED["group_id"] = $tmp_input;
			}
		break;
		case 1 :
		default :
            continue;
		break;
	}

    search_subnavigation("explorer");
	?>
	<h1>Curriculum Explorer</h1>
	<form action="<?php echo ENTRADA_RELATIVE; ?>/curriculum/explorer" method="GET">
		<input type="hidden" name="step" value="2" />
		<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
			<colgroup>
				<col style=" width: 20%" />
				<col style=" width: 22%" />
				<col style=" width: 5%" />
				<col style=" width: 20%" />
				<col style=" width: 23%" />
			</colgroup>
			<tbody>
				<tr>
					<td><label for="objective-set" style="font-weight: bold; margin-right: 5px; white-space: nowrap">Curriculum Tag Set</label></td>
					<td>
                        <?php
                        $objective_sets = Entrada_Curriculum_Explorer::getObjectiveSets();
                        if ($objective_sets) {
                            ?>
                            <select id="objective-set" name="objective_parent" >
                            <?php
                            foreach ($objective_sets as $objective_id => $objective_set) {
                                ?>
                                <option class=" <?php echo $objective_set["audience_value"] != "all" ? "course-specific-objectiveset" : ""; ?>" value="<?php echo $objective_id; ?>" <?php echo ($objective_id == $PROCESSED["objective_parent"]) ? " selected=\"selected\"" : "" ; ?>><?php echo (!empty($objective_set["objective_code"]) ? $objective_set["objective_code"] . " - " : "") . $objective_set["objective_name"]; ?></option>
                                <?php
                            }
                            ?>
                            </select>
                            <?php
                        } else {
                            ?>
                            <select id="objective-set" name="objective_parent" >
                                <option value="0">Please select a course to browse mapped curriculum tag sets.</option>
                            </select>
                            <?php
                        }
                        ?>
					</td>
					<td>&nbsp;</td>
					<td><label for="year" style="font-weight: bold; margin-right: 5px; white-space: nowrap">Academic Year</label></td>
					<td>
						<select id="year" name="year" >
							<option value="0"<?php echo ((!$SEARCH_YEAR)? " selected=\"selected\"" : ""); ?>>-- All Years --</option>
							<?php
							$start_year = (fetch_first_year() - 3);
							for ($year = $start_year; $year >= ($start_year - 3); $year--) {
                                ?>
								<option value="<?php echo $year; ?>"  <?php echo ($year == $PROCESSED["year"]) ? " selected=\"selected\"" : "" ; ?>><?php echo $year ."/" . ($year + 1); ?></option>
                                <?php
                            }
                            ?>
						</select>
					</td>
				<tr>
					<td><label for="course" style="font-weight: bold; margin-right: 5px; white-space: nowrap">Course</label></td>
					<td>
						<select id="course" name="course_id" >
							<option value="0">-- All Courses --</option>
							<?php
                            $courses = Entrada_Curriculum_Explorer::getCourses();
							if ($courses) {
								foreach ($courses as $course) {
									?>
                                    <option value="<?php echo $course["course_id"]; ?>" <?php echo ($course["course_id"] == $PROCESSED["course_id"]) ? " selected=\"selected\"" : "" ; ?>><?php echo (!empty($course["course_code"]) ? $course["course_code"] . " - " : "") . $course["course_name"]; ?></option>
									<?php
								}
							}
							?>
						</select>
					</td>
					<td>&nbsp;</td>
					<td valign="top"><label for="count" style="font-weight: bold; margin-right: 5px; white-space: nowrap">Explore</label></td>
                    <td rowspan="2">
                        <label class="checkbox"><input type="checkbox" name="count[]" class="count" value="courses" <?php echo in_array("courses", $PROCESSED["count"]) || empty($PROCESSED["count"]) ? "checked=\"checked\"" : ""; ?> /> Courses</label>
                        <label class="checkbox"><input type="checkbox" name="count[]" class="count" value="events" <?php echo in_array("events", $PROCESSED["count"]) || empty($PROCESSED["count"]) ? "checked=\"checked\"" : ""; ?> /> Learning Events</label>
                        <?php if ($ENTRADA_USER->getActiveGroup() != "student") { ?>
                        <label class="checkbox"><input type="checkbox" name="count[]" class="count" value="assessments" <?php echo in_array("assessments", $PROCESSED["count"]) || empty($PROCESSED["count"]) ? "checked=\"checked\"" : ""; ?> /> Assessments</label>
                        <?php } ?>
                        
					</td>
				</tr>
				<tr>
					<td><label for="cohort" style="font-weight: bold; margin-right: 5px; white-space: nowrap">Cohort</label></td>
					<td>
						<select id="cohort" name="group_id" >
							<option value="0">-- All Cohorts --</option>
							<?php
							$cohorts = Entrada_Curriculum_Explorer::getCohorts();
							if ($cohorts) {
								foreach ($cohorts as $cohort) {
									?>
                                    <option value="<?php echo $cohort["group_id"]; ?>" <?php echo ($cohort["group_id"] == $PROCESSED["group_id"]) ? " selected=\"selected\"" : "" ; ?>><?php echo $cohort["group_name"]; ?></option>
									<?php
								}
							}
							?>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr>
			</tbody>
		</table>
        <div class="row"><input type="submit" class="btn btn-primary pull-right space-above" value="<?php echo $translate->_("Explore"); ?>" /></div>
	</form>
	<script type="text/javascript">
    var SITE_URL = "<?php echo ENTRADA_URL; ?>";
    var YEAR = "<?php echo $PROCESSED["year"]; ?>";
    var COURSE = "<?php echo $PROCESSED["course_id"]; ?>";
	var COHORT = "<?php echo $PROCESSED["group_id"]; ?>";
    var OBJECTIVE_PARENT = "<?php echo $PROCESSED["objective_parent"]; ?>";
    var COUNT = JSON.parse('<?php echo json_encode($PROCESSED["count"]); ?>');
    var current_total = 0;
	</script>
	<script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/curriculumexplorer.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>" /></script>
	<script type="text/javascript">
	jQuery(function(){
		if (location.hash.length <= 0) {
			location.hash = "id-"+OBJECTIVE_PARENT;
		}
		var id = parseInt(location.hash.substring(4, location.hash.length));
        
        jQuery.ajax({
                type: "GET",
				url: SITE_URL + "/curriculum/explorer",
                data: {"mode": "ajax", "id" : id, "year" : YEAR, "course_id": COURSE, "count[]" : COUNT, "group_id" : COHORT},
                success: function(data) {
                    data = JSON.parse(data);
                    var link = jQuery(document.createElement("a")).addClass(".objective-link").attr("data-id", "<?php echo $PROCESSED["id"]; ?>").html(data.objective_name);
                    current_total = 0;
                    jQuery.each(data.child_objectives, function (i, v) {
                        current_total = current_total + v.event_count + v.course_count + v.assessment_count;
                    });
                    renderDOM(data, link);
                    if (jQuery(".objective-link[data-id="+id+"]").length > 0) {
                        jQuery(".objective-link[data-id="+id+"]").not(".back").addClass("active");
                    }
                }
        });
	});
	</script>
	<?php
	switch ($STEP) {
		case 2 :
            ?>
			<div id="objective-breadcrumb">
				<a class="objective-link" href="#" data-id="<?php echo $PROCESSED["objective_parent"]; ?>"><?php echo $objective_sets[$PROCESSED["objective_parent"]]["objective_name"]; ?></a>
			</div>
            <div id="objective-browser">
                
                <div id="objective-list">

                </div>
                <div id="objective-container">
                    <div id="objective-details"></div>
                </div>
            </div>
            <?php
		break;
	}
}