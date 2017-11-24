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
 * Serves as a dashboard type file for a particular apartment the Regional Education module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 */

if (!defined("IN_SCHEDULE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["confirmed"]) && ((int) $_POST["confirmed"] == 1)) {
		$url = "/admin/regionaled/apartments/manage?id=".$APARTMENT_ID."&dstamp=".$ASCHEDULE_INFO["inhabiting_start"];
		$query = "DELETE FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` WHERE `aschedule_id` = ".$db->qstr($ASCHEDULE_ID)." AND `apartment_id` = ".$db->qstr($APARTMENT_ID);
		if ($db->Execute($query) && ($db->Affected_Rows() == 1)) {
			$SUCCESS++;
			$SUCCESSSTR[] = "You have successfully removed <strong>".html_encode($ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."</strong> from <strong>".html_encode($APARTMENT_INFO["apartment_title"])."</strong> between ".date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"])." and ".date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]).".<br /><br />You will now be redirected to the apartment schedule; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL.$url."\" style=\"font-weight: bold\">click here</a> to continue.";

			application_log("success", "Successfully removed proxy_id [".$ASCHEDULE_INFO["proxy_id"]."] from apartment_id [".$APARTMENT_ID."] / aschedule_id [".$ASCHEDULE_ID."].");

			if ((int) $ASCHEDULE_INFO["event_id"]) {
				/**
				 * Reset the requires_apartment flag so this person is put back on the Regional Education dashboard.
				 */
				if (!$db->AutoExecute(CLERKSHIP_DATABASE.".events", array("requires_apartment" => 1), "UPDATE", "event_id=".$db->qstr($ASCHEDULE_INFO["event_id"]))) {
					$NOTICE++;
					$NOTICESSTR[] = "We were unable to add this learners event back onto the " . $APARTMENT_INFO["department_title"] . " dashboard as a todo task.";

					application_log("error", "Unable to set requires_apartment to 1 for event_id [".$ASCHEDULE_INFO["event_id"]."] after proxy_id [".$ASCHEDULE_INFO["proxy_id"]."] had been removed from aschedule_id [".$ASCHEDULE_ID."]. Database said: ".$db->ErrorMsg());
				}
			}

			if (isset($_POST["notify"]) && ((int) $_POST["notify"] == 1)) {
				$apartment_address  = (($APARTMENT_INFO["apartment_number"] != "") ? $APARTMENT_INFO["apartment_number"]."-" : "").$APARTMENT_INFO["apartment_address"]."\n";
				$apartment_address .= $APARTMENT_INFO["region_name"].($APARTMENT_INFO["province"] ? ", ".$APARTMENT_INFO["province"] : "")."\n";
				$apartment_address .= $APARTMENT_INFO["apartment_postcode"].", ".$APARTMENT_INFO["country"];

				$message_variables = array (
					"to_firstname" => $ASCHEDULE_INFO["firstname"],
					"to_lastname" => $ASCHEDULE_INFO["lastname"],
					"from_firstname" => $_SESSION["details"]["firstname"],
					"from_lastname" => $_SESSION["details"]["lastname"],
					"region" => $APARTMENT_INFO["region_name"],
					"apartment_address" => $apartment_address,
					"inhabiting_start" => date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"]),
					"inhabiting_finish" => date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]),
					"application_name" => APPLICATION_NAME,
					"department_title" => $APARTMENT_INFO["department_title"],
					"department_id" => $APARTMENT_INFO["department_id"]
				);

				$recipient = array (
					"email" => $ASCHEDULE_INFO["email"],
					"firstname" => $ASCHEDULE_INFO["firstname"],
					"lastname" => $ASCHEDULE_INFO["lastname"]
				);

				regionaled_apartment_notification("delete", $recipient, $message_variables);
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "Unable to remove <strong>".html_encode($ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."</strong> from <strong>".html_encode($APARTMENT_INFO["apartment_title"])."</strong> at this time. The system administrator has been notified of this issue, please try again later.<br /><br />You will now be redirected to the apartment schedule; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL.$url."\" style=\"font-weight: bold\">click here</a> to continue.";

			application_log("error", "Unable to remove proxy_id [".$ASCHEDULE_INFO["proxy_id"]."] from apartment_id [".$APARTMENT_ID."] / aschedule_id [".$ASCHEDULE_ID."]. Database said: ".$db->ErrorMsg());
		}
	} else {
		$url = "/admin/regionaled/apartments/manage/schedule?id=".$APARTMENT_ID."&sid=".$ASCHEDULE_ID;

		$ERROR++;
		$ERRORSTR[] = "You must confirm that you wish to remove <strong>".html_encode($ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."</strong> from <strong>".html_encode($APARTMENT_INFO["apartment_title"])."</strong>.<br /><br />You will now be redirected to the apartment schedule; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL.$url."\" style=\"font-weight: bold\">click here</a> to continue.";

		application_log("error", "The remove request for proxy_id [".$ASCHEDULE_INFO["proxy_id"]."] from apartment_id [".$APARTMENT_ID."] / aschedule_id [".$ASCHEDULE_ID."] was not confirmed. This step shouldn't have been accessible otherwise.");
	}

	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL.$url."\'', 5000)";

	display_status_messages();
}