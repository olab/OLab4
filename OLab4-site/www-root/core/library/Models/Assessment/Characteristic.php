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

class Models_Assessment_Characteristic extends Models_Base {
    /**
     * @var string
     */
    protected static $table_name = "assessments_lu_meta";

    /**
     * @var string
     */
    protected static $primary_key = "id";

    /**
     * @var string
     */
    protected static $default_sort_column = "type";

    /**
     * Field names within the static::$table_name table.
     * @var string
     */
    protected $id,
        $organisation_id,
        $type,
        $title,
        $description,
        $active;

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
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getOrganisationId() {
        return $this->organisation_id;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param null $id
     * @param int $active
     * @return bool|Models_Base
     */
    public static function get($id = null, $active = 1) {
        $self = new self();

        return $self->fetchRow(array(
            array(
                "key" => "id",
                "method" => "=",
                "value" => $id
            ),
            array(
                "mode" => "AND",
                "key" => "active",
                "method" => "=",
                "value" => $active
            ),
        ));
    }

    /**
     * This method returns all of the possible options from the type enum.
     * @return array
     */
    public static function getTypeOptions() {
        global $db;

        $enum = array();

        $query = "SHOW COLUMNS FROM `assessments_lu_meta` WHERE Field = 'type'";
        $type = $db->GetRow($query);

        if ($type) {
            preg_match('/^enum\((.*)\)$/', $type["Type"], $matches);

            if ($matches && is_array($matches) && isset($matches[1])) {
                foreach (explode(",", $matches[1]) as $value) {
                    $enum[] = trim($value, "'");
                }
            }
        }

        natcasesort($enum);

        return $enum;
    }

    /**
     * @return bool|Models_Base
     */
    public function getMappedMedbiqAssessmentMethod() {
        $output = false;

        $medbiq_assessment_method = Models_Assessment_MapAssessmentsMeta::fetchRowByAssessmentMethodID($this->id);
        if ($medbiq_assessment_method) {
            $medbiq_assessment_method_id = $medbiq_assessment_method->getMedbiqAssessmentMethodID();
            $output = Models_MedbiqAssessmentMethod::get($medbiq_assessment_method_id);
        }

        return $output;
    }

    /**
     * @param null $organisation_id
     * @param int $active
     * @return array
     */
    public static function fetchAllByOrganisationID($organisation_id = null, $active = 1) {
        global $db;

        $characteristics = array();
        
        $query = "SELECT *
                    FROM `assessments_lu_meta`
                    WHERE `organisation_id` = ?
                    AND `active` = ?
                    ORDER BY CAST(`type` AS CHAR) ASC, `title` ASC";
        $results = $db->GetAll($query, array($organisation_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $characteristics[] = new self($result);
            }
        }

        return $characteristics;
    }

    /**
     * @return bool
     */
    public function delete() {
        return $this->deactivate();
    }

    /**
     * @return bool
     */
    public function deactivate() {
        $this->active = 0;
        if ($this->update()) {
            return true;
        }

        return false;
    }
}
