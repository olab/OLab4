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
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Assessor extends Models_Base {
    protected $adassessor_id, $adistribution_id, $assessor_type, $assessor_role, $assessor_scope, $assessor_value, $assessor_name, $assessor_start, $assessor_end;

    protected static $table_name = "cbl_assessment_distribution_assessors";
    protected static $primary_key = "adassessor_id";
    protected static $default_sort_column = "adassessor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adassessor_id;
    }

    public function getAdassessorID() {
        return $this->adassessor_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorRole() {
        return $this->assessor_role;
    }

    public function getAssessorScope() {
        return $this->assessor_scope;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getAssessorName() {
        return $this->assessor_name;
    }

    public function getAssessorStart() {
        return $this->assessor_start;
    }

    public function getAssessorEnd() {
        return $this->assessor_end;
    }

    public static function fetchRowByID($adassessor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adassessor_id", "value" => $adassessor_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adassessor_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByProxyID($proxy_id) {
        $self = new self();
        $constraints = array(
                        array("key" => "assessor_type", "value" => "proxy_id", "method" => "="),
                        array("key" => "assessor_value", "value" => $proxy_id, "method" => "=")
                        );

        return $self->fetchAll($constraints);
    }

    public static function fetchRowByExternalAssessorIDDistributionID($external_assessor_id, $adistribution_id) {
        $self = new self();
        $constraints = array(
                        array("key" => "assessor_type", "value" => "external_hash", "method" => "="),
                        array("key" => "assessor_value", "value" => $external_assessor_id, "method" => "="),
                        array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
                        );

        return $self->fetchAll($constraints);
    }

    public static function fetchAllByDistributionID($adistribution_id) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public static function fetchRowByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionIDAssessorValue($adistribution_id, $assessor_value) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "=")
        ));
    }

    public static function fetchAllByProxyIDSearch($proxy_id, $search_term = null) {
        global $db;

        $query = "  SELECT *, e.`start_date` as `rotation_start_date`, e.`end_date` as `rotation_end_date`, if(e.`schedule_parent_id` > 0, f.`title`, e.`title`) as `rotation_name`, e.`schedule_id` as `schedule_id`, i.`name` as `target_type`, g.`target_id` as `target_id`
                    FROM `cbl_assessment_distribution_assessors` a
                    JOIN `cbl_assessment_distributions` b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    JOIN `cbl_assessment_distribution_schedule` c
                    ON a.`adistribution_id` = c.`adistribution_id`
                    JOIN `courses` d
                    ON b.`course_id` = d.`course_id`
                    JOIN `cbl_schedule` e
                    ON c.`schedule_id` = e.`schedule_id`
                    LEFT JOIN `cbl_schedule` f
                    ON f.`schedule_id` = e.`schedule_parent_id`
                    JOIN `cbl_assessment_distribution_targets` g
                    ON b.`adistribution_id` = g.`adistribution_id`
                    JOIN `cbl_assessments_lu_distribution_target_types_options` h
                    ON g.`adtto_id` = h.`adtto_id`
                    JOIN `cbl_assessments_lu_distribution_target_types` i
                    ON h.`adttype_id` = i.`adttype_id`
                    WHERE  a.`assessor_value` = ?
                    AND a.`assessor_type` = 'proxy_id'
                    ". (trim($search_term) ? " AND if(e.`schedule_parent_id` > 0, f.`title`, e.`title`) LIKE ".$db->qstr("%".$search_term."%")." OR d.`course_name` LIKE ".$db->qstr("%".$search_term."%") : "") ."
                    ORDER BY a.`adistribution_id` ASC";

        $results = $db->GetAll($query, array($proxy_id));

        $output = array();
        if (isset($search_term) && $search_term) {
            $query = "  SELECT *, e.`start_date` as `rotation_start_date`, e.`end_date` as `rotation_end_date`, if(e.`schedule_parent_id` > 0, f.`title`, e.`title`) as `rotation_name`, e.`schedule_id` as `schedule_id`, i.`name` as `target_type`, g.`target_id` as `target_id`
                        FROM `cbl_assessment_distribution_assessors` a
                        JOIN `cbl_assessment_distributions` b
                        ON a.`adistribution_id` = b.`adistribution_id`
                        JOIN `cbl_assessment_distribution_schedule` c
                        ON a.`adistribution_id` = c.`adistribution_id`
                        JOIN `courses` d
                        ON b.`course_id` = d.`course_id`
                        JOIN `cbl_schedule` e
                        ON c.`schedule_id` = e.`schedule_id`
                        LEFT JOIN `cbl_schedule` f
                        ON f.`schedule_id` = e.`schedule_parent_id`
                        JOIN `cbl_assessment_distribution_targets` g
                        ON b.`adistribution_id` = g.`adistribution_id`
                        JOIN `cbl_assessments_lu_distribution_target_types_options` h
                        ON g.`adtto_id` = h.`adtto_id`
                        JOIN `cbl_assessments_lu_distribution_target_types` i
                        ON h.`adttype_id` = i.`adttype_id`
                        WHERE  a.`assessor_value` = ?
                        AND a.`assessor_type` = 'proxy_id'
                        ORDER BY a.`adistribution_id` ASC";

            $results2 = $db->GetAll($query, array($proxy_id));
            if ($results2) {
                foreach($results2 as $result) {
                    switch($result["target_type"]) {
                        case "proxy_id":
                            $query = "  SELECT *
                                        FROM `".AUTH_DATABASE."`.`user_data` a
                                        WHERE  a.`id` = ?
                                        AND CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%");

                            $check = $db->GetAll($query, array($result["target_id"]));

                            break;
                        case "group_id":
                            $query = "  SELECT *
                                        FROM `groups` a
                                        WHERE  a.`group_id` = ?
                                        AND a.`group_name` LIKE ".$db->qstr("%".$search_term."%");

                            $check = $db->GetAll($query, array($result["target_id"]));
                            break;
                        case "group_id":
                            $query = "  SELECT *
                                        FROM `courses` a
                                        WHERE  a.`course_id` = ?
                                        AND a.`course_name` LIKE ".$db->qstr("%".$search_term."%");

                            $check = $db->GetAll($query, array($result["target_id"]));
                            break;
                        case "schedule_id":
                            $query = "  SELECT *
                                        FROM `cbl_schedule` a
                                        WHERE  a.`schedule_id` = ?
                                        AND a.`title` LIKE ".$db->qstr("%".$search_term."%");

                            $check = $db->GetAll($query, array($result["target_id"]));
                            break;
                    }

                    if ($check) {
                        $output[] = $result;
                    }
                }
            }
        }

        if (!$results) {
            return $output;
        } else {
            return array_merge($results, $output);
        }
    }

    public static function getAssessmentAssessors ($distribution_id = null, $delegator_id = null, $filter_start_date = false, $filter_end_date = false, $use_delegator = false) {
        global $db;
        $assessment_assessors = array();
        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($distribution_id);
        $assessor_records = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution_id);
        $schedule_cut_off_date = (is_null($distribution->getReleaseDate()) ? 0 : (int) $distribution->getReleaseDate());
        if ($assessor_records) {
            foreach ($assessor_records as $assessor_record) {
                switch ($assessor_record->getAssessorType()) {
                    case "proxy_id":
                        $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number` FROM `cbl_assessment_distribution_assessors` AS a
                                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                    ON a.`assessor_value` = b.`id`
                                    WHERE a.`adistribution_id` = ?
                                    AND a.`assessor_type` = 'proxy_id'
                                    AND a.`assessor_value` = ?";

                        $results = $db->GetAll($query, array($assessor_record->getAdistributionID(), $assessor_record->getAssessorValue()));
                        if ($results) {
                            foreach ($results as $assessor) {
                                $assessor_data = array("name" => $assessor["firstname"] . " " . $assessor["lastname"], "proxy_id" => $assessor["assessor_value"], "number" => $assessor["number"], "target_record_id" => $assessor["assessor_value"], "email" => $assessor["email"], "assessor_type" => "internal", "assessor_value" => $assessor["assessor_value"]);
                                $assessment_assessors[] = $assessor_data;
                            }
                        }
                        break;
                    case "group_id":
                        $assessors = Models_Group_Member::getAssessmentGroupMembers($distribution->getOrganisationID(), $assessor_record->getAssessorValue());
                        if ($assessors) {
                            foreach ($assessors as $assessor) {
                                $assessor_data = array("name" => $assessor["name"], "proxy_id" => $assessor["proxy_id"], "number" => $assessor["number"], "target_record_id" => $assessor["proxy_id"], "email" => $assessor["email"], "assessor_type" => "internal", "assessor_value" => $assessor["proxy_id"]);
                                $assessment_assessors[] = $assessor_data;
                            }
                        }
                        break;
                    case "cgroup_id":
                        $assessors = Models_Course_Group_Audience::fetchAllByCGroupID($assessor_record->getAssessorValue());
                        if ($assessors) {
                            foreach ($assessors as $assessor) {
                                $query = "  SELECT `id` as `proxy_id`, `firstname`, `lastname`, `email`, `number`
                                            FROM `" . AUTH_DATABASE . "`.`user_data`
                                            WHERE `id` = ?";
                                $assessor = $db->GetRow($query, array($assessor->getProxyID()));
                                if ($assessor) {
                                    $assessor_data = array("name" => $assessor["firstname"] . " " . $assessor["lastname"], "proxy_id" => $assessor["proxy_id"], "number" => $assessor["number"], "target_record_id" => $assessor["proxy_id"], "email" => $assessor["email"], "assessor_type" => "internal", "assessor_value" => $assessor["proxy_id"]);
                                    $assessment_assessors[] = $assessor_data;
                                }
                            }
                        }
                        break;
                    case "schedule_id":
                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($assessor_record->getAdistributionID());
                        if ($distribution_schedule) {
                            $auth_db = AUTH_DATABASE;

                            $AND_dates_filter = "";
                            if ($filter_start_date && $filter_end_date) {
                                $AND_dates_filter = "
                                AND (
                                    (a.`start_date` >= ? AND a.`start_date` <= ?)
                                    OR (a.`end_date` >= ? AND a.`end_date` <= ?)
                                    OR (a.`start_date` <= ? AND a.`end_date` >= ?)
                                )";
                            }

                            $AND_schedule_parent_filter = "";
                            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                            if ($schedule) {
                                $schedule_parent_id = $schedule->getScheduleParentID();
                                if ($schedule_parent_id) {
                                    $AND_schedule_parent_filter = "AND a.`schedule_id` = {$distribution_schedule->getScheduleID()}";
                                } else {
                                    $AND_schedule_parent_filter = "AND a.`schedule_parent_id` = {$distribution_schedule->getScheduleID()}";
                                }
                            }

                            $AND_slot_type = "";
                            if ($assessor_record->getAssessorScope() == "internal_learners") {
                                $AND_slot_type = "AND c.`slot_type_id` = 1";
                            } else if ($assessor_record->getAssessorScope() == "external_learners") {
                                $AND_slot_type = "AND c.`slot_type_id` = 2";
                            }

                            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`,
                                                b.*,
                                                c.`slot_type_id`,
                                                d.`id`, d.`firstname`, d.`lastname`, d.`email`, d.`number`
                                        FROM `cbl_schedule`           AS a
                                        JOIN `cbl_schedule_audience`  AS b ON a.`schedule_id` =  b.`schedule_id`
                                        JOIN `cbl_schedule_slots`     AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                                        JOIN `$auth_db`.`user_data`   AS d ON b.`audience_value` =  d.`id`
                                        WHERE a.`deleted_date` IS NULL
                                        AND b.`audience_type` = 'proxy_id'
                                        AND b.`deleted_date` IS NULL
                                        AND a.`start_date` >= ?
                                        $AND_dates_filter
                                        $AND_schedule_parent_filter
                                        $AND_slot_type

                                        GROUP BY b.`audience_value`
                                        ORDER BY d.`lastname`";

                            if ($filter_start_date && $filter_end_date) {
                                $prepared_variables = array($schedule_cut_off_date, $filter_start_date, $filter_end_date, $filter_start_date, $filter_end_date, $filter_start_date, $filter_end_date);
                            } else {
                                $prepared_variables = array($schedule_cut_off_date);
                            }

                            $results = $db->GetAll($query, $prepared_variables);
                            if ($results) {
                                foreach ($results as $assessor) {
                                    $assessor_data = array("name" => $assessor["firstname"] . " " . $assessor["lastname"], "proxy_id" => $assessor["id"], "number" => $assessor["number"], "target_record_id" => $assessor["id"], "email" => $assessor["email"], "assessor_value" => $assessor["id"], "assessor_type" => "internal");
                                    $assessment_assessors[] = $assessor_data;
                                }
                            }
                        }
                        break;
                    case "course_id" :
                        switch ($assessor_record->getAssessorScope()) {
                            case "all_learners" :
                                $course = Models_Course::fetchRowByID($assessor_record->getAssessorValue());
                                if ($course) {
                                    $assessors = $course->getAllMembers($distribution->getCPeriodID());
                                    if ($assessors) {
                                        foreach ($assessors as $assessor) {
                                            $assessor_data = array("name" => $assessor->getFirstname() . " " . $assessor->getLastname(), "proxy_id" => $assessor->getActiveID(), "number" => $assessor->getNumber(), "target_record_id" => $assessor->getActiveID(), "email" => $assessor->getEmail(), "assessor_type" => "internal", "assessor_value" => $assessor->getActiveID());
                                            $assessment_assessors[] = $assessor_data;
                                        }
                                    }
                                }
                                break;
                        }
                        break;
                    case "external_hash" :
                        $assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor_record->getAssessorValue());
                        if ($assessor) {
                            $assessor_data = array("name" => $assessor->getFirstname() . " " . $assessor->getLastname(), "assessor_type" => "external", "assessor_value" => $assessor->getID(), "target_record_id" => $assessor->getID(), "email" => $assessor->getEmail());
                            $assessment_assessors[] = $assessor_data;
                        }
                        break;
                }
            }
        }

        return $assessment_assessors;
    }
    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

    public static function fetchExternalHashByID($dassessment_id) {
        global $db;

        $query = "SELECT `external_hash` FROM `".static::$table_name."` WHERE `dassessment_id` = ?";
        $hash = $db->GetOne($query, array($dassessment_id));

        if ($hash) {
            return $hash;
        } else {
            return false;
        }
    }
}