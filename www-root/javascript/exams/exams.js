jQuery(document).ready(function ($) {
    jQuery(window).load(function() {
        jQuery("#page-loading").fadeOut("slow");
    });
    
    build_target_affix();

    var counter;

    /**
     *  Appends a timer to the sidebar navigation based on a total number of seconds
     *  @param minutes - the total number of minutes, defaults to 60
     *  @param warning_threshold - the number of minutes at which the timer fades from blue to yellow, defaults to 25
     *  @param danger_threshold - the number of minutes at which the timer fades from yellow to red, defaults to 10
     */
    function ui_timer_auto_save (message, minutes, warning_threshold, danger_threshold, doSomething) {
        var count = typeof minutes !== 'undefined' ? (minutes * 60) : 3600;
        var start_count = count;

        counter = setInterval(function () {
            count = count - 1;
            if (count == 0) {
                if (doSomething !== "undefined") {
                    doSomething();
                    jQuery(".last_saved_container").removeClass("hide");
                }
                count = start_count;
                counter;
                return;
            }
        }, 1000);
    }

    function show_last_saved (d) {
        $("#last_saved").html("<strong>"+d+"</strong>");
    }

    var url = $("#exam-exam").attr("action");
    var exam_data =   {
                                "step" : "2",
                                "exam_id" : $("#exam_id").val(),
                                "adistribution_id" : $("#adistribution_id").val(),
                                "aprogress_id" : $("#aprogress_id").val(),
                                "schedule_id" : $("#schedule_id").val(),
                                "target_record_id": $("#target_record_id").val()
                            };

    var timer_function = function() {
        var name;
        $("#exam-exam input[name^='question-']:checked").each(function (index) {
            name = this.name;
            exam_data[name] = $(this).val();
        });
        $("#exam-exam input[name^='group-question-']:checked").each(function (index) {
            name = this.name;
            exam_data[name] = $(this).val();
        });
        $("#exam-exam textarea[name^='question-']").each(function (index) {
            name = this.name;
            exam_data[name] = $(this).val();
        });
        $("#exam-exam select[name^='question-']").each(function (index) {
            name = this.name;
            exam_data[name] = $(this).val();
        });
        $.post(url, exam_data, function(data) {
            //ToDo: error handling
            var aprogress_id = $(data).find("#aprogress_id").val();
            $("#aprogress_id").val(aprogress_id);

            show_last_saved($(data).find("#last_saved_time").val());

            $("#change_target_link").removeClass("hide");
        },
        "html"
        );
    };

    /**
     * Trigger the auto save function
     */
    var $initial_timer = 0;
    if ($("#target_record_id").val() != 0) {
        $("div.exam-question .group-response-no-text, div.exam-question tr.horizontal-response-input td, div.exam-question td.vertical-response-input").one("click", function(e) {
            if ($("#aprogress_id").val() == "0" && $initial_timer == 0) {
                $initial_timer = 1;
                ui_timer_auto_save("Exam Auto-Saved: ", 0.1, 0, 0, timer_function);
            }
        });

        $("#exam-exam input[name^='group-question-']").one("click", function (e) {
            if ($("#aprogress_id").val() == "0" && $initial_timer == 0) {
                $initial_timer = 1;
                ui_timer_auto_save("Exam Auto-Saved: ", 0.1, 0, 0, timer_function);
            }
        });
        $("#exam-exam textarea[name^='question-']").one("keyup", function (e) {
            if ($("#aprogress_id").val() == "0" && $initial_timer == 0) {
                $initial_timer = 1;
                ui_timer_auto_save("Exam Auto-Saved: ", 0.1, 0, 0, timer_function);
            }
        });
        $("#exam-exam select[name^='question-']").one("change", function (e) {
            if ($("#aprogress_id").val() == "0" && $initial_timer == 0) {
                $initial_timer = 1;
                ui_timer_auto_save("Exam Auto-Saved: ", 0.1, 0, 0, timer_function);
            }
        });
    }

    if ($("#aprogress_id").val() != 0 && $("#step_check").data("step") == 1 && $("#save-exam").length != 0 && $("#submit_exam").length != 0) {
        ui_timer_auto_save("Exam Auto-Saved: ", 0.1, 0, 0, timer_function);
    }

    if ($("#aprogress_id").val() != 0) {
        $("#change_target_link").removeClass("hide");
    } else {
        $("#change_target_link").addClass("hide");
    }

    if ($("#target_record_id").val() == "0") {
        $("#exam-exam input").attr("disabled", true);
        $("#exam-exam select").attr("disabled", true);
        $("#exam-exam textarea").attr("disabled", true);
    }

    $('#change_target_modal').on('shown', function () {
        clearTimeout(counter);
    });

    $('#change_target_modal').on('hide', function (e) {

    });

    $("#change_target_next_step").on("click", function() {
        $("#modal_msgs").html("");

        var step = parseInt($("#change_target_step").val());
        step++;

        var errors = [];

        //process
        switch(step) {
            case 3:
                $("#change_target_modal").modal("hide");
                break;

            case 2:
                if ($("#change_target_id").val() == 0) {
                   errors.push("Please select a target.");
                   step--;
                }

                if (errors.length == 0) {
                    new_target_record_id = $("#change_target_id").val();
                    var change_target_data =   {
                        "step" : step,
                        "method": "change-target",
                        "aprogress_id": $("#aprogress_id").val(),
                        "new_target_record_id": new_target_record_id
                    };

                    $.ajax(
                        {
                            url: "?section=api-change-target",
                            data: change_target_data,
                            type: "POST",
                            beforeSend: function () {
                                show_loading_msg();
                            },
                            success: function(data) {
                                hide_loading_msg();
                                $("#target_record_id").val(new_target_record_id)
                                if (data.status == "success") {
                                    display_success(data.msg, "#modal_msgs");
                                } else if (data.status == "error") {
                                    display_error(data.msg, "#modal_msgs");
                                }
                            },
                            dataType: "json"
                        }
                    );
                }
                break;

            case 1:


                break;
        }

        //display
        switch(step) {
            case 2:
                var exam_id = $("#exam_id").val();
                var adistribution_id = $("#adistribution_id").val();
                var aprogress_id = $("#aprogress_id").val();
                var schedule_id = $("#schedule_id").val();

                var finish_link = "<a class=\"btn btn-primary\" href=\""+ENTRADA_URL+"/exams/exam?adistribution_id="+adistribution_id+"&schedule_id="+schedule_id+"&exam_id="+exam_id+"&target_record_id="+new_target_record_id+"\">Finish</a>";
                $("#change_target_next_step").replaceWith(finish_link);
                $("#change_target_previous_step").addClass("hide");
                $("#change_target_close_modal").addClass("hide");
                break;

            case 1:
                $("#change_target_previous_step").addClass("hide");

                break;
        }

        if (errors.length == 0) {
            $("#change_target_wizard_step_"+(step-1)).addClass("hide");
            $("#change_target_step").val(step);
            $("#change_target_wizard_step_"+step).removeClass("hide");
        } else {
            display_error(errors, "#modal_msgs");
        }
    });

    $("#change_target_previous_step").on("click", function() {
        var step = parseInt($("#change_target_step").val());

        $("#change_target_wizard_step_"+step).addClass("hide");
        step--;
        $("#change_target_step").val(step);
        $("#change_target_wizard_step_"+step).removeClass("hide");

        if (step > 1) {
            $("#change_target_previous_step").removeClass("hide");
        } else {
            $("#change_target_previous_step").addClass("hide");
        }
    })

    function show_loading_msg () {
        disable_wizard_controls();
        $("#change-target-loading-msg").html("Change the target of this exam...");
        $("#exam-change-target-exam").addClass("hide");
        $("#change-target-loading").removeClass("hide");
    }

    function hide_loading_msg () {
        enable_wizard_controls();
        $("#change-target-loading").addClass("hide");
        $("#change-target-loading-msg").html("");
        $("#exam-change-target-exam").removeClass("hide");
    }

    function enable_wizard_controls () {
        if ($("#change_target_next_step").is(":disabled")) {
            $("#change_target_next_step").removeAttr("disabled");
        }

        if ($("#change_target_previous_step").is(":disabled")) {
            $("#change_target_previous_step").removeAttr("disabled");
        }
    }

    function disable_wizard_controls () {
        if (!$("#change_target_next_step").is(":disabled")) {
            $("#change_target_next_step").attr("disabled", "disabled");
        }

        if (!$("#change_target_previous_step").is(":disabled")) {
            $("#change_target_previous_step").attr("disabled", "disabled");
        }
    }
    
    function build_target_affix () {
        var target = jQuery(".target-label").html();
        var panel_container = jQuery(document.createElement("div")).addClass("panel").attr({"data-spy": "affix", "data-offset": "310", id: "target-panel"});
        var panel_head = jQuery(document.createElement("div")).addClass("panel-head");
        var panel_head_heading = jQuery(document.createElement("h3")).html(current_target);
        var panel_body = jQuery(document.createElement("div")).addClass("clearfix panel-body");
        var panel_span = jQuery(document.createElement("span")).html("<strong>" + target + "</strong>");
        
        panel_head.append(panel_head_heading);
        panel_body.append(panel_span);
        panel_container.append(panel_head).append(panel_body);
        
        //var container = jQuery(document.createElement("div")).addClass("well").html("Assessing: " + target);
        $(".inner-sidebar").append(panel_container);
    }

});