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
        $('body').append('<div id="session-timeout-modal" class="modal hide fade"> \
                <div class="modal-header"> \
                    <h3 id="session-timeout-title">' + opt.title + '</h3> \
                </div> \
                <div class="modal-body"> \
                </div> \
                <div id="event-modal-footer"> \
                    <button type="button" id="btn-timeout-logout" class="btn">'+opt.logoutBtn+'</button> \
                    <button type="button" id="btn-timeout-keep-alive" class="btn btn-primary pull-right">' + opt.extendBtn + '</button> \
                </div> \
            </div>');

        // Logout button
        $('#btn-timeout-logout').on("click", function() {
            clearStorage();
            window.location = opt.logoutURL;
        });

        // Extend session button
        $('#btn-timeout-keep-alive').on("click", function() {
            extendSession();
        });

        /**
         * Function that ping the specified url to keep the session alive.
         */
        function keepAlive() {
            $.ajax({
                type: 'GET',
                url: opt.keepAliveUrl,
                data: opt.keepAliveData,
                success: function(data) {
                    startSessionTimer();
                    $('#session-timeout-modal').modal('hide');
                }
            });
        }

        /**
         * Extend the session and close the dialogue
         */
        function extendSession() {
            keepAlive();
        }

        /**
         * Start the timeout before showing the warning dialog
         */
        function startSessionTimer() {
            clearStorage();
            localStorage.auth_session_state = 1;
            localStorage.auth_timer = setTimeout(function() {
                if (opt.warnTime != undefined) {
                    $('#session-timeout-modal .modal-body').html(opt.message.replace('%%timeleft%%', (opt.warnTime > 60 ? Math.ceil(opt.warnTime/60) + ' minutes' : opt.warnTime + ' seconds')));
                }
                $('#session-timeout-modal').modal('show');
                startDialogTimer();
            }, (opt.sessionTime - opt.warnTime));
        }

        /**
         * Start the timeout
         */
        function startDialogTimer() {
            clearTimeout(localStorage.auth_timer);
            doCountdown(true);
        }

        /* Remove all the localStorage variables*/

        function clearStorage() {
            clearTimeout(localStorage.auth_timer);
            clearTimeout(localStorage.auth_logout_timer);
            clearTimeout(countdown.timer);
            localStorage.removeItem('auth_session_state');
            localStorage.removeItem('auth_timer');
            localStorage.removeItem('auth_logout_timer');
            localStorage.removeItem('auth_countdown_timer');
            localStorage.removeItem('auth_timeLeft');
        }

        /**
         * Manage the countdown to the redirection for the dialog. Redirection is handled
         * by startDialogueTimer()
         *
         * @param reset
         */
        function doCountdown(reset) {
            clearTimeout(countdown.timer);

            if (reset && (localStorage.auth_session_state == 1)) {
                localStorage.auth_timeLeft = Math.floor(opt.warnTime / 1000);
                localStorage.auth_session_state = 0;
            } else {
                if (localStorage.auth_session_state == 1) {
                    extendSession();
                } else if (!localStorage.auth_session_state) {
                    clearStorage();
                    window.location = opt.logoutURL;
                } else if (localStorage.auth_timeLeft > 0) {
                    localStorage.auth_timeLeft = localStorage.auth_timeLeft - 1;
                } else {
                    clearStorage();
                    window.location = opt.logoutURL;
                }
            }

            // Update the dialog
            if (localStorage.auth_timeLeft != undefined) {
                $('#session-timeout-modal .modal-body').html(opt.message.replace('%%timeleft%%', (localStorage.auth_timeLeft > 60 ? Math.ceil(localStorage.auth_timeLeft/60) + ' minutes' : localStorage.auth_timeLeft + ' seconds')));
            }

            countdown.timer = setTimeout(function() {
                doCountdown(false);
            }, 1000);
        }
        startSessionTimer();
    };
})(jQuery);
