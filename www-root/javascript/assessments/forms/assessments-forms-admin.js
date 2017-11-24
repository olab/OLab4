var form_offset = 0;
var form_limit = 50;
var total_forms = 0;
var show_loading_message = true;

jQuery(function($) {
    $(".move").on("click", function(e){
        e.preventDefault();
    });
    $("a.update-form-on-click").on("click", function(e) {
        e.preventDefault();
        var link = $(this).attr("href");
        $.ajax({
            url: "?section=api-forms",
            type: "POST",
            data: {
                "method"            : "update-form-primitives",
                "form_id"           : $("#form-elements").data("form-id"),
                "form_title"        : $("#form-title").val(),
                "form_description"  : $("#form-description").val()
            },
            beforeSend: function() {
                // Add some spinner or disabling semantics here.
            },
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                if (jsonResponse.status == "success") {
                    window.location = link; // follow link on click
                } else {
                    display_error(jsonResponse.data, "#form-information-error-msg");
                }
            }
        });
    });

    // Disabling the editing of everything in the form but permissions if the form is in use by a distribution
    // Note that most inputs are hidden with PHP logic in the form.in.php
    if (typeof form_in_use !== 'undefined' && form_in_use == "true") {
        // Disable all input elements
        $("#form-elements :input").prop("disabled", true);
        // Apply disabled CSS to links and prevent actions
        $("#form-elements a").addClass("disabled").click(function (e) {
            if (!$(this).hasClass("always-enabled")) {
                e.preventDefault();
            }
        });
        $(".always-enabled").removeClass("disabled");
        // Re-enable copying, previewing, permissions, saving, and item detail viewing
        $("#copy-form-link").removeClass("disabled");
        $("#preview-form").removeProp("disabled");
        $("#contact-selector").removeProp("disabled");
        $("#contact-type").removeProp("disabled");
        $("a.btn.item-details.disabled").removeClass("disabled");
        // Remove move "links" to prevent reordering
        $("a.btn.move").remove();

    }

    $(".item-details").on("click", function (e) {
        e.preventDefault();
        toggle_active_item_detail($(this));
    });

    $(".remove-single-filter").on("click", function (e) {
        e.preventDefault();
        
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax({
            url: "?section=api-forms",
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
           url: "?section=api-forms",
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
    
    var timeout;
    
    get_forms();
    
    jQuery("#form-search").keydown(function (e) {
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

            total_forms = 0;
            form_offset = 0;
            show_loading_message = true;

            clearTimeout(timeout);
            timeout = window.setTimeout(get_forms, 700);
        }
    });
    
    jQuery("#load-forms").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_forms();
        }
    });
    
    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);
        $.ajax({
            url: API_URL,
            data: "method=remove-permission&afauthor_id=" + remove_permission_btn.data("afauthor-id"),
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
    
    $(".add-curriculum-set").on("click", function (e) {
        var btn = $(this);
       
        var save_element_request = $.ajax({
            url: API_URL,
            data: "method=add-objective-element&form_id=" + btn.data("form-id"),
            type: "POST"
        });
        
        var objective_request = $.ajax({
            url: API_URL,
            data: "method=get-objectives",
            type: "GET"
        });
        
        $.when(objective_request, save_element_request).done(function (objective_data, save_data) {
            var objective_response  = objective_data[0];
            var save_response       = save_data[0];
            
            if (objective_response.length > 0 && save_response.length > 0) {
                var objectiveJsonResponse   = JSON.parse(objective_response);
                var saveJsonResponse        = JSON.parse(save_response);
                
                if (objectiveJsonResponse.status === "success" && saveJsonResponse.status === "success") {
                    var form_item           = $(document.createElement("div")).addClass("form-item").attr({"data-afelement-id": saveJsonResponse.data.afelement_id});
                    var item_container      = $(document.createElement("div")).addClass("item-container");
                    var item_table          = $(document.createElement("table")).addClass("item-table");
                    var type_row            = $(document.createElement("tr")).addClass("type");
                    var type_cell           = $(document.createElement("td")).addClass("type");

                    var span                = $(document.createElement("span")).addClass("item-type").html("Curriculum Tag Set");
                    var div                 = $(document.createElement("div")).addClass("pull-right");
                    var btn_span            = $(document.createElement("span")).addClass("btn");
                    var delete_input        = $(document.createElement("input")).attr({type: "checkbox", value: saveJsonResponse.data.afelement_id, name: "delete[]"}).addClass("delete");
                    var options_btn_group   = $(document.createElement("div")).addClass("btn-group");
                    var save_btn            = $(document.createElement("a")).addClass("btn save-objective").html("Save Required").attr({"data-element-id": saveJsonResponse.data.afelement_id}).addClass("save-required");
                    var reorder_a           = $(document.createElement("a")).attr({title: "Move"}).addClass("btn move");
                    var reorder_i           = $(document.createElement("i")).addClass("icon-move");

                    var description_row     = $(document.createElement("tr")).addClass("heading");
                    var description_cell    = $(document.createElement("td"));
                    var description_h3      = $(document.createElement("h3")).html("Select a Curriculum Tag Set");

                    var content_row         = $(document.createElement("tr")).addClass("item-response-view");
                    var content_cell        = $(document.createElement("td")).addClass("item-type-control");
                    var objective_container = $(document.createElement("div")).attr({id: "element-" + saveJsonResponse.data.afelement_id,"data-element-id": saveJsonResponse.data.afelement_id});

                    $.each(objectiveJsonResponse.data, function (i, objective) {
                        var objective_label = $(document.createElement("label")).addClass("radio form-item-objective-label");
                        var objective_input = $(document.createElement("input")).attr({type: "radio", name: "form_item_objective_" + saveJsonResponse.data.afelement_id, value: objective.target_id, "data-element-id": saveJsonResponse.data.afelement_id});
                        
                        objective_label.append(objective_input).append(objective.target_label);
                        objective_container.append(objective_label);
                    });
                    
                    content_cell.append(objective_container);
                    content_row.append(content_cell);
                    reorder_a.append(reorder_i);
                    btn_span.append(delete_input);
                    options_btn_group.append(save_btn).append(btn_span).append(reorder_a);
                    div.append(options_btn_group);

                    description_cell.append(description_h3);
                    description_row.append(description_cell);
                    type_cell.append(span).append(div);
                    type_row.append(type_cell);
                    item_table.append(type_row).append(description_row).append(content_row);
                    item_container.append(item_table);
                    form_item.append(item_container);
                    $("#form-items").append(form_item);

                    $(".no-items-attached-message").remove();
                    $(".visible-when-form-populated").removeClass("hide");
                    $("html, body").animate({scrollTop: $("#element-" + saveJsonResponse.data.afelement_id).offset().top}, 500);

                    $("#form-items .move").unbind("click");
                    $("#form-items .move").on("click", function(e){
                        e.preventDefault();
                    });
                } else {
                    
                }
            }
        });
       
        e.preventDefault();
    });

    $(".add-text").on("click", function(e) {
        var btn = $(this);
        $.ajax({
            url: API_URL,
            type: "POST",
            data: "method=add-text&form_id=" + btn.data("form-id"),
            success: function (data) {

                var jsonResponse = JSON.parse(data);

                var form_item       = $(document.createElement("div")).addClass("form-item").attr("data-afelement-id", jsonResponse.data.afelement_id);
                var item_container  = $(document.createElement("div")).addClass("item-container");
                var item_table      = $(document.createElement("table")).addClass("item-table");
                var type_row        = $(document.createElement("tr")).addClass("type");
                var type_cell       = $(document.createElement("td")).addClass("type");
                type_cell.append(
                    $(document.createElement("span")).addClass("item-type").html("Free Text"),
                    $(document.createElement("div")).addClass("pull-right").append(
                        $(document.createElement("div")).addClass("btn-group").append(
                            $(document.createElement("a")).addClass("btn save-element").attr({"data-text-element-id" : jsonResponse.data.afelement_id, "href" : "#"}).html("Save"),
                            $(document.createElement("span")).addClass("btn").append(
                                $(document.createElement("input")).attr({"type" : "checkbox", "class" : "delete", "name" : "delete[]", "value" : jsonResponse.data.afelement_id})
                            ),
                            $(document.createElement("a")).addClass("btn move").attr({"data-text-element-id" : jsonResponse.data.afelement_id, "href" : "#"}).html("<i class=\"icon-move\"></i>")

                        )
                    )
                )

                form_item.append(item_container.append(item_table.append(type_row.append(type_cell))));

                var textarea_row = $(document.createElement("tr"));
                var textarea_cell = $(document.createElement("td")).attr("style", "padding:0px 10px;");

                var textarea_container = $(document.createElement("div")).addClass("row-fluid space-above space-below");
                var textarea = $(document.createElement("textarea")).attr({"id": "element-" + jsonResponse.data.afelement_id, "name" : "element["+jsonResponse.data.afelement_id+"]"});

                form_item.append(item_table.append(textarea_row.append(textarea_cell.append(textarea_container.append(textarea)))));

                $("#form-items").append(form_item);

                $(".no-items-attached-message").remove();
                $(".visible-when-form-populated").removeClass("hide");
                $('html, body').animate({scrollTop: $("#element-" + jsonResponse.data.afelement_id).offset().top}, 500);
                $("#form-items .move").unbind("click");
                $("#form-items .move").on("click", function(e){
                    e.preventDefault();
                });

                CKEDITOR.replace("element-" + jsonResponse.data.afelement_id);
            }
        });
        e.preventDefault();
    });

    $("#copy-form").on("click", function (e) {

        var form_id = $(this).attr("data-form-id");
        var new_form_title = $("#new-form-title").val();

        // Make a copy request to the Form API
        var copy_form_request = $.ajax({
            url: API_URL,
            data: "?section=api-forms&method=copy-form&form_id=" + form_id + "&new_form_title=" + new_form_title,
            type: "POST",
            beforeSend: function () {
                $("#copy-form").prop("disabled", true);
            },
            error: function () {
                display_error("The action could not be completed. Please try again later", "#copy-form-msgs");
            }
        });

        $.when(copy_form_request).done(function (response) {
            if (response.length > 0) {
                var jsonResponse = JSON.parse(response);

                if (jsonResponse.status === "success") {
                    // Navigate to the new form's edit page
                    window.location = jsonResponse.url;
                } else {
                    // Display any errors returned from the API in the modal and re-enable the button
                    display_error(jsonResponse.msg, "#copy-form-msgs");
                    $("#copy-form").removeProp("disabled");
                }
            }
        });

        e.preventDefault();
    });

    // Preview dialog setup
    var previewLightbox = $("div.preview-dialog-content").dialog({
        draggable: false,
        resizable: false,
        autoOpen: false,
        dialogClass: "preview-dialog-container",
        buttons: [ // We're just hiding this whole footer anyways.
            {
                "text": "OK",
                "click": function() {
                    $(this).dialog("close");
                }
            }
        ],
        // Destroy RTE for dialog and page scrolling behind the dialog
        open: function (event, ui) {
            $("body").css({ overflow: 'hidden' });
        },
        beforeClose: function (event, ui) {
            $("body").css({ overflow: 'inherit' });
        }
    });

    var contentInner = $("div.preview-dialog-content-inner", previewLightbox);
    previewLightbox.dialog("option", "title", "<h1>Form Preview</h1>");

    $("a.ui-dialog-titlebar-close", previewLightbox).addClass("glyphicon");

    var dialogRef = previewLightbox;
    previewLightbox.on("dialogopen", function(event, ui) {
        dialogRef.dialog("widget").css("opacity", "1");
    });
    previewLightbox.on("dialogclose", function(event, ui) {
        dialogRef.dialog("widget").css("opacity", "0");
    });

    $("#preview-form").on("click", function (e) {
        e.preventDefault();
        // Close all item details to prevent them from being displayed in the preview.
        $(".item-table").each(function () {
            $(this).find(".item-detail-view").remove();
            $(this).find(".details").remove();
        });
        $(".item-details").each(function () {
            $(this).removeClass("active");
            $(this).find("i").removeClass("icon-white");
        });
        previewLightbox.dialog("open");
    });

    $("#form-items").on("click", ".save-objective", function(e) {
        var btn = $(this);
        var afelement_id = btn.attr("data-element-id");
        
        if ($("input[name=\"form_item_objective_"+ afelement_id +"\"]").is(":checked")) {
            var selected_objective = $("input[name=\"form_item_objective_"+ afelement_id +"\"]:checked").val();
            var update_objective_request = $.ajax({
                url: API_URL,
                data: "method=save-objective-element&afelement_id=" + afelement_id + "&element_id=" + selected_objective,
                type: "POST"
            });
            
            $.when(update_objective_request).done(function (response) {
                if (response.length > 0) {
                    var jsonResponse = JSON.parse(response);
                    if (jsonResponse.status === "success") {
                        btn.removeClass("save-required");
                        btn.html("Saved").css("backgroundColor", "#AADDAA").animate({
                            "backgroundColor" : "#ffffff"
                        }, 1000, function() {
                            $(this);
                        });
                    }
                }
            });
        } else {
            alert("Please select an objective set for this form item");
        }
    });
    
    $("#form-items").on("change", "input[name^=\"form_item_objective_\"]", function () {
        var afelement_id = $(this).attr("data-element-id");
        $("a[data-element-id=\""+ afelement_id +"\"]").addClass("save-required").html("Save Required");
    });

    $("#form-items").on("click", ".save-element", function(e) {
        var btn = $(this);
        var ckeditor_instance = "element-" + btn.data("text-element-id");
        var ckeditor_data = CKEDITOR.instances[ckeditor_instance].getData();

        $.ajax({
            url: API_URL,
            type: "POST",
            data: {
                "method" : "save-text-element",
                "afelement_id" : btn.data("text-element-id"),
                "element_text" : ckeditor_data
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                btn.html("Saved").css("backgroundColor", "#AADDAA").animate({
                    "backgroundColor" : "#ffffff"
                }, 5000, function() {
                    $(this).html("Save");
                });
            }
        });

        e.preventDefault();
    });

    $("#form-items").sortable({
        handle: "a.move",
        stop: function( event, ui ) {
            var order_array = new Array;
            $("#form-items .form-item").each(function(i, v) {
                order_array[i] = new Object({
                    "afelement_id" : $(v).data("afelement-id"),
                    "order" : i
                });
            });

            if (order_array.length > 0) {
                $.ajax({
                    url: API_URL,
                    type: "POST",
                    data: {
                        "method" : "update-form-element-order",
                        "form_id" : $("#form-elements").data("form-id"),
                        "order_array" : order_array
                    }, 
                    success : function(data) {

                    }
                });
            }

        }
    });

    $('#delete-form-modal').on('show.bs.modal', function (e) {
        $('#delete-forms-modal').removeClass("hide");
        $("#msgs").html("");
        $("#forms-selected").addClass("hide");
        $("#no-forms-selected").addClass("hide");

        var forms_to_delete = $("#form-table-form input[name='forms[]']:checked").map(function () {
            return this.value;
        }).get();

        if (forms_to_delete.length > 0) {
            $("#forms-selected").removeClass("hide");
            $("#delete-forms-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#form-table-form input[name='forms[]']:checked").each(function(index, element) {
                var list_item = document.createElement("li");
                var form_id = $(element).val();
                $(list_item).append($("#form_title_link_" + form_id).html());
                $(list).append(list_item);
            });
            $("#delete-forms-container").append(list);
        } else {
            $("#no-forms-selected").removeClass("hide");
            $("#delete-forms-modal-delete").addClass("hide");
        }
    });

    $('#delete-form-modal').on('hide.bs.modal', function (e) {
        $('#delete-forms-modal').addClass("hide");
        $("#delete-forms-container").html("");
    });

    $("#delete-forms-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-form-modal-form").attr("action");
        var forms_to_delete = $("#form-table-form input[name='forms[]']:checked").map(function () {
            return this.value;
        }).get();
        form_data = {   "method" : "delete-forms",
                        "delete_ids" : forms_to_delete};

        $("#forms-selected").removeClass("hide");
        $("#delete-forms-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, form_data, function(data) {
                if (data.status == "success") {
                    $(data.form_ids).each(function(index, element) {
                        $("input[name='forms[]'][value='" + element + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs")
                    });
                    jQuery("#load-forms").html("Showing " + (parseInt(jQuery("#load-forms").html().split(" ")[1]) - data.form_ids.length) + " of " + (parseInt(jQuery("#load-forms").html().split(" ")[3]) - data.form_ids.length) + " total forms");
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
                $('#delete-form-modal').modal('hide');
            });
    });


    $('#delete-form-items-modal').on('show.bs.modal', function (e) {
        $('#delete-form-items-modal').removeClass("hide");
        $("#msgs").html("");
        $("#form-items-selected").addClass("hide");
        $("#no-form-items-selected").addClass("hide");
        $("#form-items-success").addClass("hide");
        $("#form-items-error").addClass("hide");

        var form_items_to_delete = $("#form-elements input.delete:checked").map(function () {
            return this.value;
        }).get();

        if (form_items_to_delete.length > 0) {
            $("#form-items-selected").removeClass("hide");
            $("#delete-form-items-modal-delete").removeClass("hide");
            $("#form-items-selected div.alert ul li span").html(form_items_to_delete.length);
            $("#form-items-success div.alert ul li span").html(form_items_to_delete.length);
            $("#form-items-error div.alert ul li span").html(form_items_to_delete.length);
        } else {
            $("#no-form-items-selected").removeClass("hide");
            $("#delete-form-items-modal-delete").addClass("hide");
        }
    });

    $('#delete-form-items-modal').on('hide.bs.modal', function (e) {
        $('#delete-form-items-modal').addClass("hide");
        $("#delete-form-items-container").html("");
    });

    $("#delete-form-items-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-form-items-modal-form").attr("action");


        var delete_array = new Array();
        var count = 0;
        $("input.delete").each(function(i, v) {
            if ($(v).is(":checked")) {
                delete_array[count] = $(v).val();
                count++;
            }
        });

        $.ajax({
            url: url,
            data: {
                "method" : "delete-form-elements",
                "delete_array" : delete_array
            },
            type: "POST",
            success: function(data) {
                $("#form-items-selected").addClass("hide");
                $("#delete-form-items-modal-delete").addClass("hide");
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $(jsonResponse.data).each(function(i, v) {
                        $(".form-item[data-afelement-id=" + v + "]").remove();
                    });
                    if ($("#form-items").children().length <= 0) {
                        $(".visible-when-form-populated").addClass("hide");
                        $("#form-items").html(
                            "<div class='no-items-attached-message'>" +
                                assessment_forms_localization.message_there_are_no_items_attached +
                            "</div>"
                        );
                    }
                    $("#form-items-success").removeClass("hide");
                } else {
                    $("#form-items-error").removeClass("hide");
                }
            }
        });
    });
});

