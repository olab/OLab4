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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/
require_once("Classes/utility/SimpleCache.class.php");

/**
 * Class to model Notification instances including basic data and relationships to users/content
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 */
class Notification {
	private $notification_id;
	private $nuser_id;
	private $notification_body;
	private $proxy_id;
	private $sent;
	private $digest;
	private $sent_date;
    private $from_email;
    private $from_firstname;
    private $from_lastname;

	function __construct(	$notification_id,
                            $nuser_id,
                            $notification_body,
                            $proxy_id,
                            $sent,
                            $digest,
                            $sent_date,
                            $from_email = NULL,
                            $from_firstname = NULL,
                            $from_lastname = NULL) {

		$this->notification_id = $notification_id;
		$this->nuser_id = $nuser_id;
		$this->notification_body = $notification_body;
		$this->proxy_id = $proxy_id;
		$this->sent = $sent;
		$this->digest = $digest;
		$this->sent_date = $sent_date;
		$this->from_email = $from_email;
		$this->from_firstname = $from_firstname;
		$this->from_lastname = $from_lastname;

		//be sure to cache this whenever created.
		$cache = SimpleCache::getCache();
		$cache->set($this,"Notification",$this->notification_id);
	}

	/**
	 * Returns the id of the notification
	 * @return int
	 */
	public function getID() {
		return $this->notification_id;
	}

	/**
	 * Returns the nuser_id of the `notification_user` record for the user who was/will be sent the notification
	 * @return int
	 */
	public function getNotificationUserID() {
		return $this->nuser_id;
	}

	/**
	 * Returns the body of the notification email
	 * @return string
	 */
	public function getNotificationBody() {
		return $this->notification_body;
	}

	/**
	 * Returns the proxy of the person who made the change/comment which the notification is regarding
	 * @return int
	 */
	public function getProxyID() {
		return $this->proxy_id;
	}

	/**
	 * Returns a boolean value which represents whether the notification has been emailed to the recipient or not.
	 * @return bool
	 */
	public function getSentStatus() {
		return (bool) $this->sent;
	}

	/**
	 * Returns a unix timestamp which represents when the notification was emailed to the recipient .
	 * @return bool
	 */
	public function getDigest() {
		return $this->digest;
	}

	/**
	 * Returns a unix timestamp which represents when the notification was emailed to the recipient .
	 * @return bool
	 */
	public function getSentDate() {
		return $this->sent_date;
	}

    /**
     * Returns a string which represents the email address which will be displayed in the "from" field in received emails.
     * @return string
     */
    public function getFromEmail() {
        return $this->from_email;
    }

    /**
     * Returns a string which represents the first name which will be displayed in the "from" field in received emails.
     * @return string
     */
    public function getFromFirstname() {
        return $this->from_firstname;
    }

    /**
     * Returns a string which represents the last name which will be displayed in the "from" field in received emails.
     * @return string
     */
    public function getFromLastname() {
        return $this->from_lastname;
    }

	/**
	 * Returns an Notification specified by the provided ID
	 * @param int $notification_id
	 * @return Notification
	 */
	public static function get($notification_id) {
		global $db;
		$cache = SimpleCache::getCache();
		$notification = $cache->get("Notification",$notification_id);
		if (!$notification) {
			$query = "SELECT * FROM `notifications` WHERE `notification_id` = ".$db->qstr($notification_id);
			$result = $db->getRow($query);
			if ($result) {
				$notification = self::fromArray($result);
			}
		}
		return $notification;
	}

	/**
	 * Returns an Notification specified by the provided ID
	 * @param int $notification_id
	 * @return Notification
	 */
	public static function getAllPending($nuser_id, $digest = false) {
		global $db;
		$query = "SELECT * FROM `notifications`
					WHERE `nuser_id` = ".$db->qstr($nuser_id)."
					AND `sent` = 0
					".($digest ? "AND `digest` = 1" : "");
		$results = $db->getAll($query);
		if ($results) {
			$notifications = array();
			foreach ($results as $result) {
				$notifications[] = self::fromArray($result);
			}
			return $notifications;
		}
		return false;
	}

