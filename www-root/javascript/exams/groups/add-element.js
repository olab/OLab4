jQuery(function($) {
    var questions = [];
    var loading_container = $("#exam-questions-loading");
    var questions_container = $("#questions-container");
    var question_view_controls = $("#question-view-controls");
    var question_table_container = $("#question-table-container");
    var question_detail_container = $("#question-detail-container");

    create_question_sidebar();

    question_view_controls.children("[data-view=\""+ VIEW_PREFERENCE +"\"]").addClass("active");

    if (VIEW_PREFERENCE === "list") {
        question_table_container.removeClass("hide");
    } else {
        question_detail_container.removeClass("hide");
    }

    if (typeof group_id === 'undefined') {
        group_id = 0;
    }
    var datatable = $("#questions-table").dataTable({
        'sPaginationType': 'full_numbers',
        'bInfo': true,
        'bPaginate': true,
        'bLengthChange': false,
        'bAutoWidth': false,
        'bProcessing': false,
        'sDom': '<"top"l>rt<"bottom"ip><"clear">',
        'sAjaxSource' : '?section=api-list&group_id='+group_id,
        'bServerSide': true,
        'aoColumns': [
            { 'mDataProp': 'modified', 'bSortable': false },
            { 'mDataProp': 'question_text' },
            { 'mDataProp': 'name' },
            { 'mDataProp': 'question_code' },
            { 'mDataProp': 'responses' }
        ],
        'iDisplayLength': 100,
        'iDisplayStart': 0,
        'oLanguage': {
            'sEmptyTable': 'You currently do not have any questions available.',
            'sZeroRecords': (group_id == 0 ? 'No Group was specified' : 'You currently do not have any questions available.')
        }
    });

    $(".view-toggle").on("click", function (e) {
        e.preventDefault();
        var selected_view = $(this).attr("data-view");

        if (selected_view === "list") {
            question_table_container.removeClass("hide");
            question_detail_container.addClass("hide");
        } else {
            question_detail_container.removeClass("hide");
            question_table_container.addClass("hide");
        }

        question_view_controls.children().removeClass("active");
        $(this).addClass("active");
        set_view ();
    });

    $("#question-search").keyup(function () {
        datatable.fnFilter($(this).val());
    });


    $(".question-table .question-control").on("click", function (e) {
        e.preventDefault();
    });

    $(".question-selector").on("change", function () {
        if ($(this).closest("table").hasClass("selected")) {
            $(this).closest("table").removeClass("selected");
        } else {
            $(this).closest("table").addClass("selected");
        }
    });

    $("#add-group-element").on("click", 'input.add-group-question', function() {
        $("#msgs").html("");
        var group_id = $("#group_id").val();
        var question_id = $(this).val();
        var question_checked = ($(this).is(":checked") ? "1" : "0");
        var url = $("#add-group-element").attr("action");

        var exam_data = [{method : "add-element", question_id : question_id, add_group_question_checked : question_checked}];

        var jqxhr = $.post(url, {method : "add-element", group_id : group_id, question_id : question_id, add_group_question_checked : question_checked}, function(data) {
                if (data.status == "success") {
                    $("#group_question_count").html(data.group_question_count)
                    display_success([data.msg], "#msgs");
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        );
    });

    function list_view () {}

    function detail_view () {}

    function get_questions () {
        $.ajax({
            url: ENTRADA_URL + "/api/question_bank.api.php",
            data: "method=get-questions",
            type: 'GET',
            beforeSend: function () {
                loading_container.removeClass("hide");
            },
            complete: function () {
                loading_container.addClass("hide");
                questions_container.removeClass("hide");
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $.each(jsonResponse.data, function (key, question) {
                        questions.push(question);
                    });
                } else {

                }
            }
        });
    }

    function set_view () {
        var selected_view = question_view_controls.children(".active").attr("data-view");

        $.ajax({
            url: ENTRADA_URL + "/api/question_bank.api.php",
            data: "method=view-preference&selected_view=" + selected_view,
            type: 'POST',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    if (jsonResponse.data.view === "detail") {
                        detail_view();
                    } else {
                        list_view();
                    }
                } else {

                }
            }
        });
    }


    function create_question_sidebar() {
        var url = ENTRADA_URL + "/admin/exams/groups?section=api-group"
        var group_id = 0;
        if ($("#group_id")) {
            group_id = $("#group_id").val();
        }
        var input_data = {method: "get-group", group_id: group_id };
        var jqxhr = $.get(url, input_data, function(data) {

        }, "json")
            .done(function(data) {
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

    function add_question_summary (title, questions, count, width) {
        var container = document.createElement("div");
        var icon_container = document.createElement("i");
        var title_container = document.createElement("p");
        var msg_container = document.createElement("p");

        $(container).addClass("timer");
        $(container).addClass("question_summary");
        $(icon_container).addClass("icon-tasks");
        $(title_container).append(icon_container);
        $(title_container).append("&nbsp;" + title);
        $(msg_container).append("Contains <span id=\"group_question_count\">" + count + "</span> question(s).<br />");
        $(msg_container).append("Width of group set to <span id=\"group_question_width\">" + width + "</span>.<br />");

        //if (questions.length && questions.length > 0) {
        //    $(msg_container).append(document.createElement("ul"))
        //    $(questions).each(function(index) {
        //        var question = document.createElement("li");
        //        $(question).append(this.question_text);
        //        $(msg_container).append(question);
        //    });
        //} else {
        //    $(msg_container).append("No Questions Attached.");
        //}

        $(container).append(title_container).append(msg_container);
        $(".inner-sidebar").append(container);

        $('.question_summary').affix({
            offset: {
                top: 276
            }
        });
    }
});