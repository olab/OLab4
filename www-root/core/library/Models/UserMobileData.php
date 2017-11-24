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
 * A model for user mobile data
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_UserMobileData extends Models_Base {
    protected $id, $proxy_id, $hash, $hash_expires, $push_notifications, $created_by, $updated_by, $updated_date, $deleted_date;

    protected static $database_name        = AUTH_DATABASE;
    protected static $table_name           = "user_mobile_data";
    protected static $primary_key          = "id";
    protected static $default_sort_column  = "id";

    public function getID () {
        return $this->id;
    }

    public function getProxyID () {
        return $this->proxy_id;
    }

    public function getHash () {
        return $this->hash;
    }

    public function setHash ($hash) {
        $this->hash = $hash;
        return $this;
    }

    public function setHashExpires ($hash_expires) {
        $this->hash_expires = $hash_expires;
        return $this;
    }

    public function getHashExpires () {
        return $this->hash_expires;
    }

    public function getPushNotifications () {
        return $this->push_notifications;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public static function fetchRowByProxyID ($proxy_id = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "proxy_id", "value" => (int) $proxy_id, "method" => "=")
        ));
    }

    public static function fetchRowByHash ($hash = null) {
        global $db;
        $user_mobile_data = false;

        $query = "  SELECT a.* FROM `". AUTH_DATABASE ."`.`user_mobile_data` AS a
                    JOIN `". AUTH_DATABASE ."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`hash` = ?";

        $result = $db->GetRow($query, array($hash));
        if ($result) {
            $user_mobile_data = new self($result);
        }
        
        return $user_mobile_data;
    }

    public static function fetchUserNotices($proxy_id = null) {
        global $db;
        $ENTRADA_USER = User::get($proxy_id);
        $output = array();
        $i = 0;

        if ($ENTRADA_USER) {
            $total_organisations = count($ENTRADA_USER->getOrganisationGroupRole());

            $query = "SELECT a.*, b.`statistic_id`, MAX(b.`timestamp`) AS `last_read`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS notice_author
                        FROM `notices` AS a
                        LEFT JOIN `statistics` AS b
                        ON b.`module` = 'notices'
                        AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getId())."
                        AND b.`action` = 'read'
                        AND b.`action_field` = 'notice_id'
                        AND b.`action_value` = a.`notice_id`
                        JOIN `notice_audience` AS c
                        ON a.`notice_id` = c.`notice_id`
                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                        ON c.`id` = a.`updated_by`
                        WHERE (
                            (";
            foreach ($ENTRADA_USER->getOrganisationGroupRole() as $organisation_id => $groups) {
                $i++;

                $all_audience_types = array();
                $all_groups = array();

                foreach ($groups as $group) {
                    /**
                     * Allows the MEdTech group to be classified as staff by
                     * the notices module.
                     */
                    if ($group["group"] == "medtech") {
                        $group["group"] = "staff";
                    }

                    if ($group["group"] != "staff" || !in_array("staff", $all_groups)) { // This makes sure staff are only added once.
                        $all_audience_types[] = "all:" . $group["group"];
                        $all_groups[] = $group["group"];
                    }
                }

                $query .= "     (
                                    a.`organisation_id` = ".$db->qstr($organisation_id)."
                                    AND (
                                        c.`audience_type` = 'all:all'
                                        OR c.`audience_type` IN ('".implode("', '", $all_audience_types)."')
                                        OR (
                                            c.`audience_type` IN ('".implode("', '", $all_groups)."')
                                            AND c.`audience_value` = ".$db->qstr($ENTRADA_USER->getId())."
                                        )
                                    )
                                )";

                if ($i < $total_organisations) {
                    $query .= " OR ";
                }

            }
            $query .= "
                            )
                            OR (
                                (c.`audience_type` = 'cohort' OR c.`audience_type` = 'course_list')
                                AND c.`audience_value` IN (
                                    SELECT a.`group_id`
                                    FROM `group_members` AS a
                                    JOIN `groups` AS b
                                    ON b.`group_id` = a.`group_id`
                                    WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getId())."
                                    AND a.`member_active` = 1
                                    AND (a.`start_date` IS NULL OR a.`start_date` = 0 OR a.`start_date` <= UNIX_TIMESTAMP())
                                    AND (a.`finish_date` IS NULL OR a.`finish_date` = 0 OR a.`finish_date` > UNIX_TIMESTAMP())
                                    AND b.`group_active` = 1
                                    AND (b.`start_date` IS NULL OR b.`start_date` = 0 OR b.`start_date` <= UNIX_TIMESTAMP())
                                    AND (b.`expire_date` IS NULL OR b.`expire_date` = 0 OR b.`expire_date` > UNIX_TIMESTAMP())
                                )
                            )
                        )
                        GROUP BY a.`notice_id`
                        ORDER BY a.`updated_date` DESC, a.`display_until` ASC";

            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
					$output[] = $result;
                }
            }
        }
        return $output;
    }

    public static function fetchUserEvents ($user_proxy_id = null, $user_group = null, $user_role = null, $user_organisation_id = null) {
        global $db;

        $ENTRADA_USER = User::get($user_proxy_id);
        $ENTRADA_USER->setActiveGroup($user_group);
        $ENTRADA_USER->setActiveRole($user_role);
        $ENTRADA_USER->setActiveOrganisation($user_organisation_id);

        $GLOBALS["ENTRADA_USER"] = $ENTRADA_USER;
        $ENTRADA_ACL = new Entrada_Acl(array("id" => $user_proxy_id, "group" => $user_group, "role" => $user_role, "organisation_id" => $user_organisation_id));
        $GLOBALS["ENTRADA_ACL"] = $ENTRADA_ACL;

        $learning_events = false;
        $event_start = strtotime("-12 months 00:00:00");
    	$event_finish = strtotime("+12 months 23:59:59");

        if ($user_group == "faculty" || $user_group == "staff" || $user_group == "medtech") {
            $learning_events = events_fetch_filtered_events(
                            $user_proxy_id,
                            $user_group,
                            $user_role,
                            $user_organisation_id,
                            "date",
                            "asc",
                            "custom",
                            $event_start,
                            $event_finish,
                            (isset($selected_course) && $selected_course ? events_filters_faculty($selected_course, $user_group, $user_role) : events_filters_defaults($user_proxy_id, $user_group, $user_role,  0, 0)),
                            true,
                            1,
                            1750,
                            0,
                            ($user_group == "student" ? true : false));
        } else {
            $learning_events = events_fetch_filtered_events(
                    $user_proxy_id,
                    $user_group,
                    $user_role,
                    $user_organisation_id,
                    "date",
                    "asc",
                    "custom",
                    $event_start,
                    $event_finish,
                    events_filters_defaults($user_proxy_id, $user_group, $user_role,  0, $selected_course),
                    true,
                    1,
                    1750,
                    0,
                    ($user_group == "student" ? true : false));
        }

    	/*if ($ENTRADA_ACL->amIAllowed("clerkship", "read")) {
    		$query = "	SELECT c.*
    					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
    					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
    					ON b.`event_id` = a.`event_id`
    					LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
    					ON c.`rotation_id` = a.`rotation_id`
    					WHERE a.`event_finish` >= ".$db->qstr(strtotime("00:00:00"))."
    					AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
    					AND b.`econtact_type` = 'student'
    					AND b.`etype_id` = ".$db->qstr($user_proxy_id)."
    					ORDER BY a.`event_start` ASC";
    		$clerkship_schedule	= $db->GetRow($query);
    		if (isset($clerkship_schedule) && $clerkship_schedule && $clerkship_schedule["rotation_id"] != MAX_ROTATION) {
    			$course_id = $clerkship_schedule["course_id"];
    			$course_ids = array();

                $query 	= "SELECT `course_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
    					WHERE `course_id` <> ".$db->qstr($course_id)."
    					AND `course_id` <> 0";

                $course_ids_array = $db->GetAll($query);
    			foreach ($course_ids_array as $id) {
    					$course_ids[] = $id;
    			}

    			foreach ($learning_events["events"] as $key => $event) {
    				if (array_search($event["course_id"], $course_ids) !== false) {
    					unset($learning_events["events"][$key]);
    				}
    			}
    		}
    	}*/

        return $learning_events["events"];
    }
}
