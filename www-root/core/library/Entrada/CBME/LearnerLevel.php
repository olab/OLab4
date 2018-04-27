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
 * A class for learner level functionality
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Entrada_CBME_LearnerLevel extends Entrada_CBME_Base {
    /**
     * This function determines and returns a learners current stage based on the entries in cbl_learner_objectives_completion table
     * @param int $proxy_id
     * @param int $course_id
     * @return array
     */
    public function determineLearnerStage($proxy_id = 0, $course_id = 0) {
        $learner_stage = array();
        $stage = $this->fetchLearnerStage($proxy_id, $course_id);
        if ($stage) {
            $learner_stage = $this->fetchNextLearnerStage($stage["objective_id"]);
            if (!$learner_stage) {
                $learner_stage = $stage;
            }
        } else {
            $learner_stage = $this->fetchDefaultOrganisationStage();
        }
        return $learner_stage;
    }

    /**
     * This is a wrapper function to get a learners current stage
     * @param int $proxy_id
     * @param int $course_id
     * @param string $objective_set_shortname
     * @return array
     */
    private function fetchLearnerStage($proxy_id = 0, $course_id = 0, $objective_set_shortname = "stage") {
        $completed_stage = array();
        $objective_completion_model = new Models_Objective_Completion();
        $current_stage = $objective_completion_model->fetchCompletedObjectiveByProxyIDShortname($proxy_id, $course_id, $objective_set_shortname);
        if ($current_stage) {
            $completed_stage = $current_stage;
        }
        return $completed_stage;
    }

    /**
     * This function fetches the default starting stage based on the default_stage_objective system setting
     * @return array
     */
    private function fetchDefaultOrganisationStage() {
        $stage = array();
        $stage_objective_value = Entrada_Settings::fetchValueByShortname("default_stage_objective", $this->actor_organisation_id);
        if ($stage_objective_value) {
            $objective = Models_Objective::fetchRow($stage_objective_value);
            if ($objective) {
                $stage = $objective->toArray();
            }
        }
        return $stage;
    }

    /**
     * This function fetches the next learner stage
     * @param int $objective_id
     * @param string $shortname
     * @return array
     */
    private function fetchNextLearnerStage($objective_id = 0, $shortname = "stage") {
        $stage = array();
        $objective_completion_model = new Models_Objective_Completion();
        $next_stage = $objective_completion_model->fetchNextObjectiveToComplete($objective_id, $shortname);
        if ($next_stage) {
            $stage = $next_stage;
        }
        return $stage;
    }

    /**
     * This function fetches data associated with learner levels
     * @return array
     */
    public function fetchLearnerLevel() {
        $learner_level_data = array();
        $learner_level_model = new Models_User_LearnerLevel();
        $learner_level = $learner_level_model->fetchActiveLevelInfoByProxyIDOrganisationID($this->actor_proxy_id, $this->actor_organisation_id);
        if ($learner_level) {
            $learner_level_data["learner_level"] = $learner_level["title"];
            $learner_level_data["cbme_flag"] = isset($learner_level["cbme"]) ? $learner_level["cbme"] : 0;
        }
        return $learner_level_data;
    }
}