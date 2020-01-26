var timeout;

jQuery(function($) {
    $("#distribution-data-form").tooltip({
        selector: '[data-toggle="tooltip"]',
        placement: "top"
    });

    $("#wizard-step-input").val("1");
    
    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        remove_filter(filter_type, filter_target);
    });

    $(".panel .clear-filters").on("click", function (e) {
        e.preventDefault();
        remove_all_filters();
    });
       
    $("#load-distributions").on("click", function (e) {
        e.preventDefault();
        if (!$(this).hasClass("load-distributions-disabled")) {
            $(this).addClass("loading");
            get_distributions();
        }
    });
    
    $("#distribution-search").keyup(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }

            $("#assessments-no-results").remove();
            $("#distributions-table tbody").empty();
            $("#distributions-table").addClass("hide");
            $("#load-distributions").addClass("hide");
            $("#assessment-distributions-loading").removeClass("hide");

            clearTimeout(timeout);
            timeout = window.setTimeout(get_distributions, 700, false);
        }
    });

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    if ($("#distribution_delivery_date").length && $("#expiry-date").length) {
        $("#distribution_delivery_date").datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function (date_text, inst) {
                if ($("#expiry-date").length) {
                    $("#expiry-date").datepicker(
                        "option", "minDate", $("#distribution_delivery_date").datepicker("getDate")
                    );
                }
            }
        });
        $("#expiry-date").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: $("#distribution_delivery_date").datepicker("getDate")
        });
    }

    $(".timepicker").timepicker({
        minutes: {
            starts: 0,
            ends: 59,
            interval: 5
        }
    });
    
    /**
     * 
     * Event listeners associated with creating distributions
     * 
     */

    $(".schedule_delivery_type").on("change", function(e) {
        $("#rotation-schedule-delivery-offset").removeClass("hide");
        var radio_button = $(this);
        switch (radio_button.data("timeline-options")) {
            case "repeat" :
                $("#timeline-option-repeat").removeClass("hide");
                $("#timeline-option-once-per").addClass("hide");
            break;
            case "once-per" :
                $("#timeline-option-once-per").removeClass("hide");
                $("#timeline-option-repeat").addClass("hide");
                switch (radio_button.hasClass("rotation")) {
                    case true:
                        $(".once-per-rotation").removeClass("hide");
                        $(".once-per-block").addClass("hide");
                    break;
                    case false:
                        $(".once-per-block").removeClass("hide");
                        $(".once-per-rotation").addClass("hide");
                    break;
                }
            break;
        }
    });

    $("#choose-cperiod-btn").on("change", function () {
        var selected_option = $("input[name=\"cperiod_id\"]").val();
        var settings = $("#rs-choose-rotation-btn").data("settings");
        settings.filters["rs_schedule"].api_params.cperiod_id = selected_option;

        $("#rs-choose-rotation-btn").html("Browse Rotation Schedules <i class=\"icon-chevron-down btn-icon pull-right\"</i>");
        $("input[name=\"schedule_id\"]").remove();
    });

    $("#choose-method-btn").on("change", function() {
        var selected_option = $("input[name=\"distribution_method\"]").val();
        $(".distribution-options").addClass("hide");
        
        switch (selected_option) {
            case "date_range" :
                $("#distribution-specific-date-options").removeClass("hide");
                $("#distribution-rotation-schedule-options").addClass("hide");
                $("#distribution-rotation-delivery-options").addClass("hide");
                $("#rotation-schedule-delivery-offset").addClass("hide");
                $("#distribution-delegator-options").addClass("hide");
                $("#distribution-delegator-options input[type='radio']").prop("checked", false);
                $("#selected-delegation-option").val("");
                $("input[name=\"distribution_delegator_timeframe\"]").removeAttr("checked");
                // Date range distributions do expiry via a date picker rather than offset.
                resetExpiryControls("date", true);
            break;
            case "rotation_schedule" :
                showScheduleOptions();
                $("#distribution-specific-date-options").addClass("hide");
                $("#distribution-delegator-options").addClass("hide");
                $("#distribution-delegator-options input[type='radio']").prop("checked", false);
                $("#selected-delegation-option").val("");
                $("input[name=\"distribution_delegator_timeframe\"]").removeAttr("checked");
                resetExpiryControls("offset", true);
            break;
            case "delegation" :
                $("#distribution-delegator-options").removeClass("hide");
                $("#distribution-specific-date-options").addClass("hide");
                $("#distribution-rotation-schedule-options").addClass("hide");
                $("#rs-rotation-schedule-options").addClass("hide");
                $("#distribution-rotation-delivery-options").addClass("hide");
                $("#rotation-schedule-delivery-offset").addClass("hide");
                resetExpiryControls("offset", true);
            break;
            case "eventtype" :
                $("#distribution-eventtype-options").removeClass("hide");
                $("#distribution-specific-date-options").addClass("hide");
                $("#distribution-rotation-schedule-options").addClass("hide");
                $("#rs-rotation-schedule-options").addClass("hide");
                $("#distribution-rotation-delivery-options").addClass("hide");
                $("#rotation-schedule-delivery-offset").addClass("hide");
                $("#selected-delegation-option").val("");
                $("input[name=\"distribution_delegator_timeframe\"]").removeAttr("checked");
                resetExpiryControls("offset", true);
            break;
        }

        if ($("input[name=\"cperiod_id\"]").length) {
            var selected_option = $("input[name=\"cperiod_id\"]").val();
            var settings = $("#rs-choose-rotation-btn").data("settings");
            settings.filters["rs_schedule"].api_params.cperiod_id = selected_option;
        }
        
        reset_wizard_step_3(null);
        reset_wizard_step_4(null);
    });

    $("input[name=\"distribution_delegator_timeframe\"]").on("change", function () {
        var selected_option = $(this).val();
        $(".distribution-delegator-options").addClass("hide");

        switch (selected_option) {
            case "date_range" :
                $("#distribution-specific-date-options").removeClass("hide");
                $("#distribution-rotation-schedule-options").addClass("hide");
                $("#distribution-rotation-delivery-options").addClass("hide");
                break;
            case "rotation_schedule" :
                showScheduleOptions();
                $("#distribution-specific-date-options").addClass("hide");
                break;
        }
    });

    // Show or hide custom reviewers option.
    $("#flagging_notifications").on("change", function () {
        if ($(this).val() == "reviewers") {
            toggleReviewControls(true);
        } else {
            toggleReviewControls(false);
            // Remove the assessment reviewers that might have been added that are now unnecessary.
            jQuery('input[name="distribution_results_user[]"]').remove();
        }
    });

    function showScheduleOptions() {
        jQuery("#distribution-rotation-schedule-options").removeClass("hide");
        if (jQuery("input:hidden[name=\"course_id\"]").val()) {
            jQuery("#rs-rotation-schedule-options").removeClass("hide");
            if (jQuery("input:hidden[name=\"schedule_id\"]").val()) {
                jQuery("#distribution-rotation-delivery-options").removeClass("hide");
                if (jQuery("input:radio[name=\"schedule_delivery_type\"]:checked").length > 0) {
                    jQuery("#rotation-schedule-delivery-offset").removeClass("hide");
                }
            }
        }

    }

    $("#choose-course-btn").on("change", function () {
        var selected_option = $("input[name=\"course_id\"]").val();
        $("#rs-choose-rotation-btn").html("Browse Rotation Schedules <i class=\"icon-chevron-down btn-icon pull-right\"</i>");
        $("input[name=\"schedule_id\"]").remove();
        $("#distribution-rotation-delivery-options").addClass("hide");
        var settings = $("#rs-choose-rotation-btn").data("settings");
        settings.filters["rs_schedule"].api_params.course_id = selected_option;
        var assessor_settings = $("#choose-assessors-faculty-btn").data("settings");
        assessor_settings.filters["assessor_faculty"].api_params.course_id = selected_option;
        var faculty_settings = $("#choose-targets-faculty-btn").data("settings");
        faculty_settings.filters["target_faculty"].api_params.course_id = selected_option;
        var approver_settings = $("#distribution-approver-results").data("settings");
        approver_settings.filters["distribution_approvers"].api_params.course_id = selected_option;
        var targets_btn_settings = $("#choose-targets-btn").data("settings");
        targets_btn_settings.filters["target_individual"].api_params.course_id = selected_option;
        targets_btn_settings.filters["target_cgroup"].api_params.course_id = selected_option;
        var assessors_btn_settings = $("#choose-assessors-btn").data("settings");
        assessors_btn_settings.filters["assessor_individual"].api_params.course_id = selected_option;
        assessors_btn_settings.filters["assessor_cgroup"].api_params.course_id = selected_option;

        if (($("input[name='distribution_method']").length > 0 && $("input[name='distribution_method']").val() == "rotation_schedule") || ($("#delegator_timeframe_rotation_schedule").length > 0 && $("#delegator_timeframe_rotation_schedule").attr("checked") == "checked")) {
            if (selected_option !== "0") {
                $("#rs-rotation-schedule-options").removeClass("hide");
            } else {
                $("#rs-rotation-schedule-options").addClass("hide");
            }
        }
    });

    $("#rs-choose-rotation-btn").on("change", function () {
        $("#distribution-rotation-delivery-options").removeClass("hide");
        var selected_option = $("input[name=\"schedule_id\"]").val();
        var settings = $("#choose-assessors-rs-individual-btn").data("settings");
        var target_settings = $("#choose-targets-rs-individual-btn").data("settings");

        if (selected_option !== "0") {
            settings.filters["individual_assessor_learners"].api_params.schedule_id = selected_option;
            target_settings.filters["individual_target_learner"].api_params.schedule_id = selected_option;
        }

        if (selected_option !== "0") {
            $("#rs-schedule-offset-options").removeClass("hide");
        } else {
            $("#rs-schedule-offset-options").addClass("hide");
        }
    });
    
    $("#assessor-options").on("change", function () {
        var selected_option = $(this).val();
        
        if (selected_option !== "0") {
            $("#assessment-assessor-options").removeClass("hide");
        } else {
            $("#assessment-assessor-options").addClass("hide");
        }
    });

    $("input[name=\"date_range_release_option\"]").on("change", function () {
        if ($("#date-range-release-control").hasClass("hide")) {
            $("#date-range-release-control").removeClass("hide");
        } else {
            $("#date-range-release-control").addClass("hide");
            $("input[name=\"rotation_release_date\"]").val("");
        }
    });

    $("input[name=\"rotation_release_option\"]").on("change", function () {
        if ($("#rotation-release-option").attr("checked") == "checked") {
            $("#rotation-release-control").css("display", "inline-block");
        } else {
            $("#rotation-release-control").css("display", "none");
        }
    });

    $("input[name=\"eventtype_release_option\"]").on("change", function () {
        if ($("#eventtype-release-option").attr("checked") == "checked") {
            $("#eventtype-release-control").css("display", "inline-block");
        } else {
            $("#eventtype-release-control").css("display", "none");
        }
    });

    $("input[name=\"expiry_option\"]").on("change", function () {
        if ($("#expiry-option").attr("checked") == "checked") {
            $("#expiry-control").css("display", "inline-block");
            $("#expiry-notification-option-controls").removeClass("hide");
        } else {
            $("#expiry-control").css("display", "none");
            $("#expiry-notification-option-controls").addClass("hide");
        }
    });

    $("input[name=\"expiry_notification_option\"]").on("change", function () {
        if ($("#expiry-notification-option").attr("checked") == "checked") {
            $("#expiry-notification-control").css("display", "inline-block");
        } else {
            $("#expiry-notification-control").css("display", "none");
        }
    });

    $("#content").on("change", 'input[name="distribution_assessor_option"]', function () {
        var selected_option = $(this).val();
        var selected_target_option = $("input[name=\"distribution_target_option\"]:checked").val();
        var settings = jQuery("#choose-method-btn").data("settings");
        var selected_method = settings.value;
        var delegation_date_range = $("#delegator_timeframe_date_range").attr("checked") == "checked";
        $(".assessor-option").addClass("hide");
        $("#distribution-feedback-options").addClass("hide");
        $("#select-approvers").addClass("hide");
        $("#feedback-required").removeAttr("checked");
        $("#approver-required").removeAttr("checked");

        switch (selected_option) {
            case "grouped_users" :
                $("#select-assessors-grouped").removeClass("hide");
                break;
            case "individual_users" :
                $("#select-assessors-individual").removeClass("hide");
                if (selected_method == "date_range" || (selected_method == "delegation" && delegation_date_range)) {
                    if (selected_target_option === "grouped_users" || selected_target_option === "individual_users") {
                        $("#distribution-feedback-options").removeClass("hide");
                    }
                }
                break;
            case "faculty" :
                $("#select-assessors-faculty").removeClass("hide");
                if (selected_method == "date_range" || (selected_method == "delegation" && delegation_date_range)) {
                    if (selected_target_option === "grouped_users" || selected_target_option === "individual_users") {
                        $("#distribution-feedback-options").removeClass("hide");
                    }
                } else if (selected_target_option === "grouped_users") {
                    $("#distribution-feedback-options").removeClass("hide");
                }
                break;
        }
    });
    
    $("input[name=\"distribution_target_option\"]").on("change", function () {
        var selected_option = $(this).val();
        var selected_assessor_option = $("input[name=\"distribution_assessor_option\"]:checked").val();
        var settings = jQuery("#choose-method-btn").data("settings");
        var selected_method = settings.value;
        var delegation_date_range = $("#delegator_timeframe_date_range").attr("checked") == "checked";
        $(".target-option").addClass("hide");
        $("#distribution-feedback-options").addClass("hide");
        $("#select-approvers").addClass("hide");
        $("#feedback-required").removeAttr("checked");
        $("#approver-required").removeAttr("checked");
        $("#select-target-options").addClass("hide");
        $("#non-cbme-targets-btn").attr("checked", "checked");

        switch (selected_option) {
            case "grouped_users" :
                $("#select-targets-grouped").removeClass("hide");
                if (cbme == true) {
                    $("#select-target-options").removeClass("hide");
                }
                if (selected_method == "date_range" || (selected_method == "delegation" && delegation_date_range)) {
                    if (selected_assessor_option === "faculty" || selected_assessor_option === "individual_users") {
                        $("#distribution-feedback-options").removeClass("hide");
                    }
                } else if (selected_assessor_option === "faculty") {
                    $("#distribution-feedback-options").removeClass("hide");
                }

                if ($("#assessment_type").val() == "assessment") {
                    $("#select-targets-grouped").removeClass("hide");
                } else {
                    $("#select-targets-grouped").addClass("hide");
                }
                break;
            case "faculty" :
                if ($("#assessment_type").val() == "assessment") {
                    $("#distribution-assessor-faculty").parent().removeClass("hide");
                    $("#distribution-assessor-internal").parent().addClass("hide");
                    $("#distribution-assessor-external").parent().removeClass("hide");

                    $("#select-assessors-grouped").addClass("hide");
                } else {
                    $("#distribution-assessor-faculty").parent().addClass("hide");
                    $("#distribution-assessor-internal").parent().removeClass("hide");
                    $("#distribution-assessor-external").parent().addClass("hide");
                }

                $("#select-targets-faculty").removeClass("hide");
                break;
            case "course" :
                $("#select-targets-course").removeClass("hide");
                break;
            case "individual_users" :
                $("#select-targets-individual").removeClass("hide");
                if (selected_method == "date_range" || (selected_method == "delegation" && delegation_date_range)) {
                    if (selected_assessor_option === "faculty" || selected_assessor_option === "individual_users") {
                        $("#distribution-feedback-options").removeClass("hide");
                    }
                }

                if ($("#assessment_type").val() == "assessment") {
                    $("#distribution-assessor-faculty").parent().removeClass("hide");
                    $("#distribution-assessor-internal").parent().addClass("hide");
                    $("#distribution-assessor-external").parent().removeClass("hide");

                    $("#select-assessors-faculty").addClass("hide");
                    $("#select-assessors-grouped").addClass("hide");
                } else {
                    $("#distribution-assessor-faculty").parent().addClass("hide");
                    $("#distribution-assessor-internal").parent().removeClass("hide");
                    $("#distribution-assessor-external").parent().addClass("hide");
                }
                break;
            case "external" :
                if ($("#assessment_type").val() == "assessment") {
                    $("#distribution-assessor-faculty").parent().removeClass("hide");
                    $("#distribution-assessor-internal").parent().addClass("hide");
                    $("#distribution-assessor-external").parent().removeClass("hide");

                    $("#select-assessors-grouped").addClass("hide");
                } else {
                    $("#distribution-assessor-faculty").parent().addClass("hide");
                    $("#distribution-assessor-internal").parent().removeClass("hide");
                    $("#distribution-assessor-external").parent().addClass("hide");
                }

                $("#select-targets-external").removeClass("hide");
                break;
        }

        var distribution_target_option_selected = $("input[name=\"distribution_target_option\"]:checked").attr("id");
        if ($("#" + distribution_target_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_target_option_selected).removeAttr("checked");
        }

        var distribution_assessor_option_selected = $("input[name=\"distribution_assessor_option\"]:checked").attr("id");
        if ($("#" + distribution_assessor_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_assessor_option_selected).removeAttr("checked");
        }
    });

    $("#content").on("change", 'input[name="attempts_scope"]', function () {
        var selected_option = $(this).val();
        switch (selected_option) {
            case "targets" :
                $("#min_target_attempts").prop("disabled", false);
                $("#max_target_attempts").prop("disabled", false);
                $("#min_overall_attempts").prop("disabled", true);
                $("#max_overall_attempts").prop("disabled", true);
                $("#repeat-target-controls").addClass("hide");
            break;
            case "overall" :
                $("#min_target_attempts").prop("disabled", true);
                $("#max_target_attempts").prop("disabled", true);
                $("#min_overall_attempts").prop("disabled", false);
                $("#max_overall_attempts").prop("disabled", false);
                $("#repeat-target-controls").removeClass("hide");
            break;
        }
    });
    
    $("#content").on("click", "#add-external-assessor-btn",  function (e) {
        e.preventDefault();
        $("#autocomplete-list-container .ui-autocomplete").css("display", "none");

        if ($("#assessor-list-internal").hasClass("hide")) {
            $("#assessor-list-internal").removeClass("hide");  
        }
        
        $("#external-assessors-controls").removeClass("hide");

        if ($("#rs-assessor-list-internal").hasClass("hide")) {
            $("#rs-assessor-list-internal").removeClass("hide");
        }

        $("#rs-external-assessors-controls").removeClass("hide");
    });

    $("#content").on("click", "#add-external-eventtype-assessor-btn",  function (e) {
        e.preventDefault();
        $("#eventtype-external-assessors-controls").removeClass("hide");

        if ($("#eventtype-assessor-list-internal").hasClass("hide")) {
            $("#eventtype-assessor-list-internal").removeClass("hide");
        }
    });

    $("#content").on("click", "#rs-add-external-assessor-btn",  function (e) {
        e.preventDefault();
        $("#rs-autocomplete-list-container .ui-autocomplete").css("display", "none");

        if ($("#assessor-list-internal").hasClass("hide")) {
            $("#assessor-list-internal").removeClass("hide");
        }

        $("#external-assessors-controls").removeClass("hide");

        if ($("#rs-assessor-list-internal").hasClass("hide")) {
            $("#rs-assessor-list-internal").removeClass("hide");
        }

        $("#rs-external-assessors-controls").removeClass("hide");
    });
    
    $("#content").on("click", ".remove-assessor-btn", function () {
        $(this).parent().parent().remove();
    });

    /**
     * Clear the form when a new distribution method is selected.
     */
    var previous_selection = $('input[name="distribution_method"].search-target-control').val();
    $("#distribution-method-choice-container").on("change", "input[type=radio]", function () {
        var filter = $(this).data("filter");
        if (filter == "distribution_method") {
            if (previous_selection != $(this).val()) {
                previous_selection = $(this).val();
                distribution_method_changed($(this).val());
            }
        }
    });

    /**
     * Clear steps 3 and 4 when delegation option is changed. Keep the #selected-delegation-option item up-to-date.
     */
    $("input[name=\"distribution_delegator_timeframe\"]").on("change", function (e) {

        var current_selection = $("#selected-delegation-option").val();
        var new_selection = $(this).val();

        if (new_selection != current_selection) {

            delegation_type_changed(new_selection);

            // update tracking item
            $("#selected-delegation-option").val( $(this).val() );
        }
    });

    $("#distribution-load-error").on("click", ".distribution-load-error-msg", function(e) {
        e.preventDefault();
        $("#distribution-load-error").addClass('hide');
    });

    var distribution_id;

    function loadDistributionData(element, clear_form, current_user_id) {
        if (typeof clear_form !== "undefined" && clear_form ) {
            clearDistributionForm();
        }

        if (typeof current_user_id == "undefined") {
            current_user_id = 0;
        }

        var link = jQuery(element);
        distribution_id = link.data("adistribution-id");
        var get_distributions_request = jQuery.ajax({
            url: "?section=api-distributions",
            data: "method=get-distribution-data&adistribution-id=" + distribution_id,
            type: "GET"
        });

        jQuery.when(get_distributions_request).done(function (data) {
            try {
                var jsonResponse = JSON.parse(data);
            }
            catch (e) {
                var jsonResponse = [];
                jsonResponse.status = "error";
            }

            if (jsonResponse.status !== "success") {
                jQuery("#distribution-load-error").removeClass("hide");
            } else if (jsonResponse.status == "success") {
                // populate form fields

                jQuery.each(jsonResponse.data, function(key, value) {
                    var search_array = ["distribution_form"];

                    search_array.push("adistribution_id");
                    if (jQuery.inArray(key, search_array) !== -1) {
                        jQuery(document.createElement("input")).attr({
                            "type": "hidden",
                            "name": key,
                            "id": key,
                            "value": value
                        }).appendTo("#distribution-data-form");
                    }
                });

                jQuery("#distribution-title").val(jsonResponse.data.title);
                jQuery("#distribution-description").val(jsonResponse.data.description);
                if (jsonResponse.data.mandatory == 1) {
                    jQuery("#assessment-mandatory").attr("checked", "checked");
                }

                jQuery("#choose-form-btn").html(jsonResponse.data.form_title + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                var form_id_element = jQuery(document.createElement("input")).attr({
                    "type"  : "hidden",
                    "class" : "search-target-control form_search_target_control form-selector",
                    "name"  : "distribution_form",
                    "id"    : "form_" + jsonResponse.data.distribution_form,
                    "value" : jsonResponse.data.distribution_form,
                    "data-label" : jsonResponse.data.form_title
                }).appendTo("#distribution-data-form");

                var method_settings = jQuery("#choose-method-btn").data("settings");
                method_settings.value = jsonResponse.data.distribution_method;

                jQuery("#choose-method-btn").html(jsonResponse.data.method_name + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                var distribution_method_element = jQuery(document.createElement("input")).attr({
                    "type"  : "hidden",
                    "class" : "search-target-control distribution_method_search_target_control distribution-method-selector",
                    "name"  : "distribution_method",
                    "id"    : "distribution_method_" + jsonResponse.data.distribution_method,
                    "value" : jsonResponse.data.distribution_method,
                    "data-label" : jsonResponse.data.method_name
                }).appendTo("#distribution-data-form");

                jQuery("#choose-course-btn").html(jsonResponse.data.course_name + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                var course_id_element = jQuery(document.createElement("input")).attr({
                    "type"  : "hidden",
                    "class" : "search-target-control rs_course_search_target_control choose-course-selector",
                    "name"  : "course_id",
                    "id"    : "rs_course_" + jsonResponse.data.course_id,
                    "value" : jsonResponse.data.course_id,
                    "data-label" : jsonResponse.data.course_name
                }).appendTo("#distribution-data-form");

                jQuery("#choose-delegator-btn").html((typeof jsonResponse.data.delegator_name != "undefined" && jsonResponse.data.delegator_name ? jsonResponse.data.delegator_name : "Browse Delegators") + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                var delegator_element = jQuery(document.createElement("input")).attr({
                    "type"  : "hidden",
                    "class" : "search-target-control delegator_search_target_control delegator-selector",
                    "name"  : "delegator_id",
                    "id"    : "delegator_" + jsonResponse.data.delegator_id,
                    "value" : jsonResponse.data.delegator_id,
                    "data-label" : jsonResponse.data.delegator_name
                }).appendTo("#distribution-data-form");

                var schedule_label = "Browse Rotation Schedules";
                if (typeof jsonResponse.data.schedule_label !== "undefined") {
                    schedule_label = jsonResponse.data.schedule_label;
                }

                jQuery("#rs-choose-rotation-btn").html(schedule_label + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                //if (jQuery("input[name=\"schedule_id\"]").length == 0)
                {
                    var schedule_element = jQuery(document.createElement("input")).attr({
                        "type": "hidden",
                        "class": "search-target-control rs_schedule_search_target_control rs-rotation-selector",
                        "name": "schedule_id",
                        "id": "rs_schedule_" + jsonResponse.data.schedule_id,
                        "value": jsonResponse.data.schedule_id,
                        "data-label": jsonResponse.data.schedule_label
                    }).appendTo("#distribution-data-form");
                }

                if (typeof jsonResponse.data.schedule_type != "undefined") {
                    if (jsonResponse.data.schedule_type != null) {
                        jQuery("input[name='schedule_delivery_type'][value='"+ jsonResponse.data.schedule_type +"']").attr("checked", "checked");
                        jQuery("#period_offset_days").val(jsonResponse.data.period_offset_days);
                        jQuery("#frequency").val(jsonResponse.data.frequency);
                    }
                }

                jQuery("#distribution-rotation-schedule-options").removeClass("hide");

                if (jQuery("input[name='course_id']").length > 0 && jQuery("input[name='course_id']").val()) {
                    var selected_option = jQuery("input[name=\"course_id\"]").val();
                    var settings = jQuery("#rs-choose-rotation-btn").data("settings");
                    var faculty_settings = jQuery("#choose-targets-faculty-btn").data("settings");

                    if (selected_option !== "0") {
                        jQuery("#rs-rotation-schedule-options").removeClass("hide");
                        settings.filters["rs_schedule"].api_params.course_id = selected_option;
                        faculty_settings.filters["target_faculty"].api_params.course_id = selected_option;
                        var targets_btn_settings = $("#choose-targets-btn").data("settings");
                        targets_btn_settings.filters["target_cgroup"].api_params.course_id = selected_option;
                        var assessors_btn_settings = $("#choose-assessors-btn").data("settings");
                        assessors_btn_settings.filters["assessor_cgroup"].api_params.course_id = selected_option;
                    } else {
                        jQuery("#rs-rotation-schedule-options").addClass("hide");
                    }
                }

                /*
                    @todo: add a case for Learning Event
                 */
                var timeframe = jsonResponse.data.distribution_method;
                if (timeframe == "delegation") {
                    jQuery("#distribution-delegation-warning").removeClass("hide");
                    jQuery("#distribution-delegator-options").removeClass("hide");
                    jQuery("#distribution-delegator-options").removeClass("hide");
                    jQuery("#distribution-specific-date-options").addClass("hide");
                    jQuery("#distribution-rotation-schedule-options").addClass("hide");
                    jQuery("#rs-rotation-schedule-options").addClass("hide");
                    jQuery("#distribution-rotation-delivery-options").addClass("hide");
                    jQuery("#rotation-schedule-delivery-offset").addClass("hide");

                    timeframe = jsonResponse.data.distribution_delegator_timeframe;
                    jQuery('#selected-delegation-option').val(timeframe);

                    if (timeframe == "rotation_schedule") {
                        jQuery("#delegator_timeframe_rotation_schedule").attr('checked', 'checked');
                        if ($("input[name=\"cperiod_id\"]").length) {
                            var cperiod_id = $("input[name=\"cperiod_id\"]").val();
                            var settings = $("#rs-choose-rotation-btn").data("settings");
                            settings.filters["rs_schedule"].api_params.cperiod_id = cperiod_id;
                        }
                    } else {
                        jQuery("#delegator_timeframe_date_range").attr('checked', 'checked');
                    }
                } else {
                    jQuery("#distribution-delegator-options").addClass("hide");
                    jQuery("#distribution-delegator-options input[type='radio']").prop("checked", false);
                }

                if (jsonResponse.data.feedback_required == "1") {
                    jQuery("#feedback-required").attr("checked", "checked");
                }
                if (jsonResponse.data.distribution_method == "eventtype") {
                    $("#select-target-options").addClass("hide");
                }
                switch (jsonResponse.data.target_option) {
                    case "all" :
                        jQuery("#all-targets-btn").attr("checked", "checked");
                        break;
                    case "non_cbme" :
                        jQuery("#non-cbme-targets-btn").attr("checked", "checked");
                        break;
                    case "only_cbme":
                        jQuery("#cbme-targets-btn").attr("checked", "checked");
                        break;
                }

                switch (timeframe) {
                    case "date_range" :
                        $("#distribution_start_date").datepicker("setDate", new Date(jsonResponse.data.range_start_date * 1000));
                        $("#distribution_end_date").datepicker("setDate", new Date(jsonResponse.data.range_end_date * 1000));
                        $("#distribution_delivery_date").datepicker("setDate", jsonResponse.data.delivery_date);
                        resetExpiryControls("date", true);
                        if (jsonResponse.data.expiry_date) {
                            $("#expiry-date").datepicker("setDate", jsonResponse.data.expiry_date);
                        }

                        var start_date = new Date(jsonResponse.data.range_start_date * 1000);
                        var start_hours = (start_date.getHours() < 10 ? "0" + start_date.getHours() : start_date.getHours());
                        var start_minutes = (start_date.getMinutes() < 10 ? "0" + start_date.getMinutes() : start_date.getMinutes());
                        $("#distribution_start_time").val(start_hours + ":" + start_minutes);

                        var end_date = new Date(jsonResponse.data.range_end_date * 1000);
                        var end_hours = (end_date.getHours() < 10 ? "0" + end_date.getHours() : end_date.getHours());
                        var end_minutes = (end_date.getMinutes() < 10 ? "0" + end_date.getMinutes() : end_date.getMinutes());
                        $("#distribution_end_time").val(end_hours + ":" + end_minutes);

                        jQuery("#specific_dates_assessor_options").removeClass("hide");
                        jQuery("#distribution-specific-date-options").removeClass("hide");
                        jQuery("#distribution-rotation-schedule-options").addClass("hide");
                        jQuery("#distribution-rotation-delivery-options").addClass("hide");
                        jQuery("#rotation-schedule-delivery-offset").addClass("hide");
                        if (jsonResponse.data.distribution_assessor_option == "faculty") {
                            jQuery("#distribution-assessor-faculty").attr("checked", "checked");
                            jQuery("#select-assessors-grouped").addClass("hide");
                            jQuery("#select-assessors-individual").addClass("hide");
                            jQuery("#select-assessors-faculty").removeClass("hide");
                        } else if (jsonResponse.data.assessor_type == "proxy_id" || jsonResponse.data.assessor_type == "external_hash") {
                            jQuery("#distribution-assessor-external").attr("checked", "checked");
                            jQuery("#select-assessors-grouped").addClass("hide");
                            jQuery("#select-assessors-faculty").addClass("hide");
                            jQuery("#select-assessors-individual").removeClass("hide");
                        } else {
                            jQuery("#distribution-assessor-internal").attr("checked", "checked");
                            jQuery("#select-assessors-individual").addClass("hide");
                            jQuery("#select-assessors-faculty").addClass("hide");
                            jQuery("#select-assessors-grouped").removeClass("hide");
                        }
                        jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                            if (jsonResponse.data.distribution_assessor_option == "faculty") {
                                var assessor_element = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "class": "search-target-control assessor_faculty_search_target_control",
                                    "name": "assessor_faculty[]",
                                    "id": "assessor_faculty_" + value.assessor_value,
                                    "value": value.assessor_value,
                                    "data-label": value.assessor_name
                                }).appendTo("#distribution-data-form");
                            } else if (value.assessor_type == "proxy_id") {
                                build_selected_assessor_item(value.assessor_value, value.assessor_name, "Internal");
                            } else if (value.assessor_type == "external_hash") {
                                build_selected_assessor_item(value.assessor_value, value.assessor_name, "External");
                            } else if (value.assessor_type == "course_id") {
                                jQuery("#choose-assessors-btn").html('<span class="selected-filter-label">Course Audience</span>' + value.assessor_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>');
                                var learner_group_assessor_element = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "class": "search-target-control assessor_course_audience_search_target_control assessor-audience-selector",
                                    "name": "assessor_course_id",
                                    "id": "assessor_course_audience_" + value.assessor_value,
                                    "value": value.assessor_value,
                                    "data-label": value.assessor_name
                                }).appendTo("#distribution-data-form");
                            } else if (value.assessor_type == "group_id") {
                                jQuery("#choose-assessors-btn").html('<span class="selected-filter-label">Cohort</span>' + value.assessor_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>');
                                var learner_group_assessor_element = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "class": "search-target-control assessor_cohort_search_target_control assessor-audience-selector",
                                    "name": "assessor_cohort_id",
                                    "id": "assessor_cohort_" + value.assessor_value,
                                    "value": value.assessor_value,
                                    "data-label": value.assessor_name
                                }).appendTo("#distribution-data-form");
                            } else if (value.assessor_type == "cgroup_id") {
                                jQuery("#choose-assessors-btn").html('<span class="selected-filter-label">Course Group</span>' + value.assessor_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>');
                                var learner_cgroup_assessor_element = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "class": "search-target-control assessor_cgroup_search_target_control assessor-audience-selector",
                                    "name": "assessor_cgroup_id",
                                    "id": "assessor_cgroup_" + value.assessor_value,
                                    "value": value.assessor_value,
                                    "data-label": value.assessor_name
                                }).appendTo("#distribution-data-form");
                            }
                        });
                        var previous_settings = $("#choose-assessors-faculty-btn").data("settings");
                        previous_settings.build_list();

                        jQuery("#select-targets-grouped").addClass("hide");
                        jQuery("#select-targets-faculty").addClass("hide");
                        jQuery("#select-targets-course").addClass("hide");
                        jQuery("#select-target-options").addClass("hide");
                        if (jsonResponse.data.target_type == "self") {
                            jQuery("#distribution-target-self").attr("checked", "checked");
                        } else if (jsonResponse.data.target_role == "learner") {
                            jQuery("#distribution-target-internal").attr("checked", "checked");
                            jQuery("#select-targets-grouped").removeClass("hide");
                            if (cbme == true) {
                                jQuery("#select-target-options").removeClass("hide");
                            }
                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                if (value.target_type == "course_id") {
                                    jQuery("#choose-targets-btn").html('<span class="selected-filter-label">Course Audience</span>' + value.target_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>');
                                    var learner_group_target_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_course_audience_search_target_control target-audience-selector",
                                        "name": "target_course_audience_id",
                                        "id": "target_course_audience_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                } else if (value.target_type == "group_id") {
                                    jQuery("#choose-targets-btn").html('<span class="selected-filter-label">Cohort</span>' + value.target_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>');
                                    var learner_group_target_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_cohort_search_target_control target-audience-selector",
                                        "name": "target_cohort_id",
                                        "id": "target_cohort_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                } else if (value.target_type == "cgroup_id") {
                                    jQuery("#choose-targets-btn").html('<span class="selected-filter-label">Course Group</span>' + value.target_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>');
                                    var learner_cgroup_target_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_cgroup_search_target_control target-audience-selector",
                                        "name": "target_cgroup_id",
                                        "id": "target_cgroup_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                } else if (value.target_type == "proxy_id"){
                                    var learner_individual_target_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_individual_search_target_control target-audience-selector",
                                        "name": "target_individual[]",
                                        "id": "target_individual_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");

                                    if (jsonResponse.data.selected_internal_targets.length - 1 == key) {
                                        var previous_settings = $("#choose-targets-btn").data("settings");
                                        previous_settings.build_list();
                                    }
                                }
                            });
                        } else if (jsonResponse.data.target_role == "faculty") {
                            var rebuild_proxy_id = false;
                            var rebuild_external_hash = false;

                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                if (value.target_type == "proxy_id") {
                                    var faculty_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_faculty_search_target_control",
                                        "name": "target_faculty[]",
                                        "id": "target_faculty_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                    rebuild_proxy_id = true;
                                } else if (value.target_type == "external_hash") {
                                    var learner_external_target_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control target_external_search_target_control",
                                        "name": "target_external[]",
                                        "id": "target_external_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                    rebuild_external_hash = true;
                                }
                            });

                            if (rebuild_proxy_id) {
                                jQuery("#distribution-target-faculty").attr("checked", "checked");
                                jQuery("#select-targets-faculty").removeClass("hide");

                                var previous_settings = $("#choose-targets-faculty-btn").data("settings");
                                previous_settings.build_list();
                            } else if (rebuild_external_hash) {
                                jQuery("#distribution-target-external").attr("checked", "checked");
                                jQuery("#select-targets-external").removeClass("hide");

                                var previous_settings = $("#choose-target-external-btn").data("settings");
                                previous_settings.build_list();
                            }
                        } else if (jsonResponse.data.target_type == "proxy_id" && jsonResponse.data.target_role == "any") {
                            jQuery("#distribution-target-individual-users").attr("checked", "checked");
                            jQuery("#select-targets-individual").removeClass("hide");
                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                build_selected_target_item(value.target_id, value.target_name, value.group + " / " + value.role);
                            });
                        } else if (jsonResponse.data.target_type == "course_id" && jsonResponse.data.target_type) {
                            jQuery("#distribution-target-course").attr("checked", "checked");
                            jQuery("#select-targets-course").removeClass("hide");
                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                jQuery("#choose-target-course-btn").html(value.target_name + '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>');
                                var course_target_element = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "class": "search-target-control target_course_search_target_control choose-target-course-selector",
                                    "name": "target_course_id",
                                    "id": "target_course_" + value.target_id,
                                    "value": value.target_id,
                                    "data-label": value.target_name
                                }).appendTo("#distribution-data-form");
                            });
                        }
                        break;
                    case "rotation_schedule" :
                        jQuery("#rotation_schedule_assessor_options").removeClass("hide");
                        showScheduleOptions();
                        if (jsonResponse.data.delivery_period) {
                            jQuery("#delivery_period option[value='" + jsonResponse.data.delivery_period + "']").attr("selected", true);
                        }

                        if (typeof jsonResponse.data.schedule_delivery_type != "undefined" && jsonResponse.data.schedule_delivery_type == "repeat") {
                            jQuery("#timeline-option-once-per").addClass("hide");
                            jQuery("#timeline-option-repeat").removeClass("hide");
                        } else {
                            jQuery("#timeline-option-repeat").addClass("hide");
                            jQuery("#timeline-option-once-per").removeClass("hide");
                        }

                        if (jsonResponse.data.release_date !== null) {
                            jQuery("input[name=\"rotation_release_option\"]").prop("checked", true);
                            jQuery('#rotation-release-control').css("display", "inline-block");
                            jQuery("input[name=\"rotation_release_date\"]").val(jsonResponse.data.release_date);
                        }

                        var assessor_option;
                        if (typeof jsonResponse.data.assessor_option != "undefined") {
                            assessor_option = jsonResponse.data.assessor_option;
                        } else {
                            assessor_option = jsonResponse.data.distribution_rs_assessor_option;
                        }

                        // Set up assessor wizard step options
                        var individual_assessors = false;
                        jQuery("#distribution-specific-date-options").addClass("hide");
                        if (assessor_option == "faculty") {
                            jQuery("#distribution-rs-assessor-faculty").attr("checked", "checked");
                            jQuery("#rs-assessor-learner-options").addClass("hide");
                            jQuery("#rs-assessor-faculty-options").removeClass("hide");
                            jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                                if (value.assessor_type == "proxy_id") {
                                    var learner_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control additional_assessor_faculty_search_target_control",
                                        "name"  : "additional_assessor_faculty[]",
                                        "id"    : "additional_assessor_faculty_" + value.assessor_value,
                                        "value" : value.assessor_value,
                                        "data-label" : value.assessor_name
                                    }).appendTo("#distribution-data-form");
                                }
                            });
                            var previous_settings = $("#choose-assessors-rs-faculty").data("settings");
                            previous_settings.build_list();
                        } else if (assessor_option == "learner") {
                            jQuery("#distribution-rs-assessor-learner").attr("checked", "checked");
                            jQuery("#rs-assessor-faculty-options").addClass("hide");
                            jQuery("#rs-assessor-learner-options").removeClass("hide");
                            if (typeof jsonResponse.data.all_learner_assessor_mode != "undefined" && jsonResponse.data.all_learner_assessor_mode) {
                                jQuery("#distribution-rs-assessor-all").attr("checked", "checked");
                                jQuery("#rs-assessor-learner-individual").addClass("hide");
                                jQuery("#rs-assessor-learner-service").removeClass("hide");
                                jQuery("#rs-additional-learners").removeClass("hide");
                            } else { // individuals
                                jQuery("#distribution-rs-assessor-individual").attr("checked", "checked");
                                individual_assessors = true;
                                jQuery("#distribution-rs-additional-learners").removeAttr("checked"); // new
                                jQuery("#rs-assessor-learner-service").addClass("hide");
                                jQuery("#rs-assessor-learner-individual").removeClass("hide");
                                jQuery("#rs-individual-learners").addClass("hide");
                            }

                            jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                                if (value.assessor_scope == "all_learners") {
                                    jQuery("#distribution-rs-onservice").attr("checked", "checked");
                                    jQuery("#distribution-rs-offservice").attr("checked", "checked");
                                } else if (value.assessor_scope == "internal_learners") {
                                    jQuery("#distribution-rs-onservice").attr("checked", "checked");
                                } else if (value.assessor_scope == "external_learners") {
                                    jQuery("#distribution-rs-offservice").attr("checked", "checked");
                                } else if (individual_assessors == true) {
                                    jQuery("#distribution-rs-additional-learners").removeAttr("checked", "checked");
                                    var learner_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control individual_assessor_learners_search_target_control",
                                        "name"  : "individual_assessor_learners[]",
                                        "id"    : "individual_assessor_learners_" + value.assessor_value,
                                        "value" : value.assessor_value,
                                        "data-label" : value.assessor_name
                                    }).appendTo("#distribution-data-form");
                                }
                                else if (value.assessor_type == "proxy_id") {
                                    jQuery("#distribution-rs-additional-learners").attr("checked", "checked");
                                    jQuery("#rs-individual-learners").removeClass("hide");
                                    var learner_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control additional_assessor_learners_search_target_control",
                                        "name"  : "additional_assessor_learners[]",
                                        "id"    : "additional_assessor_learners_" + value.assessor_value,
                                        "value" : value.assessor_value,
                                        "data-label" : value.assessor_name
                                    }).appendTo("#distribution-data-form");
                                }
                            });
                            var previous_settings = $("#choose-assessors-rs-individual-btn").data("settings");
                            previous_settings.build_list();

                            var previous_settings = $("#choose-assessors-rs-additional-learners").data("settings");
                            previous_settings.build_list();
                        } else if (assessor_option == "individual_users") {
                            jQuery("#distribution-rs-assessor-external").attr("checked", "checked");
                            jQuery("#rs-assessor-faculty-options").addClass("hide");
                            jQuery("#rs-assessor-learner-options").addClass("hide");
                            jQuery("#rs-select-assessors-individual").removeClass("hide");
                            jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                                if (value.assessor_type == "proxy_id") {
                                    build_selected_assessor_item(value.assessor_value, value.assessor_name, "Internal");
                                } else if (value.assessor_type == "external_hash") {
                                    build_selected_assessor_item(value.assessor_value, value.assessor_name, "External");
                                }
                            });
                        }

                        // Set targets wizard step options
                        var individual_targets = false;
                        jQuery("#rs-target-learner-options").addClass("hide");
                        jQuery("#rs-target-faculty-options").addClass("hide");
                        if (jsonResponse.data.target_type == "self") {
                            jQuery("#distribution-rs-target-self").attr("checked", "checked");
                        } else if (jsonResponse.data.target_role == "learner") {
                            jQuery("#distribution-rs-target-learner").attr("checked", "checked");
                            jQuery("#rs-target-learner-options").removeClass("hide");
                            if (cbme == true) {
                                jQuery("#select-target-options").removeClass("hide");
                            }

                            if (typeof jsonResponse.data.all_learner_target_mode != "undefined" && jsonResponse.data.all_learner_target_mode) {
                                jQuery("#distribution-rs-target-all").attr("checked", "checked");
                                jQuery("#rs-target-learner-individual").addClass("hide");
                                jQuery("#rs-target-learner-service").removeClass("hide");
                                jQuery("#rs-target-additional-learners").removeClass("hide");
                            } else {
                                individual_targets = true;
                                jQuery("#distribution-rs-target-individual").attr("checked", "checked");
                                jQuery("#distribution-rs-target-additional-learners").removeAttr("checked");
                                jQuery("#rs-target-learner-service").addClass("hide");
                                jQuery("#rs-target-learner-individual").removeClass("hide");
                                jQuery("#rs-target-individual-learners").addClass("hide");
                            }

                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                if (value.target_scope == "all_learners") {
                                    jQuery("#distribution-rs-target-onservice").attr("checked", "checked");
                                    jQuery("#distribution-rs-target-offservice").attr("checked", "checked");
                                } else if (value.target_scope == "internal_learners") {
                                    jQuery("#distribution-rs-target-onservice").attr("checked", "checked");
                                } else if (value.target_scope == "external_learners") {
                                    jQuery("#distribution-rs-target-offservice").attr("checked", "checked");
                                } else if (individual_targets == true) {
                                    var learner_target_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control individual_target_learner_search_target_control",
                                        "name"  : "individual_target_learner[]",
                                        "id"    : "individual_target_learner_" + value.target_id,
                                        "value" : value.target_id,
                                        "data-label" : value.target_name
                                    }).appendTo("#distribution-data-form");
                                } else if (value.target_type == "proxy_id") {
                                    jQuery("#distribution-rs-target-additional-learners").attr("checked", "checked");
                                    jQuery("#rs-target-individual-learners").removeClass("hide");
                                    var learner_target_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control additional_target_learners_search_target_control",
                                        "name"  : "additional_target_learners[]",
                                        "id"    : "additional_target_learners_" + value.target_id,
                                        "value" : value.target_id,
                                        "data-label" : value.target_name
                                    }).appendTo("#distribution-data-form");
                                }
                            });
                            var previous_settings = $("#choose-targets-rs-individual-btn").data("settings");
                            previous_settings.build_list();

                            var previous_settings = $("#choose-targets-rs-additional-learners").data("settings");
                            previous_settings.build_list();
                        } else if (jsonResponse.data.target_role == "faculty") {
                            var rebuild_proxy_id = false;
                            var rebuild_external_hash = false;

                            jQuery.each(jsonResponse.data.selected_internal_targets, function (key, value) {
                                if (value.target_type == "proxy_id") {
                                    var faculty_element = jQuery(document.createElement("input")).attr({
                                        "type"  : "hidden",
                                        "class" : "search-target-control additional_target_faculty_search_target_control",
                                        "name"  : "additional_target_faculty[]",
                                        "id"    : "additional_target_faculty_" + value.target_id,
                                        "value" : value.target_id,
                                        "data-label" : value.target_name
                                    }).appendTo("#distribution-data-form");
                                    rebuild_proxy_id = true;
                                } else if (value.target_type == "external_hash") {
                                    var faculty_external_element = jQuery(document.createElement("input")).attr({
                                        "type": "hidden",
                                        "class": "search-target-control rs_target_external_search_target_control",
                                        "name": "rs_target_external[]",
                                        "id": "rs_target_external_" + value.target_id,
                                        "value": value.target_id,
                                        "data-label": value.target_name
                                    }).appendTo("#distribution-data-form");
                                    rebuild_external_hash = true;
                                }
                            });

                            if (rebuild_proxy_id) {
                                jQuery("#distribution-rs-target-faculty").attr("checked", "checked");
                                jQuery("#rs-target-faculty-options").removeClass("hide");

                                var previous_settings = $("#choose-targets-rs-faculty").data("settings");
                                previous_settings.build_list();
                            } else if (rebuild_external_hash) {
                                jQuery("#distribution-rs-target-external").attr("checked", "checked");
                                jQuery("#rs-target-external-options").removeClass("hide");

                                var previous_settings = $("#choose-targets-rs-external").data("settings");
                                previous_settings.build_list();
                            }
                        } else if (jsonResponse.data.target_type == "schedule_id") {
                            jQuery("#distribution-rs-target-block").attr("checked", "checked");
                        }

                        if ($("input[name=\"cperiod_id\"]").length) {
                            var cperiod_id = $("input[name=\"cperiod_id\"]").val();
                            var settings = $("#rs-choose-rotation-btn").data("settings");
                            settings.filters["rs_schedule"].api_params.cperiod_id = cperiod_id;
                        }
                        break;
                    case "eventtype":
                        jQuery("#distribution-rotation-schedule-options").addClass("hide");
                        jQuery("#distribution-rotation-delivery-options").addClass("hide");
                        jQuery("#rotation_schedule_target_options").addClass("hide");
                        jQuery("#specific_dates_target_options").addClass("hide");
                        jQuery("#distribution-eventtype-options").removeClass("hide");

                        if (jsonResponse.data.release_date !== null) {
                            jQuery("input[name=\"eventtype_release_option\"]").prop("checked", true);
                            jQuery("input[name=\"eventtype_release_date\"]").val(jsonResponse.data.release_date);
                        }

                        jQuery.each(jsonResponse.data.eventtypes, function (key, eventtype) {
                            var eventtype_element = jQuery(document.createElement("input")).attr({
                                "type"  : "hidden",
                                "class" : "search-target-control eventtypes_search_target_control",
                                "name"  : "eventtypes[]",
                                "id"    : "eventtypes_" + eventtype.target_id,
                                "value" : eventtype.target_id,
                                "data-label" : eventtype.target_name
                            }).appendTo("#distribution-data-form");
                        });

                        var previous_settings = $("#choose-targets-eventtype").data("settings");
                        previous_settings.build_list();

                        switch (jsonResponse.data.target_role) {
                            case "learner" :
                                jQuery("#distribution-eventtype-target-eventtype").prop("checked", true);
                            break;
                            case "faculty" :
                                jQuery("#distribution-eventtype-target-faculty").prop("checked", true);
                            break;
                            case "any" :
                                jQuery("#distribution-eventtype-target-event").prop("checked", true);
                            break;
                        }

                        switch (jsonResponse.data.distribution_eventtype_assessor_option) {
                            case "faculty" :
                                jQuery("#distribution-eventtype-assessor-faculty").prop("checked", true);
                                //jQuery("#eventtype-assessor-faculty-options").removeClass("hide");
                                /*
                                jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                                    if (value.assessor_type == "proxy_id") {
                                        var assessor_element = jQuery(document.createElement("input")).attr({
                                            "type"  : "hidden",
                                            "class" : "search-target-control additional_assessor_eventtype_faculty_search_target_control",
                                            "name"  : "additional_assessor_eventtype_faculty[]",
                                            "id"    : "additional_assessor_eventtype_faculty_" + value.assessor_value,
                                            "value" : value.assessor_value,
                                            "data-label" : value.assessor_name
                                        }).appendTo("#distribution-data-form");
                                    }
                                });
                                */
                            break;
                            case "learner" :
                                var assessor_scope = jsonResponse.data.selected_internal_assessors[0].assessor_scope;
                                jQuery("#distribution-eventtype-assessor-learner").prop("checked", true);
                                jQuery("#eventtype-assessor-learner-options").removeClass("hide");

                                switch (assessor_scope) {
                                    case "attended_learners" :
                                        jQuery("#distribution-eventtype-learners-attended").prop("checked", true);
                                    break;
                                    case "all_learners" :
                                        jQuery("#distribution-eventtype-learners").prop("checked", true);
                                    break;
                                }
                            break;
                            case "individual_users" :
                                jQuery("#distribution-eventtype-assessor-external").prop("checked", true);
                                jQuery("#eventtype-select-assessors-individual").removeClass("hide");
                                jQuery.each(jsonResponse.data.selected_internal_assessors, function (key, value) {
                                    if (value.assessor_type == "proxy_id") {
                                        build_selected_eventtype_assessor_item(value.assessor_value, value.assessor_name, "Internal");
                                    } else if (value.assessor_type == "external_hash") {
                                        build_selected_eventtype_assessor_item(value.assessor_value, value.assessor_name, "External");
                                    }
                                });
                            break;
                        }
                        break;
                }

                //jQuery("#rs-choose-rotation-btn").trigger("change"); // update API params

                var settings = jQuery("#choose-assessors-faculty-btn").data("settings");
                settings.filters["assessor_faculty"].api_params.course_id = jsonResponse.data.course_id;
                var faculty_settings = jQuery("#choose-targets-faculty-btn").data("settings");
                faculty_settings.filters["target_faculty"].api_params.course_id = jsonResponse.data.course_id;
                var approver_settings = jQuery("#distribution-approver-results").data("settings");
                approver_settings.filters["distribution_approvers"].api_params.course_id = jsonResponse.data.course_id;
                var targets_btn_settings = jQuery("#choose-targets-btn").data("settings");
                targets_btn_settings.filters["target_individual"].api_params.course_id = jsonResponse.data.course_id;
                var assessors_btn_settings = jQuery("#choose-assessors-btn").data("settings");
                assessors_btn_settings.filters["assessor_individual"].api_params.course_id = jsonResponse.data.course_id;

                if (parseInt(jsonResponse.data.submittable_by_target) == 1) {
                    jQuery("#attempts-scope-targets").attr("checked", "checked");
                    jQuery("#attempts-scope-overall").attr("checked", false);
                    jQuery("#min_target_attempts").prop("disabled", false);
                    jQuery("#max_target_attempts").prop("disabled", false);
                    jQuery("#min_overall_attempts").prop("disabled", true);
                    jQuery("#max_overall_attempts").prop("disabled", true);
                    jQuery("#repeat-target-controls").addClass("hide");
                } else {
                    jQuery("#attempts-scope-overall").attr("checked", "checked");
                    jQuery("#attempts-scope-targets").attr("checked", false);
                    jQuery("#min_target_attempts").prop("disabled", true);
                    jQuery("#max_target_attempts").prop("disabled", true);
                    jQuery("#min_overall_attempts").prop("disabled", false);
                    jQuery("#max_overall_attempts").prop("disabled", false);
                    jQuery("#repeat-target-controls").removeClass("hide");
                    if (parseInt(jsonResponse.data.repeat_targets)) {
                        jQuery("#repeat-targets").attr("checked", "checked");
                    }
                }

                if (jsonResponse.data.min_target_attempts) {
                    jQuery("#min_target_attempts").val(jsonResponse.data.min_target_attempts);
                }
                if (jsonResponse.data.min_overall_attempts) {
                    jQuery("#min_overall_attempts").val(jsonResponse.data.min_overall_attempts);
                }
                if (jsonResponse.data.max_target_attempts) {
                    jQuery("#max_target_attempts").val(jsonResponse.data.max_target_attempts);
                }
                if (jsonResponse.data.max_overall_attempts) {
                    jQuery("#max_overall_attempts").val(jsonResponse.data.max_overall_attempts);
                }

                if (timeframe == "date_range" && $("#assessment_type").val() == "assessment") {
                    jQuery("#exclude_self_assessment_options").removeClass("hide");
                    if (jsonResponse.data.exclude_self_assessments == 1) {
                        jQuery("#exclude_self_assessments").attr("checked", "checked");
                    }
                } else {
                    jQuery("#exclude_self_assessment_options").addClass("hide");
                    jQuery("#exclude_self_assessments").attr("checked", false);
                }

                if (typeof jsonResponse.data.distribution_results_user != "undefined" && jsonResponse.data.distribution_results_user.length > 0) {
                    toggleReviewControls(true);
                    // jQuery("#reviewer-release-options").show(); // not supported yet

                    jQuery.each(jsonResponse.data.distribution_results_user, function (key, value) {
                        var reviewer_element = jQuery(document.createElement("input")).attr({
                            "type"  : "hidden",
                            "class" : "search-target-control distribution_results_user_search_target_control",
                            "name"  : "distribution_results_user[]",
                            "id"    : "distribution_results_user_" + value.proxy_id,
                            "value" : value.proxy_id,
                            "data-label" : value.reviewer_name
                        }).appendTo("#distribution-data-form");
                    });

                    var previous_settings = $("#distribution-review-results").data("settings");
                    previous_settings.build_list();
                }

                jQuery("#accordion-approver-container").removeClass("hide");

                if (typeof jsonResponse.data.distribution_approvers != "undefined" && jsonResponse.data.distribution_approvers.length > 0) {
                    jQuery("#approver-required").attr("checked", "checked");
                    jQuery("#select-approvers").removeClass("hide");

                    jQuery.each(jsonResponse.data.distribution_approvers, function (key, value) {
                        var reviewer_element = jQuery(document.createElement("input")).attr({
                            "type"  : "hidden",
                            "class" : "search-target-control distribution_approvers_search_target_control",
                            "name"  : "distribution_approvers[]",
                            "id"    : "distribution_approvers_" + value.proxy_id,
                            "value" : value.proxy_id,
                            "data-id"    : value.proxy_id,
                            "data-label" : value.approver_name,
                            "data-filter": "distribution_approvers"
                        }).appendTo("#distribution-data-form");
                    });
                    var previous_settings = $("#distribution-approver-results").data("settings");
                    previous_settings.build_list();
                }

                build_author_autocomplete();

                if (jsonResponse.data.selected_authors != "undefined") {
                    var type;
                    jQuery.each(jsonResponse.data.selected_authors, function (key, value) {
                        switch (value.author_type) {
                            case "proxy_id" :
                                type = "individual";
                                break;
                            case "course_id" :
                                type = "course";
                                break;
                            case "organisation_id" :
                                type = "organisation";
                                break;
                        }
                        build_selected_author_item(value.author_id, value.author_name, type, (current_user_id == value.author_id && type == "individual")?false:true);
                    });
                }

                if (jsonResponse.data.flagging_notifications != "undefined") {
                    jQuery("#flagging_notifications").val(jsonResponse.data.flagging_notifications);
                }

                if (jsonResponse.data.expiry_offset !== null) {
                    if (timeframe != "date_range") {
                        jQuery("input[name=\"expiry_option\"]").prop("checked", true);
                        jQuery('#expiry-control').css("display", "inline-block");
                        jQuery("input[name=\"expiry_days\"]").val(jsonResponse.data.expiry_days);
                        jQuery("input[name=\"expiry_hours\"]").val(jsonResponse.data.expiry_hours);
                        jQuery("#expiry-notification-option-controls").removeClass("hide");
                    }

                    if (jsonResponse.data.expiry_notification_offset !== null) {
                        jQuery("input[name=\"expiry_notification_option\"]").prop("checked", true);
                        jQuery('#expiry-notification-control').css("display", "inline-block");
                        jQuery("input[name=\"expiry_notification_days\"]").val(jsonResponse.data.expiry_notification_days);
                        jQuery("input[name=\"expiry_notification_hours\"]").val(jsonResponse.data.expiry_notification_hours);
                    }
                }

                reset_wizard_step_2_visibility(timeframe);

                /**
                 * Target task release
                 **/
                if (jsonResponse.data.distribution_target_task_release) {
                    $("input[name=target-task-release-option]").each(function (i, v) {
                        if ($(v).val() == jsonResponse.data.distribution_target_task_release) {
                            $(v).attr("checked", true);
                        } else {
                            $(v).attr("checked", false);
                        }
                    });
                    if (jsonResponse.data.distribution_target_task_release_threshold_option) {
                        $("input[name=target-task-release-threshold-option]").each(function (i, v) {
                            if ($(v).val() == jsonResponse.data.distribution_target_task_release_threshold_option) {
                                $(v).attr("checked", true);
                            } else {
                                $(v).attr("checked", false);
                            }
                        });
                        if ($("input[name=target-task-release-option]:checked").val() == "threshold") {
                            if ($("#assessment_type").val() == "assessment") {
                                $("#target-task-release-threshold-controls").removeClass("hide");
                            }
                            if (jsonResponse.data.distribution_target_task_release_threshold_percentage) {
                                $("#target-task-release-threshold-option-unique-percentage").val(jsonResponse.data.distribution_target_task_release_threshold_percentage);
                            }
                        } else {
                            $("#target-task-release-threshold-controls").addClass("hide");
                        }
                    }
                } else {
                    update_target_release_controls("task");
                }

                /**
                 * Target report release
                 **/
                if (jsonResponse.data.distribution_target_report_release) {
                    $("input[name=target-report-release-option]").each(function (i, v) {
                        if ($(v).val() == jsonResponse.data.distribution_target_report_release) {
                            $(v).attr("checked", true);
                        } else {
                            $(v).attr("checked", false);
                        }
                    });
                    if (jsonResponse.data.distribution_target_report_release_threshold_option) {
                        $("input[name=target-report-release-threshold-option]").each(function (i, v) {
                            if ($(v).val() == jsonResponse.data.distribution_target_report_release_threshold_option) {
                                $(v).attr("checked", true);
                            } else {
                                $(v).attr("checked", false);
                            }
                        });
                        if ($("input[name=target-report-release-option]:checked").val() == "threshold") {
                            if ($("#assessment_type").val() == "assessment") {
                                $("#target-report-release-threshold-controls").removeClass("hide");
                            }
                            if (jsonResponse.data.distribution_target_report_release_threshold_percentage) {
                                $("#target-report-release-threshold-option-unique-percentage").val(jsonResponse.data.distribution_target_report_release_threshold_percentage);
                            }
                        } else {
                            $("#target-report-release-threshold-controls").addClass("hide");
                        }
                    }
                    if (jsonResponse.data.distribution_target_report_comment_options) {
                        if ($("#assessment_type").val() == "assessment") {
                            $("#accordion-target-report-container").removeClass("hide");
                        }
                        $("input[name=target-report-comments-option]").each(function (i, v) {
                            if ($(v).val() == jsonResponse.data.distribution_target_report_comment_options) {
                                $(v).attr("checked", true);
                            } else {
                                $(v).attr("checked", false);
                            }
                        });
                    }
                } else {
                    update_target_release_controls("report");
                }
            }
        });
    }

    function toggleReviewControls(show) {
        if (show) {
            $("#review-options").removeClass("hide");
        } else {
            $("#review-options").addClass("hide");
        }
    }

    function clearDistributionForm() {
        $("#distribution-title, #distribution-description").val("");
        $("#assessment-mandatory").removeProp("checked");
        $("#choose-form-btn").html("Browse Forms <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $("#choose-method-btn").html("Browse Distribution Methods <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $("#choose-course-btn").html("Browse Courses <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $("#rs-choose-rotation-btn").html("Browse Rotation Schedules <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $("#distribution-rotation-schedule-options, #rs-assessor-faculty-options, #rs-target-learner-options, #rs-target-learner-service, #rs-rotation-schedule-options, #distribution-rotation-delivery-options, #rotation-schedule-delivery-offset").addClass("hide");
    }

    $(".add-on").on("click", function () {
        $(this).prev("input").focus();
    });
    
    $("#distribution-next-step").on("click", function () {
        distribution_next_step();
    });
    
    $("#distribution-previous-step").on("click", function () {
        distribution_previous_step();
    });
    
    $("#content").on("click", ".remove-selected-assessor", function () {
        var assessor_id = $(this).parent().parent().data("id");
        $(this).parent().parent().remove();
        $("#selected-internal-assessor-" + assessor_id).remove();
        
        if ($(".internal-assessor-list-item").length === 0) {
            $("#assessor-list-internal").addClass("hide");
        }
    });

    $("#content").on("click", ".remove-selected-target", function () {
        var target_id = $(this).parent().parent().data("id");

        $(this).parent().parent().remove();
        $("#selected-internal-target-" + target_id).remove();

        if ($(".internal-target-list-item").length === 0) {
            $("#target-list-internal").addClass("hide");
        }
    });

    $("#content").on("click", ".remove-selected-author", function () {
        var author_id = $(this).parent().parent().data("id");
        var author_type = $(this).parent().parent().data("author-type");
        var get_current_id_request = jQuery.ajax({
            url: "?section=api-distributions",
            data: "method=get-current-user-data",
            type: "GET"
        });

        jQuery.when(get_current_id_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {
                if (author_id == jsonResponse.current_id && author_type == "individual") {
                    display_error(["You cannot remove yourself."], "#msgs", "prepend"); // The user tried to remove him or herself from the author list
                } else {
                    $(this).parent().parent().remove();
                    $(".selected-" + author_type + "-author-" + author_id).remove();
                    $("#selected-" + author_type + "-author-" + author_id).remove();

                    if ($(".authors-list-item").length === 0) {
                        $("#author-list-section").addClass("hide");
                    }
                }
            }
        });
    });
    
    $("#cancel-assessor-btn").on("click", function (e) {
        e.preventDefault();
        $("#external-assessors-controls").addClass("hide");
        $("#msgs").empty();
        
        if ($(".internal-assessor-list-item").length === 0) {
            $("#assessor-list-internal").addClass("hide");
        }

        $("#rs-external-assessors-controls").addClass("hide");

        if ($(".rs-internal-assessor-list-item").length === 0) {
            $("#rs-assessor-list-internal").addClass("hide");
        }
        
        reset_external_assessor_controls();
    });

    $("#eventtype-cancel-assessor-btn").on("click", function (e) {
        e.preventDefault();
        $("#eventtype-external-assessors-controls").addClass("hide");
        $("#msgs").empty();

        if ($(".eventtype-internal-assessor-list-item").length === 0) {
            $("#eventtype-assessor-list-internal").addClass("hide");
        }

        reset_external_assessor_controls();
    });

    $("#rs-cancel-assessor-btn").on("click", function (e) {
        e.preventDefault();

        $("#external-assessors-controls").addClass("hide");
        $("#msgs").empty();

        if ($("#internal-assessors-list li").length === 0) {
            $("#assessor-list-internal").addClass("hide");
        }

        $("#rs-external-assessors-controls").addClass("hide");

        if ($("#rs-internal-assessors-list li").length === 0) {
            $("#rs-assessor-list-internal").addClass("hide");
        }

        reset_external_assessor_controls();
    });
    
    $("#add-external-user-btn").on("click", function (e) {
        e.preventDefault();
        add_external_assessor("date_range");
    });

    $("#rs-add-external-user-btn").on("click", function (e) {
        e.preventDefault();
        add_external_assessor("rotation_schedule");
    });

    $("#eventtype-add-external-user-btn").on("click", function (e) {
        e.preventDefault();
        add_external_assessor("eventtype");
    });
    
    $(".wizard-nav-item").on("click", function (e) { 
        e.preventDefault();
        if ($(this).hasClass("complete")) {
            var next_step = $(this).data("step");
            var current_step = $("#wizard-step-input").val();
            if (next_step > current_step) {
                distribution_next_step(next_step);
            } else {
                show_step(next_step);
            }
        }
    });

    $("#content").on("change", 'input[name="distribution_rs_assessor_option"]', function () {
        var selected_option = $(this).val();
        var selected_target_option = $("input[name=\"distribution_rs_target_option\"]:checked").val();

        $(this).attr("checked", "checked");
        $(".rs-assessor-option").addClass("hide");
        $(".rs-assessor-sub-option").addClass("hide");

        switch (selected_option) {
            case "learner" :
                $("#rs-assessor-learner-options").removeClass("hide");
                $("#distribution-feedback-options").addClass("hide");
                $("#feedback-required").removeAttr("checked");
                break;
            case "faculty" :
                $("#rs-assessor-faculty-options").removeClass("hide");
                if (selected_target_option === "learner") {
                    $("#distribution-feedback-options").removeClass("hide");
                }
            break;
            case "individual_users" :
                $("#rs-select-assessors-individual").removeClass("hide");
                if (selected_target_option === "learner") {
                    $("#distribution-feedback-options").removeClass("hide");
                }
            break;
        }
    });

    $("input[name=\"distribution_rs_target_option\"]").on("change", function () {
        var selected_option = $(this).val();
        var selected_assessor_option = $("input[name=\"distribution_rs_assessor_option\"]:checked").val();
        var selected_attempts_scope_option = $("input[name=\"attempts_scope\"]:checked");

        $(this).attr("checked", "checked");
        $(selected_attempts_scope_option).attr("checked", "checked");

        $(".rs-target-option").addClass("hide");
        $('#rotation_schedule_target_options').removeClass("hide");
        $("#select-target-options").addClass("hide");
        $("#non-cbme-targets-btn").attr("checked", "checked");
        switch (selected_option) {
            case "learner" :
                $("#rs-target-learner-options").removeClass("hide");
                if (cbme == true) {
                    $("#select-target-options").removeClass("hide");
                }
                if (selected_assessor_option === "faculty") {
                    $("#distribution-feedback-options").removeClass("hide");
                }
                break;
            case "faculty" :
            case "block" :
            case "external" :
                if ($("#assessment_type").val() == "assessment") {
                    $("#distribution-rs-assessor-learner").parent().addClass("hide");
                    $("#distribution-rs-assessor-faculty").parent().removeClass("hide");
                    $("#distribution-rs-assessor-external").parent().removeClass("hide");

                    var distribution_rs_assessor_learner_option_selected = $("input[name=\"distribution_rs_assessor_learner_option\"]:checked").attr("id");
                    $("#" + distribution_rs_assessor_learner_option_selected).removeAttr("checked");
                    $("#rs-assessor-learner-service").addClass("hide");
                    $("#rs-assessor-learner-options").addClass("hide");
                    $("#rs-additional-learners").addClass("hide");
                    $("#rs-individual-learners").addClass("hide");
                    $("#rs-target-faculty-options").addClass("hide");
                    $("#rs-assessor-learner-individual").addClass("hide");
                    $("#distribution-rs-onservice").removeAttr("checked");
                    $("#distribution-rs-offservice").removeAttr("checked");
                    $("#rs-additional-learners").removeAttr("checked");
                } else {
                    $("#distribution-rs-assessor-learner").parent().removeClass("hide");
                    $("#distribution-rs-assessor-faculty").parent().addClass("hide");
                    $("#distribution-rs-assessor-external").parent().addClass("hide");

                    $("#rs-assessor-faculty-options").addClass("hide");
                    $("#rs-select-assessors-individual").addClass("hide");
                    $("#rs-assessor-list-internal").addClass("hide");
                }

                if ($("#assessment_type").val() == "evaluation") {
                    if (selected_option == "faculty") {
                        $("#rs-target-faculty-options").removeClass("hide");
                    } else if (selected_option == "external") {
                        $("#rs-target-external-options").removeClass("hide");
                    }
                }
                break;
            case "generic" :
                $("#rs-target-generic-options").removeClass("hide");
                break;
        }

        var distribution_rs_target_option_selected = $("input[name=\"distribution_rs_target_option\"]:checked").attr("id");
        if ($("#" + distribution_rs_target_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_rs_target_option_selected).removeAttr("checked");
        }

        var distribution_rs_assessor_option_selected = $("input[name=\"distribution_rs_assessor_option\"]:checked").attr("id");
        if ($("#" + distribution_rs_assessor_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_rs_assessor_option_selected).removeAttr("checked");
        }
    });

    $("#content").on("change", 'input[name="distribution_rs_assessor_learner_option"]', function () {
        var selected_option = $(this).val();
        var additional_learners_checked = $("#distribution-rs-additional-learners").attr("checked");
        $(".rs-assessor-learner-sub-option").addClass("hide");
        $("#rs-additional-learners").removeClass("hide");
        switch (selected_option) {
            case "all" :
                $("#rs-assessor-learner-service").removeClass("hide");
                $("#rs-additional-learners").removeClass("hide");
                if (additional_learners_checked == "checked") {
                    $("#rs-individual-learners").removeClass("hide");
                }
            break;
            case "individual" :
                $("#rs-assessor-learner-individual").removeClass("hide");
                $("#rs-additional-learners").addClass("hide");
                $("#rs-individual-learners").addClass("hide");

            break;
        }
    });
    
    $("input[name=\"distribution_rs_target_learner_option\"]").on("change", function () {
        var selected_option = $(this).val();
        var additional_checked = $("#distribution-rs-target-additional-learners").attr("checked");
        $(".rs-target-learner-sub-option").addClass("hide");
        $("#rs-target-additional-learners").removeClass("hide");
        $("#rs-target-individual-learners").addClass("hide");
        switch (selected_option) {
            case "all" :
                $("#rs-target-learner-service").removeClass("hide");
                $("#rs-target-additional-learners").removeClass("hide");
                if (additional_checked == "checked") {
                    $("#rs-target-individual-learners").removeClass("hide");
                }
                break;
            case "individual" :
                $("#rs-target-learner-individual").removeClass("hide");
                $("#rs-target-individual-learners").addClass("hide");
                $("#rs-target-additional-learners").addClass("hide");
                break;
        }
    });
    
    $("input[name=\"distribution_rs_additional_learners\"]").on("change", function () {
        if ($(this).is(":checked")) {
            $("#rs-individual-learners").removeClass("hide");
        } else {
            $("#rs-individual-learners").addClass("hide");
        }
    });
    
    $("input[name=\"distribution_rs_target_additional_learners\"]").on("change", function () {
        if ($(this).is(":checked")) {
            $("#rs-target-individual-learners").removeClass("hide");
        } else {
            $("#rs-target-individual-learners").addClass("hide");
        }
    });

    $("input[name=\"distribution_eventtype_target_option\"]").on("change", function () {
        var selected_option = $(this).val();
        var selected_attempts_scope_option = $("input[name=\"attempts_scope\"]:checked");

        reset_wizard_step_3(null);
        $(this).attr("checked", "checked"); // reset the form to prevent validation inconsistencies
        $(selected_attempts_scope_option).attr("checked", "checked");
        $("#non-cbme-targets-btn").attr("checked", "checked");

        if (selected_option == "faculty") {
            if ($("#assessment_type").val() == "assessment") {
                $("#distribution-eventtype-assessor-learner").parent().addClass("hide");
                $("#distribution-eventtype-assessor-faculty").parent().removeClass("hide");
                $("#distribution-eventtype-assessor-external").parent().removeClass("hide");

                var distribution_eventtype_learners_selected = $("input[name=\"distribution_eventtype_learners\"]:checked").attr("id");
                $("#" + distribution_eventtype_learners_selected).removeAttr("checked");
                $("#eventtype-assessor-learner-options").addClass("hide");
            } else {
                $("#distribution-eventtype-assessor-learner").parent().removeClass("hide");
                $("#distribution-eventtype-assessor-faculty").parent().removeClass("hide");
                $("#distribution-eventtype-assessor-external").parent().removeClass("hide");
            }
        } else if (selected_option == "event") {
            if ($("#assessment_type").val() == "assessment") {
                $("#distribution-eventtype-assessor-learner").parent().addClass("hide");
                $("#distribution-eventtype-assessor-faculty").parent().addClass("hide");
                $("#distribution-eventtype-assessor-external").parent().removeClass("hide");

                var distribution_eventtype_learners_selected = $("input[name=\"distribution_eventtype_learners\"]:checked").attr("id");
                $("#" + distribution_eventtype_learners_selected).removeAttr("checked");
                $("#eventtype-assessor-learner-options").addClass("hide");
            } else {
                $("#distribution-eventtype-assessor-learner").parent().removeClass("hide");
                $("#distribution-eventtype-assessor-faculty").parent().removeClass("hide");
                $("#distribution-eventtype-assessor-external").parent().removeClass("hide");
            }
        }

        var distribution_eventtype_target_option_selected = $("input[name=\"distribution_eventtype_target_option\"]:checked").attr("id");
        if ($("#" + distribution_eventtype_target_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_eventtype_target_option_selected).removeAttr("checked");
        }

        var distribution_eventtype_assessor_option_selected = $("input[name=\"distribution_eventtype_assessor_option\"]:checked").attr("id");
        if ($("#" + distribution_eventtype_assessor_option_selected).parent().hasClass("hide")) {
            $("#" + distribution_eventtype_assessor_option_selected).removeAttr("checked");
        }
    });

    if ($("#targets-search").length) {
        var target_autocomplete = $("#targets-search").autocomplete({
            source: "?section=api-distributions&method=get-organisation-targets",
            minLength: 3,
            appendTo: $("#target-autocomplete-list-container"),
            open: function () {
                $("#targets-search").removeClass("searching");
                $("#targets-search").addClass("search");
            },

            close: function (e) {
                $("#targets-search").removeClass("searching");
                $("#targets-search").addClass("search");
            },

            select: function (e, ui) {
                build_selected_target_item(ui.item.value, ui.item.label, ui.item.group + " / " + ui.item.role);
                e.preventDefault();
            },

            search: function () {
                $("#targets-search").removeClass("search");
                $("#targets-search").addClass("searching");
            }
        }).data("autocomplete");

        target_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a = $(document.createElement("a"));
            var photo_div = $(document.createElement("div")).addClass("target-photo-container");
            var photo_img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("target-photo");
            var details_div = $(document.createElement("div")).addClass("target-details");
            var secondary_details_span = $(document.createElement("span")).addClass("target-secondary-details");
            var name_span = $(document.createElement("span")).addClass("target-name");
            var email_span = $(document.createElement("span")).addClass("target-email");
            var group_role_span = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);
            group_role_span.html(item.group + " / " + item.role);

            if (item.role === "Internal") {
                group_role_span.addClass("badge-green");
            } else {
                group_role_span.addClass("badge-grey");
            }

            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        target_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                target_autocomplete._renderItem(ul, item);
            });
        };
    }

    if ($("#external-assessors-search").length) {
        var assessor_autocomplete = $("#external-assessors-search").autocomplete({
            source: "?section=api-distributions&method=get-organisation-users",
            minLength: 3,
            appendTo: $("#autocomplete-list-container"),
            open: function () {
                $("#external-assessors-search").removeClass("searching");
                $("#external-assessors-search").addClass("search");
            },

            close: function (e) {
                $("#external-assessors-search").removeClass("searching");
                $("#external-assessors-search").addClass("search");
            },

            select: function (e, ui) {
                build_selected_assessor_item(ui.item.value, ui.item.label, ui.item.role);
                e.preventDefault();
            },

            search: function () {
                $("#external-assessors-search").removeClass("search");
                $("#external-assessors-search").addClass("searching");
            }
        }).data("autocomplete");

        assessor_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a = $(document.createElement("a"));
            var photo_div = $(document.createElement("div")).addClass("external-assessor-photo-container");
            var photo_img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("external-assessor-photo");
            var details_div = $(document.createElement("div")).addClass("external-assessor-details");
            var secondary_details_span = $(document.createElement("span")).addClass("external-assessor-secondary-details");
            var name_span = $(document.createElement("span")).addClass("external-assessor-name");
            var email_span = $(document.createElement("span")).addClass("external-assessor-email");
            var group_role_span = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);
            group_role_span.html(item.role + " assessor");

            if (item.role === "Internal") {
                group_role_span.addClass("badge-green");
            } else {
                group_role_span.addClass("badge-grey");
            }

            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        assessor_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                assessor_autocomplete._renderItem(ul, item);
            });

            build_external_assessors_button();
        };
    }

    if ($("#rs-external-assessors-search").length) {
        var rs_assessor_autocomplete = $("#rs-external-assessors-search").autocomplete({
            source: "?section=api-distributions&method=get-organisation-users",
            minLength: 3,
            appendTo: $("#rs-autocomplete-list-container"),
            open: function () {
                $("#rs-external-assessors-search").removeClass("searching");
                $("#rs-external-assessors-search").addClass("search");
            },

            close: function (e) {
                $("#rs-external-assessors-search").removeClass("searching");
                $("#rs-external-assessors-search").addClass("search");
            },

            select: function (e, ui) {
                build_selected_assessor_item(ui.item.value, ui.item.label, ui.item.role);
                e.preventDefault();
            },

            search: function () {
                $("#rs-external-assessors-search").removeClass("search");
                $("#rs-external-assessors-search").addClass("searching");
            }
        }).data("autocomplete");

        rs_assessor_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a = $(document.createElement("a"));
            var photo_div = $(document.createElement("div")).addClass("external-assessor-photo-container");
            var photo_img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("external-assessor-photo");
            var details_div = $(document.createElement("div")).addClass("external-assessor-details");
            var secondary_details_span = $(document.createElement("span")).addClass("external-assessor-secondary-details");
            var name_span = $(document.createElement("span")).addClass("external-assessor-name");
            var email_span = $(document.createElement("span")).addClass("external-assessor-email");
            var group_role_span = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);
            group_role_span.html(item.role + " assessor");

            if (item.role === "Internal") {
                group_role_span.addClass("badge-green");
            } else {
                group_role_span.addClass("badge-grey");
            }

            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        rs_assessor_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                rs_assessor_autocomplete._renderItem(ul, item);
            });

            build_external_assessors_button(true);
        };
    }

    if ($("#eventtype-external-assessors-search").length) {
        var eventtype_assessor_autocomplete = $("#eventtype-external-assessors-search").autocomplete({
            source: "?section=api-distributions&method=get-organisation-users",
            minLength: 3,
            appendTo: $("#eventtype-autocomplete-list-container"),
            open: function () {
                $("#eventtype-external-assessors-search").removeClass("searching");
                $("#eventtype-external-assessors-search").addClass("search");
            },

            close: function (e) {
                $("#add-external-eventtype-assessor-btn").remove();
                $("#eventtype-external-assessors-search").removeClass("searching");
                $("#eventtype-external-assessors-search").addClass("search");
            },

            select: function (e, ui) {
                build_selected_eventtype_assessor_item(ui.item.value, ui.item.label, ui.item.role);
                e.preventDefault();
            },

            search: function () {
                $("#eventtype-external-assessors-search").removeClass("search");
                $("#eventtype-external-assessors-search").addClass("searching");
            }
        }).data("autocomplete");

        eventtype_assessor_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a = $(document.createElement("a"));
            var photo_div = $(document.createElement("div")).addClass("external-assessor-photo-container");
            var photo_img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("external-assessor-photo");
            var details_div = $(document.createElement("div")).addClass("external-assessor-details");
            var secondary_details_span = $(document.createElement("span")).addClass("external-assessor-secondary-details");
            var name_span = $(document.createElement("span")).addClass("external-assessor-name");
            var email_span = $(document.createElement("span")).addClass("external-assessor-email");
            var group_role_span = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);
            group_role_span.html(item.role + " assessor");

            if (item.role === "Internal") {
                group_role_span.addClass("badge-green");
            } else {
                group_role_span.addClass("badge-grey");
            }

            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        eventtype_assessor_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                eventtype_assessor_autocomplete._renderItem(ul, item);
            });

            build_external_eventtype_assessors_button();
        };
    }

    $("#content").on("click", '#external-assessors-search', function () {
        if ($("#external-assessors-search").val()) {
            $("#autocomplete-list-container .ui-autocomplete").css("display", "block");
        }
    });

    $("#content").on("click", '#rs-external-assessors-search', function () {
        if ($("#rs-external-assessors-search").val()) {
            $("#rs-autocomplete-list-container .ui-autocomplete").css("display", "block");
        }
    });

    $("#content").on("click", '#eventtype-external-assessors-search', function () {
        if ($("#eventtype-external-assessors-search").val()) {
            $("#eventtype-autocomplete-list-container .ui-autocomplete").css("display", "block");
            build_external_eventtype_assessors_button();
        }
    });

    $("#targets-search").on("click", function () {
        if ($("#targets-search").val()) {
            $("#target-autocomplete-list-container").find(".ui-autocomplete").css("display", "block");
        }
    });

    $("#authors-search").on("click", function () {
        if ($("#authors-search").val()) {
            $("#author-autocomplete-list-container .ui-autocomplete").css("display", "block");
        }
    });

    $("#choose-method-btn").on("change", function (e, distribution_method) {
        reset_target_attempt_options();
        var settings = $(this).data("settings");
        settings.value = distribution_method;
        if ($("input[name='course_id']").length > 0 && $("input[name='course_id']").val()) {
            var selected_option = $("input[name=\"course_id\"]").val();
            var settings = $("#rs-choose-rotation-btn").data("settings");
            var faculty_settings = $("#choose-targets-faculty-btn").data("settings");

            if (selected_option !== "0") {
                $("#rs-rotation-schedule-options").removeClass("hide");
                settings.filters["rs_schedule"].api_params.course_id = selected_option;
                faculty_settings.filters["target_faculty"].api_params.course_id = selected_option;
            } else {
                $("#rs-rotation-schedule-options").addClass("hide");
            }
            $("#select-target-options").addClass("hide");
        }
    });

    $("#distribution-review-results").on("change", function (e, reviewers) {
        /* Reviewer release options are not supported yet.
        if (jQuery("input[name='distribution_results_user[]']").length > 0) {
            jQuery("#reviewer-release-options").show();
        } else {
            jQuery("#reviewer-release-options").hide();
        }
        */
        populate_flagging_options();
    });

    $("#author-type").on("change", function (e) {
        // Reinstantiate the author autocomplete to use new author type.
        build_author_autocomplete();
    });

    $(document).on("change", 'input[name="distribution_eventtype_assessor_option"]', function () {
        $(".eventtype-assessor-option").addClass("hide");
        var selected_option = $(this).val();
        switch (selected_option) {
            case "learner":
                $("#eventtype-assessor-learner-options").removeClass("hide");
            break;
            case "faculty":
                $("#eventtype-assessor-faculty-options").removeClass("hide");
            break;
            case "individual_users":
                $("#eventtype-select-assessors-individual").removeClass("hide");
            break;
        }
    });

    var author_autocomplete = null;

    function build_author_autocomplete() {

        var author_type = $("#author-type").val();

        author_autocomplete = $("#authors-search").autocomplete({
            source: "?section=api-distributions&method=get-filtered-audience&author_type=" + author_type + (distribution_id != "undefined" ? "&adistribution_id=" + distribution_id : ""),
            minLength: 3,
            appendTo: $("#author-autocomplete-list-container"),
            open: function () {
                $("#authors-search").removeClass("searching");
                $("#authors-search").addClass("search");
            },

            close: function(e) {
                $("#authors-search").removeClass("searching");
                $("#authors-search").addClass("search");
            },

            select: function(e, ui) {
                var label;
                switch (ui.item.role) {
                    case "course" :
                        label = course_author_label;
                        break;
                    case "organisation" :
                        label = organisation_author_label;
                        break;
                    default :
                        label = individual_author_label;
                        break;
                }
                build_selected_author_item(ui.item.value, ui.item.label, ui.item.role);
                e.preventDefault();
            },

            search: function () {
                $("#authors-search").removeClass("search");
                $("#authors-search").addClass("searching");
            }
        }).data("autocomplete");
    }

    if ($("#authors-search").length) {
        build_author_autocomplete();

        author_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a = $(document.createElement("a"));
            var photo_div = $(document.createElement("div")).addClass("author-photo-container");
            var photo_img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("author-photo");
            var details_div = $(document.createElement("div")).addClass("author-details");
            var secondary_details_span = $(document.createElement("span")).addClass("author-secondary-details");
            var name_span = $(document.createElement("span")).addClass("author-name");
            var email_span = $(document.createElement("span")).addClass("author-email");
            var group_role_span = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);

            var label;
            switch (item.role) {
                case "course" :
                    label = course_author_label;
                    break;
                case "organisation" :
                    label = organisation_author_label;
                    break;
                default :
                    label = individual_author_label;
                    break;
            }
            group_role_span.html(label).addClass("badge-green");
            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        author_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                author_autocomplete._renderItem(ul, item);
            });
        };
    }

    // If the form is up, then populate it, either with the distribution data, or just fill in the read-only author
    if ($("#editor-load-distribution-flag").length) {

        if ($("#editor-load-distribution-flag").data("adistribution-id") == 0) {
            update_target_release_controls(null);
        }

        // Fetch the current user id
        var get_current_id_request = jQuery.ajax({
            url: "?section=api-distributions",
            data: "method=get-current-user-data",
            type: "GET"
        });

        jQuery.when(get_current_id_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {
                // Check if we're in the editor, and we need to populate the form
                if ($("#editor-load-distribution-flag").data("adistribution-id")) {
                    loadDistributionData("#editor-load-distribution-flag", false, jsonResponse.current_id);
                } else {
                    // Add the current user as a default author when creating a new form
                    build_selected_author_item(jsonResponse.current_id, jsonResponse.name, "individual", false);
                }
            }
        });
    }

    $("#approver-required").on("change", function () {
        if ($(this).is(':checked')) {
            $("#select-approvers").removeClass("hide");
        } else {
            $("#select-approvers").addClass("hide");
            $("[name=\"distribution_approvers[]\"]").remove();
            $("#distribution_approvers-list-container").remove();
        }
    });

    $(".remove-single-filter").on("click", function (e) {
        e.preventDefault();

        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");

        var remove_filter_request = jQuery.ajax({
            url: "?section=api-distributions",
            data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
            type: "POST"
        });

        jQuery.when(remove_filter_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                window.location.reload();
            }
        });
    });

    $("#clear-all-filters").on("click", function (e) {
        e.preventDefault();

        var remove_filters_request = jQuery.ajax({
            url: "?section=api-distributions",
            data: "method=remove-all-filters",
            type: "POST"
        });

        jQuery.when(remove_filters_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                window.location.reload();
            }
        });
    });

    $(".choose-associated-faculty-btn").on("click", function (e) {
        e.preventDefault();
        var parent_div = $(this).closest("div");
        var associated_faculty_btn = $(this);
        var prefix = "";
        var distribution_method = $('[name="distribution_method"]').val();
        if (distribution_method == "rotation_schedule" || (distribution_method == "delegation" && $("#selected-delegation-option").val() == "rotation_schedule")) {
            prefix = "additional_";
        }

        var distribution_step_count = $("#wizard-step-input").val();
        var distribution_step = null;

        if (distribution_step_count == 3) {
            distribution_step = "target";
        } else if (distribution_step_count == 4) {
            distribution_step = "assessor";
        }

        if (distribution_step != null) {
            var get_associated_faculty_request = jQuery.ajax({
                url: "?section=api-distributions",
                data: "method=get-associated-faculty&course_id=" + $("input[name=\"course_id\"]").val(),
                type: "GET"
            });

            jQuery.when(get_associated_faculty_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    var ul = null;
                    var append_ul = false;
                    if ($("#" + prefix + distribution_step + "_faculty_list_container").length == 0) {
                        ul = jQuery(document.createElement("ul")).attr({"id": prefix + distribution_step + "_faculty_list_container"}).addClass("selected-items-list");
                        append_ul = true;
                    } else {
                        ul = $("#" + prefix + distribution_step + "_faculty_list_container");
                    }

                    jQuery.each(jsonResponse.data, function (key, faculty) {
                        if (associated_faculty_btn.data("state") == "select") {
                            if ($("#" + prefix + distribution_step + "_faculty_" + faculty.target_id).length == 0) {
                                var li = jQuery(document.createElement("li")).attr({"data-id": faculty.target_id}).addClass(prefix + distribution_step + "_faculty_target_item " + prefix + distribution_step + "_faculty_" + faculty.target_id);
                                var span_container = jQuery(document.createElement("span")).addClass("selected-list-container");
                                var span_item = jQuery(document.createElement("span")).addClass("selected-list-item").text("Faculty");
                                var span_remove = jQuery(document.createElement("span")).attr({
                                    "data-id": faculty.target_id,
                                    "data-filter": prefix + distribution_step + "_faculty"
                                }).addClass("remove-selected-list-item remove-target-toggle").text("×");
                                var span_name = jQuery(document.createElement("span")).text(" " + faculty.target_label);

                                ul.append(li.append(span_container.append(span_item, span_remove), span_name));
                                var hidden_input = jQuery(document.createElement("input")).attr({
                                    "type": "hidden",
                                    "value": faculty.target_id,
                                    "id": prefix + distribution_step + "_faculty_" + faculty.target_id,
                                    "data-label": faculty.target_label,
                                    "name": prefix + distribution_step + "_faculty[]"
                                }).addClass("search-target-control " + prefix + distribution_step + "_faculty_search_target_control");
                                $("#distribution-data-form").append(hidden_input);
                            }
                        } else {
                            $("." + prefix + distribution_step + "_faculty_" + faculty.target_id).remove();
                            $("#" + prefix + distribution_step + "_faculty_" + faculty.target_id).remove();
                        }
                    });
                    if (append_ul) {
                        parent_div.append(ul);
                    }

                    if (associated_faculty_btn.data("state") == "select") {
                        associated_faculty_btn.text("Remove Associated Faculty");
                        associated_faculty_btn.data("state", "remove");
                    } else {
                        associated_faculty_btn.text("Select Associated Faculty");
                        associated_faculty_btn.data("state", "select");
                    }
                } else {
                    display_error([jsonResponse.data], "#msgs", "prepend");
                }
            });
        }
    });

    var automatically_triggered = false;
    $("#assessment_type").on("change", function () {
        if (!automatically_triggered) {
            reset_wizard_step_3(null);
            reset_wizard_step_4(null);
            update_target_release_controls(null);
        }

        reset_assessor_target_controls();
        change_assessment_evaluation_text($(this).val());
        automatically_triggered = false;
    });

    function reset_assessor_target_controls () {
        if($("#assessment_type").val() == "evaluation") {
            $("#distribution-rs-target-self").parent().addClass("hide");
            $("#distribution-rs-target-learner").parent().addClass("hide");
            $("#distribution-rs-target-faculty").parent().removeClass("hide");
            $("#distribution-rs-target-block").parent().removeClass("hide");
            $("#distribution-rs-target-external").parent().removeClass("hide");

            $("#distribution-eventtype-target-eventtype").parent().addClass("hide");
            $("#distribution-eventtype-target-faculty").parent().removeClass("hide");
            $("#distribution-eventtype-target-event").parent().removeClass("hide");

            $("#distribution-target-self").parent().addClass("hide");
            $("#distribution-target-faculty").parent().removeClass("hide");
            $("#distribution-target-internal").parent().addClass("hide");
            $("#distribution-target-course").parent().removeClass("hide");
            $("#distribution-target-individual-users").parent().removeClass("hide");
            $("#distribution-target-external").parent().removeClass("hide");
            $("#select-target-options").addClass("hide");
        } else {
            $("#distribution-rs-target-self").parent().removeClass("hide");
            $("#distribution-rs-target-learner").parent().removeClass("hide");
            $("#distribution-rs-target-faculty").parent().addClass("hide");
            $("#distribution-rs-target-block").parent().addClass("hide");
            $("#distribution-rs-target-external").parent().addClass("hide");

            $("#distribution-eventtype-target-eventtype").parent().removeClass("hide");
            $("#distribution-eventtype-target-faculty").parent().addClass("hide");
            $("#distribution-eventtype-target-event").parent().addClass("hide");

            $("#distribution-target-self").parent().removeClass("hide");
            $("#distribution-target-faculty").parent().addClass("hide");
            $("#distribution-target-internal").parent().removeClass("hide");
            $("#distribution-target-course").parent().addClass("hide");
            $("#distribution-target-individual-users").parent().addClass("hide");
            $("#distribution-target-external").parent().addClass("hide");
        }
    }

    function update_target_release_controls(control_set) {

        var update_task = false;
        var update_report = false;
        if (control_set == null) {
            update_task = true;
            update_report = true;
        } else if (control_set == "task") {
            update_task = true;
        } else if (control_set == "report") {
            update_report = true;
        }

        if ($("#assessment_type").val() == "evaluation") {
            if (update_task) {
                $("#accordion-target-release-container").removeClass("hide");
                // Hide identifiable task options.
                $("#target-task-release-controls").addClass("hide");
            }

            if (update_report) {
                $("#accordion-target-release-container").removeClass("hide");
                // Hide identifiable task options.
                $("#target-task-release-controls").addClass("hide");
                // No extended reporting options.
                $("#accordion-target-report-container").addClass("hide");
            }

            if (update_task) {
                /**
                 * Set default release options for evaluations (never release, never report).
                 */
                // Task release
                $("input[name=target-task-release-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-task-release-never").attr("checked", true);
                $("input[name=target-task-release-threshold-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
            }

            if (update_report) {
                // Report release
                $("input[name=target-report-release-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-report-release-never").attr("checked", true);
                $("input[name=target-report-release-threshold-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });

                // Reporting options

                // Default comments to anonymous.
                $("input[name=target-report-comments-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-report-comments-anonymous").attr("checked", true);
            }

        } else {

            if (update_task) {
                /**
                 * Set default release options for assessments (immediately release, never report).
                 */
                $("#accordion-target-release-container").removeClass("hide");
                $("#target-task-release-controls").removeClass("hide");
            }

            if (update_task) {
                // Task release
                $("input[name=target-task-release-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-task-release-always").attr("checked", true);
                $("input[name=target-task-release-threshold-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-task-release-threshold-controls").addClass("hide");
            }

            if (update_report) {
                // Report release
                $("input[name=target-report-release-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-report-release-never").attr("checked", true);
                $("input[name=target-report-release-threshold-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-report-release-threshold-controls").addClass("hide");

                // Reporting options
                $("#accordion-target-report-container").removeClass("hide");

                // Default comments to anonymous.
                $("input[name=target-report-comments-option]").each(function (i, v) {
                    $(v).attr("checked", false);
                });
                $("#target-report-comments-anonymous").attr("checked", true);
            }
        }
    }

    $("#content").on("click", "#change-task-type", function () {
        if (distribution_id) {
            var update_distribution_task_type = jQuery.ajax({
                url: "?section=api-distributions",
                data: "method=change-distribution-task-type&adistribution_id=" + distribution_id,
                type: "POST"
            });

            jQuery.when(update_distribution_task_type).done(function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    location.reload();
                } else {
                    display_error(jsonResponse.data, "#msgs", "prepend");
                }
            });
        } else {
            display_error(["No distribution id set."], "#msgs", "prepend");
        }
    });

    $("#content").on("change", 'input[data-filter="target_individual"]', function() {
        $("#choose-targets-btn").html("Browse Targets <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $(".target_cohort_search_target_control").remove();
        $(".target_course_audience_search_target_control").remove();
    });

    $("#content").on("change", 'input[data-filter="target_cohort"]', function() {
        $(".target_individual_target_item").remove();
        $(".target_individual_search_target_control").remove();
    });

    $("#content").on("change", 'input[data-filter="target_course_audience"]', function() {
        $(".target_individual_target_item").remove();
        $(".target_individual_search_target_control").remove();
    });

    $("#content").on("change", 'input[data-filter="assessor_individual"]', function() {
        var assessors_btn_text = $("#assessment_type").val() == "assessment" ? "Assessors" : "Evaluators";
        $("#choose-assessors-btn").html("Browse " + assessors_btn_text + " <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $(".assessor_cohort_search_target_control").remove();
        $(".assessor_course_audience_search_target_control").remove();
    });

    $("#content").on("change", 'input[data-filter="assessor_cohort"]', function() {
        $(".assessor_individual_target_item").remove();
        $(".assessor_individual_search_target_control").remove();
    });

    $("#content").on("change", 'input[data-filter="assessor_course_audience"]', function() {
        $(".assessor_individual_target_item").remove();
        $(".assessor_individual_search_target_control").remove();
    });

    $("input[name=target-task-release-option]").on("change", function () {
        if ($("input[name=target-task-release-option]:checked").val() == "threshold") {
            $("#target-task-release-threshold-controls").removeClass("hide");
        } else {
            $("#target-task-release-threshold-controls").addClass("hide");

            $("input[name=target-task-release-threshold-option]").each(function (i, v) {
                $(v).attr("checked", false);
            });
        }
    });

    $("input[name=target-report-release-option]").on("change", function () {
        if ($("input[name=target-report-release-option]:checked").val() == "threshold") {

            $("#target-report-release-threshold-controls").removeClass("hide");

            if ($("#assessment_type").val() == "assessment") {
                $("#accordion-target-report-container").removeClass("hide");
            }

            if (!$("input[name=target-report-comments-option]:checked").val()) {
                // Default to anonymous comments.
                $("#target-report-comments-anonymous").attr("checked", true);
            }
        } else if ($("input[name=target-report-release-option]:checked").val() == "always") {

            $("#target-report-release-threshold-controls").addClass("hide");

            if ($("#assessment_type").val() == "assessment") {
                $("#accordion-target-report-container").removeClass("hide");
            }

            if (!$("input[name=target-report-comments-option]:checked").val()) {
                // Default to anonymous comments.
                $("#target-report-comments-anonymous").attr("checked", true);
            }

            $("input[name=target-report-release-threshold-option]").each(function (i, v) {
                $(v).attr("checked", false);
            });
        } else {

            $("#target-report-release-threshold-controls").addClass("hide");

            if ($("#assessment_type").val() == "assessment") {
                $("#accordion-target-report-container").addClass("hide");
            }

            $("input[name=target-report-release-threshold-option]").each(function (i, v) {
                $(v).attr("checked", false);
            });
        }
    });

    function change_assessment_evaluation_text (task_type) {
        if (task_type == "assessment") {
            var distribution_eventtype_learners_attended = $("#distribution-eventtype-learners-attended").parent().html();
            if (distribution_eventtype_learners_attended) {
                $("#distribution-eventtype-learners-attended").parent().html(distribution_eventtype_learners_attended.replace("evaluation", "assessment"));
            }

            var distribution_eventtype_learners = $("#distribution-eventtype-learners").parent().html();
            if (distribution_eventtype_learners) {
                $("#distribution-eventtype-learners").parent().html(distribution_eventtype_learners.replace("evaluation", "assessment"));
            }

            var eventtype_target_options = $("#eventtype-target-options div label:first").html();
            if (eventtype_target_options) {
                $("#eventtype-target-options div label:first").html(eventtype_target_options.replace("Evaluations", "Assessments"));
            }

            var specific_dates_target_options = $("#specific_dates_target_options div label:first").html();
            if (specific_dates_target_options) {
                $("#specific_dates_target_options div label:first").html(specific_dates_target_options.replace("Evaluations", "Assessments"));
            }

            var rotation_schedule_target_options = $("#rotation_schedule_target_options div label:first").html();
            if (rotation_schedule_target_options) {
                $("#rotation_schedule_target_options div label:first").html(rotation_schedule_target_options.replace("Evaluations", "Assessments"));
            }

            var assessment_mandatory_label = $('label[for="assessment-mandatory"]:first').html();
            if (assessment_mandatory_label) {
                $('label[for="assessment-mandatory"]:first').html(assessment_mandatory_label.replace("Evaluation", "Assessment"));
            }

            var assessment_mandatory = $("#assessment-mandatory").parent().html();
            if (assessment_mandatory) {
                $("#assessment-mandatory").parent().html(assessment_mandatory.replace("evaluators", "assessors").replace("this evaluation", "this assessment"));
            }

            var attempts_scope_targets = $("#attempts-scope-targets").parent().html();
            if (attempts_scope_targets) {
                $("#attempts-scope-targets").parent().html(attempts_scope_targets.replace("Evaluators", "Assessors").replace("evaluate", "assess"));
            }

            var attempts_scope_overall = $("#attempts-scope-overall").parent().html();
            if (attempts_scope_overall) {
                $("#attempts-scope-overall").parent().html(attempts_scope_overall.replace("Evaluators", "Assessors"));
            }

            var wizard_nav_item_4 = $("#wizard-nav-item-4 a").html();
            if (wizard_nav_item_4) {
                $("#wizard-nav-item-4 a").html(wizard_nav_item_4.replace("Evaluators", "Assessors"));
            }

            var distribution_target_self = $("#distribution-target-self").html();
            if (distribution_target_self) {
                $("#distribution-target-self").html(distribution_target_self.replace("evaluators", "assessors").replace("evaluation", "assessment"));
            }

            var distribution_rs_target_self = $("#distribution-rs-target-self").html();
            if (distribution_rs_target_self) {
                $("#distribution-rs-target-self").html(distribution_rs_target_self.replace("evaluators", "assessors").replace("evaluation", "assessment"));
            }

            var repeat_targets = $("#repeat-targets").html();
            if (repeat_targets) {
                $("#repeat-targets").html(repeat_targets.replace("Evaluators", "Assessors"));
            }

            var eventtype_assessor_options = $("#eventtype-assessor-options div label:first").html();
            if (eventtype_assessor_options) {
                $("#eventtype-assessor-options div label:first").html(eventtype_assessor_options.replace("Evaluator", "Assessor"));
            }

            var distribution_eventtype_assessor_learner = $("#distribution-eventtype-assessor-learner").parent().html();
            if (distribution_eventtype_assessor_learner) {
                $("#distribution-eventtype-assessor-learner").parent().html(distribution_eventtype_assessor_learner.replace("evaluators", "assessors"));
            }

            var distribution_eventtype_assessor_faculty = $("#distribution-eventtype-assessor-faculty").parent().html();
            if (distribution_eventtype_assessor_faculty) {
                $("#distribution-eventtype-assessor-faculty").parent().html(distribution_eventtype_assessor_faculty.replace("evaluators", "assessors"));
            }

            var distribution_eventtype_assessor_external = $("#distribution-eventtype-assessor-external").html();
            if (distribution_eventtype_assessor_external) {
                $("#distribution-eventtype-assessor-external").html(distribution_eventtype_assessor_external.replace("evaluators", "assessors"));
            }

            var eventtype_select_assessors_individual = $("#eventtype-select-assessors-individual div label:first").html();
            if (eventtype_select_assessors_individual) {
                $("#eventtype-select-assessors-individual div label:first").html(eventtype_select_assessors_individual.replace("Evaluators", "Assessors"));
            }

            var eventtype_external_assessors_search = $("#eventtype-external-assessors-search").attr("placeholder");
            if (eventtype_external_assessors_search) {
                $("#eventtype-external-assessors-search").attr("placeholder", eventtype_external_assessors_search.replace("evaluators", "assessors"));
            }

            var eventtype_selected_assessors_list_heading = $("#eventtype-selected-assessors-list-heading").html();
            if (eventtype_selected_assessors_list_heading) {
                $("#eventtype-selected-assessors-list-heading").html(eventtype_selected_assessors_list_heading.replace("Evaluators", "Assessors"));
            }

            var eventtype_add_external_user_btn = $("#eventtype-add-external-user-btn").html();
            if (eventtype_add_external_user_btn) {
                $("#eventtype-add-external-user-btn").html(eventtype_add_external_user_btn.replace("Evaluator", "Assessor"));
            }

            var rotation_schedule_assessor_options = $("#rotation_schedule_assessor_options div label:first").html();
            if (rotation_schedule_assessor_options) {
                $("#rotation_schedule_assessor_options div label:first").html(rotation_schedule_assessor_options.replace("Evaluator", "Assessor"));
            }

            var distribution_rs_assessor_learner = $("#distribution-rs-assessor-learner").parent().html();
            $("#distribution-rs-assessor-learner").parent().html(distribution_rs_assessor_learner.replace("evaluators", "assessors"));

            var distribution_rs_assessor_faculty = $("#distribution-rs-assessor-faculty").parent().html();
            if (distribution_rs_assessor_faculty) {
                $("#distribution-rs-assessor-faculty").parent().html(distribution_rs_assessor_faculty.replace("evaluators", "assessors"));
            }

            var distribution_rs_assessor_faculty = $("#distribution-rs-assessor-faculty").parent().html().replace("evaluators", "assessors");
            if (distribution_rs_assessor_faculty) {
                $("#distribution-rs-assessor-faculty").parent().html(distribution_rs_assessor_faculty.replace("evaluators", "assessors"));
            }

            var rs_select_assessors_individual = $("#rs-select-assessors-individual div label:first").html();
            if (rs_select_assessors_individual) {
                $("#rs-select-assessors-individual div label:first").html(rs_select_assessors_individual.replace("Evaluators", "Assessors"));
            }

            var rs_external_assessors_search = $("#rs-external-assessors-search").attr("placeholder");
            if (rs_external_assessors_search) {
                $("#rs-external-assessors-search").attr("placeholder", rs_external_assessors_search.replace("evaluators", "assessors"));
            }

            var rs_selected_assessors_list_heading = $("#rs-selected-assessors-list-heading").html();
            if (rs_selected_assessors_list_heading) {
                $("#rs-selected-assessors-list-heading").html(rs_selected_assessors_list_heading.replace("Evaluators", "Assessors"));
            }

            var rs_add_external_user_btn = $("#rs-add-external-user-btn").html();
            if (rs_add_external_user_btn) {
                $("#rs-add-external-user-btn").html(rs_add_external_user_btn.replace("Evaluator", "Assessor"));
            }

            var specific_dates_assessor_options = $("#specific_dates_assessor_options div label:first").html();
            if (specific_dates_assessor_options) {
                $("#specific_dates_assessor_options div label:first").html(specific_dates_assessor_options.replace("Evaluator", "Assessor"));
            }

            var distribution_assessor_external = $("#distribution-assessor-external").parent().html();
            if (distribution_assessor_external) {
                $("#distribution-assessor-external").parent().html(distribution_assessor_external.replace("evaluators", "assessors"));
            }

            var select_assessors_grouped = $("#select-assessors-grouped label:first").html();
            if (select_assessors_grouped) {
                $("#select-assessors-grouped label:first").html(select_assessors_grouped.replace("Evaluators", "Assessors"));
            }

            var choose_assessors_btn_default_text = $("#choose-assessors-btn-default-text").val();
            if (choose_assessors_btn_default_text) {
                $("#choose-assessors-btn-default-text").val(choose_assessors_btn_default_text.replace("Evaluators", "Assessors"));
            }

            var choose_assessors_btn = $("#choose-assessors-btn").html();
            if (choose_assessors_btn) {
                $("#choose-assessors-btn").html(choose_assessors_btn.replace("Evaluators", "Assessors"));
            }

            var select_assessors_faculty = $("#select-assessors-faculty label:first").html();
            if (select_assessors_faculty) {
                $("#select-assessors-faculty label:first").html(select_assessors_faculty.replace("Evaluators", "Assessors"));
            }

            var select_assessors_individual = $("#select-assessors-individual div label:first").html();
            if (select_assessors_individual) {
                $("#select-assessors-individual div label:first").html(select_assessors_individual.replace("Evaluators", "Assessors"));
            }

            var external_assessors_search = $("#external-assessors-search").attr("placeholder");
            if (external_assessors_search) {
                $("#external-assessors-search").attr("placeholder", external_assessors_search.replace("evaluators", "assessors"));
            }

            var external_assessors_search_html = $("#external-assessors-search").html();
            if (external_assessors_search_html) {
                $("#external-assessors-search").html(external_assessors_search_html.replace("Evaluators", "Assessors"));
            }

            var add_external_user_btn = $("#add-external-user-btn").html();
            if (add_external_user_btn) {
                $("#add-external-user-btn").html(add_external_user_btn.replace("Evaluator", "Assessor"));
            }
        } else {
            var distribution_eventtype_learners_attended = $("#distribution-eventtype-learners-attended").parent().html();
            if (distribution_eventtype_learners_attended) {
                $("#distribution-eventtype-learners-attended").parent().html(distribution_eventtype_learners_attended.replace("assessment", "evaluation"));
            }

            var distribution_eventtype_learners = $("#distribution-eventtype-learners").parent().html();
            if (distribution_eventtype_learners) {
                $("#distribution-eventtype-learners").parent().html(distribution_eventtype_learners.replace("assessment", "evaluation"));
            }

            var eventtype_target_options = $("#eventtype-target-options div label:first").html();
            if (eventtype_target_options) {
                $("#eventtype-target-options div label:first").html(eventtype_target_options.replace("Assessments", "Evaluations"));
            }

            var specific_dates_target_options = $("#specific_dates_target_options div label:first").html();
            if (specific_dates_target_options) {
                $("#specific_dates_target_options div label:first").html(specific_dates_target_options.replace("Assessments", "Evaluations"));
            }

            var rotation_schedule_target_options = $("#rotation_schedule_target_options div label:first").html();
            if (rotation_schedule_target_options) {
                $("#rotation_schedule_target_options div label:first").html(rotation_schedule_target_options.replace("Assessments", "Evaluations"));
            }

            var assessment_mandatory_label = $('label[for="assessment-mandatory"]:first').html();
            if (assessment_mandatory_label) {
                $('label[for="assessment-mandatory"]:first').html(assessment_mandatory_label.replace("Assessment", "Evaluation"));
            }

            var assessment_mandatory = $("#assessment-mandatory").parent().html();
            if (assessment_mandatory) {
                $("#assessment-mandatory").parent().html(assessment_mandatory.replace("assessors", "evaluators").replace("this assessment", "this evaluation"));
            }

            var attempts_scope_targets = $("#attempts-scope-targets").parent().html();
            if (attempts_scope_targets) {
                $("#attempts-scope-targets").parent().html(attempts_scope_targets.replace("Assessors", "Evaluators").replace("assess", "evaluate"));
            }

            var attempts_scope_overall = $("#attempts-scope-overall").parent().html();
            if (attempts_scope_overall) {
                $("#attempts-scope-overall").parent().html(attempts_scope_overall.replace("Assessors", "Evaluators"));
            }

            var wizard_nav_item_4 = $("#wizard-nav-item-4 a").html();
            if (wizard_nav_item_4) {
                $("#wizard-nav-item-4 a").html(wizard_nav_item_4.replace("Assessors", "Evaluators"));
            }

            var distribution_target_self = $("#distribution-target-self").html();
            if (distribution_target_self) {
                $("#distribution-target-self").html(distribution_target_self.replace("assessors", "evaluators").replace("assessment", "evaluation"));
            }

            var distribution_rs_target_self = $("#distribution-rs-target-self").html();
            if (distribution_rs_target_self) {
                $("#distribution-rs-target-self").html(distribution_rs_target_self.replace("assessors", "evaluators").replace("assessment", "evaluation"));
            }

            var repeat_targets = $("#repeat-targets").html();
            if (repeat_targets) {
                $("#repeat-targets").html(repeat_targets.replace("Assessors", "Evaluators"));
            }

            var eventtype_assessor_options = $("#eventtype-assessor-options div label:first").html();
            if (eventtype_assessor_options) {
                $("#eventtype-assessor-options div label:first").html(eventtype_assessor_options.replace("Assessor", "Evaluator"));
            }

            var distribution_eventtype_assessor_learner = $("#distribution-eventtype-assessor-learner").parent().html();
            if (distribution_eventtype_assessor_learner) {
                $("#distribution-eventtype-assessor-learner").parent().html(distribution_eventtype_assessor_learner.replace("assessors", "evaluators"));
            }

            var distribution_eventtype_assessor_faculty = $("#distribution-eventtype-assessor-faculty").parent().html();
            if (distribution_eventtype_assessor_faculty) {
                $("#distribution-eventtype-assessor-faculty").parent().html(distribution_eventtype_assessor_faculty.replace("assessors", "evaluators"));
            }

            var distribution_eventtype_assessor_external = $("#distribution-eventtype-assessor-external").html();
            if (distribution_eventtype_assessor_external) {
                $("#distribution-eventtype-assessor-external").html(distribution_eventtype_assessor_external.replace("assessors", "evaluators"));
            }

            var eventtype_select_assessors_individual = $("#eventtype-select-assessors-individual div label:first").html();
            if (eventtype_select_assessors_individual) {
                $("#eventtype-select-assessors-individual div label:first").html(eventtype_select_assessors_individual.replace("Assessors", "Evaluators"));
            }

            var eventtype_external_assessors_search = $("#eventtype-external-assessors-search").attr("placeholder");
            if (eventtype_external_assessors_search) {
                $("#eventtype-external-assessors-search").attr("placeholder", eventtype_external_assessors_search.replace("assessors", "evaluators"));
            }

            var eventtype_selected_assessors_list_heading = $("#eventtype-selected-assessors-list-heading").html();
            if (eventtype_selected_assessors_list_heading) {
                $("#eventtype-selected-assessors-list-heading").html(eventtype_selected_assessors_list_heading.replace("Assessors", "Evaluators"));
            }

            var eventtype_add_external_user_btn = $("#eventtype-add-external-user-btn").html();
            if (eventtype_add_external_user_btn) {
                $("#eventtype-add-external-user-btn").html(eventtype_add_external_user_btn.replace("Assessor", "Evaluator"));
            }

            var rotation_schedule_assessor_options = $("#rotation_schedule_assessor_options div label:first").html();
            if (rotation_schedule_assessor_options) {
                $("#rotation_schedule_assessor_options div label:first").html(rotation_schedule_assessor_options.replace("Assessor", "Evaluator"));
            }

            var distribution_rs_assessor_learner = $("#distribution-rs-assessor-learner").parent().html();
            $("#distribution-rs-assessor-learner").parent().html(distribution_rs_assessor_learner.replace("assessors", "evaluators"));

            var distribution_rs_assessor_faculty = $("#distribution-rs-assessor-faculty").parent().html();
            if (distribution_rs_assessor_faculty) {
                $("#distribution-rs-assessor-faculty").parent().html(distribution_rs_assessor_faculty.replace("assessors", "evaluators"));
            }

            var distribution_rs_assessor_faculty = $("#distribution-rs-assessor-faculty").parent().html().replace("assessors", "evaluators");
            if (distribution_rs_assessor_faculty) {
                $("#distribution-rs-assessor-faculty").parent().html(distribution_rs_assessor_faculty.replace("assessors", "evaluators"));
            }

            var rs_select_assessors_individual = $("#rs-select-assessors-individual div label:first").html();
            if (rs_select_assessors_individual) {
                $("#rs-select-assessors-individual div label:first").html(rs_select_assessors_individual.replace("Assessors", "Evaluators"));
            }

            var rs_external_assessors_search = $("#rs-external-assessors-search").attr("placeholder");
            if (rs_external_assessors_search) {
                $("#rs-external-assessors-search").attr("placeholder", rs_external_assessors_search.replace("assessors", "evaluators"));
            }

            var rs_selected_assessors_list_heading = $("#rs-selected-assessors-list-heading").html();
            if (rs_selected_assessors_list_heading) {
                $("#rs-selected-assessors-list-heading").html(rs_selected_assessors_list_heading.replace("Assessors", "Evaluators"));
            }

            var rs_add_external_user_btn = $("#rs-add-external-user-btn").html();
            if (rs_add_external_user_btn) {
                $("#rs-add-external-user-btn").html(rs_add_external_user_btn.replace("Assessor", "Evaluator"));
            }

            var specific_dates_assessor_options = $("#specific_dates_assessor_options div label:first").html();
            if (specific_dates_assessor_options) {
                $("#specific_dates_assessor_options div label:first").html(specific_dates_assessor_options.replace("Assessor", "Evaluator"));
            }

            var distribution_assessor_external = $("#distribution-assessor-external").parent().html();
            if (distribution_assessor_external) {
                $("#distribution-assessor-external").parent().html(distribution_assessor_external.replace("assessors", "evaluators"));
            }

            var select_assessors_grouped = $("#select-assessors-grouped label:first").html();
            if (select_assessors_grouped) {
                $("#select-assessors-grouped label:first").html(select_assessors_grouped.replace("Assessors", "Evaluators"));
            }

            var choose_assessors_btn_default_text = $("#choose-assessors-btn-default-text").val();
            if (choose_assessors_btn_default_text) {
                $("#choose-assessors-btn-default-text").val(choose_assessors_btn_default_text.replace("Assessors", "Evaluators"));
            }

            var choose_assessors_btn = $("#choose-assessors-btn").html();
            if (choose_assessors_btn) {
                $("#choose-assessors-btn").html(choose_assessors_btn.replace("Assessors", "Evaluators"));
            }

            var select_assessors_faculty = $("#select-assessors-faculty label:first").html();
            if (select_assessors_faculty) {
                $("#select-assessors-faculty label:first").html(select_assessors_faculty.replace("Assessors", "Evaluators"));
            }

            var select_assessors_individual = $("#select-assessors-individual div label:first").html();
            if (select_assessors_individual) {
                $("#select-assessors-individual div label:first").html(select_assessors_individual.replace("Assessors", "Evaluators"));
            }

            var external_assessors_search = $("#external-assessors-search").attr("placeholder");
            if (external_assessors_search) {
                $("#external-assessors-search").attr("placeholder", external_assessors_search.replace("assessors", "evaluators"));
            }

            var external_assessors_search_html = $("#external-assessors-search").html();
            if (external_assessors_search_html) {
                $("#external-assessors-search").html(external_assessors_search_html.replace("Assessors", "Evaluators"));
            }

            var add_external_user_btn = $("#add-external-user-btn").html();
            if (add_external_user_btn) {
                $("#add-external-user-btn").html(add_external_user_btn.replace("Assessor", "Evaluator"));
            }
        }
    }

    function resetExpiryControls(control_to_display = false, clear_values = false) {

        switch (control_to_display) {
            case "offset":
                $("#expiry-option-controls").removeClass("hide");
                $("#expiry-date-option-controls").addClass("hide");
                break;
            case "date":
                $("#expiry-option-controls").addClass("hide");
                $("#expiry-date-option-controls").removeClass("hide");
                break;
            default:
                break;
        }

        if (clear_values) {
            $("#expiry-option").attr("checked", false);
            $("#expiry-date").val("");
        }
    }

    automatically_triggered = true;
    $("#assessment_type").trigger("change");

    var popover_options = {
        animation: false,
        container: "body",
        selector: "[rel=\"popover\"]",
        html: true,
        placement: "left",
        title: "User Information",
        content: function () {
            var result = "";
            var target_id = $(this).attr("data-id");
            var user_data_request = $.ajax({
                url: "?section=api-distributions",
                data: "method=get-user-popover-data&proxy_id=" + target_id + "&advanced_search=1&proxy_id=" + target_id,
                type: "GET"
            });
            $.when(user_data_request).done(function (data) {
                result = false;
                var jsonResponse = safeParseJson(data, "");
                if (typeof jsonResponse.data !== 'undefined') {
                    if (jsonResponse.data) {
                        var data = jsonResponse.data[0];
                        if (typeof data !== 'undefined') {
                            if (typeof data.target_id !== 'undefined') {
                                result = "<div class='inner-popover-content'><img class='userAvatar img-polaroid center-align square-image' src='" + ENTRADA_URL + "/api/photo.api.php/" + data.target_id + "/official' /><br/>";
                                result += "<div> " + data.target_label;

                                if ((data.learner_level == "" && data.group == "faculty") || (data.cbme_flag == false)) {
                                    result += "<div class='pull-right'><span class='label'>" + data.group.charAt(0).toUpperCase() + data.group.slice(1) + " &bull; " + data.role + "</span></div>";
                                } else {
                                    if (data.hasOwnProperty("stage_name") && data.hasOwnProperty("stage") && data.hasOwnProperty("cbme_flag")) {
                                        /**
                                         * Removed the functionality that showed the learners CBME stage since at this point a course_id isn't always known, therefore the learner stage can't reliably be determined.
                                         */
                                        //result += "<div class='pull-right'><span data-toggle='tooltip' title='"+ data.stage_name +"' class='label "+ (data.cbme_flag == true ? "learner-level-badge cbme" : "") +"'>" + data.stage + (data.stage == "" ? "" : " &bull; ") + data.learner_level + "</span></div>";
                                        result += "<div class='pull-right'><span class='label'>" + data.group.charAt(0).toUpperCase() + data.group.slice(1) + " &bull; " + data.role + "</span></div>";
                                    }
                                }

                                result += "</div>";
                                result += "<div class='truncate'><a href='mailto:" + data.email + "' target='_top'>" + data.email + "</a></div>";
                            }
                        }
                    }
                }

                if (!result) {
                    result = "<p class='center-align space-above large'><strong>No information found</strong></p>";
                }

                $(".popover-content").html(result);
            });
            return result;
        }
    };

    $(document).on("mouseenter", ".search-filter-item", function(e) {
        e.stopPropagation();
        var item = $(this);

        if ($(item).attr("data-id").length && $(item).attr("data-id") > 0) {
            var popover = false;
            var popover_filters = [
                "target_individual",
                "individual_target_learner",
                "target_faculty",
                "additional_target_faculty",
                "additional_target_learners",
                "assessor_individual",
                "individual_assessor_learners",
                "assessor_faculty",
                "additional_assessor_faculty",
                "additional_assessor_learners",
                "delegator"
            ];

            // We only want to render popover user cards in individual user cases, which we determine using the data filter.
            $.each(popover_filters, function (i, v) {
                if ($(item).find("input[data-filter=\"" + v + "\"]").length) {
                    popover = true;
                }
            });

            if (popover) {
                timer = setTimeout(function () {
                    $(".popover").remove();
                    $("[rel=\"popover\"]").popover(popover_options);
                    item.popover("show");
                }, 700);
            }
        }
    });

    $(document).on("click", ".popover", function(e) {
        e.stopPropagation();
        $(".popover").remove();
        $("[rel=\"popover\"]").popover(popover_options);
        $(this).popover("show");
    });

    $(document).on("mouseleave", ".search-filter-item", function(e) {
        e.stopPropagation();
        clearTimeout(timer);
        setTimeout(function () {
            if (!$(".popover:hover").length) {
                $(".popover-content").empty();
                $(".popover").remove();
            }
        }, 300);
    });

    $(document).on("mouseleave", ".popover", function(e) {
        e.stopPropagation();
        clearTimeout(timer);
        if (!$(".search-filter-item:hover").length) {
            setTimeout(function () {
                if (!$(".popover:hover").length) {
                    $(".popover-content").empty();
                    $(".popover").remove();
                }
            }, 300);
        }
    });

    $(document).on("click", ".search-filter-item", function(e) {
        e.stopPropagation();
        $(".popover").hide();
    });
});

