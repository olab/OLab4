// This function is called from the rotation scheduling interface, as well as the learner schedule overview (using FullCalendar).
function populateModal(proxy_id, draft_id, block_order, block_type_id) {
    jQuery("#book-slot input[name=proxy_id]").val(proxy_id);
    jQuery("#book-slot input[name=block_order]").val(block_order);
    jQuery("#book-slot input[name=block_type_id]").val(block_type_id);
    jQuery("#book-slot-error").hide();
    jQuery("#book-slot-no-results").hide();

    var settings = jQuery("#choose-rotations-btn").data("settings");
    settings.filters["rotation"].api_params.proxy_id = proxy_id;
    settings.filters["rotation"].api_params.draft_id = draft_id;
    settings.filters["rotation"].api_params.block_order = block_order;
    settings.filters["rotation"].api_params.block_type_id = block_type_id;

    jQuery.ajax({
        url: API_URL,
        data: {
            "method": "get-slot-blocks",
            "draft_id": draft_id,
            "block_type_id": block_type_id,
            "block_order": block_order,
            "proxy_id": proxy_id
        },
        type: "GET",
        success: function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {
                if (jsonResponse.data.length > 0) {
                    jQuery("#rotation-dates").empty().append(jsonResponse.data[0]["block_type_name"] + ": " + jsonResponse.data[0]["rotation_start_date"] + " - " + jsonResponse.data[0]["rotation_end_date"]);

                    if (jQuery("#rotation-list-container").length == 0) {
                        var list_container = jQuery(document.createElement("div")).attr({id: "rotation-list-container"});
                        var ul_item = jQuery(document.createElement("ul")).attr({id: "rotation-list"});
                        jQuery("#rotation-container-location").append(list_container.append(ul_item));
                    }

                    buildHiddenInputs(jsonResponse.data);
                    jQuery("#repeater_input").trigger("click");
                } else {
                    jQuery("#book-slot-no-results").show();
                }
            } else {
                jQuery("#book-slot-error").show();
                jQuery("#book-slot-error p").empty().append(jsonResponse.msg);
            }
        }
    });
}

function buildHiddenInputs(slot_block) {
    jQuery("#rotation_list_container").remove();
    jQuery(slot_block).each(function(j, schedule) {
        if(schedule.occupied) {
            var hidden_input = jQuery(document.createElement("input")).attr({
                "type"        : "hidden",
                "name"        : "rotation[]",
                "value"       : schedule.block_id,
                "id"          : "rotation_" + schedule.block_id,
                "data-id"     : schedule.block_id,
                "data-label"  : schedule.title,
                "data-filter" : "rotation",
                "data-parent" : schedule.target_parent
            }).addClass("search-target-control rotation_search_target_control");

            var original_parent_list = jQuery(document.createElement("input")).attr({
                "name"        : "original_parent_list",
                "type"        : "hidden",
                "data-value"  : schedule.block_id
            });

            jQuery("#rotation-form").append(hidden_input);
            jQuery("#rotation-form").append(original_parent_list);
            jQuery("#book-slot-delete").show();
        }
    });

    var previous_settings = jQuery("#choose-rotations-btn").data("settings");
    previous_settings.build_list();
}

