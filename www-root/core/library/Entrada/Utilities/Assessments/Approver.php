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
 * A class to handle Distribution Delegation functionality.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Assessments_Approver extends Entrada_Utilities_Assessments_Base {
    protected $dassessment_id, $approver_id;

    public function __construct($arr = null) {
        parent::__construct($arr);
    }

    public function getApproverID() {
        return $this->approver_id;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function setDassessmentId($dassessment_id) {
        $this->dassessment_id = $dassessment_id;
    }

    public function setApproverId($approver_id) {
        $this->approver_id = $approver_id;
    }

    public function sendApproverReminder() {
        $assessment = Models_Assessments_Assessor::fetchRowByID($this->getDassessmentID());
        if ($assessment) {
            $this->queueApproverNotifications($assessment, $this->approver_id);
            return true;
        }
        return false;
    }
}