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

class Views_CBME_Templates_AssessmentCard extends Views_HTML {

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
        <!-- Completed assessments card template -->
        <script type="text/html" id="assessment-card-template">
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
                <div class="list-card-body assessment-body" style="display: none;">
                    <div class="list-card-body-section assessment-card-entrustment">
                        <span class="list-card-label"><?php echo $translate->_("Entrustment Rating") ?></span>
                        <p class="rating-title" data-content="entrustment_response_descriptor"></p>
                    </div>
                    <!-- Comment section should not display for PPA form type -->
                    <div class="list-card-body-section assessment-card-comment">
                        <span class="list-card-label"><?php echo $translate->_("Comment") ?></span>
                        <p data-content="comment_response"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Completed By") ?></span>
                        <p data-content="assessor"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Assessment Method") ?></span>
                        <p data-content="assessment_method"></p>
                    </div>
                </div>
                <div class="list-card-footer">
                    <a class="list-card-cell full-width view-assessment-details" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute": "data-read-id", "value": "read_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"},{"attribute": "data-read-type", "value" : "read_type"}]' data-href="assessment_url"><?php echo $translate->_("View Details") ?></a>
                    <div class="list-card-label assessor-footer-label" data-content="assessor_label"></div>
                    <div class="list-card-cell list-card-btn-group">
                        <a data-class="show_like_and_comment">
                            <i data-class="comment_icon_class" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute": "data-like-id", "value": "like_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"},{"attribute": "id", "value" : "like_button_id"}]'></i>
                        </a>
                        <a data-class="show_like_and_comment">
                            <span data-class="like_assessment_class" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute": "data-like-id", "value": "like_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"}]'></span>
                        </a>
                        <a class="list-card-btn read-item">
                            <span data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute": "data-read-id", "value": "read_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"},{"attribute": "data-read-type", "value" : "read_type"}]' data-class="read_class"></span>
                        </a>
                        <a class="list-card-btn">
                            <span data-pin-type="assessment" class="pin" data-template-bind='[{"attribute": "data-id", "value": "dassessment_id"}, {"attribute": "data-dassessment-id", "value": "dassessment_id"}, {"attribute": "data-pin-id", "value": "pin_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"}]' data-class="pinned_class"></span>
                        </a>
<!--                        <a class="list-card-btn flag-item">-->
<!--                            <span class="list-card-icon fa fa-flag"></span>-->
<!--                        </a>-->
                        <a class="list-card-btn details-item">
                            <span class="list-card-icon fa fa-chevron-down"></span>
                        </a>
                    </div>
                </div>
                <div class="assessment-like-comment-area hide" data-template-bind='[{"attribute": "id", "value" : "comment_area_id"}]'>
                    <div data-class="previous_comment_class">
                        <div class="previously-uploaded-comment" data-content="comment"></div>
                    </div>
                    <textarea class="like-assessment-comment" data-template-bind='[{"attribute": "id", "value" : "assessment_comment_area_id"}]' placeholder="<?php echo $translate->_("Leave a comment here (optional)"); ?>"></textarea>
                    <div class="space-above align-right">
                        <button class="btn btn-default close-assessment-like-comment space-right" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"}]'><?php echo $translate->_("Close"); ?></button>
                        <button class="btn btn-primary submit-assessment-comment" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"},{"attribute": "data-like-id", "value": "like_id"},{"attribute": "data-aprogress-id", "value" : "aprogress_id"},{"attribute": "id", "value" : "submit_assessment_comment_id"}]'><?php echo $translate->_("Submit"); ?></button>
                    </div>
                </div>
            </div>
        </script>

        <!-- Pending/Inprogress assessments card template -->
        <script type="text/html" id="assessment-pending-card-template">
            <div class="list-card-item-wrap">
                <div class="list-card-header">
                    <div class="list-card-title" data-content="form_title"></div>
                    <div class="list-card-label" data-content="created_date"></div>
                </div>
                <div class="list-item-stats-header clearfix">
                    <div class="list-item-tags">
                        <ul class="tag-list">
                            <li><a href="#" class="label" data-content="form_type"></a></li>
                            <li><a class="label" data-content="progress_value"></a></li>
                        </ul>
                    </div>
                    <div class="list-item-scale">
                        <div class="assessment-rating"></div>
                    </div>
                </div>
                <div class="list-card-body assessment-body" style="display: none;">
                    <div class="list-card-body-section assessment-card-entrustment">
                        <span class="list-card-label"><?php echo $translate->_("Entrustment Rating") ?></span>
                        <p class="rating-title" data-content="entrustment_response_descriptor"></p>
                    </div>
                    <!-- Comment section should not display for PPA form type -->
                    <div class="list-card-body-section assessment-card-comment">
                        <span class="list-card-label"><?php echo $translate->_("Comment") ?></span>
                        <p data-content="comment_response"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Assessed By") ?></span>
                        <p data-content="assessor"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Assessment Method") ?></span>
                        <p data-content="assessment_method"></p>
                    </div>
                </div>
                <div class="list-card-footer">
                    <div class="list-card-label assessor-footer-label" data-content="assessor_label"></div>
                    <div class="list-card-cell list-card-btn-group drawer-toggle-icon">
                        <a class="list-card-btn details-item">
                            <span class="list-card-icon fa fa-chevron-down"></span>
                        </a>
                    </div>
                </div>
            </div>
        </script>

        <!-- Deleted assessments card template -->
        <script type="text/html" id="assessment-deleted-card-template">
            <div class="list-card-item-wrap">
                <div class="list-card-header">
                    <div class="list-card-title" data-content="form_title"></div>
                    <div class="list-card-label" data-content="deleted_date"></div>
                    <div class="list-card-label small-space-right" data-content="deleted_by"></div>
                </div>
                <div class="list-item-stats-header clearfix">
                    <div class="list-item-tags">
                        <ul class="tag-list">
                            <li><a href="#" class="label" data-content="form_type"></a></li>
                        </ul>
                        <div></div>
                    </div>
                    <div class="list-item-scale">
                        <div class="assessment-rating"></div>
                    </div>
                </div>
                <div class="list-card-body assessment-body" style="display: none;">
                    <div class="list-card-body-section assessment-card-entrustment">
                        <span class="list-card-label"><?php echo $translate->_("Entrustment Rating") ?></span>
                        <p class="rating-title" data-content="entrustment_response_descriptor"></p>
                    </div>
                    <!-- Comment section should not display for PPA form type -->
                    <div class="list-card-body-section assessment-card-comment">
                        <span class="list-card-label"><?php echo $translate->_("Comment") ?></span>
                        <p data-content="comment_response"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Assessment Method") ?></span>
                        <p data-content="assessment_method"></p>
                    </div>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Deleted Reason") ?></span>
                        <p data-content="deleted_reason"></p>
                    </div>
                </div>
                <div class="list-card-footer">
                    <div class="list-card-label assessor-footer-label" data-content="assessor_label"></div>
                    <div class="list-card-cell list-card-btn-group drawer-toggle-icon">
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
