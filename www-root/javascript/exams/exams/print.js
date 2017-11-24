jQuery(document).ready(function($) {
    // Uncheck certain defaults

    $("a.print_button").on("click", function() {
        window.print();
        return false;
    });

    $("#update_fonts").on("change", function() {
        updateFontSize(this);
    });

    $("input.hide_sections").on("click", function() {
        var clicked = $(this);
        var type = $(clicked).data("type");

        $("." + type).toggle();
    });

    $("#one_per_page").on("click", function () {
        if ($(".print-question").hasClass("one-per-page-break")) {
            $(".print-question").removeClass("one-per-page-break");
        } else {
            $(".print-question").addClass("one-per-page-break");
        }
    });
    
    $("#repeat_question_stem").on("click", function () {
        var checked = $(this).is(":checked");
        var stems = $(".question_stem");

        if (checked === true) {
            $.each(stems, function(key, value) {
                var copy = $(value).clone();
                var parent = $(value).parent(".print-question");
                copy.addClass("copy");
                copy.insertBefore(parent);
            });

            $(".question_stem.copy .correct").hide();
        } else {
            $(".question_stem.copy").each(function (key, value) {
              $(value).remove();
            });
        }
    });

    setTimeout(function() {
        $("#exam_id").click();
        $("#question_folder").click();
        $("#curriculum_tags").click();
    }, 50);

});

function updateFontSize(elem) {
    var font_size = elem.value * 16;
    jQuery(".print-friendly").css("font-size", font_size);
}