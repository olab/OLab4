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
 * A model for handling Assessment Marking Schemes
 *
 * @author Organisation: Queen's University
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Marking_Scheme extends Models_Base {

    protected $id;
    protected $name;
    protected $handler;
    protected $description;
    protected $enabled;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "assessment_marking_schemes";
    protected static $primary_key = "id";
    protected static $default_sort_column = "name";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function setHandler($handler) {
        $this->handler = $handler;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getEnabled() {
        return $this->enabled;
    }

    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }

    /* @return bool|Models_Gradebook_Assessment_Marking_Scheme */
    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "method" => "=", "value" => $id)
        ));
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_Marking_Scheme[] */
    public static function fetchAllRecords($enabled = 1) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "enabled", "method" => "=", "value" => $enabled)));
    }

}