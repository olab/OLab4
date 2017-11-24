var mapped = [];
var listed = [];
var question_type_id;
var shortname;
var answers_checked = {};
var answers_match_checked = {};
var correct_answer = [];
var answers_to_delete = [];
var delete_url;
var fnb_collection = {};
var sortable_id;
var folder_id;
var xhr;

jQuery(document).ready(function ($) {
    var question_type_controls = $("#question-type-controls");
    var min_answers = 2;
    var max_answers = 99;

    //sets the original shortname
    question_type_id = $("#question-type").val();

    manageCorrectAnswer();
    updateMatchCorrectCollection();

    if (typeof folder_id_get != "undefined") {
        renderFolderView(folder_id_get, true, "");
    }

    /**
     * Init Code
     */

    jQuery("#exam-question-topics-toggle").trigger("click");

    if (jQuery("#mapped_flat_objectives").children("li").length == 0) {
        jQuery("#toggle_sets").trigger("click");
    }

    //load mapped array on page load
    jQuery("#checked_objectives_select").children("option").each(function() {
        mapped.push($(this).val());
    });
    jQuery("#clinical_objectives_select").children("option").each(function() {
        mapped.push($(this).val());
    });

    jQuery("#mapped_flat_objectives").children("li").each(function() {
        if (jQuery(this).attr("data-id") !== undefined && jQuery(this).attr("data-id")){
            listed.push(jQuery(this).attr("data-id"));
        }
    });

    /*
     Initiates the folder sorting on first load
     */
    jQuery.ajax({
        url: FOLDER_API_URL,
        data: "method=get-folder-permissions&folder_id=" + 0,
        type: "GET",
        success: function (data) {
            if (data) {
                var jsonAnswer = JSON.parse(data);
                /*
                 Initiates the folder sorting if the user can edit the parent folders
                 */
                if (jsonAnswer.edit_folder == 1) {
                    jQuery("#folder_ul").each(makeSortable);
                }
            }
        }
    });

    $("#question-type").on("change", function () {
        loadDefaults(this);
    }).trigger("change");

    $(".add-answer").on("click", function (e) {
        e.preventDefault();
        var current_answer = parseInt($(".answer-row").length);
        if (max_answers > current_answer) {
            build_answer_row(0);
        }
    });

    //manages the item stems for Match Question types
    $(".add-item-stem").on("click", function (e) {
        e.preventDefault();
        var current_answer = parseInt($(".stem-row").length);
        if (max_answers > current_answer) {
            build_stem_row(0);
        }
    });

    $("#item-stem-table").on("change", ".add-match-correct", function(e) {
        e.preventDefault();
        updateMatchCorrectCollection();
    });

    //used for FNB question types to add correct answers to inputs
    $("#answer-table").on("click", ".fnb-correct-add", function(e) {
        e.preventDefault();
        var parent = $(this).closest(".exam-question-answer");
        sortable_id = $(parent).data("sortable-element-id");

        $("#add-correct-answers-modal span#add-correct-text").text(sortable_id);
        $("#add-correct-answers-modal").modal("show");
    });

    $("#correct-answers-modal-add-more").on("click", function (e) {
        e.preventDefault();
        manageCorrectAnswerFNB();
    });

    $("#correct-answers-modal-add-1").on("click", function (e) {
        e.preventDefault();
        manageCorrectAnswerFNB();
        $("#add-correct-answers-modal").modal("hide");
    });

    $("#answer-table").on("click", ".remove_correct_text_anchor", function(e) {
        e.preventDefault();
        removeItemAnswerArray($(this));
    });

    $("#answer-table").on("click", ".delete", function(e) {
        e.preventDefault();
        var parent = $(this).closest(".exam-question-answer");
        $(parent).remove();
    });

    $("#load-questions").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            renderFolderView(current_folder_id, false, "more");
        }
    });

    $("#load-previous-questions").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            renderFolderView(current_folder_id, false, "previous");
        }
    });

    $("#exam-question-bank-container").on("click", ".bank-folder", function() {
        var clicked = $(this);
        var folder_id = clicked.data("folder-id");
        renderFolderView(folder_id, true, "");
        folderNavigator(folder_id, "right");
    });

    $("#exam-question-bank-breadcrumbs").on("click", "a", function() {
        var clicked = $(this);
        var folder_id = clicked.data("id");
        renderFolderView(folder_id, true, "");
        folderNavigator(folder_id, "right");
    });

    $("#qbf-selector").on("click", ".folder-selector", function() {
        folder_id = $(this).data("id");
        $(".folder-selector").removeClass("folder-selected");
        $(this).addClass("folder-selected");
    });

    $("button#confirm-folder-move").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);
        var type = clicked.data("type");
        folder_selector_html($(this), folder_id, type);
        $("#parent-folder-modal").modal("hide");
    });

    $("button#cancel-folder-move").on("click", function(e) {
        e.preventDefault();
        $("#parent-folder-modal").modal("hide");
    });

    $("#qbf-selector").on("click", ".qbf-back-nav", function() {
        var folder_selected = $(this).data("folder-id");
        folderNavigator(folder_selected, "left");
    });

    $("#qbf-selector").on("click", ".sub-folder-selector", function() {
        var folder_selected = $(this).data("id");
        folderNavigator(folder_selected, "right");
    });

    $("#image-picker span.folder-image").on("click", function() {
        folderImageClicked($(this));
    });

    //disables the move icon from activating a submit when clicked.
    $("tr.answer-header a.btn.move").on("click", function(e) {
        e.preventDefault();
    });

    //this is the preview for FNB
    $("button#update-fnb-stem").on("click", function(e) {
        e.preventDefault();
        fnbPreview();
    });

    $("#toggle-all-question-bank").on("click", function (e) {
        e.preventDefault();
        toggleQuestionBankView($(this));
    });

    $(".btn.show-all-details").on("click", function(e) {
        e.preventDefault();
        toggleAllDetails(this);
    });

    $("#answer-table").on("click", ".delete", function(e) {
        e.preventDefault();
        var parent = $(this).closest(".exam-question-answer");
        $(parent).remove();
    });

    $("#question-search").keypress(function(event) {
        if (event.keyCode == 13)  {
            event.preventDefault();
        }

        setTimeout(function() {
            clearTimeout(timeout);
            timeout = window.setTimeout(renderFolderView(current_folder_id, false, ""), 700, false);
        },
        100);
    });

    $("#sub-folder-search .btn").click(function(e) {
        e.preventDefault();
        subFolderSearch($(this));
    });

    $("#answer-table").on("click", ".answer-correct", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#answer-table").on("change", ".answer-correct", function () {
        onAnswerCorrect($(this));
    });

    $("#answer-table").on("click", ".select-answer", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#answer-table").on("change", ".select-answer", function () {
        selectElement($(this));
    });

    $("#question-exam").on("change", "#grading_scheme", function (e) {
        e.preventDefault();
        manageAnswerWeight();
    });

    /*
     start delete answer section
     */
    $("#delete-answers-modal").on("show.bs.modal", function (e) {
        onDeleteAnswerModal();
    });

    $("#delete-answers-modal").on("hide.bs.modal", function (e) {
        $("#delete-answers-container").html("");
    });

    $("#delete-answers-modal-delete").on("click", function(e) {
        e.preventDefault();
        onDeleteAnswers();
    });
    /*
    end delete answer section
     */

    /*
    Start delete match stem section
     */
    $("#item-stem-table").on("click", ".select-match", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#item-stem-table").on("change", ".select-match", function () {
        onItemStemChange($(this));
    });

    $("#delete-item-stem-modal").on("show.bs.modal", function (e) {
        onDeleteItemStemModal()
    });

    $("#delete-item-stem-modal").on("hide.bs.modal", function (e) {
        $("#delete-match-container").html("");
    });

    $("#delete-match-modal-delete").on("click", function(e) {
        e.preventDefault();
        onDeleteMatchModal();
    });
    /*
     end  delete match stem section
     */

    $("#answer-table").on("click", ".answer-details", function(e) {
        e.preventDefault();
        answerDetailsClicked($(this));
    });

    //prevents CKEDITOR instances from losing data while dragging
    var panelList = $("#answer-table");

    panelList.sortable({
        axis: "y",
        start: function (event, ui) {
            if (shortname != "fnb") {
                var id_textarea = ui.item.find("textarea").attr("id");
                CKEDITOR.instances[id_textarea].destroy();
            }
        },
        stop: function (event, ui) {
            if (shortname != "fnb") {
                var id_textarea = ui.item.find("textarea").attr("id");
                CKEDITOR.replace(id_textarea);
            }
        },
        update: function(event, ui) {
            //updates the answer order on save
            var order_object = {};

            panelList.find("div[data-sortable-element-id]").each(function(index, value) {
                var new_order = index + 1;
                var answer_number = $(this).find(".answer-number");
                answer_number.html("Answer: " + new_order);
                var sortable_id = $(value).data("sortable-element-id");
                order_object[new_order] = sortable_id;
            });
            var temp_values = JSON.stringify(order_object);

            $("#answers_fnb_order").val(temp_values);
        }
    });

    /*
     * Enables the per page selector interface
     */
    var input_number_questions_pp = $("#number_questions_pp");
    if (input_number_questions_pp.length) {
        $("#number_questions_pp").inputSelector({
            rows:       1,
            columns:    6,
            data_text: [10, 25, 50, 100, 150, 200],
            modal:      0,
            header:     "Questions Per Page",
            form_name : "#per_page_nav",
            type:       "button",
            label:      "Per Page"
        });
    }

    $("#per_page_nav").on("click", ".selector-menu td.ui-timefactor-cell", function (e) {
        e.preventDefault();
        setTimeout(function () {
            question_limit = parseInt($("#number_questions_pp").data("value"));
            question_offset = 0;
            renderFolderView(current_folder_id, false, "");
        }, 100);
    });

    // objective-remove

    $("#exam-search").on("change", ".select-question", function() {
        toggleActionBtn();
    });

    /**
     * Group Questions
     */
    $("#group-question-modal").on("show", function () {
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
            selected_list_container: $("#selected_list_container"),
            parent_form: $("#group-question-modal-question"),
            width: 400,
            modal: true
        });
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

        var modal = $("#group-question-modal");
        var form = $("form#group-question-modal-question");
        var questions = $("form#exam-search").find("input[name=\"questions[]\"]").serializeArray();
        var new_group = form.find("input[name=\"new_group\"]:checked").val();

        modal.find(".alert").remove();
        switch (new_group) {
            case "1":
                var group_title = $.trim(form.find("input#group-title").val());
                if (group_title !== "") {
                    createGroup(group_title, questions).done(function (json) {
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
        renderFolderView(current_folder_id, false, "");
        toggleActionBtn();
    });

    function buildGroupModalBody() {
        var form = $("<form>").attr({id: "add-group-exam-modal", action: ENTRADA_URL+"/admin/exams/questions?section=api-group", method: "POST"}).addClass("form-horizontal");
        var label_radio_required = $("<label>").addClass("radio form-required");
        var input_radio = $("<input>").attr({type: "radio", name: "new_group"});
        var input_radio_createnew = input_radio.clone().prop("checked", true).val(1);
        var input_radio_addto = input_radio.clone().prop("checked", false).val(0);
        var label_group_title = $("<label>").addClass("control-label form-required").attr("for", "group-title").text("Group name");
        var input_group_title = $("<div>").addClass("controls").append($("<input>").attr({name: "group_title", id: "group-title", type: "text"}));
        var control_group_creategroup = $("<div>").addClass("control-group").addClass("control-group-sub").attr("id", "create-group-section").append(label_group_title, input_group_title);
        var control_group_createnew = $("<div>").addClass("control-group").append(label_radio_required.clone().text("Create new question group and add the selected question(s)").append(input_radio_createnew), control_group_creategroup);

        var label_choosequestiongroup = $("<label>").addClass("control-label form-required").attr("for", "choose-question-group-btn").text("Select group");
        var input_choosequestiongroup = $("<div>").addClass("controls entrada-search-widget").append($("<button>").addClass("btn btn-search-filter").attr({type: "button", id: "choose-question-group-btn"}).html("Browse Groups <i class=\"icon-chevron-down btn-icon pull-right\"></i>"));
        var control_group_addtogroup = $("<div>").addClass("control-group").addClass("control-group-sub").attr("id", "add-to-group-section").append(label_choosequestiongroup, input_choosequestiongroup);
        var control_group_addto = $("<div>").addClass("control-group").append(label_radio_required.clone().text("Add the selected question(s) to an existing question group").append(input_radio_addto), control_group_addtogroup);

        var choose_option = $("<h3>").text("How would you like to group the selected questions?");
        var selected_questions = $("<h3>").text("You have selected the following questions to group: ");
        var selected_questions_list = $("<ul>").attr("id", "selected-questions-to-group");
        $.each(questions_checked, function(key, value) {
            var question_text = $("div.exam-question[data-question-id=" + value.question_id + "] table tr.heading td .question_text").text();
            var question = $("<li>").html("ID: <strong>" + value.question_id + "</strong> / Ver: <strong>" + value.version_count + "</strong> - " + question_text);
            selected_questions_list.append(question);
        });
        form.append(selected_questions, selected_questions_list, choose_option, control_group_createnew, control_group_addto);

        return form;
    }

    function buildGroupModalFooter(state) {
        var footer = $("<div>").addClass("row-fluid");
        switch(state){
            case "complete":
                var btn_close = $("<a>").addClass("btn btn-primary btn-close").data("dismiss", "modal").attr("href", "#").text("Close");
                footer.append(btn_close);
                break;
            case "start":
            default:
                var btn_cancel = $("<a>").addClass("btn btn-default btn-close pull-left").data("dismiss", "modal").attr("href", "#").text("Cancel");
                var btn_save = $("<input>").attr({type: "submit", id: "group-questions-modal-save"}).addClass("btn btn-primary").val("Save");
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

    function answerDetailsClicked(clicked) {
        var active = jQuery(clicked).hasClass("active");
        if (active) {
            manageDetailsVisibility(clicked, "hide");
        } else {
            manageDetailsVisibility(clicked, "show");
        }
    }

    function subFolderSearch(button) {
        var value = button.data("value");
        var action;
        var value_text;

        if (value === "off") {
            action = 0;
            value_text = "on";
        } else {
            action = 1;
            value_text = "off";
        }

        var other_button = jQuery("#sub-folder-search .btn[data-value=\"" + value_text + "\"]");
        button.addClass("btn-success");
        other_button.removeClass("btn-success");

        $.ajax({
            url: API_URL,
            data: "method=update-sub-folder-search-preference&action=" + action,
            type: "POST",
            success: function (data) {
                renderFolderView(current_folder_id, false, "");
            }
        });
    }

    function folderImageClicked(clicked) {
        var image_selected = $(clicked).data("image-id");
        $("#image_id").val(image_selected);

        $("img.folder-select").removeClass("active");
        $(clicked).find("img.folder-select").addClass("active");
    }

    function onDeleteMatchModal() {
        var url = $("#delete-match-modal-question").attr("action");

        var answers_checked_verified = true;

        //todo check if the question version has been used already before allowing to delete.

        if (answers_checked_verified === true) {
            if ($("#msg-match-remove").hasClass("show")) {
                $("#msg-match-remove").removeClass("show");
                $("#msg-match-remove").addClass("hide");
            }

            $("#match-selected").removeClass("hide");
            $("#delete-match-modal-delete").removeClass("hide");

            $.each(answers_match_checked, function(key, value) {
                var container = $(".exam-question-match[data-sortable-element-id=" + value.element_id + "]");
                container.remove();
                $("#delete-item-stem-modal").modal("hide");
            });
        } else {
            $("#msg-match-remove").removeClass("hide").addClass("show");
        }
    }

    function onDeleteAnswerModal() {
        answers_to_delete = [];
        $("#msgs").html("");
        $("#answers-selected").addClass("hide");
        $("#no-answers-selected").addClass("hide");

        jQuery.each(answers_checked, function(key, value) {
            answers_to_delete.push(key);
        });

        //todo check if the question has been used already before allowing to delete.
        if (answers_to_delete.length > 0) {
            $("#answers-selected").removeClass("hide");
            $("#delete-answers-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $.each(answers_to_delete, function(index, element) {
                var list_question = document.createElement("li");
                var element_id = answers_checked[element].element_id;
                var container = $(".exam-question-answer[data-sortable-element-id=" + element_id + "]");

                answers_checked[element].question_id = $(container).data("question-id");
                answers_checked[element].version_id = $(container).data("version-id");

                var element_name = $(container).find(".answer-number").text();

                $(list_question).append(element_name);

                $(list).append(list_question);
            });
            $("#delete-answers-container").append(list);
        } else {
            $("#no-answers-selected").removeClass("hide");
            $("#delete-answers-modal-delete").addClass("hide");
        }
    }

    function onDeleteAnswers() {
        var url = $("#delete-answers-modal-question").attr("action");

        var answers_checked_verified = true;

        //todo check if the question version has been used already before allowing to delete.

        if (answers_checked_verified === true) {
            if ($("#msg-answer-remove").hasClass("show")) {
                $("#msg-answer-remove").removeClass("show");
                $("#msg-answer-remove").addClass("hide");
            }

            $("#answers-selected").removeClass("hide");
            $("#delete-answers-modal-delete").removeClass("hide");

            $.each(answers_checked, function(key, value) {
                var container = $(".exam-question-answer[data-sortable-element-id=" + value.element_id + "]");
                container.remove();
                $("#delete-answers-modal").modal("hide");
            });
        } else {
            $("#msg-answer-remove").removeClass("hide");
            $("#msg-answer-remove").addClass("show");
        }
    }

    function onItemStemChange(clicked) {
        var span = jQuery(clicked);
        var icon = jQuery(clicked).find(".icon-select-match");
        var element_id = icon.data("sortable-element-id");

        if (span.closest("table").hasClass("selected")) {
            span.closest("table").removeClass("selected");
            span.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("fa-check-square-o");

            if (answers_match_checked[element_id]) {
                delete answers_match_checked[element_id];
            }
        } else {
            span.closest("table").addClass("selected");
            span.addClass("selected");
            icon.addClass("fa-check-square-o").removeClass("fa-square-o");

            if (!answers_match_checked[element_id]) {
                answers_match_checked[element_id] = {
                    element_id: element_id
                };
            }
        }
    }

    function onDeleteItemStemModal() {
        answers_to_delete = [];
        $("#msgs").html("");
        $("#match-selected").addClass("hide");
        $("#no-match-selected").addClass("hide");

        jQuery.each(answers_match_checked, function(key, value) {
            answers_to_delete.push(key);
        });

        //todo check if the question has been used already before allowing to delete.
        if (answers_to_delete.length > 0) {
            $("#match-selected").removeClass("hide");
            $("#delete-match-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $.each(answers_to_delete, function(index, element) {
                var list_question = document.createElement("li");
                var element_id = answers_match_checked[element].element_id;
                var container = $(".exam-question-match[data-sortable-element-id=" + element_id + "]");

                answers_match_checked[element].match_id     = $(container).data("match-id");
                answers_match_checked[element].version_id   = $(container).data("version-id");

                var element_name = $(container).find(".match-number").text();

                $(list_question).append(element_name);

                $(list).append(list_question);
            });
            $("#delete-match-container").append(list);
        } else {
            $("#no-match-selected").removeClass("hide");
            $("#match-answers-modal-delete").addClass("hide");
        }
    }

    function onAnswerCorrect(clicked) {
        switch (shortname) {
            case "mc_v_m":
            case "mc_h_m":
            case "drop_m":
                var icon = jQuery(clicked).find(".icon-select-correct");
                if ($(icon).hasClass("incorrect")) {
                    $(icon).addClass("correct").removeClass("incorrect");
                } else {
                    $(icon).addClass("incorrect").removeClass("correct");
                }

                manageAnswerWeight();

                break;
            default :
                $(".icon-select-correct.correct").removeClass("correct").addClass("incorrect");
                var icon = jQuery(clicked).find(".icon-select-correct");
                $(icon).addClass("correct").removeClass("incorrect");
                break;
        }

        manageCorrectAnswer();
    }

    function selectElement(clicked) {
        var span = clicked;
        var icon = clicked.find(".icon-select-answer");
        var element_id = icon.data("sortable-element-id");

        if (span.closest("table").hasClass("selected")) {
            span.closest("table").removeClass("selected");
            span.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("fa-check-square-o");

            if (answers_checked[element_id]) {
                delete answers_checked[element_id];
            }
        } else {
            span.closest("table").addClass("selected");
            span.addClass("selected");
            icon.addClass("fa-check-square-o").removeClass("fa-square-o");

            if (!answers_checked[element_id]) {
                answers_checked[element_id] = {
                    element_id: element_id
                };
            }
        }
    }

    function manageAnswerWeight() {
        var correct_percent_split, incorrect_percent_split;
        var grading_scheme = jQuery("#grading_scheme").val();

        if (grading_scheme != "full") {
            var answer_total_count = jQuery(".exam-question-answer").length;
            var answer_correct = jQuery(".exam-question-answer .icon-select-correct.correct");
            var answer_incorrect = jQuery(".exam-question-answer .icon-select-correct.incorrect");
            var answer_correct_count = jQuery(answer_correct).length;
            if (grading_scheme === "partial" || grading_scheme === "penalty") {
                if (answer_correct_count > 0) {
                    correct_percent_split   = Math.round((100 / answer_total_count + 0.00001) * 100) / 100;
                    incorrect_percent_split = correct_percent_split;
                    //correct_percent_split   = Math.round((100 / answer_correct_count + 0.00001) * 100) / 100;
                    //incorrect_percent_split = -Math.abs(Math.round((100 / answer_total_count + 0.00001) * 100) / 100);
                } else {
                    correct_percent_split   = 0;
                    incorrect_percent_split = 0;
                }
            }
            jQuery.each(answer_correct, function(key, value) {
                var id = jQuery(value).data("sortable-element-id");
                jQuery("#question_answer_weight_" + id).val(correct_percent_split);
            });

            jQuery.each(answer_incorrect, function(key, value) {
                var id = jQuery(value).data("sortable-element-id");
                jQuery("#question_answer_weight_" + id).val(incorrect_percent_split);
            });
        }
    }

    function toggleQuestionBankView(clicked) {
        var active = false;

        if ($(clicked).hasClass("active")) {
            $(clicked).removeClass("active");
        //    $(clicked).find("i").removeClass("white-icon");
        } else {
            $(clicked).addClass("active");
        //    $(clicked).find("i").addClass("white-icon");
            active = true;
        }

        var item_details = jQuery(".question-details");

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

    function toggleAllDetails(clicked) {
        var answer_details = jQuery("tr.answer-details-row");

        if (jQuery(clicked).hasClass("active")) {
            jQuery.each(answer_details, function (key, detail_row ) {
                manageDetailsVisibility(detail_row, "hide");
            });
        } else {
            jQuery.each(answer_details, function (key, detail_row ) {
                manageDetailsVisibility(detail_row, "show");
            });
        }

        jQuery(clicked).toggleClass("active");
        //jQuery(clicked).find("i").toggleClass("white-icon");
    }

    function manageDetailsVisibility(detail_row, state) {
        var parent = jQuery(detail_row).closest(".exam-question-answer");
        var answer_details = jQuery(parent).find("tr.answer-details-row");

        if (state === "show") {
            answer_details.removeClass("hide");
            parent.find(".answer-details").addClass("active");
            //parent.find(".answer-details i").addClass("white-icon");
        } else {
            answer_details.addClass("hide");
            parent.find(".answer-details").removeClass("active");
            //parent.find(".answer-details i").removeClass("white-icon");
        }

        var text_area = jQuery(answer_details).find("textarea");
        text_area.css("height", "28px");
    }

    function updateMatchCorrectCollection() {
        var add_match_correct = jQuery(".add-match-correct");

        var stem_collection = [];
        jQuery.each(add_match_correct, function(key, value) {
            var stem_order      = key + 1;
            var stem_correct    = parseInt(jQuery(value).val());
            stem_correct = stem_correct ? stem_correct : 0;

            var stem_object = {"stem_order": stem_order, "stem_correct": stem_correct};
            stem_collection.push(stem_object);
        });

        jQuery("#match_stem_correct").val(JSON.stringify(stem_collection));
    }

    function folder_selector_html(selected, folder_selected, type) {
        var temp_folder_id = $("#folder_id").val();
        if (temp_folder_id != folder_selected) {
            $.ajax({
                url: FOLDER_API_URL,
                data: "method=get-folder-view&folder_id=" + folder_selected,
                type: "GET",
                success: function (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        var folder_render = jsonAnswer.render;
                        var parent_folder_selector = $("#selected-parent-folder");
                        var folder_image = $(parent_folder_selector).find(".folder-image");
                        var folder_title = $(parent_folder_selector).find(".folder-title");
                        var button = $(parent_folder_selector).find("#select_parent_folder_button");
                        folder_image.remove();
                        folder_title.remove();
                        $(button).before(folder_render);
                    }
                }
            });
        }

        switch (type) {
            case "folder":
                $("#parent_folder_id").attr("value", folder_selected);
                break;
            case "question":
                $("#folder_id").attr("value", folder_id);
                break;
        }
    }

    function fnbPreview() {
        var parent_div      = jQuery("#question-text").parent().parent();
        var visual_editor   = jQuery("#fnb-stem-visual");
        var fnb_editor      = jQuery("#fnb-stem-visual #fnb_editor");
        var blank_choices   = jQuery("#fill-blank-choices");
        var fnb_type        = "_?_";

        if (jQuery(parent_div).hasClass("hide")) {
            //translate back to text from collection
            var ck_text = "";
            for (var property in fnb_collection) {
                if (fnb_collection.hasOwnProperty(property)) {
                    //build input box
                    var object = fnb_collection[property];

                    if (object["type"] == "input") {
                        ck_text += "_?_";
                    } else {
                        ck_text += object["text"];
                    }
                }
            }

            CKEDITOR.instances["question-text"].setData(ck_text);
            jQuery(parent_div).removeClass("hide");
            jQuery(visual_editor).addClass("hide");

        } else {
            fnb_collection = {};
            jQuery(parent_div).addClass("hide");
            jQuery(visual_editor).removeClass("hide");

            var stem    = CKEDITOR.instances["question-text"].getData();
            var stem_array = stem.split(fnb_type);
            var stem_text_length = stem_array.length;

            fnb_editor.html("");
            blank_choices.html("");

            var object_count = 0;

            jQuery.each(stem_array, function(key, span) {
                var number = key + 1;
                if (span != "") {
                    //add text element to page
                    jQuery(fnb_editor).append( "<span>" + span + "</span>");

                    //adds item to object
                    var object = {"text" : span, "type": "text", "order": object_count};
                    fnb_collection[object_count] = object;
                    object_count++;
                }

                if (number < stem_text_length) {
                    //add input element
                    var input = "<input type=\"text\" class=\"fnb_input\" disabled=\"disabled\" placeholder=\"" + number + "\" />";

                    jQuery(fnb_editor).append("<input type=\"text\" class=\"fnb_input\" disabled=\"disabled\" placeholder=\"" + number + "\" />");

                    //adds item to object
                    var object = {"text" : number, "type": "input", "order": object_count};
                    fnb_collection[object_count] = object;
                    object_count++;
                }
            });
        }
    }

    function getShortname() {
        $.ajax({
            url: API_URL,
            data: "method=get-question-type-shortname&question_type_id=" + question_type_id,
            type: "POST",
            success: function (data) {
                var jsonAnswer = JSON.parse(data);

                if (jsonAnswer.status == "success") {
                    shortname = jsonAnswer.data.shortname;
                }
            }
        });
    }

    function loadDefaults(clicked) {
        var selected_question_type = $(clicked).val();

        $.ajax({
            url: API_URL,
            data: "method=get-question-type-shortname&question_type_id=" + selected_question_type,
            type: "POST",
            success: function(data) {
                var jsonAnswer = JSON.parse(data);

                if (jsonAnswer.status == "success") {
                    $("#update-fnb-stem").addClass("hide");
                    $("#item-section").addClass("hide");
                    shortname = jsonAnswer.data.shortname;
                    switch (shortname) {
                        case "mc_h_m" :
                        case "mc_v_m" :
                        case "drop_m" :
                            $("#custom_grading").removeClass("hide");
                            $("td.grading_weight").removeClass("hide");
                            $("#answer-section").removeClass("hide");
                            jQuery("tr.answer-details-row td.rationale").attr("colspan", 1);
                            break;
                        case "mc_h" :
                        case "mc_v" :
                        case "drop_s" :
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#answer-section").removeClass("hide");
                            break;
                        case "short" :
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#answer-section").addClass("hide");
                            break;
                        case "essay" :
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#answer-section").addClass("hide");
                            break;
                        case "text" :
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#answer-section").addClass("hide");
                            break;
                        case "fnb" :
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#update-fnb-stem").removeClass("hide");
                            break;
                        case "match":
                            $("#custom_grading").addClass("hide");
                            $("td.grading_weight").addClass("hide");
                            $("#item-section").removeClass("hide");
                            $("#answer-section").removeClass("hide");
                            break;
                    }

                    switch (shortname) {
                        case "mc_h" :
                        case "mc_h_m" :
                            max_answers = 5;
                            build_answer_grid();
                            break;
                        case "mc_v" :
                        case "mc_v_m" :
                            max_answers = 26;
                            build_answer_grid();
                            break;
                        case "drop_s" :
                        case "drop_m" :
                        case "fnb":
                            if (existing_question === false) {
                                $("#answer-table div").remove();
                            }
                            min_answers = 1;
                            max_answers = 10;
                            build_answer_grid();

                            break;
                        case "match":
                            min_answers = 1;
                            max_answers = 26;
                            build_stem_grid();
                            build_answer_grid();
                            break;
                    }

                    question_type_controls.empty();

                    remove_excess_rows();

                }
            }
        });
    }
    
    function build_answer_grid () {
        if (!$("#answer-table").length > 0) {
            build_answer_table();
        }

        if (!$(".answer-row").length > 0) {
            for (i = 1; i <= min_answers; i++) {
                build_answer_row(i);
            }
        }
    }

    function build_stem_grid () {
        if (!$("#item-stem-table").length > 0) {
            build_stem_table();
        }

        if (!$(".stem-row").length > 0) {
            for (i = 1; i <= min_answers; i++) {
                build_stem_row(i);
            }
        }
    }

    function build_answer_row(answer_number) {
        var html_row;
        var details = false;

        if (answer_number == 0) {
            answer_number = parseInt($(".answer-row").length) + 1;
        }

        if (jQuery(".show-all-details").hasClass("active")) {
            var details = true;
        }

        jQuery.ajax({
            url: API_URL,
            data: "method=get-answer-row&answer_number=" + answer_number + "&question_type_name=" + shortname,
            type: "POST",
            success: function (data) {
                jQuery("#msgs").html("");
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        html_row = jsonAnswer.answer_row;

                        var item_on_screen = $("#answer-table").find(".exam-question-answer[data-sortable-element-id=" + (answer_number + 1) + "]");
                        if (item_on_screen.length) {
                            item_on_screen.prepend(html_row);
                        } else {
                            $("#answer-table").append(html_row);
                        }

                        if (shortname != "fnb") {
                            CKEDITOR.replace("question_answer_" + answer_number);
                        }
                        if (shortname === "match") {
                            var html_option = "<option value=\"" + answer_number + "\">" + answer_number + "</option>";
                            jQuery(".add-match-correct").append(html_option);
                        }

                        if (shortname === "mc_h_m" || shortname === "mc_v_m" || shortname === "drop_m") {
                            manageAnswerWeight();
                        }
                        if (details === true) {
                            // Activate details view since the overall details is activated.
                            var answer_details = jQuery("div.exam-question-answer[data-sortable-element-id=" + answer_number + "]").find("tr.answer-details-row");
                            manageDetailsVisibility(answer_details, "show");
                        }
                    } else if (jsonAnswer.status == "error") {
                        jQuery("#msgs").append("<p>error</p>");
                    } else if (jsonAnswer.status == "notice") {
                        jQuery("#msgs").append("<p>notice</p>");
                    }
                } else {
                    return false;
                }
            }
        });
    }

    function build_stem_row(stem_number) {
        var html_row;

        if (stem_number == 0) {
            stem_number = parseInt($(".stem-row").length) + 1;
        }

        var answer_number = parseInt($(".exam-question-answer").length);

        jQuery.ajax({
            url: API_URL,
            data: "method=get-stem-row&stem_number=" + stem_number + "&question_type_name=" + shortname,
            type: "POST",
            success: function (data) {
                jQuery("#msgs").html("");
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        html_row = jsonAnswer.match_row;
                        $("#item-stem-table").append(html_row);
                        CKEDITOR.replace("item_stem_" + stem_number);

                        var stem = jQuery(".exam-question-match[data-sortable-element-id=\"" + stem_number + "\"]");
                        var stem_option = stem.find(".add-match-correct");
                        var stem_option_count = stem_option.length;

                        if (stem_option_count != answer_number + 1) {
                            //update added rows if they're not there
                            for (var i = 1; i <= answer_number; i++ ) {
                                var html_option = "<option value=\"" + i + "\">" + i + "</option>";
                                jQuery(stem_option).append(html_option);
                            }
                        }

                    } else if (jsonAnswer.status == "error") {
                        jQuery("#msgs").append("<p>error</p>");
                    } else if (jsonAnswer.status == "notice") {
                        jQuery("#msgs").append("<p>notice</p>");
                    }
                } else {
                    return false;
                }
            }
        });
    }

    function build_answer_table () {
        var answer_table = document.createElement("table");
        var answer_table_head = document.createElement("thead");
        var answer_table_head_row = document.createElement("tr");
        var answer_table_answer_label_th = document.createElement("th");
        var answer_table_answer_th = document.createElement("th");
        var answer_table_pass_th = document.createElement("th");
        var answer_table_body = document.createElement("tbody");

        $(answer_table).addClass("table table-striped table-bordered").attr({id: "answer-table"});
        $(answer_table_answer_label_th).attr({width: "14%"});
        $(answer_table_answer_th).html("Answer Text").attr({width: "auto"});
        $(answer_table_pass_th).html("Minimum Pass").attr({width: "16%"});

        $(answer_table_head_row).append(answer_table_answer_label_th).append(answer_table_answer_th).append(answer_table_pass_th);
        $(answer_table_head).append(answer_table_head_row);
        $(answer_table).append(answer_table_head);
        $(answer_table).append(answer_table_body);
        $("#answer-section").append(answer_table);
    }

    function build_stem_table () {
        var answer_table = document.createElement("table");
        var answer_table_head = document.createElement("thead");
        var answer_table_head_row = document.createElement("tr");
        var answer_table_answer_label_th = document.createElement("th");
        var answer_table_answer_th = document.createElement("th");
        var answer_table_pass_th = document.createElement("th");
        var answer_table_body = document.createElement("tbody");

        $(answer_table).addClass("table table-striped table-bordered").attr({id: "item-stem-table"});
        $(answer_table_answer_label_th).attr({width: "14%"});
        $(answer_table_answer_th).html("Answer Text").attr({width: "auto"});
        $(answer_table_pass_th).html("Minimum Pass").attr({width: "16%"});

        $(answer_table_head_row).append(answer_table_answer_label_th).append(answer_table_answer_th).append(answer_table_pass_th);
        $(answer_table_head).append(answer_table_head_row);
        $(answer_table).append(answer_table_head);
        $(answer_table).append(answer_table_body);
        $("#item-section").append(answer_table);
    }

    function remove_excess_rows () {
        var total_answers = parseInt($(".answer-row").length);
        if (total_answers > max_answers) {
            $("#answer-table tbody tr").slice(max_answers, total_answers).remove();
        }
    }

    $(".exam-authors").on("click", ".remove-permission", function(e) {
        e.preventDefault();
        removeAuthorList($(this));
    });

    // exam-objectives-section checked-objective

    /* objective controls */
    $("#exam-question-objective-list").on("click", ".objective-remove", function() {
        clickObjectiveRemove($(this));
    });

    $("#exam-objectives-section").on("click", ".objective-remove", function() {
        clickObjectiveRemove($(this));
    });

    $("#exam-objectives-section").on("change", ".checked-objective", function() {
        clickCheckedObjective($(this));
    });

    $("#exam-question-objective-list").on("change", ".checked-mapped", function() {
        changeCheckedMapped($(this));
    });

    function removeAuthorList(remove_permission_btn) {
        $.ajax({
            url: API_URL,
            data: "method=remove-permission&author_id=" + remove_permission_btn.data("author-id"),
            type: "POST",
            success: function(data) {
                var jsonAnswer = JSON.parse(data);
                if (jsonAnswer.status == "success") {
                    var li = remove_permission_btn.closest("li");
                    var ul = remove_permission_btn.closest("ul");
                    var div = remove_permission_btn.closest("div");
                    $(li).remove();
                    var current_lis = $(ul).children("li");

                    if (current_lis.length < 1) {
                        $(div).addClass("hide");
                    }
                } else {
                    alert(jsonAnswer.data);
                }
            }
        });
    }

    function changeCheckedMapped(clicked) {
        var id = jQuery(clicked).val();
        var qrow = jQuery("#qrow").val();
        // parents will return all sets above that objective, which for anything other than curriculum objectives will be an array
        // this grabs all parents above the object and then fetches the list from the immediate (last) parent
        var sets_above = jQuery(clicked).parents(".mapped-list");
        var list = jQuery(sets_above[sets_above.length-1]).attr("data-importance");

        var title = jQuery(".mapped_objective_" + id).attr("data-title");
        var description = jQuery(".mapped_objective_" + id).attr("data-description");
        if (jQuery(clicked).is(":checked")) {
            mapObjective(id,title,description,list,false);
            addObjective(id, qrow);
        } else {
            var importance = "checked";
            if (list == "flat") {
                importance = "clinical";
            }
            if (jQuery(".mapped_objective_" + id).is(":checked")) {
                mapObjective(id,title,description,list,false);
                addObjective(id, qrow);
            } else {
                unmapObjective(id,list,importance);
                removeObjective(id, qrow);
            }
        }
    }

    function clickObjectiveRemove(clicked) {
        var id = jQuery(clicked).data("id");
        var qrow = jQuery("#qrow").val();
        var list = jQuery(".mapped_objective_" + id).parent().attr("data-importance");
        var importance = "checked";
        if (list == "flat") {
            importance = "clinical";
        }
        unmapObjective(id,list,importance);
        removeObjective(id, qrow);
        return false;
    }

    function clickCheckedObjective(clicked) {
        var id = jQuery(clicked).val();
        var qrow = jQuery("#qrow").val();
        // parents will return all sets above that objective, which for anything other than curriculum objectives will be an array
        // this grabs all parents above the object and then fetches the list from the immediate (last) parent
        var sets_above = jQuery(clicked).parents(".objective-set");
        var list = jQuery(sets_above[sets_above.length-1]).attr("data-list");

        var title = jQuery("#objective_title_" + id).attr("data-title");
        var description = jQuery("#objective_" + id).attr("data-description");
        if (jQuery(clicked).is(":checked")) {
            mapObjective(id, title, description, list, true);
            addObjective(id, qrow);
        } else {
            var importance = "checked";
            if (list == "flat") {
                importance = "clinical";
            }
            unmapObjective(id,list,importance);
            removeObjective(id, qrow);
        }
    }

    function manageCorrectAnswer() {
        var initial_correct = jQuery(".icon-select-correct.correct");
        correct_answer = [];

        jQuery.each(initial_correct, function(key, value) {
            var element_id = jQuery(value).data("sortable-element-id");
            correct_answer.push(element_id);
        });

        var correct_string = JSON.stringify(correct_answer);
        jQuery("#correct-answer-input").val(correct_string);
    }

    function manageCorrectAnswerFNB() {
        var text            = jQuery("#add-correct-answer").val();
        var answer_div      = jQuery(".exam-question-answer[data-sortable-element-id=\"" + sortable_id + "\"]").find(".span8");
        if (!fnb_answers[sortable_id]) {
            var answer_array = [];
            answer_array.push(text);
            fnb_answers[sortable_id] = answer_array;
            jQuery(answer_div).append("<span class=\"correct-answer-fnb label label-info\">" + text + "</span>");
        } else {
            var answer_array = fnb_answers[sortable_id];
            var found = jQuery.inArray(text, answer_array) > -1;

            if (!found) {
                answer_array.push(text);
                fnb_answers[sortable_id] = answer_array;
                jQuery(answer_div).append("<span class=\"correct-answer-fnb label label-info\">" + text + "</span>");
            }
        }
        jQuery("#correct_answers_fnb").val(JSON.stringify(fnb_answers));
    }

    function removeItemAnswerArray(clicked) {
        var correct_answer_span = jQuery(clicked).closest(".correct-answer-fnb");
        var div                 = jQuery(clicked).closest("div.exam-question-answer");

        var text        = jQuery(correct_answer_span).text();
        var position_id = jQuery(div).data("sortable-element-id");

        var array = fnb_answers[position_id];
        var index = array.indexOf(text);

        if (index != -1) {
            //remove item at index
            array.splice(index, 1);
            fnb_answers[position_id] = array;
        }
        jQuery(correct_answer_span).remove();
        jQuery("#correct_answers_fnb").val(JSON.stringify(fnb_answers));
    }

    function folderNavigator(folder_selected, direction) {
        var parent_folder_id = jQuery("#parent_folder_id").val();
        if (ajax_in_progress === false) {
            ajax_in_progress = true;
            jQuery.ajax({
                url: FOLDER_API_URL,
                data: "method=get-sub-folder-selector&folder_id=" + folder_selected + "&parent_folder_id=" + parent_folder_id,
                type: "GET",
                success: function (data) {
                    var jsonAnswer      = JSON.parse(data);
                    var folder_count    = jsonAnswer.folder_count;
                    var current_folder  = jQuery(".qbf-folder.active");
                    var sub_folders     = document.createElement("span");
                    sub_folders.setAttribute("id", "qbf-folder-" + folder_selected);
                    sub_folders.setAttribute("class", "qbf-folder active");
                    if (direction === "left") {
                        jQuery(sub_folders).animate({
                            right: "250"
                        }, 0);
                    } else {
                        jQuery(sub_folders).animate({
                            left: "250"
                        }, 0);
                    }

                    if (jsonAnswer.status_folder == "success") {
                        var subfolder_html = jsonAnswer.subfolder_html;
                        jQuery(sub_folders).append(subfolder_html);
                        jQuery("#qbf-selector").append(sub_folders);
                        jQuery(current_folder).removeClass("active");
                        var new_folder = jQuery("#qbf-folder-" + folder_selected);

                        if (direction === "left") {
                            jQuery(current_folder).animate({
                                left: "250"
                            }, 350, function() {
                                jQuery(current_folder).remove();
                            });

                            jQuery(new_folder).animate({
                                right: "0"
                            }, 350);
                        } else {
                            jQuery(current_folder).animate({
                                right: "250"
                            }, 350, function() {
                                jQuery(current_folder).remove();
                            });

                            jQuery(new_folder).animate({
                                left: "5"
                            }, 350);
                        }
                    }

                    if (jsonAnswer.status_nav == "success") {
                        jQuery("#qbf-nav").html(jsonAnswer.nav_html);
                    }

                    if (jsonAnswer.status_title == "success") {
                        jQuery("#qbf-title").html(jsonAnswer.title_html);
                    }

                    ajax_in_progress = false;

                    var folder_selector_height = jQuery(".folder-selector").outerHeight();
                    var adjusted_height = folder_count * folder_selector_height + 110;

                    if (adjusted_height < 350) {
                        adjusted_height = 350;
                    }

                    jQuery("#qbf-selector").css("height", adjusted_height + "px");
                }
            });
        }
    }
});