function get_distributions () {
    var total_rows = jQuery(".data-row").length;
    var offset = total_rows;
    var search_value = jQuery("#distribution-search").val();
    
    var get_distributions_request = jQuery.ajax({
        url: "?section=api-distributions",
        data: "method=get-distributions&search_value=" + search_value  + "&offset=" + offset,
        type: "GET"
    });

    jQuery.when(get_distributions_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            jQuery.each(jsonResponse.data.distributions, function (key, distribution) {
                build_distribution_row(distribution);
            });

            var total_display_rows = parseInt(jQuery(".data-row").length);
            var total_rows = parseInt(jsonResponse.data.total_records);
            update_load_button_text (total_display_rows, total_rows);
            jQuery("#distributions-table").removeClass("hide");
            jQuery("#load-distributions").removeClass("hide");
        } else {
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No distributions found matching the search criteria.");
            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
            jQuery("#assessment-msgs").append(no_results_div);
        }
        jQuery("#load-distributions").removeClass("loading");
        jQuery("#assessment-distributions-loading").addClass("hide");
    });
}

function update_load_button_text (total_display_rows, total_rows) {
    jQuery("#load-distributions").html("Showing " + total_display_rows + " of " + total_rows + " distributions");
    if (total_display_rows === total_rows) {
        jQuery("#load-distributions").addClass("load-distributions-disabled");
    } else {
        jQuery("#load-distributions").removeClass("load-distributions-disabled");
    }
}

