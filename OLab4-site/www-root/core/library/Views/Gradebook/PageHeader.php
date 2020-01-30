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
 * Generates the page header to Gradebook pages
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Views_Gradebook_PageHeader extends Views_Gradebook_Base {
	protected $course, $module, $page_title;

	/**
	 * Get page title
	 * @return string
	 */
	public function getPageTitle() {
		return $this->page_title;
	}

	/**
	 * Returns the output buffer of courses_subnavigation(), as it only echos the menu structure
	 * @return string html Output of courses_subnavigation()
	 */
	public function getCoursesSubnavigation() {
		ob_start();

		courses_subnavigation($this->course, $this->module);

		$courses_subnavigation = ob_get_contents();
		ob_end_clean();

		return $courses_subnavigation;
	}

	/**
	 * Generate the html for the search bar
	 * @param array $options
	 * @return string
	 */
	protected function renderView($options = array()) {
		$html = array();

		$html[] = '<h1>'.$this->course->getFullCourseTitle().'</h1>';
		$html[] = $this->getCoursesSubnavigation();

		echo implode("\n", $html);
	}
}