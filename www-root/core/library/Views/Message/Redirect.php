<?php
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
 * This View class renders a simple closeable HTML information blurb
 * and add a header redirect.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Views_Message_Redirect extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("message_type", "redirection_url"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $ONLOAD, $HEAD, $translate;

        $message_type            = $options["message_type"];
        $redirection_url         = $options["redirection_url"];

        $add_redirect_message    = array_key_exists("add_redirect_message", $options) ? $options["add_redirect_message"] : true;
        $add_click_here_message  = array_key_exists("add_click_here_message", $options) ? $options["add_click_here_message"] : true;
        $add_sidebar_begone      = array_key_exists("add_sidebar_begone", $options) ? $options["add_sidebar_begone"] : false;
        $timeout                 = array_key_exists("timeout", $options) ? (int)$options["timeout"] : 5; // 5 second default
        $redirect_to             = array_key_exists("redirect_name", $options) ? $options["redirect_name"] : null;
        $message_text            = array_key_exists("message_text", $options) ? $options["message_text"] : null;
        $disable_set_timeout     = array_key_exists("disable_set_timeout", $options) ? $options["disable_set_timeout"] : false;

        $click_here_message = sprintf($translate->_("Please <a href=\"%s\">click here</a> if you do not wish to wait."), $redirection_url);
        $redirected_message = ($redirect_to) ?
            sprintf($translate->_("You will be redirected to %s in %s seconds."), $redirect_to, $timeout) :
            sprintf($translate->_("You will be redirected in %s seconds."), $timeout);

        $messages = array();

        if (!$disable_set_timeout) {
            $ONLOAD[] = "setTimeout(\"window.location='$redirection_url'\", {$timeout}000);";
        }
        if ($add_sidebar_begone) {
            $HEAD[] = "<script>sidebarBegone();</script>";
        }

        if ($message_text) {
            $messages[] = $message_text;
        }
        if ($add_redirect_message) {
            $messages[] = $redirected_message;
        }
        if ($add_click_here_message && $redirected_message) {
            $messages[] = $click_here_message;
        }

        $concat_messages = implode(" ", $messages);

        switch ($message_type) {
            case "error":
                add_error($concat_messages);
                break;
            case "success":
                add_success($concat_messages);
                break;
            case "notice":
                add_notice($concat_messages);
                break;
            default:
            case "generic":
                add_generic($concat_messages);
                break;
        }
        display_status_messages();
    }
}