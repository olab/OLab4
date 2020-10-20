var notification_offset = 0;
var notification_limit = 50;
var total_notifications = 0;
var show_loading_message = true;
var timeout;
var reload_notifications = false;
var sort_object;

jQuery(document).ready(function ($) {

    get_notifications(false);

    $("#notification-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            total_notifications = 0;
            notification_offset = 0;
            show_loading_message = true;

            if (e.keyCode == 13) {
                e.preventDefault();
            }

            $("#load-notifications").addClass("hide");
            $("#notifications-table").addClass("hide");
            $("#notification-table-container table tbody, #notification-detail-container").empty();
            $("#notification-detail-container").addClass("hide");

            clearTimeout(timeout);
            timeout = window.setTimeout(get_notifications, 700, false);
        }
    });

    $(".notification-sort").on("click", function (e) {
        var order = $(this).attr("data-order");

        $.each($(".notification-sort"), function(i, notification){
            $(notification).attr("order", "");
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

        get_notifications();

    });

    $('#notification-table-container').on('click', '.community', function(e){
        if (!$(this).hasClass("active")) {
            var community_id = $(this).attr("data-communityid");
            var notify_type = $(this).attr("data-notify-type");
            var value = $(this).prop("checked");
            var proxy_id = $("#proxy_id").val();

            var url = "?section=api-notifications";

            var notification_data = {   "method" : "change-community-notification",
                "community_id" : community_id,
                "notify_type" : notify_type,
                "proxy_id" : proxy_id,
                "value" : value
            };

            var jqxhr = $.post(url, notification_data, function(data) {
                    if (data.status == "success") {
                        display_success([data.msg], "#msgs");
                    } else if(data.status == "error") {
                        display_error([data.msg], "#msgs");
                    }
                },
                "json"
            );
        }

    });

    $("#load-notifications").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_notifications(false);
        }
    });

    $('#delete-notifications-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#notifications-selected").addClass("hide");
        $("#no-notifications-selected").addClass("hide");

        var container = "notification-table-container";

        var notifications_to_delete = $("#" + container + " input[name='notifications[]']:checked").map(function () {
            return this.value;
        }).get();

        if (notifications_to_delete.length > 0) {
            $("#notifications-selected").removeClass("hide");
            $("#delete-notifications-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='notifications[]']:checked").each(function(index, element) {
                var list_notification = document.createElement("li");
                var notification_id = $(element).val();
                var notification_text = $("#notification-row-" + notification_id).find(".notification-title a").html();
                $(list_notification).append(notification_text);
                $(list).append(list_notification);
            });
            $("#delete-notifications-container").append(list);
        } else {
            $("#no-notifications-selected").removeClass("hide");
            $("#delete-notifications-modal-delete").addClass("hide");
        }
    });

    $('#delete-notification-modal').on('hide.bs.modal', function (e) {
        $("#delete-notification-container").html("");
    });

    $("#delete-notifications-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-notifications-modal-notification").attr("action");

        var container = "notification-table-container";

        var notifications_to_delete = $("#" + container + " input[name='notifications[]']:checked").map(function () {
            return this.value;
        }).get();

        var notification_data = {   "method" : "delete-notifications",
            "delete_ids" : notifications_to_delete};

        $("#notifications-selected").removeClass("hide");
        $("#delete-notifications-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, notification_data, function(data) {
                if (data.status == "success") {
                    $(data.notification_ids).each(function(index, notification_id) {
                        $("input[name='notifications[]'][value='" + notification_id + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs");
                        if ($("#" + container + " input[name='notifications[]']").length == 0) {
                            jQuery("#load-notifications").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(module_text.index.no_notifications_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "notifications-no-results"});
                            jQuery("#notification-msgs").append(no_results_div);
                        }
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-notifications-modal').modal('hide');
        });
    });

});

