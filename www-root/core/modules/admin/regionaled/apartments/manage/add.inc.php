<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Allows administrators to add occupants to an apartment.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_MANAGE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "create", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(), "title" => "Add Occupant");

	switch ($STEP) {
		case 2 :
			$PROCESSED["apartment_id"] = $APARTMENT_ID;
			$PROCESSED["event_id"] = 0;
			$PROCESSED["proxy_id"] = 0;
			$PROCESSED["occupant_title"] = "";
			$PROCESSED["occupant_type"] = "";

			if (isset($_POST["require_confirmation"]) && ((int) $_POST["require_confirmation"] == 1)) {
				$PROCESSED["confirmed"] = 0;
			} else {
				$PROCESSED["confirmed"] = 1;
			}
			
			$PROCESSED["cost_recovery"] = 0;
			$PROCESSED["notes"] = "";

			$PROCESSED["fullname"] = (isset($_POST["fullname"]) ? clean_input($_POST["fullname"], array("trim", "notags")) : "");
			$PROCESSED["occupant_ref"] = $PROCESSED["fullname"];

			if (isset($_POST["occupant_type"]) && ($tmp_input = clean_input($_POST["occupant_type"], "alpha"))) {
				$PROCESSED["occupant_type"] = $tmp_input;
			}

			switch ($PROCESSED["occupant_type"]) {
				case "undergrad" :
				case "postgrad" :
					if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], "int"))) {
						$PROCESSED["proxy_id"] = $tmp_input;

						/**
						 * The event_id can only be provided if there is a proxy_id.
						 */
						if (isset($_POST["event_id"]) && ($tmp_input = clean_input($_POST["event_id"], "int"))) {
							$PROCESSED["event_id"] = $tmp_input;
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "You must select a valid occupant from the <strong>Occupant Name</strong> auto completer dialog.";
					}
				break;
				case "other" :
					if (isset($_POST["occupant_title"]) && ($tmp_input = clean_input($_POST["occupant_title"], array("trim", "notags")))) {
						$PROCESSED["occupant_title"] = $tmp_input;
					} else {
						$ERROR++;
						$ERRORSTR[] = "You must provide an occupant name or title in the <strong>Occupant Name / Title</strong> field.";
					}

					if (isset($_POST["cost_recovery"]) && ($_POST["cost_recovery"] == "1")) {
						$PROCESSED["cost_recovery"] = 1;
					}

					$PROCESSED["confirmed"] = 1;
				break;
				default :
					$ERROR++;
					$ERRORSTR[] = "You must provide a valid occupant type in order to continue.";
				break;
			}

			if (isset($_POST["notes"]) && ($tmp_input = clean_input($_POST["notes"], array("trim", "allowedtags")))) {
				$PROCESSED["notes"] = $tmp_input;
			}

			$inhabiting_date = validate_calendars("inhabiting", true, true, false);
			
			if ((isset($inhabiting_date["start"])) && ((int) $inhabiting_date["start"])) {
				$PROCESSED["inhabiting_start"] = (int) $inhabiting_date["start"];
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a date that the occupant will move into ".html_encode($APARTMENT_INFO["apartment_title"]);
			}

			if ((isset($inhabiting_date["finish"])) && ((int) $inhabiting_date["finish"])) {
				$PROCESSED["inhabiting_finish"] = (int) $inhabiting_date["finish"];
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a date that the occupant will vacate from ".html_encode($APARTMENT_INFO["apartment_title"]);
			}

			if (!$ERROR) {
				$PROCESSED["updated_last"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
				$PROCESSED["aschedule_status"] = "published";

				/**
				 * Check to ensure the availability still exists.
				 */
				$availability = regionaled_apartment_availability($PROCESSED["apartment_id"], $PROCESSED["inhabiting_start"], $PROCESSED["inhabiting_finish"]);
				if ($availability["openings"] > 0) {

					/**
					 * Check to make sure the provided event_id requires accommodation and belongs to the provided proxy_id.
					 */
					if ($PROCESSED["event_id"]) {
						$query = "	SELECT a.*, b.`etype_id`
									FROM `".CLERKSHIP_DATABASE."`.`events` AS a
									LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
									ON b.`event_id` = a.`event_id`
									WHERE a.`event_id`=".$db->qstr($PROCESSED["event_id"])."
									AND b.`etype_id`=".$db->qstr($PROCESSED["proxy_id"]);
						$event_info = $db->GetRow($query);
						if ($event_info) {
							if ($event_info["region_id"] == $APARTMENT_INFO["region_id"]) {
								if ($event_info["requires_apartment"] == 0) {
									$ERROR++;
									$ERRORSTR[] = "The learner has either specified they do not require accommodation for the selected event, or there are no accommodations available in the region this event takes place in.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "The region this accommodation is in and the region where this learning event takes place are not the same.";
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "We were unable to locate the provided event belonging to the selected individual.";

							application_log("error", "Unable to locate event_id [".$PROCESSED["event_id"]."] that belonged to proxy_id [".$PROCESSED["proxy_id"]."] when adding an occupant to an apartment. Database said: ".$db->ErrorMsg());
						}
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "There is no availability in this accommodation between ".date(DEFAULT_DATE_FORMAT, $PROCESSED["inhabiting_start"])." and ".date(DEFAULT_DATE_FORMAT, $PROCESSED["inhabiting_finish"]).".";
				}
			}

			if (!$ERROR) {
				if ($db->AutoExecute(CLERKSHIP_DATABASE.".apartment_schedule", $PROCESSED, "INSERT") && ($aschedule_id = $db->Insert_Id())) {
					/**
					 * If this is an undergrad or postgrad learner.
					 */
					if (in_array($PROCESSED["occupant_type"], array("undergrad", "postgrad"))) {
						/**
						 * Check if an event is attached to this schedule.
						 */
						if ((int) $PROCESSED["event_id"]) {
							if (!$db->AutoExecute(CLERKSHIP_DATABASE.".events", array("requires_apartment" => 0), "UPDATE", "event_id=".$db->qstr($PROCESSED["event_id"]))) {
								$NOTICE++;
								$NOTICESSTR[] = "We were unable to remove this learners entry from the " . $APARTMENT_INFO["department_title"] . " dashboard.";

								application_log("error", "Unable to set requires_apartment to 0 for event_id [".$PROCESSED["event_id"]."] after an apartment had been assigned. Database said: ".$db->ErrorMsg());
							}
						}
						
						/**
						 * Send notification to the learner that they are required to confirm their apartment status.
						 */
						if ($PROCESSED["proxy_id"] && !$PROCESSED["confirmed"]) {
							$recipient = array (
								"email" => get_account_data("email", $PROCESSED["proxy_id"]),
								"firstname" => get_account_data("firstname", $PROCESSED["proxy_id"]),
								"lastname" => get_account_data("lastname", $PROCESSED["proxy_id"])
							);

							$message_variables = array (
								"to_firstname" => $recipient["firstname"],
								"to_lastname" => $recipient["lastname"],
								"from_firstname" => $_SESSION["details"]["firstname"],
								"from_lastname" => $_SESSION["details"]["lastname"],
								"region" => $APARTMENT_INFO["region_name"],
								"confirmation_url" => ENTRADA_URL."/regionaled/view?id=".$aschedule_id,
								"application_name" => APPLICATION_NAME,
								"department_title" => $APARTMENT_INFO["department_title"],
								"department_id" => $APARTMENT_INFO["department_id"]
							);

							regionaled_apartment_notification("confirmation", $recipient, $message_variables);
						}
					}

					$url = ENTRADA_URL."/admin/regionaled/apartments/manage?id=".$APARTMENT_ID."&dstamp=".$PROCESSED["inhabiting_start"];

					$SUCCESS++;
					$SUCCESSSTR[] = "Successfully scehduled a new occupant in <strong>".html_encode($APARTMENT_INFO["apartment_title"])."</strong> from ".date(DEFAULT_DATE_FORMAT, $PROCESSED["inhabiting_start"])." and ".date(DEFAULT_DATE_FORMAT, $PROCESSED["inhabiting_finish"]).".<br /><br />You will now be redirected to the apartment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

					application_log("success", "Successfully added aschedule_id [".$aschedule_id."] to apartment_id [".$APARTMENT_ID."].");
				} else {
					$ERROR++;
					$ERRORSTR[] = "Unable to schedule this occupant into this apartment at this time. The system administrator has been informed of the error, please try again later.";

					application_log("error", "Unable to schedule an occupant into apartment_id [".$APARTMENT_ID."]. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :

			if (!isset($PROCESSED["inhabiting_start"])) {
				if (isset($_GET["dstamp"]) && ($tmp_input = clean_input($_GET["dstamp"], "int"))) {
					$timestamp = $tmp_input;
				} else {
					$timestamp = time();
				}

				$PROCESSED["inhabiting_start"] = startof("month", $timestamp);

				if (!isset($PROCESSED["inhabiting_finish"])) {
					$PROCESSED["inhabiting_finish"] = (strtotime("+1 week", $PROCESSED["inhabiting_start"]) - 1);
				}
			}
		break;
	}

	switch ($STEP) {
		case 2 :
			if ($ERROR) {
				echo display_errors();
			}

			if ($NOTICE) {
				echo display_notices();
			}

			if ($SUCCESS) {
				echo display_success();
			}
		break;
		case 1 :
		default :
			?>
			<h2>Add Occupant</h2>
			<?php
			if ($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?id=<?php echo $APARTMENT_ID; ?>&section=add" method="post" id="addOccupantForm">
				<input type="hidden" id="step" name="step" value="2" />
				<input type="hidden" id="proxy_id" name="proxy_id" value="<?php echo (isset($PROCESSED["proxy_id"]) ? (int) $PROCESSED["proxy_id"] : 0); ?>" />
				<input type="hidden" id="occupant_ref" name="occupant_ref" value="<?php echo (isset($PROCESSED["occupant_ref"]) ? html_encode($PROCESSED["occupant_ref"]) : ""); ?>" />

				<table style="width: 100%" cellspacing="0" cellpadding="2" summary="Add Occupant Form">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 10px; text-align: right">
								<span id="check-results"></span>
								<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?id=<?php echo $APARTMENT_ID; ?>'" />
								<input type="button" class="btn btn-primary" id="proceed-button" value="Proceed" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="occupant_name" class="form-required">Occupant Type</label></td>
							<td>
								<select id="occupant_type" name="occupant_type" style="width: 210px;">
									<option value="undergrad"<?php echo ((!isset($PROCESSED["occupant_type"]) || $PROCESSED["occupant_type"] == "undergrad") ? " selected=\"selected\"" : ""); ?>><?php echo APPLICATION_NAME; ?> Learner</option>
									<option value="other"<?php echo ((isset($PROCESSED["occupant_type"]) && $PROCESSED["occupant_type"] == "other") ? " selected=\"selected\"" : ""); ?>>Other Occupancy</option>
								</select>
							</td>
						</tr>
					</tbody>
					<tbody id="occupant_select_name">
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="occupant_name" class="form-required">Occupant Name</label></td>
							<td>
								<input type="text" id="occupant_name" name="fullname" size="30" value="<?php echo (isset($PROCESSED["fullname"]) ? html_encode($PROCESSED["fullname"]) : ""); ?>" autocomplete="off" style="width: 203px; vertical-align: middle" />
								<div class="autocomplete" id="occupant_name_auto_complete"></div>
								<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td><input type="checkbox" id="require_confirmation" name="require_confirmation" value="1"<?php echo ((!isset($PROCESSED["confirmed"]) || !$PROCESSED["confirmed"]) ? " checked=\"checked\"" : ""); ?> /></td>
							<td colspan="2">
								<label for="require_confirmation" class="form-nrequired">Send an e-mail requiring this occupant to confirm these accommodations.</label>
							</td>
						</tr>
					</tbody>
					<tbody id="occupant_select_title" style="display: none">
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="occupant_title" class="form-required">Occupant Name / Title</label></td>
							<td>
								<input type="text" id="occupant_title" name="occupant_title" size="30" value="<?php echo (isset($PROCESSED["occupant_title"]) ? html_encode($PROCESSED["occupant_title"]) : ""); ?>" style="width: 203px; vertical-align: middle" />
								<span class="content-small">(<strong>Example:</strong> &quot;<?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>&quot; <strong>or</strong> &quot;Accommodation is being painted&quot;)</span>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td><input type="checkbox" id="cost_recovery" name="cost_recovery" value="1"<?php echo ((isset($PROCESSED["cost_recovery"]) && $PROCESSED["cost_recovery"] == "1") ? " checked=\"checked\"" : ""); ?> /></td>
							<td colspan="2"><label for="cost_recovery" class="form-nrequired">Seek <strong>cost recovery</strong> from this occupant.</label></td>
						</tr>
					</tbody>
					<tbody id="associated_events" style="display: none">
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><span class="form-nrequired">Associated Event</span></td>
							<td id="associated_events_contents">&nbsp;</td>
						</tr>
					</tbody>
					<tbody>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php
						echo generate_calendars("inhabiting", "", true, true, $PROCESSED["inhabiting_start"], true, true, $PROCESSED["inhabiting_finish"], false);
						?>
						<tr>
							<td colspan="3">
								<div class="display-error" id="availability_details" style="display: none">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<script type="text/javascript" defer="defer">
			Event.observe(window, 'load', function() {
				updateOccupantOptions(false);
			});

			var autoCompleter = new Ajax.Autocompleter('occupant_name', 'occupant_name_auto_complete', '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=' + $F('occupant_type'), {
				frequency: 0.2,
				minChars: 2,
				afterUpdateElement: function (text, li) {
					$('proxy_id').setValue(li.id);
					$('occupant_ref').setValue($F('occupant_name'));

					fetchEvents();
				}
			});

			$('occupant_name').observe('change', checkOccupant);
			$('occupant_type').observe('change', updateOccupantOptions, true);
			$('proceed-button').observe('click', checkAvailability);

			function checkOccupant() {
				if ($('occupant_name') && $('occupant_ref') && $('proxy_id')) {
					if ($F('occupant_name') != $F('occupant_ref')) {
						$('proxy_id').setValue('');

						fetchEvents();
					}
				}

				return true;
			}

			function updateDates(start_date, finish_date) {
				if (start_date && $('inhabiting_start_date')) {
					$('inhabiting_start_date').setValue(start_date);
				}

				if (finish_date && $('inhabiting_finish_date')) {
					$('inhabiting_finish_date').setValue(finish_date);
				}
			}

			function updateOccupantOptions(resetForm) {
				if ($F('occupant_type')) {
					if (autoCompleter) {
						autoCompleter.url = '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=' + $F('occupant_type');
					}

					if (resetForm) {
						$('proxy_id').setValue('');
						$('occupant_ref').setValue('');
						$('occupant_name').setValue('');
						$('occupant_title').setValue('');
						$('associated_events').hide();
						$('associated_events_contents').update('');
					}

					switch ($F('occupant_type')) {
						case 'other' :
							$('occupant_select_name').hide();
							$('occupant_select_title').show();
						break;
						case 'undergrad' :
						case 'postgrad' :
						default :
							$('occupant_select_title').hide();
							$('occupant_select_name').show();
						break;
					}
				}

				fetchEvents();
			}

			function fetchEvents() {
				if (($F('occupant_type') != 'other') && ($F('proxy_id') > 0))  {
					new Ajax.Updater('associated_events_contents', '<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?id=<?php echo $APARTMENT_ID; ?>&section=api-events', {
						parameters : {
							proxy_id : $F('proxy_id'),
							event_id : '<?php echo (isset($PROCESSED["event_id"]) ? $PROCESSED["event_id"] : 0); ?>',
							region_id : '<?php echo (int) $APARTMENT_INFO["region_id"]; ?>'
						},
						onSuccess : function (response) {
							if (response.responseText != "") {
								new Effect.SlideDown('associated_events', { duration: 0.3 });
							} else {
								$('associated_events').hide();
								$('associated_events_contents').update('');
							}
						},
						onFailure : function (response) {
							$('associated_events').hide();
							$('associated_events_contents').update('');
						}
					});
				} else {
					$('associated_events').hide();
					$('associated_events_contents').update('');
				}
			}

			function checkAvailability() {
				$('availability_details').update('&nbsp;');

				var eventId = 0;

				$('addOccupantForm').getInputs('radio', 'event_id').each(function(input) {
					if (input.checked) {
						eventId = input.value;
					}
				});

				new Ajax.Request('<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments?section=api-availability', {
					parameters : {
						apartment_id : '<?php echo $APARTMENT_ID; ?>',
						event_id : eventId,
						proxy_id : $F('proxy_id'),
						start_date : $F('inhabiting_start_date'),
						finish_date : $F('inhabiting_finish_date')
					},
					onSuccess : function (response) {
						if (response.responseText == 1) {
							$('addOccupantForm').submit();
						} else {
							$('availability_details').update('<strong>Conflict Detected:</strong> There is no availability in this accommodation between ' + $F('inhabiting_start_date') + ' and ' + $F('inhabiting_finish_date') + '.');
							new Effect.Appear('availability_details', { duration: 0.3 });
						}
					},
					onFailure : function (response) {
						$('availability_details').update('There was a problem checking for availability at this time. Please try again later, or contact your system administrator for assistance.');
						new Effect.SlideDown('availability_details', { duration: 0.3 });
					}
				});
			}
			</script>
			<?php
		break;
	}
}