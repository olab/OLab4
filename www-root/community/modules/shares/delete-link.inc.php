<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete a particular file within a folder of a community.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query			= "
					SELECT a.*
					FROM `community_share_links` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					WHERE a.`cslink_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID);
	$file_record	= $db->GetRow($query);
	if ($file_record) {
		if ((int) $file_record["link_active"]) {
			if (shares_link_module_access($RECORD_ID, "delete-link")) {
				if ($db->AutoExecute("community_share_links", array("link_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cslink_id` = ".$db->qstr($RECORD_ID)." AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
					add_statistic("community:".$COMMUNITY_ID.":shares", "link_delete", "cslink_id", $RECORD_ID);
					$db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] link from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided link id [".$RECORD_ID."] is already deactivated.");
		}

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$file_record["cshare_id"]);
		exit;
	} else {
		application_log("error", "The provided link id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No link id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>