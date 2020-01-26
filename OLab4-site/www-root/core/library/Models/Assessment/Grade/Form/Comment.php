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
 * A model for handling comments that are entered as part of grading an assessment
 *
 * @author Organisation: 
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Assessment_Grade_Form_Comment extends Models_Base {
    protected $agfcomment_id, $gafelement_id, $assessment_id, $proxy_id, $comment;

    protected static $table_name = "assessment_grade_form_comments";
    protected static $primary_key = "agfcomment_id";
    protected static $default_sort_column = "agfcomment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->agfcomment_id;
    }

    public function getAgfcommentID() {
        return $this->agfcomment_id;
    }

    public function getGafelementID() {
        return $this->gafelement_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getComment() {
        return $this->comment;
    }

    public static function fetchRowByID($agfcomment_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "agfcomment_id", "value" => $agfcomment_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "agfcomment_id", "value" => 0, "method" => ">=")));
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
     * Inserts new comments. Requires a proxy_id and assessment_id
     * @param  array  $comments array("$gafelement_id" => "comment")
     * @return boo;
     */
    public function insertNewComments($comments = array()) {
        if ($this->proxy_id && $this->assessment_id && is_array($comments)) {
            foreach ($comments as $gafelement_id => $comment) {
                $proxy_id = $this->proxy_id;
                $assessment_id = $this->assessment_id;

                $record = new Models_Assessment_Grade_Form_Comment(array(
                    "gafelement_id" => $gafelement_id,
                    "assessment_id" => $assessment_id,
                    "proxy_id" => $proxy_id,
                    "comment" => $comment
                ));

                $record->insert();
            }

            return true;
        }

        return false;
    }
}