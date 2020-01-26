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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 * Allows to display notices at the top or bottom of the screen with a
 * different icon / text color based on the type, and a slide up or down effect.
 *
 * The supported types currently supported are "success" ans "error"
 *
 */

(function ($) {
    'use strict';

    var animated_notifcation_timer1,
        animated_notifcation_timer2,
        animated_notifcation_is_running = false;

    $.animatedNotice = function(notice, notice_type, options) {
        var self = this,
            notice_div,
            wrapper_div,
            content_div;

        var settings = {
            noticeID: "animated-notification",
            noticeClass: "animated-notification",
            noticeLocation:"bottom",                // Either top or bottom
            noticeHeight: "75px",
            showDuration: "300",                    // Duration of the slide up or slide down animation when showing
            hideDuration: "300",                    // Duration of the slide up or slide down animation when hiding
            resourceUrl: "",
            iconPosition: "left",                   // Float position of the icon
            success: {
                iconImg: "/images/animated-notice-success.gif",
                iconDuration: 1500,                 // Time required for the icon animation before displaying the text
                iconWitdh: "50px",
                iconHeight: "50px",
                textColor: "#5bb75b"
            },
            error: {
                iconImg: "/images/animated-notice-error.gif",
                iconDuration: 1,
                iconWitdh: "50px",
                iconHeight: "50px",
                textColor: "#ff0919"
            },
        };

        if (options) {
            settings = $.extend(settings, options);
        }

        if (animated_notifcation_is_running == true) {
            window.clearTimeout(animated_notifcation_timer1);
            window.clearTimeout(animated_notifcation_timer2);
        }

        animated_notifcation_is_running = true;

        // Container div
        if ($("#" + settings.noticeID).length > 0) {
            notice_div = $("#" + settings.noticeID);
        } else {
            var notice_div = $(document.createElement("div")).attr("id", settings.noticeID).addClass(settings.noticeClass).css("display", "none");
            switch (settings.noticeLocation) {
                case 'top':
                    notice_div.css("top", "-" + settings.noticeHeight);
                    break;

                default:
                    notice_div.css("bottom", "-" + settings.noticeHeight);
            }

            $('body').append(notice_div);
        }

        // Wrapper div
        if ($("#" + settings.noticeID + "-content").length > 0) {
            wrapper_div =  $("#" + settings.noticeID + "-wrapper");
        } else {
            wrapper_div = $(document.createElement("div")).attr("id", settings.noticeID + "-wrapper");
            notice_div.append(wrapper_div);
        }

        // Content div
        if ($("#" + settings.noticeID + "-content").length > 0) {
            content_div =  $("#" + settings.noticeID + "-content");
        } else {
            content_div = $(document.createElement("div")).attr("id", settings.noticeID + "-content").css("height", settings.noticeHeight);
            wrapper_div.append(content_div);
        }

        content_div.html("");
        notice_div.show();

        function display_notice(notice_text, msg_config, settings) {
            var img = $(document.createElement("img")).css({
                "width": msg_config.iconWitdh,
                "height": msg_config.iconHeight,
                "float": settings.iconPosition
            });

            content_div.append(img);

            // Need a slight delay before loading the src, as the animation is not alway visible if not
            setTimeout(function() {
                img.attr('src', settings.resourceUrl + msg_config.iconImg);
            }, 3);

            var msg_div = jQuery(document.createElement("div"));
            msg_div.css({
                "opacity": "0",
                "color": msg_config.textColor
            }).addClass(settings.noticeID + "-text");

            msg_div.text(notice_text);

            content_div.append(msg_div);

            animated_notifcation_timer1 = window.setTimeout(function() {
                msg_div.animate({marginLeft:"+=20px", opacity:1}, 500, function() {
                    animated_notifcation_timer2 = window.setTimeout(function() {
                        if (settings.noticeLocation == "bottom") {
                            notice_div.animate({bottom: "-" + settings.noticeHeight}, settings.hideDuration, function () {
                                notice_div.hide();
                                img.remove();
                                animated_notifcation_is_running = false;
                            });
                        } else {
                            notice_div.animate({top: "-" + settings.noticeHeight}, settings.hideDuration, function() {
                                notice_div.hide();
                                img.remove();
                                animated_notifcation_is_running = false;
                            });
                        }
                    }, 2000)
                })
            }, msg_config.iconDuration);
        }

        switch (notice_type) {
            case "error":
                var msg_config = settings.error;
                break;

            default:
                var msg_config = settings.success;
                break;
        }

        if (settings.noticeLocation == "bottom") {
            notice_div.animate({bottom: 0}, settings.showDuration, function() {
                display_notice(notice, msg_config, settings);
            });
        } else {
            notice_div.animate({top: 0}, settings.showDuration, function() {
                display_notice(notice, msg_config, settings);
            });
        }
    }
} (jQuery));
