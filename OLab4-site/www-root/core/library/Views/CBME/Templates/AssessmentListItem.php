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
 * This view is used in the cbme dashboard to show the breakdown of assessments
 * for a specific EPA
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Templates_AssessmentListItem extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->addHeadScripts();

        //Global Entrustment Scale
        ?>
        <script type="text/html" id="list-assessment-template">
            <div class="list-assessment-wrapper" data-template-bind='[{"attribute": "data-progress", "value": "assessment_progress"}, {"attribute": "data-form-ids", "value": "assessment_form_ids"}, {"attribute": "data-rating-scale-ids", "value": "rating_scale_ids"}]'>
                <div class="list-header">
                    <div class="badge inline-block space-left" data-content="assessment_total"></div>
                    <div class="inline-block space-left assessment-tool-title" data-content="title"></div>
                </div>
                <div data-class="scale_wrap_class" class='scale-wrap assessment-scale-wrap pull-right space-right'>
                </div>
            </div>
        </script>

        <?php //No scale ?>
        <script type="text/html" id="no-scale-template">
            <div class="list-assessment-wrapper" data-template-bind='[{"attribute": "data-progress", "value": "assessment_progress"}, {"attribute": "data-form-ids", "value": "assessment_form_ids"}, {"attribute": "data-rating-scale-ids", "value": "rating_scale_ids"}]'>
                <div class="list-header">
                    <div class="space-left badge inline-block" data-content="assessment_total"></div>
                    <div class="space-left inline-block assessment-tool-title" data-content="title"></div>
                </div>
                <span class='scale-wrap assessment-scale-wrap pull-right space-right'>
                    <?php echo $translate->_("N/A"); ?>
                </span>
            </div>
        </script>

        <script type="text/html" id="assessment-count-template">
            <span data-toggle='tooltip' data-class="assessment_class" class='assessment-scale inline-block' data-content="assessment_count"  data-template-bind='[{"attribute": "title", "value": "assessment_tooltip"}, {"attribute": "data-iresponses", "value": "assessment_iresponses"}]'></span>
        </script>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts () {
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }
}
