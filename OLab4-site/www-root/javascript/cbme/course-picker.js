jQuery(document).ready(function ($) {
    /**
     * Event listener for the course dropdown
     */
    $("#cbme-course-picker").on("change", function () {
        var selected_course_id = $(this).val();
        set_course_preference(selected_course_id);
    });

    /**
     * Handles course picker preferences
     * @param course_id
     * @param course_name
     */
    function set_course_preference(course_id) {
        var course_preference_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-course-preference", "course_id": course_id}
        });

        $.when(course_preference_request).done(function (data) {
            var url;
            var proxy_id = checkForUrlParameter("proxy_id");
            if (proxy_id != false) {
                url = window.location.href.replace(window.location.search, "?proxy_id=" + proxy_id);
            } else {
                url = window.location.href.replace(window.location.search, "");
            }
            window.location = url;
        });
    }

    function checkForUrlParameter(parameter) {
        var query = window.location.search.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (decodeURIComponent(pair[0]) === parameter) {
                if (typeof(pair[1]) !== "undefined") {
                    return decodeURIComponent(pair[1]);
                }
            }
        }
        return false;
    }
});
