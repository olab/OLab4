<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to allow people to upload newer revisions of an existing document.
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

echo "<h1>Upload Revised File</h1>\n";

if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`folder_title`, b.`allow_member_read`, 
					b.`allow_troll_read`, b.`allow_member_upload`, b.`allow_troll_upload`
					FROM `community_share_files` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`csfile_id` = ".$db->qstr($RECORD_ID)."
					AND a.`file_active` = '1'
					AND b.`folder_active` = '1'";
	$file_record	= $db->GetRow($query);
	if ($file_record) {
		if ((int) $file_record["file_active"]) {
			if (shares_file_module_access($RECORD_ID, "add-revision")) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$file_record["cshare_id"], "title" => limit_chars($file_record["folder_title"], 25));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$RECORD_ID, "title" => limit_chars($file_record["file_title"], 25));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-revision&amp;id=".$RECORD_ID, "title" => "Upload Revised File");

				// Error Checking
				switch($STEP) {
					case 2 :
						if (isset($_FILES["uploaded_file"])) {
							switch($_FILES["uploaded_file"]["error"]) {
								case 0 :
                                    if (strpos($_FILES["uploaded_file"]["name"], ".") === false) {
                                        $ERROR++;
                                        $ERRORSTR[] = "You cannot upload a file without an extension (.doc, .ppt, etc).";

                                        application_log("error", "User {$ENTRADA_USER->getID()} uploaded a file to shares without an extension.");
                                    } else {
									if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"])) <= $VALID_MAX_FILESIZE) {
										$query	= "
												SELECT `file_version`
												FROM `community_share_file_versions`
												WHERE `csfile_id` = ".$db->qstr($RECORD_ID)."
												AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])."
												AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
												AND `file_active` = '1'
												ORDER BY `file_version` DESC
												LIMIT 0, 1";
										$result	= $db->GetRow($query);
										if ($result) {
											$PROCESSED["file_version"] = ($result["file_version"] + 1);
										} else {
											$PROCESSED["file_version"] = 1;
										}

                                            $finfo = new finfo(FILEINFO_MIME);
                                            $type = $finfo->file($_FILES["uploaded_file"]["tmp_name"]);
                                            $type_array = explode(";", $type);
                                            $mimetype = $type_array[0];
										$PROCESSED["file_mimetype"]		= strtolower(trim($_FILES["uploaded_file"]["type"]));
                                            switch($PROCESSED["file_mimetype"]) {
                                                case "application/x-forcedownload":
                                                case "application/octet-stream":
                                                case "\"application/octet-stream\"":
                                                case "application/download":
                                                case "application/force-download":
                                                    $PROCESSED["file_mimetype"] = $mimetype;
                                                    break;
                                            }
                                            
                                            
										$PROCESSED["file_filesize"]		= $file_filesize;
										$PROCESSED["file_filename"]		= useable_filename(trim($_FILES["uploaded_file"]["name"]));

										if ((!defined("COMMUNITY_STORAGE_DOCUMENTS")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS))) {
											$ERROR++;
											$ERRORSTR[] = "There is a problem with the document storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.";

											application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS."] does not exist or is not writable.");
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
									$ERRORSTR[]	= "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
								break;
								case 4 :
									$ERROR++;
									$ERRORSTR[]	= "You did not select a file from your computer to upload. Please select a local file and try again.";
								break;
								case 6 :
								case 7 :
									$ERROR++;
									$ERRORSTR[]	= "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";

									application_log("error", "Community file revision upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
								break;
								default :
									application_log("error", "Unrecognized file upload error number [".$_FILES["filename"]["error"]."].");
								break;
							}
						} else {
							$ERROR++;
							$ERRORSTR[]	 = "To upload a revised version of this file you must select a local file from your computer.";
						}

						if (!$ERROR) {
							$PROCESSED["csfile_id"]		= $RECORD_ID;
							$PROCESSED["cshare_id"]		= $file_record["cshare_id"];
							$PROCESSED["community_id"]	= $COMMUNITY_ID;
							$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
							$PROCESSED["file_active"]	= 1;
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

							if ($db->AutoExecute("community_share_file_versions", $PROCESSED, "INSERT")) {
								if ($VERSION_ID = $db->Insert_Id()) {
									if (communities_shares_process_file($_FILES["uploaded_file"]["tmp_name"], $VERSION_ID)) {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added a new revision to <strong>%s</strong>."), $file_record["file_title"]), "success", $MODULE);
										add_statistic("community:".$COMMUNITY_ID.":shares", "revision_add", "csfversion_id", $VERSION_ID);
										communities_log_history($COMMUNITY_ID, $PAGE_ID, $VERSION_ID, "community_history_add_file_revision", 1, $RECORD_ID);
										if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
											community_notify($COMMUNITY_ID, $RECORD_ID, "file-revision", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$RECORD_ID, $RECORD_ID);
										}

										$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-file&id=" . $RECORD_ID;
										header("Location: " . $url);
                                        exit;
									}
								}
							}

							if (!$SUCCESS) {
								/**
								 * Because there was no success, check if the file_id was set... if it
								 * was we need to delete the database record :( In the future this will
								 * be handled with transactions like it's supposed to be.
								 */
								if ($VERSION_ID) {
									$query	= "DELETE FROM `community_share_file_versions` WHERE `csfversion_id` = ".$db->qstr($VERSION_ID)." AND `csfile_id` = ".$db->qstr($RECORD_ID)." AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
									@$db->Execute($query);
								}

								$ERROR++;
								$ERRORSTR[]	= "Unable to store the revised file on the server; the MEdTech Unit has been informed of this error, please try again later.";

								application_log("error", "Failed to move the uploaded Community revised file to the storage directory [".COMMUNITY_STORAGE_DOCUMENTS."/".$VERSION_ID."].");
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						$PROCESSED = $file_record;
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
						<form id="upload-file-form" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-revision&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data">
						<input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Upload Revised File">
						<colgroup>
							<col style="width: 20%" />
							<col style="width: 80%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="2" style="padding-top: 15px; text-align: right">
									<div id="display-upload-button">
										<input type="button" class="btn btn-primary" value="Upload" onclick="uploadFile()" />
									</div>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td colspan="2">
									<h2>File Details <small><?php echo ((isset($PROCESSED["file_title"])) ? html_encode($PROCESSED["file_title"]) : ""); ?></small></h2>
								</td>
							</tr>
							<tr>
								<td style="vertical-align: top">
									<label for="uploaded_file" class="form-nrequired">New File Version</label>
								</td>
								<td style="vertical-align: top">
									<input type="file" id="uploaded_file" name="uploaded_file" />
									<div class="content-small">
										<strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
									</div>
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
			$NOTICE++;
			$NOTICESTR[] = "The file that you are trying to add a revision to was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $file_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $file_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The file record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to add a revision.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The file id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided file id was invalid [".$RECORD_ID."] (Add Revision).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid file id to proceed.";

	echo display_error();

	application_log("error", "No file id was provided to add a revision to. (Add Revision)");
}
?>