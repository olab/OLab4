<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to upload files to a discussion board of a community.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
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

echo "<h1>File Attachment</h1>";

if ($RECORD_ID) {
    $query = "
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
    $topic_record = $db->GetRow($query);
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
        $query = "SELECT COUNT(*) FROM `community_discussions_files` WHERE `cdtopic_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `file_active` = 1";

        $update_allowed = discussion_topic_module_access($topic_record["cdtopic_id"], "edit-post");

        if ($update_allowed || ($COMMUNITY_MEMBER && $topic_record["allow_member_read"]) || (!$COMMUNITY_MEMBER && $topic_record["allow_troll_read"]) || $COMMUNITY_ADMIN) {
            if ($update_allowed) {
                $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$topic_record["discussion_id"], "title" => limit_chars($topic_record["forum_title"], 16));
                if (!$topic_record["cdtopic_parent"]) {
                    $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record["cdtopic_id"], "title" => limit_chars($topic_record["topic_title"], 16));
                } else {
                    $parent_title = $db->GetOne("SELECT `topic_title` FROM `community_discussion_topics` WHERE `cdtopic_id` = ".$topic_record["cdtopic_parent"]);
                    $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record["cdtopic_parent"], "title" => limit_chars($parent_title, 12)." Reply");
                }
                $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-file&id=".$RECORD_ID, "title" => "Upload File");

                communities_load_rte();
                $file_uploads = array();
                // Error Checking

                switch($STEP) {
                    //this case uploads a new file
                    case 2 :
                        if (isset($_FILES["uploaded_file"]) && is_array($_FILES["uploaded_file"])) {
                            foreach($_FILES["uploaded_file"]["name"] as $tmp_file_id=>$file_name){

                            switch($_FILES["uploaded_file"]["error"][$tmp_file_id]) {
                                case 0 :
                                    if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"][$tmp_file_id])) <= $VALID_MAX_FILESIZE) {
                                        $PROCESSED["file_version"]        = 1;
                                        $PROCESSED["file_mimetype"]        = strtolower(trim($_FILES["uploaded_file"]["type"][$tmp_file_id]));
                                        $PROCESSED["file_filesize"]        = $file_filesize;
                                        $PROCESSED["file_filename"]        = useable_filename(trim($file_name));

                                        if ((!defined("COMMUNITY_STORAGE_DOCUMENTS")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS))) {
                                            $ERROR++;
                                            $ERRORSTR[] = "There is a problem with the document storage directory on the server; the administrators have been informed of this error, please try again later.";

                                            application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS."] does not exist or is not writable.");
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
                            if ((isset($_POST["file_title"])) && ($title = clean_input($_POST["file_title"], array("notags", "trim")))) {
                                $PROCESSED["file_title"] = $title;
                                $file_uploads[$tmp_file_id]["file_title"] = $title;
                            } elseif ((isset($PROCESSED["file_filename"])) && ($PROCESSED["file_filename"])) {
                                $PROCESSED["file_title"] = $PROCESSED["file_filename"];
                                $file_uploads[$tmp_file_id]["file_title"] = $PROCESSED["file_filename"];
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>File Title</strong> field is required.";
                            }

                            /**
                             * Non-Required field "description" / File Description.
                             *
                             */
                            if ((isset($_POST["file_description"][0])) && $description = clean_input($_POST["file_description"][0], array("notags", "trim"))) {
                                $PROCESSED["file_description"] = $description;
                                $file_uploads[$tmp_file_id]["file_description"] = $description;
                            } else {
                                $PROCESSED["file_description"] = "";
                                $file_uploads[$tmp_file_id]["file_description"] = "";
                            }

                            /**
                             * Non-Required field "access_method" / View Method.
                             */
                            if ((isset($_POST["access_method"])) && clean_input($_POST["access_method"], array("int")) == 1) {
                                $PROCESSED["access_method"] = 1;
                                $file_uploads[$tmp_file_id]["access_method"] = 1;
                            } else {
                                $PROCESSED["access_method"] = 0;
                                $file_uploads[$tmp_file_id]["access_method"] = 0;
                            }


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
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
                            }
                            if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
                                $PROCESSED["release_until"]    = (int) $release_dates["finish"];
                            } else {
                                $PROCESSED["release_until"]    = 0;
                            }
                                //no errors
                                //inserts the file if exists


                                if (!$ERROR) {
                                    $PROCESSED["cdiscussion_id"] = $topic_record["cdiscussion_id"];
                                    $PROCESSED["cdtopic_id"] = $topic_record["cdtopic_id"];
                                    $PROCESSED["community_id"]    = $COMMUNITY_ID;
                                    $PROCESSED["proxy_id"]        = $ENTRADA_USER->getActiveId();
                                    $PROCESSED["file_active"]    = 1;
                                    $PROCESSED["updated_date"]    = time();
                                    $PROCESSED["updated_by"]    = $ENTRADA_USER->getID();

                                    unset($PROCESSED["cdfile_id"]);
                                    if ($db->AutoExecute("community_discussions_files", $PROCESSED, "INSERT")) {
                                        if ($FILE_ID = $db->Insert_Id()) {
                                            $PROCESSED["cdfile_id"]    = $FILE_ID;
                                            if ($db->AutoExecute("community_discussion_file_versions", $PROCESSED, "INSERT")) {
                                                if ($VERSION_ID = $db->Insert_Id()) {
                                                    if (communities_discussion_process_file($_FILES["uploaded_file"]["tmp_name"][$tmp_file_id], $VERSION_ID)) {
                                                        $url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$topic_record['cdtopic_parent'];
                                                        $ONLOAD[]        = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                                                        $SUCCESS++;
                                                        $SUCCESSSTR[]    = "You have successfully uploaded ".html_encode($PROCESSED["file_filename"])." (version 1).<br /><br />You will now be redirected to this files page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                                        add_statistic("community:".$COMMUNITY_ID.":discussions", "file_add", "cdfile_id", $VERSION_ID);
                                                        communities_log_history($COMMUNITY_ID, $PAGE_ID, $FILE_ID, "community_history_add_file", 1, $TOPIC_ID);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if (!$SUCCESS) {
                                        /**
                                         * Because there was no success, check if the file_id was set... if it
                                         * was we need to delete the database record :( In the future this will
                                         * be handled with transactions like it's supposed to be.
                                         */
                                        if ($FILE_ID) {
                                            $query    = "DELETE FROM `community_discussions_files` WHERE `cdfile_id` = ".$db->qstr($FILE_ID)." AND `cdiscussion_id` = ".$db->qstr($PROCESSED["cdiscussion_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
                                            @$db->Execute($query);

                                            /**
                                             * Also delete the file version, again, hello transactions.
                                             */
                                            if ($VERSION_ID) {
                                                $query    = "DELETE FROM `community_discussion_file_versions` WHERE `cdfversion_id` = ".$db->qstr($VERSION_ID)." AND `cdfile_id` = ".$db->qstr($FILE_ID)." AND `cdiscussion_id` = ".$db->qstr($PROCESSED["cdiscussion_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
                                                @$db->Execute($query);
                                            }
                                        }
                                        $ERROR++;
                                        $ERRORSTR[]    = "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";
                                        application_log("error", "Failed to move the uploaded Community file to the storage directory [".COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."/".$VERSION_ID."].");
                                    }
                                }
                                if ($ERROR) {
                                    $STEP = 1;
                                }
                            }
                        } else {
                            $ERROR++;
                            $ERRORSTR[]     = "To upload a file to this folder you must select a local file from your computer.";
                        }
                    break;
                    case 3 :
                    //update file title and description here.

                        /**
                         * Required field "title" / File Title.
                         */
                        if ((isset($_POST["file_title"])) && ($title = clean_input($_POST["file_title"], array("notags", "trim")))) {
                            $PROCESSED["file_title"] = $title;
                            $file_uploads[$tmp_file_id]["file_title"] = $title;
                        } elseif ((isset($PROCESSED["file_filename"])) && ($PROCESSED["file_filename"])) {
                            $PROCESSED["file_title"] = $PROCESSED["file_filename"];
                            $file_uploads[$tmp_file_id]["file_title"] = $PROCESSED["file_filename"];
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>File Title</strong> field is required.";
                        }

                        /**
                         * Non-Required field "description" / File Description.
                         *
                         */
                        if ((isset($_POST["file_description"])) && $description = clean_input($_POST["file_description"], array("notags", "trim"))) {
                            $PROCESSED["file_description"] = $description;
                            $file_uploads[$tmp_file_id]["file_description"] = $description;
                        } else {
                            $PROCESSED["file_description"] = "";
                            $file_uploads[$tmp_file_id]["file_description"] = "";
                        }

                        /**
                         * Non-Required field "access_method" / View Method.
                         */
                        if ((isset($_POST["access_method"])) && clean_input($_POST["access_method"], array("int")) == 1) {
                            $PROCESSED["access_method"] = 1;
                            $file_uploads[$tmp_file_id]["access_method"] = 1;
                        } else {
                            $PROCESSED["access_method"] = 0;
                            $file_uploads[$tmp_file_id]["access_method"] = 0;
                        }


                        if (isset($_POST["cdfile_id"]) && (int)($_POST["cdfile_id"])) {
                              $PROCESSED["cdfile_id"] = $_POST["cdfile_id"];
                        }

                        if (!$ERROR) {
                            $PROCESSED["cdiscussion_id"]    = $topic_record["cdiscussion_id"];
                            $PROCESSED["cdtopic_id"]        = $topic_record["cdtopic_id"];
                            $PROCESSED["community_id"]      = $COMMUNITY_ID;
                            $PROCESSED["proxy_id"]          = $ENTRADA_USER->getActiveId();
                            $PROCESSED["file_active"]       = 1;
                            $PROCESSED["updated_date"]      = time();
                            $PROCESSED["updated_by"]        = $ENTRADA_USER->getID();

                            if ($db->AutoExecute("community_discussions_files", $PROCESSED, "UPDATE", "`cdfile_id` = ".$db->qstr($PROCESSED["cdfile_id"])." AND `cdiscussion_id` = ".$db->qstr($PROCESSED["cdiscussion_id"]). "AND `community_id` = ".$db->qstr($PROCESSED["community_id"]))) {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong> to the community."), $PROCESSED["file_title"]), "success", $MODULE);

                                add_statistic("community:" . $COMMUNITY_ID . ":discussions", "file-edit", "cdfile_id", $PROCESSED["cdfile_id"]);
                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $FILE_ID, "community_history_add_file", 1, $TOPIC_ID);
                                if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                    community_notify($COMMUNITY_ID, $FILE_ID, "file-db", COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-file&id=" . $FILE_ID, $RECORD_ID, $PROCESSES["release_date"]);
                                }

                                $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-post&id=" . $RECORD_ID;
                                header("Location: " . $url);
                                exit;
                            } else {
                                add_error($translate->_("There was a problem updating this forum into the system. The MEdTech Unit was informed of this error; please try again later."));
                                application_log("error", "There was an error updating an forum. Database said: ".$db->ErrorMsg());
                                $STEP = 1;
                            }
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
                            if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                community_notify($COMMUNITY_ID, $FILE_ID, "file-db", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$FILE_ID, $RECORD_ID, $PROCESSES["release_date"]);
                            }
                        }
                        if ($ERROR) {
                            echo display_error();
                        }
                    break;
                    case 1 :
                    default :
                        if(count($file_uploads)<1){
                            $file_uploads[] = array();
                        }
                        if ($ERROR) {
                            echo display_error();
                        }
                        if ($NOTICE) {
                            echo display_notice();
                        }

                        $path = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?";
                        echo communities_discussions_files_subnavigation($topic_record["cdtopic_id"],"edit-file",$path );

                      $query = "  SELECT a.* , b.*
                            FROM `community_discussion_file_versions` AS b
                            JOIN `community_discussions_files` AS a
                            ON a.`cdfile_id` = b.`cdfile_id`
                            WHERE a.`cdtopic_id` = ".$topic_record["cdtopic_id"]."
                            AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                            AND a.`file_active` = '1'
                            ORDER BY b.`cdfversion_id` DESC
                            LIMIT 1";

                        $file_record = $db->GetRow($query);
                        if (!empty($file_record)) {
                            ?>
                            <script type="text/javascript">
                                jQuery(document).ready(function() {
                                    jQuery("#deleteAttachment").click(function() {
                                        fileID = jQuery("#cdfile_id").val();
                                        fileDelete(fileID);
                                    });
                                });
                                
                                function fileDelete(id) {
                                    Dialog.confirm('Do you really wish to remove the '+ $('file_title').innerHTML +' file?<br /><br />If you confirm this action, you will be deactivating this file.',
                                        {
                                                id:                     'requestDialog',
                                                width:			350,
                                                height:			165,
                                                title:			'Delete Confirmation',
                                                className:		'medtech',
                                                okLabel:		'Yes',
                                                cancelLabel:            'No',
                                                closable:		'true',
                                                buttonClass:            'btn',
                                                ok:			function(win) {
                                                                            window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.':'.$PAGE_URL.'?section=delete-file&id='?>'+id;
                                                                            return true;
                                                                        }
                                        }
                                    );
                                }
                            </script>

                            <form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-file&amp;id=<?php echo $RECORD_ID; ?>&amp;step=3" method="post" enctype="multipart/form-data">
                                <div class="container-file">
                                    <h2>Discussion File Attachment Details</h2>
                                    <ul class="container-file-group">
                                        <li>
                                            <h3 class="db_file_col1">Title: </h3>
                                            <span class="db_file_col2 db_file_col2a">
                                                <input type="text" id="file_title" name="file_title" value="<?php echo ((isset($file_record["file_title"])) ? html_encode($file_record["file_title"]) : ""); ?>" maxlength="128" style="width: 95%" />
                                            </span>
                                        </li>
                                        <li>
                                            <h3 class="db_file_col1">Name: </h3>
                                            <span class="db_file_col2 db_file_col2a">
                                                <?php echo $file_record["file_filename"]?>
                                            </span>
                                        </li>
                                        <li>
                                            <h3 class="db_file_col1">Description: </h3>
                                            <span class="db_file_col2 db_file_col2a">
                                                <textarea id="file_description" name="file_description"><?php echo ((isset($file_record["file_description"])) ? html_encode($file_record["file_description"]) : ""); ?></textarea>
                                            </span>
                                        </li>
                                        <li>
                                            <h3 class="db_file_col1">File Size: </h3>
                                            <span class="db_file_col2 db_file_col2a">
                                                <?php echo $file_record["file_filesize"]?>
                                            </span>
                                        </li>

                                    </ul>
                                    <input type="hidden" name="cdfile_id" id="cdfile_id" value="<?php echo $file_record["cdfile_id"]?>">
                                    <input type="submit" class="btn btn-primary button-right clearfix" value="<?php echo $translate->_("global_button_update"); ?>" />
                                    <input id="deleteAttachment" type="button" class="btn btn-primary button-right clearfix" value="Delete Attachment"/>
                                    <input type="button" class="btn button-right" value="<?php echo $translate->_("global_button_cancel"); ?>" onclick="window.location='<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID; ?>'" />
                                </div>
                            </form>
                        <?php
                        } else {
                        ?>
                            <form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-file&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data">
                            <div class="container-file">
                                <input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
                                <div id="file_list">
                                    <?php foreach($file_uploads as $tmp_file_id=>$file_upload) {
                                if (!$file_upload["success"]) {
                                        ?>
                                            <div id="file_<?php echo $tmp_file_id;?>" class="file-upload">
                                                <h2>File Details</h2>
                                                <ul class="container-file-group">
                                                    <li>
                                                        <label for="uploaded_file" class="form-required db_file_col1">Select Local File</label>
                                                        <span class="db_file_col2">
                                                            <input type="file" id="uploaded_file_<?php echo $tmp_file_id;?>" name="uploaded_file[<?php echo $tmp_file_id;?>]" onchange="fetchFilename(<?php echo $tmp_file_id;?>)" />
                                                            <div class="content-small" style="margin-top: 5px">
                                                                <strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
                                                            </div>
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <label for="file_title" class="form-required db_file_col1">File Title</label>
                                                        <span class="db_file_col2">
                                                            <input type="text" id="file_title" name="file_title" value="<?php echo ((isset($PROCESSED["file_title"])) ? html_encode($PROCESSED["file_title"]) : ""); ?>" maxlength="128" style="width: 95%" />
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <label for="file_description" class="db_file_col1">File Description</label>
                                                        <span class="db_file_col2">
                                                            <textarea id="file_<?php echo $tmp_file_id;?>_description" name="file_description[<?php echo $tmp_file_id;?>]" style="width: 95%; height: 60px;max-width: 300px;min-width: 300px;" cols="50" rows="5">
                                                                <?php echo ((isset($file_upload["file_description"])) ? html_encode($file_upload["file_description"]) : ""); ?>
                                                            </textarea>
                                                        </span>
                                                    </li>
                                                    <?php
                                                    if (($LOGGED_IN && $discussion_record["allow_troll_read"]) || ($LOGGED_IN && $COMMUNITY_MEMBER && $discussion_record["allow_member_read"]) || $COMMUNITY_ADMIN) {
                                                        $is_admin = true;
                                                    } else {
                                                        $is_admin = false;
                                                    }
                                            if ($is_admin) { ?>
                                                        <li>
                                                            <h3 class="db_file_col1">Access Method: </h3>
                                                            <span class="db_file_col2 db_file_col2a">
                                                                <input type="radio" id="access_method_0_<?php echo $tmp_file_id;?>" name="access_method[<?php echo $tmp_file_id;?>]" value="0" style="vertical-align: middle" checked/>
                                                                <span class="file-checkbox-text">Download this file to their computer first, then open it.</span>
                                                                <br/>
                                                                <input type="radio" id="access_method_1_<?php echo $tmp_file_id;?>" name="access_method[<?php echo $tmp_file_id;?>]" value="1" style="vertical-align: middle"<?php echo (((isset($file_upload["access_method"])) && ((int) $file_upload["access_method"]) == 1) ? " checked" : ""); ?> />
                                                                <span class="file-checkbox-text">Attempt to view it directly in the web-browser.</span>
                                                            </span>
                                                        </li>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            </ul>
                                            <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <h2>Time Release Options</h2>
                                <div><?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), false, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?></div>
                                <div><?php echo generate_calendars("release", "", false, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?></div>
                                <input type="submit" class="btn btn-primary button-right clearfix" value="<?php echo $translate->_("global_button_update"); ?>" />
                                <input type="button" class="btn button-right" value="<?php echo $translate->_("global_button_cancel"); ?>" onclick="window.location='<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID; ?>'" />
                            </div>
                            </form>
                        <?php
                        break;
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

            $ERROR++;
            $ERRORSTR[] = "Your access level only allows you to upload one file and revisions of it. Any additional files can be uploaded as a new revision of that file without overwriting the current file.";

            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_notice();
            }
        }
    } else {
        application_log("error", "The provided folder id was invalid [".$RECORD_ID."] (Upload File).");

        header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
        exit;
    }
} else {
    application_log("error", "No folder id was provided to upload into. (Upload File)");

    header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
    exit;
}
?>
