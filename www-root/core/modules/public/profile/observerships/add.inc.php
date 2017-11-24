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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
require_once 'core/library/Classes/mspr/Observership.class.php';
if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=add", "title" => "Create Observerships");

switch($STEP){
	case 2:
	
	$OBSERVERSHIP = Observership::fromArray($_POST,"add");
	if (!$OBSERVERSHIP->isValid()){
		add_error("<strong>Invalid data entered</strong>. Please confirm everything and try again.");
	} else {
		if ($OBSERVERSHIP->create()) {
			
			if (isset($AGENT_CONTACTS["observership"])) {
				$message = "";
				$message .= "The following observership request has been submitted:\n";
				$message .= "======================================================\n";
				$message .= "\n";
				$message .= "Submitted at: ".date("Y-m-d H:i", time())."\n";
				$message .= "Submitted by: ".$ENTRADA_USER->getFullname(false)."\n";
				$message .= "E-Mail Address: ".$ENTRADA_USER->getEmail()."\n";
				$message .= "\n";
				$message .= "Observership details:\n";
				$message .= "---------------------\n";
				$message .= "Title: ".$OBSERVERSHIP->getTitle()."\n";
				$message .= "Activity Type: ".$OBSERVERSHIP->getActivityType()."\n";
				$message .= ($OBSERVERSHIP->getActivityType() == "ipobservership" ? "IP Observership Details: ".$OBSERVERSHIP->getObservershipDetails() : "")."\n";
				$message .= "Clinical Discipline: ".$OBSERVERSHIP->getClinicalDiscipline()."\n";
				$message .= "Organisation: ".$OBSERVERSHIP->getOrganisation()."\n";
				$message .= "Address: ".$OBSERVERSHIP->getAddressLine1()."\n";
				$message .= "\t\t ".$OBSERVERSHIP->getAddressLine2()."\n";
				$message .= "Preceptor: ".$OBSERVERSHIP->getPreceptorFirstname()." ".$OBSERVERSHIP->getPreceptorLastname()."\n";
				$message .= "Start date: ".date("Y-m-d", $OBSERVERSHIP->getStart())."\n";
				$message .= "End date: ".date("Y-m-d", $OBSERVERSHIP->getEnd())."\n";
				$message .= "\n";
				$message .= "The observership request can be approved or rejected at the following address:\n";
				$message .= ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$ENTRADA_USER->getID();
				
				$mail = new Zend_Mail();
				$mail->addHeader("X-Section", "Observership Notification System", true);
				$mail->setFrom($AGENT_CONTACTS["observership"]["email"], $AGENT_CONTACTS["observership"]["name"]);
				$mail->clearSubject();
				$mail->setSubject("Observership Request Created");
				$mail->setBodyText($message);
				$mail->clearRecipients();
				$mail->addTo($AGENT_CONTACTS["observership"]["email"], $AGENT_CONTACTS["observership"]["name"]);

				if ($mail->send()) {
					application_log("success", "Successfully sent email to observership administrator.");
				} else {
					application_log("error", "Failed to sent email to observership administrator.");
				}
			}
			
			$url = ENTRADA_URL."/profile/observerships";
			echo display_success("Successfully created Observership. You will be redirected to your Observership index in <strong>5 seconds</strong> or <a href=\"".$url."\">click here</a> to go there now.");
			$ONLOAD[]	= "setTimeout('window.location=\\'".$url."\\'', 5000)";
			return;
		} else {
			add_error("<strong>Error occurred creating Observership</strong>. Please confirm everything and try again.");
		}
	}

	break;
	case 1:
	default:
		$OBSERVERSHIP = new Observership();	
		break;
}


define('PUBLIC_OBSERVERSHIP_FORM',true);

$ACTION = "Create";

require_once 'form.inc.php';