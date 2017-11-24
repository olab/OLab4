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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Notification extends Models_Base {
    protected $anotification_id, $adistribution_id, $assessment_value, $assessment_type, $notified_value, $notified_type, $notification_id, $nuser_id, $notification_type, $schedule_id, $sent_date;

    protected static $table_name = "cbl_assessment_notifications";
    protected static $primary_key = "anotification_id";
    protected static $default_sort_column = "sent_date";
    protected static $default_sort_order = "DESC";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->anotification_id;
    }

    public function getAnotificationID() {
        return $this->anotification_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessmentValue() {
        return $this->assessment_value;
    }

    public function getAssessmentType() {
        return $this->assessment_type;
    }

    public function getNotifiedValue() {
        return $this->notified_value;
    }

    public function getNotifiedType() {
        return $this->notified_type;
    }

    public function getNotificationID() {
        return $this->notification_id;
    }

    public function getNuserID() {
        return $this->nuser_id;
    }

    public function getNotificationType() {
        return $this->notification_type;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function getSentDate() {
        return $this->sent_date;
    }

    public static function fetchRowByID($anotification_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "anotification_id", "value" => $anotification_id, "method" => "=")
        ));
    }

    public static function fetchAllByProxyIDAssessmentTypeForToday($proxy_id, $assessment_type) {
        global $db;

        $query = "  SELECT *
					FROM " . static::$table_name . "
					WHERE `notified_value`  = ?
					AND   `notified_type`   = 'proxy_id'
					AND   `assessment_type` = ?
					AND   `sent_date` >= UNIX_TIMESTAMP(CURDATE())";

        $results = $db->GetAll($query, array($proxy_id, $assessment_type));
        return $results;
    }

    public static function fetchAllByDistributionIDProxyID($adistribution_id, $proxy_id, $schedule_id = NULL, $external_user = false) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "notified_value", "value" => $proxy_id, "method" => "="),
            array("key" => "notified_type", "value" => ($external_user ? "external_assessor_id" : "proxy_id"), "method" => "=")
        );
        if ($schedule_id) {
            $constraints[] = array("key" => "schedule_id", "value" => $schedule_id, "method" => "=");
        }
        return $self->fetchAll($constraints, "=", "AND", "sent_date", "DESC");
    }

    public static function fetchAllByDAssessmentIDProxyID($dassessment_id, $proxy_id, $schedule_id = NULL, $external_user = false) {
        $self = new self();
        $constraints = array(
            array("key" => "dassessment_id", "value" => $dassessment_id, "method" => "="),
            array("key" => "notified_value", "value" => $proxy_id, "method" => "="),
            array("key" => "notified_type", "value" => ($external_user ? "external_assessor_id" : "proxy_id"), "method" => "=")
        );
        if ($schedule_id) {
            $constraints[] = array("key" => "schedule_id", "value" => $schedule_id, "method" => "=");
        }
        return $self->fetchAll($constraints, "=", "AND", "sent_date", "DESC");
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function sendFlaggingNotification ($target_record_id = null, $aprogress_id = null, $assessor_id = null, $adistribution_id = null) {
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $distribution = Models_Assessments_Distribution::fetchRowByID($adistribution_id);
        if ($distribution) {
            switch ($distribution->getFlaggingNotifications()) {
                case "reviewers" :
                    $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($adistribution_id);
                    if ($reviewers) {
                        foreach ($reviewers as $reviewer) {
                            $notification_user = NotificationUser::get($reviewer->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                            if (!$notification_user) {
                                $notification_user = NotificationUser::add($reviewer->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                if ($notification_user) {
                                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
                                }
                            } else {
                                Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, NULL);
                            }
                        }
                    }
                break;
                case "authors" :
                    $authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($adistribution_id);
                    if ($authors) {
                        foreach ($authors as $author) {
                            if ($author->getAuthorType() == "proxy_id") {
                                $notification_user = NotificationUser::get($author->getAuthorID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                if (!$notification_user) {
                                    $notification_user = NotificationUser::add($author->getAuthorID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                    if ($notification_user) {
                                        Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
                                    }
                                } else {
                                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, NULL);
                                }
                            }
                        }
                    }
                break;
                case "pcoordinators" :
                    if ($distribution->getCourseID()) {
                        $course = Models_Course::fetchRowByID($distribution->getCourseID());
                        if ($course->getPcoordID()) {
                            $notification_user = NotificationUser::get($course->getPcoordID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                            if (!$notification_user) {
                                $notification_user = NotificationUser::add($course->getPcoordID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                if ($notification_user) {
                                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
                                }
                            } else {
                                Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, NULL);
                            }
                        }
                        $pcoordinators = Models_Course_Contact::fetchAllByCourseIDContactType($distribution->getCourseID(), "pcoordinator");
                        if ($pcoordinators) {
                            foreach ($pcoordinators as $pcoordinator) {
                                $notification_user = NotificationUser::get($pcoordinator->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                if (!$notification_user) {
                                    $notification_user = NotificationUser::add($pcoordinator->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                    if ($notification_user) {
                                        Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
                                    }
                                } else {
                                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, NULL);
                                }
                            }
                        }
                    }
                break;
                case "directors" :
                    $directors = Models_Course_Contact::fetchAllByCourseIDContactType($distribution->getCourseID(), "director");
                    if ($directors) {
                        foreach ($directors as $director) {
                            $notification_user = NotificationUser::get($director->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                            if (!$notification_user) {
                                $notification_user = NotificationUser::add($director->getProxyID(), "assessment_flagged_response", $aprogress_id, $assessor_id);
                                if ($notification_user) {
                                    Notification::add($notification_user->getID(), $target_record_id, $aprogress_id);
                                }
                            } else {
                                Notification::add($notification_user->getID(), $target_record_id, $aprogress_id, NULL);
                            }
                        }
                    }
                break;
                case "disabled" :
                default :
                break;
            }
        }
    }
}