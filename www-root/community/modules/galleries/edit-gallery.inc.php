<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing photo galleries in a community. This action is
 * available only to existing community administrators.
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

echo "<h1>Edit Photo Gallery</h1>\n";

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_galleries` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($RECORD_ID);
	$gallery_record	= $db->GetRow($query);
	if ($gallery_record) {
		if ((int) $gallery_record["gallery_active"]) {
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$RECORD_ID, "title" => limit_chars($gallery_record["gallery_title"], 32));
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-gallery&amp;id=".$RECORD_ID, "title" => "Edit Photo Gallery");

			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "title" / Gallery Title.
					 */
					if ((isset($_POST["gallery_title"])) && ($title = clean_input($_POST["gallery_title"], array("notags", "trim")))) {
						$PROCESSED["gallery_title"] = $title;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Gallery Title</strong> field is required.";
					}

					/**
					 * Non-Required field "description" / Gallery Description.
					 */
					if ((isset($_POST["gallery_description"])) && ($description = clean_input($_POST["gallery_description"], array("notags", "trim")))) {
						$PROCESSED["gallery_description"] = $description;
					} else {
						$PROCESSED["gallery_description"] = "";
					}

					/**
					 * Non-Required field "gallery_cgphoto_id" / Gallery Thumbnail.
					 */
					if ((isset($_POST["gallery_cgphoto_id"])) && ($gallery_cgphoto_id = clean_input($_POST["gallery_cgphoto_id"], array("trim", "int")))) {
						$query	= "SELECT `cgphoto_id` FROM `community_gallery_photos` WHERE `cgphoto_id` = ".$db->qstr($gallery_cgphoto_id)." AND `cgallery_id` = ".$db->qstr($RECORD_ID)." AND `photo_active` = '1'";
						$result	= $db->GetRow($query);
						if ($result) {
							$PROCESSED["gallery_cgphoto_id"] = $gallery_cgphoto_id;
						} else {
							$PROCESSED["gallery_cgphoto_id"] = 0;
						}
					} else {
						$PROCESSED["gallery_cgphoto_id"] = 0;
					}

					/**
					 * Permission checking for member access.
					 */
					if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
						$PROCESSED["allow_member_read"]		= 1;
					} else {
						$PROCESSED["allow_member_read"]		= 0;
					}
					if ((isset($_POST["allow_member_upload"])) && (clean_input($_POST["allow_member_upload"], array("int")) == 1)) {
						$PROCESSED["allow_member_upload"]	= 1;
					} else {
						$PROCESSED["allow_member_upload"]	= 0;
					}
					if ((isset($_POST["allow_member_comment"])) && (clean_input($_POST["allow_member_comment"], array("int")) == 1)) {
						$PROCESSED["allow_member_comment"]	= 1;
					} else {
						$PROCESSED["allow_member_comment"]	= 0;
					}

					/**
					 * Permission checking for troll access.
					 * This can only be done if the community_registration is set to "Open Community"
					 */
					if (!(int) $community_details["community_registration"]) {
						if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
							$PROCESSED["allow_troll_read"]		= 1;
						} else {
							$PROCESSED["allow_troll_read"]		= 0;
						}
						if ((isset($_POST["allow_troll_upload"])) && (clean_input($_POST["allow_troll_upload"], array("int")) == 1)) {
							$PROCESSED["allow_troll_upload"]	= 1;
						} else {
							$PROCESSED["allow_troll_upload"]	= 0;
						}
						if ((isset($_POST["allow_troll_comment"])) && (clean_input($_POST["allow_troll_comment"], array("int")) == 1)) {
							$PROCESSED["allow_troll_comment"]	= 1;
						} else {
							$PROCESSED["allow_troll_comment"]	= 0;
						}
					} else {
						$PROCESSED["allow_troll_read"]			= 0;
						$PROCESSED["allow_troll_upload"]		= 0;
						$PROCESSED["allow_troll_comment"]		= 0;
					}

					/**
					 * Permission checking for public access.
					 * This can only be done if the community_protected is set to "Public Community"
					 */
					if (!(int) $community_details["community_protected"]) {
						if ((isset($_POST["allow_public_read"])) && (clean_input($_POST["allow_public_read"], array("int")) == 1)) {
							$PROCESSED["allow_public_read"]	= 1;
						} else {
							$PROCESSED["allow_public_read"]	= 0;
						}
						$PROCESSED["allow_public_upload"]	= 0;
						$PROCESSED["allow_public_comment"]	= 0;
					} else {
						$PROCESSED["allow_public_read"]		= 0;
						$PROCESSED["allow_public_upload"]	= 0;
						$PROCESSED["allow_public_comment"]	= 0;
					}

					/**
					 * Email Notificaions.
					 */
					if(isset($_POST["admin_notifications"])) {
						$PROCESSED["admin_notifications"] = $_POST["admin_notifications"];
					} elseif(isset($_POST["admin_notify"]) || isset($_POST["member_notify"])) {
						$PROCESSED["admin_notifications"] = $_POST["admin_notify"] + $_POST["member_notify"];
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

					if (!$ERROR) {
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

						if ($db->AutoExecute("community_galleries", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($RECORD_ID))) {
							$SUCCESS++;
							Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated the <strong>%s</strong> photo gallery."), $PROCESSED["gallery_title"]), "success", $MODULE);

							add_statistic("community:".$COMMUNITY_ID.":galleries", "gallery_edit", "cgallery_id", $RECORD_ID);
							communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_gallery", 1);

							$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?confmsg=galleryedit&title=" . $PROCESSED["gallery_title"];
							header("Location: " . $url);
							exit;
						}

						if (!$SUCCESS) {
							$ERROR++;
							$ERRORSTR[] = "There was a problem updating this gallery in the system. The MEdTech Unit was informed of this error; please try again later.";

							application_log("error", "There was an error updating a photo gallery. Database said: ".$db->ErrorMsg());
						}
					}

					if ($ERROR) {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					$PROCESSED = $gallery_record;
				break;
			}

			// Page Display
			switch($STEP) {
				case 1 :
				default :
					$ONLOAD[] = "updateThumbnailPreview('".(int) $gallery_record["gallery_cgphoto_id"]."')";

					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
					?>
					<script type="text/javascript">
					function updateThumbnailPreview(cgphoto_id) {
						if ($('thumbnail-preview-holder')) {
							if ((!cgphoto_id) || (cgphoto_id == 0) || (cgphoto_id == '')) {
								var photo_url = '<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif"; ?>';
							} else {
								var photo_url = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=' + cgphoto_id + '&render=thumbnail"; ?>';
							}

							$('thumbnail-preview-holder').src = photo_url;
						}

						return;
					}
					</script>
					<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-gallery&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
						<table summary="Edit Photo Gallery">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="2" style="padding-top: 15px; text-align: right">
		                                <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td colspan="2">
										<h2>Gallery Details</h2>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top;">
										<label for="gallery_title" class="form-required">Gallery Title</label>
									</td>
									<td>
										<input type="text" id="gallery_title" name="gallery_title" class="span12" value="<?php echo ((isset($PROCESSED["gallery_title"])) ? html_encode($PROCESSED["gallery_title"]) : ""); ?>" maxlength="64" />
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top;">
										<label for="gallery_description" class="form-nrequired">Gallery Description</label>
									</td>
									<td>
										<textarea id="gallery_description" name="gallery_description" class="span12" style="height: 100px"><?php echo ((isset($PROCESSED["gallery_description"])) ? html_encode($PROCESSED["gallery_description"]) : ""); ?></textarea>
									</td>
								</tr>
								<tr>
									<td style="vertical-align: top;">
                                        <label for="gallery_cgphoto_id" class="form-nrequired">Gallery Thumbnail</label>
                                    </td>
                                    <td>
										<select id="gallery_cgphoto_id" name="gallery_cgphoto_id" class="span8" onchange="updateThumbnailPreview(this.options[this.selectedIndex].value)">
                                            <option value="0"<?php echo ((!(int) $gallery_record["gallery_cgphoto_id"]) ? " selected=\"selected\"" : ""); ?>>No Thumbnail Selected</option>
                                            <?php
                                            $query		= "SELECT `cgphoto_id`, `photo_title` FROM `community_gallery_photos` WHERE `cgallery_id` = ".$db->qstr($RECORD_ID)." AND `photo_active` = '1' ORDER BY `photo_title` ASC";
                                            $results	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
                                            if ($results) {
                                                foreach($results as $result) {
                                                    echo "<option value=\"".(int) $result["cgphoto_id"]."\"".(($result["cgphoto_id"] == $gallery_record["gallery_cgphoto_id"]) ? " selected=\"selected\"" : "").">".html_encode($result["photo_title"])."</option>\n";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <br />
                                        <img id="thumbnail-preview-holder" class="img-polaroid center" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif"; ?>" width="<?php echo $VALID_MAX_DIMENSIONS["thumb"]; ?>" height="<?php echo $VALID_MAX_DIMENSIONS["thumb"]; ?>" alt="<?php echo html_encode($PROCESSED["gallery_title"]); ?> - Gallery Thumbnail" title="<?php echo html_encode($PROCESSED["gallery_title"]); ?> - Gallery Thumbnail" />
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<h2>Gallery Permissions</h2>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<table class="table table-striped table-bordered">
											<colgroup>
												<col style="width: 40%" />
												<col style="width: 20%" />
												<col style="width: 20%" />
												<col style="width: 20%" />
											</colgroup>
											<thead>
												<tr>
													<td>
														Group
													</td>
													<td>
														View Gallery
													</td>
													<td>
														Upload Photos
													</td>
													<td>
														Allow Comments
													</td>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<strong>Community Administrators</strong>
													</td>
													<td class="on">
														<input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" />
													</td>
													<td>
														<input type="checkbox" id="allow_admin_post" name="allow_admin_post" value="1" checked="checked" onclick="this.checked = true" />
													</td>
													<td class="on">
														<input type="checkbox" id="allow_admin_reply" name="allow_admin_reply" value="1" checked="checked" onclick="this.checked = true" />
													</td>
												</tr>
												<tr>
													<td>
														<strong>Community Members</strong>
													</td>
													<td class="on">
														<input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
													</td>
													<td>
														<input type="checkbox" id="allow_member_upload" name="allow_member_upload" value="1"<?php echo (((!isset($PROCESSED["allow_member_upload"])) || ((isset($PROCESSED["allow_member_upload"])) && ($PROCESSED["allow_member_upload"] == 1))) ? " checked=\"checked\"" : ""); ?> />
													</td>
													<td class="on">
														<input type="checkbox" id="allow_member_comment" name="allow_member_comment" value="1"<?php echo (((!isset($PROCESSED["allow_member_comment"])) || ((isset($PROCESSED["allow_member_comment"])) && ($PROCESSED["allow_member_comment"] == 1))) ? " checked=\"checked\"" : ""); ?> />
													</td>
												</tr>
												<?php if (!(int) $community_details["community_registration"]) :  ?>
												<tr>
													<td>
														<strong>Browsing Non-Members</strong>
													</td>
													<td class="on">
														<input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
													</td>
													<td>
														<input type="checkbox" id="allow_troll_upload" name="allow_troll_upload" value="1"<?php echo (((isset($PROCESSED["allow_troll_upload"])) && ($PROCESSED["allow_troll_upload"] == 1)) ? " checked=\"checked\"" : ""); ?> />
													</td>
													<td class="on">
														<input type="checkbox" id="allow_troll_comment" name="allow_troll_comment" value="1"<?php echo (((isset($PROCESSED["allow_troll_comment"])) && ($PROCESSED["allow_troll_comment"] == 1)) ? " checked=\"checked\"" : ""); ?> />
													</td>
												</tr>
												<?php endif; ?>
												<?php if (!(int) $community_details["community_protected"]) :  ?>
												<tr>
													<td>
														<strong>Non-Authenticated / Public Users</strong>
													</td>
													<td class="on">
														<input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> />
													</td>
													<td>
														<input type="checkbox" id="allow_public_upload" name="allow_public_upload" value="0" onclick="noPublic(this)" />
													</td>
													<td class="on">
														<input type="checkbox" id="allow_public_comment" name="allow_public_comment" value="0" onclick="noPublic(this)" />
													</td>
												</tr>
												<?php endif; ?>
											</tbody>
										</table>
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
					<?php
				break;
			}
		} else {
			$NOTICE++;
			$NOTICESTR[] = "The photo gallery that you are trying to edit was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $gallery_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $gallery_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The photo gallery record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The photo gallery id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided photo gallery id was invalid [".$RECORD_ID."] (Edit Gallery).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid photo gallery id to proceed.";

	echo display_error();

	application_log("error", "No photo gallery id was provided to edit. (Edit Gallery)");
}
?>
