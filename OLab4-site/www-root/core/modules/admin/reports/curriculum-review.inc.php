<?php
/**
 * One-Off Report
 *
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
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
	$BREADCRUMB[]	= array("url" => "", "title" => "Curriculum Review Report");
	
	$HEAD[]		= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
	$ONLOAD[]	= "$('courses_list').style.display = 'none'";

	$organisation_id_changed = false;
	
	/**
	 * Fetch the organisation_id that has been selected.
	 */
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = -1;
	} elseif ((isset($_GET["org_id"])) && ($tmp_input = clean_input($_GET["org_id"], "int"))) {
		$organisation_id_changed = true;
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = $tmp_input;
	} else {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = (int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"];
	}
		
	/**
	 * Fetch all courses into an array that will be used.
	 */
	$query = "SELECT * FROM `courses`".(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] > 0) ? " WHERE `organisation_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]) : "")." ORDER BY `course_code` ASC";
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
	} elseif (($organisation_id_changed) || (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"]))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
	}
	
	if (isset($_POST["event_title_search"]) && $_POST["event_title_search"]) {
		$event_title_search = clean_input($_POST["event_title_search"], "notags");
	}
	if (isset($_POST["show_children"]) && $_POST["show_children"] == "true") {
		$show_children = true;
	} else {
		$show_children = false;
	}
	?>
    <style>
        h1,h2 {
            page-break-before:	always;
        }
    </style>
	<h1><?php echo $translate->_("Curriculum Review Report"); ?></h1>

	<div class="no-printing">
		<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" onsubmit="selIt()" class="form-horizontal">
            <input type="hidden" name="organisation_id" id="organisation_id" value="<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>">
			<h2><?php echo $translate->_("Report Options"); ?></h2>

			<?php echo Entrada_Utilities::generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>

			<div class="control-group">
				<div class="controls">
					<label class="checkbox">
                        <input type="checkbox" name="show_children" value="true" <?php echo $show_children === true ? "checked=\"checked\"" : ""; ?> />
                        Include child Learning Events
                    </label>
				</div>
			</div>
			<div class="control-group">
				<label class="form-required control-label">Courses Included</label>
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
                    echo "	<input type=\"button\" id=\"courses_list_remove_btn\" class=\"btn\" onclick=\"delIt()\" value=\"Remove\" />\n";
                    echo "	<input type=\"button\" id=\"courses_list_add_btn\" class=\"btn\" onclick=\"addIt()\" style=\"display: none\" value=\"Add\" />\n";
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
		
		echo "<h2 style=\"page-break-before: avoid\">Curriculum Review Report</h2>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATETIME_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATETIME_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).".";
		echo "</div>\n";

		if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] != -1) {
			$organisation_where = " AND (b.`organisation_id` = ".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"].")";
		} else {
			$organisation_where = "";
		}
		
		$eventtype_legend[$event_type["eventtype_id"]] = $event_type["eventtype_title"];
		
		$presentation_ids = array();
		$mcc_presentations = fetch_clinical_presentations(309);
		if ($mcc_presentations) {
			foreach ($mcc_presentations as $mcc_presentation) {
				$presentation_ids[] = $mcc_presentation["objective_id"];
			}
		}
		
		foreach ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] as $course_id) {
			$query = "	SELECT a.`event_id`, b.`course_name`, b.`organisation_id`, a.`event_title`, a.`event_objectives`, a.`event_description`
						FROM `events` AS a
						LEFT JOIN `courses` AS b
						ON b.`course_id` = a.`course_id`
						WHERE (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
						AND a.`course_id` = ".$db->qstr($course_id).
						$organisation_where.
						($show_children == false ? "AND (a.`parent_id` IS NULL OR a.`parent_id` = '0')" : "")."
						ORDER BY a.`event_start` ASC";
			$results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
			
			if ($results) {
				$courses_included[$course_id] = $course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"];
				
				foreach ($results as $result) {
					$output[$course_id][] = $result;

					if (!isset($appendix[$course_id][$result["event_id"]])) {
						$appendix[$course_id][$result["event_id"]] = $result;
					}
					
					$query = "SELECT b.`objective_id`, b.`objective_name`
								FROM `event_objectives` AS a
								JOIN `objective_organisation` AS oo
								ON oo.`organisation_id` = ".$db->qstr($result["organisation_id"])."
								JOIN `global_lu_objectives` AS b
								ON b.`objective_id` = a.`objective_id`
								AND b.`objective_active` = 1
								AND b.`objective_id` = oo.`objective_id`
								WHERE a.`event_id` = ".$db->qstr($result["event_id"]);
					$objectives = $db->GetAll($query);
					if ($objectives) {
							foreach ($objectives as $objective) {
							// This means it's an MCC Presentation. Don't judge me.
							if (in_array($objective["objective_id"], $presentation_ids)) {
								$appendix[$course_id][$result["event_id"]]["presentations"][$objective["objective_id"]] = $objective["objective_name"];
							} else {
								$appendix[$course_id][$result["event_id"]]["objectives"][$objective["objective_id"]] = $objective["objective_name"];
							}
						}
					}
					
					$query = "SELECT b.`topic_id`, b.`topic_name`
								FROM `event_topics` AS a
								JOIN `events_lu_topics` AS b
								ON b.`topic_id` = a.`topic_id`
								AND b.`topic_type` = 'ed10'
								WHERE a.`event_id` = ".$db->qstr($result["event_id"]);
					$topics = $db->GetAll($query);
					if ($topics) {
						foreach ($topics as $topic) {
							$appendix[$course_id][$result["event_id"]]["hottopics"][$topic["topic_id"]] = $topic["topic_name"];
						}
					}
				}
			}
		}
		?>
		<style type="text/css">
		table.grid thead tr td {
			font-weight: 700;
			border-bottom: 2px #CCC solid;
			font-size: 11px;
		}
		
		table.grid tbody tr td {
			vertical-align:top;
			padding:3px 2px 10px 4px;
			font-size: 11px;
			border-bottom: 1px #EEE solid;
		}
		
		table.grid tbody tr td a {
			font-size: 11px;
		}
		
		table.grid tbody td.border-r {
			border-right: 1px #EEE solid;
		}
		</style>
		<?php
		if (count($output)) {
			foreach ($output as $course_id => $result) {
				$total_duration = 0;
				?>
				<h1><?php echo html_encode($courses_included[$course_id]); ?></h1>
				<?php
				if ($appendix[$course_id]) {
					?>
					<table class="grid" style="width: 900px" cellspacing="0" summary="<?php echo html_encode($courses_included[$course_id]); ?>">
					<colgroup>
						<col style="width: 20%" />
						<col style="width: 25%" />
						<col style="width: 25%" />
						<col style="width: 15%" />
						<col style="width: 15%" />
					</colgroup>
					<thead>
						<tr>
							<td class="border-r">Event Title</td>
							<td class="border-r">Description</td>
							<td class="border-r">Freetext Objectives</td>
							<td class="border-r">Objectives</td>
							<td class="border-r">Presentations</td>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($appendix[$course_id] as $event_id => $event) {
						if (isset($event["objectives"]) && is_array($event["objectives"])) {
							asort($event["objectives"]);
						}
						
						$objectives = array();
						if ($event["objectives"]) {
							foreach ($event["objectives"] as $value) {
								$firstpart = substr($value, 0, (strlen($value) - 1));
								$letter = substr($value, -1);
								
								if (!isset($objectives[$firstpart])) {
									$objectives[$firstpart] = $firstpart . $letter;
								} else {
									$objectives[$firstpart] .= ", " . $letter;
								}
							}
						}
						
						echo "<tr>\n";
						echo "	<td class=\"border-r\"><a href=\"".ENTRADA_URL."/events?id=".$event["event_id"]."\" target=\"_blank\">".html_encode($event["event_title"])."</a></td>\n";
						echo "	<td class=\"border-r\">".limit_chars($event["event_description"], 376)."</td>\n";
						echo "	<td class=\"border-r\">".$event["event_objectives"]."</td>\n";
						echo "	<td class=\"border-r\">".(!empty($event["objectives"]) ? "&rsaquo; ".implode("<br />&rsaquo; ", $objectives) : "&nbsp;")."</td>\n";
						echo "	<td class=\"border-r\">".(!empty($event["presentations"]) ? "&rsaquo; ".implode("<br />&rsaquo; ", $event["presentations"]) : "&nbsp;")."</td>\n";
						echo "</tr>\n";
					}
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