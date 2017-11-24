var item_offset = 0;
var item_limit = 50;
var total_members = 0;
var show_loading_message = true;
var timeout;
var reload_items = false;
var sort_object;

jQuery(document).ready(function ($) {

    $("#facultyorstaff_name").autocompletelist({ type: 'facultyorstaff', url: ENTRADA_URL + '/api/personnel.api.php?type=facultyorstaff', remove_image: DELETE_IMAGE_URL});

    get_members(false);

    $("#checkAll").on("click", function(e) {
        $("input[name='members[]']").prop('checked', $(this).prop("checked"));
    });


    $(".member-sort").on("click", function (e) {
        var order = $(this).attr("data-order");

        $.each($(".member-sort"), function(i, item){
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

        get_members();

    });

    $("#load-members").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_members(false);
        }
    });

    $('#delete-members-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#members-selected").addClass("hide");
        $("#no-members-selected").addClass("hide");

        var container = "member-table-container";

        var members_to_delete = $("#" + container + " input[name='members[]']:checked").map(function () {
            return this.value;
        }).get();

        if (members_to_delete.length > 0) {
            $("#members-selected").removeClass("hide");
            $("#delete-members-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='members[]']:checked").each(function(index, element) {
                var list_member = document.createElement("li");
                var member_id = $(element).val();
                var member_text = $("#member-row-" + member_id).find(".member-name a").html();
                $(list_member).append(member_text);
                $(list).append(list_member);
            });
            $("#delete-members-container").append(list);
        } else {
            $("#no-members-selected").removeClass("hide");
            $("#delete-members-modal-delete").addClass("hide");
        }
    });

    $('#delete-members-modal').on('hide.bs.modal', function (e) {
        $("#delete-members-container").html("");
    });

    $("#delete-members-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-members-modal-item").attr("action");

        var container = "member-table-container";

        var members_to_delete = $("#" + container + " input[name='members[]']:checked").map(function () {
            return this.value;
        }).get();

        var member_data = {   "method" : "delete-members",
            "delete_ids" : members_to_delete};

        $("#members-selected").removeClass("hide");
        $("#delete-members-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, member_data, function(data) {
                if (data.status == "success") {
                    $(data.member_ids).each(function(index, member_id) {
                        $("input[name='members[]'][value='" + member_id + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs");
                        if ($("#" + container + " input[name='members[]']").length == 0) {
                            jQuery("#load-members").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(module_text.index.no_items_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "members-no-results"});
                            jQuery("#member-msgs").append(no_results_div);
                        }
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-members-modal').modal('hide');
        });
    });
});

function get_members (item_index) {
    var search_term = "";
    var group_id = jQuery("#group-id").val();
    var item_ids = [];
    var sort_column = "";
    var sort_order = "";

    jQuery("input.add-item:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), item_ids) == -1) {
            item_ids.push(jQuery(element).val());
        }
    });

    //set the item type if we are building a rubric/membered item
    if (sort_object) {
        item_offset = 0;
        total_members = 0;
        show_loading_message = true;
        sort_column = sort_object.attr("data-name");
        sort_order = sort_object.attr("data-order");
    }

    var items = jQuery.ajax({
        url: "?section=api-members",
        data: "method=get-members&group_id=" + group_id + "&search_term=" + search_term + "&offset=" + item_offset + "&limit=" + item_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {

            if (jQuery("#members-no-results").length) {
                jQuery("#members-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#load-members").addClass("hide");
                jQuery("#member-loading").removeClass("hide");
                jQuery("#members-table").addClass("hide");
                jQuery("#item-detail-container").addClass("hide");
                jQuery("#members-table tbody").empty();
                jQuery("#item-detail-container").empty();
            } else {
                jQuery("#load-members").addClass("loading");
            }
        }
    });

    jQuery.when(items).done(function (data) {

        if (jQuery("#members-no-results").length) {
            jQuery("#members-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            if (!reload_items) {
                total_members += parseInt(jsonResponse.results);
            } else {
                total_members = parseInt(jsonResponse.results);
                var checked_items = jQuery("#members-table input.add-item:checkbox:checked").length;
                if (checked_items > 0) {
                    checked_items = checked_items;
                    total_members += checked_items;
                }
            }

            jQuery("#load-members").html("Showing " + total_members + " of " + jsonResponse.data.total_members + " total members");

            if (jsonResponse.results < item_limit) {
                jQuery("#load-members").attr("disabled", "disabled");
            } else {
                jQuery("#load-members").removeAttr("disabled");
            }

            item_offset = (item_limit + item_offset);

            if (reload_items && item_index) {
                jQuery("#members-table").find("tbody tr[id!=item-row-"+item_index+"]").remove();
                jQuery("div#item-detail-container").find("div.item-container[data-item-id!='"+item_index+"']").remove();

                reload_items = false;
            }

            if (show_loading_message) {
                jQuery("#member-loading").addClass("hide");
                jQuery("#load-members").removeClass("hide");
                jQuery("#members-table").removeClass("hide");
            } else {
                jQuery("#load-members").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.members, function (key, item) {
                build_member_row(item);
            });

            show_loading_message = false;
        } else {
            jQuery("#member-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No Members Found.");
            jQuery(no_results_div).append(no_results_p).attr({id: "members-no-results"});
            jQuery("#member-msgs").append(no_results_div);
            jQuery("#search-bar").hide();
        }
    });
}

function build_member_row (member) {
    var member_name_anchor   = document.createElement("a");

    jQuery(member_name_anchor).attr({href: ENTRADA_URL + "/people?profile=" + member.username });

    var member_row            = document.createElement("tr");
    var member_delete_td      = document.createElement("td");
    var member_name_td   = document.createElement("td");
    var member_role_td   = document.createElement("td");
    var member_delete_input   = document.createElement("input");


    jQuery(member_delete_input).attr({type: "checkbox", "class": "add-member", name: "members[]", value: member.member_id});
    jQuery(member_delete_td).append(member_delete_input);
    jQuery(member_name_anchor).html(member.name);
    jQuery(member_name_td).append(member_name_anchor).addClass("member-name");
    jQuery(member_role_td).html(member.grouprole);

    jQuery(member_row).attr("id", "member-row-"+member.member_id);

    if (!member.active) {
        jQuery(member_row)

    }

    jQuery(member_row).append(member_delete_td).append(member_name_td).append(member_role_td).addClass("member-row");
    jQuery("#members-table").append(member_row);

}
