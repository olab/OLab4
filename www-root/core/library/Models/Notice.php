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
 * Models_Notice
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 */

class Models_Notice {
    /**
     * This method returns the organisations that use this application.
     * 
     * @global object $db
     * @global object $config
     * @return string CSV organisation IDs
     */
    private static function getAppOrganisations() {
        global $db, $config;
        
        $query = "SELECT DISTINCT b.`organisation_id`
                    FROM `".AUTH_DATABASE."`.`registered_apps` AS a
                    JOIN `".AUTH_DATABASE."`.`organisations` AS b
                    ON a.`id` = b.`app_id`
                    WHERE a.`script_id` = ?";
        return $db->CacheGetAll($query, array($config->auth_username));
    }
    
    /**
     * This method returns the top 5 public notices for use on the
     * Entrada login page (primarily).
     *
     * @global object $db
     * @return array
     */
    public static function fetchPublicNotices($start = 0, $limit = 5) {
        global $db;

        $start = (int) $start;
        $limit = (int) $limit;

        $PROCESSED["org_ids"] = array();
        $org_ids = self::getAppOrganisations();
        if ($org_ids) {
            foreach ($org_ids as $org_id) {
                $tmp_input = clean_input($org_id["organisation_id"], "int");
                if ($tmp_input) {
                    $PROCESSED["org_ids"][] = $db->qstr($tmp_input);
                }
            }
        }
        
        $query = "SELECT a.*
                    FROM `notices` AS a
                    JOIN `notice_audience` AS b
                    ON a.`notice_id` = b.`notice_id`
                    WHERE b.`audience_type` = 'public'
                    AND (a.`display_from` = 0 OR a.`display_from` <= UNIX_TIMESTAMP())
                    AND (a.`display_until` = 0 OR a.`display_until` > UNIX_TIMESTAMP())
                    AND (a.`organisation_id` IN (".implode(",", $PROCESSED["org_ids"])."))
                    GROUP BY a.`notice_id`
                    ORDER BY a.`updated_date` DESC, a.`display_until` ASC
                    LIMIT ?, ?";
        return $db->CacheGetAll(CACHE_TIMEOUT, $query, array($start, $limit));
    }

    /**
     * This method returns notices
     * @global object $db
     * @param type $proxy_id
     * @param type $group
     * @param type $org_id
     * @param type $include_read_notices
     * @return type
     */
    public static function fetchUserNotices($include_read_notices = false, $only_read_notices = false) {
        global $db, $ENTRADA_USER;

        $include_read_notices = (bool) $include_read_notices;
        $output = array();

        $i = 0;
        $total_organisations = count($ENTRADA_USER->getOrganisationGroupRole());

        if ($ENTRADA_USER) {
            $query = "SELECT a.*, b.`statistic_id`, MAX(b.`timestamp`) AS `last_read`, c.`firstname`, c.`lastname`
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
                        AND (a.`display_from` IS NULL OR a.`display_from` = 0 OR a.`display_from` <= UNIX_TIMESTAMP())
                        AND (a.`display_until` IS NULL OR a.`display_until` = 0 OR a.`display_until` >= UNIX_TIMESTAMP())
                        GROUP BY a.`notice_id`
                        ORDER BY a.`updated_date` DESC, a.`display_until` ASC";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
					if ($only_read_notices) {
						if (($result["statistic_id"]) || !($result["last_read"] <= $result["updated_date"])) {
							$output[] = $result;
						}
					} else {
						if ($include_read_notices || !$result["statistic_id"] || ($result["last_read"] <= $result["updated_date"])) {
							$output[] = $result;
						}
					}
                }
            }
        }

        return $output;
    }
	
	public static function fetchNotice($notice_id = 0) {
		global $db;
		$query = "SELECT * FROM `notices` WHERE `notice_id` = ?";
		$result = $db->GetRow($query, array($notice_id));
		return $result;
	}
	
	public static function fetchOrganisationNotices () {
		global $db, $ENTRADA_USER;
		$output = array();
		$query = "	SELECT a.*, b.`organisation_title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS notice_author
					FROM `notices` AS a
					JOIN `".AUTH_DATABASE."`.`organisations` AS b
					ON b.`organisation_id` = a.`organisation_id`
					JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`created_by` = c.`id`
					WHERE a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					AND a.`display_until` > '".strtotime("-5 days 00:00:00")."'
					ORDER BY a.`display_until` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$output[] = $result;
			}
		}
		return $output;
	}

	public static function fetchAuthorNotices () {
		global $db, $ENTRADA_USER;
		$output = array();
		$query = "	SELECT a.*, b.`organisation_title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS notice_author
					FROM `notices` AS a
					JOIN `".AUTH_DATABASE."`.`organisations` AS b
					ON b.`organisation_id` = a.`organisation_id`
					JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`created_by` = c.`id`
					WHERE a.`created_by` = ".$db->qstr($ENTRADA_USER->getActiveID())."
					ORDER BY a.`display_until` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$output[] = $result;
			}
		}
		return $output;
	}
	
	public static function UserPushNotificationsInfo ($proxy_id = 0, $max_notice_id = 0) {
		global $db;
		$i = 0;
		$user = User::fetchRowByID($proxy_id);
		$total_organisations = count($user->getOrganisationGroupRole());
		$query = "  SELECT COUNT(*) AS `notice_count`, MAX(a.`notice_id`) AS `max_notice_id`
					FROM `notices` AS a
					LEFT JOIN `statistics` AS b
					ON b.`module` = 'notices'
					AND b.`proxy_id` = ".$db->qstr($user->getId())."
					AND b.`action` = 'read'
					AND b.`action_field` = 'notice_id'
					AND b.`action_value` = a.`notice_id`
					JOIN `notice_audience` AS c
					ON a.`notice_id` = c.`notice_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON c.`id` = a.`updated_by`
					WHERE (
						(";
		foreach ($user->getOrganisationGroupRole() as $organisation_id => $groups) {
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
									c.`audience_type` = 'all'
									OR c.`audience_type` IN ('".implode("', '", $all_audience_types)."')
									OR (
										c.`audience_type` IN ('".implode("', '", $all_groups)."')
										AND c.`audience_value` = ".$db->qstr($user->getId())."
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
							WHERE a.`proxy_id` = ".$db->qstr($user->getId())."
							AND a.`member_active` = 1
							AND (a.`start_date` IS NULL OR a.`start_date` = 0 OR a.`start_date` <= UNIX_TIMESTAMP())
							AND (a.`finish_date` IS NULL OR a.`finish_date` = 0 OR a.`finish_date` > UNIX_TIMESTAMP())
							AND b.`group_active` = 1
							AND (b.`start_date` IS NULL OR b.`start_date` = 0 OR b.`start_date` <= UNIX_TIMESTAMP())
							AND (b.`expire_date` IS NULL OR b.`expire_date` = 0 OR b.`expire_date` > UNIX_TIMESTAMP())
						)
					)
				)
				AND (a.`display_from` IS NULL OR a.`display_from` = 0 OR a.`display_from` <= UNIX_TIMESTAMP())
				AND (a.`display_until` IS NULL OR a.`display_until` = 0 OR a.`display_until` >= UNIX_TIMESTAMP())
				AND (a.`notice_id` > " . $db->qstr($max_notice_id) . ")
				ORDER BY a.`updated_date` DESC, a.`display_until` ASC";
		$notices = $db->GetRow($query);
		if ($notices) {
			return $notices;
		}
	}
}