function addObjective(id, rownum) {
    var ids = id;

    if (jQuery("div.objectives-empty-notice").is(":visible")) {
        jQuery("div.objectives-empty-notice").hide();
    }

    var alreadyAdded = false;
    jQuery("input.objective_ids_" +rownum).each(
        function (key, value) {
            if (!ids) {
                ids = jQuery(this).val();
            } else {
                ids += "," + jQuery(this).val();
            }
            if (jQuery(this).val() == id) {
                alreadyAdded = true;
            }
        }
    );

    jQuery("#objective_ids_string_" + rownum).val(ids);

    if (!alreadyAdded) {
        var input_parent    = jQuery("#objectives_" + rownum + "_list");
        var new_input       = jQuery(document.createElement("input"));
        new_input.attr("type", "hidden");
        new_input.attr("class", "objective_ids_" + rownum);
        new_input.attr("id", "objective_ids_" + rownum + "_" + id);
        new_input.val(id);
        new_input.attr("name", "objective_ids_" + rownum + "[]");

        input_parent.append(new_input);
    }
}

function removeObjective(id, rownum) {
    var ids = "";

    jQuery("#objective_ids_" + rownum + "_" + id).remove();

    jQuery("input.objective_ids_" +rownum).each(
        function () {
            if (!ids) {
                ids = jQuery(this).val();
            } else {
                ids += "," + jQuery(this).val();
            }
        }
    );

    if (!jQuery("div.objectives-empty-notice").is(":visible") && !ids) {
        jQuery("div.objectives-empty-notice").show();
    }

    jQuery("#objective_ids_string_" +rownum).val(ids);
}

