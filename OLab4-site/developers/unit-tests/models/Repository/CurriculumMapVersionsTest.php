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

class Models_Repository_CurriculumMapVersionsTest extends BaseTestCase {

    private $repository;

    public function setUp() {
        parent::setUp();
        $this->repository = Phake::partialMock("Models_Repository_CurriculumMapVersions");
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
        $row = array("version_id" => 1, "title" => "Test Version");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $versions = $this->repository->fetchAllByIDs(array(1));
        $this->assertNotEmpty($versions);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`version_id` IN ('1')")
        ));
    }

    public function testEventsForVersion() {
        $event1 = new Models_Event(array("event_id" => 101));
        $event2 = new Models_Event(array("event_id" => 102));
        $version1 = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        $version2 = new Models_Curriculum_Map_Versions(array("version_id" => 2));
        Phake::when($this->repository)->fetchVersionsByEventIDs(Phake::anyParameters())->thenReturn(array(
            101 => array(2 => $version2),
            102 => array(1 => $version1),
        ));
        $this->assertEquals(array(102 => $event2), $this->repository->eventsForVersion(1, array(101 => $event1, 102 => $event2)));
        Phake::verify($this->repository)->fetchVersionsByEventIDs(array(101 => 101, 102 => 102));
    }

    public function testFetchLatestVersionsByEventIDs() {
        $version1 = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        $version2 = new Models_Curriculum_Map_Versions(array("version_id" => 2));
        Phake::when($this->repository)->fetchVersionsByEventIDs(Phake::anyParameters())->thenReturn(array(
            101 => array(
                2 => $version2,
                1 => $version1,
            ),
        ));
        $this->assertEquals(array(101 => $version2), $this->repository->fetchLatestVersionsByEventIDs(array(101)));
        Phake::when($this->repository)->fetchVersionsByEventIDs(array(101));
    }

    public function testFetchVersionsByEventID() {
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->repository)->fetchVersionsByEventIDs(Phake::anyParameters())->thenReturn(array(101 => array(1 => $version)));
        Phake::when($this->repository)->flatten(Phake::anyParameters())->thenReturn(array(1 => $version));
        $this->assertEquals(array(1 => $version), $this->repository->fetchVersionsByEventID(101));
        Phake::verify($this->repository)->fetchVersionsByEventIDs(array(101));
        Phake::verify($this->repository)->flatten(array(101 => array(1 => $version)));
    }

    public function testFetchVersionsByEventIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchVersionsByEventIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchVersionsByEventIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array("data"));
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->repository)->fromArraysBy(Phake::anyParameters())->thenReturn(array(101 => array(1 => $version)));
        $this->assertEquals(array(101 => array(1 => $version)), $this->repository->fetchVersionsByEventIDs(array(101)));
        Phake::verify($db)->GetAll($this->stringContains("`event_id` IN ('101')"));
        Phake::verify($this->repository)->fromArraysBy("event_id", array("data"));
    }

    public function testFetchVersionsByCourseIDCperiodID() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array("data"));
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->repository)->fromArrays(Phake::anyParameters())->thenReturn(array(1 => $version));
        $this->assertEquals(array(1 => $version), $this->repository->fetchVersionsByCourseIDCperiodID(1, 123));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cperiod_id` = '123'"),
            $this->stringContains("`course_id` = '1'")));
        Phake::verify($this->repository)->fromArrays(array("data"));
    }

    public function testFetchVersionsByDateRange() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array("data"));
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->repository)->fromArrays(Phake::anyParameters())->thenReturn(array(1 => $version));
        $this->assertEquals(array(1 => $version), $this->repository->fetchVersionsByDateRange(1000, 2000, 11));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("'1000' BETWEEN"),
            $this->stringContains("'2000' BETWEEN"),
            $this->stringContains("BETWEEN '1000' AND '2000'"),
            $this->stringContains("`course_id` = '11'")));
        Phake::verify($this->repository)->fromArrays(array("data"));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Models_Repository_CurriculumMapVersionsTest::main();
}
