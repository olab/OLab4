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
 * This class tests the functions in Views_UI_PickList.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");

class PickListTest extends BaseTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testRenderSelected() {
        ob_start();
        Views_UI_PickList::render(
            "course",
            "course_ids",
            "Courses Included:",
            array(123 => array("name" => "MEDD 411 ghjkl1")),
            array(123),
            function($course) { return $course["name"]; } );
        $html = ob_get_contents();
        ob_end_clean();
        $this->assertContains("MEDD 411 ghjkl1", $html);
    }

    public function testRenderUnselected() {
        ob_start();
        Views_UI_PickList::render(
            "course",
            "course_ids",
            "Courses Included:",
            array(124 => array("name" => "MEDD 412 ghjkl2")),
            array(),
            function($course) { return $course["name"]; } );
        $html = ob_get_contents();
        ob_end_clean();
        $this->assertContains("MEDD 412 ghjkl2", $html);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    PickListTest::main();
}
