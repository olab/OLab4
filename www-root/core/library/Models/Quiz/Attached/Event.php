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
 * A model to handle quizzes attached to events
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Quiz_Attached_Event extends Models_Quiz_Attached {

    protected $event_id, $course_id, $event_title, $event_start, $event_duration, 
              $course_name, $course_code;
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchAllByQuizID($quiz_id) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*, b.`event_id`, b.`course_id`, b.`event_title`, b.`event_start`, b.`event_duration`, c.`course_name`, c.`course_code`
                    FROM `attached_quizzes` AS a
                    JOIN `events` AS b
                    ON a.`content_type` = 'event'
                    AND	b.`event_id` = a.`content_id`
                    JOIN `courses` AS c
                    ON c.`course_id` = b.`course_id`
                    WHERE a.`quiz_id` = ".$db->qstr($quiz_id)."
                    AND c.`course_active` = '1'
                    ORDER BY b.`event_start` DESC";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
    }
    
    public static function fetchRowByID($aquiz_id = null) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*, b.`event_id`, b.`course_id`, b.`event_title`, b.`event_start`, b.`event_duration`, c.`course_name`, c.`course_code`
                    FROM `attached_quizzes` AS a
                    JOIN `events` AS b
                    ON a.`content_type` = 'event'
                    AND	b.`event_id` = a.`content_id`
                    JOIN `courses` AS c
                    ON c.`course_id` = b.`course_id`
                    WHERE a.`aquiz_id` = ".$db->qstr($aquiz_id)."
                    AND c.`course_active` = '1'
                    ORDER BY b.`event_start` DESC";
        $result = $db->GetRow($query);
        if ($result) {
            $output = new self($result);
        }
        
        return $output;
    }
    
    public function getEventID() {
        return $this->event_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getEventTitle() {
        return $this->event_title;
    }

    public function getEventStart() {
        return $this->event_start;
    }

    public function getEventDuration() {
        return $this->event_duration;
    }

    public function getCourseName() {
        return $this->course_name;
    }

    public function getCourseCode() {
        return $this->course_code;
    }
    
}

?>
