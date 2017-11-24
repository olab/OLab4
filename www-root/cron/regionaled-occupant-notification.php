<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for emailing occupants 30 days prior to their starting
 * inhabiting date.
 *
 * Setup to run daily in CRON.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's Univerity. All Rights Reserved.
 * if they're doing a clerkship rotation in a location that the regional education manages (oshawa)
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

$search = array("%LEARNER_NAME%", "%ACCOMMODATION_TITLE%", "%ACCOMMODATION_NUMBER%", "%ACCOMMODATION_STREET%", "%ACCOMMODATION_REGION%","%INHABITING_START%","%INHABITING_FINISH%","%ACCOMMODATION_CONTACT_NAME%","%ACCOMMODATION_CONTACT_INFO%", "%ACCOMMODATION_LINK%", "%APPLICATION_NAME%");

$mail = new Zend_Mail();

$query = "	SELECT c.`username`,
				CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `fullname`,
				c.`email`,
				a.`apartment_title`,
				a.`apartment_number`,
				a.`apartment_address`,
				d.`region_name`,
				a.`apartment_province`,
				b.`aschedule_id`,
				FROM_UNIXTIME(b.inhabiting_start) AS inhabiting_start,
				FROM_UNIXTIME(b.inhabiting_finish) AS inhabiting_finish,
				e.`department_id`,
				e.`department_title`
			FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
			LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS b
			ON a.`apartment_id` = b.`apartment_id`
			LEFT JOIN `".AUTH_DATABASE."`.`user_data` as c
			ON b.`proxy_id` = c.`id`
			LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` as d
			ON a.`region_id` = d.`region_id`
			LEFT JOIN `".AUTH_DATABASE."`.`departments` as e
			ON a.`department_id` = e.`department_id`
			WHERE b.`occupant_title` = ''
			AND DATEDIFF(FROM_UNIXTIME(b.`inhabiting_start`), FROM_UNIXTIME('".time()."')) = 30";

$occupants = $db->GetAll($query);

if ($occupants) {
	$email_body = file_get_contents(ENTRADA_ABSOLUTE . "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/email/regionaled-learner-accommodation-notification.txt");

	foreach ($occupants as $occupant) {
		$mail->addHeader("X-Section",  $occupant["department_title"] . " Accommodation Notification System", true);
		$mail->setFrom($AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["email"], $AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["name"]);
		$mail->clearSubject();
		$mail->setSubject("Regional Accomodation: ".$occupant["region_name"]);
		$replace = array($occupant["fullname"], 
						$occupant["apartment_title"], 
						$occupant["apartment_number"], 
						$occupant["apartment_address"], 
						$occupant["region_name"],
						date("l, F j, Y",  strtotime($occupant["inhabiting_start"])),
						date("l, F j, Y",  strtotime($occupant["inhabiting_finish"])), 
						$AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["name"], 
						$AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["email"],
						"https://meds.queensu.ca/central/regionaled/view?id=".$occupant["aschedule_id"], 
						$AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["name"]);
		$mail->setBodyText(str_replace($search, $replace, $email_body));
		$mail->clearRecipients();
		$mail->addTo($occupant["email"],$occupant["fullname"]);
		$mail->send();
	}
}
