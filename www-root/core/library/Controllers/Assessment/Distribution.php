<?php
class Controllers_Assessment_Distribution extends Controllers_Base {
    protected $default_error_msg = "Please select a <strong>%s</strong> to continue to the next step.";

    protected $validation_rules = array(
        "adistribution_id"          => array(
            "label"                 => "Distribution Identifier",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 1
        ),
        "title"                     => array(
            "label"                 => "Distribution Title",
            "required"              => true,
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 1
        ),
        "description"               => array(
            "label"                 => "Distribution Description",
            "required"              => false,
            "default"               => "",
            "sanitization_params"   => array("trim", "striptags"),
            "step"                  => 1
        ),
        "distribution_form"         => array(
            "label"                 => "Form",
            "db_fieldname"          => "form_id",
            "required"              => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 1
        ),
        "mandatory"                 => array(
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "step"                  => 1
        ),
        "distribution_start_date"   => array(
            "label"                 => "Start Date",
            "db_fieldname"          => "start_date",
            "required"              => true,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "step"                  => 2
        ),
        "distribution_start_time"   => array(
            "label"                 => "Start Time",
            "required"              => false,
            "sanitization_params"   => array("trim", "strtotimeonly"),
            "step"                  => 2
        ),
        "distribution_end_date"     => array(
            "label"                 => "End Date",
            "db_fieldname"          => "end_date",
            "required"              => true,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "step"                  => 2
        ),
        "distribution_end_time"     => array(
            "label"                 => "End Time",
            "required"              => false,
            "sanitization_params"   => array("trim", "strtotimeonly"),
            "step"                  => 2
        ),
        "distribution_delivery_date"   => array(
            "label"                 => "Delivery Date",
            "db_fieldname"          => "delivery_date",
            "required"              => true,
            "default"               => null,
            "sanitization_params"   => array("trim", "strtotime"),
            "step"                  => 2
        ),
        "course_id"                     => array(
            "label"                     => "Course",
            "required"                  => true,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 1,
            "cascade_when_unrequired" => false //Default is true, not required
        ),
        "cperiod_id"                    => array(
            "label"                     => "Curriculum Period",
            "required"                  => true,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 1,
            "cascade_when_unrequired"   => false //Default is true, not required
        ),
        "distribution_method"       => array(
            "label"                 => "Distribution Method",
            "required"              => true,
            "sanitization_params"   => array("trim"),
            "allowed_values"        => array("date_range", "rotation_schedule", "delegation", "eventtype"),
            "step"                  => 2,
            "requirement_matrix"    => array(
                "date_range" => array("distribution_start_date", "distribution_end_date", "distribution_delivery_date"),
                "rotation_schedule" => array("course_id", "schedule_id"),
                "delegation" => array("delegator_id", "distribution_delegator_timeframe")
            )
        ),
        "eventtypes"                   => array(
            "label"                     => "Event Type Option",
            "required"                  => false,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 2,
            "array"                     => true,
            "required_when"                 => array(
                "values" => array(
                    "distribution_method" => "eventtype"
                ),
                "type" => "variable"
            ),
        ),
        "eventtype_release_option"      => array(
            "required"                  => false,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "striptags"),
            "allowed_values"            => array("1"),
            "step"                      => 2,
            "requirement_matrix"        => array(
                "1" => array("eventtype_release_date"),
            )
        ),
        "eventtype_release_date"        => array(
            "label"                     => "Release Date",
            "required"                  => false,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "strtotime"),
            "step"                      => 2,
        ),
        "schedule_id"                   => array(
            "label"                     => "Rotation Schedule",
            "required"                  => false,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 2,
            "requirement_matrix"    => array(
                "%*%" => array("schedule_delivery_type")
            ),
            "cascade_when_unrequired" => false
        ),
        "rotation_release_option"       => array(
            "required"                  => false,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "striptags"),
            "allowed_values"            => array("1"),
            "step"                      => 2,
            "requirement_matrix"        => array(
                "1" => array("rotation_release_date"),
            )
        ),
        "rotation_release_date"         => array(
            "label"                     => "Release Date",
            "required"                  => false,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "strtotime"),
            "step"                      => 2,
        ),
        "schedule_delivery_type"    => array(
            "label"                 => "Delivery Period",
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "allowed_values"        => array("repeat", "block", "rotation"),
            "step"                  => 2,
            "requirement_matrix"    => array(
                "repeat" => array("frequency"),
                "block" => array("delivery_period"),
                "rotation" => array("delivery_period")
            )
        ),
        "period_offset_days"  => array(
            "label"                 => "Days",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 2,
        ),
        "frequency"  => array(
            "label"                 => "Repeat Frequency",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 2,
            "cascade_when_unrequired" => false
        ),
        "delivery_period"             => array(
            "required"              => false,
            "sanitization_params"   => array("trim"),
            "step"                  => 2,
            "allowed_values"        => array("after-start", "before-middle", "after-middle", "before-end", "after-end")
        ),
        "delegator_id"              => array(
            "label"                 => "Delegator",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 2
        ),
        "distribution_delegator_timeframe"  => array(
            "label"                         => "Delegation Option",
            "db_fieldname"                  => "distribution_delegator_timeframe",
            "required"                      => false,
            "sanitization_params"           => array("trim"),
            "allowed_values"                => array("date_range", "rotation_schedule"),
            "requirement_matrix"    => array(
                "date_range" => array("distribution_start_date", "distribution_end_date", "distribution_delivery_date"),
                "rotation_schedule" => array("course_id", "schedule_id", "delivery_period")
            ),
            "step"                          => 2
        ),
        "distribution_assessor_option"  => array(
            "label"                     => "Assessor Option",
            "db_fieldname"                  => "assessor_option",
            "required_when"             => array(
                "type" => "variable",
                "number_required" => 1,
                "values" => array(
                    "distribution_method" => "date_range",
                    "distribution_delegator_timeframe" => "date_range"
                )
            ),
            "required"                  => false,
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("faculty", "grouped_users", "individual_users"),
            "requirement_matrix"    => array(
                "faculty" => array("assessor_faculty"),
                "grouped_users" => array(
                    array(
                        "number_required" => 1,
                        "fields" => array(
                            "assessor_cohort_id",
                            "assessor_course_id",
                            "assessor_organisation_id"
                        )
                    )
                ),
                "individual_users" => array("selected_internal_assessors")
            ),
            "step"                      => 4
        ),
        "distribution_eventtype_assessor_option" => array(
            "label"                     => "Assessor Option",
            "db_fieldname"              => "assessor_option",
            "required"                  => false,
            "required_when"             => array(
                "values" => array(
                    "distribution_method" => "eventtype",
                ),
                "type" => "variable"
            ),
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("learner", "faculty", "individual_users"),
            "requirement_matrix"    => array(
                "faculty" => array("additional_assessor_eventtype_faculty"),
                "individual_users" => array("selected_internal_assessors")
            ),
            "step"                      => 4
        ),
        "distribution_eventtype_learners" => array(
            "label"                     => "Learner Option",
            "required"                  => false,
            "required_when"             => array(
                "values" => array(
                    "distribution_eventtype_assessor_option" => "learner",
                ),
                "type" => "variable"
            ),
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("attended", "all-learners"),
            "step"                      => 4
        ),
        /*
        "additional_assessor_eventtype_faculty" => array(
            "label"                     => "Faculty Option",
            "required"                  => false,
            "required_when"             => array(
                "values" => array(
                    "distribution_eventtype_assessor_option" => "faculty",
                ),
                "type" => "variable"
            ),
            "array"                     => true,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 4
        ),
        */
        "selected_internal_assessors"   => array(
            "label"                     => "Assessor",
            "db_fieldname"              => "assessors",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim"),
            "step"                      => 4
        ),
        "assessor_cohort_id"        => array(
            "label"                 => "Cohort",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 4
        ),
        "assessor_course_id"        => array(
            "label"                 => "Course Audience",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 4
        ),
        "assessor_organisation_id"  => array(
            "label"                 => "Organisation",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 4
        ),
        "assessor_faculty"          => array(
            "label"                 => "Faculty Member",
            "required"              => false,
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 4
        ),
        "distribution_rs_assessor_option"   => array(
            "label"                         => "Assessor Option",
            "db_fieldname"                  => "assessor_option",
            "required"                      => false,
            "required_when"                 => array(
                "type" => "variable",
                "number_required" => 1,
                "values" => array(
                    "distribution_method" => "rotation_schedule",
                    "distribution_delegator_timeframe" => "rotation_schedule"
                )
            ),
            "sanitization_params"           => array("trim"),
            "allowed_values"                => array("learner", "faculty", "individual_users"),
            "requirement_matrix"    => array(
                "learner" => "distribution_rs_assessor_learner_option",
                "faculty" => "additional_assessor_faculty",
                "individual_users" => array("selected_internal_assessors")
            ),
            "step"                          => 4
        ),
        "distribution_rs_assessor_learner_option"   => array(
            "label"                                 => "Learner Option",
            "required"                              => false,
            "sanitization_params"                   => array("trim"),
            "allowed_values"                        => array("all", "individual"),
            "requirement_matrix"                    => array(
                "all" => "distribution_rs_learner_service",
                "individual" => "individual_assessor_learners",
            ),
            "step"                                  => 4
        ),
        "distribution_rs_learner_service"    => array(
            "required"                              => false,
            "array"                                 => true,
            "sanitization_params"                   => array("trim"),
            "allowed_values"                        => array("onservice", "offservice"),
            "step"                                  => 4,
            "error_msgs"                             => "Please select whether <strong>On Service</strong> and/or <strong>Off Service</strong> learners will be the assessors to continue."
        ),
        "individual_assessor_learners"  => array(
            "label"                     => "Learner",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 4
        ),
        "distribution_assessor_additional_learners" => array(
            "required"                              => false,
            "sanitization_params"                   => array("trim"),
            "requirement_matrix"    => array(
                "yes" => "additional_assessor_learners"
            ),
            "step"                                  => 4
        ),
        "distribution_rs_additional_learners"   => array(
            "required"                              => false,
            "array"                                 => true,
            "sanitization_params"                   => array("trim"),
            "requirement_matrix"                    => array(
                "yes" => "additional_assessor_learners"
            ),
            "step"                                  => 4
        ),
        "additional_assessor_learners"  => array(
            "label"                     => "Additional Learner",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 4
        ),
        "additional_assessor_faculty"   => array(
            "label"                     => "Additional Faculty Member",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 4
        ),
        "repeat_targets"            => array(
            "required"              => false,
            "default"               => false,
            "sanitization_params"   => array("trim", "bool"),
            "step"                  => 3
        ),
        "distribution_target_option"    => array(
            "label"                     => "Target Option",
            "required"                  => false,
            "required_when"             => array(
                "type" => "variable",
                "number_required" => 1,
                "values" => array(
                    "distribution_method" => "date_range",
                    "distribution_delegator_timeframe" => "date_range"
                )
            ),
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("self", "faculty", "grouped_users", "course", "individual_users"),
            "requirement_matrix"        => array(
                "faculty" => "target_faculty",
                "grouped_users" => array(
                    array(
                        "number_required" => 1,
                        "fields" => array(
                            "target_cohort_id",
                            "target_course_audience_id",
                            "target_organisation_id"
                        )
                    )
                ),
                "course" => "target_course_id",
                "individual_users" => "selected_internal_targets"
            ),
            "step"                      => 3
        ),
        "selected_internal_targets"   => array(
            "label"                     => "Target",
            "db_fieldname"              => "targets",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim"),
            "step"                      => 3
        ),
        "target_cohort_id"          => array(
            "label"                 => "Cohort",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "target_course_audience_id"          => array(
            "label"                 => "Course Audience",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "target_organisation_id"     => array(
            "label"                 => "Organisation",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "target_faculty"            => array(
            "label"                 => "Faculty Member",
            "required"              => false,
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "target_course_id"          => array(
            "label"                 => "Target Course",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "feedback_required"         => array(
            "label"                     => "Feedback is required for this assessment",
            "required"                  => false,
            "default"                   => 0,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 3
        ),
        "distribution_rs_target_option" => array(
            "label"                     => "Target Option",
            "required"                  => false,
            "required_when"             => array(
                "type" => "variable",
                "number_required" => 1,
                "values" => array(
                    "distribution_method" => "rotation_schedule",
                    "distribution_delegator_timeframe" => "rotation_schedule"
                )
            ),
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("self", "learner", "faculty", "block"),
            "requirement_matrix"    => array(
                "learner" => "distribution_rs_target_learner_option",
                "faculty" => "additional_target_faculty"
            ),
            "step"                      => 3
        ),
        "distribution_eventtype_target_option" => array(
            "label"                     => "Target Option",
            "required"                  => false,
            "required_when"             => array(
                "values" => array(
                    "distribution_method" => "eventtype",
                ),
                "type" => "variable"
            ),
            "sanitization_params"       => array("trim"),
            "allowed_values"            => array("learner", "faculty", "event"),
            "step"                      => 3
        ),
        "distribution_rs_target_learner_option"     => array(
            "label"                                 => "Learner Option",
            "required"                              => false,
            "sanitization_params"                   => array("trim"),
            "allowed_values"                        => array("all", "individual"),
            "requirement_matrix"    => array(
                "all" => "distribution_rs_target_learner_service",
                "individual" => "individual_target_learner"
            ),
            "step"                                  => 3
        ),
        "individual_target_learner" => array(
            "required"              => false,
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "distribution_rs_target_learner_service"    => array(
            "required"                              => false,
            "array"                                 => true,
            "sanitization_params"                   => array("trim"),
            "allowed_values"                        => array("onservice", "offservice"),
            "step"                                  => 3,
            "error_msgs"                             => "Please select whether <strong>On Service</strong> and/or <strong>Off Service</strong> learners will be the targets to continue."
        ),
        "distribution_rs_target_additional_learners"   => array(
            "required"                              => false,
            "sanitization_params"                   => array("trim"),
            "requirement_matrix"                    => array(
                "yes" => "additional_target_learners"
            ),
            "step"                                  => 3
        ),
        "additional_target_learners"    => array(
            "label"                     => "Additional Learner",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim", "int"),
            "step"                      => 3
        ),
        "additional_target_faculty" => array(
            "label"                 => "Additional Faculty Member",
            "required"              => false,
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "attempts_scope" => array(
            "label"                 => "Target Attempt Option",
            "required"              => true,
            "array"                 => true,
            "sanitization_params"   => array("trim"),
            "allowed_values"        => array("targets", "overall"),
            "requirement_matrix"                    => array(
                "targets" => array("min_target_attempts", "max_target_attempts"),
                "overall" => array("min_overall_attempts", "max_overall_attempts")
            ),
            "step"                  => 3
        ),
        "min_target_attempts" => array(
            "label"                 => "Minimum Number of Attempts",
            "db_fieldname"          => "min_submittable",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "max_target_attempts" => array(
            "label"                 => "Maximum Number of Attempts",
            "db_fieldname"          => "max_submittable",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "min_overall_attempts" => array(
            "label"                 => "Minimum Number of Attempts",
            "db_fieldname"          => "min_submittable",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "max_overall_attempts" => array(
            "label"                 => "Maximum Number of Attempts",
            "db_fieldname"          => "max_submittable",
            "required"              => false,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 3
        ),
        "distribution_results_start_date"   => array(
            "label"                 => "Start Date",
            "db_fieldname"          => "release_start_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "step"                  => 5
        ),
        "distribution_results_start_time"   => array(
            "label"                 => "Start Time",
            "required"              => false,
            "sanitization_params"   => array("trim", "strtotimeonly"),
            "step"                  => 5
        ),
        "distribution_results_end_date"     => array(
            "label"                 => "End Date",
            "db_fieldname"          => "release_end_date",
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "strtotime"),
            "step"                  => 5
        ),
        "distribution_results_end_time"     => array(
            "label"                 => "End Time",
            "required"              => false,
            "sanitization_params"   => array("trim", "strtotimeonly"),
            "step"                  => 5
        ),
        "selected_authors"   => array(
            "label"                     => "Authors",
            "db_fieldname"              => "authors",
            "required"                  => false,
            "array"                     => true,
            "sanitization_params"       => array("trim"),
            "step"                      => 5
        ),
        "flagging_notifications"    => array(
            "required"              => false,
            "default"               => "disabled",
            "sanitization_params"   => array("trim", "alpha"),
            "allowed_values"        => array("disabled", "reviewers", "authors", "pcoordinators", "directors"),
            "step"                  => 5
        ),
        "distribution_results_user" => array(
            "label"                 => "Distribution Reviewer",
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "flagging_notifications" => "reviewers"
                ),
                "type" => "variable"
            ),
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "error_msgs"            => "Please select custom reviewers to receive Flagged Response Notifications.",
            "step"                  => 5
        ),
        "distribution_results_response_quantity"    => array(
            "required"                              => false,
            "sanitization_params"                   => array("trim", "int"),
            "step"                                  => 5
        ),
        "approver_required"         => array(
            "required"              => false,
            "default"               => 0,
            "sanitization_params"   => array("trim", "bool"),
            "step"                  => 5
        ),
        "distribution_approvers" => array(
            "label"                 => "Reviewer",
            "array"                 => true,
            "sanitization_params"   => array("trim", "int"),
            "step"                  => 5,
            "required"              => false,
            "required_when"         => array(
                "values" => array(
                    "approver_required" => "1"
                ),
                "type" => "variable"
            )
        )
    );

    public function save() {
        global $ENTRADA_USER, $translate, $db, $ERROR;

        $method = "insert";

        if (isset($this->validated_data["adistribution_id"]) && $tmp_input = clean_input($this->validated_data["adistribution_id"], array("trim", "int"))) {
            $method = "update";
        }

        $PROCESSED = $this->getValidatedData(true);

        $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
        $PROCESSED["notifications"] = 1;
        $PROCESSED["updated_date"] = time();
        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
        $PROCESSED["created_date"] = time();
        $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
        $PROCESSED["submittable_by_target"] = (isset($PROCESSED["attempts_scope"][0]) && $PROCESSED["attempts_scope"][0] == "targets" ? 1 : 0);
        $PROCESSED["release_date"] = NULL;
        $PROCESSED["visibility_status"] = "visible";
        $PROCESSED["assessment_type"] = "assessment";


        /**
         * "assessor_option" can pass validation with the value of "grouped_user", but "grouped_user" isn't a valid field value (the schema's enum
         *  can only be "faculty", "individual_users" or "learner"), so we adjust it here. The "grouped_user"s are always "learner"s.
         */
        if (isset($PROCESSED["assessor_option"]) && $PROCESSED["assessor_option"] == "grouped_users") {
            $PROCESSED["assessor_option"] = "learner";
        }
        // Here we determine weather or not the distribution we are saving is an assessment or an evaluation
        if(isset($PROCESSED["assessor_option"])) {
            if(isset($PROCESSED["distribution_target_option"])) {
                if ($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_target_option"] !== "learner") {
                    if ($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_target_option"] === "individual_users") {
                        //figure out if the target is faculty or learner.  Any means that it could be either one.
                        foreach ($PROCESSED["targets"] as $key => $target_id) {
                            $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                            if ($user_access->getGroup() == "student") {
                                $PROCESSED["assessment_type"] = "assessment";
                            }
                            if ($user_access->getGroup() == "faculty") {
                                $PROCESSED["assessment_type"] = "evaluation";
                            }
                        }
                    } else {
                        $PROCESSED["assessment_type"] = "evaluation";
                    }
                }
                if ($PROCESSED["assessor_option"] === "individual_users" && $PROCESSED["distribution_target_option"] !== "learner") {
                    foreach ($PROCESSED["assessors"] as $key => $assessor_id) {
                        $pieces = explode("_", $assessor_id);
                        //individual users could be either faculty or learners. So we need to figure out which one they are.
                        $user_access = Models_User_Access::fetchRowByUserIDAppID($pieces[1]);
                        if($user_access->getGroup() == "student") {
                            // need to see if the target is a learner that was selected individually
                            if($PROCESSED["distribution_target_option"] === "individual_users") {
                                foreach ($PROCESSED["targets"] as $key => $target_id) {
                                    $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                                    if ($user_access->getGroup() == "student") {
                                        $PROCESSED["assessment_type"] = "assessment";
                                    }
                                    if ($user_access->getGroup() == "faculty") {
                                        $PROCESSED["assessment_type"] = "evaluation";
                                    }
                                }
                            } else {
                                $PROCESSED["assessment_type"] = "evaluation";
                            }
                        } else {
                            $PROCESSED["assessment_type"] = "evaluation";
                        }
                    }
                }
            }
            if(isset($PROCESSED["distribution_rs_target_option"])) {
                if ($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_rs_target_option"] !== "learner") {
                    $PROCESSED["assessment_type"] = "evaluation";
                }
                if($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_rs_target_option"] === "any") {
                    foreach ($PROCESSED["targets"] as $key => $target_id) {
                        $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                        if($user_access->getGroup() == "faculty") {
                            $PROCESSED["assessment_type"] = "evaluation";
                        }
                        if($user_access->getGroup() == "student") {
                            $PROCESSED["assessment_type"] = "assessment";
                        }
                    }
                }
                if ($PROCESSED["assessor_option"] === "individual_users" && $PROCESSED["distribution_rs_target_option"] !== "learner") {
                    foreach ($PROCESSED["assessors"] as $key => $assessor_id) {
                        $pieces = explode("_", $assessor_id);
                        $user_access = Models_User_Access::fetchRowByUserIDAppID($pieces[1]);
                        if($user_access->getGroup() == "student") {
                            if($PROCESSED["distribution_target_option"] === "individual_users") {
                                foreach ($PROCESSED["targets"] as $key => $target_id) {
                                    $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                                    if ($user_access->getGroup() == "student") {
                                        $PROCESSED["assessment_type"] = "assessment";
                                    }
                                    if ($user_access->getGroup() == "faculty") {
                                        $PROCESSED["assessment_type"] = "evaluation";
                                    }
                                }
                            } else {
                                $PROCESSED["assessment_type"] = "evaluation";
                            }
                        }
                    }
                }
            }
            if(isset($PROCESSED["distribution_eventtype_target_option"])) {
                if ($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_eventtype_target_option"] !== "faculty") {
                    $PROCESSED["assessment_type"] = "evaluation";
                }
                if($PROCESSED["assessor_option"] === "learner" && $PROCESSED["distribution_eventtype_target_option"] === "any") {
                    foreach ($PROCESSED["targets"] as $key => $target_id) {
                        $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                        if($user_access->getGroup() == "student") {
                            $PROCESSED["assessment_type"] = "assessment";
                        }
                    }
                }
                if ($PROCESSED["assessor_option"] === "individual_users" && $PROCESSED["distribution_eventtype_target_option"] !== "learner") {
                    foreach ($PROCESSED["assessors"] as $key => $assessor_id) {
                        $pieces = explode("_", $assessor_id);
                        $user_access = Models_User_Access::fetchRowByUserIDAppID($pieces[1]);
                        if($user_access->getGroup() == "student") {
                            if($PROCESSED["distribution_target_option"] === "individual_users") {
                                foreach ($PROCESSED["targets"] as $key => $target_id) {
                                    $user_access = Models_User_Access::fetchRowByUserIDAppID($target_id);
                                    if ($user_access->getGroup() == "student") {
                                        $PROCESSED["assessment_type"] = "assessment";
                                    }
                                    if ($user_access->getGroup() == "faculty") {
                                        $PROCESSED["assessment_type"] = "evaluation";
                                    }
                                }
                            } else {
                                $PROCESSED["assessment_type"] = "evaluation";
                            }
                        }
                    }
                }
            }
        }

        $validated_as_date_range = false;

        if ($PROCESSED["distribution_method"]) {
            switch ($PROCESSED["distribution_method"]) {
                case "date_range" :
                    $validated_as_date_range = true;
                    if (isset($PROCESSED["assessor_option"]) && isset($PROCESSED["distribution_target_option"])) {
                        if (($PROCESSED["assessor_option"] === "faculty" || $PROCESSED["assessor_option"] === "individual_users") && ($PROCESSED["distribution_target_option"] === "grouped_users" || $PROCESSED["distribution_target_option"] === "individual_users")) {
                            if (isset($PROCESSED["feedback_required"]) && $PROCESSED["feedback_required"] === (int) 1) {
                                $PROCESSED["feedback_required"] = 1;
                            } else {
                                $PROCESSED["feedback_required"] = 0;
                            }
                        } else {
                            $PROCESSED["feedback_required"] = 0;
                        }
                    }
                    break;
                case "rotation_schedule" :
                    if (isset($PROCESSED["assessor_option"]) && isset($PROCESSED["distribution_rs_target_option"])) {
                        if ($PROCESSED["assessor_option"] === "faculty" || ($PROCESSED["assessor_option"] === "individual_users" && $PROCESSED["distribution_rs_target_option"] === "learner")) {
                            if (isset($PROCESSED["feedback_required"]) && $PROCESSED["feedback_required"] === (int) 1) {
                                $PROCESSED["feedback_required"] = 1;
                            } else {
                                $PROCESSED["feedback_required"] = 0;
                            }
                        } else {
                            $PROCESSED["feedback_required"] = 0;
                        }
                    }
                    $PROCESSED["delivery_date"] = null;
                    break;
                case "delegation" :
                    if (isset($PROCESSED["distribution_delegator_timeframe"])) {
                        switch ($PROCESSED["distribution_delegator_timeframe"]) {
                            case "date_range" :
                                $validated_as_date_range = true;
                                if (isset($PROCESSED["assessor_option"]) && isset($PROCESSED["distribution_target_option"])) {
                                    if (($PROCESSED["assessor_option"] === "faculty" || $PROCESSED["assessor_option"] === "individual_users") && ($PROCESSED["distribution_target_option"] === "grouped_users" || $PROCESSED["distribution_target_option"] === "individual_users")) {
                                        if (isset($PROCESSED["feedback_required"]) && $PROCESSED["feedback_required"] === (int) 1) {
                                            $PROCESSED["feedback_required"] = 1;
                                        } else {
                                            $PROCESSED["feedback_required"] = 0;
                                        }
                                    } else {
                                        $PROCESSED["feedback_required"] = 0;
                                    }
                                }
                                break;
                            case "rotation_schedule" :
                                $PROCESSED["delivery_date"] = null;
                                if (isset($PROCESSED["distribution_rs_assessor_option"]) && isset($PROCESSED["distribution_rs_target_option"])) {
                                    if ($PROCESSED["distribution_rs_assessor_option"] === "faculty" || ($PROCESSED["distribution_rs_assessor_option"] === "individual_users" && $PROCESSED["distribution_rs_target_option"] === "learner")) {
                                        if (isset($PROCESSED["feedback_required"]) && $PROCESSED["feedback_required"] === (int) 1) {
                                            $PROCESSED["feedback_required"] = 1;
                                        } else {
                                            $PROCESSED["feedback_required"] = 0;
                                        }
                                    } else {
                                        $PROCESSED["feedback_required"] = 0;
                                    }
                                }
                                break;
                        }
                    }
                    break;
                case "eventtype" :
                    $PROCESSED["delivery_date"] = null;
                    break;
            }
        }
        if (!isset($PROCESSED["schedule_id"]) || !$PROCESSED["schedule_id"]) {
            if (isset($PROCESSED["start_date"]) && $PROCESSED["start_date"] && isset($PROCESSED["distribution_start_time"]) && $PROCESSED["distribution_start_time"]) {
                $PROCESSED["start_date"] = $PROCESSED["start_date"] + $PROCESSED["distribution_start_time"];
            }
            if (isset($PROCESSED["end_date"]) && $PROCESSED["end_date"] && isset($PROCESSED["distribution_end_time"]) && $PROCESSED["distribution_end_time"]) {
                $PROCESSED["end_date"] = $PROCESSED["end_date"] + $PROCESSED["distribution_end_time"];
            }

            if (isset($PROCESSED["eventtype_release_option"]) && $PROCESSED["eventtype_release_option"]) {
                if (isset($PROCESSED["eventtype_release_date"])) {
                    $PROCESSED["release_date"] = $PROCESSED["eventtype_release_date"];
                }
            }
        } else {
            $PROCESSED["start_date"] = 0;
            $PROCESSED["end_date"] = 0;
            if (isset($PROCESSED["rotation_release_option"]) && $PROCESSED["rotation_release_option"]) {
                if (isset($PROCESSED["rotation_release_date"])) {
                    $PROCESSED["release_date"] = $PROCESSED["rotation_release_date"];
                }
            }
        }
        if (isset($PROCESSED["release_start_date"]) && $PROCESSED["release_start_date"] && isset($PROCESSED["distribution_results_start_time"]) && $PROCESSED["distribution_results_start_time"]) {
            $PROCESSED["release_start_date"] += $PROCESSED["distribution_results_start_time"];
        }
        if (isset($PROCESSED["release_end_date"]) && $PROCESSED["release_end_date"] && isset($PROCESSED["distribution_results_end_time"]) && $PROCESSED["distribution_results_end_time"]) {
            $PROCESSED["release_end_date"] += $PROCESSED["distribution_results_end_time"];
        }

        $distribution = new Models_Assessments_Distribution($PROCESSED);
        if ($distribution->{$method}()) {
            if ($method == "update") {
                $authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($distribution->getID());
                if ($authors) {
                    foreach ($authors as $author) {
                        $author->delete();
                    }
                }
                $distribution_approvers = new Models_Assessments_Distribution_Approver();
                $approvers = $distribution_approvers->fetchAllByDistributionID($distribution->getID());
                if ($approvers) {
                    foreach ($approvers as $approver) {
                        $approver->delete();
                    }
                }
            }

            if (isset($PROCESSED["authors"]) && $PROCESSED["authors"]) {

                foreach ($PROCESSED["authors"] as $a) {
                    /*
                    Authors are either individual students, courses or organisations. The data will follow this format:
                        array(3) {
                            [0]=>
                            string(23) "author_individual_73301"
                            [1]=>
                            string(21) "author_organisation_8"
                            [2]=>
                            string(17) "author_course_450"
                        }
                    So the value of pieces pieces[1] will be the author type, followed by the author id at pieces[2]
                    */
                    $pieces = explode("_", $a);
                    $author_type = false;

                    if ($pieces[1] === "individual") {
                        $author_type = "proxy_id";
                    } elseif ($pieces[1] === "course") {
                        $author_type = "course_id";
                    } elseif ($pieces[1] === "organisation") {
                        $author_type = "organisation_id";
                    }

                    if ($author_type) {
                        $author = new Models_Assessments_Distribution_Author(
                            array(
                                "adistribution_id"  => $distribution->getID(),
                                "author_type"       => $author_type,
                                "author_id"         => $pieces[2],
                                "created_date"      => time(),
                                "created_by"        => $ENTRADA_USER->getActiveID(),
                            )
                        );
                        if (!$author->insert()) {
                            add_error($translate->_("There was an error attempting to add an author record to this distribution. DB said " . $db->ErrorMsg()));
                        }
                    } else {
                        add_error($translate->_("There was an error attempting to add an author with an unrecognized author type to this distribution."));
                    }
                }
            }

            if (isset($PROCESSED["distribution_approvers"]) && is_array($PROCESSED["distribution_approvers"])) {
                foreach ($PROCESSED["distribution_approvers"] as $proxy_id) {
                    $approver = new Models_Assessments_Distribution_Approver(array(
                            "adistribution_id"  => $distribution->getID(),
                            "proxy_id"          => $proxy_id,
                            "created_date"      => time(),
                            "created_by"        => $ENTRADA_USER->getActiveID()
                    ));

                    if (!$approver->insert()) {
                        add_error($translate->_("An error occurred while attempting to insert a approver." . $db->ErrorMsg()));
                    }
                }
            }

            // Delete the previous delegator if one exists.
            $old_delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
            if ($old_delegator) {
                $old_delegator->delete();
            }

            // We should only create a delegator if the distribution is new, or if it is being edited to include one.
            if ($PROCESSED["distribution_method"] == "delegation" && (isset($PROCESSED["delegator_id"]) && $PROCESSED["delegator_id"]) && (isset($PROCESSED["distribution_delegator_timeframe"]) && $PROCESSED["distribution_delegator_timeframe"])) {
                $delegator = new Models_Assessments_Distribution_Delegator(
                    array(
                        "adistribution_id" => $distribution->getID(),
                        "delegator_type" => "proxy_id",
                        "delegator_id" => $PROCESSED["delegator_id"]
                    )
                );

                if (!$delegator->insert()) {
                    add_error($translate->_("An error occurred while attempting to insert the delegator for this distribution."));
                }
            }

            if ($validated_as_date_range && $method == "update") {
                // Clear the existing rotation schedules here, since we're saving as a date range, and we're updating (clear the old ones)
                $old_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                if ($old_schedule) {
                    $old_schedule->delete();
                }
            }

            if ((isset($PROCESSED["schedule_id"]) && ($tmp_input = $PROCESSED["schedule_id"])) || (isset($PROCESSED["delegator_schedule_id"]) && ($tmp_input = $PROCESSED["delegator_schedule_id"]))) {
                $PROCESSED["period_offset"] = 0;
                if (isset($PROCESSED["period_offset_days"]) && $PROCESSED["period_offset_days"]) {
                    $PROCESSED["period_offset"] = (86400 * $PROCESSED["period_offset_days"]);
                }
                $schedule = Models_Schedule::fetchRowByID($tmp_input);
                $schedule_array = array(
                    "adistribution_id" => $distribution->getID(),
                    "addelegator_id" => (isset($delegator) && $delegator && $delegator->getID() ? $delegator->getID() : NULL),
                    "schedule_type" => $PROCESSED["schedule_delivery_type"],
                    "period_offset" => (isset($PROCESSED["period_offset"]) && (int)$PROCESSED["period_offset"] ? ((int)$PROCESSED["period_offset"]) : 0),
                    "delivery_period" => (isset($PROCESSED["delivery_period"]) && $PROCESSED["delivery_period"] ? $PROCESSED["delivery_period"] : "after-start"),
                    "schedule_id" => $schedule->getID(),
                    "frequency" => (isset($PROCESSED["frequency"]) && $PROCESSED["frequency"] ? $PROCESSED["frequency"] : 1),
                    "start_date" => NULL,
                    "end_date" => NULL
                );
                $old_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                if ($old_schedule) {
                    $old_schedule->delete();
                }
                $distribution_schedule = new Models_Assessments_Distribution_Schedule($schedule_array);

                if (!$distribution_schedule->insert()) {
                    add_error($translate->_("An error occurred while attempting to insert the schedule information for this distribution."));
                }
            }

            if (isset($PROCESSED["eventtypes"]) && is_array($PROCESSED["eventtypes"])) {
                $old_eventtypes = Models_Assessments_Distribution_Eventtype::fetchAllByDistributionID($distribution->getID());
                if ($old_eventtypes) {
                    foreach ($old_eventtypes as $old_eventtype) {
                        $old_eventtype->delete();
                    }
                }

                foreach ($PROCESSED["eventtypes"] as $key => $type) {
                    $eventtype = new Models_Assessments_Distribution_Eventtype(array(
                        "adistribution_id" => $distribution->getID(),
                        "eventtype_id" => $type
                    ));

                    if (!$eventtype->insert()) {
                        add_error($translate->_("An error occurred while attempting to insert the selected event type."));
                    }
                }
            }

            if ($method == "update") {
                $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getID());
                if ($assessors) {
                    foreach ($assessors as $assessor) {
                        $assessor->delete();
                    }
                }
                $external_assessors = Models_Assessments_Distribution_ExternalAssessor::fetchAllByDistributionID($distribution->getID());
                if ($external_assessors) {
                    foreach ($external_assessors as $external_assessor) {
                        $external_assessor->delete();
                    }
                }
            }

            $external_assessor_model = new Models_Assessments_Distribution_ExternalAssessor();
            $course_contact_model = new Models_Assessments_Distribution_CourseContact();

            if (isset($PROCESSED["assessor_option"]) && $PROCESSED["assessor_option"] && $validated_as_date_range) {

                switch ($PROCESSED["assessor_option"]) {

                    case "individual_users" :
                        $PROCESSED["assessor_role"] = "any";
                        foreach ($PROCESSED["assessors"] as $key => $assessor_id) {

                            // When the assessors are individuals the format of the submitted value will be formatted as internal_id or external_id depending on whether or not
                            // the users were added as external or selected from the existing users list.
                            // So the value of pieces[0] will be either internal or external and pieces[1] will be either the users proxy_id or their external assessor_id

                            $pieces = explode("_", $assessor_id);
                            $assessor_value = $pieces[1];

                            if ($pieces[0] === "internal") {
                                $assessor_type = "proxy_id";
                            } else {
                                $assessor_type = "external_hash";
                            }

                            if (!$ERROR && $assessor_value) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => $assessor_type,
                                    "assessor_scope" => "self",
                                    "assessor_value" => $assessor_value,
                                    "assessor_role" => "any"
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert assessor(s)"));
                                }
                            }

                            if (!$ERROR && $assessor_value) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor_value, $pieces[0]);
                            }
                        }
                        break;

                    case "learner" :
                        $PROCESSED["assessor_role"] = "learner";
                        $grouped_value = false;
                        if (isset($PROCESSED["assessor_cohort_id"])) {
                            $assessor_type = "group_id";
                            $grouped_value = $PROCESSED["assessor_cohort_id"];
                        }

                        if (isset($PROCESSED["assessor_course_id"])) {
                            $assessor_type = "course_id";
                            $grouped_value = $PROCESSED["assessor_course_id"];
                        }

                        $assessor = new Models_Assessments_Distribution_Assessor(array(
                            "adistribution_id" => $distribution->getID(),
                            "assessor_type" => $assessor_type,
                            "assessor_scope" => "all_learners",
                            "assessor_value" => $grouped_value,
                            "assessor_role" => "learner"
                        ));

                        if (!$assessor->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert external assessors"));
                        }
                        break;

                    case "faculty" :
                        $PROCESSED["assessor_role"] = "faculty";
                        foreach ($PROCESSED["assessor_faculty"] as $key => $assessor_id) {
                            $assessor = new Models_Assessments_Distribution_Assessor(array(
                                "adistribution_id" => $distribution->getID(),
                                "assessor_type" => "proxy_id",
                                "assessor_scope" => "self",
                                "assessor_value" => $assessor_id,
                                "assessor_role" => "faculty"
                            ));

                            if (!$assessor->insert()) {
                                add_error($translate->_("An error occurred while attempting to insert an assessor."));
                            }

                            if (!$ERROR) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor_id, "internal");
                            }
                        }
                        break;
                }
            } elseif (isset($PROCESSED["assessor_option"]) && $PROCESSED["assessor_option"] && !$validated_as_date_range) {
                switch ($PROCESSED["assessor_option"]) {
                    case "individual_users" :
                        $PROCESSED["assessor_role"] = "any";
                        foreach ($PROCESSED["assessors"] as $key => $assessor_id) {

                            // When the assessors are individuals the format of the submitted value will be formatted as internal_id or external_id depending on whether or not
                            // the users were added as external or selected from the existing users list.
                            // So the value of pieces[0] will be either internal or external and pieces[1] will be either the users proxy_id or their external assessor_id

                            $pieces = explode("_", $assessor_id);
                            $assessor_value = $pieces[1];

                            if ($pieces[0] === "internal") {
                                $assessor_type = "proxy_id";
                            } else {
                                $assessor_type = "external_hash";
                            }

                            if (!$ERROR && $assessor_value) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => $assessor_type,
                                    "assessor_scope" => "self",
                                    "assessor_value" => $assessor_value,
                                    "assessor_role" => "any"
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert external assessors"));
                                }
                            }

                            if (!$ERROR && $assessor_value) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor_value, $pieces[0]);
                            }
                        }
                        break;
                    case "learner" :
                        $PROCESSED["assessor_role"] = "learner";
                        if (isset($PROCESSED["distribution_rs_assessor_learner_option"]) && $PROCESSED["distribution_rs_assessor_learner_option"]) {
                            switch ($PROCESSED["distribution_rs_assessor_learner_option"]) {
                                case "individual" :
                                    if (!isset($PROCESSED["individual_assessor_learners"]) || !is_array($PROCESSED["individual_assessor_learners"])) {
                                        $PROCESSED["individual_assessor_learners"] = array();
                                    }
                                    if (!isset($PROCESSED["additional_assessor_learners"]) || !is_array($PROCESSED["additional_assessor_learners"])) {
                                        $PROCESSED["additional_assessor_learners"] = array();
                                    }
                                    $learner_ids = array_merge($PROCESSED["individual_assessor_learners"], $PROCESSED["additional_assessor_learners"]);

                                    if ($learner_ids) {
                                        foreach ($learner_ids as $learner_id) {
                                            $assessor = new Models_Assessments_Distribution_Assessor(array(
                                                "adistribution_id" => $distribution->getID(),
                                                "assessor_type" => "proxy_id",
                                                "assessor_role" => "learner",
                                                "assessor_scope" => "self",
                                                "assessor_value" => $learner_id
                                            ));

                                            if (!$assessor->insert()) {
                                                add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                            }
                                        }
                                    }

                                    break;
                                case "all" :
                                    if ($PROCESSED["schedule_id"] && isset($PROCESSED["distribution_rs_learner_service"]) && is_array($PROCESSED["distribution_rs_learner_service"])) {

                                        $service = false;
                                        foreach ($PROCESSED["distribution_rs_learner_service"] as $learner_service_type) {
                                            if (!$service) {
                                                $service = ($learner_service_type == "onservice" ? "internal_learners" : ($learner_service_type == "offservice" ? "external_learners" : "all_learners"));
                                            } elseif ($service && (($service == "internal_learners" && $learner_service_type == "offservice") || ($service == "external_learners" && $learner_service_type == "onservice"))) {
                                                $service = "all_learners";
                                            }
                                        }
                                        if ($service) {
                                            $assessor = new Models_Assessments_Distribution_Assessor(array(
                                                "adistribution_id" => $distribution->getID(),
                                                "assessor_type" => "schedule_id",
                                                "assessor_role" => "learner",
                                                "assessor_scope" => $service,
                                                "assessor_value" => $PROCESSED["schedule_id"]
                                            ));
                                            if (!$assessor->insert()) {
                                                add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                            }
                                        }
                                        if (isset($PROCESSED["additional_assessor_learners"]) && is_array($PROCESSED["additional_assessor_learners"])) {
                                            foreach ($PROCESSED["additional_assessor_learners"] as $learner_id) {
                                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                                    "adistribution_id" => $distribution->getID(),
                                                    "assessor_type" => "proxy_id",
                                                    "assessor_role" => "learner",
                                                    "assessor_scope" => "self",
                                                    "assessor_value" => $learner_id
                                                ));

                                                if (!$assessor->insert()) {
                                                    add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                                }
                                            }
                                        }
                                    }
                                    break;
                            }
                        } else if (isset($PROCESSED["distribution_eventtype_target_option"])) {
                            if (isset($PROCESSED["distribution_eventtype_learners"])) {
                                switch ($PROCESSED["distribution_eventtype_learners"]) {
                                    case "attended" :
                                        $assessor_role = "learner";
                                        $assessor_scope = "attended_learners";
                                        break;
                                    case "all-learners" :
                                        $assessor_role = "learner";
                                        $assessor_scope = "all_learners";
                                        break;
                                }

                                if (isset($PROCESSED["eventtypes"]) && is_array($PROCESSED["eventtypes"])) {
                                    foreach ($PROCESSED["eventtypes"] as $eventtype_id) {
                                        $assessor = new Models_Assessments_Distribution_Assessor(array(
                                            "adistribution_id" => $distribution->getID(),
                                            "assessor_type" => "eventtype_id",
                                            "assessor_role" => $assessor_role,
                                            "assessor_scope" => $assessor_scope,
                                            "assessor_value" => $eventtype_id
                                        ));

                                        if (!$assessor->insert()) {
                                            add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case "faculty" :
                        $PROCESSED["assessor_role"] = "faculty";
                        if (isset($PROCESSED["eventtypes"]) && is_array($PROCESSED["eventtypes"])) {
                            foreach ($PROCESSED["eventtypes"] as $eventtype_id) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => "eventtype_id",
                                    "assessor_role" => "faculty",
                                    "assessor_scope" => "faculty",
                                    "assessor_value" => $eventtype_id
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                }
                            }
                        }
                        /*
                        if (isset($PROCESSED["additional_assessor_eventtype_faculty"]) && is_array($PROCESSED["additional_assessor_eventtype_faculty"])) {
                            foreach ($PROCESSED["additional_assessor_eventtype_faculty"] as $proxy_id) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => "proxy_id",
                                    "assessor_role" => "faculty",
                                    "assessor_scope" => "self",
                                    "assessor_value" => $proxy_id
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                }
                            }
                        }
                        */

                        if (isset($PROCESSED["assessor_faculty"]) && is_array($PROCESSED["assessor_faculty"])) {
                            foreach ($PROCESSED["assessor_faculty"] as $key => $assessor_id) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => "proxy_id",
                                    "assessor_scope" => "self",
                                    "assessor_value" => $assessor_id,
                                    "assessor_role" => "faculty"
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                }

                                if (!$ERROR) {
                                    $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor_id, "internal");
                                }
                            }
                        }

                        if (isset($PROCESSED["additional_assessor_faculty"]) && is_array($PROCESSED["additional_assessor_faculty"])) {
                            foreach ($PROCESSED["additional_assessor_faculty"] as $proxy_id) {
                                $assessor = new Models_Assessments_Distribution_Assessor(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "assessor_type" => "proxy_id",
                                    "assessor_role" => "faculty",
                                    "assessor_scope" => "self",
                                    "assessor_value" => $proxy_id
                                ));

                                if (!$assessor->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert an assessor."));
                                }

                                if (!$ERROR) {
                                    $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $proxy_id, "internal");
                                }
                            }
                        }
                        break;
                }
            }

            if ($method == "update") {
                $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                if ($targets) {
                    foreach ($targets as $target) {
                        $target->delete();
                    }
                }
            }
            if (isset($PROCESSED["distribution_target_option"]) && $PROCESSED["distribution_target_option"]) {
                switch ($PROCESSED["distribution_target_option"]) {
                    case "self" :
                        $data = array(
                            "adistribution_id" => $distribution->getID(),
                            "target_type" => "self",
                            "target_scope" => "self",
                            "target_role" => "any",
                            "target_id" => NULL
                        );
                        $target = new Models_Assessments_Distribution_Target($data);
                        if (!$target->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert a target."));
                        }
                        break;
                    case "faculty" :
                        if (isset($PROCESSED["target_faculty"]) && is_array($PROCESSED["target_faculty"])) {
                            foreach ($PROCESSED["target_faculty"] as $proxy_id) {
                                $target = new Models_Assessments_Distribution_Target(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "target_type" => "proxy_id",
                                    "target_scope" => "self",
                                    "target_role" => "faculty",
                                    "target_id" => $proxy_id
                                ));

                                if (!$target->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert a target."));
                                }
                            }
                        }
                        break;
                    case "grouped_users" :
                        $grouped_value = false;
                        if (isset($PROCESSED["target_cohort_id"])) {
                            $target_type = "group_id";
                            $grouped_value = $PROCESSED["target_cohort_id"];
                        }

                        if (isset($PROCESSED["target_course_audience_id"])) {
                            $target_type = "course_id";
                            $grouped_value = $PROCESSED["target_course_audience_id"];
                        }

                        if (isset($PROCESSED["target_organisation_id"])) {
                            $target_type = "organisation_id";
                            $grouped_value = $PROCESSED["target_organisation_id"];
                        }

                        $target = new Models_Assessments_Distribution_Target(array(
                            "adistribution_id" => $distribution->getID(),
                            "target_type" => $target_type,
                            "target_scope" => "all_learners",
                            "target_role" => "learner",
                            "target_id" => $grouped_value
                        ));

                        if (!$target->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert a target"));
                        }

                        break;
                    case "course" :
                        if (isset($PROCESSED["target_course_id"]) && $PROCESSED["target_course_id"]) {
                            $target = new Models_Assessments_Distribution_Target(array(
                                "adistribution_id" => $distribution->getID(),
                                "target_type" => "course_id",
                                "target_scope" => "self",
                                "target_role" => "any",
                                "target_id" => $PROCESSED["target_course_id"]
                            ));

                            if (!$target->insert()) {
                                add_error($translate->_("An error occurred while attempting to insert a target"));
                            }
                        }


                        break;
                    case "individual_users" :
                        $PROCESSED["target_role"] = "any";
                        foreach ($PROCESSED["targets"] as $key => $target_id) {

                            $target_type = "proxy_id";

                            $target = new Models_Assessments_Distribution_Target(array(
                                "adistribution_id" => $distribution->getID(),
                                "target_type" => $target_type,
                                "target_scope" => "self",
                                "target_id" => $target_id,
                                "target_role" => "any"
                            ));

                            if (!$target->insert()) {
                                add_error($translate->_("An error occurred while attempting to insert individual targets"));
                            }
                        }
                        break;
                }
            } elseif (isset($PROCESSED["distribution_rs_target_option"]) && $PROCESSED["distribution_rs_target_option"]) {
                switch ($PROCESSED["distribution_rs_target_option"]) {
                    case "learner" :
                        if (isset($PROCESSED["distribution_rs_target_learner_option"]) && $PROCESSED["distribution_rs_target_learner_option"]) {
                            switch ($PROCESSED["distribution_rs_target_learner_option"]) {
                                case "individual" :
                                    if (!isset($PROCESSED["individual_target_learner"]) || !is_array($PROCESSED["individual_target_learner"])) {
                                        $PROCESSED["individual_target_learner"] = array();
                                    }
                                    if (!isset($PROCESSED["additional_target_learners"]) || !is_array($PROCESSED["additional_target_learners"])) {
                                        $PROCESSED["additional_target_learners"] = array();
                                    }
                                    $learner_ids = array_merge($PROCESSED["individual_target_learner"], $PROCESSED["additional_target_learners"]);
                                    if ($learner_ids) {
                                        foreach ($learner_ids as $learner_id) {
                                            $target = new Models_Assessments_Distribution_Target(array(
                                                "adistribution_id" => $distribution->getID(),
                                                "target_type" => "proxy_id",
                                                "target_scope" => "self",
                                                "target_role" => "learner",
                                                "target_id" => $learner_id
                                            ));

                                            if (!$target->insert()) {
                                                add_error($translate->_("An error occurred while attempting to insert a target."));
                                            }
                                        }
                                    }

                                    break;
                                case "all" :
                                    if ($PROCESSED["schedule_id"] && isset($PROCESSED["distribution_rs_target_learner_service"]) && is_array($PROCESSED["distribution_rs_target_learner_service"])) {
                                        $service = false;
                                        foreach ($PROCESSED["distribution_rs_target_learner_service"] as $learner_service_type) {
                                            if (!$service) {
                                                $service = ($learner_service_type == "onservice" ? "internal_learners" : ($learner_service_type == "offservice" ? "external_learners" : "all_learners"));
                                            } elseif ($service && (($service == "internal_learners" && $learner_service_type == "offservice") || ($service == "external_learners" && $learner_service_type == "onservice"))) {
                                                $service = "all_learners";
                                            }
                                        }

                                        if ($service) {
                                            $data = array(
                                                "adistribution_id" => $distribution->getID(),
                                                "target_type" => "schedule_id",
                                                "target_scope" => $service,
                                                "target_id" => $PROCESSED["schedule_id"],
                                                "target_role" => "learner"
                                            );
                                            $target = new Models_Assessments_Distribution_Target($data);
                                            if (!$target->insert()) {
                                                add_error($translate->_("An error occurred while attempting to insert a target."));
                                            }
                                        }
                                        if (isset($PROCESSED["additional_target_learners"]) && is_array($PROCESSED["additional_target_learners"])) {
                                            foreach ($PROCESSED["additional_target_learners"] as $learner_id) {
                                                $data = array(
                                                    "adistribution_id" => $distribution->getID(),
                                                    "target_type" => "proxy_id",
                                                    "target_scope" => "self",
                                                    "target_role" => "learner",
                                                    "target_id" => $learner_id
                                                );
                                                $target = new Models_Assessments_Distribution_Target($data);

                                                if (!$target->insert()) {
                                                    add_error($translate->_("An error occurred while attempting to insert a target."));
                                                }
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                        break;
                    case "self" :
                        $data = array(
                            "adistribution_id" => $distribution->getID(),
                            "target_type" => "self",
                            "target_scope" => "self",
                            "target_role" => "any",
                            "target_id" => NULL,
                        );
                        $target = new Models_Assessments_Distribution_Target($data);
                        if (!$target->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert a target."));
                        }
                        break;
                    case "faculty" :
                        if (isset($PROCESSED["additional_target_faculty"]) && is_array($PROCESSED["additional_target_faculty"])) {
                            foreach ($PROCESSED["additional_target_faculty"] as $proxy_id) {
                                $target = new Models_Assessments_Distribution_Target(array(
                                    "adistribution_id" => $distribution->getID(),
                                    "target_type" => "proxy_id",
                                    "target_scope" => "self",
                                    "target_role" => "faculty",
                                    "target_id" => $proxy_id
                                ));

                                if (!$target->insert()) {
                                    add_error($translate->_("An error occurred while attempting to insert a target."));
                                }
                            }
                        }
                        break;
                    case "block" :
                        $data = array(
                            "adistribution_id" => $distribution->getID(),
                            "target_type" => "schedule_id",
                            "target_scope" => "self",
                            "target_role" => "any",
                            "target_id" => $PROCESSED["schedule_id"],
                        );
                        $target = new Models_Assessments_Distribution_Target($data);
                        if (!$target->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert a target."));
                        }
                        break;
                }
            } elseif (isset($PROCESSED["distribution_eventtype_target_option"])) {
                switch ($PROCESSED["distribution_eventtype_target_option"]) {
                    case "learner" :
                        $target_scope = "self";
                        $target_role = "learner";
                    break;
                    case "faculty" :
                        $target_scope = "self";
                        $target_role = "faculty";
                    break;
                    case "event" :
                        $target_scope = "self";
                        $target_role = "any";
                    break;
                }

                if (isset($PROCESSED["eventtypes"]) && is_array($PROCESSED["eventtypes"])) {
                    foreach ($PROCESSED["eventtypes"] as $eventtype_id) {
                        $target = new Models_Assessments_Distribution_Target(array(
                            "adistribution_id" => $distribution->getID(),
                            "target_type" => "eventtype_id",
                            "target_scope" => $target_scope,
                            "target_role" => $target_role,
                            "target_id" => $eventtype_id
                        ));

                        if (!$target->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert a target."));
                        }
                    }
                }
            }

            if ($method == "update") {
                $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($distribution->getID());
                if ($reviewers) {
                    foreach ($reviewers as $reviewer) {
                        $reviewer->delete();
                    }
                }
            }
            if (isset($PROCESSED["distribution_results_user"]) && is_array($PROCESSED["distribution_results_user"])) {
                foreach ($PROCESSED["distribution_results_user"] as $proxy_id) {
                    $reviewer = new Models_Assessments_Distribution_Reviewer(array(
                        "adistribution_id" => $distribution->getID(),
                        "proxy_id" => $proxy_id,
                        "created_date" => time(),
                        "created_by" => $ENTRADA_USER->getID()
                    ));

                    if (!$reviewer->insert()) {
                        add_error($translate->_("An error occurred while attempting to insert a reviewer."));
                    }
                }
            }
        } else {
            application_log("error", "An error occurred while attempting to save this distribution. DB:" . $db->ErrorMsg());
        }
    }

    public function loadRecordAsValidatedData($data_record, $validated_data = array()) {
        $this->validated_data = static::_loadRecordAsValidatedData($data_record, get_class($this));
        if (isset($this->validated_data["form_id"]) && $this->validated_data["form_id"]) {
            $form = Models_Assessments_Form::fetchRowByID($this->validated_data["form_id"]);
            if ($form) {
                $this->validated_data["form_title"] = $form->getTitle();
                $this->validated_data["distribution_form"] = $form->getID();
            }
        }
        if (isset($data_record["delegator"]) && $data_record["delegator"]) {
            $this->validated_data["delegator_id"] = $data_record["delegator"]["delegator_id"];
            $this->validated_data["delegator_name"] = get_account_data("wholename", $this->validated_data["delegator_id"]);
            $this->validated_data["method_name"] = "Delegation";
            $this->validated_data["distribution_method"] = "delegation";
        }

        if (isset($data_record["schedule_id"]) && $data_record["schedule_id"]) {
            $this->validated_data["schedule_delivery_type"] = $data_record["schedule_type"];
            if (isset($this->validated_data["delegator_id"]) && $this->validated_data["delegator_id"]) {
                $this->validated_data["distribution_delegator_timeframe"] = "rotation_schedule";
            } else {
                $this->validated_data["distribution_method"] = "rotation_schedule";
                $this->validated_data["method_name"] = "Rotation Schedule";
                $this->validated_data["distribution_method"] = "rotation_schedule";
            }
        } elseif (isset($data_record["eventtypes"]) && $data_record["eventtypes"]) {
            $this->validated_data["method_name"] = "Learning Event Schedule";
            $this->validated_data["distribution_method"] = "eventtype";
            $this->validated_data["eventtypes"] = $data_record["eventtypes"];
        } elseif (!isset($data_record["delegator"]) || !$data_record["delegator"]) {
            $this->validated_data["method_name"] = "Date Range";
            $this->validated_data["distribution_method"] = "date_range";
            $this->validated_data["range_start_date"] = $data_record["start_date"];
            $this->validated_data["range_end_date"] = $data_record["end_date"];
            $this->validated_data["delivery_date"] = $data_record["delivery_date"];
        } else {
            $this->validated_data["distribution_delegator_timeframe"] = "date_range";
            $this->validated_data["range_start_date"] = $data_record["start_date"];
            $this->validated_data["range_end_date"] = $data_record["end_date"];
            $this->validated_data["delivery_date"] = $data_record["delivery_date"];
        }

        if (!isset($this->validated_data["course_id"]) || !$this->validated_data["course_id"]) {
            $this->validated_data["course_name"] = "No Course Affiliation";
        }

        return $this->validated_data;
    }
}