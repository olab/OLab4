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
 * A class to handle reporting on assessment forms.
 *
 * The core functionality of this object is to fetch individualized
 * progress on each target and generate a CSV for the form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_CSVReports extends Entrada_Utilities_Assessments_Base {

    // Class properties set at construction time.
    protected   $organisation_id = null,
                $adistribution_id = null,
                $form_id = null,
                $prune_empty_rubrics = true,
                $cleanup_rubrics = true;


    // Flag for disabling caching; can be set at construction time
    protected $disable_file_caching = true;

    // Internal data structure for report data.
    private   $report_data = array();

    /**
     * Entrada_Utilities_Assessments_Reports constructor.
     *
     * @param null|array() $arr
     */
    public function __construct($arr = null) {
        parent::__construct($arr);
    }

    //--- Getters/Setters ---//

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    //--- Public methods ---//

    /**
     * Generate the report data for the given report parameters, either specified or derived from object properties.
     *
     * @return array
     */
    public function generateReport() {

        $report_data = array(
            "response_data" => array(),
            "item_data" => array()
        );

        $assessor_data = array();
        $target_data = array();

        // Fetch all completions.
        $progress = $this->fetchCompletedProgress();
        if ($progress) {
            foreach ($progress as $progress_data) {

                $target_index = "{$progress_data["target_type"]}-{$progress_data["target_record_id"]}";
                $assessor_index = "{$progress_data["assessor_type"]}-{$progress_data["assessor_value"]}";

                // Pull in responses.
                $responses = $this->fetchAllAssessmentResponsesData($progress_data["aprogress_id"]);
                foreach ($responses as $response) {

                    // Pull in target information.
                    if (!array_key_exists($target_index, $target_data)) {
                        $target_info = self::getTargetInfo($progress_data["target_type"], $progress_data["target_record_id"]);
                        $target_data[$target_index] =  array(
                            "name" => $target_info ? $target_info["name"] : "",
                            "number" => $target_info ? $target_info["number"] : ""
                        );
                    }
                    $response["target_name"] = $target_data[$target_index]["name"];
                    $response["target_number"] = $target_data[$target_index]["number"] ? $target_data[$target_index]["number"] : "";

                    // Pull in assessor information.
                    if (!array_key_exists($assessor_index, $assessor_data)) {
                        $assessor_info = self::getUserByType($progress_data["assessor_value"], $progress_data["assessor_type"]);
                        $assessor_data[$assessor_index] = array(
                            "name" => $assessor_info ? $assessor_info->getFullname(false) : "",
                            "number" => $assessor_info ? $assessor_info->getNumber() : ""
                        );
                    }
                    $response["assessor_name"] = $assessor_data[$assessor_index]["name"];
                    $response["assessor_number"] = $assessor_data[$assessor_index]["number"] ? $assessor_data[$assessor_index]["number"] : "";

                    // Store parent item data.
                    if (!array_key_exists($response["form_element_order"], $report_data["item_data"])) {
                        $report_data["item_data"][$response["form_element_order"]] = array(
                            "item_id"               => $response["item_id"],
                            "item_code"             => $response["item_code"],
                            "item_text"             => $response["item_text"],
                            "form_element_order"    => $response["form_element_order"],
                            "comment_type"          => $response["comment_type"],
                            "itemtype_id"           => $response["itemtype_id"],
                            "itemtype_shortname"    => $response["itemtype_shortname"],
                            "itemtype_name"         => $response["itemtype_name"],
                        );
                    }

                    /* Determine if the item or rubric has a scale. If so, we will need to match it to the response as
                    this will be used for weighting purposes. */
                    $response["rating_scale_id"] = null;
                    $response["response_weight"] = null;

                    $scale_id = false;
                    if ($response["item_rating_scale_id"]) {
                        $scale_id = $response["item_rating_scale_id"];
                    } elseif ($response["rubric_rating_scale_id"]) {
                        $scale_id = $response["rubric_rating_scale_id"];
                    }

                    if ($scale_id) {
                        $response["rating_scale_id"] = $scale_id;
                        $rating_scale_response = Models_Assessments_RatingScale_Response::fetchRowByRatingScaleARDescriptorID($scale_id, $response["ardescriptor_id"]);
                        if ($rating_scale_response) {
                            $response["response_weight"] = $rating_scale_response->getWeight();
                        }
                    }

                    // Response data entry will follow this format:
                    // parent array => progress_id => target_index => item_id => data
                    $report_data["response_data"][$progress_data["aprogress_id"]][$target_index][$response["item_id"]][] = $response;
                }
            }

            ksort($report_data["item_data"]);
        }

        return $this->report_data = $report_data;
    }

    /**
     * Fetch relevant progress for the given report parameters, either specified or derived from object properties.
     *
     * @return array
     */
    public function fetchCompletedProgress() {
        global $db;

        $AND_organisation_id    = $this->createOrganisationIDValueClause();
        $AND_distribution_id    = $this->createDistributionIDValueClause();
        $AND_form_id            = $this->createFormIDValueClause();

        // Find all completed progress for the given parameters
        $query = "  SELECT ap.*, da.*, af.`title` AS `form_title`, ad.`title` AS `distribution_title`

                    FROM `cbl_assessment_progress`              AS ap
                    JOIN `cbl_assessment_distributions`         AS ad   ON ap.`adistribution_id` = ad.`adistribution_id`
                    JOIN `cbl_distribution_assessments`         AS da   ON ap.`dassessment_id` = da.`dassessment_id` 
                    JOIN `cbl_assessments_lu_forms`             AS af   ON da.`form_id` = af.`form_id`
                    JOIN `cbl_distribution_assessment_targets`  AS at   ON ap.`target_record_id` = at.`target_value` AND ap.`target_type` = at.`target_type` AND ap.`dassessment_id` = at.`dassessment_id`

                    WHERE ap.`progress_value` = 'complete'
                    AND   ap.`deleted_date` IS NULL
                    $AND_organisation_id
                    $AND_distribution_id
                    $AND_form_id

                    GROUP BY ap.`aprogress_id`";

        $result_set = $db->GetAll($query);
        return $result_set;
    }

    //--- Private methods ---//

    /**
     * Fetch all responses for a given progress record.
     *
     * @param $aprogress_id
     * @return array
     */
    private function fetchAllAssessmentResponsesData($aprogress_id) {
        global $db;
        // Fetch all the responses for the completed assessments
        // LEFT JOIN to include rows that may or may not have response descriptors or item responses.
        $query = "SELECT    pr.`aprogress_id`, pr.`form_id`, pr.`adistribution_id`, pr.`assessor_type`, 
                            pr.`assessor_value`, pr.`afelement_id`, pr.`iresponse_id`, pr.`comments`,
                            fe.`afelement_id`, fe.`element_id`, fe.`element_type`, fe.`element_text`, fe.`order` AS `form_element_order`, fe.`rubric_id`,
                            i.`item_id`, i.`itemtype_id`, i.`item_code`, i.`item_text`, i.`comment_type`,
                            it.`shortname` AS `itemtype_shortname`, it.`name` AS `itemtype_name`,
                            ir.`text` AS `item_response_text`, ir.`order` AS `response_order`,
                            rd.`ardescriptor_id`, rd.`descriptor` AS `response_descriptor`,
                            i.`rating_scale_id` AS `item_rating_scale_id`, r.`rating_scale_id` AS `rubric_rating_scale_id`
                  FROM      `cbl_assessment_progress_responses`       AS pr
                  LEFT JOIN `cbl_assessment_form_elements`            AS fe  ON fe.`afelement_id`    = pr.`afelement_id`
                  LEFT JOIN `cbl_assessments_lu_items`                AS i   ON i.`item_id`          = fe.`element_id`
                  LEFT JOIN `cbl_assessments_lu_rubrics`              AS r   ON r.`rubric_id`        = fe.`rubric_id`
                  LEFT JOIN `cbl_assessments_lu_itemtypes`            AS it  ON it.`itemtype_id`     = i.`itemtype_id`
                  LEFT JOIN `cbl_assessments_lu_item_responses`       AS ir  ON ir.`iresponse_id`    = pr.`iresponse_id`
                  LEFT JOIN `cbl_assessments_lu_response_descriptors` AS rd  ON rd.`ardescriptor_id` = ir.`ardescriptor_id`
                  WHERE     pr.`aprogress_id` = ?
                  AND       pr.`deleted_date` IS NULL
                  ORDER BY  fe.`order` ASC";

        $responses = $db->GetAll($query, array($aprogress_id));
        if (!is_array($responses)) {
            return array();
        }
        return $responses;
    }

    private function createDistributionIDValueClause() {
        if (is_array($this->adistribution_id) && !empty($this->adistribution_id)) {
            $clean_distribution_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->adistribution_id
            );
            $imploded = implode(",", $clean_distribution_ids);
            $AND_distribution_id = "AND ap.`adistribution_id` IN ($imploded)";
        } else {
            $distribution_id = clean_input($this->adistribution_id, array("trim", "int"));
            $AND_distribution_id = "AND ap.`adistribution_id` = $distribution_id";
        }
        return $AND_distribution_id;
    }

    private function createOrganisationIDValueClause() {
        if (is_array($this->organisation_id) && !empty($this->organisation_id)) {
            $clean_organisation_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $this->organisation_id
            );
            $imploded = implode(",", $clean_organisation_ids);
            $AND_organisation_id = "AND da.`organisation_id` IN ($imploded)";
        } else {
            $organisation_id = clean_input($this->organisation_id, array("trim", "int"));
            $AND_organisation_id = "AND da.`organisation_id` = $organisation_id";
        }
        return $AND_organisation_id;
    }

    private function createFormIDValueClause() {
        $form_id = clean_input($this->form_id, array("trim", "int"));
        return "AND da.`form_id` = $form_id";
    }

}