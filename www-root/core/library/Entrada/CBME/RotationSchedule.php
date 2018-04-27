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
 * This is a class for handling rotation schedule retrieval
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_CBME_RotationSchedule extends Entrada_Base {

    protected $disable_internal_storage = false;    // A hard override for disabling the isInStorage mechanism check (makes it always return false)
    protected $memory_storage;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
        $this->memory_storage = new Entrada_Utilities_MemoryStorage($arr);
    }

    public function calculateDateByOffset($delivery_period, $period_offset, $start_date, $end_date) {
        $delivery_date = 0;
        switch ($delivery_period) {
            case "after-start" :
                $delivery_date = $start_date + $period_offset;
                break;
            case "before-middle" :
                $seconds_until_middle = ($end_date - $start_date) / 2;
                $delivery_date = ($start_date + $seconds_until_middle) - $period_offset;
                break;
            case "after-middle" :
                $seconds_until_middle = ($end_date - $start_date) / 2;
                $delivery_date = ($start_date + $seconds_until_middle) + $period_offset;
                break;
            case "before-end" :
                $delivery_date = $end_date - $period_offset;
                break;
            case "after-end" :
                $delivery_date = $end_date + $period_offset;
                break;
        }
        return ceil($delivery_date);
    }

    public function calculateDateByFrequency($frequency, $date) {
        $date = ($date + ($frequency * 86400));
        return $date;
    }

    public function fetchLearnerBlocks($block_id, $proxy_id = null) {
        global $db;
        if ($this->memory_storage->isInStorage("fetch-learner-blocks", "$block_id-$proxy_id")) {
            return $this->memory_storage->fetchFromStorage("fetch-learner-blocks", "$block_id-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }
            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_audience_value
                    ORDER BY a.`start_date`";

            $prepared_variables = array();
            $prepared_variables[] = $block_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            $learner_blocks = $db->GetAll($query, $prepared_variables);
            $this->memory_storage->addToStorage("fetch-learner-blocks", $learner_blocks, "$block_id-$proxy_id");
            return $learner_blocks;
        }
    }

    public function fetchBlockRotations($block_id, $scope = null, $proxy_id = null) {
        global $db;
        if ($this->memory_storage->isInStorage("fetch-block-rotations", "$block_id-$scope-$proxy_id")) {
            return $this->memory_storage->fetchFromStorage("fetch-block-rotations", "$block_id-$scope-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }

            $AND_slot_type_filter = "";
            if ($scope == "internal_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 1";
            } else if ($scope == "external_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 2";
            }

            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_audience_value
                    $AND_slot_type_filter
                    ORDER BY a.`start_date`";

            $prepared_variables = array();
            $prepared_variables[] = $block_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            $block_rotations = $db->GetAll($query, $prepared_variables);
            $this->memory_storage->addToStorage("fetch-block-rotations", $block_rotations, "$block_id-$scope-$proxy_id");
            return $block_rotations;
        }
    }

    public function fetchRotations($schedule_id, $scope = null, $proxy_id = null, $cperiod_id = null) {
        global $db;
        if ($this->memory_storage->isInStorage("fetch-rotations", "$schedule_id-$scope-$proxy_id")) {
            return $this->memory_storage->fetchFromStorage("fetch-rotations", "$schedule_id-$scope-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }
            $AND_slot_type_filter = "";
            if ($scope == "internal_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 1";
            } else if ($scope == "external_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 2";
            }
            $AND_cperiod_value = "";
            if ($cperiod_id) {
                $AND_cperiod_value = "AND a.`cperiod_id` = ?";
            }

            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`, a.`title`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_parent_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_slot_type_filter
                    $AND_audience_value
                    $AND_cperiod_value
                    ORDER BY a.`start_date`";
            $prepared_variables = array();
            $prepared_variables[] = $schedule_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            if ($cperiod_id) {
                $prepared_variables[] = $cperiod_id;
            }
            $rotations = $db->GetAll($query, $prepared_variables);
            $this->memory_storage->addToStorage("fetch-rotations", $rotations, "$schedule_id-$scope-$proxy_id");
            return $rotations;
        }
    }

    public function getRotationDates($rotations = null, $organisation_id = null) {
        $storage_key = md5(serialize($rotations)) . "$organisation_id";
        if ($this->memory_storage->isInStorage("get-rotation-dates", $storage_key)) {
            return $this->memory_storage->fetchFromStorage("get-rotation-dates", $storage_key);
        } else {
            $all_rotations = array();
            $all_rotation_dates = array();
            $unique_rotation_dates = false;
            foreach ($rotations as $rotation) {
                $all_rotations[$rotation["audience_value"]][] = $rotation;
            }
            if ($all_rotations) {
                foreach ($all_rotations as $proxy_id => $user_rotations) {
                    foreach ($user_rotations as $user_rotation) {
                        $contiguous_end_date = $user_rotation["start_date"] - 1;
                        $start_date = $this->recursiveStartDate($contiguous_end_date, $proxy_id, $user_rotation["schedule_parent_id"], $organisation_id, $user_rotation["start_date"]);

                        $contiguous_start_date = $user_rotation["end_date"] + 1;
                        $end_date = $this->recursiveEndDate($contiguous_start_date, $proxy_id, $user_rotation["schedule_parent_id"], $organisation_id, $user_rotation["end_date"]);

                        $all_rotation_dates[$proxy_id][$end_date] = array($start_date, $end_date);
                        $unique_rotation_dates[] = array($start_date, $end_date);
                    }
                }
                $unique_rotation_dates = array_unique($unique_rotation_dates, SORT_REGULAR);
            }
            $unique_dates = array("unique_rotation_dates" => $unique_rotation_dates, "all_rotation_dates" => $all_rotation_dates);
            $this->memory_storage->addToStorage("get-rotation-dates", $unique_dates, $storage_key);
            return $unique_dates;
        }
    }

    private function recursiveStartDate($contiguous_end_date, $proxy_id, $schedule_parent_id, $organisation_id, $start_date) {
        global $db;
        $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, `end_date`, b.*
                    FROM   `cbl_schedule`  AS a
                    JOIN   `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    WHERE  a.`end_date` = ?
                    AND    b.`audience_value` = ?
                    AND    a.`schedule_parent_id` = ?
                    AND    a.`organisation_id` = ?
                    AND    b.`audience_type` = 'proxy_id'
                    AND    b.`deleted_date` IS NULL";

        $result = $db->GetRow($query, array($contiguous_end_date, $proxy_id, $schedule_parent_id, $organisation_id));
        if ($result) {
            $contiguous_start_date = $result["start_date"] - 1;
            return $this->recursiveStartDate($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $result["start_date"]);
        } else {
            return $start_date;
        }
    }

    private function recursiveEndDate($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $end_date) {
        global $db;
        $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, `end_date`, b.*
                    FROM   `cbl_schedule`  AS a
                    JOIN   `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    WHERE  a.`start_date` = ?
                    AND    b.`audience_value` = ?
                    AND    a.`schedule_parent_id` = ?
                    AND    a.`organisation_id` = ?
                    AND    b.`audience_type` = 'proxy_id'
                    AND    b.`deleted_date` IS NULL";

        $result = $db->GetRow($query, array($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id));
        if ($result) {
            $contiguous_start_date = $result["end_date"] + 1;
            return $this->recursiveEndDate($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $result["end_date"]);
        } else {
            return $end_date;
        }
    }
}