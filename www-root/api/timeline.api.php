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
	
	header("Content-Type: text/xml; charset=".DEFAULT_CHARSET);
	echo "<?xml version=\"1.0\" encoding=\"".DEFAULT_CHARSET."\" ?>\n";
	
	echo "<data>\n";
	if ($search_query) {
		$queries = Entrada_Curriculum_Search::prepare($search_query, $search_org, $search_cohort, $search_academic_year, true, false);

        if ($queries) {
            $query = $queries["search"];

            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $key => $result) {
                    echo "\t<event start=\"" . date("M j Y H:i:s \G\M\T", $result["event_start"]) . "\" title=\"" . html_encode($result["event_title"]) . "\">\n";
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
