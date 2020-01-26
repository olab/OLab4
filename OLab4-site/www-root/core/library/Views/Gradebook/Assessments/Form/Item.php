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
 * Renders an assessment form item based on type
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item extends Views_Gradebook_Base {

	protected $item_id, $data, $item_class, $edit_mode = false, $display_scores_and_weights, $edit_scores, $edit_weights, $edit_comments;

	/**
	 * Instantiates the specific item View class based on what the type is (rubric, horizontal single response, etc)
	 * @param array $options 
	 */
	public function __construct($options) {
		parent::__construct($options);

		$item_class = $this->getClassName();

		if (class_exists($item_class)) {
			$this->item_class = new $item_class($options);
		}
	}

	/**
	 * Generates the class name based on type
	 * @return string
	 */
	protected function getClassName() {
		return 'Views_Gradebook_Assessments_Form_Item_'.html_encode($this->data['details']['type']);
	}

	/**
	 * Renders html only if corresponding class exists
	 * @param array $options
	 * @return string html
	 */
	protected function renderView($options = array()) {

		if (class_exists($this->getClassName())) {

			$html = array();

			$html[] = '<div class="form-item" data-element-id="'.html_encode($this->item_id).'">';
			$html[] = $this->item_class->render(array(), $echo = false);
			$html[] = '</div>'."\n";

			echo implode("\n", $html);
		}
	}
}