function unmapObjective(id,list,importance) {
    var key = jQuery.inArray(id,mapped);
    if (key != -1) {
        mapped.splice(key,1);
    }
    var lkey = jQuery.inArray(id,listed);
    if (lkey === -1) {
        importance = "checked";
    }

    jQuery("#" + importance + "_objectives_select option[value=\"" + id+ "\"]").remove();
    jQuery("#check_objective_" + id).prop("checked",false);
    jQuery("#check_mapped_" + id).prop("checked",false);
    jQuery("#text_container_" + id).remove();
    if (lkey === -1) {
        jQuery(".mapped_objective_" + id).remove();
    }
    var mapped_siblings = false;
    jQuery("#objective_" + id).siblings("li.objective-container").each(function() {
        var oid = jQuery(this).attr("data-id");
        if (jQuery("#check_objective_" +oid).prop("checked")){
            mapped_siblings = true;
        }
    });
    jQuery("#objective_" + id).parents(".objective-list").each(function() {
        var mapped_cousins = false;
        var pid = jQuery(this).attr("data-id");
        if (mapped_siblings == false) {
            jQuery("#objective_list_" +pid+ " > li").each(function() {
                var cid = jQuery(this).attr("data-id");
                if (jQuery("#check_objective_" +cid).prop("checked")){
                    mapped_cousins = true;
                }
            });
            if (mapped_cousins == false) {
                jQuery("#check_objective_" +pid).prop("checked",false);
                jQuery("#check_objective_" +pid).prop("disabled",false);
            }
        }
    });
}

