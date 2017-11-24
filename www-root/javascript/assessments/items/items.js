var item_offset = 0;
var item_limit = 50;
var total_items = 0;
var show_loading_message = true;
var timeout;
var reload_items = false;
var items_added = [];

function delete_action(form) {
    var action = ENTRADA_URL + "/admin/assessments/items?section=delete&step=1";
    form.action = action;
    return false;
}

function get_items (item_index, specified_width) {
    var search_term = jQuery("#item-search").val();

    var item_ids = [];
    var filters = [];
    
    if (jQuery("#search-targets-form").length > 0) {
        filters = jQuery("#search-targets-form").serialize();
    }
    
    jQuery("input.add-item:checked").each(function(index, element) {
        if (jQuery.inArray(jQuery(element).val(), item_ids) == -1) {
            item_ids.push(jQuery(element).val());
        }
    });

    var exclude_item_ids = item_ids;
    var rubric_reference_token = jQuery("#rref").val();
    var form_reference_token = jQuery("#fref").val();

    var width = jQuery("#rubric_width").val();
    if (specified_width) {
        width = specified_width;
    }

    var items = jQuery.ajax({
        url: "?section=api-items",
        data: {
            "method"            : "get-items",
            "search_term"       : search_term,
            "offset"            : item_offset,
            "limit"             : item_limit,
            "view"              : VIEW_PREFERENCE,
            "rref"              : rubric_reference_token,
            "fref"              : form_reference_token,
            "rubric_width"      : width,
            "exclude_item_ids"  : exclude_item_ids,
            "filters"           : filters
        },
        type: 'GET',
        beforeSend: function () {

            if (jQuery("#assessments-no-results").length) {
                jQuery("#assessments-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#load-items").addClass("hide");
                jQuery("#assessment-items-loading").removeClass("hide");
                jQuery("#items-table").addClass("hide");
                jQuery("#item-detail-container").addClass("hide");
                jQuery("#items-table tbody").empty();
                jQuery("#item-detail-container").empty();
            } else {
                jQuery("#load-items").addClass("loading");
            }
        }
    });

    jQuery.when(items).done(function (data) {

        if (jQuery("#assessments-no-results").length) {
            jQuery("#assessments-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            if (!reload_items) {
                total_items += parseInt(jsonResponse.results);
            } else {
                total_items = parseInt(jsonResponse.results);
                var checked_items = jQuery("#items-table input.add-item:checkbox:checked").length;
                if (checked_items > 0) {
                    checked_items = checked_items;
                    total_items += checked_items;
                }
            }

            var set_disabled = false;
            if (total_items >= jsonResponse.data.total_items) {
                total_items = jsonResponse.data.total_items;
                set_disabled = true;
            }
            jQuery("#load-items").html("Showing " + total_items + " of " + jsonResponse.data.total_items + " total items");

            if (jsonResponse.results < item_limit) {
                jQuery("#load-items").attr("disabled", "disabled");
            } else {
                jQuery("#load-items").removeAttr("disabled");
            }

            if (set_disabled) {
                jQuery("#load-items").attr("disabled", "disabled");
            }

            item_offset = (item_limit + item_offset);

            if (reload_items && item_index) {
                jQuery("#items-table").find("tbody tr[id!=item-row-"+item_index+"]").remove();
                jQuery("div#item-detail-container").find("div.item-container[data-item-id!='"+item_index+"']").remove();

                reload_items = false;
            }

            if (show_loading_message) {
                jQuery("#assessment-items-loading").addClass("hide");
                jQuery("#load-items").removeClass("hide");
                if (VIEW_PREFERENCE === "list") {
                    jQuery("#items-table").removeClass("hide");
                } else {
                    jQuery("#item-detail-container").removeClass("hide");
                }
            } else {
                jQuery("#load-items").removeClass("loading");
            }

            jQuery.each(jsonResponse.data.items, function (key, item) {
                build_item_row(item);
                build_item_details(item);
            });

            show_loading_message = false;
        } else {
            jQuery("#assessment-items-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(submodule_text.index.no_items_found);
            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
            jQuery("#assessment-msgs").append(no_results_div);
        }
    }); 
}

function build_item_additional_details (item_id) {
    var details = jQuery.ajax({
        url: "?section=api-items",
        data: "method=get-item-details&item_id=" + item_id,
        type: "GET"
    });

    jQuery.when(details).done(function (data) {
        var jsonResponse = JSON.parse(data);

        switch (jsonResponse.status) {
            case "success" :
                var item_detail_section_tr  = document.createElement("tr");
                var item_section_td         = document.createElement("td");
                var item_detail_tr          = document.createElement("tr");
                var item_detail_td          = document.createElement("td");
                var item_detail_div         = document.createElement("div");
                var item_code_h5            = document.createElement("h5");
                var item_code_span          = document.createElement("span");
                var item_tags_h5            = document.createElement("h5");
                var item_tags_div           = document.createElement("div");
                var item_ul                 = document.createElement("ul");
                var item_comments_li        = document.createElement("li");
                var item_date_li            = document.createElement("li");

                jQuery(item_code_span).html(jsonResponse.data.item_code);
                jQuery(item_code_h5).html("Item Code: ").append(item_code_span);
                jQuery(item_tags_h5).html("Item Tagged with");
                jQuery(item_tags_div).addClass("item-tags");
                jQuery(item_detail_div).append(item_code_h5).append(item_tags_h5).append(item_tags_div).addClass("item-details-container");
                jQuery(item_detail_td).append(item_detail_div).attr({colspan: jsonResponse.data.total_responses});
                jQuery(item_detail_tr).append(item_detail_td).addClass("item-detail-view");
                jQuery(item_section_td).attr({colspan: jsonResponse.data.total_responses}).html("Item Details");
                jQuery(item_detail_section_tr).addClass("details").append(item_section_td);

                if (jsonResponse.data.hasOwnProperty("tags")) {
                    jQuery(item_tags_div).html(jsonResponse.data.tags);
                } else {
                    jQuery(item_tags_div).html("<h5><span>N/A</span></h5>");
                }

                // TODO: Add localization
                var comment_type;
                switch (jsonResponse.data.comment_type) {
                    case "optional" :
                        comment_type = "optional";
                        break;
                    case "mandatory" :
                        comment_type = "mandatory";
                        break;
                    case "flagged" :
                        comment_type = "mandatory for flagged responses";
                        break;
                    case "disabled" :
                    default :
                        comment_type = "disabled";
                        break;
                }

                jQuery(item_comments_li).html("<span>Comments</span> are " + comment_type + " for this item");
                jQuery(item_date_li).html("<span>Item created on</span>: " + jsonResponse.data.created_date).addClass("pull-right");

                jQuery(item_ul).append(item_comments_li).append(item_date_li);
                jQuery(item_detail_div).append(item_ul);
                jQuery("div[data-item-id=\"" + item_id + "\"]").find(".item-table tbody").append(item_detail_section_tr).append(item_detail_tr);
                break;
            case "error" :
                break;
        }
    });
}

function add_item_summary (title, items, count, width) {
    var container = document.createElement("div");
    var icon_container = document.createElement("i");
    var title_container = document.createElement("p");
    var msg_container = document.createElement("p");

    jQuery(container).addClass("item-summary");
    jQuery(icon_container).addClass("icon-tasks");
    jQuery(title_container).append(icon_container);
    jQuery(title_container).append("&nbsp;" + title);
//        jQuery(msg_container).append("Contains <span id=\"rubric_item_count\">" + count + "</span> item(s).<br />");
    if (width) {
        jQuery(msg_container).append("Width of Grouped Item set to <span id=\"rubric_item_width\">" + width + "</span>.<br />");
    }

    jQuery(container).append(title_container).append(msg_container);
    jQuery("#item-summary").append(container);
}

function create_item_summary(rubric_id) {
    //ToDo: change to use the rubric_width get param to initialize...may also need to add the rubric item count
    var url = ENTRADA_URL + "/admin/assessments/rubrics?section=api-rubric"
    var input_data = {method: "get-rubric", rubric_id: rubric_id };
    var jqxhr = jQuery.get(url, input_data, function(data) {

                }, "json")
                    .done(function(data) {
                        var rubric_title = "Grouped Item Elements";
                        var items = data.data.elements;
                        var count = 0;
                        var width = 0;
                        if (data.status == "success") {
                            rubric_title = data.data.rubric.rubric_title;
                            items = data.data.elements;
                            count = data.data.count;
                            width = data.data.width;
                        }

                        add_item_summary(rubric_title, items, count, width);
                    });
}

function set_view () {
    var selected_view = jQuery("#item-view-controls").children(".active").attr("data-view");

    jQuery.ajax({
        url: "?section=api-items",
        data: "method=view-preference&selected_view=" + selected_view,
        type: 'POST',
        success: function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {

            } else {

            }
        }
    });
}

