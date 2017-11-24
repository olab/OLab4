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

class Models_Assessments_Progress extends Models_Base {
    protected $aprogress_id, $one45_formsAttached_id, $adistribution_id, $dassessment_id, $assessor_type, $assessor_value, $adtarget_id, $target_record_id, $target_learning_context_id, $progress_value, $uuid, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_progress";
    protected static $primary_key = "aprogress_id";
    protected static $default_sort_column = "aprogress_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aprogress_id;
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function getOne45FormsAttachedID() {
        return $this->one45_formsAttached_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getUuid() {
        return $this->uuid;
    }

    public function getAdtargetID() {
        return $this->adtarget_id;
    }

    public function getTargetRecordID() {
        return $this->target_record_id;
    }

    public function getDAssessmentID() {
        return $this->dassessment_id;
    }

    public function getTargetLearningContextID() {
        return $this->target_learning_context_id;
    }

    public function getProgressValue() {
        return $this->progress_value;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($id) {
        $this->updated_by = $id;
    }

    public static function fetchRowByID($aprogress_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aprogress_id", "value" => $aprogress_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    public static function fetchAllByDistributionID($distribution_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
    
    public static function fetchAllByDistributionIDProgressValue($distribution_id, $progress_value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "progress_value", "value" => $progress_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public static function fetchRowByAdistributionIDAssessorTypeAssessorValue($adistribution_id, $assessor_type, $assessor_value, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

	public static function fetchRowByAdistributionIDTargetRecordID($adistribution_id, $target_record_id, $deleted_date = NULL) {
		$self = new self();
		return $self->fetchRow(array(
		array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
		array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
		array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		));
	}

	public static function fetchAllByDistributionIDAssessmentID($distribution_id, $assessment_id, $deleted_date = NULL) {
		$self = new self();
		return $self->fetchAll(array(
			array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
			array("key" => "dassessment_id", "value" => $assessment_id, "method" => "="),
			array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		));
	}

    public static function fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordID($adistribution_id, $assessor_type, $assessor_value, $target_record_id, $progress_value = NULL, $dassessment_id = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }

        if (isset($dassessment_id) && $dassessment_id) {
            $constraints[] = array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "=");
        }

        return $self->fetchRow($constraints);
    }

    public static function fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($adistribution_id, $assessor_type, $assessor_value, $target_record_id, $progress_value = NULL, $dassessment_id = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }

        if (isset($dassessment_id) && $dassessment_id) {
            $constraints[] = array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "=");
        }

        return $self->fetchAll($constraints);
    }

    public static function fetchRowByDassessmentIDAssessorTypeAssessorValueTargetRecordID($dassessment_id, $assessor_type, $assessor_value, $target_record_id, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

    public static function fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($adistribution_id, $assessor_type, $assessor_value, $target_record_id, $dassessment_id, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

    public static function fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDLearningContextID($adistribution_id, $assessor_type, $assessor_value, $target_record_id, $target_learning_context_id, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "target_learning_context_id", "value" => ($target_learning_context_id ? $target_learning_context_id : NULL), "method" => ($target_learning_context_id ? "<=" : "IS")),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

    public static function fetchAllByProxyID($proxy_id) {
        global $db;
        $output = array();

        $query = "  SELECT a.*
                    FROM `cbl_assessment_progress` a
                    JOIN `cbl_assessment_distribution_targets` b
                    ON a.`adtarget_id` = b.`adtarget_id`
                    JOIN `cbl_assessments_lu_distribution_target_types_options` c
                    ON b.`adtto_id` = c.`adtto_id`
                    JOIN `cbl_assessments_lu_distribution_target_types` d
                    ON c.`adttype_id` = d.`adttype_id`
                    WHERE  a.`target_record_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND d.`name` in ('proxy_id', 'group_id', 'cgroup_id')
                    AND a.`progress_value` = 'complete'
                    ORDER BY a.`adistribution_id` ASC";

        $results = $db->GetAll($query, array($proxy_id));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchAllByProxyIDSearch($proxy_id, $search_term = null) {
        global $db;
        $output = array();

        $query = "  SELECT a.*
                    FROM `cbl_assessment_progress` a
                    JOIN `cbl_assessment_distribution_targets` b
                    ON a.`adtarget_id` = b.`adtarget_id`
                    JOIN `cbl_assessments_lu_distribution_target_types_options` c
                    ON b.`adtto_id` = c.`adtto_id`
                    JOIN `cbl_assessments_lu_distribution_target_types` d
                    ON c.`adttype_id` = d.`adttype_id`
                    JOIN `cbl_assessment_distribution_schedule` ds
                    ON a.`adistribution_id` = ds.`adistribution_id`
                    JOIN `cbl_schedule` e
                    ON ds.`schedule_id` = e.`schedule_id`
                    LEFT JOIN `cbl_schedule` f
                    ON f.`schedule_id` = e.`schedule_parent_id`
                    JOIN `cbl_assessment_distributions` g
                    ON a.`adistribution_id` = g.`adistribution_id`
                    JOIN `courses` h
                    ON g.`course_id` = h.`course_id`
                    JOIN `".AUTH_DATABASE."`.`user_data` ud
                    ON ud.`id` = a.`proxy_id`
                    WHERE  a.`target_record_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND d.`name` in ('proxy_id', 'group_id', 'cgroup_id')
                    AND a.`progress_value` = 'complete'
                    ". (trim($search_term) ? " AND CONCAT(ud.`firstname`, ' ' , ud.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR if(e.`schedule_parent_id` > 0, f.`title`, e.`title`) LIKE ".$db->qstr("%".$search_term."%")." OR h.`course_name` LIKE ".$db->qstr("%".$search_term."%") : "") ."
                    ORDER BY a.`adistribution_id` ASC";

        $results = $db->GetAll($query, array($proxy_id));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchAllByTargetRecordID ($target_record_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByDassessmentID($dassessment_id = null, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchRow($constraints);
    }

    public static function fetchAllByDassessmentID ($dassessment_id, $progress_value = NULL, $deleted_date = NULL, $sort_order = "ASC") {
        $self = new self();

        $constraints = array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if ($progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }

        return $self->fetchAll($constraints, "=", "AND", "use_default", $sort_order);
    }

    public static function fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID ($dassessment_id, $assessor_type, $assessor_value, $target_record_id, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();

        $constraints = array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if ($progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }

        return $self->fetchAll($constraints);
    }

    public static function fetchAllByAdistributionIDAssessorTypeAssessorValue($adistribution_id, $assessor_type, $assessor_value, $progress_value = NULL, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if (isset($progress_value) && $progress_value) {
            $constraints[] = array("key" => "progress_value", "value" => $progress_value, "method" => "=");
        }
        return $self->fetchAll($constraints);
    }

    public static function updateUuidField () {
        global $db;
        $self = new self();
        $progress_records = $self::fetchAllRecords();
        if ($progress_records) {
            foreach ($progress_records as $progress_record) {
                $query = "SELECT UUID() as `uuid`";
                $result = $db->GetRow($query);
                if ($result) {
                    $progress_record->fromArray(array("uuid" => $result["uuid"]))->update();
                }
            }
        }
    }

    public static function fetchRowByUuid ($uuid = null, $deleted_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "uuid", "value" => $uuid, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchRow($constraints);
    }

    public static function generateUuid () {
        global $db;
        $uuid = null;

        $query = "SELECT UUID() as `uuid`";
        $result = $db->GetRow($query);
        if ($result) {
            $uuid = $result["uuid"];
        }
        return $uuid;
    }

    public static function fetchAllBySyncDateAssessorTypeAssessorValue ($assessor_type = null, $assessor_value = null, $sync_date = null, $deleted_date = null) {
        global $db;
        $progress_records = false;

        $query = "SELECT * FROM `". static::$table_name ."` WHERE `assessor_type` = ? AND `assessor_value` = ? AND (`created_date` > ? OR `updated_date` > ?)";
        $results = $db->GetAll($query, array($assessor_type, $assessor_value, $sync_date, $sync_date));
        if ($results) {
            foreach ($results as $result) {
                $progress_records[] = new self($result);
            }
        }
        return $progress_records;
    }

    public static function fetchAllByADistributionIDTargetRecordID($distribution_id, $target_record_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "target_record_id", "value" => $target_record_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByAssessorTypeAssessorValueDAssessmentID ($assessor_type, $assessor_value, $dassessment_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllTargetsByFormIDDistributionID($form_id, $distribution_id) {
        global $db;

        $query = "  SELECT DISTINCT a.`target_record_id`
                    FROM `cbl_assessment_progress` as a
                    JOIN `cbl_assessment_progress_responses` as b
                    ON a.`aprogress_id` = b.`aprogress_id`          
                    WHERE a.`progress_value` = 'complete'
                    AND a.`deleted_date` IS NULL
                    AND b.`form_id` = ? 
                    AND b.`adistribution_id` = ? 
                    GROUP BY a.`aprogress_id`";

        return $db->GetAll($query, array($form_id, $distribution_id));
    }

    public static function fetchAllTargetsByID($target_record_id) {
        global $db;

        $query = "  SELECT b.`adistribution_id` FROM `cbl_assessment_progress` AS a
                    JOIN `cbl_assessment_progress_responses` AS b 
                    ON a.`aprogress_id` = b.`aprogress_id` 
                    WHERE a.`target_record_id` = ? 
                    AND a.`progress_value` = 'complete' 
                    AND a.`deleted_date` IS NULL 
                    GROUP BY b.`adistribution_id`";

        return $db->GetAll($query, array($target_record_id));
    }
}