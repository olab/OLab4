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
 * View class for rendering navigation tabs for the dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Dashboard_NavigationTabs extends Views_Assessments_Forms_Controls_Base {
    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $active = array_key_exists("active", $options) ? $options["active"] : "";
        $role = array_key_exists("role", $options) ? $options["role"] : null;
        $group = array_key_exists("group", $options) ? $options["group"] : null;
        ?>
        <ul class="nav nav-tabs">
            <li <?php echo $active == "dashboard" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL . '/admin/assessments'; ?>"><?php echo $translate->_("Dashboard"); ?></a></li>
            <li <?php echo $active == "distributions" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL . '/admin/assessments/distributions'; ?>"><?php echo $translate->_("Distributions"); ?></a></li>
            <li <?php echo $active == "forms" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL .  '/admin/assessments/forms'; ?>"><?php echo $translate->_("Forms"); ?></a></li>
            <li <?php echo $active == "blueprints" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL .  '/admin/assessments/blueprints'; ?>"><?php echo $translate->_("Form Templates"); ?></a></li>
            <li <?php echo $active == "items" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL .  '/admin/assessments/items'; ?>"><?php echo $translate->_("Items"); ?></a></li>
            <?php if ($group == "medtech" && $role == "admin"): ?>
                <li <?php echo $active == "scales" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_URL .  '/admin/assessments/scales'; ?>"><?php echo $translate->_("Scales"); ?></a></li>
            <?php endif; ?>
        </ul>
        <?php
    }
}