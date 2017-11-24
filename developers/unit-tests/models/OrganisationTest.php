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
 * This class tests the functions in Models_Organisation.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Brandon Nam <brandon.nam@ubc.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
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

class OrganisationTest extends BaseTestCase {

    public function testFetchAllOrganisations() {
        global $db;
        $row = array("organisation_id" => 1, "organisation_title" => "Test Organisation");

        $expectedQuery = "SELECT a.*
                    FROM `".AUTH_DATABASE."`.`organisations` AS a
                    ORDER BY a.`organisation_title` ASC";
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertEquals(array(array("organisation_id" => 1, "organisation_title" => "Test Organisation")), Models_Organisation::fetchAllOrganisations());
        Phake::verify($db)->GetAll($expectedQuery);
    } 
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    OrganisationTest::main();
}
