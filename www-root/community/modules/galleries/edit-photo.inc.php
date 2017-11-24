<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit an existing photo within a gallery. This action is available
 * to either the original photo uploader or to any community administrator.
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

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/galleries.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Edit Photo</h1>\n";

if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`gallery_title`, b.`gallery_cgphoto_id`, b.`admin_notifications`, c.`notify_active`
					FROM `community_gallery_photos` AS a
					LEFT JOIN `community_galleries` AS b
					ON a.`cgallery_id` = b.`cgallery_id`
					LEFT JOIN `community_notify_members` AS c
					ON a.`cgphoto_id` = c.`record_id`
					AND c.`community_id` = a.`community_id`
					AND c.`notify_type` = 'photo-comment'
					AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cgphoto_id` = ".$db->qstr($RECORD_ID)."
					AND a.`photo_active` = '1'
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)." 
					AND b.`gallery_active` = '1'";
	$photo_record	= $db->GetRow($query);
	if ($photo_record) {
		if (isset($photo_record["notify_active"])) {
			$notifications = ($photo_record["notify_active"] ? true : false);
			if ($photo_record["notify_active"] != null) {
				$notify_record_exists = true;
			}
		} else {
			$notifications = false;
			$notify_record_exists = false;
		}
		if ((int) $photo_record["photo_active"]) {
			if (galleries_photo_module_access($RECORD_ID, "edit-photo")) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$photo_record["cgallery_id"], "title" => limit_chars($photo_record["gallery_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=".$RECORD_ID, "title" => limit_chars($photo_record["photo_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-photo&amp;id=".$RECORD_ID, "title" => "Edit Photo");

				/**
				 * Whether or not to process an updated photo.
				 */
				$update_photo_file = false;

				// Error Checking
				switch($STEP) {
					case 2 :
						/**
						 * Not-Required (for edit) field "photo_file" / Select Local Photo.
						 */
						if (isset($_FILES["photo_file"])) {
							switch($_FILES["photo_file"]["error"]) {
								case 0 :
									if (@in_array($photo_mimetype = strtolower(trim($_FILES["photo_file"]["type"])), array_keys($VALID_MIME_TYPES))) {
										if (($photo_filesize = (int) trim($_FILES["photo_file"]["size"])) <= $VALID_MAX_FILESIZE) {
											$update_photo_file				= true;
											$PROCESSED["photo_mimetype"]	= $photo_mimetype;
											$PROCESSED["photo_filesize"]	= $photo_filesize;
											$PROCESSED["photo_filename"]	= useable_filename(trim($_FILES["photo_file"]["name"]));
											$photo_file_extension			= strtoupper($VALID_MIME_TYPES[strtolower(trim($_FILES["photo_file"]["type"]))]);

											if ((!defined("COMMUNITY_STORAGE_GALLERIES")) || (!@is_dir(COMMUNITY_STORAGE_GALLERIES)) || (!@is_writable(COMMUNITY_STORAGE_GALLERIES))) {
												$ERROR++;
												$ERRORSTR[] = "There is a problem with the gallery storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.";

												application_log("error", "The community gallery storage path [".COMMUNITY_STORAGE_GALLERIES."] does not exist or is not writable.");
											}
										}
									} else {
										$ERROR++;
										$ERRORSTR[] = "The file that you have uploaded does not appear to be a valid image. Please ensure that you upload a JPEG, GIF or PNG file.";
									}
								break;
								case 1 :
								case 2 :
									$ERROR++;
									$ERRORSTR[] = "The photo that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the photo smaller and try again.";
								break;
								case 3 :
									$ERROR++;
									$ERRORSTR[]	= "The photo that was uploaded did not complete the upload process or was interupted; please try again.";
								break;
								case 4 :
									continue;
								break;
								case 6 :
								case 7 :
									$ERROR++;
									$ERRORSTR[]	= "Unable to store the new photo file on the server; the MEdTech Unit has been informed of this error, please try again later.";

									application_log("error", "Community photo file upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
								break;
								default :
									application_log("error", "Unrecognized photo file upload error number [".$_FILES["filename"]["error"]."].");
								break;
							}
						}

						/**
						 * Required field "title" / Photo Title.
						 */
						if ((isset($_POST["photo_title"])) && ($title = clean_input($_POST["photo_title"], array("notags", "trim")))) {
							$PROCESSED["photo_title"] = $title;
						} elseif ((isset($PROCESSED["photo_filename"])) && ($PROCESSED["photo_filename"])) {
							$PROCESSED["photo_title"] = $PROCESSED["photo_filename"];
						} else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Photo Title</strong> field is required.";
						}

						/**
						 * Non-Required field "description" / Photo Description.
						 *
						 */
						if ((isset($_POST["photo_description"])) && ($description = clean_input($_POST["photo_description"], array("notags", "trim")))) {
							$PROCESSED["photo_description"] = $description;
						} else {
							$PROCESSED["photo_description"] = "";
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
						
						/**
						 * Email Notificaions.
						 */
						if(isset($_POST["enable_notifications"])) {
							$notifications = $_POST["enable_notifications"];
						} else {
							$notifications = 0;
						}		

						if (!$ERROR) {
							$PROCESSED["updated_date"]		= time();
							$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

							if ($db->AutoExecute("community_gallery_photos", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cgphoto_id` = ".$db->qstr($RECORD_ID))) {
								if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
									if ($PROCESSED["release_date"] != $photo_record["release_date"]) {
										$notification = $db->GetRow("SELECT * FROM `community_notifications` WHERE `record_id` = ".$db->qstr($RECORD_ID)." AND `type` = 'event'");
										if ($notification) {
											$notification["release_time"] = $PROCESSED["release_date"];
											$db->AutoExecute("community_notifications", $notification, "UPDATE", "`cnotification_id` = ".$db->qstr($notification["cnotification_id"]));
										}
									}
									if (isset($notifications) && $notify_record_exists) {
										$db->Execute("UPDATE `community_notify_members` SET `notify_active` = '".($notifications ? "1" : "0")."' WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `record_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `notify_type` = 'photo-comment'");
									} elseif (isset($notifications) && !$notify_record_exists) {
										$db->Execute("INSERT INTO `community_notify_members` (`proxy_id`, `record_id`, `community_id`, `notify_type`, `notify_active`) VALUES (".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($RECORD_ID).", ".$db->qstr($COMMUNITY_ID).", 'photo-comment', '".($notifications ? "1" : "0")."')");
									}
								}
								/**
								 * Check if the actual file needs to be updated, if it does
								 * process it using the communities_galleries_process_photo function.
								 */	
								if ($update_photo_file) {
									if (!communities_galleries_process_photo($_FILES["photo_file"]["tmp_name"], $RECORD_ID)) {
										$ERROR++;
										$ERRORSTR[] = "Unable to store the new photo file on the server or generate a valid thumbnail; the MEdTech Unit has been informed of this error, please try again later.";

										application_log("error", "Failed to replace edited photo image. communities_galleries_process_photo failed.");
									}
								}

								/**
								 * Check if the main gallery thumbnail image needs to be changed
								 * from it's current state.
								 */
								if ((bool) $COMMUNITY_ADMIN) {
									if ((isset($_POST["gallery_cgphoto_id"])) && ((int) trim($_POST["gallery_cgphoto_id"]) === 1) && ((int) $photo_record["gallery_cgphoto_id"] != (int) $RECORD_ID)) {
										if (!$db->AutoExecute("community_galleries", array("gallery_cgphoto_id" => $RECORD_ID), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($photo_record["cgallery_id"]))) {
											$ERROR++;
											$ERRORSTR[] = "Unable to set this photo as the gallery thumbnail; the MEdTech Unit has been informed of this error, please try again later.";

											application_log("error", "Failed to set this photo as the gallery thumbnail. Database said: ".$db->ErrorMsg());
										}
									} elseif (((int) $photo_record["gallery_cgphoto_id"] == (int) $RECORD_ID) && ((!isset($_POST["gallery_cgphoto_id"])) || ((int) trim($_POST["gallery_cgphoto_id"]) != 1))) {
										if (!$db->AutoExecute("community_galleries", array("gallery_cgphoto_id" => 0), "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($photo_record["cgallery_id"]))) {
											$ERROR++;
											$ERRORSTR[] = "Unable to unset this photo as the gallery thumbnail; the MEdTech Unit has been informed of this error, please try again later.";

											application_log("error", "Failed to unset this photo as the gallery thumbnail. Database said: ".$db->ErrorMsg());
										}
									}
								}
								if (!$ERROR) {
									Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated the <strong>%s</strong> photo."), $PROCESSED["photo_title"]), "success", $MODULE);

									add_statistic("community:" . $COMMUNITY_ID . ":galleries", "photo_edit", "cgphoto_id", $RECORD_ID);
									communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_photo", 1, $photo_record["cgallery_id"]);

									$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-photo&id=" . $RECORD_ID;
									header("Location: " . $url);
									exit;
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "Unable to update this photo at this time; the MEdTech Unit has been informed of this error, please try again later.";

								application_log("error", "Failed to update a photo. Database said: ".$db->ErrorMsg());
							}
						}

						if ($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						$PROCESSED = $photo_record;
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
						<form id="upload-photo-form" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-photo&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data" accept="<?php echo ((@is_array($VALID_MIME_TYPES)) ? implode(",", array_keys($VALID_MIME_TYPES)) : ""); ?>">
							<input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Edit Photo">
								<colgroup>
									<col style="width: 20%" />
									<col style="width: 80%" />
								</colgroup>
								<tfoot>
									<tr>
										<td colspan="2" style="padding-top: 15px; text-align: right">
											<div id="display-upload-button">
												<input type="button" class="btn btn-primary" value="Save" onclick="uploadPhoto()" />
											</div>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<tr>
										<td colspan="2"><h2>Current Photo</h2></td>
									</tr>
									<tr>
										<td colspan="2" style="text-align: center">
											<?php
											if ((@file_exists(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID)) && (@is_readable(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID))) {
												$photo_url	= COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&amp;id=".$RECORD_ID."&amp;render=image";
												list($width, $height) = @getimagesize(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID);
											} else {
												$photo_url	= COMMUNITY_RELATIVE."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif";
												$width		= 150;
												$height		= 150;
											}
											?>
											<img src="<?php echo $photo_url; ?>" width="<?php echo (int) $width; ?>" height="<?php echo (int) $height; ?>" alt="<?php echo html_encode($photo_record["photo_title"]); ?>" title="<?php echo html_encode($photo_record["photo_title"]); ?>" />
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<h2>Photo Details</h2>
										</td>
									</tr>
									<tr>
										<td style="vertical-align: top">
											<label for="photo_file" class="form-nrequired">Replacement Photo</label>
										</td>
										<td style="vertical-align: top">
											<input type="file" id="photo_file" name="photo_file" onchange="fetchPhotoFilename()" />
											<div class="content-small">
												<strong>Notice:</strong> You may upload JPEG, GIF or PNG images under <?php echo readable_size($VALID_MAX_FILESIZE); ?> only and any image larger than <?php echo $VALID_MAX_DIMENSIONS["photo"]; ?>px (width or height) will be automatically resized.
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<label for="photo_title" class="form-required">Photo Title</label>
										</td>
										<td>
											<input type="text" id="photo_title" name="photo_title" value="<?php echo ((isset($PROCESSED["photo_title"])) ? html_encode($PROCESSED["photo_title"]) : ""); ?>" maxlength="64" style="width: 300px" />
										</td>
									</tr>
									<tr>
										<td>
											<label for="photo_description" class="form-nrequired">Photo Description</label>
										</td>
										<td>
											<textarea id="photo_description" name="photo_description" style="width: 98%; height: 60px" cols="50" rows="5"><?php echo ((isset($PROCESSED["photo_description"])) ? html_encode($PROCESSED["photo_description"]) : ""); ?></textarea>
										</td>
									</tr>
									<?php if ((bool) $COMMUNITY_ADMIN) : ?>
									<?php endif; 
									if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
										?>
										<tr>
				                            <td colspan="2">
				                            	<table class="table table-bordered no-thead">
				                            		<colgroup>
					                                    <col style="width: 5%" />
					                                    <col style="width: auto" />
					                                </colgroup>
				                            		<tbody>
				                            			<tr>
							                        		<td class="center">
									                            <input type="checkbox" id="gallery_cgphoto_id" name="gallery_cgphoto_id" value="1" <?php echo (((int) $photo_record["gallery_cgphoto_id"] == (int) $RECORD_ID) ? "checked=\"checked\"" : ""); ?> />
							                        		</td>
							                        		<td>
							                        			<label for="gallery_cgphoto_id" class="form-nrequired">Make this photo the &quot;<?php echo html_encode($photo_record["gallery_title"]); ?>&quot; gallery thumbnail.</label>
							                        		</td>
							                        	</tr>
							                        	<tr>
							                        		<td class="center">
									                            <input type="checkbox" name="enable_notifications" id="enable_notifications" <?php echo ($notifications ? "checked=\"checked\"" : ""); ?>/>
							                        		</td>
							                        		<td>
							                        			<label for="enable_notifications" class="form-nrequired">Receive notifications when this users comment on this photo</label>
							                        		</td>
							                        	</tr>
						                        	</tbody>
					                        	</table>
				                            </td>
			                            </tr>
										<?php
									}	
									?>
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
									<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="Photo Uploading" title="Please wait while this photo is being uploaded." style="vertical-align: middle" /> Please Wait: this photo is being uploaded.
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
			$NOTICESTR[] = "The photo that you are trying to edit was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $photo_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $photo_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The photo record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The photo id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided photo id was invalid [".$RECORD_ID."] (Edit Photo).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid photo id to proceed.";

	echo display_error();

	application_log("error", "No photo id was provided to edit. (Edit Photo)");
}
?>
