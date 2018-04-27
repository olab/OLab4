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
 * View class for rendering a user card.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_User_Card extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet(
                $options,
                array(
                    "proxy_id",
                    "number",
                    "firstname",
                    "lastname",
                    "email"
                )
            )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Generate the html for user card
     * @param array $options
     */
    protected function renderView($options = array()) {

        // Ensure /css/views/user/card.css is included.
        global $HEAD;
        $common_css = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/views/user/card.css?release=".APPLICATION_VERSION."\" />";
        $included = false;
        foreach ($HEAD as $inclusion) {
            if ($inclusion == $common_css) {
                $included = true;
            }
        }
        if (!$included) {
            $HEAD[] = $common_css;
        }

        // Optional.
        $id = array_key_exists("id", $options) ? $options["id"] : null;
        $class = array_key_exists("class", $options) && $options["class"] ? $options["class"] : "user-card";
        $image_class = array_key_exists("image_class", $options) ? $options["image_class"] : "img-circle";
        $action_label = array_key_exists("action_label", $options) ? $options["action_label"] : null;
        $action_url = array_key_exists("action_url", $options) ? $options["action_url"] : null;
        $role = array_key_exists("role", $options) ? $options["role"] : null;
        $full_width = array_key_exists("full_width", $options) ? true : false;

        // Cache photo data before calling this view if you want to pull user images.
        $cache = new Entrada_Utilities_Cache();
        $image_data = $cache->loadCache($options["proxy_id"]);
        if ($image_data === false) {
            $image_data = $cache->loadCache("default_photo");
        }
        $mime_type = $image_data["mime_type"];
        $encoded_image = $image_data["photo"];
        ?>
        <div class="<?php echo $full_width ? "full-width no-padding space-below " : " " ?><?php echo $class; ?>"<?php echo ($id ? " id=\"{$id}\"" : ""); ?>>
            <div class="<?php echo $class; ?>-wrapper">

                <div class="<?php echo $class; ?>-data-container">
                    <img src="<?php echo "data:{$mime_type};base64,{$encoded_image}"; ?>"
                         class="<?php echo $image_class; ?>"
                    />
                    <h3>
                        <?php echo html_encode($options["lastname"]) . ", " . html_encode($options["firstname"]);
                        if ($options["number"]): ?>
                        <span><?php echo html_encode($options["number"]); ?></span>
                        <?php endif; ?>
                    </h3>
                    <a href="mailto:<?php echo html_encode($options["email"]); ?>"><?php echo html_encode($options["email"]); ?></a>
                    <?php if ($role): ?>
                        <span><?php echo html_encode($options["role"]); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($action_label && $action_url): ?>
                <div class="<?php echo $class; ?>-parent">
                    <div class="<?php echo $class; ?>-child">
                        <a class="user-action-link" href="<?php echo $action_url; ?>"><?php echo $action_label; ?> &rtrif;</a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php if ($full_width): ?>
            <div class="clearfix"></div>
        <?php endif;
    }

}