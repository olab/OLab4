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
 * Generates a search bar
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_SearchBar extends Views_Gradebook_Base {
	protected $id, $class, $placeholder;

	/**
	 * Get placeholder text
	 * @return string 
	 */
	public function getPlaceholder() {
		return $this->placeholder;
	}

	/**
	 * Set placeholder text that will appear when the form input is empty
	 * @param string $placeholder "Search assessments"
	 */
	public function setPlaceholder($placeholder) {
		$this->placeholder = $placeholder;
	}

	/**
	 * Generate the html for the search bar
	 * @param array $options
	 * @return string
	 */
	protected function renderView($options = array()) {
		$html = array();

		$html[] = '<form id="'.$this->getID().'" class="'.$this->getClass().'">';
		$html[] = '	<div class="control-group">';
		$html[] = '		<div class="controls">';
		$html[] = '			<input type="text" id="input-'.$this->getID().'" placeholder="'.$this->getPlaceholder().'" autocomplete="off">';
		$html[] = '		</div>';
		$html[] = '	</div>';
		$html[] = '</form>';

		echo implode("\n", $html);
	}
}