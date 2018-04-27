jQuery(document).ready(function ($) {

    $(".accordion-heading").on("click", function (e) {
        e.preventDefault();
        $(this).find("i.accordion-chevron").toggleClass("active");
    });

});