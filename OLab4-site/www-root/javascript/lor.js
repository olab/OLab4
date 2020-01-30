var timeout;
var array_chunk = function(arr, chunkSize) {
    var groups = [], i;
    for (i = 0; i < arr.length; i += chunkSize) {
        groups.push(arr.slice(i, i + chunkSize));
    }
    return groups;
};

jQuery(document).ready(function($) {
    toggle_object_type();

    $("#object_type_select").on("change", function() {
        toggle_object_type();
    });
});

jQuery(function ($) {
    var dragdrop = false;
    var learning_object_drop_overlay = $("#learning_object_drop_overlay");
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    }

    if (dragdrop) {
        /**
         * Event listeners for drag and drop image uploading
         */

        if (window.FileReader) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $("#resource_file").attr({"src": e.target.result}).css("height", "50%").css("width", "50%");
                $("#screenshot-img").removeClass("hide").attr({"src": e.target.result});
            };
        } else {
            $("#resource_file").hide().css("height", "auto");
        }

        var timer;

        $(".modal-body").on("dragover", function (event) {
            clearTimeout(timer);
            event.preventDefault();
            event.stopPropagation();

            $("#upload-container").addClass("hide");
            learning_object_drop_overlay.removeClass("hide");
        });

        $(".modal-body").on("dragleave", function (event) {
            event.preventDefault();
            event.stopPropagation();

            timer = setTimeout(function() {
                learning_object_drop_overlay.addClass("hide");
                $("#upload-container").removeClass("hide");
            }, 200);

            return false;
        });

        $(".screenshot-modal-body").on("drop", function (event) {
            event.preventDefault();
            event.stopPropagation();

            event.dataTransfer = event.originalEvent.dataTransfer;
            var file = event.dataTransfer.files[0];

            learning_object_drop_overlay.addClass("hide");

            $("#upload-container").removeClass("hide");
            if (file.type.match('image/*')) {
                if (window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        $("#screenshot-img").removeClass("hide").attr({"src": event.target.result});
                        $("#resource_file").attr({"src": event.target.result});
                        $("#learning-object-screenshot-hidden").val($("#resource_file").prop('src'));
                    }
                    $(".learning-object-upload-text").addClass("hide");
                    $(".learning-object-upload-span").html(file.name);
                    reader.readAsDataURL(file);
                    upload_file(file);
                }
            } else {
                $("#screenshot-img").addClass("hide").removeAttr("src");
            }
        });

        $(".lm-modal-body").on("drop", function (event) {
            event.preventDefault();
            event.stopPropagation();
            var module_type = $("#object_type_select").val();

            event.dataTransfer = event.originalEvent.dataTransfer;
            var file = event.dataTransfer.files[0];

            $("#lm-upload-container").removeClass("hide");
            if (file.type.match('application/*zip*')) {
                if (window.FileReader) {
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        $("#learning-object-" + module_type + "-title").html(event.target.result);
                    }
                    $(".learning-object-upload-text").addClass("hide");
                    $(".learning-modules-upload-span").html(file.name);
                    reader.readAsDataURL(file);
                    upload_lm_file(file);
                }
            } else {
                $("#screenshot-img").addClass("hide").removeAttr("src");
            }
        });
    }

    $(document).on("change", "#learning-object-screenshot-filename", function (e) {
        e.preventDefault();
        console.log("#learning-object-screenshot-filename change");
        if (this.files && this.files[0] && this.files[0].type.match("image/*")) {
            if (window.FileReader) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $("#screenshot-img").removeClass("hide").attr({"src": e.target.result});
                    $("#resource_file").attr({"src": e.target.result});
                };
                $(".learning-object-upload-text").addClass("hide");
                $(".learning-object-upload-span").html(this.files[0].name);
                reader.readAsDataURL(this.files[0]);
            }
        } else {
            $("#screenshot-img").addClass("hide").removeAttr("src");
        }
    });

    $(document).on("change", "#learning-object-module-filename", function (e) {
        var module_type = $("#object_type_select").val();
        e.preventDefault();

        if (this.files && this.files[0] && this.files[0].type.match("application/*zip*")) {
            if (window.FileReader) {
                var reader = new FileReader();

                $(".learning-object-upload-text").addClass("hide");
                $(".learning-module-upload-span").html(this.files[0].name);
                $("#learning-object-" + module_type + "-title").html(this.files[0].name);
                reader.readAsDataURL(this.files[0]);
            }
        }
    });

    function upload_lm_file (file) {
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        var valid_file_type = true;
        var lm_type = $("#object_type_select").val();
        var file_size = file.size;
        if (file_size > 0) {
            $(file).each(function (i, v) {
                fd.append("upload[]", v);
            });
        }

        if (file_size <= 300000000) {
            $(".learning-module-upload-text").html("Uploading file, this may take a few moments.");

            xhr.open("POST", ENTRADA_URL + "/api/lor.api.php?method=upload-learning-module&type=" + lm_type, true);
            xhr.send(fd);

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.status == "success") {
                        $("#upload-image-label").html(file.name);
                    }
                }
            }
        } else {
            display_error(["The file you are attempting to upload exceeds the maximum file size limit of 300MB, please select a file with a size of 300MB or less."], "#event-resource-msgs", "append");
        }
    }

    function upload_file (file) {
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        var valid_file_type = true;
        var file_size = file.size;
        if (file_size > 0) {
            $(file).each(function (i, v) {
                fd.append("upload[]", v);
            });
        }
        switch (file.type) {
            case "image/jpeg" :
            case "image/gif" :
            case "image/png" :
                valid_file_type = true;
                break;
        }

        if (file_size <= 300000000) {
            $(".learning-object-upload-text").html("Uploading file, this may take a few moments.");

            xhr.open("POST", ENTRADA_URL + "/api/lor.api.php?method=upload-files", true);
            xhr.send(fd);

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.status == "success") {
                        $("#upload-image-label").html(file.name);
                    }
                }
            }
        } else {
            display_error(["The file you are attempting to upload exceeds the maximum file size limit of 300MB, please select a file with a size of 300MB or less."], "#event-resource-msgs", "append");
        }
    }

    if (!window.FileReader) {
        $("#learning-object-screenshot-filename").removeClass("hide");
    }

    $("#load-learning-objects").on("click", function (e) {
        e.preventDefault();
        if (!$(this).hasClass("load-learning-objects-disabled")) {
            $(this).addClass("loading");
            get_learning_objects(true);
        }
    });

    $("#learning-object-search-form").on("submit", function (e) {
        e.preventDefault();
    });

    $("#learning-object-search").keyup(function (e) {
        var keycode = e.keyCode;

        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32 || keycode == 13   || keycode == 8) {

            $("#learning-objects-no-results").remove();
            $("#learning-objects-table tbody").empty();
            $("#learning-objects-table").addClass("hide");
            $("#learning-objects-list").empty();
            $("#learning-objects-list").addClass("hide");
            $("#load-learning-objects").addClass("hide");
            $("#learning-objects-loading").removeClass("hide");

            clearTimeout(timeout);
            timeout = window.setTimeout(get_learning_objects, 700, false);
        }
    });

    $("#object-type-search").on("change", function() {
        $("#learning-objects-no-results").remove();
        $("#learning-objects-table tbody").empty();
        $("#learning-objects-table").addClass("hide");
        $("#learning-objects-list").empty();
        $("#learning-objects-list").addClass("hide");
        $("#load-learning-objects").addClass("hide");
        $("#learning-objects-loading").removeClass("hide");

        clearTimeout(timeout);
        timeout = window.setTimeout(get_learning_objects, 700, false);
    });

    $("#delete-learning-object-modal").on("show.bs.modal", function (e) {
        $("#learning-objects-selected").addClass("hide");
        $("#no-learning-objects-selected").addClass("hide");

        var learning_objects_to_delete = $("#learning-objects-table input[name='learning_objects[]']:checked").map(function () {
            return this.value;
        }).get();

        if (learning_objects_to_delete.length > 0) {
            $("#learning-objects-selected").removeClass("hide");
            $("#delete-learning-objects-modal-delete").removeClass("hide");

            var list = document.createElement("ul");

            $("#learning-objects-table input[name='learning_objects[]']:checked").each(function(index, element) {
                var list_item = document.createElement("li");
                var learning_object_id = $(element).val();
                $(list_item).append($("#learning_object_title_link_" + learning_object_id).html());
                $(list).append(list_item);
            });

            $("#delete-learning-objects-container").append(list);
        } else {
            $("#no-learning-objects-selected").removeClass("hide");
            $("#delete-learning-objects-modal-delete").addClass("hide");
        }
    });

    $("#delete-learning-object-modal").on("hide.bs.modal", function (e) {
        $("#delete-learning-objects-container").html("");
    });

    $("#delete-learning-objects-modal-delete").on("click", function(e) {
        e.preventDefault();

        var url = $("#delete-learning-object-modal-form").attr("action");
        var learning_objects_to_delete = $("#learning-objects-table input[name='learning_objects[]']:checked").map(function () {
            return this.value;
        }).get();

        var learning_object_data = {
            "method": "delete-learning-objects",
            "delete_ids": learning_objects_to_delete
        };

        $("#learning-objects-selected").removeClass("hide");
        $("#delete-learning-objects-modal-delete").removeClass("hide");

        var jqxhr = $.post(url, learning_object_data, function(data) {
                if (data.status == "success") {
                    $(data.learning_object_ids).each(function(index, element) {
                        $("input[name='learning_objects[]'][value='" + element + "']").parent().parent().remove();
                        display_success([data.msg], "#learning-object-msgs");
                    });
                } else if(data.status == "error") {
                    display_error([data.msg], "#learning-object-msgs");
                }
            },
            "json"
        ).done(function(data) {
            $("#learning-objects-no-results").remove();
            $("#learning-objects-table tbody").empty();
            $("#learning-objects-table").addClass("hide");
            $("#load-learning-objects").addClass("hide");
            $("#learning-objects-loading").removeClass("hide");

            get_learning_objects(true);

            $("#delete-learning-object-modal").modal("hide");
        });
    });

    $("#learning-object-information").on("click", "#add-external-author-btn", function (e) {
        e.preventDefault();

        $("#external-authors-controls").removeClass("hide");
    });


    $("#add-external-user-btn").on("click", function (e) {
        e.preventDefault();

        add_external_author();
    });

    $("#cancel-author-btn").on("click", function (e) {
        e.preventDefault();

        $("#external-authors-controls").addClass("hide");
        $("#msgs").empty();

        if ($(".internal-author-list-item").length === 0) {
            $("#author-list-internal").addClass("hide");
        }

        reset_external_author_controls();
    });

    $("#learning-object-information").on("click", ".remove-selected-author",  function () {
        var author_id = $(this).parent().parent().data("id");

        $(this).parent().parent().remove();
        $("#selected-internal-author-" + author_id).remove();

        if ($(".internal-author-list-item").length === 0) {
            $("#author-list-internal").addClass("hide");
        }
    });

    var author_autocomplete = $("#learning-object-authors-search").autocomplete({
        source: "?section=api-lor&method=get-organisation-users",
        minLength: 3,
        appendTo: $("#autocomplete-list-container"),
        open: function () {
            $("#learning-object-authors-search").removeClass("searching");
            $("#learning-object-authors-search").addClass("search");
        },

        close: function (e) {
            $("#add-external-author-btn").remove();
            $("#learning-object-authors-search").removeClass("searching");
            $("#learning-object-authors-search").addClass("search");
        },

        select: function (e, ui) {
            build_selected_author_item(ui.item.value, ui.item.label, ui.item.role);
            e.preventDefault();
        },

        search: function () {
            $("#learning-object-authors-search").removeClass("search");
            $("#learning-object-authors-search").addClass("searching");
        },

        focus: function (e, ui) {
            e.preventDefault();
        }
    }).data("autocomplete");

    if (author_autocomplete) {
        author_autocomplete._renderItem = function(ul, item) {
            var user_li                = $(document.createElement("li")).data("item.autocomplete", item);
            var template_a             = $(document.createElement("a"));
            var photo_div              = $(document.createElement("div")).addClass("external-author-photo-container");
            var photo_img              = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/headshot-male-small.gif"}).addClass("external-author-photo");
            var details_div            = $(document.createElement("div")).addClass("external-author-details");
            var secondary_details_span = $(document.createElement("span")).addClass("external-author-secondary-details");
            var name_span              = $(document.createElement("span")).addClass("external-author-name");
            var email_span             = $(document.createElement("span")).addClass("external-author-email");
            var group_role_span        = $(document.createElement("span")).addClass("pull-right");

            photo_div.append(photo_img);
            name_span.html(item.label);
            email_span.html(item.email);
            group_role_span.html(item.role + " author");

            if (item.role === "Internal") {
                group_role_span.addClass("badge-green");
            } else {
                group_role_span.addClass("badge-grey");
            }

            $(secondary_details_span).append(group_role_span);
            $(details_div).append(photo_div).append(name_span).append(secondary_details_span).append(email_span);
            $(template_a).append(details_div);
            $(user_li).append(template_a);

            return (user_li.appendTo(ul));
        };

        author_autocomplete._renderMenu = function(ul, items) {
            $.each(items, function(index, item) {
                author_autocomplete._renderItem(ul, item);
            });

            build_external_authors_button();
        };

        author_autocomplete._resizeMenu = function() {
            this.menu.element.outerWidth(286);
        }
    }

    $("#learning-object-authors-search").on("click", function () {
        if ($("#learning-object-authors-search").val()) {
            $("#autocomplete-list-container .ui-autocomplete").css("display", "block");
            build_external_authors_button();
        }
    });

    $("#choose-file").on("click", function(e) {
        $("#learning-object-screenshot-filename").click();
    });

    $("#learning-object-screenshot-filename").on("change", function(e) {
        e.preventDefault();

        if (this.files && this.files[0] && this.files[0].type.match("image/*")) {
            if (window.FileReader) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $("#screenshot-img").removeClass("hide");
                    $("#screenshot-img").attr({"src": e.target.result});
                    $("#file-name").html($("#learning-object-screenshot-filename").val());
                };

                reader.readAsDataURL(this.files[0]);
            }
        } else {
            $("#screenshot-img").addClass("hide");
            $("#screenshot-img").removeAttr("src");
            $("#file-name").html("");
        }
    });

    $('#list-view').on('click',function(e) {
        $('#grid-view').removeClass('active');
        $('.card').removeClass('card').addClass('list');
        $('.card-deck').removeClass('row-fluid');
        $('.card-img-top').removeClass('card-img-top').addClass('hidden').addClass('list-image');
        $('.card-head').removeClass('card-head').addClass('list-head');
        $('.card-title').removeClass('card-title').addClass('list-title');
        $('.card-block').removeClass('card-block').addClass('list-block');
        $('.card-data').removeClass('card-data').addClass('list-data');
        $('#learning-objects-container').addClass('list-spacing');
    });
    $('#grid-view').on('click',function(e) {
        $('#list-view').removeClass('active');
        $('.card-deck').addClass('row-fluid');
        $('.list').removeClass('list').addClass('card');
        $('.list-image').removeClass('list-image').removeClass('hidden').addClass('card-img-top');
        $('.list-title').removeClass('list-title').addClass('card-title');
        $('.list-block').removeClass('list-block').addClass('card-block');
        $('.list-data').removeClass('list-data').addClass('card-data');
        $('.list-head').removeClass('list-head').addClass('card-head');
        $('#learning-objects-container').removeClass('list-spacing');
    });
});

