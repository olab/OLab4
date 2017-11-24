/**
 * Created by eah5 on 2016-05-13.
 */
jQuery(function($) {

    // jQueryUI datepicker for the Applicable Date input
    $('#grading_scale_applicable_date').datepicker({dateFormat: "yy-mm-dd"});
    $(".datepicker-icon").on("click", function () {
        if (!$(this).prev("input").is(":disabled")) {
            $(this).prev("input").focus();
        }
    });

    // sort function for dataTable to handle values from inputs
    $.fn.dataTableExt.afnSortData['dom-text-numeric'] = function  ( oSettings, iColumn )
    {
        var aData = [];
        $( 'td:eq('+iColumn+') input', oSettings.oApi._fnGetTrNodes(oSettings) ).each( function () {
            aData.push( this.value * 1 );
        } );
        return aData;
    };

    // Datatable for the range table
    var range_table = $('#grading_scale_range').dataTable(
        {
            "bPaginate": false,
            "bLengthChange": false,
            "bFilter": false,
            "bSort": true,
            "bInfo": false,
            "bAutoWidth": false,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumnDefs": [ { "bSortable": false, "aTargets": [0, 2, 3, 4] },
                { "sSortDataType": "dom-text-numeric", "aTargets": [ 1 ] },
                { "sType": "numeric", "aTargets": [ 1 ]} ]
        }
    );

    // remove rows in the scale range
    $('#grading_scale_range tbody').on( 'click', 'img.list-cancel-image', function () {
        range_table.fnDeleteRow($(this).parents('tr')[0]);
    });

    // re-sort the table if the percentage value changes
    $('#grading_scale_range').on('change', 'td input.numeric_grade_min', function(e) {
        e.preventDefault();
        range_table.fnDraw();
    });

    // add a new blank range entry to the table
    $('#add-range').on('click', function(e){
        e.preventDefault();
        range_table.fnAddData([
            '<input class="input-small" type="text" name="letter_grade[]" required/>',
            '<input class="input-mini numeric_grade_min" type="text" name="numeric_grade_min[]" required/>',
            '<input class="input-mini" type="text" name="gpa[]" />',
            '<input class="input-small" type="text" name="notes[]" />',
            '<span class="input-mini"><img src="' + ENTRADA_URL + '/images/action-delete.gif" class="list-cancel-image"></span>'
        ]);
        jQuery('#grading_scale_range tr').last().find('input:text').first().select()
    });
});
