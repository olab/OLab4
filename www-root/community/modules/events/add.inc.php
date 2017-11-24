<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add events to a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

communities_load_rte();

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add", "title" => "Add Event");

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/community/javascript/events.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Add Event</h1>\n";

// Error Checking
switch ($STEP) {
	case 2 :
		/**
		 * Required field "title" / Event Title.
		 */
		if ((isset($_POST["event_title"])) && ($title = clean_input($_POST["event_title"], array("notags", "trim")))) {
			$PROCESSED["event_title"] = $title;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Event Title</strong> field is required.";
		}
		
		/**
		 * Non-Required field "event_location" / Event Location.
		 */
		if ((isset($_POST["event_location"])) && ($event_location = clean_input($_POST["event_location"], array("notags", "trim")))) {
			$PROCESSED["event_location"] = $event_location;
		} else {
			$PROCESSED["event_location"] = "";
		}

		$event_dates = validate_calendars("event", true, true);
		if ((isset($event_dates["start"])) && ((int) $event_dates["start"])) {
			$PROCESSED["event_start"] = (int) $event_dates["start"];
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Event Start</strong> field is required if this is to appear on the calendar.";
		}

		if ((isset($event_dates["finish"])) && ((int) $event_dates["finish"])) {
			$PROCESSED["event_finish"] = (int) $event_dates["finish"];
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Event Finish</strong> field is required if this is to appear on the calendar.";
		}
		
		/**
		 * Non-Required field "description" / Event Details / Description.
		 */
		if ((isset($_POST["event_description"])) && ($description = clean_input($_POST["event_description"], array("trim", "allowedtags")))) {
			$PROCESSED["event_description"] = $description;
		} else {
			$PROCESSED["event_description"] = "";
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

			if ($db->AutoExecute("community_events", $PROCESSED, "INSERT")) {
				if ($EVENT_ID = $db->Insert_Id()) {
					
					$SUCCESS++;

					if (!$COMMUNITY_ADMIN && ($PAGE_OPTIONS["moderate_posts"] == 1)) {
						Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> to this page, however because you are not an administrator your event must be reviewed before appearing to all users."), $PROCESSED["event_title"]), "success", $MODULE);
					} else {
						Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong> to the community."), $PROCESSED["event_title"]), "success", $MODULE);
					}

					communities_log_history($COMMUNITY_ID, $PAGE_ID, $EVENT_ID, "community_history_add_event", ($PROCESSED["pending_moderation"] == 1 ? 0 : 1));
					add_statistic("community:".$COMMUNITY_ID.":events", "add", "cevent_id", $EVENT_ID);

					if (COMMUNITY_NOTIFICATIONS_ACTIVE && (isset($_POST["notify_members"]) && $_POST["notify_members"]) && (!$PAGE_OPTIONS["moderate_posts"] || $COMMUNITY_ADMIN)) {
						community_notify($COMMUNITY_ID, $EVENT_ID, "event", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$EVENT_ID, $COMMUNITY_ID, $PROCESSED["release_date"]);
					}
					if (COMMUNITY_NOTIFICATIONS_ACTIVE && ($PAGE_OPTIONS["moderate_posts"] && !$COMMUNITY_ADMIN)) {
						community_notify($COMMUNITY_ID, $EVENT_ID, "event-moderation", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$EVENT_ID, $COMMUNITY_ID, $PROCESSED["release_date"]);
					}
					$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
					header("Location: " . $url);
					exit;
				}
			}

			if (!$SUCCESS) {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this event into the system. The MEdTech Unit was informed of this error; please try again later.";

				application_log("error", "There was an error inserting an event. Database said: ".$db->ErrorMsg());
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
switch ($STEP) {
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
			<table summary="Add Event">
				<colgroup>
					<col style="width: 20%" />
					<col style="width: 80%" />
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="2" style="padding-top: 15px; text-align: left">
							<input type="button" class="btn button-right" value="<?php echo $translate->_("global_button_cancel"); ?>" onclick="window.location='<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL?>'" />
						</td>
						<td colspan="2" style="padding-top: 15px; text-align: right">
		                    <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save");?>" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td colspan="2">
							<h2>Event Details</h2>
						</td>
					</tr>
					<tr>
						<td>
							<label for="event_title" class="form-required">Event Title</label>
						</td>
						<td>
							<input type="text" id="event_title" name="event_title" value="<?php echo ((isset($PROCESSED["event_title"])) ? html_encode($PROCESSED["event_title"]) : ""); ?>" maxlength="128" style="width: 300px; float: left;" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="event_location" class="form-nrequired">Event Location</label>
						</td>
						<td>
							<input type="text" id="event_location" name="event_location" value="<?php echo ((isset($PROCESSED["event_location"])) ? html_encode($PROCESSED["event_location"]) : ""); ?>" maxlength="128" style="width: 300px" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="date-time">
								<?php 
									echo generate_calendars("event", "", true, true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0), true, true, ((isset($PROCESSED["event_finish"])) ? $PROCESSED["event_finish"] : 0)); 
								?>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="event_description" class="form-nrequired">Event Details / Description</label>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea id="event_description" name="event_description" style="width: 98%; height: 200px" cols="70" rows="10"><?php echo ((isset($PROCESSED["event_description"])) ? html_encode($PROCESSED["event_description"]) : ""); ?></textarea>
						</td>
					</tr>
					<?php 
					if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
						?>
						<tr>
							<td colspan="2">
								<table class="table table-bordered no-thead space-above">
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
												<label for="notify_members" class="form-nrequired">Notify Community Members of Event</label>
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