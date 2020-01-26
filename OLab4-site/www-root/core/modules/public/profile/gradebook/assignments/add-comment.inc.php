<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to allow users to add comments to a particular file that is being shared
 * within a folder.
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

echo "<h1>Add Assignment Comment</h1>\n";

if (isset($_GET["pid"]) && $tmp_input = clean_input($_GET["pid"], "int")) {
    $USER_ID = $tmp_input;
} else {
    $USER_ID = $ENTRADA_USER->getActiveID();
}

if ($RECORD_ID) {
	
	$query			= "
					SELECT a.*
					FROM `assignments` AS a
					WHERE a.`assignment_id` = ".$db->qstr($RECORD_ID)."
					AND a.`assignment_active` = '1'";
	$assignment_record	= $db->GetRow($query);
	if ($assignment_record) {
        $allowed = false;
        $query = "
                SELECT * 
                FROM `assignment_files` 
                WHERE `assignment_id`=".$db->qstr($RECORD_ID)."
                AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
        $owner = $db->GetRow($query);
        if ($owner) {
            $allowed = true;
        } else{
            $query = "
                    SELECT a.* 
                    FROM `assignment_files` AS a 
                    JOIN `assignment_contacts` AS b 
                    ON a.`assignment_id` = b.`assignment_id` 
                    WHERE a.`assignment_id` = ".$db->qstr($RECORD_ID)."
                    AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
            $assignment_contact = $db->GetRow($query);				
            if($assignment_contact){
                $allowed = true;
            }
        }
        if ($allowed){//shares_module_access($file_record["cshare_id"], "add-comment")) {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$RECORD_ID.(isset($assignment_contact)&&$assignment_contact?"&pid=".$assignment_record["proxy_id"]:""), "title" => limit_chars($assignment_record["assignment_title"], 32));
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?section=add-comment&assignment_id=".$RECORD_ID."&pid=".$USER_ID, "title" => "Add Comment");

            load_rte();

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
                        $PROCESSED["proxy_to_id"]       = $USER_ID;
                        $PROCESSED["assignment_id"]		= $RECORD_ID;
                        $PROCESSED["proxy_id"]			= $ENTRADA_USER->getID();
                        $PROCESSED["comment_active"]	= 1;
                        $PROCESSED["release_date"]		= time();
                        $PROCESSED["updated_date"]		= time();
                        $PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

                        if ($db->AutoExecute("assignment_comments", $PROCESSED, "INSERT")) {
                            if ($COMMENT_ID = $db->Insert_Id()) {
                                $url			= ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$RECORD_ID.(isset($assignment_contact)&&$assignment_contact?"&pid=".$assignment_record["proxy_id"]:"")."#comment-".$COMMENT_ID;
                                $ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

                                $SUCCESS++;
                                $SUCCESSSTR[]	= "You have successfully added a new assignment comment.<br /><br />You will now be redirected back to this assignment; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

                                add_statistic("assignment:".$RECORD_ID, "comment_add", "acomment_id", $COMMENT_ID);
                            }
                        }

                        if (!$SUCCESS) {
                            $ERROR++;
                            $ERRORSTR[] = "There was a problem adding this assignment comment into the system. The MEdTech Unit was informed of this error; please try again later.";

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
                <form action="<?php echo ENTRADA_URL."/profile/gradebook/assignments?section=add-comment&amp;assignment_id=".$RECORD_ID."&pid=".$USER_ID; ?>&amp;step=2" method="post">
                <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Add Assignment Comment">
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
            $ERRORSTR[] = "You are not authorized to add a comment to this assignment.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";
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
		
		$ERROR++;
		$ERRORSTR[] = "The assignment id that you have provided does not exist in the system. Please provide a valid record id to proceed.<br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";

		echo display_error();

		application_log("error", "The provided file id was invalid [".$RECORD_ID."] (Add Comment).");
	}
} else {
	$url			= ENTRADA_URL."/profile/gradebook/assignments";
	$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
	
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid assignment id to proceed. <br /><br />You will now be redirected back to the assignment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue";

	echo display_error();

	application_log("error", "No assignment id was provided to comment on. (Add Comment)");
}
