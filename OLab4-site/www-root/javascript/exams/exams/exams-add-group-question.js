    get_questions(false);

    if (typeof VIEW_PREFERENCE === "undefined") {
        var VIEW_PREFERENCE = "details";
    }

    jQuery("#question-search").keypress(function (event) {
        if (event.keyCode == 13)  {
            event.preventDefault();
        }

        total_questions = 0;
        question_offset = 0;
        show_loading_message = true;

        $("#load-questions").addClass("hide");
        $("#questions-table").addClass("hide");
        $("#question-detail-container").addClass("hide");
        $("#question-table-container table tbody, #question-detail-container").empty();


        clearTimeout(timeout);
        timeout = window.setTimeout(get_questions, 700, false);
    });

    jQuery("#load-questions").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_questions(false);
        }
    });

    function get_questions(question_index) {
        /*
         todo this needs to make sure to only search inside the current folder.
         */

        var search_term = jQuery("#question-search").val();
        var exclude_question_ids = {exclude_question_ids: []};
        var question_ids = [];

        if (jQuery("#search-targets-exam").length > 0) {
            question_offset = 0;
            total_questions = 0;
            show_loading_message = true;
            var filters = jQuery("#search-targets-exam").serialize();

        }

        jQuery("input.add-question:checked").each(function (index, element) {
            if (jQuery.inArray(jQuery(element).val(), question_ids) == -1) {
                question_ids.push(jQuery(element).val());
            }
        });

        exclude_question_ids = {exclude_question_ids: question_ids}

        var questions = jQuery.ajax
        (
            {
                url: ENTRADA_URL + "/admin/exams/questions?section=api-questions",
                data: "method=get-questions&search_term=" + search_term + "&offset=" + question_offset + "&limit=" + question_limit + "&view=" + VIEW_PREFERENCE +
                //(group_questions["group_questions"].length > 0 ? "&" + jQuery.param(group_questions) : "") +
                (exclude_question_ids["exclude_question_ids"].length > 0 ? "&" + jQuery.param(exclude_question_ids) : "") +
                (jQuery("#exam_id").val() !== "" ? "&exam_id=" + jQuery("#exam_id").val() : "") +
                (typeof filters !== "undefined" ? "&" + filters : ""),
                type: 'GET',
                beforeSend: function () {
                    if (jQuery("#exams-no-results").length) {
                        jQuery("#exams-no-results").remove();
                    }

                    if (show_loading_message) {
                        jQuery("#load-questions").addClass("hide");
                        jQuery("#exam-questions-loading").removeClass("hide");
                        jQuery("#questions-table").addClass("hide");
                        jQuery("#question-detail-container").addClass("hide");
                        jQuery("#questions-table tbody").empty();
                        jQuery("#question-detail-container").empty();
                    } else {
                        jQuery("#load-questions").addClass("loading");
                    }
                }
            }
        );

        jQuery.when(questions).done(function (data) {

            if (jQuery("#exams-no-results").length) {
                jQuery("#exams-no-results").remove();
            }
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.results > 0) {
                if (!reload_questions) {
                    total_questions += parseInt(jsonResponse.results);
                } else {
                    total_questions = parseInt(jsonResponse.results);
                    var checked_questions = jQuery("#questions-table input.add-question:checkbox:checked").length;
                    if (checked_questions > 0) {
                        checked_questions = checked_questions;
                        total_questions += checked_questions;
                    }
                }

                jQuery("#load-questions").html("Showing " + total_questions + " of " + jsonResponse.data.total_questions + " total questions");

                if (jsonResponse.results < question_limit) {
                    jQuery("#load-questions").attr("disabled", "disabled");
                } else {
                    jQuery("#load-questions").removeAttr("disabled");
                }

                question_offset = (question_limit + question_offset);

                if (reload_questions && question_index) {
                    jQuery("#questions-table").find("tbody tr[id!=question-row-" + question_index + "]").remove();
                    jQuery("div#question-detail-container").find("div.question-container[data-question-id!='" + question_index + "']").remove();

                    reload_questions = false;
                }

                if (show_loading_message) {
                    jQuery("#exam-questions-loading").addClass("hide");
                    jQuery("#load-questions").removeClass("hide");
                    if (VIEW_PREFERENCE === "list") {
                        jQuery("#questions-table").removeClass("hide");
                    } else {
                        jQuery("#question-detail-container").removeClass("hide");
                    }
                } else {
                    jQuery("#load-questions").removeClass("loading");
                }

                jQuery.each(jsonResponse.data.questions, function (key, question) {
                    var question_HTML = jQuery.ajax(
                        {
                            url: ENTRADA_URL + "/admin/exams/questions?section=api-questions",
                            data: {method: "build-question-answers", question: question, exam_mode: false},
                            type: "POST"
                        }
                    );

                    jQuery.when(question_HTML).done(function (data) {
                        var jsonResponse = JSON.parse(data);
                        jQuery("#questions-table").append(jsonResponse.data.html_list);
                        jQuery("#question-detail-container").append(jsonResponse.data.html_details);
                    });
                });

                show_loading_message = false;
            } else {
                jQuery("#exam-questions-loading").addClass("hide");
                var no_results_div = jQuery(document.createElement("div"));
                var no_results_p = jQuery(document.createElement("p"));

                no_results_p.html(submodule_text.index.no_questions_found);
                jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
                jQuery("#exam-msgs").append(no_results_div);
            }
        });
    }

    function build_question_additional_details(question_id) {
        var details = jQuery.ajax
        (
            {
                url: ENTRADA_URL + "/admin/exams/questions?section=api-questions",
                data: "method=get-question-details&question_id=" + question_id,
                type: "GET"
            }
        );

        jQuery.when(details).done(function (data) {
            var jsonResponse = JSON.parse(data);

            switch (jsonResponse.status) {
                case "success" :
                    var question_detail_section_tr = document.createElement("tr");
                    var question_section_td = document.createElement("td");
                    var question_detail_tr = document.createElement("tr");
                    var question_detail_td = document.createElement("td");
                    var question_detail_div = document.createElement("div");
                    var question_code_h5 = document.createElement("h5");
                    var question_code_span = document.createElement("span");
                    var question_tags_h5 = document.createElement("h5");
                    var question_tags_div = document.createElement("div");
                    var question_ul = document.createElement("ul");
                    var question_comments_li = document.createElement("li");
                    var question_date_li = document.createElement("li");

                    jQuery(question_code_span).html(jsonResponse.data.question_code);
                    jQuery(question_code_h5).html("Question Code: ").append(question_code_span);
                    jQuery(question_tags_h5).html("Question Tagged with");
                    jQuery(question_tags_div).addClass("question-tags");
                    jQuery(question_detail_div).append(question_code_h5).append(question_tags_h5).append(question_tags_div).addClass("question-details-container");
                    jQuery(question_detail_td).append(question_detail_div).attr({colspan: jsonResponse.data.total_answers});
                    jQuery(question_detail_tr).append(question_detail_td).addClass("question-detail-view");
                    jQuery(question_section_td).attr({colspan: jsonResponse.data.total_answers}).html("Question Details");
                    jQuery(question_detail_section_tr).addClass("details").append(question_section_td);

                    if (jsonResponse.data.hasOwnProperty("tags")) {
                        jQuery.each(jsonResponse.data.tags, function (key, tag) {
                            var tag_span = document.createElement("span");
                            var tag_a = document.createElement("a");
                            jQuery(tag_a).attr({herf: "#"}).html(tag.tag);
                            jQuery(tag_span).append(tag_a);
                            jQuery(question_tags_div).append(tag_span);
                        });
                    }

                    if (jsonResponse.data.allow_comemnts == "1") {
                        jQuery(question_comments_li).html("<span>Comments</span> are enabled for this question");
                    } else {
                        jQuery(question_comments_li).html("<span>Comments</span> are disabled for this question");
                    }

                    jQuery(question_date_li).html("<span>Question created on</span>: " + jsonResponse.data.created_date).addClass("pull-right");

                    jQuery(question_ul).append(question_comments_li).append(question_date_li);
                    jQuery(question_detail_div).append(question_ul);
                    jQuery("div[data-question-id=\"" + question_id + "\"]").find(".question-table tbody").append(question_detail_section_tr).append(question_detail_tr);
                    break;
                case "error" :
                    break;
            }
        });
    }

    function add_question_summary(title, questions, count, width) {
        var container = document.createElement("div");
        var icon_container = document.createElement("i");
        var title_container = document.createElement("p");
        var msg_container = document.createElement("p");

        jQuery(container).addClass("question-summary");
        jQuery(icon_container).addClass("icon-tasks");
        jQuery(title_container).append(icon_container);
        jQuery(title_container).append("&nbsp;" + title);
//        jQuery(msg_container).append("Contains <span id=\"group_question_count\">" + count + "</span> question(s).<br />");
        jQuery(msg_container).append("Width of group set to <span id=\"group_question_width\">" + width + "</span>.<br />");

        jQuery(container).append(title_container).append(msg_container);
        jQuery("#question-summary").append(container);
    }

    function create_question_summary(group_id) {
        //ToDo: change to use the group_width get param to initialize...may also need to add the group question count
        var url = ENTRADA_URL + "/admin//admin/exams/groups?section=api-group"
        var input_data = {method: "get-group", group_id: group_id};
        var jqxhr = jQuery.get(url, input_data, function (data) {

        }, "json")
            .done(function (data) {
                var group_title = "Group Elements";
                var questions = data.data.elements;
                var count = 0;
                var width = 0;
                if (data.status == "success") {
                    group_title = data.data.group.group_title;
                    questions = data.data.elements;
                    count = data.data.count;
                    width = data.data.width;
                }

                add_question_summary(group_title, questions, count, width);
            });
    }

    function set_view() {
        var selected_view = jQuery("#question-view-controls").children(".active").attr("data-view");

        jQuery.ajax({
            url: ENTRADA_URL + "/admin/exams/questions?section=api-questions",
            data: "method=view-preference&selected_view=" + selected_view,
            type: 'POST',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {

                } else {

                }
            }
        });
    }

    function build_question_row(question) {
        var question_code_anchor = document.createElement("a");
        var question_type_anchor = document.createElement("a");
        var question_answers_anchor = document.createElement("a");

        jQuery(question_code_anchor).attr({href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id});
        jQuery(question_type_anchor).attr({href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id});
        jQuery(question_answers_anchor).attr({href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id});

        var question_row = document.createElement("tr");
        var question_delete_td = document.createElement("td");
        var question_code_td = document.createElement("td");
        var question_type_td = document.createElement("td");
        var question_answers_td = document.createElement("td");
        var question_delete_input = document.createElement("input");

        jQuery(question_delete_input).attr({
            type: "checkbox",
            "class": "add-question",
            name: "questions[]",
            value: question.question_id
        });
        jQuery(question_delete_td).append(question_delete_input);
        jQuery(question_code_anchor).html(question.question_code);
        jQuery(question_type_anchor).html(question.question_type);
        jQuery(question_code_td).append(question_code_anchor);
        jQuery(question_type_td).append(question_type_anchor);
        jQuery(question_answers_td).append(question_answers_anchor);

        jQuery(question_row).attr("id", "question-row-" + question.question_id);
        jQuery(question_row).append(question_delete_td).append(question_code_td).append(question_type_td).append(question_answers_td).addClass("question-row");
        jQuery("#questions-table").append(question_row);

    }

    function build_question_details(question) {
        var question_div = document.createElement("div");
        var question_table = document.createElement("table");
        var question_table_tbody = document.createElement("tbody");
        var question_table_heading_tr = document.createElement("tr");
        var question_table_heading_td = document.createElement("td");
        var question_table_heading_h3 = document.createElement("h3");
        var question_table_type_tr = document.createElement("tr");
        var question_table_type_td = document.createElement("td");
        var question_type_span = document.createElement("span");
        var question_controls = document.createElement("div");
        var question_controls_span = document.createElement("span");
        var question_controls_delete = document.createElement("input");
        var question_controls_btn_group = document.createElement("div");
        var question_edit_a = document.createElement("a");
        var question_detail_a = document.createElement("a");
        var question_add_a = document.createElement("a");

        jQuery(question_edit_a).attr({
            href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id,
            title: "Edit Question"
        }).html("<i class=\"fa fa-pencil\"></i>").addClass("btn edit-question");
        jQuery(question_detail_a).attr({
            href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id,
            title: "View Question Details"
        }).html("<i class=\"fa fa-eye\"></i>").addClass("btn question-details");
        jQuery(question_add_a).attr({
            href: ENTRADA_URL + "/admin//admin/exams/questions?section=edit-question&id=" + question.question_id,
            title: "Attach to Exam"
        }).html("<i class=\"fa fa-plus-circle\"></i>").addClass("btn attach-question");

        jQuery(question_controls_btn_group).append(question_edit_a).append(question_detail_a).append(question_add_a).addClass("btn-group");
        jQuery(question_controls_delete).attr({
            type: "checkbox",
            "class": "add-question",
            name: "questions[]",
            value: question.question_id
        }).addClass("question-selector");
        jQuery(question_controls_span).append(question_controls_delete).addClass("btn select-question");
        jQuery(question_controls).append(question_controls_span).append(question_controls_btn_group).addClass("pull-right");
        jQuery(question_type_span).attr("data-question-type-id", question.questiontype_id);
        jQuery(question_type_span).html(question.question_type).addClass("question-type");
        jQuery(question_table_type_td).append(question_type_span).append(question_controls);
        jQuery(question_table_type_tr).append(question_table_type_td).addClass("type");
        jQuery(question_table_heading_h3).html(question.question_text);
        jQuery(question_table_heading_td).attr("id", "question-heading-" + question.question_id);
        jQuery(question_table_heading_td).append(question_table_heading_h3);
        jQuery(question_table_heading_tr).append(question_table_heading_td).addClass("heading");
        jQuery(question_table_tbody).append(question_table_type_tr).append(question_table_heading_tr);
        jQuery(question_table).append(question_table_tbody).addClass("question-table");
        jQuery(question_div).attr("data-question-answers", question.question_answers);
        jQuery(question_div).attr("data-question-type-id", question.questiontype_id);
        jQuery(question_div).append(question_table).attr({"data-question-id": question.question_id}).addClass("question-container");
        jQuery("#question-detail-container").append(question_div)
        build_answers(question);
    }

