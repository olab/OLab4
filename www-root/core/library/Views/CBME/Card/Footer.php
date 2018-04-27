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
 * A view for rendering the CBME assessment card footer
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Card_Footer extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("dassessment_id", "data_id"));
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $is_comment_card = Entrada_Utilities::arrayValueOrDefault($options, "is_comment_card");
        $this->renderHead();
        ?>
        <div class="list-card-footer">
            <?php if ($options["card_type"] == "completed" || $options["card_type"] == "item"): ?>
                <?php if ($options["card_type"] == "completed"): ?>
                    <a class="list-card-cell full-width view-assessment-details <?php echo isset($options["read_id"]) && $options["read_id"] ? 'read-assessment' : 'unread-assessment' ?>" href="<?php echo ENTRADA_URL; ?>/assessments/assessment?dassessment_id=<?php echo $options["dassessment_id"]; ?><?php echo isset($options["atarget_id"]) ? "&atarget_id=" . $options["atarget_id"] : "" ?><?php echo (isset($options["aprogress_id"]) ? "&aprogress_id=" . $options["aprogress_id"] : "") ?>" data-read-type="<?php echo $options["card_type"]; ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" data-id="<?php echo $options["data_id"]; ?>" data-read-id="<?php echo $options["read_id"]; ?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>"><?php echo $translate->_("View Details"); ?></a>
                <?php else : ?>
                    <a class="list-card-cell full-width" href="<?php echo ENTRADA_URL; ?>/assessments/assessment?dassessment_id=<?php echo $options["dassessment_id"]; ?>"><?php echo $translate->_("View Details"); ?></a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options["card_type"] !== "item" && $options["card_type"] !== "assessment"): ?>
                <?php if ($options["assessor"]): ?>
                    <div class="list-card-label assessor-footer-label"><?php echo sprintf($translate->_("Assessed By %s"), $options["assessor"]) ?></div>
                <?php endif ?>
            <?php endif; ?>
            <div class="list-card-cell list-card-btn-group  <?php echo $options["card_type"] !== "completed" ? "drawer-toggle-icon" : "";?>">
                <?php if (!$is_comment_card && ($options["card_type"] == "completed" || $options["card_type"] == "item")) : ?>
                    <?php if ($options["card_type"] == "completed"): ?>
                        <?php if ($options["is_admin_view"] == 0): ?>
                            <a class="list-card-btn" >
                                <i class="list-card-icon fa like-comment-icon <?php echo isset($options["like_id"]) && $options["like_id"] ? (isset($options["comment"]) && $options["comment"] ? "fa-commenting" : "fa-comment"): "fa-comment hide" ?>" title="<?php echo $translate->_("Add a comment to this assessment"); ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>" id="assessment-comment-<?php echo $options["aprogress_id"]; ?>"></i>
                            </a>
                            <a class="list-card-btn">
                                <span class="list-card-icon fa fa-thumbs-up like-assessment <?php echo isset($options["like_id"]) && $options["like_id"] ? 'liked' : 'unliked' ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" title="<?php echo $translate->_("Like this assessment");?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>" data-like-id="<?php echo $options["like_id"]; ?>"></span>
                            </a>
                        <?php endif; ?>
                        <a class="list-card-btn read-item">
                            <span title="<?php echo $translate->_("Mark assessment as read"); ?>" data-read-type="<?php echo $options["card_type"]; ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" data-id="<?php echo $options["data_id"]; ?>" data-read-id="<?php echo $options["read_id"]; ?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>" class="list-card-icon fa  read-toggle <?php echo isset($options["read_id"]) && $options["read_id"] ? 'read fa-eye' : 'unread fa-eye-slash' ?>"></span>
                        </a>
                    <?php endif; ?>
                    <a class="list-card-btn">
                        <span title="<?php echo $translate->_("Pin Assessment"); ?>" data-pin-type="<?php echo ($options["card_type"] == "completed" ? "assessment" : "item") ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" data-id="<?php echo $options["data_id"] ?>" data-pin-id="<?php echo $options["pin_id"]; ?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>" class="list-card-icon fa fa-thumb-tack pin <?php echo isset($options["pin_id"]) && $options["pin_id"] ? 'pinned' : '' ?>"></span>
                    </a>
<!--                    <a  class="list-card-btn flag-item">-->
<!--                        <span class="list-card-icon fa fa-flag"></span>-->
<!--                    </a>-->
                <?php endif; ?>
				<a class="list-card-btn details-item">
                    <span class="list-card-icon fa fa-chevron-down"></span>
                </a>
            </div>
        </div>
        <div class="assessment-like-comment-area hide" id="comment-area-<?php echo $options["aprogress_id"]; ?>">
            <?php if (isset($options["comment"]) && $options["comment"]) : ?>
                <div class="space-below">
                    <div class="previously-uploaded-comment"><?php echo $options["comment"]; ?></div>
                </div>
            <?php endif; ?>
            <textarea class="like-assessment-comment" id="assessment-comment-area-<?php echo $options["aprogress_id"]; ?>" placeholder="<?php echo $translate->_("Leave a comment here (optional)"); ?>"></textarea>
            <div class="space-above align-right">
                <button class="btn btn-default close-assessment-like-comment space-right" data-dassessment-id="<?php echo $options["aprogress_id"]; ?>"><?php echo $translate->_("Close"); ?></button>
                <button class="btn btn-primary submit-assessment-comment" id="submit-assessment-comment-<?php echo $options["aprogress_id"]; ?>" data-dassessment-id="<?php echo $options["dassessment_id"]; ?>" data-aprogress-id="<?php echo $options["aprogress_id"] ?>" data-like-id="<?php echo $options["like_id"]; ?>"><?php echo $translate->_("Submit"); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME card footer"); ?></strong>
        </div>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead () {
        Entrada_Utilities::addJavascriptTranslation("A problem occurred while attempting to pin this item. Please try again later.", "assessment_pin_error", "cbme_assessments");
    }
}
