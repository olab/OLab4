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
 * A view for rendering CBME assessment item cards
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Item_Card extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array(
            "dassessment_id",
            "assessor_value",
            "assessor_type",
            "updated_date",
            "item_id",
            "item_text",
            "item_description",
            "rubric_id",
            "comments",
            "order",
            "item_response_text",
            "item_rating_scale_id",
            "rubric_rating_scale_id",
            "response_descriptor",
            "rubric_title",
            "rating_scale_responses",
            "assessor"
        ));
    }

    protected function renderView($options = array()) {
        global $translate;
        ?>
        <li class="list-card-item item-list-card">
            <div class="list-card-item-wrap">
                <?php
                $card_header_view = new Views_CBME_Card_Header();
                $card_header_view->render(array(
                    "form_type" => "",
                    "updated_date" => $options["updated_date"] ? $options["updated_date"] : $options["created_date"],
                    "title" => ($options["rubric_id"] ? $options["rubric_title"] : $options["item_text"]),
                    "card_type" => "completed",
                    "encounter_date" => $options["encounter_date"]
                ));

                ?>
                <div class="list-item-stats-header clearfix">
                    <?php if ($options["mapped_epas"]) : ?>
                    <div class="list-item-tags">
                        <ul>
                            <?php foreach ($options["mapped_epas"] as $mapped_epa) : ?>
                                <li><a href="#" class="label <?php echo html_encode($mapped_epa["stage_code"]) ?>-stage"><?php echo html_encode($mapped_epa["objective_code"]) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ($options["item_rating_scale_id"] || $options["rubric_rating_scale_id"]) : ?>
                        <div class="list-item-scale">
                            <div class="assessment-item-rating">
                                <?php foreach ($options["rating_scale_responses"] as $rating_scale_response) : ?>
                                    <?php if ($rating_scale_response["order"] <= $options["order"]) : ?>
                                        <span data-toggle="tooltip" data-container="body" title="<?php echo html_encode($rating_scale_response["text"]); ?>" class="rating-icon-active fa fa-circle"></span>
                                    <?php else : ?>
                                        <span data-toggle="tooltip" data-container="body" title="<?php echo html_encode($rating_scale_response["text"]); ?>" class="rating-icon fa fa-circle"></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="list-card-body assessment-item-body" style="display: none;">
                    <?php if ($options["item_description"]) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Description"); ?></span>
                            <p>
                                <?php echo html_encode($options["item_description"]); ?>
                            </p>
                        </div>
                    <?php elseif ($options["item_text"] && $options["rubric_id"]) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Description"); ?></span>
                            <p>
                                <?php echo html_encode($options["item_text"]); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if ($options["item_response_text"]) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Response"); ?></span>
                            <p>
                                <?php echo html_encode($options["item_response_text"]); ?>
                            </p>
                        </div>
                    <?php elseif ($options["response_descriptor"]) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Response Descriptor"); ?></span>
                            <p>
                                <?php echo html_encode($options["response_descriptor"]); ?>
                            </p>
                        </div>
                    <?php elseif ($options["comments"]) : ?>
                        <div class="list-card-body-section">
                            <span class="list-card-label"><?php echo $translate->_("Comment"); ?></span>
                            <p>
                                <?php echo html_encode($options["comments"]); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="list-card-body-section">
                        <span class="list-card-label"><?php echo $translate->_("Completed By"); ?></span>
                        <p><?php echo html_encode($options["assessor"]); ?></p>
                    </div>
                </div>
                <?php
                $card_footer_view = new Views_CBME_Card_Footer();
                $card_footer_view->render(array("dassessment_id" => $options["dassessment_id"], "data_id" => $options["item_id"], "is_pinned" => $options["is_pinned"], "card_type" => "item", "pin_id" => $options["pin_id"], "aprogress_id" => $options["aprogress_id"], "read_id" => $options["read_id"]));
                ?>
            </div>
        </li>
        <?php
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME item card"); ?></strong>
        </div>
        <?php
    }
}
