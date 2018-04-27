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
 * Factory class to generate blueprint-type objects.
 * New blueprints should be added in the getBlueprint static method.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Blueprints_Factory extends Entrada_Base {

    static public function getBlueprint($form_type_id, $actor_proxy_id, $actor_organisation_id, $additional_construction = array()) {

        // ADRIAN-TODO: Cache this instead of repeatedly hitting the DB for the type
        $form_type = Models_Assessments_Form_Type::fetchAllByFormTypeIDOrganisationID($form_type_id, $actor_organisation_id);
        if (empty($form_type)) {
            return false;
        }
        $construction = array_merge(
            array(
                "actor_proxy_id" => $actor_proxy_id,
                "actor_organisation_id" => $actor_organisation_id
            ),
            $additional_construction
        );
        switch ($form_type->getShortname()) {
            case "cbme_supervisor":
                $blueprint_object = new Entrada_Assessments_Workers_Blueprints_Supervisor($construction);
                break;
            case "cbme_fieldnote":
                $blueprint_object = new Entrada_Assessments_Workers_Blueprints_Fieldnote($construction);
                break;
            case "cbme_multisource_feedback":
                $blueprint_object = new Entrada_Assessments_Workers_Blueprints_MultisourceFeedback($construction);
                break;
            case "cbme_procedure":
                $blueprint_object = new Entrada_Assessments_Workers_Blueprints_Procedure($construction);
                break;
            default:
                $blueprint_object = false;
                break;
        }
        return $blueprint_object;
    }

}