jQuery(function($) {
    var items = [];
    var loading_container = $("#assessment-items-loading");
    var items_container = $("#items-container");
    var item_view_controls = $("#item-view-controls");
    var item_table_container = $("#item-table-container");
    var item_detail_container = $("#item-detail-container");

    create_item_sidebar();

    item_view_controls.children("[data-view=\""+ VIEW_PREFERENCE +"\"]").addClass("active");

    if (VIEW_PREFERENCE === "list") {
        item_table_container.removeClass("hide");
    } else {
        item_detail_container.removeClass("hide");
    }

    if (typeof rubric_id === 'undefined') {
        rubric_id = 0;
    }
    var datatable = $("#items-table").dataTable({
        'sPaginationType': 'full_numbers',
        'bInfo': true,
        'bPaginate': true,
        'bLengthChange': false,
        'bAutoWidth': false,
        'bProcessing': false,
        'sDom': '<"top"l>rt<"bottom"ip><"clear">',
        'sAjaxSource' : '?section=api-list&rubric_id='+rubric_id,
        'bServerSide': true,
        'aoColumns': [
            { 'mDataProp': 'modified', 'bSortable': false },
            { 'mDataProp': 'item_text' },
            { 'mDataProp': 'name' },
            { 'mDataProp': 'item_code' },
            { 'mDataProp': 'responses' }
        ],
        'iDisplayLength': 100,
        'iDisplayStart': 0,
        'oLanguage': {
            'sEmptyTable': 'You currently do not have any items available.',
            'sZeroRecords': (rubric_id == 0 ? 'No Grouped Item was specified' : 'You currently do not have any items available.')
        }
    });

    $(".view-toggle").on("click", function (e) {
        e.preventDefault();
        var selected_view = $(this).attr("data-view");

        if (selected_view === "list") {
            item_table_container.removeClass("hide");
            item_detail_container.addClass("hide");
        } else {
            item_detail_container.removeClass("hide");
            item_table_container.addClass("hide");
        }

        item_view_controls.children().removeClass("active");
        $(this).addClass("active");
        set_view ();
    });

    $("#item-search").keyup(function () {
        datatable.fnFilter($(this).val());
    });


    $(".item-table .item-control").on("click", function (e) {
        e.preventDefault();
    });

    $(".item-selector").on("change", function () {
        if ($(this).closest("table").hasClass("selected")) {
            $(this).closest("table").removeClass("selected");
        } else {
            $(this).closest("table").addClass("selected");
        }
    });

    $("#add-rubric-element").on("click", 'input.add-rubric-item', function() {
        $("#msgs").html("");
        var rubric_id = $("#rubric_id").val();
        var item_id = $(this).val();
        var item_checked = ($(this).is(":checked") ? "1" : "0");
        var url = $("#add-rubric-element").attr("action");

        var form_data = [{method : "add-element", item_id : item_id, add_rubric_item_checked : item_checked}];

        var jqxhr = $.post(url, {method : "add-element", rubric_id : rubric_id, item_id : item_id, add_rubric_item_checked : item_checked}, function(data) {
                if (data.status == "success") {
                    $("#rubric_item_count").html(data.rubric_item_count)
                    display_success([data.msg], "#msgs");
                } else if(data.status == "error") {
                    display_error([data.msg], "#msgs");
                }
            },
            "json"
        );
    });

    function list_view () {}

    function detail_view () {}

    function get_items () {
        $.ajax({
            url: ENTRADA_URL + "/api/item_bank.api.php",
            data: "method=get-items",
            type: 'GET',
            beforeSend: function () {
                loading_container.removeClass("hide");
            },
            complete: function () {
                loading_container.addClass("hide");
                items_container.removeClass("hide");
            },
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $.each(jsonResponse.data, function (key, item) {
                        items.push(item);
                    });
                } else {

                }
            }
        });
    }

    function set_view () {
        var selected_view = item_view_controls.children(".active").attr("data-view");

        $.ajax({
            url: ENTRADA_URL + "/api/item_bank.api.php",
            data: "method=view-preference&selected_view=" + selected_view,
            type: 'POST',
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    if (jsonResponse.data.view === "detail") {
                        detail_view();
                    } else {
                        list_view();
                    }
                } else {

                }
            }
        });
    }


    function create_item_sidebar() {
        var url = ENTRADA_URL + "/admin/assessments/rubrics?section=api-rubric"
        var rubric_id = 0;
        if ($("#rubric_id")) {
            rubric_id = $("#rubric_id").val();
        }
        var input_data = {method: "get-rubric", rubric_id: rubric_id };
        var jqxhr = $.get(url, input_data, function(data) {

        }, "json")
            .done(function(data) {
                var rubric_title = "Grouped Item Elements";
                var items = data.data.elements;
                var count = 0;
                var width = 0;
                if (data.status == "success") {
                    rubric_title = data.data.rubric.rubric_title;
                    items = data.data.elements;
                    count = data.data.count;
                    width = data.data.width;
                }

                add_item_summary(rubric_title, items, count, width);
            });
    }

    function add_item_summary (title, items, count, width) {
        var container = document.createElement("div");
        var icon_container = document.createElement("i");
        var title_container = document.createElement("p");
        var msg_container = document.createElement("p");

        $(container).addClass("timer");
        $(container).addClass("item_summary");
        $(icon_container).addClass("icon-tasks");
        $(title_container).append(icon_container);
        $(title_container).append("&nbsp;" + title);
        $(msg_container).append("Contains <span id=\"rubric_item_count\">" + count + "</span> item(s).<br />");
        $(msg_container).append("Width of Grouped Item set to <span id=\"rubric_item_width\">" + width + "</span>.<br />");

        //if (items.length && items.length > 0) {
        //    $(msg_container).append(document.createElement("ul"))
        //    $(items).each(function(index) {
        //        var item = document.createElement("li");
        //        $(item).append(this.item_text);
        //        $(msg_container).append(item);
        //    });
        //} else {
        //    $(msg_container).append("No Items Attached.");
        //}

        $(container).append(title_container).append(msg_container);
        $(".inner-sidebar").append(container);

        $('.item_summary').affix({
            offset: {
                top: 276
            }
        });
    }
});