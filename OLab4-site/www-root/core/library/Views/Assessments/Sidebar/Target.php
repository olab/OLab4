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
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_Target extends Views_Assessments_Base {
    
    /**
     * Ensure that our required variables exist.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("target_name", "target_type", "assessment_in_progress", "associated_record_type", "delivery_date"));
    }

    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $assessment_in_progress = $options["assessment_in_progress"];
        $assessor_is_viewer     = array_key_exists("assessor_is_viewer", $options) ? $options["assessor_is_viewer"] : false;

        $delivery_date          = $options["delivery_date"];
        $current_target_type    = $options["target_type"];
        $target_name            = $options["target_name"];
        $target_organisation    = array_key_exists("target_organisation", $options) ? $options["target_organisation"] : "";

        $associated_record_type = $options["associated_record_type"];
        $associated_entity_name = array_key_exists("associated_entity_name", $options) ? $options["associated_entity_name"] : null;
        $start_date             = array_key_exists("start_date", $options) ? $options["start_date"] : null;
        $end_date               = array_key_exists("end_date", $options) ? $options["end_date"] : null;

        // For proxy ID only
        $target_photo_uri       = array_key_exists("target_photo_uri", $options) ? $options["target_photo_uri"] : null;
        $target_group           = array_key_exists("target_group", $options) ? $options["target_group"] : "";
        $target_email           = array_key_exists("target_email", $options) ? $options["target_email"] : "";

        // Create a localized string of what the target type is, for append
        switch ($current_target_type) {
            case "course_id":
                $localized_target_heading_assessing = $translate->_("Currently Assessing Course");
                $localized_target_heading = $translate->_("Target Course");
                break;
            case "schedule_id":
                $localized_target_heading_assessing = $translate->_("Currently Assessing Rotation");
                $localized_target_heading = $translate->_("Target Rotation");
                break;
            case "event_id":
                $localized_target_heading_assessing = $translate->_("Currently Assessing Event");
                $localized_target_heading = $translate->_("Target Event");
                break;
            case "external":
            case "external_hash":
                $localized_target_heading_assessing = $translate->_("Currently Assessing External Target");
                $localized_target_heading = $translate->_("External Target");
                $target_organisation = ""; // TODO: Remove/Change this as appropriate when external targets are implemented
                break;
            default:
                $localized_target_heading_assessing = $translate->_("Currently Assessing");
                $localized_target_heading = $translate->_("Target");
                break;
        }
        ?>
        <div id="target-card" class="match-height">
            <?php if ($assessor_is_viewer && $assessment_in_progress): ?>
                <h4 class="heading course-target-heading"><?php echo $localized_target_heading_assessing ?></h4>
            <?php else: ?>
                <h3 class="heading"><?php echo $localized_target_heading ?></h3>
            <?php endif; ?>
            <?php if ($target_photo_uri): ?>
                <div class="user-image">
                    <img src="<?php echo $target_photo_uri ?>">
                </div>
            <?php endif; ?>
            <div class="user-metadata">
                <p class="user-fullname"><?php echo html_encode($target_name) ?></p>
                <?php if ($target_group && $target_organisation): ?>
                    <p class="user-organisation"><?php echo html_encode($target_group) ?> <span>•</span> <?php echo html_encode($target_organisation) ?></p>
                <?php else: ?>
                    <?php if ($target_group): ?>
                        <p class="user-organisation"><?php echo html_encode($target_group) ?></p>
                    <?php endif; ?>
                    <?php if($target_organisation): ?>
                        <p class="user-organisation"><?php echo html_encode($target_organisation) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($target_email): ?>
                    <div class="email-wrapper">
                        <a class="user-email" href="#"><?php echo html_encode($target_email) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="clearfix"></div>
        <?php if ($delivery_date): ?>
            <div class="assessment-delivery-detail-delivery-date well">
                <span class="assessment-delivery-detail"><?php echo sprintf($translate->_("Task delivered on <strong>%s</strong>"), $delivery_date); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($associated_record_type == "event_id"): ?>
            <div class="assessment-delivery-header">
                <div class="assessment-delivery-detail well">
                    <?php if ($current_target_type != "event_id"): ?>
                        <h3 class="heading"><?php echo $translate->_("Event Information"); ?></h3>
                        <div class="event-metadata">
                            <p class="event-name"><?php echo html_encode($associated_entity_name) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($start_date && $end_date): ?>
                        <span class="assessment-delivery-detail">
                            <strong><?php echo $start_date ?></strong> <?php echo $translate->_("to") ?> <strong><?php echo $end_date ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($associated_record_type == "schedule_id"): ?>
            <div class="assessment-delivery-header">
                <div class="assessment-delivery-detail well">
                <?php if ($start_date && $end_date): ?>
                    <h3 class="heading"><?php echo $translate->_("Rotation Information"); ?></h3>
                    <div class="event-metadata">
                        <p class="event-name"><?php echo html_encode($associated_entity_name) ?></p>
                    </div>
                    <div class="assessment-delivery-detail">
                        <strong><?php echo $start_date ?></strong> <?php echo $translate->_("to") ?> <strong><?php echo $end_date ?></strong>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }
}