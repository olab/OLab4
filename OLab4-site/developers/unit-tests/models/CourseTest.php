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
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../www-root/core",
    dirname(__FILE__) . "/../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../www-root/core/library",
    dirname(__FILE__) . "/../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

require_once(dirname(__FILE__) . "/../BaseTestCase.php");

class CourseTest extends BaseTestCase {

    public function testGetCperiodID_UserInAudience() {
        global $db, $ENTRADA_USER;
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(111);
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(array("cperiod_id" => 123)));
        $course = new Models_Course(array("course_id" => 1));
        $this->assertEquals(123, $course->getCperiodID());
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("course_audience"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`proxy_id` = '111'"),
            $this->stringContains("`audience_value` = '111'")));
        Phake::verify($ENTRADA_USER)->getID();
    }

    public function testGetCperiodID_UserNotInAudience() {
        global $db, $ENTRADA_USER;
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(111);
        Phake::when($db)->GetAll($this->stringContains("111"))->thenReturn(array());
        Phake::when($db)->GetAll($this->logicalNot($this->stringContains("111")), $this->anything())->thenReturn(array(array("cperiod_id" => 123)));
        $course = new Models_Course(array("course_id" => 1));
        $this->assertEquals(123, $course->getCperiodID());
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("course_audience"),
            $this->stringContains("curriculum_periods"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`proxy_id` = '111'"),
            $this->stringContains("`audience_value` = '111'")));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("course_audience"),
            $this->stringContains("curriculum_periods"),
            $this->logicalNot($this->stringContains("111"))), $this->contains("1"));
        Phake::verify($ENTRADA_USER)->getID();
    }

    public function testGetCperiodID_UserNotInAudience_NoCurriculumPeriods() {
        global $db, $ENTRADA_USER;
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(111);
        Phake::when($db)->GetAll($this->stringContains("111"))->thenReturn(array());
        Phake::when($db)->GetAll($this->logicalNot($this->stringContains("111")), $this->anything())->thenReturn(array());
        $course = new Models_Course(array("course_id" => 1));
        $this->assertEquals(null, $course->getCperiodID());
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("course_audience"),
            $this->stringContains("curriculum_periods"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`proxy_id` = '111'"),
            $this->stringContains("`audience_value` = '111'")));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("course_audience"),
            $this->stringContains("curriculum_periods"),
            $this->logicalNot($this->stringContains("111"))), $this->contains("1"));
        Phake::verify($ENTRADA_USER)->getID();
    }

    public function testGetObjectives_Empty() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByCourseIDsAndCperiodID(Phake::anyParameters())->thenReturn(array());
        $course = new Models_Course(array("course_id" => 1));
        $this->assertEquals(array(), $course->getObjectives(1));
        Phake::verify($objective_repository)->fetchAllByCourseIDsAndCperiodID(array(1), 1);
    }

    public function testGetObjectives() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByCourseIDsAndCperiodID(Phake::anyParameters())->thenReturn(array(1 => array("objectives")));
        $course = new Models_Course(array("course_id" => 1));
        $this->assertEquals(array("objectives"), $course->getObjectives(1));
        Phake::verify($objective_repository)->fetchAllByCourseIDsAndCperiodID(array(1), 1);
    }

    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    CourseTest::main();
}
