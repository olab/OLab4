<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to upload photos to an existing gallery in a community.
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

echo "<h1>Upload Photo</h1>\n";

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_galleries` WHERE `cgallery_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$gallery_record	= $db->GetRow($query);
	if ($gallery_record) {
		if (galleries_module_access($RECORD_ID, "add-photo")) {
			$BREADCRUMB[]	= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$gallery_record["cgallery_id"], "title" => limit_chars($gallery_record["gallery_title"], 32));
			$BREADCRUMB[]	= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-photo&id=".$RECORD_ID, "title" => "Upload Photo");

			$photo_uploads	= array();
            if ((array_count_values($copyright_settings = (array) $translate->_("copyright")) > 1) && isset($copyright_settings["copyright-uploads"]) && strlen($copyright_settings["copyright-uploads"])) {
                $COPYRIGHT = true;
            } else {
                $COPYRIGHT = false;
            }

			// Error Checking
			switch($STEP) {
				case 2 :
					if ((isset($_FILES["photo_files"])) && (is_array($_FILES["photo_files"]))) {
						if ((!defined("COMMUNITY_STORAGE_GALLERIES")) || (!@is_dir(COMMUNITY_STORAGE_GALLERIES)) || (!@is_writable(COMMUNITY_STORAGE_GALLERIES))) {
							$error_current++;

							$ERROR++;
							$ERRORSTR[] = "There is a problem with the storage directory on the server; the system administrator has been informed of this error, please try again later.";

							application_log("error", "The community gallery storage path [".COMMUNITY_STORAGE_GALLERIES."] does not exist or is not writable.");
						} else {
							foreach ($_FILES["photo_files"]["name"] as $tmp_photo_id => $photo_name) {
								$PROCESSED		= array();

								$photo_id		= 0;
								$error_current	= 0;

								$photo_number	= ($tmp_photo_id + 1);
								$photo_name		= useable_filename(clean_input($photo_name, "trim"));

								if ($photo_name != "") {
									if (isset($_FILES["photo_files"]["error"][$tmp_photo_id])) {
										switch($_FILES["photo_files"]["error"][$tmp_photo_id]) {
											case 0 :
												if (@in_array($photo_mimetype = strtolower(trim($_FILES["photo_files"]["type"][$tmp_photo_id])), array_keys($VALID_MIME_TYPES))) {
													$photo_filesize = (int) trim($_FILES["photo_files"]["size"][$tmp_photo_id]);
													if (($photo_filesize) && ($photo_filesize <= $VALID_MAX_FILESIZE)) {
														$PROCESSED["photo_mimetype"]	= $photo_mimetype;
														$PROCESSED["photo_filesize"]	= $photo_filesize;
														$PROCESSED["photo_filename"]	= $photo_name;
														$photo_file_extension			= strtoupper($VALID_MIME_TYPES[strtolower(trim($_FILES["photo_files"]["type"][$tmp_photo_id]))]);

													}
												} else {
													$error_current++;

													$ERROR++;
													$ERRORSTR[] = "Photo ".$photo_number.": The file that you have uploaded does not appear to be a valid image. Please ensure that you upload a JPEG, GIF or PNG file.";
												}
											break;
											case 1 :
											case 2 :
												$error_current++;

												$ERROR++;
												$ERRORSTR[] = "Photo ".$photo_number.": The photo that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the photo smaller and try again.";
											break;
											case 3 :
												$error_current++;

												$ERROR++;
												$ERRORSTR[]	= "Photo ".$photo_number.": The photo that was uploaded did not complete the upload process or was interrupted; please try again.";
											break;
											case 4 :
												$error_current++;

												$ERROR++;
												$ERRORSTR[]	= "Photo ".$photo_number.": You did not select a photo from your computer to upload. Please select a local image file and try again.";
											break;
											case 6 :
											case 7 :
												$error_current++;

												$ERROR++;
												$ERRORSTR[]	= "Photo ".$photo_number.": Unable to store the new photo file on the server; the system administrator has been informed of this error, please try again later.";

												application_log("error", "Community photo file upload error: ".(($_FILES["photo_files"]["error"][$tmp_photo_id] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
											break;
											default :
												application_log("error", "Unrecognized photo file upload error number [".$_FILES["photo_files"]["error"][$tmp_photo_id]."].");
											break;
										}

										if ($_FILES["photo_files"]["size"][$tmp_photo_id] > $VALID_MAX_FILESIZE) {
											$error_current++;

											$ERROR++;
											$ERRORSTR[] = "Photo ".$photo_number.": The file you have attempted to upload is above the maximum size [".readable_size($VALID_MAX_FILESIZE)."].";
										}
									} else {
										$error_current++;

										$ERROR++;
										$ERRORSTR[]	 = "Photo ".$photo_number.": To upload a photo to the gallery you must select an image file from your computer.";
									}

									/**
									 * Required field "title" / Photo Title.
									 */
									if ((isset($_POST["photo_title"][$tmp_photo_id])) && ($tmp_input = clean_input($_POST["photo_title"][$tmp_photo_id], array("notags", "trim")))) {
										$PROCESSED["photo_title"] = $tmp_input;
									} elseif ((isset($PROCESSED["photo_filename"])) && ($PROCESSED["photo_filename"])) {
										$PROCESSED["photo_title"] = $PROCESSED["photo_filename"];
									} else {
										$error_current++;

										$ERROR++;
										$ERRORSTR[] = "Photo ".$photo_number.": The <strong>Photo Title</strong> field is required.";
									}
									
									/**
									 * Email Notificaions.
									 */
									if(isset($_POST["enable_notifications"][$tmp_photo_id])) {
										$notifications = $_POST["enable_notifications"][$tmp_photo_id];
									} else {
										$notifications = 0;
									}		

									/**
									 * Non-Required field "description" / Photo Description.
									 *
									 */
									if ((isset($_POST["photo_description"][$tmp_photo_id])) && ($tmp_input = clean_input($_POST["photo_description"][$tmp_photo_id], array("notags", "trim")))) {
										$PROCESSED["photo_description"] = $tmp_input;
									} else {
										$PROCESSED["photo_description"] = "";
									}
									
									if (!$error_current) {
										/**
										 * Required field "release_from" / Release Start (validated through validate_calendars function).
										 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
										 */
										$release_dates = validate_calendars("release", true, false);
										if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
											$PROCESSED["release_date"]	= (int) $release_dates["start"];
										} else {
											$PROCESSED["release_date"]	= time();
										}

										if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
											$PROCESSED["release_until"]	= (int) $release_dates["finish"];
										} else {
											$PROCESSED["release_until"]	= 0;
										}

										if (!$error_current) {
											$PROCESSED["cgallery_id"]		= $RECORD_ID;
											$PROCESSED["community_id"]		= $COMMUNITY_ID;
											$PROCESSED["proxy_id"]			= $ENTRADA_USER->getActiveId();
											$PROCESSED["photo_active"]		= 1;
											$PROCESSED["updated_date"]		= time();
											$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

											if ($db->AutoExecute("community_gallery_photos", $PROCESSED, "INSERT")) {
												$photo_id = $db->Insert_Id();
												if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
													$db->Execute("INSERT INTO `community_notify_members` (`proxy_id`, `record_id`, `community_id`, `notify_type`, `notify_active`) VALUES (".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($photo_id).", ".$db->qstr($COMMUNITY_ID).", 'photo-comment', '".(isset($notifications) && $notifications ? "1" : "0")."')");
												}
												if ($photo_id) {
													if (communities_galleries_process_photo($_FILES["photo_files"]["tmp_name"][$tmp_photo_id], $photo_id)) {
														if (!(int) $gallery_record["gallery_cgphoto_id"]) {
															if (!$db->AutoExecute("community_galleries", array("gallery_cgphoto_id" => $photo_id), "UPDATE", "`cgallery_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID))) {
																application_log("error", "Unable to set the gallery_cgphoto_id to this photo_id when adding the first picture. Database said: ".$db->ErrorMsg());
															}
														}

														if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
															community_notify($COMMUNITY_ID, $photo_id, "photo", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=".$photo_id, $RECORD_ID, $PROCESSED["release_date"]);
														}
														Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong>."), $PROCESSED["photo_title"]), "success", $MODULE);

														$SUCCESS++;																									

														add_statistic("community:".$COMMUNITY_ID.":galleries", "photo_add", "cgphoto_id", $photo_id);
														communities_log_history($COMMUNITY_ID, $PAGE_ID, $photo_id, "community_history_add_photo", 1, $RECORD_ID);
													} else {
														$query = "DELETE FROM `community_gallery_photos` WHERE `cgphoto_id` = ".$db->qstr($photo_id)." AND `cgallery_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
														if (!$db->Execute($query)) {
															application_log("error", "Failed to remove a newly uploaded photo [".$photo_id."] from the community_gallery_photos table in community [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
														}
														$error_current++;

														$ERROR++;
														$ERRORSTR[]	= "Photo ".$photo_number.": Unable to store the new photo file on the server; the system administrator has been informed of this error, please try again later.";

														application_log("error", "Failed to move the uploaded Community photo to the storage directory [".COMMUNITY_STORAGE_GALLERIES."/".$photo_id."].");
													}
												}
											}
										}
									}

									$photo_uploads[$tmp_photo_id]	= array(
																		"success"		=> (($error_current) ? false : true),
																		"photo_id"		=> ($photo_id ? $photo_id : 0),
																		"title"			=> $PROCESSED["photo_title"],
																		"description"	=> $PROCESSED["photo_description"]
																	);

								}
							}
						}
					}

					if (($_SERVER["REQUEST_METHOD"] == "POST") && (empty($_POST)) && ($_SERVER["CONTENT_LENGTH"] > 0)) {
						$ERROR++;
						$ERRORSTR[] = "You have attempted to upload a number of pictures with a size exceeding ".readable_size(MAX_UPLOAD_FILESIZE).". Please try again with a smaller number of photos at once.";
					} elseif (!$SUCCESS) {
						$ERROR++;
						$ERRORSTR[] = "Please ensure you upload at least one photo.";
					}
					if ($SUCCESS) {
						$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-gallery&id=" .$RECORD_ID;
						header("Location: " . $url);
						exit;
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
					if (count($photo_uploads) < 1) {
						$photo_uploads[] = array();
					}
					
					if ($SUCCESS) {
						echo display_success();
					}

					if ($NOTICE) {
						echo display_notice();
					}
					
					if ($ERROR) {
						echo display_error();
					}
					?>
					<script type="text/javascript">
					var addPhotoHTML =	'<div id="photo_#{photo_id}" class="photo-upload">' +
										'	<table class="upload" style="width: 100%">' +
										'	<colgroup>' +
										'		<col style="width: 20%" />' +
										'		<col style="width: 80%" />' +
										'	</colgroup>' +
										'	<tr>' +
										'		<td colspan="2">' +
										'			<h2 id="photo_#{photo_id}_title">Photo #{photo_number})</h2>' +
										'			<div style="text-align: right">(<a class="action" href="#" onclick="$(\'photo_#{photo_id}\').remove();">remove</a>)</div>' +
										'		</td>' +
										'	</tr>' +
										'	<tbody>' +
										'		<tr>' +
										'			<td style="vertical-align: top"><label for="photo_file_#{photo_id}" class="form-required">Select Local Photo</label></td>' +
										'			<td style="vertical-align: top">' +
										'				<input type="file" id="photo_file_#{photo_id}" name="photo_files[#{photo_id}]" onchange="fetchPhotoFilename(#{photo_id})" />' +
										'			</td>' +
										'		</tr>' +
										'		<tr>' +
										'			<td colspan="2">&nbsp;</td>' +
										'		</tr>' +
										'		<tr>' +
										'			<td><label for="photo_title_#{photo_id}" class="form-required">Photo Title</label></td>' +
										'			<td><input type="text" id="photo_title_#{photo_id}" name="photo_title[#{photo_id}]" value="" maxlength="64" style="width: 300px" /></td>' +
										'		</tr>' +
										'		<tr>' +
										'			<td><label for="photo_description_#{photo_id}" class="form-nrequired">Photo Description</label></td>' +
										'			<td>' +
										'				<textarea id="photo_description_#{photo_id}" name="photo_description[#{photo_id}]" style="width: 97%; height: 60px; resize: vertical" cols="50" rows="5"></textarea>' +
										'			</td>' +
										'		</tr>' +
										'		<tr>' +
										'			<td colspan="2">&nbsp;</td>' +
										'		</tr>' +
										<?php
										if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) {
											?>
											'		<tr>' +
							                '            <td colspan="2">' +
							                '            	<table class="table table-bordered no-thead">' +
							                '            		<colgroup>' +
								            '                        <col style="width: 5%" />' +
								            '                        <col style="width: auto" />' +
								            '                    </colgroup>' +
							                '            		<tbody>' +
										    '                    	<tr>' +
										    '                    		<td class="center">' +
											'	                            <input type="checkbox" name="enable_notifications[<?php echo $tmp_photo_id; ?>]" id="enable_notifications_<?php echo $tmp_photo_id; ?>" <?php echo ($notifications ? "checked=\"checked\"" : ""); ?>/>' +
										    '                    		</td>' +
										    '                    		<td>' +
										    '                    			<label for="enable_notifications_<?php echo $tmp_photo_id; ?>" class="form-nrequired">Receive notifications when users comment on this photo</label>' +
										    '                    		</td>' +
										    '                    	</tr>' +
									        '                	</tbody>' +
								            '            	</table>' +
							                '            </td>' +
						                    '        </tr>' +
											<?php
										}
										?>
										'	</tbody>' +
										'	</table>' +
										'</div>';
					</script>
					<div>
						To add multiple JPEG, GIF or PNG images at once, click the &quot;Add Another Photo&quot; link below, which will increase the number of photo selections available. Please note that images with dimensions larger than <?php echo $VALID_MAX_DIMENSIONS["photo"]; ?> x <?php echo $VALID_MAX_DIMENSIONS["photo"]; ?>px will be automatically resized, and the combined file size of all uploaded images may not exceed <?php echo readable_size(MAX_UPLOAD_FILESIZE); ?>.
					</div>
					<div style="float: right; margin-top: 10px;">
						<ul class="page-action">
							<li>
								<a style="cursor: pointer" onclick="addPhoto(addPhotoHTML)">Add Another Photo</a>
							</li>
						</ul>
					</div>
					<div style="clear: both"></div>
					
					<form id="upload-photo-form" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-photo&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data" accept="<?php echo ((@is_array($VALID_MIME_TYPES)) ? implode(",", array_keys($VALID_MIME_TYPES)) : ""); ?>" onsubmit="uploadPhotos()">
						<input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
						<table summary="Upload Photo">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tfoot>
	                            <?php if ($COPYRIGHT) {
		                            ?>
		                            <tr>
			                            <td colspan="2">
				                            <h2><?php echo $translate->_("copyright_title"); ?></h2>
			                            </td>
		                            </tr>
		                            <tr>
			                            <td colspan="2">
			                            	<table class="table table-striped-rev table-bordered no-thead">
			                            		<colgroup>
				                                    <col style="width: 5%" />
				                                    <col style="width: auto" />
				                                </colgroup>
			                            		<tbody>
				                            		<tr>
							                            <td colspan="2" style="padding:15px;">
							                                <?php echo $copyright_settings["copyright-uploads"]; ?>
							                            </td>
						                        	</tr>
						                        	<tr>
						                        		<td class="center">
								                            <input type="checkbox" value="1" id="accept_copyright" onchange="acceptButton(this)" />
						                        		</td>
						                        		<td>
						                        			<label for="accept_copyright" class="form-nrequired"><?php echo $translate->_("copyright_accept_label"); ?>
						                        		</td>
						                        	</tr>
					                        	</tbody>
				                        	</table>
			                            </td>
		                            </tr>
	                            <?php
	                            } ?>
	                            <tr>
									<td colspan="2" style="padding-top: 1px; text-align: right">
										<div id="display-upload-button">
											<input type="submit" class="btn btn-primary" id="upload-button" value="Upload" <?php echo ($COPYRIGHT ? " disabled=\"disabled\"" : "");?> />
										</div>
									</td>
								</tr>
	                        </tfoot>
							<tbody>
								<tr>
									<td colspan="2">
										<div id="photo_list">
										<?php
											foreach ($photo_uploads as $tmp_photo_id => $photo_upload) {
												if ($photo_upload["success"]) {
													?>
													<div id="photo_<?php echo $tmp_photo_id; ?>" class="photo-upload">
														<table>
															<colgroup>
																	<col style="width: 20%" />
																	<col style="width: 80%" />
															</colgroup>
															<tr>
																<td colspan="2">
																	<h2 id="photo_<?php echo $tmp_photo_id; ?>_title">Photo <?php echo ($tmp_photo_id + 1); ?></h2>
																</td>
															</tr>
															<tbody>
																<tr>
																	<td style="vertical-align: top;">
																		<?php echo communities_galleries_fetch_thumbnail($photo_upload["photo_id"], $photo_upload["title"]); ?>
																	</td>
																	<td style="vertical-align: top;">
																		<h3><?php echo html_encode($photo_upload["title"]); ?></h3>
																		<div>
																			<?php echo nl2br(html_encode($photo_upload["description"])); ?>
																		</div>
																	</td>
																</tr>
															</tbody>
														</table>
													</div>
													<?php
												} else {
													?>
													<div id="photo_<?php echo $tmp_photo_id; ?>" class="photo-upload">
														<table class="upload" style="width: 100%">
															<colgroup>
																	<col style="width: 20%" />
																	<col style="width: 80%" />
															</colgroup>
															<tr>
																<td colspan="2">
																	<h2 id="photo_<?php echo $tmp_photo_id; ?>_title">Photo <?php echo ($tmp_photo_id + 1); ?>)</h2>
																	<?php
																	if ($tmp_photo_id) {
																		?>
																		<div style="text-align: right">(<a class="action" href="#" onclick="$('photo_<?php echo $tmp_photo_id; ?>').remove();">remove</a>)</div>
																		<?php
																	}
																	?>

																</td>
															</tr>
															<tbody>
																<tr>
																	<td style="vertical-align: top">
																		<label for="photo_file_<?php echo $tmp_photo_id; ?>" class="form-required">Select Local Photo</label>
																	</td>
																	<td style="vertical-align: top">
																		<input type="file" id="photo_file_<?php echo $tmp_photo_id; ?>" name="photo_files[<?php echo $tmp_photo_id; ?>]" onchange="fetchPhotoFilename('<?php echo $tmp_photo_id; ?>')" />
																	</td>
																</tr>
																<tr>
																	<td>
																		<label for="photo_title_<?php echo $tmp_photo_id; ?>" class="form-required">Photo Title</label>
																	</td>
																	<td>
																		<input type="text" id="photo_title_<?php echo $tmp_photo_id; ?>" name="photo_title[<?php echo $tmp_photo_id; ?>]" value="<?php echo ((isset($photo_upload["title"])) ? html_encode($photo_upload["title"]) : ""); ?>" maxlength="64" style="width: 300px" />
																	</td>
																</tr>
																<tr>
																	<td>
																		<label for="photo_description_<?php echo $tmp_photo_id; ?>" class="form-nrequired">Photo Description</label>
																	</td>
																	<td style="vertical-align: top;">
																		<textarea id="photo_description_<?php echo $tmp_photo_id; ?>" name="photo_description[<?php echo $tmp_photo_id; ?>]" style="width: 97%; height: 60px; resize: vertical" cols="50" rows="5"><?php echo ((isset($photo_upload["description"])) ? html_encode($photo_upload["description"]) : ""); ?></textarea>
																	</td>
																</tr>
																<?php
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
																                            <input type="checkbox" name="enable_notifications[<?php echo $tmp_photo_id; ?>]" id="enable_notifications_<?php echo $tmp_photo_id; ?>" <?php echo ($notifications ? "checked=\"checked\"" : ""); ?>/>
														                        		</td>
														                        		<td>
														                        			<label for="enable_notifications_<?php echo $tmp_photo_id; ?>" class="form-nrequired">Receive notifications when users comment on this photo</label>
														                        		</td>
														                        	</tr>
													                        	</tbody>
												                        	</table>
											                            </td>
										                            </tr>
																	<?php
																}
																?>
															</tbody>
														</table>
													</div>
													<?php
												}
											}
										?>
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><h2>Time Release Options</h2></td>
								</tr>
								<tr>
									<td colspan="2">
										<table class="date-time">
								<?php
		                        echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0));

		                        ?>
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
		application_log("error", "The provided photo gallery id was invalid [".$RECORD_ID."] (Upload Photo).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No photo gallery id was provided to upload into. (Upload Photo)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>
