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
 * Google Docs Viewer
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_File_GoogleDocsViewer extends Views_Gradebook_File_Base {

	/**
	 * Adds the Google Docs Viewer URL prefix and URL-encodes the file URL
	 * @return string URL
	 */
	protected function getFileUrl() {
		return "https://docs.google.com/viewer?url=".urlencode(parent::getFileUrl())."&amp;embedded=true";
	}

	protected function renderView($options = array()) {
		echo '<iframe width="100%" height="99%" frameborder="0" src="'.$this->getFileUrl().'" />';
	}

}