function get_learning_objects (clicked) {
    var total_rows = jQuery(".data-total").length;
    var offset = total_rows;
    var search_value = jQuery("#learning-object-search").val();

    var get_learning_objects_request = jQuery.ajax({
        url: "?section=api-lor",
        data: "method=get-learning-objects&search_value=" + search_value  + "&offset=" + offset + "&object_type=" + jQuery("#object-type-search").val(),
        type: "GET",
        async: (clicked ? true : false)
    });

    jQuery.when(get_learning_objects_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            if (IN_ADMIN) {
                jQuery.each(jsonResponse.data.learning_objects, function (key, learning_object) {
                    build_learning_object_row(learning_object);
                });
            } else {
                build_learning_object_cards(jsonResponse.data.learning_objects);
            }


            var total_display_rows = parseInt(jQuery(".data-total").length);
            var total_rows = parseInt(jsonResponse.data.total_records);
            update_load_button_text (total_display_rows, total_rows);
            jQuery("#learning-objects-table").removeClass("hide");
            jQuery("#learning-objects-list").removeClass("hide");
            jQuery("#load-learning-objects").removeClass("hide");
        } else {
            var no_results_div = jQuery(document.createElement("div"));
            var no_results_p = jQuery(document.createElement("p"));

            no_results_p.html("No Learning Objects found matching the search criteria.");
            jQuery(no_results_div).append(no_results_p).attr({id: "learning-objects-no-results"});
            jQuery("#learning-object-msgs").append(no_results_div);
        }
        jQuery("#load-learning-objects").removeClass("loading");
        jQuery("#learning-objects-loading").addClass("hide");
    });
}

