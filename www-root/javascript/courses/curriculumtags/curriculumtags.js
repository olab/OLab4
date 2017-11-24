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

    var form = $("#curriculum-tag-form");
    var dragdrop_zone = $("#curriculum-tag-upload");

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
            if (form.hasClass("is-uploading")) {
                return false;
            }

            form.addClass("is-uploading").removeClass("is-error");

            if (is_advanced_upload) {
                e.preventDefault();

                var ajax_data = new FormData(form.get(0));
                ajax_data.append("method", "upload-curriculum-tag-set");

                if (dragdrop_files) {
                    ajax_data.append(input.attr("name"), dragdrop_files);
                }

                $.ajax({
                    url: ENTRADA_URL + "/admin/courses/cbme?section=api-curriculumtags",
                    type: "POST",
                    data: ajax_data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    complete: function() {
                        form.removeClass("is-uploading");
                    },
                    success: function(data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status == "success") {
                            $("#msgs").addClass("hide");
                            if (!jQuery.isEmptyObject(jsonResponse.data.objectives)) {
                                label.html("<span class=\"input-label\">Choose a file</span><span class=\"form-dragdrop\"> or drag and drop it here</span>");
                                $("#upload-controls").addClass("hide");
                                var curriculum_tag_shortname = $("input[name=\"curriculum_tag_shortname\"]").val();
                                list_objectives(curriculum_tag_shortname, jsonResponse.data.objectives);
                            }
                        } else {
                            $("#msgs").empty();
                            display_error(jsonResponse.data, "#msgs");
                            $("#msgs").removeClass("hide");
                        }
                    },
                    error: function() {
                        display_error(["A problem occurred while attempting to upload the selected file, please try again at a later time."], "#msgs");
                        $("#msgs").removeClass("hide");
                    }
                });
            } else {
                // ajax for legacy browsers
            }
        });
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
                    var title_th = $(document.createElement("th")).html("Title").attr({width: "85%"});
                    var table_tbody = $(document.createElement("tbody")).attr({id: curriculum_tag_shortname + "-tbody"});

                    head_tr.append(code_th).append(title_th);
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

                        table_row.append(code_td).append(title_td);
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
});