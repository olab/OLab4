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
 * Generates a simple <select>
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_Select extends Views_Gradebook_Base {
	protected $id, $class, $label, $data_attr = array(), $label_class = "control-label content-small", $name, $options = array();

	/**
	 * Renders an associative array into a string of data-attributes
	 * @return string html
	 */
	protected function renderDataAttr() {
		if ($this->data_attr) {
			$html = array();

			foreach($this->data_attr as $key => $attr) {
				$html[] = 'data-' . $key . '="'.$attr.'"';
			}

			// implode with spaces between 
			return implode(" ", $html);
		}
	}

	/**
	 * Generate html
	 * @param array $options
	 * @return string Html string
	 */
	protected function renderView($options = array()) {
		$html = array();
		$id = "";

		if ($this->getID()) {
			$id = 'selector-'.$this->getId();
		}

		// Render select

		$html[] = '<label for="'.$id.'" class="'.$this->label_class.'">'.$this->label.'</label>';
		$html[] = '<select id="'.$id.'" name="'.$this->name.'" class="'.$this->getClass().'" '.$this->renderDataAttr().'>';

		foreach($this->options as $option) {
			$html[] = '<option value="'.$option["value"].'" class="'.$option["class"].'" id="'.$option["id"].'">';
			$html[] = $option["text"];
			$html[] = '</option>';
		}

		$html[] = '</select>';

		echo implode("\n", $html);
	}
}