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
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Entrada_Curriculum_Search {

    /**
     * This method generates and returns the 2 queries used to perform a Curriculum Search. The first query is a simple
     * counter to determine how many results are matching. The second query performs the actual search. This is a
     * relatively complex query, which has been optimized for efficiency and speed. Unfortunately, that makes it
     * slightly complicated for developers to read.
     *
     * @param string $search_term
     * @param int $search_org
     * @param int $search_cohort
     * @param int $search_academic_year
     * @param bool|true $only_active
     * @param int $search_course
     * @param int $search_unit
     * @param string $sort_order
     *
     * @return array|bool
     */
    public static function prepare($search_term = "", $search_org = 0, $search_cohort = 0, $search_academic_year = 0, $only_active = true, $add_limit_param = true, array $search_filters = array(), array $filter_tag_ids = array(), $search_course = 0, $search_unit = 0, $sort_order = null) {
        global $db;

        $traversal_filters = array();

        $search_org = (int) $search_org;
        $search_cohort = (int) $search_cohort;
        $search_academic_year = (int) $search_academic_year;
        $search_course = (int) $search_course;
        
        if ($only_active !== true) {
            $only_active = false;
        }

        if ($add_limit_param !== true) {
            $add_limit_param = false;
        }

        $counter_select = "SELECT DISTINCT(`events`.`event_id`)";

        $search_select  = "SELECT `courses`.`course_code`, `courses`.`course_name`, `events`.*, `event_audience`.`audience_type`, COALESCE(`course_audience`.`audience_value`, `event_audience`.`audience_value`) AS `event_cohort`, ";

        $from = "FROM `events`
                JOIN `courses` ON `events`.`course_id` = `courses`.`course_id`
                " . ($only_active ? " AND `courses`.`course_active` = 1" : "") . "
                " . ($search_org ? " AND `courses`.`organisation_id` = " . (int) $search_org : "") . "
                LEFT JOIN `event_audience` ON `event_audience`.`event_id` = `events`.`event_id`
                LEFT JOIN `course_audience` ON `course_audience`.`course_id` = `courses`.`course_id`
                AND `event_audience`.`audience_type` = 'course_id'
                AND `course_audience`.`course_id` = `event_audience`.`audience_value`
                LEFT JOIN `curriculum_periods` ON `curriculum_periods`.`cperiod_id` = `course_audience`.`cperiod_id`";

        $where = "WHERE ";

        if ($search_course) {
            $where .= "courses.course_id = $search_course AND ";
        }

        if ($search_unit) {
            $where .= "events.cunit_id = $search_unit AND ";
        }

        $where .= "(`events`.`parent_id` IS NULL OR `events`.`parent_id` = '0')";

        if ($search_cohort) {
            $where .= " AND (`event_audience`.`audience_type` = 'cohort' AND `event_audience`.`audience_value` = " . (int) $search_cohort . "";
            $where .= " OR `course_audience`.`audience_type` = 'group_id' AND `course_audience`.`audience_value` = " . (int) $search_cohort . ")";
            $where .= " AND (`curriculum_periods`.`cperiod_id` IS NULL OR (`events`.`event_start` BETWEEN `curriculum_periods`.`start_date` AND `curriculum_periods`.`finish_date`))";
        }

        if ($search_academic_year) {
            if (defined("ACADEMIC_YEAR_START_DATE")) {
                $academic_start_date = ACADEMIC_YEAR_START_DATE;
            } else {
                $academic_start_date = "September 1";
            }

            $search_dates = fetch_timestamps("academic", strtotime("00:00:00 " . $academic_start_date . " " . $search_academic_year));

            $where .= " AND (`events`.`event_start` BETWEEN " . $db->qstr($search_dates["start"]) . " AND " . $db->qstr($search_dates["end"]) . ")";

            $traversal_filters["start"] = $search_dates["start"];
            $traversal_filters["end"] = $search_dates["end"];
        }

        $traversal = Entrada_Curriculum_Traversal::getInstance();

        if (!empty($search_filters) || !empty($search_course) || !empty($search_unit)) {
            $where .= " " . self::searchFilterWhereSql($search_filters);
            $search_tag_filters = self::searchFilterTagIDs($search_filters, true);
            if (!empty($search_tag_filters)) {
                foreach ($search_tag_filters as $search_filter_tag_ids) {
                    $search_filter_event_ids = $traversal->eventIDsLinkedToObjectiveIDs($search_filter_tag_ids, false, $traversal_filters);
                    if (!empty($search_filter_event_ids)) {
                        $where .= " AND `events`.`event_id` IN (".implode(", ", $search_filter_event_ids).")";
                    } else {
                        $where .= " AND NOT TRUE";
                    }
                }
            }
            $non_tag_filters = self::searchFilterTagIDs($search_filters, false);
            if (!empty($non_tag_filters)) {
                foreach ($non_tag_filters as $non_filter_tag_ids) {
                    $non_filter_event_ids = $traversal->eventIDsLinkedToObjectiveIDs($non_filter_tag_ids, false, $traversal_filters);
                    if (!empty($non_filter_event_ids)) {
                        $where .= " AND `events`.`event_id` NOT IN (".implode(", ", $non_filter_event_ids).")";
                    }
                }
            }
        }

        if (!empty($filter_tag_ids)) {
            foreach ($filter_tag_ids as $filter_tag_id) {
                $tag_filtered_event_ids = $traversal->eventIDsLinkedToObjectiveIDs(array($filter_tag_id), false, $traversal_filters);
                if (!empty($tag_filtered_event_ids)) {
                    $where .= " AND `events`.`event_id` IN (".implode(", ", $tag_filtered_event_ids).")";
                } else {
                    $where .= " AND NOT TRUE";
                }
            }
        }

        if ($search_term) {
            $search_term = self::parseBooleans($search_term);

            $event_ids_by_rank = self::eventsByRankThroughTagLinks($search_term, $traversal_filters);
            $tag_link_count_query = self::tagLinkQuery(null, $counter_select, $event_ids_by_rank, $from, $where);
            $tag_link_search_query = self::tagLinkQuery($search_select, null, $event_ids_by_rank, $from, $where);

            $count_query = <<<COUNTQUERY1
SELECT COUNT(*) AS `total_rows`
FROM
(
    -- Count Learning Events

    {$counter_select}
    {$from}
    {$where}
    AND MATCH (`events`.`event_title`, `events`.`event_description`, `events`.`event_goals`, `events`.`event_objectives`, `events`.`event_message`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    GROUP BY `events`.`event_id`

    UNION

    -- Count Learning Event Sessional Free-Text Objectives

    {$counter_select}
    {$from}
    JOIN `event_objectives`
    ON `event_objectives`.`event_id` = `events`.`event_id`
    {$where}
    AND MATCH (`event_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    GROUP BY `events`.`event_id`

    UNION

    -- Count Course Modified Selected Curriculum Tags

    {$counter_select}
    {$from}
    JOIN `event_objectives`
    ON `event_objectives`.`event_id` = `events`.`event_id`
    JOIN `course_objectives`
    ON `course_objectives`.`objective_id` = `event_objectives`.`objective_id`
    AND `course_objectives`.`course_id` = `events`.`course_id`
    AND `course_objectives`.`active` = 1
    {$where}
    AND MATCH (`course_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    GROUP BY `events`.`event_id`

    UNION

    -- Count Selected Curriculum Tags

    {$counter_select}
    {$from}
    JOIN `event_objectives`
    ON `event_objectives`.`event_id` = `events`.`event_id`
    JOIN `global_lu_objectives`
    ON `global_lu_objectives`.`objective_id` = `event_objectives`.`objective_id`
    {$where}
    AND MATCH (`global_lu_objectives`.`objective_code`, `global_lu_objectives`.`objective_name`, `global_lu_objectives`.`objective_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    GROUP BY `events`.`event_id`

    {$tag_link_count_query}

    UNION

    -- Search Course Units

    {$counter_select}
    {$from}
    JOIN `course_units`
    ON `course_units`.`cunit_id` = `events`.`cunit_id`
    {$where}
    AND MATCH (`course_units`.`unit_code`, `course_units`.`unit_title`, `course_units`.`unit_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    GROUP BY `events`.`event_id`
COUNTQUERY1;

            list($sort_column, $sort_direction) = explode('-', $sort_order);

            switch($sort_column) {
                case 'course':
                    $order_by = "`course_code` $sort_direction, `course_name` $sort_direction";
                    break;
                case 'event':
                    $order_by = "`event_title` $sort_direction";
                    break;
                case 'date':
                    $order_by = "`event_start` $sort_direction";
                    break;
                case 'duration':
                    $order_by = "`event_duration` $sort_direction";
                    break;
                case 'description':
                    $order_by = "`event_description` $sort_direction";
                    break;
                case 'unit':
                    $order_by = "`cunit_id` $sort_direction";
                    break;
                default:
                    $order_by = "`rank` DESC, `event_start` DESC";
            }

            if (defined("SEARCH_FILE_CONTENTS") && SEARCH_FILE_CONTENTS && isset($_GET["search-in-files"])) {
                $count_query .= <<<COUNTQUERY2
            
    UNION

    -- Count Selected File Contents Curriculum Tags

    {$counter_select}
    {$from}
    JOIN `event_files`
    ON `event_files`.`event_id` = `events`.`event_id`
    {$where}
    AND (MATCH (`event_files`.`file_contents`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    OR MATCH (`event_files`.`file_name`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
    OR MATCH (`event_files`.`file_notes`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE))
    GROUP BY `events`.`event_id`
COUNTQUERY2;
}
            $count_query .= ") AS t";

            $search_query = <<<SEARCHQUERY1
-- Search Learning Events

{$search_select}
MATCH (`events`.`event_title`, `events`.`event_description`, `events`.`event_goals`, `events`.`event_objectives`, `events`.`event_message`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
{$where}
AND MATCH (`events`.`event_title`, `events`.`event_description`, `events`.`event_goals`, `events`.`event_objectives`, `events`.`event_message`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
GROUP BY `events`.`event_id`

UNION

-- Search Learning Event Sessional Free-Text Objectives

{$search_select}
MATCH (`event_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
JOIN `event_objectives`
ON `event_objectives`.`event_id` = `events`.`event_id`
{$where}
AND MATCH (`event_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
GROUP BY `events`.`event_id`

UNION

-- Search Course Modified Selected Curriculum Tags

{$search_select}
MATCH (`course_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
JOIN `event_objectives`
ON `event_objectives`.`event_id` = `events`.`event_id`
JOIN `course_objectives`
ON `course_objectives`.`objective_id` = `event_objectives`.`objective_id`
AND `course_objectives`.`course_id` = `events`.`course_id`
AND `course_objectives`.`active` = 1
{$where}
AND MATCH (`course_objectives`.`objective_details`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
GROUP BY `events`.`event_id`

UNION

-- Search Selected Curriculum Tags

{$search_select}
MATCH (`global_lu_objectives`.`objective_code`, `global_lu_objectives`.`objective_name`, `global_lu_objectives`.`objective_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
JOIN `event_objectives`
ON `event_objectives`.`event_id` = `events`.`event_id`
JOIN `global_lu_objectives`
ON `global_lu_objectives`.`objective_id` = `event_objectives`.`objective_id`
{$where}
AND MATCH (`global_lu_objectives`.`objective_code`, `global_lu_objectives`.`objective_name`, `global_lu_objectives`.`objective_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
GROUP BY `events`.`event_id`

{$tag_link_search_query}

UNION

-- Search Course Units

{$search_select}
MATCH (`course_units`.`unit_code`, `course_units`.`unit_title`, `course_units`.`unit_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
JOIN `course_units`
ON `course_units`.`cunit_id` = `events`.`cunit_id`
{$where}
AND MATCH (`course_units`.`unit_code`, `course_units`.`unit_title`, `course_units`.`unit_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
GROUP BY `events`.`event_id`
SEARCHQUERY1;
            if (defined("SEARCH_FILE_CONTENTS") && SEARCH_FILE_CONTENTS && isset($_GET["search-in-files"])) {
                $search_query .= <<<SEARCHQUERY2
            
UNION

-- Selected File Contents Curriculum Tags

{$search_select}
MATCH (`event_files`.`file_contents`, `event_files`.`file_name`, `event_files`.`file_notes`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
{$from}
JOIN `event_files`
ON `event_files`.`event_id` = `events`.`event_id`
{$where}
AND (MATCH (`event_files`.`file_contents`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
OR MATCH (`event_files`.`file_name`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
OR MATCH (`event_files`.`file_notes`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE))
GROUP BY `events`.`event_id`
SEARCHQUERY2;
            }
            $search_query .= " ORDER BY `rank` DESC, `event_start` DESC";

            return array (
                "counter" => $count_query,
                "search" => $search_query . ($add_limit_param ? " LIMIT %s, %s" : "")
            );
        } else if (!empty($filter_tag_ids) || !empty($search_filters) || !empty($search_course) || !empty($search_unit)) {
            $count_query = <<<COUNTQUERY
SELECT COUNT(*) AS `total_rows`
FROM
(
    -- Count Learning Events
    {$counter_select}
    {$from}
    {$where}
    GROUP BY `events`.`event_id`
) AS t
COUNTQUERY;

            $search_query = <<<SEARCHQUERY
-- Search Learning Events
{$search_select} 1 AS `rank`
{$from}
{$where}
GROUP BY `events`.`event_id`
ORDER BY `rank` DESC, `event_start` DESC
SEARCHQUERY;

            return array (
                "counter" => $count_query,
                "search" => $search_query . ($add_limit_param ? " LIMIT %s, %s" : "")
            );
        }

        return false;
    }

    private static function tagLinkQuery($search_select, $counter_select, array $event_ids_by_rank, $from, $where) {
        global $db;
        if (!$search_select && !$counter_select) {
            throw new InvalidArgumentException();
        }
        $queries = array();
        foreach ($event_ids_by_rank as $rank => $event_ids) {
            if ($search_select) {
                $select = $search_select . " {$rank} AS rank";
            } else if ($counter_select) {
                $select = $counter_select;
            } else {
                throw new InvalidArgumentException();
            }
            if (!empty($event_ids)) {
                $event_list_sql = implode(", ", $event_ids);
                $queries[] = <<<LINKSEARCHQUERY
-- Search Selected Curriculum Tag Links (Rank {$rank})

{$select}
{$from}
{$where}
AND `events`.`event_id` IN ({$event_list_sql})
GROUP BY `events`.`event_id`
LINKSEARCHQUERY;
            }
        }
        if (!empty($queries)) {
            return "UNION\n\n" . implode("\n\nUNION\n\n", $queries);
        } else {
            return "";
        }
    }

    private static function eventsByRankThroughTagLinks($search_term, array $traversal_filters) {
        global $db;

        $query_objectives = "SELECT `global_lu_objectives`.`objective_id`,
                             MATCH (`global_lu_objectives`.`objective_code`, `global_lu_objectives`.`objective_name`, `global_lu_objectives`.`objective_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE) AS `rank`
                             FROM `global_lu_objectives`
                             WHERE MATCH (`global_lu_objectives`.`objective_code`, `global_lu_objectives`.`objective_name`, `global_lu_objectives`.`objective_description`) AGAINST ({$db->qstr($search_term)} IN BOOLEAN MODE)
                             GROUP BY `global_lu_objectives`.`objective_id`";

        $results_objectives = $db->GetAll($query_objectives);
        $objectives_objects = array();

        foreach ($results_objectives as $results_objective) {
            $objective = new Models_Objective();
            $objective->fromArray($results_objective);
            $objectives_objects[$objective->getID()] = $objective;
        }

        $objective_repository = Models_Repository_Objectives::getInstance();
        $objectives = $objective_repository->searchExcludeByTagSetIDs($objectives_objects, $objective_repository->getSearchExcludedTagSetIds());
        $event_ids_by_rank = array();

        if ($objectives) {
            $objective_ids_by_rank = array();
            foreach ($objectives as $objective) {
                $rank = $objective->rank;
                $objective_id = $objective->getID();
                $objective_ids_by_rank[$rank][$objective_id] = $objective_id;
            }
            $traversal = Entrada_Curriculum_Traversal::getInstance();
            foreach ($objective_ids_by_rank as $rank => $objective_ids) {
                $event_ids_by_rank[$rank] = $traversal->eventIDsLinkedToObjectiveIDs($objective_ids, true, $traversal_filters);
            }
        }

        return $event_ids_by_rank;
    }

    private static function searchFilterWhereSql(array $search_filters)
    {
        $where = "";
        $event_fields = array("event_title", "event_description");
        foreach ($search_filters as $search_filter_field => $search_filter) {
            foreach ($search_filter as $search_filter_operator => $search_filter_values) {
                foreach ($search_filter_values as $search_filter_value) {
                    if (in_array($search_filter_field, $event_fields)) {
                        $where .= " AND " . self::searchFilterCompareSql("`events`.`{$search_filter_field}`", $search_filter_operator, $search_filter_value);
                    } else if (substr($search_filter_field, 0, 8) != "tag_set-") {
                        switch ($search_filter_field) {
                        case "event_types":
                            $my_from = "FROM `event_eventtypes` INNER JOIN `events_lu_eventtypes` ON `events_lu_eventtypes`.`eventtype_id` = `event_eventtypes`.`eventtype_id`";
                            $my_where = "WHERE `event_eventtypes`.`event_id` = `events`.`event_id`";
                            $my_field = "`events_lu_eventtypes`.`eventtype_title`";
                            $where .= " AND " . self::searchFilterExistenceSql($my_from, $my_where, $my_field, $search_filter_operator, $search_filter_value);
                            break;
                        case "teachers":
                            $my_from = "FROM `event_contacts` INNER JOIN `".AUTH_DATABASE."`.`user_data` ON `user_data`.`id` = `event_contacts`.`proxy_id`";
                            $my_where = "WHERE `event_contacts`.`event_id` = `events`.`event_id` AND `event_contacts`.`contact_role` = 'teacher'";
                            $my_field = "CONCAT(`user_data`.`firstname`, ' ', `user_data`.`lastname`)";
                            $where .= " AND " . self::searchFilterExistenceSql($my_from, $my_where, $my_field, $search_filter_operator, $search_filter_value);
                            break;
                        case "course":
                            $where .= " AND " . self::filterCourses($search_filter_operator, $search_filter_value);
                            break;
                        case "course_unit":
                            $where .= " AND " . self::filterCourseUnits($search_filter_operator, $search_filter_value);
                            break;
                        default:
                            throw new InvalidArgumentException(sprintf("Invalid search filter \"%s\"", $search_filter_field));
                            break;
                        }
                    }
                }
            }
        }
        return $where;
    }

    private static function filterCourses($search_filter_operator, $search_filter_value) {
        global $db;
        list($nonexistence, $operator) = self::nonExistenceOperator($search_filter_operator);
        $field = "COALESCE(CONCAT(`courses`.`course_code`, ' - ', `courses`.`course_name`), `courses`.`course_name`)";
        $query = "
            SELECT `course_id`
            FROM `courses`
            WHERE (
                " . self::searchFilterCompareSql($field, $operator, $search_filter_value) . " OR
                " . self::searchFilterCompareSql("`courses`.`course_code`", $operator, $search_filter_value) . " OR
                " . self::searchFilterCompareSql("`courses`.`course_name`", $operator, $search_filter_value) . "
            )
            AND `course_active` = 1";
        if (($results = $db->GetAll($query))) {
            $courses_sql = implode(", ", array_map(function ($result) { return $result["course_id"]; }, $results));
            if ($nonexistence) {
                return "`events`.`course_id` NOT IN (" . $courses_sql . ")";
            } else {
                return "`events`.`course_id` IN (" . $courses_sql . ")";
            }
        } else {
            if ($nonexistence) {
                return "TRUE";
            } else {
                return "FALSE";
            }
        }
    }

    private static function filterCourseUnits($search_filter_operator, $search_filter_value) {
        global $db;
        list($nonexistence, $operator) = self::nonExistenceOperator($search_filter_operator);
        $field = "COALESCE(CONCAT(`course_units`.`unit_code`, ': ', `course_units`.`unit_title`), `course_units`.`unit_title`)";
        $query = "
            SELECT `cunit_id`
            FROM `course_units`
            WHERE (
                " . self::searchFilterCompareSql($field, $operator, $search_filter_value) . " OR
                " . self::searchFilterCompareSql("`course_units`.`unit_code`", $operator, $search_filter_value) . " OR
                " . self::searchFilterCompareSql("`course_units`.`unit_title`", $operator, $search_filter_value) . "
            )
            AND `deleted_date` IS NULL";
        if (($results = $db->GetAll($query))) {
            $courses_sql = implode(", ", array_map(function ($result) { return $result["cunit_id"]; }, $results));
            if ($nonexistence) {
                return "`events`.`cunit_id` NOT IN (" . $courses_sql . ")";
            } else {
                return "`events`.`cunit_id` IN (" . $courses_sql . ")";
            }
        } else {
            if ($nonexistence) {
                return "TRUE";
            } else {
                return "FALSE";
            }
        }
    }

    private static function searchFilterTagIDs(array $search_filters, $existence) {
        global $db;
        $tag_set_ids = array();
        foreach ($search_filters as $search_filter_field => $search_filter) {
            if (preg_match("/^tag_set-(\d+)$/", $search_filter_field, $match)) {
                $tag_set_id = (int) $match[1];
                foreach ($search_filter as $search_filter_operator => $search_filter_values) {
                    list($nonexistence, $operator) = self::nonExistenceOperator($search_filter_operator);
                    if ($existence) {
                        if (!$nonexistence) {
                            $find_tag_ids = true;
                        } else {
                            $find_tag_ids = false;
                        }
                    } else {
                        if ($nonexistence) {
                            $find_tag_ids = true;
                        } else {
                            $find_tag_ids = false;
                        }
                    }
                    if ($find_tag_ids) {
                        foreach ($search_filter_values as $search_filter_value) {
                            $query = "
                                SELECT objective_id
                                FROM `global_lu_objectives`
                                WHERE `global_lu_objectives`.`objective_parent` = " . $db->qstr($tag_set_id) . "
                                AND (" . self::searchFilterCompareSql("`global_lu_objectives`.`objective_name`", $operator, $search_filter_value) . "
                                OR " . self::searchFilterCompareSql("`global_lu_objectives`.`objective_description`", $operator, $search_filter_value) . "
                                OR " . self::searchFilterCompareSql("`global_lu_objectives`.`objective_code`", $operator, $search_filter_value) . ")";
                            $results = $db->GetAll($query);
                            if (!empty($results)) {
                                $tag_set_ids[] = array_map(function ($result) { return $result["objective_id"]; }, $results);
                            } else {
                                $tag_set_ids[] = array();
                            }
                        }
                    }
                }
            }
        }
        return array_unique($tag_set_ids);
    }

    private static function searchFilterExistenceSql($from, $where, $field, $search_filter_operator, $search_filter_value) {
        list($nonexistence, $operator) = self::nonExistenceOperator($search_filter_operator);
        $existence_sql = "EXISTS (
            SELECT 1
            {$from}
            {$where}
            AND " . self::searchFilterCompareSql($field, $operator, $search_filter_value) . "
        )";
        if ($nonexistence) {
            return "NOT " . $existence_sql;
        } else {
            return $existence_sql;
        }
    }

    private static function searchFilterCompareSql($field, $search_filter_operator, $search_filter_value) {
        global $db;
        switch ($search_filter_operator) {
        case "is":
            return "{$field} = {$db->qstr($search_filter_value)} ";
            break;
        case "is_not":
            return "{$field} <> {$db->qstr($search_filter_value)} ";
            break;
        case "contains":
            return "{$field} LIKE " . $db->qstr("%%".$search_filter_value."%%") . " ";
            break;
        case "not_contains":
            return "{$field} NOT LIKE " . $db->qstr("%%".$search_filter_value."%%") . " ";
            break;
        case "starts_with":
            return "{$field} LIKE " . $db->qstr($search_filter_value."%%") . " ";
            break;
        case "not_starts_with":
            return "{$field} NOT LIKE " . $db->qstr($search_filter_value."%%") . " ";
            break;
        case "ends_with":
            return "{$field} LIKE " . $db->qstr("%%".$search_filter_value) . " ";
            break;
        case "not_ends_with":
            return "{$field} NOT LIKE " . $db->qstr("%%".$search_filter_value) . " ";
            break;
        default:
            throw new InvalidArgumentException();
        }
    }

    private static function nonExistenceOperator($search_filter_operator) {
        if (strpos($search_filter_operator, "not_") === 0) {
            $nonexistence = true;
            $operator = substr($search_filter_operator, 4);
        } else if (strpos($search_filter_operator, "_not") === strlen($search_filter_operator) - 4) {
            $nonexistence = true;
            $operator = substr($search_filter_operator, 0, -4);
        } else {
            $nonexistence = false;
            $operator = $search_filter_operator;
        }
        return array($nonexistence, $operator);
    }

    /**
     * Parses boolean operators in the search term
     * @param string $search_term
     * @return string
     */
    private static function parseBooleans($search_term)
    {
        $offset = 0;
        $tokens = array();
        $token_regex = "/^\s*(\(|\)|\-|\bOR\b|\"[^\"]+\"|[^\s,.;\(\)]+|[^\s])\s*/";
        while (preg_match($token_regex, substr($search_term, $offset), $match, PREG_OFFSET_CAPTURE)) {
            list($token, $match_offset) = $match[1];
            $offset = $offset + $match_offset + strlen($token);
            $tokens[] = $token;
        }
        $last_token_index = array();
        $previous_token_index = null;
        $prepend = function ($str, $with) {
            if (strncmp($str, $with, strlen($with)) != 0) {
                return $with . $str;
            } else {
                return $str;
            }
        };
        foreach ($tokens as $i => $token) {
            switch ($token) {
            case ",":
            case ";":
            case ".":
                unset($tokens[$i]);
                break;
            case "(":
                array_push($last_token_index, $i);
                $previous_token_index = null;
                break;
            case ")":
                $previous_token_index = array_pop($last_token_index);
                break;
            case "OR":
            case "or":
                $previous_token_index = null;
                unset($tokens[$i]);
                break;
            case "-":
                $next_token_index = $i + 1;
                if (isset($tokens[$next_token_index])) {
                    $tokens[$next_token_index] = $prepend($tokens[$next_token_index], "-");
                }
                $previous_token_index = null;
                unset($tokens[$i]);
                break;
            default:
                if (isset($tokens[$previous_token_index])) {
                    $tokens[$previous_token_index] = $prepend($tokens[$previous_token_index], "+");
                    $current_token_index = $i;
                    if (isset($tokens[$current_token_index])) {
                        $tokens[$current_token_index] = $prepend($tokens[$current_token_index], "+");
                    }
                }
                $previous_token_index = $i;
                break;
            }
        }
        return str_replace("%", "%%", implode(" ", $tokens));
    }
}
