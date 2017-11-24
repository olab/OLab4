<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete a particular html document within a folder of a community.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}
if ($RECORD_ID) {
    $share_id_active = false;
    if (isset($_GET['current_share_id'])) {
        $current_share_id = (int)$_GET['current_share_id'];
    }
    
    if (isset($_GET['share_id'])) {
        $share_id = (int)$_GET['share_id'];
    }
    Zend_Debug::dump($current_share_id);
    Zend_Debug::dump($share_id);

	if ($share_id != 0) {
		$query			= "	SELECT cs.*, cp.`page_url`
							FROM `community_shares` AS cs
                            LEFT JOIN `community_pages` AS cp
                            ON cs.`cpage_id` = cp.`cpage_id`
							WHERE  cs.`community_id` = " .$db->qstr($COMMUNITY_ID) . "
                            AND cs.`cshare_id` = " . $db->qstr($RECORD_ID);
		$folder_record	= $db->GetRow($query);
		if ($folder_record) {
            if ($share_id === $RECORD_ID) {
                header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$folder_record["page_url"]);
                exit;
            }
            
            if ((int) $folder_record["folder_active"]) {
                if ($db->AutoExecute("community_shares", array("parent_folder_id" => $share_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
                    communities_log_history($COMMUNITY_ID, $PAGE_ID, $share_id, "community_history_move_folder", true, $RECORD_ID);
                    add_statistic("community:".$COMMUNITY_ID.":shares", "folder_move", "cshare_id", $RECORD_ID);
                    $db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
                } else {
                    application_log("error", "Failed to move [".$RECORD_ID."] folder document to folder. Database said: ".$db->ErrorMsg());
                }
            } else {
                application_log("error", "The provided folder id [".$RECORD_ID."] is deactivated.");
            }

            header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$folder_record["page_url"]."?section=view-folder&id=".$current_share_id);
            exit;

		} else {
			application_log("error", "The provided folder id [".$RECORD_ID."] was invalid.");
		}
	} else if($share_id === 0) {
		$query			= "	SELECT cs.*, cp.`page_url`
							FROM `community_shares` AS cs
                            LEFT JOIN `community_pages` AS cp
                            ON cs.`cpage_id` = cp.`cpage_id`
							WHERE  cs.`community_id` = " .$db->qstr($COMMUNITY_ID) . "
                            AND cs.`cshare_id` = " . $db->qstr($RECORD_ID);
		$folder_record	= $db->GetRow($query);
		if ($folder_record) {
            if ($share_id === $RECORD_ID) {
                header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$folder_record["page_url"]);
                exit;
            }
            if ($db->AutoExecute("community_shares", array("parent_folder_id" => $share_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
                communities_log_history($COMMUNITY_ID, $PAGE_ID, $share_id, "community_history_move_folder", true, $RECORD_ID);
                add_statistic("community:".$COMMUNITY_ID.":shares", "folder_move", "cshare_id", $RECORD_ID);
                $db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
            } else {
                application_log("error", "Failed to move [".$RECORD_ID."] folder document to folder. Database said: ".$db->ErrorMsg());
            }
            if (isset($current_share_id) && $current_share_id != "") {
                header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$folder_record["page_url"]."?section=view-folder&id=".$current_share_id);
            } else {
                header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$folder_record["page_url"]);
            }
            exit;
        }
    } else {
        application_log("error", "The provided folder id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No folder id was provided for moving.");
}
exit;
?>