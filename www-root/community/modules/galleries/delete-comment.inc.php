<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete comments that have been made to a specific photo. This is
 * available to either community administrators or to the original comment
 * poster.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_GALLERIES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query			= "	SELECT * FROM `community_gallery_comments` as a
						LEFT JOIN `community_galleries` as b
						ON a.`cgallery_id` = b.`cgallery_id`
						WHERE a.`cgcomment_id` = ".$db->qstr($RECORD_ID)." 
						AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND b.`cpage_id` = ".$db->qstr($PAGE_ID);
	
	$comment_record	= $db->GetRow($query);
	if ($comment_record) {
		if ((int) $comment_record["comment_active"]) {
			if (galleries_comment_module_access($RECORD_ID, "delete-comment")) {
				if ($db->AutoExecute("community_gallery_comments", array("comment_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cgcomment_id` = ".$db->qstr($RECORD_ID)." AND `cgphoto_id` = ".$db->qstr($comment_record["cgphoto_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
					delete_notifications("gallery:photo_comment:$RECORD_ID");
					add_statistic("community:".$COMMUNITY_ID.":galleries", "comment_delete", "cgcomment_id", $RECORD_ID);
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] photo comment from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided photo comment id [".$RECORD_ID."] is already deactivated.");
		}

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=".$comment_record["cgphoto_id"]);
		exit;
	} else {
		application_log("error", "The provided photo comment id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No photo comment id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>