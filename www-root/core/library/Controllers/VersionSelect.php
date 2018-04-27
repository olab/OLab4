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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Controllers_VersionSelect {

    public static function processVersionSelect() {
        $PROCESSED = array();

        /**
         * Non-required field "cperiod_id" / Curriculum Period
         */
        if ((isset($_POST["version_cperiod_id"])) && ($cperiod_id = clean_input($_POST["version_cperiod_id"], array("int")))) {
            $PROCESSED["version_cperiod_id"] = $cperiod_id;
        } else {
            $PROCESSED["version_cperiod_id"] = null;
        }

        /**
         * Non-required field "version_id" / Curriculum Map Version
         */
        if ((isset($_POST["version_id"])) && ($version_id = clean_input($_POST["version_id"], array("int")))) {
            $PROCESSED["version_id"] = $version_id;
        } else {
            $PROCESSED["version_id"] = null;
        }

        return array($PROCESSED["version_cperiod_id"], $PROCESSED["version_id"]);
    }

    public static function showVersionSelect($curriculum_type_id, $course_id = null) {
        global $PROCESSED, $PREFERENCES;

        $PROCESSED["version_cperiod_id"] = !empty($PROCESSED["version_cperiod_id"]) ? $PROCESSED["version_cperiod_id"] : 0;
        $PROCESSED["version_id"] = !empty($PROCESSED["version_id"]) ? $PROCESSED["version_id"] : 0;

        /**
         * Get Curriculum Periods
         */
        if ($curriculum_type_id) {
            if ($course_id) {
                $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDCourseID($curriculum_type_id, $course_id);
            } else {
                $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeID($curriculum_type_id);
            }
        } else {
            $curriculum_periods = array();
        }

        /**
         * Get Curriculum Period
         */
        if (!$PROCESSED["version_cperiod_id"]) {
            if ($curriculum_periods) {
                $first_curriculum_period = current($curriculum_periods);
                $PROCESSED["version_cperiod_id"] = $first_curriculum_period->getID();
            }
        }

        /**
         * Get Curriculum Map Versions
         */
        if ($PROCESSED["version_cperiod_id"]) {
            $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
            $curriculum_map_versions = $version_repository->fetchVersionsByCourseIDCperiodID($course_id, $PROCESSED["version_cperiod_id"]);
        } else {
            $curriculum_map_versions = array();
        }

        /**
         * Get Curriculum Map Version
         */
        if (!$PROCESSED["version_id"]) {
            if ($curriculum_map_versions) {
                $version_ids = array_map(function (Models_Curriculum_Map_Versions $version) { return $version->getID(); }, $curriculum_map_versions);
                if (isset($PREFERENCES["selected_curriculum_map_version"]) && array_key_exists($PREFERENCES["selected_curriculum_map_version"], $version_ids)) {
                    $PROCESSED["version_id"] = $PREFERENCES["selected_curriculum_map_version"];
                } else {
                    $first_map_version = current($curriculum_map_versions);
                    $PROCESSED["version_id"] = $first_map_version->getID();
                }
            }
        }

        $version_select = new Zend_View();
        $version_select->setScriptPath(ENTRADA_ABSOLUTE."/core/includes/views/");
        $version_select->curriculum_type_id = $curriculum_type_id;
        $version_select->curriculum_periods = $curriculum_periods;
        $version_select->cperiod_id = $PROCESSED["version_cperiod_id"];
        $version_select->curriculum_map_versions = $curriculum_map_versions;
        $version_select->version_id = $PROCESSED["version_id"];
        echo $version_select->render("version-select.inc.php");
    }
}
