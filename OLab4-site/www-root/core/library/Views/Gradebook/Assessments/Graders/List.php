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
class Views_Gradebook_Assessments_Graders_List extends Views_Gradebook_Base {
    protected $assessment_id, $graders;

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function setAssessmentID($assessment_id) {
        $this->assessment_id = $assessment_id;
    }

    public function getGraders() {
        return $this->graders;
    }

    public function setGraders($graders) {
        $this->graders = $graders;
    }

    protected function renderView($options = array()) {
        global $translate;

        $contact_roles = array(
            "director" => $translate->_("Course Directors"),
            "ccoordinator" => $translate->_("Curriculum Coordinators"),
            "faculty" => $translate->_("Associated Faculty"),
            "pcoordinator" => $translate->_("Program Coordinator"),
            "evaluationrep" => $translate->_("Evaluation Rep"),
            "studentrep" => $translate->_("Student Rep"),
            "ta" => $translate->_("Teaching Assistant")
        );

        if (!isset($this->graders) || !is_array($this->graders)) {
            return;
        }

        $html = array();
        $html[] = '<table id="graders-list-table">';
        $html[] = '    <tbody>';
        
        foreach ($this->graders as $grader) {
            $html[] = '    <tr>';
            $html[] = '        <td><i class="icon-user"></i></td>';
            $html[] = '        <td>'.$grader->getFullname().'</td>';
            if ($grader->role) {
                $html[] = '        <td><a class="btn btn-small">' . $contact_roles[$grader->role] . '</a></td>';
            } else {
                $html[] = '        <td>&nbsp;</td>';
            }
            $html[] = '        <td><a href="#modal-remove-grader-from-list" class="remove_grader" data-toggle="modal" data-name="'.$grader->getFullname().'" data-id="'.$grader->getID().'"><img src="/images/action-delete.gif"></a>';
            $html[] = '        </td>';
            $html[] = '    </tr>';
        }
        
        $html[] = '    </tbody>';
        $html[] = '</table>';

        echo implode("\n", $html);
    }
}