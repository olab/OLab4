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
 * Primary controller file for the Events module.
 * /admin/events
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
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
require_once("Entrada/gradebook/handlers.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_GRADEBOOK",	true);
	

	// GRADEBOOK API SCRIPT STRUCTURE
	// 1 - Process incoming data. Usually we exoect a grade_id and a value to be posted, or an assessment_id and a proxy_id and a value. 
	//      - In BOTH cases assessment_id and proxy_id are REQUIRED post values so that orphaned grades can be found and dealt with redundantly.
	//        These values are posted always anyways and it makes sense to include them to ensure consistency.
	// 2 - Decide on the mode, create, update, or delete.
	//     - If a grade ID has been posted, we identify this as the grade in question.
	//     - If no grade ID has been posted, but a grade relating to the proxy_id and assessment_id can be found, we identify this grade.
	//     - If no grade ID has been posted, and no grade can be found for the proxy_id and assessment_id, we create a new grade for that pair and identify it 
	//       as the grade in question.
	// 3 - Determine marking scheme handler of assessment in question
	//     - If an exisitng grade has been previously identified, JOIN the assessment scheme handler and just use that
	//     - If no grade was identified and a new one is created, look up the assessment and find the handler.
	// 3 - Take action on the identified grade.
	// 	   - If a grade has been identified and a value sent for it, we update that grade and then send back the grade_id and the new value for javascript processing
	//     - If a grade has been identified but no value for it has been sent (or an empty value), we delete that grade and send back "-" signifying deletion.
	
	// Process incoming POST data
	
	// See if updating or creating a grade.
	if ((isset($_POST["grade_id"])) && ($tmp_input = clean_input($_POST["grade_id"], array("trim", "int")))) {
		$GRADE_ID = $tmp_input;
	}
	
	//If creating, ensure presence of an assessment id and a proxy id
	if ((isset($_POST["assessment_id"])) && ($tmp_input = clean_input($_POST["assessment_id"], array("trim", "int")))) {
		$ASSESSMENT_ID = $tmp_input;
	} else {
		echo "Error! Assessment ID needed!";
		application_log("error", "Failed to provide a valid assessment_id when trying to AJAX edit.");
		exit;
	}	
	
	//Always ensure presence of proxy ID
	if ((isset($_POST["proxy_id"])) && ($tmp_input = clean_input($_POST["proxy_id"], array("trim", "int")))) {
		$PROXY_ID = $tmp_input;
	} else {
		echo "Error! Proxy ID needed!";
		application_log("error", "Failed to provide a valid proxy_id when trying to AJAX edit.");
		exit;
	}
	
	// Always require a new grade value, that is what is being updated.
	if (isset($_POST["value"])) {
		$tmp_input = clean_input($_POST["value"], array("trim"));
		if(isset($tmp_input) && $tmp_input != "") {
			$grade_value = $tmp_input;
		} else {
			if(isset($GRADE_ID)) {
				//Empty grade value posted with a grade ID, delete the grade.
				$mode = "delete";
			} else {
				echo "-";
				exit;
			}
		}
	} else {
		if(isset($GRADE_ID)) {
			//Empty grade value posted with a grade ID, delete the grade.
			$mode = "delete";
		} else {
			echo "-";
			exit;
		}
	}
	
	// Find the grade or assessment being modified in the system.
	if(isset($GRADE_ID)) {
		$query = "  SELECT a.*, b.*, c.`handler`, d.`organisation_id`
		            FROM `assessment_grades` AS a
                    JOIN `assessments` as b
                    ON a.`assessment_id` = b.`assessment_id`
                    AND b.`active` = '1'
                    LEFT JOIN `assessment_marking_schemes` as c
                    ON b.`marking_scheme_id` = c.`id`
                    LEFT JOIN `courses` as d
                    ON b.`course_id` = d.`course_id`
					WHERE a.`grade_id` = ".$db->qstr($GRADE_ID);
		$grade = $db->GetRow($query);
		if(isset($grade) && is_array($grade) && (count($grade) >= 1)) {
			$assessment = &$grade;
			if(!isset($mode)) {
				$mode = "update";
			}
		} else {
			echo "Error! Grade not found.";
			application_log("error", "Failed to provide a valid grade identifier when trying to AJAX edit.");
			exit;
		}
	} else if(isset($PROXY_ID) && isset($ASSESSMENT_ID)) {
		// Find a grade not by ID but by proxy_id and assessment_id pair. Redundant fallback, may not succeed
		$query = "  SELECT a.*, b.*, c.`handler`, d.`organisation_id` FROM `assessment_grades` AS a
					JOIN `assessments` as b
					ON a.`assessment_id` = b.`assessment_id`
					AND b.`active` = '1'
					LEFT JOIN `assessment_marking_schemes` as c
					ON b.`marking_scheme_id` = c.`id`
					LEFT JOIN `courses` as d
					ON b.`course_id` = d.`course_id`
					WHERE a.`assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
					AND a.`proxy_id` = ".$db->qstr($PROXY_ID);
		$grade = $db->GetRow($query);
		if(isset($grade) && is_array($grade) && (count($grade) >= 1)) {
			// Found grade without proxy ID 
			$assessment = &$grade;
			if(!isset($mode)) {
				$mode = "update";
			}
		}
	}
	
	if(!isset($mode)) {
		// No grades found, create one for this proxy_id and assessment_id pair.
		$mode = "create";
	}
	
	// If we're creating a grade assessment wont be set yet, find the assessment in the system so we know the marking scheme handler.
	if(!isset($assessment)) {
		$query = "  SELECT a.*, b.`handler`, c.`organisation_id` FROM `assessments` as a
					LEFT JOIN `assessment_marking_schemes` as b
					ON a.`marking_scheme_id` = b.`id`
					LEFT JOIN `courses` as c
					ON a.`course_id` = c.`course_id`
					WHERE a.`assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
					AND a.`active` = '1'";
		$assessment = $db->GetRow($query);
		if(!isset($assessment) || !is_array($assessment) || !(count($assessment) >= 1)) {
			echo "Error! Assessment not found.";
			application_log("error", "Failed to provide a valid assessment identifier when trying to AJAX edit.");
			exit;
		}
	}
	
	if(!$ENTRADA_ACL->amIAllowed(new GradebookResource($assessment["course_id"], $assessment["organisation_id"]), "update")) {
		echo "Permissions Error!";
		application_log("error", "User tried to edit grades for an assessment without permission.");
		exit;
	}
	// Format grade value for insertion or update. If it comes back as blank, then delete.
	$GRADE_VALUE = get_storage_grade($grade_value, $assessment);
	
	
	// Grade or assessment has been found if it has been specified, 
	// Delete an exisiting grade if it was cleared
	if($mode == "delete" || $GRADE_VALUE === "") {
		$query = "DELETE FROM `assessment_grades` WHERE `assessment_grades`.`proxy_id` = ".$db->qstr($grade["proxy_id"])." AND `assessment_grades`.`assessment_id` = ".$db->qstr($grade["assessment_id"]);
		if($db->Execute($query)) {
			echo "-";
		} else {
			echo "Error! Grade not deleted.";
			application_log("error", "Failed to delete grade when AJAX editing. DB said [".$db->ErrorMsg()."]");
		}
	} else {
		
		// If a grade was specified in the request (update mode), update it.
		if($mode == "update") {
			$grade["value"] = $GRADE_VALUE;
			$mode = "UPDATE";
			$where = "`grade_id` = ".$db->qstr($grade["grade_id"]);
		} else {
			// If no grade was specified or found (create mode), insert a new one.
			$grade = array("value" => $GRADE_VALUE, "assessment_id" => $ASSESSMENT_ID, "proxy_id" => $PROXY_ID);
			$mode = "INSERT";
			$where = false;
		}
		
		$query = "SELECT `grade_threshold`
				  FROM `assessments`
				  WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
				  AND `active` = '1'";
		$result = $db->GetRow($query);

		if ($result && $GRADE_VALUE < $result["grade_threshold"]) {
			$grade["threshold_notified"] = "0";
		}
		
		if($db->AutoExecute("assessment_grades", $grade, $mode, $where)) {
			if ($mode == "UPDATE") {
				application_log("success", "Successfully updated grade for assessment_id [".$ASSESSMENT_ID."] for proxy_id [".$PROXY_ID."].");
			
				$GRADE_ID = $db->qstr($grade["grade_id"]);
			} else if($mode == "INSERT") {
				application_log("success", "Successfully entered grade for assessment_id [".$ASSESSMENT_ID."] for proxy_id [".$PROXY_ID."].");
			
				$GRADE_ID = $db->Insert_ID();
			} else {
				application_log("error", "Unknown mode for assessment_id [".$ASSESSMENT_ID."] for proxy_id [".$PROXY_ID."].");
			}

			if ($_POST["return_type"] && $_POST["return_type"] == "json") {
				echo json_encode(array(
					"grade_id" => str_replace("'", "", $GRADE_ID),
					"grade_value" => format_retrieved_grade($GRADE_VALUE, $assessment)
				));
			}
			else {
				echo $GRADE_ID."|". format_retrieved_grade($GRADE_VALUE, $assessment);
			}
		} else {
			echo "Error saving grade!";
			
			application_log("error", "Failed to save grade when AJAX editing. DB said [".$db->ErrorMsg()."]");
		}
	}

}