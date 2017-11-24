var item_offset = 0;
var item_limit = 50;
var total_groups = 0;
var show_loading_message = true;
var timeout;
var reload_items = false;
var sort_object;

jQuery(document).ready(function ($) {

    get_groups(false);

    $("#cperiod_select").on("change", function(e) {
        total_groups = 0;
        item_offset = 0;
        show_loading_message = true;
        $("#cperiod_assign_select").val($(this).val());
        $("#no-curriculum-msg").hide();
        $("#assign-period-modal-but").addClass("active");
        get_groups();
    });

    $("#print").on("click", function (e) {
        e.preventDefault();
        window.print();
    });

    $("#checkAll").on("click", function(e) {
        $("input[name='groups[]']").prop('checked', $(this).prop("checked"));
    });

    $("#group-table-container").on("click", ".add-group", function(e) {
        if ($(this).prop("checked") && $("#assign-period-modal-but").hasClass("active") && $("#group-row-"+$(this).val()).hasClass("warning"))
        {
            $("#assign-period-modal-but").removeClass("active");
        }
        else if(!$(this).prop("checked") && !$("#assign-period-modal-but").hasClass("active") && $("#group-row-"+$(this).val()).hasClass("warning")) {
            var groups = $("#group-table-container input[name='groups[]']:checked").map(function () {
                if ($("#group-row-"+$(this).val()).hasClass("warning")) {
                    return this.value;
                }
            }).get();

            if (groups.length == 0) {
                $("#assign-period-modal-but").addClass("active");
            }
        }
    });

    $("#assign-period-modal-but").on("click", function (e) {
        if (!$(this).hasClass("active")) {
            $("#assign-period-modal").modal('show');
        }
    });

    $('#assign-period-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#groups-selected").addClass("hide");
        $("#no-groups-selected").addClass("hide");

        var container = "group-table-container";

        var groups_to_assign = $("#" + container + " input[name='groups[]']:checked").map(function () {
            if ($("#group-row-"+$(this).val()).hasClass("warning")) {
                return this.value;
            }
        }).get();

        if (groups_to_assign.length > 0) {
            $("#assign-groups-selected").removeClass("hide");
            $("#delete-groups-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='groups[]']:checked").each(function(index, element) {
                if ($("#group-row-"+$(element).val()).hasClass("warning")) {
                    var list_group = document.createElement("li");
                    var group_id = $(element).val();
                    var group_text = $("#group-row-" + group_id).find(".group-name a").html();
                    $(list_group).append(group_text);
                    $(list).append(list_group);
                }
            });
            $("#assign-groups-container").append(list);
        } else {
            $("#assign-groups-modal-assign").addClass("hide");
        }
    });

    $('#assign-period-modal').on('hide.bs.modal', function (e) {
        $("#assign-groups-container").html("");
    });

    $("#assign-groups-modal-assign").on("click", function(e) {
        e.preventDefault();
        var url = $("#assign-period-modal-item").attr("action");

        var container = "group-table-container";

        var groups_to_assign = $("#" + container + " input[name='groups[]']:checked").map(function () {
            if ($("#group-row-"+$(this).val()).hasClass("warning")) {
                return this.value;
            }
        }).get();

        var group_data = {  "method" : "assign-groups",
                            "assign_ids" : groups_to_assign,
                            "cperiod_id" : $("#cperiod_assign_select").val()
                        };

        $("#assign-roups-selected").removeClass("hide");
        $("#assign-groups-modal-assign").removeClass("hide");

        var jqxhr = $.post(url, group_data, function(data) {
                if (data.status == "success") {
                    total_groups = 0;
                    item_offset = 0;
                    show_loading_message = true;
                    $("#assign-period-modal-but").addClass("active");
                    get_groups();
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#assign-period-modal').modal('hide');
        });
    });
    
    $("#download-csv").on("click", function (e) {
        e.preventDefault();
        var container = "group-table-container";
        var groups = $("#" + container + " input[name='groups[]']:checked").map(function () {
            return this.value;
        }).get();

        var course_id = jQuery("#course-id").val();

        if (groups.length == 0) {
            $("input[name='groups[]']").prop('checked',true);
            $('#form-search').attr("action", ENTRADA_URL+"/admin/courses/groups?section=export&id=" + course_id);
            $('#form-search').submit();
            $("input[name='groups[]']").prop('checked',false);
        } else {
            $('#form-search').attr("action", ENTRADA_URL+"/admin/courses/groups?section=export&id=" + course_id);
            $('#form-search').submit();
        }

    });

    $("#group-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            total_groups = 0;
            item_offset = 0;
            show_loading_message = true;

            if (e.keyCode == 13) {
                e.preventDefault();
            }

            $("#load-items").addClass("hide");
            $("#groups-table").addClass("hide");
            $("#group-table-container table tbody, #item-detail-container").empty();
            $("#item-detail-container").addClass("hide");

            clearTimeout(timeout);
            timeout = window.setTimeout(get_groups, 700, false);
        }
    });

    $(".group-sort").on("click", function (e) {
        var order = $(this).attr("data-order");

        $.each($(".group-sort"), function(i, item){
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

        get_groups();

    });



    $("#load-groups").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_groups(false);
        }
    });

    $('#delete-groups-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#groups-selected").addClass("hide");
        $("#no-groups-selected").addClass("hide");

        var container = "group-table-container";

        var groups_to_delete = $("#" + container + " input[name='groups[]']:checked").map(function () {
            return this.value;
        }).get();

        if (groups_to_delete.length > 0) {
            $("#groups-selected").removeClass("hide");
            $("#delete-groups-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='groups[]']:checked").each(function(index, element) {
                var list_group = document.createElement("li");
                var group_id = $(element).val();
                var group_text = $("#group-row-" + group_id).find(".group-name a").html();
                $(list_group).append(group_text);
                $(list).append(list_group);
            });
            $("#delete-groups-container").append(list);
        } else {
            $("#no-groups-selected").removeClass("hide");
            $("#delete-groups-modal-delete").addClass("hide");
        }
    });

    $('#delete-groups-modal').on('hide.bs.modal', function (e) {
        $("#delete-groups-container").html("");
    });

    $("#delete-groups-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-groups-modal-item").attr("action");

        var container = "group-table-container";

        var groups_to_delete = $("#" + container + " input[name='groups[]']:checked").map(function () {
            return this.value;
        }).get();

        var group_data = {   "method" : "delete-groups",
            "delete_ids" : groups_to_delete};

        $("#groups-selected").removeClass("hide");
        $("#delete-groups-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, group_data, function(data) {
                if (data.status == "success") {
                    $(data.group_ids).each(function(index, group_id) {
                        $("input[name='groups[]'][value='" + group_id + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs");
                        if ($("#" + container + " input[name='groups[]']").length == 0) {
                            jQuery("#load-groups").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(module_text.index.no_items_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "groups-no-results"});
                            jQuery("#groups-msgs").append(no_results_div);
                        }
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-groups-modal').modal('hide');
        });
    });

    
});

