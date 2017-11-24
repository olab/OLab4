jQuery(document).ready(function ($) {
    $("[data-toggle=\"tooltip\"]").tooltip();

    $("#exam-settings").on("hide.bs.tooltip", function(e) {
        setTimeout(function() {
            $(".settings_tooltip").show();
        }, 1);
    });
    
    $("#exam-progress-records").on("click", ".incorrect", function() {
        var progress_id = $(this).data("id");
        window.location = ENTRADA_RELATIVE + '/exams?section=incorrect&progress_id=' + progress_id;
    });

    $("#exam-progress-records").on("click", ".category", function() {
        var progress_id = $(this).data("id");
        window.location = ENTRADA_RELATIVE + '/exams/reports?section=category&progress_id=' + progress_id;
    });
});