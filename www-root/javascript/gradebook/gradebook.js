var timeout;

jQuery(document).ready(function($) {

    var sorts = {};
    read_sort_settings(sorts);

    get_gradebooks(null, null, sorts.sort_column, sorts.sort_order);

    // handle the load more button underneath the table
    $("#load-more-gradebook").click(function() {
        handle_load_more();
    });

    // handle column sort clicks
    $("i.gradebook-sort").click(function() {
        var target = this;
        handle_column_sort(target);
    })

    $("#gradebook-search").keydown(function (e) {
        var keycode = e.keyCode;
        
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            if (e.keyCode == 13) {
                e.preventDefault();
            }
        
            clearTimeout(timeout);
            timeout = window.setTimeout(function () {
                jQuery("#gradebook-table").find("tbody").empty();
                jQuery("#total_loaded").text(0);
                get_gradebooks(null, null, null, null);
            }, 700, false);
        }
    });
});

function get_gradebooks(offset, limit, col, ord) {

    var search_term = jQuery("#gradebook-search").val();
    var item_offset = (!offset ? 0 : offset);
    var item_limit = (!limit ? 25 : limit);
    var show_loading_message = true;
    var sort_order = (!ord ? "" : ord);
    var sort_column = (!col ? "" : col);

    var items = jQuery.ajax({
        url: "?section=api-gradebook",
        data: "method=get-gradebooks" + (search_term ? "&search_term="+search_term : "") + "&offset=" + item_offset + "&limit=" + item_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {
             jQuery("#gradebook-no-results").addClass("hide");
             jQuery("#gradebook-loading").removeClass("hide");
             jQuery("#gradebook-table").hide();
             jQuery("#load-more-gradebook").hide();
        }
    });

    jQuery.when(items).done(function (data) {
        jQuery("#gradebook-loading").addClass("hide");
        var jsonResponse = JSON.parse(data);

        if (jsonResponse.status == "error") {
            jQuery("#gradebook-no-results").removeClass("hide");
        } else if (jsonResponse.data.total_gradebooks > 0) {
            var i = 0;
            jQuery.each(jsonResponse.data.gradebooks, function (key, item) {
                i++;
                build_item_row(item);
            });
            var previous_total_loaded = parseInt(jQuery("span#total_loaded").text());
            var new_total_loaded = previous_total_loaded + i;
            var total_available = parseInt(jsonResponse.data.total_gradebooks);

            if (new_total_loaded < total_available) {
                jQuery("#load-more-gradebook").removeAttr("disabled")
            } else {
                jQuery("#load-more-gradebook").attr("disabled","disabled");
            }
            jQuery("span#total_loaded").text(new_total_loaded);
            jQuery("span#total_available").text(total_available);
            jQuery("#gradebook-table").show();
            jQuery("#load-more-gradebook").show();

        } else {
            jQuery("#gradebook-no-results").removeClass("hide");
        }
    });
}

function build_item_row (item) {
    var course_curriculum_type_anchor        = document.createElement("a");
    var course_code_anchor                   = document.createElement("a");
    var course_name_anchor                   = document.createElement("a");

    jQuery(course_curriculum_type_anchor).attr({href: ENTRADA_URL + "/admin/gradebook?section=view&id=" + item.course_id});
    jQuery(course_code_anchor).attr({href: ENTRADA_URL + "/admin/gradebook?section=view&id=" + item.course_id});
    jQuery(course_name_anchor).attr({href: ENTRADA_URL + "/admin/gradebook?section=view&id=" + item.course_id});

    var course_row            = document.createElement("tr");;
    var course_curriculum_type_td        = document.createElement("td");
    var course_code_td        = document.createElement("td");
    var course_name_td   = document.createElement("td");

    jQuery(course_code_anchor).html(item.course_code);
    jQuery(course_curriculum_type_anchor).html(item.curriculum_type);
    jQuery(course_name_anchor).html(item.course_name);
    jQuery(course_code_td).append(course_code_anchor).addClass("course-code");
    jQuery(course_curriculum_type_td).append(course_curriculum_type_anchor);
    jQuery(course_name_td).append(course_name_anchor).addClass("course-name");

    jQuery(course_row).attr("id", "course-row-"+item.course_id);
    jQuery(course_row).append(course_curriculum_type_td).append(course_code_td).append(course_name_td).addClass("course-row");
    jQuery("#gradebook-table").find("tbody").append(course_row);

}

function handle_load_more() {

    var offset = parseInt(jQuery("span#total_loaded").text());
    var sorts = {};
    read_sort_settings(sorts);

    get_gradebooks(offset,null,sorts.sort_column,sorts.sort_order);
}

function read_sort_settings(sorts) {
    jQuery("#gradebook-table th i").each(function() {
        var sort_order = jQuery(this).attr("data-order");
        if (sort_order) {
            var sort_column = jQuery(this).attr('data-name');
            sorts.sort_order = sort_order;
            sorts.sort_column = sort_column;
            return false;
        }
    });
}

function handle_column_sort(target) {

    var clicked_column = jQuery(target).attr("data-name");
    var clicked_order =  jQuery(target).attr("data-order");

    // unset all column sort settings
    jQuery("#gradebook-table th i").each(function() {
        jQuery(this).removeClass("fa-sort-asc").removeClass("fa-sort-desc").addClass("fa-sort").attr("data-order","");
    })

    if (clicked_order == "asc") {
        var new_order = "desc";
    } else if (clicked_order == "desc") {
        var new_order = "";
    } else {
        var new_order = "asc";
    }

    jQuery("#gradebook-table").find("tbody").empty();
    jQuery("#total_loaded").text(0);

    if (!new_order) {
        get_gradebooks(null,null,null,null);
    } else {
        jQuery("#gradebook-table th i[data-name='"+clicked_column+"']").removeClass("fa-sort").addClass("fa-sort-"+new_order)
            .attr("data-order",new_order);
        get_gradebooks(null,null,clicked_column,new_order);
    }
}