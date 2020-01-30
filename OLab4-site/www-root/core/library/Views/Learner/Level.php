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
 * View class for rendering a learner level.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Learner_Level extends Views_HTML {

    protected $mode = "blank";
    protected $valid_modes = array("readonly", "editor", "editor-readonly", "blank");
    protected $state = null;
    private $visibility_flags = array();

    /**
     * Views_Learner_Level constructor.
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->configureVisibility();
    }

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }

    protected function validateMode() {
        if (in_array($this->mode, $this->valid_modes)) {
            return true;
        } else {
            return false;
        }
    }

    private function configureVisibility() {
        $this->setDefaultVisibility();
        if (!$this->state) {
            $this->setStateByMode();
        }
        $this->setVisibilityByState();
    }

    private function setStateByMode() {
        if ($this->validateMode()) {
            switch ($this->mode) {
                case "readonly":
                    $this->state = "readonly";
                    break;
                case "editor":
                case "editor-readonly":
                    $this->state = "edit-clean";
                    break;
            }
        }
    }

    private function setVisibilityByState() {

        switch ($this->state) {
            case "readonly":
                // Display only data output, no controls.
                $this->visibility_flags["show_header"] = false;
                $this->visibility_flags["show_header_save_button"] = false;
                $this->visibility_flags["show_header_delete_button"] = false;
                break;
            case "edit-clean":
                // Everything is displayed, edibility determined by mode.
                break;
            default:
                break;
        }
    }

    private function setDefaultVisibility() {
        $this->visibility_flags = array(
            // Fields/Data
            "show_level_title" => true,
            "show_stage_title" => true,
            "show_course_title" => true,
            "show_start_date" => true,
            "show_finish_date" => true,
            "show_status_title" => true,
            "show_active" => true,
            // Header
            "show_header" => true,
            "show_header_save_button" => true,
            "show_header_delete_button" => true
        );
    }

    protected function validateOptions($options = array()) {

        $result = $this->validateIsSet(
            $options,
            array(
                "user_learner_level_id",
                "level_title",
                "stage_title",
                "start_date",
                "finish_date",
                "status_title",
                "active"
            )
        );

        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * Generate the html for learner level.
     * @param array $options
     */
    protected function renderView($options = array()) {

        // Ensure /css/views/level/level.css is included.
        global $HEAD, $translate;
        $common_css = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/views/learner/level.css?release=".APPLICATION_VERSION."\" />";

        $included = false;
        foreach ($HEAD as $inclusion) {
            if ($inclusion == $common_css) {
                $included = true;
            }
        }
        if (!$included) {
            $HEAD[] = $common_css;
        }

        $start_dt = DateTime::createFromFormat("U", $options["start_date"]);
        if ($start_dt === false || array_sum($start_dt->getLastErrors())) {
            $start_dt = "";
        } else {
            $start_dt = $start_dt->format("Y-m-d");
        }
        $finish_dt = DateTime::createFromFormat("U", $options["finish_date"]);
        if ($finish_dt === false || array_sum($finish_dt->getLastErrors())) {
            $finish_dt = "";
        } else {
            $finish_dt = $finish_dt->format("Y-m-d");
        }

        // Optional.
        $id = array_key_exists("id", $options) ? $options["id"] : null;
        $class = array_key_exists("class", $options) && $options["class"] ? $options["class"] : "learner-level";
        $course_title = array_key_exists("course_title", $options) ? $options["course_title"] : null;

        $stages_datasource = (isset($options["stages_datasource"]) && is_array($options["stages_datasource"]) ? $options["stages_datasource"] : array());
        $selected_stage_id = array_key_exists("selected_stage_id", $options) && $options["selected_stage_id"] ? $options["selected_stage_id"] : null;

        $advanced_search_selector = "learner-level-stage-search-btn";
        $parent_form_selector = "learner-level-stage-section";
        $selected_target_label = $options["stage_title"];
        $selected_target_type_label = $translate->_("Stage");

        $no_stages_defined = empty($stages_datasource) ? true : false;
        $force_show_clear_stage_button = false;
        $stages_count = count($stages_datasource);
        $readonly = false;
        if ($no_stages_defined) {
            $readonly = true;
        }
        ?>
        <div data-user-learner-level-id="<?php echo html_encode($options["user_learner_level_id"]); ?>"
             class="<?php echo $class; ?><?php echo $options["active"] ? " active" : ""; ?>"<?php echo ($id ? " id=\"{$id}\"" : ""); ?>
        >
            <div class="<?php echo $class; ?>-wrapper<?php echo $options["active"] ? " active" : ""; ?>">
                <?php if ($this->visibility_flags["show_header"]):
                    $this->renderHeader(array("disabled" => false, "user_learner_level_id" => $options["user_learner_level_id"]));
                endif; ?>
                <div class="<?php echo $class; ?>-data-container<?php echo $options["active"] ? " active" : ""; ?>">
                    <?php
                    if ($this->visibility_flags["show_level_title"]):
                        $this->renderLearnerLevel(array("level_title" => $options["level_title"]));
                    endif;

                    if ($this->visibility_flags["show_status_title"]):
                        $this->renderStatus(array("status_title" => $options["status_title"]));
                    endif;

                    if ($this->visibility_flags["show_start_date"]):
                        $this->renderStartDate(array("start_date" => $start_dt));
                    endif;
                    if ($this->state == "readonly") {
                        $this->renderDelimiter($translate->_("to"), array("pad" => true));
                    }
                    if ($this->visibility_flags["show_finish_date"]):
                        $this->renderFinishDate(array("finish_date" => $finish_dt));
                    endif;

                    if ($this->visibility_flags["show_course_title"] && $course_title):
                        $this->renderCourse(array("course_title" => $course_title));
                    endif;

                    if ($this->visibility_flags["show_stage_title"]):
                        if ($this->mode == "editor") :
                            // Render stages advanced search.
                            $this->renderStageAdvancedSearch($stages_datasource, $advanced_search_selector, $parent_form_selector, $advanced_search_selector . "-clear-btn", 300);
                            if ($selected_stage_id) :
                                $this->renderHiddenInput($selected_stage_id, $options["stage_title"], $options["stage_title"]);
                            endif;
                            $this->renderSelectStageButton($advanced_search_selector, $selected_target_type_label, $selected_target_label, $stages_count, $readonly, $no_stages_defined); ?>
                            <button id="<?php echo $advanced_search_selector . "-clear-btn" ?>" class="btn <?php echo $force_show_clear_stage_button  ? "" : "hide" ?>"><?php echo html_encode($translate->_("Clear Stage Selection")); ?></button>

                        <?php else:
                            $this->renderStage(array("stage_title" => $options["stage_title"]));
                        endif;
                    endif;
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the html markup for the header
     * @param $options
     */
    private function renderHeader($options) {
        $disabled = isset($options["disabled"]) ? $options["disabled"] : false;
        ?>
        <div class="learner-level-header">
            <div class="btn-group level-actions pull-right">
                <?php if ($this->visibility_flags["show_header_save_button"]): ?>
                    <a class="btn save-learner-level" href="#"<?php echo $disabled ? "disabled" : ""; ?>>
                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    </a>
                <?php endif;?>
                <?php if ($this->visibility_flags["show_header_delete_button"]): ?>
                    <a class="btn btn-danger delete-learner-level" href="#"
                       data-user-learner-level-id="<?php echo html_encode($options["user_learner_level_id"]); ?>"
                       <?php echo $disabled ? "disabled" : ""; ?>
                    >
                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the html markup for a delimiter
     * @param $string
     * @param $options
     */
    private function renderDelimiter($string, $options = array()) {
        ?>
        <span class="learner-level-delimiter<?php echo isset($options["pad"]) && $options["pad"] ? " padded" : "" ?>">
            <?php echo html_encode($string); ?>
        </span>
        <?
    }

    /**
     * Render the html markup for the level
     * @param $options
     */
    private function renderLearnerLevel($options) {
        ?>
        <span class="learner-level-title"><strong><?php echo html_encode($options["level_title"]); ?></strong></span>
        <?php
    }

    /**
     * Render the html markup for the start date
     * @param $options
     */
    private function renderStartDate($options) {
        ?>
        <span class="learner-level-start-date"><?php echo html_encode($options["start_date"]); ?></span>
        <?php
    }

    /**
     * Render the html markup for the finish date
     * @param $options
     */
    private function renderFinishDate($options) {
        ?>
        <span class="learner-level-start-finish"><?php echo html_encode($options["finish_date"]); ?></span>
        <?php
    }

    /**
     * Render the html markup for the status
     * @param $options
     */
    private function renderCourse($options) {
        ?>
        <span class="learner-level-course"><?php echo html_encode($options["course_title"]); ?></span>
        <?php
    }

    /**
     * Render the html markup for the status
     * @param $options
     */
    private function renderStatus($options) {
        ?>
        <span class="learner-level-status"><?php echo html_encode($options["status_title"]); ?></span>
        <?php
    }

    /**
     * Render the html markup for the stage
     * @param $options
     */
    private function renderStage($options) {
        ?>
        <span class="learner-level-stage"><?php echo html_encode($options["stage_title"]); ?></span>
        <?php
    }

    /**
     * Render the select stage button.
     *
     * @param $advance_search_selector
     * @param $target_type_label
     * @param $target_label
     * @param $stage_count
     * @param bool $disabled
     * @param bool $no_stages_defined
     */
    private function renderSelectStageButton($advance_search_selector, $target_type_label, $target_label, $stage_count, $disabled = false, $no_stages_defined = false) {
        global $translate;
        $disabled_text = $disabled ? "disabled" : "";
        if ($target_label): ?>
            <button id="<?php echo $advance_search_selector ?>" name="rating_scale_id" class="btn btn-search-filter <?php echo $disabled_text?>">
                <?php if ($target_type_label && $stage_count > 1): ?>
                    <span class="selected-filter-label"><?php echo html_encode($target_type_label) ?></span>
                <?php endif; ?>
                <?php echo html_encode($target_label) ?>&nbsp;
                <?php if (!$disabled): ?>
                    <i class="icon-chevron-down pull-right btn-icon"></i>
                <?php endif; ?>
            </button>
        <?php else: ?>
            <?php if ($disabled):?>
                <button id="<?php echo $advance_search_selector ?>" name="learner_level_stage_id" class="btn disabled padding-left padding-right medium"><?php echo html_encode($translate->_("None")); ?></button>
            <?php elseif ($no_stages_defined): ?>
                <button id="<?php echo $advance_search_selector ?>" name="learner_level_stage_id" class="btn disabled padding-left padding-right medium"><?php echo html_encode($translate->_("No Stages Defined")); ?></button>
            <?php else :?>
                <button id="<?php echo $advance_search_selector ?>" name="learner_level_stage_id" class="btn <?php echo $disabled_text?>"><?php echo html_encode($translate->_("Select Stage")); ?></button>
            <?php endif; ?>
        <?php endif;
    }

    /**
     * Render the html markup and js for the stage advanced search
     *
     * @param $stages
     * @param $advanced_search_selector
     * @param $parent_form_selector
     * @param $advance_search_selector_clear_button
     * @param $width
     */
    protected function renderStageAdvancedSearch($stages, $advanced_search_selector, $parent_form_selector, $advance_search_selector_clear_button, $width) {
        global $translate; ?>
        <script type="text/javascript">
            jQuery(function ($) {
                $("#<?php echo $advanced_search_selector ?>").advancedSearch({
                    resource_url: ENTRADA_URL,
                    select_filter_type_label: '<?php echo html_encode($translate->_("Select A Stage")) ?>',
                    filters: {
                        rs_schedule: {
                            label: "<?php echo $translate->_("Stage"); ?>",
                            data_source: <?php echo json_encode($stages); ?>,
                            mode: "radio",
                            selector_control_name: "learner_level_stage_id"
                        }
                    },
                    control_class: "learner_level_stage_control",
                    no_results_text: "<?php echo html_encode($translate->_("No stages found.")); ?>",
                    parent_form: $("#<?php echo $parent_form_selector ?>"),
                    width: <?php echo $width; ?>
                });
                $("#<?php echo $advanced_search_selector ?>").on("change", function(e) {
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
                if ($("#<?php echo $parent_form_selector ?> input[name='"+$("#<?php echo $advanced_search_selector ?>").attr("name")+"']").val()) {
                    $("#<?php echo $advance_search_selector_clear_button ?>").removeClass("hide");
                }
            });
        </script>
        <?php
    }

    /**
     * Render the hidden input that stores the selected rating scale ID.
     * The static parts of this input are defined in the javascript above.
     *
     * @param $target_id
     * @param $target_label
     * @param $target_type_shortname
     */
    private function renderHiddenInput($target_id, $target_label, $target_type_shortname) {
        // This input mirrors what is created by the AdvancedSearch widget.
        ?>
        <input
            name="learner_level_stage_id"
            value="<?php echo $target_id ?>"
            id="<?php echo "{$target_type_shortname}_{$target_id}"; ?>"
            data-label="<?php echo html_encode($target_label) ?>"
            class="search-target-control <?php echo "{$target_type_shortname}_search_target_control" ?> learner_level_stage_control"
            type="hidden"
        />
        <?php
    }

}