function mapObjective(id, title, description, list, create) {
    var key = jQuery.inArray(id, mapped);
    var lkey = jQuery.inArray(id, listed);
    if (key != -1) return;
    var importance = "checked";
    if (list === undefined || !list) {
        list = "flat";
    }
    if (list == "flat") {
        importance = "clinical";
    }

    if (description === undefined || !description || description == null || description == "null") {
        description = "";
    }

    if (create && lkey == -1 && key == -1) {
        var li = jQuery(document.createElement("li"));
        var controls = 	jQuery(document.createElement("div"));
        var rm = jQuery(document.createElement("i"));
        var strong_title = jQuery(document.createElement("strong"));
        var desc = jQuery(document.createElement("div"));
        var sets_above = jQuery("#objective_" + id).parents(".objective-set");
        var set_id = jQuery(sets_above[sets_above.length-1]).attr("data-id");
        var set_name = jQuery("#objective_title_" + set_id).attr("data-title");

        li.attr("class","mapped-objective mapped_objective_" + id)
        li.attr("data-title", title)
        li.attr("data-description", description);
        controls.attr("class", "exam-question-objective-controls");
        rm.attr("data-id", id)
        rm.attr("class", "icon-remove-sign pull-right objective-remove list-cancel-image")
        rm.attr("id", "objective_remove_" + id);
        desc.attr("class", "objective-description");
        desc.attr("data-description", description);

        strong_title.html(title);
        jQuery(controls).append(rm);
        jQuery(li).append(controls);
        jQuery(li).append(strong_title);

        if (set_name) {
            jQuery(desc).html("From the Curriculum Tag Set: <strong>" + set_name + "</strong><br/>");
        }
        jQuery(desc).append(description);

        jQuery(li).append(desc);

        jQuery(".mapped_exam_question_objectives").append(li);
        jQuery(".mapped_exam_question_objectives .display-notice").remove();

        jQuery("#objective_" + id).parents(".objective-list").each(function(key, value) {
            var parent_id = jQuery(this).attr("data-id");
            jQuery("#check_objective_" + parent_id).prop("checked",true);
            jQuery("#check_objective_" + parent_id).prop("disabled",true);
        });

        if (jQuery("#exam-question-toggle").hasClass("collapsed")) {
            jQuery("#exam-question-toggle").removeClass("collapsed");
            jQuery("#exam-question-toggle").addClass("expanded");
            var d = jQuery("#exam-question-toggle").next();
            jQuery(d).slideDown();
        }
        if (!jQuery("#exam-question-list-wrapper").is(":visible")) {
            jQuery("#exam-question-list-wrapper").show();
        }
        list = "exam-question";
    }

    jQuery("#check_objective_" + id).prop("checked",true);
    jQuery("#check_mapped_" + id).prop("checked",true);
    if (jQuery("#" + importance + "_objectives_select option[value=\"" + id + "\"]").length == 0) {
        var option = jQuery(document.createElement("option"));
        option.val(id);
        option.attr("selected","selected");
        option.html(title);
        jQuery("#" + importance + "_objectives_select").append(option);
    }

    jQuery("#objective_" + id).parents(".objective-list").each(function() {
        var id = jQuery(this).attr("data-id");
        jQuery("#check_objective_" + id).prop("checked",true);
        jQuery("#check_objective_" + id).prop("disabled",true);
    });

    mapped.push(id);
}


