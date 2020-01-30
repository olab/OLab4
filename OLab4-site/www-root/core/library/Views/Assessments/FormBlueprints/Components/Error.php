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
 * This view is a generic error renderer. The error text is displayed
 * (along with the view options); this is intended to be used when
 * a form blueprint is misconfigured and is missing item data, in
 * particular, standard item data.
 *
 * Translation/localization is not necessary as this is a developer-facing
 * view that should not be seen by end-users.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_FormBlueprints_Components_Error extends Views_HTML {

    protected function renderView($options = array()) {

        // This is developer-facing debug data. This view simply outputs an error describing what's missing
        $error_text = array_key_exists("error_text", $options) ?
            $options["error_text"] :
            "Cannot render this item. Required data is missing.";
        ?>
        <div class="alert alert-danger">
            <strong><?php echo $error_text; ?></strong>
            <p>Supplied View Data: </p>
            <ul class="padding-bottom">
            <?php foreach ($options as $i => $opt): ?>
                <?php if ($i != "error_text"): ?>
                    <li><?php echo $i ?>: "<?php echo print_r($opt, true) ?>"</li>
                <?php endif; ?>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}