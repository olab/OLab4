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

class Models_MedbiqAssessmentMethod extends Models_Base {
    /**
     * @var string
     */
    protected static $table_name = "medbiq_assessment_methods";

    /**
     * @var string
     */
    protected static $primary_key = "assessment_method_id";

    /**
     * @var string
     */
    protected static $default_sort_column = "assessment_method";

    /**
     * Field names within the static::$table_name table.
     * @var string
     */
    protected $assessment_method_id,
        $code,
        $assessment_method,
        $assessment_method_description,
        $active,
        $updated_date,
        $updated_by;

    /**
     * @param null $arr
     */
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    /**
     * @return mixed
     */
    public function getID() {
        return $this->assessment_method_id;
    }

    /**
     * @return mixed
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getAssessmentMethod() {
        return $this->assessment_method;
    }

    /**
     * @return mixed
     */
    public function getAssessmentMethodDescription() {
        return $this->assessment_method_description;
    }

    /**
     * @return mixed
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate() {
        return $this->updated_date;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /**
     * @param null $assessment_method_id
     * @param int $active
     * @return bool|Models_Base
     */
    public static function get($assessment_method_id = null, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array(
                "key" => "assessment_method_id",
                "method" => "=",
                "value" => $assessment_method_id,
            ),
            array(
                "mode" => "AND",
                "key" => "active",
                "method" => "=",
                "value" => $active,
            ),
        ));
    }

    /**
     * @return array
     */
    public static function fetchAllMedbiqAssessmentMethods() {
        $self = new self();
        return $self->fetchAll(array("active" => 1), "=", "AND", "assessment_method");
    }

    /**
     * @return array
     */
    public function getMappedAssessmentCharacteristics() {
        return Models_Assessment_MapAssessmentsMeta::fetchAllByMedbiqAssessmentMethodID($this->getID());
    }

    /**
     * @return bool
     */
    public function update() {
		global $db;

		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "UPDATE", "`assessment_method_id` = ".$db->qstr($this->getID()))) {
			return true;
		}

        return false;
	}

    /**
     * @return bool
     */
    public function insert() {
		global $db;

		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "INSERT")) {
			$this->assessment_method_id = $db->Insert_ID();

			return true;
		}

        return false;
	}

    /**
     * @return bool
     */
    public function delete() {
        return false;
    }
}
