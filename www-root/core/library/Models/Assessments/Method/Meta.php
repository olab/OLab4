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
 * A model for handling the assessment method meta.
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Method_Meta extends Models_Base {

    protected $amethod_meta_id;
    protected $assessment_method_id;
    protected $group;
    protected $title;
    protected $description;
    protected $instructions;
    protected $button_text;
    protected $skip_validation;
    protected $assessment_cue;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessment_method_group_meta";
    protected static $primary_key = "amethod_meta_id";
    protected static $default_sort_column = "assessment_method_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->amethod_meta_id;
    }

    public function getAmethodMetaID() {
        return $this->amethod_meta_id;
    }

    public function setAmethodMetaID($amethod_meta_id) {
        $this->amethod_meta_id = $amethod_meta_id;
    }

    public function getAssessmentMethodID() {
        return $this->assessment_method_id;
    }

    public function setAssessmentMethodID($assessment_method_id) {
        $this->assessment_method_id = $assessment_method_id;
    }

    public function getGroup() {
        return $this->group;
    }

    public function setGroup($group) {
        $this->group = $group;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getInstructions() {
        return $this->instructions;
    }

    public function setInstructions($instructions) {
        $this->instructions = $instructions;
    }

    public function getSkipValidation() {
        return $this->skip_validation;
    }

    public function setSkipValidation($skip_validation) {
        $this->skip_validation = $skip_validation;
    }

    public function getAssessmentCue() {
        return $this->assessment_cue;
    }

    public function setAssessmentCue($assessment_cue) {
        $this->assessment_cue = $assessment_cue;
    }

    public function getButtonText() {
        return $this->button_text;
    }

    public function setButtonText($button_text) {
        $this->button_text = $button_text;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($amethod_meta_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "amethod_meta_id", "method" => "=", "value" => $amethod_meta_id)
        ));
    }

    public static function fetchRowByAssessmentMethodIDGroup($assessment_method_id, $group) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_method_id", "method" => "=", "value" => $assessment_method_id),
            array("key" => "group", "method" => "=", "value" => $group)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "amethod_meta_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        global $ENTRADA_USER;

        $this->deleted_date = time();
        $this->updated_date = time();
        $this->updated_by = $ENTRADA_USER->getActiveId();

        return $this->update();
    }

}