jQuery(document).ready(function($) {
    $(".lcr_advance").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);
        if (!$(clicked).hasClass("disabled")) {
            advance_learner_comment(clicked);
        }
    });

    $(".lcr_backwards").on("click", function(e) {
        e.preventDefault();
        var clicked = $(this);
        if (!$(clicked).hasClass("disabled")) {
            backwards_learner_comment(clicked);
        }
    });

    function advance_learner_comment(clicked) {
        var element_id = $(clicked).data("element-id");
        var current_active = $(".lc_review_comments.active[data-element-id=\"" + element_id + "\"]");
        var next_comment = $(current_active).next(".lc_review_comments");
        if (next_comment.length != 0) {
            var response_count = $(next_comment).data("response-count");
            $(".response_number[data-element-id=\"" + element_id + "\"]").text(response_count);
            $(next_comment).addClass("active");
            $(current_active).removeClass("active");
            $(".lcr_backwards[data-element-id=\"" + element_id + "\"]").removeClass("disabled");
            var next_next_comment = $(next_comment).next(".lc_review_comments");
            if (next_next_comment.length == 0) {
                $(".lcr_advance[data-element-id=\"" + element_id + "\"]").addClass("disabled");
            }
        }
    }

    function backwards_learner_comment(clicked) {
        var element_id = $(clicked).data("element-id");
        var current_active = $(".lc_review_comments.active[data-element-id=\"" + element_id + "\"]");
        var prev_comment = $(current_active).prev(".lc_review_comments");
        if (prev_comment.length != 0) {
            var response_count = $(prev_comment).data("response-count");
            $(".response_number[data-element-id=\"" + element_id + "\"]").text(response_count);
            $(prev_comment).addClass("active");
            $(current_active).removeClass("active");
            $(".lcr_advance[data-element-id=\"" + element_id + "\"]").removeClass("disabled");
            var prev_prev_comment = $(prev_comment).prev(".lc_review_comments");
            if (prev_prev_comment.length == 0) {
                $(".lcr_backwards[data-element-id=\"" + element_id + "\"]").addClass("disabled");
            }
        }
    }
});