function update_load_button_text (total_display_rows, total_rows) {
    jQuery("#load-learning-objects").html("Showing " + total_display_rows + " of " + total_rows + " Learning Objects");
    if (total_display_rows === total_rows) {
        jQuery("#load-learning-objects").addClass("load-learning-objects-disabled");
    } else {
        jQuery("#load-learning-objects").removeClass("load-learning-objects-disabled");
    }
}

function build_learning_object_cards (learning_objects) {
    learning_objects = array_chunk(learning_objects, 2);
    for(var i=0; i < learning_objects.length; i++) {
        var card_wrapper = jQuery(document.createElement("div")).addClass("card-deck row-fluid");
        for(var j=0; j < learning_objects[i].length; j++) {
            if(jQuery('#grid-view').hasClass('active')) {
                card_wrapper = build_learning_object_grid(card_wrapper, learning_objects[i][j]);
            } else {
                card_wrapper = build_learning_object_list(card_wrapper, learning_objects[i][j]);
            }
        }
        jQuery("#learning-objects-list").append(card_wrapper);
    }

}

function build_learning_object_list(card_wrapper, learning_object) {

    var learning_object_div = jQuery(document.createElement("div")).addClass("list data-total");
    var img_a = jQuery(document.createElement("a")).attr({
        "href": learning_object.url,
        "target": "_blank"
    });
    var url_img = jQuery(document.createElement("img")).attr({"src": "?section=api-lor&method=get-images&image=" + learning_object.screenshot_filename}).addClass("list-image").addClass('hidden');
    var card_block = jQuery(document.createElement("div")).addClass("list-block");
    var card_head = jQuery(document.createElement("div")).addClass("list-head");
    var heading_a = jQuery(document.createElement("a")).attr({
        "href": learning_object.url,
        "target": "_blank"
    });
    var card_title = jQuery(document.createElement("h4")).addClass("list-title");
    if (learning_object.authors) {
        var card_author = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_author.html(learning_object.authors);
    }
    if (learning_object.primary_usage) {
        var card_primary_usage = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_primary_usage.html(learning_object.primary_usage);
    }
    if (learning_object.description) {
        var card_description = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_description.html(learning_object.description);
    }
    var card_data = jQuery(document.createElement("div")).addClass("list-data");
    var card_footer = jQuery(document.createElement("p")).addClass("card-footer");
    var icon_clock = jQuery(document.createElement("i")).addClass("fa fa-clock-o");

    heading_a.html(learning_object.title);

    card_title.append(heading_a);
    card_head.append(card_title);
    img_a.append(url_img);
    card_footer.append(icon_clock).append("&nbsp;Updated " + learning_object.updated_date );
    card_data.append(card_footer);
    card_block.append(card_author).append(card_primary_usage).append(card_description);
    learning_object_div.append(card_head).append(img_a).append(card_block).append(card_data);
    card_wrapper.append(learning_object_div);

    return card_wrapper;
}

