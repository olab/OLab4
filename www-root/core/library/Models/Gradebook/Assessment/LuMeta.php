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
 * A model to handle gradebook assessments Assessment_LuMeta
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */
class Models_Gradebook_Assessment_LuMeta extends Models_Base {

    protected   $id,
                $organisation_id,
                $type,
                $title,
                $description,
                $active;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name          = "assessments_lu_meta";
    protected static $primary_key         = "id";
    protected static $default_sort_column = "type";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getType() {
        return $this->type;
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

    /* @return bool|Models_Gradebook_Assessment_LuMeta */
    public static function fetchRowByID($id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "id", "value" => $id, "method" => "=", "mode" => "AND"),
                array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
            )
        );
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_LuMeta[] */
    public static function fetchAllByOrganisationId($id, $active = 1) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "organisation_id", "value" => $id, "method" => "=", "mode" => "AND"),
                array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
            ), "=", "AND", self::$default_sort_column, "ASC", "title", "ASC"
        );
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_LuMeta[] */
    public static function fetchRowByOrganisationIdTypeTitle($id, $type, $title, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "organisation_id", "value" => $id, "method" => "=", "mode" => "AND"),
            array("key" => "type", "value" => $type, "method" => "=", "mode" => "AND"),
            array("key" => "title", "value" => $title, "method" => "=", "mode" => "AND"),
            array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
        ), "=", "AND"
        );
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_LuMeta[] */
    public static function fetchAllRecords($active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
}