var item_offset = 0;
var item_limit = 50;
var total_units = 0;
var show_loading_message = true;
var timeout;
var reload_items = false;
var sort_object;

jQuery(document).ready(function ($) {

    get_units(false);

    $("#cperiod_select").on("change", function(e) {
        total_units = 0;
        item_offset = 0;
        show_loading_message = true;
        $("#cperiod_assign_select").val($(this).val());
        $("#no-curriculum-msg").hide();
        $("#assign-period-modal-but").addClass("active");
        get_units();
    });

    $("#checkAll").on("click", function(e) {
        $("input[name='units[]']").prop('checked', $(this).prop("checked"));
    });

    $("#unit-table-container").on("click", ".add-unit", function(e) {
        if ($(this).prop("checked") && $("#assign-period-modal-but").hasClass("active") && $("#unit-row-"+$(this).val()).hasClass("warning"))
        {
            $("#assign-period-modal-but").removeClass("active");
        }
        else if(!$(this).prop("checked") && !$("#assign-period-modal-but").hasClass("active") && $("#unit-row-"+$(this).val()).hasClass("warning")) {
            var units = $("#unit-table-container input[name='units[]']:checked").map(function () {
                if ($("#unit-row-"+$(this).val()).hasClass("warning")) {
                    return this.value;
                }
            }).get();

            if (units.length == 0) {
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
        $("#units-selected").addClass("hide");
        $("#no-units-selected").addClass("hide");

        var container = "unit-table-container";

        var units_to_assign = $("#" + container + " input[name='units[]']:checked").map(function () {
            if ($("#unit-row-"+$(this).val()).hasClass("warning")) {
                return this.value;
            }
        }).get();

        if (units_to_assign.length > 0) {
            $("#assign-units-selected").removeClass("hide");
            $("#delete-units-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='units[]']:checked").each(function(index, element) {
                if ($("#unit-row-"+$(element).val()).hasClass("warning")) {
                    var list_unit = document.createElement("li");
                    var cunit_id = $(element).val();
                    var unit_text = $("#unit-row-" + cunit_id).find(".unit-name a").html();
                    $(list_unit).append(unit_text);
                    $(list).append(list_unit);
                }
            });
            $("#assign-units-container").append(list);
        } else {
            $("#assign-units-modal-assign").addClass("hide");
        }
    });

    $('#assign-period-modal').on('hide.bs.modal', function (e) {
        $("#assign-units-container").html("");
    });

    $("#assign-units-modal-assign").on("click", function(e) {
        e.preventDefault();
        var url = $("#assign-period-modal-item").attr("action");

        var container = "unit-table-container";

        var units_to_assign = $("#" + container + " input[name='units[]']:checked").map(function () {
            if ($("#unit-row-"+$(this).val()).hasClass("warning")) {
                return this.value;
            }
        }).get();

        var unit_data = {  "method" : "assign-units",
                            "assign_ids" : units_to_assign,
                            "cperiod_id" : $("#cperiod_assign_select").val()
                        };

        $("#assign-roups-selected").removeClass("hide");
        $("#assign-units-modal-assign").removeClass("hide");

        var jqxhr = $.post(url, unit_data, function(data) {
                if (data.status == "success") {
                    total_units = 0;
                    item_offset = 0;
                    show_loading_message = true;
                    $("#assign-period-modal-but").addClass("active");
                    get_units();
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#assign-period-modal').modal('hide');
        });
    });

    $(".unit-sort").on("click", function (e) {
        var order = $(this).attr("data-order");

        $.each($(".unit-sort"), function(i, item){
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

        get_units();

    });

    $("#load-units").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_units(false);
        }
    });

    $('#delete-units-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#units-selected").addClass("hide");
        $("#no-units-selected").addClass("hide");

        var container = "unit-table-container";

        var units_to_delete = $("#" + container + " input[name='units[]']:checked").map(function () {
            return this.value;
        }).get();

        if (units_to_delete.length > 0) {
            $("#units-selected").removeClass("hide");
            $("#delete-units-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='units[]']:checked").each(function(index, element) {
                var list_unit = document.createElement("li");
                var cunit_id = $(element).val();
                var unit_text = $("#unit-row-" + cunit_id).find(".unit-title a").html();
                $(list_unit).append(unit_text);
                $(list).append(list_unit);
            });
            $("#delete-units-container").append(list);
        } else {
            $("#no-units-selected").removeClass("hide");
            $("#delete-units-modal-delete").addClass("hide");
        }
    });

    $('#delete-units-modal').on('hide.bs.modal', function (e) {
        $("#delete-units-container").html("");
    });

    $("#delete-units-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-units-modal-item").attr("action");

        var container = "unit-table-container";

        var units_to_delete = $("#" + container + " input[name='units[]']:checked").map(function () {
            return this.value;
        }).get();

        var unit_data = { "method" : "delete-units", "delete_ids" : units_to_delete};

        $("#units-selected").removeClass("hide");
        $("#delete-units-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, unit_data, function(data) {
                if (data.status == "success") {
                    $(data.unit_ids).each(function(index, cunit_id) {
                        $("input[name='units[]'][value='" + cunit_id + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs");
                        if ($("#" + container + " input[name='units[]']").length == 0) {
                            jQuery("#load-units").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(module_text.index.no_items_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "units-no-results"});
                            jQuery("#units-msgs").append(no_results_div);
                        }
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-units-modal').modal('hide');
        });
    });
});

function get_units (item_index) {
    var course_id = jQuery("#course-id").val();
    var cperiod_id = jQuery("#cperiod_select").val();
    var item_ids = [];
    var sort_column = "";
    var sort_order = "";

    jQuery("input.add-item:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), item_ids) == -1) {
            item_ids.push(jQuery(element).val());
        }
    });

    //set the item type if we are building a rubric/grouped item
    if (sort_object) {
        item_offset = 0;
        total_units = 0;
        show_loading_message = true;
        sort_column = sort_object.attr("data-name");
        sort_order = sort_object.attr("data-order");
    }

    var items = jQuery.ajax({
        url: "?section=api-units",
        data: "method=get-units&course=" + course_id + "&cperiod_id=" + cperiod_id + "&offset=" + item_offset + "&limit=" + item_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {

            if (jQuery("#units-no-results").length) {
                jQuery("#units-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#load-units").addClass("hide");
                jQuery("#units-items-loading").removeClass("hide");
                jQuery("#units-table").addClass("hide");
                jQuery("#item-detail-container").addClass("hide");
                jQuery("#units-table tbody").empty();
                jQuery("#item-detail-container").empty();
            } else {
                jQuery("#load-units").addClass("loading");
            }
        }
    });

    jQuery.when(items).done(function (data) {

        if (jQuery("#units-no-results").length) {
            jQuery("#units-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            if (!reload_items) {
                total_units += parseInt(jsonResponse.results);
            } else {
                total_units = parseInt(jsonResponse.results);
                var checked_items = jQuery("#units-table input.add-item:checkbox:checked").length;
                if (checked_items > 0) {
                    checked_items = checked_items;
                    total_units += checked_items;
                }
            }
            jQuery("#load-units").html("Showing " + total_units + " of " + jsonResponse.data.total_units + " total units");

            if (jsonResponse.results < item_limit) {
                jQuery("#load-units").attr("disabled", "disabled");
            } else {
                jQuery("#load-units").removeAttr("disabled");
            }

            item_offset = (item_limit + item_offset);

            if (reload_items && item_index) {
                jQuery("#units-table").find("tbody tr[id!=item-row-"+item_index+"]").remove();
                jQuery("div#item-detail-container").find("div.item-container[data-item-id!='"+item_index+"']").remove();

                reload_items = false;
            }

            if (show_loading_message) {
                jQuery("#units-items-loading").addClass("hide");
                jQuery("#load-units").removeClass("hide");
                jQuery("#units-table").removeClass("hide");
            } else {
                jQuery("#load-units").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.units, function (key, item) {
                build_unit_row(item);
            });

            show_loading_message = false;
        } else {
            jQuery("#units-items-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No Units Found.");
            jQuery(no_results_div).append(no_results_p).attr({id: "units-no-results"});
            jQuery("#units-msgs").append(no_results_div);
        }
    });
}

function build_unit_row(unit) {
    var unit_title_anchor = document.createElement("a");

    jQuery(unit_title_anchor).attr({href: ENTRADA_URL + "/admin/courses/units?section=edit&id=" + unit.course_id + "&cunit_id=" + unit.cunit_id});

    var unit_row = document.createElement("tr");
    var unit_delete_td = document.createElement("td");
    var unit_title_td = document.createElement("td");
    var unit_but_td = document.createElement("td");
    var unit_delete_input = document.createElement("input");

    jQuery(unit_delete_input).attr({type: "checkbox", "class": "add-unit", name: "units[]", value: unit.cunit_id});
    jQuery(unit_delete_td).append(unit_delete_input);
    jQuery(unit_title_anchor).html(unit.unit_title);
    jQuery(unit_title_td).append(unit_title_anchor).addClass("unit-title");

    jQuery(unit_row).attr("id", "unit-row-" + unit.cunit_id);

    if (!unit.cperiod_id) {
        jQuery(unit_row).addClass("warning");
        jQuery("#no-curriculum-msg").show();
    }

    jQuery(unit_row).append(unit_delete_td).append(unit_title_td).addClass("unit-row");
    jQuery("#units-table").append(unit_row);
}
