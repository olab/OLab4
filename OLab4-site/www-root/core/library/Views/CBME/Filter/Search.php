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
 * A view for rendering the CBME search filter
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_Search extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("epas", "canmed_roles"));
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <script type="text/javascript">
            jQuery(function($) {
                $("#select-epa-btn").advancedSearch({
                    filters : {
                        epa : {
                            label : "<?php echo $translate->_("EPAs"); ?>",
                            data_source : <?php echo json_encode($options["epas"]); ?>,
                            selector_control_name: "course_epa",
                            search_mode: false
                        }
                    },
                    control_class: "course-epa-selector",
                    no_results_text: "<?php echo $translate->_("No EPAs found matching the search criteria."); ?>",
                    width: 300,
                    parent_form: $("#epa-filter-div"),
                    modal: false,
                    list_selections: false
                });

                $("#select-canmed-roles-btn").advancedSearch({
                    filters: {
                        role: {
                            label: "<?php echo $translate->_("CanMEDS Roles"); ?>",
                            data_source: <?php echo json_encode($options["canmed_roles"]); ?>,
                            selector_control_name: "canmed_role",
                            search_mode: false
                        }
                    },
                    control_class: "canmed-role-selector",
                    no_results_text: "<?php echo $translate->_("No CanMEDS Roles found matching the search criteria."); ?>",
                    width: 300,
                    parent_form: $("#canmed-roles-filter-div"),
                    modal: false,
                    list_selections: false
                });
            });
        </script>
        <div class="clearfix"></div>
        <div>
            <input class="search-icon" type="text" placeholder="<?php echo $translate->_("Search for Items..."); ?>">
            <div id="epa-filter-div" class="space-below">
                <a href="#" id="select-epa-btn" class="btn" type="button"><?php echo $translate->_("Select EPAs"); ?> <i class="icon-chevron-down"></i></a>
            </div>
            <div id="canmed-roles-filter-div">
                <a href="#" id="select-canmed-roles-btn" class="btn" type="button"><?php echo $translate->_("Select CanMEDS Roles"); ?> <i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <?php
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render filter list."); ?></strong>
        </div>
        <?php
    }
}