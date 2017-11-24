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
 * Records user responses and displays responses from Entrada's Manage Polls
 * module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: serve-polls.php 1171 2010-05-01 14:39:27Z ad29 $
 * 
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
	$POLL_ID = 0;

	if((isset($_GET["poll_id"])) && ((int) trim($_GET["poll_id"]))) {
		$POLL_ID = (int) trim($_GET["poll_id"]);
	} elseif((isset($_POST["poll_id"])) && ((int) trim($_POST["poll_id"]))) {
		$POLL_ID = (int) trim($_POST["poll_id"]);
	}

	if($POLL_ID) {
		if(isset($_GET["pollSend"])) {
			if(poll_prevote_check($POLL_ID)) {
				$PROCESSED				= array();
				$PROCESSED["poll_id"]	= $POLL_ID;
				$PROCESSED["answer_id"]	= (int) trim($_POST["poll_answer_id"]);
				$PROCESSED["proxy_id"]	= (int) $ENTRADA_USER->getID();
				$PROCESSED["ip"]		= $_SERVER["REMOTE_ADDR"];
				$PROCESSED["timestamp"]	= time();

				if($db->AutoExecute("poll_results", $PROCESSED, "INSERT")) {
					application_log("success", "Successfully recorded result for poll [".$POLL_ID."]");
				} else {
					application_log("error", "Unable to store poll results.");
				}
			}
			echo poll_results($POLL_ID);
		} elseif(isset($_GET["pollGet"])) {
			echo poll_results($POLL_ID);
		}
	} else {
		echo poll_display(0);
	}
} else {
	application_log("notice", "Unauthorised access to the serve-polls.php file.");
}