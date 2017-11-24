jQuery(document).ready(function ($) {
    $("#targets-pending-btn").on("click", function (e) {
        $(".targets-container").addClass("hide");
        $("#targets-pending-container").removeClass("hide");
        $(".target-status-btn").removeClass("active");
        $(this).addClass("active");

        save_preferences();
        e.preventDefault();
    });

    $("#targets-inprogress-btn").on("click", function (e) {
        $(".targets-container").addClass("hide");
        $("#targets-inprogress-container").removeClass("hide");
        $(".target-status-btn").removeClass("active");
        $(this).addClass("active");

        save_preferences();
        e.preventDefault();
    });

    $("#targets-complete-btn").on("click", function (e) {
        $(".targets-container").addClass("hide");
        $("#targets-complete-container").removeClass("hide");
        $(".target-status-btn").removeClass("active");
        $(this).addClass("active");

        save_preferences();
        e.preventDefault();
    });

    $("#target-search-input").on("keyup", function () {
        var show_no_targets_message = true;
        if ($("#delegation-progress-mode").length) {
            show_no_targets_message = $("#delegation-progress-mode").val();
            console.log(show_no_targets_message);
        }
        var search_text = $(this).val().toLowerCase();
        var selected_view = $(".view-toggle.active").attr("data-view");

        if (search_text.length === 0) {
            $(".no-search-targets").remove();
            $(".target-block").removeClass("hide");
            if (selected_view === "list") {
                $(".target-table").removeClass("hide");
            }
        } else {
            $(".target-block").each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(search_text) >= 0) {
                    $(this).removeClass("hide").addClass("visible");
                } else {
                    $(this).addClass("hide").removeClass("visible");
                }
            });

            var total_pending = $(".target-pending-block.visible").length;
            var total_inprogress = $(".target-inprogress-block.visible").length;
            var total_complete = $(".target-complete-block.visible").length;

            if (total_pending === 0) {
                if ($("#no-search-targets-pending").length === 0 && $("#no-pending-targets").length === 0) {
                    var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-search-targets-pending"}).html("No pending targets found matching your search.");
                    $("#no-pending-targets").removeClass("hide");
                    $("#targets-pending-table").addClass("hide");
                    if (show_no_targets_message > 0) {
                        $("#targets-pending-container").append(no_search_targets_p);
                    }
                }
            } else {
                $("#no-search-targets-pending").remove();
                if (selected_view === "list") {
                    $("#targets-pending-table").removeClass("hide");
                }
            }

            if (total_inprogress === 0) {
                if ($("#no-search-targets-inprogress").length === 0 && $("#no-inprogress-targets").length === 0) {
                    if (show_no_targets_message) {
                        var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-search-targets-inprogress"}).html("No targets in progress found matching your search.");
                        $("#no-inprogress-targets").removeClass("hide");
                        $("#targets-inprogress-table").addClass("hide");
                        if (show_no_targets_message > 0) {
                            $("#targets-inprogress-container").append(no_search_targets_p);
                        }
                    }
                }
            } else {
                $("#no-search-targets-inprogress").remove();
                if (selected_view === "list") {
                    $("#targets-inprogress-table").removeClass("hide");
                }
            }

            if (total_complete === 0) {
                if ($("#no-search-targets-complete").length === 0 && $("#no-complete-targets").length === 0) {
                    var no_search_targets_p = $(document.createElement("p")).addClass("no-search-targets").attr({id: "no-search-targets-complete"}).html("No completed targets found matching your search.");
                    $("#no-complete-targets").removeClass("hide");
                    $("#targets-complete-table").addClass("hide");
                    if (show_no_targets_message > 0) {
                        $("#targets-complete-container").append(no_search_targets_p);
                    }
                }
            } else {
                $("#no-search-targets-complete").remove();
                if (selected_view === "list") {
                    $("#targets-complete-table").removeClass("hide");
                }
            }
        }

        $(".icon-trash").removeClass("select-all");
        $(".icon-bell").removeClass("select-all");
        $(".icon-download-alt").removeClass("select-all");

        $("input[name=\"delete[]\"]").each(function () {
            $(this).prop("checked", false);
        });

        $("input[name=\"generate-pdf[]\"]").each(function () {
            $(this).prop("checked", false);
        });

        $("input[name=\"remind[]\"]").each(function () {
            $(this).prop("checked", false);
        });
    });

    $(".view-toggle").on("click", function (e) {
        var selected_view = $(this).attr("data-view");
        $(".view-toggle").removeClass("active");
        $(this).addClass("active");

        if (selected_view === "grid") {
            $(".targets-container .target-grid").removeClass("hide");
            $(".targets-container .target-table").addClass("hide");
        } else {
            $(".targets-container .target-grid").addClass("hide");
            $(".targets-container .target-table").removeClass("hide");
        }

        save_preferences();
        e.preventDefault();
    });

    function save_preferences () {
        var selected_target_status = $(".target-status-btn.active").attr("data-target-status");
        var selected_view = $(".view-toggle.active").attr("data-view");

        var preference_request = $.ajax({
            url: ENTRADA_URL + "/assessments/assessment?section=api-assessment",
            data: "method=save-view-preferences&distribution_id="+ distribution_id +"&target_status_view=" + selected_target_status + "&view=" + selected_view,
            type: "POST"
        });

        $.when(preference_request).done(function (data) {
            console.log(data);
        });
    }
});
