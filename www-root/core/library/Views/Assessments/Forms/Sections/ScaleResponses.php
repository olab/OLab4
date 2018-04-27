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

class Views_Assessments_Forms_Sections_ScaleResponses extends Views_Assessments_Forms_Sections_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("descriptors", "response_descriptors"));
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $response_descriptors   = $options["response_descriptors"]; // the selected descriptors
        $descriptors            = $options["descriptors"]; // All descriptors, advanced search format
        $readonly_override      = @$options["readonly_override"] ? $options["readonly_override"] : false;

        if ($readonly_override) {
            $show_grid_controls = false;
        } else {
            $show_grid_controls = true;
        }
        ?>
        <div <?php echo $this->getClassString(); ?> <?php echo $this->getIDString(); ?>>

            <h2><?php echo $translate->_("Responses Categories"); ?></h2>

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
                    <th width=""><?php echo $translate->_("Response Category"); ?></th>
                </thead>
                <tbody>
                    <?php $ordinal = 0; foreach ($response_descriptors as $key => $response_descriptor_id): $ordinal++; ?>
                        <tr class="response-row">
                            <?php
                                $this->renderResponseLabelTD($ordinal);
                                if ($readonly_override) {
                                    $this->renderReadonlyDescriptorTD($ordinal, $response_descriptor_id, $descriptors);
                                } else {
                                    $this->renderSearchableDescriptorTD($ordinal, $response_descriptor_id, $descriptors);
                                }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>        
        <?php
    }

    private function renderSearchableDescriptorTD($ordinal, $response_descriptor_id, $all_descriptors) {
        global $translate;
        $target_id = 0;
        $target_label = $translate->_("Browse Descriptors");
        if ($search_source_item = Entrada_Utilities_AdvancedSearchHelper::getSearchItemByField($all_descriptors, "target_id", $response_descriptor_id)) {
            $target_label = $search_source_item["target_label"];
            $target_id = $search_source_item["target_id"];
        }
        ?>
        <td>
            <input type="hidden"
                   value="<?php echo $target_id; ?>"
                   name="selected_ardescriptor_ids[<?php echo ($ordinal); ?>]"
                   id="response_category_<?php echo ($ordinal); ?>_<?php echo $target_id; ?>"
                   class="search-target-control response_category_<?php echo ($ordinal); ?>_search_target_control descriptor-<?php echo ($ordinal); ?>"
                   data-label="<?php echo $target_label; ?>"
            />
            <button type="text" id="descriptor-<?php echo ($ordinal); ?>" name="ardescriptor_id[<?php echo ($ordinal); ?>]" class="btn">
                <?php echo $target_label ?>
                <i class="icon-chevron-down btn-icon pull-right"></i>
            </button>
            <script type="text/javascript">
                jQuery("#descriptor-<?php echo ($ordinal); ?>").advancedSearch({
                    filters: { },
                    control_class: "descriptor-<?php echo ($ordinal); ?>",
                    no_results_text: "",
                    parent_form: jQuery("#item-form"),
                    width: 275,
                    modal: false
                });
                var descriptor_settings = jQuery("#descriptor-<?php echo ($ordinal) ?>").data("settings");
                descriptor_settings.filters["response_category_<?php echo ($ordinal) ?>"] = {
                    label : "",
                    data_source : <?php echo json_encode($all_descriptors); ?>,
                    mode: "radio",
                    selector_control_name : "selected_ardescriptor_ids[<?php echo ($ordinal) ?>]",
                    search_mode: true
                }
            </script>

        </td>
        <?php
    }

    private function renderResponseLabelTD($ordinal) {
        global $translate;
        ?>
        <td class="text-center">
            <label for="item_response_<?php echo $ordinal ?>" class="item-response-label form-required">
                <?php echo sprintf($translate->_("Response <span>%s</span>"),  $ordinal); ?>
            </label>
        </td>
        <?php
    }

    private function renderReadonlyDescriptorTD($ordinal, $response_descriptor_id, $all_descriptors) {
        global $translate;
        $target_id = 0;
        $target_label = $translate->_("No Descriptor Set");
        if ($search_source_item = Entrada_Utilities_AdvancedSearchHelper::getSearchItemByField($all_descriptors, "target_id", $response_descriptor_id)) {
            $target_label = $search_source_item["target_label"];
            $target_id = $search_source_item["target_id"];
        }
        ?>
        <td>
            <a href="#" class="btn disabled full-width no-padding-right no-padding-left">
                <?php echo $target_label; ?>
            </a>
            <input type="hidden" name="<?php echo "ardescriptor_id[$ordinal]"; ?>" value="<?php echo $target_id; ?>"/>
        </td>
        <?php
    }

}