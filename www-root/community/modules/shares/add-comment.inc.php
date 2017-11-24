<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to allow users to add comments to a particular file that is being shared
 * within a folder.
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

$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Add File Comment</h1>\n";

if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`folder_title`, b.`admin_notifications`
					FROM `community_share_files` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`csfile_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`file_active` = '1'
					AND b.`folder_active` = '1'";
	$file_record	= $db->GetRow($query);
	if ($file_record) {
		if ((int) $file_record["file_active"]) {
			if (shares_module_access($file_record["cshare_id"], "add-comment")) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$file_record["cshare_id"], "title" => limit_chars($file_record["folder_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$RECORD_ID, "title" => limit_chars($file_record["file_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-comment&amp;id=".$RECORD_ID, "title" => "Add File Comment");

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
							$PROCESSED["csfile_id"]			= $RECORD_ID;
							$PROCESSED["cshare_id"]			= $file_record["cshare_id"];
							$PROCESSED["community_id"]		= $COMMUNITY_ID;
							$PROCESSED["proxy_id"]			= $ENTRADA_USER->getActiveId();
							$PROCESSED["comment_active"]	= 1;
							$PROCESSED["release_date"]		= time();
							$PROCESSED["updated_date"]		= time();
							$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

							if ($db->AutoExecute("community_share_comments", $PROCESSED, "INSERT")) {
								if ($COMMENT_ID = $db->Insert_Id()) {
                                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully added a new file comment."), "success", $MODULE);
									add_statistic("community:".$COMMUNITY_ID.":shares", "comment_add", "cscomment_id", $COMMENT_ID);
									communities_log_history($COMMUNITY_ID, $PAGE_ID, $COMMENT_ID, "community_history_add_file_comment", 1, $RECORD_ID);
                                    if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                        community_notify($COMMUNITY_ID, $RECORD_ID, "file-comment", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$RECORD_ID, $RECORD_ID);
                                    }

                                    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-file&id=".$RECORD_ID;
                                    header("Location: " . $url);
                                    exit;
								}
							}

							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem adding this file comment into the system. The MEdTech Unit was informed of this error; please try again later.";

								application_log("error", "There was an error inserting a file comment. Database said: ".$db->ErrorMsg());
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						continue;
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
					<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-comment&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
						<table summary="Add File Comment">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="2" style="padding-top: 15px; text-align: right">
										<input type="submit" class="btn btn-primary" value="Save" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td colspan="2"><h2>File Comment Details</h2></td>
								</tr>
								<tr>
									<td>
										<label for="comment_title" class="form-nrequired">Comment Title</label>
									</td>
									<td>
										<input type="text" id="comment_title" name="comment_title" value="<?php echo ((isset($PROCESSED["comment_title"])) ? html_encode($PROCESSED["comment_title"]) : ""); ?>" maxlength="128" style="width: 300px" />
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
			$NOTICESTR[] = "The file that you are trying to comment on was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $file_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $file_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The file record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to comment on it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The file id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided file id was invalid [".$RECORD_ID."] (Add Comment).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid file id to proceed.";

	echo display_error();

	application_log("error", "No file id was provided to comment on. (Add Comment)");
}
?>
