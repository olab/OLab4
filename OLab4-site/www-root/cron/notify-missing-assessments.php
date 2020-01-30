<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for notifying learners when there are assessments that need to be completed
 * before the end of the current rotation.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

@ini_set("memory_limit", "-1");
@set_time_limit(0);
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

$user_model = new Models_User();

/**
 * Get the organisations provided by the execution call.
 * Sample: php notify-missing-assessments.php --organisation_id=1,2,3,4
 */
$organisations = array();
if (isset($argv) && is_array($argv)) {
    if (isset($argv[1])) {
        $pieces = explode("=", $argv[1]);
        if (isset($pieces[1])) {
            $organisations = explode(",", $pieces[1]);
        }
    }
}

// These values are the thresholds in which a notice is set. These should be set as required.
$unlikely_assessment_minimum = 1;
$likely_assessment_minimum = 2;
$very_likely_assessment_minimum = 3;

if (empty($organisations)) {
    // Nothing to work on
    exit("No organisations specified.");
}

foreach ($organisations as $organisation_id) {

    // Instantiate a translation object for the given org.
    $translate = Entrada_Utilities::buildTranslateByOrganisation($organisation_id);

    // Build a list of students indexed by the org id.  Loop through the students and add a notice.
    $students = $user_model->getStudentsByOrganisationID($organisation_id, time(), time());
    if (!$students) {
        continue;
    }
    foreach ($students as $student) {
        $objective_code_array = array();
        $current_assessments = array();

        /**
         * Get the schedule for each learner
         */
        $schedule = Models_Schedule::fetchRowByAudienceValueAudienceType($student["proxy_id"], "proxy_id", true, time());
        if (!$schedule) {
            // No schedule found, skip this learner
            continue;
        }
        $halfway_point = Entrada_Utilities::createTimestampForDay(floor(($schedule->getStartDate() + $schedule->getEndDate()) / 2));
        $one_week_before_end = strtotime('-1 week', $schedule->getEndDate());
        if ((date("Y-m-d", $halfway_point) == date("Y-m-d"))
            || (date("Y-m-d", $one_week_before_end) == date("Y-m-d"))
        ) {
            /**
             * Get all of the objectives for the current schedule
             */
            $course_objective_model = new Models_Schedule_CourseObjective();
            $objectives = $course_objective_model->fetchAllByScheduleIDCourseID($schedule->getCourseID(), $schedule->getScheduleParentID());

            $cbme_progress_api = new Entrada_CBME_Visualization(array(
                "actor_proxy_id" => $student["proxy_id"],
                "actor_organisation_id" => $student["organisation_id"],
                "actor_course_id" => $schedule->getCourseID(),
                "datasource_type" => "progress",
                "limit_dataset" => array("epa_assessments")
            ));
            $dataset = $cbme_progress_api->fetchData();
            if ($objectives) {
                /**
                 * Build a list of the objectives that are being compared.  from the $objectives array
                 */
                foreach ($objectives as $objective) {
                    array_push($objective_code_array,
                        array("objective_code" => $objective["objective_code"],
                            "likelihood" => $objective["likelihood_id"],
                            "priority" => $objective["priority"],
                            "assessment_count" => 0,
                            "likelihood_shortname" => $objective["shortname"]
                        )
                    );
                }
                // Go through all of the users assessments
                foreach ($dataset["stage_data"] as $stage) {
                    if (!empty($stage["progress"])) {
                        foreach ($stage["progress"] as $progress) {
                            if ($progress) {
                                // Check to see if the assessment matches a code for the current schedule
                                if ($progress["assessment_count"] != "0") {
                                    foreach ($objective_code_array as &$objective_code) {
                                        if ($objective_code["objective_code"] == $progress["objective_code"]) {
                                            $objective_code["assessment_count"] = (int)$progress["assessment_count"];
                                            //Here we need to build a count of all the assessments that have been completed for objectives from the current schedule.
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($objective_code_array)) {
            $display_from = $halfway_point;
            $display_until = $schedule->getEndDate();
            $notice_details = $translate->_("Assessment Likelihood Notice");
            foreach ($objective_code_array as $objective_item) {
                switch ($objective_item["likelihood_shortname"]) {
                    case "unlikely":
                        if ($objective_item["assessment_count"] < $unlikely_assessment_minimum) {
                            $notice_summary = sprintf(
                                $translate->_("Our records indicate that you have not been assessed on <strong>%s</strong> during the current rotation.  Although <strong>%s</strong> has been mapped to this rotation, it is unlikely you would experience it here."),
                                $objective_item["objective_code"],
                                $objective_item["objective_code"]
                            );
                            if ($objective_item["priority"] == 1) {
                                $notice_summary .= sprintf($translate->_(" <strong>%s</strong> has been marked as a <strong>priority</strong> if experienced on your current rotation, so be sure to get assessed on <strong>%s</strong> if you experience it."),
                                    $objective_item["objective_code"],
                                    $objective_item["objective_code"]
                                );
                            }
                            Models_Notice::addNotice("proxy_id:" . $student["proxy_id"], $student["organisation_id"], $notice_summary, $notice_details, $display_from, $display_until, $student["proxy_id"]);
                        }
                        break;
                    case "likely":
                        if ($objective_item["assessment_count"] < $likely_assessment_minimum) {
                            $notice_summary = sprintf(
                                $translate->_("Our records indicate that you have had <strong>%s</strong> assessed less than %s times during the current rotation.  It is likely that you will experience <strong>%s</strong> during this rotation, so you may want to consider getting more assessments done on <strong>%s</strong>."),
                                $objective_item["objective_code"],
                                $likely_assessment_minimum,
                                $objective_item["objective_code"],
                                $objective_item["objective_code"]
                            );
                            if ($objective_item["priority"] == 1) {
                                $notice_summary .= sprintf(
                                    $translate->_(" <strong>%s</strong> has been marked as a <strong>priority</strong> when experienced on your current rotation, so be sure to get assessed on <strong>%s</strong> when you experience it."),
                                    $objective_item["objective_code"],
                                    $objective_item["objective_code"]
                                );
                            }
                            Models_Notice::addNotice("proxy_id:" . $student["proxy_id"], $student["organisation_id"], $notice_summary, $notice_details, $display_from, $display_until, $student["proxy_id"]);
                        }
                        break;
                    case "very_likely":
                        if ($objective_item["assessment_count"] < $very_likely_assessment_minimum) {
                            $notice_summary = sprintf(
                                $translate->_("Our records indicate that you have had <strong>%s</strong> assessed less than %s times during the current rotation.  It is very likely that you will experience <strong>%s</strong> during this rotation, so you may want to consider getting more assessments done on <strong>%s</strong>."),
                                $objective_item["objective_code"],
                                $very_likely_assessment_minimum,
                                $objective_item["objective_code"],
                                $objective_item["objective_code"]
                            );
                            if ($objective_item["priority"] == 1) {
                                $notice_summary .= sprintf(
                                    $translate->_(" <strong>%s</strong> has been marked as a <strong>priority</strong> when experienced on your current rotation, so be sure to get assessed on <strong>%s</strong> when you experience it."),
                                    $objective_item["objective_code"],
                                    $objective_item["objective_code"]
                                );
                            }
                            Models_Notice::addNotice("proxy_id:" . $student["proxy_id"], $student["organisation_id"], $notice_summary, $notice_details, $display_from, $display_until, $student["proxy_id"]);
                        }
                        break;
                }
            }
        }
    }
}

