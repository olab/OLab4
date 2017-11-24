<?php

class Controllers_Exam_Post extends Controllers_Base {
    protected $post;
    protected $default_error_msg = "Please select a <strong>%s</strong> to continue to the next step.";

    protected $validation_rules = array(
        "post_id"                   => array(
            "label"                 => "Exam Post Identifier",
            "db_fieldname"          => "post_id",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "int",
            "step"                  => 1
        ),
        "exam"                      => array(
            "label"                 => "Exam Identifier",
            "db_fieldname"          => "exam_id",
            "required"              => true,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "int",
            "step"                  => 1
        ),
        "target_type"               => array(
            "label"                 => "Target Type",
            "db_fieldname"          => "target_type",
            "required"              => true,
            "sanitization_params"   => array("trim", "striptags"),
            "data_type"             => "text",
            "step"                  => 1
        ),
        "target_id"                 => array(
            "label"                 => "Target ID",
            "db_fieldname"          => "target_id",
            "required"              => true,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "int",
            "step"                  => 1
        ),
        "exam_title"                => array(
            "label"                 => "Exam Title",
            "db_fieldname"          => "title",
            "required"              => true,
            "sanitization_params"   => array("trim", "striptags"),
            "data_type"             => "text",
            "step"                  => 1
        ),
        "exam_description"          => array(
            "label"                 => "Exam Description",
            "db_fieldname"          => "description",
            "required"              => false,
            "sanitization_params"   => array("allowedtags"),
            "data_type"             => "text",
            "step"                  => 1
        ),
        "exam_instructions"         => array(
            "label"                 => "Exam Instructions",
            "db_fieldname"          => "instructions",
            "required"              => false,
            "sanitization_params"   => array("allowedtags"),
            "data_type"             => "text",
            "step"                  => 1
        ),
        "max_attempts"              => array(
            "label"                 => "Number of attempts allowed",
            "required"              => true,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "int",
            "step"                  => 2
        ),
        "backtrack"                 => array(
            "label"                 => "Backtrack",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "secure"                    => array(
            "label"                 => "Secure",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "secure_mode"               => array(
            "label"                 => "Security Type",
            "db_fieldname"          => "secure_mode",
            "required"              => false,
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "exam_url"                  => array(
            "label"                 => "Exam url",
            "db_fieldname"          => "exam_url",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure_mode" => "rp_now",
                ),
                "type" => "variable"
            ),
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "exam_sponsor"              => array(
            "label"                 => "Exam Sponsor",
            "db_fieldname"          => "exam_sponsor",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure" => true,
                    "secure_mode" => "rp_now",
                ),
                "type" => "all"
            ),
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "exam_sponsor",
            "step"                  => 3
        ),
        "rpnow_reviewed_exam"      => array(
            "label"                 => "Should Software Secure review the exam?",
            "db_fieldname"          => "rpnow_reviewed_exam",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 3
        ),
        "rpnow_reviewer_notes"      => array(
            "label"                 => "Rp-Now Reviewer Notes",
            "db_fieldname"          => "rpnow_reviewer_notes",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure" => true,
                    "secure_mode" => "rp_now",
                    "rpnow_reviewed_exam" => "1"
                ),
                "type" => "all"
            ),
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "mark_faculty_review" => array(
            "label"                 => "Allow ScratchPad to be marked for Faculty Review",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "allow_calculator" => array(
            "label"                 => "Calculator",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "use_self_timer" => array(
            "label"                 => "Self Timer",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "mandatory"                 => array(
            "label"                 => "Required",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "auto_save"                 => array(
            "label"                 => "Auto Save Time",
            "required"              => false,
            "default"               => 30,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "seconds",
            "step"                  => 2
        ),
        "auto_submit"           => array(
            "label"                 => "Auto Submit",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "hide_exam"            => array(
            "label"                 => "Hide from Learners",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "timeframe"           => array(
            "label"                 => "Time Frame",
            "required"              => true,
            "default"               => 0,
            "sanitization_params"   => array("trim", "striptags"),
            "data_type"             => "text",
            "step"                  => 2
        ),
        "use_time_limit"           => array(
            "label"                 => "Use Time Limit",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure" => true,
                ),
                "type" => "variable"
            ),
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "time_limit_hours"          => array(
            "label"                 => "Time Limit Hours",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure" => true,
                    "time_limit_mins" => 0,
                ),
                "type" => "all"
            ),
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "hours",
            "step"                  => 2
        ),
        "time_limit_mins"           => array(
            "label"                 => "Time Limit Minutes",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure" => true,
                    "time_limit_hours" => 0,
                ),
                "type" => "all"
            ),
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "minutes",
            "step"                  => 2
        ),
        "use_exam_start_date"       => array(
            "label"                 => "Use Exam Start Date",
            "required"              => false,
            "default"               => 1,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "use_exam_end_date"         => array(
            "label"                 => "Use Exam End Date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "use_exam_submission_date"  => array(
            "label"                 => "Use Exam Submission Deadline",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 2
        ),
        "exam_start_date"   => array(
            "label"                 => "Exam Start Date",
            "db_fieldname"          => "start_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "data_type"             => "date",
            "step"                  => 2
        ),
        "exam_start_time"   => array(
            "label"                 => "Exam Start Time",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "data_type"             => "time",
            "step"                  => 2
        ),
        "exam_end_date"     => array(
            "label"                 => "Exam End Date",
            "db_fieldname"          => "end_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "data_type"             => "date",
            "step"                  => 2
        ),
        "exam_end_time"     => array(
            "label"                 => "Exam End Time",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "data_type"             => "time",
            "step"                  => 2
        ),
        "exam_submission_date"     => array(
            "label"                 => "Submission Deadline Date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "data_type"             => "date",
            "step"                  => 2
        ),
        "exam_submission_time"     => array(
            "label"                 => "Submission Deadline Time",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "data_type"             => "time",
            "step"                  => 2
        ),
        "use_resume_password"  => array(
            "label"                 => "Use Resume Password",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 3
        ),
        "resume_password_rp_now"     => array(
            "label"                 => "Exam Password",
            "db_fieldname"          => "resume_password",
            "required"              => false,
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "resume_password_basic"     => array(
            "label"                 => "Exam Begin Password",
            "db_fieldname"          => "resume_password",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "secure_mode" => "basic",
                ),
                "type" => "variable"
            ),
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "resume_password_seb"       => array(
            "label"                 => "Resume Password",
            "db_fieldname"          => "resume_password",
            "required"              => false,
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 3
        ),
        "secure_key"                => array(
            "label"                 => "Secure Key",
            "required"              => false,
            "sanitization_params"   => array('array'),
            "data_type"             => "time",
            "step"                  => 3
        ),
        "exam_exceptions"           => array(
            "label"                 => "Exam Exceptions",
            "required"              => false,
            "sanitization_params"   => array("trim", "striptags"),
            "data_type"             => "exam_exceptions",
            "step"                  => 4
        ),
        "release_score"              => array(
            "label"                 => "Release scores allowed",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "release_feedback"           => array(
            "label"                 => "Release feedback allowed",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "release_incorrect_responses" => array(
            "label"                 => "Release Incorrect Feedback",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "release_start_date"   => array(
            "label"                 => "Release Start Date",
            "db_fieldname"          => "release_start_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "data_type"             => "date",
            "step"                  => 5
        ),
        "release_start_time"   => array(
            "label"                 => "Release Start Time",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "data_type"             => "time",
            "step"                  => 5
        ),
        "release_end_date"     => array(
            "label"                 => "Release End Date",
            "db_fieldname"          => "release_end_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "data_type"             => "date",
            "step"                  => 5
        ),
        "release_end_time"     => array(
            "label"                 => "Release End Time",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "data_type"             => "time",
            "step"                  => 5
        ),
        "use_release_start_date"      => array(
            "label"                 => "Uses Release Start Date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "use_release_end_date"        => array(
            "label"                 => "Uses Release End Date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "use_re_attempt_threshold"  => array(
            "label"                 => "Uses Re-Attempt Threshold",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "data_type"             => "bool",
            "step"                  => 5
        ),
        "re_attempt_threshold"      => array(
            "label"                 => "Re-Attempt Threshold",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "striptags"),
            "data_type"             => "int",
            "step"                  => 5
        ),
        "re_attempt_threshold_attempts"  => array(
            "label"                 => "Re-Attempt Threshold Attempts",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "int",
            "step"                  => 5
        ),
        "grade_book"              => array(
            "label"                 => "Grade Book Assessment",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "data_type"             => "grade_book",
            "step"                  => 5
        ),
    );

    public function getPost() {
        return $this->post;
    }

    public function displayData($exam_detail, $value) {
        $type  = $exam_detail["data_type"];

        switch ($type) {
            case "date":
                if ($value != 0) {
                    $return = date("m-d-Y", $value);
                } else {
                    $return = "NA";
                }
                break;
            case "time":
                if ($value != 0) {
                    $return  = date("g:i a", strtotime($value));
                } else {
                    $return = "NA";
                }
                break;
            case "bool":
                if ($value === 1 || $value === true) {
                    $return = "Yes";
                } else {
                    $return = "No";
                }
                break;
            case "grade_book":
                $gradebook = Models_Gradebook_Assessment::fetchRowByID($value);
                if ($gradebook && is_object($gradebook)) {
                    $return = $gradebook->getName();
                } else {
                    $return = "NA";
                }
                break;
            case "exam_exceptions":
                if (!is_array($value)) {
                    $array = json_decode($value, true);
                }
                $return = "";
                if ($array && is_array($array) && !empty($array)) {
                    foreach ($array as $key => $user) {
                        $return .= $user["label"] . "</br>";
                    }
                }
                break;
            case "exam_sponsor":
                $user = Models_User::fetchRowByID($value);
                if ($user && is_object($user)) {
                    $return = $user->getFullname(false);
                } else {
                    $return = "NA";
                }
                break;
            case "seconds":
            case "hours":
            case "minutes":
            case "int":
            case "text":
            default:
                $return = $value;
                break;
        }
        return $return;
    }

    public function save() {
        global $ENTRADA_USER, $translate, $db;

        $method = "insert";
        $PROCESSED = $this->getValidatedData(true);

        if (isset($this->validated_data["post_id"]) && $tmp_input = clean_input($this->validated_data["post_id"], array("trim", "int"))) {
            $method = "update";
        } else {
            $PROCESSED["created_date"] = time();
            $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
        }

        $PROCESSED["updated_date"] = time();
        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();

        if (isset($PROCESSED["backtrack"]) && $PROCESSED["backtrack"] == true) {
            $PROCESSED["backtrack"] = 1;
        }

        if (isset($PROCESSED["start_date"]) && $PROCESSED["start_date"] && isset($PROCESSED["exam_start_time"]) && $PROCESSED["exam_start_time"]) {
            $time = explode(":", $PROCESSED["exam_start_time"]);
            $hours = $time[0] * 60 * 60;
            $minutes = $time[1] * 60;
            $PROCESSED["start_date"] += $hours + $minutes;
        }

        if (isset($PROCESSED["end_date"]) && $PROCESSED["end_date"] && isset($PROCESSED["exam_end_time"]) && $PROCESSED["exam_end_time"]) {
            $time = explode(":", $PROCESSED["exam_end_time"]);
            $hours = $time[0] * 60 * 60;
            $minutes = $time[1] * 60;
            $PROCESSED["end_date"] += $hours + $minutes;
        }

        if (!isset($PROCESSED["time_limit_hours"])) {
            $PROCESSED["time_limit_hours"] = 0;
        }

        if (!isset($PROCESSED["time_limit_mins"])) {
            $PROCESSED["time_limit_mins"] = 0;
        }

        if (isset($PROCESSED["time_limit_hours"]) && isset($PROCESSED["time_limit_mins"])) {
            $hours      = (int)$PROCESSED["time_limit_hours"] * 60;
            $minutes    = (int)$PROCESSED["time_limit_mins"];
            $PROCESSED["time_limit"] = $hours + $minutes;
        }

        if (isset($PROCESSED["exam_submission_date"]) && $PROCESSED["exam_submission_date"] && isset($PROCESSED["exam_submission_time"]) && $PROCESSED["exam_submission_time"]) {
            $time = explode(":", $PROCESSED["exam_submission_time"]);
            $hours = $time[0] * 60 * 60;
            $minutes = $time[1] * 60;
            $PROCESSED["exam_submission_date"] += $hours + $minutes;
        }

        if (isset($PROCESSED["release_start_date"]) && $PROCESSED["release_start_date"] && isset($PROCESSED["release_start_time"]) && $PROCESSED["release_start_time"]) {
            $time = explode(":", $PROCESSED["release_start_time"]);
            $hours = $time[0] * 60 * 60;
            $minutes = $time[1] * 60;
            $PROCESSED["release_start_date"] += $hours + $minutes;
        }

        if (isset($PROCESSED["release_end_date"]) && $PROCESSED["release_end_date"] && isset($PROCESSED["release_end_time"]) && $PROCESSED["release_end_time"]) {
            $time = explode(":", $PROCESSED["release_end_time"]);
            $hours = $time[0] * 60 * 60;
            $minutes = $time[1] * 60;
            $PROCESSED["release_end_date"] += $hours + $minutes;
        }

        if (isset($PROCESSED["exam_exceptions"]) && is_string($PROCESSED["exam_exceptions"])) {
            $PROCESSED["exam_exceptions"] = json_decode($PROCESSED["exam_exceptions"], true);
        }

        if (isset($PROCESSED["grade_book"])) {
            $PROCESSED["grade_book"] = (int) $PROCESSED["grade_book"];
        }

        if (isset($PROCESSED["timeframe"]) &&  in_array($PROCESSED["timeframe"] , array("none","pre","during","post"))) {
            $PROCESSED["timeframe"] = $PROCESSED["timeframe"];
        } else {
            $PROCESSED["timeframe"] = "none";
        }

        if (isset($PROCESSED["release_incorrect_responses"])) {
            $PROCESSED["release_incorrect_responses"] = (int) $PROCESSED["release_incorrect_responses"];
        } else {
            $PROCESSED["release_incorrect_responses"] = 0;
        }

        if (isset($PROCESSED["allow_calculator"])) {
            $PROCESSED["use_calculator"] = (int) $PROCESSED["allow_calculator"];
        } else {
            $PROCESSED["use_calculator"] = 0;
        }

        if ($PROCESSED["post_id"]) {
            $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
            if ($post && is_object($post)) {
                $PROCESSED["created_date"] = $post->getCreatedDate();
                $PROCESSED["created_by"] = $post->getCreatedBy();
            }
        }

        $this->post = new Models_Exam_Post($PROCESSED);
        if ($this->post->{$method}()) {
            /*
             * Set Exam Post Exceptions
             */
            $old_exceptions_proxy_ids = array();
            $new_exceptions_proxy_ids = array();
            if ($method === "update") {
                $old_exceptions = Models_Exam_Post_Exception::fetchAllByPostID($this->post->getID());
                if (isset($old_exceptions)) {
                    $old_exceptions_proxy_ids = ($old_exceptions_proxy_ids_search = Models_Exam_Post_Exception::getProxyIds($old_exceptions)) ? $old_exceptions_proxy_ids_search : array();
                }
            }

            if (isset($PROCESSED["exam_exceptions"]) && is_array($PROCESSED["exam_exceptions"]) && !empty($PROCESSED["exam_exceptions"])) {
                foreach ($PROCESSED["exam_exceptions"] as $proxy_id => $exception_data) {
                    $delete_rp_now_user = false;
                    if (!in_array($proxy_id, $new_exceptions_proxy_ids)) {
                        $new_exceptions_proxy_ids[] = $proxy_id;
                    }
                    $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->post->getID(), $proxy_id);
                    if (isset($exception) && is_object($exception)) {
                        $exception_data["ep_exception_id"] = $exception->getID();
                        $exception_data["post_id"] = $exception->getPostID();
                        $exception_data["proxy_id"] = $exception->getProxyID();
                        $exception_data["updated_date"] = time();
                        $exception_data["updated_by"] = $ENTRADA_USER->getID();

                        if (isset($exception_data["selected"]) && $exception_data["selected"] == 1) {
                            //replaces the data with the updated information
                            $exception = new Models_Exam_Post_Exception($exception_data);
                            if ($method === "update" && isset($exception_data["excluded"]) && $exception_data["excluded"] == 1 && $PROCESSED["secure_mode"] == "rp_now") {
                                $delete_rp_now_user = true;
                            }
                        } else {
                            //set deleted
                            $exception->setDeletedDate(time());

                            //Verify if is Rpnow and if the user is already in the RpNowUsers table if its not there add to the table
                            if ($method === "update" &&  $PROCESSED["secure_mode"] == "rp_now") {
                                $rp_now = Models_Secure_RpNow::fetchRowByPostID($this->post->getID());
                                if ($rp_now) {
                                    $rp_now_user = Models_Secure_RpNowUsers::fetchRowByRpnowConfigIdProxyId($rp_now->getID(), $proxy_id);
                                    if (!$rp_now_user) {
                                        $rp_now_user = new Models_Secure_RpNowUsers();
                                        $exam_code = $rp_now_user->generateCode(5) . "-" . $this->post->getID() . "-" . $proxy_id;

                                        $rp_now_user_arr["proxy_id"] = $proxy_id;
                                        $rp_now_user_arr["exam_code"] = $exam_code;
                                        $rp_now_user_arr["ssi_record_locator"] = null;
                                        $rp_now_user_arr["rpnow_config_id"] = $rp_now->getID();
                                        $rp_now_user_arr["created_date"] = time();
                                        $rp_now_user_arr["created_by"] = $ENTRADA_USER->getActiveID();
                                        $rp_now_user_arr["updated_date"] = time();
                                        $rp_now_user_arr["updated_by"] = $ENTRADA_USER->getActiveID();

                                        if (!$db->AutoExecute("rp_now_users", $rp_now_user_arr, "INSERT")) {
                                            application_log("error", "An error occurred while attempting to save rp-now con:" . $db->ErrorMsg());
                                        }
                                    }
                                }
                            }
                        }
                        if (!$exception->update()) {
                            application_log("error", "An error occurred while attempting to update this exam exception. DB: " . $db->ErrorMsg());
                        }
                    } else {
                        //create
                        if (isset($exception_data["selected"]) && $exception_data["selected"] == 1) {
                            //update exception
                            $exception_data["proxy_id"]     = $proxy_id;
                            $exception_data["post_id"]      = $this->post->getID();
                            $exception_data["created_date"] = time();
                            $exception_data["created_by"]   = $ENTRADA_USER->getID();
                            $exception_data["updated_date"] = time();
                            $exception_data["updated_by"]   = $ENTRADA_USER->getID();

                            $exception = new Models_Exam_Post_Exception($exception_data);
                            if (!$exception->insert()) {
                                application_log("error", "An error occurred while attempting to insert this exam exception. DB: " . $db->ErrorMsg());
                            }
                        }
                        if ($method === "update" && isset($exception_data["excluded"]) && $exception_data["excluded"] == 1 && $PROCESSED["secure_mode"] == "rp_now") {
                            $delete_rp_now_user = true;
                        }
                    }
                    if (isset($delete_rp_now_user) && $delete_rp_now_user) {
                        $rp_now = Models_Secure_RpNow::fetchRowByPostID($this->post->getID());
                        if ($rp_now) {
                            $rp_now_user = Models_Secure_RpNowUsers::fetchRowByRpnowConfigIdProxyId($rp_now->getID(), $proxy_id);
                            if ($rp_now_user && !$rp_now_user->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID(), "deleted_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                application_log("error", "An error occurred while attempting to delete RpNowUser id [" . $rp_now_user->getID() . "]");
                            }
                        }
                    }
                }
            }

            if ($method === "update") {
                $remove_proxy_ids = array_diff($old_exceptions_proxy_ids, $new_exceptions_proxy_ids);

                if (isset($remove_proxy_ids) && is_array($remove_proxy_ids) && !empty($remove_proxy_ids)) {
                    foreach ($remove_proxy_ids as $proxy_id) {
                        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->post->getID(), $proxy_id);
                        $exception->setDeletedDate(time());
                        if (!$exception->update()) {
                            application_log("error", "An error occurred while attempting to save this exam. DB: " . $db->ErrorMsg());
                        }
                    }
                }

                /*
                 * End Exam Post Exceptions
                 */

                if ($this->post->getTargetType() == "event") {
                    $entity = Models_Event_Resource_Entity::fetchRowByEntityTypeEntityValue("12", $this->post->getID());
                    if ($entity && is_object($entity)) {
                        // update entity as well.
                        $entity->setEventId($PROCESSED["target_id"]);
                        $entity->setReleaseDate($this->post->getReleaseStartDate());
                        $entity->setUpdatedDate(time());
                        $entity->setUpdatedBy($ENTRADA_USER->getID());

                        if (!$entity->update()) {
                            application_log("error", "An error occurred while attempting to save this event entity for an exam. DB: " . $db->ErrorMsg());
                        }
                    }
                }
            }

            if ($method === "insert") {
                if ($this->post->getTargetType() == "event") {
                    $entity = new Models_Event_Resource_Entity(array(
                        "event_id" => $PROCESSED["target_id"],
                        "entity_type" => 12,
                        "entity_value" => $this->post->getID(),
                        "release_date" => $this->post->getReleaseStartDate(),
                        "release_until" => 0,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getID(),
                        "active" => 1
                    ));

                    if (!$entity->insert()) {
                        add_error("Failed to insert Event Resource Entity for Exam Post");
                    }
                }
            }

            return true;
        } else {
            application_log("error", "An error occurred while attempting to save this exam. DB: " . $db->ErrorMsg());
            return false;
        }
    }
}
