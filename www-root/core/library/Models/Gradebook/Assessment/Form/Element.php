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
 * Stores the weight for a given assessment form item and 
 * the accompanying assessment
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Form_Element extends Models_Base {
    protected $gafelement_id, $assessment_id, $afelement_id, $weight;

    protected static $table_name = "gradebook_assessment_form_elements";
    protected static $primary_key = "gafelement_id";
    protected static $default_sort_column = "gafelement_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->gafelement_id;
    }

    public function setID($id) {
        return $this->setGafelementID($id);
    }

    public function getGafelementID() {
        return $this->gafelement_id;
    }

    public function setGafelementID($gafelement_id) {
        $this->gafelement_id = $gafelement_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    /**
     * Set assessment ID
     * @param int $assessment_id
     */
    public function setAssessmentID($assessment_id) {
        $this->assessment_id = $assessment_id;
    }

    public function getAfelementID() {
        return $this->afelement_id;
    }

    /**
     * Set afelement_id
     * @param int $afelement_id
     */
    public function setAfelementID($afelement_id) {
        $this->afelement_id = $afelement_id;
    }

    public function getWeight() {
        return $this->weight;
    }

    /**
     * Set weight
     * @param int $weight
     */
    public function setWeight($weight) {
        $this->weight = $weight;
    }

    public static function fetchRowByID($gafelement_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "gafelement_id", "value" => $gafelement_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "gafelement_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Fetches all records by assessment ID
     * @return array db results
     */
    public function fetchAllByAssessmentID() {
        return $this->fetchAll(array(array("key" => "assessment_id", "value" => $this->assessment_id, "method" => "=")));
    }

    /**
     * Updates the database with values in the model. By default updates all values regardless of null status. 
     * Optional parameter allows to specify which values will get updated.
     * @param  array|null $fields_to_update Ex. array('assessment_id', 'order')
     * @return $this 
     */
    public function update($fields_to_update = null) {
        global $db;

        // if fields_to_update is not set or not an array, use the standard toArray()
        if (!$fields_to_update || !is_array($fields_to_update)) {
            $update_array = $this->toArray();
        }
        else {
            $update_array = array();

            foreach($fields_to_update as $field) {
                $update_array[$field] = $this->$field;
            }
        }

        if ($db->AutoExecute(static::$table_name, $update_array, "UPDATE", "`gafelement_id` = ".$this->gafelement_id)) {
            return $this;
        } else {
            return false;
        }
    }

    public function updateByAssessmentIDAndAfelementID() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`assessment_id` = ".$this->assessment_id." AND `afelement_id` = ".$this->afelement_id)) {
            return $this;
        } else {
            return false;
        }
    }

    public function insertBulk($form_elements = array()) {
        global $db;

        if ($form_elements) {
            $query = "INSERT INTO `".DATABASE_NAME."`.`".static::$table_name."` (`assessment_id`, `afelement_id`, `weight`) values ";

            $number_of_elements = count($form_elements);

            foreach($form_elements as $i => $form_element) {
                $query .= "(".$db->qstr($form_element["assessment_id"]).", ".$db->qstr($form_element["afelement_id"]).", ".$db->qstr($form_element["weight"]).")";

                $query .= (($i + 1) < $number_of_elements) ? "," : "";
            }

            $result = $db->Execute($query);

            return $result;
        }        
    }
}