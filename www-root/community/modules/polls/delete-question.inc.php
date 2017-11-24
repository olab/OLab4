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
	$query			= "SELECT * FROM `community_polls_questions` WHERE `cpquestion_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `question_active` = '1'";
	$question_record	= $db->GetRow($query);
	if ($question_record) {
		$poll_id = $db->GetOne("SELECT `cpolls_id` FROM `community_polls_questions` WHERE `cpquestion_id` = ".$db->qstr($RECORD_ID));
		if (polls_module_access($RECORD_ID, "delete-question")) {
			if (!$db->AutoExecute("community_polls_questions", array("question_active" => 0, "question_order" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cpquestion_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cpolls_id` = ".$db->qstr($poll_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
				application_log("error", "Failed to remove [".$RECORD_ID."] question from poll. Database said: ".$db->ErrorMsg());
			}else{
				add_statistic("community_polling", "question_delete", "cpquestion_id", $RECORD_ID);
			}
		}
	} else {
		application_log("error", "The provided question id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No question id was provided for deactivation.");
}
header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-poll&id=".$poll_id);
exit;
?>
