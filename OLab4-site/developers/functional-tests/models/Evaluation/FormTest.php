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
 * This file is intended to test the <entrada-root>/www-root/core/library/Models/Evaluation/Form.php file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

/**
 * Class FormTest
 *
 * This class contains tests for Models_Evaluation_Form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

require_once(dirname(__FILE__) . "/../../BaseDatabaseTestCase.php");

class FormTest extends BaseDatabaseTestCase
{
    protected $data;
    /**
     * Setup and Teardown functions required by PHP Unit.
     */
    public function setup() {
        parent::setUp();
        $temp = $this->getDataSet()->getTable("evaluation_forms");
        //var_dump($temp);
        //var_dump($temp->getRow(0));
        $this->data = $temp->getRow(0);
        //$this->data = $this->getDataSet()->getTable("evaluation_forms")->getRow(0);
    }
    public function tearDown() {
        parent::tearDown();
    }
    
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    /**
     * This test extracts properties from entrada.xml and verifies the property values are as expected.
     *
     * @test
     * @covers Models_Evaluation_Form::__construct
     */
    public function propertyLoad() {
        //actual
        $form = new Models_Evaluation_Form($this->data);
   
        $this->assertEquals($form->getFormID(), 1, "The form id was not 1 as expected.");
        $this->assertEquals($form->getOrganisationID(), 1, "The organisation id was not 1 as expected.");
        $this->assertEquals($form->getTargetID(), 8, "The target id was not 8 as expected.");
        $this->assertEquals($form->getFormParent(), 0, "The form parent id was not 0 as expected.");
        $this->assertEquals($form->getFormTitle(), "Test Self Assessment", "The form title was not \"Test Self Assessment\" as expected.");
        $this->assertEquals($form->getFormDescription(), "Test Self Assessment", "The form description was not \"Test Self Assessment\" as expected.");
        $this->assertEquals($form->getFormActive(), 1, "The form active flag was not 1 as expected.");
    }
        
    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }    
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    FormTest::main();
}
