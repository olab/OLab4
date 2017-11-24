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
 * Api to search learning event child by title.
 *
 * @author Organization: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
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

    if (isset($_POST["related_event_title"]) && ($tmp_input = clean_input($_POST["related_event_title"], array("trim", "striptags")))) {
        $event_title = $tmp_input;
    } else {
        $event_title = "";
    }

    if (isset($_GET["course_id"]) && ($tmp_input = clean_input($_GET["course_id"], array("trim", "int")))) {
        $course_id = $tmp_input;
    } else {
        $course_id = 0;
    }

    if (isset($_GET["parent_id"]) && ($tmp_input = clean_input($_GET["parent_id"], array("trim", "int")))) {
        $parent_id = $tmp_input;
    } else {
        $parent_id = 0;
    }

    if ($course_id && $event_title) {
        echo "<ul>\n";

        $events = Models_Event::fetchAllByCourseIdTitle($course_id, $event_title);
        if ($events) {
            foreach($events as $event) {
                if ($parent_id != $event->getParentID()) {
                    echo "<li id=\"" . (int) $event->getID() . "\">";
                    echo html_encode($event->getEventTitle());
                    echo "  <div class=\"informal\"><small>" . date(DEFAULT_DATE_FORMAT, $event->getEventStart()) . " / ID: " . (int) $event->getID() . "</small></div>";
                    echo "</li>";

                }
            }
        } else {
            echo "<li id=\"0\"><span class=\"informal\">" . $translate->_("Unable to find any matching learning events.") . "</span></li>";
        }
        echo "</ul>";
    }
    
    exit;
}