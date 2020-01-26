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

class Entrada_Curriculum_Context implements Entrada_Curriculum_IContext {

    protected $event_ids;
    protected $cunit_ids;
    protected $course_ids;
    protected $cperiod_id;

    public function __construct(array $context = array()) {
        foreach ($context as $key => $value) {
            if ($value) {
                switch ($key) {
                case "event_id":
                    $this->event_ids = array($value);
                    break;
                case "cunit_id":
                    $this->cunit_ids = array($value);
                    break;
                case "course_id":
                    $this->course_ids = array($value);
                    break;
                case "cperiod_id":
                    $this->cperiod_id = $value;
                    break;
                default:
                    throw new DomainException("Invalid context key ".$key." = ".$value);
                }
            }
        }
    }

    public function getEventIDs() {
        if (!isset($this->event_ids)) {
            if (count($this->cunit_ids) == 1) {
                $event_repository = Models_Repository_Events::getInstance();
                $events = $event_repository->flatten($event_repository->fetchAllByCunitIDs($this->cunit_ids));
                $this->event_ids = array_map(function (Models_Event $event) { return $event->getID(); }, $events);
            } else if ($this->course_ids) {
                $event_repository = Models_Repository_Events::getInstance();
                $events = $event_repository->flatten($event_repository->fetchAllByCourseIDs($this->course_ids));
                $this->event_ids = array_map(function (Models_Event $event) { return $event->getID(); }, $events);
            } else {
                $this->event_ids = array();
            }
        }
        return $this->event_ids;
    }

    public function getCunitIDs() {
        if (!isset($this->cunit_ids)) {
            if (count($this->event_ids) == 1) {
                $events = Models_Repository_Events::getInstance()->fetchAllByIDs($this->event_ids);
                $this->cunit_ids = array_unique(array_map(function (Models_Event $event) { return $event->getCunitID(); }, $events));
            } else if ($this->course_ids) {
                $course_unit_repository = Models_Repository_CourseUnits::getInstance();
                if ($this->cperiod_id) {
                    $course_units = $course_unit_repository->flatten($course_unit_repository->fetchAllByCourseIDsAndCperiodID($this->course_ids, $this->cperiod_id));
                } else {
                    $course_units = $course_unit_repository->flatten($course_unit_repository->fetchAllByCourseIDs($this->course_ids));
                }
                $this->cunit_ids = array_map(function (Models_Course_Unit $course_unit) { return $course_unit->getID(); }, $course_units);
            } else {
                $this->cunit_ids = array();
            }
        }
        return $this->cunit_ids;
    }

    public function getCourseIDs() {
        if (!isset($this->course_ids)) {
            if (count($this->event_ids) == 1) {
                $events = Models_Repository_Events::getInstance()->fetchAllByIDs($this->event_ids);
                $this->course_ids = array_unique(array_map(function (Models_Event $event) { return $event->getCourseID(); }, $events));
            } else if ($this->cunit_ids) {
                $course_units = Models_Repository_CourseUnits::getInstance()->fetchAllByIDs($this->cunit_ids);
                $this->course_ids = array_unique(array_map(function (Models_Course_Unit $course_unit) { return $course_unit->getCourseID(); }, $course_units));
            } else {
                $this->course_ids = array();
            }
        }
        return $this->course_ids;
    }

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    protected function quoteIDs(array $ids) {
        global $db;
        return implode(", ", array_map(array($db, "qstr"), $ids));
    }
}
