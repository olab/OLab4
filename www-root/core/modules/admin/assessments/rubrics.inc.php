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
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES["assessments"]["resource"], $MODULES["assessments"]["permission"], false)) {
    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_RUBRICS", true);

    $SUBMODULE_TEXT = $MODULE_TEXT[$SUBMODULE];

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/rubrics", "title" => $translate->_("Grouped Items"));

    if (($router) && ($router->initRoute())) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    }
    ?>
    <script type="text/javascript">
        var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
        var API_URL = "<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-rubric"; ?>";
        var submodule_text      = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
    </script>
    <?php
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/assessments/rubrics.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
}