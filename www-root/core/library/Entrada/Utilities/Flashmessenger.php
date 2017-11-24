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
 * A class to handle one-off messaging between pages.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Flashmessenger extends Entrada_Base {

    public static function addMessage($message, $message_type = "notice", $module = "global") {
        global $_SESSION;
        $_SESSION["flashmessager"][$module][$message_type][] = $message;
        return true;
    }

    public static function getMessages($module = "global", $message_type = NULL, $unset = true) {
        global $_SESSION;
        $messages = array();
        if (isset($_SESSION["flashmessager"])) {
            if (array_key_exists($module, $_SESSION["flashmessager"])) {
                if ($message_type && array_key_exists($message_type, $_SESSION["flashmessager"][$module])) {
                    $messages = $_SESSION["flashmessager"][$module][$message_type];
                } else {
                    $messages = $_SESSION["flashmessager"][$module];
                }
            }
            if (array_key_exists($module, $_SESSION["flashmessager"]) && $unset === true) {
                unset($_SESSION["flashmessager"][$module]);
            }
        }
        return $messages;
    }

    public static function displayMessages($module = "global") {
        $flash_messages = $_SESSION["flashmessager"][$module];
        if (isset($flash_messages) && $flash_messages) {
            foreach ($flash_messages as $message_type => $messages) {
                switch ($message_type) {
                    case "error" :
                        echo display_error($messages);
                        break;
                    case "success" :
                        echo display_success($messages);
                        break;
                    case "notice" :
                    default :
                        echo display_notice($messages);
                        break;
                }
            }
        }
        if (isset($_SESSION["flashmessager"][$module])) {
            unset($_SESSION["flashmessager"][$module]);
        }
    }


} 