function build_distribution_row (distribution) {
    var distribution_tr = jQuery(document.createElement("tr")).addClass("data-row");
    var input_td        = jQuery(document.createElement("td"));
    var title_td        = jQuery(document.createElement("td"));
    var course_td       = jQuery(document.createElement("td"));
    var cperiod_td      = jQuery(document.createElement("td"));
    var date_td         = jQuery(document.createElement("td"));
    var options_td      = jQuery(document.createElement("td"));

    var input           = jQuery(document.createElement("input")).attr({type: "checkbox", name: "distributions[]", value: distribution.adistribution_id});
    var title_a         = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + distribution.adistribution_id}).html(distribution.title);
    var course_a        = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + distribution.adistribution_id}).html(distribution.course_name);
    var cperiod_a       = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + distribution.adistribution_id}).html((distribution.curriculum_period_title == "") ? distribution.start_date + " to " + distribution.finish_date : distribution.curriculum_period_title);
    var date_a          = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + distribution.adistribution_id}).html(distribution.updated_date);
    //var title_a         = distribution.title;
    //var course_a        = distribution.course_name;
    //var date_a          = distribution.updated_date;
    var btn_group       = jQuery(document.createElement("div")).addClass("btn-group");
    var btn             = jQuery(document.createElement("button")).addClass("btn btn-mini dropdown-toggle").attr({"data-toggle": "dropdown"});
    var icon            = jQuery(document.createElement("i")).addClass("fa fa-cog");
    var options_ul      = jQuery(document.createElement("ul")).addClass("dropdown-menu toggle-left");
    var edit_li         = jQuery(document.createElement("li"));
    var edit_a          = jQuery(document.createElement("a")).addClass("edit-distribution").attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=form&adistribution_id=" + distribution.adistribution_id, "data-adistribution-id" : distribution.adistribution_id}).html("Edit Distribution");
    var copy_li         = jQuery(document.createElement("li"));
    var copy_a          = jQuery(document.createElement("a")).addClass("copy-distribution").attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=form&mode=copy&adistribution_id=" + distribution.adistribution_id, "data-adistribution-id" : distribution.adistribution_id}).html("Copy Distribution");
    var progress_li     = jQuery(document.createElement("li"));
    var progress_a      = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + distribution.adistribution_id}).html("View Distribution Report");

    progress_li.append(progress_a);
    edit_li.append(edit_a);
    copy_li.append(copy_a);
    options_ul.append(edit_li).append(copy_li).append(progress_li);
    btn.append(icon);
    btn_group.append(btn).append(options_ul);

    input_td.append(input);
    //title_td.append(title_a);
    //course_td.append(course_a);
    //date_td.append(date_a);
    title_td.append(title_a).html();
    course_td.append(course_a).html();
    cperiod_td.append(cperiod_a).html();
    date_td.append(date_a).html();
    options_td.append(btn_group);

    distribution_tr.append(input_td).append(title_td).append(course_td).append(cperiod_td).append(date_td).append(options_td);

    jQuery("#distributions-table tbody").append(distribution_tr);
}

