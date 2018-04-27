var script_name = document.scripts[document.scripts.length-1].src;
jQuery.getScript(script_name.match(/.*\//) + "get-objective-text.js");

var EDITABLE = false;
var loaded = {};
var loading_objectives = false;

jQuery(document).on('click', '.objective-title', function() {
    var has_children = jQuery(this).attr('data-has-children');
    if (has_children) {
        var id = jQuery(this).attr('data-id');
        var link_id = jQuery(this).attr('data-link-id');
        var children = [];
        var objective_key;
        if (link_id) {
            objective_key = id + '-' + link_id;
        } else {
            objective_key = id;
        }
        if (loaded[objective_key] === undefined || !loaded[objective_key]) {
            if (!loading_objectives) {
                var event_id = jQuery(this).attr('data-event-id');
                var cunit_id = jQuery(this).attr('data-course-unit-id');
                var course_id = jQuery(this).attr('data-course-id');
                var cperiod_id = jQuery(this).attr('data-curriculum-period-id');
                var query = {
                    'method': 'get-linked-objectives',
                    'show_has_links': 1,
                    'objective_id': id,
                    'version_id': VERSION_ID ? VERSION_ID : undefined,
                    'org_id': (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id),
                    'event_id': event_id,
                    'cunit_id': cunit_id,
                    'course_id': course_id,
                    'cperiod_id': cperiod_id
                };
                function load_data(direction, delayRender) {
                    query['direction'] = direction;
                    query['exclude_tag_set_ids'] = EXCLUDE_TAG_SET_IDS;
                    var loading = jQuery(document.createElement('img'))
                        .attr('src', SITE_URL + '/images/loading.gif')
                        .attr('width', '15')
                        .attr('title', 'Loading...')
                        .attr('alt', 'Loading...')
                        .attr('class', 'loading')
                        .attr('id', 'loading_' + objective_key);
                    jQuery('#objective_controls_' + objective_key).append(loading);
                    loading_objectives = true;
                    return jQuery.ajax({
                        url: SITE_URL + '/api/curriculum-tags.api.php',
                        data: query,
                        success: function(data, status, xhr) {
                            jQuery('#loading_' + objective_key).remove();
                            var objectives = jQuery.parseJSON(data);
                            if (loaded[objective_key] === undefined) {
                                loaded[objective_key] = objectives;
                            } else {
                                for (var tag_set_name in objectives) {
                                    loaded[objective_key][tag_set_name] = objectives[tag_set_name];
                                }
                            }
                            buildDOM(objectives, objective_key, direction, event_id, cunit_id, course_id, delayRender);
                            loading_objectives = false;
                        }
                    });
                }
                var direction = jQuery(this).attr('data-direction') ? jQuery(this).attr('data-direction') : 'from';
                if (direction === 'both') {
                    load_data('to', true).then(function() {
                        load_data('from');
                    });
                } else {
                    load_data(direction);
                }
            }
        } else if (jQuery('#children_' + objective_key).is(':visible')) {
            jQuery('#children_' + objective_key).slideUp(600);
            jQuery('#objective_title_' + objective_key).toggleClass("objective-open");
        } else {
            if (jQuery("#objective_list_" + objective_key).children('li').length > 0) {
                showChildren(objective_key);
            }
        }
    }
});

function buildDOM(objectives_by_parent, objective_key, direction, event_id, cunit_id, course_id, delayRender) {
    if (objectives_by_parent && !Array.isArray(objectives_by_parent) && Object.keys(objectives_by_parent).length > 0) {
        jQuery('#children_' + objective_key).hide();
        for (var tag_set in objectives_by_parent) {
            var tag_set_heading = jQuery(document.createElement('h3')).html(tag_set);
            jQuery('#objective_list_' + objective_key).append(tag_set_heading);
            var children = objectives_by_parent[tag_set];
            for (var objective_id in children) {
                var objective = children[objective_id];
                var data_title = get_objective_text(objective);
                var unique_id = Math.round(Math.random() * 1000000);
                var child_objective_key = objective.objective_id + '-' + unique_id;

                var container = jQuery(document.createElement('li'))
                    .attr('class', 'objective-container' + (!objective.has_links ? ' objective-container-no-children' : ' objective-container-children'))
                    .attr('data-id', objective.objective_id)
                    .attr('data-code', objective.objective_code)
                    .attr('data-name', objective.objective_name)
                    .attr("data-title", data_title)
                    .attr('data-description', objective.objective_description)
                    .attr('id', 'objective_' + child_objective_key);

                var title = jQuery(document.createElement('div'))
                    .attr('class', 'objective-title' + (!objective.has_links ? ' objective-title-no-children' : ' objective-title-children'))
                    .attr('id', 'objective_title_' + child_objective_key)
                    .attr('data-id', objective.objective_id)
                    .attr('data-link-id', unique_id)
                    .attr('data-event-id', event_id)
                    .attr('data-course-unit-id', cunit_id)
                    .attr('data-course-id', course_id)
                    .attr('data-title', data_title)
                    .attr('data-direction', direction)
                    .attr('data-has-children', objective.has_links)
                    .html(data_title);

                var controls = jQuery(document.createElement('div'))
                    .attr('class', 'objective-controls')
                    .attr('id', 'objective_controls_' + child_objective_key);

                var description_text = (data_title == objective.objective_name) ? objective.objective_description : null;
                var description_html = (description_text && description_text.trim() ? description_text : '');
                var description = jQuery(document.createElement('div'))
                    .attr('class', 'objective-description content-small')
                    .attr('id', 'description_' + child_objective_key)
                    .html(description_html);
                var child_container = jQuery(document.createElement('div'))
                    .attr('class', 'objective-children')
                    .attr('id', 'children_' + child_objective_key);
                var child_list =      jQuery(document.createElement('ul'))
                    .attr('class', 'objective-list')
                    .attr('id', 'objective_list_' + child_objective_key)
                    .attr('data-id', objective.objective_id);
                jQuery(child_container).append(child_list);
                var type = jQuery('#mapped_objectives').attr('data-resource-type');
                jQuery(container).append(title)
                    .append(controls)
                    .append(description)
                    .append(child_container);
                jQuery('#objective_list_' + objective_key).append(container);
            }
        }
        if (!delayRender){
            showChildren(objective_key);
        }
    }
}

function showChildren(objective_key) {
    jQuery('#children_' + objective_key).slideDown();
    jQuery('#objective_title_' + objective_key).toggleClass("objective-open");
}
