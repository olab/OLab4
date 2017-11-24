<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing comments on a file. This action can be used by either
 * the original comment poster or by any community administrator.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 * 
*/

if (!defined("IN_PUBLIC_ASSIGNMENTS")) {
	exit;
}

$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Edit File Comment</h1>\n";

$ASSIGNMENT_ID = $RECORD_ID;

if (isset($_GET["cid"]) && $tmp_cmt = clean_input($_GET["cid"],"int")){
	$RECORD_ID = $tmp_cmt;
}else{
	$RECORD_ID = false;
}

if ($RECORD_ID && $ASSIGNMENT_ID) {
	$query			= "
					SELECT a.*, a.`proxy_id` AS `file_owner`, c.`assignment_title`, c.`course_id`
					FROM `assignment_comments` AS a
					JOIN `assignments` AS c
					ON a.`assignment_id` = c.`assignment_id`
					WHERE a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
					AND a.`acomment_id` = ".$db->qstr($RECORD_ID)."
					AND a.`comment_active` = '1'
					AND c.`assignment_active` = '1'";
	$comment_record	= $db->GetRow($query);
	if ($comment_record) {
		if ((int) $comment_record["comment_active"]) {
			if ($comment_record["proxy_id"] === $ENTRADA_USER->getID()) {
				if ($comment_record["file_owner"] === $ENTRADA_USER->getID()) {
					$owner = true;
				} else {
					$query = "SELECT * FROM `assignment_contacts` WHERE `assignment_id` = ".$db->qstr($ASSIGNMENT_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
					$assignment_contact = $db->GetRow($query);
				}
				if (isset($assignment_contact) && $assignment_contact) {
					$query = "SELECT CONCAT_WS(' ', `firstname`,`lastname`) AS `uploader` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($comment_record["file_owner"]);
					$user_name = $db->GetOne($query);
					$BREADCRUMB = array();
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook", "title" => "Gradebooks");
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "id" => $comment_record["course_id"])), "title" => "Assignments");
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("section" => "grade", "id" => $comment_record["course_id"], "assignment_id"=>$comment_record["assignment_id"], "step" => false)), "title" => $comment_record["assignment_title"]);
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?".replace_query(array("section" => "view", "id" => $ASSIGNMENT_ID, "pid"=>$comment_record["file_owner"], "step" => false)), "title" => $user_name."'s Submission");
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("section" => "edit-comment", "id" => $ASSIGNMENT_ID, "cid"=>$RECORD_ID, "step" => false)), "title" => "Edit Comment");
				} else {
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?section=view&amp;assignment_id=".$ASSIGNMENT_ID.(isset($assignment_contact) && $assignment_contact?"&amp;pid=".$comment_record["file_owner"]:""), "title" => limit_chars($comment_record["assignment_title"], 32));
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?section=edit-comment&amp;assignment_id=".$ASSIGNMENT_ID."&amp;cid=".$RECORD_ID, "title" => "Edit Comment");
				}							

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

							if ($db->AutoExecute("assignment_comments", $PROCESSED, "UPDATE", "`acomment_id` = ".$db->qstr($RECORD_ID)." AND `assignment_id` = ".$db->qstr($ASSIGNMENT_ID))) {
								
								//$url			= ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$ASSIGNMENT_ID.(isset($assignment_contact) && $assignment_contact?"&amp;pid=".$comment_record["file_owner"]:"")."#comment-".$RECORD_ID;
								$url			= ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$ASSIGNMENT_ID.(isset($assignment_contact) && $assignment_contact?"&pid=".$comment_record["file_owner"]:"")."#comment-".$RECORD_ID;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

								$SUCCESS++;
								$SUCCESSSTR[]	= "You have successfully edited your file comment.<br /><br />You will now be redirected back to this file; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

								add_statistic("assignment:".$ASSIGNMENT_ID, "comment_edit", "acomment_id", $RECORD_ID);
							}

							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem editing this file comment. The MEdTech Unit was informed of this error; please try again later.";

								application_log("error", "There was an error editing a file comment. Database said: ".$db->ErrorMsg());
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
					case 2 :
						if ($NOTICE) {
							echo display_notice();
						}
						if ($SUCCESS) {
							echo display_success();
						}
					break;
					case 1 :
					default :
					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
					?>
					<form action="<?php echo ENTRADA_URL."/profile/gradebook/assignments?section=edit-comment&amp;assignment_id=".$ASSIGNMENT_ID."&amp;cid=".$RECORD_ID; ?>&amp;step=2" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Edit File Comment">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 15px; text-align: right">
								<input type="submit" class="btn btn-primary" value="Save" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td colspan="3"><h2>File Comment Details</h2></td>
						</tr>
						<tr>
							<td colspan="2"><label for="comment_title" class="form-nrequired">Comment Title</label></td>
							<td style="text-align: right"><input type="text" id="comment_title" name="comment_title" value="<?php echo ((isset($PROCESSED["comment_title"])) ? html_encode($PROCESSED["comment_title"]) : ""); ?>" maxlength="128" style="width: 95%" /></td>
						</tr>
						<tr>
							<td colspan="3"><label for="comment_description" class="form-required">Comment Body</label></td>
						</tr>
						<tr>
							<td colspan="3">
								<textarea id="comment_description" name="comment_description" style="width: 100%; height: 200px" cols="68" rows="12"><?php echo ((isset($PROCESSED["comment_description"])) ? html_encode($PROCESSED["comment_description"]) : ""); ?></textarea>
							</td>
						</tr>
					</tbody>
					</table>
					</form>
					<?php
					break;
				}
			} else {
				$url			= ENTRADA_URL."/profile/gradebook/assignments";
				$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				
				$ERROR++;
				$ERRORSTR[] = "You are not authorized to add a comment to this file.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";
				if ($ERROR) {
					echo display_error();
				}
				if ($NOTICE) {
					echo display_notice();
				}
			}
		} else {
			$url			= ENTRADA_URL."/profile/gradebook/assignments";
			$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
			
			$NOTICE++;
			$NOTICESTR[] = "The comment that you are trying to edit was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $comment_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $comment_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";

			echo display_notice();

			application_log("error", "The comment record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$url			= ENTRADA_URL."/profile/gradebook/assignments";
		$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
	
		$ERROR++;
		$ERRORSTR[] = "The comment id that you have provided does not exist in the system. Please provide a valid record id to proceed.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";

		echo display_error();

		application_log("error", "The provided comment id was invalid [".$RECORD_ID."] (Edit Comment).");
	}
} else {
	$url			= ENTRADA_URL."/profile/gradebook/assignments";
	$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
	
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid comment id to proceed.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";

	echo display_error();

	application_log("error", "No comment id was provided to edit. (Edit Comment)");
}
