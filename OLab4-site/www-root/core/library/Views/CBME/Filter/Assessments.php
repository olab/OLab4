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
 * A view for rendering the CBME filter search bar
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_Assessments extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("filters", "filter_list_data", "course_assessment_tools", "rating_scales", "preferences", "course_id", "total_count", "filtered_count", "query_limit", "section", "form_action_url", "form_reset_url"));
    }

    /**
     * Render the view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $filter_header_view = new Views_CBME_Filter_Header();
        $filter_footer_view = new Views_CBME_Filter_Footer();
        ?>
        <form id="cbme-filters" method="GET" class="list-filter" action="<?php echo html_encode($options["form_action_url"]); ?>">
            <input type="hidden" name="course_id" value="<?php echo html_encode($options["course_id"]) ?>" />
            <input type="hidden" name="limit" value="<?php echo html_encode($options["query_limit"]) ?>" />
            <?php if (isset($options["proxy_id"])) : ?>
            <input type="hidden" name="proxy_id" value="<?php echo html_encode($options["proxy_id"]) ?>" />
            <?php endif; ?>
            <?php if (isset($options["secondary_proxy_id"])) : ?>
                <input type="hidden" name="secondary_proxy_id" value="<?php echo html_encode($options["secondary_proxy_id"]) ?>" />
            <?php endif; ?>
            <input type="hidden" name="offset" value="0" />
            <?php $filter_header_view->render(array("filter_label" => $translate->_("Filter Options"), "filters" => $options["filters"], "apply_button_text" => $translate->_("Apply Filters"), "all_filters_preference" => (isset($options["preferences"]["assessment_filter_view_preference"]["all_filters"]) ? $options["preferences"]["assessment_filter_view_preference"]["all_filters"] : "expanded"))); ?>
            <div id="filter-wrapper" <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["all_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["all_filters"] == "collapsed" ? "style=\"display: none;\"" : "") ?>>
                <div class="collapsed-filter">
                    <a class="collapsed-filter-toggle <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["date_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["date_filters"] == "expanded" ? "open" : "") ?>" data-filter-type="date_filters">
                        <div class="list-filter-cell list-filter-title full-width">
                            <?php echo $translate->_("Date Filters"); ?>
                        </div>
                        <div class="list-filter-cell list-filter-label">
                            <?php if ((isset($options["filters"]["start_date"]) && isset($options["filters"]["finish_date"]))  && ($options["filters"]["finish_date"] > $options["filters"]["start_date"])) : ?>
                                <?php echo sprintf($translate->_("Filtering assessments between %s and %s"), date("F j, Y", $options["filters"]["start_date"]), date("F j, Y", $options["filters"]["finish_date"])) ?>
                            <?php elseif (isset($options["filters"]["start_date"]) && !isset($options["filters"]["finish_date"])) : ?>
                                <?php echo sprintf($translate->_("Filtering assessments from %s until now"), date("F j, Y", html_encode($options["filters"]["start_date"]))) ?>
                            <?php elseif (isset($options["filters"]["finish_date"]) && !isset($options["filters"]["start_date"])) : ?>
                                <?php echo sprintf($translate->_("Filtering assessments up until %s"), date("F j, Y", html_encode($options["filters"]["finish_date"]))) ?>
                            <?php endif; ?>
                        </div>
                        <div class="list-filter-cell">
                            <span class="list-filter-icon fa fa-angle-down"></span>
                        </div>
                    </a>
                    <div class="filter-options" <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["date_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["date_filters"] == "collapsed" ? "style=\"display: none;\"" : "") ?>>
                        <div class="filter-options-body">
                            <div class="filter-section full-width">
                                <!--
                                <label class="radio">
                                    <input type="radio" name="date" value="1" /> From program start
                                </label>
                                <label class="radio">
                                    <input type="radio" name="date" value="2" /> Post Graduate Year
                                </label>
                                <select disabled>
                                    <option>PGY 1</option>
                                    <option>PGY 2</option>
                                    <option>PGY 3</option>
                                </select>
                                -->
                                <label class="radio">
                                    <input type="radio" name="date" class="date-filter" value="3" <?php echo isset($options["filters"]["experience"]) ? '' : 'checked'?>/><?php echo $translate->_("Date Range"); ?>
                                </label>
                                <div class="input-append">
                                    <input type="text" name="start_date" class="datepicker" value="<?php echo (isset($options["filters"]["start_date"])  && !isset($options["filters"]["experience"]) ? date("Y-m-d", $options["filters"]["start_date"]) : "") ?>" <?php echo isset($options["filters"]["experience"]) ? 'disabled' : ''?>/>
                                    <span class="add-on pointer">
                                        <i class="icon-calendar"></i>
                                    </span>
                                </div>
                                <span>to</span>
                                <div class="input-append">
                                    <input type="text" name="finish_date" class="datepicker" value="<?php echo (isset($options["filters"]["finish_date"])  && !isset($options["filters"]["experience"]) ? date("Y-m-d", $options["filters"]["finish_date"]) : "") ?>"  <?php echo isset($options["filters"]["experience"]) ? 'disabled' : ''?>/>
                                    <span class="add-on pointer">
                                        <i class="icon-calendar"></i>
                                    </span>
                                </div>
                                <label class="radio">
                                    <input type="radio" class="date-filter" name="date" value="4" <?php echo isset($options["filters"]["experience"]) ? 'checked' : ''?>/><?php echo $translate->_("Rotation"); ?>
                                </label>
                                <div class="input-append quarter-width">
                                    <button class="btn btn-default btn-search-filter experience_select full-width"  <?php echo isset($options["filters"]["experience"]) ? '' : 'disabled'?>><?php echo $translate->_("Select Rotation"); ?><i class="fa fa-angle-down space-left"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden-date-inputs">
                    <?php if (isset($options["filters"]["experience"])) : ?>
                        <input type="hidden" name="start_date" value="<?php echo isset($options["filters"]["finish_date"]) ? date("Y-m-d", $options["filters"]["start_date"]) : ''; ?>"/>
                        <input type="hidden" name="finish_date" value="<?php echo isset($options["filters"]["finish_date"]) ? date("Y-m-d", $options["filters"]["finish_date"]) : ''; ?>"/>
                    <?php endif; ?>
                </div>
                <div class="collapsed-filter">
                    <a class="collapsed-filter-toggle <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["curriculum_tag_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["curriculum_tag_filters"] == "expanded" ? "open" : "") ?>" data-filter-type="curriculum_tag_filters">
                        <div class="list-filter-cell list-filter-title full-width">
                            <?php echo $translate->_("Curriculum Tag Filters") ?>
                        </div>
                        <div class="list-filter-cell">
                            <span class="list-filter-icon fa fa-angle-down"></span>
                        </div>
                    </a>
                    <div class="filter-options" <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["curriculum_tag_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["curriculum_tag_filters"] == "collapsed" ? "style=\"display: none;\"" : "") ?>>
                        <div class="filter-options-body">
                            <div class="filter-section">
                                <label for="select-epa-btn" class="filter-section-title"><?php echo $translate->_("EPAs") ?></label>
                                <button id="select-epa-btn" class="btn btn-default"><?php echo $translate->_("Select EPAs") ?> <i class="fa fa-angle-down"></i></button>
                            </div>
                            <div class="filter-section">
                                <label for="select-role-btn" class="filter-section-title"><?php echo $translate->_("CanMEDs Roles") ?></label>
                                <button id="select-role-btn" class="btn btn-default"><?php echo $translate->_("Select CanMEDs Roles") ?> <i class="fa fa-angle-down"></i></button>
                            </div>
                            <div class="filter-section">
                                <label for="select-milestone-btn" class="filter-section-title"><?php echo $translate->_("Milestones") ?></label>
                                <button id="select-milestone-btn" class="btn btn-default"><?php echo $translate->_("Select Milestones") ?> <i class="fa fa-angle-down"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="collapsed-filter">
                    <a class="collapsed-filter-toggle <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["contextual_variable_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["contextual_variable_filters"] == "expanded" ? "open" : "") ?>" data-filter-type="contextual_variable_filters">
                        <div class="list-filter-cell list-filter-title full-width">
                            <?php echo $translate->_("Contextual Variable Filters") ?>
                        </div>
                        <div class="list-filter-cell">
                            <span class="list-filter-icon fa fa-angle-down"></span>
                        </div>
                    </a>
                    <div class="filter-options" <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["contextual_variable_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["contextual_variable_filters"] == "collapsed" ? "style=\"display: none;\"" : "") ?>>
                        <div class="filter-options-body">
                            <div class="filter-section">
                                <label for="select-cv-btn" class="filter-section-title"><?php echo $translate->_("Contextual Variables") ?></label>
                                <button id="select-cv-btn" class="btn btn-default"><?php echo $translate->_("Select Contextual Variables") ?> <i class="fa fa-angle-down"></i></button>
                            </div>
                            <div class="filter-section">
                                <label for="select-cv-responses-btn" class="filter-section-title"><?php echo $translate->_("Contextual Variable Responses") ?></label>
                                <button id="select-cv-responses-btn" class="btn btn-default" disabled><?php echo $translate->_("Select Responses") ?> <i class="fa fa-angle-down"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="collapsed-filter">
                    <a class="collapsed-filter-toggle <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["assessment_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["assessment_filters"] == "expanded" ? "open" : "") ?>" data-filter-type="assessment_filters">
                        <div class="list-filter-cell list-filter-title full-width">
                            <?php echo $translate->_("Assessment Filters") ?>
                        </div>
                        <div class="list-filter-cell list-filter-label">
                            <?php echo sprintf($translate->_("Filtered %s of %s total assessments"), "<span id=\"assessment-display-count\">". html_encode($options["filtered_count"]) ."</span>", "<span id=\"assessment-count\">" . html_encode($options["total_count"]) . "</span>") ?>
                        </div>
                        <div class="list-filter-cell">
                            <span class="list-filter-icon fa fa-angle-down"></span>
                        </div>
                    </a>
                    <div class="filter-options" <?php echo (isset($options["preferences"]["assessment_filter_view_preference"]["assessment_filters"]) && $options["preferences"]["assessment_filter_view_preference"]["assessment_filters"] == "collapsed" ? "style=\"display: none;\"" : "") ?>>
                        <div class="filter-options-body">
                            <?php if ($options["course_assessment_tools"]) : ?>
                                <div class="filter-section filter-scroll match-height">
                                    <span class="filter-section-title"><?php echo $translate->_("Assessment Tools") ?></span>
                                    <?php foreach ($options["course_assessment_tools"] as $assessment_tool) : ?>
                                        <label class="checkbox">
                                            <input type="checkbox" name="form_types[]" id="form_types_<?php echo html_encode($assessment_tool["form_type_id"]) ?>_checkbox" value="<?php echo html_encode($assessment_tool["form_type_id"]) ?>" <?php echo (isset($options["filters"]["form_types"]) && is_array($options["filters"]["form_types"]) && in_array($assessment_tool["form_type_id"], $options["filters"]["form_types"]) ? "checked=\"checked\"" : "") ?> /> <?php echo html_encode($assessment_tool["title"]) ?>
                                        </label>
                                    <?php endforeach ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($options["rating_scales"]) :
                                $current_type = ""; ?>
                                <div class="filter-section filter-scroll match-height">
                                    <label for="rating-scales" class="filter-section-title"><?php echo $translate->_("Rating Scales") ?></label>
                                    <select id="rating-scales" name="rating_scale_id">
                                        <option value="0"><?php echo $translate->_("-- Select a Rating Scale --") ?></option>
                                        <?php foreach ($options["rating_scales"] as $rating_scale) :
                                            if ($current_type != $rating_scale["rating_scale_type_name"]):

                                                if ($current_type != $rating_scale["rating_scale_type_name"]):
                                                    if ($current_type != "" ) {
                                                        echo "</optgroup>";
                                                    } ?>
                                                    <optgroup label="<?php echo ($rating_scale["rating_scale_type_description"]) ? $rating_scale["rating_scale_type_description"] : $rating_scale["rating_scale_type_name"]; ?>">
                                                    <?php
                                                    $current_type = $rating_scale["rating_scale_type_name"];
                                                endif;
                                            endif; ?>
                                            <option value="<?php echo html_encode($rating_scale["rating_scale_id"]) ?>" <?php echo (isset($options["filters"]["rating_scale_id"][0]) && $options["filters"]["rating_scale_id"][0] == $rating_scale["rating_scale_id"] ? "selected=\"selected\"" : "") ?>><?php echo html_encode($rating_scale["rating_scale_title"]) ?></option>
                                        <?php endforeach;
                                        if ($current_type != "" ) {
                                            echo "</optgroup>";
                                        }
                                        ?>
                                    </select>
                                    <?php foreach ($options["rating_scales"] as $rating_scale) : ?>
                                        <div id="rating-scale-<?php echo html_encode($rating_scale["rating_scale_id"]) ?>-responses" class="hide scale-response-container">
                                        <?php if ($rating_scale["responses"]) : ?>
                                            <?php foreach ($rating_scale["responses"] as $rating_scale_response) : ?>
                                                <label class="checkbox">
                                                    <input type="checkbox" name="descriptors[]" value="<?php echo html_encode($rating_scale_response["ardescriptor_id"]) ?>" <?php echo (isset($options["filters"]["descriptors"]) && is_array($options["filters"]["descriptors"]) && in_array($rating_scale_response["ardescriptor_id"], $options["filters"]["descriptors"]) ? "checked=\"checked\"" : "") ?> /> <?php echo html_encode($rating_scale_response["descriptor"]) ?>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <label for="triggered-by" class="filter-section-title"><?php echo $translate->_("Triggered By") ?></label>
                                    <select id="triggered-by" name="triggered_by">
                                        <option <?php echo isset($options["filters"]["triggered_by"]) ? ($options["filters"]["triggered_by"] == "all" ? "selected" : "") : "" ?> value="<?php echo html_encode("all"); ?>"><?php echo $translate->_("All"); ?></option>
                                        <?php if ($options["triggered_by"]) : ?>
                                            <?php foreach ($options["triggered_by"] as $triggered_by) : ?>
                                                <option <?php echo isset($options["filters"]["triggered_by"]) ? ($options["filters"]["triggered_by"] == $triggered_by ? "selected" : "") : ""; ?> value="<?php echo html_encode($triggered_by); ?>"><?php $triggered_by_string = $triggered_by == "student" ? "Resident" : $triggered_by; echo html_encode(ucfirst($triggered_by_string)); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="filter-section filter-scroll match-height">
                                <span class="filter-section-title"><?php echo $translate->_("Sort Options") ?></span>
                                <label class="radio">
                                    <input type="radio" name="sort" value="DESC" <?php echo ((!isset($options["filters"]["sort"]) || isset($options["filters"]["sort"]) && $options["filters"]["sort"] == "DESC") ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Newest to Oldest") ?>
                                </label>
                                <label class="radio">
                                    <input type="radio" name="sort" value="ASC" <?php echo (isset($options["filters"]["sort"]) && $options["filters"]["sort"] == "ASC" ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Oldest to Newest") ?>
                                </label>
                                <span class="filter-section-title"><?php echo $translate->_("Other Options") ?></span>
                                <label class="radio">
                                    <input type="radio" name="other" value="all" <?php echo ((!isset($options["filters"]["other"]) || isset($options["filters"]["other"]) && $options["filters"]["other"] == "all") ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Show All") ?>
                                </label>
                                <label class="radio">
                                    <input type="radio" name="other" value="read" <?php echo ((isset($options["filters"]["other"]) && $options["filters"]["other"] == "read") ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Read") ?>
                                </label>
                                <label class="radio">
                                    <input type="radio" name="other" value="unread" <?php echo ((isset($options["filters"]["other"]) && $options["filters"]["other"] == "unread") ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Unread") ?>
                                </label>
                                <!--
                                <label class="radio">
                                    <input type="radio" name="other" value="flagged" <?php echo (isset($options["filters"]["other"]) && $options["filters"]["other"] == "flagged" ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Show Only Flagged") ?>
                                </label>
                                <label class="radio">
                                    <input type="radio" name="other" value="unread" <?php echo (isset($options["filters"]["other"]) && $options["filters"]["other"] == "unread" ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Show Only Unread") ?>
                                </label>
                                <label class="radio">
                                    <input type="radio" name="other" value="pinned" <?php echo (isset($options["filters"]["other"]) && $options["filters"]["other"] == "pinned" ? "checked=\"checked\"" : "") ?> /> <?php echo $translate->_("Show Only Pinned") ?>
                                </label>
                                -->
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php $filter_footer_view->render(array(
                    "reset_button_text" => $translate->_("Reset"),
                    "apply_button_text" => $translate->_("Apply Filters"),
                    "form_reset_url" => $options["form_reset_url"]
            ));
            ?>
            <?php if ($options["filter_list_data"]) : ?>
                <?php foreach ($options["filter_list_data"] as $filter_type => $filter_values) : ?>
                    <?php foreach ($filter_values as $key => $filter_value) : ?>
                        <?php if ($filter_type !== "form_types") : ?>
                            <?php if ($filter_type == "milestones") : ?>
                                <?php foreach ($filter_value as $filter) : ?>
                                    <input type="hidden" value="<?php echo html_encode($filter["value"]) ?>" id="<?php echo html_encode($filter["data_filter_control"]) ?>" data-label="<?php echo html_encode($filter["label"]) ?>" class="search-target-control <?php echo html_encode($filter["style_class"]) ?>" name="<?php echo html_encode($key) ?>[]">
                                <?php endforeach;  ?>
                            <?php else : ?>
                                <input type="hidden" value="<?php echo html_encode($filter_value["value"]) ?>" id="<?php echo html_encode($filter_value["data_filter_control"]) ?>" data-label="<?php echo html_encode($filter_value["label"]) ?>" class="search-target-control <?php echo html_encode($filter_value["style_class"]) ?>" name="<?php echo html_encode($filter_type) ?>[]">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </form>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render filters."); ?></strong>
        </div>
        <?php
    }
}
