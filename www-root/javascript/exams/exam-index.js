jQuery(document).ready(function ($) {
    
    $('#exam_index_tabs a[data-toggle="tab"]').on('shown', function(e){
        createCookie(proxy_id+'exam_index_last_tab', $(e.target).attr('href'));
    });

    var lastTab = readCookie(proxy_id+'exam_index_last_tab');
    if (lastTab) {
        $('#exam_index_tabs a[href=' + lastTab + ']').tab('show');
    } else {
        $('#exam_index_tabs a[data-toggle="tab"]:first').tab('show');
    }

    if (!lastTab) {
        lastTab = "#" + jQuery("#exams > div.tab-pane.active").attr("id");
    }

    var status;
    //status = fetchStatusByTab(lastTab);

    if (lastTab == "#exam_results") {
        //fetchExamsOnUser(exam_index_view_preference, proxy_id, lastTab);
    } else {
        //fetchExams(exam_index_view_preference, status, lastTab);
    }
    /*
    function fetchStatusByTab(lastTab) {
        var m_status = [];
        switch(lastTab) {
            case "#exams_to_complete":
                m_status = ["inprogress", "awaitingcompletion"];
            break;
            case "#completed_exams":
            case "#exam_results":
                m_status = ["complete"];
            break;
        }
        return m_status;
    }

    
    $('#exam_index_tabs a').click(function (e) {
        e.preventDefault();
        $("#exam-list").remove();
        $(".exam-grid-question").remove();
        $(".exams-loading").removeClass("hide");
        $(this).tab('show');
        var activeTab = e.target;
        status = fetchStatusByTab($(activeTab).attr("href"));
        if ($(activeTab).attr("href") == "#exam_results") {
            fetchExamsOnUser(exam_index_view_preference, proxy_id, $(activeTab).attr("href"));
        } else {
            fetchExams(exam_index_view_preference, status, $(activeTab).attr("href"));
        }
    });
    
    $(".view-toggle[data-view='"+ exam_index_view_preference +"']").addClass("active");
    $(".exams-loading").removeClass("hide");

    $(".view-toggle").on("click", function (e) {
        if (!$(this).hasClass("active")) {
            e.preventDefault();
            $("#exam-list").remove();
            $(".exam-grid-question").remove();
            $(".exams-loading").removeClass("hide");
            exam_index_view_preference = $(this).attr("data-view");
            var current_tab = jQuery("#exams > div.tab-pane.active");
            status = fetchStatusByTab("#" + $(current_tab).attr("id"));
            if ($(current_tab).attr("id") == "exam_results") {

                fetchExamsOnUser(exam_index_view_preference, proxy_id, "#" + $(current_tab).attr("id"));
            } else {
                fetchExams(exam_index_view_preference, status, "#" + $(current_tab).attr("id"));
            }
        }
    });
    */

    function fetchExamsOnUser(exam_index_view_preference, proxy_id, lastTab) {
        var search_term = jQuery("#exam-search").val();
        $.ajax({
            "url": ENTRADA_URL +"/exams?section=api-exams",
            "type": "GET",
            "data": {
                "method": "list-exams-by-user",
                "exam_index_view_preference": exam_index_view_preference,
                "proxy_id": proxy_id,
                "search_term": search_term
            },
            "success": function(data) {
                jQuery(".exams-loading").addClass("hide");
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    switch (exam_index_view_preference) {
                        case "grid" :
                            listExamsByUserGrid(jsonResponse.data.exams, lastTab);
                            break;
                        case "table" :
                            listExamsByUserTable(jsonResponse.data.exams, lastTab);
                            break;
                    }
                } else {
                    //ToDo: display error message
                }
            }
        });
    }

    function fetchExams(exam_index_view_preference, status, lastTab) {
        var search_term = jQuery("#exam-search").val();
        $.ajax({
            "url": ENTRADA_URL +"/exams?section=api-exams",
            "type": "GET",
            "data": {
                "method": "list-exams",
                "exam_index_view_preference": exam_index_view_preference,
                "status": status,
                "search_term": search_term
            },
            "success": function(data) {
                console.log(data);
                jQuery(".exams-loading").addClass("hide");

                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    switch (exam_index_view_preference) {
                        case "grid" :
                            listExamsGrid(jsonResponse.data.exams, lastTab);
                            break;
                        case "table" :
                            listExamsTable(jsonResponse.data.exams, lastTab);
                            break;
                    }
                } else {
                    //ToDo: display error message
                }
            }
        });
    }

    function listExamsGrid(exams, lastTab) {
        var group_div = document.createElement("div");
        jQuery(group_div).addClass("row space-below medium exam-grid-question");

        var media_div;
        if (exams.length > 0) {
            jQuery.each(exams, function (index, exam) {
                media_div = buildGrid(exam);
                jQuery(group_div).append(media_div);
            });
        } else {
            var empty_div = document.createElement("div");
            jQuery(empty_div).addClass("media course-members-media-list");
            var empty_p = document.createElement("p");
            $(empty_p).html("No Exams found.");
            $(empty_p).addClass("exam-search-message");
            $(empty_div).append(empty_p);
            jQuery(group_div).append(empty_div);
        }

        jQuery(lastTab).append(group_div);
    }

    function buildGrid (exam) {
        var media_div = document.createElement("div");
        var media_body = document.createElement("div");
        var media_heading = document.createElement("h4");
        var media_heading_a = document.createElement("a");
        var media_heading_small = document.createElement("small");
        var media_p = document.createElement("p");
        var image_holder = document.createElement("div");
        jQuery(image_holder).addClass("img-holder pull-left");
        var image = document.createElement("img");
        $(image).addClass("media-object grid-view-thumb img-rounded");
        $(image).attr("src", exam.img_src);
        $(image).attr("alt", "Target Image");
        var image_anchor = document.createElement("a");
        $(image_anchor).attr("href", exam.link);
        $(image_anchor).append(image);
        $(image_holder).append(image_anchor);

        jQuery(media_div).addClass("media course-members-media-list");
        jQuery(media_body).addClass("media-body");
        jQuery(media_heading).addClass("media-heading");
        jQuery(media_heading_a).addClass("print-black").text(exam.targets[0].name).attr({href: exam.link}).html();
        jQuery(media_heading_small).addClass("pull-right print-black").text(exam.title).html();
        var start_date = new Date(exam.startDate * 1000);
        var end_date = new Date(exam.endDate * 1000);
        jQuery(media_p).html("Rotation Dates: " + start_date.toLocaleDateString() + " to " + end_date.toLocaleDateString() + "<br />" + "Program: " + exam.program.name);

        jQuery(media_heading).append(media_heading_a).append(media_heading_small);
        jQuery(media_body).append(media_heading).append(media_p)
        jQuery(media_div).append(image_holder).append(media_body);

        return media_div;
    }


    function listExamsTable(exams, lastTab) {
        var table = document.createElement("table");
        $(table).attr("id", "exam-list");
        var table_head = document.createElement("thead");
        var target_heading = document.createElement("th");
        var rotation_heading = document.createElement("th");
        var program_heading = document.createElement("th");
        var start_date_heading = document.createElement("th");
        var end_date_heading = document.createElement("th");
        var due_date_heading = document.createElement("th");
        var status_heading = document.createElement("th");

        jQuery(target_heading).text(exams_index["Target"]);
        jQuery(rotation_heading).text(exams_index["Rotation"]);
        jQuery(program_heading).text(exams_index["Program"]);
        jQuery(start_date_heading).text(exams_index["Start Date"]);
        jQuery(end_date_heading).text(exams_index["End Date"]);
        jQuery(due_date_heading).text(exams_index["Due Date"]);
        jQuery(status_heading).text(exams_index["Status"]);

        jQuery(table_head).append(target_heading).append(rotation_heading).append(program_heading).append(start_date_heading).append(end_date_heading).append(due_date_heading).append(status_heading);
        jQuery(table).addClass("table table-striped table-bordered").append(table_head);

        var row;
        if (exams.length > 0) {
            jQuery.each(exams, function (index, exam)  {
                row = buildTable(exam, lastTab);
                jQuery(table).append(row);
            });
        } else {
            var empty_row = document.createElement("tr");
            var empty_cell = document.createElement("td");
            $(empty_cell).attr("colspan", 7);
            $(empty_cell).text("No Exams found.");
            $(empty_cell).addClass("exam-search-message");
            $(empty_row).append(empty_cell);
            jQuery(table).append(empty_row);
        }
        jQuery(lastTab).append(table);
        alert($(".pending-row").length);
    }

    function buildTable (exam, lastTab) {
        var row = document.createElement("tr");
        var target_cell = document.createElement("td");
        var rotation_cell = document.createElement("td");
        var program_cell = document.createElement("td");
        var start_date_cell = document.createElement("td");
        var end_date_cell = document.createElement("td");
        var due_date_cell = document.createElement("td");
        var status_cell = document.createElement("td");

        var target_a = document.createElement("a");
        var rotation_a = document.createElement("a");
        var program_a = document.createElement("a");
        var start_date_a = document.createElement("a");
        var end_date_a = document.createElement("a");
        var due_date_a = document.createElement("a");
        var status_a = document.createElement("a");

        jQuery(target_a).text(exam.targets[0].name).attr({href: exam.link}).html();
        jQuery(rotation_a).text(exam.title).attr({href: exam.link}).html();
        jQuery(program_a).text(exam.program.name).attr({href: exam.link}).html();
        var start_date = new Date(exam.startDate * 1000);
        jQuery(start_date_a).text(start_date.toLocaleDateString()).attr({href: exam.link}).html();
        var end_date = new Date(exam.endDate * 1000);
        jQuery(end_date_a).text(end_date.toLocaleDateString()).attr({href: exam.link}).html();
        var due_date = new Date(exam.gracePeriodEnd * 1000);
        jQuery(due_date_a).text(due_date.toLocaleDateString()).attr({href: exam.link}).html();
        jQuery(status_a).text(exam.status).attr({href: exam.link}).html();

        jQuery(target_cell).append(target_a);
        jQuery(rotation_cell).append(rotation_a);
        jQuery(program_cell).append(program_a);
        jQuery(start_date_cell).append(start_date_a);
        jQuery(end_date_cell).append(end_date_a);
        jQuery(due_date_cell).append(due_date_a);
        jQuery(status_cell).append(status_a);

        jQuery(row).append(target_cell).append(rotation_cell).append(program_cell).append(start_date_cell).append(end_date_cell).append(due_date_cell).append(status_cell).addClass((lastTab === "#completed_exams" ? "complete-row" : "pending-row"));

        return row;
    }

    function listExamsByUserTable(exams, lastTab) {
        var table = document.createElement("table");
        $(table).attr("id", "exam-list");
        var table_head = document.createElement("thead");
        var assessor_heading = document.createElement("th");
        var rotation_heading = document.createElement("th");
        var program_heading = document.createElement("th");
        var start_date_heading = document.createElement("th");
        var end_date_heading = document.createElement("th");

        jQuery(assessor_heading).text(exams_index["Assessor"]);
        jQuery(rotation_heading).text(exams_index["Rotation"]);
        jQuery(program_heading).text(exams_index["Program"]);
        jQuery(start_date_heading).text(exams_index["Start_Date"]);
        jQuery(end_date_heading).text(exams_index["End_Date"]);

        jQuery(table_head).append(assessor_heading).append(rotation_heading).append(program_heading).append(start_date_heading).append(end_date_heading);
        jQuery(table).addClass("table table-striped").append(table_head);

        var row;
        if (typeof(exams) != 'undefined' && exams.length > 0) {
            jQuery.each(exams, function (index, exam)  {
                row = buildUserTable(exam);
                jQuery(table).append(row);
            });
        } else {
            var empty_row = document.createElement("tr");
            var empty_cell = document.createElement("td");
            $(empty_cell).attr("colspan", 7);
            $(empty_cell).text("No Exams found.");
            $(empty_cell).addClass("exam-search-message");
            $(empty_row).append(empty_cell);
            jQuery(table).append(empty_row);
        }
        jQuery(lastTab).append(table);
    }

    function buildUserTable (exam) {
        var row = document.createElement("tr");
        var assessor_cell = document.createElement("td");
        var rotation_cell = document.createElement("td");
        var program_cell = document.createElement("td");
        var start_date_cell = document.createElement("td");
        var end_date_cell = document.createElement("td");

        var assessor_a = document.createElement("a");
        var rotation_a = document.createElement("a");
        var program_a = document.createElement("a");
        var start_date_a = document.createElement("a");
        var end_date_a = document.createElement("a");

        jQuery(assessor_a).text(exam.assessor).attr({href: exam.link}).html();
        jQuery(rotation_a).text(exam.title).attr({href: exam.link}).html();
        jQuery(program_a).text(exam.program.name).attr({href: exam.link}).html();
        var start_date = new Date(exam.startDate * 1000);
        jQuery(start_date_a).text(start_date.toLocaleDateString()).attr({href: exam.link}).html();
        var end_date = new Date(exam.endDate * 1000);
        jQuery(end_date_a).text(end_date.toLocaleDateString()).attr({href: exam.link}).html();

        jQuery(assessor_cell).append(assessor_a);
        jQuery(rotation_cell).append(rotation_a);
        jQuery(program_cell).append(program_a);
        jQuery(start_date_cell).append(start_date_a);
        jQuery(end_date_cell).append(end_date_a);

        jQuery(row).append(assessor_cell).append(rotation_cell).append(program_cell).append(start_date_cell).append(end_date_cell);

        return row;
    }

    function listExamsByUserGrid(exams, lastTab) {
        var group_div = document.createElement("div");
        jQuery(group_div).addClass("row space-below medium exam-grid-question");

        var media_div;
        if (exams.length > 0) {
            jQuery.each(exams, function (index, exam) {
                media_div = buildUserGrid(exam);
                jQuery(group_div).append(media_div);
            });
        } else {
            var empty_div = document.createElement("div");
            jQuery(empty_div).addClass("media course-members-media-list");
            var empty_p = document.createElement("p");
            $(empty_p).html("No Exams found.");
            $(empty_p).addClass("exam-search-message");
            $(empty_div).append(empty_p);
            jQuery(group_div).append(empty_div);
        }

        jQuery(lastTab).append(group_div);
    }

    function buildUserGrid (exam) {
        var media_div = document.createElement("div");
        var media_body = document.createElement("div");
        var media_heading = document.createElement("h4");
        var media_heading_a = document.createElement("a");
        var media_heading_small = document.createElement("small");
        var media_p = document.createElement("p");
        var media_p2 = document.createElement("p");
        var image_holder = document.createElement("div");
        jQuery(image_holder).addClass("img-holder pull-left");
        var image = document.createElement("img");
        $(image).addClass("media-object grid-view-thumb img-rounded");
        $(image).attr("src", exam.img_src);
        $(image).attr("alt", "Target Image");
        var image_anchor = document.createElement("a");
        $(image_anchor).attr("href", exam.link);
        $(image_anchor).append(image);
        $(image_holder).append(image_anchor);

        jQuery(media_div).addClass("media course-members-media-list");
        jQuery(media_body).addClass("media-body");
        jQuery(media_heading).addClass("media-heading");
        jQuery(media_heading_a).addClass("print-black").text(exam.assessor).attr({href: exam.link}).html();
        jQuery(media_heading_small).addClass("pull-right print-black").text(exam.title).html();
        var start_date = new Date(exam.startDate * 1000);
        var end_date = new Date(exam.endDate * 1000);
        jQuery(media_p).text("Rotation Dates: " + start_date.toLocaleDateString() + " to " + end_date.toLocaleDateString()).html();
        jQuery(media_p2).text("Program: " + exam.program.name).html();

        jQuery(media_heading).append(media_heading_a).append(media_heading_small);
        jQuery(media_body).append(media_heading).append(media_p).append(media_p2);
        jQuery(media_div).append(image_holder).append(media_body);

        return media_div;
    }

    $("#exam-search-exam").on("submit", function (e) {
        e.preventDefault();
    });

    var timer;
    var done_interval = 600;

    $("#exam-search").keyup(function () {
        $("#exam-container").empty();
        $(".exam-loading").removeClass("hide");
        clearTimeout(timer);
        timer = setTimeout(function () {
            $("#exam-list").remove();
            $(".exam-grid-question").remove();
            $(".exams-loading").removeClass("hide");
            var current_tab = jQuery("#exams > div.tab-pane.active");
            status = fetchStatusByTab("#" + $(current_tab).attr("id"));
            if ($(current_tab).attr("id") == "exam_results") {
                fetchExamsOnUser(exam_index_view_preference, proxy_id, "#" + $(current_tab).attr("id"));
            } else {
                fetchExams(exam_index_view_preference, status, "#" + $(current_tab).attr("id"));
            }
        }, done_interval);
    });
});