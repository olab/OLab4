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
 * Class to do some things with a CSV.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@quensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

ini_set('auto_detect_line_endings', true);

class Entrada_Event_Draft_CsvImporter {

	private $errors, $success, $draft_id, $updater, $valid_rows, $last_parent, $col_map, $validation_rules;

	function __construct($draft_id, $proxy_id, $col_map) {
		$this->draft_id = $draft_id;
		$this->updater = $proxy_id;
        $this->col_map = $col_map;
        $this->validation_rules = array(
            "original_event"            => array("int"),
            "parent_event"              => array("int"),
            "recurring_event"           => array("int"),
            "course_code"               => array("trim", "striptags"),
            "course_name"               => array("trim", "striptags"),
            "term"                      => array("trim", "striptags"),
            "date"                      => array("trim", "striptags"),
            "start_time"                => array("trim", "striptags"),
            "total_duration"            => array("int"),
            "event_type_durations"      => array("nows", "striptags"),
            "event_types"               => array("trim", "striptags"),
            "event_title"               => array("trim", "striptags"),
            "event_description"         => array("trim", "striptags"),
            "location"                  => array("trim", "striptags"),
            "location_room"             => array("trim", "striptags"),
            "audience_groups"           => array("trim", "striptags"),
            "audience_cohorts"          => array("trim", "striptags"),
            "audience_students"         => array("trim", "striptags"),
            "teacher_names"             => array("trim", "striptags"),
            "teacher_numbers"           => array("trim", "striptags"),
            "objectives_release_date"   => array("trim", "striptags"),
            "event_tutors"              => array("trim", "striptags")
        );
        $this->delimited_fields = array(
            "event_type_durations", "event_types", "audience_groups", 
            "audience_cohorts", "audience_students", "teacher_numbers",
            "teacher_names", "event_tutors"
        );
	}

	/**
	 * Returns the errors
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns the successfully imported row numbers
	 * @return array
	 */
	public function getSuccess() {
		return $this->success;
	}

