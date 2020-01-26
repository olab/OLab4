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

abstract class Entrada_Curriculum_Context_Specific extends Entrada_Curriculum_Context implements Entrada_Curriculum_Context_ISpecific {

    public function __construct() {
        parent::__construct(array());
        $this->event_ids = array();
        $this->cunit_ids = array();
        $this->course_ids = array();
    }

    public function getID() {
        if (count($event_ids = $this->getEventIDs()) == 1) {
            list($context_id) = $event_ids;
        } else if (count($cunit_ids = $this->getCunitIDs()) == 1) {
            list($context_id) = $cunit_ids;
        } else if (count($course_ids = $this->getCourseIDs()) == 1) {
            list($context_id) = $course_ids;
        } else {
            throw new Exception("Context ID not present. Why??");
        }
        return $context_id;
    }

    public function getTable() {
        if (count($this->getEventIDs()) == 1) {
            return "event_linked_objectives";
        } else if (count($this->getCunitIDs()) == 1) {
            return "course_unit_linked_objectives";
        } else if (count($this->getCourseIDs()) == 1) {
            return "course_linked_objectives";
        } else {
            throw new Exception("Context ID not present. Why??");
        }
    }

    public function getTaughtInTable() {
        if ($this->getEventIDs()) {
            return "event_objectives";
        } else if ($this->getCunitIDs()) {
            return "course_unit_objectives";
        } else if ($this->getCourseIDs()) {
            return "course_objectives";
        } else {
            throw new Exception("Context ID not present. Why??");
        }
    }

    public function getColumn() {
        if (count($this->getEventIDs()) == 1) {
            return "event_id";
        } else if (count($this->getCunitIDs()) == 1) {
            return "cunit_id";
        } else if (count($this->getCourseIDs()) == 1) {
            return "course_id";
        } else {
            throw new Exception("Context ID not present. Why??");
        }
    }
}
