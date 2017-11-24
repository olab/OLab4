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
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine
 * @author Developer: Geoff Harvey <gharvey@uottawa.ca>
 * @copyright Copyright 2015 University of Ottawa. All Rights Reserved.
 *
 */

/**
 * Class CountryTest
 *
 * This class contains tests for the Country model in <entrada-root>/www-root/core/library/Models/region/Country.class.php file.
 *
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine
 * @author Developer: Geoff Harvey <gharvey@uottawa.ca>
 * @copyright Copyright 2015 University of Ottawa. All Rights Reserved.
 */
require_once(dirname(__FILE__) . "/../../BaseDatabaseTestCase.php");

class CountryTest extends BaseDatabaseTestCase
{
    protected $data;
    /**
     * Setup and Teardown functions required by PHP Unit.
     */
    public function setup() {
        parent::setUp();
        $this->data = $this->getDataSet()->getTable("global_lu_countries")->getRow(0);
        
    }
    public function tearDown() {
        parent::tearDown();
    }
    
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        require_once ("Models/regions/Region.class.php");
        require_once ("Models/regions/Country.class.php");        
    }

    /**
     * Loads an instance of country from fixture and tests that it matches expected values.
     * @test
     */
    public function fixtureVsExpectedValues () {
        $aCountry = Country::get(1);
        $anExpectedCountry = new Country ("Afghanistan", "1", "AFG", "AF", 4);
        $this->assertEquals($anExpectedCountry, $aCountry, "Expected values do not match values from fixture!");
    }

    /**
     * Loads an instance of country from fixture and tests that various properties respond as expected.
     * @test
     * @depends fixtureVsExpectedValues
     */
    public function checkAbbreviation () {
        $aCountry = Country::get(1);
        
        $this->assertEquals ("AFG", $aCountry->getAbbreviation(), "Abbreviation does not match expected value of AFG!");
    }
    
    /**
     * Loads an instance of country from fixture and tests that various properties respond as expected.
     * @test
     * @depends fixtureVsExpectedValues
     */
    public function checkISO3 () {
        $aCountry = Country::get(1);
        
        $this->assertEquals ("AFG", $aCountry->getIso3(), "Abbreviation does not match expected value of AFG!");
    }
    
    /**
     * Loads an instance of country from fixture and tests that various properties respond as expected.
     * @test
     * @depends fixtureVsExpectedValues
     */
    public function checkISO2 () {
        $aCountry = Country::get(1);
        
        $this->assertEquals ("AF", $aCountry->getIso2(), "ISO2 does not match expected value of AF!");
    }
    
    /**
     * Loads an instance of country from fixture and tests that various properties respond as expected.
     * @test
     * @depends fixtureVsExpectedValues
     */
    public function checkISONum () {
        $aCountry = Country::get(1);
        
        $this->assertEquals (4, $aCountry->getIsonum(), "ISO Numeric does not match expected value of 4!");
    }
    
    /**
     * Creates and runs a test framework containing this class.
     */
    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    CountryTest::main();
}
