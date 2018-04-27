let control_flow = {

    // Controls without dependencies that are always displayed.
    "course": {
        control: "course",
        next: ["target"]
    },
    "start_date": {
        control: "start_date",
        next: ["target"]
    },
    "end_date": {
        control: "end_date",
        next: ["target"]
    },
    "target": {
        control: "target",
        next: ["form", "distribution"]
    },

    // Controls that depend on the above entries.

    // Results set modifiers.
    "form": {
        control: "form",
        next: ["generate", "report_options"]
    },
    "distribution": {
        control: "distribution",
        next: ["generate", "report_options"]
    },

    // Report output modifiers.
    "report_options": {
        control: "report_options",
        next: ["include_comments", "include_description", "include_statistics"]
    },
    "include_comments": {
        control: "include_comments",
        next: ["include_commenter_id", "include_commenter_name"]
    },
    "include_commenter_id": {
        control: "include_commenter_id"
    },
    "include_commenter_name": {
        control: "include_commenter_name"
    },
    "include_description": {
        control: "include_description",
        next: ["description_text"]
    },
    "description_text": {
        control: "description_text"
    },
    "include_statistics": {
        control: "include_statistics",
        next: ["include_positivity"]
    },
    "include_positivity": {
        control: "include_positivity"
    },

    // Final submission.
    "generate": {
        control: "generate",
        api_params: {                       // API params will be stored over the course of data selection for final submission.
            "target_type": "proxy_id",      // Target type is proxy_id.
            "include_comments": true,       // Page defaults to having comments enabled.
            "is_evaluation": false
        }
    }
};

