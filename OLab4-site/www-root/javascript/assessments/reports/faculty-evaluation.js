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

    $("#choose-evaluation-btn").advancedSearch({
        api_url : ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
        resource_url: ENTRADA_URL,
        filters : {
            target : {
                label : "Faculty",
                data_source : "get-user-faculty"
            }
        },
        no_results_text: "No faculty found matching the search criteria.",
        parent_form: $("#evaluation-form"),
        control_class: "form-selector",
        width: 350,
        select_all_enabled : true
    });

    $("#choose-form-btn").advancedSearch({
        api_url : ENTRADA_URL + "/admin/assessments?section=api-evaluation-reports",
        resource_url: ENTRADA_URL,
        filters: {
            form: {
                label: "Forms",
                data_source: "get-user-forms-for-reporting",
                mode: "radio"
            }
        },
        no_results_text: "No forms found matching the search criteria.",
        parent_form: $("#evaluation-form"),
        control_class: "form-selector",
        width: 300
    });
});