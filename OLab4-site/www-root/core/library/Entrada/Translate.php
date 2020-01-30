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
 * @author Organisation: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Entrada_Translate extends Zend_Translate {

    /**
     * Used to support translations within Smarty template. Just wrap {translate}{/translate}
     * around any language file options.
     * @param array $params
     * @param string $string
     * @param $smarty
     * @param $repeat
     * @return mixed
     */
    public function smarty($params = array(), $string = "", $smarty, &$repeat) {
        return $this->_($string);
    }
}