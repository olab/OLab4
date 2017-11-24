<?php

class Models_Curriculum_Type extends Models_Base {

    protected $curriculum_type_id, $parent_id, $curriculum_type_name, $curriculum_type_description, $curriculum_type_order,
              $curriculum_type_active, $curriculum_level_id, $updated_date, $updated_by;

    protected static $table_name = "curriculum_lu_types";
    protected static $default_sort_column = "curriculum_type_order";
    protected static $primary_key = "curriculum_level_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCurriculumLevelID() {
        return $this->curriculum_level_id;
    }

    public function getCurriculumTypeActive() {
        return $this->curriculum_type_active;
    }

    public function getCurriculumTypeDescription() {
        return $this->curriculum_type_description;
    }

    public function getID() {
        return $this->curriculum_type_id;
    }

    public function getCurriculumTypeName() {
        return $this->curriculum_type_name;
    }

    public function getCurriculumTypeOrder() {
        return $this->curriculum_type_order;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public static function setCurriculumTypeOrderByCurriculumIDArray($curriculum_type_id_array) {
        global $db;

        foreach ($curriculum_type_id_array as $key => $curriculum_type_id) {
            $query = "UPDATE `curriculum_lu_types` SET 
                  curriculum_type_order = ?
                  WHERE `curriculum_type_id` = ?";

            $result = $db->Execute($query, array(($key + 1), $curriculum_type_id));
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public static function fetchRowByID($curriculum_type_id) {
        $self = new self();
        return $self->fetchRow(array("curriculum_type_id" => $curriculum_type_id));
    }

    public static function fetchAllByOrg($org_id, $active = 1) {
        global $db;
        $output = false;
        $query = "SELECT b.*
                    FROM `curriculum_type_organisation` AS a
                    JOIN `curriculum_lu_types` AS b
                    ON a.`curriculum_type_id` = b.`curriculum_type_id`
                    WHERE a.`organisation_id` = ?
                    AND b.`curriculum_type_active` = ?
                    ORDER BY b.`curriculum_type_order`";
        $results = $db->GetAll($query, array($org_id, $active));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchRowByCPeriodID($cperiod_id) {
        global $db;
        $output = false;
        $query = "SELECT a.*
                    FROM `curriculum_lu_types` AS a
                    JOIN `curriculum_periods` AS b
                    ON a.`curriculum_type_id` = b.`curriculum_type_id`
                    WHERE b.`cperiod_id` = ?";
        $result = $db->GetRow($query, array($cperiod_id));
        if ($result) {
            $output = new self($result);
        }
        return $output;
    }

}
