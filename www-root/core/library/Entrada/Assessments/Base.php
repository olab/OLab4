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
 * This is the base class for all assessment related functionality.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Assessments_Base extends Entrada_Base {

    protected $task_list = array();     // The list of all tasks, potential and actual, for distributions.
    private $verbose = false;           // Debug flag for console/logging
    private $data_storage = array();    // Data storage to limit DB hits

    /**
     * For a given distribution ID, fetch all of the related data in one (large) array.
     *
     * @param $distribution_id
     * @param bool $include_rotation_blocks
     * @param bool $include_delegations
     * @param bool $include_assessments
     * @param bool $include_deleted_tasks
     * @param bool $include_events
     * @param bool $include_assessment_progress
     * @param bool $include_assessment_targets
     * @param bool $include_assessment_approvals
     * @return array
     */
    public function getAllDistributionData($distribution_id = null, $include_rotation_blocks = false, $include_assessments = false, $include_delegations = false, $include_deleted_tasks = false, $include_events = false, $include_assessment_progress = false, $include_assessment_targets = false, $include_assessment_approvals = false) {
        $distribution_data = array();
        $distribution_data["adistribution_id"] = false;
        $distribution_data["distribution_id"] = false;
        $distribution_data["distribution"] = false;
        $distribution_data["curriculum_period"] = false;
        $distribution_data["course"] = false;
        $distribution_data["distribution_form"] = false;
        $distribution_data["distribution_authors"] = false;
        $distribution_data["distribution_reviewers"] = false;

        $distribution_data["distribution_schedule"] = false;
        $distribution_data["rotation_schedule"] = array(
            "selected_schedule" => false,
            "parent_schedule" => false,
            "rotation_blocks" => false
        );
        $distribution_data["distribution_delegator"] = false;
        $distribution_data["distribution_delegator_name"] = false;
        $distribution_data["delegations"] = false;
        $distribution_data["delegations_count"] = false;
        $distribution_data["delegation_assignments"] = false;

        $distribution_data["distribution_assessors_summary"] = false;
        $distribution_data["distribution_assessors"] = false;
        $distribution_data["distribution_assessments_count"] = false;
        $distribution_data["distribution_assessments"] = false;
        $distribution_data["distribution_assessments_progress"] = false;

        $distribution_data["distribution_targets_summary"] =  false;
        $distribution_data["distribution_targets"] = false;
        $distribution_data["distribution_assessment_targets_count"] = false;
        $distribution_data["distribution_assessment_targets"] = false;

        $distribution_data["distribution_approvers"] = false;
        $distribution_data["distribution_approvals"] = false;

        $distribution_data["distribution_eventtypes"] = false;
        $distribution_data["events"] = false;
        $distribution_data["events_count"] = false;

        $distribution_data["deleted_tasks"] = false;

        // Make sure we have a distribution ID to work with.
        if (!$distribution_id) {
            if (method_exists($this, "getDistributionID")) {
                $distribution_id = $this->getDistributionID();
            } else if (method_exists($this, "getADistributionID")) {
                $distribution_id = $this->getADistributionID();
            } else if (method_exists($this, "getAdistributionID")) {
                $distribution_id = $this->getAdistributionID();
            }
        }

        if ($distribution_id && $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_id)) {
            $distribution_data["distribution"] = $distribution;

            $distribution_data["adistribution_id"] = $distribution_id;
            $distribution_data["distribution_id"] = $distribution_id;

            $distribution_data["distribution_form"] = Models_Assessments_Form::fetchRowByID($distribution->getFormID());

            $distribution_data["curriculum_period"] = Models_Curriculum_Period::fetchRowByID($distribution->getCperiodID());
            $distribution_data["course"] = Models_Course::fetchRowByID($distribution->getCourseID());
            $distribution_data["authors"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_Author::fetchAllByDistributionID($distribution_id));
            $distribution_data["reviewers"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($distribution_id));

            $distribution_data["distribution_assessors"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution_id));
            $distribution_data["distribution_assessors_summary"] =  $this->buildAssessorsSummary($distribution_data["distribution_assessors"]);

            $distribution_data["distribution_targets"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution_id));
            $distribution_data["distribution_targets_summary"] =  $this->buildTargetsSummary($distribution_data["distribution_targets"]);

            $distribution_data["distribution_schedule"] = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_id);
            if ($distribution_data["distribution_schedule"]) {
                $schedule = Models_Schedule::fetchRowByID($distribution_data["distribution_schedule"]->getScheduleID());
                if ($schedule) {
                    $distribution_data["rotation_schedule"]["selected_schedule"] = $schedule;

                    if ($schedule->getScheduleParentID()) {
                        $distribution_data["rotation_schedule"]["parent_schedule"] = $schedule->getScheduleParentID();
                        if ($include_rotation_blocks) {
                            $distribution_data["rotation_schedule"]["rotation_blocks"] = Models_Schedule::fetchAllByParentID($schedule->getScheduleParentID());
                        }
                    } else {
                        $distribution_data["rotation_schedule"]["parent_schedule"] = $schedule;
                        if ($include_rotation_blocks) {
                            $distribution_data["rotation_schedule"]["rotation_blocks"] = Models_Schedule::fetchAllByParentID($schedule->getID());
                        }
                    }
                }
            }

            $distribution_data["distribution_delegator"] = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution_id);
            if ($distribution_data["distribution_delegator"]) {
                $user_data = $this->getUserByType($distribution_data["distribution_delegator"]->getDelegatorID(), $distribution_data["distribution_delegator"]->getDelegatorType());
                if ($user_data) {
                    $distribution_data["distribution_delegator_name"] = "{$user_data->getFirstname()} {$user_data->getLastname()}";
                }
            }

            if ($include_delegations) {
                $distribution_data["delegations"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_Delegation::fetchAllByDistributionID($distribution_id));
                $distribution_data["delegations_count"] = count($distribution_data["delegations"]);
                $distribution_data["delegation_assignments"] = $this->addToArrayByPrimaryKey(Models_Assessments_Distribution_DelegationAssignment::fetchAllByDistributionIDIgnoreDeletedDate($distribution_id));
            }

            if ($include_assessments) {
                $distribution_data["distribution_assessments"] = $this->addToArrayByPrimaryKey(Models_Assessments_Assessor::fetchAllRecordsByDistributionID($distribution_id));
                $distribution_data["distribution_assessments_count"] = count($distribution_data["distribution_assessments"]);
                if ($include_assessment_targets) {
                    $distribution_data["distribution_assessment_targets"] = $this->addToArrayByPrimaryKey(Models_Assessments_AssessmentTarget::fetchAllByDistributionID($distribution_id));
                    $distribution_data["distribution_assessment_targets_count"] = count($distribution_data["distribution_assessment_targets"]);
                }
                if ($include_assessment_progress) {
                    $distribution_data["distribution_assessments_progress"] = $this->addToArrayByPrimaryKey(Models_Assessments_Progress::fetchAllByDistributionID($distribution_id));
                }
            }

            if ($include_deleted_tasks) {
                $distribution_data["deleted_tasks"] = Models_Assessments_DeletedTask::fetchAllByAdistributionID($distribution_id);
            }

            $distribution_data["distribution_eventtypes"] = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($distribution_id);
            if ($include_events) {
                $distribution_data["events"] = array();
                $distribution_data["events_count"] = 0;
                if (!empty($distribution_data["distribution_eventtypes"]) && $distribution_data["course"]) {
                    foreach ($distribution_data["distribution_eventtypes"] as $eventtype) {
                        $distribution_data["events"][$eventtype->getEventtypeID()] = Models_Event::fetchAllByCourseIDEventtypeID($distribution_data["course"]->getID(), $eventtype->getEventtypeID());
                        $distribution_data["events_count"] += count($distribution_data["events"][$eventtype->getEventtypeID()]);
                    }
                }
            }

            $approvers = new Models_Assessments_Distribution_Approver();
            $distribution_data["distribution_approvers"] = $approvers->fetchAllByDistributionID($distribution_id);
            if ($include_assessment_approvals) {
                $approvals = new Models_Assessments_Distribution_Approvals();
                $distribution_data["distribution_approvals"] = $approvals->fetchAllByDistributionID($distribution_id);
            }
        }
        return $distribution_data;
    }

    /**
     * Takes a schedule record and concatenates the block names of its children into one string.
     *
     * @param int $dassessment_id
     * @param Models_Schedule $schedule_record
     * @param int $start_date
     * @param int $end_date
     * @param int $organisation_id
     * @param string $title_separator
     * @param string $block_delimiter
     * @param bool $include_schedule_name
     * @return bool|string
     */
    public static function getConcatenatedBlockString($dassessment_id, $schedule_record, $start_date, $end_date, $organisation_id, $title_separator = " - ", $block_delimiter = ", ", $include_schedule_name = true) {
        global $translate;
        $schedule_string = "";
        $ids = array();

        if (!is_object($schedule_record)) {
            return $translate->_("Invalid schedule");
        }

        if ($include_schedule_name) {
            if (is_object($schedule_record) && $schedule_record->getTitle()) {
                $schedule_string = $schedule_record->getTitle();
            }
        }

        // If a dassessment is already associated with this task, then use the given targets.
        if ($dassessment_id) {
            $assessment = Models_Assessments_Assessor::fetchRowByID($dassessment_id);
            if ($assessment) {
                $targets = Models_Assessments_AssessmentTarget::fetchAllByDassessmentID($dassessment_id);
                if (!empty($targets)) {
                    foreach ($targets as $t) {
                        $ids[$t->getTargetValue()] = $t->getTargetValue();
                    }
                }
            }
        }

        // If no specific targets are found, use them all to determine blocks (empty array uses a wider search parameter).
        $child_schedules = Models_Schedule::fetchAllByParentAndDateRangeGroupedByScheduleID($organisation_id, $schedule_record->getID(), $ids, $start_date, $end_date);
        if (is_array($child_schedules) && !empty($child_schedules)) {
            $blocks = array();
            foreach ($child_schedules as $key => $child_schedule) {
                $b_string = $child_schedule->getTitle();
                // 13 blocks = 4 week blocks. 26 = 2 week blocks, 52 = 1 week blocks.
                if ($child_schedule->number_of_blocks != 13) {
                    $b_string .= " ({$child_schedule->block_type_name})";
                }
                $blocks[] = $b_string;
            }
            $blocks_string = implode($block_delimiter, $blocks);
            if ($blocks_string) {
                if ($include_schedule_name) {
                    $schedule_string .= $title_separator;
                }
                $schedule_string .= $blocks_string;
            }
        }

        return $schedule_string;
    }

    /**
     * Fetch a user object based on the given type.
     *
     * @param $user_id
     * @param $type
     * @return bool|Models_Base
     */
    public function getUserByType($user_id, $type = null) {
        if ($type == "external" || $type == "external_hash" || $type == "external_assessor_id") {
            return Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($user_id);
        } else {
            return Models_User::fetchRowByID($user_id);
        }
    }

    /**
     * Determines if a command exists on the current environment
     *
     * @param string $command
     * @return bool
     */
    public function commandExists ($command) {
        $where_is_command = (PHP_OS == 'WINNT') ? 'where.exe' : 'command -v';

        $process = proc_open(
            "$where_is_command $command",
            array(
                0 => array("pipe", "r"), //STDIN
                1 => array("pipe", "w"), //STDOUT
                2 => array("pipe", "w"), //STDERR
            ),
            $pipes
        );
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $stdout != '';
        }
        return false;
    }

    /**
     * Given the module path with preceeding slash, and the full server uri (from $_SERVER["REQUEST_URI"]), build an appropriate URI string.
     *
     * @param string $module_path (e.g. "/assessments/assessment")
     * @param string $server_uri ($_SERVER["REQUEST_URI"])
     * @return string
     */
    public function buildURI($module_path, $server_uri) {
        $uri_params = substr($server_uri, strpos($server_uri, "?") + 1);
        return ENTRADA_URL . "$module_path?$uri_params";
    }

    /**
     * Function to create a filename, optionally unique.
     *
     * @param string $file_title
     * @param string $extension
     * @param bool $make_unique_in_filesystem
     * @param bool $use_local_path
     * @param bool $add_timestamp
     * @return string
     */
    public function buildFilename($file_title, $extension, $make_unique_in_filesystem = false, $use_local_path = false, $add_timestamp = false) {

        $extension = trim($extension, "."); // remove period, if it was given (we add our own)

        // Sanitize the given file title
        $sanitized_file_title = preg_replace("/[^A-Za-z0-9 ]/", "", $file_title); // Only characters and spaces
        $sanitized_file_title = str_replace(" ", "-", $sanitized_file_title);
        $sanitized_file_title = strtolower($sanitized_file_title);

        $file_path_base = "";
        $file_path_base .= ($use_local_path) ? CACHE_DIRECTORY . "/" : "";
        $file_path_base .= $sanitized_file_title;
        $file_path_base .= ($add_timestamp) ? "-" . time() : "";

        $file_path = "{$file_path_base}.{$extension}";

        if ($make_unique_in_filesystem) {
            $ordinal = 0;
            while (file_exists($file_path)) {
                $ordinal++;
                $file_path = "{$file_path_base}-{$ordinal}.{$extension}";
            }
        }
        return $file_path;
    }

    /**
     * A function to add CLI colouring to the given string.
     *
     * @param $string
     * @param $foreground
     * @param null $background
     * @return string
     */
    public function cliString($string, $foreground, $background = null) {
        $foreground_colours["black"] = "0;30";
        $foreground_colours["dark_gray"] = "1;30";
        $foreground_colours["blue"] = "0;34";
        $foreground_colours["light_blue"] = "1;34";
        $foreground_colours["green"] = "0;32";
        $foreground_colours["light_green"] = "1;32";
        $foreground_colours["cyan"] = "0;36";
        $foreground_colours["light_cyan"] = "1;36";
        $foreground_colours["red"] = "0;31";
        $foreground_colours["light_red"] = "1;31";
        $foreground_colours["purple"] = "0;35";
        $foreground_colours["light_purple"] = "1;35";
        $foreground_colours["brown"] = "0;33";
        $foreground_colours["yellow"] = "1;33";
        $foreground_colours["light_gray"] = "0;37";
        $foreground_colours["white"] = "1;37";

        $background_colours["black"] = "40";
        $background_colours["red"] = "41";
        $background_colours["green"] = "42";
        $background_colours["yellow"] = "43";
        $background_colours["blue"] = "44";
        $background_colours["magenta"] = "45";
        $background_colours["cyan"] = "46";
        $background_colours["light_gray"] = "47";

        if (!isset($foreground_colours[$foreground])) {
            return $string;
        }

        $coloured_string = "\033[{$foreground_colours[$foreground]}m";
        if ($background) {
            if (isset($background_colours[$background])) {
                $coloured_string .= "\033[{$background_colours[$background]}m";
            }
        }

        $coloured_string .= $string;
        $coloured_string .= "\033[0m";
        return $coloured_string;
    }

    //-- Notification wrappers --//

    /**
     * Queue notification for a completed or reviewed or released assessment task to notify the assessor or learner or approver
     *
     * @param $assessment_id
     * @param $distribution_id
     * @param $notify_proxy_id
     * @param $notification_type
     * @param $record_id
     */
    public function queueCompletedNotification($assessment_id, $distribution_id, $notify_proxy_id, $notification_type , $record_id) {
        global $ENTRADA_USER;
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $notification_user = NotificationUser::get($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, "proxy_id");
        if (!$notification_user) {
            $notification_user = NotificationUser::add($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, 1, 0, 0, "proxy_id");
        }

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_id);
        if ($distribution_schedule) {
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
        }

        if (isset($notification_user) && $notification_user) {
            $notification = Notification::add($notification_user->getID(), $notify_proxy_id, $record_id, $ENTRADA_USER->getID());
            if ($notification) {
                $assessment_notification = new Models_Assessments_Notification(array(
                    "adistribution_id" => $distribution_id,
                    "assessment_value" => $assessment_id,
                    "assessment_type" => "assessment",
                    "notified_value" => $notify_proxy_id,
                    "notified_type" => "proxy_id",
                    "notification_id" => $notification->getID(),
                    "nuser_id" => $notification_user->getID(),
                    "notification_type" => $notification_type,
                    "schedule_id" => (isset($schedule) && $schedule ? $schedule->getID() : NULL),
                    "sent_date" => time()
                ));
                if (!$assessment_notification->insert()) {
                    application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user.");
                }
            } else {
                application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user.");
            }
        } else {
            application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment notification being sent to a user..");
        }
    }

    /**
     * Add assessor notifications. Sends after an assessment task is created. Notification will be a reminder if $send_as_reminder is set true; that should happen on stale notification check.
     *
     * @param Models_Assessments_Assessor $distribution_assessment
     * @param int $proxy_id
     * @param null $schedule_id
     * @param int $notify
     * @param bool $general_email
     * @param bool $send_as_reminder
     */
    protected function queueAssessorNotifications($distribution_assessment, $proxy_id, $schedule_id = NULL, $notify = 1, $send_as_reminder = false, $general_email = true, $create_as_sent = false) {
        if ($notify) {
            require_once("Classes/notifications/NotificationUser.class.php");
            require_once("Classes/notifications/Notification.class.php");
            if ($distribution_assessment->getMinSubmittable() && $distribution_assessment->getMinSubmittable() > $distribution_assessment->getNumberSubmitted()) {
                $email_found = false;
                $external_assessor = false;
                if ($distribution_assessment->getExternalHash()) {
                    $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($proxy_id);
                    if ($external_assessor && $external_assessor->getEmail() && !$external_assessor->getDeletedDate()) {
                        $email_found = true;
                    }
                } else {
                    $user = Models_User::fetchRowByID($proxy_id);
                    if ($user && $user->getEmail()) {
                        $email_found = true;
                    }
                }

                if ($email_found) {
                    $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
                    if ($distribution) {
                        $notification_user = NotificationUser::get($proxy_id, (!$external_assessor && $general_email ? "assessment_general" : "assessment"), $distribution_assessment->getID(), $proxy_id, ($distribution_assessment->getExternalHash() ? "external_assessor_id" : "proxy_id"));
                        if (!$notification_user) {
                            $notification_user = NotificationUser::add($proxy_id, (!$external_assessor && $general_email ? "assessment_general" : "assessment"), $distribution_assessment->getID(), $proxy_id, 1, 0, 0, ($distribution_assessment->getExternalHash() ? "external_assessor_id" : "proxy_id"));
                        }

                        if (isset($notification_user) && $notification_user) {
                            $previous_notification = Models_Assessments_Notification::fetchAllByProxyIDAssessmentTypeForToday($proxy_id, "assessment");
                            if ($external_assessor || !$previous_notification) {
                                $notification = Notification::add($notification_user->getID(), $proxy_id, $distribution_assessment->getID(), ($send_as_reminder) ? 1 : null, $create_as_sent);
                                if ($notification) {
                                    $assessment_notification = new Models_Assessments_Notification(array(
                                        "adistribution_id" => $distribution->getID(),
                                        "assessment_value" => $distribution_assessment->getID(),
                                        "assessment_type" => "assessment",
                                        "notified_value" => $proxy_id,
                                        "notified_type" => ($external_assessor) ? "external_assessor_id" : "proxy_id",
                                        "notification_id" => $notification->getID(),
                                        "nuser_id" => $notification_user->getID(),
                                        "notification_type" => ($send_as_reminder) ? "assessor_reminder" : "assessor_start",
                                        "schedule_id" => $schedule_id,
                                        "sent_date" => time()
                                    ));

                                    if (!$assessment_notification->insert()) {
                                        $relevant_log_info = "adistribution_id = '{$distribution_assessment->getID()}', dassessment_id = '{$distribution_assessment->getID()}', notified_value(proxy_id) = '$proxy_id', notification_id = {$notification->getID()}";
                                        application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user ($relevant_log_info).");
                                    }
                                }
                            } else {
                                application_log("error", "Failed to create new assessor notification for nuserid = {$notification_user->getID()}, proxy_id = $proxy_id");
                            }
                        }
                    } else {
                        application_log("error", "Attempted to send a notification for a distribution that is not found but has an assessment record (dassessment_id = '{$distribution_assessment->getID()}', distribution_id = '{$distribution_assessment->getADistributionID()}').");
                    }
                }
            }
        }
    }

    /**
     * Adds approver notification. Sends as a reminder for the task to be reviewed.
     *
     * @param Models_Assessments_Assessor $distribution_assessment
     * @param int $proxy_id
     */
    protected function queueApproverNotifications($distribution_assessment, $approver_id) {
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");
        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_assessment->getADistributionID());
        if ($distribution) {
            $notification_user = NotificationUser::get($approver_id, "assessment_approver", $distribution_assessment->getID(), $approver_id, "proxy_id");
            if (!$notification_user) {
                $notification_user = NotificationUser::add($approver_id, "assessment_approver", $distribution_assessment->getID(), $approver_id, 1, 0, 0, "proxy_id");
            }

            if (isset($notification_user) && $notification_user) {
                $previous_notification = Models_Assessments_Notification::fetchAllByProxyIDAssessmentTypeForToday($approver_id, "approver");
                if (!$previous_notification) {
                    $notification = Notification::add($notification_user->getID(), $approver_id, $distribution_assessment->getID(),  1, false);
                    if ($notification) {
                        $assessment_notification = new Models_Assessments_Notification(array(
                            "adistribution_id" => $distribution->getID(),
                            "assessment_value" => $distribution_assessment->getID(),
                            "assessment_type" => "approver",
                            "notified_value" => $approver_id,
                            "notified_type" =>  "proxy_id",
                            "notification_id" => $notification->getID(),
                            "nuser_id" => $notification_user->getID(),
                            "notification_type" => "approver_reminder",
                            "sent_date" => time()
                        ));

                        if (!$assessment_notification->insert()) {
                            $relevant_log_info = "adistribution_id = '{$distribution_assessment->getID()}', dassessment_id = '{$distribution_assessment->getID()}', notified_value(proxy_id) = '$approver_id', notification_id = {$notification->getID()}";
                            application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user ($relevant_log_info).");
                        }
                    }
                } else {
                    application_log("error", "Failed to create new assessor notification for nuserid = {$notification_user->getID()}, proxy_id = $approver_id");
                }
            }
        } else {
            application_log("error", "Attempted to send a notification for a distribution that is not found but has an assessment record (dassessment_id = '{$distribution_assessment->getID()}', distribution_id = '{$distribution_assessment->getADistributionID()}').");
        }
    }

    /**
     * Queue notifications for delegators. Optionally, add them as if they have already been sent (this suppresses the notification).
     *
     * @param Models_Assessments_Distribution $distribution
     * @param bool|Models_Assessments_Distribution_Delegation $delegation
     * @param int $proxy_id
     * @param int $notify
     * @param bool $create_as_sent
     * @param bool $send_as_reminder
     * @param bool $general_email
     */
    protected function queueDelegatorNotifications ($distribution, $delegation, $proxy_id, $notify = 1, $create_as_sent = false, $send_as_reminder = false, $general_email = true) {
        if ($notify) {
            require_once("Classes/notifications/NotificationUser.class.php");
            require_once("Classes/notifications/Notification.class.php");

            if (is_object($delegation)) { // $delegation can be true, false, or object so we make sure we're working with a proper object.
                $notification_user = NotificationUser::get($proxy_id, ($general_email ? "assessment_delegation_general" : "assessment_delegation"), $distribution->getID(), $proxy_id);
                if (!$notification_user) {
                    $notification_user = NotificationUser::add($proxy_id, ($general_email ? "assessment_delegation_general" : "assessment_delegation"), $distribution->getID(), $proxy_id);
                }
                if ($notification_user) {
                    $previous_notification = Models_Assessments_Notification::fetchAllByProxyIDAssessmentTypeForToday($proxy_id, "delegation");
                    if (!$previous_notification) {
                        $notification = Notification::add(
                            $notification_user->getID(),
                            $proxy_id,
                            array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()),
                            ($send_as_reminder) ? 1 : null,
                            $create_as_sent
                        );
                        if ($notification) {
                            $assessment_notification = new Models_Assessments_Notification(array(
                                "adistribution_id" => $distribution->getID(),
                                "assessment_value" => $delegation->getID(),
                                "assessment_type" => "delegation",
                                "notified_value" => $proxy_id,
                                "notified_type" => "proxy_id", // delegators can only be internal
                                "notification_id" => $notification->getID(),
                                "nuser_id" => $notification_user->getID(),
                                "notification_type" => ($send_as_reminder) ? "delegator_late" : "delegator_start",
                                "schedule_id" => null,
                                "sent_date" => time()
                            ));

                            if (!$assessment_notification->insert()) {
                                $relevant_log_info = "adistribution_id = '{$distribution->getID()}', addelegation_id = '{$delegation->getID()}', notified_value(proxy_id) = '$proxy_id', notification_id = {$notification->getID()}";
                                application_log("error", "Error encountered while attempting to save history of a delegation creation notification being sent to a user ($relevant_log_info).");
                            }
                        } else {
                            application_log("error", "Failed to create new delegator notification for nuserid = {$notification_user->getID()}, proxy_id = $proxy_id");
                        }
                    }
                } else {
                    application_log("error", "Unable to create notification user record for assessment_delegation (distribution_id = '{$distribution->getID()}', addelegation_id = '{$delegation->getID()}' proxy_id = '$proxy_id')");
                }
            }
        }
    }

    //-- Internal assessment task list functionality --//

    /**
     * Add an entry in the internal task list. The task list is a construct that represents all of the tasks that can, have, and will be created for a distribution.
     * Among the parameters is the option to fetch the actual created record that corresponds with the potential task.
     *
     * @param int $distribution_id
     * @param int $delivery_date
     * @param int $release_date
     * @param int $start_date
     * @param int $end_date
     * @param array $target_list
     * @param array $assessor_list
     * @param string $task_type
     * @param string $grouping
     * @param int|mixed $related_grouping_id
     * @param null $delegator_id
     * @param bool $find_current_record
     * @param null $schedule_type
     * @param int $delivery_period
     * @param int $period_offset
     * @param string $target_type
     * @return bool
     */
    public function addToTaskList($distribution_id, $delivery_date, $release_date, $start_date, $end_date, $target_list, $assessor_list, $task_type = "assessment", $grouping = "dates", $related_grouping_id = null, $delegator_id = null, $find_current_record = true, $schedule_type = null, $delivery_period = 0, $period_offset = 0, $target_type = "proxy_id") {
        $deleted_date = null;
        $current_record = array();
        switch ($task_type) {
            case "assessment":
                $found = ($find_current_record) ? Models_Assessments_Assessor::fetchRowByADistributionIDDeliveryDate($distribution_id, $delivery_date) : false;
                break;

            case "delegation":
                if (!$delegator_id) {
                    application_log("error", "No delegator found when trying to add existing record for distribution delegation [given delegator ID was '$delegator_id', distribution id: '$distribution_id']");
                    return false;
                }
                $found = ($find_current_record) ? Models_Assessments_Distribution_Delegation::fetchRowByDistributionIDDelegatorIDDeliveryDateStartDateEndDate($distribution_id, $delegator_id, $delivery_date, $start_date, $end_date) : false;
                break;

            case "learning_event_assessment":
                $found = false; // There can be more than one "current record" for learning event assessment task lists. We don't track them here.
                break;

            default:
                // If type isn't one we expect, we log and quit.
                application_log("error", "Invalid task type given to Entrada_Utilities_Assessments_Base->addToTaskList() [supplied type was: '$task_type'']");
                return false;
        }

        // We found a current record, so save it and keep its deleted date, if any.
        if ($find_current_record) {
            if ($found) {
                $current_record = $found;
                $deleted_date = $found->getDeletedDate();
            }
        }

        // Should this assessment exist? If cron hasn't created the task yet, we still want to report information about it.
        $should_exist = false;
        if (($release_date <= $delivery_date) && ($delivery_date <= time())) {
            $should_exist = true;
        }

        // If it exists and was deleted, it "shouldn't exist"
        if ($deleted_date) {
            $should_exist = false;
        }

        // If applicable, change the grouping. Default behaviour is to group tasks by the relevant dates.
        switch ($grouping) {
            case "event_id":
                $storage_key = $related_grouping_id;
                break;
            case "dates":
            default:
                $storage_key = "$delivery_date-$release_date-$start_date-$end_date";
                break;
        }

        // Save the item, grouped by distribution ID and the related storage key.
        $this->task_list[$distribution_id][$storage_key] = array(
            "meta" => array(
                "active" => true, // If we ever want to include state information about tasks, this is where we would add it.
                "should_exist" => $should_exist,
                "task_type" => $task_type, // Should only be "assessment", "delegation", or "learning_event_assessment"
                "delivery_date" => $delivery_date,
                "release_date" => $release_date,
                "start_date" => $start_date,
                "end_date" => $end_date,
                "deleted_date" => $deleted_date,
                "schedule_type" => $schedule_type,
                "delivery_period" => $delivery_period,
                "period_offset" => $period_offset,
                "target_count" => count($target_list),
                "target_type" => $target_type,
                "assessor_count" => count($assessor_list),
            ),
            "targets" => $target_list,
            "assessors" => $assessor_list,
            "current_record" => $current_record // One single record for delegation and standard assessments (false for learning event)
        );
        return true;
    }

    /**
     * Fetch the internal task list construct.
     *
     * @return array
     */
    public function getTaskList() {
        return $this->task_list;
    }

    /**
     * Erase the internal task list construct. Optionally set the initial index with given index.
     *
     * @param $index
     */
    public function clearTaskList($index = null) {
        $this->task_list = array();
        if ($index) {
            $this->task_list[$index] = array();
        }
    }

    /**
     * Erase (reset) the internal task list construct. Wrapper for clearTaskList().
     *
     * @param $distribution_id
     */
    public function resetTaskList($distribution_id = null) {
        $this->clearTaskList($distribution_id);
    }

    //-- Protected utility methods --//

    protected function calculateDateByOffset ($delivery_period, $period_offset, $start_date, $end_date) {
        $delivery_date = 0;
        switch ($delivery_period) {
            case "after-start" :
                $delivery_date = $start_date + $period_offset;
                break;
            case "before-middle" :
                $seconds_until_middle = ($end_date - $start_date) / 2;
                $delivery_date = ($start_date + $seconds_until_middle) - $period_offset;
                break;
            case "after-middle" :
                $seconds_until_middle = ($end_date - $start_date) / 2;
                $delivery_date = ($start_date + $seconds_until_middle) + $period_offset;
                break;
            case "before-end" :
                $delivery_date = $end_date - $period_offset;
                break;
            case "after-end" :
                $delivery_date = $end_date + $period_offset;
                break;
        }
        return ceil($delivery_date);
    }

    protected function calculateDateByFrequency ($frequency, $date) {
        $date = ($date + ($frequency * 86400));
        return $date;
    }

    protected function fetchLearnerBlocks ($block_id, $proxy_id = null) {
        global $db;
        if ($this->isInStorage("fetch-learner-blocks", "$block_id-$proxy_id")) {
            return $this->fetchFromStorage("fetch-learner-blocks", "$block_id-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }
            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_audience_value
                    ORDER BY a.`start_date`";

            $prepared_variables = array();
            $prepared_variables[] = $block_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            $learner_blocks = $db->GetAll($query, $prepared_variables);
            $this->addToStorage("fetch-learner-blocks", $learner_blocks, "$block_id-$proxy_id");
            return $learner_blocks;
        }
    }

    protected function fetchBlockRotations ($block_id, $scope = null, $proxy_id = null) {
        global $db;
        if ($this->isInStorage("fetch-block-rotations", "$block_id-$scope-$proxy_id")) {
            return $this->fetchFromStorage("fetch-block-rotations", "$block_id-$scope-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }

            $AND_slot_type_filter = "";
            if ($scope == "internal_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 1";
            } else if ($scope == "external_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 2";
            }

            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_audience_value
                    $AND_slot_type_filter
                    ORDER BY a.`start_date`";

            $prepared_variables = array();
            $prepared_variables[] = $block_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            $block_rotations = $db->GetAll($query, $prepared_variables);
            $this->addToStorage("fetch-block-rotations", $block_rotations, "$block_id-$scope-$proxy_id");
            return $block_rotations;
        }
    }

    protected function fetchRotations ($schedule_id, $scope = null, $proxy_id = null) {
        global $db;
        if ($this->isInStorage("fetch-rotations", "$schedule_id-$scope-$proxy_id")) {
            return $this->fetchFromStorage("fetch-rotations", "$schedule_id-$scope-$proxy_id");
        } else {
            $AND_audience_value = "";
            if ($proxy_id) {
                $AND_audience_value = "AND b.`audience_value` = ?";
            }

            $AND_slot_type_filter = "";
            if ($scope == "internal_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 1";
            } else if ($scope == "external_learners") {
                $AND_slot_type_filter = "AND c.`slot_type_id` = 2";
            }

            $query = "  SELECT  a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`
                    FROM    `cbl_schedule`  AS a
                    JOIN    `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    JOIN    `cbl_schedule_slots`    AS c ON b.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE   a.`schedule_parent_id` = ?
                    AND     a.`deleted_date` IS NULL
                    AND     b.`audience_type` = 'proxy_id'
                    AND     b.`deleted_date` IS NULL
                    $AND_slot_type_filter
                    $AND_audience_value
                    ORDER BY a.`start_date`";

            $prepared_variables = array();
            $prepared_variables[] = $schedule_id;
            if ($proxy_id) {
                $prepared_variables[] = $proxy_id;
            }
            $rotations = $db->GetAll($query, $prepared_variables);
            $this->addToStorage("fetch-rotations", $rotations, "$schedule_id-$scope-$proxy_id");
            return $rotations;
        }
    }

    protected function getRotationDates ($rotations = null, $organisation_id = null) {
        $storage_key = md5(serialize($rotations)) . "$organisation_id";
        if ($this->isInStorage("get-rotation-dates", $storage_key)) {
            return $this->fetchFromStorage("get-rotation-dates", $storage_key);
        } else {
            $all_rotations = array();
            $all_rotation_dates = array();
            $unique_rotation_dates = false;
            foreach ($rotations as $rotation) {
                $all_rotations[$rotation["audience_value"]][] = $rotation;
            }

            if ($all_rotations) {
                foreach ($all_rotations as $proxy_id => $user_rotations) {
                    foreach ($user_rotations as $user_rotation) {
                        $contiguous_end_date = $user_rotation["start_date"] - 1;
                        $start_date = $this->recursiveStartDate($contiguous_end_date, $proxy_id, $user_rotation["schedule_parent_id"], $organisation_id, $user_rotation["start_date"]);

                        $contiguous_start_date = $user_rotation["end_date"] + 1;
                        $end_date = $this->recursiveEndDate($contiguous_start_date, $proxy_id, $user_rotation["schedule_parent_id"], $organisation_id, $user_rotation["end_date"]);

                        $all_rotation_dates[$proxy_id][$end_date] = array($start_date, $end_date);
                        $unique_rotation_dates[] = array($start_date, $end_date);
                    }
                }
                $unique_rotation_dates = array_unique($unique_rotation_dates, SORT_REGULAR);
            }
            $unique_dates = array("unique_rotation_dates" => $unique_rotation_dates, "all_rotation_dates" => $all_rotation_dates);
            $this->addToStorage("get-rotation-dates", $unique_dates, $storage_key);
            return $unique_dates;
        }
    }

    /**
     * For a given rotation, get the earliest and latest dates contained.
     *
     * @param array $complete_rotation
     * @param bool $search_by_named_index
     * @return array
     */
    protected function findStartAndEndDateRange($complete_rotation, $search_by_named_index = true) {
        $earliest_date = 0;
        $latest_date = 0;

        if ($search_by_named_index) {
            $start_index = "start_date";
            $end_index = "end_date";
        } else {
            $start_index = 0;
            $end_index = 1;
        }

        foreach ($complete_rotation as $block) {
            if ($earliest_date == 0) {
                $earliest_date = $block[$start_index];
            }
            if ($block[$start_index] < $earliest_date) {
                $earliest_date = $block[$start_index];
            }
            if ($block[$end_index] > $latest_date) {
                $latest_date = $block[$end_index];
            }
        }

        return array("earliest_date" => $earliest_date, "latest_date" => $latest_date);
    }

    //-- Private methods --//

    private function recursiveEndDate ($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $end_date) {
        global $db;
        $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, `end_date`, b.*
                    FROM   `cbl_schedule`  AS a
                    JOIN   `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    WHERE  a.`start_date` = ?
                    AND    b.`audience_value` = ?
                    AND    a.`schedule_parent_id` = ?
                    AND    a.`organisation_id` = ?
                    AND    b.`audience_type` = 'proxy_id'
                    AND    b.`deleted_date` IS NULL";

        $result = $db->GetRow($query, array($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id));
        if ($result) {
            $contiguous_start_date = $result["end_date"] + 1;
            return $this->recursiveEndDate($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $result["end_date"]);
        } else {
            return $end_date;
        }
    }

    private function recursiveStartDate ($contiguous_end_date, $proxy_id, $schedule_parent_id, $organisation_id, $start_date) {
        global $db;
        $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, `end_date`, b.*
                    FROM   `cbl_schedule`  AS a
                    JOIN   `cbl_schedule_audience` AS b ON a.`schedule_id` =  b.`schedule_id`
                    WHERE  a.`end_date` = ?
                    AND    b.`audience_value` = ?
                    AND    a.`schedule_parent_id` = ?
                    AND    a.`organisation_id` = ?
                    AND    b.`audience_type` = 'proxy_id'
                    AND    b.`deleted_date` IS NULL";

        $result = $db->GetRow($query, array($contiguous_end_date, $proxy_id, $schedule_parent_id, $organisation_id));
        if ($result) {
            $contiguous_start_date = $result["start_date"] - 1;
            return $this->recursiveStartDate($contiguous_start_date, $proxy_id, $schedule_parent_id, $organisation_id, $result["start_date"]);
        } else {
            return $start_date;
        }
    }

    /**
     * Reorder the array by the ID of the given object.
     *
     * @param $result_set
     * @return array
     */
    private function addToArrayByPrimaryKey($result_set) {
        $return_set = array();
        foreach ($result_set as $object) {
            $return_set[$object->getID()] = $object;
        }
        return $return_set;
    }

    /**
     * Build localized descriptions of the assessors associated with this distribution.
     * Optionally return it as one string.
     *
     * @param array $assessors
     * @param bool $summary_as_string
     * @return array|string
     */
    private function buildAssessorsSummary($assessors, $summary_as_string = false) {
        global $translate;

        $assessor_summary = $translate->_("No assessors defined for this distribution.");
        $assessor_types = array();

        if ($assessors && !empty($assessors)) {
            foreach ($assessors as $assessor) {
                switch ($assessor->getAssessorType()) {
                    case "proxy_id":
                        if ($assessor->getAssessorRole() == "faculty") {
                            $assessor_types["proxy_id"][] = $translate->_("Individuals (faculty)");
                        }
                        else if ($assessor->getAssessorRole() == "learner"){
                            $assessor_types["proxy_id"][] = $translate->_("Individuals (learners)");
                        } else {
                            $assessor_types["proxy_id"][] = $translate->_("Individuals");
                        }
                        break;
                    case "schedule_id":
                        if ($assessor->getAssessorScope() == "internal_learners") {
                            $assessor_types["schedule_id"][] = $translate->_("On service learners");
                        } else if ($assessor->getAssessorScope() == "external_learners") {
                            $assessor_types["schedule_id"][] = $translate->_("Off service learners");
                        } else {
                            $assessor_types["schedule_id"][] = $translate->_("All rotation learners");
                        }
                        break;
                    case "external_hash":
                        $assessor_types["external_hash"][] = $translate->_("External assessors");
                        break;
                    case "course_id":
                        $assessor_types["course_id"][] = $translate->_("Cohort members");
                        break;
                    case "group_id":
                        $assessor_types["group_id"][] = $translate->_("Course audience members");
                        break;
                    case "eventtype_id":
                        if ($assessor->getAssessorScope() == "attended_learners") {
                            $assessor_types["schedule_id"][] = $translate->_("Learning event attendees");
                        } else if ($assessor->getAssessorScope() == "all_learners") {
                            $assessor_types["schedule_id"][] = $translate->_("Learning event audience");
                        } else {
                            $assessor_types["schedule_id"][] = $translate->_("Unsupported event type audience");
                        }
                        break;
                    default:
                        // not supported
                        $assessor_types["unsupported"][] = $translate->_("Unsupported assessor type");
                        break;
                }
            }
            $collected = array();
            foreach ($assessor_types as $collected_types) {
                $collected = array_merge($collected, array_unique($collected_types));
            }
            if ($summary_as_string) {
                $assessor_summary = implode(", ", $collected);
            } else {
                $assessor_summary = $collected;
            }
        }
        return $assessor_summary;
    }

    /**
     * Build localized descriptions of the targets associated with this distribution.
     * Optionally return as a string.
     *
     * @param array $targets
     * @param bool $summary_as_string
     * @return array|string
     */
    private function buildTargetsSummary($targets, $summary_as_string = false) {
        global $translate;

        $targets_summary = $translate->_("No targets defined for this distribution.");
        $targets_types = array();

        if ($targets && !empty($targets)) {
            foreach ($targets as $target) {
                switch ($target->getTargetType()) {
                    case "proxy_id":
                        if ($target->getTargetRole() == "learner") {
                            $targets_types["proxy_id"][] = $translate->_("Learners");
                        } else if ($target->getTargetRole() == "faculty") {
                            $targets_types["proxy_id"][] = $translate->_("Faculty");
                        } else {
                            $targets_types["proxy_id"][] = $translate->_("Individuals");
                        }
                        break;
                    case "schedule_id":
                        if ($target->getTargetScope() == "self") {
                            $targets_types["schedule_id"][] = $translate->_("The rotation schedule");
                        } else if ($target->getTargetScope() == "internal_learners") {
                            $targets_types["schedule_id"][] = $translate->_("On service learners");
                        } else if ($target->getTargetScope() == "external_learners") {
                            $targets_types["schedule_id"][] = $translate->_("Off service learners");
                        } else {
                            $targets_types["schedule_id"][] = $translate->_("All rotation learners");
                        }
                        break;
                    case "self":
                        $targets_types["self"][] = $translate->_("Self assessment");
                        break;
                    case "course_id":
                        $targets_types["course_id"][] = $translate->_("Course");
                        break;
                    case "group_id":
                        $targets_types["group_id"][] = $translate->_("Cohort");
                        break;
                    case "external_hash":
                        $targets_types["external_hash"][] = $translate->_("External target");
                        break;
                    case "eventtype_id":
                        $targets_types["eventtype"][] = $translate->_("Learning events");
                        break;
                    default:
                        // not supported
                        $targets_types["unsupported"][] = $translate->_("Unsupported target type");
                        break;
                }
            }
            $collected = array();
            foreach ($targets_types as $collected_types) {
                $collected = array_merge($collected, array_unique($collected_types));
            }
            if ($summary_as_string) {
                $targets_summary = implode(", ", $collected);
            } else {
                $targets_summary = $collected;
            }
        }
        return $targets_summary;
    }

    //--- Temporary data storage functionality ---//

    /**
     * Search for an item in the storage. If an index is specified, use that to find if it (or anything at that index) exists.
     * If the item is specified, do an exact comparison.
     *
     * @param string $type
     * @param string $index
     * @param mixed $item
     * @return bool
     */
    protected function isInStorage($type, $index = null, $item = null) {
        // Both are set, return true
        if ($index !== null && $item !== null) {
            if ($this->data_storage[$type][$index] == $item) {
                return true;
            }
        } else if ($index === null && $item !== null) {
            // Search for the exact item
            foreach ($this->data_storage[$type] as $potential_match) {
                if ($potential_match == $item) {
                    return true;
                }
            }
            // The index is set, so something is there
        } else if ($index !== null && $item === null) {
            if (isset($this->data_storage[$type][$index])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Put an item in the storage, optionally at a specified index.
     *
     * @param string $type
     * @param mixed $item
     * @param string $index
     */
    protected function addToStorage($type, $item, $index = null) {
        if ($index) {
            $this->data_storage[$type][$index] = $item;
        } else {
            $this->data_storage[$type][] = $item;
        }
    }

    /**
     * Fetch the item from the specified index in storage. Returns false if the item is not in storage.
     *
     * @param string $type
     * @param string $index
     * @return mixed|bool
     */
    protected function fetchFromStorage($type, $index) {
        if (isset($this->data_storage[$type][$index])) {
            return $this->data_storage[$type][$index];
        }
        return false;
    }

    /**
     * Fetch the entire storage array.
     *
     * @return array
     */
    protected function fetchStorage() {
        return $this->data_storage;
    }

    /**
     * Clear all existing storage.
     */
    protected function clearStorage() {
        $this->data_storage = array();
    }

    //--- Debug/console logging ---//

    /**
     * Turn on or off debug verbosity.
     *
     * @param bool $verbose
     */
    protected function setVerbose($verbose) {
        $this->verbose = $verbose;
    }

    /**
     * Debug to console/echo
     *
     * @param $string
     */
    protected function verboseOut($string) {
        if ($this->verbose) {
            echo $string;
        }
    }

    /**
     * Converts a given string to a database-safe cleaned version. Strips tags and returns the clean string.
     * Allows for zero as a return value.
     *
     * Validation failure returns boolean false.
     *
     * @param mixed $input
     * @return string|false
     */
    public function cleanInputString($input) {
        $trimmed = trim($input);
        if (is_numeric($trimmed)) {
            return $trimmed;
        } else {
            if ($tmp_input = clean_input($input, array("trim", "striptags"))) {
                return $tmp_input;
            } else {
                return false;
            }
        }
    }

}