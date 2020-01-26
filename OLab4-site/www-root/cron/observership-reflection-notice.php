<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * A cron job to notify students of observerships which require reflection entry.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's Univerity. All Rights Reserved.
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

if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
	if (!file_exists(CACHE_DIRECTORY."/observership-reflections.lck")) {
		if (@file_put_contents(CACHE_DIRECTORY."/observership-reflections.lck", "L_O_C_K")) {
			application_log("notice", "observership-reflections.lck created.");

			$query = "	SELECT a.`id` AS `observership_id`, a.`title`, a.`start`, a.`end`, b.`email`, b.`firstname`, b.`lastname`
						FROM `student_observerships` AS a 
						JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`student_id` = b.`id`
						WHERE 
							IF (a.`end` IS NOT NULL, a.`end`, a.`start`) < UNIX_TIMESTAMP(NOW())
							AND `notice_sent` IS NULL
							AND `status` = 'approved'
							AND `reflection_id` IS NULL;";

			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {

					$message_body  = "Hello ".$result["firstname"]." ".$result["lastname"].",\n\n";
					$message_body .= "Your observership, ".$result["title"].", finished on ".date("Y-m-d", $result["end"]).".\n\n";
					$message_body .= "Please use the following link to enter a personal reflection on the observership.\n\n";
					$message_body .= ENTRADA_URL . "/profile/observerships?section=reflection&id=".$result["observership_id"]." \n\n";
					$message_body .= "When your reflection has been enter the preceptor will be sent an email requestion confirmation of the observership.\n\n";
					$message_body .= "Thank you,\n\n";
					$message_body .= $AGENT_CONTACTS["observership"]["name"];
					
					$mail = new Zend_Mail();
					$mail->addHeader("X-Section", "Observership System", true);
					$mail->setFrom($AGENT_CONTACTS["observership"]["email"], $AGENT_CONTACTS["observership"]["name"]);
					$mail->setSubject("Observership Reflection Notification");
					$mail->setBodyText($message_body);
					$mail->addTo($result["email"], $result["firstname"] . " " . $result["lastname"]);
					
					if ($mail->send()) {
						$query = "UPDATE `student_observerships` SET `notice_sent` = UNIX_TIMESTAMP(NOW()) WHERE `id` = ".$db->qstr($result["observership_id"]);
						if ($db->Execute($query)) {
							application_log("success", "Observership reflection notice sent to ".$result["email"]." and DB updated.");
						} else {
							application_log("error", "Observership reflection notice sent to ".$result["email"]." but DB failed to update, DB said: ".$db->ErrorMsg());
						}
					} else {
						application_log("error", "Observership reflection notification failed to send.");
					}
					
				}
			} else {
				application_log("notice", "No observership reflection notices needed to be sent.");
			}

			/*
			 * Delete the lock file.
			 */
			if (unlink(CACHE_DIRECTORY."/observership-reflections.lck")) {
				application_log("success", "observership-reflections.lck deleted.");
			} else {
				application_log("error", "Unable to delete observership-reflections.lck");
			}

		} else {
			application_log("error", "Could not write observership-reflections.lck, exiting.");
		}
	} else {
		application_log("error", "observership-reflections.lck found, exiting.");
	}
} else {
	application_log("error", "Error with cache directory [".CACHE_DIRECTORY."], not found or not writable.");
}