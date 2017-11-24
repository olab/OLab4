var item_offset = 0;
var item_limit = 50;
var total_courses = 0;
var show_loading_message = true;
var timeout;
var reload_items = false;
var sort_object;

jQuery(document).ready(function ($) {

    get_courses(false);

    $("#course-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            total_courses = 0;
            item_offset = 0;
            show_loading_message = true;

            if (e.keyCode == 13) {
                e.preventDefault();
            }

            $("#courses-loading").addClass("hide");
            $("#courses-table").addClass("hide");
            $("#course-table-container table tbody, #item-detail-container").empty();
            $("#item-detail-container").addClass("hide");

            clearTimeout(timeout);
            timeout = window.setTimeout(get_courses, 700, false);
        }
    });

    $(".course-sort").on("click", function (e) {
        var order = $(this).attr("data-order");

        $.each($(".course-sort"), function(i, item){
            $(item).attr("order", "");
            $(this).removeClass("fa-sort-asc").removeClass("fa-sort-desc").addClass("fa-sort");
        });

        if (!order) {
            $(this).attr("data-order", "asc");
            $(this).removeClass("fa-sort").removeClass("fa-sort-desc").addClass("fa-sort-asc");
        } else if (order == "asc") {
            $(this).attr("data-order", "desc");
            $(this).removeClass("fa-sort").removeClass("fa-sort-asc").addClass("fa-sort-desc");
        } else {
            $(this).attr("data-order", "");
            $(this).removeClass("fa-sort-asc").removeClass("fa-sort-desc").addClass("fa-sort");
        }

        sort_object = $(this);

        get_courses();
        
    });

    $("#load-more-courses").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_courses(false);
        }
    });

    $('#delete-courses-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#courses-selected").addClass("hide");
        $("#no-courses-selected").addClass("hide");

        var container = "course-table-container";

        var courses_to_delete = $("#" + container + " input[name='courses[]']:checked").map(function () {
            return this.value;
        }).get();

        if (courses_to_delete.length > 0) {
            $("#delete-courses-container").html("");
            $("#courses-selected").removeClass("hide");
            $("#delete-courses-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='courses[]']:checked").each(function(index, element) {
                var list_course = document.createElement("li");
                var course_id = $(element).val();

                var code = $("#course-row-" + course_id).find(".course-code a").html();
                var name = $("#course-row-" + course_id).find(".course-name a").html();
                var course_text = code + ' : ' + name;
                $(list_course).append(course_text);
                $(list).append(list_course);
            });
            $("#delete-courses-container").append(list);
        } else {
            $("#no-courses-selected").removeClass("hide");
            $("#delete-courses-modal-delete").addClass("hide");
        }
    });
    
    $("#delete-courses-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-courses-modal-item").attr("action");

        var container = "course-table-container";

        var courses_to_delete = $("#" + container + " input[name='courses[]']:checked").map(function () {
            return this.value;
        }).get();

        var course_data = {   "method" : "delete-courses",
            "delete_ids" : courses_to_delete};

        $("#courses-selected").removeClass("hide");
        $("#delete-courses-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, course_data, function(data) {
                if (data.status == "success") {
                    $(data.course_ids).each(function(index, course_id) {
                        $("input[name='courses[]'][value='" + course_id + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs");
                        if ($("#" + container + " input[name='courses[]']").length == 0) {
                            jQuery("#load-more-courses").addClass("hide");
                            jQuery("#courses-no-results").removeClass("hide");
                        }
                    });
                    jQuery("#load-more-courses").html("Showing " + (parseInt(jQuery("#load-more-courses").html().split(" ")[1]) - data.course_ids.length) + " of " + (parseInt(jQuery("#load-more-courses").html().split(" ")[3]) - data.course_ids.length) + " total items");
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-courses-modal').modal('hide');
        });
    });

});

