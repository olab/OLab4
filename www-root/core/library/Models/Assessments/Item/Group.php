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
 * A model for handling Item Groups
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Item_Group extends Models_Base {
    protected $item_group_id, $form_type_id, $shortname, $title, $description, $active, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $item_type;

    protected static $table_name = "cbl_assessments_lu_item_groups";
    protected static $primary_key = "item_group_id";
    protected static $default_sort_column = "form_type_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->item_group_id;
    }

    public function getItemGroupID() {
        return $this->item_group_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getItemType() {
        return $this->item_type;
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

    public static function fetchRowByID($item_group_id, $active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_group_id", "value" => $item_group_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    public static function fetchRowByIDIgnoreActive($item_group_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_group_id", "value" => $item_group_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($active = 1) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    public static function fetchRowByShortname($shortname) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "shortname", "value" => $shortname, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
        ));
    }

    /**
     * Fetch all item groups by the form type and shortname
     * @param $form_type_id
     * @param $shortname
     * @return mixed
     */
    public function fetchItemGroupsByFormTypeAndShortname($form_type_id, $shortname) {
        global $db;
        $shortname = "%" . $shortname . "%";
        $query = "  SELECT * FROM `cbl_assessments_lu_item_groups`
                    WHERE form_type_id = ?
                    AND `shortname` LIKE ?";
        return $db->getAll($query, array($form_type_id, $shortname));
    }

    /**
     * Fetch an item group by the form type and shortname
     * @param $form_type_id
     * @param $shortname
     * @return mixed
     */
    public function fetchItemGroupByFormTypeAndShortname($form_type_id, $shortname) {
        global $db;
        $shortname = "%" . $shortname . "%";
        $query = "  SELECT * FROM `cbl_assessments_lu_item_groups`
                    WHERE form_type_id = ?
                    AND `shortname` LIKE ?";
        return $db->getRow($query, array($form_type_id, $shortname));
    }
}