<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves a particular calendar in either JSON or ICS depending on the extension of the $_GET["request"];
 * http://www.yourschool.ca/calendars/username.json
 * http://www.yourschool.ca/calendars/username.ics
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$event_id = 0;
$dashboard_result_id = false;

if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
	$event_id = $tmp_input;
}

if (isset($_GET["drid"])) {
	$dashboard_result_id = clean_input($_GET["drid"], array("trim", "int"));
}

if (($event_id) && (isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo DEFAULT_CHARSET; ?>" />

		<title>Calendar: Event Summary</title>

		<link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
		<link href="<?php echo ENTRADA_RELATIVE; ?>/css/print.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="print" />
	</head>
	<body>
	<?php
	$query = "	SELECT a.*, b.`course_name`, b.`course_code`, b.`organisation_id`, IF(a.`room_id` IS NULL, a.`event_location`, CONCAT(d.`building_code`, '-', c.`room_number`)) AS `event_location`
				FROM `events` AS a
				LEFT JOIN `courses` AS b
				ON b.`course_id` = a.`course_id`
                LEFT JOIN `global_lu_rooms` AS c
                ON c.`room_id` = a.`room_id`
                LEFT JOIN `global_lu_buildings` AS d
                ON d.`building_id` = c.`building_id`
				WHERE a.`event_id` = ".$db->qstr($event_id);
	$event_info	= $db->GetRow($query);
	if ($event_info) {
		$LASTUPDATED = $event_info["updated_date"];

		if(($event_info["release_date"]) && ($event_info["release_date"] > time())) {
			add_error("The event you are trying to view is not yet available. Please try again after ".date("r", $event_info["release_date"]));

			echo display_error();
		} elseif(($event_info["release_until"]) && ($event_info["release_until"] < time())) {
            add_error("The event you are trying to view is no longer available; it expired ".date("r", $event_info["release_until"]));

			echo display_error($errorstr);
		} else {
			if($ENTRADA_ACL->amIAllowed(new EventResource($event_id, $event_info["course_id"], $event_info["organisation_id"]), "read")) {
				add_statistic("events", "view", "event_id", $event_id);

				$event_resources	= events_fetch_event_resources($event_id, "all");
				$event_files		= (is_array($event_resources["files"]) ? count($event_resources["files"]) : 0);
				$event_links		= (is_array($event_resources["links"]) ? count($event_resources["links"]) : 0);
				$event_quizzes		= (is_array($event_resources["quizzes"]) ? count($event_resources["quizzes"]) : 0);
				$event_discussions	= (is_array($event_resources["discussions"]) ? count($event_resources["discussions"]) : 0);
				
				if ($ENTRADA_USER->getActiveGroup() === "student") {
				    $event_exams 	= Models_Exam_Post::fetchAllByEventIDNotHidden($event_id);
				} else {
				    $event_exams	= Models_Exam_Post::fetchAllByEventID($event_id);
				}

				?>
				<div id="eventToolTip">
					<div class="colLeft">
						<table style="width: 100%" cellspacing="1" cellpadding="1" border="0">
						<tr>
							<td colspan="2" style="padding-bottom: 5px"><a href="<?php echo ENTRADA_URL; ?>/courses?id=<?php echo $event_info["course_id"]; ?>" target="_blank" style="font-weight: bold"><?php echo html_encode($event_info["course_code"]) . ": " . html_encode($event_info["course_name"]); ?></a></td>
						</tr>
						<tr>
							<td><strong>Date &amp; Time</strong></td>
							<td><?php echo date(DEFAULT_DATE_FORMAT, $event_info["event_start"]); ?></td>
						</tr>
						<tr>
							<td><strong>Duration</strong></td>
							<td><?php echo (((int) $event_info["event_duration"]) ? $event_info["event_duration"]." minutes" : "To Be Announced"); ?></td>
						</tr>
						<tr>
							<td><strong>Location</strong></td>
							<td><?php echo (($event_info["event_location"]) ? $event_info["event_location"] : "To Be Announced"); ?></td>
						</tr>						
                        <tr>
							<td><strong>Attendance</strong></td>
							<td><?php echo (isset($event_info["attendance_required"]) && ($event_info["attendance_required"] == 0) ? "<em>Optional</em>" :  "Required"); ?></td>
						</tr>
						<?php if (trim($event_info["event_message"]) != "") : ?>
						<tr>
							<td colspan="2" style="padding-top: 15px">
								<strong>Required Preparation</strong><br />
								<?php echo limit_chars(trim(strip_tags($event_info["event_message"])), 300); ?>
							</td>
						</tr>
						<?php endif; ?>
						</table>
					</div>
					<div class="colRight">
						<img src="<?php echo ENTRADA_RELATIVE; ?>/images/attachment.gif" width="16" height="16" alt="Resources" style="vertical-align: middle" /> <strong style="vertical-align: middle">Event Resources</strong>
						<ul style="margin: 5px 0 5px 5px; padding-left: 15px; list-style-type: none">
							<li><a href="<?php echo ENTRADA_URL; ?>/events?id=<?php echo $event_id; ?>#event-resources-files"><?php echo $event_files; ?> attached file<?php echo (($event_files != 1) ? "s" : ""); ?></a></li>
							<li><a href="<?php echo ENTRADA_URL; ?>/events?id=<?php echo $event_id; ?>#event-resources-links"><?php echo $event_links; ?> attached link<?php echo (($event_links != 1) ? "s" : ""); ?></a></li>
							<li><a href="<?php echo ENTRADA_URL; ?>/events?id=<?php echo $event_id; ?>#event-resources-quizzes"><?php echo $event_quizzes; ?> attached quiz<?php echo (($event_quizzes != 1) ? "zes" : ""); ?></a></li>
							<li style="margin-top: 15px"><a href="<?php echo ENTRADA_URL; ?>/events?id=<?php echo $event_id; ?>#event-comments-section"><?php echo $event_discussions; ?> discussion<?php echo (($event_discussions != 1) ? "s" : ""); ?></a></li>
                            <?php
                            if (isset($event_exams) && is_array($event_exams)) {
                                $exam_count = count($event_exams);
                                $EXAM_TEXT = $translate->_("exams");
                                ?>
                                <li><a href="<?php echo ENTRADA_URL; ?>/events?drid=<?php echo $event_id; ?>#event-resources-exams"><?php echo $exam_count . " " . strtolower($EXAM_TEXT["exams"]["posts"]["title_singular"]) . (($exam_count != 1) ? "s" : ""); ?></a></li>
                                <?php
                            }
                            ?>
						</ul>
					</div>
					<div style="clear: both; text-align: center; padding-top: 15px">
						<a href="<?php echo ENTRADA_URL; ?>/events?<?php echo (($dashboard_result_id === 0 || $dashboard_result_id >= 1) ? "drid=" . $dashboard_result_id : "id=" . $event_id); ?>" style="font-weight: bold; font-size: 12px">Review Learning Event</a>
					</div>
				</div>
				<?php
			} else {
                add_error("You are not permitted to access this event.");

				echo display_error($errorstr);
				application_log("error", "Proxy_id [".$ENTRADA_USER->getID()."] attempted to access event_id [".$event_id."] and was denied access.");
			}
		}
	}
	?>
	</body>
	</html>
	<?php
}
?>