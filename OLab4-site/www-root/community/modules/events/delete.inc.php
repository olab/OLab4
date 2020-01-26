<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Removes events from the community events calendar.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_EVENTS"))) {
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
	
	$query			= "	SELECT * FROM `community_events` 
						WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
						AND `cpage_id` = ".$db->qstr($PAGE_ID)." 
						AND `event_active` = '1'
						AND `cevent_id` = ".$db->qstr($RECORD_ID);
	$event_record	= $db->GetRow($query);
	if ($event_record) {
		if ($db->AutoExecute("community_events", array("event_active" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `event_active` = '1' AND `cevent_id` = ".$db->qstr($RECORD_ID))) {
			communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
			delete_notifications("event:".$event_record["cevent_id"]);
			add_statistic("community:".$COMMUNITY_ID.":events", "delete", "cevent_id", $RECORD_ID);
		} else {
			application_log("error", "Failed to delete [".$RECORD_ID."] event from community. Database said: ".$db->ErrorMsg());
		}
	} else {
		application_log("error", "The provided event record [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No event record was provided for deletion.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL.(($PAGE_CURRENT) ? "?pv=".$PAGE_CURRENT : ""));
exit;
?>