	private function validateRow($row = array()) {
		global $db, $ENTRADA_ACL, $ENTRADA_USER;

		if (!is_array($row)) {
			return false;
		}

		$output = array();
		$skip_row = false;
        $mapped_cols = array();
        
        foreach ($this->col_map as $col => $field_name) {
            $mapped_cols[$field_name] = clean_input($row[$col], $this->validation_rules[$field_name]);
            if (in_array($field_name, $this->delimited_fields) && !empty($mapped_cols[$field_name])) {
                $mapped_cols[$field_name] = explode(";", $mapped_cols[$field_name]);
                if (!empty($mapped_cols[$field_name])) {
                    foreach ($mapped_cols[$field_name] as $entry => $value) {
                        $mapped_cols[$field_name][$entry] = clean_input($value, $this->validation_rules[$field_name]);
                    }
                }
            }
        }
        
		$event_duration			= 0;
		
		// check draft for existing event_id and get the devent_id if found
		if ($mapped_cols["original_event"] != 0) {

			$query = "	SELECT `devent_id`
						FROM `draft_events`
						WHERE `event_id` = ".$db->qstr($mapped_cols["original_event"])."
						AND `draft_id` = ".$db->qstr($this->draft_id);
			if ($result = $db->GetRow($query)) {
				$output[$mapped_cols["original_event"]]["devent_id"] = $result["devent_id"];
			}
			
			$query = "	SELECT * FROM `events` WHERE `event_id` = ".$db->qstr($mapped_cols["original_event"]);
			$old_event_data = $db->GetRow($query);
		}

		// set the output event_id
		$output[$mapped_cols["original_event"]]["event_id"] = $mapped_cols["original_event"];

		// check the parent_id column
		if (!is_null($mapped_cols["parent_event"])) {
			if ($mapped_cols["parent_event"] === 1) {
				$output[$mapped_cols["original_event"]]["parent_event"] = 0;
				$this->last_parent = $mapped_cols["original_event"];
			} else if ($mapped_cols["parent_event"] === 0) {
				$output[$mapped_cols["original_event"]]["parent_event"] = $this->last_parent;
			}
		} else {
			$err["errors"][] = "Parent ID field must be 1 or 0.";
			$skip_row = true;
		}

		// term - not required
		if ($mapped_cols["term"] != 0) {
			$output[$mapped_cols["original_event"]]["term"] = $mapped_cols["original_event"];
		}

        $course_id = 0;
        $organisation_id = $ENTRADA_USER->getActiveOrganisation();
        $course_permission = false;

		// verify the course code
		$query = "	SELECT `course_id`, `organisation_id`, `permission`
					FROM `courses`
					WHERE `course_code` = ".$db->qstr($mapped_cols["course_code"])."
                    AND `course_active` = '1'";
        $result = $db->getRow($query);
		if ($result) {
            if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $result["course_id"], $ENTRADA_USER->getActiveOrganisation()), "create")) {
                $output[$mapped_cols["original_event"]]["course_id"] = $result["course_id"];

                $course_id = $result["course_id"];
                $organisation_id = $result["organisation_id"];
                $course_permission = $result["permission"];
            } else {
                $err["errors"][] = "You do not have the permissions required to create events in ".html_encode($mapped_cols["course_code"]).".";
                $skip_row = true;
            }
		} else {
            $err["errors"][] = "We were unable to locate a course with a code of [".html_encode($mapped_cols["course_code"])."].";
            $skip_row = true;
		}

		// validate required date and time
        $event_start = strtotime($mapped_cols["date"]." ".$mapped_cols["start_time"]);
		if ($event_start) {
			$output[$mapped_cols["original_event"]]["event_start"] = $event_start;
		} else {
			$err["errors"][] = "The start date [".html_encode($mapped_cols["date"])."] and time [".html_encode($mapped_cols["start_time"])."] of this event could not be validated.";
			$skip_row = true;
		}

		// number of eventtype durations must match number of eventtypes
		if (count($mapped_cols["event_type_durations"]) == count($mapped_cols["event_types"])) {
			$i = 0;
			foreach ($mapped_cols["event_type_durations"] as $duration) {
				$query = "	SELECT a.`eventtype_id`
							FROM `events_lu_eventtypes` AS a
                            JOIN `eventtype_organisation` AS b
                            ON a.`eventtype_id` = b.`eventtype_id`
                            AND b.`organisation_id` = ".$db->qstr($organisation_id)."
							WHERE LCASE(`eventtype_title`) = ".$db->qstr(strtolower(clean_input($mapped_cols["event_types"][$i], array("striptags", "trim"))));
				$results = $db->GetRow($query);
				if ($results) {
					$output[$mapped_cols["original_event"]]["eventtypes"][$i]["type"] = $results["eventtype_id"];
					$output[$mapped_cols["original_event"]]["eventtypes"][$i]["duration"] = $duration;
					$output[$mapped_cols["original_event"]]["total_duration"] += $duration;
				} else {
					$err["errors"][] = "We were unable to find a learning event type of [".$mapped_cols["event_types"][$i]."].";
					$skip_row = true;
				}
				$i++;
			}
		} else {
			$err["errors"][] = "The number of event types [".html_encode($row[8])."] provided does not match the number of durations [".html_encode($row[9])."] provided.";
			$skip_row = true;
		}

		// required event title
		if (!empty($mapped_cols["event_title"])) {
			$output[$mapped_cols["original_event"]]["event_title"] = $mapped_cols["event_title"];
		} else {
			$err["errors"][] = "The event title was not set for this event.";
			$skip_row = true;
		}
		// recurring event id, not required
		if (!is_null($mapped_cols["recurring_event"])) {
			$output[$mapped_cols["original_event"]]["recurring_event"] = $mapped_cols["recurring_event"];
		} else {
			if ($old_event_data) {
				$output[$mapped_cols["original_event"]]["recurring_event"] = $old_event_data["recurring_id"];
			}
		}

		// event description, not required
		if (strlen($mapped_cols["event_description"]) > 0) {
			$output[$mapped_cols["original_event"]]["event_description"] = $mapped_cols["event_description"];
		} else {
			if ($old_event_data) {
				$output[$mapped_cols["original_event"]]["event_description"] = $old_event_data["event_description"];
			}
		}

		// event location, not required
		if (strlen($mapped_cols["location"]) > 0) {
			$output[$mapped_cols["original_event"]]["event_location"] = $mapped_cols["location"];
		} else {
			if ($old_event_data) {
				$output[$mapped_cols["original_event"]]["event_location"] = $old_event_data["event_location"];
			}
		}

        // event location room_id, not required
        if (strlen($mapped_cols["location_room"]) > 0) {
		    $room_str = explode("-",$mapped_cols["location_room"]);
		    $building = Models_Location_Building::fetchRowByCode($room_str[0], $organisation_id);
		    $room = Models_Location_Room::fetchRowByNumber($room_str[1], $building->getBuildingID());
            $output[$mapped_cols["original_event"]]["room_id"] = (int) $room->getRoomId();
        } else {
            if ($old_event_data) {
                $output[$mapped_cols["original_event"]]["room_id"] = $old_event_data["room_id"];
            }
        }

		// event audience, not required	but needs to be verified
		if (!empty($mapped_cols["audience_cohorts"])) {
			foreach ($mapped_cols["audience_cohorts"] as $i => $cohort) {
				if (!empty($cohort)) {
					$mapped_cols["audience_cohorts"][$i] = $db->qstr(strtolower(clean_input($cohort, array("trim", "striptags"))));
				}
			}
			$query = "	SELECT a.`group_id`, a.`group_name`
						FROM `groups` AS a
                        JOIN `group_organisations` AS b
                        ON b.`group_id` = a.`group_id`
                        AND b.`organisation_id` = ".$db->qstr($organisation_id)."
						WHERE LCASE(a.`group_name`) IN (".implode(", ", $mapped_cols["audience_cohorts"]).")
						GROUP BY a.`group_name`";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$mapped_cols["original_event"]]["audiences"]["cohorts"][] = $result["group_id"];
				}
			}
		}

		if (!empty($mapped_cols["audience_groups"])) {
			foreach ($mapped_cols["audience_groups"] as $i => $group) {
				if (!empty($group)) {
					$mapped_cols["audience_groups"][$i] = $db->qstr(strtolower(clean_input($group, array("trim", "striptags"))));
				}
			}

			$query = "	SELECT `cgroup_id`, `course_id`, `group_name`
						FROM `course_groups`
						WHERE LCASE(`group_name`) IN (".implode(", ", $mapped_cols["audience_groups"]).")
                        AND `course_id` = ".$db->qstr($course_id)."
						GROUP BY `group_name`";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$mapped_cols["original_event"]]["audiences"]["groups"][] = $result["cgroup_id"];
				}
			} else {
				$err["errors"][] = "We were unable to find the provided event audience groups [".implode(",", $mapped_cols["audience_groups"])."].";
				$skip_row = true;
			}
		}

		if (!empty($mapped_cols["audience_students"])) {
			foreach ($mapped_cols["audience_students"] as $i => $student) {
				if (!empty($student)) {
					$mapped_cols["audience_students"][$i] = $db->qstr((int) $student);
				}
			}
			$query = "	SELECT `id`
						FROM `".AUTH_DATABASE."`.`user_data`
						WHERE `number` IN (".implode(", ", $mapped_cols["audience_students"]).")";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$mapped_cols["original_event"]]["audiences"]["students"][] = $result["id"];
				}
			}
		}

		if (!empty($mapped_cols["teacher_numbers"])) {
            $e_teachers = array();
			foreach ($mapped_cols["teacher_numbers"] as $teacher) {
				if (!empty($teacher) && $teacher != "0") {
					$e_teachers[$teacher] = $db->qstr((int) $teacher);
				}
			}
			$query = "	SELECT `id`
						FROM `".AUTH_DATABASE."`.`user_data`
						WHERE `number` IN (".implode(", ", $e_teachers).")";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$mapped_cols["original_event"]]["teachers"][] = $result["id"];
				}
			}
		}

		if (!empty($mapped_cols["event_tutors"])) {
            $e_tutors = array();
			foreach ($mapped_cols["event_tutors"] as $teacher) {
				if (!empty($teacher) && $teacher != "0") {
					$e_tutors[$teacher] = $db->qstr((int) $teacher);
				}
			}
			$query = "	SELECT `id`
						FROM `".AUTH_DATABASE."`.`user_data`
						WHERE `number` IN (".implode(", ", $e_tutors).")";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$mapped_cols["original_event"]]["tutors"][] = $result["id"];
				}
			}
		}
		
		if (!$skip_row) {
			return $output;
		} else {
			return $err;
		}

	}

	private function importRow($valid_row = array()) {
		global $db;

        if (is_array($valid_row)) {
            foreach ($valid_row as $row) {

                if (isset($row["devent_id"])) {
                    $update = true;
                    $where = " WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
                    $query = "UPDATE `draft_events`
                                SET `parent_id` = ".$db->qstr($row["parent_event"]).",
                                    `recurring_id` = ".$db->qstr($row["recurring_event"]).",
                                    `course_id` = ".$db->qstr($row["course_id"]).",
                                    `event_title` = ".$db->qstr($row["event_title"]).",
                                    `event_description` = ".$db->qstr($row["event_description"]).",
                                    `event_start` = ".$db->qstr($row["event_start"]).",
                                    `event_finish` = ".$db->qstr(($row["event_start"] + ($row["total_duration"] * 60))).",
                                    `event_duration` = ".$db->qstr($row["total_duration"]).",
                                    `event_location` = ".$db->qstr($row["event_location"]).",
                                    `room_id` = ".$db->qstr($row["room_id"])."
                                    WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
                } else {
                    $update = false;
                    $query = "INSERT INTO `draft_events` (`draft_id`, `event_id`, `parent_id`, `recurring_id`, `course_id`, `event_title`, `event_description`, `event_start`, `event_finish`, `event_duration`, `event_location`, `room_id`)
                                VALUES (".$this->draft_id.", ".$db->qstr($row["event_id"]).", ".$db->qstr($row["parent_event"]).", ".$db->qstr($row["recurring_event"]).", ".$db->qstr($row["course_id"]).", ".$db->qstr($row["event_title"]).", ".$db->qstr($row["event_description"]).", ".$db->qstr($row["event_start"]).", ".$db->qstr($row["event_start"] + ($row["total_duration"] * 60)).", ".$db->qstr($row["total_duration"]).", ".$db->qstr($row["event_location"]).", ".$db->qstr($row["room_id"]).")";
                }

                $result = $db->Execute($query);

                $devent_id = (isset($row["devent_id"]) ? $row["devent_id"] : $db->Insert_ID()."\n");

                if ($update) {
                    $query = "DELETE FROM `draft_eventtypes`
                                WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
                    if (!$db->Execute($query)) {
                        application_log("error", "Unable to remove existing `draft_eventtypes` records when importing a csv into an events draft. DB Said: ".$db->ErrorMsg());
                    }
                }
                foreach ($row["eventtypes"] as $eventtype) {
                    $query =	"INSERT INTO `draft_eventtypes` (`devent_id`, `event_id`, `eventtype_id`, `duration`)
                                VALUES (".$db->qstr($devent_id).", ".$db->qstr($row["event_id"]).", ".$db->qstr($eventtype["type"]).", ".$db->qstr($eventtype["duration"]).")";
                    $result = $db->Execute($query);
                }

                if ($update) {
                    $query = "DELETE FROM `draft_audience`
                                    WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
                    if (!$db->Execute($query)) {
                        application_log("error", "Unable to remove existing `draft_audience` records when importing a csv into an events draft. DB Said: ".$db->ErrorMsg());
                    }
                }

                if (isset($row["audiences"]["cohorts"])) {
                    foreach ($row["audiences"]["cohorts"] as $cohort) {
                        $query =	"INSERT INTO `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", 'cohort', ".$db->qstr($cohort).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                    }
                }
                if (isset($row["audiences"]["groups"])) {
                    foreach ($row["audiences"]["groups"] as $group) {
                        $query =	"INSERT INTO `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", 'group_id', ".$db->qstr($group).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                    }
                }
                if (isset($row["audiences"]["students"])) {
                    foreach ($row["audiences"]["students"] as $student) {
                        $query =	"INSERT INTO `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", 'proxy_id', ".$db->qstr($student).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                    }
                }

                // If there is no custom audience set above, set the audience to the course_id.
                if ($row["course_id"] && (!isset($row["audiences"]["cohorts"]) || empty($row["audiences"]["cohorts"])) && (!isset($row["audiences"]["groups"]) || empty($row["audiences"]["groups"])) && (!isset($row["audiences"]["students"]) || empty($row["audiences"]["students"]))) {
                        $query =	"INSERT INTO `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", 'course_id', ".$db->qstr($row["course_id"]).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                }

                if ($update) {
                    $query = "DELETE FROM `draft_contacts`
                                        WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
                    if (!$db->Execute($query)) {
                        application_log("error", "Unable to remove existing `draft_contacts` records when importing a csv into an events draft. DB Said: ".$db->ErrorMsg());
                    }
                }
                if (isset($row["teachers"])) {
                    $i = 0;
                    foreach ($row["teachers"] as $teacher) {
                        $query =	"INSERT INTO `draft_contacts` (`devent_id`, `proxy_id`, `contact_role`, `contact_order`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", ".$db->qstr($teacher).", 'teacher', ".$db->qstr($i).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                        $i++;
                    }
                }
				
				if (isset($row["tutors"])) {
                    $i = 0;
                    foreach ($row["tutors"] as $tutor) {
                        $query =	"INSERT INTO `draft_contacts` (`devent_id`, `proxy_id`, `contact_role`, `contact_order`, `updated_date`, `updated_by`)
                                    VALUES (".$db->qstr($devent_id).", ".$db->qstr($tutor).", 'tutor', ".$db->qstr($i).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")";
                        $result = $db->Execute($query);
                        $i++;
                    }
                }
            }
        }
	}

	public function importCsv($file) {
        if(!DEMO_MODE) {
			$handle = fopen($file, "r");
			if ($handle) {
                $row_count = 0;
				while (($row = fgetcsv($handle)) !== false) {
					if ($row_count >= 1) {
						$results = $this->validateRow($row);
						if (isset($results["errors"])) {
							$this->errors[$row_count + 1] = $results["errors"];
						} else {
							$this->valid_rows[] = $results;
						}
					}
					$row_count++;
				}
				if (count($this->errors) <= 0) {
					foreach ($this->valid_rows as $valid_row) {
						$this->importRow($valid_row);
						$this->success[] = $row_count + 1;
					}
				}
			}
			fclose($handle);
		} else {
			$handle = fopen(DEMO_SCHEDULE, "r");
			if ($handle) {
				$row_count = 0;
				while (($row = fgetcsv($handle)) !== false) {
					if ($row_count >= 1) {
						$results = $this->validateRow($row);
						if (isset($results["errors"])) {
							$this->errors[$row_count + 1] = $results["errors"];
						} else {
							$this->valid_rows[] = $results;
						}
					}
					$row_count++;
				}
				if (count($this->errors) <= 0) {
					foreach ($this->valid_rows as $valid_row) {
						$this->importRow($valid_row);
						$this->success[] = $row_count + 1;
					}
				}
			}
			fclose($handle);
		}

		return $row_count;
	}
}
