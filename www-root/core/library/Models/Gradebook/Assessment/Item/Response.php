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
 * A model for storing a score for a given item response in an assessment form
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Item_Response extends Models_Base {
    protected $gairesponse_id, $assessment_id, $iresponse_id, $score;

    protected static $table_name = "gradebook_assessment_item_responses";
    protected static $primary_key = "gairesponse_id";
    protected static $default_sort_column = "assessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->gairesponse_id;
    }

    public function setID($id) {
        return $this->setGairesponseID($id);
    }

    public function getGairesponseID() {
        return $this->gairesponse_id;
    }

    public function setGairesponseID($gairesponse_id) {
        $this->gairesponse_id = $gairesponse_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getIresponseID() {
        return $this->iresponse_id;
    }

    public function getScore() {
        return $this->score;
    }

    public static function fetchRowByID($gairesponse_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "gairesponse_id", "value" => $gairesponse_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "gairesponse_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Fetches all records by assessment ID
     * @return array db results
     */
    public function fetchAllByAssessmentID() {
        return $this->fetchAll(array(array("key" => "assessment_id", "value" => $this->assessment_id, "method" => "=")));
    }

    public function insertBulk($item_responses = array()) {
        global $db;

        if ($item_responses) {
            $query = "INSERT INTO `".DATABASE_NAME."`.`".static::$table_name."` (`assessment_id`, `iresponse_id`, `score`) values ";

            $number_of_elements = count($item_responses);

            foreach($item_responses as $i => $item_response) {
                $query .= "(".$db->qstr($item_response["assessment_id"]).", ".$db->qstr($item_response["iresponse_id"]).", ".$db->qstr($item_response["score"]).")";

                $query .= (($i + 1) < $number_of_elements) ? "," : "";
            }

            $result = $db->Execute($query);

            return $result;
        }        
    }
}