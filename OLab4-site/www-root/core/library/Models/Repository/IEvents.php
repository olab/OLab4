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

interface Models_Repository_IEvents extends Models_IRepository {

    public function fetchAllByCunitID($cunit_id);

    public function fetchAllByCunitIDs(array $cunit_ids);

    public function fetchAllByCourseID($course_id);

    public function fetchAllByCourseIDs(array $course_ids);

    public function fetchAllByObjectiveIDs(array $objective_ids);

    public function fetchAllByObjectiveIDsAndFilters(array $objective_ids, array $filters);

    public function fetchTotalMappingsByEventIDs(array $event_ids);

    public function fetchEventTypesByEventIDs(array $event_ids);
}
