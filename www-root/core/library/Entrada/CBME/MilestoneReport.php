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
 * This model handles the generation of a milestone report
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */
class Entrada_CBME_MilestoneReport extends Entrada_CBME_Base {

    protected $course_id = 0;
    protected $actor_proxy_id = 0;
    protected $dir = "";
    protected $fp;
    protected $file_names = array();
    protected $unique_rating_scales = array();
    protected $epa_ids = array();
    protected $epa_codes = array();
    protected $milestone_objectives = array();
    protected $epas = array();
    protected $csv_headings = array();
    protected $response_headings = array();
    protected $milestone_id_array = array();
    protected $dataset = array();
    protected $tallied_responses = array();
    protected $assessment_total = 0;
    protected $row = array();
    protected $zip;
    protected $zipname = "MilestoneReports.zip";
    protected $file_name = "";

    public function __construct($arr = array()) {
        $this->dir = CACHE_DIRECTORY . "/zips";
        parent::__construct($arr);
    }

    /**
     * Generates a milestone report as a CSV and outputs it in a ZIP folder
     * @param $form_ids
     */
    public function generateMilestoneReport($form_ids) {
        //Determine the rating scales we need for the report
        $this->determineUniqueRatingScales($form_ids);

        //Build the list of EPAs and milestones required
        $this->determineMilestonesAndEPAs();

        //Create the zip folder and open it
        $this->createAndOpenZip();

        //Data retrieval and formatting for the report
        $this->fetchReportData();
    }

    /**
     * Determines the unique rating scales from the provided assessment tools
     * @param $form_ids
     */
    private function determineUniqueRatingScales($form_ids = array()) {
        $unique_scales = $this->fetchRatingScales($form_ids);
        $this->unique_rating_scales = array_reverse($unique_scales);
    }

    /**
     * Fetch the rating scale for the form blueprint id
     * @param $form_ids
     * @return array
     */
    private function fetchRatingScales($form_ids) {
        $rating_scales = array();
        foreach($form_ids as $form_id) {
            $forms_api = new Entrada_Assessments_Forms(array("form_id" => $form_id, "limit_dataset" => array("elements", "rubrics")));
            $form_data = $forms_api->fetchFormData();
            if ($form_data) {
                if (isset($form_data["elements"]) && is_array($form_data["elements"])) {
                    foreach ($form_data["elements"] as $element) {
                        if ($element["item"]["rating_scale_id"]) {
                            $rating_scales[] = $element["item"]["rating_scale_id"];
                        }
                    }
                }
                if (isset($form_data["rubrics"]) && is_array($form_data["rubrics"])) {
                    foreach ($form_data["rubrics"] as $rubric) {
                        if ($rubric["rating_scale"]) {
                            $rating_scales[] = $rubric["rating_scale"]["rating_scale_id"];
                        }
                    }
                }
            }
        }
        $rating_scales = array_unique($rating_scales);
        $rating_scale_model = new Models_Assessments_RatingScale();
        $unique_scales = array();
        foreach ($rating_scales as $scale) {
            $rating_scale = $rating_scale_model->fetchMilestoneScaleAndTypeByID($scale);
            if ($rating_scale) {
                $unique_scales[] = $rating_scale["rating_scale_id"];
            }
        }
        return $unique_scales;
    }

