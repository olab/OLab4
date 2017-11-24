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
class Views_Assessments_Modals_GeneratePDF extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["action_url"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $label = isset($options["label"]) ? $options["label"] : $translate->_("Please confirm that you want to download PDFs for the following assessment task(s):");
        ?>
        <div class="modal hide fade" id="generate-pdf-modal">
            <form class="form-horizontal" name="generate-pdf-modal-form" id="generate-pdf-modal-form" method="POST" action="<?php echo $options["action_url"] ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Download PDFs"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="generate-success">
                        <?php echo display_success($translate->_("PDF(s) downloaded successfully.")) ?>
                    </div>
                    <div id="generate-error">
                    </div>
                    <div id="generate-pdf-details-section">
                        <strong><?php echo $label; ?></strong>
                        <div id="download_option" class="control-group" style="margin-bottom: 0px">
                            <label id="pdf_individual_option_label" class="control-label" for="pdf_individual_option" style="text-align: left"><?php echo $translate->_("Download as one file"); ?></label>
                            <div class="controls">
                                <input type="checkbox" name="pdf_individual_option" id="pdf_individual_option" checked/>
                            </div>
                        </div>
                        <div id="generate-details" class="space-below">
                            <table id="generate-pdf-details-table" class="table table-striped table-bordered space-above">
                                <thead>
                                    <tr>
                                        <th width="30%" id="assessor-header"><?php echo $translate->_("Assessor"); ?></th>
                                        <th width="40%"><?php echo $translate->_("Target"); ?></th>
                                        <th width="30%"><?php echo $translate->_("Delivery Date"); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="no-generate-selected">
                        <?php echo $translate->_("No targets selected for PDF download.") ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <button type="submit" id="generate-pdf-modal-confirm" name="generate-pdf-confirm" class="btn btn-success pull-right">
                            <span class="icon-download-alt icon-white"></span> <?php echo $translate->_("Download PDFs"); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}