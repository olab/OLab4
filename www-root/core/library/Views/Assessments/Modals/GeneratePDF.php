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
 * View class for modal window to display and confirm the generation of
 * PDF files for assessments.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_GeneratePDF extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url"));
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $download_as_one_file_option = Entrada_Utilities::arrayValueOrDefault($options, "download_as_one_file", true);
        $download_button_label = Entrada_Utilities::arrayValueOrDefault($options, "download_button_label", $translate->_("Download PDF(s)"));
        $hide_task_table = Entrada_Utilities::arrayValueOrDefault($options, "hide_task_table", false);
        $error_url = Entrada_Utilities::arrayValueOrDefault($options, "error_url", null);
        $label = Entrada_Utilities::arrayValueOrDefault(
            $options,
            "label",
            $translate->_("Please confirm that you want to download PDFs for the following task(s):")
        );
        $this->addHeadScripts();
        ?>
        <div class="modal hide fade" id="generate-pdf-modal">
            <form class="form-horizontal" name="generate-pdf-modal-form" id="generate-pdf-modal-form" method="POST" action="<?php echo $options["action_url"] ?>">
                <?php if ($error_url): ?>
                    <input type="hidden" name="error_url" value="<?php echo html_encode($error_url); ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Download PDF"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="generate-success" class="hide">
                        <?php echo display_success($translate->_("PDF(s) downloaded successfully.")) ?>
                    </div>
                    <div id="generate-error">
                    </div>
                    <div id="generate-pdf-details-section-loading" class="hide text-center">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                        <p class="margin-top-20"><?php echo $translate->_("Please Wait..."); ?></p>
                    </div>
                    <div id="generate-pdf-details-section">
                        <strong><?php echo $label; ?></strong>
                        <?php if ($download_as_one_file_option): ?>
                        <div id="download_option" class="control-group no-margin-bottom">
                            <label id="pdf_individual_option_label" class="control-label text-left" for="pdf_individual_option"><?php echo $translate->_("Download as one file"); ?></label>
                            <div class="controls">
                                <input type="checkbox" name="pdf_individual_option" id="pdf_individual_option" checked/>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div id="generate-details" class="space-below">
                            <?php if (!$hide_task_table): ?>
                                <table id="generate-pdf-details-table" class="table table-striped table-bordered space-above">
                                    <thead>
                                        <tr>
                                            <th width="30%" id="assessor-header"><?php echo $translate->_("Assessor"); ?></th>
                                            <th width="40%"><?php echo $translate->_("Target"); ?></th>
                                            <th width="30%"><?php echo $translate->_("Delivery Date"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="generate-pdf-modal-targets-list">
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            <div id="additional-pdf-form-data" class="additional-pdf-form-data hide">
                            </div>
                        </div>
                    </div>
                    <div id="generate-pdf-download-wait" class="alert alert-info hide">
                        <div>
                            <ul>
                                <li><p class="text-center"><?php echo $translate->_("Download is currently in progress, please wait..."); ?>&nbsp;&nbsp;<img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/></p></li>
                            </ul>
                        </div>
                    </div>
                    <div id="no-generate-selected" class="hide">
                        <?php echo $translate->_("No targets selected for PDF download.") ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <button type="submit" id="generate-pdf-modal-confirm" name="generate-pdf-confirm" class="btn btn-success pull-right">
                            <span class="icon-download-alt icon-white"></span> <?php echo $download_button_label; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Build the appropriate header entries required to enable the download functionality.
     */
    public function addHeadScripts() {
        global $HEAD;
        $head_contents = array();
        ob_start();
        ?><script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/assessments/pdf-download.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script><?php
        $head_contents[] = ob_get_clean();

        foreach ($head_contents as $head_item) {
            if (!in_array(trim($head_item), $HEAD)) {
                $HEAD[] = trim($head_item);
            }
        }
        // Add common translations via Tasks object
        Entrada_Assessments_Tasks::addCommonJavascriptTranslations();
    }
}