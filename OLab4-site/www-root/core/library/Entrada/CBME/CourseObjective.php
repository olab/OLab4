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
 * This is an abstraction layer for Course CBME Objectives.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_CBME_CourseObjective extends Entrada_CBME_Base {

    public function fetchObjectiveSetsByCourse($organisation_id, $course_id = null, $proxy_id = null) {
        global $translate;
        $data = array();

        // EPAs.
        if ($course_id && $proxy_id) {
            $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $proxy_id, "actor_organisation_id" => $organisation_id, "course_id" => $course_id));
            $epas = $tree_object->fetchTreeNodesAtDepth(0, "o.objective_code", true);
            if ($epas) {
                foreach ($epas as $objective) {
                    $objective["objective_code"] = $translate->_("EPAs");
                    $objective["objective_name"] = "";
                    $objective["node_id"] = $objective["cbme_objective_tree_id"];
                    $children = $tree_object->fetchBranch($objective["node_id"], ($objective["depth"] + 1), "o.objective_code");
                    $objective["has_children"] = $children && !empty($children) ? true : false;
                    $data[] = $objective;
                }
            }
        }

        // Organisation curriculum tag sets.
        $child_objectives = Models_Objective::fetchAllByParentIDObjectiveAudienceNotCourse($organisation_id);

        if ($child_objectives) {
            foreach ($child_objectives as $objective) {
                $objective = $objective->toArray();
                $children = Models_Objective::fetchAllByParentID($organisation_id, $objective["objective_id"]);
                if (empty($children)) {
                    continue;
                }
                $objective["has_children"] = $children && !empty($children) ? true : false;
                $objective["node_id"] = null;
                $objective["depth"] = null;
                $objective["course_id"] = null;
                $data[] = $objective;
            }
        }

        return $data;
    }
}
