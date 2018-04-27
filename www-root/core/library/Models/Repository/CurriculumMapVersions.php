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

class Models_Repository_CurriculumMapVersions extends Models_Repository implements Models_Repository_ICurriculumMapVersions, Entrada_IGettable {

    use Entrada_Gettable;

    public function fetchAllByIDs(array $event_ids) {
        global $db;
        if ($event_ids) {
            $query = "SELECT *
                      FROM `curriculum_map_versions`
                      WHERE `version_id` IN (".$this->quoteIDs($event_ids).")
                      ORDER BY `title` DESC";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function eventsForVersion($version_id, array $events) {
        $event_ids = array_map(function (Models_Event $event) { return $event->getID(); }, $events);
        $versions_by_event = $this->fetchVersionsByEventIDs($event_ids);
        return array_filter($events, function (Models_Event $event) use ($version_id, $versions_by_event) {
            return isset($versions_by_event[$event->getID()][$version_id]);
        });
    }

    public function fetchLatestVersionsByEventIDs(array $event_ids) {
        $versions_by_event = $this->fetchVersionsByEventIDs($event_ids);
        return array_map(function (array $versions) { $first = current($versions); return $first; }, $versions_by_event);
    }

    public function fetchVersionsByEventID($event_id) {
        return $this->flatten($this->fetchVersionsByEventIDs(array($event_id)));
    }

    public function fetchVersionsByEventIDs(array $event_ids) {
        global $db;
        if ($event_ids) {
            $query = "SELECT `events`.`event_id`, `curriculum_map_versions`.*
                      FROM `events`
                      INNER JOIN `courses` ON `courses`.`course_id` = `events`.`course_id`
                      LEFT JOIN `course_audience` ON `course_audience`.`course_id` = `courses`.`course_id` AND `course_audience`.`audience_active` = 1
                      LEFT JOIN `curriculum_periods` ON `events`.`event_start` BETWEEN `curriculum_periods`.`start_date` AND `curriculum_periods`.`finish_date` AND `curriculum_periods`.`active` = 1 AND `curriculum_periods`.`cperiod_id` = `course_audience`.`cperiod_id`
                      LEFT JOIN `curriculum_map_version_periods` ON `curriculum_map_version_periods`.`cperiod_id` = `curriculum_periods`.`cperiod_id`
                      LEFT JOIN `curriculum_map_versions` ON `curriculum_map_versions`.`version_id` = `curriculum_map_version_periods`.`version_id` AND `curriculum_map_versions`.`deleted_date` IS NULL
                      WHERE `events`.`event_id` IN (".$this->quoteIDs($event_ids).")
                      GROUP BY `events`.`event_id`, `curriculum_map_versions`.`version_id`
                      ORDER BY (`curriculum_map_versions`.`status` = 'published') DESC, `curriculum_periods`.`finish_date` DESC";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("event_id", $results);
        } else {
            return array();
        }
    }

    public function fetchVersionsByCourseIDCperiodID($course_id, $cperiod_id) {
        global $db;
        $query = "SELECT `curriculum_map_version_periods`.`cperiod_id`, `curriculum_map_versions`.*
                  FROM `curriculum_map_versions`
                  INNER JOIN `curriculum_map_version_periods`
                  ON `curriculum_map_version_periods`.`version_id` = `curriculum_map_versions`.`version_id`
                  INNER JOIN `curriculum_periods`
                  ON `curriculum_periods`.`cperiod_id` = `curriculum_map_version_periods`.`cperiod_id`
                  INNER JOIN `course_audience`
                  ON `course_audience`.`cperiod_id` = `curriculum_periods`.`cperiod_id`
                  WHERE `curriculum_map_versions`.`deleted_date` IS NULL
                  AND `curriculum_periods`.`active` = 1
                  AND `course_audience`.`audience_active` = 1
                  AND `curriculum_map_version_periods`.`cperiod_id` = ".$db->qstr($cperiod_id)."
                  AND `course_audience`.`course_id` = ".$db->qstr($course_id)."
                  GROUP BY `curriculum_map_versions`.`version_id`
                  ORDER BY (`curriculum_map_versions`.`status` = 'published') DESC, `curriculum_periods`.`finish_date` DESC";
        $results = $db->GetAll($query);
        return $this->fromArrays($results);
    }

    public function fetchVersionsByDateRange($start, $end, $course_id) {
        global $db;
        $query = "SELECT `curriculum_map_versions`.*
                  FROM `curriculum_map_versions`
                  INNER JOIN `curriculum_map_version_periods`
                  ON `curriculum_map_version_periods`.`version_id` = `curriculum_map_versions`.`version_id`
                  INNER JOIN `curriculum_periods`
                  ON `curriculum_periods`.`cperiod_id` = `curriculum_map_version_periods`.`cperiod_id`
                  INNER JOIN `courses` ON `courses`.`curriculum_type_id` = `curriculum_periods`.`curriculum_type_id`
                  WHERE `courses`.`course_id` = ".$db->qstr($course_id)."
                  AND `curriculum_map_versions`.`deleted_date` IS NULL
                  AND `curriculum_map_versions`.`status` = 'published'
                  AND `curriculum_periods`.`active` = 1
                  AND (
                    ".$db->qstr($start)." BETWEEN `curriculum_periods`.`start_date` AND `curriculum_periods`.`finish_date` OR
                    ".$db->qstr($end)." BETWEEN `curriculum_periods`.`start_date` AND `curriculum_periods`.`finish_date` OR
                    `curriculum_periods`.`start_date` BETWEEN ".$db->qstr($start)." AND ".$db->qstr($end)."
                  )";
        $results = $db->GetAll($query);
        return $this->fromArrays($results);
    }

    protected function fromArray(array $result) {
        return new Models_Curriculum_Map_Versions($result);
    }
}
