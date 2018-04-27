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

class Views_CBME_Templates_AssessmentCommentCard extends Views_HTML {

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
        <script type="text/html" id="assessment-comment-card-template">
            <div class="list-card-item-wrap">
                <div class="list-card-header">
                    <div class="list-card-title" data-content="form_title"></div>
                    <div class="list-card-label" data-content="created_date"></div>
                </div>
                <div class="list-item-stats-header clearfix">
                    <div class="list-item-tags">
                        <ul class="tag-list">
                            <li><a href="#" class="label" data-content="form_type"></a></li>
                        </ul>
                    </div>
                    <div class="list-item-scale">
                        <div class="assessment-rating"></div>
                    </div>
                </div>
                <div class="list-card-body assessment-body assessment-comments"></div>
                <div class="list-card-body assessment-body">
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Completed By") ?></span>
                        <p data-content="assessor"></p>
                    </div>
                </div>
                <div class="list-card-footer">
                    <a class="list-card-cell full-width" data-href="assessment_url"><?php echo $translate->_("View Details") ?></a>
                    <div class="list-card-cell list-card-btn-group">
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
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }
}