function build_learning_object_grid(card_wrapper, learning_object) {

    var learning_object_div = jQuery(document.createElement("div")).addClass("card data-total");
    var img_a = jQuery(document.createElement("a")).attr({
        "href": learning_object.url,
        "target": "_blank"
    });
    var url_img = jQuery(document.createElement("img")).attr({"src": "?section=api-lor&method=get-images&image=" + learning_object.screenshot_filename}).addClass("card-img-top");
    var card_block = jQuery(document.createElement("div")).addClass("card-block");
    var card_head = jQuery(document.createElement("div")).addClass("card-head");
    var heading_a = jQuery(document.createElement("a")).attr({
        "href": learning_object.url,
        "target": "_blank"
    });
    var card_title = jQuery(document.createElement("h4")).addClass("card-title");
    if (learning_object.authors) {
        var card_author = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_author.html(learning_object.authors);
    }
    if (learning_object.primary_usage) {
        var card_primary_usage = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_primary_usage.html(learning_object.primary_usage);
    }
    if (learning_object.description) {
        var card_description = jQuery(document.createElement("p")).addClass("card-text card-subheading");
        card_description.html(learning_object.description);
    }
    var card_data = jQuery(document.createElement("div")).addClass("card-data");
    var card_footer = jQuery(document.createElement("p")).addClass("card-footer");
    var icon_clock = jQuery(document.createElement("i")).addClass("fa fa-clock-o");

    heading_a.html(learning_object.title);

    card_title.append(heading_a);
    card_head.append(card_title);
    img_a.append(url_img);
    card_footer.append(icon_clock).append("&nbsp;Updated " + learning_object.updated_date);
    card_data.append(card_footer);
    card_block.append(card_author).append(card_primary_usage).append(card_description);
    learning_object_div.append(card_head).append(img_a).append(card_block).append(card_data);
    card_wrapper.append(learning_object_div);

    return card_wrapper;
}

