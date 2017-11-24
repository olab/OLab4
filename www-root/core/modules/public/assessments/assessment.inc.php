<?php
/**
 *
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
 * Module:	Assessments
 * Area:	Public
 * @author Unit: Education Technology Unit
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @version 3.0
 * @copyright Copyright 2015 Queen's University, MEdTech Unit
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessment', 'read', false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_ASSESSMENT", "IN_ASSESSMENT");
    $PROCESSED = array();

    if (($router) && ($router->initRoute())) {
        $PREFERENCES = preferences_load($MODULE);
        if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
            $STEP = (int) trim($_GET["step"]);
        } elseif((isset($_POST["step"])) && ((int) trim($_POST["step"]))) {
            $STEP = (int) trim($_POST["step"]);
        } else {
            $STEP = 1;
        }

        $PROCESSED["form_id"] = 0;
        if((isset($_GET["form_id"])) && ($tmp_input = clean_input($_GET["form_id"], array("trim","int")))) {
            $PROCESSED["form_id"] = $tmp_input;
        } elseif((isset($_POST["form_id"])) && ($tmp_input = clean_input($_POST["form_id"], array("trim","int")))) {
            $PROCESSED["form_id"] = $tmp_input;
        }

        $PROCESSED["adistribution_id"] = 0;
        if((isset($_GET["adistribution_id"])) && ($tmp_input = clean_input($_GET["adistribution_id"], array("trim","int")))) {
            $PROCESSED["adistribution_id"] = $tmp_input;
        } elseif((isset($_POST["adistribution_id"])) && ($tmp_input = clean_input($_POST["adistribution_id"], array("trim","int")))) {
            $PROCESSED["adistribution_id"] = $tmp_input;
        }

        $PROCESSED["aprogress_id"] = 0;
        if((isset($_GET["aprogress_id"])) && ($tmp_input = clean_input($_GET["aprogress_id"], array("trim","int")))) {
            $PROCESSED["aprogress_id"] = $tmp_input;
        } elseif((isset($_POST["aprogress_id"])) && ($tmp_input = clean_input($_POST["aprogress_id"], array("trim","int")))) {
            $PROCESSED["aprogress_id"] = $tmp_input;
        }

        $PROCESSED["target_record_id"] = 0;
        if((isset($_GET["target_record_id"])) && ($tmp_input = clean_input($_GET["target_record_id"], array("trim","int")))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        } elseif((isset($_POST["target_record_id"])) && ($tmp_input = clean_input($_POST["target_record_id"], array("trim","int")))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        }

        $PROCESSED["schedule_id"] = 0;
        if((isset($_GET["schedule_id"])) && ($tmp_input = clean_input($_GET["schedule_id"], array("trim","int")))) {
            $PROCESSED["schedule_id"] = $tmp_input;
        } elseif((isset($_POST["schedule_id"])) && ($tmp_input = clean_input($_POST["schedule_id"], array("trim","int")))) {
            $PROCESSED["schedule_id"] = $tmp_input;
        }

        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update($MODULE, $PREFERENCES);
    }

}