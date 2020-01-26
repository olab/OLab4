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
 * Utility class that finds all form blueprints that require publishing, and
 * publishes them.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Assessments_PublishFormBlueprint extends Entrada_Assessments_Base {

    public function run($verbosity = false) {
        $this->setVerbose($verbosity);

        $form_blueprints_list = Models_Assessments_Form_Blueprint::fetchIncompleteList();

        if (empty($form_blueprints_list)) {
            $this->verboseOut("\n{$this->cliString("No blueprints to publish.", "green")}\n");
            return true;
        }

        foreach ($form_blueprints_list as $blueprint_info) {
            $form_blueprint_id = $blueprint_info["form_blueprint_id"];
            $organisation_id = $blueprint_info["organisation_id"];
            $creator_proxy_id = $blueprint_info["created_by"];

            $queue_start_time = microtime(true);
            $forms_api = new Entrada_Assessments_Forms(array(
                "actor_proxy_id" => $creator_proxy_id,
                "actor_organisation_id" => $organisation_id,
                "form_blueprint_id" => $form_blueprint_id
            ));
            $this->verboseOut("\n{$this->cliString("Fetch form blueprint for ID {$form_blueprint_id}", "black", "white")}\n");

            $blueprint_dataset = $forms_api->fetchFormBlueprintData();
            if (empty($blueprint_dataset)) {
                $this->verboseOut("\n{$this->cliString("Blueprint dataset came back empty (invalid blueprint).", "red", "grey")}.\n");
                continue;
            }
            if (!$forms_api->isFormBlueprintPublished()) {
                $this->verboseOut("\n{$this->cliString("This blueprint is not published. Ignoring.", "yellow", "grey")}.\n");
                continue;
            }
            if ($forms_api->isFormBlueprintComplete()) {
                $this->verboseOut("\n{$this->cliString("This blueprint is already complete (forms already created). Ignoring.", "yellow", "grey")}.\n");
                continue;
            }
            // Passed checks, let's publish this form
            if (!$forms_api->publishFormBlueprint()) {
                foreach ($forms_api->getErrorMessages() as $error_message) {
                    $this->verboseOut("{$this->cliString($error_message, "red", "white")}\n");
                }
                continue;
            }
            $queue_end_time = microtime(true);
            $queue_runtime = sprintf("%.3f", ($queue_end_time - $queue_start_time));
            $this->verboseOut("Successfully published [$form_blueprint_id]. Took $queue_runtime seconds.\n");
            $forms_api->clearStorage(); // Clear storage; ensure that no cache collides
        }


        $this->verboseOut("\nCompleted form blueprint publish execution.\n");
        // Execution completed
        return true;
    }

}