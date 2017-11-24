<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing discussion posts within a forum in a community. This
 * action can be called by either a community administrator, or by the user who
 * originally posted the topic.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.* 
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DISCUSSIONS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/discussions.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type='text/javascript' src='" . ENTRADA_URL . "/javascript/bootstrap-filestyle.min.js?release=".html_encode(APPLICATION_VERSION)."'></script>";
$HEAD[] = "<script type='text/javascript' src='" . COMMUNITY_URL . "/javascript/discussion_files.js?release=".html_encode(APPLICATION_VERSION)."'></script>";

echo "<h1>Edit Post</h1>\n";

if ($RECORD_ID) {
    $DISCUSSION_ID = (int)$db->GetOne("SELECT `cdiscussion_id` FROM `community_discussion_topics` WHERE `cdtopic_id`=".$db->qstr($RECORD_ID));
	$query			= "
					SELECT a.*, b.`forum_title`, b.`admin_notifications`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `poster_fullname`, c.`username` AS `poster_username`, d.`notify_active`, e.`notify_active` AS `parent_notify` 
					FROM `community_discussion_topics` AS a
					LEFT JOIN `community_discussions` AS b
					ON a.`cdiscussion_id` = b.`cdiscussion_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					LEFT JOIN `community_notify_members` AS d
					ON a.`cdtopic_id` = d.`record_id`
					AND d.`community_id` = a.`community_id`
					AND d.`notify_type` = 'reply'
					AND d.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					LEFT JOIN `community_notify_members` AS e
					ON a.`cdtopic_parent` = e.`record_id`
					AND e.`community_id` = a.`community_id`
					AND e.`notify_type` = 'reply'
					AND e.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					WHERE a.`proxy_id` = c.`id`
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)." 
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
					AND a.`topic_active` = '1'
					AND b.`forum_active` = '1'";
	$topic_record	= $db->GetRow($query);
	if ($topic_record) {
		if (isset($topic_record["notify_active"])) {
			$notifications = ($topic_record["notify_active"] ? true : false);
			if ($topic_record["notify_active"] != null) {
				$notify_record_exists = true;
			}
		} elseif (isset($topic_record["parent_notify"])) {
			$notifications = ($topic_record["parent_notify"] ? true : false);
			if ($topic_record["parent_notify"] != null) {
				$notify_record_exists = true;
			}
		} else {
			$notifications = false;
			$notify_record_exists = false;
		}
        
        $read_allowed = discussion_topic_module_access($topic_record["cdtopic_id"], "view-post");
        $update_allowed = discussion_topic_module_access($topic_record["cdtopic_id"], "edit-post");
        
		if ($update_allowed) {
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$topic_record["cdiscussion_id"], "title" => limit_chars($topic_record["forum_title"], 16));
			if (!$topic_record["cdtopic_parent"]) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID, "title" => limit_chars($topic_record["topic_title"], 16));
			} else {
				$parent_title = $db->GetOne("SELECT `topic_title` FROM `community_discussion_topics` WHERE `cdtopic_id` = ".$topic_record["cdtopic_parent"]);
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record["cdtopic_parent"], "title" => limit_chars($parent_title, 12)." Reply");
			}
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-post&id=".$RECORD_ID, "title" => "Edit Post");

			/**
			 * This is used to determine what information is displayed to the user
			 * since this one file (edit-post) is used to edit both topic posts and replies.
			 */
			$POST_TYPE = ((!(int) $topic_record["cdtopic_parent"]) ? "post" : "reply");

			communities_load_rte();
            //this section is for the orginal post

			if ($POST_TYPE == "post") {
				// Error Checking
				switch($STEP) {
					case 2 :
						/**
						 * Required field "title" / Topic Title.
						 */
						if ((isset($_POST["topic_title"])) && ($title = clean_input($_POST["topic_title"], array("notags", "trim")))) {
							$PROCESSED["topic_title"] = $title;
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Post Title</strong> field is required.";
						}

						/**
						 * Non-Required field "description" / Forum Description.
						 * Security Note: I guess I do not need to html_encode the data in the description because
						 * the bbcode parser takes care of this. My other option would be to html_encode, then html_decode
						 * but I think I'm going to trust the bbcode parser right now. Other scaries would be XSS in PHPMyAdmin...
						 */
						if ((isset($_POST["topic_description"])) && ($description = clean_input($_POST["topic_description"], array("trim", "allowedtags")))) {
							$PROCESSED["topic_description"] = $description;
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Post Body</strong> field is required, this is the body of your post.";
						}
						
						/**
						 * Non-required field "anonymous" / Should posts be displayed anonymously to non-admins
						 */
						if (defined("COMMUNITY_DISCUSSIONS_ANON") && COMMUNITY_DISCUSSIONS_ANON && (isset($_POST["anonymous"])) && ((int) $_POST["anonymous"])) {
							$PROCESSED["anonymous"]	= 1;
						} else {
							$PROCESSED["anonymous"]	= 0;
						}	
						
						/**
						 * Email Notificaions.
						 */
						if(isset($_POST["enable_notifications"])) {
							$notifications = $_POST["enable_notifications"];
						} else {
							$notifications = 0;
						}
                        
						/**
						 * Required field "release_from" / Release Start (validated through validate_calendars function).
						 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
						 */
						$release_dates = validate_calendars("release", true, false);
						if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
							$PROCESSED["release_date"]	= (int) $release_dates["start"];
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
						}
						if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
							$PROCESSED["release_until"]	= (int) $release_dates["finish"];
						} else {
							$PROCESSED["release_until"]	= 0;
						}

                        /* Deactivate any files the user has chosen to deactivate */
                        if (isset($_POST['files_to_remove']) && is_array($_POST['files_to_remove'])) {
                            $files_to_remove = array();
                            foreach ($_POST['files_to_remove'] as $file_id) {
                                if ((int)$file_id) {
                                    $files_to_remove[] = (int)$file_id;
                                }
                            }
                            
                            foreach ($files_to_remove as $cdfile_id) {
                                $file_remove_query = "UPDATE `community_discussions_files`
                                          SET `file_active`=0, `updated_date`=".time().", 
                                          `updated_by`=".$db->qstr($ENTRADA_USER->getID())."
                                          WHERE `cdfile_id`=".$db->qstr($cdfile_id)."
                                          AND `cdtopic_id`=".$db->qstr($RECORD_ID)."
                                          AND `community_id`=".$db->qstr($COMMUNITY_ID);
                                $file_remove_results = $db->execute($file_remove_query);
                                if ($file_remove_results) {
                                    $file_version_query = "UPDATE `community_discussion_file_versions`
                                              SET `file_active`=0, `updated_date`=".time().",
                                              `updated_by`=".$db->qstr($ENTRADA_USER->getID())."
                                              WHERE `cdfile_id`=".$db->qstr($cdfile_id)."
                                              AND `cdtopic_id`=".$db->qstr($RECORD_ID)."
                                              AND `community_id`=".$db->qstr($COMMUNITY_ID);
                                    $file_version_results = $db->query($file_version_query);
                                    if ($file_version_results) {
                                        communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
                                        add_statistic("community:".$COMMUNITY_ID.":discussions", "file_delete", "cdfile_id", $RECORD_ID);
                                        $db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id`=".$db->qstr($COMMUNITY_ID)." AND `module_id`=".$db->qstr($MODULE_ID)." AND `record_id`=".$db->qstr($RECORD_ID));
                                        
                                        $SUCCESS++;
                                        $SUCCESSSTR[] = "Successfully deactivated file [".$cdfile_id."]";
                                    }
                                }
                            }
                        }
                        
                        /* Add any files the user has chosen to add */
                        $PROCESSED['uploaded_file'] = array();
                        if (isset($_FILES["uploaded_file"]) && is_array($_FILES["uploaded_file"]["name"])) {
                            foreach ($_FILES["uploaded_file"]["name"] as $tmp_file_id => $file_name) {

                                $file_info = array();

                                switch ($_FILES["uploaded_file"]["error"][$tmp_file_id]) {
                                    case 0 :
                                        if (strpos($_FILES["uploaded_file"]["name"][$tmp_file_id], ".") === false) {
                                            $ERROR++;
                                            $ERRORSTR[] = "You cannot upload a file without an extension (.doc, .ppt, etc).";

                                            application_log("error", "User {$ENTRADA_USER->getID()} uploaded a file to shares without an extension.");
                                        } else {
                                            if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"][$tmp_file_id])) <= $VALID_MAX_FILESIZE) {
                                                $finfo = new finfo(FILEINFO_MIME);
                                                $type = $finfo->file($_FILES["uploaded_file"]["tmp_name"][$tmp_file_id]);
                                                $type_array = explode(";", $type);
                                                $mimetype = $type_array[0];
                                                $file_info["file_mimetype"] = strtolower(trim($_FILES["uploaded_file"]["type"][$tmp_file_id]));

                                                switch ($file_info["file_mimetype"]) {
                                                    case "application/x-forcedownload":
                                                    case "application/octet-stream":
                                                    case "\"application/octet-stream\"":
                                                    case "application/download":
                                                    case "application/force-download":
                                                        $file_info["file_mimetype"] = $mimetype;
                                                    break;
                                                }
                                                $file_info["file_version"] = 1;
                                                $file_info["file_filesize"] = $file_filesize;
                                                $file_info["file_filename"] = useable_filename(trim($file_name));

                                                if ((!defined("COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION))) {
                                                    $ERROR++;
                                                    $ERRORSTR[] = "There is a problem with the document storage directory on the server; the administrators have been informed of this error, please try again later.";
                                                    application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."] does not exist or is not writable.");
                                                }
                                            } else {
                                                $ERROR++;
                                                $ERRORSTR[] = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";

                                                application_log("error", "User {$ENTRADA_USER->getID()} unable to upload a file, the file size is larger than the limit.");
                                            }
                                        }
                                    break;
                                    case 1 :
                                    case 2 :
                                        $ERROR++;
                                        $ERRORSTR[] = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";
                                    break;
                                    case 3 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
                                    break;
                                    case 4 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "You did not select a file from your computer to upload. Please select a local file and try again. The file's id was ".$tmp_file_id;
                                    break;
                                    case 6 :
                                    case 7 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "Unable to store the new file on the server; the administrators have been informed of this error, please try again later.";

                                        application_log("error", "Community file upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
                                    break;
                                    default :
                                        application_log("error", "Unrecognized file upload error number [".$_FILES["filename"]["error"]."].");
                                    break;
                                }

                                /**
                                 * Required field "title" / File Title.
                                 */
                                if (isset($_POST['uploaded_title'][$tmp_file_id]) && ($title = clean_input($_POST['uploaded_title'][$tmp_file_id]))) {
                                    $file_info['file_title'] = $title;
                                } else {
                                    $ERROR++;
                                    $ERRORSTR[] = "The <strong>File Title</strong> field is required.";
                                }

                                $PROCESSED['uploaded_file'][] = $file_info;
                            }
                        }

						if (!$ERROR) {
							$PROCESSED["cdtopic_parent"]	= 0;
							$PROCESSED["updated_date"]		= time();
							$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();
							
                            foreach ($PROCESSED['uploaded_file'] as $file_index => $file_info) {
                                $file_insert_array = array(
                                    'cdtopic_id' => $RECORD_ID,
                                    'cdiscussion_id' => $DISCUSSION_ID,
                                    'community_id' => $COMMUNITY_ID,
                                    'proxy_id' => $ENTRADA_USER->getID(),
                                    'file_title' => $file_info['file_title'],
                                    'file_description' => '',
                                    'allow_member_revision' => 1,
                                    'allow_troll_revision' => 0,
                                    'access_method' => 1,
                                    'release_date' => $PROCESSED['release_date'],
                                    'release_until' => $PROCESSED['release_until'],
                                    'updated_date' => time(),
                                    'updated_by' => $ENTRADA_USER->getID()
                                    );
                                if ($db->AutoExecute("community_discussions_files", $file_insert_array, "INSERT")) {
                                    if ($FILE_ID = $db->Insert_Id()) {
                                        $file_insert_array['cdfile_id'] = $FILE_ID;
                                        $file_insert_array['file_mimetype'] = $file_info['file_mimetype'];
                                        $file_insert_array['file_filename'] = $file_info['file_filename'];
                                        $file_insert_array['file_filesize'] = $file_info['file_filesize'];
                                        if ($db->AutoExecute("community_discussion_file_versions", $file_insert_array, "INSERT")) {
                                            if ($VERSION_ID = $db->Insert_Id()) {
                                                if (communities_discussion_process_file($_FILES["uploaded_file"]["tmp_name"][$file_index], $VERSION_ID)) {
                                                    $SUCCESS++;
                                                    $SUCCESSSTR[] = "You have successfully uploaded ".html_encode($file_insert_array['file_filename'].".");

                                                    add_statistic("community:".$COMMUNITY_ID.":discussions", "file_add", "cdfile_id", $VERSION_ID);
                                                    communities_log_history($COMMUNITY_ID, $PAGE_ID, $FILE_ID, "community_history_add_file", 1, $TOPIC_ID);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
							
							if ($db->AutoExecute("community_discussion_topics", $PROCESSED, "UPDATE", "`cdtopic_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
								if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
									if ($PROCESSED["release_date"] != $topic_record["release_date"]) {
										$notification = $db->GetRow("SELECT * FROM `community_notifications` WHERE `record_id` = ".$db->qstr($RECORD_ID)." AND `type` = 'post'");
										if ($notification) {
											$notification["release_time"] = $PROCESSED["release_date"];
											$db->AutoExecute("community_notifications", $notification, "UPDATE", "`cnotification_id` = ".$db->qstr($notification["cnotification_id"]));
										}
									}
									if (isset($notifications) && $notify_record_exists && $_SESSION["details"]["notifications"] && COMMUNITY_NOTIFICATIONS_ACTIVE) {
										$db->Execute("UPDATE `community_notify_members` SET `notify_active` = '".($notifications ? "1" : "0")."' WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `record_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `notify_type` = 'reply'");
									} elseif (isset($notifications) && !$notify_record_exists && COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
										$db->Execute("INSERT INTO `community_notify_members` (`proxy_id`, `record_id`, `community_id`, `notify_type`, `notify_active`) VALUES (".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($RECORD_ID).", ".$db->qstr($COMMUNITY_ID).", 'reply', '".($notifications ? "1" : "0")."')");
									}
								}
								Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["topic_title"]), "success", $MODULE);

                                add_statistic("community:".$COMMUNITY_ID.":discussions", "post_edit", "cdtopic_id", $RECORD_ID);
								communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_post", 0, $topic_record["cdiscussion_id"]);

                                $url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID;
                                header("Location: " . $url);
                                exit;
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem updating this discussion post, perhaps there were no changes? The MEdTech Unit was informed of this error; please try again later.";

								application_log("error", "There was an error updating a discussion forum post. Database said: ".$db->ErrorMsg());
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						$PROCESSED = $topic_record;
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
						<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-post&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data">
                            <div class="container-file">
                                <h2>Discussion Post Details</h2>
                                <ul class="container-file-group">
                                    <li>
                                        <label for="topic_title" class="form-required db_file_col1">Post Title</label>
                                        <span class="db_file_col2">
                                            <input type="text" id="topic_title" name="topic_title" value="<?php echo ((isset($PROCESSED["topic_title"])) ? html_encode($PROCESSED["topic_title"]) : ""); ?>" maxlength="128" style="width: 95%" /></span>
                                    </li>
                                    <li>
                                        <label for="topic_description" class="form-required db_file_col1">Post Body</label>
                                        <span class="db_file_col2">
                                            <textarea id="topic_description" name="topic_description" style="width: 100%; height: 200px" cols="68" rows="12">
                                            <?php echo ((isset($PROCESSED["topic_description"])) ? html_encode($PROCESSED["topic_description"]) : ""); ?>
                                            </textarea>
                                        </span>
                                    </li>
                                    <li>
                                        <br/>
                                        <table class="table table-bordered no-thead">
                                            <colgroup>
                                                <col style="width: 5%" />
                                                <col style="width: auto" />
                                            </colgroup>
                                            <tbody>
                                            <?php
                                            if (defined("COMMUNITY_DISCUSSIONS_ANON") && COMMUNITY_DISCUSSIONS_ANON) {
                                                ?>
                                                <tr>
                                                    <td class="center">
                                                        <input type="checkbox" id="anonymous" name="anonymous" <?php echo (isset($PROCESSED["anonymous"]) && $PROCESSED["anonymous"] ? "checked=\"checked\"" : ""); ?> value="1"/>
                                                    </td>
                                                    <td>
                                                        <label for="anonymous" class="form-nrequired">Hide my name from other community members.</label>
                                                    </td>
                                                </tr>
                                                <?php
                                            }

                                            if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
                                                ?>
                                                <tr>
                                                    <td class="center">
                                                        <input type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo ($notifications ? "checked=\"checked\"" : ""); ?>/>
                                                    </td>
                                                    <td>
                                                        <label for="enable_notifications" class="form-nrequired">Receive e-mail notification when people reply to this thread.</label>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>

                                            </tbody>
                                        </table>
                                    </li>
                                    <li id="file_list_db">
                                        <input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
                                        <div id="file_list">
                                            <h2>File Attachments</h2>
                                            <ul class="container-file-group">
                                                <div class="content-small" style="margin-top: 5px">
                                                    <strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
                                                </div>
                                                <li>
                                                    <label for="uploaded_file" class="form-required db_file_col1">Select Local File</label>
                                                    <span class="db_file_col2">
                                                        <input type="file" id="uploaded_file" />
                                                        <input type="text" id="uploaded_title" placeholder="Title" maxlength="128" />
                                                        <input type="button" class="btn" id="file_attach_button" value="Attach File" />
                                                    </span>
                                                </li>
                                                <li id="attached-files">
                                                <?php
                                                $query = "SELECT a.*, d.`file_filename`
                                                          FROM `community_discussions_files` AS a
                                                          LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                          ON a.`proxy_id` = b.`id`
                                                          LEFT JOIN `community_discussion_topics` AS c
                                                          ON a.`cdtopic_id` = c.`cdtopic_id`
                                                          LEFT JOIN `community_discussion_file_versions` AS d
                                                          ON d.`cdfile_id`=a.`cdfile_id`
                                                          WHERE a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
                                                          AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                                          AND a.`file_active` = '1'";
                                                $results = $db->GetAll($query);
                                                if ($results) {
                                                    foreach ($results as $row) {
                                                        echo "<div id=\"attached_file{$row['cdfile_id']}\">\n";
                                                        echo "<span class=\"attached-file-wrapper\">{$row['file_title']} ({$row['file_filename']})\n";
                                                        echo "<a href=\"javascript:removeAttachedFile({$row['cdfile_id']})\"><i class=\"icon-remove\"></i></a>\n";
                                                        echo "</span>\n";
                                                        echo "</div>\n";
                                                    }
                                                }
                                                ?>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>

                                <h2>Time Release Options</h2>
                                <table class="date-time">
								    <?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
								</table>
                                <br/>
                                <div>
                                    <input type="button" class="btn pull-left" value="<?php echo $translate->_("global_button_cancel"); ?>" onclick="window.location='<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID; ?>'" />
                                </div>
                                <div class="pull-right">
                                    <input type="submit" class="btn btn-primary clearfix" value="<?php echo $translate->_("global_button_update"); ?>" />
                                </div>
                            </div>
						</form>
						<?php
					break;
				}
            } else { //this section is the replies
				// Error Checking
				switch($STEP) {
					case 2 :
						/**
						 * Non-Required field "description" / Forum Description.
						 * Security Note: I guess I do not need to html_encode the data in the description because
						 * the bbcode parser takes care of this. My other option would be to html_encode, then html_decode
						 * but I think I'm going to trust the bbcode parser right now. Other scaries would be XSS in PHPMyAdmin...
						 */
						if ((isset($_POST["topic_description"])) && ($description = clean_input($_POST["topic_description"], array("trim")))) {
							$PROCESSED["topic_description"] = $description;
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Post Body</strong> field is required, this is your reply to the post.";
						}

						/**
						 * Non-required field "anonymous" / Should posts be displayed anonymously to non-admins
						 */
						if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && (isset($_POST["anonymous"])) && ((int) $_POST["anonymous"])) {
							$PROCESSED["anonymous"]	= 1;
						} else {
							$PROCESSED["anonymous"]	= 0;
						}

						if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"] && isset($_POST["enable_notifications"])) {
							$notifications = $_POST["enable_notifications"];
						} else {
							$notifications = false;
						}
						
                        /* Deactivate any files the user has chosen to deactivate */
                        if (isset($_POST['files_to_remove']) && is_array($_POST['files_to_remove'])) {
                            $files_to_remove = array();
                            foreach ($_POST['files_to_remove'] as $file_id) {
                                if ((int)$file_id) {
                                    $files_to_remove[] = (int)$file_id;
                                }
                            }
                            
                            foreach ($files_to_remove as $cdfile_id) {
                                $file_remove_query = "UPDATE `community_discussions_files`
                                          SET `file_active`=0, `updated_date`=".time().", 
                                          `updated_by`=".$db->qstr($ENTRADA_USER->getID())."
                                          WHERE `cdfile_id`=".$db->qstr($cdfile_id)."
                                          AND `cdtopic_id`=".$db->qstr($RECORD_ID)."
                                          AND `community_id`=".$db->qstr($COMMUNITY_ID);
                                $file_remove_results = $db->execute($file_remove_query);
                                if ($file_remove_results) {
                                    $file_version_query = "UPDATE `community_discussion_file_versions`
                                              SET `file_active`=0, `updated_date`=".time().",
                                              `updated_by`=".$db->qstr($ENTRADA_USER->getID())."
                                              WHERE `cdfile_id`=".$db->qstr($cdfile_id)."
                                              AND `cdtopic_id`=".$db->qstr($RECORD_ID)."
                                              AND `community_id`=".$db->qstr($COMMUNITY_ID);
                                    $file_version_results = $db->query($file_version_query);
                                    if ($file_version_results) {
                                        communities_deactivate_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID);
                                        add_statistic("community:".$COMMUNITY_ID.":discussions", "file_delete", "cdfile_id", $RECORD_ID);
                                        $db->AutoExecute("community_history", array("history_display" => 0), "UPDATE", "`community_id`=".$db->qstr($COMMUNITY_ID)." AND `module_id`=".$db->qstr($MODULE_ID)." AND `record_id`=".$db->qstr($RECORD_ID));
                                        
                                        $SUCCESS++;
                                        $SUCCESSSTR[] = "Successfully deactivated file [".$cdfile_id."]";
                                    }
                                }
                            }
                        }
                        
                        /* Add any files the user has chosen to add */
                        $PROCESSED['uploaded_file'] = array();
                        if (isset($_FILES["uploaded_file"]) && is_array($_FILES["uploaded_file"]["name"])) {
                            foreach ($_FILES["uploaded_file"]["name"] as $tmp_file_id => $file_name) {

                                $file_info = array();

                                switch ($_FILES["uploaded_file"]["error"][$tmp_file_id]) {
                                    case 0 :
                                        if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"][$tmp_file_id])) <= $VALID_MAX_FILESIZE) {
                                            $file_info["file_version"]        = 1;
                                            $file_info["file_mimetype"]        = strtolower(trim($_FILES["uploaded_file"]["type"][$tmp_file_id]));
                                            $file_info["file_filesize"]        = $file_filesize;
                                            $file_info["file_filename"]        = useable_filename(trim($file_name));

                                            if ((!defined("COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION))) {
                                                $ERROR++;
                                                $ERRORSTR[] = "There is a problem with the document storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.";
                                                application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."] does not exist or is not writable.");
                                            }
                                        }
                                    break;
                                    case 1 :
                                    case 2 :
                                        $ERROR++;
                                        $ERRORSTR[] = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";
                                    break;
                                    case 3 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
                                    break;
                                    case 4 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "You did not select a file from your computer to upload. Please select a local file and try again. The file's id was ".$tmp_file_id;
                                    break;
                                    case 6 :
                                    case 7 :
                                        $ERROR++;
                                        $ERRORSTR[]    = "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";

                                        application_log("error", "Community file upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
                                    break;
                                    default :
                                        application_log("error", "Unrecognized file upload error number [".$_FILES["filename"]["error"]."].");
                                    break;
                                }

                                /**
                                 * Required field "title" / File Title.
                                 */
                                if (isset($_POST['uploaded_title'][$tmp_file_id]) && ($title = clean_input($_POST['uploaded_title'][$tmp_file_id]))) {
                                    $file_info['file_title'] = $title;
                                } else {
                                    $ERROR++;
                                    $ERRORSTR[] = "The <strong>File Title</strong> field is required.";
                                }

                                $PROCESSED['uploaded_file'][] = $file_info;
                            }
                        }
						
						if (!$ERROR) {
							$PROCESSED["updated_date"]		= time();
							$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

                            foreach ($PROCESSED['uploaded_file'] as $file_index => $file_info) {
                                $file_insert_array = array(
                                    'cdtopic_id' => $RECORD_ID,
                                    'cdiscussion_id' => $DISCUSSION_ID,
                                    'community_id' => $COMMUNITY_ID,
                                    'proxy_id' => $ENTRADA_USER->getID(),
                                    'file_title' => $file_info['file_title'],
                                    'file_description' => '',
                                    'allow_member_revision' => 1,
                                    'allow_troll_revision' => 0,
                                    'access_method' => 1,
                                    'release_date' => time(),
                                    'release_until' => 0,
                                    'updated_date' => time(),
                                    'updated_by' => $ENTRADA_USER->getID()
                                    );
                                if ($db->AutoExecute("community_discussions_files", $file_insert_array, "INSERT")) {
                                    if ($FILE_ID = $db->Insert_Id()) {
                                        $file_insert_array['cdfile_id'] = $FILE_ID;
                                        $file_insert_array['file_mimetype'] = $file_info['file_mimetype'];
                                        $file_insert_array['file_filename'] = $file_info['file_filename'];
                                        $file_insert_array['file_filesize'] = $file_info['file_filesize'];
                                        if ($db->AutoExecute("community_discussion_file_versions", $file_insert_array, "INSERT")) {
                                            if ($VERSION_ID = $db->Insert_Id()) {
                                                if (communities_discussion_process_file($_FILES["uploaded_file"]["tmp_name"][$file_index], $VERSION_ID)) {
                                                    $SUCCESS++;
                                                    $SUCCESSSTR[] = "You have successfully uploaded ".html_encode($file_insert_array['file_filename'].".");

                                                    add_statistic("community:".$COMMUNITY_ID.":discussions", "file_add", "cdfile_id", $VERSION_ID);
                                                    communities_log_history($COMMUNITY_ID, $PAGE_ID, $FILE_ID, "community_history_add_file", 1, $TOPIC_ID);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

							if ($db->AutoExecute("community_discussion_topics", $PROCESSED, "UPDATE", "`cdtopic_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
								if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
									if ($PROCESSED["release_date"] != $topic_record["release_date"]) {
										$notification = $db->GetRow("SELECT * FROM `community_notifications` WHERE `record_id` = ".$db->qstr($topic_record["cdtopic_parent"])." AND `type` = 'post'");
										if ($notification) {
											$notification["release_time"] = $PROCESSED["release_date"];
											$db->AutoExecute("community_notifications", $notification, "UPDATE", "`cnotification_id` = ".$db->qstr($notification["cnotification_id"]));
										}
									}
									if (isset($notifications) && $notify_record_exists && COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"] && COMMUNITY_NOTIFICATIONS_ACTIVE) {
										$db->Execute("UPDATE `community_notify_members` SET `notify_active` = '".($notifications ? "1" : "0")."' WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `record_id` = ".$db->qstr($topic_record["cdtopic_parent"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `notify_type` = 'reply'");
									} elseif (isset($notifications) && !$notify_record_exists && COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
										$db->Execute("INSERT INTO `community_notify_members` (`proxy_id`, `record_id`, `community_id`, `notify_type`, `notify_active`) VALUES (".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($topic_record["cdtopic_parent"]).", ".$db->qstr($COMMUNITY_ID).", 'reply', '".($notifications ? "1" : "0")."')");
									}
								}
                                Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully updated your discussion post reply."), "success", $MODULE);

                                add_statistic("community:".$COMMUNITY_ID.":discussions", "post_edit", "cdtopic_id", $RECORD_ID);
								communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_reply", 0, $topic_record["cdtopic_parent"]);

                                $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-post&id=" . $topic_record["cdtopic_parent"]; //#post-".$RECORD_ID;
                                header("Location: " . $url);
                                exit;
                            } else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem updating this discussion post reply, perhaps there were no changes?. The MEdTech Unit was informed of this error; please try again later.";

								application_log("error", "There was an error updating a discussion post reply. Database said: ".$db->ErrorMsg());
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						$PROCESSED = $topic_record;
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

                        if ($update_allowed) {
                        $query    = "
                                    SELECT a.*, b.`forum_title`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `poster_fullname`, c.`username` AS `poster_username`
                                    FROM `community_discussion_topics` AS a
                                    LEFT JOIN `community_discussions` AS b
                                    ON a.`cdiscussion_id` = b.`cdiscussion_id`
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                                    ON a.`proxy_id` = c.`id`
                                    WHERE a.`proxy_id` = c.`id`
                                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND b.`cpage_id` = ".$db->qstr($PAGE_ID)." 
                                    AND a.`cdtopic_id` = ".$db->qstr($topic_record["cdtopic_parent"])."
                                    AND a.`topic_active` = '1'
                                    AND b.`forum_active` = '1'";
                        $result = $db->GetRow($query);
                            
                        if ($result) {
                            ?>
                            <script type="text/javascript">
                                jQuery(document).ready(function() {
                                   //style input file like bootstrap buttons
                                    jQuery("#uploaded_file").filestyle(
                                        {
                                        icon: true,
                                        buttonText: " Find File"
                                        }); 

                                    jQuery('#file_attach_button').click(function() {
                                        attachClicked();
                                    });
                                });
                            </script>
                            <form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-post&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data">
                                <?php
                                if ($read_allowed) {
                                    $query	= "
                                        SELECT a.*, b.`forum_title`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `poster_fullname`, c.`username` AS `poster_username`
                                        FROM `community_discussion_topics` AS a
                                        LEFT JOIN `community_discussions` AS b
                                        ON a.`cdiscussion_id` = b.`cdiscussion_id`
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                                        ON a.`proxy_id` = c.`id`
                                        WHERE a.`proxy_id` = c.`id`
                                        AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                        AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
                                        AND a.`cdtopic_id` = ".$db->qstr($topic_record["cdtopic_parent"])."
                                        AND a.`topic_active` = '1'
                                        AND b.`forum_active` = '1'";
                                    $result = $db->GetRow($query);
                                    if ($result) {
                                        ?>
                                        <h2>Original Post: <?php echo html_encode($result["topic_title"]); ?></h2>
                                        <table class="table">
                                            <colgroup>
                                                <col style="width: 30%" />
                                                <col style="width: 70%" />
                                            </colgroup>
                                            <tr>
                                                <td style="border-bottom: none; border-right: none"><?php echo get_online_status($result["proxy_id"], "image"); ?> <a href="<?php echo ENTRADA_URL."/people?profile=".html_encode($result["poster_username"]); ?>" style="font-weight: bold; text-decoration: underline"><?php echo html_encode($result["poster_fullname"]); ?></a></td>
                                                <td style="border-bottom: none; text-align: left">
                                                        <span class="content-small"><strong>Posted:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="content">
                                                    <?php echo $result["topic_description"]; ?>
                                                </td>
                                            </tr>
                                            <?php
                                            $query		= "	SELECT a.*, b.`username` AS `owner_username`
                                                                FROM `community_discussions_files` AS a
                                                                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                                ON a.`proxy_id` = b.`id`
                                                                LEFT JOIN `community_discussion_topics` AS c
                                                                ON a.`cdtopic_id` = c.`cdtopic_id`
                                                                WHERE a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
                                                                AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                                                AND a.`file_active` = '1'
                                                                ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "");
                                            $results	= $db->GetAll($query);
                                            if ($results) {
                                                ?>
                                                <tr>
                                                    <?php
                                                    echo '<td colspan="2">';
                                                    foreach($results as $result) {
                                                        $query = "  SELECT *
                                                        FROM `community_discussion_file_versions`
                                                        WHERE `cdfile_id` = '".$result["cdfile_id"]."'
                                                        AND `file_active` = '1'
                                                        ORDER BY `cdfversion_id`
                                                        LIMIT 1";
                                                        $version_result = $db->GetRow($query);
                                                        echo '<a href="'.COMMUNITY_URL.$COMMUNITY_URL.':'.$PAGE_URL.'?section=view-post&amp;id='.$RECORD_ID.'&amp;reply_id='.$result["cdtopic_id"].'&amp;cdfile_id='.$result["cdfile_id"].'&amp;download=latest">'.$result["file_title"] . '</a> - ' . formatSizeUnits($version_result["file_filesize"])."<br />";
                                                        if (isset($result["file_description"]) && $result["file_description"] != "") {
                                                            echo '<br/>'.$result["file_description"];
                                                        }
                                                    }
                                                    echo '</td>';
                                                    ?>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </table>
                                        <?php
                                    }
                                }?>
                                <div class="container-file">
                                    <h2>Current Reply</h2>
                                    <ul class="container-file-group">
                                        <li>
                                            <span class="db_file_col3">
                                            <textarea id="topic_description" name="topic_description" style="width: 100%; height: 200px" cols="68" rows="12"><?php echo ((isset($PROCESSED["topic_description"])) ? html_encode($PROCESSED["topic_description"]) : ""); ?></textarea>
                                            </span>
                                        </li>
                                        <?php if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON) { ?>
                                        <li>
                                            <table class="table table-bordered no-thead space-above">
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="anonymous" <?php echo (isset($PROCESSED["anonymous"]) && $PROCESSED["anonymous"] ? "checked=\"checked\"" : ""); ?> value="1"/>
                                                    </td>
                                                    <td>
                                                        <span class="file-checkbox-text">Hide my name from other community members.</span>
                                                    </td>
                                                </tr>

                                        <?php
                                        }
                                        if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
                                        ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="enable_notifications" id="enable_notifications" <?php echo ($notifications ? "checked=\"checked\"" : ""); ?> />
                                                </td>
                                                <td>
                                                    <span class="file-checkbox-text">Receive e-mail notification when people reply to this thread.</span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </table>
                                        </li>
                                        <li id="file_list_db">
                                            <input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
                                            <div id="file_list">
                                                <h2>File Attachments</h2>
                                                <ul class="container-file-group">
                                                    <li>
                                                        <div class="content-small" style="margin-top: 5px">
                                                            <strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <label for="uploaded_file" class="form-required db_file_col1">Select Local File</label>
                                                        <span class="db_file_col2">
                                                            <input type="file" id="uploaded_file" />
                                                            <input type="text" id="uploaded_title" placeholder="Title" maxlength="128" />
                                                            <input type="button" class="btn" id="file_attach_button" value="Attach" />
                                                        </span>
                                                    </li>
                                                    <li id="attached-files">
                                                        <?php
                                                        $query = "SELECT a.*, d.`file_filename`
                                                                  FROM `community_discussions_files` AS a
                                                                  LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                                  ON a.`proxy_id` = b.`id`
                                                                  LEFT JOIN `community_discussion_topics` AS c
                                                                  ON a.`cdtopic_id` = c.`cdtopic_id`
                                                                  LEFT JOIN `community_discussion_file_versions` AS d
                                                                  ON d.`cdfile_id`=a.`cdfile_id`
                                                                  WHERE a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
                                                                  AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                                                  AND a.`file_active` = '1'";
                                                        $results = $db->GetAll($query);
                                                        if ($results) {
                                                            foreach ($results as $row) {
                                                                echo "<div id=\"attached_file{$row['cdfile_id']}\">\n";
                                                                echo "<span class=\"attached-file-wrapper\">{$row['file_title']} ({$row['file_filename']})\n";
                                                                echo "<a href=\"javascript:removeAttachedFile({$row['cdfile_id']})\"><i class=\"icon-remove\"></i></a>\n";
                                                                echo "</span>\n";
                                                                echo "</div>\n";
                                                            }
                                                        }
                                                        ?>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                    <a class="btn pull-left" href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record["cdtopic_parent"]."#post-".$RECORD_ID; ?>"><?php echo $translate->_("global_button_cancel"); ?></a>
                                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_update"); ?>" />
                                </div>
                            </form>
                            <?php
                            }

					break;
				}
			}
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
		application_log("error", "The provided discussion post id was invalid [".$RECORD_ID."] (Edit Post).");
		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No discussion post id was provided to edit. (Edit Post)");
	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>
