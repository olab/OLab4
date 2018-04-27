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
 * View class for rendering a list of Views_User_Card
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_User_Card_List extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet(
                $options,
                array(
                    "list_title",
                    "users"
                )
            )
        ) {
            return false;
        }
        if (!$this->validateArray($options, array("users"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        // Overall list options.
        $id = array_key_exists("id", $options) ? $options["id"] : null;
        $class = array_key_exists("class", $options) ? $options["class"] : null;
        $users = array_key_exists("users", $options) ? $options["users"] : array();

        // Card specific options.
        $card_class = array_key_exists("card_class", $options) && $options["class"] ? $options["card_class"] : null;
        $action_label = array_key_exists("action_label", $options) ? $translate->_($options["action_label"]) : null;
        $action_url = array_key_exists("action_url", $options) ? $options["action_url"] : null;
        $no_results = array_key_exists("no_results_label", $options) ? $options["no_results_label"] : $translate->_("No users found");

        ?>
        <h2 class="inline-title"><?php echo $translate->_($options["list_title"]); ?></h2>

        <div<?php echo ($id ? " id=\"{$id}\"" : "") . ($class ? " class=\"{$class}\"" : ""); ?>>
        <?php if (!empty($users)):
            foreach ($users as $user) :
                $user["id"] = $user["proxy_id"];
                $user["class"] = $card_class;
                $user["action_label"] = $action_label;
                $user["action_url"] = $action_url . $user["id"];
                $card = new Views_User_Card();
                $card->render($user);
            endforeach;
        else: ?>
            <h3><?php echo html_encode($no_results); ?></h3>
        <?php endif; ?>
        </div>
        <?php
    }

}