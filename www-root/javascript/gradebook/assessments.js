var SITE_URL;
var COURSE_ID;
var ASSESSMENT_ID;
var notify_list = new Array();
var qq_ids;
var cperiod_id;

var timer;
var done_interval = 600;

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


    // buildAttachedExamList(post_id);
    var exam_post_ids = $(".assessment-post-exam");

    $.each(exam_post_ids, function(key, value) {
        var post_id = $(value).data("post_id");
        buildAttachedExamList(post_id);
    });

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
            $("#as_groups_list_container").removeClass("hide");
            $("#randomly-distribute-learners").prop('disabled', true);
        } else {
            $("#as_groups").addClass("hide");
            $("#group-learner-table").addClass("hide");
            $("#group-learner-table").removeClass("learner-table");
            $("#individual-learner-table").addClass("learner-table");
            $("#individual-learner-table").removeClass("hide");
            $("#as_groups_list_container").addClass("hide");
            $("#randomly-distribute-learners").prop('disabled', false);
        }
    });

    $("#group_assessment").trigger("change");

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

    if ($("#group_assessment").is(":checked")) {
        var advanced_settings = $("#as_groups").data("settings");
        advanced_settings.build_list();
    }

    $("#assessment-form").on("change", ".search-target-input-control", function () {
        if ($(this).is(":checked")) {
            AddGroupListByGroupID(this.value);
        } else {
            RemoveGroupListByGroupID(this.value);
        }
    });

    $("#assessment-form").on("click", ".remove-target-toggle", function () {
        RemoveGroupListByGroupID($(this).attr("data-id"));
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

    jQuery(document).on("click", ".remove_item", function() {
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

    $("#show_exm_option").on("click", function () {
        var clicked = $(this);
        var checked = $(clicked).is(":checked");
        if (checked) {
            $("#exam_posts").show();
            $("#exam_scoring_method_row").show();
        } else {
            $("#exam_posts").hide();
            $("#exam_scoring_method_row").hide();
        }
    });

    $("#exam-post-title-search").keyup(function () {
        var title = $(this).val();

        clearTimeout(timer);
        timer = setTimeout(function () {
            getExamsByTitle(title);
        }, done_interval);
    });

    $("#close-exam-post-modal").on("click", function (e) {
        e.preventDefault();
        if ($("#exam-search-list").children().hasClass("active")) {
            $("#exam-search-list").children().removeClass("active");
        }

        $("#exam-post-modal").modal("hide");
    });

    $("#exam-search-wrap").on("click", "#exam-search-list li", function () {
        if ($("#exam-search-list").children().hasClass("active")) {
            $("#exam-search-list").children().removeClass("active");
        }

        if (!$(this).hasClass("active")) {
            $(this).addClass("active");
        }
    });

    $("#attach-exam-posts").on("click", function (e) {
        e.preventDefault();

        if ($("#exam-search-list").children().hasClass("active")) {
            var post_id         = $("#exam-search-list").children(".active").attr("data-post_id");
            var post_title      = $("#exam-search-list").children(".active").attr("data-post_title");
            var exam_title      = $("#exam-search-list").children(".active").attr("data-exam_title");
            var post_date       = $("#exam-search-list").children(".active").attr("data-date");

            var exists = $("li.attached-exam-post-li[data-post_id=" + post_id + "]").length;

            if (exists > 0) {
                alert(exam_already_attached);
            } else {
                buildExamInput(post_id, post_title, post_date, exam_title);
                buildAttachedExamList(post_id);
                $("#attach-exam-post").addClass("hide");
                $("#exam-post-modal").modal("hide");
            }
        } else {
            alert(select_exam_post);
        }
    });

    $("#assessment-form").on("click", ".remove-attached-assessment-exam", function () {
        var post_id = $(this).data("post_id");
        removeExamPost(post_id)
    });

    $("#exam-post-modal").on("hide", function () {
        if ($("#exam-search-list").children().hasClass("active")) {
            $("#exam-search-list").children().removeClass("active");
        }
        $("#exam-search-list").empty();
        $("#exam-post-title-search").val("");
    });

    $("#exam-post-modal").on("show", function () {
        if (!jQuery("#exam-search-msgs").children().length) {
            display_notice([search_exam_post], "#exam-search-msgs", "append");
        }
    });

    $("#marking_scheme_id").on("change", function() {
        var marking_scheme_id = this;
        if ($(":selected", marking_scheme_id).val() == 3 || $(":selected", marking_scheme_id).text() == "Numeric") {

            var ac          = $("#assessment_characteristic");
            var value       = $(ac).val();
            var selected    = $("#assessment_characteristic option[data-id=" + value + "]");
            var title       = selected.data("title");
            var type        = selected.data("type");

            if ($("#show_exm_option").is(":checked")) {
                $("#numeric_grade_points_total").prop("disabled", true);
                $("#computer_numeric_note").show();
            } else {
                $("#numeric_grade_points_total").prop("disabled", false);
                $("#computer_numeric_note").hide();
            }

            $("#numeric_marking_scheme_details").show();
        } else {
            $("#computer_numeric_note").show();
            $("#numeric_marking_scheme_details").hide();
        }
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
        if (jQuery(':selected', this).val() == 3 || jQuery(':selected', this).text() == "Numeric") {
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



    jQuery(document).on("click", ".remove_quiz_btn", function() {
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

    $("#modal-attach-assessment-eportfolio").on('show', function(e) {
        getAssessmentEportfolios();
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

    $("#assessment-eportfolio-search-wrap").on("click", "#assessment-eportfolio-search-list li", function () {
        // Just allow one active ePortfolio
        $("#assessment-eportfolio-search-list li").removeClass("active");
        $(this).addClass("active");
    });

    // ToDo: is this typo a bug?
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

    $('#assessment-form table td:has(.table-internal)').css('padding', '0');

    $('.match-height').matchHeight({
        byRow: true,
        property: 'height',
        target: null,
        remove: false
    });

    $(".modal").on("show", function() {
        $(this).removeClass("hide");
    });
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

function buildAttachedEventList () {
    var event_title 		= jQuery("#assessment-event").attr("data-title");
    var event_date 			= jQuery("#assessment-event").attr("data-date");
    var event_li 			= document.createElement("li");
    var event_div 			= document.createElement("div");
    var remove_icon_div 	= document.createElement("div");
    var event_h3 			= document.createElement("h3");
    var event_span 			= document.createElement("span");
    var remove_icon_span 	= document.createElement("span");
    var event_remove_icon 	= document.createElement("i");

    jQuery(remove_icon_span).attr({id: "remove-attached-assessment-event"}).addClass("label label-important");
    jQuery(event_remove_icon).addClass("icon-trash icon-white");
    jQuery(remove_icon_span).append(event_remove_icon);
    jQuery(remove_icon_div).append(remove_icon_span).attr({id: "remove-attached-assessment-event-div"});
    jQuery(event_h3).addClass("event-text").text(event_title).html();
    jQuery(event_span).addClass("event-text").addClass("muted").text(event_date).html();
    jQuery(event_div).append(event_h3).append(event_span);
    jQuery(event_li).append(event_div);
    jQuery(event_li).append(remove_icon_div).append(event_div);
    jQuery("#attached-event-list").empty();
    jQuery("#attached-event-list").append(event_li);
}

function getExamsByTitle (title) {
    var data_object;
    var post_id = jQuery("input[name=\"exam_post_ids[]\"]").val();
    if (typeof assessment_id && assessment_id !== 0) {
        data_object = {
            method : "exam_title_search",
            course_id : course_id,
            assessment_id : assessment_id,
            title : title,
            post_id : post_id
        };
    } else {
        data_object = {
            method : "exam_title_search",
            course_id : course_id,
            title : title,
            post_id : post_id
        };
    }

    jQuery.ajax({
        url: API_URL,
        data: data_object,
        type: "GET",
        beforeSend: function () {
            jQuery("#exam-search-msgs").empty();
            jQuery("#exam-search-list").empty();
            jQuery("#loading-exam-post").removeClass("hide");
        },
        success: function(data) {
            jQuery("#loading-exam-post").addClass("hide");
            var response = JSON.parse(data);
            if (response.status == "success") {
                jQuery.each(response.data, function (key, exam) {
                    buildExamList(exam);
                });
            } else {
                display_notice(response.data, "#exam-search-msgs", "append");
            }
        },
        error: function () {
            jQuery("#loading-exam-post").addClass("hide");
        }
    });
}

function buildExamList (exam) {
    var exam_title = exam.exam_title;
    var post_title = exam.post_title;

    var exam_li 	= document.createElement("li");
    var exam_div 	= document.createElement("div");
    var exam_h3 	= document.createElement("h3");
    var exam_h5 	= document.createElement("h5");
    var h3_span_1   = document.createElement("span");
    var h5_span_1   = document.createElement("span");
    var h3_span_2   = document.createElement("span");
    var h5_span_2   = document.createElement("span");
    var exam_span 	= document.createElement("span");

    jQuery(h3_span_1).addClass("exam-title").text("Exam: ");
    jQuery(h5_span_1).addClass("post-title").text("Post: ");
    jQuery(h3_span_2).text(exam_title);
    jQuery(h5_span_2).text(post_title);
    jQuery(exam_h3).addClass("exam-text").append(h3_span_1).append(h3_span_2);
    jQuery(exam_h5).addClass("post-text").append(h5_span_1).append(h5_span_2);

    jQuery(exam_span).addClass("exam-text").addClass("muted").text(exam.post_start).html();
    jQuery(exam_div).addClass("exam-container").append(exam_h3).append(exam_h5).append(exam_span);
    jQuery(exam_li).attr({"data-id": exam.exam_id, "data-post_id": exam.post_id, "data-exam_title": exam.exam_title,  "data-post_title": exam.post_title, "data-date": exam.post_start}).append(exam_div);

    jQuery("#exam-search-list").append(exam_li);
}

function buildExamInput(post_id, post_title, post_date, exam_title) {
    var post_input = document.createElement("input");
    jQuery(post_input).attr({
        name: "exam_post_ids[]",
        class: "assessment-post-exam",
        type: "hidden",
        value: post_id,
        "data-post_title": post_title,
        "data-exam_title": exam_title,
        "data-date": post_date,
        "data-post_id": post_id}
    );
    jQuery("#assessment-form").append(post_input);
}

function buildAttachedExamList(post_id) {
    if (jQuery("#no-exam-post").length > 0) {
        jQuery("#attached-post-list").empty();
    }
    var item                = jQuery(".assessment-post-exam[data-post_id=" + post_id + "]");
    var exam_title 		    = jQuery(item).attr("data-exam_title");
    var post_title 		    = jQuery(item).attr("data-post_title");
    var exam_date 			= jQuery(item).attr("data-date");
    var exam_li 			= document.createElement("li");
    var exam_div 			= document.createElement("div");
    var remove_icon_div 	= document.createElement("div");
    var exam_h3 			= document.createElement("h3");
    var exam_h5 			= document.createElement("h5");
    var h3_span_1           = document.createElement("span");
    var h5_span_1           = document.createElement("span");
    var h3_span_2           = document.createElement("span");
    var h5_span_2           = document.createElement("span");
    var exam_span 			= document.createElement("span");
    var remove_icon_span 	= document.createElement("span");
    var exam_remove_icon 	= document.createElement("i");

    jQuery(remove_icon_span).attr({class: "remove-attached-assessment-exam", "data-post_id": post_id}).addClass("label label-important");
    jQuery(exam_remove_icon).addClass("icon-trash icon-white");
    jQuery(remove_icon_span).append(exam_remove_icon);
    jQuery(remove_icon_div).append(remove_icon_span).attr({class: "remove-attached-assessment-exam-div"});
    jQuery(h3_span_1).addClass("exam-title").text("Exam: ");
    jQuery(h5_span_1).addClass("post-title").text("Post: ");
    jQuery(h3_span_2).addClass("exam-title").text(exam_title);
    jQuery(h5_span_2).addClass("post-title").text(post_title);
    jQuery(exam_h3).addClass("exam-text").append(h3_span_1).append(h3_span_2);
    jQuery(exam_h5).addClass("post-text").append(h5_span_1).append(h5_span_2);
    jQuery(exam_span).addClass("exam-text").addClass("muted").text(exam_date);
    jQuery(exam_li).addClass("attached-exam-post-li").attr({"data-post_id": post_id});
    jQuery(exam_div).addClass("attached-assessment-exam-div");
    jQuery(exam_div).append(exam_h3).append(exam_h5).append(exam_span);
    jQuery(exam_li).append(exam_div);
    jQuery(exam_li).append(remove_icon_div).append(exam_div);

    jQuery("#attached-post-list").append(exam_li);
}

function removeExamPost(post_id) {
    jQuery(".assessment-post-exam[data-post_id=" + post_id + "]").remove();
    jQuery("li.attached-exam-post-li[data-post_id=" + post_id + "]").remove();

    if (jQuery(".remove-attached-assessment-exam").length < 1) {
        var exam_well_li = document.createElement("li");
        var exam_well    = document.createElement("div");
        jQuery(exam_well).addClass("well well-small content-small").text("There are currently no exam posts attached to this assessment. To attach an exam posts to this assessment, use the attach exam posts button.").attr({id: "no-exam-post"});
        jQuery(exam_well_li).append(exam_well);
        jQuery("#attached-post-list").empty();
        jQuery("#attached-post-list").append(exam_well_li);
    }
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

/* ePortfolio functions */
function buildAssessmentEportfolioList (assessmentEportfolio, autoSelect) {
    var list_li = document.createElement("li");
    var list_div = document.createElement("div");
    var list_h3 = document.createElement("h3");
    var list_span = document.createElement("span");

    jQuery(list_h3)
        .addClass("event-text")
        .text(assessmentEportfolio.portfolio_name)
        .html();
    jQuery(list_span)
        .addClass("event-text")
        .addClass("muted")
        .text(assessmentEportfolio.portfolio_start_date + " - " + assessmentEportfolio.portfolio_finish_date)
        .html();
    jQuery(list_div)
        .addClass("event-container")
        .append(list_h3)
        .append(list_span);
    jQuery(list_li)
        .attr({"data-portfolio-name": assessmentEportfolio.portfolio_name,
                "data-portfolio-id": assessmentEportfolio.portfolio_id})
        .append(list_div);

    if (true == autoSelect) {
        jQuery(list_li).addClass("active");
    }

    jQuery("#assessment-eportfolio-search-list").append(list_li);
}
