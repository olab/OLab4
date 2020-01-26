var rubric_offset = 0;
var rubric_limit = 50;
var total_rubrics = 0;
var show_loading_message = true;

jQuery(function($) {

    $(".disabled").on("click", function(e) {
        e.preventDefault();
    });

    $("#attach-selected-grouped-items-btn").on("click", function(e){
        e.preventDefault();
        $(this).addClass("disabled");
        $(this).attr("disabled", true);
        $("#rubric-table-form").submit();
    });

    $(".remove-single-filter").on("click", function (e) {
        e.preventDefault();
        
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax(
            {
                url: "?section=api-rubric",
                data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
                type: "POST"
            }
        );

        $.when(remove_filter_request).done
        (
            function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    window.location.reload();
                }
            }
        );
    });
    
    $("#clear-all-filters").on("click", function (e) {
        e.preventDefault();
        
        var remove_filter_request = $.ajax(
           {
               url: "?section=api-rubric",
               data: "method=remove-all-filters",
               type: "POST"
           }
        );

        $.when(remove_filter_request).done
        (
           function (data) {
               var jsonResponse = JSON.parse(data);
               if (jsonResponse.status === "success") {
                   window.location.reload();
               }
           }
        );
    });
    
    var timeout;
    var aritem_id = "";
    
    var rubric_view_controls = $("#rubric-view-controls");
    var rubric_table_container = $("#rubric-table-container");
    var rubric_detail_container = $("#rubric-detail-container");
    
    rubric_view_controls.children("[data-view=\""+ VIEW_PREFERENCE +"\"]").addClass("active");
    
    get_rubrics();
    
    jQuery("#rubric-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

        total_rubrics = 0;
        rubric_offset = 0;
        show_loading_message = true;

            if (e.keyCode == 13) {
        e.preventDefault();
            }

            clearTimeout(timeout);
            timeout = window.setTimeout(get_rubrics, 700, false);
        }
    });

    $(".category-editable").on("mouseenter", function(e) {
        var self = $(this).find(".icon-category-pencil");
            self.show();
    });

    $(".category-editable").on("mouseleave", function(e) {
        var self = $(this).find(".icon-category-pencil");
        self.hide();
    });

    $(".icon-category-pencil").on("click", function(e) {
        $(this).hide();
        $(this).parent().find("h3").hide();
        $(this).parent().find(".btn-category-ok").show();
        $(this).parent().find(".btn-category-remove").show();
        $(this).parent().find("select").show();
    });

    $(".btn-category-remove").on("click", function(e) {
        $(this).hide();
        $(this).parent().find(".btn-category-ok").hide();
        $(this).parent().find("select").hide();
        $(this).parent().find("h3").show();
        $(this).parent().find(".btn-category-pencil").show();
    });

    $(".btn-category-ok").on("click", function(e) {
        var self = $(this);

        self.hide();
        self.parent().find(".btn-category-remove").hide();
        self.parent().find("select").hide();
        self.parent().find(".category-loading").show();

        $(".rubric-error-msg").empty();

        //var descriptor_id = (self.attr("data-descriptor-id"));
        var rubric_id = (self.attr("data-rubric-id"));
        var position = (self.parent().find("h3").attr("data-column-number"));
        var selected_descriptor_id = self.parent().find("select").val();

        var data =  {
                        method : "update-response-category",
                        new_descriptor_id : selected_descriptor_id,
                        rubric_id : rubric_id,
                        position : position
                    };

        var url = API_URL;

        $.post(url, data, function(data) {
            if (data.status == "success") {
                self.parent().find("h3").html(self.parent().find("select option:selected").text());
            } else if(data.status == "error") {
                display_error([data.msg], ".rubric-error-msg");
            }
        },
        "json"
        ).always(function() {
             self.parent().find(".category-loading").hide();
             self.parent().find("h3").show();
             self.parent().find(".icon-category-pencil").show();
             self.attr("data-descriptor-id", data.new_descriptor_id);
         });
    });

    $("#add-rubric-form-modal").submit(function(e) {
        e.preventDefault();
        $("#add-rubric-form-modal").find('input[type=submit]').prop("disabled", true);
        $("#add-rubric-msgs").html("");
        var url = $(this).attr("action");
        var form_data = $(this).serialize();

        if ($(".rating_scale_search_target_control").length > 0) {
            form_data += "&rating_scale_id=" + $(".rating_scale_search_target_control").val();
        }

        var method = "add-rubric";
        if ($("#rubric_id").val() !== undefined && $("#rubric_id").val() != '') {
            method = "update-rubric";
        }
        form_data += "&method=" + encodeURIComponent(method);
        var jqxhr = $.post(url, form_data, function(data) {
                if (data.status == "success") {
                    $("#rubric_id").val(data.rubric_id);
                    $("#rubric-items").removeClass("hide");
                    window.location.href = ENTRADA_URL+"/admin/assessments/rubrics?section=edit-rubric&rubric_id="+data.rubric_id;
                } else if(data.status == "error") {
                    display_error(data.msg, "#add-rubric-msgs");
                    $("#add-rubric-form-modal").find('input[type=submit]').prop("disabled", false);
                }
            },
            "json"
        );
    });

    $("#copy-attach-rubric").on("click", function (e) {
        e.preventDefault();

        // Make a copy request to the Rubric API
        var copy_rubric_request = $.ajax({
            url: API_URL,
            data: {
                section           : "api-rubric",
                method            : "copy-attach-rubric",
                rubric_id         : $(this).data("rubric-id"),
                form_id           : $(this).data("form-id"),
                new_rubric_title  : $("#new-rubric-title").val(),
                fref              : form_referrer_hash

            },
            type: "POST",
            beforeSend: function () {
                $("#copy-attach-rubric").prop("disabled", true);
            },
            error: function () {
                display_error(rubric_localizations.error_unable_to_copy, "#copy-rubric-msgs");
            }
        });

        $.when(copy_rubric_request).done(function (response) {
            if (response.length > 0) {
                var jsonResponse = JSON.parse(response);

                if (jsonResponse.status === "success") {
                    // Navigate to the new rubric's edit page
                    window.location = jsonResponse.url;
                } else {
                    // Display any errors returned from the API in the modal and re-enable the button
                    display_error(jsonResponse.data, "#copy-rubric-msgs");
                    $("#copy-attach-rubric").removeProp("disabled");
                }
            }
        });
    });

    $("#copy-rubric").on("click", function (e) {

        var rubric_id = $(this).attr("data-rubric-id");
        var new_rubric_title = $("#new-rubric-title").val();

        // Make a copy request to the Rubric API
        var copy_rubric_request = $.ajax({
            url: API_URL,
            data: "?section=api-rubric&method=copy-rubric&rubric_id=" + rubric_id + "&new_rubric_title=" + new_rubric_title,
            type: "POST",
            beforeSend: function () {
                $("#copy-rubric").prop("disabled", true);
            },
            error: function () {
                display_error("The action could not be completed. Please try again later", "#copy-rubric-msgs");
            }
        });

        $.when(copy_rubric_request).done(function (response) {
            if (response.length > 0) {
                var jsonResponse = JSON.parse(response);

                if (jsonResponse.status === "success") {
                    // Navigate to the new rubric's edit page
                    window.location = jsonResponse.url;
                } else {
                    // Display any errors returned from the API in the modal and re-enable the button
                    display_error(jsonResponse.msg, "#copy-rubric-msgs");
                    $("#copy-rubric").removeProp("disabled");
                }
            }
        });

        e.preventDefault();
    });

    $("#create-attach-rubric").on("click", function (e) {

        var form_id = $(this).attr("data-form-id");
        var fref = $(this).attr("data-fref");
        var new_rubric_title = $("#new-rubric-title").val();
        var new_rubric_scale_id = $("#create-attach-rubric-modal-form input[name='rating_scale_id']").val();

        // Make a rubric creation request to the Rubric API
        var add_rubric_request = $.ajax({
            url: API_URL,
            data: "?section=api-rubric&method=create-attach-rubric&form_id=" + form_id + "&rubric_title=" + new_rubric_title + "&fref=" + fref + "&rating_scale_id=" + new_rubric_scale_id,
            type: "POST",
            beforeSend: function () {
                $("#create-attach-rubric").prop("disabled", true);
            },
            error: function () {
                display_error(rubric_localizations.error_default, "#create-attach-rubric-msgs");
            }
        });

        $.when(add_rubric_request).done(function (response) {
            if (response.length > 0) {
                var jsonResponse = safeParseJson(response);

                if (jsonResponse.status === "success") {
                    window.location = jsonResponse.url;
                } else {
                    // Display any errors returned from the API in the modal and re-enable the button
                    display_error(jsonResponse.msg, "#create-attach-rubric-msgs");
                    $("#create-attach-rubric").removeProp("disabled");
                }
            }
        });

        e.preventDefault();
    });

    $('#delete-rubric-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#rubrics-selected").addClass("hide");
        $("#no-rubrics-selected").addClass("hide");

        var rubrics_to_delete = $("#rubric-table-form input[name='rubrics[]']:checked").map(function () {
            return this.value;
        }).get();

        if (rubrics_to_delete.length > 0) {
            $("#rubrics-selected").removeClass("hide");
            $("#delete-rubrics-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#rubric-table-form input[name='rubrics[]']:checked").each(function(index, element) {
                var rubric = document.createElement("li")
                var rubric_id = $(element).val()
                $(rubric).append($("#rubric_link_" + rubric_id).html());
                $(list).append(rubric);
            });
            $("#delete-rubrics-container").append(list);
        } else {
            $("#no-rubrics-selected").removeClass("hide");
            $("#delete-rubrics-modal-delete").addClass("hide");
        }
    });

    $('#delete-rubric-modal').on('hide.bs.modal', function (e) {
        $("#delete-rubrics-container").html("");
    });

    $("#delete-rubrics-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-rubric-form-modal").attr("action");
        var rubrics_to_delete = $("#rubric-table-form input[name='rubrics[]']:checked").map(function () {
            return this.value;
        }).get();
        form_data = {   "method" : "delete-rubrics",
                        "delete_ids" : rubrics_to_delete};

        $("#rubrics-selected").removeClass("hide");
        $("#delete-rubrics-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, form_data, function(data) {
                        if (data.status == "success") {
                            $(data.rubric_ids).each(function(index, element) {
                                $("input[name='rubrics[]'][value='" + element + "']").parent().parent().remove();
                                display_success([data.msg], "#msgs")
                            });
                            jQuery("#load-rubrics").html("Showing " + (parseInt(jQuery("#load-rubrics").html().split(" ")[1]) - data.rubric_ids.length) + " of " + (parseInt(jQuery("#load-rubrics").html().split(" ")[3]) - data.rubric_ids.length) + " total items");
                            window.location.reload();
                        } else if(data.status == "error") {
                            display_error([data.msg], "#msgs");
                        }
                    },
                    "json"
                    ) .done(function(data) {
                        $('#delete-rubric-modal').modal('hide');
                    });
    });

    function setDeleteRubricButton() {
        $(".delete-rubric-item").unbind("click");
        $(".delete-rubric-item").on("click", function (e) {
            aritem_id = $(this).closest("tbody").data("aritemId");
            $("#delete-rubric-item-form-modal > #aritem_id").val(aritem_id);
            var item_text = $("tr.rubric-response-input[data-aritem-id=" + aritem_id + "]").find("div.rubric-item-text").html();
            $("#rubric-item-selected").append("<ul><li>" + item_text + "</li></ul>");
        });
    }
    setDeleteRubricButton();

    $('#delete-rubric-item-modal').on('show.bs.modal', function (e) {
    });

    $("#delete-rubric-item-modal-delete").on("click", function(e) {
        e.preventDefault();

        aritem_id = $("#delete-rubric-item-form-modal > #aritem_id").val();
        var rubric_id = $(this).attr("data-rubric-id");
        $.ajax({
            url: API_URL,
            data: "method=delete-rubric-item&aritem_id=" + aritem_id + "&rubric_id=" + rubric_id,
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    location.reload();
                }
            }
        }).done(function(data) {
            $('#delete-rubric-item-modal').modal('hide');
        });
    });

    $('#delete-rubric-item-modal').on('hide.bs.modal', function (e) {
        $("#aritem_id").val("");
        $("#rubric-item-selected").html("");
    });

    if ($(".edit-rubric-label").length) {
        $(".edit-rubric-label").editable(API_URL, {
            id   : 'rlabel_id',
            name : 'label',
            submitdata : function(value, settings) {
                            var rubric_id = $(".rubric-container").attr("data-item-id");
                            var item_id = $(this).data("itemId");
                            return {method : "edit-rubric-label", item_id : item_id, rubric_id : rubric_id}
                        },
            type      : 'textarea',
            cancel    : 'Cancel',
            submit    : 'OK',
            tooltip   : 'Click add description...',
            width     : 140,
            height    : 28,
            data      : function(string) {
                            if (string == "&nbsp;") {
                                return ""
                            } else {
                                return string;
                            }
                        }
        });
    }

    $(".edit-rubric-label-link").on("click", function(e) {
        e.preventDefault();
        $('span.edit-rubric-label[data-item-id="'+$(this).data("itemId")+'"]').trigger("click");
    });

    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);
        $.ajax({
            url: API_URL,
            data: "method=remove-permission&arauthor_id=" + remove_permission_btn.data("arauthor-id"),
            type: "POST",
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    remove_permission_btn.parent().remove();
                } else {
                    alert(jsonResponse.data);
                }
            }
        });
        e.preventDefault();
    });

    $(".move-rubric-item").on("click", function(e){
        e.preventDefault();
    });

    $(".rubric-table").sortable({
        handle: "a.move-rubric-item",
        placeholder: "sortable-placeholder",
        helper: "clone",
        axis: 'y',
        containment: "document",
        start: function(event, ui){
            placeholder_item = ui.item.html();
            ui.placeholder.html(placeholder_item)
            ui.item.startHtml = ui.item.html();
            ui.item.removeClass("ui-draggable");
            ui.item.addClass("ind-drag drag");
        },
        stop: function( event, ui ) {
            var order_array = new Array();
            var count = 0;
            ui.item.html(ui.item.startHtml);
            ui.item.addClass("ui-draggable");
            ui.item.removeClass("ind-drag drag");
            $(".rubric-table tbody").each(function(i, v) {
                if (typeof $(v).data("aritem-id") != "undefined") {
                    order_array[count] = $(v).data("aritem-id");
                    count++;
                }
            });
            setDeleteRubricButton();

            if (order_array.length > 0) {
                $.ajax({
                    url: API_URL,
                    type: "POST",
                    data: {
                        "method" : "update-rubric-item-order",
                        "rubric_id" : $(".rubric-item").data("rubric-id"),
                        "order_array" : order_array
                    },
                    success : function(data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status && jsonResponse.data) {
                            jQuery.animatedNotice(jsonResponse.data, jsonResponse.status, {"resourceUrl": ENTRADA_URL})
                        }
                    }
                });
            }
        }
    });

    $("#load-rubrics").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_rubrics();
        }
    });

    $("#item-rating-scale-btn").on("change", function() {
        var rref = $("#post_rref").val();
        var scale_id = $("input[name='rating_scale_id']").val();
        if (rref) {
            $.ajax({
                url: API_URL,
                type: "POST",
                async: false,
                data: {
                    "method" : "update-rubric-scale",
                    "rating_scale_id" : scale_id,
                    "rref" : rref
                },
                beforeSend : function() {
                    $("#create-and-attach-add-element").addClass("disabled");
                    $("#add-element").addClass("disabled");
                },
                success : function(data) {
                    var jsonResponse = safeParseJson(data);
                    if (jsonResponse.status == "error") {
                        display_error([jsonResponse.data], "#msgs");
                    } else {
                        $("#create-and-attach-add-element").removeClass("disabled");
                        $("#add-element").removeClass("disabled");
                    }
                }
            });
        }
    });

});

