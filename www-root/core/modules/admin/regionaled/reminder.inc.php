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
 * The file that is loaded when the regional education office wants to remove someone from the list of students
 * who require accommodations.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONALED")) {
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
	if (isset($_POST["remind"]) && is_array($_POST["remind"]) && count($_POST["remind"])) {
		$recipients = array();

		foreach ($_POST["remind"] as $aschedule_id) {
			$aschedule_id = clean_input($aschedule_id, array("nows", "int"));
			if ($aschedule_id) {
				$query = "	SELECT a.*, c.`region_name`, d.`firstname`, d.`lastname`, d.`email`, g.`department_id`, g.`department_title`
							FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartments` AS b
							ON b.`apartment_id` = a.`apartment_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
							ON c.`region_id` = b.`region_id`
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = a.`proxy_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` AS f
							ON a.`apartment_id` = f.`apartment_id`
							LEFT JOIN `".AUTH_DATABASE."`.`departments` AS g
							ON f.`department_id` = g.`department_id`
							WHERE a.`aschedule_id` = ".$db->qstr($aschedule_id)."
							AND a.`proxy_id` > 0
							AND a.`confirmed` = 0
							AND f.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
				$result = $db->GetRow($query);
				if ($result) {
					/**
					 * Resend notification to the selected learner that they are required to confirm their apartment status.
					 */
					$recipient = array (
						"email" => $result["email"],
						"firstname" => $result["firstname"],
						"lastname" => $result["lastname"]
					);

					$message_variables = array (
						"to_firstname" => $recipient["firstname"],
						"to_lastname" => $recipient["lastname"],
						"from_firstname" => $_SESSION["details"]["firstname"],
						"from_lastname" => $_SESSION["details"]["lastname"],
						"region" => $result["region_name"],
						"confirmation_url" => ENTRADA_URL."/regionaled/view?id=".$aschedule_id,
						"application_name" => APPLICATION_NAME,
						"department_title" => $result["department_title"],
						"department_id" => $result["department_id"]
					);

					if (regionaled_apartment_notification("confirmation", $recipient, $message_variables)) {
						$recipients[] = html_encode($recipient["firstname"]." ".$recipient["lastname"]." <".$recipient["email"].">");
					}
				}
			}
		}

		$url = ENTRADA_URL."/admin/regionaled";
		
		if (count($recipients)) {
			$SUCCESS++;
			$SUCCESSSTR[$SUCCESS]  = "You have successfully sent accommodation confirmation reminder e-mails to the following recipients:\n";
			$SUCCESSSTR[$SUCCESS] .= "<ul>\n";
			$SUCCESSSTR[$SUCCESS] .= "	<li>".implode("</li>\n<li>", $recipients)."</li>\n";
			$SUCCESSSTR[$SUCCESS] .= "</ul>";
			$SUCCESSSTR[$SUCCESS] .= "<p>You will now be redirected to the " . $result["department_titile"] . " dashboard; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.</p>";
		}

		$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";

		display_status_messages(false);
	} else {
		header("Location: ".ENTRADA_URL."/admin/regionaled");
		exit;
	}
}