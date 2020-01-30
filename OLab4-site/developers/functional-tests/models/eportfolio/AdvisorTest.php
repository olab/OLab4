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
 * This file is intended to test the <entrada-root>/www-root/core/library/Models/eportfolio/Advisor.php file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

/**
 * Class AdvisorTest
 *
 * This class contains the tests for each function in the <entrada-root>/www-root/core/library/Models/eportfolio/Advisor.php file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
require_once(dirname(__FILE__) . "/../../BaseDatabaseTestCase.php");

class AdvisorTest extends BaseDatabaseTestCase
{
    protected $data;
    /**
     * Setup and Teardown functions required by PHP Unit.
     */
    public function setup() {
        parent::setUp();
        $this->data = $this->getDataSet()->getTable("portfolio-advisors")->getRow(0);
    }
    public function tearDown() {
        parent::tearDown();
    }
    
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        require_once ("Models/Eportfolio/Advisor.php");
    }

    /**
     * Test inserting and fetching a record.
     *
     * @covers Models_Eportfolio_Advisor::fetchRow
     */
    public function test_fetchRow() {
        //expected
        $test_padvisor = new Models_Eportfolio_Advisor($this->data);
        //actual
        $advisor = Models_Eportfolio_Advisor::fetchRow(2);
        //Set the fields to empty that involve relationships to other data until we figure out how to mock this.
        $advisor->setRelated("");
        $advisor->setFirstname("");
        $advisor->setLastname("");
        $this->assertEquals($test_padvisor, $advisor, "The expected advisor was not found in the database.");
    }

    /**
     * Tests updating a record.
     *
     * @covers Models_Eportfolio_Advisor::update
     */
    public function test_update() {
        $test_padvisor = new Models_Eportfolio_Advisor($this->data);
        $test_padvisor->setActive(0);
        $test_padvisor->update();

        $expected_active = 0;

        $this->assertEquals($expected_active, $test_padvisor->getActive(), "The expected active value did not match the actual active value after update.");
    }

    static function main() {
        $suite = new PHPUnit_Framework_TestSuite(__CLASS__);
        PHPUnit_TextUI_TestRunner::run($suite);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    AdvisorTest::main();
}
