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
 *
 * $Id: discussions.inc.php 1171 2010-05-01 14:39:27Z ad29 $
 */

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('discussion', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$PROCESSED		= array();
	$EVENT_ID		= 0;
	$RESULT_ID		= 0;
	$USE_QUERY		= false;

	$DISCUSSION_ID		= 0;
	$DISCUSSION_COMMENT	= "";


	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$DISCUSSION_ID	= (int) trim($_GET["id"]);
	}

	if((isset($_GET["event_id"])) && ((int) trim($_GET["event_id"]))) {
		$EVENT_ID = (int) trim($_GET["event_id"]);
	} elseif((isset($_GET["rid"])) && ((int) trim($_GET["rid"]))) {
		$EVENT_ID = (int) trim($_POST["event_id"]);
	} elseif((isset($_GET["drid"])) && ((int) trim($_GET["drid"]))) {
		$EVENT_ID = (int) trim($_POST["event_id"]);
	} elseif((isset($_POST["event_id"])) && ((int) trim($_POST["event_id"]))) {
		$EVENT_ID = (int) trim($_POST["event_id"]);
	}

	switch($ACTION) {
		case "add" :
			if($EVENT_ID) {
				if($DISCUSSION_COMMENT = clean_input($_POST["discussion_comment"], array("notags", "trim"))) {
					$query	= "SELECT * FROM `events` WHERE `event_id` = ".$db->qstr($EVENT_ID);
					$result	= $db->GetRow($query);
					if($result) {
						$PROCESSED["event_id"]				= $EVENT_ID;
						$PROCESSED["proxy_id"]				= $ENTRADA_USER->getID();
						$PROCESSED["parent_id"]				= 0;
						$PROCESSED["discussion_title"]		= "RE: ".$result["event_title"];
						$PROCESSED["discussion_comment"]	= $DISCUSSION_COMMENT;
						$PROCESSED["discussion_active"]		= 1;
						$PROCESSED["updated_date"]			= time();
						$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();

						if(!$db->AutoExecute("event_discussions", $PROCESSED, "INSERT")) {
							application_log("error", "Unable to add discussion comment to event id [".$EVENT_ID."]");
						} elseif (($EDISCUSSION_ID = $db->Insert_Id()) && defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
							require_once("Classes/notifications/NotificationUser.class.php");
							NotificationUser::addAllNotifications("event_discussion", $EVENT_ID, 0, $ENTRADA_USER->getID(), $EDISCUSSION_ID);
						}
					}
				}
				header("Location: ".ENTRADA_URL."/events?".(($USE_QUERY) ? ((isset($_GET["drid"])) ? "drid" : "rid")."=".$RESULT_ID : "id=".$EVENT_ID)."#event-comments-section");
				exit;
			} else {
				header("Location: ".ENTRADA_URL."/events");
				exit;
			}
			break;
		default :
			continue;
			break;
	}
}
?>