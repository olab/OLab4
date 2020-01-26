jQuery(document).ready(function ($) {

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: "",
        maxDate: ""
    });

    $(".add-on").on("click", function() {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    /**
     * Safely parse JSON and return a default error message (single string) if failure.
     * @param data JSON
     * @param default_message string
     * @returns {Array}
     */
    function safeParseJson(data, default_message) {
        try {
            var jsonResponse = JSON.parse(data);
        }
        catch (e) {
            var jsonResponse = [];
            jsonResponse.status = "error";
            jsonResponse.data = [default_message];
        }
        return jsonResponse;
    }

    function clear_error_messages() {
        $("#msgs").html("");
        $("#assessor-msgs").html("");
    }

    function scroll_to_error(override) {
        var container = "#msgs";
        if (typeof override != "undefined") {
            if (override) {
                container = override;
            }
        }
        jQuery("html, body").animate({scrollTop: jQuery(container).offset().top - 20}, 100);
    }

    /*---- Delegation index/summary page functionality ----**/

    $(".disabled").on("click", function(e) {
        e.preventDefault();
    });

    $("#target-search-input").on("keyup", function () {
        var search_text = $(this).val().toLowerCase();

        if (search_text.length === 0) {
            $("#targets-pending-table-container").removeClass("hide");
            $(".target-block").removeClass("hide");
            $("#targets-pending-table-container-no-search").addClass("hide");
        } else {
            $(".target-block").each(function () {
                $(this).children().each(function(){
                    if ($(this).hasClass("target-block-target-details")) {
                        var oparent = $(this).parent();
                        var text = $(this).text().toLowerCase();
                        if (text.indexOf(search_text) >= 0) {
                            oparent.removeClass("hide").addClass("visible");
                        } else {
                            oparent.addClass("hide").removeClass("visible");
                        }
                    }
                });
            });

            var total_pending = $(".target-block.visible").length;
            if (total_pending === 0) {
                $("#targets-pending-table-container").addClass("hide");
                $("#targets-pending-table-container-no-search").removeClass("hide");
            } else {
                $("#targets-pending-table-container").removeClass("hide");
                $("#targets-pending-table-container-no-search").addClass("hide");
            }
        }
    });

    /**
     * Remove an assessment task/assessor from a target.
     */
    $(".target-list-remove-assessor").on("click", function(e){
        e.preventDefault();

        $("#remove-assessor-assessor-name").html($(this).data("assessor-name"));
        $("#remove-assessor-target-name").html($(this).data("target-name"));

        $("#remove-assessor-info").data("assessor-id", $(this).data("assessor-id"));
        $("#remove-assessor-info").data("target-id", $(this).data("target-id"));
        $("#remove-assessor-info").data("assessment-id", $(this).data("assessment-id"));

        $("#remove-assessor-info").data("target-type", $(this).data("target-type"));
        $("#remove-assessor-info").data("assessor-type", $(this).data("assessor-type"));
        $("#remove-assessor-info").data("addassignment-id", $(this).data("addassignment-id"));

        $("#removal-reason-text").val($("#default-removal-text").val());
        $("#removal-reason-text").data("text-modified", false);

        $("#remove-assessor-confirm-modal").modal("show");
    });

    $("#removal-reason-text").on("focus", function() {
        if ($("#removal-reason-text").data("text-modified") == false) {
            $("#removal-reason-text").data("text-modified", true);
            $("#removal-reason-text").val("");
        }
    });

    /**
     * User clicked on a name in the target list, set it as checked
     */
    $(".delegation-target-td, .delegation-target-img").on("click", function(e){
        e.preventDefault();

        var checkbox_id = $(this).data("target-checkbox-id");
        if (checkbox_id) {
            $('#'+checkbox_id).attr("checked", "checked");
        }
    });

    /**
     * Open then "Mark this delegation task as completed" modal.
     */
    $("#delegation-mark-as-complete-btn").on("click", function(e){
        e.preventDefault();
        $("#mark-as-complete-modal").modal("show");
    });

    /**
     * Submit the selected assessors (POST to next form)
     */
    $("#delegation-add-assessors-btn").on("click", function(e){
        e.preventDefault();

        var total_targets = 0;
        var checked_targets = 0;

        clear_error_messages();

        $('input[name="target_assign[]"]').each(function(k,v){
            if ($(v).attr("checked") == "checked") {
                checked_targets++;
                var value = $(v).val();
                var key = "target_assign_value-" + value;

                $(document.createElement("input")).attr({
                    "type": "hidden",
                    "name": key,
                    "id": key,
                    "value": value
                }).appendTo("#delegation-add-targets-form");
            }
            total_targets++;
        });

        if (!checked_targets) {
            display_error([delegation_summary_msgs.error_select_targets], "#msgs", "prepend");
            scroll_to_error();
        } else {
            // post the form
            $("#all_targets_selected").val(checked_targets == total_targets ? "1" : "0");
            $("#delegation-add-targets-form").submit();
        }
    });

    /**
     * User confirmed removal of assessor.
     */
    $("#modal-remove-assessor-btn").on("click", function(e){
        e.preventDefault();
        var distribution_id = $('input[name="adistribution_id"]').val();
        var delegation_id = $('input[name="addelegation_id"]').val();

        if ($("#removal-reason-text").data("text-modified") == false) {
            display_error([delegation_summary_msgs.error_add_removal_reason], "#msgs", "prepend");
            $("#remove-assessor-confirm-modal").modal("hide");
            scroll_to_error();
        } else {
            var api_request = jQuery.ajax({
                url: "?section=api-delegation",
                data: {
                    "method": "remove-assessor",
                    "adistribution_id": distribution_id,
                    "addelegation_id": delegation_id,
                    "removal_reason": $("#removal-reason-text").val(),
                    "removal_reason_id": $("#delete-tasks-reason").val(),
                    "addassignment_id": $("#remove-assessor-info").data("addassignment-id"),
                    "assessment_id": $("#remove-assessor-info").data("assessment-id"),
                    "target_type": $("#remove-assessor-info").data("target-type"),
                    "target_id": $("#remove-assessor-info").data("target-id"),
                    "assessor_type": $("#remove-assessor-info").data("assessor-type"),
                    "assessor_id": $("#remove-assessor-info").data("assessor-id")
                },
                type: "POST"
            });

            jQuery.when(api_request).done(function (data) {
                var jsonResponse = safeParseJson(data, delegation_summary_msgs.error_default);
                clear_error_messages();
                if (jsonResponse.status == "success") {
                    $("#remove-assessor-confirm-modal").fadeOut(0, function(){
                        location.reload();
                    });
                } else { // jsonResponse.status == "error"
                    $("#remove-assessor-confirm-modal").modal("hide");
                    display_error(jsonResponse.data, "#msgs", "prepend");
                    scroll_to_error();
                }
            });
        }
    });

    /**
     * Mark the delegation as complete, and hide the modal
     */
    $("#modal-delegation-complete-btn").on("click", function(e){
        e.preventDefault();
        if ( !$(this).hasClass("disabled") ) {

            var distribution_id = $('input[name="adistribution_id"]').val();
            var delegation_id = $('input[name="addelegation_id"]').val();
            var completed_reason = "";
            if ($("#completion-reason-text").data("text-modified") == true) {
                completed_reason = "&completed_reason=" + encodeURIComponent( $("#completion-reason-text").val() );
            }

            var api_request = $.ajax({
                url: "?section=api-delegation",
                data: "method=complete-delegation&addelegation_id=" + delegation_id +"&adistribution_id=" + distribution_id + completed_reason,
                type: "POST"
            });

            $.when(api_request).done(function (data) {
                var jsonResponse = safeParseJson(data, delegation_summary_msgs.error_default);
                clear_error_messages();
                if (jsonResponse.status == "success") {
                    $("#mark-as-complete-modal").fadeOut(0, function(){
                        window.location = ENTRADA_URL + "/assessments/";
                    });
                } else { // jsonResponse.status == "error"
                    $("#remove-assessor-confirm-modal").modal("hide");
                    display_error(jsonResponse.data, "#msgs", "prepend");
                    scroll_to_error();
                }
            });
        }
    });

    $("#completion-reason").on("click", function(e) {
        $("#completion-reason-text").addClass("disabled");
        $("#completion-reason-text").attr("disabled", "disabled");

        $("#modal-delegation-complete-btn").removeClass("disabled");
    });

    $("#completion-reason-other").on("click", function(e) {
        $("#completion-reason-text").removeClass("disabled");
        $("#completion-reason-text").removeAttr("disabled", "disabled");
        $("#modal-delegation-complete-btn").removeClass("disabled");

        if ($("#completion-reason-text").data("text-modified") == false) {
            $("#completion-reason-text").data("text-modified", true);

            $("#completion-reason-text").html('');
        }
    });

    /*---- Select assessors page functionality ----**/

    function duplicate_warning_modal (duplicates) {
        // populate the duplicate assessor modal with the duplicates data
        jQuery("#duplicate-assessments-model-body").html('');

        duplicates.forEach(function(dupe) {
            var target_type = dupe['target']['target_type'];
            var target_value = dupe['target']['target_value'];
            var target_div = '#target-' + target_type + '-' + target_value;

            var assessor_type = dupe['assessor']['assessor_type'];
            var assessor_value = dupe['assessor']['assessor_value'];
            var assessor_div = '#assessor-container-' + assessor_type + '-' + assessor_value;

            var new_row = jQuery(document.createElement("tr"));

            new_row.html("<td>" + jQuery(target_div).html() + "</td><td>" + jQuery(assessor_div).html() + "</td>");

            jQuery("#duplicate-assessments-model-body").append(new_row);
            jQuery("#duplicate-assessments-model-body td .pull-right").removeClass("pull-right"); // clear the pull-right class on the copied div, it screws up spacing in the modal context
        });
        jQuery("#duplicate-assessor-error-modal").modal("show");
    }

    function query_assessor_selections (ignore_duplicates) {
        var is_checked = false;
        var selected_assessors = [];
        var selected_targets = [];
        var ignore_duplicates_str = (ignore_duplicates)?'1':'0';
        var distribution_id = $('input[name="adistribution_id"]').val();
        var delegation_id = $('input[name="addelegation_id"]').val();

        // add the selected assessors to the form
        jQuery('input[name="selected_assessors[]"]').remove(); // clear any extraneous form inputs
        jQuery('input[name="add_assessors[]"]').each(function(k,v){
            if (jQuery(v).attr("checked") == "checked") {
                is_checked = true;

                selected_assessors.push({'assessor_value':jQuery(this).data("assessor-value"), 'assessor_type':jQuery(this).data("assessor-type")});

                var value = jQuery(v).data("assessor-type") + '-' + jQuery(v).data("assessor-value");
                jQuery(document.createElement("input")).attr({
                    "type": "hidden",
                    "name": "selected_assessors[]",
                    "id": "assessor-" + value,
                    "value": value
                }).appendTo("#delegation-add-assessors-form");
            }
        });
        jQuery('input[name="selected_targets[]"]').each(function() {
            selected_targets.push({'target_id':jQuery(this).data("target-id"), 'type':jQuery(this).data("target-type"), 'scope':jQuery(this).data("target-scope")});
        });

        if (!is_checked) {
            display_error([select_assessors_msgs.error_select_assessors], "#msgs", "prepend");
            scroll_to_error();
        } else {
            var api_request = jQuery.ajax({
                url: "?section=api-delegation",
                data: { "method": "query-assessor-selections",
                        "adistribution_id": distribution_id,
                        "addelegation_id": delegation_id,
                        "allow_duplicates": ignore_duplicates_str,
                        "assessor_list": selected_assessors,
                        "target_list": selected_targets
                    },
                type: "POST"
            });

            jQuery.when(api_request).done(function (data) {
                var jsonResponse = safeParseJson(data, select_assessors_msgs.error_default);
                clear_error_messages();

                if (jsonResponse.status == "success") {
                    // no errors, go to next page
                    jQuery("#delegation-add-assessors-form").submit();

                } else if (jsonResponse.status == "duplicates_error") {
                    // if AJAX returns duplicate warning
                    duplicate_warning_modal(jsonResponse.duplicates);

                } else { // jsonResponse.status == "error"
                    display_error(jsonResponse.data, "#msgs", "prepend");
                    scroll_to_error();
                }
            });
        }
    }

    $("#add-unlisted-assessor").on("click", function(e){
        e.preventDefault();

        // Clear off previously tracked checkbox selections
        jQuery('input[name="selected_available_assessors[]"]').remove(); // clear any extraneous form inputs

        // Save the assessor selections (the checkboxes ticked)
        $('input[name="add_assessors[]"]').each(function(k,v){
            if ($(v).attr("checked") == "checked") {
                var form_input = jQuery(document.createElement("input")).attr({
                    id: "form-checkbox-" + $(v).attr("id"),
                    name: "selected_available_assessors[]",
                    type: "hidden",
                    value: $(v).data("assessor-type") + "_" + $(v).data("assessor-value")
                });
                $("#assessor-selections-form").append(form_input);
            }
        });

        $("#additional-assessors-modal").modal("show");
    });

    $("#assessor-search-input").on("keyup", function () {
        var search_text = $(this).val().toLowerCase();

        if (search_text.length === 0) {
            $("#available-assessors-list").removeClass("hide");
            $(".assessor-block").removeClass("hide");
        } else {
            $(".assessor-block").each(function () {
                var text = $(this).text().toLowerCase();

                if (text.indexOf(search_text) >= 0) {
                    $(this).removeClass("hide").addClass("visible");
                } else {
                    $(this).addClass("hide").removeClass("visible");
                }
            });
        }
    });

    $("#assessor-select-cancel-btn").on("click", function(e) {
        e.preventDefault();
        window.location = previous_page_url; // previous page url is set by the assessor selection template.
    });

    $("#assessor-select-continue-forced-btn").on("click", function(e) {
        e.preventDefault();
        $("#duplicate-assessor-error-modal").modal("hide");
        query_assessor_selections(true);
    });

    $("#assessor-select-continue-btn").on("click", function(e) {
        e.preventDefault();
        query_assessor_selections(false);
    });

    /*---- Confirmation page functionality ----**/

    $("#auto-mark-complete").on("change", function() {
        if ($("[name=\"all_targets_selected\"]").val() == "0") {
            if ($(this).prop('checked') == true) {
                $("#pending-target-warning").removeClass("hide");
            } else {
                $("#pending-target-warning").addClass("hide");
            }
        }
    });

    function create_new_assessments () {
        var distribution_id = $('input[name="adistribution_id"]').val();
        var delegation_id = $('input[name="addelegation_id"]').val();
        var auto_mark_complete = 0;
        var redirect_to_delegation_index = $("[name=\"all_targets_selected\"]").val() == "0";

        if ($("#auto-mark-complete").prop("checked") === true) {
            auto_mark_complete = $("#auto-mark-complete").attr("value");
        }

        var selected_assessors = [];
        jQuery('input[name="selected_assessors[]"]').each(function() {
            selected_assessors.push({'assessor_value':jQuery(this).data("assessor-value"), 'assessor_type':jQuery(this).data("assessor-type")});
        });

        var selected_targets = [];
        jQuery('input[name="selected_targets[]"]').each(function() {
            selected_targets.push({'target_id':jQuery(this).data("target-id"), 'type':jQuery(this).data("target-type"), 'scope':jQuery(this).data("target-scope")});
        });

        // Try and add new assessment tasks
        var api_request = jQuery.ajax({
            url: "?section=api-delegation",
            data: {
                "method"            : "create-assessments",
                "allow_duplicates"  : 1,
                "addelegation_id"   : delegation_id,
                "adistribution_id"  : distribution_id,
                "assessor_list"     : selected_assessors,
                "target_list"       : selected_targets,
                "auto_mark_complete": auto_mark_complete
            },
            type: "POST"
        });

        jQuery.when(api_request).done(function (data) {
            var jsonResponse = safeParseJson(data, confirm_assessments_msgs.error_default);
            clear_error_messages();

            if (jsonResponse.status == "success") {
                jQuery("#confirm-create-tasks-btn").remove();
                jQuery("#assessor-assignment-container").addClass("hide");
                jQuery("#distribution-information").addClass("hide");

                if (redirect_to_delegation_index) {
                    if (auto_mark_complete) {
                        display_success([confirm_assessments_msgs.success_added_assessors_auto_completed_delegation], "#msgs", "prepend");
                    } else {
                        display_success([confirm_assessments_msgs.success_added_assessors_delegation], "#msgs", "prepend");
                    }
                    setTimeout(function () { window.location = success_delegation_url; }, 5000);
                } else {
                    if (auto_mark_complete) {
                        display_success([confirm_assessments_msgs.success_added_assessors_auto_completed], "#msgs", "prepend");
                        setTimeout(function () { window.location = success_url; }, 5000);
                    } else {
                        display_success([confirm_assessments_msgs.success_added_assessors_delegation], "#msgs", "prepend");
                        setTimeout(function () { window.location = success_delegation_url; }, 5000);
                    }
                }
            } else {
                display_error([confirm_assessments_msgs.error_creating_assessments], "#msgs", "prepend");
                scroll_to_error();
            }
        });
    }

    $("#confirm-cancel-previous-btn").on("click", function(e) {
        e.preventDefault();
        $("#selection-form-posted").submit();
    });

    $("#confirm-create-tasks-btn").on("click", function(e) {
        e.preventDefault();
        create_new_assessments();
    });


    /*---- ADDITIONAL ASSESSOR FUNCTIONALITY ----*/

    function append_checked_assessors(checkbox_ids, append_to_selector) {
        checkbox_ids.forEach(function(previously_selected) {
            var form_input = $(document.createElement("input")).attr({
                id: "prepopulate-" + previously_selected,
                name: "prepopulate_checkboxes[]",
                type: "hidden",
                value: previously_selected
            });
            $(append_to_selector).append(form_input);
        });
    }

    function set_selected_assessors_heading() {
        var child_count = $("#selected-assessors-list-ul").children().length;
        if (child_count == 0) {
            $("#selected-assessors-list-heading").addClass("hide");
            $("selected-assessors-list-ul").removeClass("selected-assessors-results-ul");
            $("#assessor-msgs").html("");
        } else {
            $("#selected-assessors-list-heading").removeClass("hide");
        }
    }

    function build_selected_assessor_item (id, name, assessor_type) {
        var item_id = "selected-assessor-" + assessor_type.toLowerCase() + "-" + id;

        if ($("#"+item_id).length) {
            // Ignore the already added item.
            return;
        }

        var item_li = $(document.createElement("li"));
        $(item_li).html($("#default-selected-assessor").html()); // copy the default element
        $(item_li).data("value", id);
        $(item_li).data("type", assessor_type);
        $(item_li).attr("id", item_id);
        $(item_li).addClass("community");
        $(item_li).addClass(item_id);

        if (assessor_type == "Internal") {
            $(item_li).find(".selected-assessor-label").html(additional_assessors_msgs.internal_label);
        } else {
            $(item_li).find(".selected-assessor-label").html(additional_assessors_msgs.external_label);
        }
        $(item_li).find(".selected-assessor-name").html(name);

        $(item_li).find(".remove-selected-assessor").on("click", function(e){
            e.preventDefault();
            $("#" + item_id).remove();
            $("#form-" + item_id).remove();
            set_selected_assessors_heading();
        });

        var form_input = jQuery(document.createElement("input")).attr({
            id: "form-" + item_id,
            name: "selected_additional_assessors[]",
            type: "hidden",
            value: assessor_type.toLowerCase() + "_" + id
        });
        $("#assessor-selections-form").append(form_input);
        $("#selected-assessors-list-ul").append(item_li);

        set_selected_assessors_heading();
    }

    function close_additional_assessors_autocomplete () {
        $("#additional-assessors-search").val("");
        $("#additional-assessors-search").removeClass("searching");
        $("#additional-assessors-search").addClass("search");
        $("#additional-assessors-autocomplete-list").addClass("hide");
        $("#assessor-autocomplete-clear-fields").addClass("hide");
        reset_external_assessor_controls();

        $(".ui-autocomplete").html("");
    }

    if ($("#additional-assessors-search").length) {

        var assessor_autocomplete = $("#additional-assessors-search").autocomplete({
            source: "?section=api-delegation&method=get-organisation-users",
            minLength: 3,
            appendTo: $("#additional-assessors-autocomplete-list"),
            open: function () {
                $("#additional-assessors-search").removeClass("searching");
                $("#additional-assessors-search").addClass("search");
                $("#assessor-autocomplete-clear-fields").removeClass("hide");
            },
            select: function (e, ui) {
                $("selected-assessors-list-ul").addClass("selected-assessors-results-ul");
                build_selected_assessor_item(ui.item.value, ui.item.label, ui.item.role);
                e.preventDefault();
                close_additional_assessors_autocomplete();
            },
            search: function () {
                $("#additional-assessors-autocomplete-list").removeClass("hide");
                $("#additional-assessors-search").removeClass("search");
                $("#additional-assessors-search").addClass("searching");
            }
        }).data("autocomplete");

        assessor_autocomplete.close = function() {};
        assessor_autocomplete._renderItem = function (ul, item) {
            var user_li = $(document.createElement("li")).data("item.autocomplete", item);
            $(user_li).html($("#autocomplete-default-item").html());

            if (item.role == "Internal") {
                $(user_li).find(".additional-assessor-type").html(additional_assessors_msgs.internal_label);
                $(user_li).find(".additional-assessor-type").addClass("badge-green");
            } else {
                $(user_li).find(".additional-assessor-type").html(additional_assessors_msgs.external_label);
                $(user_li).find(".additional-assessor-type").addClass("badge-grey");
            }
            $(user_li).find(".additional-assessor-name").html(item.label);
            $(user_li).find(".additional-assessor-email").html(item.email);

            return (user_li.appendTo(ul));
        };

        assessor_autocomplete._renderMenu = function (ul, items) {
            $.each(items, function (index, item) {
                assessor_autocomplete._renderItem(ul, item);
            });

            show_add_external_button();
        };
    }

    $("#additional-assessor-selections-submit").on("click", function(e){
        e.preventDefault();
        var distribution_id = $('input[name="adistribution_id"]').val();
        var delegation_id = $('input[name="addelegation_id"]').val();
        var selected_additional_assessors = [];
        jQuery('input[name="selected_additional_assessors[]"]').each(function() {
            selected_additional_assessors.push($(this).val());
        });

        var selected_available_assessors = [];
        jQuery('input[name="selected_available_assessors[]"]').each(function() {
            selected_available_assessors.push($(this).val()); // these are used to repopulate the checked boxes on the page
        });

        var api_request = jQuery.ajax({
            url: "?section=api-delegation",
            data: {
                "method": "add-additional-assessors",
                "adistribution_id": distribution_id,
                "addelegation_id": delegation_id,
                "selected_available_list": selected_available_assessors,
                "selected_additional_list": selected_additional_assessors
            },
            type: "POST"
        });

        jQuery.when(api_request).done(function (data) {
            var jsonResponse = safeParseJson(data, select_assessors_msgs.error_default);
            clear_error_messages();

            if (jsonResponse.status == "success") {
                $("#additional-assessors-modal").fadeOut(0, function() {
                    if (typeof jsonResponse.data.checked_assessors != "undefined") {
                        append_checked_assessors(jsonResponse.data.checked_assessors, "#delegation-add-assessors-form");
                    }
                });
                $("#delegation-add-assessors-form").attr("action", $("#form-repost-url").val());
                $("#delegation-add-assessors-form").submit();
            } else {
                display_error(jsonResponse.data, "#assessor-msgs", "prepend");
            }
        });
    });

    $("#cancel-assessor-btn").on("click", function(e) {
        e.preventDefault();
        $("#external-assessors-controls").addClass("hide");
    });

    $("#save-external-assessor-btn").on("click", function(e) {
        e.preventDefault();
        add_external_assessor();
    });

    function add_external_assessor () {
        var firstname = jQuery("#external-assessor-firstname").val();
        var lastname  = jQuery("#external-assessor-lastname").val();
        var email     = jQuery("#external-assessor-email").val();
        var distribution_id = $('input[name="adistribution_id"]').val();
        var delegation_id = $('input[name="addelegation_id"]').val();

        var add_external_assessor_request = jQuery.ajax({
            url: "?section=api-delegation",
            data: {
                "method": "add-external-assessor",
                "adistribution_id": distribution_id,
                "addelegation_id": delegation_id,
                "firstname": firstname,
                "lastname": lastname,
                "email": email
            },
            type: "POST"
        });

        jQuery.when(add_external_assessor_request).done(function (data) {
            var jsonResponse = safeParseJson(data, select_assessors_msgs.error_default);
            if (jsonResponse.status === "success") {
                jQuery("#msgs").empty();
                jQuery("#external-assessors-controls").addClass("hide");

                jQuery("selected-assessors-list-ul").addClass("selected-assessors-results-ul");
                build_selected_assessor_item(jsonResponse.data.id, jsonResponse.data.firstname + " " + jsonResponse.data.lastname, "External");
            } else {
                display_error(jsonResponse.data, "#assessor-msgs", "prepend");
            }
        });
    }

    function reset_external_assessor_controls() {
        $(".add-external-assessor-btn").remove();
    }

    function show_add_external_button() {
        var external_button = $(document.createElement("a")).attr({
            class:"add-external-assessor-btn"
        });
        $(external_button).html($("#default-add-external-assessor-btn").html());
        $(external_button).css("display", "inline-block");
        $(external_button).removeClass("hide");

        $(external_button).on("click", function(e) {
            e.preventDefault();
            $("#external-assessors-controls").removeClass("hide");
            close_additional_assessors_autocomplete();
        });

        if ($(".add-external-assessor-btn").length == 0) {
            $("#additional-assessors-autocomplete-list").append(external_button);
        }
    }

    $("#assessor-autocomplete-clear-field").on("click", function(){
        close_additional_assessors_autocomplete();
    });
});