jQuery(document).ready(function ($) {
    $("#show_controls").on("click", function() {
        var search_form = $("#search_form");
        if ($(search_form).hasClass("hide")) {
            $(search_form).removeClass("hide").addClass("show");
            //$(this).text("Hide Controls");
            $(this).addClass("hide");

            var error_box = $("#display-error-box");
            if (error_box) {
                $(error_box).addClass("hide");
            }
        } else {
            $(search_form).removeClass("show").addClass("hide");
            $(this).text("Show Controls");
        }
    });
});