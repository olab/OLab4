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
 * A model to handle quiz progress responses
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_Progress_Response extends Models_Base {
    
    protected $qpresponse_id, $qprogress_id, $aquiz_id, $content_type, $content_id, $quiz_id, $proxy_id, $qquestion_id, $qqresponse_id, $updated_date, $updated_by;
    
    protected static $table_name = "quiz_progress_responses";
    protected static $default_sort_column = "qpresponse_id";
    protected static $primary_key = "qpresponse_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($qprogress_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "qprogress_id", "value" => $qprogress_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($aquiz_id) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "aquiz_id",
                "value"     => $aquiz_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public function getQpresponseID() {
        return $this->qpresponse_id;
    }

    public function getQprogressID() {
        return $this->qprogress_id;
    }

    public function getAquizID() {
        return $this->aquiz_id;
    }

    public function getContentType() {
        return $this->content_type;
    }

    public function getContentID() {
        return $this->content_id;
    }

    public function getQuizID() {
        return $this->quiz_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getQquestionID() {
        return $this->qquestion_id;
    }

    public function getQqresponseID() {
        return $this->qqresponse_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
}
