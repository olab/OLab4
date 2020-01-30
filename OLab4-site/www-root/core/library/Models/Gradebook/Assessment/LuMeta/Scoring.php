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
 * A model for handling Assessment scoring methods
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2017 Regents of The University of California. All Rights Reserved.
 */

class Models_Gradebook_Assessment_LuMeta_Scoring extends Models_Base {

    protected $id;
    protected $title;
    protected $short_name;
    protected $active;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "assessments_lu_meta_scoring";
    protected static $primary_key = "id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    public function getShortName() {
        return $this->short_name;
    }

    public function setShortName($short_name) {
        $this->short_name = $short_name;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /* @return bool|Models_Gradebook_Assessment_LuMeta_Scoring */
    public static function fetchRowByID($id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "method" => "=", "value" => $id),
            array("key" => "active", "method" => "=", "value" => $active)
        ));
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_LuMeta_Scoring[] */
    public static function fetchAllRecords($active = 1) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "method" => "=", "value" => $active)));
    }

}