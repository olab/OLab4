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

class Models_Assessments_Item_Tag extends Models_Base {
    protected $aitag_id, $item_id, $tag_id;

    protected static $table_name = "cbl_assessment_item_tags";
    protected static $primary_key = "aitag_id";
    protected static $default_sort_column = "aitag_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aitag_id;
    }

    public function getAitagID() {
        return $this->aitag_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getTagID() {
        return $this->tag_id;
    }

    public static function fetchRowByID($aitag_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aitag_id", "value" => $aitag_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "aitag_id", "value" => 0, "method" => ">=")));
    }
}