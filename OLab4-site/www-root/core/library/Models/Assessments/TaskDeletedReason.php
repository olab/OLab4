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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_TaskDeletedReason extends Models_Base {
    protected $reason_id, $order_id, $reason_details, $notes_required, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_lu_task_deleted_reasons";
    protected static $primary_key = "reason_id";
    protected static $default_sort_column = "reason_details";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->reason_id;
    }

    public function getOrderID() {
        return $this->order_id;
    }

    public function getDetails() {
        return $this->reason_details;
    }

    public function getNotesRequired() {
        return $this->notes_required;
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

    public static function fetchRowByID($reason_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "reason_id", "value" => $reason_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

	public static function fetchAllRecords($deleted_date = NULL) {
		$self = new self();
		$constraints = array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")));
        $result = $self->fetchAll($constraints);
		return $result;
	}

    /**
     * Fetch all records sorted by order ID.
     *
     * @param null $deleted_date
     * @param string $sort_order
     * @return array
     */
	public static function fetchAllRecordsOrderByOrderID($deleted_date = NULL, $sort_order = "ASC") {
		$self = new self();

		$constraints = array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")));
		$result = $self->fetchAll($constraints, "=", "AND", "order_id", $sort_order);
		return $result;
	}
}