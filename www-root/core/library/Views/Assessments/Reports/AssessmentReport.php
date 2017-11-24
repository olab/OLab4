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
        $strip_comments = @$options["strip_comments"];
        $is_evaluation = @$options["is_evaluation"];

        // Render the full report
        $form_order = 0;
        if ($options["report_data"] && !empty($options["report_data"])) {
            foreach ($options["report_data"] as $report_index => $report_node) {
                $report_index_a = explode("-", $report_index);
                $element_type = $report_index_a[0];
                $element_id = $report_index_a[1];
                switch ($element_type) {
                    case "rubric":
                        $this->renderRubric(++$form_order, $report_node, $strip_comments);
                        break;

                    case "freetext":
                        $this->renderFreetextLabel($report_node);
                        break;

                    case "objective":
                        $this->renderObjective($element_id, ++$form_order, $report_node, $strip_comments);
                        break;

                    default:
                    case "element":
                        switch ($report_node["item_type"]) {
                            case "free_text":
                                if (!$strip_comments) {
                                    $this->renderFreetextComment(++$form_order, $report_node);
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
                                $this->renderChoiceSelection(++$form_order, $report_node, $strip_comments);
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
     */
    private function renderObjective($element_id, $display_order, $report_node, $strip_comments) {
        // NOT SUPPORTED YET
        // TODO: Fill this in to support objective display
    }

    /**
     * Render a choice selection item; horizontal, vertical, or multiple choice are all displayed in the horizontal-style table.
     *
     * @param int $display_order
     * @param array $report_node
     * @param bool $strip_comments
     */
    private function renderChoiceSelection($display_order, $report_node, $strip_comments) {
        global $translate;
        $column_count = count($report_node["responses"]);
        ?>
        <div class="assessment-report-node">
            <h3><?php echo "$display_order.&nbsp;". html_encode($report_node["element_text"]) ?></h3>
            <table class="table table-bordered table-striped">
                <?php if (empty($report_node["responses"])): ?>
                    <tbody>
                        <tr>
                            <td><strong><?php echo $translate->_("There are no responses for this form element."); ?></strong></td>
                        </tr>
                    </tbody>
                <?php else: ?>
                    <tr>
                        <?php foreach ($report_node["responses"] as $response): ?>
                            <td class="text-center"><?php echo html_encode($response["display_text"]) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($report_node["responses"] as $response): ?>
                            <td class="text-center"><?php echo $response["count"] ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php if (!$strip_comments && !empty($report_node["comments"])): ?>
                        <tr>
                            <td colspan="<?php echo $column_count ?>">
                                <p><strong><?php echo $translate->_("Response Comments") ?></strong></p>
                                <ul>
                                    <?php foreach ($report_node["comments"] as $comment): ?>
                                        <li><?php echo html_encode($comment) ?></li>
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
     */
    private function renderFreetextComment($display_order, $report_node) {
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
                        <?php foreach ($report_node["responses"] as $comment): ?>
                            <tr>
                                <td width="5%"><?php echo ++$ordinal ?>.</td>
                                <td><?php echo html_encode($comment) ?></td>
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
     */
    private function renderRubric($display_order, $report_node, $strip_comments) {
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
                                <?php foreach ($response["rubric_response_detail"] as $element_id => $response_detail): ?>
                                    <?php if ($element_id == $response["item_id"]): ?>
                                        <?php foreach ($response_detail as $order => $response_summary): ?>
                                            <td>
                                                <?php echo $response_summary["count"] ?>
                                                <?php if (isset($response_summary["text"]) && $response_summary["text"]): ?>
                                                    <hr />
                                                    <?php echo $response_summary["text"] ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endif;?>
                                <?php endforeach; ?>
                            </tr>
                            <?php if (!$strip_comments): ?>
                            <?php
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
                                        <?php foreach ($response_comments as $comment): ?>
                                            <li><?php echo html_encode($comment) ?></li>
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
}