var ENTRADA_URL;

jQuery(document).ready(function($) {
    function fetchReportData() {
        $("#assessment-tools-feedbacks-loading").removeClass("hide");
        $("#load-feedbacks").addClass("hide");

        var report_offset = parseInt($("#report-offset").val());
        var report_limit = parseInt($("#report-limit").val());

        var report_query = $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-tools-feedbacks-reports",
            data: {
                "method": "get-assessments-tools-feedbacks",
                "limit": report_limit,
                "offset": report_offset
            },
            type: "GET"
        });

        $.when(report_query).done(function (data) {
            var jsonResponse = safeParseJson(data, "");
            if (jsonResponse.status === "success") {
                $.each(jsonResponse.data, function (i, feedback) {
                    $("<tr/>").loadTemplate(
                        "#assessment-tools-feedbacks-report-table-row", {
                            'tools-feedback-date': feedback.date,
                            'tools-feedback-assessor': feedback.firstname + ' ' + feedback.lastname + '<br /><a href="mailto:' + feedback.email + '">' + feedback.email + '</a>',
                            'tools-feedback-tool': '<a target="_blank" href="' + ENTRADA_URL + '/admin/assessments/forms?section=edit-form&form_id=' + feedback.form_id + '">' + feedback.title + '</a>',
                            'tools-feedback-feedback': feedback.comments,
                            'assessment-url': ENTRADA_URL + "/assessments/assessment?dassessment_id=" + feedback.dassessment_id
                        }
                    ).appendTo("#assessment-tools-feedbacks-report-table");
                });

                report_offset += jsonResponse.data.length;
                $("#report-offset").val(report_offset);

                $("#load-feedbacks").html('Showing ' + report_offset + ' of ' + jsonResponse.total + ' total feedback responses');

                if (report_offset >= parseInt(jsonResponse.total)) {
                    $("#load-feedbacks").attr("disabled", "disabled");
                } else {
                    $("#load-feedbacks").removeAttr("disabled");
                }
            }

            $("#assessment-tools-feedbacks-loading").addClass("hide");
            $("#load-feedbacks").removeClass("hide");
        });
    }

    function update_filters() {
        var form_data = $("#assessment-tools-feedbacks-form").serialize();

        var filter_query = $.ajax({
            url: ENTRADA_URL + "/admin/assessments?section=api-tools-feedbacks-reports",
            data: "method=update-filters&" + form_data,
            type: "POST"
        });

        $.when(filter_query).done(function (data) {
            var jsonResponse = safeParseJson(data, "");
            if (jsonResponse.status === "success") {
                $("#assessment-tools-feedbacks-report-table").find("tr:gt(0)").remove();
                $("#report-offset").val(0);
                fetchReportData();
            } else {
                display_error(Array(jsonResponse.data));
            }
        });
    }

    fetchReportData();

    $("#load-feedbacks").on("click", function () {
        if (!$("#load-feedbacks").attr("disabled")) {
            fetchReportData();
        }
    });

    $("#apply-filter-btn").on("click", function () {
        update_filters();
    });

    $("#reset-filter-btn").on("click", function () {
        $("input[name^='tools']").remove();
        $("#courses_list_container").remove();
        $("input[name^='courses']").remove();
        $("#tools_list_container").remove();
        $("#report-offset").val(0);
        $("#report-start-date").val('');
        $("#report-end-date").val('');

        $("#assessment-tools-feedbacks-report-table").find("tr:gt(0)").remove();

        update_filters();
    });

    $(".add-on").on("click", function () {
        $(this).prev("input").focus();
    });
});