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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_COURSES")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif ($ENTRADA_ACL->amIAllowed('coursecontent', 'update ', false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ($COURSE_ID) {
        ?>
        <h1><?php echo $translate->_("Event Types"); ?> Report</h1>
        <?php
        $query = "SELECT d.course_name, a.event_title, c.eventtype_title, b.duration
                    FROM events as a
                    LEFT JOIN event_eventtypes as b
                    ON a.event_id = b.event_id
                    LEFT JOIN events_lu_eventtypes  AS c
                    ON b.eventtype_id = c.eventtype_id
                    LEFT JOIN courses as d
                    ON a.course_id = d.course_id
                    WHERE a.course_id = ?";
        $results = $db->GetAll($query, array($COURSE_ID));
        if ($results) {
            ?>
            <h2><?php echo $results[0]["course_name"]; ?></h2>
            <table class="table">
                <tr>
                    <th>Event</th>
                    <th><?php echo $translate->_("Event Type"); ?></th>
                    <th>Duration</th>
                </tr>
                <?php
                foreach ($results as $key => $result) {
                    ?>
                    <tr>
                        <td><?php echo html_encode($result["event_title"]); ?></td>
                        <td><?php echo html_encode($result["eventtype_title"]); ?></td>
                        <td><?php echo html_encode($result["duration"]); ?> minutes</td>
                    </tr>
                <?php
                }
                ?>
            </table>

            <p>
                Return to <?php echo "<a href=\"" . ENTRADA_URL . "/admin/courses?id=" . $COURSE_ID . "&section=content\">" . $results[0]["course_name"] . "</a>"; ?>.
            </p>
            <?php
        } else {
            add_notice("There were no learning events found in this ".strtolower($translate->_("course"))." at this time.");

            echo display_notice();
        }
    } else {
        add_error("In order to view an " . $translate->_("Event Type") . " report you must provide a valid identifier.");

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifier when attempting to view an event type report.");
    }
}
