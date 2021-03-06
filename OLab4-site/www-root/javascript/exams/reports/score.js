jQuery(document).ready(function ($) {
    $("#show_controls").on("click", function() {
        var search_form = $("#search_form");
        if ($(search_form).hasClass("hide")) {
            $(search_form).removeClass("hide").addClass("show");
            $(this).text("Hide Controls");
        } else {
            $(search_form).removeClass("show").addClass("hide");
            $(this).text("Show Controls");
        }
    });

    var csv = new Array;

    function ltrim(stringToTrim) {
        return stringToTrim.replace(/^0+/,"");
    }

    function ltrim_space(stringToTrim) {
        return stringToTrim.replace(/^\s+/,"");
    }

    function rtrim_space(stringToTrim) {
        return stringToTrim.replace(/\s+$/,"");
    }

    //initiates the DataTable and hides the student ID and method columns
    progress_table = $("#progress-table").DataTable({
        sPaginationType: 'full_numbers',
        "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]],
        "columns": [
            null,
            null,
            null,
            null,
            { "visible": false },
            null,
            null,
            null,
            { "visible": false },
            { "visible": false },
            null,
            null,
            { "visible": false }
        ]
    });
    //adds the Show/Hide columns button
    var colvis = new $.fn.dataTable.ColVis( progress_table );
    $(colvis.button() ).insertBefore('#download-csv');

    $(".ColVis").show().addClass("pull-left");
    $(".ColVis_Button").addClass("btn").removeClass("ColVis_Button");

    $(".progress_menu").click(function() {
        progress_id = $(this).data("id");
    });

    $("button.reopen").click(function( ) {
        reopen_progress(progress_id);
    });

    $("button.delete-progress").click(function( ) {
        delete_progress(progress_id);
    });

    //exports a csv file of all data the is visable
    jQuery("#download-csv").click(function() {
        table2csv("#progress-table");
        if (csv.length > 0 && csv != "") {
            jQuery("#csv-form").submit();
        } else {
            $.growl.error({ title: "Error", message: "No CSV data to export." });
        }
    });

    //generate the csv file based on the tables shown
    function table2csv(table) {
        csv.length = 0;
        var headers = "";
        var fields = "";

        // Get header names
        var headers_found = jQuery(table + ' thead').find('th');
        $.each(headers_found, function() {
            if (!$(this).hasClass("edit_menu")) {
                var header = jQuery(this).text().replace(/"/g, '\&quot;');
                header = ltrim_space(header);
                header = rtrim_space(header);
                headers += "\"" + header + "\",";
            }
        });

        headers = headers.substring(0, headers.length - 2);
        csv.push(headers);
        // get table data

        jQuery(table + ' tbody tr').each(function() {
            var fields_found = jQuery(this).find('td');
            $.each(fields_found, function () {
                if (!$(this).hasClass("edit_menu")) {
                    var field = jQuery(this).text().replace(/"/g, '\&quot;');
                    field = ltrim_space(field);
                    field = rtrim_space(field);
                    fields += "\"" + field + "\",";
                }
            });

            fields = fields.substring(0, fields.length - 2);
            csv.push(fields);
            fields = "";
        });

        var json_csv = JSON.stringify(csv);
        jQuery("#csv-hidden-field").val( json_csv);
    }

});