function add_element_url() {
    var rubric_id = jQuery("#rubric_id").val();

    jQuery("#add-element").attr('href', function() {
        return jQuery("#add-element").attr('href') + '&rubric_id=' + rubric_id;
    });
}

function get_rubrics () {

    if (jQuery("#search-targets-form").length > 0) {
        var filters = jQuery("#search-targets-form").serialize();
    }

    if (jQuery("#item-id").length > 0) {
        var item_id = jQuery("#item-id").val();
    }

    var search_term = jQuery("#rubric-search").val();
    var rubrics = jQuery.ajax(
        {
            url: "?section=api-rubric",
            data:
                "method=get-rubrics&search_term=" +
                search_term +
                "&limit=" + rubric_limit +
                "&offset=" + rubric_offset +
                (typeof item_id !== "undefined" ? "&item_id=" + item_id : "") +
                (typeof filters !== "undefined" ? "&" + filters : ""),
            type: "GET",
            beforeSend: function () {

                if (jQuery("#assessments-no-results").length) {
                    jQuery("#assessments-no-results").remove();
                }

                if (show_loading_message) {
                    jQuery("#assessment-rubrics-loading").removeClass("hide");
                    jQuery("#rubrics-table").addClass("hide");
                    jQuery("#load-rubrics").addClass("hide");
                    jQuery("#rubrics-table tbody").empty();
                } else {
                    jQuery("#load-rubrics").addClass("loading");
                }
            }
        }
    );

    jQuery.when(rubrics).done(function (data) {

        if (jQuery("#assessments-no-results").length) {
            jQuery("#assessments-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
       
        if (jsonResponse.results > 0) {

            var set_disabled = false;
            total_rubrics += parseInt(jsonResponse.results);

            if (total_rubrics > jsonResponse.data.total_rubrics) {
                set_disabled = true;
                total_rubrics = jsonResponse.data.total_rubrics;
            }
            jQuery("#load-rubrics").html("Showing " + total_rubrics + " of " + jsonResponse.data.total_rubrics + " total grouped items");

            if (jsonResponse.results < rubric_limit) {
                jQuery("#load-rubrics").attr("disabled", "disabled");
            } else {
                jQuery("#load-rubrics").removeAttr("disabled");
            }
            if (set_disabled) {
                jQuery("#load-rubrics").attr("disabled", "disabled");
            }

            rubric_offset = (rubric_limit + rubric_offset);

            jQuery.each(jsonResponse.data.rubrics, function (key, rubric) {
                build_rubric_row(rubric);
            });

            if (show_loading_message) {
                jQuery("#assessment-rubrics-loading").addClass("hide");
                jQuery("#rubrics-table").removeClass("hide");
                jQuery("#load-rubrics").removeClass("hide");
            } else {
                jQuery("#load-rubrics").removeClass("loading");
            }

            show_loading_message = false;

        } else {
            jQuery("#assessment-rubrics-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(submodule_text.index.no_rubrics_found);
            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
            jQuery("#assessment-msgs").append(no_results_div);
        }
    });
}

function build_rubric_row (rubric) {

    var item_id = false;
    if (jQuery("#item-id").length > 0) {
        item_id = jQuery("#item-id").val();
    }

    var rubric_title_anchor       = document.createElement("a");
    var rubric_date_anchor        = document.createElement("a");
    //var item_count_anchor         = document.createElement("a");

    var url = ENTRADA_URL + "/admin/assessments/rubrics?section=edit-rubric&rubric_id=" + rubric.rubric_id;

    jQuery(rubric_title_anchor).attr({href: url});
    jQuery(rubric_date_anchor).attr({href: url});
    //jQuery(item_count_anchor).attr({href: ENTRADA_URL + "/admin/assessments/rubrics?section=edit-rubric&rubric_id=" + rubric.rubric_id});

    var rubric_row            = document.createElement("tr");
    var rubric_delete_td      = document.createElement("td");
    var rubric_title_td       = document.createElement("td");
    var rubric_date_td        = document.createElement("td");
    //var rubric_items_td       = document.createElement("td");
    var rubric_delete_input   = document.createElement("input");

    jQuery(rubric_delete_input).attr({type: "checkbox", "class": "add-rubric", name: "rubrics[]", value: rubric.rubric_id});
    jQuery(rubric_delete_td).append(rubric_delete_input);
    jQuery(rubric_title_anchor).html(rubric.title);
    jQuery(rubric_title_anchor).attr("id", "rubric_link_"+rubric.rubric_id)
    jQuery(rubric_date_anchor).html(rubric.created_date);
    //jQuery(item_count_anchor).html(rubric.item_count);
    jQuery(rubric_title_td).append(rubric_title_anchor);
    jQuery(rubric_date_td).append(rubric_date_anchor);
    //jQuery(rubric_items_td).append(item_count_anchor);

    jQuery(rubric_row).append(rubric_delete_td).append(rubric_title_td).append(rubric_date_td).addClass("rubric-row");
    jQuery("#rubrics-table").append(rubric_row);
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
    jQuery(item_type_span).attr("data-item-type-id", item.itemtype_id);
    jQuery(item_type_span).html(item.item_type).addClass("item-type");
    jQuery(item_table_type_td).append(item_type_span);
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

        jQuery(item_response_tr).append(item_response_input_td).append(item_response_text_td).addClass("vertical-choice-row item-response-view");
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
