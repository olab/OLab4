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
        if (!$this->validateIsSet($options, array("itemtype_shortname", "use_custom_flags"))) {
            return false;
        }
        if (!$this->validateArray($options, array("item_responses", "advanced_search_descriptor_datasource"))) {
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
        $response_descriptor_datasource = $options["advanced_search_descriptor_datasource"];

        $show_grid_controls             = array_key_exists("show_grid_controls", $options) ? $options["show_grid_controls"] : false;
        $flag_response                  = array_key_exists("flag_response", $options) ? $options["flag_response"] : null;
        $default_response               = array_key_exists("default_response", $options) ? $options["default_response"] : null;
        $given_rubric_descriptors       = array_key_exists("rubric_descriptors", $options) ? $options["rubric_descriptors"] : null;

        $disabled_text                  = array_key_exists("disabled", $options) ? $options["disabled"] ? "disabled" : "" : "";
        $use_readonly_override          = array_key_exists("readonly_override", $options);
        $readonly_override              = @$options["readonly_override"] ? $options["readonly_override"] : false;
        $allow_default                  = array_key_exists("allow_default", $options) ? $options["allow_default"] : false;
        $use_custom_flags               = $options["use_custom_flags"];
        $flags_datasource               = array_key_exists("flags_datasource", $options) ? $options["flags_datasource"] : array();

        $readonly_referred_rubric_descriptors = false;
        if ($given_rubric_descriptors &&
            is_array($given_rubric_descriptors) &&
            !empty($given_rubric_descriptors)) {
            $readonly_referred_rubric_descriptors = true;
        }

        // If we've been given both item response and pre-set descriptors, use the already set item responses' descriptors.
        if (!empty($given_rubric_descriptors) && !empty($item_responses)) {
            $given_rubric_descriptors = array_map(function($i) { return $i["ardescriptor_id"]; }, $item_responses);
        }

        $hide_flex_column = "hide";
        if (!$readonly_override && !$readonly_referred_rubric_descriptors) {
            $hide_flex_column = "";
        }

        $hide_default_column = " hide";
        if ($allow_default) {
            $hide_default_column = "";
        }
        ?>
        <div <?php echo $this->getClassString(); ?> <?php echo $this->getIDString(); ?>>

            <h2><?php echo $translate->_("Item Responses"); ?></h2>

            <div class="btn-group space-below pull-right" id="response-grid-controls">
                <a href="#" class="btn add-response"<?php echo (!$show_grid_controls) ? " style=\"display: none;\"" : ""; ?>><i class="icon-plus-sign"></i></a>
                <a href="#" class="btn remove-response"<?php echo (!$show_grid_controls) ? " style=\"display: none;\"" : ""; ?>><i class="icon-minus-sign"></i></a>
            </div>

            <div id="item-removal-success-box" class="clear"></div>

            <table id="response-table" class="table table-striped table-bordered">
                <thead>
                    <th width="15%"></th>
                    <th width="45%"><?php echo $translate->_("Response Text"); ?></th>
                    <th width=""><?php echo $translate->_("Response Category"); ?></th>
                    <th width="5%"><?php echo $translate->_("Prompt"); ?></th>
                    <th width="5%" class="default_selection_column <?php echo $hide_default_column; ?>"><?php echo $translate->_("Default"); ?></th>
                    <th width="5%" class="items-header-flex-column <?php echo $hide_flex_column ?>"></th>
                    <th width="5%" class="items-header-flex-column <?php echo $hide_flex_column ?>"></th>
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
                                    <?php $this->renderFlagResponseTD($ordinal, @$flag_response[$ordinal], $disabled_text, $use_custom_flags, $flags_datasource); ?>
                                    <?php $this->renderDefaultSelectionTD($allow_default, $ordinal, $default_response, $disabled_text); ?>

                                <?php else: // Fully searchable descriptors ?>

                                    <?php $this->renderSearchableDescriptorTD($ordinal, $response_descriptor_datasource, $item_response_descriptor_id, $item_response_descriptor_text); ?>
                                    <?php $this->renderFlagResponseTD($ordinal, @$flag_response[$ordinal], $disabled_text, $use_custom_flags, $flags_datasource); ?>
                                    <?php $this->renderDefaultSelectionTD($allow_default, $ordinal, $default_response, $disabled_text); ?>
                                    <?php $this->renderItemOptionsTDs($ordinal); ?>

                                <?php endif; ?>
                            </tr>

                        <?php endforeach; ?>

                    <?php else:

                        // Rubric descriptors are given to us via rubric referrer data.
                        // This happens when creating a new item and attaching it to a rubric; there is no existing item to display
                        // responses for, but descriptors must be taken into account when creating this item.
                        $key = 0;
                        foreach ($given_rubric_descriptors as $response_descriptor):
                            $key++; // starting from 1, not 0
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
                                    <?php $this->renderFlagResponseTD($key, @$flag_response[$key], $disabled_text, $use_custom_flags, $flags_datasource); ?>
                                    <?php $this->renderDefaultSelectionTD($allow_default, $key, $default_response, $disabled_text); ?>

                                <?php else: // Fully searchable descriptors ?>

                                    <?php $this->renderSearchableDescriptorTD($key, $response_descriptor_datasource, $descriptor_id, $label); ?>
                                    <?php $this->renderFlagResponseTD($key, @$flag_response[$key], $disabled_text, $use_custom_flags, $flags_datasource); ?>
                                    <?php $this->renderDefaultSelectionTD($allow_default, $key, $default_response, $disabled_text); ?>
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

    private function renderFlagResponseTD($ordinal, $flag_response, $disabled_text, $custom_flags, $flags) {
        global $translate; ?>
        <td>
            <?php if ($custom_flags) {
                $this->renderAdvancedSearchFlagSelector($ordinal, $flag_response, $disabled_text, $flags);
            } else {
                $this->renderCheckboxFlagSelector($ordinal, $flag_response, $disabled_text);
            }?>
        </td>
        <?php
    }

    private function renderAdvancedSearchFlagSelector($ordinal, $flag_response, $disabled_text, $flags) {
        global $translate;
        $selected = "";
        foreach ($flags as $flag) {
            if ($flag["target_id"] == $flag_response) {
                $selected = $flag["target_label"];
            }
        }
        if ($flag_response > 0 && !$selected) {
            // The flag_response value does not correspond to a flag in the system.
            // So we denote that the flag is actually set, but we don't populate the advancedSearch as to not overwrite the existing value.
            $selected = $translate->_("Flagged");
        }
        ?>
        <button id="flag-<?php echo $ordinal; ?>" class="btn btn-search-filter text-left" <?php echo $disabled_text; ?>>
            <?php echo $selected ? $selected : $translate->_("Not Flagged"); ?><i class="icon-chevron-down btn-icon pull-right"></i>
        </button>
        <script language="JavaScript">
            jQuery(document).ready(function ($) {
                $("#flag-<?php echo $ordinal;?>").advancedSearch({
                    filters: {},
                    control_class: "flag-<?php echo $ordinal;?>",
                    no_results_text: "",
                    parent_form: $("#item-form"),
                    width: 275,
                    modal: false
                });
                // We must declare the filter after the object has been created to allow us to use a dynamic key with a variable in the name.
                var descriptor_settings = jQuery("#flag-<?php echo $ordinal; ?>").data("settings");
                if (descriptor_settings) {
                    descriptor_settings.filters["flag_response_<?php echo $ordinal; ?>"] = {
                        label: "<?php echo $translate->_("Flags") ?>",
                        data_source: <?php echo json_encode($flags) ?>,
                        mode: "radio",
                        selector_control_name: "flag_response[<?php echo $ordinal; ?>]",
                        search_mode: false
                    }
                }
            })
        </script>
        <input type="hidden"
               name="flag_response[<?php echo $ordinal; ?>]"
               value="<?php echo $flag_response; ?>"
               id="flag_response_<?php echo $ordinal; ?>_<?php echo $flag_response; ?>"
               data-label="<?php echo $selected; ?>"
               class="search-target-control flag_response_<?php echo $ordinal; ?> search_target_control flag-<?php echo $ordinal; ?>">
        <?php
    }

    private function renderCheckboxFlagSelector($ordinal, $flag_response, $disabled_text) {
        ?>
        <input type="checkbox" name="flag_response[<?php echo $ordinal ?>]" value="1" <?php echo $flag_response ? "checked='checked'" : "" ?> <?php echo $disabled_text ?> />
        <?php
    }

    private function renderDefaultSelectionTD($allow_default, $ordinal, $default_response, $disabled_text) {
        $hide_tag = $allow_default ? "" : " hide";
        ?>
        <td class="default_selection_column <?php echo $hide_tag; ?>">
            <input id="default-response-<?php echo $ordinal; ?>" type="radio" name="default_response" value="<?php echo $ordinal ?>" <?php echo ($default_response == $ordinal) ? "checked='checked'" : "" ?> <?php echo $disabled_text ?> />
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
                    $("<?php echo "#descriptor-{$ordinal}"; ?>").advancedSearch({
                        filters: {},
                        control_class: "<?php echo "descriptor-{$ordinal}"; ?>",
                        no_results_text: "",
                        parent_form: $("#item-form"),
                        width: 275,
                        modal: false
                    });
                    var descriptor_settings = $("<?php echo "#descriptor-{$ordinal}"; ?>").data("settings");
                    descriptor_settings.filters["<?php echo "response_category_{$ordinal}"; ?>"] = {
                        label: "",
                        data_source: <?php echo json_encode($response_descriptor_datasource); ?>,
                        mode: "radio",
                        selector_control_name: "<?php echo "ardescriptor_id[{$ordinal}]"; ?>",
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