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

//This file controls the javascript functions for the ICS subscribe/download

//switcher between all and individual course based
//hides download for all calendars
function course_switcher(e, type) {
    jQuery("div#dashboard_ics_calendar .content-calendar #all-course .btn").removeClass('active');
    jQuery(e).addClass('active');
    if (type == 'all') {
        jQuery("div#dashboard_ics_calendar .content-calendar #course-selector").hide();
        jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download .btn[data-type='download']").hide();
        jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download").removeClass('btn-group');
        download_switcher(jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download .btn[data-type='subscribe']"), 'subscribe');
    }

    if (type == 'course') {
        jQuery("div#dashboard_ics_calendar .content-calendar #course-selector").show();
        jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download .btn[data-type='download']").show();
        jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download").addClass('btn-group');
    }
}
//switcher between subscribe and download
//subscribe has a text box and the blue subscirbe button which will open an app like Outlook or Apple Calendar
//download just shows a url btn
function download_switcher(e, type) {
    jQuery("div#dashboard_ics_calendar .content-calendar #subscribe-download .btn").removeClass('active');
    jQuery(e).addClass('active');
    if (type == 'subscribe') {
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe").show();
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download").hide();
    }

    if (type == 'download') {
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe").hide();
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download").show();
    }
}

function show_hide_calendar_ics() {
    if (jQuery('div#dashboard_ics_calendar').hasClass('hidden')) {
        jQuery('div#dashboard_ics_calendar').removeClass('hidden').addClass('visable').slideDown('fast');
        jQuery('div#dashboard_ics_calendar .content-calendar #close').removeClass('hidden').addClass('visable').slideDown('fast');
    } else {
        jQuery('div#dashboard_ics_calendar').removeClass('visable').addClass('hidden').slideUp('fast');
        jQuery('div#dashboard_ics_calendar .content-calendar #close').removeClass('hidden').addClass('visable').slideUp('fast');
    }
}

function update_html_ics(calendar_http_url, calendar_webcal_url, type, show) {
    if (type == 'course') {
        var course_selected = jQuery("div#dashboard_ics_calendar .content-calendar #course-quick-select").val();

        var html_sub = '<span id="calendar-link-wrapper"></span>\n'+
                        '<a class="btn btn-small" href="javascript:showCalendarLink(\'' + calendar_http_url + '&course=' + course_selected + '\')" id="copy-link"><i class="icon-link"></i> Copy Subscription URL</a>\n'+
                        '<a class="btn btn-info btn-small" href="' + calendar_webcal_url + '&course=' + course_selected + '" id="subscribe-calendar-btn"><i class="icon-calendar icon-white"></i> Subscribe to Calendar</a>\n';

        var html_download = '<a class="btn btn-info btn-small" href="' + calendar_http_url + '&course=' + course_selected + '"><i class="icon-calendar icon-white"></i> Download Calendar</a>\n';

        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe .span10").html(html_sub);
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download .span10").html(html_download);
        
        if (show) {
            jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe .span10").show();
            jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download .span10").show();
        }
    } else {
        var course_selected = jQuery("div#dashboard_ics_calendar .content-calendar #course-quick-select").val();

        var html_sub = '<span id="calendar-link-wrapper"></span>\n'+
                        '<a class="btn btn-small" href="javascript:showCalendarLink(\'' + calendar_http_url + '\')" id="copy-link"><i class="icon-link"></i> Copy Subscription URL</a>\n'+
                        '<a class="btn btn-info btn-small" href="' + calendar_webcal_url + '" id="subscribe-calendar-btn"><i class="icon-calendar icon-white"></i> Subscribe to Calendar</a>\n';

        var html_download = '<a class="btn btn-info btn-small" href="' + calendar_http_url + '"><i class="icon-calendar icon-white"></i> Download Calendar</a>\n';

        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe .span10").html(html_sub);
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download .span10").html(html_download);        

        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-subscribe .span10").show();
        jQuery("div#dashboard_ics_calendar .content-calendar #calendar-download .span10").show();
        
    }
}