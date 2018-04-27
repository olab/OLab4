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
 * This view is used in sidebars and headers, to display assessment
 * related delivery information.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Templates_AssessmentItemCard extends Views_HTML {

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
        ?>
        <script type="text/html" id="item-card-template">
            <div class="list-card-item-wrap">
                <div class="list-card-header">
                    <div class="list-card-title" data-content="item_text"></div>
                    <div class="list-card-label" data-content="created_date"></div>
                </div>
                <div class="list-item-stats-header clearfix">
                    <div class="list-item-tags">
                        <ul class="tag-list"></ul>
                    </div>
                    <div class="list-item-scale">
                        <div class="assessment-item-rating">
                        </div>
                    </div>
                </div>
                <div class="list-card-body assessment-item-body" style="display: none;">
                    <div class="list-card-body-section item-description">
                        <span class="list-card-label"><?php echo $translate->_("Description") ?></span>
                        <p data-content="item_description"></p>
                    </div>
                    <div class="list-card-body-section item-response-descriptor">
                        <span class="list-card-label"><?php echo $translate->_("Response") ?></span>
                        <p data-content="response_descriptor"></p>
                    </div>
                    <div class="list-card-body-section item-response-text">
                        <span class="list-card-label"><?php echo $translate->_("Response") ?></span>
                        <p data-content="response"></p>
                    </div>
                    <div class="list-card-body-section item-comments">
                        <span class="list-card-label"><?php echo $translate->_("Comment") ?></span>
                        <p data-content="comments"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Completed By") ?></span>
                        <p data-content="assessor"></p>
                    </div>
                </div>
                <div class="list-card-footer">
                    <a class="list-card-cell full-width" data-href="assessment_url"><?php echo $translate->_("View Details") ?></a>
                    <div class="list-card-cell list-card-btn-group">
                        <a class="list-card-btn">
                            <span data-pin-type="item" class="pin" data-template-bind='[{"attribute": "data-id", "value": "item_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"},{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute" : "data-pin-id", "value" : "pin_id"}]' data-class="pinned_class"></span>
                        </a>
                        <a class="list-card-btn flag-item">
                            <span class="list-card-icon fa fa-flag"></span>
                        </a>
                        <a class="list-card-btn details-item">
                            <span class="list-card-icon fa fa-chevron-down"></span>
                        </a>
                    </div>
                </div>
            </div>
        </script>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts () {
        global $HEAD;
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
    }
}
