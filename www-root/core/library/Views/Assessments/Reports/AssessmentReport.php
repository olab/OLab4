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
 * Displays a summary of assessment forms completed on a user.
 *
 * This view is coupled with the Entrada_Utilities_Assessments_Reports object,
 * making use of the data it provides.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_AssessmentReport extends Views_Assessments_Base
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
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        // Optional
        $strip_comments         = array_key_exists("strip_comments", $options) ? $options["strip_comments"] : null;
        $include_commenter_id   = array_key_exists("include_commenter_id", $options) ? $options["include_commenter_id"] : null;
        $include_commenter_name = array_key_exists("include_commenter_name", $options) ? $options["include_commenter_name"] : null;
        $is_evaluation          = array_key_exists("is_evaluation", $options) ? $options["is_evaluation"] : null;
        $additional_statistics  = array_key_exists("additional_statistics", $options) ? $options["additional_statistics"] : null;
        $include_positivity     = array_key_exists("include_positivity", $options) ? $options["include_positivity"] : false;
        $include_assessor_names = array_key_exists("include_assessor_names", $options) ? $options["include_assessor_names"] : false;

        // Render the full report

        if ($include_assessor_names && isset($options["report_data"]["assessors"])):
            $this->renderAssessorInfo($options["report_data"]["assessors"]);
        endif;
        unset($options["report_data"]["assessors"]);

        $form_order = 0;
        if ($options["report_data"] && !empty($options["report_data"])) {
            foreach ($options["report_data"] as $report_index => $report_node) {
                $report_index_a = explode("-", $report_index);
                $element_type = $report_index_a[0];
                $element_id = $report_index_a[1];
                switch ($element_type) {
                    case "rubric":
                        $this->renderRubric(++$form_order, $report_node, $strip_comments, $additional_statistics, $include_positivity, $include_commenter_id, $include_commenter_name);
                        break;

                    case "freetext":
                        $this->renderFreetextLabel($report_node);
                        break;

                    case "objective":
                        $this->renderObjective($element_id, ++$form_order, $report_node, $strip_comments, $include_commenter_id, $include_commenter_name);
                        break;

                    default:
                    case "element":
                        switch ($report_node["item_type"]) {
                            case "free_text":
                                if (!$strip_comments) {
                                    $this->renderFreetextComment(++$form_order, $report_node, $include_commenter_id, $include_commenter_name);
                                }
                                break;

                            case "date":
                            case "user":
                            case "fieldnote":
                            case "numeric":
                                // Not supported
                                break;

                            default:
                                // draw single line summary item
                                $this->renderChoiceSelection(++$form_order, $report_node, $strip_comments, $additional_statistics, $include_positivity, $include_commenter_id, $include_commenter_name);
                                break;
                        }
                        break;
                }
            }

        } else {
            $this->renderEmptyFormNotification($is_evaluation);
        }
    }

    /**
     * Draw a notification indicating that there are no assessments.
     */
    private function renderEmptyFormNotification($is_evaluation) {
        global $translate;
        $error_type = ($is_evaluation) ? "evaluations" : "assessments"?>
        <div class="assessment-report-node">
            <table class="table table-striped table-bordered">
                <tbody>
                    <tr>
                        <td class="form-search-message text-center" colspan="4">
                            <p class="no-search-targets space-above space-below medium"><?php echo $translate->_("No completed $error_type."); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render objective selector summary.
     *
     * @param $element_id
     * @param $display_order
     * @param $report_node
     * @param $strip_comments
     * @param $include_commenter_id
     * @param $include_commenter_name
     */
    private function renderObjective($element_id, $display_order, $report_node, $strip_comments, $include_commenter_id = false, $include_commenter_name = false) {
        // NOT SUPPORTED YET
        // TODO: Fill this in to support objective display
    }

    /**
     * Render a choice selection item; horizontal, vertical, or multiple choice are all displayed in the horizontal-style table.
     *
     * @param int $display_order
     * @param array $report_node
     * @param bool $strip_comments
     * @param $additional_statistics
     * @param $include_positivity
     * @param bool $include_commenter_id
     * @param bool $include_commenter_name
     */
    private function renderChoiceSelection($display_order, $report_node, $strip_comments, $additional_statistics, $include_positivity, $include_commenter_id = false, $include_commenter_name = false) {
        global $translate;
        $column_count = count($report_node["responses"]);
        ?>
        <div class="assessment-report-node">
            <h3><?php echo "$display_order.&nbsp;". html_encode($report_node["element_text"]); ?></h3>
            <table class="table table-bordered table-striped">
                <?php if (empty($report_node["responses"])): ?>
                    <tbody>
                        <tr>
                            <td><strong><?php echo $translate->_("There are no responses for this form element."); ?></strong></td>
                        </tr>
                    </tbody>
                <?php elseif (@count($report_node["responses"]) <= 6): ?>
                    <tr>
                        <?php foreach ($report_node["responses"] as $response): ?>
                            <td class="text-center"><?php echo html_encode($response["display_text"]); ?></td>
                        <?php endforeach;
                        if ($additional_statistics && $include_positivity): ?>
                            <td class="text-center"><?php echo $translate->_("Aggregate Positive Score / Aggregate Negative Score"); ?></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <?php
                        $overall_count = 0;
                        $included_col_count = count($report_node["responses"]);
                        $positivity_count_list = array("negative" => 0, "positive" => 0);
                        $excluded_cols = array();

                        foreach ($report_node["responses"] as $order => $response): ?>
                            <td class="text-center"><?php echo $response["count"] ?></td>
                            <?php
                            // N/A, Not Applicable and Not Observed are excluded from responses when determining average. A modifier will be set if needed, which will adjust later calculations appropriately.
                            if (strtolower($response["display_text"]) == $translate->_("n/a") ||
                                strtolower($response["display_text"]) == $translate->_("not applicable") ||
                                strtolower($response["display_text"]) == $translate->_("not observed") ||
                                strtolower($response["display_text"]) == $translate->_("did not attend") ||
                                strtolower($response["display_text"]) == $translate->_("please select")
                            ) {
                                $excluded_cols[] = $response["display_text"];
                                $included_col_count--;
                            }
                        endforeach;

                        // Positive/negative is determined by splitting the descriptors down the middle, rounding down.
                        $positivity_cutoff = round($included_col_count / 2, 0, PHP_ROUND_HALF_DOWN);

                        foreach ($report_node["responses"] as $order => $response) {
                            if (!in_array($response["display_text"], $excluded_cols)) {
                                // Positive/negative is determined by splitting the descriptors down the middle, rounding down.
                                $positivity = $order + 1 <= $positivity_cutoff ? "negative" : "positive";
                                $positivity_count_list[$positivity] += $response["count"];
                            }
                        }

                        // ADDITIONAL STATISTICS
                        if ($additional_statistics && $include_positivity):
                            $overall_count = $positivity_count_list["positive"] + $positivity_count_list["negative"]; ?>
                            <td class="text-center">
                                <?php // POSITIVE/NEGATIVE COUNTS
                                echo $positivity_count_list["positive"] . " / " . $positivity_count_list["negative"]; ?>
                            </td>
                        <?php endif; ?>
                    </tr>

                    <?php if ($additional_statistics): ?>
                        <tr class="report-statistics-row">
                            <?php
                            // PERCENTAGE OF OVERALL TOTAL FOR EACH RESPONSE
                            foreach ($report_node["responses"] as $order => $response):
                                $avg = 0;
                                if ($overall_count > 0):
                                    $avg = ($response["count"] / $overall_count) * 100;
                                endif;
                                ?>
                                <td class="text-center">
                                    <?php echo round($avg, 2, PHP_ROUND_HALF_UP) . "%"; ?>
                                </td>
                            <?php endforeach;

                            if ($include_positivity):
                                // PERCENTAGE OF OVERALL NEGATIVE/POSITIVE
                                $positive_result = $negative_result = 0;
                                if ($overall_count > 0):
                                    $positive_result = ($positivity_count_list["positive"] / $overall_count) * 100;
                                    $negative_result = ($positivity_count_list["negative"] / $overall_count) * 100;
                                endif; ?>
                                <td class="text-center">
                                    <?php echo round($positive_result, 2, PHP_ROUND_HALF_UP); ?>% / <?php echo round($negative_result, 2, PHP_ROUND_HALF_UP); ?>%
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endif;

                    if (!$strip_comments && !empty($report_node["comments"])): ?>
                        <tr>
                            <td colspan="<?php echo $column_count ?>">
                                <p><strong><?php echo $translate->_("Response Comments") ?></strong></p>
                                <ul>
                                    <?php foreach ($report_node["comments"] as $comment):
                                        $commenter_prepend = "";
                                        $commenter_prepend .= $include_commenter_id ? "[".Entrada_Assessments_Base::generateAssessorHash($comment["assessor_type"], $comment["assessor_value"])."] " : "";
                                        $commenter_prepend .= $include_commenter_name && $comment["comment_anonymity"] == "identifiable" ? $comment["assessor_name"] . " - " : "";
                                        ?>
                                        <li><?php echo html_encode($commenter_prepend) . html_encode($comment["comment_text"]) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php else: ?>
                    <?php foreach ($report_node["responses"] as $response):
                        if ($response["count"] > 0): ?>
                        <tr>
                            <td class="text-center"><?php echo html_encode($response["display_text"]); ?></td>
                            <td class="text-center"><?php echo html_encode($response["count"]); ?></td>
                        </tr>
                    <?php endif; 
                    endforeach; ?>
                    <?php if (!$strip_comments && !empty($report_node["comments"])): ?>
                        <tr>
                            <td colspan="2">
                                <p><strong><?php echo $translate->_("Response Comments") ?></strong></p>
                                <ul>
                                    <?php foreach ($report_node["comments"] as $comment):
                                        $commenter_prepend = "";
                                        $commenter_prepend .= $include_commenter_id ? "[".Entrada_Assessments_Base::generateAssessorHash($comment["assessor_type"], $comment["assessor_value"])."] " : "";
                                        $commenter_prepend .= $include_commenter_name && $comment["comment_anonymity"] == "identifiable" ? $comment["assessor_name"] . " - " : "";
                                        ?>
                                        <li><?php echo html_encode($commenter_prepend) . html_encode($comment["comment_text"]) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Draw the free-text as-is, not html_encode()'d since we want the formatting.
     *
     * @param $report_node
     */
    private function renderFreetextLabel($report_node) {
        ?>
        <div class="assessment-report-node space-below medium">
            <?php echo $report_node["element_text"]; ?>
        </div>
        <?php
    }

    /**
     * Render the comments provided for a free-text form item.
     * This method is not called when strip_comments is specified by the instantiator.
     *
     * @param $display_order
     * @param $report_node
     * @param $include_commenter_id
     * @param $include_commenter_name
     */
    private function renderFreetextComment($display_order, $report_node, $include_commenter_id = false, $include_commenter_name = false) {
        global $translate;
        $ordinal = 0;
        ?>
        <div class="assessment-report-node space-below medium">
            <h3><?php echo "$display_order.&nbsp;" . html_encode($report_node["element_text"]) ?></h3>
            <table class="table table-bordered table-striped">
                <tbody>
                    <?php if (empty($report_node["responses"])): ?>
                        <tr>
                            <td><strong><?php echo $translate->_("There are no responses for this form element."); ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report_node["responses"] as $comment):
                            $commenter_prepend = "";
                            $commenter_prepend .= $include_commenter_id ? "[".Entrada_Assessments_Base::generateAssessorHash($comment["assessor_type"], $comment["assessor_value"])."] " : "";
                            $commenter_prepend .= $include_commenter_name && $comment["comment_anonymity"] == "identifiable" ? $comment["assessor_name"]: "";
                            ?>
                            <tr>
                                <td width="5%"><?php echo ++$ordinal ?>.</td>
                                <?php if ($commenter_prepend) : ?>
                                    <td width="15%"><?php echo html_encode($commenter_prepend) ?></td>
                                <?php endif; ?>
                                <td><?php echo html_encode($comment["comment_text"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
        <?php
    }

    /**
     * Render a rubric, complete with answers, descriptors and comments.
     *
     * @param $display_order
     * @param $report_node
     * @param $strip_comments
     * @param $additional_statistics
     * @param $include_positivity
     * @param bool $include_commenter_id
     * @param bool $include_commenter_name
     */
    private function renderRubric($display_order, $report_node, $strip_comments, $additional_statistics, $include_positivity, $include_commenter_id = false, $include_commenter_name = false) {
        global $translate;
        $rubric_order = 0;
        ?>
        <div class="assessment-report-node assessment-report-node-rubric">
            <h3><?php echo "$display_order.&nbsp;" . html_encode($report_node["element_text"]); ?></h3>
            <table class="table table-bordered table-striped">
                <?php if (empty($report_node["responses"])): // This state should not happen, but we must catch it regardless. ?>

                    <tbody>
                        <tr>
                            <td><strong><?php echo $translate->_("There are no responses for this form element."); ?></strong></td>
                        </tr>
                    </tbody>

                <?php else: ?>

                    <?php
                        $descriptors = array();
                        // Get the first node, and use those response descriptors
                        $first_node = current($report_node["responses"]);
                        if (isset($first_node["rubric_response_detail"])) {
                            if ($descriptors_node = current($first_node["rubric_response_detail"]) ) {
                                foreach ($descriptors_node as $order => $response) {
                                    $descriptors[] = $response["descriptor"]; // Can be null
                                }
                            }
                        }
                        if ($additional_statistics) {
                            if ($include_positivity) {
                                $descriptors[] = $translate->_("Aggregate Positive Score / Aggregate Negative Score");
                            }
                            $descriptors[] = $translate->_("Average");
                        }
                        $column_count = count($descriptors) +1;
                    ?>
                    <?php if (!empty($descriptors)): ?>
                        <thead>
                            <tr>
                                <th width="37%">&nbsp;</th>
                                <?php foreach ($descriptors as $descriptor): // Render the descriptors ?>
                                    <th><?php echo $descriptor ? html_encode($descriptor) : "" ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                    <?php endif; ?>
                    <tbody>
                        <?php foreach ($report_node["responses"] as $response): $rubric_order++ ?>
                            <tr>
                                <td><?php echo "$rubric_order.&nbsp;" . html_encode($response["text"]) ?></td>

                                <?php
                                $count_list = array();
                                $positivity_count_list = array("negative" => 0, "positive" => 0);
                                $exclude_ardescriptor_ids = array();
                                $overall_count = 0;
                                $count = 0;
                                $modifier = 0;
                                foreach ($response["rubric_response_detail"] as $element_id => $response_detail):
                                    $included_col_count = count($response_detail);
                                    $last_item = end($response_detail);
                                    $offset = count($response_detail) - $last_item["order"];

                                    if ($element_id == $response["item_id"]):
                                        foreach ($response_detail as $order => $response_summary):
                                            // N/A, Not Applicable and Not Observed are excluded from responses when determining average. A modifier will be set if needed, which will adjust later calculations appropriately.
                                            if (strtolower($response_summary["descriptor"]) == $translate->_("n/a") ||
                                                strtolower($response_summary["descriptor"]) == $translate->_("not applicable") ||
                                                strtolower($response_summary["descriptor"]) == $translate->_("did not attend") ||
                                                strtolower($response_summary["descriptor"]) == $translate->_("not observed")
                                            ):
                                                $modifier = ((int)$order <= 1) ? -1 : 0;
                                                $exclude_ardescriptor_ids[] = $response_summary["ardescriptor_id"];
                                                $included_col_count--;
                                                break;
                                            endif;
                                        endforeach;

                                        // Positive/negative is determined by splitting the descriptors down the middle, rounding down.
                                        $positivity_cutoff = round($included_col_count / 2, 0, PHP_ROUND_HALF_DOWN);

                                        foreach ($response_detail as $order => $response_summary): ?>
                                            <td class="text-center">
                                                <?php if (!in_array($response_summary["ardescriptor_id"], $exclude_ardescriptor_ids)):
                                                    $count += (int)$response_summary["count"];

                                                    // Count list is the number of responses in the category multiplied by the "scale", or order in this case. This takes the offset and modifiers into account.
                                                    $count_list[] = (int)$response_summary["count"] * ($order + $offset + $modifier);

                                                    // Positive/negative is determined by splitting the descriptors down the middle, rounding down.
                                                    $positivity = $order <= $positivity_cutoff ? "negative" : "positive";
                                                    $positivity_count_list[$positivity] += (int)$response_summary["count"];
                                                endif;

                                                echo $response_summary["count"];
                                                if (isset($response_summary["text"]) && $response_summary["text"]): ?>
                                                    <hr />
                                                    <?php echo $response_summary["text"];
                                                endif; ?>
                                            </td>
                                        <?php endforeach;
                                    endif;
                                endforeach;

                                // ADDITIONAL STATISTICS
                                if ($additional_statistics):
                                    if ($include_positivity): ?>
                                    <td class="text-center">
                                        <?php
                                            // POSITIVE/NEGATIVE COUNTS
                                            echo $positivity_count_list["positive"] . " / " . $positivity_count_list["negative"];
                                        ?>
                                    </td>
                                    <?php endif;

                                    $overall_count = $positivity_count_list["positive"] + $positivity_count_list["negative"];

                                    // OVERALL AVERAGE "SCORE"
                                    // The average is the sum of all of the count lists (see explanation above) divided by the number of "scales".
                                    $count_result = array_sum($count_list) / ($count == 0 ? 1 : $count);
                                    ?>
                                    <td class="text-center">
                                        <?php echo round($count_result, 1, PHP_ROUND_HALF_UP); ?>
                                    </td>
                                <?php endif; ?>

                                </tr>

                                <?php if ($additional_statistics): ?>
                                    <tr class="report-statistics-row">
                                        <td>&nbsp;</td>
                                        <?php
                                        // PERCENTAGE OF OVERALL TOTAL FOR EACH RESPONSE
                                        foreach ($response["rubric_response_detail"] as $element_id => $response_detail):
                                            foreach ($response_detail as $order => $response_summary):
                                                $avg = 0;
                                                if ($overall_count > 0):
                                                    $avg = ((int)$response_summary["count"] / $overall_count) * 100;
                                                endif;
                                                ?>
                                                <td class="text-center">
                                                    <?php echo round($avg, 2, PHP_ROUND_HALF_UP) . "%"; ?>
                                                </td>
                                                <?php
                                            endforeach;
                                        endforeach;

                                        if ($include_positivity):
                                        // PERCENTAGE OF OVERALL NEGATIVE/POSITIVE
                                        $positive_result = $negative_result = 0;
                                        if ($overall_count > 0):
                                            $positive_result = ($positivity_count_list["positive"] / $overall_count) * 100;
                                            $negative_result = ($positivity_count_list["negative"] / $overall_count) * 100;
                                        endif; ?>
                                        <td class="text-center">
                                            <?php echo round($positive_result, 2, PHP_ROUND_HALF_UP); ?>% / <?php echo round($negative_result, 2, PHP_ROUND_HALF_UP); ?>%
                                        </td>
                                        <?php endif; ?>
                                        <td>&nbsp;</td>
                                    </tr>
                                <?php endif;

                                if (!$strip_comments):
                                $response_comments = array();
                                // Comments are attached on a per-item-response basis. Group the comments to display in one row.
                                foreach ($response["rubric_response_detail"] as $element_id => $response_detail) {
                                    if ($element_id == $response["item_id"]) {
                                        foreach ($response_detail as $order => $response_summary) {
                                            foreach ($response_summary["comments"] as $comment) {
                                                $response_comments[] = $comment;
                                            }
                                        }
                                    }
                                }
                                if (!empty($response_comments)): ?>
                                <tr>
                                    <td colspan="<?php echo $column_count ?>">
                                        <p><strong><?php echo $translate->_("Response Comments") ?></strong></p>
                                        <ul>
                                        <?php foreach ($response_comments as $comment):
                                            $commenter_prepend = "";
                                            $commenter_prepend .= $include_commenter_id ? "[".Entrada_Assessments_Base::generateAssessorHash($comment["assessor_type"], $comment["assessor_value"])."] " : "";
                                            $commenter_prepend .= $include_commenter_name && $comment["comment_anonymity"] == "identifiable" ? $comment["assessor_name"] . " - " : "";
                                            ?>
                                            <li><?php echo html_encode($commenter_prepend) . html_encode($comment["comment_text"]) ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>

                <?php endif;?>
            </table>
        </div>
        <?php
    }

    /**
     * Render a header of assessor information.
     *
     * @param $assessors
     */
    private function renderAssessorInfo($assessors) {
        if (empty($assessors)):
            return;
        endif;
        global $translate;
        ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?php echo $translate->_("Assessor Name") ?></th>
                    <th><?php echo $translate->_("Assessor Email") ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($assessors as $assessor): ?>
                <tr>
                    <td><?php echo $assessor["assessor_name"]; ?></td>
                    <td><a href="mailto:<?php echo html_encode($assessor["assessor_email"]); ?>"><?php echo $assessor["assessor_email"]; ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

}