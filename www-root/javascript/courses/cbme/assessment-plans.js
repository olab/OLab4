/**
 * JS for handling Assessment Plans
 */
jQuery(document).ready(function ($) {
    /**
     * Global timeout variable
     */
    var timeout;

    /**
     * Event handler for typing in the objective search box
     */
    $("#objective-search-control").on("keyup", function (e) {
        var keycode = e.keyCode;
        var search_value = $.trim($(this).val().toLowerCase());
        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32                    ||
            keycode == 13                    ||
            keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }

            clearTimeout(timeout);
            timeout = setTimeout(function () {
                search_objectives(search_value);
            }, 400);
        }
    });

    /**
     * Paste event listener for the objective search input
     */
    $("#objective-search-control").on("paste", function () {
        setTimeout(function () {
            var search_value = $.trim($("#objective-search-control").val().toLowerCase());
            search_objectives(search_value);
        }, 200);
    });

    /**
     * Cut event listener for the objective search input
     */
    $("#objective-search-control").on("cut", function () {
        setTimeout(function () {
            var search_value = $.trim($("#objective-search-control").val().toLowerCase());
            search_objectives(search_value);
        }, 200);
    });

    /**
     * Event listener for when the delete plan modal is shown - populates a list of selected plans for deletion
     */
    $("#delete-plan-modal").on("show", function () {
        build_deleted_plan_list();
    });

    /**
     * Event listener for when the delete plan modal is hidden - resets the modal
     */
    $("#delete-plan-modal").on("hide", function () {
        reset_delete_modal();
    });

    /**
     * Build a list of plans for deletion
     */
    function build_deleted_plan_list() {
        var checked_assessment_plans = $("input[name=\"assessment_plan_containers[]\"]:checked");
        if (checked_assessment_plans.length > 0) {
            $(checked_assessment_plans).each(function (i, input) {
                var hidden_input = $(document.createElement("input")).attr({
                    type: "hidden",
                    value: $(input).val(),
                    name: "assessment_plan_ids[]"
                });
                $("#delete-plan-form").append(hidden_input);

                var delete_list_item = $(document.createElement("li")).html($(input).attr("data-title"));
                $("#delete-plans-list").append(delete_list_item);
                $("#plans-selected").removeClass("hide");
            });
        } else {
            $("#no-plans-selected").removeClass("hide");
        }
    }

    /**
     * Reset the delete modal
     */
    function reset_delete_modal() {
        $("input[name=\"assessment_plan_ids[]\"]").remove();
        $("#delete-plans-list").empty();
        $("#no-plans-selected").addClass("hide");
        $("#plans-selected").addClass("hide");
    }

    /**
     * Hide any forms that don't match the user supplied search input
     * @param search_value
     */
    function hide_unmatched_objectives(search_value) {
        $(".objective-list-item").addClass("hide");
        $.each($(".objective-list-item"), function (i, objective) {
            var objective_code = $(objective).find(".objective-code").text().toLowerCase();
            var objective_name_text = $(objective).find(".objective-name").text().toLowerCase();

            if (objective_code.indexOf(search_value) >= 0 ||
                objective_name_text.indexOf(search_value) >= 0) {
                $(objective).removeClass("hide");
            }
        });
        toggle_objective_list_visibility();
    }

    /**
     * Show matched forms
     */
    function show_matched_objectives() {
        $(".objective-list-item").removeClass("hide");
        $("#objective-list-set").removeClass("hide");
    }

    /**
     * Toggle the no results message when the objective search returns nothing
     */
    function toggle_no_results_message() {
        if (!$(".objective-list-item").not(".hide").length > 0) {
            $("#no-search-results").removeClass("hide");
        } else {
            $("#no-search-results").addClass("hide");
        }
    }

    /**
     * Handle objective searching
     * @param search_value
     */
    function search_objectives(search_value) {
        if (search_value.length > 0) {
            hide_unmatched_objectives(search_value);
        } else {
            show_matched_objectives();
        }
        toggle_no_results_message();
    }

    /**
     * Toggles the visibility of the objective-set-list if all list-items within it are hidden
     */
    function toggle_objective_list_visibility () {
        if (!$(".objective-list-item").not(".hide").length > 0) {
            $("#objective-list-set").addClass("hide");
        } else {
            $("#objective-list-set").removeClass("hide");
        }
    }
});
