var folder_id;
jQuery(document).ready(function ($) {


    $(".qbf-selector").on("click", ".folder-selector", function() {
        folder_id = $(this).data("id");
        $(".folder-selector").removeClass("folder-selected");
        $(this).addClass("folder-selected");
    });

    $("button#confirm-folder-move").on("click", function(e) {
        e.preventDefault();

        $("#parent_folder_id").val(folder_id);
        $("#parent-folder-modal").modal("hide");

        var folder = $(".folder-selector.folder-selected");
        var image = $(folder).find("img.folder-color");
        var title = $(folder).find(".folder-title").text();
        var parent_folder  = $("#selected-parent-folder");

        $(parent_folder).find(".folder-title").text(title);
        $(parent_folder).find(".folder-image").data("image-id", folder_id);
        $(parent_folder).find(".folder-image").html(image);
    });

    $("button#cancel-folder-move").on("click", function(e) {
        e.preventDefault();
        $("#parent-folder-modal").modal("hide");
    });

    $(".qbf-selector").on("click", ".qbf-back-nav", function() {
        var folder_selected = $(this).data("folder-id");
        folderNavigator(folder_selected, "left");
    });

    $(".qbf-selector").on("click", ".sub-folder-selector", function() {
        var folder_selected = $(this).data("id");
        folderNavigator(folder_selected, "right");
    });

    $("#image-picker span.folder-image").on("click", function() {
        folderImageClicked($(this));
    });

    function folderImageClicked(clicked) {
        var image_selected = $(clicked).data("image-id");
        $("#image_id").val(image_selected);

        $("img.folder-select").removeClass("active");
        $(clicked).find("img.folder-select").addClass("active");
    }

    function folderNavigator(folder_selected, direction) {
        var parent_folder_id = jQuery("#parent_folder_id").val();
        if (ajax_in_progress === false) {
            ajax_in_progress = true;
            jQuery.ajax({
                url: FOLDER_API_URL,
                data: "method=get-sub-folder-selector&folder_id=" + folder_selected + "&parent_folder_id=" + parent_folder_id,
                type: "GET",
                success: function (data) {
                    var jsonAnswer      = JSON.parse(data);
                    var folder_count    = jsonAnswer.folder_count;
                    var current_folder  = jQuery(".qbf-folder.active");
                    var sub_folders     = document.createElement("span");
                    sub_folders.setAttribute("id", "qbf-folder-" + folder_selected);
                    sub_folders.setAttribute("class", "qbf-folder active");
                    if (direction === "left") {
                        jQuery(sub_folders).animate({
                            right: "250"
                        }, 0);
                    } else {
                        jQuery(sub_folders).animate({
                            left: "250"
                        }, 0);
                    }

                    if (jsonAnswer.status_folder == "success") {
                        var subfolder_html = jsonAnswer.subfolder_html;
                        jQuery(sub_folders).append(subfolder_html);
                        jQuery(".qbf-selector").append(sub_folders);
                        jQuery(current_folder).removeClass("active");
                        var new_folder = jQuery("#qbf-folder-" + folder_selected);

                        if (direction === "left") {
                            jQuery(current_folder).animate({
                                left: "250"
                            }, 350, function() {
                                jQuery(current_folder).remove();
                            });

                            jQuery(new_folder).animate({
                                right: "0"
                            }, 350);
                        } else {
                            jQuery(current_folder).animate({
                                right: "250"
                            }, 350, function() {
                                jQuery(current_folder).remove();
                            });

                            jQuery(new_folder).animate({
                                left: "5"
                            }, 350);
                        }
                    }

                    if (jsonAnswer.status_nav == "success") {
                        jQuery("#qbf-nav").html(jsonAnswer.nav_html);
                    }

                    if (jsonAnswer.status_title == "success") {
                        jQuery("#qbf-title").html(jsonAnswer.title_html);
                    }

                    ajax_in_progress = false;

                    var folder_selector_height = jQuery(".folder-selector").outerHeight();
                    var adjusted_height = folder_count * folder_selector_height + 110;

                    if (adjusted_height < 350) {
                        adjusted_height = 350;
                    }

                    jQuery(".qbf-selector").css("height", adjusted_height + "px");
                }
            });
        }
    }
});