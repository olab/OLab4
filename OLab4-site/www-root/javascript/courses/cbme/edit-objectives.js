jQuery(document).ready(function ($) {
    var timeout;

    /**
     * Event listener for saving objectives
     */
    $(".save-objective-btn").on("click", function (e) {
        var objective_id = $(this).attr("data-objective-id");
        var form = document.getElementById("objective-form-" + objective_id);
        var form_data = new FormData(form);

        form_data.append("method", "save-objective");

        var objective_update_request = $.ajax({
            url: ENTRADA_URL + "/admin/courses/cbme?section=api-cbme",
            type: "POST",
            data: form_data,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                show_objective_loading_message(objective_id);
            },
            complete: function() {
                hide_objective_loading_message(objective_id);
            },
            error: function() {
                hide_objective_loading_message(objective_id);
            }
        });

        $.when(objective_update_request).done(function (data) {
            handle_objective_request(data);
        });

        e.preventDefault();
    });

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
     * Show the loading message for a particular objective
     * @param objective_id
     */
    function show_objective_loading_message(objective_id) {
        $("#objective-form-" + objective_id + "-submit").addClass("hide");
        $("#objective-form-" + objective_id + "-loading").removeClass("hide");
    }

    /**
     * Hide the loading message for a particular objective
     * @param objective_id
     */
    function hide_objective_loading_message(objective_id) {
        $("#objective-form-" + objective_id + "-loading").addClass("hide");
        $("#objective-form-" + objective_id + "-submit").removeClass("hide");
    }

    /**
     * Hide any forms that don't match the user supplied search input
     * @param form
     */
    function hide_unmatched_forms(search_value) {
        $(".objective-form").addClass("hide");
        $.each($(".objective-form"), function (i, form) {
            var objective_code = $(form).find(".objective-code").text().toLowerCase();
            var objective_name_text = $(form).find("textarea[name=\"objective_name\"]").text().toLowerCase();
            var objective_description_text = $(form).find("textarea[name=\"objective_description\"]").text().toLowerCase();
            var objective_secondary_description_text = $(form).find("textarea[name=\"objective_secondary_description\"]").text().toLowerCase();

            if (objective_code.indexOf(search_value) >= 0 ||
                objective_name_text.indexOf(search_value) >= 0 ||
                objective_description_text.indexOf(search_value) >= 0 ||
                objective_secondary_description_text.indexOf(search_value) >= 0) {
                $(form).removeClass("hide");
            }
        });
    }

    /**
     * Show matched forms
     */
    function show_matched_forms() {
        $(".objective-form").removeClass("hide");
    }

    /**
     * Toggle the no results message when the objective search returns nothing
     */
    function toggle_no_results_message() {
        if (!$(".objective-form").not(".hide").length > 0) {
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
            hide_unmatched_forms(search_value);
        } else {
            show_matched_forms();
        }
        toggle_no_results_message();
    }

    /**
     * Handle the request to save an objective
     */
    function handle_objective_request(data) {
        var jsonResponse = safeParseJson(data, javascript_translations.objective_update_error);
        $(".objective-msgs").empty();
        if (jsonResponse.status == "success") {
            if (jsonResponse.data.hasOwnProperty("objective_id")) {
                var objective_id = jsonResponse.data.objective_id;
                display_success(jsonResponse.data.messages, "#objective-form-" + objective_id + "-msgs");
            }
        } else {
            if (jsonResponse.data.hasOwnProperty("objective_id")) {
                var objective_id = jsonResponse.data.objective_id;
                $(".objective-msgs").empty();
                display_error(jsonResponse.data.messages, "#objective-form-" + objective_id + "-msgs");
            }
        }
    }
});