jQuery(document).ready(function ($) {

    $('#delete-tasks-modal').on('hide.bs.modal', function (e) {
        if ($("#delete-tasks-close-button").data("successfully-deleted") == true) {
            location.reload();
        }
    });

    if (typeof active_tab != "undefined") {

        $(".target-status-btn").removeClass("active");
        $("#targets-"+active_tab+"-btn").addClass("active");

        $(".targets-container").addClass("hide");
        $("#targets-"+active_tab+"-container").removeClass("hide");

    }

    $('#delete-tasks-modal').on('show.bs.modal', function (e) {
        $('#delete-tasks-modal').removeClass("hide");
        $("#tasks-selected").addClass("hide");
        $("#no-tasks-selected").addClass("hide");
        $("#tasks-success").addClass("hide");
        $("#tasks-error").addClass("hide");
        var target_details = new Array();

        var tasks_to_delete = $(".target-block input.delete:checked").map(function () {
            // Construct human readable date for display from the delivery timestamp.
            var date = new Date($(this).val() * 1000);
            var formatted_date = date.getFullYear() + "-" + (("0" + (date.getMonth() + 1)).slice(-2)) + "-" + ("0" + date.getDate()).slice(-2);
            var tmp_array = new Array($(this).data("assessor-name"), $(this).data("target-name"), formatted_date);
            target_details.push(tmp_array);
            return this.value;
        }).get();

        var tbody = document.createElement("tbody");
        // Construct a table containing the details for each task that will be deleted.
        $.each(target_details, function (i, v) {
            var tr = document.createElement("tr");
            $.each(v, function (i2, v2) {
                var td = document.createElement("td");
                td.appendChild(document.createTextNode(v2));
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        $("#delete-tasks-details-table tbody").html(""); // clear old table contents
        document.getElementById("delete-tasks-details-table").appendChild(tbody);

        if (tasks_to_delete.length > 0) {
            $("#tasks-selected").removeClass("hide");
            $("#target-details").removeClass("hide");
            $("#delete-tasks-modal-confirm").removeClass("hide");
            $("#delete-tasks-details-section").removeClass("hide");
            $("#delete-tasks-reason-section").removeClass("hide");
            $("#tasks-selected div.alert ul li span").html(tasks_to_delete.length);
            $("#tasks-success div.alert ul li span").html(tasks_to_delete.length);
            $("#tasks-error div.alert ul li span").html(tasks_to_delete.length);
        } else {
            $("#delete-tasks-modal-confirm").addClass("hide");
            $("#no-tasks-selected").removeClass("hide");
            $("#delete-tasks-details-section").addClass("hide");
            $("#delete-tasks-reason-section").addClass("hide");
        }
    });

    $("#delete-tasks-modal-confirm").on("click", function (e) {
        e.preventDefault();
        var url = $("#delete-tasks-modal-form").attr("action");
        var reason_id = $("#delete-tasks-reason").val();
        var reason_notes = $("#delete-tasks-other-reason").val();
        $("#delete-tasks-modal-confirm").attr("disabled", "disabled");
        $("#delete-tasks-close-button").attr("disabled", "disabled");

        var atarget_task_data = [];
        var non_atarget_task_data = [];

        // Existing tasks can be deleted by their atarget_id, tasks that do not exist will need to be preemptively created and deleted.
        $("input.delete").each(function (i, v) {
            if ($(v).is(":checked")) {
                if ($(v).data("atarget-id")) {
                    atarget_task_data.push($(v).data("atarget-id"));
                } else {
                    non_atarget_task_data.push({
                        assessor_type: $(v).data("assessor-type"),
                        assessor_value: $(v).data("assessor-value"),
                        target_type: $(v).data("target-type"),
                        target_id: $(v).data("target-id"),
                        delivery_date: $(v).data("delivery-date"),
                        form_id: $(v).data("form-id"),
                        organisation_id: $(v).data("organisation-id"),
                        feedback_required: $(v).data("feedback-required"),
                        min_submittable: $(v).data("min-submittable"),
                        max_submittable: $(v).data("max-submittable"),
                        start_date: $(v).data("start-date"),
                        end_date: $(v).data("end-date"),
                        rotation_start_date: $(v).data("rotation-start-date"),
                        rotation_end_date: $(v).data("rotation-end-date"),
                        associated_record_type: $(v).data("associated-record-type"),
                        associated_record_id: $(v).data("associated-record-id"),
                        additional_task: $(v).data("additional-task"),
                        task_type: $(v).data("task-type")
                    });

                }

            }
        });

        // Unfortunately the two types of tasks require different API calls, this should be merged into one method that can handle all data.
        $.ajax({
            url: url,
            data: {
                "method": "delete-tasks-by-atarget",
                "task_data_array": atarget_task_data,
                "reason_id": reason_id,
                "reason_notes": reason_notes
            },
            type: "POST",
            success: function (results) {

                if (non_atarget_task_data.length > 0) {
                    $.ajax({
                        url: url,
                        data: {
                            "method": "delete-tasks-by-future",
                            "task_data_array": non_atarget_task_data,
                            "reason_id": reason_id,
                            "reason_notes": reason_notes,
                            "location": "progress"
                        },
                        type: "POST",
                        success: function (data) {

                            $("#tasks-selected").addClass("hide");
                            $("#no-tasks-selected").addClass("hide");
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse.status == "success") {
                                $("#tasks-success").removeClass("hide");
                                $("#tasks-selected").addClass("hide");
                                $("#tasks-error").addClass("hide");
                                $("#delete-tasks-modal-confirm").addClass("hide");
                                $("#delete-tasks-close-button").removeAttr("disabled");
                                $("#delete-tasks-details-section").addClass("hide");
                                $("#delete-tasks-reason-section").addClass("hide");
                                $("#delete-tasks-close-button").data("successfully-deleted", true);
                            } else {
                                $("#delete-tasks-modal-confirm").removeAttr("disabled");
                                $("#delete-tasks-close-button").removeAttr("disabled");
                                $(jsonResponse.data).each(function (i, v) {
                                    display_error(v, "#tasks-error");
                                });
                                $("#tasks-error").removeClass("hide");
                            }
                        }
                    });
                } else {
                    $("#tasks-selected").addClass("hide");
                    $("#no-tasks-selected").addClass("hide");
                    var jsonResponse = JSON.parse(results);
                    if (jsonResponse.status == "success") {
                        $("#tasks-success").removeClass("hide");
                        $("#tasks-selected").addClass("hide");
                        $("#tasks-error").addClass("hide");
                        $("#delete-tasks-modal-confirm").addClass("hide");
                        $("#delete-tasks-close-button").removeAttr("disabled");
                        $("#delete-tasks-details-section").addClass("hide");
                        $("#delete-tasks-reason-section").addClass("hide");
                        $("#delete-tasks-close-button").data("successfully-deleted", true);
                    } else {
                        $("#delete-tasks-modal-confirm").removeAttr("disabled");
                        $("#delete-tasks-close-button").removeAttr("disabled");
                        $(jsonResponse.data).each(function (i, v) {
                            display_error(v, "#tasks-error");
                        });
                        $("#tasks-error").removeClass("hide");
                    }
                }
            }
        });
    });

    $('#reminder-modal').on('show.bs.modal', function (e) {
        $('#reminder-modal').removeClass("hide");
        $("#reminders-selected").addClass("hide");
        $("#no-reminders-selected").addClass("hide");
        $("#reminders-success").addClass("hide");
        $("#reminders-error").addClass("hide");
        $("#reminder-details-section-delegators").addClass("hide");
        $("#reminder-details-section").addClass("hide");
        var assessor_names = [];

        var tasks_to_remind = $(".target-block input.remind:checked").map(function () {
            // Ensure no duplicate assessor names are added to the array for display
            if ($.inArray($(this).data("assessor-name"), assessor_names) == -1) {
                assessor_names.push($(this).data("assessor-name"));
            }
            return this.value;
        }).get();

        $("#reminder-details-list").html("");
        if (assessor_names.length > 0) {
            $.each(assessor_names, function (i, v) {
                $("#reminder-details-list").append("<li>" + v + "</li>");
            });
        }

        var delegators_to_remind = false;
        $("input.delegator-notify-checkbox:checked").each(function () {
           delegators_to_remind = true;
        });
        $("#reminder-details-section-delegator-name").html($("#hidden-delegator-name").val());

        if (delegators_to_remind == false && tasks_to_remind.length == 0) {
            $("#reminder-modal-confirm").addClass("hide");
            $("#no-reminders-selected").removeClass("hide");
        } else {

            if (tasks_to_remind.length > 0) {
                $("#reminders-selected").removeClass("hide");
                $("#reminder-details").removeClass("hide");
                $("#reminder-modal-confirm").removeClass("hide");
                $("#reminder-details-section").removeClass("hide");
                $("#reminder-reason-section").removeClass("hide");
                $("#reminders-selected div.alert ul li span").html(tasks_to_remind.length);
                $("#reminders-success div.alert ul li span").html(tasks_to_remind.length);
                $("#reminders-error div.alert ul li span").html(tasks_to_remind.length);
            }

            if (delegators_to_remind) {
                $("#reminder-modal-confirm").removeClass("hide");
                $("#reminder-details").removeClass("hide");
                $("#reminder-details-section-delegators").removeClass("hide");
            }
        }
    });

    $("#reminder-modal-confirm").on("click", function (e) {
        e.preventDefault();

        var url = $("#reminder-modal-form").attr("action");

        var assessor_array = [];
        var dassessment_id_array = [];
        $("input.remind:checked").each(function (i, v) {
            assessor_array[i] = $(v).val();
            dassessment_id_array[i] = $(v).data("assessment-id");
        });

        var delegator_tasks_array = [];
        $("input.delegator-notify-checkbox:checked").each(function () {
            delegator_tasks_array.push({
                "adistribution_id": $(this).data("distribution-id"),
                "addelegation_id": $(this).data("delegation-task-id"),
            });
        });

        $.ajax({
            url: url,
            data: {
                "method": "send-reminders",
                "dassessment_id_array": dassessment_id_array,
                "assessor_array": assessor_array,
                "delegator_tasks_array": delegator_tasks_array
            },
            type: "POST",
            success: function (data) {
                $("#reminders-selected").addClass("hide");
                $("#no-reminders-selected").addClass("hide");
                var jsonResponse = safeParseJson(data, ["Unknown Server Error"]);
                if (jsonResponse.status == "success") {
                    $("input[type=checkbox].remind").each(function () {
                        $(this).attr("checked", false);
                    });
                    $("input[type=checkbox].delegator-notify-checkbox").each(function () {
                        $(this).attr("checked", false);
                    });
                    $("#reminders-success").removeClass("hide");
                    $("#reminder-details-section-delegators").addClass("hide");
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
    });

    // Display a notice that the task will not be delivered until midnight if the delivery date is today or in the past.
    checkDate();

    $("#add-task-delivery-date").on("change", function (e) {
        checkDate();
    });

    $('#add-task-modal').on('hide.bs.modal', function (e) {
        $('#add-task-modal').addClass("hide");
        location.reload();
    });

    $("#add-task-modal-confirm").on("click", function (e) {
        e.preventDefault();

        $(this).attr("disabled", true);
        var url = $("#distribution-data-form").attr("action");
        var delivery_date = $("#add-task-delivery-date").val();
        var assessor_array = [];
        var count = 0;
        $("[id^=selected-internal-assessor-]").each(function (i, v) {
            var pieces = $(v).val().split("_");
            assessor_array[count] = {
                assessor_type: pieces[0],
                assessor_value: pieces[1]
            };
            count++;
        });

        var target_array = [];
        count = 0;
        $("[id^=selected-internal-target-]").each(function (i, v) {
            target_array[count] = $(v).val();
            count++;
        });

        $.ajax({
            url: url,
            data: {
                "method": "add-task",
                "assessor_array": assessor_array,
                "target_array": target_array,
                "delivery_date": delivery_date
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = safeParseJson(data, ["Unknown Server Error"]);
                if (jsonResponse.status == "success") {
                    $("#add-task-success").removeClass("hide");
                    $("#add-task-error").addClass("hide");
                    $("#add-task-modal-confirm").addClass("hide");
                    $("#add-task-controls").addClass("hide");
                } else {
                    $(jsonResponse.data).each(function (i, v) {
                        display_error(v, "#add-task-error");
                    });
                    $("#add-task-error").removeClass("hide");
                }
                $("#add-task-modal-confirm").attr("disabled", false);
            }
        });
    });

    function checkDate() {
        var delivery_date = new Date($("#add-task-delivery-date").val());
        var current_date = new Date();
        $("#add-task-delivery-notice").addClass("hide");
        if (delivery_date <= current_date) {
            $("#add-task-delivery-notice").removeClass("hide");
        }
    }

    /** Delegation progress page specific functionality **/

    function collapseAllAssessor(type) {
        $(".all-assessor-block-container").each(function(){
            if ($(this).data("progress-type") == type) {
                slideToggleAssessorsDrawer(type, $(this).data("toggle-ordinal") , "closed");
            }
        });
    }

    function expandAllAssessor(type) {
        $(".all-assessor-block-container").each(function(){
            if ($(this).data("progress-type") == type) {
                slideToggleAssessorsDrawer(type, $(this).data("toggle-ordinal") , "open");
            }
        });
    }

    function slideToggleAssessorsDrawer(type, toggle_ordinal, force_state) {
        var assessors_container_id = ".all-assessor-block-container-" + type + "-" + toggle_ordinal;
        var toggle_bar_id = ".assessor-toggle-arrow-" + type + "-" + toggle_ordinal;
        var slide_speed = 350;

        if (force_state == "open") {
            $(assessors_container_id).slideDown(slide_speed, function(){
                $(toggle_bar_id).html($("#assessor-toggle-arrow-default-up").html());
            });
        } else if (force_state == "closed") {
            $(assessors_container_id).slideUp(slide_speed, function() {
                $(toggle_bar_id).html($("#assessor-toggle-arrow-default-down").html());
            });
        } else {
            $(assessors_container_id).slideToggle(slide_speed, function () {
                if ($(assessors_container_id).css("display") == "none") {
                    $(toggle_bar_id).html($("#assessor-toggle-arrow-default-down").html());
                } else {
                    $(toggle_bar_id).html($("#assessor-toggle-arrow-default-up").html());
                }
            });
        }
    }

    function toggleEmptyDelegations(type, visibility) {
        var slide_speed = 350;
        $(".delegation-progress-table-"+type).each(function(){
            if ($(this).data("has-assignments") == 0) {
                if (visibility == "hide") {
                    $(this).slideUp(slide_speed);
                } else {
                    $(this).slideDown(slide_speed);
                }
            }
        });
    }

    $(".all-assessor-toggle-bar").on("click", function(e){
        e.preventDefault();
        slideToggleAssessorsDrawer($(this).data("progress-type"), $(this).data("toggle-ordinal"), null);
    });

    $(".delegation-progress-collapse-all-all-assessors").on("click", function(){
        if ($(this).attr("checked") == "checked") {
            collapseAllAssessor($(this).data("progress-type"));
        } else {
            expandAllAssessor($(this).data("progress-type"));
        }
    });

    $(".delegation-progress-hide-empty").on("click", function(){
        var type = $(this).data("progress-type");
        if ($(this).attr("checked") == "checked") {
            toggleEmptyDelegations(type, "hide");
        } else {
            toggleEmptyDelegations(type, "show");
        }
    });

    function get_delegated_tasks_to_delete() {
        var delegations_to_delete = [];
        $(".delete-delegation-assessment:checked").each(function() {
            var assignment_detail = {
                "addassignment_id"  : $(this).data("assignment-id"),
                "addelegation_id"   : $(this).data("delegation-id"),
                "target_type"       : $(this).data("target-type"),
                "target_name"       : $(this).data("target-name"),
                "target_id"         : $(this).data("target-id"),
                "assessor_type"     : $(this).data("assessor-type"),
                "assessor_name"     : $(this).data("assessor-name"),
                "assessor_value"    : $(this).data("assessor-value"),
                "dassessment_id"    : $(this).data("assessment-id")
            };
            delegations_to_delete.push(assignment_detail);
        });
        return delegations_to_delete;
    }

      $('#delete-delegation-tasks-modal').on('show.bs.modal', function (e) {
        $('#delete-tasks-modal').removeClass("hide");
        $("#no-tasks-selected").addClass("hide");
        $("#tasks-selected").addClass("hide");
        $("#tasks-success").addClass("hide");
        $("#tasks-error").addClass("hide");

        var delegations_to_delete = get_delegated_tasks_to_delete();
        if (delegations_to_delete.length > 0) {
            var tbody = document.createElement("tbody");
            // Construct a table containing the details for each task that will be deleted.
            $.each(delegations_to_delete, function (i, v) {
                var tr = document.createElement("tr");
                $(tr).html("<td>" + v.target_name + "</td><td>" + v.assessor_name + "</td>");
                tbody.appendChild(tr);
            });
            $("#delete-tasks-details-table tbody").html(""); // clear old table contents
            document.getElementById("delete-tasks-details-table").appendChild(tbody);

            $("#tasks-selected").removeClass("hide");
            $("#target-details").removeClass("hide");
            $("#delete-delegation-tasks-modal-confirm").removeClass("hide");
            $("#delete-tasks-details-section").removeClass("hide");
            $("#delete-tasks-reason-section").removeClass("hide");
            $("#tasks-selected div.alert ul li span").html(delegations_to_delete.length);
            $("#tasks-success div.alert ul li span").html(delegations_to_delete.length);
            $("#tasks-error div.alert ul li span").html(delegations_to_delete.length);
        } else {
            $("#delete-delegation-tasks-modal-confirm").addClass("hide");
            $("#no-tasks-selected").removeClass("hide");
            $("#delete-tasks-details-section").addClass("hide");
            $("#delete-tasks-reason-section").addClass("hide");
        }
    });

    $("#delete-delegation-tasks-modal-confirm").on("click", function (e) {
        e.preventDefault();
        var url = $("#delete-tasks-modal-form").attr("action");
        var reason_id = $("#delete-tasks-reason").val();
        var reason_notes = $("#delete-tasks-other-reason").val();
        var task_data = get_delegated_tasks_to_delete();
        $.ajax({
            url: url,
            data: {
                "method": "delete-delegated-tasks",
                "task_data_array": task_data,
                "reason_id": reason_id,
                "reason_notes": reason_notes
            },
            type: "POST",
            success: function (data) {
                $("#tasks-selected").addClass("hide");
                $("#no-tasks-selected").addClass("hide");
                var jsonResponse = safeParseJson(data, ["Unknown Server Error"]);
                if (jsonResponse.status == "success") {
                    $("#tasks-success").removeClass("hide");
                    $("#tasks-selected").addClass("hide");
                    $("#tasks-error").addClass("hide");
                    $("#delete-delegation-tasks-modal-confirm").addClass("hide");
                    $("#delete-tasks-details-section").addClass("hide");
                    $("#delete-tasks-reason-section").addClass("hide");

                    // reload the page once they dismiss the modal.
                    $("#delete-delegation-tasks-modal").on("hidden.bs.modal", function() {
                        location.reload();
                    });
                } else {
                    $(jsonResponse.data).each(function (i, v) {
                        display_error(v, "#tasks-error");
                    });
                    $("#tasks-error").removeClass("hide");
                }
            }
        });
    });

    $("th .icon-download-alt").on("click", function (e) {
        $.each($("th .icon-download-alt"), function (i, v) {
            $(v).hasClass("select-all") ? $(v).removeClass("select-all") : $(v).addClass("select-all");
        });

        $("input[name=\"generate-pdf[]\"]").each(function () {
            if (!$(this).closest(".targets-container").hasClass("hide") && !$(this).closest("tr").hasClass("hide")) {
                $(this).prop("checked", $(".icon-download-alt").hasClass("select-all"));
            }
        });
    });

    $("th .icon-trash").on("click", function (e) {
        $.each($("th .icon-trash"), function (i, v) {
            $(v).hasClass("select-all") ? $(v).removeClass("select-all") : $(v).addClass("select-all");
        });

        $("input[name=\"delete[]\"]").each(function () {
            if (!$(this).closest(".targets-container").hasClass("hide") && !$(this).closest("tr").hasClass("hide")) {
                $(this).prop("checked", $(".icon-trash").hasClass("select-all"));
            }
        });
    });

    $("th .icon-bell").on("click", function (e) {
        $.each($("th .icon-bell"), function (i, v) {
            $(v).hasClass("select-all") ? $(v).removeClass("select-all") : $(v).addClass("select-all");
        });

        $("input[name=\"remind[]\"]").each(function () {
            if (!$(this).closest(".targets-container").hasClass("hide") && !$(this).closest("tr").hasClass("hide")) {
                $(this).prop("checked", $(".icon-bell").hasClass("select-all"));
            }
        });
    });

    $(".target-status-btn").on("click", function (e) {
        $(".icon-trash").removeClass("select-all");
        $(".icon-bell").removeClass("select-all");
        $(".icon-download-alt").removeClass("select-all");

        $("input[name=\"delete[]\"]").each(function () {
            $(this).prop("checked", false);
        });

        $("input[name=\"generate-pdf[]\"]").each(function () {
            $(this).prop("checked", false);
        });

        $("input[name=\"remind[]\"]").each(function () {
            $(this).prop("checked", false);
        });
    });

    $("#target-progress-search-input").on("keyup", function () {
        var search_text = $(this).val().toLowerCase();

        if (search_text.length === 0) {
            $("#targets-table-container").removeClass("hide");
            $(".target-block").removeClass("hide");
            $("#targets-table-container-no-search").addClass("hide");
        } else {
            $(".target-block").each(function () {
                $(this).children().each(function(){
                    if ($(this).hasClass("target-block-target-details")) {
                        var oparent = $(this).parent();
                        var text = $(this).text().toLowerCase();
                        if (text.indexOf(search_text) >= 0) {
                            oparent.removeClass("hide").addClass("visible");
                        } else {
                            oparent.addClass("hide").removeClass("visible");
                        }
                    }
                });
            });
        }
    });

    $("#event-search-input").on("keyup", function () {
        var search_text = $(this).val().toLowerCase();

        if (search_text.length === 0) {
            //$("#targets-table-container").removeClass("hide");
            $(".event-block").removeClass("hide");
            //$("#targets-table-container-no-search").addClass("hide");
        } else {
            $(".event-block").each(function () {
                $(this).children().each(function(){
                    if ($(this).hasClass("event-block-event-details")) {
                        var oparent = $(this).parent();
                        var text = $(this).text().toLowerCase();
                        if (text.indexOf(search_text) >= 0) {
                            oparent.removeClass("hide").addClass("visible");
                        } else {
                            oparent.addClass("hide").removeClass("visible");
                        }
                    }
                });
            });
        }
    });
});
