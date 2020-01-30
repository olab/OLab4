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
 * This file displays the list of learning events that match any requested
 * filters. Data is pulled from the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else if (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_timestamp.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/events_exporter.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

	$default_csv_headings = array(
			"event_id" => "Original Event",
            "recurring_id" => "Recurring Event",
			"event_term" => "Term",
			"course_code" => "Course Code",
			"course_name" => "Course Name",
			"event_start_date" => "Date",
			"event_start_time" => "Start Time",
			"total_duration" => "Total Duration",
			"event_type_durations" => "Event Type Durations",
			"event_types" => "Event Types",
			"event_title" => "Event Title",
			"event_description" => "Event Description",
			"event_location" => "Location",
            "event_location_room" => "Location Room",
			"audience_cohorts" => "Audience (Cohorts)",
			"audience_groups" => "Audience (Groups)",
			"audience_students" => "Audience (Students)",
			"teacher_numbers" => "Teacher Numbers",
			"teacher_names" => "Teacher Names",			
			"objectives_release_date" => "Objectives Release Date"
	);

	$objective_name = $translate->_("events_filter_controls");
	$curriculum_objectives_name = $objective_name["co"]["global_lu_objectives_name"];
	$clinical_presentations_name = $objective_name["cp"]["global_lu_objectives_name"];
	
	$additional_csv_headings = array(
		"student_names" => "Student Names",
		"release_date" => "Release Date",
		"release_until" => "Release Until",
		"event_children" => "Child Events",
        "parent_event" => "Parent Event",
		"parent_id" => "Parent ID",
		"event_message" => "Required Preparation",
		"free_text_objectives" => "Free Text Objectives",
		"curriculum_objectives" => $curriculum_objectives_name,
		"clinical_presentations" => $clinical_presentations_name,
		"hot_topics" => "Hot Topics",
		"attached_files" => "Attached Files",
		"attached_links" => "Attached Links",
		"attached_quizzes" => "Attached Quizzes",
		"attendance" => "Attendance",
		"attendance_required" => "Attendance Required",
		"auditor_numbers" => "Auditor Numbers",
		"auditor_names" => "Auditor Names",
		"teachers_assistant_numbers" => "Teacher's Assistant Numbers",
		"teachers_assistant_names" => "Teacher's Assistant Names",
		"tutor_numbers" => "Tutor Numbers",
		"tutor_names" => "Tutor Names",
	);

    if (isset($_SESSION["my_export_options"]) && $_SESSION["my_export_options"]) {
        $diff = array_diff($default_csv_headings, $_SESSION["my_export_options"]);
        $default_csv_headings = $_SESSION["my_export_options"];
        $additional_csv_headings = array_diff($additional_csv_headings, $_SESSION["my_export_options"]);
    }

	if (isset($diff) && $diff && is_array($diff)) {
		$additional_csv_headings = array_merge($additional_csv_headings, $diff);
	}

	/**
	 * Process any sorting or pagination requests.
	 */
	events_process_sorting();

	/**
	 * Process any filter requests.
	 */
	events_process_filters($ACTION, "admin");

	/**
	 * Check if preferences need to be updated.
	 */
	preferences_update($MODULE, $PREFERENCES);

	/**
	 * Fetch all of the events that apply to the current filter set.
	 */
	$learning_events = events_fetch_filtered_events(
			$ENTRADA_USER->getActiveId(),
			$ENTRADA_USER->getActiveGroup(),
			$ENTRADA_USER->getActiveRole(),
			$ENTRADA_USER->getActiveOrganisation(),
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"],
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["so"],
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"],
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"],
			0,
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"],
			false, //pagination
			(isset($_GET["pv"]) ? (int) trim($_GET["pv"]) : 1), //current page
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"], //results per page, not necessary
            false, //community id
            false); //respect time release
    
    //Filter events by whether the user has update rights
    $learning_events["events"] = array_filter(
        $learning_events["events"],
        function($item) use ($ENTRADA_ACL) {
            return $ENTRADA_ACL->amIAllowed(new EventResource($item["event_id"], $item["course_id"], $item["organisation_id"]), "update") ||
                   $ENTRADA_ACL->amIAllowed(new EventContentResource($item["event_id"], $item["course_id"], $item["organisation_id"]), "update");
        }
    );
    //Fix total_rows and total_pages for pagination and get the slice of events for this page
    $learning_events["total_rows"] = count($learning_events["events"]);
    $learning_events["events"] = array_slice($learning_events["events"], ((isset($_GET["pv"]) ? (int) trim($_GET["pv"]) : 1) - 1) * $_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"]);
    $learning_events["total_pages"] = ceil($learning_events["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"]);

	echo "<h1>".$MODULES[strtolower($MODULE)]["title"]."</h1>";

	if (isset($_SESSION["export_error"]) && $_SESSION["export_error"]) {
		add_error($_SESSION["export_error"]);

		echo display_error();
	}

	/**
	 * Output the filter HTML.
	 */
	events_output_filter_controls("admin");

	if ($ENTRADA_ACL->amIAllowed("event", "create", false)) {
		?>
		<div class="row-fluid">
			<span class="pull-right">
				<a class="btn space-right" href="<?php echo ENTRADA_RELATIVE; ?>/admin/events/drafts"><i class="icon-file"></i> Manage My Drafts</a>
				<a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=add"><i class="icon-plus-sign icon-white"></i> Add New Event</a>
			</span>
		</div>
		<br />
		<?php
	}

	/**
	 * Output the calendar controls and pagination.
	 */
	events_output_calendar_controls("admin");

	if (!empty($learning_events["events"])) {
		if ($ENTRADA_ACL->amIAllowed("event", "delete", false) or $ENTRADA_ACL->amIAllowed("event", "create", false)) : ?>
		<form name="frmSelect" action="<?php echo ENTRADA_URL; ?>/admin/events?section=delete" method="post">
		<?php endif; ?>
		<p class="muted text-center">
			<small>
                <?php
                switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"]) {
                    case "day" :
                        echo "Found ".$learning_events["total_rows"]." event".(($learning_events["total_rows"] != 1) ? "s" : "")." that take place on <strong>".date("D, M jS, Y", $learning_events["duration_start"])."</strong>.\n";
                        break;
                    case "month" :
                        echo "Found ".$learning_events["total_rows"]." event".(($learning_events["total_rows"] != 1) ? "s" : "")." that take place during <strong>".date("F", $learning_events["duration_start"])."</strong> of <strong>".date("Y", $learning_events["duration_start"])."</strong>.\n";
                        break;
                    case "year" :
                        echo "Found ".$learning_events["total_rows"]." event".(($learning_events["total_rows"] != 1) ? "s" : "")." that take place during <strong>".date("Y", $learning_events["duration_start"])."</strong>.\n";
                        break;
                    default :
                    case "week" :
                        echo "Found ".$learning_events["total_rows"]." event".(($learning_events["total_rows"] != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $learning_events["duration_start"])."</strong> to <strong>".date("D, M jS, Y", $learning_events["duration_end"])."</strong>.\n";
                        break;
                }
                ?>
            </small>
		</p>
		<table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" summary="List of Events">
			<colgroup>
				<col class="modified" />
				<col class="date" />
				<col class="course-code" />
				<col class="title" />
				<col class="attachment" />
			</colgroup>
			<thead>
				<tr>
					<th class="modified">&nbsp;</th>
                    <th class="date<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "date") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo admin_order_link("date", "Date &amp; Time"); ?></th>
                    <th class="course-code<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "course") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo admin_order_link("course", "Course"); ?></th>
                    <th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo admin_order_link("title", "Event Title"); ?></th>
					<th class="attachment">&nbsp;</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
                    <td>&nbsp;</td>
					<td style="padding-top: 10px" colspan="2">
						<?php
						if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
							?>
							<input type="submit" class="btn btn-danger" value="Delete Selected" />
							<?php
						}
						if ($ENTRADA_ACL->amIAllowed("event", "create", false)) {
							?>
							<input type="submit" class="btn" value="Copy Selected" onClick="document.frmSelect.action ='<?php echo ENTRADA_URL; ?>/admin/events?section=copy'" />
							<?php
						}
						?>
					</td>
					<td style="padding-top: 10px; text-align: right" colspan="2">
						<?php
						if ($ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
							?>
							<a href="#export-results" id="export-results-button" class="btn btn-default" data-toggle="modal"><i class="fa fa-download"></i> <?php echo $translate->_("Export Results"); ?></a>
							<?php
						}
						?>
					</td>
				</tr>
			</tfoot>
			<tbody>

			<?php
			$count_modified = 0;

			foreach ($learning_events["events"] as $result) {
				$url = "";
				$accessible = true;
				$administrator = false;
				if ($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
					$administrator = true;
					$url = ENTRADA_URL."/admin/events?section=edit&amp;id=".$result["event_id"];
				} else if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
					$url = ENTRADA_URL."/admin/events?section=content&amp;id=".$result["event_id"];
				}

				if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
					$accessible = false;
				}

				echo "<tr id=\"event-".$result["event_id"]."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
				echo "	<td class=\"modified".((!$url) ? " np" : "")."\">".(($administrator) ? "<input type=\"checkbox\" name=\"checked[]\" value=\"".$result["event_id"]."\" />" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"19\" height=\"19\" alt=\"\" title=\"\" />")."</td>\n";
				echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Date\">" : "").date(DEFAULT_DATETIME_FORMAT, $result["event_start"]).(($url) ? "</a>" : "")."</td>\n";
				echo "	<td class=\"course".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Intended For ".html_encode($result["course_code"])."\">" : "").html_encode($result["course_code"]).(($url) ? "</a>" : "")."</td>\n";
				echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">" : "").html_encode($result["event_title"]).(($url) ? "</a>" : "")."</td>\n";
				echo "  <td class=\"attachment".((!$url) ? " np" : "")."\">";
                if ($url) {
                    echo "  <div class=\"btn-group\">\n";
                    echo "      <button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
                    echo "          <i class=\"fa fa-cog\" aria-hidden=\"true\"></i>\n";
                    echo "      </button>";
                    echo "      <ul class=\"dropdown-menu toggle-left\">\n";
					if ($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), 'update')) {
                        echo "      <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=edit&amp;id=".$result["event_id"]."\">" . $translate->_("Setup") . "</a></li>";
                    }
                    echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=content&amp;id=".$result["event_id"]."\">" . $translate->_("Content") . "</a></li>";
                    echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=attendance&amp;id=".$result["event_id"]."\">" . $translate->_("Attendance") . "</a></li>";
                    echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=history&amp;id=".$result["event_id"]."\">" . $translate->_("History") . "</a></li>";
                    echo "          <li><a href=\"".ENTRADA_RELATIVE . "/admin/events?section=statistics&amp;id=".$result["event_id"]."\">" . $translate->_("Statistics") . "</a></li>";
                    echo "      </ul>\n";
                    echo "  </div>\n";
                } else {
                    echo "&nbsp;";
                }
                echo "  </td>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
		</table>
		<?php if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) : ?>
		</form>
		<?php
		endif;
	} else {
		$filters_applied = (((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"])) && ($filters_total = @count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"]))) ? true : false);
		?>
		<div class="display-notice">
			<h3>No Matching Events</h3>
			There are no learning events scheduled
			<?php
			switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"]) {
				case "day" :
					echo "that take place on <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_start"])."</strong>";
				break;
				case "month" :
					echo "that take place during <strong>".date("F", $learning_events["duration_start"])."</strong> of <strong>".date("Y", $learning_events["duration_start"])."</strong>";
				break;
				case "year" :
					echo "that take place during <strong>".date("Y", $learning_events["duration_start"])."</strong>";
				break;
				//default :
				case "week" :
					echo "from <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_start"])."</strong> to <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_end"])."</strong>";
				break;
				default :
					continue;
				break;
			}
			echo (($filters_applied) ? " that also match the supplied &quot;Show Only&quot; restrictions" : "") ?>.
			<br /><br />
			If this is unexpected there are a few things that you can check:
			<ol>
				<li style="padding: 3px">Make sure that you are browsing the intended time period. For example, if you trying to browse <?php echo date("F", time()); ?> of <?php echo date("Y", time()); ?>, make sure that the results bar above says &quot;... takes place in <strong><?php echo date("F", time()); ?></strong> of <strong><?php echo date("Y", time()); ?></strong>&quot;.</li>
				<?php
				if ($filters_applied) {
					echo "<li style=\"padding: 3px\">You also have ".$filters_total." filter".(($filters_total != 1) ? "s" : "")." applied to the event list. you may wish to remove ".(($filters_total != 1) ? "one or more of these" : "it")." by clicking the link in the &quot;Showing Events That Include&quot; box above.</li>";
				}
				?>
			</ol>
		</div>
		<?php
	}

	echo "<form action=\"\" method=\"get\">\n";
	echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
	echo "</form>\n"; ?>

	<div class="modal hide fade" id="export-results">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3>Export Results</h3>
		</div>
		<div class="modal-body">
			<?php echo display_notice("Select the fields you would like to export by dragging them from the left to the right.  Remove fields from the Export by dragging them from the right to the left."); ?>
			<div id="available-wrap">
				<h3>Available Fields:</h3>
				<div id="available_export_options_container" class="ui-widget-content">
					<ul id="available_export_options">
					</ul>
				</div>
			</div>
			<div id="export-wrap">
				<h3>Export Fields:</h3>
				<div id="selected_export_options_container" class="ui-widget-content">
					<ul id="selected_export_options">
						<?php
						if ($default_csv_headings) {
							foreach($default_csv_headings as $key => $value) {
								echo "<li class=\"ui-widget-content ui-state-default\" data-field=\"" . $key . "\"><span class=\"ui-icon ui-icon-arrowthick-2-n-s\"></span>" . $value . "</li>";
							}
						}
						?>
					</ul>
				</div>
			</div>
			<form id="my_export_options_form" action="<?php echo ENTRADA_URL . "/admin/events/export"; ?>">
				<input type="hidden" name="my_export_options" value="" />
			</form>
		</div>
		<div class="modal-footer">
			<button data-dismiss="modal" class="btn">Close</button>
			<a href="#" class="btn btn-primary" id="export-button">Export</a>
		</div>
	</div>

			<script type="text/javascript">
				jQuery(document).ready(function($){
					var default_items = {};
					var additional_items = {};
					<?php
						echo "default_items = " . json_encode((object) $default_csv_headings) . ";";
						echo "additional_items = " . json_encode((object) $additional_csv_headings) . ";";
					?>

					$("ul#selected_export_options > li").sortable();

					for (var key in additional_items) {
						var item = $( "<li></li>" ).text( additional_items[key] ).addClass("ui-widget-content ui-state-default draggable");
						item.attr("data-field", key);
						item.appendTo( $("ul#available_export_options") );
					}

					var my_export_options = default_items;
					$('input[name=my_export_options]').val(JSON.stringify(my_export_options));

					$(".draggable").draggable({
						revert:"invalid"
					});
					$("#export-button").on("click", function() {
						$("#my_export_options_form").submit();
					});
				});
			</script>

			<style type="text/css">
				#available-wrap {
					float:left;
					width:49%;
				}

				#export-wrap {
					float:right;
					width:49%;
				}

				#available_export_options_container {
					border:none;
					float: left;
					width: 95%;
					padding: 0.5em;
				}

				#selected_export_options_container {
					border:none;
					float: right;
					width: 95%;
					padding: 0.5em;
				}

				li.ui-widget-content {
					width: 50%;
				}

				#selected_export_options, #available_export_options {
					list-style-type: none;
					margin: 0;
					padding: 0;
					width: 100%;
				}

				#selected_export_options li, #available_export_options li {
					margin: 0 3px 3px 3px;
					padding: 0.4em;
					padding-left: 1.5em;
					font-size: 1em;
					height: 13px;
					cursor: move;
					width: 89%;
				}

				#available_export_options li {
					padding-left: 0.5em; width: 94%
				}

				#selected_export_options li span {
					position: absolute; margin-left: -1.3em;
				}
			</style>
<?php
	/**
	 * Output the sidebar for sorting and legend.
	 */
	events_output_sidebar("admin");

	$ONLOAD[] = "initList()";
}
