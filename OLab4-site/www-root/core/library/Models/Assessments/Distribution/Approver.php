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
 * @author Organisation: Queen's University
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Approver extends Models_Base {
    protected $adapprover_id, $adistribution_id, $proxy_id, $created_date, $created_by;

    protected static $table_name = "cbl_assessment_distribution_approvers";
    protected static $primary_key = "adapprover_id";
    protected static $default_sort_column = "adapprover_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adapprover_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }
    
    public function fetchRowByID($adapprover_id) {
        return $this->fetchRow(array(
            array("key" => "adapprover_id", "value" => $adapprover_id, "method" => "=")
        ));
    }

    public function fetchRowByProxyID($proxy_id) {
        return $this->fetchRow(array(
            array("key" => "adapprover_id", "value" => $proxy_id, "method" => "=")
        ));
    }

    public function fetchRowByProxyIDDistributionID($proxy_id, $adistribution_id) {
        return $this->fetchRow(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public function fetchAllRecords() {
        return $this->fetchAll(array(array("key" => "adapprover_id", "value" => 0, "method" => ">=")));
    }
    
    public function fetchAllByDistributionID($adistribution_id) {
        $params = array(array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="));
        return $this->fetchAll($params);
    }

    public function fetchAllByProxyID($proxy_id, $current_section = "assessments", $filters = array(), $search_value = null, $limit = 0, $offset = 0) {
        global $db;
        $assessments = false;

        $course_id_list = Models_Course::getActiveUserCoursesIDList();

        $AND_course_in = ($current_section == "assessments" || empty($course_id_list) ? " " : "  AND b.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = "";
        $AND_course_filter_in = "";
        $AND_title_like = "";
        $LIMIT = "";
        $OFFSET = "";

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND b.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND b.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if ($search_value != "" && $search_value != null) {
            $AND_title_like = "     AND b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        $query = "          SELECT a.* FROM `cbl_assessment_distribution_approvers` AS a
                            JOIN `cbl_assessment_distributions` AS b
                            ON a.`adistribution_id` = b.`adistribution_id`
                            JOIN `courses` AS c
                            ON b.`course_id` = c.`course_id`                        
                            WHERE a.`proxy_id` = ?
                            AND b.`deleted_date` IS NULL
                            
                            $AND_course_in
                            $AND_course_filter_in
                            $AND_cperiod_in
                            $AND_title_like
                            
                            ORDER BY b.`title` ASC
                            $LIMIT $OFFSET
                            ";

        $results = $db->GetAll($query, array($proxy_id));
        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }

    public function getApproverName($proxy_id = "") {
        $name = false;
        $user = Models_User::fetchRowByID($proxy_id == "" ? $this->proxy_id : $proxy_id);

        if ($user) {
            $name = $user->getFirstname() . " " . $user->getLastname();
        }
        return $name;
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `" . static::$table_name . "` WHERE `" . static::$primary_key . "` = " . $this->getID())) {
            return true;
        } else {
            application_log("error", "Error deleting " . get_called_class(). " id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }
}