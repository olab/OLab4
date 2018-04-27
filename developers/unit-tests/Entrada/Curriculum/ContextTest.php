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

class Entrada_Curriculum_ContextTest extends BaseTestCase {

    private $event_repository;
    private $course_unit_repository;

    public function setUp() {
        parent::setUp();
        $this->event_repository = Phake::mock("Models_Repository_Events");
        Models_Repository_Events::setInstance($this->event_repository);
        $this->course_unit_repository = Phake::mock("Models_Repository_CourseUnits");
        Models_Repository_CourseUnits::setInstance($this->course_unit_repository);
    }

    public function testGetEventIDs_ForEvent() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1, "cunit_id" => 11, "event_id" => 101));
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
    }

    public function testGetEventIDs_ForCourseUnit() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1, "cunit_id" => 11));
        $event = new Models_Event(array("event_id" => 101));
        Phake::when($this->event_repository)->fetchAllByCunitIDs(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($this->event_repository)->flatten(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
        Phake::verify($this->event_repository, Phake::times(1))->fetchAllByCunitIDs(array(11));
        Phake::verify($this->event_repository, Phake::times(1))->flatten(array("data"));
    }

    public function testGetEventIDs_ForCourse() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1));
        $event = new Models_Event(array("event_id" => 101));
        Phake::when($this->event_repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($this->event_repository)->flatten(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
        $this->assertEquals(array(101), Phake::makeVisible($context)->getEventIDs());
        Phake::verify($this->event_repository, Phake::times(1))->fetchAllByCourseIDs(array(1));
        Phake::verify($this->event_repository, Phake::times(1))->flatten(array("data"));
    }

    public function testGetEventIDs_Empty() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array());
        $this->assertEquals(array(), Phake::makeVisible($context)->getEventIDs());
        $this->assertEquals(array(), Phake::makeVisible($context)->getEventIDs());
    }

    public function testGetCunitIDs_ForEvent() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1, "event_id" => 101));
        $event = new Models_Event(array("event_id" => 101, "cunit_id" => 11));
        Phake::when($this->event_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
        Phake::verify($this->event_repository, Phake::times(1))->fetchAllByIDs(array(101));
    }

    public function testGetCunitIDs_ForCourseUnit() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1, "cunit_id" => 11, "event_id" => 101));
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
    }

    public function testGetCunitIDs_ForCourse() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1));
        $course_unit = new Models_Course_Unit(array("cunit_id" => 11));
        Phake::when($this->course_unit_repository)->fetchAllByCourseIDs(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($this->course_unit_repository)->flatten(Phake::anyParameters())->thenReturn(array($course_unit));
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
        $this->assertEquals(array(11), Phake::makeVisible($context)->getCunitIDs());
        Phake::verify($this->course_unit_repository, Phake::times(1))->fetchAllByCourseIDs(array(1));
        Phake::verify($this->course_unit_repository, Phake::times(1))->flatten(array("data"));
    }

    public function testGetCunitIDs_Empty() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array());
        $this->assertEquals(array(), Phake::makeVisible($context)->getCunitIDs());
        $this->assertEquals(array(), Phake::makeVisible($context)->getCunitIDs());
    }

    public function testGetCourseIDs_ForEvent() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("event_id" => 101, "cunit_id" => 11));
        $event = new Models_Event(array("event_id" => 101, "cunit_id" => 11, "course_id" => 1));
        Phake::when($this->event_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($event));
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
        Phake::verify($this->event_repository, Phake::times(1))->fetchAllByIDs(array(101));
    }

    public function testGetCourseIDs_ForCourseUnit() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("cunit_id" => 11));
        $course_unit = new Models_Course_Unit(array("cunit_id" => 11, "course_id" => 1));
        Phake::when($this->course_unit_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($course_unit));
        Phake::when($this->course_unit_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array($course_unit));
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
        Phake::verify($this->course_unit_repository, Phake::times(1))->fetchAllByIDs(array(11));
    }

    public function testGetCourseIDs_ForCourse() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array("course_id" => 1, "cunit_id" => 11, "event_id" => 101));
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
        $this->assertEquals(array(1), Phake::makeVisible($context)->getCourseIDs());
    }

    public function testGetCourseIDs_Empty() {
        $context = Phake::partialMock("Entrada_Curriculum_Context", array());
        $this->assertEquals(array(), Phake::makeVisible($context)->getCourseIDs());
        $this->assertEquals(array(), Phake::makeVisible($context)->getCourseIDs());
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    ContextTest::main();
}
