jQuery(document).ready(function ($) {
    $("#reset-cbme-data-btn").on("click", function (e) {
        var form_data = new FormData($("#reset-cbme-data-form").get(0));
        var course_id = $("input[name=\"course_id\"]").val();
        var cbme_data_request = $.ajax({
            url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
            type: "POST",
            data: form_data,
            cache: false,
            contentType: false,
            processData: false,
            error: function() {
                display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], "#msgs");
                $("#modal-msgs").removeClass("hide");
            }
        });

        $.when(cbme_data_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {
                window.location.replace(ENTRADA_URL + "/admin/courses/cbme?section=import-cbme-data&id=" + course_id);
            } else {
                $("#modal-msgs").empty();
                display_error(jsonResponse.data, "#modal-msgs");
                $("#modal-msgs").removeClass("hide");
            }
        });
        e.preventDefault();
    });

    $("#curriculum-tag-option-btn").on("click", function (e) {
        var course_id = $("input[name=\"course_id\"]").val();
        var curriculum_tag_option = $("input[name=\"curriculum_tag_option\"]:checked").val();
        var form_data = new FormData($("#curriculum-tag-option-form").get(0));
        form_data.append("method", "save-curriculum-tag-option");

        var curriculum_tag_option_request = $.ajax({
            url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
            type: "POST",
            data: form_data,
            cache: false,
            contentType: false,
            processData: false,
            complete: function() {
                form.removeClass("is-uploading");
            },
            error: function() {
                display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], "#msgs");
                $("#msgs").removeClass("hide");
            }
        });

        $.when(curriculum_tag_option_request).done(function (data) {
            var jsonResponse = safeParseJson(data);
            if (jsonResponse.status == "success") {
                window.location.replace(ENTRADA_URL + "/admin/courses/cbme?section=import-cbme-data&id=" + course_id);
            } else {
                $("#msgs").empty();
                display_error(jsonResponse.data, "#msgs");
                $("#msgs").removeClass("hide");
            }
        });

        e.preventDefault();
    });

    var is_advanced_upload = function() {
        var div = document.createElement("div");
        return (("draggable" in div) || ("ondragstart" in div && "ondrop" in div)) && "FormData" in window && "FileReader" in window;
    }();

    $(".upload-form").each(function() {
        var form = $(this);
        var id = this.id;
        var dragdrop_zone = $("#"+this.id+"-uploader");
        var input    = form.find("input[type=\"file\"]");
        var label    = form.find("#file-label");
        var course_id = $("input[name=\"course_id\"]").val();
        var msgs = form.find(".msgs");
        var show_files = function (files) {
            label.text(files.length > 1 ? (input.attr("data-multiple-caption") || "").replace( "{count}", files.length ) : files.name);
        };

        if (is_advanced_upload) {
            var dragdrop_files = false;

            dragdrop_zone.addClass("has-advanced-upload");
            dragdrop_zone.on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
                e.preventDefault();
                e.stopPropagation();
            }).on("dragover dragenter", function() {
                dragdrop_zone.addClass("is-dragover");
            }).on("dragleave dragend drop", function() {
                dragdrop_zone.removeClass("is-dragover");
            }).on("drop", function(e) {
                dragdrop_files = e.originalEvent.dataTransfer.files[0];
                show_files(dragdrop_files);
            }).on("change", function (e) {
                show_files(e.target.files[0]);
            });

            form.on("submit", function(e) {
                var method = form.attr("data-method");
                if (form.hasClass("is-uploading")) {
                    return false;
                }

                form.addClass("is-uploading").removeClass("is-error");

                if (is_advanced_upload) {
                    e.preventDefault();

                    var ajax_data = new FormData(form.get(0));
                    ajax_data.append("method", method);

                    if (dragdrop_files) {
                        ajax_data.append(input.attr("name"), dragdrop_files);
                    }

                    var upload_request = $.ajax({
                        url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
                        type: "POST",
                        data: ajax_data,
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            showLoadingMessage(id);
                        },
                        complete: function() {
                            form.removeClass("is-uploading");
                        },
                        error: function() {
                            display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], "#msgs");
                            $("#" + form.attr("id") + " .msgs").removeClass("hide");
                            hideLoadingMessage(id);
                        }
                    });

                    $.when(upload_request).done(function (data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status == "success") {
                            window.location.replace(ENTRADA_URL + "/admin/courses/cbme?section=import-cbme-data&id=" + course_id);
                        } else {
                            $(".msgs").empty();
                            display_error(jsonResponse.data, "#" + form.attr("id") + " .msgs");
                            msgs.removeClass("hide");
                            hideLoadingMessage(id);
                        }
                    });
                }
            });
        }
    });

    function showLoadingMessage (id) {
        $(".cbme-upload-"+id+"-loading").removeClass("hide");
        $(".cbme-upload-"+id+"-btn").addClass("hide");
    }

    function hideLoadingMessage (id) {
        $(".cbme-upload-"+id+"-loading").addClass("hide");
        $(".cbme-upload-"+id+"-btn").removeClass("hide");
    }
});
