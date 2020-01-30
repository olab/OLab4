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
 * An intance of this class represents a list of linked objectives related to a context.
 *
 * @author Organisation: UBC
 * @author Unit: Faculty of Medicine
 * @author Developer: Craig Parsons <craig.parsons@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 */

class Entrada_Curriculum_Linked_Objectives {

    protected $objectives;
    protected $target_objective_ids = array();
    protected $version_id;
    protected $context;
    protected $repository;

    /**
     * This function is called when initializing an instance for class Entrada_Curriculum_Linked_Objectives.
     *
     * @param Models_Repository|null repository The repo used to update the database for linked objectives.
     */
    function __construct($repository = null)
    {
        $this->repository = $repository;

        if ($this->repository == null) {
            $this->repository = Models_Repository_Objectives::getInstance();
        }
    }

    /**
     * @param mixed $objectives
     *
     * @return self
     */
    public function setObjectives($objectives)
    {
        $this->objectives = $objectives;

        return $this;
    }

    /**
     * @param array $target_objective_ids
     *
     * @return self
     */
    public function setTargetObjectiveIds(array $target_objective_ids)
    {
        $this->target_objective_ids = $target_objective_ids;

        return $this;
    }

    /**
     * @param integer $version_id
     *
     * @return self
     */
    public function setVersionId($version_id)
    {
        $this->version_id = $version_id;

        return $this;
    }

    /**
     * @param Entrada_Curriculum_Context_ISpecific $context
     *
     * @return self
     */
    public function setContext(Entrada_Curriculum_Context_ISpecific $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Update Linked Objectives updates all the links passed in using the
     * deleteLinkedObjectives function. Passes these important attributes to
     * the objective repository.
     *
     * @return void
     */
    public function delete()
    {
        global $db;
        $db->BeginTrans();

        // Functions that get called on the Objective Repository
        // be careful when modifying this code, order matters.
        $deleteLinkedFunctions = array(
            'deleteLinkedObjectiveContexts',
            'deleteLinkedObjectives'
        );

        try {
            // Delete all the linked objectives per event linked objectives.
            foreach ($this->objectives as $name => $record) {
                foreach ($record as $objective_id => $objective_flag) {
                    foreach ($deleteLinkedFunctions as $deleteLinkedObjective) {
                        $this->repository->$deleteLinkedObjective(
                            $objective_id,
                            $this->target_objective_ids,
                            $this->version_id,
                            $this->context
                        );
                    }
                }
            }

        } catch (Exception $e) {
            $db->RollbackTrans();
            application_log("error", "Error in ".get_called_class().". DB Said: " . $e->getMessage());

            throw new Exception("Database error updating linked objectives");
        }

        $db->CommitTrans();
    }
}
