var url = ENTRADA_URL + "/admin/reports?section=curriculum&mode=ajax";
$ = jQuery.noConflict();
$(document).ready(function () {
    $("#tag-set").on("change", function(e) {
        getTags($(this).val());
        e.preventDefault;
    });
    getTags($("#tag-set").val());

    $("#print-button").on("click", function() {
        var print_window = window.open("", "PRINT", "height=400,width=960, left=100, top=100");
        var document_title = $(".report_title").text();
        var window_html = "<html>" +
            "<head>" +
            "<title>" + document_title + "<\/title>" +
            "<\/head>" +
            "<body>" +
            "<h1>" + document_title + "<\/h1>" +
            "" + $("#curriculum-report-form #tags-list").html() + "" +
            "<\/body>" +
            "<\/html>";
        print_window.document.write(window_html);

        print_window.document.close(); // necessary for IE >= 10
        print_window.focus(); // necessary for IE >= 10*/

        print_window.print();
        print_window.close();

        return true;
    });

    $(".mapping_direction_btn").on("click", function() {
        $(".mapping_direction_btn").removeClass("active");
        $(this).addClass("active");
        $("#mapping_direction").val($(this).data("direction"));
    });
});

function getTags(id) {
    var objective_id = id;
    $("#tags-list").html("<p class='text-center text-muted'><i class='fa fa-spin fa-spinner'></i> Loading curriculum tags</p>");
    $.getJSON(url, {
        method: "get-curriculum-tags",
        objective_id: objective_id
    }, function (json) {
        if (json) {
            $("#tags-list").empty();
            if (json.status == "success") {
                $("#tags-list").append("<h2>" + json.tag_set_name + "</h2>" +
                    (json.tag_set_description ? "<p>" + json.tag_set_description + "</p>" : ""));
                $.each(json.data, function (index, tag) {
                    $("#tags-list").append("<div class=\"tag-item\" data-id=\"" + tag.objective_id + "\">" +
                        "<i class=\"fa fa-chevron-down\"></i>" +
                        "<span class=\"tag-path\">" + json.path.join(" / ") + "</span>" +
                        "<h3>" + tag.objective_name + "</h3>" +
                        (tag.objective_description ? "<p>" + tag.objective_description + "</p>" : "") +
                        "</div>");
                });

                $(".tag-item").on("click", function (e) {
                    getMappedTags($(this).data("id"), this);
                    e.preventDefault();
                });
            } else {
                display_msg("error", [json.data], "#tags-list");
            }
        }
    });
}

function getMappedTags(id, e) {
    var objective_id = id;
    if ($(e).hasClass("active")) {
        $(e).removeClass("active");
    } else {
        $.getJSON(url, {
            method: "get-mapped-tags",
            objective_id: objective_id,
            mapping_direction: $("#mapping_direction").val()
        }, function (json) {
            if (json) {
                if (json.status == "success") {
                    $(e).addClass("active");
                    if (!$(e).next().hasClass("tag-children")) {
                        console.log($(e));
                        $(e).after("<ul class=\"tag-children\"></ul>");
                        $.each(json.data, function (index, tag) {
                            $(e).next(".tag-children").append("<li class=\"tag-item\" data-id=\"" + tag.objective_id + "\">" +
                                "<i class=\"fa fa-chevron-down\"></i>" +
                                "<small class=\"tag-path\">" + tag.path.join(" / ") + "</small>" +
                                "<h4>" + (tag.objective_code && tag.objective_code != tag.objective_name ? tag.objective_code + ": " : "") + tag.objective_name + "</h4>" +
                                (tag.objective_description ? "<p>" + tag.objective_description + "</p>" : "") +
                                "</li>");
                        });
                        $(".tag-item").off();
                        $('.tag-item').on("click", function (e) {
                            getMappedTags($(this).data("id"), this);
                            e.preventDefault();
                        });
                    }
                }
            }
        });
    }
}