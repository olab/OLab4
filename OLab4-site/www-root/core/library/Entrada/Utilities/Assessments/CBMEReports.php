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
 * A class to handle reporting on CBME assessment forms.
 *
 * The core functionality of this object is to fetch all of the CBME
 * assessments based on requested filters, aggregate that data, and
 * return the contents in a view-consumable data structure.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_CBMEReports extends Entrada_Utilities_Assessments_Reports {

    // Class properties set at construction time.
    protected $aprogress_id = null,
              $iresponse_id = null,
              $item_group_blacklist = null;

    /**
     * Fetch a session-level preference setting. This is specific to the reporting summary being accessed from the assessments module.
     *
     * @param $specified_preference
     * @return null
     */
    public static function getPreferenceFromSession($specified_preference) {
        // Session values are stored as such: $_SESSION[ APPLICATION_IDENTIFIER ][ MODULE ][ SUBMODULE ][ variable ]
        // This preference setting assumes this functionality is using assessments/assessment_reports to store its preferences.
        // This isn't really problematic from a functional standpoint, but might be semantically awkward if this object is used
        // from outside of this module/submodule.
        if (isset($_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["cbme_assessment_reports"][$specified_preference])) {
            return $_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["cbme_assessment_reports"][$specified_preference];
        } else {
            return null;
        }
    }

    /**
     * Fetch a summary of the assessments to report on. "Target" role will scope to appropriate assessment options.
     *
     * @param $target_role
     * @return array
     */
    public function fetchCompletedAssessmentsMeta($target_role = false) {
        global $db;

        $prepared_variables = array();

        $AND_date_greater = "";
        if (!is_null($this->start_date)) {
            $AND_date_greater = " AND da.`delivery_date` >= ". $db->qstr($this->start_date);
        }

        $AND_date_less = "";
        if (!is_null($this->end_date)) {
            $AND_date_less = " AND da.`delivery_date` <= ". $db->qstr($this->end_date);
        }

        $AND_organisation_id    = $this->organisation_id  ? "AND da.`organisation_id` = ?" : "";
        $AND_form_id            = $this->createFormIDClause();
        $AND_course_id          = $this->createCourseIDClause();
        $AND_aprogress_id       = $this->createAProgressIDClause();
        $AND_iresponse_id       = $this->createIResponseIDClause();
        $AND_target             = $this->createTargetClause();

        $JOIN_progress_response  = "";
        if ($AND_iresponse_id) {
            $JOIN_progress_response = " JOIN `cbl_assessment_progress_responses` AS pr
                                        ON pr.`aprogress_id` = ap.`aprogress_id`";
        }

        $GROUP_by_proxies = "";
        if ($this->target_type == "proxy_id") {
            $GROUP_by_proxies = "ap.`target_record_id`";
        }

        // Find all completed assessments for the given parameters
        $query = "  SELECT COUNT(*) AS form_count,
                        da.`form_id`, da.`course_id`,
                        ap.`target_record_id`,
                        af.`title` AS `form_title`

                FROM `cbl_assessment_progress`              AS ap
                JOIN `cbl_distribution_assessments`         AS da   ON ap.`dassessment_id` = da.`dassessment_id` 
        
                JOIN `cbl_assessments_lu_forms`             AS af   ON da.`form_id` = af.`form_id`
                JOIN `cbl_distribution_assessment_targets`  AS at   ON ap.`target_record_id` = at.`target_value` AND ap.`target_type` = at.`target_type` AND ap.`dassessment_id` = at.`dassessment_id`

                $JOIN_progress_response

                WHERE ap.`progress_value` = 'complete'
                AND   ap.`deleted_date` IS NULL
                $AND_aprogress_id
                $AND_target
                $AND_organisation_id
                $AND_course_id
                $AND_form_id
                $AND_date_greater
                $AND_date_less
                $AND_iresponse_id

                GROUP BY $GROUP_by_proxies, da.`course_id`, af.`form_id`
                ORDER BY af.`form_id`";

        foreach (array($this->organisation_id) as $prepare) {
            if ($prepare) {
                $prepared_variables[] = $prepare;
            }
        }

        $result_set = $db->GetAll($query, $prepared_variables);
        // Save the result set for later use.

        return $result_set;
    }

    /**
     * Generate the report data for the given report parameters, either specified or derived from object properties.
     *
     * @param $report_type
     * @param $target_role
     * @return array
     */
    public function generateReport($report_type = null, $target_role = null) {

        // Fetch and iterate through the metadata (our report parameters).
        // fetchCompletedAssessmentsMeta() guarantees an array as return value.
        switch ($report_type) {
            case "event_feedback":
                $summary = $this->fetchCompletedEventEvaluationsMeta();
                break;
            default:
                $summary = $this->fetchCompletedAssessmentsMeta();
                break;
        }

        // The metadata could potentially be multiple records. The summary data is grouped by course_id and form_id (optionally limited by distribution ID).
        foreach ($summary as $meta) {
            // Seed our internal results array based on the current incarnation of the form. This ensures our report can display
            // any free-text items and items with no responses that may have been optional.
            $this->reportDataConfigure($meta["form_id"], $this->item_group_blacklist);

            // Fetch all completed progress records. We build the report using progress records as to ensure we find all responses, even if the form or form items have changed.
            switch ($report_type) {
                default:
                    $completions = $this->fetchCompletedProgressData(
                        $meta["target_record_id"],
                        $meta["course_id"],
                        null,
                        $meta["form_id"],
                        null,
                        (isset($meta["eventtype_id"])) ? "event" : null
                    );
                    break;
            }

            // For each completed assessment, fetch all the responses and store them in the internal report_data array
            foreach ($completions as $completed) {
                $comment_anonymity = "anonymous";
                $assesment_options_model = new Models_Assessments_Options();
                $assesment_options = $assesment_options_model->fetchAllByDassessmentID($completed["dassessment_id"]);
                if ($assesment_options) {
                    foreach ($assesment_options as $assesment_option) {
                        if ($assesment_option->getOptionName() == "target_reporting_comment_anonymity") {
                            $comment_anonymity = $assesment_option->getOptionValue();
                        }
                    }
                }
                $responses = $this->fetchAllAssessmentResponsesData($completed["aprogress_id"]);
                foreach ($responses as $response_data) {
                    if ($response_data["element_type"] == "item") {

                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        $assessor = $assessments_base->getUserByType($response_data["assessor_value"], $response_data["assessor_type"])->toArray();
                        $assessor_info = array(
                            "assessor_type" => $response_data["assessor_type"],
                            "assessor_value" => $response_data["assessor_value"],
                            "assessor_name" => $assessor ? $assessor["firstname"] . " " . $assessor["lastname"] : null,
                            "assessor_email" => $assessor ? $assessor["email"] : null
                        );

                        // Storing assessor info for ease of access later.
                        $assessor_key = "{$response_data["assessor_type"]}-{$response_data["assessor_value"]}";
                        $this->report_data["assessors"][$assessor_key] = $assessor_info;

                        if ($response_data["rubric_id"]) {
                            // This form element is a rubric.
                            // Fetch all of the rubric data and add it to the report data structure.
                            $rubric = $this->fetchRubricData($response_data["rubric_id"]);
                            if ($rubric) {
                                $this->reportDataAddNodeGroupedItem($response_data["rubric_id"], $response_data["element_id"], $response_data["ardescriptor_id"], $response_data["item_response_text"], $response_data["comments"], $rubric, $assessor_info, $comment_anonymity);
                            } else {
                                application_log("error", "Reports: Failed to fetch data for rubric {$response_data["rubric_id"]}");
                            }

                        } else if ($response_data["itemtype_shortname"] == "free_text") {
                            // This form element a free-text comment.
                            $this->reportDataAddNodeFreeTextComment($response_data["element_id"], $response_data["form_element_order"], $response_data["comments"], $assessor_info, $comment_anonymity);

                        } else {
                            // This form element is a single item (a scale or multiple choice, or other)
                            // Fetch all responses (fetchItemResponsesDataByItemElement adds a group_key)
                            $all_responses = $this->fetchItemResponsesDataByItemElement($response_data["element_id"]);

                            // Determine if we're using the text of the item or the descriptor to do our comparison on
                            $compare_on_descriptor = $this->determineUseDescriptorComparison($all_responses);

                            // Group by the group_key (ensures no duplicate items)
                            $grouped_responses = $this->groupResponsesByGroupKey($all_responses);

                            // Add the response data to the report structure
                            $this->reportDataAddNodeSingleElement(
                                $response_data["element_id"],
                                $compare_on_descriptor ? $response_data["response_descriptor"] : $response_data["item_response_text"],
                                $response_data["comments"],
                                $grouped_responses,
                                $assessor_info,
                                $comment_anonymity
                            );
                        }

                    } else if ($response_data["element_type"] == "objective") {
                        // Objective set selection
                        // TODO: Support objectives later
                    }
                }
            }
        }


        // Cleanup rubrics
        // There are some forms out there that have duplicate responses descriptors for some (but not all) of the responses.
        // The initial report logic aggregates them correctly, but since they appear in different orders, they are added in more than one position, duplicating
        // the response. We prune the extras here.
        if ($this->cleanup_rubrics) {
            foreach ($this->report_data as $type_and_index => $report_node) {
                $type_and_index_a = explode("-", $type_and_index);
                $type = $type_and_index_a[0];
                if ($type == "rubric") {
                    foreach ($report_node["responses"] as $i => $response) {
                        foreach ($response["rubric_response_detail"] as $element_id => $rubric_responses) {
                            foreach ($rubric_responses as $item_order_find => $response_detail_find) {
                                foreach ($rubric_responses as $item_order_found => $response_detail_found) {
                                    if ($item_order_find == $item_order_found) {
                                        continue;
                                    } else {
                                        if ($response_detail_find["ardescriptor_id"] == $response_detail_found["ardescriptor_id"]) {
                                            // Remove the extraneous grouping
                                            unset($this->report_data[$type_and_index]["responses"][$i]["rubric_response_detail"][$element_id][$item_order_found]);

                                            // Remove them from the local copies, so we don't try and remove it anymore (thereby stopping the appropriate grouping from being removed)
                                            unset($rubric_responses[$item_order_found]);
                                            unset($rubric_responses[$item_order_find]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Prune empty rubric responses.
        // There may be some empty responses if, by some error, the rubric was edited or the task was erroneously marked as complete.
        if ($this->prune_empty_rubrics) {
            foreach ($this->report_data as $type_and_index => $report_node) {
                $type_and_index_a = explode("-", $type_and_index);
                $type = $type_and_index_a[0];
                if ($type == "rubric") {
                    foreach ($report_node["responses"] as $i => $response) {
                        foreach ($response["rubric_response_detail"] as $element_id => $rubric_responses) {
                            $response_count = 0;
                            foreach ($rubric_responses as $item_order => $response_detail) {
                                $response_count += $response_detail["count"];
                            }
                            if ($response_count == 0) {
                                // This rubric element has no responses, so let's prune it.
                                unset($this->report_data[$type_and_index]["responses"][$i]);
                            }
                        }
                    }
                }
            }
        }

        return $this->report_data;
    }

    /**
     * Fetch completed assessments on a particular target id.
     *
     * @param $target_record_id
     * @param $course_id
     * @param $cperiod_id
     * @param $form_id
     * @param $type
     * @return array
     */
    protected function fetchCompletedProgressData($target_record_id, $course_id, $cperiod_id, $form_id, $distribution_id = null, $type = null) {
        global $db;

        $AND_date_greater    = "";
        $AND_date_less       = "";

        if (!is_null($this->start_date)) {
            $AND_date_greater = " AND da.`delivery_date` >= ". $db->qstr($this->start_date);
        }

        if (!is_null($this->end_date)) {
            $AND_date_less = "    AND da.`delivery_date` <= ". $db->qstr($this->end_date);
        }

        $AND_limit_to_associated_record = "";
        if ($this->associated_record_type && $this->associated_record_ids) {
            $imploded = implode(",", $this->associated_record_ids);
            $AND_limit_to_associated_record = " AND da.`associated_record_type` = '$this->associated_record_type' AND da.`associated_record_id` IN ($imploded)";
        }

        $AND_aprogress_id       = $this->createAProgressIDClause();
        $AND_iresponse_id       = $this->createIResponseIDClause();

        $JOIN_progress_response  = "";
        if ($AND_iresponse_id) {
            $JOIN_progress_response = " JOIN `cbl_assessment_progress_responses` AS pr
                                        ON pr.`aprogress_id` = ap.`aprogress_id`";
        }

        // Fetch all completed assessments (we're looking at the entire history of the form, even if elements have changed).
        // INNER JOIN for only those records that have assessment records.
        $query = "  SELECT  ap.`target_record_id`, ap.`aprogress_id`, ap.`dassessment_id`, ap.`adistribution_id`, ap.`updated_date`
                    FROM    `cbl_assessment_progress`       AS ap
                    JOIN    `cbl_distribution_assessments`  AS da ON ap.`dassessment_id` = da.`dassessment_id`
                    $JOIN_progress_response
                    WHERE   ap.`progress_value` = 'complete'
                    AND     ap.`target_record_id` = ?
                    AND     da.`course_id` = ?
                    AND     da.`form_id` = ?
                    $AND_limit_to_associated_record
                    $AND_date_greater
                    $AND_date_less
                    $AND_aprogress_id
                    $AND_iresponse_id
                    GROUP BY ap.`aprogress_id`";

        $prepared = array($target_record_id, $course_id, $form_id);

        $completions = $db->GetAll($query, $prepared);
        if (!is_array($completions)) {
            return array();
        }
        return $completions;
    }

    /**
     * Fetch all responses for a given progress record.
     *
     * @param $aprogress_id
     * @return array
     */
    protected function fetchAllAssessmentResponsesData($aprogress_id) {
        global $db;

        $AND_item_group_blacklist = $this->createItemGroupBlacklistClause();
        $JOIN_item_groups = "";
        if ($AND_item_group_blacklist) {
            $JOIN_item_groups = "   LEFT JOIN `cbl_assessments_lu_item_groups` AS ig
                                    ON ig.`item_group_id` = i.`item_group_id`";
        }

        // Fetch all the responses for the completed assessments
        // LEFT JOIN to include rows that may or may not have response descriptors or item responses.
        $query = "SELECT    pr.`aprogress_id`, pr.`form_id`, pr.`adistribution_id`, pr.`assessor_type`,
                            pr.`assessor_value`, pr.`afelement_id`, pr.`iresponse_id`, pr.`comments`,
                            fe.`afelement_id`, fe.`element_id`, fe.`element_type`, fe.`element_text`, fe.`order` AS `form_element_order`, fe.`rubric_id`,
                            i.`item_id`, i.`itemtype_id`, i.`item_code`, i.`item_text`, i.`comment_type`,
                            it.`shortname` AS `itemtype_shortname`, it.`name` AS `itemtype_name`,
                            ir.`text` AS `item_response_text`,
                            rd.`ardescriptor_id`, rd.`descriptor` AS `response_descriptor`
                  FROM      `cbl_assessment_progress_responses`       AS pr
                  LEFT JOIN `cbl_assessment_form_elements`            AS fe  ON fe.`afelement_id`    = pr.`afelement_id`
                  LEFT JOIN `cbl_assessments_lu_items`                AS i   ON i.`item_id`          = fe.`element_id`
                  LEFT JOIN `cbl_assessments_lu_itemtypes`            AS it  ON it.`itemtype_id`     = i.`itemtype_id`
                  LEFT JOIN `cbl_assessments_lu_item_responses`       AS ir  ON ir.`iresponse_id`    = pr.`iresponse_id`
                  LEFT JOIN `cbl_assessments_lu_response_descriptors` AS rd  ON rd.`ardescriptor_id` = ir.`ardescriptor_id`
                  {$JOIN_item_groups}
                  WHERE     pr.`aprogress_id` = ?
                  AND       pr.`deleted_date` IS NULL
                  {$AND_item_group_blacklist}
                  ORDER BY  fe.`order` ASC";
        $responses = $db->GetAll($query, array($aprogress_id));
        if (!is_array($responses)) {
            return array();
        }
        return $responses;
    }

    // Private functions

    private function createCourseIDClause() {
        $AND_course_value = "";

        if (is_array($this->course_id) && !empty($this->course_id)) {
            $clean_course_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->course_id
            );
            $imploded = implode(",", $clean_course_ids);
            $AND_course_value = "AND da.`course_id` IN ($imploded)";
        } else if ($this->course_id) {
            $course_id = clean_input($this->course_id, array("trim", "int"));
            $AND_course_value = "AND da.`course_id` = $course_id";
        }

        return $AND_course_value;
    }

    private function createTargetClause() {
        $AND_target = "";

        if ($this->target_type) {
            $AND_target = " AND ap.`target_type` = '$this->target_type'";

            if (is_array($this->target_value) && !empty($this->target_value)) {
                $clean_target_ids = array_map(
                    function ($val) {
                        return clean_input($val, array("trim", "int"));
                    },
                    $this->target_value
                );
                $imploded = implode(",", $clean_target_ids);
                $AND_target .= " AND ap.`target_record_id` IN ($imploded)";
            } else {
                $target_id = clean_input($this->target_value, array("trim", "int"));
                $AND_target .= " AND ap.`target_record_id` = $target_id";
            }
        }

        return $AND_target;
    }

    private function createFormIDClause() {
        $AND_form_value = "";

        if (is_array($this->form_id) && !empty($this->form_id)) {
            $clean_form_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->form_id
            );
            $imploded = implode(",", $clean_form_ids);
            $AND_form_value = "AND da.`form_id` IN ($imploded)";
        } else if ($this->form_id) {
            $form_id = clean_input($this->form_id, array("trim", "int"));
            $AND_form_value = "AND da.`form_id` = $form_id";
        }

        return $AND_form_value;
    }

    private function createAProgressIDClause() {
        $AND_progress_value = "";

        if (is_array($this->aprogress_id) && !empty($this->aprogress_id)) {
            $clean_progress_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->aprogress_id
            );
            $imploded = implode(",", $clean_progress_ids);
            $AND_progress_value = "AND ap.`aprogress_id` IN ($imploded)";
        } else if ($this->aprogress_id) {
            $aprogress_id = clean_input($this->aprogress_id, array("trim", "int"));
            $AND_progress_value = "AND ap.`aprogress_id` = $aprogress_id";
        }

        return $AND_progress_value;
    }

    private function createIResponseIDClause() {
        $AND_iresponse_value = "";

        if (is_array($this->iresponse_id) && !empty($this->iresponse_id)) {
            $clean_iresponse_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->iresponse_id
            );
            $imploded = implode(",", $clean_iresponse_ids);
            $AND_iresponse_value = "AND pr.`iresponse_id` IN ($imploded)";
        } else if ($this->iresponse_id) {
            $iresponse_id = clean_input($this->iresponse_id, array("trim", "int"));
            $AND_iresponse_value = "AND pr.`iresponse_id` = $iresponse_id";
        }

        return $AND_iresponse_value;
    }

    private function createItemGroupBlacklistClause() {
        $AND_item_group_value = "";

        if (is_array($this->item_group_blacklist) && !empty($this->item_group_blacklist)) {
            $clean_item_groups = array_map(
                function($val) {
                    global $db;
                    return $db->qstr(clean_input($val, array("trim", "striptags")));
                },
                $this->item_group_blacklist
            );
            $imploded = implode(",", $clean_item_groups);
            $AND_item_group_value = "AND (ig.`item_group_id` IS NULL OR ig.`shortname` NOT IN ($imploded))";
        } else if ($this->item_group_blacklist) {
            $item_group = clean_input($this->item_group_blacklist, array("trim", "striptags"));
            $AND_item_group_value = "AND (ig.`item_group_id` IS NULL ig.`shortname` != '$item_group'";
        }

        return $AND_item_group_value;
    }

}