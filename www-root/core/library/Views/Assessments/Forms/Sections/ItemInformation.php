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

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("authors", "scale_type_datasource"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $api_url = ENTRADA_URL . "/admin/assessments/items/?section=api-items";

        $authors                     = $options["authors"];
        $scale_type_datasource       = $options["scale_type_datasource"];
        $page_mode                   = array_key_exists("form_mode", $options) ? $options["form_mode"] : null;
        $disabled                    = array_key_exists("disabled", $options) ? $options["disabled"] ? "disabled" : "" : "";
        $comment_type                = array_key_exists("comment_type", $options) ? $options["comment_type"] ?  $options["comment_type"] : "disabled" : "disabled";
        $item_id                     = array_key_exists("item_id", $options) ? $options["item_id"] : null;
        $item_types                  = array_key_exists("item_types", $options) ? $options["item_types"] : null;
        $itemtype_id                 = array_key_exists("itemtype_id", $options) ? $options["itemtype_id"] : null;
        $itemtype_shortname          = array_key_exists("itemtype_shortname", $options) ? $options["itemtype_shortname"] : null;
        $item_in_use                 = array_key_exists("item_in_use", $options) ? $options["item_in_use"] : null;
        $item_text                   = array_key_exists("item_text", $options) ? $options["item_text"] : null;
        $item_code                   = array_key_exists("item_code", $options) ? $options["item_code"] : null;
        $mandatory                   = array_key_exists("mandatory", $options) ? $options["mandatory"] : null;
        $allow_default               = array_key_exists("allow_default", $options) ? $options["allow_default"] : null;
        $lock_rating_scale           = array_key_exists("lock_rating_scale", $options) ? $options["lock_rating_scale"] : false;
        $rating_scale_id             = array_key_exists("rating_scale_id", $options) ? $options["rating_scale_id"] : null;
        $rating_scale_title          = array_key_exists("rating_scale_title", $options) ? $options["rating_scale_title"] : null;
        $rating_scale_type_shortname = array_key_exists("rating_scale_type_shortname", $options) ? $options["rating_scale_type_shortname"] : null;
        $rating_scale_type_id        = array_key_exists("rating_scale_type_id", $options) ? $options["rating_scale_type_id"] : null;
        $rating_scale_type_title     = array_key_exists("rating_scale_type_title", $options) ? $options["rating_scale_type_title"] : null;
        $rating_scale_deleted        = array_key_exists("rating_scale_deleted", $options) ? $options["rating_scale_deleted"] : null;

        $can_have_comments = Entrada_Assessments_Forms::canHaveComments($itemtype_shortname) ? "": "hide";
        $can_have_default  = Entrada_Assessments_Forms::canHaveDefaultResponse($itemtype_shortname) ? "" : "hide";

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
                                <input class="span11" type="text" value="<?php echo $item_type->getName(); ?>" readonly="readonly" />
                                <input data-type-name="<?php echo $item_type->getShortname(); ?>" id="item-type" name="itemtype_id" type="hidden" value=<?php echo $itemtype_id; ?> />
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
        <?php
        $scale_search = new Views_Assessments_Forms_Controls_ScaleSelectorSearch();
        $scale_search->render(array(
            "width" => 350,
            "parent_selector" => "item-form",
            "search_selector" => "item-rating-scale-btn",
            "submodule" => "items",
            "readonly" => $lock_rating_scale,
            "scale_type_datasource" => $scale_type_datasource,
            "selected_target_id" => $rating_scale_id,
            "selected_target_label" => $rating_scale_title,
            "selected_target_type_id" => $rating_scale_type_id,
            "selected_target_type_label" => $rating_scale_type_title,
            "selected_target_type_shortname" => $rating_scale_type_shortname,
            "scale_deleted" => $rating_scale_deleted
        ));
        ?>
        <div class="control-group">
            <div class="controls">
                <label class="checkbox" for="item-mandatory">
                    <input type="checkbox" id="item-mandatory" name="item_mandatory" value="1" <?php echo $mandatory ? "checked=\"checked\"" : "" ?> <?php echo $disabled ?>><?php echo $translate->_("Make this item mandatory"); ?>
                </label>
            </div>
            <div class="controls comments-options <?php echo $can_have_comments ?>">
                <label class="checkbox" for="allow-comments">
                    <input type="checkbox" id="allow-comments" name="allow_comments" value="1" <?php echo $comment_type != "disabled" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Allow comments for this item"); ?>
                </label>
            </div>
            <div class="controls comments-options <?php echo $can_have_comments ?>" id="comments-type-section">
                <label class="radio" for="optional-comments">
                    <input type="radio" id="optional-comments" name="comment_type" value="optional" <?php echo $comment_type == "optional" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Comments are optional"); ?>
                </label>
                <label class="radio" for="mandatory-comments">
                    <input type="radio" id="mandatory-comments" name="comment_type" value="mandatory" <?php echo $comment_type == "mandatory" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Require comments for any response"); ?>
                </label>
                <label for="flagged-comments" class="radio">
                    <input type="radio" id="flagged-comments" name="comment_type" value="flagged" <?php echo $comment_type == "flagged" ? "checked=\"checked\"" : "" ?><?php echo $disabled ?>><?php echo $translate->_("Require comments for prompted responses") ?>
                </label>
            </div>
            <div class="controls default-response-options <?php echo $can_have_default; ?>">
                <label class="checkbox" for="allow-default">
                    <input type="checkbox" id="allow-default" name="allow_default" value="1" <?php echo $allow_default ? "checked=\"checked\"" : "" ?> <?php echo $disabled ?>><?php echo $translate->_("Allow for a default value"); ?>
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
