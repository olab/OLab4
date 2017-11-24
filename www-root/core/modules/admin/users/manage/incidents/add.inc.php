<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to add user incidents from the entrada_auth.user_incidents table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("incident", "create", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($PROXY_ID) {
		$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($PROXY_ID);
		$user_record = $db->GetRow($query);
		if ($user_record) {
			$BREADCRUMB[] = array("url" => "", "title" => "Add New Incident");

			echo "<h1>Adding Incident</h1>\n";

			// Error Checking
			switch ($STEP) {
				case 2 :
					/**
					 * Required field "incident_title" / Incident Title.
					 */
					if ((isset($_POST["incident_title"])) && ($incident_title = clean_input($_POST["incident_title"], array("trim", "notags")))) {
						if ((strlen($incident_title) >= 3) && (strlen($incident_title) <= 64)) {
							$PROCESSED["incident_title"] = $incident_title;
						} else {
							$ERROR++;
							$ERRORSTR[] = "The new incident title must be between 3 and 64 characters.";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "You must provide a valid title for this incident so it may be referenced later.";
					}

					/*
					 * Required field "incident_severity" / Incident Severity.
					 */
					if ((isset($_POST["incident_severity"])) && ($incident_severity = clean_input($_POST["incident_severity"], array("trim", "int")))) {
						$PROCESSED["incident_severity"] = $incident_severity;
					} else {
						$PROCESSED["incident_severity"] = 1;
					}

					/*
					 * Required field "incident_status" / Incident Status.
					 */
					if ((isset($_POST["incident_status"])) && $_POST["incident_status"]) {
						$PROCESSED["incident_status"] = 1;
					} else {
						$PROCESSED["incident_status"] = 0;
					}

					/**
					 * Required field "incident_date" / Incident Start (validated through validate_calendars function).
					 * Non-required field "follow_up_date" / Incident Finish (validated through validate_calendars function).
					 */
					$incident_date = validate_calendars("incident", true, false);
					if ((isset($incident_date["start"])) && ((int) $incident_date["start"])) {
						$PROCESSED["incident_date"] = (int) $incident_date["start"];
					}

					if ((isset($incident_date["finish"])) && ((int) $incident_date["finish"])) {
						$PROCESSED["follow_up_date"] = (int) $incident_date["finish"];
					} else {
						$PROCESSED["follow_up_date"] = 0;
					}

					/**
					 * Non-required field "incident_description" / Comments.
					 */
					if ((isset($_POST["incident_description"])) && ($incident_description = clean_input($_POST["incident_description"], array("trim", "notags")))) {
						$PROCESSED["incident_description"] = $incident_description;
					} else {
						$PROCESSED["incident_description"] = "";
					}

					if (!$ERROR) {
						$PROCESSED["proxy_id"] = $PROXY_ID;
						$PROCESSED["incident_author_id"] = $ENTRADA_USER->getID();
						if ($db->AutoExecute(AUTH_DATABASE.".user_incidents", $PROCESSED, "INSERT")) {
							$url = ENTRADA_URL."/admin/users/manage/incidents?id=".$PROXY_ID;

							$SUCCESS++;
							$SUCCESSSTR[] = "You have successfully updated the incident in the system.<br /><br />You will now be redirected to the user edit page for user id [".$PROXY_ID."]; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

							$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

							application_log("success", "Proxy ID [".$ENTRADA_USER->getID()."] successfully updated the incident id [".$INCIDENT_ID."].");
						} else {
							$ERROR++;
							$ERRORSTR[] = "Unable to update this user incident at this time. The MEdTech Unit has been informed of this error, please try again later.";

							application_log("error", "Unable to update user incident [".$INCIDENT_ID."]. Database said: ".$db->ErrorMsg());
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

			// Display Page.
			switch ($STEP) {
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
					?>
					<h2>Incident Details</h2>
					<form action="<?php echo ENTRADA_URL; ?>/admin/users/manage/incidents?section=add&amp;id=<?php echo $PROXY_ID; ?>&amp;step=2" method="post" class="form-horizontal">
						<div class="control-group">
							<label for="incident_title" class="control-label form-required">Incident Title</label>
							<div class="controls">
								<input type="text" id="incident_title" name="incident_title" value="<?php echo ((isset($PROCESSED["incident_title"])) ? html_encode($PROCESSED["incident_title"]) : ""); ?>" maxlength="64" />
							</div>
						</div>
						<div class="control-group">
							<label for="incident_severity" class="control-label form-nrequired">Severity</label>
							<div class="controls">
								<select id="incident_severity" name="incident_severity" style="width: 85px">
								<?php
								for ($i = 1; $i <= 5; $i++) {
									echo "<option value=\"".$i."\"".(isset($PROCESSED["incident_severity"]) && ((int) $PROCESSED["incident_severity"]) == $i ? " selected=\"selected\"" : (((!isset($PROCESSED["incident_severity"])) && ($i == 3)) ? " selected=\"selected\"" : "")).">".$i."</option>";
								}
								?>
								</select>
							</div>
						</div>
						<div class="control-group">
							<label for="incident_status" class="control-label form-nrequired">Status:</label>
							<div class="controls">
								<select id="incident_status" name="incident_status" style="width: 85px">
									<option value="1"<?php echo (isset($PROCESSED["incident_status"]) && ((int) $PROCESSED["incident_status"]) == 1 ? " selected=\"selected\" " : ""); ?>>Open</option>
									<option value="0"<?php echo (isset($PROCESSED["incident_status"]) && ((int) $PROCESSED["incident_status"]) == 0 ? " selected=\"selected\" " : ""); ?>>Closed</option>
								</select>
							</div>
						</div>
						<div class="control-group">
							<table>
								<tr>
									<?php echo generate_calendars("incident", "Incident", true, true, ((isset($PROCESSED["incident_date"])) ? $PROCESSED["incident_date"] : time()), true, false, ((isset($PROCESSED["follow_up_date"])) ? $PROCESSED["follow_up_date"] : 0), true, false, " Date", " Follow Up"); ?>
								</tr>
							</table>
						</div>
						<div class="control-group">
							<label for="incident_description" class="control-label form-nrequired">Detailed Incidient Description / Information</label>
							<div class="controls">
								<textarea id="incident_description" name="incident_description" style="width: 99%; height: 200px"><?php echo ((isset($PROCESSED["incident_description"])) ? html_encode($PROCESSED["incident_description"]) : ""); ?></textarea>
							</div>
						</div>
						<div class="pull-right">
							<input type="submit" class="btn btn-primary" value="Save incident" />
						</div>
				
					</form>
					<?php
				break;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to add a user incident you must provide a valid identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid user identifer when attempting to edit a user profile.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to add a user incident you must provide a user identifier.";

		echo display_error();

		application_log("notice", "Failed to provide incident identifer when attempting to edit a user incident.");
	}
}