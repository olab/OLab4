jQuery(document).ready(function ($) {
    /**
     * Gets the filters in the state they were in when the page loads
     */
    var filters = $("#cbme-filters").serialize();

    /**
     * Event listener for filter toggles
     */
    $(".collapsed-filter").each(function() {
        $(this).find(".collapsed-filter-toggle").on("click", function() {
            $(this).toggleClass("open");
            $(this).closest(".collapsed-filter").find(".filter-options").slideToggle(250);
        });
    });

    /**
     * Event listener for toggling the expand and collapse of all CBME filters
     */
    $(".toggle-all-filters").on("click", function (e) {
        $(this).toggleClass("open");
        $("#filter-wrapper").slideToggle(250);
        e.preventDefault();
    });

    /**
     * Show hide filters
     */
    $("#list-filter-toggle").on("click", function() {
        $("#filter-options").slideToggle(250);
        $(this).toggleClass("open");
    });

    /**
     * Assessor selector advancedSearch instance
     */
    $("#select-user-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filters : {
            selected_users : {
                label : cbme_assessments.user_filter,
                data_source: "get-faculty",
                selector_control_name: "selected_users",
                search_mode: false,
            }
        },
        lazyload: true,
        control_class: "user-selector",
        no_results_text: cbme_assessments.no_user_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false
    });

    /**
     * EPA selector advancedSearch instance
     */
    $("#select-epa-btn").advancedSearch({
        filters : {
            epas : {
                label : cbme_assessments.epa_filter,
                data_source : advanced_search_epas,
                selector_control_name: "course_epa",
                search_mode: false
            }
        },
        control_class: "course-epa-selector",
        no_results_text: cbme_assessments.no_epa_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false,
        select_all_enabled: true
    });

    /**
     * Role selector advancedSearch instance
     */
    $("#select-role-btn").advancedSearch({
        filters: {
            roles: {
                label: cbme_assessments.role_filter,
                data_source: advanced_search_roles,
                selector_control_name: "canmed_role",
                search_mode: false
            }
        },
        control_class: "canmed-role-selector",
        no_results_text: cbme_assessments.no_role_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false,
    });

    /**
     * Milestone selector advancedSearch instance
     */
    $("#select-milestone-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filters: course_stage_filters,
        control_class: "milestone-selector",
        no_results_text: cbme_assessments.no_milestone_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false,
    });

    /**
     * Contextual Variable selector advancedSearch instance
     */
    $("#select-cv-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filters: {
            contextual_variables: {
                label: cbme_assessments.contextual_variable_filter,
                data_source: "get-contextual-variables",
                selector_control_name: "contextual_variables[]",
            }
        },
        control_class: "contextual-variable-selector",
        no_results_text: cbme_assessments.no_milestone_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false,
    });

    /**
     * Contextual Variable response selector advancedSearch instance
     */
    $("#select-cv-responses-btn").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        control_class: "contextual-variable-response-selector",
        filters: {},
        no_results_text: cbme_assessments.no_contextual_variable_responses_response,
        width: 300,
        parent_form: $("#cbme-filters"),
        list_selections: false,
    });

    build_contextual_variable_responses();

    /**
     * Event listener for selected contextual variables. Populates filters for the contextual variable response advancedSearch widget
     */
    $(".collapsed-filter").on("change", "input[id^=\"contextual_variables_target_\"]", function () {
        build_contextual_variable_responses();
    });

    $(".collapsed-filter").on("click", ".remove-target-toggle", function () {
        build_contextual_variable_responses();
    });

    /**
     * Adds tooltips to advancedSearch widget instances
     */
    $("#cbme-filters").tooltip({
        selector: ".search-target-label-text",
    });

    /**
     * Adds tooltips to rating scale responses
     */
    $(document).tooltip({
        selector: ".fa-exclamation-circle"
    });

    /**
     * Adds tooltips to rating scale responses for items
     */
    $("#item-cards").tooltip({
        selector: ".fa-circle"
    });

    /**
     * Event listener for the rating scale selector
     */
    $("#rating-scales").on("change", function () {
        var selected_scale_id = $(this).val();
        $(".scale-response-container").addClass("hide");
        $("#rating-scale-" + selected_scale_id + "-responses").removeClass("hide");
    });

    /**
     * Display the appropriate rating scale responses on page load
     */
    var selected_rating_scale_id = $("#rating-scales").val();
    $("#rating-scale-" +selected_rating_scale_id + "-responses").removeClass("hide");

    /**
     * Date picker instantiation
     */
    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    /**
     * Date picker toggle for calendar icons
     */
    $(".add-on").on("click", function() {
        if ($(this).siblings("input").is(":enabled")) {
            $(this).siblings("input").focus();
        }
    });

    /**
     * Event listener for setting assessment filter preferences
     */
    $(".collapsed-filter-toggle").on("click", function (e) {
        var filter_type = $(this).attr("data-filter-type");
        var preference = "collapsed";

        if ($(this).hasClass("open")) {
            preference = "expanded";
        }

        set_view_preference(filter_type, preference);
        e.preventDefault();
    });

    /**
     * Event listener for setting all assessment filter preferences
     */
    $(".toggle-all-filters ").on("click", function (e) {
        var filter_type = $(this).attr("data-filter-type");
        var preference = "collapsed";

        if ($(this).hasClass("open")) {
            preference = "expanded";
        }

        set_view_preference(filter_type, preference);
        e.preventDefault();
    });

    /**
     * Event listener for the load more assessments button
     */
    $("#show-more-assessments-btn").on("click", function (e) {
        var limit = parseInt($("#show-more-assessments-btn").attr("data-limit"));
        var offset = parseInt($("#show-more-assessments-btn").attr("data-offset"));
        var course_id = $("input[name=\"course_id\"]").val();
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var pinned_only = $("input[name=\"pinned_only\"]").val();
        var secondary_proxy_id = $("input[name=\"secondary_proxy_id\"]").val();
        var assessment_type = $(this).attr("data-card-type");
        var is_admin_view = $("input[name=\"is_admin_view\"]").val();

        if (offset == 0) {
            var offset = offset + query_limit;
        }

        get_assessments(course_id, filters, limit, offset, proxy_id, pinned_only, secondary_proxy_id, assessment_type, is_admin_view);
        e.preventDefault();
    });

    /**
     * Event listener for the load more comments button
     */
    $("#show-more-comments-btn").on("click", function (e) {
        var limit = parseInt($("#show-more-comments-btn").attr("data-limit"));
        var offset = parseInt($("#show-more-comments-btn").attr("data-offset"));
        var course_id = $("input[name=\"course_id\"]").val();
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var pinned_only = $("input[name=\"pinned_only\"]").val();
        if (offset == 0) {
            var offset = offset + query_limit;
        }

        get_assessment_comments(course_id, filters, limit, offset, proxy_id, pinned_only);
        e.preventDefault();
    });

    /**
     * Event listener for the load more items button
     */
    $("#show-more-items-btn").on("click", function (e) {
        var limit = parseInt($("#show-more-items-btn").attr("data-limit"));
        var offset = parseInt($("#show-more-items-btn").attr("data-offset"));
        var course_id = $("input[name=\"course_id\"]").val();
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var pinned_only = $("input[name=\"pinned_only\"]").val();
        var secondary_proxy_id = $("input[name=\"secondary_proxy_id\"]").val();

        if (offset == 0) {
            var offset = offset + query_limit;
        }

        get_items(course_id, filters, limit, offset, proxy_id, pinned_only, secondary_proxy_id);
        e.preventDefault();
    });

    /**
     * Event listener for the apply filters button - resets the offset that is used when fetching assessments
     */
    $(".apply-filter-options").on("click", function () {
        $("input[name=\"limit\"]").val(query_limit);
        $("input[name=\"offset\"]").val(0);
    });

    /**
     * Event listener for the reset filters button - hides rating scale responses
     */
    $("#reset-filter-options").on("click", function () {
        $(".scale-response-container").addClass("hide");
    });

    /**
     * Event listener for removing filtered items
     */
    $(".remove-filter").on("click", function (e) {
        var filter_control = $(this).parent().attr("data-filter-control");
        $("#" + filter_control + "_checkbox").attr("checked", false);
        $("#" + filter_control).remove();
        $("[name=" + filter_control + "]").val(null);
        $("#cbme-filters").submit();
        e.preventDefault();
    });

    /**
     * Check/Unchecked shared response descriptors accross scales types.
     */
    $("input[name^='descriptors']").on("click", function() {
        $("input[name^='descriptors'][value='" + $(this).val() + "']").prop("checked", $(this).is(":checked"));
    });

    $('.match-height').matchHeight({
         byRow: true,
         property: 'height',
         target: null,
         remove: false
     });

    /**
     * Handles filter view preferences
     * @param preference
     * @param filter_type
     */
    function set_view_preference(filter_type, preference) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-assessment-filter-view-preference", "preference": preference, "filter_type": filter_type}
        });
    }

    /**
     * Fetches filtered assessments via ajax
     * @param course_id the learner course
     * @param filters the user selected filters
     */
    function get_assessments(course_id, filters, limit, offset, proxy_id, pinned_only, secondary_proxy_id, assessment_type, is_admin_view) {
        var assessment_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "GET",
            data: {"method": "get-assessments", "course_id": course_id, "filters": filters, "limit": limit, "offset": offset, "proxy_id": proxy_id, "pinned_only": pinned_only, "secondary_proxy_id" : secondary_proxy_id, "assessment_type": assessment_type}
        });

        $.when(assessment_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "Unknown Error");
            if (jsonResponse.status === "success") {
                $.each(jsonResponse.data, function (i, assessment) {
                    if (assessment_type === "completed") {
                        var list_item = $("<li/>").loadTemplate("#assessment-card-template", {
                            form_type: assessment.form_type,
                            form_title: assessment.title,
                            created_date: assessment.encounter_date ? assessment.encounter_date : assessment.created_date,
                            comment_response: assessment.comment_response,
                            assessor: assessment.assessor,
                            entrustment_response_descriptor: assessment.entrustment_response_descriptor,
                            assessment_url: ENTRADA_URL + "/assessments/assessment?dassessment_id=" + assessment.dassessment_id,
                            dassessment_id: assessment.dassessment_id,
                            pin_id: assessment.pin_id,
                            aprogress_id: assessment.aprogress_id,
                            pinned_class: assessment.is_pinned ? "pin-assessment pinned list-card-icon fa fa-thumb-tack" : "pin-assessment list-card-icon fa fa-thumb-tack" ,
                            assessment_method: assessment.assessment_method,
                            read_class: assessment.read_id ? "list-card-icon fa fa-eye read-toggle read" : "list-card-icon fa fa-eye-slash read-toggle unread",
                            read_id: assessment.read_id,
                            read_type : "assessment",
                            like_id : assessment.like_id ? assessment.like_id : "",
                            comment : assessment.comment,
                            like_button_id: "assessment-comment-" + assessment.aprogress_id,
                            comment_area_id: "comment-area-" + assessment.aprogress_id,
                            assessment_comment_area_id: "assessment-comment-area-" + assessment.aprogress_id,
                            submit_assessment_comment_id: "submit-assessment-comment-" + assessment.aprogress_id,
                            comment_icon_class: assessment.like_id ? (assessment.comment ? "list-card-icon fa fa-commenting like-comment-icon" : "list-card-icon fa fa-comment like-comment-icon"): "list-card-icon fa fa-comment like-comment-icon hide",
                            like_assessment_class: assessment.like_id ? "list-card-icon fa fa-thumbs-up like-assessment liked" : "list-card-icon fa fa-thumbs-up like-assessment",
                            previous_comment_class: assessment.comment ? "space-below" : "space-below hide",
                            show_like_and_comment: is_admin_view == 0 ? "list-card-btn" : "list-card-btn hide",
                            assessor_label: cbme_translations.assessed_by + assessment.assessor
                        }).appendTo("#assessment-cards").addClass("list-card-item assessment-list-card");
                    } else if (assessment_type === "deleted") {
                        var list_item = $("<li/>").loadTemplate("#assessment-deleted-card-template", {
                            form_type: assessment.form_type,
                            deleted_date : assessment.deleted_date,
                            form_title: assessment.title,
                            comment_response: assessment.comment_response,
                            assessor: assessment.assessor,
                            entrustment_response_descriptor: assessment.entrustment_response_descriptor,
                            dassessment_id: assessment.dassessment_id,
                            pin_id: assessment.pin_id,
                            aprogress_id: assessment.aprogress_id,
                            pinned_class: assessment.is_pinned ? "pin-assessment pinned list-card-icon fa fa-thumb-tack" : "pin-assessment list-card-icon fa fa-thumb-tack",
                            progress_value: assessment.progress_value ? capitalizeFirstLetter(assessment.progress_value) : null,
                            triggered_by: assessment.triggered_by,
                            assessment_method: assessment.assessment_method,
                            deleted_reason: assessment.deleted_reason_notes,
                            deleted_by: assessment.deleted_by,
                            assessor_label: cbme_translations.assessed_by + assessment.assessor
                        }).appendTo("#assessment-cards").addClass("list-card-item assessment-list-card");
                    } else {
                        var list_item = $("<li/>").loadTemplate("#assessment-pending-card-template", {
                            form_type: assessment.form_type,
                            form_title: assessment.title,
                            created_date: assessment.updated_date ? assessment.updated_date : assessment.assessment_created_date,
                            comment_response: assessment.comment_response,
                            assessor: assessment.assessor,
                            entrustment_response_descriptor: assessment.entrustment_response_descriptor,
                            dassessment_id: assessment.dassessment_id,
                            pin_id: assessment.pin_id,
                            aprogress_id: assessment.aprogress_id,
                            pinned_class: assessment.is_pinned ? "pin-assessment pinned list-card-icon fa fa-thumb-tack" : "pin-assessment list-card-icon fa fa-thumb-tack",
                            progress_value: assessment.progress_value ? capitalizeFirstLetter(assessment.progress_value) : null,
                            triggered_by: assessment.triggered_by,
                            assessment_method: assessment.assessment_method,
                            assessor_label: cbme_translations.assessed_by + assessment.assessor
                        }).appendTo("#assessment-cards").addClass("list-card-item assessment-list-card");
                    }

                    update_rating_scale(list_item, assessment.selected_iresponse_order, assessment.rating_scale_responses, "star", "assessment");

                    update_epa_tags(list_item, assessment.mapped_epas);

                    if (!assessment.comment_response) {
                        list_item.find(".assessment-card-comment").addClass("hide");
                    }

                    if (!assessment.entrustment_response_descriptor) {
                        list_item.find(".assessment-card-entrustment").addClass("hide");
                        list_item.find(".assessment-rating").addClass("hide");
                    }
                });
                update_display_count();
                $("#show-more-assessments-btn").attr("data-offset", offset + query_limit);
            }
        });
    }

    /**
     * Fetches filtered assessments via ajax
     * @param course_id the learner course
     * @param filters the user selected filters
     * @param limit
     * @param offset
     * @param proxy_id
     * @param pinned_only
     */
    function get_assessment_comments(course_id, filters, limit, offset, proxy_id, pinned_only) {
        var assessment_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "GET",
            data: {"method": "get-assessment-comments", "course_id": course_id, "filters": filters, "limit": limit, "offset": offset, "proxy_id": proxy_id, "pinned_only": pinned_only}
        });

        $.when(assessment_request).done(function (data) {
            var jsonResponse = safeParseJson(data, cbme_assessments.assessment_comment_error);
            if (jsonResponse.status === "success") {
                $.each(jsonResponse.data, function (i, assessment) {
                    var list_item = $("<li/>").loadTemplate("#assessment-comment-card-template", {form_type: assessment.form_type, form_title: assessment.title, created_date: assessment.created_date, comment_response: assessment.comment_response, assessor: assessment.assessor, entrustment_response_descriptor: assessment.entrustment_response_descriptor, assessment_url: ENTRADA_URL + "/assessments/assessment?dassessment_id=" + assessment.dassessment_id, dassessment_id: assessment.dassessment_id, pin_id: assessment.pin_id, aprogress_id: assessment.aprogress_id, pinned_class: assessment.is_pinned ? "pin-assessment pinned list-card-icon fa fa-thumb-tack" : "pin-assessment list-card-icon fa fa-thumb-tack"}).appendTo("#assessment-comment-cards").addClass("list-card-item assessment-list-card");

                    update_rating_scale(list_item, assessment.selected_iresponse_order, assessment.rating_scale_responses, "star", "assessment");

                    update_epa_tags(list_item, assessment.mapped_epas);

                    list_comments(list_item, assessment.comments, assessment.dassessment_id, assessment.aprogress_id);

                    if (!assessment.comment_response) {
                        list_item.find(".assessment-card-comment").addClass("hide");
                    }

                    if (!assessment.entrustment_response_descriptor) {
                        list_item.find(".assessment-card-entrustment").addClass("hide");
                        list_item.find(".assessment-rating").addClass("hide");
                    }
                });
                update_assessment_comment_display_count();
                $("#show-more-comments-btn").attr("data-offset", offset + query_limit);
            }
        });
    }

    /**
     * Fetches filtered items via ajax
     * @param course_id the learner course
     * @param filters the user selected filters
     */
    function get_items(course_id, filters, limit, offset, proxy_id, pinned_only, secondary_proxy_id) {
        var item_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "GET",
            data: {"method": "get-items", "course_id": course_id, "filters": filters, "limit": limit, "offset": offset, "proxy_id": proxy_id, "pinned_only": pinned_only, "secondary_proxy_id": secondary_proxy_id}
        });

        $.when(item_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "Unknown Error");
            if (jsonResponse.status === "success") {
                $.each(jsonResponse.data, function (i, item) {
                    var list_item = $("<li/>").loadTemplate("#item-card-template", {
                        item_text: item.rubric_id ? item.rubric_title : item.item_text,
                        created_date: item.created_date, dassessment_id : item.dassessment_id,
                        item_description: item.rubric_id ? item.item_text : item.item_description,
                        assessor: item.assessor,
                        response_descriptor: item.response_descriptor,
                        assessment_url: ENTRADA_URL + "/assessments/assessment?dassessment_id=" + item.dassessment_id, comments: item.comments,
                        aprogress_id: item.aprogress_id,
                        item_id: item.item_id,
                        pinned_class: item.is_pinned ? "pin-assessment pinned list-card-icon fa fa-thumb-tack" : "pin-assessment list-card-icon fa fa-thumb-tack",
                        read_class: item.read_id ? "list-card-icon fa fa-eye read-toggle read" : "list-card-icon fa fa-eye-slash read-toggle unread",
                        read_id: item.read_id,
                        read_type : "item",
                        pin_id: item.pin_id
                    }).appendTo("#item-cards").addClass("list-card-item item-list-card");

                    if (item.item_rating_scale_id == null && item.rubric_rating_scale_id == null) {
                        list_item.find(".rating-section").addClass("hide");
                    }

                    if ((item.item_description == null && item.rubric_id == null)) {
                        list_item.find(".item-description").addClass("hide");
                    }

                    if (item.comments == null) {
                        list_item.find(".item-comments").addClass("hide");
                    }

                    if (item.item_response_text == "" || item.item_response_text == null) {
                        list_item.find(".item-response-text").addClass("hide");
                    }

                    if (item.response_descriptor == null) {
                        list_item.find(".item-response-descriptor").addClass("hide");
                    }

                    if (item.response_descriptor == item.item_response_text) {
                        list_item.find(".item-response-text").addClass("hide");
                    }

                    update_rating_scale(list_item, item.order, item.rating_scale_responses, "circle", "item");

                    update_epa_tags(list_item, item.mapped_epas);
                });
                update_item_display_count();
                $("#show-more-items-btn").attr("data-offset", offset + query_limit);
            }
        });
    }

    /**
     * A function to highlight the appropriate number of stars in an assessment or item card based on the selected response order
     * @param list_item
     * @param order
     * @param rating_scale_responses
     * @param icon_type
     */
    function update_rating_scale(list_item, order, rating_scale_responses, icon_type, template_type) {
        var assessment_rating_class = "assessment-rating";
        if (template_type == "item") {
            assessment_rating_class = "assessment-item-rating";
        }

        $.each(rating_scale_responses, function(i, rating_scale_response) {
            var element_class = "fa fa-"+ icon_type +" rating-icon";
            if (rating_scale_response.order <= order) {
                element_class = "fa fa-"+ icon_type +" rating-icon-active";
            }
            list_item.find("." + assessment_rating_class).loadTemplate("#rating-scale-response-template", {"rating_scale_class": element_class, "response_descriptor": rating_scale_response.text}, {append: true});
        });
    }

    /**
     * A function to display each tagged EPA on the assessment card
     * @param list_item
     * @param mapped_epas
     */
    function update_epa_tags (list_item, mapped_epas) {
        $.each(mapped_epas, function (i, mapped_epa) {
            var epa_list_item = $("<li/>").loadTemplate("#mapped-epa-template", {epa_tag_class: "label " + mapped_epa.stage_code + "-stage", objective_code: mapped_epa.objective_code});
            list_item.find(".tag-list").append(epa_list_item);
        });
    }

    /**
     * Updates the UI after the show more assessments button is clicked and successfully returns results
     */
    function update_display_count() {
        var display_count = get_displayed_count();
        $("#displayed-count").html(display_count);
    }

    /**
     * Updates the UI after the show more items button is clicked and successfully returns results
     */
    function update_item_display_count() {
        var display_count = get_displayed_item_count();
        $("#displayed-item-count").html(display_count);
    }

    /**
     * Gets the currently displayed count
     * @returns int
     */
    function get_displayed_item_count() {
        var display_count = parseInt($(".item-list-card").length);
        return display_count;
    }

    /**
     * Gets the currently displayed count
     * @returns int
     */
    function get_displayed_count() {
        var display_count = parseInt($(".assessment-list-card").length);
        return display_count;
    }

    /**
     * Updates the UI after the show more assessment comments button is clicked and successfully returns results
     */
    function update_assessment_comment_display_count() {
        var display_count = get_displayed_assessment_comment_count();
        $("#displayed-comment-count").html(display_count);
    }

    /**
     * Gets the currently displayed count
     * @returns int
     */
    function get_displayed_assessment_comment_count() {
        var display_count = parseInt($("#assessment-comment-cards").find(".assessment-list-card").length);
        return display_count;
    }

    /**
     * Dynamically builds filters for the contextual variable response advancedSearch widget
     * based on the selected contextual variables
     *
     * @param filter_name
     * @param label
     * @param datasource
     * @param selector_control_name
     */
    function add_cv_response_filter(filter_name, label, datasource, selector_control_name, objective_id) {
        var settings = $("#select-cv-responses-btn").data("settings");
        settings.filters[filter_name] = {label: label, data_source: datasource, selector_control_name: selector_control_name};
        settings.filters[filter_name].api_params = {objective_id: objective_id, course_id: $("input[name=\"course_id\"]").val()}
    }

    /**
     * Remove all contextual variable response filters
     */
    function remove_cv_response_filters() {
        var settings = $("#select-cv-responses-btn").data("settings");
        settings.filters = {};
    }

    /**
     * Toggle the disabled state of the contextual variable response advancedSearch instance
     */
    function toggle_cv_response_control(selected_contextual_variables) {
        if (selected_contextual_variables.length > 0) {
            $("#select-cv-responses-btn").prop("disabled", false);
        } else {
            $("#select-cv-responses-btn").prop("disabled", true);
        }
    }

    /**
     * Dynamically builds the options for the contextual variable response advancedSearch instance
     */
    function build_contextual_variable_responses() {
        var selected_contextual_variables = $("input[name=\"contextual_variables[]\"]");
        toggle_cv_response_control(selected_contextual_variables);
        remove_cv_response_filters();
        $.each(selected_contextual_variables, function (i, contextual_variable) {
            var objective_id = $(contextual_variable).val();
            var filter_name = "objective_" + objective_id;
            var label = $(contextual_variable).attr("data-label");
            var datasource = "get-contextual-variable-responses";
            var selector_control_name = "objective_"+ objective_id +"[]";
            var objective_id = objective_id;

            add_cv_response_filter(filter_name, label, datasource, selector_control_name, objective_id);
        });
    }

    /**
     * Toggles assessment card details when details icon is clicked
     */
    $("#assessment-cards, #item-cards, #assessment-comment-cards").on("click", ".details-item", function (e) {
        e.preventDefault();
        $(this).toggleClass("active");
        $(this).closest(".list-card-item-wrap").find(".list-card-body").slideToggle("fast");
    });

    $(".date-filter").on("click", function() {
        if ($(this).val() == 4) {
            $('input[name=start_date]').attr("disabled", true);
            $('input[name=finish_date]').attr("disabled", true);
            $('.experience_select').attr("disabled", false);
        }
        if ($(this).val() == 3) {
            $('.experience_select').attr("disabled", true);
            $('input[name=finish_date]').attr("disabled", false);
            $('input[name=start_date]').attr("disabled", false);
            $(".hidden-date-inputs").empty();
        }
    });

    $(".experience_select").advancedSearch({
        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
        resource_url: ENTRADA_URL,
        filter_component_label: javascript_translations.rotations,
        filters: {
            schedule_id: {
                label: javascript_translations.curriculum_period,
                data_source: "get-experience-data",
                secondary_data_source: "get-rotations",
                mode: "checkbox",
                selector_control_name: "schedule_id",
                api_params: {
                    proxy_id : $("input[name=\"proxy_id\"]").val()
                }
            }
        },
        control_class: "learner-selector",
        parent_form: $("#cbme-filters"),
        no_results_text: javascript_translations.no_rotations_found,
        width: 400
    });

    /**
     * Event listener for pinning stuff
     */
    $(".list-card").on("click", ".pin", function (e) {
        e.preventDefault();
        var method = "";
        var pin_id = $(this).attr("data-pin-id");
        var pin_type = $(this).attr("data-pin-type");
        var pin_value = $(this).attr("data-id");
        var aprogress_id = $(this).attr("data-aprogress-id");
        var dassessment_id = $(this).attr("data-dassessment-id");
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var pin_element = $(this);

        if ($(this).hasClass("pinned")) {
            method = "unpin";
        } else {
            method = "pin";
        }

        var pin_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": method, "aprogress_id": aprogress_id, "dassessment_id": dassessment_id, "pin_id": pin_id, "proxy_id": proxy_id, "pin_type": pin_type, "pin_value": pin_value}
        });

        $.when(pin_request).done(function (data) {
            var jsonResponse = safeParseJson(data, cbme_assessments.assessment_pin_error);
            if(jsonResponse.status == "success" && jsonResponse.hasOwnProperty("status")) {
                $.animatedNotice(jsonResponse.data.message, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
                pin_element.attr("data-pin-id", jsonResponse.data.pin_id).toggleClass("pinned");
            }
        });
    });

    /**
     * Set the selected tab into user preferences
     */
    $("#pinned-assessment-tab").on("click", function() {
        set_pinned_tab_preference("assessment", true);
    });

    $("#pinned-item-tab").on("click", function() {
        set_pinned_tab_preference("item", true);
    });

    $("#pinned-comment-tab").on("click", function() {
        set_pinned_tab_preference("comment", true);
    });

    /**
     * Set the selected pin tab into the user preferences
     * @param pin_type
     * @param preference
     */
    function set_pinned_tab_preference(pin_type, preference) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "pinned-tab-preference", "pin_type": pin_type}
        });
    }

    $(document).on("click", ".read-toggle", function() {
        var method = "";
        var read_id = $(this).attr("data-read-id");
        var read_type = $(this).attr("data-read-type");
        var read_element = $(this);
        if ($(this).hasClass("read")) {
            $(this).removeClass("read fa-eye");
            $(this).addClass("unread fa-eye-slash");
            method = "unread";
        } else {
            $(this).addClass("read fa-eye");
            $(this).removeClass("unread fa-eye-slash");
            method = "read";
        }

        jQuery.ajax({
            type: "POST",
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            data: {
                "method": method,
                "read_id" : read_id,
                "read_type" : "assessment",
                "item_id" : $(this).attr("data-id") ? $(this).attr("data-id") : null,
                "aprogress_id" : $(this).attr("data-aprogress-id"),
                "dassessment_id" : $(this).attr("data-dassessment-id"),
                "proxy_id" : $("input[name=\"proxy_id\"]").val()
            },
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                if(jsonResponse.status == "success" && jsonResponse.hasOwnProperty("status")) {
                    read_element.attr("data-read-id", jsonResponse.data.read_id);
                }
            }
        });
    });

    $(document).on("click", ".view-assessment-details", function() {
        var read_id = $(this).attr("data-read-id");
        var read_type = $(this).attr("data-read-type");
        jQuery.ajax({
            type: "POST",
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            data: {
                "method": "read",
                "read_id" : read_id,
                "read_type" : "assessment",
                "item_id" : $(this).attr("data-id") ? $(this).attr("data-id") : null,
                "aprogress_id" : $(this).attr("data-aprogress-id"),
                "dassessment_id" : $(this).attr("data-dassessment-id"),
                "proxy_id" : $("input[name=\"proxy_id\"]").val()
            }
        });
    });

    $(document).on("click", "#reminder-modal-confirm", function() {
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var secondary_proxy_id = $("input[name=\"secondary_proxy_id\"]").val();
        var course_id = $("input[name=\"course_id\"]").val();
        jQuery.ajax({
           type : "POST",
           url : ENTRADA_URL + "/assessments?section=api-assessments",
           data : {
               "method" : "mark-all-as-read",
               "proxy_id" : proxy_id,
               "secondary_proxy_id" : secondary_proxy_id,
               "course_id" : course_id
           },
            beforeSend: function () {
                $(".mark-all-assessments-loading").removeClass("hide");
                $("#reminder-modal-confirm").addClass("hide");
            },
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                $("#mark-all-as-read").modal('toggle');
                if(jsonResponse.status === "success") {
                    location.reload();
                }
            },
            error: function() {
                $(".mark-all-assessments-loading").addClass("hide");
                $("#reminder-modal-confirm").removeClass("hide");
            },
            complete: function() {
                $(".mark-all-assessments-loading").addClass("hide");
                $("#reminder-modal-confirm").removeClass("hide");
            }
        });
    });

    /**
     * A function to display a comment in an assessment card
     * @param list_item
     * @param comments object
     * @param dassessment_id
     * @param aprgress_id
     */
    function list_comments(list_item, comments, dassessment_id, aprogress_id) {
        $.each(comments, function (i, comment) {
            var data_class = "";
            if (comment.pin_id != null && comment.deleted_date == null) {
                data_class = "pinned";
            }
            var comment = $("<div/>").loadTemplate("#comment-template", {pinned_class: data_class, item_text: comment.item_text, comment_response: (comment.comments == null ? cbme_assessments.no_comment : comment.comments), dassessment_id: dassessment_id, aprogress_id: aprogress_id, epresponse_id: comment.epresponse_id, pin_id: comment.pin_id}).addClass("list-card-body-section clearfix");
            list_item.find(".assessment-comments").append(comment);
        });
    }

    /**
     * Function for capitalizing the first letter of a string
     * @param string
     * @returns {string}
     */
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    /**
     * Event listener for the Like Assessment button
     */
    $(document).on("click", ".like-assessment", function(e) {
        $(".like-assessment").popover('hide');
        var method = "";
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var like_id = $(this).attr("data-like-id");
        var dassessment_id = $(this).attr("data-dassessment-id");
        var aprogress_id = $(this).attr("data-aprogress-id");
        if ($(this).hasClass("liked")) {
            $(this).removeClass("liked");
            method = "unlike";
        } else {
            $(this).addClass("liked");
            method = "like";
        }
        var like_element = $(this);
        jQuery.ajax({
            type : "POST",
            url : ENTRADA_URL + "/assessments?section=api-assessments",
            data : {
                "method" : method,
                "proxy_id" : proxy_id,
                "aprogress_id" : aprogress_id,
                "dassessment_id" : dassessment_id,
                "like_type" : "assessment",
                "like_id" : like_id
            },
            success: function(data) {
                var jsonResponse = safeParseJson(data, "");
                if (jsonResponse.status === "success") {
                    $.animatedNotice(jsonResponse.data.message, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
                    like_element.attr("data-like-id", jsonResponse.data.like_id);
                    $("#submit-assessment-comment-" + aprogress_id).attr("data-like-id", jsonResponse.data.like_id);
                    if (method === "like") {
                        $("#comment-area-" + aprogress_id).removeClass("hide");
                        $("#assessment-comment-" + aprogress_id).removeClass("hide").addClass("active-comment");
                    }
                    if (method === "unlike") {
                        $("#comment-area-" + aprogress_id).addClass("hide");
                        $("#assessment-comment-" + aprogress_id).addClass("hide");
                    }
                }
            },
            error: function() {
                $.animatedNotice(jsonResponse.data.message, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
            }
        });
    });

    /**
     * Click listener for the close button on the assessment like and comment section
     */
    $(document).on("click", ".close-assessment-like-comment", function() {
        var aprogress_id = $(this).attr("data-aprogress-id");
        $(this).closest(".assessment-like-comment-area").addClass("hide");
        $("#assessment-comment-" + aprogress_id).toggleClass("active-comment");
    });

    /**
     * Hide/show the comment section when clicking the comment icon
     */
    $(document).on("click", ".like-comment-icon", function() {
        var aprogress_id = $(this).attr("data-aprogress-id");
        $("#comment-area-" + aprogress_id).toggleClass("hide");
        $("#assessment-comment-" + aprogress_id).toggleClass("active-comment");
    });

    /**
     * Event listener for submitting an assessment comment
     */
    $(document).on("click", ".submit-assessment-comment", function() {
        var dassessment_id = $(this).attr("data-dassessment-id");
        var aprogress_id = $(this).attr("data-aprogress-id");
        var comment_text = $("#assessment-comment-area-" + aprogress_id).val();
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        var like_id = $(this).attr("data-like-id");

        jQuery.ajax({
            type: "POST",
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            data: {
                "method": "add-assessment-comment",
                "proxy_id": proxy_id,
                "aprogress_id": aprogress_id,
                "dassessment_id": dassessment_id,
                "like_type": "assessment",
                "like_id": like_id,
                "comment": comment_text
            },
            success : function (data) {
                var jsonResponse = safeParseJson(data, "");
                $.animatedNotice(jsonResponse.data.message, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
                $("#comment-area-" + aprogress_id).toggleClass("hide");
                $("#assessment-comment-" + aprogress_id).toggleClass("active-comment");
            },
            error: function(data) {
                var jsonResponse = safeParseJson(data, "");
                $.animatedNotice(jsonResponse.data.message, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
            }
        });
    });
});