function build_learning_object_row (learning_object) {
    var learning_object_tr     = jQuery(document.createElement("tr")).addClass("data-total");
    var input_td        = jQuery(document.createElement("td"));
    var title_td        = jQuery(document.createElement("td"));
    var authors_td      = jQuery(document.createElement("td"));
    var date_td         = jQuery(document.createElement("td"));

    var input           = jQuery(document.createElement("input")).attr({type: "checkbox", name: "learning_objects[]", value: learning_object.learning_object_id});
    var title_a         = jQuery(document.createElement("a")).attr({"id": "learning_object_title_link_" + learning_object.learning_object_id, "href": ENTRADA_URL + "/admin/lor?section=edit&learning_object_id=" + learning_object.learning_object_id}).html(learning_object.title);
    var authors_a       = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/lor?section=edit&learning_object_id=" + learning_object.learning_object_id}).html(learning_object.authors ? learning_object.authors : "N/A");
    var date_a          = jQuery(document.createElement("a")).attr({"href": ENTRADA_URL + "/admin/lor?section=edit&learning_object_id=" + learning_object.learning_object_id}).html(learning_object.updated_date ? learning_object.updated_date : "N/A");

    input_td.append(input);
    title_td.append(title_a);
    authors_td.append(authors_a);
    date_td.append(date_a);
    learning_object_tr.append(input_td).append(title_td).append(authors_td).append(date_td);
    jQuery("#learning-objects-table tbody").append(learning_object_tr);
}

