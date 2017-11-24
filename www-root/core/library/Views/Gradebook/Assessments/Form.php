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
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form extends Views_Gradebook_Base {

	protected $id, $class, $data, $form_item_class = "Views_Gradebook_Assessments_Form_Item", $form_item_class_options = array(), $edit_mode = false, $display_scores_and_weights = true, $edit_scores = true, $edit_weights = true, $edit_comments = false;

	protected function renderView($options = array()) {

		if ($this->data) {
			
			$html = array();

			$html[] = '<div id="'.$this->getID().'" class="'.$this->getClass().'">';

			foreach ($this->data as $item_id => $form_item) {
                $form_item_view = new $this->form_item_class(
                    array_merge(
                        $this->form_item_class_options,
                        array(
                            'item_id' => $item_id,
                            'data' => $form_item,
                            'edit_mode' => $this->edit_mode,
                            'display_scores_and_weights' => $this->display_scores_and_weights,
                            'edit_scores' => $this->edit_scores,
                            'edit_weights' => $this->edit_weights,
                            'edit_comments' => $this->edit_comments,
                        )
                    )
                );
				$html[] = $form_item_view->render(array(), false);
			}

			$html[] = '</div>';

			echo implode("\n", $html);
		}
	}

}