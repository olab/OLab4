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
 * Filtered audience selector jQuery extension
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
jQuery(function($) {
    $.fn.audienceSelector = function(options) {
        var self = $(this);
        var interval;

        var settings = $.extend({
            "filter"        : "",
            "filter_type"   : "proxy_id",
            "interval"      : 500,
            "api_url"       : "",
            "min_chars"     : 3,
            "no_results_text" : "No results found",
            "error_text"    : "Error adding member",
            "target"        : "#added-member-list",
            "delete_icon"   : "icon-remove-circle",
            "delete_attr"   : "data-afauthor-id",
            "content_type"  : "form-author",
            "content_target" : "",
            "content_style" : "default",
            "add_audience"  : true,
            "api_params" : [],
            "get_method" : "get-filtered-audience",
            "post_method" : "add-permission",
            "handle_result_click" : function(v) {
                if (settings.add_audience == true) {
                    addAudienceMember(v);
                } else {
                    appendMemberToList(v.id, v.fullname);
                }
            }
        }, options);

        self.parent().css("position", "relative");

        self.on("keypress", function(e) {
            clearInterval(interval);
            interval = window.setInterval(fetchFilteredAudience, settings.interval);
        });

        self.on("focus", function(e) {
            if (self.val().length >= settings.min_chars || settings.filter_type == "organisation_id") {
                clearInterval(interval);
                interval = window.setInterval(fetchFilteredAudience, settings.interval);
            }
        });

        self.on("blur", function(e) {
            clearInterval(interval);
            $(".audience-selector-container").remove();
        });

        if (settings.filter.length > 0) {
            $(settings.filter).on("change", function(e) {
                self.val("");
                settings.filter_type = $(this).val();
            });
        }

        function fetchFilteredAudience() {
            var search_value = self.val();

            var data = {
                "method"        : settings.get_method,
                "search_value"  : search_value,
                "filter_type"   : settings.filter_type,
                "content_type"   : settings.content_type,
                "content_target" : settings.content_target
            };

            $.each(settings.api_params, function(i, v) {
                data[i] = v.val();
            });

            if (search_value.length >= settings.min_chars || settings.filter_type == "organisation_id") {
                $.ajax({
                    url: settings.api_url,
                    type: "GET",
                    data: data,
                    success : function(data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.data.length) {
                            buildDom(jsonResponse);
                        }
                    }
                });
            }
            clearInterval(interval);
        }

        function createListItem(v) {
            var item = $(document.createElement("li"))
                .addClass("audience-selector-item")
                .data("author-id", v.id).html(v.fullname + "<br /><span class=\"content-small\">" + v.email + "</span>")
                .on("mousedown", function(e) {
                    settings.handle_result_click(v);
                    self.val("");
                });
            return item;
        }

        function buildDom(jsonResponse) {
            if ($(".audience-selector-container").length <= 0) {
                var selector_container = $(document.createElement("div")).addClass("audience-selector-container");
            } else {
                var selector_container = $(".audience-selector-container");
                selector_container.empty();
            }

            var selector_list = $(document.createElement("ul")).addClass("audience-selector-list");
            if (jsonResponse.results > 0) {
                $(jsonResponse.data).each(function(i, v) {
                    if ($("input[data-member-id="+v.id+"]").length <= 0) {
                        selector_list.append(
                            createListItem(v)
                        );
                    }
                });
            }

            if (selector_list.children().length <= 0) {
                var dummy = new Object({
                    "id" : "",
                    "fullname" : settings.no_results_text,
                    "email" : ""
                });
                selector_list.append(
                    createListItem(dummy)
                );
            }

            selector_container.append(selector_list);

            if ($(".audience-selector-container").length <= 0) {
                selector_container.insertAfter(self);
            }
        }

        function addAudienceMember(member) {
            $(".audience-selector-container").remove();
            $.ajax({
                url: settings.api_url,
                type: "POST",
                data: {
                    "method"        : settings.post_method,
                    "member_id"     : member.id,
                    "member_type"   : settings.filter_type,
                    "content_type"   : settings.content_type,
                    "content_target" : settings.content_target
                },
                success : function(data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        if (jsonResponse.data.view_html) {
                            var view_html = jsonResponse.data.view_html;
                        } else {
                            var view_html = 0;
                        }
                        appendMemberToList(jsonResponse.data.author_id, member.fullname, view_html);
                    } else {
                        addError(member.fullname);
                    }
                }
            });
        }

        function appendMemberToList(author_id, fullname, view_html) {
            if (settings.content_style == "default") {
                var member_trash = $(document.createElement("a")).attr({"href" : "#"}).addClass("remove-permission").attr(settings.delete_attr, author_id).html("<i class=\"" + settings.delete_icon + "\"></i> ");

            if ($(settings.target).length <= 0) {
                var member_list = $(document.createElement("ul")).addClass("unstyled").attr({"id" : "added-member-list"});
            } else {
                var member_list = $(settings.target);
            }

                var member_list_line = $(document.createElement("li")).append(member_trash, fullname);
            } else if (settings.content_style == "exam") {

                var target_div = $("#author-list-" + settings.filter_type + "-container");

                if (target_div.hasClass("hide")) {
                    target_div.removeClass("hide");
                }

                var member_list = $("#author-list-" + settings.filter_type);

                var member_list_line = view_html;
            }

            member_list.append(member_list_line);
            if ($(settings.target).length <= 0) {
                member_list.insertAfter(self);
            }
        }

        function addError(fullname) {
            if ($(settings.target).length <= 0) {
                var member_list = $(document.createElement("ul")).addClass("unstyled").attr({"id" : "added-member-list"});
            } else {
                var member_list = $(settings.target);
            }

            var member_list_line = $(document.createElement("li")).addClass("error-adding-member").append(settings.error_text + ": " + fullname);
            member_list.append(member_list_line);
            if ($(settings.target).length <= 0) {
                member_list.insertAfter(self);
            }
        }
    };
});