function distribution_next_step (next_step) {
    var step = parseInt(jQuery("#wizard-step-input").val());
    // For step 5, we have to clear unnecessary variables that might be problematic. This functionality would be in the switch statement below, but the distribution
    // is already saved at that point, and the variables would have already been passed along.
    if (step == 5) {
        if (jQuery("#flagging_notifications").val() !== "reviewers") {
            // Remove the assessment reviewers that might have been added that are now unnecessary.
            jQuery('input[name="distribution_results_user[]"]').remove();
        }
    }

    var distribution_step_request = jQuery.ajax({
        url: "?section=api-distributions",
        data: "method=distribution-wizard&" + jQuery("#distribution-data-form").serialize() + "&step=" + step  + (typeof next_step !== "undefined" ? "&next_step=" + next_step : ""),
        type: "POST",
        beforeSend: function () {  
            show_loading_msg();
        },
        complete: function (e,r) {
            hide_loading_msg();
            if (step == 4 || step == 5) {
                populate_flagging_options();
                if (!jQuery('#msgs').html()) {
                    // Only show "save" button text if no errors are present.
                    jQuery("#distribution-next-step").html("Save Distribution");
                }
            } else {
                jQuery("#distribution-next-step").html("Next Step");
            }
        }
    });
        
    jQuery.when(distribution_step_request).done(function (data) {
        try {
            var jsonResponse = JSON.parse(data);
        } catch (e) {
            var jsonResponse = [];
            jsonResponse.status = "error";
            jsonResponse.data = [];
            jsonResponse.data[0] = "Unable to read distribution data.";
        }
        var current_step = jQuery("#wizard-step-input").val();
        
        remove_errors();
        
        if (jsonResponse.status === "success") {
            var next_step = parseInt(jsonResponse.data.step);

            jQuery("#overall-distribution-title").html( " : " + jQuery("#distribution-title").val() );

            switch (next_step) {

                case 2 : /** Validation completed on first step/form selection **/
                    break;

                case 3: /** Validation completed on method selection, prepare for Targets step **/
                    var settings = jQuery("#choose-method-btn").data("settings");
                    var selected_method = settings.value;
                    jQuery("#distribution-feedback-options").addClass("hide");

                    switch (selected_method) {
                        case "rotation_schedule" :
                            jQuery("#rotation_schedule_target_options").removeClass("hide");
                            jQuery("#eventtype-target-options").addClass("hide");
                        break;
                        case "date_range" :
                            jQuery("#specific_dates_target_options").removeClass("hide");
                            jQuery("#eventtype-target-options").addClass("hide");
                            if ((jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "individual_users") && (jQuery("input[name=\"distribution_target_option\"]:checked").val() === "grouped_users" || jQuery("input[name=\"distribution_target_option\"]:checked").val() === "individual_users")) {
                                jQuery("#distribution-feedback-options").removeClass("hide");
                            }
                        break;
                        case "delegation" :
                            jQuery("#distribution-delegation-warning").removeClass("hide");
                            jQuery("#eventtype-target-options").addClass("hide");
                            if (jQuery("#delegator_timeframe_rotation_schedule").attr("checked") == "checked") {
                                jQuery("#specific_dates_target_options").addClass("hide");
                                jQuery("#rotation_schedule_target_options").removeClass("hide");

                                if ((jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "individual_users") && jQuery("input[name=\"distribution_rs_target_option\"]:checked").val() === "learner") {
                                    jQuery("#distribution-feedback-options").removeClass("hide");
                                }
                            } else if (jQuery("#delegator_timeframe_date_range").attr("checked") == "checked") {
                                jQuery("#rotation_schedule_target_options").addClass("hide");
                                jQuery("#specific_dates_target_options").removeClass("hide");

                                if ((jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "individual_users") && (jQuery("input[name=\"distribution_target_option\"]:checked").val() === "grouped_users" || jQuery("input[name=\"distribution_target_option\"]:checked").val() === "individual_users")) {
                                    jQuery("#distribution-feedback-options").removeClass("hide");
                                }
                            }
                        break;
                        case "eventtype" :
                            jQuery("#eventtype-target-options").removeClass("hide");
                            jQuery("#non-cbme-targets-btn").attr("checked", "checked");
                        break;
                    }
                    break;

                case 4: /** Validation complete on Targets step, prepare for Assessors step **/
                    // Clear the previous step's additional target learners when necessary. We don't want to POST additional learners if we don't have to (this causes problems if we do).
                    if (jQuery('#distribution-rs-target-individual').attr("checked") == "checked" || jQuery('#distribution-rs-target-additional-learners').attr("checked") != "checked" ) {
                        jQuery("input[name='additional_target_learners[]']").remove();
                        jQuery("#distribution-rs-target-additional-learners").removeAttr("checked");
                    }

                    jQuery(".assessor-options").addClass("hide");
                    jQuery("#distribution-delegation-warning").addClass("hide");
                    var settings = jQuery("#choose-method-btn").data("settings");
                    var selected_method = settings.value;
                    jQuery("#distribution-feedback-options").addClass("hide");

                    if (jQuery("#assessment_type").val() == "assessment" && selected_method == "date_range") {
                        jQuery("#exclude_self_assessment_options").removeClass("hide");
                    } else {
                        jQuery("#exclude_self_assessment_options").addClass("hide");
                    }

                    switch (selected_method) {
                        case "rotation_schedule" :
                            jQuery("#rotation_schedule_assessor_options").removeClass("hide");
                            if ((jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "individual_users") && jQuery("input[name=\"distribution_rs_target_option\"]:checked").val() === "learner") {
                                jQuery("#distribution-feedback-options").removeClass("hide");
                            }
                            break;
                        case "date_range" :
                            jQuery("#specific_dates_assessor_options").removeClass("hide");
                            if ((jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "individual_users") && (jQuery("input[name=\"distribution_target_option\"]:checked").val() === "grouped_users" || jQuery("input[name=\"distribution_target_option\"]:checked").val() === "individual_users")) {
                                jQuery("#distribution-feedback-options").removeClass("hide");
                            }
                            break;
                        case "delegation" :
                            if (jQuery("#delegator_timeframe_rotation_schedule").attr("checked") == "checked") {
                                jQuery("#specific_dates_assessor_options").addClass("hide");
                                jQuery("#rotation_schedule_assessor_options").removeClass("hide");

                                if ((jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_rs_assessor_option\"]:checked").val() === "individual_users") && jQuery("input[name=\"distribution_rs_target_option\"]:checked").val() === "learner") {
                                    jQuery("#distribution-feedback-options").removeClass("hide");
                                }
                            } else if (jQuery("#delegator_timeframe_date_range").attr("checked") == "checked") {
                                jQuery("#rotation_schedule_assessor_options").addClass("hide");
                                jQuery("#specific_dates_assessor_options").removeClass("hide");

                                if ((jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "faculty" || jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "individual_users") && (jQuery("input[name=\"distribution_target_option\"]:checked").val() === "grouped_users" || jQuery("input[name=\"distribution_target_option\"]:checked").val() === "individual_users")) {
                                    jQuery("#distribution-feedback-options").removeClass("hide");
                                }
                            }

                            /*jQuery("#distribution-delegation-warning").removeClass("hide");
                            if (jQuery("#delegator_timeframe_rotation_schedule").attr("checked") == "checked") {
                                jQuery("#specific_dates_assessor_options").addClass("hide");
                                jQuery("#rotation_schedule_assessor_options").removeClass("hide");
                            } else if (jQuery("#delegator_timeframe_date_range").attr("checked") == "checked") {
                                jQuery("#rotation_schedule_assessor_options").addClass("hide");
                                jQuery("#specific_dates_assessor_options").removeClass("hide");
                            }

                            if (jQuery("input[name=\"distribution_assessor_option\"]:checked").val() === "faculty" && jQuery("input[name=\"distribution_target_option\"]:checked").val() === "grouped_users") {
                                jQuery("#distribution-feedback-options").removeClass("hide");
                            } else {
                                jQuery("#distribution-feedback-options").addClass("hide");
                            }*/
                            break;
                        case "eventtype" :
                            jQuery("#eventtype-assessor-options").removeClass("hide");
                            break;
                    }
                    break;


                case 5 : /** Validation completed on Assessors step, prepare for Results step **/

                    // Clear the previous step's additional learners when necessary. We don't want to POST additional learners if we don't have to (this causes problems if we do).
                    if (jQuery('#distribution-rs-assessor-individual').attr("checked") == "checked" || jQuery('#distribution-rs-additional-learners').attr("checked") != "checked" ) {
                        jQuery("input[name='additional_assessor_learners[]']").remove();
                        jQuery("#distribution-rs-additional-learners").removeAttr("checked");
                    }
                    break;

                case 6 : /** Steps complete **/

                    jQuery(".distribution-wizard-step-container").addClass("hide");
                    display_success([distribution_form.save_success], "#msgs");
                    setTimeout(function(){ window.location = jQuery("#distribution-cancel-close-btn").attr("href"); }, 5000);
                    jQuery("#distribution-cancel-close-btn").addClass("hide");
                    jQuery("#distribution-success-close-btn").removeClass("hide");
                    jQuery("#distribution-next-step").remove();
                    jQuery("#distribution-previous-step").remove();
                    break;

                default :
                    //jQuery("#distribution-next-step").html("Next");
                    break;
            }
            
            jQuery(".wizard-nav-item.active").removeClass("active");
            jQuery("#wizard-nav-item-" + next_step).addClass("active");
            
            jQuery("li[data-step=\""+ current_step +"\"]").addClass("complete");
            
            if (next_step > 1 && next_step < 6) {
                jQuery("#distribution-previous-step").removeClass("hide");
            }
            
            if (jsonResponse.data.hasOwnProperty("previous_step")) {
                jQuery("#wizard-previous-step-input").val(jsonResponse.data.previous_step);
            } else {
                jQuery("#wizard-previous-step-input").val("0");
            }
            
            jQuery("#wizard-step-input").val(next_step);
            jQuery(".wizard-step").addClass("hide");
            jQuery("#wizard-step-" + next_step).removeClass("hide");
            toggle_active_nav_item(next_step);
        } else {
            enable_wizard_controls();
            display_error(jsonResponse.data, "#msgs", "prepend");
        }
    });
}

