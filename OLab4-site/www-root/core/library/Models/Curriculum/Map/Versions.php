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
 * A model for handling Curriculum Map Versions.
 *
 * @author Organisation: University of British Columbia
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Curriculum_Map_Versions extends Models_Base {
    protected $version_id, $title, $description, $status, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "curriculum_map_versions";
    protected static $primary_key = "version_id";
    protected static $default_sort_column = "title";
    protected static $default_sort_order = "DESC";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->version_id;
    }

    public function getVersionID() {
        return $this->version_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public function fetchRowByID($version_id, $organisation_id) {
        global $db;

        $version_id = (int) $version_id;
        $organisation_id = (int) $organisation_id;

        if ($version_id) {
            $query = "SELECT a.*, b.`organisation_id`
                        FROM `" . static::$table_name . "` AS a
                        JOIN `curriculum_map_version_organisations` AS b
                        ON a.`version_id` = b.`version_id`
                        WHERE a.`deleted_date` IS NULL
                        AND a.`version_id` = ?
                        AND b.`organisation_id` = ?";
            $result = $db->GetRow($query, array($version_id, $organisation_id));

            if ($result) {
                return new self($result);
            }
        }

        return false;
    }

    public function fetchAllRecords($organisation_id) {
        global $db;

        $organisation_id = (int) $organisation_id;

        if ($organisation_id) {
            $query = "SELECT a.*
                        FROM `" . static::$table_name . "` AS a
                        JOIN `curriculum_map_version_organisations` AS b
                        ON a.`version_id` = b.`version_id`
                        WHERE a.`deleted_date` IS NULL
                        AND b.`organisation_id` = ?
                        ORDER BY a.`title` DESC";
            return $db->GetAll($query, array($organisation_id));
        }

        return false;
    }

    public function insertOrganisation($organisation_id) {
        global $db;

        $version_id = $this->getID();
        $organisation_id = (int) $organisation_id;

        if ($version_id && $organisation_id) {
            return $db->AutoExecute("curriculum_map_version_organisations", array("version_id" => $version_id, "organisation_id" => $organisation_id), "INSERT");
        }

        return false;
    }

    public function copyUnversionedLinkedObjectives() {
        global $db;

        $query = "INSERT INTO `linked_objectives` SELECT NULL, " . $db->qstr($this->getVersionID()) . ", `objective_id`, `target_objective_id`, 1 FROM `linked_objectives` WHERE `version_id` IS NULL";

        return $db->Execute($query);
    }

    public function copyLinkedObjectives($old_version_id) {
        global $db;

        $old_version_id = (int) $old_version_id;

        $query = "INSERT INTO `linked_objectives` SELECT NULL, " . $db->qstr($this->getVersionID()) . ", `objective_id`, `target_objective_id`, 1 FROM `linked_objectives` WHERE `version_id` = " . $db->qstr($old_version_id);

        return $db->Execute($query);
    }

    public function delete(array $version_ids) {
        global $db, $ENTRADA_USER;
        
        $version_ids = array_filter($version_ids, "is_numeric");
        if ($version_ids) {
            $query = "UPDATE `" . static::$table_name . "` SET `updated_date` = " . time() . ", `updated_by` = ?, `deleted_date` = " . time() . " WHERE `version_id` IN (" . implode(", ", $version_ids) . ")";
            return $db->Execute($query, array($ENTRADA_USER->getID()));
        }

        return false;
    }

    public function fetchPeriods() {
        global $db;
        $query = "SELECT `curriculum_periods`.*
                  FROM `curriculum_periods`
                  INNER JOIN `curriculum_map_version_periods`
                  ON `curriculum_map_version_periods`.`cperiod_id` = `curriculum_periods`.`cperiod_id`
                  WHERE `version_id` = ?";
        $result = $db->GetAll($query, array($this->getID()));
        if (is_array($result)) {
            $curriculum_periods = array();
            foreach ($result as $row) {
                $curriculum_periods[] = new Models_Curriculum_Period($row);
            }
            return $curriculum_periods;
        } else {
            return false;
        }
    }

    public function fetchPeriodIDs() {
        $get_period_id = function ($period) {
            return $period->getCperiodID();
        };
        return array_map($get_period_id, $this->fetchPeriods());
    }

    public function insertPeriods(array $curriculum_period_ids) {
        global $db;
        $version_id = $this->getID();
        $curriculum_period_ids = array_filter($curriculum_period_ids, 'is_numeric');
        if ($curriculum_period_ids) {
            foreach ($curriculum_period_ids as $curriculum_period_id) {
                $query = "INSERT INTO `curriculum_map_version_periods`(`version_id`, `cperiod_id`)
                          VALUES (?, ?)";
                if ($db->Execute($query, array($version_id, $curriculum_period_id)) === false) {
                    return false;
                }
            }
        }
        return true;
    }

    public function deletePeriods(array $curriculum_period_ids) {
        global $db;
        $version_id = $this->getID();
        $curriculum_period_ids = array_filter($curriculum_period_ids, 'is_numeric');
        if ($curriculum_period_ids) {
            $curriculum_period_ids_sql = implode(", ", $curriculum_period_ids);
            $query = "DELETE FROM curriculum_map_version_periods
                      WHERE version_id = ?
                      AND cperiod_id IN (" . $curriculum_period_ids_sql . ")";
            return $db->Execute($query, array($version_id));
        }
        return true;
    }

    public function updatePeriods(array $curriculum_period_ids) {
        $curriculum_period_ids = array_filter($curriculum_period_ids, 'is_numeric');
        $existing_curriculum_period_ids = $this->fetchPeriodIDs();
        $new_curriculum_period_ids = array_diff($curriculum_period_ids, $existing_curriculum_period_ids);
        $common_curriculum_period_ids = array_intersect($curriculum_period_ids, $existing_curriculum_period_ids);
        $delete_curriculum_period_ids = array_diff($existing_curriculum_period_ids, $common_curriculum_period_ids);
        if ($curriculum_period_ids) {
            return (
                $this->insertPeriods($new_curriculum_period_ids) &&
                $this->deletePeriods($delete_curriculum_period_ids)
            );
        } else {
            return true;
        }
    }

    public function getPublishedVersionByPeriods(array $curriculum_period_ids) {
        global $db;
        $curriculum_period_ids = array_filter($curriculum_period_ids, 'is_numeric');
        if ($curriculum_period_ids) {
            if ($this->getID()) {
                $version_id_sql = "AND `curriculum_map_versions`.`version_id` <> " . ((int) $this->getID());
            }
            $curriculum_period_ids_sql = implode(", ", $curriculum_period_ids);
            $query = "SELECT `curriculum_map_versions`.*
                      FROM `curriculum_map_versions`
                      INNER JOIN `curriculum_map_version_periods`
                      ON `curriculum_map_version_periods`.`version_id` = `curriculum_map_versions`.`version_id`
                      WHERE `curriculum_map_versions`.`deleted_date` IS NULL
                      AND `curriculum_map_versions`.`status` = 'published'
                      " . $version_id_sql . "
                      AND `curriculum_map_version_periods`.`cperiod_id` IN (" . $curriculum_period_ids_sql . ")";
            $result = $db->GetRow($query);
            if ($result) {
                return new self($result);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function fetchAllByCperiodIDOrganisationID($cperiod_id, $organisation_id) {
        global $db;

        $query = "
            SELECT a.* FROM `curriculum_map_versions` AS a
            JOIN `curriculum_map_version_periods` AS b ON b.`version_id` = a.`version_id`
            JOIN `curriculum_periods` c ON c.`cperiod_id` = b.`cperiod_id`
            JOIN `curriculum_lu_types` d ON d.`curriculum_type_id` = c.`curriculum_type_id`
            JOIN `curriculum_type_organisation` e ON e.`curriculum_type_id` = d.`curriculum_type_id`
            WHERE b.`cperiod_id` = ?
            AND e.`organisation_id` = ?
            AND a.`deleted_date` IS NULL
            AND c.`active` = 1
            AND d.`curriculum_type_active` = 1
            ORDER BY a.`version_id` DESC";

        $results = $db->GetAll($query, array($cperiod_id, $organisation_id));
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        }
    }
}
