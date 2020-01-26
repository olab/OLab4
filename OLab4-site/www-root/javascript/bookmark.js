jQuery(document).ready(function() {
    var shown = false;
    var editingBookmarks = false;
    var visibleBookmarks = 5; //Number of bookmarks visible by default

    jQuery("#bookmarks").on("click", "button#submit-bookmark", function() {
        var formData = jQuery(".bookmark-form").serialize();
        if (jQuery("input#bookmark-title").val().length == 0) {
            jQuery("input#bookmark-title").closest(".control-group").addClass("error");
        } else {
            submitBookmark(formData);
            removeBookmarkForm();
        }

        return false;
     });

     jQuery("#bookmarks").on("click", "#close-bookmark-form", function() {
        removeBookmarkForm();
        return false;
     });

     jQuery("#bookmarks").on("click", ".popover-cancel", function() {
        jQuery(this).closest(".popover").popover("hide");
        return false;
     });

     jQuery("#bookmarks").on("click", "#edit-bookmarks", function() {
        if (shown === true) {
            removeBookmarkForm();
        }

        if (editingBookmarks == false) {
            editBookmarks();
            return false;

        } else {
            doneEditing();
            return false;
        }
     });


    jQuery("#bookmarks").on("click", "#bookmark-page", function() {
        if (shown === false) {
            shown = true;

            var bookmarkUrl = jQuery(this).data("bookmark-url");
            var container = jQuery("<div id=\"bookmark-form-container\" />");

            jQuery.ajax({
                url: ENTRADA_URL + "/api/bookmark-dialog.api.php",
                data: "action=add&id=" + encodeURIComponent(bookmarkUrl),
                type: "POST",
                cache: false,
                success: function(data) {
                   container.html("<div class=\"cornerarrow\"></div>" + data);
                    jQuery("input#bookmark-title").val(jQuery(".breadcrumb li:last-child").text().replace("/", ""));
                   jQuery("input#bookmark-title").focus();
                }
            })
            .fail(function() {
                displayErrors("Could not load bookmark form");
            });

            container.appendTo(jQuery("#bookmarks-widget"));
            jQuery("#bookmark-form-container").animate({
                left: jQuery(this).parent().width() + 22
            }, 200);


        } else {
            removeBookmarkForm();
        }
        return false;

    });

    jQuery("#bookmarks").on("click", "#bookmarked-page", function() {
        if (shown === false) {
            shown = true;

            var container = jQuery("<div id=\"bookmark-form-container\" />");
            var bookmarkId = jQuery(this).data("bookmark");

            jQuery.ajax({
                url: ENTRADA_URL+"/api/bookmark-dialog.api.php",
                data: "action=remove&id=" + bookmarkId,
                type: "POST",
                cache: false,
                success: function(data) {
                    container.html("<div class=\"cornerarrow\"></div>" + data);

                }
            })
            .fail(function() {
                displayErrors("Could not remove bookmark");
            });

            container.appendTo(jQuery("#bookmarks-widget"));
            jQuery("#bookmark-form-container").animate({
                left: jQuery(this).parent().width() + 22
            }, 200);

            return false;

         } else {
            removeBookmarkForm();
            return false;
         }

    });


    jQuery("#bookmarks #bookmark_search_value").on("keyup", function() {
        searchBookmarks();
        return false;
    });

    /*
     * Makes Bookmark Title editable
     */
    jQuery("#bookmarks").on("click", ".bookmark-text.editable", function() {

        var currentBookmark = jQuery(this);
        var currentBookmarkText = currentBookmark.text();
        var currentBookmarkContainer = currentBookmark.closest(".bookmark-item");
        var bookmarkId = currentBookmarkContainer.data("bookmark-link-id");

        currentBookmark.toggleClass(function() {
            if (jQuery(this).hasClass("editable")) {
                jQuery(this).addClass("editing");
                return "editable";
            } else {
                jQuery(this).removeClass("editing");
                return "editable";
            }
        });

        currentBookmark.html("<div class=\"form-inline form-update-bookmark-title\"><input type=\"text\" name=\"bookmark-edit\" value=\"" + currentBookmarkText + "\" class=\"span9 input-small input-bookmark-edit\" id=\"" + bookmarkId + "\" /> <button class=\"btn btn-small\" id=\"btn-update-bookmark-title\"><i class=\"icon-ok\"></i></button></div>");

        return false;

    });
    /*
     * Updates Bookmark title in the database
     */
    jQuery("#bookmarks").on("click", "#btn-update-bookmark-title", function() {
        var updatedBookmarkInput = jQuery(this).siblings("input.input-bookmark-edit");
        var updatedBookmarkTitle = updatedBookmarkInput.val();
        var bookmarkID = updatedBookmarkInput.attr("id");

        if (updatedBookmarkTitle.length > 0) {

            updateBookmarkTitle(updatedBookmarkTitle, bookmarkID);

        } else {
            displayErrors("Bookmark title cannot be blank!");
        }

    });
    /*
     * Toggles the "Show more" section to display all bookmarks
     */
    jQuery("#bookmarks").on("click", "#btn-show-more-collapsible", function() {
        if (jQuery(this).hasClass("show-more")) {
            jQuery("#all-bookmarks").slideDown("fast");
            jQuery("#btn-show-more-collapsible").text("Show less").removeClass("show-more").addClass("show-less");
        } else {
            jQuery("#all-bookmarks").slideUp("fast");
            jQuery("#btn-show-more-collapsible").text("Show more").removeClass("show-less").addClass("show-more");
        }
        //jQuery( "#all-bookmarks").collapse("toggle");
    });
    jQuery("#all-bookmarks").on("show", function() {
        jQuery("#btn-show-more-collapsible").text("Show less").removeClass("show-more").addClass("show-less");
    });
    jQuery("#all-bookmarks").on("hide", function() {
        jQuery("#btn-show-more-collapsible").text("Show more").removeClass("show-less").addClass("show-more");
    });

    function editBookmarks () {
        editingBookmarks = true;

        clearErrors();

        jQuery(".bookmark-link").replaceWith(function() {
            return jQuery("<div class=\"bookmark-link\">" + jQuery(this).html() + "</div>");
        });
        jQuery("#edit-bookmarks").html("<i class=\"fa fa-check icon-white\"></i> Done").addClass("btn-success");
        jQuery(".bookmark-column").removeClass("span12").addClass("span10");
        jQuery("#bookmark-list").addClass("editing");
        jQuery("#bookmark-list-container").addClass("sortable-bookmarks");
        jQuery(".delete-column, .move-column").show();
        jQuery(".bookmark-text").addClass("editable");

        if (jQuery("#all-bookmarks").length) {
            jQuery("#all-bookmarks").slideDown("fast").children().unwrap();
            jQuery("#btn-show-more-collapsible").hide();
        }

        jQuery(".sortable-bookmarks").sortable({
            update : function () {
                var bookmarkOrder = jQuery(this).sortable("serialize");

                jQuery.ajax({
                    url: ENTRADA_URL + "/api/bookmark.api.php",
                    data: "method=sort-bookmarks&" + bookmarkOrder,
                    type: "POST",
                    cache: false
                })
                .fail(function() {
                    displayErrors("Could not update the sort order");
                });
            }
         });

        jQuery("#bookmarks").on("click", "#delete-bookmark", function() {
            var currentBookmark = jQuery(this).closest(".bookmark-item");
            var bookmarkId = currentBookmark.data("bookmark-link-id");
            var formData = "method=remove-bookmark&bookmarkId=" + bookmarkId;

            submitBookmark(formData);
            currentBookmark.remove();

            return false;
        });
    }

    function doneEditing() {
        editingBookmarks = false;

        clearErrors();
        jQuery("#edit-bookmarks").html("<i class=\"fa fa-cog\" aria-hidden=\"true\"></i>").removeClass("btn-success");
        jQuery(".bookmark-text").removeClass("editable");
        getBookmarks();
        return false;
    }

    function submitBookmark (formData) {
        clearErrors();
        jQuery.ajax({
            url: ENTRADA_URL + "/api/bookmark.api.php",
            data: formData,
            dataType: "json",
            type: "POST",
            cache: false,
            success: function(data) {
                var jsonResponse = data;

                if (!jsonResponse.error) {
                    jQuery("#bookmark-modal").modal("hide");
                    updateBookmarkButton(jsonResponse.type, jsonResponse.bookmark_id);

                    if (editingBookmarks !== true) {
                        getBookmarks();
                    }

                } else {
                    displayErrors(jsonResponse.error[0]);
                }
                return false;
            }
        })
        .fail(function() {
            displayErrors("Could not processs your request");
        });
    }

    function searchBookmarks() {
        if (jQuery("#bookmark_search_value").val() != "" && jQuery("#bookmark_search_value").val().length > 1) {
            setTimeout(function(){
                getBookmarks(jQuery("#bookmark_search_value").val());
            }, 500)
        } else {
            getBookmarks();
        }
    }

    function getBookmarks (search_value) {
        var currentUrl = jQuery(".bookmark-btn").data("bookmark-url");

        jQuery.ajax({
            url: ENTRADA_URL+"/api/bookmark.api.php",
            data: "method=get-bookmarks&pageUri=" + encodeURIComponent(currentUrl) + (search_value ? "&search_value=" + search_value : ""),
            dataType: "json",
            type: "POST",
            cache: false,
            success: function(data) {
                var jsonResponse = data;

                if (!jsonResponse.error) {

                    var bookmarkList = jQuery("#bookmark-list-container");
                    var bookmarkListSize = jsonResponse.length;
                    var bookmarkHtml = "";

                    bookmarkList.empty();

                    if (jsonResponse.empty) {
                        bookmarkHtml += "<div class=\"alert\" id=\"bookmarks-empty\">" + jsonResponse.empty[0] + "</div>";

                        if (jQuery("#edit-bookmarks").length) {
                            jQuery("#edit-bookmarks").remove();
                        }
                    } else {

                        //Check to make sure the edit button is visible
                        if (!jQuery("#edit-bookmarks").length) {
                            jQuery("#bookmark-controls").append("<button class=\"btn btn-small\" id=\"edit-bookmarks\"><i class=\"fa fa-cog\" aria-hidden=\"true\"></i></button>");
                        }

                        for ($i = 0; $i < bookmarkListSize; ++$i) {
                            if ($i === visibleBookmarks) {
                                bookmarkHtml += "<div id=\"all-bookmarks\" style=\"display: none;\">";
                            }
                            if ($i > visibleBookmarks) {
                                bookmarkHtml += "<div class=\"row-fluid bookmark-item hidden-bookmark\" id=\"bookmark_" + jsonResponse[$i].bookmark_id + "\" data-bookmark-link-id=\"" + jsonResponse[$i].bookmark_id + "\">";
                            } else {
                                bookmarkHtml += "<div class=\"row-fluid bookmark-item\" id=\"bookmark_" + jsonResponse[$i].bookmark_id + "\" data-bookmark-link-id=\"" + jsonResponse[$i].bookmark_id + "\">";
                            }

                            bookmarkHtml += "<div class=\"span1 delete-column\" style=\"display: none;\"><i class=\"fa fa-trash\" id=\"delete-bookmark\"></i></div>";
                            bookmarkHtml += "<div class=\"span12 bookmark-column\"><a href=\"" + jsonResponse[$i].url + "\" class=\"bookmark-link\"><span class=\"bookmark-text\">";
                            bookmarkHtml += (jsonResponse[$i].current == 1) ? "<strong>" + jsonResponse[$i].title + "</strong>" : jsonResponse[$i].title;
                            bookmarkHtml += "</span></a></div>";
                            bookmarkHtml += "<div class=\"span1 move-column muted\" style=\"display: none;\"><i class=\"fa fa-arrows\" id=\"move-bookmark\"></i></div>";
                            bookmarkHtml += "</div>"; //end bookmark-item
                            if ($i >= visibleBookmarks && $i+1 === bookmarkListSize) {
                                bookmarkHtml += "</div>";
                            }
                        }

                        if (bookmarkListSize > visibleBookmarks) {
                            jQuery("#btn-show-more-collapsible").replaceWith("<a class=\"btn btn-link btn-block show-more\" id=\"btn-show-more-collapsible\">Show more</a>");
                        }
                    }

                    bookmarkList.append(bookmarkHtml);

                }
            }
        })
        .fail(function() {
            displayErrors("Could not load your bookmark(s) at this time.");
        });
    }

    function updateBookmarkButton(action, bookmarkID) {
        if (action === "add") {
            if (!bookmarkID) {
                displayErrors("An error has occurred. Please refresh the page.");
                return false;
            } else {
                jQuery(".bookmark-btn").addClass("active").data("bookmark", bookmarkID).attr("id","bookmarked-page").children("#bookmark-text").text("Bookmarked");
                return false;
            }
        } else if (action === "remove") {
            var bookmarkButtonId = parseInt(jQuery(".bookmark-btn").data("bookmark"));
            var bookmarkID = parseInt(bookmarkID);

            if (bookmarkID === bookmarkButtonId) {
                jQuery(".bookmark-btn").removeClass("active").removeData("bookmark").attr("id","bookmark-page").children("#bookmark-text").text("Add Bookmark");
            }
            return false;
        } else {
            displayErrors("Action not supported");
            return false;
        }
    }

    function updateBookmarkTitle(updatedTitle, bookmarkID) {
        var currentBookmark = jQuery("#bookmark-list-container").find("input#" + bookmarkID).closest(".bookmark-text");
        var updatedBookmarkForm = currentBookmark.closest(".form-update-bookmark-title");

        jQuery.ajax({
            url: ENTRADA_URL+"/api/bookmark.api.php",
            data: "method=edit-bookmark&bookmarkId=" + bookmarkID + "&updatedTitle=" + encodeURIComponent(updatedTitle),
            dataType: "json",
            async: "false",
            type: "POST",
            cache: false,
            success: function(data) {
                var jsonResponse = data;

                if (jsonResponse.hasOwnProperty("error")) {

                } else {
                    updatedBookmarkForm.remove();
                    currentBookmark.text(updatedTitle);

                    currentBookmark.toggleClass(function() {
                        if (jQuery(this).hasClass("editable")) {
                            jQuery(this).addClass("editing");
                            return "editable";
                        } else {
                            jQuery(this).removeClass("editing");
                            return "editable";
                        }
                    });

                }
            }
        })
        .fail(function() {
            displayErrors("Could not update bookmark title");
        });
        return true;
    }

    function removeBookmarkForm() {
        jQuery("#bookmark-form-container").slideUp(function() {
            jQuery(".bookmark-form")[0].reset();
            jQuery(this).remove();
        });
        shown = false;

        return false;
    }

    function displayErrors(errorMessage) {
        var errorMessageHtml = "<div class=\"alert alert-error\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Error!</strong> " + errorMessage + "</div>";

        jQuery("#bookmark-controls").append(errorMessageHtml);
    }

    function clearErrors() {
        jQuery("#bookmarks").find(".alert-error").remove();
    }
});