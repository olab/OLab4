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
 * View class for rendering navigation tabs for assessments forms module.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_NavigationTabs extends Views_Assessments_Forms_Controls_Base {

    protected function validateIsSet(&$options, $primitives_list = array()) {
        return $this->validateIsSet($options, array("has_access"));
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $has_access = $options["has_access"];
        $active     = @$options["active"];
        $exclusions = is_array(@$options["exclusions"]) ? $options["exclusions"] : array();
        ?>
        <div class="no-printing">
            <ul class="nav nav-tabs">
                <?php if ($has_access): ?>
                    <?php if (!in_array("items", $exclusions)): ?>
                        <li <?php echo $active == "items" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_RELATIVE."/admin/assessments/items" ?>"><?php echo $translate->_("Items") ?></a></li>
                    <?php endif; ?>
                    <?php if (!in_array("rubrics", $exclusions)): ?>
                        <li <?php echo $active == "rubrics" ? 'class="active"' : ""; ?>><a href="<?php echo ENTRADA_RELATIVE."/admin/assessments/rubrics" ?>"><?php echo $translate->_("Grouped Items") ?></a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    }
}