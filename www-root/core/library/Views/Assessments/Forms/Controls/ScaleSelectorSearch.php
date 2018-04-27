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
 * View class for rendering advanced search widget for selecting a rating scale.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_ScaleSelectorSearch extends Views_Assessments_Forms_Controls_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("search_selector", "parent_selector", "scale_type_datasource", "submodule"));
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $advance_search_selector        = $options["search_selector"]; // DOM selector
        $parent_form_selector           = $options["parent_selector"]; // DOM selector
        $submodule                      = $options["submodule"]; // The submodule that calls this widget (e.g., "scales")
        $scale_types                    = $options["scale_type_datasource"]; // Advanced search datasource
        $width                          = @$options["width"] ? $options["width"] : 350; // integer value
        $readonly                       = @$options["readonly"] ? $options["readonly"] : false;
        $selected_target_id             = @$options["selected_target_id"];
        $selected_target_label          = @$options["selected_target_label"];
        $selected_target_type_id        = @$options["selected_target_type_id"];
        $selected_target_type_shortname = @$options["selected_target_type_shortname"];
        $selected_target_type_label     = @$options["selected_target_type_label"];
        $selected_target_is_deleted     = @$options["scale_deleted"];

        $no_scales_defined = empty($scale_types) ? true : false;
        $force_show_clear_scale_button = false;
        $scale_type_count = count($scale_types);

        if ($selected_target_is_deleted || $no_scales_defined) {
            $readonly = true;
            //$force_show_clear_scale_button = true;
        }

        // If there's no target type ID set (i.e., there's no type associated with this scale), label it as "default"
        if ($selected_target_label && !$selected_target_type_label) {
            $selected_target_type_label = $translate->_("Default");
        }
        $advance_search_selector_clear_button = "{$advance_search_selector}-clear-btn";

        if (!$readonly && !$no_scales_defined) {
            $this->renderAdvancedSearchScriptTags($scale_types, $submodule, $advance_search_selector, $parent_form_selector, $advance_search_selector_clear_button, $width);
            $this->renderAdvancedSearchClearScriptTag($advance_search_selector, $parent_form_selector, $advance_search_selector_clear_button);
        }
        ?>
        <script>
            jQuery(function ($) {
                $(".scale-info-tooltip").tooltip({placement: "bottom"});
            });
        </script>
        <div class="item-rating-scale-control-group">
            <div class="control-group">
                <label data-toggle="tooltip"
                       class="control-label cursor-help scale-info-tooltip"
                       data-original-title="<?php echo html_encode($translate->_("Selecting a Rating Scale will configure an Item or Grouped Item to use the response categories of the pre-defined Rating Scale. Clearing the scale selection will also clear the item responses and categories.")) ?>">
                    <?php echo html_encode($translate->_("Rating Scale")); ?>
                    <i class="icon-question-sign"></i>
                </label>
                <div class="controls entrada-search-widget">
                    <?php if ($selected_target_id) : ?>
                        <?php $this->renderHiddenInput($selected_target_id, $selected_target_label, $selected_target_type_shortname); ?>
                    <?php endif; ?>
                    <?php $this->renderSelectScalebutton($advance_search_selector, $selected_target_type_label, $selected_target_label, $scale_type_count, $readonly, $no_scales_defined); ?>
                    <button id="<?php echo $advance_search_selector_clear_button ?>" class="btn <?php echo $force_show_clear_scale_button  ? "" : "hide" ?>"><?php echo html_encode($translate->_("Clear Scale Selection")); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the JavaScript block that initializes the button that clears and resets the AdvancedSearch widget.
     *
     * @param $advance_search_selector
     * @param $parent_form_selector
     * @param $advance_search_selector_clear_button
     */
    private function renderAdvancedSearchClearScriptTag($advance_search_selector, $parent_form_selector, $advance_search_selector_clear_button) {
        global $translate; ?>
        <script type="text/javascript">
            jQuery(function ($) {
            $("#<?php echo $advance_search_selector_clear_button ?>").on("click", function(e){
                e.preventDefault();
                $(this).addClass("hide");

                // Clear selected; reset to default and trigger change
                $("#<?php echo $advance_search_selector ?>").html("<?php echo html_encode($translate->_("Select Rating Scale")) ?>"); // reset button
                    $("#<?php echo $parent_form_selector ?> input[name='"+$("#<?php echo $advance_search_selector ?>").attr("name")+"']").remove(); //clear ID
                    $("#<?php echo $advance_search_selector ?>").trigger("change");
                });
            });
        </script>
        <?php
    }

    /**
     * Render the JavaScript block that initialized the AdvancedSearch widget.
     *
     * @param $scale_types
     * @param $submodule
     * @param $advance_search_selector
     * @param $parent_form_selector
     * @param $advance_search_selector_clear_button
     * @param $width
     */
    private function renderAdvancedSearchScriptTags($scale_types, $submodule, $advance_search_selector, $parent_form_selector, $advance_search_selector_clear_button, $width) {
        global $translate; ?>
        <script type="text/javascript">
            jQuery(function ($) {
                $("#<?php echo $advance_search_selector ?>").advancedSearch({
                    api_url: "<?php echo ENTRADA_URL . "/admin/assessments/scales?section=api-scales&submodule=$submodule"; ?>",
                    resource_url: ENTRADA_URL,
                    select_filter_type_label: '<?php echo html_encode($translate->_("Select A Rating Scale Type")) ?>',
                    filters: {
                        <?php
                        $js = array();
                        // Build a filter for each scale type
                        foreach ($scale_types as $scale_type) {
                            $replaced = $this->buildFilterScriptTemplate();
                            $replaced = str_replace("%%filter_name%%", $scale_type["shortname"], $replaced);
                            $replaced = str_replace("%%target_id%%", $scale_type["target_id"], $replaced);
                            $replaced = str_replace("%%target_label%%", $scale_type["target_label"], $replaced);
                            $js[] = $replaced;
                        }
                        echo implode(",\n", $js);
                        ?>
                    },
                    control_class: "rating_scale_control",
                    no_results_text: "<?php echo html_encode($translate->_("No rating scales found.")); ?>",
                    parent_form: $("#<?php echo $parent_form_selector ?>"),
                    width: <?php echo $width; ?>
                });
                $("#<?php echo $advance_search_selector ?>").on("change", function(e) {
                    var attr_name = $(this).attr("name");
                    var selected_scale_id = null;
                    if (attr_name) {
                        selected_scale_id = $("#<?php echo $parent_form_selector?> input[name='"+attr_name+"']").val();
                    }
                    if (selected_scale_id) {
                        $("#<?php echo $advance_search_selector_clear_button ?>").removeClass("hide");
                    } else {
                        $("#<?php echo $advance_search_selector_clear_button ?>").addClass("hide");
                    }
                });

                // Set initial state based on previously selected item
                if ($("#<?php echo $parent_form_selector ?> input[name='"+$("#<?php echo $advance_search_selector ?>").attr("name")+"']").val()) {
                    $("#<?php echo $advance_search_selector_clear_button ?>").removeClass("hide");
                }
            });
        </script>
        <?php
    }

    /**
     * Rebder the select scale button.
     *
     * @param $advance_search_selector
     * @param $target_type_label
     * @param $target_label
     * @param bool $disabled
     * @param bool $no_scales_defined
     */
    private function renderSelectScalebutton($advance_search_selector, $target_type_label, $target_label, $scale_type_count, $disabled = false, $no_scales_defined = false) {
        global $translate;
        $disabled_text = $disabled ? "disabled" : "";
        if ($target_label): ?>
            <button id="<?php echo $advance_search_selector ?>" name="rating_scale_id" class="btn btn-search-filter <?php echo $disabled_text?>">
                <?php if ($target_type_label && $scale_type_count > 1): ?>
                    <span class="selected-filter-label"><?php echo html_encode($target_type_label) ?></span>
                <?php endif; ?>
                <?php echo html_encode($target_label) ?>&nbsp;
                <?php if (!$disabled): ?>
                    <i class="icon-chevron-down pull-right btn-icon"></i>
                <?php endif; ?>
            </button>
        <?php else: ?>
            <?php if ($disabled):?>
                <button id="<?php echo $advance_search_selector ?>" name="rating_scale_id" class="btn disabled padding-left padding-right medium"><?php echo html_encode($translate->_("None")); ?></button>
            <?php elseif ($no_scales_defined): ?>
                <button id="<?php echo $advance_search_selector ?>" name="rating_scale_id" class="btn disabled padding-left padding-right medium"><?php echo html_encode($translate->_("No Scales Defined")); ?></button>
            <?php else :?>
                <button id="<?php echo $advance_search_selector ?>" name="rating_scale_id" class="btn <?php echo $disabled_text?>"><?php echo html_encode($translate->_("Select Rating Scale")); ?></button>
            <?php endif; ?>
        <?php endif;
    }

    /**
     * Render the hidden input that stores the selected rating scale ID.
     * The static parts of this input, such as "rating_scale_control" and "rating_scale_id" are defined in the javascript above.
     *
     * @param $target_id
     * @param $target_label
     * @param $target_type_shortname
     */
    private function renderHiddenInput($target_id, $target_label, $target_type_shortname) {
        // This input mirrors what is created by the AdvancedSearch widget.
        ?>
        <input
            name="rating_scale_id"
            value="<?php echo $target_id ?>"
            id="<?php echo "{$target_type_shortname}_{$target_id}"; ?>"
            data-label="<?php echo html_encode($target_label) ?>"
            class="search-target-control <?php echo "{$target_type_shortname}_search_target_control" ?> rating_scale_control"
            type="hidden"
        />
        <?php
    }

    /**
     * An in-line template for adding a filter for a rating scale to the advanced search widget.
     *
     * @return string
     */
    private function buildFilterScriptTemplate() {
        ob_start();
        ?>
        %%filter_name%% : {
            label: '%%target_label%%',
            api_params: {
                rating_scale_type: '%%target_id%%',
            },
            data_source: 'get-scales',
            mode: 'radio',
            selector_control_name: 'rating_scale_id'
        }
        <?php
        $filter_template = ob_get_contents();
        ob_end_clean();
        return $filter_template;
    }
}