function build_selected_author_item (id, name, author_type) {
    build_selected_author_list();

    if (jQuery(".internal-author-" + id).length === 0) {
        var role_span               = jQuery(document.createElement("span")).addClass("pull-right selected-author-container");
        var role_label_span         = jQuery(document.createElement("span")).addClass("selected-author-label").html((author_type === "Internal" ? internal_author_label : external_author_label));
        var remove_author_span      = jQuery(document.createElement("span")).addClass("remove-selected-author").html("&times");
        var item                    = jQuery(document.createElement("li")).addClass("community internal-author-list-item internal-author-" + id).html(name).attr("data-id", id);
        var input                   = jQuery(document.createElement("input")).attr({id: "selected-internal-author-" + id, name: "selected_internal_authors[]", type: "hidden", value: (author_type === "Internal" ? "internal_" : "external_") + id}).addClass("selected-internal-author-control");

        role_span.append(role_label_span).append(remove_author_span);
        item.append(role_span);

        jQuery("#internal-authors-list").append(item);
        jQuery("#author-list-internal").removeClass("hide");
        jQuery("#internal-authors-list-container").append(input);
    }
}

function build_selected_author_list () {
    if (jQuery(".no-internal-authors-msg").length > 0) {
        jQuery(".no-internal-authors-msg").remove();
    }

    if (jQuery(".internal-authors-list").length === 0) {
        var author_ul = jQuery(document.createElement("ul")).attr({id: "internal-authors-list"}).addClass("internal-authors-list menu");
        jQuery("#internal-authors-list-container").append(author_ul);
    }
    if (jQuery(".no-internal-authors-msg").length > 0) {
        jQuery(".no-internal-authors-msg").remove();
    }

    if (jQuery(".internal-authors-list").length === 0) {
        var author_ul = jQuery(document.createElement("ul")).attr({id: "internal-authors-list"}).addClass("internal-authors-list menu");
        jQuery("#internal-authors-list-container").append(author_ul);
    }
}

