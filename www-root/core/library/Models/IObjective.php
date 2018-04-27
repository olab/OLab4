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
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
 */

interface Models_IObjective {
    public function __construct($objective_id = NULL,
                                $objective_code = NULL,
                                $objective_name = NULL,
                                $objective_description = NULL,
                                $objective_parent = NULL,
                                $associated_objective = NULL,
                                $objective_order = NULL,
                                $objective_loggable = NULL,
                                $objective_active = 1,
                                $updated_date = NULL,
                                $updated_by = NULL);

    public function getID();

    public function getCode();

    public function getName();

    public function getDescription();

    public function getParent();

    public function getAssociatedObjective();

    public function getOrder();

    public function getDateUpdated();

    public function getUpdatedBy();

    public function getLoggable();

    public function getActive();

    public function toArray();

    public function fromArray($arr);

    public function getObjectiveText($always_show_code = false);
}
