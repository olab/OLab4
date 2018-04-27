// Curriculum tab for Add / Edit objectives
//
// @author Organisation: University of Ottawa
// @author Unit: Faculty of Medicine, Medtech
// @author Developer: Yacine Ghomri <yghomri@uottawa.ca>
// @copyright Copyright 2017 University of Ottawa. All Rights Reserved.

var course_obj = JSON.parse(course_json);
var course_list = [];
jQuery.each(course_obj, function (i, elem) {
    course_list.push({"title": i, "id": elem});
});

var event_obj = JSON.parse(event_json);
var mapped_event = [];
jQuery.each(event_obj, function (i, elem) {
    mapped_event.push({"event_title": elem.event_title, "course_name": elem.course_name, "event_id": elem.event_id});
});

// Dropdown list of courses
function populate_course() {
    if (course_list.length > 0) {
        $j.each(course_list, function(i, elem) {
            var option = '<option value="' + elem.id + '">' + elem.title + '</option>';
            jQuery('#select_course').append(option);
        });
    }
}

// Mapped Events in Right side
function generate_mapped_events() {
    var html = "";
    if (mapped_event.length > 0) {
        html += '<div id ="checkboxes" class="checkbox">';
        html += '<ul style="list-style: none;">';

        $j.each(mapped_event, function (i, elem) {
            html += '<li>';
            html += '<label for="event_' + elem.event_id + '"><input type="checkbox" name="mappedEvents[]" value="' + elem.event_id + '" checked>&nbsp;';
            html += '<a target="_blank" href="../events?section=edit&id=' + elem.event_id + '">' + elem.event_title + " (" + elem.course_name + ")" + '</a></label>';
            html += '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }
    return html;
}

// Events on the Left side
function buildEvents(data) {
    var courseTitle = $j("#select_course").find(":selected").text();
    var courseId = $j("#select_course").find(":selected").val();
    var html = '<span id="courseName">' + courseTitle + "</span>";

    if (data.length != 0) {

        html += '<div id="checkboxes" class="checkbox">';
        html += '<ul style="list-style: none;">';
        data.each( function(item) {
            var checked = "";
            mapped_event.each( function(elem) {
                if (elem.event_id == item.event_id) {
                    checked = "checked";
                    return false;
                }
            });

            html += '<li>';
            html += '<label for="event_' + item.event_id + '"><input type="checkbox" ' + checked + ' id="event_' + item.event_id + '" name="courseEvents[]" value="' + item.event_id + '" />';
            html += '<a target="_blank" href="../events?section=edit&id=' + item.event_id + '">' + item.event_title + '</a></label>';
            html += '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }
    jQuery('#course_tree').html(html);
}

// Handle list of courses in dropdown list
function handleCoursesList() {
    id = jQuery('#select_course option:selected').attr('value');
    if (id == 0) {
        var html = 'Select a filter to get a list of events that can be mapped to this objective.';
        jQuery('#course_tree').html(html);
    } else {
        getLearningEvents(id);
    }
}

// Check selected courses in dropdown list
function checkSelectedCourse() {
    if (jQuery("#select_course").val() == 0) {
        var html = 'Select a filter to get a list of events that can be mapped to this objective.';
        jQuery('#course_tree').html(html);
    }
}

// Left side events checkboxes
function leftSideEvents() {
    var course_name = jQuery("#courseName").text();
    if (this.checked) {
        // Add the event to the mapped events.
        var eventTitle = $j("label[for=event_" + this.value + "]").find("a").text();
        mapped_event.push({"event_id": this.value, "event_title": eventTitle, "course_name": course_name});
    } else {
        var eventID = this.value;
        // Delete the event to the mapped events
        mapped_event.each( function (elem, i) {
            if ( elem.event_id == eventID ) {
                mapped_event.splice(i, 1);
            }
        });
    }
    // Update the mapped events list on the right side.
    jQuery('#mapped_events').html(generate_mapped_events());
}

// Bind to the mapped_events checkboxes (right side) that are dynamically created.
function rightSideMappedEvents() {
    var eventID = this.value;
    if (!this.checked) {
        mapped_event.each( function (elem, i) {
            if ( elem.event_id == eventID ) {
                mapped_event.splice(i, 1);
            }
        });
    }
    // Update the mapped events list on the right side.
    jQuery('#mapped_events').html(generate_mapped_events());
    // Get the course id from dropdown list
    id = jQuery('#select_course option:selected').attr('value');
    if (id == 0) {
        var html = 'Select a filter to get a list of events that can be mapped to this objective.';
        jQuery('#course_tree').html(html);
    } else {
        //var input = jQuery("#course_tree > ul > li").find("input");
        var input = jQuery("#course_tree > div#checkbox").find("input");
        input.each( function(index, elem) {
            var left_side_event_id = jQuery(elem).val();
            if (left_side_event_id == eventID) {
                jQuery(elem).prop("checked", false);
            }
        });
    }
}

// Ajax Call to get Events
var getLearningEvents = function(course_id)  {
    var filtersToSend = {};
    filtersToSend.CourseId = course_id;

    var data = $j.ajax({
        url: ENTRADA_URL + "/api/api-objectives.inc.php?method=get-events&data=" + encodeURI(JSON.stringify(filtersToSend)),
        cache:  false,
        type:   'GET',
        dataType:   "json",
        error: function(jqXHR, textStatus, errorThrown) {
            var error   =   {"statusCode":jqXHR.status, "textStatus":textStatus, "error":errorThrown};
            console.log(error);
        },
        success: function(data) {
            buildEvents(data);
        }
    });
};

// Bind
jQuery(document).ready(function() {
    // Bind to the select_course dropdown list
    jQuery('#select_course').click(handleCoursesList);

    // Bind to the course_tree checkboxes (left side) that are dynamically created.
    jQuery('#course_tree').on('click', ':checkbox', leftSideEvents);

    // Bind to the mapped_events checkboxes (right side) that are dynamically created.
    jQuery('#mapped_events').on('click', ':checkbox', rightSideMappedEvents);

    // Can only select one Domain
    jQuery('#DOMAIN input').on('change', function() {
        jQuery('#DOMAIN input').not(this).prop('checked', false);
    });

    checkSelectedCourse();
    populate_course();
    jQuery('#mapped_events').html(generate_mapped_events());
});
