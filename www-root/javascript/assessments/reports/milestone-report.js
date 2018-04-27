jQuery(function($) {
    $(".date-error-msgs").empty();
    jQuery('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    jQuery('.add-on').on('click', function() {
        if (jQuery(this).siblings('input').is(':enabled')) {
            jQuery(this).siblings('input').focus();
        }
    });

    $("#get-assessment-tools").on("click", function(e) {
        e.preventDefault();
        $(".date-error-msgs").empty();

        if (!$("input[name=\"start_date\"]").val() || !$("input[name=\"finish_date\"]").val()) {
            display_error([cbme_translations.improper_date_selection], ".date-error-msgs");
        } else {
            $(".report-instructions").hide();
            $("#select-all-tools").removeClass("disabled");
            var subject_id = $("input[name='proxy_id']").val();
            var finish_date = $("input[name='finish_date']").val();
            var start_date = $("input[name='start_date']").val();
            var course_id = $("input[name='course_id']").val();
            var start_date_obj = new Date(start_date + " 00:00:00");
            var finish_date_obj = new Date(finish_date + " 00:00:00");

            if (isNaN(start_date_obj) || isNaN(finish_date_obj)) {
                display_error([cbme_translations.incorrect_date_format], ".date-error-msgs");
            } else {
                if (start_date_obj.getTime() >= finish_date_obj.getTime()) {
                    display_error([cbme_translations.start_date_before_end], ".date-error-msgs");
                } else {
                    var assessment_tool_request = $.ajax({
                        url: ENTRADA_URL + '/assessments?section=api-assessments&method=get-all-assessment-tools-by-date&subject_id=' + subject_id + "&finish_date=" + finish_date + "&start_date=" + start_date + "&course_id=" + course_id,
                        type: "GET",
                        beforeSend: function () {
                            $("#assessment-tool-loading").removeClass("hide");
                        },
                        complete: function () {
                            $("#assessment-tool-loading").addClass("hide");
                            $(".assessment-tool-section").removeClass("hide");
                        },
                        error: function () {
                            $("#assessment-tool-loading").addClass("hide");
                        }
                    });
                    $.when(assessment_tool_request).done(function (data) {
                        var jsonResponse = safeParseJson(data, "No assessment tools found.");
                        $(".assessment-tool-list").empty();
                        if (jsonResponse.status === "success") {
                            $("#generate-milestone-report").removeClass("hide");
                            $.each(jsonResponse.data, function (index, value) {
                                $("<li/>").loadTemplate(
                                    "#assessment-tool-list-template", {
                                        form_type_title: value.form_type_title,
                                        title: value.title,
                                        form_id: value.form_id,
                                        objective_code: value.objectives[0] === undefined ? "" : value.objectives[0].objective_code,
                                        tool_created_date: value.created_date,
                                        assessment_tool_id: "assessment-tool-" + index
                                    }
                                ).appendTo(".assessment-tool-list").addClass("list-set-item");
                            });
                        } else {
                            $("#generate-milestone-report").addClass("hide");
                            $("<li/>").loadTemplate(
                                "#assessment-tool-list-error-template", {
                                    title: jsonResponse.data,
                                }
                            ).appendTo(".assessment-tool-list");
                            $("#select-all-tools").addClass("disabled");
                        }
                    });
                }
            }
        }
    });

    $("#generate-milestone-report").on("click", function() {
        $(".date-error-msgs").empty();
    });

    $("#generate-milestone-reports-form").submit(function( event ) {
        $(".date-error-msgs").empty();
        display_generic([cbme_translations.loading_csv_data], ".date-error-msgs");
    });

    /**
     * Select all toggle for assessment tools
     */
    $("#select-all-tools").on("click", function() {
        if (!$(this).hasClass("disabled")) {
            $(this).toggleClass("checked-all");
            if ($(this).hasClass("checked-all")) {
                $(".assessment-tool-checkbox").prop("checked", true);
            } else {
                $(".assessment-tool-checkbox").prop("checked", false);
            }
        }
    });
});