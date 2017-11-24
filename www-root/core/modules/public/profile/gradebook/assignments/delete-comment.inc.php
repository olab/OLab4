<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete comments on a file within a folder. This action may be used by
 * either the original comment poster or by any community administrator.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 * 
*/

if (!defined("IN_PUBLIC_ASSIGNMENTS")) {
	exit;
}
if (isset($_GET["acomment_id"]) && ($tmp_input = clean_input($_GET["acomment_id"], array("nows", "int")))) {
	$ACOMMENT_ID = $tmp_input;
} else {
	$ACOMMENT_ID = 0;
}
$ASSIGNMENT_ID = false;
if ($ACOMMENT_ID) {
	$query			= "SELECT a.*, b.`course_id` FROM `assignment_comments` AS a
	                    JOIN `assignments` AS b
	                    ON a.`assignment_id` = b.`assignment_id`
	                    WHERE a.`acomment_id` = ".$db->qstr($ACOMMENT_ID)."
	                    AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
	                    AND b.`assignment_active` = '1'";
			
	$comment_record	= $db->GetRow($query);
	if ($comment_record) {
		$ASSIGNMENT_ID = $comment_record["assignment_id"];
		if ((int) $comment_record["comment_active"]) {
			if ($comment_record["proxy_id"] === $ENTRADA_USER->getID()) {
				if ($db->AutoExecute("assignment_comments", array("comment_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`acomment_id` = ".$db->qstr($ACOMMENT_ID))) {
					delete_notifications("assignments:assignment_comment:$ACOMMENT_ID");
					add_statistic("assignment:".$comment_record["assignment_id"], "comment_delete", "acomment_id", $ACOMMENT_ID);
					
				} else {
					application_log("error", "Failed to deactivate [".$ACOMMENT_ID."] comment from assignment. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided assignment comment id [".$ACOMMENT_ID."] is already deactivated.");
		}
		
		if (isset($_GET["returnto"]) && $return = clean_input($_GET["returnto"],array("trim","notags"))) {
			switch($return){
				case 'grade':
					header("Location: ".ENTRADA_URL."/admin/gradebook/assignments?section=grade&id=".$comment_record["course_id"]."&assignment_id=".$ASSIGNMENT_ID);
					exit;
			}
		} else {
			$query = "SELECT a.*
                      FROM `assignments` AS a
                      JOIN `assignment_files` AS b
                      ON b.`assignment_id`=a.`assignment_id`
                      WHERE b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
                      AND a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID);
			if ($db->GetRow($query)) {
				header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$ASSIGNMENT_ID);
                exit;
			} else {
				$query = "SELECT a.*
                          FROM `assignments` AS a 
                          JOIN `assignment_contacts` AS b 
                          ON a.`assignment_id` = b.`assignment_id` 
                          WHERE b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
                          AND a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID);
				if ($assignment_record = $db->GetRow($query)) {
					header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$ASSIGNMENT_ID."&pid=".$assignment_record["proxy_id"]);
                    exit;
                }
			}
		}
		exit;
	} else {
		application_log("error", "The provided assignment comment id [".$ACOMMENT_ID."] was invalid.");
	}
} else {
	application_log("error", "No file comment id was provided for deactivation.");
}
if ($ASSIGNMENT_ID) {
	header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=view&amp;assignment_id=".$ASSIGNMENT_ID);
} else {
	header("Location: ".ENTRADA_URL."/profile/gradebook/assignments");	
}
exit;
