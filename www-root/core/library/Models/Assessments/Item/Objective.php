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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Item_Objective extends Models_Base {
    protected $aiobjective_id, $item_id, $objective_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_item_objectives";
    protected static $primary_key = "aiobjective_id";
    protected static $default_sort_column = "aiobjective_id";

    public $objective_tree = array();

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aiobjective_id;
    }

    public function getAiobjectiveID() {
        return $this->aiobjective_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($aiobjective_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aiobjective_id", "value" => $aiobjective_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecordsByItemID($item_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByItemID($item_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public function buildObjectiveList ($objective_parent = 0, $item_objective_id = 0) {
        if ($objective_parent === 0) {
            return 1;
        } else {
            if (!in_array($item_objective_id, $this->objective_tree)) {
                $this->objective_tree[$item_objective_id] = $item_objective_id;
            }

            $objective = Models_Objective::fetchRow($objective_parent);
            if ($objective && $objective->getParent() != 0) {
                $this->objective_tree[$objective->getID()] = $objective->getID();
                return $this->buildObjectiveList($objective->getParent());
            }
        }
    }

    public static function deleteByID($id) {
        if ($this_record = self::fetchRowByID($id)) {
            $this_record->setDeletedDate(time());
            return $this_record->update();
        }
        return false;
    }
}