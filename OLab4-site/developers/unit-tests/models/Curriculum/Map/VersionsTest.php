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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../../../www-root/core",
    dirname(__FILE__) . "/../../../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../../../www-root/core/library",
    dirname(__FILE__) . "/../../../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

require_once(dirname(__FILE__) . "/../../../BaseTestCase.php");

class VersionsTest extends BaseTestCase
{
    public function setUp() {
        parent::setUp();
        global $db;
        Phake::when($db)->qstr(Phake::anyParameters())->thenReturnCallback(function ($str) { return sprintf("'%s'", $str); });
    }

    public function testFetchRowByID() {
        global $db;
        Phake::when($db)->GetRow($this->anything(), array(1, 1))->thenReturn(array("version_id" => 1));
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->fetchRowByID(Phake::anyParameters())->thenCallParent();
        $version = Phake::makeVisible($version_model)->fetchRowByID(1, 1);
        $this->assertTrue($version instanceof Models_Curriculum_Map_Versions);
        $this->assertEquals(1, $version->getID());
    }

    public function testFetchAllRecords() {
        global $db;
        $rows = array(array("version_id" => 1));
        Phake::when($db)->GetAll($this->anything(), array(1))->thenReturn($rows);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->fetchAllRecords(Phake::anyParameters())->thenCallParent();
        $this->assertEquals($rows, Phake::makeVisible($version_model)->fetchAllRecords(1));
    }

    public function testInsertOrganisation() {
        global $db;
        Phake::when($db)->AutoExecute("curriculum_map_version_organisations", array("version_id" => 1, "organisation_id" => 1), "INSERT")->thenReturn("foo");
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getID()->thenReturn(1);
        Phake::when($version_model)->insertOrganisation(Phake::anyParameters())->thenCallParent();
        $this->assertEquals("foo", Phake::makeVisible($version_model)->insertOrganisation(1));
    }

    public function testCopyUnversionedLinkedObjectives() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn("foo");
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getVersionID()->thenReturn(1);
        Phake::when($version_model)->copyUnversionedLinkedObjectives(Phake::anyParameters())->thenCallParent();
        $this->assertEquals("foo", Phake::makeVisible($version_model)->copyUnversionedLinkedObjectives());
        Phake::verify($db)->Execute($this->stringContains("'1',"));
    }

    public function testCopyLinkedObjectives() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn("foo");
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getVersionID()->thenReturn(1);
        Phake::when($version_model)->copyLinkedObjectives(Phake::anyParameters())->thenCallParent();
        $this->assertEquals("foo", Phake::makeVisible($version_model)->copyLinkedObjectives(1));
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("'1',"),
            $this->stringContains("`version_id` = '1'")));
    }

    public function testDelete() {
        global $db, $ENTRADA_USER;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn("foo");
        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID()->thenReturn(1);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->delete(Phake::anyParameters())->thenCallParent();
        $this->assertEquals("foo", Phake::makeVisible($version_model)->delete(array(1, 2, 3)));
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("`deleted_date` = "),
            $this->stringContains("`version_id` IN (1, 2, 3)")), array(1));
    }

    public function testFetchPeriods() {
        global $db;
        $results = array(array("cperiod_id" => 1), array("cperiod_id" => 2));
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($results);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getID()->thenReturn(1);
        Phake::when($version_model)->fetchPeriods(Phake::anyParameters())->thenCallParent();
        $periods = Phake::makeVisible($version_model)->fetchPeriods();
        $this->assertEquals(2, count($periods));
        $this->assertEquals("Models_Curriculum_Period", get_class($periods[0]));
        $this->assertEquals("Models_Curriculum_Period", get_class($periods[1]));
        $this->assertEquals(1, $periods[0]->getCperiodID());
        $this->assertEquals(2, $periods[1]->getCperiodID());
        Phake::verify($db)->GetAll($this->anything(), array(1));
    }

    public function testFetchPeriodIDs() {
        global $db;
        $periods = array(
            Phake::mock("Models_Curriculum_Period"),
            Phake::mock("Models_Curriculum_Period"),
        );
        Phake::when($periods[0])->getCperiodID()->thenReturn(2);
        Phake::when($periods[1])->getCperiodID()->thenReturn(1);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->fetchPeriods()->thenReturn($periods);
        Phake::when($version_model)->fetchPeriodIDs(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(array(2, 1), Phake::makeVisible($version_model)->fetchPeriodIDs());
    }

    public function testInsertPeriods() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getID()->thenReturn(1);
        Phake::when($version_model)->insertPeriods(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(true, Phake::makeVisible($version_model)->insertPeriods(array(2, 1, 3)));
        Phake::verify($db)->Execute($this->stringContains("INSERT"), array(1, 2));
        Phake::verify($db)->Execute($this->stringContains("INSERT"), array(1, 1));
        Phake::verify($db)->Execute($this->stringContains("INSERT"), array(1, 3));
    }

    public function testDeletePeriods() {
        global $db;
        Phake::when($db)->Execute(Phake::anyParameters())->thenReturn(true);
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getID()->thenReturn(1);
        Phake::when($version_model)->deletePeriods(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(true, Phake::makeVisible($version_model)->deletePeriods(array(2, 1, 3)));
        Phake::verify($db)->Execute($this->logicalAnd(
            $this->stringContains("DELETE"),
            $this->logicalOr(
                $this->stringContains("cperiod_id IN (2, 1, 3)"),
                $this->stringContains("`cperiod_id` IN (2, 1, 3)"))), array(1));
    }

    public function testUpdatePeriods() {
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->fetchPeriodIDs()->thenReturn(array(4, 1, 2));
        Phake::when($version_model)->insertPeriods(Phake::anyParameters())->thenReturn(true);
        Phake::when($version_model)->deletePeriods(Phake::anyParameters())->thenReturn(true);
        Phake::when($version_model)->updatePeriods(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(true, Phake::makeVisible($version_model)->updatePeriods(array(3, 2, 1)));
        Phake::verify($version_model)->insertPeriods(array(3));
        Phake::verify($version_model)->deletePeriods(array(4));
    }

    public function testGetPublishedVersionByPeriods() {
        global $db;
        Phake::when($db)->GetRow(Phake::anyParameters())->thenReturn(array("version_id" => 1));
        $version_model = Phake::mock("Models_Curriculum_Map_Versions");
        Phake::when($version_model)->getID()->thenReturn(1);
        Phake::when($version_model)->getPublishedVersionByPeriods(Phake::anyParameters())->thenCallParent();
        $version = Phake::makeVisible($version_model)->getPublishedVersionByPeriods(array(2, 1, 3));
        $this->assertTrue($version instanceof Models_Curriculum_Map_Versions);
        $this->assertEquals(1, $version->getID());
        Phake::verify($db)->GetRow($this->logicalAnd(
            $this->stringContains("`version_id` <> 1"),
            $this->stringContains("`cperiod_id` IN (2, 1, 3)")));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    VersionsTest::main();
}
