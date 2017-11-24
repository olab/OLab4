var SITE_URL;
var COURSE_ID;
var ASSESSMENT_ID;
var notify_list = new Array();
var qq_ids;
var cperiod_id;

function displayQuiz(quiz_id) {
    var quiz_li = document.createElement("li");

    jQuery.ajax({
        url: SITE_URL + "/api/assessment-quiz.api.php",
        data: "method=get_quiz_display&assessment_id=" + ASSESSMENT_ID + "&quiz_id=" + quiz_id,
        type: "GET",
        success: function(data) {
            jQuery("#loading").addClass("hide");
            jQuery("#quiz_list").append(data);
            if (typeof qq_ids !== 'undefined') {
                jQuery('input[name^="question_ids"]').each(function () {
                    jQuery(this).prop("checked", (qq_ids.indexOf(parseInt(jQuery(this).val())) > -1) ? true : false);
                });
            }
        },
        error: function () {
            jQuery("#loading").addClass("hide");
        }
    });
}

function buildNotifyList(notify) {
    var added_contact = jQuery("#notify-list-table").find('tr[data-id="'+notify.contact_id+'"]');

    if (added_contact.length == 0) {
        var notify_tr = document.createElement("tr");
        var notify_td_1 = document.createElement("td");
        var notify_td_2 = document.createElement("td");
        var notify_td_3 = document.createElement("td");
        var notify_td_4 = document.createElement("td");

        jQuery(notify_tr).attr({"data-id":notify.contact_id, "data-ctype":notify.contact_type, "data-ctype-id":notify.contact_type_ids});
        jQuery(notify_td_1).html('<i class="icon-user"></i>');
        jQuery(notify_td_2).html(notify.contact_name);
        for (i=0 ; i<notify.contact_type.length ; i++) {
            jQuery(notify_td_3).append('<a class="btn btn-small">' + notify.contact_type[i] + '</a>');
        }
        jQuery(notify_td_4).html('<img src="/images/action-delete.gif" class="remove_item" />');
        jQuery(notify_tr).append(notify_td_1).append(notify_td_2).append(notify_td_3).append(notify_td_4);

        jQuery("#notify-list-table").append(notify_tr);
    }

    if (jQuery("#notify-list-table").find('tr').length > 0) {
        jQuery("#threshold-notify-list-tr").show();
    } 
}

