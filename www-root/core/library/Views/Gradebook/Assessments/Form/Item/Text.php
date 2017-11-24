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
 * Renders an assessment form Text item
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Form_Item_Text extends Views_Gradebook_Assessments_Form_Item_FreeText {

	protected function renderTitle() {
		return null;
	}

	protected function renderWeight() {
        return null;
    }

	protected function renderInput() {
		// For this to load properly, don't forget to run the load_rte() function 
		// on the page this form is run on to load CKEditor in the page <head>. 
		
		if ($this->edit_mode) {
			return '<textarea '.$this->getDisabledAttr().' class="" id="item-'.html_encode($this->data['item']['item_id']).'" name="item-'.html_encode($this->data['item']['item_id']).'" style="height: 85px; overflow: hidden;">'.html_encode($this->data['details']['title']).'</textarea>';
		}

		// Purposefully does not html_encode the data because it contains html that should be rendered
		return '<div class="form-text-container"><p>'.$this->data['details']['title'].'</p></div>';
	}
}