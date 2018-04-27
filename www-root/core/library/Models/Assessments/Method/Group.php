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
 * A model for handling Assessment Type Groups
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Method_Group extends Models_Base {
    protected $amethod_group_id, $assessment_method_id, $group, $admin;

    protected static $table_name = "cbl_assessment_method_groups";
    protected static $primary_key = "amethod_group_id";
    protected static $default_sort_column = "amethod_group_id";

    public function getID() {
        return $this->amethod_group_id;
    }

    public function getAssessmentMethodID() {
        return $this->assessment_method_id;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getAdmin() {
        return $this->admin;
    }
}