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
 * @author Organisation: The University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../../../www-root/core",
    dirname(__FILE__) . "/../../../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../../../www-root/core/library",
    dirname(__FILE__) . "/../../../../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

class ExportTest extends BaseTestCase {

    private $version_repo;

    public function setUp() {
        parent::setUp();
        $this->version_repo = Phake::mock('Models_Repository_CurriculumMapVersions');
        Models_Repository_CurriculumMapVersions::setInstance($this->version_repo);
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testFetchRelatedObjectivesForEvents()
    {
        $tag_set_ids = array(1, 3);
        $events = array(
            array(
                "event_id" => 1234,
                "event_title" => "foo",
                "event_duration" => 50,
            ),
            array(
                "event_id" => 5678,
                "event_title" => "foo",
                "event_duration" => 60,
            ),
        );
        $versions_by_event = array(
            1234 => new Models_Curriculum_Map_Versions(),
            5678 => new Models_Curriculum_Map_Versions(array("version_id" => 1)),
        );
        $objective11 = Phake::mock("Models_Objective");
        Phake::when($objective11)->getParent()->thenReturn(1);
        Phake::when($objective11)->toArray()->thenReturn(array(
            "objective_id" => 11,
            "objective_parent" => 1,
            "objective_name" => "Objective 11",
        ));
        $objective12 = Phake::mock("Models_Objective");
        Phake::when($objective12)->getParent()->thenReturn(1);
        Phake::when($objective12)->toArray()->thenReturn(array(
            "objective_id" => 12,
            "objective_parent" => 1,
            "objective_name" => "Objective 12",
        ));
        $objectives_by_event = array(
            1234 => array(11 => $objective11),
            5678 => array(12 => $objective12),
        );
        $objective21 = Phake::mock("Models_Objective");
        Phake::when($objective21)->getParent()->thenReturn(2);
        Phake::when($objective21)->toArray()->thenReturn(array(
            "objective_id" => 21,
            "objective_parent" => 2,
            "objective_name" => "Objective 21",
        ));
        $objective22 = Phake::mock("Models_Objective");
        Phake::when($objective22)->getParent()->thenReturn(2);
        Phake::when($objective22)->toArray()->thenReturn(array(
            "objective_id" => 22,
            "objective_parent" => 2,
            "objective_name" => "Objective 22",
        ));
        $objective23 = Phake::mock("Models_Objective");
        Phake::when($objective23)->getParent()->thenReturn(2);
        Phake::when($objective23)->toArray()->thenReturn(array(
            "objective_id" => 23,
            "objective_parent" => 2,
            "objective_name" => "Objective 23",
        ));
        $linked_objectives_1 = array(
            null => array(
                1234 => array(
                    11 => array(21 => $objective21),
                ),
            ),
            1 => array(
                5678 => array(
                    11 => array(21 => $objective21),
                    12 => array(22 => $objective22, 23 => $objective23),
                ),
            ),
        );
        $objective31 = Phake::mock("Models_Objective");
        Phake::when($objective31)->getParent()->thenReturn(3);
        Phake::when($objective31)->toArray()->thenReturn(array(
            "objective_id" => 31,
            "objective_parent" => 3,
            "objective_name" => "Objective 31",
        ));
        $objective32 = Phake::mock("Models_Objective");
        Phake::when($objective32)->getParent()->thenReturn(3);
        Phake::when($objective32)->toArray()->thenReturn(array(
            "objective_id" => 32,
            "objective_parent" => 3,
            "objective_name" => "Objective 32",
        ));
        $objective33 = Phake::mock("Models_Objective");
        Phake::when($objective33)->getParent()->thenReturn(3);
        Phake::when($objective33)->toArray()->thenReturn(array(
            "objective_id" => 33,
            "objective_parent" => 3,
            "objective_name" => "Objective 33",
        ));
        $objective34 = Phake::mock("Models_Objective");
        Phake::when($objective34)->getParent()->thenReturn(3);
        Phake::when($objective34)->toArray()->thenReturn(array(
            "objective_id" => 34,
            "objective_parent" => 3,
            "objective_name" => "Objective 34",
        ));
        $linked_objectives_2 = array(
            null => array(
                1234 => array(
                    21 => array(31 => $objective31, 33 => $objective33),
                ),
                5678 => array(
                    23 => array(34 => $objective34),
                ),
            ),
            1 => array(
                5678 => array(
                    22 => array(32 => $objective32),
                    23 => array(32 => $objective32),
                ),
            ),
        );
        $objective41 = Phake::mock("Models_Objective");
        Phake::when($objective41)->getParent()->thenReturn(4);
        Phake::when($objective41)->toArray()->thenReturn(array(
            "objective_id" => 41,
            "objective_parent" => 4,
            "objective_name" => "Objective 41",
        ));
        $linked_objectives_3 = array(
            null => array(
                null => array(
                    31 => array(41 => $objective41),
                ),
            ),
            1 => array(),
        );
        $linked_objectives_4 = array(
            null => array(),
            1 => array(),
        );
        Phake::when($this->version_repo)->fetchLatestVersionsByEventIDs(Phake::anyParameters())->thenReturn($versions_by_event);
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByEventIDs(Phake::anyParameters())->thenReturn($objectives_by_event);
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(11, 12), null, array(1234, 5678))->thenReturn(array(null => $linked_objectives_1[null]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(11, 12), 1, array(1234, 5678))->thenReturn(array(1 => $linked_objectives_1[1]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(22, 23, 21), null, array(5678, 1234))->thenReturn(array(null => $linked_objectives_2[null]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(22, 23, 21), 1, array(5678, 1234))->thenReturn(array(1 => $linked_objectives_2[1]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(31, 33, 34, 32), null, $this->anything())->thenReturn(array(null => $linked_objectives_3[null]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(31, 33, 34, 32), 1, $this->anything())->thenReturn(array(1 => $linked_objectives_3[1]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(41), null, $this->anything())->thenReturn(array(null => $linked_objectives_4[null]));
        Phake::when($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(41), 1, $this->anything())->thenReturn(array(1 => $linked_objectives_4[1]));
        $export = Phake::partialMock('Entrada_Curriculum_Export');
        $export->fetchRelatedObjectivesForEvents($events, $tag_set_ids);
        Phake::verify($this->version_repo)->fetchLatestVersionsByEventIDs(array(1234, 5678));
        Phake::verify($objective_repository)->fetchAllByEventIDs(array(1234, 5678));
        Phake::verify($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(11, 12), null, array(1234, 5678));
        Phake::verify($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(11, 12), 1, array(1234, 5678));
        Phake::verify($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(22, 23, 21), null, array(5678, 1234));
        Phake::verify($objective_repository)->fetchLinkedObjectivesByIDsAndEvents("from", array(22, 23, 21), 1, array(5678, 1234));
        Phake::verify($objective_repository, Phake::never())->fetchLinkedObjectivesByIDsAndEvents("from", array(31, 33, 34, 32), $this->anything(), $this->anything());
        Phake::verify($objective_repository, Phake::never())->fetchLinkedObjectivesByIDsAndEvents("from", array(41), $this->anything(), $this->anything());
        $expected_events = array(
            array(
                "event_id" => 1234,
                "event_title" => "foo",
                "event_duration" => 50,
                "objectives" => array(
                    1 => array(
                        11 => array(
                            "event_id" => 1234,
                            "objective_id" => 11,
                            "objective_parent" => 1,
                            "objective_name" => "Objective 11",
                        ),
                    ),
                    3 => array(
                        31 => array(
                            "version_id" => null,
                            "from_objective_id" => 21,
                            "objective_id" => 31,
                            "objective_parent" => 3,
                            "objective_name" => "Objective 31",
                            "linked_objectives" => array(
                                1 => array(
                                    11 => 11,
                                ),
                                2 => array(
                                    21 => 21,
                                ),
                            ),
                        ),
                        33 => array(
                            "version_id" => null,
                            "from_objective_id" => 21,
                            "objective_id" => 33,
                            "objective_parent" => 3,
                            "objective_name" => "Objective 33",
                            "linked_objectives" => array(
                                1 => array(
                                    11 => 11,
                                ),
                                2 => array(
                                    21 => 21,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                "event_id" => 5678,
                "event_title" => "foo",
                "event_duration" => 60,
                "objectives" => array(
                    1 => array(
                        12 => array(
                            "event_id" => 5678,
                            "objective_id" => 12,
                            "objective_parent" => 1,
                            "objective_name" => "Objective 12",
                        ),
                    ),
                    3 => array(
                        32 => array(
                            "version_id" => 1,
                            "from_objective_id" => 23,
                            "objective_id" => 32,
                            "objective_parent" => 3,
                            "objective_name" => "Objective 32",
                            "linked_objectives" => array(
                                1 => array(
                                    12 => 12,
                                ),
                                2 => array(
                                    22 => 22,
                                    23 => 23,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $this->assertEquals($expected_events, $events);
    }

    public function testToRows() {
        $tag_set_ids = array(1,3,4);
        $tag_sets = array(
            1 => array("objective_name" => "Activity Objectives"),
            2 => array("objective_name" => "Week Objectives"),
            3 => array("objective_name" => "Course Outcomes"),
            4 => array("objective_name" => "Themes"),
        );
        $events = array(
            array(
                "course_code" => "MEDD 412",
                "course_name" => "Foundations of Medical Practice II (FOMP II)",
                "event_id" => 101,
                "event_title" => "Intro to Physiology",
                "event_start" => 1467769234,
                "event_duration" => 50,
                "event_description" => "Teach students to understand physiology",
                "course_unit" => "Week 1",
                "event_types" => array(
                    array(
                        "eventtype_title" => "Lecture",
                        "duration" => 50,
                    ),
                ),
                "contacts" => array(
                    "teacher" => array(
                        array(
                            "user_id" => 2001,
                            "firstname" => "Bob",
                            "lastname" => "Baker",
                        ),
                        array(
                            "user_id" => 2002,
                            "firstname" => "Sally",
                            "lastname" => "Shoemaker",
                        ),
                    ),
                ),
                "objectives" => array(
                    1 => array(
                        11 => array(
                            "objective_id" => 11,
                            "objective_name" => "Describe physiology",
                        ),
                        12 => array(
                            "objective_id" => 11,
                            "objective_name" => "Discuss physiology",
                        ),
                    ),
                    3 => array(
                        31 => array(
                            "objective_id" => 31,
                            "objective_name" => "Define physiology",
                            "linked_objectives" => array(
                                1 => array(
                                    11 => 11,
                                    12 => 12,
                                ),
                            ),
                        ),
                        32 => array(
                            "objective_id" => 32,
                            "objective_name" => "Treat patients",
                            "linked_objectives" => array(
                                1 => array(
                                    12 => 12,
                                ),
                            ),
                        ),
                    ),
                    4 => array(
                        41 => array(
                            "objective_id" => 41,
                            "objective_name" => "Physiology",
                            "linked_objectives" => array(
                                1 => array(
                                    11 => 11,
                                    12 => 12,
                                ),
                            ),
                        ),
                        42 => array(
                            "objective_id" => 42,
                            "objective_name" => "First Peoples",
                            "linked_objectives" => array(
                                1 => array(
                                    12 => 12,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                "course_code" => "MEDD 412",
                "course_name" => "Foundations of Medical Practice II (FOMP II)",
                "event_id" => 102,
                "event_title" => "Intro to Clinical Diagnosis",
                "event_start" => 1467682834,
                "event_duration" => 60,
                "event_description" => "Teach students to make a clinical diagnosis",
                "course_unit" => "Week 1",
                "event_types" => array(
                    array(
                        "eventtype_title" => "Lecture",
                        "duration" => 30,
                    ),
                    array(
                        "eventtype_title" => "Laboratory",
                        "duration" => 30,
                    ),
                ),
                "objectives" => array(
                    1 => array(
                        13 => array(
                            "objective_id" => 13,
                            "objective_name" => "Describe clinical diagnosis",
                        ),
                    ),
                    3 => array(
                        33 => array(
                            "objective_id" => 33,
                            "objective_name" => "Define clinical diagnosis",
                            "linked_objectives" => array(
                                1 => array(
                                    13 => 13,
                                ),
                            ),
                        ),
                    ),
                    4 => array(
                        43 => array(
                            "objective_id" => 43,
                            "objective_name" => "Clinical Diagnosis",
                            "linked_objectives" => array(
                                1 => array(
                                    13 => 13,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                "course_code" => "MEDD 412",
                "course_name" => "Foundations of Medical Practice II (FOMP II)",
                "event_id" => 103,
                "event_title" => "Intro to Diet",
                "event_start" => 1467596434,
                "event_duration" => 50,
                "event_description" => "Teach students to prescribe dietary changes",
                "course_unit" => "Week 1",
                "event_types" => array(
                    array(
                        "eventtype_title" => "Case-Based Learning",
                        "duration" => 50,
                    ),
                ),
            ),
        );
        $expected_rows = array(
            array(
                "course_name",
                "event_id",
                "event_title",
                "url",
                "course_unit",
                "event_date",
                "event_duration",
                "event_description",
                "event_type",
                "duration",
                "teachers",
                "Activity Objectives",
                "Course Outcomes",
                "Themes",
            ),
            array(
                "MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Describe physiology",
                "",
                "Physiology",
            ),
            array(
                "MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Describe physiology",
                "Define physiology",
                "",
            ),
            array(
                "MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Discuss physiology",
                "",
                "Physiology",
            ),
            array(
                "MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Discuss physiology",
                "",
                "First Peoples",
            ),
            array(
                "MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Discuss physiology",
                "Define physiology",
                "",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                101,
                "Intro to Physiology",
                "http://localhost/entrada/events?id=101",
                "Week 1",
                "Tue Jul 05/16 9:40pm",
                50,
                "Teach students to understand physiology",
                "Lecture",
                round(50.0/6, 2),
                "Bob Baker, Sally Shoemaker",
                "Discuss physiology",
                "Treat patients",
                "",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                102,
                "Intro to Clinical Diagnosis",
                "http://localhost/entrada/events?id=102",
                "Week 1",
                "Mon Jul 04/16 9:40pm",
                60,
                "Teach students to make a clinical diagnosis",
                "Lecture",
                round(30.0/2, 2),
                "",
                "Describe clinical diagnosis",
                "",
                "Clinical Diagnosis",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                102,
                "Intro to Clinical Diagnosis",
                "http://localhost/entrada/events?id=102",
                "Week 1",
                "Mon Jul 04/16 9:40pm",
                60,
                "Teach students to make a clinical diagnosis",
                "Lecture",
                round(30.0/2, 2),
                "",
                "Describe clinical diagnosis",
                "Define clinical diagnosis",
                "",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                102,
                "Intro to Clinical Diagnosis",
                "http://localhost/entrada/events?id=102",
                "Week 1",
                "Mon Jul 04/16 9:40pm",
                60,
                "Teach students to make a clinical diagnosis",
                "Laboratory",
                round(30.0/2, 2),
                "",
                "Describe clinical diagnosis",
                "",
                "Clinical Diagnosis",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                102,
                "Intro to Clinical Diagnosis",
                "http://localhost/entrada/events?id=102",
                "Week 1",
                "Mon Jul 04/16 9:40pm",
                60,
                "Teach students to make a clinical diagnosis",
                "Laboratory",
                round(30.0/2, 2),
                "",
                "Describe clinical diagnosis",
                "Define clinical diagnosis",
                "",
            ),
            array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
                103,
                "Intro to Diet",
                "http://localhost/entrada/events?id=103",
                "Week 1",
                "Sun Jul 03/16 9:40pm",
                50,
                "Teach students to prescribe dietary changes",
                "Case-Based Learning",
                50,
                "",
                "",
                "",
                "",
            ),
        );
        $export = Phake::mock('Entrada_Curriculum_Export');
        Phake::when($export)->toRows(Phake::anyParameters())->thenCallParent();
        Phake::when($export)->traverseObjectivesByTagSet(Phake::anyParameters())->thenCallParent();
        Phake::when($export)->putLinkedTagSetFirst(Phake::anyParameters())->thenCallParent();
        Phake::when($export)->objectiveLinksByTagSet(Phake::anyParameters())->thenCallParent();
        $get_objective_text = function ($objective) { return $objective["objective_name"]; };
        $this->assertEquals($expected_rows, $export->toRows($events, $tag_set_ids, $tag_sets, $get_objective_text, false));
    }

    public function testToRowsGroupedByEvent() {
		$tag_set_ids = array(1,3,4);
		$tag_sets = array(
			1 => array("objective_name" => "Activity Objectives"),
			2 => array("objective_name" => "Week Objectives"),
			3 => array("objective_name" => "Course Outcomes"),
			4 => array("objective_name" => "Themes"),
		);
		$events = array(
			array(
				"course_code" => "MEDD 412",
				"course_name" => "Foundations of Medical Practice II (FOMP II)",
				"event_id" => 101,
				"event_title" => "Intro to Physiology",
				"event_start" => 1467769234,
				"event_duration" => 50,
				"event_description" => "Teach students to understand physiology",
				"course_unit" => "Week 1",
				"event_types" => array(
					array(
						"eventtype_title" => "Lecture",
						"duration" => 50,
					),
				),
				"contacts" => array(
					"teacher" => array(
						array(
							"user_id" => 2001,
							"firstname" => "Bob",
							"lastname" => "Baker",
						),
						array(
							"user_id" => 2002,
							"firstname" => "Sally",
							"lastname" => "Shoemaker",
						),
					),
				)
			),
			array(
				"course_code" => "MEDD 412",
				"course_name" => "Foundations of Medical Practice II (FOMP II)",
				"event_id" => 102,
				"event_title" => "Intro to Clinical Diagnosis",
				"event_start" => 1467682834,
				"event_duration" => 60,
				"event_description" => "Teach students to make a clinical diagnosis",
				"course_unit" => "Week 1",
				"event_types" => array(
					array(
						"eventtype_title" => "Lecture",
						"duration" => 30,
					),
					array(
						"eventtype_title" => "Laboratory",
						"duration" => 30,
                    ),
                ),
                'objectives' => array (
                    1 => array (
                        32672 => array (
                            'objective_id' => '32672',
                            'objective_code' => null,
                            'objective_name' => 'AO-1901',
                            'objective_description' => 'Describe and interpret drug concentration vs time graphs',
                            'objective_secondary_description' => null,
                            'objective_parent' => '2405',
                            'objective_set_id' => '0',
                            'associated_objective' => null,
                            'objective_order' => '1884',
                            'objective_loggable' => '0',
                            'objective_active' => '1',
                            'updated_date' => '1466806879',
                            'updated_by' => '1',
                            'event_id' => 525
                        ),
                        32673 => array (
                            'objective_id' => '32673',
                            'objective_code' => null,
                            'objective_name' => 'AO-1902',
                            'objective_description' => 'Compare and contrast first-order vs zero-order drug elimination',
                            'objective_secondary_description' => null,
                            'objective_parent' => '2405',
                            'objective_set_id' => '0',
                            'associated_objective' => null,
                            'objective_order' => '1885',
                            'objective_loggable' => '0',
                            'objective_active' => '1',
                            'updated_date' => '1466806879',
                            'updated_by' => '1',
                            'event_id' => 525
                        ),
                        32674 => array (
                            'objective_id' => '32674',
                            'objective_code' => null,
                            'objective_name' => 'AO-1903',
                            'objective_description' => 'Apply pharmacokinetic concepts and values to the calculation of drug dosing regimens',
                            'objective_secondary_description' => null,
                            'objective_parent' => '2405',
                            'objective_set_id' => '0',
                            'associated_objective' => null,
                            'objective_order' => '1886',
                            'objective_loggable' => '0',
                            'objective_active' => '1',
                            'updated_date' => '1466806879',
                            'updated_by' => '1',
                            'event_id' => 525
                        )
                    )
				)
			)
		);
		$expected_rows = array(
			array(
				"course_name",
				"event_id",
				"event_title",
				"url",
				"course_unit",
				"event_date",
				"event_duration",
				"event_description",
				"event_type",
				"teachers",
				"Activity Objectives",
				"Course Outcomes",
				"Themes",
			),
			array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
				101,
				"Intro to Physiology",
				"http://localhost/entrada/events?id=101",
				"Week 1",
				"Tue Jul 05/16 9:40pm",
				50,
				"Teach students to understand physiology",
				"Lecture",
				"Bob Baker, Sally Shoemaker",
				"",
				"",
				""
			),
			array(
				"MEDD 412 Foundations of Medical Practice II (FOMP II)",
				102,
				"Intro to Clinical Diagnosis",
				"http://localhost/entrada/events?id=102",
				"Week 1",
				"Mon Jul 04/16 9:40pm",
				60,
				"Teach students to make a clinical diagnosis",
				"Lecture; Laboratory",
				"",
				"1. AO-1901\n2. AO-1902\n3. AO-1903\r\n",
				"",
				""
			)
		);
		$export = Phake::mock('Entrada_Curriculum_Export');
		Phake::when($export)->toRows(Phake::anyParameters())->thenCallParent();
		Phake::when($export)->traverseObjectivesByTagSet(Phake::anyParameters())->thenCallParent();
		Phake::when($export)->putLinkedTagSetFirst(Phake::anyParameters())->thenCallParent();
		Phake::when($export)->objectiveLinksByTagSet(Phake::anyParameters())->thenCallParent();
		$get_objective_text = function ($objective) { return $objective["objective_name"]; };
		$this->assertEquals($expected_rows, $export->toRows($events, $tag_set_ids, $tag_sets, $get_objective_text, true));
	}
}
if (!defined('PHPUnit_MAIN_METHOD')) {
    ExportTest::main();
}
