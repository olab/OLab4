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
 * Primary controller file for the Exam Question Bank submodule.
 * /admin/exams/questions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES["exams"]["resource"], $MODULES["exams"]["permission"], false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    define("IN_QUESTIONS", true);

    $PREFERENCES	= preferences_load($MODULE);

    $SUBMODULE_TEXT = $MODULE_TEXT[$SUBMODULE];

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE, "title" => $translate->_("Questions"));

    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("questions");
    echo $sub_navigation;

    if (($router) && ($router->initRoute())) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    }

    preferences_update($MODULE, $PREFERENCES); ?>
    <script type="text/javascript">
        var submodule_text      = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
        var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
    </script>
    <?php
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/exams/questions.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
}