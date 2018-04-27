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

class Course_UnitTest extends BaseTestCase {

    public function testGetObjectives_Empty() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByCourseUnitIDs(Phake::anyParameters())->thenReturn(array());
        $course_unit = new Models_Course_Unit(array("cunit_id" => 1));
        $this->assertEquals(array(), $course_unit->getObjectives());
        Phake::verify($objective_repository)->fetchAllByCourseUnitIDs(array(1));
    }

    public function testGetObjectives() {
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByCourseUnitIDs(Phake::anyParameters())->thenReturn(array(1 => array("objectives")));
        $course_unit = new Models_Course_Unit(array("cunit_id" => 1));
        $this->assertEquals(array("objectives"), $course_unit->getObjectives());
        Phake::verify($objective_repository)->fetchAllByCourseUnitIDs(array(1));
    }

    /**
     * The goal is to test Models_Course_Unit::fetchAllByCourseIDCperiodID
     */
    public function testFetchMoreUsits() {
        global $db, $ENTRADA_USER;

        $course_id = 2;
        $cperiod_id = 2;

        $hypertension = array(
            'cunit_id' => 69,
            'unit_code' => 'Week 10',
            'unit_title' => 'Hypertension',
            'unit_description' => '<p>This week presents the physiologic controls of blood pressure, its abnormal elevation, and the etiologic categorization of hypertension. It explores blood pressure measurement as well as the principles of diagnosis and management of hypertension. It also outlines the histology of blood vessels and the pathology of arteriosclerosis and end-organ damage from hypertension. The case of the week is based on a young woman with elevated blood pressure incidentally detected during a visit to a pharmacy.</p>',
            'course_id' => $course_id,
            'cperiod_id' => $cperiod_id,
            'week_id' => 10,
            'unit_order' => 10,
            'updated_date' => 1480718922,
            'updated_by' => 4,
            'created_date' => 1480459664,
            'created_by' => 4,
            'deleted_date' => NULL
        );

        $fetal_development = array(
            'cunit_id' => 75,
            'unit_code' => 'Week 4',
            'unit_title' => 'Fetal Development',
            'unit_description' => '<p>This week builds on the gross and microscopic anatomy concepts introduced in MEDD 410 by presenting the general organization of the body as well as fetal development during a normal pregnancy. The critical phases of fetal development will be approached by considering aspects of genetics, formation of the zygote, development of the embryo and fetus, and the week will provide a discussion of approaches to monitoring normal pregnancy. The case of this week is based on a normal pregnancy.</p>',
            'course_id' => $course_id,
            'cperiod_id' => $cperiod_id,
            'week_id' => 4,
            'unit_order' => 4,
            'updated_date' => 1485452943,
            'updated_by' => 4,
            'created_date' => 1480459733,
            'created_by' => 4,
            'deleted_date' => NULL
        );

        // The database rows that we are expecting back from the mocked database.
        $course_units = array(
            $hypertension,
            $fetal_development
        );

        // The expected data were expecting back from fetchAllByCourseIDCperiodID.
        $expected_data = array(
            0 => new Models_Course_Unit($hypertension),
            1 => new Models_Course_Unit($fetal_development)
        );

        // Return 1 when you call getId on the current user.
        Phake::when($db)->qstr(Phake::anyParameters())->thenReturnCallback(function ($x) { return "'$x'"; });

        // Return the fake course units when you do a database call.
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($course_units);

        $unit_model = new Models_Course_Unit();
        $this->assertEquals(
            $expected_data,
            $unit_model->fetchAllByCourseIDCperiodID($course_id, $cperiod_id)
        );
    }

    public function testFetchMyUnits() {
        global $db, $ENTRADA_USER;

        $rows = array(array(
            "cunit_id" => 11,
            "course_code" => "MEDD 411",
            "week_title" => "Week 11",
            "curriculum_type_name" => "Year 1",
        ));
        $expected_data = array(
            "Year 1" => array(
                "Week 11" => array(
                    "MEDD 411" => new Models_Course_Unit(array(
                        "cunit_id" => 11,
                        "course_code" => "MEDD 411",
                        "week_title" => "Week 11",
                        "curriculum_type_name" => "Year 1",
                    )),
                ),
            ),
        );

        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID(Phake::anyParameters())->thenReturn(1);
        Phake::when($ENTRADA_USER)->getCohort(Phake::anyParameters())->thenReturn(1);
        Phake::when($db)->qstr(Phake::anyParameters())->thenReturnCallback(function ($x) { return "'$x'"; });
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);

        $unit_model = new Models_Course_Unit();
        $this->assertEquals($expected_data, $unit_model->fetchMyUnits());

        Phake::verify($ENTRADA_USER)->getID();
        Phake::verify($db, Phake::times(6))->qstr($this->anything());
        Phake::verify($db)->GetAll($this->logicalAnd(
            $this->stringContains("`audience_value` = '1'"),                       // User ID is in audience as individual
            $this->stringContains("`proxy_id` = '1'"),                             // Cohort audience contains user
            $this->stringContains("' BETWEEN p.`start_date` AND p.`finish_date`"), // Current time within matching period
            $this->stringContains("' BETWEEN p.`start_date` AND p.`finish_date`"), // Current time within matching period
            $this->stringContains("`start_date` <= '"),                            // Group start date before current time
            $this->stringContains("`expire_date` >= '")                            // Group expire date after current time
        ));
    }

    public function testGetByCohort() {
        global $db, $ENTRADA_USER;

        $rows = array(array(
            "cunit_id" => 11,
            "course_code" => "MEDD 411",
            "week_title" => "Week 11",
            "curriculum_type_name" => "Year 1",
            "curriculum_period_title" => "Year 1 for Class of 2021"
        ));
        $expected_data = array(
            "Year 1" => array(
                "Week 11" => array(
                    "MEDD 411" => new Models_Course_Unit(array(
                        "cunit_id" => 11,
                        "course_code" => "MEDD 411",
                        "week_title" => "Week 11",
                        "curriculum_type_name" => "Year 1",
                        "curriculum_period_title" => "Year 1 for Class of 2021"
                    )),
                ),
            ),
        );

        $ENTRADA_USER = Phake::mock("User");
        Phake::when($ENTRADA_USER)->getID(Phake::anyParameters())->thenReturn(1);
        Phake::when($db)->GetAll(Phake::anyParameters())->thenReturn($rows);

        $unit_model = new Models_Course_Unit();
        $this->assertEquals($expected_data, $unit_model->getByCohort(1));
    }

    static function main() {
        $suite = new PHPUnit_Framework_TestSuite( __CLASS__);
        PHPUnit_TextUI_TestRunner::run( $suite);
    }
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    Course_UnitTest::main();
}

