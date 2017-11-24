<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add announcements to a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_ANNOUNCEMENTS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

communities_load_rte();

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add", "title" => "Add Announcement");

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Add Announcement</h1>\n";

// Error Checking
switch($STEP) {
	case 2 :
		
		/**
		 * Required field "title" / Announcement Title.
		 */
		if ((isset($_POST["announcement_title"])) && ($title = clean_input($_POST["announcement_title"], array("notags", "trim")))) {
			$PROCESSED["announcement_title"] = $title;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Announcement Title</strong> field is required.";
		}

		/**
		 * Required field "description" / Announcement Body.
		 */
		if ((isset($_POST["announcement_description"])) && ($description = clean_input($_POST["announcement_description"], array("trim", "allowedtags")))) {
			$PROCESSED["announcement_description"] = $description;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Announcement Body</strong> field is required.";
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
			$PROCESSED["community_id"]	= $COMMUNITY_ID;
			$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
			$PROCESSED["updated_date"]	= time();
			$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
			$PROCESSED["cpage_id"]		= $PAGE_ID;

			if (!$COMMUNITY_ADMIN) {
				$PROCESSED["pending_moderation"] = 1;
			}

			if ($db->AutoExecute("community_announcements", $PROCESSED, "INSERT")) {
				if ($ANNOUNCEMENT_ID = $db->Insert_Id()) {					

					$SUCCESS++;
					if (!$COMMUNITY_ADMIN && ($PAGE_OPTIONS["moderate_posts"] == 1)) {
						Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> to this page, however because you are not an administrator your announcement must be reviewed before appearing to all users."), $PROCESSED["announcement_title"]), "success", $MODULE);
						if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
							community_notify($COMMUNITY_ID, $ANNOUNCEMENT_ID, "announcement_moderate", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$ANNOUNCEMENT_ID, $COMMUNITY_ID, $PROCESSED["release_date"]);
						}
					} else {
						Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> to the community."), $PROCESSED["announcement_title"]), "success", $MODULE);
                        if (COMMUNITY_NOTIFICATIONS_ACTIVE && isset($_POST["notify_members"]) && $_POST["notify_members"]) {
                            community_notify($COMMUNITY_ID, $ANNOUNCEMENT_ID, "announcement", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$ANNOUNCEMENT_ID, $COMMUNITY_ID, $PROCESSED["release_date"]);
                        }
					}
					communities_log_history($COMMUNITY_ID, $PAGE_ID, $ANNOUNCEMENT_ID, "community_history_add_announcement", ($PROCESSED["pending_moderation"] == 1 ? 0 : 1));
					add_statistic("community:".$COMMUNITY_ID.":announcements", "add", "cannouncement_id", $ANNOUNCEMENT_ID);
					$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
					header("Location: " . $url);
					exit;
				}
			}

			if (!$SUCCESS) {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this announcement into the system. The MEdTech Unit was informed of this error; please try again later.";

				application_log("error", "There was an error inserting an announcement. Database said: ".$db->ErrorMsg());
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
		<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Add Announcement">
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
							<h2>Announcement Details</h2>
						</td>
					</tr>
					<tr>
						<td>
							<label for="announcement_title" class="form-required">Announcement Title</label>
						</td>
						<td>
							<input type="text" id="announcement_title" name="announcement_title" value="<?php echo ((isset($PROCESSED["announcement_title"])) ? html_encode($PROCESSED["announcement_title"]) : ""); ?>" maxlength="128" style="width: 300px" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="announcement_description" class="form-required">Announcement Body</label>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea id="announcement_description" name="announcement_description" style="width: 98%; height: 200px" cols="70" rows="10"><?php echo ((isset($PROCESSED["announcement_description"])) ? html_encode($PROCESSED["announcement_description"]) : ""); ?></textarea>
						</td>
					</tr>
					<?php
					if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
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
												<input type="checkbox" name="notify_members" id="notify_members" />
											</td>
											<td>
												<label for="notify_members" class="form-nrequired">Notify Community Members of Announcement</label>
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
		<?php
	break;
}
?>
