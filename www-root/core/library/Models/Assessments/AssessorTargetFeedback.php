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

class Models_Assessments_AssessorTargetFeedback extends Models_Base {
    protected $atfeedback_id, $dassessment_id, $assessor_type, $assessor_value, $assessor_feedback, $target_type, $target_value, $target_feedback, $target_progress_value, $comments, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessor_target_feedback";
    protected static $primary_key = "atfeedback_id";
    protected static $default_sort_column = "updated_date";

    public function getID () {
        return $this->atfeedback_id;
    }

    public function getDassessmentID () {
        return $this->dassessment_id;
    }

    public function getAssessorType () {
        return $this->assessor_type;
    }

    public function getAssessorValue () {
        return $this->assessor_value;
    }

    public function getAssessorFeedback () {
        return $this->assessor_feedback;
    }

    public function getTargetType () {
        return $this->target_type;
    }

    public function getTargetValue () {
        return $this->target_value;
    }

    public function getTargetFeedback () {
        return $this->target_feedback;
    }

    public function getTargetProgressValue () {
        return $this->target_progress_value;
    }

    public function getComments () {
        return $this->comments;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public static function fetchRowByAssessorTarget ($dassessment_id = null, $assessor_type = null, $assessor_value = null, $target_type = null, $target_value = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "target_value", "value" => $target_value, "method" => "=")
        ));
    }

    public static function sendFeedbackNotification ($target_record_id = null, $aprogress_id = null, $assessor_id = null, $adistribution_id = null) {
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $distribution = Models_Assessments_Distribution::fetchRowByID($adistribution_id);
        if ($distribution) {
            $assessment_notice_period = Entrada_Settings::fetchValueByShortname("assessment_notice_period", $distribution->getOrganisationID());
            $assessment_notice_period = ((int)$assessment_notice_period ? (int)$assessment_notice_period : ONE_WEEK * 2);

            $notification_user = NotificationUser::get($target_record_id, "assessment_feedback", $aprogress_id, $assessor_id);
            if (!$notification_user) {
                $notification_user = NotificationUser::add($target_record_id, "assessment_feedback", $aprogress_id, $assessor_id);
                Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
            } else {
                $most_recent_notification = Notification::fetchMostRecentByNUserID($notification_user->getID());
                if ($most_recent_notification && $most_recent_notification->getSentDate() < (time() - $assessment_notice_period)) {
                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, (isset($most_recent_notification) && $most_recent_notification && $most_recent_notification->getID() ? $most_recent_notification->getID() : NULL));
                }
            }
        }
    }
}