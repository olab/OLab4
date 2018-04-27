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
 * A model for handling assessments' form types
 *
 * @author Organisation: 
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Assessments_Form_Types extends Models_Base {
    protected $aftype_id, $title, $description, $active, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_assessment_form_types";
    protected static $primary_key = "aftype_id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aftype_id;
    }

    public function getAftypeID() {
        return $this->aftype_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getActive() {
        return $this->active;
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

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public static function fetchRowByID($aftype_id, $active=1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aftype_id", "value" => $aftype_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active=1) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchAllByOrganisation($organisation_id, $active=1) {
        global $db;

        if (!intval($organisation_id)) {
            return false;
        }

        $query = "SELECT a.aftype_id FROM `".DATABASE_NAME."`.`".self::$table_name."` a
                  JOIN `".DATABASE_NAME."`.`cbl_assessment_form_type_organisation` b ON a.`aftype_id` = b.`aftype_id`
                  WHERE b.`organisation_id` = ?
                  AND a.active = 1";

        $results = $db->getCol($query, array($organisation_id, $active));
        if (!$results || ! is_array($results) || !count($results)) {
            return false;
        }

        $return = array();
        foreach ($results as $result) {
            $return[] = self::fetchRowByID($result, $active);
        }

        return $return;
    }
}