function build_item_row (item) {
    var item_code_anchor        = document.createElement("a");
    var item_type_anchor        = document.createElement("a");
    var item_responses_anchor   = document.createElement("a");

    jQuery(item_code_anchor).attr({href: ENTRADA_URL + "/admin/assessments/items?section=edit-item&item_id=" + item.item_id});
    jQuery(item_type_anchor).attr({href: ENTRADA_URL + "/admin/assessments/items?section=edit-item&item_id=" + item.item_id});
    jQuery(item_responses_anchor).attr({href: ENTRADA_URL + "/admin/assessments/items?section=edit-item&item_id=" + item.item_id});

    var item_row            = document.createElement("tr");
    var item_delete_td      = document.createElement("td");
    var item_code_td        = document.createElement("td");
    var item_type_td        = document.createElement("td");
    var item_responses_td   = document.createElement("td");
    var item_delete_input   = document.createElement("input");
    var rubric_width_span   = document.createElement("span");
    jQuery(rubric_width_span).attr({"class": "rubric-width"});

    jQuery(item_delete_input).attr({type: "checkbox", "class": "add-item", name: "items[]", value: item.item_id});
    jQuery(item_delete_td).append(item_delete_input);
    jQuery(item_code_anchor).html(item.item_code);
    jQuery(item_type_anchor).html(item.item_type);
    jQuery(rubric_width_span).html(item.item_responses);
    jQuery(item_responses_anchor).html(rubric_width_span);
    jQuery(item_code_td).append(item_code_anchor);
    jQuery(item_type_td).append(item_type_anchor);
    jQuery(item_responses_td).append(item_responses_anchor);

    jQuery(item_row).attr("id", "item-row-"+item.item_id);
    jQuery(item_row).append(item_delete_td).append(item_code_td).append(item_type_td).append(item_responses_td).addClass("item-row");
    jQuery("#items-table").append(item_row);

}

