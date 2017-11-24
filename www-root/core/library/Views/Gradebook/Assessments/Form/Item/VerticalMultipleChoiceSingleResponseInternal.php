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
 * Renders an assessment form VerticalMultipleChoiceSingleResponseInternal item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_VerticalMultipleChoiceSingleResponseInternal extends Views_Gradebook_Assessments_Form_Item_VerticalMultipleChoiceSingleResponse {

	protected function renderItemResponses() {

        if ($this->data['item']['item_responses']) {

            $html = array();

            foreach($this->data['item']['item_responses'] as $i => $item_response) {
            	$row_stripe = $i % 2 ? 'row-stripe' : '';

            	$label_width = $this->display_scores_and_weights ? '90%' : '95%';

            	$html[] = '<tr class="'.$row_stripe.'">';
                $html[] = '	<td width="'.$label_width.'" class="cell-label">'.$this->renderItemResponseLabel($item_response).'</td>';
                $html[] = $this->display_scores_and_weights ? '	<td width="30%" class="cell-score">'.$this->renderScore($item_response, true).'</td>' : '';
                $html[] = '</tr>';
            }

            return implode("\n", $html);
        }
    }

    protected function renderView($options = array()) {
		$html = array();

		$html[] = '	<table class="table-internal">';
		$html[] = '		<tbody>';
		$html[] = '			'.$this->renderItemResponses();
        $html[] = '     </tbody>';
        $html[] = ' </table>';

        echo implode("\n", $html);
	}

    public function html() {
        $html = array();

        $html[] = ' <table class="table-internal">';
        $html[] = '     <tbody>';
        $html[] = '         '.$this->renderItemResponses();
        $html[] = '     </tbody>';
        $html[] = ' </table>';

        return implode("\n", $html);
    }
}