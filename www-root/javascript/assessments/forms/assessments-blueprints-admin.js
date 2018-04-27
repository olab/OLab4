var COURSE_ID;
var scroll_speed = 350;
var timeout;
var form_offset = 0;
var form_limit = 50;
var total_blueprints = 0;
var show_loading_message = true;
var notices_timeout = 3000;

function scroll_to_container(container) {
    if (typeof container != "undefined") {
        jQuery("html, body").animate({scrollTop: jQuery(container).offset().top - 20}, scroll_speed);
    }
}

function display_contextual_variables_component(component_id, epas_desc, vars_desc, data, init_data, var_settings) {
    jQuery("#table-contextual-vars-" + component_id).find("tr:gt(1)").remove();

    jQuery.each(data, function(set_no, set_data) {
        set_no++;

        jQuery('<tr/>').loadTemplate("#contextual-vars-selector-header-row-template")
            .appendTo("#table-contextual-vars-" + component_id)
            .addClass("heading");

        var template_data = {
            tpl_cvars_selector_tr_vars_td_id: "contextual-vars-list-td-" + component_id + "-" + set_no,
            tpl_cvars_selector_tr_epas_td_id: "epa-list-td-" + component_id + "-" + set_no,
            tpl_cvars_selector_set_no: 'input-hidden-set-no-' + component_id + '-' + set_no
        };

        // Append new element from template to the table.
        jQuery('<tr/>').loadTemplate("#contextual-vars-selector-row-template", template_data)
            .appendTo("#table-contextual-vars-" + component_id)
            .data("set", set_no)
            .attr("id", "contextual-vars-selector-row-" + component_id + "-" + set_no)
            .addClass("contextual-vars-selector-row");

        jQuery("#input-hidden-set-no-" + component_id + "-" + set_no).val(set_no);

        jQuery.each(set_data.vars, function(index, var_id) {
            var template_data = {
                tpl_cvars_selector_element_id: 'contextual-var-' + component_id + '-' + set_no + '-' + var_id,
                tpl_cvars_selector_element_name: "contextual_vars_" + component_id + "_" + set_no + "[]",
                tpl_cvars_selector_data_set: set_no,
                tpl_cvars_checkbox_value: var_id,
                tpl_cvars_label_content: vars_desc[var_id]['objective_name'],
                tpl_cvars_selected_responses_id: 'contextual-vars-selected-responses-' + set_no + '-' + var_id
            };
            jQuery('<div/>').loadTemplate("#contextual-vars-selector-template", template_data)
                .appendTo("#contextual-vars-list-td-" + component_id + "-" + set_no);

            if (var_settings.required_types.indexOf(vars_desc[var_id]["objective_code"]) > -1) {
                jQuery('#contextual-var-' + component_id + '-' + set_no + '-' + var_id).prop("checked", true);
                jQuery('#contextual-var-' + component_id + '-' + set_no + '-' + var_id).prop("disabled", true);
                jQuery.ajax({
                    url: "?section=api-blueprints",
                    type: "GET",
                    data: "method=get-cvar-responses-count&cvar_id=" + var_id +
                        "&course_id=" + jQuery("#course-id").val() +
                        "&form_type_id=" + jQuery("#form-type-id").val(),
                    success: function(data) {
                        var jsonResponse = safeParseJson(data, "");
                        var selected_count = 0;
                        if (jsonResponse.status == "success") {
                            jQuery.ajax({
                                url: "?section=api-blueprints",
                                type: "GET",
                                data: "method=get-child-objectives&parent_id=" + var_id +
                                    "&course_id=" +jQuery("#course-id").val() +
                                    "&form_type_id=" + jQuery("#form-type-id").val(),
                                success: function(data) {
                                    var jsonResponse2 = safeParseJson(data, "");

                                    jsonResponse2.data.each(function(objective) {
                                        var hidden = jQuery(document.createElement("input"));
                                        hidden.attr({
                                            "type": "hidden",
                                            "name": "cvariable_responses_" + component_id + "_" + set_no + "_" + var_id + "[]"
                                        }).val(objective.target_id);

                                        jQuery("#update-blueprint-contextual-vars-selection-form-" + component_id).append(hidden);
                                        selected_count++;

                                        jQuery("#contextual-vars-selected-responses-" + set_no + "-" + var_id).text(selected_count + "/" + jsonResponse.data.response_count);
                                        jQuery("#contextual-vars-selected-responses-" + set_no + "-" + var_id).removeClass("hide");
                                    });
                                }
                            });
                        }
                    }
                });

                var hidden = jQuery(document.createElement("input"))
                    .attr({
                        "name": "contextual_vars_" + component_id + "_" + set_no + "[]",
                        "type": "hidden"
                    }).val(var_id);
                jQuery('#contextual-var-' + component_id + '-' + set_no + '-' + var_id).closest("div").append(hidden);
            }
        });

        jQuery.each(set_data.epas, function(index, epa_id) {
            var template_data = {
                tpl_cvars_epas_selector_element_id: 'contextual-epa-' + component_id + '-' + set_no + '-' + epa_id,
                tpl_cvars_epas_selector_element_name: "contextual_vars_epa_" + component_id + "_" + set_no + "[]",
                tpl_cvars_epas_selector_data_set: set_no,
                tpl_cvars_epas_checkbox_value: epa_id,
                tpl_cvars_epas_label_content: epas_desc[epa_id]['objective_code'] + " : " + epas_desc[epa_id]['objective_name']
            }
            jQuery("<div/>").loadTemplate("#contextual-vars-epas-selector-template", template_data)
                .appendTo("#epa-list-td-" + component_id + "-" + set_no);
        });
    });

    jQuery("#blueprint-contextual-vars-selector-markup-" + component_id).find(".blueprint-component-save-data").removeClass("hide");
    jQuery("#blueprint-contextual-vars-selector-markup-" + component_id).find(".assessment-item-disabled-overlay").remove();
    jQuery('html, body').animate({
        scrollTop: jQuery("#blueprint-contextual-vars-selector-markup-" + component_id).offset().top
    }, scroll_speed);
}

