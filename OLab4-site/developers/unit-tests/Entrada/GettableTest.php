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

class Entrada_GettableTest extends BaseTestCase {

    public function testGetInstance() {
        $this->assertTrue(TestGettable::getInstance() instanceof TestGettable);
    }

    public function testSetInstance() {
        $old_instance = new TestGettable();
        $old_instance->random_property = "fjdklsjfdk";
        TestGettable::setInstance($old_instance);
        $new_instance = new TestGettable();
        TestGettable::setInstance($new_instance);
        $this->assertEquals($new_instance, TestGettable::getInstance());
    }
}

class TestGettable implements Entrada_IGettable {
    use Entrada_Gettable;
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Entrada_GettableTest::main();
}
