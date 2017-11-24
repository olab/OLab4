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
     * @return array|bool
     */
    public static function prepare($search_term = "", $search_org = 0, $search_cohort = 0, $search_academic_year = 0, $only_active = true, $add_limit_param = true) {
        global $db;

        $search_org = (int) $search_org;
        $search_cohort = (int) $search_cohort;
        $search_academic_year = (int) $search_academic_year;
        
        if ($only_active !== true) {
            $only_active = false;
        }

        if ($add_limit_param !== true) {
            $add_limit_param = false;
        }

        if ($search_term) {
            $search_term = self::parseBooleans($search_term);

            $counter_select = "SELECT DISTINCT(`events`.`event_id`)";

            $search_select  = "SELECT `events`.*, `event_audience`.`audience_type`, COALESCE(`course_audience`.`audience_value`, `event_audience`.`audience_value`) AS `event_cohort`, ";

            $from = "FROM `events`
                    JOIN `courses` ON `events`.`course_id` = `courses`.`course_id`
                    " . ($only_active ? " AND `courses`.`course_active` = 1" : "") . "
                    " . ($search_org ? " AND `courses`.`organisation_id` = " . (int) $search_org : "") . "
                    LEFT JOIN `event_audience` ON `event_audience`.`event_id` = `events`.`event_id`
                    LEFT JOIN `course_audience` ON `course_audience`.`course_id` = `courses`.`course_id`
                    AND `event_audience`.`audience_type` = 'course_id'
                    AND `course_audience`.`course_id` = `event_audience`.`audience_value`
                    LEFT JOIN `curriculum_periods` ON `curriculum_periods`.`cperiod_id` = `course_audience`.`cperiod_id`";

            $where = "WHERE (`events`.`parent_id` IS NULL OR `events`.`parent_id` = '0')";

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
            }

            $count_query = <<<COUNTQUERY
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
) AS t
COUNTQUERY;

            $search_query = <<<SEARCHQUERY
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

ORDER BY `rank` DESC, `event_start` DESC
SEARCHQUERY;

            return array (
                "counter" => $count_query,
                "search" => $search_query . ($add_limit_param ? " LIMIT %s, %s" : "")
            );
        }

        return false;
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
