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
 * A model for handling Linked Objectives
 *
 * @author Organisation: Queen's University
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Objective_LinkedObjective extends Models_Base {
    protected $linked_objective_id, $version_id, $objective_id, $target_objective_id, $active;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "linked_objectives";
    protected static $primary_key = "linked_objective_id";
    protected static $default_sort_column = "linked_objective_id";

    public function getID() {
        return $this->linked_objective_id;
    }

    public function getLinkedObjectiveId() {
        return $this->linked_objective_id;
    }

    public function getVersionId() {
        return $this->version_id;
    }

    public function getObjectiveId() {
        return $this->objective_id;
    }

    public function getTargetObjectiveId() {
        return $this->target_objective_id;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public static function fetchAllByObjectiveID($objective_id, $version_id = null, $active = 1) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "objective_id", "method" => "=", "value" => $objective_id),
                array("key" => "version_id", "value" => ($version_id ? $version_id : NULL), "method" => ($version_id ? "=" : "IS")),
                array("key" => "active", "method" => "=", "value" => $active)
            )
        );
    }

    public static function fetchAllByTargetObjectiveID($target_objective_id, $active = 1) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "target_objective_id", "method" => "=", "value" => $target_objective_id),
                array("key" => "active", "method" => "=", "value" => $active)
            )
        );
    }

    public static function fetchRowByObjectiveIdTargetObjectiveID($objective_id, $target_id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_id", "method" => "=", "value" => $objective_id),
            array("key" => "target_objective_id", "method" => "=", "value" => $target_id),
            array("key" => "active", "method" => "=", "value" => $active)
        ));
    }

    public static function fetchAllByObjectiveSetIDAsObjectiveID($objective_set_id) {
        global $db;

        $objectives = array();
        $query = "SELECT a.*
                      FROM " . self::$database_name . ".`" . self::$table_name . "` a
                      INNER JOIN `global_lu_objectives` b
                        ON a.`objective_id` = b.`objective_id`
                      WHERE b.`objective_set_id` = ?
                        AND b.`objective_active` = 1";
        $rows = $db->GetAll($query, $objective_set_id);
        if ($rows) {
            foreach ($rows as $row) {
                $objectives[] = new self($row);
            }

            return $objectives;
        } else {
            return false;
        }
    }

    public static function fetchAllByObjectiveSetIDAsTargetObjectiveID($objective_set_id) {
        global $db;

        $objectives = array();
        $query = "SELECT a.*
                      FROM " . self::$database_name . ".`" . self::$table_name . "` a
                      INNER JOIN `global_lu_objectives` b
                        ON a.`target_objective_id` = b.`objective_id`
                      WHERE b.`objective_set_id` = ?
                        AND b.`objective_active` = 1";
        $rows = $db->GetAll($query, $objective_set_id);
        if ($rows) {
            foreach ($rows as $row) {
                $objectives[] = new self($row);
            }

            return $objectives;
        } else {
            return false;
        }
    }
}