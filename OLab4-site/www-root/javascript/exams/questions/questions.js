var question_offset = 0;
var question_limit = 50;
var total_questions = 0;
var show_loading_message = true;
var timeout;
var reload_questions = false;
var questions_checked = {};
var questions_to_delete_approved = [];
var questions_to_delete_denied = [];
var questions_to_delete = [];
var questions_to_move_approved = [];
var questions_to_move_denied = [];
var questions_to_move = [];
var folders_to_delete = [];
var folder_id_selected;
var folders_to_delete_approved = [];
var folders_to_delete_denied = [];
var delete_url;
var current_folder_id;
var question_preview_id;
var EDITABLE = false;
var loaded = [];
var mapped_curriculum_tags = [];
var loading_objectives = false;
var linked_objective_id = 0;

jQuery(document).ready(function ($) {
    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var parameters = "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target;
        removeFilters(parameters);
    });

    $(".panel .clear-filters").on("click", function (e) {
        e.preventDefault();
        var parameters = "method=remove-all-filters";
        removeFilters(parameters);
    });

    var search_term = $("#question-search").val();

    if (typeof list_index && list_index === "undefined") {
        var list_index;
    }
    if (typeof exclude_question_id && exclude_question_id === "undefined") {
        var exclude_question_id;
    }
    if (typeof VIEW_PREFERENCE === "undefined") {
        VIEW_PREFERENCE = "details";
    }
    if (typeof VIEW_PREFERENCE_MODAL === "undefined") {
        VIEW_PREFERENCE_MODAL = VIEW_PREFERENCE;
    }

    var question_view_controls      = $("#question-view-controls");
    var question_table_container    = $("#question-table-container");
    var question_detail_container   = $("#question-detail-container");
    var question_controls_div       = $("#question-bank-view-controls");
    
    question_view_controls.children("[data-view=\"" + VIEW_PREFERENCE + "\"]").addClass("active");

    if (VIEW_PREFERENCE === "list") {
        question_table_container.removeClass("hide");
        question_controls_div.removeClass("btn-group").addClass("padding-right");
        $("#toggle-all-question-bank").hide();
    } else {
        question_detail_container.removeClass("hide");
    }

    $("#question-view-controls .view-toggle").on("click", function (e) {
        e.preventDefault();
        toggleView($(this));
    });

    $("#toggle-exam-bank").on("click", function (e) {
        e.preventDefault();
        toggleQuestionBank($(this));
    });

    $("#question-detail-container").on("click", ".question-details", function (event) {
        showDetails(event, $(this), "details");
    });

    $("#question-table-container").on("click", ".question-preview", function (event) {
        event.preventDefault();
        showQuestionPreview($(this));
    });

    $("#preview-question-modal").on("hide.bs.modal", function (e) {
        closeQuestionPreview();
    });

    $("#question-table-container").on("click", ".select-question", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#question-detail-container").on("click", ".select-question", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#question-detail-container").on("click", ".question-table .question-control", function (e) {
        e.preventDefault();
    });

    $("#question-detail-container").on("click", ".related-questions", function (e) {
        e.preventDefault();
        showRelatedVersions($(this));
    });

    $("#question-detail-container").on("click", ".disabled", function(e) {
        e.preventDefault();
    });

    $("#question-detail-container").on("click", ".related_version_link[data-type=\"question\"", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    $("#question-table-container").on("click", ".related_version_link[data-type=\"question\"", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    $("#select-all-question-bank").on("click", function (e) {
        e.preventDefault();

        var check_boxes;
        var icon;
        var object = $(this);
        var activate = false;

        if (object.hasClass("selected")) {
            object.removeClass("selected");
            icon = $(object).find(".fa-check-square-o");
            icon.addClass("fa-square-o").removeClass("white-icon").removeClass("fa-check-square-o");
        } else {
            object.addClass("selected");
            icon = $(object).find(".fa-square-o");
            icon.addClass("fa-check-square-o").addClass("white-icon").removeClass("fa-square-o");
            activate = true;
        }

        if (VIEW_PREFERENCE == "list") {
            check_boxes = $("#question-table-container .select-question");
        } else {
            check_boxes = $("#question-detail-container .select-question");
        }

        $.each(check_boxes, function (key, value) {
            if (activate == true) {
                if (!$(value).hasClass("selected")) {
                    $(value).trigger("change");
                }
            } else {
                if ($(value).hasClass("selected")) {
                    $(value).trigger("change");
                }
            }
        });
    });

    $("#question-table-container").on("change", ".select-question", function () {
        selectQuestion(this);
    });

    $("#question-detail-container").on("change", ".select-question", function () {
        selectQuestion(this);
    });

    /* move questions event listeners */
    $("#move-question-modal").on("show.bs.modal", function (e) {
        onShowMoveModal();
    });

    $("#move-question-modal").on("hide.bs.modal", function (e) {
        $("#move-questions-container").html("");
    });

    $("#move-questions-modal-move").on("click", function(e) {
        moveQuestions(e);
    });

    $("#delete-folder-modal").on("show.bs.modal", function (e) {
        showDeleteFolder();
    });

    $("#delete-folder-modal").on("hide.bs.modal", function (e) {
        $("#delete-folders-container").html("");
    });

    $("#delete-folders-modal-delete").on("click", function (e) {
        e.preventDefault();
        deleteFolders();
    });

    $("#delete-question-modal").on("show.bs.modal", function (e) {
        onShowDeleteQuestionsModal();
    });

    $("#delete-question-modal").on("hide.bs.modal", function (e) {
        $("#delete-questions-container").html("");
    });

    $("#delete-questions-modal-delete").on("click", function(e) {
        e.preventDefault();
        deleteQuestions();
    });

    $("#exam-search").on("click", "input.add-question", function(e) {
        e.preventDefault();
        addQuestion($(this));
    });

    function removeFilters(parameters) {
        var remove_filter_request = $.ajax({
            url: "?section=api-questions",
            data: parameters,
            type: "POST"
        });

        $.when(remove_filter_request).done(
            function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    window.location.reload();
                }
            }
        );
    }

    function toggleView(clicked) {
        var selected_view = $(clicked).attr("data-view");
        VIEW_PREFERENCE = selected_view;

        var question_controls_div = $("#question-bank-view-controls");
        var question_all = $("#toggle-all-question-bank");

        question_view_controls      = $("#question-view-controls");
        question_table_container    = $("#question-table-container");
        question_detail_container   = $("#question-detail-container");

        if (selected_view === "list") {
            question_table_container.removeClass("hide");
            question_detail_container.addClass("hide");
            question_all.hide();
            question_controls_div.removeClass("btn-group").addClass("padding-right");
        } else {
            question_detail_container.removeClass("hide");
            question_table_container.addClass("hide");
            question_all.show();
            question_controls_div.addClass("btn-group").removeClass("padding-right");
        }

        question_view_controls.children().removeClass("active");
        $(clicked).addClass("active");
        set_view();
    }

    function toggleQuestionBank(clicked) {
        var icon = $(clicked).children(i);
        if (icon.hasClass("fa-eye")) {
            $("#folders").addClass("hide");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            $("#folders").removeClass("hide");
            icon.addClass("fa-eye").removeClass("fa-eye-slash");
        }
    }

    function showQuestionPreview(clicked) {
        var icon = $(clicked).children(i);
        var version_id = icon.data("version-id");
        question_preview_id = version_id;
        var question_row = $("tr.question-row[data-version-id=\"" + version_id + "\"]");
        var id = $("question_row").find(".q-list-id").text();

        var question_preview_html = $("#question-detail-container .exam-question[data-version-id=\"" + version_id + "\"]").clone();
        var question_table = $(question_preview_html).find("table.question-table");
        $(question_table).find("tr.type .pull-right").remove();

        var modal_window = $("#preview-question-modal");
        var modal_body_text = modal_window.find(".modal-sub-body");

        $(modal_body_text).html(question_table);
        $(modal_window).modal("show");
    }

    function closeQuestionPreview() {
        var version_id = question_preview_id;
        var question_row = $("tr.question-row[data-version-id=\"" + version_id + "\"]").filter(":last");

        var background = $(question_row).find("td").css("background-color");

        if (background === "transparent") {
            $(question_row).find("td").addClass("AnimationTransparentToYellow");
        } else {
            $(question_row).find("td").addClass("AnimationGrayToYellow");
        }
        setTimeout(function() {
            if (background === "transparent") {
                $(question_row).find("td").removeClass("AnimationTransparentToYellow");
                $(question_row).find("td").css("background-color", "#C8F253");
            } else {
                $(question_row).find("td").removeClass("AnimationGrayToYellow");
                $(question_row).find("td").css("background-color", "#C8F253");
            }
        }, 500);

        setTimeout(function() {
            if (background === "transparent") {
                $(question_row).find("td").addClass("AnimationYellowToTransparent");
                $(question_row).find("td").css("background-color", "transparent");
            } else {
                $(question_row).find("td").addClass("AnimationYellowToGray");
                $(question_row).find("td").css("background-color", "#f9f9f9");
            }
        }, 3500);

        setTimeout(function() {
            if (background === "transparent") {
                $(question_row).find("td").removeClass("AnimationYellowToTransparent");
            } else {
                $(question_row).find("td").removeClass("AnimationYellowToGray");
            }
        }, 4000);
    }

    function addQuestion(clicked) {
        if ($("#element_type").val() == "group") {
            var question_index = $(clicked).val();

            //ensure that both the detail view question is checked
            var checked = $(clicked).is(":checked");

            if (VIEW_PREFERENCE == "list") {
                list_index = $(clicked).parent().parent().index();
                $("div.question-container[data-question-id=\"" + question_index + "\"]").find("input.question-selector").prop("checked", checked);
                $("div.question-container[data-question-id=\"" + question_index + "\"]").find("input.question-selector").trigger("change");
            } else {
                $("#questions-table input.add-question[value=" + question_index + "]").prop("checked", checked);
            }

            //refresh the list based on this group's rules and only if the group width has changed.
            if ($(clicked).not(":checked") && $("#exam-search input.add-question:checkbox:checked").length == 0) {
                $("#group_question_width").html(0);
                reload_questions = false;
                $("#questions-table").find("tbody tr").remove();
                $("div#question-detail-container").find("div.question-container").remove();
                question_offset = 0;

                renderFolderView(current_folder_id, true, "");
            }
        }
    }

    function showDeleteFolder() {
        $("#msgs").html("");
        $("#delete-folders-container").html("");
        $("#folders-selected").addClass("hide");
        $("#no-folders-selected").addClass("hide");

        if (folder_id_selected) {
            var dataObject = {
                method : "get-folder-delete-permission",
                folder_ids: folder_id_selected
            };

            jQuery.ajax({
                url: FOLDER_API_URL,
                data: dataObject,
                type: "GET",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        $.each(jsonResponse.folder_ids, function(index, folder) {
                            if (folder.delete === true) {
                                folders_to_delete_approved.push(folder.folder_id)
                            } else if (folder.delete === false) {
                                folders_to_delete_denied.push(folder.folder_id)
                            }
                        });

                        if (folders_to_delete_approved.length > 0 || folders_to_delete_denied.length > 0) {
                            $("#questions-selected").removeClass("hide");
                            $("#delete-questions-modal-delete").removeClass("hide");

                            $.each(folders_to_delete_approved, function(index, folder_id) {
                                var folder_title = $(".bank-folder[data-folder-id=" + folder_id + "] .folder-title");
                                $("#folders-selected").removeClass("hide");
                                $("#delete-folders-modal-delete").removeClass("hide");

                                var delete_folder_message = "<p>This folder <strong>" + folder_title.text() + "</strong>, ID:  <strong>" + folder_id + "</strong> will be deleted.</p>";
                                $("#delete-folders-container").append(delete_folder_message);
                            });

                            $.each(folders_to_delete_denied, function(index, folder_id) {
                                var folder_title = $(".bank-folder[data-folder-id=" + folder_id + "] .folder-title");
                                $("#folders-selected").removeClass("hide");
                                $("#delete-folders-modal-delete").removeClass("hide");

                                var delete_folder_message = "<p class=\"no-delete\">This folder <strong>" + folder_title.text() + "</strong>, ID:  <strong>" + folder_id + "</strong> can't be deleted.</p>";
                                $("#delete-folders-container").append(delete_folder_message);
                            });
                        } else {
                            $("#no-questions-selected").removeClass("hide");
                            $("#delete-questions-modal-delete").addClass("hide");
                        }
                    } else if (jsonResponse.status == "error") {
                        $("#no-questions-selected").removeClass("hide");
                        $("#delete-questions-modal-delete").addClass("hide");
                    }
                }
            });
            folders_to_delete_approved = [];
            folders_to_delete_denied = [];
        } else {
            $("#no-folders-selected").removeClass("hide");
            $("#delete-folders-modal-delete").addClass("hide");
        }
    }

    function deleteFolders() {
        var folder_data = {
            "method": "delete-folders",
            "type": "single",
            "delete_ids": folders_to_delete_approved
        };

        $("#folders-selected").removeClass("hide");
        $("#delete-folders-modal-delete").removeClass("hide");

        $.ajax({
            url: FOLDER_API_URL,
            data: folder_data,
            type: "POST",
            success: function (data) {
                var jsonAnswer = JSON.parse(data);
                if (jsonAnswer.status == "success") {
                    display_success([jsonAnswer.msg], "#msgs")
                    $(jsonAnswer.folder_ids).each(function (index, folder) {
                        //remove html for removed folder
                        var folder = $(".bank-folder[data-folder-id=" + folder_id_selected + "]").parent();
                        folder.remove();

                    });
                } else if (jsonAnswer.status == "error") {
                    display_error([jsonAnswer.msg], "#msgs");
                }
            }
        }).done(function(data) {
            $("#delete-folder-modal").modal("hide");
        });
    }

    function onShowDeleteQuestionsModal() {
        questions_to_delete_approved = [];
        questions_to_delete_denied = [];
        questions_to_delete = [];
        $("#msgs").html("");
        $("#questions-selected").addClass("hide");
        $("#no-questions-selected").addClass("hide");

        jQuery.each(questions_checked, function(key, value) {
            questions_to_delete.push(key);
        });

        var dataObject = {
            method : "get-question-delete-permission",
            questions: questions_to_delete
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $.each(jsonResponse.questions, function(index, question) {
                        if (question["delete"] == true) {
                            questions_to_delete_approved.push(question.question_id)
                        } else {
                            questions_to_delete_denied.push(question.question_id)
                        }
                    });

                    if (questions_to_delete_approved.length > 0 || questions_to_delete_denied.length > 0) {
                        $("#questions-selected").removeClass("hide");
                        $("#delete-questions-modal-delete").removeClass("hide");

                        var list = document.createElement("ul");
                        $.each(questions_to_delete_approved, function(index, element) {
                            var list_question   = document.createElement("li");
                            var question_id     = questions_checked[element].question_id;
                            var version_id      = questions_checked[element].version_id;
                            var version_count   = questions_checked[element].version_count;
                            var question_text   = $("div.exam-question[data-version-id=" + version_id + "] table tr.heading td .question_text").text();
                            question_text = filterQuestionText(question_text);

                            if ($(question_text).text() == "") {
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + question_text);
                            } else {
                                question_text = filterQuestionText(question_text);
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + $(question_text).text());
                            }

                            $(list).append(list_question);
                        });

                        $.each(questions_to_delete_denied, function(index, element) {

                            var list_question   = document.createElement("li");
                            var question_id     = questions_checked[element].question_id;
                            var version_count   = questions_checked[element].version_count;

                            $(list_question).append("<span class=\"no-delete\" ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> You can't delete this question.</span>");

                            $(list).append(list_question);

                        });
                        $("#delete-questions-container").append(list);
                    } else {
                        $("#no-questions-selected").removeClass("hide");
                        $("#delete-questions-modal-delete").addClass("hide");
                    }

                } else if (jsonResponse.status == "error") {
                    $("#no-questions-selected").removeClass("hide");
                    $("#delete-questions-modal-delete").addClass("hide");
                }
            }
        });
    }

    function deleteQuestions() {
        var url = $("#delete-question-modal-question").attr("action");

        var question_data = {
            "method"        : "delete-questions",
            "type"          : "single",
            "delete_ids"    : questions_to_delete_approved
        };

        $("#questions-selected").removeClass("hide");
        $("#delete-questions-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, question_data, function(data) {
            if (data.status == "success") {
                renderFolderView(current_folder_id, true, "");

                display_success([data.msg], "#msgs");

                if ($("#question-detail-container .exam-question ").length == 0) {
                    jQuery("#load-questions").addClass("hide");
                    var no_results_div = jQuery(document.createElement("div"));
                    var no_results_p = jQuery(document.createElement("p"));

                    no_results_p.html(submodule_text.index.no_questions_found);
                    jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
                    jQuery("#exam-msgs").append(no_results_div);
                }
            } else if(data.status == "error") {
                display_error([data.msg], "#msgs");
            }
        }, "json").done(function(data) {
            $("#delete-question-modal").modal("hide");
        });
    }

    function onShowMoveModal() {
        questions_to_move_approved = [];
        questions_to_move_denied = [];
        questions_to_move = [];
        $("#msgs").html("");
        $("#move-question-msg").html();
        $("#questions-selected-move").addClass("hide");
        $("#no-questions-selected-move").addClass("hide");

        jQuery.each(questions_checked, function(key, value) {
            questions_to_move.push(key);
        });

        var dataObject = {
            method : "get-question-move-permission",
            questions: questions_to_move
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $.each(jsonResponse.questions, function(index, question) {
                        if (question["move"] == true) {
                            questions_to_move_approved.push(question.question_id)
                        } else {
                            questions_to_move_denied.push(question.question_id)
                        }
                    });

                    if (questions_to_move_approved.length > 0 || questions_to_move_denied.length > 0) {
                        $("#questions-selected-move").removeClass("hide");
                        $("#move-questions-modal-move").removeClass("hide");

                        var list = document.createElement("ul");
                        $.each(questions_to_move_approved, function(index, element) {
                            var list_question   = document.createElement("li");
                            var question_id     = questions_checked[element].question_id;
                            var version_id      = questions_checked[element].version_id;
                            var version_count   = questions_checked[element].version_count;
                            var question_text   = $("div.exam-question[data-question-id=" + question_id + "] table tr.heading td .question_text").text();

                            question_text = filterQuestionText(question_text);

                            if ($(question_text).text() == "") {
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + question_text);
                            } else {
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + $(question_text).text());
                            }

                            $(list).append(list_question);
                        });

                        $.each(questions_to_move_denied, function(index, element) {

                            var list_question   = document.createElement("li");
                            var question_id     = questions_checked[element].question_id;
                            var version_count   = questions_checked[element].version_count;

                            $(list_question).append("<span class=\"no-delete\" ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> You can't move this question.</span>");

                            $(list).append(list_question);

                        });
                        $("#move-questions-container").append(list);
                    } else {
                        $("#no-questions-selected-move").removeClass("hide");
                        $("#move-questions-modal-delete").addClass("hide");
                    }

                } else if (jsonResponse.status == "error") {
                    $("#no-questions-selected").removeClass("hide");
                    $("#move-questions-modal-delete").addClass("hide");
                }
            }
        });
    }

    function moveQuestions(e) {
        e.preventDefault();
        var url = $("#move-question-modal-question").attr("action");

        var destination_folder = $(".folder-selector.folder-selected").data("id");

        if (typeof destination_folder !== "undefined") {
            if (destination_folder == 0) {
                var error_msg = ["The Index folder is not a valid destination"];
                display_error(error_msg, "#move-question-msg");
            } else {
                var question_data = {
                    "method"        : "move-questions",
                    "folder"        : destination_folder,
                    "question_ids"    : questions_to_move_approved
                };

                $("#questions-selected-move").removeClass("hide");
                $("#move-questions-modal-move").removeClass("hide");

                var jqxhr = $.post(url, question_data, function(data) {
                    if (data.status == "success") {
                        $(data.question_ids).each(function(index, version_id) {
                            //remove html for removed question
                            $("div.exam-question[data-version-id=" + version_id + "]").remove();
                            $("div#question-table-container tr#question-row-" + version_id).remove();
                        });

                        display_success([data.msg], "#msgs");

                        if ($("#question-detail-container .exam-question ").length == 0) {
                            jQuery("#load-questions").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(submodule_text.index.no_questions_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
                            jQuery("#exam-msgs").append(no_results_div);
                        }
                    } else if(data.status == "error") {
                        display_error([data.msg], "#move-question-msg");
                    }
                }, "json").done(function() {
                    $("#move-question-modal").modal("hide");
                });
            }
        } else {
            var error_msg = ["Please select a valid destination folder."];
            display_error(error_msg, "#move-question-msg");
        }
    }

    var element_type = $("#element_type").val();
    if (element_type !== "undefined" && element_type == "group") {
        var group_id = $("#id").val();
        create_question_summary(group_id);
    }

    function showRelatedVersions(clicked) {
        var children = $(clicked).children("i.related-question-icon");
        var question_id = $(children).data("question-id");

        if ($("#related-question-id-" + question_id).hasClass("hide")) {
            $("#related-question-id-" + question_id).removeClass("hide").show();
        } else {
            $("#related-question-id-" + question_id).addClass("hide").hide();
        }
    }

    function loadRelatedVersion(clicked) {
        var header = $(clicked).parents("div.header-buttons");
        var icon = $(header).find("i.related-question-icon");
        var original_version_id = $(icon).data("version-id");
        var new_version_id = $(clicked).data("version-id");
        var exam_div = $("div.exam-question[data-version-id=\"" + original_version_id + "\"]");
        var tr_row = $(".question-row[data-version-id=\"" + original_version_id + "\"]");

        var question_id = $(exam_div).data("question-id");
        var question = {"question_id": question_id};

        var dataObject = {
            method : "build-question-answers",
            exam_mode: false,
            question: question,
            version_id: new_version_id
        };

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

    function showDetails(event, clicked, type) {
        event.preventDefault();

        if (type == "list") {
            var element = clicked.parents("tr");
            var next    = element.next(".question-detail-view");
            next.toggleClass("hide");
        } else {
            var element = clicked.parents(".question-table");
            element.find(".question-detail-view").toggleClass("hide");
        }
        clicked.toggleClass("active");
        clicked.find("i").toggleClass("white-icon");
    }

    function selectQuestion(clicked) {
        var icon            = jQuery(clicked).find(".question-icon-select");
        var question_id     = icon.data("question-id");
        var version_id      = icon.data("version-id");
        var version_count   = icon.data("version-count");

        /* Details View */
        var icon_details    = jQuery("#question-detail-container").find(".question-icon-select[data-version-id=" + version_id + "]");
        var span_details    = icon_details.closest("span");
        var object_details  = span_details.closest("table");

        /* List View */
        var icon_list       = jQuery(".q-list-edit .question-icon-select[data-version-id=" + version_id + "]");
        var span_list       = icon_list.closest("span");
        var object_list     = span_list.closest("tr.question-row");

        //adds the question to the object used to delete
        if (object_details.hasClass("selected")) {
            if (questions_checked[version_id]) {
                delete questions_checked[version_id];
            }
        } else {
            if (!questions_checked[version_id]) {
                questions_checked[version_id] = {question_id: question_id, version_id: version_id, version_count: version_count};
            }
        }

        //changes the HTML for the question checked
        changeSelectActiveHTML(object_details, span_details, icon_details);
        changeSelectActiveHTML(object_list, span_list, icon_list);

        //section for exam adding questions
        if (span_details.hasClass("selected")) {
            var question_add_input = document.createElement("input");
            jQuery(question_add_input).attr({type: "hidden", "class": "add-question", "id":  "question-" + version_id, name: "questions[]", value: version_id});
            jQuery(span_details).parents("form").append(question_add_input);
        } else {
            jQuery(span_details).parents("form").find("input#question-" + version_id).remove();
        }
    }

    function changeSelectActiveHTML(object, span, icon) {
        if (object.hasClass("selected")) {
            object.removeClass("selected");
            span.removeClass("selected");
            icon.addClass("fa-square-o").removeClass("white-icon").removeClass("fa-check-square-o");
        } else {
            object.addClass("selected");
            span.addClass("selected");
            icon.addClass("fa-check-square-o").addClass("white-icon").removeClass("fa-square-o");
        }
    }

    function set_view () {
        var selected_view = jQuery("#question-view-controls").children(".active").attr("data-view");

        jQuery.ajax({
            url: "?section=api-questions",
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

    function filterQuestionText(question_text) {
        question_text = question_text.replace(/[(]/g, "\&#40;");
        question_text = question_text.replace(/[)]/g, "\&#41;");
        question_text = question_text.replace(/[']/g, "\&#39;");
        question_text = question_text.replace(/["]/g, "\&#34;");
        question_text = question_text.replace(/[?]/g, "\&#63;");
        question_text = question_text.replace(/[&]/g, "\&#38;");
        return question_text;
    }

    function delete_action(exam) {
        var action = ENTRADA_URL + "/admin/exams/questions?section=delete&step=1";
        exam.action = action;
        return false;
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
        var url = ENTRADA_URL + "/admin/exams/groups?section=api-group";
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

    jQuery(".btn-actions-group").on("click", "#objective-modal-toggle", function(){
        jQuery("#tagging_question_ids").html("");
        var count = 0;
        for (var key in questions_checked){
            if(questions_checked[key]){
                count++;
            }
        }
        jQuery("#tagging_question_ids").text(count + " Questions were selected. Please choose the editing mode:");
    });

    function get_objective_text(objective, always_show_code) {
        if (objective['objective_code']) {
            return objective['objective_code'] + ': ' + objective['objective_name'];
        } else {
            var is_code = /^[A-Z]+\-[\d\.]+$/.test(objective['objective_name']);
            if (objective['objective_description'] && is_code) {
                if (always_show_code) {
                    return objective['objective_name'] + ': ' + objective['objective_description'];
                } else {
                    return objective['objective_description'];
                }
            } else {
                return objective['objective_name'];
            }
        }
    }

    function buildDOM(children,id){
        var container,title, title_text, controls, check, d_control, e_control, a_control, m_control, description, child_container;
        jQuery('#children_' + id).hide();
        jQuery('#objective_list_' + id).html("");
        if(children.error !== undefined){
            if(!EDITABLE){
                jQuery('#check_objective_' + id).trigger('click');
                jQuery('#check_objective_' + id).trigger('change');
            }
            return false;
        }
        for(i = 0; i < children.length; i++){
            //Javascript to create DOM elements from JSON response
            var data_title = get_objective_text(children[i]);

            container = jQuery(document.createElement('li'))
                .attr('class','objective-container draggable')
                .attr('data-id',children[i].objective_id)
                .attr('data-code',children[i].objective_code)
                .attr('data-name',children[i].objective_name)
                .attr("data-title", data_title)
                .attr('data-description',children[i].objective_description)
                .attr('id','objective_'+children[i].objective_id);

            title = jQuery(document.createElement('div'))
                .attr('class','objective-title')
                .attr('id','objective_title_'+children[i].objective_id)
                .attr('data-id',children[i].objective_id)
                .attr('data-title',data_title)
                .html(data_title);

            controls = jQuery(document.createElement('div'))
                .attr('class','objective-controls');

            if (EDITABLE == true){
                e_control = jQuery(document.createElement('i'))
                    .attr('class','objective-edit-control icon-edit')
                    .attr('data-id',children[i].objective_id);
                a_control = jQuery(document.createElement('i'))
                    .attr('class','objective-add-control icon-plus-sign')
                    .attr('data-id',children[i].objective_id);
                d_control = jQuery(document.createElement('i'))
                    .attr('class','objective-delete-control icon-minus-sign')
                    .attr('data-id',children[i].objective_id);
                m_control = jQuery(document.createElement('i'))
                    .attr('class','objective-link-control icon-link')
                    .attr('data-id',children[i].objective_id);
            } else {
                check = 	jQuery(document.createElement('input'))
                    .attr('type','checkbox')
                    .attr('class','checked-objective')
                    .attr('id','check_objective_'+children[i].objective_id)
                    .val(children[i].objective_id);
                if(children[i].mapped && children[i].mapped != 0){
                    jQuery(check).prop('checked',true);
                }else if(children[i].child_mapped && children[i].child_mapped != 0){
                    jQuery(check).prop('checked',true);
                    jQuery(check).prop('disabled',true);
                }
            }
            description = 	jQuery(document.createElement('div'))
                .attr('class','objective-description content-small')
                .attr('id','description_'+children[i].objective_id)
                .html(children[i].objective_description);
            child_container = 	jQuery(document.createElement('div'))
                .attr('class','objective-children')
                .attr('id','children_'+children[i].objective_id);
            child_list = 	jQuery(document.createElement('ul'))
                .attr('class','objective-list')
                .attr('id','objective_list_'+children[i].objective_id)
                .attr('data-id',children[i].objective_id);
            jQuery(child_container).append(child_list);
            var type = jQuery('#mapped_objectives').attr('data-resource-type');
            if((type != 'event' && type != 'assessment' ) || !children[i].has_child){
                jQuery(controls).append(check);
            }
            if(EDITABLE == true){
                jQuery(controls).append(e_control)
                    .append(a_control)
                    .append(d_control)
                    .append(m_control);
            }
            jQuery(container).append(title)
                .append(controls)
                .append(description)
                .append(child_container);
            jQuery('#objective_list_'+id).append(container);
        }

        jQuery('#children_'+id).slideDown();
    }

    jQuery(document).on('click', '.objective-collapse-control', function(){
        var id = jQuery(this).attr('data-id');
        if(jQuery('#children_'+id).is(':visible')){
            jQuery('#children_'+id).slideUp();
        }else if(loaded[id] === undefined || !loaded[id]){
            jQuery('#objective_title_'+id).trigger('click');
        }else{
            jQuery('#children_'+id).slideDown();
        }
    });

    jQuery('#curriculum-tags-section, #assessment-objectives-section, #course-objectives-section, #event-objectives-section, #exam-objectives-section').on('click', '.objective-title', function() {
        var id = jQuery(this).attr('data-id');
        if (loaded[id] === undefined || !loaded[id]) {
            var query = {'objective_id' : id, 'org_id' : (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id)};

            if (typeof by_course_id !== 'undefined' && by_course_id) {
                query['course_id'] = by_course_id;
            } else if (typeof by_assessment_id !== 'undefined' && by_assessment_id) {
                query['assessment_id'] = by_assessment_id;
            }

            if (jQuery("#event-objectives-section").length > 0) {
                if(jQuery('#mapped_objectives').length>0){
                    var type = jQuery('#mapped_objectives').attr('data-resource-type');
                    var value = jQuery('#mapped_objectives').attr('data-resource-id');
                    if(type && value){
                        if (type != 'evaluation_question') {
                            query[type+'_id'] = value;
                        } else if (jQuery('#objective_ids_string_'+value).val()) {
                            query['objective_ids'] = jQuery('#objective_ids_string_'+value).val();
                        }
                    }
                }
            }

            if(!loading_objectives){
                var loading = jQuery(document.createElement('img'))
                    .attr('src', ENTRADA_URL + '/images/loading.gif')
                    .attr('width','15')
                    .attr('title','Loading...')
                    .attr('alt','Loading...')
                    .attr('class','loading')
                    .attr('id','loading_'+id);
                jQuery('#objective_controls_'+id).append(loading);
                loading_objectives = true;
                jQuery.ajax({
                    url: ENTRADA_URL + '/api/fetchobjectives.api.php',
                    data:query,
                    success:function(data,status,xhr){
                        jQuery('#loading_'+id).remove();
                        loaded[id] = jQuery.parseJSON(data);
                        buildDOM(loaded[id],id);
                        loading_objectives = false;
                    }
                });
            }
        } else if (jQuery('#children_'+id).is(':visible')) {
            jQuery('#children_'+id).slideUp(600);
        } else {
            //console.log(id);
            if (jQuery("#objective_list_"+id).children('li').length == 0) {
                if(!EDITABLE){
                    buildDOM(loaded[id],id);
                }
            }	else {
                jQuery('#children_'+id).slideDown(600);
            }
        }
        return false;
    });

    jQuery("#exam-objectives-section").on("click", ".checked-objective", function(){
        var id = jQuery(this).attr('value');
        if(mapped_curriculum_tags.includes(id)){
            var i = mapped_curriculum_tags.indexOf(id);
            mapped_curriculum_tags.splice(i, 1);
        }else{
            mapped_curriculum_tags.push(id);
        }
        //console.log(mapped_curriculum_tags);
    });

    jQuery("#exam-objectives-section").on("click", ".objective-remove", function(){
        var id = jQuery(this).attr('data-id');
        if(mapped_curriculum_tags.includes(id)){
            var i = mapped_curriculum_tags.indexOf(id);
            mapped_curriculum_tags.splice(i, 1);
        }
        //console.log(mapped_curriculum_tags);
    });

    jQuery("#objective-modal").on("click", "#apply_tags", function(){
        var checked_questions_ids = [];
        for (var key in questions_checked){
            if(questions_checked[key]){
                checked_questions_ids.push(questions_checked[key].question_id);
            }
        }
        var editing_mode = jQuery("input[name=tag_editing_mode]:checked").val();
        if(mapped_curriculum_tags.length){
            jQuery.ajax({
                url: ENTRADA_URL + "/admin/exams/questions?section=api-objectives",
                data: {
                    editing_mode: editing_mode,
                    checked_questions_ids: checked_questions_ids,
                    mapped_curriculum_tags: mapped_curriculum_tags
                },
                type: "POST",
                success: function(data, status, xhr){
                    var response = jQuery.parseJSON(data);
                    if (response.status === "success"){
                        var success_html = "<div class=\"alert alert-success\">\n" +
                            "<strong>Success!</strong> The selected questions curriculum tags were updated.\n" +
                            "<a href=\"#\" class=\"btn btn-success right\" style=\"margin-left: 100px\" data-dismiss=\"modal\">Close</a>\n" +
                        "</div>"
                        jQuery("#response-questions-objetive").html(success_html);
                        jQuery("#response-questions-objetive").focus();
                        setTimeout(function(){
                            jQuery("#response-questions-objetive").html("");
                        }, 10000);
                    }else{
                        var error_html = "<div class=\"alert alert-danger\">\n" +
                            "<p><strong>Error!</strong> An unexpected error happened while trying to update curriculum tags.<br/> Please reload the page and try again.</p>\n" +
                            "<a href=\"#\" class=\"btn btn-danger\" data-dismiss=\"modal\" onclick=\"window.location.reload(true)\">Reload Page</a>\n" +
                            "</div>"
                        jQuery("#response-questions-objetive").html(error_html);
                        jQuery("#response-questions-objetive").focus();
                    }
                }
            });
        }else{
            alert("You didn't choose any curriculum tag. Please select at least one.");
        }

    });

});
