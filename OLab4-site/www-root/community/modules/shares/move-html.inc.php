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
    if (isset($_GET['current_share_id'])) {
        $current_share_id = (int)$_GET['current_share_id'];
    }
    
	if (isset($_GET["share_id"]) && ($share_id = ((int) $_GET["share_id"]))) {
		$query			= "	SELECT a.*
							FROM `community_share_html` AS a
							LEFT JOIN `community_shares` AS b
							ON a.`cshare_id` = b.`cshare_id`
							WHERE a.`cshtml_id` = ".$db->qstr($RECORD_ID)."
							AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND a.`cshare_id` != ".$db->qstr($share_id)."
							AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
							AND b.`folder_active` = '1'";
		$file_record	= $db->GetRow($query);
		if ($file_record) {
			$query			= "	SELECT b.`page_url`, a.`folder_title`
								FROM `community_shares` AS a
								LEFT JOIN `community_pages` AS b
								ON b.`cpage_id` = a.`cpage_id`
								WHERE a.`cshare_id` = ".$db->qstr($share_id)."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`folder_active` = '1'";
			$share_record	= $db->GetRow($query);
			if ($share_record) {
				if ((int) $file_record["html_active"]) {
					if (shares_html_module_access($RECORD_ID, "move-html")) {
						if ($db->AutoExecute("community_share_html", array("cshare_id" => $share_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cshtml_id` = ".$db->qstr($RECORD_ID)." AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
							communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_move_html", true, $share_id);
							add_statistic("community:".$COMMUNITY_ID.":shares", "html_move", "cshtml_id", $RECORD_ID);
							$db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully moved <strong>%s</strong> to <strong>%s</strong>."), $file_record["html_title"], $share_record["folder_title"]), "success", $MODULE);
                        } else {
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Failed to move <strong>%s</strong> to <strong>%s</strong>."), $file_record["html_title"], $share_record["folder_title"]), "error", $MODULE);
							application_log("error", "Failed to move [".$RECORD_ID."] html document to folder. Database said: ".$db->ErrorMsg());
						}
					}
				} else {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("The provided html document <strong>%s</strong> is deactivated."), $file_record["html_title"]), "error", $MODULE);
                    application_log("error", "The provided html document id [".$RECORD_ID."] is deactivated.");
				}

                header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$share_record["page_url"]."?section=view-folder&id=".$current_share_id);
				exit;
			}
		} else {
			application_log("error", "The provided html document id [".$RECORD_ID."] was invalid.");
		}
	} else {

	}
} else {
	application_log("error", "No html document id was provided for moving.");
}

//header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>