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
 * Generates a bootstrap alert
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_Alert extends Views_Gradebook_Base {
	protected $id, $class, $text, $close_button = true;

	protected function renderCloseButton() {
		if ($this->close_button) {
			return '<button type="button" class="close" data-dismiss="alert">&times;</button>';
		}
	}

	/**
	 * Generate the html for the search bar
	 * @param array $options
	 * @return string
	 */
	protected function renderView($options = array()) {
		$html = array();

		$html[] = '<div id="'.$this->getID().'" class="alert '.$this->getClass().'">';
		$html[] = '	'.$this->renderCloseButton();
		$html[] = '	'.$this->text;
		$html[] = '</div>';

		echo implode("\n", $html);
	}
}