function get_courses (item_index) {
    var search_term = jQuery("#course-search").val();
    var item_ids = [];
    var sort_column = "code";
    var sort_order = "asc";

    if (jQuery("#search-targets-form").length > 0) {
        item_offset = 0;
        total_courses = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-form").serialize();

    }

    jQuery("input.add-item:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), item_ids) == -1) {
            item_ids.push(jQuery(element).val());
        }
    });
    
    if (sort_object) {
        item_offset = 0;
        total_courses = 0;
        show_loading_message = true;
        sort_column = sort_object.attr("data-name");
        sort_order = sort_object.attr("data-order");
    }

    var items = jQuery.ajax({
        url: "?section=api-courses",
        data: "method=get-courses&search_term=" + search_term + "&offset=" + item_offset + "&limit=" + item_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {

            jQuery("#courses-no-results").addClass("hide");

            if (show_loading_message) {
                jQuery("#load-more-courses").addClass("hide");
                jQuery("#courses-loading").removeClass("hide");
                jQuery("#courses-table").addClass("hide");
                jQuery("#item-detail-container").addClass("hide");
                jQuery("#courses-table tbody").empty();
                jQuery("#item-detail-container").empty();
            } else {
                jQuery("#load-more-courses").addClass("loading");
            }
        }
    });

    jQuery.when(items).done(function (data) {

        jQuery("#courses-no-results").addClass("hide");

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            if (!reload_items) {
                total_courses += parseInt(jsonResponse.results);
            } else {
                total_courses = parseInt(jsonResponse.results);
                var checked_items = jQuery("#courses-table input.add-item:checkbox:checked").length;
                if (checked_items > 0) {
                    checked_items = checked_items;
                    total_courses += checked_items;
                }
            }

            jQuery("#load-more-courses").html("Showing " + total_courses + " of " + jsonResponse.data.total_courses + " total courses");

            if (jsonResponse.results < item_limit) {
                jQuery("#load-more-courses").attr("disabled", "disabled");
            } else {
                jQuery("#load-more-courses").removeAttr("disabled");
            }

            item_offset = (item_limit + item_offset);

            if (reload_items && item_index) {
                jQuery("#courses-table").find("tbody tr[id!=item-row-"+item_index+"]").remove();
                jQuery("div#item-detail-container").find("div.item-container[data-item-id!='"+item_index+"']").remove();

                reload_items = false;
            }

            if (show_loading_message) {
                jQuery("#courses-loading").addClass("hide");
                jQuery("#load-more-courses").removeClass("hide");
                jQuery("#courses-table").removeClass("hide");
            } else {
                jQuery("#load-more-courses").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.courses, function (key, item) {
                build_course_row(item);
                // build_item_details(item);
            });

            show_loading_message = false;
        } else {
            jQuery("#courses-loading").addClass("hide");
            jQuery("#courses-no-results").removeClass("hide");
        }
    });
}

function build_course_row (course) {
    var course_curriculum_type_anchor        = document.createElement("a");
    var course_code_anchor        = document.createElement("a");
    var course_name_anchor   = document.createElement("a");

    if(course.course_permission){
        jQuery(course_curriculum_type_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=edit&id=" + course.course_id});
        jQuery(course_code_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=edit&id=" + course.course_id});
        jQuery(course_name_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=edit&id=" + course.course_id});
    } else {
        jQuery(course_curriculum_type_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=content&id=" + course.course_id});
        jQuery(course_code_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=content&id=" + course.course_id});
        jQuery(course_name_anchor).attr({href: ENTRADA_URL + "/admin/courses?section=content&id=" + course.course_id});
    }

    var btn_li = "";

    if (course.course_permission) {
        btn_li += "<li><a href=\"" + ENTRADA_URL + "/admin/courses?section=edit&amp;id=" + course.course_id + "\">Setup</a></li>";
    }
    if (course.course_content_permission) {
        btn_li += "<li><a href=\""+ ENTRADA_URL +"/admin/courses?section=content&amp;id=" + course.course_id + "\">Content</a></li>";
    }
    if (course.course_permission) {
        btn_li += "<li><a href=\"" + ENTRADA_URL + "/admin/courses/enrolment?id=" + course.course_id + "\">Enrolment</a></li>";
        btn_li += "<li><a href=\"" + ENTRADA_URL + "/admin/courses/groups?id=" + course.course_id + "\">Groups</a></li>";
    }
    if (course.course_gradebook) {
        btn_li += "<li><a href=\""+ ENTRADA_URL +"/admin/gradebook?section=view&amp;id=" + course.course_id + "\">Gradebook</a></li>";
    }
    if (course.course_content_permission) {
        btn_li += "<li><a href=\""+ ENTRADA_URL +"/admin/courses/reports?id=" + course.course_id + "\">Reports</a></li>";
    }


    var btn = "<div class=\"btn-group\">" +
        "<button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">" +
        "<i class=\"fa fa-cog\" aria-hidden=\"true\"></i>" +
        "</button>" +
        "<ul class=\"dropdown-menu toggle-left\">" +
        btn_li +
        "</ul>" +
        "</div>";

    var course_row            = document.createElement("tr");
    var course_delete_td      = document.createElement("td");
    var course_curriculum_type_td        = document.createElement("td");
    var course_code_td        = document.createElement("td");
    var course_name_td   = document.createElement("td");
    var course_but_td   = document.createElement("td");
    var course_delete_input   = document.createElement("input");

    if (course.course_permission) {
        jQuery(course_delete_input).attr({type: "checkbox", "class": "add-course", name: "courses[]", value: course.course_id });
        jQuery(course_delete_td).append(course_delete_input);
    }
    jQuery(course_code_anchor).html(course.course_code);
    jQuery(course_curriculum_type_anchor).html(course.curriculum_type);
    jQuery(course_name_anchor).html(course.course_name);
    jQuery(course_code_td).append(course_code_anchor).addClass("course-code");
    jQuery(course_curriculum_type_td).append(course_curriculum_type_anchor);
    jQuery(course_name_td).append(course_name_anchor).addClass("course-name");
    jQuery(course_but_td).append(btn);

    jQuery(course_row).attr("id", "course-row-"+course.course_id);
    jQuery(course_row).append(course_delete_td).append(course_curriculum_type_td).append(course_code_td).append(course_name_td).append(course_but_td).addClass("course-row");
    jQuery("#courses-table").append(course_row);

}