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
 * Renders an assessment form HorizontalMultipleChoiceSingleResponse item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_HorizontalMultipleChoiceSingleResponse extends Views_Gradebook_Assessments_Form_Item_Base {

    /**
     * Returns how many item responses are attached to this item
     * @return int
     */
    protected function getItemResponseCount() {
        return count($this->data['item']['item_responses']);
    }

    /**
     * Calculates the column width in percentage
     * @return string
     */
    protected function getColumnWidth() {
        $weight_cell_width = 12;

        return (100 / $this->getItemResponseCount()) . "%";
    }

    /**
     * Renders each possible response
     * @return string html
     */
    protected function renderItemResponses() {

        if ($this->data['item']['item_responses']) {

            $html = array();

            // First, create row of inputs
            $html[] = '<tr class="horizontal-response-input">';

            foreach($this->data['item']['item_responses'] as $item_response) {
                $html[] = '<td width="'.$this->getColumnWidth().'">';
                $html[] = $this->renderItemResponseInput($item_response);
                $html[] = '</td>';
            }

            $html[] = '</tr>';

            // Next, create row of input labels. These are put in separate rows so that
            // the appearance is of uniform inputs and labels
            $html[] = '<tr class="horizontal-response-label">';

            foreach($this->data['item']['item_responses'] as $item_response) {
                $html[] = '<td>';
                $html[] = $this->renderItemResponseLabel($item_response);
                $html[] = '</td>';
            }

            $html[] = '</tr>';

            $html[] = '<tr class="horizontal-response-label">';

            foreach($this->data['item']['item_responses'] as $item_response) {
                $html[] = '<td>';
                $html[] = $this->renderScore($item_response);
                $html[] = '</td>';
            }

            $html[] = '</tr>';

            // Render a comment row if comments are enabled for this item
            if ($this->data['item']['comment_type'] != 'disabled') {
                $html[] = '<tr class="rubric-comment item-response-view"">';
                $html[] = ' '.$this->renderItemComment($this->data['item']);
                $html[] = '</tr>';
            }
            return implode("\n", $html);
        }
    }

    /**
     * Renders an item response input
     * @param  array    $item_response
     * @return string   html
     */
    protected function renderItemResponseInput($item_response) {
        $selected = (isset($item_response["proxy_score"]) && !is_null($item_response["proxy_score"])) ? 'checked="checked"' : '';
        return '<input '.$this->getDisabledAttr().' '.$selected.' type="radio" class="item-control proxy-scores" data-item-id="'.html_encode($this->data['item']['element_id']).'" data-iresponse-id="'.html_encode($item_response['iresponse_id']).'" id="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'" data-gairesponse-id="'.html_encode($item_response['gairesponse_id']).'" name="proxy-scores['.html_encode($this->item_id).']" value="'.html_encode($item_response['gairesponse_id']).'">';
    }

    /**
     * Renders an item response label
     * @param  array    $item_response
     * @return string   html
     */
    protected function renderItemResponseLabel($item_response) {
        return '<label for="item-'.html_encode($this->item_id).'-response-'.html_encode($item_response['iresponse_id']).'">'.html_encode($item_response['text']).'</label>';
    }

    /**
     * Renders the item comment per rubric item
     * @param  array    $item   rubric item
     * @return string   $html   html
     */
    protected function renderItemComment($item) {

        if ($item['comment_type'] != 'disabled' && $item['item_responses']) {
            $comment_label_class = $item['comment_type'] == 'mandatory' ? ' form-required' : ($item['comment_type'] == 'flagged' ? ' form-flagged' : '');
            $comment = isset($item['comment']) ? $item['comment'] : "";
            $html = array();
            $html[] = '  <td colspan="' . count($item['item_responses']) .'">';
            $html[] = '    <label class="control-label'. $comment_label_class .'" for="'.'item-'.$item['item_id'].'-comments">Comment</label>';
            $html[] = '    <textarea '.$this->getDisabledComments().' name="item-'.$item['item_id'].'-comments" id="item-'.$item['item_id'].'-comments" data-item-id="'.$item['item_id'].'" data-gafelement-id = "'.$item['gafelement_id'].'" class="span12 expandable">'.html_encode($comment).'</textarea>';
            $html[] = '  </td>';

            return implode("\n", $html);
        }
    }

    /**
     * Renders the complete HorizontalMultipleChoiceSingleResponse block
     * If there are curriculum / objective attached to an assessment item, it will be displayed as a popover-styled tooltip
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
		$html = array();
        
        $html[] = '<div class="assessment-horizontal-choice-item item-container assessment-form-item" data-item-id="'.html_encode($this->item_id).'">';
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
        $html[] = '     <tbody>';
        $html[] = '         <tr class="item-response-view" id="item-response-view-'.html_encode($this->data['item']['element_id']).'">';
        $html[] = '             <td><table class="table-internal">'.$this->renderItemResponses().'</table></td>';
        $html[] = '             '.$this->renderWeightCell($this->data['item']);
        $html[] = '         </tr>';
        $html[] = '     </tbody>';
        $html[] = ' </table>';
        $html[] = '</div>';

        echo implode("\n", $html);
	}
}