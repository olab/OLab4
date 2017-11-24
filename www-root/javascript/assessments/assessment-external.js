jQuery(document).ready(function ($) {
    $("#assessment-form input[name^=\"item-\"]").on("change", function () {
        save_responses();
        var comment_type = $(this).closest("div").data("comment-type");
        if (comment_type == "flagged") {
            var item_name = "item-" + $(this).data("item-id");
            var flag_selected = false;
            // For each input for the item, check to see if any flagged response is selected.
            // If so, display a mandatory comments block.
            $("#assessment-form input[name^=\"" + item_name + "\"]").each(function () {
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

    $("#assessment-form textarea[name^=\"item-\"]").on("change", function () {
        save_responses();
    });

    $("#assessment-form").on("change", "input[name^=\"objective-\"]", function () {
        save_responses();
    });

    $("#assessment-form select[name^=\"item-\"]").on("change", function () {
        save_responses();
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

    $("#assessment-form input[name^=\"rubric-item-\"]").on("change", function () {
        save_responses();
        var comment_type = $(this).closest("tbody").data("comment-type");
        if (comment_type == "flagged") {
            var item_id = $(this).data("item-id");
            var rubric_id = $(".rubric-container").closest("div").data("item-id");
            var item_name = "rubric-item-" + rubric_id + "-" + item_id;
            var flag_selected = false;
            // For each input for the item, check to see if any flagged response is selected.
            // If so, display a mandatory comments block.
            $("#assessment-form input[name^=\"" + item_name + "\"]").each(function () {
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
    
    $("#assessment-form textarea[name^=\"item-\"]").on("blur", function () {
        save_responses();
    });

    $("#target-search-input").on("click", function (e) {
        e.stopPropagation();
    });

    $(".change-target").on("click", function (e) {
        var progress_id = $("input[name=\"aprogress_id\"]").val();
        var target_record_id = $(this).attr("data-target-record-id");
        var dassessment_id = $("input[name=\"dassessment_id\"]").val();
        var assessor_value = $("input[name=\"assessor_value\"]").val();
        var external_hash = $("input[name=\"external_hash\"]").val();

        var target_request = $.ajax({
            url: "api-assessment-external",
            data: "method=change-target&aprogress_id=" + progress_id + "&target_record_id=" + target_record_id + "&dassessment_id=" + dassessment_id + "&assessor_value=" + assessor_value,
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                var distribution_id     = $("input[name=\"adistribution_id\"]").val();
                var schedule_id         = $("input[name=\"schedule_id\"]").val();
                var aprogress_id        = $("input[name=\"aprogress_id\"]").val();
                var assessor_value        = $("input[name=\"assessor_value\"]").val();
                var target_record_id    = jsonResponse.data.target_record_id;

                window.location = ENTRADA_URL + "/assessment?adistribution_id=" + distribution_id + "&schedule_id=" + schedule_id + "&target_record_id=" + target_record_id + "&aprogress_id=" + aprogress_id + "&dassessment_id=" + dassessment_id + "&assessor_value=" + assessor_value + "&external_hash=" + external_hash;
            } else {
                var distribution_id     = $("input[name=\"adistribution_id\"]").val();
                var schedule_id         = $("input[name=\"schedule_id\"]").val();
                var aprogress_id        = $("input[name=\"aprogress_id\"]").val();
                var target_record_id    = $("input[name=\"target_record_id\"]").val();
                var assessor_value        = $("input[name=\"assessor_value\"]").val();

                window.location = ENTRADA_URL + "/assessment?adistribution_id=" + distribution_id + "&schedule_id=" + schedule_id + "&target_record_id=" + target_record_id + "&aprogress_id=" + aprogress_id + "&dassessment_id=" + dassessment_id + "&assessor_value=" + assessor_value + "&external_hash=" + external_hash;
            }
        });

        e.preventDefault();
    });

    $("#target-search-input").on("keyup", function () {
        var search_text = $(this).val().toLowerCase();
        if (search_text.length === 0) {
            $(".target-listitem").removeClass("hide");
            var total_pending = $(".target-listitem-pending").length;
            var total_inprogress = $(".target-listitem-inprogress").length;
            var total_complete = $(".target-listitem-complete").length;

            $("#targets-pending-count").html(total_pending);
            $("#targets-inprogress-count").html(total_inprogress);
            $("#targets-complete-count").html(total_complete);

            $("#no-target-pending-listitem-header").removeClass("hide");
            $("#no-target-inprogress-listitem-header").removeClass("hide");
            $("#no-target-complete-listitem-header").removeClass("hide");
            $(".no-target-search-listitem").remove();
        } else {
            $(".target-listitem").each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(search_text) >= 0) {
                    $(this).removeClass("hide").addClass("visible");
                } else {
                    $(this).addClass("hide").removeClass("visible");
                }

            });

            $(".no-target-search-listitem").remove();

            var total_pending = $(".target-listitem-pending.visible").length;
            var total_inprogress = $(".target-listitem-inprogress.visible").length;
            var total_complete = $(".target-listitem-complete.visible").length;

            $("#targets-pending-count").html(total_pending);
            $("#targets-inprogress-count").html(total_inprogress);
            $("#targets-complete-count").html(total_complete);

            if (total_pending === 0) {
                if ($("#no-pending-target-search-listitem").length === 0) {
                    var no_target_listitem = $(document.createElement("li")).addClass("no-target-search-listitem").attr({id: "no-pending-target-search-listitem"});
                    no_target_listitem.html("No Targets found.");
                    $("#no-target-pending-listitem-header").addClass("hide");
                    $("#target-pending-listitem-header").after(no_target_listitem);
                }
            }

            if (total_inprogress === 0) {
                if ($("#no-inprogress-target-search-listitem").length === 0) {
                    var no_target_listitem = $(document.createElement("li")).addClass("no-target-search-listitem").attr({id: "no-inprogress-target-search-listitem"});
                    no_target_listitem.html("No Targets found.");
                    $("#no-target-inprogress-listitem-header").addClass("hide");
                    $("#target-inprogress-listitem-header").after(no_target_listitem);
                }
            }

            if (total_complete === 0) {
                if ($("#no-complete-target-search-listitem").length === 0) {
                    var no_target_listitem = $(document.createElement("li")).addClass("no-target-search-listitem").attr({id: "no-complete-target-search-listitem"});
                    no_target_listitem.html("No Targets found.");
                    $("#no-target-complete-listitem-header").addClass("hide");
                    $("#target-complete-listitem-header").after(no_target_listitem);
                }
            }
        }
    });

    $(".item-container").on("click", ".collapse-objective-btn", function (e) {
        var anchor = $(this);
        var objective_id = anchor.attr("data-objective-id");
        var afelement_id = anchor.attr("data-afelement-id");
        var organisation_id = $("input[name=\"organisation_id\"]").val();
        var indent = parseInt(anchor.parent().css("padding-left")) - 14;
        
        var objective_request = $.ajax({
            url: "api-assessment-external",
            data: "method=get-parent-objectives&objective_id=" + objective_id + "&organisation_id=" + organisation_id,
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
        var organisation_id = $("input[name=\"organisation_id\"]").val();
        var indent = parseInt($("#selected-objective-list-" + afelement_id).attr("data-indent")) + 14;
        
        var objective_request = $.ajax({
            url: "api-assessment-external",
            data: "method=get-objectives&objective_id=" + objective_id + "&afelement_id=" + afelement_id + "&organisation_id=" + organisation_id,
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

    $(".change-target").tooltip({placement: "right"});
    
    //build_target_affix();

    if ($("#aprogress_id").val() != 0) {
        $("#change_target_link").removeClass("hide");
    } else {
        $("#change_target_link").addClass("hide");
    }

    if ($("#target_record_id").val() == "0") {
        $("#assessment-form input").attr("disabled", true);
        $("#assessment-form select").attr("disabled", true);
        $("#assessment-form textarea").attr("disabled", true);
    }

    $('#change_target_modal').on('shown', function () {
        //clearTimeout(counter);
    });

    $('#change_target_modal').on('hide', function (e) {

    });
    
    $("#change_target_next_step").on("click", function() {
        $("#modal_msgs").html("");

        var step = parseInt($("#change_target_step").val());
        step++;

        var errors = [];

        //process
        switch(step) {
            case 3:
                $("#change_target_modal").modal("hide");
                break;

            case 2:
                if ($("#change_target_id").val() == 0) {
                   errors.push("Please select a target.");
                   step--;
                }

                if (errors.length == 0) {
                    new_target_record_id = $("#change_target_id").val();
                    var change_target_data =   {
                        "step" : step,
                        "method": "change-target",
                        "aprogress_id": $("#aprogress_id").val(),
                        "new_target_record_id": new_target_record_id
                    };

                    $.ajax({
                        url: "api-assessment-external?section=api-change-target",
                        data: change_target_data,
                        type: "POST",
                        beforeSend: function () {
                            show_loading_msg();
                        },
                        success: function(data) {
                            hide_loading_msg();
                            $("#target_record_id").val(new_target_record_id)
                            if (data.status == "success") {
                                display_success(data.msg, "#modal_msgs");
                            } else if (data.status == "error") {
                                display_error(data.msg, "#modal_msgs");
                            }
                        },
                        dataType: "json"
                    });
                }
                break;

            case 1:


                break;
        }

        //display
        switch(step) {
            case 2:
                var form_id = $("#form_id").val();
                var adistribution_id = $("#adistribution_id").val();
                var aprogress_id = $("#aprogress_id").val();
                var schedule_id = $("#schedule_id").val();

                var finish_link = "<a class=\"btn btn-primary\" href=\""+ENTRADA_URL+"/assessment?adistribution_id="+adistribution_id+"&schedule_id="+schedule_id+"&form_id="+form_id+"&target_record_id="+new_target_record_id+"\">Finish</a>";
                $("#change_target_next_step").replaceWith(finish_link);
                $("#change_target_previous_step").addClass("hide");
                $("#change_target_close_modal").addClass("hide");
                break;

            case 1:
                $("#change_target_previous_step").addClass("hide");

                break;
        }

        if (errors.length == 0) {
            $("#change_target_wizard_step_"+(step-1)).addClass("hide");
            $("#change_target_step").val(step);
            $("#change_target_wizard_step_"+step).removeClass("hide");
        } else {
            display_error(errors, "#modal_msgs");
        }
    });

    $("#change_target_previous_step").on("click", function() {
        var step = parseInt($("#change_target_step").val());

        $("#change_target_wizard_step_"+step).addClass("hide");
        step--;
        $("#change_target_step").val(step);
        $("#change_target_wizard_step_"+step).removeClass("hide");

        if (step > 1) {
            $("#change_target_previous_step").removeClass("hide");
        } else {
            $("#change_target_previous_step").addClass("hide");
        }
    })

    function show_loading_msg () {
        disable_wizard_controls();
        $("#change-target-loading-msg").html("Change the target of this form...");
        $("#assessment-change-target-form").addClass("hide");
        $("#change-target-loading").removeClass("hide");
    }

    function hide_loading_msg () {
        enable_wizard_controls();
        $("#change-target-loading").addClass("hide");
        $("#change-target-loading-msg").html("");
        $("#assessment-change-target-form").removeClass("hide");
    }

    function enable_wizard_controls () {
        if ($("#change_target_next_step").is(":disabled")) {
            $("#change_target_next_step").removeAttr("disabled");
        }

        if ($("#change_target_previous_step").is(":disabled")) {
            $("#change_target_previous_step").removeAttr("disabled");
        }
    }

    function disable_wizard_controls () {
        if (!$("#change_target_next_step").is(":disabled")) {
            $("#change_target_next_step").attr("disabled", "disabled");
        }

        if (!$("#change_target_previous_step").is(":disabled")) {
            $("#change_target_previous_step").attr("disabled", "disabled");
        }
    }
    
    function build_target_affix () {
        var target = jQuery(".target-label").html();
        var panel_container = jQuery(document.createElement("div")).addClass("panel").attr({"data-spy": "affix", "data-offset": "310", id: "target-panel"});
        var panel_head = jQuery(document.createElement("div")).addClass("panel-head");
        var panel_head_heading = jQuery(document.createElement("h3")).html(current_target);
        var panel_body = jQuery(document.createElement("div")).addClass("clearfix panel-body");
        var panel_span = jQuery(document.createElement("span")).html("<strong>" + target + "</strong>");
        
        panel_head.append(panel_head_heading);
        panel_body.append(panel_span);
        panel_container.append(panel_head).append(panel_body);
        
        //var container = jQuery(document.createElement("div")).addClass("well").html("Assessing: " + target);
        $(".inner-sidebar").append(panel_container);
    }
    
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
                url: "api-assessment-external",
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
    
    function save_responses () {
        var save_responses_request = $.ajax({
            url: "api-assessment-external",
            data: "method=save-responses&" + $("#assessment-form").serialize(),
            type: "POST"
        });
        
        $.when(save_responses_request).done(function(data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                $("#last-saved-time").remove();
                $("#aprogress_id").val(jsonResponse.data.aprogress_id);

                var saved_span = $(document.createElement("span")).addClass("label label-info").html("Last saved @ " + jsonResponse.data.saved).attr({id: "last-saved-time"});
                $("#last-saved-container").append(saved_span);
            } else {
                display_error(jsonResponse.data, "#msgs");
            }
        });
    }

    function toggle_comments(item_name, flag_selected) {
        if (flag_selected == true) {
            $("#assessment-form #" + item_name + "-comments-header").removeClass("hide");
            $("#assessment-form #" + item_name + "-comments-block").removeClass("hide");
        } else {
            $("#assessment-form #" + item_name + "-comments-header").addClass("hide");
            $("#assessment-form #" + item_name + "-comments-block").addClass("hide");
        }
    }

});