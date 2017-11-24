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
 * Based on earlier work by Don Zuiker <don.zuiker@queensu.ca> with Queens University.
 * 
 * PLEASE NOTE: all unit tests must be in files with filenames that end with 'Test.php',
 * or they won't be picked up by phpunit.xml, despite being in the right directory.
 *
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine
 * @author Developer: Geoff Harvey <gharvey@uottawa.ca>
 * @copyright Copyright 2015 University of Ottawa. All Rights Reserved.
 *
 */

//The values below are required for both autoloaders.
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../www-root/core",
    dirname(__FILE__) . "/../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../www-root/core/library",
    dirname(__FILE__) . "/../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists("PHPUnit_Framework_TestCase")) {
    /**
     * Register the Composer autoloader.
     */
    require_once("autoload.php");
}

require_once(dirname(__FILE__) . "/../BaseTestCase.php");

/**
 * Class Functions_New_Test
 *
 * This class contains the tests for recently changed functions
 * in the <entrada-project-root>/www-root/core/includes/functions.inc.php file.
 *
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine
 * @author Developer: Geoff Harvey <gharvey@uottawa.ca>
 * @copyright Copyright 2015 University of Ottawa. All Rights Reserved.
 */
class Functions_new_Test extends BaseTestCase
{
    public function setUp() {
        parent::setUp();
    }
    
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * The following tests are a series of rough functional tests for clean_input's
     * allowedtags rule. This test establishes whether html_Purifier will remove a div
     * tag as expected.
     * @test
     */
    public function allowedTag () {
        $this->assertTrue(clean_input('<span><del><div></div></del></span>', "allowedtags") == '<span><del></del></span>', "clean_input provided unexpected output from html_purifier!");
    }

    /**
     * The following tests are a series of rough functional tests for clean_input's
     * allowedtags rule. This test, borrowed from http://htmlpurifier.org/live/smoketests/xssAttacks.php,
     * tests whether an alert script is filtered out by html_purifier.
     * @test
     */
    public function allowedTag_02 () {
        $this->assertTrue(clean_input('<SCRIPT>alert(\'XSS\')</SCRIPT>', "allowedtags") == '', "clean_input provided unexpected output from html_purifier!");
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Functions_new_Test::main();
}
