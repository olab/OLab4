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
 * A model to handle Objective Set.
 *
 * @author Organisation:
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_ObjectiveSet extends Models_Base {
    protected $objective_set_id, $title, $description, $shortname, $start_date, $end_date, $standard, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "global_lu_objective_sets";
    protected static $primary_key = "objective_set_id";
    protected static $default_sort_column = "title";

    public function getID () {
        return $this->objective_set_id;
    }

    public function getTitle () {
        return $this->title;
    }

    public function getDescription () {
        return $this->description;
    }

    public function getShortname () {
        return $this->shortname;
    }

    public function getStartDate () {
        return $this->start_date;
    }

    public function getEndDate () {
        return $this->end_date;
    }

    public function getStandard () {
        return $this->standard;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public function fetchRowByID ($objective_set_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_set_id", "value" => $objective_set_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchAllByStandardOrganisationID ($standard = null, $organisation_id = null) {
        global $db;

        $objective_sets = array();
        $query = "  SELECT a.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`standard` = ?
                    AND c.`organisation_id` = ?
                    GROUP BY a.`objective_set_id`";

        $results = $db->GetAll($query, array($standard, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objective_sets[] =  $self;
            }
        }

        return $objective_sets;
    }

    public function getAdvancedSearchData ($objective_sets = array()) {
        $search_targets = array();
        if ($objective_sets) {
            foreach ($objective_sets as $objective_set) {
                $search_targets[] = array("target_id" => $objective_set->getShortname(), "target_label" => $objective_set->getTitle());
            }
        }

        return $search_targets;
    }

    public function fetchRowByIDObjectiveParent ($objective_set_id = null, $organisation_id = null) {
        global $db;

        $objective_set = false;
        $query = "  SELECT a.*, b.`objective_id` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`objective_set_id` = ?
                    AND b.`objective_parent` = 0
                    AND c.`organisation_id` = ?";

        $results = $db->GetAll($query, array($objective_set_id, $organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $objective_set = $result;
            }
        }

        return $objective_set;
    }

    public function fetchRowByShortname ($shortname = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "shortname", "method" => "=", "value" => $shortname),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}