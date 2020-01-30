<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 * 
 * $Id: discussions.api.php 1103 2010-04-05 15:20:37Z simpson $
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
	
	if((isset($_POST["action"])) && (trim($_POST["action"]))) {
		$ACTION	= trim($_POST["action"]);
	}

	if((isset($_POST["id"])) && (clean_input($_POST["id"], array("trim", "int")))) {
		$EDISCUSSION_ID = clean_input($_POST["id"], array("trim", "int"));
	}

	switch($ACTION) {
		case "edit" :
			if($EDISCUSSION_ID) {
				$query	= "SELECT * FROM `event_discussions` WHERE `ediscussion_id` = ".$db->qstr($EDISCUSSION_ID);
				$result	= $db->GetRow($query);
				if($result) {
					if($result["proxy_id"] == $ENTRADA_USER->getID()) {
						$discussion_comment	= clean_input($_POST["discussion_comment"], array("notags", "trim"));

						if($discussion_comment == "") {
							$PROCESSED["discussion_active"]		= 0;
						} else {
							$PROCESSED["discussion_active"]		= 1;
							$PROCESSED["discussion_comment"]	= $discussion_comment;
						}
						
						$PROCESSED["updated_date"]			= time();
						$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();

						if(!$db->AutoExecute("event_discussions", $PROCESSED, "UPDATE", "ediscussion_id = ".$db->qstr($EDISCUSSION_ID))) {
							application_log("error", "Unable to update discussion id [".$EDISCUSSION_ID."]. Database said: ".$db->ErrorMsg());
						}

						if($PROCESSED["discussion_active"]) {
							echo nl2br($discussion_comment);
						} else {
							echo "<script type=\"text/javascript\">$('event_comment_".$EDISCUSSION_ID."').fade();</script>";
						}
					} else {
						application_log("error", "Someone is attempting to edit a discussion comment which they did not write.");
					}
				} else {
					application_log("error", "Unable to locate the provided discussion id [".$EDISCUSSION_ID."]. Database said: ".$db->ErrorMsg());
				}
			} else {
				application_log("notice", "There was no discussion and event ids provided to the discussions.api");
			}
		break;
		case "delete" :
			if($EDISCUSSION_ID) {
				$query	= "SELECT * FROM `event_discussions` WHERE `ediscussion_id` = ".$db->qstr($EDISCUSSION_ID);
				$result	= $db->GetRow($query);
				if($result) {
					if($result["proxy_id"] == $ENTRADA_USER->getID()) {
						$PROCESSED["discussion_active"]		= 0;
						$PROCESSED["updated_date"]			= time();
						$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();

						if(!$db->AutoExecute("event_discussions", $PROCESSED, "UPDATE", "ediscussion_id = ".$db->qstr($EDISCUSSION_ID))) {
							application_log("error", "Unable to update discussion id [".$EDISCUSSION_ID."]. Database said: ".$db->ErrorMsg());
						}

						echo "<script type=\"text/javascript\">$('event_comment_".$EDISCUSSION_ID."').fade();</script>";
					} else {
						application_log("error", "Someone is attempting to delete a discussion comment which they did not write.");
					}
				} else {
					application_log("error", "Unable to locate the provided discussion id [".$EDISCUSSION_ID."]. Database said: ".$db->ErrorMsg());
				}
			} else {
				application_log("notice", "There was no discussion and event ids provided to the discussions.api");
			}
		break;
		default :
			application_log("error", "Discussion API accessed with an unknown action [".$ACTION."].");
		break;
	}
} else {
	application_log("error", "Discussion API accessed without valid session_id.");
}
?>