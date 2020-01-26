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
 * This is the base class for all cbme related functionality.
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Entrada_CBME_Base extends Entrada_Base {
    /**
     * Required
     */
    protected $actor_proxy_id = null;
    protected $actor_organisation_id = null;
    protected $course_id;

    private $error_messages = array();              // Flat list of localized error messages (strings)

    /**
     * Return an array containing the current actor for related abstraction layer constructors.
     * Optionally add more construction options to the array via $additional_properties.
     *
     * @param array $additional_properties
     * @return array
     */
    protected function buildActorArray($additional_properties = array()) {
        $actor = array("actor_proxy_id" => $this->actor_proxy_id, "actor_organisation_id" => $this->actor_organisation_id);
        return array_merge($actor, $additional_properties);
    }

    /**
     * @return array
     */
    public function fetchStages() {
        $objective_model = new Models_Objective();
        $stages = $objective_model->fetchChildrenByObjectiveSetShortname("stage", $this->actor_organisation_id);
        return $stages;
    }

    /**
     * @param string shortname The objective_set_shortname
     * @return array
     */
    public function fetchObjectiveSetByShortname($shortname = null) {
        $objective_set_model = new Models_ObjectiveSet();
        $objective_set = $objective_set_model->fetchRowByShortname($shortname);
        return $objective_set;
    }

    /**
     * Fetch assessor data for a given assessment
     *
     * @param int $dassessment_id the assessment identifier
     * @return array
     */
    public function fetchAssessmentAssessor($dassessment_id = 0) {
        /**
         * Instantiate the assessment api object, used to fetch the assessor data associated with a particular assessment
         */
        $assessments_api = new Entrada_Assessments_Assessment(array("limit_dataset" => array("assessor")));

        $assessments_api->setAssessmentID($dassessment_id);
        return $assessments_api->getAssessor();
    }

    /**
     * Fetch a response for a particular item group and form type
     *
     * @param int $form_id
     * @param string $item_group_shortname
     * @param int $aprogress_id
     * @param string $form_type_shortname
     * @return array the response
     */
    protected function fetchCBMEAssessmentResponse($form_id = 0, $item_group_shortname = "", $aprogress_id = 0, $form_type_shortname = "") {
        global $db;

        $JOIN_item_groups = "";
        $AND_shortname = "";

        if ($item_group_shortname) {
            $JOIN_item_groups = "JOIN `cbl_assessments_lu_item_groups` AS e ON d.`item_group_id` = e.`item_group_id`";
            $AND_shortname = "AND e.`shortname` = ?";
        }

        $response = array();
        $query = "  SELECT f.*, g.*, h.`descriptor` FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessments_lu_form_types` AS b
                    ON a.`form_type_id` = b.`form_type_id`
                    JOIN `cbl_assessment_form_elements` AS c
                    ON a.`form_id` = c.`form_id`
                    JOIN `cbl_assessments_lu_items` AS d
                    ON c.`element_id` = d.`item_id`
                    $JOIN_item_groups
                    JOIN `cbl_assessment_progress_responses` AS f
                    ON a.`form_id` = f.`form_id`
                    LEFT JOIN `cbl_assessments_lu_item_responses` AS g
                    ON f.`iresponse_id` = g.`iresponse_id`
                    LEFT JOIN `cbl_assessments_lu_response_descriptors` AS h
                    ON g.`ardescriptor_id` = h.`ardescriptor_id`
                    WHERE a.`form_id` = ?
                    $AND_shortname
                    AND f.`aprogress_id` = ?
                    AND c.`afelement_id` = f.`afelement_id`
                    AND f.`deleted_date` IS NULL
                    AND b.`shortname` = ?";

        $constraints = array($form_id);
        if ($JOIN_item_groups) {
            $constraints[] = $item_group_shortname;
        }
        $constraints[] = $aprogress_id;
        $constraints[] = $form_type_shortname;

        $result = $db->GetRow($query, $constraints);
        if ($result) {
            $response = $result;
        }
        return $response;
    }

    /**
     * Fetch all applicable filter data points
     * @param int $course_id
     * @param array $filters
     * @return array
     */
    public function fetchFilterData($course_id = 0, $filters = array()) {
        global $db;
        $selected_filters = array();

        if (array_key_exists("epas", $filters)) {
            $query = "  SELECT b.`objective_id`, b.`objective_code`, b.`objective_name` FROM `cbme_course_objectives` AS a
                        JOIN `global_lu_objectives` AS b
                        ON a.`objective_id` = b.`objective_id`
                        WHERE a.`course_id` = ?
                        AND a.`objective_id` IN (". implode(",", $filters["epas"]) .")
                        AND a.`deleted_date` IS NULL
                        AND b.`objective_active` = 1";
            $results = $db->GetAll($query, array($course_id));
            if ($results) {
                foreach ($results as $result) {
                    $selected_filters["epas"][] = array("value" => $result["objective_id"], "data_filter_control" => "epas_" . $result["objective_id"], "title" => $result["objective_code"], "description" => $result["objective_name"], "label" => $result["objective_code"] . ": " . substr($result["objective_name"], 0,  37), "style_class" => "epas_search_target_control");
                }
            }
        }

        if (array_key_exists("roles", $filters)) {
            $query = "  SELECT b.`objective_id`, b.`objective_code`, b.`objective_name` FROM `cbme_objective_trees` AS a
                        JOIN `global_lu_objectives` AS b
                        ON a.`objective_id` = b.`objective_id`
                        WHERE a.`course_id` = ?
                        AND a.`objective_id` IN (". implode(",", $filters["roles"]) .")
                        AND a.`deleted_date` IS NULL
                        AND b.`objective_active` = 1
                        GROUP BY b.`objective_id`";
            $results = $db->GetAll($query, array($course_id));
            if ($results) {
                foreach ($results as $result) {
                    $selected_filters["roles"][] = array("value" => $result["objective_id"], "data_filter_control" => "roles_" . $result["objective_id"], "title" => $result["objective_code"], "description" => $result["objective_name"], "label" => $result["objective_code"] . ": " . substr($result["objective_name"], 0,  37), "style_class" => "roles_search_target_control");
                }
            }
        }

        if (array_key_exists("milestones", $filters)) {
            foreach ($filters["milestones"] as $stage_id => $milestones) {
                $objective_model = new Models_Objective();
                $objective = $objective_model->fetchRow($stage_id);
                if ($objective) {
                    $query = "  SELECT b.`objective_id`, b.`objective_code`, b.`objective_name`, b.`objective_parent` FROM `cbme_objective_trees` AS a
                            JOIN `global_lu_objectives` AS b
                            ON a.`objective_id` = b.`objective_id`
                            WHERE a.`course_id` = ?
                            AND a.`objective_id` IN (" . implode(",", $milestones) . ")
                            AND a.`deleted_date` IS NULL
                            AND b.`objective_active` = 1
                            GROUP BY b.`objective_id`";
                    $results = $db->GetAll($query, array($course_id));
                    if ($results) {
                        foreach ($results as $result) {
                            $selected_filters["milestones"]["objective_" . $stage_id][] = array("value" => $result["objective_id"], "data_filter_control" => "objective_" . $stage_id . "_" . $result["objective_id"], "title" => $result["objective_code"], "description" => $result["objective_name"], "label" => $result["objective_code"] . ": " . substr($result["objective_name"], 0, 37), "style_class" => "objective_" . $stage_id . "_search_target_control", "filter_type" => $objective->getName());
                        }
                    }
                }
            }
        }

        if (array_key_exists("contextual_variables", $filters)) {
            $query = "  SELECT `objective_id`, `objective_code`, `objective_name` FROM `global_lu_objectives`
                        WHERE `objective_id` IN  (". implode(",", $filters["contextual_variables"]) .")
                        AND `objective_active` = 1";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $selected_filters["contextual_variables"][] = array("value" => $result["objective_id"], "data_filter_control" => "contextual_variables_" . $result["objective_id"], "title" => $result["objective_name"], "description" => "", "label" => $result["objective_name"], "style_class" => "contextual_variables_search_target_control");
                }
            }
        }

        if (array_key_exists("contextual_variable_responses", $filters)) {
            $query = "  SELECT `objective_id`, `objective_code`, `objective_name`, `objective_parent` FROM `global_lu_objectives`
                        WHERE `objective_id` IN  (". implode(",", $filters["contextual_variable_responses"]) .")
                        AND `objective_active` = 1";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $selected_filters["objective_" . $result["objective_parent"]][] = array("value" => $result["objective_id"], "data_filter_control" => "objective_" . $result["objective_parent"] . "_" . $result["objective_id"], "title" => $result["objective_name"], "description" => "", "label" => $result["objective_name"], "style_class" => "objective_" . $result["objective_parent"] . "_search_target_control");
                }
            }
        }

        if (array_key_exists("selected_users", $filters)) {
            $query = "  SELECT `id`, CONCAT(`firstname`, ' ', `lastname`) AS `name`, `email` FROM `". AUTH_DATABASE ."`.`user_data` WHERE `id` IN  (". implode(",", $filters["selected_users"]) .")";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $selected_filters["selected_users"][] = array("value" => $result["id"], "data_filter_control" => "selected_users_" . $result["id"], "title" => $result["name"], "description" => $result["email"], "label" => $result["name"], "style_class" => "");
                }
            }
        }

        if (array_key_exists("experience", $filters)) {
            foreach ($filters["experience"] as $experience) {
                $schedule = Models_Schedule::fetchRowByID($experience["schedule_id"]);
                $parent_schedule = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                $selected_filters["schedule_id"][] = array("value" => $schedule->getID(), "data_filter_control" => "schedule_id_" . $schedule->getID(), "title" => $parent_schedule->getTitle(). " (" . date( "Y-m-d", $schedule->getStartDate()) . " - " . date( "Y-m-d", $schedule->getEndDate()) . ")", "description" => $schedule->getDescription(), "label" => $parent_schedule->getTitle() . " (" . date( "Y-m-d", $schedule->getStartDate()) . " - " . date( "Y-m-d", $schedule->getEndDate()) . ")", "style_class" => "schedule_id_search_target_control");
            }
        }

        if (array_key_exists("form_types", $filters)) {
            foreach($filters["form_types"] as $form_id) {
                $form_type_model = Models_Assessments_Form_Type::fetchRowByID($form_id);
                $selected_filters["form_types"][] = array("value" => $form_id, "data_filter_control" => "form_types_" . $form_id, "title" => $form_type_model->getTitle(), "description" => "", "label" => "", "style_class" => "form_type_search_target_control");
            }
        }

        if (array_key_exists("rating_scale_id", $filters)) {
            foreach($filters["rating_scale_id"] as $rating_scale) {
               $rating_scale_data =  Models_Assessments_RatingScale::fetchRowByID($rating_scale);
               $selected_filters["rating_scale"][] = array("value" => $rating_scale_data->getID(), "data_filter_control" => "rating_scale_id", "title" => $rating_scale_data->getRatingScaleTitle(), "description" => "", "label" => "", "style_class" => "form_type_search_target_control");
            }
        }

        return $selected_filters;
    }

    /**
     * Fetch a response for a particular item group and form type
     *
     * @param int $form_id
     * @param string $item_code
     * @param int $aprogress_id
     * @param string $form_type_shortname
     * @return array the response
     */
    protected function fetchCBMEAssessmentEntrustment($form_id = 0, $item_code = "", $aprogress_id = 0, $form_type_shortname = "") {
        global $db;
        $response = array();
        $query = "  SELECT f.*, g.*, h.`descriptor` FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessments_lu_form_types` AS b
                    ON a.`form_type_id` = b.`form_type_id`
                    JOIN `cbl_assessment_form_elements` AS c
                    ON a.`form_id` = c.`form_id`
                    JOIN `cbl_assessments_lu_items` AS d
                    ON c.`element_id` = d.`item_id`
                    JOIN `cbl_assessment_progress_responses` AS f
                    ON a.`form_id` = f.`form_id`
                    LEFT JOIN `cbl_assessments_lu_item_responses` AS g
                    ON f.`iresponse_id` = g.`iresponse_id`
                    LEFT JOIN `cbl_assessments_lu_response_descriptors` AS h
                    ON g.`ardescriptor_id` = h.`ardescriptor_id`
                    JOIN `cbl_assessment_form_type_meta` AS i
                    ON b.`form_type_id` = i.`form_type_id`
                    WHERE a.`form_id` = ?
                    AND d.`item_code` = ?
                    AND f.`aprogress_id` = ?
                    AND c.`afelement_id` = f.`afelement_id`
                    AND f.`deleted_date` IS NULL
                    AND b.`shortname` = ?
                    AND i.`meta_name` = 'show_entrustment'";
        $result = $db->GetRow($query, array($form_id, $item_code, $aprogress_id, $form_type_shortname));
        if ($result) {
            $response = $result;
        }

        return $response;
    }

    /**
     * Fetch a count of all entrustment responses for a particular form
     *
     * @param int $form_id
     * @param string $item_code
     * @param string $form_type_shortname
     * @return array the response
     */
    protected function countScaleResponses($form_id = 0, $item_code = "", $form_type_shortname = "") {
        global $db;
        $count = 0;
        $query = "  SELECT COUNT(e.`rating_scale_id`) AS `count` FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessments_lu_form_types` AS b
                    ON a.`form_type_id` = b.`form_type_id`
                    JOIN `cbl_assessment_form_elements` AS c
                    ON a.`form_id` = c.`form_id`
                    JOIN `cbl_assessments_lu_items` AS d
                    ON c.`element_id` = d.`item_id`
                    JOIN `cbl_assessment_rating_scale_responses` AS e
                    ON d.`rating_scale_id` = e.`rating_scale_id`
                    WHERE a.`form_id` = ?
                    AND d.`item_code` = ?
                    AND (e.`weight` <> 0 OR e.`weight` IS NULL)
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    AND d.`deleted_date` IS NULL
                    AND e.`deleted_date` IS NULL
                    AND b.`shortname` = ?";

        $result = $db->GetRow($query, array($form_id, $item_code, $form_type_shortname));
        if ($result) {
            $count = $result["count"];
        }

        return $count;
    }

    /**
     * Set the stale flag in the dataset, if the dataset exists.
     * Functionality that calls this method should behave as though an empty dataset is stale.
     */
    public function setStale() {
        if (!empty($this->dataset)) {
            if (array_key_exists("is_stale", $this->dataset)) {
                $this->dataset["is_stale"] = true;
            }
        }
    }

    /**
     * Get all errors.
     *
     * @return array
     */
    public function getErrorMessages() {
        return $this->error_messages;
    }

    /**
     * Add a single error message.
     *
     * @param $single_error_string
     * @return string
     */
    public function addErrorMessage($single_error_string) {
        $this->error_messages[] = $single_error_string;
        return $single_error_string;
    }

    /**
     * Add multiple error messages.
     *
     * @param array $error_strings
     */
    public function addErrorMessages($error_strings) {
        $this->error_messages = array_merge($this->error_messages, $error_strings);
    }

    /**
     * Clear the stored error messages.
     */
    public function clearErrorMessages() {
        $this->error_messages = array();
    }
}