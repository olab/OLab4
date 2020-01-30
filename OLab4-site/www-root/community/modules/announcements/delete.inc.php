<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete announcements from a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_ANNOUNCEMENTS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {

	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$PAGE_CURRENT = (int) trim($_GET["pv"]);
	} else {
		$PAGE_CURRENT = 0;
	}

	$query					= "	SELECT * FROM `community_announcements` 
								WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
								AND `cpage_id` = ".$db->qstr($PAGE_ID)." 
								AND `announcement_active` = '1'
								AND `cannouncement_id` = ".$db->qstr($RECORD_ID);
	$announcement_record	= $db->GetRow($query);
	if ($announcement_record) {
		$query	= "	UPDATE `community_announcements`
					SET `announcement_active` = 0
					WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
					AND `cpage_id` = ".$db->qstr($PAGE_ID)." 
					AND `cannouncement_id` = ".$db->qstr($RECORD_ID)." LIMIT 1";
		if ($db->Execute($query)) {
			if ($COMMUNITY_ADMIN && $announcement_record["pending_moderation"] == 1 && $PAGE_OPTIONS["moderate_posts"] == 1) {
				community_notify($COMMUNITY_ID, $RECORD_ID, "announcement_delete", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$RECORD_ID, $COMMUNITY_ID, $announcement_record["release_date"]);	
			}
			communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
			add_statistic("community:".$COMMUNITY_ID.":announcements", "delete", "cannouncement_id", $RECORD_ID);
			delete_notifications('announcement:'.$announcement_record["cannouncement_id"]);
		} else {
			application_log("error", "Failed to delete [".$RECORD_ID."] announcement from community. Database said: ".$db->ErrorMsg());
		}
	} else {
		application_log("error", "The provided announcement record [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No announcement record was provided for deletion.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL.(($PAGE_CURRENT) ? "?pv=".$PAGE_CURRENT : ""));
exit;
?>