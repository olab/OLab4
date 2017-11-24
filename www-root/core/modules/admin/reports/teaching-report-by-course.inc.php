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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Add PlotKit to the beginning of the $HEAD array.
	 */
	array_unshift($HEAD,
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/MochiKit/MochiKit.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/excanvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Base.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Layout.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Canvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/SweetCanvas.js\"></script>"
		);
			
	$BREADCRUMB[]	= array("url" => "", "title" => "Teaching Report By Course" );
	?>
	<style type="text/css">
	h1 {
		page-break-before:	always;
		border-bottom:		2px #CCCCCC solid;
		font-size:			24px;
	}
	
	h2 {
		font-weight:		normal;
		border:				0px;
		font-size:			18px;
	}
	
	div.top-link {
		float: right;
	}
	</style>
	<a name="top"></a>
	<div class="no-printing">
		<form action="<?php echo ENTRADA_URL; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
		<input type="hidden" name="update" value="1" />
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
		<colgroup>
			<col style="width: 3%" />
			<col style="width: 20%" />
			<col style="width: 77%" />
		</colgroup>
		<tbody>
			<tr>
				<td colspan="3"><h2>Report Options</h2></td>
			</tr>
			<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
			<tr>
				<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
			</tr>
		</tbody>
		</table>
		</form>
	</div>
	<?php
	if($STEP == 2) {
	$event_types_graphed	= 3;
	$int_use_cache			= false;
	$event_ids				= array();
	$report_results			= array();
	$no_staff_number		= array();
	$course_sidebar			= array();	
	$default_na_name		= "Unknown or N/A";
	$report_results			= array();
	
	$organisation_where = "AND (`courses`.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation().") ";

	$query = "	SELECT * FROM `courses` 
				WHERE `course_active` = '1' 
				".$organisation_where." 
				ORDER BY `course_name` ASC";

	if($int_use_cache) {
		$courses = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
	} else {
		$courses = $db->GetAll($query);
	}
	
	if($courses) {
		foreach($courses as $course) {
			
			$course_sidebar[$course["course_id"]]	= array("course_name" => $course["course_name"], "course_link" => clean_input($course["course_name"], "credentials"));

			$report_results[$course["course_id"]]	= array();
			
			$query	= "	SELECT a.*, b.`audience_type`, b.`audience_value`
						FROM `events` AS a
						LEFT JOIN `event_audience` AS b
						ON a.`event_id` = b.`event_id`
						WHERE a.`course_id` = ".$db->qstr($course["course_id"])."
						AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")";
			if($int_use_cache) {
				$events	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
			} else {
				$events	= $db->GetAll($query);
			}
			if($events) {
				$report_results[$course["course_id"]]["No Assigned Teacher"] = 0;
				foreach($events as $event) {
					$squery = "	SELECT CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`
								FROM `event_contacts` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
								AND a.`contact_role` = 'teacher' 
								ORDER BY `fullname` ASC";
					if($int_use_cache) {
						$sresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $squery);
					} else {
						$sresults	= $db->GetAll($squery);
					}
					
					if($sresults) {
						foreach($sresults as $sresult) {
							$report_results[$course["course_id"]][$sresult["fullname"]] += $event["event_duration"];
						}
					} else {
						$report_results[$course["course_id"]]["No Assigned Teacher"] += $event["event_duration"];						
					}
				}
			}
		}
	}
	
	/**
	 * Check for Learning Events that are not in a course.
	 */
	$query	= "	SELECT a.*, b.`audience_type`, b.`audience_value`
				FROM `events` AS a
				LEFT JOIN `event_audience` AS b
				ON a.`event_id` = b.`event_id`
				WHERE a.`course_id` = '0'
				AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." 
				AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
				AND (b.`audience_type` = 'organisation' AND b.`audience_value` = ".$ENTRADA_USER->getActiveOrganisation().")";

	
	if($int_use_cache) {
		$events	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
	} else {
		$events	= $db->GetAll($query);
	}
	
	if($events) {
		foreach($events as $event) {
			$squery = "	SELECT CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`
						FROM `event_contacts` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
						ORDER BY `fullname` ASC";
			if($int_use_cache) {
				$sresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $squery);
			} else {
				$sresults	= $db->GetAll($squery);
			}
			
			if($sresults) {
				foreach($sresults as $sresult) {
					$report_results[0][$sresult["fullname"]] += $event["event_duration"];
				}
			} else {
				$report_results[0]["No Assigned Teacher"] += $event["event_duration"];						
			}
		}
	}
	
	echo "<h1 style=\"page-break-before: avoid\">Teaching Report By Course (hourly)</h1>";
	echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
	echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);
	echo "</div>";

	echo "<div id=\"table-of-contents\" class=\"printing-enabled\">\n";
	echo "	<h1>Table of Contents</h1>\n";
	echo "	<div style=\"margin-left: 15px\">\n";
	echo "		<ol>\n";
	foreach($course_sidebar as $result) {
		echo "		<li><a href=\"#".$result["course_link"]."\" title=\"".html_encode($result["course_name"])."\">".html_encode($result["course_name"])."</a></li>\n";
	}
	echo "		</ol>";
	echo "	</div>\n";
	echo "</div>\n";

	if(count($report_results)) {
		$absolute_final_total = 0;
			
		foreach($report_results as $course_id => $course_teachers) {
			$OTHER_DIRECTORS	= array();

			$sub_query		= "SELECT `proxy_id` FROM `course_contacts` WHERE `course_contacts`.`course_id` = ".$db->qstr($course_id)." AND `course_contacts`.`contact_type` = 'director' ORDER BY `contact_order` ASC";
			$sub_results	= $db->GetAll($sub_query);
			if($sub_results) {
				foreach($sub_results as $sub_result) {
					$OTHER_DIRECTORS[] = $sub_result["proxy_id"];
				}
			}
						
			if((int) $course_id) {
				$course_name	= (($tmp_course_name = fetch_course_title($course_id)) ? $tmp_course_name : "Unknown Course Name");
			} else {
				$course_name	= "Learning Events With No Assigned Course";
			}
			
			$course_final_total	= 0;

			echo "<a name=\"".$course_sidebar[$course_id]["course_link"]."\"></a>\n";
			echo "<h1>".html_encode($course_name)."</h1>";
			echo "<div style=\"margin-left: 0px\">\n";

			$query	= "	SELECT * FROM `courses` 
						WHERE `course_id` = ".$db->qstr($course_id)."
						AND `course_active` = '1'";
			$result	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
			
			if ($result) {
				echo "<h2>Course Details</h2>\n";
				echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" summary=\"Detailed Course Information\">\n";
				echo "<colgroup>\n";
				echo "	<col style=\"width: 22%\" />\n";
				echo "	<col style=\"width: 78%\" />\n";
				echo "</colgroup>\n";
				echo "<tr>\n";
				echo "	<td style=\"vertical-align: top\">Course Directors</td>\n";
				echo "	<td>\n";
							$squery		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
											FROM `course_contacts` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON b.`id` = a.`proxy_id`
											WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
											AND a.`contact_type` = 'director'
											AND b.`id` IS NOT NULL
											ORDER BY a.`contact_order` ASC";
							$sresults	= $db->GetAll($squery);
							if($sresults) {
								foreach($sresults as $key => $sresult) {
									echo "<a href=\"mailto:".html_encode($sresult["email"])."\">".html_encode($sresult["fullname"])."</a><br />\n";
								}
							} else {
								echo "To Be Announced";
							}
				echo "	</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "	<td style=\"vertical-align: top\">Curriculum Coordinators</td>\n";
				echo "	<td>\n";
							$squery		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
											FROM `course_contacts` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON b.`id` = a.`proxy_id`
											WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
											AND a.`contact_type` = 'ccoordinator'
											AND b.`id` IS NOT NULL
											ORDER BY a.`contact_order` ASC";
							$sresults	= $db->GetAll($squery);
							if($sresults) {
								foreach($sresults as $key => $sresult) {
									echo "<a href=\"mailto:".html_encode($sresult["email"])."\">".html_encode($sresult["fullname"])."</a><br />\n";
								}
							} else {
								echo "To Be Announced";
							}
				echo "	</td>\n";
				echo "</tr>\n";
	            echo "<tr>\n";
	            echo "    <td colspan=\"2\">&nbsp;</td>\n";
	            echo "</tr>\n";
	            echo "<tr>\n";
	            echo "    <td>Program Coordinator:</td>\n";
	            echo "    <td>".(($result["pcoord_id"]) ? "<a href=\"mailto:".get_account_data("email", $result["pcoord_id"])."\">".get_account_data("fullname", $result["pcoord_id"])."</a>" : "To Be Announced")."</td>\n";
	            echo "</tr>\n";
	            echo "<tr>\n";
	            echo "    <td>Evaluation Rep:</td>\n";
	            echo "    <td>".(($result["evalrep_id"]) ? "<a href=\"mailto:".get_account_data("email", $result["evalrep_id"])."\">".get_account_data("fullname", $result["evalrep_id"])."</a>" : "To Be Announced")."</td>\n";
	            echo "</tr>\n";
	            echo "<tr>\n";
	            echo "    <td>Student Rep:</td>\n";
	            echo "    <td>".(($result["studrep_id"]) ? "<a href=\"mailto:".get_account_data("email", $result["studrep_id"])."\">".get_account_data("fullname", $result["studrep_id"])."</a>" : "To Be Announced")."</td>\n";
	            echo "</tr>\n";
	
				if(clean_input($result["course_url"], array("notags", "nows")) != "") {
					echo "<tr>\n";
					echo "	<td colspan=\"2\"><h2>Course Website</h2></td>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "	<td colspan=\"2\">\n";
					echo "		This course has an external course website that will possibly list the objectives and other information for the students.";
					echo "		<br /><br />\n";
					echo "		<a href=\"".$result["course_url"]."\" target=\"blank\">".$result["course_url"]."</a>\n";
					echo "	</td>\n";
					echo "</tr>\n";
				} else {
					echo "<tr>\n";
					echo "	<td colspan=\"2\">&nbsp;</td>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "	<td colspan=\"2\"><h2>Course Description</h2></td>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "	<td colspan=\"2\" style=\"text-align: justify\">\n";
								if(clean_input($result["course_description"], array("notags", "nows")) != "") {
									echo trim(strip_selected_tags($result["course_description"], array("font")))."\n";
								} else {
									echo "No course aim or goals have been provided.";
								}
					echo "	</td>\n";
					echo "</tr>\n";
					
					echo "<tr>\n";
					echo "	<td colspan=\"2\">&nbsp;</td>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "	<td colspan=\"2\"><h2>" . $translate->_("Course Objectives") . "</h2></td>\n";
					echo "</tr>\n";
					echo "<tr>\n";
					echo "	<td colspan=\"2\" style=\"text-align: justify\">\n";
								if(clean_input($result["course_objectives"], array("notags", "nows")) != "") {
									echo trim(strip_selected_tags($result["course_objectives"], array("font")))."\n";
								} else {
									echo "No " . $translate->_("Course Objectives") . " have been provided.";
								}
					echo "	</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";
			}
			?>
				<h2>Course <?php echo $translate->_("Event Types"); ?></h2>
				<table style="width: 100%" cellspacing="2" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 33%" />
					<col style="width: 34%" />
					<col style="width: 33%" />
				</colgroup>
				<tbody>
					<tr>
						<td style="vertical-align: top; text-align: center">
							<?php
							$graph_1_start	= strtotime("-2 years", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"]);
							$graph_1_end	= strtotime("+1 year", ($graph_1_start - 1));
							
							echo "<strong>".date("Y-m-d", $graph_1_start)." - ".date("Y-m-d", $graph_1_end)."</strong>\n";

							$STATISTICS					= array();
							$STATISTICS["labels"]		= array();
							
							$STATISTICS["legend"]		= array();
							$STATISTICS["legend"][0]	= "Other";
						
							$STATISTICS["results"]		= array();
							
							$counted_events				= 0;
							$total_events				= 0;
							
							/**
							 * Get the total number of events during this time period.
							 */
							$query		= "
										SELECT COUNT(*) AS `total`
										FROM `events` AS a
										AND a.`course_id` = ".$db->qstr($course_id)."
										AND (a.`event_start` BETWEEN ".$db->qstr($graph_1_start)." AND ".$db->qstr($graph_1_end).")";
							$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
							if($sresult) {
								$total_events = $sresult["total"];
							}
							
							/**
							 * Select all of the event types in the system.
							 */
							$query		= "	SELECT a.* FROM `events_lu_eventtypes` AS a 
											LEFT JOIN `eventtype_organisation` AS c 
											ON a.`eventtype_id` = c.`eventtype_id` 
											LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
											ON b.`organisation_id` = c.`organisation_id` 
											WHERE b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()."
											AND a.`eventtype_active` = '1' 
											ORDER BY a.`eventtype_order`
								";
							$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
							if($results) {
								foreach($results as $result) {
									/**
									 * Check how many events are linked to this event type during this time period.
									 */
									$query		= "
												SELECT COUNT(*) AS `total`
												FROM `events` AS a
												WHERE a.`course_id` = ".$db->qstr($course_id)."
												AND a.`eventtype_id` = ".$db->qstr($result["eventtype_id"])."
												AND (a.`event_start` BETWEEN ".$db->qstr($graph_1_start)." AND ".$db->qstr($graph_1_end).")";
									$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT,$query);
									if($sresult) {
										$STATISTICS["results"][$result["eventtype_id"]] = $sresult["total"];
						
										$counted_events += $sresult["total"];
									} else {
										$STATISTICS["results"][$result["eventtype_id"]] = 0;
									}
									
									$STATISTICS["labels"][$result["eventtype_id"]] = $result["eventtype_title"]." (".(int) $STATISTICS["results"][$result["eventtype_id"]].")";
									$STATISTICS["legend"][$result["eventtype_id"]] = $result["eventtype_title"];
								}
								
								arsort($STATISTICS["results"]);
								
								$STATISTICS["display"]	= array();
								
								$i = 0;
								foreach($STATISTICS["results"] as $key => $result) {
									if($i > $event_types_graphed) {
										$STATISTICS["display"][0]	+= (int) $result;
									} else {
										$STATISTICS["display"][$key] = $result;
									}
									$i++;
								}
							}
							?>
							<div>
								<canvas id="graph_1_<?php echo $course_id; ?>" width="240" height="240"></canvas>
							</div>
							<script type="text/javascript">
							var options = {
							   'IECanvasHTC':		'<?php echo ENTRADA_URL; ?>/javascript/plotkit/iecanvas.htc',
							   'yTickPrecision':	1,
							   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
							};
							
						    var layout	= new PlotKit.Layout('pie', options);
						    layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
						    layout.evaluate();
						    var canvas	= MochiKit.DOM.getElement('graph_1_<?php echo $course_id; ?>');
						    var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
						    plotter.render();
							</script>
						</td>
						<td style="vertical-align: top; text-align: center">
							<?php
							$graph_2_start	= strtotime("-1 years", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"]);
							$graph_2_end	= strtotime("+1 year", ($graph_2_start - 1));
							
							echo "<strong>".date("Y-m-d", $graph_2_start)." - ".date("Y-m-d", $graph_2_end)."</strong>\n";

							$STATISTICS					= array();
							$STATISTICS["labels"]		= array();
							
							$STATISTICS["legend"]		= array();
							$STATISTICS["legend"][0]	= "Other";
						
							$STATISTICS["results"]		= array();
							
							$counted_events				= 0;
							$total_events				= 0;
							
							/**
							 * Get the total number of events during this time period.
							 */
							$query		= "
										SELECT COUNT(*) AS `total`
										FROM `events` AS a
										AND a.`course_id` = ".$db->qstr($course_id)."
										AND (a.`event_start` BETWEEN ".$db->qstr($graph_2_start)." AND ".$db->qstr($graph_2_end).")";
							$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
							if($sresult) {
								$total_events = $sresult["total"];
							}
							
							/**
							 * Select all of the event types in the system.
							 */
							$query		= "	SELECT a.* FROM `events_lu_eventtypes` AS a 
											LEFT JOIN `eventtype_organisation` AS c 
											ON a.`eventtype_id` = c.`eventtype_id` 
											LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
											ON b.`organisation_id` = c.`organisation_id` 
											WHERE b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()."
											AND a.`eventtype_active` = '1' 
											ORDER BY a.`eventtype_order`
								";
							$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
							if($results) {
								foreach($results as $result) {
									/**
									 * Check how many events are linked to this event type during this time period.
									 */
									$query		= "
												SELECT COUNT(*) AS `total`
												FROM `events` AS a
												WHERE a.`course_id` = ".$db->qstr($course_id)."
												AND a.`eventtype_id` = ".$db->qstr($result["eventtype_id"])."
												AND (a.`event_start` BETWEEN ".$db->qstr($graph_2_start)." AND ".$db->qstr($graph_2_end).")";
									$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
									if($sresult) {
										$STATISTICS["results"][$result["eventtype_id"]] = $sresult["total"];
						
										$counted_events += $sresult["total"];
									} else {
										$STATISTICS["results"][$result["eventtype_id"]] = 0;
									}
									
									$STATISTICS["labels"][$result["eventtype_id"]] = $result["eventtype_title"]." (".(int) $STATISTICS["results"][$result["eventtype_id"]].")";
									$STATISTICS["legend"][$result["eventtype_id"]] = $result["eventtype_title"];
								}
								
								arsort($STATISTICS["results"]);
								
								$STATISTICS["display"]	= array();
								
								$i = 0;
								foreach($STATISTICS["results"] as $key => $result) {
									if($i > $event_types_graphed) {
										$STATISTICS["display"][0]	+= (int) $result;
									} else {
										$STATISTICS["display"][$key] = $result;
									}
									$i++;
								}
							}
							?>
							<div>
								<canvas id="graph_2_<?php echo $course_id; ?>" width="240" height="240"></canvas>
							</div>
							<script type="text/javascript">
							var options = {
							   'IECanvasHTC':		'<?php echo ENTRADA_URL; ?>/javascript/plotkit/iecanvas.htc',
							   'yTickPrecision':	1,
							   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
							};
							
						    var layout	= new PlotKit.Layout('pie', options);
						    layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
						    layout.evaluate();
						    var canvas	= MochiKit.DOM.getElement('graph_2_<?php echo $course_id; ?>');
						    var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
						    plotter.render();
							</script>
						</td>
						<td style="vertical-align: top; text-align: center">
							<?php
							$graph_3_start	= $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"];
							$graph_3_end	= strtotime("+1 year", ($graph_3_start - 1));
							
							echo "<strong>".date("Y-m-d", $graph_3_start)." - ".date("Y-m-d", $graph_3_end)."</strong>\n";

							$STATISTICS					= array();
							$STATISTICS["labels"]		= array();
							
							$STATISTICS["legend"]		= array();
							$STATISTICS["legend"][0]	= "Other";
						
							$STATISTICS["results"]		= array();
							
							$counted_events				= 0;
							$total_events				= 0;
							
							/**
							 * Get the total number of events during this time period.
							 */
							$query		= "
										SELECT COUNT(*) AS `total`
										FROM `events` AS a
										AND a.`course_id` = ".$db->qstr($course_id)."
										AND (a.`event_start` BETWEEN ".$db->qstr($graph_3_start)." AND ".$db->qstr($graph_3_end).")";
							$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
							if($sresult) {
								$total_events = $sresult["total"];
							}
							
							/**
							 * Select all of the event types in the system.
							 */
							$query		= "	SELECT a.* FROM `events_lu_eventtypes` AS a 
											LEFT JOIN `eventtype_organisation` AS c 
											ON a.`eventtype_id` = c.`eventtype_id` 
											LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
											ON b.`organisation_id` = c.`organisation_id` 
											WHERE b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()."
											AND a.`eventtype_active` = '1' 
											ORDER BY a.`eventtype_order`
								";
							$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
							if($results) {
								foreach($results as $result) {
									/**
									 * Check how many events are linked to this event type during this time period.
									 */
									$query		= "
												SELECT COUNT(*) AS `total`
												FROM `events` AS a
												WHERE a.`course_id` = ".$db->qstr($course_id)."
												AND a.`eventtype_id` = ".$db->qstr($result["eventtype_id"])."
												AND (a.`event_start` BETWEEN ".$db->qstr($graph_3_start)." AND ".$db->qstr($graph_3_end).")";
									$sresult	= $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
									if($sresult) {
										$STATISTICS["results"][$result["eventtype_id"]] = $sresult["total"];
						
										$counted_events += $sresult["total"];
									} else {
										$STATISTICS["results"][$result["eventtype_id"]] = 0;
									}
									
									$STATISTICS["labels"][$result["eventtype_id"]] = $result["eventtype_title"]." (".(int) $STATISTICS["results"][$result["eventtype_id"]].")";
									$STATISTICS["legend"][$result["eventtype_id"]] = $result["eventtype_title"];
								}
								
								arsort($STATISTICS["results"]);
								
								$STATISTICS["display"]	= array();
								
								$i = 0;
								foreach($STATISTICS["results"] as $key => $result) {
									if($i > $event_types_graphed) {
										$STATISTICS["display"][0]	+= (int) $result;
									} else {
										$STATISTICS["display"][$key] = $result;
									}
									$i++;
								}
							}
							?>
							<div>
								<canvas id="graph_3_<?php echo $course_id; ?>" width="240" height="240"></canvas>
							</div>
							<script type="text/javascript">
							var options = {
							   'IECanvasHTC':		'<?php echo ENTRADA_URL; ?>/javascript/plotkit/iecanvas.htc',
							   'yTickPrecision':	1,
							   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
							};
							
						    var layout	= new PlotKit.Layout('pie', options);
						    layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
						    layout.evaluate();
						    var canvas	= MochiKit.DOM.getElement('graph_3_<?php echo $course_id; ?>');
						    var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
						    plotter.render();
							</script>
						</td>
					</tr>
				</tbody>
				</table>
				
				<h2>Course Teaching</h2>
				<table class="tableList" cellspacing="0" summary="Summary Report For <?php echo html_encode($course_name); ?>">
				<colgroup>
					<col class="modified" style="width: 10%"/>
					<col class="general" style="width: 90%" />
					<col class="report-hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified" style="width: 100%" colspan="2">Teacher Name</td>
						<td class="report-hours">Total Hours</td>
					</tr>
				</thead>
				<tbody>
				<?php
				if((is_array($course_teachers)) && (count($course_teachers))) {
					foreach($course_teachers as $teacher_name => $teaching_minutes) {
						$course_final_total += $teaching_minutes;
								
						?>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="general"><?php echo html_encode($teacher_name); ?></td>
							<td class="report-hours"><?php echo display_hours($teaching_minutes); ?></td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr class="na" style="font-weight: bold">
						<td class="modified">&nbsp;</td>
						<td class="general"><?php echo html_encode($course_name); ?> Totals:</td>
						<td class="report-hours"><?php echo (($course_final_total) ? display_hours($course_final_total) : ""); ?></td>
					</tr>
					<?php
					$absolute_final_total += $course_final_total;
				} else {
					?>
					<tr>
						<td colspan="3" style="padding: 15px">
							<div class="display-notice">
								There are no teaching hours recorded for this course during this duration.
							</div>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
				</table>
			</div>
			<?php
		}
	}
	?>
	<table class="tableList" cellspacing="0" summary="Total Report Summary">
	<colgroup>
		<col class="modified" style="width: 10%"/>
		<col class="general" style="width: 90%" />
		<col class="report-hours" />
	</colgroup>
	<thead>
		<tr>
			<td class="modified" style="width: 100%" colspan="2">&nbsp;</td>
			<td class="report-hours">Total Hours</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="7">&nbsp;</td>
		</tr>
		<tr style="background-color: #DEE6E3; font-weight: bold">
			<td class="modified">&nbsp;</td>
			<td class="general">Final Totals:</td>
			<td class="report-hours"><?php echo (($absolute_final_total) ? display_hours($absolute_final_total) : ""); ?></td>
		</tr>
	</tbody>
	</table>
	<?php
	$sidebar_html  = "<ul class=\"menu\">\n";
	foreach($course_sidebar as $result) {
		$sidebar_html .= "	<li class=\"link\"><a href=\"#".$result["course_link"]."\" title=\"".html_encode($result["course_name"])."\">".html_encode($result["course_name"])."</a></li>\n";
	}
	$sidebar_html .= "</ul>";
	new_sidebar_item("Course List", $sidebar_html, "department-list", "open");
	}
}
?>
