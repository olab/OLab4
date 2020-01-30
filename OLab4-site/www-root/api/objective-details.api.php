<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 * 
 * $Id: discussions.api.php 1079 2010-03-26 17:20:07Z simpson $
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

if((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	$ACTION			= "";
	$EDISCUSSION_ID	= 0;
	$PROCESSED		= array();

	if((isset($_POST["id"])) && (clean_input($_POST["id"], array("trim", "int")))) {
		$OBJECTIVE_ID = clean_input($_POST["id"], array("trim", "int"));
	}

	if(((isset($_POST["cids"])) && (count(explode(",", $_POST["cids"])))) || ((isset($_POST["cids"])) && (clean_input($_POST["cids"], array("trim", "int"))))) {
		$COURSE_IDS = clean_input($_POST["cids"], array("trim"));
	}
	
	if($OBJECTIVE_ID && $COURSE_IDS) {
		$query	= "SELECT * FROM `course_objectives` WHERE `objective_id` = ".$db->qstr($OBJECTIVE_ID)." AND `course_id` IN (".$COURSE_IDS.")";
		$result	= $db->GetRow($query);
		if($result) {
			if(!$ENTRADA_ACL->amIAllowed('coursecontent', 'update ', false)) {
				$objective_details	= clean_input($_POST["objective_details"], array("notags", "trim"));

				if($objective_details) {
					$PROCESSED["objective_details"]	= $objective_details;
				} else {
					$PROCESSED["objective_details"] = "";
				}
				
				$PROCESSED["updated_date"]			= time();
				$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();

				if(!$db->AutoExecute("course_objectives", $PROCESSED, "UPDATE", "`objective_id` = ".$db->qstr($OBJECTIVE_ID)." AND `course_id` IN (".$COURSE_IDS.")")) {
					application_log("error", "Unable to update objective id [".$OBJECTIVE_ID."] in course ids [".$COURSE_IDS."]. Database said: ".$db->ErrorMsg());
				}

				if($PROCESSED["objective_details"]) {
					echo $objective_details;
				} else {
					$query = "	SELECT `objective_description` FROM `global_lu_objectives`
								WHERE `objective_id` = ".$db->qstr($OBJECTIVE_ID);
					$original_objective_details = $db->GetOne($query);
					echo $original_objective_details;
				}
			} else {
				application_log("error", "Someone is attempting to edit objective details which they do not have access to.");
			}
		} else {
			if(!$ENTRADA_ACL->amIAllowed('coursecontent', 'update ', false)) {
				$objective_details	= clean_input($_POST["objective_details"], array("notags", "trim"));

				if($objective_details) {
					$PROCESSED["objective_details"]	= $objective_details;
				}
				
				$PROCESSED["updated_date"]			= time();
				$PROCESSED["objective_id"]			= $OBJECTIVE_ID;
				$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
				if ((count(explode(",", $COURSE_IDS)))) {
					$COURSE_IDS = explode(",", $COURSE_IDS);
					foreach ($COURSE_IDS as $COURSE_ID) {
						$PROCESSED["course_id"]		= clean_input($COURSE_ID,"int");
						if(!$db->AutoExecute("course_objectives", $PROCESSED, "INSERT")) {
							application_log("error", "Unable to update objective id [".$OBJECTIVE_ID."] in course id [".$COURSE_ID."]. Database said: ".$db->ErrorMsg());
						}
					}
				} else {					
						$PROCESSED["course_id"]		= clean_input($COURSE_IDS,"int");
						if(!$db->AutoExecute("course_objectives", $PROCESSED, "INSERT")) {
							application_log("error", "Unable to update objective id [".$OBJECTIVE_ID."] in course ids [".$COURSE_IDS."]. Database said: ".$db->ErrorMsg());
						}
				}

				if($PROCESSED["objective_details"]) {
					echo $objective_details;
				} else {
					$query = "	SELECT `objective_description` FROM `global_lu_objectives`
								WHERE `objective_id` = ".$db->qstr($OBJECTIVE_ID);
					$original_objective_details = $db->GetOne($query);
					echo $original_objective_details;
				}
			} else {
				application_log("error", "Someone is attempting to edit objective details which they do not have access to.");
			}
		}
	} else {
		application_log("notice", "There was no objective and course ids provided to the objective-details.api");
	}
} else {
	application_log("error", "Objective details API accessed without valid session_id.");
}
?>