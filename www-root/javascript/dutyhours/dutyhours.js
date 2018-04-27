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
 * This file displays the edit entry interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

var year = new Date().getFullYear();
var month = new Date().getMonth();
var day = new Date().getDate();

jQuery(document).ready(function () {
    // clear and hide the message area
    jQuery("#message-text").html("");
    jQuery("#message-box").hide();

    // valid message types (alert-error, alert-notice, alert-success)
    function showMessage(messageType, message) {
        jQuery("#message-text").html(message);
        jQuery("#message-box")
            .removeClass("alert-success alert-error alert-notice")
            .addClass(messageType)
            .show();
    }

    if (jQuery("#duty_hours_calendar").length > 0) {
        displayCalendar();
    }

    function displayCalendar() {
        if ((parseInt(COURSE_ID) > 0) && (parseInt(CPERIOD_ID) > 0)) {
            jQuery.ajax({
                type: "POST",
                data: {
                    action: "getstudenthours",
                    student_id: USER_ID,
                    query_type: "course",
                    course_id: COURSE_ID,
                    cperiod_id: CPERIOD_ID
                },
                url: ENTRADA_URL + "/clerkship/dutyhours?section=api-duty-hours",
                success: function (data) {
                    var jsonResponse = JSON.parse(data);

                    // Show and hide the notice indicating 0 logged hours for this user/rotation.
                    if (jsonResponse.count < 1) {
                        showMessage(
                            "alert-notice",
                            "Notice: There are no duty hours logged for the selected rotation."
                        );
                    } else {
                        processDates(jsonResponse.data);
                    }
                }
            });
        }
    }

    /**
     * Prepare the dates in the data object for consumption by the jQuery Calendar.
     */
    function processDates(data) {
        var array = [];
        if (typeof data !== "undefined" && (typeof data !== null && typeof data === "object" && data)) {
            for (var i = 0; i < data.length; i++) {
                if (typeof data[i] !== "undefined") {
                    var eventObj = {};

                    // ua-msf : Case 2477 : hours only reported for on_duty
                    if (data[i].hours_type === "off_duty") {
                        eventObj.title = "OFF DUTY";
                        eventObj.textColor = "#990000";
                    } else if (data[i].hours_type === "absence") {
                        eventObj.title = "ABSENCE";
                        eventObj.textColor = "#990000";
                    } else {
                        eventObj.title = data[i].hours + "h";
                        eventObj.textColor = "#009900";
                    }

                    eventObj.start = new Date(parseInt(data[i].encounter_date) * 1000);
                    // ua-msf : Case 2248 : remove start time from calendar display
                    eventObj.allDay = true;
                    eventObj.entry_id = data[i].dhentry_id;
                    eventObj.comments = (data[i].comments != null) ? data[i].comments : "";

                    array.push(eventObj);
                }
            }

            // Display the Calendar
            showCalendar(array);
        } else {
            showCalendar(array);
            showMessage("alert-error", "No results found. Please log hours");
        }
        return false;
    }


    /**
     * showCalendar - Generate, populate, and display the calendar and events list IF we have selected a rotation.
     *
     * @param events
     */
    function showCalendar(events) {
        if (COURSE_ID > 0) {
            jQuery("#duty_hours_calendar")
                .fullCalendar("destroy")
                .fullCalendar({
                    dateFormat: "M d, Y",
                    height: "auto",
                    defaultView: "month",
                    events: events,
                    fixedWeekCount: false,
                    aspectRatio: 3,
                    eventColor: "transparent"
                });
        }
    }

    /*
     * ua-msf : Case 2477 : Hide hours for off_duty and absence entries
     */
    function showOrHideHours(hours_type) {
        if (hours_type === "on_duty") {
            jQuery("#hours-control-group").show();
        } else {
            jQuery("#hours-control-group").hide();
        }
    }

    // hide/show when hours_type changes
    var hoursTypeInput = jQuery("input[name='hours_type']");
    hoursTypeInput
        .change(function () {
            showOrHideHours(hoursTypeInput.filter(":checked").val());
        })
        .change(); // force change on load

    /*
     * Click on DELETE button
     */
    jQuery("#duty_hours_delete").on("click", function () {
        var entry_id = jQuery("#entry_id").val();

        if (parseInt(entry_id) > 0) {
            var r = confirm("This will deactivate the event. \n Do you want to continue?");

            if (r === true) {
                deactivateEvent(entry_id);
            }
        }
    });

    /*
     * Click on SAVE button
     */
    jQuery("#duty_hours_save").on("click", function () {
        var entryId = jQuery("#entry_id").val();
        jQuery.ajax({
            type: "POST",
            data: {
                action: (entryId > 0) ? "updaterecord" : "insertrecord",
                entry_id: entryId,
                active: 1,
                student_id: jQuery("#student_id").val(),
                course_id: jQuery("#course_id").val(),
                cperiod_id: jQuery("#cperiod_id").val(),
                encounter_date: jQuery("#encounter_date").val(),
                encounter_time: jQuery("#encounter_time").val(),
                hours: jQuery("#hours").val(),
                hours_type: jQuery("input[name=hours_type]:checked").val(),
                comments: jQuery("#comments").val()
            },
            url: ENTRADA_URL + "/clerkship/dutyhours?section=api-duty-hours",
            success: function (data) {
                var jsonResponse = JSON.parse(data);

                // Did validation fail? Show notice in the db_notice panel.
                if (jsonResponse.fail_message) {
                    showMessage("alert-error", jsonResponse.fail_message);
                } else {
                    displaySuccessAndRedirect(
                        "Your duty hours were successfully logged for " +
                        moment(encounter_date).format("YYYY-MM-DD").toString() + "."
                    );
                }
            }
        });

    });

    /*
     * Add a success comment to the message box with a timed redirect to the rotations page
     */
    function displaySuccessAndRedirect(message) {
        var redirectMsg = " You will be automatically redirected to the " +
            "rotation log entries list in 5 seconds, or you can " +
            "<a href=\"" + ENTRADA_URL + "/clerkship/dutyhours/rotation?id=" + jQuery("#course_id").val() +
            "&cperiod_id=" + jQuery("#cperiod_id").val() + "\"" +
            ">click here</a> if you do not wish to wait.";
        showMessage("alert-success", message + "\n" + redirectMsg);
        jQuery("#entry_form_controls").hide();
        setTimeout(function () {
            window.location = ENTRADA_URL + "/clerkship/dutyhours/rotation?id=" + jQuery("#course_id").val() +
                "&cperiod_id=" + jQuery("#cperiod_id").val();
        }, 5000);
    }

    /*
     * Only allow numbers and a decimal point in the hours field
     *
     * key codes:
     * 48-57 (numbers on normal keyboard), 96-105 (number pad numbers)
     * 8 (backspace), 9 (tab), 37 (left arrow), 39 (right arrow),
     * 46 (delete), 190 (period on keyboard), 110 (decimal on number pad)
     *
     */
    jQuery("input.numbers-only").keydown(function (event) {
        if (event.shiftKey === true) {
            event.preventDefault();
        }

        if ((event.keyCode >= 48 && event.keyCode <= 57) ||
            (event.keyCode >= 96 && event.keyCode <= 105) ||
            event.keyCode === 8 || event.keyCode === 9 || event.keyCode === 37 ||
            event.keyCode === 39 || event.keyCode === 46 || event.keyCode === 190 || event.keyCode === 110) {
            // nothing to do these are allowed
        } else {
            event.preventDefault();
        }

        if (jQuery(this).val().indexOf(".") !== -1 && (event.keyCode === 190 || event.keyCode === 110)) {
            event.preventDefault();
        }
        //if a decimal has been added, disable the "."-button

    });
});