jQuery(document).ready(function($) {

    $('input[name^="quiz_ids"]').each(function() {
        displayQuiz($(this).val());
    });

    if ($("#notify_threshold").is(":checked")) {
        var tmp_notify_list = new Array();
        $('input[name^="as_grade_threshold"]').each(function () {
            tmp_notify_list[tmp_notify_list.length] = $(this).val();
        });

        if (JSON.stringify(tmp_notify_list) != JSON.stringify(notify_list)) {
            notify_list = tmp_notify_list;
            var qry_string = "";
            for (i = 0; i < notify_list.length; i++) {
                qry_string += '&notify_list[]=' + notify_list[i];
            }

            jQuery.ajax({
                url: SITE_URL + "/admin/gradebook/assessments/?section=api-assessments",
                data: "method=get-notify-details&course_id=" + COURSE_ID + qry_string,
                type: "GET",
                beforeSend: function () {
                    $("#notify-list-table").find("tr").remove();
                },
                success: function (data) {
                    jQuery("#loading").addClass("hide");
                    var response = JSON.parse(data);
                    if (response.status == "success") {
                        jQuery.each(response.data, function (key, notify) {
                            buildNotifyList(notify);
                        });
                    }
                },
                error: function () {
                    jQuery("#loading").addClass("hide");
                }
            });

            if (qry_string == '') {
                $("#threshold-notify-list-tr").hide();
            } else {
                $("#threshold-notify-list-tr").show();
            }
        }

        refresh_notify_list_tr();
    } else {
         $("#grade_threshold").val(0);
    }

    $("#notify_threshold").on('change', function() {
        $("#grade_threshold").prop('disabled', !$("#notify_threshold").is(':checked'));
        $("#as_grade_threshold_notify").prop('disabled', !$("#notify_threshold").is(':checked'));
        if ($("#notify_threshold").is(':checked') && notify_list.length) {
            $("#threshold-notify-list-tr").show();
        } else {
            $("#threshold-notify-list-tr").hide();
        }
    });
    
    $("#show_quiz_option").on("click", function() {
        if ($("#show_quiz_option").is(":checked")) {
            $("#quizzes_wrapper").show("slow");
        } else {
            $("#quizzes_wrapper").hide("slow");
        }
    });

    $("#as_grade_threshold_notify").advancedSearch({
        api_url: SITE_URL + "/admin/gradebook/assessments/?section=api-assessments&course_id=" + COURSE_ID,
        resource_url: SITE_URL,
        build_selected_filters: false,
        filters: {
            as_grade_threshold: {
                label: "Select Who Gets Notified",
                data_source : "get-contacts-group",
                secondary_data_source : "get-contacts",
                mode: "checkbox",
                selector_control_name: "as_grade_threshold_notify",
                level_selectable: false
            }
        },
        list_data: {
            selector: "#threshold_notify_list_inputs"
        },
        list_selections: false,
        control_class: "as_grade_threshold_notify_selector",
        no_results_text: "No contacts were found for that group",
        parent_form: $("#threshold_notify_list_inputs"),
        width: 300,
        modal: false,
        selector_mode:true
    });

    $("#group_assessment").on("change", function() {
        $("#as_groups").prop('disabled', !$(this).is(':checked'));
        if ($(this).is(':checked')) {
            $("#as_groups").removeClass("hide");
            $("#group-learner-table").removeClass("hide");
            $("#group-learner-table").addClass("learner-table");
            $("#individual-learner-table").removeClass("learner-table");
            $("#individual-learner-table").addClass("hide");
        } else {
            $("#as_groups").addClass("hide");
            $("#group-learner-table").addClass("hide");
            $("#group-learner-table").removeClass("learner-table");
            $("#individual-learner-table").addClass("learner-table");
            $("#individual-learner-table").removeClass("hide");
        }
    });

    $("#as_groups").advancedSearch({
        api_url: SITE_URL + "/admin/gradebook/assessments/?section=api-assessments&course_id=" + COURSE_ID + "&cperiod_id=" + cperiod_id,
        resource_url: SITE_URL,
        build_selected_filters: false,
        filters: {
            as_groups: {
                label: "Group",
                data_source : "get-groups",
                mode: "checkbox",
            }
        },
        control_class: "as_groups",
        no_results_text: "No groups found",
        parent_form: $("#assessment-form"),
        width: 300,
        modal: false
    });

    $("#as_grade_threshold_search_container").on("click", ".search-target-input-control", function () {
        var contact_id = $(this).prop("value");

        if ($(this).prop("checked")) {
            qry_string = '&notify_list[]=' + contact_id;
            jQuery.ajax({
                url: SITE_URL + "/admin/gradebook/assessments/?section=api-assessments",
                data: "method=get-notify-details&course_id=" + COURSE_ID + qry_string,
                type: "GET",
                beforeSend: function () {
                    jQuery("#loading").removeClass("hide");
                },
                success: function (data) {
                    jQuery("#loading").addClass("hide");
                    var response = JSON.parse(data);
                    if (response.status == "success") {
                        jQuery.each(response.data, function (key, notify) {
                            buildNotifyList(notify);
                        });
                    }
                },
                error: function () {
                    jQuery("#loading").addClass("hide");
                }
            });
        } else { // unchecked
            $("#notify-list-table").find('tr[data-id="'+contact_id+'"]').remove();
            $("#div_threshold_notify_list").find('#as_grade_threshold_'+contact_id).remove();
        }
    });

    var timer;
    var done_interval = 600;

    $("#quiz-title-search").keyup(function () {
        var title = $(this).val();

        clearTimeout(timer);
        timer = setTimeout(function () {
            getQuizzesByTitle(title);
        }, done_interval);
    });

    $("#quizzes-search-wrap").on("click", "#quizzes-search-list li", function () {
        if ($("#quizzes-search-list").children().hasClass("active")) {
            $("#quizzes-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });

    function refresh_notify_list_tr() {
        var tmp_notify_list = new Array();
        $('input[name^="as_grade_threshold"]').each(function() {
            tmp_notify_list[tmp_notify_list.length] = $(this).val();
        });

        if (!tmp_notify_list.length) {
            $("#threshold-notify-list-tr").hide();
        } else {
            $("#threshold-notify-list-tr").show();
        }
    }

    $(".remove_item").live("click", function() {
        var id = $(this).closest("tr").attr("data-id"), found=false;
        $('input[name^="as_grade_threshold"]').each(function() {
            if ($(this).val() == id) {
                $(this).remove();
                found = true;
            }
        });

        if (found) {
            $(this).closest("tr").remove();
            refresh_notify_list_tr();
            return;
        }

        // Must have been added by selecting a contact type, remove the contact
        // type and add individual ids, apart from the one being removed.
        var contact_type = $(this).closest("tr").attr("data-ctype").split(",");
        var contact_type_id = $(this).closest("tr").attr("data-ctype-id").split(",");

        for(i=0 ; i<contact_type_id.length ; i++) {
            $('input[name^="as_grade_threshold"]').each(function() {
                if ($(this).val() == contact_type_id[i]) {
                    $(this).remove();
                    jQuery.ajax({
                        url: SITE_URL + "/admin/gradebook/assessments/?section=api-assessments",
                        data: "method=get-contacts&course_id=" + COURSE_ID + "&parent_id=" + contact_type_id[i],
                        type: "GET",
                        success: function (data) {
                            jQuery("#loading").addClass("hide");
                            var response = JSON.parse(data);
                            if (response.status == "success") {
                                jQuery.each(response.data, function (key, notify) {
                                    if (notify.target_id != id) {
                                        search_target_control = $(document.createElement("input")).attr({
                                            type: "hidden",
                                            name: "as_grade_threshold[]",
                                            value: notify.target_id,
                                            id: "as_grade_threshold_" + notify.target_id,
                                            "data-label": notify.target_label
                                        }).addClass("search-target-control").addClass("as_grade_threshold_search_target_control");
                                        $("#div_threshold_notify_list").append(search_target_control);
                                    }
                                });
                            }
                        },
                        error: function () {
                            jQuery("#loading").addClass("hide");
                        }
                    });
                }
            });

            $('input[name^="as_grade_threshold"]').each(function () {
                if ($(this).val() == contact_type_id[i]) {
                    $(this).remove();
                }
            });
        }

        $(this).closest("tr").remove();
        refresh_notify_list_tr();
    });

    $("#attach-learning-quiz").on("click", function (e) {
        e.preventDefault();

        if ($("#quizzes-search-list").children().hasClass("active")) {
            var quiz_id = $("#quizzes-search-list").children(".active").attr("data-id");
            var quiz_title = $("#quizzes-search-list").children(".active").attr("data-title");
            var quiz_location = $("#quizzes-search-list").children(".active").attr("data-location");
            var quiz_questions = $("#quizzes-search-list").children(".active").attr("data-questions");

            buildQuizInput(quiz_id, quiz_title, quiz_localtion, quiz_questions);
            buildAttachedQuizList ();

            $("#attach-quiz-button").addClass("hide");
            $("#quiz-modal").modal("hide");
        } else {
            alert("Please select a learning quiz to attach to this assessment. If you no longer wish to attach a learning quiz to this assessment, click close.");
        }
    });

    $("#quizzes-search-wrap").on("click", "#quizzes-search-list li", function () {
        if ($("#quizes-search-list").children().hasClass("active")) {
            $("#quizzes-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });


    $('#marking_scheme_id').change(function() {
        if(jQuery(':selected', this).val() == 3 || jQuery(':selected', this).text() == "Numeric") {
            jQuery('#numeric_marking_scheme_details').show();
        } else {
            jQuery('#numeric_marking_scheme_details').hide();
        }
    }).trigger('change');

    $("#close-quiz-modal").on("click", function (e) {
        e.preventDefault();
        if ($("#quizzes-search-list").children().hasClass("active")) {
            $("#quizzes-search-list").children().removeClass("active");
        }

        $("#quiz-modal").modal("hide");
    });

    $("#quiz-modal").on("hide", function () {
        if ($("#quizzes-search-list").children().hasClass("active")) {
            $("#quizzes-search-list").children().removeClass("active");
        }
        $("#quizzes-search-list").empty();
        $("#quiz-title-search").val("");
    });

    $("#quiz-modal").on("show", function () {
        if (!jQuery("#quiz-search-msgs").children().length) {
            var msg = ["To search for quizzes, begin typing the title of the quiz you wish to find in the search box."];
            display_notice(msg, "#quiz-search-msgs", "append");
        }
    });

    $(".quiz-delete-aquiz").on("click", function() {
        var aquiz_id = $(this).attr("data-aquiz-id");

        var succeeded = jQuery.ajax({
            url: SITE_URL + "/api/assessment-quiz.api.php",
            data: "method=delete_quiz&assessment_id=" + ASSESSMENT_ID + "&aquiz_id=" + aquiz_id,
            type: "GET",
            success: function(data) {
                var response = JSON.parse(data);
                if (response.status == "success") {
                    return true;
                } else {
                    return false;
                }
            }
        });

        if (succeeded) {
            $(this).closest("tbody").next('tbody').remove();
            $(this).closest("tbody").remove();
        }
    });

    function fetchOptions(select, selected_options) {
        jQuery.ajax({
            url: SITE_URL + "/admin/gradebook/assessments/?section=add&id=" + COURSE_ID,
            data: "mode=ajax&method=fetch-extended-options&type=" + select.val(),
            type: "POST",
            success: function(data) {
            if (data.length > 0) {
                jQuery("#assessment_options .options").append(data);

                if (typeof selected_options != "undefined") {
                    for (var i = 0; i < selected_options.length; i++) {
                        if (jQuery("#extended_option"+selected_options[i].length > 0)) {
                            jQuery("#extended_option"+selected_options[i]).attr("checked", "checked");
                        }
                    }
                }

                if (!jQuery("#assessment_options").is(":visible")) {
                    jQuery("#assessment_options").show();
                }
            } else {
                jQuery("#assessment_options").hide();
            }
        }
    });
    }

    $('#assessment_characteristic').on("change", function(e) {
        var select = jQuery(this);
        jQuery("#assessment_options .options").html("");
        fetchOptions(select);
        e.preventDefault();
    });

    function getQuizzesByTitle (title) {

        jQuery.ajax({
            url: SITE_URL + "/api/assessment-quiz.api.php",
            data: "method=title_search&assessment_id=" + ASSESSMENT_ID + "&course_id=" + COURSE_ID + "&title=" + title + "&cperiod=" + cperiod_id,
            type: "GET",
            beforeSend: function () {
                jQuery("#quiz-search-msgs").empty();
                jQuery("#quizzes-search-list").empty();
                jQuery("#loading").removeClass("hide");
            },
            success: function(data) {
                jQuery("#loading").addClass("hide");
                var response = JSON.parse(data);
                if (response.status == "success") {
                    jQuery.each(response.data, function (key, quiz) {
                        buildQuizList(quiz);
                    });
                } else {
                    display_notice(response.data, "#quiz-search-msgs", "append");
                }
            },
            error: function () {
                jQuery("#loading").addClass("hide");
            }
        });
    }

    function buildQuizList (quiz) {
        var quiz_li = document.createElement("li");
        var quiz_div = document.createElement("div");
        var quiz_h3 = document.createElement("h3");
        var quiz_span = document.createElement("span");

        jQuery(quiz_h3).addClass("quiz-text").text(quiz.quiz_title).html();
        jQuery(quiz_span).addClass("quiz-text").addClass("muted").text(quiz.quiz_location).html();
        jQuery(quiz_div).addClass("quiz-container").append(quiz_h3).append(quiz_span);
        jQuery(quiz_li).attr({"data-id": quiz.quiz_id, "data-title": quiz.quiz_title, "data-location": quiz.quiz_location, "data-questions": quiz.quiz_questions}).append(quiz_div);

        jQuery("#quizzes-search-list").append(quiz_li);
    }

    $("#attach-quiz").on("click", function (e) {
        e.preventDefault();

        if ($("#quizzes-search-list").children().hasClass("active")) {
            var quiz_id = $("#quizzes-search-list").children(".active").attr("data-id");

            displayQuiz(quiz_id);

            $("#assessment-form").append('<input type="hidden" name="quiz_ids[]" value="' + quiz_id + '">');
            $("#quiz-modal").modal("hide");
        } else {
            alert("Please select a quiz to attach to this assessment. If you no longer wish to attach a learning quiz to this assessment, click close.");
        }
    });



    $(".remove_quiz_btn").live("click", function() {
        quiz_id = $(this).attr("data-id");

        $('input[name^="quiz_ids"]').each(function () {
            if ($(this).val() == quiz_id) {
                $(this).remove();
            }
        });

        $('input[name^="aquiz_ids"]').each(function () {
            if ($(this).val() == quiz_id) {
                $(this).remove();
            }
        });

        $(this).closest("tbody").next('tbody').remove();
        $(this).closest("tbody").remove();
    });

    if ($("#assessment-event").length > 0) {
        buildAttachedEventList();
    }

    $("#event-title-search").keyup(function () {
        var title = $(this).val();

        clearTimeout(timer);
        timer = setTimeout(function () {
            getEventsByTitle(title);
        }, done_interval);
    });

    $("#events-search-wrap").on("click", "#events-search-list li", function () {
        if ($("#events-search-list").children().hasClass("active")) {
            $("#events-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });

    $("#attach-learning-event").on("click", function (e) {
        e.preventDefault();

        if ($("#events-search-list").children().hasClass("active")) {
            var event_id = $("#events-search-list").children(".active").attr("data-id");
            var event_title = $("#events-search-list").children(".active").attr("data-title");
            var event_date = $("#events-search-list").children(".active").attr("data-date");

            buildEventInput(event_id, event_title, event_date);
            buildAttachedEventList ();

            $("#attach-event-button").addClass("hide");
            $("#event-modal").modal("hide");
        } else {
            alert("Please select a learning event to attach to this assessment. If you no longer wish to attach a learning event to this assessment, click close.");
        }
    });

    $("#assessment-form-title-search").keyup(function () {
        var title = $(this).val();

        clearTimeout(timer);
        timer = setTimeout(function () {
            getAssessmentFormsByTitle(title);
        }, done_interval);
    });

    $("#events-search-wrap").on("click", "#events-search-list li", function () {
        if ($("#events-search-list").children().hasClass("active")) {
            $("#events-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });

    $("#assessment-form-search-wrap").on("click", "#assessment-form-search-list li", function () {
        if ($("#assessment-form-search-list").children().hasClass("active")) {
            $("#assessment-form-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });

    $("#assessment-form").on("click", "#remove-attched-assessment-event", function () {
        var event_well_li = document.createElement("li");
        var event_well = document.createElement("div");

        $(event_well_li).append(event_well);
        $("#attached-event-list").empty();
        $("#attached-event-list").append(event_well_li);
        $("#attach-event-button").removeClass("hide");
        $("#assessment-event").remove();
    });

    jQuery('#marking_scheme_id').change(function() {
        if(jQuery(':selected', this).val() == 3 || jQuery(':selected', this).text() == "Numeric") {
            jQuery('#numeric_marking_scheme_details').show();
        } else {
            jQuery('#numeric_marking_scheme_details').hide();
        }
    }).trigger('change');

    $("#close-event-modal").on("click", function (e) {
        e.preventDefault();
        if ($("#events-search-list").children().hasClass("active")) {
            $("#events-search-list").children().removeClass("active");
        }

        $("#event-modal").modal("hide");
    });

    $("#event-modal").on("hide", function () {
        if ($("#events-search-list").children().hasClass("active")) {
            $("#events-search-list").children().removeClass("active");
        }
        $("#events-search-list").empty();
        $("#event-title-search").val("");
    });

    $("#event-modal").on("show", function () {
        if (!jQuery("#event-search-msgs").children().length) {
            var msg = ["To search for learning events, begin typing the title of the event you wish to find in the search box."];
            display_notice(msg, "#event-search-msgs", "append");
        }
    });

    $("input[name='show_learner_option']").change(function(){
        if ($("input[name='show_learner_option']:checked").val() == 1) {
            $('#gradebook_release_options').show();
        }
        else if ($("input[name='show_learner_option']:checked").val() == 0) {
            $('#gradebook_release_options').hide();
        }
    });

    if ($("input[name='show_learner_option']:checked").val() == 1) {
        $('#gradebook_release_options').show();
    }
    else if ($("input[name='show_learner_option']:checked").val() == 0) {
        $('#gradebook_release_options').hide();
    }

});

/* Events functions */
function getEventsByTitle (title) {
    jQuery.ajax({
        url:  SITE_URL + "/api/assessment-event.api.php",
        data: "method=title_search&course_id=" + course_id + "&title=" + title + "&cperiod=" + cperiod_id,
        type: "GET",
        beforeSend: function () {
            jQuery("#event-search-msgs").empty();
            jQuery("#events-search-list").empty();
            jQuery("#loading").removeClass("hide");
        },
        success: function(data) {
            jQuery("#loading").addClass("hide");
            var response = JSON.parse(data);
            if (response.status == "success") {
                jQuery.each(response.data, function (key, event) {
                    buildEventList(event);
                });
            } else {
                display_notice(response.data, "#event-search-msgs", "append");
            }
        },
        error: function () {
            jQuery("#loading").addClass("hide");
        }
    });
}

function buildEventList (event) {
    var event_li = document.createElement("li");
    var event_div = document.createElement("div");
    var event_h3 = document.createElement("h3");
    var event_span = document.createElement("span");

    jQuery(event_h3).addClass("event-text").text(event.event_title).html();
    jQuery(event_span).addClass("event-text").addClass("muted").text(event.event_start).html();
    jQuery(event_div).addClass("event-container").append(event_h3).append(event_span);
    jQuery(event_li).attr({"data-id": event.event_id, "data-title": event.event_title, "data-date": event.event_start}).append(event_div);

    if (jQuery("#calendar-search-toggle").hasClass("active")) {
        jQuery("#events-list").append(event_li);
    } else {
        jQuery("#events-search-list").append(event_li);
    }
}

function buildEventInput (event_id, event_title, event_date) {
    var event_input = document.createElement("input");

    if (jQuery("#assessment-event").length) {
        jQuery("#assessment-event").remove();
    }

    jQuery(event_input).attr({name: "event_id", id: "assessment-event", type: "hidden", value: event_id, "data-title": event_title, "data-date": event_date});
    jQuery("#assessment-form").append(event_input);
}

/* Assessment Forms functions */
function buildAssessmentFormList (assessmentForm) {
    var event_li = document.createElement("li");
    var event_div = document.createElement("div");
    var event_h3 = document.createElement("h3");
    var event_span = document.createElement("span");

    jQuery(event_h3).addClass("event-text").text(assessmentForm.title).html();
    jQuery(event_span).addClass("event-text").addClass("muted").text(assessmentForm.created_date).html();
    jQuery(event_div).addClass("event-container").append(event_h3).append(event_span);
    jQuery(event_li).attr({"data-id": assessmentForm.form_id, "data-title": assessmentForm.title, "data-date": assessmentForm.created_date}).append(event_div);

    jQuery("#assessment-form-search-list").append(event_li);
}