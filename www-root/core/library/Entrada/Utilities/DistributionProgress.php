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
 * This class contains the logic for determining distribution progress,
 * meaning a list of all assessment tasks, regardless of whether or not
 * the tasks delivery date has past. The logic follows the QueueAssessments
 * utility that is run by the queue-distribution-assessments CRON job. The
 * primary difference between the two is that this class looks to create
 * an array of details for all tasks, whereas QueueAssessments is only
 * concerned with adding tasks to the database if their delivery date
 * has past.
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_DistributionProgress extends Entrada_Utilities_Assessments_Base {

    /**
     * @param $adistribution_id
     * @param null $date_range_start
     * @param null $date_range_end
     * @return array
     */
    public function getDistributionProgress($adistribution_id, $date_range_start = null, $date_range_end = null) {
        global $ENTRADA_TEMPLATE, $db;
        $target_details = array();

        $distribution = Models_Assessments_Distribution::fetchRowByID($adistribution_id);
        if ($distribution) {

            $ENTRADA_TEMPLATE->setActiveTemplate($distribution->getOrganisationID());

            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
            if ($distribution_schedule) {
                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());

                switch ($distribution_schedule->getScheduleType()) {
                    case "block" :
                        if ($schedule) {
                            $blocks = array();
                            if ($schedule->getScheduleType() == "rotation_stream") {
                                $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                            } else if ($schedule->getScheduleType() == "rotation_block") {
                                $blocks[] = $schedule;
                            }

                            $assessors = $distribution->getAssessors(null, false, false, true);
                            $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
                            foreach ($blocks as $block) {
                                $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $block->getStartDate(), $block->getEndDate());
                                if ($release_date <= $delivery_date) {
                                    if ($assessors) {
                                        $distribution_target_records = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                                        $learner_blocks = $this->fetchLearnerBlocks($block->getID());
                                        if ($learner_blocks) {
                                            foreach ($assessors as $assessor) {
                                                switch ($distribution->getAssessorOption()) {

                                                    case "learner":
                                                        foreach ($distribution_target_records as $distribution_target_record) {
                                                            switch ($distribution_target_record->getTargetType()) {
                                                                case "schedule_id":
                                                                    switch ($distribution_target_record->getTargetScope()) {
                                                                        case "self" :
                                                                            foreach ($learner_blocks as $learner_block) {
                                                                                if ($learner_block["audience_value"] == $assessor["assessor_value"]) {
                                                                                    if ($distribution_target_records) {
                                                                                        $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                                        if ($block_rotations) {
                                                                                            $schedule_array = array(
                                                                                                "parent_schedule" => $schedule->getID(),
                                                                                                "child_schedules" => array()
                                                                                            );
                                                                                            foreach ($block_rotations as $block_rotation) {
                                                                                                if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                                    $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                                                }
                                                                                                $tmp_target = array(
                                                                                                    "target_value" => $distribution_target_record->getTargetId(),
                                                                                                    "target_type" => "schedule_id",
                                                                                                    "assessor_value" => $assessor["assessor_value"],
                                                                                                    "unique_to_assessor" => true,
                                                                                                    "start_date" => false,
                                                                                                    "end_date" => $delivery_date,
                                                                                                    "delivery_date" => $delivery_date,
                                                                                                    "associated_schedules" => $schedule_array
                                                                                                );
                                                                                                $target_details[] = $tmp_target;
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                            break;
                                                                        default:
                                                                            foreach ($learner_blocks as $learner_block) {
                                                                                if ($learner_block["audience_value"] == $assessor["assessor_value"]) {
                                                                                    if ($distribution_target_records) {
                                                                                        foreach ($distribution_target_records as $distribution_target_record) {
                                                                                            $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                                            if ($block_rotations) {
                                                                                                $schedule_array = array(
                                                                                                    "parent_schedule" => $schedule->getID(),
                                                                                                    "child_schedules" => array()
                                                                                                );
                                                                                                foreach ($block_rotations as $block_rotation) {
                                                                                                    if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                                        $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                                                    }
                                                                                                    $tmp_target = array(
                                                                                                        "target_value" => $block_rotation["audience_value"],
                                                                                                        "target_type" => "proxy_id",
                                                                                                        "unique_to_assessor" => false,
                                                                                                        "start_date" => false,
                                                                                                        "end_date" => $delivery_date,
                                                                                                        "delivery_date" => $delivery_date,
                                                                                                        "associated_schedules" => $schedule_array
                                                                                                    );
                                                                                                    $target_details[] = $tmp_target;
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                            break;
                                                                    }
                                                                    break;
                                                                case "proxy_id":
                                                                    foreach ($learner_blocks as $learner_block) {
                                                                        if ($learner_block["audience_value"] == $assessor["assessor_value"]) {
                                                                            $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                            if ($block_rotations) {
                                                                                $schedule_array = array(
                                                                                    "parent_schedule" => $schedule->getID(),
                                                                                    "child_schedules" => array()
                                                                                );
                                                                                foreach ($block_rotations as $block_rotation) {
                                                                                    if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                        $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                                    }
                                                                                    $tmp_target = array(
                                                                                        "target_value" => $distribution_target_record->getTargetId(),
                                                                                        "target_type" => "proxy_id",
                                                                                        "assessor_value" => $assessor["assessor_value"],
                                                                                        "unique_to_assessor" => true,
                                                                                        "start_date" => false,
                                                                                        "end_date" => $delivery_date,
                                                                                        "delivery_date" => $delivery_date,
                                                                                        "associated_schedules" => $schedule_array
                                                                                    );
                                                                                    $target_details[] = $tmp_target;
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                    break;

                                                                case "self":
                                                                    foreach ($learner_blocks as $learner_block) {
                                                                        if ($learner_block["audience_value"] == $assessor["assessor_value"]) {
                                                                            $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                            if ($block_rotations) {
                                                                                $schedule_array = array(
                                                                                    "parent_schedule" => $schedule->getID(),
                                                                                    "child_schedules" => array()
                                                                                );
                                                                                foreach ($block_rotations as $block_rotation) {
                                                                                    if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                        $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                                    }
                                                                                    $tmp_target = array(
                                                                                        "target_value" => $assessor["assessor_value"],
                                                                                        "target_type" => "proxy_id",
                                                                                        "assessor_value" => $assessor["assessor_value"],
                                                                                        "unique_to_assessor" => true,
                                                                                        "start_date" => false,
                                                                                        "end_date" => $delivery_date,
                                                                                        "delivery_date" => $delivery_date,
                                                                                        "associated_schedules" => $schedule_array
                                                                                    );
                                                                                    $target_details[] = $tmp_target;
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                    break;

                                                                default:
                                                                    break;
                                                            }
                                                        }
                                                        break;

                                                    case "individual_users":
                                                    case "faculty":
                                                        foreach ($distribution_target_records as $distribution_target_record) {
                                                            switch ($distribution_target_record->getTargetType()) {

                                                                case "schedule_id":
                                                                    switch ($distribution_target_record->getTargetScope()) {
                                                                        case "internal_learners" :
                                                                        case "external_learners" :
                                                                        case "all_learners" :
                                                                            $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                            if ($block_rotations) {
                                                                                $schedule_array = array(
                                                                                    "parent_schedule" => $schedule->getID(),
                                                                                    "child_schedules" => array()
                                                                                );
                                                                                foreach ($block_rotations as $block_rotation) {
                                                                                    if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                        $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                                    }
                                                                                    $tmp_target = array(
                                                                                        "target_value" => $block_rotation["audience_value"],
                                                                                        "target_type" => "proxy_id",
                                                                                        "unique_to_assessor" => false,
                                                                                        "start_date" => false,
                                                                                        "end_date" => $delivery_date,
                                                                                        "delivery_date" => $delivery_date,
                                                                                        "associated_schedules" => $schedule_array
                                                                                    );
                                                                                    $target_details[] = $tmp_target;
                                                                                }
                                                                            }
                                                                            break;
                                                                        case "self" :
                                                                            $schedule_array = array(
                                                                                "parent_schedule" => $schedule->getID(),
                                                                            );
                                                                            $tmp_target = array(
                                                                                "target_value" => $schedule->getID(),
                                                                                "target_type" => "schedule_id",
                                                                                "unique_to_assessor" => false,
                                                                                "start_date" => false,
                                                                                "end_date" => $delivery_date,
                                                                                "delivery_date" => $delivery_date,
                                                                                "associated_schedules" => $schedule_array
                                                                            );
                                                                            $target_details[] = $tmp_target;
                                                                            break;
                                                                    }
                                                                    break;

                                                                case "proxy_id":
                                                                    $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                    if ($block_rotations) {
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID(),
                                                                            "child_schedules" => array()
                                                                        );
                                                                        foreach ($block_rotations as $block_rotation) {
                                                                            // TODO figure out if/when this if statement is needed
                                                                            //if ($block_rotation["audience_value"] == $distribution_target_record->getTargetId()) {
                                                                            if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                            }
                                                                            $tmp_target = array(
                                                                                "target_value" => $distribution_target_record->getTargetId(),
                                                                                "target_type" => "proxy_id",
                                                                                "unique_to_assessor" => false,
                                                                                "start_date" => false,
                                                                                "end_date" => $delivery_date,
                                                                                "delivery_date" => $delivery_date,
                                                                                "associated_schedules" => $schedule_array
                                                                            );
                                                                            $target_details[] = $tmp_target;
                                                                            //}
                                                                        }
                                                                    }
                                                                    break;

                                                                case "self":
                                                                    $block_rotations = $this->fetchBlockRotations($block->getID(), $distribution_target_record->getTargetScope());
                                                                    if ($block_rotations) {
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID(),
                                                                            "child_schedules" => array()
                                                                        );
                                                                        foreach ($block_rotations as $block_rotation) {
                                                                            // TODO figure out if/when this if statement is needed
                                                                            //if ($block_rotation["audience_value"] == $distribution_target_record->getTargetId()) {
                                                                            if (!in_array($block_rotation["schedule_id"], $schedule_array["child_schedules"])) {
                                                                                $schedule_array["child_schedules"][] = $block_rotation["schedule_id"];
                                                                            }
                                                                            $tmp_target = array(
                                                                                "target_value" => $assessor["assessor_value"],
                                                                                "target_type" => "proxy_id",
                                                                                "assessor_value" => $assessor["assessor_value"],
                                                                                "unique_to_assessor" => true,
                                                                                "start_date" => false,
                                                                                "end_date" => $delivery_date,
                                                                                "delivery_date" => $delivery_date,
                                                                                "associated_schedules" => $schedule_array
                                                                            );
                                                                            $target_details[] = $tmp_target;
                                                                            //}
                                                                        }
                                                                    }
                                                                    break;

                                                                default:
                                                                    break;
                                                            }
                                                        }
                                                        break;

                                                    default:
                                                        break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;

                    case "rotation" :
                        if ($schedule) {

                            $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
                            $assessors = $distribution->getAssessors(null, false, false, true);
                            $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());

                            if ($distribution_targets) {
                                foreach ($distribution_targets as $distribution_target) {
                                    switch ($distribution->getAssessorOption()) {

                                        case "learner":
                                            $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                                            if ($rotations) {
                                                $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                                if ($rotation_dates["unique_rotation_dates"]) {
                                                    foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {

                                                        $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                                                        switch ($distribution_target->getTargetType()) {

                                                            case "schedule_id":
                                                                switch ($distribution_target->getTargetScope()) {
                                                                    case "internal_learners" :
                                                                    case "external_learners" :
                                                                    case "all_learners" :
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID(),
                                                                        );
                                                                        $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                                                                        if ($release_date <= $delivery_date) {
                                                                            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                                                                                foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                                                                                    if ($unique_rotation_date[0] == $user_rotation_date[0] && $unique_rotation_date[1] == $user_rotation_date[1]) {
                                                                                        $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                        if ($child_schedules) {
                                                                                            $schedule_array["child_schedules"] = array();
                                                                                            foreach ($child_schedules as $child_schedule) {
                                                                                                if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                    $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                }

                                                                                            }
                                                                                        }
                                                                                        $tmp_target = array(
                                                                                            "target_value" => $proxy_id,
                                                                                            "target_type" => "proxy_id",
                                                                                            "unique_to_assessor" => false,
                                                                                            "delivery_date" => $delivery_date,
                                                                                            "start_date" => $unique_rotation_date[0],
                                                                                            "end_date" => $unique_rotation_date[1],
                                                                                            "associated_schedules" => $schedule_array
                                                                                        );
                                                                                        $target_details[] = $tmp_target;
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                        break;
                                                                    case "self":
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID(),
                                                                        );
                                                                        foreach ($assessors as $assessor) {
                                                                            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                                                                                if ($assessor["assessor_value"] == $proxy_id) {
                                                                                    foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                                                                                        if ($unique_rotation_date[0] == $user_rotation_date[0] && $unique_rotation_date[1] == $user_rotation_date[1]) {
                                                                                            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                            if ($release_date <= $delivery_date) {
                                                                                                $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                                if ($child_schedules) {
                                                                                                    $schedule_array["child_schedules"] = array();
                                                                                                    foreach ($child_schedules as $child_schedule) {
                                                                                                        if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                            $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                                $tmp_target = array(
                                                                                                    "target_value" => $schedule->getID(),
                                                                                                    "target_type" => "schedule_id",
                                                                                                    "unique_to_assessor" => true,
                                                                                                    "assessor_value" => $assessor["assessor_value"],
                                                                                                    "start_date" => $user_rotation_date[0],
                                                                                                    "end_date" => $user_rotation_date[1],
                                                                                                    "delivery_date" => $delivery_date,
                                                                                                    "associated_schedules" => $schedule_array
                                                                                                );
                                                                                                $target_details[] = $tmp_target;
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                        break;
                                                                }
                                                                break;

                                                            case "proxy_id":
                                                                switch ($distribution_target->getTargetRole()) {
                                                                    // When learners assess learners, they will receive a task for each target if the were on rotation.
                                                                    case "learner":
                                                                        if ($release_date <= $delivery_date) {
                                                                            $schedule_array = array(
                                                                                "parent_schedule" => $schedule->getID()
                                                                            );
                                                                            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                                                                                if ($distribution_target->getTargetId() == $proxy_id) {
                                                                                    foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                                                                                        if ($unique_rotation_date[0] == $user_rotation_date[0] && $unique_rotation_date[1] == $user_rotation_date[1]) {
                                                                                            $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                            if ($child_schedules) {
                                                                                                $schedule_array["child_schedules"] = array();
                                                                                                foreach ($child_schedules as $child_schedule) {
                                                                                                    if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                        $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                            $tmp_target = array(
                                                                                                "target_value" => $proxy_id,
                                                                                                "target_type" => "proxy_id",
                                                                                                "unique_to_assessor" => false,
                                                                                                "start_date" => $unique_rotation_date[0],
                                                                                                "end_date" => $unique_rotation_date[1],
                                                                                                "delivery_date" => $delivery_date,
                                                                                                "associated_schedules" => $schedule_array
                                                                                            );
                                                                                            $target_details[] = $tmp_target;
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                        break;
                                                                    // When learners assess faculty, they will receive a task for each target if the were on rotation.
                                                                    case "faculty":

                                                                        $assessors = $distribution->getAssessors(null, false, false, true);
                                                                        $distribution_assessor_records = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getID());
                                                                        if ($distribution_assessor_records) {
                                                                            $schedule_array = array(
                                                                                "parent_schedule" => $schedule->getID()
                                                                            );
                                                                            foreach ($distribution_assessor_records as $distribution_assessor_record) {
                                                                                if ($distribution_assessor_record->getAssessorType() == "schedule_id") {
                                                                                    // Schedule based assessors must be fetched manually.
                                                                                    //$assessors = $this->getScheduleAssessors($distribution->getID(), $distribution_assessor_record);
                                                                                    $rotations = $this->fetchRotations($schedule->getID(), $distribution_assessor_record->getAssessorScope());
                                                                                    if ($rotations) {
                                                                                        $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                                                                        if ($rotation_dates["all_rotation_dates"]) {
                                                                                            foreach ($assessors as $distribution_assessor) {
                                                                                                foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $assessor_rotation_dates) {
                                                                                                    if ($distribution_assessor["assessor_value"] == $proxy_id) {
                                                                                                        foreach ($assessor_rotation_dates as $assessor_rotation_date) {
                                                                                                            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $assessor_rotation_date[0], $assessor_rotation_date[1]);
                                                                                                            if ($release_date <= $delivery_date) {
                                                                                                                $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $assessor_rotation_date[0], $assessor_rotation_date[1]);
                                                                                                                if ($child_schedules) {
                                                                                                                    $schedule_array["child_schedules"] = array();
                                                                                                                    foreach ($child_schedules as $child_schedule) {
                                                                                                                        if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                                            $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                                $tmp_target = array(
                                                                                                                    "target_value" => $distribution_target->getTargetId(),
                                                                                                                    "target_type" => "proxy_id",
                                                                                                                    "unique_to_assessor" => true,
                                                                                                                    "assessor_value" => $proxy_id,
                                                                                                                    "delivery_date" => $delivery_date,
                                                                                                                    "start_date" => $assessor_rotation_date[0],
                                                                                                                    "end_date" => $assessor_rotation_date[1],
                                                                                                                    "associated_schedules" => $schedule_array
                                                                                                                );
                                                                                                                $target_details[] = $tmp_target;
                                                                                                            }
                                                                                                        }

                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                        break;
                                                                }
                                                                break;

                                                            case "self":
                                                                foreach ($assessors as $assessor) {
                                                                    if ($release_date <= $delivery_date) {
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID()
                                                                        );
                                                                        foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                                                                            if ($assessor["assessor_value"] == $proxy_id) {
                                                                                foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                                                                                    if ($unique_rotation_date[0] == $user_rotation_date[0] && $unique_rotation_date[1] == $user_rotation_date[1]) {
                                                                                        $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                        if ($child_schedules) {
                                                                                            $schedule_array["child_schedules"] = array();
                                                                                            foreach ($child_schedules as $child_schedule) {
                                                                                                if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                    $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                        $tmp_target = array(
                                                                                            "target_value" => $proxy_id,
                                                                                            "target_type" => "proxy_id",
                                                                                            "assessor_value" => $assessor["assessor_value"],
                                                                                            "unique_to_assessor" => true,
                                                                                            "delivery_date" => $delivery_date,
                                                                                            "start_date" => $unique_rotation_date[0],
                                                                                            "end_date" => $unique_rotation_date[1],
                                                                                            "associated_schedules" => $schedule_array
                                                                                        );
                                                                                        $target_details[] = $tmp_target;
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                break;

                                                            default:
                                                                break;
                                                        }
                                                    }
                                                }
                                            }
                                            break;

                                        case "individual_users":
                                        case "faculty":
                                            $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                                            $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                            if ($rotation_dates["unique_rotation_dates"]) {

                                                switch ($distribution_target->getTargetType()) {

                                                    case "schedule_id":
                                                        switch ($distribution_target->getTargetScope()) {
                                                            case "internal_learners" :
                                                            case "external_learners" :
                                                            case "all_learners" :
                                                                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                                                                if ($rotations) {
                                                                    $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                                                    if ($rotation_dates["unique_rotation_dates"]) {
                                                                        $schedule_array = array(
                                                                            "parent_schedule" => $schedule->getID()
                                                                        );
                                                                        foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                                                                            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                                                                            if ($release_date <= $delivery_date) {
                                                                                foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                                                                                    foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                                                                                        if ($unique_rotation_date[0] == $user_rotation_date[0] && $unique_rotation_date[1] == $user_rotation_date[1]) {
                                                                                            $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $user_rotation_date[0], $user_rotation_date[1]);
                                                                                            if ($child_schedules) {
                                                                                                $schedule_array["child_schedules"] = array();
                                                                                                foreach ($child_schedules as $child_schedule) {
                                                                                                    if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                        $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                    }
                                                                                                }
                                                                                                $tmp_target = array(
                                                                                                    "target_value" => $proxy_id,
                                                                                                    "target_type" => "proxy_id",
                                                                                                    "unique_to_assessor" => false,
                                                                                                    "start_date" => $unique_rotation_date[0],
                                                                                                    "end_date" => $unique_rotation_date[1],
                                                                                                    "delivery_date" => $delivery_date,
                                                                                                    "associated_schedules" => $schedule_array
                                                                                                );
                                                                                                $target_details[] = $tmp_target;
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                break;

                                                            case "self":
                                                                if ($rotations) {
                                                                    $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                                                    if ($rotation_dates["all_rotation_dates"]) {
                                                                        foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $assessor_rotation_dates) {
                                                                            foreach ($assessor_rotation_dates as $assessor_rotation_date) {
                                                                                $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $assessor_rotation_date[0], $assessor_rotation_date[1]);
                                                                                if ($release_date <= $delivery_date) {
                                                                                    $schedule_array = array(
                                                                                        "parent_schedule" => $schedule->getID(),
                                                                                    );
                                                                                    $tmp_target = array(
                                                                                        "target_value" => $schedule->getID(),
                                                                                        "target_type" => "schedule_id",
                                                                                        "unique_to_assessor" => false,
                                                                                        "delivery_date" => $delivery_date,
                                                                                        "start_date" => $assessor_rotation_date[0],
                                                                                        "end_date" => $assessor_rotation_date[1],
                                                                                        "associated_schedules" => $schedule_array
                                                                                    );
                                                                                    $target_details[] = $tmp_target;
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                break;
                                                        }
                                                        break;

                                                    // TODO this case is not handled properly by the CRON job as is, this will need to be adjusted later.
                                                    case "proxy_id":
                                                        switch ($distribution_target->getTargetRole()) {
                                                            case "learner":
                                                                if (!$distribution->getStartDate()) {
                                                                    $delivery_start_date = $distribution->getReleaseStartDate();
                                                                    $delivery_end_date = strtotime("+1 year", $distribution->getReleaseStartDate());
                                                                } else {
                                                                    $delivery_start_date = $distribution->getStartDate();
                                                                    $delivery_end_date = strtotime("+1 year", $distribution->getStartDate());
                                                                }
                                                                $schedule_array = array(
                                                                    "parent_schedule" => $schedule->getID()
                                                                );
                                                                if ($release_date <= $delivery_start_date) {
                                                                    foreach ($assessors as $assessor) {
                                                                        $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $delivery_start_date, $delivery_end_date);
                                                                        if ($child_schedules) {
                                                                            $schedule_array["child_schedules"] = array();
                                                                            foreach ($child_schedules as $child_schedule) {
                                                                                if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                    $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                }
                                                                            }
                                                                        }
                                                                        $tmp_target = array(
                                                                            "target_value" => $distribution_target->getTargetId(),
                                                                            "target_type" => "proxy_id",
                                                                            "unique_to_assessor" => false,
                                                                            "delivery_date" => $delivery_start_date,
                                                                            "start_date" => $delivery_start_date,
                                                                            "end_date" => $delivery_end_date,
                                                                            "associated_schedules" => $schedule_array
                                                                        );
                                                                        $target_details[] = $tmp_target;
                                                                    }
                                                                }
                                                                break;

                                                            case "faculty":
                                                                if (!$distribution->getStartDate()) {
                                                                    $delivery_start_date = $distribution->getReleaseStartDate();
                                                                    $delivery_end_date = strtotime("+1 year", $distribution->getReleaseStartDate());
                                                                } else {
                                                                    $delivery_start_date = $distribution->getStartDate();
                                                                    $delivery_end_date = strtotime("+1 year", $distribution->getStartDate());
                                                                }
                                                                if ($release_date <= $delivery_start_date) {
                                                                    $schedule_array = array(
                                                                        "parent_schedule" => $schedule->getID()
                                                                    );
                                                                    $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $delivery_start_date, $delivery_end_date);
                                                                    if ($child_schedules) {
                                                                        $schedule_array["child_schedules"] = array();
                                                                        foreach ($child_schedules as $child_schedule) {
                                                                            if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                            }
                                                                        }
                                                                    }
                                                                    $tmp_target = array(
                                                                        "target_value" => $distribution_target->getTargetId(),
                                                                        "target_type" => "proxy_id",
                                                                        "unique_to_assessor" => false,
                                                                        "delivery_date" => $delivery_start_date,
                                                                        "start_date" => $delivery_start_date,
                                                                        "end_date" => $delivery_end_date,
                                                                        "associated_schedules" => $schedule_array
                                                                    );
                                                                    $target_details[] = $tmp_target;
                                                                }
                                                                break;
                                                        }
                                                        break;

                                                    // TODO this case is not handled properly by the CRON job as is, this will need to be adjusted later.
                                                    case "self":
                                                        if (!$distribution->getStartDate()) {
                                                            $delivery_start_date = $distribution->getReleaseStartDate();
                                                            $delivery_end_date = strtotime("+1 year", $distribution->getReleaseStartDate());
                                                        } else {
                                                            $delivery_start_date = $distribution->getStartDate();
                                                            $delivery_end_date = strtotime("+1 year", $distribution->getStartDate());
                                                        }
                                                        $schedule_array = array(
                                                            "parent_schedule" => $schedule->getID()
                                                        );
                                                        if ($release_date <= $delivery_start_date) {
                                                            foreach ($assessors as $assessor) {
                                                                $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $delivery_start_date, $delivery_end_date);
                                                                if ($child_schedules) {
                                                                    $schedule_array["child_schedules"] = array();
                                                                    foreach ($child_schedules as $child_schedule) {
                                                                        if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                            $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                        }
                                                                    }
                                                                }
                                                                $tmp_target = array(
                                                                    "target_value" => $assessor["assessor_value"],
                                                                    "target_type" => "proxy_id",
                                                                    "unique_to_assessor" => true,
                                                                    "assessor_value" => $assessor["assessor_value"],
                                                                    "delivery_date" => $delivery_start_date,
                                                                    "start_date" => $delivery_start_date,
                                                                    "end_date" => $delivery_end_date,
                                                                    "associated_schedules" => $schedule_array
                                                                );
                                                                $target_details[] = $tmp_target;
                                                            }
                                                        }
                                                        break;
                                                }
                                            }
                                            break;

                                        default:
                                            break;
                                    }
                                }
                            }
                        }

                        break;

                    // TODO Repeat is not implemented yet. It needs to take into account the target types like the block/rotations above. Right now it is mostly a copy/paste of the QueueAssessment logic.
                    case "repeat" :
                        if ($schedule) {
                            $assessors = $distribution->getAssessors(null, false, false, true);
                            $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
                            $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());

                            if ($distribution_targets) {
                                foreach ($distribution_targets as $distribution_target) {
                                    if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {
                                        $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                                        if ($rotations) {
                                            $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                            if ($rotation_dates["unique_rotation_dates"]) {
                                                foreach ($rotation_dates["unique_rotation_dates"] as $proxy_id => $unique_rotation_date) {
                                                    $delivery_date = $this->calculateDateByFrequency($distribution_schedule->getFrequency(), $unique_rotation_date[0]);
                                                    if ($release_date <= $delivery_date) {
                                                        while ($delivery_date <= time() && $delivery_date <= $unique_rotation_date[1]) {
                                                            if ($assessors) {
                                                                $schedule_array = array(
                                                                    "parent_schedule" => $schedule->getID()
                                                                );
                                                                $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $delivery_date, $unique_rotation_date[1]);
                                                                if ($child_schedules) {
                                                                    $schedule_array["child_schedules"] = array();
                                                                    foreach ($child_schedules as $child_schedule) {
                                                                        if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                            $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                        }
                                                                    }
                                                                }
                                                                $tmp_target = array(
                                                                    "target_value" => $proxy_id,
                                                                    "target_type" => "proxy_id",
                                                                    "unique_to_assessor" => false,
                                                                    "delivery_date" => $delivery_date,
                                                                    "start_date" => $unique_rotation_date[0],
                                                                    "end_date" => $unique_rotation_date[1],
                                                                    "associated_schedules" => $schedule_array
                                                                );
                                                                $target_details[] = $tmp_target;
                                                            }
                                                            $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($assessors) {
                                            $distribution_assessor_records = $distribution->getAssessors(null, false, false, true);
                                            if ($distribution_assessor_records) {
                                                foreach ($distribution_assessor_records as $distribution_assessor_record) {
                                                    if ($distribution_assessor_record["assessor_type"] == "schedule_id") {
                                                        $rotations = $this->fetchRotations($schedule->getID(), $distribution_assessor_record->getAssessorScope());
                                                        if ($rotations) {
                                                            $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                                            if ($rotation_dates["all_rotation_dates"]) {
                                                                foreach ($assessors as $assessor) {
                                                                    foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $assessor_rotation_dates) {
                                                                        if ($assessor["assessor_value"] == $proxy_id) {
                                                                            foreach ($assessor_rotation_dates as $assessor_rotation_date) {
                                                                                $delivery_date = $this->calculateDateByFrequency($distribution_schedule->getFrequency(), $assessor_rotation_date[0]);
                                                                                if ($release_date <= $delivery_date) {
                                                                                    $schedule_array = array(
                                                                                        "parent_schedule" => $schedule->getID()
                                                                                    );
                                                                                    while ($delivery_date <= time() && $delivery_date <= $assessor_rotation_date[1]) {
                                                                                        $child_schedules = Models_Schedule::fetchAllByParentAndDateRange($distribution->getOrganisationID(), $schedule->getID(), $delivery_date, $assessor_rotation_date[1]);
                                                                                        if ($child_schedules) {
                                                                                            $schedule_array["child_schedules"] = array();
                                                                                            foreach ($child_schedules as $child_schedule) {
                                                                                                if (!in_array($child_schedule->getID(), $schedule_array["child_schedules"])) {
                                                                                                    $schedule_array["child_schedules"][] = $child_schedule->getID();
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                        $tmp_target = array(
                                                                                            "target_value" => $proxy_id,
                                                                                            "target_type" => "proxy_id",
                                                                                            "unique_to_assessor" => false,
                                                                                            "delivery_date" => $delivery_date,
                                                                                            "start_date" => $assessor_rotation_date[0],
                                                                                            "end_date" => $assessor_rotation_date[1],
                                                                                            "associated_schedules" => $schedule_array
                                                                                        );
                                                                                        $target_details[] = $tmp_target;
                                                                                        $target_details[$tmp_target["target_value"]][$tmp_target["delivery_date"]] = $tmp_target;
                                                                                        $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            } else {
                // Date range logic here

                $assessors = $distribution->getAssessors(null, false, false, true);
                if ($assessors) {
                    $delivery_date = $distribution->getDeliveryDate();
                    $distribution_target_records = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                    if ($distribution_target_records) {
                        foreach ($assessors as $assessor) {
                            foreach ($distribution_target_records as $distribution_target_record) {
                                switch ($distribution_target_record->getTargetType()) {
                                    case "proxy_id":
                                        $tmp_target = array(
                                            "target_value" => $distribution_target_record->getTargetId(),
                                            "target_type" => "proxy_id",
                                            "unique_to_assessor" => false,
                                            "delivery_date" => $delivery_date,
                                            "start_date" => false,
                                            "end_date" => false,
                                            "associated_schedules" => false
                                        );
                                        $target_details[] = $tmp_target;
                                        break;
                                    case "self":
                                        $tmp_target = array(
                                            "target_value" => $assessor["assessor_value"],
                                            "target_type" => "proxy_id",
                                            "assessor_value" => $assessor["assessor_value"],
                                            "unique_to_assessor" => true,
                                            "delivery_date" => $delivery_date,
                                            "start_date" => false,
                                            "end_date" => false,
                                            "associated_schedules" => false
                                        );
                                        $target_details[] = $tmp_target;
                                        break;
                                    case "group_id":
                                        $targets = Models_Group_Member::getAssessmentGroupMembers($distribution->getOrganisationID(), $distribution_target_record->getTargetId());
                                        if ($targets) {
                                            foreach ($targets as $target) {
                                                $tmp_target = array(
                                                    "target_value" => $target["proxy_id"],
                                                    "target_type" => "proxy_id",
                                                    "unique_to_assessor" => false,
                                                    "delivery_date" => $delivery_date,
                                                    "start_date" => false,
                                                    "end_date" => false,
                                                    "associated_schedules" => false
                                                );
                                                $target_details[] = $tmp_target;
                                            }
                                        }
                                        break;
                                    case "course_id":
                                        $course = Models_Course::fetchRowByID($distribution_target_record->getTargetId());
                                        if ($course) {
                                            switch ($distribution_target_record->getTargetScope()) {
                                                case "self" :
                                                    $tmp_target = array(
                                                        "target_value" => $course->getID(),
                                                        "target_type" => "course_id",
                                                        "unique_to_assessor" => false,
                                                        "delivery_date" => $delivery_date,
                                                        "start_date" => false,
                                                        "end_date" =>  false,
                                                        "associated_schedules" => false
                                                    );
                                                    $target_details[] = $tmp_target;
                                                    break;
                                                case "faculty" :
                                                    $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number`, c.`group`, c.`role` FROM `course_contacts` AS a
                                                            JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                                            ON a.`proxy_id` = b.`id`
                                                            JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                                                            ON b.`id` = c.`user_id`
                                                            WHERE a.`course_id` = ?
                                                            AND c.`account_active` = 'true'
                                                            AND (c.`access_starts` = '0' OR c.`access_starts` <= ?)
                                                            AND (c.`access_expires` = '0' OR c.`access_expires` > ?)
                                                            AND c.`organisation_id` = ?";

                                                    $results = $db->GetAll($query, array($course->getID(), time(), time(), $distribution->getOrganisationID()));
                                                    if ($results) {
                                                        foreach ($results as $target) {
                                                            $tmp_target = array(
                                                                "target_value" => $target["proxy_id"],
                                                                "target_type" => "proxy_id",
                                                                "unique_to_assessor" => false,
                                                                "delivery_date" => $delivery_date,
                                                                "start_date" => false,
                                                                "end_date" => false,
                                                                "associated_schedules" => false
                                                            );
                                                            $target_details[] = $tmp_target;

                                                        }
                                                    }
                                                    break;
                                                case "internal_learners" :
                                                case "all_learners":
                                                    $targets = $course->getAllMembers($distribution->getCPeriodID());
                                                    if ($targets) {
                                                        foreach ($targets as $target) {
                                                            $tmp_target = array(
                                                                "target_value" => $target->getID(),
                                                                "target_type" => "proxy_id",
                                                                "unique_to_assessor" => false,
                                                                "delivery_date" => $delivery_date,
                                                                "start_date" => false,
                                                                "end_date" => false,
                                                                "associated_schedules" => false
                                                            );
                                                            $target_details[] = $tmp_target;
                                                        }
                                                    }
                                                    break;
                                                case "external_learners":
                                                    break;
                                            }
                                        }
                                        break;

                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->buildProgressDetails($distribution->getID(), $target_details, $date_range_start, $date_range_end);
    }

    public function buildProgressDetails($distribution_id, $targets, $date_range_start = false, $date_range_end = false) {

        $details = array(
            "pending" => array("internal" => array(), "external" => array()),
            "inprogress" => array("internal" => array(), "external" => array()),
            "complete" => array("internal" => array(), "external" => array())
        );
        $details["distribution_target_type"] = false;
        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_id);

        $assessors = $distribution->getAssessors(null, false, false, true);
        if ($assessors && $targets) {
            foreach ($assessors as $assessor) {

                $assessor_name = "N/A";
                $assessor_email = false;

                if ($assessor["assessor_type"] == "external") {
                    $internal_external = "external";
                    $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor["assessor_value"]);
                    $assessor["proxy_id"] = $assessor["assessor_value"];
                    if ($external_assessor) {
                        $assessor_name = $external_assessor->getFirstname() . " " . $external_assessor->getLastname();
                        $assessor_email = $external_assessor->getEmail();
                    }
                } else {
                    $internal_external = "internal";
                    // TODO figure out how to handle different app IDs
                    $assessor_details = User::fetchRowByID($assessor["proxy_id"]);
                    if ($assessor_details) {
                        $assessor_name = $assessor_details->getPrefix() . " " . $assessor_details->getFirstname() . " " . $assessor_details->getLastname();
                        $assessor_email = $assessor_details->getEmail();
                    }
                }

                foreach ($targets as $target) {

                    // Each target will be added for each assessor unless the unique_to_assessor flag is is set to true. If it is,
                    // the task should only be added for that specific assessor (similar to an additional task as seen below this loop).
                    if ($target["unique_to_assessor"] == false || ($target["assessor_value"] && $target["assessor_value"] == $assessor["assessor_value"])) {

                        $target_group = false;
                        $active = true;
                        $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution->getID(), $assessor["assessor_type"], $assessor["proxy_id"], $target["target_value"], $target["delivery_date"]);
                        if ($deleted_task) {
                            $active = false;
                        }

                        // If a date range is set, restrict which targets are added to the details array.
                        if (((!$date_range_start && !$date_range_end) || ($target["delivery_date"] >= $date_range_start && $target["delivery_date"] <= $date_range_end)) && $active) {

                            // Attempt to fetch a matching assessment task.
                            if ($target["start_date"]) {
                                $distribution_assessment_record = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate($assessor["assessor_type"], $assessor["proxy_id"], $distribution->getID(), $target["start_date"], $target["end_date"]);
                            } elseif ($target["end_date"]) {
                                $distribution_assessment_record = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueEndDate($assessor["assessor_type"], $assessor["proxy_id"], $distribution->getID(), $target["end_date"]);
                            } elseif ($target["delivery_date"]) {
                                $distribution_assessment_record = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($distribution_id, $assessor["assessor_type"], $assessor["proxy_id"], $target["delivery_date"]);
                            }

                            if (!$distribution_assessment_record || $distribution_assessment_record->getDeletedDate() == null) {
                                if ($distribution_assessment_record) {
                                    $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($distribution_id, $internal_external, $assessor["proxy_id"], $target["target_value"], $distribution_assessment_record->getID());
                                    if ($progress) {
                                        $progress_status = $progress->getProgressValue();
                                    } else {
                                        $progress_status = "pending";
                                    }
                                } else {
                                    $progress_status = "pending";
                                }

                                // Name/title fetching logic.
                                switch ($target["target_type"]) {
                                    case "proxy_id":
                                        $member_details = Models_User::fetchRowByID($target["target_value"]);
                                        if ($member_details) {
                                            $prefix = $member_details->getPrefix();
                                            $target_name = (($prefix) ? $prefix . " " : "") . $member_details->getFirstname() . " " . $member_details->getLastname();

                                            // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                            $access = Models_User_Access::fetchAllByUserIDOrganisationID($member_details->getID(), $distribution->getOrganisationID());
                                            if ($access) {
                                                $faculty = false;
                                                foreach ($access as $group) {
                                                    if ($group->getGroup() == "faculty") {
                                                        $faculty = true;
                                                    }
                                                    $target_group = $group->getGroup();
                                                }
                                                if ($faculty) {
                                                    $target_group = "faculty";
                                                }
                                            }
                                        } else {
                                            $target_name = "N/A";
                                        }
                                        break;
                                    case "schedule_id":
                                        $schedule = Models_Schedule::fetchRowByID($target["target_value"]);
                                        $target_group = "schedule";
                                        if ($schedule) {
                                            $target_name = $schedule->getTitle();
                                        } else {
                                            $target_name = "N/A";
                                        }
                                        break;
                                    case "course_id":
                                        $course = Models_Course::fetchRowByID($target["target_value"]);
                                        $target_group = "course";
                                        if ($course) {
                                            $target_name = $course->getCourseName() . " (" . $course->getCourseCode() . ")";
                                        } else {
                                            $target_name = "N/A";
                                        }
                                        break;
                                    default:
                                        $target_name = "N/A";
                                        break;
                                }
                                $details["distribution_target_type"] = $target["target_type"];

                                // Build assessor
                                if (!array_key_exists($assessor["proxy_id"], $details[$progress_status][$assessor["assessor_type"]])) {
                                    $details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]] = array(
                                        "assessor_value" => $assessor["proxy_id"],
                                        "assessor_type" => $assessor["assessor_type"],
                                        "assessor_email" => $assessor_email,
                                        "assessor_name" => $assessor_name,
                                        "targets" => array()
                                    );
                                }

                                $parent_schedule = false;
                                $child_schedules = false;
                                if ($target["associated_schedules"]) {
                                    $parent_schedule = Models_Schedule::fetchRowByID($target["associated_schedules"]["parent_schedule"]);
                                    if (array_key_exists("child_schedules", $target["associated_schedules"])) {
                                        $child_schedules = array();
                                        foreach ($target["associated_schedules"]["child_schedules"] as $child_schedule_id) {
                                            $child_schedule = Models_Schedule::fetchRowByID($child_schedule_id);
                                            if ($child_schedule) {
                                                $child_schedules[] = $child_schedule->toArray();
                                            }
                                        }
                                    }
                                }

                                $duplicate = false;
                                // TODO is there a graceful way of indexing this array?
                                // Ensure the target was not added previously.
                                if (array_key_exists("targets", $details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]])) {
                                    foreach ($details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]]["targets"] as $previous_target) {
                                        if ($previous_target["target_id"] == $target["target_value"] &&
                                            $previous_target["target_type"] == $target["target_type"] &&
                                            $previous_target["delivery_date"] == $target["delivery_date"]
                                        ) {
                                            $duplicate = true;
                                        }
                                    }
                                }

                                if (!$duplicate) {
                                    // Add target to assessor.
                                    $details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]]["targets"][] = array(
                                        "target_name" => $target_name,
                                        "target_id" => $target["target_value"],
                                        "target_type" => $target["target_type"],
                                        "target_group" => $target_group,
                                        "dassessment_id" => ($distribution_assessment_record ? $distribution_assessment_record->getID() : false),
                                        "external_hash" => ($assessor["assessor_type"] == "external" && $distribution_assessment_record ? $distribution_assessment_record->getExternalHash() : false),
                                        "aprogress_id" => ((isset($progress) && $progress) ? $progress->getID() : false),
                                        "parent_schedule" => ($parent_schedule ? $parent_schedule->toArray() : false),
                                        "child_schedules" => ($child_schedules ? $child_schedules : false),
                                        "delivery_date" => $target["delivery_date"]
                                    );
                                }

                                // Sort targets by delivery date.
                                usort($details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]]["targets"], function ($a, $b) {
                                    return $a["delivery_date"] - $b["delivery_date"];
                                });

                                /*
                                // Sort assessors alphabetically.
                                usort($details[$progress_status][$assessor["assessor_type"]][$assessor["proxy_id"]], function ($a, $b) {
                                    return $a["assessor_name"] - $b["assessor_name"];
                                });
                                */

                            }
                        }
                    }
                }
            }
        }

        // Check for additional tasks added through post-creation distribution management.
        $additional_tasks = Models_Assessments_AdditionalTask::fetchAllByADistributionID($distribution->getID());
        if ($additional_tasks) {
            foreach ($additional_tasks as $task) {

                $active = true;
                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution->getID(), $task->getAssessorType(), $task->getAssessorValue(), $task->getTargetID(), $task->getDeliveryDate());
                if ($deleted_task) {
                    $active = false;
                }

                if (((!$date_range_start && !$date_range_end) || ($task->getDeliveryDate() >= $date_range_start && $task->getDeliveryDate() <= $date_range_end)) && $active) {

                    $assessor_name = "N/A";
                    $assessor_email = false;
                    $target_group = false;

                    if ($task->getAssessorType() == "external") {
                        $internal_external = "external";
                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($task->getAssessorValue());
                        if ($external_assessor) {
                            $assessor_name = $external_assessor->getFirstname() . " " . $external_assessor->getLastname();
                            $assessor_email = $external_assessor->getEmail();
                        }
                    } else {
                        $internal_external = "internal";
                        // TODO figure out how to handle different app IDs
                        $assessor_details = User::fetchRowByID($task->getAssessorValue());
                        if ($assessor_details) {
                            $assessor_name = $assessor_details->getPrefix() . " " . $assessor_details->getFirstname() . " " . $assessor_details->getLastname();
                            $assessor_email = $assessor_details->getEmail();
                        }
                    }

                    $member_details = Models_User::fetchRowByID($task->getTargetID());
                    if ($member_details) {
                        $prefix = $member_details->getPrefix();
                        $target_name = (($prefix) ? $prefix . " " : "") . $member_details->getFirstname() . " " . $member_details->getLastname();

                        $access = Models_User_Access::fetchAllByUserIDOrganisationID($member_details->getID(), $distribution->getOrganisationID());
                        if ($access) {
                            $faculty = false;
                            foreach ($access as $group) {
                                if ($group->getGroup() == "faculty") {
                                    $faculty = true;
                                }
                                $target_group = $group->getGroup();
                            }
                            if ($faculty) {
                                $target_group = "faculty";
                            }
                        }
                    } else {
                        $target_name = "N/A";
                    }

                    $distribution_assessment_record = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueEndDate($internal_external, $task->getAssessorValue(), $distribution->getID(), $task->getDeliveryDate());

                    if (!$distribution_assessment_record || $distribution_assessment_record->getDeletedDate() == null) {

                        if ($distribution_assessment_record) {
                            $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($distribution_id, $internal_external, $task->getAssessorValue(), $task->getTargetID(), $distribution_assessment_record->getID());
                            if ($progress) {
                                $progress_status = $progress->getProgressValue();
                            } else {
                                $progress_status = "pending";
                            }
                        } else {
                            $progress_status = "pending";
                        }

                        // Build additional assessor.
                        if (!array_key_exists($task->getAssessorValue(), $details[$progress_status][$task->getAssessorType()])) {
                            $details[$progress_status][$task->getAssessorType()][$task->getAssessorValue()] = array(
                                "assessor_value" => $task->getAssessorValue(),
                                "assessor_type" => $internal_external,
                                "assessor_email" => $assessor_email,
                                "assessor_name" => $assessor_name,
                                "targets" => array()
                            );
                        }

                        // Ensure there is actually an assessment (meaning the assessment task CRON job has been run for the assessment for this task) before providing the view with the assessment ID to navigate to.
                        $assessment = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($distribution_id, $internal_external, $task->getAssessorValue(), $task->getDeliveryDate());

                        // Add additional target to assessor.
                        $details[$progress_status][$task->getAssessorType()][$task->getAssessorValue()]["targets"][] = array(
                            "target_name" => $target_name,
                            "target_id" => $task->getTargetID(),
                            "target_type" => "proxy_id",
                            "target_group" => $target_group,
                            "dassessment_id" => ($assessment ? $assessment->getID() : 0),
                            "external_hash" => ($task->getAssessorType() == "external" && $assessment ? $assessment->getExternalHash() : false),
                            "aprogress_id" => ((isset($progress) && $progress) ? $progress->getID() : 0),
                            "parent_schedule" => array("title" => "Additional Task" . (!$assessment ? " - Unavailable until midnight" : "")),
                            "child_schedules" => false,
                            "delivery_date" => $task->getDeliveryDate()
                        );

                        // Sort targets by delivery date.
                        usort($details[$progress_status][$task->getAssessorType()][$task->getAssessorValue()]["targets"], function ($a, $b) {
                            return $a["delivery_date"] - $b["delivery_date"];
                        });

                        /*
                        // Sort assessors alphabetically.
                        usort($details[$progress_status][$task->getAssessorType()][$task->getAssessorValue()], function ($a, $b) {
                            return $a["assessor_name"] - $b["assessor_name"];
                        });
                        */
                    }
                }
            }

            if (!isset($details["distribution_target_type"]) || !$details["distribution_target_type"]) {
                $details["distribution_target_type"] = "proxy_id";
            }
        }

        /*  I wanted to be efficient and sort this array in one loop, and it does seem to be ordering the targets correctly
            when looking at a dump, but it's not assigning the targets back to the assessor for whatever reason. Resorting
            to sorting after each target is appended instead.
        foreach ($details as $key1 => $type) {
            foreach ($type as $key2 => $assessor) {
                foreach ($assessor as $index) {
                    usort($index["targets"], function ($a, $b) {
                        return $a["delivery_date"] - $b["delivery_date"];
                    });
                }
            }
        }
        */
        return $details;
    }

}