function course_edit_version_select(course_id) {

    var filter_type = 'course_tag';

    jQuery(function() {
        jQuery('#version_id').change(function() {
            refresh_course_objectives();
        });
    });

    function refresh_course_objectives() {

        var cperiod_id = parseInt(jQuery('#version_cperiod_id').val());

        var promise = fetch_course_objectives(course_id, cperiod_id).done(function(objectives) {

            var course_form = jQuery('#courseForm');

            /*
             * Remove UI elements for course objectives for the period that was being shown before.
             */
            course_form.find('#course_tag_list_container .course_tag_target_item').remove();
            course_form.find('input[name="' + filter_type + '[]"]').remove();

            /*
             * Add UI elements for course objectives for this period.
             */
            jQuery.each(objectives, function (index, objective) {

                var value = {
                    target_id: objective['objective_id'],
                    target_name: objective['objective_text']
                };
                var element_id = filter_type + '_' + value.target_id;

                jQuery('#' + element_id).remove();

                var tag_input = jQuery(document.createElement('input')).attr({
                    'type'        : 'hidden',
                    'class'       : 'search-target-control ' + filter_type + '_search_target_control',
                    'name'        : filter_type + '[]',
                    'id'          : element_id,
                    'value'       : value.target_id,
                    'data-id'     : value.target_id,
                    'data-filter' : filter_type,
                    'data-label'  : value.target_name
                });

                course_form.append(tag_input);
            });

            refresh_course_linked_objectives().done(function() {
                var filter_button = jQuery('#' + filter_type + '_button');

                filter_button.data('settings').build_list();
            });
        }).fail(function(message) {
            alert('Error getting course objectives: ' + message);
        });

        return promise;
    }

    function refresh_course_linked_objectives() {

        var cperiod_id = parseInt(jQuery('#version_cperiod_id').val());
        var version_id = parseInt(jQuery('#version_id').val());

        var promise = fetch_course_linked_objectives(course_id, cperiod_id, version_id).done(function(linked_objectives) {

            var linked_objective_controls = jQuery("#linked-objective-controls");

            /*
             * Remove UI elements for course objectives for the period that was being shown before.
             */
            linked_objective_controls.find('input[type="hidden"]').remove();

            /*
             * Add UI elements for course linked objectives for this period.
             */
            jQuery.each(linked_objectives, function (index, objective) {

                var objective_id = objective['objective_id'];
                var target_objective_id = objective['target_objective_id'];
                var target_objective_text = objective['target_objective_text'];
                var input = linked_objective_controls.find("input[data-id=" + objective_id + "][data-target-id=" + target_objective_id + "]");

                if (input.length === 0) {

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
            });
        }).fail(function(message) {
            alert('Error getting course linked objectives: ' + message);
        });

        return promise;
    }
}

function fetch_course_objectives(course_id, cperiod_id) {

    var promise = call_api('curriculum-tags', 'GET', 'get-course-objectives', {
        'course_id': course_id,
        'cperiod_id': cperiod_id,
        'show_codes': 1
    });

    return promise;
}

function fetch_course_linked_objectives(course_id, cperiod_id, version_id) {

    var promise = call_api('curriculum-tags', 'GET', 'get-course-linked-objectives', {
        'course_id': course_id,
        'cperiod_id': cperiod_id,
        'version_id': version_id,
        'show_codes': 1
    });

    return promise;
}
