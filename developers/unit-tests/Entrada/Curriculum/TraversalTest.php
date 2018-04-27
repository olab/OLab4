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
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
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

class Entrada_Curriculum_TraversalTest extends BaseTestCase {

    private $traversal;
    private $event_repository;
    private $objective_repository;
    private $version_repository;

    public function setUp() {
        parent::setUp();
        $this->traversal = Phake::partialMock("Entrada_Curriculum_Traversal");

        $this->event_repository = Phake::mock("Models_Repository_Events");
        Phake::when($this->event_repository)->flatten(Phake::anyParameters())->thenCallParent();
        Models_Repository_Events::setInstance($this->event_repository);

        $this->objective_repository = Phake::mock("Models_Repository_Objectives");
        Phake::when($this->objective_repository)->flatten(Phake::anyParameters())->thenCallParent();
        Phake::when($this->objective_repository)->hasMore(Phake::anyParameters())->thenReturn(true);
        Models_Repository_Objectives::setInstance($this->objective_repository);

        $this->version_repository = Phake::mock("Models_Repository_CurriculumMapVersions");
        Phake::when($this->version_repository)->flatten(Phake::anyParameters())->thenCallParent();
        Models_Repository_CurriculumMapVersions::setInstance($this->version_repository);
    }

    public function testEventIDsLinkedToObjectiveIDs() {
        $event101 = new Models_Event(array("event_id" => 101));
        Phake::when($this->traversal)->eventsLinkedToObjectiveIDs(Phake::anyParameters())->thenReturn(array(101 => $event101));
        $event_ids = $this->traversal->eventIDsLinkedToObjectiveIDs(array(11, 12, 13), false, array("filters"));
        $this->assertEquals(array(101 => 101), $event_ids);
        Phake::verify($this->traversal)->eventsLinkedToObjectiveIDs(array(11, 12, 13), false, array("filters"));
    }

    public function testEventsLinkedToObjectiveIDs_IgnoreDirectEventObjectives() {
        $event101 = new Models_Event(array("event_id" => 101));
        $event102 = new Models_Event(array("event_id" => 102));
        Phake::when($this->event_repository)->fetchAllByObjectiveIDsAndFilters(array(11), array("filters"))->thenReturn(array(11 => array(101 => $event101)));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(11), false, $this->anything(), $this->anything())->thenReturn(array(
            1 => array(
                null => array(
                    11 => array(
                        23 => new Models_Objective(23),
                    ),
                ),
            ),
        ));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(23 => 23), false, $this->anything(), $this->anything())->thenReturn(array(
            1 => array(
                101 => array(
                    23 => array(
                        21 => new Models_Objective(21),
                    ),
                ),
                102 => array(
                    23 => array(
                        22 => new Models_Objective(22),
                    ),
                ),
            ),
        ));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(), false, $this->anything(), $this->anything())->thenReturn(array());
        Phake::when($this->event_repository)->fetchAllByIDs(array(101, 102))->thenReturn(array(101 => $event101, 102 => $event102));
        $version1 = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->version_repository)->fetchLatestVersionsByEventIDs(Phake::anyParameters())->thenReturn(array(
            102 => $version1,
        ));
        $this->assertEquals(array(102 => $event102), $this->traversal->eventsLinkedToObjectiveIDs(array(11), true, array("filters")));
        Phake::verify($this->event_repository, Phake::never())->fetchAllByObjectiveIDsAndFilters(array(11), array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(11), false, false, array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(23 => 23), false, false, array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(), false, false, array("filters"));
        Phake::verify($this->event_repository)->fetchAllByIDs(array(101, 102));
        Phake::verify($this->version_repository)->fetchLatestVersionsByEventIDs(array(101 => 101, 102 => 102));
    }

    public function testEventsLinkedToObjectiveIDs_IncludeDirectEventObjectives() {
        $event101 = new Models_Event(array("event_id" => 101));
        $event102 = new Models_Event(array("event_id" => 102));
        Phake::when($this->event_repository)->fetchAllByObjectiveIDsAndFilters(array(11), array("filters"))->thenReturn(array(11 => array(101 => $event101)));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(11), false, $this->anything(), $this->anything())->thenReturn(array(
            1 => array(
                null => array(
                    11 => array(
                        23 => new Models_Objective(23),
                    ),
                ),
            ),
        ));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(23 => 23), false, $this->anything(), $this->anything())->thenReturn(array(
            1 => array(
                101 => array(
                    23 => array(
                        21 => new Models_Objective(21),
                    ),
                ),
                102 => array(
                    23 => array(
                        22 => new Models_Objective(22),
                    ),
                ),
            ),
        ));
        Phake::when($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(), false, $this->anything(), $this->anything())->thenReturn(array());
        Phake::when($this->event_repository)->fetchAllByIDs(array(101, 102))->thenReturn(array(101 => $event101, 102 => $event102));
        $version1 = new Models_Curriculum_Map_Versions(array("version_id" => 1));
        Phake::when($this->version_repository)->fetchLatestVersionsByEventIDs(Phake::anyParameters())->thenReturn(array(
            102 => $version1,
        ));
        $this->assertEquals(array(101 => $event101, 102 => $event102), $this->traversal->eventsLinkedToObjectiveIDs(array(11), false, array("filters")));
        Phake::verify($this->event_repository)->fetchAllByObjectiveIDsAndFilters(array(11), array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(11), false, false, array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(23 => 23), false, false, array("filters"));
        Phake::verify($this->objective_repository)->fetchLinkedObjectivesByIDsAndEvents("to", array(), false, false, array("filters"));
        Phake::verify($this->event_repository)->fetchAllByIDs(array(101, 102));
        Phake::verify($this->version_repository)->fetchLatestVersionsByEventIDs(array(101 => 101, 102 => 102));
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    Models_Repository_TraversalTest::main();
}
