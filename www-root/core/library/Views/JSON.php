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
 * Base view class for rendering JSON output.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_JSON extends Views_Base {

    /**
     * JSON-based renderView. Can be overridden by child class to produce more relevant JSON.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        echo json_encode($options);
    }

    /**
     * Render a generic error message. Should be overridden by child class to produce more relevant JSON.
     */
    protected function renderError() {
        global $translate;
        echo json_encode(
            array(
                "error" => true,
                "error_message" => $translate->_("Undefined error")
            )
        );
    }

}