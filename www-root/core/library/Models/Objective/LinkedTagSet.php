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
 * @author Organisation: University of British Columbia
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Objective_LinkedTagSet extends Models_Base {
    protected $linked_tag_set_id, $organisation_id, $type, $objective_id, $target_objective_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "linked_tag_sets";
    protected static $primary_key = "linked_tag_set_id";
    protected static $default_sort_column = "linked_tag_set_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->linked_tag_set_id;
    }

    public function getLinkedTagSetID() {
        return $this->linked_tag_set_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getType() {
        return $this->type;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getTargetObjectiveID() {
        return $this->target_objective_id;
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

    public static function fetchRowByID($linked_tag_set_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "linked_tag_set_id", "value" => $linked_tag_set_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "linked_tag_set_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByTypeAndOrganisationID($type, $organisation_id) {
        if (!in_array($type, array('event', 'course_unit', 'course'))) {
            throw new InvalidArgumentException("Invalid linked tag set type");
        }
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "type", "value" => $type, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
        ));
    }

    public static function fetchAllByTypeAndOrganisationIDAndTagSetID($type, $organisation_id, $objective_id) {
        if (!in_array($type, array('event', 'course_unit', 'course'))) {
            throw new InvalidArgumentException("Invalid linked tag set type");
        }
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "type", "value" => $type, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
        ));
    }

    public static function fetchAllowedTagSetIDs($type, $organisation_id) {
        $linked_tag_sets = Models_Objective_LinkedTagSet::fetchAllByTypeAndOrganisationID($type, $organisation_id);
        $allowed_tag_set_ids = array_reduce($linked_tag_sets, function (array $allowed_tag_set_ids, Models_Objective_LinkedTagSet $linked_tag_set) {
            $tag_set_id = $linked_tag_set->getObjectiveID();
            $target_tag_set_id = $linked_tag_set->getTargetObjectiveID();
            $allowed_tag_set_ids[$tag_set_id][$target_tag_set_id] = $target_tag_set_id;
            return $allowed_tag_set_ids;
        }, array());
        return $allowed_tag_set_ids;
    }
}
