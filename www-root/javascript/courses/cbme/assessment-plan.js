/**
 * JS for handling Assessment Plans
 */
jQuery(document).ready(function ($) {
    var bind_plan_tools = true;
    var safe_assessment_tools = null;
    try {
        safe_assessment_tools = JSON.parse(assessment_tools); // The undefined throw will be caught
    } catch (e) {
        bind_plan_tools = false;
    }

    /**
     * Assessment Tools advancedSearch widget
     */
    if (bind_plan_tools) {
        $("#assessment-tool-msgs").html(javascript_translations.no_assessment_tools_label).addClass("hide");
        $("#assessment_plan_tools").advancedSearch({
            filters: {
                assessment_forms: {
                    label: javascript_translations.assessment_tools_label,
                    data_source: safe_assessment_tools,
                    mode: "checkbox",
                    selector_control_name: "assessment_forms",
                    search_mode: false
                }
            },
            build_selected_filters: false,
            control_class: "assessment-form-selector",
            list_selections: false,
            no_results_text: javascript_translations.no_assessment_tools_label,
            parent_form: $("#assessment-plan-form"),
            width: 400,
            modal: false
        });
    } else {
        $("#assessment_plan_tools").attr("disabled", "disabled");
        $("#assessment-tool-msgs").html(javascript_translations.no_assessment_tools_label).removeClass("hide");

    }

    /**
     * Instantiate necessary contextual variable widgets
     */
    build_contextual_variable_widgets();

    /**
     * Instantiate necessary contextual variable response widgets
     */
    build_contextual_variable_response_widgets();

    /**
     * Event listener for the assessment tool picker
     */
    $(document).on("change", "input[id*=assessment_forms_target_]", function () {
        var form_id = $(this).val();
        if ($(this).is(":checked")) {
            fetch_contextual_variables(form_id, true);
        } else {
            remove_form_container(form_id);
        }
    });

    /**
     * Event listener for the contextual variable picker
     */
    $(document).on("change", "input[id*=contextual_variables_target_]", function () {
        var form_id = $(this).closest(".assessment-plan-container").attr("data-id");
        var objective_id = $(this).val();
        if ($(this).is(":checked")) {
            fetch_contextual_variable_responses(form_id, objective_id, true);
        } else {
            remove_contextual_variable_container(form_id, objective_id);
        }
    });

    /**
     * Event listener for contextual
     */
    $(document).on("change", "input[id*=responses_target_]", function () {
        var form_id = $(this).closest(".assessment-plan-container").attr("data-form-id");
        var objective_id = $(this).closest(".assessment-plan-container").attr("data-id");
        var response_objective_id = $(this).val();
        if ($(this).is(":checked")) {
            build_contextual_variable_response(objective_id, form_id, $(this));
        } else {
            remove_contextual_variable_response(form_id, objective_id, response_objective_id);
        }
    });

    /**
     * Event listener for removing selected form tools
     */
    $(document).on("click", ".remove-form-btn", function (e) {
        var form_id = $(this).attr("data-form-id");
        remove_form_container(form_id);
        $(this).parent().parent().remove();
        e.preventDefault();
    });

    /**
     * Event listener for removing selected contextual variables
     */
    $(document).on("click", ".remove-contextual-variable-btn", function (e) {
        var form_id = $(this).attr("data-form-id");
        var contextual_variable_id = $(this).attr("data-contextual-variable-id");
        remove_contextual_variable_container(form_id, contextual_variable_id);
        $(this).parent().parent().remove();
        e.preventDefault();
    });

    /**
     * Event listener for removing selected contextual variable responses
     */
    $(document).on("click", ".remove-contextual-variable-response-btn", function (e) {
        var form_id = $(this).attr("data-form-id");
        var contextual_variable_id = $(this).attr("data-contextual-variable-id");
        var contextual_variable_response_id = $(this).attr("data-contextual-variable-response-id");
        remove_contextual_variable_response(form_id, contextual_variable_id, contextual_variable_response_id);
        $(this).parent().parent().remove();
        e.preventDefault();
    });

    /**
     * Event listener for when the delete plan modal is shown - populates a list of selected plans for deletion
     */
    $("#delete-plan-modal").on("show", function () {
        build_delete_plan_controls();
    });

    /**
     * Event listener for when the delete plan modal is hidden - resets the modal
     */
    $("#delete-plan-modal").on("hide", function () {
        reset_delete_modal();
    });

    /**
     * Build the necessary UI components for deleting an assessment plan
     */
    function build_delete_plan_controls() {
        $("#plans-selected").removeClass("hide");
    }

    /**
     * Reset the delete modal
     */
    function reset_delete_modal() {
        $("#delete-plans-list").empty();
        $("#no-plans-selected").addClass("hide");
        $("#plans-selected").addClass("hide");
    }

    /**
     * Binds an advancedSearch instance to a DOM object - should be a button
     * @param identifier
     * @param objectives
     * @param type
     */
    function bind_advanced_search(identifier, objectives, type, data_type, mode) {
        if ($("#" + identifier).length > 0) {
            var datasource = build_advanced_search_datasource(objectives, data_type);
            $("#" + identifier).advancedSearch({
                filters: {},
                build_selected_filters: false,
                control_class: identifier + "-selector",
                list_selections: false,
                no_results_text: javascript_translations.no_assessment_form_cv_label,
                parent_form: $("#assessment-plan-form"),
                width: 400,
                modal: false
            });

            var settings = $("#" + identifier).data("settings");
            settings.filters[identifier] = {
                label: javascript_translations.assessment_tools_cv_label,
                data_source: datasource,
                mode: mode,
                selector_control_name: identifier
            };
        }
    }

    /**
     * Builds a datasource to be used by an instance of advancedSearch
     * @param data
     * @returns {{}}
     */
    function build_advanced_search_datasource(data, data_type) {
        var datasource = {};
        if (data.length > 0) {
            count = 0;
            $.each(data, function (i, datasource_item) {
                switch (data_type) {
                    case "objective":
                        datasource[count] = {target_id: datasource_item.objective_id, target_label: datasource_item.objective_name};
                        break;
                    case "rating_scale_response":
                        datasource[count] = {target_id: datasource_item.iresponse_id, target_label: datasource_item.text};
                        break;
                }
                count ++;
            });
        }
        return datasource;
    }

    /**
     * Fetch contextual variables
     * @param form_id
     * @param load_template_flag
     */
    function fetch_contextual_variables(form_id, load_template_flag) {
        var form_id = form_id;
        var objective_id = $("#objective-id").val();
        var form_objective_request = $.ajax({
            url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
            data: "method=get-form-objectives&form_id=" + form_id + "&objective_id=" + objective_id,
            type: "GET",
            beforeSend: function () {
            },
            complete: function () {
            },
            error: function () {
            }
        });

        $.when(form_objective_request).done(function (data) {
            var jsonResponse = safeParseJson(data, javascript_translations.parse_error);
            var cv_advanced_search_id = "form_" + jsonResponse.data.form_id + "_contextual_variables";

            if (load_template_flag) {
                /**
                 * Populate an data object that the template file will use
                 */
                var template_options = {};
                template_options.assessment_tool_title = jsonResponse.data.form_title;
                template_options.cv_advanced_search_id = cv_advanced_search_id;
                template_options.minimum_identifier = "form-" + jsonResponse.data.form_id + "-minimum";
                template_options.minimum_assessors_identifier = "form-" + jsonResponse.data.form_id + "-minimum-assessors";
                template_options.minimum_name = "form_" + jsonResponse.data.form_id + "_minimum";
                template_options.minimum_assessors_name = "form_" + jsonResponse.data.form_id + "_minimum_assessors";
                template_options.form_id = jsonResponse.data.form_id;
                template_options.form_rating_scale_response_identifier = "form_" + jsonResponse.data.form_id + "_rating_scale_response";
                template_options.form_rating_scale_response_name = "form_" + jsonResponse.data.form_id + "_rating_scale_response";

                $("<div/>").loadTemplate(
                    "#assessment-plan-tool-template", template_options).addClass("assessment-plan-container space-below medium clearfix").attr({
                    id: "form-" + jsonResponse.data.form_id + "-container",
                    "data-id": jsonResponse.data.form_id
                }).appendTo("#assessment-tools");
            }

            bind_advanced_search(cv_advanced_search_id, jsonResponse.data.objectives, "form_"+jsonResponse.data.form_id  +"_contextual_variables", "objective", "checkbox");

            /**
             * If there are rating scale responses then add the required data to the template_options object
             */
            if (jsonResponse.data.hasOwnProperty("entrustment_item")) {
                if (jsonResponse.data.entrustment_item.hasOwnProperty("responses")) {
                    bind_advanced_search("form_" + jsonResponse.data.form_id + "_rating_scale_response", jsonResponse.data.entrustment_item.responses, "form_"+jsonResponse.data.form_id  +"_rating_scale_response", "rating_scale_response", "radio");
                }
            }
        });
    }

    function build_responses(responses) {
        var list = [];
        $.each(responses, function (i, response) {
            list.push(response.text);
        });
        return list;
    }

    /**
     * Fetch responses for a contextual variable
     * @param form_id
     * @param objective_id
     * @param load_template_flag
     */
    function fetch_contextual_variable_responses(form_id, objective_id, load_template_flag) {
        var form_id = form_id;
        var objective_id = objective_id;
        var course_id = $("#course-id").val();
        var cv_response_request = $.ajax({
            url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
            data: "method=get-cv-responses&objective_id=" + objective_id + "&course_id=" + course_id,
            type: "GET",
            beforeSend: function () {
            },
            complete: function () {
            },
            error: function () {

            }
        });

        $.when(cv_response_request).done(function (data) {
            var jsonResponse = safeParseJson(data, javascript_translations.parse_error);
            var contextual_response_advanced_search_id = "form_"+ form_id +"_cv_" + jsonResponse.data.objective_id + "_responses";
            if (load_template_flag) {
                $("<div/>").loadTemplate(
                    "#assessment-plan-cv-response-template", {
                        contextual_variable_title: jsonResponse.data.objective_name,
                        contextual_response_advanced_search_id: contextual_response_advanced_search_id,
                        form_id: form_id,
                        contextual_variable_id : jsonResponse.data.objective_id
                    }).addClass("assessment-plan-container space-below medium clearfix").attr({
                    id: "form-" + form_id + "-contextual-variable-" + jsonResponse.data.objective_id + "-container",
                    "data-id": jsonResponse.data.objective_id,
                    "data-form-id": form_id
                }).insertBefore("#form-" + form_id + "-container .remove-form-container");
            }
            bind_advanced_search(contextual_response_advanced_search_id, jsonResponse.data.objectives, "form-"+ form_id +"-contextual-variable-responses", "objective", "checkbox");
        });
    }

    /**
     * Build contextual variable response
     * @param objective_id
     * @param form_id
     * @param input
     */
    function build_contextual_variable_response(objective_id, form_id, input) {
        var contextual_variable_response_id = $(input).val();
        var contextual_variable_id = objective_id;
        var contextual_variable = $(input).attr("data-label");

        if ($("#form-"+ form_id +"-contextual-variable-" + contextual_variable_id + "-response-list").length == 0) {
            var list = $(document.createElement("ul")).attr({id: "form-"+ form_id +"-contextual-variable-" + contextual_variable_id + "-response-list"}).addClass("list-set space-below medium contextual-variable-response-list");
            list.insertBefore("#form-"+ form_id +"-contextual-variable-"+ contextual_variable_id +"-container .remove-contextual-variable-container");
        }

        if ($("#form-"+ form_id +"-contextual-variable-response-" + contextual_variable_response_id + "-item").length == 0) {
            $("<li/>").loadTemplate("#assessment-plan-cv-response-item-template", {
                contextual_variable_response: contextual_variable,
                contextual_variable_response_identifier: "form-"+ form_id +"-contextual-variable-response-" + contextual_variable_response_id,
                contextual_variable_response_name: "form_"+ form_id +"_contextual_variable_response_"+ contextual_variable_response_id,
                form_id: form_id,
                contextual_variable_id : contextual_variable_id,
                contextual_variable_response_id: contextual_variable_response_id
            }).addClass("list-set-item").appendTo("#form-"+ form_id +"-contextual-variable-"+ contextual_variable_id +"-response-list").attr({id: "form-"+ form_id +"-contextual-variable-response-" + contextual_variable_response_id + "-item"});
        }
    }

    /**
     * Instantiate necessary contextual variable advancedSearch widgets after the page has loaded - triggered on edit or error state.
     */
    function build_contextual_variable_widgets() {
        $.each($(".contextual-variable-widget"), function (i, widget) {
            var form_id = $(this).attr("data-form-id");
            fetch_contextual_variables(form_id, false);
        });
    }

    /**
     * Instantiate necessary contextual variable response advancedSearch widgets after the page has loaded - triggered on edit or error state.
     */
    function build_contextual_variable_response_widgets() {
        $.each($(".contextual-variable-response-widget"), function (i, widget) {
            var form_id = $(this).attr("data-form-id");
            var objective_id = $(this).attr("data-objective-id");
            fetch_contextual_variable_responses(form_id, objective_id, false);
        });
    }

    /**
     * Remove the form template and its matching advancedSearch generated hidden input from the UI.
     * @param form_id
     */
    function remove_form_container(form_id) {
        $("#assessment_forms_" + form_id).remove();
        $("#form-" + form_id + "-container").remove();
        $("input[name=\"form_"+ form_id +"_rating_scale_response\"]").remove();
        $("input[id*=form_"+ form_id +"_contextual_variables_]").remove();
        $("input[id*=form_"+ form_id +"_cv_]").remove();
    }

    /**
     * Remove the contextual variable template and its matching advancedSearch generated hidden inputs from the UI.
     * @param form_id
     * @param objective_id
     */
    function remove_contextual_variable_container(form_id, objective_id) {
        $("#form-"+ form_id +"-contextual-variable-"+ objective_id +"-container").remove();
        $("#form_" + form_id + "_contextual_variables_" + objective_id).remove();
        $("input[id*=form_"+ form_id +"_cv_" + objective_id + "_responses_]").remove();
    }

    /**
     * Remove a contextual variable response from the UI
     * @param form_id
     * @param objective_id
     * @param $response_objective_id
     */
    function remove_contextual_variable_response(form_id, objective_id, $response_objective_id) {
        $("#form_" + form_id + "_cv_" + objective_id + "_responses_" + $response_objective_id).remove();
        $("#form-" + form_id + "-contextual-variable-response-"+ $response_objective_id +"-item").remove();
        if ($("#form-"+ form_id +"-contextual-variable-"+ objective_id +"-response-list li").length == 0) {
            $("#form-"+ form_id +"-contextual-variable-"+ objective_id +"-response-list").remove();
        }
    }
});
