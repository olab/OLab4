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
 * This class tests the functions in Models_Organisation.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

$id = html_encode($this->id);
$filter_type = html_encode(str_replace("-", "_", $this->id));

?>
<style type="text/css">
    <?php echo "#{$this->container_id}"; ?> li[data-parent="0"] input[type="checkbox"] {
        display: none;
    }
</style>

<div id="<?php echo $this->container_id; ?>">
    <?php if (!empty($this->title)): ?>
        <h2 class="collapsible collapsed list-heading"><?php echo $this->translate->_($this->title); ?></h2>
    <?php endif; ?>
    <div class="control-group">
        <label for="<?php echo $filter_type; ?>_button" class="control-label" style="text-align: left"><?php echo $this->translate->_($this->filter_label); ?>:</label>
        <div class="controls">
            <button id="<?php echo $filter_type; ?>_button" type="button" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><span id="<?php echo $filter_type; ?>_button_text"><?php echo $this->translate->_("Select " . $this->filter_label); ?></span><i class="icon-chevron-down btn-icon pull-right"></i></button>
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <div id="<?php echo $id; ?>-individual-container">
            </div>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(function() {
            var allowed_tag_set_ids = <?php echo json_encode(isset($this->allowed_tag_set_ids) ? array_values($this->allowed_tag_set_ids) : array()); ?>;
            var exclude_tag_set_ids = <?php echo json_encode(isset($this->exclude_tag_set_ids) ? array_values($this->exclude_tag_set_ids) : array()); ?>;
            var query_str = jQuery.param({
                'show_codes': 1,
                'allowed_tag_set_ids': allowed_tag_set_ids ? allowed_tag_set_ids : [],
                'exclude_tag_set_ids': exclude_tag_set_ids ? exclude_tag_set_ids : []
            });
            var id = '<?php echo $id; ?>';
            var filter_type = '<?php echo $filter_type; ?>';
            var advanced_search_settings = {
                resource_url: '<?php echo ENTRADA_URL; ?>',
                api_url: '<?php echo ENTRADA_URL; ?>/api/curriculum-tags.api.php?' + query_str,
                filters: {},
                list_data: {
                    selector: '#' + id + '-individual-container'
                },
                control_class: id + '-selector',
                no_results_text: '<?php echo $this->translate->_("No " . $this->filter_label . " found matching the search criteria"); ?>',
                parent_form: jQuery('#<?php echo $this->form_id; ?>'),
                width: 400
            };
            advanced_search_settings.filters[filter_type] = {
                label: '<?php echo $this->translate->_($this->filter_label); ?>',
                data_source: 'get-objectives',
                secondary_data_source: 'get-objectives',
                mode: 'checkbox',
                set_button_text_to_selected_option: true
            };
            jQuery('#' + filter_type + '_button').advancedSearch(advanced_search_settings);
            var objectives = <?php echo json_encode(array_map(function ($objective) {
                    return array(
                        "target_id" => html_encode($objective->getID()),
                        "target_parent" => html_encode($objective->getParent()),
                        "target_name" => html_encode($objective->getObjectiveText(true)),
                    );
                }, $this->objectives)); ?>;
            if (!Array.isArray(objectives)) {
                for (var objective_id in objectives) {
                    var value = objectives[objective_id];
                    var element_id = filter_type + '_' + value.target_id;
                    jQuery('#' + element_id).remove();
                    jQuery(document.createElement('input')).attr({
                        'type'        : 'hidden',
                        'class'       : 'search-target-control ' + filter_type + '_search_target_control',
                        'name'        : filter_type + '[]',
                        'id'          : element_id,
                        'value'       : value.target_id,
                        'data-id'     : value.target_id,
                        'data-filter' : filter_type,
                        'data-label'  : value.target_name
                    }).appendTo('#<?php echo $this->form_id; ?>');
                }
            }
            jQuery('#' + filter_type + '_button').data('settings').build_list();
        });
    </script>
</div>
