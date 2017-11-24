var exam_offset = 0;
var exam_limit = 50;
var total_exams = 0;
var show_loading_message = true;
var exams_checked = {};
var elements_checked = {};
var exam_ids_approved = [];
var element_ids_approved = [];

jQuery(function($) {
    var display_page_breaks = ($("#exam-questions").hasClass("allow-page-breaks")) ? true : false;
    if (typeof exam_id && exam_id == "undefined") {
        var exam_id = $("#exam-questions-container").data("exam-id");
    }

    $("[data-toggle=\"tooltip\"]").tooltip();

    if (typeof exam_in_progress && exam_in_progress != "undefined" && exam_in_progress == 1) {
        disableFormControls();
    } else {
        $("#exam-questions").each(makeSortable);
        $("#exam-list-table tbody#exam-list-body").each(makeSortable);
    }

    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();
        
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax(
            {
                url: "?section=api-exams",
                data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
                type: "POST"
            }
        );

        $.when(remove_filter_request).done
        (
            function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    window.location.reload();
                }
            }
        );
    });
    
    $(".panel .clear-filters").on("click", function (e) {
        e.preventDefault();
        
        var remove_filter_request = $.ajax(
           {
               url: "?section=api-exams",
               data: "method=remove-all-filters",
               type: "POST"
           }
        );

        $.when(remove_filter_request).done
        (
           function (data) {
               var jsonResponse = JSON.parse(data);
               if (jsonResponse.status === "success") {
                   window.location.reload();
               }
           }
        );
    });
    
    var timeout;
    
    jQuery("#exam-search").keypress(function (event) {
        if (event.keyCode == 13)  {
            event.preventDefault();
        }
        
        total_exams = 0;
        exam_offset = 0;
        show_loading_message = true;
        
        clearTimeout(timeout);
        timeout = window.setTimeout(get_exams, 700);
    });
    
    jQuery("#load-exams").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_exams("more");
        }
    });

    jQuery("#load-previous-exams").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_exams("previous");
        }
    });

    if (display_page_breaks === true) {
        $("body").on("mouseenter", ".after-element", function () {
            var $exam_id = $(this).data("exam-id");
            var page_break_html =   "<div class=\"add-page-break-inline-container text-center\">" +
                                        "<a class=\"btn btn-default add-page-break add-page-break-inline\" data-exam-id=\"" + $exam_id + "\">" +
                                            "<i class=\"fa fa-file\"></i> " + INDEX_TEXT.add_page_break +
                                        "</a>" +
                                    "</div>";
            $(this).animate({height: "40px"}, 200);
            $(this).append(page_break_html);
        })

        .on("mouseleave", ".after-element", function () {
            $(this).animate({height: "25px"}, 200);
            $(this).empty();

        });
        /*
         * Add .after-element to each question
         * This is needed to create page-breaks on the fly
         */
        $(".exam-question").each(function() {
            $exam_id = $(this).parents("#exam-questions").data("exam-id");
            $element_id = $(this).parents(".exam-question").data("element-id");
            $(this).append("<div class=\"after-element\" data-exam-id=\"" + $exam_id + "\" data-element-id=\"" + $element_id + "\"></div>");
        });
    }

    $("body").on("click", ".add-page-break", function(e) {
        addPageBreak($(this));
        e.preventDefault();
    });

    $("#exam-questions").on("click", ".add-to-group", function(e) {
        $("#add-group-question-modal").modal("show");
    });

    $(".btn.move").on("click", function(e) {
        e.preventDefault();
    });
    
    $("#exam-questions").on("click", ".item-details", function (e) {
        e.preventDefault();
        var clicked = $(this);
        toggleQuestionDetailsPane(clicked);
    });

    $("#exam-list-container").on("click", ".item-details", function (e) {
        e.preventDefault();
        var clicked = $(this);
        toggleQuestionDetailsPane(clicked);
    });

    $("#exam-list-container").on("click", ".question-preview", function (event) {
        event.preventDefault();
        showQuestionPreview($(this));
    });

    $("#exam-list-table").on("click", ".related_version_link", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    $("#exam-questions").on("click", ".related_version_link", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    $("#update_all_questions").on("click", function(e) {
        e.preventDefault();
        updateAllQuestions();
    });

    $("th.sort-column").on("click", function() {
        loadExamSorted($(this))
    });

    $("input.question-number-update").on("change", function() {
        generateOrder();
    });

    $("#save_order").on("click", function() {
        $("#exam-elements").submit();
    });

    $("#exams-table").on("click", ".get-post-targets", function (e) {
        getPostTargets($(this));
    });

    if (typeof EXAM_VIEW_PREFERENCE === "undefined") {
        EXAM_VIEW_PREFERENCE = "details";
    }

    var exam_view_controls      = jQuery("#exam-view-controls");
    var exam_list_container     = jQuery("#exam-list-container");
    var exam_detail_container   = jQuery("#exam-questions");

    exam_view_controls.children("[data-view=\"" + EXAM_VIEW_PREFERENCE + "\"]").addClass("active");

    if (EXAM_VIEW_PREFERENCE === "list") {
        exam_list_container.removeClass("hide");
    } else {
        exam_detail_container.removeClass("hide");
    }

    jQuery("#toggle-exam-bank").on("click", function (e) {
        e.preventDefault();
        toggleExamBankView($(this));
    });

    jQuery("#exam-view-controls .view-toggle").on("click", function (e) {
        e.preventDefault();
        toggleExamView($(this));
    });

    jQuery("#exam-questions-container").on("click", ".select-item", function (e) {
        e.preventDefault();
        selectQuestion(jQuery(this));
    });

    $(".add-text").on("click", function(e) {
        addTextElement($(this));
        e.preventDefault();
    });

    $("#exam-questions").on("click", ".save-element", function(e) {
        saveExamElement($(this));
        e.preventDefault();
    });

    $("#exam-questions").on("change", ".points", function(e) {
        var element = $(this).parents(".exam-question");
        var $this = $(this);

        $.ajax({
            url: API_URL,
            type: "POST",
            async: false,
            data: {
                "method" : "save-points",
                "exam_element_id" : element.data("element-id"),
                "points" : $(this).val()
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $this.parents(".input-append").before("<span class=\"text-success\"><small>Saved points</small></span> ");
                } else {
                    $this.parents(".input-append").before("<span class=\"text-error\"><small>Error!</small></span> ");
                }
             }
        });

        e.preventDefault();
    });

    $("#exam-questions").on("click", ".dropdown-menu li a.scoring-option", function(e) {
        var element = $(this).parents(".exam-question");
        var $this = $(this);
        var $scored_value = $this.hasClass("not-scored") ? 1 : 0;
        var $button = $this.parents(".btn-group.scoring-method").find("button.scoring-method");

        $.ajax({
            url: API_URL,
            type: "POST",
            async: false,
            data: {
                "method" : "save-scoring",
                "exam_element_id" : element.data("element-id"),
                "not_scored" : $scored_value
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                element.find(".scoring-alert").remove();
                if (jsonResponse.status == "success") {
                    element.find(".question-type").after("<span class=\"label label-success scoring-alert\">Saved scoring</span> ");
                } else {
                    element.find(".question-type").after("<span class=\"label label-error scoring-alert\">Error!</span> ");
                }

                element.find("li.active").removeClass("active");
                $this.closest("li").addClass("active");

                if ($this.hasClass("not-scored")) {
                    element.addClass("not-scored").find("input.points").prop("disabled", true);
                    $button.toggleClass("state-scored state-not-scored");
                    element.find("tr.heading > td > h3 > span.label").remove();
                    element.find("tr.heading > td > h3").append("<span class=\"label label-info\">Not Scored</span>");
                } else {
                    element.removeClass("not-scored").find("input.points").prop("disabled", false);
                    $button.toggleClass("state-not-scored state-scored");
                    element.find("tr.heading > td > h3 > span.label").remove();
                }
                $button.html($this.text() + " <span class=\"caret\"></span>");
                $button.val($this.text());
            }
        });

        e.preventDefault();
    });

    $("button.scoring-method").each(function() {
        var $this = $(this);
        var element = $this.parents(".exam-question");
        if ($this.hasClass("state-not-scored")) {
            element.find("input.points").prop("disabled", true);
        } else {
            element.find("input.points").prop("disabled", false);
        }
    });

    $(".btn-delete").on("click", function(e) {
        e.preventDefault();
        if (!$(this).hasClass("disabled")) {
            $("#delete-exam-question-modal").modal("show");
        }
    });

    $("#delete-exam-question-modal").on("show.bs.modal", function (e) {
        buildExamElementsDelete();
    });

    $("#delete-exam-question-modal").on("hide.bs.modal", function (e) {
        $("#delete-questions-container").html("");
    });

    $("#delete-questions-modal-button").on("click", function (e) {
        e.preventDefault();
        deleteExamElements();
    });

    /**
     * Group Questions Listeners
     */
    $("#group-question-modal").on("show", function () {
        onShowGroupQuestions();
    });

    $("div#exam-list-container").on("click", ".btn.delete-group-question", function(e) {
        e.preventDefault();
        onClickDeleteGroupQuestion($(this));
    });

    $("div#exam-questions").on("click", ".btn.delete-group-question", function(e) {
        e.preventDefault();
        onClickDeleteGroupQuestion($(this));
    });

    $("#group-question-modal").on("change", "input[name=\"new_group\"]", function (e) {
        e.preventDefault();
        toggleGroupSections();
    });

    $("#group-question-modal").on("click", ".btn.btn-close", function() {
        $("#group-question-modal").modal("hide");
    });

    $("#group-question-modal").on("hidden", function() {
        toggleActionBtn();
    });

    $("#group-question-modal").on("click", "#group-questions-modal-save", function (e) {
        e.preventDefault();
        saveGroupQuestions();
    });
    /**
     * Ends Group Questions
     */

    /**
     * Enables the per page selector interface
     */

    var number_exams_pp = $("#number_exams_pp");

    if (number_exams_pp.length) {
        $("#number_exams_pp").inputSelector({
            rows:       1,
            columns:    6,
            data_text: [10, 25, 50, 100, 150, 200],
            modal:      0,
            header:     "Exams Per Page",
            form_name : "#exams-container",
            type:       "button",
            label:      "Per Page"
        });
    }

    $("#per_page_nav").on("click", ".selector-menu td.ui-timefactor-cell", function () {
        setTimeout(function () {
            exam_limit = parseInt($("#number_exams_pp").data("value"));
            exam_offset = 0;
            get_exams();
        }, 100);
    });

    /*
     * Event listeners for deleting, copying, and moving exams
     */
    $("#delete-exam-modal").on("show.bs.modal", function (e) {
        buildDeleteExam();
    });

    $("#delete-exam-modal").on("hide.bs.modal", function (e) {
        $("#delete-exams-container").html("");
    });

    $("#delete-exams-modal-button").on("click", function(e) {
        e.preventDefault();
        deleteApprovedExams();
    });

    $("#copy-exam-modal").on("show.bs.modal", function (e) {
        buildCopyExam();
    });

    $("#copy-exam-modal").on("hide.bs.modal", function (e) {
        $("#copy-exams-container").html("");
    });

    $("#copy-exams-modal-button").on("click", function(e) {
        e.preventDefault();
        copyApprovedExams();
    });

    $("#move-exam-modal").on("show.bs.modal", function (e) {
        buildMoveExam();
    });

    $("#move-exam-modal").on("hide.bs.modal", function (e) {
        $("#move-exams-container").html("");
    });

    $("#move-exams-modal-delete").on("click", function(e) {
        e.preventDefault();
        moveApprovedExams();
    });

    jQuery("#exams-table").on("click", ".select-exam", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    jQuery("#exams-table").on("change", ".select-exam", function (e) {
        var span = jQuery(this);
        var icon = jQuery(this).find(".icon-select-exam");
        var exam_id = icon.data("exam-id");
        var title = icon.data("title");
        var removed_select = false;

        if (span.closest("tr.exam-row").hasClass("selected")) {
            span.closest("tr.exam-row").removeClass("selected");
            span.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("fa-check-square-o");

            if (exams_checked[exam_id]) {
                delete exams_checked[exam_id];
            }
            removed_select = true;
        } else {
            span.closest("tr.exam-row").addClass("selected");
            span.addClass("selected");
            icon.addClass("fa-check-square-o").removeClass("fa-square-o");

            if (!exams_checked[exam_id]) {
                exams_checked[exam_id] = {
                    id: exam_id,
                    title: title
                };
            }
        }

        if (post_exam && post_exam != 0) {
            togglePostBtn();
            var select_buttons = $(".select-exam");
            select_buttons.each(function(key, value) {
                if (removed_select) {
                    $(value).prop("disabled", false);
                } else {
                    if (!$(value).hasClass("selected")) {
                        $(value).prop("disabled", true);
                    }

                }
            });
        } else {
            toggleActionBtn();
        }
    });


    $("#post-exam").on("click", function(e) {
        e.preventDefault();

        if (typeof exams_checked) {
            var keys = Object.keys(exams_checked);
            var exam_ids = [];
            for (var i = 0; i < keys.length; i++ ) {
                var exam_obj        = exams_checked[keys[i]];
                var exam_id         = exam_obj.id;
                exam_ids.push(exam_id);
            }
            var url = ENTRADA_URL + "/admin/exams/exams?section=form-post&id=" + exam_id + "&target_type=event&target_id=" + event_id;
            window.location = url;
        }
    });


    /**
     * Group Questions Functions
     */

    function onClickDeleteGroupQuestion(clicked) {
        var exam_question = $(clicked).parents(".exam-question");
        var exam_element_id;
        if (!exam_question.length) {
            exam_question = $(clicked).closest(".question-row");
            exam_element_id = exam_question.data("element-id");
        } else {
            exam_element_id = exam_question.data("element-id");
        }
        var data = {
            "method" : "delete-exam-group-element",
            "element": exam_element_id
        };

        $.ajax({
            url: API_URL,
            data: data,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    var updated_html = jsonResponse.updated_html;

                    $.each(updated_html, function(current_id, question_view) {
                        updateExamElementRowView(exam_element_id, current_id, question_view);
                    });

                    if ($("#exam-questions").children().length <= 0) {
                        var no_elements_attached = INDEX_TEXT.no_elements_attached;
                        $("#exam-questions").html(no_elements_attached);
                    }
                } else if (jsonResponse.status == "error") {
                    var error   = [jsonResponse.data];
                    var target  = "#msgs";

                    display_error(error, target);
                }
            }
        });
    }

    function updateExamElementRowView(remove_element, update_element, question_view) {
        var selected_exam_q = $(".exam-question[data-element-id=" + update_element + "]").filter(":last");
        var question_row    = $(".question-row[data-element-id=" + update_element + "]").filter(":last");

        //var question_view   = jsonResponse.question_view;
        var html_details    = question_view.details;
        var html_list       = question_view.list;

        if (remove_element == update_element) {
            /* removes detail group view */
            var parents   = selected_exam_q.parents(".exam-question-group-elements");
            selected_exam_q.remove();
            var children  = parents.find(".exam-question");
            var container = parents.parents(".exam-question-group");

            /* removes list group view */
            var parent_row = $(question_row).closest(".question-row.group");
            question_row.remove();
            var row_children = parent_row.find(".question-row");

            /* show question view here */
            container.before(html_details);
            parent_row.before(html_list);

            /* removes list group view container */
            if (children.length == 0) {
                container.remove();
            }

            /* removes list group view container */
            if (row_children.length == 0) {
                parent_row.remove();
            }
        } else {
            /* show question view here */
            selected_exam_q.before(html_details);
            selected_exam_q.remove();
            question_row.before(html_list);
            question_row.remove();
        }
    }

    function onShowGroupQuestions() {
        $("#group-question-modal").find(".modal-body").html(buildGroupModalBody());
        $("#group-question-modal").find(".modal-footer").html(buildGroupModalFooter());

        toggleGroupSections();
        $("#choose-question-group-btn").advancedSearch({
            api_url: ENTRADA_URL+"/admin/exams/groups?section=api-group",
            resource_url: ENTRADA_URL,
            filters: {
                question_group: {
                    label: "Question Group",
                    data_source: "get-user-groups",
                    mode: "radio",
                    selector_control_name: "question-group"
                }
            },
            control_class: "question-group-selector",
            no_results_text: "No Question Groups found matching the search criteria",
            parent_form: $("#group-question-modal-question"),
            width: 400,
            modal: true
        });
    }

    function saveGroupQuestions() {
        var modal       = $("#group-question-modal");
        var form        = $("form#group-question-modal-question");
        var new_group   = form.find("input[name=\"new_group\"]:checked").val();

        modal.find(".alert").remove();
        switch (new_group) {
            case "1":
                var group_title = $.trim(form.find("input#group-title").val());
                if (group_title !== "") {
                    //sorts the elements by order
                    elements_checked_order = {};
                    $.each(elements_checked, function (key, value) {
                        var row = $("tr.question-row[data-element-id=\"" + key + "\"]");
                        var order = $(row).find("input.question-number-update").val();
                        elements_checked_order[order] = value;
                    });

                    createGroup(group_title, elements_checked_order).done(function (json) {
                        var alert   = $("<div>").addClass("alert");
                        var status  = json.status;
                        var data    = json.data;
                        var msg     = json.msg;
                        if (status === "success") {
                            alert.addClass("alert-success");

                            var goToBtn = $("<a>").attr("href", ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + data.group_id);
                            goToBtn.addClass("btn btn-default");
                            goToBtn.html("<i class=\"fa fa-pencil\"></i> Edit Group");

                            var success_options = $("<div>");
                            success_options.addClass("text-center");
                            success_options.append(goToBtn);

                            modal.find(".modal-body").empty().append(alert.append(msg), success_options);
                            $("#group-question-modal").find(".modal-footer").html(buildGroupModalFooter("complete"));
                            elements_checked = {};

                            var exam_id = $("#exam_id").val();
                            var group_id = data.group_id;
                            var edit_exam = "edit";

                            switch (edit_exam) {
                                case "add":
                                    var replaceWith = null;
                                    break;
                                case "edit":
                                    var replaceWith = modal.data("question-version-id");
                                    break;
                                default:
                                    var replaceWith = null;
                                    break;
                            }

                            modal.find(".alert").remove();
                            attachGroup(exam_id, group_id, replaceWith).done(function (json) {
                                var alert = $("<div>").addClass("alert");
                                var status = json.status;
                                var msg = json.data;
                                if (status === "success") {
                                    alert.addClass("alert-success");
                                    modal.find(".modal-body").empty().append(alert.append(msg));
                                    window.location.href = ENTRADA_URL + "/admin/exams/exams?section=edit-exam&id=" + exam_id;
                                } else if (status === "error") {
                                    alert.addClass("alert-error");
                                    modal.find(".modal-body").prepend(alert.append(msg));
                                }
                            });
                        } else if (status === "error") {
                            alert.addClass("alert-error");
                            modal.find(".modal-body").prepend(alert.append(msg));
                        }
                    });
                }
                break;
            case "0":
                if ($("input[name=\"question-group\"]").val()) {
                    var group_id = $("input[name=\"question-group\"]").val();
                    addToGroup(group_id, questions).done(function (json) {
                        var alert = $("<div>").addClass("alert");
                        var status = json.status;
                        var data = json.data;
                        var msg = json.msg;
                        if (status === "success") {
                            alert.addClass("alert-success");
                            var goToBtn = $("<a>").attr("href", ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + data.group_id).addClass("btn btn-default").html("<i class=\"fa fa-pencil\"></i> Edit Group");
                            var success_options = $("<div>").addClass("text-center").append(goToBtn);

                            modal.find(".modal-body").empty().append(alert.append(msg), success_options);
                            $("#group-question-modal").find(".modal-footer").html(buildGroupModalFooter("complete"));
                        } else if (status === "error") {
                            alert.addClass("alert-error");
                            alert.append(msg);
                            if (data.length !== 0) {
                                var error_list = $("<ul>");
                                $.each(data, function(i, v) {
                                    var error_element = $("<li>").html(v);
                                    error_list.append(error_element);
                                });
                                alert.append(error_list);
                            }
                            modal.find(".modal-body").prepend(alert);
                        }
                    });
                }
                break;
            default:
                break;
        }
        toggleActionBtn();
    }

    function attachGroup(exam_id, group_id, replaceWith) {
        return $.post(ENTRADA_URL + "/admin/exams/exams?section=api-exams", {
            "method": "attach-grouped-questions",
            "exam_id": exam_id,
            "group_id": group_id,
            "replace": replaceWith
        }, null, "json");
    }

    function buildGroupModalBody() {
        var form                        = $("<form>").attr({id: "add-group-exam-modal", action: ENTRADA_URL + "/admin/exams/groups?section=api-group", method: "POST"}).addClass("form-horizontal");
        var label_radio_required        = $("<label>").addClass("radio form-required");
        var input_radio                 = $("<input>").attr({type: "radio", name: "new_group"});
        var input_radio_create_new      = input_radio.clone().prop("checked", true).val(1);
        var input_radio_add_to          = input_radio.clone().prop("checked", false).val(0);
        var label_group_title           = $("<label>").addClass("control-label form-required").attr("for", "group-title").text("Group name");
        var input_group_title           = $("<div>").addClass("controls").append($("<input>").attr({name: "group_title", id: "group-title", type: "text"}));
        var control_group_create_group  = $("<div>").addClass("control-group").addClass("control-group-sub").attr("id", "create-group-section").append(label_group_title, input_group_title);
        var control_group_create_new    = $("<div>").addClass("control-group").append(label_radio_required.clone().text("Create new question group and add the selected question(s)").append(input_radio_create_new), control_group_create_group);

        var label_choose_question_group = $("<label>").addClass("control-label form-required").attr("for", "choose-question-group-btn").text("Select group");
        var input_choose_question_group = $("<div>").addClass("controls entrada-search-widget").append($("<button>").addClass("btn btn-search-filter").attr({type: "button", id: "choose-question-group-btn"}).html("Browse Groups <i class=\"icon-chevron-down btn-icon pull-right\"></i>"));
        var control_group_add_to_group  = $("<div>").addClass("control-group").addClass("control-group-sub").attr("id", "add-to-group-section").append(label_choose_question_group, input_choose_question_group);
        var control_group_add_to        = $("<div>").addClass("control-group").append(label_radio_required.clone().text("Add the selected question(s) to an existing question group").append(input_radio_add_to), control_group_add_to_group);

        var choose_option               = $("<h3>").text("How would you like to group the selected questions?");
        var selected_questions          = $("<h3>").text("You have selected the following questions to group: ");
        var selected_questions_list     = $("<ul>").attr("id", "selected-questions-to-group");
        $.each(elements_checked, function(key, value) {
            var question_text   = $("div.exam-question[data-question-id=" + value.question_id + "] table tr.heading td .question_text").text();
            var question        = $("<li>").html("ID: <strong>" + value.question_id + "</strong> / Ver: <strong>" + value.version_count + "</strong> - " + question_text);
            selected_questions_list.append(question);
        });
        form.append(selected_questions, selected_questions_list, choose_option, control_group_create_new, control_group_add_to);

        return form;
    }

    function buildGroupModalFooter(state) {
        var footer = $("<div>").addClass("row-fluid");
        switch (state) {
            case "complete":
                var btn_close = $("<a>").addClass("btn btn-primary btn-close").data("dismiss", "modal").attr("href", "#").text("Close");
                footer.append(btn_close);
                break;
            case "start":
            default:
                var btn_cancel  = $("<a>").addClass("btn btn-default btn-close pull-left").data("dismiss", "modal").attr("href", "#").text("Cancel");
                var btn_save    = $("<input>").attr({type: "submit", id: "group-questions-modal-save"}).addClass("btn btn-primary").val("Save");
                footer.append(btn_cancel, btn_save);
                break;
        }

        return footer;
    }

    function createGroup(group_title, questions) {
        return $.post(ENTRADA_URL + "/admin/exams/groups?section=api-group", {
            "method": "add-group",
            "group_title": group_title,
            "questions": questions,
        }, null, "json");
    }

    function addToGroup(group_id, questions) {
        return $.post(ENTRADA_URL + "/admin/exams/groups?section=api-group", {
            "method": "add-to-group",
            "group_id": group_id,
            "questions": questions,
        }, null, "json");
    }

    function toggleGroupSections() {
        var modal = $("#group-question-modal");
        var form = $("form#group-question-modal-question");
        var new_group = form.find("input[name=\"new_group\"]:checked").val();

        switch (new_group) {
            case "1":
                form.find("#add-to-group-section").hide();
                form.find("#create-group-section").show();
                break;
            case "0":
                form.find("#add-to-group-section").show();
                form.find("#create-group-section").hide();
                break;
            default:
                break;
        }
    }

    function getPostTargets(clicked) {
        var exam_id = $(clicked).data("id");
        var transmission = {"method" : "courses-from-posts", "exam_id" : exam_id};
        $("#post-info-modal").modal("show");
        $.ajax({
            url: "?section=api-exams",
            data: transmission,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                var html = jsonResponse.html;
                if (jsonResponse.status == "success") {
                    $("#post-info-modal .modal-body").html(html);
                } else if (jsonResponse.status == "warning") {
                    $("#post-info-modal .modal-body").html(jsonResponse.msg);
                } else {
                    $("#post-info-modal .modal-body").html(jsonResponse.msg);
                }
            }
        });
    }

    function addTextElement(btn) {
        $.ajax({
            url: API_URL,
            type: "POST",
            async: false,
            data: "method=add-text&exam_id=" + btn.data("exam-id"),
            success: function (data) {
                var jsonResponse    = JSON.parse(data);
                var details_display = jsonResponse.data.details_display;
                var list_display    = jsonResponse.data.list_display;

                $("#exam-questions").append(details_display);
                $("#exam-list-body").append(list_display);
                $("html, body").animate({scrollTop: $("#exam-questions").find("[data-element-id=" + jsonResponse.data.exam_element_id +"]").offset().top}, 500);
                CKEDITOR.replace("element-" + jsonResponse.data.exam_element_id );
            }
        });
    }

    function addPageBreak(btn) {
        $.ajax({
            url: API_URL,
            type: "POST",
            async: false,
            data: "method=add-page-break&exam_id=" + btn.data("exam-id"),
            success: function (data) {
                var jsonResponse    = JSON.parse(data);
                if (display_page_breaks === true) {

                    var details_display = jsonResponse.data.details_display;
                    var list_display    = jsonResponse.data.list_display;

                    $("#exam-questions").append(details_display);
                    $("#exam-list-body").append(list_display);
                    $("html, body").animate({scrollTop: $("#exam-questions").find("[data-element-id=" + jsonResponse.data.exam_element_id +"]").offset().top}, 500);

                    $("#exam-questions").trigger("sortupdate");
                }
            }
        });
    }

    function saveExamElement(btn) {
        var ckeditor_instance = "element-" + btn.data("text-element-id");
        var ckeditor_data = CKEDITOR.instances[ckeditor_instance].getData();

        $.ajax({
            url: API_URL,
            type: "POST",
            async: false,
            data: {
                "method" : "save-text-element",
                "exam_element_id" : btn.data("text-element-id"),
                "element_text" : ckeditor_data
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                btn.html("Saved").css("backgroundColor", "#AADDAA").animate({
                    "backgroundColor" : "#ffffff"
                }, 5000, function() {
                    $(this).html("Save");
                });
            }
        });
    }

    function disableFormControls() {
        $(".q-list-edit").css("display", "none");
        $(".question-number-update").prop("disabled", true);
        $(".question-table tr.type .btn-group.scoring").css("display", "none");
        $(".question-table tr.type .btn-group.header-buttons").css("display", "none");
        $(".question-table tr.heading .element-controls").css("display", "none");
        $(".group-edit-buttons").css("display", "none");
    }

    function updateAllQuestions() {
        $.each(update_questions, function(key, value) {
            $("li.related_version_link[data-version-id=" +  value.version_id + "]").trigger("click");
            $("#questions_available").remove();
        });
    }

    function loadRelatedVersion(clicked) {
        var header              = $(clicked).parents("div.header-buttons");
        var icon                = $(header).find("i.related-question-icon");
        var original_version_id = $(icon).data("version-id");
        var new_version_id      = $(clicked).data("version-id");
        var exam_div            = $("div.exam-question[data-version-id=\"" + original_version_id + "\"]:not(.exam-question-group)");
        var tr_row              = $(".question-row[data-version-id=\"" + original_version_id + "\"]:not(.group)");
        var question_id         = $(exam_div).data("question-id");
        var question            = {"question_id": question_id};
        var element_id          = $(tr_row).data("element-id");
        var type                = $(clicked).data("type");
        var group_id            = $(exam_div).parents(".question-group-table").data("group-id");

        var dataObject = {
            method:     "build-question-answers",
            exam_mode:  false,
            question:   question,
            version_id: new_version_id,
            element_id: element_id,
            group_id: null
        };

        if (type === "group") {
            dataObject.method    = "update-group-question";
            dataObject.group_id  = group_id;
        }

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status == "success") {
                    var html_details = jsonResponse.data.html_details;
                    var html_list = jsonResponse.data.html_list;
                    $(exam_div).before(html_details);
                    $(exam_div).remove();
                    $(tr_row).before(html_list);
                    $(tr_row).remove();
                }
            }
        });
    }

    function loadExamSorted(clicked) {
        var exam_id         = $("#exam_id").val();
        var sort_field      = $(clicked).data("field");
        var sort_direction  = $(clicked).data("direction");

        if (!sort_direction) {
            sort_direction = "desc";
        }
        var icon;

        $.ajax({
            url: "?section=api-exams",
            data: "method=get-exam-elements&exam_id=" + exam_id + "&sort_field=" + sort_field + "&sort_direction=" + sort_direction,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $("span.sort-icon").find("i").remove();

                    if (sort_direction == "asc") {
                        sort_direction = "desc";
                        icon = "<i class=\"fa fa-sort-amount-desc\"></i>";
                    } else {
                        sort_direction = "asc";
                        icon = "<i class=\"fa fa-sort-amount-asc\"></i>";
                    }

                    $(clicked).find("span.sort-icon").html(icon);
                    $("th.sort-column").removeData("direction").removeAttr("data-direction");
                    $(clicked).data("direction", sort_direction);
                    var list_view_order = jsonResponse.list_view_order;
                    $.each(list_view_order, function(key, value) {
                        var element_id = value.element_id;
                        $("tr.exam-element[data-element-id=" + element_id + "]").appendTo("#exam-list-body");
                    });

                    if (sort_field == "order" && sort_direction == "asc") {
                        $("#exam-list-table tbody#exam-list-body").sortable( "option", "disabled", false );
                    } else {
                        $("#exam-list-table tbody#exam-list-body").sortable( "option", "disabled", true );
                    }
                } else {

                }
            }
        });
    }

    function generateOrder() {
        var order = $("input.question-number-update");
        var collection = {};
        $.each(order, function(key, question) {
            var new_order = $(question).val();
            var old_order = $(question).prop("defaultValue");
            var element_id = $(question).data("element-id");

            collection[element_id] = {
                "new_order" : new_order,
                "old_order" : old_order
            }
        });

        $("#re_order").val(JSON.stringify(collection));
        $("#save_order").prop("disabled", false);
    }

    function togglePostBtn() {
        if (isAnExamSelected() === false) {
            $("#post-exam").prop("disabled", true);
        } else {
            $("#post-exam").prop("disabled", false);
        }
    }
    
    function toggleActionBtn() {
        if (isAnExamSelected() === false) {
            $(".btn-actions").prop("disabled", true);
        } else {
            $(".btn-actions").prop("disabled", false);
        }
    }

    function isAnExamSelected() {
        var $selected = false;
        $("#exams-table").find(".select-exam").each(function() {
            if ($(this).hasClass("selected")) {
                return $selected = true;
            }
        });
        return $selected;
    }

    function toggleQuestionDetailsPane(clicked) {
        var element = clicked.parents(".question-table");

        var parent = clicked.parents(".exam-question");
        if (!parent.length) {
            parent = clicked.parents(".question-row");
        }

        var element_id = $(parent).data("element-id");

        /* Details View */
        var parent_detail_row = $(".exam-question[data-element-id=\"" + element_id + "\"]");
        var icon = parent_detail_row.find(".item-details");
        var details_row = parent_detail_row.find(".question-detail-view");
        details_row.toggleClass("hide");
        icon.toggleClass("active");
        //icon.find("i").toggleClass("white-icon");
    }

    function toggleExamView(clicked) {
        var selected_view = $(clicked).attr("data-view");
        EXAM_VIEW_PREFERENCE = selected_view;
        var add_text_link = $("a.add-text").parent();

        if (selected_view === "list") {
            exam_list_container.removeClass("hide");
            exam_detail_container.addClass("hide");
            $("#toggle-exam-bank").hide();
            if (!$(add_text_link).hasClass("disabled")) {
                $(add_text_link).addClass("disabled");
            }
        } else {
            exam_list_container.addClass("hide");
            exam_detail_container.removeClass("hide");
            $("#toggle-exam-bank").show();
            if ($(add_text_link).hasClass("disabled")) {
                $(add_text_link).removeClass("disabled");
            }
        }

        exam_view_controls.children().removeClass("active");
        $(clicked).addClass("active");
        setExamView();
    }

    function toggleExamBankView(clicked) {
        var active = false;

        if ($(clicked).hasClass("active")) {
            $(clicked).removeClass("active");
            //$(clicked).find("i").removeClass("white-icon");
        } else {
            $(clicked).addClass("active");
            //$(clicked).find("i").addClass("white-icon");
            active = true;
        }

        var item_details = jQuery(".item-details");

        jQuery.each(item_details, function(key, value) {
            if (active) {
                if (!jQuery(value).hasClass("active")) {
                    jQuery(value).trigger("click");
                }
            } else {
                if (jQuery(value).hasClass("active")) {
                    jQuery(value).trigger("click");
                }
            }
        });
    }

    function setExamView() {
        var selected_view = jQuery("#exam-view-controls").children(".active").attr("data-view");

        jQuery.ajax({
            url: "?section=api-exams",
            data: "method=view-preference&selected_view=" + selected_view,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {

                } else {

                }
            }
        });
    }

    function showQuestionPreview(clicked) {
        var icon = $(clicked).children(i);
        var version_id = icon.data("version-id");
        question_preview_id = version_id;
        var question_row = $("tr.question-row[data-version-id=\"" + version_id + "\"]").filter(":last");
        var id = $("question_row").find(".q-list-id").text();
        var html = $("#exam-questions-container .exam-question[data-version-id=\"" + version_id + "\"]").filter(":last");
        var question_preview_html = $(html).clone();
        var question_table = $(question_preview_html).find("table.question-table");
        $(question_table).find("tr.type .pull-right").remove();

        var modal_window = $("#preview-question-modal");
        var modal_body_text = modal_window.find(".modal-sub-body");

        $(modal_body_text).html(question_table);
        $(modal_window).modal("show");
    }

    function makeSortable() {
        var $group_sort = ($(this).hasClass("exam-question-group-elements")) ? true : false;
        var $group_id = ($group_sort) ? $(this).parents(".exam-question-group").data("group-id") : false;
        $(this).sortable( {
            opacity: 0.7
        });

        $(this).on("sortupdate", function() {
            var element_order = $(this).sortable("serialize", {"attribute": "data-sortable-element-id"});
            var element_order_temp = element_order.replace(/element\[\]\=|\&element\[\]\=/g, ",");
            if (element_order_temp[0] === ",") {
                element_order_temp = element_order_temp.substring(1);
            }
            var element_order_array = element_order_temp.split(",");
            element_order_array.reverse();

            var count = 0;
            var question_rows = $(".question-row");
            $.each(question_rows, function(key, value) {
                if ($(value).data("element-id").toString().substr(0, 5) != "group") {
                    count++;
                }
            });

            var table_id = $(this).attr("id");

            $(this).find("[data-sortable-element-id]").each(function(index, value) {
                var $editor = CKEDITOR.instances["element-"+$(this).data("element-id")];
                if ($editor) {
                    $editor.destroy();
                }
            });

            if ($group_sort) {
                var data_str = "method=update-exam-element-group-order&group_id=" + $group_id + "&" + element_order;
            } else {
                var data_str = "method=update-exam-element-order&" + element_order;
            }

            $.ajax({
                url: API_URL,
                data: data_str,
                type: "POST",
                cache: false
            });

            // Updates the order of the other section when the order is changed

            $.each(element_order_array, function(key, element_id) {
                if (table_id === "exam-list-body") {
                    var element             = $("#exam-questions .exam-question[data-sortable-element-id=\"element_" + element_id + "\"]");
                } else {
                    var element             = $("#exam-list-table .question-row[data-sortable-element-id=\"element_" + element_id + "\"]");
                }
                element = $(element).filter(":first");

                var span_number         = $("#exam-questions span.question-number[data-element-id=\"" + element_id + "\"]");

                var group_main_row      = $("#exam-list-table tr.question-row.group[data-sortable-element-id=\"element_" + element_id + "\"]");

                if (group_main_row.length) {
                    // this element is a group and we need to update the other questions in the group
                    var group_rows = $(group_main_row).find("tr.question-row");
                    var group_ids = [];
                    $.each(group_rows, function(key, group_row) {
                        var group_element_id = $(group_row).data("element-id");
                        group_ids.push(group_element_id);
                    });

                    group_ids.reverse();
                    $.each(group_ids, function(key, group_element_id) {
                        new_question_number = count--;

                        // update the value of the input
                        var input = $(group_main_row).find("input.question-number-update[data-element-id=\"" + group_element_id + "\"]");
                        input.val(new_question_number);

                        var span_number         = $("#exam-questions span.question-number[data-element-id=\"" + group_element_id + "\"]");
                        $(span_number).text(new_question_number + ".");
                    });
                } else {
                    var new_question_number = count--;
                    $(span_number).text(new_question_number + ".");

                    // update the value of the input
                    var input               = $("input.question-number-update[data-element-id=\"" + element_id + "\"]");
                    input.val(new_question_number);
                }
                $(element).parent().prepend(element);
            });
        });

        $(this).find("> .exam-question").find(".exam-question-group-elements").each(makeSortable);
    }

    function selectQuestion(clicked) {
        var $this           = clicked;
        var question        = $this.parents(".exam-element");
        var element_id      = question.data("element-id");
        question            = $("div.exam-element[data-element-id=" + element_id +"]");
        var question_id     = question.data("question-id");
        var version_id      = question.data("version-id");
        var version_count   = question.data("version-count");
        var question_description = question.find("span.question-description").text();
        var question_code   = question.find("span.question-code").text();
        var question_type   = question.find(".question-details-container span.question-type").text();
        var element_type    = $(question).data("element-type");

        /* Details View */
        var object_details  = $("#exam-questions .exam-element[data-element-id=" + element_id + "] > table"); //span_details.closest("table");

        /* List View */
        var object_list     = $("#exam-list-container .exam-element[data-element-id=" + element_id + "]"); //span_list.closest("tr.question-row");
        //adds the question to the object used to delete
        if (object_details.hasClass("selected")) {
            if (object_details.hasClass("group-table")) {
                object_details.find(".exam-question-group-elements > .exam-element").each(function() {
                    var group_element_id = $(this).data("element-id");
                    if (elements_checked[group_element_id]) {
                        delete elements_checked[group_element_id];
                    }
                });
            } else {
                if (elements_checked[element_id]) {
                    delete elements_checked[element_id];
                }
            }
        } else {
            if (object_details.hasClass("group-table")) {
                object_details.find(".exam-question-group-elements > .exam-element").each(function() {
                    var group_element_id            = $(this).data("element-id");
                    var group_question_id           = $(this).data("question-id");
                    var group_version_count         = $(this).data("version-count");
                    var group_question_description  = $(this).find("span.question-description").text();
                    var group_question_code         = $(this).find("span.question-code").text();
                    var group_question_type         = $(this).find(".question-details-container span.question-type").text();

                    if (!elements_checked[group_element_id]) {
                        elements_checked[group_element_id] = {
                            element_id:     group_element_id,
                            question_id:    group_question_id,
                            version_id:     version_id,
                            version_count:  group_version_count,
                            question_description: group_question_description,
                            question_code:  group_question_code,
                            question_type:  group_question_type
                        };
                    }
                });
            } else {
                if (!elements_checked[element_id]) {
                    if (element_type === "text" || element_type === "page_break") {
                        question_type = element_type;
                    }
                    elements_checked[element_id] = {
                        element_id:     element_id,
                        question_id:    question_id,
                        version_id:     version_id,
                        version_count:  version_count,
                        question_description: question_description,
                        question_code:  question_code,
                        question_type:  question_type
                    };
                }
            }
        }

        // Changes the HTML for the question checked
        changeSelectActiveHTML(object_details);
        changeSelectActiveHTML(object_list);

        if ($this.hasClass("selected")) {
            if (question.hasClass("exam-question-group")) {
                var elements = question.find(".group-table").find(".exam-question");

                elements.each(function() {
                    var element_id = $(this).data("element-id");
                    var element_add_input = document.createElement("input");
                    jQuery(element_add_input).attr({type: "hidden", "class": "add-element", "id":  "element-" + element_id, name: "elements[]", value: element_id});
                    $(this).parents("form").append(element_add_input);
                })

            } else {
                var element_add_input = document.createElement("input");
                jQuery(element_add_input).attr({type: "hidden", "class": "add-element", "id":  "element-" + element_id, name: "elements[]", value: element_id});
                $this.parents("form").append(element_add_input);
            }
        } else {
            if (question.hasClass("exam-question-group")) {
                var elements = question.find(".group-table").find(".exam-question");
                elements.each(function() {
                    var element_id = $(this).data("element-id");
                    $(this).parents("form").find("input#element-" + element_id).remove();
                });
            } else {
                $this.parents("form").find("input#element-" + element_id).remove();
            }
        }

        if ($.isEmptyObject(elements_checked)) {
            if ($(".btn-actions").prop("disabled") == false) {
                $(".btn-actions").prop("disabled", true);
            }
        } else {
            if ($(".btn-actions").prop("disabled") == true) {
                $(".btn-actions").prop("disabled", false);
            }
        }
    }

    function changeSelectActiveHTML(object) {
        var btn = object.find(".select-item");
        var icon = btn.find("i.fa");
        if (object.hasClass("selected")) {
            object.removeClass("selected");
            btn.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("fa-check-square-o");
        } else {
            object.addClass("selected");
            btn.addClass("selected");
            icon.addClass("fa-check-square-o").removeClass("fa-square-o");
        }
    }

    function buildExamElementsDelete() {
        var exam_id = $("#exam_id").val();
        var questions_selected = $("#questions-selected");
        var no_questions_selected = $("#no-questions-selected");
        var button = $("#delete-questions-modal-button");
        var exam_title = $("#exam-title").val();

        $("#msgs").html("");
        $("#exam-title-modal").text(exam_title);
        questions_selected.hide();
        no_questions_selected.hide();

        var questions = elements_checked;
        var empty = jQuery.isEmptyObject(questions);
        var delete_exam_elements = 0;

        if (!empty) {
            element_ids_approved = [];

            questions_selected.show();
            button.show();

            var dataObject = {
                method : "get-exam-element-delete-permission",
                exam_id: exam_id
            };

            jQuery.ajax({
                url: API_URL,
                data: dataObject,
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        var permission = jsonResponse.permission;

                        //todo update this section with new text
                        if (typeof permission && permission != "undefined") {
                            $.each(permission, function(exam_id, delete_permission) {
                                if (delete_permission === 1) {
                                    delete_exam_elements = 1;
                                }
                            });

                            var questionTableContainer  = $("<div>").attr("id", "question-table-container");
                            var table                   = $("<table>").attr("id", "question-table-container").addClass("table table-bordered table-striped");
                            var tableHeader             = $("<thead>");
                            var headerRow               = $("<tr>");
                            var headerColumnID          = $("<th>").text("ID/Ver");
                            var headerColumnType        = $("<th>").text("Type");
                            var headerColumnDescription = $("<th>").text("Description");
                            var tableBody               = $("<tbody>");
                            tableHeader.append(headerRow.append(headerColumnID, headerColumnType, headerColumnDescription));

                            //Build the Details view
                            $.each(questions, function(question_id, question) {
                                console.log(question);
                                var question_row        = $("<tr>");
                                var columnID            = $("<td>").text("ID: " + question.question_id + " / Ver: " + question.version_count);
                                var columnType          = $("<td>").text(question.question_type);
                                var columnDescription   = $("<td>").text(question.question_description);

                                if (question.question_type === "page_break" || question.question_type === "text") {
                                    columnID = $("<td>").text("ID: N/A / Ver: N/A");
                                }

                                question_row.append(columnID, columnType, columnDescription);
                                tableBody.append(question_row);
                                table.append(tableHeader, tableBody);
                                if (delete_exam_elements === 1) {
                                    element_ids_approved.push(question.element_id);
                                }
                            });
                            questionTableContainer.append(table);

                            if (delete_exam_elements !== 1) {
                                var msg = $("<div>").addClass("alert alert-error").text("The following questions cannot be removed from the exam");
                                questionTableContainer.prepend(msg)
                            }

                            $("#delete-questions-container").append(questionTableContainer);
                        }
                    } else if (jsonResponse.status == "error") {
                        $("#no-questions-selected").show();
                        $("#questions-selected").hide();
                    }
                }
            });
        } else {
            no_questions_selected.show();
            button.hide();
        }
    }

    function deleteExamElements() {
        var exam_id = $("#exam_id").val();
        if (typeof element_ids_approved && element_ids_approved != "undefined") {
            var dataObject = {
                method : "delete-exam-elements",
                exam_in_progress: exam_in_progress,
                exam_id: exam_id,
                element_ids: element_ids_approved
            };

            jQuery.ajax({
                url: API_URL,
                data: dataObject,
                type: "POST",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        var edit_url = jsonResponse.edit_url;
                        window.location = edit_url;
                    }
                }
            });
        } else {
            // Error no elements to remove
        }
    }

    function buildDeleteExam() {
        $("#exam-msgs").html("");
        $("#exams-selected").addClass("hide");
        $("#no-exams-selected").addClass("hide");

        var exams_to_modify = exams_checked;
        var empty = jQuery.isEmptyObject(exams_to_modify);

        var exam_ids = [];
        var exam_titles = {};

        if (!empty) {
            exam_ids_approved = [];
            $("#exams-selected").removeClass("hide");
            $("#delete-exams-modal-button").removeClass("hide");

            var list = document.createElement("ul");
            var keys = Object.keys(exams_to_modify);

            for (var i = 0; i < keys.length; i++ ) {
                var exam_obj        = exams_to_modify[keys[i]];
                var exam_id         = exam_obj.id;
                var title           = exam_obj.title;
                exam_ids.push(exam_id);
                exam_titles[exam_id] = title;
            }

            // Now check the ids for permissions and build the html for display.
            var dataObject = {
                method : "get-exam-delete-permission",
                exam_ids: exam_ids
            };

            jQuery.ajax({
                url: API_URL,
                data: dataObject,
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        var delete_permissions = jsonResponse.delete_permission;

                        if (typeof delete_permissions && delete_permissions != "undefined") {
                            $.each(delete_permissions, function(exam_id, delete_permission) {
                                var list_question = document.createElement("li");
                                if (delete_permission === 1) {
                                    $(list_question).append("<span>" + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + exam_titles[exam_id] + "</span>");
                                    exam_ids_approved.push(exam_id);
                                } else {
                                    var can_not = INDEX_TEXT.can_not_delete;
                                    $(list_question).append("<span class=\"no-delete\">Exam ID: " + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + can_not + "</span>");
                                }
                                $(list).append(list_question);
                            });
                        }
                    } else if (jsonResponse.status == "error") {
                        $("#no-exams-selected").removeClass("hide");
                        $("#exams-selected").addClass("hide");
                    }

                    $("#delete-exams-container").append(list);
                }
            });
        } else {
            $("#no-exams-selected").removeClass("hide");
            $("#delete-exams-modal-button").addClass("hide");
        }
    }

    function buildCopyExam() {
        var on_edit_exam = 0;
        var exam_id = $("#exam_id").val();

        if (typeof exam_id && exam_id != "undefined") {
            if (jQuery.isEmptyObject(exam_id)) {
                on_edit_exam = 0;
            } else {
                on_edit_exam = 1;
            }
        }

        $("#exam-msgs").html("");
        $("#exams-selected-copy").addClass("hide");
        $("#no-exams-selected-copy").addClass("hide");

        var exams_to_modify = exams_checked;
        var empty = jQuery.isEmptyObject(exams_to_modify);

        var exam_ids = [];
        var exam_titles = {};

        if (!empty || on_edit_exam === 1) {
            exam_ids_approved = [];
            $("#exams-selected-copy").removeClass("hide");
            $("#copy-exams-modal-button").removeClass("hide");

            var list = document.createElement("ul");

            if (on_edit_exam === 0) {
                var keys = Object.keys(exams_to_modify);

                for (var i = 0; i < keys.length; i++ ) {
                    var exam_obj        = exams_to_modify[keys[i]];
                    var exam_id         = exam_obj.id;
                    var title           = exam_obj.title;
                    exam_ids.push(exam_id);
                    exam_titles[exam_id] = title;
                }
            } else {
                var title = $("h1#exam_title").text();
                exam_ids.push(exam_id);
                exam_titles[exam_id] = title;
            }

            // Now check the ids for permissions and build the html for display.
            var dataObject = {
                method : "get-exam-copy-permission",
                exam_ids: exam_ids
            };

            jQuery.ajax({
                url: API_URL,
                data: dataObject,
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        var copy_permissions = jsonResponse.copy_permission;

                        if (typeof copy_permissions && copy_permissions != "undefined") {
                            $.each(copy_permissions, function(exam_id, copy_permissions) {
                                var list_question = document.createElement("li");
                                if (copy_permissions === 1) {
                                    $(list_question).append("<span>" + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + exam_titles[exam_id] + "</span>");
                                    exam_ids_approved.push(exam_id);
                                } else {
                                    var can_not = INDEX_TEXT.can_not_copy;
                                    $(list_question).append("<span class=\"no-delete\"" + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + can_not + "</span>");
                                }
                                $(list).append(list_question);
                            });
                        }
                    } else if (jsonResponse.status == "error") {
                        $("#no-exams-selected-copy").removeClass("hide");
                        $("#exams-selected-copy").addClass("hide");
                    }

                    $("#copy-exams-container").append(list);
                }
            });
        } else {
            $("#no-exams-selected-copy").removeClass("hide");
            $("#copy-exams-modal-button").addClass("hide");
        }
    }

    function buildMoveExam() {
        $("#exam-msgs").html("");
        $("#exams-selected-move").addClass("hide");
        $("#no-exams-selected-move").addClass("hide");

        var exams_to_modify = exams_checked;
        var empty = jQuery.isEmptyObject(exams_to_modify);

        var exam_ids = [];
        var exam_titles = {};

        if (!empty) {
            exam_ids_approved = [];
            $("#exams-selected-move").removeClass("hide");
            $("#move-exams-modal-button").removeClass("hide");

            var list = document.createElement("ul");
            var keys = Object.keys(exams_to_modify);

            for (var i = 0; i < keys.length; i++ ) {
                var exam_obj        = exams_to_modify[keys[i]];
                var exam_id         = exam_obj.id;
                var title           = exam_obj.title;
                exam_ids.push(exam_id);
                exam_titles[exam_id] = title;
            }

            // Now check the ids for permissions and build the html for display.
            var dataObject = {
                method : "get-exam-move-permission",
                exam_ids: exam_ids
            };

            jQuery.ajax({
                url: API_URL,
                data: dataObject,
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        var move_permissions = jsonResponse.move_permission;

                        if (typeof move_permissions && move_permissions != "undefined") {
                            $.each(move_permissions, function(exam_id, move_permissions) {
                                var list_question = document.createElement("li");
                                if (move_permissions === 1) {
                                    $(list_question).append("<span>" + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + exam_titles[exam_id] + "</span>");
                                    exam_ids_approved.push(exam_id);
                                } else {
                                    var can_not = INDEX_TEXT.can_not_move;
                                    $(list_question).append("<span class=\"no-delete\">" + INDEX_TEXT.Exam_id + ": <strong>" + exam_id + "</strong> - " + can_not + "</span>");
                                }
                                $(list).append(list_question);
                            });
                        }
                    } else if (jsonResponse.status == "error") {
                        $("#no-exams-selected-move").removeClass("hide");
                        $("#exams-selected-move").addClass("hide");
                    }

                    $("#move-exams-container").append(list);
                }
            });
        } else {
            $("#no-exams-selected-move").removeClass("hide");
            $("#move-exams-modal-button").addClass("hide");
        }
    }

    function deleteApprovedExams() {
        var dataObject = {
            method : "delete-exams",
            delete_ids: exam_ids_approved
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $(jsonResponse.exam_ids).each(function(index, element) {
                        var exam_row = $("tr.exam-row[data-id=\"" + element + "\"]");
                        exam_row.remove();
                    });
                    $("#delete-exam-modal").modal("hide");
                    display_success([jsonResponse.msg], "#exam-msgs")
                } else if (jsonResponse.status == "error") {
                    $("#delete-exam-modal").modal("hide");
                    display_error([jsonResponse.msg], "#exam-msgs");
                }
            }
        });

        exams_checked = {};
        var rows = $("tr.exam-row.selected");
        var buttons = $("button.selected");
        $(rows).removeClass("selected");
        $(buttons).removeClass("selected");
    }

    function copyApprovedExams() {
        var on_edit_exam = 0;
        var exam_id = $("#exam_id").val();
        var target_message = "#exam-msgs";

        if (typeof exam_id && exam_id != "undefined") {
            if (jQuery.isEmptyObject(exam_id)) {
                on_edit_exam = 0;
            } else {
                on_edit_exam = 1;
            }
        }

        var dataObject = {
            method : "copy-exams",
            copy_ids: exam_ids_approved
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    if (on_edit_exam === 0) {
                        $(jsonResponse.exam_view_data).each(function(index, exam_view) {
                            $("#exams-table").append(exam_view);
                        });
                        var message = [jsonResponse.msg];
                    } else {
                        target_message =  "#msgs";
                        var new_exam_id = jsonResponse.new_exam_id;
                        var url = $("#copy-exam-modal").data("href") + "&id=" + new_exam_id;
                        var message_part_1 = INDEX_TEXT.text_copy_01;
                        var message_part_2 = INDEX_TEXT.text_copy_02;
                        var message_part_3 = INDEX_TEXT.text_copy_03;
                        var message = [jsonResponse.msg, message_part_1 + "<a href=\"" + url + "\" style=\"font-weight: bold\">" + message_part_2 + "</a>" + message_part_3];
                    }

                    $("#copy-exam-modal").modal("hide");

                    display_success(message, target_message);

                    if (on_edit_exam === 1) {
                        setTimeout(function() {
                            window.location = url + "&id=" + new_exam_id;
                        }, 5000);
                    }
                } else if (jsonResponse.status == "error") {
                    $("#copy-exam-modal").modal("hide");
                    display_error([jsonResponse.msg], target_message);
                }
            }
        });
        if (on_edit_exam === 0) {
            exams_checked = {};
            var rows = $("tr.exam-row.selected");
            var buttons = $("button.selected");
            $(rows).removeClass("selected");
            $(buttons).removeClass("selected");
        }
    }
    
    function moveApprovedExams() {
        // todo update this once we add the folders for exams
        // todo it is commented out for now

        //var dataObject = {
        //    method : "move-exams",
        //    move_ids: exam_ids_approved
        //};
        //
        //jQuery.ajax({
        //    url: API_URL,
        //    data: dataObject,
        //    type: "POST",
        //    success: function (data) {
        //        var jsonResponse = JSON.parse(data);
        //        if (jsonResponse.status == "success") {
        //            $(jsonResponse.exam_ids).each(function(index, element) {
        //
        //                //var exam_row = $("tr.exam-row[data-id=\"" + element + "\"]");
        //                //exam_row.remove();
        //                //$("#delete-exam-modal").modal("hide");
        //                //display_success([jsonResponse.msg], "#exam-msgs")
        //            });
        //        } else if (jsonResponse.status == "error") {
        //            $("#move-exam-modal").modal("hide");
        //            display_error([jsonResponse.msg], "#exam-msgs");
        //        }
        //    }
        //});
        //exams_checked = {};
        //var rows = $("tr.exam-row.selected");
        //var buttons = $("button.selected");
        //$(rows).removeClass("selected");
        //$(buttons).removeClass("selected");
    }


});


