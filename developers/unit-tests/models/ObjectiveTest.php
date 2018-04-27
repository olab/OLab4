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
 * This class tests the functions in Models_Objective.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Brandon Nam <brandon.nam@ubc.ca>
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

class ObjectiveTest extends BaseTestCase {

    public function testFetchRowByNameParentID() {
        global $db;
        $rows = array("objective_id" => 1);
        Phake::when($db)->GetRow(Phake::anyParameters())->thenReturn($rows);
        $objective = Models_Objective::fetchRowByNameParentID(1, "AAMC", 0);
        $this->assertTrue($objective instanceof Models_Objective);
        $this->assertEquals(1, $objective->getID());
        Phake::verify($db)->GetRow($this->stringContains("`objective_parent` = ?"), $this->contains("AAMC"));
    }

    public function testGetObjectiveText_WithCode() {
        $row = array(
            "objective_code" => "aamc-pcrs-comp-c0100",
            "objective_name" => "Patient Care",
        );
        $objective = new Models_Objective();
        $objective->fromArray($row);
        $this->assertEquals("aamc-pcrs-comp-c0100: Patient Care", $objective->getObjectiveText());
    }

    public function testGetObjectiveText_WithoutCodeInName() {
        $row = array(
            "objective_name" => "Anatomy",
            "objective_description" => "An objective that teaches about the anatomy of the human body",
        );
        $objective = new Models_Objective();
        $objective->fromArray($row);
        $this->assertEquals("Anatomy", $objective->getObjectiveText());
    }

    public function testGetObjectiveText_WithCodeInName() {
        $row = array(
            "objective_name" => "AO-0001",
            "objective_description" => "Describe the different assessment modalities in the MD Undergraduate Program",
        );
        $objective = new Models_Objective();
        $objective->fromArray($row);
        $this->assertEquals("Describe the different assessment modalities in the MD Undergraduate Program", $objective->getObjectiveText());
    }

    public function testGetObjectiveText_AlwaysShowCode() {
        $row = array(
            "objective_name" => "AO-0001",
            "objective_description" => "Describe the different assessment modalities in the MD Undergraduate Program",
        );
        $objective = new Models_Objective();
        $objective->fromArray($row);
        $this->assertEquals("AO-0001: Describe the different assessment modalities in the MD Undergraduate Program", $objective->getObjectiveText(true));
    }

    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    ObjectiveTest::main();
}
