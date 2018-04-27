jQuery(document).ready(function ($) {
    /**
     * Date picker instantiation
     */
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    /**
     * Date picker toggle for calendar icons
     */
    $(".add-on").on("click", function() {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    /**
     * Event listener for filter toggles
     */
    $(".collapsed-filter").each(function() {
        $(this).find(".collapsed-filter-toggle").on("click", function() {
            $(this).toggleClass("open");
            $(this).closest(".collapsed-filter").find(".filter-options").slideToggle(250);
        });
    });

    /**
     * Show hide filters
     */
    $("#list-filter-toggle").on("click", function() {
        $("#filter-options").slideToggle(250);
        $(this).toggleClass("open");
    });

});
