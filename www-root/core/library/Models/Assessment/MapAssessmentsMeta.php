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
 * @author Unit: Health Scieinces Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessment_MapAssessmentsMeta extends Models_Base {
    /**
     * @var string
     */
    protected static $table_name = "map_assessments_meta";

    /**
     * @var string
     */
    protected static $primary_key = "map_assessments_meta_id";

    /**
     * @var string
     */
    protected static $default_sort_column = "map_assessments_meta_id";

    /**
     * Field names within the static::$table_name table.
     * @var string
     */
    protected $map_assessments_meta_id,
        $fk_assessment_method_id,
        $fk_assessments_meta_id,
        $updated_date,
        $updated_by;

    /**
     * @param null $arr
     */
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    /**
     * @return string
     */
    public function getID() {
        return $this->map_assessments_meta_id;
    }

    /**
     * @return string
     */
    public function getMedbiqAssessmentMethodID() {
        return $this->fk_assessment_method_id;
    }

    /**
     * @return string
     */
    public function getAssessmentMethodID() {
        return $this->fk_assessments_meta_id;
    }

    /**
     * @return string
     */
    public function getUpdatedDate() {
        return $this->updated_date;
    }

    /**
     * @return string
     */
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /**
     * @param null $fk_assessment_method_id
     * @return array
     */
    public static function fetchAllByMedbiqAssessmentMethodID($fk_assessment_method_id = null) {
        $self = new self();
        return $self->fetchAll(array(
            array(
                "key" => "fk_assessment_method_id",
                "method" => "=",
                "value" => $fk_assessment_method_id
            )
        ));
    }

    /**
     * @param null $fk_assessments_meta_id
     * @return bool|Models_Base
     */
    public static function fetchRowByAssessmentMethodID($fk_assessments_meta_id = null) {
        $self = new self();
        return $self->fetchRow(array("fk_assessments_meta_id" => $fk_assessments_meta_id));
    }

    /**
     * @param null $fk_assessments_meta_id
     * @return array
     */
    public static function fetchAllByAssessmentMethodID($fk_assessments_meta_id = null) {
        $self = new self();
        return $self->fetchAll(array("fk_assessments_meta_id" => $fk_assessments_meta_id));
    }

    /**
     * @return bool|Models_Base
     */
    public function getMedbiqAssessmentMethod () {
        return Models_MedbiqAssessmentMethod::get($this->fk_assessment_method_id);
    }

    /**
     * @return bool
     */
    public function delete() {
        global $db;

        $query = "DELETE FROM `".static::$table_name."` WHERE `map_assessments_meta_id` = ?";
        if ($db->Execute($query, array($this->getID()))) {
            return true;
        }

        return false;
    }
}
