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
 * View class for rendering the file uploader.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Course_Cbme_ImportData_Uploader extends Views_HTML {

    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet(
            $options,
            array(
                "course_id",
                "data_method",
                "type",
                "type_plural",
                "form_id"
            )
        );
    }

    /**
     * Render the file uploader for CBME milestones and EPAs.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $include_file_title = array_key_exists("include_file_title", $options) ? $options["include_file_title"] : false;
        $allow_multiple = array_key_exists("allow_multiple", $options) ? $options["allow_multiple"] : false;
        $curriculum_tag_shortname = array_key_exists("curriculum_tag_shortname", $options) ? $options["curriculum_tag_shortname"] : null;
        $hide_submit_button = array_key_exists("hide_submit_button", $options) ? $options["hide_submit_button"] : false;
        ?>
        <form id="<?php echo $options["form_id"]; ?>" class="form-horizontal space-above medium upload-form" enctype="multipart/form-data" data-method="<?php echo html_encode($options["data_method"]); ?>">
            <?php if ($include_file_title): ?>
                <div id="upload-controls" class="control-group">
                    <label class="control-label file-title form-required" for="file-name"><?php echo $translate->_("File Title"); ?></label>
                    <div class="controls">
                        <input type="text" name="file-name" id="file-name">
                    </div>
                </div>
            <?php endif; ?>
            <input id="course-id" name="course_id" type="hidden" value="<?php echo html_encode($options["course_id"]) ?>" />
            <?php if ($curriculum_tag_shortname): ?>
                <input type="hidden" name="curriculum_tag_shortname" value="<?php echo html_encode($curriculum_tag_shortname) ?>" />
            <?php endif; ?>
            <div class="hide msgs"></div>
            <div id="upload-controls" class="control-group file-control-group <?php echo $include_file_title ? "hide" : "" ?>">
                <label class="control-label" for="file-<?php echo $options["form_id"]; ?>"><?php echo sprintf($translate->_("Upload %s"), html_encode($options["type_plural"])) ?></label>
                <div id="<?php echo html_encode($options["form_id"]); ?>-uploader" class="controls well uploader">
                    <div class="form-input">
                        <input class="form-file" type="file" <?php echo $allow_multiple ? 'name="files[]" multiple' : 'name="files"' ?> id="file-<?php echo html_encode($options["form_id"]); ?>" />
                        <label id="file-label" for="file-<?php echo html_encode($options["form_id"]); ?>">
                            <?php echo $translate->_("<span class=\"input-label\">Choose a file</span><span class=\"form-dragndrop\"> or drag and drop it here</span>.") ?>
                        </label>
                    </div>
                    <div class="form-uploading"><?php echo $translate->_("Uploading&hellip;") ?></div>
                    <div class="form-success"><?php echo $translate->_("Done") ?></div>
                    <div class="form-error"><?php echo $translate->_("Error") ?></div>
                </div>
            </div>
            <div class="clearfix">
                <?php if ($hide_submit_button == false) : ?>
                    <button type="submit" class="cbme-upload-<?php echo $options["form_id"]; ?>-btn btn btn-primary pull-right"><?php echo sprintf($translate->_("Save and upload %s"), html_encode($options["type_plural"])) ?></button>
                <?php endif; ?>
                <span class="cbme-upload-<?php echo html_encode($options["form_id"]); ?>-loading pull-right hide">
                        <img src="<?php echo ENTRADA_URL ?>/images/indicator.gif" />
                    <?php echo sprintf($translate->_("Uploading %s"), html_encode($options["type_plural"])) ?>
                    </span>
                <div class="cbme-upload-<?php echo $options["form_id"]; ?>-success pull-right hide">
                    <span class="fa fa-check-circle-o green-icon"></span>
                    <?php echo $translate->_("You have successfully uploaded the file(s)"); ?>
                </div>
            </div>
        </form>
        <?php
    }
}