var script_name = document.scripts[document.scripts.length-1].src;
jQuery.getScript(script_name.match(/.*\//) + "get-objective-text.js");

function link_objectives_view(link_objectives_modal, objective_container, allowed_tag_set_ids, exclude_tag_set_ids, allowed_objective_ids, context) {

    var objective_id;

    var search_button;
    var linked_objectives_container = link_objectives_modal.find("#linked-objectives");
    var linked_objective_controls = jQuery("#linked-objective-controls");
    var all_linked_objectives_container = jQuery("#all-linked-objectives");

    link_objectives_modal.on("change", ".entrada-search-widget .search-filter-item[data-parent!=\"0\"] .search-target-input-control", function(e) {
        var target_objective_id = jQuery(this).val();
        var target_objective_text = jQuery(this).attr("data-label");
        if (jQuery(this).is(":checked")) {
            link_objective(target_objective_id, target_objective_text);
        } else {
            remove_linked_objective(target_objective_id);
        }
    });

    linked_objectives_container.on("click", "li img", function() {
        var target_objective_id = jQuery(this).parent().attr("data-target-id");
        remove_linked_objective(target_objective_id);
    });

    all_linked_objectives_container.on("click", "li img", function() {
        var target_objective_id = jQuery(this).parent().attr("data-target-id");
        var target_objective_text = jQuery(this).parent().attr("data-label");
        link_objective(target_objective_id, target_objective_text);
    });

    objective_container.on("click", ".selected-items-list li", function(e) {
        if (e.target.className.indexOf('remove-selected-list-item') == -1) {
            objective_id = jQuery(this).attr("data-id");
            var copy = jQuery(this).clone();
            copy.children().remove();
            var objective_text = copy.html();
            link_objectives_modal.find("#source-objective-text").html(objective_text);
            link_objectives_show();
        }
    });

    function link_objectives_show() {
        linked_objectives_populate();
        show_all_linked_objectives(objective_id);
        add_search_button();
        link_objectives_modal.dialog({
            title: "Link Curriculum Tag",
            modal: true,
            draggable: true,
            resizable: true,
            width: 700,
            height: 450,
            dialogClass: "fixed",
            buttons: {
                "Done": function() {
                    jQuery(this).dialog("close");
                }
            },
            close: function(event, ui) {
                jQuery(this).dialog("destroy");
            }
        });
    }

    function add_search_button() {
        var search_container = jQuery("#link-objective-search-container");
        search_container.find(".controls").remove();
        var search_controls = jQuery(document.createElement("div")).addClass("controls");
        search_button = jQuery(document.createElement("button")).attr({
            "id": "link-objective-button",
            "type": "button"
        }).addClass("btn btn-search-filter").css({
            "min-width": "220px",
            "text-align": "left"
        });
        var button_text = jQuery(document.createElement("span")).attr("id", "link-objective-button-text").html("Select Curriculum Tag");
        search_button.append(button_text);
        var chevron_down = jQuery(document.createElement("i")).addClass("icon-chevron-down btn-icon pull-right");
        search_button.append(chevron_down);
        search_controls.append(search_button);
        search_container.append(search_controls);
        search_button.advancedSearch({
            resource_url: ENTRADA_URL,
            api_url: api_url(),
            filters: {
                link_objective: {
                    label: 'Curriculum Tags',
                    data_source: 'get-objectives',
                    secondary_data_source: 'get-objectives',
                    mode: 'checkbox',
                    set_button_text_to_selected_option: false
                }
            },
            session_storage_key: 'linked_objective_id',
            no_results_text: 'No Curriculum Tags found matching the search criteria',
            parent_form: undefined,
            width: 400,
            modal: true
        });
    }

    function api_url() {
        var query_str = jQuery.param({
            'show_codes': 1,
            'from_objective_id': objective_id,
            'allowed_tag_set_ids': allowed_tag_set_ids ? allowed_tag_set_ids : [],
            'exclude_tag_set_ids': exclude_tag_set_ids ? exclude_tag_set_ids : [],
            'allowed_objective_ids': allowed_objective_ids ? allowed_objective_ids : []
        });
        return ENTRADA_URL + '/api/curriculum-tags.api.php?' + query_str;
    }

    function linked_objectives_populate() {
        linked_objectives_container.html("");
        linked_objective_controls.find("input[data-id=" + objective_id + "]").each(function () {
            var target_objective_id = jQuery(this).attr("data-target-id");
            var target_objective_text = jQuery(this).attr("data-text");
            linked_objectives_container.append(create_objective_list_element(target_objective_id, target_objective_text, "delete"));
        });
    }

    function link_objective(target_objective_id, target_objective_text) {
        link_objectives(objective_id, target_objective_id, target_objective_text);
        add_list_element(target_objective_id, target_objective_text);
    }

    function link_objectives(objective_id, target_objective_id, target_objective_text) {
        var input = linked_objective_controls.find("input[data-id=" + objective_id + "][data-target-id=" + target_objective_id + "]");
        if (!input.length) {
            var new_input = jQuery(document.createElement("input")).attr({
                "type": "hidden",
                "name": "linked_objectives[" + objective_id + "][" + target_objective_id + "]",
                "value": target_objective_id,
                "data-id": objective_id,
                "data-target-id": target_objective_id,
                "data-text": target_objective_text
            });
            linked_objective_controls.append(new_input);
        }
    }

    function add_list_element(target_objective_id, target_objective_text) {
        var li = linked_objectives_container.find("li[data-target-id=" + target_objective_id + "]");
        if (!li.length) {
            linked_objectives_container.append(create_objective_list_element(target_objective_id, target_objective_text, "delete"));
            all_linked_objectives_container.find("li[data-target-id=" + target_objective_id + "]").hide();
        }
    }

    function remove_linked_objective(target_objective_id) {
        var input = linked_objective_controls.find("input[data-id=" + objective_id + "][data-target-id=" + target_objective_id + "]");
        input.remove();
        var li = linked_objectives_container.find("li[data-target-id=" + target_objective_id + "]");
        li.remove();
        all_linked_objectives_container.find("li[data-target-id=" + target_objective_id + "]").show();
    }

    function show_all_linked_objectives(objective_id) {
        var version_id = jQuery('#version_id').val();
        var container_title = jQuery("#all-linked-objectives-title");
        container_title.hide();
        all_linked_objectives_container.html("");
        fetch_linked_objectives('from', objective_id, version_id, context, true).done(function(objectives_by_parent) {
            if (objectives_by_parent && !Array.isArray(objectives_by_parent) && Object.keys(objectives_by_parent).length) {
                container_title.show();
                for (var tag_set in objectives_by_parent) {
                    var target_objectives = objectives_by_parent[tag_set];
                    for (var target_objective_id in target_objectives) {
                        var target_objective = target_objectives[target_objective_id];
                        var target_parent_id = target_objective.objective_parent;
                        if (!allowed_objective_ids || !(target_parent_id in allowed_objective_ids) || (target_objective_id in allowed_objective_ids[target_parent_id])) {
                            var target_objective_text = get_objective_text(target_objective, true);
                            var new_li = create_objective_list_element(target_objective_id, target_objective_text, "add");
                            all_linked_objectives_container.append(new_li);
                            var input = linked_objective_controls.find("input[data-id=" + objective_id + "][data-target-id=" + target_objective_id + "]");
                            if (input.length) {
                                new_li.hide();
                            }
                        }
                    }
                }
            }
        });
    }
}

function remove_objective_view(remove_objective_modal, objective_container, context) {
    var objective_id;

    objective_container.on("click", ".remove-selected-list-item", function(e) {
        objective_id = jQuery(this).attr("data-id");
        var copy = jQuery(this).parent().parent().clone();
        copy.children().remove();
        var objective_text = copy.html();
        remove_objective_modal.find("#source-objective-text").html(objective_text);
        remove_objective_show();
    });

    function remove_objective_show() {
        show_linked_objectives('to', objective_id).done(function(to_results) {
            show_linked_objectives('from', objective_id).done(function(from_results) {
                if (to_results || from_results) {
                    remove_objective_modal.dialog({
                        title: "Remove Curriculum Tag",
                        modal: true,
                        draggable: true,
                        resizable: true,
                        width: 700,
                        height: 450,
                        dialogClass: "fixed",
                        buttons: {
                            "OK": function() {
                                jQuery(this).dialog("close");
                            }
                        },
                        close: function(event, ui) {
                            jQuery(this).dialog("destroy");
                        }
                    });
                }
            });
        });
    }

    function show_linked_objectives(direction, objective_id) {
        var deferred = jQuery.Deferred();
        var version_id = jQuery('#version_id').val();
        var container_title = jQuery('#' + direction + '-objectives-title');
        container_title.hide();
        var linked_objectives_container = remove_objective_modal.find('#' + direction + '-objectives');
        linked_objectives_container.html("");
        fetch_linked_objectives(direction, objective_id, version_id, context, false).done(function(objectives_by_parent) {
            if (objectives_by_parent && !Array.isArray(objectives_by_parent) && Object.keys(objectives_by_parent).length) {
                container_title.show();
                for (var tag_set in objectives_by_parent) {
                    var target_objectives = objectives_by_parent[tag_set];
                    for (var target_objective_id in target_objectives) {
                        var target_objective = target_objectives[target_objective_id];
                        var target_parent_id = target_objective.objective_parent;
                        var target_objective_text = get_objective_text(target_objective, true);
                        var new_li = create_objective_list_element(target_objective_id, target_objective_text, "");
                        linked_objectives_container.append(new_li);
                    }
                }
                deferred.resolve(true);
            } else {
                deferred.resolve(false);
            }
        });
        return deferred.promise();
    }
}

function create_objective_list_element(target_objective_id, target_objective_text, image_type) {
    var image_url;
    switch (image_type) {
        case "add":
            image_url = ENTRADA_URL + "/images/add.png";
            break;
        case "delete":
            image_url = ENTRADA_URL + "/images/action-delete.gif";
            break;
        default:
            break;
    }
    var image_html;
    if (image_url) {
        image_html = " <img src=\"" + image_url + "\">";
    } else {
        image_html = "";
    }
    var new_li = jQuery(document.createElement("li")).attr({
        "data-target-id": target_objective_id,
        "data-label": target_objective_text
    }).html(target_objective_text + image_html);
    return new_li;
}

function fetch_linked_objectives(direction, objective_id, version_id, context, not) {
    var deferred = jQuery.Deferred();
    var query = jQuery.extend({
        'method': 'get-linked-objectives',
        'objective_id': objective_id,
        'version_id': version_id ? version_id : 0,
        'direction': direction,
        'not': not ? 1 : 0
    }, context);
    jQuery.ajax({
        url: ENTRADA_URL + '/api/curriculum-tags.api.php',
        data: query,
        success: function(data, status, xhr) {
            var objectives_by_parent = JSON.parse(data);
            deferred.resolve(objectives_by_parent);
        },
        error: function(xhr, status, error) {
            deferred.reject(error);
        }
    });
    return deferred.promise();
}
