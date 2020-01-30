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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");

class Models_Reports_ObjectiveMappingsTest extends BaseTestCase {

    private $version_repository;

    public function setUp() {
        parent::setUp();
        $this->version_repository = Phake::mock("Models_Repository_CurriculumMapVersions");
        Models_Repository_CurriculumMapVersions::setInstance($this->version_repository);
        $this->objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($this->objective_repository);
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testObjectivesLinkingToByParent()
    {
        $version_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $objective1 = new Models_Objective();
        $objective1->fromArray(array("objective_parent" => 3, "objective_id" => 31));
        $objective2 = new Models_Objective();
        $objective2->fromArray(array("objective_parent" => 3, "objective_id" => 32));
        $objective3 = new Models_Objective();
        $objective3->fromArray(array("objective_parent" => 4, "objective_id" => 41));
        $objectives_by_event = array(
            101 => array(
                11 => array(31 => $objective1, 32 => $objective2),
                12 => array(41 => $objective3),
            ),
        );
        $expected_objective_links = array(
            11 => array(
                101 => array(
                    3 => array(
                        31 => true,
                        32 => true,
                    ),
                ),
            ),
            12 => array(
                101 => array(
                    4 => array(
                        41 => true,
                    ),
                ),
            ),
        );
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents(Phake::anyParameters())->thenReturn(array($version_id => $objectives_by_event));
        Phake::when($this->objective_repository)->flatten(array($version_id => $objectives_by_event))->thenReturn($objectives_by_event);
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->objectivesLinkingToByParent(Phake::anyParameters())->thenCallParent();
        $this->assertEquals(array(), Phake::makeVisible($report_model)->objectivesLinkingToByParent($version_id, array(), $course_id, $cunit_ids, $start, $end));
        Phake::verify($this->objective_repository, Phake::never())->fetchLinkedObjectivesByIDsAndEvents(Phake::anyParameters());
        $this->assertEquals($expected_objective_links, Phake::makeVisible($report_model)->objectivesLinkingToByParent($version_id, array(11, 12, 13), $course_id, $cunit_ids, $start, $end));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(11, 12, 13), $version_id, false, array(
            "course_ids" => array($course_id),
            "cunit_ids" => $cunit_ids,
            "start" => $start,
            "end" => $end,
        ));
        $this->assertEquals($expected_objective_links, Phake::makeVisible($report_model)->objectivesLinkingToByParent($version_id, array(11, 12, 13), $course_id, $cunit_ids, $start, $end));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(13), $version_id, false, array(
            "course_ids" => array($course_id),
            "cunit_ids" => $cunit_ids,
            "start" => $start,
            "end" => $end,
        ));
    }

    public function testEventsLinkingTo()
    {
        global $db;
        $objective_ids = array(41, 51, 61);
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $event100 = new Models_Event(array("event_id" => 100, "event_duration" => 60.0));
        $event101 = new Models_Event(array("event_id" => 101, "event_duration" => 120.0));
        $event102 = new Models_Event(array("event_id" => 102, "event_duration" => 50.0));
        $events_by_objective = array(
            41 => array(
                100 => $event100,
                101 => $event101,
            ),
            51 => array(
                102 => $event102,
            ),
        );
        $mappings_by_event = array(
            100 => array(
                4 => 1,
            ),
            101 => array(
                4 => 2,
            ),
            102 => array(
                5 => 3,
            ),
        );
        $event_types_by_event = array(
            100 => array(
                1 => new Models_Event_EventType(array("event_id" => 100, "eventtype_id" => 1, "duration" => 60)),
            ),
            101 => array(
                1 => new Models_Event_EventType(array("event_id" => 101, "eventtype_id" => 1, "duration" => 75)),
                2 => new Models_Event_EventType(array("event_id" => 101, "eventtype_id" => 2, "duration" => 45)),
            ),
            102 => array(
                1 => new Models_Event_EventType(array("event_id" => 102, "eventtype_id" => 1, "duration" => 50)),
            ),
        );
        $objective41 = new Models_Objective();
        $objective41->fromArray(array("objective_id" => 41, "objective_parent" => 4));
        $objective51 = new Models_Objective();
        $objective51->fromArray(array("objective_id" => 51, "objective_parent" => 5));
        $objectives = array(
            41 => $objective41,
            51 => $objective51,
        );
        $expected_durations_by_objective_event = array(
            41 => array(
                100 => array(
                    array(
                        "event_duration" => 60.0,
                        "event_type_duration" => array(
                            1 => 60.0,
                        ),
                        "total_mappings" => 1,
                    ),
                ),
                101 => array(
                    array(
                        "event_duration" => 120.0,
                        "event_type_duration" => array(
                            1 => 75.0,
                            2 => 45.0,
                        ),
                        "total_mappings" => 2,
                    ),
                ),
            ),
            51 => array(
                102 => array(
                    array(
                        "event_duration" => 50.0,
                        "event_type_duration" => array(
                            1 => 50.0,
                        ),
                        "total_mappings" => 3,
                    ),
                ),
            ),
        );

        $event_repository = Phake::partialMock("Models_Repository_Events");
        Models_Repository_Events::setInstance($event_repository);
        Phake::when($event_repository)->fetchAllByObjectiveIDsAndFilters(Phake::anyParameters())->thenReturn(null);
        Phake::when($event_repository)->fetchAllByObjectiveIDsAndFilters(array(41, 51, 61), $this->anything())->thenReturn($events_by_objective);
        Phake::when($event_repository)->fetchAllByObjectiveIDsAndFilters(array(61), $this->anything())->thenReturn(array());
        Phake::when($event_repository)->fetchTotalMappingsByEventIDs(Phake::anyParameters())->thenReturn($mappings_by_event);
        Phake::when($event_repository)->fetchEventTypesByEventIDs(Phake::anyParameters())->thenReturn($event_types_by_event);

        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchAllByIDs(Phake::anyParameters())->thenReturn($objectives);

        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->eventsLinkingTo(Phake::anyParameters())->thenCallParent();

        $this->assertEquals(array(), Phake::makeVisible($report_model)->eventsLinkingTo(array(), $course_id, $cunit_ids, $start, $end));
        Phake::verify($event_repository, Phake::never())->fetchTotalMappingsByEventIDs(Phake::anyParameters());

        $this->assertEquals($expected_durations_by_objective_event, Phake::makeVisible($report_model)->eventsLinkingTo($objective_ids, $course_id, $cunit_ids, $start, $end));
        Phake::verify($event_repository)->fetchAllByObjectiveIDsAndFilters(array(41, 51, 61), array(
            "course_ids" => array(1),
            "cunit_ids" => array(1, 2),
            "start" => 1000,
            "end" => 2000,
        ));
        Phake::verify($event_repository)->fetchTotalMappingsByEventIDs(array(100 => 100, 101 => 101, 102 => 102));
        Phake::verify($event_repository)->fetchEventTypesByEventIDs(array(100 => 100, 101 => 101, 102 => 102));

        $this->assertEquals($expected_durations_by_objective_event, Phake::makeVisible($report_model)->eventsLinkingTo($objective_ids, $course_id, $cunit_ids, $start, $end));
        Phake::verify($event_repository)->fetchAllByObjectiveIDsAndFilters(array(61), $this->anything());
    }

    public function testTotalMappingsByObjectivesTo()
    {
        $rows = array(
            array("objective_id" => 21, "event_id" => 101, "mappings" => 3),
            array("objective_id" => 22, "event_id" => 101, "mappings" => 2),
        );
        $mappings_by_objective = array(
            21 => array(
                101 => 3,
            ),
            22 => array(
                101 => 2,
            ),
        );
        $objective_repository = Phake::mock("Models_Repository_Objectives");
        Models_Repository_Objectives::setInstance($objective_repository);
        Phake::when($objective_repository)->fetchTotalMappingsByObjectivesTo(Phake::anyParameters())->thenReturn($mappings_by_objective);
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->totalMappingsByObjectivesTo(Phake::anyParameters())->thenCallParent();
        $this->assertEquals($mappings_by_objective, Phake::makeVisible($report_model)->totalMappingsByObjectivesTo(1, 2, array(21, 22, 23), array(101)));
        Phake::verify($objective_repository)->fetchTotalMappingsByObjectivesTo(1, 2, array(21, 22, 23), array(101));
        $this->assertEquals($mappings_by_objective, Phake::makeVisible($report_model)->totalMappingsByObjectivesTo(1, 2, array(21, 22, 23), array(101)));
        Phake::verify($objective_repository)->fetchTotalMappingsByObjectivesTo(1, 2, array(23), array(101));
    }

    public function testEventsLinkedToObjectiveSet()
    {
        $version_id = 1;
        $tag_set_id = 4;
        $objective_ids = array(1, 2, 3);
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $objective_links = array(
            1 => array(
                100 => array(
                    5 => array(
                        11 => true,
                    ),
                ),
            ),
            2 => array(
                101 => array(
                    5 => array(
                        12 => true,
                    ),
                ),
            ),
        );
        $total_mappings_by_objective = array(
            11 => array(
                100 => 1,
            ),
            12 => array(
                101 => 2,
            ),
        );
        $my_data_11 = array(
            "objectives" => array(
                100 => array(
                    5 => array(
                        11 => array(
                            "objectives" => array(
                                100 => array(
                                    6 => array(
                                        21 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60.0,
                                                    "event_type_duration" => array(
                                                        1 => 60.0,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $my_data_12 = array(
            "objectives" => array(
                101 => array(
                    5 => array(
                        12 => array(
                            "objectives" => array(
                                101 => array(
                                    6 => array(
                                        22 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 50.0,
                                                    "event_type_duration" => array(
                                                        1 => 50.0,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $durations_by_objective_event = array(
            1 => array(
                102 => array(
                    array(
                        "event_duration" => 50.0,
                        "event_type_duration" => array(
                            1 => 50.0,
                        ),
                        "total_mappings" => 3,
                    ),
                ),
                103 => array(
                    array(
                        "event_duration" => 120.0,
                        "event_type_duration" => array(
                            1 => 120.0,
                        ),
                        "total_mappings" => 4,
                    ),
                ),
            ),
        );
        $expected_data = array(
            "objectives" => array(
                100 => array(
                    4 => array(
                        1 => array(
                            "objectives" => array(
                                100 => array(
                                    5 => array(
                                        11 => array(
                                            "objectives" => array(
                                                100 => array(
                                                    6 => array(
                                                        21 => array(
                                                            "event" => array(
                                                                array(
                                                                    "event_duration" => 60.0,
                                                                    "event_type_duration" => array(
                                                                        1 => 60.0,
                                                                    ),
                                                                    "total_mappings" => 2,
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            "total_mappings" => 1,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                101 => array(
                    4 => array(
                        2 => array(
                            "objectives" => array(
                                101 => array(
                                    5 => array(
                                        12 => array(
                                            "objectives" => array(
                                                101 => array(
                                                    6 => array(
                                                        22 => array(
                                                            "event" => array(
                                                                array(
                                                                    "event_duration" => 50.0,
                                                                    "event_type_duration" => array(
                                                                        1 => 50.0,
                                                                    ),
                                                                    "total_mappings" => 3,
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                102 => array(
                    4 => array(
                        1 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 50.0,
                                    "event_type_duration" => array(
                                        1 => 50.0,
                                    ),
                                    "total_mappings" => 3,
                                ),
                            ),
                        ),
                    ),
                ),
                103 => array(
                    4 => array(
                        1 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 120.0,
                                    "event_type_duration" => array(
                                        1 => 120.0,
                                    ),
                                    "total_mappings" => 4,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->eventsLinkingTo(Phake::anyParameters())->thenReturn($durations_by_objective_event);
        Phake::when($report_model)->objectivesLinkingToByParent(Phake::anyParameters())->thenReturn($objective_links);
        Phake::when($report_model)->totalMappingsByObjectivesTo(Phake::anyParameters())->thenReturn($total_mappings_by_objective);
        Phake::when($report_model)->eventsLinkedToObjectiveSet(5, array(11), $version_id, $course_id, $cunit_ids, $start, $end)->thenReturn($my_data_11);
        Phake::when($report_model)->eventsLinkedToObjectiveSet(5, array(12), $version_id, $course_id, $cunit_ids, $start, $end)->thenReturn($my_data_12);
        Phake::when($report_model)->eventsLinkedToObjectiveSet($tag_set_id, $objective_ids, $version_id, $course_id, $cunit_ids, $start, $end)->thenCallParent();
        $data = Phake::makeVisible($report_model)->eventsLinkedToObjectiveSet($tag_set_id, $objective_ids, $version_id, $course_id, $cunit_ids, $start, $end);
        $this->assertEquals($expected_data, $data);
        Phake::verify($report_model)->objectivesLinkingToByParent(1, array(1, 2, 3), $course_id, $cunit_ids, $start, $end);
        Phake::verify($report_model)->totalMappingsByObjectivesTo(1, 4, array(11 => 11, 12 => 12), array(100 => 100, 101 => 101));
        Phake::verify($report_model)->eventsLinkedToObjectiveSet(5, array(11), $version_id, $course_id, $cunit_ids, $start, $end);
        Phake::verify($report_model)->eventsLinkedToObjectiveSet(5, array(12), $version_id, $course_id, $cunit_ids, $start, $end);
        Phake::verify($report_model)->eventsLinkingTo(array(1, 2, 3), $course_id, $cunit_ids, $start, $end);
    }

    public function testEventsLinkedToObjectiveSet_HighLevel()
    {
        $version_id = 1;
        $tag_set_id = 4;
        $objective_ids = array(1, 2, 3);
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $objective_links = array(
            1 => array(
                "" => array(
                    5 => array(
                        11 => true,
                    ),
                ),
            ),
        );
        $total_mappings_by_objective = array(
            11 => array(
                "" => 1,
            ),
        );
        $my_data_11 = array(
            "objectives" => array(
                100 => array( // Event 100
                    5 => array( // Tag set 5
                        11 => array( // Objective 11
                            "objectives" => array(
                                100 => array( // Event 100
                                    6 => array( // Tag set 6
                                        21 => array( // Objective 21
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60.0,
                                                    "event_type_duration" => array(
                                                        1 => 60.0,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $expected_data = array(
            "objectives" => array(
                100 => array( // Event 100
                    4 => array( // Tag set 4
                        1 => array( // Objective 1
                            /* Data from $my_data_11 above */
                            "objectives" => array(
                                100 => array( // Event 100
                                    5 => array( // Tag set 5
                                        11 => array( // Objective 11
                                            "objectives" => array(
                                                100 => array( // Event 100
                                                    6 => array( // Tag set 6
                                                        21 => array( // Objective 21
                                                            "event" => array(
                                                                array(
                                                                    "event_duration" => 60.0,
                                                                    "event_type_duration" => array(
                                                                        1 => 60.0,
                                                                    ),
                                                                    "total_mappings" => 2,
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            "total_mappings" => 1,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->eventsLinkingTo(Phake::anyParameters())->thenReturn(array());
        Phake::when($report_model)->objectivesLinkingToByParent(Phake::anyParameters())->thenReturn($objective_links);
        Phake::when($report_model)->totalMappingsByObjectivesTo(Phake::anyParameters())->thenReturn($total_mappings_by_objective);
        Phake::when($report_model)->eventsLinkedToObjectiveSet(5, array(11), $version_id, $course_id, $cunit_ids, $start, $end)->thenReturn($my_data_11);
        Phake::when($report_model)->eventsLinkedToObjectiveSet($tag_set_id, $objective_ids, $version_id, $course_id, $cunit_ids, $start, $end)->thenCallParent();
        $data = Phake::makeVisible($report_model)->eventsLinkedToObjectiveSet($tag_set_id, $objective_ids, $version_id, $course_id, $cunit_ids, $start, $end);
        $this->assertEquals($expected_data, $data);
        Phake::verify($report_model)->objectivesLinkingToByParent(1, array(1, 2, 3), $course_id, $cunit_ids, $start, $end);
        Phake::verify($report_model)->totalMappingsByObjectivesTo(1, 4, array(11 => 11), $this->anything());
        Phake::verify($report_model)->eventsLinkedToObjectiveSet(5, array(11), $version_id, $course_id, $cunit_ids, $start, $end);
        Phake::verify($report_model)->eventsLinkingTo(array(1, 2, 3), $course_id, $cunit_ids, $start, $end);
    }

    public function testMergeData()
    {
        $data = array(
            "objectives" => array(
                101 => array(
                    6 => array(
                        21 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 100.0,
                                    "event_type_duration" => array(
                                        1 => 100.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                    ),
                ),
                100 => array(
                    6 => array(
                        21 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 4,
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
            ),
            "event" => array(
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 3,
                ),
            ),
            "total_mappings" => 3,
        );
        $my_data = array(
            "objectives" => array(
                100 => array(
                    6 => array(
                        21 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
                102 => array(
                    6 => array(
                        22 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 50.0,
                                    "event_type_duration" => array(
                                        1 => 50.0,
                                    ),
                                    "total_mappings" => 3
                                ),
                            ),
                            "total_mappings" => 4,
                        ),
                    ),
                ),
                105 => array(
                    7 => array(
                        31 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 150.0,
                                    "event_type_duration" => array(
                                        1 => 150.0,
                                    ),
                                    "total_mappings" => 6,
                                ),
                            ),
                            "total_mappings" => 2,
                        ),
                    ),
                ),
            ),
            "event" => array(
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
            "total_mappings" => 2,
        );
        $expected_new_data = array(
            "objectives" => array(
                100 => array(
                    6 => array(
                        21 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 4,
                                ),
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 6,
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
                101 => array(
                    6 => array(
                        21 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 100.0,
                                    "event_type_duration" => array(
                                        1 => 100.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                    ),
                ),
                102 => array(
                    6 => array(
                        22 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 50.0,
                                    "event_type_duration" => array(
                                        1 => 50.0,
                                    ),
                                    "total_mappings" => 3,
                                ),
                            ),
                            "total_mappings" => 4,
                        ),
                    ),
                ),
                105 => array(
                    7 => array(
                        31 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 150.0,
                                    "event_type_duration" => array(
                                        1 => 150.0,
                                    ),
                                    "total_mappings" => 6,
                                ),
                            ),
                            "total_mappings" => 2,
                        ),
                    ),
                ),
            ),
            "event" => array(
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 3,
                ),
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 4,
                ),
            ),
            "total_mappings" => 3,
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->mergeData(Phake::anyParameters())->thenCallParent();
        $new_data = Phake::makeVisible($report_model)->mergeData($data, $my_data);
        $this->assertEquals($expected_new_data, $new_data);
    }

    public function testCollapseDataInvalidArgumentException()
    {
        $data = array(
            "objectives" => array(),
            "event" => array(),
        );
        try {
            $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
            Phake::when($report_model)->collapseData(Phake::anyParameters())->thenCallParent();
            $new_data = Phake::makeVisible($report_model)->collapseData($data, array());
            $this->fail("Should throw InvalidArgumentException");
        } catch (InvalidArgumentException $e) {
        } catch (Exception $e) {
            $this->fail("Should throw InvalidArgumentException");
        }
    }

    public function testCollapseDataWithTagSetNotIncludedEventsEmpty()
    {
        $tag_sets_included = array();
        $data = array(
            "objectives" => array(
                101 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                101 => array(
                                    2 => array(
                                        20 => array(
                                        ),
                                        21 => array(
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->collapseData(Phake::anyParameters())->thenCallParent();
        $collapsed_data = Phake::makeVisible($report_model)->collapseData($data, $tag_sets_included);
        $this->assertEmpty($collapsed_data);
        Phake::verify($report_model, Phake::atLeast(1))->collapseData(Phake::anyParameters());
        Phake::verifyNoOtherInteractions($report_model);
    }

    public function testCollapseDataWithTagSetNotIncludedEventsNotEmpty()
    {
        $tag_sets_included = array();
        $data = array(
            "objectives" => array(
                100 => array(
                    1 => array(
                        10 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                        11 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $unset_data = array(
            "objectives" => array(
                100 => array(
                ),
            ),
        );
        $data_for_objective = array(
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $merged_data_1 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $merged_data_2 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $expected_collapsed_data = array(
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->mergeData($unset_data, $data_for_objective)->thenReturn($merged_data_1);
        Phake::when($report_model)->mergeData($merged_data_1, $data_for_objective)->thenReturn($merged_data_2);
        Phake::when($report_model)->collapseData(Phake::anyParameters())->thenCallParent();
        $collapsed_data = Phake::makeVisible($report_model)->collapseData($data, $tag_sets_included);
        $this->assertEquals($expected_collapsed_data, $collapsed_data);
        Phake::verify($report_model, Phake::atLeast(1))->collapseData(Phake::anyParameters());
        Phake::verify($report_model, Phake::atLeast(1))->mergeData(Phake::anyParameters());
        Phake::verifyNoOtherInteractions($report_model);
    }

    public function testCollapseDataWithTagSetIncludedEventsEmpty()
    {
        $tag_sets_included = array(1);
        $data = array(
            "objectives" => array(
                101 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                101 => array(
                                    2 => array(
                                        20 => array(
                                        ),
                                        21 => array(
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        11 => array(
                            "objectives" => array(
                                101 => array(
                                    2 => array(
                                        20 => array(
                                        ),
                                        21 => array(
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $expected_collapsed_data = array(
            "objectives" => array(
                101 => array(
                    1 => array(
                        10 => array(
                        ),
                        11 => array(
                        ),
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->collapseData(Phake::anyParameters())->thenCallParent();
        $collapsed_data = Phake::makeVisible($report_model)->collapseData($data, $tag_sets_included);
        $this->assertEquals(array(), $collapsed_data);
        Phake::verify($report_model, Phake::atLeast(1))->collapseData(Phake::anyParameters());
        Phake::verifyNoOtherInteractions($report_model);
    }

    public function testCollapseDataWithTagSetIncludedEventsNotEmpty()
    {
        $tag_sets_included = array(1);
        $data = array(
            "objectives" => array(
                100 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                100 => array(
                                    2 => array(
                                        20 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60.0,
                                                    "event_type_duration" => array(
                                                        1 => 60.0,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                        ),
                                        21 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60.0,
                                                    "event_type_duration" => array(
                                                        1 => 60.0,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                101 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                101 => array(
                                    2 => array(
                                        20 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 30.0,
                                                    "event_type_duration" => array(
                                                        1 => 30.0,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                102 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                102 => array(
                                    2 => array(
                                        21 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 50.0,
                                                    "event_type_duration" => array(
                                                        1 => 50.0,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $unset_data_1 = array(
            "objectives" => array(
                100 => array(
                ),
            ),
        );
        $unset_data_2 = array(
            "objectives" => array(
                101 => array(
                ),
            ),
        );
        $unset_data_3 = array(
            "objectives" => array(
                102 => array(
                ),
            ),
        );
        $data_for_objective_1 = array(
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $data_for_objective_2 = array(
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $data_for_objective_3 = array(
            "event" => array(
                array(
                    "event_duration" => 30.0,
                    "event_type_duration" => array(
                        1 => 30.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $data_for_objective_4 = array(
            "event" => array(
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 3,
                ),
            ),
        );
        $merged_data_1 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $merged_data_2 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
                array(
                    "event_duration" => 60.0,
                    "event_type_duration" => array(
                        1 => 60.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $merged_data_3 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 30.0,
                    "event_type_duration" => array(
                        1 => 30.0,
                    ),
                    "total_mappings" => 2,
                ),
            ),
        );
        $merged_data_4 = array(
            "objectives" => array(
            ),
            "event" => array(
                array(
                    "event_duration" => 50.0,
                    "event_type_duration" => array(
                        1 => 50.0,
                    ),
                    "total_mappings" => 3,
                ),
            ),
        );
        $expected_collapsed_data = array(
            "objectives" => array(
                100 => array(
                    1 => array(
                        10 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                                array(
                                    "event_duration" => 60.0,
                                    "event_type_duration" => array(
                                        1 => 60.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                    ),
                ),
                101 => array(
                    1 => array(
                        10 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 30.0,
                                    "event_type_duration" => array(
                                        1 => 30.0,
                                    ),
                                    "total_mappings" => 2,
                                ),
                            ),
                        ),
                    ),
                ),
                102 => array(
                    1 => array(
                        10 => array(
                            "event" => array(
                                array(
                                    "event_duration" => 50.0,
                                    "event_type_duration" => array(
                                        1 => 50.0,
                                    ),
                                    "total_mappings" => 3,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->mergeData($unset_data_1, $data_for_objective_1)->thenReturn($merged_data_1);
        Phake::when($report_model)->mergeData($merged_data_1, $data_for_objective_2)->thenReturn($merged_data_2);
        Phake::when($report_model)->mergeData($unset_data_2, $data_for_objective_3)->thenReturn($merged_data_3);
        Phake::when($report_model)->mergeData($unset_data_3, $data_for_objective_4)->thenReturn($merged_data_4);
        Phake::when($report_model)->collapseData(Phake::anyParameters())->thenCallParent();
        $collapsed_data = Phake::makeVisible($report_model)->collapseData($data, $tag_sets_included);
        $this->assertEquals($expected_collapsed_data, $collapsed_data);
        Phake::verify($report_model, Phake::atLeast(1))->collapseData(Phake::anyParameters());
        Phake::verify($report_model, Phake::atLeast(1))->mergeData(Phake::anyParameters());
        Phake::verifyNoOtherInteractions($report_model);
    }

    public function testGroupEventsLinkedToObjectives()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $version_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        $main_objective_ids = array(10);
        $tag_sets_included = array(1, 2);
        $main_data = array("main data");
        $collapsed_main_data = array("collapsed data");
        $expected_data = array("collapsed data");
        Phake::when($report_model)->eventsLinkedToObjectiveSet($main_tag_set_id, $main_objective_ids, $version_id, $course_id, $cunit_ids, $start, $end)->thenReturn($main_data);
        Phake::when($report_model)->collapseData($main_data, $tag_sets_included)->thenReturn($collapsed_main_data);
        Phake::when($report_model)->groupEventsLinkedToObjectives(Phake::anyParameters())->thenCallParent();
        $data = Phake::makeVisible($report_model)->groupEventsLinkedToObjectives($main_tag_set_id, $main_objective_ids, $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end);
        $this->assertEquals($expected_data, $data);
    }

    public function testGroupEventsLinkedToObjectiveSet()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $version_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        $main_objective_rows = array(10 => array("objective_id" => 10));
        $main_objective_ids = array(10);
        $main_data = array("main data");
        $collapsed_main_data = array("collapsed data");
        $expected_data = array("collapsed data");
        Phake::when($this->objective_repository)->fetchAllByTagSetIDAndOrganisationID(Phake::anyParameters())->thenReturn(array("objectives1"));
        Phake::when($this->objective_repository)->fetchAllByTagSetID(Phake::anyParameters())->thenReturn(array("objectives2"));
        Phake::when($this->objective_repository)->toArrays(Phake::anyParameters())->thenReturn($main_objective_rows);
        Phake::when($report_model)->groupEventsLinkedToObjectives($main_tag_set_id, $main_objective_ids, $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end)->thenReturn($collapsed_main_data);
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(Phake::anyParameters())->thenCallParent();
        $data = Phake::makeVisible($report_model)->groupEventsLinkedToObjectiveSet($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end);
        $this->assertEquals($expected_data, $data);
        Phake::verify($this->objective_repository)->fetchAllByTagSetIDAndOrganisationID(1, 1);
        Phake::verify($this->objective_repository, Phake::never())->fetchAllByTagSetID(Phake::anyParameters());
        Phake::verify($this->objective_repository)->toArrays(array("objectives1"));
    }

    public function testCollectGroupedData()
    {
        $data = array(
            "objectives"  => array(
                100 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                100 => array(
                                    2 => array(
                                        20 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                        21 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
                101 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                101 => array(
                                    2 => array(
                                        20 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 50,
                                                    "event_type_duration" => array(
                                                        1 => 50,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
                102 => array(
                    1 => array(
                        10 => array(
                            "objectives" => array(
                                102 => array(
                                    2 => array(
                                        21 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 180,
                                                    "event_type_duration" => array(
                                                        1 => 180,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 3,
                        ),
                    ),
                ),
                103 => array(
                    1 => array(
                        11 => array(
                            "objectives" => array(
                                103 => array(
                                    2 => array(
                                        22 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 120,
                                                    "event_type_duration" => array(
                                                        1 => 120,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                                array(
                                                    "event_duration" => 120,
                                                    "event_type_duration" => array(
                                                        1 => 120,
                                                    ),
                                                    "total_mappings" => 6,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 4,
                        ),
                    ),
                ),
                104 => array(
                    1 => array(
                        11 => array(
                            "objectives" => array(
                                104 => array(
                                    2 => array(
                                        23 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                                array(
                                                    "event_duration" => 60,
                                                    "event_type_duration" => array(
                                                        1 => 60,
                                                    ),
                                                    "total_mappings" => 2,
                                                ),
                                            ),
                                            "total_mappings" => 2,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 4,
                        ),
                    ),
                ),
                105 => array(
                    3 => array(
                        31 => array(
                            "objectives" => array(
                                105 => array(
                                    4 => array(
                                        41 => array(
                                            "event" => array(
                                                array(
                                                    "event_duration" => 50,
                                                    "event_type_duration" => array(
                                                        1 => 50,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                                array(
                                                    "event_duration" => 50,
                                                    "event_type_duration" => array(
                                                        1 => 50,
                                                    ),
                                                    "total_mappings" => 3,
                                                ),
                                            ),
                                            "total_mappings" => 1,
                                        ),
                                    ),
                                ),
                            ),
                            "total_mappings" => 5,
                        ),
                    ),
                ),
            ),
        );
        $expected_grouped_data = array(
            array(
                array(
                    1 => 10,
                    2 => 20,
                ),
                array(
                    100 => array(
                        "event_duration" => 60,
                        "duration" => (60.0 / 2 / (3 * 2)) + (60.0 / 2 / (3 * 2)),
                        "event_type_duration" => array(
                            1 => (60.0 / 2 / (3 * 2)) + (60.0 / 2 / (3 * 2)),
                        ),
                        "mappings" => 2,
                    ),
                ),
            ),
            array(
                array(
                    1 => 10,
                    2 => 21,
                ),
                array(
                    100 => array(
                        "event_duration" => 60,
                        "duration" => (60.0 / 2 / (3 * 2)) + (60.0 / 2 / (3 * 2)),
                        "event_type_duration" => array(
                            1 => (60.0 / 2 / (3 * 2)) + (60.0 / 2 / (3 * 2)),
                        ),
                        "mappings" => 2,
                    ),
                ),
            ),
            array(
                array(
                    1 => 10,
                    2 => 20,
                ),
                array(
                    101 => array(
                        "event_duration" => 50,
                        "duration" => (50.0 / 3 / (3 * 2)),
                        "event_type_duration" => array(
                            1 => (50.0 / 3 / (3 * 2)),
                        ),
                        "mappings" => 1,
                    ),
                ),
            ),
            array(
                array(
                    1 => 10,
                    2 => 21,
                ),
                array(
                    102 => array(
                        "event_duration" => 180,
                        "duration" => (180.0 / 3 / (3 * 2)),
                        "event_type_duration" => array(
                            1 => (180.0 / 3 / (3 * 2)),
                        ),
                        "mappings" => 1,
                    ),
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 22,
                ),
                array(
                    103 => array(
                        "event_duration" => 120,
                        "duration" => (120.0 / 3 / (4 * 2)) + (120.0 / 6 / (4 * 2)),
                        "event_type_duration" => array(
                            1 => (120.0 / 3 / (4 * 2)) + (120.0 / 6 / (4 * 2)),
                        ),
                        "mappings" => 2,
                    ),
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 23,
                ),
                array(
                    104 => array(
                        "event_duration" => 60,
                        "duration" => (60.0 / 2 / (4 * 2)) + (60.0 / 2 / (4 * 2)),
                        "event_type_duration" => array(
                            1 => (60.0 / 2 / (4 * 2)) + (60.0 / 2 / (4 * 2)),
                        ),
                        "mappings" => 2,
                    ),
                ),
            ),
            array(
                array(
                    3 => 31,
                    4 => 41,
                ),
                array(
                    105 => array(
                        "event_duration" => 50,
                        "duration" => (50.0 / 3 / (5 * 1)) + (50.0 / 3 / (5 * 1)),
                        "event_type_duration" => array(
                            1 => (50.0 / 3 / (5 * 1)) + (50.0 / 3 / (5 * 1)),
                        ),
                        "mappings" => 2,
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->collectGroupedData(Phake::anyParameters())->thenCallParent();
        $grouped_data = Phake::makeVisible($report_model)->collectGroupedData($data);
        $this->assertEquals($expected_grouped_data, $grouped_data);
    }

    public function testCollateGroupedData()
    {
        $rows = array(
            array(
                array(
                    1 => 11,
                    2 => 21,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 10.0,
                            "event_type_duration" => array(
                                1 => 10.0,
                            ),
                            "mappings" => 2,
                        ),
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 25.0,
                            "event_type_duration" => array(
                                1 => 25.0,
                            ),
                            "mappings" => 2,
                        ),
                ),
            ),
            array(
                array(
                    1 => 12,
                    2 => 22,
                ),
                array(
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 25.0,
                            "event_type_duration" => array(
                                1 => 25.0,
                            ),
                            "mappings" => 2,
                        ),
                ),
            ),
        );
        $join_with_rows = array(
            array(
                array(
                    3 => 31,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "mappings" => 2,
                        ),
                    102 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "mappings" => 3,
                        ),
                ),
            ),
            array(
                array(
                    3 => 32,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 60.0,
                            "event_type_duration" => array(
                                1 => 60.0,
                            ),
                            "mappings" => 3,
                        ),
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 50.0,
                            "event_type_duration" => array(
                                1 => 50.0,
                            ),
                            "mappings" => 1,
                        ),
                    102 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 20.0,
                            "event_type_duration" => array(
                                1 => 20.0,
                            ),
                            "mappings" => 2,
                        ),
                ),
            ),
        );
        $expected_new_rows = array(
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 31,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0 * (10.0 / 60.0),
                            "event_type_duration" => array(
                                1 => 30.0 * (10.0 / 60.0),
                            ),
                            "mappings" => 2 * 2,
                        ),
                ),
            ),
            array(
                array(
                    1 => 12,
                    2 => 22,
                    3 => 31,
                ),
                array(
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 32,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 60.0 * (10.0 / 60.0),
                            "event_type_duration" => array(
                                1 => 60.0 * (10.0 / 60.0),
                            ),
                            "mappings" => 2 * 3,
                        ),
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 50.0 * (25.0 / 50.0),
                            "event_type_duration" => array(
                                1 => 50.0 * (25.0 / 50.0),
                            ),
                            "mappings" => 1 * 2,
                        ),
                ),
            ),
            array(
                array(
                    1 => 12,
                    2 => 22,
                    3 => 32,
                ),
                array(
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 50.0 * (25.0 / 50.0),
                            "event_type_duration" => array(
                                1 => 50.0 * (25.0 / 50.0),
                            ),
                            "mappings" => 1 * 2,
                        ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->collateGroupedData(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->collateGroupedData($rows, $join_with_rows);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testAggregateEventValues()
    {
        $rows = array(
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 31,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 5.0,
                            "event_type_duration" => array(
                                1 => 5.0,
                            ),
                            "mappings" => 4,
                        ),
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 32,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 10.0,
                            "event_type_duration" => array(
                                1 => 10.0,
                            ),
                            "mappings" => 6,
                        ),
                    101 =>
                        array(
                            "event_duration" => 50.0,
                            "duration" => 25.0,
                            "event_type_duration" => array(
                                1 => 25.0,
                            ),
                            "mappings" => 2,
                        ),
                ),
            ),
        );
        $expected_new_rows = array(
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 31,
                ),
                array(
                    "duration" => 5.0,
                    "mappings" => 4,
                    "event_type_duration" => array(
                        1 => 5.0,
                    ),
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 21,
                    3 => 32,
                ),
                array(
                    "duration" => 35.0,
                    "mappings" => 8,
                    "event_type_duration" => array(
                        1 => 35.0,
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->aggregateEventValues(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->aggregateEventValues($rows);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testValuesForObjectivesEmptyFilter()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2, 3, 4);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $filter_objective_ids_by_tag_set = array();
        $data_1 = array("data1");
        $data_2 = array("data2");
        $data_3 = array("data3");
        $data_4 = array("data4");
        $rows_1 = array(
            array(
                array(
                    1 => 11,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "total_mappings" => 2,
                        ),
                ),
            )
        );
        $rows_2 = array(
            array(
                array(
                    2 => 21,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 20.0,
                            "event_type_duration" => array(
                                1 => 20.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
        );
        $rows_3 = array(
            array(
                array(
                    2 => 21,
                    3 => 31,
                    4 => 41,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 10.0,
                            "event_type_duration" => array(
                                1 => 10.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
        );
        $rows_4 = array(
            array(
                array(
                    4 => 41,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "total_mappings" => 1,
                        ),
                ),
            ),
        );
        $joined_rows = array("joined rows");
        $agg_rows = array("aggregated rows");
        $expected_new_rows = $agg_rows;
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->version_repository)->fetchVersionsByDateRange($start, $end, $course_id)->thenReturn(array(1 => $version));
        $report_model = Phake::partialMock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(1, array(1=>2, 2=>3, 3=>4), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_1);
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(2, array(0=>1, 2=>3, 3=>4), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_2);
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(3, array(0=>1, 1=>2, 3=>4), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_3);
        Phake::when($report_model)->collectGroupedData($data_1)->thenReturn($rows_1);
        Phake::when($report_model)->collectGroupedData($data_2)->thenReturn($rows_2);
        Phake::when($report_model)->collectGroupedData($data_3)->thenReturn($rows_3);
        Phake::when($report_model)->collateGroupedData($rows_1, $rows_3)->thenReturn($joined_rows);
        Phake::when($report_model)->aggregateEventValues($joined_rows)->thenReturn($agg_rows);
        Phake::when($report_model)->valuesForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
        Phake::verify($report_model, Phake::never())->groupEventsLinkedToObjectiveSet(4, $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything());
    }

    public function testValuesForObjectivesFilterNotUsed()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $filter_objective_ids_by_tag_set = array(3 => 31);
        $data_1 = array("data1");
        $data_2 = array("data2");
        $data_3 = array("data3");
        $rows_1 = array(
            array(
                array(
                    1 => 11,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "total_mappings" => 2,
                        ),
                ),
            )
        );
        $rows_2 = array(
            array(
                array(
                    2 => 21,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 20.0,
                            "event_type_duration" => array(
                                1 => 20.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
        );
        $rows_3 = array(
            array(
                array(
                    3 => 31,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 10.0,
                            "event_type_duration" => array(
                                1 => 10.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
        );
        $joined_rows_1 = array("joined rows 1");
        $joined_rows_2 = array("joined rows 2");
        $agg_rows = array("aggregated rows");
        $expected_new_rows = $agg_rows;
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->version_repository)->fetchVersionsByDateRange($start, $end, $course_id)->thenReturn(array(1 => $version));
        $report_model = Phake::partialMock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(1, array(1=>2, 2=>3), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_1);
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(2, array(0=>1, 2=>3), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_2);
        Phake::when($report_model)->groupEventsLinkedToObjectives(3, array(31), array(0=>1, 1=>2), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_3);
        Phake::when($report_model)->collectGroupedData($data_1)->thenReturn($rows_1);
        Phake::when($report_model)->collectGroupedData($data_2)->thenReturn($rows_2);
        Phake::when($report_model)->collectGroupedData($data_3)->thenReturn($rows_3);
        Phake::when($report_model)->collateGroupedData($rows_1, $rows_2)->thenReturn($joined_rows_1);
        Phake::when($report_model)->collateGroupedData($joined_rows_1, $rows_3)->thenReturn($joined_rows_2);
        Phake::when($report_model)->aggregateEventValues($joined_rows_2)->thenReturn($agg_rows);
        Phake::when($report_model)->valuesForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
        Phake::verify($report_model)->groupEventsLinkedToObjectives(3, $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything());
    }

    public function testValuesForObjectivesFilterUsed()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $filter_objective_ids_by_tag_set = array(3 => 31);
        $data_1 = array("data1");
        $data_2 = array("data2");
        $data_3 = array("data3");
        $rows_1 = array(
            array(
                array(
                    1 => 11,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 30.0,
                            "event_type_duration" => array(
                                1 => 30.0,
                            ),
                            "total_mappings" => 2,
                        ),
                ),
            )
        );
        $rows_2 = array(
            array(
                array(
                    2 => 21,
                    3 => 31,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 20.0,
                            "event_type_duration" => array(
                                1 => 20.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
            array(
                array(
                    2 => 22,
                    3 => 32,
                ),
                array(
                    100 =>
                        array(
                            "event_duration" => 60.0,
                            "duration" => 20.0,
                            "event_type_duration" => array(
                                1 => 20.0,
                            ),
                            "total_mappings" => 3,
                        ),
                ),
            ),
        );
        $rows_2_filtered = array($rows_2[0]);
        $joined_rows = array("joined rows");
        $agg_rows = array("aggregated rows");
        $expected_new_rows = $agg_rows;
        $version = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->version_repository)->fetchVersionsByDateRange($start, $end, $course_id)->thenReturn(array(1 => $version));
        $report_model = Phake::partialMock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(1, array(1=>2, 2=>3), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_1);
        Phake::when($report_model)->groupEventsLinkedToObjectiveSet(2, array(0=>1, 2=>3), 1, 1, 1, $cunit_ids, 1000, 2000)->thenReturn($data_2);
        Phake::when($report_model)->collectGroupedData($data_1)->thenReturn($rows_1);
        Phake::when($report_model)->collectGroupedData($data_2)->thenReturn($rows_2);
        Phake::when($report_model)->collateGroupedData($rows_1, $this->anything())->thenReturn($joined_rows);
        Phake::when($report_model)->aggregateEventValues($joined_rows)->thenReturn($agg_rows);
        Phake::when($report_model)->valuesForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
        Phake::verify($report_model)->collateGroupedData($rows_1, $rows_2_filtered);
    }

    public function testAggregateForTagSet()
    {
        $main_tag_set_id = 1;
        $value_by_values = function($values) {
            return $values["duration"];
        };
        $rows = array(
            array(
                array(
                    1 => 11,
                    2 => 21,
                ),
                array(
                    "duration" => 10.0,
                    "mappings" => 2,
                ),
            ),
            array(
                array(
                    1 => 12,
                    2 => 21,
                ),
                array(
                    "duration" => 15.0,
                    "mappings" => 3,
                ),
            ),
            array(
                array(
                    1 => 11,
                    2 => 22,
                ),
                array(
                    "duration" => 20.0,
                    "mappings" => 4,
                ),
            ),
        );
        $expected_new_rows = array(
            array(
                array(
                    2 => 21,
                ),
                array(
                    11 => 10.0,
                    12 => 15.0,
                ),
            ),
            array(
                array(
                    2 => 22,
                ),
                array(
                    11 => 20.0,
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->aggregateForTagSet(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->aggregateForTagSet($main_tag_set_id, $rows, $value_by_values);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testDurationsForObjectives()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 1000;
        $filter_objective_ids_by_tag_set = array(3 => 30);
        $rows = array("rows");
        $agg_rows = array("aggregated rows");
        $expected_new_rows = $agg_rows;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set)->thenReturn($rows);
        $aggregate_stub = function ($main_tag_set_id, $rows, $value_by_values) use ($agg_rows) {
            if ($value_by_values(array("duration" => 10.0)) == 10.0) {
                return $agg_rows;
            } else {
                return null;
            }
        };
        Phake::when($report_model)->aggregateForTagSet($main_tag_set_id, $rows, $this->anything())->thenReturnCallback($aggregate_stub);
        Phake::when($report_model)->durationsForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->durationsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testMappingsForObjectives()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 1000;
        $filter_objective_ids_by_tag_set = array(3 => 30);
        $rows = array("rows");
        $agg_rows = array("aggregated rows");
        $expected_new_rows = $agg_rows;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set)->thenReturn($rows);
        $aggregate_stub = function ($main_tag_set_id, $rows, $value_by_values) use ($agg_rows) {
            if ($value_by_values(array("mappings" => 2)) == 2) {
                return $agg_rows;
            } else {
                return null;
            }
        };
        Phake::when($report_model)->aggregateForTagSet($main_tag_set_id, $rows, $this->anything())->thenReturnCallback($aggregate_stub);
        Phake::when($report_model)->mappingsForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->mappingsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testAggregateByEventType()
    {
        $agg_rows = array(
            array(
                array(
                    1 => 10,
                ),
                array(
                    20 => array(
                        123 => 60.0,
                        124 => 30.0,
                    ),
                ),
            ),
            array(
                array(
                    1 => 11,
                ),
                array(
                    21 => array(
                        123 => 45.0,
                        124 => 15.0,
                    ),
                ),
            ),
        );
        $expected_rows_by_event_type = array(
            123 => array(
                array(
                    array(
                        1 => 10,
                    ),
                    array(
                        20 => 60.0,
                    ),
                ),
                array(
                    array(
                        1 => 11,
                    ),
                    array(
                        21 => 45.0,
                    ),
                ),
            ),
            124 => array(
                array(
                    array(
                        1 => 10,
                    ),
                    array(
                        20 => 30.0,
                    ),
                ),
                array(
                    array(
                        1 => 11,
                    ),
                    array(
                        21 => 15.0,
                    ),
                ),
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->aggregateByEventTypes(Phake::anyParameters())->thenCallParent();
        $rows_by_event_type = Phake::makeVisible($report_model)->aggregateByEventTypes($agg_rows);
        $this->assertEquals($expected_rows_by_event_type, $rows_by_event_type);
    }

    public function testEventTypeDurationsForObjectives()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 1000;
        $filter_objective_ids_by_tag_set = array(3 => 30);
        $rows = array("rows");
        $agg_rows = array("aggregated rows");
        $rows_by_event_type = array(123 => array("aggregated rows"));
        $expected_new_rows = $rows_by_event_type;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set)->thenReturn($rows);
        $aggregate_stub = function ($main_tag_set_id, $rows, $value_by_values) use ($agg_rows) {
            if ($value_by_values(array("event_type_duration" => array(123 => 60.0))) == array(123 => 60.0)) {
                return $agg_rows;
            } else {
                return null;
            }
        };
        Phake::when($report_model)->aggregateForTagSet($main_tag_set_id, $rows, $this->anything())->thenReturnCallback($aggregate_stub);
        Phake::when($report_model)->aggregateByEventTypes($agg_rows)->thenReturn($rows_by_event_type);
        Phake::when($report_model)->eventTypeDurationsForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->eventTypeDurationsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testEventTypeMappingsForObjectives()
    {
        $main_tag_set_id = 1;
        $group_by_tag_set_ids = array(2);
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 1000;
        $filter_objective_ids_by_tag_set = array(3 => 30);
        $rows = array("rows");
        $agg_rows = array("aggregated rows");
        $rows_by_event_type = array(123 => array("aggregated rows"));
        $expected_new_rows = $rows_by_event_type;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set)->thenReturn($rows);
        $aggregate_stub = function ($main_tag_set_id, $rows, $value_by_values) use ($agg_rows) {
            if ($value_by_values(array("mappings" => 3, "duration" => 90.0, "event_type_duration" => array(123 => 60.0, 124 => 30.0))) == array(123 => 2.0, 124 => 1.0)) {
                return $agg_rows;
            } else {
                return null;
            }
        };
        Phake::when($report_model)->aggregateForTagSet($main_tag_set_id, $rows, $this->anything())->thenReturnCallback($aggregate_stub);
        Phake::when($report_model)->aggregateByEventTypes($agg_rows)->thenReturn($rows_by_event_type);
        Phake::when($report_model)->eventTypeMappingsForObjectives(Phake::anyParameters())->thenCallParent();
        $new_rows = Phake::makeVisible($report_model)->eventTypeMappingsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_rows, $new_rows);
    }

    public function testAggregateByTagSet()
    {
        $main_tag_set_id = 1;
        $rows = array(
            array(
                array(
                    1 => 11,
                ),
                array(
                    "duration" => 10.0,
                    "mappings" => 2,
                ),
            ),
            array(
                array(
                    1 => 12,
                ),
                array(
                    "duration" => 15.0,
                    "mappings" => 3,
                ),
            ),
        );
        $expected_values_by_objective = array(
            11 => array(
                "duration" => 10.0,
                "mappings" => 2,
            ),
            12 => array(
                "duration" => 15.0,
                "mappings" => 3,
            ),
        );
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->aggregateByTagSet(Phake::anyParameters())->thenCallParent();
        $values_by_objective = Phake::makeVisible($report_model)->aggregateByTagSet($main_tag_set_id, $rows);
        $this->assertEquals($expected_values_by_objective, $values_by_objective);
    }

    public function testValuesByObjectives()
    {
        $main_tag_set_id = 1;
        $organisation_id = 1;
        $course_id = 1;
        $cunit_ids = array(1, 2);
        $start = 1000;
        $end = 2000;
        $filter_objective_ids_by_tag_set = array(3 => 30);
        $group_by_tag_set_ids = array();
        $rows = array("rows");
        $values_by_objective = array("values by objective");
        $expected_new_values_by_objective = $values_by_objective;
        $report_model = Phake::mock("Models_Reports_ObjectiveMappings");
        Phake::when($report_model)->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set)->thenReturn($rows);
        Phake::when($report_model)->aggregateByTagSet($main_tag_set_id, $rows)->thenReturn($values_by_objective);
        Phake::when($report_model)->valuesByObjectives(Phake::anyParameters())->thenCallParent();
        $new_values_by_objective = Phake::makeVisible($report_model)->valuesByObjectives($main_tag_set_id, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $this->assertEquals($expected_new_values_by_objective, $new_values_by_objective);
    }
}

if (!defined("PHPUnit_MAIN_METHOD")) {
    Models_Reports_ObjectiveMappingsTest::main();
}