function isAQuestionSelected() {
    var $selected = false;
    jQuery("#exam-search").find(".select-question").each(function() {
        if (jQuery(this).hasClass("selected")) {

            return $selected = true;
        }
    });
    return $selected;
}

function toggleActionBtn() {
    if (isAQuestionSelected() === false) {
        jQuery(".btn-actions").prop("disabled", true);
    } else {
        jQuery(".btn-actions").prop("disabled", false);
    }
}

function renderFolderView(folder_id, popstate, offset) {
    // resets check variable on folder change
    questions_checked = {};
    jQuery("#exam-questions-loading").removeClass("hide");

    var sub_folder_search;

    var sub_folder_search_value = jQuery("#sub-folder-search .btn-success").data("value");
    if (sub_folder_search_value === "on") {
        sub_folder_search = 1;
    } else {
        sub_folder_search = 0;
    }

    var search_term = jQuery("#question-search").val();

    var exclude_question_ids = {exclude_question_ids : []};
    var question_ids = [];

    if (jQuery("#search-targets-exam").length > 0) {
        total_questions = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-exam").serialize();
    }

    if (question_offset < 0) {
        question_offset = 0;
    }

    jQuery("input.add-question:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), question_ids) == -1) {
            question_ids.push(jQuery(element).val());
        }
    });

    // Moves the offset for the next search
    if (offset == "more") {
        question_offset = (parseInt(question_limit) + parseInt(question_offset));
    } else if (offset == "previous") {
        question_offset = (parseInt(question_offset) - parseInt(question_limit));
    } else {
        question_offset = 0;
    }

    if (question_offset > 0) {
        jQuery("#load-previous-questions").prop("disabled", false);
    } else {
        jQuery("#load-previous-questions").prop("disabled", true);
    }

    exclude_question_ids = {exclude_question_ids : question_ids};


    var show_all_button = jQuery("#toggle-all-question-bank");
    if (jQuery(show_all_button).hasClass("active")) {
        var active_details = 1;
    } else {
        active_details = 0;
    }

    var data_string = "method=get-questions&search_term=" + search_term + "&offset=" + question_offset + "&limit=" + question_limit + "&view=" + VIEW_PREFERENCE +
            //(group_questions["group_questions"].length > 0 ? "&" + jQuery.param(group_questions) : "") +
        (exclude_question_ids["exclude_question_ids"].length > 0 ? "&" + jQuery.param(exclude_question_ids) : "") +
        (jQuery("#exam_id").val() !== "" ? "&exam_id=" + jQuery("#exam_id").val() : "") +
        (typeof filters !== "undefined" ? "&" + filters : "") +
        (typeof folder_id !== "undefined" ? "&folder_id=" + folder_id : "" ) +
        (typeof sub_folder_search !== "undefined" ? "&sub_folder_search=" + sub_folder_search : "" ) +
        (typeof sort_direction !== "undefined" ? "&sort_direction=" + sort_direction : "" ) +
        (typeof sort_column !== "undefined" ? "&sort_column=" + sort_column : "" ) +
        "&active_details=" + active_details +
        "&exam_mode=false";


    if (xhr) {
        xhr.abort();
    }
    xhr = jQuery.ajax({
        url: API_URL,
        data: data_string,
        type: "GET",
        success: function (data) {
            jQuery("#msgs").html("");
            if (data) {
                var jsonAnswer = JSON.parse(data);
                var question_count = 0;
                // Folder section
                if (jsonAnswer.status_question == "success") {
                    var html_list       = jsonAnswer.question_data.html_list;
                    var html_details    = jsonAnswer.question_data.html_details;
                    question_count      = jsonAnswer.question_count;
                    total_questions     = jsonAnswer.total_questions;


                    jQuery("#question-table-container #questions-table tbody").html(html_list);
                    jQuery("#question-detail-container").html(html_details);
                } else if (jsonAnswer.status_question == "error") {
                    jQuery("#msgs").append("<p>" + jsonAnswer.status_question_error + "</p>");
                } else if (jsonAnswer.status_question == "notice") {
                    jQuery("#question-table-container #questions-table tbody").html("<tr><td colspan=\"5\">" + jsonAnswer.status_question_notice + "</td></tr>");
                    jQuery("#question-detail-container").html(jsonAnswer.status_question_notice);
                } else if (jsonAnswer.status_question == "root_folder") {
                    jQuery("#question-table-container #questions-table tbody").html("");
                    jQuery("#question-detail-container").html("");
                }

                jQuery("#exam-questions-loading").addClass("hide");

                if (VIEW_PREFERENCE === "detail") {
                    jQuery("#question-detail-container").removeClass("hide");
                    jQuery("#question-table-container").addClass("hide");
                } else {
                    jQuery("#question-detail-container").addClass("hide");
                    jQuery("#question-table-container").removeClass("hide");
                }

                var question_num_1 = question_offset + 1;
                var question_num_2 = question_offset + question_count;

                if (question_count != 0) {
                    jQuery("#questions-loaded-display").html("Showing " + question_num_1 + " - " +  question_num_2 + " of " + total_questions + " total questions");
                    jQuery("#questions-loaded-display").show();
                } else {
                    jQuery("#questions-loaded-display").hide();
                }

                if (question_num_2 >= total_questions || question_limit > total_questions) {
                    jQuery("#load-questions").prop("disabled", true);
                } else {
                    jQuery("#load-questions").prop("disabled", false);
                }

                if (question_count > 0) {
                    jQuery("#exams-no-results").addClass("hide");
                }

                // Breadcrumbs
                if (jsonAnswer.status_breadcrumbs == "success") {
                    var html_breadcrumbs = jsonAnswer.breadcrumb_data;
                    jQuery("#exam-question-bank-breadcrumbs").html(html_breadcrumbs);
                } else if (jsonAnswer.status_breadcrumbs == "error") {
                    jQuery("#msgs").append("<p>" + jsonAnswer.status_breadcrumbs_error + "</p>");
                } else if (jsonAnswer.status_breadcrumbs == "notice") {
                    jQuery("#msgs").append("<p>" + jsonAnswer.status_breadcrumbs_error + "</p>");
                }

                // SubFolders
                if (jsonAnswer.status_folder == "success") {
                    var html_folders = jsonAnswer.subfolder_html;
                    var title = jsonAnswer.title;
                    jQuery("#exam-question-bank-tree #folders").html(html_folders);
                    /*
                     Initiates the folder sorting if the user can edit the parent folders
                     */
                    if (jsonAnswer.edit_folder == 1) {
                        jQuery("#folder_ul").each(makeSortable);
                    }

                    jQuery("#exam-question-bank-tree-title").text(title);
                } else if (jsonAnswer.status_folder == "error") {
                    jQuery("#msgs").append("<p>" + jsonAnswer.status_folder_error + "</p>");
                } else if (jsonAnswer.status_folder == "notice") {
                    jQuery("#msgs").append("<p>" + jsonAnswer.status_folder_error + "</p>");
                }

                // Select all button reset
                var select_object = jQuery("#select-all-question-bank");
                if (select_object.hasClass("selected")) {
                    select_object.removeClass("selected");
                    icon = $(select_object).find(".fa-check-square-o");
                    icon.addClass("fa-square-o").removeClass("fa-check-square-o");
                }

                // renders url window for reloads or copying the url.
                // supported in IE 10 and up, all other browsers support
                // window.atob is an object first in IE 10
                if (window.atob) {
                    if (popstate === true) {
                        var stateObject = JSON.stringify({
                            folder: folder_id,
                            element_type: element_type,
                            exam_id: exam_id
                        });

                        var url = "questions?folder_id=" + folder_id;

                        if (element_type && element_type != "[object HTMLInputElement]") {
                            url += "&element_type=" + element_type;
                        }

                        if (exam_id && exam_id != "[object HTMLInputElement]") {
                            url += "&exam_id=" + exam_id;
                        }

                        history.pushState(stateObject, "Navigation", url);
                    }
                } else {
                    //old IE or
                }
                //stores the new folder_id in the local variable for other functions to use.
                current_folder_id = folder_id;

                toggleActionBtn();

            } else {
                return false;
            }
        }
    });
}


function makeSortable() {
    jQuery(this).sortable({
        opacity: 0.7,
        update: function () {
            //, {'attribute': 'data-sortable-folder-id'}
            if (jQuery(this).data("sortable-folder-id")) {
                var folder_order = jQuery(this).sortable("serialize");

                var data_str = "method=update-exam-folder-order&" + folder_order;

                jQuery.ajax({
                    url: FOLDER_API_URL,
                    data: data_str,
                    type: "POST",
                    cache: false,
                    success: function(data) {
                        var jsonAnswer = JSON.parse(data);

                        if (jsonAnswer.status == "success") {
                            jQuery.growl({ title: "Success", message: jsonAnswer.message });
                        } else {
                            jQuery.growl.error({ title: "Error", message: jsonAnswer.message });
                        }
                    }
                });
            }
        }
    });
}

window.onpopstate = function(event) {
    var state = JSON.parse(event.state);
    if (typeof state && (state !== "undefined" || state !== null)) {
        var folder_id = state.folder;
        renderFolderView(folder_id, false, "");
    } else {
        renderFolderView(0, false, "");
    }
};