function get_new_cvars_set_no(component_id) {
    new_set=1;

    while(jQuery("#input-hidden-set-no-" + component_id + '-' + new_set).length) {
        new_set++;
    }

    return new_set;
}

function show_cvar_responses_modal(cvar_id, set_no, component_id) {
    jQuery.ajax({
        url: "?section=api-blueprints",
        type: "GET",
        data: "method=get-child-objectives&parent_id=" + cvar_id +
            "&course_id=" +jQuery("#course-id").val() +
            "&form_type_id=" + jQuery("#form-type-id").val(),
        success: function(data) {
            jsonResponse = safeParseJson(data, "");
            jQuery('#contextual-variables-response-list').empty();
            jsonResponse.data.each(function(objective) {
                var template_data = {
                    tpl_cvars_response_element_id: 'cvar-response-checkbox-' + objective.target_id,
                    tpl_cvars_response_element_value: objective.target_id ,
                    tpl_cvars_response_label_content: objective.target_label
                };
                jQuery("<div/>").loadTemplate("#contextual-vars-responses-template", template_data)
                    .appendTo('#contextual-variables-response-list');

                if (jQuery("input[name^='cvariable_responses_" + component_id + "_" + set_no + "_" + cvar_id + "'][value='" + objective.target_id + "']").length ) {
                    jQuery('#cvar-response-checkbox-' + objective.target_id).attr("checked", "checked");
                }
            });
            jQuery("#cvar-response-cvar-id").val(cvar_id);
            jQuery("#cvar-response-set-no").val(set_no);
            jQuery("#cvar-response-component-id").val(component_id);
            jQuery("#contextual-variable-responses-modal").modal("show");
        }
    });
}

