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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../BaseTestCase.php");

class BatchUnitTest extends BaseTestCase
{
    public function testAddStatistics() {
        $statistic_model = Phake::mock("Models_Statistic");
        Phake::when($statistic_model)->addStatistic(Phake::anyParameters())->thenReturn(true);
        $batch_unit = new Entrada_BatchUnit($statistic_model);
        $batch_unit->addStatistics(array(array("efile_id" => 123, "file_name" => "bob.txt")));
        Phake::verify($statistic_model)->addStatistic("events", "file_download", "file_id", 123);
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    BatchUnitTest::main();
}
