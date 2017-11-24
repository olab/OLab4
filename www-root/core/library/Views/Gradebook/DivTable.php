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
 * @author Organization: Queens University.
 * @author Developer: Steve Yang <sy49@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_DivTable extends Views_Gradebook_Base {
	protected $id, $class, $assessments, $group_id = null, $search = null;
    
	/**
	 * Renders an associative array into a string of
	 * @return string html
	 */
	private function link($section, $assessment) {
		global $COURSE_ID;
		return '<a href="' . ENTRADA_URL . '/admin/gradebook/assessments?section=' . $section . '&id=' . $COURSE_ID .'&assessment_id=' . $assessment['assessment_id'] . '">';
	}
	/**
	 * Renders an associative array into a string of 
	 * @return string html
	 */
	private function renderGroupOpen($assessment) {
		$html = array();
		$html[] = '<div class="row group" data-collection-id="' . $assessment['collection_id'] .'" data-collection-title="' . $assessment['title'] . '" data-collection-description="' . $assessment['desc'] . '">';
		$html[] = '<div class="group-header">';
		$html[] = '    <div class="assessment-cell">';
    	$html[] = '        <div class="assessment-cell-table">';
		$html[] = '            <div class="icn-cell"><span class="fa fa-grip fa-grip-row"></span></div>';
		$html[] = '            <div class="icn-cell"><input class="group-checkbox-assessment" name="collections[]" value="' . $assessment['collection_id'] . '" type="checkbox"></div>';
		$html[] = '            <div class="collection-name">' . $assessment['title'] . '</div>';
		$html[] = '            <div class="grouped-weight"></div>';
		$html[] = '        </div>';
		$html[] = '    </div>';
		$html[] = '    <div class="edit-cell group-edit-cell"><a href="#"><span class="fa fa-pencil center-block"></span></a></div>';
		$html[] = '</div>';
		$html[] = '<div class="ui-sortable">';

		return implode(" ", $html);
	}
	/**
	 * Renders an associative array into a string of 
	 * @return string html
	 */
	private function renderGroupClose() {
		$html = array();

		$html[] = '    </div>';
		$html[] = '</div>';

		return implode(" ", $html);
	}
	/**
	 * Renders an associative array into a string of 
	 * @return string html
	 */
	private function renderRow($assessment, $is_last = false) {
        global $translate;
	    $html = array();
        $assignment_model = new Models_Assignment(array("assessment_id" => $assessment['assessment_id']));
        $assignment = $assignment_model->fetchRowByAssessmentID();

		if (!empty($assessment['collection_id'])) {
			
			if (!empty($this->group_id) && $this->group_id != $assessment['collection_id']) {
				$html[] = $this->renderGroupClose();
				$this->group_id = $assessment['collection_id'];
				$html[] = $this->renderGroupOpen($assessment);
			} // else if (!empty($this->group_id) && $this->group_id == $assessment['collection_id']) { do nothing }

			if (empty($this->group_id)) {

				$html[] = $this->renderGroupOpen($assessment);
				$this->group_id = $assessment['collection_id'];
			}
		} else { // if (empty($assessment['collection_id']))

			if (!empty($this->group_id)) {
				$html[] = $this->renderGroupClose();
				$this->group_id = null;
			} // else { do nothing }
		}

		$html[] = '<div class="row assessment-row" data-assessment-id="' . $assessment['assessment_id'] . '"  >';
		$html[] = '    <div class="assessment-cell">';
		$html[] = '        <div class="assessment-cell-table">';
		
		if (!empty($assessment['collection_id'])) {
			$html[] = '        <div class="icn-cell"><span class="fa fa-caret-right"></span></div>';
		}
		$html[] = '            <div class="icn-cell"><span class="fa fa-grip fa-grip-row"></span></div>';
		$html[] = '            <div class="icn-cell"><input class="checkbox-assessment" name="assessments[]" value="' . $assessment['assessment_id'] . '" type="checkbox"></div>';
		$html[] = '            <div>' . $this->link("grade", $assessment) . $assessment['name'] . '</a></div>';
		$html[] = '        </div>';
		$html[] = '    </div>';
		$html[] = '    <div class="weight-cell">' . $assessment['grade_weighting'] . '%</div>';
        $html[] = '    <div class="assignment-cell">' . ($assignment ? "<a href=" . ENTRADA_URL . "/admin/gradebook/assignments?" . replace_query(array("step" => false, "section" => "grade", "assignment_id" => $assignment->getAssignmentID())) . ">" . $translate->_("View Drop Box") :
                            "<a href=" . ENTRADA_URL . "/admin/gradebook/assignments?" . replace_query(array("step" => false, "section" => "add", "assessment_id" => $assessment['assessment_id'])) . ">" . $translate->_("Add Drop Box")) . '</a></div>';
		$html[] = '    <div class="edit-cell">' . $this->link("edit", $assessment) . '<span class="fa fa-pencil center-block"></span>' . '</a></div>';
		$html[] = '</div>';

		if ($is_last && !empty($this->group_id)) {
			$html[] = $this->renderGroupClose();
			$this->group_id = null;
		}
		// implode with spaces between 
		return implode(" ", $html);
	}
	/**
	 * Generate html
	 * @return string Html string
	 */
	protected function renderView($options = array()) {
		$html = array();

		if (is_array($this->assessments) && count($this->assessments) > 0) {
			$html[] = '<div id="datatable-assessments" class="'.$this->getClass().'">';
			// draw the header th div
			$html[] = '<div class="header-row row">';
			$html[] = '    <div class="assessment-cell">Assessment</div>';
			$html[] = '    <div class="weight-cell">Weight</div>';
			$html[] = '    <div class="assignment-cell">Assignment</div>';
			$html[] = '    <div class="edit-cell">Edit</div>';
			$html[] = '</div>';
			// header row
			$html[] = '<div class="ui-sortable outer">';

			if (is_array($this->assessments)) {
                $last_assessment = end($this->assessments);
				foreach ($this->assessments as $assessment) {
					$is_last = $last_assessment === $assessment;
					$html[] = $this->renderRow($assessment, $is_last);
				}
			}
			
			$html[] = '</div>';
			$html[] = '</div>';
		} else {
			$html[] = '<div id="display-notice-box" class="alert alert-dismissable" role="alert">';
			$html[] = '    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';
			$html[] = '    <ul>';
			if (empty($this->search)) {
				$html[] = '        <li>No Assessments can be found for the chosen curriculum period.</li>';
			} else {
				$html[] = '        <li>No Assessments can be found that match the search criteria.</li>';
			}
			$html[] = '    </ul>';
			$html[] = '</div>';
		}

		echo implode("\n", $html);
	}
}