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
 * This view is used in sidebars and headers, to display assessment
 * related delivery information.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_DeliveryInfo extends Views_Assessments_Base
{
    /**
     * Validate: make sure assessment record and distribution exist in
     * order to display the relevant info.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        // assessment_record
        if (!isset($options["assessment_record"])) {
            return false;
        }
        if (!is_a($options["assessment_record"], "Models_Assessments_Assessor")) {
            return false;
        }
        if (!isset($options["distribution"])) {
            return false;
        }
        if (!is_a($options["distribution"], "Models_Assessments_Distribution")) {
            return false;
        }
        // distribution
        return true;
    }

    /**
     * Render view specific error, indicating that delivery information is unreadable.
     */
    protected function renderError() {
        global $translate; ?>
        <div id="assessment-delivery-info" class="space-below">
            <?php echo $translate->_("Unable to determine task details."); ?>
        </div>
        <?php
    }

    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $assessment_record = $options["assessment_record"];
        $distribution = $options["distribution"];

        $distribution_schedule = @$options["distribution_schedule"];
        $is_pdf = @$options["is_pdf"];
        $target_name = @$options["target_name"];
        $assessor_name = @$options["assessor_name"];
        $event_name = @$options["event_name"];
        $completed_date = @$options["completed_date"];
        $learning_event_time_start = @$options["timeframe_start"];
        $learning_event_time_end = @$options["timeframe_end"];
        ?>
        <div id="assessment-delivery-info" class="space-below assessment-delivery-header">
            <?php if ($is_pdf && $target_name): ?>
                <span class="assessment-delivery-detail"><?php echo $translate->_("Target")?>: <?php echo $target_name ?></span>
            <?php endif; ?>
            <?php if ($is_pdf && ($event_name != $target_name) && ($event_name && $target_name)): ?>
                <span class="assessment-delivery-detail"><?php echo $translate->_("Event")?>: <?php echo $event_name ?></span>
            <?php endif; ?>
            <?php if ($is_pdf && $assessor_name): ?>
                <span class="assessment-delivery-detail"><?php echo $translate->_("Assessor")?>: <?php echo $assessor_name ?></span>
            <?php endif; ?>
            <?php if ($learning_event_time_start && $learning_event_time_end): ?>
                <span class="assessment-delivery-detail">
                    <?php echo $translate->_("Event date");?>: <strong><?php echo html_encode($learning_event_time_start) ?></strong> <?php echo html_encode($translate->_("to")) ?> <strong><?php echo html_encode($learning_event_time_end) ?></strong>
                </span>
            <?php endif; ?>
            <?php if ($assessment_record->getRotationStartDate() && $assessment_record->getRotationEndDate()):?>
                <?php if ($distribution && $distribution_schedule): ?>
                    <?php if ($schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID())): ?>
                        <span class="assessment-delivery-detail">
                            <strong>
                                <?php echo Entrada_Utilities_Assessments_Base::getConcatenatedBlockString($assessment_record->getID(), $schedule, $assessment_record->getRotationStartDate(), $assessment_record->getRotationEndDate(), $distribution->getOrganisationID(), ": "); ?>
                            </strong>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
                <span class="assessment-delivery-detail">
                    <strong><?php echo html_encode(date("Y-m-d", $assessment_record->getRotationStartDate())) ?></strong> <?php echo $translate->_("to") ?> <strong><?php echo date("Y-m-d", $assessment_record->getRotationEndDate()) ?></strong>
                </span>
            <?php elseif ($distribution && $distribution->getStartDate() && $distribution->getEndDate()): ?>
                <span class="assessment-delivery-detail">
                    <strong><?php echo html_encode(date("Y-m-d", $distribution->getStartDate())) ?></strong> <?php echo $translate->_("to") ?> <strong><?php echo date("Y-m-d", $distribution->getEndDate()) ?></strong>
                </span>
            <?php endif; ?>
            <?php if ($assessment_record->getDeliveryDate()): ?>
                <span class="assessment-delivery-detail">
                    <?php echo $translate->_("Delivered on"); ?> <strong><?php echo html_encode(date("Y-m-d", $assessment_record->getDeliveryDate())) ?></strong>
                </span>
            <?php endif; ?>
            <?php if ($is_pdf && $completed_date): ?>
                <span class="assessment-delivery-detail">
                    <?php echo $translate->_("Completed on"); ?> <strong><?php echo html_encode(date("Y-m-d", $completed_date)) ?></strong>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
}