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
 * A view for rendering the CBME date filter search bar
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_Date extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("reset_button_text", "apply_button_text", "form_reset_url", "form_action_url"));
    }

    /**
     * Render the view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $proxy_id = Entrada_Utilities::arrayValueOrDefault($options, "proxy_id");
        $course_id = Entrada_Utilities::arrayValueOrDefault($options, "course_id");
        $objective_id = Entrada_Utilities::arrayValueOrDefault($options, "objective_id");
        $date_filters_expanded = Entrada_Utilities::multidimensionalArrayValue($options, "", "preferences", "assessment_filter_view_preference", "date_filters");
        $filter_start_timestamp = Entrada_Utilities::multidimensionalArrayValue($options, null, "filter_start_date");
        $filter_end_timestamp = Entrada_Utilities::multidimensionalArrayValue($options, null, "filter_finish_date");
        ?>
        <form id="cbme-filters" method="GET" class="list-filter" action="<?php echo html_encode($options["form_action_url"]); ?>">
            <div class="list-filter-search">
                <div class="list-filter-cell list-filter-title">
                    <label for="assessment-search"><strong><?php echo $translate->_("Filters"); ?></strong></label>
                </div>
            </div>
            <?php if ($proxy_id) : ?>
                <input type="hidden" name="proxy_id" value="<?php echo $proxy_id; ?>"/>
            <?php endif; ?>
            <?php if ($course_id) : ?>
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>"/>
            <?php endif; ?>
            <?php if ($objective_id) : ?>
                <input type="hidden" name="objective_id" value="<?php echo $objective_id; ?>"/>
            <?php endif; ?>
            <input type="hidden" name="offset" value="0"/>
            <div class="collapsed-filter">
                <a type="button"
                   class="collapsed-filter-toggle <?php echo $date_filters_expanded; ?>"
                   data-filter-type="date_filters">
                    <div class="list-filter-cell list-filter-title full-width">
                        <?php echo $translate->_("Date Filters"); ?>
                    </div>
                    <div class="list-filter-cell list-filter-label">
                        <?php if ($filter_start_timestamp && $filter_end_timestamp && ($filter_end_timestamp > $filter_start_timestamp)): ?>
                            <?php echo sprintf(
                                $translate->_("Filtering history between %s and %s"),
                                date("F j, Y", $filter_start_timestamp),
                                date("F j, Y", $filter_end_timestamp)
                            ); ?>
                        <?php elseif ($filter_start_timestamp && !$filter_end_timestamp): ?>
                            <?php echo sprintf(
                                $translate->_("Filtering history from %s until now"),
                                date("F j, Y", $filter_start_timestamp)
                            ); ?>
                        <?php elseif ($filter_end_timestamp && !$filter_start_timestamp): ?>
                            <?php echo sprintf(
                                $translate->_("Filtering history up until %s"),
                                date("F j, Y", $filter_end_timestamp)
                            ); ?>
                        <?php endif; ?>
                    </div>
                    <div class="list-filter-cell">
                        <span class="list-filter-icon fa fa-angle-down"></span>
                    </div>
                </a>
                <div class="filter-options mini" <?php echo $date_filters_expanded == "collapsed" ? "style=\"display: none;\"" : ""; ?>>
                    <div class="filter-options-body inline-block">
                        <div class="filter-section full-width">
                            <label class="radio">
                                <input type="radio" name="date" value="3" checked/><?php echo $translate->_("Date Range"); ?>
                            </label>
                            <div class="input-append">
                                <input type="text" name="start_date" class="datepicker"
                                       value="<?php echo $filter_start_timestamp ? date("Y-m-d", $filter_start_timestamp) : ""; ?>"/>
                                <span class="add-on pointer">
                                    <i class="icon-calendar"></i>
                                </span>
                            </div>
                            <span><?php echo $translate->_("to"); ?></span>
                            <div class="input-append">
                                <input type="text" name="finish_date" class="datepicker"
                                       value="<?php echo $filter_end_timestamp ? date("Y-m-d", $filter_end_timestamp) : ""; ?>"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-options-footer">
                <div class="list-filter-btn-group">
                    <a href="<?php echo $options["form_reset_url"]; ?>" id="reset-filter-options" class="btn btn-default"><?php echo html_encode($options["reset_button_text"]) ?></a>
                    <input type="submit" class="apply-filter-options btn btn-primary" value="<?php echo html_encode($options["apply_button_text"]) ?>">
                </div>
                <div class="clearfix"></div>
            </div>
        </form>
        <?php
    }
}