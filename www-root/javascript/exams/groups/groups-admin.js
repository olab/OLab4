var group_offset = 0;
var group_limit = 50;
var total_groups = 0;
var show_loading_message = true;
var questions_checked = {};

jQuery(function($) {
    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();
        
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax(
            {
                url: "?section=api-group",
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
               url: "?section=api-group",
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
    var arquestion_id = "";
    
    var group_view_controls = $("#group-view-controls");
    var group_table_container = $("#group-table-container");
    var group_detail_container = $("#group-detail-container");
    
    group_view_controls.children("[data-view=\""+ VIEW_PREFERENCE +"\"]").addClass("active");
    
    get_groups();
    
    $("#group-search").keypress(function (event) {
        if (event.keyCode == 13)  {
            event.preventDefault();
        }
        
        total_groups = 0;
        group_offset = 0;
        show_loading_message = true;
        
        clearTimeout(timeout);
        timeout = window.setTimeout(get_groups, 700);
    });
    
    $("#load-groups").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_groups();
        }
    });
    $("#question-detail-container").on("click", ".item-details", function (event) {
        event.preventDefault();
        showDetails(event, $(this), "details");
    });

    $("#question-table-container").on("click", ".question-preview", function (event) {
        event.preventDefault();
        showQuestionPreview($(this));
    });

    $("#preview-question-modal").on("hide.bs.modal", function (e) {
        closeQuestionPreview();
    });
    
    $(".group-container").on("click", ".question-details", function (e) {
        e.preventDefault();
        toggle_active_question_detail($(this));
    });

    jQuery("#question-table-container").on("click", ".select-question", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    jQuery("#question-detail-container").on("click", ".select-question", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    jQuery("#question-detail-container").on("click", ".question-table .question-control", function (e) {
        e.preventDefault();
    });

    jQuery("#question-detail-container").on("click", ".related-questions", function (e) {
        e.preventDefault();
        showRelatedVersions($(this));
    });

    jQuery("#question-detail-container").on("click", ".disabled", function(e) {
        e.preventDefault();
    });

    jQuery("#question-detail-container").on("click", ".related_version_link", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    jQuery("#question-table-container").on("click", ".related_version_link", function(e) {
        e.preventDefault();
        loadRelatedVersion($(this));
    });

    jQuery("#question-table-container").on("change", ".select-question", function () {
        selectQuestion(this);
    });

    jQuery("#question-detail-container").on("change", ".select-question", function () {
        selectQuestion(this);
        toggleActionBtn();
    });

    $("#delete-group-question-modal").on("show.bs.modal", function (e) {
        onShowDeleteQuestionsModal();
    });

    $("#delete-group-question-modal").on("hide.bs.modal", function (e) {
        $("#delete-questions-container").html("");
    });

    $("#delete-group-question-modal-delete").on("click", function(e) {
        e.preventDefault();
        deleteQuestions();
    });

    $("body").on("click", "a.disabled", function(event) {
        event.preventDefault();
    });

    function isAQuestionSelected() {
        var $selected = false;
        jQuery("#question-detail-container").find(".select-question").each(function() {
            if (jQuery(this).hasClass("selected")) {
                return $selected = true;
            }
        });
        return $selected;
    }

    function toggleActionBtn() {
        if (isAQuestionSelected() === false) {
            jQuery("#btn-delete-question").prop("disabled", true).addClass("disabled");
        } else {
            jQuery("#btn-delete-question").prop("disabled", false).removeClass("disabled");
        }
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
        var icon_details    = jQuery(".pull-right .question-icon-select[data-version-id=" + version_id + "]");
        var span_details    = icon_details.closest("span");
        var object_details  = span_details.closest("table");

        /* List View */
        var icon_list       = jQuery(".question-icon-select[data-version-id=" + version_id + "]");
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
    
    $("#add-group-exam-modal").submit(function(e) {
        e.preventDefault();
        $("#msgs").html("");
        var url = $(this).attr("action");
        var exam_data = $(this).serialize();
        var method = "add-group";
        if ($("#group_id").val() !== undefined && $("#group_id").val() != "") {
            method = "update-group";
        }
        exam_data += "&method=" + encodeURIComponent(method);
        var jqxhr = $.post(url, exam_data, function(data) {
                if (data.status == "success") {
                    $("#group_id").val(data.group_id);
                    $("#group-questions").removeClass("hide");
                    window.location.href = ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + data.group_id;
                } else if (data.status == "error") {
                    window.location.href = ENTRADA_URL + "/admin/exams/groups";
                }
            },
            "json"
        );
    });

    $("#group-form").submit(function(e) {
        e.preventDefault();
        $("#msgs").html("");
        var url = $(this).attr("action");
        var exam_data = $(this).serialize();
        var method = "add-group";
        if ($("#group_id").val() !== undefined && $("#group_id").val() != "") {
            method = "update-group";
        }
        exam_data += "&method=" + encodeURIComponent(method);
        var jqxhr = $.post(url, exam_data, function(data) {
            if (data.status == "success") {
                $("#group_id").val(data.group_id);
                if (data.method === "insert") {
                    display_success([data.msg + SECTION_TEXT["redirect"]], "#msgs");
                    window.location.href = ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + data.group_id;
                } else {
                     display_success([data.msg], "#msgs");
                }
                //ToDo: check if there is exam_id and react accordingly.
            } else if(data.status == "error") {
                display_error([data.msg], "#msgs");
            }
        },
            "json"
        );
    });

    $("#delete-group-modal").on("show.bs.modal", function (e) {
        $("#msgs").html("");
        $("#groups-selected").addClass("hide");
        $("#no-groups-selected").addClass("hide");

        var groups_to_delete = $("#groups-table input[name=\"groups[]\"]:checked").map(function () {
            return this.value;
        }).get();

        if (groups_to_delete.length > 0) {
            $("#groups-selected").removeClass("hide");
            $("#delete-groups-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#groups-table input[name=\"groups[]\"]:checked").each(function(index, element) {
                var group = document.createElement("li");
                var group_id = $(element).val();
                $(group).append($("#group_link_" + group_id).html());
                $(list).append(group);
            });
            $("#delete-groups-container").append(list);
        } else {
            $("#no-groups-selected").removeClass("hide");
            $("#delete-groups-modal-delete").addClass("hide");
        }
    });

    $("#delete-group-modal").on("hide.bs.modal", function (e) {
        $("#delete-groups-container").html("");
    });

    $("#delete-groups-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-group-form-modal").attr("action");
        var groups_to_delete = $("#groups-table input[name=\"groups[]\"]:checked").map(function () {
            return this.value;
        }).get();
        var exam_data = {
            "method" : "delete-groups",
            "delete_ids" : groups_to_delete
        };

        $("#groups-selected").removeClass("hide");
        $("#delete-groups-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, exam_data, function(data) {
            if (data.status == "success") {
                $(data.group_ids).each(function(index, element) {
                    $("input[name=\"groups[]\"][value=\"" + element + "\"]").parent().parent().remove();
                    display_success([data.msg], "#msgs")
                });
            } else if(data.status == "error") {
                display_error([data.msg], "#msgs");
            }
        },
        "json"
        ) .done(function(data) {
            $("#delete-group-modal").modal("hide");
        });
    });

    if ($(".edit-group-label").length) {
        $(".edit-group-label").editable(API_URL, {
            id   : "glabel_id",
            name : "label",
            submitdata : function(value, settings) {
                            var group_id = $("#group_id").val();
                            var question_id = $(this).data("questionId");
                            return {method : "edit-group-label", question_id : question_id, group_id : group_id}
                        },
            type      : "textarea",
            cancel    : "Cancel",
            submit    : "OK",
            tooltip   : "Click add description...",
            width     : 140,
            height    : 28,
            data      : function(string) {
                if (string == "&nbsp;") {
                    return ""
                } else {
                    return string;
                }
            }
        });
    }

    $(".edit-group-label-link").on("click", function(e) {
        e.preventDefault();
        $("span.edit-group-label[data-question-id=\"" + $(this).data("questionId") + "\"]").trigger("click");
    });

    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);

        var group_id = $("#group_id").val();

        $.ajax({
            url: API_URL,
            data: "method=remove-permission&author-id=" + remove_permission_btn.data("author-id") + "&group-id=" + group_id,
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

                    display_success([jsonAnswer.data], "#msgs");
                } else {
                    display_error([jsonAnswer.data], "#msgs");
                }
            }
        });
        e.preventDefault();
    });
    
    $(".group-container").sortable({
        update : function () {
            var question_order = $(this).sortable("serialize", {"attribute": "data-egquestion-id"});
            var group_id    = $("#group_id").val();
            var exam_id     = $("#exam_id").val();
            $.ajax({
                url: API_URL,
                data: "method=update-group-question-order&" + question_order + "&group_id=" + group_id + "&exam_id=" + exam_id,
                type: "POST",
                success: function(data) {
                    var jsonResponse = JSON.parse(data);

                    if (jsonResponse.status == "success") {
                        display_success([jsonResponse.data], "#msgs");
                    } else {
                        display_error([jsonResponse.data], "#msgs");
                    }
                }
            });
        }
     });

    function onShowDeleteQuestionsModal() {
        $("#group-question-selected").empty();
        $("#msgs").html("");
        $("#questions-selected").addClass("hide");
        $("#no-questions-selected").addClass("hide");

        var dataObject = {
            method : "get-question-group-delete-permission",
            group_id: $("#group_id").val()
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {


                if (!$.isEmptyObject(questions_checked)) {
                    $("#questions-selected").removeClass("hide");
                    $("#delete-group-question-modal-delete").prop("disabled", false);

                    var list = document.createElement("ul");
                    $.each(questions_checked, function(index, element) {
                        var list_question   = document.createElement("li");
                        var question_id     = element.question_id;
                        var version_id      = element.version_id;
                        var version_count   = element.version_count;
                        var question_text   = $("div.exam-question[data-version-id=" + version_id + "] table tr.heading td .question_text").text();

                        var jsonResponse = JSON.parse(data);

                        if (jsonResponse.status == "success") {
                            if ($(question_text).text() == "") {
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + question_text);
                            } else {
                                $(list_question).append("ID: <strong>" + question_id + "</strong> / Ver: <strong>" + version_count + "</strong> - " + $(question_text).text());
                            }
                        } else if (jsonResponse.status == "error") {
                            $("#no-questions-selected").removeClass("hide");
                            $(list_question).append("<p>This group can't be edited</p>");
                            $(list_question).addClass("permission-error");
                            $("#delete-group-question-modal-delete").prop("disabled", true);
                        }

                        $(list).append(list_question);
                    });

                    $("#group-question-selected").append(list);
                } else {
                    $("#no-questions-selected").removeClass("hide");
                    $("#delete-group-question-modal-delete").addClass("hide");
                }


            }
        });
    }

    function deleteQuestions() {
        var url = $("#delete-group-question-form-modal").attr("action");
        var group_id = jQuery("#group_id").val();
        var question_data = {
            "method"        : "delete-group-question",
            "questions"      : questions_checked,
            "group_id"      : group_id
        };

        $("#questions-selected").removeClass("hide");
        $("#delete-questions-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, question_data, function(data) {
            if (data.status == "success") {
                window.location = ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + group_id
            } else if(data.status == "error") {
                display_error([data.data], "#msgs");
            }
        }, "json").done(function(data) {
            $("#delete-question-modal").modal("hide");
        });
    }
});

