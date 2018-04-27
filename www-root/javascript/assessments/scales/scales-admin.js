var scale_offset = 0; // scale offeset
var scale_limit = 50;
var total_scales = 0;
var current_results = 0;
var show_loading_message = true;

jQuery(function($) {

    // Disabling the editing of everything in the scale but permissions if it is used by a form that is in use by a distribution
    // Note that most inputs are hidden with PHP logic in the form.in.php
    if (typeof scale_in_use !== 'undefined' && scale_in_use == "true") {
        // Disable all input elements
        $("#scale-form :input").prop("disabled", true);
        // Apply disabled CSS to links and prevent actions
        $("#scale-form a").addClass("disabled").click(function (e) {
            e.preventDefault();
        });
        // Re-enable copying, previewing, permissions, saving, and item detail viewing
        $("#copy-scale-link").removeClass("disabled");
        $("#contact-selector").removeProp("disabled");
        $("#contact-type").removeProp("disabled");
        $("a.btn.item-details.disabled").removeClass("disabled");
        // Remove move "links" to prevent reordering and deleting
        $("a.btn.move-scale-item").remove();
        $("a.btn.delete-scale-item").remove();
    }

    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();

        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax(
            {
                url: "?section=api-scales",
                data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
                type: "POST"
            }
        );

        $.when(remove_filter_request).done
        (
            function (data) {
                var jsonResponse = safeParseJson(data, ["Unknown Error"]);
                if (jsonResponse.status === "success") {
                    window.location.reload();
                }
            }
        );
    });

    $(".panel .clear-filters").on("click", function (e) {
        e.preventDefault();

        var remove_filter_request = $.ajax(
            {
                url: "?section=api-scales",
                data: "method=remove-all-filters",
                type: "POST"
            }
        );

        $.when(remove_filter_request).done
        (
            function (data) {
                var jsonResponse = safeParseJson(data, ["Unknown Error"]);
                if (jsonResponse.status === "success") {
                    window.location.reload();
                }
            }
        );
    });

    var timeout;

    // get list of all scales
    get_scales();

    jQuery("#scale-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            total_scales = 0;
            scale_offset = 0;
            current_results = 0;
            show_loading_message = true;

            if (e.keyCode == 13) {
                e.preventDefault();
            }

            clearTimeout(timeout);
            timeout = window.setTimeout(get_scales, 700, false);
        }
    });

    $('#delete-scale-modal').on('show.bs.modal', function (e) {
        $("#msgs").html("");
        $("#scales-selected").addClass("hide");
        $("#no-scales-selected").addClass("hide");

        var scales_to_delete = $("#scale-table-form input[name='scales[]']:checked").map(function () {
            return this.value;
        }).get();

        if (scales_to_delete.length > 0) {
            $("#scales-selected").removeClass("hide");
            $("#delete-scales-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#scale-table-form input[name='scales[]']:checked").each(function(index, element) {
                var scale = document.createElement("li")
                var scale_id = $(element).val()
                $(scale).append($("#scale_link_" + scale_id).html());
                $(list).append(scale);
            });
            $("#delete-scales-container").append(list);
        } else {
            $("#no-scales-selected").removeClass("hide");
            $("#delete-scales-modal-delete").addClass("hide");
        }
    });

    $('#delete-scale-modal').on('hide.bs.modal', function (e) {
        $("#delete-scales-container").html("");
    });

    $("#delete-scales-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-scale-form-modal").attr("action");
        var scales_to_delete = $("#scale-table-form input[name='scales[]']:checked").map(function () {
            return this.value;
        }).get();

        var form_data = {
            "method" : "delete-scales",
            "delete_ids" : scales_to_delete
        };

        $("#scales-selected").removeClass("hide");
        $("#delete-scales-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, form_data, function(data) {
                if (data.status == "success") {
                    window.location.reload();
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-scale-modal').modal('hide');
        });
    });

    $("#load-scales").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_scales();
        }
    });

    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);

        $.ajax({
            url: API_URL,
            data: "method=remove-permission&aiauthor_id=" + remove_permission_btn.data("aiauthor-id"),
            type: "POST",
            success: function(data) {
                var jsonResponse = safeParseJson(data, ["Unknown Error"]);
                if (jsonResponse.status == "success") {
                    remove_permission_btn.parent().remove();
                } else {
                    alert(jsonResponse.data);
                }
            }
        });
        e.preventDefault();
    });
});