function get_groups (item_index) {
    var search_term = jQuery("#group-search").val();
    var course_id = jQuery("#course-id").val();
    var cperiod_id = jQuery("#cperiod_select").val();
    var item_ids = [];
    var sort_column = "";
    var sort_order = "";

    if (jQuery("#search-targets-form").length > 0) {
        item_offset = 0;
        total_groups = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-form").serialize();

    }

    jQuery("input.add-item:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), item_ids) == -1) {
            item_ids.push(jQuery(element).val());
        }
    });

    //set the item type if we are building a rubric/grouped item
    if (sort_object) {
        item_offset = 0;
        total_groups = 0;
        show_loading_message = true;
        sort_column = sort_object.attr("data-name");
        sort_order = sort_object.attr("data-order");
    }

    var items = jQuery.ajax({
        url: "?section=api-groups",
        data: "method=get-groups&course=" + course_id + "&cperiod_id=" + cperiod_id + "&search_term=" + search_term + "&offset=" + item_offset + "&limit=" + item_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {

            if (jQuery("#groups-no-results").length) {
                jQuery("#groups-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#load-groups").addClass("hide");
                jQuery("#groups-items-loading").removeClass("hide");
                jQuery("#groups-table").addClass("hide");
                jQuery("#item-detail-container").addClass("hide");
                jQuery("#groups-table tbody").empty();
                jQuery("#item-detail-container").empty();
            } else {
                jQuery("#load-groups").addClass("loading");
            }
        }
    });

    jQuery.when(items).done(function (data) {

        if (jQuery("#groups-no-results").length) {
            jQuery("#groups-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            if (!reload_items) {
                total_groups += parseInt(jsonResponse.results);
            } else {
                total_groups = parseInt(jsonResponse.results);
                var checked_items = jQuery("#groups-table input.add-item:checkbox:checked").length;
                if (checked_items > 0) {
                    checked_items = checked_items;
                    total_groups += checked_items;
                }
            }
            jQuery("#load-groups").html("Showing " + total_groups + " of " + jsonResponse.data.total_groups + " total groups");

            if (jsonResponse.results < item_limit) {
                jQuery("#load-groups").attr("disabled", "disabled");
            } else {
                jQuery("#load-groups").removeAttr("disabled");
            }

            item_offset = (item_limit + item_offset);

            if (reload_items && item_index) {
                jQuery("#groups-table").find("tbody tr[id!=item-row-"+item_index+"]").remove();
                jQuery("div#item-detail-container").find("div.item-container[data-item-id!='"+item_index+"']").remove();

                reload_items = false;
            }

            if (show_loading_message) {
                jQuery("#groups-items-loading").addClass("hide");
                jQuery("#load-groups").removeClass("hide");
                jQuery("#groups-table").removeClass("hide");
            } else {
                jQuery("#load-groups").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.groups, function (key, item) {
                build_group_row(item);
            });

            show_loading_message = false;
        } else {
            jQuery("#groups-items-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No Groups Found.");
            jQuery(no_results_div).append(no_results_p).attr({id: "groups-no-results"});
            jQuery("#groups-msgs").append(no_results_div);
        }
    });
}

function build_group_row (group) {
    var group_name_anchor   = document.createElement("a");

    jQuery(group_name_anchor).attr({href: ENTRADA_URL + "/admin/courses/groups?section=manage&id=" + group.course_id + "&ids=" + group.group_id});

    var group_row            = document.createElement("tr");
    var group_delete_td      = document.createElement("td");
    var group_name_td   = document.createElement("td");
    var group_members_td   = document.createElement("td");
    var group_but_td   = document.createElement("td");
    var group_delete_input   = document.createElement("input");

    
    jQuery(group_delete_input).attr({type: "checkbox", "class": "add-group", name: "groups[]", value: group.group_id});
    jQuery(group_delete_td).append(group_delete_input);
    jQuery(group_name_anchor).html(group.group_name);
    jQuery(group_name_td).append(group_name_anchor).addClass("group-name");
    jQuery(group_members_td).html(group.members);

    jQuery(group_row).attr("id", "group-row-"+group.group_id);
    
    if (!group.active) {
        jQuery(group_row)

    }
    
    if (!group.cperiod_id) {
        jQuery(group_row).addClass("warning");
        jQuery("#no-curriculum-msg").show();
    }

    jQuery(group_row).append(group_delete_td).append(group_name_td).append(group_members_td).addClass("group-row");
    jQuery("#groups-table").append(group_row);

}