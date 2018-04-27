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
 * A view for rendering the CBME filter lists
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_List extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("filter_list_data", "filter_label", "filter_type"));
    }

    /**
     * Render the view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="clearfix"></div>
        <div class="filter-wrap">
            <p class="list-set-item-label"><?php echo html_encode($options["filter_label"]) ?></p>
            <ul class="list-set">
                <?php foreach ($options["filter_list_data"] as $filter) : ?>
                <li class="list-set-item display-block" data-filter-control="<?php echo html_encode($filter["data_filter_control"]) ?>">
                    <div class="list-set-item-cell full-width">
                        <span class="list-set-item-title"><?php echo html_encode($filter["title"]) ?></span>
                        <?php if ($filter["description"]) : ?>
                            <div class="list-set-item-description"><?php echo html_encode($filter["description"]) ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="#" class="list-set-item-cell remove-filter">
                        <span class="fa fa-close"></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render filter list."); ?></strong>
        </div>
        <?php
    }
}