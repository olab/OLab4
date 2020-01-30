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
 * Model for handling item responses flag.
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Flag extends Models_Base {
    protected $flag_id;
    protected $flag_value;
    protected $organisation_id;
    protected $ordering;
    protected $title;
    protected $description;
    protected $color;
    protected $visibility;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessments_lu_flags";
    protected static $primary_key = "flag_id";
    protected static $default_sort_column = "ordering";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->flag_id;
    }

    public function getFlagID() {
        return $this->flag_id;
    }

    public function setFlagID($flag_id) {
        $this->flag_id = $flag_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function setOrganisationID($organisation_id) {
        $this->organisation_id = $organisation_id;
    }

    public function getOrdering() {
        return $this->ordering;
    }

    public function setOrdering($ordering) {
        $this->ordering = $ordering;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getFlagValue() {
        return $this->flag_value;
    }

    public function setFlagValue($value) {
        $this->flag_value = $value;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getColor() {
        return $this->color;
    }

    public function setColor($color) {
        $this->color = $color;
    }

    public function getVisibility() {
        return $this->visibility;
    }

    public function setVisibility($visibility) {
        $this->visibility = $visibility;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($flag_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "flag_id", "method" => "=", "value" => $flag_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "flag_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (!$this->flag_id) {
            return false;
        }
        global $db;
        return $db->Execute("UPDATE `cbl_assessments_lu_flags` SET `deleted_date` = ? WHERE `flag_id` = ?", array(time(), $this->flag_id));
    }

    /**
     * Checked if any flags are set for a specified organisation and if so,
     * return true as the organisation uses custom set of flag, else return
     * false and the organisation uses a on/off flagging system
     *
     * @param $organisation_id
     * @return bool
     */
    public static function organisationUsesCustomFlags($organisation_id) {
        global $db;

        $query = "SELECT COUNT(`flag_id`) 
                  FROM `cbl_assessments_lu_flags` f 
                  WHERE f.`organisation_id` = ?
                  AND f.`deleted_date` IS NULL";

        return intval($db->getOne($query, $organisation_id)) ? true : false;
    }

    /**
     * Fetch all flags for the specified organisation.
     * Also optionally include global flags and optionally limit to public flags only.
     *
     * @param $organisation_id
     * @param $search_value
     * @param $include_default
     * @param $include_public
     * @param $include_global
     * @param $include_admin
     * @return array|bool
     */
    public static function fetchAllByOrganisation($organisation_id, $search_value = "", $include_default = true, $include_public = true, $include_global = true, $include_admin = false) {
        global $db;

        $constraints = array();

        $AND_organisation = "AND `organisation_id` = ?";
        $constraints[] = $organisation_id;
        if ($include_global) {
            $AND_organisation = "AND (`organisation_id` = ? OR `organisation_id` IS NULL)";
        }

        $AND_visibility = "";
        $visibilities = array();
        foreach (array("Admin" => $include_admin, "Default" => $include_default, "Public" => $include_public) as $type => $visibility_bool) {
            if ($visibility_bool) {
                $visibilities[] = " `visibility` = '$type' ";
            }
        }
        if (!empty($visibilities)) {
            $or_clause = implode(" OR ", $visibilities);
            $AND_visibility = " AND ($or_clause) ";
        }

        $AND_search_value = "";
        if ($search_value) {
            $AND_search_value = "AND `title` LIKE '%s?%s'";
            $constraints[] = $search_value;
        }

        $query = "SELECT * FROM `cbl_assessments_lu_flags` 
                  WHERE `deleted_date` IS NULL
                  $AND_organisation
                  $AND_visibility
                  $AND_search_value
                  ORDER BY `ordering` ASC";

        $results = $db->GetAll($query, $constraints);
        if (empty($results)) {
            return false;
        }
        $objects = array();
        foreach ($results as $result) {
            $self = new self();
            $objects[$result["flag_id"]] = $self->fromArray($result);
        }
        return $objects;
    }

    /**
     * Fetch all the flags for an organisation that match the flag_value.
     *
     * @param $organisation_id
     * @param $flag_value
     * @return array
     */
    public static function fetchAllByOrganisationFlagValue($organisation_id, $flag_value) {
        $self = new self();
        $constraints = array();
        $constraints[] = array("key" => "organisation_id", "method" => "=", "value" => $organisation_id);
        $constraints[] = array("key" => "flag_value", "method" => "=", "value" => $flag_value);
        $constraints[] = array("key" => "deleted_date", "method" => "IS", "value" => null);
        return $self->fetchAll($constraints);
    }

    /**
     * Find and return the next available flag order for a specified organisation
     *
     * @param $organisation_id
     * @return int
     */
    public static function fetchNextAvailableOrder($organisation_id) {
        global $db;

        $query = "SELECT MAX(`ordering`) 
                  FROM `cbl_assessments_lu_flags`
                  WHERE organisation_id = ?
                  AND deleted_date IS NULL";

        return intval($db->getOne($query, array($organisation_id))) + 1;
    }

    /**
     * Set the order of flag with id `flag_id` to `ordering`
     *
     * @param $flag_id
     * @param $ordering
     * @return mixed
     */
    public static function updateOrdering($flag_id, $ordering) {
        global $db;

        $query = "UPDATE `cbl_assessments_lu_flags` 
                  SET `ordering` = ? 
                  WHERE `flag_id` = ?";

        return $db->Execute($query, array($ordering, $flag_id));
    }

    /**
     * Return a list of flag title associated to its ID
     *
     * @param $organisation
     * @return array
     */
    public static function fetchOrganisationFlagsLabels($organisation) {
        $flags_label = array();
        $flags = self::fetchAllByOrganisation($organisation);

        foreach ($flags as $flag) {
            $flags_label[$flag->getID()] = $flag->getTitle();
        }

        return $flags_label;
    }
}
