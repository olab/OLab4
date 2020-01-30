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
 * A model for handling assessment forms statistics
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Statistics extends Models_Base {

    protected $afstatistic_id;
    protected $course_id;
    protected $form_id;
    protected $proxy_id;
    protected $count;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessments_form_statistics";
    protected static $primary_key = "afstatistic_id";
    protected static $default_sort_column = "afstatistic_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afstatistic_id;
    }

    public function getAformStatID() {
        return $this->afstatistic_id;
    }

    public function setAformStatID($afstatistic_id) {
        $this->afstatistic_id = $afstatistic_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function setFormID($form_id) {
        $this->form_id = $form_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function setProxyID($proxy_id) {
        $this->proxy_id = $proxy_id;
    }

    public function getCount() {
        return $this->count;
    }

    public function setCount($count) {
        $this->count = $count;
    }

    public static function fetchRowByID($afstatistic_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afstatistic_id", "method" => "=", "value" => $afstatistic_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "afstatistic_id", "method" => ">=", "value" => 0)));
    }

    public static function truncate() {
        global $db;
        $query = "TRUNCATE `" . static::$table_name . "`";
        if (!$db->Execute($query)) {
            application_log("error", "Unable to truncate " . static::$table_name . ". DB said: " . $db->ErrorMsg());
        }
    }

    public static function getCompletedAssessmentCountByCourseIDFormIDProxyID($course_id, $form_id, $proxy_id) {
        global $db;

        $query = "SELECT `count` 
                  FROM `cbl_assessments_form_statistics`
                  WHERE `course_id` = ?
                  AND `form_id` = ?
                  AND `proxy_id` = ?";

        return intval($db->getOne($query, array($course_id, $form_id, $proxy_id)));
    }
}