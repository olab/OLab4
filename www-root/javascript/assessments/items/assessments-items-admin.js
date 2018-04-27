jQuery(document).ready(function ($) {

    $("a.disabled").on("click", function(e){
        e.preventDefault();
    });

    var item_type_controls = $("#item-type-controls");
    var min_responses = 2;
    var max_responses = 10;
    var response_item_ordinal = 1;

    $(".add-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($("#response-table tr").length);
        if (max_responses >= current_response) {
            build_response_row();
        }
    });

    $(".remove-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($("#response-table tr").length);
        if (min_responses < current_response-1) {
            remove_response_row();
        }
    });

    $(".add-field-note-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($(".field-note-response").length);
        if (max_responses > current_response) {
            build_field_note_controls();
        }

    });

    $(".remove-field-note-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($(".field-note-response").length);
        if (min_responses < current_response) {
            remove_field_note_response_row();
        }
    });

    if ($("#allow-comments").is(":checked")) {
        if (!$("input[name='comment_type']:checked").val()) {
            $("#optional-comments").prop("checked", true);
        }
        $("#comments-type-section").show();
    } else {
        $("#comments-type-section").hide();
    }

    $("#allow-comments").on("change", function (e) {
        if ($("#allow-comments").is(":checked")) {
            $("#comments-type-section").show();
            if (!$("input[name='comment_type']:checked").val()) {
                $("#optional-comments").prop("checked", true);
            }
        } else {
            $("#comments-type-section").hide();
        }
    });

    $("#copy-item").on("click", function (e) {

        var item_id = $(this).attr("data-item-id");
        var new_item_title = $("#new-item-title").val();

        // Make a copy request to the Item API
        var copy_item_request = $.ajax({
            url: API_URL,
            data: "?section=api-items&method=copy-item&item_id=" + item_id + "&new_item_title=" + new_item_title,
            type: "POST",
            beforeSend: function () {
                $("#copy-item").prop("disabled", true);
            },
            error: function () {
                display_error(assessment_item_localization.error_unable_to_copy, "#copy-item-msgs");
            }
        });

        $.when(copy_item_request).done(function (response) {
            var jsonResponse = safeParseJson(response, assessment_item_localization.error_default_json);
            if (jsonResponse.status === "success") {
                // Navigate to the new item's edit page
                window.location = jsonResponse.url;
            } else {
                // Display any errors returned from the API in the modal and re-enable the button
                display_error(jsonResponse.data, "#copy-item-msgs");
                $("#copy-item").removeProp("disabled");
            }
        });

        e.preventDefault(); 
    });

    $("#copy-attach-item").on("click", function (e) {

        var item_id = $(this).attr("data-item-id");
        var new_item_title = $("#new-copy-attached-item-title").val();
        var form_ref = $("#item-form input[name='fref']").val();
        var rubric_ref = $("#item-form input[name='rref']").val();

        // Make a copy request to the Item API
        var copy_item_request = $.ajax({
            url: API_URL,
            data: "?section=api-items&method=copy-attach-item&item_id=" + item_id + "&new_item_title=" + new_item_title + "&fref=" + form_ref+ "&rref=" + rubric_ref,
            type: "POST",
            beforeSend: function () {
                $("#copy-attach-item").prop("disabled", true);
            },
            error: function () {
                display_error(assessment_item_localization.error_unable_to_copy, "#copy-attach-item-msgs");
            }
        });

        $.when(copy_item_request).done(function (response) {
            var jsonResponse = safeParseJson(response, assessment_item_localization.error_default_json);
            if (jsonResponse.status === "success") {
                // Navigate to the new item's edit page
                window.location = jsonResponse.url;
            } else {
                // Display any errors returned from the API in the modal and re-enable the button
                display_error(jsonResponse.data, "#copy-attach-item-msgs");
                $("#copy-item").removeProp("disabled");
            }
        });

        e.preventDefault();
    });

    var modify_mandatory = false;

    /**
     * When item type changes, we set visibility of form elements and set a default for whether or not the item is mandatory. On page load, we trigger this once.
     */
    $("#item-type").on("change", function () {
        // The first item-type change will be the trigger. On this initial trigger, we do not want to modify whether or not the item is mandatory as this may have already been set by the user.
        updateControls(modify_mandatory);
        modify_mandatory = true;
    }).trigger("change");

    function updateControls(modify_mandatory) {
        var selected_item_type = $("#item-type").find(":selected").data("type-name");
        switch (selected_item_type) {
            case "horizontal_multiple_choice_single" :
            case "vertical_multiple_choice_single" :
            case "selectbox_single" :
            case "horizontal_multiple_choice_multiple" :
            case "vertical_multiple_choice_multiple" :
            case "selectbox_multiple" :
            case "scale" :
                $(".item-response-label").removeClass("form-nrequired");
                $(".item-response-label").addClass("form-required");
                $("#field-note-response").addClass("hide");
                $("#response-section").removeClass("hide");
                $("#objective-options").removeClass("hide");
                $(".comments-options").removeClass("hide");
                $(".default-response-options").removeClass("hide");
                $(".item-rating-scale-control-group").removeClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", true);
                }
                break;
            case "free_text" :
            case "numeric" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").removeClass("hide");
                $(".comments-options").addClass("hide");
                $(".default-response-options").addClass("hide");
                $(".item-rating-scale-control-group").addClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", false);
                }
                break;
            case "date" :
            case "user" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").addClass("hide");
                $(".comments-options").addClass("hide");
                $(".default-response-options").addClass("hide");
                $(".item-rating-scale-control-group").addClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", false);
                }
                break;
            case "fieldnote" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").addClass("hide");
                $(".comments-options").addClass("hide");
                $(".default-response-options").addClass("hide");
                $(".item-rating-scale-control-group").addClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", true);
                }
                break;
            case "rubric_line" :
                $(".item-response-label").removeClass("form-required");
                $(".item-response-label").addClass("form-nrequired");
                $("#field-note-response").addClass("hide");
                $("#response-section").removeClass("hide");
                $("#objective-options").removeClass("hide");
                $(".comments-options").removeClass("hide");
                $(".default-response-options").removeClass("hide");
                $(".item-rating-scale-control-group").removeClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", true);
                }
                break;
        }

        item_type_controls.empty();

        switch (selected_item_type) {
            case "horizontal_multiple_choice_single" :
            case "horizontal_multiple_choice_multiple" :
            case "vertical_multiple_choice_single" :
            case "vertical_multiple_choice_multiple" :
            case "rubric_line" :
            case "scale" :
                min_responses = 2;
                max_responses = 10;
                break;
            case "selectbox_single" :
            case "selectbox_multiple" :
                min_responses = 2;
                max_responses = 50;
                break;
            case "fieldnote" :
                min_responses = 3;
                max_responses = 6;
                if (!$(".field-note-response").length > 0) {
                    for (var i = 1; i <= min_responses; i++) {
                        //build_field_note_controls();
                    }
                }
                $("#field-note-response").removeClass("hide");
                break;
        }
        remove_excess_rows();
        build_missing_rows();
        set_item_response_selector_properties();
        update_response_label_ordering();
    }

    /**
     * For new items, we make sure there are at least the minimum number of items showing.
     */
    function build_missing_rows() {
        // If there are no rows, and there should be, add them
        var current_response = parseInt($("#response-table tr").length);
        var prev_response = 0;
        while (min_responses >= current_response) {
            build_response_row();
            prev_response = current_response;
            current_response = parseInt($("#response-table tr").length);
            if (prev_response == current_response) {
                return; // For some reason, the table row isn't being added and we're stuck in a loop, so let's get out.
            }
        }
    }

    function text_into_span(string, insertion) {
        return string.replace('<span>%s</span>', '<span>'+insertion+'</span>');
    }

    /**
     * Build an item response edit row from a template.
     * Enables sorting, advanced search and keeps track of its position on the page.
     */
    function build_response_row () {
        var response_number = response_item_ordinal;
        var template_data = {
            tpl_response_number: response_number,
            tpl_response_label: text_into_span(assessment_item_localization.response_item_template, response_number),
            tpl_response_element_id: "item_response_" + response_number,
            tpl_item_responses_name: "item_responses[" + response_number + "]",
            tpl_ardescriptor_name: "ardescriptor_id[" + response_number + "]",
            tpl_descriptor_id: "descriptor-" + response_number,
            tpl_flag_response: "flag_response[" + response_number + "]",
            tpl_flag_id: "flag-" + response_number,
            tpl_default_response: "default_response",
            tpl_default_response_id: "default-response-" + response_number
        };

        // Append new element from template to the table.
        $("<tr/>").loadTemplate("#response-row-template", template_data)
            .appendTo("#response-table")
            .addClass("response-row response-row-"+response_number)
            .data("ordinal", response_number);

        // Enable sorting on the new item
        enable_responses_sortable();

        // Attach advancedSearch to the new item
        enable_descriptor_advanced_search(response_number);
        enable_reponse_flag_advanced_search(response_number);

        // Each item has a unique ordinal value (not related to the actual response number). Keep it updated here.
        response_item_ordinal++;

        // Update the labels (so that items in their respective positions are clearly labeled as such)
        update_response_label_ordering();

        // Set properties of the new element (button handlers)
        set_item_response_selector_properties();

        // Show/Hide default selection column based on the checkbox state
        toggle_default_column();

        // Clear any error/success messages for responses
        clear_response_error();
    }

    /**
     * Set the response_item_ordinal value to 1 greater than the highest one of the initial page.
     */
    function set_response_item_ordinal() {
        // Find the highest and set our initial response_item_ordinal variable value
        var highest = 0;
        $(".response-row").each(function(i,v){
            if ($(v).data("ordinal") > highest) {
                highest = $(v).data("ordinal");
            };
        });
        response_item_ordinal = highest + 1;
    }

    /**
     * For a given row, enable/init the advanced search.
     *
     * @param response_number
     */
    function enable_descriptor_advanced_search(response_number) {
        // If it hasn't already been set, updated the advanced search
        if (typeof jQuery("#descriptor-" + response_number).data("settings") == "undefined") {
            $("#descriptor-" + response_number).advancedSearch({
                api_url: ENTRADA_URL + "/admin/assessments/items?section=api-items",
                resource_url: ENTRADA_URL,
                filters: {},
                control_class: "descriptor-" + response_number,
                no_results_text: "",
                parent_form: $("#item-form"),
                width: 275,
                modal: false
            });

            // We must declare the filter after the object has been created to allow us to use a dynamic key with a variable in the name.
            var descriptor_settings = jQuery("#descriptor-" + response_number).data("settings");
            if (descriptor_settings) {
                descriptor_settings.filters["response_category_" + response_number] = {
                    label: "",
                    data_source: "get-response-descriptors",
                    mode: "radio",
                    selector_control_name: "ardescriptor_id[" + response_number + "]",
                    search_mode: true
                }
            }
        }
    }

    function enable_reponse_flag_advanced_search(response_number) {
        // If it hasn't already been set, updated the advanced search
        if (typeof jQuery("#flag-" + response_number).data("settings") == "undefined") {
            $("#flag-" + response_number).advancedSearch({
                api_url: ENTRADA_URL + "/admin/assessments/items?section=api-items",
                resource_url: ENTRADA_URL,
                filters: {},
                control_class: "flag-" + response_number,
                no_results_text: "",
                parent_form: $("#item-form"),
                width: 275,
                modal: false
            });

            // We must declare the filter after the object has been created to allow us to use a dynamic key with a variable in the name.
            var descriptor_settings = jQuery("#flag-" + response_number).data("settings");
            if (descriptor_settings) {
                descriptor_settings.filters["response_category_" + response_number] = {
                    label: "",
                    data_source: "get-response-flags",
                    mode: "radio",
                    selector_control_name: "flag_response[" + response_number + "]",
                    search_mode: false
                }
            }
        }
    }

    /**
     * Clear the selected custom flags, this is mainly called when swtiching
     * between rating scales, to avoid previous flag selection to interfere
     * with the new response set.
     */
    function clear_advancedsearch_flags() {
        $("input[name^='flag_response'][type='hidden']").remove();
    }

    function remove_response_row () {
        $("#response-table tr:last").remove();
        toggle_default_column();
    }

    function remove_field_note_response_row () {
        $(".field-note-response:last").remove();
    }

    function build_field_note_controls () {
        var total_field_note_responses  = parseInt($(".field-note-response").length) + 1;
        var response_descriptor_type    = "field-note-" + total_field_note_responses;
        var response_container          = $(document.createElement("div")).attr({id: "field-note-" + total_field_note_responses}).addClass("field-note-response");
        var category_control_group      = $(document.createElement("div")).addClass("control-group");
        var category_label              = $(document.createElement("label")).addClass("control-label").attr({"for": "descriptor-" + response_descriptor_type}).html("Response Category");
        var category_controls           = $(document.createElement("div")).addClass("controls");
        var category_select             = $(document.createElement("select")).attr({name: "field_note_ardescriptor_id["+ total_field_note_responses +"]", id: "descriptor-" + response_descriptor_type}).addClass("field-note-category");
        var category_default_option     = $(document.createElement("option")).attr({value: "0"}).html("-- Select Descriptor --");

        category_select.append(category_default_option);
        category_controls.append(category_select);
        category_control_group.append(category_label).append(category_controls);

        //get_response_descriptors(response_descriptor_type);

        var flag_response_label     = $(document.createElement("label")).addClass("checkbox field-note-flag").attr({"for": "flag-field-note-response-" + total_field_note_responses});
        var flag_response_input     = $(document.createElement("input")).attr({id: "flag-field-note-response-" + total_field_note_responses, type: "checkbox", name: "field_note_flag_response["+ total_field_note_responses +"]", value: "1"});

        flag_response_label.append(flag_response_input).append("Flag this Response");
        category_controls.append(flag_response_label);

        var response_control_group  = $(document.createElement("div")).addClass("control-group");
        var response_controls       = $(document.createElement("div")).addClass("controls");
        var response_label          = $(document.createElement("label")).addClass("control-label form-required").html("Response Text");
        var response_textarea       = $(document.createElement("textarea")).attr({name: "field_note_item_responses["+ total_field_note_responses +"]", id: "field-note-response-" + total_field_note_responses});

        response_controls.append(response_textarea);
        response_control_group.append(response_label).append(response_controls);
        response_container.append(category_control_group).append(response_control_group);

        $("#field-note-response").append(response_container);
        CKEDITOR.replace(response_textarea.attr("id"));
    }

    function remove_excess_rows () {
        var total_responses = parseInt($("#response-table tr").length);
        if (total_responses > max_responses) {
            $("#response-table tbody tr").slice(max_responses, total_responses).remove();
        }
        toggle_default_column();
    }

    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);

        $.ajax({
            url: API_URL,
            data: "method=remove-permission&aiauthor_id=" + remove_permission_btn.data("aiauthor-id"),
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

    /**
     * Init Code
     */

    /**
     * Set extra properties/handlers for a response item row.
     */
    function set_item_response_selector_properties() {
        $("td.move-item-response").on("click", function(e){
            e.preventDefault();
        });

        $("td.delete-item-response").on("click", function(e){
            var ordinal = $(this).data("related-response-ordinal");
            var related_item_id = "#item_response_" + ordinal;
            var item_response_text = $(related_item_id).val();
            if (item_response_text) {
                $("#delete-item-response-text").html("<ul><li>" + item_response_text + "</li></ul>");
            }
            $("#delete-item-response-delete-btn").data("removal-ordinal", ordinal);
            clear_response_error();
            $("#delete-item-response-modal").modal("show");
        });
    }

    /**
     * On delete, remove the row that was clicked, and dismiss the modal.
     */
    $("#delete-item-response-delete-btn").on("click", function(e){
        e.preventDefault();
        var row_to_remove = ".response-row-" + $(this).data("removal-ordinal");
        $(row_to_remove).remove();
        display_success([assessment_item_localization.success_removed_item], "#item-removal-success-box");
        update_response_label_ordering();
        $("#delete-item-response-modal").modal("hide");
        toggle_default_column();
    });

    function clear_response_error() {
        $("#item-removal-success-box").html("");
    }

    /**
     * Enable sorting for item responses
     */
    function enable_responses_sortable() {
        var selected_default;

        $(".sortable-items").sortable({
            handle: "td.move-item-response",
            placeholder: "success response-row",
            helper: "clone",
            axis: 'y',
            containment: "document",
            disable: true,
            start: function (event, ui) {
                clear_response_error();
                // A bug in jQuery UI resets the selected radio, taking note of what is
                // selected at the start to restore it when the drop happen end
                selected_default = jQuery.find("input[name='default_response']:checked");
                if (selected_default.length) {
                    selected_default = selected_default[0].value;
                } else {
                    selected_default = null;
                }

                var placeholder_item = ui.item.html();
                ui.placeholder.html(placeholder_item);
                //ui.helper.hide();
            },
            stop: function (event, ui) {
                update_response_label_ordering();
                // Restore the selected default value if any and re-index the values to reflect the new order
                if (selected_default) {
                    $("#default-response-" + selected_default).prop("checked", true);
                }
                $("input[name='default_response']").each(function(index) {
                    $(this).val(index + 1);
                });
            }
        });
    }

    /**
     * Update the label for each response (e.g. The first item will be "Response 1", and the second "Response 2" etc)
     */
    function update_response_label_ordering() {
        $(".sortable-items .item-response-label span").each(function(i,v){
            $(v).html(i+1);
        });
    }

    // Turn on sortability
    enable_responses_sortable();

    // Set the initial response item ordinal value
    set_response_item_ordinal();

    /**
     * Rating scale functions
     */

    /**
     * Build a scale response edit row from a template.
     * Enables sorting, advanced search and keeps track of its position on the page.
     */
    function build_scale_response_row (response_number, response) {
        var template_data = {
            tpl_response_number: response_number,
            tpl_response_label: text_into_span(assessment_item_localization.response_item_template, response_number),
            tpl_response_element_id: "item_response_" + response_number,
            tpl_item_responses_name: "item_responses[" + response_number + "]",
            tpl_ardescriptor_name: "ardescriptor_id[" + response_number + "]",
            tpl_ardescriptor_value: response.descriptor,
            tpl_ardescriptor_id_value: response.ardescriptor_id,
            tpl_selected_ardescriptor_name: "selected_ardescriptor_id[" + response_number + "]",
            tpl_selected_ardescriptor_id: response.ardescriptor_id,
            tpl_descriptor_id: "descriptor-" + response_number,
            tpl_flag_response: "flag_response[" + response_number + "]",
            tpl_flag_id: "flag-" + response_number,
            tpl_default_response: "default_response"
        };

        // Append new element from template to the table.
        $("<tr/>").loadTemplate("#itemscale-response-row-template", template_data)
            .appendTo("#response-table")
            .addClass("response-row response-row-"+response_number)
            .data("ordinal", response_number);

        // Each item has a unique ordinal value (not related to the actual response number). Keep it updated here.
        response_item_ordinal++;

        // Update the labels (so that items in their respective positions are clearly labeled as such)
        update_response_label_ordering();
        enable_reponse_flag_advanced_search(response_number);

        // Clear any error/success messages for responses
        clear_response_error();

        // Toggle the default selection column
        toggle_default_column();
    }

    $("#item-rating-scale-btn").on("click", function (e) {
        e.preventDefault();
    });

    $("#item-rating-scale-btn").on("change", function(e) {
        e.preventDefault();
        if ($(this).hasClass("disabled")) {
            e.preventDefault();
            return;
        }
        var scale_id = $("#item-form input[name='rating_scale_id']").val();
        $("#response-table tr.response-row").remove();

        if (scale_id == 0 || scale_id == null) {
            $(".btn.add-response").show();
            $(".btn.remove-response").show();
            response_item_ordinal = 1;
            build_missing_rows();
            set_item_response_selector_properties();
            update_response_label_ordering();
            $(".items-header-flex-column").removeClass("hide");
        } else {
            response_item_ordinal = 1;
            $(".btn.add-response").hide();
            $(".btn.remove-response").hide();
            $(".items-header-flex-column").addClass("hide");
            $.ajax({
                url: SCALE_API_URL,
                type: "GET",
                data: "method=get-scale-responses&rating_scale_id=" + scale_id,
                dataType: "json",
                success: function(response) {
                    if (response.status == "success") {
                        jQuery.each(response.data, function(num, response) {
                            build_scale_response_row(num + 1, response);
                        });
                        set_response_item_ordinal();
                    }
                }
            })
        }
        clear_advancedsearch_flags();
    });

    function toggle_default_column() {
        if ($("#allow-default").prop("checked")) {
            $(".default_selection_column").removeClass("hide");
            if (! $("input:radio[name='default_response']").is(":checked")) {
                $("input:radio[name='default_response']:first").attr('checked', true);
            }
        } else {
            $(".default_selection_column").addClass("hide");
        }
    }

    $("#allow-default").on("change", function() {
        toggle_default_column();
    });

    /**
     * Initial objective set controls are built based on the selected course.
     */
    $("#choose-objective-course-btn").on("change", function () {
        $("#objectives-selector-controls").empty();
        build_objective_set_selector_controls();
    });

    /**
     * Build objective set selector controls.
     */
    function build_objective_set_selector_controls() {
        var course_id = $("input[name=\"course_id\"]").val();
        clear_objective_error();

        // API call to fetch first level of objectives for the course.
        $.ajax({
            url: ENTRADA_URL + "/admin/assessments/forms?section=api-forms",
            data: {
                "method": "get-objective-sets-by-course",
                "course_id": course_id
            },
            type: "GET",
            success: function (results) {
                var jsonResponse = JSON.parse(results);
                if (jsonResponse.status === "success") {
                    var markup = false;
                    var template_data = false;

                    // Build an objective set selector and append it to the container.
                    jsonResponse.data.each(function (objective) {
                        var processed_title = (objective.objective_code
                            ? objective.objective_code + ($(objective.objective_name).length > 0 ? ": " + objective.objective_name : "")
                            : objective.objective_name
                        );

                        template_data = {
                            tpl_objective_id:           objective.objective_id,
                            tpl_objective_node_id:      objective.node_id,
                            tpl_objective_name:         "objectives[" + objective.objective_id + "]",
                            tpl_objective_code:         objective.objective_code,
                            tpl_objective_title:        processed_title,
                            tpl_objective_description:  "",//objective.objective_description,
                            tpl_objective_depth:        objective.depth,
                            tpl_objective_course_id:    objective.course_id
                        };

                        // New objective set built from template.
                        markup = $("<div/>").loadTemplate("#objective-set-template", template_data)
                            .addClass("objective-set-container")
                            .data("id", objective.objective_id)
                            .data("depth", objective.depth)
                            .data("node-id", objective.node_id)
                            .data("course-id", objective.course_id)
                            .attr("id", "objective_" + objective.objective_id);

                        // If there are further objectives "below" this set, apply classes for styling.
                        if (objective.has_children) {
                            markup.find("div.objective-select").addClass("has-child-objectives").addClass("child-objectives-hidden");
                        }

                        // The objective code span should be hidden if this objective has no code set.
                        if (!objective.objective_code) {
                            markup.find("span.objective-code").addClass("hide");
                        }

                        $("#objectives-selector-controls").append(markup);
                    });
                } else {
                    $("#objectives-selector-error").removeClass("hide");
                    display_error(jsonResponse.msg, "#objectives-selector-error");
                }
            }
        });
    }

    /**
     * Handles when an objective or objective set is clicked. This only applies to the immediate
     * .objective-select clicked rather than propagating through all handlers.
     */
    $(document).on("click", ".objective-select", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        clear_objective_error();
        var control = $(this);
        var control_objective_container = control.find("div.objective-container");

        /**
         * If the objective set already has children, it has previously been clicked and it's data fetched.
         * Rather than fetching again, the child sets and parent class should be toggled.
         */
        if (control_objective_container.length > 0) {
            var currently_visible = $(control_objective_container).is(":visible");
            $(control_objective_container).slideToggle("fast");

            if (!currently_visible) {
                control.removeClass("child-objectives-hidden");
                control.addClass("child-objectives-visible");
            } else {
                control.removeClass("child-objectives-visible");
                control.addClass("child-objectives-hidden");
            }
        } else {
            /**
             * We pre-fetched the next level of objectives in the API to determine if there are children, so we
             * don't need to bother if we didn't find any.
             */
            if (control.hasClass("has-child-objectives")) {

                control.addClass("child-objectives-visible");
                control.removeClass("child-objectives-hidden");

                // There is further data to fetch. Build child objective sets from API response.
                var node_id = control.data("node-id") ? control.data("node-id") : null;
                var current_depth = control.data("depth") ? control.data("depth") : null;
                var course_id = control.data("course-id") ? control.data("course-id") : null;
                var objective_id = control.data("objective-id");

                $.ajax({
                    url: ENTRADA_URL + "/admin/assessments/forms?section=api-forms",
                    data: {
                        "method": "get-objectives-by-depth",
                        "node_id": node_id,                             // If node is present, it is CBME objective tree based.
                        "objective_id": objective_id,                   // When we are not processing a node, we use the parent/child relationship of objectives.
                        "depth": current_depth + 1,                     // Depth applies when a node is being processed. We only want to fetch the next level down the tree.
                        "course_id": course_id                          // Course only applies when fetching course specific CBME objectives.
                    },
                    type: "GET",
                    success: function (results) {

                        var jsonResponse = JSON.parse(results);
                        if (jsonResponse.status === "success") {

                            /**
                             * Breadcrumb is either parent codes (ie: D1.1 > D1.2 > D2.3) or the truncated titles (ie: Performing a... > Assessing a... > As a medic...).
                             */
                            var crumbs = [];
                            var string = "";
                            if ($(control).data("objective-code")) {
                                string = $(control).data("objective-code");
                            } else {
                                if ($(control).data("objective-title").length > 10) {
                                    string = $.trim($(control).data("objective-title")).substring(0, 10).trim(this) + "...";
                                } else {
                                    string = $(control).data("objective-title");
                                }
                            }
                            crumbs.push(string);
                            control.parents(".objective-select").each(function (i, parent) {
                                if ($(parent).data("objective-code")) {
                                    string = $(parent).data("objective-code");
                                } else {
                                    if ($(parent).data("objective-title").length > 10) {
                                        string = $.trim($(parent).data("objective-title")).substring(0, 10).trim(this) + "...";
                                    } else {
                                        string = $(parent).data("objective-title");
                                    }
                                }
                                crumbs.push(string);
                            });

                            var breadcrumbs = "";
                            crumbs.reverse().each(function (crumb_string) {
                                breadcrumbs += (breadcrumbs ? " > " : " ");
                                breadcrumbs += crumb_string;
                            });

                            var markup = false;
                            var child_list = control.find("div.child-objectives");

                            // Build an objective selector and append it after the current node.
                            jsonResponse.data.each(function (objective) {
                                markup = build_objective_selector(
                                    objective.objective_id,
                                    objective.node_id,
                                    objective.depth,
                                    objective.objective_name,
                                    objective.objective_code,
                                    "",//objective.objective_description,
                                    objective.course_id,
                                    breadcrumbs
                                );

                                // If the objective we just "found" is already mapped, it should be checked off.
                                if (parseInt(objective.node_id) > 0) {
                                    if ($(".mapped_objective_" + objective.objective_id).length > 0
                                        && $("#mapped_objective_tree_ids_" + objective.node_id).length > 0 ) {
                                        markup.find("input.objective-checkbox").prop("checked", true);
                                    }
                                } else {
                                    if ($(".mapped_objective_" + objective.objective_id).length > 0) {
                                        markup.find("input.objective-checkbox").prop("checked", true);
                                    }
                                }

                                // If there are further objectives "below" this set, apply classes for styling.
                                if (objective.has_children) {
                                    markup.find("div.objective-select").addClass("has-child-objectives").addClass("child-objectives-hidden");
                                }

                                // The objective code span should be hidden if this objective has no code set.
                                if (!objective.objective_code) {
                                    markup.find("span.objective-code").addClass("hide");
                                }

                                child_list.append(markup);
                            });
                        } else {
                            $("#objectives-selector-error").removeClass("hide");
                            display_error(jsonResponse.msg, "#objectives-selector-error");
                        }
                    }
                });
            }
        }
    });

    /**
     * Build an objective selector from the template.
     */
    function build_objective_selector(objective_id, node_id, node_depth, objective_name, objective_code, objective_description, course_id, breadcrumb) {

        var template_data = {
            tpl_objective_id:                       objective_id,
            tpl_objective_node_id:                  node_id,
            tpl_objective_name:                     "objectives[" + objective_id + "]",
            tpl_objective_code:                     objective_code,
            tpl_objective_title:                    objective_name,
            tpl_objective_description:              objective_description,
            tpl_objective_depth:                    node_depth,
            tpl_objective_course_id:                course_id,
            tpl_objective_breadcrumb:               breadcrumb
        };

        // Return new element built from template.
        return $("<div/>").loadTemplate("#objective-select-template", template_data)
            .addClass("objective-container")
            .data("id", objective_id)
            .data("depth", node_depth)
            .data("node-id", node_id)
            .data("course-id", course_id)
            .attr("id", "objective_" + objective_id);
    }

    /**
     * Handles when an objective checkbox is clicked. The objective should be added to or removed
     * from the mapped objectives. This only applies to the immediate input clicked rather than
     * propagating through all handlers.
     */
    $(document).on("click", ".objective-checkbox", function (e) {
        e.stopImmediatePropagation();
        if ($(this).prop("checked") === false) {
            unmap_objective($(this).val(), $(this).closest(".objective-selector").attr("data-node-id"));
        } else {
            var code = $(this).data("objective-code");
            var objective_title = $(this).data("objective-title");
            var objective_tree_id = $(this).closest(".objective-select").data("node-id");
            var breadcrumb = $(this).closest(".objective-selector").find(".objective-breadcrumb").html();
            var title = (code) ? code + "&nbsp;" + objective_title : objective_title;
            map_objective($(this).val(), title, $(this).data("objective-description"), objective_tree_id, breadcrumb);
        }
    });

    /**
     * Add the objective to the mapped objective list if it is not already there.
     */
    function map_objective(objective_id, objective_title, objective_description, objective_tree_id, breadcrumb) {
        var is_mapped = false;
        if (!parseInt(objective_tree_id)) {
            if ($(".mapped_objective_" + objective_id).length > 0) {
                is_mapped = true;
            }
        } else {
            if ($(".mapped_objective_" + objective_id).length > 0 && $("#mapped_objective_tree_ids_" + objective_tree_id).length > 0 ) {
                is_mapped = true;
            }
        }
        if (!is_mapped) {
            var markup = build_mapped_objective(objective_id, objective_title, objective_description, objective_tree_id, breadcrumb);

            $(".mapped_assessment_item_objectives").append(markup);
            $(".objectives-empty-notice").hide();
        }
    }

    /**
     * Build a mapped objective from the template.
     */
    function build_mapped_objective(objective_id, objective_title, objective_description, objective_tree_id, breadcrumb) {
        if (objective_tree_id > 0) {
            var template_data = {
                tpl_objective_id: objective_id,
                tpl_objective_name: "mapped_objective_ids[" + objective_id + "]",
                tpl_objective_class: "mapped-objective mapped_objective_" + objective_id,
                tpl_objective_title: objective_title,
                tpl_objective_description: objective_description,
                tpl_node_input_id: "mapped_objective_tree_ids_" + objective_tree_id,
                tpl_objective_tree_name: "mapped_objective_tree_ids_" + objective_id + "[]",
                tpl_objective_tree_id: objective_tree_id,
                tpl_objective_breadcrumb: breadcrumb,
                tpl_breadcrumb_input_name: "mapped_objective_breadcrumbs[" + objective_tree_id + "]"
            };
        } else {
            var template_data = {
                tpl_objective_id: objective_id,
                tpl_objective_name: "mapped_objective_ids[" + objective_id + "]",
                tpl_objective_class: "mapped-objective mapped_objective_" + objective_id,
                tpl_objective_title: objective_title,
                tpl_objective_breadcrumb: breadcrumb,
                tpl_objective_description: objective_description,
            };
        }

        // Return new element built from template.
        return $("<div/>").loadTemplate("#mapped-objective-template", template_data);
    }

    /**
     * Remove button unmaps the objective and unchecks the respective checkbox from the selectors.
     */
    $(document).on("click", ".remove-mapped-objective", function() {
        unmap_objective($(this).attr('data-id'), $(this).attr("data-tree-id"));
    });

    /**
     * Remove the objective from the mapped objectives list and unchecks it from the selectors.
     */
    function unmap_objective(objective_id, objective_tree_id) {

        if (parseInt(objective_tree_id) < 1 || objective_tree_id == "" || typeof(objective_tree_id)=="undefined") {
            $("input[name=\"objectives[" + objective_id + "]\"]").prop("checked", false);
            $(".mapped_objective_" + objective_id).remove();
        } else {
            $("input[name^='objectives'][value='" + objective_id + "']").each(function() {
                if ($(this).closest(".objective-selector").attr("data-node-id") == objective_tree_id) {
                    $(this).prop("checked", false);
                }
            })

            $("input[name^='mapped_objective_tree_ids_" + objective_id + "'][value='" + objective_tree_id + "']").closest(".mapped-objective").remove();
        }

        // Show the empty notice if there are now no mapped objectives.
        if ($("div.mapped-objective").length == 0) {
            $("div.objectives-empty-notice").show();
        }
    }

    function clear_objective_error() {
        $("#objectives-selector-error").html("");
    }

    var course_select_height = $("#objectives-selector .course-select").outerHeight();
    $("#objectives-selector #objectives-selector-controls").css("height", 496 - course_select_height + "px");
});

