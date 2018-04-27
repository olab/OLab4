jQuery(document).ready(function ($) {

    $(".question_preview").on("click", function(event) {
        event.preventDefault();
        var clicked = $(this);
        showQuestionPreview(clicked);
    });

    $(".group_preview").on("click", function(event) {
        event.preventDefault();
        var clicked = $(this);
        showGroupPreview(clicked);
    });

    function showQuestionPreview(clicked) {
        var version_id      = clicked.data("version-id");
        var type            = clicked.data("type");
        var dataObject      = {};

        switch (type) {
            case "exam-element":
                dataObject = {
                    method : "get-version-preview",
                    version_id: version_id
                };
                break;
            case "adjustment-correct":
                var answer_correct  = clicked.data("adjustment-correct");
                dataObject = {
                    method : "get-version-preview",
                    version_id: version_id,
                    answer_correct: answer_correct
                };
                break;
            case "adjustment-incorrect":
                var answer_incorrect  = clicked.data("adjustment-incorrect");
                dataObject = {
                    method : "get-version-preview",
                    version_id: version_id,
                    answer_incorrect: answer_incorrect
                };
                break;
        }

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    var question_preview_html = jsonResponse.html;

                    var modal_window = $("#preview-question-modal");
                    var modal_body_text = modal_window.find(".modal-sub-body");

                    var browser_window_height = window.innerHeight;
                    var browser_window_width = window.innerWidth;
                    var max_height = Math.floor(browser_window_height * 0.7);
                    var max_width = Math.floor(browser_window_width * 0.7);

                    $(modal_body_text).html(question_preview_html);
                    $(modal_window).modal("show");

                    setTimeout(function() {
                        var exam_element = $(".exam-element").height() + 10;
                        if (exam_element >= max_height) {
                            exam_element = max_height - 20;
                        }

                        $(modal_body_text).height(exam_element);
                        $(modal_window).width(max_width);
                        $(modal_window).css("margin-left", "-" + Math.floor(max_width / 2) + "px");
                    }, 200);

                }
            }
        });
    }
    function showGroupPreview(clicked) {
        var group_id      = clicked.data("group-id");
        var dataObject = {
            method : "get-group-preview",
            group_id: group_id
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    var question_preview_html = jsonResponse.html;

                    var modal_window = $("#preview-question-modal");
                    var modal_body_text = modal_window.find(".modal-sub-body");

                    var browser_window_height = window.innerHeight;
                    var browser_window_width = window.innerWidth;
                    var max_height = Math.floor(browser_window_height * 0.7);
                    var max_width = Math.floor(browser_window_width * 0.7);

                    $(modal_body_text).html(question_preview_html);
                    $(modal_window).modal("show");

                    setTimeout(function() {
                        var exam_element = $(".exam-element").height() + 10;
                        if (exam_element >= max_height) {
                            exam_element = max_height - 20;
                        }

                        $(modal_body_text).height(exam_element);
                        $(modal_window).width(max_width);
                        $(modal_window).css("margin-left", "-" + Math.floor(max_width / 2) + "px");
                    }, 200);

                }
            }
        });
    }
});


