<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Reports
 * Area:		Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
 * $Id: report-by-event-types.inc.php 992 2009-12-22 16:26:26Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_RELATIVE);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => $translate->_("Learning Event Types") . " by Course");

	/**
	 * Add PlotKit to the beginning of the $HEAD array.
	 */
	array_unshift($HEAD,
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/MochiKit/MochiKit.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/excanvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Base.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Layout.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Canvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/SweetCanvas.js\"></script>"
		);

	$HEAD[]		= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
	$ONLOAD[]	= "$('courses_list').style.display = 'none'";

	/**
	 * Fetch all courses into an array that will be used.
	 */
	$query = "SELECT * FROM `courses`
			  WHERE `organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()."
			  ORDER BY `course_code` ASC";
	$courses = $db->GetAll($query);
	if ($courses) {
		foreach ($courses as $course) {
			$course_list[$course["course_id"]] = array("code" => $course["course_code"], "name" => $course["course_name"]);
		}
	}

	/**
	 * Fetch selected course_ids.
	 */
	if ((isset($_POST["course_ids"])) && (is_array($_POST["course_ids"]))) {
		$course_ids = array();

		foreach ($_POST["course_ids"] as $course_id) {
			if ($course_id = (int) $course_id) {
				$course_ids[] = $course_id;
			}
		}

		if (count($course_ids)) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = $course_ids;
		} else {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
		}
	}

	if (isset($_POST["event_title_search"]) && $_POST["event_title_search"]) {
		$event_title_search = clean_input($_POST["event_title_search"], "notags");
	}
	?>

	</style>
	<div class="no-printing">
		<h2>Reporting Dates</h2>
		<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" onsubmit="selIt()" class="form-horizontal">
		<div class="control-group">
			<table>
				<tr>
					<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
				</tr>
			</table>
		</div>
		<div class="control-group">
			<label class="form-required">Courses Included</label>
			<div class="controls">
				<?php
							echo "<select class=\"multi-picklist\" id=\"PickList\" name=\"course_ids[]\" multiple=\"multiple\" size=\"5\" style=\"width: 100%; margin-bottom: 5px\">\n";
									if ((is_array($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) && (count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"]))) {
										foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
											echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
										}
									}
							echo "</select>\n";
							echo "<div style=\"float: left; display: inline\">\n";
							echo "	<input type=\"button\" id=\"courses_list_state_btn\" class=\"btn\" value=\"Show List\" onclick=\"toggle_list('courses_list')\" />\n";
							echo "</div>\n";
							echo "<div style=\"float: right; display: inline\">\n";
							echo "	<input type=\"button\" id=\"courses_list_remove_btn\" class=\"btn btn-danger\" onclick=\"delIt()\" value=\"Remove\" />\n";
							echo "	<input type=\"button\" id=\"courses_list_add_btn\" class=\"btn btn-primary\" onclick=\"addIt()\" style=\"display: none\" value=\"Add\" />\n";
							echo "</div>\n";
							echo "<div id=\"courses_list\" style=\"clear: both; padding-top: 3px; display: none\">\n";
							echo "	<h2>Courses List</h2>\n";
							echo "	<select class=\"multi-picklist\" id=\"SelectList\" name=\"other_courses_list\" multiple=\"multiple\" size=\"15\" style=\"width: 100%\">\n";
									if ((is_array($course_list)) && (count($course_list))) {
										foreach ($course_list as $course_id => $course) {
											if (!in_array($course_id, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) {
												echo "<option value=\"".(int) $course_id."\">".html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"])."</option>\n";
											}
										}
									}
							echo "	</select>\n";
							echo "	</div>\n";
							echo "	<script type=\"text/javascript\">\n";
							echo "	\$('PickList').observe('keypress', function(event) {\n";
							echo "		if (event.keyCode == Event.KEY_DELETE) {\n";
							echo "			delIt();\n";
							echo "		}\n";
							echo "	});\n";
							echo "	\$('SelectList').observe('keypress', function(event) {\n";
							echo "	    if (event.keyCode == Event.KEY_RETURN) {\n";
							echo "			addIt();\n";
							echo "		}\n";
							echo "	});\n";
							echo "	</script>\n";
							?>
			</div>
		</div>
		<div class="control-group">
			<label for="event_title_search" class="control-label form-nrequired">Search <strong>Event Titles</strong> for</label>
			<div class="controls">
					<input type="text" value="<?php echo (isset($event_title_search) && $event_title_search ? $event_title_search : ""); ?>" name="event_title_search" id="event_title_search" style="width: 70%" />
						<div class="content-small" style="width: 70%">
								<strong>Please Note:</strong> You can leave this blank to include all events, or provide a search term (i.e. Unit 1) to include only those events in the report.
						</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="pull-right">
				<input type="submit" class="btn btn-primary" value="Create Report" />
			</div>
		</div>
		</form>
	</div>
	<?php
	if ($STEP == 2) {
		$output		= array();
		$appendix	= array();

		$courses_included	= array();
		$eventtype_legend	= array();

		echo "<h2 style=\"page-break-before: avoid\">" . $translate->_("Learning Event Types") . " by Course</h2>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).".";
		echo "</div>\n";

		$query = "	SELECT a.* FROM `events_lu_eventtypes` AS a
					LEFT JOIN `eventtype_organisation` AS c
					ON a.`eventtype_id` = c.`eventtype_id`
					LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
					ON b.`organisation_id` = c.`organisation_id`
					WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					AND a.`eventtype_active` = '1'
					ORDER BY a.`eventtype_order`
			";
		$event_types = $db->GetAll($query);
		if ($event_types) {
			foreach ($event_types as $event_type) {
				$eventtype_legend[$event_type["eventtype_id"]] = $event_type["eventtype_title"];

				foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
					$query = "	SELECT a.`event_id`, a.`recurring_id`, b.`course_name`, a.`event_title`, a.`event_start`, c.`duration`, d.`eventtype_title`
								FROM `events` AS a
								LEFT JOIN `courses` AS b
								ON b.`course_id` = a.`course_id`
								LEFT JOIN `event_eventtypes` AS c
								ON c.`event_id` = a.`event_id`
								LEFT JOIN `events_lu_eventtypes` AS d
								ON d.`eventtype_id` = c.`eventtype_id`
								WHERE c.`eventtype_id` = ".$db->qstr($event_type["eventtype_id"])."
								AND (a.`parent_id` IS NULL OR a.`parent_id` = 0)
								AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
								".(isset($event_title_search) && $event_title_search ? "AND a.`event_title` LIKE ".$db->qstr("%".$event_title_search."%") : "")."
								AND a.`course_id` = ".$db->qstr($course_id)."
								ORDER BY d.`eventtype_order` ASC, b.`course_name` ASC, a.`event_start` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						$courses_included[$course_id] = $course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"];

						foreach ($results as $result) {
                            if ($result["event_id"] == $result["recurring_id"]  || ($result["recurring_id"] == '0' || $result["recurring_id"] == NULL)) {
                                $output[$course_id]["events"][$event_type["eventtype_id"]]["duration"] += $result["duration"];
                                $output[$course_id]["events"][$event_type["eventtype_id"]]["events"] += 1;

                                $appendix[$course_id][$result["event_id"]][] = $result;
                            }
						}

						$output[$course_id]["total_duration"] += $output[$course_id]["events"][$event_type["eventtype_id"]]["duration"];
						$output[$course_id]["total_events"] += $output[$course_id]["events"][$event_type["eventtype_id"]]["events"];
					}
				}
			}
		}

		if (count($output)) {
			foreach ($output as $course_id => $result) {
				?>
				<h2><?php echo html_encode($courses_included[$course_id]); ?></h2>
				<?php
				$STATISTICS					= array();
				$STATISTICS["labels"]		= array();
				$STATISTICS["legend"]		= array();
				$STATISTICS["results"]		= array();
				?>
				<div style="text-align: center">
					<canvas id="graph_1_<?php echo $course_id; ?>" width="675" height="450"></canvas>
				</div>
				<table id="data_table_<?php echo $course_id; ?>" class="tableList" cellspacing="0" summary="<?php echo $translate->_("Event Types"); ?> of <?php echo html_encode($courses_included[$course_id]); ?>">
				<colgroup>
					<col class="modified" />
					<col class="title" />
					<col class="report-hours" style="background-color: #F3F3F3" />
					<col class="report-hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="title"><?php echo $translate->_("Event Type"); ?></td>
						<td class="report-hours large">Event Count</td>
						<td class="report-hours large">Hour Count</td>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($result["events"] as $eventtype_id => $event) {
					$STATISTICS["labels"][$eventtype_id] = $eventtype_legend[$eventtype_id];
					$STATISTICS["legend"][$eventtype_id] = $eventtype_legend[$eventtype_id];
					$STATISTICS["display"][$eventtype_id] = $event["duration"] / 60;

					if ($result["total_events"] > 0) {
						$percent_events = round((($event["events"] / $result["total_events"]) * 100));
					} else {
						$percent_events = 0;
					}

					if ($result["total_duration"] > 0) {
						$percent_duration = round((($event["duration"] / $result["total_duration"]) * 100));
					} else {
						$percent_duration = 0;
					}

					echo "<tr>\n";
					echo "	<td>&nbsp;</td>\n";
					echo "	<td>".html_encode($eventtype_legend[$eventtype_id])."</td>\n";
					echo "	<td class=\"report-hours large\" style=\"text-align: left\">".$event["events"]." (~ ".$percent_events."%)</td>\n";
					echo "	<td class=\"report-hours large\" style=\"text-align: left\">".display_hours($event["duration"])." hrs (~ ".$percent_duration."%)</td>\n";
					echo "</tr>\n";
				}
				?>
				</tbody>
				<tbody>
					<tr class="na">
						<td>&nbsp;</td>
						<td><?php echo $translate->_("Event Type"); ?> Totals</td>
						<td class="report-hours large"><?php echo $result["total_events"]; ?></td>
						<td class="report-hours large"><?php echo display_hours($result["total_duration"]); ?> hrs</td>
					</tr>
				</tbody>
				</table>
				<script type="text/javascript">
				var options = {
				   'IECanvasHTC':		'<?php echo ENTRADA_RELATIVE; ?>/javascript/plotkit/iecanvas.htc',
				   'yTickPrecision':	1,
				   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
				};

			    var layout	= new PlotKit.Layout('pie', options);
			    layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
			    layout.evaluate();

			    var canvas	= MochiKit.DOM.getElement('graph_1_<?php echo $course_id; ?>');
			    var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
			    plotter.render();

			    var canvas	= MochiKit.DOM.getElement('graph_2_<?php echo $course_id; ?>');
			    var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
			    plotter.render();
				</script>
				<?php
			}
		} else {
			echo display_notice(array("There are no learning events in the system during the timeframe you have selected."));
		}

		if (count($output)) {
			foreach ($output as $course_id => $result) {
				$total_duration = 0;
				?>
				<h1>Appendix: <?php echo html_encode($courses_included[$course_id]); ?> Data</h1>
				<?php
				if ($appendix[$course_id]) {
					?>
					<table class="tableList" cellspacing="0" summary="Appendix: <?php echo html_encode($courses_included[$course_id]); ?> Data">
					<colgroup>
						<col class="title" />
						<col class="date" />
						<col class="date" style="background-color: #F3F3F3" />
						<col class="report-hours" />
					</colgroup>
					<thead>
						<tr>
							<td class="title" style="border-left: 1px #666 solid">Event Title</td>
							<td class="date"><?php echo $translate->_("Event Type"); ?></td>
							<td class="date">Date</td>
							<td class="report-hours">Duration</td>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($appendix[$course_id] as $event_id => $segments) {
						foreach ($segments as $event) {
							$total_duration += $event["duration"];
							$hours = display_hours($event["duration"]);
							echo "<tr>\n";
							echo "	<td class=\"title\"><a href=\"".ENTRADA_URL."/events?id=".$event["event_id"]."\" target=\"_blank\">".html_encode($event["event_title"])."</a></td>\n";
							echo "	<td class=\"date\">".html_encode($event["eventtype_title"])."</td>\n";
							echo "	<td class=\"date\">".date(DEFAULT_DATE_FORMAT, $event["event_start"])."</td>\n";
							echo "	<td class=\"report-hours\">".$hours." hr".(($hours != 1) ? "s" : "")."</td>\n";
							echo "</tr>\n";
						}
					}

					echo "<tr class=\"na\" style=\"font-weight: bold\">\n";
					echo "	<td colspan=\"2\" style=\"padding-left: 10px\">Total of ".count($appendix[$course_id])." events with ".$result["total_events"]." " . $translate->_("Event Type") . " segments.</td>\n";
					echo "	<td class=\"date\" style=\"text-align: right\">Total Hours:</td>\n";
					echo "	<td class=\"report-hours\">".display_hours($total_duration)." hr".(($total_duration != 1) ? "s" : "")."</td>\n";
					echo "</tr>\n";
					?>
					</tbody>
					</table>
					<?php
				} else {
					echo display_notice(array("There are no learning events in this course during the selected duration."));
				}
			}
		}
	}
}
?>