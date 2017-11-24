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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assessments_AssessmentTarget extends Models_Base {
    protected $atarget_id, $dassessment_id, $adistribution_id, $target_type, $target_value, $delegation_list_id, $associated_schedules, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_distribution_assessment_targets";
    protected static $primary_key = "atarget_id";
    protected static $default_sort_column = "atarget_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->atarget_id;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getTargetType() {
        return $this->target_type;
    }

    public function getTargetValue() {
        return $this->target_value;
    }

    public function getDelegationListID() {
        return $this->delegation_list_id;
    }

    public function getAssociatedSchedules() {
        return $this->associated_schedules;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setUpdatedDate ($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy ($id) {
        $this->updated_by = $id;
    }

    public function setDeletedDate ($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public static function fetchAllByDistributionIDTargetTypeTargetValueAssessmentID ($distribution_id = null, $target_type = "proxy_id", $target_value = null, $dassessment_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

	public static function fetchRowByDistributionIDTargetValue ($distribution_id = null, $target_value = null, $deleted_date = null) {
		$self = new self();
		$fetch = array(
			array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
			array("key" => "target_value", "method" => "=", "value" => $target_value),
			array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		);
		return $self->fetchRow($fetch);
	}

	public static function fetchAllByDistributionIDAssessmentID ($distribution_id = null, $assessment_id = null, $deleted_date = null) {
		$self = new self();
		$fetch = array(
			array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
			array("key" => "dassessment_id", "method" => "=", "value" => $assessment_id),
			array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		);
		return $self->fetchAll($fetch);
	}

    public static function fetchAllByDistributionID ($distribution_id = null, $deleted_date = null, $use_delegation_list_id = false) {
        $self = new self();
        $fetch = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if ($use_delegation_list_id) {
            $fetch[] = array("key" => "delegation_list_id", "method" => "IS NOT", "value" => null);
        }
        return $self->fetchAll($fetch);
    }

    public static function fetchAllByDassessmentID ($dassessment_id, $delegation_list_id = NULL, $deleted_date = NULL) {
        $self = new self();
        $fetch_params = array();
        $fetch_params[] = array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id);
        if ($delegation_list_id) {
            $fetch_params[] = array("key" => "delegation_list_id", "method" => ($delegation_list_id ? "=" : "IS NOT"), "value" => ($delegation_list_id ? $delegation_list_id : NULL));
        }
        $fetch_params[] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));

        return $self->fetchAll($fetch_params);
    }

    public static function fetchRowByTarget_value ($target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }
    public static function fetchRowByDAssessmentIDTargetTypeTargetValue ($dassessment_id = null, $target_type = null, $target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public function getTargetName() {
        global $db;
        $name = "N/A";
        $query = "SELECT `firstname`, `lastname` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE id = ?";

        $result = $db->GetRow($query, array($this->gettargetValue()));
        if ($result) {
            $name = $result["firstname"] . " " . $result["lastname"];
        }

        return $name;
    }

    public function getTargetEmail() {
        global $db;
        $email = "N/A";
        $query = "SELECT `email` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE id = ?";

        $result = $db->GetRow($query, array($this->gettargetValue()));
        if ($result) {
            $email = $result["email"];
        }

        return $email;
    }

    public static function fetchAllByDistributionIDTargetTypeTargetValue ($distribution_id = null, $target_type = "proxy_id", $target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function getAllByTargetValue($target_id, $group_by_distribution = true) {
        global $db;
        $assessment_targets = array();

        $GROUP_BY_distribution = "";
        if ($group_by_distribution) {
            $GROUP_BY_distribution = " GROUP BY `adistribution_id`";
        }

        $query = "SELECT * FROM `cbl_distribution_assessment_targets` 
                  WHERE `target_value` = ? 
                  $GROUP_BY_distribution ";

        $results = $db->GetAll($query, array($target_id));
        if ($results) {
            foreach ($results as $result) {
                $assessment_targets[] = new self($result);
            }
        }

        return $assessment_targets;
    }
}