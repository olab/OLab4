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
class Views_Assessments_Sidebar_TargetsSwitcher extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("targets"))) {
            return false;
        }
        if (!$this->validateIsSet(
                $options,
                array(
                    "dassessment_id",           // int
                    "max_attempts",             // int
                    "external_hash",            // string
                    "target_name",              // string
                    "targets_pending",          // int
                    "targets_inprogress",       // int
                    "targets_complete",         // int
                    "distribution_deleted_date",// int|null
                    "allow_progress_swap"       // bool
                )
            )
        ) {
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

        $dassessment_id           = $options["dassessment_id"];
        $max_attempts             = $options["max_attempts"];
        $min_attempts             = $options["min_attempts"];
        $distribution_is_deleted  = $options["distribution_deleted_date"];
        $external_hash            = $options["external_hash"];
        $current_target_name      = $options["target_name"];
        $targets_pending          = $options["targets_pending"];
        $targets_inprogress       = $options["targets_inprogress"];
        $targets_complete         = $options["targets_complete"];
        $targets                  = $options["targets"];
        $allow_progress_swap      = $options["allow_progress_swap"];

        $path = ($external_hash) ?
            ENTRADA_URL . "/assessment/?external_hash={$external_hash}&dassessment_id={$dassessment_id}" :
            ENTRADA_URL . "/assessments/assessment?dassessment_id={$dassessment_id}";
        ?>
        <?php if (count($targets) > 1): ?>
        <div id="target-switcher" class="btn-group target-list clearfix">
            <a class="btn dropdown-toggle list-btn" href="#" data-toggle="dropdown">
                <?php echo $translate->_("Choose a Target") ?><span class="assessment-dropdown-arrow"></span>
            </a>
            <ul class="dropdown-menu targets-ul">
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
                        <?php if (in_array("pending", $target["progress"]) && $target["counts"]["inprogress"] == 0):
                            $url = "$path&atarget_id={$target["atarget_id"]}"; ?>
                            <li class="target-listitem target-listitem-pending">
                                <div class="clearfix">
                                    <a class="pull-left" href="<?php echo $url?>">
                                        <span class="target-name"><?php echo html_encode($target["name"]) ?></span>
                                        <?php if (!$distribution_is_deleted && $allow_progress_swap): ?>
                                            <span class="change-target pull-right"
                                               data-toggle="tooltip"
                                               data-target-record-id="<?php echo $target["target_record_id"] ?>"
                                               data-target-type="<?php echo $target["target_type"] ?>"
                                               title="<?php echo sprintf($translate->_("Change your current target and responses from %s to %s"), $current_target_name, html_encode($target["name"]))?>">
                                                <span class="fa fa-refresh"></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($max_attempts > 1):
                                            if ($target["counts"]["pending"] == 1) {
                                                $pending_target_tooltip = sprintf(
                                                    $translate->_("There is 1 form remaining to be completed out of a total of %s %s for this target."),
                                                    $max_attempts,
                                                    $max_attempts == 1 ? $translate->_("form") : $translate->_("forms")
                                                );
                                            } else {
                                                $pending_target_tooltip = sprintf(
                                                    $translate->_("There are %s forms remaining to be completed out of a total of %s %s for this target."),
                                                    $target["counts"]["pending"],
                                                    $max_attempts,
                                                    $max_attempts == 1 ? $translate->_("form") : $translate->_("forms")
                                                );
                                            }
                                            ?>
                                            <span class="badge complete pull-right target-progress-tooltip pull-right space-left"
                                                  data-toggle="tooltip"
                                                  title="<?php echo $pending_target_tooltip ?>"><?php echo $target["counts"]["pending"] ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else :?>
                    <li id="no-target-pending-listitem-header" class="no-target-listitem">
                        <?php echo $translate->_("All forms have been started.") ?>
                    </li>
                <?php endif; // End targets pending list ?>

                <li id="target-inprogress-listitem-header" class="target-listitem-header">
                    <?php echo $translate->_("Forms In Progress") ?>
                    <span id="targets-inprogress-count" class="badge inprogress pull-right"><?php echo $targets_inprogress ?></span>
                </li>

                <?php if ($targets_inprogress): ?>
                    <?php foreach ($targets as $target): ?>
                        <?php if (in_array("inprogress", $target["progress"]) && !$target["deleted_date"]):
                            $progress_id_str = $target["inprogress_aprogress_id"] ? "&aprogress_id={$target["inprogress_aprogress_id"]}" : "";
                            $url = "$path&atarget_id={$target["atarget_id"]}{$progress_id_str}"; ?>
                            <li class="target-listitem target-listitem-inprogress">
                              <div class="clearfix">
                                  <a class="inprogress pull-left" href="<?php echo $url?>"><span class="target-name"><?php echo html_encode($target["name"]) ?></span></a>
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
                            $progress_id_str = $target["complete_aprogress_id"] ? "&aprogress_id={$target["complete_aprogress_id"]}" : "";
                            $url = "$path&atarget_id={$target["atarget_id"]}{$progress_id_str}"; ?>
                            <li class="target-listitem target-listitem-complete">
                                <div class="clearfix">
                                    <a class="complete pull-left" href="<?php echo $url ?>"><span class="target-name">
                                        <?php echo html_encode($target["name"]) ?></span>
                                        <?php if ($max_attempts > 1):
                                            if ($target["deleted_date"]) {
                                                if ($target["counts"]["complete"] == 1) {
                                                    $complete_progress_tooltip = sprintf($translate->_("There is 1 completed form for this target."));
                                                } else {
                                                    $complete_progress_tooltip = sprintf($translate->_("There are %s completed forms for this target."), $target["counts"]["complete"]);
                                                }
                                                ?>
                                                <span class="badge complete pull-right target-progress-tooltip space-left" data-toggle="tooltip" title="<?php echo $complete_progress_tooltip ?>"><?php echo $target["counts"]["complete"] ?></span>
                                                <?php
                                            } else {
                                                if ($target["counts"]["complete"] == 1) {
                                                    $complete_progress_tooltip = sprintf($translate->_("There is 1 completed form out of %s total possible submissions for this target."), $max_attempts);
                                                } else {
                                                    $complete_progress_tooltip = sprintf($translate->_("There are %s completed forms out of %s total possible submissions for this target."), $target["counts"]["complete"], $max_attempts);
                                                }
                                                ?>
                                                <span class="badge complete pull-right target-progress-tooltip space-left" data-toggle="tooltip" title="<?php echo $complete_progress_tooltip ?>"><?php echo $target["counts"]["complete"] ?>/<?php echo $max_attempts ?></span>
                                                <?php
                                            }
                                        endif; ?>
                                    </a>
                                </div>
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