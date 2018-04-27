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
 * A model for linking objective sets into trees, using nested set pattern.
 *
 * ASSUMPTIONS:
 *
 *   - The top node of a tree has left of 1, and right of n * 2
 *   - The top node of a tree has no siblings, only children (or no children at all)
 *   - The top node of a tree has NULL objective_id
 *   - Trees are scoped to course and organisation
 *   - Trees have a distinct/unique tree ID, per course and organisation.
 *   - The tree ID must be unique to the course/organisation.
 *   - The tree ID can be unique to the table but is not required to be.
 *   - In all cases, when querying a tree, course, organisation,
 *     and tree_id MUST be specified.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_CBME_ObjectiveTree extends Models_Base {
    protected $cbme_objective_tree_id, $left, $right, $depth, $primary, $tree_id, $objective_id, $course_id, $organisation_id, $active_from, $active_until, $created_by, $created_date, $updated_by, $updated_date, $deleted_date;

    protected static $table_name = "cbme_objective_trees";
    protected static $primary_key = "cbme_objective_tree_id";
    protected static $default_sort_column = "left";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cbme_objective_tree_id;
    }

    public function getObjectiveTreeID() {
        return $this->cbme_objective_tree_id;
    }

    public function getLeft() {
        return $this->left;
    }

    public function getRight() {
        return $this->right;
    }

    public function getDepth() {
        return $this->depth;
    }

    public function getPrimary() {
        return $this->primary;
    }

    public function getTreeID() {
        return $this->tree_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getActiveFrom() {
        return $this->active_from;
    }

    public function getActiveUntil() {
        return $this->active_until;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setLeft($left) {
        $this->left = $left;
    }

    public function setRight($right) {
        $this->right = $right;
    }

    public function setObjectiveID($objective_id) {
        $this->objective_id = $objective_id;
    }

    public function setUpdatedDate($updated_time) {
        $this->updated_date = $updated_time;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public static function fetchRowByID($cbme_objective_tree_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cbme_objective_tree_id", "value" => $cbme_objective_tree_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "cbme_objective_tree_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Initialize a new tree root node. Returns the root as an object, with depth 0, left 1, right 2, and a new tree ID (max + 1).
     *
     * @param $course_id
     * @param $organisation_id
     * @param $proxy_id
     * @param bool $primary
     * @return bool|Models_CBME_ObjectiveTree
     */
    public function initializeTree($course_id, $organisation_id, $proxy_id, $primary = false) {
        global $db;
        $tree_id = $this->fetchHighestTreeID();
        if ($tree_id === false) {
            // can't save tree since ID was false (error from DB)
            application_log("error", "Unable to get new tree_id to initialize new objective tree for course = '$course_id', org id = '$organisation_id'', proxy id = '$proxy_id'. db said: " . $db->ErrorMsg());
            return false;
        }

        $new_tree_id = $tree_id + 1;

        $self = new self();
        $self->fromArray(array(
            "cbme_objective_tree_id" => null,
            "left" => 1,
            "right" => 2,
            "depth" => 0,
            "primary" => $primary,
            "tree_id" => $new_tree_id,
            "objective_id" => null,
            "organisation_id" => $organisation_id,
            "course_id" => $course_id,
            "created_by" => $proxy_id,
            "created_date" => time(),
        ));
        if (!$self->insert()) {
            application_log("error", "Unable to initialize new objective tree for course = '$course_id', org id = '$organisation_id'', proxy id = '$proxy_id', db said: ". $db->ErrorMsg());
            return false;
        }
        return $self;
    }

    /**
     * Fetch all the root nodes of a tree. Optionally, limit to the primary tree.
     *
     * @param $course_id
     * @param $organisation_id
     * @param bool $primary
     * @return bool|Models_Base
     */
    public function fetchAllRootNodes($course_id, $organisation_id, $primary = false) {
        $self = new self();

        $constraints = array(
            array("key" => "left", "value" => 1, "method" => "="),
            array("key" => "objective_id", "value" => null, "method" => "IS"),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        );
        if ($primary) {
            $constraints[] = array("key" => "primary", "value" => 1, "method" => "=");
        }
        return $self->fetchRow($constraints);
    }

    /**
     * Fetch the primary (default) tree root node for a given course and organisation.
     * An organisation/course can have multiple trees, but only one primary/default one.
     *
     * @param $course_id
     * @param $organisation_id
     * @return bool|Models_Base
     */
    public function fetchPrimaryTreeRootByCourseIDOrganisationID($course_id, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "primary", "value" => 1, "method" => "="),
            array("key" => "objective_id", "value" => null, "method" => "IS"),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        ));
    }

    /**
     * Fetch an entire tree.
     * The default is to ignore active from/until dates, but it can optionally limit the tree to a given timeframe.
     *
     * @param $tree_id
     * @param $course_id
     * @param $organisation_id
     * @param bool $ignore_active_dates
     * @param bool $include_deleted
     * @param int|null $active_from
     * @param int|null $active_until
     * @return mixed
     */
    public function fetchTree($tree_id, $course_id, $organisation_id, $ignore_active_dates = false, $include_deleted = false, $active_from = null, $active_until = null) {
        global $db;

        $constraints = array();
        $constraints[] = $tree_id;
        $constraints[] = $course_id;
        $constraints[] = $organisation_id;

        if ($ignore_active_dates) {
            $AND_active_from = "";
            $AND_active_until = "";
        } else {
            $AND_active_from = "AND (l.`active_from` <= ? OR l.`active_from` IS NULL)";
            $constraints[] = $active_from ? $active_from : time();
            $AND_active_until = "AND (l.`active_until` >= ? OR l.`active_until` IS NULL)";
            $constraints[] = $active_until ? $active_until : time();
        }
        $AND_deleted_date = $include_deleted ? "" : "AND l.`deleted_date` IS NULL";

        $query = "SELECT    l.*,
                            o.`objective_code`, o.`objective_name`
                  FROM      `cbme_objective_trees` AS l
                  LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`objective_id`
                  WHERE     l.`tree_id` = ?
                  AND       l.`left` >= 1
                  AND       l.`course_id` = ?
                  AND       l.`organisation_id` = ?
                  $AND_deleted_date
                  $AND_active_from
                  $AND_active_until
                  ORDER BY  l.`left`";
        return $db->GetAll($query, $constraints);
    }

    /**
     * Fetch all of the bottom nodes of a tree.
     *
     * @param $tree_id
     * @param $course_id
     * @param $organisation_id
     * @return mixed
     */
    public function fetchAllLeafNodes($tree_id, $course_id, $organisation_id) {
        global $db;
        $query = "SELECT    l.*,
                            o.`objective_name`, o.`objective_code`
                  FROM      `cbme_objective_trees`AS l
                  LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`objective_id`
                  WHERE     `tree_id` = ?
                  AND       `course_id` = ?
                  AND       `organisation_id` = ?
                  AND       `right` = `left` + 1";
        $prepared = array();
        $prepared[] = $tree_id;
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        return $db->GetAll($query, $prepared);
    }

    /**
     * Fetch one branch using a given objective ID as the head.
     *
     * @param $cbme_objective_tree_id
     * @param $course_id
     * @param $organisation_id
     * @param $depth
     * @param $ordering
     * @param $include_description
     * @return mixed
     */
    public function fetchBranchByObjectiveTreeID($cbme_objective_tree_id, $course_id, $organisation_id, $depth = null, $ordering = null, $include_description = false) {
        global $db;
        if (!$row = $this->fetchRowByID($cbme_objective_tree_id)) {
            return false;
        }

        $SELECT_description = $include_description ? ", o.`objective_description`" : "";
        $AND_depth = ($depth) ? "AND node.`depth` = ". (int)$depth : "";
        $tree_id = $row->getTreeID();

        if ($ordering) {
            $order_by = " ORDER BY LENGTH(" . $ordering . "), " . $ordering;
        } else {
            $order_by = " ORDER BY node.`left`";
        }

        $query = "SELECT node.*,
                         o.`objective_name`, o.`objective_code` {$SELECT_description}
                  FROM `cbme_objective_trees` AS node 
                  JOIN `cbme_objective_trees` AS parent
                  LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = node.`objective_id`
                  WHERE node.`left` BETWEEN parent.`left` AND parent.`right`
                  AND parent.`cbme_objective_tree_id` = ?
                  AND parent.`tree_id` = ?
                  AND node.`tree_id` = ?
                  AND node.`deleted_date` IS NULL
                  AND parent.`deleted_date` IS NULL
                  AND node.course_id = ?
                  AND node.organisation_id = ?
                  $AND_depth
                  {$order_by}";
        $prepared = array();
        $prepared[] = $cbme_objective_tree_id;
        $prepared[] = $tree_id;
        $prepared[] = $tree_id;
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        return $db->GetAll($query, $prepared);
    }

    /**
     * Fetch all of the leaf nodes of a given branch.
     * Ignores node deleted dates in order to match left/right values.
     * This only fetches the very bottom of the tree, ignoring branch nodes.
     *
     * @param int $cbme_objective_tree_id
     * @param int $course_id
     * @param int $organisation_id
     * @param string $ordering
     * @return mixed
     */
    public function fetchLeafNodesByObjectiveTreeID($cbme_objective_tree_id, $course_id, $organisation_id, $ordering = null) {
        global $db;
        if (!$row = $this->fetchRowByID($cbme_objective_tree_id)) {
            return false;
        }

        $ORDER_BY = $ordering ? "ORDER BY $ordering" : "ORDER BY node.`left`";
        $tree_id = $row->getTreeID();

        $query = "SELECT node.*,
                         o.`objective_name`, o.`objective_code`
                  FROM   `cbme_objective_trees` AS node LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = node.`objective_id`,
                         `cbme_objective_trees` AS parent
                  WHERE node.`left` BETWEEN parent.`left` AND parent.`right`
                  AND parent.`cbme_objective_tree_id` = ?
                  AND parent.`tree_id` = ?
                  AND node.`tree_id` = ?
                  AND parent.`deleted_date` IS NULL
                  AND node.`right` = node.`left` + 1
                  AND node.course_id = ?
                  AND node.organisation_id = ?
                  $ORDER_BY";

        $prepared = array();
        $prepared[] = $cbme_objective_tree_id;
        $prepared[] = $tree_id;
        $prepared[] = $tree_id;
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        $result = $db->GetAll($query, $prepared);
        return $result;
    }

    /**
     * Fetch the depth of every node in the tree.
     *
     * @param $course_id
     * @param $organisation_id
     * @return mixed
     */
    public function fetchNodeDepths($course_id, $organisation_id) {
        global $db;
        $query = "SELECT    node.*,
                            (COUNT(parent.`cbme_objective_tree_id`)) AS depth
                  FROM      `cbme_objective_trees` AS node,
                            `cbme_objective_trees` AS parent
                  WHERE     node.`left` BETWEEN parent.`left` AND parent.`right`
                  AND       node.`course_id` = ?
                  AND       node.`organisation_id` = ?
                  GROUP BY  node.`cbme_objective_tree_id`
                  ORDER BY  node.`left`";
        $prepared = array();
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        $results = $db->GetAll($query, $prepared);
        if (!empty($results)) {
            return $results;
        } else {
            return false;
        }
    }

    /**
     * Fetch the relative depth of a subtree starting at a given ID (primary key).
     *
     * @param $cbme_objective_tree_id
     * @param $course_id
     * @param $organisation_id
     * @return mixed
     */
    public function fetchSubtreeDepth($cbme_objective_tree_id, $course_id, $organisation_id) {
        global $db;
        $query = "SELECT node.`cbme_objective_tree_id`, (COUNT(parent.`cbme_objective_tree_id`) - (sub_tree.`depth`)) AS depth
                  FROM `cbme_objective_trees` AS node,
                       `cbme_objective_trees` AS parent,
                       `cbme_objective_trees` AS sub_parent,
                       (
                            SELECT node.`cbme_objective_tree_id`, (COUNT(parent.`cbme_objective_tree_id`) - 1) AS depth
                            FROM `cbme_objective_trees` AS node, `cbme_objective_trees` AS parent
                            WHERE node.`left` BETWEEN parent.`left` AND parent.`right`
                            AND node.`cbme_objective_tree_id` = ?
                            GROUP BY node.`cbme_objective_tree_id`
                            ORDER BY node.`left`
                       ) AS sub_tree
                  WHERE node.`left` BETWEEN parent.`left` AND parent.`right`
                  AND node.`left` BETWEEN sub_parent.`left` AND sub_parent.`right`
                  AND node.`course_id` = ?
                  AND node.`organisation_id` = ?
                  AND node.`deleted_date` IS NULL
                  AND sub_parent.`cbme_objective_tree_id` = sub_tree.`cbme_objective_tree_id`
                  GROUP BY node.`cbme_objective_tree_id`
                  ORDER BY node.`left`";
        $prepared = array();
        $prepared[] = $cbme_objective_tree_id;
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        $results = $db->GetAll($query, $prepared);
        return $results;
    }

    public function fetchHighestTreeID() {
        global $db;
        $query = "SELECT MAX(`tree_id`) AS highest_tree_id FROM `cbme_objective_trees` LIMIT 1";
        $result = $db->GetOne($query);
        if ($result === false) {
            // error
            return false;
        }
        if ($result === null) {
            // None found
            return 0;
        }
        return (int)$result;
    }

    public function fetchTreeRootNodeByTreeID($tree_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tree_id", "value" => $tree_id, "method" => "="),
            array("key" => "left", "value" => 1, "method" => "=")
        ));
    }

    public function fetchTreeRoot($tree_id, $course_id, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tree_id", "value" => $tree_id, "method" => "="),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    /**
     * Fetch all of the objectives names and codes contained in the tree. This is a flat list.
     *
     * @param $tree_id
     * @param $course_id
     * @param $organisation_id
     * @return array
     */
    public function fetchObjectivesFromTree($tree_id, $course_id, $organisation_id) {
        global $db;
        $query = "SELECT    l.`objective_id`, o.`objective_code`, o.`objective_name`
                  FROM      `cbme_objective_trees` AS l
                  LEFT JOIN `global_lu_objectives` AS o ON o.`objective_id` = l.`objective_id`
                  WHERE     l.`tree_id` = ?
                  AND       l.`left` > 1
                  AND       l.`course_id` = ?
                  AND       l.`organisation_id` = ?
                  AND       l.`deleted_date` IS NULL
                  AND       l.`objective_id` IS NOT NULL
                  ORDER BY  l.`left`";
        $prepared = array();
        $prepared[] = $tree_id;
        $prepared[] = $course_id;
        $prepared[] = $organisation_id;
        $results = $db->GetAll($query, $prepared);
        $return_set = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                $return_set[$result["objective_id"]] = $result;
            }
        }
        return $return_set;
    }

    /**
     * Fetch a node by objective ID at a given depth.
     *
     * @param $tree_id
     * @param $depth
     * @param $left
     * @param $right
     * @param $objective_id
     * @param $course_id
     * @param $organisation_id
     * @return bool|Models_Base
     */
    public function fetchNodeObjectiveAtDepth($tree_id, $depth, $left, $right, $objective_id, $course_id, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tree_id", "value" => $tree_id, "method" => "="),
            array("key" => "depth", "value" => $depth, "method" => "="),
            array("key" => "left", "value" => $left, "method" => ">"),
            array("key" => "right", "value" => $right, "method" => "<"),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        ));
    }

    /**
     * Adding a new leaf node (no children) to the tree.
     *
     * @param $tree_id
     * @param $cbme_objective_tree_id
     * @param $objective_id
     * @param $course_id
     * @param $organisation_id
     * @param $proxy_id
     * @return bool|Models_Cbme_ObjectiveTree
     */
    public function addNode($tree_id, $cbme_objective_tree_id, $objective_id, $course_id, $organisation_id, $proxy_id) {
        global $db;
        $created = time();

        if (!$parent_node = $this->fetchRowByID($cbme_objective_tree_id)) {
            // Current node not found, can't add
            application_log("error", "Parent not found for node ID ('$cbme_objective_tree_id')");
            return false;
        }

        $new_depth = $parent_node->getDepth() + 1;
        $parent_left = $parent_node->getLeft();

        $shift_rights_query = "UPDATE  `cbme_objective_trees`
                               SET     `right` = `right` + 2, `updated_by` = ?, `updated_date` = ?
                               WHERE   `right` > ?
                               AND     `tree_id` = ?
                               AND     `course_id` = ?
                               AND     `organisation_id` = ?";
        if (!$db->Execute($shift_rights_query, array($proxy_id, $created, $parent_left, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "Adding node: Failed to shift table right values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $shift_lefts_query = "UPDATE `cbme_objective_trees`
                              SET    `left` = `left` + 2, `updated_by` = ?, `updated_date` = ?
                              WHERE  `left` > ?
                              AND    `tree_id` = ?
                              AND    `course_id` = ?
                              AND    `organisation_id` = ?";
        if (!$db->Execute($shift_lefts_query, array($proxy_id, $created, $parent_left, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "Adding node: Failed to shift table left values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $self = new self();
        $self->fromArray(array(
            "cbme_objective_tree_id" => null,
            "left" => $parent_left + 1,
            "right" => $parent_left + 2,
            "depth" => $new_depth,
            "primary" => false,
            "tree_id" => $tree_id,
            "objective_id" => $objective_id,
            "organisation_id" => $organisation_id,
            "course_id" => $course_id,
            "created_by" => $proxy_id,
            "created_date" => time(),
        ));
        if (!$self->insert()) {
            application_log("error", "Unable to add new objective tree item for course = '$course_id', org id = '$organisation_id'', proxy id = '$proxy_id', db said: ". $db->ErrorMsg());
            return false;
        }
        return $self;
    }

    /**
     * Inserting a new node onto the same level of the tree. This does not allow adding beside the root.
     *
     * @param $tree_id
     * @param $cbme_objective_tree_id
     * @param $objective_id
     * @param $course_id
     * @param $organisation_id
     * @param $proxy_id
     * @return bool|Models_Cbme_ObjectiveTree
     */
    public function insertNode($tree_id, $cbme_objective_tree_id, $objective_id, $course_id, $organisation_id, $proxy_id) {
        global $db;
        $created = time();

        if (!$adjacent_node = $this->fetchRowByID($cbme_objective_tree_id)) {
            // Current node not found, can't add
            application_log("error", "insertNode: Failed to find node ID ('$cbme_objective_tree_id')");
            return false;
        }

        if ($adjacent_node->getDepth() == 0) {
            application_log("notice", "Not inserting node adjacted to root ('$cbme_objective_tree_id')");
            return false;
        }

        $parent_right_value = $adjacent_node->getRight();

        $shift_rights_query = "UPDATE  `cbme_objective_trees`
                               SET     `right` = `right` + 2, `updated_by` = ?, `updated_date` = ?
                               WHERE   `right` > ?
                               AND     `tree_id` = ?
                               AND     `course_id` = ?
                               AND     `organisation_id` = ?";

        if (!$db->Execute($shift_rights_query, array($proxy_id, $created, $parent_right_value, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "insertNode: Failed to shift table right values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $shift_lefts_query = "UPDATE `cbme_objective_trees`
                              SET    `left` = `left` + 2, `updated_by` = ?, `updated_date` = ?
                              WHERE  `left` > ?
                              AND    `tree_id` = ?
                              AND    `course_id` = ?
                              AND    `organisation_id` = ?";
        if (!$db->Execute($shift_lefts_query, array($proxy_id, $created, $parent_right_value, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "insertNode: Failed to shift table left values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $self = new self();
        $self->fromArray(array(
            "cbme_objective_tree_id" => null,
            "left" => $parent_right_value + 1,
            "right" => $parent_right_value + 2,
            "depth" => $adjacent_node->getDepth(),
            "primary" => false,
            "tree_id" => $tree_id,
            "objective_id" => $objective_id,
            "organisation_id" => $organisation_id,
            "course_id" => $course_id,
            "created_by" => $proxy_id,
            "created_date" => time(),
        ));
        if (!$self->insert()) {
            application_log("error", "Unable to add new objective tree item for course = '$course_id', org id = '$organisation_id'', proxy id = '$proxy_id', db said: ". $db->ErrorMsg());
            return false;
        }
        return $self;
    }

    /**
     * Query to fetch a tree for debug dump.
     *
     * @param int $tree_id
     * @param int $course_id
     * @param int $organisation_id
     * @param bool $include_deleted
     * @param bool $ignore_active_dates
     * @param string $spacer
     * @param int|null $active_from
     * @param int|null $active_until
     * @return mixed
     */
    public function fetchTreeForRender($tree_id, $course_id, $organisation_id, $include_deleted = false, $ignore_active_dates = false, $spacer = "__", $active_from = null, $active_until = null) {

        $constraints = array($spacer, $tree_id, $course_id, $organisation_id, $tree_id, $course_id, $organisation_id);
        if ($ignore_active_dates) {
            $AND_active_from = "";
            $AND_active_until = "";
        } else {
            $AND_active_from = "AND (node.`active_from` <= ? OR node.`active_from` IS NULL)";
            $constraints[] = $active_from ? $active_from : time();
            $AND_active_until = "AND (node.`active_until` >= ? OR node.`active_until` IS NULL)";
            $constraints[] = $active_until ? $active_until : time();
        }
        $AND_include_deleted = ($include_deleted) ? "" : "AND node.`deleted_date` IS NULL AND parent.`deleted_date` IS NULL";

        $query = "SELECT (REPEAT( ?, (COUNT(parent.`cbme_objective_tree_id`) - 1) )) AS node_spacing,
                         (COUNT(parent.`cbme_objective_tree_id`) - 1) AS node_space_count,
                         node.*,
                         o.`objective_code`,
                         o.`objective_name`

                  FROM   `cbme_objective_trees` AS node 
                  LEFT JOIN `global_lu_objectives` AS o USING(`objective_id`), `cbme_objective_trees` AS parent

                  WHERE  node.`left` BETWEEN parent.`left` AND parent.`right`

                  AND    parent.`tree_id` = ?
                  AND    parent.`course_id` = ?
                  AND    parent.`organisation_id` = ?
                  AND    node.`tree_id` = ?
                  AND    node.`course_id` = ?
                  AND    node.`organisation_id` = ?
                  $AND_include_deleted
                  $AND_active_from
                  $AND_active_until
                  GROUP BY node.`cbme_objective_tree_id`
                  ORDER BY node.`left`;";
        global $db;
        $tree = $db->GetAll($query, $constraints);
        return $tree;
    }

    /**
     * Destructive operation: this method deletes a node and any/all children of that node, and
     * repositions the rest of the tree.
     *
     * This method should not be used outside of administrative action as it will remove existing
     * records without any history.
     *
     * @param $tree_id
     * @param $cbme_objective_tree_id
     * @param $course_id
     * @param $organisation_id
     * @param $proxy_id
     * @return bool
     */
    public function hardDeleteNode($tree_id, $cbme_objective_tree_id, $course_id, $organisation_id, $proxy_id) {
        global $db;

        if (!$to_delete = $this->fetchRowByID($cbme_objective_tree_id)) {
            application_log("error", "Failed to fetch row to delete by ID ('$cbme_objective_tree_id').");
            return false;
        }
        $node_left = $to_delete->getLeft();
        $node_right = $to_delete->getRight();
        $width = $node_right - $node_left + 1;

        $delete_query = "DELETE FROM `cbme_objective_trees` WHERE (`left` BETWEEN ? AND ?) AND `course_id` = ? and `organisation_id` = ? AND `tree_id` = ?";
        if (!$db->Execute($delete_query, array($node_left, $node_right, $course_id, $organisation_id, $tree_id))) {
            application_log("error", "hardDeleteNode: Failed to execute delete query for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $shift_rights_query = "UPDATE  `cbme_objective_trees`
                               SET     `right` = `right` - ?, `updated_by` = ?, `updated_date` = ?
                               WHERE   `right` > ?
                               AND     `tree_id` = ?
                               AND     `course_id` = ?
                               AND     `organisation_id` = ?";

        if (!$db->Execute($shift_rights_query, array($width, $proxy_id, time(), $node_right, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "hardDeleteNode: Failed to shift table right values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }

        $shift_lefts_query = "UPDATE  `cbme_objective_trees`
                              SET     `left` = `left` - ?, `updated_by` = ?, `updated_date` = ?
                              WHERE   `left` > ?
                              AND     `tree_id` = ?
                              AND     `course_id` = ?
                              AND     `organisation_id` = ?";

        if (!$db->Execute($shift_lefts_query, array($width, $proxy_id, time(), $node_right, $tree_id, $course_id, $organisation_id))) {
            application_log("error", "hardDeleteNode: Failed to shift table left values for node ID ('$cbme_objective_tree_id') DB said: " . $db->ErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Fetch an array containing the parent ID of a the given node.
     *
     * @param $cbme_objective_tree_id
     * @return array|mixed
     */
    public function fetchParentOfNode($cbme_objective_tree_id) {
        global $db;
        $query = "SELECT  `cbme_objective_tree_id` AS child_id,
                          (
                              SELECT    `cbme_objective_tree_id`
                              FROM      `cbme_objective_trees` t2
                              WHERE     t2.`left` < t1.`left`
                              AND       t2.`right` > t1.`right`
                              AND       t1.`cbme_objective_tree_id` = ?
                              AND       t1.`tree_id` = t2.`tree_id`
                              ORDER BY  t2.`right` - t1.`right` ASC LIMIT 1
                          ) AS parent_id
                  FROM `cbme_objective_trees` t1
                  ORDER BY parent_id DESC LIMIT 1";

        $result = $db->GetAll($query, array($cbme_objective_tree_id));
        if (is_array($result)) {
            return array_shift($result);
        }
        return $result;
    }

    /**
     * Fetch all nodes at a given depth.
     * Optionally limit the result set to those records that have children of particular IDs (specified in child_objectives_limit).
     *
     * @param $depth
     * @param $tree_id
     * @param $course_id
     * @param $organisation_id
     * @param string $ordering
     * @param bool $include_description
     * @param array $child_objectives_limit
     * @param int $active_from
     * @param int $active_until
     * @return mixed
     */
    public function fetchNodesAtDepth($depth, $tree_id, $course_id, $organisation_id, $ordering = null, $include_description = false, $child_objectives_limit = array(), $active_from = null, $active_until = null) {
        global $db;

        $SELECT_description = $include_description ? ", o.`objective_description`, o.`objective_secondary_description`" : "";

        if ($ordering) {
            $ORDER_BY = " ORDER BY LENGTH(" . $ordering . "), " . $ordering;
        } else {
            $ORDER_BY = "";
        }

        $AND_child_subquery = "";
        if (!empty($child_objectives_limit)) {
            $child_objectives_limit = array_map(function($v){
                return clean_input($v, array("trim", "int"));
            }, $child_objectives_limit);
            $child_ids_string = implode(", ", $child_objectives_limit);
            $AND_child_subquery = "
            AND (SELECT `objective_id` 
                 FROM `cbme_objective_trees` AS subt 
                 WHERE subt.`left` > t.`left` AND subt.`right` < t.`right`
                   AND subt.`objective_id` IN ($child_ids_string)
                   AND subt.`tree_id` = ?
                   AND subt.`course_id` = ?
                   AND subt.`organisation_id` = ? 
                 LIMIT 1
            )";
        }

        // Add constraints for default WHERE
        $constraints = array(
            $depth,
            $tree_id,
            $organisation_id,
            $course_id,
        );

        $AND_active_from = "AND (`active_from` <= UNIX_TIMESTAMP() OR `active_from` IS NULL)";
        $AND_active_until = "AND (`active_until` >= UNIX_TIMESTAMP() OR `active_until` IS NULL)";
        if ($active_from) {
            $AND_active_from = "AND (`active_from` <= ? OR `active_from` IS NULL)";
            $constraints[] = $active_from;
        }
        if ($active_until) {
            $AND_active_until = "AND (`active_until` >= ? OR `active_until` IS NULL)";
            $constraints[] = $active_until;
        }

        // Add constraints for final subquery
        if ($AND_child_subquery) {
            $constraints[] = $tree_id;
            $constraints[] = $course_id;
            $constraints[] = $organisation_id;
        }

        $query = "SELECT t.* , o.`objective_code`, o.`objective_name` {$SELECT_description}
                  FROM `cbme_objective_trees` AS t
                  LEFT JOIN `global_lu_objectives` AS o USING(`objective_id`)
                  WHERE `depth` = ? 
                  AND `tree_id` = ?
                  AND `organisation_id` = ?
                  AND `course_id` = ?
                  AND `deleted_date` IS NULL
                  $AND_active_from
                  $AND_active_until
                  $AND_child_subquery
                  $ORDER_BY";

        $result = $db->GetAll($query, $constraints);
        return $result;
    }

    /**
     * Fetch all nodes that contain the given objective ID for a given tree/org/course.
     *
     * @param $objective_id
     * @param $tree_id
     * @param $course_id
     * @param $organisation_id
     * @param bool $include_deleted
     * @return array
     */
    public function fetchAllByObjectiveID($objective_id, $tree_id, $course_id, $organisation_id, $include_deleted = false) {
        $self = new self();
        $constraints = array(
            array("key" => "tree_id", "value" => $tree_id, "method" => "="),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "=")
        );
        if (!$include_deleted) {
            $constraints[] = array("key" => "deleted_date", "value" => null, "method" => "IS");
        }
        return $self->fetchAll($constraints);
    }

}