<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for emailing occupants 30 days prior to their starting
 * inhabiting date.
 *
 * Regional Education module cron job to run daily.  It will find students 
 * who are due to leave their accomodations 7 days from today and will email 
 * them a notice.  The notice is slightly different if the occupant is at 415 
 * Simcoe St. Oshawa. 
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2013 Queen's Univerity. All Rights Reserved.
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

$search = array("%LEARNER_NAME%");

$mail = new Zend_Mail();

//Department id for Regional Education is 170.
$query = "	SELECT c.`username`,
				CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `fullname`,
				c.`email`,
				a.`apartment_id`,
				a.`apartment_title`,
				a.`apartment_number`,
				a.`apartment_address`,
				d.`region_name`,
				a.`apartment_province`,
				b.`aschedule_id`,
				170 as `department_id`,
				FROM_UNIXTIME(b.inhabiting_start) AS inhabiting_start,
				FROM_UNIXTIME(b.inhabiting_finish) AS inhabiting_finish
			FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
			LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS b
			ON a.`apartment_id` = b.`apartment_id`
			LEFT JOIN `".AUTH_DATABASE."`.`user_data` as c
			ON b.`proxy_id` = c.`id`
			LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` as d
			ON a.`region_id` = d.`region_id`
			WHERE b.`occupant_title` = ''	
			AND a.`department_id` = 170
			AND DATEDIFF(FROM_UNIXTIME(b.`inhabiting_finish`), FROM_UNIXTIME('".time()."')) = 7";


$occupants = $db->GetAll($query);

if ($occupants) {
	foreach ($occupants as $occupant) {
		$email_body = file_get_contents(ENTRADA_ABSOLUTE . "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/email/regionaled-learner-predeparture-notification.txt");

		$mail->addHeader("X-Section",  "Regional Education Notification System", true);
		$mail->setFrom($AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["email"], $AGENT_CONTACTS["agent-regionaled"][$occupant["department_id"]]["name"]);
		$mail->clearSubject();
		$mail->setSubject("Regional Accomodation: ".$occupant["region_name"]);
		$replace = array($occupant["fullname"]);
		$mail->setBodyText(str_replace($search, $replace, $email_body));
		$mail->clearRecipients();
		$mail->addTo($occupant["email"],$occupant["fullname"]);
		$mail->send();
		//clean up
		$mail->clearFrom();
	}
}