// function to get list of scales
function get_scales () {
    var filters = jQuery("#search-targets-form").serialize();

    var search_term = jQuery("#scale-search").val();
    var scales = jQuery.ajax({
        url: "?section=api-scales",
        data: "method=get-scales&search_term=" + search_term + "&limit=" + scale_limit + "&offset=" + scale_offset + (typeof filters !== "undefined" ? "&" + filters : ""),
        type: "GET",
        beforeSend: function () {

            if (jQuery("#assessments-no-results").length) {
                jQuery("#assessments-no-results").remove();
            }

            if (show_loading_message) {
                jQuery("#assessment-items-loading").removeClass("hide");
                jQuery("#scales-table").addClass("hide");
                jQuery("#load-scales").addClass("hide");
                jQuery("#scales-table tbody").empty();
            } else {
                jQuery("#load-scales").addClass("loading");
            }
        }
    });

    jQuery.when(scales).done(function (data) {

        if (jQuery("#assessments-no-results").length) {
            jQuery("#assessments-no-results").remove();
        }

        var jsonResponse = safeParseJson(data, ["Unknown Error"]);

        if (jsonResponse.status == "success") {
            current_results += jsonResponse.current_scales;

            var replacement_string = scale_localization.load_more_template;
            replacement_string = replacement_string.replace("%%current_results%%", current_results);
            replacement_string = replacement_string.replace("%%total_scales%%", jsonResponse.total_scales);

            jQuery("#load-scales").html(replacement_string);

            if (current_results >= jsonResponse.total_scales) {
                jQuery("#load-scales").attr("disabled", "disabled");
            } else {
                jQuery("#load-scales").removeAttr("disabled");
            }

            scale_offset = (scale_limit + scale_offset);

            jQuery.each(jsonResponse.data, function (key, scale) {
                build_scale_row(scale);
            });

            if (show_loading_message) {
                jQuery("#assessment-items-loading").addClass("hide");
                jQuery("#scales-table").removeClass("hide");
                jQuery("#load-scales").removeClass("hide");
            } else {
                jQuery("#load-scales").removeClass("loading");
            }

            show_loading_message = false;

        } else {
            jQuery("#assessment-items-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div")).attr({id: "assessments-no-results"});
            var no_results_p = jQuery(document.createElement("p")).html(jsonResponse.data);
            jQuery("#assessment-msgs").append(no_results_div.append(no_results_p));
        }
    });
}

// function to build one row for one scale record
function build_scale_row (scale) {

    // leverage loadTemplate

    var url = ENTRADA_URL + "/admin/assessments/scales?section=edit-scales&rating_scale_id=" + scale.rating_scale_id;

    var scale_delete_input   = jQuery(document.createElement("input")).attr({type: "checkbox", "class": "add-scale", name: "scales[]", value: scale.rating_scale_id});
    var scale_title_anchor   = jQuery(document.createElement("a")).attr({href: url, "id": "scale_link_" + scale.rating_scale_id}).html(scale.rating_scale_title);
    var scale_type_anchor    = jQuery(document.createElement("a")).attr({href: url}).html(scale.rating_scale_type);
    var scale_date_anchor    = jQuery(document.createElement("a")).attr({href: url}).html(scale.created_date);

    var scale_row            = jQuery(document.createElement("tr"));
    var scale_delete_td      = jQuery(document.createElement("td")).append(scale_delete_input);
    var scale_title_td       = jQuery(document.createElement("td")).append(scale_title_anchor);
    var scale_type_td        = jQuery(document.createElement("td")).append(scale_type_anchor);
    var scale_date_td        = jQuery(document.createElement("td")).append(scale_date_anchor).addClass("scale-row");

    scale_row.append(scale_delete_td, scale_title_td, scale_type_td, scale_date_td);
    jQuery("#scales-table").append(scale_row);
}
