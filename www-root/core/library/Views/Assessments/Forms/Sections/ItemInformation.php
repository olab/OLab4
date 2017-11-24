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
 * View class for assessment form form information edit contorls.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_ItemInformation extends Views_Assessments_Forms_Sections_Base {

    protected function renderView($options = array()) {
        global $translate;
        $api_url = ENTRADA_URL . "/admin/assessments/items/?section=api-items";
        $page_mode = @$options["form_mode"];
        $disabled = @$options["disabled"] ? "disabled" : "";

        $item_id = @$options["item_id"];
        $authors = $options["authors"];

        $item_types = @$options["item_types"];
        $itemtype_id = @$options["itemtype_id"];
        $itemtype_shortname = @$options["itemtype_shortname"];
        $item_in_use = @$options["item_in_use"];

        $item_text = @$options["item_text"];
        $item_code = @$options["item_code"];
        $mandatory = @$options["mandatory"];
        $comment_type = @$options["comment_type"];
        if (!$comment_type) {
            $comment_type = "disabled";
        }

        if (!empty($item_types)): ?>

            <div class="control-group">
                <label class="control-label form-required" for="item-type"><?php echo $translate->_("Item Type"); ?></label>
                <div class="controls">

                    <?php if (!$item_in_use): ?>

                        <select id="item-type" name="itemtype_id" class="span11" <?php echo $item_in_use ? "disabled='disabled'" : "" ?>>
                            <?php foreach ($item_types as $item_type): ?>
                                <option data-type-name="<?php echo $item_type->getShortname(); ?>" value="<?php echo $item_type->getID(); ?>" <?php echo($item_type->getID() == $itemtype_id ? "selected=\"selected\"" : "") ?>><?php echo $item_type->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php else: ?>

                        <?php foreach ($item_types as $item_type): ?>
                            <?php if ($item_type->getID() == $itemtype_id): ?>
                                <input type="text" value="<?php echo $item_type->getName(); ?>" readonly="readonly" />
                                <input id="item-type" name="itemtype_id" type="hidden" value=<?php echo $itemtype_id; ?> />
                            <?php endif; ?>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>

        <div class="control-group">
            <label class="control-label form-required" for="item-text"><?php echo $translate->_("Item Text"); ?></label>
            <div class="controls">
                <textarea id="item-text" name="item_text" class="expandable span11" <?php echo $disabled ?>><?php echo $item_text; ?></textarea>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="item-code"><?php echo $translate->_("Item Code"); ?></label>
            <div class="controls">
                <input class="span11" type="text" name="item_code" id="item-code" value="<?php echo $item_code; ?>" <?php echo $disabled ?>/>
            </div>
        </div>
        <div id="control-group" class="control-group">
            <div class="controls">
                <label class="checkbox" for="item-mandatory">
                    <input type="checkbox" id="item-mandatory" name="item_mandatory" value="1" <?php echo $mandatory ? "checked=\"checked\"" : "" ?> <?php echo $disabled ?>><?php echo $translate->_("Make this item mandatory"); ?>
                </label>
            </div>
        </div>
        <div id="comments-options" class="control-group <?php echo Entrada_Assessments_Forms::canHaveComments($itemtype_shortname) ? "": "hide"; ?>">
            <div class="controls">
                <label class="checkbox" for="allow-comments">
                    <input type="checkbox" id="allow-comments" name="allow_comments" value="1" <?php echo $comment_type != "disabled" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Allow comments for this item"); ?>
                </label>
            </div>
            <div class="controls" id="comments-type-section">
                <label class="radio" for="optional-comments">
                    <input type="radio" id="optional-comments" name="comment_type" value="optional" <?php echo $comment_type == "optional" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Comments are optional"); ?>
                </label>
                <label class="radio" for="mandatory-comments">
                    <input type="radio" id="mandatory-comments" name="comment_type" value="mandatory" <?php echo $comment_type == "mandatory" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Require comments for any response"); ?>
                </label>
                <label for="flagged-comments" class="radio">
                    <input type="radio" id="flagged-comments" name="comment_type" value="flagged" <?php echo $comment_type == "flagged" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Require comments for flagged responses") ?>
                </label>
            </div>
        </div>
        <?php if ($page_mode == "edit") : ?>
            <script>
                jQuery(function() {
                    jQuery("#contact-selector").audienceSelector({
                            "filter" : "#contact-type",
                            "target" : "#author-list",
                            "content_type" : "item-author",
                            "content_target" : "<?php echo $item_id; ?>",
                            "api_url" : "<?php echo $api_url ; ?>",
                            "delete_attr" : "data-aiauthor-id"
                        }
                    );
                });
            </script>
            <?php
                $audience_selector = new Views_Assessments_Forms_Controls_AudienceSelector(array("mode" => $page_mode));
                $audience_selector->render(array(
                        "authors" => $authors,
                        "related-data-key" => "data-aiauthor-id"
                    )
                );
            ?>
        <?php endif;
    }
}
