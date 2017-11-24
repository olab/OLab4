<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for notifying course contacts when student scores below grade threshold.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
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

function grade_below_threshold_notice($assessment_list) {
	global $db, $AGENT_CONTACTS;

	$assessment_list = (array) $assessment_list;
	
	foreach ($assessment_list as $assessment_id => $assessment) {

		$mail = new Zend_Mail();
		$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
		$mail->addHeader("X-Section", "Gradebook Notification System",true);
		$mail->clearFrom();
		$mail->clearSubject();
		$mail->setFrom($AGENT_CONTACTS["agent-notifications"]["email"], APPLICATION_NAME.' Gradebook System');
		$mail->setSubject("Grade Below Threshold Notification");

		$message = "<pre>This notification is being sent to inform you that students scored below the assessment threshold.\n\n";
		$message .= "Course:\t\t\t".$assessment["course_name"]." - ".$assessment["course_code"]."\n";
		$message .= "Assessment:\t\t<a href=\"".ENTRADA_URL."/admin/gradebook/assessments?section=grade&id=".$assessment["course_id"]."&assessment_id=".$assessment_id."\">".$assessment["assessment_name"]."</a>\n";
		$message .= "Assessment ID:\t\t".$assessment_id."\n";
		$message .= "Grade Threshold:\t".$assessment["threshold"]."%\n\n";
		$message .= "The following students scored below the assessment threshold:\n";
		$message .= "-------------------------------------------------------------\n\n";

		foreach ($assessment["students"] as $proxy_number => $student) {
			$query = "	UPDATE `assessment_grades`
						SET `threshold_notified` = '1'
						WHERE `grade_id` = ".$db->qstr($student["grade_id"]);
			$result = $db->Execute($query);
			$message .= "Student:\t\t".$student["student_name"]." - [".$student["student_email"]."] \n";
			$message .= "Student Number:\t\t".$student["student_number"]."\n";
			$message .= "Grade Recieved:\t\t".$student["assessment_grade"]."%\n\n";
		}
		$message .= "</pre>";
		$mail->setBodyHtml($message);
		$query = "	SELECT a.`contact_type`, a.`contact_order`, b.`prefix`, b.`firstname`, b.`lastname`, b.`email`, a.`proxy_id`
			FROM ".DATABASE_NAME.".`course_contacts` AS a 
			JOIN ".AUTH_DATABASE.".`user_data` AS b 
			ON a.`proxy_id` = b.`id`  
			WHERE a.`course_id` = ".$db->qstr($assessment["course_id"])."
			ORDER BY a.`contact_type` DESC, a.`contact_order` ASC";
		$contacts = $db->GetAll($query);
		foreach ($contacts as $contact) {
			$mail->addTo($contact["email"], (!empty($contact["prefix"]) ? $contact["prefix"]." " : "").$contact["firstname"]." ".$contact["lastname"] );
			$contact_proxies[] = $contact["proxy_id"];
		}
		$sent = true;
		try {
			$mail->send();
		}
		catch (Exception $e) {
			$sent = false;
		}
		if($sent) {
			application_log("success", "Sent grade below threshold notification to Program Coordinators / Directors [".implode(",", $contact_proxies)."].");
			$return_value = true;
		} else {
			application_log("error", "Unable to send grade below threshold notification to Program Coordinators / Directors [".implode(",", $contact_proxies)."].");
		}
	}	
}

$query = "SELECT a.`assessment_id`, a.`name` AS `assessment_name`, a.`course_id`, a.`grade_threshold`, b.`grade_id`, b.`proxy_id`, b.`value`, c.`firstname`, c.`lastname`, c.`email`, c.`number` AS `student_number`, d.`course_name`, d.`course_code`
		  FROM `assessments` AS a
		  JOIN `assessment_grades` AS b
		  ON a.`assessment_id` = b.`assessment_id`
		  JOIN ".AUTH_DATABASE.".`user_data` AS c 
		  ON b.`proxy_id` = c.`id`  
		  JOIN `courses` AS d
		  ON a.`course_id` = d.`course_id`
		  WHERE a.`grade_threshold` != 0
		  AND a.`active` = 1
		  AND b.`value` < a.`grade_threshold`
		  AND b.`threshold_notified` = '0'
		  AND (a.`marking_scheme_id` = '2' OR a.`marking_scheme_id` = '3')";

if ($results = $db->GetAll($query)) {
		foreach ($results as $result) {
			$assessment_list[$result["assessment_id"]]["course_id"] =			$result["course_id"];
			$assessment_list[$result["assessment_id"]]["course_name"] =			$result["course_name"];
			$assessment_list[$result["assessment_id"]]["course_code"] =			$result["course_code"];
			$assessment_list[$result["assessment_id"]]["assessment_name"] =		$result["assessment_name"];
			$assessment_list[$result["assessment_id"]]["threshold"] =			$result["grade_threshold"];
			$assessment_list[$result["assessment_id"]]["students"][$result["proxy_id"]]["grade_id"] =			$result["grade_id"];
			$assessment_list[$result["assessment_id"]]["students"][$result["proxy_id"]]["assessment_grade"] =	$result["value"];
			$assessment_list[$result["assessment_id"]]["students"][$result["proxy_id"]]["student_name"] =		$result["lastname"].", ".$result["firstname"];
			$assessment_list[$result["assessment_id"]]["students"][$result["proxy_id"]]["student_email"] =		$result["email"];
			$assessment_list[$result["assessment_id"]]["students"][$result["proxy_id"]]["student_number"] =		$result["student_number"];
		}
		grade_below_threshold_notice($assessment_list);
};


?>
