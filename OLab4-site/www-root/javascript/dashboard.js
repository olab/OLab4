var ENTRADA_URL;

jQuery(function($) {
    $("#view-in-progress-assessments").on("click", function() {
        var filter_requests = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-tasks",
            data: "method=save-assessments-filters&task_status[]=inprogress",
            type: "POST"
        });

        $.when(filter_requests).done(function (data) {
            var jsonResponse = safeParseJson(data, "Unknown Error");
            if (jsonResponse.status === "success") {
                window.location = ENTRADA_URL + "/assessments";
            }
        });
    });
});