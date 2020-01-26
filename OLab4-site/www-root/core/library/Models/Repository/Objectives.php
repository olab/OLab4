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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Repository_Objectives extends Models_Repository implements Models_Repository_IObjectives, Entrada_IGettable {

    use Entrada_Gettable;

    public function fetchAllByIDs(array $objective_ids) {
        global $db;
        if ($objective_ids) {
            $query = "SELECT *
                      FROM `global_lu_objectives`
                      WHERE `objective_id` IN (".$this->quoteIDs($objective_ids).")
                      ORDER BY `objective_order`";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function fetchAllByParentID($parent_id) {
        return $this->fetchAllByParentIDs(array($parent_id));
    }

    public function fetchAllByParentIDs(array $parent_ids) {
        global $db;
        if ($parent_ids) {
            $query = "SELECT *
                      FROM `global_lu_objectives`
                      WHERE `objective_parent` IN (".implode(", ", array_map(array($db, "qstr"), $parent_ids)).")
                      AND `objective_active` = 1
                      ORDER BY `objective_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function fetchTagSetsByOrganisationID($organisation_id) {
        return $this->fetchAllByParentIDAndOrganisationID(0, $organisation_id);
    }

    public function fetchAllByParentIDAndOrganisationID($parent_id, $organisation_id) {
        return $this->fetchAllByParentIDsAndOrganisationID(array($parent_id), $organisation_id);
    }

    public function fetchAllByParentIDsAndOrganisationID(array $parent_ids, $organisation_id) {
        global $db;
        if ($parent_ids && $organisation_id) {
            $query = "SELECT o.*
                      FROM `global_lu_objectives` o
                      INNER JOIN `objective_organisation` org ON org.`objective_id` = o.`objective_id`
                      WHERE `objective_parent` IN (".implode(", ", array_map(array($db, "qstr"), $parent_ids)).")
                      AND org.`organisation_id` = ".$db->qstr($organisation_id)."
                      AND `objective_active` = 1
                      ORDER BY `objective_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArrays($results);
        } else {
            return array();
        }
    }

    public function fetchAllByTagSetID($tag_set_id) {
        $children = array();
        $objectives = $this->fetchAllByParentIDs(array($tag_set_id));
        while ($objectives) {
            $children = array_reduce($objectives, function (array $children, Models_Objective $objective) {
                unset($children[$objective->getParent()]);
                $children[$objective->getID()] = $objective;
                return $children;
            }, $children);
            $objective_ids = array_keys($objectives);
            $objectives = $this->fetchAllByParentIDs($objective_ids);
        }
        return $children;
    }

    public function fetchAllByTagSetIDAndOrganisationID($tag_set_id, $organisation_id) {
        $children = array();
        $objectives = $this->fetchAllByParentIDsAndOrganisationID(array($tag_set_id), $organisation_id);
        while ($objectives) {
            $children = array_reduce($objectives, function (array $children, Models_Objective $objective) {
                unset($children[$objective->getParent()]);
                $children[$objective->getID()] = $objective;
                return $children;
            }, $children);
            $objective_ids = array_keys($objectives);
            $objectives = $this->fetchAllByParentIDsAndOrganisationID($objective_ids, $organisation_id);
        }
        return $children;
    }

    public function fetchLinkedObjectivesByID($direction, $objective_id, $version_id, Entrada_Curriculum_IContext $context, $not = false) {
        return $this->flatten($this->flatten($this->fetchLinkedObjectivesByIDs($direction, array($objective_id), $version_id, $context, $not)));
    }

    public function fetchLinkedObjectivesByIDs($direction, array $objective_ids, $version_id, Entrada_Curriculum_IContext $context, $not = false) {
        global $db;
        if (!in_array($direction, array("to", "from"))) {
            throw new InvalidArgumentException();
        }
        if ($objective_ids) {
            switch ($direction) {
            case "from":
                $source_objective_id_field = "l.`objective_id`";
                $destination_objective_id_field = "l.`target_objective_id`";
                break;
            case "to":
                $source_objective_id_field = "l.`target_objective_id`";
                $destination_objective_id_field = "l.`objective_id`";
                break;
            }
            if ((int) $version_id) {
                $version_sql = "AND l.`version_id` = ".((int) $version_id);
            } else if ($version_id !== false) {
                $version_sql = "AND l.`version_id` IS NULL";
            } else {
                $version_sql = "";
            }
            if (!$not) {
                list($context_join_sql, $context_where_sql) = $this->contextJoinWhere($context);
            } else {
                list($context_join_sql, $context_where_sql) = $this->contextJoinWhereNot($context);
            }
            $query = "SELECT o.*, ".$source_objective_id_field." AS `source_objective_id`, l.`version_id`
                      FROM `global_lu_objectives` AS o
                      INNER JOIN `linked_objectives` l ON ".$destination_objective_id_field." = o.`objective_id`
                      ".$context_join_sql."
                      WHERE o.`objective_active` = 1 AND l.`active` = 1
                      AND ".$source_objective_id_field." IN (".$this->quoteIDs($objective_ids).")
                      ".$version_sql."
                      ".$context_where_sql."
                      ORDER BY o.`objective_order` ASC";
            $results = $db->GetAll($query);
            return $this->fromArraysByMany(array("version_id", "source_objective_id"), $results);
        } else {
            return array();
        }
    }

    /**
     * Gets events and event objectives by week objective id. Used in Weekly Summary page.
     *
     * @param $week_objective_id
     * @param $cunit_id
     *
     * @return mixed
     */
    public function fetchEventObjectivesByWeekObjective($week_objective_id, $cunit_id) {
        global $db;

        $query = "
            SELECT 
                glo.objective_id, 
                glo.objective_name,
                glo.objective_description, 
                lo.target_objective_id,
                e.event_id,
                e.event_title,                
                GROUP_CONCAT(DISTINCT elet.eventtype_title SEPARATOR ';') AS eventtype_title
            FROM global_lu_objectives glo
            INNER JOIN linked_objectives lo
            ON glo.objective_id = lo.objective_id
            INNER JOIN event_linked_objectives elo
            ON elo.linked_objective_id = lo.linked_objective_id
            INNER JOIN events e
            ON e.event_id = elo.event_id
            INNER JOIN event_eventtypes eet
            ON eet.event_id = elo.event_id
            INNER JOIN events_lu_eventtypes elet
            ON elet.eventtype_id = eet.eventtype_id
            INNER JOIN curriculum_map_versions v 
            ON v.version_id = lo.version_id 
            AND v.status = 'published'
            INNER JOIN curriculum_map_version_periods p
            ON p.version_id = v.version_id
            WHERE lo.target_objective_id = ?
            AND e.cunit_id = ?
            GROUP BY e.event_id, glo.objective_id
        ";

        return $db->GetAll($query, array($week_objective_id, $cunit_id));
    }

    private function contextJoinWhere(Entrada_Curriculum_IContext $context) {
        $event_ids = $context->getEventIDs();
        $cunit_ids = $context->getCunitIDs();
        $course_ids = $context->getCourseIDs();
        $context_join_sql = "";
        $context_where_sql = "FALSE ";
        if ($event_ids) {
            $context_join_sql .= "LEFT JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "OR el.`event_id` IN (".$this->quoteIDs($event_ids).") ";
        }
        if ($cunit_ids) {
            $context_join_sql .= "LEFT JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "OR cul.`cunit_id` IN (".$this->quoteIDs($cunit_ids).") ";
        }
        if ($course_ids) {
            $context_join_sql .= "LEFT JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "OR cl.`course_id` IN (".$this->quoteIDs($course_ids).") ";
        }
        if ($event_ids || $cunit_ids || $course_ids) {
            $context_where_sql .= "OR (TRUE ".
                ($event_ids ? "AND el.`event_id` IS NULL " : "").
                ($cunit_ids ? "AND cul.`cunit_id` IS NULL " : "").
                ($course_ids ? "AND cl.`course_id` IS NULL " : "").
                ")";
        }
        return array($context_join_sql, "AND ({$context_where_sql})");
    }

    private function contextJoinWhereNot(Entrada_Curriculum_IContext $context) {
        $context_join_sql = "";
        $context_where_sql = "";
        if ($event_ids = $context->getEventIDs()) {
            $context_join_sql .= "LEFT JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "AND (el.`event_id` NOT IN (".$this->quoteIDs($event_ids).") OR el.`event_id` IS NULL) ";
        } else if ($cunit_ids = $context->getCunitIDs()) {
            $context_join_sql .= "LEFT JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "AND (cul.`cunit_id` NOT IN (".$this->quoteIDs($cunit_ids).") OR cul.`cunit_id` IS NULL) ";
        } else if ($course_ids = $context->getCourseIDs()) {
            $context_join_sql .= "LEFT JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`\n";
            $context_where_sql .= "AND (cl.`course_id` NOT IN (".$this->quoteIDs($course_ids).") OR cl.`course_id` IS NULL) ";
        } else {
            $context_join_sql = "";
            $context_where_sql = "";
        }
        return array($context_join_sql, $context_where_sql);
    }

    public function fetchLinkedObjectivesByIDsAndEvents($direction, array $objective_ids, $version_id, $event_ids = false, array $filters = array()) {
        global $db;
        if (!in_array($direction, array("to", "from"))) {
            throw new InvalidArgumentException();
        }
        if ($direction == "from" && $event_ids === false) {
            throw new LogicException("Expected event IDs to be specified if direction is from");
        }

        if ($objective_ids && ($event_ids === false || (is_array($event_ids) && count($event_ids)))) {
            switch ($direction) {
            case "from":
                $source_objective_id_field = "l.`objective_id`";
                $destination_objective_id_field = "l.`target_objective_id`";
                break;
            case "to":
                $source_objective_id_field = "l.`target_objective_id`";
                $destination_objective_id_field = "l.`objective_id`";
                break;
            }
            $join_linked_objectives = "`linked_objectives` l";

            if ((int) $version_id) {
                $version_sql = "AND l.`version_id` = ".((int) $version_id);
            } else if ($version_id !== false) {
                $version_sql = "AND l.`version_id` IS NULL";
            } else {
                $version_sql = "";
            }

            if ($event_ids) {
                $event_sql = "AND e.`event_id` IN (".$this->quoteIDs($event_ids).")";
            } else if ($event_ids !== false) {
                $event_sql = "AND FALSE";
            } else {
                $event_sql = "";
            }

            if (!empty($filters["cunit_ids"])) {
                $cunit_sql = "AND e.`cunit_id` IN (".$this->quoteIDs($filters["cunit_ids"]).")";
            } else {
                $cunit_sql = "";
            }
            if (!empty($filters["course_ids"])) {
                $course_sql = "AND e.`course_id` IN (".$this->quoteIDs($filters["course_ids"]).")";
            } else {
                $course_sql = "";
            }
            if (!empty($filters["start"])) {
                $start_sql = "AND e.`event_start` >= ".$db->qstr($filters["start"]);
            } else {
                $start_sql = "";
            }
            if (!empty($filters["end"])) {
                $end_sql = "AND e.`event_start` <= ".$db->qstr($filters["end"]);
            } else {
                $end_sql = "";
            }

            $query = "SELECT a.*, o.objective_parent, o.objective_code, o.objective_name, o.objective_description FROM
                      (
                          SELECT ".$destination_objective_id_field." AS `objective_id`, ".$source_objective_id_field." AS `source_objective_id`, l.`version_id`, e.`event_id`
                          FROM ".$join_linked_objectives."
                          INNER JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`
                          INNER JOIN `events` e ON e.`event_id` = el.`event_id`
                          WHERE l.`active` = 1
                          AND ".$source_objective_id_field." IN (".$this->quoteIDs($objective_ids).")
                          ".$version_sql."
                          ".$event_sql."
                          ".$cunit_sql."
                          ".$course_sql."
                          ".$start_sql."
                          ".$end_sql."
                          GROUP BY ".$destination_objective_id_field.", ".$source_objective_id_field.", l.`version_id`, e.`event_id`

                          UNION

                          SELECT ".$destination_objective_id_field." AS `objective_id`, ".$source_objective_id_field." AS `source_objective_id`, l.`version_id`, e.`event_id`
                          FROM ".$join_linked_objectives."
                          INNER JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`
                          INNER JOIN `events` e ON e.`cunit_id` = cul.`cunit_id`
                          INNER JOIN `linked_objectives` l2 ON l2.`version_id` = l.`version_id` AND l2.`target_objective_id` = l.`objective_id`
                          INNER JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l2.`linked_objective_id` AND el.`event_id` = e.`event_id`
                          WHERE l.`active` = 1 AND l2.`active` = 1
                          AND ".$source_objective_id_field." IN (".$this->quoteIDs($objective_ids).")
                          ".$version_sql."
                          ".$event_sql."
                          ".$cunit_sql."
                          ".$course_sql."
                          ".$start_sql."
                          ".$end_sql."
                          GROUP BY ".$destination_objective_id_field.", ".$source_objective_id_field.", l.`version_id`, e.`event_id`

                          UNION

                          SELECT ".$destination_objective_id_field." AS `objective_id`, ".$source_objective_id_field." AS `source_objective_id`, l.`version_id`, e.`event_id`
                          FROM ".$join_linked_objectives."
                          INNER JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`
                          INNER JOIN `events` e ON e.`course_id` = cl.`course_id`
                          INNER JOIN `linked_objectives` l2 ON l2.`version_id` = l.`version_id` AND l2.`target_objective_id` = l.`objective_id`
                          INNER JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l2.`linked_objective_id` AND cul.`cunit_id` = e.`cunit_id`
                          INNER JOIN `linked_objectives` l3 ON l3.`version_id` = l2.`version_id` AND l3.`target_objective_id` = l2.`objective_id`
                          INNER JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l3.`linked_objective_id` AND el.`event_id` = e.`event_id`
                          WHERE l.`active` = 1 AND l2.`active` = 1 AND l3.`active` = 1
                          AND ".$source_objective_id_field." IN (".$this->quoteIDs($objective_ids).")
                          ".$version_sql."
                          ".$event_sql."
                          ".$cunit_sql."
                          ".$course_sql."
                          ".$start_sql."
                          ".$end_sql."
                          GROUP BY ".$destination_objective_id_field.", ".$source_objective_id_field.", l.`version_id`, e.`event_id`

                          UNION

                          SELECT ".$destination_objective_id_field." AS `objective_id`, ".$source_objective_id_field." AS `source_objective_id`, l.`version_id`, NULL AS `event_id`
                          FROM ".$join_linked_objectives."
                          LEFT JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`
                          LEFT JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`
                          LEFT JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`
                          WHERE l.`active` = 1
                          AND ".$source_objective_id_field." IN (".$this->quoteIDs($objective_ids).")
                          ".$version_sql."
                          AND el.`event_id` IS NULL
                          AND cul.`cunit_id` IS NULL
                          AND cl.`course_id` IS NULL
                          GROUP BY ".$destination_objective_id_field.", ".$source_objective_id_field.", l.`version_id`
                      ) a INNER JOIN `global_lu_objectives` o ON o.`objective_id` = a.`objective_id` AND o.`objective_active` = 1";
            $results = $db->GetAll($query);
            return $this->fromArraysByMany(array("version_id", "event_id", "source_objective_id"), $results);
        } else {
            return array();
        }
    }

    public function fetchHasLinks($direction, array $objectives, $version_id, array $exclude_tag_set_ids, Entrada_Curriculum_IContext $context) {
        global $db;
        if (!in_array($direction, array("to", "from"))) {
            throw new InvalidArgumentException();
        }
        $objective_ids = array_map(function ($objective) { return $objective->getID(); }, $objectives);
        if ($objective_ids) {
            switch($direction) {
            case "to":
                $group_by_field = "l.`target_objective_id`";
                $target_field = "l.`objective_id`";
                break;
            case "from":
                $group_by_field = "l.`objective_id`";
                $target_field = "l.`target_objective_id`";
                break;
            }
            if ($exclude_tag_set_ids) {
                $exclude_join_sql = "INNER JOIN `global_lu_objectives` AS o ON o.`objective_id` = ".$target_field." ";
                $exclude_where_sql = "AND o.`objective_parent` NOT IN (".$this->quoteIDs($exclude_tag_set_ids).")";
            } else {
                $exclude_join_sql = "";
                $exclude_where_sql = "";
            }
            if ($version_id) {
                $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
            } else {
                $version_sql = "AND l.`version_id` IS NULL";
            }
            list($context_join_sql, $context_where_sql) = $this->contextJoinWhere($context);
            $query = "SELECT ".$group_by_field." AS `objective_id`
                      FROM `linked_objectives` l
                      ".$context_join_sql."
                      ".$exclude_join_sql."
                      WHERE ".$group_by_field." IN (".$this->quoteIDs($objective_ids).")
                      ".$version_sql."
                      ".$context_where_sql."
                      ".$exclude_where_sql."
                      AND l.`active` = 1
                      GROUP BY ".$group_by_field;
            $results = $db->GetAll($query);
            if ($results === false) {
                application_log("error", "Database error in ".get_called_class().". DB Said: " . $db->ErrorMsg());
                throw new Exception("Database error fetching data in ".get_called_class()." records");
            } else {
                $objectives_have_links = array();
                foreach ($results as $result) {
                    $objectives_have_links[$result["objective_id"]] = $result["objective_id"];
                }
                return $objectives_have_links;
            }
        } else {
            return array();
        }
    }

    public function populateHasLinks(array $rows, array $objectives_have_links) {
        return array_map(function (array $row) use ($objectives_have_links) {
            if (isset($objectives_have_links[$row["objective_id"]])) {
                $row["has_links"] = true;
            } else if (!isset($row["has_links"])) {
                $row["has_links"] = false;
            }
            return $row;
        }, $rows);
    }

    public function fetchTotalMappingsByObjectivesTo($version_id, $to_tag_set_id, array $from_objective_ids, array $event_ids) {
        global $db;

        if ($from_objective_ids && $event_ids) {
            if ($version_id) {
                $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
            } else {
                $version_sql = "AND l.`version_id` IS NULL";
            }

            $event_sql = "AND e.`event_id` IN (".$this->quoteIDs($event_ids).")";
            $objective_sql = "AND l.`objective_id` IN (".$this->quoteIDs($from_objective_ids).")";
            $to_objectives = $this->fetchAllByTagSetID($to_tag_set_id);
            $to_objective_ids = array_map(function (Models_Objective $objective) { return $objective->getID(); }, $to_objectives);
            $to_objective_sql = "AND l.`target_objective_id` IN (".$this->quoteIDs($to_objective_ids).")";
            $query = "SELECT l.`objective_id`, e.`event_id`, COUNT(DISTINCT l.`target_objective_id`) AS mappings
                      FROM `linked_objectives` l
                      INNER JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`
                      INNER JOIN `events` e ON e.`event_id` = el.`event_id` ".$event_sql."
                      WHERE l.`active` = 1
                      ".$version_sql."
                      ".$objective_sql."
                      ".$to_objective_sql."
                      GROUP BY l.`objective_id`, e.`event_id`
                      UNION ALL
                      SELECT l.`objective_id`, e.`event_id`, COUNT(DISTINCT l.`target_objective_id`) AS mappings
                      FROM `linked_objectives` l
                      INNER JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`
                      INNER JOIN `events` e ON e.`cunit_id` = cul.`cunit_id` ".$event_sql."
                      WHERE l.`active` = 1
                      ".$version_sql."
                      ".$to_objective_sql."
                      GROUP BY l.`objective_id`, e.`event_id`
                      UNION ALL
                      SELECT l.`objective_id`, e.`event_id`, COUNT(DISTINCT l.`target_objective_id`) AS mappings
                      FROM `linked_objectives` l
                      INNER JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`
                      INNER JOIN `events` e ON e.`course_id` = cl.`course_id` ".$event_sql."
                      WHERE l.`active` = 1
                      ".$version_sql."
                      ".$objective_sql."
                      ".$to_objective_sql."
                      GROUP BY l.`objective_id`, e.`event_id`
                      UNION ALL
                      SELECT l.`objective_id`, NULL AS `event_id`, COUNT(DISTINCT l.`target_objective_id`) AS mappings
                      FROM `linked_objectives` l
                      LEFT JOIN `course_linked_objectives` cl ON cl.`linked_objective_id` = l.`linked_objective_id`
                      LEFT JOIN `course_unit_linked_objectives` cul ON cul.`linked_objective_id` = l.`linked_objective_id`
                      LEFT JOIN `event_linked_objectives` el ON el.`linked_objective_id` = l.`linked_objective_id`
                      WHERE l.`active` = 1
                      ".$version_sql."
                      ".$objective_sql."
                      ".$to_objective_sql."
                      AND el.`linked_objective_id` IS NULL
                      AND cul.`linked_objective_id` IS NULL
                      AND cl.`linked_objective_id` IS NULL
                      GROUP BY l.`objective_id`";
            $rows = $db->GetAll($query);

            $mappings_by_objective = array_reduce($rows, function (array $mappings_by_objective, array $row) {
                $mappings_by_objective[$row["objective_id"]][$row["event_id"]] = $row["mappings"];
                return $mappings_by_objective;
            }, array());

            return $mappings_by_objective;
        } else {

            return array();
        }
    }

    public function updateLinkedObjectives(array $objectives, array $linked_objectives, $version_id, Entrada_Curriculum_Context_ISpecific $context) {
        global $db;
        $db->BeginTrans();
        try {
            foreach (array_keys($objectives) as $objective_id) {
                if (isset($linked_objectives[$objective_id])) {
                    $target_objective_ids = array_keys($linked_objectives[$objective_id]);
                } else {
                    $target_objective_ids = array();
                }
                $this->insertLinkedObjectives($objective_id, $target_objective_ids, $version_id);
                $this->insertLinkedObjectiveContexts($objective_id, $target_objective_ids, $version_id, $context);
                $this->deleteLinkedObjectiveContexts($objective_id, $target_objective_ids, $version_id, $context);
                $this->deleteLinkedObjectives($objective_id, $target_objective_ids, $version_id, $context);
            }
            $this->deleteLinkedObjectivesNotTo($version_id, $context);
        } catch (Exception $e) {
            $db->RollbackTrans();
            application_log("error", "Error in ".get_called_class().". DB Said: " . $e->getMessage());
            throw new Exception("Database error updating linked objectives");
        }
        $db->CommitTrans();
    }

    public function insertLinkedObjectiveContexts($objective_id, $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context) {
        global $db, $ENTRADA_USER;
        if ($target_objective_ids) {
            $user_id = $ENTRADA_USER->getID();
            if ($version_id) {
                $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
            } else {
                $version_sql = "AND l.`version_id` IS NULL";
            }
            $context_table = $context->getTable();
            $context_column = $context->getColumn();
            $context_id = $context->getID();
            $insert = "
                INSERT INTO `".$context_table."`(`".$context_column."`, `linked_objective_id`, `updated_date`, `updated_by`)
                SELECT ".$db->qstr($context_id).", l.`linked_objective_id`, UNIX_TIMESTAMP(), ".$user_id."
                FROM `linked_objectives` AS l
                LEFT JOIN `".$context_table."` AS cl ON cl.`".$context_column."` = ".$db->qstr($context_id)." AND cl.`linked_objective_id` = l.`linked_objective_id`
                WHERE l.`objective_id` = ".$db->qstr($objective_id)."
                AND l.`target_objective_id` IN (".$this->quoteIDs($target_objective_ids).")
                AND cl.`linked_objective_id` IS NULL
                ".$version_sql;
            if ($db->Execute($insert) === false) {
                throw new Exception($db->ErrorMsg());
            }
        }
    }

    public function insertLinkedObjectives($objective_id, array $target_objective_ids, $version_id) {
        global $db;
        if ($target_objective_ids) {
            if ($version_id) {
                $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
            } else {
                $version_sql = "AND l.`version_id` IS NULL";
            }
            $insert = "
                    INSERT INTO `linked_objectives`(`objective_id`, `target_objective_id`, `version_id`, `active`)
                    SELECT ".$db->qstr($objective_id).", o.`objective_id`, ".$db->qstr($version_id).", 1
                    FROM `global_lu_objectives` AS o
                    LEFT JOIN `linked_objectives` AS l ON l.`target_objective_id` = o.`objective_id`
                        AND l.`objective_id` = ".$db->qstr($objective_id)."
                        ".$version_sql."
                        AND l.`active` = 1
                    WHERE o.`objective_id` IN (".$this->quoteIDs($target_objective_ids).")
                    AND l.`linked_objective_id` IS NULL";
            if ($db->Execute($insert) === false) {
                throw new Exception($db->ErrorMsg());
            }
        }
    }

    public function deleteLinkedObjectiveContexts($objective_id, array $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context) {
        global $db, $ENTRADA_USER;
        if ($target_objective_ids) {
            $target_objective_sql = "AND l.`target_objective_id` NOT IN (".$this->quoteIDs($target_objective_ids).")";
        } else {
            $target_objective_sql = "";
        }
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $context_table = $context->getTable();
        $context_taught_in_table = $context->getTaughtInTable();
        $context_column = $context->getColumn();
        $context_id = $context->getID();
        $delete = "
            DELETE `".$context_table."` FROM `".$context_table."`
            INNER JOIN `linked_objectives` AS l ON l.`linked_objective_id` = `".$context_table."`.`linked_objective_id`
            LEFT JOIN `".$context_taught_in_table."` AS o ON o.`objective_id` = l.`objective_id`
            AND o.`".$context_column."` = `".$context_table."`.`".$context_column."`
            WHERE `".$context_table."`.`".$context_column."` = ".$db->qstr($context_id)."
            AND ((o.`objective_id` = ".$db->qstr($objective_id)." ".$target_objective_sql.") OR o.`objective_id` IS NULL)
            ".$version_sql;
        if ($db->Execute($delete) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function deleteLinkedObjectives($objective_id, array $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context) {
        global $db;
        if ($target_objective_ids) {
            $target_objective_sql = "AND l.`target_objective_id` NOT IN (".$this->quoteIDs($target_objective_ids).")";
        } else {
            $target_objective_sql = "";
        }
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $context_table = $context->getTable();
        $context_column = $context->getColumn();
        $context_id = $context->getID();
        $delete = "
            DELETE l FROM `linked_objectives` AS l
            LEFT JOIN `".$context_table."` ON `".$context_table."`.`linked_objective_id` = l.`linked_objective_id`
            AND `".$context_table."`.`".$context_column."` <> ".$db->qstr($context_id)."
            WHERE l.`objective_id` = ".$db->qstr($objective_id)."
            ".$version_sql."
            ".$target_objective_sql."
            AND `".$context_table."`.`".$context_column."` IS NULL";
        if ($db->Execute($delete) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function deleteLinkedObjectivesNotTo($version_id, Entrada_Curriculum_Context_ISpecific $context) {
        $context_column = $context->getColumn();
        $context_id = $context->getID();
        switch ($context_column) {
        case "cunit_id":
            $cunit_id = $context_id;
            $this->deleteLinkedObjectiveContextsNotToCourseUnit($version_id, $cunit_id);
            $this->deleteLinkedObjectivesNotToCourseUnit($version_id, $cunit_id);
            break;
        case "course_id":
            $course_id = $context_id;
            $this->deleteLinkedObjectiveContextsNotToCourse($version_id, $course_id);
            $this->deleteLinkedObjectivesNotToCourse($version_id, $course_id);
            break;
        }
    }

    public function deleteLinkedObjectiveContextsNotToCourseUnit($version_id, $cunit_id) {
        global $db;
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $delete_event_linked_objectives = "
            DELETE el FROM `event_linked_objectives` AS el
            INNER JOIN `linked_objectives` AS l ON l.`linked_objective_id` = el.`linked_objective_id`
            INNER JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`target_objective_id`
            INNER JOIN `linked_tag_sets` AS t ON t.`objective_id` = o.`objective_parent` AND t.`type` = 'course_unit'
            INNER JOIN `events` e ON e.`event_id` = el.`event_id`
            LEFT JOIN `course_unit_objectives` AS cuo ON cuo.`cunit_id` = e.`cunit_id` AND cuo.`objective_id` = l.`target_objective_id`
            WHERE e.`cunit_id` = ".$db->qstr($cunit_id)."
            AND cuo.`objective_id` IS NULL
            ".$version_sql;
        if ($db->Execute($delete_event_linked_objectives) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function deleteLinkedObjectivesNotToCourseUnit($version_id, $cunit_id) {
        global $db;
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $delete_linked_objectives = "
            DELETE l FROM `linked_objectives` AS l
            INNER JOIN `event_objectives` eo ON eo.`objective_id` = l.`objective_id`
            INNER JOIN `events` AS e ON e.`event_id` = eo.`event_id`
            LEFT JOIN `event_linked_objectives` AS el ON el.`linked_objective_id` = l.`linked_objective_id`
            LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`target_objective_id`
            LEFT JOIN `linked_tag_sets` AS t ON t.`objective_id` = o.`objective_parent` AND t.`type` = 'course_unit'
            LEFT JOIN `course_unit_objectives` AS cuo ON cuo.`cunit_id` = e.`cunit_id` AND cuo.`objective_id` = l.`target_objective_id`
            WHERE e.`cunit_id` = ".$db->qstr($cunit_id)."
            AND cuo.`objective_id` IS NULL
            AND el.`event_id` IS NULL
            ".$version_sql;
        if ($db->Execute($delete_linked_objectives) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function deleteLinkedObjectiveContextsNotToCourse($version_id, $course_id) {
        global $db;
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $delete_course_unit_linked_objectives = "
            DELETE cul FROM `course_unit_linked_objectives` AS cul
            INNER JOIN `linked_objectives` AS l ON l.`linked_objective_id` = cul.`linked_objective_id`
            INNER JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`target_objective_id`
            INNER JOIN `linked_tag_sets` AS t ON t.`objective_id` = o.`objective_parent` AND t.`type` = 'course'
            INNER JOIN `course_units` cu ON cu.`cunit_id` = cul.`cunit_id`
            LEFT JOIN `course_objectives` AS co ON co.`course_id` = cu.`course_id` AND co.`objective_id` = l.`target_objective_id`
            AND co.`cperiod_id` <=> cu.`cperiod_id`
            WHERE cu.`course_id` = ".$db->qstr($course_id)."
            AND co.`objective_id` IS NULL
            ".$version_sql;
        if ($db->Execute($delete_course_unit_linked_objectives) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function deleteLinkedObjectivesNotToCourse($version_id, $course_id) {
        global $db;
        if ($version_id) {
            $version_sql = "AND l.`version_id` = ".$db->qstr($version_id);
        } else {
            $version_sql = "AND l.`version_id` IS NULL";
        }
        $delete_linked_objectives = "
            DELETE l FROM `linked_objectives` AS l
            INNER JOIN `course_unit_objectives` cuo ON cuo.`objective_id` = l.`objective_id`
            INNER JOIN `course_units` AS cu ON cu.`cunit_id` = cuo.`cunit_id`
            LEFT JOIN `course_unit_linked_objectives` AS cul ON cul.`linked_objective_id` = l.`linked_objective_id`
            LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`target_objective_id`
            LEFT JOIN `linked_tag_sets` AS t ON t.`objective_id` = o.`objective_parent` AND t.`type` = 'course'
            LEFT JOIN `course_objectives` AS co ON co.`course_id` = cu.`course_id` AND co.`objective_id` = l.`target_objective_id`
            AND co.`cperiod_id` <=> cu.`cperiod_id`
            WHERE cu.`course_id` = ".$db->qstr($course_id)."
            AND co.`objective_id` IS NULL
            AND cul.`cunit_id` IS NULL
            ".$version_sql;
        if ($db->Execute($delete_linked_objectives) === false) {
            throw new Exception($db->ErrorMsg());
        }
    }

    public function fetchTagSetByObjectives(array $objectives) {
        $parent_id_by_objective = function (Models_Objective $objective) { return $objective->getParent(); };
        $objective_parent_ids = array_map($parent_id_by_objective, $objectives);
        $all_parents = $parents = $this->fetchAllByIDs($objective_parent_ids);
        $is_not_tag_set = function (Models_Objective $parent) { return $parent->getParent() > 0; };
        $non_tag_sets = array_filter($parents, $is_not_tag_set);
        while ($non_tag_sets) {
            $non_tag_set_parent_ids = array_map($parent_id_by_objective, $non_tag_sets);
            $objective_parent_ids = array_map(function ($parent_id) use ($non_tag_set_parent_ids) {
                return !empty($non_tag_set_parent_ids[$parent_id]) ? $non_tag_set_parent_ids[$parent_id] : $parent_id;
            }, $objective_parent_ids);
            $parents = $this->fetchAllByIDs($non_tag_set_parent_ids);
            $all_parents = $parents + $all_parents;
            $non_tag_sets = array_filter($parents, $is_not_tag_set);
        }
        $objective_tag_sets = array_map(function ($tag_set_id) use ($all_parents) {
            return isset($all_parents[$tag_set_id]) ? $all_parents[$tag_set_id] : null;
        }, $objective_parent_ids);
        return $objective_tag_sets;
    }

    public function fetchSearchTagSetByObjectives(array $objectives, $organisation_id) {
        $parent_id_by_objective = function (Models_Objective $objective) { return $objective->getParent(); };
        $objective_parent_ids = array_map($parent_id_by_objective, $objectives);

        $parent_ids = array();
        foreach ($objectives as $objective) {
            $tag_set = Models_Objective::fetchObjectiveSet($objective->getId(), $organisation_id);

            if ($tag_set) {
                $parent_ids[$objective->getID()] = $tag_set->getID();
            } else {
                // objective is already a parent if result is false
                $parent_ids[$objective->getID()] = null;
            }
        }

        $all_parents = $parents = $this->fetchAllByIDs($parent_ids);
        $is_not_tag_set = function (Models_Objective $parent) { return $parent->getParent() > 0; };
        $non_tag_sets = array_filter($parents, $is_not_tag_set);

        while ($non_tag_sets) {
            $non_tag_set_parent_ids = array_map($parent_id_by_objective, $non_tag_sets);
            $objective_parent_ids = array_map(function ($parent_id) use ($non_tag_set_parent_ids) {
                return !empty($non_tag_set_parent_ids[$parent_id]) ? $non_tag_set_parent_ids[$parent_id] : $parent_id;
            }, $objective_parent_ids);
            $parents = $this->fetchAllByIDs($non_tag_set_parent_ids);
            $all_parents = $parents + $all_parents;
            $non_tag_sets = array_filter($parents, $is_not_tag_set);
        }

        $objective_tag_set_ids = array_keys($all_parents);

        $objective_tag_sets = array();
        foreach ($objective_tag_set_ids as $objective_tag_set_id) {
            $objective_tag_sets[$objective_tag_set_id] = Models_Objective::fetchRow($objective_tag_set_id, 1, $organisation_id);
        }

        return $objective_tag_sets;
    }

    public function groupByTagSet(array $objectives) {
        $objective_tag_sets = $this->fetchTagSetByObjectives($objectives);
        return array_reduce($objectives, function (array $objective_by_tag_set, Models_Objective $objective) use ($objective_tag_sets) {
            $objective_id = $objective->getID();
            $tag_set_name = isset($objective_tag_sets[$objective_id]) ? $objective_tag_sets[$objective_id]->getName() : "";
            $objective_by_tag_set[$tag_set_name][$objective_id] = $objective;
            return $objective_by_tag_set;
        }, array());
    }

    public function groupArraysByTagSet(array $rows) {
        $objectives = $this->fromArrays($rows);
        $objective_tag_sets = $this->fetchTagSetByObjectives($objectives);
        return array_reduce($rows, function (array $objective_by_tag_set, array $row) use ($objective_tag_sets) {
            $objective_id = $row["objective_id"];
            $tag_set_name = isset($objective_tag_sets[$objective_id]) ? $objective_tag_sets[$objective_id]->getName() : "";
            $objective_by_tag_set[$tag_set_name][$objective_id] = $row;
            return $objective_by_tag_set;
        }, array());
    }

    public function groupIDsByTagSet(array $objectives) {
        $objective_tag_sets = $this->fetchTagSetByObjectives($objectives);
        return array_reduce($objectives, function (array $objective_by_tag_set, Models_Objective $objective) use ($objective_tag_sets) {
            $objective_id = $objective->getID();
            $tag_set_id = isset($objective_tag_sets[$objective_id]) ? $objective_tag_sets[$objective_id]->getID() : 0;
            $objective_by_tag_set[$tag_set_id][$objective_id] = $objective_id;
            return $objective_by_tag_set;
        }, array());
    }

    public function searchExcludeByTagSetIDs(array $objectives, array $exclude_tag_set_ids) {
        global $ENTRADA_USER;
        $organisation_id = $ENTRADA_USER->getActiveOrganisation();
        $objective_tag_sets = $this->fetchSearchTagSetByObjectives($objectives, $organisation_id);

        $array_filter_result = array_filter($objectives, function (Models_Objective $objective) use ($exclude_tag_set_ids, $objective_tag_sets, $organisation_id) {
            $objective_id = $objective->getID();
            $tag_set_objective = Models_Objective::fetchObjectiveSet($objective_id, $organisation_id);

            if (is_object($tag_set_objective)) {
                return (!in_array($tag_set_objective->getID(), $exclude_tag_set_ids));
            } else {
                return true;
            }
        });

        return $array_filter_result;
    }

    public function excludeByTagSetIDs(array $objectives, array $exclude_tag_set_ids) {
        $objective_tag_sets = $this->fetchTagSetByObjectives($objectives);

        return array_filter($objectives, function (Models_Objective $objective) use ($exclude_tag_set_ids, $objective_tag_sets) {
            $objective_id = $objective->getID();
            $tag_set_id = isset($objective_tag_sets[$objective_id]) ? $objective_tag_sets[$objective_id]->getID() : 0;
            return (!in_array($objective_tag_sets[$objective->getID()]->getID(), $exclude_tag_set_ids));
        });
    }

    public function fetchAllByEventIDs(array $event_ids) {
        global $db;
        if ($event_ids) {
            $query = "SELECT eo.`event_id`, o.*
                      FROM `global_lu_objectives` AS o
                      INNER JOIN `event_objectives` AS eo ON eo.`objective_id` = o.`objective_id`
                      WHERE eo.`event_id` IN (" . implode(", ", array_map(array($db, "qstr"), $event_ids)) . ")
                      AND o.`objective_active` = 1
                      ORDER BY o.`objective_order`";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("event_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByCourseUnitIDs(array $cunit_ids) {
        global $db;
        if ($cunit_ids) {
            $query = "SELECT cuo.`cunit_id`, o.*
                      FROM `global_lu_objectives` AS o
                      INNER JOIN `course_unit_objectives` AS cuo ON cuo.`objective_id` = o.`objective_id`
                      WHERE cuo.`cunit_id` IN (" . implode(", ", array_map(array($db, "qstr"), $cunit_ids)) . ")
                      AND o.`objective_active` = 1
                      ORDER BY o.`objective_order`";

            $results = $db->GetAll($query);
            return $this->fromArraysBy("cunit_id", $results);
        } else {
            return array();
        }
    }

    public function fetchAllByCourseIDsAndCperiodID(array $course_ids, $cperiod_id) {
        global $db;
        if ($course_ids) {
            if ($cperiod_id) {
                $cperiod_sql = "AND co.`cperiod_id` = ".$db->qstr($cperiod_id);
            } else {
                $cperiod_sql = "AND co.`cperiod_id` IS NULL";
            }
            $query = "SELECT co.`course_id`, o.*
                      FROM `global_lu_objectives` AS o
                      INNER JOIN `course_objectives` AS co 
                        ON co.`objective_id` = o.`objective_id`
                        AND co.`active` = 1
                      WHERE co.`course_id` IN (" . implode(", ", array_map(array($db, "qstr"), $course_ids)) . ")
                      ".$cperiod_sql."
                      AND o.`objective_active` = 1
                      ORDER BY o.`objective_order`";
            $results = $db->GetAll($query);
            return $this->fromArraysBy("course_id", $results);
        } else {
            return array();
        }
    }

    /**
     * Gets events and event objectives. Used in Map By Events report.
     *
     * @param int course_id
     *
     * @return array
     */
    public function fetchEventObjectives($course_id, $limit_start, $limit_end) {
        global $db;

        $query = "
            SELECT 
                e.event_id, 
                cu.unit_title, 
                e.event_title,
                elet.eventtype_title,
                eet.duration AS minutes,
                event_start,
                event_finish,
                e.event_description,
                glo.objective_id,
                glo.objective_description
            FROM course_units cu
            INNER JOIN events e
            ON e.cunit_id = cu.cunit_id
            INNER JOIN event_eventtypes eet
            ON eet.event_id = e.event_id
            INNER JOIN events_lu_eventtypes elet
            ON elet.eventtype_id = eet.eventtype_id
            INNER JOIN event_linked_objectives elo
            ON e.event_id = elo.event_id
            INNER JOIN linked_objectives lo
            ON elo.linked_objective_id = lo.linked_objective_id
            INNER JOIN global_lu_objectives glo
            ON glo.objective_id = lo.objective_id
            WHERE cu.course_id = ?
            ORDER BY e.event_id ASC            
            LIMIT ?,?
        ";

        return $db->GetAll($query, array((int)$course_id, (int)$limit_start, (int)$limit_end));
    }

    /**
     * Converts OBJECTIVE_LINKS_VIEW_EXCLUDE comma-delimited string into corresponding objective ids
     *
     * @returns array
     */
    public function getExcludedTagSetIds() {
        global $ENTRADA_USER;
        $exclude_tag_set_ids = array();

        foreach (explode(",", OBJECTIVE_LINKS_VIEW_EXCLUDE) as $exclude_tag_set_name) {
            $exclude_tag_set = Models_Objective::fetchRowByNameParentID($ENTRADA_USER->getActiveOrganisation(), $exclude_tag_set_name, 0);

            if ($exclude_tag_set) {
                $exclude_tag_set_ids[] = $exclude_tag_set->getID();
            }
        }

        return $exclude_tag_set_ids;
    }

    /**
     * Converts OBJECTIVE_LINKS_SEARCH_EXCLUDE comma-delimited string into corresponding objective ids
     *
     * @returns array
     */
    public function getSearchExcludedTagSetIds() {
        global $ENTRADA_USER;
        $exclude_tag_set_ids = array();

        foreach (explode(",", OBJECTIVE_LINKS_SEARCH_EXCLUDE) as $exclude_tag_set_name) {
            $exclude_tag_set = Models_Objective::fetchRowByNameParentID($ENTRADA_USER->getActiveOrganisation(), $exclude_tag_set_name, 0);

            if ($exclude_tag_set) {
                $exclude_tag_set_ids[] = $exclude_tag_set->getID();
            }
        }

        return $exclude_tag_set_ids;
    }

    protected function fromArray(array $result) {
        $objective = new Models_Objective();
        $objective->fromArray($result);
        return $objective;
    }
}
