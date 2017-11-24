/**
 * Safely parse JSON and return a default error message (single string) if failure.
 * @param data JSON
 * @param default_message string
 * @returns {Array}
 */
function safeParseJson(data, default_message) {
    try {
        var jsonResponse = JSON.parse(data);
    } catch (e) {
        var jsonResponse = [];
        jsonResponse.status = "error";
        jsonResponse.data = [default_message];
    }
    return jsonResponse;
}

var timeout;
var limit = 10;
var current_section;
var current_page;

jQuery(document).ready(function ($) {
    $("#assessments").tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    build_filter_list();

    $("#task_start_date").on("change", function (e) {
        var advanced_search_settings = $("#advanced-search").data("settings");
        advanced_search_settings.api_params["start_date"] = $(this).val();
    });

    $("#task_end_date").on("change", function (e) {
        var advanced_search_settings = $("#advanced-search").data("settings");
        advanced_search_settings.api_params["end_date"] = $(this).val();
    });

    if ($("#learner-curriculum-period-select").length > 0) {
        filterLearners($("#learner-curriculum-period-select").val());
    }

    $(".container").on("change", "#learner-curriculum-period-select", function (e) {
        filterLearners($(this).val());

        $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: {
                method: "set-curriculum-period",
                cperiod_id: $("#learner-curriculum-period-select").val()
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = safeParseJson(data, "Unable to save curriculum period preference.");
                if (jsonResponse.status == "success") {
                    console.log("Successfully updated curriculum period preference.")
                } else {
                    console.log("Unable to update curriculum period preference.")
                }
            }
        });
    });

    $('#form_index_tabs a[data-toggle="tab"]').on('shown', function(e){
        createCookie(proxy_id+'assessment_index_last_tab', $(e.target).attr('href'));
    });

    var lastTab = readCookie(proxy_id+'assessment_index_last_tab');
    if (lastTab) {
        $('#form_index_tabs a[href=' + lastTab + ']').tab('show');
    } else {
        $('#form_index_tabs a[data-toggle="tab"]:first').tab('show');
    }

    if (!lastTab) {
        lastTab = "#" + jQuery("#assessments > div.tab-pane.active").attr("id");
    }

    $("#learner-search-form").on("submit", function (e) {
        e.preventDefault();
    });

    $("#learner-search").keyup(function () {
        filterLearners($("#learner-curriculum-period-select").val());
    });

    $("#faculty-search-form, #external-faculty-search-form").on("submit", function (e) {
        e.preventDefault();
    });

    $("#faculty-search").keyup(function () {
        facultySearch($(this).val());
    });

    function facultySearch(search_text) {
        var card_visibility_status = $("#change-faculty-card-visibility").attr("data-show-faculty");
        var cards_displayed = false;

        if (search_text.length == 0) {
            $(".faculty-card").each(function () {
                if (card_visibility_status == "hide" && !$(this).hasClass("hidden") || card_visibility_status == "show") {
                    $(this).removeClass("hide").addClass("visible");
                    cards_displayed = true;
                }
            });
            $("#faculty-detail-container .no-search-targets").remove();
        } else {
            search_text = search_text.toLowerCase();
            $("#faculty-detail-container .no-search-targets").remove();
            $(".faculty-card").each(function () {
                if (card_visibility_status == "hide" && !$(this).hasClass("hidden") || card_visibility_status == "show") {
                    var assessor_type = $(this).find(".assessor-type-badge").html().toLowerCase();
                    var text = $(this).find("h3").text().toLowerCase();
                    if (text.indexOf(search_text) >= 0 || assessor_type.indexOf(search_text) >= 0) {
                        $(this).removeClass("hide").addClass("visible");
                        cards_displayed = true;
                    } else {
                        $(this).addClass("hide").removeClass("visible");
                    }
                }
            });
        }

        if (!cards_displayed) {
            if ($("#faculty-detail-container .no-search-targets").length == 0) {
                var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-faculty-blocks"}).html("No users found matching your search.");
                $("#faculty-detail-container").append(no_search_targets_p);
            }
        } else {
            $("#faculty-detail-container .no-search-targets").remove();
        }
    }

    $(".reminder-btn").on("click", function (e) {
        $("#reminder-modal").removeClass("hide");
        $("#reminders-selected").addClass("hide");
        $("#no-reminders-selected").addClass("hide");
        $("#reminders-success").addClass("hide");
        $("#reminders-error").addClass("hide");
        $("#reminder-summary-table").addClass("hide");
        $("#reminder-details-section").addClass("hide");

        var assessor_data = [];
        var tasks_to_remind = $("input.remind:checked").map(function () {
            if (assessor_data[$(this).data("assessor-id")] != undefined) {
                assessor_data[$(this).data("assessor-id")].count++;
            } else {
                assessor_data[$(this).data("assessor-id")] = {name: $(this).data("assessor-name"), count: 1};
            }

            return this.value;
        }).get();

        $("#reminder-summary-table tbody").html("");
        // Add a row to the summary table for each assessor to be reminded.
        if (assessor_data.length > 0) {
            $.each(assessor_data, function (i, v) {
                if (v != undefined) {
                    $("#reminder-summary-table tbody:last-child").append("<tr><td>" + v.name + "</td><td>" + v.count + "</td></tr>");
                }
            });
        }

        if (tasks_to_remind.length > 0) {
            $("#reminders-selected").removeClass("hide");
            $("#reminder-details").removeClass("hide");
            $("#reminder-modal-confirm").removeClass("hide");
            $("#reminder-details-section").removeClass("hide");
            $("#reminder-reason-section").removeClass("hide");
            $("#reminder-summary-table").removeClass("hide");
            $("#reminders-selected div.alert ul li span").html(tasks_to_remind.length);
            $("#reminders-success div.alert ul li span").html(tasks_to_remind.length);
            $("#reminders-error div.alert ul li span").html(tasks_to_remind.length);
        } else {
            $("#reminder-modal-confirm").addClass("hide");
            $("#no-reminders-selected").removeClass("hide");
        }
    });

    $("#reminder-modal-confirm").on("click", function (e) {
        e.preventDefault();
        send_task_reminders();
    });

    function send_task_reminders() {
        var url = $("#reminder-modal-form").attr("action");

        var assessor_array          = [];
        var dassessment_id_array    = [];
        var delegator_tasks_array   = [];
        var approver_tasks_array    = [];

        $("input.remind:checked").each(function (i, v) {
            if ($(v).data("task-type") == undefined || $(v).data("task-type") == "task") {
                assessor_array[i] = $(v).data("assessor-id");
                dassessment_id_array[i] = $(v).val();
            } else if ($(v).data("task-type") == "delegation") {
                delegator_tasks_array.push({
                    "adistribution_id": $(this).data("adistribution-id"),
                    "addelegation_id": $(this).data("addelegation-id")
                });
            } else {
                approver_tasks_array.push({
                    "dassessment_id": $(v).val(),
                    "approver_id": $(this).data("assessor-id")
                });
            }
        });

        $.ajax({
            url: url,
            data: {
                "method": "send-reminders",
                "dassessment_id_array": dassessment_id_array,
                "assessor_array": assessor_array,
                "delegator_tasks_array": delegator_tasks_array,
                "approver_tasks_array": approver_tasks_array
            },
            type: "POST",
            success: function (data) {
                $("#reminders-selected").addClass("hide");
                $("#no-reminders-selected").addClass("hide");
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $("input[type=checkbox].remind").each(function () {
                        $(this).attr("checked", false);
                    });
                    $(".icon-bell").removeClass("select-all");
                    $("#reminders-success").removeClass("hide");
                    $("#reminders-selected").addClass("hide");
                    $("#reminder-details").addClass("hide");
                    $("#reminder-modal-confirm").addClass("hide");
                    $("#reminder-details-section").addClass("hide");
                    $("#reminder-reason-section").addClass("hide");
                } else {
                    $(jsonResponse.data).each(function (i, v) {
                        display_error(v, "#reminders-error");
                    });
                    $("#reminders-error").removeClass("hide");
                }
            }
        });
    }

    $(".container").on("click", ".remove", function() {
        if ($(this).data("assessment-id") != undefined) {
            var task_object = {
                "assessment_id": $(this).data("assessment-id"),
                "assessor_value": $(this).data("assessor-value"),
                "assessor_type": $(this).data("task-type") == "delegation" ? null : $(this).data("assessor-type"),
                "target_id": $(this).data("task-type") == "delegation" ? null : $(this).data("target-id"),
                "task_type": $(this).data("task-type"),
                "distribution_id": $(this).data("distribution-id"),
                "delivery_date": $(this).data("delivery-date")
            };
            $("#current_record_data").val(JSON.stringify(task_object));
        } else {
            $("#current_record_data").val(JSON.stringify($(this).data("assessment")));
        }
        $("#remove_form_modal").removeClass("hide");
    });

    $(".container").on("click", "#save_remove", function() {
        if ($("#current_record_data").val() !== undefined && $("#current_record_data").val() != null && $("#current_record_data").val() != "") {
            var task_data = JSON.parse($("#current_record_data").val());

            if (task_data !== undefined) {
                var removing_reason_id = $("input[name='reason']:checked").val();
                var removing_reason = $("#other_reason").val();
                var notification = task_data["target_id"] ? "yes" : "no";
                if (removing_reason_id !== undefined) {
                    if (removing_reason_id > 1 || removing_reason.trim() != "") {
                        $.ajax({
                            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment&adistribution_id=" + task_data["distribution_id"],
                            data: {
                                "method": "delete-task",
                                "task_data_array": [task_data],
                                "reason_id": removing_reason_id,
                                "reason_notes": removing_reason,
                                "notify": notification
                            },
                            type: "POST",
                            success: function (data) {
                                var jsonResponse = JSON.parse(data);
                                if (jsonResponse.status == "success") {
                                    location.reload();
                                } else {
                                    display_error(jsonResponse.data, "#remove-msgs");
                                }
                            }
                        });
                    } else {
                        display_error(["Please indicate why you are removing this assessment task from your task list."], "#remove-msgs");
                    }
                } else {
                    display_error(["Please select an option."], "#remove-msgs");
                }
            } else {
                display_error(["Task data missing, unable to remove task."], "#remove-msgs");
            }
        } else {
            display_error(["Task data missing, unable to remove task."], "#remove-msgs");
        }
    });

    // Filters learners based on name and the curriculum periods they are/were a part of.
    function filterLearners (cperiod_id) {
        $("#generate-pdf").removeClass("hide");
        var search_text = $("#learner-search").val().toLowerCase();

        $("#learner-detail-container .no-search-targets").remove();
        $(".learner-card").each(function () {
            var text = $(this).text().toLowerCase();
            text = text.substring(0, text.search(/\d/));
            if (cperiod_id > 0) {
                if (text.indexOf(search_text) !== -1) {
                    var cperiod_ids = $(this).data("cperiod_ids").toString().split("-");
                    if (cperiod_ids) {
                        if (cperiod_ids.indexOf(cperiod_id) >= 0) {
                            $(this).removeClass("hide").addClass("visible");
                        } else {
                            $(this).addClass("hide").removeClass("visible");
                        }
                    } else {
                        $(this).addClass("hide").removeClass("visible");
                    }
                } else {
                    $(this).addClass("hide").removeClass("visible");
                }
            } else {
                if (search_text.length == 0) {
                    $(".learner-card").removeClass("hide").addClass("visible");
                    $("#learner-detail-container .no-search-targets").remove();
                } else {
                    $("#learner-detail-container .no-search-targets").remove();
                    $(".learner-card").each(function () {
                        var text = $(this).text().toLowerCase();
                        text = text.substring(0, text.search(/\d/));
                        if (text.indexOf(search_text) !== -1) {
                            $(this).removeClass("hide").addClass("visible");
                        } else {
                            $(this).addClass("hide").removeClass("visible");
                        }
                    });
                }
            }
        });

        if ($(".learner-card.visible").length == 0) {
            if ($("#learner-detail-container .no-search-targets").length == 0) {
                var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-learner-blocks"}).html("No users found matching your search.");
                $("#learner-detail-container").append(no_search_targets_p);
                $("#generate-pdf").addClass("hide");
            }
        } else {
            $("#learner-detail-container .no-search-targets").remove();
        }
    }

    $("#assessment-reports-group-by-distribution").on("click", function(e) {
        var group_by_distribution = 0;
        if ($("#assessment-reports-group-by-distribution").attr("checked") == "checked") {
            group_by_distribution = 1;
        }

        $.ajax({
            url: "?section=api-reports",
            data: {
                method: "save-preferences",
                group_by_distribution: group_by_distribution
            },
            type: "GET",
            success: function (data) {
                var jsonResponse = safeParseJson(data, assessment_reports.default_error_message);
                if (jsonResponse.status == "success") {
                    location.reload();
                } else {
                    display_error(jsonResponse.data, "#reports-error-msg");
                }
            }
        });
    });

    $(".curriculum-period-selector").on("change", function() {
        $.ajax({
            url: "?section=api-reports",
            data: {
                method: "set-curriculum-period",
                report_cperiod_id: $(this).val()
            },
            type: "GET",
            success: function (data) {
                var jsonResponse = safeParseJson(data, assessment_reports.default_error_message);
                if (jsonResponse.status == "success") {
                    location.reload();
                } else {
                    display_error(jsonResponse.data, "#reports-error-msg");
                }
            }
        });
    });

    $("#generate-pdf").on("click", function(e) {
        $(this).attr("href", $(this).attr("href") + "&selected-cperiod=" + $("#learner-curriculum-period-select").val() + "&search-term=" + ($("#learner-search").val().trim().length > 0 ? $("#learner-search").val().trim().toLowerCase() : ""));
    });

    function sort_table(table_name){
        if ($(table_name + " tbody  tr").length) {
            var rows = $(table_name + " tbody  tr").get();

            rows.sort(function (a, b) {
                var first_delivery_date  = new Date($(a).children("td").find(".delivery-date").data("delivery-date"));
                var second_delivery_date = new Date($(b).children("td").find(".delivery-date").data("delivery-date"));

                if (first_delivery_date > second_delivery_date) {
                    return -1;
                }

                if (first_delivery_date <= second_delivery_date) {
                    return 1;
                }

                return 0;
            });

            $.each(rows, function (index, row) {
                $(table_name).children("tbody").append(row);
            });
        }
    }
    sort_table(".completed");

    function get_tasks(load_more_tasks, append) {
        var search_value    = jQuery("#task-search").val();
        if (search_value.replace(/\s/g, "").length || search_value == "") {
            if (!load_more_tasks) {
                jQuery(".form-search-message").remove();
            }
            var start_date = jQuery("#task_start_date").val();
            var end_date = jQuery("#task_end_date").val();
            var proxy_id = jQuery("#proxy_id").val();
            var organisation_id = jQuery("#organisation_id").val();
            var is_external = jQuery("#is_external").val();

            var tab_list = ["incomplete", "completed_on_me", "completed"];
            if (current_section == "learner") {
                tab_list = ["completed_on_me", "pending", "upcoming", "incomplete", "future"];
            } else if (current_section == "faculty") {
                tab_list = ["incomplete", "completed", "future"];
            }

            if (load_more_tasks) {
                tab_list = ["completed"];
            } else {
                jQuery("#offset").val(0);
            }

            var success = false;

            jQuery.each(tab_list, function (i, tab) {
                var spinner_div = jQuery(document.createElement("div")).addClass("spinner-container").attr({"id":"spinner-"+tab});
                var loading_tasks = jQuery(document.createElement("h3")).attr({"id": "loading_h3"}).html("Loading Assessment Tasks...");
                var spinner = jQuery(document.createElement("img")).attr({
                    "id": "loading_spinner",
                    "src": ENTRADA_URL + "/images/loading.gif"
                });

                if (!load_more_tasks) {
                    jQuery("#assessment-tasks." + tab).empty();
                }

                jQuery("#assessment-tasks." + tab).after(spinner_div.append(loading_tasks, spinner));

                var get_tasks_request = jQuery.ajax({
                    url: ENTRADA_URL + "/assessments?section=api-tasks",
                    data: {
                        "method": "get-tasks",
                        "search_value": search_value,
                        "start_date": start_date,
                        "end_date": end_date,
                        "current_page": tab,
                        "current_section": current_section,
                        "proxy_id": proxy_id,
                        "org_id": organisation_id,
                        "is_external" : is_external,
                        "limit": (tab == "completed") ? limit : 0,
                        "offset": (tab == "completed") ? jQuery("#offset").val() : 0
                    },
                    type: "GET"
                });

                jQuery.when(get_tasks_request).done(function (data) {
                    if (!load_more_tasks) {
                        jQuery("#assessment-tasks." + tab).empty();
                    }
                    jQuery(".spinner-container[id=spinner-"+ tab +"]").remove();

                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status === "success") {
                        if (append == "false") {
                            $("ul." + tab).empty();
                        }

                        var tasks = [];
                        jQuery.each(jsonResponse.data, function (key, task) {
                            var task_object = build_assessment_card(task, tab);
                            tasks.push(task_object);
                        });

                        switch (current_section) {
                            case "assessments" :
                                switch (tab) {
                                    case "incomplete":
                                        $("#assessment-tasks.incomplete").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.incomplete .complete").addClass("hide");
                                        break;
                                    case "completed":
                                        $("#assessment-tasks.completed").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.completed .pending").addClass("hide");
                                        $("#assessment-tasks.completed .inprogress").addClass("hide");

                                        $("a.remove").each(function () {
                                            $(this).addClass("hide");
                                            $(this).parent().parent().removeClass("btn-group");
                                        });
                                        break;
                                    case "completed_on_me":
                                        $("#assessment-tasks.completed_on_me").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.completed_on_me .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.completed_on_me .details").addClass("hide");
                                        $("#assessment-tasks.completed_on_me .completed_date").addClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor").removeClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor-group-role").removeClass("hide");
                                        break;
                                }
                                break;
                            case "learner" :
                                switch (tab) {
                                    case "completed_on_me" :
                                        $("#assessment-tasks.completed_on_me").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.completed_on_me .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor").removeClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.completed_on_me .assessor-group-role").removeClass("hide");
                                        break;
                                    case "incomplete" :
                                        $("#assessment-tasks.incomplete").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.incomplete .complete").addClass("hide");
                                        $("#assessment-tasks.incomplete .pdf-download").addClass("hide");
                                        $("#assessment-tasks.incomplete .task-reminder").removeClass("hide");
                                        $("#assessment-tasks.upcoming .remove").removeClass("hide");
                                        $("#assessment-tasks.upcoming .remove-task-link").html("Remove Task");
                                        $("#assessment-tasks.incomplete .send-reminder-text").removeClass("hide").append("Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks.");
                                        break;
                                    case "upcoming" :
                                        $("#assessment-tasks.upcoming").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.upcoming .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.upcoming .details").addClass("hide");
                                        $("#assessment-tasks.upcoming .assessment-task-select").addClass("hide");
                                        $("#assessment-tasks.upcoming .view-task-link").addClass("hide");
                                        $("#assessment-tasks.upcoming .remove-task-link").parent().parent().removeClass("btn-group");
                                        $("#assessment-tasks.upcoming .remove").removeClass("hide");
                                        $("#assessment-tasks.upcoming .remove-task-link").html("Remove Task");
                                        $("#assessment-tasks.upcoming .assessor").removeClass("hide");
                                        $("#assessment-tasks.upcoming .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.upcoming .assessor-group-role").removeClass("hide");
                                        break;
                                    case "future" :
                                        $("#assessment-tasks.future").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.future .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.future .details").addClass("hide");
                                        $("#assessment-tasks.future .assessment-task-select").addClass("hide");
                                        $("#assessment-tasks.future .view-task-link").addClass("hide");
                                        $("#assessment-tasks.future .remove-task-link").parent().parent().removeClass("btn-group");
                                        $("#assessment-tasks.future .remove").removeClass("hide");
                                        $("#assessment-tasks.future .remove-task-link").html("Remove Task");
                                        $("#assessment-tasks.future .assessor").removeClass("hide");
                                        $("#assessment-tasks.future .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.future .assessor-group-role").removeClass("hide");
                                        break;
                                    case "pending" :
                                        $("#assessment-tasks.pending").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.pending .assessor").removeClass("hide");
                                        $("#assessment-tasks.pending .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.pending .complete").addClass("hide");
                                        $("#assessment-tasks.pending .assessment-task-delegation-badge").addClass("hide");
                                        $("#assessment-tasks.pending .assessment-task-select").addClass("hide");
                                        $("#assessment-tasks.pending .view-task-link").addClass("hide");

                                        $("#assessment-tasks.pending .remove-task-link").parent().parent().removeClass("btn-group");
                                        $("#assessment-tasks.pending .remove").removeClass("hide");
                                        $("#assessment-tasks.pending .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.pending .details").addClass("hide");
                                        $("#assessment-tasks.pending .assessment-task-meta").removeClass("hide");

                                        $.each($("#assessment-tasks.pending .assessor-group-role"), function () {
                                            if ($.isNumeric($(this).closest(".assessment-task").find(".remind").val())) {
                                                $(this).closest(".assessment-task").find(".task-reminder").removeClass("hide");
                                                $(this).closest(".assessment-task").find(".send-reminder-text").append("Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks.").removeClass("hide");
                                                $(this).closest(".assessment-task").find(".remove-task-link").html("Remove Task");
                                            }
                                        });
                                        break;
                                }
                                break;
                            case "faculty" :
                                switch (tab) {
                                    case "incomplete" :
                                        $("#assessment-tasks.incomplete").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.incomplete .complete").addClass("hide");
                                        $("#assessment-tasks.incomplete .send-reminder-text").append("Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks.");
                                        $("#assessment-tasks.incomplete .delegated-by").addClass("hide");
                                        break;
                                    case "completed" :
                                        $("#assessment-tasks.completed").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.completed .pending").addClass("hide");
                                        $("#assessment-tasks.completed .inprogress").addClass("hide");

                                        $("a.remove").each(function () {
                                            $(this).addClass("hide");
                                            $(this).parent().parent().removeClass("btn-group");
                                        });
                                        break;
                                    case "future" :
                                        $("#assessment-tasks.future").loadTemplate($("#assessment-card"), tasks, {append: true});
                                        $("#assessment-tasks.future .assessment-progress").addClass("hide");
                                        $("#assessment-tasks.future .details").addClass("hide");
                                        $("#assessment-tasks.future .assessment-task-select").addClass("hide");
                                        $("#assessment-tasks.future .view-task-link").addClass("hide");
                                        $("#assessment-tasks.future .remove-task-link").parent().parent().removeClass("btn-group");
                                        $("#assessment-tasks.future .remove").removeClass("hide");
                                        $("#assessment-tasks.future .remove-task-link").html("Remove Task");
                                        $("#assessment-tasks.future .assessor").removeClass("hide");
                                        $("#assessment-tasks.future .assessor-data").removeClass("hide");
                                        $("#assessment-tasks.future .assessor-group-role").removeClass("hide");
                                        break;
                                }
                                break;
                        }

                        if (tab != "completed") {
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .distribution"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .future"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .details"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-link"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-title-div"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-date"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-progress"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessor"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessor.future"));
                            $.fn.matchHeight._apply($(".assessment-card-description"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper.future"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper"));
                            $.fn.matchHeight._apply($(".assessment-task"));
                        }

                        $(".assessment-task-schedule-info-badge:empty").addClass("hide");
                        $(".assessment-task-date-range:empty").addClass("hide");
                        $(".assessment-task-delegation-badge:empty").addClass("hide");
                        $(".assessment-task-release-schedule-info-badge:empty").addClass("hide");
                        $(".assessment-task-date:empty").addClass("hide");
                        $(".assessment-task-event-info-badge:empty").addClass("hide");
                        $(".assessor-group-role:empty").addClass("hide");
                        $(".send-reminder-text:empty").addClass("hide");
                        $(".generate-pdf-text:empty").addClass("hide");
                        $(".delegated-date:empty").addClass("hide");
                        $(".task-description:empty").addClass("hide");
                        $(".assessor-external-badge:empty").addClass("hide");
                        $(".event-date-range:empty").addClass("hide");

                        if ($("." + tab + " .send-reminder-text:empty")) {
                            $("." + tab + " .send-reminder-text:empty").parent().parent().addClass("hide");
                        }

                        $(".complete .progress-circle div:empty").each(function () {
                            $(this).parent().parent().addClass("hide");
                            $(this).closest(".assessment-progress").find(".progress-title").html("Completed <strong>N/A</strong>");
                        });

                        $(".generate-pdf-text:empty").each(function () {
                            $(this).parent().parent().addClass("hide");
                        });

                        $(".remove-task-link:empty").each(function () {
                            $(this).parent().addClass("hide");
                            $(this).parent().parent().removeClass("btn-group");
                        });

                        $(".pending-attempts-text:empty").each(function () {
                            $(this).parent().parent().addClass("hide");
                        });

                        $(".inprogress-attempts-text:empty").each(function () {
                            $(this).parent().parent().addClass("hide");
                        });

                        if (tab == "completed") {
                            jQuery("#load-tasks").removeClass("hide complete");
                            sort_table(".completed");
                        }

                        if (tab == "incomplete") {
                            var progress_html = $("#assessment-tasks.incomplete .progress-title").html();
                            if (progress_html != "" && progress_html != null) {
                                $("#assessment-tasks.incomplete .progress-title").html(progress_html.replace("Completed", "Progress"));
                            }
                        } else if (tab == "completed_on_me") {
                            $("#assessment-tasks.completed_on_me .details").removeClass("hide");
                            $.fn.matchHeight._apply($(".assessment-task.completed_on_me.details"));
                        } else if (tab == "completed") {
                            $("#assessment-tasks.completed .clearfix").each(function (index, element) {
                                if ($(element).parent().hasClass("completed")) {
                                    $(element).remove();
                                }
                            });

                            $.fn.matchHeight._apply($(".assessment-task-wrapper .distribution"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .future"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .details"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-link"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-title-div"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-task-date"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessment-progress"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessor"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper .assessor.future"));
                            $.fn.matchHeight._apply($(".assessment-card-description"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper.future"));
                            $.fn.matchHeight._apply($(".assessment-task-wrapper"));
                            $.fn.matchHeight._apply($(".assessment-task"));
                        }

                        $(".incomplete .progress-title").each(function (index, element) {
                            $(element).html($(element).html().replace("Completed", "Progress"));
                        });
                    } else {
                        if (jQuery("#assessment-tasks." + tab + ":empty") && !load_more_tasks) {
                            display_error_message(tab);
                        }

                        if (tab == "completed") {
                            jQuery("#load-tasks").addClass("hide complete");
                        }
                    }
                });
            });
        }
    }

    function build_assessment_card (task, tab) {
        var task_object = {
            assessment_task_title: task.title,
            schedule_details: task.schedule_details,
            task_url: task.url,
            task_details: (!task.details ? "No details provided." : task.details)
        };

        task_object["data_task_type"] = task.type;

        if (task.delivery_date) {
            task_object["delivery_date"] = "Delivered on <strong>" + task.delivery_date + "</strong>";
        }

        if (task.rotation_start_date != false && task.rotation_end_date != false) {
            task_object["date_range"] = task.rotation_start_date + " to " + task.rotation_end_date;
        } else if (task.start_date != false && task.end_date != false) {
            if (task.event_start_date == false && task.event_end_date == false) {
                task_object["date_range"] = task.start_date + " to " + task.end_date;
            } else {
                task_object["event_date_range"] = task.start_date + " to " + task.end_date;
                task_object["date_range"] = task.event_start_date + " to " + task.event_end_date;
            }
        }

        if (task.completed_date != false && task.completed_date != null) {
            task_object["completed_date"] = "Completed on <strong>" + task.completed_date + "</strong>";
            task_object["progress_text"] = "Completed";
        } else {
            task_object["progress_text"] = "Progress";
        }

        if (task.type == "delegation") {
            if (tab != "completed" && tab != "complete_on_faculty" && tab != "completed_on_me") {
                task_object["remove_task_text"] = "Remove Task";
            }
            task_object["delegation_label"] = "Delegation Task";
            task_object["completed_date"] = "";
            task_object["progress_text"] = task.completed_date != null && task.completed_date ? "Completed <strong>N/A</strong>" : "Progress <strong>N/A</strong>";
            task_object["assessor_data"] = "Assessor: <strong>" + task.assessor + "</strong>";
            if (task.group != "" && task.role != "") {
                task_object["assessor_group_role"] = task.group + " • " + task.role;
            }

            if (task.assessor_type == "external") {
                task_object["assessor_group_role"] = "External";
            }

            if (task.delegated_date != false) {
                task_object["delegated_date"] = "Delegated on <strong>" + task.delegated_date + "</strong>";
            }

            if (task.delegated_by) {
                task_object["delegated_by"] = "Delegated by <strong>" + task.delegated_by + "</strong>";
            }
        }

        if (task.type == "approver") {
            task_object["generate_pdf_text"] = "Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks.";
            task_object["approver_label"] = "Reviewer Task";
        }

        if (task.type == "assessment") {
            task_object["generate_pdf_text"] = "Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks.";
            task_object["complete_attempts"] = task.total_targets;
            task_object["event_details_label"] = task.event_details;
            task_object["assessor_data"] = "Assessor: <strong>" + task.assessor + "</strong>";

            if (task.group != "" && task.role != "") {
                task_object["assessor_group_role"] = task.group + " • " + task.role;
            }

            if (task.assessor_type == "external") {
                task_object["assessor_group_role"] = "External";
            }

            if (task.targets_pending > 0 || task.targets_inprogress > 0) {
                task_object["remove_task_text"] = "Remove Task";
            }

            if (task.completed_targets > 0) {
                task_object["complete_attempts"] = task.completed_targets;
                task_object["progress_title_complete"] = task.target_names_complete;
            }

            if (task.total_targets > 1) {
                task_object["inprogress_attempts"] = task.targets_inprogress;
                task_object["pending_attempts"] = task.targets_pending;
                task_object["progress_title_inprogress"] = task.target_names_inprogress;
                task_object["progress_title_pending"] = task.target_names_pending;
            } else {
                if (task.targets_inprogress > 0) {
                    task_object["inprogress_attempts"] = task.targets_inprogress;
                    task_object["progress_title_inprogress"] = task.target_names_inprogress;
                }

                if (task.targets_pending > 0) {
                    task_object["pending_attempts"] = task.targets_pending;
                    task_object["progress_title_pending"] = task.target_names_pending;
                }
            }
        }

        if (task.type == "completed") {
            task_object["progress_title_pending"] = task.target_names_pending;
            task_object["generate_pdf_text"] = "Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks.";
            task_object["assessor_data"] = "Assessor: <strong>" + task.assessor + "</strong>";
            task_object["progress_text"] = "Completed";

            if (task.group != "" && task.role != "") {
                task_object["assessor_group_role"] = task.group + " • " + task.role;
            }

            if (task.assessor_type == "external") {
                task_object["assessor_group_role"] = "External";
            }

            if (!task.details) {
                task_object["task_details"] = task.description;
            }
        }

        task_object["data_assessor_name"] = task.assessor;
        task_object["data_assessor_type"] = task.assessor_type;
        task_object["data_target_id"] = task.target_id;
        task_object["data_assessor_id"] = task.assessor_value;
        task_object["data_assessor_value"] = task.assessor_value;
        task_object["data_targets"] = task.target_info;
        task_object["data_assessment_id"] = task.dassessment_id;
        task_object["data_adistribution_id"] = task.adistribution_id;
        task_object["value"] = task.delivery_date_timestamp;

        if (!task.details) {
            task_object["task_details"] = task.description;
            if (!task_object["task_details"]) {
                task_object["task_details"] = "This assessment was completed on " + task.completed_date;
            }
        }

        if (tab == "future") {
            task_object["assessor_data"] = "Target: ";
            task_object["assessor_group_role"] = "";

            jQuery(task.targets).each(function (index, target) {
                task_object["assessor_data"] += "<strong>" + target.name + "</strong>, ";

                if (target.group != "" && target.role != "") {
                    var group_role = target.group + " • " + target.role + ", ";
                    task_object["assessor_group_role"] += group_role.charAt(0).toUpperCase() + group_role.slice(1);
                }
            });

            task_object["assessor_data"] = task_object["assessor_data"].slice(0, -2);
            task_object["assessor_group_role"] = task_object["assessor_group_role"].slice(0, -2);

            if (task.delivery_date) {
                task_object["delivery_date"] = "Will be delivered on <strong>" + task.delivery_date + "</strong>";
            }
        } else if (tab == "upcoming") {
            if (task.delivery_date) {
                task_object["delivery_date"] = "Will be delivered on <strong>" + task.delivery_date + "</strong>";
            }
        }

        if (task_object["data_target_id"] == null && task.targets != null && task.targets !== undefined && task.targets[0]!= null && task.targets[0] != null && task.targets[0] !== undefined && task.targets[0]["target_value"] != null && task.targets[0]["target_value"] !== undefined) {
            task_object["data_target_id"] = task.targets[0]["target_value"];
        }

        return task_object;
    }

    function display_error_message(tab) {
        var message = "";

        switch (current_section) {
            case "assessments" :
                switch (tab) {
                    case "incomplete":
                        message = "You currently have no <strong>Assessments</strong> to complete.";
                        break;
                    case "completed_on_me":
                        message = "No <strong>Assessments</strong> have been completed on you.";
                        break;
                    case "completed":
                        message = "You currently have no completed <strong>Assessments</strong> to review.";
                        break;
                }
                break;
            case "learner" :
                switch (tab) {
                    case "completed_on_me":
                        message = "Learner currently has no <strong>Assessments</strong> completed on them.";
                        break;
                    case "pending":
                        message = "No <strong>Pending Tasks</strong> on learner.";
                        break;
                    case "upcoming":
                        message = "No <strong>Upcoming Tasks</strong> on learner.";
                        break;
                    case "incomplete":
                        message = "Learner has no <strong>Assessments</strong> to complete.";
                        break;
                    case "future":
                        message = "Learner has no <strong>Upcoming Tasks</strong>.";
                        break;
                }
                break;
            case "faculty" :
                switch (tab) {
                    case "incomplete":
                        message = "Faculty has no <strong>Assessments</strong> to complete.";
                        break;
                    case "completed":
                        message = "Faculty has no completed <strong>Assessments</strong> to review.";
                        break;
                    case "future":
                        message = "Faculty has no <strong>Upcoming Tasks</strong>.";
                        break;
                }
                break;
        }

        if (jQuery("#active-filters ul li").length >= 1) {
            message = "No <strong>Assessments</strong> found.";
        }

        var no_results_div = jQuery(document.createElement("div")).addClass("form-search-message");
        var no_results_span = jQuery(document.createElement("span")).html(message);
        jQuery("#assessment-tasks." + tab).append(no_results_div.append(no_results_span));
    }

    function build_filter_list() {
        var active_filters = jQuery("#active-filters");
        var filter_div = jQuery(document.createElement("div")).addClass("well well-small filter-well");
        var create = false, distribution_subtitle_created = false, cperiod_subtitle_created = false, program_subtitle_created = false;

        filter_div.append(jQuery(document.createElement("div")).addClass("title").html("Active Filters"));
        active_filters.empty();

        jQuery(":input[class^=search-target-control]").each(function (index, element) {
            create = true;
            var type = jQuery(element).attr("name").replace("_" , " ").slice(0, -2);
            var label_span = jQuery(document.createElement("span")).addClass("label label-info filter-label").html(jQuery(element).data("label"));

            switch (type) {
                case "distribution method":
                    if (!distribution_subtitle_created) {
                        var distribution_filter = jQuery(document.createElement("div")).attr({"id": "distribution-filter"});
                        var distribution_subtitle = jQuery(document.createElement("span")).addClass("subtitle").html("Distribution Method");
                        filter_div.append(distribution_filter.append(distribution_subtitle));
                        distribution_subtitle_created = true;
                    }
                    filter_div.find("#distribution-filter").append(label_span);
                    break;
                case "cperiod":
                    if (!cperiod_subtitle_created) {
                        var cperiod_filter = jQuery(document.createElement("div")).attr({"id": "cperiod-filter"});
                        var cperiod_subtitle = jQuery(document.createElement("span")).addClass("subtitle").html("Curriculum Period");
                        filter_div.append(cperiod_filter.append(cperiod_subtitle));
                        cperiod_subtitle_created = true;
                    }
                    filter_div.find("#cperiod-filter").append(label_span);
                    break;
                case "program":
                    if (!program_subtitle_created) {
                        var program_filter = jQuery(document.createElement("div")).attr({"id": "program-filter"});
                        var program_subtitle = jQuery(document.createElement("span")).addClass("subtitle").html("Program");
                        filter_div.append(program_filter.append(program_subtitle));
                        program_subtitle_created = true;
                    }
                    filter_div.find("#program-filter").append(label_span);
                    break;
            }
        });

        if (create) {
            active_filters.append(filter_div);
        }
    }

    $("#task-search").keyup(function (e) {
        var keycode = e.keyCode;
        var append = $(this).attr("data-append");

        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32                    ||
            keycode == 13                    ||
            keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
            var advanced_search_settings = $("#advanced-search").data("settings");
            advanced_search_settings.api_params["search_value"] = $(this).val();

            clearTimeout(timeout);
            timeout = setTimeout(function() {
                get_tasks(false, false);
            }, 1000);
        }
    });

    $("#apply_filters").on("click", function(e) {
        var filter_settings = $("#advanced-search").data("settings");
        filter_settings.apply_filter_function(e);
    });

    $("#remove_filters").on("click", function(e) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-tasks",
            data: { method: "remove-" + current_section + "-filters" },
            type: "POST",
            success: function (data) {
                window.location.reload();
            }
        });
    });

    $('a[data-toggle="tab"]').on("shown", function (e) {
        current_page = $(".tab-pane.active").find(".current_page").val();
        $("#assessment_tasks_filter_container").removeClass("hide");

        if (!$("#load-tasks").hasClass("complete")) {
            $("#load-tasks").removeClass("hide");
        }

        if (current_section == "assessments" && (current_page == "learner_cards" || current_page == "faculty_cards")) {
            $("#assessment_tasks_filter_container").addClass("hide");
            $("#load-tasks").addClass("hide");
        } else if (current_page == "complete_on_faculty") {
            $("#assessment_tasks_filter_container").addClass("hide");
        }
    });

    function capitalize_first_letter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    $("#assessment-report-create-pdf").on("click", function(e) {
        e.preventDefault();
        if ($(this).data("pdf-unavailable") == 1) {
            display_error([assessment_reports.pdf_unavailable], "#pdf-button-error");
        } else {
            window.location = $(this).attr("href");
        }
    });

    $(".generate-pdf-btn").on("click", function (e) {
        if ($(".generate-pdf-btn." + current_page + "").data("pdf-unavailable") == "1") {
            display_error([progress_page_translations.pdf_unavailable], "#assessment-error");
            e.preventDefault();
            $("#assessment-error").removeClass("hide");
        } else {
            $("#generate-pdf-modal-confirm").attr("disabled", false);
            $("#generate-pdf-modal").removeClass("hide");
            $("#generate-pdf-details-section").removeClass("hide");
            $("#download_option").removeClass("hide");
            $("#generate-pdf-modal-confirm").removeClass("hide");
            $("#no-generate-selected").addClass("hide");
            $("#generate-error").addClass("hide");
            $("#generate-success").addClass("hide");
            $("#pdf_individual_option").attr("checked", true);

            var delegations_to_generate = get_tasks_to_generate();
            if (delegations_to_generate.length > 0) {
                var tbody = document.createElement("tbody");
                $.each(delegations_to_generate, function (i, v) {
                    var tr = document.createElement("tr");
                    var formatted_date = "N/A";
                    if (v.delivery_date !== "false" && v.delivery_date != "") {
                        var delivery_date = new Date(v.delivery_date * 1000);
                        formatted_date = delivery_date.getFullYear() + "-" + (("0" + (delivery_date.getMonth() + 1)).slice(-2)) + "-" + ("0" + delivery_date.getDate()).slice(-2);
                    }
                    $(tr).html("<td>" + v.assessor_name + "</td><td>" + v.target_name + "</td><td>" + formatted_date + "</td>");
                    tbody.appendChild(tr);
                });
                $("#generate-pdf-details-table tbody").html("");
                document.getElementById("generate-pdf-details-table").appendChild(tbody);
            } else {
                $("#no-generate-selected").removeClass("hide");
                $("#generate-pdf-details-section").addClass("hide");
                $("#download_option").addClass("hide");
                $("#generate-pdf-modal-confirm").addClass("hide");
            }
        }
    });

    $("#generate-pdf-modal").on("shown", function() {
        $('input[name="task_data"]').remove();
        $('input[name="method"]').remove();
    });

    $("#generate-pdf-modal-form").submit(function (e) {
        $("#generate-pdf-modal-confirm").attr("disabled", true);
        $("#generate-pdf-modal").modal("hide");

        var task_data = get_tasks_to_generate();
        var hidden_task_data = $("<input>").attr("type", "hidden").attr("name", "task_data").val(JSON.stringify(task_data));
        var hidden_method = null;
        if ($("#pdf_individual_option").is(':checked')) {
            hidden_method = $("<input>").attr("type", "hidden").attr("name", "method").val("generate-pdf-for-tasks");
        } else {
            hidden_method = $("<input>").attr("type", "hidden").attr("name", "method").val("generate-pdf");
        }
        $(this).append(hidden_task_data, hidden_method);
    });

    $(".select-all-to-download").on("click", function () {
        $(this).hasClass("select-all") ? $(this).removeClass("select-all") : $(this).addClass("select-all");
        if ($(this).hasClass("select-all")) {
            $(this).find(".label-select").addClass("hide");
            $(this).find(".label-unselect").removeClass("hide");
        } else {
            $(this).find(".label-select").removeClass("hide");
            $(this).find(".label-unselect").addClass("hide");
        }
        $("#assessment-tasks." + current_page + " input[name=\"generate-pdf[]\"]").each(function () {
            $(this).prop("checked", $(".select-all-to-download").hasClass("select-all"));
        });
    });

    $(".select-all-to-remind").on("click", function () {
        $(this).hasClass("select-all") ? $(this).removeClass("select-all") : $(this).addClass("select-all");
        if ($(this).hasClass("select-all")) {
            $(this).find(".label-select").addClass("hide");
            $(this).find(".label-unselect").removeClass("hide");
        } else {
            $(this).find(".label-select").removeClass("hide");
            $(this).find(".label-unselect").addClass("hide");
        }
        $("#assessment-tasks." + current_page + " input[name=\"remind[]\"]").each(function () {
            $(this).prop("checked", $(".select-all-to-remind").hasClass("select-all"));
        });
    });

    $(".icon-bell").on("click", function (e) {
        var bell_icon = $(this);
        bell_icon.hasClass("select-all") ? $(this).removeClass("select-all") : $(this).addClass("select-all");

        $("#assessment-tasks-table." + current_page + " input[name=\"remind[]\"]").each(function () {
            if (!$(this).prop("disabled")) {
                $(this).prop("checked", bell_icon.hasClass("select-all"));
            }
        });
    });

    function get_tasks_to_generate() {
        var tasks_to_generate = [];
        $("#assessment-tasks." + current_page + " .generate-pdf:checked").each(function() {
            var assessor_name = $(this).data("assessor-name");
            var assessor_value = $(this).data("assessor-value");
            var dassessment_id = $(this).data("assessment-id");
            var adistribution_id = $(this).data("adistribution-id");
            var delivery_date = $(this).val();

            $.each($(this).data("targets"), function(key, target) {
                var add_tasks = true;
                var target_id = 0;

                if (current_page == "incomplete") {
                    if ("progress" in target) {
                        $.each(target.progress, function(key, progress) {
                            if (progress == "complete") {
                                add_tasks = false;
                            }
                        });
                    }
                } else if (current_page == "completed") {
                    if ("progress" in target) {
                        $.each(target.progress, function(key, progress) {
                            if (progress != "complete") {
                                add_tasks = false;
                            }
                        });
                    } else {
                        add_tasks = false;
                    }
                }

                if ("proxy_id" in target) {
                    target_id = target.proxy_id;
                } else if ("target_record_id" in target) {
                    target_id = target.target_record_id;
                } else {
                    add_tasks = false;
                }

                if (add_tasks) {
                    var assignment_detail = {
                        "target_name": target.name,
                        "target_id": target_id,
                        "assessor_name": assessor_name,
                        "assessor_value": assessor_value,
                        "dassessment_id": dassessment_id,
                        "adistribution_id": adistribution_id,
                        "aprogress_id": ("aprogress_id" in target) ? target.aprogress_id : null,
                        "delivery_date": delivery_date
                    };
                    tasks_to_generate.push(assignment_detail);
                }
            });
        });
        return tasks_to_generate;
    }

    if ($("#external-faculty-detail-container li").length == 0) {
        $("#change-faculty-view").addClass("hide");
    }

    $(".update-external-assessor-email").on("click", function() {
        $("#edit-external-error").empty();
        $("#edit-external-success").addClass("hide");

        var external_email = $(this).closest("li").find(".external-email");
        $("#edit-external-email").val(external_email.html());
        $("#save-external-email-confirm").attr("data-external-id", $(this).data("proxy-id"));
        $("#edit-external-modal").modal("show");
    });

    $("#edit-external-email").keydown(function(e){
        if (e.keyCode == 13) {
            updateExternalEmail();
        }
    });

    $("#save-external-email-confirm").on("click", function() {
        updateExternalEmail();
    });

    function updateExternalEmail() {
        $("#edit-external-error").empty();
        $("#edit-external-success").addClass("hide");

        var update_email_request = $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: {
                method: "update-external-assessor-email",
                email: $("#edit-external-email").val(),
                external_id: $("#save-external-email-confirm").data("external-id")
            },
            type: "POST"
        });

        $.when(update_email_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "Unable to save external assessor email.");
            if (jsonResponse.status == "success") {
                $("#edit-external-success").removeClass("hide");
                window.location.reload();
            } else {
                $(jsonResponse.data).each(function (i, error) {
                    display_error(error, "#edit-external-error");
                });
            }
        });
    }

    $(".change-external-assessor-visibility").on("click", function() {
        var card_view = "add";
        var li = $(this).closest("li");
        var a = li.find(".change-external-assessor-visibility");
        if (li.hasClass("hidden")) {
            a.html("Hide Card");
            li.removeClass("hidden");
            card_view = "remove";
        } else {
            li.addClass("hidden hide");
            a.html("Unhide Card");
        }

        $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: {
                method: "control-external-assessor-visibility",
                external_id: $(this).data("proxy-id"),
                card_view: card_view
            },
            type: "POST"
        });
    });

    $("#change-faculty-card-visibility").on("click", function() {
        if ($(this).attr("data-show-faculty") == "hide") {
            $(this).val("Hide External Faculty");
            $(this).attr("data-show-faculty", "show");
            $(".faculty-card").each(function() {
                $(this).removeClass("hide");
            });
        } else {
            $(this).val("Show Hidden External Faculty");
            $(this).attr("data-show-faculty", "hide");
            $(".faculty-card.hidden").each(function() {
                $(this).addClass("hide");
            });
        }
        facultySearch($("#faculty-search").val());
    });

    $("#load-tasks").on("click", function() {
        var append = $(this).attr("data-append");
        $("#offset").val(parseInt($("#offset").val()) + limit);
        get_tasks(true, append);
    });

    $("#report-start-date, #report-end-date").on("change", function(e) {
        var report_start_date = $("#report-start-date").val();
        var report_end_date = $("#report-end-date").val();

        if (!isValidDate(report_start_date)) {
            report_start_date = null;
        }

        if (!isValidDate(report_end_date)) {
            report_end_date = null;
        }

        set_date_range_preferences(report_start_date, report_end_date);
    });

    function set_date_range_preferences(report_start_date, report_end_date) {
        var cperiod_ids = [];

        var proxy_id = $("#specified_proxy_id").val();
        var role = "&role=" + $("#specified_target_role").val();
        var start_date = report_start_date == null ? "" : "&start-date=" + report_start_date;
        var end_date = report_end_date == null ? "" : "&end-date=" + report_end_date;

        $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
            data: {
                "method": "set-date-range",
                "start_date": report_start_date,
                "end_date": report_end_date,
                "current_page": current_page
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    var cperiod_list = "";

                    $.each(jsonResponse.data, function (key, value) {
                        if (key) {
                            cperiod_list += "&cperiod_ids[]=" + key;
                        }
                    });

                    window.location.href = ENTRADA_URL + "/assessments/reports?proxy_id=" + proxy_id  + role + cperiod_list + start_date + end_date;
                }
            }
        });
    }

    function isValidDate(dateString) {
        var regEx = /^\d{4}-\d{2}-\d{2}$/;
        return dateString.match(regEx) != null;
    }

    if ($(".faculty-card").length == 0) {
        $("#change-faculty-card-visibility").addClass("hide");
    }

    current_section = $("#current_section").val();
    current_page = $(".tab-pane.active").find(".current_page").val();
    if (current_section == "assessments" && (current_page == "learner_cards" || current_page == "faculty_cards")) {
        $("#assessment_tasks_filter_container").addClass("hide");
        $("#load-tasks").addClass("hide");
    } else if (current_page == "complete_on_faculty") {
        $("#assessment_tasks_filter_container").addClass("hide");
    }

    /**
     * Makes elements with the same class the same height
     */
    $(".assessment-task-wrapper .distribution").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper.future").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .details").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessment-progress").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessor").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessor.future").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-card-description").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper.future").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $("#pending_forms_on_learner .assessment-task").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessment-task-link").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessment-task-title-div").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });

    $(".assessment-task-wrapper .assessment-task-date").matchHeight({
        byRow: true,
        property: "height",
        target: null,
        remove: false
    });
});