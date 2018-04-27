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
 * This view is used in sidebars and headers, to display assessment
 * related delivery information.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Templates_Comment extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <script type="text/html" id="comment-template">
            <a class="list-card-btn pin-comment comment-pin pull-left">
                <span data-pin-type="comment" title="<?php echo $translate->_("Pin Comment") ?>" data-template-bind='[{"attribute": "data-dassessment-id", "value": "dassessment_id"}, {"attribute": "data-id", "value": "epresponse_id"}, {"attribute": "data-pin-id", "value": "pin_id"}, {"attribute": "data-aprogress-id", "value": "aprogress_id"}]' class="list-card-icon fa fa-thumb-tack pin-assessment pin" data-class="pinned_class"></span>
            </a>
            <div class="assessment-comment pull-right">
                <span class="list-card-label" data-content="item_text"></span>
                <p class="comment" data-content="comment_response"></p>
            </div>
        </script>
        <?php
    }
}
