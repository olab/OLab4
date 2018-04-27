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

interface Models_Repository_ICurriculumMapVersions extends Models_IRepository {

    public function eventsForVersion($version_id, array $events);

    public function fetchLatestVersionsByEventIDs(array $event_ids);

    public function fetchVersionsByEventID($event_id);

    public function fetchVersionsByEventIDs(array $event_ids);

    public function fetchVersionsByCourseIDCperiodID($course_id, $cperiod_id);

    public function fetchVersionsByDateRange($start, $end, $course_id);
}
