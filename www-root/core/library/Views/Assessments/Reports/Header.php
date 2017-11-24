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
 * Simple view to render the header portion of the report related pages.
 * Includes relevant titles and an error message container.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_Header extends Views_Assessments_Base
{
    /**
     * Validate
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["target_name"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the html.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $show_pdf_button    = @$options["enable_pdf_button"];
        $pdf_unavailable    = @$options["pdf_configured"] ? false : true;
        $generate_as_pdf    = @$options["generate_pdf"]; // when generating PDF, we hide the button
        $full_url           = @$options["pdf_generation_url"];

        $completed_string = $translate->_("Completed Forms on %s");
        if (@$options["use_assessments_title"]) {
            $completed_string = $translate->_("Completed Assessments on %s");
        }
        ?>
        <div <?php echo $this->getClassString() ?>>
            <h1 class="space-below medium"><?php echo html_encode(sprintf($completed_string, $options["target_name"])) ?></h1>
            <?php if (!$generate_as_pdf && $show_pdf_button) : ?>
                <?php $this->renderPDFOutputButton($full_url, $pdf_unavailable); ?>
            <?php endif; ?>
            <?php if (@$options["form_name"] || @$options["distribution_name"]): ?>
                <div class="clearfix">
                    <?php if (@$options["form_name"]): ?>
                        <h2 class="no-margin"><?php echo html_encode($translate->_("Form")) ?>:&nbsp;<?php echo html_encode($options["form_name"]) ?></h2>
                    <?php endif; ?>
                    <?php if (@$options["distribution_name"]): ?>
                        <h2 class="no-margin"><?php echo $translate->_("Distribution"); ?>:&nbsp;<?php echo html_encode($options["distribution_name"]) ?></h2>
                    <?php endif;?>
                    <?php if (@$options["list_info"]): ?>
                        <ul><?php echo $options["list_info"] ?></ul>
                    <?php endif;?>
                    <?php if (@$options["description"]): ?>
                        <div class="assessment-report-node">
                            <table class="table table-striped table-bordered">
                            <tbody>
                            <tr>
                                <td class="form-search-message text-center" colspan="4">
                                <p class="no-search-targets space-above space-below medium"><?php echo @$options["description"]; ?></p>
                                </td>
                            </tr>
                            </tbody>
                            </table>
                        </div>
                    <?php endif;?>
                </div>
            <?php endif; ?>
        </div>
        <div id="reports-error-msg"></div>
        <?php
    }

    /**
     * Render the "Download PDF" button
     *
     * @param string $full_url
     * @param bool $pdf_unavailable
     */
    private function renderPDFOutputButton($full_url, $pdf_unavailable = true) {
        global $translate; ?>
        <div class="clearfix">
            <div id="pdf-button-error"></div>
            <div class="assessment-report-control pull-right">
                <a id="assessment-report-create-pdf" href="<?php echo $full_url ?>" class="btn btn-success" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="<?php echo $pdf_unavailable ? 1 : 0?>">
                    <i class="icon-download-alt icon-white"></i> <?php echo $translate->_("Download PDF"); ?>
                </a>
            </div>
        </div>
        <?php
    }

}