function build_external_authors_button () {
    if (jQuery("#add-external-author-btn").length === 0) {
        var add_external_author_a = jQuery(document.createElement("a"));
        add_external_author_a.html("<i class=\"icon-plus-sign\"></i> Add External Author").attr({
            id: "add-external-author-btn",
            href: "#"
        });
        jQuery("#autocomplete").append(add_external_author_a);
    }
}

function reset_external_author_controls () {
    jQuery("#author-firstname").val("");
    jQuery("#author-lastname").val("");
    jQuery("#author-email").val("");
}

function add_external_author () {
    var firstname = jQuery("#author-firstname").val();
    var lastname  = jQuery("#author-lastname").val();
    var email     = jQuery("#author-email").val();

    var add_external_author_request = jQuery.ajax({
        url: "?section=api-lor",
        data: "method=add-external-author&firstname=" + firstname + "&lastname=" + lastname + "&email=" + email,
        type: "POST"
    });

    jQuery.when(add_external_author_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        if (jsonResponse.status === "success") {
            jQuery("#msgs").empty();
            jQuery("#external-authors-controls").addClass("hide");

            build_selected_author_item(jsonResponse.data.id, jsonResponse.data.firstname + " " + jsonResponse.data.lastname, "External");
            reset_external_author_controls();

            jQuery("#author-lists").removeClass("hide");

            //display_success(["Successfully added <strong>" + jsonResponse.data.firstname + " " + jsonResponse.data.lastname + "</strong> as an external author"], "#msgs", "prepend");
        } else {
            display_error(jsonResponse.data, "#msgs", "prepend");
        }
    });
}

function toggle_object_type () {
    var selected = jQuery("#object_type_select").val();

    jQuery("#link-group").hide();
    jQuery("#tincan-group").hide();
    jQuery("#scorm-group").hide();

    jQuery("#" + selected + "-group").show();
}