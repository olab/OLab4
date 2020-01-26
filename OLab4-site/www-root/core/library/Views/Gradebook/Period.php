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
 * Generates a Gradebook curriculum period selector 
 * 
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_Period extends Views_Gradebook_PeriodSelector {
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

		// render
		$html[] = '<div id="'.$this->getID().'" class="'.$this->class.'">';
		$html[] = $this->getCurriculumPeriodEntry($this->curriculum_periods);
		$html[] = '	</div>';

		echo implode("\n", $html);
	}
}