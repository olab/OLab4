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
 * A Model for handling questions versions highlights
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */


class Models_Exam_Exam_Element_Highlight extends Models_Base {
    protected
        $highlight_id,
        $exam_element_id,
        $element_text,
        $exam_progress_id,
        $proxy_id,
        $updated_date,
        $updated_by;

    protected static $table_name = "exam_element_highlight";
    protected static $primary_key = "highlight_id";
    protected static $default_sort_column = "highlight_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->highlight_id;
    }

    public function getExamElementID() {
        return $this->exam_element_id;
    }

    public function getElementText() {
        return $this->element_text;
    }

    public function getExamProgressID() {
        return $this->exam_progress_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setElementText($element_text) {
        $this->element_text = $element_text;
    }

    public function setUpdatedDate($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    /* @return bool|Models_Exam_Question_Version_Highlight */
    public static function fetchRowByHighlightID($highlight_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "highlight_id", "value" => $highlight_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Exam_Question_Version_Highlight */
    public static function fetchRowByProgressIdProxyIdElementId($exam_progress_id, $proxy_id, $exam_element_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "exam_progress_id", "value" => $exam_progress_id, "method" => "="),
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "exam_element_id", "value" => $exam_element_id, "method" => "=")
        ));
    }
}