<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add photo galleries to a community. This action is only available to
 * community administrators.
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

echo "<h1>Add Photo Gallery</h1>\n";

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-gallery", "title" => "Add Photo Gallery");

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
		if (((isset($_POST["admin_notify"])) && ($admin_notify = (int) $_POST["admin_notify"])) || ((isset($_POST["member_notify"])) && ($member_notify = (int) $_POST["member_notify"]))) {
			$PROCESSED["admin_notifications"] = ($admin_notify + $member_notify);
		} else {
			$PROCESSED["admin_notifications"] = 0;
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
			$PROCESSED["community_id"]		= $COMMUNITY_ID;
			$PROCESSED["proxy_id"]			= $ENTRADA_USER->getActiveId();
			$PROCESSED["gallery_active"]	= 1;
			$PROCESSED["updated_date"]		= time();
			$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();
			$PROCESSED["cpage_id"]			= $PAGE_ID;

			if ($db->AutoExecute("community_galleries", $PROCESSED, "INSERT")) {
				if ($GALLERY_ID = $db->Insert_Id()) {
					$SUCCESS++;
					Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong>."), $PROCESSED["gallery_title"]), "success", $MODULE);

					add_statistic("community:".$COMMUNITY_ID.":galleries", "gallery_add", "cgallery_id", $GALLERY_ID);
					communities_log_history($COMMUNITY_ID, $PAGE_ID, $GALLERY_ID, "community_history_add_gallery", 1);

					$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
					header("Location: " . $url);
					exit;
				}
			}

			if (!$SUCCESS) {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this gallery into the system. The MEdTech Unit was informed of this error; please try again later.";

				application_log("error", "There was an error inserting a photo gallery. Database said: ".$db->ErrorMsg());
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
	case 1 :
	default :
		if ($ERROR) {
			echo display_error();
		}
		if ($NOTICE) {
			echo display_notice();
		}
		?>
		<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-gallery&amp;step=2" method="post">
			<table cellspacing="0" cellpadding="2" border="0" summary="Add Photo Gallery">
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
						<td colspan="2"><h2>Gallery Details</h2></td>
					</tr>
					<tr>
						<td style="vertical-align: top">
							<label for="gallery_title" class="form-required">Gallery Title</label>
						</td>
						<td>
							<input type="text" id="gallery_title" name="gallery_title" style="width: 70%" class="span8" value="<?php echo ((isset($PROCESSED["gallery_title"])) ? html_encode($PROCESSED["gallery_title"]) : ""); ?>" maxlength="64" />
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top">
							<label for="gallery_description" class="form-nrequired">Gallery Description</label>
						</td>
						<td>
							<textarea id="gallery_description" name="gallery_description" class="span8" style="height: 100px; resize: vertical; width: 70%" cols="50" rows="5"><?php echo ((isset($PROCESSED["gallery_description"])) ? html_encode($PROCESSED["gallery_description"]) : ""); ?></textarea>
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
										<td>Group</td>
										<td>View Gallery</td>
										<td>Upload Photos</td>
										<td>Allow Comments</td>
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
										<td><strong>Community Members</strong>
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
?>