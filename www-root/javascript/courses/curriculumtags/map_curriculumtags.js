jQuery(document).ready(function ($) {
    var timeout;
    var objective_codes = "";
    var epas_to_show = 5;
    var timeout;

    $("body").tooltip({
        selector: "circle-tooltip",
        container: "body",
        placement: "bottom"
    });

    /**
     * event listener for epa map searching
     */
    $("#epa-search").on("keyup", function (e) {
        var keycode = e.keyCode;
        var search_text = $(this).val().toLowerCase();

        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32                    ||
            keycode == 13                    ||
            keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }

            if (search_text.length > 0) {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    $.each($(".accordion"), function (i, accordion) {
                        var accordion_text = $(this).text().toLowerCase();
                        if (accordion_text.indexOf(search_text) !== -1) {
                            $(accordion).removeClass("hide").addClass("visible");
                        } else {
                            $(accordion).addClass("hide").removeClass("visible");
                        }
                    });

                    if ($(".accordion.visible").length == 0) {
                        $(".accordion").addClass("hide");
                        $("#no-epas").removeClass("hide");
                        $("#show-more-btn").addClass("hide");
                    } else {
                        $("#no-epas").addClass("hide");
                    }
                }, 700);
            } else {
                $("#no-epas").addClass("hide");
                $(".accordion").addClass("hide");
                $(".accordion.on").removeClass("hide");
                $("#show-more-btn").removeClass("hide");
            }
        }
    });

    /**
     * event listener for the show more epa maps button
     */
    $("#show-more-btn").on("click", function (e) {
        var counter = 0;
        if ($(".accordion.hide").length > 0) {
            $(".accordion.hide").each(function (i, accordion) {
                if (counter < epas_to_show) {
                    $(accordion).removeClass("hide").addClass("on");
                }

                if ($(".accordion.hide").length == 0) {
                    $("#show-more-btn").addClass("hide");
                }
                counter ++;
            });
        }
        e.preventDefault();
    });

    /**
     * event listener for objective list items
     */
    $("#curriculum-tag-container").on("click", "li.objective", function () {
        var list_container = $(this).closest(".entrada-select-list-container");
        var populates = list_container.attr("data-populates");
        var multi_select = (list_container.attr("data-multi-select") === "true");
        var final_node = (list_container.attr("data-final-node") === "true");
        var current_list_item = $(this);
        var current_list_items = $(this).siblings();
        var objective_id = $(this).attr("data-id");

        current_list_item.toggleClass("active");
        deactivate_objective_set(populates, true);

        if (multi_select) {
            get_objectives(populates, "", objective_id);
        } else {
            current_list_items.not($(this)).removeClass("active");
            if (current_list_item.hasClass("active")) {
                get_objectives(populates, "", objective_id);
            }
        }

        if (final_node) {
            build_branch();
        } else {
            trim_branch();
        }
    });

    /**
     * event listeners for searching objective_sets
     */
    $(".objective-set-search").on("paste", function () {
        var objective_set = $(this).closest(".entrada-select-list-container").attr("data-objective-set");
        var list_items = $("#" + objective_set + "-list li.objective");
        var default_list_item = $("#" + objective_set + "-list li.entrada-search-list-empty");

        clearTimeout(timeout);
        timeout = setTimeout(function () {
            var search_term = $("#" + objective_set + "-search").val().toLowerCase();
            search_objectives(list_items, default_list_item, search_term);
        }, 300);
    });

    $(".objective-set-search").on("cut", function () {
        var objective_set = $(this).closest(".entrada-select-list-container").attr("data-objective-set");
        var list_items = $("#" + objective_set + "-list li.objective");
        var default_list_item = $("#" + objective_set + "-list li.entrada-search-list-empty");
        list_items.removeClass("hide");
        default_list_item.addClass("hide");
    });

    $(".objective-set-search").keyup(function (e) {
        var keycode = e.keyCode;
        var objective_set = $(this).closest(".entrada-select-list-container").attr("data-objective-set");
        var list_items = $("#" + objective_set + "-list li.objective");
        var default_list_item = $("#" + objective_set + "-list li.entrada-search-list-empty");
        var search_term = $(this).val().toLowerCase();

        if ((keycode > 47 && keycode < 58)   ||
            (keycode > 64 && keycode < 91)   ||
            (keycode > 95 && keycode < 112)  ||
            (keycode > 185 && keycode < 193) ||
            (keycode > 218 && keycode < 223) ||
            keycode == 32                    ||
            keycode == 13                    ||
            keycode == 8) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }

            clearTimeout(timeout);
            timeout = setTimeout(function () {
                search_objectives(list_items, default_list_item, search_term);
            }, 300);
        }
    });

    $("#save-branch").on("click", function (e) {
        e.preventDefault();
        var valid = validate_branch();
        if (valid) {
            //hide error message and submit form
            $("#msgs").addClass("hide");
            $("#manage-objectives-form").submit();
        } else {
            $("#msgs").removeClass("hide");
        }
    });

    function get_objectives (objective_set, search_term, objective_id) {
        build_objective_code(objective_set);
        var ajax_load = ($("#" + objective_set + "-list").closest(".entrada-select-list-container").attr("data-ajax-load") === "true");
        var objective_set_list = $("#" + objective_set + "-list");
        var objective_set_search = $("#" + objective_set + "-search");

        if (ajax_load) {
            var objective_request = $.ajax({
                url: "?section=api-cbme",
                data: "method=" + objective_set + "&course_id=" + course_id + (objective_codes.length > 0 ? "&objective_codes=" + objective_codes : "") + (typeof search_term != "undefined" ? "&search_term=" + search_term : "") + (typeof objective_id != "undefined" ? "&objective_id=" + objective_id : ""),
                type: "GET",
                beforeSend: function () {
                    objective_set_search.addClass("loading");
                },
                complete: function () {
                    objective_set_search.removeClass("loading");
                },
                error: function () {
                    var empty_li = $(document.createElement("li")).addClass("entrada-search-list-empty").html("An error occurred while attempting to fetch " + objective_set);
                    objective_set_search.removeClass("loading");
                    objective_set_list.empty();
                    objective_set_list.append(empty_li);
                }
            });

            $.when(objective_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                var objective_set_list = $("#" + objective_set + "-list");

                if (jsonResponse.status == "success") {
                    fill_objective_set(objective_set, jsonResponse.data.objectives, jsonResponse.data.parent);
                } else {
                    var empty_li = $(document.createElement("li")).addClass("entrada-search-list-empty").html("No " + objective_set + " found matching the search criteria");
                    objective_set_list.empty();
                    objective_set_list.append(empty_li);
                }
            });
        } else {
            activate_objective_set(objective_set);
        }
    }

    function activate_objective_set (objective_set) {
        $("#" + objective_set + "-list li").removeClass("hide");
        $("#" + objective_set + "-list li.entrada-search-list-empty").addClass("hide");
        $("#" + objective_set + "-search").prop("disabled", false);
    }

    function deactivate_objective_set (objective_set, cascade) {
        empty_objective_set(objective_set);
        $("#" + objective_set + "-list li.entrada-search-list-empty").removeClass("hide");
        $("#" + objective_set + "-search").prop("disabled", true);

        if (cascade) {
            var objective_sets = $(".entrada-select-list-container[data-objective-set=\""+ objective_set +"\"]").nextAll();
            $("#" + objective_set + "-list li").removeClass("active");
            $.each(objective_sets, function (i, objective_objective_set) {
                deactivate_objective_set($(objective_objective_set).attr("data-objective-set"));
            });
        }
    }

    function deactivate_all_objective_sets () {
        $(".objective").addClass("hide");
        $(".entrada-search-list-empty").removeClass("hide");
        $(".objective_set-search").prop("disabled", true);
    }

    function fill_objective_set (objective_set, objectives, parent) {
        $("#" + objective_set + "-list li.objective").remove();
        $("#" + objective_set + "-list li.entrada-search-list-empty").addClass("hide");
        $.each(objectives, function (i, objective) {
            if (typeof objective.searched_code != "undefined") {
                if ($(".entrada-select-list-item-header[data-code=\""+ objective.searched_code +"\"]").length == 0) {
                    var objective_header = {objective_code: objective.searched_code};
                    $("<li/>").loadTemplate("#entrada-select-list-item-header", objective_header).attr({"data-code": objective.searched_code}).appendTo("#" + objective_set + "-list").addClass("entrada-select-list-item-header objective-header");
                }
            }

            if ($(".objective-item-" + objective.objective_id).length == 0) {
                var objective_set_objective = {
                    objective_code: objective.objective_code,
                    objective_name: objective.objective_name
                };
                $("<li/>").loadTemplate("#entrada-select-list-item", objective_set_objective).attr({"data-id": objective.objective_id, "data-parent": parent, "data-code": objective.objective_code, "data-code-stub": (typeof objective.searched_code != "undefined" ? objective.searched_code : "0")}).appendTo("#" + objective_set + "-list").addClass("objective " + objective_set + " objective-item-" + objective.objective_id);
            }
        });

        activate_objective_set(objective_set);
    }

    function empty_objective_set (objective_set) {
        var ajax_load = ($("#" + objective_set + "-list").closest(".entrada-select-list-container").attr("data-ajax-load") === "true");
        if (ajax_load) {
            $("#" + objective_set + "-list li.objective-header").remove();
            $("#" + objective_set + "-list li.objective").remove();
        } else {
            $("#" + objective_set + "-list li.objective").removeClass("active");
            $("#" + objective_set + "-list li.objective").addClass("hide");
        }
    }

    function build_objective_code (objective_set) {
        var objective_codes_array = [];
        objective_codes = "";
        if ($("#roles-list li.active").length > 0 && $("#stages-list li.active").length > 0) {
            var selected_roles = $("#roles-list li.active");
            var selected_stage_code = $("#stages-list li.active").attr("data-code");
            $.each(selected_roles, function (i, role_item) {
                var code = {};
                var role_code = $(role_item).attr("data-code");
                if (objective_set == "milestones") {
                    var code_string = selected_stage_code + " " + role_code;
                } else {
                    var code_string = role_code;
                }
                code.code = code_string;
                objective_codes_array.push(code);
            });
            objective_codes = JSON.stringify(objective_codes_array);
        }
    }

    function search_objectives (list_items, default_list_item, search_term) {
        if (search_term.length > 0) {
            list_items.addClass("hide");
            list_items.each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(search_term) >= 0) {
                    $(this).removeClass("hide")
                }
            });

            if (list_items.not(".hide").length == 0) {
                default_list_item.removeClass("hide");
            } else {
                default_list_item.addClass("hide");
            }
        } else {
            list_items.removeClass("hide");
            default_list_item.addClass("hide");
        }
    }

    function build_branch () {
        var objective_lists = $(".entrada-select-list-container");
        var form = $("#manage-objectives-form");
        objective_lists.each(function () {
            var selected_items = $(this).find("ul.entrada-select-list li.active");
            var objective_set = $(this).attr("data-objective-set");
            var multi_select = ($(this).attr("data-multi-select") === "true");

            if (multi_select) {
                selected_items.each(function () {
                    var objective_id = $(this).attr("data-id");
                    var objective_code = $(this).attr("data-code");
                    if ($(".branch-node[data-id=\"" + objective_id + "\"]").length == 0) {
                        var objective_input = $(document.createElement("input")).attr({
                            type: "hidden",
                            name: "objective_sets[" + objective_set + "][" + objective_id + "]",
                            value: objective_code,
                            "data-id": objective_id
                        }).addClass("branch-node branch-" + objective_set);
                        form.append(objective_input);
                    }
                });
            } else {
                var objective_id = selected_items.attr("data-id");
                var objective_input = $(document.createElement("input")).attr({
                    type: "hidden",
                    name: objective_set,
                    value: objective_id,
                    "data-id": objective_id
                }).addClass("branch-node branch-" + objective_set);
                form.append(objective_input);
            }
        });
    }

    function trim_branch () {
        if ($(".branch-node").length > 0) {
            $(".branch-node").remove();
        }
    }

    function validate_branch () {
        var objective_lists = $(".entrada-select-list-container");
        var valid = true;
        objective_lists.each(function () {
            if ($(this).find("ul.entrada-select-list li.active").length == 0) {
                valid = false;
            }
        });
        return valid;
    }

    createTrees();

    function createTrees () {
        var container_width = $("#cbme-curriculum-map").width();
        var margin = {top: 20, right: 120, bottom: 20, left: 120};
        var width = container_width;
        var height = 500;
        var duration = 750;
        var epa_counter = 1;

        $.each(tree_json, function (index, branch) {
            var epa_accordion = $(document.createElement("div")).attr({id: "accordion-container-" + branch[0].name}).addClass("accordion");
            var epa_accordion_group = $(document.createElement("div")).addClass("accordion-group");
            var epa_accordion_heading = $(document.createElement("div")).addClass("accordion-heading");
            var epa_heading = $(document.createElement("h2")).html(branch[0].label);
            var epa_accordion_toggle = $(document.createElement("a")).attr({href: "#accordion-" + branch[0].name, "data-parent":"accordion-container-" + branch[0].name, "data-toggle": "collapse", "data-target": "#accordion-" + branch[0].name}).addClass("accordion-toggle").html(branch[0].label + ": " + branch[0].objective_name);
            var epa_accordion_body = $(document.createElement("div")).attr({id: "accordion-" + branch[0].name}).addClass("accordion-body collapse");
            var epa_accordion_body_inner = $(document.createElement("div")).attr({id: "accordion-inner-" + branch[0].name}).addClass("accordion-inner");

            epa_accordion_body_inner.append(epa_heading);
            epa_accordion_body.append(epa_accordion_body_inner);
            epa_accordion_heading.append(epa_accordion_toggle);
            epa_accordion_group.append(epa_accordion_heading).append(epa_accordion_body);
            epa_accordion.append(epa_accordion_group);

            $("#cbme-curriculum-map").append(epa_accordion);

            if (epa_counter > epas_to_show) {
                epa_accordion.addClass("hide");
            } else {
                epa_accordion.addClass("on");
            }

            var tree = d3.tree().size([height, width]);
            var svg = d3.select("#accordion-inner-" + branch[0].name).append("svg")
                .attr("width", width)
                .attr("height", height + margin.top + margin.bottom).on("mousewheel", function() {
                    d3.event.stopPropagation();
                })
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");


            var root = d3.stratify()
                .id(function(d) {
                    return d.name;
                })
                .parentId(function(d) {
                    return d.parent;
                })(branch);


            root.each(function(d) {
                d.name = d.data.label;
                d.id = i;
                d.objective_name = d.data.objective_name;
                i++;
            });

            root.x0 = height / 2;
            root.y0 = 0;

            update(root);

            function update(source) {
                // Compute the new tree layout.
                var nodes = tree(root).descendants(),
                    links = nodes.slice(1);

                // Normalize for fixed-depth.
                nodes.forEach(function(d) { d.y = d.depth * 100; });

                // Update the nodes…
                var node = svg.selectAll("g.node")
                    .data(nodes, function(d) { return d.id || (d.id = ++i); });

                // Enter any new nodes at the parent's previous position.
                var nodeEnter = node.enter().append("g")
                    .attr("class", "node")
                    .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
                    .on("click", click);

                nodeEnter.append("circle")
                    .attr("r", 1e-6)
                    .style("fill", function(d) { return d._children ? "#000" : "#fff"; }).attr("title", function(d) { return d.objective_name }).attr("class", "circle-tooltip");

                nodeEnter.append("text")
                    .attr("x", function(d) { return d.children || d._children ? -10 : 10; })
                    .attr("dy", ".35em")
                    .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
                    .text(function(d) { return d.name; })
                    .style("fill-opacity", 1e-6);

                // Transition nodes to their new position.
                var nodeUpdate = node.merge(nodeEnter).transition()
                    .duration(duration)
                    .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

                nodeUpdate.select("circle")
                    .attr("r", 4.5)
                    .style("fill", function(d) { return d._children ? "#00a8be" : "#fff"; }).attr("title", function(d) { return d.objective_name }).attr("class", "circle-tooltip").style("cursor", "pointer");

                nodeUpdate.select("text")
                    .style("fill-opacity", 1);

                // Transition exiting nodes to the parent's new position.
                var nodeExit = node.exit().transition()
                    .duration(duration)
                    .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
                    .remove();

                nodeExit.select("circle")
                    .attr("r", 1e-6);

                nodeExit.select("text")
                    .style("fill-opacity", 1e-6);


                // Update the links…
                var link = svg.selectAll("path.link")
                    .data(links, function(link) { var id = link.id + '->' + link.parent.id; return id; });

                // Transition links to their new position.
                link.transition()
                    .duration(duration)
                    .attr("d", connector);

                // Enter any new links at the parent's previous position.
                var linkEnter = link.enter().insert("path", "g")
                    .attr("class", "link")
                    .attr("d", function(d) {
                        var o = {x: source.x0, y: source.y0, parent:{x: source.x0, y: source.y0}};
                        return connector(o);
                    });

                // Transition links to their new position.
                link.merge(linkEnter).transition()
                    .duration(duration)
                    .attr("d", connector);

                // Transition exiting nodes to the parent's new position.
                link.exit().transition()
                    .duration(duration)
                    .attr("d",  function(d) {
                        var o = {x: source.x, y: source.y, parent:{x: source.x, y: source.y}};
                        return connector(o);
                    })
                    .remove();

                // Stash the old positions for transition.
                nodes.forEach(function(d) {
                    d.x0 = d.x;
                    d.y0 = d.y;
                });

                $(".circle-tooltip").tooltip({container: "body"});
            }

            // Toggle children on click.
            function click(d) {
                if (d.children) {
                    d._children = d.children;
                    d.children = null;
                } else {
                    d.children = d._children;
                    d._children = null;
                }
                update(d);
            }

            function connector(d) {
                return "M" + d.y + "," + d.x +
                    "C" + (d.y + d.parent.y) / 2 + "," + d.x +
                    " " + (d.y + d.parent.y) / 2 + "," + d.parent.x +
                    " " + d.parent.y + "," + d.parent.x;
            }

            function collapse(d) {
                if (d.children) {
                    d._children = d.children;
                    d._children.forEach(collapse);
                    d.children = null;
                }
            }
            epa_counter ++;
        });
    }
});
