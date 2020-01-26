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

class Models_Repository_ObjectivesTest extends BaseTestCase {

    private $repository;

    public function setUp() {
        parent::setUp();
        $this->repository = Phake::partialMock("Models_Repository_Objectives");
        Phake::when($this->repository)->flatten(Phake::anyParameters())->thenReturnCallback(function (array $a) {
            return ($a) ? current($a) : array();
        });
        Phake::when($this->repository)->fromArrays(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($this->repository)->fromArraysBy(Phake::anyParameters())->thenReturn(array(123 => array("data")));
        Phake::when($this->repository)->fromArraysByMany(Phake::anyParameters())->thenReturn(array(1 => array(123 => array("data"))));

        $this->context = Phake::mock("Entrada_Curriculum_Context");
    }

    public function testFetchAllByIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByIDs() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $objectives = $this->repository->fetchAllByIDs(array(1));
        $this->assertNotEmpty($objectives);
        Phake::verify($db)->GetAll($this->stringContains("`objective_id` IN ('1')"));
    }

    public function testFetchAllByParentIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByParentIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByParentIDs() {
        global $db;
        $row = array("objective_id" => 10, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $objectives = $this->repository->fetchAllByParentIDs(array(1));
        $this->assertNotEmpty($objectives);
        Phake::verify($db)->GetAll($this->stringContains("`objective_parent` IN ('1')"));
    }

    public function testFetchAllByParentIDsAndOrganisationID() {
        global $db;
        $row = array("objective_id" => 10, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $objectives = $this->repository->fetchAllByParentIDsAndOrganisationID(array(1), 1);
        $this->assertNotEmpty($objectives);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`objective_parent` IN ('1')"),
            $this->stringContains("`organisation_id` = '1'")));
    }

    public function testFetchAllByTagSetID() {
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective12 = new Models_Objective();
        $objective12->fromArray(array("objective_id" => 12, "objective_parent" => 11));
        $objective13 = new Models_Objective();
        $objective13->fromArray(array("objective_id" => 13, "objective_parent" => 1));
        Phake::when($this->repository)->fetchAllByParentIDs(Phake::anyParameters())->thenReturn(null);
        Phake::when($this->repository)->fetchAllByParentIDs(array(1))->thenReturn(array(11 => $objective11, 13 => $objective13));
        Phake::when($this->repository)->fetchAllByParentIDs(array(11, 13))->thenReturn(array(12 => $objective12));
        Phake::when($this->repository)->fetchAllByParentIDs(array(12))->thenReturn(array());
        $this->assertEquals(array(12 => $objective12, 13 => $objective13), $this->repository->fetchAllByTagSetID(1));
        Phake::verify($this->repository)->fetchAllByParentIDs(array(1));
        Phake::verify($this->repository)->fetchAllByParentIDs(array(11, 13));
        Phake::verify($this->repository)->fetchAllByParentIDs(array(12));
    }

    public function testFetchAllByTagSetIDAndOrganisationID() {
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective12 = new Models_Objective();
        $objective12->fromArray(array("objective_id" => 12, "objective_parent" => 11));
        $objective13 = new Models_Objective();
        $objective13->fromArray(array("objective_id" => 13, "objective_parent" => 1));
        Phake::when($this->repository)->fetchAllByParentIDsAndOrganisationID(Phake::anyParameters())->thenReturn(null);
        Phake::when($this->repository)->fetchAllByParentIDsAndOrganisationID(array(1), 1)->thenReturn(array(11 => $objective11, 13 => $objective13));
        Phake::when($this->repository)->fetchAllByParentIDsAndOrganisationID(array(11, 13), 1)->thenReturn(array(12 => $objective12));
        Phake::when($this->repository)->fetchAllByParentIDsAndOrganisationID(array(12), 1)->thenReturn(array());
        $this->assertEquals(array(12 => $objective12, 13 => $objective13), $this->repository->fetchAllByTagSetIDAndOrganisationID(1, 1));
        Phake::verify($this->repository)->fetchAllByParentIDsAndOrganisationID(array(1), 1);
        Phake::verify($this->repository)->fetchAllByParentIDsAndOrganisationID(array(11, 13), 1);
        Phake::verify($this->repository)->fetchAllByParentIDsAndOrganisationID(array(12), 1);
    }