function distribution_previous_step () {
    var step = parseInt(jQuery("#wizard-step-input").val());
    var previous_step = parseInt(jQuery("#wizard-previous-step-input").val());
    remove_errors();
    jQuery("#distribution-next-step").html("Next Step");

    if (previous_step !== 0) {
        var updated_step = previous_step;
        jQuery("#wizard-step-input").val(updated_step);
        jQuery(".wizard-step").addClass("hide");
        jQuery("#wizard-step-" + updated_step).removeClass("hide");
        jQuery("#wizard-previous-step-input").val("0");
        toggle_active_nav_item(updated_step);
    } else {
        if (step > 1) {
            var updated_step = step - 1;
            jQuery("#wizard-step-input").val(updated_step);
            jQuery(".wizard-step").addClass("hide");
            jQuery("#wizard-step-" + updated_step).removeClass("hide");
            toggle_active_nav_item(updated_step);
        }
    }
    
    var updated_step = parseInt(jQuery("#wizard-step-input").val());
    if (updated_step === 1) {
        jQuery("#distribution-previous-step").addClass("hide");
    }
}

function remove_errors () {
    jQuery("#msgs").empty();
}

function show_loading_msg () {
    disable_wizard_controls();
    jQuery("#distribution-loading-msg").html("Loading Distribution Options...");
    jQuery("#distribution-data-form").addClass("hide");
    jQuery("#distribution-loading").removeClass("hide");
}

