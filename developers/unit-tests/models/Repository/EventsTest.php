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

class Models_Repository_EventsTest extends BaseTestCase {

    private $repository;

    public function setUp() {
        parent::setUp();
        $this->repository = Phake::partialMock("Models_Repository_Events");
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
        $row = array("event_id" => 1, "event_title" => "Test Event");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $events = $this->repository->fetchAllByIDs(array(1));
        $this->assertNotEmpty($events);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`event_id` IN ('1')"),
            $this->stringContains("ORDER BY `event_start` ASC, `updated_date` DESC")
        ));
    }

    public function testFetchAllByCunitID_Empty() {
        Phake::when($this->repository)->fetchAllByCunitIDs(Phake::anyParameters())->thenReturn(array());
        $this->assertEquals(array(), $this->repository->fetchAllByCunitID(1));
        Phake::verify($this->repository)->fetchAllByCunitIDs(array(1));
    }

    public function testFetchAllByCunitID() {
        $event = new Models_Event(array("event_id" => 101));
        Phake::when($this->repository)->fetchAllByCunitIDs(Phake::anyParameters())->thenReturn(array(1 => array(101 => $event)));
        $this->assertEquals(array(101 => $event), $this->repository->fetchAllByCunitID(1));
        Phake::verify($this->repository)->fetchAllByCunitIDs(array(1));
    }

    public function testFetchAllByCunitIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByCunitIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByCunitIDs() {
        global $db;
        $row = array("event_id" => 101, "event_title" => "Test Event");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $events = $this->repository->fetchAllByCunitIDs(array(1));
        $this->assertNotEmpty($events);
        Phake::verify($this->repository)->fromArraysBy("cunit_id", array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`cunit_id` IN ('1')"),
            $this->stringContains("ORDER BY `event_start` ASC, `updated_date` DESC")
        ));
    }

    public function testFetchAllByCourseID_Empty() {
        Phake::when($this->repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array());
        $this->assertEquals(array(), $this->repository->fetchAllByCourseID(1));
        Phake::verify($this->repository)->fetchAllByCourseIDs(array(1));
    }

    public function testFetchAllByCourseID() {
        $event = new Models_Event(array("event_id" => 101));
        Phake::when($this->repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array(1 => array(101 => $event)));
        $this->assertEquals(array(101 => $event), $this->repository->fetchAllByCourseID(1));
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
        $row = array("event_id" => 101, "event_title" => "Test Event");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $events = $this->repository->fetchAllByCourseIDs(array(1));
        $this->assertNotEmpty($events);
        Phake::verify($this->repository)->fromArraysBy("course_id", array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("ORDER BY `event_start` ASC, `updated_date` DESC")
        ));
    }

    public function testFetchAllByObjectiveIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByObjectiveIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByObjectiveIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            "objective_id" => 11,
            "event_id" => 101,
            "event_title" => "Lecture 1",
        ));
        $events = $this->repository->fetchAllByObjectiveIDs(array(11));
        $this->assertNotEmpty($events);
        $this->assertArrayHasKey(123, $events);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`objective_id` IN ('11')"),
            $this->stringContains("ORDER BY e.`event_start` ASC, e.`updated_date` DESC")
        ));
    }

    public function testFetchAllByObjectiveIDsAndFilters_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByObjectiveIDsAndFilters(array(), array("filters")));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByObjectiveIDsAndFilters() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            "objective_id" => 11,
            "event_id" => 101,
            "event_title" => "Lecture 1",
        ));
        $filters = array(
            "course_ids" => array(1),
            "cunit_ids" => array(11),
            "start" => 1000,
            "end" => 2000,
        );
        $events = $this->repository->fetchAllByObjectiveIDsAndFilters(array(21), $filters);
        $this->assertNotEmpty($events);
        $this->assertArrayHasKey(123, $events);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`objective_id` IN ('21')"),
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`event_start` >= '1000'"),
            $this->stringContains("`event_start` + (e.`event_duration` * 60) <= '2000'"),
            $this->stringContains("ORDER BY e.`event_start` ASC, e.`updated_date` DESC")
        ));
    }

    public function testFetchTotalMappingsByEventIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            array("event_id" => 101, "objective_parent" => 1, "mappings" => 100),
            array("event_id" => 101, "objective_parent" => 2, "mappings" => 50),
            array("event_id" => 102, "objective_parent" => 1, "mappings" => 25),
            array("event_id" => 102, "objective_parent" => 2, "mappings" => 12),
        ));
        $this->assertEquals(array(), $this->repository->fetchTotalMappingsByEventIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchTotalMappingsByEventIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            array("event_id" => 101, "objective_parent" => 1, "mappings" => 100),
            array("event_id" => 101, "objective_parent" => 2, "mappings" => 50),
            array("event_id" => 102, "objective_parent" => 1, "mappings" => 25),
            array("event_id" => 102, "objective_parent" => 2, "mappings" => 12),
        ));
        $expected_mappings_by_events = array(
            101 => array(
                1 => 100,
                2 => 50,
            ),
            102 => array(
                1 => 25,
                2 => 12,
            ),
        );
        $this->assertEquals($expected_mappings_by_events, $this->repository->fetchTotalMappingsByEventIDs(array(101, 102, 103)));
        Phake::verify($db)->GetAll($this->stringContains("`event_id` IN ('101', '102', '103')"));
    }

    public function testEventTypesByEvents_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            array("event_id" => 101, "objective_parent" => 1, "mappings" => 100),
            array("event_id" => 101, "objective_parent" => 2, "mappings" => 50),
            array("event_id" => 102, "objective_parent" => 1, "mappings" => 25),
            array("event_id" => 102, "objective_parent" => 2, "mappings" => 12),
        ));
        $this->assertEquals(array(), $this->repository->fetchEventTypesByEventIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testEventTypesByEvents() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(
            array("event_id" => 101, "eventtype_id" => 1, "duration" => 30),
            array("event_id" => 101, "eventtype_id" => 2, "duration" => 20),
        ));
        $expected_event_types = array(
            101 => array(
                1 => new Models_Event_EventType(array("event_id" => 101, "eventtype_id" => 1, "duration" => 30)),
                2 => new Models_Event_EventType(array("event_id" => 101, "eventtype_id" => 2, "duration" => 20)),
            ),
        );
        $this->assertEquals($expected_event_types, $this->repository->fetchEventTypesByEventIDs(array(101)));
        Phake::verify($db)->GetAll($this->stringContains("`event_id` IN ('101')"));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Models_Repository_EventsTest::main();
}
