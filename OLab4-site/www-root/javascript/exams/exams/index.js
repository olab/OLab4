var folder_id;
var folder_edited_id;
var folder_id_approved;
var copy_parent_folder_id;
var ajax_in_progress = false;
var xhr;
var timeout;
var folder_count_int = 0;

jQuery(document).ready(function ($) {
    if (typeof folder_id_get != "undefined") {
        get_exams(folder_id_get, true, "");
    } else {
        get_exams(0, true, "");
    }

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

    jQuery("#exam-search").keypress(function (event) {
        if (event.keyCode == 13)  {
            event.preventDefault();
        }

        total_exams = 0;
        exam_offset = 0;
        show_loading_message = true;

        clearTimeout(timeout);
        timeout = window.setTimeout(get_exams(folder_id, false, ""), 700);
    });

    jQuery("#load-exams").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_exams(folder_id, true, "more");
        }
    });

    jQuery("#load-previous-exams").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_exams(folder_id, true, "previous");
        }
    });

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
            get_exams(folder_id, false, "");
        }, 100);
    });

    $("#exam-bank-tree").on("mouseenter", ".folder-edit-btn", function() {
        var row = $(this).parents("li");
        $(row).addClass("active");
        highlightFolderRow(row);
        folder_edited_id = $(row).data("sortable-folder-id");
    }).on("mouseleave", ".folder-edit-btn", function() {
        var row = $(this).parents("li");
        $(row).removeClass("active");
        removeFolderRowHighlight(row);
    });

    $("#exam-bank-container").on("click", ".bank-folder", function() {
        var clicked = $(this);
        folder_id = clicked.data("folder-id");
        get_exams(folder_id, true, "");
        folderNavigator(folder_id, "right");
    });

    $("#exam-bank-breadcrumbs").on("click", "a", function() {
        var clicked = $(this);
        folder_id = clicked.data("id");
        get_exams(folder_id, true, "");
        folderNavigator(folder_id, "right");
    });

    var folder_selector = $(".qbf-selector");

    $(folder_selector).on("click", ".folder-selector", function() {
        folder_id = $(this).data("id");
        $(".folder-selector").removeClass("folder-selected");
        $(this).addClass("folder-selected");
    });

    $(folder_selector).on("click", ".qbf-back-nav", function() {
        var folder_selected = $(this).data("folder-id");
        var parent_selector = $(this).parent().parent();
        folderNavigator(folder_selected, "left", parent_selector);
    });

    $(folder_selector).on("click", ".sub-folder-selector", function() {
        var folder_selected = $(this).data("id");
        var parent_selector = $(this).parent().parent().parent().parent().parent();
        folderNavigator(folder_selected, "right", parent_selector);
    });

    $("#add-exam-modal").on("click", ".folder-selector", function () {
       var folder_selected = $(this).data("id");
       $("#folder_id_add_exam").val(folder_selected);
       var exam_title = $("#exam-title").val();
       if (folder_selected != 0 && folder_selected != undefined && exam_title != undefined && exam_title !== "") {
           $("#add-exam-submit").prop("disabled", false);
       } else{
           $("#add-exam-submit").prop("disabled", true);
       }
    });

    $("#add-exam-modal").on("keyup blur change", "#exam-title", function () {
        var folder_selected = $("#folder_id_add_exam").val();
        var exam_title = $("#exam-title").val();
        if (folder_selected != 0 && folder_selected != undefined && exam_title != undefined && exam_title !== "") {
            $("#add-exam-submit").prop("disabled", false);
        } else{
            $("#add-exam-submit").prop("disabled", true);
        }
    });

    $("button#confirm-folder-move").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);
        var type = clicked.data("type");
        $("#parent-folder-modal").modal("hide");
    });

    $("button#cancel-folder-move").on("click", function(e) {
        e.preventDefault();
        $("#parent-folder-modal").modal("hide");
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

    var sub_folder = $("#sub-folder-search");
    var sub_folder_button = sub_folder.find(".btn");

    $(sub_folder_button).on("click", function(e) {
        e.preventDefault();
        subFolderSearch($(this));
    });

    $("#toggle-exam-bank").on("click", function (e) {
        e.preventDefault();
        toggleExamBank($(this));
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
            header:     "Exams Per Page",
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
            get_exams(current_folder_id, false, "");
        }, 100);
    });

    function highlightFolderRow(row) {
        $(row).addClass("AnimationTransparentToYellow");

        if ($(row).hasClass("active")) {
            setTimeout(function () {
                if ($(row).hasClass("active")) {
                    $(row).removeClass("AnimationTransparentToYellow");
                    $(row).css("background-color", "#C8F253");
                }
            }, 500);
        }
    }

    function removeFolderRowHighlight(row) {
        $(row).addClass("AnimationYellowToTransparent");
        $(row).css("background-color", "transparent");

        setTimeout(function() {
            $(row).removeClass("AnimationYellowToTransparent");
        }, 500);
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
                get_exams(current_folder_id, false, "");
            }
        });
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

    /**
     *  Gets all parameters from the current url
     *  @return object object with param names as keys and values
     * */
    function get_current_url_parameters() {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        var query_string = {};
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            // If first entry with this name
            if (typeof query_string[pair[0]] === "undefined") {
                query_string[pair[0]] = decodeURIComponent(pair[1]);
                // If second entry with this name
            } else if (typeof query_string[pair[0]] === "string") {
                var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
                query_string[pair[0]] = arr;
                // If third or later entry with this name
            } else {
                query_string[pair[0]].push(decodeURIComponent(pair[1]));
            }
        }
        return query_string;
    }

    /**
     *  Changes a url parameter to a specified value
     *  @param string param_key param to be changed
     *  @param string param_value new param value to be set
     *  @return string current url with updated param
    * */
    function set_url_param(param_key, param_value){
        var raw_url = (
            window.location.protocol + "//" + window.location.host + window.location.pathname + "?"
        );
        var current_params = get_current_url_parameters();
        current_params[param_key] = param_value;
        var query_a = [];
        var current_param_keys = [];
        for (var pkn in current_params){
            current_param_keys.push(pkn);
        }
        current_param_keys.forEach(function(pk){
            query_a.push(pk + "=" + current_params[pk]);
        });
        return raw_url + query_a.join("&");
    }

    function folderNavigator(folder_selected, direction, parent_selector) {
        if (parent_selector == null) {
            // If parent select is null we are using modal folder seletor
            var next_url = set_url_param("folder_id", folder_selected);
            window.location.assign(next_url);
        } else {
            folder_count_int++;
            var parent_folder_id = jQuery("#parent_folder_id").val();
            if (ajax_in_progress === false) {
                ajax_in_progress = true;
                jQuery.ajax({
                    url: FOLDER_API_URL,
                    data: "method=get-sub-folder-selector&folder_type=exam&folder_id=" + folder_selected + "&parent_folder_id=" + parent_folder_id,
                    type: "GET",
                    success: function (data) {
                        var jsonAnswer = JSON.parse(data);
                        var folder_count = jsonAnswer.folder_count;
                        var current_folder = jQuery(".qbf-folder.active");
                        var sub_folders = document.createElement("span");
                        sub_folders.setAttribute("id", "qbf-folder-" + folder_selected + "-" + folder_count_int);
                        sub_folders.setAttribute("class", "qbf-folder active");

                        if (direction === "left") {
                            jQuery(sub_folders).animate({
                                right: "250"
                            }, 0);
                        } else if (direction === "right") {
                            jQuery(sub_folders).animate({
                                left: "250"
                            }, 0);
                        }

                        if (jsonAnswer.status_folder == "success") {
                            var subfolder_html = jsonAnswer.subfolder_html;
                            jQuery(sub_folders).append(subfolder_html);
                            jQuery(parent_selector).append(sub_folders);
                            jQuery(current_folder).removeClass("active");
                            var new_folder = jQuery("#qbf-folder-" + folder_selected + "-" + folder_count_int);

                            if (direction === "left") {
                                jQuery(current_folder).animate({
                                    left: "250"
                                }, 350, function () {
                                    jQuery(current_folder).remove();
                                });

                                jQuery(new_folder).animate({
                                    right: "30"
                                }, 350);
                            } else if (direction === "right") {
                                jQuery(current_folder).animate({
                                    right: "250"
                                }, 350, function () {
                                    jQuery(current_folder).remove();
                                });

                                jQuery(new_folder).animate({
                                    left: "5"
                                }, 350);
                            }
                        }

                        if (jsonAnswer.status_nav == "success") {
                            jQuery(parent_selector).find("#qbf-nav").html(jsonAnswer.nav_html);
                        }

                        if (jsonAnswer.status_title == "success") {
                            jQuery(parent_selector).find("#qbf-title").html(jsonAnswer.title_html);
                        }

                        ajax_in_progress = false;

                        var folder_selector_height = jQuery(".folder-selector").outerHeight();
                        var adjusted_height = folder_count * folder_selector_height + 110;

                        if (adjusted_height < 350) {
                            adjusted_height = 350;
                        }

                        jQuery(parent_selector).css("height", adjusted_height + "px");
                    }
                });
            }
        }
    }

    function toggleExamBank(clicked) {
        var icon = $(clicked).children(i);
        if (icon.hasClass("fa-eye")) {
            $("#folders").addClass("hide");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            $("#folders").removeClass("hide");
            icon.addClass("fa-eye").removeClass("fa-eye-slash");
        }
    }

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

    $("#move-exam-modal").on("show.bs.modal", function (e) {
        buildMoveExamFolderSelector();
        buildMoveExam();
    });

    $("#move-exam-modal").on("hide.bs.modal", function (e) {
        $("#move-exams-container").html("");
    });

    $("#move-exams-modal-move").on("click", function(e) {
        e.preventDefault();
        moveApprovedExams();
    });

    var exams_selected_move = $("#exams-selected-move");

    exams_selected_move.on("click", ".folder-selector", function() {
        $("#move-exams-modal-move").prop("disabled", false);
    });

    exams_selected_move.on("click", ".qbf-back-nav", function() {
        $("#move-exams-modal-move").prop("disabled", true);
    });

    exams_selected_move.on("click", ".sub-folder-selector", function() {
        $("#move-exams-modal-move").prop("disabled", true);
    });

    /*
     Folders Actions
     */

    $("#copy-folder-modal").on("show.bs.modal", function (e) {
        buildCopyFolder();
    });

    $("#copy-folder-modal").on("hide.bs.modal", function (e) {
        $("#move-folder-container").html("");
    });

    $("#copy-folder-modal-copy").on("click", function(e) {
        e.preventDefault();
        copyApprovedFolder();
    });

    $("#move-folder-modal").on("show.bs.modal", function (e) {
        buildMoveFolder();
    });

    $("#move-folder-modal").on("hide.bs.modal", function (e) {
        $("#move-folder-container").html("");
    });

    $("#move-folder-modal-move").on("click", function(e) {
        e.preventDefault();
        moveApprovedFolder();
    });


    $("#delete-folder-modal").on("show.bs.modal", function (e) {
        buildDeleteFolder();
    });

    $("#delete-folder-modal").on("hide.bs.modal", function (e) {
        $("#move-folder-container").html("");
    });

    $("#delete-folder-modal-delete").on("click", function(e) {
        e.preventDefault();
        deleteFolder();
    });

    $("#exams-table").on("click", ".select-exam", function (e) {
        e.preventDefault();
        $(this).trigger("change");
    });

    $("#exams-table").on("change", ".select-exam", function (e) {
        var span = $(this);
        var icon = $(this).find(".select-exam-icon");
        selectExam(span, icon);
    });

    function selectExam(span, icon) {
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

    function buildMoveExamFolderSelector() {
        var parent_selector = $("#move-exam-modal .qbf-selector");

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: "method=get-sub-folder-selector&folder_type=exam&folder_id=" + folder_id + "&parent_folder_id=" + folder_id,
            type: "GET",
            success: function (data) {
                var jsonAnswer      = JSON.parse(data);
                var folder_count    = jsonAnswer.folder_count;

                parent_selector.find(".qbf-folder").remove();

                var sub_folders     = document.createElement("span");
                sub_folders.setAttribute("id", "qbf-folder-" + folder_id + "-" + folder_count_int);
                sub_folders.setAttribute("class", "qbf-folder active");

                if (jsonAnswer.status_folder === "success") {
                    var subfolder_html = jsonAnswer.subfolder_html;
                    jQuery(sub_folders).append(subfolder_html);
                    jQuery(parent_selector).append(sub_folders);
                }

                if (jsonAnswer.status_nav === "success") {
                    jQuery(parent_selector).find("#qbf-nav").html(jsonAnswer.nav_html);
                }

                if (jsonAnswer.status_title === "success") {
                    jQuery(parent_selector).find("#qbf-title").html(jsonAnswer.title_html);
                }

                var folder_selector_height = jQuery(".folder-selector").outerHeight();
                var adjusted_height = folder_count * folder_selector_height + 110;

                if (adjusted_height < 350) {
                    adjusted_height = 350;
                }

                jQuery(parent_selector).css("height", adjusted_height + "px");
            }
        });
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
                    if (jsonResponse.status === "success") {
                        var move_permissions = jsonResponse.move_permission;

                        if (typeof move_permissions && move_permissions !== "undefined") {
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
                    } else if (jsonResponse.status === "error") {
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
        var selected_move_folder = jQuery(".qbf-selector").find(".folder-selected").data("id");

        var dataObject = {
            method : "move-exams",
            move_ids: exam_ids_approved,
            destination_folder_id: selected_move_folder
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    get_exams(current_folder_id, false, "");
                    $("#move-exam-modal").modal("hide");
                } else if (jsonResponse.status == "error") {
                    $("#move-exam-modal").modal("hide");
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


    function buildMoveFolder() {
        $("#move-folder-msg").removeClass("alert").removeClass("alert-success").removeClass("alert-error").html();

        var parent_selector = $("#move-folder-modal .qbf-selector");

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: "method=get-sub-folder-selector&folder_type=exam&folder_id=" + folder_id + "&parent_folder_id=" + folder_id,
            type: "GET",
            success: function (data) {
                var jsonAnswer      = JSON.parse(data);
                var folder_count    = jsonAnswer.folder_count;

                parent_selector.find(".qbf-folder").remove();

                var sub_folders     = document.createElement("span");
                sub_folders.setAttribute("id", "qbf-folder-" + folder_id + "-" + folder_count_int);
                sub_folders.setAttribute("class", "qbf-folder active");

                if (jsonAnswer.status_folder === "success") {
                    var subfolder_html = jsonAnswer.subfolder_html;
                    jQuery(sub_folders).append(subfolder_html);
                    jQuery(parent_selector).append(sub_folders);
                }

                if (jsonAnswer.status_nav === "success") {
                    jQuery(parent_selector).find("#qbf-nav").html(jsonAnswer.nav_html);
                }

                if (jsonAnswer.status_title === "success") {
                    jQuery(parent_selector).find("#qbf-title").html(jsonAnswer.title_html);
                }

                var folder_selector_height = jQuery(".folder-selector").outerHeight();
                var adjusted_height = folder_count * folder_selector_height + 110;

                if (adjusted_height < 350) {
                    adjusted_height = 350;
                }

                jQuery(parent_selector).css("height", adjusted_height + "px");
            }
        });

        var dataObject = {
            method : "get-folder-permissions",
            get_title: 1,
            get_parent_folder: 1,
            folder_id: folder_edited_id
        };

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        var list = document.createElement("ul");
                        var list_question = document.createElement("li");
                        if (jsonAnswer.edit_folder == 1) {
                            folder_id_approved = folder_edited_id;
                            copy_parent_folder_id = jsonAnswer.parent_folder_id;
                            $(list_question).append("<span><strong>" + jsonAnswer.title + "</strong> - approved to be moved.</span>");
                            $("#move-folder-modal-move").prop("disabled", false);
                        } else {
                            folder_id_approved = 0;
                            copy_parent_folder_id = 0;
                            $("#move-folder-modal-move").prop("disabled", true);
                            $(list_question).append("<span><strong>" + jsonAnswer.title + "</strong> - can not be moved.</span>");
                        }

                        $(list).append(list_question);

                        $("#folder-selected-move").removeClass("hide");
                        $("#move-folder-container").html("").append(list);
                    }
                }
            }
        });
    }



    function buildCopyFolder() {
        $("#copy-folder-msg").removeClass("alert").removeClass("alert-success").removeClass("alert-error").html();

        var parent_selector = $("#copy-folder-modal .qbf-selector");

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: "method=get-sub-folder-selector&folder_type=exam&folder_id=" + folder_id + "&parent_folder_id=" + folder_id,
            type: "GET",
            success: function (data) {
                var jsonAnswer      = JSON.parse(data);
                var folder_count    = jsonAnswer.folder_count;

                parent_selector.find(".qbf-folder").remove();

                var sub_folders     = document.createElement("span");
                sub_folders.setAttribute("id", "qbf-folder-" + folder_id + "-" + folder_count_int);
                sub_folders.setAttribute("class", "qbf-folder active");

                if (jsonAnswer.status_folder == "success") {
                    var subfolder_html = jsonAnswer.subfolder_html;
                    jQuery(sub_folders).append(subfolder_html);
                    jQuery(parent_selector).append(sub_folders);
                }

                if (jsonAnswer.status_nav == "success") {
                    jQuery(parent_selector).find("#qbf-nav").html(jsonAnswer.nav_html);
                }

                if (jsonAnswer.status_title == "success") {
                    jQuery(parent_selector).find("#qbf-title").html(jsonAnswer.title_html);
                }

                var folder_selector_height = jQuery(".folder-selector").outerHeight();
                var adjusted_height = folder_count * folder_selector_height + 110;

                if (adjusted_height < 350) {
                    adjusted_height = 350;
                }

                jQuery(parent_selector).css("height", adjusted_height + "px");
            }
        });

        var dataObject = {
            method : "get-folder-permissions",
            get_title: 1,
            get_parent_folder: 1,
            folder_id: folder_edited_id
        };

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        var list = document.createElement("ul");
                        var list_question = document.createElement("li");
                        if (jsonAnswer.edit_folder == 1) {
                            folder_id_approved = folder_edited_id;
                            copy_parent_folder_id = jsonAnswer.parent_folder_id;
                            $(list_question).append("<span><strong>" + jsonAnswer.title + "</strong> - approved to copy.</span>");
                            $("#copy-folder-modal-copy").prop("disabled", false);
                        } else {
                            folder_id_approved = 0;
                            copy_parent_folder_id = 0;
                            $("#copy-folder-modal-copy").prop("disabled", true);
                            $(list_question).append("<span><strong>" + jsonAnswer.title + "</strong> - can not be copied.</span>");
                        }

                        $(list).append(list_question);

                        $("#copy-folder-move").removeClass("hide");
                        $("#copy-folder-container").html("").append(list);
                    }
                }
            }
        });
    }

    function copyApprovedFolder() {
        var dataObject = {
            method : "copy-folder",
            folder_id_approved: folder_id_approved,
            folder_id_destination: folder_id
        };

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        $("#copy-folder-msg").addClass("alert alert-success").html(jsonAnswer.msg);

                        get_exams(copy_parent_folder_id, false, "");
                    } else {
                        $("#copy-folder-msg").addClass("alert alert-error").html(jsonAnswer.msg);
                    }
                }
            }
        });
    }

    function moveApprovedFolder() {
        if (folder_id_approved != 0) {
            if (folder_id_approved != folder_id) {
                var dataObject = {
                    method : "move-folder",
                    folder_id_approved: folder_id_approved,
                    folder_id_destination: folder_id
                };

                jQuery.ajax({
                    url: FOLDER_API_URL,
                    data: dataObject,
                    type: "POST",
                    success: function (data) {
                        if (data) {
                            var jsonAnswer = JSON.parse(data);
                            if (jsonAnswer.status == "success") {
                                $("#move-folder-msg").addClass("alert alert-success").html(jsonAnswer.msg);

                                get_exams(copy_parent_folder_id, false, "");
                            } else {
                                $("#move-folder-msg").addClass("alert alert-error").html(jsonAnswer.msg);
                            }
                        }
                    }
                });
            } else {
                // can't move a folder into itself
                $("#move-folder-msg").addClass("alert alert-error").html("The destination folder can not be the same folder.");
            }

        } else {
            // need to select a folder
            $("#move-folder-msg").addClass("alert alert-error").html("Please select a folder.");
        }
    }

    function buildDeleteFolder() {

        $("#delete-folder-msg").removeClass("alert").removeClass("alert-success").removeClass("alert-error").html();

        var dataObject = {
            method : "get-folder-delete-permission",
            folder_ids: folder_edited_id
        };

        folder_id_approved = 0;
        copy_parent_folder_id = 0;

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        var folders = jsonAnswer.folder_ids;
                        var list = document.createElement("ul");
                        $.each(folders, function(key, value) {
                            var title = value.title;
                            var list_question = document.createElement("li");
                            if (value.delete == true) {
                                folder_id_approved = folder_edited_id;
                                copy_parent_folder_id = jsonAnswer.parent_folder_id;
                                $(list_question).append("<span><strong>" + title + "</strong> - approved to delete.</span>");
                                $("#delete-folder-modal-delete").prop("disabled", false);
                            } else {
                                $("#delete-folder-modal-delete").prop("disabled", true);
                                $(list_question).append("<span><strong>" + title + "</strong> - can not be deleted.</span>");
                            }
                            $(list).append(list_question);

                        });

                        $("#folders-selected-delete").removeClass("hide");
                        $("#delete-folder-container").html("").append(list);
                    }
                }
            }
        });
    }


    function deleteFolder() {
        var dataObject = {
            method : "delete-folders",
            delete_ids: folder_id_approved,
            type: "folder"
        };

        jQuery.ajax({
            url: FOLDER_API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                if (data) {
                    var jsonAnswer = JSON.parse(data);
                    if (jsonAnswer.status == "success") {
                        $("#delete-folder-msg").addClass("alert alert-success").html(jsonAnswer.msg);

                        get_exams(copy_parent_folder_id, false, "");
                    } else {
                        $("#delete-folder-msg").addClass("alert alert-error").html(jsonAnswer.msg);
                    }
                }
            }
        });
    }
});


