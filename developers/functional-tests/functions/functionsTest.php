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
 * This file is intended to test the <entrada-root>/www-root/core/includes/functions.inc.php file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
require_once(dirname(__FILE__) . "/../BaseDatabaseTestCase.php");

/**
 * Class FunctionsTest
 *
 * This class contains the tests for each function in the <entrada-root>/www-root/core/includes/functions.inc.php file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class FunctionsTest extends BaseDatabaseTestCase
{
    /**
     * Setup and Teardown functions required by PHP Unit.
     */
    public function setup() {
        parent::setUp();
    }
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * Tests that the events_fetch_event_attendance_for_user function returns the expected
     * results.
     *
     */
    public function test_events_fetch_event_attendance_for_user() {
        //expected
        $data_set = $this->getDataSet()->getTable("event_attendance")->getRow(0);
        //actual
        $event_attendance = events_fetch_event_attendance_for_user(1, 1);

        $this->assertEquals($data_set, $event_attendance, "The expected event attendance for user did not match the actual results.");
    }

    /**
     * Serves as a failing test for the continuous integration server.
     *
     * @coversNothing
     */
    public function test_failing_test() {
        $this->assertEquals(0, 1);
    }
}
