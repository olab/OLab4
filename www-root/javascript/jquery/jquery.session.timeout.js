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
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 * The session timeout widget observes user activity by storing a timestamp in local storage
 * whenever the user opens a page that contains the entrada footer. The widget periodically
 * checks the timestamp to determine whether a warning countdown dialog should be displayed.
 * When warning time (defaults to 3 minutes) counts down without user activity the user is logged out.
 *
 * localStorage.auth_session_last_active - a timestamp indicating last user active time.
 * localStorage.auth_timer - the session timeout callback on interval session-time minus the warn-time.
 *
 */
(function($) {
    'use strict';

    $.timeoutMonitor = function(options) {
        var defaults = {
            sessionTime: 900000,   // 15 min
            warnTime: 60000,       // 60 seconds
            title: 'Your session will expire',
            message: 'Your session will expire in %%timeleft%%. Any information entered will be lost.<br /><br />Do you want to extend your session?',
            extendBtn: 'Yes, Extend my session',
            logoutBtn: 'No, Log me out',
            keepAliveURL: '/index.php',
            keepAliveData: '',
            logoutURL: '/?action=logout'
        }

        var opt = defaults,
            timer,
            countdown = {};

        // Extend user-set options over defaults
        if (options) {
            opt = $.extend(defaults, options);
        }

        /**
         * Create the modal box
         */
        $('body').append('<div id="session-timeout-modal" class="modal hide fade responsive-modal" tabindex="-1" role="dialog" aria-labelledby="session-timeout-modal-title" aria-hidden="true"> \
                <div class="modal-header"> \
                    <h3 id="session-timeout-modal-title">' + opt.title + '</h3> \
                </div> \
                <div class="modal-body"> \
                </div> \
                <div id="session-timeout-modal-footer" class="modal-footer"> \
                    <button type="button" id="btn-timeout-logout" class="btn">'+opt.logoutBtn+'</button> \
                    <button type="button" id="btn-timeout-keep-alive" class="btn btn-primary pull-right">' + opt.extendBtn + '</button> \
                </div> \
            </div>');

        /*
         * Show the next User Disclaimer after one is hidden
         * */
        $("#session-timeout-modal").on("hidden", function() {
            localStorage.auth_session_last_active = Date.now();
            $('.modal-backdrop:last').remove();
        });

        // Logout button
        $('#btn-timeout-logout').on("click", function() {
            doLogOut();
        });

        // Extend session button
        $('#btn-timeout-keep-alive').on("click", function() {
            jQuery("#session-timeout-modal").modal("hide");
        });

        clearStorage();

        // Set last active to current time.
        var currentTime = Date.now();
        localStorage.auth_session_last_active = currentTime;

        // Start the session timeout.
        timer = setTimeout(sessionTimeout, (opt.sessionTime - opt.warnTime));

        /**
         * Session timeout callback.
         * The callback checks the user's last activity time and initiates logout
         * or warning countdown as required.
         */
        function sessionTimeout() {
            clearTimeout(timer);

            var lastActive = getLocalLastActive(),
                timeForWarning = 0,
                timeForLogout = lastActive + opt.sessionTime,
                currentTime = Date.now();

            if (currentTime >= timeForLogout) {
                // Get the session last active.
                var sessionLastActive = getSessionLastActive();
                if (sessionLastActive > lastActive) {
                    lastActive = sessionLastActive;
                }
            }

            timeForWarning = lastActive + opt.sessionTime - opt.warnTime;
            timeForLogout = lastActive + opt.sessionTime;

            if (currentTime >= timeForLogout) {
                doLogOut();

            } else if (currentTime >= timeForWarning) {
                doCountdown(timeForLogout - currentTime);
                timer = setTimeout(sessionTimeout, 1000);

            } else {
                // Reset the session time out as required.
                timer = setTimeout(sessionTimeout, timeForWarning - currentTime);
                $('#session-timeout-modal').modal('hide');
            }
        }


        /**
         * Returns the last active time from local storage, or 0
         * if not a number.
         */
        function getLocalLastActive() {
            var lastActive = parseInt(localStorage.auth_session_last_active);

            if (isNaN(lastActive)) {
                return 0;
            }

            return lastActive;
        }

        /**
         * Returns the last active time from the session, or 0 if undefined or failed request.
         */
        function getSessionLastActive() {

            var lastActive = 0;

            $.ajax({
                type: 'GET',
                url: opt.keepAliveURL,
                data: opt.keepAliveData,
                success: function(data) {
                    if (typeof data !== undefined) {
                        $('#session-timeout-modal').modal('hide');
                    } else {
                        doLogOut();
                    }
                },
                async: false
            });

            return lastActive;
        }

        /**
         * Logs the user out at the end of the count down, unless the user engaged with the portal.
         */
        function doLogOut() {
            clearStorage();
            window.location = opt.logoutURL;
        }

        /* Remove all the localStorage variables*/

        function clearStorage() {
            clearTimeout(timer);
            localStorage.removeItem('auth_session_last_active');
        }

        /**
         * Manage the countdown to the redirection for the dialog.
         *
         * @param timeleft
         */
        function doCountdown(timeLeft) {
            var timeLeftSeconds = Math.floor(timeLeft / 1000);
            var timeLeftMinutes = Math.floor(timeLeftSeconds / 60);
            var timeLeftSecPart = timeLeftSeconds - (timeLeftMinutes * 60);

            var minutesStr = timeLeftMinutes ? timeLeftMinutes + ' minutes and ' : '';

            $('#session-timeout-modal .modal-body').html(opt.message.replace('%%timeleft%%', minutesStr + timeLeftSecPart + ' seconds'));
            $('#session-timeout-modal').modal("show");
            $('.modal-backdrop:last').css('z-index', 999);
        }
    };
})(jQuery);
