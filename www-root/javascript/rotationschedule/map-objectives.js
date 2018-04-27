jQuery(document).ready(function ($) {

    $(".map-objective-button").on("click", function (e) {
        e.preventDefault();

        var map_button = $(this);
        var parent_row = $(this).closest("tr");
        var priority_button = parent_row.find("button.objective-priority-button");

        if (map_button.hasClass("objective-unmapped")) {

            // Map objective (enable to the row).
            enable_row(map_button, priority_button, parent_row);
        } else {

            // Un-map objective (disable to the row and remove the linkage via API call).
            $.ajax({
                url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
                data: {
                    "method": "switch-rotation-mapped-objective",
                    "course_id": course_id,
                    "schedule_id": schedule_id,
                    "objective_id": map_button.data("objective-id"),
                    "priority": priority_button.hasClass("active")
                },
                type: "POST",
                success: function (results) {
                    var jsonResponse = JSON.parse(results);
                    $("#objectives-mapping-results").removeClass("hide");
                    clear_mapping_results();

                    if (jsonResponse.status == "success") {
                        disable_row(map_button, priority_button, parent_row);
                        display_success(jsonResponse.data, "#objectives-mapping-results");
                    } else {
                        display_error(jsonResponse.data, "#objectives-mapping-results");
                    }
                }
            });
        }
    });


    $(".objective-likelihood-button").on("click", function (e) {
        e.preventDefault();

        var likelihood_button = $(this);
        var parent_row = $(this).closest("tr");
        var priority_button = parent_row.find("button.objective-priority-button");

        // Map new likelihood to objective. Remove any previously selected likelihood for this objective and remove it's linkage via API call.
        $.ajax({
            url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
            data: {
                "method": "switch-rotation-mapped-objective",
                "course_id": course_id,
                "schedule_id": schedule_id,
                "objective_id": likelihood_button.data("objective-id"),
                "likelihood_id": likelihood_button.data("likelihood-id"),
                "priority": priority_button.hasClass("active") ? true : false
            },
            type: "POST",
            success: function (results) {
                var jsonResponse = JSON.parse(results);
                $("#objectives-mapping-results").removeClass("hide");
                clear_mapping_results();

                if (jsonResponse.status == "success") {
                    update_priority_enabled(priority_button, true);
                    update_row_likelihoods(likelihood_button, parent_row);
                    display_success(jsonResponse.data, "#objectives-mapping-results");
                } else {
                    display_error(jsonResponse.data, "#objectives-mapping-results");
                }
            }
        });
    });

    $(".objective-priority-button").on("click", function (e) {
        e.preventDefault();

        var priority_button = $(this);
        var current_priority = priority_button.hasClass("active");
        var new_priority = current_priority ? false : true;

        // Map priority to objective. This requires a likelihood to be previously selected.
        $.ajax({
            url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
            data: {
                "method": "switch-rotation-mapped-objective-priority",
                "course_id": course_id,
                "schedule_id": schedule_id,
                "objective_id": priority_button.data("objective-id"),
                "priority": new_priority
            },
            type: "POST",
            success: function (results) {
                var jsonResponse = JSON.parse(results);
                $("#objectives-mapping-results").removeClass("hide");
                clear_mapping_results();

                if (jsonResponse.status == "success") {
                    toggle_priority(priority_button, new_priority);
                    display_success(jsonResponse.data, "#objectives-mapping-results");
                } else {
                    display_error(jsonResponse.data, "#objectives-mapping-results");
                }
            }
        });
    });


    function enable_row(map_button, priority_button, row) {
        $(row).removeClass("objective-unmapped");
        $(row).addClass("objective-mapped");

        $(map_button).removeClass("objective-unmapped");
        $(map_button).addClass("objective-mapped");
        $(map_button).find("i").removeClass("fa-plus").addClass("fa-check");

        // Update dynamic tooltip.
        var title = map_button.attr("data-original-title");
        $(map_button).attr("data-original-title", title.replace(javascript_translations.map_objective, javascript_translations.unmap_objective));

        update_priority_enabled(priority_button, true);

        var likelihood_buttons = $(row).find("button.objective-likelihood-button");
        $(likelihood_buttons).each(function (i, likelihood_button) {
            $(likelihood_button).removeClass("objective-unmapped");
            $(likelihood_button).removeClass("active");
            $(likelihood_button).removeClass("disabled");
            $(likelihood_button).removeAttr("disabled");
        });
    }

    function disable_row(map_button, priority_button, row) {
        $(row).removeClass("objective-mapped");
        $(row).addClass("objective-unmapped");

        $(map_button).removeClass("objective-mapped");
        $(map_button).addClass("objective-unmapped");
        $(map_button).find("i").removeClass("fa-check").addClass("fa-plus");

        // Update dynamic tooltip.
        var title = map_button.attr("data-original-title");
        $(map_button).attr("data-original-title", title.replace(javascript_translations.unmap_objective, javascript_translations.map_objective));

        update_priority_enabled(priority_button, false);

        var likelihood_buttons = $(row).find("button.objective-likelihood-button");
        $(likelihood_buttons).each(function (i, likelihood_button) {
            switch($(likelihood_button).attr("data-likelihood-shortname")) {
                case "unlikely":
                    $(likelihood_button).addClass("unlikely-disabled");
                    break;
                case "likely":
                    $(likelihood_button).addClass("likely-disabled");
                    break;
                case "very_likely":
                    $(likelihood_button).addClass("very-likely-disabled");
                    break;
            }
            $(likelihood_button).removeClass("objective-mapped");
            $(likelihood_button).removeClass("active");
            $(likelihood_button).addClass("objective-unmapped");
            $(likelihood_button).addClass("disabled");
            $(likelihood_button).prop("disabled", true);
        });
    }

    function update_row_likelihoods(button, row) {
        var likelihood_buttons = $(row).find("button.objective-likelihood-button");
        $(likelihood_buttons).each(function (i, likelihood_button) {
            $(likelihood_button).removeClass("objective-mapped");
            $(likelihood_button).removeClass("active");
            $(likelihood_button).addClass("objective-unmapped");
            $(likelihood_button).removeClass("disabled");
            $(likelihood_button).removeAttr("disabled");
            switch($(likelihood_button).attr("data-likelihood-shortname")) {
                case "unlikely":
                    $(this).removeClass("unlikely-active");
                    $(this).removeClass("unlikely-disabled");
                    $(this).addClass("unlikely");
                break;
                case "likely":
                    $(this).removeClass("likely-active");
                    $(this).removeClass("likely-disabled");
                    $(this).addClass("likely");
                break;
                case "very_likely":
                    $(this).removeClass("very-likely-active");
                    $(this).removeClass("very-likely-disabled");
                    $(this).addClass("very-likely");
                break;
            }
        });
        switch($(button).attr("data-likelihood-shortname")) {
            case "unlikely":
                $(button).addClass("unlikely-active");
                $(button).removeClass("unlikely-disabled");
                $(button).removeClass("unlikely");
            break;
            case "likely":
                $(button).addClass("likely-active");
                $(button).removeClass("likely-disabled");
                $(button).removeClass("likely");
            break;
            case "very_likely":
                $(button).addClass("very-likely-active");
                $(button).removeClass("very-likely-disabled");
                $(button).removeClass("very-likely");
            break;
        }
        $(button).removeClass("objective-unmapped");
        $(button).addClass("objective-mapped");
        $(button).addClass("active");
    }

    function update_priority_enabled(button, active) {
        if (active) {
            $(button).removeClass("disabled");
            $(button).removeAttr("disabled");
        } else {
            $(button).removeClass("active");
            $(button).prop("disabled", true);
            $(button).addClass("disabled");
            $(button).removeClass("objective-mapped");
            $(button).addClass("objective-unmapped");
        }
    }

    function toggle_priority(button, active) {
        var title = button.attr("data-original-title");
        if (active) {
            $(button).removeClass("objective-unmapped");
            $(button).addClass("objective-mapped");
            $(button).addClass("active");

            // Update dynamic tooltip.
            $(button).attr("data-original-title", title.replace("Prioritize", "Unprioritize"));
        } else {
            $(button).removeClass("objective-mapped");
            $(button).addClass("objective-unmapped");
            $(button).removeClass("active");

            // Update dynamic tooltip.
            $(button).attr("data-original-title", title.replace("Unprioritize", "Prioritize"));
        }
    }

    function clear_mapping_results() {
        $("#objectives-mapping-results").html("");
    }

});