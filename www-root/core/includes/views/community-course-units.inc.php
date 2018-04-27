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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Craig Parsons <craig.parsons@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}

echo '<a name="course-units-section"></a>';
echo '<h2 title="'.$this->translator->_('Course Units Section').'">'.$this->translator->_('Course Units').'</h2>';
echo '<div id="course-units-section">';
echo '<ul class="course-units-list">';

foreach ($this->course_units as $course) {
    foreach ($course as $unit) {

        try {
            $course_view = new Zend_View();
            $course_view->setScriptPath($this->view_directory);
            $course_view->unit_id = $unit->getID();
            $course_view->unit_course_id = $unit->getCourseID();
            $course_view->unit_text = $unit->getUnitText();
            $course_view->course_unit_item_url = $this->course_unit_item_url;
            $course_view->COMMUNITY_URL = $this->COMMUNITY_URL;
            $course_view->PAGE_URL = $this->PAGE_URL;
            echo $course_view->render('community-course-unit.inc.php');

        } catch (Exception $e) {
            echo display_error($e->getMessage());
        }
    }
}
?>
</ul>
</div>
