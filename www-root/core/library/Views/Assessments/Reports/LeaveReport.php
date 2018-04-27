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
 * Displays a summary of rotation leave for residents.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_LeaveReport extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array())
    {
        if (!isset($options["report_data"])) {
            return false;
        }
        if (!isset($options["target_names"])) {
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
        global $translate;
        $generate_as_pdf    = @$options["generate_pdf"]; // when generating PDF, we hide the button
        ?>

        <input type="hidden" name="current-page" id="current-page" value="rotation-leave"/>
        <?php if (!$generate_as_pdf): ?>
            <a class="btn btn-default space-above space-left pull-right" id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF") ?></a>
        <?php
        endif;
        $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
        $pdf_modal->render(array(
            "action_url" => ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports",
            "label" => $translate->_("Please confirm that you want to download the Leave Tracking Report PDF.")
        ));
        if($options["description"]) {
            ?>
            <div class="space-below">
                <h4><?php echo $translate->_("Description"); ?></h4>
                <ul>
                    <li><h4><?php echo ucfirst($options["description"]); ?></h4></li>
                </ul>
            </div>
            <?php
        }
        if($options["start-date"] && $options["end-date"]) {
            ?>
            <div>
                <h4><?php echo $translate->_("Report Date Range"); ?></h4>
                <ul>
                    <li><h4 class="start-date space-right inline"><?php echo date("Y-m-d", $options["start-date"]); ?></h4><h4 class="space-right inline"><?php echo " to "?></h4><h4 class="end-date inline"><?php echo date("Y-m-d", $options["end-date"])  ?></h4></li>
                </ul>
            </div>
            <?php
        }
        if($options["target_names"]) {
            foreach($options["target_names"] as $key=>$target_name) {
                ?>
                <h2><?php echo $target_name; ?></h2>
                <input type="hidden" name="target_ids[]" class="target-ids" value="<?php echo $key?>" />
                <?php
                if($options["report_data"][$key]) {
                    foreach ($options["report_data"] as $leaves) {
                        if ($leaves && $leaves[0]->getProxyID() == $key) {
                            $totalWeekDays = 0;
                            $grandTotalDays = 0;
                            $reportRangeDays = 0;
                            if ($leaves) {
                                $this->renderTableHeader($options);
                                foreach ($leaves as $leave) {
                                    $leave_type = Models_Leave_Type::fetchRowByID($leave->getTypeID());
                                    $weekDay = $leave->getWeekdaysUsed();
                                    $totalDays = $leave->getDaysUsed();
                                    $totalWeekDays += $weekDay;
                                    $grandTotalDays += $totalDays;
                                    ?>
                                    <tr>
                                        <td><?php echo $leave_type->getTypeValue(); ?></td>
                                        <td><?php echo date("Y-m-d", $leave->getStartDate()) . " to " . date("Y-m-d", $leave->getEndDate()); ?></td>
                                        <td><?php echo $weekDay ? $weekDay : "N/A"; ?></td>
                                        <td><?php echo $leave->getWeekendDaysUsed() ? $leave->getWeekendDaysUsed() : "N/A"; ?></td>
                                        <td><?php echo $totalDays; ?></td>
                                        <?php if ($options["comments"]) { ?>
                                            <td><?php echo $leave->getComments() ?></td>
                                        <?php } ?>
                                    </tr>
                                    <?php
                                    if ($leave->getStartDate() <= $options["start-date"] && $leave->getEndDate() <= $options["end-date"]) {
                                        $reportRangeDays += (($leave->getEndDate() - $options["start-date"]) / (60 * 60 * 24));
                                    } elseif ($leave->getStartDate() >= $options["start-date"] && $leave->getEndDate()  <= $options["end-date"]) {
                                        $reportRangeDays += (($leave->getEndDate() - $leave->getStartDate()) / (60 * 60 * 24)) + 1;
                                    } elseif ($leave->getStartDate() >= $options["start-date"] && $leave->getEndDate() >= $options["end-date"]) {
                                        $reportRangeDays += (($options["end-date"] - $leave->getStartDate()) / (60 * 60 * 24));
                                    } elseif ($leave->getStartDate() <= $options["start-date"] && $leave->getEndDate() >= $options["end-date"]) {
                                        $reportRangeDays += (($options["end-date"] - $options["start-date"]) / (60 * 60 * 24));
                                    }
                                }
                                ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <strong><?php echo $translate->_("Days Within Report Range: ". round($reportRangeDays, 2)); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6">
                                        <strong><?php echo $translate->_("Total days used for this resident: " . round($totalWeekDays, 2) . " Week Day(s), " . round($grandTotalDays, 2) . " Total Days"); ?></strong>
                                    </td>
                                </tr>
                                </tfoot>
                                </table>
                                <?php
                            }
                        }
                    }
                } else {
                    $this->renderEmptyResults();
                }
            }
        }
    }

    private function renderEmptyResults() {
        global $translate; ?>
        <div class="assessment-report-node">
            <table class="table table-striped table-bordered">
                <tbody>
                <tr>
                    <td class="form-search-message text-center" colspan="4">
                        <p class="no-search-targets medium" style="margin: 0;"><?php echo $translate->_("No leave scheduled for this user."); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function renderTableHeader($options = array()) {
        global $translate; ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th><?php echo $translate->_("Leave Type"); ?></th>
                    <th><?php echo $translate->_("Date Range"); ?></th>
                    <th><?php echo $translate->_("Weekdays Used"); ?></th>
                    <th><?php echo $translate->_("Weekend Days Used"); ?></th>
                    <th><?php echo $translate->_("Total Days Used"); ?></th>
                    <?php if ($options["comments"]) { ?>
                    <th><?php echo $translate->_("Comments"); ?></th>
                    <?php } ?>
                </tr>
            </thead>
        <tbody>
        <?php
    }
}