jQuery(function($) {
    
    $('a[data-toggle="tab"]').on('shown', function (e) {
        $.ajax({
            url: API_URL,
            data: {
                "method": "save-preference",
                "section": SECTION,
                "pref_name": "active-tab",
                "pref_val": $(e.target).attr("href")
            },
            type: "POST"
        });
    });

    $(".learner-slot, .remove-learner-cell").hover(function(e) {
        if (!$(this).hasClass("currently-booked") && !$(this).hasClass("unavailable")) {
            $(this).children("span.background-block-number").addClass("hide");
            // var i = $(document.createElement("i")).addClass("icon-plus-sign");
            // $(this).append(i);
        }
    }, function(e) {
        $(this).children("i").remove();
        $(this).children("span.background-block-number").removeClass("hide");
    });

    $("#learners").on("click", ".learner-slot", function(e) {
        
        active_cell = $(this);
        var name             = $(this).data("name");
        var proxy_id         = $(this).data("proxy-id");
        var number_of_blocks = $(this).data("number-of-blocks");
        var draft_end_date   = $(this).data("draft-end-date");
        var block_order      = $(this).data("block-order");
        var block_type_id    = $(this).data("block-type-id");
        var max              = number_of_blocks - block_order + 1;
        var offset           = 52 / number_of_blocks;

        $("#book-slot input[name=code]").each(function(i, v) {
            if ($(v).val() == code) {
                $(v).attr("checked", "checked");
            }
        });

        $("#repeater_input").attr("max" , max);

        $("#repeater_input").on("click keyup", function (e) {

            $("#book-slot-error p").empty();
            $("#book-slot-error").hide();

            var end_date = "";
            var repeater_value = $("#repeater_input").val();
            $("#calculated-end-date").empty();
            $("#book-slot-warning").hide();

            if (repeater_value == max) {
                end_date = new Date(draft_end_date);
                end_date.setDate(end_date.getDate() + 1);
                $("#calculated-end-date").html($.datepicker.formatDate("yy-mm-dd", end_date));
            } else if (repeater_value >= 1 && repeater_value < max) {
                end_date = new Date($("#rotation-dates").html().split(" ")[4]);
                end_date.setDate(end_date.getDate() + 7 * offset * (repeater_value - 1) + 1);
                $("#calculated-end-date").html($.datepicker.formatDate("yy-mm-dd", end_date));
            }

            if(repeater_value > 1 && repeater_value <= max) {
                $("#book-slot-warning").show();
            }
        });

        populateModal(proxy_id, draft_id, block_order, block_type_id);
        
        $(".modal-header h2 span").remove();
        $(".modal-header h2").append("<span>" + block_order + " for " + name + "</span>");
        $("#book-slot").modal("show");
    });

    $('#book-slot').on('shown', function(){
        $('body').css('overflow', 'hidden');
    }).on('hidden', function(){
        $('body').css('overflow', 'auto');
    });

    $(".remove-learner-cell").on("click", function(e) {
        var remove_link = $(document.createElement("a")).addClass("pull-left btn btn-danger remove-off-service-learner").html(translation.remove_learner_from_slot).attr("href", "#").data({
            "saudience-id"  : $(this).data("saudience-id"),
            "proxy-id"      : $(this).parent().data("proxy-id")
        });
        $("#book-slot .modal-footer").append(remove_link);
        $("#book-slot").modal("show");
    });

    $("#book-slot").on("hidden", function(e) {
        $("#book-slot input[type=hidden][id*=rotation]").each(function(i, hidden_input) {
            $(hidden_input).remove();
        });
        $("#book-slot input[name=original_parent_list][type=hidden]").each(function(i, hidden_input) {
            $(hidden_input).remove();
        });

        $("#repeater_input").val(1);
        $("#off-service").empty();
        $("#on-service").empty();
        $(".remove-off-service-learner").remove();
        $("#rotation-list-container").remove();
        $("#book-slot-error p").empty();
        $("#book-slot-error").hide();
        $("#book-slot-warning").hide();
        $("#book-slot-delete").hide();
    });

    $("#book-slot input[id=book-slot-submit], input[id=book-slot-delete]").on("click", function(e) {
        e.preventDefault();

        var repeat_count = 1;
        var max_repeat_count = 2;

        if ($("#repeater_input").length) {
            repeat_count = parseInt($("#repeater_input").val());
            max_repeat_count = parseInt($("#repeater_input").attr("max"));
        }

        if (isNaN(repeat_count) || repeat_count < 1 || repeat_count > max_repeat_count) {
            $("#book-slot-error p").empty().append("The 'Block Span' must be less than " + (max_repeat_count + 1) + ".");
            $("#book-slot-error").show();
        } else if ($("#book-slot input[type=hidden][id*=rotation]").length < 1 && $("#book-slot input[name=original_parent_list][type=hidden]").length < 1) {
            $("#book-slot-error p").empty().append("Please select one or more rotations.");
            $("#book-slot-error").show();
        } else {
            var block_ids = new Array(),
                slot_type_ids = new Array(),
                original_schedule_ids = new Array(),
                block_order = $("#book-slot input[name=block_order]").val(),
                block_type_id = $("#book-slot input[name=block_type_id]").val(),
                proxy_id = $("#book-slot input[name=proxy_id]").val(),
                course_id = $("#book-slot input[name=course_id]").val(),
                draft_id = $("#book-slot input[name=draft_id]").val()

            $("#book-slot-submit").attr("disabled", "disabled");
            $("#book-slot-delete").attr("disabled", "disabled");

            if (this.id == "book-slot-submit") {
                $("#book-slot input[name*=rotation][type=hidden]").each(function (i, v) {
                    block_ids.push($(v).attr("value"));
                    slot_type_ids.push(1);
                });
            }

            $("#book-slot input[name=original_parent_list][type=hidden]").each(function (i, v) {
                original_schedule_ids.push($(v).attr("data-value"));
            });

            $.ajax({
                url: API_URL,
                data: {
                    "method"                : "edit-slot-member",
                    "proxy_id"              : proxy_id,
                    "course_id"             : course_id,
                    "draft_id"              : draft_id,
                    "block_order"           : block_order,
                    "block_type_id"         : block_type_id,
                    "block_ids"             : block_ids,
                    "slot_type_ids"         : slot_type_ids,
                    "repeat_count"          : repeat_count,
                    "max_repeat_count"      : max_repeat_count,
                    "original_schedule_ids" : original_schedule_ids
                },
                type: "POST",
                success: function (data) {

                    $("#book-slot-submit").removeAttr("disabled");
                    $("#book-slot-delete").removeAttr("disabled");

                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        if (typeof active_cell != "undefined") {

                            var data_block_order = parseInt(active_cell.attr("data-block-order"));

                            for (var i = 0; i < repeat_count; i++) {

                                var next_cell = active_cell.closest(".table-row").find($("div[data-block-order=" + (data_block_order + i) + "][data-block-type-id=" + block_type_id + "][data-proxy-id=" + proxy_id + "]"));

                                if (jsonResponse.data[i] != "") {
                                    next_cell.text(jsonResponse.data[i]).addClass("currently-booked");
                                    next_cell.append($(document.createElement("span")).addClass("fa fa-pencil"));
                                } else {
                                    next_cell.text("").removeClass("currently-booked");
                                    next_cell.append($(document.createElement("span")).addClass("fa fa-plus-circle"));
                                    if (parseInt($(next_cell).attr("data-block-type-id")) == 3) {
                                        next_cell.append($(document.createElement("span")).addClass("background-block-number").text(data_block_order + i));
                                    }
                                }
                            }

                            active_cell = "";
                        }
                        $("#book-slot").modal("hide");
                    }
                }
            });
        }
        e.preventDefault();
    });

    $("#book-slot").on("click", ".remove-off-service-learner", function(e) {
        remove_btn = $(this);
        $.ajax({
            url: API_URL,
            data: {
                "method": "remove-off-service-learner",
                "saudience_id": remove_btn.data("saudience-id")
            },
            type: "POST",
            success: function(data) {
                $("tr[data-proxy-id="+remove_btn.data("proxy-id")+"] td[data-saudience-id="+remove_btn.data("saudience-id")+"]").empty();

                var row = $("tr[data-proxy-id="+remove_btn.data("proxy-id")+"]");
                var can_remove = true;
                $.each(row.children().not(".learner-name"), function(i, v) {
                    if ($(v).html().length >= 1) {
                        can_remove = false;
                    }
                });
                if (can_remove == true) {
                    row.remove();
                    if (row.parent().children().length <= 0) {
                        $("#off-service-learners").remove();
                        $("h4[title=\"" + translation.off_service_learners + "\"]").remove();
                    }
                }
                $("#book-slot").modal("hide");
            }
        });
    });

    $(".delete-rotation").on("click", function(e) {
        var url = ENTRADA_URL + "/admin/" + MODULE + "?section=delete&draft_id=" + draft_id + "&step=1";
        $("#my-drafts").attr("action", url).submit();
        e.preventDefault();
    });

    $(".publish-draft").on("click", function(e) {
        var method = $(document.createElement("input")).attr({"type" : "hidden", "name" : "publish", "value" : "publish"});
        var url = ENTRADA_URL + "/admin/" + MODULE + "?section=drafts&step=2&draft_id=" + draft_id;
        $("#my-drafts").attr("action", url).append(method).submit();
        e.preventDefault();
    });

    $("#draft-authors").audienceSelector({
        "filter": "#contact-type",
        "target": "#slot-occupants",
        "content_type": "individual",
        "content_target": "slot-id",
        "api_url": ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
        "delete_attr": "data-proxy-id",
        "add_audience": false,
        "min_chars": 3,
        "api_params": {
            "schedule_slot_id": $("#slot-id")
        },
        "get_method" : "get-draft-authors",
        "handle_result_click" : function(v) {
            if (v.fullname != "No results found") {
                $(".draft_author_container").append("<input value=\""+v.id+"\" type=\"hidden\" name=\"draft_author_proxy_id[]\" />");
                $(".unstyled").append("<li><i id=\""+ v.id+"\" class=\"icon-remove-circle remove-draft-author\"></i> "+v.fullname+"</a></li>");
            }
        }
    });

    $(".unstyled").on("click", ".remove-draft-author", function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
            url: ENTRADA_URL + "/admin/" + MODULE + "?section=api-schedule",
            data: {
                "method"    : "remove-draft-author",
                "draft_id"  : draft_id,
                "proxy_id"  : $(this).attr('id')
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status != "success") {
                    $("input[name=\"draft_author_proxy_id[]\"][value=\"" + id + "\"]").remove();
                }
            },
            complete: function () {
                $("#" + id + "").parent("li").remove();
                $("#" + id + "").remove();
            }
        });
    });

    $("#copy-draft-rotations").on("hide.bs.modal", function(e) {
        $("#copy-draft-rotations").addClass("hide");
        location.reload();
    });

    $("#copy-draft-rotations-confirm").on("click", function(e) {
        e.preventDefault();
        $("#copy-draft-rotations-confirm").prop("disabled", true);
        var url = $("#copy-draft-rotations-form").attr("action");
        $.ajax({
            url: url,
            data: {
                "method": "copy-draft-rotations",
                "draft_id": $("#draft_id").val(),
                "copy_draft_id": $("#copy-rotations-draft-selector").val()
            },
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    display_success(jsonResponse.data, "#copy-draft-rotations-success");
                    $("#copy-draft-rotations-success").removeClass("hide");
                } else {
                    $(jsonResponse.data).each(function (i, v) {
                        display_error(v, "#copy-draft-rotations-error");
                    });
                    $("#copy-draft-rotations-error").removeClass("hide");
                }
                $("#copy-draft-rotations-confirm").prop("disabled", false);
            }
        });
    });

    $("#choose-rotations-btn").on("change", function () {
        updateRotationPlaceHolder();
    });

    $("#rotation-form").on("click", ".remove-target-toggle", function () {
        updateRotationPlaceHolder();
    });

    $("#rotation-form").on("click", ".remove-list-item", function () {
        updateRotationPlaceHolder();
    });

    function updateRotationPlaceHolder() {
        $("#book-slot-error p").empty();
        $("#book-slot-error").hide();

        if ($("#rotation-list").children().length == 0) {
            if ($("#rotation-list-container").length == 0) {
                var list_container = $(document.createElement("div")).attr({id: "rotation-list-container"});
                var ul_item = $(document.createElement("ul")).attr({id: "rotation-list"});
                $("#rotation-container-location").append(list_container.append(ul_item));
            }
            $("#rotation-list").append($(document.createElement("li")).addClass("selected-list-item rotation-list-item").attr({id : "rotation-place-holder"}).html("No rotations selected"));
        } else {
            $("#rotation-place-holder").remove();
        }
    }

    $(".current-block-button").click(function(e) {
        e.preventDefault();

        $("div.learner-table-wrapper .table-content").each(function () {
            // Scroll the overflowed table div to the top and left of the current block within that div.
            var current_block_cell = $(this).find("div.current-block-header");

            if (current_block_cell.length > 0) {
                $(this).animate({
                    scrollTop: current_block_cell.offset().top - $(this).offset().top + $(this).scrollTop(),
                    scrollLeft: current_block_cell.offset().left - $(this).offset().left + $(this).scrollLeft()
                });
            }

            /* Instant, non-animated alternative.
             parent_div.scrollTop(
             current_block_cell.offset().top - parent_div.offset().top + parent_div.scrollTop()
             );
             parent_div.scrollLeft(
             current_block_cell.offset().left - parent_div.offset().left + parent_div.scrollLeft()
             );
             */
        });
    });

    $(".scroll-left").on({
        "click": function (e) {
            e.preventDefault();
            /* Individual click alternative to button hold movement for scrolling.
             $("div.learner-table-wrapper").each(function () {
             // Scroll the overflowed table div.
             var left_position = $(this).scrollLeft();

             $(this).animate({
             scrollLeft: left_position - 304
             }, 100);
             });
             */
        },
        "mousedown touchstart": function (e) {
            e.preventDefault();
            $("div.learner-table-wrapper .table-content").each(function () {
                // Scroll the overflowed table div.
                $(this).animate({
                    scrollLeft: -$("div.learner-table-wrapper .table-content").width(),
                    queue: false
                }, 1800);
            });
        },
        "mouseup touchend": function () {
            $("div.learner-table-wrapper .table-content").each(function () {
                $(this).stop(true);
            });
        }
    });

    $(".scroll-right").on({
       "click": function (e) {
           e.preventDefault();
           /* Individual click alternative to button hold movement for scrolling.
            $("div.learner-table-wrapper").each(function () {
            // Scroll the overflowed table div.
            var left_position = $(this).scrollLeft();

            $(this).animate({
            scrollLeft: left_position + 304
            }, 100);
            });
            */
       },
       "mousedown touchstart": function (e) {
           e.preventDefault();
           $("div.learner-table-wrapper .table-content").each(function () {
               // Scroll the overflowed table div.
               $(this).animate({
                   // The header row should have a width that is equal to the entire content of the table.
                   scrollLeft: $("div.table-content:first .header-row").width(),
                   queue: false
               }, 1800);
           });
       },
       "mouseup touchend": function () {
            $("div.learner-table-wrapper .table-content").each(function () {
                $(this).stop(true);
            });
        }
    });

    $("#export-btn").on("click", function(e) {
        if (!$("#export-btn").hasClass("disabled")) {
            $("#export-btn").addClass("disabled");
            var href = $("#export-btn").attr("href");
            var block_type = $("#block-type").val();
            //var learner_type = $("#learner-type").val();

            if (href.indexOf("block_type_id") > -1) {
                href = href.substring(0, href.indexOf("block_type_id"));
            }

            $("#export-btn").attr("href", href + "block_type_id=" + block_type + "&include_off_service=0");// + learner_type);
        } else {
            e.preventDefault();
        }
        $("#export-csv").modal("hide");
    });

    $("#export-csv").on("shown", function () {
        if ($("#export-btn").hasClass("disabled")) {
            $("#export-btn").removeClass("disabled");
        }
    });

    /**
     * This function sizes the learner table content to make room for the fixed column.
     */
    if ($(".learner-table-wrapper").length) {
        responsive_table_cap();

        $(window).on("resize", function () {
            responsive_table_cap();
        });

        $(".learners-tab-toggle a").on("click", function() {
            setTimeout(function() {
                responsive_table_cap();
            }, 100);
        });

        /**
         * Makes learner cell and block cells the same height
         */
        learner_cell_height();

        $(".learners-tab-toggle a").on("click", function() {
            setTimeout(function() {
                learner_cell_height();
            }, 100);
        });
    }

    function responsive_table_cap() {
        $(".learner-table-wrapper").each(function() {
            var table_wrapper_width = $(this).outerWidth(),
                left_cap_width = $(this).find(".left-end-cap").outerWidth(),
                table_content_width = table_wrapper_width - left_cap_width;

            $(this).find(".table-content").css({"width": table_content_width + "px", "margin-left": left_cap_width + "px"});
        });
    }

    if ($("#navigation-buttons-fixed-wrapper").length) {
        fixed_navigation_buttons();

        /**
         * This function makes the navigation buttons fixed when they reach the top of the window.
         */
        $(".learners-tab-toggle a, h2.collapsable").on("click", function() {
            setTimeout(function() {
                fixed_navigation_buttons();
            }, 100);
        });
    }

    function fixed_navigation_buttons() {
        var stickyNavButtons = $("#navigation-buttons-fixed-wrapper").offset().top;

        $(window).scroll(function() {
            if ($(window).scrollTop() > (stickyNavButtons - $("#draft-information").outerHeight())) {
                $("#navigation-buttons-fixed-wrapper").addClass("fixed");
            } else {
                $("#navigation-buttons-fixed-wrapper").removeClass("fixed");
            }
        });
    }

    function learner_cell_height() {
        $(".learner-table-wrapper .left-end-cap .learner-row .table-cell").each(function() {
            var block_cell_height = $(".learner-table-wrapper .table-content .block-row .table-cell").outerHeight();
            $(this).css({"height": block_cell_height + "px"});
        });
    }
});