	/**
	 * Creates a new notification and returns its id.
	 *
	 * @param int $nuser_id
	 * @param int $proxy_id
	 * @param int|array $record_id
	 * @param int $subcontent_id
	 * @param bool $create_as_sent
	 * @return int $notification_id
	 */
	public static function add($nuser_id, $proxy_id, $record_id, $subcontent_id = 0, $create_as_sent = false) {
		global $db, $ENTRADA_TEMPLATE;

        $from_email = NULL;
        $from_firstname = NULL;
        $from_lastname = NULL;
		$notification_user = NotificationUser::getByID($nuser_id);
		if ($notification_user) {
			if ($notification_user->getDigestMode()) {
				$notification_body = $notification_user->getContentBody($record_id);
				$sent = false;

				$new_notification = array(	"nuser_id" => $nuser_id,
											"notification_body" => $notification_body,
											"proxy_id" => $proxy_id,
											"sent" => 0,
											"digest" => 1,
											"sent_date" => 0,
											"from_email" => $from_email,
											"from_firstname" => $from_firstname,
											"from_lastname" => $from_lastname);
				$db->AutoExecute("notifications", $new_notification, "INSERT");
				if (!($notification_id = $db->Insert_Id())) {
					application_log("error", "There was an issue attempting to add a notification record to the database. Database said: ".$db->ErrorMsg());
				} else {
					$new_notification["notification_id"] = $notification_id;
					$notification = self::fromArray($new_notification);
					$notification_user->setNextNotificationDate();
					return $notification;
				}
			} else {
				switch ($notification_user->getContentType()) {
                    case "logbook_rotation" :
                        $search = array("%AUTHOR_FULLNAME%",
                            "%OWNER_FULLNAME%",
                            "%ROTATION_NAME%",
                            "%CONTENT_BODY%",
                            "%URL%",
                            "%UNSUBSCRIBE_URL%",
                            "%APPLICATION_NAME%",
                            "%ENTRADA_URL%");
                        $replace = array(html_encode(get_account_data("wholename", $proxy_id)),
                            html_encode(get_account_data("wholename", $notification_user->getRecordProxyID())),
                            html_encode($notification_user->getContentTitle()),
                            html_encode($notification_user->getContentBody($record_id)),
                            html_encode($notification_user->getContentURL()),
                            html_encode(ENTRADA_URL . "/profile?section=notifications&id=" . $nuser_id . "&action=unsubscribe"),
                            html_encode(APPLICATION_NAME),
                            html_encode(ENTRADA_URL));
                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-logbook-rotation-" . ($notification_user->getProxyID() == $notification_user->getRecordProxyID() ? "student" : "admin") . ".xml");
                        $notification_body = str_replace($search, $replace, $notification_body);
                        break;
                    case "evaluation" :
                    case "evaluation_overdue" :
                        $query = "SELECT * FROM `evaluations` AS a
                                    JOIN `evaluation_forms` AS b
                                    ON a.`eform_id` = b.`eform_id`
                                    JOIN `evaluations_lu_targets` AS c
                                    ON b.`target_id` = c.`target_id`
                                    WHERE a.`evaluation_id` = " . $db->qstr($record_id);
                        $evaluation = $db->GetRow($query);
                        if ($evaluation) {
                            $search = array("%UC_CONTENT_TYPE_NAME%",
                                "%CONTENT_TYPE_NAME%",
                                "%CONTENT_TYPE_SHORTNAME%",
                                "%UC_CONTENT_TYPE_SHORTNAME%",
                                "%EVALUATOR_FULLNAME%",
                                "%CONTENT_TITLE%",
                                "%EVENT_TITLE%",
                                "%CONTENT_BODY%",
                                "%CONTENT_START%",
                                "%CONTENT_FINISH%",
                                "%MANDATORY_STRING%",
                                "%URL%",
                                "%APPLICATION_NAME%",
                                "%LOCKOUT_STRING%",
                                "%ENTRADA_URL%");
                            if (strpos($notification_user->getContentTypeName(), "assessment") !== false) {
                                $content_type_shortname = "assessment";
                            } else {
                                $content_type_shortname = "evaluation";
                            }
                            if (array_search($evaluation["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $subcontent_id && defined("CLERKSHIP_EVALUATION_LOCKOUT") && CLERKSHIP_EVALUATION_LOCKOUT) {
                                $query = "SELECT * FROM `" . CLERKSHIP_DATABASE . "`.`events` WHERE `event_id` = " . $db->qstr($subcontent_id);
                                $clerkship_event = $db->GetRow($query);
                                if ($clerkship_event) {
                                    if ($evaluation["target_shortname"] != "rotation_elective") {
                                        $evaluation["evaluation_start"] = ($clerkship_event["event_finish"] - (86400 * 5));
                                        $evaluation["evaluation_finish"] = $clerkship_event["event_finish"] + CLERKSHIP_EVALUATION_TIMEOUT;
                                    }
                                    $evaluation["evaluation_lockout"] = $clerkship_event["event_finish"] + CLERKSHIP_EVALUATION_LOCKOUT;
                                    $event_title = $clerkship_event["event_title"];
                                } else {
                                    $event_title = "";
                                    $evaluation["evaluation_lockout"] = $evaluation["evaluation_finish"];
                                }
                            } elseif (defined("EVALUATION_LOCKOUT") && EVALUATION_LOCKOUT) {
                                $event_title = "";
                                $evaluation["evaluation_lockout"] = $evaluation["evaluation_finish"] + (defined('EVALUATION_LOCKOUT') && EVALUATION_LOCKOUT ? EVALUATION_LOCKOUT : 0);
                            }
                            $mandatory = $evaluation["evaluation_mandatory"];
                            $evaluation_start = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_start"]);
                            $evaluation_finish = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_finish"]);
                            if (isset($evaluation["evaluation_lockout"])) {
                                $evaluation_lockout = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_lockout"]);
                            } else {
                                $evaluation_lockout = false;
                            }
                            $organisation_id = get_account_data("organisation_id", $proxy_id);
                            $content_url = $notification_user->getContentURL();
                            $replace = array(html_encode(ucwords($notification_user->getContentTypeName())),
                                html_encode($notification_user->getContentTypeName()),
                                html_encode($content_type_shortname),
                                html_encode(ucfirst($content_type_shortname)),
                                html_encode(get_account_data("wholename", $notification_user->getProxyID())),
                                html_encode($notification_user->getContentTitle()),
                                html_encode($event_title),
                                html_encode($notification_user->getContentBody($record_id)),
                                html_encode($evaluation_start),
                                html_encode($evaluation_finish),
                                html_encode((isset($mandatory) && $mandatory ? "mandatory" : "non-mandatory")),
                                html_encode($content_url),
                                html_encode(APPLICATION_NAME),
                                html_encode((isset($EVALUATION_LOCKOUT[$evaluation["organisation_id"]]) && $EVALUATION_LOCKOUT[$evaluation["organisation_id"]] ? "\nAccess to this evaluation will be closed as of " . $evaluation_lockout . "." : "")),
                                html_encode(ENTRADA_URL));
                            if ($evaluation["target_shortname"] == "rotation_core") {
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-rotation-core-evaluation-" . ($evaluation["evaluation_finish"] >= time() || $evaluation["evaluation_start"] >= strtotime("-1 day") ? "release" : "overdue") . ".xml");
                            } elseif ($evaluation["target_shortname"] == "preceptor") {
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-preceptor-evaluation-" . ($evaluation["evaluation_finish"] >= time() || $evaluation["evaluation_start"] >= strtotime("-1 day") ? "release" : "overdue") . ".xml");
                            } else {
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-evaluation-" . ($evaluation["evaluation_finish"] >= time() || $evaluation["evaluation_start"] >= strtotime("-1 day") ? "release" : "overdue") . ".xml");
                            }
                            $notification_body = str_replace($search, $replace, $notification_body);
                        }
                        break;
                    case "evaluation_threshold" :
                        $search = array("%UC_CONTENT_TYPE_NAME%",
                            "%CONTENT_TYPE_NAME%",
                            "%CONTENT_TYPE_SHORTNAME%",
                            "%EVALUATOR_FULLNAME%",
                            "%CONTENT_TITLE%",
                            "%URL%",
                            "%APPLICATION_NAME%",
                            "%ENTRADA_URL%");
                        if (strpos($notification_user->getContentTypeName(), "assessment") !== false) {
                            $content_type_shortname = "assessment";
                        } else {
                            $content_type_shortname = "evaluation";
                        }
                        $evaluation = $db->GetRow("SELECT * FROM `evaluations` WHERE `evaluation_id` = " . $db->qstr($notification_user->getRecordID()));
                        $evaluation_start = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_start"]);
                        $evaluation_finish = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_finish"]);
                        $replace = array(html_encode(ucwords($notification_user->getContentTypeName())),
                            html_encode($notification_user->getContentTypeName()),
                            html_encode($content_type_shortname),
                            html_encode(get_account_data("wholename", $proxy_id)),
                            html_encode($notification_user->getContentTitle()),
                            html_encode($notification_user->getContentURL() . "&pid=" . $record_id),
                            html_encode(APPLICATION_NAME),
                            html_encode(ENTRADA_URL));
                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-evaluation-threshold.xml");
                        $notification_body = str_replace($search, $replace, $notification_body);
                        break;
                    case "evaluation_request" :
                        $search = array("%UC_CONTENT_TYPE_NAME%",
                            "%CONTENT_TYPE_NAME%",
                            "%CONTENT_TYPE_SHORTNAME%",
                            "%TARGET_FULLNAME%",
                            "%CONTENT_TITLE%",
                            "%CONTENT_BODY%",
                            "%URL%",
                            "%APPLICATION_NAME%",
                            "%ENTRADA_URL%");
                        if (strpos($notification_user->getContentTypeName(), "assessment") !== false) {
                            $content_type_shortname = "assessment";
                        } else {
                            $content_type_shortname = "evaluation";
                        }
                        $evaluation = $db->GetRow("SELECT * FROM `evaluations` WHERE `evaluation_id` = " . $db->qstr($notification_user->getRecordID()));
                        $evaluation_start = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_start"]);
                        $evaluation_finish = date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_finish"]);
                        $replace = array(html_encode(ucwords($notification_user->getContentTypeName())),
                            html_encode($notification_user->getContentTypeName()),
                            html_encode($content_type_shortname),
                            html_encode(get_account_data("wholename", $proxy_id)),
                            html_encode($notification_user->getContentTitle()),
                            html_encode($notification_user->getContentBody($record_id)),
                            html_encode($notification_user->getContentURL() . "&proxy_id=" . $proxy_id),
                            html_encode(APPLICATION_NAME),
                            html_encode(ENTRADA_URL));
                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-evaluation-request.xml");
                        $notification_body = str_replace($search, $replace, $notification_body);
                        break;
					case "assessment" :
						$search = array("%TARGET_FIRSTNAME%",
							"%TARGET_LASTNAME%",
							"%URL%",
							"%USERNAME%",
							"%EMAIL%",
							"%ENTRADA_URL%",
							"%CREATOR_FIRSTNAME%",
							"%CREATOR_LASTNAME%",
							"%CREATOR_EMAIL%");
						$distribution_assessment = Models_Assessments_Assessor::fetchRowByID($record_id);
						if ($distribution_assessment) {
							$distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
							if ($distribution) {

								$is_external = ($distribution_assessment->getAssessorType() == "external") ? true : false;
								$targets = Models_Assessments_Distribution_Target::getAssessmentTargets($distribution->getID(), $distribution_assessment->getID(), $distribution_assessment->getAssessorValue(), $distribution_assessment->getAssessorValue(), $is_external);
								if ($targets) {
									$target_record_id = $targets[0]["target_record_id"];
                                    $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                                    $creator = Models_User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
									if ($creator) {
										$from_email = $creator->getEmail();
										$from_firstname = $creator->getFirstname();
										$from_lastname = $creator->getLastname();
										$url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $distribution->getID() . "&target_record_id=" . $target_record_id . "&dassessment_id=" . $distribution_assessment->getID();
										$user = array();
										if ($notification_user->getNotificationUserType() == "proxy_id") {
											$tmp_user = Models_User::fetchRowByID($proxy_id);
											$user["firstname"] = $tmp_user->getFirstname();
											$user["lastname"] = $tmp_user->getLastname();
											$user["username"] = $tmp_user->getUsername();
											$user["email"] = $tmp_user->getEmail();
										} else {
											$tmp_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($proxy_id);
											$user["firstname"] = $tmp_assessor->getFirstname();
											$user["lastname"] = $tmp_assessor->getLastname();
											$user["username"] = "";
											$user["email"] = $tmp_assessor->getEmail();
											$hash = $distribution_assessment->getExternalHash();
											$url = ENTRADA_URL . "/assessment?adistribution_id=" . $distribution->getID() . "&dassessment_id=" . $distribution_assessment->getID() . (isset($hash) && $hash ? "&external_hash=" . $hash : "")."&assessor_value=".$proxy_id;
										}
										$replace = array(
											html_encode($user["firstname"]),
											html_encode($user["lastname"]),
											html_encode($url),
											html_encode($user["username"]),
											html_encode($user["email"]),
											html_encode(ENTRADA_URL),
											html_encode($creator->getFirstname()),
											html_encode($creator->getLastname()),
											html_encode($creator->getEmail()));

										if ($notification_user->getNotificationUserType() == "proxy_id") {
											if ($subcontent_id) {
												$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-reminder.xml");
											} else {
												$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-new.xml");
											}
										} else {
											if ($subcontent_id) {
												$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-external-reminder.xml");
											} else {
												$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-external-new.xml");
											}
										}
										$notification_body = str_replace($search, $replace, $notification_body);
									}
								}
							}
						}
						break;
                    case "assessment_general" :
                        $search = array(
                            "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%ENTRADA_URL%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%APPLICATION_NAME%"
                        );
                        $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($record_id);
                        if ($distribution_assessment) {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
                            if ($distribution) {
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                                $creator = Models_User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
                                if ($creator) {
                                    $from_email = $creator->getEmail();
                                    $from_firstname = $creator->getFirstname();
                                    $from_lastname = $creator->getLastname();
                                }
                                $user = Models_User::fetchRowByID($proxy_id);
                                if ($user) {
                                    $replace = array(
                                        html_encode($user->getFirstname()),
                                        html_encode($user->getLastname()),
                                        html_encode(ENTRADA_URL),
                                        html_encode($user->getUsername()),
                                        html_encode($user->getEmail()),
                                        html_encode(ENTRADA_URL),
                                        html_encode(APPLICATION_NAME)
                                    );
                                    if ($subcontent_id) {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-reminder-general.xml");
                                    } else {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-new-general.xml");
                                    }
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                } else {
                                    application_log("error", "Notification failed: User not found.");
                                }
                            } else {
                                application_log("error", "Notification failed: Distribution not found.");
                            }
                        } else {
                            application_log("error", "Notification failed: Distribution assessment not found.");
                        }
                        break;
                    case "assessment_approver" :
                        $search = array(
                            "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%URL%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%CREATOR_FIRSTNAME%",
                            "%CREATOR_LASTNAME%",
                            "%CREATOR_EMAIL%"
                        );
                        
                        $progress = Models_Assessments_Progress::fetchRowByDassessmentID($record_id);
                        if ($progress && $progress->getProgressValue() == "complete") {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($progress->getAdistributionID());
                            if ($distribution) {
                                $target_record_id = $progress->getTargetRecordID();
                                $creator = Models_User::fetchRowByID($distribution->getCreatedBy());
                                $approver = Models_User::fetchRowByID($proxy_id);

                                if ($creator && $approver) {
                                    $from_email = $creator->getEmail();
                                    $from_firstname = $creator->getFirstname();
                                    $from_lastname = $creator->getLastname();
                                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $progress->getAdistributionID() . "&target_record_id=" . $target_record_id . "&dassessment_id=" . $progress->getDAssessmentID() . "&approver_task=true";
                                    
                                    $replace = array(
                                        html_encode($approver->getFirstname()),
                                        html_encode($approver->getLastname()),
                                        html_encode($url),
                                        html_encode($approver->getUsername()),
                                        html_encode($approver->getEmail()),
                                        html_encode(ENTRADA_URL),
                                        html_encode($creator->getFirstname()),
                                        html_encode($creator->getLastname()),
                                        html_encode($creator->getEmail())
                                    );

                                    $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-approver-reminder.xml");
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                }
                            }
                        }
                        break;
                    case "assessment_delegation" :
						$search = array("%TARGET_FIRSTNAME%",
										"%TARGET_LASTNAME%",
										"%URL%",
										"%USERNAME%",
										"%EMAIL%",
										"%ENTRADA_URL%",
										"%CREATOR_FIRSTNAME%",
										"%CREATOR_LASTNAME%",
										"%CREATOR_EMAIL%");

						// $record_id is actually an array in this case.
						$adistribution_id = @$record_id["adistribution_id"];
						$addelegation_id = @$record_id["addelegation_id"];
						if ($adistribution_id && $addelegation_id) {
							$distribution = Models_Assessments_Distribution::fetchRowByID($adistribution_id);
							if ($distribution) {
								$creator = User::fetchRowByID($distribution->getCreatedBy());
								if ($creator) {
									$from_email = $creator->getEmail();
									$from_firstname = $creator->getFirstname();
									$from_lastname = $creator->getLastname();
									$user = User::fetchRowByID($proxy_id);
									$url = ENTRADA_URL . "/assessments/delegation?adistribution_id=$adistribution_id&addelegation_id=$addelegation_id";
									$replace = array(
										html_encode($user->getFirstname()),
										html_encode($user->getLastname()),
										html_encode($url),
										html_encode($user->getUsername()),
										html_encode($user->getEmail()),
										html_encode(ENTRADA_URL),
										html_encode($creator->getFirstname()),
										html_encode($creator->getLastname()),
										html_encode($creator->getEmail()));
									if ($subcontent_id) {
										$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-delegation-reminder.xml");
									} else {
										$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-delegation-new.xml");
									}
									$notification_body = str_replace($search, $replace, $notification_body);
								}
							}
						} else {
							application_log("error", "Notification failed: record_id specifid was invald (record_id = '" . print_r($record_id, true) ."'). proxy = '$proxy_id', nuser_id = '$nuser_id'");
						}
					    break;
                    case "assessment_delegation_general" :
                        $search = array(
                            "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%ENTRADA_URL%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%APPLICATION_NAME%"
                        );
                        // $record_id is actually an array in this case.
                        $adistribution_id = @$record_id["adistribution_id"];
                        if ($adistribution_id) {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($adistribution_id);
                            if ($distribution) {
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                                $creator = Models_User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
                                if ($creator) {
                                    $from_email = $creator->getEmail();
                                    $from_firstname = $creator->getFirstname();
                                    $from_lastname = $creator->getLastname();
                                }
                                $user = User::fetchRowByID($proxy_id);
                                if ($user) {
                                    $replace = array(
                                        html_encode($user->getFirstname()),
                                        html_encode($user->getLastname()),
                                        html_encode(ENTRADA_URL),
                                        html_encode($user->getUsername()),
                                        html_encode($user->getEmail()),
                                        html_encode(ENTRADA_URL),
                                        html_encode(APPLICATION_NAME)
                                    );
                                    if ($subcontent_id) {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-delegation-reminder-general.xml");
                                    } else {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-delegation-new-general.xml");
                                    }
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                } else {
                                    application_log("error", "Notification failed: User not found.");
                                }
                            } else {
                                application_log("error", "Notification failed: Distribution not found.");
                            }
                        } else {
                            application_log("error", "Notification failed: Distribution id specified was invalid.");
                        }
                        break;
					case "assessment_delegation_assignment_removed":
						$search = array("%TARGET_FIRSTNAME%",
							"%TARGET_LASTNAME%",
							"%ASSESSMENT_NAME%",
							"%ASSESSMENT_DATE%",
							"%URL%",
							"%USERNAME%",
							"%EMAIL%",
							"%ENTRADA_URL%",
							"%CREATOR_FIRSTNAME%",
							"%CREATOR_LASTNAME%",
							"%CREATOR_EMAIL%");

						$notification_body = NULL;

						$delegation_assignment = Models_Assessments_Distribution_DelegationAssignment::fetchRowByID($subcontent_id);
						if ($delegation_assignment) {
							$distribution_assessment = Models_Assessments_Assessor::fetchRowByID($delegation_assignment->getDassessmentID(), null, true);
							if ($distribution_assessment) {
								$distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
								if ($distribution) {
									$creator = Models_User::fetchRowByID($distribution->getCreatedBy());
									if ($creator) {
										$from_email = $creator->getEmail();
										$from_firstname = $creator->getFirstname();
										$from_lastname = $creator->getLastname();
										$user = array();
										if ($notification_user->getNotificationUserType() == "proxy_id") {
											$tmp_user = Models_User::fetchRowByID($proxy_id);
											$user["firstname"] = $tmp_user->getFirstname();
											$user["lastname"] = $tmp_user->getLastname();
											$user["username"] = $tmp_user->getUsername();
											$user["email"] = $tmp_user->getEmail();
										} else {
											$tmp_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($proxy_id);
											$user["firstname"] = $tmp_assessor->getFirstname();
											$user["lastname"] = $tmp_assessor->getLastname();
											$user["username"] = "";
											$user["email"] = $tmp_assessor->getEmail();
										}
										$replace = array(
											html_encode($user["firstname"]),
											html_encode($user["lastname"]),
											html_encode($distribution->getTitle()),
											html_encode(date(DEFAULT_DATE_FORMAT, $distribution_assessment->getCreatedDate())),
											html_encode(ENTRADA_URL . "/assessments"),
											html_encode($user["username"]),
											html_encode($user["email"]),
											html_encode(ENTRADA_URL),
											html_encode($creator->getFirstname()),
											html_encode($creator->getLastname()),
											html_encode($creator->getEmail()));

										if ($notification_user->getNotificationUserType() == "proxy_id") {
											$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-removed.xml");
										} else {
											$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-external-removed.xml");
										}
										$notification_body = str_replace($search, $replace, $notification_body);
									}
								}
							}
						}
					break;
					case "assessment_removal" :
						$search = array("%TARGET_FIRSTNAME%",
										"%TARGET_LASTNAME%",
										"%ASSESSMENT_NAME%",
										"%ASSESSMENT_DATE%",
										"%URL%",
										"%USERNAME%",
										"%EMAIL%",
										"%ENTRADA_URL%",
										"%CREATOR_FIRSTNAME%",
										"%CREATOR_LASTNAME%",
										"%CREATOR_EMAIL%");

                        $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($record_id, time());
                        if ($distribution_assessment) {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
                            if ($distribution) {
                                $creator = Models_User::fetchRowByID($distribution->getCreatedBy());
                                if ($creator) {
                                    $from_email = $creator->getEmail();
                                    $from_firstname = $creator->getFirstname();
                                    $from_lastname = $creator->getLastname();
                                    $user = array();
                                    if ($notification_user->getNotificationUserType() == "proxy_id") {
                                        $tmp_user = Models_User::fetchRowByID($proxy_id);
                                        $user["firstname"] = $tmp_user->getFirstname();
                                        $user["lastname"] = $tmp_user->getLastname();
                                        $user["username"] = $tmp_user->getUsername();
                                        $user["email"] = $tmp_user->getEmail();
                                    } else {
                                        $tmp_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($proxy_id);
                                        $user["firstname"] = $tmp_assessor->getFirstname();
                                        $user["lastname"] = $tmp_assessor->getLastname();
                                        $user["username"] = "";
                                        $user["email"] = $tmp_assessor->getEmail();
                                    }
                                    $replace = array(
                                        html_encode($user["firstname"]),
                                        html_encode($user["lastname"]),
                                        html_encode($distribution->getTitle()),
                                        html_encode(date(DEFAULT_DATE_FORMAT, $distribution_assessment->getCreatedDate())),
                                        html_encode(ENTRADA_URL . "/assessments"),
                                        html_encode($user["username"]),
                                        html_encode($user["email"]),
                                        html_encode(ENTRADA_URL),
                                        html_encode($creator->getFirstname()),
                                        html_encode($creator->getLastname()),
                                        html_encode($creator->getEmail()));

                                    if ($notification_user->getNotificationUserType() == "proxy_id") {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-removed.xml");
                                    } else {
                                        $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-external-removed.xml");
                                    }
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                }
                            }
						}
					break;
                    case "assessment_feedback" :
                        $search = array("%TARGET_FIRSTNAME%",
                                        "%TARGET_LASTNAME%",
                                        "%URL%",
                                        "%USERNAME%",
                                        "%EMAIL%",
                                        "%ENTRADA_URL%",
                                        "%APPLICATION_NAME%");
                        $progress_record = Models_Assessments_Progress::fetchRowByID($record_id);
                        if ($progress_record) {
                            $assessor_id = false;
                            if ($progress_record->getAssessorType() == "internal") {
                                $assessor_id = $progress_record->getAssessorValue();
								$creator = User::fetchRowByID($assessor_id);
								if ($creator) {
									$from_email = $creator->getEmail();
									$from_firstname = $creator->getFirstname();
									$from_lastname = $creator->getLastname();
								}
                            } else {
                                $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($progress_record->getAssessorValue());
                                if ($external_assessor) {
                                    $assessor_id = $external_assessor->getID();
									$from_email = $external_assessor->getEmail();
									$from_firstname = $external_assessor->getFirstname();
									$from_lastname = $external_assessor->getLastname();
                                }
                            }

                            $user = User::fetchRowByID($proxy_id);
                            if ($user && $assessor_id) {
                                $replace = array(
                                    html_encode($user->getFirstname()),
                                    html_encode($user->getLastname()),
                                    html_encode(ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $progress_record->getAdistributionID() . "&target_record_id=" . $proxy_id . "&aprogress_id=" . $record_id . "&dassessment_id=" . $progress_record->getDassessmentID()),
                                    html_encode($user->getUsername()),
                                    html_encode($user->getEmail()),
                                    html_encode(ENTRADA_URL),
                                    html_encode(APPLICATION_NAME));
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-feedback.xml");
                                $notification_body = str_replace($search, $replace, $notification_body);
                            }
                        }
                    break;
					case "assessment_submitted" :
						$search = array(
						    "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%SUBMITTED_BY%",
                            "%URL%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%CREATOR_FIRSTNAME%",
                            "%CREATOR_LASTNAME%",
                            "%CREATOR_EMAIL%"
                        );
						$distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($record_id);
						if ($distribution) {
                            $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                            $creator = Models_User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
                            $target = User::fetchRowByID($proxy_id);
                            if ($creator && $target) {
								$from_email = $creator->getEmail();
								$from_firstname = $creator->getFirstname();
								$from_lastname = $creator->getLastname();
                                $submitted_by = User::fetchRowByID($subcontent_id);

								$replace = array(
									html_encode($target->getFirstname()),
									html_encode($target->getLastname()),
									html_encode($submitted_by->getFirstname()." ".$submitted_by->getLastname()),
									html_encode(ENTRADA_URL . "/assessments" . ($delegator ? "/delegation?adistribution_id=" . $distribution->getID() : "")),
									html_encode($target->getUsername()),
									html_encode($target->getEmail()),
									html_encode(ENTRADA_URL),
									html_encode($creator->getFirstname()),
									html_encode($creator->getLastname()),
									html_encode($creator->getEmail())
                                );
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-submitted.xml");
                                $notification_body = str_replace($search, $replace, $notification_body);
							}
						}
					break;
					case "assessment_submitted_notify_approver" :
						$search = array(
							"%TARGET_FIRSTNAME%",
							"%TARGET_LASTNAME%",
							"%SUBMITTED_BY%",
							"%URL%",
							"%USERNAME%",
							"%EMAIL%",
							"%ENTRADA_URL%"
						);
                        $progress_record = Models_Assessments_Progress::fetchRowByID($record_id);
                        if ($progress_record) {
                            $distribution_approver = new Models_Assessments_Distribution_Approver();
                            $approver_record = $distribution_approver->fetchRowByProxyIDDistributionID($proxy_id, $progress_record->getAdistributionID());
                            if ($approver_record) {
                                $assessor = User::fetchRowByID($subcontent_id);
                                $approver = User::fetchRowByID($proxy_id);
                                if ($assessor && $approver) {
                                    $from_email = $assessor->getEmail();
                                    $from_firstname = $assessor->getFirstname();
                                    $from_lastname = $assessor->getLastname();
                                    $replace = array(
                                        html_encode($approver->getFirstname()),
                                        html_encode($approver->getLastname()),
                                        html_encode($assessor->getFirstname() . " " . $assessor->getLastname()),
                                        html_encode(ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $progress_record->getAdistributionID() . "&target_record_id=" . $progress_record->getTargetRecordID() . "&aprogress_id=" . $record_id . "&dassessment_id=" . $progress_record->getDassessmentID() . "&approver_task=true"),
                                        html_encode($approver->getUsername()),
                                        html_encode($approver->getEmail()),
                                        html_encode(ENTRADA_URL)
                                    );
                                    $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-submitted-with-approver.xml");
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                }
                            }
                        }
						break;
                    case "assessment_submitted_notify_learner" :
                        $search = array(
                            "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%SUBMITTED_BY%",
                            "%URL%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%CREATOR_FIRSTNAME%",
                            "%CREATOR_LASTNAME%",
                            "%CREATOR_EMAIL%"
                        );
                        $progress_record = Models_Assessments_Progress::fetchRowByID($record_id);
                        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($progress_record->getAdistributionID());
                        if ($progress_record && $distribution) {
                            $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                            $creator = Models_User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
                            $target = User::fetchRowByID($progress_record->getTargetRecordID());
                            if ($creator && $target) {
                                $from_email = $creator->getEmail();
                                $from_firstname = $creator->getFirstname();
                                $from_lastname = $creator->getLastname();
                                $submitted_by = User::fetchRowByID($progress_record->getAssessorValue());
                                $replace = array(
                                    html_encode($target->getFirstname()),
                                    html_encode($target->getLastname()),
                                    html_encode($submitted_by->getFirstname() . " " . $submitted_by->getLastname()),
                                    html_encode(ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $progress_record->getAdistributionID() . "&target_record_id=" . $progress_record->getTargetRecordID() . "&aprogress_id=" . $record_id . "&dassessment_id=" . $progress_record->getDassessmentID()),
                                    html_encode($target->getUsername()),
                                    html_encode($target->getEmail()),
                                    html_encode(ENTRADA_URL),
                                    html_encode($creator->getFirstname()),
                                    html_encode($creator->getLastname()),
                                    html_encode($creator->getEmail())
                                );
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-submitted-with-learner.xml");
                                $notification_body = str_replace($search, $replace, $notification_body);
                            }
                        }
                        break;
					case "delegated_assessment_task_deleted" :
						$search = array("%TARGET_FIRSTNAME%",
							"%TARGET_LASTNAME%",
							"%SUBMITTED_BY%",
							"%URL%",
							"%REASON%",
							"%NOTES%",
							"%USERNAME%",
							"%EMAIL%",
							"%ENTRADA_URL%",
							"%CREATOR_FIRSTNAME%",
							"%CREATOR_LASTNAME%",
							"%CREATOR_EMAIL%");

						$delegation_assignment = Models_Assessments_Distribution_DelegationAssignment::fetchRowByID($record_id);
						if ($delegation_assignment) {
							$assessment = Models_Assessments_Assessor::fetchRowByID($delegation_assignment->getDassessmentID(), null, true);
							if ($assessment) {
								$distribution = Models_Assessments_Distribution::fetchRowByID($assessment->getADistributionID());
								$assessor_id = false;
								$assessor_firstname = "";
								$assessor_lastname = "";
								if ($assessment->getAssessorType() == "internal") {
									$assessor_id = $assessment->getAssessorValue();
									$internal_assessor = Models_User::fetchRowByID($assessor_id);
									if ($internal_assessor) {
										$assessor_firstname = $internal_assessor->getFirstname();
										$assessor_lastname = $internal_assessor->getLastName();
									}
								} else {
									$external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessment->getAssessorValue());
									if ($external_assessor) {
										$assessor_id = $external_assessor->getID();
										$assessor_firstname = $external_assessor->getFirstname();
										$assessor_lastname = $external_assessor->getLastname();
									}
								}
								if ($delegation_assignment->getDeletedReasonID()) {
									$reason_type = Models_Assessments_TaskDeletedReason::fetchRowByID($delegation_assignment->getDeletedReasonID());
									$reason_type_text = $reason_type->getDetails();
								} else {
									$reason_type_text = "";
								}
								$creator = User::fetchRowByID($distribution->getCreatedBy());
								if ($creator && $assessor_id) {
									$from_email = $creator->getEmail();
									$from_firstname = $creator->getFirstname();
									$from_lastname = $creator->getLastname();
									$submitted_by = User::fetchRowByID($notification_user->getProxyID());
									$replace = array(
										html_encode($assessor_firstname),
										html_encode($assessor_lastname),
										html_encode($submitted_by->getFirstname()." ".$submitted_by->getLastname()),
										html_encode(ENTRADA_URL . "/assessments"),
										html_encode(html_encode($reason_type_text)),
										html_encode(html_encode($delegation_assignment->getDeletedReason())),
										html_encode(ENTRADA_URL),
										html_encode($creator->getFirstname()),
										html_encode($creator->getLastname()),
										html_encode($creator->getEmail()));
									$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-task-deleted.xml");
									$notification_body = str_replace($search, $replace, $notification_body);
								}
							}
						}
						break;
                    case "assessment_task_deleted" :
						$search = array("%TARGET_FIRSTNAME%",
										"%TARGET_LASTNAME%",
										"%SUBMITTED_BY%",
                                        "%URL%",
										"%REASON%",
                                        "%NOTES%",
										"%USERNAME%",
										"%EMAIL%",
										"%ENTRADA_URL%",
										"%CREATOR_FIRSTNAME%",
										"%CREATOR_LASTNAME%",
										"%CREATOR_EMAIL%");
						$assessment = Models_Assessments_Assessor::fetchRowByID($record_id, null, true);
						if ($assessment) {
                        	$distribution = Models_Assessments_Distribution::fetchRowByID($assessment->getADistributionID());
                            $deleted_task = Models_Assessments_DeletedTask::fetchRowByID($subcontent_id);
							$assessor_id = false;
							$assessor_firstname = "";
							$assessor_lastname = "";
							if ($deleted_task->getAssessorType() == "internal") {
								$assessor_id = $deleted_task->getAssessorValue();
								$internal_assessor = Models_User::fetchRowByID($assessor_id);
								if ($internal_assessor) {
									$assessor_firstname = $internal_assessor->getFirstname();
									$assessor_lastname = $internal_assessor->getLastName();
								}
							} else {
								$external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($deleted_task->getAssessorValue());
								if ($external_assessor) {
									$assessor_id = $external_assessor->getID();
									$assessor_firstname = $external_assessor->getFirstname();
									$assessor_lastname = $external_assessor->getLastname();
								}
							}
                            $creator = User::fetchRowByID($distribution->getCreatedBy());
							if ($creator && $deleted_task && $assessor_id) {
								$from_email = $creator->getEmail();
								$from_firstname = $creator->getFirstname();
								$from_lastname = $creator->getLastname();
								$reason = Models_Assessments_TaskDeletedReason::fetchRowByID($deleted_task->getDeletedReasonID());
                                $submitted_by = User::fetchRowByID($notification_user->getProxyID());
								$replace = array(
									html_encode($assessor_firstname),
									html_encode($assessor_lastname),
									html_encode($submitted_by->getFirstname()." ".$submitted_by->getLastname()),
                                    html_encode(ENTRADA_URL . "/assessments"),
                                    html_encode((isset($reason) && $reason ? $reason->getDetails() : "")),
                                    html_encode($deleted_task->getDeletedReasonNotes() ? $deleted_task->getDeletedReasonNotes() : ""),
									html_encode(ENTRADA_URL),
									html_encode($creator->getFirstname()),
									html_encode($creator->getLastname()),
									html_encode($creator->getEmail()));
                                    $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-task-deleted.xml");
								    $notification_body = str_replace($search, $replace, $notification_body);
							}
						}
					break;
                    case "delegation_task_deleted" :
                        $search = array(
                            "%TARGET_FIRSTNAME%",
                            "%TARGET_LASTNAME%",
                            "%SUBMITTED_BY%",
                            "%URL%",
                            "%REASON%",
                            "%NOTES%",
                            "%USERNAME%",
                            "%EMAIL%",
                            "%ENTRADA_URL%",
                            "%CREATOR_FIRSTNAME%",
                            "%CREATOR_LASTNAME%",
                            "%CREATOR_EMAIL%"
                        );

                        $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($record_id);
                        if ($delegation) {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($delegation->getDistributionID());
                            $deleted_task = Models_Assessments_DeletedTask::fetchRowByID($subcontent_id);

                            if ($distribution && $deleted_task) {
                                $assessor = Models_User::fetchRowByID($deleted_task->getAssessorValue());
                                $creator = Models_User::fetchRowByID($distribution->getCreatedBy());
                                $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($deleted_task->getDeletedReasonID());
                                $submitted_by = Models_User::fetchRowByID($notification_user->getProxyID());

                                if ($assessor && $creator && $reason && $submitted_by) {
                                    $from_email = $creator->getEmail();
                                    $from_firstname = $creator->getFirstname();
                                    $from_lastname = $creator->getLastname();
                                    $replace = array(
                                        html_encode($assessor->getFirstname()),
                                        html_encode($assessor->getLastName()),
                                        html_encode($submitted_by->getFirstname() . " " . $submitted_by->getLastname()),
                                        html_encode(ENTRADA_URL . "/assessments"),
                                        html_encode((isset($reason) && $reason ? $reason->getDetails() : "")),
                                        html_encode($deleted_task->getDeletedReasonNotes() ? $deleted_task->getDeletedReasonNotes() : ""),
                                        html_encode(ENTRADA_URL),
                                        html_encode($creator->getFirstname()),
                                        html_encode($creator->getLastname()),
                                        html_encode($creator->getEmail()));
                                    $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-task-deleted.xml");
                                    $notification_body = str_replace($search, $replace, $notification_body);
                                }
                            }
                        }
                        break;
                    case "assessment_flagged_response" :
                        $search = array("%ASSESSOR_FULLNAME%",
                            "%TARGET_TITLE%",
                            "%CONTENT_TITLE%",
                            "%URL%",
                            "%ENTRADA_URL%",
                            "%APPLICATION_NAME%");
                        $assessor_fullname = "";
                        $progress_record = Models_Assessments_Progress::fetchRowByID($record_id);
                        if ($progress_record) {
                            $distribution = Models_Assessments_Distribution::fetchRowByID($progress_record->getADistributionID());
                            $distribution_name = "N/A";
                            if ($distribution) {
                                $distribution_name = $distribution->getTitle();
                            }
                            $assessor_id = false;
                            if ($progress_record->getAssessorType() == "internal") {
                                $assessor_id = $progress_record->getAssessorValue();
                                $internal_assessor = Models_User::fetchRowByID($assessor_id);
                                if ($internal_assessor) {
                                    $assessor_fullname = $internal_assessor->getFirstname()." ".$internal_assessor->getLastname();
                                }
                            } else {
                                $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($progress_record->getAssessorValue());
                                if ($external_assessor) {
                                    $assessor_id = $external_assessor->getID();
                                    $assessor_fullname = $external_assessor->getFirstname()." ".$external_assessor->getLastname();
                                }
                            }

                            $target_name = "N/A";
                            $target_record = Models_Assessments_Distribution_Target::fetchRowByID($progress_record->getAdtargetID());
                            if ($target_record) {
                                $target_name = $target_record->getTargetName($proxy_id);
                            }

                            if ($assessor_id) {
                                $replace = array(
                                    html_encode($assessor_fullname),
                                    html_encode($target_name),
                                    html_encode($distribution_name),
                                    html_encode(ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $progress_record->getAdistributionID() . "&target_record_id=" . $proxy_id . "&aprogress_id=" . $record_id . "&dassessment_id=" . $progress_record->getDassessmentID()),
                                    html_encode(ENTRADA_URL),
                                    html_encode(APPLICATION_NAME));
                                $notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute() . "/email/notification-assessment-flagged-notification.xml");
                                $notification_body = str_replace($search, $replace, $notification_body);
                            }
                        }
                        break;
					default :
						$search = array("%UC_CONTENT_TYPE_NAME%",
										"%CONTENT_TYPE_NAME%",
										"%AUTHOR_FULLNAME%",
										"%CONTENT_TITLE%",
										"%CONTENT_BODY%",
										"%URL%",
										"%UNSUBSCRIBE_URL%",
										"%DIGEST_URL%",
										"%APPLICATION_NAME%",
										"%ENTRADA_URL%");

						$replace = array(	html_encode(ucwords($notification_user->getContentTypeName())),
											html_encode($notification_user->getContentTypeName()),
											html_encode(get_account_data("wholename", $proxy_id)),
											html_encode($notification_user->getContentTitle()),
											html_encode($notification_user->getContentBody($record_id)),
											html_encode($notification_user->getContentURL()),
											html_encode(ENTRADA_URL."/profile?section=notifications&id=".$nuser_id."&action=unsubscribe"),
											html_encode(ENTRADA_URL."/profile?section=notifications&id=".$nuser_id."&action=digest-mode"),
											html_encode(APPLICATION_NAME),
											html_encode(ENTRADA_URL));
						$notification_body = file_get_contents($ENTRADA_TEMPLATE->absolute()."/email/notification-default.xml");
						$notification_body = str_replace($search, $replace, $notification_body);
					break;
				}
				if (!isset($notification_body) || !$notification_body) {
					application_log("error", "There was an issue attempting to create the content (body text) for a notification. Not adding empty notification.");
				} else {
					$new_notification = array(	"nuser_id" => $nuser_id,
												"notification_body" => $notification_body,
												"proxy_id" => $proxy_id,
												"sent" => false,
												"digest" => 0,
												"sent_date" => 0,
												"from_email" => $from_email,
												"from_firstname" => $from_firstname,
												"from_lastname" => $from_lastname);
					$db->AutoExecute("notifications", $new_notification, "INSERT");
					if (!($notification_id = $db->Insert_Id())) {
						application_log("error", "There was an issue attempting to add a notification record to the database. Database said: ".$db->ErrorMsg());
					} else {
						$new_notification["notification_id"] = $notification_id;
						$notification = self::fromArray($new_notification);
						$notification_user->setNextNotificationDate();
						if ($create_as_sent) {
							$notification->setSentStatus(true);
						}
						return $notification;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Creates a new notification and returns its id.
	 *
	 * @param int $nuser_id
	 * @return Notification
	 */
	public static function addDigest($nuser_id) {
		global $db, $ENTRADA_TEMPLATE;

		require_once("Classes/utility/Template.class.php");

		$notification_user = NotificationUser::getByID($nuser_id);
		if ($notification_user) {
			$notifications = self::getAllPending($nuser_id, 1);
			$activity_count = count($notifications);
			if ($notifications && $activity_count) {
				$notification_template = file_get_contents($ENTRADA_TEMPLATE->absolute()."/email/notification-default-digest.xml");
				$search = array(	"%UC_CONTENT_TYPE_NAME%",
									"%CONTENT_TYPE_NAME%",
									"%COMMENTS_NUMBER_STRING%",
									"%CONTENT_TITLE%",
									"%URL%",
									"%UNSUBSCRIBE_URL%",
									"%APPLICATION_NAME%",
									"%ENTRADA_URL%");
				$replace = array(	html_encode(ucwords($notification_user->getContentTypeName())),
									html_encode($notification_user->getContentTypeName()),
									html_encode(($activity_count > 1 ? $activity_count." new comments have" : "A new comment has")),
									html_encode($notification_user->getContentTitle()),
									html_encode($notification_user->getContentURL()),
									html_encode(ENTRADA_URL."/profile?section=notifications&id=".$nuser_id."&action=unsubscribe"),
									html_encode(APPLICATION_NAME),
									html_encode(ENTRADA_URL));
				$notification_body = str_replace($search, $replace, $notification_template);
				$new_notification = array(	"nuser_id" => $nuser_id,
											"notification_body" => $notification_body,
											"proxy_id" => 0,
											"sent" => false,
											"sent_date" => 0,
											"digest" => 1);
				$db->AutoExecute("notifications", $new_notification, "INSERT");
				if (!($notification_id = $db->Insert_Id())) {
					application_log("error", "There was an issue attempting to add a notification record to the database. Database said: ".$db->ErrorMsg());
				} else {
					$new_notification["notification_id"] = $notification_id;
					foreach ($notifications as $processed_notification) {
						$processed_notification->setSentStatus(true);
					}
					$notification = self::fromArray($new_notification);
					$notification_user->setNextNotificationDate();
					return $notification;
				}
			}
		}
		return false;
	}

	static public function fromArray($array) {
		return new Notification($array["notification_id"], $array["nuser_id"], $array["notification_body"], $array["proxy_id"], $array["sent"], $array["digest"], $array["sent_date"], $array["from_email"], $array["from_firstname"], $array["from_lastname"]);
	}

	protected function setSentStatus($sent) {
		global $db;
		if ($sent == $this->sent) {
			return false;
		} else {
			if (!$db->AutoExecute("notifications", array("sent" => $sent, "sent_date" => time()), "UPDATE", "`notification_id` = ".$db->qstr($this->notification_id))) {
				application_log("error", "There was an issue attempting to update the `sent` value for a notification record in the database. Database said: ".$db->ErrorMsg());
				return false;
			}
		}
		return true;
	}

	/**
	 * This function sends an email out to the user referenced by the notification_user record,
	 * and returns whether sending the email was successful or not.
	 * @return bool
	 */
	public function send() {
		global $db, $AGENT_CONTACTS;
		require_once("Classes/utility/TemplateMailer.class.php");
		$notification_user = NotificationUser::getByID($this->nuser_id);
		if ($notification_user->getNotificationUserType() == "proxy_id") {
			$query = "SELECT a.`proxy_id`, b.`firstname`, b.`lastname`, b.`email`, a.`content_type`, a.`record_id`, a.`record_proxy_id` FROM `notification_users` AS a
					JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					WHERE a.`nuser_id` = " . $db->qstr($this->nuser_id);
		} elseif ($notification_user->getNotificationUserType() == "external_assessor_id") {
			$query = "SELECT a.`proxy_id`, b.`firstname`, b.`lastname`, b.`email`, a.`content_type`, a.`record_id`, a.`record_proxy_id` FROM `notification_users` AS a
					JOIN `cbl_external_assessors` AS b
					ON a.`proxy_id` = b.`eassessor_id`
					WHERE a.`nuser_id` = " . $db->qstr($this->nuser_id);
		}
		if (isset($query) && $query) {
			$user = $db->GetRow($query);
			if ($user) {
				$template = new Template();
				$template->loadString($this->notification_body);
				$mail = new TemplateMailer(new Zend_Mail());
				$mail->addHeader("X-Section", APPLICATION_NAME . " Notifications System", true);

                if ($this->getFromEmail()) {
                    $from = array("email" => $this->getFromEmail(), "firstname" => ($this->getFromFirstname() || $this->getFromLastname() ? $this->getFromFirstname() : APPLICATION_NAME . " Notification System"), "lastname" => $this->getFromLastname());
                } else {
                    $from = array("email" => $AGENT_CONTACTS["agent-notifications"]["email"], "firstname" => APPLICATION_NAME . " Notification System", "lastname" => "");
                }

                if (isset($user["email"]) && $user["email"]) {
                    $to = array("email" => $user["email"], "firstname" => $user["firstname"], "lastname" => $user["lastname"]);

                    try {
                        $mail->send($template, $to, $from, DEFAULT_LANGUAGE);
                        if ($this->setSentStatus(true)) {
                            application_log("success", "A [" . $user["content_type"] . "] notification has been sent to a user [" . $user["proxy_id"] . "] successfully.");
                            return true;
                        }
                    } catch (Zend_Mail_Transport_Exception $e) {
                        system_log_data("error", "Unable to send [" . $user["content_type"] . "] notification to user [" . $user["proxy_id"] . "]. Template Mailer said: " . $e->getMessage());
                    }
                } else {
                    application_log("error", "A [" . $user["content_type"] . "] notification was unable to be sent to a user [" . $user["proxy_id"] . "], as they no email was found for the user.");
                }
			}
		}

		return false;
	}

	public static function fetchMostRecentByNUserID($nuser_id) {
		global $db;

		$output = false;

		//Get unsent notifications first, as those are the "newest" in reality.
		$query = "SELECT * FROM `notifications`
					WHERE `nuser_id` = ?
					AND `sent` = 0
					ORDER BY `notification_id` DESC";
		$notification = $db->getRow($query, array($nuser_id));
		if (!$notification) {
			//If no unsent are found, get the most recently sent.
			$query = "SELECT * FROM `notifications`
					WHERE `nuser_id` = ?
					AND `sent` = 1
					ORDER BY `sent_date` DESC";
			$notification = $db->getRow($query, array($nuser_id));
		}

		if ($notification) {
			$output = new Notification($notification["notification_id"], $notification["nuser_id"], $notification["notification_body"], $notification["proxy_id"], $notification["sent"], $notification["digest"], $notification["sent_date"], $notification["from_email"], $notification["from_firstname"], $notification["from_lastname"]);
		}
		return $output;
	}
}