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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!(Entrada_Settings::read("curriculum_weeks_enabled") && $ENTRADA_ACL->amIAllowed("weekcontent", "read", false))) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ($WEEK_ID) {
        $week = Models_Week::fetchRowByID($WEEK_ID);
        if ($week) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE."?id=".$WEEK_ID, "title" => $week->getWeekTitle());
            $curriculum_type = Models_Curriculum_Type::fetchRowByID($week->getCurriculumTypeID());
            $course_units = Models_Course_Unit::fetchAllByWeekID($WEEK_ID);
            $units = array();
            foreach ($course_units as $course_unit) {
                $course = Models_Course::fetchRowByID($course_unit->getCourseID());
                $period = Models_Curriculum_Period::fetchRowByID($course_unit->getCperiodID());
                $units[$period->getPeriodText()][$course_unit->getID()] = $course;
            }
            $detail_view = function () use ($translate, $week, $curriculum_type, $units) {
                ?>
                <h1><?php echo $week->getWeekTitle(); ?></h1>
                <div id="week-details-section">
                    <div class="control-group">
                        <div class="controls">
                            <strong><?php echo $translate->_("Curriculum Category"); ?></strong>:
                            <?php echo $curriculum_type->getCurriculumTypeName(); ?>
                        </div>
                    </div>
                </div>
                <div id="course-units-section">
                    <?php foreach ($units as $period_text => $unit_tuples): ?>
                        <h2><?php echo $period_text; ?></h2>
                        <ul class="course-units-list">
                            <?php foreach ($unit_tuples as $cunit_id => $course): ?>
                                <li class="course-unit-item">
                                    <a href="<?php echo ENTRADA_URL; ?>/courses/units?id=<?php echo $course->getID(); ?>&cunit_id=<?php echo $cunit_id; ?>">
                                        <?php echo $course->getCourseText(); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
                <?php
            };
            $detail_view();
        } else {
            display_error("Week not found");
        }
    } else {
        $units = Models_Course_Unit::fetchMyUnits();

        // Find out list of courses per year (we need this to structure our table headers)
        $year_courses = array();
        foreach ($units as $year => $year_content) {
            if (!array_key_exists($year, $year_courses)) {
                $year_courses[$year] = array();
            }
            foreach ($year_content as $unit => $courses) {
                foreach ($courses as $course => $course_content) {
                    if (!array_key_exists($course, $year_courses[$year])) {
                        $year_courses[$year][$course] = array();
                    }
                }
            }
        }
        foreach ($year_courses as $year => $courses) {
            ksort($year_courses[$year]);
        }

        try {
            $view_directory = ENTRADA_ABSOLUTE . "/core/modules/public/courses/units/";
            $units_view = new Zend_View();
            $units_view->setScriptPath($view_directory);
            $units_view->view_directory = $view_directory;
            $units_view->translate = $translate;
            $units_view->units = $units;
            $units_view->year_courses = $year_courses;
            echo $units_view->render('units.view.php');
        } catch (Exception $e) {
            display_error($e->getMessage());
        }
    }
}
