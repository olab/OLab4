jQuery(document).ready(function ($) {
    if ($("#current-page").val() != "leave_by_block" && $("#current-page").val() != "rotation-leave") {
        update_ui_by_date_range(false);

        var target_id_list = getTargetIDList();
        if (target_id_list.length > 0) {
            var settings = $("#choose-form-btn").data("settings");
            settings.filters["form"].api_params.target_id_list = target_id_list;
            $("#additional-comments").removeClass("hide");
            $("#additional-description").removeClass("hide");
            $("#additional-statistics").removeClass("hide");
            $("#generate-pdf-btn").removeClass("hide");
        }

        if ($(".form_search_target_control").length > 0) {
            $("#choose-form-btn").html($(".form_search_target_control").attr("data-label") + "&nbsp;" + "<i class=\"icon-chevron-down pull-right btn-icon\"></i>");
        }
    }

    if ($("input[name=\"target[]\"]").length > 0) {
        $("#form-selector").removeClass("hide");
    }

    if ($("#current-page").val() == "rotations") {
        $("#evaluation-search").addClass("hide");
    }

    $("#report-start-date, #report-end-date").on("change", function(e) {
        update_ui_by_date_range();
        if ($("#current-page").val() != "rotation-leave") {
            var advanced_search_settings = $("#choose-evaluation-btn").data("settings");
            advanced_search_settings.filters.target.api_params["start_date"] = new Date($("#report-start-date").val()).getTime() / 1000;
            advanced_search_settings.filters.target.api_params["end_date"] = new Date($("#report-end-date").val()).getTime() / 1000;
            if ($("#current-page").val() == "completion") {
                if ($("#report-start-date").val() != null && $("#report-start-date").val() != "" || $("#report-end-date").val() != null && $("#report-end-date").val() != "") {
                    $("#include-externals-div").removeClass("hide");
                } else {
                    $("#include-externals-div").addClass("hide");
                }
            }
        } else {
            $("#choose-form-btn").data("settings").filters["start_date"] = new Date($("#report-start-date").val()).getTime()/1000;
            $("#choose-form-btn").data("settings").filters["end_date"] = new Date($("#report-end-date").val()).getTime()/1000;
        }
    });

    $("#choose-evaluation-btn").on("change", function(e) {
        if ($("#current-page").val() != "learner" && $("#current-page").val() != "rotation-leave") {
            if ($("#current-page").val() == "faculty" || $("#current-page").val() == "learner-reports" || $("#current-page").val() == "course") {
                $(".form_search_target_control").remove();
                $("#additional-comments").addClass("hide");
                $("#additional-statistics").addClass("hide");
                $("#additional-description").addClass("hide");
                $("#output-options").addClass("hide");
                $("#generate-report").addClass("hide");
                $("#generate-pdf-btn").addClass("hide");

                var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
                $("#choose-form-btn").html("Browse Forms ").append(icon);

                $("#form-selector").removeClass("hide");
                var target_id_list = getTargetIDList();
                if (target_id_list.length > 0) {
                    var settings = $("#choose-form-btn").data("settings");
                    settings.filters["form"].api_params.target_id_list = target_id_list;
                }
            } else {
                $("#report-type-div").addClass("hide");
                $("#additional-comments").addClass("hide");
                $("#additional-description").addClass("hide");
                $("#output-options").addClass("hide");
                $("#generate-report").addClass("hide");
                $("#generate-csv").addClass("hide");
                update_evaluation_subtypes();
            }
        } else {

            if ($(".target_target_item").length > 0) {
                $("#generate-pdf-btn").removeClass("hide");
                $("#additional-description").removeClass("hide");
                if ($("#select-individual-events-checkbox").is(":checked")) {
                    $("#output-options").removeClass("hide");
                }
            } else {
                $("#generate-pdf-btn").addClass("hide");
                $("#additional-description").addClass("hide");
                $("#output-options").addClass("hide");
            }

        }
    });

    $("#page").on("click", ".remove-target-toggle", function(e) {

        if ($("#current-page").val() != "completion") {

            var proceed = false;
            // We do not want the other lists destroyed if we are removing specific learning events and still have some selected.
            if ($("#current-page").val() == "learning-events" && $(this).data("filter") == "learning_events") {
                if ($("input[name=\"learning_events[]\"]").length == 0) {
                    proceed = true;

                    var event_settings = $("#choose-individual-events-btn").data("settings");
                    event_settings.filters.learning_events.data_source = [];
                }
            }

            if (proceed) {

                if ($(".selected-items-list").length == 0) {
                    $("#evaluation-subtypes").empty();
                    $("#form-selector").addClass("hide");
                    $("#generate-pdf-btn").addClass("hide");
                    $("#additional-description").addClass("hide");
                    $("#output-options").addClass("hide");
                }

                if ($("#current-page").val() != "learner") {
                    $("#additional-statistics").addClass("hide");
                    $("#additional-comments").addClass("hide");
                    $("#additional-description").addClass("hide");
                    $("#output-options").addClass("hide");

                    var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
                    $("#choose-form-btn").html("Browse Forms ").append(icon);
                    $(".form_search_target_control").remove();
                    $("#generate-pdf-btn").addClass("hide");
                    $("#generate-report").addClass("hide");

                    var settings = $("#choose-form-btn").data("settings");

                    if ($("#current-page").val() == "faculty" || $("#current-page").val() == "learner-reports") {
                        settings.filters["form"].api_params.target_id_list = getTargetIDList();
                    } else {
                        if ($("#current-page").val() == "leave_by_block") {
                            if ($(".block_target_item").length > 0) {
                                $("#select-learners-div").removeClass("hide");
                            } else {
                                $("#learner_list_container").empty();
                                $("#learner_selected_targets_list").empty();
                                $(".learner_search_target_control").remove();
                                $("#select-learners-div").addClass("hide");
                                $("#generate-pdf-btn").addClass("hide");
                            }

                            if ($("#learner_list_container").length > 0) {
                                $("#generate-pdf-btn").removeClass("hide");
                            } else {
                                $("#generate-pdf-btn").addClass("hide");
                            }
                        } else {
                            settings.filters["form"].api_params.target_id_list = "";
                            update_evaluation_subtypes();
                        }
                    }
                }
            }
        } else {
            if ($(".faculty_target_item").length > 0) {
                $("#generate-pdf-btn").removeClass("hide");
                $("#add-average-delivery-date").removeClass("hide");
                $("#additional-description").removeClass("hide");
                if ($("#select-individual-events-checkbox").is(":checked")) {
                    $("#output-options").removeClass("hide");
                }
            } else {
                $("#generate-pdf-btn").addClass("hide");
                $("#add-average-delivery-date").addClass("hide");
                $("#additional-description").addClass("hide");
                $("#output-options").addClass("hide");
            }
        }
    });

    $("#select-individual-events-checkbox").on("change", function (e) {
        reset_form(true);
    });

    $("#page").on("change", ".target-subtype-checkbox", function(e) {

        if ($("#current-page").val() == "learning-events" && $("#select-individual-events-checkbox").prop("checked")) {
            generate_individual_events();
        } else {
            update_forms(getTargetIDListBySubtype());
        }
    });

    $(document).on("change", "input[id^=\"learning_events_target_\"]", function (e) {
        if ($("input[name=\"learning_events[]\"]").length > 0) {
            update_forms(getTargetIDListBySubtype());
        } else {
            update_forms([]);
        }
    });

    function generate_individual_events() {
        // Individual events sub-list creation.
        var adistribution_ids = [];
        $(".target-subtype-checkbox").each(function (i, value) {
            if ($(value).is(":checked")) {
                adistribution_ids.push($(value).attr("data_adistribution_id"));
            }
        });
        
        var event_settings = $("#choose-individual-events-btn").data("settings");

        if ($(adistribution_ids).length > 0) {
            $.ajax({
                url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
                data: {
                    "method": "get-individual-learning-events-by-adistribution-ids",
                    "adistribution_ids": adistribution_ids,
                    "start_date": $("#report-start-date").val(),
                    "end_date": $("#report-end-date").val()
                },
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        event_settings.filters.learning_events.data_source = jsonResponse.data;
                    } else {
                        event_settings.filters.learning_events.data_source = [];
                    }
                    $("#individual-events-list").removeClass("hide");
                }
            });
        } else {
            event_settings.filters.learning_events.data_source = [];
        }
    }

    $("#page").on("change", "#select-all-subtypes", function(e) {

        if ($("#current-page").val() == "learning-events" && $("#select-individual-events-checkbox").prop("checked")) {

            if ($(this).is(":checked")) {
                $.each($(".target-subtype-checkbox"), function () {
                    $(this).attr("checked", "checked");
                });
            } else {
                $.each($(".target-subtype-checkbox"), function () {
                    $(this).removeAttr("checked");
                });
            }

            generate_individual_events();
        } else {

            if ($(this).is(":checked")) {
                $.each($(".target-subtype-checkbox"), function () {
                    $(this).attr("checked", "checked");
                });
                $("#form-selector").removeClass("hide");
            } else {
                $.each($(".target-subtype-checkbox"), function () {
                    $(this).removeAttr("checked");
                });
                $("#form-selector").addClass("hide");
            }

            $("#additional-comments").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
            $("#generate-report").addClass("hide");
            $("#generate-pdf-btn").addClass("hide");
            $(".form_search_target_control").remove();

            var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
            $("#choose-form-btn").html("Browse Forms ").append(icon);

            var settings = $("#choose-form-btn").data("settings");
            settings.filters["form"].api_params.target_id_list = getTargetIDListBySubtype();
            settings.filters["form"].api_params.current_page = $("#current-page").val();
        }
    });

    $("#choose-form-btn").on("change", function(e) {
        if ($(".form_search_target_control").length > 0) {
            $("#report-type-div").removeClass("hide");
            $("#generate-report").removeClass("hide");
            $("#generate-pdf-btn").removeClass("hide");
            $("#additional-comments").removeClass("hide");
            $("#additional-description").removeClass("hide");
            if ($("#select-individual-events-checkbox").is(":checked")) {
                $("#output-options").removeClass("hide");
            }
            $("#additional-statistics").removeClass("hide");
            $("#include-comments").attr("checked", "checked");
            $.ajax({
                url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
                data: {
                    "method": "validate-subtype-by-form",
                    "form_id": $(".form_search_target_control").val(),
                    "target_id_subtype_list": getTargetIDListBySubtype(),
                    "current_page": $("#current-page").val()
                },
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {

                        $.each($(".target-subtype-checkbox"), function() {
                            $(this).removeAttr("checked");
                        });

                        var target_id_list = [];
                        $.each(jsonResponse.data, function(key, value) {
                            $("#target-subtype-" + value).attr("checked", "checked");
                            target_id_list.push(value);
                        });

                        var settings = $("#choose-form-btn").data("settings");
                        settings.filters["form"].api_params.target_id_list = target_id_list;

                        $("[name='report-type']:checked").trigger("change");
                    }
                }
            });
        } else {
            $("#generate-report").addClass("hide");
            $("#generate-pdf-btn").addClass("hide");
            $("#additional-comments").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
        }
    });

    $("#generate-report").on("click", function(e) {
        var comments = "";
        var start_date = "";
        var end_date = "";
        var target_list = "";
        var cperiod_list = "";
        var description = "";

        var report_start_date = $("#report-start-date").val();
        var report_end_date = $("#report-end-date").val();

        var settings = $("#choose-form-btn").data("settings");
        var target_list_id = settings.filters["form"].api_params.target_id_list;

        if ($("#include-comments").is(":checked")) {
            if ($("#include-commenter-id").is(":checked")) {
                comments += "&commenter-id=1"
            }
            if ($("#include-commenter-name").is(":checked")) {
                comments += "&commenter-name=1"
            }
        } else {
            comments = "&strip=1";
        }

        if ($("#include-description").is(":checked") && $("#description-text").val().length > 0) {
            description = "&description=" + $("#description-text").val();
        }

        if (isValidDate(report_start_date) && report_start_date != null && report_start_date != "") {
            start_date = "&start-date=" + report_start_date;
        }

        if (isValidDate(report_end_date) && report_end_date != null && report_end_date != "") {
            end_date = "&end-date=" + report_end_date;
        }

        if ($("#current-page").val() == "faculty" || $("#current-page").val() == "learner-reports") {
            target_list = "&target_ids[]=" + target_list_id;

            var cperiod_ids = settings.filters["form"].api_params.cperiod_ids;
            $.each(cperiod_ids, function (key, value) {
                if (value) {
                    cperiod_list += "&cperiod_ids[]=" + value;
                }
            });

        } else {
            $.each(target_list_id, function (key, value) {
                if (value) {
                    target_list += "&target_ids[]=" + value;
                }
            });
        }

        var form_id = "&form_id=" + $(".form_search_target_control").val();

        var previous_page = "&previous_page=" + $("#current-page").val();

        var specific_associated_record_type = "";
        var specific_associated_record_ids = "";

        if (($("#current-page").val() == "learning-events" && $("#select-individual-events-checkbox").prop("checked") && $("input[name=\"learning_events[]\"]").length > 0)) {
            specific_associated_record_type = "&associated_record_type=event_id";
            $("input[name=\"learning_events[]\"]").each(function (key, input) {
                specific_associated_record_ids += "&associated_record_ids[]=" + $(input).val();
            });
        }

        var include_statistics = "&include_statistics=" + $("#include-statistics").is(":checked") + "&include_positivity=" + $("#include-positivity").is(":checked");

        var include_associated_records_subheaders = "&include_associated_records_subheaders=" + $("#output-associated-records").is(":checked");

        if (target_list != "") {
            window.location.href = ENTRADA_URL + "/admin/assessments/reports?section=report" + target_list + form_id + comments + previous_page + cperiod_list + start_date + end_date + description + specific_associated_record_type + specific_associated_record_ids + include_statistics + include_associated_records_subheaders;
        }
    });

    $("#generate-pdf-btn").on("click", function (e) {
        if ($("#current-page").val() == "faculty"
            || $("#current-page").val() == "learner-reports"
            || $("#current-page").val() == "completion"
            || $("#current-page").val() == "rotation-leave"
        ) {
            $("#pdf_individual_option").attr("checked", false).addClass("hide");
            $("#pdf_individual_option_label").addClass("hide");
        }

        $("#generate-pdf-modal-confirm").attr("disabled", false);
        $("#generate-pdf-modal-form").find("input[type=hidden]").remove();
        $("#assessor-header").remove();
        $("#generate-pdf-details-table tbody").empty();

        if ($("#current-page").val() == "course") {
            var tr = $(document.createElement("tr"));
            var start_date = "Not set";
            var end_date = "Not set";
            if ($("#report-start-date").val()) {
                start_date = $("#report-start-date").val();
            }
            if ($("#report-end-date").val()) {
                end_date = $("#report-end-date").val();
            }
            var td_target = $(document.createElement("td")).text("Any");
            var td_date_range = $(document.createElement("td")).text(start_date + " - " + end_date);
            $("#generate-pdf-details-table tbody").append(tr.append(td_target, td_date_range));
        } else if ($("#current-page").val() == "leave_by_block" || $("#current-page").val() == "rotation-leave") {
            $("#generate-pdf-details-table").addClass("hide");
            $("#download_option").addClass("hide");
        } else {
            var selector = $("#current-page").val() == "completion" ? "faculty" : "target";
            $.each($("li." + selector + "_target_item"), function (i, v) {
                var tr = $(document.createElement("tr"));
                var start_date = "Not set";
                var end_date = "Not set";
                if ($("#report-start-date").val()) {
                    start_date = $("#report-start-date").val();
                }
                if ($("#report-end-date").val()) {
                    end_date = $("#report-end-date").val();
                }
                var td_target = $(document.createElement("td")).text($(v).text().slice($(v).text().indexOf("×") + 1));
                if ($("#current-page").val() == "completion") {
                    if (td_target.text().indexOf("-") > 0) {
                        td_target.text(td_target.text().substring(0, td_target.text().indexOf("-") - 1));
                    }
                }
                var td_date_range = $(document.createElement("td")).text(start_date + " - " + end_date);
                $("#generate-pdf-details-table tbody").append(tr.append(td_target, td_date_range));
            });
        }

        $("#display-success-box").addClass("hide");
        $("#no-generate-selected").addClass("hide");

        // Sorry; hacking this until we can standardize modal usage.
        $("#generate-pdf-download-wait").addClass("hide");
        $("#generate-pdf-modal-confirm").removeProp("disabled");
        $("#generate-pdf-modal-confirm").removeClass("disabled");

    });

    $("#generate-pdf-modal-form").submit(function (e) {
        $("#generate-pdf-modal-confirm").attr("disabled", true);
        $("#generate-pdf-modal").modal("hide");

        var hidden_start_date = $(document.createElement("input")).attr({"type": "hidden", "name": "start_date"}).val($("#report-start-date").val());
        var hidden_end_date = $(document.createElement("input")).attr({"type": "hidden", "name": "end_date"}).val($("#report-end-date").val());

        var selector = $("#current-page").val() == "completion" ? "faculty" : "target";
        $.each($("li." + selector + "_target_item"), function (i, v) {
            var hidden_proxy_id = $(document.createElement("input")).attr({"type": "hidden", "name": "proxy_id[]"}).val($(v).data("id"));
            $("#generate-pdf-modal-form").append(hidden_proxy_id);
        });

        var hidden_method = $(document.createElement("input")).attr({"type": "hidden", "name": "method"});

        if ($("#current-page").val() == "faculty" || $("#current-page").val() == "learner-reports" || $("#current-page").val() == "course") {
            var hidden_include_comments = $(document.createElement("input")).attr({"type": "hidden", "name": "include_comments"}).val($("#include-comments").is(":checked"));
            var hidden_include_commenter_id = $(document.createElement("input")).attr({"type": "hidden", "name": "include_commenter_id"}).val($("#include-commenter-id").is(":checked"));
            var hidden_include_commenter_name = $(document.createElement("input")).attr({"type": "hidden", "name": "include_commenter_name"}).val($("#include-commenter-name").is(":checked"));
            var hidden_include_statistics = $(document.createElement("input")).attr({"type": "hidden", "name": "include_statistics"}).val($("#include-statistics").is(":checked"));
            var hidden_include_positivity = $(document.createElement("input")).attr({"type": "hidden", "name": "include_positivity"}).val($("#include-positivity").is(":checked"));
            var hidden_form_id = $(document.createElement("input")).attr({"type": "hidden", "name": "form_id"}).val($(".form_search_target_control").val());
            $(this).append(hidden_include_comments, hidden_include_commenter_id, hidden_include_commenter_name, hidden_form_id, hidden_include_statistics, hidden_include_positivity);

            if ($("#current-page").val() != "course") {
                $.each($(".cperiod-filter"), function (i, v) {
                    var hidden_cperiod_id = $(document.createElement("input")).attr({"type": "hidden", "name": "cperiod_ids[]"}).val($(v).val());
                    $("#generate-pdf-modal-form").append(hidden_cperiod_id);
                });
            } else {
                var hidden_course_id = $(document.createElement("input")).attr({"type": "hidden", "name": "course_id"}).val($(".target_search_target_control").val());
                $(this).append(hidden_course_id);
            }
            hidden_method.val("generate-pdf-bulk-reports");
        } else if ($("#current-page").val() == "completion") {
            var hidden_include_average_delivery_date = $(document.createElement("input")).attr({"type": "hidden", "name": "include_average_delivery_date"}).val($("#add-average-delivery-date-checkbox").is(":checked"));
            $(this).append(hidden_include_average_delivery_date);

            hidden_method.val("generate-pdf-completion-report");
        } else if ($("#current-page").val() == "leave_by_block") {
            $.each($("li.block_target_item"), function (i, v) {
                var hidden_schedule_id = $(document.createElement("input")).attr({"type": "hidden", "name": "schedule_id[]"}).val($(v).data("id"));
                $("#generate-pdf-modal-form").append(hidden_schedule_id);
            });

            $.each($("li.learner_target_item"), function (i, v) {
                var hidden_proxy_id = $(document.createElement("input")).attr({"type": "hidden", "name": "proxy_id[]"}).val($(v).data("id"));
                $("#generate-pdf-modal-form").append(hidden_proxy_id);
            });

            hidden_method.val("generate-pdf-leave-by-block-report");
        } else if($("#current-page").val() == "rotation-leave") {
            var hidden_start_date = $(document.createElement("input")).attr({"type": "hidden", "name": "start-date"}).val($(".start-date").text());
            $("#generate-pdf-modal-form").append(hidden_start_date);

            var hidden_end_date = $(document.createElement("input")).attr({"type": "hidden", "name": "end-date"}).val($(".end-date").text());
            $("#generate-pdf-modal-form").append(hidden_end_date);

            $.each($(".target-ids"), function (i, v) {
                var hidden_proxy_id = $(document.createElement("input")).attr({"type": "hidden", "name": "target_id[]"}).val($(v).val());
                $("#generate-pdf-modal-form").append(hidden_proxy_id);
            });

            hidden_method.val("generate-pdf-rotation-leave-report");
        } else {
            if ($("#pdf_individual_option").is(':checked')) {
                hidden_method.val("generate-pdf-for-tasks-bulk");
            } else {
                hidden_method.val("generate-pdf-bulk");
            }
        }

        if ($("#include-description").is(":checked")) {
            var hidden_description = $(document.createElement("input")).attr({"type": "hidden", "name": "description"}).val($("#description-text").val());
            $(this).append(hidden_description);
        }

        if ($("#current-page").val() == "faculty" && $("input[name=\"course_override\"]").length) {
            var hidden_course_override = $(document.createElement("input")).attr({"type": "hidden", "name": "course_id_override"}).val($("input[name=\"course_override\"]").val());
            $(this).append(hidden_course_override);
        }

        if ($("#current-page").val() == "faculty" && $("input[name=\"distribution_override\"]").length) {
            var hidden_distribution_override = $(document.createElement("input")).attr({"type": "hidden", "name": "distribution_id_override"}).val($("input[name=\"distribution_override\"]").val());
            $(this).append(hidden_distribution_override);
        }

        $(this).append(hidden_method, hidden_start_date, hidden_end_date);
    });

    $("#select-course-btn").on("change", function(e) {
        $("#report-date-range-div").removeClass("hide");
        $("#include-externals-yes").removeAttr("checked");
        $("#include-externals-no").removeAttr("checked");
        $(".faculty_search_target_control").remove();
        $(".faculty_target_item").remove();
        $("#select-faculty-div").addClass("hide");
        $("#add-average-delivery-date").addClass("hide");
        $("#additional-description").addClass("hide");
        $("#output-options").addClass("hide");
        $("#generate-pdf-btn").addClass("hide");
        $("#report-type-div").addClass("hide");
        $("#additional-comments").addClass("hide");
        $("#generate-report").addClass("hide");
        $("#generate-csv").addClass("hide");
        if ($("#report-start-date").val() != null && $("#report-start-date").val() != "" || $("#report-end-date").val() != null && $("#report-end-date").val() != "") {
            $("#include-externals-div").removeClass("hide");
            $("#evaluation-search").removeClass("hide");
        } else {
            $("#include-externals-div").addClass("hide");
            $("#evaluation-search").addClass("hide");
        }

        if ($("#current-page").val() == "rotations") {
            var settings = $("#choose-evaluation-btn").data("settings");
            settings.filters["target"].api_params.course_list = $(".course_search_target_control").val();
            reset_form(true);
        }
    });

    $("#include-externals-yes, #include-externals-no").on("change", function(e) {
        var settings = $("#select-faculty-btn").data("settings");
        settings.filters["faculty"].api_params.add_externals = $("#include-externals-yes").is(":checked");
        $(".faculty_search_target_control").remove();
        $(".faculty_target_item").remove();
        $("#select-faculty-div").removeClass("hide");
        $("#add-average-delivery-date").addClass("hide");
        $("#additional-description").addClass("hide");
        $("#output-options").addClass("hide");
        $("#generate-pdf-btn").addClass("hide");
    });

    $("#select-faculty-btn").on("change", function(e) {
        if ($(".faculty_target_item").length > 0) {
            $("#generate-pdf-btn").removeClass("hide");
            $("#add-average-delivery-date").removeClass("hide");
            $("#additional-description").removeClass("hide");
            if ($("#select-individual-events-checkbox").is(":checked")) {
                $("#output-options").removeClass("hide");
            }
        } else {
            $("#generate-pdf-btn").addClass("hide");
            $("#add-average-delivery-date").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
        }
    });

    function reset_form(remove_targets) {
        $("#msgs").empty();
        $("#evaluation-subtypes").empty();
        $("#form-selector").addClass("hide");
        $("#additional-comments").addClass("hide");
        $("#additional-statistics").addClass("hide");
        $("#additional-description").addClass("hide");
        $("#output-options").addClass("hide");
        $("#generate-report").addClass("hide");
        $("#generate-pdf-btn").addClass("hide");
        if (remove_targets) {
            $(".form_search_target_control").remove();
        }
        $("#include-description").removeAttr("checked");
        $("#description-text").addClass("hide").val("");
        $("#report-type-div").addClass("hide");
        $("#generate-csv").addClass("hide");

        if (remove_targets) {
            $(".target_search_target_control").remove();
            $("#target_list_container").remove();
        }
        var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
        $("#choose-form-btn").html("Browse Forms ").append(icon);

        if ($("#current-page").val() == "faculty") {
            $("#choose-evaluation-btn").html("Browse Faculty ").append(icon);
        } else if ($("#current-page").val() == "learner-reports") {
            $("#choose-evaluation-btn").html("Browse Learners ").append(icon);
        } else if ($("#current-page").val() == "course") {
            $("#choose-evaluation-btn").html("Browse Courses ").append(icon);
        }

        if ($("#current-page").val() == "completion") {
            $("#include-externals-yes").removeAttr("checked");
            $("#include-externals-no").removeAttr("checked");
            $(".faculty_search_target_control").remove();
            $(".faculty_target_item").remove();
            $("#select-faculty-div").addClass("hide");
            $("#add-average-delivery-date").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
            $("#generate-pdf-btn").addClass("hide");
        }
    }

    function update_ui_by_date_range(reset_form_flag) {
        if (typeof reset_form_flag == "undefined") {
            var reset_form_flag = true;
        } else {
            var reset_form_flag = false;
        }

        $("#generate-pdf-btn").addClass("hide");
        if ($("#report-start-date").val().empty() && $("#report-end-date").val().empty()) {
            $("#evaluation-search").addClass("hide");
        } else {
            $("#evaluation-search").removeClass("hide");
        }

        reset_form(reset_form_flag);

        var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
        $("#choose-form-btn").html("Browse Forms ").append(icon);

        var start_date = $("#report-start-date").val();
        var end_date = $("#report-end-date").val();
        var update_start_date = false;
        var update_end_date = false;

        if (isValidDate(start_date) || start_date == null || start_date == "") {
            update_start_date = true;
        }

        if (isValidDate(end_date) || end_date == null || end_date == "") {
            update_end_date = true;
        }

        if (update_start_date && update_end_date) {
            set_date_range_preferences(start_date, end_date);
        } else {
            $("#evaluation-search").addClass("hide");
        }
    }

    function getTargetIDListBySubtype() {
        var target_id_list = [];
        $.each($(".target-subtype-checkbox"), function(key, value) {
            if ($(this).is(":checked")) {
                target_id_list.push($(this).attr("data_key"));
            }
        });
        return target_id_list;
    }

    function isValidDate(dateString) {
        var regEx = /^\d{4}-\d{2}-\d{2}$/;
        return dateString.match(regEx) != null;
    }

    function set_date_range_preferences(start_date, end_date) {
        var cperiod_ids = [];

        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
            data: {
                "method": "set-date-range",
                "start_date": start_date,
                "end_date": end_date,
                "current_page": $("#current-page").val()
            },
            type: "POST",
            success: function (data) {
                $(".cperiod-filter").remove();

                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $.each(jsonResponse.data, function (key, value) {
                        var cperiod = $(document.createElement("input")).attr({
                            type: "hidden",
                            class: "cperiod-filter",
                            name: "cperiod[]",
                            value: key
                        });

                        $("#evaluation-form").append(cperiod);

                        cperiod_ids.push(key);
                    });
                }

                if ($("#current-page").val() == "faculty" || $("#current-page").val() == "learner-reports") {
                    var settings = $("#choose-form-btn").data("settings");
                    settings.filters["form"].api_params.cperiod_ids = cperiod_ids;

                    if ($("#current-page").val() == "learner-reports") {
                        var settings = $("#choose-evaluation-btn").data("settings");
                        settings.filters["target"].api_params.cperiod_ids = cperiod_ids;
                    }
                } else {
                    var settings = $("#choose-evaluation-btn").data("settings");
                    if (settings != null) {
                        settings.filters["target"].api_params.cperiod_ids = cperiod_ids;
                    }
                }
            }
        });
    }

    function getTargetIDList() {
        var target_list = [];

        $.each($(".target_search_target_control"), function(key, value) {
            if ($(this).val()) {
                target_list.push($(this).val());
            }
        });

        return target_list;
    }

    function getCourseIDList() {
        var target_list = [];

        $.each($(".course_search_target_control"), function(key, value) {
            if ($(this).val()) {
                target_list.push($(this).val());
            }
        });

        return target_list;
    }

    function update_evaluation_subtypes() {
        reset_form(false);

        var target_list = $("#current-page").val() == "distribution-schedule" ? getCourseIDList() : getTargetIDList();

        var current_page = $("#current-page").val();
        var settings = $("#choose-evaluation-btn").data("settings");
        var spinner = $(document.createElement("img")).attr({"id": "loading-spinner", "src": ENTRADA_URL + "/images/loading.gif"});
        $("#evaluation-subtypes").append(spinner);

        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
            data: {
                "method": "update-subtypes-" + current_page,
                "target_id_list": target_list,
                "cperiod_ids": settings.filters["target"].api_params.cperiod_ids,
                "course_list": ($("#current-page").val() == "rotations" || $("#current-page").val() == "distribution-schedule") ? settings.filters["target"].api_params.course_list : null,
                "task_type": $("#current-page").val() == "distribution-schedule" ? $("input[name=\"task_type\"]").val() : null
            },
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    // Distribution schedule report doesn't require any sort of form selection
                    if ($("#current-page").val() == "distribution-schedule") {
                        update_distribution_datasource(jsonResponse.data, current_page);
                        $("#distribution-search").removeClass("hide");
                    } else {
                        filterSubtypesByInvalidForms(jsonResponse.data, current_page, target_list.length);
                    }
                } else {
                    if (target_list.length > 0) {
                        reset_form(false);
                        display_error([jsonResponse.data], "#msgs", "prepend");
                    } else {
                        $("#evaluation-subtypes").empty();
                    }
                }
            }
        });
    }

    function buildSubTypeCheckBoxList(target_subtypes, filtered_results, current_page) {
        var header = $(document.createElement("h3")).attr({id: "checkbox-group-header"}).html("Select " + (current_page == "rotations" ? "Distribution Rotations" : "Learning Event Distributions") + " By Curriculum Period:");
        var checkbox_group = $(document.createElement("div")).attr({class: "checkbox-group"});
        var select_all_label = $(document.createElement("label")).attr({ class: "checkbox", for: "select-all-subtypes" }).html("Select All");
        var select_all_checkbox = $(document.createElement("input")).attr({ type: "checkbox", id: "select-all-subtypes" });

        $("#evaluation-subtypes").empty().append(header, checkbox_group.append(select_all_label.append(select_all_checkbox)));

        $.each(target_subtypes, function(key, value) {
            if (isTargetIDFound(value["target_id"], filtered_results)) {
                var label = $(document.createElement("label")).attr({
                    class: "checkbox target-subtype-label",
                    for: "target-subtype-" + value["target_id"]
                }).html(value["target_label"]);
                var cperiod = $(document.createElement("input")).attr({
                    type: "checkbox",
                    class: "target-subtype-checkbox",
                    name: "target-subtype-" + value["target_id"],
                    id: "target-subtype-" + value["target_id"],
                    data_key: value["target_id"],
                    data_adistribution_id: value["adistribution_id"]
                });

                checkbox_group.append(label.append(cperiod));
            }
        });
    }

    function isTargetIDFound(target_id, filtered_results) {
        var is_found = false;
        $.each(filtered_results, function(key, value) {
            if (target_id == value) {
                is_found = true;
            }
        });
        return is_found;
    }

    function filterSubtypesByInvalidForms(target_subtypes, current_page, target_list_size) {
        var target_id_list = [];
        $.each(target_subtypes, function(key, value) {
            target_id_list.push(value["target_id"]);
        });

        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
            data: {
                "method": "validate-subtype-by-evaluations",
                "target_id_list": target_id_list,
                "current_page": current_page
            },
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    buildSubTypeCheckBoxList(target_subtypes, jsonResponse.data, current_page);
                } else {
                    if (target_list_size > 0) {
                        reset_form(false);
                        display_error([jsonResponse.data], "#msgs", "prepend");
                    }
                }
            }
        });
    }

    function update_forms(target_id_list) {
        if (target_id_list.length > 0) {
            $("#form-selector").removeClass("hide");
        } else {
            $("#form-selector").addClass("hide");
        }

        $("#additional-comments").addClass("hide");
        $("#additional-statistics").addClass("hide");
        $("#additional-description").addClass("hide");
        $("#output-options").addClass("hide");
        $("#generate-report").addClass("hide");
        $("#generate-pdf-btn").addClass("hide");
        $("#generate-csv").addClass("hide");
        $("#report-type-div").addClass("hide");

        var icon = $(document.createElement("i")).attr({class: "icon-chevron-down"});
        $("#choose-form-btn").html("Browse Forms ").append(icon);
        $(".form_search_target_control").remove();

        var settings = $("#choose-form-btn").data("settings");
        settings.filters["form"].api_params.target_id_list = target_id_list;
        settings.filters["form"].api_params.current_page = $("#current-page").val();
    }


    $("#generate-rotation-leave-report").on("click", function(e) {
        var start_date = "";
        var end_date = "";
        var target_list = "";
        var comments = "";
        var description = "";

        var report_start_date = $("#report-start-date").val();
        var report_end_date = $("#report-end-date").val();
        var settings = $("choose-evaluation-btn").data("settings");

        $(".target_search_target_control").each(function(i, obj) {
            target_list += "&target_ids[]=" + $(this).val();
        });

        if (isValidDate(report_start_date) && report_start_date != null && report_start_date != "") {
            start_date = "&start-date=" + report_start_date;
            if($("#no-Start-Date-alert").length) {
                $("#no-Start-Date-alert").remove();
            }
        } else {
           createErrorMessage("Start Date");
        }

        if (isValidDate(report_end_date) && report_end_date != null && report_end_date != "") {
            end_date = "&end-date=" + report_end_date;
            if($("#no-End-Date-alert").length) {
                $("#no-End-Date-alert").remove();
            }
        } else {
            createErrorMessage("End Date");
        }

        if ($("#include-description").is(":checked")) {
            description = "&description=" + $("#description-text").val();
        }

        if ($("#include-comments").is(":checked")) {
            comments = "&comments=1";
            if ($("#include-commenter-id").is(":checked")) {
                comments += "&commenter-id=1"
            }
            if ($("#include-commenter-name").is(":checked")) {
                comments += "&commenter-name=1"
            }
        }

        var previous_page = "&previous_page=" + $("#current-page").val();

        if (target_list != "") {
            if($("#no-Resident-alert").length) {
                $("#no-Resident-alert").remove();
            }
            if(start_date && end_date) {
                window.location.href = ENTRADA_URL + "/admin/assessments/reports?section=leave-report" + target_list + previous_page + start_date + end_date + comments + description;
            }
        } else {
            createErrorMessage("Resident");
        }
    });

    function createErrorMessage(alert_item) {
        var alert_item_id = alert_item.replace(" ", "-");
        var error_div = $(document.createElement("div")).attr({class: "alert alert-error", id: "no-"+alert_item_id+"-alert"}).html("You must select a <strong>"+alert_item+"</strong>");
        if(!$("#no-"+alert_item_id+"-alert").length) {
            if ($("#current-page").val() == "rotation-leave") {
                $("#rotation-leave-form").prepend(error_div);
            } else {
                $("#assessment-form").prepend(error_div);
            }
        }
    }

    $("#include-description").on("click", function(e) {
        if ($("#description-text").hasClass("hide")) {
            $("#description-text").removeClass("hide");
        } else {
            $("#description-text").addClass("hide");

        }
    });

    $("#curriculum-period-select").on("change", function(e) {
        $("#block_list_container").empty();
        $("#block_selected_targets_list").empty();
        $(".block_search_target_control").remove();
        $("#learner_list_container").empty();
        $("#learner_selected_targets_list").empty();
        $(".learner_search_target_control").remove();
        $("#select-learners-div").addClass("hide");
        $("#generate-pdf-btn").addClass("hide");
        $("#additional-description").addClass("hide");
        $("#output-options").addClass("hide");

        var settings = $("#select-learners-btn").data("settings");
        settings.filters["learner"].api_params.cperiod_ids = $("#curriculum-period-select option:selected").val();
        settings = $("#select-block-btn").data("settings");
        settings.filters["block"].api_params.cperiod_id = $("#curriculum-period-select option:selected").val();

        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
            data: {
                "method": "set-cperiod-preferences",
                "current_page": $("#current-page").val(),
                "cperiod": $("#curriculum-period-select option:selected").val()
            },
            type: "POST"
        });
    });

    $("#select-block-btn").on("change", function(e) {
        if ($(".block_target_item").length > 0) {
            $("#select-learners-div").removeClass("hide");
        } else {
            $("#select-learners-div").addClass("hide");
            $("#generate-pdf-btn").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
        }
    });

    $("#select-learners-btn").on("change", function(e) {
        if ($("#learner_list_container").length > 0) {
            $("#generate-pdf-btn").removeClass("hide");
            $("#additional-description").removeClass("hide");
            if ($("#select-individual-events-checkbox").is(":checked")) {
                $("#output-options").removeClass("hide");
            }
        } else {
            $("#generate-pdf-btn").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
        }
    });

    $("[name='report-type']").on("change", function(e) {
        if($(this).val() == "aggregated-report") {
            $("#additional-comments").removeClass("hide");
            $("#additional-description").removeClass("hide");
            if ($("#select-individual-events-checkbox").is(":checked")) {
                $("#output-options").removeClass("hide");
            }
            $("#generate-report").removeClass("hide");
            $("#generate-csv").addClass("hide");
        } else {
            $("#additional-comments").addClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
            $("#generate-report").addClass("hide");
            $("#generate-csv").removeClass("hide");
        }
    });

    $("#generate-csv").on("click", function(e) {
        e.preventDefault();

        var hidden_schedule_list = [];
        $.each($(".target-subtype-checkbox:checked"), function (i, v) {
            hidden_schedule_list.push($(v).attr("data_key"));
        });

        var hidden_cperiod_list = [];
        $.each($(".cperiod-filter"), function (i, v) {
            hidden_cperiod_list.push($(v).val());
        });

        window.location = ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports&method=download-rotation-evaluation-csv&schedule_id=" + hidden_schedule_list + "&form_id=" + $(".form-selector").val() + "&cperiod_id=" + hidden_cperiod_list;
    });

    $("#include-statistics").on("change", function () {
        if ($(this).is(":checked")) {
            $("#include-positivity-controls").removeClass("hide");
        } else {
            $("#include-positivity-controls").addClass("hide");
        }
    });

    $("#include-comments").on("change", function () {
        if ($(this).is(":checked")) {
            $("#commenter-id-controls").removeClass("hide");
        } else {
            $("#commenter-id-controls").addClass("hide");

        }
        if ($(this).is(":checked")) {
            $("#commenter-name-controls").removeClass("hide");
        } else {
            $("#commenter-name-controls").addClass("hide");

        }
    });

    function update_distribution_datasource(data, current_page) {
        var advanced_search_settings = $("#select-distribution-btn").data("settings");
        advanced_search_settings.filters["distribution"].data_source = data;
    }

    $("#select-distribution-btn").on("change", function(e) {
        if ($(".distribution_target_item").length > 0) {
            $("#generate-distribution-schedule-report").removeClass("hide");
            $("#additional-description").removeClass("hide");
        } else {
            $("#generate-distribution-schedule-report").removeClass("hide");
            $("#additional-description").addClass("hide");
            $("#output-options").addClass("hide");
        }
    });

    $("#generate-distribution-schedule-report").on("click", function(e) {
        var start_date = "";
        var end_date = "";
        var target_list = "";
        var description = "";

        var report_start_date = $("#report-start-date").val();
        var report_end_date = $("#report-end-date").val();

        var report_type = "&report-type=" + $("input:radio[name ='distribution-schedule-report-type']:checked").val();

        $("input[name='distribution[]']").each(function(i, obj) {
            target_list += "&target_ids[]=" + $(obj).val();
        });

        if (isValidDate(report_start_date) && report_start_date != null && report_start_date != "") {
            start_date = "&start-date=" + report_start_date;
            if($("#no-Start-Date-alert").length) {
                $("#no-Start-Date-alert").remove();
            }
        } else {
            createErrorMessage("Start Date");
        }

        if (isValidDate(report_end_date) && report_end_date != null && report_end_date != "") {
            end_date = "&end-date=" + report_end_date;
            if($("#no-End-Date-alert").length) {
                $("#no-End-Date-alert").remove();
            }
        } else {
            createErrorMessage("End Date");
        }

        if ($("#include-description").is(":checked")) {
            description = "&description=" + $("#description-text").val();
        }

        var previous_page = "&previous_page=" + $("#current-page").val();

        if (target_list != "") {
            if($("#no-Resident-alert").length) {
                $("#no-Resident-alert").remove();
            }
            if(start_date && end_date) {
                window.location.href = ENTRADA_URL + "/admin/assessments/reports?section=schedule-report" + report_type + target_list + previous_page + start_date + end_date + description;
            }
        } else {
            createErrorMessage("Distribution");
        }
    });

});