function get_forms () {
    if (jQuery("#search-targets-form").length > 0) {
        var filters = jQuery("#search-targets-form").serialize();
    }

    var search_term = jQuery("#form-search").val();
    var forms = jQuery.ajax(
        {
            url: "?section=api-forms",
            data: "method=get-forms&search_term=" + search_term +
                  "&limit=" + form_limit +
                  "&item_id=" + referrer_item_id +
                  "&rubric_id=" + referrer_rubric_id +
                  "&offset=" + form_offset +
                  (typeof filters !== "undefined" ? "&" + filters : ""),

            type: "GET",
            beforeSend: function () {

                if (jQuery("#assessments-no-results").length) {
                    jQuery("#assessments-no-results").remove();
                }

                if (show_loading_message) {
                    jQuery("#assessment-forms-loading").removeClass("hide");
                    jQuery("#forms-table").addClass("hide");
                    jQuery("#load-forms").addClass("hide");
                    jQuery("#forms-table tbody").empty();
                } else {
                    jQuery("#load-forms").addClass("loading");
                }
            }
        }
    );

    jQuery.when(forms).done(function (data) {
        if (jQuery("#assessments-no-results").length) {
            jQuery("#assessments-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            total_forms += parseInt(jsonResponse.results);

            var set_disabled = false;
            if (total_forms >= jsonResponse.data.total_forms) {
                set_disabled = true;
                total_forms = jsonResponse.data.total_forms;
            }
            jQuery("#load-forms").html("Showing " + total_forms + " of " + jsonResponse.data.total_forms + " total forms");

            if (jsonResponse.results < form_limit) {
                jQuery("#load-forms").attr("disabled", "disabled");
            } else {
                jQuery("#load-forms").removeAttr("disabled");
            }
            if (set_disabled) {
                jQuery("#load-forms").attr("disabled", "disabled");
            }

            form_offset = (form_limit + form_offset);

            jQuery.each(jsonResponse.data.forms, function (key, form) {
                build_form_row(form);
            });

            if (show_loading_message) {
                jQuery("#assessment-forms-loading").addClass("hide");
                jQuery("#forms-table").removeClass("hide");
                jQuery("#load-forms").removeClass("hide");
            } else {
                jQuery("#load-forms").removeClass("loading");
            }

            show_loading_message = false;

        } else {
            jQuery("#assessment-forms-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(submodule_text.index.no_forms_found);
            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
            jQuery("#assessment-msgs").append(no_results_div);
        }
    });
}

function build_form_row (form) {

    var target_string = null;
    if (typeof(rubric_referrer_hash) != "undefined") {
        if (rubric_referrer_hash) {
            target_string = "_blank";
        }
    }

    // Append new element from template to the table.
    jQuery("<tr/>").loadTemplate(
        "#form-search-result-row-template", {
            tpl_form_link_id: "form_title_link_" + form.form_id,
            tpl_form_id: form.form_id,
            tpl_form_title: form.title,
            tpl_form_created: form.created_date,
            tpl_form_item_count: form.item_count,
            tpl_form_url: ENTRADA_URL + "/admin/assessments/forms?section=edit-form&form_id=" + form.form_id,
            tpl_url_target: target_string })
        .appendTo("#forms-table")
        .addClass("form-row");
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
        jQuery(item).find("i").removeClass("icon-white");
    }
}

function build_item_additional_details (item_id) {
    var details = jQuery.ajax
    (
        {
            url: "items?section=api-items",
            data: "method=get-item-details&item_id=" + item_id,
            type: "GET"
        }
    );

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
                }

                var comment_type;
                switch (jsonResponse.data.comment_type) {
                    case "optional" :
                        comment_type = assessment_forms_localization.comment_type_optional;
                        break;
                    case "mandatory" :
                        comment_type = assessment_forms_localization.comment_type_mandatory;
                        break;
                    case "flagged" :
                        comment_type = assessment_forms_localization.comment_type_mandatory_flagged;
                        break;
                    case "disabled" :
                    default :
                        comment_type = assessment_forms_localization.comment_type_disabled;
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

// Assessment JavaScript follows, copied over to support comments and curriculum tags functionality

jQuery(document).ready(function ($) {
    $("#assessment-form input[name^=\"item-\"]").on("change", function () {
        save_responses();
    });

    $("#assessment-form").on("change", "input[name^=\"objective-\"]", function () {
        save_responses();
    });

    $("#assessment-form select[name^=\"item-\"]").on("change", function () {
        save_responses();
    });

    $("#assessment-form input[name^=\"rubric-item-\"]").on("change", function () {
        save_responses();
    });

    $("#assessment-form textarea[name^=\"item-\"]").on("blur", function () {
        save_responses();
    });

    $("#target-search-input").on("click", function (e) {
        e.stopPropagation();
    });

    $(".datepicker").datepicker({
        "dateFormat": "yy-mm-dd"
    });

    $(".datepicker-icon").on("click", function () {
        if (!$(this).prev("input").is(":disabled")) {
            $(this).prev("input").focus();
        }
    });

    $("#form-preview-dialog input[name^=\"item-\"]").on("change", function () {
        var comment_type = $(this).closest("div").data("comment-type");
        if (comment_type == "flagged") {
            var item_name = "item-" + $(this).data("item-id");
            var flag_selected = false;
            // For each input for the item, check to see if any flagged response is selected.
            // If so, display a mandatory comments block.
            $("#form-preview-dialog input[name^=\"" + item_name + "\"]").each(function () {
                if ($(this).is(":checked") || $(this).is(":selected")) {
                    var flagged = $(this).data("response-flagged");
                    if (typeof flagged !== typeof undefined && flagged !== false) {
                        flag_selected = true;
                    }
                }
            });
            toggle_comments(item_name, flag_selected);
        }
    });

    $("#form-preview-dialog select[name^=\"item-\"]").on("change", function () {
        var comment_type = $(this).closest("div").data("comment-type");
        if (comment_type == "flagged") {
            var item_name = "item-" + $(this).data("item-id");
            var flag_selected = false;
            // For each selected option, check to see if they are flagged.
            // If so, display a mandatory comments block.
            $(this).children("option:selected").each(function () {
                var flagged = $(this).data("response-flagged");
                if (typeof flagged !== typeof undefined && flagged !== false) {
                    flag_selected = true;
                }
            });
            toggle_comments(item_name, flag_selected);
        }
    });

    $("#form-preview-dialog input[name^=\"rubric-item-\"]").on("change", function () {
        var comment_type = $(this).closest("tbody").data("comment-type");
        console.log(comment_type);
        if (comment_type == "flagged") {
            var item_id = $(this).data("item-id");
            var rubric_id = $(this).closest(".rubric-container").closest("div").data("item-id");
            var item_name = "rubric-item-" + rubric_id + "-" + item_id;
            var flag_selected = false;
            // For each input for the item, check to see if any flagged response is selected.
            // If so, display a mandatory comments block.
            $("#form-preview-dialog input[name^=\"" + item_name + "\"]").each(function () {
                if ($(this).is(":checked") || $(this).is(":selected")) {
                    var flagged = $(this).data("response-flagged");
                    if (typeof flagged !== typeof undefined && flagged !== false) {
                        flag_selected = true;
                    }
                }
            });
            toggle_comments(item_name, flag_selected);
        }
    });

    function toggle_comments(item_name, flag_selected) {
        if (flag_selected == true) {
            $("#form-preview-dialog #" + item_name + "-comments-header").removeClass("hide");
            $("#form-preview-dialog #" + item_name + "-comments-block").removeClass("hide");
        } else {
            $("#form-preview-dialog #" + item_name + "-comments-header").addClass("hide");
            $("#form-preview-dialog #" + item_name + "-comments-block").addClass("hide");
        }
    }

    $(".item-container").on("click", ".collapse-objective-btn", function (e) {
        var anchor = $(this);
        var objective_id = anchor.attr("data-objective-id");
        var afelement_id = anchor.attr("data-afelement-id");
        var indent = parseInt(anchor.parent().css("padding-left")) - 14;

        var objective_request = $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: "method=get-parent-objectives&objective_id=" + objective_id,
            type: "GET",
            beforeSend: function () {
                anchor.find(".assessment-objective-list-spinner").removeClass("hide");
                anchor.find(".ellipsis").addClass("hide");
            },
            complete: function () {
                anchor.find(".assessment-objective-list-spinner").addClass("hide");
                anchor.find(".ellipsis").removeClass("hide");
            }
        });

        $.when(objective_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {

                if ($(".fieldnote-item-warning-" + afelement_id).length > 0) {
                    $(".fieldnote-item-warning-" + afelement_id).remove();
                }

                anchor.parent().parent().attr("data-indent", indent);
                $("#item-fieldnote-container-" + afelement_id).remove();

                if (jsonResponse.data.objectives.length > 0) {
                    var selected_objectives = $(".collapse-objective-" + afelement_id);

                    $.each(selected_objectives, function (i, objective_item) {
                        var sibling_objective_id = parseInt($(objective_item).attr("data-objective-id"));

                        if (sibling_objective_id  >= objective_id) {
                            $(objective_item).remove();
                        }
                    });

                    var selected_objective_inputs = $(".afelement-objective-" + afelement_id);

                    $.each(selected_objective_inputs, function (i, objective_input) {
                        var input_objective_id = parseInt($(objective_input).val());

                        if (input_objective_id  >= objective_id) {
                            $(objective_input).remove();
                        }
                    });
                    display_objectives(jsonResponse.data.objectives, afelement_id);
                } else {
                    alert("Bottom level");
                }
            }
        });

        $("#objective-list-" + afelement_id).empty();
        e.preventDefault();
    });

    $(".item-container").on("click", ".expand-objective-btn", function (e) {
        var anchor = $(this);
        var objective_id = anchor.attr("data-objective-id");
        var afelement_id = anchor.attr("data-afelement-id");
        var indent = parseInt($("#selected-objective-list-" + afelement_id).attr("data-indent")) + 14;

        var objective_request = $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: "method=get-objectives&objective_id=" + objective_id + "&afelement_id=" + afelement_id,
            type: "GET",
            beforeSend: function () {
                anchor.find(".assessment-objective-list-spinner").removeClass("hide");
                anchor.find(".plus-sign").addClass("hide");
            },
            complete: function () {
                anchor.find(".assessment-objective-list-spinner").addClass("hide");
                anchor.find(".plus-sign").removeClass("hide");
            }
        });

        $.when(objective_request).done(function (data) {
            var jsonResponse = JSON.parse(data);

            if (jsonResponse.status === "success") {
                $("#selected-objective-list-" + afelement_id).attr("data-indent", indent);
                build_objectives_lists(afelement_id, jsonResponse.data);
                build_objective_input(afelement_id, objective_id);
            }
        });

        e.preventDefault();
    });

    function build_objectives_lists(afelement_id, objective_data) {
        if ($("#selected-objective-list-" + afelement_id).length == 0) {
            var selected_objective_list = $(document.createElement("ul")).attr({id: "selected-objective-list-" + afelement_id, "data-indent": 0}).addClass("assessment-objective-list selected-objective-list");
            $("#objective-list-" + afelement_id).before(selected_objective_list);
        } else {
            var selected_objective_list = $("#selected-objective-list-" + afelement_id);
        }

        var indent = parseInt(selected_objective_list.attr("data-indent"));
        var selected_objective_item     = $(document.createElement("li")).attr({"data-objective-name": objective_data.objective_parent.objective_parent_name, "data-objective-id": objective_data.objective_parent.objective_parent_id}).addClass("collapse-objective-" + afelement_id).css("padding-left", indent);
        var selected_objective_a        = $(document.createElement("a")).attr({href: "#", "data-afelement-id": afelement_id, "data-objective-name": objective_data.objective_parent.objective_parent_name, "data-objective-id": objective_data.objective_parent.objective_parent_id}).addClass("collapse-objective-btn");
        var selected_spinner_span       = $(document.createElement("span")).addClass("assessment-objective-list-spinner hide").html("&nbsp;");
        var selected_collapse_span      = $(document.createElement("span")).addClass("ellipsis").html("&bull;&bull;&bull;");
        var selected_objective_span     = $(document.createElement("span")).addClass("assessment-objective-name").html(objective_data.objective_parent.objective_parent_name);

        selected_objective_a.append(selected_spinner_span).append(selected_collapse_span).append(selected_objective_span);
        selected_objective_item.append(selected_objective_a);
        selected_objective_list.append(selected_objective_item);

        if (objective_data.hasOwnProperty("objectives")) {
            display_objectives(objective_data.objectives, afelement_id);
        } else {
            $("#objective-list-" + afelement_id).empty();
            var item_request = $.ajax({
                url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
                data: "method=get-competency-items&objective_id=" + objective_data.objective_parent.objective_parent_id,
                type: "GET"
            });

            $.when(item_request).done(function (data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status === "success") {
                    var item_container = $(document.createElement("div")).attr({id: "item-fieldnote-container-" + afelement_id}).addClass("item-fieldnote-container");
                    var item_text_heading = $(document.createElement("h3")).html(jsonResponse.data.item_text);
                    var fieldnote_response = $(document.createElement("div")).addClass("fieldnote-responses-container");
                    item_container.append(item_text_heading);

                    if (jsonResponse.data.hasOwnProperty("responses")) {
                        $.each(jsonResponse.data.responses, function (i, response) {
                            var response_container = $(document.createElement("div")).addClass("fieldnote-response-container");
                            var descriptor_label = $(document.createElement("label")).addClass("radio");
                            var descriptor_input = $(document.createElement("input")).attr({type: "radio", value: response.iresponse_id, name: "objective-" + objective_data.afelement_objective});

                            descriptor_label.append(descriptor_input).append(response.descriptor);
                            response_container.append(descriptor_label);
                            response_container.append(response.text);
                            fieldnote_response.append(response_container);
                        });
                    }

                    item_container.append(fieldnote_response);

                    $("#selected-objective-list-" + afelement_id).after(item_container);
                } else {
                    if ($(".fieldnote-item-warning-" + afelement_id).length === 0) {
                        var notice_div = $(document.createElement("div")).addClass("well").html("No field note items have been attached to this objective").addClass("fieldnote-item-warning-" + afelement_id);
                        $("#selected-objective-list-" + afelement_id).after(notice_div);
                    }
                }
            });
        }
    }

    function build_objective_input (afelement_id, objective_id) {
        var objective_input = $(document.createElement("input")).attr({type: "hidden", name: "afelement_objectives["+ afelement_id +"][]", value: objective_id}).addClass("afelement-objective-" + afelement_id);
        $("#assessment-form").append(objective_input);
    }

    function display_objectives (objectives, afelement_id) {
        if ($("#objective-list-" + afelement_id).length == 0) {
            var objective_list = $(document.createElement("ul")).attr({id: "objective-list-" + afelement_id}).addClass("assessment-objective-list");
        }

        $("#objective-cell-" + afelement_id).append(objective_list);

        $("#objective-list-" + afelement_id).empty();
        $.each(objectives, function (i, objective) {
            var objective_item      = $(document.createElement("li")).attr({"data-objective-name": objective.objective_name, "data-objective-id": objective.objective_id});
            var objective_a         = $(document.createElement("a")).attr({href: "#", "data-afelement-id": afelement_id, "data-objective-name": objective.objective_name, "data-objective-id": objective.objective_id}).addClass("expand-objective-btn");
            var spinner_span        = $(document.createElement("span")).addClass("assessment-objective-list-spinner hide").html("&nbsp;");
            var collapse_span       = $(document.createElement("span")).addClass("plus-sign").html("+");
            var objective_span      = $(document.createElement("span")).addClass("assessment-objective-name").html(objective.objective_name);

            objective_a.append(spinner_span).append(collapse_span).append(objective_span);
            objective_item.append(objective_a);
            $("#objective-list-" + afelement_id).append(objective_item);
        });
    }

});
