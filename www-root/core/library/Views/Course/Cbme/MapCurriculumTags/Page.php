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
 * View class for rendering the curriculum mapping page
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_MapCurriculumTags_Page extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        $valid = true;

        if (!isset($options["course_id"])) {
            $valid = false;
        }

        if (!isset($options["entrada_url"])) {
            $valid = false;
        }

        if (!isset($options["module"])) {
            $valid = false;
        }

        if (!isset($options["stages"])) {
            $valid = false;
        }

        if (!isset($options["standard_roles"])) {
            $valid = false;
        }

        if (!isset($options["course_milestones"])) {
            $valid = false;
        }

        if (!isset($options["enabling_competencies"])) {
            $valid = false;
        }

        if (!isset($options["tree_json"])) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * Render the curriculum mapping form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        /**
         * Instantiate an ObjectiveList view for the Stages list
         */
        $stages_view = new Views_Course_Cbme_MapCurriculumTags_ObjectiveList(array("objectives" => $options["stages"]));

        /**
         * Instantiate an ObjectiveList view for the Roles list
         */
        $roles_view = new Views_Course_Cbme_MapCurriculumTags_ObjectiveList(array("objectives" => $options["standard_roles"]));

        /**
         * Instantiate an ObjectiveList view for the EPA list
         */
        $epas_view = new Views_Course_Cbme_MapCurriculumTags_ObjectiveList();

        /**
         * Instantiate an ObjectiveList view for the Milestones list
         */
        $milestones_view = new Views_Course_Cbme_MapCurriculumTags_ObjectiveList();

        /**
         * Instantiate an ObjectiveList view for the Enabling Competencies list
         */
        $ec_view = new Views_Course_Cbme_MapCurriculumTags_ObjectiveList(array("objectives" => $options["enabling_competencies"]));

        /**
         * Instantiate list template views
         */
        $list_item_view = new Views_Course_Cbme_MapCurriculumTags_ListItemTemplate();
        $list_item_header_view = new Views_Course_Cbme_MapCurriculumTags_ListItemHeaderTemplate();

        $this->addHeadScripts($options["entrada_url"], $options["course_id"], $options["module"], $options["tree_json"]);
        ?>
        <h1 class="muted"><?php echo $translate->_("Map Curriculum Tags") ?></h1>
        <?php
        /**
         * Render the Course CBME subnavigation
         */
        $navigation_view = new Views_Course_Cbme_Navigation();
        $navigation_view->render(array(
            "course_id" => $options["course_id"],
            "active_tab" => "map_curriculum_tags"
        ));
        ?>
        <?php if ((!is_null($options["cbme_milestones"]) && $options["course_milestones"]) || (!is_null($options["cbme_milestones"]) && $options["cbme_milestones"] == "0")) : ?>
        <div id="curriculum-tag-container">
            <form action="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=map-curriculumtags&step=2&id=" . html_encode($options["course_id"]) ?>" method="POST" id="manage-objectives-form" class="space-above medium">
                <input type="hidden" id="milestones_codes" name="milestones_codes" value="" />
                <div>
                    <h2><?php echo $translate->_("Curriculum Tag Map") ?></h2>
                    <div id="cbme-curriculum-map">
                        <div class="control-group">
                            <div class="controls">
                                <input id="epa-search" type="text" class="input-block-level search-icon" placeholder="<?php echo $translate->_("Begin typing to search EPA maps...") ?>" />
                            </div>
                         </div>
                        <div id="no-epas" class="hide">
                            <?php echo $translate->_("No EPAs found matching the search criteria.") ?>
                        </div>
                    </div>
                    <div id="show-more-container" class="space-below medium">
                        <a id="show-more-btn" href="#" class="btn btn-default btn-block"><?php echo $translate->_("Show More EPA Maps") ?></a>
                    </div>

                    <h2><?php echo $translate->_("Map Additional Curriculum Tags") ?></h2>
                    <div id="msgs" class="alert alert-danger hide"><?php echo $translate->_("You must <strong>select at least one objective from each objective set</strong> in order to save this branch.") ?></div>
                    <div class="clearfix"></div>
                    <?php
                    /**
                     * Render the stage list
                     */
                    $stages_view->render(array("objective_list_id" => "stages-objective-set", "objective_set" => "stages", "objective_set_search_input_id" => "stages-search", "objective_set_search_input_name" => "stage_search", "objective_set_search_placeholder" => "Search Stages...", "objective_set_list_id" => "stages-list", "multi_select" => "false", "no_objectives_text" => "No stages to display", "objective_class_string" => "stage objective", "load_more_text" => "Load More", "populates" => "epas", "ajax_load" => "false", "active" => true, "load_more" => false, "final_node" => "false"));

                    /**
                     * Render the epa list
                     */
                    $epas_view->render(array("objective_list_id" => "epas-objective-set", "objective_set" => "epas", "objective_set_search_input_id" => "epas-search", "objective_set_search_input_name" => "epa_search", "objective_set_search_placeholder" => "Search EPAs...", "objective_set_list_id" => "epas-list", "multi_select" => "false", "no_objectives_text" => "No epas to display", "objective_class_string" => "epa objective", "load_more_text" => "Load More", "populates" => "roles", "ajax_load" => "true", "active" => false, "load_more" => false, "final_node" => "false"));

                    if ($options["course_milestones"]) {
                        /**
                         * Render the role list
                         */
                        $roles_view->render(array("objective_list_id" => "roles-objective-set", "objective_set" => "roles", "objective_set_search_input_id" => "roles-search", "objective_set_search_input_name" => "role_search", "objective_set_search_placeholder" => "Search Roles...", "objective_set_list_id" => "roles-list", "multi_select" => "true", "no_objectives_text" => "No roles to display", "objective_class_string" => "role objective", "load_more_text" => "Load More", "populates" => "milestones", "ajax_load" => "false", "active" => false, "load_more" => false, "final_node" => "false"));

                        /**
                         * If the course has milestones, then render the milestone list
                         */
                        $milestones_view->render(array("objective_list_id" => "milestones-objective-set", "objective_set" => "milestones", "objective_set_search_input_id" => "milestones-search", "objective_set_search_input_name" => "milestone_search", "objective_set_search_placeholder" => "Search Milestones...", "objective_set_list_id" => "milestones-list", "multi_select" => "true", "no_objectives_text" => "No milestones to display", "objective_class_string" => "milestone objective", "load_more_text" => "Load More", "populates" => "false", "ajax_load" => "true", "active" => false, "load_more" => false, "final_node" => "true"));
                    } else {
                        /**
                         * Render the role list
                         */
                        $roles_view->render(array("objective_list_id" => "roles-objective-set", "objective_set" => "roles", "objective_set_search_input_id" => "roles-search", "objective_set_search_input_name" => "role_search", "objective_set_search_placeholder" => "Search Roles...", "objective_set_list_id" => "roles-list", "multi_select" => "true", "no_objectives_text" => "No roles to display", "objective_class_string" => "role objective", "load_more_text" => "Load More", "populates" => "enabling-competencies", "ajax_load" => "false", "active" => false, "load_more" => false, "final_node" => "false"));

                        /**
                         * If there are no milestones for this course, then render the ec list
                         */
                        $ec_view->render(array("objective_list_id" => "enabling-competencies-objective-set", "objective_set" => "enabling-competencies", "objective_set_search_input_id" => "enabling-competencies-search", "objective_set_search_input_name" => "enabling-competencies_search", "objective_set_search_placeholder" => "Search Enabling Competencies...", "objective_set_list_id" => "enabling-competencies-list", "multi_select" => "true", "no_objectives_text" => "No enabling competencies to display", "objective_class_string" => "enabling-competency objective", "load_more_text" => "Load More", "populates" => "false", "ajax_load" => "true", "active" => false, "load_more" => false, "final_node" => "true"));
                    }
                    ?>
                </div>
                <div class="clearfix"></div>
                <div id="form-controls" class="space-above medium">
                    <input id="save-branch" type="submit" value="<?php echo $translate->_("Save Curriculum Map") ?>" class="btn btn-primary pull-right" />
                </div>
            </form>
        </div>
        <?php else : ?>
        <div class="alert alert-danger space-above medium">
            <?php echo $translate->_("Please finish importing CBME data in order to map curriculum tags.") ?>
        </div>
        <?php endif; ?>
    <?php
        $list_item_view->render();
        $list_item_header_view->render();
    }

    /**
     * @param string $entrada_url
     * @param int $course_id
     * @param string $module
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts ($entrada_url, $course_id, $module, $tree_json) {
        global $translate, $BREADCRUMB, $HEAD;

        $BREADCRUMB[] = array("url" => $entrada_url."/admin/".$module."/cbme?".replace_query(array("section" => "curriculumtags", "id" => $course_id, "step" => false)), "title" => $translate->_("Map Curriculum Tags"));
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $entrada_url . "/css/courses/curriculum-tags.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . $entrada_url . "/javascript/d3.min.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . $entrada_url . "/javascript/courses/curriculumtags/map_curriculumtags.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . $entrada_url . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" >var course_id = '". $course_id ."'</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $entrada_url . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
        $HEAD[] = "<script type=\"text/javascript\">var tree_json = $tree_json; </script>";
    }
}