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
 * Generates a Gradebook DataTable
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_DataTable extends Views_Gradebook_Base {
	protected $id, $class, $width = '100%', $columns = array(), $body_data = array(), $ignore_fields = array();

	/**
	 * Get table width, either in pixels or percent
	 * @return int|string
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Set table width in pixels or percent
	 * @param int|string $width Ex. "345" or "100%"
	 */
	public function setWidth($width) {
		$this->width = $width;
	}

	/**
	 * Get list of columns
	 * @return array
	 */
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Set list of columns. Accepts both simple and associated arrays for adding more info
	 * @param array $columns Ex. array("ID", "Last name", "First name") or array(array("name" => "ID", "width" => "300"))
	 */
	public function setColumns($columns) {
		$this->columns = $columns;
	}

	/**
	 * Renders the table header
	 * @return string html
	 */
	public function renderHeader() {
		$html = array();

		if ($this->columns && is_array($this->columns)) {
			$html[]	= '	<thead>';
			$html[] = '		<tr>';

			foreach($this->columns as $column_entry) {
				if (is_array($column_entry)) {
					$html[] = '<th width="'.$column_entry['width'].'">'.$column_entry['name'].'</th>';
				}
				else {
					$html[] = '<th>'.$column_entry.'</th>';
				}
			}

			$html[] = '		</tr>';
			$html[] = '</thead>';
		}

		return implode("\n", $html);
	}

	/**
	 * Renders the table body if body_data is provided
	 * @return string html
	 */
	public function renderBody() {
		$html = array();

		if ($this->body_data && is_array($this->body_data)) {
			$html[] = '<tbody>';

			foreach($this->body_data as $row_data) {
				$html[] = '<tr>';

				foreach($row_data as $key => $table_data) {

					// only include data from fields that are not in ignore_fields 
					if (!in_array($key, $this->ignore_fields)) {
						
						if (is_array($table_data)) {
							$html[] = '<td class="'.$table_data["class"].'" id="'.$table_data["id"].'">';
							$html[] = $table_data["content"];
							$html[] = '</td>';
						}
						else {
							$html[] = '<td>';
							$html[] = $table_data;
							$html[] = '</td>';
						}
					}
				}

				$html[] = '</tr>';
			}

			$html[] = '</tbody>';
		}

		return implode("\n", $html);
	}

	/**
	 * Generate html
	 * @param array $options
	 * @return string html
	 */
	protected function renderView($options = array()) {
		$html = array();

		$html[] = '<table id="'.$this->getID().'" class="'.$this->getClass().'" width="'.$this->getWidth().'">';
		$html[] = $this->renderHeader();
		$html[] = $this->renderBody();
		$html[] = '</table>';

		echo  implode("\n", $html);
	}
}