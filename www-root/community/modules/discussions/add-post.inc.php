<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add discussion posts to a particular forum in a community.
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
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved. * 
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

echo "<h1>New Discussion Post</h1>\n";

if ($RECORD_ID) {
	$query				= "SELECT * FROM `community_discussions` WHERE `cdiscussion_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$discussion_record	= $db->GetRow($query);
	if ($discussion_record) {
        $isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

        if (discussions_module_access($RECORD_ID, "add-post")) {
        $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$discussion_record["cdiscussion_id"], "title" => limit_chars($discussion_record["forum_title"], 32));
        $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-post&id=".$RECORD_ID, "title" => "New Discussion Post");

        communities_load_rte();

        $file_uploads = array();
        // Error Checking
        switch($STEP) {
            case 2 :
                /**
                     * Required field "title" / Forum Title.
                 */
                if ((isset($_POST["topic_title"])) && ($title = clean_input($_POST["topic_title"], array("notags", "trim")))) {
                    $PROCESSED["topic_title"] = $title;
                    $topic_title = $title;
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
                    $PROCESSED["topic_description"] = "";
                }

                if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"] && isset($_POST["enable_notifications"])) {
                    $notifications = $_POST["enable_notifications"];
                } else {
                    $notifications = false;
                }
                /**
                 * Non-required field "anonymous" / Should posts be displayed anonymously to non-admins
                 */
                if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && (isset($_POST["anonymous"])) && ((int) $_POST["anonymous"])) {
                    $PROCESSED["anonymous"]	= 1;
                } else {
                    $PROCESSED["anonymous"]	= 0;
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

                /*
                 * end upload file section error checking
                 */
                if (!$ERROR) {
                    $PROCESSED["cdtopic_parent"]	= 0;
                    $PROCESSED["cdiscussion_id"]	= $RECORD_ID;
                    $PROCESSED["community_id"]		= $COMMUNITY_ID;
                    $PROCESSED["proxy_id"]			= $ENTRADA_USER->getActiveId();
                    $PROCESSED["topic_active"]		= 1;
                    $PROCESSED["updated_date"]		= time();
                    $PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

                    if ($db->AutoExecute("community_discussion_topics", $PROCESSED, "INSERT")) {
                        if ($TOPIC_ID = $db->Insert_Id()) {
                            if (isset($notifications) && COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
                                $db->Execute("INSERT INTO `community_notify_members` (`proxy_id`, `record_id`, `community_id`, `notify_type`, `notify_active`) VALUES (".$db->qstr($PROCESSED["proxy_id"]).", ".$db->qstr($TOPIC_ID).", ".$db->qstr($COMMUNITY_ID).", 'reply', '".($notifications ? "1" : "0")."')");
                            }
                        }

                        /*
                         * Upload file section error checking
                         */
                        if (isset($_FILES["uploaded_file"]) && is_array($_FILES["uploaded_file"]) && !empty($_FILES["uploaded_file"]["name"][0])) {

                            // Reset error string and processed strings
                            if (isset($ERRORSTR)) {
                                unset($ERRORSTR);
                            }

                            if (isset($PROCESSED)) {
                                unset($PROCESSED);
                            }

                            foreach ($_FILES["uploaded_file"]["name"] as $tmp_file_id => $file_name) {

                                $file_info = array();

                                switch ($_FILES["uploaded_file"]["error"][$tmp_file_id]) {
                                    case 0 :
                                        if (strpos($_FILES["uploaded_file"]["name"][$tmp_file_id], ".") === false) {
                                            add_error("You cannot upload a file without a file extension (i.e. .doc, .ppt, etc).");

                                            application_log("error", "User {$ENTRADA_USER->getID()} uploaded a file to shares without an extension.");
                                        } else {
                                            if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"][$tmp_file_id])) <= $VALID_MAX_FILESIZE) {
                                                /*
                                                 * @TODO Warning finfo is not always compiled with PHP. This adds a new system requirement.
                                                 */
                                                $finfo = new finfo(FILEINFO_MIME);

                                                $type = $finfo->file($_FILES["uploaded_file"]["tmp_name"][$tmp_file_id]);
                                                $type_array = explode(";", $type);

                                                $mimetype = $type_array[0];

                                                $file_info["file_mimetype"] = strtolower(trim($_FILES["uploaded_file"]["type"][$tmp_file_id]));

                                                switch($PROCESSED["file_mimetype"]) {
                                                    case "application/x-forcedownload":
                                                    case "application/octet-stream":
                                                    case "\"application/octet-stream\"":
                                                    case "application/download":
                                                    case "application/force-download":
                                                        $PROCESSED["file_mimetype"] = $mimetype;
                                                    break;
                                                }

                                                $file_info["file_version"] = 1;
                                                $file_info["file_filesize"] = $file_filesize;
                                                $file_info["file_filename"] = useable_filename(trim($file_name));

                                                if ((!defined("COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION))) {
                                                    add_error("There is a problem with the document storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.");

                                                    application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."] does not exist or is not writable.");
                                                }
                                            } else {
                                                add_error("The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.");

                                                application_log("error", "User {$ENTRADA_USER->getID()} unable to upload a file, the file size is larger than the limit.");
                                            }
                                        }
                                    break;
                                    case 1 :
                                    case 2 :
                                        add_error("The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.");
                                    break;
                                    case 3 :
                                        add_error("The file that was uploaded did not complete the upload process or was interrupted; please try again.");
                                    break;
                                    case 4 :
                                        add_error("You did not select a file from your computer to upload. Please select a local file and try again. The file's id was ".$tmp_file_id);
                                    break;
                                    case 6 :
                                    case 7 :
                                        add_error("We are unable to store the new file on the server. Please try again later.");

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

                                $PROCESSED["uploaded_files"][] = $file_info;

                                /**
                                 * Permission checking for member access.
                                 */
                                if ((isset($_POST["allow_member_revision"])) && (clean_input($_POST["allow_member_revision"], array("int")) == 1)) {
                                    $PROCESSED["allow_member_revision"]    = 1;
                                } else {
                                    $PROCESSED["allow_member_revision"]    = 0;
                                }

                                /**
                                 * Permission checking for troll access.
                                 * This can only be done if the community_registration is set to "Open Community"
                                 */
                                if (!(int) $community_details["community_registration"]) {
                                    if ((isset($_POST["allow_troll_revision"])) && (clean_input($_POST["allow_troll_revision"], array("int")) == 1)) {
                                        $PROCESSED["allow_troll_revision"]    = 1;
                                    } else {
                                        $PROCESSED["allow_troll_revision"]    = 0;
                                    }
                                } else {
                                    $PROCESSED["allow_troll_revision"]        = 0;
                                }

                                /**
                                 * Required field "release_from" / Release Start (validated through validate_calendars function).
                                 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
                                 */
                                $release_dates = validate_calendars("release", true, false);
                                if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
                                    $PROCESSED["release_date"]    = (int) $release_dates["start"];
                                } else {
                                    add_error("The <strong>Release Start</strong> field is required.");
                                }
                                if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
                                    $PROCESSED["release_until"]    = (int) $release_dates["finish"];
                                } else {
                                    $PROCESSED["release_until"]    = 0;
                                }
                            }                            

                            //no errors
                            //inserts the file if exists
                            $PROCESSED["cdtopic_id"]        = $TOPIC_ID;
                            $PROCESSED["community_id"]      = $COMMUNITY_ID;
                            $PROCESSED["proxy_id"]          = $ENTRADA_USER->getActiveId();
                            $PROCESSED["file_active"]       = 1;
                            $PROCESSED["updated_date"]      = time();
                            $PROCESSED["updated_by"]        = $ENTRADA_USER->getID();
                            $PROCESSED["cdiscussion_id"]    = $RECORD_ID;

                            if (!$ERROR) {
                                $PROCESSED["cdfile_id"]     = $RECORD_ID;
                                $PROCESSED["cdtopic_id"]    = $TOPIC_ID;
                                $PROCESSED["community_id"]  = $COMMUNITY_ID;
                                $PROCESSED["proxy_id"]      = $ENTRADA_USER->getActiveId();
                                $PROCESSED["file_active"]   = 1;
                                $PROCESSED["updated_date"]  = time();
                                $PROCESSED["updated_by"]    = $ENTRADA_USER->getID();


                                unset($PROCESSED["cdfile_id"]);
                                foreach ($PROCESSED['uploaded_files'] as $file_index => $file_info) {
                                    $file_insert_array = array(
                                        'cdtopic_id' => $TOPIC_ID,
                                        'cdiscussion_id' => $RECORD_ID,
                                        'community_id' => $COMMUNITY_ID,
                                        'proxy_id' => $ENTRADA_USER->getID(),
                                        'file_title' => $file_info['file_title'],
                                        'file_description' => '',
                                        'allow_member_revision' => $PROCESSED['allow_member_revision'],
                                        'allow_troll_revision' => $PROCESSED['allow_troll_revision'],
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
                                                        $url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$TOPIC_ID;

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
                            }
                        }
                        $SUCCESS++;
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> to the community."), (isset($topic_title) ? $topic_title : "a new discussion post")), "success", $MODULE);

                        add_statistic("community:".$COMMUNITY_ID.":discussions", "post_add", "cdtopic_id", $TOPIC_ID);
                        communities_log_history($COMMUNITY_ID, $PAGE_ID, $TOPIC_ID, "community_history_add_post", 1, $RECORD_ID);

                        if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                            community_notify($COMMUNITY_ID, $TOPIC_ID, "post", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$TOPIC_ID, $RECORD_ID, $PROCESSED["release_date"]);
                        }
                        $url = COMMUNITY_URL . $COMMUNITY_URL. ":" . $PAGE_URL . "?section=view-post&id=" . $TOPIC_ID;
                        header("Location: " . $url);
                        exit;
                    }


                    if (!$SUCCESS) {
                        $ERROR++;
                        $ERRORSTR[] = "There was a problem inserting this discussion post into the system. The MEdTech Unit was informed of this error; please try again later.";

                        application_log("error", "There was an error inserting a discussion forum post. Database said: ".$db->ErrorMsg());
                    }
                }

                if ($ERROR) {
                    $STEP = 1;
                }
            break;
            case 1 :
            default :
            break;
        }

        // Page Display
        switch($STEP) {
            case 1 :
            default :
                if (count($file_uploads)<1) {
                    $file_uploads[] = array();
                }
                if ($ERROR) {
                    echo display_error();
                }
                if ($NOTICE) {
                    echo display_notice();
                }
                ?>

            <form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-post&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data">
                <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Add Discussion Post">
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
                        <td colspan="2">
                            <h2>Discussion Post Details</h2>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="topic_title" class="form-required">Post Title</label>
                        </td>
                        <td>
                            <input type="text" id="topic_title" name="topic_title" value="<?php echo ((isset($PROCESSED["topic_title"])) ? html_encode($PROCESSED["topic_title"]) : ""); ?>" maxlength="128" style="width: 95%" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="topic_description" class="form-required">Post Body</label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea id="topic_description" name="topic_description" style="width: 98%; height: 200px" cols="68" rows="12"><?php echo ((isset($PROCESSED["topic_description"])) ? html_encode($PROCESSED["topic_description"]) : ""); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
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
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h2>File Attachments</h2>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
                            <div id="file_list">
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

                                    </li>
                                </ul>

                            </div>

                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <h2>Time Release Options</h2>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table class="date-time">
                                <?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
            <div id="display-upload-status" style="display: none">
                <div style="text-align: left; background-color: #EEEEEE; border: 1px #666666 solid; padding: 10px">
                    <div style="color: #003366; font-size: 18px; font-weight: bold">
                        <img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="File Uploading" title="Please wait while this file is being uploaded." style="vertical-align: middle" /> Please Wait: this file is being uploaded.
                    </div>
                    <br /><br />
                    This can take time depending on your connection speed and the filesize.
                </div>
            </div>
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
		application_log("error", "The provided discussion forum id was invalid [".$RECORD_ID."] (Add Post).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No discussion forum id was provided to post against. (Add Post)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>
