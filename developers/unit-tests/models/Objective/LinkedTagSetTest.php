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

class LinkedTagSetTest extends BaseTestCase {

    public function testFetchAllByTypeAndOrganisationID() {
        global $db;
        $row = array("type" => "event", "objective_id" => 5, "target_objective_id" => 6, "organisation_id" => 1);
        $linked_tag_set = new Models_Objective_LinkedTagSet($row);
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertEquals(array($linked_tag_set), Models_Objective_LinkedTagSet::fetchAllByTypeAndOrganisationID("event", 1));
        Phake::verify($db)->GetAll(
            $this->logicalAnd(
                $this->stringContains("`type` = ?"),
                $this->stringContains("`organisation_id` = ?"),
                $this->stringContains("`deleted_date` IS ?")),
            $this->logicalAnd(
                $this->contains("event"),
                $this->contains(1),
                $this->contains(null)));
    }

    public function testFetchAllByTypeAndOrganisationIDAndTagSetID() {
        global $db;
        $row = array("type" => "event", "objective_id" => 5, "target_objective_id" => 6, "organisation_id" => 1);
        $linked_tag_set = new Models_Objective_LinkedTagSet($row);
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertEquals(array($linked_tag_set), Models_Objective_LinkedTagSet::fetchAllByTypeAndOrganisationIDAndTagSetID("event", 1, 5));
        Phake::verify($db)->GetAll(
            $this->logicalAnd(
                $this->stringContains("`type` = ?"),
                $this->stringContains("`organisation_id` = ?"),
                $this->stringContains("`objective_id` = ?"),
                $this->stringContains("`deleted_date` IS ?")),
            $this->logicalAnd(
                $this->contains("event"),
                $this->contains(1),
                $this->contains(5),
                $this->contains(null)));
    }

    public function testFetchAllowedTagSetIDs() {
        global $db;
        $row = array("type" => "event", "objective_id" => 5, "target_objective_id" => 6, "organisation_id" => 1);
        $linked_tag_set = new Models_Objective_LinkedTagSet($row);
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn(array($row));
        $this->assertEquals(array(5 => array(6 => 6)), Models_Objective_LinkedTagSet::fetchAllowedTagSetIDs("event", 1));
        Phake::verify($db)->GetAll(
            $this->logicalAnd(
                $this->stringContains("`type` = ?"),
                $this->stringContains("`organisation_id` = ?"),
                $this->stringContains("`deleted_date` IS ?")),
            $this->logicalAnd(
                $this->contains("event"),
                $this->contains(1),
                $this->contains(null)));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    LinkedTagSetTest::main();
}
