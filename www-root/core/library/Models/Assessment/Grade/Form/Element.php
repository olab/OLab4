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
 * Model for storing proxy scores for a given gairesponse_id
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc. All Rights Reserved.
 */

class Models_Assessment_Grade_Form_Element extends Models_Base {
    protected $agfelement_id, $gairesponse_id, $assessment_id, $proxy_id, $score;

    protected static $table_name = "assessment_grade_form_elements";
    protected static $primary_key = "agfelement_id";
    protected static $default_sort_column = "agfelement_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->agfelement_id;
    }

    public function getAgfelementID() {
        return $this->agfelement_id;
    }

    public function getGairesponseID() {
        return $this->gairesponse_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getScore() {
        return $this->score;
    }

    public static function fetchRowByID($agfelement_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "agfelement_id", "value" => $agfelement_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "agfelement_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Deletes all rows with a given proxy id and assessment id
     * @return bool
     */
    public function deleteAllByProxyIDAssessmentID() {
        global $db;

        $query = "DELETE FROM `".static::$table_name."`
                    WHERE `proxy_id` = ?
                    AND `assessment_id` = ?";

        $result = $db->Execute($query, array($this->proxy_id, $this->assessment_id));

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Inserts new scores. Requires a proxy_id and assessment_id
     * @param  array  $scores array("gairesponse_id" => "score")
     * @return 
     */
    public function insertNewScores($scores = array()) {
        global $db;

        if ($this->proxy_id && $this->assessment_id && is_array($scores)) {

            $query = "INSERT INTO `".DATABASE_NAME."`.`".static::$table_name."` (`gairesponse_id`, `assessment_id`, `proxy_id`, `score`) values ";

            $number_of_elements = count($scores);
            $i = 0;

            foreach ($scores as $gairesponse_id => $score) {
                $query .= "(".$db->qstr($gairesponse_id).", ".$db->qstr($this->assessment_id).", ".$db->qstr($this->proxy_id).", ".$db->qstr($score).")";
                $query .= (($i + 1) < $number_of_elements) ? "," : "";

                $i++;
            }

            $result = $db->Execute($query);

            return $result;
        }

        return false;
    }
}