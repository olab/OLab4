jQuery(document).ready(function ($) {
    $(".submit-progress-change").on("click", function(e) {
        e.preventDefault();
        var objective_id = $("[name=\"objective_id\"]").val();
        var proxy_id = $("[name=\"proxy_id\"]").val();
        var action = $("[name=\"action\"]").val();
        var reason = $("[name=\"reason\"]").val();
        var objective_set = $("[name=\"objective_set\"]").val();
        var course_id = $("[name=\"course_id\"]").val();

        var update_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {
                "method": "update-objective-completion-status",
                "action": action,
                "proxy_id": proxy_id,
                "objective_id": objective_id,
                "course_id": course_id,
                "reason": reason,
                "objective_set": objective_set
            }
        });

        $.when(update_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "Invalid Json");
            $.animatedNotice(jsonResponse.data, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
            setTimeout(function() {
                location.reload();
            }, 2200);
        });
    });
    $("#reason-description").on("keyup", function() {
       if (!$("#reason-description").val() && $(".submit-progress-change").hasClass('incomplete-objective')) {
           $(".submit-progress-change").attr("disabled", true);
       } else {
           $(".submit-progress-change").removeAttr("disabled");
       }
    });
});