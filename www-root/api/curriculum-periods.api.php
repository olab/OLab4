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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine - Med IT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$api = new Entrada_Api("json", true, array(
    "GET" => array(
        "get-periods" => function (array $request) {
            $PROCESSED = array();
            $PROCESSED["organisation_id"] = $this->validateOrganisationID();
            $PROCESSED["curriculum_type_id"] = $this->validateRequestField("type_id", array("trim", "int"));
            $this->verifyOrganisation($PROCESSED["organisation_id"]);
            $periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDOrganisationID($PROCESSED["curriculum_type_id"], $PROCESSED["organisation_id"]);
            $data = array();
            foreach ($periods as $period) {
                $data[] = array(
                    "id" => html_encode($period->getCperiodID()),
                    "title" => html_encode($period->getPeriodText()),
                );
            }
            return $data;
        },
    ),
));
$api->handle();
