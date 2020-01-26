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
 * View class for rendering the sidebar target information, specifically for event
 * type related assessments, appended to the assessments sidebar.
 *
 * This view expects to use a learning_event_data array, containing information
 * about a learning event target, be it a user that is part of an event, or the
 * event itself. This data structure is built using the DistributionLearningEvent
 * utility object.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_EventTarget extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        // Data fetched via Entrada_Utilities_Assessments_DistributionLearningEvent->getLearningEventTargetData()
        if (!isset($options["learning_event_data"]) || !is_array($options["learning_event_data"]) || empty($options["learning_event_data"])) {
            return false;
        }
        if (!isset($options["delivery_date"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        // Get our validated options variables
        $learning_event_target = $options["learning_event_data"];
        $delivery_date = $options["delivery_date"];

        // Optionals
        $include_assessing_label = isset($options["include_assessing_heading"]) ? $options["include_assessing_heading"] : false;
        $include_target_label = isset($options["include_target_heading"]) ? $options["include_target_heading"] : true;
        ?>
        <?php if ($include_assessing_label):?>
            <h4 class="course-target-heading"> <?php echo $translate->_("Currently Assessing")?>: </h4>
        <?php endif; ?>
        <?php if ($include_target_label) : ?>
            <h3><?php echo $translate->_("Target"); ?>:</h3>
        <?php endif; ?>
        <?php if ($learning_event_target["target_type"] == "event_id"): ?>
            <div class="user-metadata">
                <p class="course-name"><?php echo html_encode($learning_event_target["event_name"]); ?></p>
            </div>
        <?php elseif ($learning_event_target["target_type"] == "proxy_id"): ?>
            <div class="user-image">
                <img src="<?php echo $learning_event_target["user_avatar"] ?>">
            </div>
            <div class="user-metadata">
                <p class="user-fullname"><?php echo html_encode($learning_event_target["user_name"]); ?></p>
                <p class="user-organisation"><?php echo html_encode($learning_event_target["user_group"]); ?> <span>&bull;</span> <?php echo html_encode($learning_event_target["user_organisation_title"]);?></p>
                <a class="user-email" href="#"><?php echo html_encode($learning_event_target["user_email"]); ?></a>
            </div>
        <?php endif; ?>
        <?php if ($learning_event_target["target_type"] != "event_id"): // Draw the event title when the event itself isn't the target ?>
            <div class="event-metadata">
                <p class="event-name"><?php echo $translate->_("Event")?>: <?php echo html_encode($learning_event_target["event_name"]); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($learning_event_target["timeframe_start"] && $learning_event_target["timeframe_end"]): ?>
        <div id="assessment-date-range-info" class="muted space-below medium">
            <span class="assessment-delivery-detail">
                <strong><?php echo html_encode($learning_event_target["timeframe_start"]) ?></strong> <?php echo html_encode($translate->_("to")) ?> <strong><?php echo html_encode($learning_event_target["timeframe_end"]) ?></strong>
            </span>
            <?php if ($delivery_date): ?>
                <span class="assessment-delivery-detail"><?php echo $translate->_("Delivered on")?> <strong><?php echo html_encode(date("Y-m-d", $delivery_date));?></strong></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php
    }
}