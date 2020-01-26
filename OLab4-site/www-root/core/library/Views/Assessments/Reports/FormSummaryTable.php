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
 * View class for rendering the summary list of forms completed on a user (for reporting).
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_FormSummaryTable extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("completed_tasks", "target_role"));
    }

    /**
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        // Get our validated options variables
        $tasks = $options["completed_tasks"];
        $target_role = $options["target_role"];
        $report_start_date = @$options["start_date"];
        $report_end_date = @$options["end_date"];
        $group_by_distribution = @$options["group_by_distribution"];

        $render_table = ($tasks && !empty($tasks));
        $start_date = is_null($report_start_date) ? "" : "&start-date=" . date("Y-m-d", $report_start_date);
        $end_date = is_null($report_end_date) ? "" : "&end-date=" . date("Y-m-d", $report_end_date);
        ?>
        <div <?php echo $this->getClassString() ?>>
            <?php if ($render_table): ?>
                <label for="assessment-reports-group-by-distribution" class="checkbox pull-right clearfix <?php echo $target_role == "target" ? " hide" : "" ?>"<?php echo $target_role == "target" ? " disabled" : "" ?>>
                    <input type="checkbox" id="assessment-reports-group-by-distribution" <?php echo $group_by_distribution || $target_role == "target" ? "checked" : "" ?>> <?php echo $translate->_("Group by distribution");?>
                </label>
            <?php endif; ?>

            <table class="table table-striped table-bordered space-above" id="assessment-tasks-table">
                <thead>
                <?php if ($render_table): ?>
                    <tr>
                        <th width=""><?php echo $translate->_("Form Name") ?></th>
                        <?php if ($group_by_distribution): ?>
                            <th width=""><?php echo $translate->_("Distribution Name") ?></th>
                            <th width=""><?php echo $translate->_("Description") ?></th>
                        <?php endif; ?>
                        <th width="15%"><?php echo $translate->_("Completions") ?></th>
                        <th width="12%"></th>
                    </tr>
                <?php endif; ?>
                </thead>
                <tbody>
                    <?php if ($render_table): ?>
                        <?php foreach ($tasks as $form_summary):
                            $distribution_id = ($group_by_distribution) ? $form_summary["adistribution_id"] : "";
                            ?>
                            <tr>
                                <td>
                                    <?php echo html_encode($form_summary["form_title"]); ?>
                                    <?php if (@$form_summary["cperiod_title"]): ?>
                                        &nbsp;(<?php echo html_encode($form_summary["cperiod_title"]); ?>)
                                    <?php endif; ?>
                                </td>
                                <?php if ($group_by_distribution): ?>
                                    <td>
                                        <?php echo html_encode($form_summary["distribution_title"]); ?>
                                    </td>
                                    <td>
                                        <?php echo html_encode($form_summary["description"]); ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php echo $form_summary["form_count"] ?>
                                </td>
                                <td>
                                    <div class="btn-group pull-right">
                                        <button class="btn btn-default dropdown-toggle no-printing" data-toggle="dropdown" title="<?php echo $translate->_("Generate Report"); ?>">
                                            <?php echo $translate->_("Options") ?>
                                            <span class="caret"></span>
                                        </button>

                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="<?php echo ENTRADA_URL . "/assessments/reports/report?section=report&target_id={$form_summary["target_record_id"]}&form_id={$form_summary["form_id"]}&role={$target_role}&adistribution_id={$distribution_id}&strip=0&cperiod_id={$form_summary["cperiod_id"]}{$start_date}{$end_date}" ?>"><?php echo $translate->_("Generate Report"); ?></a>
                                            </li>
                                            <li>
                                                <a href="<?php echo ENTRADA_URL . "/assessments/reports/report?section=report&target_id={$form_summary["target_record_id"]}&form_id={$form_summary["form_id"]}&role={$target_role}&adistribution_id={$distribution_id}&strip=1&cperiod_id={$form_summary["cperiod_id"]}{$start_date}{$end_date}" ?>"><?php echo $translate->_("Generate Report (Without Comments)"); ?></a>
                                            </li>
                                            <?php if ($options["target_role"] != "target"): ?>
                                                <li>
                                                    <a href="<?php echo ENTRADA_URL . "/admin/assessments/forms?section=edit-form&id={$form_summary["form_id"]}" ?>" target="_blank"><?php echo $translate->_("View This Form"); ?></a>
                                                </li>
                                                <li>
                                                    <a href="<?php echo ENTRADA_URL . "/assessments/reports/?section=list&target_id={$form_summary["target_record_id"]}&form_id={$form_summary["form_id"]}&role={$target_role}&adistribution_id={$distribution_id}&cperiod_id={$form_summary["cperiod_id"]}{$start_date}{$end_date}" ?>" target="_blank"><?php echo $translate->_("View Assessments"); ?></a>
                                                </li>
                                                <?php if ($group_by_distribution): ?>
                                                    <li>
                                                        <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=$distribution_id" ?>" target="_blank"><?php echo $translate->_("View Distribution Progress"); ?></a>
                                                    </li>
                                                <?php endif;
                                            endif;
                                            ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="form-search-message text-center" colspan="4">
                                <?php echo $translate->_("There are no completed forms."); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}