function build_item_details (item) {
    var item_div                    = document.createElement("div");
    var item_table                  = document.createElement("table");
    var item_table_tbody            = document.createElement("tbody");
    var item_table_heading_tr       = document.createElement("tr");
    var item_table_heading_td       = document.createElement("td");
    var item_table_heading_h3       = document.createElement("h3");
    var item_table_type_tr          = document.createElement("tr");
    var item_table_type_td          = document.createElement("td");
    var item_type_span              = document.createElement("span");
    var item_controls               = document.createElement("div");
    var item_controls_span          = document.createElement("span");
    var item_controls_delete        = document.createElement("input");
    var item_controls_btn_group     = document.createElement("div");
    var item_edit_a                 = document.createElement("a");
    var item_detail_a               = document.createElement("a");
    var item_add_a                  = document.createElement("a");

    if (jQuery.inArray(item.item_id, items_added) != "-1") {
        return;
    }

    jQuery(item_edit_a).attr({href: ENTRADA_URL + "/admin/assessments/items?section=edit-item&item_id=" + item.item_id, title: "Edit Item"}).html("<i class=\"icon-pencil\"></i>").addClass("btn edit-item");
    jQuery(item_detail_a).attr({href: ENTRADA_URL + "/admin/assessments/items?section=edit-item&item_id=" + item.item_id, title: "View Item Details"}).html("<i class=\"icon-eye-open\"></i>").addClass("btn item-details");

    jQuery(item_controls_btn_group).append(item_edit_a).append(item_detail_a).append(item_add_a).addClass("btn-group");
    jQuery(item_controls_delete).attr({type: "checkbox", "class": "add-item", name: "items[]", value: item.item_id}).addClass("item-selector");
    jQuery(item_controls_span).append(item_controls_delete).addClass("btn select-item");
    jQuery(item_controls).append(item_controls_span).append(item_controls_btn_group).addClass("pull-right");
    jQuery(item_type_span).attr("data-item-type-id", item.itemtype_id);
    jQuery(item_type_span).html(item.item_type).addClass("item-type");
    jQuery(item_table_type_td).append(item_type_span).append(item_controls);
    jQuery(item_table_type_tr).append(item_table_type_td).addClass("type");
    jQuery(item_table_heading_h3).html(item.item_text);
    jQuery(item_table_heading_td).attr("id", "item-heading-" + item.item_id);
    jQuery(item_table_heading_td).append(item_table_heading_h3);
    jQuery(item_table_heading_tr).append(item_table_heading_td).addClass("heading");
    jQuery(item_table_tbody).append(item_table_type_tr).append(item_table_heading_tr);
    jQuery(item_table).append(item_table_tbody).addClass("item-table");
    jQuery(item_div).attr("data-item-responses", item.item_responses);
    jQuery(item_div).attr("data-item-type-id", item.itemtype_id);
    jQuery(item_div).append(item_table).attr({"data-item-id": item.item_id}).addClass("item-container");

    jQuery("#item-detail-container").append(item_div);
    items_added.push(item.item_id);
    build_responses(item);
}

