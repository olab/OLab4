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
 * File viewer for when there is no file viewer. Allows a user to download instead.
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_File_Download extends Views_Gradebook_File_Base {

	/**
	 * Creates a timestamp that is 30 mins, instead of the standard
	 * @return int Unix timestamp
	 */
	protected function getTimestamp() {
		// 30 minutes
		return time() + (60 * 30);
	}

	/**
	 * Generates the file URL with the download param 
	 * @return string URL
	 */
	protected function getDownloadLink() {
		return $this->getFileUrl().'&download=1';
	}

	protected function renderView($options = array()) {
		global $translate;

		$html = array();

		$html[] = '<p>'.$translate->_("No viewer available for this filetype. Click the link below to download.").'</p>';
		$html[] = '<a target="_blank" href="'.$this->getDownloadLink().'">'.html_encode($this->data['file_filename']).'</a>';

		echo implode("\n", $html);
	}

}