jQuery(document).ready(function ($) {

    /* We might trigger a date range preference update on page load to ensure that dates are applied to all
     controls, because the dates may have been loaded by user preference during PHP markup generation. */
    if ($("#report_start_date").val() || $("#report_end_date").val()) {
        setDateRangePreferences($("#report_start_date").val(), $("#report_end_date").val());
    }

    /**
     * Controls with class "report-control" correspond to entries in control_flow. They should be handled using onChange().
     */
    $(".report-control").on("change", function (e) {
        e.preventDefault();
        handleChange($(this).data("control"));
    });

    /**
     * Controls with class "report-control" correspond to entries in control_flow. They should be handled using onClick().
     */
    $(".report-control").on("click", function (e) {
        handleClick($(this).data("control"));
    });

    /**
     * Spans with class "remove-selected-list-item" and "remove-target-toggle" correspond to removing data from
     * advancedSearch entries in control_flow. They should trigger the corresponding filter's onChange().
     */
    $(document).on("click", "span.remove-selected-list-item, span.remove-target-toggle", function (e) {
        e.preventDefault();
        if ($(this).data("filter").length) {
            handleChange($(this).data("filter"));
        }
    });

    /**
     * Final submission logic, hit API with all API parameters that have been added over the course of the page.
     */
    $("#generate-pdf-btn").on("click", function (e) {
        e.preventDefault();
        let parameters_string = "";

        // Add any parameters that were stored for submission.
        $.each(control_flow["generate"].api_params, function (param_name, param_value) {
            if ($.isArray(param_value)) {
                $(param_value).each(function (i, v) {
                    parameters_string += "&" + param_name + "[]=" + v;
                });
            } else {
               parameters_string += "&" + param_name + "=" + param_value;
            }
        });

        if (parameters_string) {
            window.location = ENTRADA_URL + "/admin/assessments?section=api-assessment-reports&method=generate-bulk-pdf-reports&reviewer_only=true" + parameters_string;
        }
    });

    /**
     * onChange() event handler.
     *
     * @param control
     */
    function handleChange(control) {
        switch (control) {
            case "include_comments":
            case "include_commenter_id":
            case "include_commenter_name":
            case "include_description":
            case "include_statistics":
            case "include_positivity":
                break;
            case "start_date":
            case "end_date":
                setDateRangePreferences($("#report_start_date").val(), $("#report_end_date").val());
            default:
                configureDependantControls(control);
                if (requirementsMet(control)) {
                    revealNext(control);
                }
                break;
        }
    }

    /**
     * onClick() event handler.
     *
     * @param control
     */
    function handleClick(control) {
        switch (control) {
            case "include_comments":
            case "include_description":
            case "include_commenter_id":
            case "include_commenter_name":
            case "include_statistics":
            case "include_positivity":
                configureDependantControls(control);
                if (requirementsMet(control)) {
                    revealNext(control);
                }
                break;
            default:
                break;
        }
    }

    /**
     * Check controls for the custom defined requirements.
     *
     * @param control
     * @return boolean
     */
    function requirementsMet(control) {
        switch (control) {
            case "course":
            case "start_date":
            case "end_date":
                // Course, start date and/or end date must be set to proceed.
                if ($("input[name=\"course[]\"]").length && ($("#report_start_date").val() || $("#report_end_date").val())) {
                    return true;
                }
                break;
            case "target":
                // We must have selected targets to proceed.
                if ($("input[name=\"target[]\"]").length) {
                    return true;
                }
                break;
            case "form":
            case "distribution":
                // Forms and distributions are required.
                if ($("input[name=\"form[]\"]").length && $("input[name=\"distribution[]\"]").length) {
                    return true;
                }
                break;
            case "include_comments":
            case "include_description":
            case "include_statistics":
                if ($("#" + control).is(":checked")) {
                    return true;
                }
                break;
            default:
                return true;
                break;
        }
        return false;
    }

    /**
     * Move to the next control(s), as defined in the "next" property of the matching control flow entry.
     *
     * @param control
     */
    function revealNext(control) {
        switch (control) {
            default:
                $(control_flow[control].next).each(function (i, next_control) {
                    if (next_control in control_flow) {
                        switch (next_control) {
                            case "report_options":
                                $("#report_options_div").removeClass("hide");
                                if (requirementsMet(next_control)) {
                                    revealNext(next_control);
                                }
                                break;
                            case "include_comments":
                                if (requirementsMet(next_control)) {
                                    revealNext(next_control);
                                }
                                break;
                            case "include_commenter_id":
                                $("#commenter_id_controls").removeClass("hide");
                                break;
                            case "include_commenter_name":
                                $("#commenter_name_controls").removeClass("hide");
                                break;
                            case "description_text":
                                $("#description_text").removeClass("hide");
                                break;
                            case "include_positivity":
                                $("#include_positivity_controls").removeClass("hide");
                                break;
                            case "generate":
                                $("#generate-pdf-btn").removeClass("hide");
                                break;
                            default:
                                $("#select_" + next_control + "_div").removeClass("hide");
                                if (requirementsMet(next_control)) {
                                    revealNext(next_control);
                                }
                                break;
                        }
                    }
                });
                break;
        }
    }

    /**
     * Hide the next control(s), as defined in the "next" property of the matching control flow entry.
     *
     * @param control
     */
    function hideNext(control) {
        switch (control) {
            default:
                $(control_flow[control].next).each(function (i, next_control) {
                    if (next_control in control_flow) {
                        switch (next_control) {
                            case "report_options":
                                $("#report_options_div").addClass("hide");
                                hideNext(next_control);
                                break;
                            case "include_commenter_id":
                                $("#commenter_id_controls").addClass("hide");
                                hideNext(next_control);
                                break;
                            case "include_commenter_name":
                                $("#commenter_name_controls").addClass("hide");
                                hideNext(next_control);
                                break;
                            case "description_text":
                                $("#description_text").addClass("hide");
                                hideNext(next_control);
                                break;
                            case "include_positivity":
                                $("#include_positivity_controls").addClass("hide");
                                hideNext(next_control);
                                break;
                            case "generate":
                                $("#generate-pdf-btn").addClass("hide");
                                break;
                            default:
                                $("#select_" + next_control + "_div").addClass("hide");
                                hideNext(next_control);
                                break;
                        }
                    }
                });
                break;
        }
    }

    /**
     * Traverse up the control flow from the provided control and set any dependant control states as needed.
     *
     * @param control
     */
    function configureDependantControls(control) {
        let submission_values = {};
        let is_checked = null;

        let start_date = $("#report_start_date").val();
        let end_date = $("#report_end_date").val();

        switch (control) {
            case "course":
                // Course influences almost everything.
                let course_ids = [];
                $("input[name=\"course[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        course_ids.push($(v).val());
                    }
                });

                // Set as an API parameter on dependant controls.
                $(control_flow[control].next).each(function (i, next_control) {
                    if (next_control in control_flow) {
                        setAdvancedSearchApiParameter(next_control, next_control, "course_ids", course_ids);
                        // The course will be applied all the way up.
                        configureDependantControls(next_control);
                    }
                });

                submission_values["course_ids"] = course_ids;

                if (course_ids.length < 1) {
                    hideNext(control);
                }
                break;

            case "start_date":
                // Start date and/or end date influence almost everything.
                // Set as an API parameter on dependant controls.
                $(control_flow[control].next).each(function (i, next_control) {
                    console.log(next_control);
                    if (next_control in control_flow) {
                        setAdvancedSearchApiParameter(next_control, next_control, "start_date", start_date);
                        // Start date will be applied all the way up.
                        configureDependantControls(next_control);
                    }
                });

                submission_values["start_date"] = start_date;

                if (start_date.length < 1 && end_date.length < 1) {
                    hideNext(control);
                }
                break;

            case "end_date":
                // Start date and/or end date influence almost everything.
                // Set as an API parameter on dependant controls.
                $(control_flow[control].next).each(function (i, next_control) {
                    if (next_control in control_flow) {
                        setAdvancedSearchApiParameter(next_control, next_control, "end_date", end_date);
                        // End date will be applied all the way up.
                        configureDependantControls(next_control);
                    }
                });

                submission_values["end_date"] = end_date;

                if (start_date.length < 1 && end_date.length < 1) {
                    hideNext(control);
                }
                break;

            case "target":
                // Form fetching API method is generic and receives target type and IDs.
                setAdvancedSearchApiParameter("form", "form", "target_type", "proxy_id");
                let target_ids = [];
                $("input[name=\"target[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        target_ids.push($(v).val());
                    }
                });

                // Target type is proxy_id.
                setAdvancedSearchApiParameter("form", "form", "target_type", "proxy_id");
                setAdvancedSearchApiParameter("distribution", "distribution", "target_type", "proxy_id");

                // Filter the form and distribution data set to the target targets chosen.
                setAdvancedSearchApiParameter("form", "form", "target_ids", target_ids);
                setAdvancedSearchApiParameter("distribution", "distribution", "target_ids", target_ids);

                submission_values["target_type"] = "proxy_id";
                // Targets are the final targets for this report.
                submission_values["target_ids"] = target_ids;

                if (target_ids.length < 1) {
                    hideNext(control);
                }
                break;

            case "form":
                let form_ids = [];
                $("input[name=\"form[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        form_ids.push($(v).val());
                    }
                });

                setAdvancedSearchApiParameter("distribution", "distribution", "form_ids", form_ids);
                submission_values["form_ids"] = form_ids;

                if (form_ids.length < 1) {
                    hideNext(control);
                }
                break;

            case "distribution":
                let distribution_ids = [];
                $("input[name=\"distribution[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        distribution_ids.push($(v).val());
                    }
                });

                setAdvancedSearchApiParameter("form", "form", "distribution_ids", distribution_ids);
                submission_values["distribution_ids"] = distribution_ids;

                if (distribution_ids.length < 1) {
                    hideNext(control);
                }
                break;

            case "include_comments":
                is_checked = !!$("#include_comments").is(":checked");
                submission_values["include_comments"] = is_checked;
                if (!is_checked) {
                    hideNext(control)
                }
                break;

            case "include_commenter_id":
                is_checked = !!$("#include_commenter_id").is(":checked");
                submission_values["include_commenter_id"] = is_checked;
                if (!is_checked) {
                    hideNext(control)
                }
                break;

            case "include_commenter_name":
                is_checked = !!$("#include_commenter_name").is(":checked");
                submission_values["include_commenter_name"] = is_checked;
                break;

            case "include_description":
                is_checked = !!$("#include_description").is(":checked");
                submission_values["include_description"] = is_checked;
                submission_values["description"] = $("#description_text").val();
                if (!is_checked) {
                    hideNext(control)
                }
                break;

            case "description_text":
                submission_values["description"] = $("#description_text").val();
                break;

            case "include_statistics":
                is_checked = !!$("#include_statistics").is(":checked");
                submission_values["include_statistics"] = is_checked;
                if (!is_checked) {
                    hideNext(control)
                }
                break;

            case "include_positivity":
                is_checked = !!$("#include_positivity").is(":checked");
                submission_values["include_positivity"] = is_checked;
                if (!is_checked) {
                    hideNext(control)
                }
                break;

            default:
                break;
        }

        // The final submission will want essentially all of the parameters that we just configured, so store it now.
        $.each(submission_values, function (i, v) {
            setControlApiParameter("generate", i, v);
        });
    }

    /**
     * Set the date user preferences via the API. Add resulting cperiods as API parameters for future use.
     *
     * @param start_date
     * @param end_date
     */
    function setDateRangePreferences(start_date, end_date) {
        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-assessment-reports",
            data: {
                "method": "set-date-range",
                "start_date": start_date,
                "end_date": end_date,
                "current_page": $("#current-page").val()
            },
            type: "POST",
            success: function (data) {
                let cperiod_ids = [];

                let jsonResponse = safeParseJson(data, javascript_translations.cperiod_error);
                if (jsonResponse.status === "success") {
                    $.each(jsonResponse.data, function (key, v) {
                        cperiod_ids.push(key);
                    });

                    // Set as an API parameter on all controls.
                    $.each(control_flow, function (i, control_object) {
                        if ($("#select_" + control_object.control + "_btn").length) {
                            setAdvancedSearchApiParameter(control_object.control, control_object.control, "cperiod_ids", cperiod_ids);
                            setAdvancedSearchApiParameter(control_object.control, control_object.control, "start_date", start_date);
                            setAdvancedSearchApiParameter(control_object.control, control_object.control, "end_date", end_date);
                        }
                    });

                    // Final submission will need cperiods and dates as well.
                    setControlApiParameter("generate", "cperiod_ids", cperiod_ids);
                    setControlApiParameter("generate", "start_date", start_date);
                    setControlApiParameter("generate", "end_date", end_date);
                }
            }
        });
    }

    /**
     * Add the provided parameter to the specified advancedSearch control filter.
     *
     * @param control
     * @param filter
     * @param parameter_name
     * @param parameter_value
     */
    function setAdvancedSearchApiParameter(control, filter, parameter_name, parameter_value) {
        let settings = $("#select_" + control + "_btn").data("settings");
        settings.filters[filter].api_params[parameter_name] = parameter_value;
    }

    /**
     * Add a key/value pair to the API parameters of the final submission controls.
     *
     * @param control
     * @param parameter_name
     * @param parameter_value
     */
    function setControlApiParameter(control, parameter_name, parameter_value) {
        if (control in control_flow) {
            // Create the API parameters object if it does not already exist.
            if (!("api_params" in control_flow[control])) {
                control_flow[control]["api_params"] = {};
            }
            control_flow[control].api_params[parameter_name] = parameter_value;
        }
    }

});