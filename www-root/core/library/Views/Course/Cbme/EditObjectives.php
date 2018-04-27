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
 * View class for rendering the edit EPAs interface
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_EditObjectives extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id", "show_secondary_objective", "objective_set_name"))) {
            return false;
        }
        if (!$this->validateArrayNotEmpty($options, array("objectives"))) {
            return false;
        }
        return true;
    }

    /**
     * Render the EPA editor
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->renderHead();
        $objective_ids = array();
        ?>
        <h1 class="muted"><?php echo sprintf($translate->_("Edit %s"), $options["objective_set_name"]) ?></h1>
        <?php
        /**
         * Render the Course CBME subnavigation
         */
        $navigation_view = new Views_Course_Cbme_Navigation();
        $navigation_view->render(array(
            "course_id" => $options["course_id"],
            "active_tab" => "import_cbme_data"
        ));
        ?>
        <div class="alert alert-warning">
            <?php echo sprintf($translate->_("<strong>Please Note:</strong> this editor is intended to facilitate minor wording changes or corrections to %s and <strong>should not</strong> alter meaning or context."), $options["objective_set_name"]); ?>
        </div>
        <div class="control-group">
            <label for="objective-search-control"><?php echo sprintf($translate->_("Search %s"), $options["objective_set_name"])?></label>
            <div class="controls">
                <input id="objective-search-control" placeholder="<?php echo sprintf($translate->_("Begin typing to search %s"), $options["objective_set_name"]) ?>" type="text" class="search-icon input-block-level">
            </div>
        </div>

        <div id="no-search-results" class="hide">
            <?php echo $translate->_("None of your EPAs match the search criteria.") ?>
        </div>

        <div class="objectives-container">
            <?php foreach ($options["objectives"] as $objective) : ?>
                <?php if (!in_array($objective["objective_id"], $objective_ids)) : ?>
                    <form id="objective-form-<?php echo $objective["objective_id"]; ?>" class="objective-form">
                        <input type="hidden" name="objective_id" value="<?php echo $objective["objective_id"]; ?>" />
                        <input type="hidden" name="objective_set_shortname" value="epa" />
                        <div class="list-set space-below medium">
                            <div>
                                <div class="list-set-item-cell">
                                    <div class="list-set-item-title objective-code">
                                        <?php echo html_encode($objective["objective_code"]) ?>
                                    </div>
                                </div>

                                <div class="edit-objective-container">
                                    <div id="objective-form-<?php echo $objective["objective_id"]; ?>-msgs" class="objective-msgs"></div>

                                    <div class="control-group">
                                        <label for="objective-title-<?php echo $objective["objective_id"]; ?>"><?php echo $translate->_("Title") ?></label>
                                        <div class="controls">
                                            <textarea name="objective_name" id="objective-title-<?php echo $objective["objective_id"]; ?>" rows="4"><?php echo html_encode($objective["objective_name"]) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <label for="objective-description-<?php echo $objective["objective_id"]; ?>"><?php echo $translate->_("Detailed Description") ?></label>
                                        <div class="controls">
                                            <textarea name="objective_description" id="objective-description-<?php echo $objective["objective_id"]; ?>" rows="4"><?php echo html_encode(str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $objective["objective_description"])) ?></textarea>
                                        </div>
                                    </div>

                                    <?php if ($options["show_secondary_objective"]) : ?>
                                    <div class="control-group">
                                        <label for="objective-secondary-description-<?php echo $objective["objective_id"]; ?>"><?php echo $translate->_("Entrustment") ?></label>
                                        <div class="controls">
                                            <textarea name="objective_secondary_description" id="objective-secondary-description-<?php echo $objective["objective_id"]; ?>" rows="4"><?php echo html_encode($objective["objective_secondary_description"]) ?></textarea>
                                        </div>
                                    </div>
                                    <?php endif ?>

                                    <input id="objective-form-<?php echo $objective["objective_id"]; ?>-submit" type="submit" value="<?php echo $translate->_("Save Changes") ?>" data-objective-id="<?php echo $objective["objective_id"]; ?>" class="btn btn-success save-objective-btn" />
                                    <span id="objective-form-<?php echo $objective["objective_id"]; ?>-loading" class="hide">
                                        <img src="<?php echo ENTRADA_URL ?>/images/indicator.gif">
                                        <?php echo $translate->_("Saving EPA...") ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php $objective_ids[] = $objective["objective_id"]; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php
    }

    /**
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     * @param int $course_id
     */
    protected function renderHead($course_id = 0) {
        global $translate, $BREADCRUMB, $HEAD;
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/courses/cbme?".replace_query(array("section" => "edit-epas", "id" => $course_id, "step" => false)), "title" => $translate->_("Edit EPAs"));
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/curriculum-tags.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/cbme/edit-objectives.js\"></script>";
        Entrada_Utilities::addJavascriptTranslation("An error occurred while attempting to update this objective. Please try again at a later time.", "objective_update_error", "javascript_translations");
    }

}