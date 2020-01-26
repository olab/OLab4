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
 * Admin-facing objective set selector renderer.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Objectives_AdminObjectiveSetSelector extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("afelement_id", "objective_id"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $objective_id = $options["objective_id"];
        $afelement_id = $options["afelement_id"];
        $objectives = @$options["objectives"] ? $options["objectives"] : array();

        $html[] = "<div class='form-item' data-afelement-id='{$afelement_id}'>";
        $html[] = "<div class=\"item-container\">";
        $html[] =    "<table class=\"item-table\">";
        $html[] =        "<tr class=\"type\">";
        $html[] =            "<td class=\"type\">";
        $html[] =                "<span class=\"item-type\">" . $translate->_("Curriculum Tag Set") . "</span>";
        $html[] =                    "<div class=\"pull-right\">";
        $html[] =                        "<div class=\"btn-group\">";
        $html[] =                            "<a class=\"btn save-objective\" data-element-id=\"{$afelement_id}\">". $translate->_("Save") ."</a>";
        $html[] =                            "<span class=\"btn\">";
        $html[] =                                "<input type=\"checkbox\" value=\"". html_encode($afelement_id) ."\" name=\"delete[]\" class=\"delete\" />";
        $html[] =                            "</span>";
        $html[] =                            "<a title=\"Move\" class=\"btn move\"><i class=\"icon-move\"></i></a>";
        $html[] =                        "</div>";
        $html[] =                    "</div>";
        $html[] =            "</td>";
        $html[] =        "</tr>";
        $html[] =        "<tr class=\"heading\">";
        $html[] =            "<td>";
        $html[] =                "<h3>". $translate->_("Select a Curriculum Tag Set") ."</h3>";
        $html[] =            "</td>";
        $html[] =        "</tr>";
        $html[] =        "<tr class=\"item-response-view\">";
        $html[] =            "<td class=\"item-type-control\">";
        $html[] =                "<div id=\"element-". html_encode($afelement_id) ."\" data-element-id=\"". html_encode($afelement_id) ."\">";
        if ($objectives) {
            foreach ($objectives as $objective) {
                $html[] =                        "<label class=\"radio form-item-objective-label\">";
                $html[] =                            "<input type=\"radio\" name=\"form_item_objective_". html_encode($afelement_id) ."\" value=\"". html_encode($objective->getID()) ."\" data-element-id=\"". html_encode($afelement_id) ."\" " . ($objective_id === $objective->getID() ? "checked=\"checked\"" : "") . " />";
                $html[] =                            html_encode($objective->getName());
                $html[] =                        "</label>";
            }
        } else {
            $html[] =                    "No objectives found to display";
        }
        $html[] =                "</div>";
        $html[] =            "</td>";
        $html[] =        "</tr>";
        $html[] =    "</table>";
        $html[] = "</div>";
        $html[] = "</div>";        
        
        echo implode("\n", $html);
    }
}


