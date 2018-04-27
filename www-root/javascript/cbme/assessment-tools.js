jQuery(document).ready(function ($) {
    var can_trigger_assessment = true;
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: "",
        maxDate: ""
    });

    $(".add-on").on("click", function () {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    $("#complete_and_confirm_by_pin").attr("disabled", true);
    $("#complete_and_confirm_by_pin").next(".assessment-type-title").addClass("muted");

    var previewLightbox = $("#assessment-tool-form-preview").dialog({
        draggable: false,
        resizable: false,
        autoOpen: false,
        dialogClass: "preview-dialog-container",
        buttons: [{
            "text": "OK",
            "click": function () {
                $(this).dialog("close");
            }
        }],
        open: function () {
            $("body").css({ overflow: "hidden" });
        },
        beforeClose: function () {
            $("body").css({ overflow: "inherit" });
        },
        close: function () {
            $("#assessment-tool-form-preview").empty();
        }
    });

    previewLightbox.dialog("option", "title", "<h1>Form Preview</h1>");
    previewLightbox.on("dialogopen", function () {
        previewLightbox.dialog("widget").css("opacity", "1");
    });
    previewLightbox.on("dialogclose", function () {
        previewLightbox.dialog("widget").css("opacity", "0");
    });

    function tool_selection(trigger_action, form_id, assessor_value, assessment_method_id, target_record_id, course_id, encounter_date, assessment_cue, referrer) {
        switch (trigger_action) {
            case "begin":
                if (can_trigger_assessment) {
                    can_trigger_assessment = false;
                    var assessment_tool_request = $.ajax({
                        url: ENTRADA_URL + "/assessments?section=api-assessments",
                        type: "POST",
                        data: {
                            method: "trigger-assessment",
                            "form_id": form_id,
                            "assessor_value": assessor_value,
                            "assessment_method_id": assessment_method_id,
                            "target_record_id": target_record_id,
                            "course_id": course_id,
                            "encounter_date": encounter_date,
                            "assessment_cue": assessment_cue,
                            "referrer": referrer
                        },
                        beforeSend: function () {

                        },
                        complete: function () {

                        },
                        error: function () {

                        }
                    });
                    $.when(assessment_tool_request).done(function (data) {
                        var jsonResponse = safeParseJson(data, "No assessment tools found.");
                        if (jsonResponse.status === "success") {
                            window.location.replace(jsonResponse.data.url);
                        } else {
                            can_trigger_assessment = true;
                            $("#assessment-tool-msgs").empty();
                            display_error(jsonResponse.data, "#assessment-tool-msgs");
                            $("#assessment-tool-msgs").removeClass("hide");

                            if ($("#assessment-tool-picker-modal").length > 0) {
                                if ($("#assessment-tool-picker-modal").attr("aria-hidden") == "false") {
                                    $("#assessment-tool-msgs").empty();
                                    display_error(jsonResponse.data, "#modal-error-msgs");
                                }
                            }
                            window.scrollTo(0, 0);
                        }
                    });
                }
                break;
            case "preview":


            
                break;
            case "deliver":
                // Create an assessment for the attending
                if (can_trigger_assessment) {
                    can_trigger_assessment = false;
                    var assessment_tool_request = $.ajax({
                        url: ENTRADA_URL + "/assessments?section=api-assessments",
                        type: "POST",
                        data: {
                            method: "trigger-assessment",
                            "form_id": form_id,
                            "assessor_value": assessor_value,
                            "assessment_method_id": assessment_method_id,
                            "target_record_id": target_record_id,
                            "course_id": course_id,
                            "encounter_date": encounter_date
                        },
                        beforeSend: function () {

                        },
                        complete: function () {

                        },
                        error: function () {

                        }
                    });
                    $.when(assessment_tool_request).done(function (data) {
                        var jsonResponse = safeParseJson(data, "No assessment tools found.");
                        if (jsonResponse.status === "success") {
                            window.location = ENTRADA_URL + "/admin/assessments/tools?section=success";
                        } else {
                            can_trigger_assessment = true;
                            $("#assessment-tool-msgs").empty();
                            display_error(jsonResponse.data, "#assessment-tool-msgs");
                            $("#assessment-tool-msgs").removeClass("hide");
                        }
                    });
                }
                break;
            default:
                alert("Unrecognized action.");
                break;
        }
    }

    $("#assessment-tool-form").on("click", ".assessment-tool-btn", function (e) {
        e.preventDefault();
        $("#modal-error-msgs").empty();
        var encounter_date = $("input[name=\"date_of_encounter\"]").val();
        var assessor_value = $("input[name=\"attending\"]").val();
        var assessment_method_id = $("input[name=\"assessment_method\"]:checked").val();
        var target_record_id = $("input[name=\"target_record_id\"]").val();
        var form_id = $(this).attr("data-form-id");
        var form_type = $(this).attr("data-form-type");
        var course_id = $("[name=\"course_id\"]").val();
        var trigger_action = $(this).data("trigger-action");
        var form_count = $(this).attr("data-form-count");
        var epa_objective_id = $(this).attr("data-epa-objective-id");
        var assessment_cue = $("#assessment-cue-text").val();
        var referrer = $("input[name=\"referrer\"]").val();
        if (parseInt(form_count) > 1) {
            $("#assessment-cue-modal").modal("hide");
            $("#assessment-tool-picker-modal").modal("show");
            $("#assessment-tool-picker-form-type-label").html(form_type);

            // Load the tool picker variables (these all remain the same for each form, only need to be loaded one time)
            // The form-id is passed along via the selection after load template has executed.
            $("#assessment-tool-picker-trigger-action").val(trigger_action);
            $("#assessment-tool-picker-assessor-value").val(assessor_value);
            $("#assessment-tool-picker-assessment-method-id").val(assessment_method_id);
            $("#assessment-tool-picker-target-record-id").val(target_record_id);

            var assessment_tool_request = $.ajax({
                url: ENTRADA_URL + "/assessments?section=api-assessments&method=get-assessment-procedure-tools"
                + "&form_id=" + form_id
                + "&objective_id=" + epa_objective_id,
                type: "GET",
                beforeSend: function () {
                    $("#tool-picker-loading").removeClass("hide");
                    $("#assessment-tool-picker-container").addClass("hide");
                },
                complete: function () {
                    $("#tool-picker-loading").addClass("hide");
                    $("#assessment-tool-picker-container").removeClass("hide");
                },
                error: function () {
                    $("#tool-picker-loading").addClass("hide");
                    $("#assessment-tool-picker-container").removeClass("hide");
                }
            });
            $.when(assessment_tool_request).done(function (data) {
                $("#assessment-tool-picker-list").empty();
                var jsonResponse = safeParseJson(data, "No assessment tools found.");
                if (jsonResponse.status === "success") {
                    $("#tool-picker-loading-msg").addClass("hide");
                    $.each(jsonResponse.data, function (i, assessment_tool) {
                        var form_id = assessment_tool.form_id;
                        var tool_selector = "procedure-tool-" + form_id;
                        var tool_form_input_name = "procedure_tool_selection";
                        $("<li/>").loadTemplate(
                            "#assessment-tool-picker-list-item-template", {
                                tpl_tool_title: assessment_tool.objective_name,
                                /*tpl_tool_description: assessment_tool.tool_description,*/
                                tpl_tool_selector: tool_selector,
                                tpl_tool_selection: tool_form_input_name,
                                tpl_form_id: assessment_tool.form_id
                            }
                        ).appendTo("#assessment-tool-picker-list");
                    });
                } else {
                    display_error(jsonResponse.data, "#tool-picker-loading-msg");
                    $("#tool-picker-loading-msg").removeClass("hide");
                }
            });

        } else {
            $("#assessment-tool-msgs").empty();
            tool_selection(trigger_action, form_id, assessor_value, assessment_method_id, target_record_id, course_id, encounter_date, assessment_cue, referrer);
        }
    });

    /**
     * Modal is open, selection is made (tool is selected)
     */
    $("#assessment-tool-selected").on("click", function (e) {
        e.preventDefault();
        var encounter_date = $("input[name=\"date_of_encounter\"]").val();
        var assessment_cue = $("#assessment-cue-text").val();
        var referrer = $("input[name=\"referrer\"]").val();
        tool_selection(
            $("#assessment-tool-picker-trigger-action").val(),
            $('input[name="procedure_tool_selection"]:checked').attr('data-form-id'), // use the currently selected procedure
            $("#assessment-tool-picker-assessor-value").val(),
            $("#assessment-tool-picker-assessment-method-id").val(),
            $("#assessment-tool-picker-target-record-id").val(),
            $("[name=\"course_id\"]").val(),
            encounter_date,
            assessment_cue,
            referrer
        );
    });


    $("#select-epa-btn").advancedSearch({
        filters: {
            epa: {
                label: "EPA",
                data_source: JSON.parse(course_epas),
                mode: "radio",
                selector_control_name: "course_epa",
                search_mode: false
            }
        },
        control_class: "course-epa-selector",
        no_results_text: "No EPAs found",
        parent_form: $("#assessment-tool-form"),
        width: 400,
        modal: false
    });

    var epa_btn_settings = $("#select-epa-btn").data("settings");
    if (typeof preset_filters != "undefined") {
        epa_btn_settings.filters.epa["filter_presets"] = JSON.parse(preset_filters);
    }

    $("#select-attending-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filters: {
            attending: {
                label: "Assessor",
                data_source: "get-residents-and-faculty",
                mode: "radio",
                selector_control_name: "attending",
                search_mode: false
            }
        },
        control_class: "attending-selector",
        lazyload: true,
        no_results_text: "No Results found",
        parent_form: $("#assessment-tool-form"),
        width: 300,
        modal: false,
        user_card: true
    });

    $("#select-epa-btn").on("change", function (e) {
        var selected_epa = $("input[name=\"course_epa\"]").val();
        var course_id = $("[name=\"course_id\"]").val();
        var subject_id = $("input[name='target_record_id']").val();
        var assessment_tool_request = $.ajax({
            url: ENTRADA_URL + '/assessments?section=api-assessments&method=get-assessment-tools&node_id=' + selected_epa + "&course_id=" + course_id + "&subject_id=" + subject_id,
            type: "GET",
            beforeSend: function () {
                $("#assessment-tool-loading").removeClass("hide");
            },
            complete: function () {
                $("#assessment-tool-loading").addClass("hide");
            },
            error: function () {
                $("#assessment-tool-loading").addClass("hide");
            }
        });

        $.when(assessment_tool_request).done(function (data) {
            $("#assessment-tool-list").empty();
            var jsonResponse = safeParseJson(data, "No assessment tools found.");
            if (jsonResponse.status == "success") {
                $("#assessment-tool-msgs").addClass("hide");
                    $.each(jsonResponse.data, function (i, assessment_tool) {
                        $(".epa-help").attr('href', ENTRADA_URL + "/cbme/encyclopedia?objective_id=" + assessment_tool.objective_id + "&course_id=" + course_id);
                        $("<li/>").loadTemplate(
                            "#assessment-tool-template", {
                                form_id: assessment_tool.form_id,
                                epa_objective_id: assessment_tool.objective_id,
                                title: assessment_tool.title,
                                form_type: assessment_tool.form_type_title,
                                completed_count: assessment_tool.completed_count,
                                form_average_time: assessment_tool.average_time,
                                form_count: assessment_tool.form_count
                            }
                        ).appendTo("#assessment-tool-list");
                    });
                $("#assessment-tools").removeClass("hide");
                $("input[name='assessment_method']").change();
            } else {
                if (e.currentTarget.id === "select-epa-btn") {
                    display_error(jsonResponse.data, "#assessment-tool-msgs");
                    $("#assessment-tool-msgs").removeClass("hide");
                }
            }
        });
    });

    $("input[name='assessment_method']").on("change", function() {
        if ($("input[name=\"assessment_method\"]:checked").attr("data-shortname") === "send_blank_form") {
            $(".trigger-assessment-template-btn").addClass("hide");
            $(".send-assessment-template-btn").removeClass("hide");
        } else {
            $(".trigger-assessment-template-btn").removeClass("hide");
            $(".send-assessment-template-btn").addClass("hide");
        }
    });

    $("#select-attending-btn").on("change", function () {
        var proxy_id = $("input[name=\"attending\"]").val();
        $(".disabled-overlay").addClass("hide");
        var user_pin_request = $.ajax({
            url: ENTRADA_URL + '/assessments?section=api-assessments&method=get-user-pin&proxy_id=' + proxy_id,
            type: "GET",
            beforeSend: function () {

            },
            complete: function () {

            },
            error: function () {

            }
        });

        $.when(user_pin_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "No assessment tools found.");
            if (jsonResponse.status == "success") {
                if (jsonResponse.data.has_pin) {
                    $("#complete_and_confirm_by_pin").attr("disabled", false);
                    $("#complete_and_confirm_by_pin").prop("checked", "checked").change();
                    $("#complete_and_confirm_by_pin").siblings(".assessment-type-title").removeClass("muted");
                    $("#complete_and_confirm_by_pin").siblings(".assessment-type-description").removeClass("hide");
                    $("#complete_and_confirm_by_pin").siblings(".pin-warning").addClass("hide");
                } else {
                    if ($("#complete_and_confirm_by_pin").prop("checked")) {
                        $("#send_blank_form").prop("checked", "checked").change();
                    }
                    $("#complete_and_confirm_by_pin").attr("disabled", true);
                    $("#complete_and_confirm_by_pin").removeProp("checked");
                    $("#complete_and_confirm_by_pin").siblings(".assessment-type-title").addClass("muted");
                    $("#complete_and_confirm_by_pin").siblings(".assessment-type-description").addClass("hide");
                    $("#complete_and_confirm_by_pin").siblings(".pin-warning").removeClass("hide");
                }
            } else {
                display_error(jsonResponse.data, "#assessment-tool-msgs");
                $("#assessment-tool-msgs").removeClass("hide");
            }
        });
    });

    $("#select-resident-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments&course-id=" + $("#course-id").val(),
        resource_url: ENTRADA_URL,
        filters: {
            target_record_id: {
                label: "Resident",
                data_source: "get-residents",
                mode: "radio",
                selector_control_name: "target_record_id",
                search_mode: false
            }
        },
        control_class: "residents-selector",
        lazyload: true,
        no_results_text: "No results found",
        parent_form: $("#assessment-tool-form"),
        width: 300,
        modal: false,
        user_card: true
    });

    $("#select-my-learners-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filter_component_label: javascript_translations.my_residents,
        filters: {
            target_record_id: {
                label: javascript_translations.curriculum_periods,
                data_source: "get-learner-picker-data",
                secondary_data_source: "my-learners",
                selector_control_name: "target_record_id",
                mode: "radio"
            }
        },
        control_class: "residents-selector",
        no_results_text: javascript_translations.no_results,
        parent_form: $("#assessment-tool-form"),
        width: 300
    });

    $("#cbme-course-picker").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments&advanced_search=true&proxy_id=", // $("input[name='target_record_id']").val(), must be added on click
        resource_url: ENTRADA_URL,
        filters: {
            target_course_id: {
                label: "Program",
                data_source: "get-user-course",
                mode: "radio",
                selector_control_name: "course_id",
                search_mode: false
            }
        },
        control_class: "faculty-courses-selector",
        lazyload: true,
        no_results_text: "No results found",
        parent_form: $("#assessment-tool-form"),
        width: 300,
        modal: false
    });

    $("#select-resident-btn,#select-my-learners-btn").on("change", function () {
        var proxy_id = $("input[name='target_record_id']").val();
        // Clear selected course
        $("input[name='course_id']").remove();
        $("#cbme-course-picker").html($("#course-selection-default-button-text").html());

        // Clear selected EPA
        $("input[name='course_epa']").remove();
        $("#select-epa-btn").html($("#epa-selection-default-button-text").html());

        $("#assessment-tools").addClass("hide");
        $(".epa-selector-div").addClass("hide");
        if (proxy_id) {

            var course_selection_request = $.ajax({
                url: ENTRADA_URL + "/assessments?section=api-assessments&method=get-user-course&advanced_search=1&proxy_id=" + proxy_id,
                type: "GET"
            });
            $.when(course_selection_request).done(function (data) {
                var jsonResponse = safeParseJson(data, "");
                if (typeof jsonResponse.data !== 'undefined') {
                    if (jsonResponse.data) {
                        var settings = $("#cbme-course-picker").data("settings");

                        settings.filters.target_course_id.data_source = jsonResponse.data;
                        // If the data source only includes 1 element, select it by default.
                        if (jsonResponse.data.length === 1) {
                            var single_course_id = jsonResponse.data[0].target_id;
                            var single_course_name = jsonResponse.data[0].target_label;
                            var assessment_tools_flag = jsonResponse.data[0].objective_tools;

                            if ($("input[name=\"course_id\"]").length == 0) {
                                $('#assessment-tool-form').append(
                                    '<input name="course_id" value="' + single_course_id + '" id="target_course_id_' + single_course_id + '" data-label="' + single_course_name + '"class="search-target-control target_course_id_search_target_control faculty-courses-selector" type="hidden">'
                                );
                            } else {
                                $("input[name=\"course_id\"]").val(single_course_id).attr("data-objective-tools", assessment_tools_flag);
                            }

                            if ($("input[name=\"course_id_hidden\"]").length == 0) {
                                $('#assessment-tool-form').append(
                                    '<input name="course_id_hidden" value="' + single_course_id + '" id="course-id" type="hidden" data-objective-tools="'+ assessment_tools_flag +'">'
                                );
                            } else {
                                $("input[name=\"course_id_hidden\"]").val(single_course_id).attr("data-objective-tools", assessment_tools_flag);
                            }

                            $(".course-selector-div").addClass("hide");
                            $("#course-id").trigger("change");
                            $("#cbme-course-picker").trigger("change");
                            $(".epa-help").attr('href', ENTRADA_URL + "/cbme/encyclopedia?course_id=" + single_course_id);
                        } else {
                            $(".course-selector-div").removeClass("hide");
                        }
                    }
                }
            });

        } else {
            $(".course-selector-div").addClass("hide");
        }
    });

    $(document).on("change", "#cbme-course-picker", function (e) {
        var course_id = $("input[name='course_id']").val();
        var proxy_id = "0";
        if ($("input[name='target_record_id']").length > 0) {
            proxy_id = $("input[name='target_record_id']").val();
        }
        var assessment_api_url = ENTRADA_URL + '/assessments?section=api-assessments&method=get-course-epas&course_id=';
        $("input[name='course_epa']").remove();
        $("#select-epa-btn").html($("#epa-selection-default-button-text").html());
        $("#assessment-tools").addClass("hide");
        var course_preference_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-course-preference", "course_id": course_id}
        });
        $.when(course_preference_request).done(function (data) {
            if (course_id && course_id > 0) {
                // Fetch EPAs for the given course when course ID has changed
                var course_epas_request = $.ajax({
                    url: assessment_api_url + course_id,
                    type: "GET",
                    data: {"proxy_id": proxy_id}
                });
                $.when(course_epas_request).done(function (data) {
                    var jsonResponse = safeParseJson(data, "");
                    if (typeof jsonResponse.data !== 'undefined') {
                        if (jsonResponse.data.hasOwnProperty("course_requires_date_of_encounter")) {
                            if (jsonResponse.data.course_requires_date_of_encounter) {
                                $("#date-of-encounter-container").removeClass("hide");
                            } else {
                                $("#date-of-encounter-container").addClass("hide");
                            }
                        }

                        if (jsonResponse.data && jsonResponse.data.epas.length > 0) {
                            var settings = $("#select-epa-btn").data("settings");
                            settings.filters.epa.filter_presets = {};
                            settings.filters.epa.data_source = jsonResponse.data.epas;
                            if (jsonResponse.data.hasOwnProperty("filter_presets")) {
                                settings.filters.epa.filter_presets = jsonResponse.data.filter_presets;
                            }

                            $(".epa-selector-div").removeClass("hide");
                        } else {
                            $(".epa-selector-div").addClass("hide");
                            var course_tool_request = $.ajax({
                                url: ENTRADA_URL + "/assessments?section=api-assessments",
                                type: "GET",
                                data: {"method": "get-course-tools", "course_id": course_id}
                            });

                            $.when(course_tool_request).done(function (data) {
                                var jsonResponse = safeParseJson(data, "");
                                if (jsonResponse.status == "success") {
                                    if (jsonResponse.data && jsonResponse.data.assessment_tools.length > 0) {
                                        if (jsonResponse.data.hasOwnProperty("course_requires_date_of_encounter")) {
                                            if (jsonResponse.data.course_requires_date_of_encounter) {
                                                $("#date-of-encounter-container").removeClass("hide");
                                            } else {
                                                $("#date-of-encounter-container").addClass("hide");
                                            }
                                        }

                                        if (jsonResponse.data.hasOwnProperty("assessment_tools")) {

                                            $("#assessment-tool-list").empty();
                                            $.each(jsonResponse.data.assessment_tools, function (i, assessment_tool) {
                                                $("<li/>").loadTemplate(
                                                    "#assessment-tool-template", {
                                                        form_id: assessment_tool.form_id,
                                                        epa_objective_id: assessment_tool.objective_id,
                                                        title: assessment_tool.title,
                                                        form_type: assessment_tool.form_type_title,
                                                        completed_count: assessment_tool.completed_count,
                                                        form_average_time: assessment_tool.average_time,
                                                        form_count: assessment_tool.form_count
                                                    }
                                                ).appendTo("#assessment-tool-list");
                                            });
                                            $("#assessment-tools").removeClass("hide");
                                        }
                                    }
                                }
                            });
                            $(".epa-help").attr('href', ENTRADA_URL + "/cbme/encyclopedia?course_id=" + course_id);
                        }
                    }
                });
            } else {
                $(".epa-selector-div").addClass("hide");
            }
        });


    });

    $(document).on("change", "#course-id", function () {
        var course_id = $(this).val();
        var target = $("#course-id");
        if (target.is("select")) {
            var has_objective_tools = ($("#course-id option:selected").attr("data-objective-tools") == "true");
        } else {
            var has_objective_tools = ($("#course-id").attr("data-objective-tools") == "true");
        }

        var course_assessment_method_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "GET",
            data: {"method": "get-course-assessment-methods", "course_id": course_id}
        });

        $.when(course_assessment_method_request).done(function (assessment_method_data) {
            var jsonResponse = safeParseJson(assessment_method_data, "");
            if (jsonResponse.status == "success") {
                $(".assessment-method").addClass("hide");
                $.each(jsonResponse.data, function (i, method) {
                    if (method.display) {
                        $(".assessment-method-" + method.assessment_method_id).removeClass("hide");
                    }
                });

                var assessment_api_url = ENTRADA_URL + '/assessments?section=api-assessments&method=get-course-epas&course_id=';
                $("input[name='course_epa']").remove();
                $("#select-epa-btn").html($("#epa-selection-default-button-text").html());
                $("#assessment-tools").addClass("hide");

                if (has_objective_tools) {
                    $(".epa-selector-div").addClass("hide");
                } else {
                    $(".epa-selector-div").removeClass("hide");
                }

                var course_preference_request = $.ajax({
                    url: ENTRADA_URL + "/assessments?section=api-assessments",
                    type: "POST",
                    data: {"method": "set-course-preference", "course_id": course_id}
                });

                $.when(course_preference_request).done(function (data) {
                    if (course_id && course_id > 0) {
                        if (has_objective_tools) {
                            var course_tool_request = $.ajax({
                                url: ENTRADA_URL + "/assessments?section=api-assessments",
                                type: "GET",
                                data: {"method": "get-course-tools", "course_id": course_id}
                            });

                            $.when(course_tool_request).done(function (data) {
                                $("#assessment-tool-list").empty();
                                var jsonResponse = safeParseJson(data, "");
                                if (jsonResponse.status == "success") {
                                    if (jsonResponse.data.hasOwnProperty("course_requires_date_of_encounter")) {
                                        if (jsonResponse.data.course_requires_date_of_encounter) {
                                            $("#date-of-encounter-container").removeClass("hide");
                                        } else {
                                            $("#date-of-encounter-container").addClass("hide");
                                        }
                                    }

                                    $.each(jsonResponse.data.assessment_tools, function(i, assessment_tool) {
                                        $("<li/>").loadTemplate(
                                            "#assessment-tool-template", {
                                                form_id: assessment_tool.form_id,
                                                epa_objective_id: assessment_tool.objective_id,
                                                title: assessment_tool.title,
                                                form_type: assessment_tool.form_type_title,
                                                completed_count: assessment_tool.completed_count,
                                                form_average_time: assessment_tool.average_time,
                                                form_count: assessment_tool.form_count
                                            }
                                        ).appendTo("#assessment-tool-list");
                                    });
                                    $("#assessment-tools").removeClass("hide");
                                }
                            });
                        } else {
                            var proxy_id = "0";
                            if ($("input[name='target_record_id']").length > 0) {
                                proxy_id = $("input[name='target_record_id']").val();
                            }

                            // Fetch EPAs for the given course when course ID has changed
                            var course_epas_request = $.ajax({
                                url: assessment_api_url + course_id,
                                type: "GET",
                                data: {"proxy_id": proxy_id}
                            });
                            $.when(course_epas_request).done(function (data) {
                                var jsonResponse = safeParseJson(data, "");
                                if (typeof jsonResponse.data !== 'undefined') {
                                    if (jsonResponse.data.hasOwnProperty("course_requires_date_of_encounter")) {
                                        if (jsonResponse.data.course_requires_date_of_encounter) {
                                            $("#date-of-encounter-container").removeClass("hide");
                                        } else {
                                            $("#date-of-encounter-container").addClass("hide");
                                        }
                                    }
                                    if (jsonResponse.data.hasOwnProperty("epas")) {
                                        var settings = $("#select-epa-btn").data("settings");
                                        settings.filters.epa.filter_presets = {};
                                        settings.filters.epa.data_source = jsonResponse.data.epas;
                                        if (jsonResponse.data.hasOwnProperty("filter_presets")) {
                                            settings.filters.epa.filter_presets = jsonResponse.data.filter_presets;
                                        }

                                        if (jsonResponse.data.epas.length > 0) {
                                            $(".epa-selector-div").removeClass("hide");
                                        } else {
                                            $(".epa-selector-div").addClass("hide");
                                        }
                                    }
                                }
                            });
                            $(".epa-selector-div").removeClass("hide");
                        }
                    } else {
                        $(".epa-selector-div").addClass("hide");
                    }
                });
            }
        });
    });
    var popover_options = {
        animation: false,
        container: "body",
        selector: "[rel=\"popover\"]",
        html: true,
        placement: "left",
        title: cbme_translations.user_information,
        content: function () {
            var result = "";
            var target_id = $(this).attr("data-id");
            var user_data_request = $.ajax({
                url: ENTRADA_URL + "/assessments?section=api-assessments&method=get-residents-faculty-courses&proxy_id=" + target_id + "&advanced_search=1&proxy_id=" + target_id,
                type: "GET"
            });
            $.when(user_data_request).done(function (data) {
                var jsonResponse = safeParseJson(data, "");
                if (typeof jsonResponse.data !== 'undefined') {
                    if (jsonResponse.data) {
                        var data = jsonResponse.data[0];
                        data.group == "faculty" ? assessment_tool = 1 : assessment_tool = 0;
                        if (typeof data !== 'undefined') {
                            if (typeof data.target_id !== 'undefined') {
                                var course_string = [];
                                if(data.courses.length > 0) {
                                    for (var i = 0; i < data.courses.length; i++) {
                                         course_string[i] = data.courses[i].target_label;
                                    }
                                } else {
                                    course_string = cbme_translations.not_available;
                                }
                                result = "<div class='inner-popover-content'><img class='userAvatar img-polaroid center-align square-image' src='" + data.photo_url + "' /><br/>";
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
                                result += "<br/> Programs: " + course_string.toString() + "</div>";
                                $(".popover-content").html(result);
                            }
                        } else {
                            result = cbme_translations.no_information;
                        }
                    }
                }
            });
            return result;
        }
    };

    $(document).on("mouseenter", ".search-filter-item", function(e) {
        e.stopPropagation();
        var item = $(this);
        timer = setTimeout(function () {
            $(".popover").remove();
            $("[rel=\"popover\"]").popover(popover_options);
            item.popover("show");
        }, 700);
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

    $(document).on("click", ".preview-assessment-tool-form", function(e) {
        var form_id = $(this).attr("data-form-id");
        var form_preview_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "GET",
            data: {"method": "get-form-preview", "form_id": form_id}
        });

        $("#assessment-tool-form-preview").empty();

        $.when(form_preview_request).done(function (form_preview_request_data) {
            var jsonResponse = safeParseJson(form_preview_request_data, "");
            if (jsonResponse.status == "success") {
                var view_html = atob(jsonResponse.data);
                $("#assessment-tool-form-preview").html(view_html);
                previewLightbox.dialog("open");
            }
        });
        e.preventDefault();
    });

    if ($(".epa-selector-div").hasClass("preload-epas") || !course_requires_epas) {
        $("#course-id").trigger("change");
    }

    $("#preceptor-access-request-submit").on("click", function (e) {
        var fd = new FormData(document.getElementById("preceptor-access-request-form"));
        var method = "preceptor-access-request";
        fd.append("method", method);

        var preceptor_access_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
        });

        $.when(preceptor_access_request).done(function (data) {
            reset_errors();
            $("#assessment-tool-msgs").empty().addClass("hide");
            var jsonResponse = safeParseJson(data, assessment_tools.json_error);
            if (jsonResponse.status == "success") {
                if (jsonResponse.data.hasOwnProperty("target_id") && jsonResponse.data.hasOwnProperty("target_label")) {
                    var proxy_id = jsonResponse.data.target_id;
                    var name = jsonResponse.data.target_label;
                    var hidden_input = $(document.createElement("input")).attr({
                        type: "hidden",
                        name: "attending",
                        value: proxy_id,
                        id: "attending_" + proxy_id,
                        "data-label": name,
                        "class": "search-target-control attending_search_target_control attending-selector"
                    });

                    $("#assessment-tool-form").append(hidden_input);
                    $("#select-attending-btn").html(name + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");

                    display_success([assessment_tools.preceptor_exists], "#assessment-tool-msgs");
                    $("#assessment-tool-msgs").removeClass("hide");
                } else {
                    $("#select-attending-btn").html("Click here to select an assessor &nbsp;  <i class=\"icon-chevron-down pull-right btn-icon\"></i>");
                    $(".attending_search_target_control").remove();
                    display_success(jsonResponse.data, "#assessment-tool-msgs");
                    $("#assessment-tool-msgs").removeClass("hide");
                }
                $("#preceptor-access-request-modal").modal("hide");
            } else if (jsonResponse.status == "error") {
                display_error(jsonResponse.data, "#preceptor-msgs");
            }
        });
        e.preventDefault();
    });

    $("#preceptor-access-request-modal").on("hidden", function () {
        reset_preceptor_modal();
    });

    function reset_preceptor_modal() {
        reset_errors();
        $("#preceptor-access-request-form").trigger("reset");
    }

    function reset_errors() {
        $("#preceptor-msgs").empty();
    }

    $("#search-assessment-tools").keyup(function() {
        var search_string = $(this).val().toLowerCase();
        var hidden = 0;
        $(".user-card-container").each(function () {
            if ($(this).find("h3").text().toLowerCase().indexOf(search_string) >= 0) {
                $(this).parent().show();
            } else {
                if ($(this).find("p strong").text().toLowerCase().indexOf(search_string) >= 0) {
                    $(this).parent().show();
                } else {
                    $(this).parent().hide();
                }
            }
        });

        $(".user-list-card li").each(function () {
            if ($(this).css("display") === "none") {
                hidden++;
            }
            if (hidden == $(".user-list-card li").length) {
                $(".no-results-container").html("<div class='alert alert-info no-assessment-search-results'>" + assessment_tools.no_results_message + "</div>");
            } else {
                $('.no-assessment-search-results').remove();
            }
        });
    });

    function reset_epa_btn() {
        $("#select-epa-btn").prop("disabled", true).html("Click here to select an EPA&nbsp;<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        $("#assessment-tool-list").empty();
        $(".course-epa-selector").remove();
        $("#assessment-tools").addClass("hide");
    }

    $(document).on("click", ".all-assessments", function() {
        $("#send-assessment-button").attr("data-form-id", $(this).attr("data-form-id")).attr("data-epa-objective-id", $(this).attr("data-epa-objective-id")).attr("data-form-type", $(this).attr("data-form-type")).attr("data-form-count", $(this).attr("data-form-count"));
    });
});