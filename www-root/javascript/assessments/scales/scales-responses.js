var mapped = [];
var listed = [];
var ENTRADA_URL;

jQuery(document).ready(function ($) {
    var min_responses = 2;
    var max_responses = 10;

    var current_responses = $(".response-row").length;
    for (var i = 1; i <= min_responses - current_responses; i++) {
        build_response_row();
        $('#responses').val('2');
    }

    // listener for clicking ADD-RESPONSE button
    $(".add-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($(".response-row").length);
        if (max_responses > current_response) {
            build_response_row();
        }
    });

    // listener for clickind REMOVE-RESPONSE button
    $(".remove-response").on("click", function (e) {
        e.preventDefault();
        var current_response = parseInt($(".response-row").length);
        if (min_responses < current_response) {
            remove_response_row();
        }
    });

    function text_into_span(string, insertion) {
        return string.replace('<span>%s</span>', '<span>'+insertion+'</span>');
    }

    /**
     * For a given row, enable/init the advanced search.
     *
     * @param response_number
     */
    function enable_descriptor_advanced_search(response_number) {
        // If it hasn't already been set, updated the advanced search
        if (typeof jQuery("#descriptor-" + response_number).data("settings") == "undefined") {
            $("#descriptor-" + response_number).advancedSearch ({
                api_url : ENTRADA_URL + "/admin/assessments/items?section=api-items",
                filters : {
                },
                control_class: "descriptor-" + response_number,
                no_results_text: "",
                parent_form: $("#item-form"),
                width: 275,
                modal: false
            });

            // We must declare the filter after the object has been created to allow us to use a dynamic key with a variable in the name.
            var descriptor_settings = jQuery("#descriptor-" + response_number).data("settings");
            descriptor_settings.filters["response_category_" + response_number] = {
                label : "",
                data_source : "get-response-descriptors",
                mode: "radio",
                selector_control_name : "selected_ardescriptor_ids[" + response_number + "]",
                search_mode: true
            }
        }
    }

    // function to build new additional Response row
    function build_response_row () {
        var response_number = parseInt($(".response-row").length) + 1;
        var template_data = {
            tpl_response_number: response_number,
            tpl_response_label: text_into_span(assessment_item_localization.response_item_template, response_number),
            tpl_response_element_id: "item_response_" + response_number,
            tpl_item_responses_name: "item_responses[" + response_number + "]",
            tpl_ardescriptor_name: "ardescriptor_id[" + response_number + "]",
            tpl_descriptor_id: "descriptor-" + response_number,
            tpl_response_hidden_input_name: "selected_ardescriptor_ids["+ response_number +"]",
            tpl_response_hidden_input_id: "additional_item_response_" + response_number,
            tpl_response_hidden_input_label: response_number,
            tpl_response_hidden_input_value: null
        };

        // Append new element from template to the table.
        $("<tr/>").loadTemplate("#response-row-template", template_data)
            .appendTo("#response-table")
            .addClass("response-row response-row-"+response_number)
            .data("ordinal", response_number);

        // Attach advancedSearch to the new item
        enable_descriptor_advanced_search(response_number);

        // Each item has a unique ordinal value (not related to the actual response number). Keep it updated here.
        //response_item_ordinal++;
        $('#responses').val(+$('#responses').val() + 1);
    }

    // function to remove response row
    function remove_response_row () {
        $("#response-table tr:last").remove();
        $('#responses').val(+$('#responses').val() - 1);
    }
});