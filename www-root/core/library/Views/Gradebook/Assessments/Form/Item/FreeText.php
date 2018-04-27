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
 * Renders an assessment form free text item
 *
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Gradebook_Assessments_Form_Item_FreeText extends Views_Gradebook_Assessments_Form_Item_Base {

    protected function renderTitle() {
        $html = array();

        $html[] = '<tr class="heading">';
        $html[] = ' <th colspan="100%" class="text-left">';
        $html[] = '     <h3>'.html_encode($this->data['details']['title']).'</h3>';
        $html[] = ' </th>';
        $html[] = '</tr>';

        return implode("\n", $html);
    }

	protected function renderInput() {
		return '<textarea '.$this->getDisabledAttr().' class="expandable" id="item-'.html_encode($this->data['item']['item_id']).'" name="item-'.html_encode($this->data['item']['item_id']).'" style="height: 85px; overflow: hidden;"></textarea>';
	}

    protected function renderWeight() {
        return $this->renderWeightCell($this->data['item']);
    }

	/**
     * Renders the complete free text block
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
		$html = array();

		$disabled = $this->edit_mode ? 'disabled="disabled"' : '';

		$html[] = '<div class="assessment-horizontal-choice-item item-container assessment-form-item" data-item-id="'.html_encode($this->item_id).'">';
		$html[] = '	<table class="table item-table horizontal-multiple-choice-single table-bordered">';
        $html[] = '     <thead>';
        $html[] = '         '.$this->renderEditBar();
        $html[] = '         '.$this->renderTitle();
        $html[] = '     </thead>';
		$html[] = '		<tbody>';
		$html[] = '			<tr class="response-label item-response-view">';
		$html[] = '				<td class="item-type-control">';
		$html[] = '					'.$this->renderInput();
		$html[] = '				</td>';
		$html[] = '			</tr>';
        $html[] = '     </tbody>';
        $html[] = ' </table>';
        $html[] = '</div>';


        echo implode("\n", $html);
	}
}
