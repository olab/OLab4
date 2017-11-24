<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete an existing poll from a community. This action is available
 * only to community administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_polls` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$poll_record	= $db->GetRow($query);
	if ($poll_record) {
		if ((int) $poll_record["poll_active"]) {
			if (polls_module_access($RECORD_ID, "delete-poll")) {
				if ($db->AutoExecute("community_polls", array("poll_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cpolls_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
					add_statistic("community_polling", "poll_delete", "cpolls_id", $RECORD_ID);
					$db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID));
					if ($poll_record[poll_notifications]) {  // Delete pending notifications
						delete_notifications('polls:'.$poll_record["cpolls_id"]);
					}
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] poll from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided poll id [".$RECORD_ID."] is already deactivated.");
		}
		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	} else {
		application_log("error", "The provided poll id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No poll id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>
