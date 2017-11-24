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
 * This file is used to view the statistics (i.e. views
 * etc.) within a learning event from the entrada.statistics table.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('eventcontent', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	if ($EVENT_ID) {
		$query		= "	SELECT a.*, b.`organisation_id`
					FROM `events` AS a
					LEFT JOIN `courses` AS b
					ON b.`course_id` = a.`course_id`
					WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
		$event_info	= $db->GetRow($query);

		if ($event_info) {
			if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), "update")) {
				application_log("error", "Someone attempted to view statistics for an event [".$EVENT_ID."] that they were not the coordinator for.");

				header("Location: ".ENTRADA_URL."/admin/".$MODULE);
				exit;
			} else {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "Statistics", "id" => $EVENT_ID)), "title" => "Event Statistics");
                $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                //This will create a record set that has the proxyid, firstname, lastname, last timestamp, view per user.

                $statistics = Models_Statistic::getEventViews($EVENT_ID);

                $total_views = 0;
                
                events_subnavigation($event_info,'statistics');
				?>
                <div class="content-small"><?php echo fetch_course_path($event_info["course_id"]); ?></div>
                <h1 id="page-top" class="event-title"><?php echo html_encode($event_info["event_title"]); ?></h1>
                <h2 title="Event Statistics Section">Event Statistics</h2>
                <?php
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        var event_views_table = $("#event-views").DataTable({
                            "bPaginate": false,
                            "bInfo": false,
                            "bFilter": false,
                            'oLanguage': {
                                'sEmptyTable': 'This event has not yet been viewed.',
                                'sZeroRecords': 'No views found to display.'
                            }
                        });
                    });
                </script>
                <table class="table table-bordered table-striped" id="event-views">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Views</th>
                            <th>Last viewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($statistics) {
                            foreach ($statistics as $statistic) {
                                $total_views += $statistic["views"];
                                ?>
                            <tr>
                                <td><?php echo $statistic["lastname"] . ", " . $statistic["firstname"]; ?></td>
                                <td><?php echo $statistic["views"]; ?></td>
                                <td><?php echo date("Y-m-d H:i", $statistic["last_viewed_time"]); ?></td>
                            </tr>
                                <?php
                            }
                        } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Number of users who viewed this event:</td>
                            <td><?php echo count($statistics); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">Total event views:</td>
                            <td><?php echo $total_views; ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php
			}
		} else {
			add_error("In order to view event update history you must provide a valid event identifier. The provided ID does not exist in this system.");

			echo display_error();

			application_log("notice", "Failed to provide a valid event identifer when attempting to view event updates.");
		}
	} else {
		add_error("In order to view event update history you must provide the events identifier.");

		echo display_error();

		application_log("notice", "Failed to provide event identifer when attempting to view history of an event.");
	}
}