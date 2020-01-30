jQuery(document).ready(function ($) {
    var timeout;
    var table_list = [
        "stage-container"
    ];
    var spinner_div = jQuery(document.createElement("div")).addClass("spinner-container");
    var loading_tasks = jQuery(document.createElement("h3")).html("Loading EPAs...");
    var spinner = jQuery(document.createElement("img")).attr({
        "class": "loading_spinner",
        "src": ENTRADA_URL + "/images/loading.gif"
    });

    /**
     * Event listener for the hide/show EPAs buttons
     */
    $(document).on("click", ".stage-toggle, .stage-container h2, .epa-progress-toggle", function (e) {
        var preference = "expanded";
        var stage = $(this).attr("data-stage");
        var show_more_btn = $(".show-more-btn");

        if (show_more_btn.hasClass("active")) {
            show_more_btn.removeClass("active").siblings().not(".visible").addClass("hide");
            show_more_btn.find("a").html(cbme_progress_dashboard.show_more);
        }

        if ($("#" + stage).hasClass("collapsed")) {
            $("#" + stage).show();
            $("#" + stage + "-show-hide").removeClass("fa-angle-down").addClass("fa-angle-up");
            $("#" + stage).removeClass("collapsed");
            $("#" + stage).slideDown(200);
        } else {
            $("#" + stage + "-show-hide").removeClass("fa-angle-up").addClass("fa-angle-down");
            $("#" + stage).addClass("collapsed");
            $("#" + stage).slideUp(200);
        }

        if ($(this).parent().find(".stage-toggle").hasClass("collapsed")) {
            $(this).parent().find(".stage-toggle").addClass("expanded");
            $(this).parent().find(".stage-toggle").removeClass("collapsed");
            $(this).parent().find(".stage-toggle-label").html(cbme_progress_dashboard.hide);
            $(this).parent().find(".epa-container").slideDown(200);
        } else {
            preference = "collapsed";
            $(this).parent().find(".stage-toggle").addClass("collapsed");
            $(this).parent().find(".stage-toggle").removeClass("expanded");
            $(this).parent().find(".stage-toggle-label").html(cbme_progress_dashboard.show);
            $(this).parent().find(".epa-container").slideUp(200);
        }

        set_view_preference(preference, stage);
        e.preventDefault();
    });

    $(document).on("click", ".epa-progress-toggle-secondary", function (e) {
        var preference = "expanded";
        var stage = $(this).attr("data-stage");
        if ($("#" + stage).hasClass("collapsed")) {
            $("#" + stage + "-show-hide").removeClass("fa-angle-down").addClass("fa-angle-up");
            $("#" + stage).show().removeClass("collapsed").slideDown(200);
        } else {
            $("#" + stage + "-show-hide").removeClass("fa-angle-up").addClass("fa-angle-down");
            $("#" + stage).addClass("collapsed").slideUp(200);
        }

        set_view_preference(preference, stage);
        e.preventDefault();
    });

    $(document).on("click", ".epa-map-toggle", function(e) {
        var stage = $(this).attr("data-stage");
        if ($("#cbme-curriculum-map-"+stage).hasClass("collapsed")) {
            $("#cbme-curriculum-map-"+stage).show();
            $("#" + stage + "-map-show-hide").removeClass("fa-angle-down").addClass("fa-angle-up");
            $("#cbme-curriculum-map-"+stage).removeClass("collapsed");
            $("#cbme-curriculum-map-"+stage).slideDown(200);
        } else {
            $("#" + stage + "-map-show-hide").removeClass("fa-angle-up").addClass("fa-angle-down");
            $("#cbme-curriculum-map-"+stage).addClass("collapsed");
            $("#cbme-curriculum-map-"+stage).slideUp(200);
        }
        e.preventDefault();
    });

    function set_view_preference(preference, stage) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-epa-view-preference", "preference": preference, "stage": stage}
        });
    }
    

    /**
     * Event handler for typing in the EPA seach box
     */
    $(".task-table-search").keyup(function (e) {
        var keycode = e.keyCode;
        var table_search = $(this);

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
            if($("#no-search-results").length != 0) {
                $("#no-search-results").remove();
            }
            if (table_search.val().length == 0) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    $(".list-set").removeClass("hide");
                    $("#filter-container").show();
                }, 500);
            }
            if (table_search.val().length >= 2) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    $.each(table_list, function (i, v) {
                        if (table_search.hasClass(v + "-search")) {
                            $(".list-set").addClass("hide");
                            $(".list-set:containsNoCase("+ table_search.val() +")").removeClass("hide");
                            $(".list-set:containsNoCase("+ table_search.val() +")").parent().show();
                            $(".stage-toggle").removeClass("collapsed").addClass("expanded");
                            $("#filter-container").hide();
                            if( $(".list-set:visible").length === 0) {
                                var no_results = $(document.createElement("div")).attr("id", "no-search-results").html("There were no results matching: " + table_search.val().bold());
                                $("#stage-container").prepend(no_results);
                            }
                        }
                    });
                }, 500);
            }
        }
    });

    /**
     * Case sensitive text search
     * @param el element
     * @param i
     * @param m
     * @returns {boolean}
     */
    $.expr[":"].containsNoCase = function(el, i, m) {
        var search = m[3];
        if (!search) return false;
        var pattern = new RegExp(search,"i");
        return pattern.test($(el).text());
    };

    /**
     * Look to see if there are any EPAs that need to be moved to the top of the page
     * @type {*}
     */
    var move_up  = $(".move-up");
    if(move_up.length !=0) {
        var move_top = move_up.clone(true);
        var epa_code = move_up.children().children().children().first().text();
        var stage = $(".move-up").attr("stage");
        var stage_name = $("div").find("[stage-id='" + stage + "']");
        move_top.find(".epa-progress-toggle").attr("data-stage", epa_code + "-cloned");
        move_top.find(".epa-progress-toggle-secondary").attr("data-stage", epa_code + "-secondary-cloned");
        move_top.find(".epa-description-block").attr("id", epa_code + "-cloned");
        move_top.find(".epa-description-block-secondary").attr("id", epa_code + "-secondary-cloned");
        move_top.find(".fa").attr("id", epa_code + "-cloned-show-hide");
        $("#filter-container").append($(document.createElement("p")).attr("id", "filtered-by-text").addClass("list-set-item-label space-above").text("Filtered by " + stage_name.text() + " > " + epa_code), move_top);
    }

    createTrees();
    var epas_to_show = 5;

    function createTrees () {
        var container_width = $(".epa-description-block").width();
        var margin = {top: 20, right: 120, bottom: 20, left: 120};
        var width = container_width;
        var height = 500;
        var duration = 750;
        var epa_counter = 1;

        $.each(tree_json, function (index, branch) {
            var epa_accordion = $(document.createElement("div")).attr({id: "accordion-container-" + branch[0].name}).addClass("epa-accordion-container");
            var epa_accordion_group = $(document.createElement("div"));
            var epa_heading = $(document.createElement("h2")).html(branch[0].label).addClass("space-left");
            var epa_accordion_body = $(document.createElement("div")).attr({id: "accordion-" + branch[0].name}).addClass("accordion-body");
            var epa_accordion_body_inner = $(document.createElement("div")).attr({id: "accordion-inner-" + branch[0].name});

            epa_accordion_body_inner.append(epa_heading);
            epa_accordion_body.append(epa_accordion_body_inner);
            epa_accordion_group.append(epa_accordion_body);
            epa_accordion.append(epa_accordion_group);

            $("#cbme-curriculum-map-"+branch[0].label).append(epa_accordion);

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

            var i=0;
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