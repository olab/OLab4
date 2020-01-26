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
 * Loads a file, using the appropriate Filetype class
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_File extends Views_Gradebook_Base {
	protected $file_class, $data = array();

	public function __construct($options) {
		parent::__construct($options);

		$file_class = $this->getClassName();

		if (class_exists($file_class)) {
			$this->file_class = new $file_class($options);
		}
		else {
			$this->file_class = new Views_Gradebook_File_Download($options);
		}
	}

	protected function getClassName() {
		return "Views_Gradebook_File_".$this->data['classname'];
	}

	protected function renderView($options = array()) {
		echo $this->file_class->render($echo = false);
	}
}