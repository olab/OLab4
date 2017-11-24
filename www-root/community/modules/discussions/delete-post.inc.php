<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete discussion posts from a particular forum within a community.
 * This action can be called by either a community administrator or the
 * individual who created the discussion post.
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
	$query			= "	SELECT * FROM `community_discussion_topics` as a
						LEFT JOIN `community_discussions` as b
						ON a.`cdiscussion_id` = b.`cdiscussion_id`
						WHERE a.`cdtopic_id` = ".$db->qstr($RECORD_ID)." 
						AND b.`cpage_id` = ".$db->qstr($PAGE_ID)." 
						AND a.`community_id` = ".$db->qstr($COMMUNITY_ID);
	$topic_record	= $db->GetRow($query);
	if ($topic_record) {
		if ((int) $topic_record["topic_active"]) {

            if (discussion_topic_module_access($RECORD_ID, "delete-post")) {
				/**
				 * Get a list of all replies, so we can deactivate those in
				 * history as well.
				 */
				$topic_ids		= array();
				$topic_ids[]	= $RECORD_ID;
				$query			= "SELECT `cdtopic_id` FROM `community_discussion_topics` WHERE `cdtopic_parent` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
				$results		= $db->GetAll($query);
				if ($results) {
					foreach($results as $result) {
						if ($topic_id = (int) $result["cdtopic_id"]) {
							$topic_ids[] = $topic_id;
						}
					}
				}
				if ($db->AutoExecute("community_discussion_topics", array("topic_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "(`cdtopic_id` = ".$db->qstr($RECORD_ID)." OR `cdtopic_parent` = ".$db->qstr($RECORD_ID).") AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
					communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
					add_statistic("community:".$COMMUNITY_ID.":discussions", "post_delete", "cdtopic_id", $RECORD_ID);
					delete_notifications("discussion:discuss_reply:$RECORD_ID");
				} else {
					application_log("error", "Failed to deactivate [".$RECORD_ID."] discussion forum from community. Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "The provided discussion post id [".$RECORD_ID."] is already deactivated.");
		}

		if ((int) $topic_record["cdtopic_parent"]) {
			header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record["cdtopic_parent"]);
		} else {
			header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$topic_record["cdiscussion_id"]);
		}
		exit;
	} else {
		application_log("error", "The provided discussion post id [".$RECORD_ID."] was invalid.");
	}
} else {
	application_log("error", "No discussion post id was provided for deactivation.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>