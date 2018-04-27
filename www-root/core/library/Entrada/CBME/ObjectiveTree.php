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
 * This is an abstraction layer for CBME Objective Set Trees.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Entrada_CBME_ObjectiveTree extends Entrada_Base {

    /**
     * @todo: In order to support objective tree versioning the following changes need to be applied to this file
     *
     *  findNodesByObjectiveID - needs to recieve active_from and active_until
     *  fetchAllByObjectiveID - needs active_from and active_until
     *  fetchTree - needs active_from and active_until
     *  fetchBranch - needs active_from and active_until
     *  fetchBranchByObjectiveTreeID - needs active_from and active_until
     *  addNode - needs active_from and active_until
     *  tree->addNode - needs active_from and active_until
     *  fetchTreeObjectives - needs active_from and active_until
     *  fetchBranchByObjectiveTreeID - needs active_from and active_until
     *  addBranch - needs active_from and active_until - pass an additional array to this function indexed by objective_id with the valid_from and valid_until values
     *  traverseTreeUpFromLeaf - needs active_from and active_until
     *  buildBranchObjectiveIDListByLeafNode - needs active_from and active_until
     *  debugDumpTree - needs active_from and active_until
     *  fetchLeafNodes - needs active_from and active_until
     *  fetchTreeBranchList - needs active_from and active_until
     *  debugDumpTreeBranchList - needs active_from and active_until
     *  fetchEpaBranchesParentChild - needs active_from and active_until
     */

    // Required
    protected $actor_proxy_id = null;
    protected $actor_organisation_id = null;
    protected $course_id = null;

    // Internal tracker of what tree we're on. Can be overridden.
    protected $tree_id = null;

    private $root_node = null; // Array of the root node's data (the singular top of the tree)
    private $tree = null; // Tree model object
    private $error_messages = array();

    public function __construct($arr = array()) {
        parent::__construct($arr);

        $this->tree = new Models_CBME_ObjectiveTree();
        
        // After default construction, set the tree_id
        if ($this->actor_proxy_id && $this->actor_organisation_id && $this->course_id) {

            if (!$this->tree_id) {
                // Attempt to find the default tree root.
                if ($tree_root = $this->tree->fetchPrimaryTreeRootByCourseIDOrganisationID($this->course_id, $this->actor_organisation_id)) {
                    $this->tree_id = $tree_root->getTreeID();
                    $this->root_node = $tree_root->toArray(); // Save an array version of the record
                }
            } else {
                // Store the root of the given tree_id
                if ($tree_root = $this->tree->fetchTreeRoot($this->tree_id, $this->course_id, $this->actor_organisation_id)) {
                    $this->tree_id = $tree_root->getTreeID();
                    $this->root_node = $tree_root->toArray(); // Save an array version of the record
                }
            }
        }
    }

    //-- Getters & Setters --//

    public function getRootNodeID() {
        if (@$this->root_node["cbme_objective_tree_id"]) {
            return $this->root_node["cbme_objective_tree_id"];
        }
        return false;
    }

    public function getTreeID() {
        return $this->tree_id;
    }

    public function setTreeID($tree_id) {
        $this->tree_id = $tree_id;
    }

    public function getActorProxyID() {
        return $this->actor_proxy_id;
    }

    public function setActorProxyID($actor_proxy_id) {
        $this->actor_proxy_id = $actor_proxy_id;
    }

    public function getActorOrganisationID() {
        return $this->actor_organisation_id;
    }

    public function setActorOrganisationID($actor_organisation_id) {
        $this->actor_organisation_id = $actor_organisation_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;
    }

    /**
     * Get all errors.
     *
     * @return array
     */
    public function getErrorMessages() {
        return $this->error_messages;
    }

    /**
     * Add a single error message.
     *
     * @param $single_error_string
     */
    public function addErrorMessage($single_error_string) {
        $this->error_messages[] = $single_error_string;
    }

    /**
     * Add multiple error messages.
     *
     * @param array $error_strings
     */
    public function addErrorMessages($error_strings) {
        $this->error_messages = array_merge($this->error_messages, $error_strings);
    }

    //-- Public Tree Access --//

    /**
     * Check if this object is initialized.
     *
     * @param bool $add_error
     * @param bool $use_tree_id
     * @return bool
     */
    public function isInitialized($add_error = true, $use_tree_id = true) {
        global $translate;
        if ($use_tree_id) {
            if ($this->actor_proxy_id &&
                $this->actor_organisation_id &&
                $this->course_id &&
                $this->tree_id &&
                !empty($this->root_node)
            ) {
                return true;
            }
        } else {
            if ($this->actor_proxy_id &&
                $this->actor_organisation_id &&
                $this->course_id
            ) {
                return true;
            }
        }

        if ($add_error) {
            $this->addErrorMessage($translate->_("Tree is not initialized."));
        }
        return false;
    }

    /**
     * Create a new tree root node.
     *
     * @param bool $force_default
     * @return bool|int
     */
    public function createNewTree($force_default = false) {
        global $translate;
        if (!$this->actor_proxy_id || !$this->actor_organisation_id || !$this->course_id) {
            $this->addErrorMessage($translate->_("Unable to initialize new tree. Invalid user, organisation, or course specified."));
            return false;
        }

        // Check if any other trees exist for this organisation. If so, we create this one as a non-primary tree.
        $default_status = false;
        if (!$root_nodes = $this->tree->fetchAllRootNodes($this->course_id, $this->actor_organisation_id, true)) {
            // No root nodes found, we can define this as a primary tree.
            $default_status = true;
        }

        if (!$tree_root = $this->tree->initializeTree($this->course_id, $this->actor_organisation_id, $this->actor_proxy_id, $force_default ? true : $default_status)) {
            $this->addErrorMessage($translate->_("Unable to initialize new tree. Failed to create tree root node."));
            return false;
        }

        // Set internal properties
        $this->root_node = $tree_root->toArray();
        $this->tree_id = $tree_root->getTreeID();

        // Return success
        return $this->tree_id;
    }

    /**
     * Find a flat list of the nodes that contain the given objective ID.
     *
     * @param int $objective_id
     * @return bool|array
     */
    public function findNodesByObjectiveID($objective_id) {
        if (!$this->isInitialized()) {
            return false;
        }
        return $this->tree->fetchAllByObjectiveID($objective_id, $this->tree_id, $this->course_id, $this->actor_organisation_id);
    }

    /**
     * Fetch an entire tree.
     * Optionally, specify the tree_id to fetch tree nodes out of scope of this object.
     * Note: the active from/active until are ignored when ignore_inactive is true, even if they are specified.
     *
     * @param null $use_tree_id
     * @param bool $include_deleted
     * @param bool $ignore_inactive
     * @param int|null $active_from
     * @param int|null $active_until
     * @return array|bool
     */
    public function fetchTree($use_tree_id = null, $include_deleted = false, $ignore_inactive = true, $active_from = null, $active_until = null) {
        global $translate;
        $tree_id = ($use_tree_id) ? $use_tree_id : $this->tree_id;
        if (!$tree_id) {
            $this->addErrorMessage($translate->_("Invalid tree specified."));
            return false;
        }
        if (!$this->isInitialized(true, $use_tree_id ? false : true)) {
            return false;
        }
        return $this->tree->fetchTree($tree_id, $this->course_id, $this->actor_organisation_id, $ignore_inactive, $include_deleted, $active_from, $active_until);
    }

    /**
     * Fetch a branch of a tree, from a given position.
     * Fetches all nodes underneath the given primary key ID.
     *
     * @param int $objective_tree_node_id
     * @param int|null $depth
     * @param int|null $ordering
     * @param bool|null $include_description
     * @return bool|mixed
     */
    public function fetchBranch($objective_tree_node_id, $depth = null, $ordering = null, $include_description = false) {
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$this->fetchValidatedNode($objective_tree_node_id)) {
            return false;
        }
        return $this->tree->fetchBranchByObjectiveTreeID($objective_tree_node_id, $this->course_id, $this->actor_organisation_id, $depth, $ordering, $include_description);
    }

    /**
     * Add a node to the tree, under the given parent record ID. Returns the ID of the newly added record.
     *
     * @param $parent_id
     * @param $objective_id
     * @param bool $validate_parent
     * @return bool|int
     */
    public function addNode($parent_id, $objective_id, $validate_parent = true) {
        global $translate;

        if (!$this->isInitialized()) {
            return false;
        }

        $parent_node = null;
        if ($validate_parent) {
            if (!$parent_node = $this->fetchValidatedNode($parent_id)) {
                return false;
            }
        }

        // If we aren't validating the parent, we just fetch it directly.
        if (!$parent_node) {
            if (!$parent_node = $this->tree->fetchRowByID($parent_id)) {
                $this->addErrorMessage($translate->_("Unable to find parent node."));
                return false;
            }
        }

        if (!$node = $this->tree->addNode($this->tree_id, $parent_id, $objective_id, $this->course_id, $this->actor_organisation_id, $this->actor_proxy_id)) {
            $this->addErrorMessage($translate->_("Unable to add node to tree."));
            return false;
        }

        $new_node_id = $node->getID();
        return $new_node_id;
    }

    /**
     * Fetch all of the unique objectives linked to by the tree.
     *
     * @return array|bool
     */
    public function fetchTreeObjectives() {
        if (!$this->isInitialized()) {
            return false;
        }

        $tree_model = new Models_CBME_ObjectiveTree();
        return $tree_model->fetchObjectivesFromTree($this->getTreeID(), $this->course_id, $this->actor_organisation_id);
    }

    /**
     * Update a given node.
     * This method should be used from within Adminsitrative contexts only.
     * User-facing functionality should not need to update a node, they should add a version instead.
     *
     * @param $node_id
     * @param $objective_id
     * @param $active_from
     * @param $active_until
     * @return bool
     */
    public function saveNode($node_id, $objective_id, $active_from = null, $active_until = null) {
        global $translate, $db;
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$node_data = $this->fetchValidatedNode($node_id)) {
            return false;
        }
        $node_data["updated_by"] = $this->actor_proxy_id;
        $node_data["updated_date"] = time();
        $node_data["active_from"] = $active_from;
        $node_data["active_until"] = $active_until;
        $node_data["objective_id"] = $objective_id;
        $this->tree->fromArray($node_data);
        if (!$this->tree->update()) {
            $this->addErrorMessage($translate->_("Error attempting to update node."));
            application_log("error", "Unable to update objective tree record ($node_id). DB said " . $db->errorMsg());
            return false;
        }

        // Update all child nodes with these active dates
        $branch_nodes = $this->tree->fetchBranchByObjectiveTreeID($node_id, $this->course_id, $this->actor_organisation_id);
        if (empty($branch_nodes)) {
            $this->addErrorMessage($translate->_("Unable to fetch tree from specified node."));
            return false;
        }
        foreach ($branch_nodes as $leaf_node) {
            if ($leaf_node["cbme_objective_tree_id"] == $node_id) {
                continue;
            }
            $leaf_node_data = $this->fetchValidatedNode($leaf_node["cbme_objective_tree_id"], true);
            if (!empty($leaf_node_data)) {
                $leaf_node_data["updated_by"] = $this->actor_proxy_id;
                $leaf_node_data["updated_date"] = time();
                $leaf_node_data["active_from"] = $active_from;
                $leaf_node_data["active_until"] = $active_until;
                $this->tree->fromArray($leaf_node_data);
                if (!$this->tree->update()) {
                    $this->addErrorMessage($translate->_("Error attempting to update node."));
                    application_log("error", "Unable to update objective tree record ($node_id). DB said " . $db->errorMsg());
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Add a complete branch of nodes.
     * This function safely adds to the tree, adding to the branch where objective_ids match.
     *
     * @param $parent_node_id
     * @param $objective_id_list
     * @return bool
     */
    public function addBranch($parent_node_id, $objective_id_list) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }

        if (empty($objective_id_list)) {
            $this->addErrorMessage($translate->_("No objectives to add."));
            return false;
        }

        do {
            $parent_node = $this->fetchValidatedNode($parent_node_id);
            if ($parent_node == false) {
                return false;
            }
            $objective_id = array_shift($objective_id_list);
            if ($existing_node = $this->tree->fetchNodeObjectiveAtDepth($this->tree_id, $parent_node["depth"] + 1, $parent_node["left"], $parent_node["right"], $objective_id, $this->course_id, $this->actor_organisation_id)) {
                $parent_node_id = $existing_node->getID();
            } else {
                $parent_node_id = $this->addNode($parent_node_id, $objective_id, false);
                if (!$parent_node_id) {
                    return false;
                }
            }

        } while (!empty($objective_id_list));

        return true;
    }

    /**
     * Fetch the given node, if it exists within the current tree.
     *
     * @param $node_id
     * @param bool $suppress_error
     * @return array|bool
     */
    private function fetchValidatedNode($node_id, $suppress_error = false) {
        global $translate;
        if (!$node_id) {
            if (!$suppress_error) {
                $this->addErrorMessage($translate->_("Invalid node ID specified."));
            }
            return false;
        }

        $node = $this->tree->fetchRowByID($node_id);
        if (!$node) {
            if (!$suppress_error) {
                $this->addErrorMessage($translate->_("Node not found."));
            }
            return false;
        }

        if ($node->getCourseID() != $this->course_id || $node->getOrganisationID() != $this->actor_organisation_id || $node->getTreeID() != $this->tree_id) {
            if (!$suppress_error) {
                $this->addErrorMessage($translate->_("Node is not within the current tree."));
            }
            return false;
        }

        return $node->toArray();
    }

    /**
     * Effectively move a node and all its children from one position in the
     * tree to another. This copies the source node and appends it to the destination
     * parent node, then safely deletes the original source.
     *
     * @param $source_node_id
     * @param $destination_parent_node_id
     * @return bool
     */
    public function moveNode($source_node_id, $destination_parent_node_id) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$this->fetchValidatedNode($source_node_id, true)) {
            $this->addErrorMessage($translate->_("Unable to validate node to move."));
            return false;
        }
        if (!$this->fetchValidatedNode($destination_parent_node_id, true)) {
            $this->addErrorMessage($translate->_("Unable to validate node to move to."));
            return false;
        }

        // Fetch the bottom nodes of the branch given (1 or more nodes)
        if (!$source_leaf_nodes = $this->tree->fetchLeafNodesByObjectiveTreeID($source_node_id, $this->course_id, $this->actor_organisation_id)) {
            $this->addErrorMessage($translate->_("Unable to determine which nodes to move."));
            return false;
        }

        // For each bottom node we want to move, traverse up each tree branch to fetch the tree ids, including this node
        $branches = array();
        foreach ($source_leaf_nodes as $source_leaf_node) {
            $branches[] = $this->buildBranchObjectiveIDListByLeafNode($source_leaf_node["cbme_objective_tree_id"], $source_node_id);
        }

        // Append each branch to the destination node
        foreach ($branches as $branch_group) {
            $this->addBranch($destination_parent_node_id, $branch_group);
        }

        // Clear the old branch (deleteNode soft-deletes the node and the entire branch under the given node, if it has any children)
        $delete_status = $this->deleteNode($source_node_id);
        if (!$delete_status) {
            $this->addErrorMessage($translate->_("Unable to delete old tree (failed to clean up after move)."));
        }
        return $delete_status;
    }

    /**
     * For a given node ID, traverse up the branch, fetching all parent nodes up to the root (or the specified stopping point).
     * This is an expensive function that should not be executed within the scope of a loop.
     *
     * @param $leaf_node_id
     * @param int|null $stop_at_node_id
     * @return array|bool
     */
    public function traverseTreeUpFromLeaf($leaf_node_id, $stop_at_node_id = null) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$current_node = $this->fetchValidatedNode($leaf_node_id, true)) {
            $this->addErrorMessage($translate->_("Specified leaf node does not exist in current tree."));
            return false;
        }
        if (!$stop_at_node_id) {
            $stop_at_node_id = $this->getRootNodeID();
        } else {
            if (!$this->fetchValidatedNode($stop_at_node_id, true)) {
                $this->addErrorMessage($translate->_("Specified parent node does not exist in current tree."));
                return false;
            }
        }

        $branch_nodes = array();
        $current_id = $leaf_node_id;
        $branch_nodes[] = $current_node;

        do {
            if (!$parent_child_info = $this->tree->fetchParentOfNode($current_id)) {
                $this->addErrorMessage($translate->_("Error fetching parent node information."));
                return false;
            }
            $current_id = $parent_child_info["parent_id"];
            $current_node = $this->tree->fetchRowByID($current_id);
            if (!$current_node) {
                $this->addErrorMessage($translate->_("Error fetching node record."));
                return false;
            }
            $branch_nodes[] = $current_node->toArray();

        } while($current_id && $stop_at_node_id != $current_id);

        return $branch_nodes;
    }

    /**
     * Fetch a node, and delete it and all children from the database.
     * This should not be used outside of Administrative actions. Use deleteNode() for user-related actions instead.
     *
     * @param $node_id
     * @return bool
     */
    public function hardDeleteNode($node_id) {
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$this->fetchValidatedNode($node_id)) {
            return false;
        }
        return $this->tree->hardDeleteNode($this->tree_id, $node_id, $this->course_id, $this->actor_organisation_id, $this->actor_proxy_id);
    }

    /**
     * Fetch a node, set it and all children as deleted.
     *
     * @param $node_id
     * @return bool
     */
    public function deleteNode($node_id) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }
        if (!$this->fetchValidatedNode($node_id)) {
            return false;
        }
        // To delete a node, we simply set deleted dates on the relevant records.
        // So, we fetch the tree by branch, and mark all of the associated records as deleted.
        if ($tree_array = $this->tree->fetchBranchByObjectiveTreeID($node_id, $this->course_id, $this->actor_organisation_id)) {
            foreach ($tree_array as $tree_data) {
                $tree_data["updated_by"] = $this->actor_proxy_id;
                $tree_data["updated_date"] = time();
                $tree_data["deleted_date"] = time();
                $tree_data["deleted_by"] = $this->actor_proxy_id;
                if (!$this->tree->fromArray($tree_data)->update()) {
                    $this->addErrorMessage($translate->_("Failed to update node as deleted."));
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * For a given leaf node, traverse up the tree toward the given stopping point, building a list of all of the objective IDs.
     *
     * @param $leaf_node_id
     * @param null $stop_at_node_id
     * @return array
     */
    private function buildBranchObjectiveIDListByLeafNode($leaf_node_id, $stop_at_node_id = null) {
        $branch = array();
        $exclude_root = true;
        if ($stop_at_node_id) {
            $exclude_root = false;
        }
        if ($branch_nodes = $this->traverseTreeUpFromLeaf($leaf_node_id, $stop_at_node_id)) {
            $objectives_list = array();
            foreach ($branch_nodes as $branch_node) {
                if (!$branch_node["deleted_date"]) {
                    $objectives_list[] = $branch_node["objective_id"];
                }
            }
            if ($exclude_root) {
                array_pop($objectives_list); // Skip the root node in the branch if specified
            }
            $branch = array_reverse($objectives_list);
        }
        return $branch;
    }

    /**
     * For the given tree ID, duplicate it for the given user/course/org.
     *
     * @param $tree_id
     * @return bool
     */
    public function copyTree($tree_id)  {
        global $translate;
        if (!$root_node = $this->tree->fetchTreeRoot($tree_id, $this->course_id, $this->actor_organisation_id)) {
            $this->addErrorMessage($translate->_("Unable to find tree root node."));
            return false;
        }

        // Fetch the old tree leaf nodes
        $tree_array = $this->tree->fetchLeafNodesByObjectiveTreeID($root_node->getID(), $this->course_id, $this->actor_organisation_id);
        $branches = array();
        foreach ($tree_array as $flat_tree_node) {
            $branches[] = $this->buildBranchObjectiveIDListByLeafNode($flat_tree_node["cbme_objective_tree_id"]);
        }

        // Create a new tree, and duplciate the branches
        $this->createNewTree(); // sets internal pointers to this new tree

        foreach ($branches as $branch_objective_ids) {
            if (!$this->addBranch($this->getRootNodeID(), $branch_objective_ids)) {
                $this->addErrorMessage($translate->_("Failed adding branch to copied tree."));
                application_log("error", "Failed to add branch to tree id: {$this->getTreeID()}, root node given: {$this->getRootNodeID()}");
                return false;
            }
        }

        // Complete, return the new tree ID
        return $this->getTreeID();
    }

    /**
     * Simple method to consume the error messages. Echos them to context and reset the internal array.
     *
     * @param bool $as_html
     */
    public function dumpErrorMessages($as_html = true) {
        foreach ($this->getErrorMessages() as $error) {
            if ($as_html) {
                echo "<p class='alert-error'>$error</p>\n";
            } else {
                echo "$error\n";
            }
        }
        $this->error_messages = array(); // clear the consumed errors
    }

    /**
     * Fetch an entire tree and render a human-readable debug version of it.
     *
     * @param bool $include_deleted
     * @param bool $ignore_active_dates
     * @param null $active_from
     * @param null $active_until
     * @param string $spacer
     * @param bool $closed_tree
     */
    public function debugDumpTree($include_deleted = false, $ignore_active_dates = false, $active_from = null, $active_until = null, $closed_tree = false, $spacer = "___") {
        if (!$tree_data = $this->tree->fetchTreeForRender($this->tree_id, $this->course_id, $this->actor_organisation_id, $include_deleted, $ignore_active_dates, $spacer, $active_from, $active_until)) {
            echo "<pre>Empty Tree</pre>";
            return;
        }
        ?>
        <span class="btn btn-primary" type="button" data-toggle="collapse" data-target=".collapse-debug-tree-<?php echo $this->tree_id ?>" aria-expanded="false" >Tree ID: <?php echo $this->tree_id ?></span>
        <pre class='panel-collapse <?php echo $closed_tree ? "collapse" : "in" ?> collapse-debug-tree-<?php echo $this->tree_id ?>'><?php foreach ($tree_data as $t) {
                $inactive_ended = $inactive_pending = false;
                if ($t["active_from"] && time() < $t["active_from"]) {
                    $inactive_pending = true;
                }
                if ($t["active_until"] && time() > $t["active_until"]) {
                    $inactive_ended = true;
                }
                $inactive_state = "";
                if (!$t["deleted_date"] && $inactive_pending) {
                    $inactive_state = "|<em>INACTIVE-PENDING</em> ";
                }
                if (!$t["deleted_date"] && $inactive_ended) {
                    $inactive_state = "|<em>INACTIVE-ENDED</em> ";
                }
                $active_time = "";
                $active_time .= ($t["active_from"]) ? "|Active from: " . strftime("%Y-%m-%d %H:%M:%S ", $t["active_from"]) : "";
                $active_time .= ($t["active_until"]) ? "|Active until: " . strftime("%Y-%m-%d %H:%M:%S ", $t["active_until"]) : "";

                for ($x=0; $x<$t["node_space_count"]; $x++) {
                    echo $spacer;
                }

                echo "|<strong>Primary Key</strong>: {$t["cbme_objective_tree_id"]} (L:{$t["left"]}|R:{$t["right"]}|depth:{$t["depth"]})\n";

                for ($x=0; $x<$t["node_space_count"] * strlen($spacer); $x++) {
                    echo " ";
                }
                if ($t["deleted_date"]) {
                    echo "|<em>DELETED NODE (on " . strftime("%Y-%m-%d", $t["deleted_date"]) . ")</em> ";
                } else {
                    if ($inactive_state) {
                        echo "{$inactive_state}\n";
                    }
                }
                if ($t["objective_id"]) {

                    if ($active_time) {
                        for ($x = 0; $x < $t["node_space_count"] * strlen($spacer); $x++) {
                            echo " ";
                        }
                        echo "{$active_time}\n";
                        for ($x = 0; $x < $t["node_space_count"] * strlen($spacer); $x++) {
                            echo " ";
                        }
                    }
                    echo "|<strong>Objective ID:</strong> {$t["objective_id"]} ";
                    echo "<strong>CODE: {$t["objective_code"]}</strong> | Name: ";
                    echo strlen($t["objective_name"]) > 30 ? substr($t["objective_name"],0,30)."..." : $t["objective_name"];

                } else {

                    echo ($t["left"] == 1) ? "<em>ROOT NODE (Objective ID is NULL)</em> " : "";

                }
                echo "\n";
            } ?>
        </pre>
        <?php
    }

    /**
     * Fetch all bottom level leaf nodes for a given node ID.
     *
     * @param int $node_id
     * @param string $ordering
     * @return bool|mixed
     */
    public function fetchLeafNodes($node_id, $ordering = null) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }

        $leaf_nodes = $this->tree->fetchLeafNodesByObjectiveTreeID($node_id, $this->course_id, $this->actor_organisation_id, $ordering);
        if (!$leaf_nodes) {
            $this->addErrorMessage($translate->_("Unable to fetch leaf nodes by branch parent."));
            return false;
        }
        return $leaf_nodes;
    }

    /**
     * Fetch the objective IDs for each branch.
     *
     * @return bool|array
     */
    public function fetchTreeBranchList(){
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }

        $leaf_nodes = $this->tree->fetchLeafNodesByObjectiveTreeID($this->getRootNodeID(), $this->course_id, $this->actor_organisation_id);
        if (!$leaf_nodes) {
            $this->addErrorMessage($translate->_("Unable to fetch branch."));
            return false;
        }

        $list = array();
        foreach ($leaf_nodes as $leaf_node) {
            $list[] = $this->buildBranchObjectiveIDListByLeafNode($leaf_node["cbme_objective_tree_id"]);
        }

        return $list;
    }

    /**
     * Dump the objective IDs for each branch.
     *
     * @return bool
     */
    public function debugDumpTreeBranchList(){
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }

        $leaf_nodes = $this->tree->fetchLeafNodesByObjectiveTreeID($this->getRootNodeID(), $this->course_id, $this->actor_organisation_id);
        if (!$leaf_nodes) {
            $this->addErrorMessage($translate->_("Unable to fetch branch."));
            return false;
        }

        $list = array();
        foreach ($leaf_nodes as $leaf_node) {
            $list[] = $this->buildBranchObjectiveIDListByLeafNode($leaf_node["cbme_objective_tree_id"]);
        }

        echo "<pre>";
        foreach ($list as $item) {
            echo implode(", ", $item);
            echo "\n";
        }
        echo "</pre>";
        return true;
    }

    /**
     * Fetch all nodes of a tree for a given depth. Optionally limit the results to those nodes that have children that have the specified objective IDs.
     *
     * @param $depth
     * @param $ordering
     * @param $include_description
     * @param array $child_objective_ids
     * @param int $active_from
     * @param int $active_until
     * @return bool|mixed
     */
    public function fetchTreeNodesAtDepth($depth, $ordering = null, $include_description = false, $child_objective_ids = array(), $active_from = null, $active_until = null) {
        global $translate;
        if (!$this->isInitialized()) {
            return false;
        }

        $results = $this->tree->fetchNodesAtDepth($depth, $this->tree_id, $this->course_id, $this->actor_organisation_id, $ordering, $include_description, $child_objective_ids, $active_from, $active_until);
        if (empty($results)) {
            $this->addErrorMessage($translate->_("No nodes found"));
            return false;
        }
        return $results;
    }

    /**
     * Fetch all nodes at a depth, based on shortname.
     *
     * @param $objective_set_shortname
     * @param null $ordering
     * @param bool $include_description
     * @return array|bool|mixed
     */
    public function fetchTreeNodesByObjectiveSetShortname($objective_set_shortname, $ordering = null, $include_description = false, $active_from = null, $active_until = null) {
        if (!$this->isInitialized()) {
            return false;
        }

        // TODO: The specific depths should be configurable by settings instead of hard-coded
        // Stages should be looked up in the objective_sets table and matched accordingly.

        switch ($objective_set_shortname) {
            case "stage":
                return $this->fetchTreeNodesAtDepth(1, $ordering, $include_description, array(), $active_from, $active_until);
            case "epa":
                return $this->fetchTreeNodesAtDepth(2, $ordering, $include_description, array(), $active_from, $active_until);
            case "role":
                return $this->fetchTreeNodesAtDepth(3, $ordering, $include_description, array(), $active_from, $active_until);
            case "kc":
                return $this->fetchTreeNodesAtDepth(4, $ordering, $include_description, array(), $active_from, $active_until);
            case "ec":
                return $this->fetchTreeNodesAtDepth(5, $ordering, $include_description, array(), $active_from, $active_until);
            case "milestone":
                return $this->fetchTreeNodesAtDepth(6, $ordering, $include_description, array(), $active_from, $active_until);
            default:
                return array();
        }
    }
    
    /**
     * Build an array representing the tree's parent-child relationships in JSON.
     * Uses the flat dataset returned from fetchTree().
     *
     * @param array $node_array
     * @return string
     */
    public function buildParentChildJsonAssociation(&$node_array) {
        global $translate;
        $to_json = array();
        $parent_node = array_shift($node_array); // shift off the root
        $to_json[] = array(
            "parent" => null,
            "name" => $parent_node["cbme_objective_tree_id"],
            "label" => $parent_node["objective_code"],
            "objective_name" => $parent_node["objective_name"]
        );
        $this->buildRecursiveParentChild($to_json, $node_array, $parent_node);
        return $to_json;
    }

    /**
     * Recursively make the associations between parent and child, using the flat tree dataset.
     * This function consumes the flat_tree array, shifting nodes off of it until there are none
     * left, or the data does not follow a tree pattern.
     *
     * @param $output
     * @param $flat_tree
     * @param $parent_node
     */
    private function buildRecursiveParentChild(&$output, &$flat_tree, $parent_node) {
        $last_node = $parent_node;
        while (!empty($flat_tree)) {
            $current_node = array_shift($flat_tree);

            if ($current_node["depth"] == $parent_node["depth"] + 1) {
                // Current node is a direct descendant
                $output[] = array(
                    "parent" => $parent_node["cbme_objective_tree_id"],
                    "name" => $current_node["cbme_objective_tree_id"],
                    "label" => $current_node["objective_code"],
                    "objective_name" =>  $current_node["objective_name"]
                );
                $last_node = $current_node;
            } else if ($current_node["depth"] > $parent_node["depth"] + 1) {
                // Further down the tree (child of child)
                array_unshift($flat_tree, $current_node); // put the node back in the list, and recurse using last_node as our parent
                $this->buildRecursiveParentChild($output, $flat_tree, $last_node);

            } else {
                // Same level sibling or parent/higher level
                array_unshift($flat_tree, $current_node); // Put the node back on the list so it can be consumed by the caller we are returning to.
                return;
            }
        }
    }

    /**
     * Build an array representing the tree's parent-child relationships in
     * JSON starting at the EPA depth.
     *
     * @return string
     */
    public function fetchEpaBranchesParentChild() {
        $branches = array();

         // Fetch all tree branches for this course starting at the EPA depth
        $course_epas = $this->fetchTreeNodesByObjectiveSetShortname("epa", "o.`objective_code`");

         // Get all EPA tree branches and return as JSON array
        if ($course_epas) {
            foreach ($course_epas as $course_epa) {
                $branch = $this->fetchBranch($course_epa["cbme_objective_tree_id"]);
                $branches[] = $this->buildParentChildJsonAssociation($branch);
            }
        }
        /**
         * @todo: remove json encode, the calling code should handle the json_encode call
         */
        return json_encode($branches);
    }
}