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
 * A model for a student syllabus.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

require_once("library/Classes/utility/SimpleCache.class.php");
require_once("library/Classes/courses/Course.class.php");

class Models_Syllabus {
	private $syllabus_id,
			$syllabus_start,
			$syllabus_finish,
			$template,
			$course_id,
			$course_name,
			$course_code,
			$active = 0;
	
	public function __construct($arr = NULL) {
		if (is_array($arr)) {
			$this->fromArray($arr);
		}
	}
	
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				$arr[$class_var] = $this->$class_var;
			}
		}
		return $arr;
	}
	
	public function fromArray($arr) {
		foreach ($arr as $class_var_name => $value) {
			$this->$class_var_name = $value;
		}
		return $this;
	}
	
    public static function fetchAll($start = NULL, $finish = NULL, $active = NULL) {
		global $db;
		
		$syllabi = false;
		
		if (!is_null($active)) {
			$where[] = "`active` = ".$db->qstr($active);
		}
		
		$query = "	SELECT a.*, b.`course_name`, b.`course_code`, c.`start_date` AS `syllabus_start`, c.`finish_date` AS `syllabus_finish`
					FROM `course_syllabi` AS a
					JOIN `courses` AS b
					ON a.`course_id` = b.`course_id`
					JOIN `curriculum_periods` AS c
					ON b.`curriculum_type_id` = c.`curriculum_type_id`
					AND c.`active` = 1
					WHERE UNIX_TIMESTAMP(NOW()) BETWEEN c.`start_date` AND c.`finish_date` AND a.`active` = '1'
					GROUP BY a.`syllabus_id`";
		$syllabi_data = $db->GetAll($query);
		if ($syllabi_data) {
			foreach ($syllabi_data as $syllabus_data) {
				$syllabi[] = new self($syllabus_data);
			}
		}
		
		return $syllabi;
    }

    public static function fetchRow($syllabus_id, $active = NULL) {
        global $db;
		
		$syllabus = false;
		
		$query = "	SELECT a.*, b.`course_name`, b.`course_code`
					FROM `course_syllabi` AS a
					JOIN `courses` AS b
					ON a.`course_id` = b.`course_id`
					WHERE `syllabus_id` = ?".
					(!is_null($active) ? "AND `active` = ?" : "");
		$syllabus_data = $db->GetRow($query, array($syllabus_id, $active));
		if ($syllabus_data) {
			$syllabus = new self($syllabus_data);
		}
		
		return $syllabus;
    }

	public static function fetchRowByCourseID($course_id, $active = NULL) {
		global $db;

		$syllabus = new self();

		$query = "	SELECT a.*, b.`course_name`, b.`course_code`, c.`start_date` AS `syllabus_start`, c.`finish_date` AS `syllabus_finish`
					FROM `course_syllabi` AS a
					JOIN `courses` AS b
					ON a.`course_id` = b.`course_id`
					JOIN `curriculum_periods` AS c
					ON b.`curriculum_type_id` = c.`curriculum_type_id`
					AND c.`active` = 1
					WHERE a.`course_id` = ? 
					AND UNIX_TIMESTAMP(NOW()) BETWEEN c.`start_date` AND c.`finish_date` ".
					(!is_null($active) ? "AND a.`active` = ?" : "")."
                    ORDER BY c.`start_date` DESC";

		$constraints = array($course_id);

		if (!is_null($active)) {
			$constraints[] = $active;
		}

		$course_details = $db->GetRow($query, $constraints);

		if ($course_details) {
			$syllabus->fromArray($course_details);
		}

		return $syllabus;
	}

    public static function fetchSyllibiRowByCourseIDActive($course_id, $active = 1) {
        global $db;

        $syllabus = new self();

        $query = "	SELECT a.*, b.`course_name`, b.`course_code`, c.`start_date` AS `syllabus_start`, c.`finish_date` AS `syllabus_finish`
					FROM `course_syllabi` AS a
					JOIN `courses` AS b
					ON a.`course_id` = b.`course_id`
					JOIN `curriculum_periods` AS c
					ON b.`curriculum_type_id` = c.`curriculum_type_id`
					AND c.`active` = 1
					WHERE a.`course_id` = ?
					AND a.`active` = ?
					ORDER BY c.`start_date`, a.`syllabus_id` DESC";
        $course_details = $db->GetRow($query, array($course_id, $active));
        if ($course_details) {
            $syllabus->fromArray($course_details);
        }

        return $syllabus;
    }
	
	public function insert() {
		global $db;
		
		return false;
    }

    public function update() {
		global $db;
		
		return false;
    }

    public function delete() {
        return false;
    }
	
	/*
	 * Setters and Getters
	 */
	
	public function getID() {
		return $this->syllabus_id;
	}
	
	public function getTemplate() {
		return $this->template;
	}
	
	public function setTemplate($template) {
		$this->template = $template;
		return $this;
	}
	
	public function getCourseCode() {
		return $this->course_code;
	}
	
	public function getCourseName() {
		return $this->course_name;
	}
	
	public function getCourse($course_id = NULL) {
		if (is_null($course_id)) {
			if (isset($this->course_id)) {
				$course_id = $this->course_id;
			} else {
				return false;
			}
		}
		return Course::get($course_id);
	}
	
	public function getActive() {
		return $this->active;
	}
	
	public function getStart() {
		return $this->syllabus_start;
	}
	
	public function getFinish() {
		return $this->syllabus_finish;
	}
	
}
