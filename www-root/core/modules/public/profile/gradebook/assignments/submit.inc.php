<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to upload files to a specific folder of a community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 * 
*/

if((!defined("IN_PUBLIC_ASSIGNMENTS"))) {
	exit;
} 

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Submit Assignment</h1>\n";

if ($RECORD_ID) {
	$query			= "SELECT * FROM `assignments`
	                    WHERE `assignment_id` = ".$db->qstr($RECORD_ID)."
	                    AND `assignment_active` = '1'
	                    AND (`release_date` = 0 OR `release_date` < ".$db->qstr(time()).")
	                    AND (`release_until` = 0 OR `release_until` > ".$db->qstr(time()).")";
	$folder_record	= $db->GetRow($query);
	if ($folder_record){// || true) {
		$course_ids = groups_get_enrolled_course_ids($ENTRADA_USER->getID());
		if(in_array($folder_record["course_id"],$course_ids)){
			if ($folder_record["assessment_id"] == 0) {
				$permitted = true;
			} else {
				$query = "	SELECT * FROM `assessments` AS a 
							JOIN `groups` AS b 
							ON a.`cohort` = b.`group_id` 
							JOIN `group_members` AS c 
							ON b.`group_id` = c.`group_id` 
							WHERE a.`assessment_id` = ".$db->qstr($folder_record["assessment_id"])."
							AND a.`active` = 1
							AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
				$permitted = $db->GetRow($query);
			}
			if ($permitted) {				
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-file&assignment_id=".$RECORD_ID, "title" => "Upload File");

				$file_uploads = array();
				// Error Checking
				switch($STEP) {
					case 2 :
						if (isset($_FILES["uploaded_file"])) {
							switch($_FILES["uploaded_file"]["error"]) {
								case 0 :
									if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"])) <= $VALID_MAX_FILESIZE) {
										if (!DEMO_MODE) {
											$PROCESSED["file_version"]		= 1;
											$PROCESSED["file_mimetype"]		= strtolower(trim($_FILES["uploaded_file"]["type"]));
											$PROCESSED["file_filesize"]		= $file_filesize;
											$PROCESSED["file_filename"]		= useable_filename(trim($_FILES["uploaded_file"]["name"]));
										} else {
											$PROCESSED["file_version"]		= 1;
											$PROCESSED["file_mimetype"]		= filetype(DEMO_ASSIGNMENT);
											$PROCESSED["file_filesize"]		= filesize(DEMO_ASSIGNMENT);
											$PROCESSED["file_filename"]		= basename(DEMO_ASSIGNMENT);
										}

										if ((!defined("COMMUNITY_STORAGE_DOCUMENTS")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS))) {
											add_error("There is a problem with the document storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.");

											application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS."] does not exist or is not writable.");
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
									add_error("You did not select a file from your computer to upload. Please select a local file and try again.");
								break;
								case 6 :
								case 7 :
									add_error("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.");

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
						} elseif ((isset($PROCESSED["file_filename"])) && ($PROCESSED["file_filename"])) {
							$PROCESSED["file_title"] = $PROCESSED["file_filename"];
						} else {
							add_error("The <strong>File Title</strong> field is required.");
						}

						/**
						 * Non-Required field "description" / File Description.
						 *
						 */
						if ((isset($_POST["file_description"])) && $description = clean_input($_POST["file_description"], array("notags", "trim"))) {
							$PROCESSED["file_description"] = $description;
							$file_uploads["file_description"] = $description;
						} else {
							$PROCESSED["file_description"] = "";
							$file_uploads["file_description"] = "";
						}


						if (!$ERROR) {
							$PROCESSED["assignment_id"]		= $RECORD_ID;
							$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
							$PROCESSED["file_active"]	= 1;
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

							if ($db->AutoExecute("assignment_files", $PROCESSED, "INSERT")) {
								if ($FILE_ID = $db->Insert_Id()) {
									$PROCESSED["afile_id"]	= $FILE_ID;
									if ($db->AutoExecute("assignment_file_versions", $PROCESSED, "INSERT")) {
										if ($VERSION_ID = $db->Insert_Id()) {

											if (assignments_process_file($_FILES["uploaded_file"]["tmp_name"], $VERSION_ID)) {										

												$url = ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$RECORD_ID;
												$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

												$SUCCESS++;
												if (!DEMO_MODE) {
													$SUCCESSSTR[]	= "You have successfully uploaded ".html_encode($PROCESSED["file_filename"])." (version 1).<br /><br />You will now be redirected to this files page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
												} else {
													$SUCCESSSTR[]	= "Entrada is in demo mode therefore the Entrada demo assignment file was used for this import instead of the file you attempted to upload.<br /><br />You will now be redirected to this files page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
												}
												add_statistic("assignment:".$RECORD_ID, "file_add", "afile_id", $FILE_ID);
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
									$query	= "DELETE FROM `assignment_files` WHERE `afile_id` = ".$db->qstr($FILE_ID)." AND `assignment_id` = ".$db->qstr($RECORD_ID)." AND LIMIT 1";
									@$db->Execute($query);

									/**
									 * Also delete the file version, again, hello transactions.
									 */
									if ($VERSION_ID) {
										$query	= "DELETE FROM `assignment_file_versions` WHERE `afversion_id` = ".$db->qstr($VERSION_ID)." AND `afile_id` = ".$db->qstr($FILE_ID)." AND `assignment_id` = ".$db->qstr($RECORD_ID)." LIMIT 1";
										@$db->Execute($query);
									}
								}


								add_error("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.");

								application_log("error", "Failed to move the uploaded Community file to the storage directory [".COMMUNITY_STORAGE_DOCUMENTS."/".$VERSION_ID."].");
							}
						}

							if ($ERROR) {
								$STEP = 1;
							}


						} else {
							add_error("To upload a file to this folder you must select a local file from your computer.");
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
                        
                        $max_files = $db->GetOne("SELECT `max_file_uploads` FROM `assignments` WHERE `assignment_id`=".$db->qstr($RECORD_ID));
                        
						?>


						<form id="upload-file-form" action="<?php echo ENTRADA_URL."/profile/gradebook/assignments?section=submit&assignment_id=".$RECORD_ID."&step=2"; ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
						<table style="width: 420px;" cellspacing="0" cellpadding="2" border="0" summary="Upload File">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 20%" />
							<col style="width: 77%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="3" style="padding-top: 15px; text-align: right">
                                    <p>You may upload <?php echo $max_files; ?> 
                                        file<?php echo $max_files !== 1 ? 's' : ''; ?> for this assignment.</p>
									<div id="display-upload-button">
										<input type="button" class="btn btn-primary" value="Upload File" onclick ="uploadFile()" />
									</div>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td colspan="3">
									<div id="file_list">
										<div id="file_1" class="file-upload">
											<table>
												<tr>
													<td colspan="3"><h2>File Details</h2></td>
												</tr>
												<tr>
													<td colspan="2" style="vertical-align: top"><label for="uploaded_file" class="form-required">Select Local File</label></td>
													<td style="vertical-align: top">
														<input type="file" id="uploaded_file_1" name="uploaded_file" onchange="fetchFilename(1)" />
														<div class="content-small" style="margin-top: 5px">
															<strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
														</div>
													</td>
												</tr>
												<tr>
													<td colspan="3">&nbsp;</td>
												</tr>
												<tr>
													<td colspan="2"><label for="file_title" class="form-required">File Title</label></td>
													<td><input type="text" id="file_1_title" name="file_title" value="<?php echo ((isset($PROCESSED["file_title"])) ? html_encode($PROCESSED["file_title"]) : ""); ?>" maxlength="64" style="width: 95%" /></td>
												</tr>
												<tr>
													<td colspan="2" style="vertical-align: top"><label for="file_description" class="form-nrequired">File Description</label></td>
													<td style="vertical-align: top">
														<textarea id="file_<?php echo $tmp_file_id;?>_description" name="file_description" style="width: 95%; height: 60px;max-width: 300px;min-width: 300px;" cols="50" rows="5"><?php echo ((isset($PROCESSED["file_description"])) ? html_encode($PROCESSED["file_description"]) : ""); ?></textarea>
													</td>
												</tr>
												<tr>
													<td colspan="3">&nbsp;</td>
												</tr>
											</table>
										</div>
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
				add_error('You are not authorized to upload to this assignment. If you think you are receiving this message in error, please contact the coordinator for the course.');
				if ($ERROR) {
					echo display_error();
				}
				if ($NOTICE) {
					echo display_notice();
				}
			}
		}else{
			echo display_error("You do not have authorization to view this resource.");
		}
	} else {
		application_log("error", "Invalid assignment id was provided. (Assignment Submit)");
		add_error('Invalid assignment ID provided. This assignment may have already expired, or may have been deleted.');
		echo display_error();
		//header("Location: ".ENTRADA_URL."/profile/gradebook/assignments");
		exit;
	}
} else {
	application_log("error", "No assignment id was provided to submit to. (Assignment Submit)");
	add_error("No assignment id was provided to submit to.");
	echo display_error();
	//header("Location: ".ENTRADA_URL."/profile/gradebook/assignments");
	exit;
}
