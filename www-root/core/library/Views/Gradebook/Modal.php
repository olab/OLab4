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

class Views_Gradebook_Modal extends Views_Gradebook_Base {
	protected $id, $class, $title, $body, $dismiss_button, $success_button, $additional_button, $header_content, $footer_content;

	/**
	 * Get the modal title displayed in the header
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title text
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Get modal body
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Set modal body
	 * @param string $body html
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * Get dismiss button
	 * @return string
	 */
	public function getDismissButton() {
		global $translate;

		if ($this->dismiss_button) {
			$button_class = '';
			$button_text = $translate->_('Close');

			// if dismiss_button is an array, pull the values from it
			if (is_array($this->dismiss_button)) {
				// set class
				if (isset($this->dismiss_button['class'])) {
					$button_class = $this->dismiss_button['class'];
				}

				// set button text
				if (isset($this->dismiss_button['text'])) {
					$button_text = $this->dismiss_button['text'];
				}
			}
			else {
				$button_text = $this->dismiss_button;
			}

			return '<button class="btn '.$button_class.'" data-dismiss="modal" aria-hidden="true">'.$button_text.'</button>';
		}
	}

	/**
	 * Sets dismiss button. Supports both simple text string and array for more advanced customization.
	 * @param array|string $button
	 */
	public function setDismissButton($dismiss_button) {
		$this->dismiss_button = $dismiss_button;
	}

	/**
	 * Get success button
	 * @return string
	 */
	public function getSuccessButton() {
		global $translate;

		if ($this->success_button) {
			$button_class = 'btn-primary';
			$button_text = $translate->_('Close');

			// if success_button is an array, pull the values from it
			if (is_array($this->success_button)) {
				// set class
				if (isset($this->success_button['class'])) {
					$button_class = $this->success_button['class'];
				}

				// set button text
				if (isset($this->success_button['text'])) {
					$button_text = $this->success_button['text'];
				}
			}
			else {
				$button_text = $this->success_button;
			}

			return '<button class="btn '.$button_class.'">'.$button_text.'</button>';
		}
	}

	/**
	 * Get additional button
	 * @return string
	 */
	public function getAdditionalButton() {
		global $translate;

		if ($this->additional_button) {
			$button_class = 'btn-info';
			$button_text = $translate->_('');

			// if additional_button is an array, pull the values from it
			if (is_array($this->additional_button)) {
				// set class
				if (isset($this->additional_button['class'])) {
					$button_class = $this->additional_button['class'];
				}

				// set button text
				if (isset($this->additional_button['text'])) {
					$button_text = $this->additional_button['text'];
				}
			}
			else {
				$button_text = $this->additional_button;
			}

			return '<button class="btn '.$button_class.'">'.$button_text.'</button>';
		}
	}

	/**
	 * Get header content, right of the header
	 * @return string
	 */
	public function getHeaderContent() {
		return $this->header_content;
	}

	/**
	 * Set header content
	 * @param string $header_content html
	 */
	public function setHeaderContent($header_content) {
		$this->header_content = $header_content;
	}

	/**
	 * Get footer content, left of buttons
	 * @return string
	 */
	public function getFooterContent() {
		if ($this->footer_content) {
			$html = array();

			$html[] = '<div class="footer-content pull-left">';
			$html[] = '	'.$this->footer_content;
			$html[] = '</div>';

			return implode("\n", $html);
		}
	}

	/**
	 * Set footer content
	 * @param string
	 */
	public function setFooterContent($footer_content) {
		$this->footer_content = $footer_content;
	}

	/**
	 * Generate html
	 * @param array $options
	 * @return string
	 */
	protected function renderView($options = array()) {
        $html = array();

        $html[] = '<div id="'.$this->getID().'" class="modal hide '.$this->getClass().'" tabindex="-1" role="dialog" aria-labelledby="'.$this->getID().'" aria-hidden="true">';
        $html[] = '  <div class="modal-dialog">';
        $html[] = '    <div class="modal-content">';
        $html[] = '      <div class="modal-header clearfix">';
        $html[] = '        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
        $html[] = '        <h3>'.$this->getTitle().'</h3>';
        $html[] = '        '.$this->getHeaderContent();
        $html[] = '      </div>';
        $html[] = '      <div class="modal-body">';
        $html[] =          $this->getBody();
        $html[] = '      </div>';
        $html[] = '      <div class="modal-footer">';
        $html[] = '        '.$this->getFooterContent();
        $html[] = '        <div class="buttons pull-right">';
        $html[] = '          '.$this->getDismissButton();
        $html[] = '          '.$this->getAdditionalButton();
        $html[] = '          '.$this->getSuccessButton();
        $html[] = '        </div>';
        $html[] = '      </div>';
        $html[] = '    </div>';
        $html[] = '  </div>';
        $html[] = '</div>';

        echo implode("\n", $html);
    }
}