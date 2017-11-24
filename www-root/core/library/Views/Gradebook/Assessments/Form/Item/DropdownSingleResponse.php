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
 * Renders an assessment form dropdown single reponse item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_DropdownSingleResponse extends Views_Gradebook_Assessments_Form_Item_Base {
	
	/**
	 * Renders each item response as a select option
	 * @return string html
	 */
	protected function getItemResponsesAsOptions() {
		
		if ($this->data['item']['item_responses']) {

			$html = array();

			foreach($this->data['item']['item_responses'] as $item_response) {
				$html[] = '<option data-item-id="'.html_encode($this->data['item']['item_id']).'" data-gairesponse-id="'.html_encode($item_response['gairesponse_id']).'" value="'.html_encode($item_response['gairesponse_id']).'" '.$this->getSelectedAttr($item_response).'>'.html_encode($item_response['text']).'</option>';
			}

			return implode("\n", $html);
		}
	}

	protected function renderDefaultOption() {
		global $translate;
		return '<option>'.$translate->_("Select One").'</option>';
	}

	protected function getSelectedAttr($item_response) {
        return !is_null($item_response["proxy_score"]) ? 'selected="selected"' : '';
    }

    protected function renderScoreList() {
        if ($this->display_scores_and_weights) {
            $gradebook_assessments_form_item = new Views_Gradebook_Assessments_Form_Item_VerticalMultipleChoiceSingleResponseInternal(
                array(
                    'data' => $this->data,
                    'display_scores_and_weights' => $this->display_scores_and_weights,
                    'edit_scores' => $this->edit_scores
                )
            );
            return $gradebook_assessments_form_item->html();
        }
        return null;
    }

	protected function renderMultiple() {
		return null;
	}

	protected function getNameAttr() {
		return 'name="proxy-scores['.html_encode($this->data['item']['item_id']).']"';
	}

    /**
     * Renders the item comment per rubric item
     * @param  array    $item   rubric item
     * @return string   $html   html
     */
    protected function renderItemComment($item) {
    	global $translate;

        if ($item['comment_type'] != 'disabled' && $item['item_responses']) {

            $comment_label_class = $item['comment_type'] == 'mandatory' ? ' form-required' : ($item['comment_type'] == 'flagged' ? ' form-flagged' : ''); // '' is for $item['comment_type'] == 'optional'
       
            $html = array();
            $html[] = '<tr class="rubric-comment item-response-view">';
            $html[] = '  <td colspan="2">';
            $html[] = '    <label class="control-label'. $comment_label_class .'" for="'.'item-'.$item['item_id'].'-comments">Comment</label>';
            $html[] = '    <textarea '.$this->getDisabledComments().' name="item-'.$item['item_id'].'-comments" id="item-'.$item['item_id'].'-comments" data-gafelement-id = "'.$item['gafelement_id'].'" class="span12 expandable">'.html_encode($item['comment']).'</textarea>';
            $html[] = '  </td>';
            $html[] = '</tr>';

            return implode("\n", $html);
        }
    }

	/**
     * Renders the complete free text block
	 * @param array $options
     * @return string html
     */
	protected function renderView($options = array()) {
		$html = array();

		$disabled = $this->display_mode ? '' : 'disabled="disabled"';

		$class = $this->display_scores_and_weights ? 'has-internal-table' : '';

		$html[] = '<div class="assessment-horizontal-choice-item item-container assessment-form-item '.$class.'" data-item-id="'.html_encode($this->item_id).'">';
		$html[] = '	<table class="table item-table horizontal-multiple-choice-single table-bordered">';
        $html[] = '     <thead>';
        $html[] = '         '.$this->renderEditBar();
        $html[] = '         <tr class="heading">';
        $html[] = '             <th colspan="100%" class="text-left">';
        $html[] = '                 <h3>'.html_encode($this->data['details']['title']).'</h3>';
		$html[] = '                 '.$this->renderCurriculumTags($this->data['item']['curriculum-tags']);
        $html[] = '             </th>';
        $html[] = '         </tr>';
        $html[] = '     </thead>';
		$html[] = '		<tbody>';
		$html[] = '			<tr class="response-label item-response-view" id="item-response-view-'.html_encode($this->data['item']['item_id']).'">';
		$html[] = '				<td width="45%" class="item-type-control middle">';
		$html[] = '					<select id="item-'.html_encode($this->data['item']['item_id']).'" data-item-id="'.html_encode($this->data['item']['item_id']).'" '.$this->getNameAttr().' class="item-control proxy-scores" '.$this->renderMultiple().'>';
		$html[] = '						'.$this->renderDefaultOption();
		$html[] = '						'.$this->getItemResponsesAsOptions();
		$html[] = '					</select>';
		$html[] = '				</td>';
        if ($out= $this->renderScoreList()) {
            $html[] = '         <td>' . $this->renderScoreList() . '</td>';
        }
        $html[] = '             '.$this->renderWeightCell($this->data['item']);
        $html[] = '         </tr>';
        $html[] = '			'.$this->renderItemComment($this->data['item']);
        $html[] = '     </tbody>';
        $html[] = ' </table>';
        $html[] = '</div>';


        echo implode("\n", $html);
	}
}