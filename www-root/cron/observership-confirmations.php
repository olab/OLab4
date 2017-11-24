<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for emailing observership preceptors.
 *
 * Setup to run daily in CRON.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's Univerity. All Rights Reserved.
 */

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
					dirname(__FILE__) . "/../",
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
/*
 * Fetch the unconfirmed observerships whose preceptors have not been notified. 
 */
$query = "	SELECT *
			FROM `student_observerships` 
			WHERE ((FROM_UNIXTIME(`start`) <= NOW() AND FROM_UNIXTIME(`end`) <= NOW()) OR (FROM_UNIXTIME(`start`) <= NOW() AND `end` IS NULL))
				AND (`notice_sent` = '0' OR `notice_sent` IS NULL OR DATEDIFF(NOW(), FROM_UNIXTIME(`notice_sent`)) >= '7')
				AND `status` = 'approved'
				AND (`reflection_id` IS NOT NULL AND `reflection_id` <> 0)";

$results = $db->GetAll($query);

if ($results) {
	foreach ($results as $result) {
		/*
		 * Create the observership object, send the notification, and update it with the new time.
		 */
		sendNotification($result);
	}
}

function sendNotification($result) {
	global $AGENT_CONTACTS, $db;
		
	if ($result["preceptor_proxy_id"] != 0) {
		$query = "SELECT `prefix`, `firstname`, `lastname`, `email` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($result["preceptor_proxy_id"]);
		$preceptor = $db->GetRow($query);
		$preceptor_email = $preceptor["email"];
		$preceptor_name = (!empty($preceptor["prefix"]) ? $preceptor["prefix"]. " " : "").$preceptor["firstname"]. " " . $preceptor["lastname"];
	} else {
		$preceptor_email = $result["preceptor_email"];
		$preceptor_name = $result["preceptor_firstname"]. " " .$result["preceptor_lastname"];
	}
	
	if ($preceptor_email) {

		$student_user = User::fetchRowByID($result["student_id"]);
		
		$message	= $preceptor_name.",\n\n";
		$message   .= "You have been indicated as the preceptor on an Observership:\n".
					  "======================================================\n".

					  "Submitted at: ".date("Y-m-d H:i", time())."\n".
					  "Submitted by: ".$student_user->getFullname(false)."\n".
					  "E-Mail Address: ".$student_user->getEmail()."\n".

					  "Observership details:\n".
					  "---------------------\n".
					  "Title: ".$result["title"]."\n".
					  "Activity Type: ".$result["activity_type"]."\n".
					  ($result["activity_type"] == "ipobservership" ? "IP Observership Details: ".$result["activity_type"]."\n" : "").
					  "Clinical Discipline: ".$result["clinical_discipline"]."\n".
					  "Organisation: ".$result["organisation"]."\n".
					  "Address: ".$result["address_l1"]."\n".
					  "Preceptor: ".$preceptor_name ."\n".
					  "Start date: ".date("Y-m-d", $result["start"])."\n".
					  "End date: ".date("Y-m-d", $result["end"])."\n\n".

					  "The observership request can be approved or rejected at the following address:\n".
					  ENTRADA_URL."/confirm_observership?unique_id=".$result["unique_id"];

		$mail = new Zend_Mail();
		$mail->addHeader("X-Section", "Observership Confirmation", true);
		$mail->setFrom($AGENT_CONTACTS["general-contact"]["email"], $AGENT_CONTACTS["general-contact"]["name"]);
		$mail->setSubject("Observership Confirmation");
		$mail->setBodyText($message);
		$mail->addTo($preceptor_email, $preceptor_name);

		if ($mail->send()) {
			$query = "UPDATE `student_observerships` SET `notice_sent` = ".$db->qstr(time())." WHERE `id` = ".$db->qstr($result["id"]);
			if ($db->Execute($query)) {
				return true;
				application_log("success", "Sent observership notification to [".$preceptor_email."] for observership_id [".$result["id"]."].");
			}
		} else {
			application_log("error", "Unable to send observership [observership_id: ".$result["id"]."] confirmation request.");
			return false;
		}

	}
}

?>