jQuery(document).ready(function ($) {

    $(".category-performance-summary").DataTable({
        "paging":   false,
        "ordering": false,
        "info":     false,
        "searching":   false
    });

    var reports_table = $(".objectives-report").DataTable({
        sPaginationType: 'full_numbers',
        bSortClasses: false,
        aoColumnDefs: [{
            aTargets: [0, -1],
            bSortable: false
        }],
        oSearch: { bSmart: false },
        aaSorting: [[ 0, 'asc']],
        "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]],
        "columns": [
            null,
            null,
            null,
            null,
            null,
            null
        ]
    });
    //adds the Show/Hide columns button
    var colvis = new jQuery.fn.dataTable.ColVis( reports_table );
    jQuery(colvis.button() ).insertAfter('.columns span');

    $(".ColVis").show().addClass("pull-left");
    $(".ColVis_Button").addClass("btn").removeClass("ColVis_Button");
});