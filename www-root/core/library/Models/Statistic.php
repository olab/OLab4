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
 * A model to handle recording and fetching statistics.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Statistic extends Models_Base {
    
    protected $statistic_id, $proxy_id, $timestamp, $module, $submodule, 
              $section, $action, $action_field, $action_value, $prune_after;

    protected static $primary_key = "statistic_id";
    protected static $table_name = "statistics";
    protected static $default_sort_column = "timestamp";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($statistic_id) {
        $self = new self();
        return $self->fetchRow(array("statistic_id" => $statistic_id));
    }
    
    public function getStatisticID() {
        return $this->statistic_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    public function getModule() {
        return $this->module;
    }

    public function getSubmodule() {
        return $this->submodule;
    }

    public function getSection() {
        return $this->section;
    }

    public function getAction() {
        return $this->action;
    }

    public function getActionField() {
        return $this->action_field;
    }

    public function getActionValue() {
        return $this->action_value;
    }

    public function getPruneAfter() {
        return $this->prune_after;
    }
    
    public static function getEventViews($event_id) {
        global $db;
        
        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `".DATABASE_NAME."`.`statistics` AS a
                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`module` = 'events'
                    AND a.`action` = 'view'
                    AND a.`action_field` = 'event_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($event_id));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getCommunityFileDownloads($module, $action_value) {
        global $db;
        
        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `".DATABASE_NAME."`.`statistics` AS a
                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`module` = ?
                    AND a.`action` = 'file_download'
                    AND a.`action_field` = 'csfile_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($module, $action_value));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getCommunityFolderViews($module, $action_value) {
        global $db;
        
        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `".DATABASE_NAME."`.`statistics` AS a
                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`module` = ?
                    AND a.`action` = 'folder_view'
                    AND a.`action_field` = 'cshare_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($module, $action_value));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getCommunityLinkViews($module, $action_value) {
        global $db;

        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `" . DATABASE_NAME . "`.`statistics` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`module` = ?
                    AND a.`action` = 'link_view'
                    AND a.`action_field` = 'cslink_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($module, $action_value));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }

    public static function getEventFileViews($action_value) {
        global $db;

        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `".DATABASE_NAME."`.`statistics` AS a
                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE (a.`module` = 'events' OR a.`module` = 'podcasts')
                    AND a.`action` = 'file_download'
                    AND a.`action_field` = 'file_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($action_value));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getEventLinkViews($action_value) {
        global $db;

        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(*) AS views, b.`firstname`, b.`lastname`, MAX(a.`timestamp`) as last_viewed_time
                    FROM `".DATABASE_NAME."`.`statistics` AS a
                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`module` = 'events'
                    AND a.`action` = 'link_access'
                    AND a.`action_field` = 'link_id' 
                    AND a.`action_value` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname` ASC";
        $results = $db->GetAll($query, array($action_value));
        echo $db->ErrorMsg();
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getGradebookViews($assessment_id) {
        global $db;

        $query = "SELECT DISTINCT (a.`proxy_id`), COUNT(DISTINCT d.`statistic_id`) AS views, c.`firstname`, c.`lastname`, DATE_FORMAT(FROM_UNIXTIME(MIN(d.`timestamp`)), '%Y-%m-%d %H:%i') as `first_viewed_time`, DATE_FORMAT(FROM_UNIXTIME(MAX(d.`timestamp`)), '%Y-%m-%d %H:%i') as `last_viewed_time`
                    FROM `group_members` AS a
                    JOIN `assessments` AS b
                    ON a.`group_id` = b.`cohort`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS c
                    ON a.`proxy_id` = c.`id`
                    LEFT JOIN `statistics` AS d
                    ON b.`assessment_id` = d.`action_value`
                    AND d.`proxy_id` = a.`proxy_id`
                    AND d.`action_field` = 'assessment_id'
                    AND d.`action` = 'view'
                    AND d.`module` = 'gradebook'
                    WHERE b.`assessment_id` = ?
                    GROUP BY a.`proxy_id`
                    ORDER BY c.`lastname`, c.`firstname`";
        $results = $db->GetAll($query, array($assessment_id));
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }
    
    public static function getCountByParams($params = array()) {
        global $db;
        
        $query = "SELECT COUNT(*) AS `views`
                    FROM `statistics` AS a
                    WHERE a.`module` = ?
                    AND a.`action` = ?
                    AND a.`action_field` = ?
                    AND a.`action_value` = ?";
        $result = $db->GetRow($query, array($params["module"], $params["action"], $params["action_field"], $params["action_value"]));
        return $result;
    }

    /**
     * Method returns an array of the learner logins since the date specified.
     *
     * @param int $since
     * @return mixed
     */
    public function getLearnerLogins($since = 0) {
        global $db;

        $query = "SELECT a.*, c.`number`, c.`firstname`, c.`lastname`, c.`email`
                  FROM `statistics` AS a
                  JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                  ON b.`user_id` = a.`proxy_id`
                  AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                  AND b.`group` = 'student'
                  JOIN `" . AUTH_DATABASE . "`.`user_data` AS c
                  ON c.`id` = b.`user_id`
                  WHERE a.`timestamp` >= " . (int) $since . "
                  AND a.`module` = 'index'
                  AND a.`action` = 'login'";

        $statistics = $db->GetAll($query);

        return $statistics;
    }

    public function getLearnerStats($since = 0) {
        global $db;

        $query = "SELECT a.*, c.id AS `proxy_id`, c.`number`, c.`firstname`, c.`lastname`, c.`email`
                  FROM `statistics` AS a
                  JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                  ON b.`user_id` = a.`proxy_id`
                  AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                  AND b.`group` = 'student'
                  JOIN `" . AUTH_DATABASE . "`.`user_data` AS c
                  ON c.`id` = b.`user_id`
                  WHERE a.`timestamp` >= " . (int) $since;

        $statistics = $db->GetAll($query);

        return $statistics;
    }

    public static function fetchAllRecords($module, $action, $action_field, $proxy_id = NULL) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "module",
                "value"     => $module,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "action",
                "value"     => $action,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "action_field",
                "value"     => $action_field,
                "method"    => "="
            )
        );
        
        if (!is_null($proxy_id)) {
            $constraints[] = array(
                "mode"      => "AND",
                "key"       => "proxy_id",
                "value"     => $proxy_id,
                "method"    => "="
            );
        }

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
}

