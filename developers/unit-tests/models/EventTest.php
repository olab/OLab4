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

class EventTest extends BaseTestCase {

    public function testGetObjectives() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByEventIDs(Phake::anyParameters())->thenReturn(array(100 => array("objectives")));
        $event = new Models_Event(array("event_id" => 100));
        $this->assertEquals(array("objectives"), $event->getObjectives());
        Phake::verify($objective_repository)->fetchAllByEventIDs(array(100));
    }

    public function testGetObjectives_Empty() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByEventIDs(Phake::anyParameters())->thenReturn(array());
        $event = new Models_Event(array("event_id" => 100));
        $this->assertEquals(array(), $event->getObjectives());
        Phake::verify($objective_repository)->fetchAllByEventIDs(array(100));
    }

    public function testGetCperiodID() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array(array("cperiod_id" => 123)));
        $event = new Models_Event(array("event_id" => 100, "event_start" => 1000, "course_id" => 1));
        $this->assertEquals(123, $event->getCperiodID());
        Phake::verify($db)->GetAll(
            $this->logicalAnd($this->stringContains("curriculum_periods"), $this->stringContains("course_audience")),
            $this->logicalAnd($this->contains(1000), $this->contains(1)));
    }

    public function testGetCperiodID_Empty() {
        global $db;
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array());
        $event = new Models_Event(array("event_id" => 100, "event_start" => 1000, "course_id" => 1));
        $this->assertEquals(null, $event->getCperiodID());
        Phake::verify($db)->GetAll(
            $this->logicalAnd($this->stringContains("curriculum_periods"), $this->stringContains("course_audience")),
            $this->logicalAnd($this->contains(1000), $this->contains(1)));
    }

    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    EventTest::main();
}
