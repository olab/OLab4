jQuery(document).ready(function ($) {
    
    
    get_event_resources();
    
    var edit_mode = false;
    var next_step_control = $("#resource_next_step");
    var previous_step_control = $("#resource_previous_step");
    var resource_step_control = $("#resource_step");
    var resource_substep_control = $("#resource_substep");
    var resource_type_value_control = $("#event_resource_type_value");
    var resource_required_value_control = $("#event_resource_required_value");
    var resource_release_value_control = $("#event_resource_release_value");
    var resource_timeframe_value_control = $("#event_resource_timeframe_value");
    var resource_release_start_control = $("#event_resource_release_start_value");
    var resource_release_start_time_control = $("#event_resource_release_start_time_value");
    var resource_release_finish_control = $("#event_resource_release_finish_value");
    var resource_release_finish_time_control = $("#event_resource_release_finish_time_value");
    var resource_recurring_bool = $("#re_bool").val();
    var recurring_event_ids = JSON.parse($("#re_ids").val());
    var resource_recurring_event_ids = recurring_event_ids;
    var resource_step_container = $("#event-resource-step");
    var event_resource_drop_overlay = $("#event_resource_drop_overlay");
    var event_resource_id_value = $("#resource_id");
    var resource_attach_file = $("#event_resource_attach_file");
    
    var dragdrop = false;
    
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    }
    
    if (dragdrop) {
        
        /**
        *
        * Event listeners for drag and drop file uploading
        *
        */
       
       var timer;

       $(".modal-body").on("dragover", function (event) {
           clearTimeout(timer);
           event.preventDefault();
           event.stopPropagation();
           if ($(".modal-body").hasClass("upload")) {
               $("#event_resource_form").addClass("hide");
               event_resource_drop_overlay.removeClass("hide");
           }
       });

       $(".modal-body").on("dragleave", function (event) {
            event.preventDefault();
            event.stopPropagation();
            if ($(".modal-body").hasClass("upload")) {
                timer = setTimeout(function() {
                    event_resource_drop_overlay.addClass("hide");
                    $("#event_resource_form").removeClass("hide");
                }, 200);
            }
            return false;
       });

       $(".modal-body").on("drop", function (event) {
            event.preventDefault();
            event.stopPropagation();
           
            event.dataTransfer = event.originalEvent.dataTransfer;
            var file = event.dataTransfer.files[0];

            event_resource_drop_overlay.addClass("hide");

            $("#event-resource-msgs").empty();

            $("#event_resource_form").removeClass("hide");
            if ($(".modal-body").hasClass("upload")) {
                upload_file(file);
            }
       });
    }
    
    /*
     *
     * The abilty to see who has viewed a resource has been removed for the moment as per request.
     * This event listener just needs to be uncommented to re-enable that functionality, also removed was the cursor style on the "views" badge
     * so it doesn't appear clickable (removed from line 2018 & 2036 .css("cursor", pointer)
     *
     */
    $("#event-resources-container").on("click", ".resource-view-toggle", function (event) {
        var resource_type = $(this).attr("data-type");
        var resource_id = $(this).attr("data-value");
        var resource_title = $(this).attr("data-title");

        $("#event-resource-view-modal-heading").html(resource_title + " views");
        event.preventDefault();
        $.ajax({
            url: SITE_URL + "/admin/events?section=api-resource-wizard",
            data: "method=resource-views&resource_type=" + resource_type + "&resource_id=" + resource_id,
            type: 'GET',
            success: function (data) {
                $("#resource-views-table tbody").empty();
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $.each(jsonResponse.data, function (key, view) {
                        var view_table_row  = document.createElement("tr");
                        var view_name_td = document.createElement("td");
                        var view_views_td = document.createElement("td");
                        var view_date_td = document.createElement("td");

                        $(view_name_td).append(view.name);
                        $(view_views_td).append(view.views);
                        $(view_date_td).append(view.last_viewed);
                        $(view_table_row).append(view_name_td).append(view_views_td).append(view_date_td);
                        $("#resource-views-table tbody").append(view_table_row);
                    });
                } if (jsonResponse.status === "error") {
                    $("#resource-views-table tbody").html("<tr class=\"muted text-center\"><td colspan=\"3\">" + jsonResponse.data + "</td></tr>");
                }
            },
            beforeSend: function () {
                $("#event-resources-views-loading").removeClass("hide");
                $("#resource-views-table tbody").empty();
            },
            complete: function () {
                $("#event-resources-views-loading").addClass("hide");
            }
        });
        $("#resource-views-table").width("100%");
        $("#event-resource-view-modal").modal("show");
    });

    
    $("#event-resource-step").on("change", "#event_resource_upload", function (event) {
        if (dragdrop) {
            event.target = event.originalEvent.target;
            var file = event.target.files[0];
            upload_file(file);
        } else {
            $("#event_resource_form").submit();
        }
    });
    
    /**
     * 
     *  Event listeners for common wizard controls
     * 
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_type]", function () {
        resource_type_value_control.val($(this).val());
        if ($(".resource_type_control").length) {
            $(".resource_type_control").remove();
        }
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_required]", function () {
        $("#event_resource_required_value").val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_timeframe]", function () {
        resource_timeframe_value_control.val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_release_start]", function () {
        resource_release_start_control.val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_release_start_time]", function () {
        resource_release_start_time_control.val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_release_finish]", function () {
        resource_release_finish_control.val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_release_finish_time]", function () {
        resource_release_finish_time_control.val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=resource_release]", function () {
        resource_release_value_control.val($(this).val());
        if ($(this).val() == "yes") {
            $("#resource_release_container").removeClass("hide");
        } else {
            $("#resource_release_container").addClass("hide");
        }
    });
    
    $("#event-resource-step").on("click", ".datepicker", function () {
        $(this).datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function (date) {
                if ($(this).hasClass("start-date")) {
                    resource_release_start_control.val(date);
                } else {
                    resource_release_finish_control.val(date);
                }
            }
        });
        
        $(this).datepicker("show");
    });
    
    $("#event-resource-step").on("click", ".timepicker", function () {
        $(this).timepicker({
            onSelect: function (time) {
                if ($(this).hasClass("start-time")) {
                    resource_release_start_time_control.val(time);
                } else {
                    resource_release_finish_time_control.val(time);
                }
            }
        });
        
        $(this).timepicker("show");
    });
    
    $("#event-resource-step").on("click", ".r_events", function () {
        var r_e_ids = $(".r_events:checked");
        resource_recurring_event_ids = [];
        $.each(r_e_ids, function (key, value) {
            var id = $(value).data("id");
            resource_recurring_event_ids.push(id);
        });
    });

    $("#event-resources-container").on("click", ".resource-link", function () {
        edit_mode = true;
        
        var title_modal = "Edit Event Resource";
        $("#event_resource_modal_title").html(title_modal);
        
        var event_resource_id = $(this).parent().attr("data-id");
        var data_string;
        if (resource_recurring_bool == 1) {
            data_string = "method=event_resource&event_resource_id=" + event_resource_id + "&recurring_event_ids=" + JSON.stringify(resource_recurring_event_ids);
        } else {
            data_string = "method=event_resource&event_resource_id=" + event_resource_id;
        }

        $("#event-resource-modal").modal("show");
        $.ajax({
            url: SITE_URL + "/admin/events?section=api-resource-wizard",
            data: data_string,
            type: 'GET',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    var title_span = document.createElement("span");
                    $(title_span).addClass("event-resource-title-small");
                    resource_step_control.val("2");
                    build_hidden_form_controls(jsonResponse.data.resource_type);
                    resource_type_value_control.val(jsonResponse.data.resource_type);
                    resource_timeframe_value_control.val(jsonResponse.data.timeframe);
                    resource_release_start_control.val(jsonResponse.data.release_date);
                    resource_release_finish_control.val(jsonResponse.data.release_until);
                    resource_release_start_time_control.val(jsonResponse.data.start_time);
                    resource_release_finish_time_control.val(jsonResponse.data.finish_time);
                    event_resource_id_value.val(jsonResponse.data.resource_id);
                    $("#event_resource_entity_id").val(jsonResponse.data.entity_id);
                    
                    if (jsonResponse.data.release_date != "" || jsonResponse.data.release_until != "") {
                        resource_release_value_control.val("yes");
                    } else {
                        resource_release_value_control.val("no");
                    }
                    
                    if (jsonResponse.data.required == "1") {
                        resource_required_value_control.val("yes");
                    } else {
                        resource_required_value_control.val("no");
                    }
                    
                    switch (parseInt(jsonResponse.data.resource_type)) {
                        case 1 :
                        case 5 :
                        case 6 :
                        case 11 :
                            $("#event_resource_file_view_value").val((jsonResponse.data.access_method == "0" ? "download" : "view"));
                            $("#event_resource_file_title_value").val(jsonResponse.data.title);
                            $("#event_resource_file_description_value").val(jsonResponse.data.description);
                            $(title_span).html(jsonResponse.data.title);
                            $("#event_resource_modal_title").append("<br/>").append(title_span);
                        break;
                        case 2 :
                            $("#event_resource_bring_description_value").val(jsonResponse.data.description);
                        break;
                        case 3 :
                            
                            var proxyify = "";
                            if (jsonResponse.data.proxyify == "1") {
                                proxyify = "yes";
                            } else {
                                proxyify = "no";
                            }
                            
                            $("#event_resource_link_proxy_value").val(proxyify);
                            $("#event_resource_link_url_value").val(jsonResponse.data.link);
                            $("#event_resource_link_title_value").val(jsonResponse.data.title);
                            $("#event_resource_link_description_value").val(jsonResponse.data.description);
                            $(title_span).html(jsonResponse.data.title);
                            $("#event_resource_modal_title").append("<br/>").append(title_span);
                        break;
                        case 4 :
                            $("#event_resource_homework_description_value").val(jsonResponse.data.description);
                        break;
                        case 7 :
                            $("#event_resource_module_proxy_value").val((jsonResponse.data.proxyify == "1" ? "yes" : "no"));
                            $("#event_resource_module_url_value").val(jsonResponse.data.link);
                            $("#event_resource_module_title_value").val(jsonResponse.data.title);
                            $("#event_resource_module_description_value").val(jsonResponse.data.description);
                            $(title_span).html(jsonResponse.data.title);
                            $("#event_resource_modal_title").append("<br/>").append(title_span);
                        break;
                        case 8 :
                            var quiz_type = parseInt(jsonResponse.data.quiztype_id);
                            var quiz_results = "";
                            
                            if (quiz_type == 1) {
                                quiz_results = "delayed";
                            } else if (quiz_type == 2) {
                                quiz_results = "immediate";
                            } else {
                                quiz_results = "hide";
                            }
                            
                            $("#event_resource_quiz_id_value").val(jsonResponse.data.quiz_id);
                            $("#event_resource_quiz_title_value").val(jsonResponse.data.title);
                            $("#event_resource_quiz_instructions_value").val(jsonResponse.data.description);
                            $("#event_resource_quiz_attendance_value").val((jsonResponse.data.require_attendance == 1 ? "yes" : "no"));
                            $("#event_resource_quiz_shuffled_value").val((jsonResponse.data.random_order == 1 ? "yes" : "no"));
                            $("#event_resource_quiz_time_value").val(jsonResponse.data.quiz_timeout);
                            $("#event_resource_quiz_attempts_value").val(jsonResponse.data.quiz_attempts);
                            $("#event_resource_quiz_results_value").val(quiz_results);
                            $(title_span).html(jsonResponse.data.title);
                            $("#event_resource_modal_title").append("<br/>").append(title_span);
                            
                        break;
                        case 9 : 
                            $("#event_resource_textbook_description_value").val(jsonResponse.data.description);
                        break;
                        case 10 :
                            $("#event_resource_lti_title_value").val(jsonResponse.data.title);
                            $("#event_resource_lti_description_value").val(jsonResponse.data.description);
                            $("#event_resource_lti_url_value").val(jsonResponse.data.link);
                            $("#event_resource_lti_key_value").val(jsonResponse.data.lti_key);
                            $("#event_resource_lti_secret_value").val(jsonResponse.data.lti_secret);
                            $("#event_resource_lti_parameters_value").val(jsonResponse.data.lti_params);
                            $(title_span).html(jsonResponse.data.title);
                            $("#event_resource_modal_title").append("<br/>").append(title_span);
                        break;
                    }
                    show_step();
                } else {
                    display_error(jsonResponse.data, "#event-resource-msgs", "append");
                }
            },
            beforeSend: function () {  
                $("#event_resource_loading_msg").html("Loading Event Resource...");
                $("#event_resource_form").addClass("hide");
                $("#event_resource_loading").removeClass("hide");
            },
            complete: function () {
                $("#event_resource_loading").addClass("hide");
                $("#event_resource_loading_msg").html("");
                $("#event_resource_form").removeClass("hide");
            }
        });
        
    });
    
    $("#event-resource-toggle").on("click", function (e) {
        $("#event-resources-delete-confirmation").empty();
        show_step();
        $("#event-resource-modal").modal("show");
    });
    
    $("#event-resource-previous").on("click", function () {
        $("#event-resource-msgs").empty();
        previous_step();
    });
    
    $("#event-resource-modal").on("hide", function () {
        $("#event_resource_modal_title").html("Add Event Resource");
        $("#event_resource_type_1").prop("checked", true);
        $("#event_resource_required_no").prop("checked", true);
        $("#event_resource_timeframe_pre").prop("checked", true);
        
        if (resource_step_control.val() == "3") {
            $(".datepicker").datepicker("destroy");
        }
        
        resource_step_control.val("1");
        resource_type_value_control.val("11");
        resource_required_value_control.val("no");
        resource_timeframe_value_control.val("none");
        resource_release_value_control.val("no");
        resource_release_start_control.val("");
        resource_release_finish_control.val("");
        
        resource_release_finish_time_control.val("");
        event_resource_id_value.val("");
        resource_attach_file.val("no");
        $("#event_resource_entity_id").val("");
        
        if ($(".resource_type_control").length) {
            $(".resource_type_control").remove();
        }
        
        if ($(".modal-body").hasClass("upload")) {
            $(".modal-body").removeClass("upload");
        }
        
        $("#event-resource-msgs").empty();
        $("#event_resource_loading").addClass("hide");
        
        if (!$("#copyright").hasClass("hide")) {
            $("#copyright").addClass("hide");
        }
        
        if (edit_mode) {
            edit_mode = false;
        }
    });
    
    $("#event-resource-next").on("click", function () {
        var step = parseInt(resource_step_control.val());
        var selected_resource_type = resource_type_value_control.val();
        $("#event-resource-msgs").empty();
            if (step == 6) {
                edit_mode = false;
                resource_step_control.val("1");
                resource_substep_control.val("1");
                resource_type_value_control.val("11");
                next_step_control.val("0");
                previous_step_control.val("0");
                resource_required_value_control.val("no");
                resource_timeframe_value_control.val("none");
                resource_release_value_control.val("no");
                resource_release_start_control.val("");
                resource_release_finish_control.val("");
                
                event_resource_id_value.val("");
                resource_attach_file.val("no");

                if ($(".resource_type_control").length) {
                    $(".resource_type_control").remove();
                }

                if ($(".modal-body").hasClass("upload")) {
                    $(".modal-body").removeClass("upload");
                }
                
                show_step();
            } else {
                var data_string;
                if (resource_recurring_bool == 1) {
                    data_string = "method=add&event_resource_type_value=" + selected_resource_type + "&step=" + step + "&" + $("#event_resource_form").serialize() + "&recurring_event_ids=" + JSON.stringify(resource_recurring_event_ids);
                } else {
                    data_string = "method=add&event_resource_type_value=" + selected_resource_type + "&step=" + step + "&" + $("#event_resource_form").serialize();
                }
                $.ajax({
                    url: SITE_URL + "/admin/events?section=api-resource-wizard",
                    data: data_string,
                    type: 'POST',
                    success: function (data) {
                        var jsonResponse = JSON.parse(data);
                        if ($("#event-resource-next").is(":disabled")) {
                            $("#event-resource-next").removeAttr("disabled");
                        }
                        
                        if ($("#event-resource-previous").is(":disabled")) {
                            $("#event-resource-previous").removeAttr("disabled");
                        }
                        
                        if (jsonResponse.data.next_step == "2") {
                            if (selected_resource_type == "12") {
                                var event_id = $("#event_id").val();
                                var url = ENTRADA_URL + "/admin/exams/exams?event_id=" + event_id;

                                window.location = url;
                            }
                        }
                        
                        if (jsonResponse.data.next_step == "3") {
                            if (!edit_mode) {
                                resource_release_start_control.val(jsonResponse.data.default_dates.release_start);
                                resource_release_start_time_control.val(jsonResponse.data.default_dates.release_start_time);
                                resource_release_finish_control.val(jsonResponse.data.default_dates.release_until);
                                resource_release_finish_time_control.val(jsonResponse.data.default_dates.release_until_time);
                            }
                        }
                        
                        if (jsonResponse.status === "success") {
                            next_step_control.val(jsonResponse.data.next_step);
                            resource_substep_control.val(jsonResponse.data.sub_step);
                            next_step();
                        } else {
                            display_error(jsonResponse.data, "#event-resource-msgs", "append");
                        }
                    },
                    beforeSend: function () {
                        $("#event-resource-next").attr("disabled", "disabled");
                        $("#event-resource-previous").attr("disabled", "disabled");
                        $("#event_resource_loading_msg").html("Please wait while your selections are saved");
                        $("#event_resource_form").addClass("hide");
                        $("#event_resource_loading").removeClass("hide");
                        if (!$("#copyright").hasClass("hide")) {
                            $("#copyright").addClass("hide");
                        }
                    },
                    complete: function () {
                        $("#event_resource_loading").addClass("hide");
                        $("#event_resource_form").removeClass("hide");
                        $("#event_resource_loading_msg").html("");
                    }
                });
            }
    });
    
    /**
     *
     * Event listeners for file controls
     *
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_file_view_option]", function () {
        $("#event_resource_file_view_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_file_title]", function () {
        $("#event_resource_file_title_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_file_description]", function () {
        $("#event_resource_file_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for link controls
     *
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_link_proxy]", function () {
        $("#event_resource_link_proxy_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_link_url]", function () {
        $("#event_resource_link_url_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_link_title]", function () {
        $("#event_resource_link_title_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_link_description]", function () {
        $("#event_resource_link_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for module controls
     *
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_module_proxy]", function () {
        $("#event_resource_module_proxy_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_module_url]", function () {
        $("#event_resource_module_url_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_module_title]", function () {
        $("#event_resource_module_title_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_module_description]", function () {
        $("#event_resource_module_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for lti controls
     *
     */
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_lti_title]", function () {
        $("#event_resource_lti_title_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_lti_url]", function () {
        $("#event_resource_lti_url_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_lti_key]", function () {
        $("#event_resource_lti_key_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_lti_secret]", function () {
        $("#event_resource_lti_secret_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_lti_description]", function () {
        $("#event_resource_lti_description_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_lti_parameters]", function () {
        $("#event_resource_lti_parameters_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for homework controls
     *
     */
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_homework_description]", function () {
        $("#event_resource_homework_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for bring to class controls
     *
     */
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_bring_description]", function () {
        $("#event_resource_bring_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for textbook reading controls
     *
     */
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_textbook_description]", function () {
        $("#event_resource_textbook_description_value").val($(this).val());
    });
    
    /**
     *
     * Event listeners for quiz controls
     *
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_quiz_id]", function () {
        $("#event_resource_quiz_id_value").val($(this).val());
        $("#event_resource_quiz_title_value").val($(this).attr("data-title"));
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_quiz_title]", function () {
        $("#event_resource_quiz_title_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "textarea[name=event_resource_quiz_instructions]", function () {
        $("#event_resource_quiz_instructions_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_quiz_time]", function () {
        $("#event_resource_quiz_time_value").val($(this).val());
    });
    
    $("#event-resource-step").on("keyup", "input[name=event_resource_quiz_attempts]", function () {
        $("#event_resource_quiz_attempts_value").val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_quiz_attendance]", function () {
        $("#event_resource_quiz_attendance_value").val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_quiz_shuffled]", function () {
        $("#event_resource_quiz_shuffled_value").val($(this).val());
    });
    
    $("#event-resource-step").on("change", "input[name=event_resource_quiz_results]", function () {
        $("#event_resource_quiz_results_value").val($(this).val());
    });
    
    /**
     *
     * Event listener for exam stats
     *
     */

    $("#event-resources-container").on("click", ".event-resource-access-label.exam-post-stats", function (e) {
        e.preventDefault();
        var post_id = $(this).attr("data-value");
        var url     = SITE_URL + "/admin/exams/exams?section=activity&id=" + post_id;

        window.location = url;
    });

    /**
     *
     * Event listener for attach new file controls
     *
     */
    
    $("#event-resource-step").on("change", "input[name=event_resource_attach_file]", function () {
        $("#event_resource_attach_file").val($(this).val());
    });
    
    /**
     *
     * Event listener for the delete modal
     *
     */
    
    $("#event-resources-container").on("click", ".delete-resource", function (e) {
        e.preventDefault();

        var data_id             = $(this).attr("data-id");
        var recurring_event_ids = $("#re_ids").val();
        var recurring_events    = $("#re_bool").val();

        var msg = "Are you sure you want to <strong>delete</strong> this resource?";

        if (recurring_events) {
            var data_object = {
                "method": "recurring_events_resource_view",
                "data_id": data_id,
                "recurring_event_ids": recurring_event_ids
            };

            $.ajax({
                url: SITE_URL + "/admin/events?section=api-resource-wizard",
                data: data_object,
                type: 'GET',
                success: function (data) {
                    var jsonResponse = JSON.parse(data);

                    if (jsonResponse.status === "success") {
                        var html = jsonResponse.data.html;
                        var resource = jsonResponse.data.resource;

                        if (recurring_events && html) {
                            msg += "<br/>You can also delete it from the following recurring events in this series.";
                            $("#delete-event-resource-modal .modal-body").append(html);
                            $("#delete-event-resource-modal").css({"max-height": 425});
                            display_notice([msg], "#delete-event-resource-msgs", "append");
                        } else {
                            display_notice([msg], "#delete-event-resource-msgs", "append");
                            $("#delete-event-resource-modal").css({"max-height": 230});
                        }
                    } else {
                        display_error(jsonResponse.data, "#event-resource-msgs", "append");
                    }
                },
                beforeSend: function () {

                },
                complete: function () {

                }
            });
        } else {
        display_notice([msg], "#delete-event-resource-msgs", "append");
        
        var delete_table = document.createElement("table");
        var delete_table_thead = document.createElement("thead");
        var delete_table_tbody = document.createElement("tbody");
        var delete_headings_tr = document.createElement("tr");
        var delete_row_tr = document.createElement("tr");
        var delete_title_th = document.createElement("th");
        var delete_description_th = document.createElement("th");
        var delete_title_td = document.createElement("td");
        var delete_description_td = document.createElement("td");
        
        $(delete_title_th).html("Resource Title").width("30%");
        $(delete_description_th).html("Resource Description").width("70%");
        $(delete_title_td).html($(this).siblings(".resource-link").html());
        $(delete_description_td).html($(this).siblings(".resource-description").html() );
        $(delete_headings_tr).append(delete_title_th).append(delete_description_th);
        $(delete_row_tr).append(delete_title_td).append(delete_description_td);
        $(delete_table_thead).append(delete_headings_tr);
        $(delete_table_tbody).append(delete_row_tr);
        $(delete_table).append(delete_table_thead).append(delete_table_tbody).addClass("table table-striped table-bordered");
        $("#delete-event-resource-modal .modal-body").append(delete_table);
        }
        
        $("#delete-event-resource-modal").modal("show");
        $("#delete-event-resource").attr({"data-id": data_id});
    });
    
    $("#delete-event-resource-modal").on("hide", function () {
        $("#delete-event-resource-msgs").empty();
        $("#delete-event-resource-modal .modal-body table").remove();
    });
    
    $("#delete-event-resource").on("click", function () {
        var entity_id = $(this).attr("data-id");
        var data = $("#delete-event-resource-modal input.entity:checked");
        var entities = [];
        if (data) {
            $.each(data, function(key, value) {
                var entity_id = $(value).data("entity-id");
                entities.push(entity_id);
            });
        }
        $.ajax({
            url: SITE_URL + "/admin/events?section=api-resource-wizard",
            data: "method=delete&entity_id=" + entity_id + "&entities=" + entities,
            type: 'POST',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $("#delete-event-resource-modal").modal("hide");
                    display_success(jsonResponse.data, "#event-resources-delete-confirmation", "append");
                    get_event_resources();
                } else {
                    display_error(jsonResponse.data, "#event-resource-msgs", "append");
                }
            },
            beforeSend: function () {
                
            },
            complete: function () {
                
            }
        });
    });
    
    function next_step () {
        resource_step_control.val(next_step_control.val());
        show_step();
    }
    
    function previous_step () {
        var step = parseInt(resource_step_control.val());
        var substep = parseInt(resource_substep_control.val());
        
        if (substep == 1) {
            if (step == 5) {
                if (resource_recurring_bool == 1) {
                    resource_step_control.val(step -1);
                } else {
                    resource_step_control.val(step -2);
                }
            } else {
                resource_step_control.val(step -1);
            }
        } else {
            resource_substep_control.val(substep -1);
        }
        
        if ($(".modal-body").hasClass("upload")) {
            $(".modal-body").removeClass("upload");
        }
        
        show_step();
    }
    
    function show_step () {
        $("#event-resource-next").html("Next Step");
        
        if ($("#event-resource-next").is(":disabled")) {
            $("#event-resource-next").removeAttr("disabled");
        }
        
        var step = parseInt(resource_step_control.val());
        var selected_resource_type = parseInt(resource_type_value_control.val());
        var sub_step = parseInt(resource_substep_control.val());
        
        if (step > 1) {
            $("#event-resource-previous").removeClass("hide");
        } else {
            $("#event-resource-previous").addClass("hide");
        }
        
        if (step == 2 && $("#event_resource_entity_id").val()) {
            $("#event-resource-previous").addClass("hide");
        }
       
        resource_step_container.empty();
        switch (step) {
            case 1 :
                
                /**
                 *
                 * Builds resource type options
                 *
                 */
                
                $.ajax({
                    url: SITE_URL + "/admin/events?section=api-resource-wizard",
                    data: "method=event_resource_types",
                    type: 'GET',
                    success: function (data) {
                        var selected_resource_type = resource_type_value_control.val();
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status === "success") {
                            var step_heading = document.createElement("h3");
                            var step_control_group = document.createElement("div");
                            
                            $(step_heading).html("What type of resource would you like to add?");
                            
                            $.each(jsonResponse.data, function (key, event_resource) {
                                var resource_label = document.createElement("label");
                                var resource_radio = document.createElement("input");
                                var resource_p = document.createElement("span");
                                
                                $(resource_label).attr({"for": "event_resource_type_" + event_resource.event_resource_type_id}).addClass("radio");
                                $(resource_radio).attr({type: "radio", id: "event_resource_type_" + event_resource.event_resource_type_id, value: event_resource.event_resource_type_id, name: "event_resource_type"});
                                $(resource_p).html(event_resource.description).addClass("muted resource-type-description");
                                $(resource_label).append(resource_radio).append(event_resource.resource_type).append(resource_p);
                                
                                $(step_control_group).append(resource_label).addClass("control-group");
                            });
                            
                            resource_step_container.append(step_heading).append(step_control_group);
                            
                            $("#event_resource_type_" + selected_resource_type).prop("checked", true);
                        } else {
                            display_error(jsonResponse.data, "#event-resource-msgs", "append");
                        }
                    },
                    beforeSend: function () {
                        $("#event-resource-next").attr("disabled", "disabled");
                        $("#event-resource-previous").attr("disabled", "disabled");
                        $("#event_resource_loading_msg").html("Loading event resource options");
                        $("#event_resource_form").addClass("hide");
                        $("#event_resource_loading").removeClass("hide");
                    },
                    complete: function () {
                        if ($("#event-resource-next").is(":disabled")) {
                            $("#event-resource-next").removeAttr("disabled");
                        }
                        
                        if ($("#event-resource-previous").is(":disabled")) {
                            $("#event-resource-previous").removeAttr("disabled");
                        }
                        
                        $("#event_resource_loading").addClass("hide");
                        $("#event_resource_form").removeClass("hide");
                        $("#event_resource_loading_msg").html("");
                    }
                });
                
            break;
            case 2 :
                
                /**
                 *
                 * Builds required options
                 *
                 */
                
                var selected_resource_required = resource_required_value_control.val();
                
                var resource_required_heading = document.createElement("h3");
                var resource_required_control_group = document.createElement("div");
                var resource_required_label = document.createElement("label");
                var resource_required_radio = document.createElement("input");
                var resource_optional_label = document.createElement("label");
                var resource_optional_radio = document.createElement("input");
                
                $(resource_required_heading).html("Should viewing this resource be considered optional or required?");
                
                $(resource_optional_label).attr({"for": "event-resource-required-no"}).addClass("radio");
                $(resource_optional_radio).attr({type: "radio", id: "event-resource-required-no", value: "no", name: "event_resource_required"});
                $(resource_optional_label).append(resource_optional_radio).append("Optional");
                
                $(resource_required_label).attr({"for": "event-resource-required-yes"}).addClass("radio");
                $(resource_required_radio).attr({type: "radio", id: "event-resource-required-yes", value: "yes", name: "event_resource_required"});
                $(resource_required_label).append(resource_required_radio).append("Required");
                
                $(resource_required_control_group).append(resource_optional_label).append(resource_required_label).addClass("control-group");
                $("#event-resource-step").append(resource_required_heading).append(resource_required_control_group);
                $("#event-resource-required-" + selected_resource_required).prop("checked", true);
                
                /**
                 *
                 * Builds timeframe options
                 *
                 */
                
                var selected_resource_timefarme = resource_timeframe_value_control.val();
               
                var resource_heading_timeframe = document.createElement("h3");
                var resource_timeframe_control_group = document.createElement("div");
                var resource_timeframe_pre_label = document.createElement("label");
                var resource_timeframe_pre_radio = document.createElement("input");
                var resource_timeframe_during_label = document.createElement("label");
                var resource_timeframe_during_radio = document.createElement("input");
                var resource_timeframe_post_label = document.createElement("label");
                var resource_timeframe_post_radio = document.createElement("input");
                var resource_timeframe_none_label = document.createElement("label");
                var resource_timeframe_none_radio = document.createElement("input");
                
                $(resource_heading_timeframe).html("When should this resource be used by the learner?");
                
                $(resource_timeframe_pre_label).attr({"for": "event-resource-timeframe-pre"}).addClass("radio");
                $(resource_timeframe_pre_radio).attr({type: "radio", id: "event-resource-timeframe-pre", value: "pre", name: "event_resource_timeframe"});
                $(resource_timeframe_pre_label).append(resource_timeframe_pre_radio).append("Before Class");
                
                $(resource_timeframe_during_label).attr({"for": "event-resource-timeframe-during"}).addClass("radio");
                $(resource_timeframe_during_radio).attr({type: "radio", id: "event-resource-timeframe-during", value: "during", name: "event_resource_timeframe"});
                $(resource_timeframe_during_label).append(resource_timeframe_during_radio).append("During Class");
                
                $(resource_timeframe_post_label).attr({"for": "event-resource-timeframe-post"}).addClass("radio");
                $(resource_timeframe_post_radio).attr({type: "radio", id: "event-resource-timeframe-post", value: "post", name: "event_resource_timeframe"});
                $(resource_timeframe_post_label).append(resource_timeframe_post_radio).append("After Class");
                
                $(resource_timeframe_none_label).attr({"for": "event-resource-timeframe-none"}).addClass("radio");
                $(resource_timeframe_none_radio).attr({type: "radio", id: "event-resource-timeframe-none", value: "none", name: "event_resource_timeframe"});
                $(resource_timeframe_none_label).append(resource_timeframe_none_radio).append("No Timeframe");
                
                $(resource_timeframe_control_group).append(resource_timeframe_pre_label).append(resource_timeframe_during_label).append(resource_timeframe_post_label).append(resource_timeframe_none_label).addClass("control-group");
                $(resource_timeframe_control_group).append(resource_timeframe_pre_label).append(resource_timeframe_during_label).append(resource_timeframe_post_label).append(resource_timeframe_none_label).addClass("control-group");
                
                resource_step_container.append(resource_heading_timeframe).append(resource_timeframe_control_group);
                $("#event-resource-required-" + selected_resource_required).prop("checked", true);
                $("#event-resource-timeframe-" + selected_resource_timefarme).prop("checked", true);
            break;
            case 3 :
                
                /**
                 *
                 * Builds time release options
                 *
                 */
                
                var selected_resource_release_option = resource_release_value_control.val();
                var start_date_value = resource_release_start_control.val();
                var finish_date_value = resource_release_finish_control.val();
                var start_time_value = resource_release_start_time_control.val();
                var finish_time_value = resource_release_finish_time_control.val();
                
                var resource_release_heading = document.createElement("h3");
                var resource_release_control_group = document.createElement("div");
                var resource_release_no_label = document.createElement("label");
                var resource_release_no_radio = document.createElement("input");
                var resource_release_yes_label = document.createElement("label");
                var resource_release_yes_radio = document.createElement("input");
                
                $(resource_release_heading).html("Would you like to add timed release dates to this resource?");
                
                $(resource_release_no_label).attr({"for": "resource_release_no"}).addClass("radio");
                $(resource_release_no_radio).attr({type: "radio", id: "resource_release_no", value: "no", name: "resource_release"});
                $(resource_release_no_label).append(resource_release_no_radio).append("No, this resource is accessible any time");
                
                $(resource_release_yes_label).attr({"for": "resource_release_yes"}).addClass("radio");
                $(resource_release_yes_radio).attr({type: "radio", id: "resource_release_yes", value: "yes", name: "resource_release"});
                $(resource_release_yes_label).append(resource_release_yes_radio).append("Yes, this resource should only be available for a certain time period");
                
                $(resource_release_control_group).append(resource_release_no_label).append(resource_release_yes_label).addClass("control-group");
                
                resource_step_container.append(resource_release_heading).append(resource_release_control_group);
                
                $("#resource_release_"  + selected_resource_release_option).prop("checked", true);
                
                var release_options_container = document.createElement("div");
                var resource_release_options_heading = document.createElement("h3");
                
                /**
                 *
                 *  Builds release start controls
                 *
                 */
                
                var resource_release_start_control_group = document.createElement("div");
                var resource_release_start_controls = document.createElement("div");
                var resource_release_start_date_append = document.createElement("div");
                var resource_release_start_time_append = document.createElement("div");
                var resource_release_start_date_span = document.createElement("span");
                var resource_release_start_date_icon = document.createElement("i");
                var resource_release_start_time_span = document.createElement("span");
                var resource_release_start_time_icon = document.createElement("i");
                var resource_release_start_label = document.createElement("label");
                var resource_release_start_date_input = document.createElement("input");
                var resource_release_start_time_input = document.createElement("input");
                
                $(resource_release_start_label).attr({"for": "event_resource_release_start"}).html("Release Start: ").addClass("control-label");
                $(resource_release_start_date_input).attr({type: "text", id: "event_resource_release_start", name: "event_resource_release_start"}).addClass("input-small datepicker start-date").val(start_date_value);
                $(resource_release_start_date_append).addClass("input-append space-right");
                $(resource_release_start_date_span).addClass("add-on pointer");
                $(resource_release_start_date_icon).addClass("icon-calendar");
                
                $(resource_release_start_time_input).attr({type: "text", id: "event_resource_release_start_time", name: "event_resource_release_start_time"}).addClass("input-mini timepicker start-time").val(start_time_value);
                $(resource_release_start_time_append).addClass("input-append");
                $(resource_release_start_time_span).addClass("add-on pointer");
                $(resource_release_start_time_icon).addClass("icon-time");
                $(resource_release_start_date_span).append(resource_release_start_date_icon);
                $(resource_release_start_date_append).append(resource_release_start_date_input).append(resource_release_start_date_span);
                $(resource_release_start_controls).append(resource_release_start_date_append).addClass("controls");
                
                $(resource_release_start_time_span).append(resource_release_start_time_icon);
                $(resource_release_start_time_append).append(resource_release_start_time_input).append(resource_release_start_time_span);
                $(resource_release_start_controls).append(resource_release_start_time_append);
                $(resource_release_start_control_group).append(resource_release_start_label).append(resource_release_start_controls).addClass("control-group");
                
                /**
                 *
                 *  Builds release finish controls
                 *
                 */
                
                var resource_release_finish_control_group = document.createElement("div");
                var resource_release_finish_controls = document.createElement("div");
                var resource_release_finish_date_append = document.createElement("div");
                var resource_release_finish_time_append = document.createElement("div");
                var resource_release_finish_date_span = document.createElement("span");
                var resource_release_finish_date_icon = document.createElement("i");
                var resource_release_finish_time_span = document.createElement("span");
                var resource_release_finish_time_icon = document.createElement("i");
                var resource_release_finish_label = document.createElement("label");
                var resource_release_finish_date_input = document.createElement("input");
                var resource_release_finish_time_input = document.createElement("input");
                
                $(resource_release_finish_label).attr({"for": "event_resource_release_finish"}).html("Release Finish: ").addClass("control-label");
                $(resource_release_finish_date_input).attr({type: "text", id: "event_resource_release_finish", name: "event_resource_release_finish"}).addClass("input-small datepicker fnish-date").val(finish_date_value);
                $(resource_release_finish_date_append).addClass("input-append space-right");
                $(resource_release_finish_date_span).addClass("add-on pointer");
                $(resource_release_finish_date_icon).addClass("icon-calendar");
                
                $(resource_release_finish_time_input).attr({type: "text", id: "event_resource_release_finish_time", name: "event_resource_release_finish_time"}).addClass("input-mini timepicker finish-time").val(finish_time_value);
                $(resource_release_finish_time_append).addClass("input-append");
                $(resource_release_finish_time_span).addClass("add-on pointer");
                $(resource_release_finish_time_icon).addClass("icon-time");
                $(resource_release_finish_date_span).append(resource_release_finish_date_icon);
                $(resource_release_finish_date_append).append(resource_release_finish_date_input).append(resource_release_finish_date_span);
                $(resource_release_finish_controls).append(resource_release_finish_date_append).addClass("controls");

                $(resource_release_finish_time_span).append(resource_release_finish_time_icon);
                $(resource_release_finish_time_append).append(resource_release_finish_time_input).append(resource_release_finish_time_span);
                $(resource_release_finish_controls).append(resource_release_finish_time_append);
                $(resource_release_finish_control_group).append(resource_release_finish_label).append(resource_release_finish_controls).addClass("control-group");
                
                $(release_options_container).attr({id: "resource_release_container"}).append(resource_release_start_control_group).append(resource_release_finish_control_group);
                
                if (selected_resource_release_option == "no") {
                    $(release_options_container).addClass("hide");
                }
                
                $(resource_step_container).append(resource_release_options_heading).append(release_options_container);
                
                /*if (!resource_release_start_control.val()) {
                    var now     = new Date(); 
                    var year    = now.getFullYear();
                    var month   = now.getMonth()+1; 
                    var day     = now.getDate();
                    var hour    = now.getHours();
                    var minute  = now.getMinutes();

                    if (month.toString().length == 1) {
                        var month = "0" + month;
                    }

                    if (day.toString().length == 1) {
                        var day = "0" + day;
                    } 

                    if (hour.toString().length == 1) {
                        var hour = "0" + hour;
                    }

                    if (minute.toString().length == 1) {
                        var minute = "0" + minute;
                    }

                    var date = year + '-' + month + '-' + day;
                    var time = hour + ":" + minute;
                    
                    $("#event_resource_release_start").val(date);
                    $("#event_resource_release_start_time").val(time);
                    resource_release_start_control.val(date);
                    resource_release_start_time_control.val(time);
                }*/
                
            break;
            case 4 :
                if (resource_recurring_bool == 1) {
                    var data_object = {
                        "method": "recurring_events_view",
                        "recurring_event_ids": recurring_event_ids,
                        "ids_checked": resource_recurring_event_ids
                    };

                    $.ajax({
                        url: SITE_URL + "/admin/events?section=api-resource-wizard",
                        data: data_object,
                        type: 'GET',
                        success: function (data) {
                            var jsonResponse = JSON.parse(data);
                            resource_substep_control.val(jsonResponse.data.sub_step);
                            if (jsonResponse.status === "success") {
                                if (jsonResponse.data.recurring_events === true) {
                                    var html = jsonResponse.data.html;
                                    var resource_recurring_heading = document.createElement("h3");
                                    var resource_recurring_body = document.createElement("div");
                                    var resource_recurring_ul = document.createElement("ul");
                                    var recurring_header = "Select the recurring events you would like apply these changes to.";
                                    $(resource_recurring_heading).attr({id: "resource_recurring_container"}).append(recurring_header);
                                    $(resource_recurring_body).attr({id: "resource_recuring_body"}).append(resource_recurring_ul).append(html);
                                    $(resource_step_container).append(resource_recurring_heading).append(resource_recurring_body);
                                }
                            } else {
                                display_error(jsonResponse.data, "#event-resource-msgs", "append");
                            }
                        }
                    });
                } else {
                    display_error(["You've arrived to a step you shouldn't see."], "#event-resource-msgs", "append")
                }

                break;
            case 5 :
                switch (selected_resource_type) {
                    case 1 :
                    case 5 :
                    case 6 :
                    case 11 :
                        
                        build_hidden_form_controls (selected_resource_type);
                        
                        switch (sub_step) {
                            case 1 :
                                if (selected_resource_type != 1) {
                                    
                                    /**
                                     *
                                     * Builds file view controls
                                     *
                                     */

                                    var selected_file_view_option = $("#event_resource_file_view_value").val();

                                    var resource_file_view_option_heading = document.createElement("h3");
                                    var resource_file_view_option_control_group = document.createElement("div");
                                    var resource_file_option_download_label = document.createElement("label");
                                    var resource_file_option_download_radio = document.createElement("input");
                                    var resource_file_option_view_label = document.createElement("label");
                                    var resource_file_option_view_radio = document.createElement("input");


                                    $(resource_file_view_option_heading).html("How do you want people to view this file?");
                                    $(resource_file_option_download_radio).attr({type: "radio", "id": "event_resource_file_download", name: "event_resource_file_view_option"}).val("download");
                                    $(resource_file_option_download_label).attr({"for": "event_resource_file_download"}).addClass("radio");
                                    $(resource_file_option_view_radio).attr({type: "radio", "id": "event_resource_file_view", name: "event_resource_file_view_option"}).val("view");
                                    $(resource_file_option_view_label).attr({"for": "event_resource_file_view"}).addClass("radio");
                                    $(resource_file_option_download_label).append(resource_file_option_download_radio).append("Download it to their computer first, then open it.");
                                    $(resource_file_option_view_label).append(resource_file_option_view_radio).append("Attempt to view it directly in the web-browser.");
                                    $(resource_file_view_option_control_group).append(resource_file_view_option_heading).append(resource_file_option_download_label).append(resource_file_option_view_label).addClass("control-group");
                                }
                                
                                    /**
                                     *
                                     * Builds file title controls
                                     *
                                     */

                                    var resource_file_title_heading = document.createElement("h3");
                                    var resource_file_title_control_group = document.createElement("div");
                                    var resource_file_title_controls = document.createElement("div");
                                    var resource_file_title_input = document.createElement("input");

                                    $(resource_file_title_heading).html("You can optionally provide a different title for this file.");
                                    $(resource_file_title_input).attr({type: "text", id: "event_resource_file_title", name: "event_resource_file_title"}).addClass("input-xlarge").val($("#event_resource_file_title_value").val());
                                    $(resource_file_title_controls).append(resource_file_title_input);
                                    $(resource_file_title_control_group).append(resource_file_title_heading).append(resource_file_title_controls).addClass("control-group");
                                
                                /**
                                 *
                                 * Builds file description controls
                                 *
                                 */
                                
                                var resource_file_description_heading = document.createElement("h3");
                                var resource_file_description_control_group = document.createElement("div");
                                var resource_file_description_controls = document.createElement("div");
                                var resource_file_description_textarea = document.createElement("textarea");

                                $(resource_file_description_heading).html("You must provide a description for this file as well.");
                                $(resource_file_description_textarea).attr({name: "event_resource_file_description", id: "event_resource_file_description", rows: "6"}).addClass("input-xxlarge").val($("#event_resource_file_description_value").val());
                                $(resource_file_description_controls).append(resource_file_description_textarea);
                                $(resource_file_description_control_group).append(resource_file_description_heading).append(resource_file_description_controls).addClass("control-group");
                                
                                resource_step_container.append(resource_file_title_control_group).append(resource_file_description_control_group);
                                
                                if (edit_mode) {
                                    var attach_file_heading = document.createElement("h3");
                                    var attach_file_control_group = document.createElement("div");
                                    var attach_file_yes_label = document.createElement("label");
                                    var attach_file_yes_radio = document.createElement("input");
                                    var attach_file_no_label = document.createElement("label");
                                    var attach_file_no_radio = document.createElement("input");
                                    
                                    
                                    $(attach_file_heading).html("Would you like to replace the current file with a new one?");
                                    $(attach_file_yes_radio).attr({type: "radio", name: "event_resource_attach_file", id: "event_resource_attach_file_yes"}).val("yes");
                                    $(attach_file_no_radio).attr({type: "radio", name: "event_resource_attach_file", id: "event_resource_attach_file_no"}).val("no");
                                    $(attach_file_yes_label).attr({"for": "event_resource_attach_file_yes"}).append(attach_file_yes_radio).append("Yes, I would like to replace the existing file.").addClass("radio");
                                    $(attach_file_no_label).attr({"for": "event_resource_attach_file_no"}).append(attach_file_no_radio).append("No, I do not wish to replace current file.").addClass("radio");
                                    
                                    $(attach_file_control_group).append(attach_file_heading).append(attach_file_no_label).append(attach_file_yes_label).addClass("control-group");
                                    resource_step_container.prepend(attach_file_control_group);
                                }
                                
                                if (selected_resource_type != 1) {
                                    resource_step_container.prepend(resource_file_view_option_control_group);
                                }
                                
                                var selected_attach_file_option = $("#event_resource_attach_file").val();
                                $("#event_resource_file_" + selected_file_view_option).prop("checked", true);
                                $("#event_resource_attach_file_" + selected_attach_file_option).prop("checked", true);
                            break;
                            case 2 :
                                
                                /**
                                 *
                                 * Gets and builds UI for the file upload copyright statement
                                 *
                                 */
                                
                                $.ajax({
                                    url: SITE_URL + "/admin/events?section=api-resource-wizard",
                                    data: "method=copyright",
                                    type: 'GET',
                                    success: function (data) {
                                        var jsonResponse = JSON.parse(data);
                                        if (jsonResponse.status === "success") {
                                            var copyright_heading = document.createElement("h3");
                                            var copyright_div = document.createElement("div");
                                            
                                            $(copyright_heading).html(jsonResponse.data.app_name + " File Upload Copyright Statement");
                                            $(copyright_div).html(jsonResponse.data.copyright_statement);
                                            resource_step_container.append(copyright_heading).append(copyright_div);
                                        } else {
                                            display_error(jsonResponse.data, "#event-resource-msgs", "append");
                                        }
                                    },
                                    beforeSend: function () {
                                        $("#event-resource-next").attr("disabled", "disabled");
                                        $("#event-resource-previous").attr("disabled", "disabled");
                                        $("#event_resource_loading_msg").html("Loading Copyright Statement");
                                        $("#event_resource_form").addClass("hide");
                                        $("#event_resource_loading").removeClass("hide");
                                    },
                                    complete: function () {
                                        if ($("#event-resource-next").is(":disabled")) {
                                            $("#event-resource-next").removeAttr("disabled");
                                        }

                                        if ($("#event-resource-previous").is(":disabled")) {
                                            $("#event-resource-previous").removeAttr("disabled");
                                        }
                                        
                                        $("#event_resource_loading").addClass("hide");
                                        $("#event_resource_form").removeClass("hide");
                                        $("#event_resource_loading_msg").html("");
                                    }
                                });
                                
                                
                            break;
                            case 3 :
                                
                                /**
                                *
                                * Builds drag and drop interface
                                *
                                */
                               

                                var upload_input = document.createElement("input");
                                var upload_input_div = document.createElement("div");
                                var drag_drop_p = document.createElement("p");
                                var upload_label = document.createElement("label");
                                var upload_span = document.createElement("span");

                                $(upload_span).html("No file selected").addClass("span6 event-resource-upload-span");
                                
                                $(upload_input).attr({type: "file", id: "event_resource_upload", name: "file"}).addClass("hide");
                                $(upload_label).addClass("btn btn-success span3").append("Browse").append(upload_input);
                                $(upload_input_div).append(upload_label).append(upload_span).addClass("event-resource-upload-input-div");
                                $(drag_drop_p).html("Please select a file to upload.").addClass("event-resource-upload-text").css("margin-top", "35px");

                                if (dragdrop) {
                                    var drag_drop_img_div = document.createElement("div");
                                    var drag_drop_img = document.createElement("img");

                                    $(drag_drop_p).html("You can drag and drop files into this window to upload.").addClass("event-resource-upload-text");
                                    $(drag_drop_img).attr({src: "../images/event-resource-file.png"}).addClass("event-resource-upload-img");
                                    $(drag_drop_img_div).append(drag_drop_img).addClass("event-resource-upload-div");
                                    resource_step_container.append(drag_drop_img_div);
                                }
                                resource_step_container.append(drag_drop_p);
                                resource_step_container.append(upload_input_div);
                                
                                $("#event-resource-next").attr({disabled: "disabled"}).html("Save Resource");
                                $(".modal-body").addClass("upload");

                            break;
                        }
                    break;
                    case 2 :
                        
                        build_hidden_form_controls(selected_resource_type);
                        
                        /**
                         *
                         * Builds bring to class controls
                         *
                         */
                        
                        var resource_bring_description_heading = document.createElement("h3");
                        var resource_bring_description_control_group = document.createElement("div");
                        var resource_bring_description_controls = document.createElement("div");
                        var resource_bring_description_textarea = document.createElement("textarea");
                        
                        $(resource_bring_description_heading).html("Please provide a description of what students should bring to class");
                        $(resource_bring_description_textarea).attr({id: "event_resource_bring_description", name: "event_resource_bring_description", rows: 8}).addClass("input-xxlarge").val($("#event_resource_bring_description_value").val());
                        $(resource_bring_description_controls).append(resource_bring_description_textarea);
                        $(resource_bring_description_control_group).append(resource_bring_description_heading).append(resource_bring_description_controls).addClass("control-group");
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(resource_bring_description_control_group);
                    break;
                    case 3 :
                        
                        build_hidden_form_controls (selected_resource_type);
                        
                        /**
                         * 
                         * Builds proxy required controls
                         * 
                         */
                        
                        var selected_link_proxy_option = $("#event_resource_link_proxy_value").val();
                        
                        var link_proxy_heading = document.createElement("h3");
                        var link_proxy_control_group = document.createElement("div");
                        var link_proxy_controls = document.createElement("div");
                        var link_proxy_yes_label = document.createElement("label");
                        var link_proxy_yes_radio = document.createElement("input");
                        var link_proxy_no_label = document.createElement("label");
                        var link_proxy_no_radio = document.createElement("input");
                        
                        $(link_proxy_heading).html("Does this link require the proxy to be enabled?");
                        $(link_proxy_yes_label).attr({"for": "event_resource_link_proxy_yes"}).addClass("radio");
                        $(link_proxy_yes_radio).attr({type: "radio", id: "event_resource_link_proxy_yes", value: "yes", name: "event_resource_link_proxy"});
                        $(link_proxy_no_label).attr({"for": "event_resource_link_proxy_no"}).addClass("radio");
                        $(link_proxy_no_radio).attr({type: "radio", id: "event_resource_link_proxy_no", value: "no", name: "event_resource_link_proxy"});
                        $(link_proxy_no_label).append(link_proxy_no_radio).append("No, the proxy isn't required to be enabled");
                        $(link_proxy_yes_label).append(link_proxy_yes_radio).append("Yes, the proxy is required to be enabled");
                        $(link_proxy_controls).append(link_proxy_no_label).append(link_proxy_yes_label);
                        $(link_proxy_control_group).append(link_proxy_heading).append(link_proxy_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds link url controls
                         * 
                         */
                        
                        var link_url_heading = document.createElement("h3");
                        var link_url_control_group = document.createElement("div");
                        var link_url_controls = document.createElement("div");
                        var link_url_input = document.createElement("input");
                        
                        $(link_url_heading).html("Please provide the full URL of the link");
                        $(link_url_input).attr({type: "text", id: "event_resource_link_url", name: "event_resource_link_url"}).val($("#event_resource_link_url_value").val()).addClass("input-xlarge");
                        $(link_url_controls).append(link_url_input);
                        $(link_url_control_group).append(link_url_heading).append(link_url_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds link title controls 
                         * 
                         */
                        
                        var link_title_heading = document.createElement("h3");
                        var link_title_control_group = document.createElement("div");
                        var link_title_controls = document.createElement("div");
                        var link_title_input = document.createElement("input");
                        
                        $(link_title_heading).html("You can optionally provide a different title for this link");
                        $(link_title_input).attr({type: "text", id: "event-resource-link-title", name: "event_resource_link_title"}).addClass("input-xlarge").val($("#event_resource_link_title_value").val());
                        $(link_title_controls).append(link_title_input);
                        $(link_title_control_group).append(link_title_heading).append(link_title_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds link description controls
                         * 
                         */
                        
                        var link_description_heading = document.createElement("h3");
                        var link_description_control_group = document.createElement("div");
                        var link_description_controls = document.createElement("div");
                        var link_description_textarea = document.createElement("textarea");
                        
                        $(link_description_heading).html("Please provide a description for this link");
                        $(link_description_textarea).attr({name: "event_resource_link_description", id: "event-resource-link-description", rows: "4"}).addClass("input-xxlarge").val($("#event_resource_link_description_value").val());
                        $(link_description_controls).append(link_description_textarea);
                        $(link_description_control_group).append(link_description_heading).append(link_description_controls);
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(link_proxy_control_group).append(link_url_control_group).append(link_title_control_group).append(link_description_control_group);
                        $("#event_resource_link_proxy_" + selected_link_proxy_option).prop("checked", true);
                    break;
                    case 4 :
                        
                        build_hidden_form_controls (selected_resource_type);
                        
                        /**
                         * 
                         * Builds homework controls
                         * 
                         */
                        
                        var resource_homework_description_heading = document.createElement("h3");
                        var resource_homework_description_control_group = document.createElement("div");
                        var resource_homework_description_controls = document.createElement("div");
                        var resource_homework_description_textarea = document.createElement("textarea");
                        
                        $(resource_homework_description_heading).html("Please provide a Homework description");
                        $(resource_homework_description_textarea).attr({id: "event_resource_homework_description", name: "event_resource_homework_description", rows: 8}).addClass("input-xxlarge").val($("#event_resource_homework_description_value").val());
                        $(resource_homework_description_controls).append(resource_homework_description_textarea);
                        $(resource_homework_description_control_group).append(resource_homework_description_heading).append(resource_homework_description_controls).addClass("control-group");
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(resource_homework_description_control_group);
                    break;
                    case 7 :
                        
                        build_hidden_form_controls (selected_resource_type);
                        
                        /**
                         * 
                         * Builds proxy required controls
                         * 
                         */
                        
                        var selected_module_proxy_option = $("#event_resource_module_proxy_value").val();
                        
                        var module_proxy_heading = document.createElement("h3");
                        var module_proxy_control_group = document.createElement("div");
                        var module_proxy_controls = document.createElement("div");
                        var module_proxy_yes_label = document.createElement("label");
                        var module_proxy_yes_radio = document.createElement("input");
                        var module_proxy_no_label = document.createElement("label");
                        var module_proxy_no_radio = document.createElement("input");
                        
                        $(module_proxy_heading).html("Does this module require the proxy to be enabled?");
                        $(module_proxy_yes_label).attr({"for": "event_resource_module_proxy_yes"}).addClass("radio");
                        $(module_proxy_yes_radio).attr({type: "radio", id: "event_resource_module_proxy_yes", value: "yes", name: "event_resource_module_proxy"});
                        $(module_proxy_no_label).attr({"for": "event_resource_module_proxy_no"}).addClass("radio");
                        $(module_proxy_no_radio).attr({type: "radio", id: "event_resource_module_proxy_no", value: "no", name: "event_resource_module_proxy"});
                        $(module_proxy_no_label).append(module_proxy_no_radio).append("No, the proxy isn't required to be enabled");
                        $(module_proxy_yes_label).append(module_proxy_yes_radio).append("Yes, the proxy is required to be enabled");
                        $(module_proxy_controls).append(module_proxy_no_label).append(module_proxy_yes_label);
                        $(module_proxy_control_group).append(module_proxy_heading).append(module_proxy_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds link url controls
                         * 
                         */
                        
                        var module_url_heading = document.createElement("h3");
                        var module_url_control_group = document.createElement("div");
                        var module_url_controls = document.createElement("div");
                        var module_url_input = document.createElement("input");
                        
                        $(module_url_heading).html("Please provide the full URL of the module");
                        $(module_url_input).attr({type: "text", id: "event_resource_module_url", name: "event_resource_module_url"}).val($("#event_resource_module_url_value").val()).addClass("input-xlarge");
                        $(module_url_controls).append(module_url_input);
                        $(module_url_control_group).append(module_url_heading).append(module_url_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds link title controls 
                         * 
                         */
                        
                        var module_title_heading = document.createElement("h3");
                        var module_title_control_group = document.createElement("div");
                        var module_title_controls = document.createElement("div");
                        var module_title_input = document.createElement("input");
                        
                        $(module_title_heading).html("You can optionally provide a different title for this module");
                        $(module_title_input).attr({type: "text", id: "event-resource-module-title", name: "event_resource_module_title"}).addClass("input-xlarge").val($("#event_resource_module_title_value").val());
                        $(module_title_controls).append(module_title_input);
                        $(module_title_control_group).append(module_title_heading).append(module_title_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds module description controls
                         * 
                         */
                        
                        var module_description_heading = document.createElement("h3");
                        var module_description_control_group = document.createElement("div");
                        var module_description_controls = document.createElement("div");
                        var module_description_textarea = document.createElement("textarea");
                        
                        $(module_description_heading).html("Please provide a description for this module");
                        $(module_description_textarea).attr({name: "event_resource_module_description", id: "event-resource-module-description", rows: "4"}).addClass("input-xxlarge").val($("#event_resource_module_description_value").val());
                        $(module_description_controls).append(module_description_textarea);
                        $(module_description_control_group).append(module_description_heading).append(module_description_controls);
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(module_proxy_control_group).append(module_url_control_group).append(module_title_control_group).append(module_description_control_group);
                        $("#event_resource_module_proxy_" + selected_module_proxy_option).prop("checked", true);
                    break;
                    case 10 :
                        build_hidden_form_controls(selected_resource_type);
                        
                        /**
                         * 
                         * Builds link title controls 
                         * 
                         */
                        
                        var lti_title_heading = document.createElement("h3");
                        var lti_title_control_group = document.createElement("div");
                        var lti_title_controls = document.createElement("div");
                        var lti_title_input = document.createElement("input");
                        
                        $(lti_title_heading).html("Please provide a title for this LTI Provider");
                        $(lti_title_input).attr({type: "text", id: "event-resource-lti-title", name: "event_resource_lti_title"}).addClass("input-xlarge").val($("#event_resource_lti_title_value").val());
                        $(lti_title_controls).append(lti_title_input);
                        $(lti_title_control_group).append(lti_title_heading).append(lti_title_controls).addClass("control-group");
                        
                        
                        /**
                         * 
                         * Builds LTI description controls
                         * 
                         */
                        
                        var lti_description_heading = document.createElement("h3");
                        var lti_description_control_group = document.createElement("div");
                        var lti_description_controls = document.createElement("div");
                        var lti_description_textarea = document.createElement("textarea");
                        
                        $(lti_description_heading).html("You must provide a description for this LTI Provider");
                        $(lti_description_textarea).attr({name: "event_resource_lti_description", id: "event-resource-lti-description", rows: "4"}).addClass("input-xxlarge").val($("#event_resource_lti_description_value").val());
                        $(lti_description_controls).append(lti_description_textarea);
                        $(lti_description_control_group).append(lti_description_heading).append(lti_description_controls);
                        
                        /**
                         * 
                         * Builds lti url controls
                         * 
                         */
                        
                        var lti_url_heading = document.createElement("h3");
                        var lti_url_control_group = document.createElement("div");
                        var lti_url_controls = document.createElement("div");
                        var lti_url_input = document.createElement("input");
                        
                        $(lti_url_heading).html("Please provide the full external LTI launch URL:");
                        $(lti_url_input).attr({type: "text", id: "event_resource_lti_url", name: "event_resource_lti_url"}).val($("#event_resource_lti_url_value").val()).addClass("input-xlarge");
                        $(lti_url_controls).append(lti_url_input);
                        $(lti_url_control_group).append(lti_url_heading).append(lti_url_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds lti key controls
                         * 
                         */
                        
                        var lti_key_heading = document.createElement("h3");
                        var lti_key_control_group = document.createElement("div");
                        var lti_key_controls = document.createElement("div");
                        var lti_key_input = document.createElement("input");
                        
                        $(lti_key_heading).html("Please provide the LTI Key / Username:");
                        $(lti_key_input).attr({type: "text", id: "event_resource_lti_key", name: "event_resource_lti_key"}).val($("#event_resource_lti_key_value").val()).addClass("input-xlarge");
                        $(lti_key_controls).append(lti_key_input);
                        $(lti_key_control_group).append(lti_key_heading).append(lti_key_controls).addClass("control-group");
                        
                        
                        /**
                         * 
                         * Builds lti secret controls
                         * 
                         */
                        
                        var lti_secret_heading = document.createElement("h3");
                        var lti_secret_control_group = document.createElement("div");
                        var lti_secret_controls = document.createElement("div");
                        var lti_secret_input = document.createElement("input");
                        
                        $(lti_secret_heading).html("Please provide the LTI Secret / Password:");
                        $(lti_secret_input).attr({type: "text", id: "event_resource_lti_secret", name: "event_resource_lti_secret"}).val($("#event_resource_lti_secret_value").val()).addClass("input-xlarge");
                        $(lti_secret_controls).append(lti_secret_input);
                        $(lti_secret_control_group).append(lti_secret_heading).append(lti_secret_controls).addClass("control-group");
                        
                        /**
                         * 
                         * Builds LTI description controls
                         * 
                         */
                        
                        var lti_parameters_heading = document.createElement("h3");
                        var lti_parameters_control_group = document.createElement("div");
                        var lti_parameters_controls = document.createElement("div");
                        var lti_parameters_textarea = document.createElement("textarea");
                        
                        $(lti_parameters_heading).html("Please provide a additional parameters for this LTI Provider");
                        $(lti_parameters_textarea).attr({name: "event_resource_lti_parameters", id: "event-resource-lti-description", rows: "4"}).addClass("input-xxlarge").val($("#event_resource_lti_parameters_value").val());
                        $(lti_parameters_controls).append(lti_parameters_textarea);
                        $(lti_parameters_control_group).append(lti_parameters_heading).append(lti_parameters_controls);
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(lti_title_control_group).append(lti_description_control_group).append(lti_url_control_group).append(lti_key_control_group).append(lti_secret_control_group).append(lti_parameters_control_group);
                    break;
                    case 8 :
                        
                        build_hidden_form_controls(selected_resource_type);
                        
                        switch (sub_step) {
                            case 1 :
                                
                                /**
                                 *
                                 * Gets and builds a list of quizzes associated with the current user
                                 *
                                 */
                                
                                var quiz_id = $("#event_resource_quiz_id_value").val();
                                
                                $.ajax({
                                    url: SITE_URL + "/admin/events?section=api-resource-wizard",
                                    data: "method=quizzes&quiz_id=" + quiz_id,
                                    type: 'GET',
                                    success: function (data) {
                                        var jsonResponse = JSON.parse(data);
                                        if (jsonResponse.status === "success") {
                                            var selected_quiz = $("#event_resource_quiz_id_value").val();
                                            var quiz_table = document.createElement("table");
                                            var quiz_table_heading = document.createElement("thead");
                                            var quiz_table_body = document.createElement("tbody");
                                            var quiz_table_heading_tr = document.createElement("tr");
                                            var quiz_table_radio_th = document.createElement("th");
                                            var quiz_table_title_th = document.createElement("th");

                                            $(quiz_table_title_th).html("Quiz Title");
                                            $(quiz_table_radio_th).attr({width: "5%"});
                                            $(quiz_table_heading_tr).append(quiz_table_radio_th).append(quiz_table_title_th);
                                            $(quiz_table_heading).append(quiz_table_heading_tr);
                                            $(quiz_table).append(quiz_table_heading);

                                            $.each(jsonResponse.data, function (key, quiz) {
                                                var quiz_tr = document.createElement("tr");
                                                var quiz_radio_td = document.createElement("td");
                                                var quiz_title_td = document.createElement("td");
                                                var quiz_title_label = document.createElement("label");
                                                var quiz_description_div = document.createElement("div");
                                                var quiz_radio = document.createElement("input");

                                                $(quiz_title_label).attr({"for": "quiz_id_" + quiz.quiz_id}).html(quiz.quiz_title);
                                                $(quiz_radio).attr({type: "radio", id: "quiz_id_" + quiz.quiz_id, name: "event_resource_quiz_id"}).val(quiz.quiz_id).attr({"data-title": quiz.quiz_title});
                                                $(quiz_description_div).html(quiz.quiz_description).addClass("content-small");
                                                $(quiz_radio_td).append(quiz_radio);
                                                $(quiz_title_td).append(quiz_title_label).append(quiz_description_div);
                                                $(quiz_tr).append(quiz_radio_td).append(quiz_title_td);
                                                $(quiz_table_body).append(quiz_tr);
                                            });

                                            $(quiz_table).append(quiz_table_body).addClass("table table-striped table-bordered");
                                            resource_step_container.append(quiz_table);
                                            $("#quiz_id_" + selected_quiz).prop("checked", true);
                                        } else {
                                            $("#event-resource-next").prop("disabled", true);
                                            $("#event-resource-previous").prop("disabled", true);
                                            
                                            var no_quiz_p = document.createElement("p");
                                            var create_quiz_div = document.createElement("div");
                                            var create_quiz_i = document.createElement("i");
                                            var create_quiz_a = document.createElement("a");
                                            
                                            $(create_quiz_i).addClass("icon-plus-sign icon-white");
                                            $(create_quiz_a).attr({href: SITE_URL + "/admin/quizzes?section=add"}).addClass("btn btn-large btn-success").append(create_quiz_i).append(" Create a new Quiz");
                                            $(no_quiz_p).html("You currently have no Quizzes to display, if you would like to create a Quiz, click the button below.").addClass("no-quizzes");
                                            $(create_quiz_div).addClass("add-quiz-container").append(create_quiz_a)
                                            resource_step_container.append(no_quiz_p).append(create_quiz_div);
                                        }
                                    },
                                    beforeSend: function () {
                                        $("#event-resource-next").attr("disabled", "disabled");
                                        $("#event-resource-previous").attr("disabled", "disabled");
                                        $("#event_resource_loading_msg").html("Loading a list of Quizzes");
                                        $("#event_resource_form").addClass("hide");
                                        $("#event_resource_loading").removeClass("hide");
                                    },
                                    complete: function () {
                                        $("#event_resource_loading").addClass("hide");
                                        $("#event_resource_form").removeClass("hide");
                                        $("#event-resource-next").removeAttr("disabled");
                                        $("#event-resource-previous").removeAttr("disabled");
                                        $("#event_resource_loading_msg").html("");
                                    }
                                });
                            break;
                            case 2 :
                                
                                /**
                                 *
                                 * Builds quiz title and description controls
                                 *
                                 */
                                
                                var attached_quiz_title_heading = document.createElement("h3");
                                var attached_quiz_title_control_group = document.createElement("div");
                                var attached_quiz_title_controls = document.createElement("div");
                                var attached_quiz_title_input = document.createElement("input");
                                
                                $(attached_quiz_title_heading).html("You can optionally provide a different title for this quiz");
                                $(attached_quiz_title_input).attr({type: "text", id: "event_resource_quiz_title", name: "event_resource_quiz_title"}).addClass("input-xlarge").val($("#event_resource_quiz_title").val());
                                $(attached_quiz_title_controls).append(attached_quiz_title_input);
                                $(attached_quiz_title_control_group).append(attached_quiz_title_heading).append(attached_quiz_title_controls).addClass("control-group");
                                
                                var attached_quiz_instrucion_heading = document.createElement("h3");
                                var attached_quiz_instrucion_control_group = document.createElement("div");
                                var attached_quiz_instrucion_controls = document.createElement("div");
                                var attached_quiz_instrucion_textarea = document.createElement("textarea");
                                var attached_quiz_instrucion_hint = document.createElement("div");
                                
                                $(attached_quiz_instrucion_heading).html("You can optionally provide more detailed instructions for this quiz");
                                $(attached_quiz_instrucion_textarea).attr({id: "event_resource_quiz_instructions", name: "event_resource_quiz_instructions", rows: 8}).addClass("input-xxlarge").val($("#event_resource_quiz_instructions").val());
                                $(attached_quiz_instrucion_controls).append(attached_quiz_instrucion_textarea);
                                $(attached_quiz_title_control_group).append(attached_quiz_instrucion_heading).append(attached_quiz_instrucion_controls).addClass("control-group");
                                $(attached_quiz_instrucion_hint).html("<strong>Hint</strong>: this information is visibile to the learners at the top of the quiz.").addClass("content-small");
                                
                                resource_step_container.append(attached_quiz_title_control_group).append(attached_quiz_instrucion_control_group).append(attached_quiz_instrucion_hint);
                                
                                $("#event_resource_quiz_title").val($("#event_resource_quiz_title_value").val());
                                $("#event_resource_quiz_instructions").val($("#event_resource_quiz_instructions_value").val());
                            break;
                            case 3 :
                                
                                /**
                                 *
                                 * Builds quiz attendance controls
                                 *
                                 */
                                
                                var selected_attendance_option = $("#event_resource_quiz_attendance_value").val();
                                var selected_shuffle_option = $("#event_resource_quiz_shuffled_value").val();
                                var selected_result_option = $("#event_resource_quiz_results_value").val();
                                
                                var attached_quiz_attendance_heading = document.createElement("h3");
                                var attached_quiz_attendance_control_group = document.createElement("div");
                                var attached_quiz_attendance_radio_yes = document.createElement("input");
                                var attached_quiz_attendance_label_yes = document.createElement("label");
                                var attached_quiz_attendance_radio_no = document.createElement("input");
                                var attached_quiz_attendance_label_no = document.createElement("label");
                                
                                $(attached_quiz_attendance_heading).html("Is attendance required for this quiz to be completed?");
                                $(attached_quiz_attendance_radio_yes).attr({type: "radio", id: "event_resource_quiz_attendance_yes", name: "event_resource_quiz_attendance"}).val("yes");
                                $(attached_quiz_attendance_label_yes).attr({"for": "event_resource_quiz_attendance_yes"}).append(attached_quiz_attendance_radio_yes).append("Required").addClass("radio");
                                $(attached_quiz_attendance_radio_no).attr({type: "radio", id: "event_resource_quiz_attendance_no", name: "event_resource_quiz_attendance"}).val("no");
                                $(attached_quiz_attendance_label_no).attr({"for": "event_resource_quiz_attendance_no"}).append(attached_quiz_attendance_radio_no).append("Not Required").addClass("radio");
                                $(attached_quiz_attendance_control_group).append(attached_quiz_attendance_heading).append(attached_quiz_attendance_label_no).append(attached_quiz_attendance_label_yes).addClass("control-group");
                                
                                /**
                                 *
                                 * Builds quiz shuffled controls
                                 *
                                 */
                                
                                var attached_quiz_shuffled_heading = document.createElement("h3");
                                var attached_quiz_shuffled_control_group = document.createElement("div");
                                var attached_quiz_shuffled_radio_yes = document.createElement("input");
                                var attached_quiz_shuffled_label_yes = document.createElement("label");
                                var attached_quiz_shuffled_radio_no = document.createElement("input");
                                var attached_quiz_shuffled_label_no = document.createElement("label");
                                
                                $(attached_quiz_shuffled_heading).html("Should the order of the questions be shuffled for this quiz?");
                                $(attached_quiz_shuffled_radio_yes).attr({type: "radio", id: "event_resource_quiz_shuffled_radio_yes", name: "event_resource_quiz_shuffled"}).val("yes");
                                $(attached_quiz_shuffled_label_yes).attr({"for": "event_resource_quiz_shuffled_radio_yes"}).addClass("radio").html("Shuffled");
                                $(attached_quiz_shuffled_radio_no).attr({type: "radio", id: "event_resource_quiz_shuffled_radio_no", name: "event_resource_quiz_shuffled"}).val("no");
                                $(attached_quiz_shuffled_label_no).attr({"for": "event_resource_quiz_shuffled_radio_no"}).addClass("radio").html("Not Shuffled");
                                $(attached_quiz_shuffled_label_yes).append(attached_quiz_shuffled_radio_yes);
                                $(attached_quiz_shuffled_label_no).append(attached_quiz_shuffled_radio_no);
                                $(attached_quiz_shuffled_control_group).append(attached_quiz_shuffled_heading).append(attached_quiz_shuffled_label_no).append(attached_quiz_shuffled_label_yes).addClass("control-group");
                                
                                /**
                                 *
                                 * Builds quiz time controls
                                 *
                                 */
                                
                                var attached_quiz_time_heading = document.createElement("h3");
                                var attached_quiz_time_control_group = document.createElement("div");
                                var attached_quiz_time_controls = document.createElement("div");
                                var attached_quiz_time_hint_div = document.createElement("div");
                                var attached_quiz_time_input = document.createElement("input");
                                
                                $(attached_quiz_time_heading).html("How much time (in minutes) can the learner spend taking this quiz?");
                                $(attached_quiz_time_input).attr({type: "text", id: "event_resource_quiz_time", name: "event_resource_quiz_time"}).addClass("input-mini").val($("#event_resource_quiz_time_value").val());
                                $(attached_quiz_time_hint_div).html("<strong>Hint</strong>: enter 0 to allow unlimited time").addClass("content-small");
                                $(attached_quiz_time_controls).append(attached_quiz_time_input).append(attached_quiz_time_hint_div);
                                $(attached_quiz_time_control_group).append(attached_quiz_time_heading).append(attached_quiz_time_controls);
                                
                                /**
                                 *
                                 * Builds quiz attemps controls
                                 *
                                 */
                                
                                var attached_quiz_attempts_heading = document.createElement("h3");
                                var attached_quiz_attempts_control_group = document.createElement("div");
                                var attached_quiz_attempts_controls = document.createElement("div");
                                var attached_quiz_attempts_hint_div = document.createElement("div");
                                var attached_quiz_attempts_input = document.createElement("input");
                                
                                $(attached_quiz_attempts_heading).html("How many attempts can a learner take at completing this quiz?");
                                $(attached_quiz_attempts_input).attr({type: "text", id: "event_resource_quiz_attempts", name: "event_resource_quiz_attempts"}).addClass("input-mini").val($("#event_resource_quiz_attempts_value").val());
                                $(attached_quiz_attempts_hint_div).html("<strong>Hint</strong>: enter 0 to allow <strong>unlimited</strong> attempts").addClass("content-small");
                                $(attached_quiz_attempts_controls).append(attached_quiz_attempts_input).append(attached_quiz_attempts_hint_div);
                                $(attached_quiz_attempts_control_group).append(attached_quiz_attempts_heading).append(attached_quiz_attempts_controls);
                                
                                /**
                                 *
                                 * Builds quiz result controls
                                 *
                                 */
                                
                                var attached_quiz_results_heading = document.createElement("h3");
                                var attached_quiz_results_immediate_control_group = document.createElement("div");
                                var attached_quiz_results_immediate_radio = document.createElement("input");
                                var attached_quiz_results_immediate_label = document.createElement("label");
                                var attached_quiz_results_immediate_div = document.createElement("div");
                                
                                $(attached_quiz_results_heading).html("When should learners be allowed to view the results of the quiz?");
                                $(attached_quiz_results_immediate_radio).attr({type: "radio", id: "event_resource_quiz_results_immediate", name: "event_resource_quiz_results"}).val("immediate");
                                $(attached_quiz_results_immediate_label).attr({"for": "event_resource_quiz_results_immediate"}).append(attached_quiz_results_immediate_radio).append("Immediate Quiz Results").addClass("radio");
                                $(attached_quiz_results_immediate_div).html("This option will allow the learner to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback) immediately after they complete the quiz.").addClass("content-small");
                                $(attached_quiz_results_immediate_control_group).append(attached_quiz_results_heading).append(attached_quiz_results_immediate_label).append(attached_quiz_results_immediate_div).addClass("control-group");
                                
                                var attached_quiz_results_delayed_control_group = document.createElement("div");
                                var attached_quiz_results_delayed_radio = document.createElement("input");
                                var attached_quiz_results_delayed_label = document.createElement("label");
                                var attached_quiz_results_delayed_div = document.createElement("div");
                                
                                $(attached_quiz_results_delayed_radio).attr({type: "radio", id: "event_resource_quiz_results_delayed", name: "event_resource_quiz_results"}).val("delayed");
                                $(attached_quiz_results_delayed_label).attr({"for": "event_resource_quiz_results_delayed"}).append(attached_quiz_results_delayed_radio).append("Delayed Quiz Results").addClass("radio");
                                $(attached_quiz_results_delayed_div).html("This option restricts the learners ability to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback) until after the time release period has expired.").addClass("content-small");
                                $(attached_quiz_results_delayed_control_group).append(attached_quiz_results_delayed_label).append(attached_quiz_results_delayed_div).addClass("control-group");
                                
                                var attached_quiz_results_hide_control_group = document.createElement("div");
                                var attached_quiz_results_hide_label = document.createElement("label");
                                var attached_quiz_results_hide_div = document.createElement("div");
                                var attached_quiz_results_hide_radio = document.createElement("input");
                                
                                $(attached_quiz_results_hide_radio).attr({type: "radio", id: "event_resource_quiz_results_hide", name: "event_resource_quiz_results"}).val("hide");
                                $(attached_quiz_results_hide_label).attr({"for": "event_resource_quiz_results_hide"}).append(attached_quiz_results_hide_radio).append("Hide Quiz Results").addClass("radio");
                                $(attached_quiz_results_hide_div).html("This option restricts the learners ability to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback), and requires either manual release of the results to the students, or use of a Gradebook Assessment to release the resulting score.").addClass("content-small");
                                $(attached_quiz_results_hide_control_group).append(attached_quiz_results_hide_label).append(attached_quiz_results_hide_div).addClass("control-group");
                                
                                resource_step_container.append(attached_quiz_attendance_control_group).append(attached_quiz_shuffled_control_group).append(attached_quiz_time_control_group).append(attached_quiz_attempts_control_group).append(attached_quiz_results_immediate_control_group).append(attached_quiz_results_delayed_control_group).append(attached_quiz_results_hide_control_group);
                                
                                $("#event-resource-next").html("Save Resource");
                                $("#event_resource_quiz_attendance_" + selected_attendance_option).prop("checked", true);
                                $("#event_resource_quiz_shuffled_radio_" + selected_shuffle_option).prop("checked", true);
                                $("#event_resource_quiz_results_" + selected_result_option).prop("checked", true);
                            break;
                        }
                    break;
                    case 9 :
                        
                        build_hidden_form_controls (selected_resource_type);
                        
                        /**
                         * 
                         * Builds textbook reading controls
                         * 
                         */
                        
                        var resource_textbook_description_heading = document.createElement("h3");
                        var resource_textbook_description_control_group = document.createElement("div");
                        var resource_textbook_description_controls = document.createElement("div");
                        var resource_textbook_description_textarea = document.createElement("textarea");
                        
                        $(resource_textbook_description_heading).html("Please provide Textbook Reading Details");
                        $(resource_textbook_description_textarea).attr({id: "event_resource_textbook_description", name: "event_resource_textbook_description", rows: 8}).addClass("input-xxlarge").val($("#event_resource_textbook_description_value").val());
                        $(resource_textbook_description_controls).append(resource_textbook_description_textarea);
                        $(resource_textbook_description_control_group).append(resource_textbook_description_controls).addClass("control-group");
                        
                        $("#event-resource-next").html("Save Resource");
                        resource_step_container.append(resource_textbook_description_heading).append(resource_textbook_description_control_group);
                    break;
                    
                }
            break;
            case 6 :
                get_event_resources ();
                $(".modal-body").removeClass("upload");
                $("#event_resource_entity_id").val("");
                var resource_type = "";
                
                switch (selected_resource_type) {
                    case 1 :
                        resource_type = "Podcast";
                    break;
                    case 5 :
                        resource_type = "Lecture Note";
                    break;
                    case 6 :
                        resource_type = "Lecture Slide";
                    break;
                    case 11 :
                        resource_type = "File";
                    break;
                    case 2 :
                        resource_type = "Class Work";
                    break;
                    case 3 :
                        resource_type = "External Link";
                    break;
                    case 4 :
                        resource_type = "Homework";
                    break;
                    case 7 :
                        resource_type = "Online Learning Module";
                    break;
                    case 8 :
                        resource_type = "Quiz";
                    break;
                    case 9 :
                        resource_type = "Textbook Reading";
                    break;
                    case 10 :
                        resource_type = "LTI Provider";
                    break;
                }
                
                var success_p = document.createElement("p");
                var success_text_p = document.createElement("p");
                
                $(success_p).html((edit_mode ? "Successfully updated the selected <strong>" + resource_type + "</strong>" : "Successfully attached a <strong>" + resource_type + "</strong> Resource to this event.")).attr({id: "event-resource-success-msg"});
                $(success_text_p).html("You may continue to add resources to this event by clicking the <strong>Attach another Resource</strong> button, or you may close this dialog by clicking the <strong>Close</strong> button.").attr({id: "event-resource-success-text"});
                resource_step_container.append(success_p).append(success_text_p);
                
                $("#event-resource-next").html("Attach another Resource");
                $("#event-resource-previous").addClass("hide");
                
            break;
        }
    }
    
    function get_event_resources () {
        var event_id = $("#event_id").val();
        $(".resource-list").remove();
        
        $.ajax({
            url: SITE_URL + "/admin/events?section=api-resource-wizard",
            data: "method=event_resources&event_id=" + event_id,
            type: 'GET',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    build_event_resource_list(jsonResponse.data);
                } else {
                    $("#event-resources-msgs").append(jsonResponse.data);
                }
            },
            beforeSend: function () {
                $("#event-resources-container-loading").removeClass("hide");
                $("#event-resources-container").addClass("hide");
            },
            complete: function () {
                $("#event-resources-container-loading").addClass("hide");
                $("#event-resources-container").removeClass("hide");
            }
        });
    }
    
    function build_event_resource_list (resources) {
        $('#event-resources-msgs').empty();
        
        jQuery.each(resources, function (key, resources) {
            var timeframe = key;

            var heading = '';
            
            switch (timeframe) {
                case 'pre' :
                    heading = 'Before Class';
                break;
                case 'during' :
                    heading = "During Class";
                break;
                case 'post' :
                    heading = "After Class";
                break;
                case 'none' :
                    heading = "No Timeframe";
                break;
            }

            if (resources && resources.length) {
                var timeframe_ul = document.createElement("ul");
                var timeframe_resource_div = document.createElement("div");
                var timeframe_container_div = document.createElement("div");
                var timeframe_heading_p = document.createElement("p");

                $(timeframe_ul).attr({id: "timeframe-" + timeframe}).addClass("timeframe");
                $(timeframe_resource_div).attr({id: "event-resource-timeframe-" + timeframe + "-container"}).addClass("resource-list");
                $(timeframe_container_div).addClass("resource-container-" + timeframe);
                $(timeframe_heading_p).addClass("timeframe-heading").html(heading);

                jQuery.each(resources, function (key, resource) {
                    var resource_li = document.createElement("li");
                    var resource_details_div = document.createElement("div");
                    var resource_stats_div = document.createElement("div");
                    var resource_delete_span = document.createElement("span");
                    var resource_delete_i = document.createElement("i");
                    var resource_description_p = document.createElement("p");
                    var resource_required_span = document.createElement("span");
                    var resource_hidden_span    = document.createElement("span");
                    var resource_type_span = document.createElement("span");
                    var resource_time_p = document.createElement("p");
                    var resource_details_a = document.createElement("a");

                    var release_date = (resource.release_date != "" ? resource.release_date : "");
                    var release_until = (resource.release_until != "" ? resource.release_until : "");

                    $(resource_delete_i).addClass("icon-white icon-trash");
                    if (release_date && release_until) {
                        $(resource_time_p).html(release_date + " " + release_until).addClass("muted").addClass("event-resource-release-dates");
                    }

                    if (resource.delete != 0) {
                        $(resource_delete_span).addClass("btn btn-mini btn-danger pull-right delete-resource").append(resource_delete_i).attr({"data-id": resource.entity_id});
                    }

                    $(resource_description_p).html(resource.description).addClass("muted resource-description");

                    var required = "";
                    var styles = "";

                    if (resource.required == "1") {
                        required = "Required";
                        styles = "label label-important";
                    } else {
                        required = "Optional";
                        styles = "label label-default";
                    }

                    $(resource_required_span).html(required).addClass(styles).addClass("event-resource-stat-label");
                    $(resource_type_span).html(resource.resource_type_title).addClass("label label-info event-resource-stat-label");
                    $(resource_stats_div).append(resource_required_span);

                    var required = "";
                    var styles   = "";

                    if (resource.hidden == "1") {
                        required = "Hidden";
                        styles   = "label label-important";
                        $(resource_hidden_span).html(required).addClass(styles).addClass("event-resource-stat-label label-hidden");
                        $(resource_stats_div).append(resource_hidden_span);
                    }

                    $(resource_details_div).append(resource_delete_span).append(resource_description_p).attr({"data-id": resource.entity_id});
                    $(resource_stats_div).append(resource_required_span).append(resource_type_span);
                    $(resource_li).append(resource_details_div).append(resource_stats_div);
                    $(resource_details_a).html(resource.title);

                    switch (parseInt(resource.resource_type)) {
                        case 1 :
                        case 5 :
                        case 6 :
                        case 11 :
                            var resource_accesses_span = document.createElement("span");
                            var resource_accesses_i = document.createElement("i");
                            var download_span = document.createElement("span");
                            var download_i = document.createElement("span");
                            var download_a = document.createElement("a");

                            $(resource_type_span).html(resource.resource_type_title + " " + resource.file_size);
                            $(download_i).addClass("icon-download-alt");
                            $(download_a).attr({href: resource.url}).prepend(download_i);
                            $(download_span).append(download_a);
                            $(resource_accesses_i).addClass("icon-white icon-eye-open");
                            $(resource_accesses_span).attr({
                                "data-type": resource.resource_type,
                                "data-value": resource.resource_id,
                                "data-title": resource.title
                            }).addClass("label label-info event-resource-access-label resource-view-toggle").html(" " + " views").prepend(resource_accesses_i);
                            $(resource_details_a).html(resource.title);
                            $(resource_stats_div).append(resource_accesses_span).append(download_span);
                            break;
                        case 3 :
                        case 7 :
                            var resource_accesses_span = document.createElement("span");
                            var resource_accesses_i = document.createElement("i");
                            var link_span = document.createElement("span");
                            var link_i = document.createElement("span");
                            var link_a = document.createElement("a");

                            $(resource_type_span).html((resource.resource_type == "3" ? "Link" : "Online Learning Module")).addClass("label label-info event-resource-stat-label");

                            $(link_i).addClass("icon-globe");
                            $(link_a).attr({href: resource.url, target: "_BLANK"}).prepend(link_i);
                            $(link_span).append(link_a);
                            $(resource_accesses_i).addClass("icon-white icon-eye-open");
                            $(resource_accesses_span).attr({
                                "data-type": resource.resource_type,
                                "data-value": resource.resource_id,
                                "data-title": resource.title
                            }).addClass("label label-info event-resource-access-label resource-view-toggle").html(" " +  " views").prepend(resource_accesses_i);
                            $(resource_stats_div).append(resource_accesses_span).append(link_span);
                            break;
                        /*
                         case 7 :

                         var module_span = document.createElement("span");
                         var module_i = document.createElement("span");
                         var module_a = document.createElement("a");

                         $(module_i).addClass("icon-globe");
                         $(module_a).attr({href: resource.link, target: "_BLANK"}).prepend(module_i);
                         $(module_span).append(module_a);
                         $(resource_stats_div).append(module_span);
                         break;*/
                        case 8 :
                            var resource_link_quiz_accesses_span = document.createElement("span");
                            var edit_quiz_i = document.createElement("i");
                            var quiz_results_i = document.createElement("i");
                            var quiz_link = document.createElement("a");
                            var quiz_results_link = document.createElement("a");


                            $(quiz_results_i).addClass("fa fa-bar-chart");
                            $(resource_link_quiz_accesses_span).attr({
                                "data-type": resource.resource_type,
                                "data-value": resource.resource_id
                            }).addClass("label label-warning event-resource-access-label").html(" View Results").prepend(quiz_results_i);
                            $(quiz_results_link).attr({
                                href: SITE_URL + "/admin/quizzes?section=results&id=" + resource.resource_id,
                                title: "View quiz results"
                            }).append(resource_link_quiz_accesses_span);
                            $(edit_quiz_i).addClass("icon-pencil").attr({title: "Edit this quiz"});
                            $(quiz_link).attr({href: SITE_URL + "/admin/quizzes?section=edit&id=" + resource.quiz_id}).append(edit_quiz_i);
                            $(resource_details_a).html(resource.title);

                            $(resource_stats_div).append(quiz_results_link).append(quiz_link);
                            break;
                        case 12 :
                            var resource_accesses_span  = document.createElement("span");
                            var resource_accesses_i     = document.createElement("i");
                            var post_text               = resource.post_text;
                            var available               = resource.available;
                            var attempts_allowed        = resource.attempts_allowed;
                            var time_limit              = resource.time_limit;
                            var progress_count          = resource.progress_count;

                            if (available) {
                                $(resource_time_p).html(available).addClass("muted").addClass("event-resource-release-dates");
                            }

                            if (attempts_allowed) {
                                var resource_attempts_allowed_span = document.createElement("span");
                                var header = post_text.table_headers.attempts;

                                $(resource_attempts_allowed_span).html(header + ": " + attempts_allowed).addClass("label label-default").addClass("event-resource-stat-label");
                                $(resource_stats_div).append(resource_attempts_allowed_span);
                            }

                            if (time_limit) {
                                var resource_time_limit_span = document.createElement("span");
                                var header = post_text.table_headers.time_limit;

                                $(resource_time_limit_span).html(header + ": " + time_limit).addClass("label label-default").addClass("event-resource-stat-label");
                                $(resource_stats_div).append(resource_time_limit_span);
                            }

                            if (progress_count || progress_count == 0) {
                                $(resource_accesses_i).addClass("fa fa-bar-chart");
                                $(resource_accesses_span).attr({
                                    "data-type": resource.resource_type,
                                    "data-value": resource.resource_id,
                                    "data-title": resource.title
                                }).addClass("label label-info event-resource-access-label resource-view-toggle exam-post-stats").html(" " + progress_count + " started").prepend(resource_accesses_i);
                                $(resource_stats_div).append(resource_accesses_span);
                            }

                            break;
                    }

                    if (resource.resource_type != 12) {
                        $(resource_details_a).attr({href: "#"}).addClass("resource-link");
                    }

                    $(resource_details_div).prepend(resource_time_p).prepend(resource_details_a);
                    $(timeframe_ul).append(resource_li);

                });

                $(timeframe_container_div).append(timeframe_heading_p).append(timeframe_ul);
                $(timeframe_resource_div).append(timeframe_container_div);
                $("#event-resources-container").append(timeframe_resource_div);
            }
        });
    }
    
    function build_hidden_form_controls (selected_resource_type) {
        
        switch (parseInt(selected_resource_type)) {
            case 1 :
            case 5 :
            case 6 :
            case 11 :
                
                /**
                *
                * Builds the hidden inputs for the file controls
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_file_view_value = document.createElement("input");
                   var event_resource_file_title_value = document.createElement("input");
                   var event_resource_file_description_value = document.createElement("input");

                   $(event_resource_file_view_value).attr({type: "hidden", id: "event_resource_file_view_value",  name: "event_resource_file_view_value"}).val("download").addClass("resource_type_control");
                   $(event_resource_file_title_value).attr({type: "hidden", id: "event_resource_file_title_value", name: "event_resource_file_title_value"}).val("").addClass("resource_type_control");
                   $(event_resource_file_description_value).attr({type: "hidden", id: "event_resource_file_description_value", name: "event_resource_file_description_value"}).val("").addClass("resource_type_control");

                   $("#event_resource_form").append(event_resource_file_view_value).append(event_resource_file_title_value).append(event_resource_file_description_value);
               }
            break;
            case 2 :
                
                /**
                *
                * Builds the hidden inputs for the bring to class controls
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_bring_description_value = document.createElement("input");
                   $(event_resource_bring_description_value).attr({type: "hidden", id: "event_resource_bring_description_value", name: "event_resource_bring_description_value"}).val("").addClass("resource_type_control");
                   $("#event_resource_form").append(event_resource_bring_description_value);
               }
            break;
            case 3 :
                
                /**
                *
                * Builds the hidden inputs for each link control
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_link_proxy_value = document.createElement("input");
                   var event_resource_link_url_value = document.createElement("input");
                   var event_resource_link_title_value = document.createElement("input");
                   var event_resource_link_description_value = document.createElement("input");

                   $(event_resource_link_proxy_value).attr({type: "hidden", id: "event_resource_link_proxy_value", name: "event_resource_link_proxy_value"}).val("no").addClass("resource_type_control");
                   $(event_resource_link_url_value).attr({type: "hidden", id: "event_resource_link_url_value", name: "event_resource_link_url_value"}).val("http://").addClass("resource_type_control");
                   $(event_resource_link_title_value).attr({type: "hidden", id: "event_resource_link_title_value", name: "event_resource_link_title_value"}).val("").addClass("resource_type_control");
                   $(event_resource_link_description_value).attr({type: "hidden", id: "event_resource_link_description_value", name: "event_resource_link_description_value"}).val("").addClass("resource_type_control");

                   $("#event_resource_form").append(event_resource_link_proxy_value).append(event_resource_link_url_value).append(event_resource_link_title_value).append(event_resource_link_description_value);
               }
            break;
            case 4 :
                /**
                *
                * Builds the hidden inputs for the bring to class controls
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_homework_description_value = document.createElement("input");
                   $(event_resource_homework_description_value).attr({type: "hidden", id: "event_resource_homework_description_value", name: "event_resource_homework_description_value"}).val("").addClass("resource_type_control");
                   $("#event_resource_form").append(event_resource_homework_description_value);
               }
            break;
            case 7 :
                
                /**
                *
                * Builds the hidden inputs for each module control
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_module_proxy_value = document.createElement("input");
                   var event_resource_module_url_value = document.createElement("input");
                   var event_resource_module_title_value = document.createElement("input");
                   var event_resource_module_description_value = document.createElement("input");

                   $(event_resource_module_proxy_value).attr({type: "hidden", id: "event_resource_module_proxy_value", name: "event_resource_module_proxy_value"}).val("no").addClass("resource_type_control");
                   $(event_resource_module_url_value).attr({type: "hidden", id: "event_resource_module_url_value", name: "event_resource_module_url_value"}).val("http://").addClass("resource_type_control");
                   $(event_resource_module_title_value).attr({type: "hidden", id: "event_resource_module_title_value", name: "event_resource_module_title_value"}).val("").addClass("resource_type_control");
                   $(event_resource_module_description_value).attr({type: "hidden", id: "event_resource_module_description_value", name: "event_resource_module_description_value"}).val("").addClass("resource_type_control");

                   $("#event_resource_form").append(event_resource_module_proxy_value).append(event_resource_module_url_value).append(event_resource_module_title_value).append(event_resource_module_description_value);
               }
            break;
            case 8 :
                
                /**
                *
                * Builds the hidden inputs for the quiz controls
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_quiz_id_value = document.createElement("input");
                   var event_resource_quiz_title_value = document.createElement("input");
                   var event_resource_quiz_instructions_value = document.createElement("input");
                   var event_resource_quiz_attendance_value = document.createElement("input");
                   var event_resource_quiz_shuffled_value = document.createElement("input");
                   var event_resource_quiz_time_value = document.createElement("input");
                   var event_resource_quiz_attempts_value = document.createElement("input");
                   var event_resource_quiz_results_value = document.createElement("input");

                   $(event_resource_quiz_id_value).attr({type: "hidden", id: "event_resource_quiz_id_value", name: "event_resource_quiz_id_value"}).val("").addClass("resource_type_control");
                   $(event_resource_quiz_title_value).attr({type: "hidden", id: "event_resource_quiz_title_value", name: "event_resource_quiz_title_value"}).val("").addClass("resource_type_control");
                   $(event_resource_quiz_instructions_value).attr({type: "hidden", id: "event_resource_quiz_instructions_value", name: "event_resource_quiz_instructions_value"}).val("").addClass("resource_type_control");
                   $(event_resource_quiz_attendance_value).attr({type: "hidden", id: "event_resource_quiz_attendance_value", name: "event_resource_quiz_attendance_value"}).val("no").addClass("resource_type_control");
                   $(event_resource_quiz_shuffled_value).attr({type: "hidden", id: "event_resource_quiz_shuffled_value", name: "event_resource_quiz_shuffled_value"}).val("no").addClass("resource_type_control");
                   $(event_resource_quiz_time_value).attr({type: "hidden", id: "event_resource_quiz_time_value", name: "event_resource_quiz_time_value"}).val("0").addClass("resource_type_control");
                   $(event_resource_quiz_attempts_value).attr({type: "hidden", id: "event_resource_quiz_attempts_value", name: "event_resource_quiz_attempts_value"}).val("0").addClass("resource_type_control");
                   $(event_resource_quiz_results_value).attr({type: "hidden", id: "event_resource_quiz_results_value", name: "event_resource_quiz_results_value"}).val("immediate").addClass("resource_type_control");

                   $("#event_resource_form").append(event_resource_quiz_id_value).append(event_resource_quiz_title_value).append(event_resource_quiz_instructions_value).append(event_resource_quiz_attendance_value).append(event_resource_quiz_shuffled_value).append(event_resource_quiz_time_value).append(event_resource_quiz_attempts_value).append(event_resource_quiz_results_value);
               }
            break;
            case 9 :
                /**
                *
                * Builds the hidden inputs for the Textbook reading controls
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_textbook_description_value = document.createElement("input");
                   $(event_resource_textbook_description_value).attr({type: "hidden", id: "event_resource_textbook_description_value", name: "event_resource_textbook_description_value"}).val("").addClass("resource_type_control");
                   $("#event_resource_form").append(event_resource_textbook_description_value);
               }
            break;
            case 10 :
                /**
                *
                * Builds the hidden inputs for each link control
                *
                */

               if (!$(".resource_type_control").length) {
                   var event_resource_lti_title_value = document.createElement("input");
                   var event_resource_lti_description_value = document.createElement("input");
                   var event_resource_lti_url_value = document.createElement("input");
                   var event_resource_lti_key_value = document.createElement("input");
                   var event_resource_lti_secret_value = document.createElement("input");
                   var event_resource_lti_parameters_value = document.createElement("input");

                   $(event_resource_lti_title_value).attr({type: "hidden", id: "event_resource_lti_title_value", name: "event_resource_lti_title_value"}).val("").addClass("resource_type_control");
                   $(event_resource_lti_description_value).attr({type: "hidden", id: "event_resource_lti_description_value", name: "event_resource_lti_description_value"}).val("").addClass("resource_type_control");
                   $(event_resource_lti_url_value).attr({type: "hidden", id: "event_resource_lti_url_value", name: "event_resource_lti_url_value"}).val("http://").addClass("resource_type_control");
                   $(event_resource_lti_key_value).attr({type: "hidden", id: "event_resource_lti_key_value", name: "event_resource_lti_key_value"}).val("").addClass("resource_type_control");
                   $(event_resource_lti_secret_value).attr({type: "hidden", id: "event_resource_lti_secret_value", name: "event_resource_lti_secret_value"}).val("").addClass("resource_type_control");
                   $(event_resource_lti_parameters_value).attr({type: "hidden", id: "event_resource_lti_parameters_value", name: "event_resource_lti_parameters_value"}).val("").addClass("resource_type_control");

                   $("#event_resource_form").append(event_resource_lti_title_value).append(event_resource_lti_description_value).append(event_resource_lti_url_value).append(event_resource_lti_key_value).append(event_resource_lti_secret_value).append(event_resource_lti_parameters_value);
               }
            break;
        }
    }
    
    function upload_file (file) {  
        var selected_resource = parseInt(resource_type_value_control.val());
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        var valid_file_type = true;
        var file_size = file.size;

        switch (selected_resource) {
            case 1 :
                switch (file.type) {
                    case "audio/mpeg" :
                    case "video/mpeg" :
                    case "video/mp4" :
                    case "video/avi" :
                        valid_file_type = true;
                    break;

                }
            break;
            case 5 :
            case 6 :
            case 11 :
                switch (file.type) {
                    case "text/csv" :
                    case "application/zip" :
                    case "application/vnd.ms-write" :
                    case "application/vnd.ms-access" :
                    case "application/vnd.ms-project" :
                    case "application/msword" :
                    case "application/vnd.ms-powerpoint" :
                    case "application/vnd.ms-excel" :
                    case "application/vnd.openxmlformats-officedocument.wordprocessingml.document" :
                    case "application/vnd.openxmlformats-officedocument.wordprocessingml.template" :
                    case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" :
                    case "application/vnd.openxmlformats-officedocument.spreadsheetml.template" :
                    case "application/vnd.openxmlformats-officedocument.presentationml.presentation" :
                    case "application/vnd.openxmlformats-officedocument.presentationml.slideshow" :
                    case "application/vnd.openxmlformats-officedocument.presentationml.template" :
                    case "application/vnd.openxmlformats-officedocument.presentationml.slide" :
                    case "application/onenote" :
                    case "application/vnd.apple.keynote" :
                    case "application/vnd.apple.numbers" :
                    case "application/vnd.apple.pages" :
                    case "application/pdf" :
                    case "image/jpeg" :
                    case "image/gif" :
                    case "image/png" :
                    case "text/plain" :
                    case "text/richtext" :
                        valid_file_type = true;
                    break;
                }
            break;
        }

        if (file_size <= 300000000) {
            $("#event-resource-msgs").empty();
            $("#event_resource_loading_msg").html("Uploading file, this may take a few moments.");
            $("#event_resource_form").addClass("hide");
            $("#event_resource_loading").removeClass("hide");
            
            fd.append("file", file);
            fd.append("method", "add");
            fd.append("event_id", $("#event_id").val());
            fd.append("resource_recurring_bool", resource_recurring_bool);
            fd.append("resource_recurring_event_ids", resource_recurring_event_ids);
            fd.append("event_resource_required_value", resource_required_value_control.val());
            fd.append("event_resource_timeframe_value", resource_timeframe_value_control.val());
            fd.append("event_resource_release_value", resource_release_value_control.val());
            fd.append("event_resource_release_start_value", resource_release_start_control.val());
            fd.append("event_resource_release_start_time_value", resource_release_start_time_control.val());
            fd.append("event_resource_release_finish_value", resource_release_finish_control.val());
            fd.append("event_resource_release_finish_time_value", resource_release_finish_time_control.val());
            fd.append("event_resource_file_view_value", $("#event_resource_file_view_value").val());
            fd.append("event_resource_file_title_value", $("#event_resource_file_title_value").val());
            fd.append("event_resource_file_description_value", $("#event_resource_file_description_value").val());
            fd.append("event_resource_attach_file", $("#event_resource_attach_file").val());
            fd.append("step", resource_step_control.val());
            fd.append("resource_substep", resource_substep_control.val());
            fd.append("event_resource_type_value", resource_type_value_control.val());
            fd.append("resource_id", event_resource_id_value.val());
            fd.append("event_resource_entity_id", $("#event_resource_entity_id").val());

            xhr.open('POST', SITE_URL + "/admin/events?section=api-resource-wizard", true);		
            xhr.send(fd);

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    $("#event_resource_loading").addClass("hide");
                    $("#event_resource_form").removeClass("hide");
                    $("#event_resource_loading_msg").html("");
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.status == "success") {
                        next_step_control.val(jsonResponse.data.next_step);
                        previous_step_control.val(jsonResponse.data.previous_step);
                        resource_substep_control.val(jsonResponse.data.sub_step);
                        next_step();
                    } else {
                        $("#event_resource_loading").addClass("hide");
                        $("#event_resource_form").removeClass("hide");
                        $("#event_resource_loading_msg").html("");
                        display_error(jsonResponse.data, "#event-resource-msgs", "append");
                    }
                } else {
                    $("#event_resource_loading").addClass("hide");
                    $("#event_resource_form").removeClass("hide");
                    $("#event_resource_loading_msg").html("");
                }
            }
        } else {
            display_error(["The file you are attempting to upload exceeds the maximum file size limit of 300MB, please select a file with a size of 300MB or less."], "#event-resource-msgs", "append");
        }
    }
});