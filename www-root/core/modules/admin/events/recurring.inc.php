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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ($EVENT_ID) {
        $query = "SELECT a.*, b.`organisation_id`
                    FROM `events` AS a
                    LEFT JOIN `courses` AS b
                    ON b.`course_id` = a.`course_id`
                    WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
        $event_info	= $db->GetRow($query);
        if ($event_info && isset($event_info["recurring_id"]) && $event_info["recurring_id"]) {
            $recurring_events = Models_Event::fetchAllRecurringByEventID($event_info["event_id"]);
            if ($recurring_events) {
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "content", "id" => $EVENT_ID)), "title" => "Event Content");
                events_subnavigation($event_info, "recurring");

                echo "<div class=\"content-small\">".fetch_course_path($event_info["course_id"])."</div>\n";
                echo "<h1 class=\"event-title\">".html_encode($event_info["event_title"])."</h1>\n";

                if ($SUCCESS) {
                    fade_element("out", "display-success-box");
                    echo display_success();
                }

                if ($NOTICE) {
                    echo display_notice();
                }

                if ($ERROR) {
                    echo display_error();
                }
                ?>
                <a name="event-attendance-section"></a>
                <h2 title="Event Resources Section">Related Recurring Events</h2>
                <div id="event-attendance-section">
                    <form name="frmSelect" action="<?php echo ENTRADA_URL; ?>/admin/events?section=delete" method="post">
                        <table class="tableList" cellspacing="0" summary="List of Recurring Events">
                            <colgroup>
                                <col class="modified"/>
                                <col class="date"/>
                                <col class="title"/>
                                <col class="attachment"/>
                            </colgroup>
                            <thead>
                                <tr>
                                    <td class="modified">&nbsp;</td>
                                    <td class="date">Date & Time</td>
                                    <td class="title">Event Title</td>
                                    <td class="attachment">&nbsp;</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($recurring_events as $recurring_event) {
                                $recurring_event = $recurring_event->toArray();

                                if ($ENTRADA_ACL->amIAllowed(new EventResource($recurring_event["event_id"], $recurring_event["course_id"], $event_info["organisation_id"]), "update")) {
                                    $administrator = true;
                                    $url = ENTRADA_URL."/admin/events?section=edit&amp;id=".$recurring_event["event_id"];
                                } else if ($ENTRADA_ACL->amIAllowed(new EventContentResource($recurring_event["event_id"], $recurring_event["course_id"], $event_info["organisation_id"]), "update")) {
                                    $url = ENTRADA_URL."/admin/events?section=content&amp;id=".$recurring_event["event_id"];
                                }
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="delete" name="checked[]" value="<?php echo $recurring_event["event_id"];?>" id="event-<?php echo $recurring_event["event_id"];?>" /></td>
                                    <?php
                                    echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Date\">" : "").($EVENT_ID == $recurring_event["event_id"] ? "<strong>" : "").date(DEFAULT_DATE_FORMAT, $recurring_event["event_start"]).($EVENT_ID == $recurring_event["event_id"] ? "</strong>" : "").(($url) ? "</a>" : "")."</td>\n";
                                    echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Title: ".html_encode($recurring_event["event_title"])."\">" : "").($EVENT_ID == $recurring_event["event_id"] ? "<strong>" : "").html_encode($recurring_event["event_title"]).($EVENT_ID == $recurring_event["event_id"] ? "</strong>" : "").(($url) ? "</a>" : "")."</td>\n";
                                    echo "  <td class=\"attachment".((!$url) ? " np" : "")."\">";
                                    if ($url) {
                                        echo "  <div class=\"btn-group\">\n";
                                        echo "      <button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
                                        echo "          <i class=\"fa fa-cog\" aria-hidden=\"true\"></i>\n";
                                        echo "      </button>";
                                        echo "      <ul class=\"dropdown-menu toggle-left\">\n";
                                        if ($ENTRADA_ACL->amIAllowed(new EventResource($recurring_event["event_id"], $recurring_event["course_id"], $event_info["organisation_id"]), 'update')) {
                                            echo "      <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=edit&amp;id=".$recurring_event["event_id"]."\">" . $translate->_("Event Setup") . "</a></li>";
                                        }
                                        echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=content&amp;id=".$recurring_event["event_id"]."\">Event Content</a></li>";
                                        echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=attendance&amp;id=".$recurring_event["event_id"]."\">Event Attendance</a></li>";
                                        echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=history&amp;id=".$recurring_event["event_id"]."\">Event History</a></li>";
                                        echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=statistics&amp;id=".$recurring_event["event_id"]."\">Event Statistics</a></li>";
                                        echo "      </ul>\n";
                                        echo "  </div>\n";
                                    } else {
                                        echo "&nbsp;";
                                    }
                                    echo "  </td>\n";
                                    ?>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                        <div style="margin-top:10px">
                            <?php
                            if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
                                ?>
                                <input type="submit" class="btn btn-danger pull-right" value="Delete Selected" />
                                <?php
                            }
                            ?>
                        </div>
                    </form>
                </div>
                <?php
            } else {
                echo display_notice("No recurring events associated with this event [<strong>".html_encode($event_info["event_title"])."</strong>] were found in the system.");
            }
        } else {
            add_error("In order to view the related recurring events for an event, you must provide a valid event identifier. The provided ID does not exist in this system.");

            echo display_error();

            application_log("notice", "Failed to provide a valid event identifer when attempting to edit a event.");
        }
    } else {
        add_error("In order to view the related recurring events for an event, you must provide the events identifier.");

        echo display_error();

        application_log("notice", "Failed to provide event identifer when attempting to view related recurring events for an event.");
    }
}
