
var audience = {};

jQuery(document).ready(function ($) {

    //$("#release_start_date").val("");
    //$("#release_start_time").val("");
    //$("#release_end_date").val("");
    //$("#release_end_time").val("");

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $(".timepicker").timepicker({
        defaultTime: $(this).val(),
        minutes: {
            starts: 0,
            ends: 59,
            interval: 5
        }
    });

    $(".use_date").click(function() {
        var clicked = $(this);
        var id = clicked.attr("id");
        var date_id = clicked.data("date-name");
        var time_id = clicked.data("time-name");

        var calendar_icon = $("#" + date_id).next(".add-on");

        if (clicked.prop("checked") == true ) {
            $("#" + date_id).val($("#" + date_id).data("default-date")).attr("disabled", false);
            $("#" + time_id).val($("#" + time_id).data("default-time")).attr("disabled", false);
            $(calendar_icon).addClass("pointer");
        } else {
            $("#" + date_id).data("default-date", $("#" + date_id).val());
            $("#" + time_id).data("default-time", $("#" + time_id).val());
            $("#" + date_id).val("").attr("disabled", true);
            $("#" + time_id).val("").attr("disabled", true);
            $(calendar_icon).removeClass("pointer");
        }
    });
});