function get_exams(folder_id, popstate, offset) {
    // resets check variable on folder change
    questions_checked = {};

    if (jQuery("#search-targets-exam").length > 0) {
        total_exams = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-exam").serialize();
    }

    var search_term = jQuery("#exam-search").val();

    if ( typeof search_term === "undefined") {
        search_term = "";
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
        (typeof filters !== "undefined" ? "&" + filters : "") +
        (typeof folder_id !== "undefined" ? "&folder_id=" + folder_id : "" );

    if (exams_xhr) {
        exams_xhr.abort();
    }

    exams_xhr = jQuery.ajax({
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
                 jQuery("#exams-loaded-display").html("");
                jQuery("#exams-table tbody").empty();
            }
        },
        success: function (data) {
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

                if (typeof jsonResponse.data.exams !== "undefined") {
                    jQuery("#exams-table tbody").empty();
                    jQuery.each(jsonResponse.data.exams, function (key, exam) {
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
                jQuery("#exams-loaded-display").html("Showing 0 exams");
                var no_results_div = jQuery(document.createElement("div"));
                var no_results_p = jQuery(document.createElement("p"));

                no_results_p.html(submodule_text.index.no_exams_found);
                jQuery(no_results_div).append(no_results_p).attr({id: "exams-no-results"});
                jQuery("#exam-msgs").append(no_results_div);
            }


            // Breadcrumbs
            if (jsonResponse.data.status_breadcrumbs == "success") {
                var html_breadcrumbs = jsonResponse.data.breadcrumb_data;
                jQuery("#exam-bank-breadcrumbs").html(html_breadcrumbs);
            } else if (jsonResponse.data.status_breadcrumbs == "error") {
                jQuery("#msgs").append("<p>" + jsonResponse.data.status_breadcrumbs_error + "</p>");
            } else if (jsonResponse.data.status_breadcrumbs == "notice") {
                jQuery("#msgs").append("<p>" + jsonResponse.data.status_breadcrumbs_error + "</p>");
            }

            // SubFolders
            if (jsonResponse.data.status_folder == "success") {
                var html_folders = jsonResponse.data.subfolder_html;
                var title = jsonResponse.data.title;
                jQuery("#exam-bank-tree #folders").html(html_folders);
                /*
                 Initiates the folder sorting if the user can edit the parent folders
                 */
                if (jsonResponse.edit_folder == 1) {
                    jQuery("#folder_ul").each(makeSortable);
                }

                jQuery("#exam-bank-tree-title").text(title);
            } else if (jsonResponse.data.status_folder == "error") {
                jQuery("#msgs").append("<p>" + jsonResponse.data.status_folder_error + "</p>");
            } else if (jsonResponse.data.status_folder == "notice") {
                jQuery("#msgs").append("<p>" + jsonResponse.data.status_folder_error + "</p>");
            }

            // renders url window for reloads or copying the url.
            // supported in IE 10 and up, all other browsers support
            // window.atob is an object first in IE 10

            if (window.atob) {
                if (popstate === true) {
                    var stateObject = JSON.stringify({
                        folder: folder_id
                    });

                    var url = "exams?folder_id=" + folder_id;

                    history.pushState(stateObject, "Navigation", url);
                }
            } else {
                //old IE or
            }
            //stores the new folder_id in the local variable for other functions to use.
            current_folder_id = folder_id;
        }
    });
}

function makeSortable() {
    jQuery(this).sortable({
        opacity: 0.7,
        update: function () {
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
    });
}

window.onpopstate = function(event) {
    var state = JSON.parse(event.state);
    if (typeof state &&  state !== null) {
        if (typeof state && state !== "undefined") {
            var folder_id = state.folder;
            get_exams(folder_id, false, "");
        } else {
            get_exams(0, false, "");
        }
    }
};
