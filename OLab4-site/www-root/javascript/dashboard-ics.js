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
 * This is the main dashboard that people see when they log into Entrada
 * and have not requested another page or module.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 *
*/

jQuery(document).ready(function($) {
    var dashboard_isc = jQuery("div#dashboard_ics_calendar .content-calendar");
    jQuery(dashboard_isc).find("#subscribe-download").removeClass('btn-group');

    //hides subscribe versus Download till a course is chosen so they can't download the all link.
    jQuery("#calendar-ics-btn").on("click", function () {
        show_hide_calendar_ics();
    });

    jQuery(dashboard_isc).find("#close").on("click", function () {
        show_hide_calendar_ics();
    });

    jQuery(dashboard_isc).find("#all-course .btn").click(function () {
        jQuery(dashboard_isc).find("#calendar-subscribe .span10").hide();
        jQuery(dashboard_isc).find("#calendar-download .span10").hide();
        //hides the course download/subscribe buttons if no course is set
        if (!jQuery(dashboard_isc).find("#course-quick-select").val() == "") {
            update_html_ics(calendar_http_url, calendar_webcal_url, jQuery(this).data("type"), true);
        } else {
            update_html_ics(calendar_http_url, calendar_webcal_url, jQuery(this).data("type"), false);
        }
        course_switcher(this, jQuery(this).data("type"));
    });

    jQuery(dashboard_isc).find("#subscribe-download .btn").on("click", function () {
        download_switcher(this, jQuery(this).data("type"));
    });

    jQuery(dashboard_isc).find("#course-quick-select").on("change", function () {
        update_html_ics(calendar_http_url, calendar_webcal_url, "course", true);
    });

    $("a[data-toggle=\"tab\"]").on("shown", function (e) {
        if ($(e.target).attr("href") == "#rotation-calendar") {
            $(".rotation-schedule").fullCalendar("render");
        }
    });

    $("#calendar-subscribe").on("click", ".copy-link", function () {
        var clicked = $(this);
        var url = $(clicked).data("url");
        var use_course = $(clicked).data("use_course");
        if (use_course) {
            var course = $(clicked).data("course-selected");
            showCalendarLink(url + "&course=" + course);
        } else {
            showCalendarLink(url);
        }
    });
});

// This file controls the javascript functions for the ICS subscribe/download

// switcher between all and individual course based
// hides download for all calendars
function course_switcher(e, type) {
    var dashboard_isc = jQuery("div#dashboard_ics_calendar .content-calendar");

    jQuery(dashboard_isc).find("#all-course .btn").removeClass("active");
    jQuery(e).addClass("active");

    if (type == "all") {
        jQuery(dashboard_isc).find("#course-selector").hide();
        jQuery(dashboard_isc).find("#subscribe-download .btn[data-type=\"download\"]").hide();
        jQuery(dashboard_isc).find("#subscribe-download").removeClass("btn-group");
        download_switcher(jQuery(dashboard_isc).find("#subscribe-download .btn[data-type=\"subscribe\"]"), "subscribe");
    } else if (type == "course") {
        jQuery(dashboard_isc).find("#course-selector").show();
        jQuery(dashboard_isc).find("#subscribe-download .btn[data-type=\"download\"]").show();
        jQuery(dashboard_isc).find("#subscribe-download").addClass("btn-group");
    }
}

// switcher between subscribe and download
// subscribe has a text box and the blue subscribe button which will open an app like Outlook or Apple Calendar
// download just shows a url btn
function download_switcher(e, type) {
    var dashboard_isc = jQuery("div#dashboard_ics_calendar .content-calendar");

    jQuery(dashboard_isc).find("#subscribe-download .btn").removeClass("active");
    jQuery(e).addClass("active");
    if (type == "subscribe") {
        jQuery(dashboard_isc).find("#calendar-subscribe").show();
        jQuery(dashboard_isc).find("#calendar-download").hide();
    } else if (type == "download") {
        jQuery(dashboard_isc).find("#calendar-subscribe").hide();
        jQuery(dashboard_isc).find("#calendar-download").show();
    }
}

