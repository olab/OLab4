<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete comments on a file within a folder. This action may be used by
 * either the original comment poster or by any community administrator.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query			= "	SELECT * FROM `community_share_comments` as a
						LEFT JOIN `community_shares` as b
						ON a.`cshare_id` = b.`cshare_id`
	 					WHERE a.`cscomment_id` = ".$db->qstr($RECORD_ID)." 
	 					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
	 					AND b.`cpage_id` = ".$db->qstr($PAGE_ID);
	$comment_record	= $db->GetRow($query);
	if ($comment_record) {
		if ((int) $comment_record["comment_active"]) {
			if (shares_comment_module_access($RECORD_ID, "delete-comment")) {
				if ($db->AutoExecute("community_share_comments", array("comment_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cscomment_id` = ".$db->qstr($RECORD_ID)." AND `csfile_id` = ".$db->qstr($comment_record["csfile_id"])." AND `cshare_id` = ".$db->qstr($comment_record["cshare_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
					delete_notifications("shares:file_comment:$RECORD_ID");
					add_statistic("community:".$COMMUNITY_ID.":shares", "comment_delete", "cscomment_id", $RECORD_ID);
					
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] file comment from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided file comment id [".$RECORD_ID."] is already deactivated.");
		}

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$comment_record["csfile_id"]);
		exit;
	} else {
		application_log("error", "The provided file comment id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No file comment id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>