    public function testFetchLinkedObjectivesByID_From_Empty() {
        Phake::when($this->repository)->fetchLinkedObjectivesByIDs(Phake::anyParameters())->thenReturn(array());
        $this->assertEquals(array(), $this->repository->fetchLinkedObjectivesByID("from", 10, 1, $this->context));
        Phake::verify($this->repository)->fetchLinkedObjectivesByIDs("from", array(10), 1, $this->context, false);
    }

    public function testFetchLinkedObjectivesByID_From() {
        $objectives_by_version = array(1 => array(10 => array("objectives")));
        Phake::when($this->repository)->fetchLinkedObjectivesByIDs(Phake::anyParameters())->thenReturn($objectives_by_version);
        $this->assertEquals(array("objectives"), $this->repository->fetchLinkedObjectivesByID("from", 10, 1, $this->context));
        Phake::verify($this->repository)->fetchLinkedObjectivesByIDs("from", array(10), 1, $this->context, false);
    }

    public function testFetchLinkedObjectivesByIDsFrom_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(), 1, $this->context));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDsTo_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(), 1, $this->context));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDs_To_Versioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), 1, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_To_Unversioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), null, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` IS NULL"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_To_AllVersions() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), false, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->logicalNot($this->stringContains("l.`version_id` IS NULL")),
            $this->logicalNot($this->stringContains("l.`version_id` IN")),
            $this->logicalNot($this->stringContains("l.`version_id` =")),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_To_WithContext() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), 1, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`course_id` IN ('1')")));
    }

    public function testFetchLinkedObjectivesByIDs_To_WithContext_Not_Event() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`")),
            $this->stringContains("`event_id` NOT IN ('101')"),
            $this->stringContains("`event_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDs_To_WithContext_Not_CourseUnit() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`")),
            $this->stringContains("`cunit_id` NOT IN ('11')"),
            $this->stringContains("`cunit_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDs_To_WithContext_Not_Course() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("to", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("ON l.`objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`course_id` NOT IN ('1')"),
            $this->stringContains("`course_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDs_From_Versioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), 1, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_From_Unversioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), null, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` IS NULL"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_From_AllVersions() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), false, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->logicalNot($this->stringContains("l.`version_id` IS NULL")),
            $this->logicalNot($this->stringContains("l.`version_id` IN")),
            $this->logicalNot($this->stringContains("l.`version_id` =")),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchLinkedObjectivesByIDs_From_WithContext() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), 1, $this->context));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`event_id` IS NULL"),
            $this->stringContains("`cunit_id` IS NULL"),
            $this->stringContains("`course_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDs_From_WithContext_Not_Event() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`")),
            $this->stringContains("`event_id` NOT IN ('101')")));
    }

    public function testFetchLinkedObjectivesByIDs_From_WithContext_Not_CourseUnit() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`")),
            $this->stringContains("`cunit_id` NOT IN ('11')")));
    }

    public function testFetchLinkedObjectivesByIDs_From_WithContext_Not_Course() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDs("from", array(1), 1, $this->context, true));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT o.*, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("ON l.`target_objective_id` = o.`objective_id`"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("o.`objective_active` = 1"),
            $this->stringContains("l.`version_id` = 1"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`course_id` NOT IN ('1')")));
    }

    public function testFetchLinkedObjectivesByIDs_To_EmptyObjectiveIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(), 1, array(101)));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDs_From_EmptyObjectiveIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(), 1, array(101)));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDs_To_EmptyEventIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(), 1, array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDs_From_EmptyEventIDs() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(), 1, array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_To_NoEventIDs() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(1), 1));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id` AS `objective_id`, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->logicalNot($this->stringContains("`event_id` IN"))));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_To_Versioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(1), 1, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id` AS `objective_id`, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("l.`version_id` = 1")));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_To_Unversioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(1), null, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id` AS `objective_id`, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("l.`version_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_To_AllVersions() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("to", array(1), false, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id` AS `objective_id`, l.`target_objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`target_objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->logicalNot($this->stringContains("l.`version_id` IS NULL")),
            $this->logicalNot($this->stringContains("l.`version_id` IN")),
            $this->logicalNot($this->stringContains("l.`version_id` ="))
        ));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_From_NoEventIDs() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        try {
            $this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(1), 1);
        } catch (LogicException $e) {
            return;
        } catch (Exception $e) {
            $this->fail("Wrong exception");
        }
        $this->fail("Expected exception");
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_From_Versioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(1), 1, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id` AS `objective_id`, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("l.`version_id` = 1")));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_From_Versioned_Filters() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(1), 1, array(101), array(
            "cunit_ids" => array(11),
            "course_ids" => array(1),
            "start" => 1000,
            "end" => 2000,
        )));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id` AS `objective_id`, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`event_start` >= '1000'"),
            $this->stringContains("`event_start` <= '2000'"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("l.`version_id` = 1")));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_From_Unversioned() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(1), null, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id` AS `objective_id`, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->stringContains("l.`version_id` IS NULL")));
    }

    public function testFetchLinkedObjectivesByIDsAndEvents_From_AllVersions() {
        global $db;
        $row = array("objective_id" => 1, "objective_name" => "Test Objective");
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertNotEmpty($this->repository->fetchLinkedObjectivesByIDsAndEvents("from", array(1), false, array(101)));
        Phake::verify($this->repository)->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), array($row));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id` AS `objective_id`, l.`objective_id` AS `source_objective_id`, l.`version_id`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("l.`objective_id` IN ('1')"),
            $this->stringContains("l.`active` = 1"),
            $this->logicalNot($this->stringContains("l.`version_id` IS NULL")),
            $this->logicalNot($this->stringContains("l.`version_id` IN")),
            $this->logicalNot($this->stringContains("l.`version_id` ="))));
    }

    public function testFetchHasLinks_From_Versioned() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives_have_links = $this->repository->fetchHasLinks("from", $objectives, 1, array(), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id`"),
            $this->stringContains("l.`objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` = '1'"),
            $this->stringContains("GROUP BY l.`objective_id`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchHasLinks_From_Unversioned() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives_have_links = $this->repository->fetchHasLinks("from", $objectives, null, array(), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id`"),
            $this->stringContains("l.`objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` IS NULL"),
            $this->stringContains("GROUP BY l.`objective_id`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchHasLinks_From_Exclude() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives_have_links = $this->repository->fetchHasLinks("from", $objectives, null, array(1), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id`"),
            $this->stringContains("INNER JOIN `global_lu_objectives` AS o"),
            $this->stringContains("o.`objective_parent` NOT IN ('1')"),
            $this->stringContains("l.`objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` IS NULL"),
            $this->stringContains("GROUP BY l.`objective_id`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchHasLinks_From_WithContext() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $objectives_have_links = $this->repository->fetchHasLinks("from", $objectives, 1, array(), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`objective_id`"),
            $this->stringContains("l.`objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` = '1'"),
            $this->stringContains("GROUP BY l.`objective_id`"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`course_id` IN ('1')")
        ));
    }

    public function testFetchHasLinks_To_Unversioned() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives_have_links = $this->repository->fetchHasLinks("to", $objectives, null, array(), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id`"),
            $this->stringContains("l.`target_objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` IS NULL"),
            $this->stringContains("GROUP BY l.`target_objective_id`"),
            $this->logicalNot($this->stringContains("LEFT JOIN `event_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_unit_linked_objectives`")),
            $this->logicalNot($this->stringContains("LEFT JOIN `course_linked_objectives`"))));
    }

    public function testFetchHasLinks_To_WithContext() {
        global $db;
        $rows = array(array("objective_id" => 11));
        $objectives = array(
            11 => new Models_Objective(11),
            12 => new Models_Objective(12),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        Phake::when($this->context)->getEventIDs()->thenReturn(array(101));
        Phake::when($this->context)->getCunitIDs()->thenReturn(array(11));
        Phake::when($this->context)->getCourseIDs()->thenReturn(array(1));
        $objectives_have_links = $this->repository->fetchHasLinks("to", $objectives, 1, array(), $this->context);
        $this->assertArrayHasKey(11, $objectives_have_links);
        $this->assertArrayNotHasKey(12, $objectives_have_links);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("SELECT l.`target_objective_id`"),
            $this->stringContains("l.`target_objective_id` IN ('11', '12')"),
            $this->stringContains("l.`version_id` = '1'"),
            $this->stringContains("GROUP BY l.`target_objective_id`"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_unit_linked_objectives`"),
            $this->stringContains("LEFT JOIN `course_linked_objectives`"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`cunit_id` IN ('11')"),
            $this->stringContains("`course_id` IN ('1')")
        ));
    }

    public function testPopulateHasLinks() {
        $rows = array(
            array("objective_id" => 11),
            array("objective_id" => 12),
        );
        $objectives_have_links = array(11 => 11);
        $expected_rows = array(
            array("objective_id" => 11, "has_links" => true),
            array("objective_id" => 12, "has_links" => false),
        );
        $new_rows = $this->repository->populateHasLinks($rows, $objectives_have_links);
        $this->assertEquals($expected_rows, $new_rows);
    }

    public function testFetchTotalMappingsByObjectivesTo() {
        global $db;
        $rows = array(
            array("objective_id" => 21, "event_id" => 101, "mappings" => 3),
            array("objective_id" => 22, "event_id" => 101, "mappings" => 2),
        );
        $expected_mappings_by_objective = array(
            21 => array(
                101 => 3,
            ),
            22 => array(
                101 => 2,
            ),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        Phake::when($this->repository)->fetchAllByTagSetID(Phake::anyParameters())->thenReturn(array(new Models_Objective(123)));
        $this->assertEquals(array(), $this->repository->fetchTotalMappingsByObjectivesTo(1, 2, array(), array(101)));
        $this->assertEquals(array(), $this->repository->fetchTotalMappingsByObjectivesTo(1, 2, array(21, 22, 23), array()));
        $this->assertEquals($expected_mappings_by_objective, $this->repository->fetchTotalMappingsByObjectivesTo(1, 2, array(21, 22, 23), array(101)));
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` IN ('21', '22', '23')"),
            $this->stringContains("`event_id` IN ('101')"),
            $this->stringContains("`target_objective_id` IN ('123')")));
    }

    public function testUpdateLinkedObjectives_Empty() {
        global $db;
        $objectives = array(11 => new Models_Objective(11));
        $linked_objectives = array();
        $repository = Phake::mock("Models_Repository_Objectives");
        Phake::when($repository)->insertLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->insertLinkedObjectiveContexts(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->deleteLinkedObjectiveContexts(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->deleteLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->updateLinkedObjectives(Phake::anyParameters())->thenCallParent();
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $repository->updateLinkedObjectives($objectives, $linked_objectives, 1, $context);
        Phake::verify($db)->BeginTrans();
        Phake::verify($repository)->insertLinkedObjectives(11, array(), 1);
        Phake::verify($repository)->insertLinkedObjectiveContexts(11, array(), 1, $context);
        Phake::verify($repository)->deleteLinkedObjectiveContexts(11, array(), 1, $context);
        Phake::verify($repository)->deleteLinkedObjectives(11, array(), 1, $context);
        Phake::verify($db)->CommitTrans();
    }

    public function testUpdateLinkedObjectives() {
        global $db;
        $objectives = array(11 => new Models_Objective(11));
        $linked_objectives = array(
            11 => array(
                22 => new Models_Objective(22),
                23 => new Models_Objective(23),
            ),
        );
        $repository = Phake::mock("Models_Repository_Objectives");
        Phake::when($repository)->insertLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->insertLinkedObjectiveContexts(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->deleteLinkedObjectiveContexts(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->deleteLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->updateLinkedObjectives(Phake::anyParameters())->thenCallParent();
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $repository->updateLinkedObjectives($objectives, $linked_objectives, 1, $context);
        Phake::verify($db)->BeginTrans();
        Phake::verify($repository)->insertLinkedObjectives(11, array(22, 23), 1);
        Phake::verify($repository)->insertLinkedObjectiveContexts(11, array(22, 23), 1, $context);
        Phake::verify($repository)->deleteLinkedObjectiveContexts(11, array(22, 23), 1, $context);
        Phake::verify($repository)->deleteLinkedObjectives(11, array(22, 23), 1, $context);
        Phake::verify($db)->CommitTrans();
    }

    public function testUpdateLinkedObjectives_Error() {
        global $db;
        $objectives = array(11 => new Models_Objective(11));
        $linked_objectives = array(
            11 => array(
                22 => new Models_Objective(22),
                23 => new Models_Objective(23),
            ),
        );
        $repository = Phake::mock("Models_Repository_Objectives");
        Phake::when($repository)->insertLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->insertLinkedObjectiveContexts(Phake::anyParameters())->thenThrow(new Exception());
        Phake::when($repository)->deleteLinkedObjectiveContexts(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->deleteLinkedObjectives(Phake::anyParameters())->thenReturn(false);
        Phake::when($repository)->updateLinkedObjectives(Phake::anyParameters())->thenCallParent();
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        try {
            $repository->updateLinkedObjectives($objectives, $linked_objectives, 1, $context);
        } catch (Exception $e) {
        }
        Phake::verify($db)->BeginTrans();
        Phake::verify($db)->RollbackTrans();
        Phake::verify($db, Phake::never())->CommitTrans();
    }

    public function testInsertLinkedObjectives_Empty() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->insertLinkedObjectives(11, array(), 1);
        Phake::verify($db, Phake::never())->Execute(Phake::anyParameters());
    }

    public function testInsertLinkedObjectives_Unversioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->insertLinkedObjectives(11, array(21), null);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("INSERT"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("objective_id` IN ('21')"),
            $this->stringContains("`version_id` IS NULL")));
    }

    public function testInsertLinkedObjectives_Versioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->insertLinkedObjectives(11, array(21), 1);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("INSERT"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("objective_id` IN ('21')"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testInsertLinkedObjectives_Error() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(false);
        try {
            $this->repository->insertLinkedObjectives(11, array(21), 1);
        } catch (Exception $e) {
        }
        $this->assertTrue(isset($e));
    }

    public function testDeleteLinkedObjectives_Empty() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectives(11, array(), 1, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("DELETE"),
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` = '11'"),
            $this->logicalNot($this->stringContains("`target_objective_id` NOT IN ('21')")),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("`event_id` <> '101'")));
    }

    public function testDeleteLinkedObjectives_Unversioned() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectives(11, array(21), null, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("DELETE"),
            $this->stringContains("`version_id` IS NULL"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` NOT IN ('21')"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("`event_id` <> '101'")));
    }

    public function testDeleteLinkedObjectives() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectives(11, array(21), 1, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("DELETE"),
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` NOT IN ('21')"),
            $this->stringContains("LEFT JOIN `event_linked_objectives`"),
            $this->stringContains("`event_id` <> '101'")));
    }

    public function testDeleteLinkedObjectives_Error() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(false);
        try {
            $this->repository->deleteLinkedObjectives(11, array(21), 1, $context);
        } catch (Exception $e) {
        }
        $this->assertTrue(isset($e));
    }

    public function testInsertLinkedObjectiveContexts_Empty() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        $this->repository->insertLinkedObjectiveContexts(11, array(), 1, $context);
        Phake::verify($db, Phake::never())->Execute(Phake::anyParameters());
    }

    public function testInsertLinkedObjectiveContexts_Versioned() {
        global $db, $ENTRADA_USER;
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(1);
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getTaughtInTable()->thenReturn("event_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->insertLinkedObjectiveContexts(11, array(21), 1, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`event_linked_objectives`"),
            $this->stringContains("'101'"),
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` IN ('21')")));
        Phake::verify($ENTRADA_USER)->getID();
    }

    public function testInsertLinkedObjectiveContexts_Unversioned() {
        global $db, $ENTRADA_USER;
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(1);
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getTaughtInTable()->thenReturn("event_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->insertLinkedObjectiveContexts(11, array(21), null, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`event_linked_objectives`"),
            $this->stringContains("'101'"),
            $this->stringContains("`version_id` IS NULL"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` IN ('21')")));
        Phake::verify($ENTRADA_USER)->getID();
    }

    public function testDeleteLinkedObjectiveContexts_Empty() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getTaughtInTable()->thenReturn("event_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->deleteLinkedObjectiveContexts(11, array(), 1, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`event_linked_objectives`"),
            $this->stringContains("`event_id` = '101'"),
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` = '11'")));
    }

    public function testDeleteLinkedObjectiveContexts_Versioned() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getTaughtInTable()->thenReturn("event_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->deleteLinkedObjectiveContexts(11, array(21), 1, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`event_linked_objectives`"),
            $this->stringContains("`event_id` = '101'"),
            $this->stringContains("`version_id` = '1'"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` NOT IN ('21')")));
    }

    public function testDeleteLinkedObjectiveContexts_Unversioned() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getTable()->thenReturn("event_linked_objectives");
        Phake::when($context)->getTaughtInTable()->thenReturn("event_objectives");
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->deleteLinkedObjectiveContexts(11, array(21), null, $context);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`event_linked_objectives`"),
            $this->stringContains("`event_id` = '101'"),
            $this->stringContains("`version_id` IS NULL"),
            $this->stringContains("`objective_id` = '11'"),
            $this->stringContains("`target_objective_id` NOT IN ('21')")));
    }

    public function testDeleteLinkedObjectivesNotTo_Event() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($context)->getID()->thenReturn(101);
        Phake::when($context)->getColumn()->thenReturn("event_id");
        $this->repository->deleteLinkedObjectivesNotTo(1, $context);
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectiveContextsNotToCourseUnit(Phake::anyParameters());
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectivesNotToCourseUnit(Phake::anyParameters());
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectiveContextsNotToCourse(Phake::anyParameters());
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectivesNotToCourse(Phake::anyParameters());
    }

    public function testDeleteLinkedObjectivesNotTo_CourseUnit() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($context)->getID()->thenReturn(11);
        Phake::when($context)->getColumn()->thenReturn("cunit_id");
        $this->repository->deleteLinkedObjectivesNotTo(1, $context);
        Phake::verify($this->repository)->deleteLinkedObjectiveContextsNotToCourseUnit(1, 11);
        Phake::verify($this->repository)->deleteLinkedObjectivesNotToCourseUnit(1, 11);
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectiveContextsNotToCourse(Phake::anyParameters());
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectivesNotToCourse(Phake::anyParameters());
    }

    public function testDeleteLinkedObjectivesNotTo_Course() {
        global $db;
        $context = Phake::mock("Entrada_Curriculum_Context_Specific");
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourseUnit(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectiveContextsNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($this->repository)->deleteLinkedObjectivesNotToCourse(Phake::anyParameters())->thenReturn(false);
        Phake::when($context)->getID()->thenReturn(1);
        Phake::when($context)->getColumn()->thenReturn("course_id");
        $this->repository->deleteLinkedObjectivesNotTo(1, $context);
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectiveContextsNotToCourseUnit(Phake::anyParameters());
        Phake::verify($this->repository, Phake::never())->deleteLinkedObjectivesNotToCourseUnit(Phake::anyParameters());
        Phake::verify($this->repository)->deleteLinkedObjectiveContextsNotToCourse(1, 1);
        Phake::verify($this->repository)->deleteLinkedObjectivesNotToCourse(1, 1);
    }

    public function testDeleteLinkedObjectiveContextsNotToCourseUnit_Versioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectiveContextsNotToCourseUnit(1, 11);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`cunit_id` = '11'"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testDeleteLinkedObjectiveContextsNotToCourseUnit_Unversioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectiveContextsNotToCourseUnit(null, 11);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`cunit_id` = '11'"),
            $this->stringContains("`version_id` IS NULL")));
    }

    public function testDeleteLinkedObjectivesNotToCourseUnit_Versioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectivesNotToCourseUnit(1, 11);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`cunit_id` = '11'"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testDeleteLinkedObjectivesNotToCourseUnit_Unversioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectivesNotToCourseUnit(null, 11);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`cunit_id` = '11'"),
            $this->stringContains("`version_id` IS NULL")));
    }

    public function testDeleteLinkedObjectiveContextsNotToCourse_Versioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectiveContextsNotToCourse(1, 1);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testDeleteLinkedObjectiveContextsNotToCourse_Unversioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectiveContextsNotToCourse(null, 1);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`version_id` IS NULL")));
    }

    public function testDeleteLinkedObjectivesNotToCourse_Versioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectivesNotToCourse(1, 1);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testDeleteLinkedObjectivesNotToCourse_Unversioned() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $this->repository->deleteLinkedObjectivesNotToCourse(null, 1);
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`course_id` = '1'"),
            $this->stringContains("`version_id` IS NULL")));
    }

    public function testFetchAllByEventIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByEventIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByEventIDs() {
        global $db;
        $rows = array(
            array("objective_id" => 1, "objective_name" => "Test Objective"),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives = $this->repository->fetchAllByEventIDs(array(1));
        $this->assertNotEmpty($objectives);
        Phake::verify($db)->GetAll($this->stringContains("`event_id` IN ('1')"));
    }

    public function testFetchAllByCourseUnitIDs_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByCourseUnitIDs(array()));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByCourseUnitIDs() {
        global $db;
        $rows = array(
            array("objective_id" => 1, "objective_name" => "Test Objective"),
        );
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        $objectives = $this->repository->fetchAllByCourseUnitIDs(array(1));
        $this->assertNotEmpty($objectives);
        Phake::verify($db)->GetAll($this->stringContains("`cunit_id` IN ('1')"));
    }

    public function testFetchAllByCourseIDsAndCperiodID_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $this->assertEmpty($this->repository->fetchAllByCourseIDsAndCperiodID(array(), 123));
        Phake::verify($db, Phake::never())->GetAll(Phake::anyParameters());
    }

    public function testFetchAllByCourseIDsAndCperiodID() {
        global $db;
        $row = array("course_id" => 1, "objective_id" => 1, "objective_name" => "Test Objective");
        $rows = array($row);
        $expected_objectives = array(1 => array(1 => new Models_Objective($row)));
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        Phake::when($this->repository)->fromArraysBy("course_id", $rows)->thenReturn($expected_objectives);
        $objectives = $this->repository->fetchAllByCourseIDsAndCperiodID(array(1), 123);
        $this->assertEquals($expected_objectives, $objectives);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`cperiod_id` = '123'")));
    }

    public function testFetchAllByCourseIDsAndCperiodID_NoCperiod() {
        global $db;
        $row = array("course_id" => 1, "objective_id" => 1, "objective_name" => "Test Objective");
        $rows = array($row);
        $expected_objectives = array(1 => array(1 => new Models_Objective($row)));
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);
        Phake::when($this->repository)->fromArraysBy("course_id", $rows)->thenReturn($expected_objectives);
        $objectives = $this->repository->fetchAllByCourseIDsAndCperiodID(array(1), null);
        $this->assertEquals($expected_objectives, $objectives);
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`course_id` IN ('1')"),
            $this->stringContains("`cperiod_id` IS NULL")));
    }

    public function testFetchTagSetByObjectives() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        $objective22 = new Models_Objective();
        $objective22->fromArray(array("objective_id" => 22, "objective_parent" => 21));
        Phake::when($repository)->fetchAllByIDs(array(11 => 1, 21 => 2, 22 => 21))->thenReturn(array(1 => $tag_set1, 2 => $tag_set2, 21 => $objective21));
        Phake::when($repository)->fetchAllByIDs(array(11 => 1, 21 => 2, 22 => 2))->thenReturn(array(1 => $tag_set1, 2 => $tag_set2));
        Phake::when($repository)->fetchAllByIDs(array(21 => 2))->thenReturn(array(2 => $tag_set2));
        $expected_objective_tag_sets = array(
            11 => $tag_set1,
            21 => $tag_set2,
            22 => $tag_set2,
        );
        $this->assertEquals($expected_objective_tag_sets, $repository->fetchTagSetByObjectives(array(11 => $objective11, 21 => $objective21, 22 => $objective22)));
        Phake::verify($repository)->fetchAllByIDs(array(11 => 1, 21 => 2, 22 => 21));
        Phake::verify($repository, Phake::never())->fetchAllByIDs(array(11 => 1, 21 => 2, 22 => 2));
        Phake::verify($repository)->fetchAllByIDs(array(21 => 2));
    }

    public function testFetchSearchTagSetByObjectives() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(
            array(
                "objective_id" => 1,
                "objective_name" => "Activity Objectives",
                "objective_parent" => 0
            )
        );
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(
            array(
                "objective_id" => 2,
                "objective_name" => "Week Objectives",
                "objective_parent" => 0
            )
        );
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        $objective22 = new Models_Objective();
        $objective22->fromArray(array("objective_id" => 22, "objective_parent" => 21));
        Phake::when($repository)->fetchAllByIDs(
            array(
                11 => null,
                21 => null,
                22 => null
            )
        )->thenReturn(
            array(
                1 => $tag_set1,
                2 => $tag_set2,
                21 => $objective21
            )
        );
        Phake::when($repository)->fetchAllByIDs(
            array(
                11 => null,
                21 => null,
                22 => null
            ))->thenReturn(
            array(
                1 => $tag_set1,
                2 => $tag_set2
            )
        );
        Phake::when($repository)->fetchAllByIDs(
            array(
                21 => 2
            ))->thenReturn(
            array(
                2 => $tag_set2
            )
        );
        $expected_objective_tag_sets = array(
            1 => null,
            2 => null
        );
        $this->assertEquals(
            $expected_objective_tag_sets,
            $repository->fetchSearchTagSetByObjectives(
                array(11 => $objective11,
                    21 => $objective21,
                    22 => $objective22
                ), 1
            )
        );
    }

    public function testGroupByTagSet() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        Phake::when($repository)->fetchTagSetByObjectives(Phake::anyParameters())->thenReturn(array(11 => $tag_set1, 21 => $tag_set2));
        $expected_objectives_by_tag_sets = array(
            "Activity Objectives" => array(
                11 => $objective11,
            ),
            "Week Objectives" => array(
                21 => $objective21,
            ),
        );
        $this->assertEquals($expected_objectives_by_tag_sets, $repository->groupByTagSet(array(11 => $objective11, 21 => $objective21)));
        Phake::verify($repository)->fetchTagSetByObjectives(array(11 => $objective11, 21 => $objective21));
    }

    public function testGroupArraysByTagSet() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        $row11 = $objective11->toArray();
        $row21 = $objective21->toArray();
        Phake::when($repository)->fetchTagSetByObjectives(Phake::anyParameters())->thenReturn(array(11 => $tag_set1, 21 => $tag_set2));
        $expected_objectives_by_tag_sets = array(
            "Activity Objectives" => array(
                11 => $row11,
            ),
            "Week Objectives" => array(
                21 => $row21,
            ),
        );
        $this->assertEquals($expected_objectives_by_tag_sets, $repository->groupArraysByTagSet(array(11 => $row11, 21 => $row21)));
        Phake::verify($repository)->fetchTagSetByObjectives(array(11 => $objective11, 21 => $objective21));
    }

    public function testGroupIDsByTagSet() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        Phake::when($repository)->fetchTagSetByObjectives(Phake::anyParameters())->thenReturn(array(11 => $tag_set1, 21 => $tag_set2));
        $expected_objectives_by_tag_sets = array(
            1 => array(
                11 => 11,
            ),
            2 => array(
                21 => 21,
            ),
        );
        $this->assertEquals($expected_objectives_by_tag_sets, $repository->groupIDsByTagSet(array(11 => $objective11, 21 => $objective21)));
        Phake::verify($repository)->fetchTagSetByObjectives(array(11 => $objective11, 21 => $objective21));
    }

    public function testExcludeByTagSetIDs() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        Phake::when($repository)->fetchTagSetByObjectives(Phake::anyParameters())->thenReturn(array(11 => $tag_set1, 21 => $tag_set2));
        $expected_objectives = array(
            11 => $objective11,
        );
        $this->assertEquals($expected_objectives, $repository->excludeByTagSetIDs(array(11 => $objective11, 21 => $objective21), array(2)));
        Phake::verify($repository)->fetchTagSetByObjectives(array(11 => $objective11, 21 => $objective21));
    }

    public function testSearchExcludeByTagSetIDs() {
        $repository = Phake::partialMock("Models_Repository_Objectives");
        $tag_set1 = new Models_Objective();
        $tag_set1->fromArray(array("objective_id" => 1, "objective_name" => "Activity Objectives", "objective_parent" => 0));
        $tag_set2 = new Models_Objective();
        $tag_set2->fromArray(array("objective_id" => 2, "objective_name" => "Week Objectives", "objective_parent" => 0));
        $objective11 = new Models_Objective();
        $objective11->fromArray(array("objective_id" => 11, "objective_parent" => 1));
        $objective21 = new Models_Objective();
        $objective21->fromArray(array("objective_id" => 21, "objective_parent" => 2));
        Phake::when($repository)->fetchSearchTagSetByObjectives(Phake::anyParameters())->thenReturn(array(11 => $tag_set1, 21 => $tag_set2));
        $expected_objectives = array(
            11 => $objective11,
            21 => $objective21
        );
        $this->assertEquals($expected_objectives, $repository->searchExcludeByTagSetIDs(array(11 => $objective11, 21 => $objective21), array(2)));
        Phake::verify($repository)->fetchSearchTagSetByObjectives(array(11 => $objective11, 21 => $objective21), 1);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Models_Repository_ObjectivesTest::main();
}
