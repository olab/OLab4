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
 * View class for rendering the sidebar target switcher, appended
 * to the assessments sidebar.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_TargetsSwitcher extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["distribution"]) || !is_a($options["distribution"], "Models_Assessments_Distribution")) {
            return false;
        }
        if (!isset($options["assessment_record"]) || !is_a($options["assessment_record"], "Models_Assessments_Assessor")) {
            return false;
        }
        if (!isset($options["targets"]) && !is_array($options["targets"])) {
            return false;
        }
        if (!isset($options["target_name"]) || !isset($options["targets_pending"]) || !isset($options["targets_inprogress"]) || !isset($options["targets_complete"])) {
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
        $distribution = $options["distribution"];
        $assessment_record = $options["assessment_record"];
        $current_target_name = $options["target_name"];
        $targets = $options["targets"];
        $targets_pending = $options["targets_pending"];
        $targets_inprogress = $options["targets_inprogress"];
        $targets_complete = $options["targets_complete"];
        ?>
        <?php if (count($targets) > 1): ?>
        <div class="btn-group target-list clearfix">
            <a class="btn dropdown-toggle list-btn" href="#" data-toggle="dropdown"><?php echo $translate->_("Choose a Target") ?><span class="assessment-dropdown-arrow"></span></a>
            <ul id="dropdown-menu" class="dropdown-menu targets-ul">
                <li class="target-search-listitem">
                    <div id="target-search-bar">
                        <input class="search-icon" id="target-search-input" type="text" placeholder="<?php echo $translate->_("Search Targets...")?>"/>
                    </div>
                </li>
                <li id="target-pending-listitem-header" class="target-listitem-header"><?php echo $translate->_("Forms Not Started");?>
                    <span id="targets-pending-count" class="badge pending pull-right"><?php echo $targets_pending ?></span>
                </li>
                <?php if ($targets_pending): ?>
                    <?php foreach ($targets as $target): ?>
                        <?php if (in_array("pending", $target["progress"])):
                            $schedule_id = (isset($schedule) && $schedule ? $schedule->getID() : "");
                            $dassessment_id = $assessment_record->getID();
                            $assessor_value = $assessment_record->getAssessorValue();
                            $external_hash = $assessment_record->getExternalHash();
                            $path = ($external_hash) ? "/assessment/" : "/assessments/assessment";
                            $url = ENTRADA_URL . "$path?adistribution_id={$distribution->getID()}&schedule_id=$schedule_id&target_record_id={$target["target_record_id"]}&dassessment_id=$dassessment_id&assessor_value=$assessor_value&external_hash=$external_hash"; ?>
                            <li class="target-listitem target-listitem-pending">
                                <div class="clearfix">
                                    <a class="target-name pull-left" href="<?php echo $url?>"><?php echo html_encode($target["name"]) ?></a>
                                    <?php if (!$distribution->getDeletedDate()): ?>
                                        <a class="change-target pull-right" href="<?php echo $url?>" data-toggle="tooltip" data-target-record-id="<?php echo $target["target_record_id"]?>" title="<?php sprintf($translate->_("Change your current target and responses from %s to %s"), $current_target_name, html_encode($target["name"]))?>">
                                            <i class="icon-retweet"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else :?>
                    <li id="no-target-pending-listitem-header" class="no-target-listitem">
                        <?php echo $translate->_("No forms not yet started.") ?>
                    </li>
                <?php endif; // End targets pending list ?>

                <li id="target-inprogress-listitem-header" class="target-listitem-header">
                    <?php echo $translate->_("Forms In Progress") ?>
                    <span id="targets-inprogress-count" class="badge inprogress pull-right"><?php echo $targets_inprogress ?></span>
                </li>

                <?php if ($targets_inprogress): ?>
                    <?php foreach ($targets as $target): ?>
                        <?php if (in_array("inprogress", $target["progress"])):
                            $schedule_id = (isset($schedule) && $schedule ? $schedule->getID() : "");
                            $dassessment_id = $assessment_record->getID();
                            $assessor_value = $assessment_record->getAssessorValue();
                            $external_hash = $assessment_record->getExternalHash();
                            $progress_id_str = (array_key_exists("aprogress_id", $target)) ? "&aprogress_id={$target["aprogress_id"]}" : "";
                            $path = ($external_hash) ? "/assessment/" : "/assessments/assessment";
                            $url = ENTRADA_URL . "$path?adistribution_id={$distribution->getID()}&schedule_id=$schedule_id{$progress_id_str}&target_record_id={$target["target_record_id"]}&dassessment_id=$dassessment_id&assessor_value=$assessor_value&external_hash=$external_hash"; ?>
                            <li class="target-listitem target-listitem-inprogress">
                              <div class="clearfix">
                                  <a class="target-name inprogress pull-left" href="<?php echo $url?>"><?php echo html_encode($target["name"]) ?></a>
                              </div>
                            </li>
                            <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li id="no-target-inprogress-listitem-header" class="no-target-listitem"><?php echo $translate->_("No forms in progress.")?></li>
                <?php endif; // end targets in progress list ?>

                <li id="target-complete-listitem-header" class="target-listitem-header">
                    <?php echo $translate->_("Completed Forms") ?>
                    <span id="targets-complete-count" class="badge complete pull-right"><?php echo $targets_complete ?></span>
                </li>

                <?php if ($targets_complete): ?>
                    <?php foreach ($targets as $target): ?>
                        <?php if (in_array("complete", $target["progress"])):
                            $schedule_id = (isset($schedule) && $schedule ? $schedule->getID() : "");
                            $dassessment_id = $assessment_record->getID();
                            $assessor_value = $assessment_record->getAssessorValue();
                            $external_hash = $assessment_record->getExternalHash();
                            $progress_id_str = (array_key_exists("aprogress_id", $target)) ? "&aprogress_id={$target["aprogress_id"]}" : "";
                            $path = ($external_hash) ? "/assessment/" : "/assessments/assessment";
                            $url = ENTRADA_URL . "$path?adistribution_id={$distribution->getID()}&schedule_id=$schedule_id&target_record_id={$target["target_record_id"]}$progress_id_str&dassessment_id=$dassessment_id&assessor_value=$assessor_value&external_hash=$external_hash"; ?>
                            <li class="target-listitem target-listitem-complete">
                                <a href="<?php echo $url?>"><?php echo html_encode($target["name"]) ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li id="no-target-complete-listitem-header" class="no-target-listitem"><?php echo $translate->_("No forms completed.") ?></li>
                <?php endif; // end of targets completed list ?>
              </ul>
        </div>
        <?php endif;
    }
}