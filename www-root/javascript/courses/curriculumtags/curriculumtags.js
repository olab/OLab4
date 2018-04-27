jQuery(document).ready(function ($) {
    $("#curriculum-tag-form").on("change", "input[name=\"selected-target\"]", function (e) {
        $(".bucket-list").addClass("hide");
        $("#upload-instructions").addClass("hide");

        var curriculum_tag_set = $(this).val();
        if ($("#" + curriculum_tag_set + "-container").length > 0) {
            $("#upload-controls").addClass("hide");
            $("#" + curriculum_tag_set + "-container").removeClass("hide");
        } else {
            $("#upload-controls").removeClass("hide");
        }
    });

    var is_advanced_upload = function() {
        var div = document.createElement("div");
        return (("draggable" in div) || ("ondragstart" in div && "ondrop" in div)) && "FormData" in window && "FileReader" in window;
    }();

    var form = $(".upload-form");
    var dragdrop_zone = $(".uploader");

    var input    = form.find("input[type=\"file\"]"),
        label    = form.find("#file-label"),
        showFiles = function (files) {
            label.text(files.length > 1 ? (input.attr("data-multiple-caption") || "").replace( "{count}", files.length ) : files.name);
        };

    if (is_advanced_upload) {
        var dragdrop_files = false;

        dragdrop_zone.addClass("has-advanced-upload");
        dragdrop_zone.on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
            e.preventDefault();
            e.stopPropagation();
        })
        .on("dragover dragenter", function() {
            dragdrop_zone.addClass("is-dragover");
        })
        .on("dragleave dragend drop", function() {
            dragdrop_zone.removeClass("is-dragover");
        })
        .on("drop", function(e) {
            dragdrop_files = e.originalEvent.dataTransfer.files[0];
            showFiles(dragdrop_files);
        })
        .on("change", function (e) {
            showFiles(e.target.files[0]);
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

                if (method == "upload-procedure-criteria") {
                    var error_div = "#error_msgs";
                } else {
                    var error_div = "#msgs"
                }

                $.ajax({
                    url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
                    type: "POST",
                    data: ajax_data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        showLoadingMessage();
                    },
                    complete: function() {
                        form.removeClass("is-uploading");
                    },
                    success: function(data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status == "success") {
                            $(error_div).addClass("hide");
                            if (!jQuery.isEmptyObject(jsonResponse.data.objectives)) {
                                label.html("<span class=\"input-label\">Choose a file</span><span class=\"form-dragdrop\"> or drag and drop it here</span>");
                                $("#upload-controls").addClass("hide");

                                if (method == "upload-curriculum-tag-set") {
                                    var curriculum_tag_shortname = $("input[name=\"curriculum_tag_shortname\"]").val();
                                    list_objectives(curriculum_tag_shortname, jsonResponse.data.objectives);
                                }
                            }
                            if (method == "upload-procedure-criteria") {
                                $("#upload-controls").addClass("hide");
                                $("#epa-select-container").addClass("hide");
                                $("#procedure-criteria-modal-succeeded").removeClass("hide");
                                $("#procedure-upload-icon-" + $("#procedure-modal-procedure-id").val()).removeClass("fa-upload black-icon").addClass("fa-check green-icon");
                            }
                            hideLoadingMessage(true);
                        } else {
                            $(error_div).empty();
                            display_error(jsonResponse.data, error_div);
                            $(error_div).removeClass("hide");
                            hideLoadingMessage(false);
                        }

                    },
                    error: function() {
                        display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], error_div);
                        $(error_div).removeClass("hide");
                        hideLoadingMessage(false);
                    }
                });
            } else {
                // ajax for legacy browsers
            }
        });
    }

    function showLoadingMessage () {
        $(".procedure-criteria-modal-upload-loading").removeClass("hide");
        $(".procedure-criteria-modal-btns").addClass("hide");
    }

    function hideLoadingMessage (success) {
        $(".procedure-criteria-modal-upload-loading").addClass("hide");
        if (success) {
            $(".procedure-criteria-modal-after-upload").removeClass("hide");
        } else {
            $(".procedure-criteria-modal-btns").removeClass("hide");
        }
    }

    function build_objective_table (curriculum_tag_shortname) {
        if ($("#" + curriculum_tag_shortname + "-container").length == 0) {
            var curriculum_tag_title = $("input[name=\"curriculum_tag_shortname\"]").attr("data-label");
            var bucket_div = $(document.createElement("div")).attr({id: curriculum_tag_shortname + "-container"}).addClass("bucket-list");
            var alert_div = $(document.createElement("div")).addClass("alert alert-success").html("You have successfully imported a list of <strong>"+ curriculum_tag_title +"</strong>. If you need to upload an updated version of this list, please contact <a href=\"mailto:healthsci.suport@queensu.ca\">Education Technology support</a>.");
            bucket_div.append(alert_div);
            switch (curriculum_tag_shortname) {
                case "milestone" :
                    var milestone_table = $(document.createElement("table")).attr({id: curriculum_tag_shortname + "-table"}).addClass("table table-striped table-bordered");
                    var table_thead = $(document.createElement("thead"));
                    var head_tr = $(document.createElement("tr"));
                    var code_th = $(document.createElement("th")).html("Code").attr({width: "15%"});
                    var title_th = $(document.createElement("th")).html("Title").attr({width: "30%"});
                    var description_th = $(document.createElement("th")).html("Description").attr({width: "55%"});
                    var table_tbody = $(document.createElement("tbody")).attr({id: curriculum_tag_shortname + "-tbody"});

                    head_tr.append(code_th).append(title_th).append(description_th);
                    table_thead.append(head_tr);
                    milestone_table.append(table_thead).append(table_tbody);
                    bucket_div.append(milestone_table);
                    break;
                case "epa" :
                    var epa_table = $(document.createElement("table")).attr({id: curriculum_tag_shortname + "-table"}).addClass("table table-striped table-bordered");
                    var table_thead = $(document.createElement("thead"));
                    var head_tr = $(document.createElement("tr"));
                    var code_th = $(document.createElement("th")).html("Code").attr({width: "15%"});
                    var title_th = $(document.createElement("th")).html("Title").attr({width: "25%"});
                    var description_th = $(document.createElement("th")).html("Description").attr({width: "30%"});
                    var entrustment_th = $(document.createElement("th")).html("Entrustment").attr({width: "30%"});
                    var table_tbody = $(document.createElement("tbody")).attr({id: curriculum_tag_shortname + "-tbody"});

                    head_tr.append(code_th).append(title_th).append(description_th).append(entrustment_th);
                    table_thead.append(head_tr);
                    epa_table.append(table_thead).append(table_tbody);
                    bucket_div.append(epa_table);
                    break;
            }

            $("#upload-controls").after(bucket_div);
        }
    }

    function list_objectives (curriculum_tag_shortname, objectives) {
        build_objective_table (curriculum_tag_shortname);

        switch (curriculum_tag_shortname) {
            case "milestone" :
                if ($("#" + curriculum_tag_shortname + "-tbody").length > 0) {
                    $.each(objectives, function (key, objective) {
                        var table_row = $(document.createElement("tr"));
                        var code_td = $(document.createElement("td")).html(objective.objective_code);
                        var title_td = $(document.createElement("td")).html(objective.objective_name);
                        var description_td = $(document.createElement("td")).html(objective.objective_description);

                        table_row.append(code_td).append(title_td).append(description_td);
                        $("#" + curriculum_tag_shortname + "-tbody").append(table_row);
                    });
                }
                break;
            case "epa" :
                if ($("#" + curriculum_tag_shortname + "-tbody").length > 0) {
                    $.each(objectives, function (key, objective) {
                        var table_row = $(document.createElement("tr"));
                        var code_td = $(document.createElement("td")).html(objective.objective_code);
                        var title_td = $(document.createElement("td")).html(objective.objective_name);
                        var description_td = $(document.createElement("td")).html(objective.objective_description);
                        var entrustment_td = $(document.createElement("td")).html(objective.objective_secondary_description);

                        table_row.append(code_td).append(title_td).append(description_td).append(entrustment_td);
                        $("#" + curriculum_tag_shortname + "-tbody").append(table_row);
                    });
                }
                break;
        }
    }

    $(document).on("click", ".cv-response-remove", function(e) {
        var table = $(this).parent().parent().parent().parent();
        var tr = $(this).parent().parent();
        var tr_objective_id = tr.data("objective-id");
        var tr_attrib_objective_id = tr.attr("data-objective-id");

        if (tr_objective_id === "-1" || tr_attrib_objective_id === "-1") {
            if ($("."+table.attr("id")+" > tbody > tr").length == 1) {
                $("."+table.attr("id")).addClass("hide");
                $("#"+table.attr("id")+"_button").removeClass("cv-response-add");
            }
            tr.remove();
        } else {
            if (tr_objective_id) {
                $("#delete-contextual-variable-response-modal").removeClass("hide");
                $("#hidden-objective-id").val(tr_objective_id);
            }
        }
    });

    $("#delete-contextual-variable-response-modal-confirm").on("click", function (e) {
        var tr_objective_id = $("#hidden-objective-id").val();
        var tr = $("[data-objective-id=" + tr_objective_id + "]");
        if (tr_objective_id) {
            var remove_cv_response_request = $.ajax({
                url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
                data: {
                    method: "remove-cv-responses",
                    objective_id: tr_objective_id
                },
                type: "POST"
            });

            $.when(remove_cv_response_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                $("#msgs").empty();
                $("#delete-contextual-variable-response-close-button").trigger("click");
                
                if (jsonResponse.status == "success") {
                    if (tr.parent().children().length == 1) {
                        tr.parent().parent().addClass("hide");
                    }
                    tr.remove();
                    display_success([jsonResponse.data], "#msgs");
                } else {
                    display_error(jsonResponse.data, "#msgs");
                }
            });
        }
    });

    $(document).on("click", ".cv-response-save", function() {
        var tr = $(this).parent().parent();
        var tr_objective_id     = tr.data("objective-id");
        var tr_objective_code   = tr.data("objective-code");


        if (tr_objective_id) {
            var save_cv_response_request = $.ajax({
                url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
                data: {
                    method: "save-cv-responses",
                    objective_title: $(this).parent().parent().find(".objective-name").val(),
                    objective_description: $(this).parent().parent().find(".objective-description").val(),
                    objective_id: tr_objective_id,
                    objective_code: tr_objective_code,
                    course_id: $("#course-id").val()
                },
                type: "POST"
            });

            $.when(save_cv_response_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                $("#msgs").empty();
                if (jsonResponse.status === "success") {
                    if (jsonResponse.objective_id != null) {
                        tr.data("objective-id", jsonResponse.objective_id);
                        tr.attr('data-objective-id', jsonResponse.objective_id);
                        tr.find(".cv-response-remove").attr({"href": "#delete-contextual-variable-response-modal", "data-toggle": "modal"});
                    }
                    display_success([jsonResponse.data], "#msgs");
                } else {
                    display_error(jsonResponse.data, "#msgs");
                }
            });
        }
    });

    $(".cv-response-add").on("click", function() {
        var objective_code          = ($(this).data("objective-code"));

        var tr                      = $(document.createElement("tr")).attr({"data-objective-id": "-1", "data-objective-code": objective_code});

        var remove_td               = $(document.createElement("td")).addClass("remove-response-row");
        var remove_a                = $(document.createElement("a")).addClass("btn cv-response-remove").attr({
            "data-toggle": "tooltip",
            "data-original-title": "Remove Response",
            "data-placement": "bottom"
        });
        var remove_i                = $(document.createElement("i")).addClass("fa fa-minus-circle red-icon fa-lg");

        var title_td                = $(document.createElement("td"));
        var title_input             = $(document.createElement("input")).attr({"type": "text"}).addClass("input-xlarge objective-name");

        var description_td          = $(document.createElement("td"));
        var description_textarea    = $(document.createElement("textarea")).attr({"rows": "1"}).addClass("cv-response-description objective-description");

        var save_td                 = $(document.createElement("td")).addClass("save-response-row");
        var save_a                  = $(document.createElement("a")).addClass("btn cv-response-save").attr({
            "data-toggle": "tooltip",
            "data-original-title": "Save Response",
            "data-placement": "bottom"
        });
        var save_i                  = $(document.createElement("i")).addClass("fa fa-floppy-o green-icon fa-lg");

        var upload_td                 = $(document.createElement("td")).addClass("view-upload-criteria");
        var upload_a                  = $(document.createElement("a")).addClass("btn cv-upload-criteria").attr({
            "data-toggle": "tooltip",
            "data-original-title": "Upload New Assessment Criteria",
            "data-placement": "bottom"
        });
        var upload_i                  = $(document.createElement("i")).addClass("fa fa-upload black-icon");

        remove_td.append(remove_a.append(remove_i));
        title_td.append(title_input);
        description_td.append(description_textarea);
        save_td.append(save_a.append(save_i));
        upload_td.append(upload_a.append(upload_i));
        $("body").tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        if($("#"+objective_code).length > 0) {
            $("#"+objective_code+"_button").addClass("btn-block").addClass("cv-response-add");
        }
        if (objective_code == "procedure") {
            $("." + objective_code).removeClass("hide").append(tr.append(remove_td, title_td, description_td, upload_td, save_td));
        } else {
            $("." + objective_code).removeClass("hide").append(tr.append(remove_td, title_td, description_td, save_td));
        }
    });

    $(".toggle-cv-response").on("click", function (e) {
        if ($(this).next(".toggle-cv-response-group").hasClass("collapsed")) {
            $(this).next(".toggle-cv-response-group").removeClass("collapsed");
            $(this).next(".toggle-cv-response-group").slideDown(200);
            $(this).find(".toggle-cv-response-text").html("Hide");
            var objective_code = ($(this).attr("id"));
            if($("#"+objective_code+" > tbody > tr").length == 0) {
                $("#"+objective_code+"_button").removeClass("cv-response-add");
            }
        } else {
            $(this).next(".toggle-cv-response-group").addClass("collapsed");
            $(this).next(".toggle-cv-response-group").slideUp(200);
            $(this).find(".toggle-cv-response-text").html("Show");
        }
    });

    $(".toggle-cv-response-group table tbody").each(function (key, value) {
        if ($(this).children().length == 0) {
            $(this).parent().addClass("hide");
        }
    });

    if ($(".cv-response-remove").length > 0) {
        $(".cv-response-objective").removeClass("hide");
    }

    $(".toggle-cv-response-group").slideToggle(0);

    $(document).on("click", ".cv-upload-criteria", function() {
        var procedure_id = $(this).closest("tr").data("objective-id");

        $("#procedure-modal-procedure-id").val(procedure_id);
        $("#procedure-modal-uploaded-procedure-id").val($(this).closest("tr").data("objective-id"));
        $("#upload-controls").removeClass("hide");
        $("#epa-select-container").removeClass("hide");
        $("#procedure-criteria-modal-succeeded").addClass("hide");
        $(".procedure-criteria-modal-upload-loading").addClass("hide");
        $(".procedure-criteria-modal-after-upload").addClass("hide");
        $(".procedure-criteria-modal-btns").removeClass("hide");
        $("#procedure-criteria-file-input").val('');
        $("#file-label").html(procedure_criteria_uploader_localization.choose_file_message);
        $("#error_msgs").html('').addClass("hide");

        if ($(this).find("i").hasClass("fa-check")) {
            /**
             * Fetch appropriate message based on previous upload
             */
            var msg_request = $.ajax({
                url: ENTRADA_URL + "/admin/courses/cbme",
                data: {
                    section: "api-cbme",
                    method: "get-uploaded-msg",
                    course_id: $("#course-id").val(),
                    procedure_id: + procedure_id,
                    organisation_id: $("#organisation-id").val()
                },
                type: "GET"
            });

            $.when(msg_request).done(function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $("#epa-content-title").html(jsonResponse.data.title);
                    $(".uploaded-date").html(jsonResponse.data.upload_date);
                    $("#procedure-uploaded-criteria-uploader-body").empty();
                    $.each(jsonResponse.data.criteria, function (index, jsonObject) {
                        $("<li/>").loadTemplate(
                            "#criteria-list-template", {
                                'epa-code': jsonObject.objective_code,
                                'objective-id': jsonObject.objective_id,
                                'objective-name': jsonObject.objective_name,
                                'objective-code': jsonObject.objective_code
                            }
                        ).appendTo("#procedure-uploaded-criteria-uploader-body").addClass("epa-code-"+index);
                        if(jsonObject.objective_code == jsonResponse.data.epa_list[index]) {
                            $.each(jsonObject, function (key, object) {
                                if (key !== "objective_code" && key !== "objective_id" && key !== "objective_name") {
                                    $("<div/>").loadTemplate(
                                        "#criteria-details-template", {
                                            "procedure-name": object.title,
                                            "procedure-title": object.procedure_heading === undefined ? "" : object.procedure_heading
                                        }
                                    ).appendTo(".epa-code-"+index+" .procedure-details-container").addClass("container-" + index + "-" +key + " space-above");
                                    $.each(object, function (keyval, criteria) {
                                        if (keyval !== "title" && keyval !== "procedure_heading") {
                                            $("<li/>").loadTemplate(
                                                "#criteria-procedures-template", {
                                                    "description": criteria
                                                }
                                            ).appendTo(".container-" + index + "-" +key + " .criteria-list");
                                        }
                                    });
                                }
                            });
                        }
                    });
                    $("#procedure-criteria-uploaded-msg-modal").modal("show");
                } else {
                    display_error(jsonResponse.data, "#msgs");
                }
            });
        } else {
            $("#procedure-criteria-uploader-modal").modal("show");
        }
    });

    $(document).on("click", ".epa-criteria-toggle", function() {
        if ($(this).next(".criteria-info-container").hasClass("collapsed")) {
            $(this).next(".criteria-info-container").removeClass("collapsed");
            $(this).next(".criteria-info-container").slideDown(200);
            $(this).addClass("rotate");
        } else {
            $(this).next(".criteria-info-container").addClass("collapsed");
            $(this).next(".criteria-info-container").slideUp(200);
            $(this).removeClass("rotate");
        }
    });

    $(document).on("click", "#procedure-criteria-replace-attributes", function () {
        $("#procedure-criteria-uploaded-msg-modal").modal("hide");
        $(".selected-items-list").remove();
        $(".selected_epa_search_target_control").remove();
        $("#selected_epa_list_container").html("");
        $("#procedure-criteria-uploader-modal").modal("show");
    });

    $(document).on("click", ".replace-specific-epa", function () {
        var objective_id = $(this).data("objective-id");
        var objective_name = $(this).data("objective-name");
        var objective_code = $(this).data("objective-code");
        $("#procedure-criteria-uploaded-msg-modal").modal("hide");
        $(".selected_epa_search_target_control").remove();
        $(".selected-items-list").remove();
        $("<ul/>").loadTemplate(
            "#selected-epa-template", {
                "objective-id": objective_id,
                "objective_code_name": objective_code + ": " + objective_name,
                "epa-target-class" : "selected_epa_target_item selected_epa_" + objective_id
            }
        ).appendTo(".entrada-search-widget").addClass("selected-items-list").attr("id", "selected_epa_list_container");
        $("#procedure-criteria-form").append("<input type='hidden' name='selected_epa[]' value='"+objective_id+"' id='selected_epa_"+objective_id+"' data-label='"+objective_code+": "+objective_name+"' class='search-target-control selected_epa_search_target_control' />");
        $("#procedure-criteria-uploader-modal").modal("show");
    });
});