function build_responses (item) {
    switch (item.itemtype_id) {
        case "1" :
        case "4" :
            build_horizontal_choice_matrix (item);
        break;
        case "2" :
        case "5" :
            build_vertical_choice_matrix (item);
        break;
        case "3" :
        case "6" :
            build_selectbox_response (item);
        break;
        case "7" :
            build_freetext_response (item);
        break;
        case "8" :
            build_date_selector (item);
        break;
        case "9" :
        break;
        case "10" :
            build_numeric_response (item);
        break;
        case "11":
            build_rubric_response(item);
            break;
        case "12":
            build_scale_response(item);
        break;
    }
}

function build_horizontal_choice_matrix (item) {
    var total_responses = parseInt(item.item_responses);
    var item_response_input_tr      = document.createElement("tr");
    var item_response_text_tr       = document.createElement("tr");

    jQuery(item_response_input_tr).addClass("horizontal-response-input item-response-view");
    jQuery(item_response_text_tr).addClass("horizontal-response-label item-response-view");

    jQuery.each(item.responses, function (key, response) {
        var td_width = (100 / total_responses);
        var response_input_td           = document.createElement("td");
        var response_input              = document.createElement("input");
        var response_text_td            = document.createElement("td");
        var response_text_label         = document.createElement("label");

        jQuery(response_input).attr({type: (item.itemtype_id == "1" ? "radio" : "checkbox")}).addClass("item-control");
        jQuery(response_input_td).attr({width: td_width + "%"}).append(response_input);

        jQuery(response_text_label).html(response.text);
        jQuery(response_text_td).attr({width: td_width + "%"}).append(response_text_label);

        jQuery(item_response_input_tr).append(response_input_td);
        jQuery(item_response_text_tr).append(response_text_td);
    });

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody tr td").attr({colspan: total_responses});
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_input_tr).append(item_response_text_tr);
}

function build_rubric_response (item) {
    var total_responses = parseInt(item.item_responses);
    var item_response_input_tr      = document.createElement("tr");
    var item_response_text_tr       = document.createElement("tr");

    jQuery(item_response_input_tr).addClass("horizontal-response-input item-response-view");
    jQuery(item_response_text_tr).addClass("horizontal-response-label item-response-view");

    jQuery.each(item.responses, function (key, response) {
        var td_width = (100 / total_responses);
        var response_input_td           = document.createElement("td");
        var response_input              = document.createElement("input");
        var response_text_td            = document.createElement("td");
        var response_text_label         = document.createElement("label");

        jQuery(response_input).attr({type: "radio"}).addClass("item-control");
        jQuery(response_input_td).attr({width: td_width + "%"}).append(response_input);

        jQuery(response_text_label).html(response.descriptor);
        jQuery(response_text_label).addClass("item-response-label");
        jQuery(response_text_td).attr({width: td_width + "%"}).append(response_text_label);

        jQuery(item_response_input_tr).append(response_input_td);
        jQuery(item_response_text_tr).append(response_text_td);
    });

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody tr td").attr({colspan: total_responses});
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_input_tr).append(item_response_text_tr);
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").attr({"data-total-responses": total_responses});
}

