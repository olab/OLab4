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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Author extends Models_Base {
    protected $adauthor_id, $adistribution_id, $author_type, $author_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_distribution_authors";
    protected static $primary_key = "adauthor_id";
    protected static $default_sort_column = "adauthor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adauthor_id;
    }

    public function getAdauthorID() {
        return $this->adauthor_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAuthorType() {
        return $this->author_type;
    }

    public function getAuthorID() {
        return $this->author_id;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    /**
     * @return mixed
     */
    public function getDeletedDate()
    {
        return $this->deleted_date;
    }

    /**
     * @return mixed
     */
    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public static function fetchRowByID($adauthor_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adauthor_id", "value" => $adauthor_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adauthor_id", "value" => 0, "method" => ">=")));
    }
    
    public function getAuthorName() {
        global $db; 
        
        $return = false;
        switch ($this->author_type) {
            case "proxy_id" :
                $user = Models_User::fetchRowByID($this->author_id);
                if ($user) {
                    $return = $user->getFirstname() . " " . $user->getLastname();
                }
            break;
            case "organisation_id" :
                $query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ?";
                $result = $db->GetRow($query, array($this->author_id));
                if ($result) {
                    $return = $result["organisation_title"];
                }
            break;
            case "course_id" :
                $course = Models_Course::get($this->author_id);
                if ($course) {
                    $return = $course->getCourseCode() . " - " . $course->getCourseName();
                }
            break;
            default :
                $return = false;
            break;
        }
        return $return;
    }
    
    public static function fetchByAuthorTypeProxyID ($organisation_id = null, $search_value = null) {
        global $db;
        $authors = false;
        
        $query = "  SELECT a.`adistribution_id`, b.*, c.`id`, c.`firstname`, c.`lastname` FROM `cbl_assessment_distributions` AS a
                    JOIN `cbl_assessment_distribution_authors` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    JOIN `". AUTH_DATABASE ."`".".`user_data` AS c
                    ON b.`author_id` = c.`id`
                    WHERE a.`organisation_id` = ?
                    AND (c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") ."))
                    AND b.`author_type` = 'proxy_id'
                    GROUP BY b.`author_id`";
        
        $results = $db->GetAll($query, array($organisation_id));
        if ($results) {
            foreach ($results as $result) {
                $authors[] = new self(array("adauthor_id" => $result["adauthor_id"], "adistribution_id" => $result["adistribution_id"], "author_type" => $result["author_type"], "author_id" => $result["author_id"], "deleted_date" => $result["deleted_date"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"]));
            }
        }
        
        return $authors;
    }

    public static function fetchAllByDistributionID ($adistribution_id, $proxy_id = NULL) {
        $self = new self();
        $params = array(array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="));
        if (!is_null($proxy_id)) {
            $params[] = array("key" => "author_type", "value" => "proxy_id", "method" => "=");
            $params[] = array("key" => "author_id", "value" => $proxy_id, "method" => "=");
        }
        return $self->fetchAll($params);
    }

    public static function fetchAvailableAuthors($filter_type, $adistribution_id, $search_value) {
        global $db, $ENTRADA_USER;

        switch ($filter_type) {
            case "organisation_id" :
                $query = "  SELECT a.`organisation_id` AS `proxy_id`, `organisation_title` AS `firstname`, '' as `lastname`, '' AS `email`, '' AS `group`, 'organisation' AS `role`
                            FROM `" . AUTH_DATABASE . "`.`organisations` AS a";
                if ($adistribution_id) {
                // Prevent return of duplicate authors.
                $query .= " LEFT JOIN `cbl_assessment_distribution_authors` AS b
                            ON a.`organisation_id` = b.`author_id`
                            AND b.`adistribution_id` = ".$db->qstr($adistribution_id)."
                            AND b.`author_type` = 'organisation_id'
                            AND b.`deleted_date` IS NULL";
                }
                $query .= " WHERE a.`organisation_title` LIKE (" . $db->qstr($search_value) . ")";
                if ($adistribution_id) {
                $query .= " AND b.`adistribution_id` IS NULL";
                }

                break;
            case "course_id" :
                $query = "  SELECT a.`course_id` AS `proxy_id`, CONCAT(a.`course_code`, ' - ', a.`course_name`) AS `firstname`, '' AS `lastname`, '' AS `email`, '' AS `group`, 'course' AS `role`
                            FROM `courses` AS a";
                if ($adistribution_id) {
                // Prevent return of duplicate authors.
                $query .= " LEFT JOIN `cbl_assessment_distribution_authors` AS b
                            ON a.`course_id` = b.`author_id`
                            AND b.`adistribution_id` = ".$db->qstr($adistribution_id)."
                            AND b.`author_type` = 'course_id'
                            AND b.`deleted_date` IS NULL";
                }
                $query .= " WHERE (a.`course_code` LIKE (".$db->qstr($search_value).")
                            OR a.`course_name` LIKE (".$db->qstr($search_value)."))
                            AND a.`course_active` = '1'
                            AND a.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation());
                if ($adistribution_id) {
                $query .= " AND b.`adistribution_id` IS NULL";
                }
                $query .= " ORDER BY a.`course_code`";

                break;
            case "proxy_id" :
                $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, '' AS `group`, b.`role`, a.`email`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`";
                if ($adistribution_id) {
                // Prevent return of duplicate authors.
                $query .= " LEFT JOIN `cbl_assessment_distribution_authors` AS c
                            ON a.`id` = c.`author_id`
                            AND c.`adistribution_id` = ".$db->qstr($adistribution_id)."
                            AND c.`author_type` = 'proxy_id'
                            AND c.`deleted_date` IS NULL
                            WHERE c.`adistribution_id` IS NULL
                            AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")";
                } else {
                // Do not allow the user to select themselves if the distribution is being created rather than edited. It should be selected by default.
                $query .= " WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
                            AND a.`id` NOT IN (" . $ENTRADA_USER->getID() . ")";
                }
                $query .= " AND b.`account_active` = 'true'
                    AND (
                            CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE (".$db->qstr($search_value).") OR
                            CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE (".$db->qstr($search_value).") OR
                            a.email LIKE (".$db->qstr($search_value).")
                        )
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";

                break;
        }

        return $db->GetAll($query);
    }

    public static function fetchAllByAuthorTypeAuthorValue($author_type, $author_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "author_type", "method" => "=", "value" => $author_type),
            array("key" => "author_id", "method" => "=", "value" => $author_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

}