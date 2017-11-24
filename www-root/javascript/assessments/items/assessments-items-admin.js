var mapped = [];
var listed = [];

function addObjective(id, rownum) {
    var ids = id;

    if (jQuery('div.objectives-empty-notice').is(':visible')) {
        jQuery('div.objectives-empty-notice').hide();
    }

    var alreadyAdded = false;
    jQuery('input.objective_ids_'+rownum).each(
        function () {
            if (!ids) {
                ids = jQuery(this).val();
            } else {
                ids += ','+jQuery(this).val();
            }
            if (jQuery(this).val() == id) {
                alreadyAdded = true;
            }
        }
    );

    jQuery('#objective_ids_string_'+rownum).val(ids);

    if (!alreadyAdded) {
        var attrs = {
            type		: 'hidden',
            className	: 'objective_ids_'+rownum,
            id			: 'objective_ids_'+rownum+'_'+id,
            value		: id,
            name		:'objective_ids_'+rownum+'[]'
        };

        var newInput = new Element('input', attrs);
        $('objectives_'+rownum+'_list').insert({bottom: newInput});
    }
}

function removeObjective(id, rownum) {
    var ids = "";

    jQuery('#objective_ids_'+rownum+'_'+id).remove();

    jQuery('input.objective_ids_'+rownum).each(
        function () {
            if (!ids) {
                ids = jQuery(this).val();
            } else {
                ids += ','+jQuery(this).val();
            }
        }
    );

    if (!jQuery('div.objectives-empty-notice').is(':visible') && !ids) {
        jQuery('div.objectives-empty-notice').show();
    }

    jQuery('#objective_ids_string_'+rownum).val(ids);
}

function unmapObjective(id,list,importance){
    var key = jQuery.inArray(id,mapped);
    if(key != -1){
        mapped.splice(key,1);
    }
    var lkey = jQuery.inArray(id,listed);
    if(lkey === -1){
        importance = 'checked';
    }

    jQuery("#"+importance+"_objectives_select option[value='"+id+"']").remove();
    jQuery('#check_objective_'+id).prop('checked',false);
    jQuery('#check_mapped_'+id).prop('checked',false);
    jQuery('#text_container_'+id).remove();
    if(lkey === -1){
        jQuery('.mapped_objective_'+id).remove();
    }
    var mapped_siblings = false;
    jQuery('#objective_'+id).siblings('li.objective-container').each(function(){
        var oid = jQuery(this).attr('data-id');
        if(jQuery('#check_objective_'+oid).prop('checked')){
            mapped_siblings = true;
        }
    });
    jQuery('#objective_'+id).parents('.objective-list').each(function(){
        var mapped_cousins = false;
        var pid = jQuery(this).attr('data-id');
        if(mapped_siblings == false){
            jQuery('#objective_list_'+pid+' > li').each(function(){
                var cid = jQuery(this).attr('data-id');
                if(jQuery('#check_objective_'+cid).prop('checked')){
                    mapped_cousins = true;
                }
            });
            if(mapped_cousins == false){
                jQuery('#check_objective_'+pid).prop('checked',false);
                jQuery('#check_objective_'+pid).prop('disabled',false);
            }
        }
    });

}