function build_scale_response (item) {
    var total_responses = parseInt(item.item_responses);
    var item_response_input_tr      = document.createElement("tr");
    var item_response_text_tr       = document.createElement("tr");

    jQuery(item_response_input_tr).addClass("horizontal-response-input item-response-view");
    jQuery(item_response_text_tr).addClass("horizontal-response-label item-response-view");

    jQuery.each(item.responses, function (key, response) {
        var td_width = (100 / total_responses);
        var response_input_td           = document.createElement("td");
        var response_input              = document.createElement("input");
        var response_text_td            = document.createElement("td");
        var response_text_label         = document.createElement("label");

        jQuery(response_input).attr({type: "radio"}).addClass("item-control");
        jQuery(response_input_td).attr({width: td_width + "%"}).append(response_input);

        jQuery(response_text_label).html(response.descriptor);
        jQuery(response_text_label).addClass("item-response-label");
        jQuery(response_text_td).attr({width: td_width + "%"}).append(response_text_label);

        jQuery(item_response_input_tr).append(response_input_td);
        jQuery(item_response_text_tr).append(response_text_td);
    });

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody tr td").attr({colspan: total_responses});
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_input_tr).append(item_response_text_tr);
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").attr({"data-total-responses": total_responses});
}

function build_vertical_choice_matrix (item) {
    var total_responses = parseInt(item.item_responses);
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody tr td").attr({colspan: total_responses});

    jQuery.each(item.responses, function (key, response) {
        var item_response_tr            = document.createElement("tr");
        var item_response_input_td      = document.createElement("td");
        var response_input              = document.createElement("input");
        var item_response_text_td       = document.createElement("td");
        var response_text_label         = document.createElement("label");

        jQuery(response_input).attr({type: (item.itemtype_id == "2" ? "radio" : "checkbox")}).addClass("item-control");
        jQuery(item_response_input_td).append(response_input).addClass("vertical-response-input").attr({width: "5%"});

        jQuery(response_text_label).html(response.text);
        jQuery(item_response_text_td).append(response_text_label).addClass("vertical-response-label").attr({width: "95%"});

        jQuery(item_response_tr).append(item_response_input_td).append(item_response_text_td).addClass("vertical-choice-row");
        jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_tr);
    });
}

function build_selectbox_response (item, total_responses) {
    var item_response_tr        = document.createElement("tr");
    var item_response_td        = document.createElement("td");
    var item_response_select    = document.createElement("select");

    jQuery(item_response_select).prop("multiple", (item.itemtype_id == "6" ? true : false)).addClass("item-control");
    jQuery(item_response_td).append(item_response_select).addClass("item-type-control");
    jQuery(item_response_tr).append(item_response_td).addClass("item-response-view");

    jQuery.each(item.responses, function (key, response) {
        var item_response_option = document.createElement("option");
        jQuery(item_response_option).html(response.text);
        jQuery(item_response_select).append(item_response_option);
    });

    jQuery(item_response_td).append(item_response_select);
    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_tr);
}

function build_freetext_response (item) {
    var item_response_tr            = document.createElement("tr");
    var item_response_td            = document.createElement("td");
    var item_response_textarea      = document.createElement("textarea");

    jQuery(item_response_textarea).addClass("form-control item-control input-xlarge");
    jQuery(item_response_td).append(item_response_textarea).addClass("item-type-control");
    jQuery(item_response_tr).append(item_response_td).addClass("item-response-view");

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_tr);
}

function build_numeric_response (item) {
    var item_response_tr        = document.createElement("tr");
    var item_response_td        = document.createElement("td");
    var item_response_input     = document.createElement("input");

    jQuery(item_response_input).attr({type: "text"}).addClass("form-control item-control");
    jQuery(item_response_td).append(item_response_input).addClass("item-type-control");
    jQuery(item_response_tr).append(item_response_td).addClass("item-response-view");

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_tr);
}