    /**
     * Determine the milestones and EPAs that will be used during the report generation
     */
    private function determineMilestonesAndEPAs() {
        $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $this->actor_proxy_id, "actor_organisation_id" => $this->actor_organisation_id, "course_id" => $this->course_id));
        if ($tree_object) {
            $this->milestone_objectives = $tree_object->fetchTreeNodesByObjectiveSetShortname("milestone", 9);
            $this->epas = $tree_object->fetchTreeNodesByObjectiveSetShortname("epa", 9);
            foreach ($this->epas as $epa) {
                if (isset($epa["objective_id"]) && $tmp_input = clean_input($epa["objective_id"], array("trim", "int"))) {
                    $this->epa_ids[] = $tmp_input;
                }
                $this->epa_codes[] = $epa["objective_code"];
            }
        }
    }

    /**
     * Create the zip folder and open it for use
     */
    private function createAndOpenZip() {
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0777);
        }
        $this->zip = new ZipArchive();
        $this->zip->open($this->dir . "/" . $this->zipname, ZipArchive::CREATE);
    }

    /**
     * Main function for fetching all the report data
     */
    private function fetchReportData() {
        if ($this->unique_rating_scales) {
            foreach ($this->unique_rating_scales as $scale_index => $rating_scale) {
                $rating_scale_responses = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleID($rating_scale);
                $this->row = array();
                if ($rating_scale_responses) {
                    $this->createCSVHeadings($rating_scale_responses);
                    $this->createCSVFile($rating_scale);
                    $this->createResponseHeadings($rating_scale_responses);
                    if ($this->milestone_objectives) {
                        $this->buildMilestonIDs();
                        $this->buildDataset($rating_scale);
                        foreach ($this->milestone_objectives as $index => $milestone) {
                            $this->buildCSVRow($milestone, $rating_scale_responses);
                        }
                    }
                    $this->closeFileAddToZip();
                }
            }
            $this->closeZipAndOutput();
            $this->cleanupFiles();
        }
    }

    /**
     * Create the CSV headings to go across the top of the file
     * @param $rating_scale_responses
     */
    private function createCSVHeadings($rating_scale_responses) {
        $this->csv_headings = array();
        foreach ($this->epas as $key => $epa) {
            if ($key == 0) {
                $this->csv_headings[] = "";
                $this->csv_headings[] = $epa["objective_code"];
                for ($count = 0; $count < sizeof($rating_scale_responses) - 1; $count++) {
                    $this->csv_headings[] = "";
                }
            } else {
                $this->csv_headings[] = $epa["objective_code"];
                for ($count = 0; $count < sizeof($rating_scale_responses) - 1; $count++) {
                    $this->csv_headings[] = "";
                }
            }
        }
    }

    /**
     * Create the CSV file and name it based on the scale and current date
     * @param $rating_scale
     */
    private function createCSVFile($rating_scale) {
        $current_scale = Models_Assessments_RatingScale::fetchRowByID($rating_scale);
        $scale_name = strtolower(str_replace(" ", "-", $current_scale->getRatingScaleTitle()));
        $this->file_name = $scale_name . "-" . date("Y-m-d") . ".csv";
        $this->file_names[] = $this->file_name;
        $this->fp = fopen($this->dir . "/" . $this->file_name, "w");
        fputcsv($this->fp, $this->csv_headings);
    }

    /**
     * Create the rating scale response headings
     * @param $rating_scale_responses
     */
    private function createResponseHeadings($rating_scale_responses) {
        $this->response_headings = array();
        for ($i = 0; $i <= sizeof($this->epas); $i++) {
            if ($i == 0) {
                $this->response_headings[] = "";
            } else {
                foreach ($rating_scale_responses as $j => $response) {
                    $this->response_headings[] = $response->getText();
                }
            }
        }
        fputcsv($this->fp, $this->response_headings);
    }

    /**
     * Builds an array of milestone ids
     */
    private function buildMilestonIDs() {
        $this->milestone_id_array = array();
        $temp_milestone_array = array();
        foreach ($this->milestone_objectives as $index => $milestone) {
            if (isset($milestone["objective_id"]) && $tmp_input = clean_input($milestone["objective_id"], array("trim", "int"))) {
                $this->milestone_id_array[] = $tmp_input;
            }
            $temp_milestone_array[$milestone["objective_id"]] = array("objective_id" => $milestone["objective_id"], "objective_code" => $milestone["objective_code"]);
        }
        $this->milestone_objectives = $temp_milestone_array;
        $this->milestone_id_array = array_unique($this->milestone_id_array);
    }

    /**
     * Build the dataset used to create the report
     * @param $rating_scale
     */
    private function buildDataset($rating_scale) {
        $this->tallied_responses = array();
        $cbme_assessments = $this->fetchAssessmentData($rating_scale);
        if ($cbme_assessments) {
            foreach($cbme_assessments as $assessment) {
                $rating_scale_responses = $this->fetchRatingScaleResponses($assessment["item_id"]);

                $mapped_epas = $this->fetchEPAsMappedToForms($this->actor_organisation_id, $assessment["form_id"], $this->course_id);
                if ($mapped_epas) {
                    foreach ($mapped_epas as &$mapped_epa) {
                        $mapped_epa["stage_code"] = strtolower(substr($mapped_epa["objective_code"], 0, 1));
                    }
                }

                $assessment_data[] = array(
                    "selected_iresponse_order" => $assessment["order"],
                    "rating_scale_responses"   => $rating_scale_responses,
                    "milestone_objective"      => $assessment["objective_id"],
                    "mapped_epas"              => $mapped_epas,
                );
            }
        }

        $this->dataset["assessments"] = $assessment_data;
    }

    /**
     * Use the data that we retrieved to build a row for the CSV with the data formatted properly
     * @param $milestone
     * @param $rating_scale_responses
     */
    private function buildCSVRow($milestone, $rating_scale_responses) {
        $this->assessment_total = 0;
        $this->tallied_responses = array();
        $this->row = array();
        $this->row[] = $milestone["objective_code"];
        if ($this->dataset["assessments"]) {
            foreach ($this->epa_codes as $b => $code) {
                foreach ($rating_scale_responses as $response) {
                    $this->tallied_responses[] = "";
                }
                foreach ($this->dataset["assessments"] as $assessment) {
                    //Offset where we are in the CSV depending on what EPA we are looking at
                    $offset = ($b) * sizeof($rating_scale_responses);
                    if ($code == $assessment["mapped_epas"][0]["objective_code"]) {
                        if ($assessment["milestone_objective"] == $milestone["objective_id"]) {
                            $this->assessment_total++;
                            if ($assessment["rating_scale_responses"]) {
                                foreach ($assessment["rating_scale_responses"] as $key => $scale_response) {
                                    if (!isset($this->tallied_responses[$key + $offset])) {
                                        $this->tallied_responses[$key + $offset] = 0;
                                    }
                                    if ($assessment["selected_iresponse_order"] === $scale_response["order"]) {
                                        $this->tallied_responses[$key + $offset]++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $this->tallied_responses[] = "";
        }

        foreach ($this->tallied_responses as &$tally) {
            if ($tally) {
                $tally .= " of " . $this->assessment_total;
            }
        }

        $this->row[] = $this->tallied_responses;
        /**
         * Flatten the array so that the CSV can handle it
         */
        $this->row = $this->flattenArray($this->row);
        fputcsv($this->fp, $this->row);
    }

    /**
     * Close the current file and add it to the ZIP folder
     */
    private function closeFileAddToZip() {
        fclose($this->fp);
        $this->zip->addFile($this->dir . "/" . $this->file_name, $this->file_name);
    }

    /**
     * Close the ZIP folder and output it as a download
     */
    private function closeZipAndOutput() {
        $this->zip->close();
        /**
         * Output the Zip file
         */
        if (file_exists($this->dir . "/" . $this->zipname) && is_readable($this->dir . "/" . $this->zipname)) {
            ob_clear_open_buffers();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"" . $this->zipname . "\"");
            header("Content-Length: " . filesize($this->dir . "/" . $this->zipname));
            header("Content-Transfer-Encoding: binary\n");
            echo file_get_contents($this->dir . "/" . $this->zipname, FILE_BINARY);
        }
    }

    /**
     * Remove the files that we have created since they are no longer necessary.
     */
    private function cleanupFiles() {
        foreach ($this->file_names as $file) {
            if (file_exists($this->dir . "/" . $file)) {
                unlink($this->dir . "/" . $file);
            }
        }
        if (file_exists($this->dir . "/" . $this->zipname)) {
            unlink($this->dir . "/" . $this->zipname);
        }
    }

    /**
     * Fetch rating scale responses for a specific item's rating_scale_id, inlcudes item responses
     * @param int $item_id
     * @return array
     */
    private function fetchRatingScaleResponses($item_id = 0) {
        $rating_scale_response_model = new Models_Assessments_RatingScale_Response();
        $responses = $rating_scale_response_model->fetchAllByItemRatingScaleID($item_id);
        return $responses;
    }

    /**
     * Query for the assessment data
     * @param $rating_scale
     * @return mixed
     */
    private function fetchAssessmentData($rating_scale) {
        global $db;
        $query = "  SELECT ir.`order`, io.`objective_id`, j.`item_id`, f.`form_id`
                    FROM `cbl_distribution_assessments` AS a 
                    JOIN `cbl_assessment_lu_types` AS b 
                    ON a.`assessment_type_id` = b.`assessment_type_id` 
                    JOIN `cbl_assessment_type_organisations` AS c 
                    ON b.`assessment_type_id` = c.`assessment_type_id`
                    JOIN `cbl_distribution_assessment_targets` AS d 
                    ON a.`dassessment_id` = d.`dassessment_id`
                    JOIN `cbl_assessments_lu_forms` AS f 
                    ON a.`form_id` = f.`form_id`
                    JOIN `cbl_assessment_form_objectives` AS h FORCE INDEX(form_id) 
                    ON f.`form_id` = h.`form_id` 
                    JOIN `cbl_assessment_form_elements` AS i 
                    ON f.`form_id` = i.`form_id` 
                    JOIN `cbl_assessments_lu_items` AS j FORCE INDEX(PRIMARY) 
                    ON i.`element_id` = j.`item_id` 
                    AND i.`element_type` = 'item'
                    JOIN `cbl_assessment_progress` AS e 
                    ON a.`dassessment_id` = e.`dassessment_id`
                    JOIN `cbl_assessment_progress_responses` as pr
                    ON pr.`aprogress_id` = e.`aprogress_id`
                    AND pr.`form_id` = f.`form_id`
                    JOIN `cbl_assessments_lu_item_responses` as ir
                    ON pr.`iresponse_id` = ir.`iresponse_id`
                    AND ir.`item_id` = j.`item_id`
                    JOIN `cbl_assessment_item_objectives` AS io 
            		ON io.`item_id` = ir.`item_id`
                    LEFT JOIN `cbl_assessments_lu_rubrics` AS ru
                    ON i.`rubric_id` = ru.`rubric_id`
                    
                    WHERE  a.`course_id` = ? 
                    AND c.`organisation_id` = ? 
                    AND a.`assessor_type` = 'internal' 
                    AND d.`target_type` = 'proxy_id' 
                    AND d.`target_value` = ? 
                    AND d.`deleted_reason_id` IS NULL 
                    AND f.`form_type_id` NOT IN (
                        SELECT `form_type_id` 
                        FROM   `cbl_assessment_form_type_meta` 
                        WHERE  `organisation_id` = ? 
                        AND `meta_name` = 'hide_from_dashboard' 
                        AND `meta_value` = 1 
                        AND `deleted_date` IS NULL
                    ) 
                    AND NOT ( a.`assessor_type` = 'internal' AND a.`assessor_value` = d.`target_value` )
                    AND e.`progress_value` = 'complete'
                    AND e.`target_type` = 'proxy_id' 
                    AND e.`target_record_id` = ? 
                    AND pr.`deleted_date` IS NULL
                    AND (j.`rating_scale_id` = ? OR ru.`rating_scale_id` = ?)
                    AND j.`deleted_date` IS NULL
                    AND io.`objective_id` IN (". implode(",", $this->milestone_id_array) .")
                    AND h.`objective_id` IN (". implode(",", $this->epa_ids) .")
                    AND io.`deleted_date` IS NULL
                    AND ir.`deleted_date` IS NULL";

        return $db->GetAll($query, array($this->course_id, $this->actor_organisation_id, $this->actor_proxy_id, $this->actor_organisation_id, $this->actor_proxy_id, $rating_scale, $rating_scale));
    }

    /**
     * Fetch all EPAs that are mapped to the provided form identifier
     * @param int $organisation_id
     * @param int $form_id
     * @param int $course_id
     * @param int $active
     * @return array
     */
    private function fetchEPAsMappedToForms($organisation_id = 0, $form_id = 0, $course_id = 0, $active = 1) {
        global $db;
        $epas = array();
        $query = "  SELECT b.`objective_code` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    JOIN `cbl_assessment_form_objectives` as d
                    ON c.`objective_id` = d.`objective_id`
                    WHERE a.`shortname` = 'epa'
                    AND a.`deleted_date` IS NULL
                    AND c.`organisation_id` = ?
                    AND d.`form_id` = ?
                    AND d.`course_id` = ?
                    AND b.`objective_active` = ?";
        $results = $db->GetAll($query, array($organisation_id, $form_id, $course_id, $active));
        if ($results) {
            $epas = $results;
        }
        return $epas;
    }

    private function flattenArray(array $array) {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }
}