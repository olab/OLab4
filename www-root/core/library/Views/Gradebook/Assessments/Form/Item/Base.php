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
 * Renders an assessment form date selector item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_Base extends Views_Gradebook_Base {
	protected $id, $class, $item_id, $data, $edit_mode, $display_scores_and_weights, $disable_selections, $edit_scores, $edit_weights, $edit_comments, $disable_scores;

	/**
	 * Renders the editing options for that form item.
	 * Only shown if edit_mode is enabled
	 * @return string html
	 */
	protected function renderEditBar() {
		// TODO: implement edit buttons for edit form page
		return $this->edit_mode ? '<tr class="type"><th colspan="100%"></th></tr>' : '';
	}

	/**
     * Render whether an html form element should be disabled
     * @return string html
     */
    protected function getDisabledAttr() {
        return $this->edit_mode || $this->edit_scores ? 'disabled="disabled"' : '';
    }

    /**
     * Render whether the comments html form element should be disabled
     * @return string html
     */
    protected function getDisabledComments() {
        return $this->edit_mode || $this->edit_comments ? '' : 'disabled="disabled"';
    }
    
	/**
	 * Renders a table cell with the weight entry of that row
	 * @param  array $item   row item data model
	 * @param  string $width width of the cell, defaults to 12%
	 * @return string        html
	 */
	protected function renderWeightCell($item, $width = null) {
		global $translate;

		if ($this->display_scores_and_weights) {
			$class = $this->edit_weights ? "text-left" : "text-center";
            $rowspan = ($item['comment_type'] != 'disabled') ? 2: 1;

			$html = array();

			$html[] = '<td rowspan="'.$rowspan.'" class="cell-weight control-group">';
			$html[] = '		<label for="weight-'.html_encode($item['afelement_id']).'" class="label-weight">';
			$html[] = '			<span class="label-text '.$class.'">'.$translate->_("Weight:").'</span>';
	        $html[] = '	  		'.$this->renderWeightInput($item);
	        $html[] = '  	</label>';
	        $html[] = '</td>';

	        return implode("\n", $html);
		}
	}

	/**
	 * Renders the weight as input or text, depending on $this->edit_weights setting
	 * @param  array $item 	item object
	 * @return string 		html
	 */
	protected function renderWeightInput($item) {
		if ($this->edit_weights) {
			$html = array();

			$html[] = '<input type="text" class="item-control input-weight pull-left" name="item-weights['.html_encode($item['afelement_id']).']" id="weight-'.$item['afelement_id'].'" data-item-id="'.html_encode($item['element_id']).'" value="'.html_encode($item['weight']).'">';
			$html[] = '<span class="pull-left weight-percentage-symbol">%</span>';

			$html = implode("\n", $html);
		}
		else {
			$html = isset($item['weight']) ? '<span class="label-text text-center" id="weight-item-'.$item['element_id'].'">'.$item['weight'].'%</span>' : '';
		}

		return $html;
	}

    /**
     * Renders the score as input or text, depending on $this->edit_scores setting
     * @param  array  $item_response  item_response object
     * @param  boolean $inline        Renders the score as above/below or inline
     * @return string                   html
     */
    protected function renderScore($item_response, $inline = false) {

        if ($this->display_scores_and_weights) {
            $score = $item_response["proxy_score"] ? $item_response["proxy_score"] : $item_response["item_response_score"];
            $label_class = $inline ? "text-score-inline pull-center" : "text-score"; 
            $input_score_class = $item_response['flag_response'] == 1 ? " flagged" : "";
            $input_type = $this->edit_scores ? "text" : "hidden";
            $unique_id = $this->edit_scores ? $item_response["iresponse_id"] : $item_response["gairesponse_id"];
            $html = array();
            $html[] = '<div class="score control-group">';
            $html[] = ' <label for="input-score-'.$unique_id.'" class="'.$label_class.'">Score:</label>';
            $html[] = ' <input type="'.$input_type.'" id="input-score-'.$unique_id.'" name="item-scores['.html_encode($unique_id).']" data-iresponse-id="'.html_encode($item_response['iresponse_id']).'" data-gairesponse-id="'.$item_response['gairesponse_id'].'" class="input-score'.$input_score_class.'" value="'.html_encode($score).'" '.($this->disable_scores ? 'disabled="disabled"' : '').' />';
            $html[] = $this->edit_scores ? '' : ' <span id="text-score-'.$unique_id.'" class="text-score">'.html_encode($score).'</span>';
            $html[] = ' <input type="hidden" id="item-response-score-'.$unique_id.'" class="item-response-score" value="'.html_encode($item_response["item_response_score"]).'" />';
            $html[] = '</div>';

            return implode("\n", $html);
        }
    }

	/**
	 * Renders the curriculum tags added to an assessment ID that can be clicked to bring up information: tag name code and description
	 * @param  array  $curriculum_tags  curriculum/objective tags object
	 * @return string 				  html
	 */
	protected function renderCurriculumTags($curriculum_tags) {

		$html = array();

		if (is_array($curriculum_tags) && $curriculum_tags) {
			$i = 0;

			$html[] = '<span style="margin: 0px 8px;">';
			foreach ($curriculum_tags as $val) {

				if (isset($val['id']) && $val['id']) {
					if ($i++ > 0) {
						$html[] = ',';
					}
					$html[] = '<a href="#" onclick="return false;" class="curriculum-tag" data-toggle="popover" objective-id="' . $val['id'] . '" title="' .
						html_encode($val['name']) . '" data-content="' . html_encode($val['desc']);
					if ($val['code'] != '') {
						$html[] = '(' . $val['code'] . ')"';
					}
					$html[] = '">';
					$html[] = limit_chars($val['label'], 20, true, true) . '</a>';
				}
			}
			$html[] = '</span>';
		}

		return implode("\n", $html);
	}
}