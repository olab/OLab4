var inputs = {};
var saved  = {};
var responses = [];
var flagged = [];
var marked_for_faculty = {};
var learner_comments = {};
var striked = {};
var post_id;
var exam_id;
var exam_progress_id;
var proxy_id;
var current_page;

var side_bar_right_open = 0;
var flag_content_menu_open = 0;
var flag_id;
var clicked_flag;
var menu_open;

var highlight_time      = 0;
var save_highlights_bool = 0;

var use_time_limit;
var time_limit;
var use_self_timer;
var self_timer;
var self_timer_start;
var exam_timer_displayed;
var use_submission_date;
var submission_date;
var create_time;
var start_time;
var auto_submit;
var clock_loop;
var timer_loop;
var auto_submit_msgs;
var allow_feedback;

var text_save = "";
var text_saved = "";

jQuery(document).ready(function ($) {
    var secure_mode =  ($("body").is("#secure")) ? true : false;
    var warning_5_min_displayed = getCookie("exam_5min_" + exam_progress_id);
    var warning_1_min_displayed = getCookie("exam_1min_" + exam_progress_id);
    var displayed = getCookie("exam_dismiss_attempt_message_" + exam_progress_id);
    exam_timer_displayed = getCookie("exam_timer_" + exam_progress_id);

    //loads the custom right sidebar nav if it was opened on a previous page
    if (menu_open === 1) {
        set_exam_menu_init();
        adjust_exam_height();
        adjust_exam_top();
        adjust_main_window();
        scrollNavMenu();
    } else {
        adjust_main_window();
    }

    startClock();
    startTimer();

    //Prevent user from clicking access_false links
    $("body").on("click", "a.access_false", function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    
    $("input#instructions-start-exam").click(function(e){
        e.preventDefault();
       instructionsViewed();
    });

    $("#pdf_viewer").css({
        width: "500px",
        height: "400px"
    });

    $("#pdf_viewer").draggable({
        handle: "#pdf_handle"
    });

    $("#pdf_viewer").resizable();

    $(".exam_file_link").on("click", function(e) {
        e.preventDefault();

        $("#pdf_viewer").addClass("show").removeClass("hide");
        $("#pdf_viewer_fixed").addClass("active");

        var file_id = $(this).data("file-id");
        var path = ENTRADA_URL + "/view-pdf.php?id=" + file_id;
        $("#iframe_path").attr("src", path);
    });

    $("#pdf_close").on("click", function(e) {
        e.preventDefault();

        $("#pdf_viewer").addClass("hide").removeClass("show");
        $("#pdf_viewer_fixed").removeClass("active");
    });

    $("a.exit_exam[data-type=\"un_secure\"]").on("click", function (e) {
        e.preventDefault();
        var url = ENTRADA_URL + "/exams" + (post_id != undefined ? "?section=post&id=" + post_id : "");
        saveExam("normal", function() { window.location = url; });
    });

    $("a#dismiss_attempt_message").on("click", function(e) {
        var clicked = $(this);
        var progress_id = $(clicked).data("progress-id");
        setCookie("exam_dismiss_attempt_message_" + exam_progress_id, 1, 1);
    });

    $(".question-control").on("change", function(e) {
        answerChange(this);
    });

    $(".learner_comments_text_area").on("change", function(e) {
        manage_learner_comment(this);
    });

    // Save when the user uses the navigation
    $("#control-bar .pagination a").on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();

        var url = $(this).prop("href");

        if (isThereAnythingToSave()) {
            saveExam("normal", function() { window.location = url; });
        } else {
            window.location = url;
        }
    });

    $(".save-exam").click(function(e) {
        saveExam("normal");
    });

    $(".submit-exam").click(function(e) {
        e.preventDefault();
        submitExam(e);
    });

    $("#menu-toggle-exam").click(function() {
        toggleSidebarMenu();
    });

    $("#confirmation-modal").on("show.bs.modal", function (e) {
        $(".submit-exam").prop("disabled", true);
        $("#missing-responses").removeClass("alert-error");
        $("#missing-responses").removeClass("alert-success");
        $("#missing-responses").addClass("alert-notice");
        $("#missing-responses").html("<p><i class=\"fa fa-spinner fa-pulse\"></i> Please wait while checking for missing answers.</p>");

        if (isThereAnythingToSave()) {
            saveExam("quiet");
            // submit-exam
            setTimeout(function() {
                checkAllSaved();
            }, 1000);
        } else {
            checkAllSaved();
        }
    });

    $(".exam-question").on("click", ".flag-question", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $(".exam-question").on("change", ".flag-question", function () {
        flag_question($(this));
    });

    $(".comment-question").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);
        open_comment(clicked);
    });

    $("#content").on("click", ".learner_comments_mark_faculty_review", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $(".learner_comments_mark_faculty_review").on("change", function () {
        selectQuestion($(this));
    });

    $("#exam-progress-menu .nav-list-header").on("click", function(e) {
        toggleNavMenuVisibility($(this));
    });

    $(".clock-toggle").on("click", function(e) {
        toggleClock();
    });

    $("#clock-container i").on("click", function(e) {
        toggleClock();
    });

    $("#timer-controls span").on("click", function(e) {
       toggleTimer();
    });

    $("#toggle-timer-controls").on("click", function(e) {
       toggleSelfTimerModal(e);
    });

    $("#use_self_timer").on("click", function(e) {
        if ($(this).prop("checked") == true) {
            $("#time_limit_hours").prop("disabled", false);
            $("#time_limit_mins").prop("disabled", false);
        } else {
            $("#time_limit_hours").prop("disabled", true);
            $("#time_limit_mins").prop("disabled", true);
        }
    });

    $("#update_self_timer").on("click", function(e) {
        e.preventDefault();

        var use_self_timer = 0;

        var time_limit_hours    = $("#time_limit_hours").val();
        var time_limit_mins     = $("#time_limit_mins").val();

        if ($("#use_self_timer").prop("checked") == true) {
            use_self_timer = 1;
        } else {
            time_limit_hours = 0;
            time_limit_mins = 0;
        }

        var data_object = {
            "method" : "save-self-timer",
            "use_self_timer" : use_self_timer,
            "time_limit_hours" : time_limit_hours,
            "time_limit_mins" : time_limit_mins,
            "exam_progress_id" : exam_progress_id,
            "proxy_id": proxy_id
        };

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: data_object,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var message = jsonResponse.data;
                if (jsonResponse.status == "success") {
                    var modal = $("#self-timer-modal");
                    $(modal).modal("hide");
                    self_timer              = jsonResponse.start_length;
                    self_timer_start        = jsonResponse.start_time;
                    exam_timer_displayed    = 0;

                    if (use_self_timer == 0) {
                        // clearInterval(timer_loop);
                        exam_timer_displayed = 1;
                        displayTimerTime(Date.now());
                    } else {
                        startTimer();
                    }
                    setCookie("exam_timer_" + exam_progress_id, exam_timer_displayed);

                } else if (jsonResponse.status == "warning") {
                    $.growl.warning({ title: "Warning", message: message });
                } else {
                    $.growl.error({ title: "Error", message: message });
                }
            }
        });
    });

    //fires when left clicking on the custom contextual menu that is loaded when right clicking on a flagged question in the sidebar.
    $("#contextMenuExam").on("click", ".un-flag-item", function(e) {
        e.preventDefault();
        click_unflag(flag_id);
    });

    //fires when left clicking on a side bar link to another question
    $("#exam-progress-menu").on("click", ".progress_menu_item", function (e) {
        side_bar_nav_clicked($(this));
    });

    //right click for strike out on MC questions
    $(document).on("contextmenu", ".exam-vertical-choice-question .question-answer-view", function(e) {
        right_click_answer(e);
        return false;
    });

    $(document).on("click", ".strikeout-choice", function(e) {
        right_click_answer(e);
        return false;
    });

    //removes custom contextual menu when clicking outside it
    $("body").click(function () {
        if (flag_content_menu_open === 1) {
            $("#contextMenuExam").css({"display" : "none"});
            flag_content_menu_open = 0;
        }
    });

    //loads the custom contextual menu when right clicking on flag question
    $("#exam-progress-menu").on("contextmenu", ".progress_menu_item .icon-flag-question", function(e) {
        right_click_flag_icon(e);
        return false;
    });

    $("span.summernote_text").summernote({
        popover: {
            air: [
                ["color", ["color"]],
                ["font", ["clear"]]
            ]
        },
        airMode: true,
        colors: [
            ["#FFFF00", "#00FF00", "#00FFFF"]
        ],
        disableDragAndDrop: 1,
        modules: $.extend($.summernote.options.modules, {"disableEditing": DisableEditing}),
        callbacks: {
            onChange: function(highlight_text) {
                var d = new Date();
                var now = d.getTime();
                var time_diff = now - highlight_time;

                /* Prevents duplicate change calls */
                if ((now > highlight_time && (time_diff >= 100)) || highlight_time === 0) {
                    save_highlights_bool = 1;
                } else {
                    save_highlights_bool = 0;
                }

                highlight_time = now;

                if (save_highlights_bool === 1) {
                    var element = this;
                    save_highlights(element, highlight_text);
                }
            }
        }
    });

    // highlight on hover rather than click, this gets around issues with clicking in Safari and IE
    $("body").on("hover", ".note-btn", function(e) {

        var button_highlight = $(this);

        if (timeoutHover) {
            clearTimeout(timeoutHover);
        }

        var timeoutHover = setTimeout(function() {
            $(button_highlight).trigger("click");
        }, 350);
    });

    /*
     * Disabled for now as it fires for drag as well as click
     * Clicks the input label when clicking the summernote text
     */
    // $(".note-editor").on("click", function (e) {
    //     var row = $(this).parents(".question-answer-view");
    //     var input_label = $(row).find("span.question-letter label");
    //     $(input_label).trigger("click");
    // });

    /**
     * Bug Fix for Chrome, since area isn't focused on first click
     */

    $("body").on("click", ".note-editable[contenteditable=\"true\"]", function(e) {
        $(this).focus();
    });

    $("body").on("click", ".note-editable a", function (e) {
        var href = $(this).attr("href");
        var target = $(this).attr("target");

        if (target == "_blank") {
            $("#link_modal_window").modal("show");

            var link = "<a href=\"" + href + "\" target=\"" + target +"\">\n";
            link += href + "\n";
            link += "</a>";
            $(".open_link_msg").html(link);
        } else {
            window.location = href;
        }
    });

    //when changing the browser window size update the sidebar and content window sizes and css
    $(window).resize(function() {
        if (side_bar_right_open == 1) {
            adjust_main_window();
            adjust_exam_height();
        }

        if (flag_content_menu_open === 1) {
            adjust_context_menu();
        }
    });

    //when scrolling the browser window size update the sidebar and content window sizes and css
    $(window).scroll(function() {
        if (side_bar_right_open == 1) {
            adjust_exam_top();
            adjust_exam_height();
        }

        if (flag_content_menu_open === 1) {
            adjust_context_menu();
        }
    });

    $("#exam-menu").scroll(function () {
        if (timeoutId) clearTimeout(timeoutId);
        var timeoutId = setTimeout(function() {
            var menu_position = $("#exam-progress-menu").position();
            setCookie("exam_menu_pos" + exam_progress_id, menu_position.top);
        }, 500);
    });

    /*
     Auto saving function runs based on how many seconds are entered for the post in the database.
     Defaults to 0 and isn't editable by a user in the interface.
     Auto save function is disabled if set to 0 for the post
     */
    if (AUTO_SAVE_TIMER > 0) {
        window.setInterval(function() {
            autoSaveExam();
        }, 1000 * AUTO_SAVE_TIMER);
    }

    function save_highlights(element, highlight_text) {
        var type = $(element).data("type");
        var element_id;
        var order;

        switch (type) {
            case "question_text":
                element_id  = $(element).data("version-id");
                break;
            case "element_text":
                element_id  = $(element).data("exam-element-id");
                break;
            case "answer_text":
            case "match_text":
            case "fnb_text":
                element_id  = $(element).data("version-id");
                order       = $(element).data("order");
                break;
        }

        var submission = {
            "method" : "save-highlight",
            "type" : type,
            "element_id" : element_id,
            "order" : order,
            "highlight_text" : highlight_text,
            "exam_progress_id" : exam_progress_id,
            "proxy_id": proxy_id
        };

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: submission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var message = jsonResponse.data;
                if (jsonResponse.status == "success") {
                    /* Hides success message on highlight
                     $.growl({ title: "Success", message: message });
                     */
                } else if (jsonResponse.status == "warning") {
                    $.growl.warning({ title: "Warning", message: message });
                } else {
                    $.growl.error({ title: "Error", message: message });
                }
            }
        });
    }

    function open_comment(clicked) {
        var icon = $(clicked).children("i");
        var version_id = $(icon).data("version-id");

        var learner_comment_window =  $(".learner_comments[data-version-id=" + version_id + "]");
        if (learner_comment_window.hasClass("active")) {
            $(learner_comment_window).hide().removeClass("active");
            $(clicked).removeClass("active");
        } else {
            $(learner_comment_window).show().addClass("active");
            $(clicked).addClass("active");
        }
    }

    function selectQuestion(clicked) {
        var icon            = jQuery(clicked).find("i");
        var span            = icon.closest("span");
        var element_id      = icon.data("element-id");
        var checked         = 1;

        if (span.hasClass("selected")) {
            checked = 0;
        }

        if (!marked_for_faculty[element_id]) {
            marked_for_faculty[element_id] = {element_id: element_id, checked: checked};
        } else {
            marked_for_faculty[element_id] = {element_id: element_id, checked: checked};
        }

        // Changes the HTML for the question checked
        changeSelectActiveHTML(span, icon);

        manageSaveButton(1);
    }

    function changeSelectActiveHTML(span, icon) {
        if (span.hasClass("selected")) {
            span.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("white-icon").removeClass("fa-check-square-o");
        } else {
            span.addClass("selected");
            icon.addClass("fa-check-square-o").addClass("white-icon").removeClass("fa-square-o");
        }
    }

    function manageInputs(element) {
        var element_id    = $(element).data("element-id");
        var question_type = $(element).data("type");

        //mc_m allow more than one answer, but unique to the answer choice id
        //default types only allow one answer
        switch (question_type) {
            case "mc_h_m" :
            case "mc_v_m":
            case "drop_m":
            case "fnb" :
                var qanswer_id = $(element).data("qanswer-id");

                if (!inputs[element_id]) {
                    var answer_object = {};
                    answer_object[qanswer_id] = $(element);

                    inputs[element_id] = answer_object;
                } else {
                    if (!inputs[element_id][qanswer_id]) {
                        inputs[element_id][qanswer_id] = $(element);
                    } else {
                        inputs[element_id][qanswer_id] = $(element);
                    }
                }
                break;
            case "match":
                var match_id = $(element).data("match-id");
                var select = $("select [data-match-id=\"" + match_id + "\"]:selected");

                //this allows for multiselect selects
                if (select.length === 1) {
                    var current_option = select[0];
                    var qanswer_id = $(current_option).data("qanswer-id");

                    if (!inputs[element_id]) {
                        var answer_object = {};
                        answer_object[match_id] = {}
                        answer_object[match_id][qanswer_id] = $(current_option);
                        inputs[element_id] = answer_object;
                    } else {
                        if (!inputs[element_id][match_id]) {
                            var answer_object = {};
                            answer_object[qanswer_id] = $(current_option);
                            inputs[element_id][match_id] = {}
                            inputs[element_id][match_id] = answer_object;
                        } else {
                            var answer_object = {};
                            answer_object[qanswer_id] = $(current_option);
                            inputs[element_id][match_id] = {}
                            inputs[element_id][match_id] = answer_object;
                        }
                    }
                } else {
                    //jQuery.each(select, function(key, value) {
                    //    //todo add method for multiselect
                    //});
                }
                break;
            default:
                if (!inputs[element_id]) {
                    inputs[element_id] = $(element);
                } else {
                    inputs[element_id] = $(element);
                }
                break;
        }
        $(".save-exam").click();
    }

    function answerChange(element) {
        manageInputs(element);
        saveExam("auto");
    }

    function manage_learner_comment(element) {
        manageSaveButton(1);

        var element_id = $(element).data("element-id");
        var comment    = $(element).val();

        var nav_link = $(".exam_nav_link[data-element-id=" + element_id + "]");

        update_nav_link(nav_link, element_id);

        learner_comments[element_id] = comment;
    }

    function mark_for_review(element_id) {
        manageSaveButton(1);

        var in_array = $.inArray(element_id, flagged);

        if (in_array < 0) {
            flagged.push(element_id);
        }
    }

    function flagQuestion(element_id) {
        manageSaveButton(1);

        var in_array = $.inArray(element_id, flagged);

        if (in_array < 0) {
            flagged.push(element_id);
        }
    }

    function autoSaveExam() {
        if (isThereAnythingToSave()) {
            saveExam("auto");
        }
    }

    function addResponse(type, value) {
        var exam_element_id = $(value).data("element-id");
        var response_value  = $(value).val();

        switch (type) {
            case "mc_h_m" :
            case "mc_v_m":
            case "mc_h":
            case "mc_v":
                var checked         = $(value).prop("checked");
                var exam_element_id = $(value).data("element-id");
                var qanswer_id      = $(value).data("qanswer-id");
                var order           = $(value).data("answer-order");
                var letter          = $(value).data("answer-letter");

                responses.push({
                    "exam_element_id"   : exam_element_id,
                    "qanswer_id"        : qanswer_id,
                    "response_value"    : checked,
                    "type"              : type,
                    "order"             : order,
                    "letter"            : letter
                });
                break;
            case "fnb":
                var qanswer_id         = $(value).data("qanswer-id");
                var order              = $(value).data("answer-order");

                responses.push({
                    "exam_element_id"   : exam_element_id,
                    "qanswer_id"        : qanswer_id,
                    "response_value"    : response_value,
                    "type"              : type,
                    "order"             : order
                });
                break;
            case "match":
                var match_id            = $(value).data("match-id");
                var match_order         = $(value).data("order");
                var qanswer_id          = $(value).data("qanswer-id");
                var order               = $(value).data("answer-order");
                var letter              = $(value).data("answer-letter");

                responses.push({
                    "exam_element_id"   : exam_element_id,
                    "qanswer_id"        : qanswer_id,
                    "match_id"          : match_id,
                    "response_value"    : response_value,
                    "type"              : type,
                    "order"             : order,
                    "match_order"       : match_order,
                    "letter"            : letter
                });
                break;
            case "short":
            case "essay":
                responses.push({
                    "exam_element_id": exam_element_id,
                    "response_value" : response_value,
                    "type"           : type
                });
                break;
            case "drop_m":
            case "drop_s":
                var qanswer_id         = $(value).data("qanswer-id");
                var order              = $(value).data("answer-order");

                responses.push({
                    "exam_element_id" : exam_element_id,
                    "qanswer_id"      : qanswer_id,
                    "response_value"  : response_value,
                    "type"            : type,
                    "order"           : order
                });
                break;
            default:
                break;
        }
    }

    function saveExam(mode, afterSaveCallback) {
        var elements         = [];
        var flagged_elements = [];
        responses = [];

        $.each(inputs, function(key, value) {
            //if the value is a jquery object then it's a single response question and not a multiple
            //we switch on type in the function so no need to do it here now.
            if (value instanceof jQuery) {
                elements.push($(value).data("element-id"));
                var type = $(value).data("type");
                addResponse(type, value);
            } else {
                $.each(value, function(sub_index, sub_value) {
                    if (sub_value instanceof jQuery) {
                        //mc_h_m and mc_v_m should all be in this level

                        //inArray returns the array position
                        var element_id = $(sub_value).data("element-id");
                        var in_elements = jQuery.inArray(element_id, elements);
                        if (in_elements == -1) {
                            elements.push(element_id);
                        }
                        var type = $(sub_value).data("type");
                        addResponse(type, sub_value);
                    } else {
                        //matching question probably
                        $.each(sub_value, function(qanswer_id, option) {
                            var option_chosen = option[0];
                            var type = $(option_chosen).data("type");
                            addResponse(type, option_chosen);
                        });
                    }
                });
            }
        });

        $.each(flagged, function(key, element_id) {
            var span = $(".question-control[data-element-id=" + element_id + "]").parents("table.question-table").find("span.flag-question");

            var is_flagged = $(span.hasClass("flagged"));
            if (is_flagged[0] === true) {
                var flagged_is_true = 1;
            } else {
                flagged_is_true = 0;
            }

            var flag_object = {
                "element_id" : element_id,
                "flagged"    : flagged_is_true
            };

            flagged_elements.push(flag_object)
        });

        var submission = {
            "method"            : "save-responses",
            "exam_progress_id"  : exam_progress_id,
            "post_id"           : post_id,
            "exam_id"           : exam_id,
            "proxy_id"          : proxy_id,
            "responses"         : responses,
            "elements"          : elements,
            "flagged_elements"  : flagged_elements,
            "marked_for_faculty": marked_for_faculty,
            "learner_comments"  : learner_comments,
            "striked"           : striked
        };

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: submission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var message      = jsonResponse.data;
                saved            = jsonResponse.saved;

                if (jsonResponse.status == "success") {
                    /*
                    if (mode === "normal") {
                        $.growl({ title: "Success", message: message });
                    } else if (mode === "auto") {
                        $.growl({ title: "Success", message: "Auto-saved: " + message });
                    }
                    */

                    $("#control-bar-progress").html(jsonResponse.bar);

                    flagged.length  = 0;

                    $.each(striked, function(key, value) {
                        delete striked[key];
                    });

                    /*
                     * Generates an object of saved elements for comparison against the inputs calculated
                     */
                    var saved_object = {};
                    $.each(saved, function(key, value) {
                        saved_object[value] = inputs[value];
                    });

                    $.each(learner_comments, function(key, value) {
                        delete learner_comments[key];
                    });

                    $.each(responses, function(key, value) {
                        var exam_element_id = value.exam_element_id;

                        // Only remove the item from inputs if it was saved, this way it will try to save it on next save.
                        if (exam_element_id in saved_object ) {
                            delete inputs[exam_element_id];
                            delete saved[exam_element_id];
                            var nav_link = $(".exam_nav_link[data-element-id=" + exam_element_id + "]");
                            update_nav_link(nav_link, exam_element_id);
                        }
                    });

                    if ($.isEmptyObject(inputs)) {
                        manageSaveButton(0);
                    } else {
                        manageSaveButton(1);
                    }
                } else if (jsonResponse.status == "warning") {
                    if (mode === "normal") {
                        $.growl.warning({title: "Warning", message: message});
                    } else if (mode === "auto") {
                        $.growl.warning({ title: "Warning", message: "Auto-save: " + message });
                    }
                } else {
                    if (mode === "normal") {
                        $.growl.error({title: "Error", message: message});
                    } else if (mode === "auto") {
                        $.growl.error({ title: "Error", message: "Auto-save: " + message });
                    }
                }
                
                if (typeof afterSaveCallback !== "undefined") {
                    afterSaveCallback();
                }
            }
        });
    }

    function submitExam() {
        var submission = {"method" : "submit-exam", "exam_progress_id" : exam_progress_id, "proxy_id": proxy_id};
        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: submission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var message = jsonResponse.data;
                if (jsonResponse.status == "success") {
                    $.growl({ title: "Success", message: message });
                    var $entrada_url = (secure_mode) ? ENTRADA_URL + "/secure" : ENTRADA_URL;
                    var url = $entrada_url + "/exams?section=confirmation&exam_progress_id=" + exam_progress_id;
                    setTimeout(function() {
                        window.location.href = url;
                    }, 2000);
                } else if (jsonResponse.status == "warning") {
                    $.growl.warning({ title: "Warning", message: message });
                } else {
                    $.growl.error({ title: "Error", message: message });
                }
            }
        });
    }

    function instructionsViewed() {
        var submission = {"method" : "instructions-viewed", "exam_progress_id" : exam_progress_id, "proxy_id": proxy_id};
        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: submission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var message = jsonResponse.data;
                if (jsonResponse.status == "success") {
                    $('form#exam-attempt-instructions').submit();
                } else if (jsonResponse.status == "warning") {
                    $.growl.warning({ title: "Warning", message: message });
                } else {
                    $.growl.error({ title: "Error", message: message });
                }
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown){
            $.growl.error({ title: "Error", message: "An error has occurred while starting the exam. Please contact an administrator if the problem persists." });
        });
    }

    function checkAllSaved() {
        var submission = {"method" : "check-responses", "exam_progress_id" : exam_progress_id, "post_id": post_id, "exam_id": exam_id, "proxy_id": proxy_id};
        var message;

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: submission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    message             = "<p>" + SECTION_TEXT["all_answered"] + "</p>";
                    $("#missing-responses").removeClass("alert-notice").addClass("alert-success");
                } else if (jsonResponse.status == "warning") {
                    var count_missing   = jsonResponse.count;
                    var missing         = jsonResponse.missing;
                    message             = "<p>" + count_missing + SECTION_TEXT["not_answered"] + missing + ".</p>";
                    $("#missing-responses").removeClass("alert-notice").addClass("alert-error");
                } else {
                    message             = "<p>" + SECTION_TEXT["unknown_error"] + "</p>";
                    $("#missing-responses").removeClass("alert-notice").addClass("alert-error");
                }

                $("#missing-responses").show().html(message);

                $(".submit-exam").prop("disabled", false);
            }
        });
    }

    function isEmpty(obj){
        return (Object.getOwnPropertyNames(obj).length === 0);
    }

    function set_exam_menu_init() {
        var exam_menu       = $("#exam-menu");
        exam_menu.removeClass("hide").addClass("show");
        side_bar_right_open = 1;
    }

    function adjust_exam_top() {
        var content         = document.getElementById("content");
        var screenPosition  = content.getBoundingClientRect();
        var new_menu_y      = screenPosition.top;

        if (new_menu_y > 157) {
            new_menu_y = 157;
        } else if (new_menu_y < 0) {
            new_menu_y = 0;
        }

        $("#exam-menu").css("top", new_menu_y);
    }

    function adjust_context_menu() {
        var position        = $(clicked_flag).offset();
        var flag_position   = clicked_flag.getBoundingClientRect();
        var contextMenu     = $("#contextMenuExam");
        var width           = $(contextMenu).outerWidth();

        $(contextMenu).css({"top" : flag_position.top, "left" : position.left - width, "display" : "inline"});
    }

    function adjust_exam_height() {
        var window_height   = $(window).height();
        var exam_menu       = $("#exam-menu");
        var control_bar     = $("#control-bar").outerHeight();
        var exam_menu_height = $("#exam-progress-menu").outerHeight();

        var content         = document.getElementById("content");
        var screenPosition  = content.getBoundingClientRect();
        var new_menu_y      = screenPosition.top;

        if (new_menu_y > 157) {
            new_menu_y = 157;
        } else if (new_menu_y < 0) {
            new_menu_y = 0;
        }

        var exam_height     = window_height - new_menu_y - control_bar - 5;

        if (exam_height <= exam_menu_height) {
            exam_menu.css({"overflow-y": "scroll"});
        } else {
            exam_menu.css({"overflow-y": "hidden"});
        }

        exam_menu.css({"height": exam_height});
    }

    function adjust_main_window() {
        var window_width    = $(window).width();
        var clock_container = $("#control-bar-clock");
        var cc_width        = $(clock_container).width();
        var exam_menu_width = $("#exam-menu").outerWidth();
        var content_width   = $("#content").outerWidth();
        var gutters_width   = (window_width - content_width);
        var gutter_right    = gutters_width - $("#content").offset().left;
        var full_width      = 1150;
        var min_width       = 600;

        if ((window_width >= min_width && window_width <= 1310 ) || (window_width <= 1430 && exam_menu_width > 215 ) ) {
            // generates the new size based on the current content width minus
            // the difference of the side bar menu width and the right gutter space
            var temp_content_width = content_width - (exam_menu_width - gutter_right);
            // adds 10 extra pixels for padding
            $("#content").width(temp_content_width - 20);
        } else if (window_width >= 1311) {
            $("#content").width(full_width);
        }

        if (cc_width < 124) {
            $(clock_container).find(".fa-clock-o").hide();
        } else if (cc_width >= 124) {
            $(clock_container).find(".fa-clock-o").show();
        }
    }

    //This function loads and hides the sidebar when clicking the icon for it on the control bar
    function toggleSidebarMenu() {
        var exam_menu   = $("#exam-menu");
        var full_width  = 960;
        var status;

        if (exam_menu.hasClass("hide")) {
            status = 1;
        } else {
            status = 0
        }

        adjust_main_window();
        adjust_exam_height();

        var dataObject = {
            "method"            : "get-menu",
            "exam_progress_id"  : exam_progress_id,
            "post_id"           : post_id,
            "exam_id"           : exam_id,
            "proxy_id"          : proxy_id,
            "status"            : status
        };

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: dataObject,
            type: "GET",
            success: function(data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status == "success") {

                }
            }
        });

        if (exam_menu.hasClass("hide")) {
            exam_menu.slideDown();
            setTimeout(function() {
                exam_menu.removeClass("hide").addClass("show");

                adjust_main_window();
                adjust_exam_height();
            }, 500);

            side_bar_right_open = 1;
        } else {
            exam_menu.css({
                "top"   : "auto",
                "bottom": 30
            });
            exam_menu.slideUp();
            setTimeout(function() {
                exam_menu.removeClass("show").addClass("hide");
                $("#content").width(full_width);
            }, 500);

            side_bar_right_open = 0;
        }
    }

    function update_nav_link(nav_link, element_id) {
        var nav_link_parent = $(nav_link).parent();
        var flagged = 0;
        var comment = 0;
        var element = $(".exam-question[data-element-id=\"" + element_id + "\"]");
        var table = $(element).find("table.question-table");
        var scratchPad = $(table).find(".learner_comments_text_area");

        // check flagged status
        if ($(table).hasClass("flagged")) {
            flagged = 1;
        }

        // check comment status
        var scratchPad_length = $(scratchPad).val().length;

        if (scratchPad_length > 0) {
            comment = 1;
        } else {
            comment = 0;
        }

        var dataObject = {
            "method"            : "get-menu-item",
            "exam_progress_id"  : exam_progress_id,
            "current_page"      : current_page,
            "element_id"        : element_id,
            "flagged"           : flagged,
            "comment"           : comment
        };

        $.ajax({
            url: ENTRADA_URL + "/exams?section=api-exams",
            data: dataObject,
            type: "GET",
            success: function(data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status == "success") {
                    var new_nav_link = jsonResponse.html;
                    nav_link_parent.html(new_nav_link);
                }
            }
        });
    }

    //this function will flag a question for review on the question header and also the sidebar nav link.
    function flag_question(span) {
        var icon    = span.find(".icon-flag");
        var element = span.parents(".exam-question");
        var element_id = element.find(".question-control").data("element-id");
        var nav_link = $(".exam_nav_link[data-element-id=" + element_id + "]");
        var nav_link_sibling = $(nav_link).next("i");

        if (span.closest("table").hasClass("flagged")) {
            span.closest("table").removeClass("flagged");
            span.removeClass("flagged");
            nav_link_sibling.remove();
        } else {
            span.closest("table").addClass("flagged");
            span.addClass("flagged");

            update_nav_link(nav_link, element_id);
        }

        flagQuestion(element_id);
    }

    //This function is for striking out an answer choice
    function right_click_answer(event) {
        switch (event.type) {
            case "contextmenu":
                var element = event.currentTarget;
                break;
            case "click":
                // var element = event.currentTarget;
                var element = $(event.currentTarget).parents(".question-answer-view");
                break;
        }

        var button = $(element).find(".strikeout-choice");

        if ($(element).hasClass("strike")) {
            $(element).removeClass("strike");
            $(button).removeClass("strike");
            var action = "remove";
        } else {
            $(element).addClass("strike");
            $(button).addClass("strike");
            var action = "add";
        }

        var answer_id   = $(element).data("qanswer-id");
        var element_id  = $(element).data("element-id");

        if (!striked[element_id]) {
            var answer_object = new Object();
            answer_object[answer_id] = action;
            striked[element_id] = answer_object;
        } else {
            var answer_object = striked[element_id];
            answer_object[answer_id] = action;
            striked[element_id] = answer_object;
        }

        manageSaveButton(1);
    }

    //This function changes the page when clicking a nav link in the sidebar.
    function side_bar_nav_clicked(clicked) {
        var anchor = clicked.children("a");
        var display_questions = $(anchor).data("display-questions");

        switch (display_questions) {
            case "one" :
            case "page_breaks" :
                var page_clicked = $(anchor).data("page");
                if (current_page == page_clicked) {
                    //scrolls the screen to the matching element of the nav link clicked.
                    side_bar_nav_same_page(anchor);
                } else {
                    side_bar_nav_new_page(anchor, page_clicked);
                }
                break;
            case "all" :
            default :
                //scrolls the screen to the matching element of the nav link clicked.
                side_bar_nav_same_page(anchor);
                break;
        }
    }

    function manageSaveButton(save_on) {
        if (save_on === 1) {
            $(".save-exam").prop("disabled", false);
            $(".save-exam").text(text_save);
        } else {
            $(".save-exam").prop("disabled", true);
            $(".save-exam").text(text_saved);
        }
    }

    function side_bar_nav_same_page(anchor) {
        var element_id = $(anchor).data("element-id");
        var selected_question = $("div.exam-question[data-element-id=" + element_id + "]");
        var y_scroll = $(selected_question).offset().top - 20;
        window.scroll(0, y_scroll);
    }

    function side_bar_nav_new_page(anchor, page_clicked) {
        var attempt = $(anchor).data("attempt");
        if (typeof page_clicked && page_clicked !== "undefined" && !$(anchor).hasClass("access_false")) {

            //Determine whether the user is in secure mode and select the appropriate URL
            var $entrada_url = (secure_mode) ? ENTRADA_URL + "/secure" : ENTRADA_URL;
            var url = $entrada_url + "/exams?section=attempt&action=resume&continue=true&id=" + post_id + "&progress_id=" + exam_progress_id + "&page=" + page_clicked;

            if (isThereAnythingToSave()) {
                saveExam("normal", function() { window.location = url; });
            } else {
                window.location = url;
            }
        }
    }

    //This function creates and loads the contextual menu for flagged items on the nav link sidebar
    function right_click_flag_icon(event) {
        event.preventDefault();
        flag_content_menu_open = 1;

        clicked_flag = event.currentTarget;
        flag_id = $(clicked_flag).prev("a").data("response_id");
        var position = $(clicked_flag).offset();
        var flag_position = clicked_flag.getBoundingClientRect();

        var html = "<li><a tabindex=\"-1\" href=\"#\" class=\"un-flag-item\">Un-flag</a></li>";
        $("#contextMenuExam").html(html);

        var contextMenu = $("#contextMenuExam");
        var width = $(contextMenu).outerWidth();

        $(contextMenu).css({"top" : flag_position.top, "left" : position.left - width, "display" : "inline"});
    }

    //this function will uncheck a flagged question
    function click_unflag(response_id) {
        var submission = {
            "method" : "update-flagged",
            "response_id" : response_id
        };

        var element_id = $(".exam_nav_link[data-response_id=\"" + response_id + "\"]").data("element-id");
        var in_a = jQuery.inArray(element_id, flagged);

        if (in_a === -1) {
            // item is not in the flagged array which means it has been saved.
            $.ajax({
                url: ENTRADA_URL + "/exams?section=api-exams",
                data: submission,
                type: "POST",
                success: function(data) {
                    var jsonResponse = JSON.parse(data);
                    var message = jsonResponse.data;
                    if (jsonResponse.status == "success") {
                        //removes flag from question header
                        clearFlagHtml(element_id, response_id);

                        /*
                         Disables save message
                         $.growl({ title: "Success", message: message });
                         */

                    } else if (jsonResponse.status == "warning") {
                        $.growl.warning({ title: "Warning", message: message });
                    } else {
                        $.growl.error({ title: "Error", message: message });
                    }
                }
            });
        } else {
            //clears flag from flagged array
            flagged.splice($.inArray(element_id, flagged), 1);

            if (!isThereAnythingToSave()) {
                manageSaveButton(0);
            }

            clearFlagHtml(element_id, response_id);
        }
    }

    function clearFlagHtml(element_id, response_id) {
        var table = $(".exam-question[data-element-id=" + element_id + "]").children("table.question-table");
        if (table.hasClass("flagged")) {
            table.removeClass("flagged");
            table.find("span.flag-question").removeClass("flagged");
        }

        //removes flag from sidebar
        var nav_link = $(".exam_nav_link[data-response_id=" + response_id + "]");
        $(nav_link).next("i").remove();
    }

    function isThereAnythingToSave() {
        var save_bool = 0;
        if (!$.isEmptyObject(inputs) || flagged.length > 0 || !$.isEmptyObject(striked) || marked_for_faculty.length > 0 || !$.isEmptyObject(learner_comments)) {
            save_bool = 1;
        }

        return save_bool;
    }

    function toggleNavMenuVisibility(clicked) {
        var header      = $(clicked).children("h4");
        var type        = $(clicked).data("type");
        var icon        = $(header).children("i.side-bar-icon");
        var header_text = $(header).text();
        header_text     = header_text.trim().replace(" ", "_");
        var nav         = $(clicked).next(".nav-list-item");
        var exam_menu   = $("#exam-menu");

        var exam_width;
        if ($(nav).hasClass("hide")) {
            $(nav).removeClass("hide");
            $(header).removeClass("dim");
            $(icon).removeClass("fa-plus").addClass("fa-minus");
            setCookie("exam_header_" + header_text + "_" + exam_progress_id, 1, 1);
            if (type == "calculator") {
                $(exam_menu).addClass("expanded");
                adjust_main_window();
            }
        } else {
            $(nav).addClass("hide");
            $(header).addClass("dim");
            $(icon).removeClass("fa-minus").addClass("fa-plus");
            setCookie("exam_header_" + header_text + "_" + exam_progress_id, 0, 1);
            if (type == "calculator") {
                $(exam_menu).removeClass("expanded");
                adjust_main_window();
            }
        }
    }

    function toggleSelfTimerModal(e) {
        e.preventDefault();
        var button = $(this);
        var status = $(button).hasClass("closed");
        var modal = $("#self-timer-modal");

        if (status == true) {
            $(modal).modal("hide");
        } else {
            $(modal).modal("show");
        }
    }

    function toggleTimer() {
        var count_down_clock        = $("#count-down-timer");
        var count_down_sec_class    = $("#count-down-sec-timer");

        if ($(count_down_clock).hasClass("show")) {
            $(count_down_clock).removeClass("show").addClass("hide");
            $(count_down_sec_class).addClass("show").removeClass("hide");
            setCookie("exam_timer_" + exam_progress_id, 1, 1);
        } else {
            $(count_down_sec_class).removeClass("show").addClass("hide");
            $(count_down_clock).removeClass("hide").addClass("show");
            setCookie("exam_timer_" + exam_progress_id, 0, 1);
        }
    }

    function toggleClock() {
        var count_down_clock        = $("#count-down-clock");
        var no_count_down_clock     = $("#no-count-down-clock");
        var count_down_sec_class    = $("#count-down-sec-clock");

        if ($(count_down_clock).hasClass("show")) {
            $(count_down_clock).removeClass("show").addClass("hide");
            $(no_count_down_clock).removeClass("hide").addClass("show");
            $(count_down_sec_class).addClass("hide");
            setCookie("exam_clock_" + exam_progress_id, 0, 1);
        } else if ($(no_count_down_clock).hasClass("show")) {
            $(no_count_down_clock).removeClass("show").addClass("hide");
            $(count_down_sec_class).removeClass("hide").addClass("show");
            $(count_down_clock).addClass("hide");
            setCookie("exam_clock_" + exam_progress_id, 2, 1);
        } else {
            $(count_down_sec_class).removeClass("show").addClass("hide");
            $(count_down_clock).removeClass("hide").addClass("show");
            $(no_count_down_clock).addClass("hide");
            setCookie("exam_clock_" + exam_progress_id, 1, 1);
        }
    }

    function displayTime(end_date) {
        //clock updates here
        var unixTimeStamp = Date.now();
        if (end_date > unixTimeStamp) {
            //end date hasn't passed yet so update the clock.
            var time_left          = (end_date - unixTimeStamp) / 1000 / 60;
            if (use_submission_date) {
                //check if the time limit will be before the submission_date
                var end_time_left = (time_left * 1000 * 60) + unixTimeStamp;
                if (end_time_left >= submission_date) {
                    time_left = (submission_date - unixTimeStamp) / 1000 / 60;
                }
            }

            var time_limit_hours   = Math.floor(time_left / 60);
            var time_limit_mins    = Math.floor(time_left % 60);
            var time_limit_sec     = Math.floor((time_left * 60) % (60));

            if (time_limit_mins < 10) {
                var time_limit_mins_display = "0" + time_limit_mins;
            } else {
                var time_limit_mins_display = time_limit_mins;
            }

            if (time_limit_sec < 10) {
                var time_limit_sec_display = "0" + time_limit_sec;
            } else {
                var time_limit_sec_display = time_limit_sec;
            }

            if (time_limit_hours < 1 && time_limit_mins < 6 && time_limit_sec_display < 1 || time_limit_hours < 1 && time_limit_mins < 5 ) {
                var display_clock = time_limit_hours + " : " + time_limit_mins_display + " : " + time_limit_sec_display;
                var display_clock_seconds = display_clock;
            } else {
                var display_clock = time_limit_hours + " : " + time_limit_mins_display;
                var display_clock_seconds = time_limit_hours + " : " + time_limit_mins_display + " : " + time_limit_sec_display;
            }

            $("#count-down-clock p").text(display_clock);
            $("#count-down-sec-clock p").text(display_clock_seconds);

            if (time_limit_hours < 1 && time_limit_mins < 6 && time_limit_mins > 2 && warning_5_min_displayed === false) {
                var display_warning = false;
                if (auto_submit == 1) {
                    var message = auto_submit_msgs[0];
                } else {
                    var message = auto_submit_msgs[1];
                }

                if (time_limit_mins == 5 && time_limit_sec < 1) {
                    display_warning = true;
                } else if (time_limit_mins < 5) {
                    display_warning = true;
                }

                if (display_warning == true) {
                    $.growl.warning({title: "Warning", message: message});
                    warning_5_min_displayed = true;
                    setCookie("exam_5min_" + exam_progress_id, warning_5_min_displayed);
                }
            }

            if (time_limit_hours < 1 && time_limit_mins < 2 && warning_1_min_displayed === false) {
                var display_warning = false;

                if (auto_submit == 1) {
                    var message = auto_submit_msgs[2];
                } else {
                    var message = auto_submit_msgs[3];
                }

                if (time_limit_mins == 1 && time_limit_sec < 1) {
                    display_warning = true;
                } else if (time_limit_mins < 1) {
                    display_warning = true;
                }

                if (display_warning == true) {
                    $.growl.error({title: "Warning", message: message});
                    warning_1_min_displayed = true;
                    setCookie("exam_1min_" + exam_progress_id, warning_1_min_displayed);
                }
            }

            if (time_left <= 0) {
                //time left is passed and clock should stop
                clearInterval(clock_loop);
                if (auto_submit == 1) {
                    saveExam("auto", submitExam());
                }
            }

        } else {
            //end date is passed and clock should stop
            clearInterval(clock_loop);
            if (auto_submit == 1) {
                saveExam("auto", submitExam());
            }
        }
    }

    function startClock() {
        //time_limit is in minutes so need to convert to milliseconds
        var time_limit_ms   = time_limit * 60 * 1000;
        var start_time_ms   = start_time * 1000;
        var time_limit_end  = start_time_ms + time_limit_ms;

        var displayed = getCookie("exam_time_limit_sub_" + exam_progress_id);

        if (use_submission_date && displayed != 1) {
            var unixTimeStamp = Date.now();
            var time_left = (time_limit_end - unixTimeStamp) / 1000 / 60;
            var end_time_left = (time_left * 1000 * 60) + unixTimeStamp;
            if (end_time_left >= submission_date) {
                //show bootstrap modal warning
                $("#time-limit-modal").modal();
                setCookie("exam_time_limit_sub_" + exam_progress_id, 1, 1);
            }
        }

        //starts the clock if the option was chosen on the post.
        if (use_time_limit == 1) {
            clock_loop = setInterval(function() {
                displayTime(time_limit_end);
            }, 1000);
        }
    }


    function displayTimerTime(end_date) {
        //clock updates here
        var unixTimeStamp = Date.now();
        if (end_date > unixTimeStamp) {
            //end date hasn't passed yet so update the clock.
            var time_left          = (end_date - unixTimeStamp) / 1000 / 60;

            var time_limit_hours   = Math.floor(time_left / 60);
            var time_limit_mins    = Math.floor(time_left % 60);
            var time_limit_sec     = Math.floor((time_left * 60) % (60));

            if (time_limit_mins < 10) {
                var time_limit_mins_display = "0" + time_limit_mins;
            } else {
                var time_limit_mins_display = time_limit_mins;
            }

            if (time_limit_sec < 10) {
                var time_limit_sec_display = "0" + time_limit_sec;
            } else {
                var time_limit_sec_display = time_limit_sec;
            }

            if (time_limit_hours < 1 && time_limit_mins < 6 && time_limit_sec_display < 1 || time_limit_hours < 1 && time_limit_mins < 5 ) {
                var display_clock = time_limit_hours + " : " + time_limit_mins_display + " : " + time_limit_sec_display;
                var display_clock_seconds = display_clock;
            } else {
                var display_clock = time_limit_hours + " : " + time_limit_mins_display;
                var display_clock_seconds = time_limit_hours + " : " + time_limit_mins_display + " : " + time_limit_sec_display;
            }

            $("#count-down-timer").text(display_clock);
            $("#count-down-sec-timer").text(display_clock_seconds);

            if (time_left <= 0) {
                if (exam_timer_displayed == 0) {
                    $("#self-timer-limit-modal").modal("show");
                    exam_timer_displayed = 1;
                    setCookie("exam_timer_" + exam_progress_id, exam_timer_displayed);
                }
                //time left is passed and clock should stop
                clearInterval(timer_loop);
            }
        } else {
            //end date is passed and clock should stop

            if (exam_timer_displayed == 0) {
                $("#self-timer-limit-modal").modal("show");
                exam_timer_displayed = 1;
                setCookie("exam_timer_" + exam_progress_id, exam_timer_displayed);
            }

            $("#count-down-timer").text("0 : 00");
            $("#count-down-sec-timer").text("0 : 00 : 00")

            //time left is passed and clock should stop
            clearInterval(timer_loop);
        }
    }

    function startTimer() {
        //time_limit is in minutes so need to convert to milliseconds
        var start_time_ms   = self_timer_start * 1000;
        var time_limit_ms   = self_timer * 60 * 1000;
        var time_limit_end  = start_time_ms + time_limit_ms;

        //starts the timer
        clearInterval(timer_loop);
        if (use_self_timer == 1 && self_timer > 0) {
            timer_loop = setInterval(function() {
                displayTimerTime(time_limit_end);
            }, 1000);
        }
    }

    function scrollNavMenu() {
        var scroll_position = getCookie("exam_menu_pos" + exam_progress_id);

        var scroll = Math.abs(scroll_position);
        if (scroll > 15) {
            scroll = scroll + 15;
        } else if (scroll == 15) {
            scroll = 0;
        }

        jQuery("div#exam-menu").animate({
            scrollTop: scroll
        }, 100);
    }

    function setCookie(cname, cvalue, days) {
        var d = new Date();
        d.setTime(d.getTime() + (days, 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires;
    }

    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(";");
        for (var i=0; i<ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==" ") c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
        }
        return false;
    }
});