function mapObjective(id,title,description,list,create){
    var key = jQuery.inArray(id,mapped);
    var lkey = jQuery.inArray(id,listed);
    if(key != -1) return;
    var importance = 'checked';
    if(list === undefined || !list){
        list = 'flat';
    }
    if(list == 'flat'){
        importance = 'clinical';
    }

    if(description === undefined || !description || description == null || description == 'null'){
        description = '';
    }

    if(create && lkey == -1 && key == -1){
        var li = jQuery(document.createElement('li'))
            .attr('class','mapped-objective mapped_objective_'+id)
            .attr('data-title',title)
            .attr('data-description',description);
        var controls = 	jQuery(document.createElement('div'))
            .attr('class','assessment-item-objective-controls');
        var rm = jQuery(document.createElement('i'))
            .attr('data-id',id)
            .attr('class','icon-remove-sign pull-right objective-remove list-cancel-image')
            .attr('id','objective_remove_'+id);

        jQuery(controls).append(rm);
        jQuery(li).append(controls);

        var strong_title = jQuery(document.createElement('strong'))
            .html(title);

        jQuery(li).append(strong_title);

        var desc = jQuery(document.createElement('div'))
            .attr('class','objective-description')
            .attr('data-description',description);
        var sets_above = jQuery('#objective_'+id).parents('.objective-set');
        var set_id = jQuery(sets_above[sets_above.length-1]).attr('data-id');
        var set_name = jQuery('#objective_title_'+set_id).attr('data-title');
        if(set_name){
            jQuery(desc).html("Curriculum Tag Set: <strong>"+set_name+"</strong><br/>");
        }
        jQuery(desc).append(description);

        jQuery(li).append(desc);
        //jQuery(li).append(rm);
        jQuery('.mapped_assessment_item_objectives').append(li);
        jQuery('.mapped_assessment_item_objectives .display-notice').remove();
        jQuery('#objective_'+id).parents('.objective-list').each(function(){
            var id = jQuery(this).attr('data-id');
            jQuery('#check_objective_'+id).prop('checked',true);
            jQuery('#check_objective_'+id).prop('disabled',true);
        });
        if(jQuery('#assessment-item-toggle').hasClass('collapsed')){
            jQuery('#assessment-item-toggle').removeClass('collapsed');
            jQuery('#assessment-item-toggle').addClass('expanded');
            var d = jQuery('#assessment-item-toggle').next();
            jQuery(d).slideDown();
        }
        if(!jQuery('#assessment-item-list-wrapper').is(':visible')){
            jQuery('#assessment-item-list-wrapper').show();
        }
        list = 'assessment-item';
    }


    jQuery('#check_objective_'+id).prop('checked',true);
    jQuery('#check_mapped_'+id).prop('checked',true);
    if(jQuery("#"+importance+"_objectives_select option[value='"+id+"']").length == 0){
        var option = jQuery(document.createElement('option'))
            .val(id)
            .attr('selected','selected')
            .html(title);
        jQuery('#'+importance+'_objectives_select').append(option);
    }

    jQuery('#objective_'+id).parents('.objective-list').each(function(){
        var id = jQuery(this).attr('data-id');
        jQuery('#check_objective_'+id).prop('checked',true);
        jQuery('#check_objective_'+id).prop('disabled',true);
    });

    mapped.push(id);
}

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
                $("#comments-options").removeClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", true);
                }
                break;
            case "numeric" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").removeClass("hide");
                $("#comments-options").addClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", false);
                }
                break;
            case "free_text" :
            case "date" :
            case "user" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").addClass("hide");
                $("#comments-options").addClass("hide");
                if (modify_mandatory == true) {
                    $("#item-mandatory").prop("checked", false);
                }
                break;
            case "fieldnote" :
                $("#response-section").addClass("hide");
                $("#field-note-response").addClass("hide");
                $("#objective-options").addClass("hide");
                $("#comments-options").addClass("hide");
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
                $("#comments-options").removeClass("hide");
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
            tpl_flag_response: "flag_response[" + response_number + "]"
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

        // Each item has a unique ordinal value (not related to the actual response number). Keep it updated here.
        response_item_ordinal++;

        // Update the labels (so that items in their respective positions are clearly labeled as such)
        update_response_label_ordering();

        // Set properties of the new element (button handlers)
        set_item_response_selector_properties();

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

    function remove_response_row () {
        $("#response-table tr:last").remove();
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

    $('#objective-options').on('click', '.objective-remove', function(){
        var id = jQuery(this).attr('data-id');
        var qrow = jQuery("#qrow").val();
        var list = jQuery('.mapped_objective_'+id).parent().attr('data-importance');
        var importance = 'checked';
        if(list == "flat"){
            importance = 'clinical';
        }
        unmapObjective(id,list,importance);
        removeObjective(id, qrow);
        return false;
    });

    $('#objective-options').on('change', '.checked-objective', function(){
        var id = jQuery(this).val();
        var qrow = jQuery("#qrow").val();
        // parents will return all sets above that objective, which for anything other than curriculum objectives will be an array
        // this grabs all parents above the object and then fetches the list from the immediate (last) parent
        var sets_above = jQuery(this).parents('.objective-set');
        var list = jQuery(sets_above[sets_above.length-1]).attr('data-list');

        var title = jQuery('#objective_title_'+id).attr('data-title');
        var description = jQuery('#objective_'+id).attr('data-description');
        if (jQuery(this).is(':checked')) {
            mapObjective(id,title,description,list,true);
            addObjective(id, qrow);
        } else {
            var importance = 'checked';
            if (list == "flat") {
                importance = 'clinical';
            }
            unmapObjective(id,list,importance);
            removeObjective(id, qrow);
        }
    });

    // cannot find instances of 'checked-mapped' class in add or edit item code
    $(document).on('change', '.checked-mapped', function(){
        var id = jQuery(this).val();
        var qrow = jQuery("#qrow").val();
        // parents will return all sets above that objective, which for anything other than curriculum objectives will be an array
        // this grabs all parents above the object and then fetches the list from the immediate (last) parent
        var sets_above = jQuery(this).parents('.mapped-list');
        var list = jQuery(sets_above[sets_above.length-1]).attr('data-importance');

        var title = jQuery('.mapped_objective_'+id).attr('data-title');
        var description = jQuery('.mapped_objective_'+id).attr('data-description');
        if (jQuery(this).is(':checked')) {
            mapObjective(id,title,description,list,false);
            addObjective(id, qrow);
        } else {
            var importance = 'checked';
            if (list == "flat"){
                importance = 'clinical';
            }
            if (jQuery('.mapped_objective_'+id).is(':checked')) {
                mapObjective(id,title,description,list,false);
                addObjective(id, qrow);
            } else {
                unmapObjective(id,list,importance);
                removeObjective(id, qrow);
            }
        }
    });

    /**
     * Init Code
     */

    //jQuery('#assessment-item-topics-toggle').trigger('click');

    if(jQuery('#mapped_flat_objectives').children('li').length == 0){
        jQuery('#toggle_sets').trigger('click');
    }

    //load mapped array on page load
    jQuery('#checked_objectives_select').children('option').each(function(){
        mapped.push($(this).val());
    });

    jQuery('#clinical_objectives_select').children('option').each(function(){
        mapped.push($(this).val());
    });

    jQuery('#mapped_flat_objectives').children('li').each(function(){
        if(jQuery(this).attr('data-id') !== undefined && jQuery(this).attr('data-id')){
            listed.push(jQuery(this).attr('data-id'));
        }
    });

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
    });

    function clear_response_error() {
        $("#item-removal-success-box").html("");
    }

    /**
     * Enable sorting for item responses
     */
    function enable_responses_sortable() {
        $(".sortable-items").sortable({
            handle: "td.move-item-response",
            placeholder: "success response-row",
            helper: "clone",
            axis: 'y',
            containment: "document",
            start: function (event, ui) {
                clear_response_error();
                var placeholder_item = ui.item.html();
                ui.placeholder.html(placeholder_item);
                //ui.helper.hide();
            },
            stop: function (event, ui) {
                update_response_label_ordering();
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
});