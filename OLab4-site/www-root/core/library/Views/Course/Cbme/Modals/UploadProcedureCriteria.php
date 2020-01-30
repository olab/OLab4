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
 * View class for rendering the procedure criteria uploader
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Course_Cbme_Modals_UploadProcedureCriteria extends Views_HTML
{
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        $required = array(
            "course_id",
            "title"
        );

        if (!$this->validateIsSet($options,$required)) {
            return false;
        }

        if (!$this->validateArray($options, array("epas"))) {
            return false;
        }

        return true;
    }

    /**
     * Render the procedure criteria uploader view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        $this->renderUploadView($options);
        $this->renderUploadedView($options);
    }

    private function renderUploadedView($options) {
        global $translate;

        $title = $options["title"];
        ?>
        <div class="modal fade" id="procedure-criteria-uploaded-msg-modal" style="display:none">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_($title); ?></h3>
            </div>
            <div class="modal-body">
                <div id="epa-content-title"></div>
                <div id="epa-uploaded-date"><strong><?php echo $translate->_("Uploaded on "); ?><span class="uploaded-date"></span></strong></div>
                <ul id="procedure-uploaded-criteria-uploader-body" class="procedure-uploaded-criteria-uploader-body"></ul>
                <div class="upload-criteria-warning"><?php echo $translate->_("Uploading a new file to these EPA(s) will overwrite them."); ?></div>
            </div>
            <div class="modal-footer">
                <div class="row-fluid procedure-uploaded-criteria-modal-btns">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <button id="procedure-criteria-replace-attributes" type="submit" class="btn btn-primary pull-right"><?php echo $translate->_("Upload new criteria") ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderUploadView($options) {
        global $translate;

        $title = $options["title"];
        $course_id = $options["course_id"];
        ?>
        <script type="text/javascript">
            var procedure_criteria_uploader_localization = {};
            procedure_criteria_uploader_localization.choose_file_message = '<?php echo $translate->_("<span class=\"input-label\">Choose a file</span><span class=\"form-dragndrop\"> or drag and drop it here</span>."); ?>';
        </script>
        <div class="modal fade" id="procedure-criteria-uploader-modal" style="display:none">
            <form id="procedure-criteria-form" class="form-vertical space-above medium upload-form" enctype="multipart/form-data" data-method="upload-procedure-criteria">
                <input id="procedure-modal-course-id" type="hidden" name="course_id" value="<?php echo $course_id; ?>" />
                <input id="procedure-modal-procedure-id" type="hidden" name="procedure_id" value="" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_($title); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="procedure-criteria-uploader-body" class="procedure-criteria-uploader-body">
                        <div id="error_msgs" class="hide"></div>
                        <div id="upload-controls" class="control-group">
                            <label class="control-label form-required" for="procedure-criteria-file-input"><?php echo $translate->_("Upload CSV") ?></label>
                            <div class="controls well uploader">
                                <div class="form-input">
                                    <input id="procedure-criteria-file-input" class="form-file" type="file" name="files" />
                                    <label id="file-label" for="procedure-criteria-file-input">
                                        <?php echo $translate->_("<span class=\"input-label\">Choose a file</span><span class=\"form-dragndrop\"> or drag and drop it here</span>.") ?>
                                    </label>
                                </div>
                                <div class="form-uploading"><?php echo $translate->_("Uploading&hellip;") ?></div>
                                <div class="form-success"><?php echo $translate->_("Done") ?></div>
                                <div class="form-error"><?php echo $translate->_("Error") ?></div>
                            </div>
                        </div>
                        <div id="epa-select-container">
                            <div class="control-group">
                                <label for="select-epa-btn" class="control-label"><?php echo $translate->_("Select EPA(s)") ?></label>
                                <div class="controls">
                                    <button id="select-epa-btn" class="btn btn-default"><?php echo $translate->_("Click here to select EPA(s)") ?> <i class="icon-chevron-down"></i></button>
                                    <ul id="selected_epa_list_container" class="selected-items-list">
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php echo $this->renderAdvancedSearch($options["epas"]) ?>
                        <div id="procedure-criteria-modal-succeeded">
                            <?php echo $translate->_("The file has been successfully uploaded and processed."); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid procedure-criteria-modal-btns">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <button type="submit" class="cbme-upload-btn btn btn-primary pull-right"><?php echo $translate->_("Save and upload criteria") ?></button>
                    </div>
                    <div class="row-fluid procedure-criteria-modal-upload-loading hide">
                        <img src="<?php echo ENTRADA_URL ?>/images/indicator.gif" />
                        <?php echo $translate->_("Uploading Criteria...") ?>
                    </div>
                    <div class="row-fluid procedure-criteria-modal-after-upload hide">
                        <a href="#" class="btn btn-primary pull-right" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    private function renderAdvancedSearch($epas) {
        global $translate;
        $epa_datasource = array();

        if ($epas) {
            foreach ($epas as $epa) {
                $epa_datasource[] = array("target_id" => $epa["objective_id"], "target_label" => $epa["objective_code"] . ":" . " " . $epa["objective_name"] );
            }
        }

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                var epa_datasource = <?php echo json_encode($epa_datasource) ?>;

                var filters = {
                    selected_epa: {
                        label: "<?php echo $translate->_("EPA"); ?>",
                        data_source: epa_datasource
                    }
                };

                $("#select-epa-btn").advancedSearch({
                    api_url: "<?php echo ENTRADA_URL; ?>",
                    resource_url: "<?php echo ENTRADA_URL; ?>",
                    filters: filters,
                    build_selected_filters: false,
                    select_all_enabled: true,
                    modal: true,
                    control_class: "course-epa-selector",
                    no_results_text: "<?php echo $translate->_("No EPAs found matching the search criteria"); ?>",
                    parent_form: $("#procedure-criteria-form"),
                    width: 400
                });
            });
        </script>
        <?php
    }
}