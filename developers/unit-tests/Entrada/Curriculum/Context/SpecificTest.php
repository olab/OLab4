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

class Entrada_Curriculum_Context_SpecificTest extends BaseTestCase {

    public function testGetEventIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        $this->assertEmpty(Phake::makeVisible($context)->getEventIDs());
    }

    public function testGetCunitIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        $this->assertEmpty(Phake::makeVisible($context)->getCunitIDs());
    }

    public function testGetCourseIDs() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        $this->assertEmpty(Phake::makeVisible($context)->getCourseIDs());
    }

    public function testGetIDTableColumn_Event() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getEventIDs()->thenReturn(array(101));
        Phake::when($context)->getCunitIDs()->thenReturn(array());
        Phake::when($context)->getCourseIDs()->thenReturn(array());
        $this->assertEquals(101, $context->getID());
        $this->assertEquals("event_linked_objectives", $context->getTable());
        $this->assertEquals("event_objectives", $context->getTaughtInTable());
        $this->assertEquals("event_id", $context->getColumn());
    }

    public function testGetIDTableColumn_CourseUnit() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getEventIDs()->thenReturn(array());
        Phake::when($context)->getCunitIDs()->thenReturn(array(11));
        Phake::when($context)->getCourseIDs()->thenReturn(array());
        $this->assertEquals(11, $context->getID());
        $this->assertEquals("course_unit_linked_objectives", $context->getTable());
        $this->assertEquals("course_unit_objectives", $context->getTaughtInTable());
        $this->assertEquals("cunit_id", $context->getColumn());
    }

    public function testGetIDTableColumn_Course() {
        $context = Phake::partialMock("Entrada_Curriculum_Context_Specific");
        Phake::when($context)->getEventIDs()->thenReturn(array());
        Phake::when($context)->getCunitIDs()->thenReturn(array());
        Phake::when($context)->getCourseIDs()->thenReturn(array(1));
        $this->assertEquals(1, $context->getID());
        $this->assertEquals("course_linked_objectives", $context->getTable());
        $this->assertEquals("course_objectives", $context->getTaughtInTable());
        $this->assertEquals("course_id", $context->getColumn());
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Entrada_Curriculum_Context_SpecificTest::main();
}
