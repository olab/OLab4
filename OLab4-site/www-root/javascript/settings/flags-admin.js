var ORGANISATION_ID;

jQuery(document).ready(function ($) {
    if (typeof FLAGS_COLOR_PALETTE != 'undefined' && Array.isArray(FLAGS_COLOR_PALETTE)) {
        color_picker('#flag_color', FLAGS_COLOR_PALETTE);
    } else {
        color_picker('#flag_color');
    }

    /**
     * Enable sorting for item responses
     */
    $(".sortable-items").sortable({
        handle: "td.move-item-response",
        placeholder: "success flags-table-row",
        helper: "clone",
        axis: 'y',
        containment: "document",
        disable: true,
        start: function (event, ui) {
            //clear_response_error();
            var placeholder_item = ui.item.html();
            ui.placeholder.html(placeholder_item);
        },
        stop: function (event, ui) {
            update_flags_orderging();
        }
    });

    function update_flags_orderging() {
        var ids_list = "";
        var set_values = "";
        var ordering = 1;

        $("input[name^='remove-ids']").each(function() {
            ids_list += "&ids_list[]=" + $(this).val();
            set_values += "&id_" + $(this).val() + "=" +  ordering++;
        });

        $.ajax({
            url: "?section=api-flags&org=" + ORGANISATION_ID,
            type: "GET",
            data: "method=update_flags_ordering&" + ids_list + set_values,
            dataType: "json",
            success: function(response) {
                if (response.status !== "success") {
                    console.log(response.data);
                }
            }
        })
    }
});