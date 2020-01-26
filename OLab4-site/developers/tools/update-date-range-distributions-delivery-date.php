<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to update date range distributions with a delivery date
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
*/

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

global $db;

$ctr = 0;
$distributions = Models_Assessments_Distribution::fetchAllRecordsIgnoreDeletedDate();

if ($distributions) {
	foreach($distributions as $distribution){
        if ($distribution->getID()) {
            $distribution_data = Models_Assessments_Distribution::fetchDistributionData($distribution->getID());
            if ($distribution_data) {
                $controller = new Controllers_Assessment_Distribution();

                $distribution_data["release_date"] = (!is_null($distribution_data["release_date"]) ? date("Y-m-d", $distribution_data["release_date"]) : null);
                $distribution_data["delivery_date"] = (!is_null($distribution_data["delivery_date"]) ? date("Y-m-d", $distribution_data["delivery_date"]) : null);

                $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getID());
                $distribution_data["assessors"] = array();
                if ($assessors) {
                    foreach ($assessors as $assessor) {
                        if (!isset($distribution_data["assessor_type"])) {
                            $distribution_data["assessor_type"] = $assessor->getAssessorType();
                            $distribution_data["assessor_role"] = $assessor->getAssessorRole();
                            $distribution_data["assessor_scope"] = $assessor->getAssessorScope();
                        }
                        if ($assessor->getAssessorType() == "schedule_id") {
                            $distribution_data["all_learner_assessor_mode"] = true;
                        }
                        $tmp_assessor = $assessor->toArray();
                        if ($tmp_assessor["assessor_type"] == "proxy_id") {
                            $tmp_assessor["assessor_name"] = get_account_data("wholename", $tmp_assessor["assessor_value"]);
                        } elseif ($tmp_assessor["assessor_type"] == "external_hash") {
                            $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor->getAssessorValue());
                            $tmp_assessor["assessor_name"] = html_encode($external_assessor->getFirstname() . " " . $external_assessor->getLastname());
                        } elseif ($tmp_assessor["assessor_type"] == "course_id") {
                            $course = Models_Course::fetchRowByID($assessor->getAssessorValue());
                            if ($course) {
                                $tmp_assessor["assessor_name"] = $course->getCourseName();
                            }
                        } elseif ($tmp_assessor["assessor_type"] == "group_id") {
                            $cohort = Models_Group::fetchRowByID($assessor->getAssessorValue());
                            if ($cohort) {
                                $tmp_assessor["assessor_name"] = $cohort->getGroupName();
                            }
                        } elseif ($tmp_assessor["assessor_type"] == "organisation_id") {
                            $organisation = Models_Organisation::fetchRowByID($assessor->getAssessorValue());
                            if ($organisation) {
                                $tmp_assessor["assessor_name"] = $organisation->getOrganisationTitle();
                            }
                        }
                        $distribution_data["assessors"][] = $tmp_assessor;
                    }
                }

                $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                $distribution_data["targets"] = array();
                if ($targets) {
                    foreach ($targets as $target) {
                        if (!isset($distribution_data["target_type"])) {
                            $distribution_data["target_type"] = $target->getTargetType();
                            $distribution_data["target_role"] = $target->getTargetRole();
                            $distribution_data["target_scope"] = $target->getTargetScope();
                        }
                        if ((!isset($distribution_data["all_learner_target_mode"]) || !$distribution_data["all_learner_target_mode"]) && $target->getTargetType() == "schedule_id") {
                            $distribution_data["all_learner_target_mode"] = true;
                        }
                        $target_array = $target->toArray();
                        if ($target->getTargetType() == "proxy_id") {
                            $user = User::fetchRowByID($target->getTargetId());
                            if ($user) {
                                $target_array["target_name"] = $user->getFullname();
                                $target_array["group"] = ucfirst($user->getGroup());
                                $target_array["role"] = ucfirst($user->getRole());
                            }
                        } elseif ($target->getTargetType() == "course_id") {
                            $course = Models_Course::fetchRowByID($target->getTargetId());
                            if ($course) {
                                $target_array["target_name"] = $course->getCourseName();
                            }
                        } elseif ($target->getTargetType() == "group_id") {
                            $cohort = Models_Group::fetchRowByID($target->getTargetID());
                            if ($cohort) {
                                $target_array["target_name"] = $cohort->getGroupName();
                            }
                        } elseif ($target->getTargetType() == "organisation_id") {
                            $organisation = Models_Organisation::fetchRowByID($target->getTargetID());
                            if ($organisation) {
                                $target_array["target_name"] = $organisation->getOrganisationTitle();
                            }
                        }
                        $distribution_data["targets"][] = $target_array;
                    }
                }

                $authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($distribution->getID());
                $distribution_data["authors"] = array();
                if ($authors) {
                    foreach ($authors as $author) {
                        $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                        $data = $author->toArray();
                        $data["author_name"] = $author_name;
                        $distribution_data["authors"][] = $data;
                    }
                }

                $distribution_approvers = new Models_Assessments_Distribution_Approver();
                $approvers = $distribution_approvers->fetchAllByDistributionID($distribution->getID());
                $distribution_data["distribution_approvers"] = array();
                if ($approvers) {
                    foreach ($approvers as $approver) {
                        $data = $approver->toArray();
                        $data["approver_name"] = $approver->getApproverName();
                        $distribution_data["distribution_approvers"][] = $data;
                    }
                }

                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                if (isset($delegator) && $delegator) {
                    $distribution_data["delegator"] = $delegator->toArray();
                }
                $schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_data["adistribution_id"]);
                if (isset($schedule) && $schedule) {
                    $distribution_data["delivery_period"] = $schedule->getDeliveryPeriod();
                    $distribution_data["frequency"] = $schedule->getFrequency();
                    $distribution_data["period_offset_days"] = round(($schedule->getPeriodOffset() / 86400));
                    $schedule = Models_Schedule::fetchRowByID($schedule->getScheduleID());
                    $distribution_data["schedule_id"] = $schedule->getID();
                    $distribution_data["schedule_label"] = $schedule->getTitle();
                }

                $eventtype_model = new Models_Assessments_Distribution_Eventtype();
                $eventtypes = $eventtype_model->fetchEventTypes($distribution_data["adistribution_id"]);
                if ($eventtypes) {
                    $distribution_data["eventtypes"] = array();
                    foreach ($eventtypes as $eventtype) {
                        $distribution_data["eventtypes"][] = array("target_id" => $eventtype["eventtype_id"], "target_name" => $eventtype["eventtype_title"]);
                    }
                }

                $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($distribution->getID());
                $distribution_data["distribution_results_user"] = array();
                if ($reviewers) {
                    foreach ($reviewers as $reviewer) {
                        $reviewer_array = $reviewer->toArray();
                        $reviewer_array["reviewer_name"] = $reviewer->getReviewerName();
                        $distribution_data["distribution_results_user"][] = $reviewer_array;
                    }
                }

                if (!empty($distribution_data)) {
                    $distribution_data = $controller->loadRecordAsValidatedData($distribution_data);
                    if ($distribution_data["distribution_method"] == "date_range" && is_null($distribution_data["delivery_date"])) {
                        if (isset($distribution_data["range_start_date"])) {
                            $distribution->setDeliveryDate($distribution_data["range_start_date"]);
                            if ($distribution->update()) {
                                $ctr++;
                            }
                        }
                    }
                } else {
                    echo "\nThere was a problem trying to fetch the distribution data for {$distribution->getID()}.";
                }
            } else {
                echo "\nThere was a problem trying to fetch the distribution with the ID provided for {$distribution->getID()}.";
            }
        } else {
            echo $ERRORSTR;
        }
	}
	echo $ctr . " date range distributions successfully updated.";
}

