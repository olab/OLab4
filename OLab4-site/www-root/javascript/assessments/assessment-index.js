var timeout;
var limit = 10;
var current_section;
var current_page;

function isValidDate(dateString) {
    var regEx = /^\d{4}-\d{2}-\d{2}$/;
    return dateString.match(regEx) != null;
}

jQuery(document).ready(function ($) {
    $("#assessments").tooltip({
        selector: '[data-toggle="tooltip"]'
    });
    $(document).on("hover", ".assessment-card-target-circle", function (e) {
        var target_element = $(this);
        if (target_element.data("targets-loaded") !== "loaded"
            && target_element.data("targets-loaded") !== "loading"
        ) {
            target_element.data("targets-loaded", "loading");
            $.ajax({
                url: ENTRADA_URL + "/assessments/?section=api-tasks",
                data: {
                    "method": "get-target-list",
                    "dassessment_id": $(this).data("dassessment-id"),
                    "progress_type": $(this).data("progress-type")
                },
                type: "GET",
                success: function (data) {
                    target_element.data("targets-loaded", "0");
                    var jsonResponse = safeParseJson(data, assessments_index.default_error_message);
                    if (jsonResponse.status === "success") {
                        target_element.data("targets-loaded", "loaded");
                        $(".tooltip").fadeOut(100, function (e) {
                            target_element.attr("data-original-title", jsonResponse.data);
                            target_element.trigger("mouseover");
                        });
                    }
                },
                error: function (data) {
                    target_element.data("targets-loaded", "0");
                }
            });
        }
    });

    $('#form_index_tabs a[data-toggle="tab"]').on('shown', function (e) {
        createCookie(proxy_id + 'assessment_index_last_tab', $(e.target).attr('href'));
        toggleFilterControls();
    });

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
            // TODO: Localize this
            success: function (data) {
                var jsonResponse = safeParseJson(data, "Unable to save curriculum period preference.");
                if (jsonResponse.status === "success") {
                    console.log("Successfully updated curriculum period preference.")
                } else {
                    console.log("Unable to update curriculum period preference.")
                }
            }
        });
    });

    $("#task-search").keyup(function (e) {
        var keycode = e.keyCode;
        var append = $(this).attr("data-append");

        if ((keycode > 47 && keycode < 58)
            || (keycode > 64 && keycode < 91)
            || (keycode > 95 && keycode < 112)
            || (keycode > 185 && keycode < 193)
            || (keycode > 218 && keycode < 223)
            || keycode == 32
            || keycode == 13
            || keycode == 8
        ) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
            var advanced_search_settings = $("#advanced-search").data("settings");
            advanced_search_settings.api_params["search_value"] = $(this).val();

            clearTimeout(timeout);
            timeout = setTimeout(function () {
                    // TODO: Change this to fetch using current filters, and calling the tasks object for new data, then loading assessment cards via load_assessment_card()
                    $("#apply_filters").trigger("click");
                },
                1000
            );
        }
    });

    $("#apply_filters").on("click", function (e) {
        var filter_settings = $("#advanced-search").data("settings");
        filter_settings.apply_filter_function(e);
    });

    $("#remove_filters").on("click", function (e) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-tasks",
            data: {
                method: "remove-" + $("#filter-mode").val() + "-filters"
            },
            type: "POST",
            success: function (data) {
                window.location.reload();
            }
        });
    });

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

        var no_results_container =  $("#faculty-detail-container .no-search-targets");

        if (search_text.length == 0) {
            $(".faculty-card").each(function () {
                if (card_visibility_status == "hide" && !$(this).hasClass("hidden") || card_visibility_status == "show") {
                    $(this).removeClass("hide").addClass("visible");
                    cards_displayed = true;
                }
            });
           no_results_container.remove();
        } else {
            search_text = search_text.toLowerCase();
            no_results_container.remove();
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

        if (cards_displayed) {
            no_results_container.remove();
        } else {
            if (no_results_container.length == 0) {
                var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-faculty-blocks"}).html("No users found matching your search.");
                $("#faculty-detail-container").append(no_search_targets_p);
            }
        }
    }

    /**
     * Handle all reminders at once.
     */
    $(".select-all-to-remind").on("click", function (e) {
        var button = $(this);
        var parent_id = button.parent().parent().attr("id");
        var checkbox_selector = "#" + parent_id + " input[type='checkbox'].remind";

        if (button.data("select-all-enabled")) {
            button.data("select-all-enabled", false);
            button.html(assessments_index.btn_select_all);
            $(checkbox_selector).removeProp("checked");
        } else {
            $(checkbox_selector).prop("checked", true);
            button.html(assessments_index.btn_deselect_all);
            button.data("select-all-enabled", true);
        }
    });

    $(".reminder-btn").on("click", function (e) {
        $("#reminder-modal").removeClass("hide");
        $("#reminders-selected").addClass("hide");
        $("#no-reminders-selected").addClass("hide");
        $("#reminders-success").addClass("hide");
        $("#reminders-error").addClass("hide");
        $("#reminder-summary-table").addClass("hide");
        $("#reminder-details-section").addClass("hide");
        $("#reminder-modal-confirm").removeProp("disabled");
        $("#reminder-modal-confirm").removeClass("disabled");

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
        } else {
            $("#reminder-modal-confirm").addClass("hide");
            $("#no-reminders-selected").removeClass("hide");
        }
    });

    /**
     * Handle reminder notification send request (button press)
     */
    $("#reminder-modal-confirm").on("click", function (e) {
        e.preventDefault();

        var reminder_data = [];
        // On confirm, iterate through all of the reminder checkboxes (not just the specific scoped ones, but all of the selected reminders)
        $("input[name='remind[]']:checked").each(function(key, value) {
           reminder_data.push({
               task_id: $(this).data("task-id"),
               task_type: $(this).data("task-type"),
               adistribution_id: $(this).data("adistribution-id"),
               aprogress_id: $(this).data("aprogress-id"),
               dassessment_id: $(this).data("dassessment-id")
           });
        });
        if (reminder_data.length > 0) {
            $.ajax({
                url: ENTRADA_URL + "/assessments/?section=api-tasks",
                data: {
                    "method": "send-reminders",
                    "reminder_data": reminder_data,
                    subject_id: $("#subject_id").val(),
                    subject_type: $("#subject_type").val()
                },
                type: "POST",
                success: function (data) {
                    var jsonResponse = safeParseJson(data, assessments_index.default_error_message);
                    if (jsonResponse.status === "success") {
                        $("#reminders-success").removeClass("hide");
                        // Prevent button press until next time they open the modal
                        $("#reminder-modal-confirm").prop("disabled", true);
                        $("#reminder-modal-confirm").addClass("disabled");
                    } else {
                        display_error(jsonResponse.data, "#reminders-error");
                        $("#reminders-error").removeClass("hide");
                    }
                }
            });
        } else {
            display_error([assessments_index.no_reminder_data], "#reminders-error");
            $("#reminders-error").removeClass("hide");
        }
    });

    /**
     * Show the remove task modal. Load it with the relevant task data from the link that was clicked.
     */
    $(document).on("click", ".assessment-task .assessment-task-link .remove", function (e) {
        e.preventDefault();
        var removal_link = $(this);
        var targets = $(removal_link).data("target-list");

        $("#removetask-task-data").val(targets);
        $("#removetask-task-data").data("task-type", $(removal_link).data("task-type"));
        $("#removetask-task-data").data("task-id", $(removal_link).data("task-id"));
        $("#removetask-task-data").data("adistribution-id", $(removal_link).data("adistribution-id"));
        $("#removetask-task-data").data("dassessment-id", $(removal_link).data("dassessment-id"));

        // Reset form values
        $("#removetask-other-reason").val("");
        $("#removetask-removing-reason").val("");
        $("#removetask-removing-reason-id").val("");
        $("input[name='removetask_reason']").removeProp("checked");

        $("#remove-msgs").addClass("hide");
        $("#remove-tasks-modal").modal("show");
    });

    /**
     * Clear errors when new selection is made for task removal reason.
     */
    $(document).on("click", "input[name='removetask_reason']:checked", function(e){
        $("#remove-msgs").addClass("hide");
    });

    /**
     * Submission of task removal request.
     */
    $(document).on("click", "#removetask-confirm", function (e) {
        e.preventDefault();
        var removing_reason_id = $("input[name='removetask_reason']:checked").val();
        var notes_required = $("input[name='removetask_reason']:checked").data("notes-required");
        var removing_reason = $("#removetask-other-reason").val();
        var task_data = $("#removetask-task-data").val();
        var task_type = $("#removetask-task-data").data("task-type");
        var task_id = $("#removetask-task-data").data("task-id");
        var adistribution_id = $("#removetask-task-data").data("adistribution-id");
        var dassessment_id = $("#removetask-task-data").data("dassessment-id");

        if (!removing_reason_id) {
            display_error([assessments_index.please_select_a_reason], "#remove-msgs");
            $("#remove-msgs").removeClass("hide");
            return;
        }
        if (notes_required) {
            if (!$("#removetask-other-reason").val().trim().length) {
                display_error([assessments_index.deletion_reason], "#remove-msgs");
                $("#remove-msgs").removeClass("hide");
                return;
            }
        }
        $.ajax({
            url: ENTRADA_URL + "/assessments/?section=api-tasks",
            data: {
                "method": "delete-tasks",
                "task_data": task_data,
                "task_type": task_type,
                "task_id": task_id,
                "adistribution_id": adistribution_id,
                "dassessment_id": dassessment_id,
                "reason_id": removing_reason_id,
                "reason_notes": removing_reason
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = safeParseJson(data, assessments_index.default_error_message);
                if (jsonResponse.status === "success") {
                    $("#remove-tasks-modal").addClass("hide"); // just hide the modal, but leave the overlay while we reload the page
                    location.reload();
                } else {
                    display_error(jsonResponse.data, "#remove-msgs");
                    $("#remove-msgs").removeClass("hide");
                }
            }
        });
    });

    // Filters learners based on name and the curriculum periods they are/were a part of.
    function filterLearners(cperiod_id) {
        $("#generate-pdf").removeClass("hide");
        var search_text = $("#learner-search").val().toLowerCase();
        var results = false;
        var no_results_container = $("#learner-detail-container .no-search-targets");

        no_results_container.remove();

        $(".learner-card").each(function () {
            var text = $(this).text().toLowerCase();
            text = text.substring(0, text.search(/\d/));

            // We must always filter by cperiod when provided, even if there is no search text.
            if (cperiod_id > 0) {
                var cperiod_ids = $(this).data("cperiod_ids").toString().split("-");
                if (cperiod_ids) {
                    if (cperiod_ids.indexOf(cperiod_id) >= 0) {
                        if (search_text.length == 0) {
                            $(this).removeClass("hide").addClass("visible");
                            results = true;
                        } else {
                            if (text.indexOf(search_text) !== -1) {
                                $(this).removeClass("hide").addClass("visible");
                                results = true;
                            } else {
                                $(this).addClass("hide").removeClass("visible");
                            }
                        }
                    } else {
                        $(this).addClass("hide").removeClass("visible");
                    }
                } else {
                    $(this).addClass("hide").removeClass("visible");
                }
            } else {
                // If there is no text or cperiod specified, show everything.
                if (search_text.length == 0) {
                    $(this).removeClass("hide").addClass("visible");
                    results = true;
                } else {
                    // No cperiod was specified, so display everything that contained the search text.
                    if (text.indexOf(search_text) !== -1) {
                        $(this).removeClass("hide").addClass("visible");
                        results = true;
                    } else {
                        $(this).addClass("hide").removeClass("visible");
                    }
                }
            }
        });

        // Check to see if we found anyone. If not, ensure the no results message is displayed.
        if (results) {
            no_results_container.remove();
        } else {
            if (no_results_container.length == 0) {
                var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-learner-blocks"}).html("No users found matching your search.");
                $("#learner-detail-container").append(no_search_targets_p);
                $("#generate-pdf").addClass("hide");
            }
        }
    }

    $("#assessment-reports-group-by-distribution").on("click", function (e) {
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

    $(".curriculum-period-selector").on("change", function () {
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

    /**
     * Load an assessment card.
     * ex.: mode: faculty, type: assessor-completed
     *
     * @param mode
     * @param type
     * @param data
     */
    function load_assessment_card(mode, type, data) {

        // Load the template, populating all the data we have.
        var loadedCard = $("<li/>").loadTemplate($("#assessment-card"), data);

        // Enable features based on card type and properties

        /**
         * Show badges if appropriate
         **/
        if (data.show_delegation_badge) {
            $(loadedCard).find("div.tpl-delegation-badge-text").removeClass("hide");
        }
        if (data.show_reviewer_badge) {
            $(loadedCard).find("div.tpl-reviewer-badge-text").removeClass("hide");
        }
        if (data.schedule_badge_text) {
            $(loadedCard).find("div.tpl-schedule-badge-text").removeClass("hide");
        }
        if (data.event_badge_text) {
            $(loadedCard).find("div.tpl-event-badge-text").removeClass("hide");
        }
        if (data.task_badge_text) {
            $(loadedCard).find("div.tpl-task-badge-text").removeClass("hide");
        }

        /**
         * Enable progress bubbles
         */
        if (parseInt(data.targets_pending)) {
            $(loadedCard).find("span.tpl-progress-pending").removeClass("hide");
        }
        if (parseInt(data.targets_in_progress)) {
            $(loadedCard).find("span.tpl-progress-inprogress").removeClass("hide");
        }
        if (parseInt(data.targets_completed)) {
            $(loadedCard).find("span.tpl-progress-complete").removeClass("hide");
        }

        /**
         * Show the task assessor
         */
        if (data.show_assessor_details) {
            $(loadedCard).find("div.tpl-task-assessor").removeClass("hide");
            if (data.assessor_role && data.assessor_group) {
                $(loadedCard).find("div.tpl-task-assessor-group-role").removeClass("hide");
            } else if (data.assessor_role) {
                $(loadedCard).find("div.tpl-task-assessor-role").removeClass("hide");
            } else if (data.assessor_group) {
                $(loadedCard).find("div.tpl-task-assessor-group").removeClass("hide");
            }
        }

        /**
         * The task has a single target to show
         */
        if (data.show_single_target_details) {
            $(loadedCard).find("div.tpl-task-target").removeClass("hide");
            if (data.single_target_role && data.single_target_group) {
                $(loadedCard).find("div.tpl-task-target-group-role").removeClass("hide");
            } else if (data.single_target_role) {
                $(loadedCard).find("div.tpl-task-target-role").removeClass("hide");
            } else if (data.single_target_group) {
                $(loadedCard).find("div.tpl-task-target-group").removeClass("hide");
            }
        }

        /**
         * Show the progress section
         */
        if (data.has_progress && data.show_progress_section) {
            $(loadedCard).find("div.tpl-progress-section").removeClass("hide");
        }

        /**
         * Show related dates
         */
        if (data.task_completion_date) {
            $(loadedCard).find("div.tpl-completion-date").removeClass("hide");
        }
        if (data.delivery_date) {
            if (data.future_delivery) {
                $(loadedCard).find("div.tpl-future-delivery-date").removeClass("hide");
            } else {
                $(loadedCard).find("div.tpl-delivery-date").removeClass("hide");
            }
        }
        if (data.event_timeframe_start || data.event_timeframe_end) {
            $(loadedCard).find("div.tpl-event-timeframe").removeClass("hide");
        } else if (data.rotation_start_date || data.rotation_end_date) {
            $(loadedCard).find("div.tpl-rotation-dates").removeClass("hide");
        } else if (data.task_start_date || data.task_end_date) {
            $(loadedCard).find("div.tpl-date-range").removeClass("hide");
        }

        /**
         * Show details
         */
        if (data.task_details) {
            $(loadedCard).find("div.tpl-task-details").removeClass("hide");
        }

        /**
         * Show applicable actions
         */
        if (data.show_download_pdf) {
            $(loadedCard).find("div.tpl-task-pdf-download").removeClass("hide");
        }
        if (data.show_send_reminders) {
            $(loadedCard).find("div.tpl-task-reminder").removeClass("hide");
        }
        if (data.show_view_button && data.show_remove_button) {
            $(loadedCard).find("a.tpl-task-view").removeClass("full-width");
            $(loadedCard).find("a.tpl-task-view").removeClass("hide");
            $(loadedCard).find("a.tpl-task-remove").removeClass("full-width");
            $(loadedCard).find("a.tpl-task-remove").removeClass("hide");
        } else {
            if (data.show_view_button) {
                $(loadedCard).find("a.tpl-task-view").removeClass("hide");
            }
            if (data.show_remove_button) {
                $(loadedCard).find("a.tpl-task-remove").removeClass("hide");
            }
        }

        /**
         * Append the loaded and configured template item to the appropriate task list
         */
        $(".assessment-tasks.task-list-" + type).append(loadedCard);
    }

    /**
     * Load More tasks via load more button.
     */
    $(".load-more-tasks-button").on("click", function (e) {
        e.preventDefault();
        var more_tasks_button = $(this);
        var offset = $(this).data("offset");
        var limit = $(this).data("limit");
        var fetch_mode = $(this).data("fetch-mode");
        var fetch_type = $(this).data("fetch-type");
        var subject_data = $(this).data("subject");
        var subject_array = safeParseJson(decodeURIComponent(subject_data), assessments_index.local_parse_error_message);
        var filter_data = $(this).data("filters");
        var filter_array = safeParseJson(decodeURIComponent(filter_data), assessments_index.local_parse_error_message);

        // Show the loading spinner
        var loading_spinner = more_tasks_button.find("img.load-more-tasks-spinner");
        loading_spinner.removeClass("hide");
        more_tasks_button.addClass("disabled");
        more_tasks_button.prop("disabled", true);

        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-tasks",
            data: {
                "method": "get-tasks",
                "start_date": filter_array.start_date,
                "end_date": filter_array.end_date,
                "limit": limit,
                "offset": offset,
                "distribution_methods": filter_array.distribution_method,
                "cperiod": filter_array.cperiod,
                "task_status": filter_array.task_status,
                "course": filter_array.course,
                "dassessment_id": filter_array.dassessment_id,
                "subject_id": subject_array.subject_id,
                "subject_type": subject_array.subject_type,
                "subject_scope": subject_array.subject_scope,
                "fetch_mode": fetch_mode,
                "fetch_type": fetch_type
            },
            type: "GET",
            success: function (data) {
                // Re-enable the button
                loading_spinner.addClass("hide");
                more_tasks_button.removeClass("disabled");
                more_tasks_button.removeProp("disabled");

                var jsonResponse = safeParseJson(data, assessments_index.default_error_message);
                if (jsonResponse.status === "success") {
                    for (var x = 0; x < jsonResponse.tasks.length; x++) {
                        load_assessment_card(fetch_mode, fetch_type, jsonResponse.tasks[x]);
                    }
                    matchHeights(); // Slow, but required
                    $(more_tasks_button).data("offset", offset + limit);
                    if ($(more_tasks_button).data("offset") >= jsonResponse.count.task_count) {
                        $(more_tasks_button).prop("disabled", true);
                        $(more_tasks_button).addClass("hide");
                    }
                    // Scroll to the bottom after click
                    //$("html, body").animate({scrollTop: $(document).height()}, 0);
                } else {
                    console.log("Error loading more tasks (parse error)");
                }
            }
        });
    });

    /**
     * Select all PDF checkboxes on assessment cards.
     */
    $(".select-all-to-download").on("click", function (e) {
        var button = $(this);
        var parent_id = button.parent().parent().attr("id");
        var checkbox_selector = "#" + parent_id + " input[type='checkbox'].generate-pdf";

        if (button.data("select-all-enabled")) {
            button.data("select-all-enabled", false);
            button.html(assessments_index.btn_select_all);
            $(checkbox_selector).removeProp("checked");
        } else {
            $(checkbox_selector).prop("checked", true);
            button.html(assessments_index.btn_deselect_all);
            button.data("select-all-enabled", true);
        }
    });

    $("#assessment-report-create-pdf").on("click", function (e) {
        e.preventDefault();
        if ($(this).data("pdf-unavailable") == 1) {
            display_error([assessment_reports.pdf_unavailable], "#pdf-button-error");
        } else {
            window.location = $(this).attr("href");
        }
    });

    // PDF for downloading enrolment
    $("#generate-pdf").on("click", function (e) {
        var built_href = $(this).attr("href")
            + "&selected-cperiod="
            + $("#learner-curriculum-period-select").val()
            + "&search-term="
            + ($("#learner-search").val().trim().length > 0
                    ? $("#learner-search").val().trim().toLowerCase()
                    : ""
            );
        $(this).attr("href", built_href);
    });

    if ($("#external-faculty-detail-container li").length == 0) {
        $("#change-faculty-view").addClass("hide");
    }

    $(".update-external-assessor-email").on("click", function () {
        $("#edit-external-error").empty();
        $("#edit-external-success").addClass("hide");

        var external_email = $(this).closest("li").find(".external-email");
        $("#edit-external-email").val(external_email.html());
        $("#save-external-email-confirm").attr("data-external-id", $(this).data("proxy-id"));
        $("#edit-external-modal").modal("show");
    });

    $("#edit-external-email").keydown(function (e) {
        if (e.keyCode == 13) {
            updateExternalEmail();
        }
    });

    $("#save-external-email-confirm").on("click", function () {
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

    $(".change-external-assessor-visibility").on("click", function () {
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

    $("#change-faculty-card-visibility").on("click", function () {
        if ($(this).attr("data-show-faculty") == "hide") {
            $(this).val("Hide External Faculty");
            $(this).attr("data-show-faculty", "show");
            $(".faculty-card").each(function () {
                $(this).removeClass("hide");
            });
        } else {
            $(this).val("Show Hidden External Faculty");
            $(this).attr("data-show-faculty", "hide");
            $(".faculty-card.hidden").each(function () {
                $(this).addClass("hide");
            });
        }
        facultySearch($("#faculty-search").val());
    });

    $("#report-start-date, #report-end-date").on("change", function (e) {
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
                    window.location.href = ENTRADA_URL + "/assessments/reports?proxy_id=" + proxy_id + role + cperiod_list + start_date + end_date;
                }
            }
        });
    }

    function matchHeights() {
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

        $("#upcoming_forms_for_learner .assessment-task-wrapper").matchHeight({
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
    }

    $(".learner-dashboard").on("click", function () {
        var proxy_id = $(this).attr("data-id");
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-learner-preference", "proxy_id": proxy_id}
        });
    });

    function toggleFilterControls() {
        $("#form_index_tabs .task-tab.active").each(function(key, value) {
            if ($(value).find("a[href='#faculty']").length
                || $(value).find("a[href='#learners']").length
            ) {
                // If the faculty or learners tab is currently active, then hide the filter controls
                $("#assessment_tasks_filter_container").addClass("hide");
            } else {
                // Otherwise, show them
                $("#assessment_tasks_filter_container").removeClass("hide");

            }
        });
    }
    var lastTab = readCookie(proxy_id + 'assessment_index_last_tab');
    if (lastTab) {
        $('#form_index_tabs a[href=' + lastTab + ']').tab('show');
    } else {
        $('#form_index_tabs a[data-toggle="tab"]:first').tab('show');
    }
    toggleFilterControls();

    matchHeights();
});