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

class RepositoryTest extends BaseTestCase {

    public function testFetchOneByID_Empty() {
        $repository = Phake::mock("TestRepository");
        Phake::when($repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array());
        Phake::when($repository)->fetchOneByID(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(false, $repository->fetchOneByID(1));
        Phake::verify($repository)->fetchAllByIDs(array(1));
    }

    public function testFetchOneByID() {
        $repository = Phake::mock("TestRepository");
        $model = new TestModel();
        Phake::when($repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn(array(1 => $model));
        Phake::when($repository)->fetchOneByID(Phake::anyParameters())->thenCallParent();
        $this->assertEquals($model, $repository->fetchOneByID(1));
        Phake::verify($repository)->fetchAllByIDs(array(1));
    }

    public function testToArrays() {
        $repository = new TestRepository();
        $this->assertEquals(array(1 => array("id" => 1)), $repository->toArrays(array(1 => new TestModel())));
    }

    public function testFromArrays() {
        $repository = Phake::mock("TestRepository");
        Phake::when($repository)->fromArraysByMany(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($repository)->fromArrays(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(array("data"), Phake::makeVisible($repository)->fromArrays(array(array("event_id" => 100))));
        Phake::verify($repository)->fromArraysByMany(array(), array(array("event_id" => 100)));
    }

    public function testFromArraysBy() {
        $repository = Phake::mock("TestRepository");
        Phake::when($repository)->fromArraysByMany(Phake::anyParameters())->thenReturn(array("data"));
        Phake::when($repository)->fromArraysBy(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(array("data"), Phake::makeVisible($repository)->fromArraysBy("event_id", array(array("event_id" => 100))));
        Phake::verify($repository)->fromArraysByMany(array("event_id"), array(array("event_id" => 100)));
    }

    public function testFromArraysByMany_Error() {
        try {
            $repository = Phake::mock("TestRepository");
            Phake::when($repository)->fromArraysByMany(Phake::anyParameters())->thenCallParent();
            Phake::makeVisible($repository)->fromArraysBy(array("version_id", "event_id"), false);
            $this->fail();
        } catch (Exception $e) {
        }
    }

    public function testFromArraysByMany() {
        $repository = Phake::mock("TestRepository");
        Phake::when($repository)->fromArray(Phake::anyParameters())->thenCallParent();
        Phake::when($repository)->fromArraysByMany(Phake::anyParameters())->thenCallParent();
        $objects = Phake::makeVisible($repository)->fromArraysByMany(array("version_id", "event_id"), array(array("version_id" => 7, "event_id" => 100)));
        $this->assertNotEmpty($objects);
        $this->assertArrayHasKey(7, $objects);
        $this->assertArrayHasKey(100, $objects[7]);
        $this->assertArrayHasKey(1, $objects[7][100]);
        $this->assertTrue($objects[7][100][1] instanceof TestModel);
    }

    public function testQuoteIDs() {
        $repository = Phake::mock("TestRepository");
        Phake::when($repository)->quoteIDs(Phake::anyParameters())->thenCallParent();
        $this->assertEquals("'1', '2', '3'", Phake::makeVisible($repository)->quoteIDs(array(1, 2, 3)));
    }

    public function testFlatten() {
        $repository = new TestRepository();
        $data = array(
            1 => array(
                11 => (object)array("id" => 11),
            ),
            2 => array(
                11 => (object)array("id" => 11),
                12 => (object)array("id" => 12),
            ),
        );
        $expected_data = array(
            11 => (object)array("id" => 11),
            12 => (object)array("id" => 12),
        );
        $this->assertEquals($expected_data, $repository->flatten($data));
    }
}

class TestRepository extends Models_Repository {

    protected function fromArray(array $result) {
        return new TestModel($result);
    }

    public function fetchAllByIDs(array $ids) {
        return array();
    }
}

class TestModel extends Models_Base {

    public function getID() {
        return 1;
    }

    public function toArray() {
        return array("id" => 1);
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    RepositoryTest::main();
}
