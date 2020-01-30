<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete photo galleries from a community. This action is available
 * only to community administrators.
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
	$query			= "SELECT * FROM `community_galleries` WHERE `cgallery_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$gallery_record	= $db->GetRow($query);
	if ($gallery_record) {
		if ((int) $gallery_record["gallery_active"]) {
			if ($db->AutoExecute("community_galleries", array("gallery_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($RECORD_ID))) {
				communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
				add_statistic("community:".$COMMUNITY_ID.":galleries", "gallery_delete", "cgallery_id", $RECORD_ID);
			} else {
				application_log("error", "Failed to deactivate [".$RECORD_ID."] photo gallery from community. Database said: ".$db->ErrorMsg());
			}
		} else {
			application_log("error", "The provided photo gallery record [".$RECORD_ID."] is already deactivated.");
		}
	} else {
		application_log("error", "The provided photo gallery record [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No photo gallery record was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>