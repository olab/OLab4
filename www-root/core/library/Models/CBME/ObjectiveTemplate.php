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
 * A model for handling CBME Objective templates
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_CBME_ObjectiveTemplate extends Models_Base {
    protected $objective_template_id, $objective_id, $objective_parent, $start_date, $end_date, $order, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbme_objective_templates";
    protected static $primary_key = "objective_template_id";
    protected static $default_sort_column = "objective_template_id";

    public function getID () {
        return $this->objective_template_id;
    }

    public function getObjectiveID () {
        return $this->objective_id;
    }

    public function getObjectiveParent () {
        return $this->objective_parent;
    }

    public function getStartDate () {
        return $this->start_date;
    }

    public function getEndDate () {
        return $this->end_date;
    }

    public function getOrder () {
        return $this->order;
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
}