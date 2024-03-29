<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing comments on a photo that was uploaded. This action is
 * available to either the original user who made the comment, or to any
 * communtiy administrator.
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

$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/galleries.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Edit Photo Comment</h1>\n";

if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`photo_title`, c.`gallery_title`, c.`admin_notifications`
					FROM `community_gallery_comments` AS a
					LEFT JOIN `community_gallery_photos` AS b
					ON a.`cgallery_id` = b.`cgallery_id`
					LEFT JOIN `community_galleries` AS c
					ON a.`cgallery_id` = c.`cgallery_id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cgcomment_id` = ".$db->qstr($RECORD_ID)."
					AND c.`cpage_id` = ".$db->qstr($PAGE_ID)." 
					AND a.`comment_active` = '1'
					AND b.`photo_active` = '1'
					AND c.`gallery_active` = '1'";
	$comment_record	= $db->GetRow($query);
	if ($comment_record) {
		if ((int) $comment_record["comment_active"]) {
			if (galleries_comment_module_access($RECORD_ID, "edit-comment")) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$comment_record["cgallery_id"], "title" => limit_chars($comment_record["gallery_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=".$comment_record["cgphoto_id"], "title" => limit_chars($comment_record["photo_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-comment&amp;id=".$RECORD_ID, "title" => "Edit Photo Comment");

				communities_load_rte();

				// Error Checking
				switch($STEP) {
					case 2 :
						/**
						 * Required field "title" / Comment Title.
						 */
						if ((isset($_POST["comment_title"])) && ($title = clean_input($_POST["comment_title"], array("notags", "trim")))) {
							$PROCESSED["comment_title"] = $title;
						} else {
							$PROCESSED["comment_title"] = "";
						}

						/**
						 * Non-Required field "description" / Comment Body
						 *
						 */
						if ((isset($_POST["comment_description"])) && ($description = clean_input($_POST["comment_description"], array("trim", "allowedtags")))) {
							$PROCESSED["comment_description"] = $description;
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Comment Body</strong> field is required, this is the comment you're making.";
						}

						/**
						 * Email Notificaions.
						 */
						if(isset($_POST["member_notify"])) {
							$PROCESSED["notify"] = $_POST["member_notify"];
						} else {
							$PROCESSED["notify"] = 0;
						}

						if (!$ERROR) {
							$PROCESSED["updated_date"]		= time();
							$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

							if ($db->AutoExecute("community_gallery_comments", $PROCESSED, "UPDATE", "`cgcomment_id` = ".$db->qstr($RECORD_ID)." AND `cgphoto_id` = ".$db->qstr($comment_record["cgphoto_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
								$SUCCESS++;
								Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully edited the <strong>%s</strong> photo comment."), $PROCESSED["comment_title"]), "success", $MODULE);

								add_statistic("community:".$COMMUNITY_ID.":galleries", "comment_edit", "cgcomment_id", $RECORD_ID);
								communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_photo_comment", 0, $comment_record["cgphoto_id"]);

								$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-photo&id=" . $comment_record["cgphoto_id"];//"#comment-".$RECORD_ID;
								header("Location: " . $url);
								exit;
							}

							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem editing this photo comment. The MEdTech Unit was informed of this error; please try again later.";

								application_log("error", "There was an error editing a photo comment. Database said: ".$db->ErrorMsg());
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						$PROCESSED = $comment_record;
					break;
				}

				// Page Display
				switch($STEP) {
					case 1 :
					default :
					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
					?>
					<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-comment&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
						<table summary="Edit Photo Comment">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="2" style="padding-top: 15px; text-align: right">
		                                <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td colspan="2">
										<h2>Photo Comment Details</h2>
									</td>
								</tr>
								<tr>
									<td>
										<label for="comment_title" class="form-nrequired">Comment Title</label>
									</td>
									<td>
										<input type="text" id="comment_title" name="comment_title" value="<?php echo ((isset($PROCESSED["comment_title"])) ? html_encode($PROCESSED["comment_title"]) : ""); ?>" maxlength="128" style="width: 300px;" />
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<label for="comment_description" class="form-required">Comment Body</label>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<textarea id="comment_description" name="comment_description" style="width: 98%; height: 200px" cols="68" rows="12"><?php echo ((isset($PROCESSED["comment_description"])) ? html_encode($PROCESSED["comment_description"]) : ""); ?></textarea>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
					<?php
					break;
				}
			} else {
				if ($ERROR) {
					echo display_error();
				}
				if ($NOTICE) {
					echo display_notice();
				}
			}
		} else {
			$NOTICE++;
			$NOTICESTR[] = "The comment that you are trying to edit was deactivated <strong>".date(DEFAULT_DATETIME_FORMAT, $comment_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $comment_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The comment record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The comment id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided comment id was invalid [".$RECORD_ID."] (Edit Comment).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid comment id to proceed.";

	echo display_error();

	application_log("error", "No comment id was provided to edit. (Edit Comment)");
}
?>
