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
    protected $objective_set_id,
        $code,
        $title,
        $description,
        $shortname,
        $start_date,
        $end_date,
        $standard,
        $languages,
        $requirements,
        $maximum_levels,
        $short_method,
        $long_method,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date;

    protected static $table_name = "global_lu_objective_sets";
    protected static $primary_key = "objective_set_id";
    protected static $default_sort_column = "title";

    public function getID () {
        return $this->objective_set_id;
    }

    public function getCode () {
        return $this->code;
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

    public function getLanguages() {
        return $this->languages;
    }

    public function getRequirements() {
        return $this->requirements;
    }

    public function getMaximumLevels() {
        return $this->maximum_levels;
    }

    public function getShortMethod() {
        return $this->short_method;
    }

    public function getLongMethod() {
        return $this->long_method;
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

    public function setUpdatedDate ($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy ($id) {
        $this->updated_by = $id;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
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

    /**
     * Fetch all objectives of the given objective set that have no parent (other than the top-level objective set record).
     *
     * @param $shortname
     * @param $organisation_id
     * @return array
     */
    public function fetchAllParentObjectivesByShortname($shortname, $organisation_id, $order_by = "") {
        global $db;
        $order_by = clean_input($order_by, array("trim", "striptags"));
        $objective_sets = array();
        $query = "SELECT b.*
                  FROM `global_lu_objective_sets` AS a
                  JOIN `global_lu_objectives` AS b ON a.`objective_set_id` = b.`objective_set_id`
                  JOIN `objective_organisation` AS c ON b.`objective_id` = c.`objective_id`

                  WHERE a.`shortname` = ?
                  AND c.`organisation_id` = ?
                  AND b.`objective_parent` = (
                      SELECT sub_obj.`objective_id`
                      FROM `global_lu_objectives` as sub_obj
                      JOIN `global_lu_objective_sets` AS sub_set ON sub_obj.`objective_set_id` = sub_set.`objective_set_id`
                      JOIN `objective_organisation` AS sub_org ON sub_obj.`objective_id` = sub_org.`objective_id`
                      WHERE (sub_obj.`objective_parent` = 0 OR sub_obj.`objective_parent` IS NULL)
                      AND sub_set.`shortname` = ?
                      AND sub_org.`organisation_id` = ?
                      LIMIT 1
                  )";

        if ($order_by) {
            $query .= "ORDER BY ". $order_by;
        } else {
            $query .= "ORDER BY b.`objective_order` ASC";
        }

        $results = $db->GetAll($query, array($shortname, $organisation_id, $shortname, $organisation_id));
        return $results;
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

    public function fetchAllActiveByOrganisationID ($organisation_id = 0) {
        global $db;

        $objective_sets = array();

        $results = $db->GetAll("  SELECT a.* FROM `global_lu_objective_sets` AS a
                                  JOIN `global_lu_objectives` AS b
                                    ON a.`objective_set_id` = b.`objective_set_id`
                                  JOIN `objective_organisation` AS c
                                    ON b.`objective_id` = c.`objective_id`
                                  WHERE c.`organisation_id` = ?
                                    AND a.`deleted_date` IS NULL
                                  GROUP BY a.`objective_set_id`", $organisation_id);
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objective_sets[] =  $self;
            }
        }

        return $objective_sets;
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

    public function fetchRowByShortname ($shortname = null, $deleted_date = null, $ignore_deleted_date = false) {
        $self = new self();
        $constraints = array(
            array("key" => "shortname", "method" => "=", "value" => $shortname)
        );
        if (!$ignore_deleted_date) {
            $constraints [] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));
        }

        return $self->fetchRow($constraints);
    }

    public function checkForObjectiveSetsObjectives ($objective_sets, $objectives, $organisation_id) {
        $objective_set_model = new Models_ObjectiveSet();
        $objective_model = new Models_Objective();
        if (is_array($objective_sets) && !empty($objective_sets)) {
            foreach ($objective_sets as $objective_set) {
                $obj_set = $objective_set_model->fetchRowByShortname($objective_set["shortname"]);
                if ($obj_set && $objective_model->fetchRowBySetIDParentID($obj_set->getID(), 0, $organisation_id)) {
                    if (isset($objectives[$obj_set->getShortname()])) {
                        foreach ($objectives[$obj_set->getShortname()] as $tag) {
                            if (!$objective_model->fetchRowBySetIDCodeName($obj_set->getID(), $tag["objective_code"], $tag["objective_name"], $organisation_id)) {
                                return false;
                            }
                        }
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}