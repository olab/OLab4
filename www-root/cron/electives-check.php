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
 * Sends the Clerkship Electives Coordinator a notification of pending
 * elective requests.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <ad29@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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

/**
 * NOTIFICATION CONFIGURATION OPTIONS
 */

$NOTIFICATION_MESSAGE = array();
$NOTIFICATION_MESSAGE["subject"] = "Clerkship Electives Awaiting Approval";

$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/default/email/electives-approval-notification.txt");

$NOTIFICATION_REMINDERS = array();

/**
 * END OF NOTIFICATION CONFIGURATION OPTIONS
 */

$START_OF_TODAY	= strtotime("00:00:00");

// Setup Zend_mail to do the work.
$mail = new Zend_Mail("iso-8859-1");
$mail->addHeader("X-Priority", "3");
$mail->addHeader("Content-Transfer-Encoding", "8bit");
$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);

$needToSend = false;

$output = array();
$query = "SELECT COUNT(`event_id`) AS `total` FROM `".CLERKSHIP_DATABASE."`.`events` WHERE `event_type` = 'elective' AND `event_status` = 'approval'";
$result	= $db->GetRow($query);
if ($result["total"] > 9) {
    $needToSend = true;
} else {
    $twoWeeksAgo = strtotime("-2 weeks");

    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`events` WHERE `event_type` = 'elective' AND `event_status` = 'approval' AND `modified_last` < ".$db->qstr($twoWeeksAgo);

    if ($result	= $db->GetRow($query)) {
        $needToSend = true;
    }
}

$search	= array(
    "%TO_NAME%",
    "%EVENT_LINK%"
);

$replace = array(
    $AGENT_CONTACTS["agent-clerkship"]["name"],
    ENTRADA_URL."/admin/clerkship/electives"
);

$mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
$mail->setSubject($NOTIFICATION_MESSAGE["subject"]);
$mail->setReplyTo($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
$mail->setBodyText(str_replace($search, $replace, $NOTIFICATION_MESSAGE["textbody"]));

if ($needToSend) {
    $email_address = get_account_data("email", $AGENT_CONTACTS["agent-clerkship"]["director_ids"]["0"]);
    $name = get_account_data("firstlast", $AGENT_CONTACTS["agent-clerkship"]["director_ids"]["0"]);
    $mail->addTo($email_address, $name);

    try {
        $mail->send();
        application_log("notification", "SUCCESS: Sent electives approval reminder to undergrad.");
    } catch (Zend_Mail_Transport_Exception $e) {
        application_log("notification", "FAILURE: Unable to send electives approval reminder to undergrad.");
    }
}

$mail->clearRecipients();