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

class Models_Assessments_Tag extends Models_Base {
    protected $tag_id, $tag, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_tags";
    protected static $primary_key = "tag_id";
    protected static $default_sort_column = "tag_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->tag_id;
    }

    public function getTagID() {
        return $this->tag_id;
    }

    public function getTag() {
        return $this->tag;
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

    public static function fetchRowByID($tag_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tag_id", "value" => $tag_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }
    
    public static function fetchAllRecordsByItemID($item_id) {
        global $db;
        $tags = false;
        
        $query = "  SELECT * FROM `cbl_assessment_item_tags` AS a
                    JOIN `cbl_assessments_lu_tags` AS b
                    ON a.`tag_id` = b.tag_id
                    WHERE a.`item_id` = ?
                    AND b.`deleted_date` IS NULL";
        
        $results = $db->GetAll($query, $item_id);
        foreach ($results as $result) {
            $tag = new Models_Assessments_Tag(array("tag_id" => $result["tag_id"], "tag" => $result["tag"], "created_date" => $result["created_date"], "deleted_date" => $result["deleted_date"]));
            $tags[] = $tag;
        }
        
        return $tags;
    }
}