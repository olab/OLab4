<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete discussion forums within a community. This action is available
 * only to community administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DISCUSSIONS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query				= "SELECT * FROM `community_discussions` WHERE `cdiscussion_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$discussion_record	= $db->GetRow($query);
	if ($discussion_record) {
		if ((int) $discussion_record["forum_active"]) {
			if ($db->AutoExecute("community_discussions", array("forum_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cdiscussion_id` = ".$db->qstr($RECORD_ID))) {
			communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
			add_statistic("community:".$COMMUNITY_ID.":discussions", "forum_delete", "cdiscussion_id", $RECORD_ID);
			} else {
				application_log("error", "Failed to deactivate [".$RECORD_ID."] discussion forum from community. Database said: ".$db->ErrorMsg());
			}
		} else {
			application_log("error", "The provided discussion forum record [".$RECORD_ID."] is already deactivated.");
		}
	} else {
		application_log("error", "The provided discussion forum record [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No discussion forum record was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>