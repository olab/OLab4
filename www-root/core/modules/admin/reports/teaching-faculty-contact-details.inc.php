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

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Teaching Faculty Contact Details");
	
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
		
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = $course_ids;
	}
	
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
	}
	
	if (isset($_POST["minimum_events"]) && (int) $_POST["minimum_events"]) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["minimum_events"] = clean_input($_POST["minimum_events"], "int");
	}
	
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["minimum_events"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["minimum_events"] = 1;
	}
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
	<div class="no-printing">
		<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" onsubmit="selIt()">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 20%" />
					<col style="width: 77%" />
				</colgroup>
				<tbody>
					<tr>
						<td colspan="3"><h2>Reporting Dates</h2></td>
					</tr>
					<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top;"><label class="form-required">Courses Included</label></td>
						<td style="vertical-align: top;">
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
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td style="vertical-align: top; padding-top: 6px"><label for="event_title_search" class="form-nrequired"><strong>Minimum </strong> Events</label></td>
						<td style="vertical-align: top;">
							<input type="text" value="<?php echo (int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["minimum_events"]; ?>" name="minimum_events" id="minimum_events" style="width: 30px" maxlength="3" />
							<span class="content-small">
								<strong>Example:</strong> If you specify <strong>4</strong>, you will only see teachers who have taught more than <strong>4</strong> sessions.
							</span>
						</td>
					</tr>

					<tr>
						<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
	<?php
	if ($STEP == 2 && !empty($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"])) {
		$output	= array();
		$appendix	= array();
		
		$courses_included	= array();
		$eventtype_legend	= array();
		
		echo "<h1>Teaching Faculty Contact Details</h1>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).".";
		echo "</div>\n";

		$organisation_where = " AND (b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation().") ";
		
		foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
			echo "<h2>".$course_list[$course_id]["name"]."</h2>";
			
			$query		= "	SELECT d.`firstname`, d.`lastname`, d.`email`, COUNT(*) AS `total`, ROUND(SUM((a.`event_finish` - a.`event_start`) / 3600)) as `total_hours`
							FROM `events` AS a
							JOIN `courses` AS b
							ON b.`course_id` = a.`course_id`
							JOIN `event_contacts` AS c
							ON a.`event_id` = c.`event_id`
							JOIN `".AUTH_DATABASE."`.`user_data` AS d
							ON d.`id` = c.`proxy_id`
							WHERE a.`course_id` = ".$db->qstr($course_id)."
							AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
							AND c.`contact_role` = 'teacher' 
							AND b.`course_active` = '1'
							GROUP BY c.`proxy_id`
							HAVING COUNT(*) >= ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["minimum_events"])."
							ORDER BY d.`lastname` ASC, d.`firstname` ASC";
			$results	= $db->GetAll($query);
			if ($results) {
				?>
				<table class="tableList" cellspacing="0" summary="Teaching Faculty Contact Details">
				<colgroup>
					<col class="general" />
					<col class="general" />
					<col class="title" />
					<col class="general" />
					<col class="general" />
				</colgroup>
				<thead>
					<tr>
						<td class="general" style="border-left: 1px #666 solid">Firstname</td>
						<td class="general">Lastname</td>
						<td class="title">E-Mail Address</td>
						<td class="general">Event Hours</td>
						<td class="general">Events Taught</td>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($results as $result) {
					if ($result["email"] != "") {
						echo "<tr>\n";
						echo "	<td class=\"general\">".$result["firstname"]."</td>\n";
						echo "	<td class=\"general\">".$result["lastname"]."</td>\n";
						echo "	<td class=\"title\">".$result["email"]."</td>\n";
						echo "	<td class=\"general\">".$result["total_hours"]."</td>\n";
						echo "	<td class=\"general\">".$result["total"]."</td>\n";
						echo "</tr>\n";
					}
				}
				?>
				</tbody>
				</table>
				<?php				
			} else {
				echo display_notice(array("There are no teachers who taught in these courses during the timeframe you have selected."));
			}
		}
	}
}