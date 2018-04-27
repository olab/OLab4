jQuery(function($) {
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: "",
        maxDate: ""
    });

    $(".add-on").on("click", function () {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    $("#select-course-btn").advancedSearch({
        api_url : ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
        resource_url: ENTRADA_URL,
        filters : {
            course : {
                label : "Course",
                data_source : "get-user-courses",
                mode: "radio"
            }
        },
        no_results_text: "No course found matching the search criteria.",
        parent_form: $("#evaluation-form"),
        control_class: "course-selector",
        width: 350
    });

    $("#choose-evaluation-btn").advancedSearch({
        api_url : ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
        resource_url: ENTRADA_URL,
        filters : {
            target : {
                label : "Rotations",
                data_source : "get-user-rotations"
            }
        },
        no_results_text: "No rotations found matching the search criteria.",
        parent_form: $("#evaluation-form"),
        width: 350
    });

    $("#choose-form-btn").advancedSearch({
        api_url : ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
        resource_url: ENTRADA_URL,
        filters: {
            form: {
                label: "Forms",
                data_source: "get-user-forms",
                mode: "radio"
            }
        },
        no_results_text: "No forms found matching the search criteria.",
        parent_form: $("#evaluation-form"),
        control_class: "form-selector",
        width: 300
    });
});