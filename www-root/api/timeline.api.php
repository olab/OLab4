<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 * 
 * $Id: timeline.api.php 1103 2010-04-05 15:20:37Z simpson $
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $search_query = "";
    $search_org = 0;
    $search_cohort = 0;
    $search_academic_year = 0;

	/**
	 * The query that is actually be searched for.
	 */
	if (isset($_GET["q"]) && ($tmp_input = clean_input($_GET["q"]))) {
		$search_query = $tmp_input;
	}

	/**
	 * Check if c variable is set for Class of.
	 */
	if (isset($_GET["c"]) && ($tmp_input = clean_input($_GET["c"], array("nows", "int")))) {
		$search_cohort = $tmp_input;
	}

	/**
	 * Check if o variable is set for Organisation
	 */
	if (isset($_GET["o"]) && ($tmp_input = clean_input($_GET["o"], array("nows", "int")))) {
		$search_org = $tmp_input;
	}

	/**
	 * Check if y variable is set for Academic year.
	 */
	if (isset($_GET["y"]) && ($tmp_input = clean_input($_GET["y"], array("nows", "int")))) {
		$search_academic_year = $tmp_input;
    }

    /**
     * Check if course_select variable is set for Course.
     */
    if (isset($_GET["course_select"]) && ($tmp_input = clean_input($_GET["course_select"], array("nows", "int")))) {
        $search_course = $tmp_input;
    }

    /**
     * Check if unit_select variable is set for Course.
     */
    if (isset($_GET["unit_select"]) && ($tmp_input = clean_input($_GET["unit_select"], array("nows", "int")))) {
        $search_unit = $tmp_input;
    }

	/**
	 * Determine filter tag ID's
	 */
	if (isset($_GET["filter_tag"]) && ($tmp_input = clean_input($_GET["filter_tag"], array("strip", "notags")))) {
		if ($_GET["filter_tag"]) {
			$filter_tag_ids = explode(",", $_GET["filter_tag"]);
		} else {
			$filter_tag_ids = array();
		}
	} else {
		$filter_tag_ids = array();
	}

	/**
	 * Determine search filters
	 */
	$search_filters = array();
	if ((isset($_GET["search_filters"])) && (is_array($_GET["search_filters"]))) {
		foreach ($_GET["search_filters"] as $search_filter_field => $search_filter) {
			if (is_array($search_filter)) {
				foreach ($search_filter as $search_filter_operator => $search_filter_values) {
					if (is_array($search_filter_values)) {
						foreach ($search_filter_values as $search_filter_value) {
							$tmp_input_field = clean_input($search_filter_field, array("strip", "notags"));
							$tmp_input_operator = clean_input($search_filter_operator, array("strip", "notags"));
							$tmp_input_value = clean_input($search_filter_value, array("strip", "notags"));
							$search_filters[$tmp_input_field][$tmp_input_operator][] = $tmp_input_value;
						}
					}
				}
			}
		}
	}

	header("Content-Type: text/xml; charset=".DEFAULT_CHARSET);
	echo "<?xml version=\"1.0\" encoding=\"".DEFAULT_CHARSET."\" ?>\n";
	
	echo "<data>\n";
	if ($search_query || $search_filters || $filter_tag_ids) {
		$queries = Entrada_Curriculum_Search::prepare($search_query, $search_org, $search_cohort, $search_academic_year, true, false, $search_filters, $filter_tag_ids, $search_course, $search_unit);

        if ($queries) {
            $query = $queries["search"];

            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $key => $result) {
                    /*
                    echo "\t<event start=\"" . date("M j Y H:i:s \G\M\T", $result["event_start"]) . "\" title=\"" . html_encode($result["event_title"]) . "\">\n";
                    */
                    if (!empty($result["cunit_id"])) {
                        $course_unit = Models_Course_Unit::fetchRowByID($result["cunit_id"]);
                        $unit_name = $course_unit->getUnitText();
                        $unit_url = utf8_encode(html_entity_decode(ENTRADA_URL."/courses/units?id=".$result["course_id"]."&cunit_id=".$course_unit->getID()));
                    } else {
                        $unit_name = "";
                        $unit_url = "";
                    }

                    echo "\t<event 
                        start=\"" . date("M j Y H:i:s \G\M\T", $result["event_start"]) . "\" 
                        title=\"" . html_encode($result["event_title"]) . "\"                        
                        courseName=\"".html_encode($result["course_code"].": ".$result["course_name"])."\"                        
                        unitName=\"".html_encode($unit_name)."\"
                        unitUrl=\"".html_encode($unit_url)."\"                        
                        eventDuration=\"".html_encode("Duration: ".(((int) $result["event_duration"]) ? $result["event_duration"]." minutes" : "To Be Announced"))."\"
                        eventLocation=\"".utf8_encode(html_entity_decode("Location:".($result["event_location"] ? $result["event_location"] : "To Be Announced")))."\"
                        attendanceRequired=\"".html_encode("Attendance: ".(isset($result["attendance_required"]) && ($result["attendance_required"] == 0) ? "<em>Optional</em>" :  "Required"))."\"
                        >\n";
                    echo html_encode("<a href=\"" . ENTRADA_URL . "/events?id=" . $result["event_id"] . "\">" . $result["event_title"] . "</a>");
                    echo "\t</event>\n";
                }
            }
        }
	}
	echo "</data>\n";
} else {
	application_log("error", "Timeline API accessed without valid session_id.");	
}
