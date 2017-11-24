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
 * This API file returns true (1) if the provided date matches a "restricted"
 * date as set in admin->settings->manage->restricteddays
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_EVENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "create", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
    /**
     * Clears all open buffers so we can return a plain response for the Javascript.
     */
    ob_clear_open_buffers();
    
    if (isset($_GET["event_start"]) && ((int)$_GET["event_start"])) {
        $event_start = $_GET["event_start"];
        $date_string = date("Y-m-d", $event_start);
    } elseif (isset($_POST["event_start"]) && ((int)$_POST["event_start"])) {
        $event_start = $_POST["event_start"];
        $date_string = date("Y-m-d", $event_start);
    }
    
    if (isset($_GET["date_string"]) && ((int)strtotime($_GET["date_string"]))) {
        $date_string = $_GET["date_string"];
        $event_start = strtotime($date_string);
    } elseif (isset($_POST["date_string"]) && ((int)strtottime($_POST["date_string"]))) {
        $date_string = $_POST["date_string"];
        $event_start = strtotime($date_string);
    }
    
    if (isset($_GET["organisation_id"]) && ((int)$_GET["organisation_id"])) {
        $organisation_id = $_GET["organisation_id"];
    } elseif (isset($_POST["organisation_id"]) && ((int)$_POST["organisation_id"])) {
        $organisation_id = $_POST["organisation_id"];
    }
    
    if ($event_start && $date_string && $organisation_id) {
        $restricted_days = Models_RestrictedDays::fetchAll($organisation_id);

        foreach ($restricted_days as $restricted_day) {
            $restricted_string = date("Y-m-d", $restricted_day->getCalculatedDate(date("Y", $event_start), date("n", $event_start), $event_start));
            if ($restricted_string == $date_string) {
                echo "Found";
                break;
            }
        }
        if ($restricted_string != $date_string) {
            echo "Not Found";
        }
    }
    
    exit;
}