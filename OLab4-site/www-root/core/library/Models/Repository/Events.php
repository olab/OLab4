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

class Models_Repository_Events extends Models_Repository implements Models_Repository_IEvents, Entrada_IGettable {

    use Entrada_Gettable;

    public function fetchAllByIDs(array $event_ids) {
        global $db;
        if ($event_ids) {
            $query = "SELECT *
                      FROM `events`
                      WHERE `event_id` IN (".$this->quoteIDs($event_ids).")
                      ORDER BY `event_start` ASC, `updated_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function fetchAllByCunitID($cunit_id) {
        $events_by_course_unit = $this->fetchAllByCunitIDs(array($cunit_id));
        if (isset($events_by_course_unit[$cunit_id])) {
            return $events_by_course_unit[$cunit_id];
        } else {
            return array();
        }
    }

    public function fetchAllByCunitIDs(array $cunit_ids) {
        global $db;
        if ($cunit_ids) {
            $query = "SELECT *
                      FROM `events`
                      WHERE `cunit_id` IN (".$this->quoteIDs($cunit_ids).")
                      ORDER BY `event_start` ASC, `updated_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("cunit_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByCourseID($course_id) {
        $events_by_course = $this->fetchAllByCourseIDs(array($course_id));
        if (isset($events_by_course[$course_id])) {
            return $events_by_course[$course_id];
        } else {
            return array();
        }
    }

    public function fetchAllByCourseIDs(array $course_ids) {
        global $db;
        if ($course_ids) {
            $query = "SELECT *
                      FROM `events`
                      WHERE `course_id` IN (".$this->quoteIDs($course_ids).")
                      ORDER BY `event_start` ASC, `updated_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("course_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByObjectiveIDs(array $objective_ids) {
        global $db;
        if ($objective_ids) {
            $query = "SELECT eo.`objective_id`, e.*
                      FROM `events` e
                      INNER JOIN `event_objectives` eo ON eo.`event_id` = e.`event_id`
                      WHERE eo.`objective_id` IN (".$this->quoteIDs($objective_ids).")
                      ORDER BY e.`event_start` ASC, e.`updated_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("objective_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByObjectiveIDsAndFilters(array $objective_ids, array $filters) {
        global $db;
        if ($objective_ids) {
            if (!empty($filters["cunit_ids"])) {
                $cunit_sql = "AND e.`cunit_id` IN (".$this->quoteIDs($filters["cunit_ids"]).")";
            } else {
                $cunit_sql = "";
            }
            if (!empty($filters["course_ids"])) {
                $course_sql = "AND e.`course_id` IN (".$this->quoteIDs($filters["course_ids"]).")";
            } else {
                $course_sql = "";
            }
            if (!empty($filters["start"])) {
                $start_sql = "AND e.`event_start` >= ".$db->qstr($filters["start"]);
            } else {
                $start_sql = "";
            }
            if (!empty($filters["end"])) {
                $end_sql = "AND e.`event_start` + (e.`event_duration` * 60) <= ".$db->qstr($filters["end"]);
            } else {
                $end_sql = "";
            }
            $query = "SELECT eo.`objective_id`, e.event_id, e.event_duration
                      FROM `events` e
                      INNER JOIN `event_objectives` eo ON eo.`event_id` = e.`event_id`
                      WHERE eo.`objective_id` IN (".$this->quoteIDs($objective_ids).")
                      ".$cunit_sql."
                      ".$course_sql."
                      ".$start_sql."
                      ".$end_sql."
                      GROUP BY 1, 2, 3
                      ORDER BY e.`event_start` ASC, e.`updated_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("objective_id", $results);
        } else {
            return array();
        }
    }

    public function fetchTotalMappingsByEventIDs(array $event_ids) {
        global $db;
        if (!empty($event_ids)) {
            $query = "SELECT eo.`event_id`, o.`objective_parent`, COUNT(DISTINCT eo.`objective_id`) AS mappings
                      FROM `event_objectives` AS eo
                      INNER JOIN `global_lu_objectives` AS o ON o.`objective_id` = eo.`objective_id`
                      WHERE eo.`event_id` IN (".$this->quoteIDs($event_ids).")
                      GROUP BY eo.`event_id`, o.`objective_parent`";
            $rows = $db->GetAll($query);
            return array_reduce($rows, function (array $mappings_by_event, array $row) {
                $event_id = $row["event_id"];
                $objective_parent_id = $row["objective_parent"];
                $mappings_by_event[$event_id][$objective_parent_id] = (int) $row["mappings"];
                return $mappings_by_event;
            }, array());
        } else {
            return array();
        }
    }

    public function fetchEventTypesByEventIDs(array $event_ids) {
        global $db;
        if (!empty($event_ids)) {
            $query = "SELECT et.`event_id`, et.`eventtype_id`, et.`duration`
                      FROM `event_eventtypes` AS et
                      WHERE et.`event_id` IN (".$this->quoteIDs($event_ids).")";
            $rows = $db->GetAll($query);
            return array_reduce($rows, function (array $event_types, array $row) {
                $event_id = $row["event_id"];
                $eventtype_id = $row["eventtype_id"];
                $event_types[$event_id][$eventtype_id] = new Models_Event_EventType($row);
                return $event_types;
            }, array());
        } else {
            return array();
        }
    }

    /**
     * Fetch the data for event resources by event ids.
     *
     * @param array $event_ids Array of event ids.
     *
     * @return array
     */
    public function fetchEventResourcesByEventIDs(array $event_ids) {
        global $db;

        if (!empty($event_ids)) {

            $query = '
            SELECT eventfiles.*,
                events.`course_id`, events.`event_title`, events.`event_start`,
                courses.`organisation_id`, courses.`course_code`,
                periods.`start_date` AS `curriculum_start`

            FROM `event_files` AS eventfiles

            LEFT JOIN `events` AS events ON eventfiles.`event_id` = events.`event_id`
            LEFT JOIN `courses` AS courses ON events.`course_id` = courses.`course_id`
            LEFT JOIN `course_audience` AS course_audience ON courses.`course_id` = course_audience.`course_id`
            LEFT JOIN `curriculum_periods` AS periods ON course_audience.`cperiod_id` = periods.`cperiod_id`

            WHERE events.`event_id` IN ('.$this->quoteIDs($event_ids).")
                      AND (events.`release_date` = 0 OR events.`release_date` <= UNIX_TIMESTAMP())
                      AND (events.`release_until` = 0 OR events.`release_until` >= UNIX_TIMESTAMP())
                      AND (eventfiles.`release_date` = 0 OR eventfiles.`release_date` <= UNIX_TIMESTAMP())
                      AND (eventfiles.`release_until` = 0 OR eventfiles.`release_until` >= UNIX_TIMESTAMP())";

            $results = $db->GetAll($query);

            return $this->fromArraysBy("efile_id", $results);

        } else {

            return array();
        }
    }

    protected function fromArray(array $result) {
        return new Models_Event($result);
    }

    /**
     * Return the associated faculty in without indexed associative array.
     * Created this function because events_fetch_event_contacts does return
     * all teachers, tutors, TAs, and auditors for the specified learning event.
     * However it indexed the results by contact_role and that wasn't useful for
     * the purposes of simply just returning all associated faculty.
     *
     * @param integer $event_ids
     *
     * @return array
     */
    public function fetchAssociatedFacultyEventIDs($event_id = 0)
    {
        global $db;

        $contacts = array();

        if ($event_id) {
            $query = "SELECT user_data.`email`,
                event_contacts.`proxy_id`,
                event_contacts.`contact_role`,
                event_contacts.`contact_order`,
                CONCAT_WS(' ', user_data.`firstname`, user_data.`lastname`) AS `fullname`

            FROM `event_contacts`
            JOIN `".AUTH_DATABASE."`.`user_data`
            ON user_data.`id` = event_contacts.`proxy_id`
            WHERE event_contacts.`event_id` = ".$db->qstr((int) $event_id)."
            ORDER BY event_contacts.`contact_order` ASC";

            $contacts = $db->GetAll($query);
        }

        return $contacts;
    }
}
