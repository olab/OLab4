<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete an existing folder within a community. This action is
 * available only to community administrators.
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
	$query			= "SELECT * FROM `community_shares` WHERE `cshare_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$folder_record	= $db->GetRow($query);
	if ($folder_record) {
		if ((int) $folder_record["folder_active"]) {
			if ($db->AutoExecute("community_shares", array("folder_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
				@$db->AutoExecute("community_share_files", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID));
				@$db->AutoExecute("community_share_file_versions", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID));
				@$db->AutoExecute("community_share_comments", array("comment_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID));
				
				communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
				add_statistic("community:".$COMMUNITY_ID.":shares", "folder_delete", "cshare_id", $RECORD_ID);
				$db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
			} else {
				application_log("error", "Failed to deactivate [".$RECORD_ID."] shared folder from community. Database said: ".$db->ErrorMsg());
			}
		} else {
			application_log("error", "The provided shared folder record [".$RECORD_ID."] is already deactivated.");
		}
	} else {
		application_log("error", "The provided shared folder record [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No shared folder record was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>