function hide_loading_msg () {
    enable_wizard_controls();
    jQuery("#distribution-loading").addClass("hide");
    jQuery("#distribution-loading-msg").html("");
    jQuery("#distribution-data-form").removeClass("hide");
}

function enable_wizard_controls () {
    if (jQuery("#distribution-next-step").is(":disabled")) {
        jQuery("#distribution-next-step").removeAttr("disabled");
    }
    
    if (jQuery("#distribution-previous-step").is(":disabled")) {
        jQuery("#distribution-previous-step").removeAttr("disabled");
    }

    jQuery("#distribution-next-step").removeClass("hide");
    jQuery("#distribution-previous-step").removeClass("hide");

}

function disable_wizard_controls () {
    if (!jQuery("#distribution-next-step").is(":disabled")) {
        jQuery("#distribution-next-step").attr("disabled", "disabled");
    }
    
    if (!jQuery("#distribution-previous-step").is(":disabled")) {
        jQuery("#distribution-previous-step").attr("disabled", "disabled");
    }

    jQuery("#distribution-next-step").addClass("hide");
    jQuery("#distribution-previous-step").addClass("hide");
}

function populate_flagging_options() {
    if (!jQuery("input[name='course_id']").val()) {
        jQuery("#flagging_notifications [value='pcoordinators']").hide();
        jQuery("#flagging_notifications [value='directors']").hide();
    } else {
        jQuery("#flagging_notifications [value='pcoordinators']").show();
        jQuery("#flagging_notifications [value='directors']").show();
    }
    jQuery("#flagging_notifications [value='reviewers']").show();
}

