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
 * This is an implementation of the CBME_Datasource interface.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Entrada_CBME_Datasource_Progress extends Entrada_CBME_Base implements Entrada_CBME_Datasource {

    /**
     * This function fetches a count of completed assessment tasks linked to EPAs
     * @param int $proxy_id
     * @param int $course_id
     * @param int $objective_set_id
     * @param string $objective_code
     * @return array
     */
    public function fetchEPAProgress ($proxy_id = 0, $course_id = 0, $objective_set_id = 0, $objective_code = "") {
        global $db;
        $query = "  SELECT a.`shortname`, b.`objective_id`, b.`objective_code`, COUNT(f.`aprogress_id`) AS `assessment_count`, b.`objective_name`, b.`objective_description`, b.`objective_secondary_description`,
                    (
                      SELECT count(*)
                      FROM `cbl_learner_objectives_completion`
                      WHERE `objective_id` = b.`objective_id`
                      AND `course_id` = ?
                      AND `proxy_id` = ?
                      AND `deleted_date` IS NULL
                    ) AS `completed`
                    FROM `global_lu_objective_sets` AS a
                    LEFT JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    LEFT JOIN `cbl_assessment_form_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    LEFT JOIN `cbl_distribution_assessments` AS d
                    ON c.`form_id` = d.`form_id`
                    LEFT JOIN `cbl_distribution_assessment_targets` AS e
                    ON d.`dassessment_id` = e.`dassessment_id`
                    AND e.`target_type` = 'proxy_id'
                    AND e.`target_value` = ?
                    LEFT JOIN `cbl_assessment_progress` AS f
                    ON d.`dassessment_id` = f.`dassessment_id`
                    AND f.`progress_value` = 'complete'
                    AND f.`target_record_id` = ?
                    AND NOT (f.`assessor_type` = 'internal' AND f.`assessor_value` = e.`target_value`)
                    JOIN `cbme_course_objectives` AS g
                    ON b.`objective_id` = g.`objective_id`
                    WHERE a.`objective_set_id` = ?
                    AND b.`objective_code` LIKE CONCAT(?, '%')
                    AND g.`course_id` = ?
                    AND d.`deleted_date` IS NULL
                    GROUP BY b.`objective_id`
                    ORDER BY LENGTH(b.`objective_code`), b.`objective_code`";
        $results = $db->GetAll($query, array($course_id, $proxy_id, $proxy_id, $proxy_id, $objective_set_id, $objective_code, $course_id));
        return $results;
    }

    /**
     * This function fetches CBME assessments for a user
     * @param int $organisation_id
     * @param int $proxy_id
     * @param int $course_id
     * @param array $filters
     * @param bool $count_flag // returns a count of filtered assessments if set to true, otherwise it returns all filtered assessments
     * @param bool $apply_filter_flag // toggles assessment filtering
     * @param bool $apply_limit_flag
     * @param int $query_limit
     * @param int $query_offset
     * @param bool $pinned_only
     * @param bool $assessments_with_pinned_comments
     * @param bool $include_comments
     * @param array $progress_type
     * @return mixed
     */
    public function fetchCBMEAssessments($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = false, $apply_filter_flag = true, $apply_limit_flag = true, $query_limit = 24, $query_offset = 0, $pinned_only = false, $secondary_proxy_id = 0, $assessments_with_pinned_comments = false, $include_comments = false, $progress_type = "complete") {
        global $db;
        $secondary_proxy_id = clean_input($secondary_proxy_id, "int");
        $progress_type = clean_input($progress_type, array("striptags", "trim"));
        $params = array($secondary_proxy_id, $secondary_proxy_id, $course_id, $organisation_id, $proxy_id, $organisation_id, $organisation_id);
        $query = "  SELECT " . ($count_flag ? "COUNT(DISTINCT a.`dassessment_id`) AS `assessment_count`," : "a.`dassessment_id`,") . " 
                        a.`updated_date`, 
                        d.`atarget_id`, 
                        e.`aprogress_id`, e.`assessor_value`, e.`created_date`, 
                        f.`form_id`, f.`title` AS `title`, 
                        g.`title` AS `form_type`, g.`shortname`, 
                        pins.`pin_id`, pins.`deleted_date`,
                        e.`progress_value`,  amgm.`title` as `assessment_method`, e.`created_by`, a.`created_date` as 'assessment_created_date',
                        d.`deleted_reason_notes`, tdr.`reason_details`, d.`deleted_date` as `target_deleted_date`, d.`deleted_by`, ua.`group`, 
                        is_read.`read_id`, is_read.`deleted_date`, is_read.`created_by` as read_created_by , a.`encounter_date`, is_liked.`like_id`, is_liked.`comment`
                        
                    FROM `cbl_distribution_assessments` AS a
                    JOIN `cbl_assessment_lu_types` AS b
                    ON a.`assessment_type_id` = b.`assessment_type_id`
                    JOIN `cbl_assessment_type_organisations` AS c
                    ON b.`assessment_type_id` = c.`assessment_type_id`
                    JOIN `cbl_distribution_assessment_targets` AS d FORCE INDEX(dassessment_id_2)
                    ON a.`dassessment_id` = d.`dassessment_id`
                    LEFT JOIN `cbl_assessment_progress` AS e
                    ON a.`dassessment_id` = e.`dassessment_id`
                    JOIN `cbl_assessments_lu_forms` AS f
                    ON a.`form_id` = f.`form_id`
                    JOIN `cbl_assessments_lu_form_types` AS g
                    ON f.`form_type_id` = g.`form_type_id`
                    LEFT JOIN `cbl_pins` AS pins
                    ON pins.`pin_value` = a.`dassessment_id`
                    AND pins.`aprogress_id` = e.`aprogress_id`
                    AND pins.`deleted_date` IS NULL
                    LEFT JOIN `cbl_read` AS is_read
                    ON is_read.`read_value` = a.`dassessment_id`
                    AND is_read.`deleted_date` IS NULL
                    AND is_read.`aprogress_id` = e.`aprogress_id`
                    AND is_read.`created_by` = ?
                    LEFT JOIN `cbl_likes` AS is_liked FORCE INDEX (aprogress_id)
                    ON is_liked.`like_value` = a.`dassessment_id`
                    AND is_liked.`deleted_date` IS NULL
                    AND is_liked.`aprogress_id` = e.`aprogress_id`
                    AND is_liked.`created_by` = ?
                    JOIN `cbl_assessment_method_group_meta` as amgm FORCE INDEX(amethod_group_id)
                    ON a.`assessment_method_id` = amgm.`assessment_method_id`
                    LEFT JOIN `cbl_assessment_lu_task_deleted_reasons` as tdr
                    ON d.`deleted_reason_id` = tdr.`reason_id`
                    JOIN `". AUTH_DATABASE ."`.`user_access` as ua
                    ON ua.`user_id` = a.`created_by`";

        if ($assessments_with_pinned_comments) {
            $query .= " JOIN `cbl_pins` AS comment_pins
                        ON e.`aprogress_id` = comment_pins.`aprogress_id`
                        AND comment_pins.`pin_type` = 'comment'
                        AND comment_pins.`deleted_date` IS NULL";
        }

        if ($include_comments) {
            $query .= " JOIN `cbl_assessment_progress_responses` as progress_responses
                        ON e.`aprogress_id` = progress_responses.`aprogress_id`";
        }

        if ($apply_filter_flag) {
            if (array_key_exists("epas", $filters)) {
                $query .= " JOIN `cbl_assessment_form_objectives` AS h FORCE INDEX(form_id)
                            ON f.`form_id` = h.`form_id`";
            }

            if (array_key_exists("rating_scale_id", $filters)) {
                $query .= " JOIN `cbl_assessment_form_elements` AS i
                            ON f.`form_id` = i.`form_id`
                            JOIN `cbl_assessments_lu_items` AS j FORCE INDEX(PRIMARY)
                            ON i.`element_id` = j.`item_id`
                            AND i.`element_type` = 'item'";

                if (array_key_exists("descriptors", $filters)) {
                    $query .= " JOIN `cbl_assessment_progress_responses` AS k
                                ON e.`aprogress_id` = k.`aprogress_id`
                                JOIN `cbl_assessments_lu_item_responses` AS l
                                ON k.`iresponse_id` = l.`iresponse_id`
                                AND l.`item_id` = j.`item_id`";
                }
            }

            if (array_key_exists("search_term", $filters)) {
                $query .= " JOIN `cbl_assessment_progress_responses` AS m
                        ON e.`aprogress_id` = m.`aprogress_id`";

                $query .= " JOIN `". AUTH_DATABASE ."`.`user_data` AS n
                        ON e.`assessor_value` = n.`id`";
            }

            if (array_key_exists("role_epas", $filters)) {
                $query .= " JOIN `cbl_assessment_form_objectives` AS o
                        ON f.`form_id` = o.`form_id`";
            }

            if (array_key_exists("selected_milestones", $filters)) {
                $query .= " JOIN `cbl_assessment_form_elements` AS p
                            ON f.`form_id` = p.`form_id`
                            JOIN `cbl_assessments_lu_items` AS q FORCE INDEX(PRIMARY)
                            ON p.`element_id` = q.`item_id`
                            AND p.`element_type` = 'item'
                            JOIN `cbl_assessment_item_objectives` AS r
                            ON q.`item_id` = r.`item_id`
                            JOIN `global_lu_objectives` AS s
                            ON r.`objective_id` = s.`objective_id`
                            JOIN `global_lu_objective_sets` AS t
                            ON s.`objective_set_id` = t.`objective_set_id`";
            }

            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " JOIN `cbl_assessment_form_elements` AS u
                            ON f.`form_id` = u.`form_id`
                            JOIN `cbl_assessments_lu_items` AS v FORCE INDEX(PRIMARY)
                            ON u.`element_id` = v.`item_id`
                            AND u.`element_type` = 'item'
                            JOIN `cbl_assessment_item_objectives` as io
                            ON v.`item_id` = io.`item_id`";

                if (array_key_exists("contextual_variable_responses", $filters)) {
                    $query .= " JOIN `cbl_assessments_lu_item_responses` AS ir
                                ON io.`item_id` = ir.`item_id`
                                JOIN `cbl_assessment_progress_responses` AS pr
                                ON e.`aprogress_id` = pr.`aprogress_id`
                                AND pr.`iresponse_id` = ir.`iresponse_id`
                                JOIN `cbl_assessments_lu_item_response_objectives` AS iro
                                ON pr.`iresponse_id` = iro.`iresponse_id`";

                }
            }
        }

        if ($pinned_only) {
            $query .= " JOIN `cbl_pins` as pin
                        ON pin.`aprogress_id` = e.`aprogress_id`";
        }

        $query .= " WHERE a.`course_id` = ?
                    AND c.`organisation_id` = ?
                    AND a.`assessor_type` = 'internal'
                    AND d.`target_type` = 'proxy_id'
                    AND d.`target_value` = ?
                    AND f.`form_type_id` NOT IN (
                        SELECT `form_type_id`
                        FROM `cbl_assessment_form_type_meta`
                        WHERE `organisation_id` = ?
                        AND `meta_name` = 'hide_from_dashboard'
                        AND `meta_value` = 1
                        AND `deleted_date` IS NULL
                    )
                    AND (pins.`pin_type` = 'assessment' OR pins.`pin_type` IS NULL)
                    AND (is_read.`read_type` = 'assessment' OR is_read.`read_type` IS NULL)
                    AND ua.`organisation_id` = ?";

        if ($progress_type == "pending") {
            $query .= " AND e.`aprogress_id` IS NULL
                        AND d.`deleted_reason_id` IS NULL";
        } elseif ($progress_type == "deleted") {
            $params[] = $proxy_id;
            $query .= " AND NOT (a.`assessor_type` = 'internal' AND a.`assessor_value` = d.`target_value`)
                        AND e.`target_type` = 'proxy_id'
                        AND e.`target_record_id` = ? 
                        AND d.`deleted_reason_id` IS NOT NULL";
        } else {
            $params[] = $proxy_id;
            $params[] = $progress_type;
            $query .= " AND NOT (a.`assessor_type` = 'internal' AND a.`assessor_value` = d.`target_value`)
                        AND e.`target_type` = 'proxy_id'
                        AND e.`target_record_id` = ?
                        AND d.`deleted_reason_id` IS NULL
                        AND e.`progress_value` = ?";
        }

        if ($include_comments) {
            $query .= " AND progress_responses.`comments` IS NOT NULL";
        }

        if ($apply_filter_flag) {
            if (array_key_exists("epas", $filters)) {
                $query .= " AND h.`objective_id` IN (". implode(",", $filters["epas"]) .")";
            }

            if (array_key_exists("form_types", $filters)) {
                $query .= " AND g.`form_type_id` IN (". implode(",", $filters["form_types"]) .")
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("rating_scale_id", $filters) && !empty($filters["rating_scale_id"])) {
                $query .= " AND j.`rating_scale_id` IN (". implode(",", $filters["rating_scale_id"]) .")
                            AND j.`deleted_date` IS NULL";
                if (array_key_exists("descriptors", $filters)) {
                    $query .= " AND l.`ardescriptor_id` IN (". implode(",", $filters["descriptors"]) .")
                                AND k.`deleted_date` IS NULL
                                AND l.`deleted_date` IS NULL";
                }
            }

            if (array_key_exists("experience", $filters)) {
                $query .= " AND (";
                foreach($filters["experience"] as $key => $experience) {
                    $query .= " CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE a.`created_date` 
                                END BETWEEN ? AND ? ";
                    $params[] = $experience["start_date"];
                    $params[] = $experience["end_date"];
                    if ($key+1 != count($filters["experience"])) {
                        $query .= " OR ";
                    }
                }
                $query .= ")";
            }

            if (array_key_exists("start_date", $filters) && array_key_exists("finish_date", $filters)) {
                $query .= " AND CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE a.`created_date` 
                                END BETWEEN ? AND ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["start_date"];
                $params[] = $filters["finish_date"];
            } elseif (array_key_exists("start_date", $filters) && !array_key_exists("finish_date", $filters)) {
                $query .= " AND CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE a.`created_date` 
                                END >= ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["start_date"];
            } elseif (!array_key_exists("start_date", $filters) && array_key_exists("finish_date", $filters)) {
                $query .= " AND CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE a.`created_date` 
                                END <= ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["finish_date"];
            }

            if (array_key_exists("role_epas", $filters)) {
                $query .= " AND o.`objective_id` IN (". implode(",", $filters["role_epas"]) .")";
            }

            if (array_key_exists("selected_milestones", $filters)) {
                $query .= " AND r.`objective_id` IN (". implode(",", $filters["selected_milestones"]) .")
                            AND t.`shortname` = 'milestone'
                            AND r.`deleted_date` IS NULL";
            }

            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " AND v.`item_code` = 'CBME_contextual_variables'
                            AND io.`objective_id` IN (". implode(",", $filters["contextual_variables"]) .")
                            AND v.`deleted_date` IS NULL";

                if (array_key_exists("contextual_variable_responses", $filters)) {
                    $query .= " AND iro.`objective_id` IN (". implode(",", $filters["contextual_variable_responses"]) .")
                                AND iro.`deleted_date` IS NULL";
                }
            }

            if (array_key_exists("selected_users", $filters)) {
                $query .= " AND e.`assessor_type` = 'internal'
                            AND e.`assessor_value` IN (". implode(",", $filters["selected_users"]) .")
                            AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("triggered_by", $filters)) {
                if ($filters["triggered_by"] !== "all") {
                    $query .= " AND ua.`group` = ?";
                    $params[] = $filters["triggered_by"];
                }
            }

            if (array_key_exists("search_term", $filters)) {
                $query .= " AND m.`comments` LIKE CONCAT('%', ?, '%')
                            AND m.`deleted_date` IS NULL";
                $params[] = $filters["search_term"];
            }

            if (array_key_exists("other", $filters)) {
                switch ($filters["other"]) {
                    case "read":
                        $query .= " AND (is_read.`updated_date` IS NOT NULL OR is_read.`created_date` IS NOT NULL)
                                    AND is_read.`deleted_date` IS NULL
                                    AND is_read.`created_by` = ? ";
                        $params[] = $secondary_proxy_id;
                    break;
                    case "unread":
                        $query .= " AND (is_read.`read_id` IS NULL OR (is_read.`read_id` IS NOT NULL AND is_read.`created_by` != ? ) 
                                    OR (is_read.`read_id` IS NOT NULL AND is_read.`created_by` = ? AND is_read.`deleted_date` IS NOT NULL))
                                    AND (is_read.`deleted_date` IS NULL)";
                        $params[] = $secondary_proxy_id;
                        $params[] = $secondary_proxy_id;
                    break;
                }
            }
        }

        if ($pinned_only == true) {
            $query .= " AND pin.`pin_type` = 'assessment'
                        AND pin.`deleted_date` IS NULL";
        }

        if (!$count_flag) {
            $query .= " GROUP BY a.`dassessment_id`";
        }

        $sort = "DESC";
        if (array_key_exists("sort", $filters)) {
            $sort = $filters["sort"];
        }

        if ($progress_type == "deleted") {
            $query .= " ORDER BY d.`deleted_date`" . $sort;
        } elseif ($progress_type == "pending") {
            $query .= " ORDER BY CASE 
                                 WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                 ELSE a.`created_date` 
                                 END " . $sort;
        } elseif ($progress_type == "inprogress") {
            $query .= " ORDER BY GREATEST(ifnull(a.`updated_date`,0), ifnull(e.`created_date`,0))" . $sort;
        } else {
            $query .= " ORDER BY CASE 
                                 WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                 ELSE e.`created_date` 
                                 END " . $sort;
        }

        if ($apply_limit_flag) {
            $query .= " LIMIT " . (int) $query_limit;
            $query .= " OFFSET " . (int) $query_offset;
        }
        $results = $db->GetAll($query, $params);
        return $results;
    }

    public function fetchCBMEAssessmentItemsByOrganisationIDProxyID($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = false, $apply_filter_flag = true, $apply_limit_flag = true, $query_limit = 24, $query_offset = 0, $pinned_only = false, $fetching_trend_data = false) {
        global $db;
        $params = array($course_id, $organisation_id, $proxy_id, $proxy_id, $organisation_id, $organisation_id);
        $item_code_black_list = array("CBME_contextual_variables", "CBME_supervisor_form_item", "CBME_fieldnote_form_item", "CBME_procedure_form_item");
        $query = "  SELECT ". ($count_flag ? "COUNT(i.`item_id`) AS item_count," : "i.`item_id`,") ." 
                        a.`dassessment_id`, a.`assessor_value`, a.`assessor_type`, 
                        e.`created_date`, e.`updated_date`, 
                        i.`item_text`, i.`item_description`, i.`rating_scale_id` AS `item_rating_scale_id`, 
                        h.`rubric_id`, 
                        j.`comments`, 
                        k.`order`, k.`text` AS 'item_response_text', 
                        l.`descriptor` AS 'response_descriptor', 
                        m.`rubric_title`, m.`rating_scale_id` AS `rubric_rating_scale_id`, 
                        pins.`pin_id`, e.`aprogress_id`, pins.`deleted_date`, ua.`group`, a.`encounter_date`, 
                        is_read.`read_id`, is_read.`deleted_date`, is_read.`proxy_id`
                    FROM `cbl_distribution_assessments` AS a
                    JOIN `cbl_assessment_lu_types` AS b
                        ON a.`assessment_type_id` = b.`assessment_type_id`
                    JOIN `cbl_assessment_type_organisations` AS c
                        ON b.`assessment_type_id` = c.`assessment_type_id`
                    JOIN `cbl_distribution_assessment_targets` AS d
                        ON a.`dassessment_id` = d.`dassessment_id`
                    JOIN `cbl_assessment_progress` AS e
                        ON a.`dassessment_id` = e.`dassessment_id`
                    JOIN `cbl_assessments_lu_forms` AS f
                        ON a.`form_id` = f.`form_id`
                    JOIN `cbl_assessments_lu_form_types` AS g
                        ON f.`form_type_id` = g.`form_type_id`
                    JOIN `cbl_assessment_form_elements` AS h
                        ON a.`form_id` = h.`form_id`
                        AND h.`element_type` = 'item'
                    JOIN `cbl_assessments_lu_items` AS i FORCE INDEX(PRIMARY)
                        ON h.`element_id` = i.`item_id`
                    JOIN  `cbl_assessment_progress_responses` AS j
                        ON (e.`aprogress_id` = j.`aprogress_id` AND a.`form_id` = j.`form_id` AND h.`afelement_id` = j.`afelement_id`)
                    LEFT JOIN `cbl_assessments_lu_item_responses` AS k
                        ON j.`iresponse_id` = k.`iresponse_id`
                        AND i.`item_id` = k.`item_id`
                    LEFT JOIN `cbl_assessments_lu_response_descriptors` AS l
                        ON k.`ardescriptor_id` = l.`ardescriptor_id`
                    LEFT JOIN `cbl_assessments_lu_rubrics` AS m
                        ON h.`rubric_id` = m.`rubric_id`
                    LEFT JOIN `cbl_pins` AS pins
                        ON pins.`pin_value` = i.`item_id`
                        AND pins.`pin_type` = 'item'
                        AND pins.`aprogress_id` = e.`aprogress_id`
                        AND pins.`deleted_date` IS NULL
                    LEFT JOIN `cbl_read` AS is_read
                        ON is_read.`read_value` = i.`item_id`
                        AND is_read.`read_type` = 'item'
                        AND is_read.`aprogress_id` = e.`aprogress_id`
                        AND is_read.`deleted_date` IS NULL
                    JOIN `". AUTH_DATABASE ."`.`user_access` as ua
                        ON ua.`user_id` = a.`created_by`";

        if ($apply_filter_flag) {
            if (array_key_exists("epas", $filters)) {
                $query .= " JOIN `cbl_assessments_lu_items` AS n
                            ON h.`element_id` = n.`item_id`
                            AND h.`element_type` = 'item'
                            JOIN `cbl_assessment_item_objectives` AS o
                            ON n.`item_id` = o.`item_id`
                            JOIN `global_lu_objectives` AS p
                            ON o.`objective_id` = p.`objective_id`
                            JOIN `global_lu_objective_sets` AS q
                            ON p.`objective_set_id` = q.`objective_set_id`";
            }

            if (array_key_exists("selected_milestones", $filters)) {
                $query .= " JOIN `cbl_assessment_item_objectives` AS s
                            ON i.`item_id` = s.`item_id`
                            JOIN `global_lu_objectives` AS t
                            ON t.`objective_id` = s.`objective_id`
                            JOIN `global_lu_objective_sets` AS u
                            ON t.`objective_set_id` = u.`objective_set_id`";
            }

            if (array_key_exists("role_epas", $filters)) {
                $query .= " JOIN `cbl_assessment_form_objectives` AS v FORCE INDEX(form_id)
                        ON f.`form_id` = v.`form_id`";
            }

            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " JOIN `cbl_assessment_form_elements` AS fe
                            ON f.`form_id` = fe.`form_id`
                            JOIN `cbl_assessments_lu_items` AS it
                            ON fe.`element_id` = it.`item_id`
                            AND fe.`element_type` = 'item'
                            JOIN `global_lu_objectives` AS go
                            ON (it.`item_text` = go.`objective_name`)";

                if (array_key_exists("contextual_variable_responses", $filters)) {
                    $query .= " JOIN `cbl_assessment_progress_responses` AS x
                                ON e.`aprogress_id` = x.`aprogress_id`
                                JOIN `cbl_assessments_lu_item_responses` AS y
                                ON x.`iresponse_id` = y.`iresponse_id`
                                JOIN `global_lu_objectives` AS z
                                ON y.`text` = z.`objective_name`";
                }
            }

            if (array_key_exists("rating_scale_id", $filters) && !empty($filters["rating_scale_id"])) {
                $query .= " JOIN `cbl_assessment_rating_scale_responses` AS sr
                            ON k.`ardescriptor_id` = sr.`ardescriptor_id`";

                if ($fetching_trend_data) {
                    $query .= " JOIN `cbl_assessment_rating_scale` AS ars
                                ON i.`rating_scale_id` = ars.`rating_scale_id`
                                JOIN `cbl_assessments_lu_rating_scale_types` AS arst
                                ON ars.`rating_scale_type` = arst.`rating_scale_type_id`";
                }

            }
        }
        if ($pinned_only) {
            $query .= " JOIN `cbl_pins` as pin
                        ON pin.`aprogress_id` = e.`aprogress_id`";
        }

        $query .= " WHERE a.`course_id` = ?
                    AND c.`organisation_id` = ?
                    AND d.`target_type` = 'proxy_id'
                    AND d.`target_value` = ?
                    AND e.`progress_value` = 'complete'
                    AND e.`target_type` = 'proxy_id'
                    AND e.`target_record_id` = ?
                    AND NOT (e.`assessor_type` = 'internal' AND e.`assessor_value` = d.`target_value`)
                    AND f.`form_type_id` NOT IN (
                        SELECT `form_type_id`
                        FROM `cbl_assessment_form_type_meta`
                        WHERE `organisation_id` = ?
                        AND `meta_name` = 'hide_from_dashboard'
                        AND `meta_value` = 1
                        AND `deleted_date` IS NULL
                    )
                    AND h.`deleted_date` IS NULL
                    AND i.`deleted_date` IS NULL
                    AND j.`deleted_date` IS NULL
                    AND m.`deleted_date` IS NULL
                    AND ua.`organisation_id` = ?";
        if ($apply_filter_flag) {
            if (array_key_exists("epas", $filters)) {
                $query .= " AND o.`objective_id` IN (" . implode(",", $filters["epas"]) . ")
                            AND q.`shortname` = 'epa'
                            AND o.`deleted_date` IS NULL
                            AND q.`deleted_date` IS NULL";
            }

            if (array_key_exists("form_types", $filters)) {
                $query .= " AND g.`form_type_id` IN (" . implode(",", $filters["form_types"]) . ")
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("rating_scale_id", $filters) && !empty($filters["rating_scale_id"])) {
                $query .= " AND i.`rating_scale_id` IN (" . implode(",", $filters["rating_scale_id"]) . ")
                            AND i.`deleted_date` IS NULL
                            AND (sr.`weight` IS NULL OR sr.`weight` <> 0)";
                if (array_key_exists("descriptors", $filters)) {
                    $query .= " AND k.`ardescriptor_id` IN (" . implode(",", $filters["descriptors"]) . ")
                                AND k.`deleted_date` IS NULL";
                }

                if ($fetching_trend_data) {
                    $query .= " AND ( g.`shortname` <> 'cbme_ppa_form' 
                                OR ( g.`shortname` = 'cbme_ppa_form' AND arst.`shortname` = 'global_assessment'
                                AND i.`item_code` = 'CBME_ppa_form_entrustment_scale' ))";
                }
            }

            if (array_key_exists("experience", $filters)) {
                $query .= " AND (";
                foreach($filters["experience"] as $key => $experience) {
                    $query .= " CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE a.`created_date` 
                                END BETWEEN ? AND ? ";
                    $params[] = $experience["start_date"];
                    $params[] = $experience["end_date"];
                    if ($key + 1 != count($filters["experience"])) {
                        $query .= " OR ";
                    }
                }
                $query .= ")";
            }

            if (array_key_exists("start_date", $filters) && array_key_exists("finish_date", $filters)) {
                $query .= " AND a.`created_date` BETWEEN ? AND ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["start_date"];
                $params[] = $filters["finish_date"];
            } elseif (array_key_exists("start_date", $filters) && !array_key_exists("finish_date", $filters)) {
                $query .= " AND a.`created_date` >= ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["start_date"];
            } elseif (!array_key_exists("start_date", $filters) && array_key_exists("finish_date", $filters)) {
                $query .= " AND a.`created_date` <= ?
                            AND a.`deleted_date` IS NULL";
                $params[] = $filters["finish_date"];
            }

            if (array_key_exists("role_epas", $filters)) {
                $query .= " AND v.`objective_id` IN (" . implode(",", $filters["role_epas"]) . ")";
            }

            if (array_key_exists("selected_milestones", $filters)) {
                $query .= " AND s.`objective_id` IN (". implode(",", $filters["selected_milestones"]) .")
                            AND u.`shortname` = 'milestone'
                            AND s.`deleted_date` IS NULL
                            AND u.`deleted_date` IS NULL";
            }

            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " AND it.`item_code` = 'CBME_contextual_variables'
                            AND go.`objective_id` IN (". implode(",", $filters["contextual_variables"]) .")
                            AND it.`deleted_date` IS NULL
                            AND go.`objective_active` = 1";
                if(array_key_exists("trends", $filters) && $filters["trends"] == false) {
                    $query .= " AND i.`item_text` = go.`objective_name`";
                }

                if (array_key_exists("contextual_variable_responses", $filters)) {
                    $query .= " AND z.`objective_id`IN (". implode(",", $filters["contextual_variable_responses"]) .")
                                AND z.`objective_active` = 1";
                }
            }

            if (array_key_exists("selected_users", $filters)) {
                $query .= " AND e.`assessor_type` = 'internal'
                            AND e.`assessor_value` IN (". implode(",", $filters["selected_users"]) .")
                            AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("search_term", $filters)) {
                $query .= " AND ((l.`descriptor` LIKE CONCAT('%', ?, '%') AND l.`deleted_date` IS NULL) OR (i.`item_text` LIKE CONCAT('%', ?, '%') AND i.`deleted_date` IS NULL) OR (m.`rubric_title` LIKE CONCAT('%', ?, '%') AND m.`deleted_date` IS NULL))";
                $params[] = $filters["search_term"];
                $params[] = $filters["search_term"];
                $params[] = $filters["search_term"];
            }
            
            if (array_key_exists("triggered_by", $filters)) {
                if ($filters["triggered_by"] !== "all") {
                    $query .= " AND ua.`group` = ?";
                    $params[] = $filters["triggered_by"];
                }
            }

            if (array_key_exists("aprogress_ids", $filters)) {
                if ($fetching_trend_data) {
                    $query .= " AND e.`aprogress_id` IN (". implode(",", $filters["aprogress_ids"]) .")";
                }
            }

            if (array_key_exists("iresponse_ids", $filters)) {
                if ($fetching_trend_data) {
                    $query .= " AND k.`iresponse_id` IN (". implode(",", $filters["iresponse_ids"]) .")";
                }
            }
        }

        $query .= " AND i.`item_code` NOT IN ('". implode("','", $item_code_black_list) ."')";

        if ($pinned_only) {
            $query .= " AND pin.`pin_type` = 'item'
                        AND pin.`pin_value` = i.`item_id`
                        AND pin.`deleted_date` IS NULL";
        }

        if (!$count_flag) {
            $query .= " GROUP BY  a.`dassessment_id`, i.`item_id`";
        }

        if (array_key_exists("sort", $filters)) {
            if ($fetching_trend_data) {
                $query .= " ORDER BY 
                            CASE 
                                WHEN a.`encounter_date` IS NOT NULL THEN a.`encounter_date` 
                                ELSE e.`created_date` 
                            END DESC, h.`order` ASC";
            } else {
                $query .= " ORDER BY a.`created_date` " . $filters["sort"].", h.`order` ASC";
            }
        } else {
            $query .= " ORDER BY a.`created_date` DESC, h.`order` ASC";
        }

        if ($apply_limit_flag) {
            $query .= " LIMIT " . (int) $query_limit;
            $query .= " OFFSET " . (int) $query_offset;
        }
        $results = $db->GetAll($query, $params);
        return $results;
    }

    /**
     * Fetch all comments from a particular progress record
     * @param int $dassessment_id
     * @param int $aprogress_id
     * @param $assessments_with_pinned_comments
     * @return array
     */
    public function fetchAssessmentComments($dassessment_id = 0, $aprogress_id = 0, $assessments_with_pinned_comments) {
        global $db;
        $comments = array();
        $item_group_blacklist = array(
            "cbme_supervisor_rubric_concerns",
            "cbme_supervisor_rubric_concerns_item_1",
            "cbme_supervisor_rubric_concerns_item_2",
            "cbme_supervisor_rubric_concerns_item_3",
            "cbme_fieldnote_rubric_concerns",
            "cbme_fieldnote_rubric_concerns_item_1",
            "cbme_fieldnote_rubric_concerns_item_2",
            "cbme_fieldnote_rubric_concerns_item_3",
            "cbme_multisource_rubric_concerns",
            "cbme_multisource_rubric_concerns_item_1",
            "cbme_multisource_rubric_concerns_item_2",
            "cbme_multisource_rubric_concerns_item_3",
            "cbme_procedure_rubric_concerns",
            "cbme_procedure_rubric_concerns_item_1",
            "cbme_procedure_rubric_concerns_item_2",
            "cbme_procedure_rubric_concerns_item_3",
            "cbme_fieldnote_feedback",
            "cbme_procedure_feedback",
            "cbme_supervisor_feedback",
            "cbme_ppa_feedback",
            "cbme_ppa_concerns",
            "cbme_ppa_concerns_item_1",
            "cbme_ppa_concerns_item_2",
            "cbme_ppa_concerns_item_3",
            "cbme_rubric_feedback",
            "cbme_rubric_concerns",
            "cbme_rubric_concerns_item_1",
            "cbme_rubric_concerns_item_2",
            "cbme_rubric_concerns_item_3",
            "cbme_multisource_feedback"
        );

        $query = "  SELECT e.`item_text`, f.`epresponse_id`, f.`comments`, pins.`pin_id`, pins.`deleted_date`, a.`encounter_date` FROM `cbl_distribution_assessments` AS a
                    JOIN `cbl_assessment_progress` AS b
                    ON a.`dassessment_id` = b.`dassessment_id`
                    JOIN `cbl_assessments_lu_forms` AS c
                    ON a.`form_id` = c.`form_id`
                    JOIN `cbl_assessment_form_elements` AS d
                    ON c.`form_id` = d.`form_id`
                    JOIN `cbl_assessments_lu_items` AS e
                    ON d.`element_id` = e.`item_id`
                    JOIN `cbl_assessments_lu_item_groups` as ig
                    ON e.`item_group_id` = ig.`item_group_id`
                    LEFT JOIN `cbl_assessment_progress_responses` AS f
                    ON (b.`aprogress_id` = f.`aprogress_id` AND a.`form_id` = f.`form_id` AND d.`afelement_id` = f.`afelement_id`)
                    LEFT JOIN `cbl_pins` AS pins
                    ON pins.`pin_value` = f.`epresponse_id`
                    AND pins.`pin_type` = 'comment'
                    AND pins.`aprogress_id` = f.`aprogress_id`";

        /*
        if (array_key_exists("epas", $filters)) {
            $query .= " JOIN `cbl_assessment_item_objectives` AS item_objectives
                        ON e.`item_id` = item_objectives.`item_id`
                        JOIN `global_lu_objectives` AS global_lu_objectives
                        ON item_objectives.`objective_id` = global_lu_objectives.`objective_id`
                        JOIN `global_lu_objective_sets` AS global_lu_objective_sets
                        ON global_lu_objectives.`objective_set_id` = global_lu_objective_sets.`objective_set_id`";
        }

        if (array_key_exists("selected_milestones", $filters)) {
            $query .= " JOIN `cbl_assessment_item_objectives` AS milestone_cbl_assessment_item_objectives
                        ON e.`item_id` = milestone_cbl_assessment_item_objectives.`item_id`
                        JOIN `global_lu_objectives` AS milestone_global_lu_objectives
                        ON milestone_global_lu_objectives.`objective_id` = cbl_assessment_item_objectives.`objective_id`
                        JOIN `global_lu_objective_sets` AS milestone_global_lu_objective_sets
                        ON milestone_global_lu_objectives.`objective_set_id` = milestone_global_lu_objective_sets.`objective_set_id`";
        }*/

        if ($assessments_with_pinned_comments) {
            $query .= " JOIN `cbl_pins` as pin
                        ON pin.`aprogress_id` = b.`aprogress_id`";
        }

        $query .= " WHERE d.`element_type` = 'item'
                    AND a.`dassessment_id` = ?
                    AND b.`aprogress_id` = ?
                    AND f.`comments` IS NOT NULL
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    AND d.`deleted_date` IS NULL
                    AND e.`deleted_date` IS NULL
                    AND ig.`shortname` NOT IN ('". implode("','" , $item_group_blacklist) ."')";

        if ($assessments_with_pinned_comments) {
            $query .= " AND pin.`pin_type` = 'comment'
                        AND pin.`pin_value` = f.`epresponse_id`
                        AND pin.`deleted_date` IS NULL";
        }

        /*if (array_key_exists("epas", $filters)) {
            $query .= " AND item_objectives.`objective_id` IN (" . implode(",", $filters["epas"]) . ")
                        AND global_lu_objective_sets.`shortname` = 'epa'
                        AND item_objectives.`deleted_date` IS NULL
                        AND global_lu_objective_sets.`deleted_date` IS NULL";
        }

        if (array_key_exists("selected_milestones", $filters)) {
            $query .= " AND milestone_cbl_assessment_item_objectives.`objective_id` IN (". implode(",", $filters["selected_milestones"]) .")
                        AND milestone_global_lu_objective_sets.`shortname` = 'milestone'
                        AND milestone_cbl_assessment_item_objectives.`deleted_date` IS NULL
                        AND milestone_global_lu_objective_sets.`deleted_date` IS NULL";
        }*/

        $results = $db->GetAll($query, array($dassessment_id, $aprogress_id));
        if ($results) {
            $comments =  $results;
        }
        return $comments;
    }

    /**
     * Fetches a list of groups that can trigger assessments based on the provided organisation id.
     * @param int $organisation_id
     */
    public function getTriggeredByGroups($organisation_id = 0) {
        global $db;

        $query = "  SELECT DISTINCT amg.`group` 
                    FROM `cbl_assessment_method_groups` as amg
                    JOIN `cbl_assessment_method_organisations` as amo
                    ON amg.`assessment_method_id` = amo.`assessment_method_id`
                    WHERE amo.`organisation_id` = ?
                    AND amg.`admin` <> 1
                    AND amo.`deleted_date` IS NULL";

        $results = $db->GetAll($query, array($organisation_id));
        return $results;
    }
}