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
 * A model for handling events
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Event extends Models_Base {
    protected $event_id,
              $parent_id,
              $event_children,
              $recurring_id,
              $region_id,
              $course_id,
              $event_phase,
              $event_title,
              $event_description,
              $include_parent_description,
              $event_goals,
              $event_objectives,
              $keywords_hidden,
              $keywords_release_date,
              $objectives_release_date,
              $event_message,
              $include_parent_message,
              $event_location,
              $room_id,
              $event_start,
              $event_finish,
              $event_duration,
              $attendance_required,
              $audience_visible,
              $release_date,
              $release_until,
              $event_color,
              $draft_id,
              $updated_date,
              $updated_by;

    protected $room, $building, $course;

    protected static $table_name = "events";
    protected static $primary_key = "event_id";
    protected static $default_sort_column = "event_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID() {
        return $this->event_id;
    }
    
    public function getParentID () {
        return $this->parent_id;
    }
    
    public function getEventChildren () {
        return $this->event_children;
    }
    
    public function getRecurringID () {
        return $this->recurring_id;
    }
    
    public function getRegionID () {
        return $this->region_id;
    }
    
    public function getCourseID () {
        return $this->course_id;
    }
    
    public function getEventPhase () {
        return $this->event_phase;
    }
    
    public function getEventTitle () {
        return $this->event_title;
    }
    
    public function getEventDescription () {
        return $this->event_description;
    }
    
    public function getIncludeParentDescription () {
        return $this->include_parent_description;
    }
    
    public function getEventGoals () {
        return $this->event_goals;
    }
    
    public function getEventObjectives () {
        return $this->event_objectives;
    }
    
    public function getKeywordsHidden() {
        return $this->keywords_hidden;
    }
    
    public function getKeywordsReleaseDate() {
        return $this->keywords_release_date;
    }
    
    public function getObjectivesReleaseDate () {
        return $this->objectives_release_date;
    }
    
    public function getEventMessage () {
        return $this->event_message;
    }
    
    public function getIncludeParentMessage () {
        return $this->include_parent_message;
    }
    
    public function getEventLocation () {
        return $this->event_location;
    }

    public function getRoomId() {
        return $this->room_id;
    }
    
    public function getEventStart () {
        return $this->event_start;
    }
    
    public function getEventFinish () {
        return $this->event_finish;
    }
    
    public function getEventDuration () {
        return $this->event_duration;
    }
    
    public function getAudienceVisible() {
        return $this->audience_visible;
    }

    public function getAttendanceRequired() {
        return $this->attendance_required;
    }
    
    public function getReleaseDate () {
        return $this->release_date;
    }
    
    public function getReleaseUntil () {
        return $this->release_until;
    }

    public function getColor() {
        return $this->event_color;
    }
    
    public function getDraftID () {
        return $this->draft_id;
    }
    
    public function getUpdatedDate () {
        return $this->updated_date;
    }
    
    public function getUpdatedBy () {
        return $this->updated_by;
    }
    
    public function getOrganisationID() {
        $course = Models_Course::get($this->course_id);
        if ($course) {
            return $course->getOrganisationID();
        } else {
            return false;
        }
    }

    public function setEventId($event_id) {
        $this->event_id = $event_id;
    }
    
    /* @return bool|Models_Exam_Post */
    public function getAttachedExams(){
        return Models_Exam_Post::fetchAllByEventID($this->event_id);
    }

    /* @return bool|Models_Event */
    public static function get($event_id = null) {
        $self = new self();
        return $self->fetchRow(array("event_id" => $event_id));
    }

    /* @return bool|Models_Event */
    public static function fetchRowByID($event_id = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Event[] */
    public static function fetchAllByParentID($parent_id = null) {
        $self = new self();
        $constraints = array(
            array(
                "key"=> "parent_id",
                "value" => $parent_id,
                "method"=>"="
                )
            );
        
        return $self->fetchRow($constraints);
    }
    
    /* @return ArrayObject|Models_Event[] */
    public static function fetchAllByCourseID($course_id = null) {
        $self = new self();
        return $self->fetchAll(array("course_id" => $course_id));
    }

    public static function fetchAllByCourseIDEventtypeID($course_id, $eventtype_id) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "course_id", "value" => $course_id, "method" => "="),
                array("key" => "eventtype_id", "value" => $eventtype_id, "method" => "=")
            )
        );
    }

    public function fetchAllByCourseIdStartDateFinishDate($course_id = 0, $start_date = 0, $finish_date = 0, $released_only = true) {
        global $db;

        $course_id = (int) $course_id;
        $start_date = (int) $start_date;
        $finish_date = (int) $finish_date;
        $released_only = (bool) $released_only;

        $events = false;

        $query = "SELECT *
                    FROM `events`
                    WHERE `course_id` = ?
                    AND `event_start` >= ?
                    AND `event_finish` <= ?";
        if ($released_only) {
            $query .= " AND (`release_date` = '0' OR `release_date` <= UNIX_TIMESTAMP())
                        AND (`release_until` = '0' OR `release_until` >= UNIX_TIMESTAMP())";
        }
        $query .= " ORDER BY `event_start` ASC";

        $results = $db->GetAll($query, array($course_id, $start_date, $finish_date));
        if ($results) {
            foreach ($results as $result) {
                $event = new self($result);
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Search the specified course_id for requested event_title.
     *
     * @param int $course_id
     * @param string $title
     * @return array
     */
    public function fetchAllByCourseIdTitle($course_id = 0, $title = "") {
        global $db;

        $course_id = (int) $course_id;
        $title = clean_input($title, ["striptags", "trim"]);

        $events = [];

        if ($course_id) {
            $query = "SELECT *
                        FROM `events` 
                        WHERE `course_id` = ?
                        AND (`event_id` = ? OR `event_title` LIKE ?)
                        AND (`parent_id` = 0 OR `parent_id` IS NULL)
                        ORDER BY `event_start` ASC";
            $results = $db->GetAll($query, [$course_id, (int) $title, ("%" . $title . "%")]);
            if ($results) {
                foreach ($results as $result) {
                    $event = new self($result);
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /* @return ArrayObject|Models_Event[] */
    public static function fetchAllByCourseIdTitleDates($course_id = null, $title = null, $cperiod_start_date = null, $cperiod_finish_date = null) {
        global $db;
        $events = false;
        
        $query = "  SELECT * FROM `events` 
                    WHERE  `course_id` = ?
                    AND `event_title` LIKE ?
                    AND `event_start` >= ?
                    AND `event_finish` <= ?
                    AND (`parent_id` = ? OR `parent_id` IS NULL)
                    ORDER BY `event_start` ASC";
        
        $results = $db->GetAll($query, array($course_id, "%".$title."%", $cperiod_start_date, $cperiod_finish_date, "0"));
        
        if ($results) {
            foreach ($results as $result) {
                $event = new self($result);
                $events[] = $event;
            }
        }
        
        return $events;
    }

     /* @return bool|Models_Curriculum_Period */
    public function getCurriculumPeriod() {
        global $db;

        $period = false;

        $query = "SELECT a.`cperiod_id`
                    FROM `course_audience` AS a
                    JOIN `curriculum_periods` AS b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    WHERE a.`course_id` = " . $db->qstr($this->course_id) . "
                    AND b.`start_date` <= " . $db->qstr($this->event_start) . "
                    AND b.`finish_date` >= " . $db->qstr($this->event_start);
        $result = $db->GetRow($query);
        if ($result) {
            $period = Models_Curriculum_Period::fetchRowByID($result["cperiod_id"]);
        }

        return $period;
    }

    /* @return ArrayObject|Models_Event_Audience[] */
    public function getEventAudience() {
        return Models_Event_Audience::fetchAllByEventID($this->event_id);
    }
    
    /* @return bool|Models_Course */
    public function getCourse() {
        if ($this->course === null) {
            return $this->course = Models_Course::fetchRowByID($this->course_id);
        } else {
            return $this->course;
        }
    }

    public static function fetchEventById($event_id) {
        global $db;

        $query		= "	SELECT a.*, b.`organisation_id`, IF(a.`room_id` IS NULL, a.`event_location`, CONCAT(d.`building_code`, '-', c.`room_number`)) AS `event_location`
						FROM `events` AS a
						LEFT JOIN `courses` AS b
						ON b.`course_id` = a.`course_id`
                        LEFT JOIN `global_lu_rooms` AS c
                        ON c.`room_id` = a.`room_id`
                        LEFT JOIN `global_lu_buildings` AS d
                        ON d.`building_id` = c.`building_id`
						WHERE a.`event_id` = ".$db->qstr($event_id);
        $event_info	= $db->GetRow($query);

        return $event_info;
    }
    
    
    public function getEventType () {
        return Models_EventType::get($this->eventtype_id);
    }
    
    /* @return ArrayObject|Models_Event[] */
    public static function fetchAllRecurringByEventID($event_id = null) {
        global $db;
        $recurring_events_query = "
                    SELECT * FROM `events`
                    WHERE `recurring_id` = (
                        SELECT `recurring_id`
                        FROM `events`
                        WHERE `event_id` = " . $db->qstr($event_id)."
                    )
                    AND `event_id` != " . $db->qstr($event_id);
        $all_recurring_events = $db->GetAll($recurring_events_query);
        if ($all_recurring_events) {
            foreach ($all_recurring_events as $result) {
                $event = new self($result);
                $events[] = $event;
            }
        }
        return $events;
    }

    public static function getRecurringEventIds($event_id = null) {
        $data = array("recurring_events" => false);

        if ($event_id != 0) {
            //get any recurring events as well
            $recurring_events = Models_Event::fetchAllRecurringByEventID($event_id);
            if (isset($recurring_events) && is_array($recurring_events) && !empty($recurring_events)) {
                $PROCESSED["recurring_events"] = $recurring_events;
            }

            if (isset($PROCESSED["recurring_events"])) {
                $R_Events = array();
                foreach ($PROCESSED["recurring_events"] as $events) {
                    if (isset($events) && is_object($events)) {
                        $R_Events[] = $events->getID();
                    }
                }
                $data["recurring_events"] = $PROCESSED["recurring_events"];
                $data["recurring_event_ids"] = $R_Events;
            }
            return $data;
        }
    }

    public static function migrateEventLocations($organisation_id, $room_id, $location) {
        global $db;

        $query = "UPDATE `events`
                  JOIN `courses`
                  ON `courses`.`course_id` = `events`.`course_id`
                  AND `courses`.`organisation_id` = " . $db->qstr($organisation_id) . "
                  SET `room_id` = " . $db->qstr($room_id) . "
                  WHERE `event_location` = " . $db->qstr($location) . " AND (`room_id` IS NULL OR `room_id` <= 0)";

        if ($db->Execute($query)) {
            return true;
        }
        return false;
    }

    public static function selectEventLocationsWithOutRoomID($organisation_id) {
        global $db;

        $query = "  SELECT DISTINCT `event_location`
                    FROM `events`
                    JOIN `courses`
                    ON `courses`.`course_id` = `events`.`course_id`
                    AND `courses`.`organisation_id` = " . $db->qstr($organisation_id) . "
                    WHERE (`room_id` IS NULL OR `room_id` <= 0) AND LENGTH(`event_location`) > 0
                    ORDER BY `event_location` ASC";
        if ($locations = $db->GetAll($query)) {
            if ($locations) {
                return $locations;
            }
        }
        return false;
    }


}