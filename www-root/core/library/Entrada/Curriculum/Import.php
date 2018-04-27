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

class Entrada_Curriculum_Import {

	private $success, $objective_set_id, $objective_parent = 0, $organisation_id = 0, $updater, $valid_rows, $last_parent, $col_map, $validation_rules;

	function __construct($objective_set_id, $proxy_id, $req_map, $parent_tag = false) {
	    global $ENTRADA_USER;
		$this->objective_set_id = $objective_set_id;
		$this->updater = $proxy_id;
        $this->req_map = $req_map;
        $this->validation_rules = array(
            "objective_id" => array("int"),
            "objective_code" => array("trim", "striptags"),
            "objective_name" => array("trim", "striptags"),
            "objective_description" => array("trim", "striptags"),
            "objective_secondary_description" => array("trim", "striptags"),
            "objective_parent" => array("int"),
            "objective_set_id" => array("int"),
            "associated_objective" => array("int"),
            "objective_order" => array("int"),
            "objective_loggable" => array("int"),
            "objective_active" => array("int"),
            "non_examinable" => array("int"),
            "objective_status_id" => array("int"),
            "admin_notes" => array("trim", "striptags"),
            "objective_translation_status_id" => array("int"),
            "updated_date" => array("trim", "striptags"),
            "updated_by" => array("trim", "striptags")
        );

        if($parent_tag) {
            $this->objective_parent = $parent_tag;
        } else {
            if ($objective = Models_Objective::fetchRowBySetIDParentID($this->objective_set_id, 0)) {
                $this->objective_parent = $objective->getID();
            }
        }

        $this->organisation_id = $ENTRADA_USER->getActiveOrganisation();
	}

	/**
	 * Returns the successfully imported row numbers
	 * @return array
	 */
	public function getSuccess() {
		return $this->success;
	}

	private function validateRow($row = array(), $count = 0) {
		global $translate;

		if (!is_array($row)) {
			return false;
		}
        $mapped_cols = array();

        foreach ($row as $col => $value) {
            if (!empty($value)) {
                $mapped_cols[$col] = clean_input($row[$col], $this->validation_rules[$col]);
            } else {
                if (in_array($col, $this->req_map)) {
                    add_error(sprintf($translate->_("Row [%d] <strong>%s</strong> can not be empty."), $count+1, $col));
                } else {
                    $mapped_cols[$col] = "";
                }
            }
        }

        if (has_error()) {
            return false;
        }

        if (!isset($row["objective_order"])) {
            $mapped_cols["objective_order"] = $count;
        }

		return $mapped_cols;

	}

	private function importRow($row = array()) {
		global $translate;

        if (is_array($row)) {
            $objective_array = array(
                "objective_code" => $row["objective_code"],
                "objective_name" => $row["objective_name"],
                "objective_description" => $row["objective_description"],
                "objective_order" => (isset($row["objective_order"]) ? $row["objective_order"] : 0),
                "objective_loggable" => (isset($row["objective_loggable"]) ? $row["objective_loggable"] : 0),
                "non_examinable" => (isset($row["non_examinable"]) ? $row["non_examinable"] : 0),
                "objective_status_id" => (isset($row["objective_status_id"]) ? $row["objective_status_id"] : Entrada_Settings::read("curriculum_tags_default_status")),
                "admin_notes" => (isset($row["admin_notes"]) ? $row["admin_notes"] : ""),
                "objective_parent" => (int) $this->objective_parent,
                "objective_set_id" => (int) $this->objective_set_id,
                "updated_date" => time(),
                "updated_by" => (int) $this->updater
            );
            $existing_objective = Models_Objective::fetchRowBySetIDCodeName($this->objective_set_id, $row["objective_code"], $row["objective_name"], $this->organisation_id, $this->objective_parent);
            if ($existing_objective) {
                $existing_objective->fromArray($objective_array);
                if ($existing_objective->update()) {
                    $this->success["update"][] = $existing_objective;
                    return true;
                } else {
                    add_error($translate->_("There was a problem importing the data"));
                    return false;
                }
            } else {
                $objective = new Models_Objective();
                $objective->fromArray($objective_array);
                if ($objective) {
                    if ($objective->insert()) {
                        $objective_organisation = new Models_Objective_Organisation(array("objective_id" => $objective->getID(), "organisation_id" => $this->organisation_id));
                        if ($objective_organisation && $objective_organisation->insert()) {
                            $this->success["add"][] = $objective;
                            return true;
                        } else {
                            add_error($translate->_("There was a problem importing the data to the organisation table"));
                            return false;
                        }
                    } else {
                        add_error($translate->_("There was a problem importing the data"));
                        return false;
                    }
                } else {
                    add_error($translate->_("There was a problem importing the data"));
                    return false;
                }
            }
        }
	}

	public function importCsv($file) {
        global $translate;
        $handle = fopen($file, "r");

        if ($handle) {
            $row_count = 0;
            $headings = array();
            $this->success = array();
            while (($row = fgetcsv($handle)) !== false) {
                if ($row_count >= 1) {
                    $rows[] = $row;
                } else {
                    $headings = $row;
                }
                $row_count++;
            }

            foreach ($this->req_map as $column) {
                if (!in_array($column, $headings)) {
                    add_error(sprintf($translate->_("<strong>%s</strong> is a required column."), $column));
                }
            }

            if (!empty($rows)) {
                foreach ($rows as $i => $row) {
                    $tmp_row = array();
                    foreach ($headings as $key => $field) {
                        $tmp_row[$i][$field] = $row[$key];
                    }
                    if ($results = $this->validateRow($tmp_row[$i], $i)) {
                        $this->valid_rows[] = $results;
                    }
                }
            } else {
                add_error($translate->_("The file has <strong>no data</strong>."));
            }

            if (!has_error()) {
                foreach ($this->valid_rows as $valid_row) {
                   if (!$this->importRow($valid_row)) {
                       return false;
                   }

                }
            } else {
                return false;
            }

        }
        fclose($handle);

		return $row_count;
	}
}