function show_hide_calendar_ics() {
    var dashboard_isc = jQuery("div#dashboard_ics_calendar");

    if (jQuery(dashboard_isc).hasClass("hidden")) {
        jQuery(dashboard_isc).removeClass("hidden").addClass("visible").slideDown("fast");
        jQuery(dashboard_isc).find(".content-calendar #close").removeClass("hidden").addClass("visible").slideDown("fast");
    } else {
        jQuery(dashboard_isc).removeClass("visible").addClass("hidden").slideUp("fast");
        jQuery(dashboard_isc).find(".content-calendar #close").removeClass("hidden").addClass("visible").slideUp("fast");
    }
}

function update_html_ics(calendar_http_url, calendar_webcal_url, type, show) {
    var dashboard_isc = jQuery("div#dashboard_ics_calendar .content-calendar");
    var course_selected;
    var html_sub;
    var html_download;
    if (type == "course") {
        course_selected = jQuery(dashboard_isc).find("#course-quick-select").val();

        html_sub = "<span id=\"calendar-link-wrapper\"></span>\n";
        //  href="javascript:showCalendarLink(\"" + calendar_http_url + "&course=" + course_selected + "\")"
        html_sub += "<a class=\"btn btn-small copy-link\" data-course-selected=\"" + course_selected + "\" data-url=\"" + calendar_http_url + "\" data-use_course=\"1\">\n";
        html_sub += "<i class=\"icon-link\"></i> Copy Subscription URL</a>\n";
        html_sub += "<a class=\"btn btn-info btn-small\" href=\"" + calendar_webcal_url + "&course=" + course_selected + "\" id=\"subscribe-calendar-btn\">\n";
        html_sub += "<i class=\"icon-calendar icon-white\"></i> Subscribe to Calendar</a>\n";

        html_download = "<a class=\"btn btn-info btn-small\" href=\"" + calendar_http_url + "&course=" + course_selected + "\"><i class=\"icon-calendar icon-white\"></i> Download Calendar</a>\n";

        jQuery(dashboard_isc).find("#calendar-subscribe .span10").html(html_sub);
        jQuery(dashboard_isc).find("#calendar-download .span10").html(html_download);
        
        if (show) {
            jQuery(dashboard_isc).find("#calendar-subscribe .span10").show();
            jQuery(dashboard_isc).find("#calendar-download .span10").show();
        }
    } else {
        //course_selected = jQuery(dashboard_isc).find("#course-quick-select").val();

        // href=\"javascript:showCalendarLink(\"" + calendar_http_url + "\")\"

        html_sub =  "<span id=\"calendar-link-wrapper\"></span>\n";
        html_sub += "<a class=\"btn btn-small copy-link\"  data-url=\"" + calendar_http_url + "\" data-use_course=\"0\">\n";
        html_sub += "<i class=\"icon-link\"></i> Copy Subscription URL</a>\n";
        html_sub += "<a class=\"btn btn-info btn-small\" href=\"" + calendar_webcal_url + "\" id=\"subscribe-calendar-btn\"><i class=\"icon-calendar icon-white\"></i> Subscribe to Calendar</a>\n";

        html_download = "<a class=\"btn btn-info btn-small\" href=\"" + calendar_http_url + "\"><i class=\"icon-calendar icon-white\"></i> Download Calendar</a>\n";

        jQuery(dashboard_isc).find("#calendar-subscribe .span10").html(html_sub);
        jQuery(dashboard_isc).find("#calendar-download .span10").html(html_download);

        jQuery(dashboard_isc).find("#calendar-subscribe .span10").show();
        jQuery(dashboard_isc).find("#calendar-download .span10").show();
    }
}

function showCalendarLink(link) {
    jQuery("#calendar-link-wrapper").html("<input id=\"calendar-link-input\" style=\"margin-bottom:0;\" type=\"text\" value=\"" + link + "\" />");
    jQuery("#calendar-link-input").select();
}