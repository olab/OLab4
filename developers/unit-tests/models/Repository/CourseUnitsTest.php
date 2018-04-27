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

class Models_Repository_CourseUnitsTest extends BaseTestCase {

    private $repository;

    public function setUp() {
        parent::setUp();
        $this->repository = Phake::partialMock("Models_Repository_CourseUnits");
        Phake::when($this->repository)->fromArrays(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($this->repository)->fromArraysBy(Phake::anyParameters())->thenReturn(array(123 => array("data")));
        Phake::when($this->repository)->fromArraysByMany(Phake::anyParameters())->thenReturn(array(1 => array(123 => array("data"))));
    }

    public function testFetchAllByIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByIDs() {
        global $db;
        $row = array("cunit_id" => 1, "unit_title" => "Test Course Unit");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $course_units = $this->repository->fetchAllByIDs(array(11));
        $this->assertNotEmpty($course_units);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`deleted_date` IS NULL"),
            $this->stringContains("ORDER BY `unit_order` ASC")
        ));
    }

    public function testFetchAllByCourseID_Empty() {
        Phake::when($this->repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array());
        $this->assertEquals(array(), $this->repository->fetchAllByCourseID(1));
        Phake::verify($this->repository)->fetchAllByCourseIDs(array(1));
    }

    public function testFetchAllByCourseID() {
        $course_unit = new Models_Course_Unit(array("cunit_id" => 11));
        Phake::when($this->repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array(1 => array(11 => $course_unit)));
        $this->assertEquals(array(11 => $course_unit), $this->repository->fetchAllByCourseID(1));
        Phake::verify($this->repository)->fetchAllByCourseIDs(array(1));
    }

    public function testFetchAllByCourseIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByCourseIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByCourseIDs() {
        global $db;
        $row = array("cunit_id" => 1, "unit_title" => "Test Course Unit");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $course_units = $this->repository->fetchAllByCourseIDs(array(1));
        $this->assertNotEmpty($course_units);
        Phake::verify($this->repository)->fromArraysBy("course_id", array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`deleted_date` IS NULL"),
            $this->stringContains("ORDER BY `unit_order` ASC")
        ));
    }

    public function testFetchAllByCourseIDsAndCperiodID_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByCourseIDsAndCperiodID(array(), 1));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByCourseIDsAndCperiodID() {
        global $db;
        $row = array("cunit_id" => 1, "unit_title" => "Test Course Unit");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $course_units = $this->repository->fetchAllByCourseIDsAndCperiodID(array(1), 1);
        $this->assertNotEmpty($course_units);
        Phake::verify($this->repository)->fromArraysBy("course_id", array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`cperiod_id` = '1'"),
            $this->stringContains("`deleted_date` IS NULL"),
            $this->stringContains("ORDER BY `unit_order` ASC")
        ));
    }

    public function testFetchAllByCourseIDsAndCperiodID_NoCperiod() {
        global $db;
        $row = array("cunit_id" => 1, "unit_title" => "Test Course Unit");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $course_units = $this->repository->fetchAllByCourseIDsAndCperiodID(array(1), null);
        $this->assertNotEmpty($course_units);
        Phake::verify($this->repository)->fromArraysBy("course_id", array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`cperiod_id` IS NULL"),
            $this->stringContains("`deleted_date` IS NULL"),
            $this->stringContains("ORDER BY `unit_order` ASC")
        ));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Models_Repository_CourseUnitsTest::main();
}
