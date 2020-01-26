jQuery(document).ready(function($) {
    $("#edit-post").on("click", function(e) {
        e.preventDefault();
        $("#preview-exam-modal").modal("show");
    });

    $(".start-exam").on("click", function(e) {
        e.preventDefault();
        var href = $(this).data("href");
        window.location = href;
    });

    $("#preview-exams-modal-button").on("click", function(e) {
        /*
        We can override these variables if we wish
         */
        var backtrack       = 1;
        var secure          = 0;
        var score_review    = 1;
        var feedback_review = 1;
        var max_attempts    = 100;

        var method = "post-preview";

        var data_object = {
            "method": method,
            "backtrack": backtrack,
            "secure": secure,
            "score_review": score_review,
            "feedback_review": feedback_review,
            "max_attempts": max_attempts,
            "exam_id": exam_id
        };
        $.ajax({
            url: API_URL,
            data: data_object,
            type: "POST",
            success: function (data) {
            }
        });
    });
});