function add_element_url() {
    var group_id = jQuery("#group_id").val();

    jQuery("#add-element").attr("href", function() {
        return jQuery("#add-element").attr("href") + "&group_id=" + group_id;
    });
}

function get_groups() {
    if (jQuery("#search-targets-exam").length > 0) {
        group_offset = 0;
        total_groups = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-exam").serialize();
    }

    var search_term = jQuery("#group-search").val();
    var data_object = {
        "method" : "get-groups",
        "search_term" : search_term,
        "limit" : group_limit,
        "offset" : group_offset
    };

    if (typeof filters !== "undefined") {
        data_object.filters = filters;
    }

    var groups = jQuery.ajax({
        url: API_URL,
        data: data_object,
        type: "GET",
        beforeSend: function () {
            if (jQuery("#exams-no-results").length) {
                jQuery("#exams-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#exam-groups-loading").removeClass("hide");
                jQuery("#groups-table").addClass("hide");
                jQuery("#load-groups").addClass("hide");
                jQuery("#groups-table tbody").empty();
            } else {
                jQuery("#load-groups").addClass("loading");
            }
        }
    });

    jQuery.when(groups).done(function (data) {
        if (jQuery("#exams-no-results").length) {
            jQuery("#exams-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
       
        if (jsonResponse.results > 0) {

            total_groups += parseInt(jsonResponse.results);

            jQuery("#load-groups").html("Showing " + total_groups + " of " + jsonResponse.data.total_groups + " total groups");

            if (jsonResponse.results < group_limit) {
                jQuery("#load-groups").attr("disabled", "disabled");
            } else {
                jQuery("#load-groups").removeAttr("disabled");
            }

            group_offset = (group_limit + group_offset);

            jQuery.each(jsonResponse.data.groups, function (key, group) {
                build_group_row(group);
            });

            if (show_loading_message) {
                jQuery("#exam-groups-loading").addClass("hide");
                jQuery("#groups-table").removeClass("hide");
                jQuery("#load-groups").removeClass("hide");
            } else {
                jQuery("#load-groups").removeClass("loading");
            }

            show_loading_message = false;

        } else {
            jQuery("#exam-groups-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(submodule_text.index.no_groups_found);
            jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
            jQuery("#exam-msgs").append(no_results_div);
        }
    });
}

function build_group_row (group) {

    var group_title_anchor       = document.createElement("a");
    var group_date_anchor        = document.createElement("a");
    //var question_count_anchor         = document.createElement("a");

    jQuery(group_title_anchor).attr({href: ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + group.group_id});
    jQuery(group_date_anchor).attr({href: ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + group.group_id});
    //jQuery(question_count_anchor).attr({href: ENTRADA_URL + "/admin/exams/groups?section=edit-group&group_id=" + group.group_id});

    var group_row            = document.createElement("tr");
    var group_delete_td      = document.createElement("td");
    var group_title_td       = document.createElement("td");
    var group_date_td        = document.createElement("td");
    //var group_questions_td       = document.createElement("td");
    var group_delete_input   = document.createElement("input");

    jQuery(group_delete_input).attr({type: "checkbox", "class": "add-group", name: "groups[]", value: group.group_id});
    jQuery(group_delete_td).append(group_delete_input);
    jQuery(group_title_anchor).html(group.title);
    jQuery(group_title_anchor).attr("id", "group_link_"+group.group_id)
    jQuery(group_date_anchor).html(group.created_date);
    //jQuery(question_count_anchor).html(group.question_count);
    jQuery(group_title_td).append(group_title_anchor);
    jQuery(group_date_td).append(group_date_anchor);
    //jQuery(group_questions_td).append(question_count_anchor);

    jQuery(group_row).append(group_delete_td).append(group_title_td).append(group_date_td).addClass("group-row");
    jQuery("#groups-table").append(group_row);
}

function toggle_active_question_detail(question) {
    var question_id = jQuery(question).closest(".question-container").attr("data-question-id");
    if (!jQuery(question).hasClass("active")) {
        jQuery(".question-container[data-question-id=\"" + question_id + "\"]").find(".question-detail-view").removeClass("hide");
        jQuery(question).addClass("active");
        jQuery(question).find("i").addClass("icon-white");
    } else {
        jQuery(".question-container[data-question-id=\"" + question_id + "\"]").find(".question-detail-view").addClass("hide");
        jQuery(question).removeClass("active");
        jQuery(question).find("i").removeClass("icon-white");
    }
}
