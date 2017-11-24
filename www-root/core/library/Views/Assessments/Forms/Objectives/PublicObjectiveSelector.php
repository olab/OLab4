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
 * Public-facing objective renderer.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Objectives_PublicObjectiveSelector extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("objective_id"));
    }

    protected function renderView($options = array()) {
        global $translate;
        global $ENTRADA_USER; // ADRIAN-TODO: Remove this once objective set refactor is complete.

        $organisation_id = $ENTRADA_USER->getActiveOrganisation();

        $specified_objective_id = $options["objective_id"];
        $afelement_id = @$options["afelement_id"];
        $aprogress_id = @$options["aprogress_id"];
        $objectives = @$options["objectives"];

        $html = array();
        $objective = Models_Objective::fetchRow($specified_objective_id, $active = 1);
        if ($objective) {
            $objective_children = Models_Objective::fetchAllByParentID($organisation_id, $objective->getID(), $active = 1);

            $html[] = "<div class=\"item-container\">";
            $html[] =    "<table class=\"item-table\">";
            $html[] =        "<tr class=\"heading\">";
            $html[] =            "<td colspan=\"1\">";
            $html[] =                "<h3>". ($objective ? html_encode($objective->getName()) : "") ."</h3>";
            $html[] =            "</td>";
            $html[] =        "</tr>";
            $html[] =        "<tr class=\"item-response-view\">";
            $html[] =            "<td colspan=\"1\" id=\"objective-cell-". html_encode($afelement_id) ."\">";
            $selected_objective_id = 0;
            if (is_array($objectives) && array_key_exists($afelement_id, $objectives)) {
                $total_objectives = count($objectives[$afelement_id]);
                $data_indent = ($total_objectives * 14);
                $indent = 0;
                $html[] =                    "<ul id=\"selected-objective-list-". html_encode($afelement_id) ."\" data-indent=\"". html_encode($data_indent - 14) ."\" class=\"assessment-objective-list selected-objective-list\">";
                foreach ($objectives[$afelement_id] as $objective_id) {

                    if ($selected_objective_id < $objective_id) {
                        $selected_objective_id = $objective_id;
                    }

                    $afelement_objective = Models_Objective::fetchRow($objective_id, $active = 1);
                    if ($afelement_objective) {
                        $html[] =                                "<li data-objective-name=\"". html_encode($afelement_objective->getName()) ."\" data-objective-id=\"". html_encode($afelement_objective->getID()) ."\" class=\"collapse-objective-". html_encode($afelement_id) ."\" style=\"padding-left: ". html_encode(($indent)) ."px\">";
                        $html[] =                                    "<a href=\"#\" data-afelement-id=\"". html_encode($afelement_id) ."\" data-objective-name=\"". html_encode($afelement_objective->getName()) ."\" data-objective-id=\"". html_encode($afelement_objective->getID()) ."\" class=\"collapse-objective-btn\" >";
                        $html[] =                                        "<span class=\"assessment-objective-list-spinner hide\">&nbsp;</span>";
                        $html[] =                                        "<span class=\"ellipsis\">&bull;&bull;&bull;</span>";
                        $html[] =                                        "<span class=\"assessment-objective-name\">". html_encode($afelement_objective->getName()) ."</span>";
                        $html[] =                                    "</a>";
                        $html[] =                                "</li>";
                    }
                    $objective_children = Models_Objective::fetchAllByParentID($organisation_id,  $afelement_objective->getID(), $active = 1);
                    $indent += 14;
                }
                $html[] =                    "</ul>";
            }

            if ($objective_children) {
                $html[] =                    "<ul id=\"objective-list-". html_encode($afelement_id) ."\" class=\"assessment-objective-list\">";
                foreach ($objective_children as $child_objective) {
                    $html[] =                            "<li><a href=\"#\" class=\"expand-objective-btn\" data-afelement-id=\"". html_encode($afelement_id) ."\" data-objective-name=\"". html_encode($child_objective->getName()) ."\" data-objective-id=\"". html_encode($child_objective->getID()) ."\"><span id=\"objective-spinner-". html_encode($child_objective->getID()) ."\" class=\"assessment-objective-list-spinner hide\">&nbsp;</span><span id=\"expand-objective-". html_encode($child_objective->getID()) ."\" class=\"plus-sign\">". html_encode("+") ."</span><span class=\"assessment-objective-name\">" . html_encode($child_objective->getName()) ."</span></a></li>";
                }
                $html[] =                    "</ul>";
            } else {
                $fieldnote_item = Models_Assessments_Item::fetchFieldNoteItem($selected_objective_id);

                if ($fieldnote_item) {
                    $html[] =                        "<div id=\"item-fieldnote-container-". html_encode($afelement_id) ."\" class=\"item-fieldnote-container\">";
                    $html[] =                            "<h3>". $fieldnote_item->getItemText() ."</h3>";
                    $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($fieldnote_item->getID());
                    if ($item_responses) {
                        $html[] =                                "<div class=\"fieldnote-responses-container\">";
                        foreach ($item_responses as $response) {
                            $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($aprogress_id, $response->getIresponseID());

                            $html[] =                                    "<div class=\"fieldnote-response-container\">";
                            $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($response->getARDescriptorID());
                            if ($response_descriptor) {
                                $html[] =                                            "<label class=\"radio\">";
                                $html[] =                                                "<input type=\"radio\" value=\"". html_encode($response->getID()) . "\" name=\"objective-" . html_encode($specified_objective_id) . "\" ". ($progress_response ? "checked=\"checked\"" : "") ." />";
                                $html[] =                                                html_encode($response_descriptor->getDescriptor());
                                $html[] =                                            "</label>";
                            }
                            $html[] =                                        $response->getText();
                            $html[] =                                    "</div>";
                        }
                        $html[] =                                "</div>";
                    }
                    $html[] =                        "</div>";
                }
            }
            $html[] =            "</td>";
            $html[] =        "</tr>";
            $html[] =    "</table>";
            $html[] = "</div>";
        }

        echo implode("\n", $html);
    }
}

