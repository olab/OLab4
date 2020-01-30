<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for emailing apartment contacts once a month with the
 * scheduled occupants for the month.
 * 
 * Setup to run the first of each month in CRON.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's Univerity. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
					dirname(__FILE__) . "/../core",
					dirname(__FILE__) . "/../core/includes",
					dirname(__FILE__) . "/../core/library",
                    dirname(__FILE__) . "/../core/library/vendor",
					get_include_path(),
				)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$search = array("%CONTACT_NAME%", "%APARTMENT_TITLE%", "%OCCUPANTS_AND_DATES%", "%REGIONALED_CONTACT%", "%REGIONALED_EMAIL%", "%APPLICATION_NAME%");
$date = time();
$months = Array ($date, $date+2629743, $date+(2629743*2), $date+(2629743*3));
$msg_body = "";
$contact_id = 0;

/*
 *  fetch the apartments
 */
$query = "	SELECT a.`apartment_id`, 
				   a.`apartment_title`, 
					CONCAT_WS(' ',a.super_firstname, a.super_lastname) AS `super_full`, 
					`super_email`, 
					CONCAT_WS(' ',a.keys_firstname,a.keys_lastname) AS `keys_full`, 
					a.`keys_email`,
					b.`region_name`,
					c.`department_id`,
					c.`department_title`
				FROM `".CLERKSHIP_DATABASE."`.`apartments` a
				JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
				ON a.`region_id` = b.`region_id`
				LEFT JOIN `".AUTH_DATABASE."`.`departments` as c
				ON a.`department_id` = c.`department_id`
				WHERE a.`available_finish` >= ".$db->qstr(time())."
				OR a.`available_finish` = 0
				GROUP BY a.`apartment_id`
				ORDER BY a.`region_id`, `keys_full`";

if ($apartments = $db->GetAll($query)) {

	foreach ($apartments as $apartment) {
		$msg_body = "";

		$current_contact = ((($apartment["super_full"] != $apartment["keys_full"]) & (!empty($apartment["keys_full"]))) ? $apartment["keys_full"] : $apartment["super_full"] );
		$current_email = ((($apartment["super_full"] != $apartment["keys_full"]) & (!empty($apartment["keys_full"]))) ? $apartment["keys_email"] : $apartment["super_email"] );

		$messages[$current_email]["name"] = $current_contact;
		$messages[$current_email]["region_name"] = $apartment["region_name"];

		if ($occupants = regionaled_apartment_occupants($apartment["apartment_id"], $months[0], $months[3])) {
			$msg_body .= "The following learners are scheduled to stay in <strong>".$apartment["apartment_title"]."</strong>.<br /><br />";
			$occupants_list = "<table width=\"100%\" cellpadding=\"0\" border=\"1\">\n";
			$occupants_list .= "\t\t<td width=\"40%\" valign=\"top\"><strong>Occupant Name</strong></td>\n";
			$occupants_list .= "\t\t<td width=\"30%\" valign=\"top\"><strong>Starting Date</strong></td>\n";
			$occupants_list .= "\t\t<td width=\"30%\" valign=\"top\"><strong>Ending Date</strong></td>\n";
			$occupants_list .= "\t</tr>\n";

			foreach ($occupants as $occupant) {

				if (!empty($occupant["occupant_title"])) {
					$occupant_name = $occupant["occupant_title"];
				} else {
					$occupant_name = $occupant["fullname"];
				}
				$occupants_list .= "\t<tr>\n";
				$occupants_list .= "\t\t<td>".$occupant_name."</td>\n";
				$occupants_list .= "\t\t<td>".date("l, F j, Y",  $occupant["inhabiting_start"])."</td>\n";
				$occupants_list .= "\t\t<td>".date("l, F j, Y",  $occupant["inhabiting_finish"])."</td>\n";
				$occupants_list .= "\t</tr>\n";
			}
			$occupants_list .= "</table><br />\n";

			$msg_body .= $occupants_list;
			$messages[$current_email]["messages"][] = $msg_body;
		} else {
			$messages[$current_email]["messages"][] = "There are currently no learners are scheduled to stay in <strong>".$apartment["apartment_title"]."</strong>.<br /><br />";
		}

	}

	foreach ($messages as $email => $message_body) {

		$message_content = "";
		foreach ($message_body["messages"] as $message) {
			$message_content .= $message;
		}

		$replace = array($message_body["name"], $message_body["region_name"], $message_content, $AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["name"], $AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["email"], $AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["name"]);

		if (!isset($occupants_email)) {
			$occupants_email = nl2br(file_get_contents(ENTRADA_ABSOLUTE . "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/email/regionaled-apartment-occupants-monthly-notification.txt"));
		}

		$mail = new Zend_Mail();
		$mail->addHeader("X-Section", $apartment["department_title"] . $apartment["department_title"] . "Accommodation Notification System", true);
		$mail->setFrom($AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["email"], $AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["name"]);
		$mail->addTo($email, $message_body["name"]);
		$mail->addCc($AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["email"], $AGENT_CONTACTS["agent-regionaled"][$apartment["department_id"]]["name"]);
		$mail->setSubject("Queen's Monthly Accomodation Schedule for {$message_body["region_name"]}");
		$mail->setBodyHtml(str_replace($search, $replace, $occupants_email));

		if ($mail->send()) {
			application_log("success", "Successfully sent apartment contacts notification to [".$email."].");
		} else {
			application_log("error", "Failed to send apartment contacts notification to [".$email."].");
		}

		unset($mail);
	}
	
} else {
	application_log("error", "Unable to find any available apartments, DB said: ".$db->ErrorMsg());
}