jQuery(function($) {
    sidebarBegone();
    
    var clicked_heading = "";
    var current_depth = 0;
    var loading = false;
    
    var levels = new Array();
    levels[1] = "Primary";
    levels[2] = "Secondary";
    levels[3] = "Tertiary";
    
    function renderDom(jsonResponse) {
        if (jsonResponse.status == "success") {
            if (jsonResponse.data != "bottom_level") {
                $("#curriculum-matrix, #curriculum-matrix-appendix").empty();
                var matrix_table = $(document.createElement("table")).addClass("table table-bordered table-striped").attr("id", "matrix-table");
                var matrix_header = $(document.createElement("thead"));
                var matrix_header_row = $(document.createElement("tr"));
                var matrix_header_row_courses_cell = $(document.createElement("th")).addClass("courses-title-cell");
                matrix_header_row.append(matrix_header_row_courses_cell);
                var matrix_appendix_list = $(document.createElement("ul"));
                var count_tags = {};

                $(jsonResponse.data.objectives).each(function(i, v) {
                    var matrix_header_row_objective_cell = $(document.createElement("th"));
                    if (v.has_children == true) {
                        var matrix_header_row_objective_cell_link = $(document.createElement("a")).attr({"href": "#objective-id=" + v.objective_id, "data-objective-id": v.objective_id, "data-objetive-name" : v.objective_name});
                        matrix_header_row_objective_cell_link.qtip({
                            content: {
                                "text": "Loading...",
                                "url" : $("#curriculum-matrix-parent").attr("action") + "?method=get-objective-details&objective_id=" + v.objective_id
                            }
                        });
                        matrix_header_row.append(matrix_header_row_objective_cell.append(matrix_header_row_objective_cell_link.append(v.objective_name)));
                    } else {
                        var matrix_header_objective_cell_span = $(document.createElement("span")).html(v.objective_name).attr("data-objective-id", v.objective_id);
                        matrix_header_objective_cell_span.qtip({
                            content: {
                                "text": "Loading...",
                                "url" : $("#curriculum-matrix-parent").attr("action") + "?method=get-objective-details&objective_id=" + v.objective_id
                            }
                        });
                        matrix_header_row.append(matrix_header_row_objective_cell.append(matrix_header_objective_cell_span));
                    }

                    var matrix_appendix_list_item = $(document.createElement("li")).attr("data-objective-id", v.objective_id).addClass("objective-" + v.objective_id).html("<strong>" + v.objective_name + "</strong>" + (v.objective_description != null ? "<br />" + v.objective_description : ""));
                    $(matrix_appendix_list).append(matrix_appendix_list_item);
                    count_tags[v.objective_id] = 0;

                });

                $("#curriculum-matrix-appendix").append(matrix_appendix_list);
                matrix_table.append(matrix_header.append(matrix_header_row));

                var last_term = "";
                var term_id = 0;
                for (var i = 0; i < jsonResponse.data.courses.length; i++) {

                    if (last_term != jsonResponse.data.courses[i].term_name) {
                        term_id++;
                        var matrix_body_course_row = $(document.createElement("tr")).addClass("term-row").attr("data-term-id", term_id);
                        var matrix_body_course_row_cell_name = $(document.createElement("td")).addClass("course-cell").append("<a href=\"#\" class=\"term-toggle\"><i class=\"icon-minus-sign\"></i></a> <strong>" + jsonResponse.data.courses[i].term_name + "</strong>");
                        var term_row = $(document.createElement("td")).attr("colspan", jsonResponse.data.objectives.length);
                        matrix_body_course_row.append(matrix_body_course_row_cell_name, term_row);
                        matrix_table.append(matrix_body_course_row);
                    }

                    last_term = jsonResponse.data.courses[i].term_name;

                    var matrix_body_course_row = $(document.createElement("tr")).addClass("term-" + term_id);
                    var matrix_body_course_row_cell_name = $(document.createElement("td")).addClass("course-cell").attr("data-course-id", jsonResponse.data.courses[i].course_id);

                    matrix_body_course_row_cell_name.html("<abbr title=\"" + jsonResponse.data.courses[i].course_name + "\">" + jsonResponse.data.courses[i].course_code + "</abbr>");
                    matrix_body_course_row.append(matrix_body_course_row_cell_name);
                    var l = 1;

                    $(jsonResponse.data.courses[i].objectives).each(function(j, v) {
                        if (v.importance != false) {
                            if ($("#curriculum-matrix-appendix ul .objective-" + v.objective_id).children("ul").length <= 0) {
                                var child_list = $(document.createElement("ul"));
                                $("#curriculum-matrix-appendix ul .objective-" + v.objective_id).append(child_list);
                            }
                            var course_list_item = $(document.createElement("li")).html(jsonResponse.data.courses[i].course_code + " - " + jsonResponse.data.courses[i].course_name + " - " + levels[v.importance] + " Level Objective");
                            $("#curriculum-matrix-appendix ul .objective-" + v.objective_id + " ul").append(course_list_item);
                            count_tags[v.objective_id] += 1;
                        }
                        var matrix_body_course_row_cell_obj = $(document.createElement("td")).html(v.importance != false ? "<a class=\"objective-details\" href=\"#objective-modal\" data-course-id=\""+jsonResponse.data.courses[i].course_id+"\" data-objective-id=\""+v.objective_id+"\" data-toggle=\"modal\"><i class=\"fa fa-star\" aria-hidden=\"true\"></i></a>" : "");
                        matrix_body_course_row.append(matrix_body_course_row_cell_obj);
                        l++;
                    });

                    matrix_table.append(matrix_body_course_row);
                }
                var div = $(document.createElement("div")).addClass("inner").append(matrix_table);
                $("#curriculum-matrix").append(div);
                
                var breadcrumb_list_entry = $(document.createElement("li"));
                var breadcrumb_list_entry_link = $(document.createElement("a"));
                breadcrumb_list_entry_link.append(clicked_heading.attr("data-objetive-name")).attr({"data-objective-id" : clicked_heading.data("objective-id"), "data-objetive-name" : clicked_heading.attr("data-objetive-name")});
                breadcrumb_list_entry.append(" &frasl; ", breadcrumb_list_entry_link);

                $("#curriculum-matrix-breadcrumb").append($("#curriculum-matrix-breadcrumb ul").append(breadcrumb_list_entry));

                var breadcrumb = $("#curriculum-matrix-breadcrumb ul li a");
                var len = breadcrumb.length;

                breadcrumb.each(function(i) {
                    if (i === len - 1) {
                        $(this).css({ "pointer-events" : "none", "color" : "black"});
                    } else {
                        $(this).removeAttr("style");
                    }
                });

                $.each(count_tags, function( index, value ) {
                    $(matrix_header_row).find("[data-objective-id=\"" + index + "\"]").append("<br><small>" + value + " <i class=\"fa fa-star\" aria-hidden=\"true\"></i></small>");
                });
            }
        }
        $("#objective-set").removeProp("disabled");
        loading = false;
    }
    
    function updateMatrix(objective_id) {
        $("#objective-set").attr("disabled", "disabled");
        if (loading == false) {
            loading = true;
            $.ajax({
                type:   $("#curriculum-matrix-parent").attr("method"),
                url:    $("#curriculum-matrix-parent").attr("action"),
                data:   "method=get-curriculum-matrix-level&objective_id=" + objective_id + (current_depth > 0 ? "&depth=" + current_depth : ""),
                success: function(data) {
                    var jsonResponse = JSON.parse(data);
                    renderDom(jsonResponse);
                }
            });
        }
    }
    
    function loadBtnToolbar(select) {
        var selected = select.children("option:selected");
        
        var button_toolbar = $(document.createElement("div")).addClass("btn-toolbar").css("margin", "0").append("<i class=\"icon-chevron-right\"></i>&nbsp;");
        var button_group = $(document.createElement("div")).addClass("btn-group");
        
        for (var i = 1; i <= selected.data("objective-set-depth"); i++) {
            var button = $(document.createElement("a")).addClass("btn" + (i == current_depth ? " active" : "")).html(i).data("objective-set-depth", i);
            button_group.append(button);
        }
        
        button_toolbar.append(button_group);
        
        $("#objective-depth").append(button_toolbar);
        
        if($("#objective-depth").hasClass("hide")) {
            $("#objective-depth").removeClass("hide");
        }
        
        if ($("#download-csv").hasClass("hide")) {
            $("#download-csv").removeClass("hide");
        }
    }

    $("#curriculum-matrix-breadcrumb").on("click", "a", function(e) {
        clicked_heading = $(this);
        $("#curriculum-matrix-breadcrumb ul li").each(function(i, v) {
            if (i >= clicked_heading.closest("li").index()) {
                v.remove();
            }
        });
        updateMatrix(clicked_heading.data("objective-id"));
        e.preventDefault();
    });

    $("#curriculum-matrix").on("click", "thead a", function (e) {
        $("#matrix-table thead a").each(function(i, v) {
            $(v).qtip("hide");
            $(v).qtip("destroy");
        });
        clicked_heading = $(this);
        $.ajax({
            type:   $("#curriculum-matrix-parent").attr("method"),
            url:    $("#curriculum-matrix-parent").attr("action"),
            data:   "method=get-objective-children&objective_id=" + clicked_heading.data("objective-id"),
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                renderDom(jsonResponse);
            }
        });
        $("#download-csv").attr("data-objective-id", $(this).data("objective-id"))
        e.preventDefault();
    });

    $("#curriculum-matrix-parent").on("submit", function(e) {
        $("#curriculum-matrix-breadcrumb, #curriculum-matrix-appendix").empty();
        $("#curriculum-matrix").append("<div class=\"throbber\">Loading...</div>")
        var breadcrumb_list = $(document.createElement("ul"));
        var breadcrumb_list_entry_link = $(document.createElement("a"));
        var selected_option = $("#objective-set option[value=" + $("#objective-set").val() + "]").html();
        breadcrumb_list_entry_link.append(selected_option).attr("data-objective-id", $("#objective-set").val()).attr("data-objetive-name", selected_option);
        $("#curriculum-matrix-breadcrumb").append(breadcrumb_list);
        clicked_heading = breadcrumb_list_entry_link;
        updateMatrix($("#objective-set").val());
        $("#download-csv").attr("data-objective-id", $("#objective-set").val())
        e.preventDefault();
    });
    
    $("#objective-set").on("change", function(e) {
        if (loading == false) {
            $("#curriculum-matrix, #curriculum-matrix-appendix").empty();
            $("#objective-depth").empty();
            current_depth = 1;
            loadBtnToolbar($(this));

            $("#curriculum-matrix-parent").submit();
        } else {
            e.preventDefault();
        }
    });
    
    $("#objective-depth").on("click", ".btn-group a", function(e) {
        if (loading == false) {
            $("#curriculum-matrix, #curriculum-matrix-appendix").empty();
            current_depth = $(this).data("objective-set-depth");
            $(".btn-group a.active").removeClass("active");
            $(this).addClass("active");
            $("#curriculum-matrix-parent").submit();
        } else {
            e.preventDefault();
        }
    });
    
    $("#download-csv").on("click", function(e) {
        window.location = $(this).attr("href") + "?step=2&method=get-matrix-csv&objective_id=" + $(this).data("objective-id") + "&depth=" + current_depth;
        return false;
    });


    $("#curriculum-matrix").on("click", ".objective-details", function(e) {
        $("#objective-modal .modal-body").empty();
        $.ajax({
            type:   $("#curriculum-matrix-parent").attr("method"),
            url:    $("#curriculum-matrix-parent").attr("action"),
            data:   "method=get-mapping&objective_id=" + $(this).data("objective-id") + "&course_id=" + $(this).data("course-id"),
            success: function(data) {
                $("#objective-modal .modal-body").html(data);
            }
        })
    });

    $("#curriculum-matrix").on("click", ".term-toggle", function(e) {
        var term_id = $(this).closest("tr").data("term-id");
        if ($(this).hasClass("term-hidden")) {
            $(this).removeClass("term-hidden");
            $(".term-" + term_id).removeClass("hide");
            $(this).children("i").removeClass("icon-plus-sign").addClass("icon-minus-sign");
        } else {
            $(this).addClass("term-hidden");
            $(".term-" + term_id).addClass("hide");
            $(this).children("i").addClass("icon-plus-sign").removeClass("icon-minus-sign");
        }
        e.preventDefault();
    });

    if (parseInt($("#objective-set").val()) != 0) {
        current_depth = $("#objective-depth").data("depth");
        loadBtnToolbar($("#objective-set"));
        $("#curriculum-matrix-parent").submit();
    }
    
});