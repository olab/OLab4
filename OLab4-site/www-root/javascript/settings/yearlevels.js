var ENTRADA_URL;
var ORGANISATION_ID;

jQuery(function($) {
    $("input[name^='lylevel_id']").on("click", function() {
        alert($(this).val());
        var value = $(this).val();
        var checked = 0;
        if ($(this).attr("checked")) {
            var checked = 1;
        }

        $.ajax({
            url: ENTRADA_URL + "/api/learner-year-levels.api.php",
            data: "method=add-delete&lvl_id=" + value + "&checked=" + checked,
            type: "POST",
            datatype: "json"
        });
    });
});