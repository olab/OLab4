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
 * Generates a modal window
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_Assignments_Modal extends Views_Gradebook_Modal {
	/**
	 * Generate html
	 * @param array $options
	 * @return string
	 */
	protected function renderView($options = array()) {
		$html = array();

		$html[] = '<div id="'.$this->getID().'" class="modal hide '.$this->getClass().'" tabindex="-1" role="dialog" aria-labelledby="'.$this->getID().'" aria-hidden="true">';
		$html[] = '	<div class="modal-header clearfix">';
		$html[] = '		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
		$html[] = '		<h3 class="pull-left">'.$this->getTitle().'</h3>';
		$html[] = '		'.$this->getHeaderContent();
		$html[] = '	</div>';
		$html[] = '	<div class="modal-body">';
		$html[] = 		$this->getBody();
		$html[] = '	</div>';
		$html[] = '	<div class="modal-footer">';
		$html[] = '		'.$this->getFooterContent();
		$html[] = '		'.$this->getDismissButton();
		$html[] = '		'.$this->getSuccessButton();
		$html[] = '	</div>';
		$html[] = '</div>';

		echo implode("\n", $html);
	}
}