function get_exams (offset) {
    if (jQuery("#search-targets-exam").length > 0) {
        total_exams = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-exam").serialize();
    }

    var search_term = jQuery("#exam-search").val();

    if ( typeof search_term === "undefined") {
        var search_term = "";
    }

    if (exam_offset < 0) {
        exam_offset = 0;
    }

    // Moves the offset for the next search
    if (offset == "more") {
        exam_offset = (parseInt(exam_limit) + parseInt(exam_offset));
    } else if (offset == "previous") {
        exam_offset = (parseInt(exam_offset) - parseInt(exam_limit));
    } else {
        exam_offset = 0;
    }

    if (exam_offset > 0) {
        jQuery("#load-previous-exams").prop("disabled", false);
    } else {
        jQuery("#load-previous-exams").prop("disabled", true);
    }

    var data_string = "method=get-exams" +
        "&search_term=" + search_term +
        "&limit=" + exam_limit +
        "&offset=" + exam_offset +
        (typeof filters !== "undefined" ? "&" + filters : "");

    var exams = jQuery.ajax({
            url: "?section=api-exams",
            data: data_string,
            type: "GET",
            beforeSend: function () {
                if (jQuery("#exams-no-results").length) {
                    jQuery("#exams-no-results").remove();
                }

                if (show_loading_message) {
                    jQuery("#exam-exams-loading").removeClass("hide");
                    jQuery("#exams-table").addClass("hide");
                    jQuery("#exams-table tbody").empty();
                }
            }
        }
    );

    jQuery.when(exams).done(function (data) {
        if (jQuery("#exams-no-results").length) {
            jQuery("#exams-no-results").remove();
            jQuery("#exams-table").removeClass("hide");
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            var exam_count = parseInt(jsonResponse.data.total_forms);
            total_exams = parseInt(jsonResponse.results);

            var exam_num_1 = exam_offset + 1;
            var exam_num_2 = exam_offset + total_exams;

            jQuery("#exams-loaded-display").html("Showing " + exam_num_1 + " - " +  exam_num_2 + " of " + exam_count + " total exams");

            if (exam_num_1 >= exam_count || exam_limit > exam_count || exam_num_2 == exam_count) {
                jQuery("#load-exams").prop("disabled", true);
            } else {
                jQuery("#load-exams").prop("disabled", false);
            }

            if (exam_count > 0) {
                jQuery("#exams-no-results").addClass("hide");
            }

            if (typeof jsonResponse.exams != "undefined") {
                jQuery("#exams-table tbody").empty();
                jQuery.each(jsonResponse.exams, function (key, exam) {
                    jQuery("#exams-table").append(exam);
                });
                jQuery("#exams-table").removeClass("hide");
            }

            if (show_loading_message) {
                jQuery("#exam-exams-loading").addClass("hide");
                jQuery("#exams-table").removeClass("hide");
            }

            show_loading_message = false;

        } else {
            jQuery("#exam-exams-loading").addClass("hide");
            jQuery("#load-exams").prop("disabled", true);
            jQuery("#exams-table").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(submodule_text.index.no_exams_found);
            jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
            jQuery("#exam-msgs").append(no_results_div);
        }
    });
}