function build_date_selector (item) {
    var item_response_tr        = document.createElement("tr");
    var item_response_td        = document.createElement("td");
    var item_response_input     = document.createElement("input");
    var item_input_append_div   = document.createElement("div");
    var item_input_append_span  = document.createElement("span");

    jQuery(item_input_append_span).addClass("add-on").html("<i class=\"icon-calendar\"></i>");
    jQuery(item_response_input).attr({type: "text"}).addClass("datepicker item-control");
    jQuery(item_input_append_div).addClass("input-append").append(item_response_input).append(item_input_append_span).css("width", "50%");
    jQuery(item_response_td).append(item_input_append_div).addClass("item-type-control");
    jQuery(item_response_tr).append(item_response_td).addClass("item-response-view");

    jQuery("div[data-item-id=\"" + item.item_id + "\"]").find(".item-table tbody").append(item_response_tr);
}

function toggle_active_item_detail(item) {
    var item_id = jQuery(item).closest(".item-container").attr("data-item-id");
    if (!jQuery(item).hasClass("active")) {
        build_item_additional_details(item_id);
        jQuery(item).addClass("active");
        jQuery(item).find("i").addClass("icon-white");
    } else {
        jQuery(item).removeClass("active");
        jQuery(item).closest(".item-table").find(".item-detail-view").remove();
        jQuery(item).closest(".item-table").find(".details").remove();
        jQuery(".form-preview-dialog").find(item).closest(".item-table").find(".item-detail-view").remove();
        jQuery(".form-preview-dialog").find(item).find(".details").remove();
        jQuery(item).find("i").removeClass("icon-white");
    }
}

