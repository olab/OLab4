jQuery(function($) {
    sidebarBegone();

    $("#leave-table").DataTable({
        "bPaginate": false,
        "bInfo": false,
        'oLanguage': {
            'sEmptyTable': javascript_translations.no_leave_in_system,
            'sZeroRecords': javascript_translations.no_leave_records_found
        }
    });

    $("#current-leave-table").DataTable({
        "bPaginate": false,
        "bInfo": false,
        "oLanguage": {
            "sZeroRecords": javascript_translations.no_leave_records_found
        },
        "aoColumnDefs": [
            {"sType": "numeric-html", "aTargets": [2]}
        ],
        "aaSorting": [[ 1, "asc" ]]
    });

    jQuery.fn.dataTableExt.oSort['numeric-html-asc']  = function(a,b) {
        a = parseInt($(a).text());
        b = parseInt($(b).text());
        return ((a < b) ? -1 : ((a > b) ?  1 : 0));
    };

    jQuery.fn.dataTableExt.oSort['numeric-html-desc']  = function(a,b) {
        a = parseInt($(a).text());
        b = parseInt($(b).text());
        return ((a < b) ? 1 : ((a > b) ?  -1 : 0));
    };

    $("#user-search").audienceSelector({
        "filter": "#contact-type",
        "target": "#slot-occupants",
        "content_type": "individual",
        "content_target": "slot-id",
        "api_url": ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
        "delete_attr": "data-proxy-id",
        "add_audience": false,
        "min_chars": 3,
        "api_params": {
            "schedule_slot_id": $("#slot-id")
        },
        "get_method" : "get-learners",
        "handle_result_click" : function(v) {

            var name_container  = $(document.createElement("div")).addClass("input-append name-container");
            var proxy_id_input  = $(document.createElement("input")).attr({"value" : v.id, "type" : "hidden", "name" : "proxy_id"})
            var name_input      = $(document.createElement("input")).attr({"value" : v.fullname, "type" : "text", "readonly" : "readonly"});
            var addon_container = $(document.createElement("span")).attr({"style" : "cursor:pointer;"}).addClass("add-on").on("click", function(e) {
                $(this).closest(".input-append").remove();
                $("#user-search").show();
            }).append("<i class=\"icon-remove-sign\"></i>");
            name_container.append(proxy_id_input, name_input, addon_container);
            $("#user-search").parent().append(name_container);
            $("#user-search").hide();
        }
    });

    $("input.datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $("input.timepicker").timepicker({});

    $(".add-on").on("click", function() {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    $("#add-leave-btn").on("click", function(e) {
        e.preventDefault();
        var btn = $(this);

        var date_reg_ex = /^\d{4}-\d{2}-\d{2}$/;
        var days_used = $("#days-used").val();
        var start_date = $("#start-date").val();
        var end_date = $("#end-date").val();

        if(start_date.match(date_reg_ex) != null && end_date.match(date_reg_ex) == null && $.isNumeric(days_used) && days_used > 0) {
            var update_end_date = new Date(start_date);
            update_end_date.setDate(update_end_date.getDate() + parseInt(days_used));
            $("#end-date").val($.datepicker.formatDate("yy-mm-dd", update_end_date));
        }

        $.ajax({
            url: ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
            type: "POST",
            data: "method=add-leave&" + $("#leave-form").serialize(),
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    if (btn.hasClass("index")) {
                        window.location = ENTRADA_URL + "/admin/" + MODULE + "/" + SUBMODULE + "?section=user&proxy_id=" + $("#leave-form input[name=proxy_id]").val();
                    } else {
                        $("#new-leave").modal("hide");
                        location.reload();
                    }
                } else {
                    $("#display-error-box-modal").remove();
                    display_error(jsonResponse.data, "#msgs");
                    $("#msgs").show();
                }
            }
        });
    });

    function clearModal() {
        $("#msgs").empty().hide();
        $(".name-container").remove();
        $("#user-search").show();
        $("#start-date").val("");
        $("#end-date").val("");
        $("#start-time").val("");
        $("#end-time").val("");
        $("#days-used").val("");
        $("#weekdays-used").val("");
        $("#weekend-days-used").val("");
        $("#comments").val("");
        $("select[name=leave_type]").children().removeAttr("selected");
        $("#add-leave-btn").html("Add");
        $("#leave-id").remove();
    }

    $(".new-leave-form").on("click" , "#add-new-leave", function(e) {
        $("#start-date").val($.datepicker.formatDate("yy-mm-dd", new Date()));
    });

    $("#new-leave").on("hidden", function(e) {
        clearModal();
    });

    $(".table").on("click", ".edit-leave", function(e) {
        e.preventDefault();

        var link = $(this);
        $.ajax({
            url: ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
            type: "GET",
            data: "method=get-leave-data&leave_id=" + link.data("leave-id"),
            success: function (data) {
                var jsonData = JSON.parse(data);
                if (jsonData.status == "success") {
                    var leave_id_input = $(document.createElement("input")).attr({"type" : "hidden", "value" : link.data("leave-id"), "name" : "leave_id", "id" : "leave-id"});
                    $("#leave-form").append(leave_id_input);
                    $("#add-leave-btn").html("Save");
                    $("#start-date").val(jsonData.data.start_date);
                    $("#start-time").val(jsonData.data.start_time);
                    $("#end-date").val(jsonData.data.end_date);
                    $("#end-time").val(jsonData.data.end_time);
                    $("#days-used").val(jsonData.data.days_used);
                    $("#weekdays-used").val(jsonData.data.weekdays_used);
                    $("#weekend-days-used").val(jsonData.data.weekend_days_used);
                    $("#comments").val(jsonData.data.comments);
                    $("select[name=leave_type] option[value="+jsonData.data.leave_type_id+"]").attr("selected", "selected");
                }
                $("#new-leave").modal("show");
            }
        });
    });

    $("#start-date").on("change", function (e) {
        var date_reg_ex = /^\d{4}-\d{2}-\d{2}$/;
        var start_date = $("#start-date").val();
        var end_date = $("#end-date").val();

        if (start_date.match(date_reg_ex) != null) {
            if (!$("#end-date").val()) {
                var new_end_date = new Date($(this).val());
                new_end_date.setDate(new_end_date.getDate() + 2);
                $("#end-date").val($.datepicker.formatDate("yy-mm-dd", new_end_date));
                updateDaysUsed();
            } else if (end_date.match(date_reg_ex) != null) {
                updateDaysUsed();
            }
        }
    });

    $("#end-date").on("change", function (e) {
        var date_reg_ex = /^\d{4}-\d{2}-\d{2}$/;
        var start_date = $("#start-date").val();
        var end_date = $("#end-date").val();

        if (start_date.match(date_reg_ex) != null && end_date.match(date_reg_ex) != null) {
            updateDaysUsed();
        }
    });

    function updateDaysUsed() {
        var start_string = $("#start-date").val() + "T" + ($("#start-time").val().length > 0 ? $("#start-time").val() : "00:00") + ":00";
        var end_string = $("#end-date").val() + "T" + ($("#end-time").val().length > 0 ? $("#end-time").val() : "00:00") + ":00";
        var num_days = 0;

        if (end_string >= start_string) {
            // Unfortunately attempting to calculate number of days on a timestamp differential isn't completely accurate as there aren't always 24 hours in a day.
            var one_day = 1000 * 60 * 60 * 24;
            var days_used = (Date.parse(end_string) - Date.parse(start_string));
            num_days = Math.round(days_used / one_day) + 1;
        }

        $("#days-used").val(num_days);
    }

    $("#delete_leave_btn").on("click", function(e) {
        var is_selected = false;
        $(".leave-tracking-table td input[name*=delete]").each(function(k,v) {
            if ($(v).attr("checked") == "checked") {
                is_selected = true;
            }
        });

        if (!is_selected) {
            e.preventDefault();
            var error = [leave_error.Display];
            display_error(error, "#no-selection-error");
            $("#no-selection-error").show();
        }
    });

    $("#leave-search").on("keyup", function () {
        $(".no-results").remove();
        $("#leave-table").dataTable().fnFilter($(this).val());

        if ($(".leave-tracking-table tbody tr:not(.hide)").length == 0) {
            display_no_results();
        }
    });

    $("#current-leave-search").on("keyup", function () {
        $(".no-results").remove();
        $("#current-leave-table").dataTable().fnFilter($(this).val());

        if ($(".leave-tracking-table tbody tr:not(.hide)").length == 0) {
            display_no_results();
        }
    });

    $("#learner-curriculum-period-select").on("change", function () {
        $.ajax({
            url: ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
            data: {
                method: "set-curriculum-period",
                cperiod_id: $("#learner-curriculum-period-select").val()
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    console.log("Successfully updated curriculum period preference.");
                } else {
                    console.log("Unable to update curriculum period preference.");
                }
            }
        });

        if ($(this).val() == 0) {
            $(".no-results").remove();
            $(".leave-tracking-table tbody tr").each(function (key, value) {
                var tr_total_days = $(value).attr("data-total-days");
                if (typeof tr_total_days !== "undefined") {
                    tr_total_days = tr_total_days.split(",");
                    if (tr_total_days[0] == "") {
                        tr_total_days[0] = javascript_translations.please_update;
                    }
                    $(value).find(".total-days-count").html(tr_total_days[0]);
                }
                $(value).removeClass("hide");
            });
        } else {
            var start_date = $(this).find(":selected").attr("data-start-date");
            var end_date = $(this).find(":selected").attr("data-end-date");
            var results = false;
            var selected_cperiod_index = $(this).prop('selectedIndex');
            $(".no-results").remove();

            $(".leave-tracking-table tbody tr").each(function (key, value) {
                var tr_start_dates = $(value).attr("data-start-dates");
                var tr_end_dates   = $(value).attr("data-end-dates");
                var tr_total_days  = $(value).attr("data-total-days");

                if (typeof tr_total_days !== "undefined") {
                    tr_total_days = tr_total_days.split(",");
                }
                tr_start_dates = tr_start_dates.split(",");
                tr_end_dates = tr_end_dates.split(",");

                if (typeof tr_total_days !== "undefined") {
                    for (var j = 0; j < tr_total_days.length; j++) {
                        if (tr_total_days[j] == 0 || tr_total_days[j] == "") {
                            tr_total_days[j] = javascript_translations.please_update;
                        }
                        if (selected_cperiod_index == j) {
                            $(value).find(".total-days-count").html(tr_total_days[j]);
                        }
                    }
                }

                for (var i = 0; i < tr_start_dates.length; i++) {
                    if (tr_start_dates[i] >= start_date && tr_end_dates[i]   <= end_date ||
                        tr_start_dates[i] >= start_date && tr_start_dates[i] <= end_date ||
                        tr_end_dates[i]   >= start_date && tr_end_dates[i]   <= end_date) {
                        $(value).removeClass("hide");
                        results = true;
                        break;
                    } else {
                        $(value).addClass("hide");
                    }
                }
            });

            if (!results) {
                display_no_results();
            }
        }
    });

    $("#leave-curriculum-period-select").on("change", function () {
        $.ajax({
            url: ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
            data: {
                method: "set-curriculum-period",
                cperiod_id: $("#leave-curriculum-period-select").val()
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    console.log("Successfully updated curriculum period preference.");
                    location.reload();
                } else {
                    console.log("Unable to update curriculum period preference.");
                }
            }
        });
    });

    function display_no_results() {
        var no_results_tr = jQuery(document.createElement("tr")).addClass("no-results").css({"background-color": "#f9f9f9"});
        var no_results_td = jQuery(document.createElement("td")).attr({"colspan": jQuery(".leave-tracking-table").attr("data-colspan")});
        var no_results_span = jQuery(document.createElement("span")).html(javascript_translations.no_records_found_in_cperiod);
        jQuery(".leave-tracking-table tbody").append(no_results_tr.append(no_results_td.append(no_results_span)));
    }

    $("#leave-table_filter").remove();
    $("#current-leave-table_filter").remove();
    $("#learner-curriculum-period-select").trigger("change");
});