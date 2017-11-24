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
 * A model to handle learning object tags.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_LearningObject_Tag extends Models_Base {
    
    protected $lo_file_tag_id, $lo_file_id, $tag, $updated_date, $updated_by, 
              $active = 1;
    
    protected static $table_name = "learning_object_file_tags";
    protected static $default_sort_column = "tag";
    protected static $primary_key = "lo_file_tag_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($lo_file_tag_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "lo_file_tag_id", "value" => $lo_file_tag_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecordsByFileID($lo_file_id, $active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "lo_file_id",
                "value"     => $lo_file_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
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
    
    public function getLoFileTagID() {
        return $this->lo_file_tag_id;
    }

    public function getLoFileID() {
        return $this->lo_file_id;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getActive() {
        return $this->active;
    }

}

?>
