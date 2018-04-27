let control_flow = {

    // Controls without dependencies that are always displayed.
    "course": {
        control: "course",
        next: ["course_group", "learner"]
    },
    "start_date": {
        control: "start_date",
        next: ["course_group", "learner"]
    },
    "end_date": {
        control: "end_date",
        next: ["course_group", "learner"]
    },

    // Controls that depend on the above entries.
    "course_group": { // Optional
        control: "course_group",
        next: ["learner"] // Group selection modifies the learner data source.
    },
    "learner": {
        control: "learner",
        next: ["form", "generate"]
    },
    "form": { // Optional
        control: "form",
        next: ["generate"]
    },
    "generate": {
        control: "generate",
        api_params: {} // API params will be stored over the course of data selection for final submission.
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

        /* pdf-download.js assumes we want to submit what is contained in the PDF modal, so we need to build out
         the parameters as elements of the form */
        let inputs_div = $("#additional-pdf-form-data");

        // Clean slate.
        inputs_div.empty();

        // API method.
        inputs_div.append("<input type='hidden' name='method' class='hide' value='generate-pdf-for-tasks-bulk'/>");

        /* Add any parameters that were stored for submission. This mimics the advancedSearch format that the API expects
         of concatenated comma separated values rather than distinct inputs for each value. */
        $.each(control_flow["generate"].api_params, function (param_name, param_value) {
            if ($.isArray(param_value)) {
                inputs_div.append("<input type='hidden' name='" + param_name + "' class='hide' value='" + param_value.join(",") + "'/>");
            } else {
                inputs_div.append("<input type='hidden' name='" + param_name + "' class='hide' value='" + param_value + "'/>");
            }
        });
    });

    /**
     * onChange() event handler.
     *
     * @param control
     */
    function handleChange(control) {
        switch (control) {
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
            case "learner":
                // We must have selected learners to proceed.
                if ($("input[name=\"learner[]\"]").length) {
                    return true;
                }
                break;
            case "course_group":
            // Group is an optional modifier for the learner data source.
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
                setAdvancedSearchApiParameter("form", "form", "course_ids", course_ids);

                submission_values["course_ids"] = course_ids;

                if (course_ids.length < 1) {
                    hideNext(control);
                }
                break;

            case "start_date":
                // Start date and/or end date influence almost everything.
                // Set as an API parameter on dependant controls.
                $(control_flow[control].next).each(function (i, next_control) {
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

            case "course_group":
                // Group will optionally limit the results of the learner API call.
                let course_group_ids = [];
                $("input[name=\"course_group[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        course_group_ids.push($(v).val());
                    }
                });
                setAdvancedSearchApiParameter("learner", "learner", "course_group_ids", course_group_ids);
                configureDependantControls("learner");
                break;

            case "learner":
                // Form fetching API method is generic and receives target type and IDs.
                setAdvancedSearchApiParameter("form", "form", "target_type", "proxy_id");
                let learner_ids = [];
                $("input[name=\"learner[]\"]").each(function (i, v) {
                    if ($(v).val() > 0) {
                        learner_ids.push($(v).val());
                    }
                });
                setAdvancedSearchApiParameter("form", "form", "target_ids", learner_ids);

                submission_values["target_type"] = "proxy_id";
                submission_values["target_ids"] = learner_ids;

                if (learner_ids.length < 1) {
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
                submission_values["form_ids"] = form_ids;
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