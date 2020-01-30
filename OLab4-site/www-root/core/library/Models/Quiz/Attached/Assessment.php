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
 * A model to handle quizzes attached to assessments
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Quiz_Attached_Assessment extends Models_Quiz_Attached {

    protected $aaquiz_id, $assessment_id, $aquiz_id, $updated_date, $updated_by;
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getAaquizID() {
        return $this->aaquiz_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getAquizID() {
        return $this->aquiz_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
}

?>
