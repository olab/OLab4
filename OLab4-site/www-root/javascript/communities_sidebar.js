jQuery(document).ready(function() {
    /* It makes the jQuery :contains selector Case-Insensitive */
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase()
                .indexOf(m[3].toUpperCase()) >= 0;
    };
    /*
     * Toggles the "Show more" section to display all communities
     */
    jQuery("#my-communities").on("click", "#btn-show-more-collapsible", function() {
        if (jQuery(this).hasClass("show-more")) {
            jQuery("#my-communities .community.hidden").addClass("active");
            jQuery(this).text("Show less").removeClass("show-more").addClass("show-less");
        } else {
            jQuery("#my-communities .community.hidden").removeClass("active");
            jQuery(this).text("Show more").removeClass("show-less").addClass("show-more");
        }
    });

    /* Search within Communities Sidebar */

    jQuery("#my-communities #communities_search_value").on("keyup", function() {
        var query = jQuery(this).val();
        if (query != "") {
            jQuery("#my-communities .community").removeClass("found").addClass("not-found");
            jQuery("#my-communities .community:contains('" + query + "')").each(function(){
                jQuery(this).addClass("found").removeClass("not-found");
            });
        } else {
            jQuery("#my-communities .community").removeClass("found").removeClass("not-found");
        }
        return false;
    });
});