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
 * A model for handling Objective Organisations
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Objective_TranslationStatus extends Models_Base {
    protected $objective_translation_status_id, $objective_translation_status_description, $updated_date, $updated_by;

    protected static $table_name = "objective_translation_status";
    protected static $primary_key = "objective_translation_status_id";
    protected static $default_sort_column = "objective_translation_status_description";

    public function getID () {
        return $this->objective_translation_status_id;
    }

    public function getDescription () {
        return $this->objective_translation_status_description;
    }

    public static function fetchRowByID ($objective_translation_status_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_translation_status_id", "method" => "=", "value" => $objective_translation_status_id)
        ));
    }

    public static function fetchAllStatuses() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "objective_translation_status_description", "value" => "", "method" => "IS NOT")));
    }
}