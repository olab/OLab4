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
 * General description of this file.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Models_Curriculum_Track extends Models_Base {

    protected $curriculum_track_id, $curriculum_track_name, $curriculum_track_description, $curriculum_track_url, $curriculum_track_order,
        $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "curriculum_lu_tracks";
    protected static $default_sort_column = "curriculum_track_order";
    protected static $primary_key = "curriculum_track_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->curriculum_track_id;
    }

    public function getCurriculumTrackName() {
        return $this->curriculum_track_name;
    }

    public function getCurriculumTrackDescription() {
        return $this->curriculum_track_description;
    }

    public function getCurriculumTrackURL() {
        return $this->curriculum_track_url;
    }

    public function getCurriculumTrackOrder() {
        return $this->curriculum_track_order;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($curriculum_track_id) {
        $self = new self();
        return $self->fetchRow(array("curriculum_track_id" => $curriculum_track_id));
    }

    public static function fetchAllByOrg($org_id) {
        global $db;
        $output = false;
        $query = "SELECT b.*
                    FROM `curriculum_lu_track_organisations` AS a
                    JOIN `curriculum_lu_tracks` AS b
                    ON a.`curriculum_track_id` = b.`curriculum_track_id`
                    WHERE a.`organisation_id` = ? AND b.`deleted_date` IS NULL ORDER BY `b`.`curriculum_track_order`";
        $results = $db->GetAll($query,array($org_id));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        return $output;
    }

    public static function countOrgByTrackID($curriculum_track_id) {
        global $db;

        $query = "SELECT COUNT(*) as org_num FROM `curriculum_lu_track_organisations` WHERE `curriculum_track_id` = ?";
        $result = $db->GetRow($query,array($curriculum_track_id));
        if ($result) {
            return $result;
        }
        return false;
    }

    public static function deleteTrackOrgByIdAndOrg($curriculum_track_id, $organisation_id) {
        global $db;

        $query = "DELETE FROM `curriculum_lu_track_organisations` WHERE `curriculum_track_id` = ? AND `organisation_id` = ?";

        if ($db->Execute($query, array($curriculum_track_id, $organisation_id))) {
            return true;
        }
        return false;
    }

    public static function insertTrackOrgRelationship($curriculum_track_id, $organisation_id) {
        global $db;

        $params = array("curriculum_track_id" => $curriculum_track_id, "organisation_id" => $organisation_id);
        if ($db->AutoExecute("curriculum_lu_track_organisations", $params, "INSERT")) {
            return true;
        }
        return false;
    }

    public static function setCurriculumTrackerOrderByIDArray($curriculum_track_id_array, $page, $pagelength) {
        global $db;
        foreach ($curriculum_track_id_array as $key => $curriculum_track_id) {
            $query = "UPDATE `curriculum_lu_tracks` SET 
                  curriculum_track_order = ?
                  WHERE `curriculum_track_id` = ?";
            $result = $db->Execute($query, array((($key + 1) + ($page * $pagelength)), $curriculum_track_id));
            if (!$result) {
                return false;
            }
        }
        return true;
    }
}