var post_id;
jQuery(document).ready(function($) {
    $("[data-toggle=\"tooltip\"]").tooltip();

    var public_exams = $(".grading-table").DataTable({
        "dom": 'C<"clear">lfrtip',
        sPaginationType: "full_numbers",
        bSortClasses: false,
        oSearch: { bSmart: false },
        "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]],
        "order": [[ 6, "desc" ]],
        "columns": [
            null,
            null,
            null,
            null,
            null,
            null,
            { "visible": false },
            { "visible": false },
            { "visible": false },
            { "visible": false },
            { "visible": false },
            null,
            { "visible": false }
        ]
    });

    var colvis = new jQuery.fn.dataTable.ColVis( public_exams );
    jQuery(colvis.button() ).insertAfter('.search_div');

    $(".ColVis").show().addClass("pull-left");
    $(".ColVis_Button").addClass("btn").removeClass("ColVis_Button");

    $(".exam-card-status-btn").on("click", function() {
        var clicked = $(this);
        showCard(clicked);
    });

    function showCard(clicked) {
        $(".grading-table-wrapper").hide();
        $(".exam-card-status-btn").removeClass("active");
        $(clicked).addClass("active");
        var statusType = $(clicked).data("status");
        switch (statusType) {
            case "un_complete":
                $("#grading-un_complete-table-wrapper").show();
                break;
            case "complete":
                $("#grading-complete-table-wrapper").show();
                break;
        }

        var dataObject = {
            method : "save_active_card_preference",
            card: statusType
        };

        $.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
            }
        });
    }

    $(".inner-content").on("hide.bs.tooltip", function(e) {
        setTimeout(function() {
            $(".settings_tooltip").show();
        }, 1);
    });

    $(".attempt-info").on("click", function (e) {
        var clicked = $(this);
        post_id = $(clicked).data("post-id");
        $("#post-modal").modal();
    });

    $("#post-modal").on("show.bs.modal", function(e) {
        $("#missing-responses").show();
        $(".modal-body #exam-activity-content").hide();
        var dataObject = {
            method : "get-activity-rows",
            post_id: post_id
        };

        $.ajax({
            url: API_URL,
            data: dataObject,
            type: "GET",
            success: function (data) {
                $("#missing-responses").hide();
                var jsonResponse = JSON.parse(data);
                var new_html = jsonResponse.html;
                $(".modal-body #exam-activity-content").show().html(new_html);
            }
        });
    });

    $("#post-modal").on("click", ".resume", function() {
        var progress_id = $(this).data("id");
        window.location = ENTRADA_RELATIVE + '/exams?section=attempt&action=resume&id=' + post_id + '&progress_id=' + progress_id;
    });

    $("#post-modal").on("click", ".feedback", function() {
        var progress_id = $(this).data("id");
        window.location = ENTRADA_RELATIVE + '/exams?section=feedback&progress_id=' + progress_id;
    });
});