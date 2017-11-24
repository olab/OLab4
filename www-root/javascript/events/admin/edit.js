/*
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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 *
 */

var event_length_minutes = "";
event_length_changed();

jQuery(function($) {
    /* Event listeners */

    // Animates the window up and down
    $("#event_audience_type_custom_options").on("click", ".time-badge", function(e) {
        e.preventDefault();
        time_custom(e, "badge");
    });

    // Animates the window up and down, for Add page
    $("#audience-options").on("click", ".time-badge", function(e) {
        e.preventDefault();
        time_custom(e, "badge");
    });

    // Turns the validation on and off
    $("#warn-overlap").on("click", function(e) {
        e.preventDefault();

        var checked;
        var clicked = $(this);
        var icon = $(clicked).children("i");

        if (icon.hasClass("fa-square-o")) {
            checked = 0;
        } else {
            checked = 1;
        }

        if (checked === 1) {
            icon.addClass("fa-square-o").removeClass("fa-check-square-o");
            validation = false;
        } else {
            icon.removeClass("fa-square-o").addClass("fa-check-square-o");
            validation = true;
        }

        validate_times();
    });

    // Animates the window up and down
    $("#custom-time-close").click(function(e) {
        time_custom(e, "close");
    });

    // Validate times for overlapping
    $("#custom-time-validate").click(function() {
        validate_times();
    });

    // Modifies the start and end date of granular scheduling when the event time is changed.
    $("#editEventForm").on("change", "#event_start_hour", function() {
        update_start_end_times();
    });

    $("#editEventForm").on("change", "#event_start_min", function() {
        update_start_end_times();
    });

    $("#editEventForm").on("change", "#eventtype_ids", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    $("#editEventForm").on("change", "#duration_container .duration_segment", function() {
        event_length_changed();
    });

    $("#editEventForm").on("change", "#duration_container a.remove", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    $("#addEventForm").on("change", "#event_start_hour", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    $("#addEventForm").on("change", "#event_start_min", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    $("#addEventForm").on("change", "#eventtype_ids", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    $("#addEventForm").on("change", "#duration_container .duration_segment", function() {
        event_length_changed();
    });

    $("#addEventForm").on("change", "#duration_container a.remove", function() {
        setTimeout(function() {
            event_length_changed();
        }, 200);
    });

    if (typeof EVENT_COLOR_PALETTE != 'undefined' && Array.isArray(EVENT_COLOR_PALETTE)) {
        color_picker('#event_color', EVENT_COLOR_PALETTE);
    } else {
        color_picker('#event_color');
    }
});

/*
 * This function opens and closes the Event Audience Override window
 * it also scrolls to the row based on the badge clicked
 */
function time_custom(e, mode) {
    var target = e.target;
    if (mode == "close") {
        if (jQuery("#custom-time").data("visibility") == "hidden") {
            jQuery("#custom-time").slideDown("fast");
            jQuery("#custom-time").data("visibility", "visible");
        } else {
            jQuery("#custom-time").slideUp("fast");
            jQuery("#custom-time").data("visibility", "hidden");
        }
    } else if (mode == "badge") {
        if (jQuery("#custom-time").data("visibility") == "hidden") {
            jQuery("#custom-time").slideDown("fast");
            jQuery("#custom-time").data("visibility", "visible");
        }

        var audience   = "";
        var data_value = "";
        var group_type = "";

        if (jQuery(target).hasClass("badge")) {
            data_value = jQuery(target).parent().data("value");
            group_type = jQuery(target).parent().data("type");
        } else {
            data_value = jQuery(target).parent().parent().data("value");
            group_type = jQuery(target).parent().parent().data("type");
        }

        switch (group_type) {
            case "proxy_id":
                audience = "proxy_id";
                break;
            case "cohort":
                audience = "cohort";
                break;
            case "group":
            case "group_id":
                audience = "group_id";
                break;
        }

        var container   = jQuery("#event_audience_override");
        var scrollTo    = jQuery(".slider-range[data-type=" + audience + "][data-id=" + data_value + "]").parent().parent();

        if (typeof scrollTo && scrollTo != "undefined") {
            // Moves container to position of row desired
            var offset = scrollTo.offset().top - container.offset().top + container.scrollTop();

            container.scrollTop(offset);

            var invalid = jQuery(".slider-range[data-type=" + audience + "][data-id=" + data_value + "]").data("invalid");

            if (invalid == "1") {
                // Highlight row clicked
                scrollTo.animate({ backgroundColor: "#F88A8F" },500);

                // Delay and turn back to regular color
                scrollTo.delay(1000).animate({ backgroundColor: "#FFB9BC" },500);
            } else {
                // Highlight row clicked
                scrollTo.animate({ backgroundColor: "#EEEEEE" },500);

                // Delay and turn back to regular color
                scrollTo.delay(1000).animate({ backgroundColor: "#FAFAFA" },500);
            }
        }
    }
}

/*
 * This function is used to manage if an event audience override is turned on or not.
 * It changes some HTML and adds the item to a javascript Array that is used for submission on a hidden form field
 */
function time_on_off(state, audience_type, audience_value) {
    // Switches depending on type of group
    switch (audience_type) {
        case "student":
            // Proxy_id's
            proxy_custom_time_array[audience_value].custom_time = state;
            jQuery("#event_audience_students_custom_times").val(JSON.stringify(proxy_custom_time_array, true));
            break;
        case "cohort":
            // Cohorts/groups
            cohorts_custom_time_array[audience_value].custom_time = state;
            jQuery("#event_audience_cohorts_custom_times").val(JSON.stringify(cohorts_custom_time_array, true));
            break;
        case "cgroup":
            // Course groups
            cgroup_custom_time_array[audience_value].custom_time = state;
            jQuery("#event_audience_course_groups_custom_times").val(JSON.stringify(cgroup_custom_time_array, true));
            break;
    }

    // Updates the display
    var audience_display    = jQuery("#audience_" + audience_type + "_" + audience_value + " span.badge");
    if (state === 1) {
        if (jQuery(audience_display).hasClass("badge-time-off")) {
            jQuery(audience_display).removeClass("badge-time-off").addClass("badge-success");
        }
    } else {
        if (jQuery(audience_display).hasClass("badge-success")) {
            jQuery(audience_display).removeClass("badge-success").addClass("badge-time-off");
        }
    }
}

/*
 * This function is used by other functions to add minutes from one javascript date obeject to another
 */
function addMinutes(date, minutes) {
    return new Date(date.getTime() + minutes*60000);
}

/*
 * This function converts 24 hour clock to AM/PM formated for diplay in html
 *
 * @param {int} start_hours
 * @param {int} start_minutes
 * @param {int} end_hours
 * @param {int} end_minutes
 * @returns {Array} output      an array with the start and end display for html
 *
 */
function generate_clock(start_hours, start_minutes, end_hours, end_minutes) {
    var output = [];
    var start_date_display;
    var start_ampm;
    var end_date_display;
    var end_ampm;

    // Converts 24 hour clock to AM/PM
    if (start_hours == 0) {
        start_date_display = "12";
    } else if (start_hours > 12) {
        start_date_display = start_hours - 12;
    } else {
        start_date_display = start_hours;
    }

    if (start_hours > 11) {
        start_ampm = " pm";
    } else {
        start_ampm = " am";
    }

    if (end_hours == 0) {
        end_date_display = "12";
    } else if (end_hours > 12) {
        end_date_display = end_hours - 12;
    } else {
        end_date_display = end_hours;
    }

    if (end_hours > 11) {
        end_ampm = " pm";
    } else {
        end_ampm = " am";
    }

    if (start_minutes > 5) {
        start_date_display += ":" + start_minutes + start_ampm;
    } else {
        start_date_display += ":0" + start_minutes + start_ampm;
    }

    if (end_minutes > 5) {
        end_date_display += ":" + end_minutes + end_ampm;
    } else {
        end_date_display += ":0" + end_minutes + end_ampm;
    }

    output["start_date_display"]    = start_date_display;
    output["end_date_display"]      = end_date_display;

    return output;
}

/*
 * This function is run when a slider is changed, it updates the time selected in HTML and in the javascript array
 *
 * @param int start_date        the overall event start date
 * @param int end_date          the overall event start date
 * @param obj ui                the slide event object with the added minutes
 * @param string slider_id      the ID for the slider, if slide is false then it will find it based on the active class
 * @param string slider_type    the type for the slider, if slide is false then it will find it based on the active class
 * @param bool  slide           if set then find the id and type from the active class
 *
 */
function slide_event(start_date, end_date, ui, slider_id, slider_type, slide) {

    var audience        = "";
    var slider_type     = "";

    if (slide) {
        slider_id       = jQuery("div.slider-range").children("a.ui-state-active").parent().data("id");
        slider_type     = jQuery("div.slider-range").children("a.ui-state-active").parent().data("type");
    }

    var new_start_date  = addMinutes(start_date, ui[0]);
    var new_end_date    = addMinutes(start_date, ui[1]);
    var start_minutes   = new_start_date.getMinutes();
    var end_minutes     = new_end_date.getMinutes();
    var start_hours     = new_start_date.getHours();
    var end_hours       = new_end_date.getHours();
    var st_unix         = Math.floor(start_date.getTime()/1000);
    var new_st_unix     = Math.floor(new_start_date.getTime()/1000);
    var et_unix         = Math.floor(end_date.getTime()/1000);
    var new_et_unix     = Math.floor(new_end_date.getTime()/1000);
    var button_state    = 0;

    var clocks  = generate_clock(start_hours, start_minutes, end_hours, end_minutes);
    var slider  = jQuery(".slider-range[data-type=" + slider_type + "][data-id=" + slider_id + "]");
    var li      = jQuery("#audience_list").children("li[data-type=" + slider_type + "][data-value=" + slider_id + "]");

    // Updates the javascript array and the hidden form field with the new times
    if (slider_type == "proxy_id") {
        audience    = "student";
        slider_type = "proxy_id";
        proxy_custom_time_array[slider_id].custom_time_start    = new_st_unix;
        proxy_custom_time_array[slider_id].custom_time_end      = new_et_unix;
        jQuery("#event_audience_students_custom_times").val(JSON.stringify(proxy_custom_time_array, true));
    } else if (slider_type == "cohort") {
        // Cohorts/groups
        audience    = "cohort";
        slider_type = "cohort";
        cohorts_custom_time_array[slider_id].custom_time_start  = new_st_unix;
        cohorts_custom_time_array[slider_id].custom_time_end    = new_et_unix;
        jQuery("#event_audience_cohorts_custom_times").val(JSON.stringify(cohorts_custom_time_array, true));
    } else {
        // Course groups
        audience    = "cgroup";
        slider_type = "group_id";
        cgroup_custom_time_array[slider_id].custom_time_start   = new_st_unix;
        cgroup_custom_time_array[slider_id].custom_time_end     = new_et_unix;
        jQuery("#event_audience_course_groups_custom_times").val(JSON.stringify(cgroup_custom_time_array, true));
    }

    if (slider.data("invalid") == "1" && (new_st_unix > et_unix || new_st_unix > et_unix)) {
        slider.parent().parent().stop().animate({ backgroundColor: "#FAFAFA" }, 10);
        li.stop().animate({ backgroundColor: "#FFFFFF" }, 10);
        slider.data("invalid", "0");
    }

    // Updates the display
    var clock_html = clocks["start_date_display"] + " - " + clocks["end_date_display"];
    jQuery("#audience_" + audience + "_" + slider_id).children("span.time").html(clock_html);
    jQuery("#" + slider_type + "_" + "time_" + slider_id).html("<p>" + clock_html + "</p>");

    if (st_unix === new_st_unix && et_unix === new_et_unix) {
        button_state = 0;
    } else {
        button_state = 1;
    }

    // Updates the display of the clock button and settings the setting to use the custom times
    time_on_off(button_state, audience, slider_id);
}

/*
 * This function is used to start the initial position of the sliders and defines which functions to call on slide events
 * The default start times and snap periods are set here
 */
function start_sliders(obj, slider_max) {
    if (typeof obj !== "undefined" || obj !== undefined) {
        var current_start_date;
        var current_end_date;
        var button_state = 0;
        var audience_type = obj.audience_type;
        var audience_value = obj.audience_value;
        var state_type;

        if (obj.custom_time_start == 0 || typeof obj === "undefined") {
            // Object time stored as seconds and not milliseconds
            current_start_date  = start_date;
            current_end_date    = end_date;
            start_date_mins     = start_date.getTime() / (1000 * 60);
            state = 1;
        } else {
            // Object time stored as seconds and not milliseconds
            current_start_date  = new Date(obj.custom_time_start * 1000);
            current_end_date    = new Date(obj.custom_time_end * 1000);
        }

        var st_unix         = Math.floor(start_date.getTime()/1000);
        var new_st_unix     = Math.floor(current_start_date.getTime()/1000);
        var et_unix         = Math.floor(end_date.getTime()/1000);
        var new_et_unix     = Math.floor(current_end_date.getTime()/1000);
        var start_minutes   = current_start_date.getMinutes();
        var end_minutes     = current_end_date.getMinutes();
        var start_hours     = current_start_date.getHours();
        var end_hours       = current_end_date.getHours();
        var clocks  = generate_clock(start_hours, start_minutes, end_hours, end_minutes);

        if (slider_max == 0) {
            var text    = jQuery("#total_duration").text();
            var array   = text.split(" ");
            slider_max  = array[2];
        }

        // Converts millisecs to minutes
        var current_start_date_mins = current_start_date.getTime()  / (1000 * 60);
        var current_end_date_mins   = current_end_date.getTime() / (1000 * 60);

        // Calculate start and end position
        var start_min_dif   = current_start_date_mins - start_date_mins;
        var end_min_dif     = current_end_date_mins - start_date_mins;

        // Sets start and end for initial positions
        if (current_start_date_mins <= 0 || current_end_date_mins <= 0) {
            start_min_dif   = 0;
            end_min_dif     = 30;
        }

        var target = jQuery(".slider-range[data-id=\"" + audience_value + "\"][data-type=\"" + audience_type + "\"]");

        target.slider({
            range: true,
            min: 0,
            max: slider_max,
            step: 5,
            values: [start_min_dif, end_min_dif],
            slide: function( event, ui ) {
                slide_event(start_date, end_date, ui.values, "", "", true);
                if (validation) {
                    validate_times();
                }
            }
        });

        switch (audience_type) {
            case "proxy_id":
                state_type = "student";
                break;
            case "cohort":
                state_type = "cohort";
                break;
            case "group_id":
                state_type = "cgroup";
                break;
        }

        // Updates the display
        var clock_html = clocks["start_date_display"] + " - " + clocks["end_date_display"];

        jQuery("#audience_" + state_type + "_" + audience_value).children("span.time").html(clock_html);
        jQuery("#" + audience_type + "_" + "time_" + audience_value).html("<p>" + clock_html + "</p>");

        if (st_unix === new_st_unix && et_unix === new_et_unix) {
            button_state = 0;
        } else {
            button_state = 1;
        }

        // Updates the display of the clock button and settings the setting to use the custom times
        time_on_off(button_state, state_type, audience_value);
    }
}

/*
 * This function is run if the validate overlapping times is on
 * It will check for overlapping times between the groups and highlight rows in red that overlap.
 * Once the row is cleared it will remove the red color
 */
function validate_times() {
    if (validation) {
        // Create array of all times from the 3 arrays
        // Only adds rows where the group is turned ON
        var all_times = [];

        if ((typeof proxy_custom_time_array && proxy_custom_time_array !== "undefined") && proxy_custom_time_array !== null) {
            jQuery.each(proxy_custom_time_array, function (key, obj) {
                if (typeof obj !== "undefined" || obj !== undefined) {
                    if (obj && obj.custom_time != 0) {
                        all_times = all_times.concat(obj);
                    }
                }
            });
        }

        if ((typeof cohorts_custom_time_array && cohorts_custom_time_array !== "undefined") && cohorts_custom_time_array !== null) {
            jQuery.each(cohorts_custom_time_array, function (key, obj) {
                if (typeof obj !== "undefined" || obj !== undefined) {
                    if (obj && obj.custom_time != 0) {
                        all_times = all_times.concat(obj);
                    }
                }
            });
        }

        if ((typeof cgroup_custom_time_array && cgroup_custom_time_array !== "undefined") && cgroup_custom_time_array !== null) {
            jQuery.each(cgroup_custom_time_array, function (key, obj) {
                if (typeof obj !== "undefined" || obj !== undefined) {
                    if (obj && obj.custom_time != 0) {
                        all_times = all_times.concat(obj);
                    }
                }
            });
        }

        // For each item check if start time falls between each other start and end time
        // For each item check if end time falls between each other start and end time

        if (all_times != null) {
            // First set all of them to not overlapping
            jQuery.each(all_times, function (key, obj) {
                if (typeof obj !== "undefined" || obj !== undefined) {
                    obj.overlapping = false;
                }
            });

            // Check for overlapping times
            jQuery.each(all_times, function (key, obj) {
                jQuery.each(all_times, function (key_sub, obj_sub) {
                    if (key === key_sub) {
                        return;
                    }
                    // Checks if the start time is between another start and end time
                    if ((obj.custom_time_start > obj_sub.custom_time_start && obj.custom_time_start < obj_sub.custom_time_end) ||
                        (obj.custom_time_end > obj_sub.custom_time_start && obj.custom_time_end < obj_sub.custom_time_end) ||
                        (obj.custom_time_end === obj_sub.custom_time_end) ||
                        (obj.custom_time_start === obj_sub.custom_time_start)) {
                        // Sets to true for overlapping times.
                        obj.overlapping     = true;
                        obj_sub.overlapping = true;
                    }
                });
            });

            // Set colors for overlapping or not overlapping times
            jQuery.each(all_times, function (key, obj) {
                var slider  = jQuery(".slider-range[data-type=" + obj.audience_type + "][data-id=" + obj.audience_value + "]");
                var li      = jQuery("#audience_list").children("li[data-type=" + obj.audience_type + "][data-value=" + obj.audience_value + "]");

                //time overlaps colors or resets to white
                if (obj.overlapping) {
                    // Highlight row overlapping times
                    slider.parent().parent().stop().animate({ backgroundColor: "#FFB9BC" },500);
                    li.stop().animate({ backgroundColor: "#FFB9BC" },500);

                    // Add marker for invalid time to be checked when slide event, if time is valid then clear marker and change color back
                    slider.data("invalid", "1");
                } else {
                    slider.parent().parent().stop().animate({ backgroundColor: "#FAFAFA" }, 10);
                    li.stop().animate({ backgroundColor: "#FFFFFF" }, 10);

                    slider.data("invalid", "0");
                }
            });
        }
    } else {
        // Clear validation array
        jQuery(".slider-range").parent().parent().animate({ backgroundColor: "#FAFAFA" }, 1);
        jQuery("#audience_list").children("li").animate({ backgroundColor: "#FFFFFF" }, 1);
    }
}

function validate_event_times_changed() {
    // Create array of all times from the 3 arrays
    // Only adds rows where the group is turned ON
    var all_times = [];
    if (proxy_custom_time_array != null) {
        jQuery.each(proxy_custom_time_array, function (key, obj) {
            if (typeof obj !== "undefined" || obj !== undefined) {
                if (obj.custom_time != 0) {
                    all_times = all_times.concat(obj);
                }
            }
        });
    }
    if (cohorts_custom_time_array != null) {
        jQuery.each(cohorts_custom_time_array, function (key, obj) {
            if (typeof obj !== "undefined" || obj !== undefined) {
                if (obj.custom_time != 0) {
                    all_times = all_times.concat(obj);
                }
            }
        });
    }
    if (cgroup_custom_time_array != null) {
        jQuery.each(cgroup_custom_time_array, function (key, obj) {
            if (typeof obj !== "undefined" || obj !== undefined) {
                if (obj.custom_time != 0) {
                    all_times = all_times.concat(obj);
                }
            }
        });
    }


    // Cycles through the events times and checks if start date or end date is passed the time
    if (all_times != null) {
        jQuery.each(all_times, function (key, obj) {
            // Validate obj.custom_time_start and obj.custom_time_end
            var invalid_time = false;
            var time = end_date.getTime() / 1000;
            if (obj.custom_time_start > time || obj.custom_time_end > time) {
                invalid_time = true;
            }

            var slider = jQuery(".slider-range[data-type=" + obj.audience_type + "][data-id=" + obj.audience_value + "]");
            var li = jQuery("#audience_list").children("li[data-type=" + obj.audience_type + "][data-value=" + obj.audience_value + "]");

            if (invalid_time) {
                // Highlight row overlapping times
                slider.parent().parent().stop().animate({ backgroundColor: "#FFB9BC" },500);
                li.stop().animate({ backgroundColor: "#FFB9BC" },500);

                // Add marker for invalid time to be checked when slide event, if time is valid then clear marker and change color back
                slider.data("invalid", "1");
            } else {
                slider.parent().parent().stop().animate({ backgroundColor: "#FAFAFA" }, 10);
                li.stop().animate({ backgroundColor: "#FFFFFF" }, 10);
                slider.data("invalid", "0");
            }
        });
    }
}

// Overrides the default xc2 calendar function on set
function afterSetDateValue(ref_field, target_field, date) {
    update_start_end_times();
}

function event_length_changed() {
    var text  = jQuery("#total_duration").text();
    var array = text.split(" ");
    event_length_minutes = array[2];
    var event_start_date = jQuery("#event_start_date").val();
    var event_start_hour = jQuery("#event_start_hour").val();
    var event_start_min  = jQuery("#event_start_min").val();

    if (event_start_date) {
        var updated_start_date = new Date(event_start_date.replace(/-/g,"/") + " " + event_start_hour + ":" + event_start_min + ":00");
        start_date = updated_start_date;

        // Modify end date, add minutes to start date to get new end date
        end_date = addMinutes(start_date, event_length_minutes);

        // Change start and end display in Event Audience Time Override
        var start_minutes   = start_date.getMinutes();
        var end_minutes     = end_date.getMinutes();
        var start_hours     = start_date.getHours();
        var end_hours       = end_date.getHours();

        var clocks = generate_clock(start_hours, start_minutes, end_hours, end_minutes);

        jQuery(".slider-times .left").text(clocks["start_date_display"]);
        jQuery(".slider-times .right").text(clocks["end_date_display"]);

        // Loops through the array of custom start times
        // This sets the start and end time for dates already set
        // Also changes the snap size for the sliders
        if (proxy_custom_time_array != null) {
            jQuery.each(proxy_custom_time_array, function (key, obj) {
                start_sliders(obj, event_length_minutes);
            });
        }

        if (cohorts_custom_time_array != null) {
            jQuery.each(cohorts_custom_time_array, function (key, obj) {
                start_sliders(obj, event_length_minutes);
            });
        }

        if (cgroup_custom_time_array != null) {
            jQuery.each(cgroup_custom_time_array, function (key, obj) {
                start_sliders(obj, event_length_minutes);
            });
        }

        validate_event_times_changed();
    }
}


// Modifies the start and end date for the granular schedule time if time is changed
function update_start_end_times() {
    if (event_length_minutes) {
        var updated_start_date = new Date(jQuery("#event_start_date").val().replace(/-/g,"/") + " " + jQuery("#event_start_hour").val() + ":" + jQuery("#event_start_min").val() + ":00");
        start_date  = updated_start_date;
        end_date    = addMinutes(start_date, event_length_minutes);

        // Change start and end display in Event Audience Time Override
        var start_minutes   = start_date.getMinutes();
        var end_minutes     = end_date.getMinutes();
        var start_hours     = start_date.getHours();
        var end_hours       = end_date.getHours();

        var clocks = generate_clock(start_hours, start_minutes, end_hours, end_minutes);

        jQuery(".slider-times .left").text(clocks["start_date_display"]);
        jQuery(".slider-times .right").text(clocks["end_date_display"]);

        if (proxy_custom_time_array != null) {
            jQuery.each(proxy_custom_time_array, function (key, obj) {
                if (obj.custom_time != 0) {
                    var audience    = obj.audience_type;
                    var data_value  = obj.audience_value;
                    var slider      = jQuery(".slider-range[data-type=" + audience + "][data-id=" + data_value + "]").slider("values");
                    slide_event(start_date, end_date, slider, data_value, audience, false);
                }
            });
        }
        if (cohorts_custom_time_array != null) {
            jQuery.each(cohorts_custom_time_array, function (key, obj) {
                if (obj.custom_time != 0) {
                    var audience    = obj.audience_type;
                    var data_value  = obj.audience_value;
                    var slider      = jQuery(".slider-range[data-type=" + audience + "][data-id=" + data_value + "]").slider("values");
                    slide_event(start_date, end_date, slider, data_value, audience, false);
                }
            });
        }
        if (cgroup_custom_time_array != null) {
            jQuery.each(cgroup_custom_time_array, function (key, obj) {
                if (obj.custom_time != 0) {
                    var audience    = obj.audience_type;
                    var data_value  = obj.audience_value;
                    var slider      = jQuery(".slider-range[data-type=" + audience + "][data-id=" + data_value + "]").slider("values");
                    slide_event(start_date, end_date, slider, data_value, audience, false);
                }
            });
        }
    }
}
