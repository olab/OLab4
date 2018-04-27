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
 * Renders an assessment form rubric item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_Rubric extends Views_Gradebook_Assessments_Form_Item_Base {
    protected $first_column_width = 20;

    /**
     * Returns how many item responses are attached to this item
     * @return int
     */
    protected function getItemResponseCount() {
        return count($this->data['item_responses']);
    }

    /**
     * Calculates the column width in percentage
     * @return string
     */
    protected function getColumnWidth() {
        // Calculate number of columns based on 
        // The +1 is to account for the first column of rubric items
        $column_count = $this->display_scores_and_weights ? $this->getItemResponseCount() + 2 : $this->getItemResponseCount() + 1;

        return ((100-$this->first_column_width) / $column_count) . "%";
    }

    /**
     * Renders the column headers in a rubric
     * @return string html
     */
    protected function renderRubricItemResponses() {


        if ($this->data['item_responses']) {

            $html = array();

            $html[] = "<tr class=\"rubric-descriptors\">";

            if ($this->getItemResponseCount() > 0) {
                // create empty field for column of items
                $html[] = '<th></th>';
            }

            foreach($this->data['item_responses'] as $item_response) {
                $html[] = '<th class="label-cell">';
                $html[] = '    <h3>'.html_encode($item_response['descriptor']).'</h3>';
                $html[] = '</th>';
            }

            if ($this->display_scores_and_weights) {
                // Adds empty field as column header for weights
                $html[] = '<th></th>';
            }

            $html[] = "</tr>";

            return implode("\n", $html);
        }
    }

    /**
     * Renders the list of items (rows) in a rubric
     * @return string html
     */
    protected function renderRubricItems() {

        if ($this->data['items']) {
            $html = array();

            foreach($this->data['items'] as $item) {

                $rowspan = ($item['comment_type'] != 'disabled') ? 2: 1;
                $html[] = '<tbody>';
                $html[] = '<tr class="rubric-response-input item-response-view" id="item-response-view-'.html_encode($item['element_id']).'" data-afelement-id="'.html_encode($item['afelement_id']).'" data-item-id="'.html_encode($item['element_id']).'">';
                $html[] = ' <td rowspan="'.$rowspan.'">';
                $html[] = '     <div class="rubric-item-text">'.html_encode($item['item_text']).'</div>';
                $html[] = $this->renderCurriculumTags($item["curriculum-tags"]);
                $html[] = '</td>';
                $html[] = $this->renderItemResponses($item);
                $html[] = '<td rowspan="'.$rowspan.'">';
                $html[] = $this->renderWeightCell($item);
                $html[] = '</td>';
                $html[] = '</tr>';

                if ($item['comment_type'] != 'disabled') {
                    $html[] = '<tr class="rubric-comment">';
                    $html[] = ' '.$this->renderItemComment($item);
                    $html[] = '</tr>';
                }
                $html[] = '</tbody>';
            }

            return implode("\n", $html);
        }
    }

    /**
     * Renders the item responses per rubric item
     * @param  array    $item   rubric item
     * @return string   $html   html
     */
    protected function renderItemResponses($item) {

        if ($item['item_responses']) {

            $html = array();

            foreach($item['item_responses'] as $item_response) {
                $class = $item_response['text'] ? 'rubric-response' : 'rubric-response';
                $selected = !is_null($item_response["proxy_score"]) ? 'checked="checked"' : '';
                $score = $item_response["proxy_score"] ? $item_response["proxy_score"] : $item_response["item_response_score"];

                $html[] = '<td class="rubric-response text-center">';
                $html[] = '    <input '.$this->getDisabledAttr().' type="radio" class="item-control proxy-scores" name="proxy-scores['.html_encode($item['element_id']).']" id="response-'.html_encode($item_response['iresponse_id']).'" data-gairesponse-id="'.html_encode($item_response['gairesponse_id']).'" data-item-id="'.html_encode($item['element_id']).'" data-iresponse-id="'.html_encode($item_response['iresponse_id']).'" value="'.html_encode($item_response['gairesponse_id']).'" '.$selected.'>';
                $html[] =      $item_response['text'] ? '<label for="response-'.html_encode($item_response['iresponse_id']).'"><div class="rubric-response-text"><div class="match-height">'.nl2br(html_encode($item_response['text'])).'</div></div></label>' : '';
                $html[] = '    '.$this->renderScore($item_response);
                $html[] = '</td>';
            }

            return implode("\n", $html);
        }
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
            $html[] = '  <td colspan="' . count($item['item_responses']).'">';
            $html[] = '    <label class="control-label'. $comment_label_class .'" for="'.'item-'.$item['item_id'].'-comments">Comment</label>';
            $html[] = '    <textarea '.$this->getDisabledComments().' name="item-'.$item['item_id'].'-comments" id="item-'.$item['item_id'].'-comments" data-item-id="'.$item['item_id'].'" data-gafelement-id = "'.$item['gafelement_id'].'" class="span12 expandable">'.html_encode($item['comment']).'</textarea>';
            $html[] = '  </td>';

            return implode("\n", $html);
        }
    }

    /**
     * Renders one complete rubric item block
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
		$html = array();

        $html[] = '  <div class="assessment-horizontal-choice-rubric rubric-container assessment-form-item" data-item-id="'.html_encode($this->item_id).'">';
        $html[] = '   <div class="rubric-error-msg"></div>';
        $html[] = '   <div class="table-responsive">';
        $html[] = '   <table class="table table-bordered table-striped rubric-table">';
        $html[] = '       <thead>';
        $html[] = '           '.$this->renderEditBar();
        $html[] = '           <tr class="rubric-title">';
        $html[] = '             <th colspan="100%" class="text-left">';
        $html[] = '                 <h3>'.html_encode($this->data['details']['title']).'</h3>';
        $html[] = '             </th>';
        $html[] = '           </tr>';
        $html[] = '           '.$this->renderRubricItemResponses();
        $html[] = '       </thead>';
        $html[] = '       '.$this->renderRubricItems();
        $html[] = '   </table>';
        $html[] = '  </div>';
        $html[] = '  </div>';

		echo implode("\n", $html);
	}
}