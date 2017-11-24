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
 * Prizm Docs Viewer
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_File_PrizmDocViewer extends Views_Gradebook_File_Base {

	/**
	 * Adds the Prizm Docs Viewer URL prefix and URL-encodes the file URL
	 * @return string URL
	 */
	protected function getFileUrl() {

		$json_data = Entrada_Settings::fetchValueByShortname("prizm_doc_settings");

		$settings_url = "";
		$server_url = "";
		if ($json_data) {
			$prizm_doc_settings = json_decode($json_data, true);
			if (!empty($prizm_doc_settings) && is_array($prizm_doc_settings)) {
				$server_url = $prizm_doc_settings["url"];
				foreach ($prizm_doc_settings as $key => $value) {
					if ($key != "url") {
						$settings_url .= $key."=".$value."&";
					}
				}
			}
		}

		if ($settings_url && $server_url) {
			$settings_url = substr($settings_url, 0, -1);
			return $server_url."?document=".urlencode(parent::getFileUrl())."?1&".$settings_url;
		}
		return false;
	}

	protected function renderView($options = array()) {
		echo '<iframe width="100%" height="99%" frameborder="0" src="'.$this->getFileUrl().'" />';
	}

}