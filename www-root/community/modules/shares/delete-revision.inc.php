<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete specific revisions of a file that was uploaded. This action
 * can be used by either the user who originally posted this revision, the user
 * who originally uploaded the first file or by any community administrator.
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
	$query			= "
					SELECT a.*, b.`file_title`, b.`file_active` AS `parent_file_active`
					FROM `community_share_file_versions` AS a
					LEFT JOIN `community_share_files` AS b
					ON a.`csfile_id` = b.`csfile_id`
					LEFT JOIN `community_shares` AS c
					ON a.`cshare_id` = c.`cshare_id`
					WHERE a.`csfversion_id` = ".$db->qstr($RECORD_ID)."
					AND c.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID);
	$file_record	= $db->GetRow($query);
	if ($file_record) {
		if (((int) $file_record["file_active"]) && ((int) $file_record["parent_file_active"])) {
			if (shares_file_module_access($file_record["csfile_id"], "delete-revision")) {
				if ($db->AutoExecute("community_share_file_versions", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`csfversion_id` = ".$db->qstr($RECORD_ID)." AND `csfile_id` = ".$db->qstr($file_record["csfile_id"])." AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					add_statistic("community:".$COMMUNITY_ID.":shares", "revision_delete", "csfversion_id", $RECORD_ID);
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] file version from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided file revision id [".$RECORD_ID."] is already deactivated.");
		}

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$file_record["csfile_id"]);
		exit;
	} else {
		application_log("error", "The provided file revision id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No file revision id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>