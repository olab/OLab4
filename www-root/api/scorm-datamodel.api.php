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
 * API that save LRS statements to the stats table, used for tincan and scorm
 * module player when no valid LRS settings are found in the database.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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

global $db, $filesystem;

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} else {
    $module = isset($_POST["module"]) ? $_POST["module"] : "";

    if ($module) {
        if ($filesystem->has(STORAGE_LOR . "/" . Entrada_Utilities_Files::getPathFromFilename($module) . $module . "/imsmanifest.xml")) {
            $scorm = Entrada_LearningObject_Scorm::loadScormModule(
                STORAGE_LOR . "/" . Entrada_Utilities_Files::getPathFromFilename($module) . $module . "/imsmanifest.xml"
            );
            if (!$scorm) {
                die("Unsupported Scorm version.");
            }
        }
    } else {
        die("missingparameter");
    }

    $result = true;
    $request = null;
    foreach ($_POST as $element => $value) {
        $element = str_replace('__', '.', $element);
        if (substr($element, 0, 3) == 'cmi') {
            $netelement = preg_replace('/\.N(\d+)\./', "\.\$1\.", $element);
            $result = Entrada_LearningObject_ScormUtils::sendTracking($module, $scorm, $element, $value) && $result;
        }
    }

    if ($result) {
        echo "true\n0";
    } else {
        echo "false\n101";
    }

    if ($request != null) {
        echo "\n" . $request;
    }
}