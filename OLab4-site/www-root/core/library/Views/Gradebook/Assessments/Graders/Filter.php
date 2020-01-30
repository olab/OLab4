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
 * Renders an assessment form
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Gradebook_Assessments_Graders_Filter extends Views_Gradebook_Base {
    protected $id, $class, $label, $name, $graders, $selected_grader;

    public function getID() {
        return $this->id;
    }

    public function getClass() {
        return $this->class;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getName() {
        return $this->name;
    }

    public function getGraders() {
        return $this->graders;
    }

    protected function renderView($options = array()) {
        global $translate;

        $html = array();
        $id = "";

        if ($this->getID()) {
            $id = 'selector-'.$this->getId();
        }

        // get selected grader from session
        $session_grader_id = Entrada_Utilities::getSessionParam('filter.grader');

        // render form
        $html[] = '<form id="'.$this->getID().'" class="'.$this->getClass().'" method="post">';
        $html[] = '	<label for="'.$id.'" class="control-label content-small">'.$this->getLabel().'</label>';
        $html[] = '	<div class="controls">';
        $html[] = '		<select id="'.$id.'" name="'.$this->getName().'">';

        $html[] = '<option value="">'.$translate->_("Browse Graders").'</option>';

        foreach($this->graders as $grader) {
            $selected = ($this->selected_grader == $grader->getID()) ? 'selected="selected"' : '';

            $html[] = '<option value="'.$grader->getID().'" '.$selected.'>';
            $html[] = $grader->getFullname();
            $html[] = '</option>';
        }

        $html[] = '		</select>';
        $html[] = '	</div>';
        $html[] = '</form>';

        echo implode("\n", $html);
    }
}