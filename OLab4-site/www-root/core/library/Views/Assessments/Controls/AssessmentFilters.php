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
 * View class for rendering assessment filtering option controls.
 * Renders the controls and the selected options.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Controls_AssessmentFilters extends Views_HTML {

    /**
     * Validate. The only required option is filter mode.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("filter_mode"));
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $filter_mode            = $options["filter_mode"];
        $search_term            = array_key_exists("search_term", $options) ? $options["search_term"] : "";
        $start_date             = array_key_exists("start_date", $options) ? $options["start_date"] ? date("Y-m-d", $options["start_date"]) : "" : "";
        $end_date               = array_key_exists("end_date", $options) ? $options["end_date"] ? date("Y-m-d", $options["end_date"]) : "" : "";
        $selected_filters       = array_key_exists("selected_filters", $options) ? is_array($options["selected_filters"]) ? $options["selected_filters"] : array() : array();
        $filter_labels          = array_key_exists("filter_labels", $options) ? is_array($options["filter_labels"]) ? $options["filter_labels"] : array() : array();
        ?>
        <div <?php echo $this->getIDString() ?> <?php echo $this->getClassString() ?>>
            <div class="row-fluid space-below">
                <div class="input-append">
                    <input type="text" id="task-search" placeholder="<?php echo $translate->_("Search Tasks..."); ?>" value="<?php echo $search_term ?>" class="input-large search-icon" data-append="false"/>
                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                </div>
                <div class="input-append space-left">
                    <input id="task_start_date" placeholder="<?php echo $translate->_("Delivery Start"); ?>" type="text" class="input-small datepicker" value="<?php echo $start_date; ?>" name="task_start_date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
                <div class="input-append space-left">
                    <input id="task_end_date" placeholder="<?php echo $translate->_("Delivery End"); ?>" type="text" class="input-small datepicker" value="<?php echo $end_date; ?>" name="task_end_date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
                <input type="button" class="btn btn-success space-left" id="apply_filters" value="<?php echo $translate->_("Apply Filters"); ?>"/>
                <input type="button" class="btn btn-default space-left" id="remove_filters" value="<?php echo $translate->_("Remove Filters"); ?>"/>
            </div>
            <input type="hidden" name="filter_mode" id="filter-mode" value="<?php echo $filter_mode?>"/>

            <div id="active-filters">
                <?php if ($this->hasFilters($selected_filters)): ?>
                    <div class="well well-small filter-well">
                        <div class="title"><?php echo $translate->_("Active Filters") ?></div>
                        <?php foreach ($selected_filters as $filter_type => $filter_values): ?>
                            <?php $this->renderFilterDescription($filter_type, $filter_values, $filter_labels); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Check if any of the filters are not empty.
     * The active filters section, which this method pertains to, only cares about
     * four filter types: distribution method, task status, course, and cperiod.
     *
     * @param $selected_filters
     * @return bool
     */
    private function hasFilters($selected_filters) {
        if (!empty($selected_filters["distribution_method"])
            || !empty($selected_filters["dassessment_id"])
            || !empty($selected_filters["task_status"])
            || !empty($selected_filters["course"])
            || !empty($selected_filters["cperiod"])
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Render a known filter's values, using supplied labels if possible.
     *
     * @param string $type
     * @param array $filters
     * @param array $labels
     */
    private function renderFilterDescription($type, $filters, $labels) {
        global $translate;
        switch ($type) {
            case "distribution_method":
                $filter_name = $translate->_("Distribution Method");
                break;
            case "course":
                $filter_name = $translate->_("Course");
                break;
            case "cperiod":
                $filter_name = $translate->_("Curriculum Period");
                break;
            case "task_status":
                $filter_name = $translate->_("Task Status");
                break;
            case "dassessment_id":
                $filter_name = $translate->_("Task ID");
                break;
            default:
                $filter_name = $translate->_("Unknown filter type");
                // Do not render unknown filters.
                return;
        }
        if (!empty($filters)): ?>
            <div>
                <span class="subtitle"><?php echo $filter_name ?></span>
                <?php if (is_array($filters)): ?>
                    <?php foreach ($filters as $filter_value): ?>
                        <span class="label label-info filter-label"><?php
                            echo isset($labels[$type][$filter_value])
                                ? $labels[$type][$filter_value] // The label version
                                : $filter_value; // Or the raw value
                        ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="label label-info filter-label"><?php
                        echo isset($labels[$type]) && !is_array($labels[$type])
                            ? $labels[$type] // The label version
                            : $filters; // Or the raw value
                        ?></span>
                <?php endif; ?>
            </div>
        <?php endif;
    }
}