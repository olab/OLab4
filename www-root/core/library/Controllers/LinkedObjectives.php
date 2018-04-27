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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Controllers_LinkedObjectives {

    public static function processLinkedObjectives() {
        if (isset($_POST["linked_objectives"]) && is_array($_POST["linked_objectives"])) {
            $linked_objectives = array();
            $target_objectives = self::processTargetObjectives();
            foreach ($_POST["linked_objectives"] as $objective_id => $target_objective_ids) {
                if (((int) $objective_id) && is_array($target_objective_ids)) {
                    foreach ($target_objective_ids as $target_objective_id) {
                        if ((int) $target_objective_id && isset($target_objectives[$target_objective_id])) {
                            $linked_objectives[$objective_id][$target_objective_id] = $target_objectives[$target_objective_id];
                        }
                    }
                }
            }
            return $linked_objectives;
        } else {
            return array();
        }
    }

    private static function processTargetObjectives() {
        $objective_repository = Models_Repository_Objectives::getInstance();
        return array_reduce($_POST["linked_objectives"], function (array $target_objectives, array $target_objective_ids) use ($objective_repository) {
            $new_target_objective_ids = array_diff($target_objective_ids, array_keys($target_objectives));
            return $target_objectives + $objective_repository->fetchAllByIDs($new_target_objective_ids);
        }, array());
    }
}
