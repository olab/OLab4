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
 * HTML view for assessment form type meta
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Assessment_MetaData extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateArray($options, array("form_type_meta", "objectives", "item_objectives"));
    }

    /**
     * Render the meta related data.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        foreach ($options["form_type_meta"] as $option) {
            switch ($option["meta_name"]) {
                case "show_objectives":
                    $this->renderObjectiveHeader($options["objectives"]);
                    break;

                case "show_procedures":
                    $this->renderProcedureHeader($options["item_objectives"]);
                    break;

                    // If there are other meta options to render, we can add them here.
                default:
                    break;
            }
        }
    }

    /**
     * For meta data settings of "show_objectives" type, render all objectives.
     *
     * @param array $form_objectives
     */
    private function renderObjectiveHeader($form_objectives) {
        foreach ($form_objectives as $objective) {
            echo sprintf(
                "<p class='assessment-form-metadata'><strong>%s</strong>: %s</p>",
                html_encode($objective["objective_code"]),
                html_encode($objective["objective_name"])
            );
        }
    }

    /**
     * For meta data settings of "show_procedures" type. Render all procedure objectives.
     *
     * @param array $objectives
     */
    private function renderProcedureHeader($objectives) {
        global $translate;
        $objective_displayed = array();
        foreach ($objectives as $objective) {
            if ($objective["objective_code"] == "procedure") {
                if (!in_array($objective["objective_name"], $objective_displayed)) {
                    echo sprintf(
                        "<p class='assessment-form-metadata'><strong>%s</strong>: %s</p>",
                        $translate->_("Procedure"),
                        html_encode($objective["objective_name"])
                    );
                }
                $objective_displayed[] = $objective["objective_name"];
            }
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render form meta data"); ?></strong>
        </div>
        <?php
    }
}