function create_assessor_item(id, name, assessor_type, prefix) {
    var role_span               = jQuery(document.createElement("span")).addClass("pull-right selected-assessor-container");
    var role_label_span         = jQuery(document.createElement("span")).addClass("selected-assessor-label").html((assessor_type === "Internal" ? internal_assessor_label : external_assessor_label));
    var remove_assessor_span    = jQuery(document.createElement("span")).addClass("remove-selected-assessor").html("&times");
    var item                    = jQuery(document.createElement("li")).addClass("community internal-assessor-list-item internal-assessor-" + id).html(name).attr("data-id", id);
    var input                   = jQuery(document.createElement("input")).attr({id: "selected-internal-assessor-" + id, name: "selected_internal_assessors[]", type: "hidden", value: (assessor_type === "Internal" ? "internal_" : "external_") + id}).addClass("selected-internal-assessor-control");

    role_span.append(role_label_span).append(remove_assessor_span);
    item.append(role_span);

    jQuery("#"+prefix+"internal-assessors-list").append(item);
    jQuery("#"+prefix+"assessor-list-internal").removeClass("hide");

    // Only add it once
    if (jQuery("#selected-internal-assessor-"+id).length === 0) {
        jQuery("#distribution-data-form").append(input);
    }
}

function build_selected_assessor_item (id, name, assessor_type) {
    build_selected_assessor_list();

    if (jQuery("#internal-assessors-list .internal-assessor-" + id).length === 0) {
        create_assessor_item(id, name, assessor_type, "");
    }

    if (jQuery("#rs-internal-assessors-list .internal-assessor-" + id).length === 0) {
        create_assessor_item(id, name, assessor_type, "rs-");
    }
}

function build_selected_eventtype_assessor_item (id, name, assessor_type) {
    build_selected_eventtype_assessor_list();

    if (jQuery("#eventtype-internal-assessors-list .internal-assessor-" + id).length === 0) {
        create_assessor_item(id, name, assessor_type, "eventtype-");
    }
}

function build_selected_target_item (id, name, target_type) {
    build_selected_target_list();

    if (jQuery(".internal-target-" + id).length === 0) {

        var role_span               = jQuery(document.createElement("span")).addClass("pull-right selected-target-container");
        var role_label_span         = jQuery(document.createElement("span")).addClass("selected-target-label").html(target_type);
        var remove_target_span      = jQuery(document.createElement("span")).addClass("remove-selected-target").html("&times");
        var item                    = jQuery(document.createElement("li")).addClass("community internal-target-list-item internal-target-" + id).html(name).attr("data-id", id);
        var input                   = jQuery(document.createElement("input")).attr({id: "selected-internal-target-" + id, name: "selected_internal_targets[]", type: "hidden", value: id}).addClass("selected-internal-target-control");

        role_span.append(role_label_span).append(remove_target_span);
        item.append(role_span);

        jQuery("#internal-targets-list").append(item);
        jQuery("#target-list-internal").removeClass("hide");
        jQuery("#distribution-data-form").append(input);
    }
}

function build_selected_author_item (id, name, author_role, removable) {
    if (typeof removable == "undefined") removable = true;
    build_selected_author_list();

    if (jQuery(".selected-" + author_role + "-author-" + id).length === 0) {

        var role_span = jQuery(document.createElement("span")).addClass("pull-right selected-author-container");
        var label;
        var author_type_hidden;
        switch (author_role) {
            case "course" :
                label = course_author_label;
                author_type_hidden = "course";
                break;
            case "organisation" :
                label = organisation_author_label;
                author_type_hidden = "organisation";
                break;
            default :
                label = individual_author_label;
                author_type_hidden = "individual";
                break;
        }

        var role_label_span = jQuery(document.createElement("span")).addClass("selected-author-label-immutable").html((label));
        var remove_author_span = jQuery(document.createElement("span")).addClass("remove-selected-author").html("&times");
        var item = jQuery(document.createElement("li")).addClass("community authors-list-item selected-" + author_role + "-author-" + id).html(name).attr("data-id", id).attr("data-author-type", author_role);
        var input = jQuery(document.createElement("input")).attr({
            id: "selected-" + author_role + "-author-" + id,
            name: "selected_authors[]",
            type: "hidden",
            value: "author_" + author_type_hidden + "_" + id
        }).addClass("selected-author-control");

        role_span.append(role_label_span);
        if (removable) {
            role_label_span.removeClass("selected-author-label-immutable");
            role_label_span.addClass("selected-author-label");
            role_span.append(remove_author_span);
        }

        item.append(role_span);

        jQuery("#authors-list").append(item);
        jQuery("#author-list-section").removeClass("hide");
        jQuery("#distribution-data-form").append(input);
    }
}

function build_selected_assessor_list () {
    if (jQuery(".no-internal-assessors-msg").length > 0) {
        jQuery(".no-internal-assessors-msg").remove();
    }
    
    if (jQuery("#internal-assessors-list").length === 0) {
        var assessor_ul = jQuery(document.createElement("ul")).attr({id: "internal-assessors-list"}).addClass("internal-assessors-list menu");
        jQuery("#internal-assessors-list-container").append(assessor_ul);
    }
    if (jQuery(".rs-no-internal-assessors-msg").length > 0) {
        jQuery(".no-internal-assessors-msg").remove();
    }

    if (jQuery("#rs-internal-assessors-list").length === 0) {
        var assessor_ul = jQuery(document.createElement("ul")).attr({id: "rs-internal-assessors-list"}).addClass("internal-assessors-list menu");
        jQuery("#rs-internal-assessors-list-container").append(assessor_ul);
    }
}

function build_selected_eventtype_assessor_list () {
    if (jQuery(".no-internal-assessors-msg").length > 0) {
        jQuery(".no-internal-assessors-msg").remove();
    }

    if (jQuery("#eventtype-internal-assessors-list").length === 0) {
        var assessor_ul = jQuery(document.createElement("ul")).attr({id: "eventtype-internal-assessors-list"}).addClass("internal-assessors-list menu");
        jQuery("#eventtype-assessors-list-container").append(assessor_ul);
    }
    if (jQuery(".eventtype-no-internal-assessors-msg").length > 0) {
        jQuery(".no-internal-assessors-msg").remove();
    }
}

function build_selected_target_list () {
    if (jQuery(".no-internal-targets-msg").length > 0) {
        jQuery(".no-internal-targets-msg").remove();
    }

    if (jQuery(".internal-targets-list").length === 0) {
        var target_ul = jQuery(document.createElement("ul")).attr({id: "internal-targets-list"}).addClass("internal-targets-list menu");
        jQuery("#internal-targets-list-container").append(target_ul);
    }
    if (jQuery(".rs-no-internal-targets-msg").length > 0) {
        jQuery(".no-internal-targets-msg").remove();
    }

    if (jQuery(".rs-internal-targets-list").length === 0) {
        var target_ul = jQuery(document.createElement("ul")).attr({id: "rs-internal-targets-list"}).addClass("internal-targets-list menu");
        jQuery("#rs-internal-targets-list-container").append(target_ul);
    }
}

