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
 * 
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Options extends Models_Base {

    protected $daoption_id;
    protected $adistribution_id;
    protected $dassessment_id;
    protected $actor_id;
    protected $option_name;
    protected $option_value;
    protected $assessment_siblings;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_distribution_assessment_options";
    protected static $primary_key = "daoption_id";
    protected static $default_sort_column = "dassessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->daoption_id;
    }

    public function getDaoptionID() {
        return $this->daoption_id;
    }

    public function setDaoptionID($daoption_id) {
        $this->daoption_id = $daoption_id;

        return $this;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function setAdistributionID($adistribution_id) {
        $this->adistribution_id = $adistribution_id;

        return $this;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function setDassessmentID($dassessment_id) {
        $this->dassessment_id = $dassessment_id;

        return $this;
    }

    public function getActorID() {
        return $this->actor_id;
    }

    public function setActorID($actor_id) {
        $this->actor_id = $actor_id;

        return $this;
    }

    public function getOptionName() {
        return $this->option_name;
    }

    public function setOptionName($option_name) {
        $this->option_name = $option_name;

        return $this;
    }

    public function getOptionValue() {
        return $this->option_value;
    }

    public function setOptionValue($option_value) {
        $this->option_value = $option_value;

        return $this;
    }

    public function getAssessmentSiblings() {
        return $this->assessment_siblings;
    }

    public function setAssessmentSiblings($assessment_siblings) {
        $this->assessment_siblings = $assessment_siblings;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public static function fetchRowByID($daoption_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "daoption_id", "method" => "=", "value" => $daoption_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "daoption_id", "method" => ">=", "value" => 0)));
    }

    public function fetchAllByDassessmentID($dassessment_id, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchTargetOptionProgressPercentage($daoption_id, $proxy_id) {
        global $db;

        $target_completion_count_sq = "   SELECT COUNT(cc_ap.`aprogress_id`) AS `completions`
                                          FROM cbl_assessment_progress AS cc_ap 
                                          WHERE (cc_ap.`target_type` = sq_at.`target_type` AND cc_ap.`target_record_id` = sq_at.`target_value`)
                                          AND cc_ap.`dassessment_id` = sq_a.`dassessment_id`
                                          AND cc_ap.`progress_value` = 'complete'
                                          AND cc_ap.`deleted_date` IS NULL";

        $query = "SELECT (
                      (
                          SELECT SUM(
                            IF(($target_completion_count_sq) > da.`min_submittable`, da.`min_submittable`, ($target_completion_count_sq))
                          ) AS `target_completion_count`
                          FROM `cbl_distribution_assessment_targets` AS sq_at
                          JOIN `cbl_distribution_assessments` AS sq_a
                          ON sq_at.`dassessment_id` = sq_a.`dassessment_id`
                          WHERE FIND_IN_SET(sq_a.`dassessment_id`, dao.`assessment_siblings`)
                          AND sq_a.`assessor_value` = ?
                      )

                      /

                      ((
                          SELECT DISTINCT COUNT(sq2_at.`atarget_id`) AS `unique_target_total_required`
                          FROM `cbl_distribution_assessment_targets` AS sq2_at
                          JOIN `cbl_distribution_assessments` AS sq2_a
                          ON sq2_a.`dassessment_id` = sq2_at.`dassessment_id`
                          WHERE FIND_IN_SET(sq2_a.`dassessment_id`, dao.`assessment_siblings`)
                          AND sq2_a.`assessor_value` = ?
                      ) * da.`min_submittable`)
                      
                      * 100
                  ) AS `completion_percentage`
                  FROM `cbl_distribution_assessment_options` AS dao  
                  JOIN `cbl_distribution_assessments` AS da 
                  ON da.`dassessment_id` = dao.`dassessment_id`
                  WHERE dao.`daoption_id` = ?
                  AND dao.`deleted_date` IS NULL ";
        $result = $db->GetRow($query, array($proxy_id, $proxy_id, $daoption_id));

        return ($result ? $result["completion_percentage"] : false);
    }

    public function fetchRowByDAssessmentIDOptionName($dasessment_id, $option_name, $option_value = null) {
        $self = new self();

        $constraints = array(
            array("key" => "dassessment_id", "method" => "=", "value" => $dasessment_id),
            array("key" => "option_name", "method" => "=", "value" => $option_name)
        );

        if (isset($option_value)) {
            $constraints[] = array("key" => "option_value", "method" => "=", "value" => $option_value);
        }

        return $self->fetchRow($constraints);
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

}