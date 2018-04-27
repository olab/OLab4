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

class CsvImporter {

	private $errors, $success, $empty, $category, $role, $group, $organisation, $updater, $valid_rows, $user_fields, $col_map, $validation_rules, $columns, $delete, $replace, $deleted;

	function __construct($cat_id, $role, $group, $organisation_id, $proxy_id, $col_map, $delete, $replace) {
		$this->category = $cat_id;
		$this->role = $role;
		$this->group = $group;
		$this->organisation = $organisation_id;
		$this->updater = $proxy_id;
		$this->col_map = $col_map;
		$this->validation_rules = array(
			"proxy_id"                  => array("int"),
			"number"                    => array("int"),
			"role"                      => array("trim", "striptags"),
			"group"                     => array("trim", "striptags"),
			"first_name"                => array("trim", "striptags"),
			"last_name"                 => array("trim", "striptags"),
			"username"                  => array("trim", "striptags"),
			"type"                      => array("trim", "striptags"),
			"value"                     => array("trim", "striptags"),
			"notes"                     => array("trim", "striptags"),
			"effective_date"            => array("trim", "striptags"),
			"expiry_date"               => array("trim", "striptags")
		);
		$this->delimited_fields = array(
			"role", "group", "first_name", "last_name", "username",
			"type", "value", "notes"
		);
		$this->delete = $delete;
		$this->replace = $replace;
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

	/**
	 * Returns the row numbers of none imported or empty records
	 * @return array
	 */
	public function getEmpty() {
		return $this->empty;
	}

	/**
	 * Returns number records that are deleted
	 * @return array
	 */
	public function getDeleted() {
		return $this->deleted;
	}

	private function badUser($record) {
		$line = ":";
		foreach ($this->user_fields as $field) {
			$line .= " ${field}[$record[$field]]";
		}
		return $line;
	}

	private function userSearch($record) {
		global $db;
		$search = $name = "";
		$identify = false;
		$this->user_fields = array();

		if (in_array("proxy_id",$this->columns) && $record["proxy_id"]) {
			$search .= " AND a.`id` LIKE ".$db->qstr($record["proxy_id"]);
			$identify = true;
			$this->user_fields[] = "proxy_id";
		}
		if (in_array("number",$this->columns) && $record["number"]) {
			$search .= " AND a.`number` LIKE ".$db->qstr($record["number"]);
			$identify = true;
			$this->user_fields[] = "number";
		}

		if (in_array("first_name",$this->columns) && strlen($record["first_name"])) {
			$name .= " AND a.`firstname` LIKE ".$db->qstr("%%".str_replace("%", "", $record["first_name"])."%%");
			$this->user_fields[] = "first_name";
		}

		if (in_array("last_name",$this->columns) && strlen($record["last_name"])) {
			if ($identify || strlen($name)) {
				$search .= $name . " AND a.`lastname` LIKE " . $db->qstr("%%" . str_replace("%", "", $record["last_name"]) . "%%");
				$identify = true;
			}
			$this->user_fields[] = "last_name";
		} else {
			$search .= $name;
		}

		if (in_array("role",$this->columns)) {
			$search .= " AND b.`role` LIKE ".$db->qstr(str_replace("%", "", $record["role"]));
		}
		if (in_array("group",$this->columns)) {
			$search .= " AND b.`group` LIKE ".$db->qstr(str_replace("%", "", $record["group"]));
		}

		if (strlen($search) && $identify) {
			$query	= "	SELECT a.`id`
				FROM `".AUTH_DATABASE."`.`user_data` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
				ON b.`user_id` = a.`id`
				AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				AND b.`account_active` = 'true'
				AND b.`access_starts` < ".$db->qstr(time())."
				AND (b.`access_expires` > ".$db->qstr(time())." OR b.`access_expires` = 0) $search";
			return $db->GetOne($query);
		} else {
			return 0;
		}
	}
	private function typeCheck($type_label) {
		$types = MetaDataTypes::getSelectionByParent($this->category);
		if (!empty($types)) {
			foreach ($types AS $type) {
				if (!strcasecmp($type["label"],$type_label)) {
					return $type["meta_type_id"];
				}
			}
		} else {
			return $this->category;

		}
		return false;
	}

	private function validateRow($row = array()) {
		global $translate;

		if (!is_array($row)) {
			return false;
		}

		$output = array();
		$skip_row = false;
		$mapped_cols = array();
		$entries = 0;

		foreach ($this->col_map as $col => $field_name) {

			$mapped_cols[$field_name] = clean_input($row[$col], $this->validation_rules[$field_name]);
			if (in_array($field_name, $this->delimited_fields) && !empty($mapped_cols[$field_name])) {
				$mapped_cols[$field_name] = explode(";", $mapped_cols[$field_name]);
				if (!empty($mapped_cols[$field_name])) {
					foreach ($mapped_cols[$field_name] as $entry => $value) {
					    $mapped_cols[$field_name][$entry] = clean_input($value, $this->validation_rules[$field_name]);
					}
					$mapped_cols[$field_name] = implode($mapped_cols[$field_name]);
				}
			}
		}

		/**
		 * Find user
		 */

		if ($id = $this->userSearch($mapped_cols)) {
			$output["proxy_id"] = $id;
		} else {
			$err["errors"][] = count($this->user_fields)?$translate->_("metadata_error_user") . $this->badUser($mapped_cols):$translate->_("metadata_error_user_search");
			$skip_row = true;
		}

		// check type and id values

		if (isset($mapped_cols["role"]) &&  strlen($mapped_cols["role"])) {
			$output["role"] = $mapped_cols["role"];
		}

		if (isset($mapped_cols["group"]) && is_string($mapped_cols["group"]) &&  strlen($mapped_cols["group"])) {
			$output["group"] = $mapped_cols["group"];
		}

		if ($type = $this->typeCheck($mapped_cols["type"])) {
			$output["update"]["type"] = $type;
		} else {
			$err["errors"][] = count($this->user_fields)?$translate->_("metadata_error_user_type") . $this->badUser($mapped_cols):$translate->_("metadata_error_user_search");
			$skip_row = true;
		}

		if (isset($mapped_cols["number"]) &&  strlen($mapped_cols["number"])) {
			$output["number"] = $mapped_cols["number"];
		}

		if (isset($mapped_cols["value"]) &&  strlen($mapped_cols["value"])) {
			$output["update"]["value"] = filter_var($mapped_cols["value"],FILTER_UNSAFE_RAW);
			$entries++;
		} else {
			$output["update"]["value"] = null;
		}

		if (isset($mapped_cols["notes"]) && strlen($mapped_cols["notes"])) {
			$output["update"]["notes"] = filter_var($mapped_cols["notes"],FILTER_UNSAFE_RAW);
			$entries++;
		} else {
			$output["update"]["notes"] = null;
		}

		if (isset($mapped_cols["effective_date"]) && strlen($mapped_cols["effective_date"])) {
			$output["update"]["effective_date"] = fmt_date(filter_var($mapped_cols["effective_date"],FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/\d{4}-\d{1,2}-\d{1,2}/"))));
			$entries++;
		} else {
			$output["update"]["effective_date"] = null;
		}

		if (isset($mapped_cols["expiry_date"]) && strlen($mapped_cols["expiry_date"])) {
			$output["update"]["expiry_date"] = fmt_date(filter_var($mapped_cols["expiry_date"],FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/\d{4}-\d{1,2}-\d{1,2}/"))));
			$entries++;
		} else {
			$output["update"]["expiry_date"] = null;
		}

		if ($skip_row) {
			return $err;
		}
		$output["entries"] = $entries;
			return $output;
	}

	private function importRow($valid_row = array()) {
		global $db;

		$delete = $this->delete && !$valid_row["entries"];

		if (!$valid_row["entries"] && !$delete) {
			return false;
		}

		$key = 0;

		if ($this->replace || $delete) {
			$key = $db->getOne("	SELECT `meta_value_id` FROM `meta_values` 
									WHERE `meta_type_id` = ".$db->qstr($valid_row["update"]["type"])." AND `proxy_id` = ".$db->qstr($valid_row["proxy_id"]));
		}

		if (!$key) {  // no existing user value
			if ($delete) {
			    return false;
			}
			$key = MetaDataValue::create($valid_row["update"]["type"], $valid_row["proxy_id"]);
		}

		if ($key) {
			$meta_value = MetaDataValue::get($key);
			if ($meta_value) {
				if ($delete) {
				    $meta_value->delete();
					$this->deleted++;
				} else {
					$meta_value->update($valid_row["update"]);
					return true;
				}
			}
		}
		return false;
	}

	public function importCsv($file) {
		$handle = fopen($file, "r");
		if ($handle) {
			$this->columns = array_values($this->col_map);
			$row_count = 0;
			while (($row = fgetcsv($handle)) !== false) {
				if ($row_count >= 1) {   // Skip header
					$results = $this->validateRow($row);
					if (isset($results["errors"])) {
						$this->errors[$row_count + 1] = $results["errors"];
					} else {
						$this->valid_rows[] = $results;
					}
				}
				$row_count++;
			}
			$row_count = 0;
			if (count($this->errors) <= 0) {
				foreach ($this->valid_rows as $valid_row) {
					if ($this->importRow($valid_row)) {
						$this->success[] = $row_count + 1;
					} else {
						$this->empty[] = $row_count + 1;
					}
					$row_count++;
				}
			}
		}
		fclose($handle);

		return $row_count;
	}
}
