jQuery(document).ready(function ($) {

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: "",
        maxDate: ""
    });
    $('.add-on').on('click', function () {
        if ($(this).siblings('input').is(':enabled')) {
            $(this).siblings('input').focus();
        }
    });

    var is_advanced_upload = function () {
        var div = document.createElement("div");
        return (("draggable" in div) || ("ondragstart" in div && "ondrop" in div)) && "FormData" in window && "FileReader" in window;
    }();

    $(document).on("click", ".upload-file-btn", function () {
        $("#course-id").val($(this).attr("data-id"));
    });

    /**
     * Prevent a user from submitting the file upload form by pressing enter
     */
    $(window).keydown(function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            return false;
        }
    });

    $(document).on("click", "#meeting-delete-file-icon", function (e) {
        e.preventDefault();
        $("#hidden-file-id").val($(this).attr("data-id"));
        var replacement_string = "%s";
        var localized_meeting_delete = meetings_index_localization.delete_message.replace(
            replacement_string,
            '<strong>' + $(this).attr("data-html") + '</strong>'
        );
        $(".file-text").html(localized_meeting_delete);
    });

    $(document).on("click", "#meeting-delete-icon", function (e) {
        e.preventDefault();
        $("#hidden-meeting-id").val($(this).attr("data-id"));
        var replacement_string = "%s";
        var localized_meeting_delete = meetings_index_localization.delete_meeting_message.replace(
            replacement_string,
            '<strong>' + $(this).attr("data-html") + '</strong>'
        );
        $(".file-text").html(localized_meeting_delete);
    });

    $(".upload-form").each(function () {
        var form = $(this);
        var id = this.id;
        var dragdrop_zone = $("#" + this.id + "-uploader");
        var label = form.find("#file-label");
        var proxy_id = $(".learner-selector").val();
        var msgs = form.find(".msgs");
        var show_files = function (files) {
            label.empty();
            $.each(files, function (i, file) {
                label.append(file.length > 1 ? (input.attr("data-multiple-caption") || "").replace("{count}", file.length) : file.name);
                if (files.length !== i + 1) {
                    label.append(", ");
                }
            });
        };

        if (is_advanced_upload) {
            var dragdrop_files = false;

            dragdrop_zone.addClass("has-advanced-upload");
            dragdrop_zone.on("drag dragstart dragend dragover dragenter dragleave drop", function (e) {
                e.preventDefault();
                e.stopPropagation();
            }).on("dragover dragenter", function () {
                dragdrop_zone.addClass("is-dragover");
            }).on("dragleave dragend drop", function () {
                dragdrop_zone.removeClass("is-dragover");
            }).on("drop", function (e) {
                dragdrop_files = e.originalEvent.dataTransfer.files;
                show_files(dragdrop_files);
                uploadFiles();
            }).on("change", function (e) {
                show_files(e.target.files);
                uploadFiles();
            });

            function uploadFiles() {
                var method = form.attr("data-method");
                if (form.hasClass("is-uploading")) {
                    return false;
                }

                form.addClass("is-uploading").removeClass("is-error");

                if (is_advanced_upload) {

                    var ajax_data = new FormData(form.get(0));
                    ajax_data.append("method", method);

                    if (dragdrop_files) {
                        $.each(dragdrop_files, function (i, file) {
                            ajax_data.append('files', file);
                        });
                    }

                    var upload_request = $.ajax({
                        url: ENTRADA_URL + "/api/api-meetings.inc.php",
                        type: "POST",
                        data: ajax_data,
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            showLoadingMessage(id);
                        },
                        complete: function () {
                            form.removeClass("is-uploading");
                        },
                        error: function () {
                            display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], "#msgs");
                            $("#" + form.attr("id") + " .msgs").removeClass("hide");
                            hideLoadingMessage(id);
                        }
                    });

                    $.when(upload_request).done(function (data) {
                        var jsonResponse = safeParseJson(data, meetings_index_localization.default_error);
                        if (jsonResponse.status === "success") {
                            hideLoadingMessage(id);
                            showSuccessMessage(id);
                            // Disable uploading past this point, and force the page reload
                            $("#file-meeting-file-upload").on("click", function (e) {
                                e.preventDefault();
                            });
                            $("#meeting-file-upload-uploader").unbind("dragstart");
                            $("#meeting-file-upload-uploader").unbind("dragenter");
                            $("#meeting-file-upload-uploader").unbind("dragend");
                            $("#meeting-file-upload-uploader").unbind("dragover");
                            $("#meeting-file-upload-uploader").unbind("dragleave");
                            $("#meeting-file-upload-uploader").unbind("drag");
                            $("#meeting-file-upload-uploader").unbind("drop");
                            $("#meeting-file-upload-uploader").unbind("change");
                            $("#meeting-file-upload-uploader").unbind("click");
                            $("#upload-file-modal").unbind("hide.bs.modal");
                            $("#upload-file-modal").on("hide.bs.modal", function (e) {
                                e.stopImmediatePropagation();
                                return false;
                            });
                            setTimeout(function () {
                                    location.reload();
                                }, 1000
                            );
                        } else {
                            $(".msgs").empty();
                            display_error(jsonResponse.data, "#" + form.attr("id") + " .msgs");
                            msgs.removeClass("hide");
                            hideLoadingMessage(id);
                        }
                    });
                }
            }
        }
    });

    function showLoadingMessage(id) {
        $(".cbme-upload-" + id + "-loading").removeClass("hide");
        $(".cbme-upload-" + id + "-btn").addClass("hide");
    }

    function hideLoadingMessage(id) {
        $(".cbme-upload-" + id + "-loading").addClass("hide");
        $(".cbme-upload-" + id + "-btn").removeClass("hide");
    }

    function showSuccessMessage(id) {
        $(".cbme-upload-" + id + "-success").removeClass("hide");
        $(".cbme-upload-" + id + "-btn").addClass("hide");
    }

    $('input[name=file-name]').keyup(function () {
        if ($(this).val().length) {
            $(".file-control-group").show();
        } else {
            $(".file-control-group").hide();
        }
    });

});
