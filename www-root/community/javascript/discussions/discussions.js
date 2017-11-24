
jQuery(document).ready(function ($) {
    var category_obj = getOpenDiscussions();
    var open_category = category_obj.data;

    activateAccordion(open_category);

    jQuery(".ui-accordion-header").click(function() {
        setOpenDiscussions();
    });

    // Creates the js array of opened folders
    function getOpenDiscussions() {
        // discussion_board_category
        var dataString = "community_id=" + community_id + "&page_id=" + page_id + "&method=get-opened";
        var category = "";
        jQuery.ajax({
            type: "GET",
            url: api_url,
            data: dataString,
            dataType: "json",
            async: false,
            success: function(data) {
                category = data;
            }
        });
        return category;
    }

    function setOpenDiscussions() {
        var discussion_open = $("h3.discussion_board_category.ui-state-active").data("category");

        var dataString = "community_id=" + community_id + "&discussion_open=" + discussion_open + "&page_id=" + page_id + "&method=save-opened";
        jQuery.ajax({
            type: "POST",
            url: api_url,
            data: dataString,
            dataType: "json",
            success: function(data) {
            }
        });
    }

    // Functions to open the correct accordion window
    function activateAccordion(target) {
        //gets the position number based of the category name. This allow us to keep the correct accordion open even if others are added.
        var position_array = jQuery("h3[data-category=\"" + target + "\"]").data("position");
        if (!position_array && position_array != 0) {
            position_array= false;
        }

        jQuery("#accordion").accordion({
            active: position_array,
            heightStyle: "content",
            collapsible: true,
            autoHeight: false
        });
    }
});