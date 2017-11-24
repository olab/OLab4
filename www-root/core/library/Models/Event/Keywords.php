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
 * A model to handle event keywords (mesh)
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */
class Models_Event_Keywords extends Models_Base {

    protected   $ekeyword_id,
                $event_id,
                $keyword_id,
                $keyword,
                $updated_date,
                $updated_by;
    
    protected static $table_name           = "event_keywords";
    protected static $primary_key          = "ekeyword_id";
    protected static $default_sort_column  = "ekeyword_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
        $this->keyword = null;
    }

    public function getID() {
        return $this->ekeyword_id;
    }

    public function getEventKeywordID() {
        return $this->ekeyword_id;
    }
    
    public function getEventID() {
        return $this->event_id;
    }
    
    public function getKeywordID() {
        return $this->keyword_id;
    }
    
    public function getKeyword() {
        global $db;
        if ($this->keyword === null) {
            $query = "
                SELECT `descriptor_name`
                FROM `mesh_descriptors`
                WHERE `descriptor_ui` = ".$db->qstr($this->keyword_id);
            $this->keyword = $db->GetOne($query);
        }
        return $this->keyword;
    }
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public static function fetchAllByEventID($event_id) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "event_id",
                "value"     => $event_id,
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
}