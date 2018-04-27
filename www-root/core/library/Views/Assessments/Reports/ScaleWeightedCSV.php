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
 * Generates a CSV summary of assessment forms completed for a distribution.
 *
 * This view is coupled with the Entrada_Utilities_Assessments_CSVReports object,
 * making use of the data it provides.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_ScaleWeightedCSV extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["report_data"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the CSV.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $csv_title = Entrada_Utilities::arrayValueOrDefault($options, "csv_title", $translate->_("Assessment Report " . time()));

        ob_clear_open_buffers();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$csv_title.csv\"");
        header("Content-Transfer-Encoding: binary");

        if ($output_file = fopen("php://output", "w")) {

            // Output header row.
            $header = array($translate->_("Student/Staff Number"));
            foreach ($options["report_data"]["item_data"] as $item) {
                $header[] = $item["item_text"];
            }
            fputcsv($output_file, $header);

            // Output a row for each progress/attempt.
            foreach ($options["report_data"]["response_data"] as $progress_id => $progress) {
                $attempt_row = array();

                foreach ($progress as $target_index => $response_data) {
                    // Loop through according to the established item order form the header, as not all progress records may have all responses.
                    foreach ($options["report_data"]["item_data"] as $item) {
                        $cell = "";
                        // Check to see if we have any responses that match this item.
                        foreach ($response_data as $item_id => $responses) {
                            if ($item_id == $item["item_id"]) {
                                foreach ($responses as $response) {
                                    // Student/staff number in first cell.
                                    $attempt_row[0] = $response["target_number"];

                                    /**
                                     * Comments are output as text.
                                     * Single response items display the scale wight, or order of the response in ascending order.
                                     * Multiple responses have their response text output.
                                     */
                                    if ($response["itemtype_shortname"] == "free_text") {

                                        $cell = $response["comments"];
                                    } elseif ($response["itemtype_shortname"] == "horizontal_multiple_choice_multiple" ||
                                        $response["itemtype_shortname"] == "vertical_multiple_choice_multiple" ||
                                        $response["itemtype_shortname"] == "selectbox_multiple"
                                    ) {

                                        /* OLD descriptor sum logic
                                        // First check if we can cast all of the descriptors.
                                        $int_castable = true;
                                        foreach ($responses as $multi_response) {
                                            if (!$multi_response["response_descriptor"] || !is_numeric($multi_response["response_descriptor"])) {
                                                $int_castable = false;
                                            }
                                        }
                                        if ($int_castable) {
                                            $total = 0;
                                            foreach ($responses as $multi_response) {
                                                $total += (int)$multi_response["response_descriptor"];
                                            }
                                            $cell = $total;
                                        } else {
                                            $cell = "?";
                                        }*/

                                        /* This is a bit janky, we need to aggregate the responses into once cell, but we
                                        don't want to output the set for each response, so keep overwriting the cell. */
                                        $cell = "";
                                        foreach ($responses as $multi_response) {
                                            $cell .= $multi_response["item_response_text"] . ",";
                                        }
                                        $cell = rtrim($cell, ",");
                                        // Comments are on an item level, so we only want to output them once per response set.
                                        if ($responses[0]["comments"]) {
                                            $cell .= " ({$responses[0]["comments"]})";
                                        }
                                    } else {

                                        // Use scale weight where available, otherwise output a "weight" based on the order of the response.
                                        if (isset($response["response_weight"])) {
                                            $cell = $response["response_weight"];
                                        } else {
                                            $cell = ($response["response_order"] - 1);
                                        }
                                    }
                                }
                            }
                        }
                        $attempt_row[] = $cell;
                    }
                }

                fputcsv($output_file, $attempt_row);
            }

            fclose($output_file);
        }
    }
}