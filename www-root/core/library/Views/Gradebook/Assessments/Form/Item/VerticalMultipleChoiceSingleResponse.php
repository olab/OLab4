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
 * Renders an assessment form VerticalMultipleChoiceSingleResponse item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_VerticalMultipleChoiceSingleResponse extends Views_Gradebook_Assessments_Form_Item_Base {

    protected function renderTableHeader() {
        $html = array();

        $html[] = '    <th class="text-left">';
        $html[] = '        <h3>'.html_encode($this->data['details']['title']).'</h3>';
        $html[] = '        '.$this->renderCurriculumTags($this->data['item']['curriculum-tags']);
        $html[] = '    </th>';

        return implode("\n", $html);
    }

    protected function renderItemResponses() {

        if ($this->data['item']['item_responses']) {

            $html = array();

            foreach($this->data['item']['item_responses'] as $i => $item_response) {
                $row_stripe = $i % 2 ? 'row-stripe' : '';
                $style = ($this->getSelectedAttr($item_response)) ? ' selected-mark-item' : '';

                $html[] = '<tr class="'.$row_stripe.' vertical-multiple-row '.$style.'" id="vertical-multiple-row-'.$item_response['gairesponse_id'].'">';
                $html[] = ' <td class="vertical-response-input">'.$this->renderItemResponseInput($item_response).'</td>';
                $html[] = ' <td class="vertical-response-label table-cell-lg"><span class="control-label">' . (html_encode($item_response["descriptor"]) == "Not Applicable" ? "" : html_encode($item_response["descriptor"])) . '</span><label for="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'">'.nl2br(html_encode($item_response['text'])).'</label></td>';
                $html[] = $this->display_scores_and_weights ? ' <td class=" vertical-response-score">'.$this->renderScore($item_response, true).'</td>' : '';
                $html[] = '</tr>';
            }
            // add the comment block if required
            if ($this->data['item']['comment_type'] != 'disabled') {
                $html[] = '<tr class="rubric-comment item-response-view">';
                $html[] = ' '.$this->renderItemComment($this->data['item']);
                $html[] = '</tr>';
            }

            return implode("\n", $html);
        }
    }

    protected function getSelectedAttr($item_response) {
        return (isset($item_response["proxy_score"]) && !is_null($item_response["proxy_score"])) ? 'checked="checked"' : '';
    }

    protected function renderItemResponseInput($item_response) {
        return '<input type="radio" class="item-control proxy-scores" data-item-id="'.html_encode($item_response['item_id']).'" data-iresponse-id="'.html_encode($item_response['iresponse_id']).'" data-gairesponse-id="'.$item_response['gairesponse_id'].'" id="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'" name="proxy-scores['.html_encode($this->item_id).']" value="'.html_encode($item_response['gairesponse_id']).'" '.$this->getDisabledAttr().' '.$this->getSelectedAttr($item_response).'>';
    }

    protected function renderItemResponseLabel($item_response) {
        return '<label for="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'">'.html_encode($item_response['text']).'</label>';
    }

    protected function renderOpenContainer() {
        return '<div class="assessment-vertical-choice-item item-container assessment-form-item" data-item-id="'.html_encode($this->item_id).'">';
    }

    protected function renderCloseContainer() {
        return '</div>';
    }

    /**
     * Renders the item comment per rubric item
     * @param  array    $item   rubric item
     * @return string   $html   html
     */
    protected function renderItemComment($item) {

        if ($item['comment_type'] != 'disabled' && $item['item_responses']) {

            $comment_label_class = $item['comment_type'] == 'mandatory' ? ' form-required' : ($item['comment_type'] == 'flagged' ? ' form-flagged' : ''); //for $item['comment_type'] == 'optional'
            
            $html = array();
            $html[] = '  <td colspan="3">';
            $html[] = '    <label class="control-label'. $comment_label_class .'" for="'.'item-'.$item['item_id'].'-comments">Comment</label>';
            $html[] = '    <textarea '.$this->getDisabledComments().' name="item-'.$item['item_id'].'-comments" id="item-'.$item['item_id'].'-comments" data-item-id="'.$item['item_id'].'" data-gafelement-id = "'.$item['gafelement_id'].'" class="span12 expandable">'.html_encode($item['comment']).'</textarea>';
            $html[] = '  </td>';

            return implode("\n", $html);
        }
    }

    protected function renderView($options = array()) {
		$html = array();

        $html[] = $this->renderOpenContainer();
        $html[] = ' <table class="item-table vertical-multiple-choice-single">';
        $html[] = '     <thead>';
        $html[] = '         '.$this->renderEditBar();
        $html[] = '         <tr class="heading">';
        $html[] = '             '.$this->renderTableHeader();
        $html[] = '             '.$this->renderWeightCell($this->data['item']);
        $html[] = '         </tr>';
        $html[] = '     </thead>';
        $html[] = '     <tbody>';
        $html[] = '         <tr class="item-response-view" id="item-response-view-'.html_encode($this->data['item']['element_id']).'">';
        $html[] = '             <td colspan="2" class="border-right"><table class="table-internal">'.$this->renderItemResponses().'</table></td>';
        $html[] = '         </tr>';
        $html[] = '     </tbody>';
        $html[] = ' </table>';
        $html[] = $this->renderCloseContainer();

        echo implode("\n", $html);
	}
}