function get_notifications (notification_index) {
    var search_term = jQuery("#notification-search").val();
    var proxy_id = jQuery("#proxy_id").val();
    var notification_ids = [];
    var sort_column = "";
    var sort_order = "";

    if (jQuery("#search-targets-form").length > 0) {
        notification_offset = 0;
        total_notifications = 0;
        show_loading_message = true;
        var filters = jQuery("#search-targets-form").serialize();

    }

    jQuery("input.add-notification:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), notification_ids) == -1) {
            notification_ids.push(jQuery(element).val());
        }
    });

    if (sort_object) {
        notification_offset = 0;
        total_notifications = 0;
        show_loading_message = true;
        sort_column = sort_object.attr("data-name");
        sort_order = sort_object.attr("data-order");
    }

    var notifications = jQuery.ajax({
        url: "?section=api-notifications",
        data: "method=get-community-notifications&proxy_id="+proxy_id+"&search_term=" + search_term + "&offset=" + notification_offset + "&limit=" + notification_limit +
        (sort_column !== "" ? "&col="+sort_column : "")+(sort_order !== "" ? "&ord="+sort_order : ""),
        type: 'GET',
        beforeSend: function () {

            if (jQuery("#notifications-no-results").length) {
                jQuery("#notifications-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#load-notifications").addClass("hide");
                jQuery("#notification-loading").removeClass("hide");
                jQuery("#notifications-table").addClass("hide");
                jQuery("#notification-detail-container").addClass("hide");
                jQuery("#notifications-table tbody").empty();
                jQuery("#notification-detail-container").empty();
            } else {
                jQuery("#load-notifications").addClass("loading");
            }
        }
    });

    jQuery.when(notifications).done(function (data) {

        if (jQuery("#notifications-no-results").length) {
            jQuery("#notifications-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);

        if (jsonResponse.results > 0) {
            if (!reload_notifications) {
                total_notifications += parseInt(jsonResponse.results);
            } else {
                total_notifications = parseInt(jsonResponse.results);
                var checked_notifications = jQuery("#notifications-table input.add-notification:checkbox:checked").length;
                if (checked_notifications > 0) {
                    checked_notifications = checked_notifications;
                    total_notifications += checked_notifications;
                }
            }

            jQuery("#load-notifications").html("Showing " + total_notifications + " of " + jsonResponse.data.total_notifications + " total notifications");

            if (jsonResponse.results < notification_limit) {
                jQuery("#load-notifications").attr("disabled", "disabled");
            } else {
                jQuery("#load-notifications").removeAttr("disabled");
            }

            notification_offset = (notification_limit + notification_offset);

            if (reload_notifications && notification_index) {
                jQuery("#notifications-table").find("tbody tr[id!=notification-row-"+notification_index+"]").remove();
                jQuery("div#notification-detail-container").find("div.notification-container[data-notification-id!='"+notification_index+"']").remove();

                reload_notifications = false;
            }

            if (show_loading_message) {
                jQuery("#notification-loading").addClass("hide");
                jQuery("#load-notifications").removeClass("hide");
                jQuery("#notifications-table").removeClass("hide");
            } else {
                jQuery("#load-notifications").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.notifications, function (key, notification) {
                build_notification_row(notification);
            });

            show_loading_message = false;
        } else {
            jQuery("#notification-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No Notification Found.");
            jQuery(no_results_div).append(no_results_p).attr({id: "notifications-no-results"});
            jQuery("#notification-msgs").append(no_results_div);
        }
    });
}

function build_notification_row (notification) {

    var announcement_checkbox                  = document.createElement("input");
    var event_checkbox                  = document.createElement("input");
    var poll_checkbox                  = document.createElement("input");
    var members_checkbox                  = document.createElement("input");
    var notification_row            = document.createElement("tr");
    var notification_title_td        = document.createElement("td");
    var notification_announcement_td        = document.createElement("td");
    var notification_event_td        = document.createElement("td");
    var notification_poll_td        = document.createElement("td");
    var notification_members_td        = document.createElement("td");
    var notification_title_anchor = document.createElement("a");

    var community_url = ENTRADA_URL + '/community' + notification.community_url;

    jQuery(notification_title_anchor).attr({'href': community_url, 'target': '_blank'}).html(notification.community_title);

    jQuery(notification_title_td).append(notification_title_anchor).addClass("notification-title");

    jQuery(announcement_checkbox).attr({"type": "checkbox", "data-communityid": notification.community_id, "data-notify-type": "announcement"}).addClass("community");

    if (parseInt(notification.announcement) == 1) {
        jQuery(announcement_checkbox).attr({"checked": "true"});
    }

    jQuery(notification_announcement_td).append(announcement_checkbox).addClass("community-notifications");

    jQuery(event_checkbox).attr({"type": "checkbox", "data-communityid": notification.community_id, "data-notify-type": "event"}).addClass("community");

    if (parseInt(notification.event) == 1) {
        jQuery(event_checkbox).attr({"checked": "true"});
    }

    jQuery(notification_event_td).append(event_checkbox).addClass("community-notifications");

    jQuery(poll_checkbox).attr({"type": "checkbox", "data-communityid": notification.community_id, "data-notify-type": "poll"}).addClass("community");

    if (parseInt(notification.poll) == 1) {
        jQuery(poll_checkbox).attr({"checked": "true"});
    }

    jQuery(notification_poll_td).append(poll_checkbox).addClass("community-notifications");

    jQuery(members_checkbox).attr({"type": "checkbox", "data-communityid": notification.community_id, "data-notify-type": "members"}).addClass("community");

    if (parseInt(notification.member_acl) == 1) {
        if (parseInt(notification.members) == 1) {
            jQuery(members_checkbox).attr({"checked": "true"});
        }
        jQuery(notification_members_td).append(members_checkbox).addClass("community-notifications");
    } else {
        jQuery(notification_members_td).html("");
    }

    jQuery(notification_row).attr("id", "notification-row-"+notification.notification_id);

    jQuery(notification_row).append(notification_title_td).append(notification_announcement_td).append(notification_event_td).append(notification_poll_td).append(notification_members_td).addClass("notification-row");
    jQuery("#notifications-table").append(notification_row);

}