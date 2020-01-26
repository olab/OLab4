var folder_id;
var ajax_in_progress = false;
var xhr;

jQuery(function($) {
    $("input.display").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);

        if (clicked.val() === "page_breaks") {
            $("#random_off").prop("checked", true);
            $("#random_on").prop("checked", false);
            $("#random_on").prop("disabled", true);
        } else {
            $("#random_on").prop("disabled", false);
        }
    });

    $(".exam-authors").on("click", ".remove-permission", function(e) {
        e.preventDefault();
        var remove_permission_btn = $(this);
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
    });

    var edit_mode                           = false;
    var next_step_control                   = $("#file_next_step");
    var previous_step_control               = $("#file_previous_step");
    var file_step_control                   = $("#file_step");
    var file_step_container                 = $("#exam-files-step");
    var exam_files_drop_overlay             = $("#exam_files_drop_overlay");
    var exam_files_id_value                 = $("#file_id");
    var file_attach_file                    = $("#exam_files_attach_file");

    get_exam_files();

    var dragdrop = false;

    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    }

    if (dragdrop) {

        /**
         *
         * Event listeners for drag and drop file uploading
         *
         */

        var timer;

        $(".modal-body").on("dragover", function (exam) {
            clearTimeout(timer);
            exam.preventDefault();
            exam.stopPropagation();
            if ($(".modal-body").hasClass("upload")) {
                $("#exam_files_form").addClass("hide");
                exam_files_drop_overlay.removeClass("hide");
            }
        });

        $(".modal-body").on("dragleave", function (exam) {
            exam.preventDefault();
            exam.stopPropagation();
            if ($(".modal-body").hasClass("upload")) {
                timer = setTimeout(function() {
                    exam_files_drop_overlay.addClass("hide");
                    $("#exam_files_form").removeClass("hide");
                }, 200);
            }
            return false;
        });

        $(".modal-body").on("drop", function (exam) {
            exam.preventDefault();
            exam.stopPropagation();

            exam.dataTransfer = exam.originalEvent.dataTransfer;
            var file = exam.dataTransfer.files[0];

            exam_files_drop_overlay.addClass("hide");

            $("#exam-files-msgs").empty();

            $("#exam_files_form").removeClass("hide");
            if ($(".modal-body").hasClass("upload")) {
                upload_file(file);
            }
        });
    }

    $("#exam-files-step").on("change", "#exam_files_upload", function (exam) {
        if (dragdrop) {
            exam.target = exam.originalEvent.target;
            var file = exam.target.files[0];
            upload_file(file);
        } else {
            $("#exam_files_form").submit();
        }
    });

    $("#exam-files-toggle").on("click", function (e) {
        $("#exam-files-delete-confirmation").empty();
        show_step();
        $("#exam-files-modal").modal("show");
    });

    $("#exam-files-modal").on("hide", function () {
        $("#exam_files_modal_title").html("Add Exam File");

        file_step_control.val("1");
        exam_files_id_value.val("");
        file_attach_file.val("no");

        if ($(".modal-body").hasClass("upload")) {
            $(".modal-body").removeClass("upload");
        }

        $("#exam-files-msgs").empty();
        $("#exam_files_loading").addClass("hide");

        if (edit_mode) {
            edit_mode = false;
        }
    });

    $("#exam-files-next").on("click", function () {
        var step                    = parseInt(resource_step_control.val());
        var selected_resource_type  = resource_type_value_control.val();
        $("#exam-files-msgs").empty();
        if (step == 3) {
            edit_mode = false;
            next_step_control.val("0");
            previous_step_control.val("0");

            exam_files_id_value.val("");
            resource_attach_file.val("no");

            if ($(".modal-body").hasClass("upload")) {
                $(".modal-body").removeClass("upload");
            }

            show_step();
        } else {
            var data_string;

            data_string = "method=add-file&step=" + step + "&" + $("#exam_files_form").serialize();

            $.ajax({
                url: API_URL,
                data: data_string,
                type: 'POST',
                success: function (data) {
                    var jsonResponse = JSON.parse(data);
                    if ($("#exam-files-next").is(":disabled")) {
                        $("#exam-files-next").removeAttr("disabled");
                    }

                    if ($("#exam-files-previous").is(":disabled")) {
                        $("#exam-files-previous").removeAttr("disabled");
                    }

                    if (jsonResponse.status === "success") {
                        next_step_control.val(jsonResponse.data.next_step);
                        next_step();
                    } else {
                        display_error(jsonResponse.data, "#exam-files-msgs", "append");
                    }
                },
                beforeSend: function () {
                    $("#exam-files-next").attr("disabled", "disabled");
                    $("#exam-files-previous").attr("disabled", "disabled");
                    $("#exam_files_loading_msg").html("Please wait while your selections are saved");
                    $("#exam_files_form").addClass("hide");
                    $("#exam_files_loading").removeClass("hide");
                },
                complete: function () {
                    $("#exam_files_loading").addClass("hide");
                    $("#exam_files_form").removeClass("hide");
                    $("#exam_files_loading_msg").html("");
                }
            });
        }
    });

    /**
     *
     * Event listeners for file controls
     *
     */

    $("#exam-files-step").on("input", "input[name=exam_files_file_title]", function () {
        $("#exam_files_file_title_value").val($(this).val());
    });

    /**
     *
     * Event listener for the delete modal
     *
     */

    $("#exam-files-resources-section").on("click", ".delete-resource", function (e) {
        e.preventDefault();

        var data_id = $(this).attr("data-id");

        var msg = "Are you sure you want to <strong>delete</strong> this resource?";

        display_notice([msg], "#delete-exam-files-msgs", "append");

        var delete_table            = document.createElement("table");
        var delete_table_thead      = document.createElement("thead");
        var delete_table_tbody      = document.createElement("tbody");
        var delete_headings_tr      = document.createElement("tr");
        var delete_row_tr           = document.createElement("tr");
        var delete_title_th         = document.createElement("th");
        var delete_title_td         = document.createElement("td");

        $(delete_title_th).html("File Title");
        var file_title = $(this).parent().find(".resource-link").html();
        $(delete_title_td).html(file_title);
        $(delete_headings_tr).append(delete_title_th);
        $(delete_row_tr).append(delete_title_td);
        $(delete_table_thead).append(delete_headings_tr);
        $(delete_table_tbody).append(delete_row_tr);
        $(delete_table).append(delete_table_thead).append(delete_table_tbody).addClass("table table-striped table-bordered");
        $("#delete-exam-files-modal .modal-body").append(delete_table);

        $("#delete-exam-files-modal").modal("show");
        $("#delete-exam-files").attr({"data-id": data_id});
    });

    $("#delete-exam-files-modal").on("hide", function () {
        $("#delete-exam-files-msgs").empty();
        $("#delete-exam-files-modal .modal-body table").remove();
    });

    $("#delete-exam-files").on("click", function () {
        var file_id = $(this).attr("data-id");

        $.ajax({
            url: API_URL,
            data: "method=delete-file&file_id=" + file_id,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $("#delete-exam-files-modal").modal("hide");
                    display_success(jsonResponse.data, "#exam-files-delete-confirmation", "append");
                    get_exam_files();
                } else {
                    display_error(jsonResponse.data, "#exam-files-msgs", "append");
                }
            },
            beforeSend: function () {

            },
            complete: function () {

            }
        });
    });

    $(".tag-report").DataTable({
        "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]],
    });

    $(".qbf-selector").on("click", ".folder-selector", function() {
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

    $(".qbf-selector").on("click", ".qbf-back-nav", function() {
        var folder_selected = $(this).data("folder-id");
        folderNavigator(folder_selected, "left");
    });

    $(".qbf-selector").on("click", ".sub-folder-selector", function() {
        var folder_selected = $(this).data("id");
        folderNavigator(folder_selected, "right");
    });

    function folderNavigator(folder_selected, direction) {
        var parent_folder_id = jQuery("#parent_folder_id").val();
        if (ajax_in_progress === false) {
            ajax_in_progress = true;
            jQuery.ajax({
                url: FOLDER_API_URL,
                data: "method=get-sub-folder-selector&folder_type=exam&folder_id=" + folder_selected + "&parent_folder_id=" + parent_folder_id,
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
                        jQuery(".qbf-selector").append(sub_folders);
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

                    jQuery(".qbf-selector").css("height", adjusted_height + "px");
                }
            });
        }
    }


    function next_step () {
        file_step_control.val(next_step_control.val());
        show_step();
    }

    function show_step () {
        $("#exam-files-next").html("Next Step");

        if ($("#exam-files-next").is(":disabled")) {
            $("#exam-files-next").removeAttr("disabled");
        }

        var step = parseInt(file_step_control.val());

        if (step > 1) {
            $("#exam-files-previous").removeClass("hide");
        } else {
            $("#exam-files-previous").addClass("hide");
        }

        $(file_step_container).empty();

        switch (step) {
            case 1 :

                $("#exam_files_loading").addClass("hide");

                /**
                 *
                 * Builds file title controls
                 *
                 */

                var file_file_title_heading         = document.createElement("h3");
                var file_file_title_control_group   = document.createElement("div");
                var file_file_title_controls        = document.createElement("div");
                var file_file_title_input           = document.createElement("input");

                $(file_file_title_heading).html("You can optionally provide a different title for this PDF.");
                $(file_file_title_input).attr({type: "text", id: "exam_files_file_title", name: "exam_files_file_title"}).addClass("input-xlarge").val($("#exam_files_file_title_value").val());
                $(file_file_title_controls).append(file_file_title_input);
                $(file_file_title_control_group).append(file_file_title_heading).append(file_file_title_controls).addClass("control-group");

                file_step_container.append(file_file_title_control_group);

                if (edit_mode) {
                    var attach_file_heading         = document.createElement("h3");
                    var attach_file_control_group   = document.createElement("div");
                    var attach_file_yes_label       = document.createElement("label");
                    var attach_file_yes_radio       = document.createElement("input");
                    var attach_file_no_label        = document.createElement("label");
                    var attach_file_no_radio        = document.createElement("input");


                    $(attach_file_heading).html("Would you like to replace the current PDF with a new one?");
                    $(attach_file_yes_radio).attr({type: "radio", name: "exam_files_attach_file", id: "exam_files_attach_file_yes"}).val("yes");
                    $(attach_file_no_radio).attr({type: "radio", name: "exam_files_attach_file", id: "exam_files_attach_file_no"}).val("no");
                    $(attach_file_yes_label).attr({"for": "exam_files_attach_file_yes"}).append(attach_file_yes_radio).append("Yes, I would like to replace the existing file.").addClass("radio");
                    $(attach_file_no_label).attr({"for": "exam_files_attach_file_no"}).append(attach_file_no_radio).append("No, I do not wish to replace current file.").addClass("radio");

                    $(attach_file_control_group).append(attach_file_heading).append(attach_file_no_label).append(attach_file_yes_label).addClass("control-group");
                    file_step_container.prepend(attach_file_control_group);
                }

                var selected_attach_file_option = $("#exam_files_attach_file").val();
                $("#exam_files_attach_file_" + selected_attach_file_option).prop("checked", true);


                /**
                 *
                 * Builds drag and drop interface
                 *
                 */


                var upload_input        = document.createElement("input");
                var upload_input_div    = document.createElement("div");
                var drag_drop_p         = document.createElement("p");
                var upload_label        = document.createElement("label");
                var upload_span         = document.createElement("span");

                $(upload_span).html("No PDFs selected").addClass("span6 exam-files-upload-span");

                $(upload_input).attr({type: "file", id: "exam_files_upload", name: "file"}).addClass("hide");
                $(upload_label).addClass("btn btn-success span3").append("Browse").append(upload_input);
                $(upload_input_div).append(upload_label).append(upload_span).addClass("exam-files-upload-input-div");
                $(drag_drop_p).html("Please select a file to upload.").addClass("exam-files-upload-text").css("margin-top", "35px");

                if (dragdrop) {
                    var drag_drop_img_div = document.createElement("div");
                    var drag_drop_img = document.createElement("img");

                    $(drag_drop_p).html("You can drag and drop PDFs into this window to upload.").addClass("exam-files-upload-text");
                    $(drag_drop_img).attr({src: ENTRADA_URL + "/images/event-resource-file.png"}).addClass("exam-files-upload-img");
                    $(drag_drop_img_div).append(drag_drop_img).addClass("exam-files-upload-div");
                    file_step_container.append(drag_drop_img_div);
                }
                file_step_container.append(drag_drop_p);
                file_step_container.append(upload_input_div);

                $("#exam-files-next").attr({disabled: "disabled"}).html("Save file");
                $(".modal-body").addClass("upload");

                break;
            case 2 :
                get_exam_files();
                $(".modal-body").removeClass("upload");

                var success_p       = document.createElement("p");
                var success_text_p  = document.createElement("p");

                $(success_p).html((edit_mode ? "Successfully updated the selected <strong>PDF</strong>" : "Successfully attached a <strong>PDF</strong> to this exam.")).attr({id: "exam-files-success-msg"});
                file_step_container.append(success_p);

                $("#exam-files-previous").addClass("hide");

                break;
        }
    }

    function upload_file (file) {
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        var valid_file_type = true;
        var file_size = file.size;

        switch (file.type) {
            case "application/pdf" :
                valid_file_type = true;
                break;
        }

        if (file_size <= 300000000) {
            $("#exam-files-msgs").empty();
            $("#exam_files_loading_msg").html("Uploading file, this may take a few moments.");
            $("#exam_files_form").addClass("hide");
            $("#exam_files_loading").removeClass("hide");

            fd.append("file", file);
            fd.append("method", "add-file");
            fd.append("exam_id", $("#exam_id").val());
            fd.append("exam_files_file_title_value", $("#exam_files_file_title_value").val());
            fd.append("exam_files_attach_file", $("#exam_files_attach_file").val());
            fd.append("step", file_step_control.val());
            fd.append("file_id", exam_files_id_value.val());

            xhr.open('POST', API_URL, true);
            xhr.send(fd);

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    $("#exam_files_loading").addClass("hide");
                    $("#exam_files_form").removeClass("hide");
                    $("#exam_files_loading_msg").html("");
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.status == "success") {
                        next_step_control.val(jsonResponse.data.next_step);
                        previous_step_control.val(jsonResponse.data.previous_step);
                        next_step();
                    } else {
                        $("#exam_files_loading").addClass("hide");
                        $("#exam_files_form").removeClass("hide");
                        $("#exam_files_loading_msg").html("");
                        display_error(jsonResponse.data, "#exam-files-msgs", "append");
                    }
                } else {
                    $("#exam_files_loading").addClass("hide");
                    $("#exam_files_form").removeClass("hide");
                    $("#exam_files_loading_msg").html("");
                }
            }
        } else {
            display_error(["The file you are attempting to upload exceeds the maximum file size limit of 300MB, please select a file with a size of 300MB or less."], "#exam-files-msgs", "append");
        }
    }

    function get_exam_files () {
        var exam_id = $("#exam_id").val();

        var file_list = $("#exam_files");
        $(file_list).empty();

        $.ajax({
            url: API_URL,
            data: "method=get-exam-files&exam_id=" + exam_id,
            type: 'GET',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $(file_list).append(jsonResponse.html);
                } else {
                    $("#exam-files-msgs").append(jsonResponse.data);
                }
            },
            beforeSend: function () {
                $("#exam-files-container-loading").removeClass("hide");
                $("#exam-files-container").addClass("hide");
            },
            complete: function () {
                $("#exam-files-container-loading").addClass("hide");
                $("#exam-files-container").removeClass("hide");
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
});