function build_exam_row (exam) {

    var exam_title_anchor       = document.createElement("a");
    var exam_date_anchor        = document.createElement("a");
    var question_count_anchor       = document.createElement("a");

    jQuery(exam_title_anchor).attr({href: ENTRADA_URL + "/admin/exams/exams?section=edit-exam&id=" + exam.exam_id});
    jQuery(exam_date_anchor).attr({href: ENTRADA_URL + "/admin/exams/exams?section=edit-exam&id=" + exam.exam_id});
    jQuery(question_count_anchor).attr({href: ENTRADA_URL + "/admin/exams/exams?section=edit-exam&id=" + exam.exam_id});

    var exam_row            = document.createElement("tr");
    var exam_delete_td      = document.createElement("td");
    var exam_title_td       = document.createElement("td");
    var exam_date_td        = document.createElement("td");
    var exam_questions_td       = document.createElement("td");
    var exam_delete_input   = document.createElement("input");

    jQuery(exam_delete_input).attr({type: "checkbox", "class": "add-exam", name: "exams[]", value: exam.exam_id});
    jQuery(exam_delete_td).append(exam_delete_input);
    jQuery(exam_title_anchor).html(exam.title);
    jQuery(exam_title_anchor).attr("id", "exam_title_link_"+exam.exam_id);
    jQuery(exam_date_anchor).html(exam.created_date);
    jQuery(question_count_anchor).html(exam.question_count);
    jQuery(exam_title_td).append(exam_title_anchor);
    jQuery(exam_date_td).append(exam_date_anchor);
    jQuery(exam_questions_td).append(question_count_anchor);

    jQuery(exam_row).append(exam_delete_td).append(exam_title_td).append(exam_date_td).append(exam_questions_td).addClass("exam-row");
    jQuery("#exams-table").append(exam_row);
}

function toggle_active_question_detail(question) {
    var element = question.parents(".exam-question");
//    var question_id = element.data("question-id");
//    if (!jQuery(question).hasClass("active")) {
//        element.find(".question-detail-view").removeClass("hide");
//        jQuery(question).addClass("active");
//        jQuery(question).find("i").addClass("white-icon");
//    } else {
//        element.find(".question-detail-view").addClass("hide");
//        jQuery(question).removeClass("active");
//        jQuery(question).find("i").removeClass("white-icon");
//    }
}