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
 * 
 *
 * @author Organisation: University of British Columbia
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Week extends Models_Base {
    protected $week_id, $curriculum_type_id, $week_title, $week_order, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "weeks";
    protected static $primary_key = "week_id";
    protected static $default_sort_column = "week_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->week_id;
    }

    public function getWeekID() {
        return $this->week_id;
    }

    public function getCurriculumTypeId() {
        return $this->curriculum_type_id;
    }

    public function getWeekTitle() {
        return $this->week_title;
    }

    public function getWeekOrder() {
        return $this->week_order;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    protected static function activeConstraint() {
        return array("key" => "deleted_date", "value" => null, "method" => "IS");
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => static::$primary_key, "value" => $id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => static::$primary_key, "value" => 0, "method" => ">="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllByIDs(array $week_ids) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => static::$primary_key, "value" => $week_ids, "method" => "IN"),
            static::activeConstraint()
        ));
    }

    public static function removeAllByIDs(array $ids) {
        global $db, $ENTRADA_USER;
        if ($ids) {
            $query = "
                UPDATE `" . static::$table_name . "`
                SET `deleted_date` = ?,
                `updated_date` = ?,
                `updated_by` = ?
                WHERE `" . static::$primary_key . "` IN (" . implode(", ", $ids) . ")";
            $time = time();
            $user_id = $ENTRADA_USER->getID();
            if ($db->Execute($query, array($time, $time, $user_id))) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public static function fetchAllByCurriculumType($curriculum_type_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "curriculum_type_id", "value" => $curriculum_type_id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllByOrganisationID($organisation_id) {
        if (!is_int($organisation_id)) {
            throw new InvalidArgumentException();
        }
        if (($curriculum_types = Models_Curriculum_Type::fetchAllByOrg($organisation_id))) {
            $weeks = array();
            foreach ($curriculum_types as $curriculum_type) {
                $my_weeks = self::fetchAllByCurriculumType($curriculum_type->getID());
                $weeks = array_merge($weeks, $my_weeks);
            }
            return $weeks;
        } else {
            return array();
        }
    }
}
/* vim: set expandtab: */
