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
 * This API file returns Assessor records in the format:
 *
 * $assessments = array(
 * 	0 => array(
 * 		"id" 		     => 16,
 * 		"title" 	     => "Winter 2014 Assessment",
 * 		"link"           => "http://www.onefortyfiveapp.com/login?assessmentid=16",
 * 		"startDate"      => 1361517660,
 * 		"endDate"        => 1393053660,
 * 		"gracePeriodEnd" => 1395472860,
 * 		"program"        => array(
 * 			"id"   => 122,
 * 			"name" => "MEDS244 - Clinical & Communication Skills 3"
 * 		),
 * 		"status"         => PRECEPTOR_ASSESSEVAL_STATUS_CLOSED,
 * 		"dataSource"     => PRECEPTOR_ASSESSEVAL_DATASOURCE_ONEFORTYFIVE,
 * 		"targets"         => array(
 * 			array(
 * 				"id"   => 153,
 * 				"name" => "David Erikson"
 * 			),
 * 			array(
 * 				"id"   => 525,
 * 				"name" => "Arthur Hardy"
 * 			),
 * 			array(
 * 			    "id"   => 15,
 * 			    "name" => "Andrea D. McMillan"
 * 			)
 * 		)
 * 	)
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    $status = array();
    if (isset(${$request_var}["status"])) {
        $tmp_input = ${$request_var}["status"];
        if (is_array($tmp_input)) {
            foreach($tmp_input as $tmp) {
                $status[] = clean_input($tmp, array("trim", "notags"));
            }
        }
    }

    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
        $search_term = $tmp_input;
    } else {
        $search_term = false;
    }

    if (isset(${$request_var}["assessment_index_view_preference"]) && $tmp_input = clean_input(${$request_var}["assessment_index_view_preference"], array("trim", "striptags"))) {
        $assessment_index_view_preference = $tmp_input;
    }

    if (isset($assessment_index_view_preference)) {
        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_index_view_preference"] = $assessment_index_view_preference;
    }

    switch ($request) {
        case "POST" :
            break;
        case "GET" :
            switch ($method) {
                case "list-assessments":
                    $assessments = array();
                    $count = 0;
                    $assessors = Models_Assessments_Distribution_Assessor::fetchAllByProxyIDSearch($ENTRADA_USER->getActiveID(), $search_term);

                    if ($assessors) {
                        $target_text = "N/A";
                        /* @var $assessor Models_Assessments_Distribution_Assessor */
                        foreach ($assessors as $assessor) {
                            $targets = fetchAssessmentTargets($assessor["adistribution_id"]);

                            $form_type = fetchFormTypeTitle($assessor["adistribution_id"]);
                            $target_text = $form_type["title"];

                            $schedule = Models_Schedule::fetchRowByID($assessor["schedule_id"]);
                            $schedule_children = $schedule->getChildren();

                            $progress_value["name"] = "Awaiting Completion";

                            $assessor_user = Models_Assessments_Distribution_Assessor::fetchRowByID($assessor["adassessor_id"]);

                            if ($schedule_children) {
                                foreach($schedule_children as $schedule_child) {
                                    $progress = false;
                                    if ($targets && count($targets) == 1) {
                                        $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor["adistribution_id"], "internal", $ENTRADA_USER->getActiveId());
                                    }

                                    $progress_value = fetchTargetStatus($targets, $assessor_user, $schedule_child);

                                    if (in_array($progress_value["shortname"], $status)) {
                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$assessor["adistribution_id"]."&schedule_id=".$assessor["schedule_id"]."&form_id=".$assessor["form_id"].($progress ? "&aprogress_id=".$progress->getID() : "");

                                        $row = array();
                                        $row["id"] = $assessor["adistribution_id"];
                                        $row["title"] = $assessor["rotation_name"];
                                        $row["link"] = $url;
                                        $row["startDate"] = $assessor["rotation_start_date"];
                                        $row["endDate"] = $assessor["rotation_end_date"];
                                        $row["gracePeriodEnd"] = $assessor["rotation_end_date"];
                                        $row["program"] = array(
                                            "id"   => $assessor["course_id"],
                                            "name" => $assessor["course_name"]);
                                        $row["status"] = $progress_value["name"];
                                        $row["dataSource"] = "";
                                        $row["targets"] = array(array(
                                            "id"   => "",
                                            "name" => $target_text));
                                        if ($assessor["target_type"] == "proxy_id") {
                                            $image_src = webservice_url("photo", array($assessor["target_id"], "official"));
                                        } else {
                                            $image_src = $ENTRADA_TEMPLATE->url()."/images/icon-checklist.gif";
                                        }
                                        $row["img_src"] = $image_src;
                                        $assessments[] = $row;
                                        $count++;
                                    }
                                }
                            } else {
                                $progress = false;
                                if ($targets && count($targets) == 1) {
                                    $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor["adistribution_id"], "internal", $ENTRADA_USER->getActiveId());
                                }

                                $progress_value = fetchTargetStatus($targets, $assessor_user, $schedule);

                                if (in_array($progress_value["shortname"], $status)) {
                                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$assessor["adistribution_id"]."&schedule_id=".$assessor["schedule_id"]."&form_id=".$assessor["form_id"].($progress ? "&aprogress_id=".$progress->getID() : "");

                                    $row = array();
                                    $row["id"] = $assessor["adistribution_id"];
                                    $row["title"] = $assessor["rotation_name"];
                                    $row["link"] = $url;
                                    $row["startDate"] = $assessor["rotation_start_date"];
                                    $row["endDate"] = $assessor["rotation_end_date"];
                                    $row["gracePeriodEnd"] = $assessor["rotation_end_date"];
                                    $row["program"] = array(
                                        "id"   => $assessor["course_id"],
                                        "name" => $assessor["course_name"]);
                                    $row["status"] = $progress_value["name"];
                                    $row["dataSource"] = "";
                                    $row["targets"] = array(array(
                                        "id"   => "",
                                        "name" => $target_text));
                                    if ($assessor["target_type"] == "proxy_id") {
                                        $image_src = webservice_url("photo", array($assessor["target_id"], "official"));
                                    } else {
                                        $image_src = $ENTRADA_TEMPLATE->url()."/images/icon-checklist.gif";
                                    }
                                    $row["img_src"] = $image_src;
                                    $assessments[] = $row;
                                    $count++;
                                }
                            }
                        }

                        echo json_encode(array("status" => "success", "data" => array("assessments" => $assessments)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("assessments" => array())));
                    }
                break;
                case "list-assessment-learners":
                    $learners = array();
                    $count = 0;
                    $course_groups = Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
                        $search_term = $tmp_input;
                    } else {
                        $search_term = false;
                    }

                    if ($course_groups) {
                        foreach ($course_groups as $course_group) {
                            $course = Models_Course::fetchRowByID($course_group->getCourseID());
                            $tmp_learners = Models_User::fetchAllByCGroupIDSearchTerm($course_group->getID(), $search_term);
                            if ($course && $tmp_learners) {
                                foreach ($tmp_learners as $learner) {

                                    $duplicate = false;
                                    // Ensure this is not a duplicate.
                                    foreach ($learners as $previous_learner) {
                                        if ($previous_learner["id"] == $learner->getID()) {
                                            $duplicate = true;
                                        }
                                    }

                                    if (!$duplicate) {
                                        $url = ENTRADA_URL . "/assessments/learner?proxy_id=" . $learner->getID();
                                        $tasks = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($learner->getID(), $ENTRADA_USER->getActiveOrganisation(), "learner");
                                        $completed_tasks = $tasks["complete"];

                                        $row = array();
                                        $row["id"] = $learner->getID();
                                        $row["name"] = $learner->getLastname() . ", " . $learner->getFirstname();
                                        $row["email"] = $learner->getEmail();
                                        $row["link"] = $url;
                                        $row["img_src"] = webservice_url("photo", array($learner->getID(), "official"));
                                        $row["course_title"] = $course->getCourseName();
                                        $row["completed_assessments"] = ($completed_tasks && @count($completed_tasks) ? @count($completed_tasks) : 0);
                                        $learners[] = $row;
                                        $count++;
                                    }
                                }
                            }
                        }
                        // Sort learners by name.
                        usort($learners,  function ($a, $b) {
                            return strcmp($a["name"], $b["name"]);
                        });

                        echo json_encode(array("status" => "success", "data" => array("learners" => $learners)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("learners" => array())));
                    }
                break;
                case "list-assessment-faculty":
                    $faculty = array();
                    $count = 0;

                    $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
                        $search_term = $tmp_input;
                    } else {
                        $search_term = false;
                    }

                    if ($courses) {
                        $tmp_faculty = array();
                        foreach ($courses as $course) {
                            if (CourseOwnerAssertion::_checkCourseOwner($ENTRADA_USER->getActiveID(), $course->getID())) {

                                // Add course directors.
                                $tmp_directors = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "director", $search_term);
                                if ($tmp_directors) {
                                    foreach ($tmp_directors as $director) {
                                        $tmp_faculty[] = $director;
                                    }
                                }

                                // Add course associated_faculty.
                                $tmp_associated_faculty = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "associated_faculty", $search_term);
                                if ($tmp_associated_faculty) {
                                    foreach ($tmp_associated_faculty as $associated_faculty) {
                                        $tmp_faculty[] = $associated_faculty;
                                    }
                                }

                                if ($tmp_faculty) {
                                    foreach ($tmp_faculty as $tmp) {

                                        $person = Models_User::fetchRowByID($tmp["proxy_id"]);
                                        if ($person) {

                                            $duplicate = false;
                                            // Ensure this is not a duplicate.
                                            foreach ($faculty as $previous_faculty) {
                                                if ($previous_faculty["id"] == $person->getID()) {
                                                    $duplicate = true;
                                                }
                                            }

                                            if (!$duplicate) {
                                                $url = ENTRADA_URL . "/assessments/faculty?proxy_id=" . $person->getID();
                                                $current_assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllTasks($person->getID(), "faculty");

                                                $row = array();
                                                $row["id"] = $person->getID();
                                                $row["name"] = $person->getLastname() . ", " . $person->getFirstname();
                                                $row["email"] = $person->getEmail();
                                                $row["link"] = $url;
                                                $row["img_src"] = webservice_url("photo", array($person->getID(), "official"));
                                                $row["course_title"] = $course->getCourseName();
                                                $faculty[] = $row;
                                                $count++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Sort faculty by name.
                        usort($faculty,  function ($a, $b) {
                            return strcmp($a["name"], $b["name"]);
                        });

                        echo json_encode(array("status" => "success", "data" => array("faculty" => $faculty)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("faculty" => array())));
                    }
                    break;

                case "list-assessments-by-user":
                    /**
                     * Find all completed progress records that have the current user as the target.
                     */
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error("A user is required");
                    }

                    if (!$ERROR) {
                        $assessments = array();
                        $progress_records = Models_Assessments_Progress::fetchAllByProxyIDSearch($proxy_id, $search_term);
                        if ($progress_records) {
                            foreach ($progress_records as $progress) {
                                $distribution = Models_Assessments_Distribution::fetchRowByID($progress->getAdistributionID());
                                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($progress->getAdistributionID());
                                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                $rotation_name = "N/A";
                                if ($schedule->getScheduleParentID()) {
                                    $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                                    $rotation_name = $schedule_parent->getTitle();
                                } else {
                                    $rotation_name = $schedule->getTitle();
                                }
                                $course = Models_Course::fetchRowByID($schedule->getCourseID());

                                $url = ENTRADA_URL . "/assessments/viewassessment?&target_record_id=".$proxy_id."&adistribution_id=".$distribution->getID()."&schedule_id=".$distribution_schedule->getScheduleID()."&form_id=".$distribution->getFormID()."&aprogress_id=".$progress->getID();

                                $row = array();
                                $row["id"] = $progress->getAdistributionID();
                                $row["title"] = $rotation_name;
                                $row["program"] = array(
                                    "id"   => $course->getID(),
                                    "name" => $course->getCourseName());
                                $row["link"] = $url;
                                $row["startDate"] = $schedule->getStartDate();
                                $row["endDate"] = $schedule->getEndDate();
                                $row["assessor"] = User::fetchRowByID($progress->getCreatedBy())->getFullname(false);
                                $image_src = webservice_url("photo", array($progress->getProxyID(), "official"));

                                $row["img_src"] = $image_src;

                                $assessments[] = $row;
                                $count++;
                            }

                            echo json_encode(array("status" => "success", "data" => array("assessments" => $assessments)));
                        } else {
                            echo json_encode(array("status" => "success", "data" => array("assessments" => array())));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => array("No Assessments Available.")));
                    break;
            }
    }

    exit;
}