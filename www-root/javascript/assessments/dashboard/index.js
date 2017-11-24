jQuery(function($) {
    var timeout;
    var offset = 100;
    var table_offset = [0, 0, 0, 0, 0, 0];
    var table_limit = [0, 0, 0, 0, 0, 0];
    var table_buffer = [0, 0, 0, 0, 0, 0];
    var table_list = [
        "assessment-outstanding-tasks"  , "assessment-upcoming-tasks",
        "assessment-deleted-tasks"      , "evaluation-outstanding-tasks",
        "evaluation-upcoming-tasks"     , "evaluation-deleted-tasks"
    ];

    var spinner_div = jQuery(document.createElement("div")).addClass("spinner-container");
    var loading_tasks = jQuery(document.createElement("h3")).html("Loading Tasks...");
    var spinner = jQuery(document.createElement("img")).attr({
        "class": "loading_spinner",
        "src": ENTRADA_URL + "/images/loading.gif"
    });

    $(".task-table").addClass("hide").after(spinner_div.append(loading_tasks, spinner));
    $(".task-btn").addClass("hide");
    $(".reminder-btn").addClass("hide");
    $(".hide-tasks").addClass("hide");
    $(".task-table-search").addClass("hide");

    get_task_data(0, true, false, false);

    $(".tasks-tabs").tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    function populate_tables_with_no_results() {
        $.each(table_list, function (i, v) {
            if ($("." + v + " tbody").children().length == 0 && table_offset[i] < table_limit[i] - offset) {
                table_offset[i] += offset;
                table_buffer[i] += offset;
                $("#tab-" + v).find(".dashboard-no-results").remove();
                get_task_data(i, false, true, false);
            }
        });
    }

    function get_task_data(ctr, all_tabs, populate_tables, search_value) {
        if (ctr != table_list.length) {
            var task_type = table_list[ctr].substring(0, table_list[ctr].indexOf("-"));
            var method = table_list[ctr].substring(table_list[ctr].indexOf("-"), table_list[ctr].length);

            if (all_tabs) {
                var href = "#tab-" + task_type + method;
                table_limit[ctr] = jQuery(".task-tab[href='" + href + "']").find("span").html();
            }

            var get_tasks_request = jQuery.ajax({
                url: ENTRADA_URL + "/admin/assessments?section=api-dashboard",
                data: {
                    "method": "get" + method,
                    "task_type": task_type,
                    "offset": table_offset[ctr],
                    "search_value": search_value ? search_value : ""
                },
                type: "GET"
            });

            jQuery.when(get_tasks_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                var table = jQuery("." + task_type + method);
                
                if (jsonResponse.status === "success") {
                    jQuery.each(jsonResponse.data, function (key, task) {
                        build_task_row(task, table, task_type, method);
                    });

                    jQuery("#" + table.parent().attr("id") + " .spinner-container").remove();
                    jQuery("#" + table.parent().attr("id") + " .task-btn").removeClass("hide");
                    jQuery("#" + table.parent().attr("id") + " .reminder-btn").removeClass("hide");
                    jQuery("#" + table.parent().attr("id") + " .hide-tasks").removeClass("hide");
                    jQuery("#" + table.parent().attr("id") + " .task-table-search").removeClass("hide");
                    jQuery("#tab-" + table_list[ctr]).find(".dashboard-no-results").remove();
                    table.removeClass("hide");

                    if (table_offset[ctr] + offset >= table_limit[ctr]) {
                        jQuery("." + table_list[ctr] + "-next").addClass("disabled");
                    }

                    if (table_offset[ctr] < offset + table_buffer[ctr]) {
                        jQuery("." + table_list[ctr] + "-previous").addClass("disabled");
                    }

                    if (search_value) {
                        jQuery("." + table_list[ctr] + "-search-results-found").removeClass("hide").html("Results Found: " + jQuery("." + table_list[ctr] + " tbody tr").length);
                    } else {
                        jQuery("." + table_list[ctr] + "-search-results-found").addClass("hide");
                    }
                } else {
                    var no_results = jQuery(document.createElement("p")).html(jsonResponse.data).addClass("dashboard-no-results");
                    jQuery("#" + table.parent().attr("id") + " .spinner-container").remove();
                    jQuery("#" + table.parent().attr("id")).append(no_results);
                    jQuery("." + table_list[ctr] + "-search-results-found").addClass("hide");
                    if (populate_tables) {
                        if (table_offset[ctr] < table_limit[ctr] - offset) {
                            table_offset[ctr] += offset;
                            table_buffer[ctr] += offset;
                            jQuery("#tab-" + table_list[ctr]).find(".dashboard-no-results").remove();
                            jQuery("." + table_list[ctr]).addClass("hide").after(spinner_div.append(loading_tasks, spinner));
                            jQuery("." + table_list[ctr] + " tbody").empty();
                            get_task_data(ctr, false, true, false);
                        }
                    }
                }

                if (ctr++ < table_list.length && all_tabs) {
                    get_task_data(ctr, all_tabs, false, false);
                }
            });
        } else {
            populate_tables_with_no_results();
        }
    }

    function build_task_row(task, table, task_type, method) {
        var tr = jQuery(document.createElement("tr"));
        var td_task_info = jQuery(document.createElement("td"));

        if (method == "-outstanding-tasks") {
            var td_task_info_a = "";
            var td_task_type_span = "";

            if (task.task_type == "delegation") {
                td_task_info_a = jQuery(document.createElement("a")).attr({
                    "href": ENTRADA_URL + "/assessments/delegation?addelegation_id=" + task.task_id +
                    "&adistribution_id=" + task.adistribution_id
                }).html(task.title);

                td_task_type_span = jQuery(document.createElement("span")).addClass("dashboard-delegation-badge").html("Delegation");
            } else {
                var reviewer_parameter = "";
                var target_parameter = "target_record_id=" + task["0"]["target_record_id"];
                var progress_parameter = "";

                if (task.task_type == "approver") {
                    reviewer_parameter = "&approver_task=true";
                    td_task_type_span = jQuery(document.createElement("span")).addClass("dashboard-release-badge").html("Reviewer");
                } else {
                    td_task_type_span = jQuery(document.createElement("span")).addClass("dashboard-assessment-evaluation-badge").html(capitalize_first_letter(task_type));
                }

                if ("1" in task) {
                    target_parameter = "section=targets";
                }

                if (task.aprogress_id) {
                    progress_parameter = "&aprogress_id=" + task.aprogress_id;
                }

                td_task_info_a = jQuery(document.createElement("a")).attr({
                    "href": ENTRADA_URL + "/assessments/assessment?" + target_parameter +
                    "&adistribution_id=" + task.adistribution_id +
                    "&dassessment_id=" + task.task_id +
                    progress_parameter +
                    reviewer_parameter
                }).html(task.title);
            }
            td_task_info.append(td_task_info_a);
        }

        var td_assessor_target_info = jQuery(document.createElement("td"));
        var td_assessor_target_info_p = jQuery(document.createElement("p")).html(task.full_name);
        td_assessor_target_info.append(td_assessor_target_info_p);

        var td_additional_info = jQuery(document.createElement("td")).addClass("dashboard-center-info");
        var td_additional_info_p = jQuery(document.createElement("p"));
        if (method == "-outstanding-tasks") {
            var target_names = [];

            if (task.task_type != "delegation") {
                var ctr = 0;
                do {
                    if (task.task_type == "approver" || task[ctr]["progress"][0] == "pending" || task[ctr]["progress"][0] == "inprogress") {
                        target_names.push(task[ctr]["name"]);
                    }
                    ctr++;
                } while (task[ctr] != undefined);
            } else {
                var ctr = 0;
                do {
                    if (task[ctr]["use_members"]) {
                        target_names.push(task[ctr]["member_fullname"]);
                    } else {
                        target_names.push(task[ctr]["entity_name"]);
                    }
                    ctr++;
                } while (task[ctr] != undefined);
            }

            var td_additional_info_span = jQuery(document.createElement("span")).addClass("dashboard-pending-task dashboard-circle");
            td_additional_info_p.html(target_names.length).attr({
                "data-toggle": "tooltip",
                "data-original-title": target_names,
                "data-placement": "bottom"
            }).addClass("dashboard-progress-circle tooltip-tag");

            var td_remind_task = jQuery(document.createElement("td")).addClass("dashboard-center-info");
            var td_remove_task = jQuery(document.createElement("td")).addClass("dashboard-center-info");
            var td_remove_task_i = jQuery(document.createElement("i")).addClass("icon-trash icon-white remove-icon");
            if (task.task_type != "approver") {
                var td_remove_task_span = jQuery(document.createElement("span")).addClass("remove").attr({
                    "data-toggle": "modal",
                    "data-target": "#remove_form_modal",
                    "data-assessment": "{" +
                        "\"assessor_type\":\"" + (task.assessor_type == 0 ? null : task.assessor_type) + "\"," +
                        "\"assessor_value\":\"" + task.assessor_value + "\"," +
                        "\"target_id\":\"" + (target_names.length == 1 && task[0]["target_record_id"] !== undefined ? task[0]["target_record_id"] : null) + "\"," +
                        "\"distribution_id\":\"" + task.adistribution_id + "\"," +
                        "\"assessment_id\":\"" + task.task_id + "\"," +
                        "\"task_type\":\"" + task.task_type + "\"," +
                        "\"delivery_date\":\"" + task.delivery_date + "\"" +
                    "}"
                });

                var td_remove_task_a = jQuery(document.createElement("a")).addClass("dashboard-remove-task dashboard-circle").attr({"href": "#remove_form_modal"});
                td_remove_task.append(td_remove_task_span.append(td_remove_task_a.append(td_remove_task_i)));
            } else {
                var td_remove_task_span = jQuery(document.createElement("span")).addClass("dashboard-remove-task-disabled dashboard-circle");
                var td_remove_task_p = jQuery(document.createElement("p")).addClass("dashboard-progress-circle tooltip-tag").attr({
                    "data-toggle": "tooltip",
                    "data-original-title": "Reviewer tasks can not be removed.",
                    "data-placement": "bottom"
                });
                td_remove_task.append(td_remove_task_span.append(td_remove_task_p.append(td_remove_task_i)));
            }

            var td_remind_task_input = jQuery(document.createElement("input")).addClass("remind remind-" + task_type).attr({
                "type": "checkbox",
                "name": "remind[]",
                "data-assessor-name": task.full_name,
                "data-assessor-id": task.assessor_value,
                "data-adistribution-id": task.task_type == "delegation" ? task.adistribution_id : null,
                "data-addelegation-id": task.task_type == "delegation" ? task.task_id : null,
                "data-task-type": task.task_type,
                "value": task.task_id
            });
            td_remind_task.append(td_remind_task_input);
            table.append(tr.append(td_task_info.prepend(td_task_type_span.addClass("space-right")), td_assessor_target_info, td_additional_info.append(td_additional_info_span.append(td_additional_info_p)), td_remind_task, td_remove_task));
        } else if (method == "-deleted-tasks") {
            td_additional_info.removeClass("dashboard-center-info");
            var td_task_info_a = jQuery(document.createElement("a")).attr({
                "href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + task.adistribution_id
            }).html(task.title);

            // Construct human readable date for display from the delivery timestamp.
            var date = new Date(task.delivery_date * 1000);
            if (date.getFullYear() > 1970) {
                var formatted_date = date.getFullYear() + "-" + (("0" + (date.getMonth() + 1)).slice(-2)) + "-" + ("0" + date.getDate()).slice(-2);
                var td_task_delivery_span = jQuery(document.createElement(("span"))).addClass("label dashboard-delivery-badge space-right").html(formatted_date);
                td_task_info.append(td_task_delivery_span);
            }

            var td_hide_task = jQuery(document.createElement("td")).addClass("dashboard-center-info");
            var td_hide_task_input = jQuery(document.createElement("input")).addClass("hide-task hide-task-" + task_type).attr({"type": "checkbox", "value": task.deleted_task_id});

            td_additional_info_p.html(task.deleted_reason_notes == null || task.deleted_reason_notes == "" ? task.reason_details : task.deleted_reason_notes);
            table.append(tr.append(td_task_info.append(td_task_info_a), td_assessor_target_info, td_additional_info.append(td_additional_info_p), td_hide_task.append(td_hide_task_input)));
        } else {
            var td_task_info_a = jQuery(document.createElement("a")).attr({
                "href": ENTRADA_URL + "/admin/assessments/distributions?section=progress&adistribution_id=" + task.adistribution_id
            }).html(task.title);

            // Construct human readable date for display from the delivery timestamp.
            var date = new Date(task.delivery_date * 1000);
            if (date.getFullYear() > 1970) {
                var formatted_date = date.getFullYear() + "-" + (("0" + (date.getMonth() + 1)).slice(-2)) + "-" + ("0" + date.getDate()).slice(-2);
                var td_task_delivery_span = jQuery(document.createElement(("span"))).addClass("label dashboard-delivery-badge space-right").html(formatted_date);
                td_task_info.append(td_task_delivery_span);
            }

            td_task_info.append(td_task_info_a);

            var td_upcoming_target = jQuery(document.createElement("td"));
            var td_upcoming_target_name = jQuery(document.createElement("p")).html(task.target_name);
            td_upcoming_target.append(td_upcoming_target_name);

            var td_remove_task = jQuery(document.createElement("td")).addClass("dashboard-center-info");
            var td_remove_task_span = jQuery(document.createElement("span")).addClass("remove").attr({
                "data-toggle": "modal",
                "data-target": "#remove_form_modal",
                "data-assessment": "{" +
                    "\"assessor_type\":\"" + task.assessor_type + "\"," +
                    "\"assessor_value\":\"" + task.assessor_value + "\"," +
                    "\"target_id\":\"" + task.target_value + "\"," +
                    "\"distribution_id\":\"" + task.adistribution_id + "\"," +
                    "\"assessment_id\":\"" + null + "\"," +
                    "\"delivery_date\":\"" + task.delivery_date + "\"" +
                "}"
            });

            var td_remove_task_a = jQuery(document.createElement("a")).addClass("dashboard-remove-task dashboard-circle").attr({"href": "#remove_form_modal"});
            var td_remove_task_i = jQuery(document.createElement("i")).addClass("icon-trash icon-white remove-icon");
            td_remove_task.append(td_remove_task_span.append(td_remove_task_a.append(td_remove_task_i)));
            table.append(tr.append(td_task_info, td_assessor_target_info, td_upcoming_target, td_remove_task));
        }
    }

    $(".task-btn").on("click", function (e) {
        var select_button = $(this);

        $.each(table_list, function (i, v) {
            if (select_button.hasClass(v + "-next") && !select_button.hasClass("disabled")) {
                if (table_offset[i] + offset < table_limit[i]) {
                    table_offset[i] += offset;
                    $("." + v).addClass("hide").after(spinner_div.append(loading_tasks, spinner));
                    $("." + v + " tbody").empty();
                    get_task_data(i, false, false, false);
                    $("." + v + "-previous").removeClass("disabled");
                }
            }

            if (select_button.hasClass(v + "-previous") && !select_button.hasClass("disabled")) {
                if (table_offset[i] - offset >= table_buffer[i]) {
                    table_offset[i] -= offset;
                    $("." + v).addClass("hide").after(spinner_div.append(loading_tasks, spinner));
                    $("." + v + " tbody").empty();
                    get_task_data(i, false, false, false);
                    $("." + v + "-next").removeClass("disabled");
                }
            }
        });
    });

    $(".reminder-btn").on("click", function (e) {
        $("#reminder-modal").removeClass("hide");
        $("#reminders-selected").addClass("hide");
        $("#no-reminders-selected").addClass("hide");
        $("#reminders-success").addClass("hide");
        $("#reminders-error").addClass("hide");
        $("#reminder-summary-table").addClass("hide");
        $("#reminder-details-section").addClass("hide");
        var assessor_data = [];
        var task_type = $(".task-list-tabs .active").text().slice(0, -1).toLowerCase();

        var tasks_to_remind = $("input.remind-" + task_type + ":checked").map(function () {
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
        var task_type = $(".task-list-tabs .active").text().slice(0, -1).toLowerCase();

        var assessor_array          = [];
        var dassessment_id_array    = [];
        var delegator_tasks_array   = [];
        var approver_tasks_array    = [];

        $("input.remind-" + task_type + ":checked").each(function (i, v) {
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
                    $("input[type=checkbox].remind-" + task_type).each(function () {
                        $(this).attr("checked", false);
                    });
                    $(".select-all-reminders").removeClass("all-selected");
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

    $(".select-all-reminders").on("click", function() {
        var task_type = $(".task-list-tabs .active").text().slice(0, -1).toLowerCase();
        if ($(this).hasClass("all-selected")) {
            $(this).removeClass("all-selected");
            $(".remind-" + task_type).removeAttr("checked");
        } else {
            $(this).addClass("all-selected");
            $(".remind-" + task_type).attr({"checked": "checked"});
        }
    });

    $(".select-all-deleted-tasks").on("click", function() {
        var task_type = $(".task-list-tabs .active").text().slice(0, -1).toLowerCase();
        if ($(this).hasClass("all-selected")) {
            $(this).removeClass("all-selected");
            $(".hide-task-" + task_type).removeAttr("checked");
        } else {
            $(this).addClass("all-selected");
            $(".hide-task-" + task_type).attr({"checked": "checked"});
        }
    });

    $(".container").on("click", ".remove", function() {
        $("#current_record_data").val(JSON.stringify($(this).data("assessment")));
        $("#remove_form_modal").removeClass("hide");
    });

    $(".container").on("click", "#save_remove", function() {
        var task_data = JSON.parse($("#current_record_data").val());

        if (task_data !== undefined) {
            var removing_reason_id = $("input[name='reason']:checked").val();
            var removing_reason = $("#other_reason").val();
            var notification = task_data["target_id"] ? "yes" : "no";
            if (removing_reason_id !== undefined) {
                if (removing_reason_id > 1 || removing_reason.trim() != "") {
                    $.ajax({
                        url: ENTRADA_URL + "/assessments/assessment?section=api-assessment&adistribution_id=" + task_data["distribution_id"] + "&hide_deleted_task=true",
                        data: {
                            "method": "delete-task",
                            "task_data_array": [task_data],
                            "reason_id": removing_reason_id,
                            "reason_notes": removing_reason,
                            "notify": notification
                        },
                        type: "POST",
                        success: function(data) {
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
    });

    $(".hide-tasks").on("click", function() {
        var deleted_task_ids = [];
        var task_type = $(".task-list-tabs .active").text().slice(0, -1).toLowerCase();

        $.each($("input.hide-task-" + task_type + ":checked"), function (i, v) {
            deleted_task_ids.push($(v).val());
        });

        if (deleted_task_ids.length > 0) {
            var hide_selected_tasks = jQuery.ajax({
                url: ENTRADA_URL + "/admin/assessments?section=api-dashboard",
                data: {
                    "method": "hide-deleted-tasks",
                    "deleted_task_ids": deleted_task_ids
                },
                type: "POST"
            });

            jQuery.when(hide_selected_tasks).done(function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    location.reload();
                }
            });
        }
    });

    $(".task-table-search").keyup(function (e) {
        var keycode = e.keyCode;
        var table_search = $(this);

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

            if (table_search.val().length >= 3 || table_search.val().length == 0) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    $.each(table_list, function (i, v) {
                        if (table_search.hasClass(v + "-search")) {
                            $("." + v).addClass("hide").after(spinner_div.append(loading_tasks, spinner));
                            $("." + v + " tbody").empty();
                            $("#tab-" + v).find(".dashboard-no-results").remove();
                            table_offset[i] = table_buffer[i];
                            get_task_data(i, false, false, table_search.val());
                            if (table_search.val().length != 0) {
                                $("." + v + "-next").addClass("disabled");
                                $("." + v + "-previous").addClass("disabled");
                            } else if (table_offset[i] + offset < table_limit[i]) {
                                $("." + v + "-next").removeClass("disabled");
                            }
                        }
                    });
                }, 1000);
            }
        }
    });

    function capitalize_first_letter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    $(".task-list-tabs .nav-tabs li").on("click", function (e) {
        if ($(this).attr("id") == "report-tab") {
            $("#additional-dashboard-details").addClass("hide");
        } else {
            $("#additional-dashboard-details").removeClass("hide");
        }
    });
});