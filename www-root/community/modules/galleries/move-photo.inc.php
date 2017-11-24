<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete existing photos from a gallery of a community. This action is
 * available to either the original photo uploader or to any community
 * administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_GALLERIES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	if (isset($_GET["gallery_id"]) && ($gallery_id = ((int) $_GET["gallery_id"]))) {
		$query			= "	SELECT a.*, b.`gallery_cgphoto_id`
							FROM `community_gallery_photos` AS a
							LEFT JOIN `community_galleries` AS b
							ON a.`cgallery_id` = b.`cgallery_id`
							WHERE a.`cgphoto_id` = ".$db->qstr($RECORD_ID)."
							AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
							AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND a.`cgallery_id` != ".$db->qstr($gallery_id);
		$photo_record	= $db->GetRow($query);
		if ($photo_record) {
			$query			= "	SELECT a.`gallery_cgphoto_id`, b.`page_url`, a.`gallery_title`
								FROM `community_galleries` AS a
								LEFT JOIN `community_pages` AS b
								ON b.`cpage_id` = a.`cpage_id`
								WHERE a.`cgallery_id` = ".$db->qstr($gallery_id)."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`gallery_active` = '1'";
			$gallery_record	= $db->GetRow($query);
			if ($gallery_record) {
				if ((int) $photo_record["photo_active"]) {
					if (galleries_photo_module_access($RECORD_ID, "move-photo")) {
						if ($db->AutoExecute("community_gallery_photos", array("cgallery_id" => $gallery_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cgphoto_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
							if ($photo_record["gallery_cgphoto_id"] == $RECORD_ID) {
								if (!$db->AutoExecute("community_galleries", array("gallery_cgphoto_id" => 0), "UPDATE", "`cgallery_id` = ".$db->qstr($photo_record["cgallery_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `gallery_cgphoto_id` = ".$db->qstr($RECORD_ID))) {
									application_log("error", "Failed to remove the gallery hilite image [".$RECORD_ID."] photo from community [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
								}
							}

							if (!(int) $gallery_record["gallery_cgphoto_id"]) {
								if (!$db->AutoExecute("community_galleries", array("gallery_cgphoto_id" => $RECORD_ID), "UPDATE", "`cgallery_id` = ".$db->qstr($gallery_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
									application_log("error", "Failed to add the gallery hilite image [".$RECORD_ID."] photo to community [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
								}
							}

							@$db->AutoExecute("community_gallery_comments", array("cgallery_id" => $gallery_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`cgphoto_id` = ".$db->qstr($RECORD_ID)." AND `cgallery_id` = ".$db->qstr($photo_record["cgallery_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID));
							add_statistic("community:".$COMMUNITY_ID.":galleries", "photo_move", "cgphoto_id", $RECORD_ID);
							communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_move_photo", true, $gallery_id);
						} else {
							application_log("error", "Failed to deactivate [".$RECORD_ID."] photo from community. Database said: ".$db->ErrorMsg());
						}
					}
				} else {
					application_log("error", "The provided photo id [".$RECORD_ID."] is already deactivated.");
				}
                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully moved the <strong>%s</strong> photo to <strong>%s</strong>."), $photo_record["photo_title"], $gallery_record["gallery_title"]), "success", $MODULE);
				header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$gallery_record["page_url"]."?section=view-gallery&id=".$gallery_id);
				exit;
			}
		} else {
			application_log("error", "The provided photo id [".$RECORD_ID."] was invalid.");
		}
	} else {
		application_log("error", "No gallery id was provided for moving the photo.");
	}
} else {
	application_log("error", "No photo id was provided for moving the photo.");
}

header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
exit;
?>