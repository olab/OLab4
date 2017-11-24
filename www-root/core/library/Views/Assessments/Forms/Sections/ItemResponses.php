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
 * View class for rendering editable item responses on an item form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_ItemResponses extends Views_Assessments_Forms_Sections_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("itemtype_shortname"))) {
            return false;
        }
        if (!$this->validateArray($options, array("item_responses", "item_response_descriptors", "advanced_search_descriptor_datasource"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $item_responses                 = $options["item_responses"];
        $itemtype_shortname             = $options["itemtype_shortname"];
        $item_response_descriptors      = $options["item_response_descriptors"];
        $response_descriptor_datasource = $options["advanced_search_descriptor_datasource"];

        $show_grid_controls             = @$options["show_grid_controls"];
        $flag_response                  = @$options["flag_response"];
        $selected_descriptors           = @$options["selected_descriptors"] ? $options["selected_descriptors"] : array();
        $given_rubric_descriptors       = @$options["rubric_descriptors"];

        $disabled_text                  = @$options["disabled"] ? "disabled" : "";
        $use_readonly_override          = array_key_exists("readonly_override", $options);
        $readonly_override              = @$options["readonly_override"] ? $options["readonly_override"] : false;

        $readonly_referred_rubric_descriptors = false;
        if ($given_rubric_descriptors &&
            is_array($given_rubric_descriptors) &&
            !empty($given_rubric_descriptors)) {
            $readonly_referred_rubric_descriptors = true;
        }
        ?>
        <div <?php echo $this->getClassString(); ?> <?php echo $this->getIDString(); ?>>

            <h2><?php echo $translate->_("Item Responses"); ?></h2>

            <?php if ($show_grid_controls): ?>
                <div class="btn-group space-below pull-right" id="response-grid-controls">
                    <a href="#" class="btn add-response"><i class="icon-plus-sign"></i></a>
                    <a href="#" class="btn remove-response"><i class="icon-minus-sign"></i></a>
                </div>
            <?php endif; ?>

            <div id="item-removal-success-box" class="clear"></div>

            <table id="response-table" class="table table-striped table-bordered">
                <thead>
                    <th width="15%"></th>
                    <th width="45%"><?php echo $translate->_("Response Text"); ?></th>
                    <th width=""><?php echo $translate->_("Response Category"); ?></th>
                    <th width="5%"><?php echo $translate->_("Flag"); ?></th>
                    <?php if (!$readonly_override && !$readonly_referred_rubric_descriptors): ?>
                        <th width="5%"></th>
                        <th width="5%"></th>
                    <?php endif; ?>
                </thead>

                <tbody class="sortable-items">

                    <?php if (!$readonly_referred_rubric_descriptors): ?>

                        <?php $ordinal = 0; foreach ($item_responses as $response):
                            $ordinal++;
                            $selected_ardescriptor_id = $response["ardescriptor_id"];
                            $item_response_descriptor_id = null;
                            $item_response_descriptor_text = "";
                            // AdvancedSearch does not like arrays with named indexes, making sorting and preserving keys impossible. The datasource is not necessarily keyed by descriptor ID, so we have to search for it.
                            if ($search_source = Entrada_Utilities_AdvancedSearchHelper::getSearchItemByField($response_descriptor_datasource, "target_id", $selected_ardescriptor_id)) {
                                $item_response_descriptor_text = $search_source["target_label"];
                                $item_response_descriptor_id = $search_source["target_id"];
                            }
                            ?>
                            <tr class="response-row response-row-<?php echo $ordinal ?>" data-ordinal="<?php echo $ordinal?>">

                                <?php $this->renderResponseLabelTD($ordinal, $itemtype_shortname); ?>

                                <?php $this->renderResponseTextInputTD($ordinal, $response["text"], $disabled_text); ?>

                                <?php if ($readonly_override): // Read-only descriptor display ?>

                                    <?php $this->renderReadonlyDescriptorTD($ordinal, $item_response_descriptor_id, $item_response_descriptor_text); ?>
                                    <?php $this->renderFlagResponseTD($ordinal, @$flag_response[$ordinal], $disabled_text); ?>

                                <?php else: // Fully searchable descriptors ?>

                                    <?php $this->renderSearchableDescriptorTD($ordinal, $response_descriptor_datasource, $item_response_descriptor_id, $item_response_descriptor_text); ?>
                                    <?php $this->renderFlagResponseTD($ordinal, @$flag_response[$ordinal], $disabled_text); ?>
                                    <?php $this->renderItemOptionsTDs($ordinal); ?>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php else:

                        // Rubric descriptors are given to us via rubric referrer data.
                        // This happens when creating a new item and attaching it to a rubric; there is no existing item to display
                        // responses for, but descriptors must be taken into account when creating this item.

                        foreach ($given_rubric_descriptors as $key => $response_descriptor):
                            $key++; // start from 1, not 0
                            $label = "";
                            $descriptor_id = null;
                            if ($search_source = Entrada_Utilities_AdvancedSearchHelper::getSearchItemByField($response_descriptor_datasource, "target_id", $response_descriptor)) {
                                $label = $search_source["target_label"];
                                $descriptor_id = $search_source["target_id"];
                            }
                            $item_response_text = "";
                            // In this case, item responses are stored by iresponse_id, so we have to match their order to the "key" value (which is the order they appear in)
                            foreach ($item_responses as $response_data) {
                                if ($response_data["order"] == $key) {
                                    $item_response_text = $response_data["text"];
                                }
                            }
                            ?>
                            <tr class="response-row response-row-<?php echo $key ?>" data-ordinal="<?php echo $key?>">

                                <?php $this->renderResponseLabelTD($key, $itemtype_shortname); ?>

                                <?php $this->renderResponseTextInputTD($key, $item_response_text, $disabled_text); ?>

                                <?php if ($use_readonly_override && $readonly_override): // Read-only descriptor display ?>

                                    <?php $this->renderReadonlyDescriptorTD($key, $descriptor_id, $label); ?>
                                    <?php $this->renderFlagResponseTD($key, @$flag_response[$key], $disabled_text); ?>

                                <?php else: // Fully searchable descriptors ?>

                                    <?php $this->renderSearchableDescriptorTD($key, $response_descriptor_datasource, $descriptor_id, $label); ?>
                                    <?php $this->renderFlagResponseTD($key, @$flag_response[$key], $disabled_text); ?>
                                    <?php $this->renderItemOptionsTDs($key); ?>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>        
        <?php
    }

    private function renderItemOptionsTDs($ordinal) {
        ?>
        <td class="delete-item-response" data-related-response-ordinal="<?php echo $ordinal ?>">
            <i class="icon-trash"></i>
        </td>
        <td class="move-item-response">
            <a href="#"><i class="icon-move"></i></a>
        </td>
        <?php
    }

    private function renderFlagResponseTD($ordinal, $flag_response, $disabled_text) {
        ?>
        <td>
            <input type="checkbox" name="flag_response[<?php echo $ordinal ?>]" value="1" <?php echo $flag_response ? "checked='checked'" : "" ?> <?php echo $disabled_text ?> />
        </td>
        <?php
    }

    private function renderSearchableDescriptorTD($ordinal, $response_descriptor_datasource, $item_response_descriptor_id, $item_response_descriptor_text) {
        global $translate;
        ?>
        <td>
            <input type="hidden"
                   name="<?php echo "selected_ardescriptor_ids[$ordinal]"; ?>"
                   value="<?php echo $item_response_descriptor_id ?>"/>

            <input type="hidden"
                   name="<?php echo "ardescriptor_id[$ordinal]"; ?>"
                   value="<?php echo $item_response_descriptor_id; ?>"
                   id="<?php echo "response_category_{$ordinal}_{$item_response_descriptor_id}"; ?>"
                   data-label="<?php echo $item_response_descriptor_text; ?>"
                   class="<?php echo "search-target-control response_category_{$ordinal}_search_target_control descriptor-{$ordinal}" ?>">

            <button id="<?php echo "descriptor-$ordinal"; ?>" name="<?php echo "ardescriptor_id[$ordinal]"; ?>" class="btn text-left">
                <?php
                if ($item_response_descriptor_text != "") {
                    echo $item_response_descriptor_text;
                } else {
                    echo $translate->_("Browse Descriptors");
                }
                ?>
                <i class="icon-chevron-down btn-icon pull-right"></i>
            </button>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $("<?php echo "#descriptor-$ordinal"; ?>").advancedSearch({
                        filters: {},
                        control_class: "<?php echo "descriptor-$ordinal"; ?>",
                        no_results_text: "",
                        parent_form: $("#item-form"),
                        width: 275,
                        modal: false
                    });
                    var descriptor_settings = $("<?php echo "#descriptor-$ordinal"; ?>").data("settings");
                    descriptor_settings.filters["<?php echo "response_category_$ordinal"; ?>"] = {
                        label: "",
                        data_source: <?php echo json_encode($response_descriptor_datasource); ?>,
                        mode: "radio",
                        selector_control_name: "<?php echo "ardescriptor_id[$ordinal]"; ?>",
                        search_mode: true
                    }
                });
            </script>
        </td>
        <?php
    }

    private function renderResponseTextInputTD($ordinal, $response_text, $disabled_text) {
        ?>
        <td>
            <textarea class="expandable response-input <?php echo $disabled_text ?>" id="item_response_<?php echo $ordinal ?>" name="item_responses[<?php echo $ordinal ?>]" <?php echo $disabled_text ?>><?php echo $response_text; ?></textarea>
        </td>
        <?php
    }

    private function renderResponseLabelTD($ordinal, $itemtype_shortname) {
        global $translate;
        ?>
        <td>
            <label for="item_response_<?php echo $ordinal ?>" class="item-response-label <?php echo $itemtype_shortname == "rubric_line" ? "form-nrequired" : "form-required"; ?>">
                <?php echo sprintf($translate->_("Response <span>%s</span>"),  $ordinal); ?>
            </label>
        </td>
        <?php
    }

    private function renderReadonlyDescriptorTD($ordinal, $item_response_descriptor_id, $item_response_descriptor_text) {
        global $translate;
        ?>
        <td>
            <a href="#" class="btn disabled full-width no-padding-right no-padding-left">
                <?php echo $item_response_descriptor_text ? $item_response_descriptor_text : $translate->_("No Descriptor Set"); ?>
            </a>

            <input type="hidden" name="<?php echo "ardescriptor_id[$ordinal]"; ?>" value="<?php echo $item_response_descriptor_id; ?>"/>
            <input type="hidden" name="<?php echo "selected_ardescriptor_ids[$ordinal]"; ?>" value="<?php echo $item_response_descriptor_id; ?>"/>

        </td>
        <?php
    }

}