function build_selected_author_list () {
    if (jQuery(".authors-list").length === 0) {
        var author_ul = jQuery(document.createElement("ul")).attr({id: "authors-list"}).addClass("authors-list menu");
        jQuery("#authors-list-container").append(author_ul);
    }

    if (jQuery(".no-authors-msg").length > 0) {
        jQuery(".no-authors-msg").remove();
    }
}

function build_selected_external_assessor_item (id, firstname, lastname) {
    build_selected_external_assessor_list();
    
    if (jQuery(".internal-assessor-" + id).length === 0) {
        var item    = jQuery(document.createElement("li")).addClass("community internal-assessor-list-item internal-assessor-" + id + "").html(firstname + " " + lastname).attr("data-id", id);
        var input   = jQuery(document.createElement("input")).attr({id: "selected-internal-assessor-" + id, name: "selected_internal_assessors[]", type: "hidden", value: "external_" + id}).addClass("selected-internal-assessor-control");
        var img     = jQuery(document.createElement("img")).attr({src: ENTRADA_URL + "/images/action-delete.gif"}).addClass("list-cancel-image remove-external-assessor");
        
        item.append(img);
        
        jQuery("#internal-assessors-list").append(item);
        jQuery("#distribution-data-form").append(input);
    }
}

function build_selected_external_assessor_list () {
    if (jQuery(".no-external-assessors-msg").length > 0) {
        jQuery(".no-external-assessors-msg").remove();
    }
    
    if (jQuery(".external-assessors-list").length === 0) {
        var assessor_ul = jQuery(document.createElement("ul")).attr({id: "external-assessors-list"}).addClass("external-assessors-list menu");
        jQuery("#external-assessors-list-container").append(assessor_ul);
    }
}

function build_assessor_msg (target, msg) {
    if (jQuery(".internal-assessors-list").length > 0) {
        jQuery(".internal-assessors-list").remove();
    }
    
    var msg_div = jQuery(document.createElement("div")).addClass("no-internal-assessors-msg well well-small").html(msg);
    jQuery(target).append(msg_div);
}

function add_external_assessor (distribution_method) {
    switch (distribution_method) {
        case "rotation_schedule":
            var firstname = jQuery("#rs-assessor-firstname").val();
            var lastname  = jQuery("#rs-assessor-lastname").val();
            var email     = jQuery("#rs-assessor-email").val();
            break;
        case "date_range":
            var firstname = jQuery("#assessor-firstname").val();
            var lastname  = jQuery("#assessor-lastname").val();
            var email     = jQuery("#assessor-email").val();
            break;
        case "eventtype":
            var firstname = jQuery("#eventtype-assessor-firstname").val();
            var lastname  = jQuery("#eventtype-assessor-lastname").val();
            var email     = jQuery("#eventtype-assessor-email").val();
            break;
    }

    var add_external_assessor = jQuery.ajax({
        url: "?section=api-distributions",
        data: {
            "method": "add-external-assessor",
            "firstname": firstname,
            "lastname": lastname,
            "email": email
        },
        type: "POST"
    });

    jQuery.when(add_external_assessor).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            jQuery("#msgs").empty();
            jQuery("#external-assessors-controls").addClass("hide");
            jQuery("#rs-external-assessors-controls").addClass("hide");
            jQuery("#eventtype-external-assessors-controls").addClass("hide");

            switch (distribution_method) {
                case "rotation_schedule":
                case "date_range":
                    build_selected_assessor_item(jsonResponse.data.id, firstname + " " + lastname, "External");
                    break;
                case "eventtype" :
                    build_selected_eventtype_assessor_item(jsonResponse.data.id, firstname + " " + lastname, "External");
                    break;
            }

            reset_external_assessor_controls();

            jQuery("#assessor-lists").removeClass("hide");
            jQuery("#rs-assessor-lists").removeClass("hide");
        } else {
            display_error(jsonResponse.data, "#msgs", "prepend");
        }
    });
}

function build_external_assessors_button (rotation_schedule) {
    var rotation_schedule_addon = (typeof rotation_schedule == "undefined") ? "" : "rs-";

    var external_assessor_li = jQuery(document.createElement("li"));

    if (jQuery("#" + rotation_schedule_addon + "add-external-assessor-btn").length === 0) {
        var external_assessor_btn = jQuery(document.createElement("button")).attr({
            "id": rotation_schedule_addon + "add-external-assessor-btn",
            "type": "button"
        }).css("width", "100%").addClass("btn").html("<i class=\"icon-plus-sign\"></i> Add External Assessor");

        jQuery("#" + rotation_schedule_addon + "autocomplete-list-container ul").append(external_assessor_li.append(external_assessor_btn));
    }
}

function build_external_eventtype_assessors_button (rotation_schedule) {
    if (jQuery("#add-external-eventtype-assessor-btn").length === 0) {
        var add_external_assessor_a = jQuery(document.createElement("a"));
        add_external_assessor_a.html("<i class=\"icon-plus-sign\"></i> Add External Assessor").attr({
            id: "add-external-eventtype-assessor-btn",
            href: "#"
        });
        jQuery("#eventtype-autocomplete-list-container").append(add_external_assessor_a);
    }
}

function build_external_targets_button () {
    if (jQuery("#add-external-target-btn").length === 0) {
        var add_external_target_a = jQuery(document.createElement("a"));
        add_external_target_a.html("<i class=\"icon-plus-sign\"></i> Add External Target").attr({id: "add-external-target-btn", href: "#"});
        jQuery("#target-autocomplete").append(add_external_target_a);
    }
}

function reset_external_assessor_controls () {
    jQuery("#assessor-firstname").val("");
    jQuery("#assessor-lastname").val("");
    jQuery("#assessor-email").val("");
    jQuery("#rs-assessor-firstname").val("");
    jQuery("#rs-assessor-lastname").val("");
    jQuery("#rs-assessor-email").val("");
    jQuery("#eventtype-assessor-firstname").val("");
    jQuery("#eventtype-assessor-lastname").val("");
    jQuery("#eventtype-assessor-email").val("");
}

function toggle_active_nav_item (step) {
    jQuery(".wizard-nav-item").removeClass("active");
    jQuery("#wizard-nav-item-" + step).addClass("active");
}

function show_step (next_step) {
    update_step(next_step);
    remove_errors();
    toggle_active_nav_item (next_step)
    if (next_step < 5) {
        jQuery("#distribution-next-step").html("Next Step");
    }
    jQuery(".wizard-step").addClass("hide");
    jQuery("#wizard-step-" + next_step).removeClass("hide");
}

function update_step (step) {
    jQuery("#wizard-step-input").val(step);
}

function remove_filter (filter_type, filter_target) {
    var remove_filter_request = jQuery.ajax({
        url: "?section=api-distributions",
        data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
        type: "POST"
    });

    jQuery.when(remove_filter_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            var id_string = "#" + filter_type + "_" + filter_target
            jQuery(id_string).remove();
            jQuery("#search-targets-form").submit();
        }
    });
}

function remove_all_filters () {
    var remove_filter_request = jQuery.ajax({
        url: "?section=api-distributions",
        data: "method=remove-all-filters",
        type: "POST"
    });

    jQuery.when(remove_filter_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            jQuery("#search-targets-form").empty().submit();
        }
    });
}

function reset_target_attempt_options() {
    jQuery("#min_target_attempts").prop("disabled", true);
    jQuery("#max_target_attempts").prop("disabled", true);
    jQuery("#min_overall_attempts").prop("disabled", true);
    jQuery("#max_overall_attempts").prop("disabled", true);
    jQuery("#repeat-target-controls").addClass("hide");
}

/**
 * Remove checked state from checkboxes and radios of all children of parent container, and optionally clear text and/or select box elements.
 *
 * @param string element
 * @param bool ignore_texts
 * @oaran bool ignore_selects
 */
function clear_form_elements(element, ignore_texts, ignore_selects) {
    if (typeof ignore_texts == "undefined") ignore_texts = false;
    if (typeof ignore_selects == "undefined") ignore_selects = false;

    jQuery(':input', element ).each(function() {
        var type = this.type;
        var tag = this.tagName.toLowerCase(); // normalize case

        if (type == 'checkbox' || type == 'radio') {
            this.checked = false;
        } else if (tag == 'select') {
            if (!ignore_selects) {
                this.selectedIndex = -1;
            }
        } else if (type == 'text' || type == 'password' || tag == 'textarea') {
            if (!ignore_texts) {
                this.value = "";
            }
        }
    });
}

/**
 * Adjust assessors autocomplete windows (for delegation, rotation and date schedules) so that it doesn't inappropriately hang open when having changed distribution
 * method and then moving from step to step.
 */
function clear_assessor_autocomplete() {
    jQuery('#assessor-list-internal').addClass("hide");
    jQuery('#external-assessors-search').val('');
    jQuery('#autocomplete-list-container').find('ul').html('').css('display','none');
    jQuery('#add-external-assessor-btn').remove();

    jQuery('#rs-assessor-list-internal').addClass("hide");
    jQuery('#rs-external-assessors-search').val('');
    jQuery('#rs-autocomplete-list-container').find('ul').html('').css('display','none');
    jQuery('#rs-add-external-assessor-btn').remove();

    jQuery('#eventtype-assessor-list-internal').addClass("hide");
    jQuery('#eventtype-external-assessors-search').val('');
    jQuery('#eventtype-autocomplete-list-container').find('ul').html('').css('display','none');
    jQuery('#eventtype-add-external-assessor-btn').remove();
}

/**
 * Adjust the targets autocomplete so that it doesn't hang inappropriately when switching between steps.
 */
function clear_targets_autocomplete () {
    jQuery('#target-list-internal').addClass("hide");
    jQuery('#targets-search').val('');
    jQuery('#target-autocomplete-list-container').find('ul').html('').css('display','none');
}

/**
 * Clear and remove the form elements from distribution wizard based on what distribution method was selected. Reset the editor to ensure data input consistency.
 *
 * @param string new_distribution_method
 */
function distribution_method_changed (new_distribution_method) {
    var chevron = "<i class=\"icon-chevron-down btn-icon pull-right\"></i>";
    var rs_default_btn_text = jQuery('#rs-choose-rotation-btn-default-label').val();
    var d_default_btn_text = jQuery('#choose-delegator-btn-default-label').val();

    /** Reset step 2 **/
    jQuery('[name="schedule_id"]').remove();
    jQuery('[name="delegator_id"]').remove();
    jQuery('#choose-delegator-btn').html(d_default_btn_text + chevron);
    jQuery('#rs-choose-rotation-btn').html(rs_default_btn_text + chevron);

    jQuery('#distribution-rotation-delivery-options').addClass("hide");
    jQuery('#rotation-schedule-delivery-offset').addClass("hide");
    jQuery('#rotation-release-control').css("display", "none");
    jQuery('#rotation-release-option').removeAttr("checked");
    jQuery('#eventtype-release-option').removeAttr("checked");
    jQuery('input[name="schedule_delivery_type"]').removeAttr("checked");

    reset_wizard_step_2_visibility(new_distribution_method);
    reset_wizard_step_3(new_distribution_method);
    reset_wizard_step_4(new_distribution_method);

    /** Don't need to reset step 5 as its content is not necessarily dependent on the rest of the wizard. **/
}

/**
 * Clear the distribution wizard of delegation distribution method related variables on the form when the delegation is changed.
 *
 * @param string new_type
 * **/
function delegation_type_changed (new_type) {
    var chevron = "<i class=\"icon-chevron-down btn-icon pull-right\"></i>";
    var rs_default_btn_text = jQuery('#rs-choose-rotation-btn-default-label').val();

    if (new_type != "rotation_schedule") {
        jQuery('[name="schedule_id"]').remove();
    }
    jQuery('#rs-choose-rotation-btn').html(rs_default_btn_text + chevron);

    if (jQuery("input[name=\"cperiod_id\"]").length) {
        var selected_option = jQuery("input[name=\"cperiod_id\"]").val();
        var settings = jQuery("#rs-choose-rotation-btn").data("settings");
        settings.filters["rs_schedule"].api_params.cperiod_id = selected_option;
    }

    clear_form_elements('#wizard-step-2', true, true); // don't reset the select box

    jQuery("#delegator_timeframe_"+new_type).attr("checked","checked");

    reset_wizard_step_2_visibility(new_type);
    reset_wizard_step_3(new_type);
    reset_wizard_step_4(new_type);
}

/**
 * Get the distribution type based on what was given via parameter, or what distribution method was selected on the second step of the distribution wizard.
 * This takes delegation into account, and returns "date_range" or "rotation_schedule" (or "not_selected" on error).
 *
 * @param given_method_type
 * @returns {string}
 */
function get_selected_method_type (given_method_type) {
    var selected_method = "not_selected";

    if (typeof given_method_type !== "undefined" && given_method_type) {

        // For rotation and date range, just pass the value along, but for delegation, attempt to get the form value.
        if (given_method_type == "date_range" || given_method_type == "rotation_schedule") {
            selected_method = given_method_type;
        } else if (given_method_type == "delegation") {
            selected_method = jQuery('#selected-delegation-option').val();
        }

    } else {
        if (jQuery('[name="distribution_method"]').val() == "delegation") {
            selected_method = jQuery('#selected-delegation-option').val();
        } else {
            selected_method = jQuery('[name="distribution_method"]').val();
        }
    }

    return selected_method;
}

/**
 * Reset step 3 of the distribution editor wizard, clearing all related form variables and restoring default element states.
 *
 * @param string distribution_method
 */
function reset_wizard_step_3 (distribution_method) {
    var chevron = "<i class=\"icon-chevron-down btn-icon pull-right\"></i>";
    var tar_btn_default_text = jQuery("#choose-targets-btn-default-text").val();

    clear_form_elements('#wizard-step-3', true);

    jQuery('#choose-targets-btn').html(tar_btn_default_text + chevron);
    jQuery('[name="target_faculty[]"]').remove();
    jQuery('[name="target_cohort_id"]').remove();
    jQuery('[name="individual_target_learner[]"]').remove();
    jQuery('[name="additional_target_learners[]"]').remove();
    jQuery('[name="selected_internal_targets[]"]').remove();
    jQuery('[name="distribution_approvers[]"]').remove();
    jQuery(".selected-target-item").remove();

    jQuery('#distribution-feedback-options').addClass("hide");
    jQuery('#internal-targets-list').html('');

    jQuery('#specific_dates_target_options').addClass("hide");
    jQuery('#select-targets-grouped').addClass("hide");
    jQuery('#select-targets-faculty').addClass("hide");
    jQuery('#select-targets-course').addClass("hide");
    jQuery('#select-targets-individual').addClass("hide");

    jQuery('#repeat-targets').removeAttr("checked");
    jQuery('#approver-required').removeAttr("checked");
    jQuery('#repeat-target-controls').addClass("hide");

    jQuery('#rotation_schedule_target_options').addClass("hide");
    jQuery('#rs-target-faculty-options').addClass("hide");
    jQuery('#rs-target-individual-learners').addClass("hide");
    jQuery('#rs-target-additional-learners').addClass("hide");
    jQuery('#rs-target-learner-options').addClass("hide");
    jQuery('#rs-target-learner-individual').addClass("hide");
    jQuery('#rs-target-learner-service').addClass("hide");
    jQuery("#eventtype-target-selector").addClass("hide");
    jQuery('#select-approvers').addClass("hide");

    jQuery("#rs-target-external-options").addClass("hide");
    jQuery("#select-targets-external").addClass("hide");

    var selected_method = get_selected_method_type(distribution_method);
    if (selected_method == "date_range") {
        jQuery('#specific_dates_target_options').removeClass("hide");
    } else if (selected_method == "rotation_schedule") {
        jQuery('#rotation_schedule_target_options').removeClass("hide");
    }

    clear_targets_autocomplete();
}

/**
 * Reset step 4 of the distribution editor wizard, clearing all related form variables and restoring default element states.
 *
 * @param string distribution_method
 */
function reset_wizard_step_4 (distribution_method) {
    var chevron = "<i class=\"icon-chevron-down btn-icon pull-right\"></i>";
    var ca_btn_default_text = jQuery("#choose-assessors-btn-default-text").val();

    clear_form_elements('#wizard-step-4', true);

    // These are the variables added to the form via hidden elements when autocompletes and advancedSearch return.
    jQuery('[name="distribution_approvers[]"]').remove();
    jQuery('[name="additional_assessor_learners[]"]').remove();
    jQuery('[name="additional_assessor_eventtype_faculty[]"]').remove();
    jQuery('[name="additional_assessor_faculty[]"]').remove();
    jQuery('[name="assessor_cohort_id"]').remove();
    jQuery('[name="assessor_course_id"]').remove();
    jQuery('[name="assessor_cgroup_id"]').remove();
    jQuery('[name="assessor_faculty[]"]').remove();
    jQuery('#internal-assessors-list').html('');
    jQuery('#rs-internal-assessors-list').html('');
    jQuery('#eventtype-internal-assessors-list').html('');
    jQuery('[name="selected_internal_assessors[]"]').remove();
    jQuery(".selected-target-item").remove();

    jQuery('#choose-assessors-btn').html(ca_btn_default_text + chevron);
    jQuery('.eventtype-assessor-option').addClass("hide");
    jQuery('#rotation-release-control').css("display", "none");
    jQuery('#select-assessors-faculty').addClass("hide");
    jQuery('#select-assessors-grouped').addClass("hide");
    jQuery('#select-assessors-individual').addClass("hide");
    jQuery('#rs-assessor-learner-service').addClass("hide");
    jQuery('#rs-assessor-learner-individual').addClass("hide");
    jQuery('#rs-additional-learners').addClass("hide");
    jQuery('#rs-individual-learners').addClass("hide");
    jQuery('#rs-assessor-learner-options').addClass("hide");
    jQuery('#rs-assessor-faculty-options').addClass("hide");
    jQuery('#rs-select-assessors-individual').addClass("hide");
    jQuery('#distribution-feedback-options').addClass("hide");
    jQuery('#select-approvers').addClass("hide");
    jQuery('#approver-required').removeAttr("checked");
    jQuery('input[name="distribution_rs_assessor_option"]').removeAttr("checked");

    var selected_method = get_selected_method_type(distribution_method);
    if (selected_method == "date_range") {
        jQuery('#specific_dates_assessor_options').removeClass("hide");
    } else if (selected_method == "rotation_schedule") {
        jQuery('#rotation_schedule_assessor_options').removeClass("hide");
    }

    clear_assessor_autocomplete();
}

/**
 * Set the visibility of step 2 elements on the distribution wizard based on distribution method selection.
 *
 * @param string given_method
 */
function reset_wizard_step_2_visibility(given_method) {
    if (typeof given_method == "undefined") given_method = null;

    var form_distribution_method = jQuery('[name="distribution_method"]').val();
    var distribution_method = form_distribution_method;

    if (form_distribution_method == "delegation") {
        distribution_method = jQuery('[name="distribution_delegator_timeframe"]').val();
    }

    if (given_method) {
        distribution_method = given_method;
    }

    switch (distribution_method) {
        case "date_range":
            jQuery("#specific_dates_target_options").removeClass("hide");
            jQuery("#specific_dates_assessor_options").removeClass("hide");

            jQuery("#rotation_schedule_target_options").addClass("hide");
            jQuery("#rotation_schedule_assessor_options").addClass("hide");

            jQuery("#distribution-rotation-delivery-options").addClass("hide");
            break;

        case "rotation_schedule":
            jQuery("#specific_dates_target_options").addClass("hide");
            jQuery("#specific_dates_assessor_options").addClass("hide");

            jQuery("#rotation_schedule_assessor_options").removeClass("hide");
            jQuery("#rotation_schedule_target_options").removeClass("hide");

            jQuery("#rs-choose-rotation-btn").trigger("change");

            // Based on what other options have been selected, show the relevant elements

            var schedule_id = 0;
            if (typeof jQuery('input[name="schedule_id"]').val() != "undefined") {
                schedule_id = jQuery('input[name="schedule_id"]').val();
            }

            // If we have a schedule selected, show the delivery options container
            if (schedule_id) {
                jQuery('#distribution-rotation-delivery-options').removeClass("hide");
            } else {
                jQuery('#distribution-rotation-delivery-options').addClass("hide");
            }

            // If the cutoff date checkbox is ticked, show the date control
            if (jQuery('#rotation-release-option').attr("checked") == "checked") {
                jQuery('#rotation-release-control').css("display", "inline-block");
            } else {
                jQuery('#rotation-release-control').css("display", "none");
            }

            // if the delivery period radio has a checked value, show the timeline options
            jQuery('input[name="schedule_delivery_type"]').each( function(key, value) {
                if (jQuery(value).attr("checked") == "checked") {
                    jQuery('#rotation-schedule-delivery-offset').removeClass("hide");
                }
            });
            break;

        case "eventtype":
            if (jQuery('#eventtype-release-option').attr("checked") == "checked") {
                jQuery('#eventtype-release-control').css("display", "inline-block");
            } else {
                jQuery('#eventtype-release-control').css("display", "none");
            }
            break;

        default: // if not set, then leave the visibility as-is
            break;
    }
}