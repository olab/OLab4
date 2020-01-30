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
 * A model to handle interaction with the draft events.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Event_Draft_Event extends Models_Base {
    
    protected $devent_id,
              $event_id,
              $draft_id,
              $parent_id,
              $draft_parent_id,
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
              $updated_date,
              $updated_by;

    protected static $table_name = "draft_events";
    protected static $primary_key = "devent_id";
    protected static $default_sort_column = "event_start";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->devent_id;
    }
    
    public function getDeventID() {
        return $this->devent_id;
    }

    public function getEventID() {
        return $this->event_id;
    }

    public function getDraftID() {
        return $this->draft_id;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function getDraftParentID() {
        return $this->draft_parent_id;
    }

    public function getEventChildren() {
        return $this->event_children;
    }

    public function getRecurringID() {
        return $this->recurring_id;
    }

    public function getRegionID() {
        return $this->region_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getEventPhase() {
        return $this->event_phase;
    }

    public function getEventTitle() {
        return $this->event_title;
    }

    public function getEventDescription() {
        return $this->event_description;
    }

    public function getIncludeParentDescription() {
        return $this->include_parent_description;
    }

    public function getEventGoals() {
        return $this->event_goals;
    }

    public function getEventObjectives() {
        return $this->event_objectives;
    }

    public function getKeywordsHidden() {
        return $this->keywords_hidden;
    }
    
    public function getKeywordsReleaseDate() {
        return $this->keywords_release_date;
    }

    public function getObjectivesReleaseDate() {
        return $this->objectives_release_date;
    }

    public function getEventMessage() {
        return $this->event_message;
    }

    public function getIncludeParentMessage() {
        return $this->include_parent_message;
    }

    public function getEventLocation() {
        return $this->event_location;
    }

    public function getRoomId() {
        return $this->room_id;
    }

    public function getEventStart() {
        return $this->event_start;
    }

    public function getEventFinish() {
        return $this->event_finish;
    }

    public function getEventDuration() {
        return $this->event_duration;
    }

    public function getAudienceVisible() {
        return $this->audience_visible;
    }

    public function getAttendanceRequired() {
        return $this->attendance_required;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getColor() {
        return $this->event_color;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public static function fetchAllByDraftID($draft_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "draft_id", "value" => $draft_id, "method" => "=")
        ));
    }
    
    public static function fetchAllByDraftIDByDate($draft_id, $start = 0, $finish = 0) {
        global $db;
        
        $output = [];
        $start = (int) $start;
        $finish = (int) $finish;
        
        $query = "SELECT *
                  FROM `draft_events`
                  WHERE `draft_id` = ?
                  " . ($start ? " AND `event_start` >= " . $db->qstr($start) : "") . "
                  " . ($finish ? " AND `event_finish` <= " . $db->qstr($finish) : "") . "
                  ORDER BY `event_start`";
        $results = $db->GetAll($query, array($draft_id));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
    }

    public function fetchAllByCourseIdTitleDraftId($draft_id, $course_id = 0, $title = "") {
        global $db;

        $course_id = (int) $course_id;
        $title = clean_input($title, ["striptags", "trim"]);

        $events = [];

        if ($course_id) {
            $query = "SELECT *
                        FROM `draft_events` 
                        WHERE `course_id` = ?
                        AND `draft_id` = ?
                        AND (`devent_id` = ? OR `event_title` LIKE ?)
                        AND (`draft_parent_id` = 0 OR `draft_parent_id` IS NULL)
                        ORDER BY `event_start` ASC";
            $results = $db->GetAll($query, [$course_id, $draft_id, (int) $title, ("%" . $title . "%")]);
            if ($results) {
                foreach ($results as $result) {
                    $event = new self($result);
                    $events[] = $event;
                }
            }
        }

        return $events;
    }
    
    public static function fetchRowByID($devent_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "devent_id", "value" => $devent_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchRowByIDCourseId($devent_id = 0, $course_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "devent_id", "method" => "=", "value" => $devent_id),
                array("key" => "course_id", "method" => "=", "value" => $course_id)
            )
        );
    }

    public static function fetchRowByEventIdDraftId($event_id = 0, $draft_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "event_id", "method" => "=", "value" => $event_id),
                array("key" => "draft_id", "method" => "=", "value" => $draft_id)
            )
        );
    }

    public static function fetchAllByParentID($parent_id = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key"=> "draft_parent_id", "method"=> "=", "value" => $parent_id)
        ));
    }
}