function show_cvar_responses_badge(cvar_id, set_no, selected_count) {
    jQuery.ajax({
        url: "?section=api-blueprints",
        type: "GET",
        data: "method=get-cvar-responses-count&cvar_id=" + cvar_id +
            "&course_id=" +jQuery("#course-id").val() +
            "&form_type_id=" + jQuery("#form-type-id").val(),
        success: function(data) {
            var jsonResponse = safeParseJson(data, "");
            if (jsonResponse.status == "success") {
                jQuery("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).text(selected_count + "/" + jsonResponse.data.response_count);
                jQuery("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).removeClass("hide");
            }
        }
    });
}

function display_scale_selector_component(component_id) {
    jQuery("#blueprint-scale-selector-markup-" + component_id).find(".blueprint-component-save-data").removeClass("hide");
    jQuery("#blueprint-scale-selector-markup-" + component_id).find(".assessment-item-disabled-overlay").remove();

    jQuery('html, body').animate({
        scrollTop: jQuery("#blueprint-scale-selector-markup-" + component_id).offset().top
    }, scroll_speed);
}

function display_free_text_component(component_id) {
    jQuery("#blueprint-free-text-markup-" + component_id).find(".blueprint-component-save-data").removeClass("hide");
    jQuery("#blueprint-free-text-markup-" + component_id).find(".assessment-item-disabled-overlay").remove();

    jQuery('html, body').animate({
        scrollTop: jQuery("#blueprint-free-text-markup-" + component_id).offset().top
    }, scroll_speed);
}

function display_roles_selector_component(component_id) {
    jQuery("#blueprint-roles-selector-markup-" + component_id).find(".blueprint-component-save-data").removeClass("hide");
    jQuery("#blueprint-roles-selector-markup-" + component_id).find(".assessment-item-disabled-overlay").remove();

    jQuery('html, body').animate({
        scrollTop: jQuery("#blueprint-roles-selector-markup-" + component_id).offset().top
    }, scroll_speed);
}

function hide_next_components(component_id) {
    /**
     * Hide all components sections that comes after
     */
    jQuery.each(jQuery(".blueprint-component-section"), function() {
        if ( jQuery(this).data("component_id") > component_id && jQuery(this).find(".assessment-item-disabled-overlay").length < 1) {
            jQuery(this).find(".blueprint-component-save-data").addClass("hide");
            var div = jQuery(document.createElement('div')).addClass("assessment-item-disabled-overlay");
            jQuery(this).prepend(div);
        }
    });
}

function get_blueprints() {
    if (jQuery("#search-targets-form").length > 0) {
        var filters = jQuery("#search-targets-form").serialize();
    }
    var forms = jQuery.ajax({
        url: "?section=api-blueprints",
        data: "method=get-blueprints&search_term=" + jQuery("#blueprint-search").val() +
        "&limit=" + form_limit +
        "&offset=" + form_offset +
        (typeof filters !== "undefined" ? "&" + filters : ""),

        type: "GET",
        beforeSend: function () {
            if (jQuery("#assessments-no-results").length) {
                jQuery("#assessments-no-results").remove();
            }
            if (show_loading_message) {
                jQuery("#assessment-forms-loading").removeClass("hide");
                jQuery("#blueprints-table").addClass("hide");
                jQuery("#load-blueprints").addClass("hide");
                jQuery("#blueprints-table tbody").empty();
            } else {
                jQuery("#load-blueprints").addClass("loading");
            }
        }
    });
    jQuery.when(forms).done(function (data) {
        if (jQuery("#assessments-no-results").length) {
            jQuery("#assessments-no-results").remove();
        }

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            total_blueprints += parseInt(jsonResponse.results);

            var set_disabled = false;
            if (total_blueprints >= jsonResponse.data.total_blueprints) {
                set_disabled = true;
                total_blueprints = jsonResponse.data.total_blueprints;
            }
            var localized_str = blueprints_index.Showing_Of_Blueprints.replace('%1', total_blueprints).replace('%2', jsonResponse.data.total_blueprints);
            jQuery("#load-blueprints").html(localized_str);

            if (jsonResponse.results < form_limit) {
                jQuery("#load-blueprints").attr("disabled", "disabled");
            } else {
                jQuery("#load-blueprints").removeAttr("disabled");
            }
            if (set_disabled) {
                jQuery("#load-blueprints").attr("disabled", "disabled");
            }

            form_offset = (form_limit + form_offset);

            jQuery.each(jsonResponse.data.blueprints, function (key, blueprint) {
                build_blueprint_row(blueprint);
            });

            if (show_loading_message) {
                jQuery("#assessment-forms-loading").addClass("hide");
                jQuery("#blueprints-table").removeClass("hide");
                jQuery("#load-blueprints").removeClass("hide");
            } else {
                jQuery("#load-blueprints").removeClass("loading");
            }

            show_loading_message = false;

        } else {
            jQuery("#assessment-forms-loading").addClass("hide");
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html(blueprints_index.No_Blueprint_Found);
            jQuery(no_results_div).append(no_results_p).attr({id: "assessments-no-results"});
            jQuery("#assessment-msgs").append(no_results_div);
        }
    });
}

function build_blueprint_row (blueprint) {
    // Append new element from template to the table.
    jQuery("<tr/>")
        .loadTemplate("#form-blueprint-search-result-row-template", {
            tpl_formblueprint_link_id: "formblueprint_title_link_" + blueprint.form_blueprint_id,
            tpl_formblueprint_id: blueprint.form_blueprint_id,
            tpl_formblueprint_title: blueprint.title,
            tpl_formblueprint_created: blueprint.created_date,
            tpl_formblueprint_item_count: blueprint.item_count,
            tpl_formblueprint_url: ENTRADA_URL + "/admin/assessments/blueprints?section=edit-blueprint&form_blueprint_id=" + blueprint.form_blueprint_id,
            tpl_url_target: null,
            tpl_formblueprint_type: blueprint.form_type
        })
        .appendTo("#blueprints-table")
        .addClass("form-row");
}

function show_epa_milestones_modal(epa_id, component_id) {
    jQuery.ajax({
        url: "?section=api-blueprints",
        type: "GET",
        data: "method=get-epa-milestones&epa_id=" + epa_id + "&course_id=" + COURSE_ID,
        success: function(data) {
            var jsonResponse = safeParseJson(data, "");
            jQuery('#epa-milestones-list').empty();
            jsonResponse.data.each(function(objective) {
                var template_data = {
                    tpl_epa_milestones_element_id: 'epa-milestones-checkbox-' + objective.objective_id,
                    tpl_epa_milestones_element_value: objective.objective_id,
                    tpl_epa_milestones_label_content: "<b>" + objective.objective_code + "</b><br>" + objective.objective_name
                };
                jQuery("<div/>").loadTemplate("#epa-milestones-template", template_data)
                    .appendTo('#epa-milestones-list');

                if (jQuery("input[name^='milestones_" + component_id + "_" + epa_id + "'][value='" + objective.objective_id + "']").length ) {
                    jQuery('#epa-milestones-checkbox-' + objective.objective_id).attr("checked", "checked");
                }
            });
            jQuery("#epa-milestones-epa-id").val(epa_id);
            jQuery("#epa-milestones-component-id").val(component_id);
            update_epa_milestones_checkboxes_lock_status();
            jQuery("#epa-milestones-modal").modal("show");
        }
    });
}

function update_epa_milestones_checkboxes_lock_status() {
    var component_id = jQuery("#epa-milestones-component-id").val();
    var max_milestones = jQuery("#max-milestones-" + component_id).val();

    if (max_milestones > 0 && jQuery("input[name^='epa_milestones']:checked").length >= max_milestones ) {
        jQuery("input[name^='epa_milestones']:not(:checked)").prop("disabled", true);
    } else {
        jQuery("input[name^='epa_milestones']").prop("disabled", false);
    }
}

function highlight_missing(component_id) {
    if (jQuery("#blueprint-epa-selector-markup-" + component_id).length) {
        /* EPA */
        jQuery("#update-blueprint-epa-selection-form-" + component_id + " .selected-milestones-badge").each(function() {
            var x = jQuery(this).text();
            if (x.charAt(0) == '0') {
                jQuery(this).addClass("blueprint-badge-missing");
            }
        });
    } else if (jQuery("#blueprint-contextual-vars-selector-markup-" + component_id).length) {
        /* Contextual Variable */
        jQuery("#update-blueprint-contextual-vars-selection-form-" + component_id + " .selected-responses-badge").each(function() {
            var x = jQuery(this).text();
            if (x.charAt(0) == '0') {
                jQuery(this).addClass("blueprint-badge-missing");
            }
        });
    }
}

jQuery(function($) {
    /**
     * EPAs selection specific section
     */
    $(document).on("click", ".selected-milestones-badge", function() {
        var epa_id = $(this).closest("li").data("id");
        var component_id = $(this).closest("table").data("component-id");

        show_epa_milestones_modal(epa_id, component_id);
    });

    $(document).on("click", "#epa-milestones-check-all", function() {
        var component_id = $("#epa-milestones-component-id").val();
        var max_milestones = $("#max-milestones-" + component_id).val();
        var objective_title = $("#epa-milestones-objective-title").val();

        $("input[name^='epa_milestones']").each(function() {
            if (max_milestones > 0 && $("input[name^='epa_milestones']:checked").length >= max_milestones) {
                var localized_string = blueprints_index.Milestone_Selected.replace('%1', max_milestones).replace('%2', objective_title);
                var notice = $(document.createElement("div"))
                    .attr({"role": "alert","id" : "epa-milestones-notice"})
                    .addClass("alert alert-warning alert-dismissible")
                    .html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + localized_string);
                $("#epa-milestones-body").prepend(notice);
                window.setTimeout(function() {
                    $("#epa-milestones-notice").alert("close");
                }, notices_timeout);
                update_epa_milestones_checkboxes_lock_status();
                return false;
            } else {
                $(this).prop("checked", true);
            }
        });
        update_epa_milestones_checkboxes_lock_status();
    });

    $(document).on("click", "label", function() {
        var component_id = $("#epa-milestones-component-id").val();
        var objective_title = $("#epa-milestones-objective-title").val();

        if ($(this).find("input[name^='epa_milestones']:disabled").length) {
            var max_milestones = $("#max-milestones-" + component_id).val();
            var notice = $(document.createElement("div"))
                .attr({"role": "alert","id" : "epa-milestones-notice"})
                .addClass("alert alert-warning alert-dismissible")
                .html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + blueprints_index.Max_Milestones_Allowed.replace('%1', max_milestones).replace('%2', objective_title));

            $("#epa-milestones-body").prepend(notice);

            window.setTimeout(function() {
                $("#epa-milestones-notice").alert("close");
            }, notices_timeout);
        }
    });

    $(document).on("change", "input[name^='epa_milestones']", function() {
        update_epa_milestones_checkboxes_lock_status();
    });

    $(document).on("change", ".blueprint-epa-selection-form", function() {
        var component_id = $(this).closest(".blueprint-component-section").data("component_id");
        var max_milestones = $("#max-milestones-" + component_id).val();
        var allow_milestones_selection = $("#allow-milestones-selection-" + component_id).val();

        hide_next_components(component_id, 'contextual_variable_list');
        $("#publish-blueprint-button").addClass("hide");
        $("#display-complete").addClass("hide");
        $("#submit-button").removeClass("hide");

        if (allow_milestones_selection > 0) {
            $("#selected_epa_list_container li").each(function () {
                var epa_id = $(this).data("id");
                var selected_count = 0;

                if ($("#epa-selected-milestones-" + component_id + "-" + epa_id).length < 1) {
                    var badge = jQuery(document.createElement("a"));
                    badge.addClass("selected-milestones-badge badge").attr("id", "epa-selected-milestones-" + component_id + "-" + epa_id);
                    $(".blueprint-component-save-data").prop("disabled", true);
                    $(".blueprint-component-save-data").addClass("disabled");

                    $.ajax({
                        url: "?section=api-blueprints",
                        type: "GET",
                        data: "method=get-epa-milestones&epa_id=" + epa_id + "&course_id=" + COURSE_ID,
                        success: function (data) {
                            var jsonResponse = safeParseJson(data, "");
                            $.each(jsonResponse.data, function (i, objective) {
                                var hidden = jQuery(document.createElement("input"));
                                hidden.attr({
                                    "type": "hidden",
                                    "name": "milestones_" + component_id + "_" + epa_id + "[]"
                                }).val(objective.objective_id);

                                $("#update-blueprint-epa-selection-form-" + component_id).append(hidden);
                                selected_count++;
                                if (max_milestones > 0) {
                                    return selected_count < max_milestones;
                                }
                            });

                            $.ajax({
                                url: "?section=api-blueprints",
                                type: "GET",
                                data: "method=get-epa-milestones-count&epa_id=" + epa_id + "&course_id=" + COURSE_ID,
                                success: function (data) {
                                    var jsonResponse2 = safeParseJson(data, "");
                                    badge.text(selected_count + "/" + jsonResponse2.data);
                                    $(".blueprint-component-save-data").prop("disabled", false);
                                    $(".blueprint-component-save-data").removeClass("disabled");
                                }
                            });
                        }
                    });

                    $(this).append(" ").append(badge);
                }
            });
        }
    });

    $(document).on("click", ".remove-selected-list-item", function() {
        // Need to find a way to get the component id instead of 0;
        hide_next_components(0);
        $("#publish-blueprint-button").addClass("hide");
        $("#display-complete").addClass("hide");
        $("#submit-button").removeClass("hide");
    });

    $(document).on("click", "#epa-milestones-uncheck-all", function() {
        $("input[name^='epa_milestones']").prop("checked", false);
        update_epa_milestones_checkboxes_lock_status();
    });

    $(document).on("click", "#epa-milestones-modal-confirm", function () {
        var epa_id = $("#epa-milestones-epa-id").val();
        var component_id = $("#epa-milestones-component-id").val();

        var selected = $("input[name^='epa_milestones']:checked").length;
        var total = $("input[name^='epa_milestones']").length;
        $("#epa-selected-milestones-" + component_id + "-" + epa_id).text(selected + '/' + total);
        if (selected) {
            $("#epa-selected-milestones-" + component_id + "-" + epa_id).removeClass("blueprint-badge-missing");
        } else {
            $("#epa-selected-milestones-" + component_id + "-" + epa_id).addClass("blueprint-badge-missing");
        }
        $("input[name^='milestones_" + component_id + "_" + epa_id + "']").remove();
        $("input[name^='epa_milestones']:checked").each(function() {
            var hidden = jQuery(document.createElement("input"));
            hidden.attr({
                "type": "hidden",
                "name": "milestones_" + component_id + "_" + epa_id + "[]"
            }).val($(this).val());

            $("#update-blueprint-epa-selection-form-" + component_id).append(hidden);
        });

        $("#epa-milestones-modal").modal("hide");

    });

    $(document).on("click", "#epa_check_all", function() {
        $('input[name^="selected_epa"]').each(function() {
            $(this).prop("checked", true);
        })
    });

    $(document).on("click", "#epa_uncheck_all", function() {
        $('input[name^="selected_epa"]').each(function() {
            $(this).prop("checked", false);
        })
    });

    $("body").tooltip({
        selector: ".epa-tooltip",
        placement: "bottom"
    });

    $("#user-course-controls").removeClass("hide");

    /**
     * Contextual Variables specific section
     */
    $(document).on("change", ".cvars_checkbox", function() {
        var cvar_id = $(this).val();
        var set_no = $(this).closest("td").find(".input-hidden-set-no").val();
        var component_id = $(this).closest("table").data("component-id");
        var checkbox = $(this);

        if (checkbox.attr("checked")) {
            var selected_count = 0;
            $(".blueprint-component-save-data").prop("disabled", true);
            $(".blueprint-component-save-data").addClass("disabled");
            jQuery.ajax({
                url: "?section=api-blueprints",
                type: "GET",
                data: "method=get-child-objectives&parent_id=" + cvar_id +
                    "&course_id=" +jQuery("#course-id").val() +
                    "&form_type_id=" + jQuery("#form-type-id").val(),
                success: function(data) {
                    var jsonResponse = safeParseJson(data, "Unknown Server Error");
                    if (jsonResponse.status === "error") {
                        display_error([jsonResponse.data], "#blueprint-components-information-error-msg-" + component_id);
                    } else {
                        jsonResponse.data.each(function (objective) {
                            var hidden = jQuery(document.createElement("input"));
                            hidden.attr({
                                "type": "hidden",
                                "name": "cvariable_responses_" + component_id + "_" + set_no + "_" + cvar_id + "[]"
                            }).val(objective.target_id);

                            $("#update-blueprint-contextual-vars-selection-form-" + component_id).append(hidden);
                            selected_count++;
                        });

                        if ($("#contextual-var-" + component_id + "-" + set_no + "-" + cvar_id).attr("checked")) {
                            show_cvar_responses_badge(cvar_id, set_no, selected_count);
                        }

                        $(".blueprint-component-save-data").prop("disabled", false);
                        $(".blueprint-component-save-data").removeClass("disabled");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    display_error([blueprints_index.Error_Posting_Data + errorThrown], "#blueprint-components-information-error-msg-" + component_id);
                    $(".blueprint-component-save-data").prop("disabled", false);
                    $(".blueprint-component-save-data").removeClass("disabled");
                }
            });
        } else {
            $("input[name^='cvariable_responses_" + component_id + "_" + set_no + "_" + cvar_id + "']").remove();
            $("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).addClass("hide");
        }
    });

    $(document).on("click", ".selected-responses-badge", function() {
        var cvar_id = $(this).closest("div").find(".cvars_checkbox").val();
        var set_no = $(this).closest("td").find(".input-hidden-set-no").val();
        var component_id = $(this).closest("table").data("component-id");

        show_cvar_responses_modal(cvar_id, set_no, component_id);
    });

    $(document).on("click", "#contextual-variable-responses-modal-confirm", function() {
        var cvar_id = $("#cvar-response-cvar-id").val();
        var set_no = $("#cvar-response-set-no").val();
        var component_id = $("#cvar-response-component-id").val();

        var selected = $("input[name^='cvar_response']:checked").length;
        var total = $("input[name^='cvar_response']").length;

        $("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).text(selected + '/' + total);
        if (selected) {
            // Clear the missing class if present
            $("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).removeClass("blueprint-badge-missing");
        } else {
            $("#contextual-vars-selected-responses-" + set_no + "-" + cvar_id).addClass("blueprint-badge-missing");
        }

        $("input[name^='cvariable_responses_" + component_id + "_" + set_no + "_" + cvar_id + "']").remove();
        $("input[name^='cvar_response']:checked").each(function() {
            var hidden = jQuery(document.createElement("input"));
            hidden.attr({
                "type": "hidden",
                "name": "cvariable_responses_" + component_id + "_" + set_no + "_" + cvar_id + "[]"
            }).val($(this).val());

            $("#update-blueprint-contextual-vars-selection-form-" + component_id).append(hidden);
        });

        $("#contextual-variable-responses-modal").modal("hide");
    });

    $(document).on("click", "#contextual-variables-responses-check-all", function() {
        $("input[name^='cvar_response']").prop("checked", true);
    });

    $(document).on("click", "#contextual-variables-responses-uncheck-all", function() {
        $("input[name^='cvar_response']").prop("checked", false);
    });

    $(document).on("change", ".cvars_epas_checkbox", function() {
        var component_id = $(this).closest("table").data("component-id");
        var set_no = $(this).closest("tr").data("set");
        var epa_id = $(this).val();
        var count = $('input[name^="contextual_vars_epa_' + component_id + '_' + set_no + '"]').length;

        if ($(this).is(':checked')) {
            // Find the checked EPA in another card and remove it if it exists
            $.each($('input[name^="set_no_' + component_id + '"]'), function() {
                var input_set = $(this).val();

                if (input_set != set_no) {
                    if ($('input[name^="contextual_vars_epa_' + component_id + '_' + input_set + '"][value="' + epa_id + '"]').length) {
                        $(this).closest("tr").prev("tr").remove();
                        $(this).closest("tr").remove();
                    }
                }
            });
        } else {
            if (count < 2) {
                /**
                 * Cannot remove unless it's in a previous card, and removing it will only
                 * create another card with the exact same configuration.
                 */
                var does_exists = false;
                for (i = 1; i < set_no; i++) {
                    if ($('input[name^="contextual_vars_epa_' + component_id + '_' + i + '"][value="' + epa_id + '"]').length) {
                        does_exists = i;
                    }
                }

                if (!does_exists) {
                    display_error([blueprints_index.Cannot_Remove_EPA], "#blueprint-components-information-error-msg-" + component_id);
                    $(this).prop("checked", "true");
                    scroll_to_container("#blueprint-components-information-error-msg");
                } else {
                    $('input[name^="contextual_vars_epa_' + component_id + '_' + does_exists + '"][value="' + epa_id + '"]').prop("checked", true);
                    $(this).closest("tr").prev("tr").remove();
                    $(this).closest("tr").remove();
                }
            } else if (count > 1 && $('input[name^="contextual_vars_epa_' + component_id + '_' + set_no + '"]:checked').length < 1) {
                display_error([blueprints_index.Cannot_Remove_Last_EPA], "#blueprint-components-information-error-msg-" + component_id);
                $(this).prop("checked", "true");
                scroll_to_container("#blueprint-components-information-error-msg");
            } else {
                /**
                 * Create a new card with the unselected EPA
                 */
                var new_set = get_new_cvars_set_no(component_id);
                jQuery('<tr/>').loadTemplate("#contextual-vars-selector-header-row-template")
                    .appendTo("#table-contextual-vars-" + component_id)
                    .addClass("heading");

                var template_data = {
                    tpl_cvars_selector_tr_vars_td_id: "contextual-vars-list-td-" + component_id + "-" + new_set,
                    tpl_cvars_selector_tr_epas_td_id: "epa-list-td-" + component_id + "-" + new_set,
                    tpl_cvars_selector_set_no: 'input-hidden-set-no-' + component_id + '-' + new_set
                };

                jQuery('<tr/>').loadTemplate("#contextual-vars-selector-row-template", template_data)
                    .appendTo("#table-contextual-vars-" + component_id)
                    .data("set", new_set)
                    .attr("id", "contextual-vars-selector-row-" + component_id + "-" + new_set)
                    .addClass("contextual-vars-selector-row");

                $("#input-hidden-set-no-" + component_id + "-" + new_set).val(new_set);

                $.each($(this).closest("tr").find(".cvars_checkbox"), function() {
                    var var_id = $(this).val();
                    var label_text = $(this).parent().find("span").text();
                    var disabled = $(this).prop("disabled");
                    var template_data = {
                        tpl_cvars_selector_element_id: 'contextual-var-' + component_id + '-' + new_set + '-' + var_id,
                        tpl_cvars_selector_element_name: "contextual_vars_" + component_id + "_" + new_set + "[]",
                        tpl_cvars_selector_data_set: new_set,
                        tpl_cvars_checkbox_value: var_id,
                        tpl_cvars_label_content: label_text,
                        tpl_cvars_selected_responses_id: 'contextual-vars-selected-responses-' + new_set + '-' + var_id
                    };
                    jQuery('<div/>').loadTemplate("#contextual-vars-selector-template", template_data)
                        .appendTo("#contextual-vars-list-td-" + component_id + "-" + new_set);

                    if (disabled) {
                        $('#contextual-var-' + component_id + '-' + new_set + '-' + var_id).prop("disabled", true).prop("checked", true);
                        var selected_count = 0;
                        jQuery.ajax({
                            url: "?section=api-blueprints",
                            type: "GET",
                            data: "method=get-child-objectives&parent_id=" + var_id +
                                "&course_id=" +jQuery("#course-id").val() +
                                "&form_type_id=" + jQuery("#form-type-id").val(),
                            success: function(data) {
                                jsonResponse = safeParseJson(data, "");
                                jsonResponse.data.each(function(objective) {
                                    var hidden = jQuery(document.createElement("input"));
                                    parent_id = objective.target_parent;
                                    hidden.attr({
                                        "type": "hidden",
                                        "name": "cvariable_responses_" + component_id + "_" + new_set + "_" + parent_id + "[]"
                                    }).val(objective.target_id);

                                    $("#update-blueprint-contextual-vars-selection-form-" + component_id).append(hidden);
                                    selected_count++;
                                });

                                show_cvar_responses_badge(parent_id, new_set, selected_count);
                            }
                        });

                        var hidden = jQuery(document.createElement("input"));
                        hidden.attr({
                            "type" : "hidden",
                            "name" : "contextual_vars_" + component_id + "_" + new_set + "[]"
                        }).val(var_id);

                        $("#contextual-vars-list-td-" + component_id + "-" + new_set).append(hidden);
                    }
                });

                var template_data = {
                    tpl_cvars_epas_selector_element_id: 'contextual-epa-' + component_id + '-' + new_set + '-' + epa_id,
                    tpl_cvars_epas_selector_element_name: "contextual_vars_epa_" + component_id + "_" + new_set + "[]",
                    tpl_cvars_epas_selector_data_set: new_set,
                    tpl_cvars_epas_checkbox_value: epa_id,
                    tpl_cvars_epas_label_content: $(this).parent().find("span").text()
                }
                jQuery("<div/>").loadTemplate("#contextual-vars-epas-selector-template", template_data)
                    .appendTo("#epa-list-td-" + component_id + "-" + new_set);

            }
        }
    })

    /**
     * Scale selector specific section
     */
    /**
     * Render the scale
     */
    $(".scale-seletor-dropdown").on("change", function () {
        var component_id = $(this).closest("table").data("component-id");
        var selected_id = $(this).val();

        $("#scale-rendering-heading-" + component_id).removeClass("hide");
        $(".contextual-scale-descriptor-row-" + component_id).remove();

        $.ajax({
            url: "?section=api-blueprints",
            type: "GET",
            data: "method=get-scale-for-rendering&scale_id=" + selected_id,
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                if (jsonResponse.status == "success") {
                    $("#response-table-" + component_id).find("tr:gt(0)").remove();
                    jQuery.each(jsonResponse.data.responses, function(index, response) {
                        var descriptor = jsonResponse.data.descriptors[response.ardescriptor_id];
                        var template_data = {
                            tpl_scale_selector_input_id: "scale-selector-descriptor-" + component_id + "-" + descriptor.ardescriptor_id,
                            tpl_scale_selector_flag_id: "scale-selector-flag-" + component_id + "-" + descriptor.ardescriptor_id,
                            tpl_response_descriptor_text: descriptor.descriptor,
                            tpl_scale_selector_default_response_id: "scale-default-response-" + component_id + "-" + descriptor.ardescriptor_id
                        };
                        jQuery("<tr/>").loadTemplate("#scale-selector-repsonse-row-template", template_data).appendTo("#response-table-" + component_id);

                        $("#scale-selector-descriptor-" + component_id + "-" + descriptor.ardescriptor_id).val(descriptor.descriptor);
                        $("#scale-selector-flag-" + component_id + "-" + descriptor.ardescriptor_id).val(descriptor.ardescriptor_id);
                        $("#scale-selector-flag-" + component_id + "-" + descriptor.ardescriptor_id).addClass("text-center");
                        if (response.flag_response==1) {
                            $("#scale-selector-flag-" + component_id + "-" + descriptor.ardescriptor_id).prop("checked", true);
                        }
                        $("#scale-default-response-" + component_id + "-" + descriptor.ardescriptor_id).val(descriptor.ardescriptor_id);
                    });

                    $("input[name='scale_default_reponse']:first").prop("checked", "cheched");
                    $(".no-response-flags").addClass("hide");
                    $("#response-table-" + component_id).removeClass("hide");
                    toggle_default_repsonse_column(component_id);
                } else {
                    display_error(jsonResponse.data, "#blueprint-components-information-error-msg-" + component_id);
                    scroll_to_container("#blueprint-components-information-error-msg");
                }
            }
        });
    });

    function toggle_default_repsonse_column(component_id) {
        if ($("#allow_default_" + component_id).prop("checked")) {
            $(".td_default_selection_" + component_id).removeClass("hide");
        } else {
            $(".td_default_selection_" + component_id).addClass("hide");
        }
    }

    $(document).on("change", ".allow_default_checkbox", function() {
        var component_id = $(this).closest("table").data("component-id");

        toggle_default_repsonse_column(component_id);
    });

    /**
     * Called when the save component button is clicked for each component section
     * It determines the method to use for the API from the data-method tag on the
     * button, and post form data for the component based on the id specified by
     * the data-component-id from the containing table.
     */
    $(document).on("click", ".blueprint-component-save-data", function() {
        var component_id = $(this).closest("table").data("component-id");

        if ($(this).data("method") == "update-blueprint-free-text-element") {
            // get CKEditor data
            $("#element-text-" + component_id).val(CKEDITOR.instances['element-text-' + component_id].getData());
        }

        var form_data = $(this).closest("form").serialize();
        form_data += "&component_id=" + component_id + "&method=" + $(this).data("method");

        // Clear previous errors
        $(".blueprint-components-information-error-msg").html("");
        hide_next_components(component_id);

        // Show overlay
        $("#blueprint-page-loading-overlay").show();

        $.ajax({
            url: "?section=api-blueprints",
            type: "POST",
            data: form_data,
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");

                if (jsonResponse.status == "success") {
                    if (jsonResponse.component_id) {
                        $("#publish-blueprint-button").addClass("hide");
                        $("#display-complete").addClass("hide");
                        switch (jsonResponse.component_type) {
                            case "contextual_variable_list":
                                display_contextual_variables_component(
                                    jsonResponse.component_id,
                                    jsonResponse.epas_desc,
                                    jsonResponse.vars_desc,
                                    jsonResponse.contextual_variables,
                                    jsonResponse.init_data,
                                    jsonResponse.component_settings
                                );
                                break;

                            case "ms_ec_scale":
                            case "entrustment_scale":
                                display_scale_selector_component(jsonResponse.component_id);
                                break;

                            case "free_text_element":
                                display_free_text_component(jsonResponse.component_id);
                                break;

                            case "role_selector":
                                display_roles_selector_component(jsonResponse.component_id);
                                break;
                        }
                    } else {
                        // Form is now complete, so let's allow publishing
                        $("#publish-blueprint-button").removeClass("hide");
                        $("#display-complete").removeClass("hide");
                        $("#submit-button").addClass("hide");
                        scroll_to_container("#display-complete");
                    }
                } else {
                    if (Array.isArray(jsonResponse.data)) {
                        var error_msg = jsonResponse.data;
                    } else {
                        var error_msg = new Array(jsonResponse.data);
                    }
                    display_error(error_msg, "#blueprint-components-information-error-msg-" + component_id);
                    scroll_to_container("#blueprint-components-information-error-msg-" + component_id);
                    if (typeof jsonResponse.missing_data !== 'undefined') {
                        highlight_missing(component_id);
                    }
                }

                $("#blueprint-page-loading-overlay").hide();
            },
            error: function ( jqXHR, textStatus, errorThrown ) {
                $("#blueprint-page-loading-overlay").hide();
                display_error([blueprints_index.Error_Posting_Data + errorThrown], "#blueprint-components-information-error-msg-" + component_id);
            }
        });
    });

    /**
     * Publishing
     */
    $("#publish-blueprint-button").on("click", function(e){
        e.preventDefault();
        $("#blueprint-page-loading-overlay").show();

        $.ajax({
            url: "?section=api-blueprints",
            type: "POST",
            data: {
                method: "publish-blueprint",
                publish: 1,
                form_blueprint_id: $("#form-blueprint-id").val()
            },
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                if (jsonResponse.status == "success") {
                    $("#blueprint-components-information-error-msg").html("");
                    $("#display-published").removeClass("hide");
                    $("#display-complete").addClass("hide");
                    $("#publish-blueprint-button").remove();
                    hide_next_components(-1); // Add overlay on all components sections
                    $("#course-id").prop("disabled", true);
                    $("#blueprint-include-instructions").prop("disabled", true);
                    $("#form-blueprint-instruction").prop("disabled", true);
                    $("#cke_form-blueprint-instruction").prop("disabled", true);
                    scroll_to_container("#display-published");
                } else {
                    display_error(jsonResponse.data, "#blueprint-components-information-error-msg");
                    scroll_to_container("#blueprint-components-information-error-msg");
                }

                $("#blueprint-page-loading-overlay").hide();
            },
            error: function ( jqXHR, textStatus, errorThrown ) {
                display_error([blueprints_index.Error_Posting_Data + errorThrown], "#blueprint-components-information-error-msg");
                scroll_to_container("#blueprint-components-information-error-msg");

                $("#blueprint-page-loading-overlay").hide();
            }
        });
    });

    /**
     * General Admin functions
     */
    get_blueprints();

    $("#form-type").on("change", function () {
        if ($("#user-course-controls").length > 0) {
            var course_related = $("#form-type").find(':selected').data('course-related');
            if (course_related == "1") {
                $("#user-course-controls").removeClass("hide");
            } else {
                $("#user-course-controls").addClass("hide");
            }
        }
    });

    $(".remove-single-filter").on("click", function (e) {
        e.preventDefault();

        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        var remove_filter_request = $.ajax({
            url: "?section=api-blueprints",
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
            url: "?section=api-blueprints",
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

    $("#blueprint-search").keydown(function (e) {
        var keycode = e.keyCode;
        if ((keycode > 47 && keycode < 58) ||
            (keycode > 64 && keycode < 91) ||
            (keycode > 95 && keycode < 112) ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13 || keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }

            total_forms = 0;
            form_offset = 0;
            show_loading_message = true;

            clearTimeout(timeout);
            timeout = window.setTimeout(get_blueprints, 700);
        }
    });

    $("#load-blueprints").on("click", function (e) {
        e.preventDefault();
        if (!$(this).attr("disabled")) {
            get_blueprints();
        }
    });

    $("#author-list").on("click", ".remove-permission", function(e) {
        var remove_permission_btn = $(this);

        $.ajax({
            url: API_URL,
            data: "method=remove-permission&afbauthor_id=" + remove_permission_btn.data("afbauthor-id"),
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

    $('#delete-form-blueprint-modal').on('show.bs.modal', function (e) {
        $('#delete-form-blueprints-modal').removeClass("hide");
        $("#msgs").html("");
        $("#form-blueprints-selected").addClass("hide");
        $("#no-form-blueprints-selected").addClass("hide");

        var forms_to_delete = $("#form-table-form input[name='forms[]']:checked").map(function () {
            return this.value;
        }).get();

        if (forms_to_delete.length > 0) {
            $("#form-blueprints-selected").removeClass("hide");
            $("#delete-form-blueprints-modal-delete").removeClass("hide");
            var list = document.createElement("ul");
            $("#form-table-form input[name='forms[]']:checked").each(function(index, element) {
                var list_item = document.createElement("li");
                var form_id = $(element).val();
                $(list_item).append($("#formblueprint_title_link_" + form_id).html());
                $(list).append(list_item);
            });
            $("#delete-form-blueprints-container").append(list);
        } else {
            $("#no-form-blueprints-selected").removeClass("hide");
            $("#delete-forms-modal-delete").addClass("hide");
        }
    });

    $('#delete-form-blueprint-modal').on('hide.bs.modal', function (e) {
        $('#delete-form-blueprints-modal').addClass("hide");
        $("#delete-form-blueprints-container").html("");
    });

    $("#form-table-form").tooltip({
        selector: ".search-target-label-text",
    });

    $("#delete-form-blueprints-modal-delete").on("click", function(e) {
        e.preventDefault();
        var url = $("#delete-form-blueprint-modal-form").attr("action");
        var forms_to_delete = $("#form-table-form input[name='forms[]']:checked").map(function () {
            return this.value;
        }).get();

        var form_data = {
            "method" : "delete-form-blueprints",
            "delete_ids" : forms_to_delete
        };

        $("#forms-selected").removeClass("hide");
        $("#delete-form-blueprints-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, form_data, function(data) {
                if (data.status == "success") {
                    $(data.form_ids).each(function(index, element) {
                        $("input[name='forms[]'][value='" + element + "']").parent().parent().remove();
                        display_success([data.msg], "#msgs")
                    });
                    var localized_str = blueprints_index.Showing_Of_Forms
                        .replace('%1', (parseInt(jQuery("#load-blueprints").html().split(" ")[1]) - data.form_ids.length))
                        .replace('%2', (parseInt(jQuery("#load-blueprints").html().split(" ")[3]) - data.form_ids.length));
                    jQuery("#load-blueprints").html(localized_str);
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        ) .done(function(data) {
            $('#delete-form-blueprint-modal').fadeOut(50);
            location.reload();
        });
    });

    $("#copy-form-blueprint").on("click", function (e) {
        e.preventDefault();
        var form_blueprint_id = $(this).attr("data-form-blueprint-id");
        var new_form_title = $("#new-form-blueprint-title").val();
        var new_course_id = $("#new-course-id").val();

        // Make a copy request to the Form API
        var copy_form_request = $.ajax({
            url: "?section=api-blueprints",
            data: "method=copy-form-blueprint&form_blueprint_id=" + form_blueprint_id + "&new_form_title=" + new_form_title + "&new_course_id=" + new_course_id,
            type: "POST",
            beforeSend: function () {
                $("#copy-form-blueprint").prop("disabled", true);
            },
            error: function () {
                display_error(["The action could not be completed. Please try again later"], "#copy-form-blueprint-msgs");
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
                    display_error([jsonResponse.data], "#copy-form-blueprint-msgs");
                    $("#copy-form-blueprint").removeProp("disabled");
                }
            }
        });
    });

    $("#blueprint-include-instructions").on("change", function() {
        if ($(this).prop("checked")) {
            $("#blueprint-instructions-div").removeClass("hide");
        } else {
            $("#blueprint-instructions-div").addClass("hide");
        }
    });
});