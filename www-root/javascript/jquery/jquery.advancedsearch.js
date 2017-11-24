/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Advanced search jQuery extension
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

;(function($) {
    $.fn.advancedSearch = function(options) {

        /*
         *
         *  Advanced search settings
         *
         */

        var self = this;
        var interval;
        var settings = $.extend({
            api_url : "?section=api-items",
            api_params : {},
            build_selected_filters : true,
            child_field : "parent_id",
            close_button : true,
            close_after_select: false,
            control_class: "",
            current_filter: "",
            current_filter_label: "",
            default_option_label: "-- Select a Filter --",
            default_apply_filter_label: "Apply Filters",
            default_clear_search_label: "Clear All",
            default_close_search_label: "Close Search",
            default_select_label : "Select",
            select_filter_type_label: "Select a filter type to begin",
            filters: "",
            filter_component_label: "Items",
            height: 400,
            interval: 500,
            lazyload: false,
            lazyload_limit: 50,
            lazyload_offset: 0,
            list_container: "",
            list_selections: true,
            load_data_function: "",
            min_search_length: 3,
            no_results_text: "No results found",
            parent_control_value: "",
            parent_id: 0,
            reload_page_flag: false,
            remove_filters_method: "remove-all-filters",
            resource_url: "",
            results_parent: "",
            save_filters_method: "set-filter-preferences",
            search_input_label: "Begin typing to search",
            search_mode: true,
            selector_control_name: "",
            selector_mode: false,
            select_all_enabled: false,
            search_target_form_action: "",
            total_filters: 0,
            target_name: false,
            value: 0,
            width: 300,
            build_form: true,
            apply_filter_function: function(e) {
                e.preventDefault();
                saveFilterPreferences();
            },
            clear_filter_function: function(e) {
                e.preventDefault();
                closeFilterInterface();
            },
            close_filter_function: function(e) {
                e.preventDefault();
                resetFilterSelect();
                closeFilterInterface();
            },
            build_list: function () {
                if (settings.list_selections) {
                    $.each(settings.filters, function (filter, options) {
                        $.each($("input[name=\""+ filter +"[]\"]"), function (key, input) {
                            var current_filter = filter;
                            var current_filter_label = options.label;
                            var target_id = $(this).val();
                            var target_label = $(this).attr("data-label");

                            setCurrentFilter(current_filter, current_filter_label);
                            buildSelectedListItem(target_id, target_label);
                        });
                    });
                }
            }
        }, options);

        $(this).data("settings", settings);

        /*
         *
         *  Advanced search event listeners
         *
         */

        self.on("click", function (e) {
            e.preventDefault();
            toggleFilterMenu();
        });

        self.parent().on("click", ".filter-list-item", function () {
            applyFilter($(this).attr("data-source"), $(this).attr("data-label"));
        });

        $(document).mouseup(function (e) {
            if ((!$(".search-overlay").is(e.target) && $(".search-overlay").has(e.target).length === 0) && (!$(".filter-menu").is(e.target) && $(".filter-menu").has(e.target).length === 0)) {
                resetFilterSelect();
                closeFilterInterface();
            }
        });

        $(document).mouseup(function (e) {
            if ((!$(".btn-search-filter").is(e.target) && $(".btn-search-filter").has(e.target).length === 0) && (!$(".filter-menu").is(e.target) && $(".filter-menu").has(e.target).length === 0)) {
                closeFilterMenu();
            }
        });

        self.parent().on("keyup", ".search-input", function () {
            clearInterval(interval);
            interval = window.setInterval(function () {
                resetLazyloadOffset();
                getFilterData(true, true, true, false);
            }, settings.interval);
        });

        self.parent().on("click", ".close-widget", function () {
            closeFilterInterface();
        });

        self.parent().on("click", ".search-target-children-toggle", function (e) {
                var parent_id = $(this).closest("li").attr("data-id");
                settings.parent_id = parent_id;
                getFilterChildren(parent_id);
                e.preventDefault();
        });

        self.parent().on("click", ".filter-ellipsis-container", function () {
            var parent_id = $(this).attr("data-parent");
            settings.parent_id = parent_id;
            getFilterChildren(parent_id);
        });

        self.parent().on("change", ".search-target-input-control", function () {
            if (jQuery(this).hasClass("select-all-targets-input-control")) {
                var ul = $(this).parent().parent().parent().parent();
                var checked = $(this).is(":checked");
                var filter_type = $(this).attr("data-filter");
                $(ul).find(".search-target-input-control").each(function (key, element) {
                    var target_id = $(element).val();
                    var target_title = $(element).attr("data-label");
                    if (checked) {
                        if ($(element).parent().parent().parent().parent().parent().parent().find(".selected-targets-container ." + filter_type + "_" + target_id).length < 1) {
                            $(element).closest("li").addClass("search-target-selected");
                            buildSearchTargetControl(target_id, target_title);
                            if (!$(element).hasClass("select-all-targets-input-control")) {
                                buildSelectedListItem(target_id, target_title);
                            }
                        }
                    } else {
                        $(element).closest("li").removeClass("search-target-selected");
                        removeFilterTag(target_id, filter_type);
                        removeSearchTargetControl(target_id, filter_type);
                    }
                }).attr("checked", (checked ? "checked" : null));
                buildSelectedFilters();
            } else {
                var target_id = $(this).val();
                var target_title = $(this).attr("data-label");
                var filter_type = $(this).attr("data-filter");
                var mode = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");

                switch (mode) {
                    case "radio" :
                        if ($(this).is(":checked")) {
                            $(".search-filter-item").removeClass("search-target-selected");
                            $(this).closest("li").addClass("search-target-selected");
                            buildSearchTargetControl(target_id, target_title);
                            buildSelectedOption(target_id, filter_type);
                            closeFilterInterface();
                        }
                        break;
                    case "checkbox" :
                        if ($(this).is(":checked")) {
                            $(this).closest("li").addClass("search-target-selected");
                            buildSearchTargetControl(target_id, target_title);
                            buildSelectedFilters();
                            buildSelectedListItem(target_id, target_title);
                        } else {
                            $(this).closest("li").removeClass("search-target-selected");
                            removeFilterTag(target_id, filter_type);
                            removeSearchTargetControl(target_id, filter_type);
                        }
                        break;
                }
            }

            self.trigger("change", target_id);
            if (settings.close_after_select) {
                closeFilterInterface();
            }
        });

        self.parent().on("click", ".remove-target-toggle", function () {
            var target_id = $(this).attr("data-id");
            var filter_type = $(this).attr("data-filter");

            removeFilterTag(target_id, filter_type);
            removeSearchTargetControl(target_id, filter_type);

            $("#" + settings.current_filter +"_target_" + target_id).prop("checked", false).closest("li").removeClass("search-target-selected");
        });

        self.parent().on("click", ".search-clear-button", function (e) {
            e.preventDefault();
            removeAllFilters();
        });

        function buildFilterMenu () {
            var filter_container = $(document.createElement("div")).addClass("filter-menu");
            var filter_heading = $(document.createElement("h4")).addClass("filter-menu-heading").html(settings.select_filter_type_label);
            var filter_ul = $(document.createElement("ul")).addClass("filter-list");
            var filter_counter = 0;
            var filter_li;
            var modal = (settings.hasOwnProperty("modal") && settings.modal  ? true : false);

            if (modal) {
                filter_container.addClass("fixed-position");
            } else {
                filter_container.addClass("absolute-position");
            }

            $.each(settings.filters, function (filter_name, filter_options) {
                filter_li = $(document.createElement("li")).html(filter_options.label).addClass("filter-list-item").attr({"data-source": filter_name, "data-label": filter_options.label});
                filter_ul.append(filter_li);
                filter_counter++;
            });

            settings.total_filters = filter_counter;

            filter_container.append(filter_heading).append(filter_ul);
            self.after(filter_container);
            filter_container.offset({top: (self.offset().top + ($(".btn-search-filter").height() + 20)), left: self.offset().left});

            if (filter_counter == 1) {
                applyFilter(filter_li.attr("data-source"), filter_li.attr("data-label"));
            }
        }

        function toggleFilterMenu () {
            if ($(".filter-menu").length > 0) {
                closeFilterMenu();
            } else {
                buildFilterMenu();
            }
        }

        function closeFilterMenu () {
            if ($(".filter-menu").length > 0) {
                $(".filter-menu").remove();

                if ($(".btn-search-filter").hasClass("active")) {
                    $(".btn-search-filter").removeClass("active");
                }
            }
        }

        function buildSearchInterface () {
            var container                   = $(document.createElement("div")).addClass("search-overlay").css("width", settings.width + "px");
            var input_container             = $(document.createElement("div")).addClass("input-container");
            var filter_container            = $(document.createElement("div")).addClass("filter-container");
            var search_input                = $(document.createElement("input")).attr({type: "text", placeholder: "Begin typing to search..."}).addClass("search-input");
            var button_container            = $(document.createElement("div")).addClass("search-buttons");
            var selected_targets_container  = $(document.createElement("div")).addClass("selected-targets-container");
            var filter_type_h4              = $(document.createElement("h4")).html("Filtering " + settings.filter_component_label + " by " + settings.current_filter_label).addClass("search-label");
            var mode = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");
            var modal = (settings.hasOwnProperty("modal") && settings.modal  ? true : false);

            if (modal) {
                container.addClass("fixed-position");
            } else {
                container.addClass("absolute-position");
            }

            switch (mode) {
                case "radio" :
                    //var select_a = $(document.createElement("a")).attr({href: "#"}).html(settings.default_select_label).addClass("target-select-button");
                    //button_container.append(select_a);
                    break;
                case "checkbox" :
                    var apply_filter_a  = $(document.createElement("a")).attr({href: "#"}).html(settings.default_apply_filter_label).addClass("search-apply-button").on("click", function(e) {
                        settings.apply_filter_function(e);
                    });
                    var close_search_a  = $(document.createElement("a")).attr({href: "#"}).html(settings.default_close_search_label).addClass("search-close-button").on("click", function(e) {
                        settings.close_filter_function(e);
                    });
                    var clear_a         = $(document.createElement("a")).attr({href: "#"}).html(settings.default_clear_search_label).addClass("search-clear-button").on("click", function(e) {
                        settings.clear_filter_function(e);
                    });

                    if (typeof settings.parent_form === "undefined") {
                        button_container.append(apply_filter_a).append(clear_a).append(close_search_a);
                    }
                    break;
            }

            if (settings.close_button) {
                var close_filter_span = $(document.createElement("span")).html("&times;").addClass("close-widget");
                input_container.append(close_filter_span);
                search_input.addClass("search-input-with-close");
            }

            input_container.append(search_input);

            container.append(input_container).append(filter_type_h4).append(selected_targets_container).append(filter_container).append(button_container);
            self.after(container);
            container.offset({top: (self.offset().top + ($(".btn-search-filter").height() + 20)), left: self.offset().left});

            buildSelectedFilters();
            getFilterData(false, false, true, false);
        }

        function closeFilterInterface() {
            resetLazyloadOffset();
            if ($(".search-overlay").length > 0) {
                resetParentID();
                $(".search-overlay").remove();
            }
        }

        function resetFilterSelect() {
            $(".filter-select").val("0");
        }

        function setHeight (height) {
            self.settings.height = height;
        }

        function setWidth (width) {
            self.settings.width = width;
        }

        function setFilterContainerHeight() {
            var filter_height = getFilterHeight();
            $(".filter-container").css("height", filter_height + "px");
        }

        function getFilterHeight() {
            var overlay_height = $(".search-overlay").height();
            var ui_component_height = $(".input-container").outerHeight() + $(".search-label").outerHeight() + $(".search-buttons").outerHeight() + $(".secondary-search-label").outerHeight();
            return (overlay_height - ui_component_height);
        }

        function showLoadingMessage(show_loading_overlay) {
            if (show_loading_overlay) {
                if ($(".loading-overlay").length == 0) {
                    var loading_overlay             = $(document.createElement("div")).addClass("loading-overlay");
                    var loading_overlay_label       = $(document.createElement("span")).addClass("loading-overlay-label").html("Loading... ");
                    var loading_overlay_spinner_img = $(document.createElement("img")).attr({src: settings.resource_url + "/images/loading_small.gif"});

                    loading_overlay_label.append(loading_overlay_spinner_img);
                    loading_overlay.append(loading_overlay_label);
                    $(".filter-container").append(loading_overlay);
                }
            } else {
                var msg_div         = $(document.createElement("div")).addClass("search-loading-msg");
                var msg_p           = $(document.createElement("p")).html("Loading " + settings.current_filter_label + " data...");
                var spinner_img     = $(document.createElement("img")).attr({src: settings.resource_url + "/images/loading_small.gif"});

                msg_div.append(msg_p).append(spinner_img);
                $(".filter-container").append(msg_div);
            }
        }

        function removeLoadingMessage(show_loading_overlay) {
            if (show_loading_overlay) {
                if ($(".loading-overlay").length > 0) {
                    $(".loading-overlay").remove();
                }
            } else {
                if ($(".search-loading-msg").length > 0) {
                    $(".search-loading-msg").remove();
                }
            }
        }

        function removeSearchList() {
            if ($(".search-filter-list").length > 0) {
                $(".search-filter-list").remove();
            }
        }

        function resetFilterContainer() {
            $(".filter-container").children().remove(":not(.data-source-error)");
        }

        function getFilterData (reset_filter_container, reset_offset, show_error, show_loading_overlay) {
            if (reset_filter_container) {
                resetFilterContainer();
            }

            if (reset_offset) {
                resetLazyloadOffset();
            }

            var data_source = settings.filters[settings.current_filter].data_source;
            var parent_id = settings.parent_id;
            var extra_params = "";

            if (typeof settings.filters[settings.current_filter].api_params != "undefined") {
                $.each(settings.filters[settings.current_filter].api_params, function(param_name, param_value) {
                    if ($.isFunction(param_value)){
                        param_value = param_value();
                    }
                    extra_params += "&" + param_name + "=" + param_value;
                });
            }

            if (typeof settings.filters[settings.current_filter].api_params == "undefined") {
                settings.filters[settings.current_filter].api_params = [];
            }

            if (typeof data_source !== "undefined") {
                if (typeof data_source === "string") {
                    var search_value = $(".search-input").val();
                    var data = $.ajax({
                        url: settings.api_url,
                        data: "method=" + data_source + "&search_value=" + search_value + "&parent_id=" + parent_id + (settings.lazyload ? "&limit=" + settings.lazyload_limit + "&offset=" + settings.lazyload_offset : "") + extra_params,
                        type: 'GET',
                        error: function () {
                            removeLoadingMessage(show_loading_overlay);
                            showDataErrorMessage("An error occurred while attempting to fetch the data for self filter. Please try again later.");
                        },
                        beforeSend: function () {
                            //if ($(".search-input").val().length === 0) {
                            removeDataErrorMessage();
                            showLoadingMessage(show_loading_overlay);
                            //}
                        }
                    });

                    $.when(data).done(
                        function (data) {
                            removeLoadingMessage(show_loading_overlay);
                            var jsonResponse = $.parseJSON(data);
                            switch (jsonResponse.status) {
                                case "success" :
                                    updateLazyloadOffset();
                                    removeDataErrorMessage();
                                    displayFilterData(jsonResponse.data, jsonResponse.level_selectable);
                                    buildSelectedFilters();
                                    break;
                                case "error" :
                                    if (show_error) {
                                        showDataErrorMessage(jsonResponse.data);
                                    }
                                    break;
                            }
                        }
                    );
                } else if (typeof data_source === "object") {
                    displayFilterData(data_source);
                }
            } else {
                showDataErrorMessage("No data source supplied.");
            }
            clearInterval(interval);
        }

        function getFilterChildren (parent_id) {
            resetFilterContainer();
            resetLazyloadOffset();
            var data_source = settings.filters[settings.current_filter].secondary_data_source;
            if (typeof data_source !== "undefined") {
                if (typeof data_source === "string") {
                    var data = $.ajax(
                        {
                            url: settings.api_url,
                            data: "method=" + data_source + "&parent_id=" + parent_id + (settings.lazyload ? "&limit=" + settings.lazyload_limit + "&offset=" + settings.lazyload_offset : ""),
                            type: 'GET',
                            error: function () {
                                showDataErrorMessage("An error occurred while attempting to fetch the data for self filter. Please try again later.");
                            },
                            beforeSend: function () {
                            }
                        }
                    );

                    $.when(data).done(
                        function (data) {
                            var jsonResponse = JSON.parse(data);
                            switch (jsonResponse.status) {
                                case "success" :
                                    removeDataErrorMessage();
                                    if (jsonResponse.parent_name !== "0") {
                                        buildSecondarySearchLabel(jsonResponse.parent_id, jsonResponse.parent_name);
                                    } else {
                                        removeSecondaryLabel();
                                    }

                                    displayFilterData(jsonResponse.data, jsonResponse.level_selectable);
                                    break;
                                case "error" :
                                    showDataErrorMessage(jsonResponse.data);
                                    break;
                            }
                        }
                    );
                } else if (typeof data_source === "object") {
                    displayFilterData(data_source);
                }
            } else {
                showDataErrorMessage("No data source supplied.");
            }
        }

        function displayFilterData (data, level_selectable) {
            if ($(".search-filter-list").length == 0) {
                var targets_ul = $(document.createElement("ul")).addClass("search-filter-list");
            } else {
                var targets_ul = $(".search-filter-list");
            }

            if (typeof settings.select_all_enabled != "undefined" && settings.select_all_enabled && (level_selectable == null || (level_selectable != null && level_selectable))) {
                var target_li               = $(document.createElement("li")).addClass("search-filter-item").attr({"data-id": 0, "data-parent" : 0});
                var target_li_div           = $(document.createElement("div")).addClass("search-target-controls");
                var target_label_span       = $(document.createElement("span")).addClass("search-target-label");
                var target_label            = $(document.createElement("label")).html("Select All").addClass("search-target-label-text").attr({"for": settings.current_filter +"_target_" + 0});
                var target_input_span       = $(document.createElement("span")).addClass("search-target-input");
                var mode                    = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");
                var is_selectable           = true;
                var target_input;

                target_input = $(document.createElement("input")).attr({
                    type: "checkbox",
                    id: settings.current_filter + "_target_0",
                    "data-label": "Select All",
                    "data-filter": settings.current_filter
                }).val(0).addClass("search-target-input-control").addClass("select-all-targets-input-control");

                target_label_span.append(target_label);

                if (typeof target_input !== "undefined") {
                    target_input_span.append(target_input);
                }

                target_li_div.append(target_label_span).append(target_input_span);
                target_li.append(target_li_div);
                targets_ul.append(target_li);

                if ($("#" + settings.current_filter + "_0").length > 0) {
                    target_input.attr("checked", "checked");
                    target_li.addClass("search-target-selected");
                }
            }
            $.each(data, function (key, filter_target) {
                var hilited_search_value    = hiliteSearchValue(filter_target.target_label, $(".search-input").val());
                var target_li               = $(document.createElement("li")).addClass("search-filter-item").attr({"data-id": filter_target.target_id, "data-parent" : filter_target.target_parent});
                var target_li_div           = $(document.createElement("div")).addClass("search-target-controls");
                var target_label_span       = $(document.createElement("span")).addClass("search-target-label");
                var target_label            = $(document.createElement("label")).html(hilited_search_value).addClass("search-target-label-text").attr({"for": settings.current_filter +"_target_" + filter_target.target_id});
                var target_input_span       = $(document.createElement("span")).addClass("search-target-input");
                var mode                    = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");
                var is_selectable           = true;
                var target_input;

                if (typeof filter_target.target_title !== "undefined") {
                    target_label.attr("title", filter_target.target_title);
                }

                if (typeof level_selectable !== "undefined") {

                    if (filter_target.hasOwnProperty("level_selectable") && !parseInt(filter_target.level_selectable)) {
                        is_selectable = false;
                    }

                    if (is_selectable) {
                        switch (mode) {
                            case "radio" :
                                target_input = $(document.createElement("input")).attr({type: "radio", id: settings.current_filter +"_target_" + filter_target.target_id, name: "selected-target", "data-label": filter_target.target_label, "data-filter": settings.current_filter}).val(filter_target.target_id).addClass("search-target-input-control");
                                break;
                            case "checkbox" :
                                target_input = $(document.createElement("input")).attr({type: "checkbox", id: settings.current_filter +"_target_" + filter_target.target_id, "data-label": filter_target.target_label, "data-filter": settings.current_filter}).val(filter_target.target_id).addClass("search-target-input-control");
                                break;
                        }
                    }
                } else {

                    if (filter_target.hasOwnProperty("level_selectable") && !parseInt(filter_target.level_selectable)) {
                        is_selectable = false;
                    }

                    if (is_selectable) {
                        switch (mode) {
                            case "radio" :
                                target_input = $(document.createElement("input")).attr({type: "radio", id: settings.current_filter +"_target_" + filter_target.target_id, name: "selected-target", "data-label": filter_target.target_label, "data-filter": settings.current_filter}).val(filter_target.target_id).addClass("search-target-input-control");
                                break;
                            case "checkbox" :
                                target_input = $(document.createElement("input")).attr({type: "checkbox", id: settings.current_filter +"_target_" + filter_target.target_id, "data-label": filter_target.target_label, "data-filter": settings.current_filter}).val(filter_target.target_id).addClass("search-target-input-control");
                                break;
                        }
                    }
                }

                if (filter_target.hasOwnProperty("target_children")) {
                    if (filter_target.target_children > 0) {
                        var target_children_span = $(document.createElement("a")).attr({href: "#"}).html("+").addClass("search-target-children-toggle").attr({"data-label": filter_target.target_label});
                        target_li_div.append(target_children_span);
                    }
                }

                target_label_span.append(target_label);

                if (typeof target_input !== "undefined") {
                    target_input_span.append(target_input);
                }

                target_li_div.append(target_label_span).append(target_input_span);
                target_li.append(target_li_div);

                if ($(".search-input").val() == "") {
                    targets_ul.append(target_li);
                } else {
                    if ($(target_label).find(".search-hilite").length > 0) {
                        targets_ul.append(target_li);
                    }
                }

                if ($("#" + settings.current_filter + "_" + filter_target.target_id).length > 0) {
                    target_input.attr("checked", "checked");
                    target_li.addClass("search-target-selected");
                }
            });

            if ($(".search-filter-list").length == 0) {
                $(".filter-container").append(targets_ul);
            }

            if (settings.lazyload) {
                $(".filter-container").on("scroll", function() {
                    if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                        if ($.active == 0) {
                            getFilterData(false, false, false, true);
                        }
                    }
                });
            }
        }

        function buildSecondarySearchLabel (parent_id, parent_name) {
            removeSecondaryLabel();
            var heading_label                   = parent_name;
            var filter_ellipsis_span            = $(document.createElement("a")).addClass("filter-ellipsis-container").attr({"data-parent": parent_id});
            var secondary_heading_container     = $(document.createElement("div")).addClass("secondary-search-container");
            var secondary_heading_h4_container  = $(document.createElement("div")).addClass("secondary-search-label-container");
            var secondary_nav_container         = $(document.createElement("div")).addClass("secondary-search-nav-container");
            var secondary_heading               = $(document.createElement("h4")).html(heading_label).addClass("secondary-search-label");

            secondary_heading_h4_container.append(secondary_heading);
            secondary_nav_container.append(filter_ellipsis_span);
            secondary_heading_container.append(secondary_nav_container).append(secondary_heading_h4_container);
            $(".search-label").after(secondary_heading_container);
        }

        function showDataErrorMessage (msg) {
            if ($(".data-source-error").length === 0) {
                var error_p = $(document.createElement("p")).html(msg).addClass("data-source-error");
                $(".filter-container").append(error_p);
            }
        }

        function removeDataErrorMessage () {
            if ($(".data-source-error").length > 0) {
                $(".data-source-error").remove();
            }
        }

        function setCurrentFilter (current_filter, current_filter_label) {
            settings.current_filter = current_filter;
            settings.current_filter_label = current_filter_label;
        }

        function hiliteSearchValue (string, needle) {
            if ($(".search-input").val().length > 0) {
                return string.replace(new RegExp('(^|)(' + needle + ')(|$)','ig'), '$1<span class=\"search-hilite\">$2</span>$3');
            }
            return string;
        }

        function resetParentID () {
            settings.parent_id = 0;
        }

        function removeSecondaryLabel () {
            if ($(".secondary-search-container").length > 0) {
                $(".secondary-search-container").remove();
            }
        }

        function removeFilterTag(target_id, filter_type) {
            if ($("." + filter_type + "_" + target_id).length > 0) {
                $("." + filter_type + "_" + target_id).remove();
            }

            if ($("." + filter_type + "_target_item").length === 0) {
                if ($("#" + filter_type + "_targets_container").length > 0) {
                    $("#" + filter_type + "_targets_container").remove();
                }

                if ($("#" + filter_type + "_list_container").length > 0) {
                    $("#" + filter_type + "_list_container").remove();
                }
            }
        }

        function removeSelectedTargetsList() {
            if ($(".selected-targets-list").length > 0) {
                $(".selected-targets-list").remove();
                $(".selected-targets-heading").remove();
            }
        }

        function clearAllFilters() {
            if ($(".selected-targets-container").length > 0) {
                $(".selected-targets-container").remove();
            }

            if ($(".search-filter-item").hasClass("search-target-selected")) {
                $(".search-filter-item").removeClass("search-target-selected");
            }

            if ($(".search-target-input-control").is(":checked")) {
                $(".search-target-input-control").prop("checked", false);
            }

            if ($("#search-targets-form").length > 0) {
                $("#search-targets-form").remove();
            }

            closeFilterInterface();
        }

        function buildSelectedFilters() {
            if (settings.build_selected_filters) {
                if ($(".selected-targets-container").length > 0) {
                    $(".selected-targets-container").remove();
                }

                var selected_targets_container = $(document.createElement("div")).addClass("selected-targets-container");

                $.each(settings.filters, function (filter, options) {
                    if ($("." + filter + "_search_target_control").length > 0) {
                        var filter_container = $(document.createElement("div")).attr({id: filter + "_targets_container"}).addClass("targets-container");
                        var selected_targets_heading = $(document.createElement("h4")).addClass("selected-targets-heading").html("Selected " + options.label);
                        var selected_targets_ul = $(document.createElement("ul")).addClass(filter + "_selected_targets_list").addClass("selected-targets-list");
                        var mode = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");

                        switch (mode) {
                            case "radio" :
                                /*
                                 var target_id               = $("input[name=\""+ settings.selector_control_name +"\"]").val();
                                 var target_label            = $("input[name=\""+ settings.selector_control_name +"\"]").attr("data-label");
                                 var target_item             = $(document.createElement("li")).addClass("selected-target-item").addClass(filter + "_" + target_id).addClass(filter + "_target_item").html(target_label);
                                 var target_item_span        = $(document.createElement("span")).addClass("remove-target-toggle").attr({"data-id": target_id, "data-filter": filter}).html("&times;");

                                 target_item.append(target_item_span);
                                 selected_targets_ul.append(target_item);
                                 */
                                break;
                            case "checkbox" :
                                $.each($("input[name=\"" + filter + "[]\"]"), function (key, input) {
                                    if (!$(input).hasClass("select-all-targets-input-control")) {
                                        var target_id = $(this).val();
                                        var target_label = $(this).attr("data-label");
                                        var target_item = $(document.createElement("li")).addClass("selected-target-item").addClass(filter + "_" + target_id).addClass(filter + "_target_item").html("<span class=\"target-label\">" + target_label + "</span>");
                                        var target_item_span = $(document.createElement("span")).addClass("remove-target-toggle").attr({
                                            "data-id": target_id,
                                            "data-filter": filter
                                        }).html("&times;");

                                        target_item.append(target_item_span);
                                        selected_targets_ul.append(target_item);

                                        filter_container.append(selected_targets_heading).append(selected_targets_ul);
                                        selected_targets_container.append(filter_container);
                                        $(".input-container").after(selected_targets_container);
                                    }
                                });
                                break;
                        }
                    }
                });
            }
        }

        function buildSelectedFilter() {
            if ($(".selected-targets-container").length > 0) {
                $(".selected-targets-container").remove();
            }

            var selected_targets_container = $(document.createElement("div")).addClass("selected-targets-container");
        }

        function buildSearchTargetsForm() {
            var form = $(document.createElement("form")).attr({id: "search-targets-form", method: "post", action: settings.search_target_form_action});
            settings.results_parent.after(form);
        }

        function buildSearchTargetControl(target_id, target_title) {
            var mode = (settings.filters[settings.current_filter].hasOwnProperty("mode") ? settings.filters[settings.current_filter].mode : "checkbox");
            var search_target_control;

            switch (mode) {
                case "radio" :
                    if (settings.control_class != "") {
                        $("." + settings.control_class).remove();
                    }
                    search_target_control = $(document.createElement("input")).attr({type: "hidden", name: settings.filters[settings.current_filter].selector_control_name, value: target_id, id: settings.current_filter + "_" + target_id, "data-label": target_title}).addClass("search-target-control").addClass(settings.current_filter + "_search_target_control").addClass(settings.control_class);
                    settings.parent_form.append(search_target_control);
                    break;
                case "checkbox" :
                    if (typeof settings.parent_form === "undefined") {
                        if ($("#search-targets-form").length === 0) {
                            buildSearchTargetsForm();
                        }

                        if ($("#search-targets-form").length > 0 && $("#" + settings.current_filter + "_" + target_id).length === 0) {
                            search_target_control = $(document.createElement("input")).attr({type: "hidden", value: target_id, id: settings.current_filter + "_" + target_id, "data-label": target_title}).addClass("search-target-control").addClass(settings.current_filter + "_search_target_control");
                            if (target_id != 0 && target_title != "Select All") {
                                search_target_control.attr("name", (settings.target_name ? settings.target_name : settings.current_filter) + "[]");
                            }
                            $("#search-targets-form").append(search_target_control);
                        }
                    } else {
                        search_target_control = $(document.createElement("input")).attr({type: "hidden", value: target_id, id: settings.current_filter + "_" + target_id, "data-label": target_title}).addClass("search-target-control").addClass(settings.current_filter + "_search_target_control");
                        if (target_id != 0 && target_title != "Select All") {
                            search_target_control.attr("name", (settings.target_name ? settings.target_name : settings.current_filter) + "[]");
                        }
                        settings.parent_form.append(search_target_control);
                    }
                    break;
            }
        }

        function removeSearchTargetControl(target_id, filter_type) {
            if ($("#" + filter_type + "_" + target_id).length > 0) {
                $("#" + filter_type + "_" + target_id).remove();
            }

            if (!$("#search-targets-form").children().length > 0) {
                //$("#search-targets-form").remove();
            }
        }

        function loadExternalData() {
            var fn = window[settings.load_data_function];
            if(typeof fn === 'function') {
                fn(false);
            }
        }

        function saveFilterPreferences() {
            if (settings.reload_page_flag) {
                if (typeof settings.api_params != "undefined") {
                    var extra_params = "";
                    $.each(settings.api_params, function(param_name, param_value) {
                        extra_params += "&" + param_name + "=" + param_value;
                    });
                }

                var filters = jQuery("#search-targets-form").serialize();
                var preference_data = $.ajax(
                    {
                        url: settings.api_url,
                        data: "method="+ settings.save_filters_method + "&" + filters + extra_params,
                        type: "POST"
                    }
                );

                $.when(preference_data).done
                (
                    function (data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status === "success") {
                            window.location.reload();
                        }
                    }
                );
            } else {
                jQuery("#search-targets-form").submit();
            }

        }

        function removeAllFilters() {
            var remove_filters_request = $.ajax(
                {
                    url: settings.api_url,
                    data: "method=" + settings.remove_filters_method,
                    type: "POST"
                }
            );

            $.when(remove_filters_request).done
            (
                function (data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status === "success") {
                        window.location.reload();
                    }
                }
            );
        }

        function applyFilter(data_source, data_label) {
            closeFilterMenu();
            setCurrentFilter(data_source, data_label);
            buildSearchInterface();
        }

        function buildSelectedOption (target_id, filter_type) {
            var label  = $("#" + filter_type + "_" + target_id).attr("data-label");
            var icon   = $(document.createElement("i")).addClass("icon-chevron-down pull-right btn-icon");

            $(".target-selected").remove();
            self.html(label).append("&nbsp;").append(icon);

            if (settings.total_filters > 1) {
                var filter = settings.filters[settings.current_filter].label;
                var selected_filter_label = $(document.createElement("span")).addClass("selected-filter-label").html(filter);
                self.prepend(selected_filter_label);
            }
        }

        function buildSelectedList () {
            if ($("#" + settings.current_filter + "_list_container").length == 0) {
                var selected_list = $(document.createElement("ul")).attr({"id": settings.current_filter + "_list_container"}).addClass("selected-items-list");
                $("#" + self.attr("id")).parent().append(selected_list);
            }
        }

        function buildSelectedListItem (target_id, target_title) {
            if (settings.list_selections) {
                buildSelectedList();

                var filter = settings.filters[settings.current_filter].label;
                var li = $(document.createElement("li")).addClass(settings.current_filter + "_target_item " + settings.current_filter + "_" + target_id).attr({
                    "data-id": target_id
                });
                var target_container = $(document.createElement("span")).addClass("selected-list-container");
                var selected_option = $(document.createElement("span")).addClass("selected-list-item");
                var remove_option = $(document.createElement("span")).addClass("remove-selected-list-item remove-target-toggle").attr({
                    "data-id": target_id,
                    "data-filter": settings.current_filter
                });

                selected_option.html(filter);
                remove_option.html("&times");
                target_container.append(selected_option).append(remove_option);
                li.append(target_container).append(target_title);

                $("#" + settings.current_filter + "_list_container").append(li);
            }
        }

        function buildListOnLoad () {
            if (settings.list_selections) {
                $.each(settings.filters, function (filter, options) {
                    $.each($("input[name=\""+ filter +"[]\"]"), function (key, input) {
                        var target_id = $(this).val();
                        var target_label = $(this).attr("data-label");

                        buildSelectedListItem(target_id, target_label);
                    });
                });
            }
        }

        function updateLazyloadOffset () {
            if (settings.lazyload) {
                settings.lazyload_offset = settings.lazyload_limit + settings.lazyload_offset;
            }
        }

        function resetLazyloadOffset () {
            if (settings.lazyload) {
                settings.lazyload_offset = 0;
            }
        }

        self.parent().addClass("entrada-search-widget");
        self.addClass("btn-search-filter");
        if (typeof settings.filters == "object") {
            for (var filter in settings.filters) {
                if (typeof settings.filters[filter].api_params == "undefined") {
                    settings.filters[filter].api_params = {};
                }
            }
        }
    };
}(jQuery));
