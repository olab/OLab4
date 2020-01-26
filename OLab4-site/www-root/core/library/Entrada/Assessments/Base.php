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

    protected $task_list = array();                 // The list of all tasks, potential and actual, for distributions.
    protected $actor_proxy_id = null;               // The proxy ID of the user manipulating this functionality (the actor). Not necessarily a proxy ID.
    protected $actor_organisation_id = null;        // The actor's organisation ID.
    protected $actor_group = null;                  // The actor's group name.
    protected $actor_scope = null;                  // "internal" or "external
    protected $actor_type = null;                   // e.g., "proxy_id" or "external_assessor_id"
    protected $dataset = array();                   // Required by abstraction layers for storage of their respective datasets.
    protected $limit_dataset = array();             // Optional limits to the $dataset (if dataset is used)
    protected $determine_meta = true;               // Enable the metadata fetch for the $dataset (if dataset is used).
    protected $disable_internal_storage = false;    // A hard override for disabling the isInStorage mechanism check (makes it always return false)
    protected $global_storage = false;              // The name of a variable to place the internal storage data (global-scoped)
    private $verbose = false;                       // Debug flag for console/logging
    private $local_data_storage = array();          // Local-scope data storage to limit DB hits
    private $error_messages = array();              // Flat list of localized error messages (strings)
    private $memory_storage = null;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
        global $translate;
        if (is_null($translate)) {
            // In the case where translate does not exist, for instance when this is executed outside of a web context, we initialize the translation object.
            $template = array_key_exists("translate_template", $arr) ? $arr["translate_template"] : "default";
            $translate = new Entrada_Translate(
                array (
                    "adapter" => "array",
                    "disableNotices" => (DEVELOPMENT_MODE ? false : true)
                )
            );
            $translate->addTranslation(
                array(
                    'adapter' => 'array',
                    'content' => ENTRADA_ABSOLUTE . "/templates/{$template}/languages",
                    'locale'  => 'auto',
                    "scan" => Entrada_Translate::LOCALE_FILENAME
                )
            );
        }
        $this->memory_storage = new Entrada_Utilities_MemoryStorage($arr);
    }

    /**
     * Takes a schedule record and concatenates the block names of its children into one string.
     *
     * @param int $dassessment_id *deprecated*
     * @param Models_Schedule $schedule_record
     * @param int $start_date
     * @param int $end_date
     * @param int $organisation_id
     * @param string $title_separator
     * @param string $block_delimiter
     * @param bool $include_schedule_name
     * @param bool $use_blocks_label
     * @return bool|string
     */
    public static function getConcatenatedBlockString($dassessment_id = null, $schedule_record, $start_date, $end_date, $organisation_id, $title_separator = " - ", $block_delimiter = ", ", $include_schedule_name = true, $use_blocks_label = false) {
        global $translate;
        $schedule_string = "";

        if (!is_object($schedule_record)) {
            return $translate->_("Invalid schedule");
        }

        if ($include_schedule_name) {
            if (is_object($schedule_record) && $schedule_record->getTitle()) {
                $schedule_string = $schedule_record->getTitle();
            }
        }

        $schedules = Models_Schedule::fetchAllByParentAndDateRange($organisation_id, ($schedule_record->getScheduleParentID() ? $schedule_record->getScheduleParentID() : $schedule_record->getID()), $start_date, $end_date);

        if (is_array($schedules) && !empty($schedules)) {
            $blocks = array();
            foreach ($schedules as $key => $schedule) {
                $b_string = $schedule->getTitle();
                if ($use_blocks_label) {
                    $b_string = str_replace($translate->_("Block"), "", $b_string);
                }
                $blocks[] = $b_string;
            }
            $blocks_string = implode($block_delimiter, $blocks);
            if ($blocks_string) {
                if ($include_schedule_name) {
                    $schedule_string .= $title_separator;
                }
                if ($use_blocks_label) {
                    if (count($blocks) > 1) {
                        $schedule_string .= $translate->_("Blocks") . " ";
                    } else {
                        $schedule_string .= $translate->_("Block") . " ";
                    }
                }
                $schedule_string .= $blocks_string;
            }
        }

        return $schedule_string;
    }

    /**
     * Fetch a user object based on the given type.
     *
     * @param int $user_id
     * @param string $type
     * @param bool $cached
     * @return bool|Models_Base
     */
    public function getUserByType($user_id, $type = null, $cached = true) {
        if ($type == "external" || $type == "external_hash" || $type == "external_assessor_id") {
            if ($cached) {
                if ($this->isInStorage("external-assessor-record", $user_id)) {
                    $external_assessor = $this->fetchFromStorage("external-assessor-record", $user_id);
                } else {
                    $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($user_id);
                    $this->addToStorage("external-assessor-record", $external_assessor, $user_id);
                }
            } else {
                $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($user_id);
            }
            return $external_assessor;
        } else {
            if ($cached) {
                if ($this->isInStorage("internal-user-record", $user_id)) {
                    $user = $this->fetchFromStorage("internal-user-record", $user_id);
                } else {
                    $user = Models_User::fetchRowByID($user_id);
                    $this->addToStorage("internal-user-record", $user, $user_id);
                }
            } else {
                $user = Models_User::fetchRowByID($user_id);
            }
            return $user;
        }
    }

    /**
     * Generate a hash based on the provided assessor_type and assessor_value.
     *
     * @param $assessor_type
     * @param $assessor_value
     * @return bool|string
     */
    public static function generateAssessorHash($assessor_type, $assessor_value) {
        return hash("adler32", serialize(array($assessor_type, $assessor_value)));
    }

    /**
     * Determines if a command exists on the current environment
     *
     * @param string $command
     * @return bool
     */
    public function commandExists($command) {
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

    //-- Abstraction Layer Actor functionality --//

    /**
     * Return an array containing the current actor for related abstraction layer constructors.
     * Optionally add more construction options to the array via $additional_properties.
     *
     * @param array $additional_properties
     * @return array
     */
    protected function buildActorArray($additional_properties = array()) {
        $actor = array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "actor_scope" => $this->actor_scope, // e.g. "internal"
            "actor_type" => $this->actor_type, // e.g. "proxy_id"
            "actor_group" => $this->actor_group, // (optional) e.g. "faculty"
        );
        return array_merge($actor, $additional_properties);
    }

    /**
     * Validate that the Actor proxy and organisation is set. This functionality should be called before any
     * methods in child classes that leverage actor proxy and org.
     *
     * @return bool
     */
    protected function validateActor() {
        global $translate;
        if ($this->actor_proxy_id && $this->actor_organisation_id) {
            return true;
        }
        $this->addErrorMessage($translate->_("User not identified."));
        return false;
    }

    //-- Notification wrappers --//
    // TODO: Move all of the notification based functionality to a new utility that wraps the notification classes.

    /**
     * Queue notification for a completed or reviewed or released assessment task to notify the assessor or learner or approver
     *
     * @param $assessment_id
     * @param $distribution_id
     * @param $notify_proxy_id
     * @param $notification_type
     * @param $record_id
     * @param $external_assessor_id
     */
    public function queueCompletedNotification($assessment_id, $distribution_id, $notify_proxy_id, $notification_type , $record_id, $external_assessor_id = null) {
        global $ENTRADA_USER;
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $nuser_type = ($external_assessor_id == null) ? "proxy_id" : "external_assessor_id";
        $assessor_id = ($external_assessor_id == null) ? $ENTRADA_USER->getID() : $external_assessor_id;

        $notification_user = NotificationUser::get($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, $nuser_type);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, 1, 0, 0, $nuser_type);
        }

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_id);
        if ($distribution_schedule) {
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
        }

        if (isset($notification_user) && $notification_user) {
            $notification = Notification::add($notification_user->getID(), $notify_proxy_id, $record_id, $assessor_id);
            if ($notification) {
                $assessment_notification = new Models_Assessments_Notification(array(
                    "adistribution_id" => $distribution_id,
                    "assessment_value" => $assessment_id,
                    "assessment_type" => "assessment",
                    "notified_value" => $notify_proxy_id,
                    "notified_type" => $nuser_type,
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
     * Queue notification for an assessment task that will expire soon, notify the assessor.
     *
     * @param $assessment_id
     * @param $distribution_id
     * @param $notify_proxy_id
     * @param $notification_type
     * @param $assessor_id
     * @param $external
     * @return bool success
     */
    public function queueExpiryWarningNotification($assessment_id, $distribution_id, $notify_proxy_id, $notification_type, $assessor_id, $external) {
        global $ENTRADA_USER;
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $nuser_type = ($external == null) ? "proxy_id" : "external_assessor_id";

        $notification_user = NotificationUser::get($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, $nuser_type);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($notify_proxy_id, $notification_type, $assessment_id, $notify_proxy_id, 1, 0, 0, $nuser_type);
        }

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_id);
        if ($distribution_schedule) {
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
        }

        if (isset($notification_user) && $notification_user) {
            $notification = Notification::add($notification_user->getID(), $notify_proxy_id, $assessment_id, $assessor_id);
            if ($notification) {
                $assessment_notification = new Models_Assessments_Notification(array(
                    "adistribution_id" => $distribution_id,
                    "assessment_value" => $assessment_id,
                    "assessment_type" => "assessment",
                    "notified_value" => $notify_proxy_id,
                    "notified_type" => $nuser_type,
                    "notification_id" => $notification->getID(),
                    "nuser_id" => $notification_user->getID(),
                    "notification_type" => $notification_type,
                    "schedule_id" => (isset($schedule) && $schedule ? $schedule->getID() : NULL),
                    "sent_date" => time()
                ));
                if ($assessment_notification->insert()) {
                    return true;
                } else {
                    application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user.");
                }
            } else {
                application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user.");
            }
        } else {
            application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment notification being sent to a user..");
        }

        return false;
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
     * @param bool $create_as_sent
     * @param bool $check_submittable
     */
    public function queueAssessorNotifications($distribution_assessment, $proxy_id, $schedule_id = NULL, $notify = 1, $send_as_reminder = false, $general_email = true, $create_as_sent = false, $check_submittable = true) {
        if ($notify) {
            require_once("Classes/notifications/NotificationUser.class.php");
            require_once("Classes/notifications/Notification.class.php");

            $attempt_send = true;
            if ($check_submittable) {
                $attempt_send = $distribution_assessment->getMinSubmittable() && $distribution_assessment->getMinSubmittable() > $distribution_assessment->getNumberSubmitted();
            }

            if ($attempt_send) {
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
                    $notification_user = NotificationUser::get($proxy_id, (!$external_assessor && $general_email ? "assessment_general" : "assessment"), $distribution_assessment->getID(), $proxy_id, ($distribution_assessment->getExternalHash() ? "external_assessor_id" : "proxy_id"));
                    if (!$notification_user) {
                        $notification_user = NotificationUser::add($proxy_id, (!$external_assessor && $general_email ? "assessment_general" : "assessment"), $distribution_assessment->getID(), $proxy_id, 1, 0, 0, ($distribution_assessment->getExternalHash() ? "external_assessor_id" : "proxy_id"));
                    }

                    if (isset($notification_user) && $notification_user) {
                        $previous_notification = null;
                        if ($general_email) {
                            $previous_notification = Models_Assessments_Notification::fetchAllByProxyIDAssessmentTypeForToday($proxy_id, "assessment");
                        }
                        if ($external_assessor || !$previous_notification) {
                            $notification = Notification::add($notification_user->getID(), $proxy_id, $distribution_assessment->getID(), ($send_as_reminder) ? 1 : null, $create_as_sent);
                            if ($notification) {
                                $assessment_notification = new Models_Assessments_Notification(array(
                                    "adistribution_id" => $distribution_assessment->getADistributionID(),
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
                }
            }
        }
    }

    /**
     * Adds approver notification. Sends as a reminder for the task to be reviewed.
     *
     * @param Models_Assessments_Assessor $distribution_assessment
     * @param int $approver_id
     */
    public function queueApproverNotifications($distribution_assessment, $approver_id) {
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
    public function queueDelegatorNotifications($distribution, $delegation, $proxy_id, $notify = 1, $create_as_sent = false, $send_as_reminder = false, $general_email = true) {
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

    /** Queue a notification for all PAs of a course when a faculty lecturer user_access record is inactive for a user */
    public function queueInactiveFacultyLecturerNotification($proxy_id = 0, $record_id = 0) {
        global $db;
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $notification_user = NotificationUser::get($proxy_id, "preceptor_inactive_access_request", $record_id);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($proxy_id, "preceptor_inactive_access_request", $record_id, NULL, 1, 0, 0, "proxy_id");
        }

        if (isset($notification_user) && $notification_user) {
            $notification = Notification::add($notification_user->getID(), $proxy_id, $record_id,  0, false);
            if (!$notification) {
                application_log("error", "An error occurred while attempting to create a preceptor_inactive_access_request notification for proxy_id: " . $proxy_id . ". DB said: " . $db->ErrorMsg());
            }
        }
    }

    /** Queue a notification for all PAs of a course when no preceptor is found during a request for preceptor access - most likely requested from a FM learner */
    public function queueNoUserFoundNotification($proxy_id = 0, $record_id = 0) {
        global $db;
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");

        $notification_user = NotificationUser::get($proxy_id, "preceptor_access_request", $record_id);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($proxy_id, "preceptor_access_request", $record_id, NULL, 1, 0, 0, "proxy_id");
        }

        if (isset($notification_user) && $notification_user) {
            $notification = Notification::add($notification_user->getID(), $proxy_id, $record_id,  0, false);
            if (!$notification) {
                application_log("error", "An error occurred while attempting to create a preceptor_access_request notification for proxy_id: " . $proxy_id . ". DB said: " . $db->ErrorMsg());
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
     * @param int|null $expiry_date
     * @param int|null $expiry_notification_date
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
     * @param string $associated_record_type
     * @param int $associated_record_id
     * @param int|null specified_current_record,
     * @param int $rotation_start_date,
     * @param int $rotation_end_date,
     * @param $min_submittable = null,
     * @param $max_submittable = null
     * @param bool $additional_assessment
     * @return bool
     */
    public function addToTaskList(
        $distribution_id,
        $delivery_date,
        $release_date,
        $expiry_date = null,
        $expiry_notification_date = null,
        $start_date,
        $end_date,
        $target_list,
        $assessor_list,
        $task_type = "assessment",
        $grouping = "dates",
        $related_grouping_id = null,
        $delegator_id = null,
        $find_current_record = true,
        $schedule_type = null,
        $delivery_period = 0,
        $period_offset = 0,
        $target_type = "proxy_id",
        $associated_record_type = null,
        $associated_record_id = null,
        $specified_current_record = null,
        $rotation_start_date = 0,
        $rotation_end_date = 0,
        $min_submittable = null,
        $max_submittable = null,
        $additional_assessment = false
    ) {

        $deleted_date = null;
        $current_record = array();

        if ($specified_current_record) {
            $find_current_record = false;
            $found = $specified_current_record;
        } else {
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
        }

        // We found a current record, so save it and keep its deleted date, if any.
        if ($find_current_record || $specified_current_record) {
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
            case "assessment":
                $hash = md5(serialize($assessor_list));
                $storage_key = "$delivery_date-$release_date-$start_date-$end_date-$hash";
                break;
            case "dates":
            default:
                $storage_key = "$delivery_date-$release_date-$start_date-$end_date";
                break;
        }

        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($distribution_id);

        // Save the item, grouped by distribution ID and the related storage key.
        $this->task_list[$distribution_id][$storage_key] = array(
            "meta" => array(
                "active" => true, // If we ever want to include state information about tasks, this is where we would add it.
                "should_exist" => $should_exist,
                "organisation_id" => $distribution ? $distribution->getOrganisationID() : false,
                "feedback_required" => $distribution->getFeedbackRequired(),
                "form_id" => $distribution->getFormID(),
                "course_id" => $distribution->getCourseID(),
                "task_type" => $task_type, // Should only be "assessment", "delegation", or "learning_event_assessment"
                "external_hash" => $current_record && is_a($current_record, "Models_Assessments_Assessor") ? $current_record->getExternalHash() : null,
                "min_submittable" => $min_submittable,
                "max_submittable" => $max_submittable,
                "delivery_date" => $delivery_date,
                "release_date" => $release_date,
                "start_date" => $start_date,
                "end_date" => $end_date,
                "rotation_start_date" => $rotation_start_date,
                "rotation_end_date" => $rotation_end_date,
                "expiry_date" => $expiry_date,
                "expiry_notification_date" => $expiry_notification_date,
                "deleted_date" => $deleted_date,
                "schedule_type" => $schedule_type,
                "delivery_period" => $delivery_period,
                "period_offset" => $period_offset,
                "target_count" => count($target_list),
                "target_type" => $target_type,
                "assessor_count" => count($assessor_list),
                "associated_record_type" => $associated_record_type,
                "associated_record_id" => $associated_record_id,
                "additional_assessment" => $additional_assessment
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

    /**
     * Build localized descriptions of the assessors associated with this distribution.
     * Optionally return it as one string.
     *
     * @param array $assessors
     * @param bool $summary_as_string
     * @return array|string
     */
    protected function buildAssessorsSummary($assessors, $summary_as_string = false) {
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
                        $assessor_types["course_id"][] = $translate->_("Course audience members");
                        break;
                    case "group_id":
                        $assessor_types["group_id"][] = $translate->_("Cohort");
                        break;
                    case "cgroup_id":
                        $assessor_types["group_id"][] = $translate->_("Course Group");
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
    protected function buildTargetsSummary($targets, $summary_as_string = false) {
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
                    case "cgroup_id":
                        $targets_types["cgroup_id"][] = $translate->_("Course Group");
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

    /**
     * Fetch a locally cached response descriptor record.
     *
     * @param $ardescriptor_id
     * @return bool|mixed|Models_Base
     */
    protected function fetchResponseDescriptor($ardescriptor_id) {
        if ($this->isInStorage("response-descriptor", $ardescriptor_id)) {
            return $this->fetchFromStorage("response-descriptor", $ardescriptor_id);
        } else {
            $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($ardescriptor_id);
            $this->addToStorage("response-descriptor", $descriptor, $ardescriptor_id);
            return $descriptor;
        }
    }

    /**
     * Fetch a locally cached descriptor text string.
     *
     * @param $ardescriptor_id
     * @return bool|mixed|string
     */
    protected function fetchResponseDescriptorText($ardescriptor_id) {
        $descriptor_text = "";
        if ($descriptor = $this->fetchResponseDescriptor($ardescriptor_id)) {
            $descriptor_text = $descriptor->getDescriptor();
        }
        return $descriptor_text;
    }

    /**
     * Execute a db->GetAll() and reorder the results by specified key.
     *
     * @param string $index
     * @param string $query (SQL)
     * @param array $params
     * @return array
     */
    protected function getAllArrayIndexed($index, $query, $params = array()) {
        global $db;
        $results = $db->GetAll($query, $params);
        $ordered = array();
        if (is_array($results) && !empty($results)) {
            foreach ($results as $result) {
                $ordered[$result[$index]] = $result;
            }
        }
        return $ordered;
    }

    //-- Assessment options related functionality --//

    /**
     * Process distribution releasing options as they apply to each existing assessment.
     *
     * @param $distribution_id
     */
    public function processDistributionAssessmentOptions($distribution_id) {

        $assessments = Models_Assessments_Assessor::fetchAllByDistributionID($distribution_id);
        if (!$assessments) {
            return;
        }
        $assessment_siblings = array();
        // Group the assessments by delivery date, as these will be siblings.
        foreach ($assessments as $assessment) {
            $assessment_siblings[$assessment->getDeliveryDate()][] = $assessment;
        }
        $assessment_options_model = new Models_Assessments_Options();
        $target_task_release_model = new Models_Assessments_Distribution_Target_TaskReleases();
        $target_task_release = $target_task_release_model->fetchRowByADistributionID($distribution_id);
        $target_report_release_model = new Models_Assessments_Distribution_Target_ReportReleases();
        $target_report_release = $target_report_release_model->fetchRowByADistributionID($distribution_id);

        foreach ($assessment_siblings as $siblings) {
            // Keep a record of the assessments siblings for easy querying later, otherwise we have no idea what is truly "related".
            $siblings_string = "";
            foreach ($siblings as $sibling) {
                $siblings_string .= $sibling->getID() . ",";
            }
            $siblings_string = rtrim($siblings_string, ",");

            foreach ($siblings as $task) {
                // Existing assessment options.
                $existing_assessment_options = $assessment_options_model->fetchAllByDassessmentID($task->getID());
                /**
                 * Target options.
                 */
                /* Task visibility. */
                if ($target_task_release) {
                    $this->processTargetTaskReleaseOption($target_task_release, $existing_assessment_options, $task->getID(), $siblings_string, $distribution_id);
                }
                /* Self-reporting. */
                if ($target_report_release) {
                    $this->processTargetReportReleaseOption($target_report_release, $existing_assessment_options, $task->getID(), $siblings_string, $distribution_id);
                    $this->processTargetReportCommentOption($target_report_release, $existing_assessment_options, $task->getID(), $siblings_string, $distribution_id);
                }
            }
        }
    }

    /**
     * Process target task releasing options from the cbl_assessment_distribution_target_task_releases table
     *
     * @param $target_task_release
     * @param $existing_assessment_options
     * @param $dassessment_id
     * @param $siblings
     * @param $distribution_id
     */
    private function processTargetTaskReleaseOption($target_task_release, $existing_assessment_options, $dassessment_id, $siblings, $distribution_id) {
        global $db;
        $option_name = $option_value = "";
        // Expected outcome for this option.
        switch ($target_task_release->getTargetOption()) {
            case "always":
                // Task always visible to target.
                $option_name = "target_viewable";
                $option_value = "true";
                break;
            case "never":
                // Task never visible to target.
                $option_name = "target_viewable";
                $option_value = "false";
                break;
            case "percent":
                // Task visible to targets once they have completed a percentage of their targets for related tasks.
                $option_name = "target_viewable_percent";
                $option_value = $target_task_release->getPercentThreshold();
                break;
            default:
                break;
        }

        $exists = false;
        // Ensure the existing option matches the expected outcome.
        foreach ($existing_assessment_options as $existing_assessment_option) {
            if ($existing_assessment_option->getOptionName() == $option_name) {

                // Update existing task visibility rule.
                if ($existing_assessment_option->getOptionValue() != $option_value || $existing_assessment_option->getAssessmentSiblings() != $siblings) {

                    $existing_assessment_option->setAssessmentSiblings($siblings);
                    $existing_assessment_option->setOptionValue($option_value);
                    $existing_assessment_option->setUpdatedDate(time());
                    $existing_assessment_option->setUpdatedBy(1);

                    if ($existing_assessment_option = $existing_assessment_option->update()) {
                        $exists = $existing_assessment_option;
                    } else {
                        application_log("error", "Unable to update assessment option {$existing_assessment_option->getID()}, DB said " . $db->ErrorMsg());
                    }
                } else {
                    $exists = $existing_assessment_option;
                }
            } elseif ($existing_assessment_option->getOptionName() == "target_viewable" || $existing_assessment_option->getOptionName() == "target_viewable_percent") {

                // Remove old task visibility rule.
                if (!$existing_assessment_option->delete()) {
                    application_log("error", "Unable to delete assessment option {$existing_assessment_option->getID()}, DB said " . $db->ErrorMsg());
                }
            }
        }

        if (!$exists) {
            // Create new assessment option.
            if (!$this->createAssessmentOption(
                $dassessment_id,
                $siblings,
                $distribution_id,
                $option_name,
                $option_value
            )) {
                application_log("error", "Unable to insert task target visible assessment option, DB said " . $db->ErrorMsg());
            }
        }
    }

    /**
     * Process target reporting options from the cbl_assessment_distribution_target_report_releases table.
     *
     * @param $target_report_release
     * @param $existing_assessment_options
     * @param $dassessment_id
     * @param $siblings
     * @param $distribution_id
     */
    private function processTargetReportReleaseOption($target_report_release, $existing_assessment_options, $dassessment_id, $siblings, $distribution_id) {
        global $db;
        $option_name = $option_value = "";
        // Expected outcome for this option.
        switch ($target_report_release->getTargetOption()) {
            case "always":
                // Report always visible to target.
                $option_name = "target_reportable";
                $option_value = "true";
                break;
            case "never":
                // Report never visible to target.
                $option_name = "target_reportable";
                $option_value = "false";
                break;
            case "percent":
                // Report visible to targets once they have completed a percentage of their targets for related reports.
                $option_name = "target_reportable_percent";
                $option_value = $target_report_release->getPercentThreshold();
                break;
            default:
                break;
        }

        $exists = false;
        // Ensure the existing option matches the expected outcome.
        foreach ($existing_assessment_options as $existing_assessment_option) {
            if ($existing_assessment_option->getOptionName() == $option_name) {

                // Update existing target reporting rule.
                if ($existing_assessment_option->getOptionValue() != $option_value || $existing_assessment_option->getAssessmentSiblings() != $siblings) {

                    $existing_assessment_option->setAssessmentSiblings($siblings);
                    $existing_assessment_option->setOptionValue($option_value);
                    $existing_assessment_option->setUpdatedDate(time());
                    $existing_assessment_option->setUpdatedBy(1);

                    if ($existing_assessment_option = $existing_assessment_option->update()) {
                        $exists = $existing_assessment_option;
                    } else {
                        application_log("error", "Unable to update assessment option {$existing_assessment_option->getID()}, DB said " . $db->ErrorMsg());
                    }
                } else {
                    $exists = $existing_assessment_option;
                }
            } elseif ($existing_assessment_option->getOptionName() == "target_reportable" || $existing_assessment_option->getOptionName() == "target_reportable_percent") {

                // Remove old target reporting rule.
                if (!$existing_assessment_option->delete()) {
                    application_log("error", "Unable to delete assessment option {$existing_assessment_option->getID()}, DB said " . $db->ErrorMsg());
                }
            }
        }

        if (!$exists) {
            // Create new assessment option.
            if (!$this->createAssessmentOption(
                $dassessment_id,
                $siblings,
                $distribution_id,
                $option_name,
                $option_value
            )) {
                application_log("error", "Unable to insert target reportable assessment option, DB said " . $db->ErrorMsg());
            }
        }
    }

    /**
     * Process target reporting comment options from the cbl_assessment_distribution_target_report_releases table.
     *
     * @param $target_report_release
     * @param $existing_assessment_options
     * @param $dassessment_id
     * @param $siblings
     * @param $distribution_id
     */
    private function processTargetReportCommentOption($target_report_release, $existing_assessment_options, $dassessment_id, $siblings, $distribution_id) {
        global $db;
        $option_name = "target_reporting_comment_anonymity";
        $option_value = $target_report_release->getCommentOptions();

        $exists = false;
        foreach ($existing_assessment_options as $existing_assessment_option) {
            if ($existing_assessment_option->getOptionName() == $option_name) {

                // Update existing target reporting rule.
                if ($existing_assessment_option->getOptionValue() != $option_value || $existing_assessment_option->getAssessmentSiblings() != $siblings) {

                    $existing_assessment_option->setAssessmentSiblings($siblings);
                    $existing_assessment_option->setOptionValue($option_value);
                    $existing_assessment_option->setUpdatedDate(time());
                    $existing_assessment_option->setUpdatedBy(1);

                    if ($existing_assessment_option = $existing_assessment_option->update()) {
                        $exists = $existing_assessment_option;
                    } else {
                        application_log("error", "Unable to update assessment option {$existing_assessment_option->getID()}, DB said " . $db->ErrorMsg());
                    }
                } else {
                    $exists = $existing_assessment_option;
                }
            }
        }

        if (!$exists) {
            // Create new assessment option.
            if (!$this->createAssessmentOption(
                $dassessment_id,
                $siblings,
                $distribution_id,
                $option_name,
                $option_value
            )) {
                application_log("error", "Unable to insert target report comment assessment option, DB said " . $db->ErrorMsg());
            }
        }
    }

    /**
     * Build and insert a new cbl_distribution_assessment_options record based on provided parameters.
     *
     * @param $dassessment_id
     * @param $siblings,
     * @param $distribution_id
     * @param $option_name
     * @param $option_value
     * @param null $actor_id
     * @return $this|bool
     */
    private function createAssessmentOption($dassessment_id, $siblings, $distribution_id, $option_name, $option_value, $actor_id = null) {
        $assessment_option = new Models_Assessments_Options(array(
            "adistribution_id"      => $distribution_id,
            "dassessment_id"        => $dassessment_id,
            "actor_id"              => $actor_id,
            "option_name"           => $option_name,
            "option_value"          => $option_value,
            "assessment_siblings"   => $siblings,
            "created_date"          => time(),
            "created_by"            => 1
        ));
        return $assessment_option = $assessment_option->insert();
    }

    /**
     * Process distribution target options to determine if this target is wanted by the distribution.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param $target_type
     * @param $target_value
     * @param $delivery_date
     *
     * @return bool
     */
    protected function isEligibleTarget($distribution, $target_type, $target_value, $delivery_date) {

        // We only need to filter learner targets.
        if ($target_type != "proxy_id") {
            return true;
        }
        switch ($distribution->getTargetOption()) {
            case "all":
                return true;

            case "only_cbme":
                // Figure out if the learner is CBME based for the time period.
                $learner_level_model = new Models_User_LearnerLevel();
                $cbme_learner_at_delivery = $learner_level_model->fetchAllByProxyIDOrganisationIDCourseIDDateCBME($target_value, $distribution->getOrganisationID(), $distribution->getCourseID(), $delivery_date, 1);
                if ($cbme_learner_at_delivery) {
                    return true;
                }
                break;

            case "non_cbme":
                // Figure out if the learner is non-CBME based for the time period.
                $learner_level_model = new Models_User_LearnerLevel();
                $levels_at_delivery = $learner_level_model->fetchAllByProxyIDOrganisationIDCourseIDDateCBME($target_value, $distribution->getOrganisationID(), $distribution->getCourseID(), $delivery_date);

                // If we have learner levels, ensure they are not CBME. If there are no levels we can't reliably exclude the learner.
                $non_cbme = true;
                if ($levels_at_delivery) {
                    foreach ($levels_at_delivery as $level) {
                        if ($level["cbme"]) {
                            $non_cbme = false;
                        }
                    }
                }
                return $non_cbme;
        }
        return false;
    }

    /**
     * Fetch existing assessments for a distribution and ensure the expiry date matches the rule of the distribution record.
     *
     * @param $distribution Models_Assessments_Distribution
     */
    protected function processDistributionAssessmentExpiry($distribution) {
        global $db;

        $assessments = Models_Assessments_Assessor::fetchAllByDistributionID($distribution->getID());
        if (!$assessments) {
            return;
        }

        /** @var $assessment Models_Assessments_Assessor */
        foreach ($assessments as $assessment) {
            $expiry_date = ($distribution->getExpiryOffset() ? ($assessment->getDeliveryDate() + $distribution->getExpiryOffset()) : null);
            $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

            if ($assessment->getExpiryDate() != $expiry_date) {
                $assessment->setExpiryDate($expiry_date);
                if (!$assessment->update()) {
                    application_log("error", "Unable to update expiry date for assessment {$assessment->getID()}, DB said " . $db->ErrorMsg());
                }
            }

            if ($assessment->getNotificationExpiryDate() != $expiry_notification_date) {
                $assessment->setExpiryNotificationDate($expiry_notification_date);
                if (!$assessment->update()) {
                    application_log("error", "Unable to update expiry notification date for assessment {$assessment->getID()}, DB said " . $db->ErrorMsg());
                }
            }
        }
    }

    //-- Private methods --//

    /**
     * Reorder the array by the ID of the given object.
     *
     * @param $result_set
     * @return array
     */
    public function addToArrayByPrimaryKey($result_set) {
        $return_set = array();
        foreach ($result_set as $object) {
            $return_set[$object->getID()] = $object;
        }
        return $return_set;
    }

    //--- Temporary data storage functionality wrappers ---//

    protected function isInStorage($type, $index = null, $item = null) {
        return $this->memory_storage->isInStorage($type, $index, $item);
    }

    protected function addToStorage($type, $item, $index = null) {
        $this->memory_storage->addToStorage($type, $item, $index);
    }

    protected function fetchFromStorage($type, $index) {
        return $this->memory_storage->fetchFromStorage($type, $index);
    }

    protected function removeFromStorage($type, $index = null) {
        return $this->memory_storage->removeFromStorage($type, $index);
    }

    protected function fetchStorage() {
        return $this->memory_storage->fetchStorage();
    }

    /**
     * Clear all existing storage.
     *
     * @param bool $local
     * @param bool $global
     */
    protected function clearStorage($local = true, $global = true) {
        $this->memory_storage->clearStorage($local, $global);
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

    //-- Session Preferences wrapping functionality --//

    /**
     * Fetch session preferences for a module.
     *
     * @param $module
     * @return array
     */
    public function getAssessmentPreferences($module) {
        global $db, $ENTRADA_USER;
        // TODO: Make this independant of ENTRADA_USER
        $query	= "SELECT `preferences` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
        $result	= $db->GetRow($query);
        if($result) {
            if($result["preferences"]) {
                $preferences = @unserialize($result["preferences"]);
                if(@is_array($preferences)) {
                    $_SESSION[APPLICATION_IDENTIFIER][$module] = $preferences;
                }
            }
        }
        return ((isset($_SESSION[APPLICATION_IDENTIFIER][$module])) ? $_SESSION[APPLICATION_IDENTIFIER][$module] : array());
    }

    /**
     * Update session preferences for a module.
     *
     * @param $module
     * @return bool
     */
    public function updateAssessmentPreferences($module) {
        global $db, $ENTRADA_USER;
        // TODO: Make this independant of ENTRADA_USER
        if(isset($_SESSION[APPLICATION_IDENTIFIER][$module])) {
            $query	= "SELECT `preference_id` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
            $result	= $db->GetRow($query);
            if($result) {
                if(!$db->AutoExecute("`".AUTH_DATABASE."`.`user_preferences`", array("preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "UPDATE", "preference_id = ".$db->qstr($result["preference_id"]))) {
                    application_log("error", "Unable to update the users database preferences for this module. Database said: ".$db->ErrorMsg());
                    return false;
                }
            } else {
                if(!$db->AutoExecute(AUTH_DATABASE.".user_preferences", array("app_id" => AUTH_APP_ID, "proxy_id" => $ENTRADA_USER->getID(), "module" => $module, "preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "INSERT")) {
                    application_log("error", "Unable to insert the users database preferences for this module. Database said: ".$db->ErrorMsg());
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Build a date-sensitive set of formatted timestamp strings. If the start and end dates are the same, only the date for the first one is
     * included in the return value. If both are different, both full timestamps are returned.
     *
     * Returns array with false values on failure.
     *
     * Build an hour-sensitive timeframe string.
     * E.g.:
     * Jan 10, 2016 10:00 PM to 11:00 PM
     * OR, if the timestamps are separated by one or more days:
     * Jan 10, 2016 10:00 PM to Jan 11, 2016 1:00 AM
     *
     * Returns the built strings in an array.
     *
     * @param $start_date
     * @param $end_date
     * @param $date_format
     * @param $time_format
     * @return array
     */
    public static function buildTimeframeStrings($start_date, $end_date, $date_format = "Y-m-d", $time_format = "H:i") {
        $timeframe_strings = array("timeframe_start" => false, "timeframe_end" => false);
        if ($start_date && $end_date) {
            $ymd_start_date = date($date_format, $start_date);
            $ymd_end_date = date($date_format, $end_date);
            $hms_start_time = date($time_format, $start_date);
            $hms_end_time = date($time_format, $end_date);
            $timeframe_strings["timeframe_start"] = "$ymd_start_date $hms_start_time";
            if ($ymd_start_date == $ymd_end_date) {
                $timeframe_strings["timeframe_end"] = $hms_end_time;
            } else {
                $timeframe_strings["timeframe_end"] = "$ymd_end_date $hms_end_time";
            }
        }
        return $timeframe_strings;
    }

    /**
     * Fetch target info based on target type and target value.
     *
     * @param $target_type
     * @param $target_value
     * @return array
     */
    public static function getTargetInfo($target_type, $target_value) {
        $target_info = array("name" => null);

        switch ($target_type) {
            case "proxy_id":
                $user = Models_User::fetchRowByID($target_value);
                if ($user) {
                    $target_info["name"] = "{$user->getFullname(false)}";
                    $target_info["number"] = "{$user->getNumber()}";
                }
                break;
            case "course_id":
                $course = Models_Course::fetchRowByID($target_value);
                if ($course) {
                    $target_info["name"] = "{$course->getCourseName()}";
                }
                break;
            case "group_id":
                $group = Models_Group::fetchRowByID($target_value);
                if ($group) {
                    $target_info["name"] = "{$group->getGroupName()}";
                }
                break;
            case "cgroup_id":
                $course_group = Models_Course_Group::fetchRowByID($target_value);
                if ($course_group) {
                    $target_info["name"] = "{$course_group->getGroupName()}";
                }
                break;
            case "schedule_id":
                $schedule = Models_Schedule::fetchRowByID($target_value);
                if ($schedule) {
                    $target_info["name"] = "{$schedule->getTitle()}";
                    if ($schedule->getScheduleType() == "rotation_block") {
                        $parent_schedule = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                        if ($parent_schedule) {
                            $target_info["name"] = "{$parent_schedule->getTitle()} - {$target_info["name"]}";
                        }
                    }
                }
                break;
            case "event_id":
                $event = Models_Event::fetchRowByID($target_value);
                if ($event) {
                    $target_info["name"] = "{$event->getEventTitle()}";
                }
                break;
            case "external_hash" :
                $course_contact_model = new Models_Assessments_Distribution_CourseContact();
                $external_target = $course_contact_model->fetchRowByAssessorValueAssessorType($target_value, "external");
                if ($external_target) {
                    $entrada_base = new Entrada_Assessments_Base();
                    $user = $entrada_base->getUserByType($external_target->getAssessorValue(), $external_target->getAssessorType());
                    if ($user) {
                        $target_info["name"] = "{$user->getFirstname()} {$user->getLastname()}";
                    }
                }
                break;
        }

        return $target_info;
    }

    //-- Error message functionality --//

    /**
     * Get all errors.
     *
     * @return array
     */
    public function getErrorMessages() {
        return $this->error_messages;
    }

    /**
     * Add a single error message.
     *
     * @param $single_error_string
     * @return string
     */
    public function addErrorMessage($single_error_string) {
        $this->error_messages[] = $single_error_string;
        return $single_error_string;
    }

    /**
     * Add multiple error messages.
     *
     * @param array $error_strings
     */
    public function addErrorMessages($error_strings) {
        $this->error_messages = array_merge($this->error_messages, $error_strings);
    }

    /**
     * Clear the stored error messages.
     */
    public function clearErrorMessages() {
        $this->error_messages = array();
    }

    //-- Set Actor --//

    public function setActorOrganisationID($organisation_id) {
        $this->actor_organisation_id = $organisation_id;
    }

    public function setActorProxyID($proxy_id) {
        $this->actor_proxy_id = $proxy_id;
    }

    public function setActorGroup($group) {
        $this->actor_group = $group;
    }


    //-- Dataset and worker object manipulation functionality --//
    // ADRIAN-TODO: Change the dataset implementation to be implemented as traits.

    /**
     * Return true when the dataset is empty.
     *
     * @return bool
     */
    public function isDatasetEmpty() {
        if (empty($this->dataset)) {
            return true;
        }
        return false;
    }

    /**
     * Set the stale flag in the dataset, if the dataset exists.
     * Functionality that calls this method should behave as though an empty dataset is stale.
     */
    public function setStale() {
        if (!empty($this->dataset)) {
            if (array_key_exists("is_stale", $this->dataset)) {
                $this->dataset["is_stale"] = true;
            }
        }
    }

    /**
     * Check if a dataset is stale.
     * Empty datasets are considered to be stale.
     *
     * @return bool
     */
    public function isStale() {
        if (empty($this->dataset)) {
            return true;
        }
        if (array_key_exists("is_stale", $this->dataset)) {
            return $this->dataset["is_stale"];
        }
        return true;
    }

    /**
     * Set one of the worker objects as stale (to be refreshed at the next fetchData()).
     *
     * @param $object_type
     */
    protected function setWorkerStale($object_type) {
        if (property_exists($this, $object_type)) {
            if (is_object($this->{$object_type})) {
                if (method_exists($this->{$object_type}, "setStale")) {
                    $this->{$object_type}->setStale();
                }
            }
        }
    }

    /**
     * Simple function to determine which ID to use; the internal one or a specific one.
     * Null is a valid $specified_id.
     * Passing false as $specified_id will use the internal ID instead of the parameter.
     *
     * @param $id_type
     * @param bool|int $specified_id
     * @return bool|int
     */
    protected function whichID($id_type, $specified_id = false) {
        $id_to_use = false;
        if (property_exists($this, $id_type)) {
            $id_to_use = $this->$id_type;
            if ($specified_id !== false) {
                $id_to_use = $specified_id;
            }
        }
        return $id_to_use;
    }

    //-- Validation functionality --//

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