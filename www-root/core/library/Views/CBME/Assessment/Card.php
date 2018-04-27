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
 * A view for rendering CBME assessment cards
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Assessment_Card extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("form_type", "title", "created_date", "updated_date", "dassessment_id"));
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $dassessment_id = Entrada_Utilities::arrayValueOrDefault($options, "dassessment_id");
        $aprogress_id = Entrada_Utilities::arrayValueOrDefault($options, "aprogress_id");
        $comments = Entrada_Utilities::arrayValueOrDefault($options, "comments");
        $comment_response = Entrada_Utilities::arrayValueOrDefault($options, "comment_response");
        $assessor = Entrada_Utilities::arrayValueOrDefault($options, "assessor");
        $triggered_by = Entrada_Utilities::arrayValueOrDefault($options, "triggered_by");
        $entrustment_response_descriptor = Entrada_Utilities::arrayValueOrDefault($options, "entrustment_response_descriptor");
        $selected_iresponse_order = Entrada_Utilities::arrayValueOrDefault($options, "selected_iresponse_order");
        $rating_scale_responses = Entrada_Utilities::arrayValueOrDefault($options, "rating_scale_responses");
        $mapped_epas = Entrada_Utilities::arrayValueOrDefault($options, "mapped_epas");
        $is_comment_card = Entrada_Utilities::arrayValueOrDefault($options, "is_comment_card");
        $assessment_method = Entrada_Utilities::arrayValueOrDefault($options, "assessment_method");
        $deleted_reason = Entrada_Utilities::arrayValueOrDefault($options, "deleted_reason_notes");
        $card_type = Entrada_Utilities::arrayValueOrDefault($options, "card_type");
        $assessment_created_date = Entrada_Utilities::arrayValueOrDefault($options, "assessment_created_date");
        ?>

        <li class="list-card-item assessment-list-card">
            <div class="list-card-item-wrap">
                <?php

                /**
                 * Instantiate and render the card header view
                 */
                $card_header_view = new Views_CBME_Card_Header();
                $card_header_view->render(array("form_type" => $options["form_type"], "title" => $options["title"], "created_date" => $options["created_date"], "updated_date" => $options["updated_date"], "deleted_date" => $options["deleted_date"], "card_type" => $card_type, "assessment_created_date" => $assessment_created_date, "deleted_by" => $options["deleted_by"], "encounter_date" => $options["encounter_date"])); ?>

                <div class="list-item-stats-header clearfix">
                    <div class="list-item-tags">
                        <ul>
                            <li><a href="#" class="label"><?php echo html_encode($options["form_type"]); ?></a></li>
                            <?php if ($card_type === "inprogress" || $card_type === "pending") : ?>
                                <li><a class="label"><?php echo ($card_type === "inprogress" ? $translate->_("In Progress") : ($card_type === "pending" ? $translate->_("Pending") : "")); ?></a></li>
                            <?php endif; ?>
                            <?php if ($mapped_epas) : ?>
                                <?php foreach ($mapped_epas as $mapped_epa) : ?>
                                    <li><a href="#" class="label <?php echo html_encode($mapped_epa["stage_code"]) ?>-stage"><?php echo html_encode($mapped_epa["objective_code"]) ?></a></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($rating_scale_responses) : ?>
                    <div class="list-item-scale">
                        <div class="assessment-rating">
                            <?php foreach ($rating_scale_responses as $rating_scale_response) : ?>
                                <?php if ($rating_scale_response["order"] <= $selected_iresponse_order) : ?>
                                    <span data-toggle="tooltip" data-container="body" title="<?php echo html_encode($rating_scale_response["text"]); ?>" class="rating-icon-active fa fa-star"></span>
                                <?php else : ?>
                                    <span data-toggle="tooltip" data-container="body" title="<?php echo html_encode($rating_scale_response["text"]); ?>" class="rating-icon fa fa-star"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($comments) : ?>
                    <div class="list-card-body assessment-body assessment-comments">
                    <?php foreach ($comments as $comment) : ?>
                        <div class="list-card-body-section clearfix">
                            <a class="list-card-btn pin-comment comment-pin pull-left">
                                <span title="<?php echo $translate->_("Pin Assessment") ?>" data-pin-type="comment" data-dassessment-id="<?php echo $dassessment_id ?>" data-id="<?php echo $comment["epresponse_id"] ?>" data-pin-id="<?php echo $comment["pin_id"] ?>" data-aprogress-id="<?php echo $aprogress_id ?>" class="list-card-icon fa fa-thumb-tack pin <?php echo $comment["pin_id"] && !$comment["deleted_date"] ? "pinned" : "" ?>"></span>
                            </a>
                            <div class="assessment-comment pull-right">
                                <span class="list-card-label"><?php echo html_encode($comment["item_text"]); ?></span>
                                <p class="comment">
                                    <?php echo ($comment["comments"] ? html_encode($comment["comments"]) : "N/A"); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="list-card-body assessment-body" <?php echo (!$is_comment_card ? "style='display:none'" : "") ?>>
                    <?php if ($entrustment_response_descriptor) : ?>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Entrustment Rating"); ?></span>
                        <p>
                            <?php echo html_encode($entrustment_response_descriptor); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <!-- Comment section should not display for PPA form type -->
                    <?php if ($comment_response) : ?>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Comment"); ?></span>
                        <p>
                            <?php echo html_encode($comment_response); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if ($assessor && $card_type !== "deleted") : ?>
                    <div class="list-card-body-section">
                        <?php if ($card_type === "completed") : ?>
                            <span class="list-card-label"><?php echo $translate->_("Completed By") ?></span>
                        <?php else: ?>
                            <span class="list-card-label"><?php echo $translate->_("Assessed By") ?></span>
                        <?php endif; ?>
                        <p><?php echo html_encode($assessor) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($triggered_by) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Triggered By") ?></span>
                            <p><?php echo html_encode($triggered_by) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($assessment_method) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Assessment Method") ?></span>
                            <p><?php echo html_encode($assessment_method) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($card_type === "deleted") : ?>
                        <?php if ($deleted_reason) : ?>
                            <div class="list-card-body-section">
                                <span class="list-card-label"><?php echo $translate->_("Deleted Reason") ?></span>
                                <p><?php echo html_encode($deleted_reason) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php

                /**
                 * Instantiate and render the card footer view
                 */
                $card_footer_view = new Views_CBME_Card_Footer();
                $card_footer_view->render(array(
                    "dassessment_id" => $options["dassessment_id"],
                    "data_id" => $options["dassessment_id"],
                    "atarget_id" => $options["atarget_id"],
                    "aprogress_id" => $options["aprogress_id"],
                    "is_pinned" => (isset($options["is_pinned"]) ? $options["is_pinned"] : false),
                    "card_type" => $options["card_type"],
                    "pin_id" => (isset($options["pin_id"]) ? $options["pin_id"] : 0),
                    "is_comment_card" => $is_comment_card,
                    "read_id" => $options["read_id"],
                    "like_id" => $options["like_id"],
                    "comment" => $options["comment"],
                    "is_admin_view" => $options["is_admin_view"],
                    "assessor" => $assessor ? $assessor : "")); ?>
            </div>
        </li>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME assessment card"); ?></strong>
        </div>
        <?php
    }
}
