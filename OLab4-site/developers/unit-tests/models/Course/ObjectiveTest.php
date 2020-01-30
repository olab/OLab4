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
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../../www-root/core",
    dirname(__FILE__) . "/../../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../../www-root/core/library",
    dirname(__FILE__) . "/../../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");

class Course_ObjectiveTest extends BaseTestCase {

    public function testFetchAllByCourseIDCperiodID_NoCperiod() {
        global $db;

        $results = array(
            array("objective_id" => 1234)
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($results);

        $expected_course_objectives = array(
            new Models_Course_Objective(array("objective_id" => 1234)),
        );

        $course_objectives = Models_Course_Objective::fetchAllByCourseIDCperiodID(1, null, "event");
        $this->assertEquals($expected_course_objectives, $course_objectives);

        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cperiod_id` IS NULL"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`objective_type` = 'event'"),
            $this->stringContains("`active` = 1")
        ));
    }

    public function testFetchAllByCourseIDCperiodID() {
        global $db;

        $results = array(
            array("objective_id" => 1234)
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($results);

        $expected_course_objectives = array(
            new Models_Course_Objective(array("objective_id" => 1234)),
        );

        $course_objectives = Models_Course_Objective::fetchAllByCourseIDCperiodID(1, 123, "event");
        $this->assertEquals($expected_course_objectives, $course_objectives);

        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cperiod_id` = '123'"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`objective_type` = 'event'"),
            $this->stringContains("`active` = 1")
        ));
    }

    public function testFetchAllByCourseIDCperiodID_Error() {
        global $db;

        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(false);

        try {
            $course_objectives = Models_Course_Objective::fetchAllByCourseIDCperiodID(1, 123, "event");
        } catch (Exception $e) {
        }
        if (!isset($e)) {
            $this->fail("Expected Exception when database returns false");
        }

        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cperiod_id` = '123'"),
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`objective_type` = 'event'"),
            $this->stringContains("`active` = 1")
        ));
    }


    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    Course_ObjectiveTest::main();
}

