<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete a particular file within a folder of a community.
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
    
    $query			= "
                        SELECT a.*, b.`cdtopic_parent`
                        FROM `community_discussions_files` AS a
                        LEFT JOIN `community_discussion_topics` AS b
                        ON a.`cdtopic_id` = b.`cdtopic_id`
                        WHERE a.`cdfile_id` = ".$db->qstr($RECORD_ID)."
                        AND a.`community_id` = ".$db->qstr($COMMUNITY_ID);
    $file_record	= $db->GetRow($query);
	
    if (discussion_topic_module_access($file_record["cdtopic_id"], "edit-post")){
        
        if ($file_record) {
            if ((int) $file_record["file_active"]) {
                if ($db->AutoExecute("community_discussions_files", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cdfile_id` = ".$db->qstr($RECORD_ID)." AND `cdtopic_id` = ".$db->qstr($file_record["cdtopic_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
                    @$db->AutoExecute("community_discussion_file_versions", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cdfile_id` = ".$db->qstr($RECORD_ID)." AND `cdtopic_id` = ".$db->qstr($file_record["cdtopic_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID));

                    communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
                    add_statistic("community:".$COMMUNITY_ID.":discussions", "file_delete", "cdfile_id", $RECORD_ID);
                    $db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `module_id` = ".$db->qstr($MODULE_ID)." AND `record_id` = ".$db->qstr($RECORD_ID));
                } else {
                    application_log("error", "Failed to deactivate [".$RECORD_ID."] file from community. Database said: ".$db->ErrorMsg());
                }
            } else {
                application_log("error", "The provided file id [".$RECORD_ID."] is already deactivated.");
            }
            header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$file_record["cdtopic_parent"]);
            exit;
        } else {
            application_log("error", "The provided file id [".$RECORD_ID."] was invalid.");
        }
    }
} else {
	application_log("error", "No file id was provided for deactivation.");
}
header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$file_record["cdtopic_id"]);

exit;
?>