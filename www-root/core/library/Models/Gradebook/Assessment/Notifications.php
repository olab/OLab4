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
 * Model for handling notifications for assessments
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Notifications extends Models_Base {
    protected $at_notificaton_id, $assessment_id, $proxy_id, $updated_date, $updated_by;

    protected static $table_name = "assessment_notificatons";
    protected static $primary_key = "at_notificaton_id";
    protected static $default_sort_column = "assessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->at_notificaton_id;
    }

    public function getAtNotificatonID() {
        return $this->at_notificaton_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($at_notificaton_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "at_notificaton_id", "value" => $at_notificaton_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "at_notificaton_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByAssessmentID($assessment_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "assessment_id", "value" => $assessment_id, "method" => "=")));
    }

    public static function addNotificationsToAssessment($notify_list, $assessment_id, $course_id=0, $notify = true) {
        global $db, $ENTRADA_USER;

        if (!$assessment_id) {
            add_error("An error occurred while saving the notification list: Invalid assessment ID");
            return false;
        }

        $query = "DELETE FROM assessment_notificatons WHERE assessment_id=".$assessment_id;
        $db->execute($query);

        if (!$notify) {
            // Notifications disabled, our job is done here
            return true;
        }

        if ((!is_array($notify_list)) || (!count($notify_list))) {
            return false;
        }

        foreach ($notify_list as $notify) {
            if (!intval($notify)) {
                $contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $notify);

                if (is_array($contacts) && count($contacts)) {
                    foreach ($contacts as $contact) {
                        $query = "INSERT INTO assessment_notificatons SET 
                                assessment_id=" . $db->qstr($assessment_id) . ",
                                proxy_id=" . $db->qstr($contact->getProxyID()) . ",
                                updated_date=NOW(),
                                updated_by=" . $db->qstr($ENTRADA_USER->getID());

                        if (!$db->execute($query)) {
                            add_error("An error occurred while saving the notification list: ".$db->errorMsg());
                            return false;
                        }
                    }
                }
            } else {
                $query = "INSERT INTO assessment_notificatons SET 
                            assessment_id=". $db->qstr($assessment_id) .",
                            proxy_id=". $db->qstr($notify) .",
                            updated_date=NOW(),
                            updated_by=". $db->qstr($ENTRADA_USER->getID());
                
                if (!$db->Execute($query)) {
                    add_error("An error occurred while saving the notification list: ".$db->errorMsg());
                    return false;
                }
            }
        }

        return true;
    }
}