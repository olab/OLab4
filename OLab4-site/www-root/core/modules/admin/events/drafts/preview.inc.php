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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
    exit;
} else if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
        header("Location: ".ENTRADA_URL);
        exit;
} else if (!$ENTRADA_ACL->amIAllowed('event', 'update', false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $draft_id = (int) $_GET["draft_id"];
    $draft = Models_Event_Draft::fetchRowByID($draft_id);

    if ($draft) {
        $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/admin/events/drafts?section=edit&draft_id=" . $draft_id, "title" => limit_chars($draft->getName(), 32));
        $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Calendar Preview"));

        $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/dhtmlxscheduler/dhtmlxscheduler.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link href=\"" . ENTRADA_RELATIVE . "/javascript/dhtmlxscheduler/dhtmlxscheduler.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" />";
        $HEAD[] = "<link href=\"" . $ENTRADA_TEMPLATE->relative() . "/css/dhtmlxscheduler.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" />";

        /**
         * Get a list of the draft events to display in the preview, and find the earliest date so the calendar will display the week containing the first event
         */
        $initial_display_date = 0;
        $json = [];
        $events = Models_Event_Draft_Event::fetchAllByDraftID($draft_id);
        if ($events) {
            foreach ($events as $event) {
                if ($initial_display_date == 0 || $event->getEventStart() < $initial_display_date) {
                    $initial_display_date = $event->getEventStart();
                }

                $json[] = [
                    "id" => $event->getID(),
                    "text" => $event->getEventTitle(),
                    "start_date" => date("Y-m-d H:i", $event->getEventStart()),
                    "end_date" => date("Y-m-d H:i", $event->getEventFinish()),
                ];
            }
        }

        if ($initial_display_date == 0) {
            $initial_display_date = time();
        }
        ?>
        <script>
            jQuery(document).ready(function () {
                scheduler.config.xml_date = '%Y-%m-%d %H:%i';
                scheduler.config.readonly = true;
                scheduler.config.details_on_dblclick = true;
                scheduler.config.first_hour = 7;
                scheduler.config.last_hour = 19;

                scheduler.ignore_week = function (date) {
                    if (date.getDay() == 6 || date.getDay() == 0) // hides Saturdays and Sundays
                        return true;
                };

                scheduler.init('draftCalendar', new Date('<?php echo date("Y-m-d H:i", $initial_display_date); ?>'), "week");

                var events = <?php echo json_encode($json); ?>;
                scheduler.parse(events, "json");
            });
        </script>

        <div id="draftCalendar" class="dhx_cal_container" style="width:100%; height:100%; min-height: 600px;">
            <div class="dhx_cal_navline">
                <div class="dhx_cal_prev_button">&nbsp;</div>
                <div class="dhx_cal_next_button">&nbsp;</div>
                <div class="dhx_cal_today_button"></div>
                <div class="dhx_cal_date"></div>
                <div class="dhx_cal_tab" name="day_tab" style="right:204px;"></div>
                <div class="dhx_cal_tab" name="week_tab" style="right:140px;"></div>
                <div class="dhx_cal_tab" name="month_tab" style="right:76px;"></div>
            </div>
            <div class="dhx_cal_header"></div>
            <div class="dhx_cal_data"></div>
        </div>

        <?php
    }
}
