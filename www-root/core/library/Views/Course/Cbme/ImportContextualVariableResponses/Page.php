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
 * View class for rendering the contextual variable import page
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Course_Cbme_ImportContextualVariableResponses_Page extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["entrada_url"])) {
            return false;
        }

        if (!isset($options["course_id"])) {
            return false;
        }

        if (!isset($options["module"])) {
            return false;
        }

        return true;
    }

    /**
     * Render the curriculum tag import form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->addHeadScripts($options["entrada_url"], $options["course_id"], $options["module"]);
        ?>

        <h1><?php echo $translate->_("Import Contextual Variable Responses") ?></h1>
        <div>
            <?php
            /**
             * Render the Course CBME subnavigation
             */
            $navigation_view = new Views_Course_Cbme_Navigation();
            $navigation_view->render(array(
                "course_id" => $options["course_id"],
                "active_tab" => "contextual_variable_responses"
            ));
            ?>
            <div class="btn-group pull-right">
                <a id="download-csv" href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-contextual-variable-csv" ?>" class="btn btn-default pull-right"><?php echo $translate->_("Download Example CSV Template") ?></a>
            </div>
        </div>
        <form id="curriculum-tag-form" class="form-horizontal space-above medium upload-form" enctype="multipart/form-data" data-method="upload-cv-responses">
            <input id="course-id" name="course_id" type="hidden" value="<?php echo html_encode($options["course_id"]) ?>" />
            <div id="msgs" class="hide"></div>
            <div id="upload-controls" class="control-group">
                <label class="control-label form-required"><?php echo $translate->_("Upload CSV") ?></label>
                <div class="controls well uploader">
                    <div class="form-input">
                        <input class="form-file" type="file" name="files" id="file" />
                        <label id="file-label" for="file">
                            <?php echo $translate->_("<span class=\"input-label\">Choose a file</span><span class=\"form-dragndrop\"> or drag and drop it here</span>.") ?>
                        </label>
                    </div>
                    <div class="form-uploading"><?php echo $translate->_("Uploading&hellip;") ?></div>
                    <div class="form-success"><?php echo $translate->_("Done") ?></div>
                    <div class="form-error"><?php echo $translate->_("Error") ?></div>
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn-primary pull-right"><?php echo $translate->_("Import Contextual Variable Responses") ?></button>
                <a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?id=" . html_encode($options["course_id"]) ?>" class="btn pull-left"><?php echo $translate->_("Cancel") ?></a>
            </div>
        </form>
        <?php
    }

    /**
     * @param string $entrada_url
     * @param int $course_id
     * @param string $module
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts ($entrada_url, $course_id, $module) {
        global $translate, $BREADCRUMB, $HEAD;
        $BREADCRUMB[] = array("url" => $entrada_url."/admin/".$module."/cbme?".replace_query(array("section" => "curriculumtags", "id" => $course_id, "step" => false)), "title" => $translate->_("Import Curriculum Tags"));
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $entrada_url . "/css/courses/curriculum-tags.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . $entrada_url . "/javascript/courses/curriculumtags/curriculumtags.js\"></script>";
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render assessment tool"); ?></strong>
        </div>
        <?php
    }
}