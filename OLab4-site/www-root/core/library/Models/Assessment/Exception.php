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
 * A model for handling assessment grade weighting exceptions
 *
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 bitHeads, Inc.. All Rights Reserved.
 */

class Models_Assessment_Exception extends Models_Base {
    protected $aexception_id, $assessment_id, $proxy_id, $grade_weighting;

    protected static $table_name = "assessment_exceptions";
    protected static $primary_key = "aexception_id";
    protected static $default_sort_column = "assessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aexception_id;
    }

    public function getAexceptionID() {
        return $this->aexception_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getGradeWeighting() {
        return $this->grade_weighting;
    }

    public static function fetchRowByID($aexception_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aexception_id", "value" => $aexception_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "aexception_id", "value" => 0, "method" => ">=")));
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM `".DATABASE_NAME."`.`".static::$table_name."`
                    WHERE `aexception_id` = ?";

        $result = $db->Execute($query, array($this->aexception_id));

        if ($result) {
            return $result;
        }

        return false;
    }
}