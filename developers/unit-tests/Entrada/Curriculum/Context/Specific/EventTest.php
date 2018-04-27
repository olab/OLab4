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
    dirname(__FILE__) . "/../../../../../../www-root/core",
    dirname(__FILE__) . "/../../../../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../../../../www-root/core/library",
    dirname(__FILE__) . "/../../../../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

require_once(dirname(__FILE__) . "/../../../../BaseTestCase.php");

class Entrada_Curriculum_Context_Specific_EventTest extends BaseTestCase {

    public function setUp() {
        parent::setUp();
        $this->event_repository = Phake::mock("Models_Repository_Events");
        Phake::when($this->event_repository)->flatten(Phake::anyParameters())->thenReturnCallback(function (array $a) {
            return $a;
        });
        Models_Repository_Events::setInstance($this->event_repository);
    }

    public function testGetEventIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific_Event", 101);
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
    }

    public function testGetCunitIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific_Event", 101);
        $event = new Models_Event(array("event_id" => 101, "cunit_id" => 11));
        Phake::when($this->event_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEmpty(Phake::makeVisible($context)->getCunitIDs());
        Phake::verify($this->event_repository, Phake::never())->fetchAllByIDs(Phake::anyParameters());
    }

    public function testGetCourseIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific_Event", 101);
        $event = new Models_Event(array("event_id" => 101, "course_id" => 1));
        Phake::when($this->event_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEmpty(Phake::makeVisible($context)->getCourseIDs());
        Phake::verify($this->event_repository, Phake::never())->fetchAllByIDs(Phake::anyParameters());
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Entrada_Curriculum_Context_Specific_EventTest::main();
}
