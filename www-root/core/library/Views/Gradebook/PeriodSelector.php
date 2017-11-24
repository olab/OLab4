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
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_PeriodSelector extends Views_Gradebook_Base {
	protected $id, $class, $label, $course, $curriculum_periods, $selected_curriculum_period;

	/**
	 * Get label text
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Set label text
	 * @param string $label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 * Renders the proper date range for a given curriculum period
	 * @param  object $curriculum_period 	Curriculum period object
	 * @return string                    	Html string
	 */
	public function getCurriculumPeriodEntry($curriculum_period) {
		$html = array();

		if ($curriculum_period->getCurriculumPeriodTitle()) {
			$html[] = html_encode($curriculum_period->getCurriculumPeriodTitle()) . " - ";
		}

		$html[] = $curriculum_period->getDateRangeString();

		return implode("\n", $html);
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

		// render form
		$html[] = '<form id="'.$this->getID().'" class="'.$this->class.'">';
		$html[] = '	<label for="'.$this->getID().'" class="control-label content-small">'.$this->getLabel().'</label>';
		$html[] = '	<div class="controls">';
		$html[] = '		<select id="'.$id.'" name="cperiod_select">';

		foreach($this->curriculum_periods as $period) {
            // marked option as selected if it matches variable selected_curriculum_period
            $selected = $this->selected_curriculum_period == $period->getID() ? 'selected="selected"' : '';

            $html[] = '<option value="' . $period->getID() . '" ' . $selected . '>';
            $html[] = $this->getCurriculumPeriodEntry($period);
            $html[] = '</option>';
        }

		$html[] = '		</select>';
		$html[] = '	</div>';
		$html[] = '</form>';

		echo implode("\n", $html);
	}
}