jQuery(document).ready(function ($) {

    $("#attach-selected-item-btn").on("click", function(e){
        e.preventDefault();
        $(this).addClass("disabled");
        $(this).attr("disabled", true);
        $("#form-search").submit();
    });

    $(".remove-single-filter").on("click", function (e) {
        e.preventDefault();

        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");

        var remove_filter_request = $.ajax({
            url: "?section=api-items",
            data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
            type: "POST"
        });

        $.when(remove_filter_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                window.location.reload();
            }
        });
    });

    $("#clear-all-filters").on("click", function (e) {
        e.preventDefault();

        var remove_filter_request = $.ajax({
            url: "?section=api-items",
            data: "method=remove-all-filters",
            type: "POST"
        });

        $.when(remove_filter_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                window.location.reload();
            }
        });
    });

    var item_view_controls = jQuery("#item-view-controls");
    var item_table_container = jQuery("#item-table-container");
    var item_detail_container = jQuery("#item-detail-container");

    var search_term = $("#item-search").val();

    if (list_index === 'undefined') {
        var list_index;
    }
    if (exclude_item_id === 'undefined') {
        var exclude_item_id;
    }

    get_items(false);

    item_view_controls.children("[data-view=\""+ VIEW_PREFERENCE +"\"]").addClass("active");

    if (VIEW_PREFERENCE === "list") {
        item_table_container.removeClass("hide");
    } else {
        item_detail_container.removeClass("hide");
    }

    jQuery(".view-toggle").on("click", function (e) {
        e.preventDefault();
        var selected_view = jQuery(this).attr("data-view");
        VIEW_PREFERENCE = selected_view;

        if (selected_view === "list") {
            $("#items-table").removeClass("hide");
            item_detail_container.addClass("hide");
        } else {
            item_detail_container.removeClass("hide");
            $("#items-table").addClass("hide");
        }

        item_view_controls.children().removeClass("active");
        jQuery(this).addClass("active");
        set_view();
    });

    jQuery("#item-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            total_items = 0;
            item_offset = 0;
            show_loading_message = true;

            if (e.keyCode == 13) {
                e.preventDefault();
            }

            $("#load-items").addClass("hide");
            $("#items-table").addClass("hide");
            $("#item-table-container table tbody, #item-detail-container").empty();
            $("#item-detail-container").addClass("hide");

            clearTimeout(timeout);
            items_added = [];
            timeout = window.setTimeout(get_items, 700, false);
        }
    });

    $("#load-items").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_items(false);
        }
    });

    $("#item-detail-container").on("click", ".item-details", function (e) {
        e.preventDefault();
        toggle_active_item_detail($(this));
    });

    $("#item-detail-container").on("click", ".item-table .item-control", function (e) {
        e.preventDefault();
    });

    $("#item-detail-container").on("change", ".item-selector", function () {
        if (jQuery(this).closest("table").hasClass("selected")) {
            jQuery(this).closest("table").removeClass("selected");
        } else {
            jQuery(this).closest("table").addClass("selected");
        }
    });

    $('#delete-item-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#items-selected").addClass("hide");
        $("#no-items-selected").addClass("hide");
        var container;

        if (VIEW_PREFERENCE == "detail") {
            container = "item-detail-container";
        } else {
            container = "item-table-container";
        }

        var items_to_delete = $("#" + container + " input[name='items[]']:checked").map(function () {
            return this.value;
        }).get();

        if (items_to_delete.length > 0) {
            $("#items-selected").removeClass("hide");
            $("#delete-items-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#" + container + " input[name='items[]']:checked").each(function(index, element) {
                var list_item = document.createElement("li");
                var item_id = $(element).val();
                var item_text = $("div.item-container[data-item-id="+item_id+"] table tr.heading td[id=item-heading-" + item_id + "]").html();
                $(list_item).append($(item_text).text());
                $(list).append(list_item);
            });
            $("#delete-items-container").append(list);
        } else {
            $("#no-items-selected").removeClass("hide");
            $("#delete-items-modal-delete").addClass("hide");
        }
    });

    $('#delete-item-modal').on('hide.bs.modal', function (e) {
        $("#delete-items-container").html("");
    });

    $("#delete-items-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-item-modal-item").attr("action");
        var container;

        if (VIEW_PREFERENCE == "detail") {
            container = "item-detail-container";
        } else {
            container = "item-table-container";
        }

        var items_to_delete = $("#" + container + " input[name='items[]']:checked").map(function () {
            return this.value;
        }).get();
        item_data = {   "method" : "delete-items",
            "delete_ids" : items_to_delete};

        $("#items-selected").removeClass("hide");
        $("#delete-items-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, item_data, function(data) {
                if (data.status == "success") {
                    $(data.item_ids).each(function(index, item_id) {
                        $("input[name='items[]'][value='" + item_id + "']").parent().parent().remove();
                        $("div.item-container[data-item-id="+item_id+"]").remove();
                        display_success([data.msg], "#msgs")
                        if ($("#" + container + " input[name='items[]']").length == 0) {
                            jQuery("#load-items").addClass("hide");
                            var no_results_div = jQuery(document.createElement("div"));
                            var no_results_p = jQuery(document.createElement("p"));

                            no_results_p.html(submodule_text.index.no_items_found);
                            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
                            jQuery("#assessment-msgs").append(no_results_div);
                        }
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-item-modal').modal('hide');
        });
    });

    var original_rubric_width = $("#rubric_width").val();
    var rubric_width = $("#rubric_width").val();
    $("#form-search").on("click", 'input.add-item', function() {
        if ($("#rref").val()) {

            //update the summary box
            var rubric_width_summary = 0;
            if (VIEW_PREFERENCE == "list") {
                rubric_width_summary = $(this).parent().parent().find("span.rubric-width").html();
            } else if (VIEW_PREFERENCE == "detail") {
                rubric_width_summary = $(this).closest("div.item-container").data("itemResponses");
            }

            var item_index = $(this).val();

            //ensure that both the detail view item is checked
            var checked = $(this).is(":checked");

            if (VIEW_PREFERENCE == "list") {
                list_index = $(this).parent().parent().index();
                $("div.item-container[data-item-id='" + item_index + "']").find("input.item-selector").prop("checked", checked);
                $("div.item-container[data-item-id='" + item_index + "']").find("input.item-selector").trigger("change");
            } else {
                $("#items-table input.add-item[value=" + item_index + "]").prop("checked", checked);
            }

            // Refresh the list based on this rubric's rules and only if the rubric width has changed.
            if ((rubric_width_summary != rubric_width) && checked) {
                reload_items = true;
                rubric_width = rubric_width_summary;
                if (item_offset >= item_limit) {
                    item_offset = item_offset - item_limit;
                }
                get_items(item_index, rubric_width);

            } else if (!checked && $("#form-search input.add-item:checkbox:checked").length == 0) {
                if (original_rubric_width != rubric_width) {
                    reload_items = true;
                    $("#items-table").find("tbody tr").remove();
                    $("div#item-detail-container").find("div.item-container").remove();
                    item_offset = 0;
                    rubric_width = original_rubric_width;
                    get_items(item_index, rubric_width);
                }
            }
        }
    });

    if ($("#rref").val() && $("#rubric_id").val()) {
        create_item_summary($("#rubric_id").val());
    }
});